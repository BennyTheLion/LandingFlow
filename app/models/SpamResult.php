<?php
namespace App\Models;

/**
 * SpamResult — output of the Spam Detection Layer.
 * Calculates a Spam Risk Score (0-100) across 7 detection categories.
 */
class SpamResult
{
    public int $score = 100; // starts at 100, deductions applied
    public array $categories = [];
    public array $issues = [];
    public array $recommendations = [];
    public array $priorityFixes = [];
    public string $summary = '';
    public string $riskLevel = 'LOW';

    // Category subscores
    public int $securityScore = 100;
    public int $hiddenContentScore = 100;
    public int $contentScore = 100;
    public int $linksScore = 100;
    public int $domainScore = 100;
    public int $emailScore = 100;
    public int $formScore = 100;

    // Form spam-protection breakdown (used by the audit page for per-mechanism checks)
    public int $formCount = 0;
    public bool $formHasCaptcha = true;
    public bool $formHasHoneypot = true;
    public bool $formHasCsrf = true;
    public bool $formHasValidation = true;

    /** Weighted final score */
    public function finalScore(): int
    {
        return (int) round(
            $this->securityScore       * 0.30 +
            $this->hiddenContentScore  * 0.20 +
            $this->contentScore        * 0.15 +
            $this->linksScore          * 0.15 +
            $this->domainScore         * 0.10 +
            $this->emailScore          * 0.05 +
            $this->formScore           * 0.05
        );
    }

    /** Risk level based on final score */
    public function riskLevel(): string
    {
        $s = $this->finalScore();
        $spamScore = 100 - $s; // invert: higher spam risk = lower score
        if ($spamScore <= 20) return 'LOW';
        if ($spamScore <= 50) return 'MEDIUM';
        if ($spamScore <= 80) return 'HIGH';
        return 'CRITICAL';
    }

    public function spamScore(): int
    {
        return 100 - $this->finalScore();
    }

    public function toArray(): array
    {
        return [
            'spam_score'       => $this->spamScore(),
            'risk_level'       => $this->riskLevel(),
            'final_score'      => $this->finalScore(),
            'categories'       => [
                'security'       => ['score' => $this->securityScore,      'weight' => 0.30, 'issues' => $this->catIssues('security')],
                'hidden_content' => ['score' => $this->hiddenContentScore, 'weight' => 0.20, 'issues' => $this->catIssues('hidden_content')],
                'content'        => ['score' => $this->contentScore,       'weight' => 0.15, 'issues' => $this->catIssues('content')],
                'links'          => ['score' => $this->linksScore,         'weight' => 0.15, 'issues' => $this->catIssues('links')],
                'domain'         => ['score' => $this->domainScore,        'weight' => 0.10, 'issues' => $this->catIssues('domain')],
                'email'          => ['score' => $this->emailScore,         'weight' => 0.05, 'issues' => $this->catIssues('email')],
                'form'           => ['score' => $this->formScore,          'weight' => 0.05, 'issues' => $this->catIssues('form')],
            ],
            'issues'           => $this->issues,
            'recommendations'  => $this->recommendations,
            'priority_fixes'   => $this->priorityFixes,
            'summary'          => $this->summary,
        ];
    }

    /** Track which category each issue belongs to */
    private array $issueCategories = [];

    public function addIssue(string $issue, string $category, int $deduction): void
    {
        $tagged = "[SPAM:{$category}] $issue";
        $this->issues[] = $tagged;
        $this->issueCategories[] = $category;

        switch ($category) {
            case 'security':       $this->securityScore       = max(0, $this->securityScore       - $deduction); break;
            case 'hidden_content': $this->hiddenContentScore  = max(0, $this->hiddenContentScore  - $deduction); break;
            case 'content':        $this->contentScore        = max(0, $this->contentScore        - $deduction); break;
            case 'links':          $this->linksScore          = max(0, $this->linksScore          - $deduction); break;
            case 'domain':         $this->domainScore         = max(0, $this->domainScore         - $deduction); break;
            case 'email':          $this->emailScore          = max(0, $this->emailScore          - $deduction); break;
            case 'form':           $this->formScore           = max(0, $this->formScore           - $deduction); break;
        }
    }

    public function addRecommendation(string $rec): void
    {
        if (!in_array($rec, $this->recommendations)) {
            $this->recommendations[] = $rec;
        }
    }

    public function addPriorityFix(string $fix): void
    {
        if (!in_array($fix, $this->priorityFixes)) {
            $this->priorityFixes[] = $fix;
        }
    }

    private function catIssues(string $category): array
    {
        $out = [];
        foreach ($this->issues as $i => $issue) {
            if (($this->issueCategories[$i] ?? '') === $category) {
                $out[] = $issue;
            }
        }
        return $out;
    }
}
