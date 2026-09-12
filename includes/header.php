<?php
/**
 * includes/header.php
 * ------------------------------------------------------------
 * Opens the HTML document: doctype, <head>, SEO meta, Open Graph,
 * favicon, CSS link, theme bootstrap (no-FOUC).
 *
 * Pages set $pageTitle, $pageDescription, $ogImage BEFORE including.
 */

require_once __DIR__ . '/functions.php';

$profile      = getProfile();
$siteName     = $profile['full_name']         ?? APP_NAME;
$siteTitle    = $profile['professional_title']?? '';
$siteTagline  = $profile['tagline']           ?? '';
$siteImage    = !empty($profile['profile_image'])
                ? url($profile['profile_image'])
                : asset('images/profile/profile.jpg');

$pageTitle       = $pageTitle       ?? $siteName;
$pageDescription = $pageDescription ?? ($siteTagline ?: 'Personal portfolio of ' . $siteName);
$ogImage         = $ogImage         ?? $siteImage;
$canonical       = url(currentPath());
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title><?= e($pageTitle) ?><?= $pageTitle !== $siteName ? ' — ' . e($siteName) : '' ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="author" content="<?= e($siteName) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">

    <!-- Open Graph -->
    <meta property="og:type"        content="website">
    <meta property="og:title"       content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:image"       content="<?= e($ogImage) ?>">
    <meta property="og:url"         content="<?= e($canonical) ?>">
    <meta property="og:site_name"   content="<?= e($siteName) ?>">

    <!-- Twitter -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($pageDescription) ?>">
    <meta name="twitter:image"       content="<?= e($ogImage) ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= asset('images/icons/favicon.svg') ?>">
    <link rel="alternate icon" type="image/png" href="<?= asset('images/icons/favicon.svg') ?>">
    <link rel="apple-touch-icon" href="<?= asset('images/icons/favicon.svg') ?>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">

    <!-- Icons (Font Awesome CDN — optional, keep small) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" referrerpolicy="no-referrer">

    <!-- Base CSS -->
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">

    <!-- Theme bootstrap: prevents flash of wrong theme (runs before body paints) -->
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                var theme = saved || (prefersDark ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {}
        })();
    </script>
</head>
<body>
