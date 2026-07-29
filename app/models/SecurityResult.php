<?php
namespace App\Models;

class SecurityResult
{
    public int $score = 100;
    public bool $hasHttps = false;
    public bool $hasHsts = false;
    public bool $hasXFrameOptions = false;
    public bool $hasXContentTypeOptions = false;
    public bool $hasCsp = false;
    public bool $hasSecureCookies = false;
    public int $insecureForms = 0;
    public int $mixedContentCount = 0;
    public array $issues = [];
    public array $recommendations = [];
    public array $priorityFixes = [];
    public string $summary = '';

    public function toArray(): array
    {
        return [
            'score'              => $this->score,
            'has_https'          => $this->hasHttps,
            'has_hsts'           => $this->hasHsts,
            'has_x_frame_options' => $this->hasXFrameOptions,
            'has_x_content_type'  => $this->hasXContentTypeOptions,
            'has_csp'            => $this->hasCsp,
            'has_secure_cookies' => $this->hasSecureCookies,
            'insecure_forms'     => $this->insecureForms,
            'mixed_content'      => $this->mixedContentCount,
            'issues'             => $this->issues,
            'recommendations'    => $this->recommendations,
            'priority_fixes'     => $this->priorityFixes,
            'summary'            => $this->summary,
        ];
    }
}
