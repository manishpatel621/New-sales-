<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: config/db.php
 * Database connection using MySQLi (secure, prepared-statement ready)
 */

// ---- EDIT THESE VALUES ACCORDING TO YOUR SERVER ----
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'order_management');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die('Database Connection Failed: ' . $conn->connect_error);
}

// Force UTF-8
$conn->set_charset('utf8mb4');
