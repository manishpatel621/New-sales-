<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/product_form.php
 * Add / Edit Product with multiple image upload
 */
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Product Form';
$current = 'products';

$id = (int) ($_GET['id'] ?? 0);
$product = null;
$images = [];

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $imgResult = $conn->query("SELECT * FROM product_images WHERE product_id = $id");
    while ($row = $imgResult->fetch_assoc()) $images[] = $row;
}

// ---- Save ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'गलत अनुरोध।');
        redirect('products.php');
    }

    $name = clean($_POST['name']);
    $description = clean($_POST['description']);
    $category_id = (int) $_POST['category_id'];
    $subcategory_id = (int) ($_POST['subcategory_id'] ?: 0) ?: null;
    $brand = clean($_POST['brand']);
    $unit = clean($_POST['unit']);
    $size = clean($_POST['size']);
    $color = clean($_POST['color']);
    $price = (float) $_POST['price'];
    $stock = (int) $_POST['stock'];
    $status = $_POST['status'] === 'hide' ? 'hide' : 'show';

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE products SET category_id=?, subcategory_id=?, name=?, description=?, brand=?, unit=?, size=?, color=?, price=?, stock=?, status=? WHERE id=?");
        $stmt->bind_param('iissssssdisi', $category_id, $subcategory_id, $name, $description, $brand, $unit, $size, $color, $price, $stock, $status, $id);
        $stmt->execute();
        $productId = $id;
        set_flash('success', 'Product अपडेट हो गया।');
    } else {
        $stmt = $conn->prepare("INSERT INTO products (category_id, subcategory_id, name, description, brand, unit, size, color, price, stock, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('iissssssdis', $category_id, $subcategory_id, $name, $description, $brand, $unit, $size, $color, $price, $stock, $status);
        $stmt->execute();
        $productId = $stmt->insert_id;
        set_flash('success', 'Product जोड़ा गया।');
    }

    // ---- Handle multiple image upload ----
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $index => $name_) {
            if ($_FILES['images']['error'][$index] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['images']['name'][$index],
                    'type' => $_FILES['images']['type'][$index],
                    'tmp_name' => $_FILES['images']['tmp_name'][$index],
                    'error' => $_FILES['images']['error'][$index],
                    'size' => $_FILES['images']['size'][$index],
                ];
                $path = upload_image($file);
                if ($path) {
                    $stmt2 = $conn->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");
                    $stmt2->bind_param('is', $productId, $path);
                    $stmt2->execute();
                }
            }
        }
    }

    redirect('products.php');
}

// ---- Delete a single image (AJAX-less, simple GET) ----
if (isset($_GET['delete_image'])) {
    $imgId = (int) $_GET['delete_image'];
    $imgRow = $conn->query("SELECT * FROM product_images WHERE id = $imgId")->fetch_assoc();
    if ($imgRow) {
        $path = __DIR__ . '/../' . $imgRow['image_path'];
        if (file_exists($path)) @unlink($path);
        $conn->query("DELETE FROM product_images WHERE id = $imgId");
    }
    redirect('product_form.php?id=' . $id);
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name");

include __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h3 style="margin-bottom:16px;"><?= $product ? 'Product Edit करें' : 'नया Product जोड़ें' ?></h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="form-row">
            <div class="form-group">
                <label>Product नाम</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($product['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Brand</label>
                <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($product['brand'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" id="category_id" class="form-control" required>
                    <option value="">-- चुनें --</option>
                    <?php while ($c = $categories->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>" <?= (($product['category_id'] ?? 0) == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Sub Category</label>
                <select name="subcategory_id" id="subcategory_id" class="form-control">
                    <option value="">-- चुनें --</option>
                    <?php
                    $subs = $conn->query("SELECT * FROM subcategories ORDER BY name");
                    while ($s = $subs->fetch_assoc()):
                    ?>
                        <option value="<?= $s['id'] ?>" data-cat="<?= $s['category_id'] ?>" <?= (($product['subcategory_id'] ?? 0) == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Size</label>
                <input type="text" name="size" class="form-control" value="<?= htmlspecialchars($product['size'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Color</label>
                <input type="text" name="color" class="form-control" value="<?= htmlspecialchars($product['color'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Unit</label>
                <input type="text" name="unit" class="form-control" value="<?= htmlspecialchars($product['unit'] ?? 'pcs') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Price (₹)</label>
                <input type="number" step="0.01" name="price" class="form-control" required value="<?= htmlspecialchars($product['price'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Stock</label>
                <input type="number" name="stock" class="form-control" required value="<?= htmlspecialchars($product['stock'] ?? '0') ?>">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="show" <?= (($product['status'] ?? '') === 'show') ? 'selected' : '' ?>>Show</option>
                    <option value="hide" <?= (($product['status'] ?? '') === 'hide') ? 'selected' : '' ?>>Hide</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Images अपलोड करें (Multiple चुन सकते हैं)</label>
            <input type="file" name="images[]" multiple accept="image/*" class="form-control" onchange="previewImages(this, 'imgPreview')">
            <div id="imgPreview" style="margin-top:10px;"></div>
        </div>

        <?php if ($images): ?>
        <div class="form-group">
            <label>मौजूदा Images</label>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <?php foreach ($images as $img): ?>
                    <div style="position:relative;">
                        <img src="../<?= htmlspecialchars($img['image_path']) ?>" style="width:80px;height:80px;object-fit:cover;border-radius:6px;">
                        <a href="product_form.php?id=<?= $id ?>&delete_image=<?= $img['id'] ?>" onclick="return confirmDelete('Image डिलीट करें?')" style="position:absolute;top:-6px;right:-6px;background:#dc2626;color:#fff;border-radius:50%;width:20px;height:20px;text-align:center;font-size:12px;line-height:20px;">✕</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary">Save Product</button>
        <a href="products.php" class="btn btn-outline">Cancel</a>
    </form>
</div>

<script>
// Filter subcategory dropdown based on selected category
document.getElementById('category_id').addEventListener('change', function () {
    const catId = this.value;
    document.querySelectorAll('#subcategory_id option').forEach(function (opt) {
        if (!opt.dataset.cat) { opt.style.display = ''; return; }
        opt.style.display = (opt.dataset.cat === catId) ? '' : 'none';
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
