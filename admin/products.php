<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/products.php
 * Product listing with Search + Filter + Hide/Show + Delete
 */
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Products';
$current = 'products';

// ---- Toggle Hide/Show ----
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $conn->query("UPDATE products SET status = IF(status='show','hide','show') WHERE id = $id");
    redirect('products.php');
}

// ---- Delete product (and its images) ----
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $imgs = $conn->query("SELECT image_path FROM product_images WHERE product_id = $id");
    while ($img = $imgs->fetch_assoc()) {
        $path = __DIR__ . '/../' . $img['image_path'];
        if (file_exists($path)) @unlink($path);
    }
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    set_flash('success', 'Product डिलीट हो गया।');
    redirect('products.php');
}

// ---- Search / Filter ----
$search = clean($_GET['q'] ?? '');
$catFilter = (int) ($_GET['category'] ?? 0);

$sql = "SELECT p.*, c.name AS cat_name, s.name AS sub_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN subcategories s ON p.subcategory_id = s.id
        WHERE 1=1";
if ($search !== '') {
    $sql .= " AND (p.name LIKE '%$search%' OR p.brand LIKE '%$search%')";
}
if ($catFilter > 0) {
    $sql .= " AND p.category_id = $catFilter";
}
$sql .= " ORDER BY p.id DESC";
$products = $conn->query($sql);
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

include __DIR__ . '/includes/header.php';
?>

<div class="card" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;">
        <input type="text" name="q" class="form-control" placeholder="Search product/brand..." value="<?= htmlspecialchars($search) ?>">
        <select name="category" class="form-control">
            <option value="0">सभी Categories</option>
            <?php while ($c = $categories->fetch_assoc()): ?>
                <option value="<?= $c['id'] ?>" <?= $catFilter == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endwhile; ?>
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>
    <a href="product_form.php" class="btn btn-success">+ नया Product जोड़ें</a>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Image</th><th>नाम</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php while ($p = $products->fetch_assoc()):
                $img = $conn->query("SELECT image_path FROM product_images WHERE product_id = {$p['id']} LIMIT 1")->fetch_assoc();
            ?>
                <tr>
                    <td><img src="<?= $img ? '../' . htmlspecialchars($img['image_path']) : 'https://placehold.co/50x50' ?>" style="width:50px;height:50px;object-fit:cover;border-radius:6px;"></td>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= htmlspecialchars($p['cat_name']) ?><?= $p['sub_name'] ? ' / ' . htmlspecialchars($p['sub_name']) : '' ?></td>
                    <td><?= money($p['price']) ?></td>
                    <td><?= (int) $p['stock'] ?></td>
                    <td><span class="badge badge-<?= $p['status'] === 'show' ? 'approved' : 'rejected' ?>"><?= ucfirst($p['status']) ?></span></td>
                    <td style="white-space:nowrap;">
                        <a href="product_form.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                        <a href="products.php?toggle=<?= $p['id'] ?>" class="btn btn-sm btn-warning"><?= $p['status'] === 'show' ? 'Hide' : 'Show' ?></a>
                        <a href="products.php?delete=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete()">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
