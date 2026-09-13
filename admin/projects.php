<?php
/**
 * admin/projects.php
 * ------------------------------------------------------------
 * Full CRUD for `projects` table + per-project gallery images.
 *
 * Actions:
 *  - List:                default (with category filter tabs)
 *  - New:                 ?action=new
 *  - Edit:                ?action=edit&id=N
 *  - Save:                POST form_action=save
 *  - Delete project:      POST form_action=delete
 *  - Delete gallery img:  POST form_action=delete_image
 *
 * Notes:
 *  - On save, if the request contains gallery_images[], each file
 *    is uploaded and inserted into `project_images`.
 *  - Saving redirects back to the edit view so uploaded images
 *    are immediately visible.
 *  - The delete_image handler is top-level, before save, so it
 *    never interferes with the save branch.
 */

require_once __DIR__ . '/../includes/auth.php';

$adminPageTitle = 'Projects';
$adminActiveNav = 'projects';

$pdo    = getDB();
$action = clean($_GET['action'] ?? 'list');
$id     = (int)($_GET['id'] ?? 0);
$errors = [];

const PROJECT_CATEGORIES = ['Web Development', 'PHP', 'JavaScript', 'Database', 'AI', 'Other'];

/* =========================================================
   POST — DELETE GALLERY IMAGE
   (Must run before save handler; it's a standalone action.)
   ========================================================= */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['form_action'] ?? '') === 'delete_image') {
    if (!verifyCsrf()) {
        setFlash('error', 'Security check failed.');
    } else {
        $iid = (int)($_POST['image_id'] ?? 0);
        if ($iid > 0) {
            try {
                $s = $pdo->prepare('SELECT image FROM project_images WHERE id = :id');
                $s->execute([':id' => $iid]);
                $img = (string)$s->fetchColumn();

                $pdo->prepare('DELETE FROM project_images WHERE id = :id')->execute([':id' => $iid]);

                if ($img && str_starts_with($img, 'uploads/')) {
                    $abs = __DIR__ . '/../' . $img;
                    if (file_exists($abs)) @unlink($abs);
                }
                setFlash('success', 'Gallery image removed.');
            } catch (Throwable $e) {
                setFlash('error', APP_ENV === 'development'
                    ? 'DB error: ' . $e->getMessage()
                    : 'Could not delete image.');
            }
        }
    }
    redirect(url('/admin/projects.php?action=edit&id=' . (int)($_POST['id'] ?? 0)));
}

/* =========================================================
   POST — SAVE (insert or update)
   ========================================================= */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['form_action'] ?? '') === 'save') {
    if (!verifyCsrf()) {
        $errors[] = 'Security check failed.';
    } else {
        $editId = (int)($_POST['id'] ?? 0);

        $data = [
            'name'              => clean($_POST['name'] ?? ''),
            'short_description' => cleanText($_POST['short_description'] ?? ''),
            'full_description'  => cleanText($_POST['full_description'] ?? ''),
            'technologies'      => clean($_POST['technologies'] ?? ''),
            'category'          => clean($_POST['category'] ?? ''),
            'github_url'        => clean($_POST['github_url'] ?? ''),
            'live_url'          => clean($_POST['live_url'] ?? ''),
            'documentation_url' => clean($_POST['documentation_url'] ?? ''),
            'year'              => clean($_POST['year'] ?? ''),
            'is_featured'       => isset($_POST['is_featured'])  ? 1 : 0,
            'is_published'      => isset($_POST['is_published']) ? 1 : 0,
            'display_order'     => (int)($_POST['display_order'] ?? 0),
        ];

        // ---- Validation ----
        if ($data['name'] === '') $errors[] = 'Project name is required.';
        if (mb_strlen($data['name']) > 180) $errors[] = 'Name is too long (max 180 chars).';
        if (!in_array($data['category'], PROJECT_CATEGORIES, true)) $errors[] = 'Please choose a valid category.';
        if ($data['year'] !== '' && !preg_match('/^\d{4}$/', $data['year'])) {
            $errors[] = 'Year must be a 4-digit number (or left empty).';
        }
        foreach (['github_url', 'live_url', 'documentation_url'] as $urlKey) {
            if ($data[$urlKey] !== '' && !filter_var($data[$urlKey], FILTER_VALIDATE_URL)) {
                $errors[] = ucfirst(str_replace('_', ' ', $urlKey)) . ' must be a valid URL.';
            }
        }

        // ---- Main image upload (optional) ----
        $newImage = null;
        if (empty($errors) && !empty($_FILES['image']['tmp_name'])) {
            $newImage = uploadImage($_FILES['image'], 'projects');
        }

        // ---- Save main project row ----
        if (!$errors) {
            try {
                if ($editId > 0) {
                    // Fetch old image to delete if replaced
                    $oldStmt = $pdo->prepare('SELECT image FROM projects WHERE id = :id');
                    $oldStmt->execute([':id' => $editId]);
                    $oldImage = (string)$oldStmt->fetchColumn();

                    $sql = 'UPDATE projects SET
                                name = :name,
                                short_description = :short_description,
                                full_description = :full_description,
                                technologies = :technologies,
                                category = :category,
                                github_url = :github_url,
                                live_url = :live_url,
                                documentation_url = :documentation_url,
                                year = :year,
                                is_featured = :is_featured,
                                is_published = :is_published,
                                display_order = :display_order'
                         . ($newImage ? ', image = :image' : '')
                         . ' WHERE id = :id';

                    $bind = [
                        ':name'              => $data['name'],
                        ':short_description' => $data['short_description'],
                        ':full_description'  => $data['full_description'],
                        ':technologies'      => $data['technologies'],
                        ':category'          => $data['category'],
                        ':github_url'        => $data['github_url']        ?: null,
                        ':live_url'          => $data['live_url']          ?: null,
                        ':documentation_url' => $data['documentation_url'] ?: null,
                        ':year'              => $data['year']              ?: null,
                        ':is_featured'       => $data['is_featured'],
                        ':is_published'      => $data['is_published'],
                        ':display_order'     => $data['display_order'],
                        ':id'                => $editId,
                    ];
                    if ($newImage) $bind[':image'] = $newImage;

                    $pdo->prepare($sql)->execute($bind);

                    // Remove old image file if replaced
                    if ($newImage && $oldImage && str_starts_with($oldImage, 'uploads/')) {
                        $abs = __DIR__ . '/../' . $oldImage;
                        if (file_exists($abs)) @unlink($abs);
                    }

                    setFlash('success', 'Project updated.');
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO projects
                          (name, short_description, full_description, image,
                           technologies, category, github_url, live_url, documentation_url,
                           year, is_featured, is_published, display_order)
                         VALUES
                          (:name, :short_description, :full_description, :image,
                           :technologies, :category, :github_url, :live_url, :documentation_url,
                           :year, :is_featured, :is_published, :display_order)'
                    );
                    $stmt->execute([
                        ':name'              => $data['name'],
                        ':short_description' => $data['short_description'],
                        ':full_description'  => $data['full_description'],
                        ':image'             => $newImage,
                        ':technologies'      => $data['technologies'],
                        ':category'          => $data['category'],
                        ':github_url'        => $data['github_url']        ?: null,
                        ':live_url'          => $data['live_url']          ?: null,
                        ':documentation_url' => $data['documentation_url'] ?: null,
                        ':year'              => $data['year']              ?: null,
                        ':is_featured'       => $data['is_featured'],
                        ':is_published'      => $data['is_published'],
                        ':display_order'     => $data['display_order'],
                    ]);
                    $editId = (int)$pdo->lastInsertId();
                    setFlash('success', 'Project added.');
                }
            } catch (Throwable $e) {
                $errors[] = APP_ENV === 'development'
                    ? 'DB error: ' . $e->getMessage()
                    : 'Could not save the project.';
            }
        }

        // ---- Extra gallery images (only if project save succeeded) ----
        if (!$errors && !empty($_FILES['gallery_images']['tmp_name'][0])) {
            try {
                foreach ($_FILES['gallery_images']['tmp_name'] as $i => $tmp) {
                    if (($_FILES['gallery_images']['error'][$i] ?? 1) !== UPLOAD_ERR_OK) continue;
                    if (empty($tmp)) continue;

                    $single = [
                        'tmp_name' => $tmp,
                        'name'     => $_FILES['gallery_images']['name'][$i],
                        'type'     => $_FILES['gallery_images']['type'][$i],
                        'error'    => $_FILES['gallery_images']['error'][$i],
                        'size'     => $_FILES['gallery_images']['size'][$i],
                    ];
                    $path = uploadImage($single, 'projects');
                    if ($path) {
                        $pdo->prepare('INSERT INTO project_images (project_id, image, display_order) VALUES (:p, :i, :o)')
                            ->execute([':p' => $editId, ':i' => $path, ':o' => 999]);
                    }
                }
            } catch (Throwable $e) {
                setFlash('error', APP_ENV === 'development'
                    ? 'Gallery upload error: ' . $e->getMessage()
                    : 'Some gallery images could not be saved.');
            }
        }

        // ---- Success: redirect back to the edit view ----
        if (!$errors) {
            clearOld();
            redirect(url('/admin/projects.php?action=edit&id=' . $editId));
        }
    }
}

/* =========================================================
   POST — DELETE PROJECT
   ========================================================= */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['form_action'] ?? '') === 'delete') {
    if (!verifyCsrf()) {
        setFlash('error', 'Security check failed.');
    } else {
        $deleteId = (int)($_POST['id'] ?? 0);
        if ($deleteId > 0) {
            try {
                // Delete gallery image files first
                $gi = $pdo->prepare('SELECT image FROM project_images WHERE project_id = :pid');
                $gi->execute([':pid' => $deleteId]);
                foreach ($gi->fetchAll(PDO::FETCH_COLUMN) as $img) {
                    if ($img && str_starts_with($img, 'uploads/')) {
                        $abs = __DIR__ . '/../' . $img;
                        if (file_exists($abs)) @unlink($abs);
                    }
                }
                // Remove gallery rows (also cascades via FK, but explicit is fine)
                $pdo->prepare('DELETE FROM project_images WHERE project_id = :pid')
                    ->execute([':pid' => $deleteId]);

                // Delete main image file
                $stmt = $pdo->prepare('SELECT image FROM projects WHERE id = :id');
                $stmt->execute([':id' => $deleteId]);
                $img = (string)$stmt->fetchColumn();
                if ($img && str_starts_with($img, 'uploads/')) {
                    $abs = __DIR__ . '/../' . $img;
                    if (file_exists($abs)) @unlink($abs);
                }

                // Delete the project row
                $pdo->prepare('DELETE FROM projects WHERE id = :id')->execute([':id' => $deleteId]);

                setFlash('success', 'Project deleted.');
            } catch (Throwable $e) {
                setFlash('error', 'Could not delete project.');
            }
        }
    }
    redirect(url('/admin/projects.php'));
}

/* =========================================================
   LOAD DATA
   ========================================================= */
$rows = $pdo->query(
    'SELECT * FROM projects
     ORDER BY is_featured DESC, display_order, year DESC, id DESC'
)->fetchAll();

$grouped = [];
foreach ($rows as $r) $grouped[$r['category']][] = $r;

$editing = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $editing = $stmt->fetch() ?: null;
    if (!$editing) {
        setFlash('error', 'Project not found.');
        redirect(url('/admin/projects.php'));
    }
}

require_once __DIR__ . '/partials/admin_header.php';
require_once __DIR__ . '/partials/admin_sidebar.php';
?>

<div class="admin-page-head">
    <div>
        <h1><i class="fas fa-diagram-project"></i> Projects</h1>
        <p>Your portfolio projects — the most visible part of your site.</p>
    </div>
    <div class="actions">
        <?php if ($action === 'list'): ?>
            <a href="<?= url('/admin/projects.php?action=new') ?>" class="btn btn--primary btn--sm">
                <i class="fas fa-plus"></i> New Project
            </a>
        <?php else: ?>
            <a href="<?= url('/admin/projects.php') ?>" class="btn btn--ghost btn--sm">
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
      $img    = $editing['image'] ?? '';
      $imgUrl = $img && file_exists(__DIR__ . '/../' . $img) ? url($img) : '';
    ?>
    <form method="post" enctype="multipart/form-data" class="panel">
        <?= csrfField() ?>
        <input type="hidden" name="form_action" value="save">
        <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">

        <div class="panel__head">
            <h2><?= $action === 'edit' ? 'Edit' : 'New' ?> Project</h2>
        </div>

        <div class="admin-form">
            <div class="grid-2">
                <div class="form-group">
                    <label for="name">Project Name *</label>
                    <input class="form-control" type="text" id="name" name="name"
                           value="<?= e($val('name')) ?>" required maxlength="180">
                </div>
                <div class="form-group">
                    <label for="category">Category *</label>
                    <select class="form-control" id="category" name="category" required>
                        <?php foreach (PROJECT_CATEGORIES as $cat): ?>
                            <option value="<?= e($cat) ?>" <?= $val('category', 'Web Development') === $cat ? 'selected' : '' ?>>
                                <?= e($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="short_description">Short Description (card preview, max ~300 chars)</label>
                <textarea class="form-control" id="short_description" name="short_description" rows="2"
                          maxlength="300"><?= e($val('short_description')) ?></textarea>
                <div class="hint">Appears on the project card and in the modal header.</div>
            </div>

            <div class="form-group">
                <label for="full_description">Full Description (project modal body)</label>
                <textarea class="form-control" id="full_description" name="full_description" rows="6"><?= e($val('full_description')) ?></textarea>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="technologies">Technologies (comma-separated)</label>
                    <input class="form-control" type="text" id="technologies" name="technologies"
                           value="<?= e($val('technologies')) ?>"
                           placeholder="PHP, MySQL, JavaScript, CSS">
                    <div class="hint">Rendered as chips on the card and in the modal.</div>
                </div>
                <div class="form-group">
                    <label for="year">Year</label>
                    <input class="form-control" type="number" id="year" name="year"
                           value="<?= e($val('year')) ?>" min="1950" max="2100" placeholder="2025">
                </div>
            </div>

            <div class="grid-3">
                <div class="form-group">
                    <label for="github_url">GitHub URL</label>
                    <input class="form-control" type="url" id="github_url" name="github_url"
                           value="<?= e($val('github_url')) ?>" placeholder="https://github.com/…">
                </div>
                <div class="form-group">
                    <label for="live_url">Live Demo URL</label>
                    <input class="form-control" type="url" id="live_url" name="live_url"
                           value="<?= e($val('live_url')) ?>" placeholder="https://…">
                </div>
                <div class="form-group">
                    <label for="documentation_url">Documentation URL</label>
                    <input class="form-control" type="url" id="documentation_url" name="documentation_url"
                           value="<?= e($val('documentation_url')) ?>" placeholder="https://…">
                </div>
            </div>

            <div class="form-group">
                <label>Project Image (main cover)</label>
                <div class="img-preview">
                    <div class="img-preview__thumb" id="projectPreview" style="width:120px;height:80px;border-radius:10px;">
                        <?php if ($imgUrl): ?>
                            <img src="<?= e($imgUrl) ?>" alt="Project image">
                        <?php else: ?>
                            <i class="fas fa-image"></i>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1">
                        <input class="form-control" type="file" name="image"
                               id="projectImageInput" accept="image/jpeg,image/png,image/webp,image/gif">
                        <div class="hint">JPG, PNG, WEBP, GIF — max 3 MB. Ideal ratio 16:10.</div>
                    </div>
                </div>
            </div>

            <div class="grid-3">
                <div class="form-group">
                    <label>Featured</label>
                    <label style="display:flex;align-items:center;gap:.5rem;font-weight:500;color:var(--text);">
                        <input type="checkbox" name="is_featured" value="1"
                               <?= (int)$val('is_featured', 0) ? 'checked' : '' ?>>
                        Highlight this project
                    </label>
                </div>
                <div class="form-group">
                    <label>Published</label>
                    <label style="display:flex;align-items:center;gap:.5rem;font-weight:500;color:var(--text);">
                        <input type="checkbox" name="is_published" value="1"
                               <?= ($action === 'new' || (int)$val('is_published', 1)) ? 'checked' : '' ?>>
                        Show on public site
                    </label>
                </div>
                <div class="form-group">
                    <label for="display_order">Display Order</label>
                    <input class="form-control" type="number" id="display_order" name="display_order"
                           value="<?= e($val('display_order', '0')) ?>">
                </div>
            </div>
        </div>

        <?php if (!empty($editing['id'])): ?>
            <div class="panel__head" style="margin-top:1.5rem;">
                <h2>Project Gallery Images</h2>
            </div>
            <?php
              $extraStmt = $pdo->prepare('SELECT * FROM project_images WHERE project_id = :pid ORDER BY display_order, id');
              $extraStmt->execute([':pid' => (int)$editing['id']]);
              $extraImages = $extraStmt->fetchAll();
            ?>
            <?php if ($extraImages): ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:.75rem;margin-bottom:1rem;">
                    <?php foreach ($extraImages as $ei): ?>
                        <div style="position:relative;border:1px solid var(--border);border-radius:10px;overflow:hidden;">
                            <img src="<?= url($ei['image']) ?>" alt="" style="width:100%;aspect-ratio:16/10;object-fit:cover;display:block;">
                            <form method="post"
                                  style="position:absolute;top:6px;right:6px;"
                                  onsubmit="return confirm('Delete this gallery image?');">
                                <?= csrfField() ?>
                                <input type="hidden" name="form_action" value="delete_image">
                                <input type="hidden" name="image_id" value="<?= (int)$ei['id'] ?>">
                                <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">
                                <button class="btn-icon btn-icon--danger" type="submit"
                                        style="background:rgba(0,0,0,.55);color:#fff;border-color:transparent;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="muted" style="margin:0 0 1rem;">No extra images yet.</p>
            <?php endif; ?>

            <div class="form-group">
                <label>Add Gallery Images (multiple)</label>
                <input class="form-control" type="file" name="gallery_images[]" multiple
                       accept="image/jpeg,image/png,image/webp,image/gif">
                <div class="hint">Select one or more images. Each max 3 MB.</div>
            </div>
        <?php endif; ?>

        <div class="admin-form-actions">
            <button type="submit" class="btn btn--primary">
                <i class="fas fa-save"></i> <?= $action === 'edit' ? 'Update' : 'Create' ?>
            </button>
            <a href="<?= url('/admin/projects.php') ?>" class="btn btn--ghost">Cancel</a>
        </div>
    </form>

    <script>
    document.getElementById('projectImageInput')?.addEventListener('change', function (e) {
        const f = e.target.files[0];
        if (!f) return;
        const url = URL.createObjectURL(f);
        document.getElementById('projectPreview').innerHTML =
            '<img src="' + url + '" alt="Preview" style="width:100%;height:100%;object-fit:cover;">';
    });
    </script>

<?php else: ?>

    <!-- ============== LIST ============== -->
    <div class="panel">
        <div class="panel__head">
            <h2>
                All Projects
                <span class="muted" style="font-weight:400;font-size:.85rem;">(<?= count($rows) ?>)</span>
            </h2>
            <div class="projects__filters" style="margin:0;">
                <button type="button" class="tab is-active" data-proj-filter="all">All</button>
                <?php foreach (PROJECT_CATEGORIES as $cat):
                    $cnt = count($grouped[$cat] ?? []);
                    if ($cnt === 0) continue;
                ?>
                    <button type="button" class="tab" data-proj-filter="<?= e($cat) ?>">
                        <?= e($cat) ?> <span class="muted">(<?= $cnt ?>)</span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($rows): ?>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width:70px;">Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th style="width:70px;">Year</th>
                            <th style="width:90px;">Featured</th>
                            <th style="width:90px;">Status</th>
                            <th style="width:80px;">Order</th>
                            <th style="width:130px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r):
                            $img    = $r['image'] ?? '';
                            $hasImg = $img && file_exists(__DIR__ . '/../' . $img);
                        ?>
                            <tr data-proj-row-cat="<?= e($r['category']) ?>">
                                <td>
                                    <div style="width:60px;height:44px;border-radius:8px;overflow:hidden;background:var(--bg-soft);display:grid;place-items:center;">
                                        <?php if ($hasImg): ?>
                                            <img src="<?= url($img) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                                        <?php else: ?>
                                            <i class="fas fa-image muted"></i>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <strong><?= e($r['name']) ?></strong>
                                    <?php if (!empty($r['technologies'])): ?>
                                        <br><small class="muted"><?= e(excerpt($r['technologies'], 60)) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="chip"><?= e($r['category']) ?></span></td>
                                <td class="mono" style="font-size:.85rem;"><?= e($r['year']) ?: '—' ?></td>
                                <td>
                                    <?php if ((int)$r['is_featured']): ?>
                                        <i class="fas fa-star" style="color:#f59e0b"></i>
                                    <?php else: ?>
                                        <span class="muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ((int)$r['is_published']): ?>
                                        <span class="badge" style="background:#10b981;color:#fff;border-color:transparent;">Live</span>
                                    <?php else: ?>
                                        <span class="badge">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int)$r['display_order'] ?></td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn-icon"
                                           href="<?= url('/admin/projects.php?action=edit&id=' . (int)$r['id']) ?>"
                                           title="Edit">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <a class="btn-icon"
                                           href="<?= url('/#projects') ?>"
                                           target="_blank" title="View on site">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="post"
                                              action="<?= url('/admin/projects.php') ?>"
                                              style="display:inline"
                                              onsubmit="return confirm('Delete this project? The image will also be removed.');">
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

            <div class="empty-state" data-proj-empty style="display:none;margin-top:1rem;">
                <i class="fas fa-filter"></i>
                <p>No projects in this category.</p>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-diagram-project"></i>
                <p>No projects yet.</p>
                <a href="<?= url('/admin/projects.php?action=new') ?>" class="btn btn--primary btn--sm">
                    <i class="fas fa-plus"></i> Add your first project
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
    (function () {
      const tabs  = document.querySelectorAll('[data-proj-filter]');
      const rows  = document.querySelectorAll('[data-proj-row-cat]');
      const empty = document.querySelector('[data-proj-empty]');
      if (!tabs.length) return;
      tabs.forEach(tab => {
        tab.addEventListener('click', () => {
          tabs.forEach(t => t.classList.remove('is-active'));
          tab.classList.add('is-active');
          const cat = tab.dataset.projFilter;
          let visible = 0;
          rows.forEach(row => {
            const show = cat === 'all' || row.dataset.projRowCat === cat;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
          });
          if (empty) empty.style.display = visible ? 'none' : '';
        });
      });
    })();
    </script>

<?php endif; ?>

<?php require_once __DIR__ . '/partials/admin_footer.php'; ?>