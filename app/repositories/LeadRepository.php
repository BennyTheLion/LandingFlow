<?php
namespace App\Repositories;

use App\Core\Database;
use App\Models\Lead;

class LeadRepository implements LeadRepositoryInterface
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(?string $status = null, ?string $search = null): array
    {
        $sql = "SELECT * FROM leads";
        $params = [];
        $conditions = [];

        if ($status) {
            $conditions[] = "status = ?";
            $params[] = $status;
        }
        if ($search) {
            $s = "%$search%";
            $conditions[] = "(name LIKE ? OR company LIKE ? OR email LIKE ?)";
            $params = array_merge($params, [$s, $s, $s]);
        }
        if ($conditions) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return array_map(fn($row) => Lead::fromRow($row), $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function findById(int $id): ?Lead
    {
        $stmt = $this->pdo->prepare("SELECT * FROM leads WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? Lead::fromRow($row) : null;
    }

    public function create(array $data): int
    {
        $cols = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt = $this->pdo->prepare("INSERT INTO leads ($cols) VALUES ($placeholders)");
        $stmt->execute(array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $set = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
        $vals = array_values($data);
        $vals[] = $id;
        $stmt = $this->pdo->prepare("UPDATE leads SET $set WHERE id = ?");
        $stmt->execute($vals);
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare("UPDATE leads SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM leads WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function addNote(int $leadId, ?int $userId, string $content, string $type = 'note'): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO lead_notes (lead_id, user_id, content, type, created_at) VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$leadId, $userId, $content, $type]);
    }

    public function getNotes(int $leadId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT n.*, u.name as author FROM lead_notes n LEFT JOIN users u ON n.user_id = u.id WHERE n.lead_id = ? ORDER BY n.created_at DESC"
        );
        $stmt->execute([$leadId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function exportAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM leads ORDER BY created_at DESC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
