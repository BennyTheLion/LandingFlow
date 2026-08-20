<?php
namespace App\LeadEngine;

use App\Core\Logger;

/**
 * PageSpeedClient — Google PageSpeed Insights API (spec §6).
 *
 * Free with a key (25k/day). When no key is configured, or the API fails, the
 * engine falls back to local heuristics — perf_mobile is 30% of hot_score, so
 * we record which source produced it (AuditResult::$perfSource) rather than
 * pretending a heuristic is a Lighthouse run.
 */
class PageSpeedClient
{
    private const ENDPOINT = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    private string $apiKey;
    private int $timeout;

    public function __construct(?string $apiKey = null, int $timeout = 60)
    {
        $this->apiKey = $apiKey ?? (defined('PAGESPEED_API_KEY') ? PAGESPEED_API_KEY : '');
        $this->timeout = $timeout;
    }

    public function isAvailable(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * @return array{performance:?int,seo:?int,accessibility:?int,best_practices:?int}|null
     *         null when unavailable or the call failed
     */
    public function analyze(string $url, string $strategy = 'mobile'): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        $query = http_build_query([
            'url'      => $url,
            'strategy' => $strategy,
            'key'      => $this->apiKey,
        ]);
        // category[] repeats, which http_build_query cannot express cleanly
        $categories = '&category=performance&category=seo&category=accessibility&category=best-practices';

        $ch = curl_init(self::ENDPOINT . '?' . $query . $categories);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT      => LeadEngineConfig::USER_AGENT,
        ]);
        $body = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error !== '' || $httpCode !== 200 || !is_string($body)) {
            Logger::warning('leadengine: pagespeed call failed', [
                'url' => $url, 'strategy' => $strategy, 'http' => $httpCode, 'error' => $error,
            ]);
            return null;
        }

        $data = json_decode($body, true);
        $categoriesOut = $data['lighthouseResult']['categories'] ?? null;
        if (!is_array($categoriesOut)) {
            Logger::warning('leadengine: pagespeed response missing categories', ['url' => $url]);
            return null;
        }

        return [
            'performance'    => self::pct($categoriesOut['performance']['score'] ?? null),
            'seo'            => self::pct($categoriesOut['seo']['score'] ?? null),
            'accessibility'  => self::pct($categoriesOut['accessibility']['score'] ?? null),
            'best_practices' => self::pct($categoriesOut['best-practices']['score'] ?? null),
        ];
    }

    /** Lighthouse returns 0..1 floats; the spec stores 0..100 ints */
    private static function pct(mixed $score): ?int
    {
        return is_numeric($score) ? (int) round($score * 100) : null;
    }
}
