<?php
/**
 * admin/experience.php
 * ------------------------------------------------------------
 * CRUD for `experience` table.
 * - List:   default
 * - Add:    ?action=new
 * - Edit:   ?action=edit&id=N
 * - Save:   POST (INSERT or UPDATE)
 * - Delete: POST (?action=delete&id=N)
 *
 * Responsibilities and Achievements are stored as newline-separated text
 * and rendered as <ul><li> bullets on the public site.
 */

require_once __DIR__ . '/../includes/auth.php';

$adminPageTitle = 'Experience';
$adminActiveNav = 'experience';

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
            'position'         => clean($_POST['position'] ?? ''),
            'organization'     => clean($_POST['organization'] ?? ''),
            'start_date'       => clean($_POST['start_date'] ?? ''),
            'end_date'         => clean($_POST['end_date'] ?? ''),
            'description'      => cleanText($_POST['description'] ?? ''),
            'responsibilities' => cleanText($_POST['responsibilities'] ?? ''),
            'achievements'     => cleanText($_POST['achievements'] ?? ''),
            'display_order'    => (int)($_POST['display_order'] ?? 0),
        ];

        if ($data['position'] === '')     $errors[] = 'Position is required.';
        if ($data['organization'] === '') $errors[] = 'Organization is required.';

        // Date validation
        $validDate = fn($d) => $d === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
        if (!$validDate($data['start_date'])) $errors[] = 'Start date is invalid.';
        if (!$validDate($data['end_date']))   $errors[] = 'End date is invalid.';
        if ($data['start_date'] && $data['end_date'] && $data['start_date'] > $data['end_date']) {
            $errors[] = 'End date must be after the start date.';
        }

        if (!$errors) {
            try {
                $bind = [
                    ':position'         => $data['position'],
                    ':organization'     => $data['organization'],
                    ':start_date'       => $data['start_date'] ?: null,
                    ':end_date'         => $data['end_date']   ?: null,
                    ':description'      => $data['description'],
                    ':responsibilities' => $data['responsibilities'],
                    ':achievements'     => $data['achievements'],
                    ':display_order'    => $data['display_order'],
                ];

                if ($editId > 0) {
                    $stmt = $pdo->prepare(
                        'UPDATE experience SET
                            position = :position,
                            organization = :organization,
                            start_date = :start_date,
                            end_date = :end_date,
                            description = :description,
                            responsibilities = :responsibilities,
                            achievements = :achievements,
                            display_order = :display_order
                         WHERE id = :id'
                    );
                    $stmt->execute($bind + [':id' => $editId]);
                    setFlash('success', 'Experience entry updated.');
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO experience
                          (position, organization, start_date, end_date,
                           description, responsibilities, achievements, display_order)
                         VALUES
                          (:position, :organization, :start_date, :end_date,
                           :description, :responsibilities, :achievements, :display_order)'
                    );
                    $stmt->execute($bind);
                    setFlash('success', 'Experience entry added.');
                }
                clearOld();
                redirect(url('/admin/experience.php'));
            } catch (Throwable $e) {
                $errors[] = APP_ENV === 'development'
                    ? 'DB error: ' . $e->getMessage()
                    : 'Could not save the experience entry.';
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
                $pdo->prepare('DELETE FROM experience WHERE id = :id')->execute([':id' => $deleteId]);
                setFlash('success', 'Experience entry deleted.');
            } catch (Throwable $e) {
                setFlash('error', 'Could not delete entry.');
            }
        }
    }
    redirect(url('/admin/experience.php'));
}

/* =========================================================
   LOAD
   ========================================================= */
$rows = $pdo->query(
    'SELECT * FROM experience
     ORDER BY display_order, start_date DESC, id DESC'
)->fetchAll();

$editing = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM experience WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $editing = $stmt->fetch() ?: null;
    if (!$editing) {
        setFlash('error', 'Experience entry not found.');
        redirect(url('/admin/experience.php'));
    }
}

require_once __DIR__ . '/partials/admin_header.php';
require_once __DIR__ . '/partials/admin_sidebar.php';
?>

<div class="admin-page-head">
    <div>
        <h1><i class="fas fa-briefcase"></i> Experience</h1>
        <p>Professional roles, teaching positions, and freelance work.</p>
    </div>
    <div class="actions">
        <?php if ($action === 'list'): ?>
            <a href="<?= url('/admin/experience.php?action=new') ?>" class="btn btn--primary btn--sm">
                <i class="fas fa-plus"></i> New Entry
            </a>
        <?php else: ?>
            <a href="<?= url('/admin/experience.php') ?>" class="btn btn--ghost btn--sm">
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
            <h2><?= $action === 'edit' ? 'Edit' : 'New' ?> Experience Entry</h2>
        </div>

        <div class="admin-form">
            <div class="grid-2">
                <div class="form-group">
                    <label for="position">Position / Role *</label>
                    <input class="form-control" type="text" id="position" name="position"
                           value="<?= e($val('position')) ?>" required maxlength="150">
                </div>
                <div class="form-group">
                    <label for="organization">Organization *</label>
                    <input class="form-control" type="text" id="organization" name="organization"
                           value="<?= e($val('organization')) ?>" required maxlength="180">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input class="form-control" type="date" id="start_date" name="start_date"
                           value="<?= e($val('start_date')) ?>">
                </div>
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input class="form-control" type="date" id="end_date" name="end_date"
                           value="<?= e($val('end_date')) ?>">
                    <div class="hint">Leave empty if this is your current role → displays as <strong>Present</strong>.</div>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Short Description</label>
                <textarea class="form-control" id="description" name="description" rows="2"><?= e($val('description')) ?></textarea>
            </div>

            <div class="form-group">
                <label for="responsibilities">Responsibilities</label>
                <textarea class="form-control" id="responsibilities" name="responsibilities" rows="5"><?= e($val('responsibilities')) ?></textarea>
                <div class="hint">One responsibility per line. Each line becomes a bullet point.</div>
            </div>

            <div class="form-group">
                <label for="achievements">Achievements</label>
                <textarea class="form-control" id="achievements" name="achievements" rows="5"><?= e($val('achievements')) ?></textarea>
                <div class="hint">One achievement per line. Each line becomes a bullet point.</div>
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
            <a href="<?= url('/admin/experience.php') ?>" class="btn btn--ghost">Cancel</a>
        </div>
    </form>

<?php else: ?>

    <!-- ============== LIST ============== -->
    <div class="panel">
        <div class="panel__head">
            <h2>
                All Experience
                <span class="muted" style="font-weight:400;font-size:.85rem;">(<?= count($rows) ?>)</span>
            </h2>
        </div>

        <?php if ($rows): ?>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Position</th>
                            <th>Organization</th>
                            <th style="width:180px;">Duration</th>
                            <th style="width:80px;">Order</th>
                            <th style="width:130px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r):
                            $resp = linesToArray($r['responsibilities'] ?? '');
                            $ach  = linesToArray($r['achievements'] ?? '');
                        ?>
                            <tr>
                                <td>
                                    <strong><?= e($r['position']) ?></strong>
                                    <?php if (!empty($r['description'])): ?>
                                        <br><small class="muted"><?= e(excerpt($r['description'], 70)) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($r['organization']) ?></td>
                                <td class="mono" style="font-size:.82rem;">
                                    <?= e(formatDateRange($r['start_date'], $r['end_date'], 'M Y')) ?>
                                </td>
                                <td><?= (int)$r['display_order'] ?></td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn-icon"
                                           href="<?= url('/admin/experience.php?action=edit&id=' . (int)$r['id']) ?>"
                                           title="Edit">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <form method="post"
                                              action="<?= url('/admin/experience.php') ?>"
                                              style="display:inline"
                                              onsubmit="return confirm('Delete this experience entry?');">
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
                <i class="fas fa-briefcase"></i>
                <p>No experience entries yet.</p>
                <a href="<?= url('/admin/experience.php?action=new') ?>" class="btn btn--primary btn--sm">
                    <i class="fas fa-plus"></i> Add your first entry
                </a>
            </div>
        <?php endif; ?>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/partials/admin_footer.php'; ?>
