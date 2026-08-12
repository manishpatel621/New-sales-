<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/orders_print_all.php
 * Printable view of the full (filtered) orders list, one page
 */
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$statusFilter = clean($_GET['status'] ?? '');
$search = clean($_GET['q'] ?? '');

$sql = "SELECT o.*, c.name AS cust_name, c.client_id, c.phone
        FROM orders o JOIN customers c ON o.customer_id = c.id WHERE 1=1";
if ($statusFilter !== '') $sql .= " AND o.status = '$statusFilter'";
if ($search !== '') $sql .= " AND (o.order_no LIKE '%$search%' OR c.name LIKE '%$search%' OR c.client_id LIKE '%$search%')";
$sql .= " ORDER BY o.id DESC";
$orders = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="hi">
<head>
<meta charset="UTF-8">
<title>All Orders - Print</title>
<style>
    body { font-family: Arial, sans-serif; padding: 24px; color:#111; }
    h1 { color:#0d9488; font-size:20px; margin-bottom:4px; }
    p.sub { color:#555; font-size:13px; margin-bottom:16px; }
    table { width:100%; border-collapse:collapse; }
    th, td { border:1px solid #ddd; padding:8px 10px; font-size:12.5px; text-align:left; }
    th { background:#f4f6f5; }
    .print-btn { margin:16px 0; padding:10px 20px; background:#0d9488; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:14px; }
    @media print { .print-btn { display:none; } }
</style>
</head>
<body>
    <h1>📦 सभी Orders</h1>
    <p class="sub">Generated on <?= date('d M Y, h:i A') ?><?= $statusFilter ? ' — Status: ' . ucfirst($statusFilter) : '' ?></p>
    <button class="print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
    <table>
        <thead><tr><th>#</th><th>Order No</th><th>Customer</th><th>Phone</th><th>Status</th><th>Total</th><th>Date</th></tr></thead>
        <tbody>
        <?php $i = 1; while ($o = $orders->fetch_assoc()):
            $totalRow = $conn->query("SELECT COALESCE(SUM(quantity*price),0) t FROM order_items WHERE order_id = {$o['id']}")->fetch_assoc();
        ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($o['order_no']) ?></td>
                <td><?= htmlspecialchars($o['cust_name']) ?> (<?= htmlspecialchars($o['client_id']) ?>)</td>
                <td><?= htmlspecialchars($o['phone']) ?></td>
                <td><?= ucfirst($o['status']) ?></td>
                <td><?= money($totalRow['t']) ?></td>
                <td><?= date('d M Y, h:i A', strtotime($o['order_time'])) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
