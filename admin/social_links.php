<?php
/**
 * admin/social_links.php
 * ------------------------------------------------------------
 * CRUD for `social_links` table.
 * - List:   default
 * - Add:    ?action=new
 * - Edit:   ?action=edit&id=N
 * - Save:   POST (INSERT or UPDATE)
 * - Delete: POST (?action=delete&id=N)
 * - Toggle: POST (?action=toggle&id=N)
 */

require_once __DIR__ . '/../includes/auth.php';

$adminPageTitle = 'Social Links';
$adminActiveNav = 'socials';

$pdo    = getDB();
$action = clean($_GET['action'] ?? 'list');
$id     = (int)($_GET['id'] ?? 0);
$errors = [];

// Preset platforms (used by the "Presets" quick-fill buttons)
$presets = [
    'github'   => ['label' => 'GitHub',    'icon' => 'fab fa-github',    'placeholder' => 'https://github.com/yourusername'],
    'linkedin' => ['label' => 'LinkedIn',  'icon' => 'fab fa-linkedin',  'placeholder' => 'https://linkedin.com/in/yourusername'],
    'facebook' => ['label' => 'Facebook',  'icon' => 'fab fa-facebook',  'placeholder' => 'https://facebook.com/yourusername'],
    'twitter'  => ['label' => 'X/Twitter', 'icon' => 'fab fa-x-twitter', 'placeholder' => 'https://x.com/yourusername'],
    'whatsapp' => ['label' => 'WhatsApp',  'icon' => 'fab fa-whatsapp',  'placeholder' => 'https://wa.me/2547XXXXXXXX'],
    'email'    => ['label' => 'Email',     'icon' => 'fas fa-envelope',  'placeholder' => 'mailto:you@example.com'],
    'youtube'  => ['label' => 'YouTube',   'icon' => 'fab fa-youtube',   'placeholder' => 'https://youtube.com/@yourhandle'],
    'instagram'=> ['label' => 'Instagram', 'icon' => 'fab fa-instagram', 'placeholder' => 'https://instagram.com/yourusername'],
    'telegram' => ['label' => 'Telegram',  'icon' => 'fab fa-telegram',  'placeholder' => 'https://t.me/yourusername'],
    'website'  => ['label' => 'Website',   'icon' => 'fas fa-globe',     'placeholder' => 'https://yourdomain.com'],
];

/* =========================================================
   POST — SAVE
   ========================================================= */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['form_action'] ?? '') === 'save') {
    if (!verifyCsrf()) {
        $errors[] = 'Security check failed.';
    } else {
        $editId = (int)($_POST['id'] ?? 0);
        $data = [
            'platform'      => strtolower(clean($_POST['platform'] ?? '')),
            'url'           => clean($_POST['url'] ?? ''),
            'icon_class'    => clean($_POST['icon_class'] ?? 'fas fa-link'),
            'display_order' => (int)($_POST['display_order'] ?? 0),
            'is_active'     => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($data['platform'] === '') $errors[] = 'Platform name is required.';
        if (mb_strlen($data['platform']) > 50) $errors[] = 'Platform name is too long.';
        if ($data['url'] === '') $errors[] = 'URL is required.';

        // URLs may be http(s):// or mailto: or tel: or # or "/path"
        if ($data['url'] !== '') {
            $ok = filter_var($data['url'], FILTER_VALIDATE_URL)
                || str_starts_with($data['url'], 'mailto:')
                || str_starts_with($data['url'], 'tel:')
                || str_starts_with($data['url'], '/');
            if (!$ok) $errors[] = 'URL must be a valid link (https://… or mailto:… or /path).';
        }

        if (!$errors) {
            try {
                if ($editId > 0) {
                    $stmt = $pdo->prepare(
                        'UPDATE social_links SET
                            platform = :platform,
                            url = :url,
                            icon_class = :icon_class,
                            display_order = :display_order,
                            is_active = :is_active
                         WHERE id = :id'
                    );
                    $stmt->execute([
                        ':platform'      => $data['platform'],
                        ':url'           => $data['url'],
                        ':icon_class'    => $data['icon_class'],
                        ':display_order' => $data['display_order'],
                        ':is_active'     => $data['is_active'],
                        ':id'            => $editId,
                    ]);
                    setFlash('success', 'Social link updated.');
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO social_links
                          (platform, url, icon_class, display_order, is_active)
                         VALUES
                          (:platform, :url, :icon_class, :display_order, :is_active)'
                    );
                    $stmt->execute([
                        ':platform'      => $data['platform'],
                        ':url'           => $data['url'],
                        ':icon_class'    => $data['icon_class'],
                        ':display_order' => $data['display_order'],
                        ':is_active'     => $data['is_active'],
                    ]);
                    setFlash('success', 'Social link added.');
                }
                clearOld();
                redirect(url('/admin/social_links.php'));
            } catch (Throwable $e) {
                $errors[] = APP_ENV === 'development'
                    ? 'DB error: ' . $e->getMessage()
                    : 'Could not save the link.';
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
                $pdo->prepare('DELETE FROM social_links WHERE id = :id')->execute([':id' => $deleteId]);
                setFlash('success', 'Social link deleted.');
            } catch (Throwable $e) {
                setFlash('error', 'Could not delete link.');
            }
        }
    }
    redirect(url('/admin/social_links.php'));
}

/* =========================================================
   POST — TOGGLE ACTIVE
   ========================================================= */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['form_action'] ?? '') === 'toggle') {
    if (!verifyCsrf()) {
        setFlash('error', 'Security check failed.');
    } else {
        $toggleId = (int)($_POST['id'] ?? 0);
        if ($toggleId > 0) {
            try {
                $pdo->prepare('UPDATE social_links SET is_active = 1 - is_active WHERE id = :id')
                    ->execute([':id' => $toggleId]);
                setFlash('success', 'Link status updated.');
            } catch (Throwable $e) {
                setFlash('error', 'Could not update status.');
            }
        }
    }
    redirect(url('/admin/social_links.php'));
}

/* =========================================================
   LOAD
   ========================================================= */
$rows = $pdo->query('SELECT * FROM social_links ORDER BY display_order, id')->fetchAll();

$editing = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM social_links WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $editing = $stmt->fetch() ?: null;
    if (!$editing) {
        setFlash('error', 'Social link not found.');
        redirect(url('/admin/social_links.php'));
    }
}

require_once __DIR__ . '/partials/admin_header.php';
require_once __DIR__ . '/partials/admin_sidebar.php';
?>

<div class="admin-page-head">
    <div>
        <h1><i class="fas fa-share-nodes"></i> Social Links</h1>
        <p>Links to your profiles shown in the hero and footer.</p>
    </div>
    <div class="actions">
        <?php if ($action === 'list'): ?>
            <a href="<?= url('/admin/social_links.php?action=new') ?>" class="btn btn--primary btn--sm">
                <i class="fas fa-plus"></i> New Link
            </a>
        <?php else: ?>
            <a href="<?= url('/admin/social_links.php') ?>" class="btn btn--ghost btn--sm">
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
      $curIcon = $val('icon_class', 'fab fa-github');
    ?>
    <form method="post" class="panel">
        <?= csrfField() ?>
        <input type="hidden" name="form_action" value="save">
        <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">

        <div class="panel__head">
            <h2><?= $action === 'edit' ? 'Edit' : 'New' ?> Social Link</h2>
        </div>

        <div class="admin-form">

            <div class="form-group">
                <label>Quick Presets</label>
                <div style="display:flex;flex-wrap:wrap;gap:.4rem;">
                    <?php foreach ($presets as $key => $p): ?>
                        <button type="button" class="chip social-preset"
                                data-platform="<?= e($key) ?>"
                                data-icon="<?= e($p['icon']) ?>"
                                data-placeholder="<?= e($p['placeholder']) ?>"
                                style="cursor:pointer;border:1px solid var(--border);padding:.35rem .65rem;">
                            <i class="<?= e($p['icon']) ?>"></i> <?= e($p['label']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="hint">Click a preset to auto-fill platform, icon, and URL placeholder.</div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="platform">Platform *</label>
                    <input class="form-control" type="text" id="platform" name="platform"
                           value="<?= e($val('platform')) ?>" required maxlength="50"
                           placeholder="github">
                </div>
                <div class="form-group">
                    <label for="icon_class">Icon Class</label>
                    <div style="display:flex;align-items:center;gap:.75rem;">
                        <div class="img-preview__thumb" id="iconPreview" style="width:46px;height:46px;font-size:1.2rem;color:var(--brand-2);">
                            <i class="<?= e($curIcon) ?>"></i>
                        </div>
                        <input class="form-control" type="text" id="icon_class" name="icon_class"
                               value="<?= e($curIcon) ?>"
                               oninput="document.querySelector('#iconPreview i').className = this.value;">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="url">URL *</label>
                <input class="form-control" type="text" id="url" name="url"
                       value="<?= e($val('url')) ?>" required
                       placeholder="https://github.com/yourusername">
                <div class="hint">Use https://… for web links, mailto:… for email, tel:… for phone, or /path for internal.</div>
            </div>

            <div class="grid-2">
                <div class="form-group" style="max-width:220px">
                    <label for="display_order">Display Order</label>
                    <input class="form-control" type="number" id="display_order" name="display_order"
                           value="<?= e($val('display_order', '0')) ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <label style="display:flex;align-items:center;gap:.5rem;font-weight:500;color:var(--text);">
                        <input type="checkbox" name="is_active" value="1"
                               <?= ($action === 'new' || (int)$val('is_active', 1)) ? 'checked' : '' ?>>
                        Show on site
                    </label>
                </div>
            </div>
        </div>

        <div class="admin-form-actions">
            <button type="submit" class="btn btn--primary">
                <i class="fas fa-save"></i> <?= $action === 'edit' ? 'Update' : 'Create' ?>
            </button>
            <a href="<?= url('/admin/social_links.php') ?>" class="btn btn--ghost">Cancel</a>
        </div>
    </form>

    <script>
    // Preset quick-fill
    document.querySelectorAll('.social-preset').forEach(btn => {
        btn.addEventListener('click', () => {
            const platform = btn.dataset.platform;
            const icon = btn.dataset.icon;
            const placeholder = btn.dataset.placeholder;

            document.getElementById('platform').value = platform;
            document.getElementById('icon_class').value = icon;
            document.querySelector('#iconPreview i').className = icon;

            const urlInput = document.getElementById('url');
            urlInput.placeholder = placeholder;
        });
    });
    </script>

<?php else: ?>

    <!-- ============== LIST ============== -->
    <div class="panel">
        <div class="panel__head">
            <h2>
                All Social Links
                <span class="muted" style="font-weight:400;font-size:.85rem;">(<?= count($rows) ?>)</span>
            </h2>
        </div>

        <?php if ($rows): ?>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width:50px;">Icon</th>
                            <th>Platform</th>
                            <th>URL</th>
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
                                        <i class="<?= e($r['icon_class'] ?: 'fas fa-link') ?>"></i>
                                    </div>
                                </td>
                                <td><strong><?= e(ucfirst($r['platform'])) ?></strong></td>
                                <td>
                                    <a href="<?= e($r['url']) ?>" target="_blank" rel="noopener"
                                       class="muted" style="text-decoration:underline;font-size:.85rem;">
                                        <?= e(excerpt($r['url'], 55)) ?>
                                    </a>
                                </td>
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
                                        <form method="post" action="<?= url('/admin/social_links.php') ?>" style="display:inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="form_action" value="toggle">
                                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                            <button type="submit" class="btn-icon" title="<?= $r['is_active'] ? 'Hide' : 'Activate' ?>">
                                                <i class="fas <?= $r['is_active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                            </button>
                                        </form>
                                        <a class="btn-icon"
                                           href="<?= url('/admin/social_links.php?action=edit&id=' . (int)$r['id']) ?>"
                                           title="Edit">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <form method="post"
                                              action="<?= url('/admin/social_links.php') ?>"
                                              style="display:inline"
                                              onsubmit="return confirm('Delete this social link?');">
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
                <i class="fas fa-share-nodes"></i>
                <p>No social links yet.</p>
                <a href="<?= url('/admin/social_links.php?action=new') ?>" class="btn btn--primary btn--sm">
                    <i class="fas fa-plus"></i> Add your first link
                </a>
            </div>
        <?php endif; ?>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/partials/admin_footer.php'; ?>
