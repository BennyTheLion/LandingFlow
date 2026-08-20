<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Logger;
use App\Core\Session;
use App\LeadEngine\ApprovalToken;
use App\LeadEngine\CsvImporter;
use App\LeadEngine\GooglePlacesClient;
use App\LeadEngine\HotScore;
use App\LeadEngine\LeadEngineConfig;
use App\LeadEngine\PoliteFetcher;
use App\LeadEngine\SendGuard;
use App\Repositories\DoNotContactRepository;
use App\Repositories\LeadEngineRepository;
use App\Repositories\OutreachRepository;
use App\Repositories\ProspectRepository;
use App\Services\LeadEngineDigest;
use App\Services\LeadEnginePipeline;
use App\Services\OutreachSender;

/**
 * LeadEngineController — the admin panel from spec §10.
 *
 * Routed under /admin/lead-engine (not /admin/leads, which is the existing CRM).
 *
 * The approval flow is deliberately split across two requests, per §9:
 *   GET  .../drafts/{id}/confirm?token=…  → preview only, no side effects
 *   POST .../drafts/{id}/send             → the actual send, CSRF-protected
 * Never collapse these into one handler: mail scanners prefetch GET links.
 */
class LeadEngineController extends Controller
{
    private ProspectRepository $prospects;
    private OutreachRepository $outreach;
    private DoNotContactRepository $dnc;
    private LeadEngineRepository $engine;

    public function __construct(?\App\Core\Request $request = null)
    {
        parent::__construct($request);
        $this->prospects = new ProspectRepository();
        $this->outreach = new OutreachRepository();
        $this->dnc = new DoNotContactRepository();
        $this->engine = new LeadEngineRepository();
    }

    // ------------------------------------------------------------- dashboard

    public function dashboard(): string
    {
        $prospectCounts = $this->prospects->countByStatus();
        $draftCounts = $this->outreach->countByStatus();

        return $this->render('leadengine/dashboard', [
            'pageTitle'      => 'מנוע לידים — LandingFlow',
            'prospectCounts' => $prospectCounts,
            'draftCounts'    => $draftCounts,
            'pendingQueue'   => count(array_filter($this->outreach->queue(50), fn($d) => $d['status'] === 'draft')),
            'sentThisWeek'   => $this->outreach->sentThisWeek(),
            'sentToday'      => $this->outreach->sentTodayCount(),
            'maxDaily'       => LeadEngineConfig::int('max_daily_sends'),
            'replyBySource'  => $this->safe(fn() => $this->outreach->replyRateBy('source')),
            'replyByNiche'   => $this->safe(fn() => $this->outreach->replyRateBy('niche')),
            'dncCount'       => $this->dnc->count(),
            'lastRuns'       => $this->engine->runs(5),
            'sendingHalted'  => LeadEngineConfig::bool('sending_halted'),
            'flashMsg'       => $this->flash(),
        ]);
    }

    // --------------------------------------------------------- approval queue

    public function queue(): string
    {
        $drafts = $this->outreach->queue(50);
        $guard = new SendGuard($this->outreach, $this->dnc);

        // Annotate each row with its live guard verdict so the operator sees why
        // a send button is disabled before clicking it.
        foreach ($drafts as &$draft) {
            $context = $this->outreach->findDraftWithContext((int) $draft['id']);
            $draft['guard'] = $context !== null
                ? $guard->check($context)
                : ['allowed' => false, 'blockers' => ['לא ניתן לטעון את הטיוטה.'], 'warnings' => []];
        }
        unset($draft);

        return $this->render('leadengine/queue', [
            'pageTitle' => 'תור אישורים — מנוע לידים',
            'drafts'    => $drafts,
            'flashMsg'  => $this->flash(),
        ]);
    }

    // ------------------------------------------------------------- prospects

    public function prospects(): string
    {
        $filters = [
            'status'    => $this->request->input('status', ''),
            'niche'     => $this->request->input('niche', ''),
            'source'    => $this->request->input('source', ''),
            'min_score' => $this->request->input('min_score', ''),
            'search'    => $this->request->input('q', ''),
        ];

        return $this->render('leadengine/prospects', [
            'pageTitle'  => 'כל הלידים — מנוע לידים',
            'prospects'  => $this->prospects->search($filters, 300),
            'filters'    => $filters,
            'niches'     => $this->prospects->distinctNiches(),
            'placesOn'   => (new GooglePlacesClient())->isAvailable(),
            'nicheList'  => GooglePlacesClient::NICHE_QUERIES,
            'flashMsg'   => $this->flash(),
        ]);
    }

    public function showProspect(string $id): string
    {
        $prospectId = (int) $id;
        $prospect = $this->prospects->findById($prospectId);
        if ($prospect === null) {
            throw new \App\Core\Exceptions\NotFoundException();
        }

        return $this->render('leadengine/prospect', [
            'pageTitle' => $prospect['business_name'] . ' — מנוע לידים',
            'prospect'  => $prospect,
            'audits'    => $this->prospects->auditHistory($prospectId),
            'drafts'    => $this->outreach->draftsForProspect($prospectId),
            'events'    => $this->outreach->eventsForProspect($prospectId),
            'flashMsg'  => $this->flash(),
        ]);
    }

    /** Manual entry — the Meta Ad Library workflow from §5 source B */
    public function storeProspect(): never
    {
        $this->requireCsrf('admin/lead-engine/prospects');

        $url = PoliteFetcher::normalizeUrl((string) $this->request->input('url', ''));
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            Session::flash('error', 'נא להזין כתובת אתר תקינה.');
            $this->redirect('admin/lead-engine/prospects');
        }

        $result = (new LeadEnginePipeline())->addProspect([
            'business_name' => trim((string) $this->request->input('business_name', '')),
            'url'           => $url,
            'domain'        => PoliteFetcher::domainKey($url),
            'niche'         => $this->request->input('niche') ?: null,
            'city'          => $this->request->input('city') ?: null,
            'phone'         => $this->request->input('phone') ?: null,
            'email'         => $this->request->input('email') ?: null,
            'contact_name'  => $this->request->input('contact_name') ?: null,
            'spends_on_ads' => $this->request->input('spends_on_ads') ? 1 : 0,
            'source'        => $this->request->input('spends_on_ads') ? 'meta_ads' : 'manual',
            'notes'         => $this->request->input('notes') ?: null,
        ]);

        Session::flash(
            $result['created'] ? 'success' : 'error',
            match ($result['reason']) {
                'created'        => 'הליד נוסף. הרץ בדיקה כדי לקבל ניקוד.',
                'duplicate'      => 'הדומיין הזה כבר קיים במערכת (90 יום אחרונים).',
                'do_not_contact' => 'הדומיין נמצא ברשימת do-not-contact ולא נוסף.',
                'requeued'       => 'הדומיין קיים — הליד הוחזר לתור לבדיקה מחדש.',
                default          => 'לא ניתן להוסיף את הליד.',
            }
        );

        $this->redirect($result['prospect_id'] > 0
            ? 'admin/lead-engine/prospects/' . $result['prospect_id']
            : 'admin/lead-engine/prospects');
    }

    /** CSV import — §5 source C */
    public function importCsv(): never
    {
        $this->requireCsrf('admin/lead-engine/prospects');

        $file = $_FILES['csv'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Session::flash('error', 'לא הועלה קובץ, או שההעלאה נכשלה.');
            $this->redirect('admin/lead-engine/prospects');
        }
        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            Session::flash('error', 'הקובץ גדול מ-2MB.');
            $this->redirect('admin/lead-engine/prospects');
        }

        $parsed = (new CsvImporter())->parse($file['tmp_name']);
        if ($parsed['rows'] === []) {
            Session::flash('error', 'לא נמצאו שורות תקינות. ' . implode(' ', array_slice($parsed['errors'], 0, 3)));
            $this->redirect('admin/lead-engine/prospects');
        }

        $pipeline = new LeadEnginePipeline();
        $added = 0;
        $skipped = $parsed['skipped'];
        foreach ($parsed['rows'] as $row) {
            if ($pipeline->addProspect($row)['created']) {
                $added++;
            } else {
                $skipped++;
            }
        }

        Session::flash('success', "יובאו {$added} לידים חדשים. דולגו {$skipped}.");
        $this->redirect('admin/lead-engine/prospects');
    }

    /** Google Places search on demand — §5 source A */
    public function sourcePlaces(): never
    {
        $this->requireCsrf('admin/lead-engine/prospects');

        $places = new GooglePlacesClient();
        if (!$places->isAvailable()) {
            Session::flash('error', 'GOOGLE_PLACES_API_KEY לא מוגדר בקונפיגורציה.');
            $this->redirect('admin/lead-engine/prospects');
        }

        $niche = trim((string) $this->request->input('niche', ''));
        $city = trim((string) $this->request->input('city', ''));
        if ($niche === '' || $city === '') {
            Session::flash('error', 'נא לבחור נישה ועיר.');
            $this->redirect('admin/lead-engine/prospects');
        }

        $pipeline = new LeadEnginePipeline();
        $added = 0;
        $skipped = 0;
        try {
            foreach ($places->search($niche, $city, 20) as $candidate) {
                if ($pipeline->addProspect($candidate)['created']) {
                    $added++;
                } else {
                    $skipped++;
                }
            }
        } catch (\Throwable $e) {
            Logger::error('leadengine: places sourcing failed', ['message' => $e->getMessage()]);
            Session::flash('error', 'החיפוש נכשל: ' . $e->getMessage());
            $this->redirect('admin/lead-engine/prospects');
        }

        Session::flash('success', "נוספו {$added} לידים מ-Google Places. דולגו {$skipped} (כפולים או מסוננים).");
        $this->redirect('admin/lead-engine/prospects');
    }

    // ----------------------------------------------------- per-prospect stages

    /** Run stages 2-4 for one prospect */
    public function reprocessProspect(string $id): never
    {
        $prospectId = (int) $id;
        $this->requireCsrf('admin/lead-engine/prospects/' . $prospectId);

        try {
            $result = (new LeadEnginePipeline())->reprocess($prospectId);
        } catch (\Throwable $e) {
            Logger::error('leadengine: manual reprocess failed', [
                'prospect_id' => $prospectId, 'message' => $e->getMessage(),
            ]);
            Session::flash('error', 'ההרצה נכשלה: ' . $e->getMessage());
            $this->redirect('admin/lead-engine/prospects/' . $prospectId);
        }

        Session::flash(
            $result['ok'] ? 'success' : 'error',
            match ($result['reason'] ?? '') {
                'below_threshold' => 'הניקוד מתחת לסף — הליד נסגר.',
                'do_not_contact'  => 'הליד נמצא ברשימת do-not-contact.',
                'not_found'       => 'הליד לא נמצא.',
                default           => $result['ok']
                    ? 'ההרצה הושלמה — נוצרה טיוטה בתור האישורים.'
                    : 'ההרצה הושלמה אך לא נוצרה טיוטה. בדוק את הלוג בכרטיס.',
            }
        );
        $this->redirect('admin/lead-engine/prospects/' . $prospectId);
    }

    /** Audit only, keeping the prospect where it is in the funnel */
    public function auditProspect(string $id): never
    {
        $prospectId = (int) $id;
        $this->requireCsrf('admin/lead-engine/prospects/' . $prospectId);

        $prospect = $this->prospects->findById($prospectId);
        if ($prospect === null) {
            Session::flash('error', 'הליד לא נמצא.');
            $this->redirect('admin/lead-engine/prospects');
        }

        try {
            $outcome = (new LeadEnginePipeline())->auditProspect($prospect);
            Session::flash('success', 'הבדיקה הושלמה. hot score: ' . $outcome['hot_score']
                . ($outcome['passed'] ? '' : ' — מתחת לסף, הליד נסגר.'));
        } catch (\Throwable $e) {
            Logger::error('leadengine: manual audit failed', [
                'prospect_id' => $prospectId, 'message' => $e->getMessage(),
            ]);
            Session::flash('error', 'הבדיקה נכשלה: ' . $e->getMessage());
        }

        $this->redirect('admin/lead-engine/prospects/' . $prospectId);
    }

    /** Update editable prospect fields, including the manual broken_form flag */
    public function updateProspect(string $id): never
    {
        $prospectId = (int) $id;
        $this->requireCsrf('admin/lead-engine/prospects/' . $prospectId);

        $update = [];
        foreach (['business_name', 'contact_name', 'contact_role', 'email', 'phone', 'niche', 'city', 'notes'] as $field) {
            $value = $this->request->input($field);
            if ($value !== null) {
                $update[$field] = $value !== '' ? $value : null;
            }
        }
        $update['spends_on_ads'] = $this->request->input('spends_on_ads') ? 1 : 0;
        // §11.5: the engine never submits another business's form — this flag is
        // set only by a human who tested it manually.
        $update['broken_form'] = $this->request->input('broken_form') ? 1 : 0;

        if (!empty($update['email']) && !filter_var($update['email'], FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'כתובת המייל אינה תקינה.');
            $this->redirect('admin/lead-engine/prospects/' . $prospectId);
        }

        $this->prospects->update($prospectId, $update);
        Session::flash('success', 'הליד עודכן.');
        $this->redirect('admin/lead-engine/prospects/' . $prospectId);
    }

    /** Record a reply → promotes to CRM and cancels follow-ups */
    public function markReplied(string $id): never
    {
        $prospectId = (int) $id;
        $this->requireCsrf('admin/lead-engine/prospects/' . $prospectId);

        (new OutreachSender())->recordReply($prospectId, (string) $this->request->input('note', ''));
        Session::flash('success', 'סומן כ"הגיב". הפולואפים בוטלו והליד הועבר ל-CRM.');
        $this->redirect('admin/lead-engine/prospects/' . $prospectId);
    }

    /** Suppression — immediate and unconditional (§11.3) */
    public function addToDnc(string $id): never
    {
        $prospectId = (int) $id;
        $this->requireCsrf('admin/lead-engine/prospects/' . $prospectId);

        (new OutreachSender())->optOut(
            $prospectId,
            (string) ($this->request->input('reason') ?: 'בקשת הסרה')
        );
        Session::flash('success', 'נוסף ל-do-not-contact. לא נפנה שוב.');
        $this->redirect('admin/lead-engine/prospects/' . $prospectId);
    }

    // ------------------------------------------------------------- draft edit

    public function updateDraft(string $id): never
    {
        $draftId = (int) $id;
        $this->requireCsrf('admin/lead-engine/queue');

        $draft = $this->outreach->findDraft($draftId);
        if ($draft === null) {
            Session::flash('error', 'הטיוטה לא נמצאה.');
            $this->redirect('admin/lead-engine/queue');
        }
        if (!in_array($draft['status'], ['draft', 'approved'], true)) {
            Session::flash('error', 'לא ניתן לערוך טיוטה בסטטוס ' . $draft['status'] . '.');
            $this->redirect('admin/lead-engine/queue');
        }

        $update = [];
        foreach (['subject', 'body', 'video_url'] as $field) {
            $value = $this->request->input($field);
            if ($value !== null) {
                $update[$field] = trim((string) $value) !== '' ? trim((string) $value) : null;
            }
        }
        if (!empty($update['video_url']) && !filter_var($update['video_url'], FILTER_VALIDATE_URL)) {
            Session::flash('error', 'קישור הסרטון אינו כתובת תקינה.');
            $this->redirect('admin/lead-engine/queue');
        }

        // Editing an approved draft returns it to draft — the approval was for
        // the text that was reviewed, not for whatever replaced it.
        if ($draft['status'] === 'approved' && ($update['body'] ?? null) !== $draft['body']) {
            $update['status'] = 'draft';
            $update['approved_at'] = null;
        }

        $this->outreach->updateDraft($draftId, $update);
        Session::flash('success', 'הטיוטה נשמרה.');
        $this->redirect('admin/lead-engine/queue#draft-' . $draftId);
    }

    public function approveDraft(string $id): never
    {
        $draftId = (int) $id;
        $this->requireCsrf('admin/lead-engine/queue');

        $result = (new OutreachSender())->approve($draftId, $this->userId());
        if ($result['approved']) {
            Session::flash('success', 'הטיוטה אושרה. כדי לשלוח — פתח תצוגה מקדימה ולחץ "שלח עכשיו".');
        } else {
            Session::flash('error', implode(' ', $result['blockers']));
        }
        $this->redirect('admin/lead-engine/queue#draft-' . $draftId);
    }

    public function rejectDraft(string $id): never
    {
        $draftId = (int) $id;
        $this->requireCsrf('admin/lead-engine/queue');

        (new OutreachSender())->reject($draftId, (string) $this->request->input('reason', ''));
        Session::flash('success', 'הטיוטה נדחתה.');
        $this->redirect('admin/lead-engine/queue');
    }

    // --------------------------------------------------- the approval hand-off

    /**
     * The email button lands here. GET, read-only, no side effects beyond
     * consuming the single-use token (§9).
     *
     * Sending happens only from the POST form this page renders.
     */
    public function confirmDraft(string $id): string
    {
        $draftId = (int) $id;
        $draft = $this->outreach->findDraftWithContext($draftId);
        if ($draft === null) {
            throw new \App\Core\Exceptions\NotFoundException();
        }

        $token = (string) $this->request->input('token', '');
        $tokenState = 'none';

        if ($token !== '') {
            try {
                $hash = ApprovalToken::hashFor($draftId, $token);
                // Conditional UPDATE: single-use, unexpired, correct hash
                $tokenState = $this->outreach->consumeToken($draftId, $hash) ? 'valid' : 'invalid';
            } catch (\Throwable $e) {
                Logger::error('leadengine: token verification failed', [
                    'draft_id' => $draftId, 'message' => $e->getMessage(),
                ]);
                $tokenState = 'error';
            }
            // Re-read: consumeToken mutated the row
            $draft = $this->outreach->findDraftWithContext($draftId) ?? $draft;
        }

        $sender = new OutreachSender();
        $guard = (new SendGuard($this->outreach, $this->dnc))->check($draft);

        return $this->render('leadengine/confirm', [
            'pageTitle'   => 'אישור שליחה — ' . ($draft['business_name'] ?? ''),
            'draft'       => $draft,
            'guard'       => $guard,
            'tokenState'  => $tokenState,
            'finalBody'   => $sender->composeBody($draft),
            'issueLabel'  => HotScore::issueLabel((string) ($draft['primary_issue'] ?? 'none')),
            'flashMsg'    => $this->flash(),
        ]);
    }

    /**
     * The actual send. POST only, CSRF-checked by the global middleware, and
     * re-verified against SendGuard inside OutreachSender.
     */
    public function sendDraft(string $id): never
    {
        $draftId = (int) $id;
        $this->requireCsrf('admin/lead-engine/queue');

        // Explicit typed confirmation — a stray click should not send.
        if ($this->request->input('confirm') !== 'send') {
            Session::flash('error', 'השליחה לא אושרה.');
            $this->redirect('admin/lead-engine/drafts/' . $draftId . '/confirm');
        }

        $result = (new OutreachSender())->send($draftId, $this->userId());
        if ($result['sent']) {
            Session::flash('success', 'ההודעה נשלחה.');
            $this->redirect('admin/lead-engine/queue');
        }

        Session::flash('error', 'לא נשלח: ' . implode(' ', $result['blockers']));
        $this->redirect('admin/lead-engine/drafts/' . $draftId . '/confirm');
    }

    // ------------------------------------------------------------------- runs

    public function runs(): string
    {
        return $this->render('leadengine/runs', [
            'pageTitle' => 'הרצות — מנוע לידים',
            'runs'      => $this->engine->runs(50),
            'flashMsg'  => $this->flash(),
        ]);
    }

    public function runNow(): never
    {
        $this->requireCsrf('admin/lead-engine/runs');

        $withSourcing = (bool) $this->request->input('with_sourcing');
        try {
            $result = (new LeadEnginePipeline())->run('manual', $withSourcing, 15);
            $c = $result['counters'];
            Session::flash('success', sprintf(
                'ההרצה הושלמה: %d נאספו, %d נבדקו, %d מתחת לסף, %d הועשרו, %d טיוטות, %d שגיאות.',
                $c['sourced'] ?? 0, $c['audited'] ?? 0, $c['below_threshold'] ?? 0,
                $c['enriched'] ?? 0, $c['drafted'] ?? 0, $c['errors'] ?? 0
            ));
        } catch (\Throwable $e) {
            Logger::error('leadengine: manual run failed', ['message' => $e->getMessage()]);
            Session::flash('error', 'ההרצה נכשלה: ' . $e->getMessage());
        }

        $this->redirect('admin/lead-engine/runs');
    }

    public function sendDigest(): never
    {
        $this->requireCsrf('admin/lead-engine/runs');

        try {
            $digest = new LeadEngineDigest();
            $followups = $digest->queueFollowups();
            $result = $digest->sendDaily();

            Session::flash(
                $result['sent'] ? 'success' : 'error',
                $result['sent']
                    ? "המייל נשלח עם {$result['count']} לידים" . ($followups > 0 ? " ({$followups} פולואפים נוספו לתור)" : '') . '.'
                    : match ($result['reason']) {
                        'nothing_pending' => 'אין טיוטות ממתינות — לא נשלח מייל.',
                        'no_recipient'    => 'ADMIN_NOTIFY_EMAIL לא מוגדר.',
                        default           => 'שליחת המייל נכשלה.',
                    }
            );
        } catch (\Throwable $e) {
            Logger::error('leadengine: manual digest failed', ['message' => $e->getMessage()]);
            Session::flash('error', 'שליחת המייל נכשלה: ' . $e->getMessage());
        }

        $this->redirect('admin/lead-engine/runs');
    }

    // --------------------------------------------------------------- settings

    public function settings(): string
    {
        return $this->render('leadengine/settings', [
            'pageTitle'  => 'הגדרות — מנוע לידים',
            'settings'   => LeadEngineConfig::all(),
            'dncList'    => $this->dnc->all(200),
            'integrations' => [
                'google_places' => (new GooglePlacesClient())->isAvailable(),
                'pagespeed'     => (new \App\LeadEngine\PageSpeedClient())->isAvailable(),
                'llm'           => (new \App\Services\OpenAiService())->isAvailable(),
                'token_secret'  => strlen(LeadEngineConfig::tokenSecret()) >= 32,
                'notify_email'  => LeadEngineConfig::notifyEmail() !== '',
            ],
            'nicheList'  => GooglePlacesClient::NICHE_QUERIES,
            'flashMsg'   => $this->flash(),
        ]);
    }

    public function updateSettings(): never
    {
        $this->requireCsrf('admin/lead-engine/settings');

        $numeric = [
            'hot_score_threshold'       => [0, 100],
            'max_daily_sends'           => [0, 100],
            'min_minutes_between_sends' => [0, 1440],
            'closed_retention_months'   => [1, 120],
        ];
        foreach ($numeric as $key => [$min, $max]) {
            $value = $this->request->input($key);
            if ($value === null || $value === '') {
                continue;
            }
            $this->engine->setSetting($key, (string) max($min, min($max, (int) $value)));
        }

        foreach (['send_window_start', 'send_window_end'] as $key) {
            $value = (string) $this->request->input($key, '');
            if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value)) {
                $this->engine->setSetting($key, $value);
            }
        }

        foreach (['active_niches', 'active_cities'] as $key) {
            $value = $this->request->input($key);
            if ($value !== null) {
                $this->engine->setSetting($key, trim((string) $value));
            }
        }

        foreach (['pipeline_enabled', 'sending_halted'] as $key) {
            $this->engine->setSetting($key, $this->request->input($key) ? '1' : '0');
        }

        LeadEngineConfig::flush();
        Session::flash('success', 'ההגדרות נשמרו.');
        $this->redirect('admin/lead-engine/settings');
    }

    /** The "stop everything" switch from §9 */
    public function toggleHalt(): never
    {
        $this->requireCsrf('admin/lead-engine');

        $halted = LeadEngineConfig::bool('sending_halted');
        $this->engine->setSetting('sending_halted', $halted ? '0' : '1');
        LeadEngineConfig::flush();

        Logger::warning('leadengine: sending halt toggled', [
            'halted' => !$halted, 'by_user_id' => $this->userId(),
        ]);
        Session::flash('success', $halted ? 'השליחות הופעלו מחדש.' : 'כל השליחות הוקפאו.');
        $this->redirect('admin/lead-engine');
    }

    public function removeFromDnc(string $id): never
    {
        $this->requireCsrf('admin/lead-engine/settings');
        $this->dnc->remove((int) $id);
        Session::flash('success', 'הוסר מרשימת do-not-contact.');
        $this->redirect('admin/lead-engine/settings');
    }

    public function addDncEntry(): never
    {
        $this->requireCsrf('admin/lead-engine/settings');

        $domain = trim((string) $this->request->input('domain', ''));
        $email = trim((string) $this->request->input('email', ''));
        $phone = trim((string) $this->request->input('phone', ''));

        if ($domain === '' && $email === '' && $phone === '') {
            Session::flash('error', 'נא להזין דומיין, מייל או טלפון.');
            $this->redirect('admin/lead-engine/settings');
        }

        $this->dnc->add(
            $domain ?: null,
            $email ?: null,
            $phone ?: null,
            (string) ($this->request->input('reason') ?: 'הוספה ידנית')
        );
        Session::flash('success', 'נוסף לרשימת do-not-contact.');
        $this->redirect('admin/lead-engine/settings');
    }

    // ---------------------------------------------------------------- helpers

    /**
     * The global CsrfMiddleware already rejects POSTs with a bad token; this is
     * a second explicit check so a routing change can never silently expose a
     * state-changing action.
     */
    private function requireCsrf(string $redirectTo): void
    {
        if (!$this->validateCsrf()) {
            Session::flash('error', 'אימות הבקשה נכשל. נסה שוב.');
            $this->redirect($redirectTo);
        }
    }

    private function userId(): ?int
    {
        $user = Session::get('user');
        return isset($user['id']) ? (int) $user['id'] : null;
    }

    private function flash(): ?array
    {
        foreach (['success', 'error'] as $type) {
            if ($message = Session::flash($type)) {
                return ['type' => $type, 'message' => $message];
            }
        }
        return null;
    }

    /** Metrics queries must never take down the dashboard */
    private function safe(callable $fn): array
    {
        try {
            return $fn() ?: [];
        } catch (\Throwable $e) {
            Logger::warning('leadengine: dashboard metric failed', ['message' => $e->getMessage()]);
            return [];
        }
    }
}
