<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$page_title = 'Dashboard';

// Summary metrics
$total_properties = $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
$total_tenants    = $pdo->query("SELECT COUNT(*) FROM tenants")->fetchColumn();
$total_units      = $pdo->query("SELECT COUNT(*) FROM units")->fetchColumn();
$occupied_units   = $pdo->query("SELECT COUNT(*) FROM units WHERE status='occupied'")->fetchColumn();
$available_units  = $pdo->query("SELECT COUNT(*) FROM units WHERE status='available'")->fetchColumn();

$rent_collected = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM rent_payments WHERE status='paid'")->fetchColumn();
$pending_rent   = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM rent_payments WHERE status IN ('unpaid','pending')")->fetchColumn();
$overdue_rent   = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM rent_payments WHERE status='overdue'")->fetchColumn();
$active_maint   = $pdo->query("SELECT COUNT(*) FROM maintenance_requests WHERE status NOT IN ('completed','cancelled')")->fetchColumn();

// Recent payments
$recent_payments = $pdo->query("
    SELECT rp.*, t.full_name
    FROM rent_payments rp
    JOIN tenants t ON t.id = rp.tenant_id
    ORDER BY rp.created_at DESC LIMIT 5
")->fetchAll();

// Upcoming rent
$upcoming = $pdo->query("
    SELECT rp.*, t.full_name
    FROM rent_payments rp
    JOIN tenants t ON t.id = rp.tenant_id
    WHERE rp.status IN ('unpaid','pending','overdue')
    ORDER BY rp.due_date ASC LIMIT 5
")->fetchAll();

// Recent maintenance
$recent_maint = $pdo->query("
    SELECT m.*, t.full_name
    FROM maintenance_requests m
    JOIN tenants t ON t.id = m.tenant_id
    ORDER BY m.created_at DESC LIMIT 5
")->fetchAll();

// Recent documents
$recent_docs = $pdo->query("
    SELECT d.*, t.full_name FROM documents d
    LEFT JOIN tenants t ON t.id = d.tenant_id
    ORDER BY d.uploaded_at DESC LIMIT 5
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card navy">
        <div class="stat-label">Total Properties</div>
        <div class="stat-value"><?= $total_properties ?></div>
        <div class="stat-sub"><?= $total_units ?> total units</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Tenants</div>
        <div class="stat-value"><?= $total_tenants ?></div>
        <div class="stat-sub">Registered tenants</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Occupied Units</div>
        <div class="stat-value"><?= $occupied_units ?></div>
        <div class="stat-sub">of <?= $total_units ?> units</div>
    </div>
    <div class="stat-card navy">
        <div class="stat-label">Available Units</div>
        <div class="stat-value"><?= $available_units ?></div>
        <div class="stat-sub">Ready to rent</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Rent Collected</div>
        <div class="stat-value"><?= money($rent_collected) ?></div>
        <div class="stat-sub">All time</div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-label">Pending Rent</div>
        <div class="stat-value"><?= money($pending_rent) ?></div>
        <div class="stat-sub">Awaiting payment</div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Overdue Rent</div>
        <div class="stat-value"><?= money($overdue_rent) ?></div>
        <div class="stat-sub">Past due date</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Active Maintenance</div>
        <div class="stat-value"><?= $active_maint ?></div>
        <div class="stat-sub">Open requests</div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><h3>Recent Payments</h3><a href="payments.php" class="btn btn-sm btn-outline">View All</a></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Tenant</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($recent_payments as $p): ?>
                    <tr>
                        <td><?= e($p['full_name']) ?></td>
                        <td><?= money($p['amount']) ?></td>
                        <td><?= status_badge($p['status']) ?></td>
                        <td><?= e($p['payment_date'] ? date('M d, Y', strtotime($p['payment_date'])) : date('M d, Y', strtotime($p['due_date']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Upcoming Rent</h3><a href="payments.php" class="btn btn-sm btn-outline">View All</a></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Tenant</th><th>Due Date</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($upcoming as $u): ?>
                    <tr>
                        <td><?= e($u['full_name']) ?></td>
                        <td><?= date('M d, Y', strtotime($u['due_date'])) ?></td>
                        <td><?= money($u['amount']) ?></td>
                        <td><?= status_badge($u['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><h3>Recent Maintenance</h3><a href="maintenance.php" class="btn btn-sm btn-outline">View All</a></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Tenant</th><th>Category</th><th>Priority</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($recent_maint as $m): ?>
                    <tr>
                        <td><?= e($m['full_name']) ?></td>
                        <td><?= e($m['category']) ?></td>
                        <td><?= status_badge($m['priority']) ?></td>
                        <td><?= status_badge($m['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Recent Documents</h3><a href="documents.php" class="btn btn-sm btn-outline">View All</a></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Title</th><th>Type</th><th>Tenant</th><th>Uploaded</th></tr></thead>
                <tbody>
                <?php if (empty($recent_docs)): ?>
                    <tr><td colspan="4" class="text-center">No documents yet</td></tr>
                <?php else: foreach ($recent_docs as $d): ?>
                    <tr>
                        <td><?= e($d['title']) ?></td>
                        <td><?= status_badge($d['doc_type']) ?></td>
                        <td><?= e($d['full_name'] ?? '—') ?></td>
                        <td><?= date('M d, Y', strtotime($d['uploaded_at'])) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>