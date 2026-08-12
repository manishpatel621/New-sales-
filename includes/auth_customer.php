<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: includes/auth_customer.php
 * Protects customer pages. Include this at the top of any customer page
 * that requires the customer to be logged in AND approved.
 */
require_once __DIR__ . '/functions.php';

if (empty($_SESSION['customer_id'])) {
    redirect('login.php');
}

if (($_SESSION['customer_status'] ?? '') !== 'approved') {
    session_unset();
    session_destroy();
    redirect('login.php?notapproved=1');
}

$timeout = 1800;
if (isset($_SESSION['customer_last_activity']) && (time() - $_SESSION['customer_last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    redirect('login.php?timeout=1');
}
$_SESSION['customer_last_activity'] = time();
