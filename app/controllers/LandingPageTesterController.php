<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Scanner\LandingPageScanner;

/**
 * LandingPageTesterController — handles the Landing Page Tester Agent UI and API.
 */
class LandingPageTesterController extends Controller
{
    /**
     * GET /landing-tester — show the tester page.
     */
    public function index(): string
    {
        return $this->render('public/landing-tester', [
            'pageTitle' => 'Landing Page Tester — LandingFlow',
        ]);
    }

    /**
     * POST /landing-tester/test — run the scan and return JSON.
     */
    public function test(): void
    {
        $url = $_POST['url'] ?? '';

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            http_response_code(422);
            die(json_encode([
                'success' => false,
                'error'   => 'Please enter a valid URL (including https://).',
            ]));
        }

        $url = rtrim($url, '/');

        // Fetch the page
        $ctx = stream_context_create([
            'http' => [
                'timeout'    => 15,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 LandingFlow-Tester/1.0',
            ],
        ]);

        $html = @file_get_contents($url, false, $ctx);
        if ($html === false || empty($html)) {
            http_response_code(502);
            die(json_encode([
                'success' => false,
                'error'   => 'Could not fetch the page. Check the URL or try again later.',
            ]));
        }

        // Capture headers
        $headers = [];
        if (isset($http_response_header)) {
            foreach ($http_response_header as $h) {
                $headers[] = $h;
            }
        }

        // Run the landing page scanner
        $scanner = new LandingPageScanner();
        try {
            $result = $scanner->scan($html, $url, $headers);
        } catch (\Throwable $e) {
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'error'   => 'Scan failed: ' . $e->getMessage(),
            ]));
        }

        // Build response
        $response = [
            'success'        => true,
            'url'            => $url,
            'date'           => date('Y-m-d'),
            'test_time'      => $result->testTime,
            'overall_score'  => $result->score,
            'summary'        => $result->summary,
            'categories'     => [
                'render'      => $result->renderScore,
                'technical'   => $result->technicalScore,
                'design'      => $result->designScore,
                'mobile'      => $result->mobileScore,
                'performance' => $result->performanceScore,
                'ux'          => $result->uxScore,
                'content'     => $result->contentScore,
            ],
            'counts'         => [
                'total'    => $result->totalChecks,
                'passed'   => $result->passedChecks,
                'warnings' => $result->warningChecks,
                'failed'   => $result->failedChecks,
            ],
            'checks'         => $result->checks,
            'critical_fixes' => $result->criticalFixes,
            'important_fixes'=> $result->importantFixes,
            'nice_fixes'     => $result->niceFixes,
            'recommendations'=> $result->recommendations,
        ];

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * POST /landing-tester/test-html — test raw HTML input.
     */
    public function testHtml(): void
    {
        $html = $_POST['html'] ?? '';
        $url  = $_POST['url'] ?? 'https://example.com';

        if (empty(trim($html))) {
            http_response_code(422);
            die(json_encode([
                'success' => false,
                'error'   => 'Please paste valid HTML code.',
            ]));
        }

        $scanner = new LandingPageScanner();
        try {
            $result = $scanner->scan($html, $url, []);
        } catch (\Throwable $e) {
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'error'   => 'Scan failed: ' . $e->getMessage(),
            ]));
        }

        $response = [
            'success'        => true,
            'url'            => $url,
            'date'           => date('Y-m-d'),
            'test_time'      => $result->testTime,
            'overall_score'  => $result->score,
            'summary'        => $result->summary,
            'categories'     => [
                'render'      => $result->renderScore,
                'technical'   => $result->technicalScore,
                'design'      => $result->designScore,
                'mobile'      => $result->mobileScore,
                'performance' => $result->performanceScore,
                'ux'          => $result->uxScore,
                'content'     => $result->contentScore,
            ],
            'counts'         => [
                'total'    => $result->totalChecks,
                'passed'   => $result->passedChecks,
                'warnings' => $result->warningChecks,
                'failed'   => $result->failedChecks,
            ],
            'checks'         => $result->checks,
            'critical_fixes' => $result->criticalFixes,
            'important_fixes'=> $result->importantFixes,
            'nice_fixes'     => $result->niceFixes,
            'recommendations'=> $result->recommendations,
        ];

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
