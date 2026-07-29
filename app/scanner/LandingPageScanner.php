<?php
namespace App\Scanner;

use App\Models\LandingPageResult;

/**
 * LandingPageScanner — the Landing Page Tester Agent.
 *
 * Performs 7-category analysis on any landing page:
 *   1. Render Check      — is the page visible?
 *   2. Technical Checks   — HTML, assets, meta, links
 *   3. Design & Layout    — hero, CTA, social proof, footer
 *   4. Mobile Responsiveness — viewport, touch targets, scaling
 *   5. Performance        — load indicators, image sizes, page weight
 *   6. UX & Conversion    — CTA placement, form simplicity, friction
 *   7. Content            — headlines, clarity, contact info
 *
 * All checks are heuristic (static HTML analysis). For a true
 * browser-rendered audit pair this with a headless-browser pipeline.
 */
class LandingPageScanner implements LandingPageScannerInterface
{
    private \DOMDocument $dom;
    private \DOMXPath $xpath;
    private string $html;
    private string $url;
    private array $headers;
    private LandingPageResult $result;

    // ---- Public API -------------------------------------------------

    public function scan(string $html, string $url, array $headers = []): LandingPageResult
    {
        $start = microtime(true);

        $this->html    = $html;
        $this->url     = $url;
        $this->headers = $headers;
        $this->result  = new LandingPageResult();

        // Parse DOM once
        $this->dom = new \DOMDocument();
        @$this->dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOWARNING | LIBXML_NOERROR);
        $this->xpath = new \DOMXPath($this->dom);

        // Run all 7 categories
        $this->checkRender();
        $this->checkTechnical();
        $this->checkDesign();
        $this->checkMobile();
        $this->checkPerformance();
        $this->checkUx();
        $this->checkContent();

        // Compute final score
        $this->result->computeFinalScore();
        $this->result->testTime = round(microtime(true) - $start, 2);
        $this->result->summary = $this->buildSummary();

        return $this->result;
    }

    // ================================================================
    // 1. RENDER CHECK
    // ================================================================
    private function checkRender(): void
    {
        $cat = 'Render';
        $score = 100;

        // 1a — body has content
        $bodyNodes = $this->xpath->query('//body//*');
        $bodyText  = trim((string) $this->dom->textContent);
        if ($bodyNodes->length === 0 || strlen($bodyText) < 30) {
            $this->result->addCheck($cat, 'Body has content', 'failed',
                'Body is empty or nearly empty — page may be blank.',
                'Ensure your HTML outputs visible content inside <body>.');
            $this->result->addIssue($cat, 'Page appears blank (no meaningful body content).', 'critical',
                'Add visible content (text, images) inside the <body> tag.');
            $score -= 40;
        } else {
            $this->result->addCheck($cat, 'Body has content', 'passed',
                "Body contains ~{$bodyNodes->length} elements, " . strlen($bodyText) . " chars of text.");
        }

        // 1b — CSS loaded
        $cssLinks = $this->xpath->query('//link[@rel="stylesheet"]');
        $styleTags = $this->xpath->query('//style');
        if ($cssLinks->length === 0 && $styleTags->length === 0) {
            $this->result->addCheck($cat, 'CSS loaded', 'warning',
                'No stylesheets or <style> blocks detected.',
                'Add at least one CSS file or <style> block for visual styling.');
            $score -= 15;
        } else {
            $this->result->addCheck($cat, 'CSS loaded', 'passed',
                "{$cssLinks->length} stylesheet(s), {$styleTags->length} inline style block(s).");
        }

        // 1c — inline JS error patterns (heuristic)
        $inlineScripts = $this->xpath->query('//script[not(@src)]');
        $jsErrorPatterns = 0;
        foreach ($inlineScripts as $s) {
            $code = $s->textContent;
            // Look for common error-prone patterns
            if (preg_match('/addEventListener\s*\(/', $code) && !preg_match('/DOMContentLoaded|defer|async/', $code)) {
                $jsErrorPatterns++;
            }
        }
        if ($jsErrorPatterns > 0) {
            $this->result->addCheck($cat, 'JS error-prone patterns', 'warning',
                "$jsErrorPatterns inline script(s) use addEventListener without DOMContentLoaded guard.",
                'Wrap DOM-dependent JS in DOMContentLoaded or use defer/async on script tags.');
            $score -= 10;
        } else {
            $this->result->addCheck($cat, 'JS error-prone patterns', 'passed',
                'No obvious JS error patterns found.');
        }

        // 1d — no visible "error" or "undefined" text (heuristic)
        if (preg_match('/(undefined is not a function|cannot read property|uncaught typeerror|fatal error)/i', $this->html)) {
            $this->result->addCheck($cat, 'No visible error messages', 'failed',
                'Error-like text found in page output.',
                'Check your JavaScript console for runtime errors.');
            $this->result->addIssue($cat, 'JavaScript error text visible on page.', 'critical',
                'Fix the JavaScript error — check browser console for details.');
            $score -= 25;
        } else {
            $this->result->addCheck($cat, 'No visible error messages', 'passed');
        }

        $this->result->renderScore = max(0, $score);
    }

    // ================================================================
    // 2. TECHNICAL CHECKS
    // ================================================================
    private function checkTechnical(): void
    {
        $cat = 'Technical';
        $score = 100;

        // 2a — HTML validation (DOM parse success)
        libxml_use_internal_errors(true);
        $testDom = new \DOMDocument();
        $testDom->loadHTML(mb_convert_encoding($this->html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOWARNING | LIBXML_NOERROR);
        $xmlErrors = libxml_get_errors();
        libxml_clear_errors();
        $errorCount = count($xmlErrors);
        if ($errorCount > 10) {
            $this->result->addCheck($cat, 'HTML structure valid', 'warning',
                "$errorCount HTML parse warnings — review tag nesting/closing.",
                'Fix unclosed tags and invalid HTML structure.');
            $score -= 15;
        } else {
            $this->result->addCheck($cat, 'HTML structure valid', 'passed',
                "$errorCount minor parse notes (acceptable).");
        }

        // 2b — images load check
        $images = $this->xpath->query('//img');
        $brokenImgs = 0;
        $imgDetails = [];
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            if ($src && $this->looksExternal($src)) {
                $imgDetails[] = $src;
                // We can't verify remotely here, flag for review
            }
            if (empty($src) || $src === '#' || $src === '/') {
                $brokenImgs++;
            }
        }
        if ($brokenImgs > 0) {
            $this->result->addCheck($cat, 'Images have valid src', 'warning',
                "$brokenImgs image(s) have empty/placeholder src.",
                'Set valid image URLs — empty src triggers extra requests.');
            $score -= 10;
        } else {
            $this->result->addCheck($cat, 'Images have valid src', 'passed',
                $images->length > 0 ? "{$images->length} image(s) with valid src." : 'No images on page.');
        }

        // 2c — CSS files load (check link hrefs)
        $cssLinks = $this->xpath->query('//link[@rel="stylesheet"]');
        $cssCount = $cssLinks->length;
        $brokenCss = 0;
        foreach ($cssLinks as $link) {
            $href = $link->getAttribute('href');
            if (empty($href) || $href === '#') $brokenCss++;
        }
        if ($brokenCss > 0) {
            $this->result->addCheck($cat, 'CSS files load', 'warning',
                "$brokenCss stylesheet(s) have empty href.");
            $score -= 8;
        } else {
            $this->result->addCheck($cat, 'CSS files load', 'passed',
                $cssCount > 0 ? "$cssCount stylesheet(s) referenced." : 'No external stylesheets.');
        }

        // 2d — JS files load
        $scripts = $this->xpath->query('//script[@src]');
        $brokenJs = 0;
        foreach ($scripts as $s) {
            $src = $s->getAttribute('src');
            if (empty($src) || $src === '#') $brokenJs++;
        }
        if ($brokenJs > 0) {
            $this->result->addCheck($cat, 'JS files load', 'warning',
                "$brokenJs script(s) have empty src.");
            $score -= 8;
        } else {
            $this->result->addCheck($cat, 'JS files load', 'passed',
                $scripts->length > 0 ? "{$scripts->length} external script(s)." : 'No external scripts.');
        }

        // 2e — Links (heuristic: check for #, javascript:void(0))
        $anchors = $this->xpath->query('//a');
        $deadLinks = 0;
        foreach ($anchors as $a) {
            $href = $a->getAttribute('href');
            if ($href === '#' || $href === 'javascript:void(0)' || $href === 'javascript:;') {
                $deadLinks++;
            }
        }
        if ($deadLinks > 3) {
            $this->result->addCheck($cat, 'No broken/placeholder links', 'warning',
                "$deadLinks link(s) use # or javascript:void(0) — may confuse users.",
                'Replace placeholder links with real URLs or remove them.');
            $score -= 8;
        } else {
            $this->result->addCheck($cat, 'No broken/placeholder links', 'passed',
                $anchors->length > 0 ? "{$anchors->length} link(s), $deadLinks placeholder(s)." : 'No links.');
        }

        // 2f — Meta tags
        $titleNodes = $this->xpath->query('//title');
        $hasTitle = $titleNodes->length > 0 && trim((string) $titleNodes->item(0)->textContent) !== '';
        $metaDesc = $this->xpath->query('//meta[@name="description"]');
        $hasDesc = $metaDesc->length > 0 && $metaDesc->item(0)->getAttribute('content') !== '';
        $hasViewport = $this->xpath->query('//meta[@name="viewport"]')->length > 0;

        $metaStatus = [];
        if ($hasTitle) $metaStatus[] = '✅ title';
        else { $metaStatus[] = '❌ title missing'; $score -= 15; }
        if ($hasDesc) $metaStatus[] = '✅ description';
        else { $metaStatus[] = '❌ description missing'; $score -= 10; }
        if ($hasViewport) $metaStatus[] = '✅ viewport';
        else { $metaStatus[] = '❌ viewport missing'; $score -= 15; }

        $hasOpenGraph = $this->xpath->query('//meta[starts-with(@property,"og:")]')->length > 0;
        if (!$hasOpenGraph) {
            $metaStatus[] = '⚠️ no Open Graph tags';
            $score -= 5;
        } else {
            $metaStatus[] = '✅ Open Graph';
        }

        $this->result->addCheck($cat, 'Meta tags (title, desc, viewport)', 
            ($hasTitle && $hasDesc && $hasViewport) ? 'passed' : 'failed',
            implode(', ', $metaStatus),
            'Every page must have <title>, <meta name="description">, and <meta name="viewport">.'
        );
        if (!$hasTitle || !$hasDesc || !$hasViewport) {
            $this->result->addIssue($cat, 'Critical meta tags missing.', 
                (!$hasTitle || !$hasViewport) ? 'critical' : 'important',
                'Add missing <title>, meta description, and viewport meta tag.'
            );
        }

        // 2g — RTL support (if Hebrew detected)
        $htmlTag = $this->xpath->query('//html')->item(0);
        $lang = $htmlTag ? $htmlTag->getAttribute('lang') : '';
        $dir  = $htmlTag ? $htmlTag->getAttribute('dir') : '';

        $isHebrew = (stripos($lang, 'he') !== false) ||
                    (preg_match('/[\x{0590}-\x{05FF}]/u', $this->html));
        if ($isHebrew) {
            if (strtolower($dir) === 'rtl') {
                $this->result->addCheck($cat, 'RTL support (Hebrew)', 'passed',
                    "dir=\"rtl\" set on <html>, lang=\"$lang\".");
            } else {
                $this->result->addCheck($cat, 'RTL support (Hebrew)', 'warning',
                    'Hebrew content detected but dir="rtl" is missing on <html>.',
                    'Add dir="rtl" and lang="he" to the <html> tag.');
                $score -= 10;
                $this->result->addIssue($cat, 'Hebrew page missing RTL direction.', 'important',
                    'Set dir="rtl" and lang="he" on <html>.');
            }
        } else {
            $this->result->addCheck($cat, 'RTL support (Hebrew)', 'passed', 'Not a Hebrew page — RTL not required.');
        }

        // 2h — Charset
        $metaCharset = $this->xpath->query('//meta[@charset]');
        $metaContentType = $this->xpath->query('//meta[@http-equiv="Content-Type"]');
        $hasCharset = $metaCharset->length > 0 || $metaContentType->length > 0;
        if ($hasCharset) {
            $this->result->addCheck($cat, 'Charset declared (UTF-8)', 'passed',
                'Character encoding explicitly declared.');
        } else {
            $this->result->addCheck($cat, 'Charset declared (UTF-8)', 'warning',
                'No charset meta tag found — browser may guess encoding.',
                'Add <meta charset="UTF-8"> in <head>.');
            $score -= 8;
        }

        $this->result->technicalScore = max(0, $score);
    }

    // ================================================================
    // 3. DESIGN & LAYOUT
    // ================================================================
    private function checkDesign(): void
    {
        $cat = 'Design';
        $score = 100;

        // 3a — Hero section with headline
        $h1 = $this->xpath->query('//h1');
        $hasHeroHeadline = $h1->length > 0;
        // Heuristic: check if first section has big text / h1
        $firstSection = $this->xpath->query('(//section|//header|//div[contains(@class,"hero") or contains(@class,"banner")])[1]');
        $hasHeroSection = $firstSection->length > 0;

        if ($hasHeroHeadline) {
            $this->result->addCheck($cat, 'Hero section with headline', 'passed',
                'H1 found: "' . mb_substr(trim($h1->item(0)->textContent), 0, 60) . '"...');
        } elseif ($hasHeroSection) {
            $this->result->addCheck($cat, 'Hero section with headline', 'warning',
                'Hero-like section found but no H1 heading.',
                'Add a clear H1 headline inside your hero section.');
            $score -= 12;
            $this->result->addIssue($cat, 'Hero section missing H1 headline.', 'important',
                'Add an H1 with your main value proposition inside the hero.');
        } else {
            $this->result->addCheck($cat, 'Hero section with headline', 'failed',
                'No hero section or H1 heading detected.',
                'Add a hero section with a compelling H1 headline.');
            $score -= 20;
            $this->result->addIssue($cat, 'No hero section or headline found.', 'critical',
                'Create a hero section (first screen) with a clear H1 headline.');
        }

        // 3b — CTA button visible and clear
        $ctaButtons = $this->xpath->query(
            '//a[contains(@class,"btn") or contains(@class,"button") or contains(@class,"cta")]|' .
            '//button[contains(@class,"btn") or contains(@class,"button") or contains(@class,"cta")]|' .
            '//a[contains(text(),"הרשמה") or contains(text(),"התחל") or contains(text(),"צור") or ' .
            'contains(text(),"Sign Up") or contains(text(),"Get Started") or contains(text(),"Contact")]'
        );
        if ($ctaButtons->length > 0) {
            $this->result->addCheck($cat, 'CTA button visible', 'passed',
                "{$ctaButtons->length} CTA button(s) detected.");
        } else {
            $this->result->addCheck($cat, 'CTA button visible', 'failed',
                'No clear CTA button found.',
                'Add a prominent CTA button (e.g., "Get Started", "Contact Us").');
            $score -= 18;
            $this->result->addIssue($cat, 'No call-to-action button on page.', 'critical',
                'Add a visible, action-oriented CTA button above the fold.');
        }

        // 3c — Features section (3-4 items)
        // Heuristic: look for grid of cards with icon + heading + text
        $featureCards = $this->xpath->query(
            '//*[contains(@class,"feature") or contains(@class,"service") or contains(@class,"benefit")]'
        );
        // Also check for sections with multiple similar child divs
        $featureContainers = $this->xpath->query(
            '//section[.//h3 or .//h4][count(.//div) >= 3]|' .
            '//div[contains(@class,"grid") or contains(@class,"row")][count(.//div) >= 3]'
        );
        $hasFeatures = $featureCards->length >= 3 || $featureContainers->length > 0;

        if ($hasFeatures) {
            $this->result->addCheck($cat, 'Features/benefits section', 'passed',
                'Features section with multiple items detected.');
        } else {
            $this->result->addCheck($cat, 'Features/benefits section', 'warning',
                'No clear features/benefits section with 3+ items.',
                'Add a section showcasing 3-4 key features or benefits.');
            $score -= 10;
            $this->result->addIssue($cat, 'Missing features/benefits section.', 'important',
                'Add 3-4 feature cards highlighting your value.');
        }

        // 3d — Social proof (testimonials, logos, stats)
        $testimonials = $this->xpath->query(
            '//*[contains(@class,"testimonial") or contains(@class,"review") or contains(@class,"quote")]'
        );
        $logoCloud = $this->xpath->query('//*[contains(@class,"logo") and contains(@class,"partner")]');
        $statsSection = $this->xpath->query(
            '//*[contains(@class,"stat") or contains(@class,"counter") or contains(@class,"number")]'
        );
        $hasSocialProof = $testimonials->length > 0 || $logoCloud->length > 0 || $statsSection->length > 0;

        if ($hasSocialProof) {
            $this->result->addCheck($cat, 'Social proof (testimonials/logos/stats)', 'passed',
                'Social proof elements detected.');
        } else {
            $this->result->addCheck($cat, 'Social proof (testimonials/logos/stats)', 'warning',
                'No testimonials, client logos, or stats found.',
                'Add social proof: testimonials, client logos, or statistics.');
            $score -= 8;
            $this->result->addIssue($cat, 'No social proof on landing page.', 'important',
                'Add testimonials, client logos, or success metrics.');
        }

        // 3e — Contact form
        $forms = $this->xpath->query('//form');
        if ($forms->length > 0) {
            $this->result->addCheck($cat, 'Contact form exists', 'passed',
                "{$forms->length} form(s) found.");
        } else {
            $this->result->addCheck($cat, 'Contact form exists', 'warning',
                'No contact form found — users have no way to reach you.',
                'Consider adding a simple contact form.');
            $score -= 5;
        }

        // 3f — Footer with basic info
        $footer = $this->xpath->query('//footer');
        $hasFooter = $footer->length > 0;
        if ($hasFooter) {
            $footerText = trim((string) $footer->item(0)->textContent);
            $this->result->addCheck($cat, 'Footer with basic info', 'passed',
                'Footer present with ' . strlen($footerText) . ' chars of content.');
        } else {
            $this->result->addCheck($cat, 'Footer with basic info', 'failed',
                'No <footer> element found.',
                'Add a footer with copyright, links, and contact info.');
            $score -= 12;
            $this->result->addIssue($cat, 'Footer missing.', 'important',
                'Add a <footer> with copyright, legal links, and contact info.');
        }

        // 3g — Consistent colors (heuristic: check for CSS variables or too many unique colors)
        preg_match_all('/#[0-9a-fA-F]{3,6}|rgb\([^)]+\)/i', $this->html, $colorMatches);
        $uniqueColors = count(array_unique($colorMatches[0] ?? []));
        if ($uniqueColors > 15) {
            $this->result->addCheck($cat, 'Consistent color palette', 'warning',
                "$uniqueColors unique colors detected — may look inconsistent.",
                'Limit your palette to 3-5 primary colors for a cohesive design.');
            $score -= 8;
        } elseif ($uniqueColors > 0) {
            $this->result->addCheck($cat, 'Consistent color palette', 'passed',
                "$uniqueColors unique colors — looks cohesive.");
        } else {
            $this->result->addCheck($cat, 'Consistent color palette', 'warning',
                'No color definitions found — page may be unstyled.');
            $score -= 5;
        }

        // 3h — Spacing / whitespace (heuristic: check for padding/margin CSS)
        $hasSpacing = preg_match('/(padding|margin)\s*:/i', $this->html) === 1;
        if ($hasSpacing) {
            $this->result->addCheck($cat, 'Proper spacing and whitespace', 'passed',
                'Padding/margin rules detected in CSS.');
        } else {
            $this->result->addCheck($cat, 'Proper spacing and whitespace', 'warning',
                'No spacing rules detected — content may appear cramped.',
                'Add CSS padding and margins for breathing room.');
            $score -= 5;
        }

        $this->result->designScore = max(0, $score);
    }

    // ================================================================
    // 4. MOBILE RESPONSIVENESS
    // ================================================================
    private function checkMobile(): void
    {
        $cat = 'Mobile';
        $score = 100;

        // 4a — Viewport meta
        $viewport = $this->xpath->query('//meta[@name="viewport"]');
        if ($viewport->length > 0) {
            $content = $viewport->item(0)->getAttribute('content');
            $hasWidthDevice = strpos($content, 'width=device-width') !== false;
            $hasInitialScale = strpos($content, 'initial-scale') !== false;
            if ($hasWidthDevice && $hasInitialScale) {
                $this->result->addCheck($cat, 'Viewport meta tag', 'passed',
                    'width=device-width + initial-scale set.');
            } else {
                $this->result->addCheck($cat, 'Viewport meta tag', 'warning',
                    'Viewport present but may be misconfigured.',
                    'Use <meta name="viewport" content="width=device-width, initial-scale=1.0">');
                $score -= 10;
            }
        } else {
            $this->result->addCheck($cat, 'Viewport meta tag', 'failed',
                'No viewport meta tag — page will not scale on mobile.',
                'Add <meta name="viewport" content="width=device-width, initial-scale=1.0">');
            $score -= 25;
            $this->result->addIssue($cat, 'Missing viewport meta tag.', 'critical',
                'Add viewport meta tag for mobile responsiveness.');
        }

        // 4b — Responsive CSS (media queries, flexbox, grid)
        $hasMediaQueries = preg_match('/@media\s*\(/i', $this->html) === 1;
        $hasFlexbox = preg_match('/(display\s*:\s*flex|display\s*:\s*grid)/i', $this->html) === 1;
        $hasMaxWidth = preg_match('/max-width\s*:/i', $this->html) === 1;

        $respScore = ($hasMediaQueries ? 1 : 0) + ($hasFlexbox ? 1 : 0) + ($hasMaxWidth ? 1 : 0);
        if ($respScore >= 2) {
            $this->result->addCheck($cat, 'Responsive CSS patterns', 'passed',
                ($hasMediaQueries ? 'media queries, ' : '') .
                ($hasFlexbox ? 'flexbox/grid, ' : '') .
                ($hasMaxWidth ? 'max-width' : '') . ' detected.');
        } elseif ($respScore === 1) {
            $this->result->addCheck($cat, 'Responsive CSS patterns', 'warning',
                'Only one responsive pattern found.',
                'Add media queries and flexbox/grid for proper responsiveness.');
            $score -= 10;
        } else {
            $this->result->addCheck($cat, 'Responsive CSS patterns', 'failed',
                'No responsive CSS patterns detected (no media queries, flexbox, or max-width).',
                'Implement responsive design with media queries, flexbox, and max-width constraints.');
            $score -= 20;
            $this->result->addIssue($cat, 'Page is not responsive.', 'critical',
                'Add CSS media queries and use flexbox/grid for layout.');
        }

        // 4c — Button tappability (min 44px heuristic)
        $buttons = $this->xpath->query('//button|//a[contains(@class,"btn") or contains(@class,"button")]|//input[@type="submit"]');
        $smallButtons = 0;
        foreach ($buttons as $btn) {
            $style = strtolower($btn->getAttribute('style'));
            // Check inline styles for small height/padding
            if (preg_match('/(?:height|min-height|padding)\s*:\s*(\d+)px/', $style, $m)) {
                if ((int) $m[1] < 44) $smallButtons++;
            }
            // Check classes that might indicate small buttons
            $cls = strtolower($btn->getAttribute('class'));
            if (preg_match('/(?:btn-xs|btn-sm|small)/', $cls)) $smallButtons++;
        }
        if ($smallButtons > 0) {
            $this->result->addCheck($cat, 'Buttons tappable (min 44px)', 'warning',
                "$smallButtons button(s) may be too small for touch.",
                'Ensure all buttons and interactive elements are at least 44×44px.');
            $score -= 8;
        } else {
            $this->result->addCheck($cat, 'Buttons tappable (min 44px)', 'passed',
                $buttons->length > 0 ? "{$buttons->length} button(s) — no obvious size issues." : 'No buttons to check.');
        }

        // 4d — Images scale properly
        $hasImgMaxWidth = preg_match('/img\s*{[^}]*max-width\s*:\s*100%/i', $this->html) === 1 ||
                          preg_match('/img[^>]+style="[^"]*max-width\s*:\s*100%/i', $this->html) === 1 ||
                          preg_match('/img-fluid|img-responsive/', $this->html) === 1;
        $imgCount = $this->xpath->query('//img')->length;
        if ($hasImgMaxWidth || $imgCount === 0) {
            $this->result->addCheck($cat, 'Images scale properly', 'passed',
                $hasImgMaxWidth ? 'max-width:100% or responsive image class detected.' : 'No images to check.');
        } else {
            $this->result->addCheck($cat, 'Images scale properly', 'warning',
                'Images may not scale on mobile — no max-width:100% detected.',
                'Add max-width:100% to images or use responsive image classes.');
            $score -= 8;
        }

        // 4e — Text readability (font-size heuristics)
        $hasSmallFont = preg_match('/font-size\s*:\s*(?:[0-9]|1[0-1])px/', $this->html) === 1;
        if ($hasSmallFont) {
            $this->result->addCheck($cat, 'Text readable on mobile', 'warning',
                'Very small font sizes detected (<12px) — hard to read on mobile.',
                'Use minimum 16px body text for mobile readability.');
            $score -= 8;
        } else {
            $this->result->addCheck($cat, 'Text readable on mobile', 'passed',
                'No extremely small fonts detected.');
        }

        $this->result->mobileScore = max(0, $score);
    }

    // ================================================================
    // 5. PERFORMANCE
    // ================================================================
    private function checkPerformance(): void
    {
        $cat = 'Performance';
        $score = 100;

        $pageSizeKb = round(strlen($this->html) / 1024, 1);

        // 5a — Page size
        if ($pageSizeKb > 1000) {
            $this->result->addCheck($cat, 'Page size (<500KB)', 'failed',
                "Page HTML is {$pageSizeKb}KB — too heavy.",
                'Minify HTML, CSS, and JS to reduce page weight below 500KB.');
            $score -= 20;
            $this->result->addIssue($cat, "Page too large ({$pageSizeKb}KB).", 'critical',
                'Minify and compress assets to bring page under 500KB.');
        } elseif ($pageSizeKb > 500) {
            $this->result->addCheck($cat, 'Page size (<500KB)', 'warning',
                "Page HTML is {$pageSizeKb}KB — above optimal.",
                'Consider reducing page size below 500KB.');
            $score -= 10;
        } else {
            $this->result->addCheck($cat, 'Page size (<500KB)', 'passed',
                "{$pageSizeKb}KB — acceptable.");
        }

        // 5b — Compression
        $hasCompression = false;
        foreach ($this->headers as $key => $value) {
            $k = strtolower(is_int($key) ? $value : $key);
            if (strpos($k, 'content-encoding') !== false) {
                $hasCompression = true;
                break;
            }
        }
        if ($hasCompression) {
            $this->result->addCheck($cat, 'Compression (gzip/brotli)', 'passed',
                'Content encoding detected.');
        } else {
            $this->result->addCheck($cat, 'Compression (gzip/brotli)', 'warning',
                'No compression detected — enable gzip or brotli.',
                'Enable gzip or brotli compression on your web server.');
            $score -= 12;
            $this->result->addIssue($cat, 'Compression not enabled.', 'important',
                'Enable gzip or brotli compression in server config.');
        }

        // 5c — Caching headers
        $hasCaching = false;
        $cacheHeaders = ['cache-control', 'expires', 'etag', 'last-modified'];
        foreach ($this->headers as $key => $value) {
            $k = strtolower(is_int($key) ? $value : $key);
            foreach ($cacheHeaders as $ch) {
                if (strpos($k, $ch) !== false) {
                    $hasCaching = true;
                    break 2;
                }
            }
        }
        if ($hasCaching) {
            $this->result->addCheck($cat, 'Browser caching headers', 'passed',
                'Cache headers detected.');
        } else {
            $this->result->addCheck($cat, 'Browser caching headers', 'warning',
                'No cache headers — assets will reload every visit.',
                'Set Cache-Control headers for static assets.');
            $score -= 8;
            $this->result->addIssue($cat, 'No browser caching configured.', 'important',
                'Add Cache-Control and ETag headers.');
        }

        // 5d — Images optimized (check for large inline images, missing width/height)
        $imgCount = 0;
        $unoptimized = 0;
        foreach ($this->xpath->query('//img') as $img) {
            $imgCount++;
            $w = $img->getAttribute('width');
            $h = $img->getAttribute('height');
            if (empty($w) || empty($h)) $unoptimized++;
        }
        if ($unoptimized > 0) {
            $this->result->addCheck($cat, 'Images have width/height', 'warning',
                "$unoptimized/$imgCount image(s) missing width/height — layout shift risk.",
                'Add explicit width and height attributes to all <img> tags.');
            $score -= min(10, $unoptimized * 3);
        } else {
            $this->result->addCheck($cat, 'Images have width/height', 'passed',
                $imgCount > 0 ? "$imgCount image(s) — all have dimensions." : 'No images.');
        }

        // 5e — Render-blocking resources
        $blockingScripts = $this->xpath->query('//script[@src and not(@async) and not(@defer)]');
        $blockingStyles = $this->xpath->query('//link[@rel="stylesheet"][not(@media="print")]');
        $blockingCount = $blockingScripts->length + $blockingStyles->length;
        if ($blockingCount > 4) {
            $this->result->addCheck($cat, 'Render-blocking resources', 'warning',
                "$blockingCount render-blocking resources — may slow first paint.",
                'Add async/defer to scripts and inline critical CSS.');
            $score -= 10;
        } else {
            $this->result->addCheck($cat, 'Render-blocking resources', 'passed',
                "$blockingCount blocking resource(s) — acceptable.");
        }

        // 5f — Total HTTP requests estimate
        $httpCount = $this->countHttpRequests();
        if ($httpCount > 50) {
            $this->result->addCheck($cat, 'HTTP request count (<50)', 'warning',
                "$httpCount estimated requests — consider bundling.",
                'Bundle CSS/JS and use image sprites to reduce requests.');
            $score -= 10;
        } else {
            $this->result->addCheck($cat, 'HTTP request count (<50)', 'passed',
                "$httpCount estimated request(s).");
        }

        // Store raw data
        $this->result->raw['page_size_kb'] = $pageSizeKb;
        $this->result->raw['http_requests'] = $httpCount;
        $this->result->raw['has_compression'] = $hasCompression;
        $this->result->raw['has_caching'] = $hasCaching;

        $this->result->performanceScore = max(0, $score);
    }

    // ================================================================
    // 6. UX & CONVERSION
    // ================================================================
    private function checkUx(): void
    {
        $cat = 'UX & Conversion';
        $score = 100;

        // 6a — CTA above the fold (heuristic: CTA within first ~20 elements of body)
        $bodyChildren = $this->xpath->query('//body/*');
        $ctaAboveFold = false;
        $maxFold = min(20, $bodyChildren->length);
        for ($i = 0; $i < $maxFold; $i++) {
            $node = $bodyChildren->item($i);
            if (!$node) continue;
            $html = strtolower($this->dom->saveHTML($node));
            if (preg_match('/(btn|button|cta|get.start|sign.up|contact|הרשמה|התחל|צור.קשר)/i', $html)) {
                $ctaAboveFold = true;
                break;
            }
        }
        if ($ctaAboveFold) {
            $this->result->addCheck($cat, 'CTA above the fold', 'passed',
                'CTA button found in first ~20 body elements.');
        } else {
            $this->result->addCheck($cat, 'CTA above the fold', 'failed',
                'No CTA found near the top of the page.',
                'Place your primary CTA button in the hero section (first screen).');
            $score -= 20;
            $this->result->addIssue($cat, 'CTA not above the fold.', 'critical',
                'Move primary CTA button to the hero section so it is visible without scrolling.');
        }

        // 6b — CTA text is action-oriented
        $ctaTexts = [];
        foreach ($this->xpath->query('//a[contains(@class,"btn") or contains(@class,"cta")]|//button') as $btn) {
            $ctaTexts[] = trim((string) $btn->textContent);
        }
        $actionWords = ['get', 'start', 'sign', 'join', 'try', 'buy', 'contact', 'download', 'book', 'register',
            'הרשמה', 'התחל', 'צור', 'קנה', 'הורד', 'הצטרף', 'נסה', 'צור קשר', 'הזמן'];
        $hasActionCta = false;
        foreach ($ctaTexts as $text) {
            foreach ($actionWords as $word) {
                if (stripos($text, $word) !== false) {
                    $hasActionCta = true;
                    break 2;
                }
            }
        }
        if ($hasActionCta) {
            $this->result->addCheck($cat, 'CTA text is action-oriented', 'passed',
                'CTA uses action verbs.');
        } elseif (count($ctaTexts) > 0) {
            $this->result->addCheck($cat, 'CTA text is action-oriented', 'warning',
                'CTA text may not be action-oriented enough.',
                'Use action verbs: "Get Started", "Sign Up Free", "Book a Demo".');
            $score -= 10;
        } else {
            $this->result->addCheck($cat, 'CTA text is action-oriented', 'failed',
                'No CTA buttons with text found.');
            $score -= 15;
        }

        // 6c — Benefits clearly stated
        $benefitKeywords = ['benefit', 'feature', 'why', 'advantage', 'save', 'grow', 'increase',
            'יתרון', 'תועלת', 'למה', 'חסוך', 'הגדל', 'שפר'];
        $hasBenefits = false;
        foreach ($benefitKeywords as $kw) {
            if (stripos($this->html, $kw) !== false) {
                $hasBenefits = true;
                break;
            }
        }
        if ($hasBenefits) {
            $this->result->addCheck($cat, 'Benefits clearly stated', 'passed',
                'Benefit-oriented language detected.');
        } else {
            $this->result->addCheck($cat, 'Benefits clearly stated', 'warning',
                'No clear benefit statements found.',
                'Add clear benefit statements — explain what users gain.');
            $score -= 10;
        }

        // 6d — Social proof visible (re-check with UX lens)
        $hasSocialElements = $this->xpath->query(
            '//*[contains(@class,"testimonial") or contains(@class,"review") or ' .
            'contains(@class,"logo") or contains(@class,"stat") or ' .
            'contains(text(),"client") or contains(text(),"לקוח")]'
        )->length > 0;
        if ($hasSocialElements) {
            $this->result->addCheck($cat, 'Social proof visible', 'passed');
        } else {
            $this->result->addCheck($cat, 'Social proof visible', 'warning',
                'No social proof detected — visitors may lack trust.',
                'Add testimonials, client logos, or success statistics.');
            $score -= 8;
        }

        // 6e — Form simplicity (max 4 visible fields)
        $formInputs = $this->xpath->query(
            '//form//input[not(@type="hidden") and not(@type="submit") and not(@type="button")]|' .
            '//form//textarea|//form//select'
        );
        $formFieldCount = $formInputs->length;
        if ($formFieldCount === 0) {
            $this->result->addCheck($cat, 'Form field count (≤4)', 'passed',
                'No form, or no visible fields — N/A.');
        } elseif ($formFieldCount <= 4) {
            $this->result->addCheck($cat, 'Form field count (≤4)', 'passed',
                "$formFieldCount field(s) — simple and conversion-friendly.");
        } else {
            $this->result->addCheck($cat, 'Form field count (≤4)', 'warning',
                "$formFieldCount fields — may reduce conversions.",
                'Reduce form to 4 fields max for better conversion rate.');
            $score -= 8;
            $this->result->addIssue($cat, "Form has $formFieldCount fields (recommend ≤4).", 'important',
                'Reduce form fields to 4 or fewer for optimal conversions.');
        }

        // 6f — Clear path to conversion (CTA repeated, not just one)
        $ctaCount = $this->xpath->query(
            '//a[contains(@class,"btn") or contains(@class,"cta") or contains(@class,"button")]|' .
            '//button[contains(@class,"btn") or contains(@class,"cta")]|' .
            '//a[contains(@href,"sign") or contains(@href,"register") or contains(@href,"contact")]'
        )->length;
        if ($ctaCount >= 2) {
            $this->result->addCheck($cat, 'Clear path to conversion', 'passed',
                "$ctaCount CTA touchpoints — user always has next step.");
        } elseif ($ctaCount === 1) {
            $this->result->addCheck($cat, 'Clear path to conversion', 'warning',
                'Only one CTA — consider adding a secondary CTA.',
                'Add a secondary CTA lower on the page for scrollers.');
            $score -= 5;
        } else {
            $this->result->addCheck($cat, 'Clear path to conversion', 'failed',
                'No conversion path — users have nowhere to go.',
                'Add at least one clear CTA button.');
            $score -= 15;
        }

        // 6g — No friction points (popups, auto-play, excessive ads)
        $hasPopup = preg_match('/(popup|modal.*display|overlay.*visible)/i', $this->html) === 1;
        $hasAutoPlay = preg_match('/autoplay/i', $this->html) === 1;
        if ($hasPopup || $hasAutoPlay) {
            $frictions = [];
            if ($hasPopup) $frictions[] = 'popup/modal';
            if ($hasAutoPlay) $frictions[] = 'autoplay media';
            $this->result->addCheck($cat, 'No friction points', 'warning',
                'Friction detected: ' . implode(', ', $frictions) . '.',
                'Remove or delay popups and avoid autoplay media.');
            $score -= 8;
        } else {
            $this->result->addCheck($cat, 'No friction points', 'passed',
                'No popups or autoplay detected.');
        }

        $this->result->uxScore = max(0, $score);
    }

    // ================================================================
    // 7. CONTENT
    // ================================================================
    private function checkContent(): void
    {
        $cat = 'Content';
        $score = 100;

        // 7a — Clear headline (H1 present and not too short)
        $h1 = $this->xpath->query('//h1');
        if ($h1->length > 0) {
            $h1Text = trim((string) $h1->item(0)->textContent);
            if (mb_strlen($h1Text) >= 10) {
                $this->result->addCheck($cat, 'Headline is clear and compelling', 'passed',
                    'H1: "' . mb_substr($h1Text, 0, 60) . (mb_strlen($h1Text) > 60 ? '...' : '') . '" (' . mb_strlen($h1Text) . ' chars).');
            } else {
                $this->result->addCheck($cat, 'Headline is clear and compelling', 'warning',
                    'H1 is very short (' . mb_strlen($h1Text) . ' chars) — may not be descriptive enough.',
                    'Write a descriptive, benefit-driven H1 headline (at least 20 chars).');
                $score -= 8;
            }
        } else {
            $this->result->addCheck($cat, 'Headline is clear and compelling', 'failed',
                'No H1 heading found.',
                'Add a compelling H1 headline that communicates your value proposition.');
            $score -= 15;
            $this->result->addIssue($cat, 'No H1 headline on page.', 'critical',
                'Add a clear H1 headline stating your main value proposition.');
        }

        // 7b — Subheadline supporting H1
        $h2 = $this->xpath->query('//h2');
        if ($h2->length > 0) {
            $h2Text = trim((string) $h2->item(0)->textContent);
            $this->result->addCheck($cat, 'Subheadline supports headline', 'passed',
                'H2 found: "' . mb_substr($h2Text, 0, 60) . (mb_strlen($h2Text) > 60 ? '...' : '') . '".');
        } else {
            $this->result->addCheck($cat, 'Subheadline supports headline', 'warning',
                'No H2 subheadline — add supporting text under your H1.',
                'Add an H2 subheadline that elaborates on your main headline.');
            $score -= 8;
        }

        // 7c — Contact info exists
        $hasEmail = preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $this->html) === 1;
        $hasPhone = preg_match('/(?:0\d{1,2}[-.\s]?\d{3}[-.\s]?\d{4}|\+\d{1,3}[-.\s]?\d{3}[-.\s]?\d{3}[-.\s]?\d{4})/', $this->html) === 1;
        $hasAddress = preg_match('/(?:street|st\.|ave|road|rd|blvd|drive|lane|רחוב|שדרות|דרך|כתובת)/i', $this->html) === 1;

        $contacts = [];
        if ($hasEmail) $contacts[] = '📧 email';
        if ($hasPhone) $contacts[] = '📞 phone';
        if ($hasAddress) $contacts[] = '📍 address';

        if (count($contacts) >= 2) {
            $this->result->addCheck($cat, 'Contact info exists', 'passed',
                implode(', ', $contacts) . ' found.');
        } elseif (count($contacts) === 1) {
            $this->result->addCheck($cat, 'Contact info exists', 'warning',
                'Only ' . implode(', ', $contacts) . ' found — add more contact methods.',
                'Include at least email and phone for trust.');
            $score -= 8;
        } else {
            $this->result->addCheck($cat, 'Contact info exists', 'failed',
                'No contact info found on page.',
                'Add visible email, phone, or contact details to build trust.');
            $score -= 15;
            $this->result->addIssue($cat, 'No contact information on landing page.', 'critical',
                'Add contact email and phone number — visitors need to trust you.');
        }

        // 7d — Tone matches audience (heuristic: check readability, formality markers)
        $bodyText = trim((string) $this->dom->textContent);
        $wordCount = str_word_count($bodyText, 0);
        if ($wordCount < 30) {
            $this->result->addCheck($cat, 'Sufficient content volume', 'warning',
                "Only ~$wordCount words — page may lack substance.",
                'Add more descriptive content about your product/service.');
            $score -= 10;
        } else {
            $this->result->addCheck($cat, 'Sufficient content volume', 'passed',
                "~$wordCount words — adequate content.");
        }

        // 7e — Spelling pitfalls (heuristic for common mistakes in HTML)
        $spellingIssues = [];
        if (preg_match('/<titl[^e]/i', $this->html)) $spellingIssues[] = 'Possible <title> typo';
        if (preg_match('/<scirpt/i', $this->html)) $spellingIssues[] = 'Possible <script> typo';
        if (preg_match('/<dev\s/i', $this->html)) $spellingIssues[] = 'Possible <div> typo';
        if (preg_match('/<lable/i', $this->html)) $spellingIssues[] = 'Possible <label> typo';

        if (empty($spellingIssues)) {
            $this->result->addCheck($cat, 'No obvious spelling/typo issues', 'passed',
                'No common HTML element typos detected.');
        } else {
            $this->result->addCheck($cat, 'No obvious spelling/typo issues', 'warning',
                implode(', ', $spellingIssues) . '.',
                'Fix typos in HTML tag names.');
            $score -= 8;
        }

        $this->result->contentScore = max(0, $score);
    }

    // ================================================================
    // Helpers
    // ================================================================

    private function countHttpRequests(): int
    {
        $count = 0;
        $count += preg_match_all('/<script\s[^>]*src=[\"\']/i', $this->html);
        $count += preg_match_all('/<link\s[^>]*href=[\"\']/i', $this->html);
        $count += preg_match_all('/<img\s[^>]*src=[\"\']/i', $this->html);
        $count += preg_match_all('/url\(/i', $this->html);
        return $count;
    }

    private function looksExternal(string $src): bool
    {
        return str_starts_with($src, 'http://') ||
               str_starts_with($src, 'https://') ||
               str_starts_with($src, '//');
    }

    private function buildSummary(): string
    {
        $s = $this->result;
        $total = $s->totalChecks;
        $pct  = $total > 0 ? round($s->passedChecks / $total * 100) : 0;

        if ($s->score >= 85) {
            return "🎉 Excellent landing page ({$s->score}/100). " .
                   "{$s->passedChecks}/{$total} checks passed. Minor improvements in " .
                   $this->weakestCategories() . " could make it perfect.";
        } elseif ($s->score >= 70) {
            return "👍 Good landing page ({$s->score}/100). " .
                   "{$s->passedChecks} passed, {$s->warningChecks} warnings, {$s->failedChecks} failed. " .
                   "Focus on " . $this->weakestCategories() . ".";
        } elseif ($s->score >= 50) {
            return "⚠️ Average landing page ({$s->score}/100). " .
                   "{$s->failedChecks} checks failed. Prioritize fixes in " .
                   $this->weakestCategories() . ".";
        } else {
            return "❌ Poor landing page ({$s->score}/100). " .
                   "{$s->failedChecks} critical failures. Major rework needed in " .
                   $this->weakestCategories() . ".";
        }
    }

    private function weakestCategories(): string
    {
        $cats = [
            'Render'      => $this->result->renderScore,
            'Technical'   => $this->result->technicalScore,
            'Design'      => $this->result->designScore,
            'Mobile'      => $this->result->mobileScore,
            'Performance' => $this->result->performanceScore,
            'UX'          => $this->result->uxScore,
            'Content'     => $this->result->contentScore,
        ];
        asort($cats);
        $weakest = [];
        $i = 0;
        foreach ($cats as $name => $s) {
            if ($s < 80 && $i < 2) {
                $weakest[] = "$name ($s%)";
                $i++;
            }
        }
        return $weakest ? implode(' and ', $weakest) : 'all categories look solid';
    }
}
