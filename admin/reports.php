<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/reports.php
 * Basic reports: order status summary, top products, date range sales
 */
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Reports';
$current = 'reports';

$from = clean($_GET['from'] ?? date('Y-m-01'));
$to = clean($_GET['to'] ?? date('Y-m-d'));

// Order status summary
$statusSummary = $conn->query("SELECT status, COUNT(*) c FROM orders WHERE DATE(order_time) BETWEEN '$from' AND '$to' GROUP BY status");

// Total sales (only delivered orders counted as real sales)
$salesResult = $conn->query("
    SELECT COALESCE(SUM(oi.quantity * oi.price),0) AS total
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'delivered' AND DATE(o.order_time) BETWEEN '$from' AND '$to'
");
$totalSales = $salesResult->fetch_assoc()['total'];

// Top selling products
$topProducts = $conn->query("
    SELECT p.name, SUM(oi.quantity) AS qty_sold
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    WHERE DATE(o.order_time) BETWEEN '$from' AND '$to'
    GROUP BY p.id ORDER BY qty_sold DESC LIMIT 10
");

// New customers in range
$newCustomers = $conn->query("SELECT COUNT(*) c FROM customers WHERE DATE(created_at) BETWEEN '$from' AND '$to'")->fetch_assoc()['c'];

include __DIR__ . '/includes/header.php';
?>

<div class="card">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        <div class="form-group"><label>From</label><input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>"></div>
        <div class="form-group"><label>To</label><input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>"></div>
        <button class="btn btn-primary">Generate</button>
    </form>
</div>

<div class="stats-grid">
    <div class="stat-box stat-green"><div class="stat-icon">💰</div><div><h3><?= money($totalSales) ?></h3><p>Total Sales (Delivered)</p></div></div>
    <div class="stat-box stat-blue"><div class="stat-icon">🆕</div><div><h3><?= $newCustomers ?></h3><p>New Customers</p></div></div>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">Order Status Summary</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Status</th><th>Count</th></tr></thead>
            <tbody>
            <?php while ($row = $statusSummary->fetch_assoc()): ?>
                <tr><td><span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td><td><?= $row['c'] ?></td></tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">Top Selling Products</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Product</th><th>Quantity Sold</th></tr></thead>
            <tbody>
            <?php while ($row = $topProducts->fetch_assoc()): ?>
                <tr><td><?= htmlspecialchars($row['name']) ?></td><td><?= (int) $row['qty_sold'] ?></td></tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
