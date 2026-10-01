<?php
require_once __DIR__ . '/../includes/auth.php';
require_tenant();
$page_title = 'My Documents';
$uid = current_user()['tenant_id'];

$docs = $pdo->prepare("SELECT d.* FROM documents d WHERE d.tenant_id = ? ORDER BY d.uploaded_at DESC");
$docs->execute([$uid]);
$docs = $docs->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h3>My Rental Documents</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Title</th><th>Type</th><th>Uploaded</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($docs as $d): ?>
                <tr>
                    <td><?= e($d['title']) ?></td>
                    <td><?= status_badge($d['doc_type']) ?></td>
                    <td><?= date('M d, Y', strtotime($d['uploaded_at'])) ?></td>
                    <td><a class="btn btn-sm btn-outline" href="<?= UPLOAD_URL . e($d['file_path']) ?>" target="_blank">Download</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($docs)): ?>
                <tr><td colspan="4" class="text-center">No documents assigned to you yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>