<?php
require_once __DIR__ . '/../includes/auth.php';
require_tenant();
$page_title = 'My Dashboard';
$uid = current_user()['tenant_id'];

$tenant = $pdo->prepare("SELECT t.*, u.unit_number, u.monthly_rent AS unit_rent, p.name AS property_name
                         FROM tenants t
                         LEFT JOIN units u ON u.id = t.unit_id
                         LEFT JOIN properties p ON p.id = u.property_id
                         WHERE t.id = ?");
$tenant->execute([$uid]);
$tenant = $tenant->fetch();

$lease = $pdo->prepare("SELECT * FROM leases WHERE tenant_id = ? ORDER BY end_date DESC LIMIT 1");
$lease->execute([$uid]);
$lease = $lease->fetch();

$payments = $pdo->prepare("SELECT * FROM rent_payments WHERE tenant_id = ? ORDER BY due_date DESC LIMIT 10");
$payments->execute([$uid]);
$payments = $payments->fetchAll();

$overdueAmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM rent_payments WHERE tenant_id=? AND status='overdue'");
$overdueAmt->execute([$uid]);
$overdueAmt = $overdueAmt->fetchColumn();

$pendingAmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM rent_payments WHERE tenant_id=? AND status IN ('unpaid','pending')");
$pendingAmt->execute([$uid]);
$pendingAmt = $pendingAmt->fetchColumn();

$paidAmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM rent_payments WHERE tenant_id=? AND status='paid'");
$paidAmt->execute([$uid]);
$paidAmt = $paidAmt->fetchColumn();

$notifications = $pdo->prepare("SELECT * FROM notifications WHERE tenant_id=? ORDER BY created_at DESC LIMIT 5");
$notifications->execute([$uid]);
$notifications = $notifications->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h3 style="color:var(--navy);">Welcome back, <?= e($tenant['full_name']) ?>! 👋</h3>
    <p style="color:var(--gray-600);margin-top:6px;">
        <?= e($tenant['property_name'] ?? 'No property') ?> — Unit <?= e($tenant['unit_number'] ?? '—') ?>
    </p>
</div>

<div class="stats-grid">
    <div class="stat-card green">
        <div class="stat-label">Total Paid</div>
        <div class="stat-value"><?= money($paidAmt) ?></div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-label">Pending</div>
        <div class="stat-value"><?= money($pendingAmt) ?></div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Overdue</div>
        <div class="stat-value"><?= money($overdueAmt) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Monthly Rent</div>
        <div class="stat-value"><?= money($lease['monthly_rent'] ?? $tenant['unit_rent'] ?? 0) ?></div>
    </div>
</div>

<?php if ($notifications): ?>
<div class="card">
    <div class="card-header"><h3>Recent Notifications</h3></div>
    <ul class="notif-list">
        <?php foreach ($notifications as $n): ?>
            <li class="notif-item <?= $n['is_read']?'':'unread' ?> <?= e($n['type']) ?>">
                <div class="notif-icon">🔔</div>
                <div>
                    <div class="notif-title"><?= e($n['title']) ?></div>
                    <div class="notif-msg"><?= e($n['message']) ?></div>
                    <div class="notif-time"><?= date('M d, Y', strtotime($n['created_at'])) ?></div>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h3>Recent Rent Payments</h3><a href="payments.php" class="btn btn-sm btn-outline">View All</a></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Due Date</th><th>Amount</th><th>Status</th><th>Payment Date</th></tr></thead>
            <tbody>
            <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($p['due_date'])) ?></td>
                    <td><?= money($p['amount']) ?></td>
                    <td><?= status_badge($p['status']) ?></td>
                    <td><?= $p['payment_date'] ? date('M d, Y', strtotime($p['payment_date'])) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>