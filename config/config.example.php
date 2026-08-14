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
