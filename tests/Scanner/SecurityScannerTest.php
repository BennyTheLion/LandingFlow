<?php
use App\Scanner\SecurityScanner;
use App\Models\SecurityResult;

class SecurityScannerTest extends TestCase
{
    private SecurityScanner $scanner;
    private string $goodHtml;

    public function setUp(): void
    {
        $this->scanner = new SecurityScanner();
        $this->goodHtml = '<!DOCTYPE html><html><head><title>Secure</title></head><body><h1>Hello</h1><form action="https://example.com/submit" method="post"><input name="csrf" type="hidden"></form></body></html>';
    }

    private function secureHeaders(): array
    {
        return [
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'self'",
        ];
    }

    public function runAll(): array
    {
        return $this->runTests([
            'testReturnsSecurityResult','testHttpsDetected','testHttpFlagged',
            'testHstsDetected','testMissingHeadersPenalized','testMixedContentDetected',
            'testSecureCookies','testInsecureForms','testAllHeadersScoreHigh',
            'testOutputContract','testSummaryGenerated','testPriorityFixes',
        ]);
    }

    public function testReturnsSecurityResult(): void
    {
        $r = $this->scanner->scan($this->goodHtml, 'https://example.com', $this->secureHeaders());
        $this->assertInstanceOf(SecurityResult::class, $r);
    }

    public function testHttpsDetected(): void
    {
        $r = $this->scanner->scan($this->goodHtml, 'https://example.com', $this->secureHeaders());
        $this->assertTrue($r->hasHttps, 'HTTPS should be detected');
    }

    public function testHttpFlagged(): void
    {
        $r = $this->scanner->scan($this->goodHtml, 'http://example.com', []);
        $this->assertFalse($r->hasHttps);
        $this->assertTrue($r->score < 80, "HTTP should score below 80, got {$r->score}");
    }

    public function testHstsDetected(): void
    {
        $r = $this->scanner->scan($this->goodHtml, 'https://example.com', $this->secureHeaders());
        $this->assertTrue($r->hasHsts, 'HSTS should be detected');
    }

    public function testMissingHeadersPenalized(): void
    {
        $r = $this->scanner->scan($this->goodHtml, 'https://example.com', []);
        $this->assertFalse($r->hasCsp, 'CSP should be missing');
        $this->assertTrue(count($r->issues) >= 3, 'Multiple header issues expected');
    }

    public function testMixedContentDetected(): void
    {
        $html = '<html><body><img src="http://insecure.com/img.jpg"><script src="http://insecure.com/app.js"></script></body></html>';
        $r = $this->scanner->scan($html, 'https://example.com', $this->secureHeaders());
        $this->assertTrue($r->mixedContentCount >= 2, "Should detect mixed content, got {$r->mixedContentCount}");
    }

    public function testSecureCookies(): void
    {
        $html = '<html><head><meta http-equiv="Set-Cookie" content="session=abc; Secure; HttpOnly; SameSite=Lax"></head><body></body></html>';
        $r = $this->scanner->scan($html, 'https://example.com', []);
        $this->assertTrue($r->hasSecureCookies, 'Secure cookies should be detected');
    }

    public function testInsecureForms(): void
    {
        $html = '<html><body><form action="http://insecure.com/login" method="post"></form></body></html>';
        $r = $this->scanner->scan($html, 'https://example.com', []);
        $this->assertTrue($r->insecureForms > 0, 'Insecure form action should be detected');
    }

    public function testAllHeadersScoreHigh(): void
    {
        $r = $this->scanner->scan($this->goodHtml, 'https://example.com', $this->secureHeaders());
        $this->assertTrue($r->score >= 80, "All headers present should score >=80, got {$r->score}");
    }

    public function testOutputContract(): void
    {
        $r = $this->scanner->scan($this->goodHtml, 'https://example.com', $this->secureHeaders());
        $arr = $r->toArray();
        foreach (['score','has_https','has_hsts','has_csp','issues','recommendations','priority_fixes'] as $k) {
            $this->assertTrue(array_key_exists($k, $arr), "Missing key: $k");
        }
    }

    public function testSummaryGenerated(): void
    {
        $r = $this->scanner->scan($this->goodHtml, 'https://example.com', $this->secureHeaders());
        $this->assertTrue(strlen($r->summary) > 0, 'Summary should be generated');
    }

    public function testPriorityFixes(): void
    {
        $r = $this->scanner->scan($this->goodHtml, 'http://example.com', []);
        $this->assertTrue(count($r->priorityFixes) > 0, 'HTTP sites should have priority fixes');
    }
}
