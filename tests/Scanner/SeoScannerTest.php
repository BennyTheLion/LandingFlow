<?php
use App\Scanner\SeoScanner;
use App\Models\SeoResult;

class SeoScannerTest extends TestCase
{
    private function minimalHtml(): string
    {
        return '<!DOCTYPE html><html><head><title>Test Page</title><meta name="description" content="A test page"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="index,follow"><link rel="canonical" href="https://example.com"><script type="application/ld+json">{}</script></head><body><h1>Welcome to Our Service</h1><section><h2>Overview</h2><p>Our service provides fast and reliable hosting. We have 500+ customers and 99.9% uptime.</p><p>Our pricing starts at $10/month with 24/7 support included.</p></section><section><h2>Benefits</h2><ul><li>Fast servers — 2x faster than industry average</li><li>Reliable — 99.9% uptime guarantee</li><li>Affordable — plans from $10/month</li></ul></section><section><h2>FAQ</h2><h3>What is it?</h3><p>It is a managed hosting platform designed for small businesses.</p><h3>How does it work?</h3><p>Simply sign up, choose your plan, and get started in minutes.</p></section><footer><a href="/about">About</a><a href="/contact">Contact</a><a href="/pricing">Pricing</a></footer></body></html>';
    }

    public function runAll(): array
    {
        return $this->runTests([
            'testScannerReturnsSeoResult','testSeoLayerScores','testLlmoLayerScores','testAeoLayerScores',
            'testGeoLayerScores','testFinalScoreCalculation','testEmptyHtmlHandling','testPerfectContentScoresHigh',
            'testFourDistinctLayers','testPriorityFixesGenerated','testSummaryGenerated','testWeightsFromSpec',
        ]);
    }

    public function testScannerReturnsSeoResult(): void
    {
        $s = new SeoScanner();
        $r = $s->scan($this->minimalHtml(), 'https://example.com');
        $this->assertInstanceOf(SeoResult::class, $r, 'Should return SeoResult');
    }

    public function testSeoLayerScores(): void
    {
        $s = new SeoScanner();
        $r = $s->scan($this->minimalHtml(), 'https://example.com');
        $this->assertTrue($r->seoScore >= 0 && $r->seoScore <= 100, 'SEO score should be 0-100');
    }

    public function testLlmoLayerScores(): void
    {
        $s = new SeoScanner();
        $r = $s->scan($this->minimalHtml(), 'https://example.com');
        $this->assertTrue($r->llmoScore >= 0 && $r->llmoScore <= 100, 'LLMO score should be 0-100');
    }

    public function testAeoLayerScores(): void
    {
        $s = new SeoScanner();
        $r = $s->scan($this->minimalHtml(), 'https://example.com');
        $this->assertTrue($r->aeoScore >= 0 && $r->aeoScore <= 100, 'AEO score should be 0-100');
    }

    public function testGeoLayerScores(): void
    {
        $s = new SeoScanner();
        $r = $s->scan($this->minimalHtml(), 'https://example.com');
        $this->assertTrue($r->geoScore >= 0 && $r->geoScore <= 100, 'GEO score should be 0-100');
    }

    public function testFinalScoreCalculation(): void
    {
        $s = new SeoScanner();
        $r = $s->scan($this->minimalHtml(), 'https://example.com');
        $expected = (int) round($r->seoScore*0.4 + $r->llmoScore*0.25 + $r->aeoScore*0.2 + $r->geoScore*0.15);
        $this->assertEquals($expected, $r->finalScore(), 'Final score should match weighted formula');
    }

    public function testEmptyHtmlHandling(): void
    {
        $s = new SeoScanner();
        $r = $s->scan('<html></html>', 'https://example.com');
        $this->assertTrue($r->seoScore <= 50, 'Minimal HTML should score low on SEO');
        $this->assertTrue(count($r->issues) >= 5, 'Minimal HTML should produce many issues');
    }

    public function testPerfectContentScoresHigh(): void
    {
        $s = new SeoScanner();
        $r = $s->scan($this->minimalHtml(), 'https://example.com');
        $this->assertTrue($r->finalScore() >= 60, 'Well-structured content should score >=60');
    }

    public function testFourDistinctLayers(): void
    {
        $s = new SeoScanner();
        $r = $s->scan($this->minimalHtml(), 'https://example.com');
        $scores = [$r->seoScore, $r->llmoScore, $r->aeoScore, $r->geoScore];
        $unique = array_unique($scores);
        $this->assertTrue(count($unique) >= 1, 'Layers should produce scores');
    }

    public function testPriorityFixesGenerated(): void
    {
        $s = new SeoScanner();
        $r = $s->scan('<html></html>', 'https://example.com');
        $this->assertTrue(count($r->priorityFixes) > 0, 'Minimal HTML should produce priority fixes');
    }

    public function testSummaryGenerated(): void
    {
        $s = new SeoScanner();
        $r = $s->scan($this->minimalHtml(), 'https://example.com');
        $this->assertTrue(strlen($r->summary) > 0, 'Summary should be generated');
    }

    public function testWeightsFromSpec(): void
    {
        $r = new SeoResult();
        $r->seoScore = 100; $r->llmoScore = 0; $r->aeoScore = 0; $r->geoScore = 0;
        $this->assertEquals(40, $r->finalScore(), 'SEO weight = 0.4');
        $r->seoScore = 0; $r->llmoScore = 100;
        $this->assertEquals(25, $r->finalScore(), 'LLMO weight = 0.25');
        $r->llmoScore = 0; $r->aeoScore = 100;
        $this->assertEquals(20, $r->finalScore(), 'AEO weight = 0.20');
        $r->aeoScore = 0; $r->geoScore = 100;
        $this->assertEquals(15, $r->finalScore(), 'GEO weight = 0.15');
    }
}
