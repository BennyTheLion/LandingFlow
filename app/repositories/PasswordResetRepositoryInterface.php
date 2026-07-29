<?php
namespace App\Repositories;

/**
 * PasswordResetRepositoryInterface — contract for password-reset token data access.
 */
interface PasswordResetRepositoryInterface
{
    /**
     * Create a reset token for an email. Returns the generated token string.
     */
    public function create(string $email): string;

    /**
     * Find a valid (non-expired, unused) token. Returns the associated email, or null.
     */
    public function findValidToken(string $token): ?string;

    /**
     * Delete all reset tokens for a given email.
     */
    public function deleteByEmail(string $email): void;

    /**
     * Mark a specific token as used.
     */
    public function markUsed(string $token): void;
}
