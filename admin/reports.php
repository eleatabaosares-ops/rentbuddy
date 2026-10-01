<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$page_title = 'Reports';

$monthly = $pdo->query("
    SELECT DATE_FORMAT(payment_date,'%Y-%m') AS ym,
           SUM(amount) AS total, COUNT(*) AS cnt
    FROM rent_payments
    WHERE status='paid' AND payment_date IS NOT NULL
    GROUP BY ym ORDER BY ym DESC LIMIT 12
")->fetchAll();

$overdue = $pdo->query("
    SELECT rp.*, t.full_name
    FROM rent_payments rp JOIN tenants t ON t.id=rp.tenant_id
    WHERE rp.status='overdue' ORDER BY rp.due_date
")->fetchAll();

$activeLeases   = $pdo->query("SELECT COUNT(*) FROM leases WHERE status='active'")->fetchColumn();
$expiringLeases = $pdo->query("SELECT COUNT(*) FROM leases WHERE status='expiring'")->fetchColumn();
$openMaint      = $pdo->query("SELECT COUNT(*) FROM maintenance_requests WHERE status NOT IN ('completed','cancelled')")->fetchColumn();

$tenants = $pdo->query("SELECT t.*, u.unit_number, p.name AS property_name FROM tenants t LEFT JOIN units u ON u.id=t.unit_id LEFT JOIN properties p ON p.id=u.property_id ORDER BY t.full_name")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card green"><div class="stat-label">Active Leases</div><div class="stat-value"><?= $activeLeases ?></div></div>
    <div class="stat-card yellow"><div class="stat-label">Expiring Soon</div><div class="stat-value"><?= $expiringLeases ?></div></div>
    <div class="stat-card red"><div class="stat-label">Overdue Payments</div><div class="stat-value"><?= count($overdue) ?></div></div>
    <div class="stat-card"><div class="stat-label">Open Maintenance</div><div class="stat-value"><?= $openMaint ?></div></div>
</div>

<div class="card">
    <div class="card-header"><h3>Monthly Rent Collection (Last 12 Months)</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Month</th><th>Payments</th><th>Total Collected</th></tr></thead>
            <tbody>
            <?php if (empty($monthly)): ?>
                <tr><td colspan="3" class="text-center">No payments recorded yet.</td></tr>
            <?php else: foreach ($monthly as $m): ?>
                <tr>
                    <td><?= date('F Y', strtotime($m['ym'].'-01')) ?></td>
                    <td><?= $m['cnt'] ?></td>
                    <td><?= money($m['total']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><h3>Overdue Rent</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Tenant</th><th>Amount</th><th>Due</th></tr></thead>
                <tbody>
                <?php foreach ($overdue as $o): ?>
                    <tr><td><?= e($o['full_name']) ?></td><td><?= money($o['amount']) ?></td><td><?= date('M d, Y', strtotime($o['due_date'])) ?></td></tr>
                <?php endforeach; ?>
                <?php if (empty($overdue)): ?><tr><td colspan="3" class="text-center">None 🎉</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3>Tenant List</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Unit</th><th>Property</th></tr></thead>
                <tbody>
                <?php foreach ($tenants as $t): ?>
                    <tr><td><?= e($t['full_name']) ?></td><td><?= e($t['unit_number'] ?? '—') ?></td><td><?= e($t['property_name'] ?? '—') ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>