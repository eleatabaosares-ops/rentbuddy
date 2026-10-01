<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$page_title = 'Lease Tracking';

// Handle new lease
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_lease'])) {
    if (verify_csrf($_POST['csrf'] ?? '')) {
        $stmt = $pdo->prepare("INSERT INTO leases (tenant_id,unit_id,start_date,end_date,monthly_rent,deposit,status) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([
            (int)$_POST['tenant_id'], (int)$_POST['unit_id'],
            $_POST['start_date'], $_POST['end_date'],
            (float)$_POST['monthly_rent'], (float)$_POST['deposit'],
            lease_status($_POST['end_date'])
        ]);
        $pdo->prepare("UPDATE units SET status='occupied' WHERE id=?")->execute([(int)$_POST['unit_id']]);
        $pdo->prepare("UPDATE tenants SET unit_id=? WHERE id=?")->execute([(int)$_POST['unit_id'], (int)$_POST['tenant_id']]);
        set_flash('success', 'Lease created successfully.');
    }
    header('Location: leases.php'); exit;
}

$leases = $pdo->query("
    SELECT l.*, t.full_name, u.unit_number, p.name AS property_name
    FROM leases l
    JOIN tenants t ON t.id = l.tenant_id
    JOIN units u ON u.id = l.unit_id
    JOIN properties p ON p.id = u.property_id
    ORDER BY l.end_date ASC
")->fetchAll();

// Refresh statuses
foreach ($leases as &$l) {
    $l['computed_status'] = lease_status($l['end_date']);
    if ($l['computed_status'] !== $l['status']) {
        $pdo->prepare("UPDATE leases SET status=? WHERE id=?")->execute([$l['computed_status'], $l['id']]);
        $l['status'] = $l['computed_status'];
    }
}

$tenants = $pdo->query("SELECT id, full_name FROM tenants ORDER BY full_name")->fetchAll();
$units = $pdo->query("SELECT u.*, p.name AS property_name FROM units u JOIN properties p ON p.id=u.property_id WHERE u.status != 'occupied' OR u.status IS NULL ORDER BY p.name, u.unit_number")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3>Lease Records</h3>
        <button class="btn btn-primary btn-sm" data-modal-open="modalAddLease">+ New Lease</button>
    </div>
    <div class="toolbar">
        <input type="text" placeholder="Search leases..." data-table-search="#leasesTable">
    </div>
    <div class="table-wrap">
        <table id="leasesTable">
            <thead>
                <tr><th>Tenant</th><th>Property / Unit</th><th>Start</th><th>End</th><th>Monthly Rent</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ($leases as $l): ?>
                <tr>
                    <td><?= e($l['full_name']) ?></td>
                    <td><?= e($l['property_name'].' / '.$l['unit_number']) ?></td>
                    <td><?= date('M d, Y', strtotime($l['start_date'])) ?></td>
                    <td><?= date('M d, Y', strtotime($l['end_date'])) ?></td>
                    <td><?= money($l['monthly_rent']) ?></td>
                    <td><?= status_badge($l['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-backdrop" id="modalAddLease">
    <div class="modal">
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="add_lease" value="1">
            <div class="modal-header"><h3>New Lease</h3><button type="button" class="modal-close" data-modal-close>×</button></div>
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
                    <label>Unit</label>
                    <select name="unit_id" required>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= e($u['property_name'].' / '.$u['unit_number'].' ('.money($u['monthly_rent']).')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-2"><label>Start Date</label><input type="date" name="start_date" required></div>
                <div class="form-group mb-2"><label>End Date</label><input type="date" name="end_date" required></div>
                <div class="form-group mb-2"><label>Monthly Rent</label><input type="number" step="0.01" name="monthly_rent" required></div>
                <div class="form-group"><label>Deposit</label><input type="number" step="0.01" name="deposit" value="0"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>