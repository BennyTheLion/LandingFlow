<?php
namespace App\Models;

/**
 * LandingPageResult — comprehensive output of the landing page test agent.
 *
 * Covers 7 categories: render, technical, design, mobile,
 * performance, ux-conversion, and content.
 */
class LandingPageResult
{
    // Overall
    public int $score = 100;
    public float $testTime = 0;
    public int $totalChecks = 0;
    public int $passedChecks = 0;
    public int $warningChecks = 0;
    public int $failedChecks = 0;

    // Category scores (0-100)
    public int $renderScore = 100;
    public int $technicalScore = 100;
    public int $designScore = 100;
    public int $mobileScore = 100;
    public int $performanceScore = 100;
    public int $uxScore = 100;
    public int $contentScore = 100;

    // Detailed check results
    // Structure: ['category' => 'Render', 'check' => 'Body has content', 'status' => 'passed'|'warning'|'failed', 'detail' => '...']
    public array $checks = [];

    // Issues grouped by severity
    public array $issues = [];
    public array $recommendations = [];

    // Priority fixes (Critical, Important, Nice-to-have)
    public array $criticalFixes = [];
    public array $importantFixes = [];
    public array $niceFixes = [];

    // Summary
    public string $summary = '';

    // Raw data used for checks
    public array $raw = [];

    /**
     * Add a check result.
     */
    public function addCheck(
        string $category,
        string $check,
        string $status, // 'passed' | 'warning' | 'failed'
        string $detail = '',
        string $recommendation = ''
    ): void {
        $this->checks[] = [
            'category'       => $category,
            'check'          => $check,
            'status'         => $status,
            'detail'         => $detail,
            'recommendation' => $recommendation,
        ];
        $this->totalChecks++;
        switch ($status) {
            case 'passed':  $this->passedChecks++;  break;
            case 'warning': $this->warningChecks++;  break;
            case 'failed':  $this->failedChecks++;   break;
        }
    }

    /**
     * Add an issue with severity.
     */
    public function addIssue(string $category, string $issue, string $severity = 'warning', string $fix = ''): void
    {
        $entry = ['category' => $category, 'issue' => $issue];
        $this->issues[] = $entry;
        if ($fix) {
            $this->recommendations[] = $fix;
        }

        switch ($severity) {
            case 'critical':
                $this->criticalFixes[] = $fix ?: $issue;
                break;
            case 'important':
                $this->importantFixes[] = $fix ?: $issue;
                break;
            default:
                $this->niceFixes[] = $fix ?: $issue;
                break;
        }
    }

    /**
     * Compute final score from category scores.
     */
    public function computeFinalScore(): int
    {
        $scores = [
            $this->renderScore,
            $this->technicalScore,
            $this->designScore,
            $this->mobileScore,
            $this->performanceScore,
            $this->uxScore,
            $this->contentScore,
        ];
        $this->score = (int) round(array_sum($scores) / count($scores));
        $this->score = max(0, min(100, $this->score));
        return $this->score;
    }

    public function toArray(): array
    {
        return [
            'score'            => $this->score,
            'test_time'        => $this->testTime,
            'total_checks'     => $this->totalChecks,
            'passed_checks'    => $this->passedChecks,
            'warning_checks'   => $this->warningChecks,
            'failed_checks'    => $this->failedChecks,
            'render_score'     => $this->renderScore,
            'technical_score'  => $this->technicalScore,
            'design_score'     => $this->designScore,
            'mobile_score'     => $this->mobileScore,
            'performance_score'=> $this->performanceScore,
            'ux_score'         => $this->uxScore,
            'content_score'    => $this->contentScore,
            'checks'           => $this->checks,
            'issues'           => $this->issues,
            'recommendations'  => $this->recommendations,
            'critical_fixes'   => $this->criticalFixes,
            'important_fixes'  => $this->importantFixes,
            'nice_fixes'       => $this->niceFixes,
            'summary'          => $this->summary,
            'raw'              => $this->raw,
        ];
    }
}
