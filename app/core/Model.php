<?php
namespace App\Core;

/**
 * Base Model with common CRUD operations
 */
abstract class Model
{
    protected Database $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(string|int $id): ?array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id LIMIT 1";
        return $this->db->fetch($sql, ['id' => $id]);
    }

    public function findAll(array $conditions = [], string $orderBy = '', int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT * FROM `{$this->table}`";
        $params = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "`{$key}` = :{$key}";
                $params[$key] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
            if ($offset > 0) {
                $sql .= " OFFSET {$offset}";
            }
        }

        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data): string
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":{$col}", $columns);
        
        $sql = sprintf(
            "INSERT INTO `%s` (`%s`) VALUES (%s)",
            $this->table,
            implode('`, `', $columns),
            implode(', ', $placeholders)
        );

        return $this->db->insert($sql, $data);
    }

    public function update(string|int $id, array $data): int
    {
        $sets = [];
        $params = [];

        foreach ($data as $key => $value) {
            $sets[] = "`{$key}` = :{$key}";
            $params[$key] = $value;
        }

        $params['id'] = $id;

        $sql = sprintf(
            "UPDATE `%s` SET %s WHERE `%s` = :id",
            $this->table,
            implode(', ', $sets),
            $this->primaryKey
        );

        return $this->db->execute($sql, $params);
    }

    public function delete(string|int $id): int
    {
        $sql = "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id";
        return $this->db->execute($sql, ['id' => $id]);
    }

    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}`";
        $params = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "`{$key}` = :{$key}";
                $params[$key] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        return (int) $this->db->fetchColumn($sql, $params);
    }

    public function exists(array $conditions): bool
    {
        return $this->count($conditions) > 0;
    }

    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->db->commit();
    }

    public function rollback(): bool
    {
        return $this->db->rollback();
    }
}
