<?php
namespace App\Models;

/**
 * EmailVerificationToken — represents a row in email_verification_tokens.
 */
class EmailVerificationToken
{
    public int $id;
    public int $userId;
    public string $token;
    public string $createdAt;
    public string $expiresAt;
    public bool $used = false;

    public static function fromRow(array $row): self
    {
        $t = new self();
        $t->id        = (int) $row['id'];
        $t->userId    = (int) $row['user_id'];
        $t->token     = $row['token'];
        $t->createdAt = $row['created_at'];
        $t->expiresAt = $row['expires_at'];
        $t->used      = (bool) ($row['used'] ?? false);
        return $t;
    }
}
