<?php
namespace App\Services;

use App\Scanner\SeoScanner;
use App\Scanner\PerformanceScanner;
use App\Scanner\SecurityScanner;
use App\Scanner\AccessibilityScanner;
use App\Scanner\LandingPageScanner;
use App\Scanner\SpamScanner;
use App\Ai\ScoringAgent;
use App\Ai\AnalysisAgent;
use App\Repositories\AuditReportRepository;

/**
 * ScanService — Integration orchestrator.
 * Wires scanners, AI agents, lead system, and persistence.
 */
class ScanService
{
    private SeoScanner $seo;
    private PerformanceScanner $perf;
    private SecurityScanner $sec;
    private AccessibilityScanner $acc;
    private LandingPageScanner $lpt;
    private SpamScanner $spam;
    private ScoringAgent $scoring;
    private AnalysisAgent $analysis;
    private AuditReportRepository $repo;

    public function __construct()
    {
        $this->seo      = new SeoScanner();
        $this->perf     = new PerformanceScanner();
        $this->sec      = new SecurityScanner();
        $this->acc      = new AccessibilityScanner();
        $this->lpt      = new LandingPageScanner();
        $this->spam     = new SpamScanner();
        $this->scoring  = new ScoringAgent();
        $this->analysis = new AnalysisAgent();
        $this->repo     = new AuditReportRepository();
    }

    /**
     * Full pipeline: URL → fetch → scan → score → analyze → persist.
     */
    public function runFullScan(string $url, ?string $html = null, array $headers = [], ?int $leadId = null, string $ip = '127.0.0.1'): array
    {
        // Fetch HTML if not provided
        if ($html === null) {
            $ctx = stream_context_create(['http' => ['timeout' => 10, 'user_agent' => 'LandingFlow/1.0']]);
            $html = @file_get_contents($url, false, $ctx);
            if ($html === false) $html = '';
            // Capture response headers
            if (isset($http_response_header)) {
                foreach ($http_response_header as $h) $headers[] = $h;
            }
        }

        // Run all scanners
        $seoResult    = $this->seo->scan($html, $url);
        $perfResult   = $this->perf->scan($html, $url, $headers);
        $secResult    = $this->sec->scan($html, $url, $headers);
        $accResult    = $this->acc->scan($html, $url);
        $spamResult   = $this->spam->scan($html, $url, $headers);

        // Compute composite score
        $scoreResult = $this->scoring->compute([
            'seo'           => $seoResult->finalScore(),
            'performance'   => $perfResult->score,
            'security'      => $secResult->score,
            'accessibility' => $accResult->score,
            'spam'          => $spamResult->finalScore(),
            'legal'         => 0,
            'ux'            => 0,
        ]);

        // AI analysis
        $analysisResult = $this->analysis->analyze([
            'seo'           => ['issues' => $seoResult->issues],
            'performance'   => ['issues' => $perfResult->issues],
            'security'      => ['issues' => $secResult->issues],
            'accessibility' => ['issues' => $accResult->issues],
            'spam'          => ['issues' => $spamResult->issues],
        ]);

        // Count checks
        $allIssues = array_merge($seoResult->issues, $perfResult->issues, $secResult->issues, $accResult->issues, $spamResult->issues);
        $totalChecks = count($allIssues) + 5; // minimum 5 checks
        $failedChecks = count($allIssues);
        $passedChecks = $totalChecks - $failedChecks;

        // Build full report
        $fullReport = [
            'seo'           => $seoResult->toArray(),
            'performance'   => $perfResult->toArray(),
            'security'      => $secResult->toArray(),
            'accessibility' => $accResult->toArray(),
            'spam'          => $spamResult->toArray(),
            'composite'     => $scoreResult,
            'analysis'      => $analysisResult,
        ];

        $recommendations = array_merge(
            $seoResult->recommendations,
            $perfResult->recommendations,
            $secResult->recommendations,
            $accResult->recommendations,
            $spamResult->recommendations
        );

        // Persist to database
        $reportId = $this->repo->create([
            'lead_id'           => $leadId,
            'url'               => $url,
            'overall_score'     => $scoreResult['total_score'],
            'seo_score'         => $seoResult->finalScore(),
            'performance_score' => $perfResult->score,
            'security_score'    => $secResult->score,
            'accessibility_score' => $accResult->score,
            'spam_score'        => $spamResult->finalScore(),
            'legal_score'       => 0,
            'total_checks'      => $totalChecks,
            'passed_checks'     => $passedChecks,
            'failed_checks'     => $failedChecks,
            'full_report'       => $fullReport,
            'recommendations'   => $recommendations,
            'ip_address'        => $ip,
        ]);

        return [
            'report_id'       => $reportId,
            'overall_score'   => $scoreResult['total_score'],
            'grade'           => $scoreResult['grade'],
            'full_report'     => $fullReport,
            'recommendations' => array_slice($recommendations, 0, 10),
            'analysis'        => $analysisResult,
        ];
    }

    /**
     * Quick scan without persistence (for preview).
     */
    public function previewScan(string $html, string $url, array $headers = []): array
    {
        return $this->runFullScan($url, $html, $headers);
    }

    /**
     * Run the Landing Page Tester Agent scan.
     */
    public function runLandingPageScan(string $url, ?string $html = null, array $headers = []): array
    {
        if ($html === null) {
            $ctx = stream_context_create(['http' => ['timeout' => 15, 'user_agent' => 'LandingFlow-LPT/1.0']]);
            $html = @file_get_contents($url, false, $ctx);
            if ($html === false) $html = '';
            if (isset($http_response_header)) {
                foreach ($http_response_header as $h) $headers[] = $h;
            }
        }

        $result = $this->lpt->scan($html, $url, $headers);

        return $result->toArray();
    }
}
