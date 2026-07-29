<?php
namespace App\Ai;

/**
 * AnalysisAgent — interprets scanner results into human-readable insights.
 * Generates priority-ranked recommendations from raw scan data.
 */
class AnalysisAgent
{
    public function analyze(array $scanResults): array
    {
        $insights = [];
        $priorities = [];
        $overallAssessment = '';

        // Collect all issues across scanners
        $allIssues = [];
        $scannerNames = ['seo' => 'SEO', 'performance' => 'Performance', 'security' => 'Security', 'accessibility' => 'Accessibility', 'spam' => 'Spam Detection'];

        foreach ($scannerNames as $key => $name) {
            if (isset($scanResults[$key]['issues'])) {
                foreach ($scanResults[$key]['issues'] as $issue) {
                    $allIssues[] = ['scanner' => $name, 'issue' => $issue];
                }
            }
        }

        // Priority ranking: security > accessibility > performance > SEO
        $priorityOrder = ['Security' => 1, 'Spam Detection' => 2, 'Accessibility' => 3, 'Performance' => 4, 'SEO' => 5];
        usort($allIssues, fn($a, $b) => ($priorityOrder[$a['scanner']] ?? 5) <=> ($priorityOrder[$b['scanner']] ?? 5));

        // Generate insights
        $categoryCounts = [];
        foreach ($allIssues as $item) {
            $cat = $item['scanner'];
            $categoryCounts[$cat] = ($categoryCounts[$cat] ?? 0) + 1;
        }

        foreach ($categoryCounts as $cat => $count) {
            $severity = $count >= 5 ? 'Critical' : ($count >= 3 ? 'Moderate' : 'Minor');
            $insights[] = "[$cat] $count issue(s) detected — $severity priority";
        }

        // Top 5 priorities
        $priorities = array_slice(array_map(fn($i) => "[{$i['scanner']}] {$i['issue']}", $allIssues), 0, 5);

        // Overall assessment
        $totalIssues = count($allIssues);
        if ($totalIssues === 0) {
            $overallAssessment = 'Excellent — no issues detected across all scanners.';
        } elseif ($totalIssues <= 5) {
            $overallAssessment = "Good — only $totalIssues minor issue(s) found. Quick fixes available.";
        } elseif ($totalIssues <= 10) {
            $overallAssessment = "Moderate — $totalIssues issues detected. Address priorities to improve scores.";
        } else {
            $overallAssessment = "Significant work needed — $totalIssues issues across scanners. Focus on security and accessibility first.";
        }

        return [
            'insights'            => $insights,
            'total_issues'        => $totalIssues,
            'priorities'          => $priorities,
            'overall_assessment'  => $overallAssessment,
        ];
    }
}
