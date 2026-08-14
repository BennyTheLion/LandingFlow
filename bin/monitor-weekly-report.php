<?php
/**
 * Sends the weekly monitoring digest email — meant to be invoked from a cron job,
 * e.g. Monday mornings: `0 8 * * 1 /usr/local/bin/php /path/to/bin/monitor-weekly-report.php`
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH', BASE_PATH . '/storage');

require_once CONFIG_PATH . '/loader.php';
require_once BASE_PATH . '/vendor/autoload.php';
require_once APP_PATH . '/core/Autoloader.php';
\App\Core\Autoloader::register();

use App\Services\MonitoringReportService;

$sent = (new MonitoringReportService())->sendWeeklyDigest();
echo $sent ? "Weekly digest sent.\n" : "Weekly digest not sent (no sites or no recipient configured).\n";
