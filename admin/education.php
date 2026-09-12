<?php
/**
 * admin/education.php
 * ------------------------------------------------------------
 * CRUD for `education` table.
 * - List:   default view
 * - Add:    ?action=new
 * - Edit:   ?action=edit&id=N
 * - Save:   POST  (INSERT or UPDATE)
 * - Delete: POST  (?action=delete&id=N)  — CSRF protected
 */

require_once __DIR__ . '/../includes/auth.php';

$adminPageTitle = 'Education';
$adminActiveNav = 'education';

$pdo    = getDB();
$action = clean($_GET['action'] ?? 'list');
$id     = (int)($_GET['id'] ?? 0);
$errors = [];

/* =========================================================
   HANDLE POST — SAVE (insert or update)
   ========================================================= */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['form_action'] ?? '') === 'save') {
    if (!verifyCsrf()) {
        $errors[] = 'Security check failed.';
    } else {
        $data = [
            'institution'      => clean($_POST['institution'] ?? ''),
            'course'           => clean($_POST['course'] ?? ''),
            'level'            => clean($_POST['level'] ?? ''),
            'start_year'       => clean($_POST['start_year'] ?? ''),
            'end_year'         => clean($_POST['end_year'] ?? ''),
            'description'      => cleanText($_POST['description'] ?? ''),
            'certificate_url'  => clean($_POST['certificate_url'] ?? ''),
            'institution_icon' => clean($_POST['institution_icon'] ?? 'fa-university'),
            'display_order'    => (int)($_POST['display_order'] ?? 0),
        ];

        if ($data['institution'] === '') $errors[] = 'Institution is required.';
        if ($data['start_year'] !== '' && !preg_match('/^\d{4}$/', $data['start_year'])) {
            $errors[] = 'Start year must be a 4-digit year.';
        }
        if ($data['end_year'] !== '' && !preg_match('/^\d{4}$/', $data['end_year'])) {
            $errors[] = 'End year must be a 4-digit year or left empty.';
        }

        $editId = (int)($_POST['id'] ?? 0);

        if (!$errors) {
            try {
                if ($editId > 0) {
                    $sql = 'UPDATE education SET
                                institution = :institution,
                                course = :course,
                                level = :level,
                                start_year = :start_year,
                                end_year = :end_year,
                                description = :description,
                                certificate_url = :certificate_url,
                                institution_icon = :institution_icon,
                                display_order = :display_order
                            WHERE id = :id';
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':institution'      => $data['institution'],
                        ':course'           => $data['course'],
                        ':level'            => $data['level'],
                        ':start_year'       => $data['start_year'] ?: null,
                        ':end_year'         => $data['end_year']   ?: null,
                        ':description'      => $data['description'],
                        ':certificate_url'  => $data['certificate_url'],
                        ':institution_icon' => $data['institution_icon'],
                        ':display_order'    => $data['display_order'],
                        ':id'               => $editId,
                    ]);
                    setFlash('success', 'Education entry updated.');
                } else {
                    $sql = 'INSERT INTO education
                              (institution, course, level, start_year, end_year,
                               description, certificate_url, institution_icon, display_order)
                            VALUES
                              (:institution, :course, :level, :start_year, :end_year,
                               :description, :certificate_url, :institution_icon, :display_order)';
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':institution'      => $data['institution'],
                        ':course'           => $data['course'],
                        ':level'            => $data['level'],
                        ':start_year'       => $data['start_year'] ?: null,
                        ':end_year'         => $data['end_year']   ?: null,
                        ':description'      => $data['description'],
                        ':certificate_url'  => $data['certificate_url'],
                        ':institution_icon' => $data['institution_icon'],
                        ':display_order'    => $data['display_order'],
                    ]);
                    setFlash('success', 'Education entry added.');
                }
                clearOld();
                redirect(url('/admin/education.php'));
            } catch (Throwable $e) {
                $errors[] = APP_ENV === 'development'
                    ? 'DB error: ' . $e->getMessage()
                    : 'Could not save the entry.';
            }
        }
    }
}

/* =========================================================
   HANDLE POST — DELETE
   ========================================================= */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['form_action'] ?? '') === 'delete') {
    if (!verifyCsrf()) {
        setFlash('error', 'Security check failed.');
    } else {
        $deleteId = (int)($_POST['id'] ?? 0);
        if ($deleteId > 0) {
            try {
                $pdo->prepare('DELETE FROM education WHERE id = :id')->execute([':id' => $deleteId]);
                setFlash('success', 'Education entry deleted.');
            } catch (Throwable $e) {
                setFlash('error', 'Could not delete entry.');
            }
        }
    }
    redirect(url('/admin/education.php'));
}

/* =========================================================
   LOAD DATA FOR THE VIEW
   ========================================================= */
$rows = $pdo->query('SELECT * FROM education ORDER BY display_order, start_year DESC, id DESC')->fetchAll();

// If editing, fetch that row
$editing = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM education WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $editing = $stmt->fetch() ?: null;
    if (!$editing) {
        setFlash('error', 'Education entry not found.');
        redirect(url('/admin/education.php'));
    }
}

// Common icon suggestions
$iconSuggestions = [
    'fa-university', 'fa-school', 'fa-graduation-cap', 'fa-book',
    'fa-certificate', 'fa-award', 'fa-laptop-code', 'fa-flask',
];

require_once __DIR__ . '/partials/admin_header.php';
require_once __DIR__ . '/partials/admin_sidebar.php';
?>

<div class="admin-page-head">
    <div>
        <h1><i class="fas fa-graduation-cap"></i> Education</h1>
        <p>Manage your academic background shown on the home page.</p>
    </div>
    <div class="actions">
        <?php if ($action === 'list'): ?>
            <a href="<?= url('/admin/education.php?action=new') ?>" class="btn btn--primary btn--sm">
                <i class="fas fa-plus"></i> New Entry
            </a>
        <?php else: ?>
            <a href="<?= url('/admin/education.php') ?>" class="btn btn--ghost btn--sm">
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
    ?>
    <form method="post" class="panel">
        <?= csrfField() ?>
        <input type="hidden" name="form_action" value="save">
        <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">

        <div class="panel__head">
            <h2><?= $action === 'edit' ? 'Edit' : 'New' ?> Education Entry</h2>
        </div>

        <div class="admin-form">
            <div class="grid-2">
                <div class="form-group">
                    <label for="institution">Institution *</label>
                    <input class="form-control" type="text" id="institution" name="institution"
                           value="<?= e($val('institution')) ?>" required>
                </div>
                <div class="form-group">
                    <label for="course">Course / Programme</label>
                    <input class="form-control" type="text" id="course" name="course"
                           value="<?= e($val('course')) ?>">
                </div>
            </div>

            <div class="grid-3">
                <div class="form-group">
                    <label for="level">Level</label>
                    <input class="form-control" type="text" id="level" name="level"
                           value="<?= e($val('level')) ?>" placeholder="Bachelor's, Diploma, Certificate…">
                </div>
                <div class="form-group">
                    <label for="start_year">Start Year</label>
                    <input class="form-control" type="number" id="start_year" name="start_year"
                           value="<?= e($val('start_year')) ?>" min="1950" max="2100" placeholder="2021">
                </div>
                <div class="form-group">
                    <label for="end_year">End Year</label>
                    <input class="form-control" type="number" id="end_year" name="end_year"
                           value="<?= e($val('end_year')) ?>" min="1950" max="2100" placeholder="2025 or blank">
                    <div class="hint">Leave blank if ongoing.</div>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4"><?= e($val('description')) ?></textarea>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="certificate_url">Certificate URL (optional)</label>
                    <input class="form-control" type="url" id="certificate_url" name="certificate_url"
                           value="<?= e($val('certificate_url')) ?>" placeholder="https://…">
                </div>
                <div class="form-group">
                    <label for="institution_icon">Institution Icon</label>
                    <input class="form-control" type="text" id="institution_icon" name="institution_icon"
                           value="<?= e($val('institution_icon', 'fa-university')) ?>"
                           placeholder="fa-university">
                    <div class="hint">
                        Font Awesome class. Examples:
                        <?php foreach ($iconSuggestions as $ic): ?>
                            <code><?= e($ic) ?></code>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="form-group" style="max-width:220px">
                <label for="display_order">Display Order</label>
                <input class="form-control" type="number" id="display_order" name="display_order"
                       value="<?= e($val('display_order', '0')) ?>">
                <div class="hint">Lower numbers appear first.</div>
            </div>
        </div>

        <div class="admin-form-actions">
            <button type="submit" class="btn btn--primary">
                <i class="fas fa-save"></i> <?= $action === 'edit' ? 'Update' : 'Create' ?>
            </button>
            <a href="<?= url('/admin/education.php') ?>" class="btn btn--ghost">Cancel</a>
        </div>
    </form>

<?php else: ?>

    <!-- ============== LIST ============== -->
    <div class="panel">
        <div class="panel__head">
            <h2>All Entries <span class="muted" style="font-weight:400;font-size:.85rem;">(<?= count($rows) ?>)</span></h2>
        </div>

        <?php if ($rows): ?>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width:40px;"></th>
                            <th>Institution</th>
                            <th>Course</th>
                            <th>Level</th>
                            <th>Years</th>
                            <th style="width:80px;">Order</th>
                            <th style="width:130px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><i class="fas <?= e($r['institution_icon'] ?: 'fa-university') ?>" style="color:var(--brand-2)"></i></td>
                                <td><strong><?= e($r['institution']) ?></strong></td>
                                <td><?= e($r['course']) ?></td>
                                <td><span class="chip"><?= e($r['level']) ?></span></td>
                                <td class="mono" style="font-size:.82rem;">
                                    <?= e($r['start_year']) ?><?= $r['end_year'] ? ' – ' . e($r['end_year']) : ' – Present' ?>
                                </td>
                                <td><?= (int)$r['display_order'] ?></td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn-icon"
                                           href="<?= url('/admin/education.php?action=edit&id=' . (int)$r['id']) ?>"
                                           title="Edit">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <form method="post"
                                              action="<?= url('/admin/education.php') ?>"
                                              style="display:inline"
                                              onsubmit="return confirm('Delete this education entry? This cannot be undone.');">
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
                <i class="fas fa-graduation-cap"></i>
                <p>No education entries yet.</p>
                <a href="<?= url('/admin/education.php?action=new') ?>" class="btn btn--primary btn--sm">
                    <i class="fas fa-plus"></i> Add your first entry
                </a>
            </div>
        <?php endif; ?>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/partials/admin_footer.php'; ?>
