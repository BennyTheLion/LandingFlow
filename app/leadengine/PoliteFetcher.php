<?php
namespace App\LeadEngine;

use App\Core\Logger;

/**
 * PoliteFetcher — every outbound page request the Lead Engine makes goes
 * through here (spec §7): identified User-Agent, robots.txt honoured, and one
 * request per 2 seconds per domain.
 *
 * The rate limit is per-process (an in-memory map of host => last request time),
 * which covers the CLI pipeline run and a single web request. It is not a
 * cluster-wide limiter; with one cron host that is the whole population.
 */
class PoliteFetcher
{
    /** host => unix timestamp of the last request */
    private array $lastRequestAt = [];

    /** host => parsed robots rules (null = fetched and none apply) */
    private array $robotsCache = [];

    private int $timeout;

    public function __construct(int $timeout = 12)
    {
        $this->timeout = $timeout;
    }

    /**
     * @return array{ok:bool,status:int,body:string,headers:array,time_ms:int,blocked:bool}
     */
    public function fetch(string $url, bool $respectRobots = true): array
    {
        $empty = ['ok' => false, 'status' => 0, 'body' => '', 'headers' => [], 'time_ms' => 0, 'blocked' => false];

        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return $empty;
        }

        if ($respectRobots && !$this->isAllowed($url)) {
            Logger::info('leadengine: robots.txt disallows path', ['url' => $url]);
            return array_merge($empty, ['blocked' => true]);
        }

        $this->throttle($host);

        $start = microtime(true);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 4,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_USERAGENT      => LeadEngineConfig::USER_AGENT,
            CURLOPT_ENCODING       => '',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        ]);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);
        curl_close($ch);

        $timeMs = (int) round((microtime(true) - $start) * 1000);

        if (!is_string($raw) || $error !== '') {
            Logger::info('leadengine: fetch failed', ['url' => $url, 'error' => $error]);
            return array_merge($empty, ['time_ms' => $timeMs, 'status' => $status]);
        }

        return [
            'ok'      => $status >= 200 && $status < 400,
            'status'  => $status,
            'body'    => substr($raw, $headerSize),
            'headers' => self::parseHeaders(substr($raw, 0, $headerSize)),
            'time_ms' => $timeMs,
            'blocked' => false,
        ];
    }

    /**
     * robots.txt check for our own User-Agent, falling back to the `*` group.
     * A missing or unreadable robots.txt means "allowed" (RFC 9309).
     */
    public function isAllowed(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = parse_url($url, PHP_URL_SCHEME) ?: 'https';
        if (!is_string($host) || $host === '') {
            return false;
        }

        if (!array_key_exists($host, $this->robotsCache)) {
            $this->robotsCache[$host] = $this->loadRobots($scheme . '://' . $host . '/robots.txt', $host);
        }

        $rules = $this->robotsCache[$host];
        if ($rules === null || $rules === []) {
            return true;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $query = parse_url($url, PHP_URL_QUERY);
        if (is_string($query) && $query !== '') {
            $path .= '?' . $query;
        }

        // Longest matching rule wins; Allow beats Disallow on equal length.
        $bestLength = -1;
        $allowed = true;
        foreach ($rules as [$type, $pattern]) {
            if ($pattern === '') {
                continue;
            }
            if (!str_starts_with($path, $pattern)) {
                continue;
            }
            $len = strlen($pattern);
            if ($len > $bestLength || ($len === $bestLength && $type === 'allow')) {
                $bestLength = $len;
                $allowed = $type === 'allow';
            }
        }
        return $allowed;
    }

    /**
     * @return list<array{0:string,1:string}>|null Directives for our UA, or null
     *         when robots.txt is absent/unreadable
     */
    private function loadRobots(string $robotsUrl, string $host): ?array
    {
        $this->throttle($host);

        $ch = curl_init($robotsUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT      => LeadEngineConfig::USER_AGENT,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($body) || $status !== 200) {
            return null;
        }

        return self::parseRobots($body);
    }

    /**
     * Collects the directive group for LandingFlowBot, else the `*` group.
     *
     * @return list<array{0:string,1:string}>
     */
    public static function parseRobots(string $body): array
    {
        $groups = [];      // ua => rules
        $currentUas = [];
        $sawRuleInGroup = false;

        foreach (preg_split('/\R/', $body) ?: [] as $line) {
            $line = trim(preg_replace('/#.*$/', '', $line) ?? '');
            if ($line === '' || !str_contains($line, ':')) {
                continue;
            }
            [$field, $value] = explode(':', $line, 2);
            $field = strtolower(trim($field));
            $value = trim($value);

            if ($field === 'user-agent') {
                // A new UA line after rules starts a fresh group
                if ($sawRuleInGroup) {
                    $currentUas = [];
                    $sawRuleInGroup = false;
                }
                $currentUas[] = strtolower($value);
                $groups[strtolower($value)] ??= [];
                continue;
            }

            if (($field === 'disallow' || $field === 'allow') && $currentUas !== []) {
                $sawRuleInGroup = true;
                foreach ($currentUas as $ua) {
                    $groups[$ua][] = [$field, self::normalizeRobotsPath($value)];
                }
            }
        }

        foreach (['landingflowbot', '*'] as $ua) {
            if (isset($groups[$ua])) {
                return $groups[$ua];
            }
        }
        return [];
    }

    /** Strip trailing wildcards so a str_starts_with prefix match is correct */
    private static function normalizeRobotsPath(string $path): string
    {
        if ($path === '') {
            return '';
        }
        $star = strpos($path, '*');
        if ($star !== false) {
            $path = substr($path, 0, $star);
        }
        return rtrim($path, '$');
    }

    /** Sleep just enough to keep 2s between hits on the same host (§7) */
    private function throttle(string $host): void
    {
        $last = $this->lastRequestAt[$host] ?? null;
        if ($last !== null) {
            $elapsed = microtime(true) - $last;
            $wait = LeadEngineConfig::CRAWL_DELAY_SECONDS - $elapsed;
            if ($wait > 0) {
                usleep((int) ($wait * 1_000_000));
            }
        }
        $this->lastRequestAt[$host] = microtime(true);
    }

    /** @return array<string,string> lowercased header name => value */
    private static function parseHeaders(string $raw): array
    {
        $headers = [];
        foreach (preg_split('/\R/', $raw) ?: [] as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $headers[strtolower(trim($name))] = trim($value);
        }
        return $headers;
    }

    /** Normalize any URL to a bare deduplication key (spec §5) */
    public static function domainKey(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?: $url;
        $host = strtolower(trim($host));
        $host = preg_replace('#^www\.#', '', $host) ?? $host;
        return rtrim($host, '.');
    }

    /** Add a scheme when the user pasted a bare domain */
    public static function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }
        return rtrim($url, '/');
    }
}
