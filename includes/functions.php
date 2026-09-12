<?php
/**
 * =============================================================
 * includes/functions.php
 * -------------------------------------------------------------
 * Shared helper functions used across public pages and admin.
 *
 * Provides:
 *   - Output escaping:        e()
 *   - Input sanitization:     clean()
 *   - Redirects:              redirect()
 *   - Flash messages:         setFlash(), getFlash(), hasFlash()
 *   - CSRF tokens:            csrfToken(), csrfField(), verifyCsrf()
 *   - Session bootstrap:      startSession()
 *   - URL helpers:            url(), asset()
 *   - Formatting:             formatDate(), formatDateRange()
 *   - Upload helpers:         uploadImage()
 *   - Old input:              old(), setOld(), clearOld()
 * =============================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

// -------------------------------------------------------------
// SESSION
// -------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    // Sensible session cookie flags
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// -------------------------------------------------------------
// OUTPUT ESCAPING
// -------------------------------------------------------------
/**
 * Escape a value for safe HTML output.
 */
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// -------------------------------------------------------------
// INPUT CLEANING
// -------------------------------------------------------------
/**
 * Trim and strip a scalar input value.
 * Returns '' for arrays or null.
 */
function clean($value): string
{
    if (is_array($value)) {
        return '';
    }
    return trim((string)($value ?? ''));
}

/**
 * Clean a multiline text block: trim + normalize newlines.
 */
function cleanText($value): string
{
    $v = clean($value);
    $v = str_replace(["\r\n", "\r"], "\n", $v);
    return $v;
}

// -------------------------------------------------------------
// REDIRECT
// -------------------------------------------------------------
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

// -------------------------------------------------------------
// URL HELPERS
// -------------------------------------------------------------
/**
 * Build an absolute app path, e.g. url('/admin/login.php')
 */
function url(string $path = '/'): string
{
    $base = rtrim(APP_URL, '/');
    return $base . '/' . ltrim($path, '/');
}

/**
 * Build an asset URL, e.g. asset('css/style.css')
 */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Current request path relative to APP_URL (used for nav highlighting).
 */
function currentPath(): string
{
    $uri  = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    $base = rtrim(APP_URL, '/');
    if ($base && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }
    return '/' . ltrim($path, '/');
}

// -------------------------------------------------------------
// FLASH MESSAGES
// -------------------------------------------------------------
/**
 * Store a one-time flash message.
 * $type: 'success' | 'error' | 'info' | 'warning'
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Pop all queued flash messages.
 */
function getFlash(): array
{
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}

function hasFlash(): bool
{
    return !empty($_SESSION['flash']);
}

// -------------------------------------------------------------
// CSRF PROTECTION
// -------------------------------------------------------------
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a ready-to-use hidden CSRF field.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

/**
 * Verify the CSRF token from a POST request.
 * Returns true on success; on failure sets flash and returns false.
 */
function verifyCsrf(): bool
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!$sent || !hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
        setFlash('error', 'Security check failed. Please try again.');
        return false;
    }
    return true;
}

// -------------------------------------------------------------
// OLD INPUT (for re-filling forms on validation errors)
// -------------------------------------------------------------
function setOld(array $data): void
{
    // Never store the CSRF token
    unset($data['csrf_token']);
    $_SESSION['old'] = $data;
}

function old(string $key, string $default = ''): string
{
    return (string)($_SESSION['old'][$key] ?? $default);
}

function clearOld(): void
{
    unset($_SESSION['old']);
}

// -------------------------------------------------------------
// DATE FORMATTING
// -------------------------------------------------------------
function formatDate(?string $date, string $format = 'M Y'): string
{
    if (!$date) return '';
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '';
}

function formatDateRange(?string $start, ?string $end, string $format = 'M Y'): string
{
    $s = formatDate($start, $format);
    $e = $end ? formatDate($end, $format) : 'Present';
    if (!$s) return $e;
    return $s . ' — ' . $e;
}

// -------------------------------------------------------------
// TEXT HELPERS
// -------------------------------------------------------------
/**
 * Convert newline-separated text into an array of trimmed lines.
 */
function linesToArray(?string $text): array
{
    if (!$text) return [];
    $lines = preg_split('/\r\n|\r|\n/', $text);
    return array_values(array_filter(array_map('trim', $lines), fn($l) => $l !== ''));
}

/**
 * Comma-separated tech string -> array.
 */
function techToArray(?string $csv): array
{
    if (!$csv) return [];
    return array_values(array_filter(array_map('trim', explode(',', $csv))));
}

/**
 * Make a URL-safe slug.
 */
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

/**
 * Shorten text safely with ellipsis.
 */
function excerpt(?string $text, int $length = 120): string
{
    $text = trim((string)$text);
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length - 1) . '…';
}

// -------------------------------------------------------------
// IMAGE UPLOAD (safe)
// -------------------------------------------------------------
/**
 * Validate and store an uploaded image.
 * $file   : $_FILES['field']
 * $subdir : 'projects' | 'profile'
 * Returns stored relative path (e.g. 'uploads/projects/abc.jpg')
 * or null on failure (sets flash error).
 */
function uploadImage(array $file, string $subdir = 'projects'): ?string
{
    if (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null; // nothing uploaded
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        setFlash('error', 'Upload failed (error code ' . (int)$file['error'] . ').');
        return null;
    }

    // Size limit: 3 MB
    $maxBytes = 3 * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxBytes) {
        setFlash('error', 'Image is too large. Maximum size is 3 MB.');
        return null;
    }

    // Verify it's a real image
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        setFlash('error', 'The file is not a valid image.');
        return null;
    }

    // Allowed MIME -> extension map
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
    $mime = $info['mime'] ?? '';
    if (!isset($allowed[$mime])) {
        setFlash('error', 'Only JPG, PNG, WEBP or GIF images are allowed.');
        return null;
    }

    // Build safe target path
    $subdir  = preg_replace('/[^a-z0-9_-]/i', '', $subdir) ?: 'projects';
    $ext     = $allowed[$mime];
    $name    = bin2hex(random_bytes(8)) . '.' . $ext;
    $relDir  = 'uploads/' . $subdir;
    $absDir  = __DIR__ . '/../' . $relDir;

    if (!is_dir($absDir) && !mkdir($absDir, 0755, true) && !is_dir($absDir)) {
        setFlash('error', 'Could not create upload directory.');
        return null;
    }

    $absPath = $absDir . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $absPath)) {
        setFlash('error', 'Could not save the uploaded file.');
        return null;
    }

    return $relDir . '/' . $name;
}

// -------------------------------------------------------------
// CONVENIENCE: fetch profile row (id=1)
// -------------------------------------------------------------
function getProfile(): array
{
    try {
        $pdo  = getDB();
        $row  = $pdo->query('SELECT * FROM profile WHERE id = 1 LIMIT 1')->fetch();
        return $row ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

// -------------------------------------------------------------
// CONVENIENCE: fetch active social links ordered
// -------------------------------------------------------------
function getSocialLinks(): array
{
    try {
        $pdo = getDB();
        return $pdo->query(
            'SELECT * FROM social_links WHERE is_active = 1 ORDER BY display_order, id'
        )->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}
