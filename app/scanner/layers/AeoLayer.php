<?php
namespace App\Scanner\Layers;

use App\Scanner\ScoringLayer;

/**
 * AEO Layer — Answer Engine Optimization (direct answers, zero-click readiness).
 * Weight: 0.20
 */
class AeoLayer implements ScoringLayer
{
    public function analyze(string $html, string $url): array
    {
        $issues = [];
        $recommendations = [];
        $score = 100;
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);

        $body = '';
        foreach ($xpath->query('//body//text()') as $node) $body .= ' ' . $node->textContent;
        $body = trim(preg_replace('/\s+/', ' ', $body));

        // Direct answers present (definition-style content)
        $hasDefinitions = (bool) preg_match('/(is|are|means|refers to|defined as)\s/i', $body);
        if (!$hasDefinitions) {
            $issues[] = 'No direct definition-style answers found';
            $score -= 15;
            $recommendations[] = 'Include clear "X is Y" style definitions that can be used as direct answers';
        }

        // FAQ structure
        $faqPattern = (bool) preg_match('/(\?\s*$|how\s+(do|can|does|is|are)|what\s+(is|are)|why\s+(is|are)|FAQ)/im', $body);
        $dlElements = $xpath->query('//dl/dt')->length;
        $detailsElements = $xpath->query('//details/summary')->length;

        if (!$faqPattern && $dlElements === 0 && $detailsElements === 0) {
            $issues[] = 'No FAQ structure or question-answer format found';
            $score -= 15;
            $recommendations[] = 'Add FAQ section with clear questions and concise answers';
        }

        // Question coverage
        $questionsCovered = 0;
        $qPatterns = [
            'What is it?'     => '/what\s+(is|are)\s/i',
            'How does it work?'=> '/how\s+(does|do|it\s+works?|can|to)\s/i',
            'Pricing'         => '/price|pricing|cost|fee|\$/i',
            'Benefits'        => '/benefits?|advantages?|why\s+(choose|us)/i',
        ];
        foreach ($qPatterns as $q => $pattern) {
            if (preg_match($pattern, $body)) $questionsCovered++;
        }
        if ($questionsCovered < 2) {
            $issues[] = 'Question coverage low — only ' . $questionsCovered . '/4 key questions addressed (What is it?, How?, Pricing, Benefits)';
            $score -= 12;
        }

        // Zero-click readiness (can user get answer without navigating?)
        $firstSection = '';
        foreach ($xpath->query('//h1|//h2|//h3') as $h) {
            $firstSection .= $h->textContent . ' ';
            if (strlen($firstSection) > 200) break;
        }
        $hasSummary = (bool) preg_match('/summary|overview|at a glance|tl;dr|key (points|takeaways)/i', $body);
        if (!$hasSummary) {
            $issues[] = 'No summary section for zero-click answers';
            $score -= 10;
            $recommendations[] = 'Add an executive summary or key-takeaways section for zero-click search results';
        }

        // Clear definitions
        $hasDl = $xpath->query('//dl')->length > 0;
        $hasStrong = $xpath->query('//strong')->length > 3;
        if (!$hasDl && !$hasStrong) {
            $issues[] = 'No definition lists or emphasized key terms';
            $score -= 5;
            $recommendations[] = 'Use <dl> definition lists or <strong> tags for key terms AI can extract';
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'recommendations' => $recommendations,
        ];
    }
}
