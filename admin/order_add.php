<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/order_add.php
 * Admin manually creates a new Order for a customer (phone orders,
 * walk-in orders, or orders admin takes on customer's behalf)
 */
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'नया Order बनाएं';
$current = 'order_add';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'गलत अनुरोध।');
        redirect('order_add.php');
    }

    $customerId = (int) $_POST['customer_id'];
    $status = clean($_POST['status']);
    $customerNote = clean($_POST['customer_note'] ?? '');
    $adminNote = clean($_POST['admin_note'] ?? '');
    $productIds = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];

    $allowedStatus = ['pending', 'accepted', 'ready', 'delivered', 'cancelled'];
    if (!in_array($status, $allowedStatus)) $status = 'pending';

    $items = [];
    foreach ($productIds as $i => $pid) {
        $qty = (int) ($quantities[$i] ?? 0);
        if ($pid && $qty > 0) {
            $items[(int) $pid] = ($items[(int) $pid] ?? 0) + $qty;
        }
    }

    if ($customerId <= 0) {
        set_flash('danger', 'कृपया Customer चुनें।');
        redirect('order_add.php');
    }
    if (empty($items)) {
        set_flash('danger', 'कृपया कम से कम एक Product और Quantity चुनें।');
        redirect('order_add.php');
    }

    $orderNo = generate_order_no($conn);
    $stmt = $conn->prepare("INSERT INTO orders (order_no, customer_id, status, customer_note, admin_note) VALUES (?,?,?,?,?)");
    $stmt->bind_param('sisss', $orderNo, $customerId, $status, $customerNote, $adminNote);
    $stmt->execute();
    $orderId = $stmt->insert_id;

    foreach ($items as $pid => $qty) {
        $priceRow = $conn->query("SELECT price FROM products WHERE id = $pid")->fetch_assoc();
        if ($priceRow) {
            $price = $priceRow['price'];
            $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)");
            $itemStmt->bind_param('iiid', $orderId, $pid, $qty, $price);
            $itemStmt->execute();
        }
    }

    set_flash('success', "नया Order बन गया! Order No: $orderNo");
    redirect('orders.php');
}

$customers = $conn->query("SELECT id, client_id, name, customer_type FROM customers WHERE status = 'approved' ORDER BY name");
$products = $conn->query("SELECT * FROM products WHERE status = 'show' ORDER BY name");

include __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h3 style="margin-bottom:16px;">🧾 नया Order बनाएं (Manual Entry)</h3>
    <p style="color:var(--text-light);font-size:13.5px;margin-bottom:18px;">फोन पर या दुकान पर आकर दिए गए Orders यहाँ से सीधे Customer के नाम पर दर्ज करें।</p>

    <form method="POST" id="orderForm">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="form-row">
            <div class="form-group">
                <label>Customer चुनें</label>
                <select name="customer_id" class="form-control" required>
                    <option value="">-- चुनें --</option>
                    <?php while ($c = $customers->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['client_id']) ?>) <?= $c['customer_type'] === 'vip' ? '⭐VIP' : '' ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Order Status</label>
                <select name="status" class="form-control">
                    <option value="pending">Pending</option>
                    <option value="accepted" selected>Accepted</option>
                    <option value="ready">Ready</option>
                    <option value="delivered">Delivered</option>
                </select>
            </div>
        </div>

        <label style="display:block;margin:16px 0 8px;font-weight:600;font-size:13.5px;">Products जोड़ें</label>
        <div id="itemsWrap">
            <div class="form-row item-row">
                <div class="form-group" style="flex:2;">
                    <select name="product_id[]" class="form-control product-select">
                        <option value="">-- Product चुनें --</option>
                        <?php
                        $products->data_seek(0);
                        while ($p = $products->fetch_assoc()):
                        ?>
                            <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= money($p['price']) ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group" style="max-width:120px;">
                    <input type="number" name="quantity[]" class="form-control" placeholder="Qty" min="0" value="0">
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-outline btn-sm" onclick="addItemRow()">+ एक और Product जोड़ें</button>

        <div class="form-group" style="margin-top:16px;">
            <label>Customer Note (वैकल्पिक)</label>
            <input type="text" name="customer_note" class="form-control">
        </div>
        <div class="form-group">
            <label>Admin Note (वैकल्पिक)</label>
            <input type="text" name="admin_note" class="form-control">
        </div>

        <button type="submit" name="create_order" class="btn btn-success">✅ Order बनाएं</button>
        <a href="orders.php" class="btn btn-outline">Cancel</a>
    </form>
</div>

<script>
function addItemRow() {
    const wrap = document.getElementById('itemsWrap');
    const row = wrap.querySelector('.item-row').cloneNode(true);
    row.querySelectorAll('input').forEach(i => i.value = i.type === 'number' ? 0 : '');
    row.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
    wrap.appendChild(row);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
