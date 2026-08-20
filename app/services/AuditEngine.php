<?php
namespace App\Services;

use App\Core\Logger;
use App\LeadEngine\AuditResult;
use App\LeadEngine\HotScore;
use App\LeadEngine\HtmlSignals;
use App\LeadEngine\PageSpeedClient;
use App\LeadEngine\PoliteFetcher;
use App\Scanner\AccessibilityScanner;
use App\Scanner\SecurityScanner;
use App\Scanner\SeoScanner;

/**
 * AuditEngine — `runAudit(url) → AuditResult` (spec §2).
 *
 * The single scoring entry point for the Lead Engine. It wraps the existing
 * app/scanner classes rather than re-implementing them, and layers on the
 * signal checks and hot_score the pipeline needs.
 *
 * Sources, in the order §6 lists them:
 *   1. PageSpeed Insights API — perf/seo/a11y (when PAGESPEED_API_KEY is set)
 *   2. Homepage HTML patch     — fbq, gtag, tel:, viewport, a11y statement, form
 *   3. SSL / response headers  — via the existing SecurityScanner
 *
 * The public /audit page keeps its own presentation-oriented checks; this engine
 * is the machine-readable one.
 */
class AuditEngine
{
    private PoliteFetcher $fetcher;
    private PageSpeedClient $pagespeed;
    private SeoScanner $seo;
    private AccessibilityScanner $a11y;
    private SecurityScanner $security;

    public function __construct(
        ?PoliteFetcher $fetcher = null,
        ?PageSpeedClient $pagespeed = null
    ) {
        $this->fetcher   = $fetcher ?? new PoliteFetcher();
        $this->pagespeed = $pagespeed ?? new PageSpeedClient();
        $this->seo       = new SeoScanner();
        $this->a11y      = new AccessibilityScanner();
        $this->security  = new SecurityScanner();
    }

    /**
     * @param bool $spendsOnAds Feeds the ×1.4 score bonus and the
     *                          no_analytics_with_ads issue (§6)
     * @param bool $brokenForm  Human-verified form failure only (§11.5)
     */
    public function runAudit(string $url, bool $spendsOnAds = false, bool $brokenForm = false): AuditResult
    {
        $url = PoliteFetcher::normalizeUrl($url);
        $result = new AuditResult();
        $result->url = $url;
        $result->hasSsl = str_starts_with(strtolower($url), 'https://');

        // The URL the scanners actually see — the http:// fallback below
        // rewrites it, and SecurityScanner infers HTTPS from the scheme.
        $scanUrl = $url;

        // --- Homepage fetch ------------------------------------------------
        $page = $this->fetcher->fetch($url);
        $result->fetchOk = $page['ok'];
        $result->httpStatus = $page['status'];
        $result->responseTimeMs = $page['time_ms'];
        $html = $page['body'];
        $firstAttempt = $page;

        if (!$result->fetchOk) {
            // Retry once over http:// when https:// failed — plenty of small
            // Israeli business sites are still http-only, and those are exactly
            // the prospects worth talking to.
            if ($result->hasSsl) {
                $httpUrl = 'http://' . substr($url, strlen('https://'));
                $page = $this->fetcher->fetch($httpUrl);
                if ($page['ok']) {
                    $result->fetchOk = true;
                    $result->hasSsl = false;
                    $result->httpStatus = $page['status'];
                    $result->responseTimeMs = $page['time_ms'];
                    $html = $page['body'];
                    $scanUrl = $httpUrl;
                    $result->issues[] = 'האתר נטען רק ב-HTTP — אין תעודת SSL';
                }
            }
            if (!$result->fetchOk) {
                // Distinguish "we chose not to fetch / were bounced by bot protection"
                // from a genuinely dead site: robots.txt disallow, or a status code
                // WAFs/CDNs commonly use to reject automated clients. A site behind
                // Cloudflare/Hostinger-style protection is not a bad lead — we just
                // couldn't see it, and that is a human-review case, not a close.
                //
                // Check both attempts, not just the last one: when https:// shows a
                // block signal (e.g. 403) and the http:// fallback then fails outright
                // (status 0 — connection refused, nothing listening on 80), $page holds
                // the fallback's result and would otherwise mask the real signal.
                $blockedByRobots = $firstAttempt['blocked'] || $page['blocked'];
                $result->looksBlocked = $blockedByRobots
                    || in_array($firstAttempt['status'], [401, 403, 429, 503], true)
                    || in_array($page['status'], [401, 403, 429, 503], true);
                $result->issues[] = $blockedByRobots
                    ? 'robots.txt חוסם סריקה של דף הבית'
                    : ($result->looksBlocked
                        ? 'האתר חסם את הסריקה (HTTP ' . $result->httpStatus . ') — כנראה הגנת בוטים'
                        : 'לא ניתן לטעון את דף הבית (HTTP ' . $result->httpStatus . ')');
                $result->hotScore = 0;
                $result->primaryIssue = 'none';
                return $result;
            }
        }

        // --- HTML signals (§6) ---------------------------------------------
        $result->hasAnalytics               = HtmlSignals::hasAnalytics($html);
        $result->hasMetaPixel               = HtmlSignals::hasMetaPixel($html);
        $result->hasClickToCall             = HtmlSignals::hasClickToCall($html);
        $result->mobileViewportOk           = HtmlSignals::hasMobileViewport($html);
        $result->hasAccessibilityStatement  = HtmlSignals::hasAccessibilityStatement($html);
        $result->contactFormFound           = HtmlSignals::hasContactForm($html);

        // --- Scanner layer --------------------------------------------------
        $headers = $page['headers'];
        try {
            $seoResult = $this->seo->scan($html, $scanUrl);
            $result->seoScore = $seoResult->finalScore();
            $result->raw['seo'] = $seoResult->toArray();
        } catch (\Throwable $e) {
            Logger::error('leadengine: seo scan failed', ['url' => $scanUrl, 'message' => $e->getMessage()]);
        }

        try {
            $a11yResult = $this->a11y->scan($html, $scanUrl);
            $result->a11yScore = $a11yResult->score;
            $result->raw['accessibility'] = $a11yResult->toArray();
        } catch (\Throwable $e) {
            Logger::error('leadengine: a11y scan failed', ['url' => $scanUrl, 'message' => $e->getMessage()]);
        }

        try {
            $secResult = $this->security->scan($html, $scanUrl, $headers);
            $result->securityScore = $secResult->score;
            $result->raw['security'] = $secResult->toArray();
        } catch (\Throwable $e) {
            Logger::error('leadengine: security scan failed', ['url' => $scanUrl, 'message' => $e->getMessage()]);
        }

        // --- Performance ----------------------------------------------------
        $psi = $this->pagespeed->analyze($scanUrl, 'mobile');
        if ($psi !== null && $psi['performance'] !== null) {
            $result->perfMobile = $psi['performance'];
            $result->perfSource = 'pagespeed';
            $result->raw['pagespeed_mobile'] = $psi;

            // Lighthouse beats our regex heuristics for these two
            if ($psi['seo'] !== null) {
                $result->seoScore = $psi['seo'];
            }
            if ($psi['accessibility'] !== null) {
                $result->a11yScore = $psi['accessibility'];
            }

            $psiDesktop = $this->pagespeed->analyze($scanUrl, 'desktop');
            if ($psiDesktop !== null && $psiDesktop['performance'] !== null) {
                $result->perfDesktop = $psiDesktop['performance'];
                $result->raw['pagespeed_desktop'] = $psiDesktop;
            }
        } else {
            $result->perfMobile = $this->heuristicMobilePerf($html, $result->responseTimeMs, $headers);
            $result->perfSource = 'heuristic';
        }

        // --- Score + headline issue (§6) ------------------------------------
        $result->hotScore = HotScore::compute($result, $spendsOnAds);
        $result->primaryIssue = HotScore::primaryIssue($result, $spendsOnAds, $brokenForm);
        $result->issues = array_merge($result->issues, $this->summarizeIssues($result));

        return $result;
    }

    /**
     * Stand-in for a Lighthouse performance score when no PSI key is set.
     *
     * Deliberately pessimistic-but-bounded: it starts at 90 and deducts for the
     * things that actually make Israeli small-business sites slow on mobile.
     * Recorded as perf_source='heuristic' so nobody quotes it as a PageSpeed
     * number in an email.
     */
    private function heuristicMobilePerf(string $html, int $responseTimeMs, array $headers): int
    {
        $score = 90;

        // Server response time — mobile networks multiply this
        if ($responseTimeMs > 4000)      $score -= 45;
        elseif ($responseTimeMs > 2500)  $score -= 32;
        elseif ($responseTimeMs > 1500)  $score -= 20;
        elseif ($responseTimeMs > 800)   $score -= 10;

        // Document weight
        $sizeKb = strlen($html) / 1024;
        if ($sizeKb > 700)      $score -= 20;
        elseif ($sizeKb > 350)  $score -= 12;
        elseif ($sizeKb > 150)  $score -= 5;

        // Render-blocking scripts and stylesheets
        $blocking = preg_match_all('/<link\b[^>]*rel=["\']stylesheet["\']/i', $html);
        if (preg_match_all('/<script\b[^>]*\bsrc=[^>]*>/i', $html, $scriptTags)) {
            foreach ($scriptTags[0] as $tag) {
                if (!preg_match('/\b(async|defer)\b/i', $tag)) {
                    $blocking++;
                }
            }
        }
        if ($blocking > 12)     $score -= 18;
        elseif ($blocking > 6)  $score -= 10;
        elseif ($blocking > 3)  $score -= 5;

        // Images without dimensions cause layout shift
        $images = preg_match_all('/<img\b[^>]*>/i', $html, $imgMatches);
        if ($images > 0) {
            $sized = 0;
            foreach ($imgMatches[0] as $img) {
                if (preg_match('/\bwidth\s*=/i', $img) && preg_match('/\bheight\s*=/i', $img)) {
                    $sized++;
                }
            }
            if ($sized / $images < 0.5) {
                $score -= 8;
            }
            if ($images > 40) {
                $score -= 8;
            }
        }

        // No viewport meta = not a mobile-ready page at all
        if (!HtmlSignals::hasMobileViewport($html)) {
            $score -= 15;
        }

        // No compression on the document
        $encoding = $headers['content-encoding'] ?? '';
        if ($encoding === '') {
            $score -= 6;
        }

        return max(1, min(99, $score));
    }

    /** Short Hebrew lines for the panel and the video brief */
    private function summarizeIssues(AuditResult $r): array
    {
        $issues = [];
        if (($r->perfMobile ?? 100) < 50) {
            $issues[] = 'מהירות מובייל ' . $r->perfMobile . '/100'
                . ($r->perfSource === 'heuristic' ? ' (הערכה מקומית, לא PageSpeed)' : ' (PageSpeed)');
        }
        if (!$r->hasAnalytics)              $issues[] = 'לא נמצא קוד Analytics';
        if (!$r->hasMetaPixel)              $issues[] = 'לא נמצא Meta Pixel';
        if (!$r->hasClickToCall)            $issues[] = 'אין קישור tel: בדף הבית';
        if (!$r->mobileViewportOk)          $issues[] = 'אין תגית viewport — האתר לא מותאם מובייל';
        if (!$r->hasAccessibilityStatement) $issues[] = 'אין הצהרת נגישות';
        if (!$r->hasSsl)                    $issues[] = 'אין HTTPS';
        if (!$r->contactFormFound)          $issues[] = 'לא נמצא טופס יצירת קשר';
        if ($r->a11yScore < 70)             $issues[] = 'ציון נגישות ' . $r->a11yScore . '/100';
        if ($r->seoScore < 60)              $issues[] = 'ציון SEO ' . $r->seoScore . '/100';
        return $issues;
    }
}
