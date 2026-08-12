<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: customer/login.php
 * Customer login - only 'approved' customers can log in
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['customer_id'])) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'गलत अनुरोध, दोबारा कोशिश करें।';
    } else {
        $email = clean($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $cust = $result->fetch_assoc();
            if (password_verify($password, $cust['password'])) {
                if ($cust['status'] === 'approved') {
                    session_regenerate_id(true);
                    $_SESSION['customer_id'] = $cust['id'];
                    $_SESSION['customer_name'] = $cust['name'];
                    $_SESSION['client_id'] = $cust['client_id'];
                    $_SESSION['customer_status'] = $cust['status'];
                    $_SESSION['customer_last_activity'] = time();
                    redirect('dashboard.php');
                } elseif ($cust['status'] === 'pending') {
                    $error = 'आपका Account अभी Admin Approval का इंतज़ार कर रहा है।';
                } elseif ($cust['status'] === 'rejected') {
                    $error = 'आपका Account Reject कर दिया गया है।';
                } else {
                    $error = 'आपका Account Blacklist किया गया है।';
                }
            } else {
                $error = 'गलत Email या Password';
            }
        } else {
            $error = 'गलत Email या Password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - Order Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-wrap">
        <div class="auth-box">
            <h2>👤 Customer Login</h2>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if (isset($_GET['timeout'])): ?><div class="alert alert-info">सेशन समाप्त हो गया, दोबारा Login करें।</div><?php endif; ?>
            <?php if (isset($_GET['notapproved'])): ?><div class="alert alert-info">आपका Account Approved नहीं है।</div><?php endif; ?>
            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" required autofocus></div>
                <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" required></div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Login</button>
            </form>
            <p style="text-align:center;margin-top:14px;">Account नहीं है? <a href="register.php" style="color:var(--primary);">Register करें</a></p>
        </div>
    </div>
</body>
</html>
