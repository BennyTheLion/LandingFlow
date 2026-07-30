<?php
use App\Controllers\AuthController;
use App\Core\Session;
use App\Core\Database;

class EmailVerificationTest extends TestCase
{
    public function setUp(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        resetDatabase();
        Session::set(CSRF_TOKEN_NAME, bin2hex(random_bytes(32)));
    }

    private function db(): PDO { return Database::getInstance()->getConnection(); }

    private function registerUser(string $email = 'verify@test.com'): int
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['name'=>'VerifyUser','email'=>$email,'phone'=>'0501234567','password'=>'Abcdef1!','password_confirm'=>'Abcdef1!',CSRF_TOKEN_NAME=>Session::get(CSRF_TOKEN_NAME)];
        $c = new AuthController();
        try { $c->register(); } catch (\Throwable $e) {}
        $u = $this->db()->query("SELECT * FROM users WHERE email='$email'")->fetch();
        return (int) $u['id'];
    }

    private function getLatestToken(int $userId): ?string
    {
        $t = $this->db()->query("SELECT token FROM email_verification_tokens WHERE user_id=$userId ORDER BY id DESC LIMIT 1")->fetch();
        return $t ? $t['token'] : null;
    }

    public function runAll(): array
    {
        return $this->runTests([
            'testTokenCreatedOnRegistration',
            'testVerifyEmailWithValidToken',
            'testVerifyEmailWithInvalidToken',
            'testVerifyEmailWithExpiredToken',
            'testEmailNotVerifiedOnRegistration',
        ]);
    }

    public function testTokenCreatedOnRegistration(): void
    {
        $uid = $this->registerUser('token@test.com');
        $token = $this->getLatestToken($uid);
        $this->assertNotNull($token, 'Verification token should be created');
        $this->assertEquals(64, strlen($token), 'Token should be 64 hex chars');
    }

    public function testVerifyEmailWithValidToken(): void
    {
        $uid = $this->registerUser('valid@test.com');
        $token = $this->getLatestToken($uid);
        $c = new AuthController();
        try { $c->verifyEmail($token); } catch (\Throwable $e) {}
        $u = $this->db()->query("SELECT email_verified_at FROM users WHERE email='valid@test.com'")->fetch();
        $this->assertNotNull($u['email_verified_at'], 'email_verified_at should be set');
    }

    public function testVerifyEmailWithInvalidToken(): void
    {
        $this->registerUser('invalid@test.com');
        $c = new AuthController();
        try { $c->verifyEmail('bogus-token-12345'); } catch (\Throwable $e) {}
        $u = $this->db()->query("SELECT email_verified_at FROM users WHERE email='invalid@test.com'")->fetch();
        $this->assertNull($u['email_verified_at'], 'Invalid token should not verify');
    }

    public function testEmailNotVerifiedOnRegistration(): void
    {
        $this->registerUser('unverified@test.com');
        $u = $this->db()->query("SELECT email_verified_at FROM users WHERE email='unverified@test.com'")->fetch();
        $this->assertNull($u['email_verified_at'], 'New user email not verified');
    }

    public function testVerifyEmailWithExpiredToken(): void
    {
        $uid = $this->registerUser('expired@test.com');
        $token = $this->getLatestToken($uid);
        // Expiry is checked against the stored expires_at column, so push it into the past.
        $expiredTime = gmdate('Y-m-d H:i:s', time() - 25 * 3600);
        $this->db()->exec("UPDATE email_verification_tokens SET expires_at='$expiredTime' WHERE token='$token'");
        $c = new AuthController();
        try { $c->verifyEmail($token); } catch (\Throwable $e) {}
        $u = $this->db()->query("SELECT email_verified_at FROM users WHERE email='expired@test.com'")->fetch();
        $this->assertNull($u['email_verified_at'], 'Expired token should not verify email');
    }
}
