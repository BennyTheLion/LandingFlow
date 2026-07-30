<?php
/**
 * Base TestCase with assertion helpers.
 */
abstract class TestCase
{
    protected int $assertions = 0;
    protected int $passed = 0;
    protected int $failed = 0;
    protected array $failures = [];

    protected function assertTrue(bool $condition, string $message = ''): void
    {
        $this->assertions++;
        if ($condition) {
            $this->passed++;
        } else {
            $this->failed++;
            $this->failures[] = $message ?: 'Expected true, got false';
        }
    }

    protected function assertFalse(bool $condition, string $message = ''): void
    {
        $this->assertTrue(!$condition, $message ?: 'Expected false, got true');
    }

    protected function assertEquals($expected, $actual, string $message = ''): void
    {
        $this->assertions++;
        if ($expected === $actual) {
            $this->passed++;
        } else {
            $this->failed++;
            $exp = is_scalar($expected) ? var_export($expected, true) : gettype($expected);
            $act = is_scalar($actual) ? var_export($actual, true) : gettype($actual);
            $this->failures[] = ($message ? "$message: " : '') . "Expected $exp, got $act";
        }
    }

    protected function assertNotEquals($expected, $actual, string $message = ''): void
    {
        $this->assertTrue($expected !== $actual, $message ?: 'Values should not be equal');
    }

    protected function assertNull($value, string $message = ''): void
    {
        $this->assertTrue($value === null, $message ?: 'Expected null');
    }

    protected function assertNotNull($value, string $message = ''): void
    {
        $this->assertTrue($value !== null, $message ?: 'Expected not null');
    }

    protected function assertContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            $message ?: "Expected '$haystack' to contain '$needle'"
        );
    }

    protected function assertArrayHasKey(string $key, array $array, string $message = ''): void
    {
        $this->assertTrue(
            array_key_exists($key, $array),
            $message ?: "Expected array to have key '$key'"
        );
    }

    protected function assertGreaterThan(int $expected, int $actual, string $message = ''): void
    {
        $this->assertTrue($actual > $expected, $message ?: "Expected $actual > $expected");
    }

    protected function assertInstanceOf(string $class, $object, string $message = ''): void
    {
        $this->assertTrue(
            $object instanceof $class,
            $message ?: 'Expected instance of ' . $class
        );
    }

    public function setUp(): void {}
    public function tearDown(): void {}

    abstract public function runAll(): array;

    protected function run(string $methodName): array
    {
        $this->setUp();
        try {
            $this->$methodName();
        } catch (\Throwable $e) {
            $this->failed++;
            $this->failures[] = "$methodName threw: " . $e->getMessage();
        }
        $this->tearDown();
        return [
            'method' => $methodName,
            'assertions' => $this->assertions,
            'passed' => $this->passed,
            'failed' => $this->failed,
            'failures' => $this->failures,
        ];
    }

    protected function runTests(array $methods): array
    {
        $results = [];
        foreach ($methods as $method) {
            $this->assertions = 0;
            $this->passed = 0;
            $this->failed = 0;
            $this->failures = [];
            $results[] = $this->run($method);
        }
        return $results;
    }
}