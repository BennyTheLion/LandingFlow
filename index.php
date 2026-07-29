<?php
/**
 * LandingFlow - Application Entry Point
 *
 * Front controller that handles all requests.
 */

// Define base path
define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH', BASE_PATH . '/storage');

// Load configuration
require_once CONFIG_PATH . '/loader.php';

// Composer autoloader (for PHPMailer etc.)
require_once BASE_PATH . '/vendor/autoload.php';

// Autoloader
require_once APP_PATH . '/core/Autoloader.php';
\App\Core\Autoloader::register();

// Start session
require_once APP_PATH . '/core/Session.php';
\App\Core\Session::start();

// Error handling
require_once APP_PATH . '/core/ErrorHandler.php';
\App\Core\ErrorHandler::register();

// Run the application
require_once APP_PATH . '/core/App.php';
$app = new \App\Core\App();
$app->run();
