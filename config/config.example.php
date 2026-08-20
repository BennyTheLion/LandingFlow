<?php
/**
 * LandingFlow - Environment Configuration
 * 
 * Copy this file to .env and update the values for your environment.
 */

// Application
define('APP_NAME', 'LandingFlow');
define('APP_URL', 'https://landingflow.co.il');
define('APP_ENV', 'production'); // development, production
define('APP_DEBUG', false);
define('APP_TIMEZONE', 'UTC');
define('APP_LOCALE', 'en_US');

// Database
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'landingflow');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');

// Security
define('SESSION_LIFETIME', 86400); // 24 hours
define('CSRF_TOKEN_NAME', 'csrf_token');
define('RATE_LIMIT_MAX', 100); // requests per minute
define('RATE_LIMIT_WINDOW', 60); // seconds

// Mail
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', '587');
define('SMTP_USER', 'noreply@landingflow.co.il');
define('SMTP_PASS', '');
define('SMTP_FROM', 'noreply@landingflow.co.il');
define('SMTP_FROM_NAME', 'LandingFlow');

// Monitoring alerts + weekly digest recipient
define('ALERT_EMAIL', 'noreply@landingflow.co.il');

// Upload
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB

// Logging
define('LOG_LEVEL', 'info'); // debug, info, warning, error
define('LOG_PATH', __DIR__ . '/../storage/logs/');

// AI / LLM (OpenAI-compatible API)
define('AI_ENABLED', false);
define('AI_PROVIDER', 'openai');
define('AI_API_KEY', '');
define('AI_API_URL', 'https://api.openai.com/v1/chat/completions');
define('AI_MODEL', 'gpt-4o-mini');
define('AI_MAX_TOKENS', 4096);

// ---------------------------------------------------------------------------
// Lead Engine (docs/lead-engine-spec.md)
//
// The engine degrades gracefully: with no keys at all you still get manual and
// CSV entry, heuristic scoring, template drafts, and the full approval flow.
// APPROVAL_TOKEN_SECRET is the one hard requirement for the approval flow.
// ---------------------------------------------------------------------------

// Stage 1 sourcing — leave empty to disable automatic Google Places sourcing
define('GOOGLE_PLACES_API_KEY', '');

// Stage 2 scoring — without a key, perf_mobile is a local heuristic, and the
// panel labels it as such so it is never quoted as a PageSpeed number
define('PAGESPEED_API_KEY', '');

// Stage 5 approval tokens — REQUIRED. Generate with:
//   php -r "echo bin2hex(random_bytes(32));"
// Must be at least 32 characters; the engine refuses to issue tokens otherwise.
define('APPROVAL_TOKEN_SECRET', '');

// Where the daily approval digest is sent (falls back to ALERT_EMAIL)
define('ADMIN_NOTIFY_EMAIL', '');

// From address on outbound prospect email (falls back to SMTP_FROM)
define('EMAIL_FROM', 'noreply@landingflow.co.il');

// Identified sender + opt-out, appended to every outbound message (spec §11.2)
define('OUTREACH_SENDER_IDENTITY', 'LandingFlow — landingflow.co.il');
define('OUTREACH_UNSUBSCRIBE_URL', '');

// Send guardrails (spec §9). The admin panel can override these at runtime;
// these are the deployment baseline.
define('MAX_DAILY_SENDS', 8);
define('SEND_WINDOW_START', '09:00');
define('SEND_WINDOW_END', '18:00');
define('HOT_SCORE_THRESHOLD', 55);
define('PIPELINE_ENABLED', true);
