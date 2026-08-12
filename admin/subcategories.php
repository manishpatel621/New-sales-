<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/subcategories.php
 * Add / Edit / Delete Sub Categories (linked to a Category)
 */
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Sub Categories';
$current = 'subcategories';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_sub'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'गलत अनुरोध।');
    } else {
        $name = clean($_POST['name']);
        $category_id = (int) $_POST['category_id'];
        $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
        $id = (int) ($_POST['id'] ?? 0);

        if ($name === '' || $category_id <= 0) {
            set_flash('danger', 'सभी फ़ील्ड भरें।');
        } elseif ($id > 0) {
            $stmt = $conn->prepare("UPDATE subcategories SET name=?, category_id=?, status=? WHERE id=?");
            $stmt->bind_param('sisi', $name, $category_id, $status, $id);
            $stmt->execute();
            set_flash('success', 'Sub Category अपडेट हो गई।');
        } else {
            $stmt = $conn->prepare("INSERT INTO subcategories (name, category_id, status) VALUES (?,?,?)");
            $stmt->bind_param('sis', $name, $category_id, $status);
            $stmt->execute();
            set_flash('success', 'Sub Category जोड़ी गई।');
        }
    }
    redirect('subcategories.php');
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM subcategories WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    set_flash('success', 'Sub Category डिलीट हो गई।');
    redirect('subcategories.php');
}

$editRow = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM subcategories WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editRow = $stmt->get_result()->fetch_assoc();
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name");
$subcategories = $conn->query("
    SELECT s.*, c.name AS category_name FROM subcategories s
    JOIN categories c ON s.category_id = c.id
    ORDER BY s.id DESC
");

include __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h3 style="margin-bottom:16px;"><?= $editRow ? 'Sub Category Edit करें' : 'नई Sub Category जोड़ें' ?></h3>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= $editRow['id'] ?? 0 ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Category चुनें</label>
                <select name="category_id" class="form-control" required>
                    <option value="">-- चुनें --</option>
                    <?php $categories->data_seek(0); while ($c = $categories->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>" <?= (($editRow['category_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Sub Category नाम</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editRow['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="active" <?= (($editRow['status'] ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= (($editRow['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>
        <button type="submit" name="save_sub" class="btn btn-primary">Save</button>
        <?php if ($editRow): ?><a href="subcategories.php" class="btn btn-outline">Cancel</a><?php endif; ?>
    </form>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">सभी Sub Categories</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>नाम</th><th>Category</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php $i = 1; while ($row = $subcategories->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['category_name']) ?></td>
                    <td><span class="badge badge-<?= $row['status'] === 'active' ? 'approved' : 'rejected' ?>"><?= ucfirst($row['status']) ?></span></td>
                    <td>
                        <a href="subcategories.php?edit=<?= $row['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                        <a href="subcategories.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete()">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
