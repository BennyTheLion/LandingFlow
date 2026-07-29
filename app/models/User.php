<?php
namespace App\Models;

/**
 * User entity — represents a row in the users table.
 */
class User
{
    public ?int $id = null;
    public string $name;
    public string $email;
    public ?string $phone = null;
    public string $password;
    public ?int $roleId = null;
    public ?string $avatar = null;
    public string $status = 'active';
    public ?string $lastLoginAt = null;
    public ?string $lastLoginIp = null;
    public ?string $emailVerifiedAt = null;
    public ?string $rememberToken = null;
    public string $createdAt;
    public string $updatedAt;

    /**
     * Create from database row.
     */
    public static function fromRow(array $row): self
    {
        $user = new self();
        $user->id             = isset($row['id']) ? (int) $row['id'] : null;
        $user->name           = $row['name'] ?? '';
        $user->email          = $row['email'] ?? '';
        $user->phone          = $row['phone'] ?? null;
        $user->password       = $row['password'] ?? '';
        $user->roleId         = isset($row['role_id']) ? (int) $row['role_id'] : null;
        $user->avatar         = $row['avatar'] ?? null;
        $user->status         = $row['status'] ?? 'active';
        $user->lastLoginAt    = $row['last_login_at'] ?? null;
        $user->lastLoginIp    = $row['last_login_ip'] ?? null;
        $user->emailVerifiedAt = $row['email_verified_at'] ?? null;
        $user->rememberToken  = $row['remember_token'] ?? null;
        $user->createdAt      = $row['created_at'] ?? '';
        $user->updatedAt      = $row['updated_at'] ?? '';
        return $user;
    }

    /**
     * Return session-safe array (no password).
     */
    public function toSession(): array
    {
        return [
            'id'      => $this->id,
            'name'    => $this->name,
            'email'   => $this->email,
            'role_id' => $this->roleId,
        ];
    }

    /**
     * Verify a plaintext password against the stored hash.
     */
    public function verifyPassword(string $plaintext): bool
    {
        return password_verify($plaintext, $this->password);
    }
}
