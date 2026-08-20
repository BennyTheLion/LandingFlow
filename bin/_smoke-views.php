<?php
/**
 * Temporary smoke test — renders every Lead Engine admin view through the real
 * controller to catch view-level errors. Delete after running.
 */

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH', BASE_PATH . '/storage');

require_once CONFIG_PATH . '/loader.php';
require_once BASE_PATH . '/vendor/autoload.php';
require_once APP_PATH . '/core/Autoloader.php';
\App\Core\Autoloader::register();

// Fail loudly on notices/warnings so a bad array key in a view is not silent
set_error_handler(function ($no, $str, $file, $line) {
    throw new ErrorException($str, 0, $no, $file, $line);
});

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_URI'] = '/admin/lead-engine';
@session_start();
\App\Core\Session::set('user', ['id' => 1, 'name' => 'Smoke', 'role_id' => 1]);

use App\Repositories\OutreachRepository;
use App\Repositories\ProspectRepository;

$prospects = new ProspectRepository();
$outreach = new OutreachRepository();

// Seed one prospect with an audit and a draft so the views have real rows
if ($old = $prospects->findByDomain('viewsmoke.co.il')) {
    $prospects->delete((int) $old['id']);
}
$pid = $prospects->create([
    'business_name' => 'עסק תצוגה', 'domain' => 'viewsmoke.co.il',
    'url' => 'https://viewsmoke.co.il', 'niche' => 'dental_clinic', 'city' => 'חיפה',
    'email' => 'a@viewsmoke.co.il', 'contact_name' => 'דנה לוי',
    'spends_on_ads' => 1, 'status' => 'drafted', 'source' => 'manual',
]);

$audit = new \App\LeadEngine\AuditResult();
$audit->url = 'https://viewsmoke.co.il';
$audit->fetchOk = true; $audit->perfMobile = 34; $audit->a11yScore = 51;
$audit->seoScore = 62; $audit->securityScore = 48; $audit->hasSsl = true;
$audit->hotScore = 77; $audit->primaryIssue = 'slow_mobile'; $audit->perfSource = 'heuristic';
$auditId = $prospects->saveAudit($pid, $audit);

$draftId = $outreach->createDraft([
    'prospect_id' => $pid, 'audit_id' => $auditId, 'channel' => 'email',
    'subject' => 'נושא לבדיקה', 'body' => "היי דנה,\n\nבדקתי את האתר.",
    'video_brief' => "עסק: עסק תצוגה\n0:00-0:10 פתיחה", 'status' => 'draft',
    'generated_by' => 'template',
]);
$outreach->addEvent($draftId, 'sent', ['to' => 'a@viewsmoke.co.il']);

$pass = 0; $fail = 0;
$controller = new \App\Controllers\LeadEngineController(new \App\Core\Request());

$pages = [
    'dashboard'       => fn() => $controller->dashboard(),
    'queue'           => fn() => $controller->queue(),
    'prospects'       => fn() => $controller->prospects(),
    'prospect card'   => fn() => $controller->showProspect((string) $pid),
    'runs'            => fn() => $controller->runs(),
    'settings'        => fn() => $controller->settings(),
    'confirm (no token)' => fn() => $controller->confirmDraft((string) $draftId),
];

foreach ($pages as $label => $render) {
    try {
        $html = $render();
        $len = strlen($html);
        $ok = $len > 2000 && str_contains($html, '</html>');
        if ($ok) { $pass++; printf("  OK   %-20s %6d bytes\n", $label, $len); }
        else { $fail++; printf("  FAIL %-20s %6d bytes (truncated or not HTML)\n", $label, $len); }
    } catch (\Throwable $e) {
        $fail++;
        printf("  FAIL %-20s %s\n         at %s:%d\n", $label, $e->getMessage(),
            basename($e->getFile()), $e->getLine());
    }
}

// Cleanup
$prospects->delete($pid);
printf("\n=== %d rendered, %d failed ===\n", $pass, $fail);
exit($fail > 0 ? 1 : 0);
