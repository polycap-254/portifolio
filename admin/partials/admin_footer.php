</main><!-- /.admin-main -->
</div><!-- /.admin-shell -->

<div class="toast-stack" id="toastStack" aria-live="polite"></div>

<?php $flashes = getFlash(); if (!empty($flashes)): ?>
<script>
window.__FLASH__ = <?= json_encode($flashes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<?php endif; ?>

<script src="<?= asset('js/script.js') ?>" defer></script>
</body>
</html>
