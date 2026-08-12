<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: includes/functions.php
 * Common helper functions used across the whole project
 */

// Start session once, everywhere this file is included
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}

/**
 * Clean user input to prevent XSS
 */
function clean($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    if (isset($conn)) {
        $data = $conn->real_escape_string($data);
    }
    return $data;
}

/**
 * Generate CSRF token
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function csrf_verify($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate next Client ID like C0001, C0002...
 */
function generate_client_id($conn) {
    $result = $conn->query("SELECT client_id FROM customers ORDER BY id DESC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastNumber = (int) substr($row['client_id'], 1);
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    return 'C' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}

/**
 * Generate unique Order Number like ORD-20260712-0001
 */
function generate_order_no($conn) {
    $prefix = 'ORD-' . date('Ymd') . '-';
    $result = $conn->query("SELECT order_no FROM orders WHERE order_no LIKE '$prefix%' ORDER BY id DESC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastNumber = (int) substr($row['order_no'], -4);
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}

/**
 * Upload a single image safely, returns relative path or false
 */
function upload_image($file, $folder = 'assets/uploads/products/') {
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) return false;
    if ($file['size'] > 3 * 1024 * 1024) return false; // Max 3MB
    if ($file['error'] !== UPLOAD_ERR_OK) return false;

    // Verify it's really an image
    if (getimagesize($file['tmp_name']) === false) return false;

    $newName = uniqid('prod_', true) . '.' . $ext;
    $target = rtrim($folder, '/') . '/' . $newName;

    if (move_uploaded_file($file['tmp_name'], __DIR__ . '/../' . $target)) {
        return $target;
    }
    return false;
}

/**
 * Redirect helper
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Flash message helper
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash() {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Get a setting value from DB (Shop Name, WhatsApp Number, etc.)
 */
function get_setting($key, $default = '') {
    global $conn;
    if (!isset($conn)) return $default;
    $key = $conn->real_escape_string($key);
    $result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = '$key' LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return $default;
}

/**
 * Send a text message via Telegram Bot API (free, automatic one-click send)
 * Requires telegram_bot_token set in Settings. Chat ID can be passed
 * explicitly (for a specific Section) or falls back to the global one.
 * Returns true on success, false on failure.
 */
function send_telegram_message($text, $chatIdOverride = null) {
    $token = get_setting('telegram_bot_token', '');
    $chatId = $chatIdOverride ?: get_setting('telegram_chat_id', '');
    if (!$token || !$chatId) return false;

    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'HTML'];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $success = !curl_errno($ch);
    curl_close($ch);

    if (!$success || !$response) return false;
    $result = json_decode($response, true);
    return isset($result['ok']) && $result['ok'] === true;
}

/**
 * Get a section's (category's) WhatsApp number, falling back to the
 * shop's main WhatsApp number from Settings if not set for that section.
 */
function get_section_whatsapp($categoryId) {
    global $conn;
    if ($categoryId) {
        $row = $conn->query("SELECT whatsapp_number FROM categories WHERE id = " . (int)$categoryId)->fetch_assoc();
        if ($row && $row['whatsapp_number']) return $row['whatsapp_number'];
    }
    return get_setting('whatsapp_number', '');
}

/**
 * Get a section's (category's) Telegram Chat ID, falling back to the
 * shop's main Telegram Chat ID from Settings if not set for that section.
 */
function get_section_telegram_chat($categoryId) {
    global $conn;
    if ($categoryId) {
        $row = $conn->query("SELECT telegram_chat_id FROM categories WHERE id = " . (int)$categoryId)->fetch_assoc();
        if ($row && $row['telegram_chat_id']) return $row['telegram_chat_id'];
    }
    return get_setting('telegram_chat_id', '');
}
/**
 * Format currency
 */
function money($amount) {
    $symbol = get_setting('currency_symbol', '₹');
    return $symbol . number_format($amount, 2);
}
