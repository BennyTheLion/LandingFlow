<?php
namespace App\Models;

/**
 * SeoResult — output of the 4-layer SEO intelligence scan.
 * Weights per SEO_ENGINE.md: SEO=0.4, LLMO=0.25, AEO=0.2, GEO=0.15
 */
class SeoResult
{
    public int $seoScore = 0;
    public int $llmoScore = 0;
    public int $aeoScore = 0;
    public int $geoScore = 0;
    public array $issues = [];
    public array $recommendations = [];
    public array $priorityFixes = [];
    public string $summary = '';

    public function finalScore(): int
    {
        return (int) round(
            $this->seoScore  * 0.40 +
            $this->llmoScore * 0.25 +
            $this->aeoScore  * 0.20 +
            $this->geoScore  * 0.15
        );
    }

    public function toArray(): array
    {
        return [
            'seo_score'       => $this->seoScore,
            'llmo_score'      => $this->llmoScore,
            'aeo_score'       => $this->aeoScore,
            'geo_score'       => $this->geoScore,
            'final_score'     => $this->finalScore(),
            'issues'          => $this->issues,
            'recommendations' => $this->recommendations,
            'priority_fixes'  => $this->priorityFixes,
            'summary'         => $this->summary,
        ];
    }
}
