<?php
namespace App\Repositories;

use App\Core\Database;

/**
 * EmailVerificationRepository — data access for email_verification_tokens.
 */
class EmailVerificationRepository implements EmailVerificationRepositoryInterface
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function create(int $userId): string
    {
        $token = bin2hex(random_bytes(32));

        $stmt = $this->pdo->prepare(
            "INSERT INTO email_verification_tokens (user_id, token, created_at, expires_at)
             VALUES (?, ?, NOW(), DATE_ADD(NOW(), '+24 HOUR'))"
        );
        $stmt->execute([$userId, $token]);

        return $token;
    }

    public function findValidToken(string $token): ?int
    {
        $stmt = $this->pdo->prepare(
            "SELECT user_id FROM email_verification_tokens
             WHERE token = ? AND created_at > DATE_ADD(NOW(), '-24 HOUR') AND used = 0"
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? (int) $row['user_id'] : null;
    }

    public function markUsed(string $token): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE email_verification_tokens SET used = 1 WHERE token = ?"
        );
        $stmt->execute([$token]);
    }

    public function deleteByUserId(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM email_verification_tokens WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
    }
}
