<?php
/**
 * admin/profile.php
 * ------------------------------------------------------------
 * Edit the single profile row (id=1).
 * - Text fields: name, title, tagline, intros, bio, contact info
 * - Image upload: profile photo (jpg/png/webp/gif, max 3MB)
 * - File upload:  CV (PDF, max 5MB)
 */

require_once __DIR__ . '/../includes/auth.php';

$adminPageTitle = 'Profile';
$adminActiveNav = 'profile';

$pdo = getDB();

// Ensure a profile row exists
$profile = $pdo->query('SELECT * FROM profile WHERE id = 1')->fetch();
if (!$profile) {
    $pdo->exec("INSERT INTO profile (id, full_name) VALUES (1, 'Your Name')");
    $profile = $pdo->query('SELECT * FROM profile WHERE id = 1')->fetch();
}

$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Security check failed. Please try again.';
    } else {
        // ---- Collect ----
        $data = [
            'full_name'          => clean($_POST['full_name'] ?? ''),
            'professional_title' => clean($_POST['professional_title'] ?? ''),
            'tagline'            => clean($_POST['tagline'] ?? ''),
            'short_intro'        => cleanText($_POST['short_intro'] ?? ''),
            'bio'                => cleanText($_POST['bio'] ?? ''),
            'location'           => clean($_POST['location'] ?? ''),
            'nationality'        => clean($_POST['nationality'] ?? ''),
            'languages'          => clean($_POST['languages'] ?? ''),
            'profession'         => clean($_POST['profession'] ?? ''),
            'career_interests'   => cleanText($_POST['career_interests'] ?? ''),
            'goals'              => cleanText($_POST['goals'] ?? ''),
            'hobbies'            => cleanText($_POST['hobbies'] ?? ''),
            'email'              => clean($_POST['email'] ?? ''),
            'phone'              => clean($_POST['phone'] ?? ''),
            'whatsapp'           => clean($_POST['whatsapp'] ?? ''),
        ];

        // ---- Validate ----
        if ($data['full_name'] === '') $errors[] = 'Full name is required.';
        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email address is not valid.';
        }

        // ---- Profile image upload ----
        $newImage = null;
        if (!empty($_FILES['profile_image']['tmp_name'])) {
            $newImage = uploadImage($_FILES['profile_image'], 'profile');
            if ($newImage) {
                // Delete old image if it exists and is not the placeholder
                $old = $profile['profile_image'] ?? '';
                if ($old && str_starts_with($old, 'uploads/') && file_exists(__DIR__ . '/../' . $old)) {
                    @unlink(__DIR__ . '/../' . $old);
                }
            }
        }

        // ---- CV upload (PDF only) ----
        $newCv = null;
        if (!empty($_FILES['cv_file']['tmp_name'])) {
            $cv = $_FILES['cv_file'];
            $ok = true;

            if ($cv['error'] !== UPLOAD_ERR_OK) { $errors[] = 'CV upload failed.'; $ok = false; }
            if ($ok && $cv['size'] > 5 * 1024 * 1024) { $errors[] = 'CV must be under 5 MB.'; $ok = false; }
            if ($ok) {
                $mime = mime_content_type($cv['tmp_name']) ?: '';
                if ($mime !== 'application/pdf') { $errors[] = 'CV must be a PDF file.'; $ok = false; }
            }
            if ($ok) {
                $dir = __DIR__ . '/../uploads/profile';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $name = 'cv_' . bin2hex(random_bytes(6)) . '.pdf';
                if (move_uploaded_file($cv['tmp_name'], $dir . '/' . $name)) {
                    $newCv = 'uploads/profile/' . $name;
                    $old = $profile['cv_file'] ?? '';
                    if ($old && str_starts_with($old, 'uploads/') && file_exists(__DIR__ . '/../' . $old)) {
                        @unlink(__DIR__ . '/../' . $old);
                    }
                } else {
                    $errors[] = 'Could not save the CV file.';
                }
            }
        }

        // ---- Update ----
        if (!$errors) {
            $sql = 'UPDATE profile SET
                        full_name = :full_name,
                        professional_title = :professional_title,
                        tagline = :tagline,
                        short_intro = :short_intro,
                        bio = :bio,
                        location = :location,
                        nationality = :nationality,
                        languages = :languages,
                        profession = :profession,
                        career_interests = :career_interests,
                        goals = :goals,
                        hobbies = :hobbies,
                        email = :email,
                        phone = :phone,
                        whatsapp = :whatsapp'
                 . ($newImage ? ', profile_image = :profile_image' : '')
                 . ($newCv    ? ', cv_file = :cv_file' : '')
                 . ' WHERE id = 1';

            $stmt = $pdo->prepare($sql);
            foreach ($data as $k => $v) $stmt->bindValue(':' . $k, $v);
            if ($newImage) $stmt->bindValue(':profile_image', $newImage);
            if ($newCv)    $stmt->bindValue(':cv_file', $newCv);
            $stmt->execute();

            setFlash('success', 'Profile updated successfully.');
            redirect(url('/admin/profile.php'));
        }
    }
}

require_once __DIR__ . '/partials/admin_header.php';
require_once __DIR__ . '/partials/admin_sidebar.php';

// Helper to read a value for re-display
$v = function (string $key, $default = '') use ($profile) {
    return old($key) !== '' ? old($key) : ($profile[$key] ?? $default);
};

$img = $profile['profile_image'] ?? '';
$imgUrl = $img && file_exists(__DIR__ . '/../' . $img) ? url($img) : '';
$cvUrl  = !empty($profile['cv_file']) && file_exists(__DIR__ . '/../' . $profile['cv_file'])
        ? url($profile['cv_file']) : '';
?>

<div class="admin-page-head">
    <div>
        <h1><i class="fas fa-id-badge"></i> Profile</h1>
        <p>Your personal and professional information.</p>
    </div>
    <div class="actions">
        <a href="<?= url('/') ?>" target="_blank" class="btn btn--ghost btn--sm">
            <i class="fas fa-eye"></i> Preview on site
        </a>
    </div>
</div>

<?php if ($errors): ?>
    <div class="empty-state" style="border-color:#ef4444;color:#ef4444;text-align:left;padding:1rem;margin-bottom:1rem;">
        <?php foreach ($errors as $err): ?>
            <div><i class="fas fa-circle-exclamation"></i> <?= e($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="panel">
    <?= csrfField() ?>

    <div class="panel__head">
        <h2>Personal Details</h2>
    </div>

    <div class="admin-form">
        <div class="grid-2">
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input class="form-control" type="text" id="full_name" name="full_name"
                       value="<?= e($v('full_name')) ?>" required>
            </div>
            <div class="form-group">
                <label for="profession">Profession</label>
                <input class="form-control" type="text" id="profession" name="profession"
                       value="<?= e($v('profession')) ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="professional_title">Professional Title (shown in hero)</label>
            <input class="form-control" type="text" id="professional_title" name="professional_title"
                   value="<?= e($v('professional_title')) ?>"
                   placeholder="Mathematics &amp; Computer Studies Educator | Web Developer | Technology Enthusiast">
            <div class="hint">Separate roles with a pipe <code>|</code>. They render as a row of roles.</div>
        </div>

        <div class="form-group">
            <label for="tagline">Tagline</label>
            <input class="form-control" type="text" id="tagline" name="tagline"
                   value="<?= e($v('tagline')) ?>">
        </div>

        <div class="form-group">
            <label for="short_intro">Short Intro (hero paragraph)</label>
            <textarea class="form-control" id="short_intro" name="short_intro" rows="3"><?= e($v('short_intro')) ?></textarea>
        </div>

        <div class="form-group">
            <label for="bio">Professional Bio (About → Professional Profile)</label>
            <textarea class="form-control" id="bio" name="bio" rows="6"><?= e($v('bio')) ?></textarea>
        </div>

        <div class="grid-3">
            <div class="form-group">
                <label for="location">Location</label>
                <input class="form-control" type="text" id="location" name="location"
                       value="<?= e($v('location')) ?>">
            </div>
            <div class="form-group">
                <label for="nationality">Nationality</label>
                <input class="form-control" type="text" id="nationality" name="nationality"
                       value="<?= e($v('nationality')) ?>">
            </div>
            <div class="form-group">
                <label for="languages">Languages</label>
                <input class="form-control" type="text" id="languages" name="languages"
                       value="<?= e($v('languages')) ?>" placeholder="English, Kiswahili">
            </div>
        </div>
    </div>

    <div class="panel__head" style="margin-top:1.5rem;">
        <h2>Career &amp; Goals</h2>
    </div>

    <div class="admin-form">
        <div class="form-group">
            <label for="career_interests">Career Interests</label>
            <textarea class="form-control" id="career_interests" name="career_interests" rows="3"><?= e($v('career_interests')) ?></textarea>
        </div>
        <div class="form-group">
            <label for="goals">Goals</label>
            <textarea class="form-control" id="goals" name="goals" rows="3"><?= e($v('goals')) ?></textarea>
        </div>
        <div class="form-group">
            <label for="hobbies">Hobbies &amp; Interests</label>
            <textarea class="form-control" id="hobbies" name="hobbies" rows="3"><?= e($v('hobbies')) ?></textarea>
        </div>
    </div>

    <div class="panel__head" style="margin-top:1.5rem;">
        <h2>Contact Info</h2>
    </div>

    <div class="admin-form">
        <div class="grid-3">
            <div class="form-group">
                <label for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email"
                       value="<?= e($v('email')) ?>">
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input class="form-control" type="text" id="phone" name="phone"
                       value="<?= e($v('phone')) ?>">
            </div>
            <div class="form-group">
                <label for="whatsapp">WhatsApp</label>
                <input class="form-control" type="text" id="whatsapp" name="whatsapp"
                       value="<?= e($v('whatsapp')) ?>">
            </div>
        </div>
    </div>

    <div class="panel__head" style="margin-top:1.5rem;">
        <h2>Photo &amp; CV</h2>
    </div>

    <div class="admin-form">
        <div class="form-group">
            <label>Profile Photo</label>
            <div class="img-preview">
                <div class="img-preview__thumb" id="profilePreview">
                    <?php if ($imgUrl): ?>
                        <img src="<?= e($imgUrl) ?>" alt="Current profile photo">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <div style="flex:1">
                    <input class="form-control" type="file" name="profile_image"
                           id="profileImageInput" accept="image/jpeg,image/png,image/webp,image/gif">
                    <div class="hint">JPG, PNG, WEBP, GIF — max 3 MB. Leave empty to keep current.</div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>CV (PDF)</label>
            <div class="img-preview">
                <div class="img-preview__thumb" style="font-size:1.6rem;">
                    <i class="fas fa-file-pdf" style="color:#ef4444"></i>
                </div>
                <div style="flex:1">
                    <input class="form-control" type="file" name="cv_file" accept="application/pdf">
                    <div class="hint">
                        PDF only, max 5 MB.
                        <?php if ($cvUrl): ?>
                            <a href="<?= e($cvUrl) ?>" target="_blank">Current CV →</a>
                        <?php else: ?>
                            No CV uploaded yet.
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-form-actions">
        <button type="submit" class="btn btn--primary">
            <i class="fas fa-save"></i> Save Changes
        </button>
        <a href="<?= url('/admin/dashboard.php') ?>" class="btn btn--ghost">Cancel</a>
    </div>
</form>

<script>
// Image preview
document.getElementById('profileImageInput')?.addEventListener('change', function (e) {
    const f = e.target.files[0];
    if (!f) return;
    const url = URL.createObjectURL(f);
    const box = document.getElementById('profilePreview');
    box.innerHTML = '<img src="' + url + '" alt="Preview">';
});
</script>

<?php require_once __DIR__ . '/partials/admin_footer.php'; ?>
