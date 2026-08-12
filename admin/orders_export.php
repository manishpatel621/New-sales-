<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/orders_export.php
 * Exports the (filtered) orders list as a downloadable CSV file.
 * Supports filtering by Section/Category (category_id) so each
 * Section's bookings can be downloaded separately.
 */
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$statusFilter = clean($_GET['status'] ?? '');
$search = clean($_GET['q'] ?? '');
$categoryId = (int) ($_GET['category_id'] ?? 0);

$sql = "SELECT DISTINCT o.order_no, o.status, o.order_time, c.name AS cust_name, c.client_id, c.phone, c.customer_type,
        (SELECT COALESCE(SUM(oi.quantity*oi.price),0) FROM order_items oi WHERE oi.order_id = o.id) AS total
        FROM orders o
        JOIN customers c ON o.customer_id = c.id";
if ($categoryId > 0) {
    $sql .= " JOIN order_items oi2 ON oi2.order_id = o.id JOIN products p2 ON oi2.product_id = p2.id AND p2.category_id = $categoryId";
}
$sql .= " WHERE 1=1";
if ($statusFilter !== '') $sql .= " AND o.status = '$statusFilter'";
if ($search !== '') $sql .= " AND (o.order_no LIKE '%$search%' OR c.name LIKE '%$search%' OR c.client_id LIKE '%$search%')";
$sql .= " ORDER BY o.id DESC";
$result = $conn->query($sql);

$sectionName = 'all';
if ($categoryId > 0) {
    $catRow = $conn->query("SELECT name FROM categories WHERE id = $categoryId")->fetch_assoc();
    if ($catRow) $sectionName = preg_replace('/[^a-zA-Z0-9]+/', '_', $catRow['name']);
}

$filename = 'orders_' . $sectionName . '_' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputs($out, "\xEF\xBB\xBF");
fputcsv($out, ['Order No', 'Customer', 'Client ID', 'Phone', 'Type', 'Status', 'Total', 'Order Time']);

while ($row = $result->fetch_assoc()) {
    fputcsv($out, [
        $row['order_no'],
        $row['cust_name'],
        $row['client_id'],
        $row['phone'],
        strtoupper($row['customer_type']),
        ucfirst($row['status']),
        number_format($row['total'], 2),
        date('d-m-Y h:i A', strtotime($row['order_time'])),
    ]);
}
fclose($out);
exit();
