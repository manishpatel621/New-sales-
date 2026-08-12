<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: customer/orders.php
 * Order history for the logged-in customer, with a visual status
 * timeline for each order (advanced tracking view)
 */
require_once __DIR__ . '/../includes/auth_customer.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'मेरे Orders';
$customerId = $_SESSION['customer_id'];

$orders = $conn->query("SELECT * FROM orders WHERE customer_id = $customerId ORDER BY id DESC");

// Steps for the visual timeline (cancelled orders show separately)
$steps = [
    'pending'   => ['icon' => '📝', 'label' => 'Order दिया'],
    'accepted'  => ['icon' => '✅', 'label' => 'स्वीकार'],
    'ready'     => ['icon' => '📦', 'label' => 'तैयार'],
    'delivered' => ['icon' => '🚚', 'label' => 'Delivered'],
];
$stepOrder = ['pending', 'accepted', 'ready', 'delivered'];

include __DIR__ . '/includes/header.php';
?>

<?php if ($orders->num_rows === 0): ?>
    <div class="card" style="text-align:center;padding:40px 20px;">
        <div style="font-size:40px;margin-bottom:10px;">🛍️</div>
        <p style="color:var(--text-light);margin-bottom:14px;">आपने अभी तक कोई Order नहीं दिया है।</p>
        <a href="dashboard.php" class="btn btn-primary">Shopping शुरू करें</a>
    </div>
<?php endif; ?>

<?php while ($o = $orders->fetch_assoc()):
    $items = $conn->query("
        SELECT oi.*, p.name AS product_name
        FROM order_items oi JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = {$o['id']}
    ");
    $total = 0;
    $currentIndex = array_search($o['status'], $stepOrder);
?>
<div class="card">
    <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div>
            <strong><?= htmlspecialchars($o['order_no']) ?></strong><br>
            <small style="color:var(--text-light);"><?= date('d M Y, h:i A', strtotime($o['order_time'])) ?></small>
        </div>
        <span class="badge badge-<?= $o['status'] ?>" style="font-size:13px;height:fit-content;"><?= ucfirst($o['status']) ?></span>
    </div>

    <?php if ($o['status'] === 'cancelled'): ?>
        <div style="text-align:center;padding:14px;background:var(--danger-bg);border-radius:var(--radius-sm);margin:14px 0;color:#991b1b;font-weight:600;font-size:13px;">
            ❌ यह Order Cancel कर दिया गया
        </div>
    <?php else: ?>
        <div class="order-timeline">
            <?php foreach ($stepOrder as $i => $stepKey):
                $stepClass = $i < $currentIndex ? 'done' : ($i === $currentIndex ? 'current' : '');
            ?>
                <div class="timeline-step <?= $stepClass ?>">
                    <div class="timeline-dot"><?= $steps[$stepKey]['icon'] ?></div>
                    <div class="timeline-label"><?= $steps[$stepKey]['label'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="table-wrap" style="margin-top:14px;">
        <table>
            <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
            <tbody>
            <?php while ($item = $items->fetch_assoc()):
                $subtotal = $item['quantity'] * $item['price'];
                $total += $subtotal;
            ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td><?= (int) $item['quantity'] ?></td>
                    <td><?= money($item['price']) ?></td>
                    <td><?= money($subtotal) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <p style="text-align:right;margin-top:8px;"><strong>Total: <?= money($total) ?></strong></p>
    <?php if ($o['customer_note']): ?><p style="font-size:13px;"><strong>आपका Note:</strong> <?= htmlspecialchars($o['customer_note']) ?></p><?php endif; ?>
    <?php if ($o['admin_note']): ?><p style="font-size:13px;"><strong>Admin Note:</strong> <?= htmlspecialchars($o['admin_note']) ?></p><?php endif; ?>
</div>
<?php endwhile; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
