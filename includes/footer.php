<?php
/**
 * includes/footer.php
 * ------------------------------------------------------------
 * Site footer + closing tags + JS + flash toast rendering.
 * Auto-generates copyright year with PHP date().
 * Uses $profile (loaded by header.php).
 */

$profile    = $profile    ?? getProfile();
$socials    = getSocialLinks();
$year       = date('Y');
$footerName = $profile['full_name'] ?? APP_NAME;
$footerTag  = $profile['tagline']   ?? 'Building clean, functional web experiences.';
$footerMail = $profile['email']     ?? '';
$footerLoc  = $profile['location']  ?? '';

// Group nav quick links
$quickLinks = [
    ['label' => 'About',      'href' => '/#about'],
    ['label' => 'Education',  'href' => '/#education'],
    ['label' => 'Skills',     'href' => '/#skills'],
    ['label' => 'Projects',   'href' => '/projects.php'],
    ['label' => 'Experience', 'href' => '/#experience'],
    ['label' => 'Services',   'href' => '/#services'],
    ['label' => 'Contact',    'href' => '/#contact'],
];
?>
<footer class="site-footer">
    <div class="container footer__grid">

        <!-- Brand + tagline -->
        <div class="footer__col footer__col--brand">
            <a class="footer__brand" href="<?= url('/') ?>">
                <span class="footer__brand-mark" aria-hidden="true">
                    <?= e(mb_substr($footerName, 0, 1)) ?>
                </span>
                <span><?= e($footerName) ?></span>
            </a>
            <p class="footer__tagline"><?= e($footerTag) ?></p>

            <?php if ($socials): ?>
            <ul class="footer__socials" aria-label="Social links">
                <?php foreach ($socials as $s): ?>
                    <li>
                        <a href="<?= e($s['url']) ?>"
                           target="_blank" rel="noopener noreferrer"
                           aria-label="<?= e(ucfirst($s['platform'])) ?>"
                           title="<?= e(ucfirst($s['platform'])) ?>">
                            <i class="<?= e($s['icon_class'] ?: 'fas fa-link') ?>"></i>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

        <!-- Quick links -->
        <div class="footer__col">
            <h3 class="footer__heading">Quick Links</h3>
            <ul class="footer__links">
                <?php foreach ($quickLinks as $l): ?>
                    <li><a href="<?= url($l['href']) ?>"><?= e($l['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Services -->
        <div class="footer__col">
            <h3 class="footer__heading">What I Do</h3>
            <ul class="footer__links">
                <li>Website Development</li>
                <li>PHP &amp; MySQL Systems</li>
                <li>Database Design</li>
                <li>Computer Support</li>
                <li>IT Consultancy</li>
            </ul>
        </div>

        <!-- Contact -->
        <div class="footer__col">
            <h3 class="footer__heading">Get In Touch</h3>
            <ul class="footer__links footer__contact">
                <?php if ($footerMail): ?>
                    <li><i class="fas fa-envelope"></i> <a href="mailto:<?= e($footerMail) ?>"><?= e($footerMail) ?></a></li>
                <?php endif; ?>
                <?php if (!empty($profile['phone'])): ?>
                    <li><i class="fas fa-phone"></i> <a href="tel:<?= e(preg_replace('/\s+/', '', $profile['phone'])) ?>"><?= e($profile['phone']) ?></a></li>
                <?php endif; ?>
                <?php if ($footerLoc): ?>
                    <li><i class="fas fa-map-marker-alt"></i> <?= e($footerLoc) ?></li>
                <?php endif; ?>
            </ul>
            <a href="<?= url('/#contact') ?>" class="btn btn--outline btn--sm footer__cta">
                <i class="fas fa-paper-plane"></i> Send a Message
            </a>
        </div>
    </div>

    <div class="footer__bottom">
        <div class="container footer__bottom-inner">
            <p>&copy; <?= e($year) ?> <?= e($footerName) ?>. All Rights Reserved.</p>
            <p class="footer__built">Built with PHP, MySQL &amp; Vanilla JavaScript.</p>
        </div>
    </div>
</footer>

<!-- Back to top -->
<button type="button" class="back-to-top" id="backToTop" aria-label="Back to top">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Toast container (JS injects toasts here) -->
<div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>

<!-- Render PHP flash messages as toasts on load -->
<?php $flashes = getFlash(); if (!empty($flashes)): ?>
<script>
window.__FLASH__ = <?= json_encode($flashes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<?php endif; ?>

<script src="<?= asset('js/script.js') ?>" defer></script>
</body>
</html>
