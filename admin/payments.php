<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$page_title = 'Rent & Payments';

// ==========================================
// SMS HELPER FUNCTION
// ==========================================
function sendPaymentSMS($phone_number, $message) {
    // ⚠️ Ensure these match your active SMS Gateway App credentials
    $sms_username = '04DB73'; 
    $sms_password = 'liealjinfernandez';
    
    $api_endpoint = BASE_URL . 'sms/function/sms.php';

    $clean_number = preg_replace('/[^0-9]/', '', $phone_number);
    $final_number = null;

    if (strlen($clean_number) === 11 && $clean_number[0] === '0') {
        $final_number = substr($clean_number, 1); 
    } elseif (strlen($clean_number) === 10 && $clean_number[0] === '9') {
        $final_number = $clean_number; 
    } elseif (strpos($clean_number, '639') === 0 && strlen($clean_number) === 12) {
        $final_number = substr($clean_number, 2); 
    } elseif (strpos($clean_number, '639') === 1 && strlen($clean_number) === 13) {
        $final_number = substr($clean_number, 3);
    }

    if (!$final_number) {
        return ['success' => false, 'error' => 'Invalid phone number format.'];
    }

    try {
        $ch = curl_init();
        $post_data = array(
            'username' => $sms_username,
            'password' => $sms_password,
            'number'   => $final_number,
            'message'  => $message
        );

        curl_setopt($ch, CURLOPT_URL, $api_endpoint); 
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data)); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); 
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        
        $api_response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return ['success' => false, 'error' => 'cURL Error: ' . $error_msg];
        }
        
        curl_close($ch);
        
        if ($api_response === false || 
            stripos($api_response, 'error') !== false || 
            stripos($api_response, 'fail') !== false ||
            stripos($api_response, 'Unauthorized') !== false ||
            empty($api_response)) 
        {
            return ['success' => false, 'error' => 'API rejected the request: ' . $api_response];
        }

        return ['success' => true, 'response' => $api_response];

    } catch (Exception $e) {
        return ['success' => false, 'error' => 'System error: ' . $e->getMessage()];
    }
}

// ==========================================
// 1. BACKEND ACTION HANDLERS
// ==========================================

// Handle: Add manual payment record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    if (verify_csrf($_POST['csrf'] ?? '')) {
        $ref = generate_reference();
        $amount = (float)$_POST['amount'];
        $tenant_id = (int)$_POST['tenant_id'];
        $due_date = $_POST['due_date'];
        
        $stmt = $pdo->prepare("INSERT INTO rent_payments (tenant_id,lease_id,amount,due_date,payment_date,reference_no,status,notes) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $tenant_id,
            $_POST['lease_id'] ?: null,
            $amount,
            $due_date,
            $_POST['payment_date'] ?: null,
            $ref,
            $_POST['status'],
            trim($_POST['notes'])
        ]);
        
        $notif_pref = $_POST['notif_pref'] ?? 'auto';
        if ($notif_pref === 'manual_now') {
            $formatted_date = date('M d, Y', strtotime($due_date));
            $msg = "RentBuddy Notice: A manual payment of ₱" . number_format($amount, 2) . " is due on " . $formatted_date . ".";
            
            add_notification($pdo, "Payment Due Notice", $msg, 'due', null, $tenant_id);
            
            $tStmt = $pdo->prepare("SELECT contact_number FROM tenants WHERE id = ?");
            $tStmt->execute([$tenant_id]);
            $tPhone = $tStmt->fetchColumn();
            
            if ($tPhone) {
                sendPaymentSMS($tPhone, $msg);
            }
        }

        set_flash('success', "Payment record added (Ref: $ref).");
    }
    header('Location: payments.php'); 
    exit;
}

// Handle: Delete payment record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_payment'])) {
    if (verify_csrf($_POST['csrf'] ?? '')) {
        $payment_id = (int)$_POST['payment_id'];
        $pdo->prepare("DELETE FROM rent_payments WHERE id = ?")->execute([$payment_id]);
        set_flash('success', 'Payment record deleted successfully.');
    }
    header('Location: payments.php'); 
    exit;
}

// Handle: Mark paid
if (isset($_GET['mark_paid'])) {
    $ref = generate_reference();
    $pdo->prepare("UPDATE rent_payments SET status='paid', payment_date=NOW(), reference_no=? WHERE id=?")->execute([$ref, (int)$_GET['mark_paid']]);
    
    $pmt = $pdo->prepare("SELECT tenant_id, amount FROM rent_payments WHERE id=?");
    $pmt->execute([(int)$_GET['mark_paid']]);
    $paymentData = $pmt->fetch();
    
    if ($paymentData) {
        add_notification($pdo, 'Payment Received', "Thank you! Your payment of ₱" . number_format($paymentData['amount'], 2) . " has been verified. Ref: $ref", 'success', null, $paymentData['tenant_id']);
    }
    
    set_flash('success', "Payment marked as paid with Reference: $ref.");
    header('Location: payments.php'); 
    exit;
}

// Handle: Send Manual Notification with Optional Note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
    if (verify_csrf($_POST['csrf'] ?? '')) {
        $payment_id = (int)$_POST['payment_id'];
        $custom_note = trim($_POST['custom_note'] ?? '');
        
        $pmtStmt = $pdo->prepare("
            SELECT p.amount, p.due_date, p.tenant_id, t.contact_number 
            FROM rent_payments p
            JOIN tenants t ON t.id = p.tenant_id
            WHERE p.id = ?
        ");
        $pmtStmt->execute([$payment_id]);
        $pmtData = $pmtStmt->fetch();

        if ($pmtData) {
            $formatted_date = date('M d, Y', strtotime($pmtData['due_date']));
            $msg = "RentBuddy Reminder: Your rent payment of ₱" . number_format($pmtData['amount'], 2) . " is due on " . $formatted_date . ".";
            
            if (!empty($custom_note)) {
                $msg .= " Note: " . $custom_note;
            }
            
            add_notification($pdo, "Payment Reminder", $msg, 'info', null, $pmtData['tenant_id']);
            
            if (!empty($pmtData['contact_number'])) {
                $smsResult = sendPaymentSMS($pmtData['contact_number'], $msg);
                if ($smsResult['success']) {
                    set_flash('success', 'Manual payment reminder and SMS sent to the tenant successfully.');
                } else {
                    set_flash('warning', 'App reminder sent, but SMS failed: ' . $smsResult['error']);
                }
            } else {
                set_flash('warning', 'App reminder sent, but tenant has no phone number on record for SMS.');
            }
        } else {
            set_flash('danger', 'Error: Payment record not found.');
        }
    }
    header('Location: payments.php'); 
    exit;
}

// Auto-flag overdue payments daily
$pdo->exec("UPDATE rent_payments SET status='overdue' WHERE status IN ('unpaid','pending') AND due_date < CURDATE()");

// ==========================================
// 🚀 AUTOMATIC 1-DAY OVERDUE SMS SENDER
// ==========================================
$overdue_check = $pdo->query("
    SELECT rp.*, t.full_name, t.contact_number 
    FROM rent_payments rp 
    JOIN tenants t ON t.id=rp.tenant_id 
    WHERE rp.status='overdue' 
    AND DATEDIFF(CURDATE(), rp.due_date) = 1
")->fetchAll();

foreach ($overdue_check as $o) {
    // Check if we ALREADY sent an overdue reminder for this specific payment to prevent spam
    $already_sent = $pdo->prepare("SELECT COUNT(*) FROM rent_reminders WHERE payment_id = ? AND reminder_type = 'overdue'");
    $already_sent->execute([$o['id']]);
    
    if ($already_sent->fetchColumn() == 0) {
        $formatted_amount = number_format($o['amount'], 2);
        $msg = "RentBuddy Alert: Hi " . $o['full_name'] . ", your rent payment of ₱$formatted_amount was due yesterday and is now 1 day overdue. Please settle your account immediately.";
        
        // Log to database to ensure it never sends twice
        $pdo->prepare("INSERT INTO rent_reminders (tenant_id,payment_id,reminder_type,message) VALUES (?,?,?,?)")
            ->execute([$o['tenant_id'], $o['id'], 'overdue', $msg]);
        
        add_notification($pdo, 'Overdue Rent Notice', $msg, 'overdue', null, $o['tenant_id']);

        // Send SMS silently
        if (!empty($o['contact_number'])) {
            sendPaymentSMS($o['contact_number'], $msg);
        }
    }
}

// ==========================================
// 2. FILTER AND SORTING LOGIC
// ==========================================
$active_filter = $_GET['filter'] ?? 'all';

$sql = "
    SELECT rp.*, t.full_name
    FROM rent_payments rp
    JOIN tenants t ON t.id = rp.tenant_id
";

if ($active_filter === 'paid') {
    $sql .= " WHERE rp.status = 'paid'";
} elseif ($active_filter === 'pending') {
    $sql .= " WHERE rp.status IN ('pending', 'unpaid')"; 
} elseif ($active_filter === 'overdue') {
    $sql .= " WHERE rp.status = 'overdue'";
}

$sql .= " ORDER BY rp.due_date DESC, rp.created_at DESC";
$payments = $pdo->query($sql)->fetchAll();

$tenants = $pdo->query("SELECT id, full_name FROM tenants ORDER BY full_name")->fetchAll();
$leases  = $pdo->query("
    SELECT l.id, l.tenant_id, l.monthly_rent, u.unit_number, p.name AS property_name 
    FROM leases l
    JOIN units u ON u.id = l.unit_id
    JOIN properties p ON p.id = u.property_id
    WHERE l.status IN ('active','expiring')
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3>All Payments</h3>
        <button class="btn btn-primary btn-sm" data-modal-open="modalAddPayment">+ Add Payment</button>
    </div>
    
    <div class="tabs" style="margin-bottom: 16px;">
        <a href="?filter=all" class="tab <?= $active_filter === 'all' ? 'active' : '' ?>" style="text-decoration: none;">All</a>
        <a href="?filter=paid" class="tab <?= $active_filter === 'paid' ? 'active' : '' ?>" style="text-decoration: none;">Paid</a>
        <a href="?filter=pending" class="tab <?= $active_filter === 'pending' ? 'active' : '' ?>" style="text-decoration: none;">Pending</a>
        <a href="?filter=overdue" class="tab <?= $active_filter === 'overdue' ? 'active' : '' ?>" style="text-decoration: none;">Overdue</a>
    </div>

    <div class="toolbar">
        <input type="text" placeholder="Search filtered payments..." data-table-search="#paymentsTable">
    </div>
    
    <div class="table-wrap">
        <table id="paymentsTable">
            <thead>
                <tr><th>Ref</th><th>Tenant</th><th>Amount</th><th>Due Date</th><th>Payment Date</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if (empty($payments)): ?>
                <tr><td colspan="7" class="text-center" style="padding: 20px;">No payments found in this category.</td></tr>
            <?php else: ?>
                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?= e($p['reference_no'] ?? '—') ?></td>
                        <td><?= e($p['full_name']) ?></td>
                        <td>₱<?= number_format($p['amount'], 2) ?></td>
                        <td><?= date('M d, Y', strtotime($p['due_date'])) ?></td>
                        <td><?= $p['payment_date'] ? date('M d, Y H:i', strtotime($p['payment_date'])) : '—' ?></td>
                        <td><?= status_badge($p['status']) ?></td>
                        
                        <td style="display:flex; align-items: center; gap: 8px;">
                            <div style="display: flex; align-items: center; gap: 8px; min-width: 150px;">
                                <?php if ($p['status'] !== 'paid'): ?>
                                    <a href="?mark_paid=<?= $p['id'] ?>" class="btn btn-sm btn-success" data-confirm="Mark as paid?">Mark Paid</a>
                                    <button class="btn btn-sm btn-outline" data-modal-open="modalNotify<?= $p['id'] ?>">Notify</button>
                                <?php else: ?>
                                    <span style="color:var(--green); font-size:.85rem; font-weight:600;">✓ Verified</span>
                                <?php endif; ?>
                            </div>
                            
                            <button class="btn btn-sm btn-danger" data-modal-open="modalDelete<?= $p['id'] ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Payment Modal -->
<div class="modal-backdrop" id="modalAddPayment">
    <div class="modal">
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="add_payment" value="1">
            <div class="modal-header">
                <h3>Add Payment Record</h3>
                <button type="button" class="modal-close" data-modal-close>×</button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-2">
                    <label>Tenant</label>
                    <select name="tenant_id" required>
                        <?php foreach ($tenants as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= e($t['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group mb-2">
                    <label>Lease (Auto-fills amount)</label>
                    <select name="lease_id" id="leaseSelect">
                        <option value="" data-rent="">— None —</option>
                        <?php foreach ($leases as $l): ?>
                            <option value="<?= $l['id'] ?>" data-rent="<?= $l['monthly_rent'] ?>">
                                <?= e($l['property_name']) ?> - Unit <?= e($l['unit_number']) ?> (₱<?= number_format($l['monthly_rent'], 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group mb-2">
                    <label>Amount (₱)</label>
                    <input type="number" step="0.01" name="amount" id="paymentAmount" required>
                </div>
                
                <div class="form-group mb-2">
                    <label>Due Date</label>
                    <input type="date" name="due_date" required>
                </div>
                
                <div class="form-group mb-2">
                    <label>Payment Date (optional)</label>
                    <input type="datetime-local" name="payment_date">
                </div>
                
                <div class="form-group mb-2">
                    <label>Status</label>
                    <select name="status">
                        <option value="unpaid">Unpaid</option>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="overdue">Overdue</option>
                    </select>
                </div>

                <div class="form-group mb-2">
                    <label>Notification Preference</label>
                    <select name="notif_pref">
                        <option value="auto">Auto-Notify (Wait for system reminder)</option>
                        <option value="manual_now">Notify Manually & Send SMS Now</option>
                        <option value="none">Do Not Notify</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Dynamically Generated Delete Modals -->
<?php foreach ($payments as $p): ?>
<div class="modal-backdrop" id="modalDelete<?= $p['id'] ?>">
    <div class="modal">
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="delete_payment" value="1">
            <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
            <div class="modal-header">
                <h3>Confirm Deletion</h3>
                <button type="button" class="modal-close" data-modal-close>×</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the payment record for <strong><?= e($p['full_name']) ?></strong>?</p>
                <ul style="margin-top:10px; margin-bottom: 10px; padding-left: 20px;">
                    <li><strong>Amount:</strong> ₱<?= number_format($p['amount'], 2) ?></li>
                    <li><strong>Due Date:</strong> <?= date('M d, Y', strtotime($p['due_date'])) ?></li>
                </ul>
                <div class="alert alert-danger mt-2">
                    Warning: This action is permanent and cannot be undone.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-danger">Yes, Delete</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<!-- Dynamically Generated Send Notification Modals -->
<?php foreach ($payments as $p): if ($p['status'] === 'paid') continue; ?>
<div class="modal-backdrop" id="modalNotify<?= $p['id'] ?>">
    <div class="modal">
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="send_notification" value="1">
            <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
            <div class="modal-header">
                <h3>Send SMS Reminder</h3>
                <button type="button" class="modal-close" data-modal-close>×</button>
            </div>
            <div class="modal-body">
                <p>You are about to send a payment reminder to <strong><?= e($p['full_name']) ?></strong> for ₱<?= number_format($p['amount'], 2) ?>.</p>
                <div class="form-group mt-3">
                    <label>Custom Note (Optional)</label>
                    <textarea name="custom_note" rows="3" placeholder="Example: Please pay on or before Friday to avoid late fees."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Send SMS</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<script>
// Automatically fill the Amount field when a Lease is selected
document.addEventListener('DOMContentLoaded', function() {
    const leaseSelect = document.getElementById('leaseSelect');
    const paymentAmount = document.getElementById('paymentAmount');
    
    if(leaseSelect && paymentAmount) {
        leaseSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const rentAmount = selectedOption.getAttribute('data-rent');
            
            if(rentAmount) {
                paymentAmount.value = rentAmount;
            } else {
                paymentAmount.value = '';
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>