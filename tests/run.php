<?php
/**
 * Simple PHP test runner for LandingFlow.
 * Run: php tests/run.php
 */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/TestCase.php';

$testFiles = [
    'Core/ValidatorTest.php' => 'ValidatorTest',
    'Auth/PasswordValidationTest.php' => 'PasswordValidationTest',
    'Auth/AuthMiddlewareTest.php' => 'AuthMiddlewareTest',
    'Auth/AuthControllerTest.php' => 'AuthControllerTest',
    'Auth/EmailVerificationTest.php' => 'EmailVerificationTest',
    'Lead/LeadControllerTest.php' => 'LeadControllerTest',
    'Scanner/SeoScannerTest.php' => 'SeoScannerTest',
    'Scanner/SeoScannerQATest.php' => 'SeoScannerQATest',
    'Scanner/PerformanceScannerTest.php' => 'PerformanceScannerTest',
    'Scanner/SecurityScannerTest.php' => 'SecurityScannerTest',
    'Scanner/AccessibilityScannerTest.php' => 'AccessibilityScannerTest',
    'Scanner/SpamScannerTest.php' => 'SpamScannerTest',
    'Ai/AiLayerTest.php' => 'AiLayerTest',
    'LeadEngine/LeadEngineTest.php' => 'LeadEngineTest',
    'Integration/IntegrationTest.php' => 'IntegrationTest',
    'Api/ApiLayerTest.php' => 'ApiLayerTest',
];

$totalAssertions = 0;
$totalPassed = 0;
$totalFailed = 0;
$totalTests = 0;
$failedTests = [];

echo str_repeat('=', 60) . "\n";
echo "LandingFlow Test Suite\n";
echo "PHP " . PHP_VERSION . " | " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 60) . "\n\n";

foreach ($testFiles as $file => $class) {
    $path = __DIR__ . '/' . $file;
    if (!file_exists($path)) {
        echo "[SKIP] $class - file not found: $file\n";
        continue;
    }
    require_once $path;

    if (!class_exists($class)) {
        echo "[SKIP] $class - class not found\n";
        continue;
    }

    $test = new $class();
    $results = $test->runAll();

    $classAssertions = 0;
    $classPassed = 0;
    $classFailed = 0;

    echo "----------------------------------------------------------\n";
    echo "  $class\n";
    echo "----------------------------------------------------------\n";

    foreach ($results as $r) {
        $status = $r['failed'] === 0 ? 'PASS' : 'FAIL';
        $icon = $r['failed'] === 0 ? '?' : '?';
        echo "  $icon {$r['method']} � {$r['passed']}/{$r['assertions']} assertions\n";

        foreach ($r['failures'] as $failure) {
            echo "     ? $failure\n";
        }

        $classAssertions += $r['assertions'];
        $classPassed += $r['passed'];
        $classFailed += $r['failed'];
        $totalTests++;

        if ($r['failed'] > 0) {
            $failedTests[] = "$class::{$r['method']}";
        }
    }

    echo "  -- $classPassed/$classAssertions passed";
    if ($classFailed > 0) echo ", $classFailed FAILED";
    echo "\n\n";

    $totalAssertions += $classAssertions;
    $totalPassed += $classPassed;
    $totalFailed += $classFailed;
}

echo str_repeat('=', 60) . "\n";
echo "RESULTS SUMMARY\n";
echo str_repeat('=', 60) . "\n";
echo "  Total tests:     $totalTests\n";
echo "  Total assertions: $totalAssertions\n";
echo "  Passed:          $totalPassed\n";
echo "  Failed:          $totalFailed\n";
echo "  Pass rate:       " . ($totalAssertions > 0 ? round($totalPassed / $totalAssertions * 100, 1) : 0) . "%\n";

if (count($failedTests) > 0) {
    echo "\n  FAILED TESTS:\n";
    foreach ($failedTests as $ft) {
        echo "    ? $ft\n";
    }
}

echo "\n";
echo $totalFailed === 0 ? "? ALL TESTS PASSED\n" : "? SOME TESTS FAILED\n";
echo str_repeat('=', 60) . "\n";

exit($totalFailed > 0 ? 1 : 0);
