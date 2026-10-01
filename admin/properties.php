<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$page_title = 'Properties & Units';

// Handle new property
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_property'])) {
    if (verify_csrf($_POST['csrf'] ?? '')) {
        $stmt = $pdo->prepare("INSERT INTO properties (name,address,city,description) VALUES (?,?,?,?)");
        $stmt->execute([
            trim($_POST['name']), trim($_POST['address']),
            trim($_POST['city']), trim($_POST['description'])
        ]);
        set_flash('success', 'Property added successfully.');
    }
    header('Location: properties.php'); exit;
}

// Handle new unit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_unit'])) {
    if (verify_csrf($_POST['csrf'] ?? '')) {
        $stmt = $pdo->prepare("INSERT INTO units (property_id,unit_number,bedrooms,bathrooms,monthly_rent,status) VALUES (?,?,?,?,?,?)");
        $stmt->execute([
            (int)$_POST['property_id'], trim($_POST['unit_number']),
            (int)$_POST['bedrooms'], (int)$_POST['bathrooms'],
            (float)$_POST['monthly_rent'], $_POST['status']
        ]);
        set_flash('success', 'Unit added successfully.');
    }
    header('Location: properties.php'); exit;
}

$properties = $pdo->query("SELECT * FROM properties ORDER BY name")->fetchAll();
$units = $pdo->query("
    SELECT u.*, p.name AS property_name
    FROM units u JOIN properties p ON p.id = u.property_id
    ORDER BY p.name, u.unit_number
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3>Properties</h3>
        <button class="btn btn-primary btn-sm" data-modal-open="modalAddProperty">+ Add Property</button>
    </div>
    <div class="grid-3">
        <?php foreach ($properties as $p): ?>
            <div class="card" style="margin:0;">
                <h4 style="color:var(--navy);"><?= e($p['name']) ?></h4>
                <p style="font-size:.85rem;color:var(--gray-600);">
                    <?= e($p['address']) ?><?= $p['city'] ? ', '.e($p['city']) : '' ?>
                </p>
                <p style="font-size:.85rem;margin-top:6px;"><?= e($p['description']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Units</h3>
        <button class="btn btn-primary btn-sm" data-modal-open="modalAddUnit">+ Add Unit</button>
    </div>
    <div class="toolbar">
        <input type="text" placeholder="Search units..." data-table-search="#unitsTable">
    </div>
    <div class="table-wrap">
        <table id="unitsTable">
            <thead>
                <tr><th>Property</th><th>Unit #</th><th>Beds</th><th>Baths</th><th>Rent</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ($units as $u): ?>
                <tr>
                    <td><?= e($u['property_name']) ?></td>
                    <td><?= e($u['unit_number']) ?></td>
                    <td><?= (int)$u['bedrooms'] ?></td>
                    <td><?= (int)$u['bathrooms'] ?></td>
                    <td><?= money($u['monthly_rent']) ?></td>
                    <td><?= status_badge($u['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Property Modal -->
<div class="modal-backdrop" id="modalAddProperty">
    <div class="modal">
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="add_property" value="1">
            <div class="modal-header"><h3>Add Property</h3><button type="button" class="modal-close" data-modal-close>×</button></div>
            <div class="modal-body">
                <div class="form-group mb-2"><label>Name</label><input name="name" required></div>
                <div class="form-group mb-2"><label>Address</label><input name="address" required></div>
                <div class="form-group mb-2"><label>City</label><input name="city"></div>
                <div class="form-group"><label>Description</label><textarea name="description" rows="3"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Unit Modal -->
<div class="modal-backdrop" id="modalAddUnit">
    <div class="modal">
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="add_unit" value="1">
            <div class="modal-header"><h3>Add Unit</h3><button type="button" class="modal-close" data-modal-close>×</button></div>
            <div class="modal-body">
                <div class="form-group mb-2">
                    <label>Property</label>
                    <select name="property_id" required>
                        <?php foreach ($properties as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-2"><label>Unit Number</label><input name="unit_number" required></div>
                <div class="form-group mb-2"><label>Bedrooms</label><input type="number" name="bedrooms" value="1" min="1"></div>
                <div class="form-group mb-2"><label>Bathrooms</label><input type="number" name="bathrooms" value="1" min="1"></div>
                <div class="form-group mb-2"><label>Monthly Rent</label><input type="number" step="0.01" name="monthly_rent" required></div>
                <div class="form-group"><label>Status</label>
                    <select name="status">
                        <option value="available">Available</option>
                        <option value="occupied">Occupied</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
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