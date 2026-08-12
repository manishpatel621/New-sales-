<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: customer/logout.php
 * Destroys customer session
 */
require_once __DIR__ . '/../includes/functions.php';
session_unset();
session_destroy();
redirect('login.php');
