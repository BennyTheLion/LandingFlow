<?php
namespace App\Services;

class MonitoringService
{
    public function check(string $url, int $timeout = 10): array
    {
        $url = $this->normalizeUrl($url);
        $result = ['url' => $url, 'status' => 'offline', 'http_code' => 0, 'response_ms' => null, 'ssl_valid' => false, 'checked_at' => date('Y-m-d H:i:s'), 'seo' => ['score' => 0, 'issues' => []], 'security' => ['score' => 0, 'issues' => []], 'headers' => [], 'error' => null];
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5, CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_HEADER => true, CURLOPT_NOBODY => false]);
        $start = microtime(true);
        $response = curl_exec($ch);
        $end = microtime(true);
        if (curl_errno($ch)) { $result['error'] = curl_error($ch); curl_close($ch); return $result; }
        $result['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result['response_ms'] = round(($end - $start) * 1000);
        $result['status'] = ($result['http_code'] >= 200 && $result['http_code'] < 400) ? 'online' : 'issues';
        $result['ssl_valid'] = str_starts_with($url, 'https');
        $result['ssl'] = ['expires_at' => null, 'days_left' => null, 'status' => 'not_configured'];
        if ($result['ssl_valid']) {
            $host = parse_url($url, PHP_URL_HOST);
            if ($host) $result['ssl'] = $this->checkSslCertificate($host);
        }
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headersRaw = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        curl_close($ch);
        $result['headers'] = $this->parseHeaders($headersRaw);
        if (!empty($body) && $result['http_code'] < 400) $result['seo'] = $this->checkSeo($body, $url);
        $result['security'] = $this->checkSecurity($result['headers'], $result['http_code'], $url, $body);
        return $result;
    }

    public function checkSingle(string $url, int $timeout = 10): array
    {
        $c = $this->check($url, $timeout);
        return ['status' => $c['status'], 'response_time_ms' => $c['response_ms'], 'http_code' => $c['http_code'], 'ssl_valid' => $c['ssl_valid'], 'ssl_expires_at' => $c['ssl']['expires_at'] ?? null, 'ssl_status' => $c['ssl']['status'] ?? 'not_configured', 'seo_score' => $c['seo']['score'], 'seo_issues' => $c['seo']['issues'], 'security_score' => $c['security']['score'], 'security_issues' => $c['security']['issues'], 'last_checked_at' => date('Y-m-d H:i:s'), 'error' => $c['error']];
    }

    /**
     * Look up the real certificate expiry for an https host (no dependency on the
     * app's own curl request, since curl doesn't expose certificate validity dates).
     */
    public function checkSslCertificate(string $host, int $timeout = 5): array
    {
        $result = ['expires_at' => null, 'days_left' => null, 'status' => 'not_configured'];
        $context = stream_context_create(['ssl' => ['capture_peer_cert' => true, 'verify_peer' => false, 'verify_peer_name' => false]]);
        $client = @stream_socket_client("ssl://{$host}:443", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$client) return $result;
        $params = stream_context_get_params($client);
        fclose($client);
        $cert = $params['options']['ssl']['peer_certificate'] ?? null;
        if (!$cert) return $result;
        $info = openssl_x509_parse($cert);
        $validTo = $info['validTo_time_t'] ?? null;
        if (!$validTo) return $result;
        $daysLeft = (int)floor(($validTo - time()) / 86400);
        $result['expires_at'] = date('Y-m-d', $validTo);
        $result['days_left'] = $daysLeft;
        $result['status'] = $daysLeft < 0 ? 'expired' : ($daysLeft <= 14 ? 'expiring_soon' : 'valid');
        return $result;
    }

    /**
     * Single entry point for "check a site and persist the result" used by the manual
     * check-now button, the admin page's auto-refresh, and the cron script alike — keeps
     * monitoring_logs status values in sync with its ENUM('up','down') and uptime_percentage
     * actually up to date, and fires alerts on real status/SSL transitions.
     */
    public function runCheckAndPersist(array $site, \PDO $db, int $timeout = 5): array
    {
        $c = $this->checkSingle($site['url'], $timeout);
        $logStatus = $c['status'] === 'online' ? 'up' : 'down';
        $db->prepare(
            "INSERT INTO monitoring_logs (website_id, status, http_code, response_time_ms, error_message, checked_at) VALUES (?,?,?,?,?,NOW())"
        )->execute([$site['id'], $logStatus, $c['http_code'] ?: null, $c['response_time_ms'], $c['error']]);

        $counts = $db->prepare(
            "SELECT COUNT(*) AS total, SUM(status='up') AS ups FROM monitoring_logs WHERE website_id = ? AND checked_at >= NOW() - INTERVAL 30 DAY"
        );
        $counts->execute([$site['id']]);
        $row = $counts->fetch(\PDO::FETCH_ASSOC);
        $uptime = ($row && $row['total'] > 0) ? round(($row['ups'] / $row['total']) * 100, 2) : 100.00;

        $previousStatus = $site['status'] ?? null;

        $db->prepare(
            "UPDATE monitoring_websites SET status=?, response_time_ms=?, ssl_expires_at=?, ssl_status=?, uptime_percentage=?, last_checked_at=NOW(), updated_at=NOW() WHERE id=?"
        )->execute([$c['status'], $c['response_time_ms'], $c['ssl_expires_at'], $c['ssl_status'], $uptime, $site['id']]);

        $site = array_merge($site, $c, ['uptime_percentage' => $uptime]);

        $alerts = new MonitoringAlertService();
        if ($previousStatus !== null && $previousStatus !== 'offline' && $c['status'] === 'offline') $alerts->down($site);
        if ($previousStatus === 'offline' && $c['status'] === 'online') $alerts->up($site);
        if ($c['ssl_status'] === 'expiring_soon') $alerts->sslExpiring($site);
        if (($c['response_time_ms'] ?? 0) > 3000) $alerts->slowResponse($site);

        return $site;
    }

    public function generateRecommendations(array $data): array
    {
        $recs = [];
        $seo = $data['seo_score'] ?? ($data['seo']['score'] ?? 70);
        $sec = $data['security_score'] ?? ($data['security']['score'] ?? 60);
        if ($seo < 80) $recs[] = ['category' => 'SEO', 'priority' => 'high', 'action' => 'שיפור SEO — title, description, H1, robots, canonical, OG', 'impact' => 'דירוג גבוה יותר בגוגל'];
        if ($sec < 80) $recs[] = ['category' => 'אבטחה', 'priority' => 'high', 'action' => 'הוספת Headers אבטחה (HSTS, CSP, X-Frame, XSS)', 'impact' => 'הגנה מפני פריצות'];
        if (($data['response_time_ms'] ?? 999) > 500) $recs[] = ['category' => 'ביצועים', 'priority' => 'medium', 'action' => 'שיפור מהירות — אופטימיזציה, Caching, CDN', 'impact' => 'חוויית משתמש טובה'];
        if (empty($data['ssl_valid'])) $recs[] = ['category' => 'אבטחה', 'priority' => 'critical', 'action' => 'התקנת SSL', 'impact' => 'אמון ואבטחת מידע'];
        if (!$recs) $recs[] = ['category' => 'כללי', 'priority' => 'low', 'action' => 'האתר במצב טוב. ניטור שוטף מומלץ.', 'impact' => 'שמירה על ביצועים'];
        return $recs;
    }

    /** Same SEO scoring as AuditController::seoC() */
    private function checkSeo(string $html, string $url): array
    {
        $c = [];
        preg_match("/<title[^>]*>(.*?)<\/title>/si", $html, $m);
        $t = trim(strip_tags($m[1] ?? ''));
        $c['title'] = ['passed' => !empty($t), 'label' => 'כותרת (Title)', 'value' => $t ? mb_substr($t, 0, 80) : 'חסר'];
        preg_match("/<meta[^>]+name=.description.[^>]+content=.([^.]*)./si", $html, $md);
        $d = $md[1] ?? '';
        $c['desc'] = ['passed' => !empty($d), 'label' => 'Meta Description', 'value' => $d ? mb_substr($d, 0, 80) : 'חסר'];
        preg_match("/<h1[^>]*>(.*?)<\/h1>/si", $html, $m);
        $h1 = trim(strip_tags($m[1] ?? ''));
        $c['h1'] = ['passed' => !empty($h1), 'label' => 'כותרת H1', 'value' => $h1 ?: 'חסר'];
        $rb = @file_get_contents($url . '/robots.txt', false, stream_context_create(['http' => ['timeout' => 3]]));
        $c['robots'] = ['passed' => $rb !== false, 'label' => 'Robots.txt', 'value' => $rb !== false ? 'קיים' : 'לא נמצא'];
        $sm = @file_get_contents($url . '/sitemap.xml', false, stream_context_create(['http' => ['timeout' => 3]]));
        $c['sitemap'] = ['passed' => $sm !== false, 'label' => 'Sitemap.xml', 'value' => $sm !== false ? 'קיים' : 'לא נמצא'];
        preg_match("/<link[^>]+rel=.canonical.[^>]+href=.[^\"']+./si", $html, $m);
        $can = $m[0] ?? '';
        $c['canonical'] = ['passed' => !empty($can), 'label' => 'Canonical URL', 'value' => $can ?: 'לא נמצא'];
        $hasOg = strpos($html, 'og:title') !== false || strpos($html, 'og:description') !== false;
        $c['og'] = ['passed' => $hasOg, 'label' => 'Open Graph', 'value' => $hasOg ? '✅ מוגדר' : '❌ לא מוגדר'];
        $passed = count(array_filter($c, fn($x) => $x['passed']));
        $score = round($passed / count($c) * 100);
        $issues = [];
        foreach ($c as $v) if (!$v['passed']) $issues[] = $v['label'];
        return ['score' => $score, 'checks' => $c, 'issues' => $issues, 'label' => 'SEO', 'title' => $t];
    }

    /** Same security scoring as AuditController::secC() */
    private function checkSecurity(array $headers, int $httpCode, string $url, string $html = ''): array
    {
        $c = [];
        $https = str_starts_with($url, 'https://');
        $c['https'] = ['passed' => $https, 'label' => 'HTTPS', 'value' => $https ? '✅ HTTPS' : '❌ HTTP'];
        $c['ssl'] = ['passed' => $https, 'label' => 'SSL', 'value' => $https ? '✅ תעודה' : '❌ אין'];
        $sh = ['Strict-Transport-Security' => 'HSTS', 'X-Frame-Options' => 'X-Frame', 'X-Content-Type-Options' => 'X-Content', 'Content-Security-Policy' => 'CSP', 'X-XSS-Protection' => 'XSS'];
        foreach ($sh as $k => $l) {
            $f = false;
            foreach ($headers as $hk => $hv) if (strcasecmp($hk, $k) === 0) { $f = true; break; }
            $c[strtolower($k)] = ['passed' => $f, 'label' => $l, 'value' => $f ? '✅ קיים' : '❌ חסר'];
        }
        $mx = $https && $html ? (bool)preg_match("/src=.http:\/\//", $html) : false;
        $c['mixed'] = ['passed' => !$mx, 'label' => 'Mixed Content', 'value' => $mx ? '⚠️ נמצא' : '✅ תקין'];
        $passed = count(array_filter($c, fn($x) => $x['passed']));
        $score = round($passed / count($c) * 100);
        $issues = [];
        foreach ($c as $v) if (!$v['passed']) $issues[] = $v['label'];
        return ['score' => $score, 'checks' => $c, 'issues' => $issues, 'label' => 'אבטחה'];
    }

    private function normalizeUrl(string $url): string { $url = trim($url); if (!preg_match('#^https?://#i', $url)) $url = 'https://' . $url; return $url; }
    private function parseHeaders(string $raw): array { $h = []; foreach (explode("\n", $raw) as $line) { $line = trim($line); if (!$line || str_starts_with($line, 'HTTP/')) continue; if (str_contains($line, ':')) { [$k, $v] = explode(':', $line, 2); $h[trim($k)] = trim($v); } } return $h; }
}
