<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: customer/register.php
 * Customer registration - account stays 'pending' until admin approves
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['customer_id'])) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'गलत अनुरोध, दोबारा कोशिश करें।';
    } else {
        $name = clean($_POST['name']);
        $email = clean($_POST['email']);
        $phone = clean($_POST['phone']);
        $address = clean($_POST['address']);
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($name === '' || $email === '' || $phone === '' || $password === '') {
            $error = 'कृपया सभी आवश्यक फ़ील्ड भरें।';
        } elseif ($password !== $confirm) {
            $error = 'Password मेल नहीं खाता।';
        } elseif (strlen($password) < 6) {
            $error = 'Password कम से कम 6 अक्षर का होना चाहिए।';
        } else {
            $check = $conn->prepare("SELECT id FROM customers WHERE email = ?");
            $check->bind_param('s', $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $error = 'यह Email पहले से रजिस्टर्ड है।';
            } else {
                $clientId = generate_client_id($conn);
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                $stmt = $conn->prepare("INSERT INTO customers (client_id, name, email, phone, address, password, status) VALUES (?,?,?,?,?,?,'pending')");
                $stmt->bind_param('ssssss', $clientId, $name, $email, $phone, $address, $hashedPassword);
                $stmt->execute();

                $success = "रजिस्ट्रेशन सफल! आपकी Client ID है: $clientId. Admin approval के बाद ही आप Login कर पाएंगे।";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration - Order Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-wrap">
        <div class="auth-box" style="max-width:480px;">
            <h2>📝 नया Account बनाएं</h2>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <a href="login.php" class="btn btn-primary" style="width:100%;">Login पेज पर जाएं</a>
            <?php else: ?>
            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="form-group"><label>पूरा नाम</label><input type="text" name="name" class="form-control" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" required></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control" required></div>
                <div class="form-group"><label>पता</label><textarea name="address" class="form-control" rows="2"></textarea></div>
                <div class="form-row">
                    <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" required></div>
                    <div class="form-group"><label>Confirm Password</label><input type="password" name="confirm_password" class="form-control" required></div>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Register करें</button>
            </form>
            <p style="text-align:center;margin-top:14px;">पहले से Account है? <a href="login.php" style="color:var(--primary);">Login करें</a></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
