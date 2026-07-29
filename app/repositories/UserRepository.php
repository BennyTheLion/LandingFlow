<?php
namespace App\Repositories;

use App\Core\Database;
use App\Models\User;

/**
 * UserRepository — data access layer for users table.
 * All SQL for users lives here. Controllers never touch PDO directly.
 */
class UserRepository implements UserRepositoryInterface
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Find user by email. Returns null if not found.
     */
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM users WHERE email = ? LIMIT 1"
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? User::fromRow($row) : null;
    }

    /**
     * Find user by ID. Returns null if not found.
     */
    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM users WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? User::fromRow($row) : null;
    }

    /**
     * Check if an email is already registered.
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) as c FROM users WHERE email = ?"
        );
        $stmt->execute([$email]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Create a new user. Returns the new user's ID.
     */
    public function create(string $name, string $email, ?string $phone, string $hashedPassword, int $roleId = 3): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (name, email, phone, password, role_id, status, created_at)
             VALUES (?, ?, ?, ?, ?, 'active', NOW())"
        );
        $stmt->execute([$name, $email, $phone, $hashedPassword, $roleId]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update user password by email.
     */
    public function updatePassword(string $email, string $hashedPassword): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET password = ? WHERE email = ?"
        );
        $stmt->execute([$hashedPassword, $email]);
    }

    /**
     * Record last login timestamp and IP.
     */
    public function updateLastLogin(int $userId, string $ip): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?"
        );
        $stmt->execute([$ip, $userId]);
    }

    /**
     * Mark user's email as verified.
     */
    public function verifyEmail(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET email_verified_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$userId]);
    }
}
