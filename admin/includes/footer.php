<!-- VERSION: v2026-08-11-FINAL -->
        </div>
    </div>
</div>
<script src="../assets/js/script.js"></script>
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('../sw.js').catch(function () {});
}
let deferredPrompt;
window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    document.querySelectorAll('.pwa-install-btn').forEach(function (btn) {
        btn.style.display = 'inline-block';
        btn.addEventListener('click', function () {
            btn.style.display = 'none';
            deferredPrompt.prompt();
        });
    });
});
window.addEventListener('appinstalled', function () {
    document.querySelectorAll('.pwa-install-btn').forEach(function (btn) { btn.style.display = 'none'; });
});
</script>
</body>
</html>
