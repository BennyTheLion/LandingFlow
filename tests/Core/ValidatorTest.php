<?php
use App\Core\Validator;

class ValidatorTest extends TestCase
{
    public function runAll(): array
    {
        return $this->runTests([
            'testRequiredPasses',
            'testRequiredFails',
            'testEmailValid',
            'testEmailInvalid',
            'testPhoneValid',
            'testPhoneInvalid',
            'testUrlValid',
            'testUrlInvalid',
            'testMinPasses',
            'testMinFails',
            'testMaxPasses',
            'testMaxFails',
            'testNumericValid',
            'testNumericInvalid',
            'testInPasses',
            'testInFails',
            'testCustomRule',
            'testMultipleRules',
            'testGetErrors',
            'testPassesAndFails',
        ]);
    }

    public function testRequiredPasses(): void
    {
        $v = new Validator(['name' => 'John', 'email' => 'test@test.com']);
        $v->required('name', 'email');
        $this->assertTrue($v->passes());
    }

    public function testRequiredFails(): void
    {
        $v = new Validator(['name' => '', 'email' => 'test@test.com']);
        $v->required('name', 'email');
        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('name', $v->getErrors());
    }

    public function testEmailValid(): void
    {
        $v = new Validator(['email' => 'user@domain.co.il']);
        $v->email('email');
        $this->assertTrue($v->passes());
    }

    public function testEmailInvalid(): void
    {
        $v = new Validator(['email' => 'not-an-email']);
        $v->email('email');
        $this->assertFalse($v->passes());
    }

    public function testPhoneValid(): void
    {
        $v = new Validator(['phone' => '052-8529448']);
        $v->phone('phone');
        $this->assertTrue($v->passes());
    }

    public function testPhoneInvalid(): void
    {
        $v = new Validator(['phone' => 'abc']);
        $v->phone('phone');
        $this->assertFalse($v->passes());
    }

    public function testUrlValid(): void
    {
        $v = new Validator(['url' => 'https://example.com']);
        $v->url('url');
        $this->assertTrue($v->passes());
    }

    public function testUrlInvalid(): void
    {
        $v = new Validator(['url' => 'not-a-url']);
        $v->url('url');
        $this->assertFalse($v->passes());
    }

    public function testMinPasses(): void
    {
        $v = new Validator(['password' => '12345678']);
        $v->min('password', 8);
        $this->assertTrue($v->passes());
    }

    public function testMinFails(): void
    {
        $v = new Validator(['password' => '1234']);
        $v->min('password', 8);
        $this->assertFalse($v->passes());
    }

    public function testMaxPasses(): void
    {
        $v = new Validator(['name' => 'Short']);
        $v->max('name', 100);
        $this->assertTrue($v->passes());
    }

    public function testMaxFails(): void
    {
        $v = new Validator(['name' => str_repeat('x', 101)]);
        $v->max('name', 100);
        $this->assertFalse($v->passes());
    }

    public function testNumericValid(): void
    {
        $v = new Validator(['age' => '25']);
        $v->numeric('age');
        $this->assertTrue($v->passes());
    }

    public function testNumericInvalid(): void
    {
        $v = new Validator(['age' => 'twenty']);
        $v->numeric('age');
        $this->assertFalse($v->passes());
    }

    public function testInPasses(): void
    {
        $v = new Validator(['status' => 'active']);
        $v->in('status', ['active', 'inactive']);
        $this->assertTrue($v->passes());
    }

    public function testInFails(): void
    {
        $v = new Validator(['status' => 'deleted']);
        $v->in('status', ['active', 'inactive']);
        $this->assertFalse($v->passes());
    }

    public function testCustomRule(): void
    {
        $v = new Validator(['age' => 17]);
        $v->custom('age', fn($val) => $val >= 18, 'Must be 18+');
        $this->assertFalse($v->passes());
        $this->assertEquals('Must be 18+', $v->getFirstError('age'));
    }

    public function testMultipleRules(): void
    {
        $v = new Validator(['email' => 'bad', 'password' => '12', 'name' => '']);
        $v->required('email', 'name');
        $v->email('email');
        $v->min('password', 6);
        $this->assertFalse($v->passes());
        $errors = $v->getErrors();
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('password', $errors);
    }

    public function testGetErrors(): void
    {
        $v = new Validator(['name' => '']);
        $v->required('name');
        $errors = $v->getErrors();
        $this->assertArrayHasKey('name', $errors);
        $this->assertNotNull($v->getFirstError('name'));
        $this->assertNull($v->getFirstError('nonexistent'));
    }

    public function testPassesAndFails(): void
    {
        $v1 = new Validator(['name' => 'OK']);
        $v1->required('name');
        $this->assertTrue($v1->passes());
        $this->assertFalse($v1->fails());

        $v2 = new Validator(['name' => '']);
        $v2->required('name');
        $this->assertFalse($v2->passes());
        $this->assertTrue($v2->fails());
    }
}