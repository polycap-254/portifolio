<?php
/**
 * admin/forgot_password.php
 * Recovery-key based password reset for the admin user.
 * - Ask for the recovery key + new password.
 * - Verify recovery key hash with password_verify.
 * - On success: update password_hash, clear the recovery key (must set a new one).
 */

require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) redirect(url('/admin/dashboard.php'));

$errors  = [];
$success = false;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Security check failed. Please try again.';
    } else {
        $username    = clean($_POST['username'] ?? '');
        $recoveryKey = trim((string)($_POST['recovery_key'] ?? ''));
        $newPass     = (string)($_POST['new_password'] ?? '');
        $confirmPass = (string)($_POST['confirm_password'] ?? '');

        if ($username === '')    $errors[] = 'Username is required.';
        if ($recoveryKey === '') $errors[] = 'Recovery key is required.';

        if (strlen($newPass) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $newPass) || !preg_match('/[a-z]/', $newPass) || !preg_match('/\d/', $newPass)) {
            $errors[] = 'Password must include upper, lower, and a number.';
        }
        if ($newPass !== $confirmPass) $errors[] = 'Passwords do not match.';

        if (!$errors) {
            try {
                $pdo = getDB();
                $stmt = $pdo->prepare('SELECT id, recovery_key_hash FROM users WHERE username = :u LIMIT 1');
                $stmt->execute([':u' => $username]);
                $user = $stmt->fetch();

                if (!$user || empty($user['recovery_key_hash'])) {
                    $errors[] = 'No recovery key is set for that account.';
                    usleep(400000);
                } elseif (!password_verify($recoveryKey, $user['recovery_key_hash'])) {
                    $errors[] = 'Recovery key is incorrect.';
                    usleep(400000);
                } else {
                    $newHash = password_hash($newPass, PASSWORD_BCRYPT);
                    $pdo->prepare('UPDATE users SET password_hash = :h, recovery_key_hash = NULL WHERE id = :id')
                        ->execute([':h' => $newHash, ':id' => $user['id']]);

                    $success = true;
                    setFlash('success', 'Password updated. Please log in with your new password. You must set a new recovery key (see README).');
                }
            } catch (Throwable $e) {
                $errors[] = APP_ENV === 'development' ? $e->getMessage() : 'Could not reset password.';
            }
        }
    }
}

$flashes = getFlash();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Reset Password · <?= e(APP_NAME) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('images/icons/favicon.svg') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.setAttribute('data-theme', saved || (prefersDark ? 'dark' : 'light'));
            } catch (e) {}
        })();
    </script>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="login-card__head">
            <div class="navbar__brand-mark" aria-hidden="true">PN</div>
            <h1>Reset Password</h1>
            <p>Enter your recovery key to set a new password</p>
        </div>

        <?php if ($success): ?>
            <div class="empty-state" style="border-color:#10b981;color:#10b981;text-align:left;padding:1rem;margin-bottom:1rem;">
                <i class="fas fa-circle-check"></i>
                <p style="margin:0 0 .5rem;color:var(--text);">Password updated successfully.</p>
                <a href="<?= url('/admin/login.php') ?>" class="btn btn--primary btn--sm" style="margin-top:.5rem;">
                    <i class="fas fa-right-to-bracket"></i> Go to Login
                </a>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="empty-state" style="border-color:#ef4444;color:#ef4444;text-align:left;padding:1rem;margin-bottom:1rem;">
                <?php foreach ($errors as $err): ?>
                    <div><i class="fas fa-circle-exclamation"></i> <?= e($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="post" action="<?= url('/admin/forgot_password.php') ?>" novalidate>
            <?= csrfField() ?>

            <div class="form-group">
                <label for="username">Username</label>
                <input class="form-control" type="text" id="username" name="username"
                       value="<?= e(old('username', 'admin')) ?>" required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="recovery_key">Recovery Key</label>
                <input class="form-control mono" type="text" id="recovery_key" name="recovery_key"
                       placeholder="XXXX-XXXX-XXXX-XXXX" required autocomplete="off"
                       style="letter-spacing:.1em;">
                <div class="hint">The 4-group code you saved when setting up your account.</div>
            </div>

            <div class="form-group">
                <label for="new_password">New Password</label>
                <input class="form-control" type="password" id="new_password" name="new_password"
                       required autocomplete="new-password" minlength="8">
                <div class="hint">Min 8 chars, with upper, lower, and a number.</div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input class="form-control" type="password" id="confirm_password" name="confirm_password"
                       required autocomplete="new-password" minlength="8">
            </div>

            <button type="submit" class="btn btn--primary btn--lg btn--block">
                <i class="fas fa-key"></i> Reset Password
            </button>
        </form>
        <?php endif; ?>

        <p class="login-card__foot">
            <a href="<?= url('/admin/login.php') ?>"><i class="fas fa-arrow-left"></i> Back to login</a>
        </p>
    </div>
</div>

<div class="toast-stack" id="toastStack" aria-live="polite"></div>
<?php if ($flashes): ?>
<script>window.__FLASH__ = <?= json_encode($flashes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<?php endif; ?>
<script src="<?= asset('js/script.js') ?>" defer></script>
</body>
</html>
