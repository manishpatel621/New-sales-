<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/customers.php
 * Customer Approve/Reject, Edit, Delete, VIP/Regular, Blacklist
 */
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Customers';
$current = 'customers';

// ---- Change status (approve/reject/blacklist) ----
if (isset($_GET['status'], $_GET['id'])) {
    $allowed = ['approved', 'rejected', 'blacklist', 'pending'];
    $status = in_array($_GET['status'], $allowed) ? $_GET['status'] : 'pending';
    $id = (int) $_GET['id'];
    $stmt = $conn->prepare("UPDATE customers SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $id);
    $stmt->execute();
    set_flash('success', 'Customer status अपडेट हो गया।');
    redirect('customers.php');
}

// ---- Toggle VIP/Regular ----
if (isset($_GET['toggle_type'])) {
    $id = (int) $_GET['toggle_type'];
    $conn->query("UPDATE customers SET customer_type = IF(customer_type='vip','regular','vip') WHERE id = $id");
    redirect('customers.php');
}

// ---- Delete customer ----
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    set_flash('success', 'Customer डिलीट हो गया।');
    redirect('customers.php');
}

// ---- Edit customer (details + optional password reset) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_customer'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'गलत अनुरोध।');
    } else {
        $id = (int) $_POST['id'];
        $name = clean($_POST['name']);
        $email = clean($_POST['email']);
        $phone = clean($_POST['phone']);
        $address = clean($_POST['address']);
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($newPassword !== '' && strlen($newPassword) < 6) {
            set_flash('danger', 'नया Password कम से कम 6 अक्षर का होना चाहिए।');
            redirect('customers.php?edit=' . $id);
        }
        if ($newPassword !== '' && $newPassword !== $confirmPassword) {
            set_flash('danger', 'नया Password मेल नहीं खाता।');
            redirect('customers.php?edit=' . $id);
        }

        if ($newPassword !== '') {
            $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE customers SET name=?, email=?, phone=?, address=?, password=? WHERE id=?");
            $stmt->bind_param('sssssi', $name, $email, $phone, $address, $hashed, $id);
        } else {
            $stmt = $conn->prepare("UPDATE customers SET name=?, email=?, phone=?, address=? WHERE id=?");
            $stmt->bind_param('ssssi', $name, $email, $phone, $address, $id);
        }
        $stmt->execute();
        set_flash('success', $newPassword !== '' ? 'Customer अपडेट हो गया और Password भी बदल दिया गया।' : 'Customer अपडेट हो गया।');
    }
    redirect('customers.php');
}

$editRow = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editRow = $stmt->get_result()->fetch_assoc();
}

$search = clean($_GET['q'] ?? '');
$sql = "SELECT c.*,
        (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) AS total_orders,
        (SELECT MAX(order_time) FROM orders o WHERE o.customer_id = c.id) AS last_order
        FROM customers c WHERE 1=1";
if ($search !== '') {
    $sql .= " AND (c.name LIKE '%$search%' OR c.client_id LIKE '%$search%' OR c.email LIKE '%$search%')";
}
$sql .= " ORDER BY c.id DESC";
$customers = $conn->query($sql);

include __DIR__ . '/includes/header.php';
?>

<?php if ($editRow): ?>
<div class="card">
    <h3 style="margin-bottom:16px;">Customer Edit करें (<?= htmlspecialchars($editRow['client_id']) ?>)</h3>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= $editRow['id'] ?>">
        <div class="form-row">
            <div class="form-group"><label>नाम</label><input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editRow['name']) ?>"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($editRow['email']) ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control" required value="<?= htmlspecialchars($editRow['phone']) ?>"></div>
            <div class="form-group"><label>पता</label><input type="text" name="address" class="form-control" value="<?= htmlspecialchars($editRow['address']) ?>"></div>
        </div>
        <hr style="margin:16px 0;border:none;border-top:1px solid var(--border);">
        <p style="font-size:13px;color:var(--text-light);margin-bottom:10px;">🔑 Customer का Password Reset करें (खाली छोड़ें अगर बदलना नहीं है)</p>
        <div class="form-row">
            <div class="form-group"><label>नया Password</label><input type="password" name="new_password" class="form-control" placeholder="कम से कम 6 अक्षर"></div>
            <div class="form-group"><label>नया Password दोबारा लिखें</label><input type="password" name="confirm_password" class="form-control"></div>
        </div>
        <button type="submit" name="save_customer" class="btn btn-primary">Save</button>
        <a href="customers.php" class="btn btn-outline">Cancel</a>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <form method="GET" style="margin-bottom:16px;">
        <input type="text" name="q" class="form-control" placeholder="नाम, Client ID या Email से खोजें..." value="<?= htmlspecialchars($search) ?>">
    </form>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Client ID</th><th>नाम</th><th>Email/Phone</th><th>Type</th><th>Status</th><th>Total Orders</th><th>Last Order</th><th>Actions</th></tr></thead>
            <tbody>
            <?php while ($c = $customers->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($c['client_id']) ?></strong></td>
                    <td><?= htmlspecialchars($c['name']) ?></td>
                    <td><?= htmlspecialchars($c['email']) ?><br><small><?= htmlspecialchars($c['phone']) ?></small></td>
                    <td><span class="badge badge-<?= $c['customer_type'] ?>"><?= strtoupper($c['customer_type']) ?></span></td>
                    <td><span class="badge badge-<?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span></td>
                    <td><?= (int) $c['total_orders'] ?></td>
                    <td><?= $c['last_order'] ? date('d M Y', strtotime($c['last_order'])) : '-' ?></td>
                    <td style="white-space:nowrap;">
                        <?php if ($c['status'] === 'pending'): ?>
                            <a href="customers.php?status=approved&id=<?= $c['id'] ?>" class="btn btn-sm btn-success">Approve</a>
                            <a href="customers.php?status=rejected&id=<?= $c['id'] ?>" class="btn btn-sm btn-danger">Reject</a>
                        <?php endif; ?>
                        <a href="customers.php?toggle_type=<?= $c['id'] ?>" class="btn btn-sm btn-warning">Toggle VIP</a>
                        <a href="customers.php?edit=<?= $c['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                        <a href="customer_bill.php?customer_id=<?= $c['id'] ?>" target="_blank" class="btn btn-sm btn-outline">🧾 Full Bill</a>
                        <?php if ($c['status'] !== 'blacklist'): ?>
                            <a href="customers.php?status=blacklist&id=<?= $c['id'] ?>" class="btn btn-sm" style="background:#111827;color:#fff;" onclick="return confirmDelete('Blacklist करें?')">Blacklist</a>
                        <?php endif; ?>
                        <a href="customers.php?delete=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete()">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
