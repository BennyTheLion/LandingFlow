<?php
namespace App\Services;

use App\Core\Database;

class MonitoringReportService
{
    private function db(): \PDO { return Database::getInstance()->getConnection(); }

    public function sendWeeklyDigest(): bool
    {
        $sites = $this->db()->query(
            "SELECT * FROM monitoring_websites WHERE status != 'paused' ORDER BY name ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        if (!$sites) return false;

        $to = defined('ALERT_EMAIL') ? ALERT_EMAIL : (defined('SMTP_USER') ? SMTP_USER : '');
        if (!$to) return false;

        $body = "דוח ניטור שבועי — LandingFlow\n";
        $body .= date('d/m/Y') . "\n";
        $body .= str_repeat('=', 50) . "\n\n";

        foreach ($sites as $site) {
            $body .= $this->siteSummary($site);
        }

        return Mailer::send($to, 'דוח ניטור שבועי — LandingFlow', $body);
    }

    private function siteSummary(array $site): string
    {
        $logStats = $this->db()->prepare(
            "SELECT COUNT(*) AS total, SUM(status='up') AS ups, AVG(response_time_ms) AS avg_ms
             FROM monitoring_logs WHERE website_id = ? AND checked_at >= NOW() - INTERVAL 7 DAY"
        );
        $logStats->execute([$site['id']]);
        $stats = $logStats->fetch(\PDO::FETCH_ASSOC) ?: [];
        $total = (int)($stats['total'] ?? 0);
        $uptime = $total > 0 ? round(($stats['ups'] / $total) * 100, 2) : null;
        $avgMs = $stats['avg_ms'] !== null ? round($stats['avg_ms']) : null;

        $alertStmt = $this->db()->prepare(
            "SELECT COUNT(*) FROM monitoring_alerts WHERE website_id = ? AND created_at >= NOW() - INTERVAL 7 DAY"
        );
        $alertStmt->execute([$site['id']]);
        $alertCount = (int)$alertStmt->fetchColumn();

        $summary = "{$site['name']} ({$site['url']})\n";
        $summary .= "  סטטוס נוכחי: {$site['status']}\n";
        $summary .= '  זמינות (7 ימים): ' . ($uptime !== null ? "{$uptime}%" : 'אין נתונים') . "\n";
        $summary .= '  זמן תגובה ממוצע: ' . ($avgMs !== null ? "{$avgMs}ms" : 'אין נתונים') . "\n";
        $summary .= "  SSL: {$site['ssl_status']}" . ($site['ssl_expires_at'] ? " (עד {$site['ssl_expires_at']})" : '') . "\n";
        $summary .= "  התראות השבוע: {$alertCount}\n\n";
        return $summary;
    }
}
