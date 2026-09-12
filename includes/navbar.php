<?php
/**
 * includes/navbar.php
 * ------------------------------------------------------------
 * Sticky, responsive navigation bar.
 *  - Logo (from profile name)
 *  - Desktop links with active highlighting
 *  - Theme toggle
 *  - Hamburger menu (mobile)
 * Expects: $profile already loaded by header.php
 */

$current = currentPath();
$navItems = [
    ['label' => 'Home',       'href' => '/'],
    ['label' => 'About',      'href' => '/#about'],
    ['label' => 'Education',  'href' => '/#education'],
    ['label' => 'Skills',     'href' => '/#skills'],
    ['label' => 'Projects',   'href' => '/#projects'],
    ['label' => 'Experience', 'href' => '/#experience'],
    ['label' => 'Services',   'href' => '/#services'],
    ['label' => 'Contact',    'href' => '/#contact'],
];

// Name for logo
$logoName = $profile['full_name'] ?? APP_NAME;
// Initials
$initials = '';
foreach (preg_split('/\s+/', trim($logoName)) as $w) {
    if ($w !== '') $initials .= mb_strtoupper(mb_substr($w, 0, 1));
    if (mb_strlen($initials) >= 2) break;
}
?>
<header class="site-header" id="siteHeader">
    <nav class="navbar container" aria-label="Primary">
        <!-- Logo -->
        <a class="navbar__brand" href="<?= url('/') ?>#home" aria-label="Home">
            <span class="navbar__brand-mark" aria-hidden="true"><?= e($initials ?: 'P') ?></span>
            <span class="navbar__brand-text"><?= e($logoName) ?></span>
        </a>

        <!-- Desktop links -->
        <ul class="navbar__links" id="primaryNav">
            <?php foreach ($navItems as $item):
                // Highlight if path matches (ignoring hash)
                $itemPath = parse_url($item['href'], PHP_URL_PATH) ?: '/';
                $isActive = ($itemPath === $current)
                         || ($itemPath === '/' && $current === '/');
            ?>
                <li>
                    <a href="<?= url($item['href']) ?>"
                       class="navbar__link<?= $isActive ? ' is-active' : '' ?>"
                       data-nav-link><?= e($item['label']) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Right actions -->
        <div class="navbar__actions">
            <button type="button"
                    class="icon-btn theme-toggle"
                    id="themeToggle"
                    aria-label="Toggle dark mode"
                    title="Toggle dark mode">
                <i class="fas fa-moon" data-theme-icon></i>
            </button>

            <a href="<?= url('/#contact') ?>" class="btn btn--primary btn--sm navbar__cta">
                Hire Me
            </a>

            <button type="button"
                    class="icon-btn navbar__burger"
                    id="navBurger"
                    aria-label="Open menu"
                    aria-expanded="false"
                    aria-controls="primaryNav">
                <span class="burger-bar"></span>
                <span class="burger-bar"></span>
                <span class="burger-bar"></span>
            </button>
        </div>
    </nav>
</header>

<!-- Mobile menu overlay -->
<div class="mobile-nav" id="mobileNav" aria-hidden="true">
    <div class="mobile-nav__backdrop" data-close-mobile-nav></div>
    <aside class="mobile-nav__panel" role="dialog" aria-modal="true" aria-label="Mobile navigation">
        <div class="mobile-nav__head">
            <span class="mobile-nav__title">Menu</span>
            <button type="button" class="icon-btn" data-close-mobile-nav aria-label="Close menu">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <ul class="mobile-nav__list">
            <?php foreach ($navItems as $item): ?>
                <li>
                    <a href="<?= url($item['href']) ?>" data-close-mobile-nav>
                        <?= e($item['label']) ?>
                        <i class="fas fa-chevron-right" aria-hidden="true"></i>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="mobile-nav__foot">
            <a href="<?= url('/#contact') ?>" class="btn btn--primary btn--block" data-close-mobile-nav>
                <i class="fas fa-paper-plane"></i> Contact Me
            </a>
        </div>
    </aside>
</div>
