<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: customer/includes/header.php
 * Shared header for customer panel - simplified topbar +
 * app-style bottom navigation for a cleaner, more modern feel
 */
$pageTitle = $pageTitle ?? 'Customer Panel';
$flash = get_flash();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#0d9488">
    <link rel="apple-touch-icon" href="../assets/icons/icon-192.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
</head>
<body>
<div class="topbar">
    <div style="display:flex;align-items:center;gap:10px;">
        <div style="width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,#14b8a6,#6366f1);display:flex;align-items:center;justify-content:center;font-size:14px;">🛒</div>
        <strong style="font-size:14.5px;"><?= htmlspecialchars($pageTitle) ?></strong>
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
        <button class="theme-toggle" title="Dark/Light Mode">🌙</button>
        <button class="pwa-install-btn" style="display:none;background:var(--primary);color:#fff;border:none;padding:6px 10px;border-radius:8px;font-size:11px;font-weight:600;cursor:pointer;">📲 Install</button>
        <a href="profile.php" style="width:30px;height:30px;border-radius:50%;background:var(--primary-light);color:var(--primary-dark);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;">
            <?= strtoupper(substr($_SESSION['customer_name'] ?? 'C', 0, 1)) ?>
        </a>
    </div>
</div>
<div class="page-body customer-body-padding">
    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>
