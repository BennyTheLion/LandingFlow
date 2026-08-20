<?php
namespace App\LeadEngine;

/**
 * HotScore — spec §6.
 *
 * The score measures how worth contacting a business is, NOT how good its site
 * is. High score = good opportunity. Every term is inverted (100 - score) or a
 * binary 100/0 penalty, so a perfect site scores 0 and gets dropped.
 */
class HotScore
{
    /** Weights per §6 — must sum to 1.00 */
    public const WEIGHTS = [
        'perf_mobile'      => 0.30,
        'a11y_score'       => 0.20,
        'no_analytics'     => 0.15,
        'no_a11y_state'    => 0.15,
        'seo_score'        => 0.10,
        'no_click_to_call' => 0.10,
    ];

    /** Multiplier applied when the business is already paying for ads (§6) */
    public const ADS_BONUS = 1.4;

    /**
     * Order matters: the first matching issue is the one we sell on, and it
     * becomes the video title and the email subject line (§6).
     */
    public const ISSUE_PRIORITY = [
        'broken_form',
        'no_analytics_with_ads',
        'slow_mobile',
        'no_accessibility',
        'no_click_to_call',
        'weak_seo',
    ];

    /**
     * @param bool $spendsOnAds Applies the ×1.4 bonus, capped at 100
     */
    public static function compute(AuditResult $audit, bool $spendsOnAds = false): int
    {
        // A homepage we could not fetch tells us nothing — score 0 so it drops
        // out of the pipeline instead of scoring 100 off all-zero inputs.
        if (!$audit->fetchOk) {
            return 0;
        }

        $perfMobile = $audit->perfMobile ?? 50;

        $score =
              (100 - self::clamp($perfMobile))       * self::WEIGHTS['perf_mobile']
            + (100 - self::clamp($audit->a11yScore)) * self::WEIGHTS['a11y_score']
            + ($audit->hasAnalytics ? 0 : 100)       * self::WEIGHTS['no_analytics']
            + ($audit->hasAccessibilityStatement ? 0 : 100) * self::WEIGHTS['no_a11y_state']
            + (100 - self::clamp($audit->seoScore))  * self::WEIGHTS['seo_score']
            + ($audit->hasClickToCall ? 0 : 100)     * self::WEIGHTS['no_click_to_call'];

        if ($spendsOnAds) {
            $score *= self::ADS_BONUS;
        }

        return (int) min(100, max(0, round($score)));
    }

    /**
     * The single issue we lead with.
     *
     * @param bool $brokenForm Human-verified only — the engine never submits
     *                         another business's form (§11.5)
     */
    public static function primaryIssue(AuditResult $audit, bool $spendsOnAds = false, bool $brokenForm = false): string
    {
        if ($brokenForm) {
            return 'broken_form';
        }
        if ($spendsOnAds && !$audit->hasAnalytics) {
            return 'no_analytics_with_ads';
        }
        if (($audit->perfMobile ?? 100) < 40) {
            return 'slow_mobile';
        }
        if (!$audit->hasAccessibilityStatement && $audit->a11yScore < 70) {
            return 'no_accessibility';
        }
        if (!$audit->hasClickToCall) {
            return 'no_click_to_call';
        }
        if ($audit->seoScore < 60) {
            return 'weak_seo';
        }
        return 'none';
    }

    /** Hebrew label for the panel, email subject, and video brief */
    public static function issueLabel(string $issue): string
    {
        return match ($issue) {
            'broken_form'           => 'טופס יצירת קשר לא עובד',
            'no_analytics_with_ads' => 'מפרסמים בלי מעקב המרות',
            'slow_mobile'           => 'האתר איטי במובייל',
            'no_accessibility'      => 'אין הצהרת נגישות',
            'no_click_to_call'      => 'אין לחיצה להתקשרות',
            'weak_seo'              => 'SEO חלש',
            default                 => 'לא זוהתה בעיה מובילה',
        };
    }

    /**
     * The concrete number to quote in the message — one hard fact beats three
     * vague ones (§8).
     */
    public static function issueEvidence(string $issue, AuditResult $audit): string
    {
        return match ($issue) {
            'broken_form'           => 'שליחת הטופס באתר לא מגיעה ליעד',
            'no_analytics_with_ads' => 'לא נמצא קוד מעקב (Analytics/Pixel) בדף הבית',
            'slow_mobile'           => 'ציון מהירות מובייל: ' . ($audit->perfMobile ?? '?') . '/100',
            'no_accessibility'      => 'ציון נגישות: ' . $audit->a11yScore . '/100, ואין הצהרת נגישות',
            'no_click_to_call'      => 'אין קישור tel: בדף הבית — גולש במובייל לא יכול להתקשר בלחיצה',
            'weak_seo'              => 'ציון SEO: ' . $audit->seoScore . '/100',
            default                 => 'ציון כללי: ' . $audit->hotScore . '/100',
        };
    }

    /** Per-term contribution, for the "why this score" breakdown in the panel */
    public static function breakdown(AuditResult $audit, bool $spendsOnAds = false): array
    {
        $terms = [
            'מהירות מובייל'  => (100 - self::clamp($audit->perfMobile ?? 50)) * self::WEIGHTS['perf_mobile'],
            'נגישות'         => (100 - self::clamp($audit->a11yScore)) * self::WEIGHTS['a11y_score'],
            'אין Analytics'  => ($audit->hasAnalytics ? 0 : 100) * self::WEIGHTS['no_analytics'],
            'אין הצהרת נגישות' => ($audit->hasAccessibilityStatement ? 0 : 100) * self::WEIGHTS['no_a11y_state'],
            'SEO'            => (100 - self::clamp($audit->seoScore)) * self::WEIGHTS['seo_score'],
            'אין click-to-call' => ($audit->hasClickToCall ? 0 : 100) * self::WEIGHTS['no_click_to_call'],
        ];

        $out = [];
        foreach ($terms as $label => $points) {
            $out[$label] = round($spendsOnAds ? $points * self::ADS_BONUS : $points, 1);
        }
        return $out;
    }

    private static function clamp(int $n): int
    {
        return max(0, min(100, $n));
    }
}
