<?php
namespace App\Repositories;

use App\Core\Database;
use App\LeadEngine\AuditResult;
use App\LeadEngine\PoliteFetcher;

/**
 * ProspectRepository — prospects + their audit history (spec §4).
 *
 * Rows are returned as plain arrays, matching the monitoring/CRM screens.
 */
class ProspectRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    // ---------------------------------------------------------------- lookups

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM prospects WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function findByDomain(string $domain): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM prospects WHERE domain = ?");
        $stmt->execute([PoliteFetcher::domainKey($domain)]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Deduplication window per spec §5: a domain touched within the last N days
     * is skipped rather than re-sourced.
     */
    public function seenWithinDays(string $domain, int $days = 90): ?array
    {
        // Cutoff computed in PHP rather than DATE_SUB/INTERVAL so the same SQL
        // runs on MySQL and on the SQLite test database.
        $cutoff = gmdate('Y-m-d H:i:s', time() - $days * 86400);
        $stmt = $this->pdo->prepare(
            "SELECT * FROM prospects WHERE domain = ? AND created_at >= ?"
        );
        $stmt->execute([PoliteFetcher::domainKey($domain), $cutoff]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * @param array{status?:string,niche?:string,source?:string,min_score?:int,search?:string} $filters
     */
    public function search(array $filters = [], int $limit = 200, int $offset = 0): array
    {
        $sql = "SELECT * FROM prospects";
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['niche'])) {
            $where[] = 'niche = ?';
            $params[] = $filters['niche'];
        }
        if (!empty($filters['source'])) {
            $where[] = 'source = ?';
            $params[] = $filters['source'];
        }
        if (isset($filters['min_score']) && $filters['min_score'] !== '') {
            $where[] = 'hot_score >= ?';
            $params[] = (int) $filters['min_score'];
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $where[] = '(business_name LIKE ? OR domain LIKE ? OR contact_name LIKE ? OR email LIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY hot_score DESC, created_at DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Prospects waiting for a given pipeline stage, worst-scoring last */
    public function byStatus(string $status, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM prospects WHERE status = ?
             ORDER BY hot_score DESC, created_at ASC LIMIT " . (int) $limit
        );
        $stmt->execute([$status]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Stage 4 queue. Prospects with a contact_name sort first — a named opener
     * is worth far more than "שלום רב" (§7 quality rule).
     */
    public function readyToDraft(int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM prospects
             WHERE status = 'enriched' AND (email IS NOT NULL AND email <> '')
             ORDER BY (contact_name IS NULL OR contact_name = '') ASC, hot_score DESC
             LIMIT " . (int) $limit
        );
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countByStatus(): array
    {
        $rows = $this->pdo->query("SELECT status, COUNT(*) AS n FROM prospects GROUP BY status")
            ->fetchAll(\PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $row) {
            $out[$row['status']] = (int) $row['n'];
        }
        return $out;
    }

    public function distinctNiches(): array
    {
        return array_column(
            $this->pdo->query("SELECT DISTINCT niche FROM prospects WHERE niche IS NOT NULL AND niche <> '' ORDER BY niche")
                ->fetchAll(\PDO::FETCH_ASSOC),
            'niche'
        );
    }

    // ---------------------------------------------------------------- writes

    /**
     * @return int New prospect id, or 0 when the domain already exists
     */
    public function create(array $data): int
    {
        $data['domain'] = PoliteFetcher::domainKey($data['domain'] ?? ($data['url'] ?? ''));
        $data['url'] = PoliteFetcher::normalizeUrl($data['url'] ?? '');
        if ($data['domain'] === '' || $data['url'] === '') {
            return 0;
        }

        $cols = implode('`, `', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        try {
            $stmt = $this->pdo->prepare("INSERT INTO prospects (`$cols`) VALUES ($placeholders)");
            $stmt->execute(array_values($data));
            return (int) $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            // 23000 = unique constraint on domain; a duplicate is expected, not exceptional
            if ($e->getCode() === '23000') {
                return 0;
            }
            throw $e;
        }
    }

    public function update(int $id, array $data): void
    {
        if ($data === []) {
            return;
        }
        $set = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
        $vals = array_values($data);
        $vals[] = $id;
        $this->pdo->prepare("UPDATE prospects SET $set, updated_at = NOW() WHERE id = ?")->execute($vals);
    }

    public function setStatus(int $id, string $status): void
    {
        $this->pdo->prepare("UPDATE prospects SET status = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$status, $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM prospects WHERE id = ?")->execute([$id]);
    }

    // ---------------------------------------------------------------- audits

    /** Persists an audit row and denormalizes the score onto the prospect */
    public function saveAudit(int $prospectId, AuditResult $audit): int
    {
        $row = $audit->toRow($prospectId);
        $cols = implode('`, `', array_keys($row));
        $placeholders = implode(', ', array_fill(0, count($row), '?'));
        $this->pdo->prepare("INSERT INTO prospect_audits (`$cols`) VALUES ($placeholders)")
            ->execute(array_values($row));
        $auditId = (int) $this->pdo->lastInsertId();

        $this->pdo->prepare(
            "UPDATE prospects SET hot_score = ?, primary_issue = ?, last_audit_at = NOW(), updated_at = NOW()
             WHERE id = ?"
        )->execute([$audit->hotScore, $audit->primaryIssue, $prospectId]);

        return $auditId;
    }

    public function latestAudit(int $prospectId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM prospect_audits WHERE prospect_id = ? ORDER BY run_at DESC, id DESC LIMIT 1"
        );
        $stmt->execute([$prospectId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /** Full history — a dropping score is an excellent reason to re-contact (§10) */
    public function auditHistory(int $prospectId, int $limit = 30): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM prospect_audits WHERE prospect_id = ? ORDER BY run_at DESC LIMIT " . (int) $limit
        );
        $stmt->execute([$prospectId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findAudit(int $auditId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM prospect_audits WHERE id = ?");
        $stmt->execute([$auditId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /** Prospects due for a periodic re-audit */
    public function dueForReaudit(int $days = 60, int $limit = 25): array
    {
        $cutoff = gmdate('Y-m-d H:i:s', time() - $days * 86400);
        $stmt = $this->pdo->prepare(
            "SELECT * FROM prospects
             WHERE status IN ('closed', 'sent')
               AND last_audit_at IS NOT NULL
               AND last_audit_at <= ?
             ORDER BY last_audit_at ASC LIMIT " . (int) $limit
        );
        $stmt->execute([$cutoff]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Retention policy §11.6: closed prospects older than N months are purged */
    public function purgeClosedOlderThan(int $months): int
    {
        $cutoff = gmdate('Y-m-d H:i:s', strtotime("-{$months} months") ?: time());
        $stmt = $this->pdo->prepare(
            "DELETE FROM prospects WHERE status = 'closed' AND updated_at <= ?"
        );
        $stmt->execute([$cutoff]);
        return $stmt->rowCount();
    }
}
