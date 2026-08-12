<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: customer/search_products.php
 * Returns matching products as JSON - powers the Live Search box
 * (Amazon/Flipkart style: results update as you type, no page reload)
 */
require_once __DIR__ . '/../includes/auth_customer.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$search = clean($_GET['q'] ?? '');
$catFilter = (int) ($_GET['category'] ?? 0);

$sql = "SELECT p.id, p.name, p.brand, p.unit, p.price, p.stock
        FROM products p WHERE p.status = 'show' AND p.stock > 0";
if ($search !== '') {
    // Partial match anywhere in name or brand - split into words so
    // "cotton shirt" also matches "Cotton Casual Shirt" etc.
    $words = preg_split('/\s+/', trim($search));
    $conditions = [];
    foreach ($words as $w) {
        if ($w === '') continue;
        $w = $conn->real_escape_string($w);
        $conditions[] = "(p.name LIKE '%$w%' OR p.brand LIKE '%$w%')";
    }
    if ($conditions) $sql .= " AND (" . implode(' AND ', $conditions) . ")";
}
if ($catFilter > 0) $sql .= " AND p.category_id = $catFilter";
$sql .= " ORDER BY p.id DESC LIMIT 60";

$result = $conn->query($sql);
$products = [];
while ($p = $result->fetch_assoc()) {
    $img = $conn->query("SELECT image_path FROM product_images WHERE product_id = {$p['id']} LIMIT 1")->fetch_assoc();
    $products[] = [
        'id' => $p['id'],
        'name' => $p['name'],
        'brand' => $p['brand'],
        'unit' => $p['unit'],
        'price' => (float) $p['price'],
        'price_formatted' => money($p['price']),
        'stock' => (int) $p['stock'],
        'image' => $img ? '../' . $img['image_path'] : null,
    ];
}

echo json_encode(['products' => $products]);
