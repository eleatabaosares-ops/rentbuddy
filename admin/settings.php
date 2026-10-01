<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$page_title = 'Settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf($_POST['csrf'] ?? '')) {
        
        // Validate the admin phone number to ensure it starts with +63
        $adminPhone = $_POST['settings']['admin_phone'] ?? '';
        if (!empty($adminPhone) && strpos(trim($adminPhone), '+63') !== 0) {
            set_flash('danger', 'Error: Admin phone number must start with +63.');
            header('Location: settings.php');
            exit;
        }

        // Loop through and save all valid settings
        foreach ($_POST['settings'] as $k => $v) {
            $pdo->prepare("INSERT INTO settings (setting_key,setting_value) VALUES (?,?)
                ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute([$k, trim($v)]);
        }
        set_flash('success', 'Settings updated.');
    }
    header('Location: settings.php'); 
    exit;
}

// Fetch current settings from the database
$rows = $pdo->query("SELECT * FROM settings")->fetchAll();
$settings = [];
foreach ($rows as $r) {
    $settings[$r['setting_key']] = $r['setting_value'];
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h3>System Settings</h3></div>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="grid-2">
            <div class="form-group">
                <label>Company Name</label>
                <input name="settings[company_name]" value="<?= e($settings['company_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Currency</label>
                <input name="settings[currency]" value="<?= e($settings['currency'] ?? 'PHP') ?>">
            </div>
            <div class="form-group">
                <label>Reminder Days Before</label>
                <input type="number" name="settings[reminder_days_before]" value="<?= e($settings['reminder_days_before'] ?? '3') ?>">
            </div>
            <div class="form-group">
                <label>Reminder Send Time</label>
                <input type="time" name="settings[reminder_send_time]" value="<?= e($settings['reminder_send_time'] ?? '08:00') ?>">
            </div>
            
            <!-- New Admin Phone Input for SMS -->
            <div class="form-group">
                <label>Admin Phone (SMS Notifications)</label>
                <input type="tel" 
                       name="settings[admin_phone]" 
                       value="<?= e($settings['admin_phone'] ?? '+63') ?>" 
                       pattern="^\+63[0-9]{10}$" 
                       title="Must start with +63 followed by 10 digits (e.g., +639123456789)" 
                       placeholder="+639xxxxxxxxx" 
                       required>
            </div>
        </div>
        <button class="btn btn-primary mt-2">Save Settings</button>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>