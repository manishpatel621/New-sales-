<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/login.php
 * Admin secure login page
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['admin_id'])) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'गलत अनुरोध, दोबारा कोशिश करें।';
    } else {
        $username = clean($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $conn->prepare("SELECT id, username, password, full_name FROM admins WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_last_activity'] = time();
                redirect('dashboard.php');
            } else {
                $error = 'गलत Username या Password';
            }
        } else {
            $error = 'गलत Username या Password';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Order Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-wrap">
        <div class="auth-box">
            <h2>🔒 Admin Login</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['timeout'])): ?>
                <div class="alert alert-info">आपका सेशन समाप्त हो गया, कृपया दोबारा लॉगिन करें।</div>
            <?php endif; ?>
            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
