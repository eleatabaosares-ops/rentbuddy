<?php
require_once __DIR__ . '/../includes/auth.php';
require_tenant();
$page_title = 'Maintenance Requests';
$uid = current_user()['tenant_id'];

// ==========================================
// SMS HELPER FUNCTION
// ==========================================
function sendAdminSMS($phone_number, $message) {
    // Ensure these match your active SMS Gateway App credentials
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    if (verify_csrf($_POST['csrf'] ?? '')) {
        $photo = null;
        if (!empty($_FILES['photo']['name'])) {
            $allowed = ['jpg','jpeg','png'];
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed) && $_FILES['photo']['size'] <= 3*1024*1024) {
                if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0775, true);
                $photo = 'maint_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], UPLOAD_DIR . $photo);
            }
        }
        
        // 1. Insert the maintenance request
        $stmt = $pdo->prepare("INSERT INTO maintenance_requests (tenant_id, unit_id, category, description, priority, photo_path) VALUES (?,?,?,?,?,?)");
        $stmt->execute([
            $uid, $_POST['unit_id'] ?: null,
            trim($_POST['category']), trim($_POST['description']),
            $_POST['priority'], $photo
        ]);
        
        $newRequestId = $pdo->lastInsertId();
        
        // 2. Notify the Tenant Internally
        add_notification($pdo, 'Maintenance Submitted',
            "Your request (#" . $newRequestId . ") has been received.",
            'info', null, $uid);
            
        // 3. SMS LOGIC: Notify the Admin/Landlord
        // Fetch the admin phone number from settings
        $adminPhoneStmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='admin_phone'");
        $adminPhone = $adminPhoneStmt->fetchColumn();

        // Fetch the tenant's full name
        $tenantStmt = $pdo->prepare("SELECT full_name FROM tenants WHERE id=?");
        $tenantStmt->execute([$uid]);
        $tenantName = $tenantStmt->fetchColumn();

        if ($adminPhone && $tenantName) {
            $category = trim($_POST['category']);
            $priority = ucfirst($_POST['priority']);
            
            $sms_message = "RentBuddy Alert: New $priority priority maintenance request ($category) submitted by $tenantName. Req #$newRequestId.";
            
            // Trigger the SMS silently in the background
            sendAdminSMS($adminPhone, $sms_message);
        }

        set_flash('success', 'Maintenance request submitted.');
    }
    header('Location: maintenance.php'); 
    exit;
}

$requests = $pdo->prepare("SELECT * FROM maintenance_requests WHERE tenant_id=? ORDER BY created_at DESC");
$requests->execute([$uid]);
$requests = $requests->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3>My Maintenance Requests</h3>
        <button class="btn btn-primary btn-sm" data-modal-open="modalNewReq">+ New Request</button>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Category</th><th>Priority</th><th>Status</th><th>Submitted</th></tr></thead>
            <tbody>
            <?php foreach ($requests as $r): ?>
                <tr>
                    <td>#<?= $r['id'] ?></td>
                    <td><?= e($r['category']) ?></td>
                    <td><?= status_badge($r['priority']) ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($requests)): ?>
                <tr><td colspan="5" class="text-center">No requests yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-backdrop" id="modalNewReq">
    <div class="modal">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="submit" value="1">
            <input type="hidden" name="unit_id" value="<?= (int)($pdo->query("SELECT unit_id FROM tenants WHERE id=$uid")->fetchColumn()) ?>">
            <div class="modal-header"><h3>New Maintenance Request</h3><button type="button" class="modal-close" data-modal-close>×</button></div>
            <div class="modal-body">
                <div class="form-group mb-2">
                    <label>Category</label>
                    <select name="category" required>
                        <option>Plumbing</option>
                        <option>Electrical</option>
                        <option>HVAC</option>
                        <option>Appliance</option>
                        <option>General</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="form-group mb-2">
                    <label>Priority</label>
                    <select name="priority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div class="form-group mb-2"><label>Description</label><textarea name="description" rows="4" required></textarea></div>
                <div class="form-group"><label>Photo (optional, max 3MB)</label><input type="file" name="photo" accept="image/*"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button class="btn btn-primary">Submit</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>