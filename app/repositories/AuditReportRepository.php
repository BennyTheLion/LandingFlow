<?php
namespace App\Repositories;

use App\Core\Database;

class AuditReportRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO audit_reports (lead_id, url, overall_score, seo_score, performance_score, security_score, accessibility_score, legal_score, total_checks, passed_checks, failed_checks, full_report, recommendations, status, ip_address, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?, ?, NOW())"
        );
        $stmt->execute([
            $data['lead_id'] ?? null,
            $data['url'],
            $data['overall_score'] ?? 0,
            $data['seo_score'] ?? 0,
            $data['performance_score'] ?? 0,
            $data['security_score'] ?? 0,
            $data['accessibility_score'] ?? 0,
            $data['legal_score'] ?? 0,
            $data['total_checks'] ?? 0,
            $data['passed_checks'] ?? 0,
            $data['failed_checks'] ?? 0,
            json_encode($data['full_report'] ?? []),
            json_encode($data['recommendations'] ?? []),
            $data['ip_address'] ?? '127.0.0.1',
            $data['user_agent'] ?? 'CLI',
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function findByLeadId(int $leadId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM audit_reports WHERE lead_id = ? ORDER BY created_at DESC");
        $stmt->execute([$leadId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findByUrl(string $url): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM audit_reports WHERE url = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$url]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: [];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM audit_reports WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
