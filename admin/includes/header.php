<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/includes/header.php
 * Opens HTML, includes topbar. $pageTitle should be set before including.
 */
$pageTitle = $pageTitle ?? 'Admin Panel';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0f172a">
    <link rel="apple-touch-icon" href="../assets/icons/icon-192.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
</head>
<body>
<div class="app-wrapper">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div style="display:flex;align-items:center;gap:12px;">
                <button class="menu-toggle">☰</button>
                <strong><?= htmlspecialchars($pageTitle) ?></strong>
            </div>
            <div style="display:flex;align-items:center;gap:12px;">
                <button class="theme-toggle" title="Dark/Light Mode">🌙</button>
                <button class="pwa-install-btn" style="display:none;background:var(--primary);color:#fff;border:none;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">📲 App Install करें</button>
                <span>👤 <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></span>
            </div>
        </div>
        <div class="page-body">
            <?php if ($flash): ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
            <?php endif; ?>
