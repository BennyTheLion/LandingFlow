<?php
namespace App\Core;

/**
 * Database Connection - PDO MySQL/SQLite Wrapper
 */
class Database
{
    private static ?Database $instance = null;
    private \PDO $pdo;

    private function __construct()
    {
        if (defined('DB_DSN')) {
            $dsn = DB_DSN;
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );
        }

        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
            \PDO::ATTR_PERSISTENT         => true,
        ];

        if (!defined('DB_DSN')) {
            $options[\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES " . DB_CHARSET . " COLLATE " . DB_COLLATION;
        }

        try {
            $this->pdo = new \PDO($dsn, DB_USER, DB_PASS, $options);
            if (defined('DB_DSN') && str_starts_with(DB_DSN, 'sqlite:')) {
                $this->pdo->exec('PRAGMA journal_mode=WAL');
                $this->pdo->exec('PRAGMA foreign_keys=ON');
                $this->pdo->sqliteCreateFunction('NOW', fn() => gmdate('Y-m-d H:i:s'), 0);
                $this->pdo->sqliteCreateFunction('DATE_ADD', fn($d, $i) => gmdate('Y-m-d H:i:s', strtotime($i, strtotime($d))), 2);
            }
        } catch (\PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    public function getConnection(): \PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    public function fetchColumn(string $sql, array $params = [], int $column = 0): mixed
    {
        $result = $this->query($sql, $params)->fetchColumn($column);
        return $result !== false ? $result : null;
    }

    public function insert(string $sql, array $params = []): string
    {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }

    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    public function quote(string $value): string
    {
        return $this->pdo->quote($value);
    }

    private function __clone() {}
}
