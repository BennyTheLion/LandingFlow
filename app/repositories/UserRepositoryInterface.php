<?php
namespace App\Repositories;

use App\Models\User;

/**
 * UserRepositoryInterface — contract for user data access.
 * Enables dependency injection, mock testing, and storage swapping.
 */
interface UserRepositoryInterface
{
    /**
     * Find a user by email address. Returns null if not found.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find a user by ID. Returns null if not found.
     */
    public function findById(int $id): ?User;

    /**
     * Check whether an email is already registered.
     */
    public function emailExists(string $email): bool;

    /**
     * Create a new user. Returns the new user's ID.
     */
    public function create(string $name, string $email, ?string $phone, string $hashedPassword, int $roleId = 3): int;

    /**
     * Update a user's password by email.
     */
    public function updatePassword(string $email, string $hashedPassword): void;

    /**
     * Record the last login timestamp and IP address.
     */
    public function updateLastLogin(int $userId, string $ip): void;

    /**
     * Mark a user's email as verified.
     */
    public function verifyEmail(int $userId): void;
}
