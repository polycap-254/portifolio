<?php
/**
 * admin/partials/admin_header.php
 * Opens the admin HTML document: <head>, topbar, opening <main>.
 * Expects: $adminPageTitle (optional), $adminActiveNav (optional)
 */
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$user          = currentUser();
$adminPageTitle = $adminPageTitle ?? 'Dashboard';
$adminActiveNav = $adminActiveNav ?? '';

// Count unread messages for the badge
$unreadCount = 0;
try {
    $pdo = getDB();
    $unreadCount = (int)$pdo->query("SELECT COUNT(*) FROM messages WHERE status = 'unread'")->fetchColumn();
} catch (Throwable $e) { /* ignore */ }
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($adminPageTitle) ?> · Admin · <?= e(APP_NAME) ?></title>
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
<body class="admin-body">

<header class="admin-topbar">
    <button type="button" class="icon-btn admin-topbar__burger" id="adminSidebarToggle" aria-label="Toggle sidebar">
        <i class="fas fa-bars"></i>
    </button>

    <a class="admin-topbar__brand" href="<?= url('/admin/dashboard.php') ?>">
        <span class="navbar__brand-mark" aria-hidden="true">
            <?= e(mb_strtoupper(mb_substr($user['full_name'] ?? 'A', 0, 1))) ?>
        </span>
        <span>
            <strong>Admin</strong>
            <small class="muted"><?= e(APP_NAME) ?></small>
        </span>
    </a>

    <div class="admin-topbar__actions">
        <a href="<?= url('/') ?>" class="btn btn--ghost btn--sm" target="_blank" rel="noopener">
            <i class="fas fa-external-link-alt"></i> <span class="hide-sm">View Site</span>
        </a>
        <button type="button" class="icon-btn theme-toggle" id="themeToggle" aria-label="Toggle theme">
            <i class="fas fa-moon" data-theme-icon></i>
        </button>
        <div class="admin-user">
            <span class="admin-user__avatar"><?= e(mb_strtoupper(mb_substr($user['full_name'] ?? 'A', 0, 1))) ?></span>
            <span class="admin-user__name hide-sm"><?= e($user['full_name'] ?? 'Admin') ?></span>
        </div>
    </div>
</header>

<div class="admin-shell">
