<?php
/**
 * admin/messages.php
 * ------------------------------------------------------------
 * Contact form inbox.
 * - List:   default (with status filter tabs)
 * - Detail: ?id=N  (auto-marks as read)
 * - Status: POST (form_action=status&status=…)
 * - Delete: POST (form_action=delete)
 */

require_once __DIR__ . '/../includes/auth.php';

$adminPageTitle = 'Messages';
$adminActiveNav = 'messages';

$pdo    = getDB();
$id     = (int)($_GET['id'] ?? 0);
$errors = [];

const STATUSES = ['unread', 'read', 'replied', 'archived'];

/* =========================================================
   POST — CHANGE STATUS
   ========================================================= */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['form_action'] ?? '') === 'status') {
    if (!verifyCsrf()) {
        setFlash('error', 'Security check failed.');
    } else {
        $msgId  = (int)($_POST['id'] ?? 0);
        $status = clean($_POST['status'] ?? '');
        if ($msgId > 0 && in_array($status, STATUSES, true)) {
            try {
                $pdo->prepare('UPDATE messages SET status = :s WHERE id = :id')
                    ->execute([':s' => $status, ':id' => $msgId]);
                setFlash('success', 'Message marked as ' . $status . '.');
            } catch (Throwable $e) {
                setFlash('error', 'Could not update message.');
            }
        } else {
            setFlash('error', 'Invalid status.');
        }
    }
    // Redirect back to where they came from
    $redirect = clean($_POST['redirect'] ?? '/admin/messages.php');
    redirect(url($redirect));
}

/* =========================================================
   POST — DELETE
   ========================================================= */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['form_action'] ?? '') === 'delete') {
    if (!verifyCsrf()) {
        setFlash('error', 'Security check failed.');
    } else {
        $msgId = (int)($_POST['id'] ?? 0);
        if ($msgId > 0) {
            try {
                $pdo->prepare('DELETE FROM messages WHERE id = :id')->execute([':id' => $msgId]);
                setFlash('success', 'Message deleted.');
            } catch (Throwable $e) {
                setFlash('error', 'Could not delete message.');
            }
        }
    }
    redirect(url('/admin/messages.php'));
}

/* =========================================================
   LOAD — counts for the filter tabs
   ========================================================= */
$counts = ['all' => 0];
foreach (STATUSES as $s) $counts[$s] = 0;

foreach ($pdo->query('SELECT status, COUNT(*) AS c FROM messages GROUP BY status')->fetchAll() as $row) {
    $counts[$row['status']] = (int)$row['c'];
    $counts['all'] += (int)$row['c'];
}

/* =========================================================
   DETAIL VIEW
   ========================================================= */
$current = null;
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM messages WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $current = $stmt->fetch() ?: null;

    if (!$current) {
        setFlash('error', 'Message not found.');
        redirect(url('/admin/messages.php'));
    }

    // Auto-mark as read when opened (if it was unread)
    if ($current['status'] === 'unread') {
        $pdo->prepare("UPDATE messages SET status = 'read' WHERE id = :id")
            ->execute([':id' => $id]);
        $current['status'] = 'read';
        // decrement unread counter for this render
        $counts['unread'] = max(0, $counts['unread'] - 1);
        $counts['read']   = $counts['read'] + 1;
    }
}

/* =========================================================
   LIST VIEW
   ========================================================= */
$filter = clean($_GET['status'] ?? 'all');
if ($filter !== 'all' && !in_array($filter, STATUSES, true)) {
    $filter = 'all';
}

$listRows = [];
if (!$current) {
    if ($filter === 'all') {
        $listRows = $pdo->query(
            'SELECT id, name, email, subject, status, created_at
             FROM messages ORDER BY created_at DESC'
        )->fetchAll();
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, name, email, subject, status, created_at
             FROM messages WHERE status = :s ORDER BY created_at DESC'
        );
        $stmt->execute([':s' => $filter]);
        $listRows = $stmt->fetchAll();
    }
}

require_once __DIR__ . '/partials/admin_header.php';
require_once __DIR__ . '/partials/admin_sidebar.php';
?>

<div class="admin-page-head">
    <div>
        <h1><i class="fas fa-envelope"></i> Messages</h1>
        <p>
            <?php if ($current): ?>
                Viewing message #<?= (int)$current['id'] ?>
            <?php else: ?>
                Contact form submissions from your portfolio.
            <?php endif; ?>
        </p>
    </div>
    <div class="actions">
        <?php if ($current): ?>
            <a href="<?= url('/admin/messages.php') ?>" class="btn btn--ghost btn--sm">
                <i class="fas fa-arrow-left"></i> Back to inbox
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($errors): ?>
    <div class="empty-state" style="border-color:#ef4444;color:#ef4444;text-align:left;padding:1rem;margin-bottom:1rem;">
        <?php foreach ($errors as $err): ?><div><i class="fas fa-circle-exclamation"></i> <?= e($err) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($current): ?>

    <!-- ============== DETAIL VIEW ============== -->
    <div class="panel">
        <div class="panel__head">
            <h2><?= e($current['subject']) ?></h2>
            <?php
              $statusColors = [
                'unread'   => 'background:#ef4444;color:#fff;border-color:transparent;',
                'read'     => 'background:#3b82f6;color:#fff;border-color:transparent;',
                'replied'  => 'background:#10b981;color:#fff;border-color:transparent;',
                'archived' => 'background:#64748b;color:#fff;border-color:transparent;',
              ];
            ?>
            <span class="badge" style="<?= $statusColors[$current['status']] ?? '' ?>">
                <?= e(ucfirst($current['status'])) ?>
            </span>
        </div>

        <div class="admin-form">
            <div class="grid-2">
                <div class="form-group">
                    <label>From</label>
                    <div style="font-weight:600;"><?= e($current['name']) ?></div>
                    <a href="mailto:<?= e($current['email']) ?>" class="muted" style="font-size:.9rem;">
                        <?= e($current['email']) ?>
                    </a>
                </div>
                <div class="form-group">
                    <label>Received</label>
                    <div><?= e(formatDate($current['created_at'], 'd M Y, H:i')) ?></div>
                    <?php if (!empty($current['ip_address'])): ?>
                        <div class="muted mono" style="font-size:.8rem;">IP: <?= e($current['ip_address']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Message</label>
                <div style="padding:1rem 1.2rem;background:var(--bg-soft);border-radius:var(--radius);border:1px solid var(--border);white-space:pre-wrap;font-size:.95rem;line-height:1.7;">
<?= e($current['message']) ?>
                </div>
            </div>
        </div>

        <div class="admin-form-actions" style="flex-wrap:wrap;">
            <?php
              $replySubject = 'Re: ' . $current['subject'];
              $replyMailto  = 'mailto:' . $current['email'] . '?subject=' . rawurlencode($replySubject);
            ?>
            <a href="<?= e($replyMailto) ?>" class="btn btn--primary btn--sm">
                <i class="fas fa-reply"></i> Reply by Email
            </a>

            <?php
              // Quick status buttons (only show ones that differ from current)
              $actions = [
                'unread'   => ['label' => 'Mark Unread',   'icon' => 'fa-envelope'],
                'read'     => ['label' => 'Mark Read',     'icon' => 'fa-envelope-open'],
                'replied'  => ['label' => 'Mark Replied',  'icon' => 'fa-reply'],
                'archived' => ['label' => 'Archive',       'icon' => 'fa-box-archive'],
              ];
              foreach ($actions as $key => $a):
                if ($key === $current['status']) continue;
            ?>
                <form method="post" action="<?= url('/admin/messages.php') ?>" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="form_action" value="status">
                    <input type="hidden" name="id" value="<?= (int)$current['id'] ?>">
                    <input type="hidden" name="status" value="<?= e($key) ?>">
                    <input type="hidden" name="redirect" value="/admin/messages.php?id=<?= (int)$current['id'] ?>">
                    <button type="submit" class="btn btn--ghost btn--sm">
                        <i class="fas <?= e($a['icon']) ?>"></i> <?= e($a['label']) ?>
                    </button>
                </form>
            <?php endforeach; ?>

            <form method="post"
                  action="<?= url('/admin/messages.php') ?>"
                  style="display:inline;margin-left:auto;"
                  onsubmit="return confirm('Permanently delete this message?');">
                <?= csrfField() ?>
                <input type="hidden" name="form_action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$current['id'] ?>">
                <button type="submit" class="btn btn--danger btn--sm">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </form>
        </div>
    </div>

<?php else: ?>

    <!-- ============== LIST VIEW ============== -->
    <div class="panel">
        <div class="panel__head">
            <h2>
                Inbox
                <span class="muted" style="font-weight:400;font-size:.85rem;">(<?= $counts['all'] ?>)</span>
            </h2>
            <div class="projects__filters" style="margin:0;">
                <?php
                  $tabs = [
                    'all'      => 'All',
                    'unread'   => 'Unread',
                    'read'     => 'Read',
                    'replied'  => 'Replied',
                    'archived' => 'Archived',
                  ];
                  foreach ($tabs as $key => $label):
                    $cnt = $counts[$key] ?? 0;
                ?>
                    <a class="tab<?= $filter === $key ? ' is-active' : '' ?>"
                       href="<?= url('/admin/messages.php' . ($key !== 'all' ? '?status=' . $key : '')) ?>">
                        <?= e($label) ?>
                        <?php if ($cnt > 0): ?>
                            <span class="muted">(<?= $cnt ?>)</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($listRows): ?>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width:40px;"></th>
                            <th>From</th>
                            <th>Subject</th>
                            <th style="width:110px;">Status</th>
                            <th style="width:150px;">Received</th>
                            <th style="width:130px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listRows as $m):
                            $isUnread = $m['status'] === 'unread';
                        ?>
                            <tr style="<?= $isUnread ? 'font-weight:600;' : '' ?>">
                                <td>
                                    <i class="fas <?= $isUnread ? 'fa-circle' : 'fa-circle-check' ?>"
                                       style="color:<?= $isUnread ? '#ef4444' : 'var(--muted)' ?>;font-size:.7rem;"></i>
                                </td>
                                <td>
                                    <strong><?= e($m['name']) ?></strong><br>
                                    <small class="muted" style="font-weight:400;"><?= e($m['email']) ?></small>
                                </td>
                                <td><?= e(excerpt($m['subject'], 70)) ?></td>
                                <td>
                                    <?php
                                      $colors = [
                                        'unread'   => 'background:#ef4444;color:#fff;border-color:transparent;',
                                        'read'     => 'background:#3b82f6;color:#fff;border-color:transparent;',
                                        'replied'  => 'background:#10b981;color:#fff;border-color:transparent;',
                                        'archived' => 'background:#64748b;color:#fff;border-color:transparent;',
                                      ];
                                    ?>
                                    <span class="badge" style="<?= $colors[$m['status']] ?? '' ?>;font-weight:600;">
                                        <?= e(ucfirst($m['status'])) ?>
                                    </span>
                                </td>
                                <td><small class="muted"><?= e(formatDate($m['created_at'], 'd M Y H:i')) ?></small></td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn-icon"
                                           href="<?= url('/admin/messages.php?id=' . (int)$m['id']) ?>"
                                           title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="post"
                                              action="<?= url('/admin/messages.php') ?>"
                                              style="display:inline"
                                              onsubmit="return confirm('Permanently delete this message?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                            <button type="submit" class="btn-icon btn-icon--danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>
                    <?php if ($filter === 'all'): ?>
                        No messages yet. When someone uses your contact form, it will appear here.
                    <?php else: ?>
                        No <strong><?= e($filter) ?></strong> messages.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/partials/admin_footer.php'; ?>
