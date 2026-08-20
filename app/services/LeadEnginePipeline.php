<?php
namespace App\Services;

use App\Core\Logger;
use App\LeadEngine\ContactFinder;
use App\LeadEngine\DraftWriter;
use App\LeadEngine\GooglePlacesClient;
use App\LeadEngine\HotScore;
use App\LeadEngine\LeadEngineConfig;
use App\LeadEngine\PoliteFetcher;
use App\Repositories\DoNotContactRepository;
use App\Repositories\LeadEngineRepository;
use App\Repositories\OutreachRepository;
use App\Repositories\ProspectRepository;

/**
 * LeadEnginePipeline — stages 1 to 4 (spec §3).
 *
 *   SOURCING → AUDIT + SCORE → CONTACT DISCOVERY → DRAFT + BRIEF
 *
 * Stage 5 (human approval) is deliberately absent: this class never sends
 * anything and never marks a draft approved. It stops at status='drafted' and
 * waits for a person.
 *
 * Every stage is individually callable so the panel can re-run one prospect,
 * and each run is recorded in lead_engine_runs with per-item skip reasons.
 */
class LeadEnginePipeline
{
    private ProspectRepository $prospects;
    private OutreachRepository $outreach;
    private DoNotContactRepository $dnc;
    private LeadEngineRepository $runs;
    private AuditEngine $auditEngine;
    private ContactFinder $contactFinder;
    private DraftWriter $draftWriter;

    private const ZERO_COUNTERS = [
        'sourced' => 0, 'skipped_duplicate' => 0, 'skipped_dnc' => 0, 'audited' => 0,
        'below_threshold' => 0, 'enriched' => 0, 'drafted' => 0, 'errors' => 0,
    ];

    /**
     * Counters for the current run. Initialized here, not in run(), because the
     * panel calls addProspect()/auditProspect() directly on a fresh instance.
     */
    private array $counters = self::ZERO_COUNTERS;
    private array $log = [];

    public function __construct(
        ?AuditEngine $auditEngine = null,
        ?ContactFinder $contactFinder = null,
        ?DraftWriter $draftWriter = null
    ) {
        $this->prospects = new ProspectRepository();
        $this->outreach = new OutreachRepository();
        $this->dnc = new DoNotContactRepository();
        $this->runs = new LeadEngineRepository();
        $this->auditEngine = $auditEngine ?? new AuditEngine();
        $this->contactFinder = $contactFinder ?? new ContactFinder();
        $this->draftWriter = $draftWriter ?? new DraftWriter();
    }

    /**
     * A full run: source new prospects, then push everything pending through
     * stages 2-4.
     *
     * @param bool $withSourcing false to process the existing backlog only
     * @return array{run_id:int,counters:array,log:array}
     */
    public function run(string $trigger = 'manual', bool $withSourcing = true, int $perStageLimit = 25): array
    {
        $this->counters = self::ZERO_COUNTERS;
        $this->log = [];

        if (!LeadEngineConfig::bool('pipeline_enabled')) {
            Logger::info('leadengine: pipeline disabled, run skipped');
            return ['run_id' => 0, 'counters' => $this->counters, 'log' => ['pipeline_disabled' => true]];
        }

        $runId = $this->runs->startRun($trigger);
        Logger::info('leadengine: pipeline run started', ['run_id' => $runId, 'trigger' => $trigger]);

        try {
            if ($withSourcing) {
                $this->stageSourcing($perStageLimit);
            }
            $this->stageAudit($perStageLimit);
            $this->stageContactDiscovery($perStageLimit);
            $this->stageDraft($perStageLimit);
            $this->stageHousekeeping();
        } catch (\Throwable $e) {
            $this->counters['errors']++;
            $this->log[] = ['stage' => 'run', 'error' => $e->getMessage()];
            Logger::error('leadengine: pipeline run aborted', [
                'run_id' => $runId, 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),
            ]);
        }

        $this->runs->finishRun($runId, $this->counters, $this->log);
        Logger::info('leadengine: pipeline run finished', ['run_id' => $runId] + $this->counters);

        return ['run_id' => $runId, 'counters' => $this->counters, 'log' => $this->log];
    }

    // ------------------------------------------------------ Stage 1: sourcing

    /**
     * Google Places across the configured niches × cities. Manual entry and CSV
     * import enter the same funnel through addProspect(), just from the panel.
     */
    public function stageSourcing(int $limit = 25): int
    {
        $places = new GooglePlacesClient();
        if (!$places->isAvailable()) {
            $this->log[] = ['stage' => 'sourcing', 'skipped' => 'GOOGLE_PLACES_API_KEY not configured'];
            return 0;
        }

        $niches = LeadEngineConfig::list('active_niches');
        $cities = LeadEngineConfig::list('active_cities');
        if ($niches === [] || $cities === []) {
            $this->log[] = ['stage' => 'sourcing', 'skipped' => 'no active niches or cities configured'];
            return 0;
        }

        $added = 0;
        foreach ($niches as $niche) {
            foreach ($cities as $city) {
                if ($added >= $limit) {
                    break 2;
                }
                try {
                    foreach ($places->search($niche, $city, $limit - $added) as $candidate) {
                        $result = $this->addProspect($candidate);
                        if ($result['created']) {
                            $added++;
                        }
                        if ($added >= $limit) {
                            break;
                        }
                    }
                } catch (\Throwable $e) {
                    $this->counters['errors']++;
                    $this->log[] = ['stage' => 'sourcing', 'niche' => $niche, 'city' => $city, 'error' => $e->getMessage()];
                }
            }
        }

        return $added;
    }

    /**
     * The single entry point for a new prospect, whatever the source. Applies
     * the §5 dedup rules and the do_not_contact block.
     *
     * @return array{created:bool,prospect_id:int,reason:string}
     */
    public function addProspect(array $data): array
    {
        $url = PoliteFetcher::normalizeUrl((string) ($data['url'] ?? ''));
        $domain = PoliteFetcher::domainKey($data['domain'] ?? $url);

        if ($domain === '' || $url === '') {
            $this->log[] = ['stage' => 'sourcing', 'skipped' => 'missing url', 'data' => $data['business_name'] ?? '?'];
            return ['created' => false, 'prospect_id' => 0, 'reason' => 'missing_url'];
        }

        // Permanent block beats everything (§5)
        if ($this->dnc->isBlocked($domain, $data['email'] ?? null, $data['phone'] ?? null)) {
            $this->counters['skipped_dnc']++;
            $this->log[] = ['stage' => 'sourcing', 'domain' => $domain, 'skipped' => 'do_not_contact'];
            return ['created' => false, 'prospect_id' => 0, 'reason' => 'do_not_contact'];
        }

        // Same domain within the dedup window → skip and record it (§5)
        $recent = $this->prospects->seenWithinDays($domain, 90);
        if ($recent !== null) {
            $this->counters['skipped_duplicate']++;
            $this->log[] = [
                'stage' => 'sourcing', 'domain' => $domain, 'skipped' => 'duplicate_within_90_days',
                'existing_prospect_id' => (int) $recent['id'],
            ];
            return ['created' => false, 'prospect_id' => (int) $recent['id'], 'reason' => 'duplicate'];
        }

        // An older row for the same domain exists: the unique index will reject
        // the insert, so reuse it and requeue for a fresh audit.
        $existing = $this->prospects->findByDomain($domain);
        if ($existing !== null) {
            $this->prospects->update((int) $existing['id'], ['status' => 'new']);
            $this->log[] = [
                'stage' => 'sourcing', 'domain' => $domain, 'action' => 're-queued existing prospect',
                'prospect_id' => (int) $existing['id'],
            ];
            return ['created' => false, 'prospect_id' => (int) $existing['id'], 'reason' => 'requeued'];
        }

        $row = [
            'business_name' => (string) ($data['business_name'] ?? $domain),
            'domain'        => $domain,
            'url'           => $url,
            'niche'         => $data['niche'] ?? null,
            'city'          => $data['city'] ?? null,
            'source'        => $data['source'] ?? 'manual',
            'source_ref'    => $data['source_ref'] ?? null,
            'phone'         => $data['phone'] ?? null,
            'email'         => $data['email'] ?? null,
            'contact_name'  => $data['contact_name'] ?? null,
            'contact_role'  => $data['contact_role'] ?? null,
            'spends_on_ads' => (int) (bool) ($data['spends_on_ads'] ?? 0),
            'status'        => 'new',
            'notes'         => $data['notes'] ?? null,
        ];

        $id = $this->prospects->create($row);
        if ($id === 0) {
            $this->counters['skipped_duplicate']++;
            return ['created' => false, 'prospect_id' => 0, 'reason' => 'duplicate'];
        }

        $this->counters['sourced']++;
        return ['created' => true, 'prospect_id' => $id, 'reason' => 'created'];
    }

    // ------------------------------------------------- Stage 2: audit + score

    public function stageAudit(int $limit = 25): int
    {
        $done = 0;
        foreach ($this->prospects->byStatus('new', $limit) as $prospect) {
            try {
                $this->auditProspect($prospect);
                $done++;
            } catch (\Throwable $e) {
                $this->counters['errors']++;
                $this->log[] = ['stage' => 'audit', 'prospect_id' => (int) $prospect['id'], 'error' => $e->getMessage()];
                Logger::error('leadengine: audit stage failed', [
                    'prospect_id' => $prospect['id'], 'message' => $e->getMessage(),
                ]);
            }
        }
        return $done;
    }

    /**
     * Audit one prospect and apply the entry threshold.
     *
     * Below the threshold the prospect is closed, not deleted — the audit row
     * stays as history for a future re-check (§10).
     *
     * @return array{audit_id:int,hot_score:int,passed:bool}
     */
    public function auditProspect(array $prospect): array
    {
        $prospectId = (int) $prospect['id'];
        $audit = $this->auditEngine->runAudit(
            (string) $prospect['url'],
            (bool) ($prospect['spends_on_ads'] ?? false),
            (bool) ($prospect['broken_form'] ?? false)
        );

        $auditId = $this->prospects->saveAudit($prospectId, $audit);
        $this->counters['audited']++;

        $threshold = LeadEngineConfig::int('hot_score_threshold');

        if (!$audit->fetchOk) {
            $this->prospects->setStatus($prospectId, 'closed');
            $this->log[] = [
                'stage' => 'audit', 'prospect_id' => $prospectId, 'domain' => $prospect['domain'],
                'closed' => 'homepage unreachable', 'http_status' => $audit->httpStatus,
            ];
            return ['audit_id' => $auditId, 'hot_score' => 0, 'passed' => false];
        }

        if ($audit->hotScore < $threshold) {
            $this->prospects->setStatus($prospectId, 'closed');
            $this->counters['below_threshold']++;
            $this->log[] = [
                'stage' => 'audit', 'prospect_id' => $prospectId, 'domain' => $prospect['domain'],
                'closed' => 'below_threshold', 'hot_score' => $audit->hotScore, 'threshold' => $threshold,
            ];
            return ['audit_id' => $auditId, 'hot_score' => $audit->hotScore, 'passed' => false];
        }

        $this->prospects->setStatus($prospectId, 'audited');
        return ['audit_id' => $auditId, 'hot_score' => $audit->hotScore, 'passed' => true];
    }

    // --------------------------------------------- Stage 3: contact discovery

    public function stageContactDiscovery(int $limit = 25): int
    {
        $done = 0;
        foreach ($this->prospects->byStatus('audited', $limit) as $prospect) {
            try {
                $this->enrichProspect($prospect);
                $done++;
            } catch (\Throwable $e) {
                $this->counters['errors']++;
                $this->log[] = ['stage' => 'contact', 'prospect_id' => (int) $prospect['id'], 'error' => $e->getMessage()];
                Logger::error('leadengine: contact stage failed', [
                    'prospect_id' => $prospect['id'], 'message' => $e->getMessage(),
                ]);
            }
        }
        return $done;
    }

    /** @return array<string,mixed> The discovered contact fields */
    public function enrichProspect(array $prospect): array
    {
        $prospectId = (int) $prospect['id'];
        $found = $this->contactFinder->find((string) $prospect['url'], $prospect['phone'] ?? null);

        $update = ['status' => 'enriched'];
        // Never overwrite a value a human entered by hand
        if (empty($prospect['email']) && !empty($found['email'])) {
            $update['email'] = $found['email'];
        }
        if (empty($prospect['phone']) && !empty($found['phone'])) {
            $update['phone'] = $found['phone'];
        }
        if (empty($prospect['contact_name']) && !empty($found['contact_name'])) {
            $update['contact_name'] = $found['contact_name'];
        }
        if (empty($prospect['contact_role']) && !empty($found['contact_role'])) {
            $update['contact_role'] = $found['contact_role'];
        }

        // A suppressed address discovered mid-pipeline stops the prospect here
        if ($this->dnc->isBlocked($prospect['domain'], $update['email'] ?? $prospect['email'] ?? null, null)) {
            $this->prospects->update($prospectId, ['status' => 'do_not_contact']);
            $this->counters['skipped_dnc']++;
            $this->log[] = ['stage' => 'contact', 'prospect_id' => $prospectId, 'skipped' => 'do_not_contact'];
            return $found;
        }

        $this->prospects->update($prospectId, $update);
        $this->counters['enriched']++;

        $this->log[] = [
            'stage' => 'contact', 'prospect_id' => $prospectId, 'domain' => $prospect['domain'],
            'contact_source' => $found['source'],
            'has_name' => !empty($update['contact_name'] ?? $prospect['contact_name']),
        ];

        return $found;
    }

    // ------------------------------------------------ Stage 4: draft + brief

    public function stageDraft(int $limit = 25): int
    {
        $done = 0;
        foreach ($this->prospects->readyToDraft($limit) as $prospect) {
            try {
                if ($this->draftProspect($prospect) > 0) {
                    $done++;
                }
            } catch (\Throwable $e) {
                $this->counters['errors']++;
                $this->log[] = ['stage' => 'draft', 'prospect_id' => (int) $prospect['id'], 'error' => $e->getMessage()];
                Logger::error('leadengine: draft stage failed', [
                    'prospect_id' => $prospect['id'], 'message' => $e->getMessage(),
                ]);
            }
        }
        return $done;
    }

    /** @return int New draft id, or 0 when nothing was created */
    public function draftProspect(array $prospect): int
    {
        $prospectId = (int) $prospect['id'];

        if ($this->outreach->hasOpenDraft($prospectId)) {
            $this->log[] = ['stage' => 'draft', 'prospect_id' => $prospectId, 'skipped' => 'draft already exists'];
            return 0;
        }

        $auditRow = $this->prospects->latestAudit($prospectId);
        if ($auditRow === null) {
            $this->log[] = ['stage' => 'draft', 'prospect_id' => $prospectId, 'skipped' => 'no audit on record'];
            return 0;
        }

        $audit = self::auditFromRow($auditRow);
        $written = $this->draftWriter->write($prospect, $audit, 'email');

        $draftId = $this->outreach->createDraft([
            'prospect_id'  => $prospectId,
            'audit_id'     => (int) $auditRow['id'],
            'channel'      => 'email',
            'subject'      => $written['subject'],
            'body'         => $written['body'],
            'video_brief'  => $written['video_brief'],
            'status'       => 'draft',
            'generated_by' => $written['generated_by'],
        ]);

        $this->prospects->setStatus($prospectId, 'drafted');
        $this->counters['drafted']++;
        $this->log[] = [
            'stage' => 'draft', 'prospect_id' => $prospectId, 'draft_id' => $draftId,
            'generated_by' => $written['generated_by'],
        ];

        return $draftId;
    }

    // ------------------------------------------------------------ housekeeping

    /** Expire stale drafts and enforce the §11.6 retention policy */
    public function stageHousekeeping(): void
    {
        $expired = $this->outreach->expireStaleDrafts(14);
        if ($expired > 0) {
            $this->log[] = ['stage' => 'housekeeping', 'expired_drafts' => $expired];
        }

        $months = LeadEngineConfig::int('closed_retention_months');
        if ($months > 0) {
            $purged = $this->prospects->purgeClosedOlderThan($months);
            if ($purged > 0) {
                $this->log[] = ['stage' => 'housekeeping', 'purged_closed_prospects' => $purged, 'older_than_months' => $months];
                Logger::info('leadengine: purged closed prospects past retention', ['count' => $purged]);
            }
        }
    }

    // ----------------------------------------------------------------- helpers

    /**
     * Rebuild an AuditResult from a persisted prospect_audits row, so the draft
     * writer works the same whether the audit just ran or came from the DB.
     */
    public static function auditFromRow(array $row): \App\LeadEngine\AuditResult
    {
        $audit = new \App\LeadEngine\AuditResult();

        $raw = [];
        if (!empty($row['raw_json'])) {
            $decoded = json_decode((string) $row['raw_json'], true);
            if (is_array($decoded)) {
                $raw = $decoded;
            }
        }

        $audit->url = (string) ($raw['url'] ?? '');
        $audit->fetchOk = (bool) ($row['fetch_ok'] ?? 1);
        $audit->httpStatus = (int) ($raw['http_status'] ?? 0);
        $audit->responseTimeMs = (int) ($raw['response_time_ms'] ?? 0);
        $audit->perfMobile = isset($row['perf_mobile']) && $row['perf_mobile'] !== null ? (int) $row['perf_mobile'] : null;
        $audit->perfDesktop = isset($row['perf_desktop']) && $row['perf_desktop'] !== null ? (int) $row['perf_desktop'] : null;
        $audit->seoScore = (int) ($row['seo_score'] ?? 0);
        $audit->a11yScore = (int) ($row['a11y_score'] ?? 0);
        $audit->securityScore = (int) ($row['security_score'] ?? 0);
        $audit->hasSsl = (bool) ($row['has_ssl'] ?? 0);
        $audit->hasAccessibilityStatement = (bool) ($row['has_accessibility_statement'] ?? 0);
        $audit->hasAnalytics = (bool) ($row['has_analytics'] ?? 0);
        $audit->hasMetaPixel = (bool) ($row['has_meta_pixel'] ?? 0);
        $audit->hasClickToCall = (bool) ($row['has_click_to_call'] ?? 0);
        $audit->mobileViewportOk = (bool) ($row['mobile_viewport_ok'] ?? 0);
        $audit->contactFormFound = (bool) ($row['contact_form_found'] ?? 0);
        $audit->hotScore = (int) ($row['hot_score'] ?? 0);
        $audit->primaryIssue = (string) ($row['primary_issue'] ?? 'none');
        $audit->perfSource = (string) ($row['perf_source'] ?? 'heuristic');
        $audit->issues = is_array($raw['issues'] ?? null) ? $raw['issues'] : [];
        $audit->raw = is_array($raw['scanners'] ?? null) ? $raw['scanners'] : [];

        return $audit;
    }

    /** Re-run one prospect end to end, ignoring its current status */
    public function reprocess(int $prospectId): array
    {
        $this->counters = self::ZERO_COUNTERS;
        $this->log = [];

        $prospect = $this->prospects->findById($prospectId);
        if ($prospect === null) {
            return ['ok' => false, 'reason' => 'not_found', 'log' => []];
        }

        $auditOutcome = $this->auditProspect($prospect);
        if (!$auditOutcome['passed']) {
            return ['ok' => false, 'reason' => 'below_threshold', 'log' => $this->log, 'counters' => $this->counters];
        }

        $prospect = $this->prospects->findById($prospectId) ?? $prospect;
        $this->enrichProspect($prospect);

        $prospect = $this->prospects->findById($prospectId) ?? $prospect;
        if (($prospect['status'] ?? '') === 'do_not_contact') {
            return ['ok' => false, 'reason' => 'do_not_contact', 'log' => $this->log, 'counters' => $this->counters];
        }

        $draftId = $this->draftProspect($prospect);

        return [
            'ok' => $draftId > 0,
            'draft_id' => $draftId,
            'log' => $this->log,
            'counters' => $this->counters,
        ];
    }

    public function counters(): array
    {
        return $this->counters;
    }

    public function log(): array
    {
        return $this->log;
    }
}
