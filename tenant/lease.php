<?php
require_once __DIR__ . '/../includes/auth.php';
require_tenant();
$page_title = 'My Lease';
$uid = current_user()['tenant_id'];

$stmt = $pdo->prepare("SELECT l.*, u.unit_number, p.name AS property_name
                       FROM leases l
                       JOIN units u ON u.id = l.unit_id
                       JOIN properties p ON p.id = u.property_id
                       WHERE l.tenant_id = ? ORDER BY l.end_date DESC LIMIT 1");
$stmt->execute([$uid]);
$lease = $stmt->fetch();

include __DIR__ . '/../includes/header.php';
?>

<?php if (!$lease): ?>
    <div class="alert alert-info">No lease on record. Please contact your property manager.</div>
<?php else: ?>
<div class="card">
    <div class="card-header"><h3>Lease Information</h3><?= status_badge(lease_status($lease['end_date'])) ?></div>
    <div class="grid-2">
        <div><strong>Property:</strong><br><?= e($lease['property_name']) ?></div>
        <div><strong>Unit:</strong><br><?= e($lease['unit_number']) ?></div>
        <div><strong>Start Date:</strong><br><?= date('M d, Y', strtotime($lease['start_date'])) ?></div>
        <div><strong>End Date:</strong><br><?= date('M d, Y', strtotime($lease['end_date'])) ?></div>
        <div><strong>Monthly Rent:</strong><br><?= money($lease['monthly_rent']) ?></div>
        <div><strong>Deposit:</strong><br><?= money($lease['deposit']) ?></div>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>