<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: customer/change_password.php
 * Customer can change their own password
 */
require_once __DIR__ . '/../includes/auth_customer.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Password बदलें';
$customerId = $_SESSION['customer_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'गलत अनुरोध।';
    } else {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $conn->prepare("SELECT password FROM customers WHERE id = ?");
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!password_verify($current, $row['password'])) {
            $error = 'मौजूदा Password गलत है।';
        } elseif (strlen($new) < 6) {
            $error = 'नया Password कम से कम 6 अक्षर का होना चाहिए।';
        } elseif ($new !== $confirm) {
            $error = 'नया Password मेल नहीं खाता।';
        } else {
            $hashed = password_hash($new, PASSWORD_BCRYPT);
            $update = $conn->prepare("UPDATE customers SET password = ? WHERE id = ?");
            $update->bind_param('si', $hashed, $customerId);
            $update->execute();
            set_flash('success', 'Password सफलतापूर्वक बदल दिया गया।');
            redirect('profile.php');
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width:480px;">
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="form-group"><label>मौजूदा Password</label><input type="password" name="current_password" class="form-control" required></div>
        <div class="form-group"><label>नया Password</label><input type="password" name="new_password" class="form-control" required></div>
        <div class="form-group"><label>नया Password दोबारा लिखें</label><input type="password" name="confirm_password" class="form-control" required></div>
        <button type="submit" class="btn btn-primary">Password बदलें</button>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
