<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$page_title = 'Documents';

// Upload document
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_doc'])) {
    if (verify_csrf($_POST['csrf'] ?? '') && !empty($_FILES['file']['name'])) {
        $allowed = ['pdf','doc','docx','jpg','jpeg','png','txt'];
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed) && $_FILES['file']['size'] <= 5*1024*1024) {
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0775, true);
            $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/','_', basename($_FILES['file']['name']));
            $dest = UPLOAD_DIR . $safeName;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                $stmt = $pdo->prepare("INSERT INTO documents (tenant_id,property_id,doc_type,title,file_path,file_size,uploaded_by) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([
                    $_POST['tenant_id'] ?: null,
                    $_POST['property_id'] ?: null,
                    $_POST['doc_type'],
                    trim($_POST['title']),
                    $safeName,
                    $_FILES['file']['size'],
                    current_user()['username']
                ]);
                set_flash('success', 'Document uploaded.');
            } else {
                set_flash('danger', 'Failed to save file.');
            }
        } else {
            set_flash('danger', 'Invalid file type or file too large (max 5MB).');
        }
    }
    header('Location: documents.php'); exit;
}

// Delete
if (isset($_GET['delete'])) {
    $d = $pdo->prepare("SELECT file_path FROM documents WHERE id=?");
    $d->execute([(int)$_GET['delete']]);
    $doc = $d->fetch();
    if ($doc) {
        @unlink(UPLOAD_DIR . $doc['file_path']);
        $pdo->prepare("DELETE FROM documents WHERE id=?")->execute([(int)$_GET['delete']]);
        set_flash('success', 'Document deleted.');
    }
    header('Location: documents.php'); exit;
}

$docs = $pdo->query("
    SELECT d.*, t.full_name, p.name AS property_name
    FROM documents d
    LEFT JOIN tenants t ON t.id = d.tenant_id
    LEFT JOIN properties p ON p.id = d.property_id
    ORDER BY d.uploaded_at DESC
")->fetchAll();
$tenants = $pdo->query("SELECT id,full_name FROM tenants ORDER BY full_name")->fetchAll();
$properties = $pdo->query("SELECT id,name FROM properties ORDER BY name")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3>Document Library</h3>
        <button class="btn btn-primary btn-sm" data-modal-open="modalUpload">+ Upload Document</button>
    </div>
    <div class="toolbar">
        <input type="text" placeholder="Search documents..." data-table-search="#docsTable">
    </div>
    <div class="table-wrap">
        <table id="docsTable">
            <thead><tr><th>Title</th><th>Type</th><th>Tenant</th><th>Property</th><th>Uploaded</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($docs as $d): ?>
                <tr>
                    <td><?= e($d['title']) ?></td>
                    <td><?= status_badge($d['doc_type']) ?></td>
                    <td><?= e($d['full_name'] ?? '—') ?></td>
                    <td><?= e($d['property_name'] ?? '—') ?></td>
                    <td><?= date('M d, Y', strtotime($d['uploaded_at'])) ?></td>
                    <td>
                        <a href="<?= UPLOAD_URL . e($d['file_path']) ?>" class="btn btn-sm btn-outline" target="_blank">Download</a>
                        <a href="?delete=<?= $d['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Delete this document?">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-backdrop" id="modalUpload">
    <div class="modal">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="upload_doc" value="1">
            <div class="modal-header"><h3>Upload Document</h3><button type="button" class="modal-close" data-modal-close>×</button></div>
            <div class="modal-body">
                <div class="form-group mb-2"><label>Title</label><input name="title" required></div>
                <div class="form-group mb-2">
                    <label>Document Type</label>
                    <select name="doc_type">
                        <option value="contract">Contract</option>
                        <option value="lease">Lease Agreement</option>
                        <option value="receipt">Receipt</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group mb-2">
                    <label>Associate Tenant (optional)</label>
                    <select name="tenant_id">
                        <option value="">— None —</option>
                        <?php foreach ($tenants as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= e($t['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-2">
                    <label>Associate Property (optional)</label>
                    <select name="property_id">
                        <option value="">— None —</option>
                        <?php foreach ($properties as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>File (PDF, DOC, JPG, PNG — max 5MB)</label><input type="file" name="file" required></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button class="btn btn-primary">Upload</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>