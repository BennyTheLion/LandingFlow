<?php
namespace App\Services;

use App\Core\Database;

class MonitoringAlertService
{
    private function db(): \PDO { return Database::getInstance()->getConnection(); }

    public function down(array $site): void
    {
        $message = "האתר \"{$site['name']}\" ({$site['url']}) לא זמין.\n\nזוהה במעקב LandingFlow ב-" . date('d/m/Y H:i') . ".";
        $this->fire($site, 'down', $message);
    }

    public function up(array $site): void
    {
        $message = "האתר \"{$site['name']}\" ({$site['url']}) חזר לפעול.\n\nזוהה במעקב LandingFlow ב-" . date('d/m/Y H:i') . ".";
        $this->fire($site, 'up', $message);
    }

    /** Skips if an ssl_expiring alert already went out for this site in the last 7 days */
    public function sslExpiring(array $site): void
    {
        if ($this->recentlyAlerted((int)$site['id'], 'ssl_expiring', 7)) return;
        $expiry = $site['ssl_expires_at'] ?? '';
        $days = $expiry ? (int)floor((strtotime($expiry) - time()) / 86400) : null;
        $message = "תעודת ה-SSL של \"{$site['name']}\" ({$site['url']}) פגה בתוקף ב-{$expiry}" . ($days !== null ? " (בעוד {$days} ימים)" : '') . ".\n\nיש לחדש אותה בהקדם.";
        $this->fire($site, 'ssl_expiring', $message);
    }

    /** Skips if a slow_response alert already went out for this site in the last hour */
    public function slowResponse(array $site): void
    {
        if ($this->recentlyAlerted((int)$site['id'], 'slow_response', 0, 1)) return;
        $ms = $site['response_time_ms'] ?? '?';
        $message = "האתר \"{$site['name']}\" ({$site['url']}) מגיב לאט — {$ms}ms.\n\nמומלץ לבדוק ביצועים.";
        $this->fire($site, 'slow_response', $message);
    }

    private function fire(array $site, string $type, string $message): void
    {
        $sentEmail = false;
        if (!empty($site['alert_email'] ?? 1)) {
            $to = defined('ALERT_EMAIL') ? ALERT_EMAIL : (defined('SMTP_USER') ? SMTP_USER : '');
            if ($to) {
                $subject = $this->subjectFor($type, $site['name']);
                $sentEmail = Mailer::send($to, $subject, $message);
            }
        }

        $sentWhatsapp = !empty($site['alert_whatsapp'] ?? 0) ? $this->notifyWhatsapp($site, $message) : false;

        $this->db()->prepare(
            "INSERT INTO monitoring_alerts (website_id, type, message, sent_email, sent_whatsapp) VALUES (?,?,?,?,?)"
        )->execute([$site['id'], $type, $message, $sentEmail ? 1 : 0, $sentWhatsapp ? 1 : 0]);
    }

    /** No WhatsApp provider is wired up yet — placeholder seam for when one is added */
    private function notifyWhatsapp(array $site, string $message): bool
    {
        return false;
    }

    private function subjectFor(string $type, string $siteName): string
    {
        return match ($type) {
            'down' => "🔴 $siteName לא זמין",
            'up' => "🟢 $siteName חזר לפעול",
            'ssl_expiring' => "⚠️ תעודת SSL עומדת לפוג — $siteName",
            'slow_response' => "🐢 $siteName מגיב לאט",
            default => "התראת ניטור — $siteName",
        };
    }

    private function recentlyAlerted(int $websiteId, string $type, int $days, int $hours = 0): bool
    {
        $interval = $hours > 0 ? "{$hours} HOUR" : "{$days} DAY";
        $stmt = $this->db()->prepare(
            "SELECT 1 FROM monitoring_alerts WHERE website_id = ? AND type = ? AND created_at >= NOW() - INTERVAL {$interval} LIMIT 1"
        );
        $stmt->execute([$websiteId, $type]);
        return (bool)$stmt->fetchColumn();
    }
}
