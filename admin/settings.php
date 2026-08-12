<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/settings.php
 * Shop-wide settings: Shop Name, WhatsApp Number, Currency Symbol,
 * Low Stock Alert threshold. Saved in `settings` table - no code
 * editing needed anymore for things like WhatsApp number.
 */
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Settings';
$current = 'settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'गलत अनुरोध।');
    } else {
        $fields = ['shop_name', 'whatsapp_number', 'currency_symbol', 'low_stock_alert', 'telegram_bot_token', 'telegram_chat_id'];
        foreach ($fields as $field) {
            $value = clean($_POST[$field] ?? '');
            if ($field === 'whatsapp_number') {
                $value = preg_replace('/[^0-9]/', '', $value); // digits only
            }
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param('sss', $field, $value, $value);
            $stmt->execute();
        }
        set_flash('success', 'Settings सेव हो गईं।');
    }
    redirect('settings.php');
}

$shopName = get_setting('shop_name', 'My Shop');
$whatsapp = get_setting('whatsapp_number', '');
$currency = get_setting('currency_symbol', '₹');
$lowStock = get_setting('low_stock_alert', '5');
$telegramToken = get_setting('telegram_bot_token', '');
$telegramChatId = get_setting('telegram_chat_id', '');

include __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width:560px;">
    <h3 style="margin-bottom:6px;">⚙️ Shop Settings</h3>
    <p style="color:var(--text-light);font-size:13.5px;margin-bottom:18px;">यह Settings बिना Code बदले, सीधे यहीं से Update हो जाएंगी।</p>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="form-group">
            <label>Shop / Business का नाम</label>
            <input type="text" name="shop_name" class="form-control" value="<?= htmlspecialchars($shopName) ?>">
        </div>
        <div class="form-group">
            <label>Main WhatsApp Number (Country code सहित, जैसे 91XXXXXXXXXX)</label>
            <input type="text" name="whatsapp_number" class="form-control" value="<?= htmlspecialchars($whatsapp) ?>">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Currency Symbol</label>
                <input type="text" name="currency_symbol" class="form-control" value="<?= htmlspecialchars($currency) ?>" maxlength="3">
            </div>
            <div class="form-group">
                <label>Low Stock Alert (इससे कम Stock पर चेतावनी)</label>
                <input type="number" name="low_stock_alert" class="form-control" value="<?= htmlspecialchars($lowStock) ?>">
            </div>
        </div>

        <hr style="margin:20px 0;border:none;border-top:1px solid var(--border);">
        <h4 style="font-size:14px;margin-bottom:6px;">✈️ Telegram Auto-Send Setup (Free, One-Click)</h4>
        <p style="color:var(--text-light);font-size:12.5px;margin-bottom:14px;line-height:1.6;">
            1. Telegram खोलें, <strong>@BotFather</strong> Search करें, उसे <code>/newbot</code> भेजें, नाम/username दें — वो एक <strong>Bot Token</strong> देगा, नीचे paste करें।<br>
            2. अपने नए Bot को Telegram पर एक बार Search करके कोई भी Message भेज दें (जैसे "hi")।<br>
            3. Browser में यह खोलें (TOKEN की जगह अपना Token डालें): <code>https://api.telegram.org/bot TOKEN /getUpdates</code><br>
            4. वहाँ जो दिखे उसमें <code>"chat":{"id": ...}</code> में जो नंबर है, वही <strong>Chat ID</strong> है — नीचे paste करें।
        </p>
        <div class="form-group">
            <label>Telegram Bot Token</label>
            <input type="text" name="telegram_bot_token" class="form-control" placeholder="123456:ABC-DEF..." value="<?= htmlspecialchars($telegramToken) ?>">
        </div>
        <div class="form-group">
            <label>Telegram Chat ID</label>
            <input type="text" name="telegram_chat_id" class="form-control" placeholder="123456789" value="<?= htmlspecialchars($telegramChatId) ?>">
        </div>

        <button type="submit" name="save_settings" class="btn btn-primary">Save Settings</button>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
