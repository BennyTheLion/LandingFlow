<?php
namespace App\Scanner;

use App\Models\SpamResult;

/**
 * SpamScanner — Spam Detection Layer for website audit.
 *
 * Analyzes a website for spam signals, security risks, SEO spam practices,
 * and trust issues across 7 weighted categories:
 *   1. Malware / Phishing / Security Threats (30%)
 *   2. Hidden Content Detection           (20%)
 *   3. Content Spam Analysis              (15%)
 *   4. Link Spam Analysis                 (15%)
 *   5. Domain Reputation                  (10%)
 *   6. Email Reputation & Security        (5%)
 *   7. Form Spam Protection               (5%)
 *
 * Returns a Spam Risk Score (0-100, higher = more spam signals).
 */
class SpamScanner implements SpamScannerInterface
{
    /** @var string[] Known suspicious TLDs and domain patterns */
    private array $suspiciousTlds = ['.tk', '.ml', '.ga', '.cf', '.gq', '.xyz', '.top', '.loan', '.work', '.click'];

    /** @var string[] Spam-related keyword categories for domain/link analysis */
    private array $spamCategories = [
        'gambling' => ['casino', 'gambling', 'bet', 'poker', 'slots', 'lottery'],
        'pharma'   => ['viagra', 'cialis', 'pharmacy', 'pills', 'rx', 'meds'],
        'adult'    => ['porn', 'xxx', 'adult', 'escort', 'dating'],
        'malware'  => ['malware', 'virus', 'trojan', 'hack', 'crack', 'keygen', 'warez'],
    ];

    // ────────────────────────────────────────────────────────────
    // Public entry point
    // ────────────────────────────────────────────────────────────

    public function scan(string $html, string $url, array $headers = []): SpamResult
    {
        $result = new SpamResult();

        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);

        // Normalize body text
        $bodyText = $this->extractBodyText($xpath);

        // Run all 7 detection categories
        $this->detectMalware($dom, $xpath, $html, $url, $result);
        $this->detectHiddenContent($dom, $xpath, $html, $result);
        $this->analyzeContentSpam($bodyText, $html, $result);
        $this->analyzeLinkSpam($dom, $xpath, $result);
        $this->checkDomainReputation($url, $headers, $result);
        $this->checkEmailSecurity($url, $headers, $result);
        $this->checkFormProtection($dom, $xpath, $result);

        // Generate summary
        $this->buildSummary($result);

        return $result;
    }

    // ────────────────────────────────────────────────────────────
    // 1. Malware / Phishing / Security Threats (Weight: 30%)
    // ────────────────────────────────────────────────────────────

    private function detectMalware(\DOMDocument $dom, \DOMXPath $xpath, string $html, string $url, SpamResult $result): void
    {
        $domain = parse_url($url, PHP_URL_HOST) ?? '';
        $malwareHits = 0;

        // 1a. Suspicious external scripts
        $scripts = $xpath->query('//script[@src]');
        foreach ($scripts as $script) {
            $src = $script->getAttribute('src');
            // Known safe CDNs
            $safeCdn = ['googleapis.com', 'gstatic.com', 'cloudflare.com', 'jsdelivr.net',
                        'unpkg.com', 'cdnjs.com', 'jquery.com', 'bootstrapcdn.com',
                        'fontawesome.com', 'googletagmanager.com', 'facebook.net',
                        'analytics.google.com', 'googlesyndication.com'];
            $isSafe = false;
            foreach ($safeCdn as $cdn) {
                if (stripos($src, $cdn) !== false) { $isSafe = true; break; }
            }
            if (!$isSafe && stripos($src, $domain) === false) {
                // Script from unrecognized external domain
                $result->addIssue(
                    "External script from unknown domain: $src",
                    'security', 15
                );
                $malwareHits++;
            }
            // Check for suspicious patterns in script URL
            foreach (['eval(', 'base64,', 'fromCharCode', 'document.write'] as $evil) {
                if (stripos($src, $evil) !== false || stripos($html, $evil) !== false) {
                    $result->addIssue(
                        "Suspicious JavaScript pattern detected: contains '$evil'",
                        'security', 25
                    );
                    $result->addPriorityFix("Remove obfuscated/suspicious JavaScript code");
                    $malwareHits++;
                    break;
                }
            }
        }

        // 1b. Suspicious iframes
        $iframes = $xpath->query('//iframe');
        foreach ($iframes as $iframe) {
            $iframeSrc = $iframe->getAttribute('src');
            if ($iframeSrc && stripos($iframeSrc, $domain) === false) {
                // Check if iframe source is suspicious
                $suspicious = false;
                foreach ($this->suspiciousTlds as $tld) {
                    if (stripos($iframeSrc, $tld) !== false) { $suspicious = true; break; }
                }
                if ($suspicious || $iframe->getAttribute('width') === '0' || $iframe->getAttribute('height') === '0') {
                    $result->addIssue(
                        "Suspicious iframe detected: $iframeSrc",
                        'security', 20
                    );
                    $result->addPriorityFix("Remove suspicious iframe: $iframeSrc");
                    $malwareHits++;
                }
            }
            // Hidden iframe
            $style = $iframe->getAttribute('style');
            if (preg_match('/display\s*:\s*none|visibility\s*:\s*hidden|width\s*:\s*0|height\s*:\s*0/i', $style)) {
                $result->addIssue(
                    "Hidden iframe detected (possible malicious redirect)",
                    'security', 25
                );
                $result->addPriorityFix("Remove hidden iframe — possible security threat");
                $malwareHits++;
            }
        }

        // 1c. Meta refresh redirect
        $metas = $xpath->query('//meta[@http-equiv="refresh"]');
        foreach ($metas as $meta) {
            $content = $meta->getAttribute('content');
            if ($content && preg_match('/url=/i', $content)) {
                $result->addIssue(
                    "Meta refresh redirect detected — common phishing technique",
                    'security', 15
                );
                $result->addRecommendation("Replace meta refresh with proper server-side redirect (301/302)");
                $malwareHits++;
            }
        }

        // 1d. Base64-encoded links
        if (preg_match('/href\s*=\s*["\'][^"\']*base64[^"\']*["\']/i', $html)) {
            $result->addIssue(
                "Base64-encoded links detected — possible obfuscated redirect",
                'security', 20
            );
            $result->addPriorityFix("Replace base64-encoded links with clean URLs");
            $malwareHits++;
        }

        if ($malwareHits > 0) {
            $result->addRecommendation("Conduct full malware scan using VirusTotal or similar service");
        }
    }

    // ────────────────────────────────────────────────────────────
    // 2. Hidden Content Detection (Weight: 20%)
    // ────────────────────────────────────────────────────────────

    private function detectHiddenContent(\DOMDocument $dom, \DOMXPath $xpath, string $html, SpamResult $result): void
    {
        $hiddenHits = 0;

        // Scan all elements for hidden styles
        $allElements = $xpath->query('//*');
        foreach ($allElements as $el) {
            $style = strtolower($el->getAttribute('style'));
            $class = strtolower($el->getAttribute('class'));
            $text  = trim($el->textContent);

            if (empty($text)) continue;

            // 2a. display:none
            if (strpos($style, 'display:none') !== false || strpos($style, 'display: none') !== false) {
                if (strlen($text) > 10) {
                    $result->addIssue(
                        "Hidden text detected (display:none): \"" . mb_substr($text, 0, 60) . "...\"",
                        'hidden_content', 15
                    );
                    $hiddenHits++;
                }
            }

            // 2b. visibility:hidden
            if (strpos($style, 'visibility:hidden') !== false || strpos($style, 'visibility: hidden') !== false) {
                if (strlen($text) > 10) {
                    $result->addIssue(
                        "Hidden text detected (visibility:hidden): \"" . mb_substr($text, 0, 60) . "...\"",
                        'hidden_content', 15
                    );
                    $hiddenHits++;
                }
            }

            // 2c. opacity:0
            if (preg_match('/opacity\s*:\s*0/', $style)) {
                if (strlen($text) > 10) {
                    $result->addIssue(
                        "Invisible text detected (opacity:0): \"" . mb_substr($text, 0, 60) . "...\"",
                        'hidden_content', 10
                    );
                    $hiddenHits++;
                }
            }

            // 2d. font-size:0
            if (preg_match('/font-size\s*:\s*0/', $style)) {
                $result->addIssue(
                    "Zero-font-size text detected — hidden keyword stuffing technique",
                    'hidden_content', 12
                );
                $hiddenHits++;
            }

            // 2e. Text color matches background (same color)
            if (preg_match('/color\s*:\s*(#[0-9a-fA-F]{3,6}|rgb\([^)]+\))/i', $style, $cm) &&
                preg_match('/background(?:-color)?\s*:\s*(#[0-9a-fA-F]{3,6}|rgb\([^)]+\))/i', $style, $bm)) {
                if (strtolower($cm[1]) === strtolower($bm[1])) {
                    $result->addIssue(
                        "Text color matches background — invisible keyword stuffing: \"" . mb_substr($text, 0, 40) . "...\"",
                        'hidden_content', 20
                    );
                    $hiddenHits++;
                }
            }
        }

        // 2f. Check for CSS-hidden elements via <style> tags
        $styles = $xpath->query('//style');
        foreach ($styles as $styleEl) {
            $css = $styleEl->textContent;
            if (preg_match_all('/(\.\w+|\#\w+)\s*\{[^}]*display\s*:\s*none[^}]*\}/i', $css, $matches)) {
                if (count($matches[0]) > 3) {
                    $result->addIssue(
                        count($matches[0]) . " CSS rules use display:none — possible hidden content",
                        'hidden_content', 10
                    );
                    $hiddenHits++;
                    break;
                }
            }
        }

        // 2g. Check for text-indent: -9999px (old-school SEO spam)
        if (preg_match_all('/text-indent\s*:\s*-9999/', $html, $m) > 0) {
            $result->addIssue(
                "Off-screen text via text-indent:-9999px detected — classic SEO spam",
                'hidden_content', 15
            );
            $hiddenHits++;
        }

        if ($hiddenHits > 0) {
            $result->addPriorityFix("Remove all hidden/invisible text — violates search engine guidelines");
        }
    }

    // ────────────────────────────────────────────────────────────
    // 3. Content Spam Analysis (Weight: 15%)
    // ────────────────────────────────────────────────────────────

    private function analyzeContentSpam(string $bodyText, string $html, SpamResult $result): void
    {
        // 3a. Keyword stuffing detection
        $words = preg_split('/\s+/', mb_strtolower($bodyText));
        $words = array_filter($words, fn($w) => mb_strlen($w) > 2);
        $wordCount = count($words);
        $wordFreq = array_count_values($words);
        arsort($wordFreq);

        $topWords = array_slice($wordFreq, 0, 20, true);
        $stuffingDetected = false;
        foreach ($topWords as $word => $count) {
            if ($wordCount > 0 && $count > 5 && ($count / $wordCount) > 0.08) {
                $result->addIssue(
                    "Possible keyword stuffing: \"$word\" appears $count times (" . round($count / $wordCount * 100, 1) . "% density)",
                    'content', 10
                );
                $stuffingDetected = true;
            }
        }
        if ($stuffingDetected) {
            $result->addRecommendation("Reduce keyword density to natural levels (under 5% per keyword)");
        }

        // 3b. Thin content check
        $textLength = mb_strlen($bodyText);
        $htmlLength = strlen($html);
        $textRatio  = $htmlLength > 0 ? $textLength / $htmlLength : 0;

        if ($textLength < 300) {
            $result->addIssue(
                "Thin content: page contains only $textLength characters of text",
                'content', 15
            );
            $result->addRecommendation("Add at least 300 words of substantive content");
        } elseif ($textRatio < 0.05) {
            $result->addIssue(
                "Low text-to-HTML ratio (" . round($textRatio * 100, 1) . "%) — page may be over-templated",
                'content', 8
            );
            $result->addRecommendation("Increase content density relative to HTML markup");
        }

        // 3c. Repeated phrases / commercial spam patterns
        $phrases = preg_split('/[.!?]+/', $bodyText);
        $phraseFreq = [];
        foreach ($phrases as $p) {
            $p = trim(mb_strtolower($p));
            if (mb_strlen($p) > 20) {
                $phraseFreq[$p] = ($phraseFreq[$p] ?? 0) + 1;
            }
        }
        foreach ($phraseFreq as $phrase => $count) {
            if ($count > 4) {
                $result->addIssue(
                    "Repeated phrase detected ($count times): \"" . mb_substr($phrase, 0, 50) . "...\"",
                    'content', 10
                );
                break;
            }
        }
    }

    // ────────────────────────────────────────────────────────────
    // 4. Link Spam Analysis (Weight: 15%)
    // ────────────────────────────────────────────────────────────

    private function analyzeLinkSpam(\DOMDocument $dom, \DOMXPath $xpath, SpamResult $result): void
    {
        $links = $xpath->query('//a[@href]');
        $totalLinks = $links->length;
        $externalLinks = [];
        $suspiciousLinks = 0;

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $anchor = trim($link->textContent);

            // Count external links
            if (preg_match('#^https?://#i', $href)) {
                $linkHost = parse_url($href, PHP_URL_HOST) ?? '';
                $externalLinks[] = ['href' => $href, 'host' => $linkHost, 'anchor' => $anchor];

                // Check for suspicious TLDs
                foreach ($this->suspiciousTlds as $tld) {
                    if (stripos($linkHost, $tld) !== false) {
                        $suspiciousLinks++;
                        break;
                    }
                }

                // Check for spam-category domains
                foreach ($this->spamCategories as $cat => $keywords) {
                    foreach ($keywords as $kw) {
                        if (stripos($linkHost, $kw) !== false || stripos($href, $kw) !== false) {
                            $result->addIssue(
                                "Link to suspicious domain (\"$cat\"): $href",
                                'links', 15
                            );
                            $suspiciousLinks++;
                            break 2;
                        }
                    }
                }
            }
        }

        // 4a. Excessive outbound links
        $externalCount = count($externalLinks);
        if ($externalCount > 30) {
            $result->addIssue(
                "High number of outbound links ($externalCount) — possible link farm",
                'links', 12
            );
            $result->addRecommendation("Reduce outbound links; consider nofollow for non-essential links");
        }

        // 4b. Suspicious link ratio
        if ($totalLinks > 0 && $suspiciousLinks / $totalLinks > 0.3) {
            $result->addIssue(
                round($suspiciousLinks / $totalLinks * 100) . "% of links are suspicious — possible link spam",
                'links', 20
            );
            $result->addPriorityFix("Remove or nofollow suspicious outbound links");
        }

        // 4c. Check for nofollow on external links
        $externalNofollow = 0;
        foreach ($links as $link) {
            $rel = strtolower($link->getAttribute('rel'));
            $href = $link->getAttribute('href');
            if (preg_match('#^https?://#i', $href) && strpos($rel, 'nofollow') !== false) {
                $externalNofollow++;
            }
        }
        if ($externalCount > 10 && $externalNofollow === 0) {
            $result->addIssue(
                "All $externalCount outbound links are follow — risk of link scheme penalties",
                'links', 8
            );
            $result->addRecommendation("Add rel=\"nofollow\" to paid, sponsored, or untrusted outbound links");
        }
    }

    // ────────────────────────────────────────────────────────────
    // 5. Domain Reputation (Weight: 10%)
    // ────────────────────────────────────────────────────────────

    private function checkDomainReputation(string $url, array $headers, SpamResult $result): void
    {
        $domain = parse_url($url, PHP_URL_HOST) ?? '';

        // 5a. Suspicious TLD
        foreach ($this->suspiciousTlds as $tld) {
            if (stripos($domain, $tld) !== false) {
                $result->addIssue(
                    "Domain uses suspicious/free TLD: $tld — often associated with spam",
                    'domain', 20
                );
                $result->addRecommendation("Consider migrating from $tld to a trusted TLD (.com, .co.il, .org)");
                break;
            }
        }

        // 5b. Suspicious domain patterns (excessive hyphens, numbers)
        if (substr_count($domain, '-') > 2) {
            $result->addIssue(
                "Domain contains excessive hyphens — common spam pattern",
                'domain', 10
            );
        }
        if (preg_match('/\d{4,}/', $domain)) {
            $result->addIssue(
                "Domain contains long numeric sequence — suspicious pattern",
                'domain', 8
            );
        }

        // 5c. Very long domain
        if (strlen($domain) > 40) {
            $result->addIssue(
                "Unusually long domain name (" . strlen($domain) . " chars) — may be spam",
                'domain', 8
            );
        }

        // 5d. Check for blacklist indicators (basic heuristic)
        $spamKeywords = ['free', 'cheap', 'best', 'top', 'win', 'bonus', 'offer', 'deal', 'discount', 'viagra', 'casino'];
        $matchCount = 0;
        foreach ($spamKeywords as $kw) {
            if (stripos($domain, $kw) !== false) $matchCount++;
        }
        if ($matchCount >= 2) {
            $result->addIssue(
                "Domain name contains multiple spam-associated keywords — lower trust",
                'domain', 12
            );
        }
    }

    // ────────────────────────────────────────────────────────────
    // 6. Email Reputation & Security (Weight: 5%)
    // ────────────────────────────────────────────────────────────

    private function checkEmailSecurity(string $url, array $headers, SpamResult $result): void
    {
        $domain = parse_url($url, PHP_URL_HOST) ?? '';

        // Check for email security DNS records via headers
        // In a full implementation this would query DNS. Here we check headers
        // and provide recommendations.

        $hasSpf = false;
        $hasDkim = false;
        $hasDmarc = false;

        // Check headers for email security indicators
        foreach ($headers as $h) {
            if (!is_string($h)) continue;
            $hl = strtolower($h);
            if (strpos($hl, 'spf') !== false) $hasSpf = true;
            if (strpos($hl, 'dkim') !== false) $hasDkim = true;
            if (strpos($hl, 'dmarc') !== false) $hasDmarc = true;
        }

        if (!$hasSpf) {
            $result->addIssue(
                "SPF record not detected — domain vulnerable to email spoofing",
                'email', 5
            );
            $result->addRecommendation("Add SPF DNS record: v=spf1 include:_spf.example.com ~all");
        }
        if (!$hasDkim) {
            $result->addIssue(
                "DKIM not detected — outgoing emails may not be verified",
                'email', 4
            );
            $result->addRecommendation("Configure DKIM signing for your email provider");
        }
        if (!$hasDmarc) {
            $result->addIssue(
                "DMARC policy not detected — no protection against domain impersonation",
                'email', 5
            );
            $result->addRecommendation("Add DMARC DNS record: v=DMARC1; p=quarantine; rua=mailto:dmarc@$domain");
        }

        if (!$hasSpf && !$hasDkim && !$hasDmarc) {
            $result->addPriorityFix("Configure email authentication (SPF + DKIM + DMARC) to protect domain reputation");
        }
    }

    // ────────────────────────────────────────────────────────────
    // 7. Form Spam Protection (Weight: 5%)
    // ────────────────────────────────────────────────────────────

    private function checkFormProtection(\DOMDocument $dom, \DOMXPath $xpath, SpamResult $result): void
    {
        $forms = $xpath->query('//form');
        $formCount = $forms->length;

        if ($formCount === 0) {
            // No forms = no form spam risk
            return;
        }

        foreach ($forms as $form) {
            $hasCaptcha = $this->detectCaptcha($form);
            $hasHoneypot = $this->detectHoneypot($form);
            $hasValidation = $this->detectValidation($form);
            $hasCsrf = $this->detectCsrf($form, $xpath);

            if (!$hasCaptcha) {
                $result->addIssue(
                    "Form missing CAPTCHA/reCAPTCHA — vulnerable to automated spam submissions",
                    'form', 4
                );
                $result->addRecommendation("Add Google reCAPTCHA v3 or hCaptcha to prevent bot submissions");
            }
            if (!$hasHoneypot) {
                $result->addIssue(
                    "Form missing honeypot field — bots may auto-fill and submit",
                    'form', 3
                );
                $result->addRecommendation("Add a hidden honeypot field (e.g., style=\"display:none\") to trap bots");
            }
            if (!$hasValidation) {
                $result->addIssue(
                    "Form fields missing HTML5 validation attributes (required, type=email, pattern)",
                    'form', 2
                );
                $result->addRecommendation("Add HTML5 validation: required, type=\"email\", minlength, pattern attributes");
            }
            if (!$hasCsrf && $formCount > 0) {
                $result->addIssue(
                    "Form missing CSRF token — vulnerable to cross-site request forgery",
                    'form', 3
                );
                $result->addRecommendation("Implement CSRF token in all forms");
            }
        }
    }

    // ────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────

    private function extractBodyText(\DOMXPath $xpath): string
    {
        $body = '';
        foreach ($xpath->query('//body//text()') as $node) {
            $body .= ' ' . $node->textContent;
        }
        return trim(preg_replace('/\s+/', ' ', $body));
    }

    private function detectCaptcha(\DOMElement $form): bool
    {
        $formHtml = $form->ownerDocument->saveHTML($form);
        $captchaIndicators = ['recaptcha', 'g-recaptcha', 'h-captcha', 'hcaptcha', 'captcha',
                              'turnstile', 'data-sitekey', 'grecaptcha'];
        foreach ($captchaIndicators as $ind) {
            if (stripos($formHtml, $ind) !== false) return true;
        }
        return false;
    }

    private function detectHoneypot(\DOMElement $form): bool
    {
        // Look for hidden fields with common honeypot names
        $inputs = $form->getElementsByTagName('input');
        $honeypotNames = ['honeypot', 'website', 'url_field', 'botcheck', 'antispam',
                          'email2', 'confirm_email', 'hp_', '_hp', 'contact_me_by_fax'];
        foreach ($inputs as $input) {
            $name = strtolower($input->getAttribute('name'));
            $id   = strtolower($input->getAttribute('id'));
            $class = strtolower($input->getAttribute('class'));
            $style = strtolower($input->getAttribute('style'));

            // Named honeypot
            foreach ($honeypotNames as $hp) {
                if (stripos($name, $hp) !== false || stripos($id, $hp) !== false ||
                    stripos($class, $hp) !== false) return true;
            }
            // Hidden field with suspicious name
            if ((strpos($style, 'display:none') !== false || strpos($style, 'display: none') !== false ||
                 $input->getAttribute('type') === 'hidden') &&
                !in_array($name, ['csrf_token', '_token', 'csrf', 'authenticity_token', 'nonce'])) {
                return true;
            }
        }
        return false;
    }

    private function detectValidation(\DOMElement $form): bool
    {
        $inputs = $form->getElementsByTagName('input');
        foreach ($inputs as $input) {
            if ($input->hasAttribute('required')) return true;
            if ($input->getAttribute('type') === 'email') return true;
            if ($input->hasAttribute('pattern')) return true;
            if ($input->hasAttribute('minlength')) return true;
        }
        // Also check for JavaScript validation hints
        $formHtml = $form->ownerDocument->saveHTML($form);
        if (preg_match('/novalidate/i', $formHtml)) return false; // explicitly disabled
        return false;
    }

    private function detectCsrf(\DOMElement $form, \DOMXPath $xpath): bool
    {
        $inputs = $form->getElementsByTagName('input');
        $csrfNames = ['csrf_token', '_token', 'csrf', 'authenticity_token', '_csrf', 'nonce',
                      '__RequestVerificationToken', 'xsrf', '_wpnonce'];
        foreach ($inputs as $input) {
            if ($input->getAttribute('type') === 'hidden') {
                $name = strtolower($input->getAttribute('name'));
                foreach ($csrfNames as $cn) {
                    if (stripos($name, $cn) !== false) return true;
                }
            }
        }
        return false;
    }

    private function buildSummary(SpamResult $result): void
    {
        $spam = $result->spamScore();
        $level = $result->riskLevel();

        if ($level === 'LOW') {
            $result->summary = "Low spam risk detected ($spam/100). The site appears clean with no significant spam signals.";
        } elseif ($level === 'MEDIUM') {
            $result->summary = "Medium spam risk ($spam/100). Some spam indicators found — review and address flagged issues.";
        } elseif ($level === 'HIGH') {
            $result->summary = "High spam risk ($spam/100). Multiple spam signals detected — immediate action recommended.";
        } else {
            $result->summary = "Critical spam risk ($spam/100). The site shows severe spam indicators and may be penalized by search engines.";
        }
    }
}
