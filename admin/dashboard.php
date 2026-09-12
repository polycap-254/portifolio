<?php
/**
 * admin/dashboard.php
 * Overview: stats + most recent messages.
 */

require_once __DIR__ . '/../includes/auth.php';

$adminPageTitle = 'Dashboard';
$adminActiveNav = 'dashboard';

$pdo = getDB();

$counts = [
    'projects'   => (int)$pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn(),
    'education'  => (int)$pdo->query('SELECT COUNT(*) FROM education')->fetchColumn(),
    'skills'     => (int)$pdo->query('SELECT COUNT(*) FROM skills')->fetchColumn(),
    'experience' => (int)$pdo->query('SELECT COUNT(*) FROM experience')->fetchColumn(),
    'services'   => (int)$pdo->query('SELECT COUNT(*) FROM services')->fetchColumn(),
    'socials'    => (int)$pdo->query('SELECT COUNT(*) FROM social_links')->fetchColumn(),
    'unread'     => (int)$pdo->query("SELECT COUNT(*) FROM messages WHERE status = 'unread'")->fetchColumn(),
    'messages'   => (int)$pdo->query('SELECT COUNT(*) FROM messages')->fetchColumn(),
];

$recent = $pdo->query(
    'SELECT id, name, email, subject, status, created_at
     FROM messages ORDER BY created_at DESC LIMIT 8'
)->fetchAll();

$latestProjects = $pdo->query(
    'SELECT id, name, category, year, is_featured, is_published
     FROM projects ORDER BY created_at DESC LIMIT 5'
)->fetchAll();

require_once __DIR__ . '/partials/admin_header.php';
require_once __DIR__ . '/partials/admin_sidebar.php';
?>

<div class="admin-page-head">
    <div>
        <h1>Welcome back, <?= e(currentUser()['full_name'] ?? 'Admin') ?> 👋</h1>
        <p>Here's what's happening with your portfolio.</p>
    </div>
    <div class="actions">
        <a href="<?= url('/admin/projects.php') ?>" class="btn btn--primary btn--sm">
            <i class="fas fa-plus"></i> New Project
        </a>
        <a href="<?= url('/admin/messages.php') ?>" class="btn btn--outline btn--sm">
            <i class="fas fa-envelope"></i> Messages
            <?php if ($counts['unread']): ?><span class="admin-sidebar__badge" style="margin-left:.35rem;"><?= $counts['unread'] ?></span><?php endif; ?>
        </a>
    </div>
</div>

<!-- Stat cards -->
<div class="stat-grid">
    <div class="stat">
        <div class="stat__icon"><i class="fas fa-diagram-project"></i></div>
        <div class="stat__meta"><strong data-count="<?= $counts['projects'] ?>">0</strong><span>Projects</span></div>
    </div>
    <div class="stat">
        <div class="stat__icon"><i class="fas fa-code"></i></div>
        <div class="stat__meta"><strong data-count="<?= $counts['skills'] ?>">0</strong><span>Skills</span></div>
    </div>
    <div class="stat">
        <div class="stat__icon"><i class="fas fa-graduation-cap"></i></div>
        <div class="stat__meta"><strong data-count="<?= $counts['education'] ?>">0</strong><span>Education</span></div>
    </div>
    <div class="stat">
        <div class="stat__icon"><i class="fas fa-briefcase"></i></div>
        <div class="stat__meta"><strong data-count="<?= $counts['experience'] ?>">0</strong><span>Experience</span></div>
    </div>
    <div class="stat">
        <div class="stat__icon"><i class="fas fa-cogs"></i></div>
        <div class="stat__meta"><strong data-count="<?= $counts['services'] ?>">0</strong><span>Services</span></div>
    </div>
    <div class="stat">
        <div class="stat__icon"><i class="fas fa-envelope"></i></div>
        <div class="stat__meta"><strong data-count="<?= $counts['messages'] ?>">0</strong><span>Messages</span></div>
    </div>
</div>

<!-- Recent messages -->
<div class="panel">
    <div class="panel__head">
        <h2><i class="fas fa-inbox"></i> Recent Messages</h2>
        <a href="<?= url('/admin/messages.php') ?>" class="btn btn--ghost btn--sm">View all</a>
    </div>

    <?php if ($recent): ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>From</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Received</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $m): ?>
                        <tr>
                            <td>
                                <strong><?= e($m['name']) ?></strong><br>
                                <small class="muted"><?= e($m['email']) ?></small>
                            </td>
                            <td><?= e(excerpt($m['subject'], 60)) ?></td>
                            <td>
                                <?php if ($m['status'] === 'unread'): ?>
                                    <span class="badge" style="background:#ef4444;color:#fff;border-color:transparent;">Unread</span>
                                <?php else: ?>
                                    <span class="badge"><?= e(ucfirst($m['status'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><small class="muted"><?= e(formatDate($m['created_at'], 'd M Y H:i')) ?></small></td>
                            <td>
                                <a class="btn-icon" href="<?= url('/admin/messages.php?id=' . (int)$m['id']) ?>" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-envelope-open"></i>
            <p>No messages yet.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Latest projects -->
<div class="panel">
    <div class="panel__head">
        <h2><i class="fas fa-diagram-project"></i> Latest Projects</h2>
        <a href="<?= url('/admin/projects.php') ?>" class="btn btn--ghost btn--sm">Manage</a>
    </div>

    <?php if ($latestProjects): ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Year</th>
                        <th>Featured</th>
                        <th>Published</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latestProjects as $p): ?>
                        <tr>
                            <td><?= e($p['name']) ?></td>
                            <td><span class="chip"><?= e($p['category']) ?></span></td>
                            <td><?= e($p['year']) ?></td>
                            <td><?= $p['is_featured'] ? '<i class="fas fa-star" style="color:#f59e0b"></i>' : '<span class="muted">—</span>' ?></td>
                            <td><?= $p['is_published'] ? '<i class="fas fa-check-circle" style="color:#10b981"></i>' : '<span class="muted">Draft</span>' ?></td>
                            <td>
                                <a class="btn-icon" href="<?= url('/admin/projects.php?edit=' . (int)$p['id']) ?>">
                                    <i class="fas fa-pen"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-diagram-project"></i>
            <p>No projects yet. <a href="<?= url('/admin/projects.php') ?>">Add your first project →</a></p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/admin_footer.php'; ?>
