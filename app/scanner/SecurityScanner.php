<?php
namespace App\Scanner;

use App\Models\SecurityResult;

class SecurityScanner implements SecurityScannerInterface
{
    public function scan(string $html, string $url, array $headers = []): SecurityResult
    {
        $r = new SecurityResult();

        // HTTPS
        $r->hasHttps = str_starts_with($url, 'https://');
        if (!$r->hasHttps) {
            $r->issues[] = 'Site does not use HTTPS — all data transmitted in plain text';
            $r->score -= 25;
            $r->recommendations[] = 'Enable HTTPS with a valid SSL/TLS certificate';
        }

        // Security headers
        $r->hasHsts = $this->headerContains($headers, 'strict-transport-security');
        $r->hasXFrameOptions = $this->headerContains($headers, 'x-frame-options');
        $r->hasXContentTypeOptions = $this->headerContains($headers, 'x-content-type-options');
        $r->hasCsp = $this->headerContains($headers, 'content-security-policy');

        if (!$r->hasHsts && $r->hasHttps) {
            $r->issues[] = 'HSTS header missing — MITM attacks possible on first visit';
            $r->score -= 10;
            $r->recommendations[] = 'Add Strict-Transport-Security header with max-age=31536000';
        }
        if (!$r->hasXFrameOptions) {
            $r->issues[] = 'X-Frame-Options header missing — site vulnerable to clickjacking';
            $r->score -= 8;
            $r->recommendations[] = 'Add X-Frame-Options: DENY or SAMEORIGIN header';
        }
        if (!$r->hasXContentTypeOptions) {
            $r->issues[] = 'X-Content-Type-Options header missing — MIME sniffing risk';
            $r->score -= 5;
            $r->recommendations[] = 'Add X-Content-Type-Options: nosniff header';
        }
        if (!$r->hasCsp) {
            $r->issues[] = 'Content-Security-Policy header missing — XSS protection weakened';
            $r->score -= 10;
            $r->recommendations[] = 'Add Content-Security-Policy header to control allowed resources';
        }

        // Mixed content
        $r->mixedContentCount = $this->countMixedContent($r->hasHttps, $html);
        if ($r->mixedContentCount > 0) {
            $r->issues[] = "{$r->mixedContentCount} mixed content resources found — insecure HTTP references on HTTPS page";
            $r->score -= min(15, $r->mixedContentCount * 5);
            $r->recommendations[] = 'Replace http:// URLs with https:// or protocol-relative URLs';
        }

        // Cookie security
        $r->hasSecureCookies = $this->checkCookieSecurity($html);
        if (!$r->hasSecureCookies && $r->hasHttps) {
            $r->issues[] = 'Cookies missing Secure/HttpOnly flags';
            $r->score -= 8;
            $r->recommendations[] = 'Set Secure, HttpOnly, and SameSite=Lax flags on all cookies';
        }

        // Insecure forms
        $r->insecureForms = $this->countInsecureForms($r->hasHttps, $html);
        if ($r->insecureForms > 0) {
            $r->issues[] = "{$r->insecureForms} form(s) submit to insecure (HTTP) endpoints";
            $r->score -= 10;
            $r->recommendations[] = 'Ensure all form actions use HTTPS endpoints';
        }

        // Priority fixes
        if (!$r->hasHttps) $r->priorityFixes[] = 'Enable HTTPS immediately — highest priority';
        if ($r->mixedContentCount > 0) $r->priorityFixes[] = 'Fix mixed content warnings';
        if (!$r->hasCsp) $r->priorityFixes[] = 'Add Content-Security-Policy header';

        // Summary
        $r->score = max(0, min(100, $r->score));
        if ($r->score >= 80) {
            $r->summary = "Good security posture ({$r->score}/100). Minor header improvements possible.";
        } elseif ($r->score >= 60) {
            $r->summary = "Moderate security ({$r->score}/100). Address missing security headers.";
        } else {
            $r->summary = "Poor security ({$r->score}/100). " . (!$r->hasHttps ? 'HTTPS required. ' : '') . "Critical vulnerabilities present.";
        }

        return $r;
    }

    private function headerContains(array $headers, string $name): bool
    {
        foreach ($headers as $key => $value) {
            $k = strtolower(is_int($key) ? $value : $key);
            if (strpos($k, $name) !== false) return true;
        }
        return false;
    }

    private function countMixedContent(bool $isHttps, string $html): int
    {
        if (!$isHttps) return 0;
        return preg_match_all('/(?:src|href)=["\']http:\/\//i', $html);
    }

    private function checkCookieSecurity(string $html): bool
    {
        if (preg_match('/Set-Cookie/i', $html)) {
            return preg_match('/Secure/i', $html) && preg_match('/HttpOnly/i', $html);
        }
        return true; // no cookies = no cookie risk
    }

    private function countInsecureForms(bool $isHttps, string $html): int
    {
        if (!$isHttps) return 0;
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);
        $count = 0;
        foreach ($xpath->query('//form[@action]') as $form) {
            $action = $form->getAttribute('action');
            if (str_starts_with($action, 'http://')) $count++;
        }
        return $count;
    }
}
