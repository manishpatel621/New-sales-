<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/logout.php
 * Destroys admin session and redirects to login
 */
require_once __DIR__ . '/../includes/functions.php';
session_unset();
session_destroy();
redirect('login.php');
