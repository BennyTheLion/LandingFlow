<?php
/**
 * Lead Engine — daily approval digest (spec §9).
 *
 * Queues any due follow-ups as drafts, then emails the pending approval queue
 * to ADMIN_NOTIFY_EMAIL. The only recipient is the operator; no prospect
 * receives anything from this script.
 *
 * Cron (Sun-Thu 08:00):
 *   0 8 * * 0-4 php /path/to/bin/lead-engine-digest.php
 *
 * Flags:
 *   --dry-run    build and print the email without sending it
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

use App\Core\Logger;
use App\Services\LeadEngineDigest;

$dryRun = in_array('--dry-run', $argv ?? [], true);

try {
    $digest = new LeadEngineDigest();

    $followups = $digest->queueFollowups();
    if ($followups > 0) {
        echo "Queued {$followups} follow-up draft(s) for approval.\n";
    }

    $result = $digest->sendDaily($dryRun);

    if ($dryRun) {
        echo "--- DRY RUN, nothing sent ---\n";
        echo "Pending drafts: {$result['count']}\n";
        echo "Subject: {$result['subject']}\n\n";
        echo $result['body'] . "\n";
        exit(0);
    }

    match ($result['reason']) {
        'sent'            => print("Digest sent — {$result['count']} lead(s) awaiting approval.\n"),
        'nothing_pending' => print("No pending drafts. No email sent.\n"),
        'no_recipient'    => print("ADMIN_NOTIFY_EMAIL is not configured. No email sent.\n"),
        default           => fwrite(STDERR, "Digest send failed.\n"),
    };

    exit($result['reason'] === 'send_failed' ? 1 : 0);
} catch (\Throwable $e) {
    Logger::error('lead-engine-digest: fatal', [
        'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),
    ]);
    fwrite(STDERR, 'Digest failed: ' . $e->getMessage() . "\n");
    exit(1);
}
