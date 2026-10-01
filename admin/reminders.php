<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$page_title = 'Rent Reminders';

// ==========================================
// SMS HELPER FUNCTION
// ==========================================
function sendPaymentSMS($phone_number, $message) {
    // ⚠️ Verify your Android App Username/Token is up to date
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
        curl_close($ch);
        
        return ['success' => true, 'response' => $api_response];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'System error: ' . $e->getMessage()];
    }
}

// ==========================================
// ACTION HANDLERS
// ==========================================

// Save settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (verify_csrf($_POST['csrf'] ?? '')) {
        foreach (['reminder_days_before','reminder_send_time'] as $k) {
            $pdo->prepare("INSERT INTO settings (setting_key,setting_value) VALUES (?,?)
                ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")
                ->execute([$k, $_POST[$k] ?? '']);
        }
        set_flash('success', 'Reminder settings saved.');
    }
    header('Location: reminders.php'); 
    exit;
}

// Generate reminders now
if (isset($_POST['generate'])) {
    // 1. Auto-flag any pending/unpaid as overdue if the date has passed
    $pdo->exec("UPDATE rent_payments SET status='overdue' WHERE status IN ('unpaid','pending') AND due_date < CURDATE()");

    $daysBefore = (int)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='reminder_days_before'")->fetchColumn() ?: 3);
    $smsSentCount = 0;

    // 2. Process UPCOMING payments (App Notifications only)
    $upcoming = $pdo->query("
        SELECT rp.*, t.full_name, t.contact_number 
        FROM rent_payments rp
        JOIN tenants t ON t.id=rp.tenant_id
        WHERE rp.status IN ('unpaid','pending')
          AND DATEDIFF(rp.due_date, CURDATE()) BETWEEN 0 AND $daysBefore
    ")->fetchAll();

    foreach ($upcoming as $u) {
        $days = (int)((strtotime($u['due_date']) - strtotime(date('Y-m-d'))) / 86400);
        $msg = $days === 0
            ? "Your rent is due today."
            : ($days === 1 ? "Your rent is due tomorrow." : "Your rent is due in $days days.");
        $type = $days === 0 ? 'due' : 'upcoming';
        
        $pdo->prepare("INSERT INTO rent_reminders (tenant_id,payment_id,reminder_type,message) VALUES (?,?,?,?)")
            ->execute([$u['tenant_id'], $u['id'], $type, $msg]);
        add_notification($pdo, 'Rent Reminder', $msg, $type, null, $u['tenant_id']);
    }

    // 3. Process OVERDUE payments (Exactly 1 day overdue + SMS)
    $overdue = $pdo->query("
        SELECT rp.*, t.full_name, t.contact_number 
        FROM rent_payments rp 
        JOIN tenants t ON t.id=rp.tenant_id 
        WHERE rp.status='overdue' 
        AND DATEDIFF(CURDATE(), rp.due_date) = 1
    ")->fetchAll();

    foreach ($overdue as $o) {
        $formatted_amount = number_format($o['amount'], 2);
        $msg = "RentBuddy Alert: Hi " . $o['full_name'] . ", your rent payment of ₱$formatted_amount was due yesterday and is now 1 day overdue. Please settle your account immediately to avoid penalties.";
        
        // Log to database and app
        $pdo->prepare("INSERT INTO rent_reminders (tenant_id,payment_id,reminder_type,message) VALUES (?,?,?,?)")
            ->execute([$o['tenant_id'], $o['id'], 'overdue', $msg]);
        add_notification($pdo, 'Overdue Rent Notice', $msg, 'overdue', null, $o['tenant_id']);

        // Send SMS to the tenant
        if (!empty($o['contact_number'])) {
            sendPaymentSMS($o['contact_number'], $msg);
            $smsSentCount++;
        }
    }

    set_flash('success', "Reminders generated. $smsSentCount overdue SMS alerts were sent.");
    header('Location: reminders.php'); 
    exit;
}

// Fetch display data
$daysBefore = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='reminder_days_before'")->fetchColumn() ?: '3';
$sendTime   = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='reminder_send_time'")->fetchColumn() ?: '08:00';

$history = $pdo->query("
    SELECT r.*, t.full_name, rp.amount, rp.due_date
    FROM rent_reminders r
    JOIN tenants t ON t.id = r.tenant_id
    LEFT JOIN rent_payments rp ON rp.id = r.payment_id
    ORDER BY r.sent_at DESC LIMIT 100
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h3>Reminder Settings</h3></div>
    <form method="POST" class="toolbar">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="save_settings" value="1">
        
        <div class="form-group">
            <label>Days before due date</label>
            <input type="number" name="reminder_days_before" value="<?= e($daysBefore) ?>" min="1" max="30">
        </div>
        <div class="form-group">
            <label>Send time</label>
            <input type="time" name="reminder_send_time" value="<?= e($sendTime) ?>">
        </div>
        
        <div style="align-self:flex-end;">
            <button class="btn btn-primary">Save Settings</button>
            <button class="btn btn-success" name="generate" value="1" type="submit" formnovalidate>Generate Now</button>
        </div>
    </form>
    <div class="alert alert-info mt-2">
        <strong>Tip:</strong> Clicking "Generate Now" will process upcoming reminders and automatically send SMS notifications to any tenant whose payment is <strong>exactly 1 day overdue</strong>.
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Reminder History</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>Tenant</th><th>Type</th><th>Message</th><th>Due Date</th></tr></thead>
            <tbody>
            <?php if (empty($history)): ?>
                <tr><td colspan="5" class="text-center">No reminders generated yet.</td></tr>
            <?php else: foreach ($history as $h): ?>
                <tr>
                    <td><?= date('M d, Y H:i', strtotime($h['sent_at'])) ?></td>
                    <td><?= e($h['full_name']) ?></td>
                    <td><?= status_badge($h['reminder_type']) ?></td>
                    <td><?= e($h['message']) ?></td>
                    <td><?= $h['due_date'] ? date('M d, Y', strtotime($h['due_date'])) : '—' ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>