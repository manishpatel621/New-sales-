<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: customer/profile.php
 * Customer can edit their own profile details
 */
require_once __DIR__ . '/../includes/auth_customer.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'मेरी Profile';
$customerId = $_SESSION['customer_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'गलत अनुरोध।');
    } else {
        $name = clean($_POST['name']);
        $phone = clean($_POST['phone']);
        $address = clean($_POST['address']);

        $stmt = $conn->prepare("UPDATE customers SET name=?, phone=?, address=? WHERE id=?");
        $stmt->bind_param('sssi', $name, $phone, $address, $customerId);
        $stmt->execute();

        $_SESSION['customer_name'] = $name;
        set_flash('success', 'Profile अपडेट हो गई।');
    }
    redirect('profile.php');
}

$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param('i', $customerId);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

include __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width:560px;text-align:center;">
    <div style="width:64px;height:64px;border-radius:50%;background:var(--primary-light);color:var(--primary-dark);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:26px;margin:0 auto 10px;">
        <?= strtoupper(substr($customer['name'], 0, 1)) ?>
    </div>
    <h3 style="margin-bottom:4px;"><?= htmlspecialchars($customer['name']) ?></h3>
    <p style="color:var(--text-light);font-size:13px;">Client ID: <strong><?= htmlspecialchars($customer['client_id']) ?></strong>
        &nbsp; <span class="badge badge-<?= $customer['customer_type'] ?>"><?= strtoupper($customer['customer_type']) ?></span>
    </p>
</div>

<div class="card" style="max-width:560px;">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="form-group"><label>पूरा नाम</label><input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($customer['name']) ?>"></div>
        <div class="form-group"><label>Email (बदला नहीं जा सकता)</label><input type="email" class="form-control" value="<?= htmlspecialchars($customer['email']) ?>" disabled></div>
        <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control" required value="<?= htmlspecialchars($customer['phone']) ?>"></div>
        <div class="form-group"><label>पता</label><textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($customer['address']) ?></textarea></div>
        <button type="submit" class="btn btn-primary">Save करें</button>
    </form>
</div>

<div class="card" style="max-width:560px;display:flex;gap:10px;flex-wrap:wrap;">
    <a href="change_password.php" class="btn btn-outline">🔑 Password बदलें</a>
    <a href="orders.php" class="btn btn-outline">🧾 मेरे Orders देखें</a>
    <a href="logout.php" class="btn btn-danger">🚪 Logout</a>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
