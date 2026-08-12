<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/customer_bill.php
 * Single Customer का पूरा Combined Bill - उसके सारे Orders मिलाकर
 * एक ही Bill में, Print/PDF के लिए तैयार
 */
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$customerId = (int) ($_GET['customer_id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param('i', $customerId);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

if (!$customer) { die('Customer नहीं मिला।'); }

$orders = $conn->query("SELECT * FROM orders WHERE customer_id = $customerId ORDER BY order_time ASC");
$grandTotal = 0;
$ordersArr = [];
while ($o = $orders->fetch_assoc()) {
    $items = $conn->query("
        SELECT oi.*, p.name AS product_name, p.unit
        FROM order_items oi JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = {$o['id']}
    ");
    $orderTotal = 0;
    $itemsArr = [];
    while ($item = $items->fetch_assoc()) {
        $itemsArr[] = $item;
        $orderTotal += $item['quantity'] * $item['price'];
    }
    $o['items'] = $itemsArr;
    $o['order_total'] = $orderTotal;
    $grandTotal += $orderTotal;
    $ordersArr[] = $o;
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
<meta charset="UTF-8">
<title>Full Bill - <?= htmlspecialchars($customer['name']) ?></title>
<style>
    body { font-family: Arial, sans-serif; padding: 30px; color: #111; max-width: 750px; margin: 0 auto; }
    .header { border-bottom:2px solid #0d9488; padding-bottom:14px; margin-bottom:20px; }
    .header h1 { font-size:22px; color:#0d9488; margin-bottom:6px; }
    .header p { font-size:13px; color:#555; }
    .order-block { margin-bottom:22px; border:1px solid #ddd; border-radius:8px; padding:14px; }
    .order-block h3 { font-size:14px; margin-bottom:8px; color:#0d9488; }
    table { width:100%; border-collapse:collapse; margin-bottom:8px; }
    th, td { border:1px solid #ddd; padding:6px 8px; font-size:12.5px; text-align:left; }
    th { background:#f4f6f5; }
    .grand-total { background:#0d9488; color:#fff; padding:16px; border-radius:8px; text-align:right; font-size:18px; font-weight:bold; margin-top:20px; }
    .action-btn { margin-top:20px; padding:10px 20px; background:#0d9488; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:14px; }
    @media print { .action-btn { display:none; } }
</style>
</head>
<body>
    <div class="header">
        <h1>🧾 Full Customer Bill</h1>
        <p><strong><?= htmlspecialchars($customer['name']) ?></strong> (<?= htmlspecialchars($customer['client_id']) ?>) · <?= htmlspecialchars($customer['phone']) ?></p>
        <p><?= htmlspecialchars($customer['address']) ?></p>
        <p>कुल Orders: <?= count($ordersArr) ?> · Generated: <?= date('d M Y, h:i A') ?></p>
    </div>

    <?php foreach ($ordersArr as $o): ?>
    <div class="order-block">
        <h3><?= htmlspecialchars($o['order_no']) ?> — <?= ucfirst($o['status']) ?> — <?= date('d M Y', strtotime($o['order_time'])) ?></h3>
        <table>
            <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
            <tbody>
            <?php foreach ($o['items'] as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td><?= (int) $item['quantity'] ?> <?= htmlspecialchars($item['unit']) ?></td>
                    <td><?= money($item['price']) ?></td>
                    <td><?= money($item['quantity'] * $item['price']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p style="text-align:right;font-size:13px;"><strong>Order Total: <?= money($o['order_total']) ?></strong></p>
    </div>
    <?php endforeach; ?>

    <div class="grand-total">कुल मिलाकर Grand Total: <?= money($grandTotal) ?></div>

    <button class="action-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
</body>
</html>
