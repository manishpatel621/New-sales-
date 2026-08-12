<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
require_once __DIR__ . '/../../includes/functions.php';
$waNumber = get_setting('whatsapp_number', '919826293234');
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF']);
?>
</div>

<nav class="bottom-nav">
    <a href="dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
        <span class="nav-icon">🛍️</span> Shop
    </a>
    <a href="orders.php" class="<?= $currentPage === 'orders.php' ? 'active' : '' ?>">
        <span class="nav-icon">🧾</span> Orders
    </a>
    <a href="profile.php" class="<?= in_array($currentPage, ['profile.php','change_password.php']) ? 'active' : '' ?>">
        <span class="nav-icon">👤</span> Profile
    </a>
    <a href="logout.php">
        <span class="nav-icon">🚪</span> Logout
    </a>
</nav>

<!-- WhatsApp Contact Button - number comes from Admin > Settings -->
<a href="https://wa.me/<?= htmlspecialchars($waNumber) ?>" target="_blank" class="whatsapp-float" title="WhatsApp पर संपर्क करें" style="bottom:90px;">💬</a>

<script src="../assets/js/script.js"></script>
<script>
// Register Service Worker (enables Offline shell + Install App)
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('../sw.js').catch(function () {});
}

// Show custom "Install App" button when browser supports it
let deferredPrompt;
window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    document.querySelectorAll('.pwa-install-btn').forEach(function (btn) {
        btn.style.display = 'inline-block';
        btn.addEventListener('click', function () {
            btn.style.display = 'none';
            deferredPrompt.prompt();
        });
    });
});
window.addEventListener('appinstalled', function () {
    document.querySelectorAll('.pwa-install-btn').forEach(function (btn) { btn.style.display = 'none'; });
});
</script>
</body>
</html>
