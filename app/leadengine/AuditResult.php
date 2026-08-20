<?php
namespace App\LeadEngine;

/**
 * AuditResult — the return value of AuditEngine::runAudit().
 *
 * Mirrors the `prospect_audits` columns (spec §4) so persistence is a straight
 * field copy. Scores are 0-100 where higher = better site; hot_score inverts
 * that (higher = better opportunity, see HotScore).
 */
class AuditResult
{
    public string $url = '';
    public bool $fetchOk = false;
    public int $httpStatus = 0;
    public int $responseTimeMs = 0;

    /** True when the failed fetch looks like bot/CDN protection rather than a dead site */
    public bool $looksBlocked = false;

    public ?int $perfMobile = null;
    public ?int $perfDesktop = null;
    public int $seoScore = 0;
    public int $a11yScore = 0;
    public int $securityScore = 0;

    public bool $hasSsl = false;
    public bool $hasAccessibilityStatement = false;
    public bool $hasAnalytics = false;
    public bool $hasMetaPixel = false;
    public bool $hasClickToCall = false;
    public bool $mobileViewportOk = false;
    public bool $contactFormFound = false;

    public int $hotScore = 0;
    public string $primaryIssue = 'none';

    /** 'pagespeed' when the PSI API answered, 'heuristic' when derived locally */
    public string $perfSource = 'heuristic';

    /** Human-readable issues, for the video brief and the panel */
    public array $issues = [];

    /** Full scanner output, persisted to prospect_audits.raw_json */
    public array $raw = [];

    public function toRow(int $prospectId): array
    {
        return [
            'prospect_id'                 => $prospectId,
            'perf_mobile'                 => $this->perfMobile,
            'perf_desktop'                => $this->perfDesktop,
            'seo_score'                   => $this->seoScore,
            'a11y_score'                  => $this->a11yScore,
            'security_score'              => $this->securityScore,
            'has_ssl'                     => (int) $this->hasSsl,
            'has_accessibility_statement' => (int) $this->hasAccessibilityStatement,
            'has_analytics'               => (int) $this->hasAnalytics,
            'has_meta_pixel'              => (int) $this->hasMetaPixel,
            'has_click_to_call'           => (int) $this->hasClickToCall,
            'mobile_viewport_ok'          => (int) $this->mobileViewportOk,
            'contact_form_found'          => (int) $this->contactFormFound,
            'hot_score'                   => $this->hotScore,
            'primary_issue'               => $this->primaryIssue,
            'perf_source'                 => $this->perfSource,
            'fetch_ok'                    => (int) $this->fetchOk,
            'raw_json'                    => json_encode($this->toArray(), JSON_UNESCAPED_UNICODE),
        ];
    }

    public function toArray(): array
    {
        return [
            'url'              => $this->url,
            'fetch_ok'         => $this->fetchOk,
            'looks_blocked'    => $this->looksBlocked,
            'http_status'      => $this->httpStatus,
            'response_time_ms' => $this->responseTimeMs,
            'perf_mobile'      => $this->perfMobile,
            'perf_desktop'     => $this->perfDesktop,
            'perf_source'      => $this->perfSource,
            'seo_score'        => $this->seoScore,
            'a11y_score'       => $this->a11yScore,
            'security_score'   => $this->securityScore,
            'signals'          => [
                'has_ssl'                     => $this->hasSsl,
                'has_accessibility_statement' => $this->hasAccessibilityStatement,
                'has_analytics'               => $this->hasAnalytics,
                'has_meta_pixel'              => $this->hasMetaPixel,
                'has_click_to_call'           => $this->hasClickToCall,
                'mobile_viewport_ok'          => $this->mobileViewportOk,
                'contact_form_found'          => $this->contactFormFound,
            ],
            'hot_score'        => $this->hotScore,
            'primary_issue'    => $this->primaryIssue,
            'issues'           => $this->issues,
            'scanners'         => $this->raw,
        ];
    }
}
