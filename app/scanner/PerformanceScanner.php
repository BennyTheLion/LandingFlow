<?php
namespace App\Scanner;

use App\Models\PerformanceResult;

/**
 * PerformanceScanner — evaluates page speed, size, and optimization.
 * Checks: page size, requests, compression, caching, images, DOM, render-blocking.
 */
class PerformanceScanner implements PerformanceScannerInterface
{
    public function scan(string $html, string $url, array $headers = []): PerformanceResult
    {
        $r = new PerformanceResult();

        // Page size
        $r->pageSizeKb = round(strlen($html) / 1024, 1);
        if ($r->pageSizeKb > 500) {
            $r->issues[] = "Page size too large ({$r->pageSizeKb} KB) — aim for <500 KB";
            $r->score -= 15;
            $r->recommendations[] = 'Minify HTML, CSS, and JavaScript to reduce page size';
        } elseif ($r->pageSizeKb > 200) {
            $r->issues[] = "Page size above optimal ({$r->pageSizeKb} KB)";
            $r->score -= 5;
            $r->recommendations[] = 'Consider compressing assets to reduce page weight';
        }

        // HTTP requests (estimate from resource references)
        $r->httpRequests = $this->countHttpRequests($html);
        if ($r->httpRequests > 50) {
            $r->issues[] = "Too many HTTP requests ({$r->httpRequests}) — aim for <50";
            $r->score -= 15;
            $r->recommendations[] = 'Bundle CSS/JS files and use image sprites to reduce requests';
        } elseif ($r->httpRequests > 30) {
            $r->issues[] = "High HTTP request count ({$r->httpRequests})";
            $r->score -= 7;
        }

        // Compression
        $r->hasCompression = $this->checkCompression($headers);
        if (!$r->hasCompression) {
            $r->issues[] = 'Content compression (gzip/brotli) not detected';
            $r->score -= 10;
            $r->recommendations[] = 'Enable gzip or brotli compression on your web server';
        }

        // Caching headers
        $r->hasCaching = $this->checkCaching($headers);
        if (!$r->hasCaching) {
            $r->issues[] = 'No cache-control or expires headers detected';
            $r->score -= 10;
            $r->recommendations[] = 'Set Cache-Control headers for static assets (images, CSS, JS)';
        }

        // Images
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html !== '' ? $html : '<html></html>', 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);

        $images = $xpath->query('//img');
        $r->imageCount = $images->length;
        foreach ($images as $img) {
            $w = $img->getAttribute('width');
            $h = $img->getAttribute('height');
            $alt = $img->getAttribute('alt');
            if (empty($w) || empty($h)) {
                $r->unoptimizedImages++;
            }
        }
        if ($r->unoptimizedImages > 0) {
            $r->issues[] = "{$r->unoptimizedImages}/{$r->imageCount} images missing width/height — layout shift risk";
            $r->score -= min(10, $r->unoptimizedImages * 3);
            $r->recommendations[] = 'Add explicit width and height to all <img> tags';
        }

        // DOM complexity
        $r->domNodes = $xpath->query('//*')->length;
        if ($r->domNodes > 1500) {
            $r->issues[] = "DOM too large ({$r->domNodes} nodes) — may slow rendering";
            $r->score -= 10;
            $r->recommendations[] = 'Reduce DOM size by simplifying HTML structure';
        } elseif ($r->domNodes > 800) {
            $r->issues[] = "DOM size above optimal ({$r->domNodes} nodes)";
            $r->score -= 5;
        }

        // Render-blocking resources
        $scripts = $xpath->query('//script[not(@async) and not(@defer) and @src]');
        $styles = $xpath->query('//link[@rel="stylesheet"][not(@media="print")]');
        $r->hasRenderBlocking = ($scripts->length + $styles->length) > 3;
        if ($r->hasRenderBlocking) {
            $r->issues[] = 'Render-blocking resources detected — add async/defer to scripts';
            $r->score -= 8;
            $r->recommendations[] = 'Use async or defer for non-critical JavaScript; inline critical CSS';
        }

        // Priority fixes
        foreach ($r->issues as $issue) {
            if (stripos($issue, 'compression') !== false || stripos($issue, 'cache') !== false) {
                if (!in_array($issue, $r->priorityFixes)) $r->priorityFixes[] = $issue;
            }
        }

        // Summary
        $r->score = max(0, min(100, $r->score));
        if ($r->score >= 80) {
            $r->summary = "Good performance ({$r->score}/100). Page size: {$r->pageSizeKb}KB, {$r->httpRequests} requests. Minor optimizations possible.";
        } elseif ($r->score >= 60) {
            $r->summary = "Moderate performance ({$r->score}/100). Page size: {$r->pageSizeKb}KB. Address caching and compression.";
        } else {
            $r->summary = "Poor performance ({$r->score}/100). Page size: {$r->pageSizeKb}KB, {$r->httpRequests} requests. Significant optimization needed.";
        }

        return $r;
    }

    private function countHttpRequests(string $html): int
    {
        $count = 0;
        $count += preg_match_all('/<script\s[^>]*src=["\']/i', $html);
        $count += preg_match_all('/<link\s[^>]*href=["\']/i', $html);
        $count += preg_match_all('/<img\s[^>]*src=["\']/i', $html);
        $count += preg_match_all('/url\(/i', $html);
        return $count;
    }

    private function checkCompression(array $headers): bool
    {
        foreach ($headers as $key => $value) {
            $k = strtolower(is_int($key) ? $value : $key);
            if (strpos($k, 'content-encoding') !== false) return true;
        }
        return false;
    }

    private function checkCaching(array $headers): bool
    {
        $cacheHeaders = ['cache-control', 'expires', 'etag', 'last-modified'];
        foreach ($headers as $key => $value) {
            $k = strtolower(is_int($key) ? $value : $key);
            foreach ($cacheHeaders as $ch) {
                if (strpos($k, $ch) !== false) return true;
            }
        }
        return false;
    }
}
