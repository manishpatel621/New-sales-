<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: index.php
 * Public landing page - links to Customer Login/Register and Admin Login
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-wrap">
        <div class="auth-box" style="text-align:center;">
            <h2>📦 Order Management System</h2>
            <p style="color:var(--text-light);margin:14px 0 24px;">Products Order करने के लिए Login/Register करें</p>
            <a href="customer/login.php" class="btn btn-primary" style="width:100%;margin-bottom:10px;">Customer Login</a>
            <a href="customer/register.php" class="btn btn-outline" style="width:100%;margin-bottom:10px;">नया Registration</a>
            <a href="admin/login.php" class="btn btn-outline" style="width:100%;">Admin Login</a>
        </div>
    </div>
    <?php $waNumber = get_setting('whatsapp_number', '919826293234'); ?>
    <a href="https://wa.me/<?= htmlspecialchars($waNumber) ?>" target="_blank" class="whatsapp-float" title="WhatsApp पर संपर्क करें">💬</a>
</body>
</html>
