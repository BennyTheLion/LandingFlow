<?php
use App\Scanner\SeoScanner;
use App\Models\SeoResult;

class SeoScannerQATest extends TestCase
{
    private SeoScanner $scanner;

    public function setUp(): void { $this->scanner = new SeoScanner(); }

    // === REAL WEBSITE TYPES ===

    private function landingPage(): string
    {
        return '<!DOCTYPE html><html lang="en"><head><title>Grow Your Business — Acme SaaS</title><meta name="description" content="All-in-one business platform. 10k+ customers."><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="canonical" href="https://acme.com"><script type="application/ld+json">{"@type":"SoftwareApplication"}</script></head><body><header><nav><a href="/">Home</a><a href="/pricing">Pricing</a></nav></header><main><section><h1>Grow Your Business</h1><p>Acme helps 10,000+ businesses grow with AI-powered tools. Our platform reduces costs by 30% on average.</p><a href="/trial">Start Free Trial</a></section><section><h2>Key Benefits</h2><ul><li><strong>30% cost reduction</strong> — verified by independent audit</li><li><strong>2x faster workflow</strong> — compared to manual processes</li><li><strong>99.9% uptime</strong> — enterprise-grade reliability</li></ul></section><section><h2>FAQ</h2><h3>What is Acme?</h3><p>Acme is an all-in-one business management platform.</p><h3>How does pricing work?</h3><p>Plans start at $29/month with a 14-day free trial.</p><h3>What are the benefits?</h3><p>Automation, analytics, and team collaboration in one place.</p></section></main><footer><a href="/privacy">Privacy</a><a href="/terms">Terms</a></footer></body></html>';
    }

    private function blogPage(): string
    {
        return '<!DOCTYPE html><html><head><title>10 SEO Tips for 2024 — Our Blog</title><meta name="description" content="Learn the top 10 SEO tips for 2024"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body><article><h1>10 SEO Tips for 2024</h1><p>Published: January 2024</p><section><h2>1. Optimize for mobile</h2><p>Over 60% of searches now come from mobile devices. Responsive design is no longer optional.</p></section><section><h2>2. Focus on Core Web Vitals</h2><p>Google confirmed that page experience signals directly impact rankings.</p></section><section><h2>3. Use structured data</h2><p>JSON-LD schema markup helps search engines understand your content.</p></section></article></body></html>';
    }

    private function ecommercePage(): string
    {
        return '<!DOCTYPE html><html><head><title>Product Name — Buy Online | Store</title><meta name="description" content="Buy Product Name at the best price. Free shipping."><meta name="viewport" content="width=device-width,initial-scale=1"></head><body><main><h1>Product Name</h1><p class="price">$49.99</p><p>In stock — ships in 24 hours</p><section><h2>Description</h2><p>High-quality product with premium materials. 4.8/5 stars from 2,500 reviews.</p><ul><li>Feature 1: Durable aluminum body</li><li>Feature 2: 12-hour battery life</li><li>Feature 3: 1-year warranty included</li></ul></section><section><h2>Reviews</h2><p>Average rating: 4.8/5 from 2,500 verified purchases.</p></section></main></body></html>';
    }

    // === EDGE CASES ===

    private function brokenHtml(): string
    {
        return '<html><head><title>Broken</title></head><body><h1>Test</h1><p>Unclosed paragraph<div>Unclosed div</body>';
    }

    private function missingMetadata(): string
    {
        return '<html><body><h1>No Head Section</h1><p>This page has no title, no meta tags, no schema.</p></body></html>';
    }

    private function multilingualHtml(): string
    {
        return '<!DOCTYPE html><html lang="he" dir="rtl"><head><title>שירותי אירוח אתרים — LandingFlow</title><meta name="description" content="אחסון אתרים מהיר ואמין. 500+ לקוחות מרוצים."><meta name="viewport" content="width=device-width,initial-scale=1"></head><body><main><h1>שירותי אירוח אתרים</h1><section><h2>התוכניות שלנו</h2><p>אנו מציעים תוכניות אחסון המתאימות לכל עסק. החל מ-$5 לחודש.</p></section><section><h2>שאלות נפוצות</h2><h3>מהו אחסון אתרים?</h3><p>אחסון אתרים הוא שירות המאפשר לך לפרסם את האתר שלך באינטרנט.</p></section></main></body></html>';
    }

    private function spamPage(): string
    {
        return '<html><head><title>Buy Cheap Now!!! Best Deals!!!</title></head><body><h1>BUY NOW!!! AMAZING DEALS!!!</h1><p>Click here click here click here!!! Amazing incredible fantastic wonderful stunning breathtaking deals!!! Buy now!!! Limited time!!!</p></body></html>';
    }

    private function jsHeavyPage(): string
    {
        return '<!DOCTYPE html><html><head><title>SPA App</title><script src="/app.js"></script></head><body><div id="root"></div><noscript>Please enable JavaScript to view this application.</noscript></body></html>';
    }

    public function runAll(): array
    {
        return $this->runTests([
            // Real website types
            'testLandingPageScan','testBlogPageScan','testEcommercePageScan',
            // Edge cases
            'testBrokenHtmlHandling','testMissingMetadataHandling','testMultilingualHebrew',
            'testSpamLowQualityPage','testJsHeavyPage','testScoreConsistency',
            // Integration
            'testOutputMatchesContract','testResultsAreDeterministic',
        ]);
    }

    public function testLandingPageScan(): void
    {
        $r = $this->scanner->scan($this->landingPage(), 'https://acme.com');
        $this->assertTrue($r->finalScore() >= 60, "Landing page should score >=60, got {$r->finalScore()}");
        $this->assertTrue(count($r->issues) > 0, 'Should have issues');
        $this->assertTrue(count($r->recommendations) > 0, 'Should have recommendations');
    }

    public function testBlogPageScan(): void
    {
        $r = $this->scanner->scan($this->blogPage(), 'https://blog.example.com');
        $this->assertTrue($r->seoScore > 0, 'Blog should have SEO score');
        $this->assertTrue(count($r->issues) > 0, 'Blog should have issues');
    }

    public function testEcommercePageScan(): void
    {
        $r = $this->scanner->scan($this->ecommercePage(), 'https://store.example.com');
        $this->assertTrue($r->seoScore > 0, 'Ecommerce should have SEO score');
        $this->assertTrue(count($r->issues) > 0, 'Should detect missing schema on ecommerce');
    }

    public function testBrokenHtmlHandling(): void
    {
        $r = $this->scanner->scan($this->brokenHtml(), 'https://broken.example.com');
        $this->assertInstanceOf(SeoResult::class, $r, 'Broken HTML should not crash');
        $this->assertTrue($r->finalScore() >= 0, 'Score should be valid even on broken HTML');
    }

    public function testMissingMetadataHandling(): void
    {
        $r = $this->scanner->scan($this->missingMetadata(), 'https://nometa.example.com');
        $this->assertTrue($r->seoScore < 60, "Missing metadata should score low, got {$r->seoScore}");
        $foundTitleIssue = false;
        foreach ($r->issues as $i) { if (stripos($i, 'title') !== false) $foundTitleIssue = true; }
        $this->assertTrue($foundTitleIssue, 'Should detect missing title');
    }

    public function testMultilingualHebrew(): void
    {
        $r = $this->scanner->scan($this->multilingualHtml(), 'https://landingflow.co.il');
        $this->assertTrue($r->finalScore() >= 0, 'Hebrew RTL content should not crash');
        $this->assertTrue(mb_strlen($r->summary) > 0, 'Summary should be generated for Hebrew content');
    }

    public function testSpamLowQualityPage(): void
    {
        $r = $this->scanner->scan($this->spamPage(), 'https://spam.example.com');
        $this->assertTrue($r->llmoScore < 70, "Spam page should score low on LLMO (ambiguous language), got {$r->llmoScore}");
        $this->assertTrue($r->geoScore < 70, "Spam page should score low on GEO (emotional tone), got {$r->geoScore}");
    }

    public function testJsHeavyPage(): void
    {
        $r = $this->scanner->scan($this->jsHeavyPage(), 'https://spa.example.com');
        $this->assertInstanceOf(SeoResult::class, $r, 'JS-heavy SPA should not crash');
        $this->assertTrue($r->seoScore < 80, 'JS-heavy page with little content should score lower');
    }

    public function testScoreConsistency(): void
    {
        $html = $this->landingPage();
        $r1 = $this->scanner->scan($html, 'https://a.com');
        $r2 = $this->scanner->scan($html, 'https://b.com');
        $this->assertEquals($r1->finalScore(), $r2->finalScore(), 'Same HTML should produce same score regardless of URL');
    }

    public function testOutputMatchesContract(): void
    {
        $r = $this->scanner->scan($this->landingPage(), 'https://test.com');
        $arr = $r->toArray();
        $required = ['seo_score','llmo_score','aeo_score','geo_score','final_score','issues','recommendations','priority_fixes','summary'];
        foreach ($required as $key) {
            $this->assertTrue(array_key_exists($key, $arr), "Output must have key: $key");
        }
        $this->assertTrue(is_array($arr['issues']), 'issues must be array');
        $this->assertTrue(is_array($arr['recommendations']), 'recommendations must be array');
        $this->assertTrue(is_array($arr['priority_fixes']), 'priority_fixes must be array');
    }

    public function testNoExceptionsOnAnyInput(): void
    {
        $inputs = [
            '', '<html></html>', '<!DOCTYPE html>', '<h1>Only heading</h1>',
            str_repeat('<p>Long content.</p>', 500),
        ];
        foreach ($inputs as $i => $html) {
            try {
                $this->scanner->scan($html, 'https://test.com');
                $this->assertTrue(true, "Input $i should not throw");
            } catch (\Throwable $e) {
                $this->assertTrue(false, "Input $i threw: " . $e->getMessage());
            }
        }
    }

    public function testResultsAreDeterministic(): void
    {
        $html = $this->blogPage();
        $r1 = $this->scanner->scan($html, 'https://blog.com/post1');
        $r2 = $this->scanner->scan($html, 'https://blog.com/post1');
        $this->assertEquals($r1->seoScore, $r2->seoScore, 'SEO score should be deterministic');
        $this->assertEquals($r1->llmoScore, $r2->llmoScore, 'LLMO score should be deterministic');
        $this->assertEquals($r1->aeoScore, $r2->aeoScore, 'AEO score should be deterministic');
        $this->assertEquals($r1->geoScore, $r2->geoScore, 'GEO score should be deterministic');
    }
}
