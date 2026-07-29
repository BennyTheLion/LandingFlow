<?php
/**
 * LandingFlow - Application Configuration Loader
 * 
 * Loads environment-specific configuration.
 */

// Load environment-specific config (or fallback to example)
$envFile = __DIR__ . '/.env.php';
if (file_exists($envFile)) {
    require_once $envFile;
} else {
    // Load defaults from example config only when no .env.php exists
    require_once __DIR__ . '/config.example.php';
}

// Environment-specific runtime settings
if (defined('APP_ENV') && APP_ENV === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

if (defined('APP_ENV') && APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
}
