<?php
namespace App\Models;

class AccessibilityResult
{
    public int $score = 100;
    public int $imagesWithoutAlt = 0;
    public int $inputsWithoutLabel = 0;
    public int $missingAriaLabels = 0;
    public bool $hasSkipLink = false;
    public int $headingGaps = 0;
    public array $issues = [];
    public array $recommendations = [];
    public array $priorityFixes = [];
    public string $summary = '';

    public function toArray(): array
    {
        return [
            'score'              => $this->score,
            'images_without_alt' => $this->imagesWithoutAlt,
            'inputs_without_label' => $this->inputsWithoutLabel,
            'missing_aria_labels' => $this->missingAriaLabels,
            'has_skip_link'      => $this->hasSkipLink,
            'heading_gaps'       => $this->headingGaps,
            'issues'             => $this->issues,
            'recommendations'    => $this->recommendations,
            'priority_fixes'     => $this->priorityFixes,
            'summary'            => $this->summary,
        ];
    }
}
