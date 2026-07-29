<?php
namespace App\Services;

use App\Core\Logger;
use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use App\Repositories\PasswordResetRepositoryInterface;
use App\Repositories\EmailVerificationRepositoryInterface;

/**
 * AuthService — orchestrates all authentication business logic.
 */
class AuthService
{
    private UserRepositoryInterface $userRepo;
    private PasswordResetRepositoryInterface $passwordResetRepo;
    private ?EmailVerificationRepositoryInterface $emailVerifyRepo;

    public function __construct(
        UserRepositoryInterface $userRepo,
        PasswordResetRepositoryInterface $passwordResetRepo,
        ?EmailVerificationRepositoryInterface $emailVerifyRepo = null
    ) {
        $this->userRepo = $userRepo;
        $this->passwordResetRepo = $passwordResetRepo;
        $this->emailVerifyRepo = $emailVerifyRepo;
    }

    // ================================================================
    //  REGISTRATION
    // ================================================================

    /**
     * @return array{success: bool, errors: string[], userId: ?int, verificationToken: ?string}
     */
    public function register(string $name, string $email, ?string $phone, string $password, string $confirm): array
    {
        Logger::info('AuthService: register attempt', ['email' => $email]);

        $errors = $this->validateRegistration($name, $email, $phone, $password, $confirm);
        if ($errors) {
            Logger::warning('AuthService: registration validation failed', ['errors' => $errors]);
            return ['success' => false, 'errors' => $errors, 'userId' => null, 'verificationToken' => null];
        }

        if ($this->userRepo->emailExists($email)) {
            Logger::warning('AuthService: duplicate email', ['email' => $email]);
            return ['success' => false, 'errors' => ['כתובת האימייל כבר רשומה במערכת.'], 'userId' => null, 'verificationToken' => null];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $userId = $this->userRepo->create($name, $email, $phone, $hash, 3);

        // Generate verification token if email verification repo is available
        $verificationToken = null;
        if ($this->emailVerifyRepo) {
            $verificationToken = $this->emailVerifyRepo->create($userId);
        }

        Logger::info('AuthService: user created', ['user_id' => $userId]);

        return ['success' => true, 'errors' => [], 'userId' => $userId, 'verificationToken' => $verificationToken];
    }

    // ================================================================
    //  LOGIN
    // ================================================================

    /**
     * @return array{success: bool, user: ?User, error: ?string}
     */
    public function login(string $email, string $password, string $ip): array
    {
        $user = $this->userRepo->findByEmail($email);

        if (!$user || $user->status !== 'active' || !$user->verifyPassword($password)) {
            return ['success' => false, 'user' => null, 'error' => 'אימייל או סיסמה שגויים.'];
        }

        $this->userRepo->updateLastLogin($user->id, $ip);

        Logger::info('AuthService: login successful', ['user_id' => $user->id]);

        return ['success' => true, 'user' => $user, 'error' => null];
    }

    // ================================================================
    //  FORGOT PASSWORD
    // ================================================================

    public function forgotPassword(string $email): void
    {
        $user = $this->userRepo->findByEmail($email);
        if ($user) {
            $this->passwordResetRepo->create($email);
            Logger::info('AuthService: reset token created', ['email' => $email]);
        }
    }

    // ================================================================
    //  RESET PASSWORD
    // ================================================================

    /**
     * @return array{success: bool, error: ?string}
     */
    public function resetPassword(string $token, string $password, string $confirm): array
    {
        if ($password !== $confirm) {
            return ['success' => false, 'error' => 'הסיסמאות אינן תואמות.'];
        }

        $pwError = $this->validatePasswordStrength($password);
        if ($pwError) {
            return ['success' => false, 'error' => $pwError];
        }

        $email = $this->passwordResetRepo->findValidToken($token);
        if (!$email) {
            return ['success' => false, 'error' => 'קישור האיפוס אינו תקף או פג תוקף.'];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->userRepo->updatePassword($email, $hash);
        $this->passwordResetRepo->deleteByEmail($email);

        Logger::info('AuthService: password reset', ['email' => $email]);

        return ['success' => true, 'error' => null];
    }

    // ================================================================
    //  EMAIL VERIFICATION
    // ================================================================

    /**
     * Verify a user's email using a verification token.
     *
     * @return array{success: bool, error: ?string}
     */
    public function verifyEmail(string $token): array
    {
        if (!$this->emailVerifyRepo) {
            return ['success' => false, 'error' => 'Email verification is not configured.'];
        }

        $userId = $this->emailVerifyRepo->findValidToken($token);
        if (!$userId) {
            return ['success' => false, 'error' => 'קישור האימות אינו תקף או פג תוקף.'];
        }

        $this->userRepo->verifyEmail($userId);
        $this->emailVerifyRepo->markUsed($token);

        Logger::info('AuthService: email verified', ['user_id' => $userId]);

        return ['success' => true, 'error' => null];
    }

    // ================================================================
    //  VALIDATION HELPERS
    // ================================================================

    public function validateRegistration(string $name, string $email, ?string $phone, string $password, string $confirm): array
    {
        $errors = [];

        if (empty($name)) {
            $errors[] = 'שם מלא הוא שדה חובה.';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'אימייל לא תקין.';
        }
        if (empty($phone)) {
            $errors[] = 'טלפון הוא שדה חובה.';
        }

        $pwError = $this->validatePasswordStrength($password);
        if ($pwError) {
            $errors[] = $pwError;
        }
        if ($password !== $confirm) {
            $errors[] = 'הסיסמאות אינן תואמות.';
        }

        return $errors;
    }

    public function validatePasswordStrength(string $password): ?string
    {
        if (mb_strlen($password) < 8) {
            return 'הסיסמה חייבת להכיל לפחות 8 תווים.';
        }
        if (mb_strlen($password) >= 12) {
            return null;
        }
        if (!preg_match('/[A-Z]/u', $password)) {
            return 'הסיסמה חייבת להכיל לפחות אות גדולה אחת באנגלית (A-Z).';
        }
        if (!preg_match('/[0-9]/', $password)) {
            return 'הסיסמה חייבת להכיל לפחות ספרה אחת (0-9).';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return 'הסיסמה חייבת להכיל לפחות תו מיוחד אחד (!@#$%^&* וכו\').';
        }
        return null;
    }
}
