<?php
/**
 * Runs due website checks and persists results — meant to be invoked from a cron job,
 * e.g. every minute: `* * * * * /usr/local/bin/php /path/to/bin/monitor-run.php`
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

use App\Core\Database;
use App\Core\Logger;
use App\Services\MonitoringService;

$db = Database::getInstance()->getConnection();
$sites = $db->query(
    "SELECT * FROM monitoring_websites
     WHERE status != 'paused'
       AND (last_checked_at IS NULL OR last_checked_at <= NOW() - INTERVAL check_interval MINUTE)"
)->fetchAll(\PDO::FETCH_ASSOC);

$monitor = new MonitoringService();
$checked = 0;

foreach ($sites as $site) {
    try {
        $monitor->runCheckAndPersist($site, $db, 10);
        $checked++;
    } catch (\Throwable $e) {
        Logger::error('monitor-run: check failed', ['website_id' => $site['id'], 'url' => $site['url'], 'message' => $e->getMessage()]);
    }
}

echo "Checked {$checked} of " . count($sites) . " due site(s).\n";
