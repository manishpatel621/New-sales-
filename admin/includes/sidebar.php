<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/includes/sidebar.php
 * Sidebar navigation for admin panel. $current should be set in the
 * including page to highlight the active link.
 */
$current = $current ?? '';
?>
<aside class="sidebar" id="sidebar">
    <div class="brand">
        <div class="logo-badge">📦</div>
        <div class="brand-text">Order Manager<small>Admin Panel</small></div>
    </div>
    <nav>
        <a href="dashboard.php" class="<?= $current === 'dashboard' ? 'active' : '' ?>">🏠 Dashboard</a>
        <a href="categories.php" class="<?= $current === 'categories' ? 'active' : '' ?>">🗂️ Sections</a>
        <a href="subcategories.php" class="<?= $current === 'subcategories' ? 'active' : '' ?>">📁 Sub Categories</a>
        <a href="products.php" class="<?= $current === 'products' ? 'active' : '' ?>">🛍️ Products</a>
        <a href="customers.php" class="<?= $current === 'customers' ? 'active' : '' ?>">👥 Customers</a>
        <a href="orders.php" class="<?= $current === 'orders' ? 'active' : '' ?>">🧾 Orders</a>
        <a href="order_add.php" class="<?= $current === 'order_add' ? 'active' : '' ?>">➕ नया Order</a>
        <a href="announcements.php" class="<?= $current === 'announcements' ? 'active' : '' ?>">📢 Home Banner</a>
        <a href="reports.php" class="<?= $current === 'reports' ? 'active' : '' ?>">📊 Reports</a>
        <a href="settings.php" class="<?= $current === 'settings' ? 'active' : '' ?>">⚙️ Settings</a>
        <a href="backup.php" class="<?= $current === 'backup' ? 'active' : '' ?>">💾 Backup</a>
        <a href="change_password.php" class="<?= $current === 'change_password' ? 'active' : '' ?>">🔑 Password बदलें</a>
        <a href="logout.php">🚪 Logout</a>
    </nav>
</aside>
