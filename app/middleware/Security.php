<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Exceptions\RateLimitException;

/**
 * Rate Limiting Middleware — IP-based, file-backed.
 */
class RateLimitMiddleware
{
    private string $cacheDir;

    public function __construct()
    {
        $this->cacheDir = STORAGE_PATH . '/cache/rate_limits';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    public function handle(Request $request): void
    {
        $ip = $request->getClientIp();
        $maxRequests = defined('RATE_LIMIT_MAX') ? RATE_LIMIT_MAX : 100;
        $window = defined('RATE_LIMIT_WINDOW') ? RATE_LIMIT_WINDOW : 60;

        $file = $this->cacheDir . '/' . md5($ip) . '.json';
        $now = time();

        $fp = @fopen($file, 'c+');
        if (!$fp) {
            return;
        }

        if (flock($fp, LOCK_EX)) {
            $raw = stream_get_contents($fp);
            $data = $raw ? json_decode($raw, true) : null;

            if (!$data || $now > $data['reset']) {
                $data = ['count' => 1, 'reset' => $now + $window];
            } else {
                $data['count']++;
            }

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($data));
            flock($fp, LOCK_UN);
        }
        fclose($fp);

        if ($data['count'] > $maxRequests) {
            throw new RateLimitException();
        }
    }
}