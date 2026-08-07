<?php
namespace App\Scanner\Layers;

use App\Scanner\ScoringLayer;

/**
 * SEO Layer — Traditional search engine optimization checks.
 * Weight: 0.40
 */
class SeoLayer implements ScoringLayer
{
    public function analyze(string $html, string $url): array
    {
        $issues = [];
        $recommendations = [];
        $score = 100;
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html !== '' ? $html : '<html></html>', 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);

        // --- Meta tags ---
        $title = $xpath->query('//title')->item(0);
        if (!$title || trim($title->textContent) === '') {
            $issues[] = 'Missing <title> tag';
            $score -= 15;
            $recommendations[] = 'Add a descriptive <title> tag (50–60 characters)';
        } elseif (mb_strlen(trim($title->textContent)) > 70) {
            $issues[] = 'Title tag too long (' . mb_strlen(trim($title->textContent)) . ' chars)';
            $score -= 5;
            $recommendations[] = 'Shorten title to 50–60 characters';
        }

        $metaDesc = $xpath->query('//meta[@name="description"]')->item(0);
        if (!$metaDesc) {
            $issues[] = 'Missing meta description';
            $score -= 10;
            $recommendations[] = 'Add a meta description tag (120–160 characters)';
        }

        $robots = $xpath->query('//meta[@name="robots"]')->item(0);
        if (!$robots) {
            $issues[] = 'Missing robots meta tag';
            $score -= 3;
        }

        // --- Headings structure ---
        $h1s = $xpath->query('//h1');
        if ($h1s->length === 0) {
            $issues[] = 'No H1 heading found';
            $score -= 10;
            $recommendations[] = 'Add a single H1 heading describing the page';
        } elseif ($h1s->length > 1) {
            $issues[] = 'Multiple H1 headings (' . $h1s->length . ') — use only one';
            $score -= 5;
        }

        $h2s = $xpath->query('//h2');
        if ($h2s->length === 0) {
            $issues[] = 'No H2 headings found — poor content structure';
            $score -= 5;
            $recommendations[] = 'Use H2 headings to organize content sections';
        }

        // --- Schema markup ---
        $schemas = $xpath->query('//script[@type="application/ld+json"]');
        if ($schemas->length === 0) {
            $issues[] = 'No schema markup (JSON-LD) found';
            $score -= 5;
            $recommendations[] = 'Add JSON-LD schema markup for rich snippets';
        }

        // --- Internal links ---
        $internalLinks = 0;
        foreach ($xpath->query('//a[@href]') as $a) {
            $href = $a->getAttribute('href');
            if (!preg_match('#^(https?:|//|mailto:|tel:)#', $href)) $internalLinks++;
        }
        if ($internalLinks < 3) {
            $issues[] = 'Too few internal links (' . $internalLinks . ')';
            $score -= 3;
            $recommendations[] = 'Add more internal links to improve site structure';
        }

        // --- Mobile friendliness (viewport) ---
        $viewport = $xpath->query('//meta[@name="viewport"]')->item(0);
        if (!$viewport) {
            $issues[] = 'No viewport meta tag — may not be mobile-friendly';
            $score -= 8;
            $recommendations[] = 'Add <meta name="viewport" content="width=device-width, initial-scale=1">';
        }

        // --- Indexability ---
        $canonical = $xpath->query('//link[@rel="canonical"]')->item(0);
        if (!$canonical) {
            $issues[] = 'No canonical URL specified';
            $score -= 3;
            $recommendations[] = 'Add a canonical link tag to prevent duplicate content issues';
        }

        // --- Content SEO ---
        $body = '';
        foreach ($xpath->query('//body//text()') as $node) $body .= ' ' . $node->textContent;
        $body = trim(preg_replace('/\s+/', ' ', $body));
        $wordCount = str_word_count($body);

        if ($wordCount < 300) {
            $issues[] = 'Thin content — only ' . $wordCount . ' words';
            $score -= 10;
            $recommendations[] = 'Add more substantive content (300+ words minimum)';
        }

        // Keyword relevance (check title words appear in body)
        if ($title) {
            $titleWords = array_filter(explode(' ', strtolower(trim($title->textContent))));
            $bodyLower = strtolower($body);
            $missing = [];
            foreach ($titleWords as $w) {
                if (strlen($w) > 3 && !str_contains($bodyLower, $w)) $missing[] = $w;
            }
            if (count($missing) > 0) {
                $issues[] = 'Title keywords missing from body: ' . implode(', ', $missing);
                $score -= 5;
            }
        }

        // Readability (average words per paragraph)
        $paragraphs = $xpath->query('//p');
        $totalWords = 0;
        foreach ($paragraphs as $p) $totalWords += str_word_count($p->textContent);
        if ($paragraphs->length > 0) {
            $avg = $totalWords / $paragraphs->length;
            if ($avg > 80) {
                $issues[] = 'Paragraphs too long (avg ' . round($avg) . ' words) — consider breaking up';
                $score -= 3;
            }
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'recommendations' => $recommendations,
        ];
    }
}
