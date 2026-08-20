<?php
/**
 * Lead Engine — weekly pipeline run (spec §3).
 *
 * Sources new prospects, audits and scores them, finds contacts, and writes
 * drafts. It stops there: nothing is approved and nothing is sent. A human does
 * that from /admin/lead-engine/queue.
 *
 * Cron (Sunday 06:00):
 *   0 6 * * 0 php /path/to/bin/lead-engine-pipeline.php
 *
 * Flags:
 *   --no-sourcing        process the existing backlog only, no Places calls
 *   --limit=N            per-stage cap (default 25)
 *   --prospect=ID        re-run one prospect end to end
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
use App\LeadEngine\LeadEngineConfig;
use App\Services\LeadEnginePipeline;

$args = $argv ?? [];
$withSourcing = !in_array('--no-sourcing', $args, true);
$limit = 25;
$prospectId = 0;
foreach ($args as $arg) {
    if (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
        $limit = max(1, (int) $m[1]);
    }
    if (preg_match('/^--prospect=(\d+)$/', $arg, $m)) {
        $prospectId = (int) $m[1];
    }
}

// Crawling other people's sites is a real-world side effect; make the kill
// switch effective for cron too, not just the panel.
if (!LeadEngineConfig::bool('pipeline_enabled')) {
    echo "Pipeline is disabled (PIPELINE_ENABLED / panel setting). Nothing to do.\n";
    exit(0);
}

$pipeline = new LeadEnginePipeline();

try {
    if ($prospectId > 0) {
        $result = $pipeline->reprocess($prospectId);
        echo "Prospect {$prospectId}: " . ($result['ok'] ? "drafted (draft #{$result['draft_id']})" : 'no draft — ' . ($result['reason'] ?? 'see log')) . "\n";
        foreach ($result['log'] ?? [] as $entry) {
            echo '  ' . json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        }
        exit($result['ok'] ? 0 : 1);
    }

    $result = $pipeline->run('cron', $withSourcing, $limit);
    $c = $result['counters'];

    printf(
        "Run #%d complete.\n"
        . "  sourced:           %d\n"
        . "  skipped duplicate: %d\n"
        . "  skipped DNC:       %d\n"
        . "  audited:           %d\n"
        . "  below threshold:   %d\n"
        . "  enriched:          %d\n"
        . "  drafted:           %d\n"
        . "  errors:            %d\n",
        $result['run_id'],
        $c['sourced'] ?? 0, $c['skipped_duplicate'] ?? 0, $c['skipped_dnc'] ?? 0,
        $c['audited'] ?? 0, $c['below_threshold'] ?? 0, $c['enriched'] ?? 0,
        $c['drafted'] ?? 0, $c['errors'] ?? 0
    );

    if (($c['drafted'] ?? 0) > 0) {
        echo "\n{$c['drafted']} draft(s) waiting for approval. Nothing was sent.\n";
    }

    exit(($c['errors'] ?? 0) > 0 ? 1 : 0);
} catch (\Throwable $e) {
    Logger::error('lead-engine-pipeline: fatal', [
        'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),
    ]);
    fwrite(STDERR, 'Pipeline failed: ' . $e->getMessage() . "\n");
    exit(1);
}
