<?php
namespace App\Scanner;

use App\Models\SeoResult;
use App\Scanner\Layers\SeoLayer;
use App\Scanner\Layers\LlmoLayer;
use App\Scanner\Layers\AeoLayer;
use App\Scanner\Layers\GeoLayer;

/**
 * SeoScanner — orchestrates the 4-layer AI SEO intelligence scan.
 * Weights per SEO_ENGINE.md: SEO=0.4, LLMO=0.25, AEO=0.2, GEO=0.15
 */
class SeoScanner implements SeoScannerInterface
{
    private array $layers;

    public function __construct()
    {
        $this->layers = [
            new SeoLayer(),
            new LlmoLayer(),
            new AeoLayer(),
            new GeoLayer(),
        ];
    }

    public function scan(string $html, string $url): SeoResult
    {
        $result = new SeoResult();

        $layerNames = ['SEO', 'LLMO', 'AEO', 'GEO'];
        $priorityKeywords = ['missing', 'no ', 'not found', '0 heading', 'unreadable'];

        foreach ($this->layers as $i => $layer) {
            $analysis = $layer->analyze($html, $url);

            switch ($i) {
                case 0: $result->seoScore  = $analysis['score']; break;
                case 1: $result->llmoScore = $analysis['score']; break;
                case 2: $result->aeoScore  = $analysis['score']; break;
                case 3: $result->geoScore  = $analysis['score']; break;
            }

            // Tag issues with layer name
            foreach ($analysis['issues'] as $issue) {
                $tagged = "[{$layerNames[$i]}] $issue";
                $result->issues[] = $tagged;

                // Priority fixes: issues containing key severity words
                foreach ($priorityKeywords as $kw) {
                    if (stripos($issue, $kw) !== false && !in_array($tagged, $result->priorityFixes)) {
                        $result->priorityFixes[] = $tagged;
                        break;
                    }
                }
            }

            foreach ($analysis['recommendations'] as $rec) {
                $result->recommendations[] = "[{$layerNames[$i]}] $rec";
            }
        }

        // Generate summary
        $final = $result->finalScore();
        if ($final >= 80) {
            $result->summary = "Strong AI-SEO alignment ($final/100). Content is well-optimized across search, AI readability, direct answers, and generative AI inclusion.";
        } elseif ($final >= 60) {
            $result->summary = "Moderate AI-SEO alignment ($final/100). Key gaps in " . $this->weakestLayer($result) . ". Address priority fixes to improve.";
        } else {
            $result->summary = "Weak AI-SEO alignment ($final/100). Significant improvements needed across " . $this->weakestLayer($result) . " and other layers.";
        }

        return $result;
    }

    private function weakestLayer(SeoResult $r): string
    {
        $scores = ['SEO' => $r->seoScore, 'LLMO' => $r->llmoScore, 'AEO' => $r->aeoScore, 'GEO' => $r->geoScore];
        asort($scores);
        return array_key_first($scores);
    }
}
