<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/categories.php
 * Add / Edit / Delete Categories (Sections) - each section can have
 * its own WhatsApp Number AND Telegram Chat ID, and its Orders can
 * be downloaded separately
 */
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Sections / Categories';
$current = 'categories';

// ---- Handle Add / Edit ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'गलत अनुरोध।');
    } else {
        $name = clean($_POST['name']);
        $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
        $whatsapp = preg_replace('/[^0-9]/', '', $_POST['whatsapp_number'] ?? '');
        $telegramChat = clean($_POST['telegram_chat_id'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);

        if ($name === '') {
            set_flash('danger', 'Section नाम आवश्यक है।');
        } elseif ($id > 0) {
            $stmt = $conn->prepare("UPDATE categories SET name=?, status=?, whatsapp_number=?, telegram_chat_id=? WHERE id=?");
            $stmt->bind_param('ssssi', $name, $status, $whatsapp, $telegramChat, $id);
            $stmt->execute();
            set_flash('success', 'Section अपडेट हो गया।');
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name, status, whatsapp_number, telegram_chat_id) VALUES (?,?,?,?)");
            $stmt->bind_param('ssss', $name, $status, $whatsapp, $telegramChat);
            $stmt->execute();
            set_flash('success', 'Section जोड़ा गया।');
        }
    }
    redirect('categories.php');
}

// ---- Handle Delete ----
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    set_flash('success', 'Section डिलीट हो गया।');
    redirect('categories.php');
}

// ---- Edit fetch ----
$editRow = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editRow = $stmt->get_result()->fetch_assoc();
}

$categories = $conn->query("SELECT * FROM categories ORDER BY id DESC");
$globalWa = get_setting('whatsapp_number', '');
$globalTelegram = get_setting('telegram_chat_id', '');
$telegramBotSet = get_setting('telegram_bot_token', '') !== '';

include __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h3 style="margin-bottom:6px;"><?= $editRow ? 'Section Edit करें' : 'नया Section जोड़ें' ?></h3>
    <p style="color:var(--text-light);font-size:13px;margin-bottom:16px;">जैसे: Shirt Section, Jeans Section, Lower/Pajama Section, Suit Section — हर Section का अपना अलग WhatsApp Number और Telegram Chat भी रख सकते हैं (खाली छोड़ने पर Main Settings वाला इस्तेमाल होगा)।</p>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= $editRow['id'] ?? 0 ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Section / Category नाम</label>
                <input type="text" name="name" class="form-control" required placeholder="जैसे: Shirt Section" value="<?= htmlspecialchars($editRow['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="active" <?= (($editRow['status'] ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= (($editRow['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>इस Section का WhatsApp Number (वैकल्पिक)</label>
                <input type="text" name="whatsapp_number" class="form-control" placeholder="919XXXXXXXXX (खाली = Main नंबर: <?= htmlspecialchars($globalWa ?: 'सेट नहीं') ?>)" value="<?= htmlspecialchars($editRow['whatsapp_number'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>इस Section का Telegram Chat ID (वैकल्पिक)</label>
                <input type="text" name="telegram_chat_id" class="form-control" placeholder="खाली = Main Chat ID: <?= htmlspecialchars($globalTelegram ?: 'सेट नहीं') ?>" value="<?= htmlspecialchars($editRow['telegram_chat_id'] ?? '') ?>">
            </div>
        </div>
        <?php if (!$telegramBotSet): ?>
            <p style="font-size:12.5px;color:#92400e;background:#fef3c7;padding:8px 12px;border-radius:8px;margin-bottom:10px;">⚠️ Telegram अभी काम नहीं करेगा — पहले Settings Page में Bot Token सेट करें।</p>
        <?php endif; ?>
        <button type="submit" name="save_category" class="btn btn-primary">Save</button>
        <?php if ($editRow): ?><a href="categories.php" class="btn btn-outline">Cancel</a><?php endif; ?>
    </form>
</div>

<div class="card">
    <h3 style="margin-bottom:16px;">सभी Sections</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>नाम</th><th>WhatsApp</th><th>Telegram Chat</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php $i = 1; while ($row = $categories->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= $row['whatsapp_number'] ? htmlspecialchars($row['whatsapp_number']) : '<span style="color:var(--text-light);">Main</span>' ?></td>
                    <td><?= $row['telegram_chat_id'] ? htmlspecialchars($row['telegram_chat_id']) : '<span style="color:var(--text-light);">Main</span>' ?></td>
                    <td><span class="badge badge-<?= $row['status'] === 'active' ? 'approved' : 'rejected' ?>"><?= ucfirst($row['status']) ?></span></td>
                    <td style="white-space:nowrap;">
                        <a href="categories.php?edit=<?= $row['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                        <a href="orders_export.php?category_id=<?= $row['id'] ?>" class="btn btn-sm btn-info">⬇️ Orders</a>
                        <a href="categories.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete()">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
