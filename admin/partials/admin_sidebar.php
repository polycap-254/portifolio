<?php
/**
 * admin/partials/admin_sidebar.php
 * Left navigation for the admin area.
 * Expects: $adminActiveNav (from admin_header.php)
 */
$items = [
    ['key' => 'dashboard',  'href' => '/admin/dashboard.php',  'icon' => 'fa-gauge-high',     'label' => 'Dashboard'],
    ['key' => 'profile',    'href' => '/admin/profile.php',    'icon' => 'fa-id-badge',       'label' => 'Profile'],
    ['key' => 'education',  'href' => '/admin/education.php',  'icon' => 'fa-graduation-cap', 'label' => 'Education'],
    ['key' => 'skills',     'href' => '/admin/skills.php',     'icon' => 'fa-code',           'label' => 'Skills'],
    ['key' => 'projects',   'href' => '/admin/projects.php',   'icon' => 'fa-diagram-project','label' => 'Projects'],
    ['key' => 'experience', 'href' => '/admin/experience.php', 'icon' => 'fa-briefcase',      'label' => 'Experience'],
    ['key' => 'services',   'href' => '/admin/services.php',   'icon' => 'fa-cogs',           'label' => 'Services'],
    ['key' => 'socials',    'href' => '/admin/social_links.php','icon' => 'fa-share-nodes',   'label' => 'Social Links'],
    ['key' => 'messages',   'href' => '/admin/messages.php',   'icon' => 'fa-envelope',       'label' => 'Messages', 'badge' => $unreadCount ?? 0],
];
?>
<aside class="admin-sidebar" id="adminSidebar" aria-label="Admin navigation">
    <nav class="admin-sidebar__nav">
        <?php foreach ($items as $it):
            $isActive = $adminActiveNav === $it['key'];
        ?>
            <a class="admin-sidebar__link<?= $isActive ? ' is-active' : '' ?>"
               href="<?= url($it['href']) ?>">
                <i class="fas <?= e($it['icon']) ?>"></i>
                <span><?= e($it['label']) ?></span>
                <?php if (!empty($it['badge'])): ?>
                    <span class="admin-sidebar__badge"><?= (int)$it['badge'] ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="admin-sidebar__foot">
        <a class="admin-sidebar__link admin-sidebar__link--danger"
           href="<?= url('/admin/logout.php') ?>"
           data-confirm="Log out of the admin area?">
            <i class="fas fa-right-from-bracket"></i>
            <span>Log Out</span>
        </a>
    </div>
</aside>

<main class="admin-main">
