// ===== VERSION: v2026-08-11-FINAL ===== 
/**
 * FILE: assets/js/script.js
 * Shared JavaScript for Admin + Customer panels
 */

/**
 * Simple, reliable Theme toggle (Light <-> Dark only)
 * Auto-resets if a stored theme value is invalid/corrupted
 */
(function () {
    const saved = localStorage.getItem('theme');
    if (saved === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    } else {
        document.documentElement.removeAttribute('data-theme');
        localStorage.setItem('theme', 'light');
    }
})();

function toggleTheme() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    if (isDark) {
        document.documentElement.removeAttribute('data-theme');
        localStorage.setItem('theme', 'light');
    } else {
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('theme', 'dark');
    }
    document.querySelectorAll('.theme-toggle').forEach(function (btn) {
        btn.textContent = document.documentElement.getAttribute('data-theme') === 'dark' ? '☀️' : '🌙';
    });
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.theme-toggle').forEach(function (btn) {
        btn.textContent = document.documentElement.getAttribute('data-theme') === 'dark' ? '☀️' : '🌙';
        btn.addEventListener('click', toggleTheme);
    });

    // Sidebar toggle on mobile
    const toggleBtn = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });
    }

    // Auto-hide flash alerts after 4 seconds
    document.querySelectorAll('.alert').forEach(function (el) {
        setTimeout(function () { el.style.display = 'none'; }, 4000);
    });

    // Dismissible promo banners (closed banners stay hidden this session)
    document.querySelectorAll('.promo-banner').forEach(function (banner) {
        const id = banner.dataset.bannerId;
        if (id && sessionStorage.getItem('banner_closed_' + id) === '1') {
            banner.style.display = 'none';
            return;
        }
        const closeBtn = banner.querySelector('.promo-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                banner.style.transition = 'opacity .2s ease, transform .2s ease';
                banner.style.opacity = '0';
                banner.style.transform = 'scale(.97)';
                setTimeout(function () { banner.style.display = 'none'; }, 200);
                if (id) sessionStorage.setItem('banner_closed_' + id, '1');
            });
        }
    });

    // Simple auto-rotating banner carousel (if more than 1 slide)
    document.querySelectorAll('.promo-carousel').forEach(function (carousel) {
        const slides = carousel.querySelectorAll('.promo-banner');
        if (slides.length <= 1) return;
        let idx = 0;
        setInterval(function () {
            const visible = Array.from(slides).filter(s => s.style.display !== 'none');
            if (visible.length <= 1) return;
            visible[idx % visible.length].style.opacity = '0';
            idx = (idx + 1) % visible.length;
            visible.forEach((s, i) => s.style.display = i === idx ? '' : 'none');
            visible[idx].style.opacity = '1';
        }, 5000);
    });
});

// Confirm before delete actions
function confirmDelete(message) {
    return confirm(message || 'क्या आप वाकई इसे डिलीट करना चाहते हैं?');
}

// Preview multiple selected images before upload
function previewImages(input, previewContainerId) {
    const container = document.getElementById(previewContainerId);
    if (!container) return;
    container.innerHTML = '';
    if (input.files) {
        Array.from(input.files).forEach(function (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.width = '70px';
                img.style.height = '70px';
                img.style.objectFit = 'cover';
                img.style.borderRadius = '6px';
                img.style.marginRight = '8px';
                img.style.display = 'inline-block';
                container.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    }
}

// Simple client-side search/filter for tables
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;
    input.addEventListener('keyup', function () {
        const q = input.value.toLowerCase();
        table.querySelectorAll('tbody tr').forEach(function (row) {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}
