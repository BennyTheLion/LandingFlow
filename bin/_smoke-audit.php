<?php
/**
 * Temporary smoke test — runs AuditEngine against a real site over the network.
 * Defaults to landingflow.co.il (our own site). Delete after running.
 *
 * Usage: php bin/_smoke-audit.php [url]
 */

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH', BASE_PATH . '/storage');

require_once CONFIG_PATH . '/loader.php';
require_once BASE_PATH . '/vendor/autoload.php';
require_once APP_PATH . '/core/Autoloader.php';
\App\Core\Autoloader::register();

use App\LeadEngine\DraftWriter;
use App\LeadEngine\HotScore;
use App\Services\AuditEngine;

$url = $argv[1] ?? 'https://landingflow.co.il';

echo "Auditing {$url} ...\n";
$start = microtime(true);
$audit = (new AuditEngine())->runAudit($url, false, false);
$elapsed = round(microtime(true) - $start, 1);

printf("\nfetch_ok=%s  http=%d  time=%dms  (engine took %ss)\n",
    $audit->fetchOk ? 'yes' : 'NO', $audit->httpStatus, $audit->responseTimeMs, $elapsed);

if (!$audit->fetchOk) {
    echo "Issues: " . implode(' | ', $audit->issues) . "\n";
    exit(1);
}

printf("\nScores        perf_mobile=%s (%s)  a11y=%d  seo=%d  security=%d\n",
    $audit->perfMobile ?? '?', $audit->perfSource,
    $audit->a11yScore, $audit->seoScore, $audit->securityScore);

printf("Signals       ssl=%s  analytics=%s  pixel=%s  tel=%s  viewport=%s  a11y_stmt=%s  form=%s\n",
    $audit->hasSsl ? 'Y' : 'n',
    $audit->hasAnalytics ? 'Y' : 'n',
    $audit->hasMetaPixel ? 'Y' : 'n',
    $audit->hasClickToCall ? 'Y' : 'n',
    $audit->mobileViewportOk ? 'Y' : 'n',
    $audit->hasAccessibilityStatement ? 'Y' : 'n',
    $audit->contactFormFound ? 'Y' : 'n');

printf("\nhot_score=%d   primary_issue=%s (%s)\n",
    $audit->hotScore, $audit->primaryIssue, HotScore::issueLabel($audit->primaryIssue));

echo "\nScore breakdown:\n";
foreach (HotScore::breakdown($audit) as $label => $points) {
    printf("  %-22s %5.1f\n", $label, $points);
}

echo "\nIssues found:\n";
foreach ($audit->issues as $issue) {
    echo "  - $issue\n";
}

echo "\nScanners populated: " . implode(', ', array_keys($audit->raw)) . "\n";

// Verify the draft path works off a real audit
$prospect = [
    'business_name' => 'LandingFlow',
    'domain'        => \App\LeadEngine\PoliteFetcher::domainKey($url),
    'contact_name'  => 'דני',
    'niche'         => 'agency',
    'city'          => 'תל אביב',
    'spends_on_ads' => 0,
];
$written = (new DraftWriter())->write($prospect, $audit, 'email');

echo "\n--- generated draft (" . $written['generated_by'] . ") ---\n";
echo "Subject: " . $written['subject'] . "\n\n" . $written['body'] . "\n";
echo "\n--- video brief ---\n" . $written['video_brief'] . "\n";
