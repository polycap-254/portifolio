<?php
/**
 * admin/login.php
 * Login form + handler. Uses password_verify via attemptLogin().
 */

require_once __DIR__ . '/../includes/auth.php';

// Already logged in? Skip to dashboard.
if (isLoggedIn()) {
    redirect(url('/admin/dashboard.php'));
}

$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Security check failed. Please try again.';
    } else {
        $username = clean($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $errors[] = 'Please enter both username and password.';
        } elseif (attemptLogin($username, $password)) {
            setFlash('success', 'Welcome back, ' . (currentUser()['full_name'] ?: 'Admin') . '!');
            redirect(url('/admin/dashboard.php'));
        } else {
            $errors[] = 'Incorrect username or password.';
            // Slow down brute force
            usleep(400000);
        }
    }
}

$pageTitle = 'Admin Login';
$flashes = getFlash();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login · <?= e(APP_NAME) ?></title>
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
            <h1>Admin Login</h1>
            <p>Sign in to manage your portfolio</p>
        </div>

        <?php if ($errors): ?>
            <div class="empty-state" style="border-color:#ef4444;color:#ef4444;padding:1rem;margin-bottom:1rem;text-align:left;">
                <?php foreach ($errors as $err): ?>
                    <div><i class="fas fa-circle-exclamation"></i> <?= e($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= url('/admin/login.php') ?>" novalidate autocomplete="on">
            <?= csrfField() ?>

            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" class="form-control"
                       value="<?= e(old('username')) ?>"
                       autocomplete="username" autofocus required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                       autocomplete="current-password" required>
            </div>

            <button type="submit" class="btn btn--primary btn--lg btn--block">
                <i class="fas fa-right-to-bracket"></i> Sign In
            </button>
        </form>
                <p style="text-align:center;margin-top:1rem;">
            <a href="<?= url('/admin/forgot_password.php') ?>" style="font-size:.85rem;color:var(--muted);">
                <i class="fas fa-key"></i> Forgot password?
            </a>
        </p>

        <p class="login-card__foot">
            <a href="<?= url('/') ?>"><i class="fas fa-arrow-left"></i> Back to site</a>
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
