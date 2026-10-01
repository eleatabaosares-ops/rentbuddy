<?php
require_once __DIR__ . '/../includes/auth.php';
require_tenant();
$page_title = 'Rent & Payments';
$uid = current_user()['tenant_id'];

// ==========================================
// SMS HELPER FUNCTION
// ==========================================
function sendAdminSMS($phone_number, $message) {
    // Make sure these match your active SMS Gateway App credentials
    $sms_username = '04DB73';
    $sms_password = 'liealjinfernandez';
    
    $api_endpoint = BASE_URL . 'sms/function/sms.php';

    // Normalize Phone Number (Formats to 10 digits starting with 9)
    $clean_number = preg_replace('/[^0-9]/', '', $phone_number);
    $final_number = null;

    if (strlen($clean_number) === 11 && $clean_number[0] === '0') {
        $final_number = substr($clean_number, 1); 
    } elseif (strlen($clean_number) === 10 && $clean_number[0] === '9') {
        $final_number = $clean_number; 
    } elseif (strpos($clean_number, '639') === 0 && strlen($clean_number) === 12) {
        $final_number = substr($clean_number, 2); 
    } elseif (strpos($clean_number, '639') === 1 && strlen($clean_number) === 13) {
        $final_number = substr($clean_number, 3); // Handles +639
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
        curl_close($ch);
        
        return ['success' => true, 'response' => $api_response];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'System error: ' . $e->getMessage()];
    }
}

// ==========================================
// FORM SUBMISSION HANDLER
// ==========================================
// Submit demo payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay'])) {
    if (verify_csrf($_POST['csrf'] ?? '')) {
        $paymentId = (int)$_POST['payment_id'];
        
        // Verify payment belongs to this tenant
        $chk = $pdo->prepare("SELECT * FROM rent_payments WHERE id=? AND tenant_id=?");
        $chk->execute([$paymentId, $uid]);
        $pmt = $chk->fetch();
        
        if ($pmt) {
            $ref = generate_reference();
            
            // 1. Update the database record to paid
            $pdo->prepare("UPDATE rent_payments SET status='paid', payment_date=NOW(), payment_method='demo', reference_no=? WHERE id=?")
                ->execute([$ref, $paymentId]);
                
            // 2. Notify the Tenant Internally
            add_notification($pdo, 'Payment Successful',
                "Your payment of ₱" . number_format($pmt['amount'], 2) . " was received. Ref: $ref",
                'success', null, $uid);
                
            // 3. SMS LOGIC: Notify the Admin/Landlord
            // Fetch the admin phone number from settings
            $adminPhoneStmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='admin_phone'");
            $adminPhone = $adminPhoneStmt->fetchColumn();

            // Fetch the tenant's full name
            $tenantStmt = $pdo->prepare("SELECT full_name FROM tenants WHERE id=?");
            $tenantStmt->execute([$uid]);
            $tenantName = $tenantStmt->fetchColumn();

            if ($adminPhone && $tenantName) {
                $formatted_amount = number_format($pmt['amount'], 2);
                $sms_message = "RentBuddy Alert: Tenant $tenantName has paid ₱$formatted_amount. Ref: $ref.";
                
                // Trigger the SMS silently in the background
                sendAdminSMS($adminPhone, $sms_message);
            }

            set_flash('success', "Demo payment successful! Reference: $ref");
        }
    }
    header('Location: payments.php'); 
    exit;
}

$payments = $pdo->prepare("SELECT * FROM rent_payments WHERE tenant_id=? ORDER BY due_date DESC");
$payments->execute([$uid]);
$payments = $payments->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h3>My Rent Payments</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Ref</th><th>Due Date</th><th>Amount</th><th>Status</th><th>Paid On</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?= e($p['reference_no'] ?? '—') ?></td>
                    <td><?= date('M d, Y', strtotime($p['due_date'])) ?></td>
                    <td>₱<?= number_format($p['amount'], 2) ?></td>
                    <td><?= status_badge($p['status']) ?></td>
                    <td><?= $p['payment_date'] ? date('M d, Y H:i', strtotime($p['payment_date'])) : '—' ?></td>
                    <td>
                        <?php if (in_array($p['status'], ['unpaid','pending','overdue'])): ?>
                            <button class="btn btn-sm btn-success"
                                    data-modal-open="modalPay<?= $p['id'] ?>">Pay Now</button>
                        <?php else: ?>
                            <span style="color:var(--green);font-size:.85rem; font-weight: 600;">Paid ✓</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($payments as $p): if (!in_array($p['status'], ['unpaid','pending','overdue'])) continue; ?>
<div class="modal-backdrop" id="modalPay<?= $p['id'] ?>">
    <div class="modal">
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="pay" value="1">
            <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
            <div class="modal-header"><h3>Confirm Payment</h3><button type="button" class="modal-close" data-modal-close>×</button></div>
            <div class="modal-body">
                <p><strong>Amount:</strong> ₱<?= number_format($p['amount'], 2) ?></p>
                <p><strong>Due Date:</strong> <?= date('M d, Y', strtotime($p['due_date'])) ?></p>
                <div class="alert alert-info mt-2">
                    This is a <strong>demo payment</strong>. No real card details are collected or stored. Clicking "Pay Now" will record a simulated payment for this system.
                </div>
                <div class="form-group mt-2">
                    <label>Payment Method (demo)</label>
                    <select name="method">
                        <option value="demo">Demo / Test Payment</option>
                        <option value="bank">Bank Transfer (demo)</option>
                        <option value="cash">Cash (demo)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button class="btn btn-success" id="payNowBtn">Pay Now</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>