<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/order_send_telegram.php
 * Automatically sends an Order's bill via Telegram Bot API (free,
 * truly one-click - no manual "send" needed unlike WhatsApp).
 *
 * Setup required (one-time, in Admin > Settings):
 * 1. Open Telegram, search for "BotFather", send /newbot, follow steps,
 *    copy the Bot Token it gives you.
 * 2. Message your new bot once (search its username, send "hi").
 * 3. Open https://api.telegram.org/bot<YOUR_TOKEN>/getUpdates in browser,
 *    find "chat":{"id": ...} - copy that number, it's your Chat ID.
 * 4. Paste both into Admin > Settings.
 */
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare("
    SELECT o.*, c.name AS cust_name, c.client_id
    FROM orders o JOIN customers c ON o.customer_id = c.id
    WHERE o.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    set_flash('danger', 'Order नहीं मिला।');
    redirect('orders.php');
}

$items = $conn->query("
    SELECT oi.*, p.name AS product_name, p.unit, p.category_id
    FROM order_items oi JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = $id
");
$total = 0;
$categoryIds = [];
$lines = [];
$lines[] = "🧾 <b>Order Receipt</b>";
$lines[] = "Order No: " . $order['order_no'];
$lines[] = "Customer: " . $order['cust_name'] . " (" . $order['client_id'] . ")";
$lines[] = "Status: " . ucfirst($order['status']);
$lines[] = "";
while ($item = $items->fetch_assoc()) {
    if ($item['category_id']) $categoryIds[$item['category_id']] = true;
    $sub = $item['quantity'] * $item['price'];
    $total += $sub;
    $lines[] = "- " . $item['product_name'] . " x" . $item['quantity'] . " = " . money($sub);
}
$lines[] = "";
$lines[] = "<b>Total: " . money($total) . "</b>";
$billText = implode("\n", $lines);

// Collect every distinct Telegram Chat ID involved in this order's
// sections (falls back to the main Chat ID for sections without one)
// so ONE click notifies every relevant Section chat at once.
$chatIds = [];
if (empty($categoryIds)) {
    $mainChat = get_setting('telegram_chat_id', '');
    if ($mainChat) $chatIds[$mainChat] = true;
} else {
    foreach (array_keys($categoryIds) as $catId) {
        $chat = get_section_telegram_chat($catId);
        if ($chat) $chatIds[$chat] = true;
    }
}

$sentCount = 0;
$failCount = 0;
foreach (array_keys($chatIds) as $chatId) {
    if (send_telegram_message($billText, $chatId)) {
        $sentCount++;
    } else {
        $failCount++;
    }
}

if ($sentCount === 0 && $failCount === 0) {
    set_flash('danger', 'कोई Telegram Chat ID सेट नहीं मिला। Settings या Section Edit में Chat ID डालें।');
} elseif ($sentCount > 0 && $failCount === 0) {
    set_flash('success', "Bill $sentCount Section(s) के Telegram Chat पर भेज दिया गया! ✅");
} elseif ($sentCount > 0 && $failCount > 0) {
    set_flash('success', "$sentCount Chat(s) पर भेजा गया, $failCount में समस्या आई।");
} else {
    set_flash('danger', 'Telegram भेजने में समस्या आई। Settings में Bot Token चेक करें।');
}
redirect('orders.php');
