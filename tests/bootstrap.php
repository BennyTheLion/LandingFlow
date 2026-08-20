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

// Lead Engine: a fixed 64-char secret so approval-token tests are deterministic.
// No live API keys — the engine's fallback paths are what we exercise here.
define('APPROVAL_TOKEN_SECRET', str_repeat('t3st-secret-', 5) . 'abcd');
define('GOOGLE_PLACES_API_KEY', '');
define('PAGESPEED_API_KEY', '');
define('ADMIN_NOTIFY_EMAIL', 'test@example.com');
define('EMAIL_FROM', 'noreply@example.com');
define('OUTREACH_SENDER_IDENTITY', 'LandingFlow Test');
define('OUTREACH_UNSUBSCRIBE_URL', 'https://example.com/data-deletion');
define('MAX_DAILY_SENDS', 8);
define('SEND_WINDOW_START', '09:00');
define('SEND_WINDOW_END', '18:00');
define('HOT_SCORE_THRESHOLD', 55);
define('PIPELINE_ENABLED', true);

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

    // --- Lead Engine tables (database/migrations/2026_08_19_lead_engine.sql) ---
    $db->exec("CREATE TABLE IF NOT EXISTS prospects (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        business_name TEXT NOT NULL,
        domain TEXT NOT NULL UNIQUE,
        url TEXT NOT NULL,
        niche TEXT,
        city TEXT,
        source TEXT DEFAULT 'manual',
        source_ref TEXT,
        phone TEXT,
        email TEXT,
        contact_name TEXT,
        contact_role TEXT,
        spends_on_ads INTEGER DEFAULT 0,
        broken_form INTEGER DEFAULT 0,
        status TEXT DEFAULT 'new',
        hot_score INTEGER,
        primary_issue TEXT,
        last_audit_at TEXT,
        crm_lead_id INTEGER,
        notes TEXT,
        created_at TEXT DEFAULT (datetime('now')),
        updated_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY (crm_lead_id) REFERENCES leads(id) ON DELETE SET NULL
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS prospect_audits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        prospect_id INTEGER NOT NULL,
        run_at TEXT DEFAULT (datetime('now')),
        perf_mobile INTEGER,
        perf_desktop INTEGER,
        seo_score INTEGER,
        a11y_score INTEGER,
        security_score INTEGER,
        has_ssl INTEGER DEFAULT 0,
        has_accessibility_statement INTEGER DEFAULT 0,
        has_analytics INTEGER DEFAULT 0,
        has_meta_pixel INTEGER DEFAULT 0,
        has_click_to_call INTEGER DEFAULT 0,
        mobile_viewport_ok INTEGER DEFAULT 0,
        contact_form_found INTEGER DEFAULT 0,
        hot_score INTEGER,
        primary_issue TEXT,
        perf_source TEXT DEFAULT 'heuristic',
        fetch_ok INTEGER DEFAULT 1,
        raw_json TEXT,
        FOREIGN KEY (prospect_id) REFERENCES prospects(id) ON DELETE CASCADE
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS outreach_drafts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        prospect_id INTEGER NOT NULL,
        audit_id INTEGER,
        channel TEXT DEFAULT 'email',
        subject TEXT,
        body TEXT,
        video_brief TEXT,
        video_url TEXT,
        status TEXT DEFAULT 'draft',
        approval_token TEXT,
        token_expires_at TEXT,
        token_used_at TEXT,
        followup_of INTEGER,
        followup_step INTEGER DEFAULT 0,
        generated_by TEXT DEFAULT 'template',
        rejected_reason TEXT,
        approved_at TEXT,
        sent_at TEXT,
        created_at TEXT DEFAULT (datetime('now')),
        updated_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY (prospect_id) REFERENCES prospects(id) ON DELETE CASCADE
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS outreach_events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        draft_id INTEGER NOT NULL,
        type TEXT NOT NULL,
        at TEXT DEFAULT (datetime('now')),
        meta_json TEXT,
        FOREIGN KEY (draft_id) REFERENCES outreach_drafts(id) ON DELETE CASCADE
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS do_not_contact (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        domain TEXT,
        email TEXT,
        phone TEXT,
        reason TEXT,
        added_at TEXT DEFAULT (datetime('now'))
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS lead_engine_runs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        \"trigger\" TEXT DEFAULT 'manual',
        started_at TEXT DEFAULT (datetime('now')),
        finished_at TEXT,
        sourced INTEGER DEFAULT 0,
        skipped_duplicate INTEGER DEFAULT 0,
        skipped_dnc INTEGER DEFAULT 0,
        audited INTEGER DEFAULT 0,
        below_threshold INTEGER DEFAULT 0,
        enriched INTEGER DEFAULT 0,
        drafted INTEGER DEFAULT 0,
        errors INTEGER DEFAULT 0,
        log_json TEXT
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS lead_engine_settings (
        key TEXT PRIMARY KEY,
        value TEXT,
        updated_at TEXT DEFAULT (datetime('now'))
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
    // Lead Engine tables first — prospects.crm_lead_id references leads
    foreach (['outreach_events', 'outreach_drafts', 'prospect_audits', 'prospects',
              'do_not_contact', 'lead_engine_runs', 'lead_engine_settings'] as $table) {
        $db->exec("DELETE FROM $table");
    }
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
