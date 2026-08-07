<?php
namespace App\Scanner\Layers;

use App\Scanner\ScoringLayer;

/**
 * GEO Layer — Generative Engine Optimization (AI content reuse).
 * Weight: 0.15
 */
class GeoLayer implements ScoringLayer
{
    public function analyze(string $html, string $url): array
    {
        $issues = [];
        $recommendations = [];
        $score = 100;
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html !== '' ? $html : '<html></html>', 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);

        $body = '';
        foreach ($xpath->query('//body//text()') as $node) $body .= ' ' . $node->textContent;
        $body = trim(preg_replace('/\s+/', ' ', $body));

        // Structured factual data (lists, tables, data elements)
        $lists = $xpath->query('//ul|//ol');
        $tables = $xpath->query('//table');
        $structuredCount = $lists->length + $tables->length;
        if ($structuredCount < 2) {
            $issues[] = 'Insufficient structured data — only ' . $structuredCount . ' list(s)/table(s) found';
            $score -= 15;
            $recommendations[] = 'Add lists and tables to present structured factual data AI can reuse';
        }

        // High information density (data-to-text ratio)
        $wordCount = str_word_count($body);
        $hasStats = preg_match_all('/\d+%|\d+\/\d+|\d+\.\d+|\d+\s*(times|more|less|faster|slower|better|higher|lower)/i', $body, $m);
        $densityRatio = $wordCount > 0 ? ($hasStats / max(1, $wordCount / 100)) : 0;
        if ($densityRatio < 0.5) {
            $issues[] = 'Low information density — add more quantitative data';
            $score -= 12;
            $recommendations[] = 'Include percentages, comparisons, and specific metrics AI can synthesize';
        }

        // Neutral tone (avoid excessive emotional/sales language)
        $emotionalWords = ['amazing', 'incredible', 'fantastic', 'awesome', 'stunning', 'breathtaking', 'unbelievable', 'wonderful'];
        $emotionalCount = 0;
        foreach ($emotionalWords as $w) {
            if (stripos($body, $w) !== false) $emotionalCount++;
        }
        if ($emotionalCount > 2) {
            $issues[] = 'Overly emotional tone — ' . $emotionalCount . ' subjective adjectives detected';
            $score -= 10;
            $recommendations[] = 'Use neutral, factual language for better AI content generation inclusion';
        }

        // Reusable content blocks (independent sections with clear boundaries)
        $blockElements = ['section', 'article', 'aside'];
        $reusableBlocks = 0;
        foreach ($blockElements as $el) {
            $nodes = $xpath->query("//$el");
            foreach ($nodes as $node) {
                $text = trim(preg_replace('/\s+/', ' ', $node->textContent));
                if (str_word_count($text) > 20) $reusableBlocks++;
            }
        }
        if ($reusableBlocks < 2) {
            $issues[] = 'Few reusable content blocks (' . $reusableBlocks . ') — content not easily extractable';
            $score -= 10;
            $recommendations[] = 'Organize content into self-contained blocks with clear headings';
        }

        // Consistent terminology
        $h1Text = '';
        foreach ($xpath->query('//h1') as $h) $h1Text .= $h->textContent;
        $mainTopic = strtolower(trim(preg_replace('/[^a-zA-Z\s]/', '', $h1Text)));
        if ($mainTopic && stripos($body, $mainTopic) === false) {
            $issues[] = 'Inconsistent terminology — H1 topic not reflected in body';
            $score -= 8;
            $recommendations[] = 'Ensure consistent terminology across headings and body content';
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'recommendations' => $recommendations,
        ];
    }
}
