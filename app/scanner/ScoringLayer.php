<?php
namespace App\Scanner;

/**
 * ScoringLayer — contract for each of the 4 intelligence layers.
 * Each layer receives HTML content and returns a score (0-100)
 * with issues and recommendations.
 */
interface ScoringLayer
{
    public function analyze(string $html, string $url): array;
    // Returns: ['score' => int(0-100), 'issues' => string[], 'recommendations' => string[]]
}
