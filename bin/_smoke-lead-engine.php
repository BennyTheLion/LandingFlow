<?php
/**
 * Temporary smoke test — exercises the Lead Engine against the real MySQL
 * database with no network calls. Delete after running.
 */

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH', BASE_PATH . '/storage');

require_once CONFIG_PATH . '/loader.php';
require_once BASE_PATH . '/vendor/autoload.php';
require_once APP_PATH . '/core/Autoloader.php';
\App\Core\Autoloader::register();

use App\LeadEngine\ApprovalToken;
use App\LeadEngine\AuditResult;
use App\LeadEngine\DraftWriter;
use App\LeadEngine\HotScore;
use App\LeadEngine\LeadEngineConfig;
use App\LeadEngine\SendGuard;
use App\Repositories\DoNotContactRepository;
use App\Repositories\LeadEngineRepository;
use App\Repositories\OutreachRepository;
use App\Repositories\ProspectRepository;
use App\Services\LeadEngineDigest;
use App\Services\LeadEnginePipeline;
use App\Services\OutreachSender;

$pass = 0; $fail = 0;
function check(string $label, bool $ok, string $detail = ''): void {
    global $pass, $fail;
    if ($ok) { $pass++; echo "  OK   $label\n"; }
    else { $fail++; echo "  FAIL $label" . ($detail ? " -- $detail" : '') . "\n"; }
}

echo "=== Lead Engine smoke test (real MySQL, no network) ===\n\n";

$prospects = new ProspectRepository();
$outreach  = new OutreachRepository();
$dnc       = new DoNotContactRepository();
$engine    = new LeadEngineRepository();
$pipeline  = new LeadEnginePipeline();

// Clean any leftovers from a previous smoke run
foreach (['smoke-dental.co.il', 'smoke-blocked.co.il'] as $d) {
    if ($row = $prospects->findByDomain($d)) { $prospects->delete((int) $row['id']); }
}
\App\Core\Database::getInstance()->getConnection()
    ->exec("DELETE FROM do_not_contact WHERE reason LIKE 'smoke:%'");

echo "-- config resolution --\n";
check('settings load from DB', LeadEngineConfig::int('hot_score_threshold') === 55,
      'got ' . LeadEngineConfig::int('hot_score_threshold'));
check('pipeline seeded disabled', LeadEngineConfig::bool('pipeline_enabled') === false);
check('sending seeded halted', LeadEngineConfig::bool('sending_halted') === true);
check('active cities parsed', count(LeadEngineConfig::list('active_cities')) === 4);

echo "\n-- stage 1: sourcing + dedup --\n";
$add = $pipeline->addProspect([
    'business_name' => 'מרפאת שיניים סמוק',
    'url'           => 'https://www.smoke-dental.co.il/',
    'niche'         => 'dental_clinic',
    'city'          => 'תל אביב',
    'email'         => 'yossi@smoke-dental.co.il',
    'contact_name'  => 'יוסי כהן',
    'spends_on_ads' => 1,
    'source'        => 'manual',
]);
check('prospect created', $add['created'] === true, $add['reason']);
$pid = $add['prospect_id'];

$dup = $pipeline->addProspect(['business_name' => 'dup', 'url' => 'http://smoke-dental.co.il/contact']);
check('duplicate domain rejected', $dup['created'] === false && $dup['reason'] === 'duplicate', $dup['reason']);

$dnc->add('smoke-blocked.co.il', null, null, 'smoke: test block');
$blocked = $pipeline->addProspect(['business_name' => 'blocked', 'url' => 'https://smoke-blocked.co.il']);
check('DNC domain rejected at sourcing', $blocked['reason'] === 'do_not_contact', $blocked['reason']);

echo "\n-- stage 2: scoring + audit persistence --\n";
$audit = new AuditResult();
$audit->url = 'https://smoke-dental.co.il';
$audit->fetchOk = true; $audit->httpStatus = 200; $audit->responseTimeMs = 3100;
$audit->perfMobile = 31; $audit->perfDesktop = 58;
$audit->a11yScore = 44; $audit->seoScore = 61; $audit->securityScore = 55;
$audit->hasSsl = true; $audit->mobileViewportOk = true; $audit->contactFormFound = true;
$audit->hasAnalytics = false; $audit->hasMetaPixel = false; $audit->hasClickToCall = false;
$audit->hasAccessibilityStatement = false;
$audit->perfSource = 'heuristic';
$audit->hotScore = HotScore::compute($audit, true);
$audit->primaryIssue = HotScore::primaryIssue($audit, true, false);

check('hot_score computed with ads bonus', $audit->hotScore === 100, (string) $audit->hotScore);
check('primary issue is no_analytics_with_ads', $audit->primaryIssue === 'no_analytics_with_ads', $audit->primaryIssue);

$auditId = $prospects->saveAudit($pid, $audit);
check('audit row persisted', $auditId > 0);
$reloaded = $prospects->findById($pid);
check('hot_score denormalized onto prospect', (int) $reloaded['hot_score'] === 100);
check('primary_issue denormalized', $reloaded['primary_issue'] === 'no_analytics_with_ads');
check('last_audit_at set', !empty($reloaded['last_audit_at']));

$roundTrip = LeadEnginePipeline::auditFromRow($prospects->latestAudit($pid));
check('AuditResult round-trips from raw_json', $roundTrip->perfMobile === 31 && $roundTrip->a11yScore === 44
      && $roundTrip->hasAnalytics === false && $roundTrip->perfSource === 'heuristic');

echo "\n-- stage 4: draft + video brief --\n";
$written = (new DraftWriter())->write($reloaded, $roundTrip, 'email');
check('template path used (no AI key)', $written['generated_by'] === 'template', $written['generated_by']);
check('subject mentions the business', str_contains($written['subject'], 'סמוק'), $written['subject']);
check('body opens with first name', str_contains($written['body'], 'היי יוסי'));
check('video brief has timings + tabs',
      str_contains($written['video_brief'], '0:00-0:10') && str_contains($written['video_brief'], 'טאבים'));
check('heuristic perf flagged in brief', str_contains($written['video_brief'], 'הערכה מקומית'));

$draftId = $outreach->createDraft([
    'prospect_id' => $pid, 'audit_id' => $auditId, 'channel' => 'email',
    'subject' => $written['subject'], 'body' => $written['body'],
    'video_brief' => $written['video_brief'], 'status' => 'draft',
    'generated_by' => $written['generated_by'],
]);
check('draft persisted', $draftId > 0);
$prospects->setStatus($pid, 'drafted');

echo "\n-- queue join + approval token --\n";
$ctx = $outreach->findDraftWithContext($draftId);
check('draft context join works', $ctx !== null && $ctx['business_name'] === 'מרפאת שיניים סמוק'
      && (int) $ctx['perf_mobile'] === 31);
$queue = $outreach->queue(50);
check('draft appears in the approval queue',
      count(array_filter($queue, fn($d) => (int) $d['id'] === $draftId)) === 1);

$tok = ApprovalToken::issue($draftId);
$outreach->updateDraft($draftId, [
    'approval_token' => $tok['hash'], 'token_expires_at' => $tok['expires_at'], 'token_used_at' => null,
]);
$hash = ApprovalToken::hashFor($draftId, $tok['token']);
check('token consumed once', $outreach->consumeToken($draftId, $hash) === true);
check('token replay rejected', $outreach->consumeToken($draftId, $hash) === false);

echo "\n-- stage 5: guardrails --\n";
$ctx = $outreach->findDraftWithContext($draftId);
$guard = new SendGuard($outreach, $dnc);
$wed = new DateTimeImmutable('2026-08-19 12:00:00', new DateTimeZone('Asia/Jerusalem'));

$g1 = $guard->check($ctx, $wed);
check('halt switch blocks send', $g1['allowed'] === false
      && count(array_filter($g1['blockers'], fn($b) => str_contains($b, 'מוקפאות'))) === 1);
check('missing video also blocks', count(array_filter($g1['blockers'], fn($b) => str_contains($b, 'סרטון'))) === 1);

// Lift the halt in memory only, to verify the rest of the guard chain
$engine->setSetting('sending_halted', '0');
LeadEngineConfig::flush();
$outreach->updateDraft($draftId, ['video_url' => 'https://www.loom.com/share/smoke123']);
$ctx = $outreach->findDraftWithContext($draftId);

$g2 = $guard->check($ctx, $wed);
check('complete draft in-window passes', $g2['allowed'] === true, implode(' | ', $g2['blockers']));
check('heuristic perf raises a warning',
      count(array_filter($g2['warnings'], fn($w) => str_contains($w, 'PageSpeed'))) === 1);

$g3 = $guard->check($ctx, new DateTimeImmutable('2026-08-21 12:00:00', new DateTimeZone('Asia/Jerusalem')));
check('Friday blocked', $g3['allowed'] === false);

$approve = (new OutreachSender())->approve($draftId);
check('draft approvable with video', $approve['approved'] === true, implode(' | ', $approve['blockers']));
check('prospect status advanced to approved', $prospects->findById($pid)['status'] === 'approved');

echo "\n-- final body composition (§11.2) --\n";
$body = (new OutreachSender())->composeBody($outreach->findDraftWithContext($draftId));
check('video link in outgoing body', str_contains($body, 'loom.com/share/smoke123'));
check('sender identity appended', str_contains($body, 'LandingFlow'));
check('opt-out appended', str_contains($body, 'הסר'));

echo "\n-- digest (dry run, sends nothing) --\n";
// The draft above is now 'approved', and the digest only reports 'draft' rows,
// so seed one pending draft for this check.
$pendingDraftId = $outreach->createDraft([
    'prospect_id' => $pid, 'audit_id' => $auditId, 'channel' => 'email',
    'subject' => 'טיוטה ממתינה', 'body' => 'גוף', 'video_brief' => 'תסריט',
    'status' => 'draft', 'generated_by' => 'template',
]);
$digest = (new LeadEngineDigest())->sendDaily(true);
check('digest built as dry run', $digest['sent'] === false && $digest['reason'] === 'dry_run', $digest['reason']);
check('digest counted the pending draft', $digest['count'] >= 1, (string) $digest['count']);
check('digest links to the confirm page, not a send URL',
      str_contains($digest['body'], '/confirm?token=') && !str_contains($digest['body'], '/send'));
check('digest states that email links never send',
      str_contains($digest['body'], 'לא שולח הודעה'));

echo "\n-- reply -> CRM promotion --\n";
(new OutreachSender())->recordReply($pid, 'smoke test reply');
$after = $prospects->findById($pid);
check('prospect marked replied', $after['status'] === 'replied');
check('promoted to a CRM lead', !empty($after['crm_lead_id']));

echo "\n-- run log --\n";
$runId = $engine->startRun('manual');
$engine->finishRun($runId, ['sourced' => 3, 'audited' => 2, 'drafted' => 1], [['stage' => 'smoke']]);
$run = $engine->findRun($runId);
check('run log written', $run !== null && (int) $run['sourced'] === 3 && !empty($run['finished_at']));

// --- cleanup ---
echo "\n-- cleanup --\n";
if (!empty($after['crm_lead_id'])) {
    \App\Core\Database::getInstance()->getConnection()
        ->prepare("DELETE FROM leads WHERE id = ?")->execute([(int) $after['crm_lead_id']]);
}
$prospects->delete($pid);
$conn = \App\Core\Database::getInstance()->getConnection();
$conn->exec("DELETE FROM do_not_contact WHERE reason LIKE 'smoke:%'");
$conn->prepare("DELETE FROM lead_engine_runs WHERE id = ?")->execute([$runId]);
$engine->setSetting('sending_halted', '1');
check('test data removed', $prospects->findByDomain('smoke-dental.co.il') === null);
check('halt switch restored', (function () { LeadEngineConfig::flush(); return LeadEngineConfig::bool('sending_halted'); })());

echo "\n=== $pass passed, $fail failed ===\n";
exit($fail > 0 ? 1 : 0);
