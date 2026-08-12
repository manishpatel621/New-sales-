<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/announcements.php
 * Manage banners shown on Customer Home page - message + multiple
 * phone numbers (call/WhatsApp). Admin can add multiple banners and
 * turn each active/inactive.
 */
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Home Banner / Announcements';
$current = 'announcements';

// ---- Save (Add / Edit) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_announcement'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'गलत अनुरोध।');
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $title = clean($_POST['title']);
        $message = clean($_POST['message']);
        $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

        // Clean phone numbers: split by comma/space/newline, keep digits only, rejoin
        $rawNumbers = preg_split('/[\s,]+/', trim($_POST['phone_numbers'] ?? ''));
        $numbers = [];
        foreach ($rawNumbers as $n) {
            $digits = preg_replace('/[^0-9]/', '', $n);
            if ($digits !== '') $numbers[] = $digits;
        }
        $phoneNumbers = implode(',', $numbers);

        if ($title === '') {
            set_flash('danger', 'Title आवश्यक है।');
        } elseif ($id > 0) {
            $stmt = $conn->prepare("UPDATE announcements SET title=?, message=?, phone_numbers=?, status=? WHERE id=?");
            $stmt->bind_param('ssssi', $title, $message, $phoneNumbers, $status, $id);
            $stmt->execute();
            set_flash('success', 'Banner अपडेट हो गया।');
        } else {
            $stmt = $conn->prepare("INSERT INTO announcements (title, message, phone_numbers, status) VALUES (?,?,?,?)");
            $stmt->bind_param('ssss', $title, $message, $phoneNumbers, $status);
            $stmt->execute();
            set_flash('success', 'Banner जोड़ा गया।');
        }
    }
    redirect('announcements.php');
}

// ---- Toggle status ----
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $conn->query("UPDATE announcements SET status = IF(status='active','inactive','active') WHERE id = $id");
    redirect('announcements.php');
}

// ---- Delete ----
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    set_flash('success', 'Banner डिलीट हो गया।');
    redirect('announcements.php');
}

$editRow = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM announcements WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editRow = $stmt->get_result()->fetch_assoc();
}

$announcements = $conn->query("SELECT * FROM announcements ORDER BY id DESC");

include __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h3 style="margin-bottom:6px;"><?= $editRow ? 'Banner Edit करें' : 'नया Banner जोड़ें' ?></h3>
    <p style="color:var(--text-light);font-size:13.5px;margin-bottom:16px;">यह Banner Customer के Home/Shop page पर दिखेगा — कोई भी Message और आपके 4-5 या ज़्यादा Mobile Numbers जो Customer को दिखाना है।</p>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= $editRow['id'] ?? 0 ?>">
        <div class="form-group">
            <label>Title / Heading</label>
            <input type="text" name="title" class="form-control" required placeholder="जैसे: हमसे संपर्क करें" value="<?= htmlspecialchars($editRow['title'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Message</label>
            <textarea name="message" class="form-control" rows="2" placeholder="जैसे: ऑर्डर से जुड़ी जानकारी के लिए कॉल करें"><?= htmlspecialchars($editRow['message'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>Mobile Numbers (Comma से अलग करें, जितने चाहें उतने डालें)</label>
            <input type="text" name="phone_numbers" class="form-control" placeholder="9876543210, 9123456780, 9012345678" value="<?= htmlspecialchars($editRow['phone_numbers'] ?? '') ?>">
        </div>
        <div class="form-group" style="max-width:220px;">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="active" <?= (($editRow['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active (दिखेगा)</option>
                <option value="inactive" <?= (($editRow['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive (छिपा रहेगा)</option>
            </select>
        </div>
        <button type="submit" name="save_announcement" class="btn btn-primary">Save</button>
        <?php if ($editRow): ?><a href="announcements.php" class="btn btn-outline">Cancel</a><?php endif; ?>
    </form>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">सभी Banners</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Title</th><th>Message</th><th>Numbers</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php while ($row = $announcements->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['title']) ?></strong></td>
                    <td style="max-width:260px;"><?= htmlspecialchars(mb_strimwidth($row['message'] ?? '', 0, 80, '...')) ?></td>
                    <td><?= htmlspecialchars($row['phone_numbers']) ?></td>
                    <td><span class="badge badge-<?= $row['status'] === 'active' ? 'approved' : 'rejected' ?>"><?= ucfirst($row['status']) ?></span></td>
                    <td style="white-space:nowrap;">
                        <a href="announcements.php?edit=<?= $row['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                        <a href="announcements.php?toggle=<?= $row['id'] ?>" class="btn btn-sm btn-warning"><?= $row['status'] === 'active' ? 'Hide' : 'Show' ?></a>
                        <a href="announcements.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete()">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
