<?php
namespace App\Ai;

/**
 * ScoringAgent — computes weighted composite score from scanner results.
 * Weights: SEO=20%, Performance=20%, Security=20%, Accessibility=15%, Legal=15%, UX=10%
 */
class ScoringAgent
{
    private const WEIGHTS = [
        'seo'           => 0.20,
        'performance'   => 0.20,
        'security'      => 0.20,
        'accessibility' => 0.15,
        'legal'         => 0.15,
        'ux'            => 0.10,
    ];

    public function compute(array $scores): array
    {
        $total = 0;
        $breakdown = [];

        foreach (self::WEIGHTS as $key => $weight) {
            $score = $scores[$key] ?? 0;
            $weighted = round($score * $weight, 1);
            $total += $weighted;
            $breakdown[$key] = ['raw' => $score, 'weight' => $weight * 100, 'weighted' => $weighted];
        }

        $final = (int) round($total);

        return [
            'total_score' => $final,
            'breakdown'   => $breakdown,
            'grade'       => $this->grade($final),
        ];
    }

    private function grade(int $score): string
    {
        if ($score >= 90) return 'A — Excellent';
        if ($score >= 80) return 'B — Good';
        if ($score >= 70) return 'C — Average';
        if ($score >= 60) return 'D — Below Average';
        return 'F — Poor';
    }
}
