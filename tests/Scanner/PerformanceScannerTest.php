<?php
use App\Scanner\PerformanceScanner;
use App\Models\PerformanceResult;

class PerformanceScannerTest extends TestCase
{
    private PerformanceScanner $scanner;

    public function setUp(): void { $this->scanner = new PerformanceScanner(); }

    private function goodPage(): string
    {
        return '<!DOCTYPE html><html><head><title>Fast Page</title></head><body><h1>Hello</h1><p>A clean, small page.</p><img src="/img.jpg" width="100" height="100" alt="Photo"></body></html>';
    }

    private function heavyPage(): string
    {
        $h = '<!DOCTYPE html><html><head><title>Heavy</title>';
        for ($i = 0; $i < 30; $i++) $h .= '<link rel="stylesheet" href="/css' . $i . '.css">';
        $h .= '<script src="/app.js"></script><script src="/lib.js"></script></head><body><h1>Heavy Page</h1>';
        for ($i = 0; $i < 100; $i++) $h .= '<div><p>Content ' . $i . '</p></div>';
        for ($i = 0; $i < 20; $i++) $h .= '<img src="/img' . $i . '.jpg">';
        $h .= str_repeat('<p>Lorem ipsum dolor sit amet.</p>', 50);
        return $h . '</body></html>';
    }

    public function runAll(): array
    {
        return $this->runTests([
            'testReturnsPerformanceResult','testGoodPageScoresHigh','testHeavyPageScoresLow',
            'testPageSizeDetected','testHttpRequestsCounted','testCompressionDetected',
            'testCachingDetected','testMissingCompressionPenalized','testMissingCachingPenalized',
            'testUnoptimizedImagesDetected','testDomComplexity','testRenderBlockingDetected',
            'testOutputContract','testSummaryGenerated',
        ]);
    }

    public function testReturnsPerformanceResult(): void
    {
        $r = $this->scanner->scan($this->goodPage(), 'https://test.com');
        $this->assertInstanceOf(PerformanceResult::class, $r);
    }

    public function testGoodPageScoresHigh(): void
    {
        $r = $this->scanner->scan($this->goodPage(), 'https://test.com', ['content-encoding: gzip', 'cache-control: max-age=3600']);
        $this->assertTrue($r->score >= 70, "Good page should score >=70, got {$r->score}");
    }

    public function testHeavyPageScoresLow(): void
    {
        $r = $this->scanner->scan($this->heavyPage(), 'https://test.com');
        $this->assertTrue($r->score < 70, "Heavy page should score <70, got {$r->score}");
    }

    public function testPageSizeDetected(): void
    {
        $r = $this->scanner->scan($this->goodPage(), 'https://test.com');
        $this->assertTrue($r->pageSizeKb > 0, 'Page size should be detected');
    }

    public function testHttpRequestsCounted(): void
    {
        $r = $this->scanner->scan($this->heavyPage(), 'https://test.com');
        $this->assertTrue($r->httpRequests > 10, 'Heavy page should have many requests');
    }

    public function testCompressionDetected(): void
    {
        $r = $this->scanner->scan($this->goodPage(), 'https://test.com', ['Content-Encoding' => 'gzip']);
        $this->assertTrue($r->hasCompression, 'Should detect gzip');
    }

    public function testCachingDetected(): void
    {
        $r = $this->scanner->scan($this->goodPage(), 'https://test.com', ['Cache-Control' => 'max-age=3600']);
        $this->assertTrue($r->hasCaching, 'Should detect cache-control');
    }

    public function testMissingCompressionPenalized(): void
    {
        $r = $this->scanner->scan($this->goodPage(), 'https://test.com', []);
        $this->assertFalse($r->hasCompression);
        $this->assertTrue(count($r->issues) > 0);
    }

    public function testMissingCachingPenalized(): void
    {
        $r = $this->scanner->scan($this->goodPage(), 'https://test.com', []);
        $this->assertFalse($r->hasCaching);
    }

    public function testUnoptimizedImagesDetected(): void
    {
        $html = '<html><body><img src="a.jpg"><img src="b.jpg" width="100"></body></html>';
        $r = $this->scanner->scan($html, 'https://test.com');
        $this->assertTrue($r->unoptimizedImages > 0, 'Images without dimensions should be detected');
    }

    public function testDomComplexity(): void
    {
        $r = $this->scanner->scan($this->heavyPage(), 'https://test.com');
        $this->assertTrue($r->domNodes > 200, 'Heavy page should have many DOM nodes');
    }

    public function testRenderBlockingDetected(): void
    {
        $r = $this->scanner->scan($this->heavyPage(), 'https://test.com');
        $this->assertTrue($r->hasRenderBlocking, 'Heavy page should have render-blocking resources');
    }

    public function testOutputContract(): void
    {
        $r = $this->scanner->scan($this->goodPage(), 'https://test.com');
        $arr = $r->toArray();
        foreach (['score','page_size_kb','http_requests','has_compression','has_caching','image_count','issues','recommendations'] as $k) {
            $this->assertTrue(array_key_exists($k, $arr), "Missing key: $k");
        }
    }

    public function testScoreBounds(): void
    {
        $r = $this->scanner->scan('', 'https://test.com');
        $this->assertTrue($r->score >= 0 && $r->score <= 100, 'Score must be 0-100');
    }

    public function testSummaryGenerated(): void
    {
        $r = $this->scanner->scan($this->goodPage(), 'https://test.com');
        $this->assertTrue(strlen($r->summary) > 0, 'Summary should be generated');
    }
}
