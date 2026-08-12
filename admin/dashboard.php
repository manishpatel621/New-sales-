<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/dashboard.php
 * Admin dashboard showing key stats
 */
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Dashboard';
$current = 'dashboard';

// Today's orders
$today = date('Y-m-d');
$todayOrders = $conn->query("SELECT COUNT(*) c FROM orders WHERE DATE(order_time) = '$today'")->fetch_assoc()['c'];

// Pending orders
$pendingOrders = $conn->query("SELECT COUNT(*) c FROM orders WHERE status = 'pending'")->fetch_assoc()['c'];

// Ready orders
$readyOrders = $conn->query("SELECT COUNT(*) c FROM orders WHERE status = 'ready'")->fetch_assoc()['c'];

// Total customers
$totalCustomers = $conn->query("SELECT COUNT(*) c FROM customers")->fetch_assoc()['c'];

// Total products
$totalProducts = $conn->query("SELECT COUNT(*) c FROM products")->fetch_assoc()['c'];

// VIP / Regular customers
$vipCustomers = $conn->query("SELECT COUNT(*) c FROM customers WHERE customer_type = 'vip'")->fetch_assoc()['c'];
$regularCustomers = $conn->query("SELECT COUNT(*) c FROM customers WHERE customer_type = 'regular'")->fetch_assoc()['c'];

// Recent orders
$recentOrders = $conn->query("
    SELECT o.order_no, o.status, o.order_time, c.name, c.client_id
    FROM orders o JOIN customers c ON o.customer_id = c.id
    ORDER BY o.id DESC LIMIT 8
");

// Recent orders
$recentOrders = $conn->query("
    SELECT o.order_no, o.status, o.order_time, c.name, c.client_id
    FROM orders o JOIN customers c ON o.customer_id = c.id
    ORDER BY o.id DESC LIMIT 6
");

// This week's sales (delivered orders)
$weekSales = $conn->query("
    SELECT COALESCE(SUM(oi.quantity * oi.price),0) AS total
    FROM order_items oi JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'delivered' AND o.order_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")->fetch_assoc()['total'];

// Recent customers
$recentCustomers = $conn->query("SELECT name, client_id, email, customer_type FROM customers ORDER BY id DESC LIMIT 4");

// Top products (by quantity sold, all-time)
$topProducts = $conn->query("
    SELECT p.name, p.price, SUM(oi.quantity) AS qty_sold
    FROM order_items oi JOIN products p ON oi.product_id = p.id
    GROUP BY p.id ORDER BY qty_sold DESC LIMIT 3
");

// Low stock products
$lowStockLimit = (int) get_setting('low_stock_alert', 5);
$lowStock = $conn->query("SELECT name, stock FROM products WHERE stock <= $lowStockLimit AND status='show' ORDER BY stock ASC LIMIT 5");

include __DIR__ . '/includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-box stat-blue"><div class="stat-icon">🧾</div><div><h3><?= $todayOrders ?></h3><p>Today's Orders</p></div></div>
    <div class="stat-box stat-orange"><div class="stat-icon">⏳</div><div><h3><?= $pendingOrders ?></h3><p>Pending Orders</p></div></div>
    <div class="stat-box stat-teal"><div class="stat-icon">✅</div><div><h3><?= $readyOrders ?></h3><p>Ready Orders</p></div></div>
    <div class="stat-box stat-purple"><div class="stat-icon">👥</div><div><h3><?= $totalCustomers ?></h3><p>Total Customers</p></div></div>
    <div class="stat-box stat-pink"><div class="stat-icon">🛍️</div><div><h3><?= $totalProducts ?></h3><p>Total Products</p></div></div>
    <div class="stat-box stat-red"><div class="stat-icon">⭐</div><div><h3><?= $vipCustomers ?></h3><p>VIP Customers</p></div></div>
    <div class="stat-box stat-green"><div class="stat-icon">🙂</div><div><h3><?= $regularCustomers ?></h3><p>Regular Customers</p></div></div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:18px;align-items:start;">
    <div class="card" style="margin-bottom:0;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <h3>हाल के Orders</h3>
            <a href="orders.php" class="btn btn-sm btn-outline">सभी देखें</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Order No</th><th>Customer</th><th>Status</th><th>Time</th></tr></thead>
                <tbody>
                    <?php while ($row = $recentOrders->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['order_no']) ?></td>
                        <td><?= htmlspecialchars($row['name']) ?> (<?= htmlspecialchars($row['client_id']) ?>)</td>
                        <td><span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                        <td style="font-size:12px;"><?= date('d M, h:i A', strtotime($row['order_time'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                <h4 style="font-size:13.5px;color:var(--text-light);">Sales Overview (7 दिन)</h4>
            </div>
            <h3 style="font-size:24px;"><?= money($weekSales) ?></h3>
        </div>

        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <h4 style="font-size:13.5px;color:var(--text-light);">हाल के Customers</h4>
                <a href="customers.php" class="btn btn-sm btn-outline">सभी</a>
            </div>
            <?php while ($c = $recentCustomers->fetch_assoc()): ?>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);">
                <div style="width:30px;height:30px;border-radius:50%;background:var(--primary-light);color:var(--primary-dark);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;"><?= strtoupper(substr($c['name'],0,1)) ?></div>
                <div style="min-width:0;flex:1;">
                    <div style="font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($c['name']) ?></div>
                    <div style="font-size:11px;color:var(--text-light);"><?= htmlspecialchars($c['client_id']) ?></div>
                </div>
                <?php if ($c['customer_type'] === 'vip'): ?><span class="badge badge-vip">VIP</span><?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>

        <?php if ($lowStock->num_rows > 0): ?>
        <div class="card" style="border-color:#fecaca;">
            <h4 style="font-size:13.5px;color:#b91c1c;margin-bottom:10px;">⚠️ Low Stock Alert</h4>
            <?php while ($ls = $lowStock->fetch_assoc()): ?>
                <div style="display:flex;justify-content:space-between;font-size:12.5px;padding:5px 0;">
                    <span><?= htmlspecialchars($ls['name']) ?></span>
                    <strong style="color:#b91c1c;"><?= (int) $ls['stock'] ?> बचे</strong>
                </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h4 style="font-size:13.5px;color:var(--text-light);margin-bottom:12px;">🔥 Top Products (All-time)</h4>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;">
        <?php while ($tp = $topProducts->fetch_assoc()): ?>
            <div style="background:var(--bg);border-radius:var(--radius-sm);padding:12px;">
                <div style="font-size:13px;font-weight:600;margin-bottom:4px;"><?= htmlspecialchars($tp['name']) ?></div>
                <div style="font-size:12px;color:var(--text-light);"><?= (int) $tp['qty_sold'] ?> बिके · <?= money($tp['price']) ?></div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<div class="card" style="display:flex;gap:10px;flex-wrap:wrap;">
    <a href="product_form.php" class="btn btn-primary">+ Product जोड़ें</a>
    <a href="order_add.php" class="btn btn-success">+ नया Order</a>
    <a href="customers.php" class="btn btn-info">👥 Customers देखें</a>
    <a href="reports.php" class="btn btn-outline">📊 Reports देखें</a>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
