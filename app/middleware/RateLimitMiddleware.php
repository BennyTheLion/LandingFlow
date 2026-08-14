<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Exceptions\RateLimitException;

/**
 * Rate Limiting Middleware — IP-based, file-backed.
 */
class RateLimitMiddleware
{
    protected string $cacheDir;
    protected int $maxRequests;
    protected int $window;

    public function __construct(?int $maxRequests = null, ?int $window = null, string $keyPrefix = 'default')
    {
        $this->cacheDir = STORAGE_PATH . '/cache/rate_limits/' . $keyPrefix;
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
        $this->maxRequests = $maxRequests ?? (defined('RATE_LIMIT_MAX') ? RATE_LIMIT_MAX : 100);
        $this->window = $window ?? (defined('RATE_LIMIT_WINDOW') ? RATE_LIMIT_WINDOW : 60);
    }

    public function handle(Request $request): void
    {
        $ip = $request->getClientIp();
        $file = $this->cacheDir . '/' . md5($ip) . '.json';
        $now = time();

        $fp = @fopen($file, 'c+');
        if (!$fp) {
            return;
        }

        $data = ['count' => 1, 'reset' => $now + $this->window];
        if (flock($fp, LOCK_EX)) {
            $raw = stream_get_contents($fp);
            $existing = $raw ? json_decode($raw, true) : null;

            if ($existing && $now <= $existing['reset']) {
                $data = $existing;
                $data['count']++;
            }

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($data));
            flock($fp, LOCK_UN);
        }
        fclose($fp);

        if ($data['count'] > $this->maxRequests) {
            throw new RateLimitException();
        }
    }
}
