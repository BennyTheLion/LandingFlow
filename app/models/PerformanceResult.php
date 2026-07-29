<?php
namespace App\Models;

/**
 * PerformanceResult — output of the performance scan.
 */
class PerformanceResult
{
    public int $score = 100;
    public float $pageSizeKb = 0;
    public int $httpRequests = 0;
    public bool $hasCompression = false;
    public bool $hasCaching = false;
    public int $imageCount = 0;
    public int $unoptimizedImages = 0;
    public int $domNodes = 0;
    public bool $hasRenderBlocking = false;
    public array $issues = [];
    public array $recommendations = [];
    public array $priorityFixes = [];
    public string $summary = '';

    public function toArray(): array
    {
        return [
            'score'           => $this->score,
            'page_size_kb'    => $this->pageSizeKb,
            'http_requests'   => $this->httpRequests,
            'has_compression' => $this->hasCompression,
            'has_caching'     => $this->hasCaching,
            'image_count'     => $this->imageCount,
            'unoptimized_images' => $this->unoptimizedImages,
            'dom_nodes'       => $this->domNodes,
            'has_render_blocking' => $this->hasRenderBlocking,
            'issues'          => $this->issues,
            'recommendations' => $this->recommendations,
            'priority_fixes'  => $this->priorityFixes,
            'summary'         => $this->summary,
        ];
    }
}
