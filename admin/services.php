<?php
/**
 * admin/services.php
 * ------------------------------------------------------------
 * CRUD for `services` table.
 * - List:   default
 * - Add:    ?action=new
 * - Edit:   ?action=edit&id=N
 * - Save:   POST (INSERT or UPDATE)
 * - Delete: POST (?action=delete&id=N)
 */

require_once __DIR__ . '/../includes/auth.php';

$adminPageTitle = 'Services';
$adminActiveNav = 'services';

$pdo    = getDB();
$action = clean($_GET['action'] ?? 'list');
$id     = (int)($_GET['id'] ?? 0);
$errors = [];

/* =========================================================
   POST — SAVE
   ========================================================= */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['form_action'] ?? '') === 'save') {
    if (!verifyCsrf()) {
        $errors[] = 'Security check failed.';
    } else {
        $editId = (int)($_POST['id'] ?? 0);
        $data = [
            'title'         => clean($_POST['title'] ?? ''),
            'description'   => cleanText($_POST['description'] ?? ''),
            'icon_class'    => clean($_POST['icon_class'] ?? 'fas fa-cogs'),
            'display_order' => (int)($_POST['display_order'] ?? 0),
            'is_active'     => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($data['title'] === '') $errors[] = 'Service title is required.';
        if (mb_strlen($data['title']) > 120) $errors[] = 'Title is too long (max 120 chars).';

        if (!$errors) {
            try {
                if ($editId > 0) {
                    $stmt = $pdo->prepare(
                        'UPDATE services SET
                            title = :title,
                            description = :description,
                            icon_class = :icon_class,
                            display_order = :display_order,
                            is_active = :is_active
                         WHERE id = :id'
                    );
                    $stmt->execute([
                        ':title'         => $data['title'],
                        ':description'   => $data['description'],
                        ':icon_class'    => $data['icon_class'],
                        ':display_order' => $data['display_order'],
                        ':is_active'     => $data['is_active'],
                        ':id'            => $editId,
                    ]);
                    setFlash('success', 'Service updated.');
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO services
                          (title, description, icon_class, display_order, is_active)
                         VALUES
                          (:title, :description, :icon_class, :display_order, :is_active)'
                    );
                    $stmt->execute([
                        ':title'         => $data['title'],
                        ':description'   => $data['description'],
                        ':icon_class'    => $data['icon_class'],
                        ':display_order' => $data['display_order'],
                        ':is_active'     => $data['is_active'],
                    ]);
                    setFlash('success', 'Service added.');
                }
                clearOld();
                redirect(url('/admin/services.php'));
            } catch (Throwable $e) {
                $errors[] = APP_ENV === 'development'
                    ? 'DB error: ' . $e->getMessage()
                    : 'Could not save the service.';
            }
        }
    }
}

/* =========================================================
   POST — DELETE
   ========================================================= */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['form_action'] ?? '') === 'delete') {
    if (!verifyCsrf()) {
        setFlash('error', 'Security check failed.');
    } else {
        $deleteId = (int)($_POST['id'] ?? 0);
        if ($deleteId > 0) {
            try {
                $pdo->prepare('DELETE FROM services WHERE id = :id')->execute([':id' => $deleteId]);
                setFlash('success', 'Service deleted.');
            } catch (Throwable $e) {
                setFlash('error', 'Could not delete service.');
            }
        }
    }
    redirect(url('/admin/services.php'));
}

/* =========================================================
   POST — QUICK TOGGLE ACTIVE
   ========================================================= */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['form_action'] ?? '') === 'toggle') {
    if (!verifyCsrf()) {
        setFlash('error', 'Security check failed.');
    } else {
        $toggleId = (int)($_POST['id'] ?? 0);
        if ($toggleId > 0) {
            try {
                $pdo->prepare('UPDATE services SET is_active = 1 - is_active WHERE id = :id')
                    ->execute([':id' => $toggleId]);
                setFlash('success', 'Service status updated.');
            } catch (Throwable $e) {
                setFlash('error', 'Could not update status.');
            }
        }
    }
    redirect(url('/admin/services.php'));
}

/* =========================================================
   LOAD
   ========================================================= */
$rows = $pdo->query('SELECT * FROM services ORDER BY display_order, id')->fetchAll();

$editing = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM services WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $editing = $stmt->fetch() ?: null;
    if (!$editing) {
        setFlash('error', 'Service not found.');
        redirect(url('/admin/services.php'));
    }
}

// Quick-pick icons
$iconSuggestions = [
    ['fas fa-globe',         'Website'],
    ['fas fa-id-card',       'Portfolio'],
    ['fas fa-school',        'School Sys'],
    ['fas fa-database',      'Database'],
    ['fas fa-server',        'Backend'],
    ['fas fa-laptop-code',   'Frontend'],
    ['fas fa-tools',         'Support'],
    ['fas fa-headset',       'IT Help'],
    ['fas fa-sync-alt',      'Maintenance'],
    ['fas fa-lightbulb',     'Consulting'],
    ['fas fa-mobile-alt',    'Mobile'],
    ['fas fa-shield-alt',    'Security'],
    ['fas fa-code',          'Code'],
    ['fas fa-cloud',         'Cloud'],
    ['fas fa-rocket',        'Launch'],
    ['fas fa-chart-line',    'Analytics'],
];

require_once __DIR__ . '/partials/admin_header.php';
require_once __DIR__ . '/partials/admin_sidebar.php';
?>

<div class="admin-page-head">
    <div>
        <h1><i class="fas fa-cogs"></i> Services</h1>
        <p>Services you offer on the public site.</p>
    </div>
    <div class="actions">
        <?php if ($action === 'list'): ?>
            <a href="<?= url('/admin/services.php?action=new') ?>" class="btn btn--primary btn--sm">
                <i class="fas fa-plus"></i> New Service
            </a>
        <?php else: ?>
            <a href="<?= url('/admin/services.php') ?>" class="btn btn--ghost btn--sm">
                <i class="fas fa-list"></i> Back to list
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($errors): ?>
    <div class="empty-state" style="border-color:#ef4444;color:#ef4444;text-align:left;padding:1rem;margin-bottom:1rem;">
        <?php foreach ($errors as $err): ?>
            <div><i class="fas fa-circle-exclamation"></i> <?= e($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($action === 'new' || $action === 'edit'): ?>

    <!-- ============== FORM ============== -->
    <?php
      $val = function (string $key, $default = '') use ($editing) {
          if (old($key) !== '') return old($key);
          return $editing[$key] ?? $default;
      };
      $curIcon = $val('icon_class', 'fas fa-cogs');
    ?>
    <form method="post" class="panel">
        <?= csrfField() ?>
        <input type="hidden" name="form_action" value="save">
        <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">

        <div class="panel__head">
            <h2><?= $action === 'edit' ? 'Edit' : 'New' ?> Service</h2>
        </div>

        <div class="admin-form">
            <div class="grid-2">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input class="form-control" type="text" id="title" name="title"
                           value="<?= e($val('title')) ?>" required maxlength="120">
                </div>
                <div class="form-group" style="max-width:220px">
                    <label for="display_order">Display Order</label>
                    <input class="form-control" type="number" id="display_order" name="display_order"
                           value="<?= e($val('display_order', '0')) ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?= e($val('description')) ?></textarea>
            </div>

            <div class="form-group">
                <label for="icon_class">Icon</label>
                <div class="grid-2" style="align-items:center;">
                    <div style="display:flex;align-items:center;gap:1rem;">
                        <div class="img-preview__thumb" id="iconPreview" style="font-size:1.6rem;color:var(--brand-2);">
                            <i class="<?= e($curIcon) ?>"></i>
                        </div>
                        <input class="form-control" type="text" id="icon_class" name="icon_class"
                               value="<?= e($curIcon) ?>" placeholder="fas fa-cogs"
                               oninput="document.querySelector('#iconPreview i').className = this.value;">
                    </div>
                </div>
                <div class="hint">Click a suggestion below to fill the field. Font Awesome class names.</div>

                <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.6rem;">
                    <?php foreach ($iconSuggestions as [$cls, $label]): ?>
                        <button type="button" class="chip icon-pick"
                                data-icon="<?= e($cls) ?>"
                                title="<?= e($label) ?>"
                                style="cursor:pointer;border:1px solid var(--border);padding:.35rem .65rem;">
                            <i class="<?= e($cls) ?>"></i> <span><?= e($label) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Status</label>
                <label style="display:flex;align-items:center;gap:.5rem;font-weight:500;color:var(--text);">
                    <input type="checkbox" name="is_active" value="1"
                           <?= ($action === 'new' || (int)$val('is_active', 1)) ? 'checked' : '' ?>>
                    Show on public site
                </label>
            </div>
        </div>

        <div class="admin-form-actions">
            <button type="submit" class="btn btn--primary">
                <i class="fas fa-save"></i> <?= $action === 'edit' ? 'Update' : 'Create' ?>
            </button>
            <a href="<?= url('/admin/services.php') ?>" class="btn btn--ghost">Cancel</a>
        </div>
    </form>

    <script>
    document.querySelectorAll('.icon-pick').forEach(btn => {
        btn.addEventListener('click', () => {
            const cls = btn.dataset.icon;
            const input = document.getElementById('icon_class');
            input.value = cls;
            document.querySelector('#iconPreview i').className = cls;
        });
    });
    </script>

<?php else: ?>

    <!-- ============== LIST ============== -->
    <div class="panel">
        <div class="panel__head">
            <h2>
                All Services
                <span class="muted" style="font-weight:400;font-size:.85rem;">(<?= count($rows) ?>)</span>
            </h2>
        </div>

        <?php if ($rows): ?>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width:50px;">Icon</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th style="width:90px;">Status</th>
                            <th style="width:70px;">Order</th>
                            <th style="width:130px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td>
                                    <div style="width:38px;height:38px;border-radius:10px;display:grid;place-items:center;background:color-mix(in srgb,var(--brand-1) 12%, transparent);color:var(--brand-2);">
                                        <i class="<?= e($r['icon_class'] ?: 'fas fa-cogs') ?>"></i>
                                    </div>
                                </td>
                                <td><strong><?= e($r['title']) ?></strong></td>
                                <td><small class="muted"><?= e(excerpt($r['description'] ?? '', 90)) ?></small></td>
                                <td>
                                    <?php if ((int)$r['is_active']): ?>
                                        <span class="badge" style="background:#10b981;color:#fff;border-color:transparent;">Active</span>
                                    <?php else: ?>
                                        <span class="badge">Hidden</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int)$r['display_order'] ?></td>
                                <td>
                                    <div class="row-actions">
                                        <form method="post" action="<?= url('/admin/services.php') ?>" style="display:inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="form_action" value="toggle">
                                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                            <button type="submit" class="btn-icon" title="<?= $r['is_active'] ? 'Hide' : 'Activate' ?>">
                                                <i class="fas <?= $r['is_active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                            </button>
                                        </form>
                                        <a class="btn-icon"
                                           href="<?= url('/admin/services.php?action=edit&id=' . (int)$r['id']) ?>"
                                           title="Edit">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <form method="post"
                                              action="<?= url('/admin/services.php') ?>"
                                              style="display:inline"
                                              onsubmit="return confirm('Delete this service?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
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
                <i class="fas fa-cogs"></i>
                <p>No services yet.</p>
                <a href="<?= url('/admin/services.php?action=new') ?>" class="btn btn--primary btn--sm">
                    <i class="fas fa-plus"></i> Add your first service
                </a>
            </div>
        <?php endif; ?>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/partials/admin_footer.php'; ?>
