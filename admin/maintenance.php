<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$page_title = 'Maintenance Requests';

// Update status / assign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_request'])) {
    if (verify_csrf($_POST['csrf'] ?? '')) {
        $pdo->prepare("UPDATE maintenance_requests SET status=?, assigned_to=? WHERE id=?")
            ->execute([$_POST['status'], $_POST['assigned_to'], (int)$_POST['id']]);
        $pdo->prepare("INSERT INTO maintenance_updates (request_id,status,note,created_by) VALUES (?,?,?,?)")
            ->execute([(int)$_POST['id'], $_POST['status'], $_POST['note'], current_user()['username']]);
        set_flash('success', 'Maintenance request updated.');
    }
    header('Location: maintenance.php'); exit;
}

// Filter
$statusFilter = $_GET['status'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$sql = "SELECT m.*, t.full_name, u.unit_number, p.name AS property_name
        FROM maintenance_requests m
        JOIN tenants t ON t.id = m.tenant_id
        LEFT JOIN units u ON u.id = m.unit_id
        LEFT JOIN properties p ON p.id = u.property_id
        WHERE 1=1";
$params = [];
if ($statusFilter)   { $sql .= " AND m.status = ?";   $params[] = $statusFilter; }
if ($priorityFilter) { $sql .= " AND m.priority = ?"; $params[] = $priorityFilter; }
$sql .= " ORDER BY m.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h3>All Maintenance Requests</h3></div>
    <form method="GET" class="toolbar">
        <select name="status">
            <option value="">All Statuses</option>
            <?php foreach (['submitted','under_review','in_progress','completed','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $statusFilter===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="priority">
            <option value="">All Priorities</option>
            <?php foreach (['low','medium','high','urgent'] as $p): ?>
                <option value="<?= $p ?>" <?= $priorityFilter===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline btn-sm">Filter</button>
        <a href="maintenance.php" class="btn btn-outline btn-sm">Reset</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Tenant</th><th>Unit</th><th>Category</th><th>Priority</th><th>Status</th><th>Assigned</th><th>Date</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $r): ?>
                <tr>
                    <td>#<?= $r['id'] ?></td>
                    <td><?= e($r['full_name']) ?></td>
                    <td><?= e(($r['property_name'] ?? '') . ' / ' . ($r['unit_number'] ?? '—')) ?></td>
                    <td><?= e($r['category']) ?></td>
                    <td><?= status_badge($r['priority']) ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td><?= e($r['assigned_to'] ?? '—') ?></td>
                    <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary" data-modal-open="modalReq<?= $r['id'] ?>">Update</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($requests as $r): ?>
<div class="modal-backdrop" id="modalReq<?= $r['id'] ?>">
    <div class="modal">
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="update_request" value="1">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <div class="modal-header"><h3>Update Request #<?= $r['id'] ?></h3><button type="button" class="modal-close" data-modal-close>×</button></div>
            <div class="modal-body">
                <p style="font-size:.9rem;margin-bottom:12px;"><strong><?= e($r['full_name']) ?>:</strong> <?= e($r['description']) ?></p>
                <div class="form-group mb-2">
                    <label>Status</label>
                    <select name="status">
                        <?php foreach (['submitted','under_review','in_progress','completed','cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $r['status']===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-2"><label>Assigned To</label><input name="assigned_to" value="<?= e($r['assigned_to'] ?? '') ?>"></div>
                <div class="form-group"><label>Note</label><textarea name="note" rows="2" placeholder="Optional update note"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>