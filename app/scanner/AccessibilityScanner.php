<?php
namespace App\Scanner;

use App\Models\AccessibilityResult;

/**
 * AccessibilityScanner — WCAG compliance checks per spec:
 * contrast ratio, keyboard navigation, ARIA labels, form labels, alt text, heading structure.
 */
class AccessibilityScanner implements AccessibilityScannerInterface
{
    public function scan(string $html, string $url): AccessibilityResult
    {
        $r = new AccessibilityResult();
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);

        // --- Alt text ---
        foreach ($xpath->query('//img') as $img) {
            $alt = trim($img->getAttribute('alt'));
            if ($alt === '') $r->imagesWithoutAlt++;
        }
        if ($r->imagesWithoutAlt > 0) {
            $r->issues[] = "{$r->imagesWithoutAlt} image(s) missing alt text";
            $r->score -= min(15, $r->imagesWithoutAlt * 5);
            $r->recommendations[] = 'Add descriptive alt text to all images for screen readers';
        }

        // --- Form labels ---
        $inputs = $xpath->query('//input[not(@type="hidden") and not(@type="submit") and not(@type="button")]|//textarea|//select');
        $labels = $xpath->query('//label');
        $labelFors = [];
        foreach ($labels as $l) { $f = $l->getAttribute('for'); if ($f) $labelFors[$f] = true; }
        foreach ($inputs as $in) {
            $id = $in->getAttribute('id');
            $aria = $in->getAttribute('aria-label');
            $ariaById = $in->getAttribute('aria-labelledby');
            if (!$id || !isset($labelFors[$id])) {
                if (!$aria && !$ariaById) $r->inputsWithoutLabel++;
            }
        }
        if ($r->inputsWithoutLabel > 0) {
            $r->issues[] = "{$r->inputsWithoutLabel} input(s) missing associated label";
            $r->score -= min(15, $r->inputsWithoutLabel * 5);
            $r->recommendations[] = 'Associate every form input with a <label> using for/id or aria-label';
        }

        // --- ARIA labels for interactive elements ---
        $interactive = $xpath->query('//button[not(text()) and not(@aria-label)]|//a[not(text()) and not(@aria-label) and not(img) and not(svg)]');
        $r->missingAriaLabels = $interactive->length;
        if ($r->missingAriaLabels > 0) {
            $r->issues[] = "{$r->missingAriaLabels} interactive element(s) missing accessible name (aria-label)";
            $r->score -= min(10, $r->missingAriaLabels * 5);
            $r->recommendations[] = 'Add aria-label to buttons and links without visible text';
        }

        // --- Keyboard navigation (tabindex, skip link) ---
        $skipLinks = $xpath->query('//a[contains(@href,"#main") or contains(@href,"#content")]');
        $r->hasSkipLink = $skipLinks->length > 0;
        if (!$r->hasSkipLink) {
            $r->issues[] = 'No skip-to-content link found — keyboard users must tab through entire page';
            $r->score -= 8;
            $r->recommendations[] = 'Add a skip-to-content link as the first focusable element';
        }

        // --- Heading structure (no gaps) ---
        $headings = [];
        for ($i = 1; $i <= 6; $i++) {
            $headings[$i] = $xpath->query("//h$i")->length;
        }
        // Check for gaps (e.g., h1→h3 without h2)
        $lastLevel = 0;
        foreach ($headings as $level => $count) {
            if ($count > 0 && $lastLevel > 0 && $level > $lastLevel + 1) {
                $r->headingGaps++;
                $r->issues[] = "Heading level skip: h{$lastLevel} → h{$level} (h" . ($lastLevel+1) . " missing)";
                $r->score -= 5;
            }
            if ($count > 0) $lastLevel = $level;
        }
        if ($r->headingGaps > 0) {
            $r->recommendations[] = 'Use consecutive heading levels (h1→h2→h3) without skipping';
        }

        if ($headings[1] === 0) {
            $r->issues[] = 'No H1 heading found — critical for screen reader navigation';
            $r->score -= 10;
            $r->recommendations[] = 'Add a single H1 heading describing the page purpose';
        }

        // --- Contrast ratio (heuristic: check for inline styles with low contrast) ---
        $lowContrastPairs = $this->checkContrast($xpath);
        if ($lowContrastPairs > 0) {
            $r->issues[] = "{$lowContrastPairs} potential low-contrast text element(s) detected";
            $r->score -= min(10, $lowContrastPairs * 5);
            $r->recommendations[] = 'Ensure text/background contrast ratio meets WCAG AA minimum (4.5:1)';
        }

        // Priority fixes
        if ($r->imagesWithoutAlt > 0) $r->priorityFixes[] = 'Add alt text to all images';
        if ($r->inputsWithoutLabel > 0) $r->priorityFixes[] = 'Associate form inputs with labels';
        if ($headings[1] === 0) $r->priorityFixes[] = 'Add H1 heading';

        // Summary
        $r->score = max(0, min(100, $r->score));
        if ($r->score >= 80) {
            $r->summary = "Good accessibility ({$r->score}/100). Minor WCAG improvements possible.";
        } elseif ($r->score >= 60) {
            $r->summary = "Moderate accessibility ({$r->score}/100). Address alt text, labels, and heading structure.";
        } else {
            $r->summary = "Poor accessibility ({$r->score}/100). Critical WCAG violations present.";
        }

        return $r;
    }

    private function checkContrast(\DOMXPath $xpath): int
    {
        $count = 0;
        $elements = $xpath->query('//*[@style]');
        foreach ($elements as $el) {
            $style = strtolower($el->getAttribute('style'));
            // Detect very light text on white or dark text on dark backgrounds
            if (preg_match('/color\s*:\s*#(?:[c-f]|[a-f][a-f]){3,6}/i', $style) ||
                preg_match('/color\s*:\s*(?:white|lightgray|lightgrey|silver|gray|grey)/i', $style)) {
                // Check if background is also light/absent
                if (!preg_match('/background(?:-color)?\s*:\s*(?:#(?:[0-3]|[0-5][0-9a-f])|black|dark|navy|maroon)/i', $style)) {
                    $count++;
                }
            }
        }
        return $count;
    }
}
