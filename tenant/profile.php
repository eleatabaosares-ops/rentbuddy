<?php
require_once __DIR__ . '/../includes/auth.php';
require_tenant();
$page_title = 'My Profile';
$uid = current_user()['tenant_id'];

$stmt = $pdo->prepare("SELECT t.*, u.unit_number, p.name AS property_name
                       FROM tenants t
                       LEFT JOIN units u ON u.id = t.unit_id
                       LEFT JOIN properties p ON p.id = u.property_id
                       WHERE t.id = ?");
$stmt->execute([$uid]);
$t = $stmt->fetch();

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h3>Personal Information</h3></div>
    <div class="grid-2">
        <div><strong>Full Name:</strong><br><?= e($t['full_name']) ?></div>
        <div><strong>Tenant ID:</strong><br>#<?= $t['id'] ?></div>
        <div><strong>Contact Number:</strong><br><?= e($t['contact_number']) ?></div>
        <div><strong>Email:</strong><br><?= e($t['email']) ?></div>
        <div><strong>Address:</strong><br><?= e($t['address']) ?></div>
        <div><strong>Unit:</strong><br><?= e($t['unit_number'] ?? '—') ?></div>
        <div><strong>Property:</strong><br><?= e($t['property_name'] ?? '—') ?></div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>