<?php
/**
 * =============================================================
 * config/database.php
 * -------------------------------------------------------------
 * Reusable PDO database connection (singleton).
 *
 * Usage from any page:
 *     require_once __DIR__ . '/../config/database.php';
 *     $pdo = getDB();
 *
 * Edit the constants below to match your environment.
 * For production, override via environment variables or a
 * separate config.local.php that is NOT committed to Git.
 * =============================================================
 */

declare(strict_types=1);

// -------------------------------------------------------------
// Database credentials — edit these for your environment
// -------------------------------------------------------------
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'polycap_portfolio');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// -------------------------------------------------------------
// Application constants
// -------------------------------------------------------------
define('APP_NAME', 'Polycap Nyamongo Maturwe');
define('APP_URL',  getenv('APP_URL') ?: 'http://localhost:81/polycap-portfolio');
define('APP_ENV',  getenv('APP_ENV') ?: 'development'); // 'development' | 'production'

// -------------------------------------------------------------
// Error reporting based on environment
// -------------------------------------------------------------
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// -------------------------------------------------------------
// getDB() — returns a shared PDO connection
// -------------------------------------------------------------
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Never leak credentials or SQL details to the browser in production
        if (APP_ENV === 'development') {
            die('Database connection failed: ' . $e->getMessage());
        }
        http_response_code(500);
        die('A database error occurred. Please try again later.');
    }

    return $pdo;
}
