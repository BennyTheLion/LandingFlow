<?php
namespace App\Repositories;

use App\Core\Database;

/**
 * PasswordResetRepository — data access layer for password_resets table.
 */
class PasswordResetRepository implements PasswordResetRepositoryInterface
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Create a password reset token. Returns the token string.
     */
    public function create(string $email): string
    {
        $token = bin2hex(random_bytes(32));

        $stmt = $this->pdo->prepare(
            "INSERT INTO password_resets (email, token, created_at, expires_at)
             VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 HOUR))"
        );
        $stmt->execute([$email, $token]);

        return $token;
    }

    /**
     * Find a valid (non-expired) reset token.
     * Returns the email associated with the token, or null if invalid/expired.
     */
    public function findValidToken(string $token): ?string
    {
        $stmt = $this->pdo->prepare(
            "SELECT email FROM password_resets
             WHERE token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
               AND used = 0"
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? $row['email'] : null;
    }

    /**
     * Delete all reset tokens for an email (cleanup after successful reset).
     */
    public function deleteByEmail(string $email): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM password_resets WHERE email = ?"
        );
        $stmt->execute([$email]);
    }

    /**
     * Mark a token as used.
     */
    public function markUsed(string $token): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE password_resets SET used = 1 WHERE token = ?"
        );
        $stmt->execute([$token]);
    }
}
