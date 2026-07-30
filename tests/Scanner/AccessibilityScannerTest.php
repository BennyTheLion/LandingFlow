<?php
use App\Scanner\AccessibilityScanner;
use App\Models\AccessibilityResult;

class AccessibilityScannerTest extends TestCase
{
    private AccessibilityScanner $scanner;

    public function setUp(): void { $this->scanner = new AccessibilityScanner(); }

    private function goodPage(): string
    {
        return '<!DOCTYPE html><html lang="en"><head><title>Accessible</title></head><body><a href="#main">Skip to content</a><main id="main"><h1>Welcome</h1><h2>Section</h2><h3>Sub</h3><img src="/a.jpg" alt="Photo"><form><label for="email">Email</label><input id="email" type="email"><button aria-label="Submit form">Send</button></form></main></body></html>';
    }

    private function badPage(): string
    {
        return '<html><body><h1>Title</h1><h3>Skip H2</h3><img src="/a.jpg"><img src="/b.jpg"><input type="text" name="name"><button></button></body></html>';
    }

    public function runAll(): array
    {
        return $this->runTests([
            'testReturnsResult','testGoodPageScoresHigh','testBadPageScoresLow',
            'testAltTextDetected','testFormLabelsDetected','testMissingAltTextFlagged',
            'testMissingLabelsFlagged','testHeadingGapDetected','testH1Required',
            'testSkipLinkDetected','testOutputContract','testSummaryGenerated',
        ]);
    }

    public function testReturnsResult(): void
    {
        $r = $this->scanner->scan($this->goodPage(), 'https://test.com');
        $this->assertInstanceOf(AccessibilityResult::class, $r);
    }

    public function testGoodPageScoresHigh(): void
    {
        $r = $this->scanner->scan($this->goodPage(), 'https://test.com');
        $this->assertTrue($r->score >= 70, "Accessible page should score >=70, got {$r->score}");
    }

    public function testBadPageScoresLow(): void
    {
        $r = $this->scanner->scan($this->badPage(), 'https://test.com');
        $this->assertTrue($r->score < 70, "Bad page should score <70, got {$r->score}");
    }

    public function testAltTextDetected(): void
    {
        $r = $this->scanner->scan($this->goodPage(), 'https://test.com');
        $this->assertEquals(0, $r->imagesWithoutAlt, 'Good page should have 0 images without alt');
    }

    public function testFormLabelsDetected(): void
    {
        $r = $this->scanner->scan($this->goodPage(), 'https://test.com');
        $this->assertEquals(0, $r->inputsWithoutLabel, 'Good page should have labeled inputs');
    }

    public function testMissingAltTextFlagged(): void
    {
        $r = $this->scanner->scan($this->badPage(), 'https://test.com');
        $this->assertTrue($r->imagesWithoutAlt > 0, 'Bad page should detect missing alt text');
    }

    public function testMissingLabelsFlagged(): void
    {
        $r = $this->scanner->scan($this->badPage(), 'https://test.com');
        $this->assertTrue($r->inputsWithoutLabel > 0, 'Bad page should detect unlabeled inputs');
    }

    public function testHeadingGapDetected(): void
    {
        $r = $this->scanner->scan($this->badPage(), 'https://test.com');
        $this->assertTrue($r->headingGaps > 0, 'Bad page should detect h1→h3 gap');
    }

    public function testH1Required(): void
    {
        $r = $this->scanner->scan('<html><body><p>No headings</p></body></html>', 'https://test.com');
        $this->assertTrue($r->score < 90, 'No H1 should reduce score');
    }

    public function testSkipLinkDetected(): void
    {
        $r = $this->scanner->scan($this->goodPage(), 'https://test.com');
        $this->assertTrue($r->hasSkipLink, 'Skip link should be detected');
    }

    public function testOutputContract(): void
    {
        $r = $this->scanner->scan($this->goodPage(), 'https://test.com');
        $arr = $r->toArray();
        foreach (['score','images_without_alt','inputs_without_label','has_skip_link','heading_gaps','issues'] as $k) {
            $this->assertTrue(array_key_exists($k, $arr), "Missing: $k");
        }
    }

    public function testSummaryGenerated(): void
    {
        $r = $this->scanner->scan($this->goodPage(), 'https://test.com');
        $this->assertTrue(strlen($r->summary) > 0);
    }
}
