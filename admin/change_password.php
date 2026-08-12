<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/change_password.php
 * Admin can change their own login password/username
 */
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Password बदलें';
$current = 'change_password';
$adminId = $_SESSION['admin_id'];
$error = '';

$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->bind_param('i', $adminId);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'गलत अनुरोध।';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newUsername = clean($_POST['username'] ?? '');
        $newName = clean($_POST['full_name'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!password_verify($currentPassword, $admin['password'])) {
            $error = 'मौजूदा Password गलत है।';
        } elseif ($newUsername === '' || $newName === '') {
            $error = 'Username और Name आवश्यक हैं।';
        } elseif ($newPassword !== '' && strlen($newPassword) < 6) {
            $error = 'नया Password कम से कम 6 अक्षर का होना चाहिए।';
        } elseif ($newPassword !== '' && $newPassword !== $confirmPassword) {
            $error = 'नया Password मेल नहीं खाता।';
        } else {
            // Check username uniqueness (excluding self)
            $check = $conn->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
            $check->bind_param('si', $newUsername, $adminId);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $error = 'यह Username पहले से मौजूद है।';
            } else {
                if ($newPassword !== '') {
                    $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
                    $update = $conn->prepare("UPDATE admins SET username=?, full_name=?, password=? WHERE id=?");
                    $update->bind_param('sssi', $newUsername, $newName, $hashed, $adminId);
                } else {
                    $update = $conn->prepare("UPDATE admins SET username=?, full_name=? WHERE id=?");
                    $update->bind_param('ssi', $newUsername, $newName, $adminId);
                }
                $update->execute();
                $_SESSION['admin_name'] = $newName;
                set_flash('success', 'Details सफलतापूर्वक अपडेट हो गईं।');
                redirect('change_password.php');
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width:520px;">
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="form-group">
            <label>पूरा नाम</label>
            <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($admin['full_name']) ?>">
        </div>
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($admin['username']) ?>">
        </div>
        <hr style="margin:16px 0;border:none;border-top:1px solid var(--border);">
        <div class="form-group">
            <label>मौजूदा Password (Confirm करने के लिए)</label>
            <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="form-group">
            <label>नया Password (खाली छोड़ें अगर बदलना नहीं है)</label>
            <input type="password" name="new_password" class="form-control">
        </div>
        <div class="form-group">
            <label>नया Password दोबारा लिखें</label>
            <input type="password" name="confirm_password" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Save करें</button>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
