<?php
/**
 * admin/skills.php
 * ------------------------------------------------------------
 * CRUD for `skills` table.
 * - List:   default view (with category filter)
 * - Add:    ?action=new
 * - Edit:   ?action=edit&id=N
 * - Save:   POST  (INSERT or UPDATE)
 * - Delete: POST  (?action=delete&id=N)
 *
 * Categories are fixed by the DB ENUM: Programming, Database,
 * Web Development, Tools, Technology.
 */

require_once __DIR__ . '/../includes/auth.php';

$adminPageTitle = 'Skills';
$adminActiveNav = 'skills';

$pdo    = getDB();
$action = clean($_GET['action'] ?? 'list');
$id     = (int)($_GET['id'] ?? 0);
$errors = [];

const SKILL_CATEGORIES = ['Programming', 'Database', 'Web Development', 'Tools', 'Technology'];

/* =========================================================
   HANDLE POST — SAVE
   ========================================================= */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['form_action'] ?? '') === 'save') {
    if (!verifyCsrf()) {
        $errors[] = 'Security check failed.';
    } else {
        $data = [
            'category'      => clean($_POST['category'] ?? ''),
            'name'          => clean($_POST['name'] ?? ''),
            'level'         => (int)($_POST['level'] ?? 50),
            'icon_class'    => clean($_POST['icon_class'] ?? 'fas fa-code'),
            'display_order' => (int)($_POST['display_order'] ?? 0),
        ];

        if (!in_array($data['category'], SKILL_CATEGORIES, true)) $errors[] = 'Please choose a valid category.';
        if ($data['name'] === '') $errors[] = 'Skill name is required.';
        if ($data['level'] < 0 || $data['level'] > 100) $errors[] = 'Level must be between 0 and 100.';

        $editId = (int)($_POST['id'] ?? 0);

        if (!$errors) {
            try {
                if ($editId > 0) {
                    $stmt = $pdo->prepare(
                        'UPDATE skills SET
                            category = :category,
                            name = :name,
                            level = :level,
                            icon_class = :icon_class,
                            display_order = :display_order
                         WHERE id = :id'
                    );
                    $stmt->execute([
                        ':category'      => $data['category'],
                        ':name'          => $data['name'],
                        ':level'         => $data['level'],
                        ':icon_class'    => $data['icon_class'],
                        ':display_order' => $data['display_order'],
                        ':id'            => $editId,
                    ]);
                    setFlash('success', 'Skill updated.');
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO skills (category, name, level, icon_class, display_order)
                         VALUES (:category, :name, :level, :icon_class, :display_order)'
                    );
                    $stmt->execute([
                        ':category'      => $data['category'],
                        ':name'          => $data['name'],
                        ':level'         => $data['level'],
                        ':icon_class'    => $data['icon_class'],
                        ':display_order' => $data['display_order'],
                    ]);
                    setFlash('success', 'Skill added.');
                }
                clearOld();
                redirect(url('/admin/skills.php'));
            } catch (Throwable $e) {
                $errors[] = APP_ENV === 'development'
                    ? 'DB error: ' . $e->getMessage()
                    : 'Could not save the skill.';
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
                $pdo->prepare('DELETE FROM skills WHERE id = :id')->execute([':id' => $deleteId]);
                setFlash('success', 'Skill deleted.');
            } catch (Throwable $e) {
                setFlash('error', 'Could not delete skill.');
            }
        }
    }
    redirect(url('/admin/skills.php'));
}

/* =========================================================
   LOAD DATA
   ========================================================= */
$filterCat = clean($_GET['cat'] ?? '');

// Pull all skills (ordered), then group
$rows = $pdo->query('SELECT * FROM skills ORDER BY category, display_order, id')->fetchAll();

$grouped = [];
foreach ($rows as $r) {
    $grouped[$r['category']][] = $r;
}

$totalSkills = count($rows);

// Editing?
$editing = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM skills WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $editing = $stmt->fetch() ?: null;
    if (!$editing) {
        setFlash('error', 'Skill not found.');
        redirect(url('/admin/skills.php'));
    }
}

// Icon suggestions for common dev tools
$iconSuggestions = [
    'fab fa-html5', 'fab fa-css3-alt', 'fab fa-js', 'fab fa-php',
    'fab fa-python', 'fab fa-git-alt', 'fab fa-github', 'fab fa-node-js',
    'fas fa-code', 'fas fa-database', 'fas fa-table', 'fas fa-server',
    'fas fa-laptop-code', 'fas fa-mobile-alt', 'fas fa-plug', 'fas fa-brain',
    'fas fa-shield-alt', 'fas fa-network-wired', 'fas fa-tools',
    'fas fa-terminal', 'fas fa-palette', 'fas fa-cogs',
];

require_once __DIR__ . '/partials/admin_header.php';
require_once __DIR__ . '/partials/admin_sidebar.php';
?>

<div class="admin-page-head">
    <div>
        <h1><i class="fas fa-code"></i> Skills</h1>
        <p>Your technical skills, grouped by category and shown on the home page.</p>
    </div>
    <div class="actions">
        <?php if ($action === 'list'): ?>
            <a href="<?= url('/admin/skills.php?action=new') ?>" class="btn btn--primary btn--sm">
                <i class="fas fa-plus"></i> New Skill
            </a>
        <?php else: ?>
            <a href="<?= url('/admin/skills.php') ?>" class="btn btn--ghost btn--sm">
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
      $curLevel = (int)$val('level', 50);
    ?>
    <form method="post" class="panel">
        <?= csrfField() ?>
        <input type="hidden" name="form_action" value="save">
        <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">

        <div class="panel__head">
            <h2><?= $action === 'edit' ? 'Edit' : 'New' ?> Skill</h2>
        </div>

        <div class="admin-form">
            <div class="grid-2">
                <div class="form-group">
                    <label for="name">Skill Name *</label>
                    <input class="form-control" type="text" id="name" name="name"
                           value="<?= e($val('name')) ?>" required maxlength="80">
                </div>
                <div class="form-group">
                    <label for="category">Category *</label>
                    <select class="form-control" id="category" name="category" required>
                        <?php foreach (SKILL_CATEGORIES as $cat): ?>
                            <option value="<?= e($cat) ?>"
                                <?= $val('category') === $cat ? 'selected' : '' ?>>
                                <?= e($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="level">Level — <span id="levelValue"><?= $curLevel ?></span>%</label>
                <input type="range" id="level" name="level" min="0" max="100" step="5"
                       value="<?= $curLevel ?>"
                       style="width:100%;accent-color:var(--brand-2);"
                       oninput="document.getElementById('levelValue').textContent = this.value;">
                <div class="hint">Be honest — 0 = never touched, 100 = expert. Intermediate is 40–70.</div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="icon_class">Icon Class</label>
                    <input class="form-control" type="text" id="icon_class" name="icon_class"
                           value="<?= e($val('icon_class', 'fas fa-code')) ?>" placeholder="fas fa-code">
                    <div class="hint">
                        Font Awesome class. Suggestions:
                        <?php foreach (array_slice($iconSuggestions, 0, 8) as $ic): ?>
                            <code><?= e($ic) ?></code>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="display_order">Display Order</label>
                    <input class="form-control" type="number" id="display_order" name="display_order"
                           value="<?= e($val('display_order', '0')) ?>">
                    <div class="hint">Lower numbers appear first within the same category.</div>
                </div>
            </div>
        </div>

        <div class="admin-form-actions">
            <button type="submit" class="btn btn--primary">
                <i class="fas fa-save"></i> <?= $action === 'edit' ? 'Update' : 'Create' ?>
            </button>
            <a href="<?= url('/admin/skills.php') ?>" class="btn btn--ghost">Cancel</a>
        </div>
    </form>

<?php else: ?>

    <!-- ============== LIST ============== -->
    <div class="panel">
        <div class="panel__head">
            <h2>
                All Skills
                <span class="muted" style="font-weight:400;font-size:.85rem;">(<?= $totalSkills ?>)</span>
            </h2>
            <div class="projects__filters" style="margin:0;">
                <button type="button" class="tab is-active" data-skill-filter="all">All</button>
                <?php foreach (SKILL_CATEGORIES as $cat):
                    $cnt = count($grouped[$cat] ?? []);
                ?>
                    <button type="button" class="tab" data-skill-filter="<?= e($cat) ?>">
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
                            <th style="width:40px;"></th>
                            <th>Name</th>
                            <th>Category</th>
                            <th style="width:180px;">Level</th>
                            <th style="width:80px;">Order</th>
                            <th style="width:130px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr data-skill-row-cat="<?= e($r['category']) ?>">
                                <td><i class="<?= e($r['icon_class'] ?: 'fas fa-code') ?>" style="color:var(--brand-2)"></i></td>
                                <td><strong><?= e($r['name']) ?></strong></td>
                                <td><span class="chip"><?= e($r['category']) ?></span></td>
                                <td>
                                    <div class="skill__bar" style="height:8px;">
                                        <span style="width: <?= (int)$r['level'] ?>%;"></span>
                                    </div>
                                    <small class="muted mono" style="font-size:.78rem;"><?= (int)$r['level'] ?>%</small>
                                </td>
                                <td><?= (int)$r['display_order'] ?></td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn-icon"
                                           href="<?= url('/admin/skills.php?action=edit&id=' . (int)$r['id']) ?>"
                                           title="Edit">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <form method="post"
                                              action="<?= url('/admin/skills.php') ?>"
                                              style="display:inline"
                                              onsubmit="return confirm('Delete this skill? This cannot be undone.');">
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

            <div class="empty-state" data-skill-empty style="display:none;margin-top:1rem;">
                <i class="fas fa-filter"></i>
                <p>No skills in this category.</p>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-code"></i>
                <p>No skills yet.</p>
                <a href="<?= url('/admin/skills.php?action=new') ?>" class="btn btn--primary btn--sm">
                    <i class="fas fa-plus"></i> Add your first skill
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // Category filter for the skills list
    (function () {
      const tabs  = document.querySelectorAll('[data-skill-filter]');
      const rows  = document.querySelectorAll('[data-skill-row-cat]');
      const empty = document.querySelector('[data-skill-empty]');
      if (!tabs.length) return;
      tabs.forEach(tab => {
        tab.addEventListener('click', () => {
          tabs.forEach(t => t.classList.remove('is-active'));
          tab.classList.add('is-active');
          const cat = tab.dataset.skillFilter;
          let visible = 0;
          rows.forEach(row => {
            const show = cat === 'all' || row.dataset.skillRowCat === cat;
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
