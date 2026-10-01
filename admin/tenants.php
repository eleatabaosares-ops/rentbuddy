<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$page_title = 'Tenants';

// Handle new tenant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tenant'])) {
    if (verify_csrf($_POST['csrf'] ?? '')) {
        $email = trim($_POST['email']);
        $username = trim($_POST['username'] ?? '');
        $createLogin = !empty($_POST['create_login']);
        $hasError = false;

        // 1. Validate duplicate email or username BEFORE trying to insert
        if ($createLogin && !empty($username) && !empty($_POST['password'])) {
            $chk = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $chk->execute([$username, $email]);
            
            if ($chk->fetch()) {
                // If a match is found, trigger a friendly alert and stop the insertion
                set_flash('danger', 'Error: A user account with that username or email already exists.');
                $hasError = true;
            }
        }

        // 2. If validation passes, proceed with database insertions safely
        if (!$hasError) {
            try {
                $stmt = $pdo->prepare("INSERT INTO tenants (full_name,contact_number,email,address,unit_id) VALUES (?,?,?,?,?)");
                $stmt->execute([
                    trim($_POST['full_name']), trim($_POST['contact_number']),
                    $email, trim($_POST['address']),
                    ($_POST['unit_id'] ?: null)
                ]);
                $tid = $pdo->lastInsertId();

                // Create user login account
                if ($createLogin && !empty($username) && !empty($_POST['password'])) {
                    $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username,email,password_hash,role,tenant_id) VALUES (?,?,?,?,?)");
                    $stmt->execute([$username, $email, $hash, 'tenant', $tid]);
                }

                // Update unit status to occupied
                if (!empty($_POST['unit_id'])) {
                    $pdo->prepare("UPDATE units SET status='occupied' WHERE id=?")->execute([(int)$_POST['unit_id']]);
                }

                set_flash('success', 'Tenant added successfully.');
                
            } catch (PDOException $e) {
                // 3. Fallback catch for any other database constraint violations
                if ($e->getCode() == 23000) {
                    set_flash('danger', 'Error: Database integrity constraint violation (e.g., duplicate entry).');
                } else {
                    set_flash('danger', 'Database Error: ' . $e->getMessage());
                }
            }
        }
    }
    header('Location: tenants.php');
    exit;
}

// Delete tenant
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM tenants WHERE id=?")->execute([(int)$_GET['delete']]);
    set_flash('success', 'Tenant deleted.');
    header('Location: tenants.php');
    exit;
}

// Fetch all tenants for the data table
$tenants = $pdo->query("
    SELECT t.*, u.unit_number, p.name AS property_name
    FROM tenants t
    LEFT JOIN units u ON u.id = t.unit_id
    LEFT JOIN properties p ON p.id = u.property_id
    ORDER BY t.full_name
")->fetchAll();

$units = $pdo->query("SELECT u.*, p.name AS property_name FROM units u JOIN properties p ON p.id=u.property_id ORDER BY p.name,u.unit_number")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3>All Tenants</h3>
        <button class="btn btn-primary btn-sm" data-modal-open="modalAddTenant">+ Add Tenant</button>
    </div>
    <div class="toolbar">
        <input type="text" placeholder="Search tenants..." data-table-search="#tenantsTable">
    </div>
    <div class="table-wrap">
        <table id="tenantsTable">
            <thead>
                <tr><th>ID</th><th>Name</th><th>Contact</th><th>Email</th><th>Unit</th><th>Property</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($tenants as $t): ?>
                <tr>
                    <td>#<?= $t['id'] ?></td>
                    <td><?= e($t['full_name']) ?></td>
                    <td><?= e($t['contact_number']) ?></td>
                    <td><?= e($t['email']) ?></td>
                    <td><?= e($t['unit_number'] ?? '--') ?></td>
                    <td><?= e($t['property_name'] ?? '--') ?></td>
                    <td>
                        <a href="leases.php?tenant=<?= $t['id'] ?>" class="btn btn-sm btn-outline">View</a>
                        <a href="?delete=<?= $t['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Delete this tenant?">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Tenant Modal -->
<div class="modal-backdrop" id="modalAddTenant">
    <div class="modal">
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="add_tenant" value="1">
            <div class="modal-header">
                <h3>Add Tenant</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-2">
                    <label>Full Name</label>
                    <input name="full_name" required>
                </div>
                <div class="form-group mb-2">
                    <label>Contact Number</label>
                    <input name="contact_number">
                </div>
                <div class="form-group mb-2">
                    <label>Email</label>
                    <input type="email" name="email">
                </div>
                <div class="form-group mb-2">
                    <label>Address</label>
                    <input name="address">
                </div>
                <div class="form-group mb-2">
                    <label>Assign Unit</label>
                    <select name="unit_id">
                        <option value="">-- None --</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>">
                                <?= e($u['property_name'].' / '.$u['unit_number'].' ('.money($u['monthly_rent']).')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <hr style="margin:12px 0;border:none;border-top:1px solid var(--gray-200);">
                
                <label style="display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" name="create_login" value="1"> Create tenant login account
                </label>
                <div class="form-group mt-2">
                    <label>Username</label>
                    <input name="username">
                </div>
                <div class="form-group mt-2">
                    <label>Password</label>
                    <input type="password" name="password">
                </div>  
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>