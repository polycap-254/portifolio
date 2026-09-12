<?php
/**
 * includes/auth.php
 * ------------------------------------------------------------
 * Session-based authentication guard + helpers for the admin area.
 * Include this AFTER includes/functions.php (functions.php starts the session).
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * Attempt to log a user in.
 * Returns true on success, false otherwise.
 */
function attemptLogin(string $username, string $password): bool
{
    $username = clean($username);
    if ($username === '' || $password === '') return false;

    try {
        $pdo = getDB();
        $stmt = $pdo->prepare(
            'SELECT id, username, password_hash, full_name, role, email
             FROM users WHERE username = :u1 OR email = :u2 LIMIT 1'
        );
        $stmt->execute([':u1' => $username, ':u2' => $username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Upgrade hash if PHP's default algorithm changed
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $up = $pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
            $up->execute([':h' => $newHash, ':id' => $user['id']]);
        }

        // Regenerate session id to prevent fixation
        session_regenerate_id(true);

        $_SESSION['user_id']   = (int)$user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['full_name'] = $user['full_name'] ?: $user['username'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['logged_in_at'] = time();

        // Update last_login
        $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id')
            ->execute([':id' => $user['id']]);

        return true;
    } catch (Throwable $e) {
        if (APP_ENV === 'development') {
            setFlash('error', 'Login error: ' . $e->getMessage());
        }
        return false;
    }
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function currentUser(): array
{
    return [
        'id'        => $_SESSION['user_id']   ?? null,
        'username'  => $_SESSION['username']  ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'role'      => $_SESSION['role']      ?? null,
    ];
}

/**
 * Redirect to login if not authenticated.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('warning', 'Please log in to access the admin area.');
        redirect(url('/admin/login.php'));
    }
}

/**
 * Log the user out and destroy the session.
 */
function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], (bool)$p['secure'], (bool)$p['httponly']);
    }
    session_destroy();
}
