<?php
namespace App\Scanner\Layers;

use App\Scanner\ScoringLayer;

/**
 * LLMO Layer — AI readability / understanding optimization.
 * Weight: 0.25
 */
class LlmoLayer implements ScoringLayer
{
    public function analyze(string $html, string $url): array
    {
        $issues = [];
        $recommendations = [];
        $score = 100;
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);

        // Clear semantic structure (semantic HTML5 elements)
        $semanticTags = ['article' => 0, 'section' => 0, 'nav' => 0, 'header' => 0, 'footer' => 0, 'main' => 0];
        $found = false;
        foreach ($semanticTags as $tag => &$count) {
            $count = $xpath->query("//$tag")->length;
            if ($count > 0) $found = true;
        }
        if (!$found) {
            $issues[] = 'No semantic HTML5 elements (article, section, nav, header, footer, main)';
            $score -= 15;
            $recommendations[] = 'Use semantic HTML5 elements to help AI systems understand page structure';
        }

        // Modular sections (check for <section> tags)
        $sections = $xpath->query('//section');
        if ($sections->length < 2) {
            $issues[] = 'Content not organized into modular sections';
            $score -= 10;
            $recommendations[] = 'Break content into independent <section> blocks that remain meaningful when isolated';
        }

        // One idea per paragraph
        $paragraphs = $xpath->query('//p');
        $longParas = 0;
        foreach ($paragraphs as $p) {
            $sentences = preg_split('/[.!?]+/', trim($p->textContent), -1, PREG_SPLIT_NO_EMPTY);
            if (count($sentences) > 3) $longParas++;
        }
        if ($longParas > 0) {
            $issues[] = $longParas . ' paragraph(s) contain multiple ideas — use one idea per paragraph';
            $score -= 10;
            $recommendations[] = 'Split paragraphs to express one clear idea each';
        }

        // Explicit factual statements (look for data, numbers, citations)
        $body = '';
        foreach ($xpath->query('//body//text()') as $node) $body .= ' ' . $node->textContent;
        $hasNumbers = preg_match_all('/\d+%|\$\d+|\d+\s*(years|months|days|users|customers|clients)/i', $body, $m);
        if ($hasNumbers < 2) {
            $issues[] = 'Few explicit factual statements — AI systems need concrete data';
            $score -= 12;
            $recommendations[] = 'Include specific numbers, statistics, and factual claims AI can reference';
        }

        // No ambiguous marketing language (check for vague superlatives)
        $vague = ['best', 'world-class', 'leading', 'unmatched', 'revolutionary', 'game-changing', 'innovative'];
        $foundVague = [];
        foreach ($vague as $v) {
            if (stripos($body, $v) !== false) $foundVague[] = $v;
        }
        if (count($foundVague) > 2) {
            $issues[] = 'Ambiguous marketing language detected: ' . implode(', ', $foundVague);
            $score -= 8;
            $recommendations[] = 'Replace vague superlatives with specific, verifiable claims';
        }

        // Chunkability — content independently extractable
        $headings = $xpath->query('//h2|//h3');
        if ($headings->length < 3) {
            $issues[] = 'Insufficient heading structure for content chunking';
            $score -= 5;
            $recommendations[] = 'Add descriptive headings so each section can be extracted independently';
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'recommendations' => $recommendations,
        ];
    }
}
