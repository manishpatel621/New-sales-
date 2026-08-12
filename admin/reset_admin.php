<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/reset_admin.php
 * ONE-TIME USE SCRIPT — resets/creates the admin login using THIS
 * server's own PHP password_hash() function, so it is guaranteed to
 * match when admin/login.php calls password_verify().
 *
 * HOW TO USE:
 * 1. Open this file in the browser: yoursite.com/admin/reset_admin.php
 * 2. It will show a success message with the login details.
 * 3. Login using those details at admin/login.php
 * 4. DELETE this file from your server immediately after use (security).
 */
require_once __DIR__ . '/../config/db.php';

$username = 'admin';
$password = 'admin123';
$fullName = 'Administrator';

$hashed = password_hash($password, PASSWORD_BCRYPT);

// Check if admin username already exists
$check = $conn->prepare("SELECT id FROM admins WHERE username = ?");
$check->bind_param('s', $username);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    // Update existing admin's password
    $row = $result->fetch_assoc();
    $update = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
    $update->bind_param('si', $hashed, $row['id']);
    $update->execute();
    $action = 'अपडेट (Update)';
} else {
    // Insert new admin
    $insert = $conn->prepare("INSERT INTO admins (username, password, full_name) VALUES (?, ?, ?)");
    $insert->bind_param('sss', $username, $hashed, $fullName);
    $insert->execute();
    $action = 'नया बनाया (Created)';
}

// Verify it actually works right now, before showing success
$verifyCheck = $conn->prepare("SELECT password FROM admins WHERE username = ?");
$verifyCheck->bind_param('s', $username);
$verifyCheck->execute();
$verifyRow = $verifyCheck->get_result()->fetch_assoc();
$works = password_verify($password, $verifyRow['password']);
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reset</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f4f6f9; padding:24px; }
        .box { background:#fff; border-radius:12px; padding:24px; max-width:480px; margin:0 auto; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
        .ok { color:#166534; background:#dcfce7; padding:12px; border-radius:8px; margin-bottom:16px; }
        .fail { color:#991b1b; background:#fee2e2; padding:12px; border-radius:8px; margin-bottom:16px; }
        code { background:#f1f1f1; padding:2px 6px; border-radius:4px; }
        a.btn { display:inline-block; margin-top:14px; background:#0d9488; color:#fff; padding:10px 18px; border-radius:8px; text-decoration:none; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Admin Login Reset</h2>
        <?php if ($works): ?>
            <div class="ok">✅ Admin account <?= $action ?> हो गया और Verify भी सफल रहा!</div>
            <p><strong>Username:</strong> <code><?= htmlspecialchars($username) ?></code></p>
            <p><strong>Password:</strong> <code><?= htmlspecialchars($password) ?></code></p>
            <a href="login.php" class="btn">Login पेज पर जाएं</a>
        <?php else: ?>
            <div class="fail">❌ कुछ गड़बड़ है — Verify फेल हुआ। कृपया config/db.php की Database सेटिंग्स चेक करें।</div>
        <?php endif; ?>
        <p style="margin-top:20px;color:#991b1b;font-size:13px;">⚠️ Security के लिए यह फाइल (reset_admin.php) अभी सर्वर से डिलीट कर दें, और Login के बाद तुरंत Password बदल लें (Sidebar → Password बदलें)।</p>
    </div>
</body>
</html>
