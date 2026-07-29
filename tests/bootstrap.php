<?php
/**
 * Test bootstrap - loads framework with SQLite for isolated testing.
 */

// Prevent output buffering issues and header errors in CLI
define('TEST_MODE', true);

// Framework paths
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('PUBLIC_PATH', BASE_PATH . '/public');

// Test-specific config
define('APP_NAME', 'LandingFlow-Test');
define('APP_URL', 'http://localhost');
define('APP_ENV', 'testing');
define('APP_DEBUG', true);
define('APP_TIMEZONE', 'Asia/Jerusalem');

// SQLite for tests
define('DB_DSN', 'sqlite:' . __DIR__ . '/test.db');
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'test');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8');
define('DB_COLLATION', '');

// Security
define('SESSION_LIFETIME', 86400);
define('CSRF_TOKEN_NAME', 'csrf_token');
define('RATE_LIMIT_MAX', 100);
define('RATE_LIMIT_WINDOW', 60);

// Test-specific: use temp session directory
define('LOG_PATH', STORAGE_PATH . '/logs/');

// Load autoloader
require_once APP_PATH . '/core/Autoloader.php';
\App\Core\Autoloader::register();

// Start session in CLI-safe mode
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Create SQLite schema
function createTestSchema(): void
{
    $db = new PDO(DB_DSN);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA foreign_keys=ON');

    $db->exec("CREATE TABLE IF NOT EXISTS roles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        slug TEXT NOT NULL UNIQUE,
        description TEXT,
        created_at TEXT DEFAULT (datetime('now')),
        updated_at TEXT DEFAULT (datetime('now'))
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        phone TEXT,
        password TEXT NOT NULL,
        role_id INTEGER,
        avatar TEXT,
        status TEXT DEFAULT 'active',
        last_login_at TEXT,
        last_login_ip TEXT,
        email_verified_at TEXT,
        remember_token TEXT,
        created_at TEXT DEFAULT (datetime('now')),
        updated_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS email_verification_tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        token TEXT NOT NULL,
        created_at TEXT DEFAULT (datetime('now')),
        expires_at TEXT NOT NULL,
        used INTEGER DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL,
        token TEXT NOT NULL,
        created_at TEXT DEFAULT (datetime('now')),
        expires_at TEXT NOT NULL,
        used INTEGER DEFAULT 0
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS leads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT,
        phone TEXT,
        company TEXT,
        website TEXT,
        source TEXT DEFAULT 'website',
        source_detail TEXT,
        status TEXT DEFAULT 'new',
        score INTEGER DEFAULT 0,
        interest TEXT,
        budget REAL,
        notes TEXT,
        assigned_to INTEGER,
        consent_given INTEGER DEFAULT 0,
        consent_date TEXT,
        created_at TEXT DEFAULT (datetime('now')),
        updated_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
    )");

        $db->exec("CREATE TABLE IF NOT EXISTS audit_reports (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lead_id INTEGER,
        url TEXT NOT NULL,
        overall_score REAL,
        seo_score REAL,
        performance_score REAL,
        security_score REAL,
        accessibility_score REAL,
        legal_score REAL,
        total_checks INTEGER DEFAULT 0,
        passed_checks INTEGER DEFAULT 0,
        failed_checks INTEGER DEFAULT 0,
        full_report TEXT,
        recommendations TEXT,
        status TEXT DEFAULT 'pending',
        ip_address TEXT,
        user_agent TEXT,
        created_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL
    )");

$db->exec("CREATE TABLE IF NOT EXISTS lead_notes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lead_id INTEGER NOT NULL,
        user_id INTEGER,
        content TEXT NOT NULL,
        type TEXT DEFAULT 'note',
        created_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");

// Seed roles
    $db->exec("INSERT OR IGNORE INTO roles (id, name, slug) VALUES (1, 'admin', 'admin')");
    $db->exec("INSERT OR IGNORE INTO roles (id, name, slug) VALUES (2, 'staff', 'staff')");
    $db->exec("INSERT OR IGNORE INTO roles (id, name, slug) VALUES (3, 'client', 'client')");

    // Reset Database singleton to use test connection
    $reflection = new ReflectionClass(\App\Core\Database::class);
    $instanceProp = $reflection->getProperty('instance');
    $instanceProp->setAccessible(true);
    $instanceProp->setValue(null, null);
}

createTestSchema();

// Reset DB singleton between tests
function resetDatabase(): void
{
    $db = new PDO(DB_DSN);
    $db->exec('DELETE FROM password_resets');
    $db->exec('DELETE FROM leads');
    $db->exec('DELETE FROM users');
    $db->exec("INSERT OR IGNORE INTO roles (id, name, slug) VALUES (1, 'admin', 'admin')");
    $db->exec("INSERT OR IGNORE INTO roles (id, name, slug) VALUES (2, 'staff', 'staff')");
    $db->exec("INSERT OR IGNORE INTO roles (id, name, slug) VALUES (3, 'client', 'client')");

    $reflection = new ReflectionClass(\App\Core\Database::class);
    $instanceProp = $reflection->getProperty('instance');
    $instanceProp->setAccessible(true);
    $instanceProp->setValue(null, null);
}

// Clean up on shutdown
register_shutdown_function(function () {
    $dbFile = __DIR__ . '/test.db';
    if (file_exists($dbFile)) {
        @unlink($dbFile);
    }
});
