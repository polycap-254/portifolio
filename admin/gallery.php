<?php
/**
 * admin/gallery.php
 * CRUD for gallery_images — the About Me carousel.
 */

require_once __DIR__ . '/../includes/auth.php';

$adminPageTitle = 'Gallery';
$adminActiveNav = 'gallery';

$pdo    = getDB();
$action = clean($_GET['action'] ?? 'list');
$id     = (int)($_GET['id'] ?? 0);
$errors = [];

/* ---------- SAVE ---------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['form_action'] ?? '') === 'save') {
    if (!verifyCsrf()) {
        $errors[] = 'Security check failed.';
    } else {
        $editId = (int)($_POST['id'] ?? 0);
        $title   = clean($_POST['title'] ?? '');
        $caption = clean($_POST['caption'] ?? '');
        $order   = (int)($_POST['display_order'] ?? 0);
        $active  = isset($_POST['is_active']) ? 1 : 0;

        $newImage = null;
        if (!empty($_FILES['image']['tmp_name'])) {
            $newImage = uploadImage($_FILES['image'], 'gallery');
        } elseif ($editId === 0) {
            $errors[] = 'An image is required.';
        }

        if (!$errors) {
            try {
                if ($editId > 0) {
                    $sql = 'UPDATE gallery_images SET title=:t, caption=:c, display_order=:o, is_active=:a'
                         . ($newImage ? ', image=:i' : '') . ' WHERE id=:id';
                    $bind = [':t'=>$title, ':c'=>$caption, ':o'=>$order, ':a'=>$active, ':id'=>$editId];
                    if ($newImage) $bind[':i'] = $newImage;
                    $pdo->prepare($sql)->execute($bind);
                    setFlash('success', 'Gallery image updated.');
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO gallery_images (title, caption, image, display_order, is_active)
                         VALUES (:t,:c,:i,:o,:a)'
                    );
                    $stmt->execute([':t'=>$title, ':c'=>$caption, ':i'=>$newImage, ':o'=>$order, ':a'=>$active]);
                    setFlash('success', 'Gallery image added.');
                }
                redirect(url('/admin/gallery.php'));
            } catch (Throwable $e) {
                $errors[] = APP_ENV === 'development' ? $e->getMessage() : 'Could not save.';
            }
        }
    }
}

/* ---------- DELETE ---------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['form_action'] ?? '') === 'delete') {
    if (!verifyCsrf()) { setFlash('error', 'Security check failed.'); }
    else {
        $did = (int)($_POST['id'] ?? 0);
        if ($did > 0) {
            $stmt = $pdo->prepare('SELECT image FROM gallery_images WHERE id=:id');
            $stmt->execute([':id'=>$did]);
            $img = (string)$stmt->fetchColumn();
            $pdo->prepare('DELETE FROM gallery_images WHERE id=:id')->execute([':id'=>$did]);
            if ($img && str_starts_with($img, 'uploads/')) {
                $abs = __DIR__ . '/../' . $img;
                if (file_exists($abs)) @unlink($abs);
            }
            setFlash('success', 'Gallery image deleted.');
        }
    }
    redirect(url('/admin/gallery.php'));
}

/* ---------- TOGGLE ---------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['form_action'] ?? '') === 'toggle') {
    if (!verifyCsrf()) { setFlash('error', 'Security check failed.'); }
    else {
        $tid = (int)($_POST['id'] ?? 0);
        if ($tid > 0) {
            $pdo->prepare('UPDATE gallery_images SET is_active = 1 - is_active WHERE id=:id')->execute([':id'=>$tid]);
            setFlash('success', 'Status updated.');
        }
    }
    redirect(url('/admin/gallery.php'));
}

/* ---------- LOAD ---------- */
$rows = $pdo->query('SELECT * FROM gallery_images ORDER BY display_order, id')->fetchAll();
$editing = null;
if ($action === 'edit' && $id > 0) {
    $s = $pdo->prepare('SELECT * FROM gallery_images WHERE id=:id');
    $s->execute([':id'=>$id]);
    $editing = $s->fetch() ?: null;
    if (!$editing) { setFlash('error', 'Not found.'); redirect(url('/admin/gallery.php')); }
}

require_once __DIR__ . '/partials/admin_header.php';
require_once __DIR__ . '/partials/admin_sidebar.php';
?>

<div class="admin-page-head">
    <div>
        <h1><i class="fas fa-images"></i> About Me Gallery</h1>
        <p>Photos of you, shown as a carousel on the About section.</p>
    </div>
    <div class="actions">
        <?php if ($action === 'list'): ?>
            <a href="<?= url('/admin/gallery.php?action=new') ?>" class="btn btn--primary btn--sm">
                <i class="fas fa-plus"></i> Upload Image
            </a>
        <?php else: ?>
            <a href="<?= url('/admin/gallery.php') ?>" class="btn btn--ghost btn--sm">
                <i class="fas fa-list"></i> Back to list
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($errors): ?>
    <div class="empty-state" style="border-color:#ef4444;color:#ef4444;text-align:left;padding:1rem;margin-bottom:1rem;">
        <?php foreach ($errors as $err): ?><div><i class="fas fa-circle-exclamation"></i> <?= e($err) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($action === 'new' || $action === 'edit'):
    $val = function($k, $d='') use ($editing) { return old($k) !== '' ? old($k) : ($editing[$k] ?? $d); };
    $imgPath = $editing['image'] ?? '';
    $imgUrl  = $imgPath && file_exists(__DIR__ . '/../' . $imgPath) ? url($imgPath) : '';
?>
    <form method="post" enctype="multipart/form-data" class="panel">
        <?= csrfField() ?>
        <input type="hidden" name="form_action" value="save">
        <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">

        <div class="panel__head"><h2><?= $action==='edit'?'Edit':'Upload' ?> Photo</h2></div>

        <div class="admin-form">
            <div class="form-group">
                <label>Image <?= $action==='new'?'*':'' ?></label>
                <div class="img-preview">
                    <div class="img-preview__thumb" id="gPreview" style="width:120px;height:80px;border-radius:10px;">
                        <?php if ($imgUrl): ?>
                            <img src="<?= e($imgUrl) ?>" alt="">
                        <?php else: ?>
                            <i class="fas fa-image"></i>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1">
                        <input class="form-control" type="file" name="image" id="gImage"
                               accept="image/jpeg,image/png,image/webp,image/gif" <?= $action==='new'?'required':'' ?>>
                        <div class="hint">JPG, PNG, WEBP, GIF — max 3 MB. Ideal ratio 16:10.</div>
                    </div>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input class="form-control" type="text" id="title" name="title" value="<?= e($val('title')) ?>" maxlength="180">
                </div>
                <div class="form-group" style="max-width:220px">
                    <label for="display_order">Display Order</label>
                    <input class="form-control" type="number" id="display_order" name="display_order" value="<?= e($val('display_order', '0')) ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="caption">Caption</label>
                <input class="form-control" type="text" id="caption" name="caption" value="<?= e($val('caption')) ?>" maxlength="300">
            </div>

            <div class="form-group">
                <label><input type="checkbox" name="is_active" value="1" <?= ($action==='new' || (int)$val('is_active', 1)) ? 'checked' : '' ?>> Show in carousel</label>
            </div>
        </div>

        <div class="admin-form-actions">
            <button type="submit" class="btn btn--primary"><i class="fas fa-save"></i> Save</button>
            <a href="<?= url('/admin/gallery.php') ?>" class="btn btn--ghost">Cancel</a>
        </div>
    </form>

    <script>
    document.getElementById('gImage')?.addEventListener('change', function(e) {
      const f = e.target.files[0]; if (!f) return;
      document.getElementById('gPreview').innerHTML =
        '<img src="' + URL.createObjectURL(f) + '" style="width:100%;height:100%;object-fit:cover;" alt="">';
    });
    </script>

<?php else: ?>
    <div class="panel">
        <div class="panel__head">
            <h2>All Photos <span class="muted" style="font-weight:400;font-size:.85rem;">(<?= count($rows) ?>)</span></h2>
        </div>

        <?php if ($rows): ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width:80px;">Image</th>
                        <th>Title</th>
                        <th>Caption</th>
                        <th style="width:90px;">Status</th>
                        <th style="width:70px;">Order</th>
                        <th style="width:130px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r):
                        $hasImg = $r['image'] && file_exists(__DIR__ . '/../' . $r['image']);
                    ?>
                    <tr>
                        <td>
                            <div style="width:60px;height:44px;border-radius:8px;overflow:hidden;background:var(--bg-soft);display:grid;place-items:center;">
                                <?php if ($hasImg): ?>
                                    <img src="<?= url($r['image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                                <?php else: ?><i class="fas fa-image muted"></i><?php endif; ?>
                            </div>
                        </td>
                        <td><strong><?= e($r['title']) ?: '—' ?></strong></td>
                        <td><small class="muted"><?= e(excerpt($r['caption'] ?? '', 70)) ?></small></td>
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
                                <form method="post" style="display:inline"><?= csrfField() ?>
                                    <input type="hidden" name="form_action" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <button class="btn-icon" type="submit" title="<?= $r['is_active']?'Hide':'Show' ?>"><i class="fas <?= $r['is_active']?'fa-eye-slash':'fa-eye' ?>"></i></button>
                                </form>
                                <a class="btn-icon" href="<?= url('/admin/gallery.php?action=edit&id=' . (int)$r['id']) ?>"><i class="fas fa-pen"></i></a>
                                <form method="post" style="display:inline" onsubmit="return confirm('Delete this image?');"><?= csrfField() ?>
                                    <input type="hidden" name="form_action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <button class="btn-icon btn-icon--danger" type="submit"><i class="fas fa-trash"></i></button>
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
                <i class="fas fa-images"></i>
                <p>No gallery images yet.</p>
                <a href="<?= url('/admin/gallery.php?action=new') ?>" class="btn btn--primary btn--sm"><i class="fas fa-plus"></i> Upload your first photo</a>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/partials/admin_footer.php'; ?>
