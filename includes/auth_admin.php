<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: includes/auth_admin.php
 * Protects admin pages. Include this at the very top of any admin page
 * that should only be visible to a logged-in admin.
 */
require_once __DIR__ . '/functions.php';

if (empty($_SESSION['admin_id'])) {
    redirect('login.php');
}

// Simple session timeout (30 minutes of inactivity)
$timeout = 1800;
if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    redirect('login.php?timeout=1');
}
$_SESSION['admin_last_activity'] = time();
