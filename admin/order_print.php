<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/order_print.php
 * Clean printable view of a single order (use browser Print/Save as PDF)
 */
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare("
    SELECT o.*, c.name AS cust_name, c.client_id, c.phone, c.address, c.customer_type
    FROM orders o JOIN customers c ON o.customer_id = c.id
    WHERE o.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) { die('Order नहीं मिला।'); }

$items = $conn->query("
    SELECT oi.*, p.name AS product_name, p.unit, p.category_id
    FROM order_items oi JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = $id
");
$itemsArr = [];
$total = 0;
$firstCategoryId = null;
$allCategoryIds = [];
while ($item = $items->fetch_assoc()) {
    $itemsArr[] = $item;
    $total += $item['quantity'] * $item['price'];
    if ($firstCategoryId === null) $firstCategoryId = $item['category_id'];
    if ($item['category_id']) $allCategoryIds[$item['category_id']] = true;
}

// Build the bill text for WhatsApp/Telegram
$billLines = [];
$billLines[] = "🧾 *Order Receipt*";
$billLines[] = "Order No: " . $order['order_no'];
$billLines[] = "Customer: " . $order['cust_name'] . " (" . $order['client_id'] . ")";
$billLines[] = "Status: " . ucfirst($order['status']);
$billLines[] = "";
foreach ($itemsArr as $item) {
    $sub = $item['quantity'] * $item['price'];
    $billLines[] = "- " . $item['product_name'] . " x" . $item['quantity'] . " = " . money($sub);
}
$billLines[] = "";
$billLines[] = "Total: " . money($total);
$billText = implode("\n", $billLines);

// Build a WhatsApp link for EACH distinct Section in this order,
// with that Section's name shown, so admin can tap through them
// one by one (true one-click-for-all isn't possible on free WhatsApp).
$waLinks = [];
$seenWa = [];
$sectionCatList = empty($allCategoryIds) ? [null] : array_keys($allCategoryIds);
foreach ($sectionCatList as $catId) {
    $num = get_section_whatsapp($catId);
    if (!$num) continue;
    $numClean = preg_replace('/[^0-9]/', '', $num);
    if (isset($seenWa[$numClean])) continue;
    $seenWa[$numClean] = true;
    $sectionLabel = 'Main';
    if ($catId) {
        $catRow = $conn->query("SELECT name FROM categories WHERE id = " . (int)$catId)->fetch_assoc();
        if ($catRow) $sectionLabel = $catRow['name'];
    }
    $waLinks[] = [
        'label' => $sectionLabel,
        'link' => 'https://wa.me/' . $numClean . '?text=' . urlencode($billText),
    ];
}

// Count how many distinct Section Telegram chats this order will reach
$telegramChatCount = 0;
$catList = empty($allCategoryIds) ? [null] : array_keys($allCategoryIds);
$seenChats = [];
foreach ($catList as $catId) {
    $chat = get_section_telegram_chat($catId);
    if ($chat && !isset($seenChats[$chat])) { $seenChats[$chat] = true; $telegramChatCount++; }
}
$telegramConfigured = get_setting('telegram_bot_token', '') && $telegramChatCount > 0;
?>
<!DOCTYPE html>
<html lang="hi">
<head>
<meta charset="UTF-8">
<title>Order <?= htmlspecialchars($order['order_no']) ?> - Print</title>
<style>
    body { font-family: Arial, sans-serif; padding: 30px; color: #111; max-width: 700px; margin: 0 auto; }
    .header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #0d9488; padding-bottom:14px; margin-bottom:20px; }
    .header h1 { font-size:22px; color:#0d9488; }
    .header p { font-size:13px; color:#555; }
    .meta { display:flex; justify-content:space-between; margin-bottom:20px; font-size:13.5px; }
    .meta div { line-height:1.6; }
    table { width:100%; border-collapse:collapse; margin-bottom:16px; }
    th, td { border:1px solid #ddd; padding:8px 10px; font-size:13px; text-align:left; }
    th { background:#f4f6f5; }
    .total-row td { font-weight:bold; font-size:14.5px; }
    .status { display:inline-block; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:bold; background:#fef3c7; color:#92400e; }
    .action-btn { margin-top:14px; margin-right:8px; padding:10px 18px; border:none; border-radius:6px; cursor:pointer; font-size:14px; text-decoration:none; display:inline-block; }
    .print-btn { background:#0d9488; color:#fff; }
    .wa-btn { background:#25D366; color:#fff; }
    .tg-btn { background:#0088cc; color:#fff; }
    @media print { .action-btn, .no-print { display:none; } }
</style>
</head>
<body>
    <div class="header">
        <div>
            <h1>📦 Order Receipt</h1>
            <p>Order No: <strong><?= htmlspecialchars($order['order_no']) ?></strong></p>
        </div>
        <div style="text-align:right;">
            <span class="status"><?= ucfirst($order['status']) ?></span>
            <p style="margin-top:6px;"><?= date('d M Y, h:i A', strtotime($order['order_time'])) ?></p>
        </div>
    </div>

    <div class="meta">
        <div>
            <strong>Customer:</strong><br>
            <?= htmlspecialchars($order['cust_name']) ?> (<?= htmlspecialchars($order['client_id']) ?>)<br>
            <?= htmlspecialchars($order['phone']) ?><br>
            <?= htmlspecialchars($order['address']) ?>
        </div>
        <div style="text-align:right;">
            <strong>Type:</strong> <?= strtoupper($order['customer_type']) ?>
        </div>
    </div>

    <table>
        <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
        <tbody>
        <?php foreach ($itemsArr as $item):
            $subtotal = $item['quantity'] * $item['price'];
        ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td><?= (int) $item['quantity'] ?> <?= htmlspecialchars($item['unit']) ?></td>
                <td><?= money($item['price']) ?></td>
                <td><?= money($subtotal) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr class="total-row"><td colspan="3" style="text-align:right;">Total</td><td><?= money($total) ?></td></tr>
        </tbody>
    </table>

    <?php if ($order['customer_note']): ?><p><strong>Customer Note:</strong> <?= htmlspecialchars($order['customer_note']) ?></p><?php endif; ?>
    <?php if ($order['admin_note']): ?><p><strong>Admin Note:</strong> <?= htmlspecialchars($order['admin_note']) ?></p><?php endif; ?>

    <div class="no-print">
        <button class="action-btn print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
        <?php foreach ($waLinks as $wa): ?>
            <a href="<?= htmlspecialchars($wa['link']) ?>" target="_blank" class="action-btn wa-btn">💬 WhatsApp - <?= htmlspecialchars($wa['label']) ?></a>
        <?php endforeach; ?>
        <?php if ($telegramConfigured): ?>
            <a href="order_send_telegram.php?id=<?= $id ?>" class="action-btn tg-btn">✈️ Telegram पर Auto-Send करें (<?= $telegramChatCount ?> Section<?= $telegramChatCount > 1 ? 's' : '' ?>)</a>
        <?php endif; ?>
    </div>
</body>
</html>
