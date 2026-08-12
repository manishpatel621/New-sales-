<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: customer/dashboard.php
 * Shop page - customer browses products and places an order request.
 * Search is Live (Amazon/Flipkart style - results update as you type,
 * powered by search_products.php via AJAX, no page reload needed).
 */
require_once __DIR__ . '/../includes/auth_customer.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Shop';

// ---- Place Order ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'गलत अनुरोध।');
        redirect('dashboard.php');
    }

    $productIds = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $customerNote = clean($_POST['customer_note'] ?? '');

    $items = [];
    foreach ($productIds as $i => $pid) {
        $qty = (int) ($quantities[$i] ?? 0);
        if ($qty > 0) {
            $items[(int) $pid] = ($items[(int) $pid] ?? 0) + $qty;
        }
    }

    if (empty($items)) {
        set_flash('danger', 'कृपया कम से कम एक Product और Quantity चुनें।');
        redirect('dashboard.php');
    }

    $orderNo = generate_order_no($conn);
    $customerId = $_SESSION['customer_id'];

    $stmt = $conn->prepare("INSERT INTO orders (order_no, customer_id, customer_note, status) VALUES (?,?,?,'pending')");
    $stmt->bind_param('sis', $orderNo, $customerId, $customerNote);
    $stmt->execute();
    $orderId = $stmt->insert_id;

    foreach ($items as $pid => $qty) {
        $priceRow = $conn->query("SELECT price FROM products WHERE id = $pid")->fetch_assoc();
        if ($priceRow) {
            $price = $priceRow['price'];
            $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)");
            $itemStmt->bind_param('iiid', $orderId, $pid, $qty, $price);
            $itemStmt->execute();
        }
    }

    set_flash('success', "आपका Order Request भेज दिया गया है! Order No: $orderNo");
    redirect('orders.php');
}

// ---- Initial page load: show all (or category-filtered) products ----
$catFilter = (int) ($_GET['category'] ?? 0);

$sql = "SELECT p.* FROM products p WHERE p.status = 'show' AND p.stock > 0";
if ($catFilter > 0) $sql .= " AND p.category_id = $catFilter";
$sql .= " ORDER BY p.id DESC";
$products = $conn->query($sql);

$categories = $conn->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name");

// Active banners/announcements to show on top of Home page
$announcements = $conn->query("SELECT * FROM announcements WHERE status = 'active' ORDER BY id DESC");

include __DIR__ . '/includes/header.php';
?>

<?php if ($announcements->num_rows > 0): ?>
<div class="promo-carousel">
    <?php while ($ann = $announcements->fetch_assoc()):
        $numbers = array_filter(array_map('trim', explode(',', $ann['phone_numbers'] ?? '')));
    ?>
    <div class="promo-banner" data-banner-id="<?= $ann['id'] ?>">
        <div class="promo-icon">📢</div>
        <div class="promo-body">
            <h4><?= htmlspecialchars($ann['title']) ?></h4>
            <?php if ($ann['message']): ?><p><?= htmlspecialchars($ann['message']) ?></p><?php endif; ?>
            <?php if ($numbers): ?>
            <div class="promo-numbers">
                <?php foreach ($numbers as $num): ?>
                    <a href="tel:<?= htmlspecialchars($num) ?>">📞 <?= htmlspecialchars($num) ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <button class="promo-close" title="बंद करें">✕</button>
    </div>
    <?php endwhile; ?>
</div>
<?php endif; ?>

<div style="margin-bottom:14px;">
    <div style="position:relative;margin-bottom:10px;">
        <input type="text" id="searchBox" class="form-control" placeholder="🔍 Product या Brand खोजें..." autocomplete="off">
        <span id="searchSpinner" style="display:none;position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:12px;color:var(--text-light);">खोज रहे हैं...</span>
    </div>
    <div class="chip-scroll">
        <button type="button" class="filter-chip active" data-cat="0">सभी</button>
        <?php $categories->data_seek(0); while ($c = $categories->fetch_assoc()): ?>
            <button type="button" class="filter-chip" data-cat="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></button>
        <?php endwhile; ?>
    </div>
</div>

<form method="POST" id="shopForm">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="product-grid" id="productGrid">
        <?php while ($p = $products->fetch_assoc()):
            $img = $conn->query("SELECT image_path FROM product_images WHERE product_id = {$p['id']} LIMIT 1")->fetch_assoc();
        ?>
        <div class="product-card">
            <img src="<?= $img ? '../' . htmlspecialchars($img['image_path']) : 'https://placehold.co/220x160?text=No+Image' ?>">
            <div class="p-body">
                <h4><?= htmlspecialchars($p['name']) ?></h4>
                <p class="p-brand"><?= htmlspecialchars($p['brand']) ?> · <?= htmlspecialchars($p['unit']) ?></p>
                <p class="p-price" data-price="<?= $p['price'] ?>"><?= money($p['price']) ?></p>
                <p class="p-stock">Stock: <?= (int) $p['stock'] ?></p>
                <input type="hidden" name="product_id[]" value="<?= $p['id'] ?>">
                <div class="qty-stepper">
                    <button type="button" class="qty-btn qty-minus">−</button>
                    <input type="number" class="qty-input" name="quantity[]" min="0" max="<?= (int) $p['stock'] ?>" value="0" inputmode="numeric">
                    <button type="button" class="qty-btn qty-plus">+</button>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <div class="card" style="margin-top:20px;">
        <div class="form-group">
            <label>Order Note (वैकल्पिक)</label>
            <textarea name="customer_note" class="form-control" rows="2" placeholder="कोई खास निर्देश..."></textarea>
        </div>
    </div>

    <div class="cart-bar">
        <div>
            <div class="cart-info"><span id="cartCount">0</span> Items चुने गए</div>
            <div class="cart-total">₹<span id="cartTotal">0</span></div>
        </div>
        <button type="submit" name="place_order" class="btn btn-success">🛒 Order Request भेजें</button>
    </div>
</form>

<script>
const CART_KEY = 'shop_cart_v1';
const PRICE_CACHE_KEY = 'shop_cart_prices_v1';
let currentCategory = 0;

function loadCart() {
    try { return JSON.parse(localStorage.getItem(CART_KEY) || '{}'); } catch (e) { return {}; }
}
function saveCart(cart) { localStorage.setItem(CART_KEY, JSON.stringify(cart)); }
function loadPriceCache() {
    try { return JSON.parse(localStorage.getItem(PRICE_CACHE_KEY) || '{}'); } catch (e) { return {}; }
}
function savePriceCache(cache) { localStorage.setItem(PRICE_CACHE_KEY, JSON.stringify(cache)); }

// Render a product-card element from a product object (used by both
// the initial PHP-rendered grid and live search results)
function renderProductCard(p) {
    const imgSrc = p.image || 'https://placehold.co/220x160?text=No+Image';
    return `
        <div class="product-card">
            <img src="${imgSrc}">
            <div class="p-body">
                <h4>${escapeHtml(p.name)}</h4>
                <p class="p-brand">${escapeHtml(p.brand || '')}</p>
                <p class="p-price" data-price="${p.price}">${p.price_formatted}</p>
                <p class="p-stock">Stock: ${p.stock}</p>
                <input type="hidden" name="product_id[]" value="${p.id}">
                <div class="qty-stepper">
                    <button type="button" class="qty-btn qty-minus">−</button>
                    <input type="number" class="qty-input" name="quantity[]" min="0" max="${p.stock}" value="0" inputmode="numeric">
                    <button type="button" class="qty-btn qty-plus">+</button>
                </div>
            </div>
        </div>`;
}
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function attachCardEvents() {
    const cart = loadCart();
    document.querySelectorAll('#productGrid .product-card').forEach(function (card) {
        const pid = card.querySelector('input[name="product_id[]"]').value;
        const input = card.querySelector('.qty-input');
        if (cart[pid]) input.value = cart[pid];

        const minus = card.querySelector('.qty-minus');
        const plus = card.querySelector('.qty-plus');
        const max = parseInt(input.max || 9999);

        function update(val) {
            val = Math.max(0, Math.min(max, val || 0));
            input.value = val;
            const c = loadCart();
            if (val > 0) c[pid] = val; else delete c[pid];
            saveCart(c);
            updateCartSummary();
        }
        minus.addEventListener('click', () => update(parseInt(input.value) - 1));
        plus.addEventListener('click', () => update(parseInt(input.value) + 1));
        input.addEventListener('input', () => update(parseInt(input.value)));
        input.addEventListener('blur', () => update(parseInt(input.value)));
    });
    cachePrices();
    updateCartSummary();
}

function cachePrices() {
    const priceCache = loadPriceCache();
    document.querySelectorAll('#productGrid .product-card').forEach(function (card) {
        const pid = card.querySelector('input[name="product_id[]"]').value;
        const price = parseFloat(card.querySelector('.p-price').dataset.price) || 0;
        priceCache[pid] = price;
    });
    savePriceCache(priceCache);
}

function updateCartSummary() {
    const cart = loadCart();
    let count = 0;
    for (const pid in cart) count += cart[pid];
    document.getElementById('cartCount').textContent = count;

    const priceCache = loadPriceCache();
    let total = 0;
    for (const pid in cart) {
        if (priceCache[pid]) total += cart[pid] * priceCache[pid];
    }
    document.getElementById('cartTotal').textContent = total.toLocaleString('en-IN', {maximumFractionDigits: 2});
}

// ---- Live Search (debounced, Amazon/Flipkart style) ----
const grid = document.getElementById('productGrid');
const searchBox = document.getElementById('searchBox');
const spinner = document.getElementById('searchSpinner');
let searchTimer = null;

function runSearch() {
    const q = searchBox.value.trim();
    spinner.style.display = 'inline';
    fetch('search_products.php?q=' + encodeURIComponent(q) + '&category=' + currentCategory)
        .then(r => r.json())
        .then(data => {
            spinner.style.display = 'none';
            if (!data.products || data.products.length === 0) {
                grid.innerHTML = '<div class="search-empty">😕 कोई Product नहीं मिला<br><small>कुछ और Try करें</small></div>';
                return;
            }
            grid.innerHTML = data.products.map(renderProductCard).join('');
            attachCardEvents();
        })
        .catch(() => { spinner.style.display = 'none'; });
}

searchBox.addEventListener('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(runSearch, 300); // debounce 300ms - feels instant, not laggy
});

// ---- Category chips (also goes through live search endpoint) ----
document.querySelectorAll('.filter-chip').forEach(function (chip) {
    chip.addEventListener('click', function () {
        document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        currentCategory = chip.dataset.cat;
        runSearch();
    });
});

// Initial setup for the PHP-rendered grid
attachCardEvents();

document.getElementById('shopForm').addEventListener('submit', function () {
    const cart = loadCart();
    for (const pid in cart) {
        const existing = document.querySelector('#productGrid .product-card input[value="' + pid + '"]');
        if (!existing) {
            const hiddenPid = document.createElement('input');
            hiddenPid.type = 'hidden';
            hiddenPid.name = 'product_id[]';
            hiddenPid.value = pid;
            this.appendChild(hiddenPid);

            const hiddenQty = document.createElement('input');
            hiddenQty.type = 'hidden';
            hiddenQty.name = 'quantity[]';
            hiddenQty.value = cart[pid];
            this.appendChild(hiddenQty);
        }
    }
    localStorage.removeItem(CART_KEY);
    localStorage.removeItem(PRICE_CACHE_KEY);
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
