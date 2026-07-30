<?php
use App\Services\AuthService;
use App\Repositories\UserRepository;
use App\Repositories\PasswordResetRepository;

class PasswordValidationTest extends TestCase
{
    private function invokeValidatePassword(string $password): ?string
    {
        $auth = new AuthService(new UserRepository(), new PasswordResetRepository());
        $ref = new ReflectionMethod(AuthService::class, 'validatePasswordStrength');
        $ref->setAccessible(true);
        return $ref->invoke($auth, $password);
    }

    public function runAll(): array
    {
        return $this->runTests([
            'testTooShort',
            'testExactly8NoComplexity',
            'testEightWithUppercaseOnly',
            'testEightWithDigitOnly',
            'testEightWithSpecialOnly',
            'testEightFullComplexity',
            'testTwelveCharsPassphrase',
            'testSixteenCharsPassphrase',
            'testTwentyCharsPassphrase',
            'testEightHebrewOnly',
            'testNullReturnsNull',
        ]);
    }

    public function testTooShort(): void
    {
        $err = $this->invokeValidatePassword('Abc1!');
        $this->assertNotNull($err);
        $this->assertContains('8', $err);
    }

    public function testExactly8NoComplexity(): void
    {
        // 8 chars but lowercase only - fails
        $err = $this->invokeValidatePassword('abcdefgh');
        $this->assertNotNull($err, '8 lowercase chars should fail (no uppercase)');
    }

    public function testEightWithUppercaseOnly(): void
    {
        // uppercase + lowercase but no digit/special - fails
        $err = $this->invokeValidatePassword('Abcdefgh');
        $this->assertNotNull($err, '8 chars with uppercase but no digit should fail');
    }

    public function testEightWithDigitOnly(): void
    {
        // lowercase + digit but no uppercase/special - fails
        $err = $this->invokeValidatePassword('abcdefg1');
        $this->assertNotNull($err, '8 chars with digit but no uppercase should fail');
    }

    public function testEightWithSpecialOnly(): void
    {
        // lowercase + special but no uppercase/digit - fails
        $err = $this->invokeValidatePassword('abcdefg!');
        $this->assertNotNull($err, '8 chars with special but no uppercase/digit should fail');
    }

    public function testEightFullComplexity(): void
    {
        // Uppercase + lowercase + digit + special = passes
        $err = $this->invokeValidatePassword('Abcdef1!');
        $this->assertNull($err, '8 chars with uppercase+digit+special should pass');
    }

    public function testTwelveCharsPassphrase(): void
    {
        // 12+ chars pass even without complexity
        $err = $this->invokeValidatePassword('abcdefghijkl');
        $this->assertNull($err, '12+ chars should pass regardless of complexity');
    }

    public function testSixteenCharsPassphrase(): void
    {
        $err = $this->invokeValidatePassword('thisismysixteencharacterpass');
        $this->assertNull($err, '16 chars should pass');
    }

    public function testTwentyCharsPassphrase(): void
    {
        $err = $this->invokeValidatePassword('12345678901234567890');
        $this->assertNull($err, '20 digits should pass (12+ rule)');
    }

    public function testEightHebrewOnly(): void
    {
        // Hebrew chars only, 8 chars - no uppercase English, no digit, no special
        $err = $this->invokeValidatePassword('?????????');
        $this->assertNotNull($err, '8 Hebrew chars should fail (no English uppercase/digit/special)');
    }

    public function testNullReturnsNull(): void
    {
        // 12 chars passphrase
        $err = $this->invokeValidatePassword('correcthorsebatterystaple');
        $this->assertNull($err, 'CorrectHorseBatteryStaple style should pass');
    }
}