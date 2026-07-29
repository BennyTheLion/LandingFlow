<?php
namespace App\Repositories;

/**
 * EmailVerificationRepositoryInterface — contract for email verification token data access.
 */
interface EmailVerificationRepositoryInterface
{
    /**
     * Create a verification token for a user. Returns the token string.
     */
    public function create(int $userId): string;

    /**
     * Find a valid (non-expired, unused) token. Returns null if invalid.
     */
    public function findValidToken(string $token): ?int;

    /**
     * Mark a token as used.
     */
    public function markUsed(string $token): void;

    /**
     * Delete all tokens for a user.
     */
    public function deleteByUserId(int $userId): void;
}
