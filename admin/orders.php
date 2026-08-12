<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/orders.php
 * Order Management: view all orders, change status, add admin notes
 */
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Orders';
$current = 'orders';

// ---- Update order status / admin note ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    if (csrf_verify($_POST['csrf_token'] ?? '')) {
        $id = (int) $_POST['order_id'];
        $status = clean($_POST['status']);
        $admin_note = clean($_POST['admin_note']);
        $allowed = ['pending', 'accepted', 'ready', 'delivered', 'cancelled'];
        if (in_array($status, $allowed)) {
            $stmt = $conn->prepare("UPDATE orders SET status=?, admin_note=? WHERE id=?");
            $stmt->bind_param('ssi', $status, $admin_note, $id);
            $stmt->execute();
            set_flash('success', 'Order अपडेट हो गया।');
        }
    }
    redirect('orders.php');
}

$statusFilter = clean($_GET['status'] ?? '');
$search = clean($_GET['q'] ?? '');

$sql = "SELECT o.*, c.name AS cust_name, c.client_id, c.customer_type
        FROM orders o JOIN customers c ON o.customer_id = c.id WHERE 1=1";
if ($statusFilter !== '') $sql .= " AND o.status = '$statusFilter'";
if ($search !== '') $sql .= " AND (o.order_no LIKE '%$search%' OR c.name LIKE '%$search%' OR c.client_id LIKE '%$search%')";
$sql .= " ORDER BY o.id DESC";
$orders = $conn->query($sql);

include __DIR__ . '/includes/header.php';
$qs = http_build_query(['status' => $statusFilter, 'q' => $search]);
?>

<div class="card" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;">
        <input type="text" name="q" class="form-control" placeholder="Order No / Customer खोजें..." value="<?= htmlspecialchars($search) ?>">
        <select name="status" class="form-control">
            <option value="">सभी Status</option>
            <?php foreach (['pending','accepted','ready','delivered','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="order_add.php" class="btn btn-success">+ नया Order बनाएं</a>
        <a href="orders_export.php?<?= $qs ?>" class="btn btn-info">⬇️ Export CSV</a>
        <a href="orders_print_all.php?<?= $qs ?>" target="_blank" class="btn btn-outline">🖨️ Print All</a>
    </div>
</div>

<?php while ($o = $orders->fetch_assoc()):
    $items = $conn->query("
        SELECT oi.*, p.name AS product_name
        FROM order_items oi JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = {$o['id']}
    ");
    $total = 0;
?>
<div class="card">
    <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div>
            <strong><?= htmlspecialchars($o['order_no']) ?></strong>
            &nbsp;<span class="badge badge-<?= $o['customer_type'] ?>"><?= strtoupper($o['customer_type']) ?></span><br>
            <?= htmlspecialchars($o['cust_name']) ?> (<?= htmlspecialchars($o['client_id']) ?>)<br>
            <small><?= date('d M Y, h:i A', strtotime($o['order_time'])) ?></small>
        </div>
        <div><span class="badge badge-<?= $o['status'] ?>" style="font-size:14px;"><?= ucfirst($o['status']) ?></span>
            &nbsp;<a href="order_print.php?id=<?= $o['id'] ?>" target="_blank" class="btn btn-sm btn-outline">🖨️ Print</a>
        </div>
    </div>

    <div class="table-wrap" style="margin-top:12px;">
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

    <?php if ($o['customer_note']): ?>
        <p style="margin-top:8px;"><strong>Customer Note:</strong> <?= htmlspecialchars($o['customer_note']) ?></p>
    <?php endif; ?>

    <form method="POST" style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
        <div class="form-group" style="flex:1;min-width:150px;">
            <label>Status बदलें</label>
            <select name="status" class="form-control">
                <?php foreach (['pending','accepted','ready','delivered','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="flex:2;min-width:200px;">
            <label>Admin Note</label>
            <input type="text" name="admin_note" class="form-control" value="<?= htmlspecialchars($o['admin_note'] ?? '') ?>">
        </div>
        <button type="submit" name="update_order" class="btn btn-primary">Update</button>
    </form>
</div>
<?php endwhile; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
