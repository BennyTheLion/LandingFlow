<?php
namespace App\LeadEngine;

/**
 * HtmlSignals — the homepage HTML patch checks from spec §6.
 *
 * Pure string/regex work on already-fetched HTML: no network, no state, so it
 * is cheap to unit-test and reusable by the contact finder.
 */
class HtmlSignals
{
    /** Analytics: GA4, universal GA, GTM, and the common Israeli wrappers */
    public static function hasAnalytics(string $html): bool
    {
        return (bool) preg_match(
            '/gtag\s*\(|googletagmanager|google-analytics\.com|\bga\s*\(\s*[\'"]create|_gaq|\b_ga\b|clarity\.ms|hotjar/i',
            $html
        );
    }

    /** Meta/Facebook pixel */
    public static function hasMetaPixel(string $html): bool
    {
        return (bool) preg_match('/fbq\s*\(|connect\.facebook\.net\/[^"\']*fbevents|facebook\.com\/tr\?/i', $html);
    }

    /** A tel: link anywhere on the homepage */
    public static function hasClickToCall(string $html): bool
    {
        return (bool) preg_match('/href\s*=\s*["\']\s*tel:/i', $html);
    }

    /** Responsive viewport meta tag */
    public static function hasMobileViewport(string $html): bool
    {
        return (bool) preg_match('/<meta[^>]+name\s*=\s*["\']viewport["\'][^>]*content\s*=\s*["\'][^"\']*width\s*=\s*device-width/i', $html);
    }

    /**
     * Israeli accessibility statement — link text or href, Hebrew or English.
     * Requires an anchor/heading context so the word "נגישות" inside body copy
     * does not read as a published statement.
     */
    public static function hasAccessibilityStatement(string $html): bool
    {
        $patterns = [
            '/href\s*=\s*["\'][^"\']*(accessibility|negishut|nagishut)[^"\']*["\']/i',
            '/<a[^>]*>[^<]{0,40}(הצהרת\s*נגישות|נגישות\s*האתר)[^<]{0,40}<\/a>/u',
            '/(הצהרת\s+נגישות)/u',
            '/<a[^>]*>[^<]{0,30}accessibility\s+statement[^<]{0,30}<\/a>/i',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $html)) {
                return true;
            }
        }
        return false;
    }

    /**
     * A contact form: a <form> carrying an email/message/name field, or an
     * embedded third-party form iframe. Detection only — the engine never
     * submits anything (§11.5).
     */
    public static function hasContactForm(string $html): bool
    {
        if (preg_match('/<iframe[^>]+(docs\.google\.com\/forms|typeform|jotform|hubspot|wufoo|forms\.gle)/i', $html)) {
            return true;
        }
        if (!preg_match_all('/<form\b.*?<\/form>/is', $html, $forms)) {
            return false;
        }
        foreach ($forms[0] as $form) {
            if (preg_match('/type\s*=\s*["\']email["\']|<textarea|name\s*=\s*["\'][^"\']*(email|mail|message|phone|tel|name|שם|טלפון|הודעה)/i', $form)) {
                return true;
            }
        }
        return false;
    }

    /** Every mailto: and bare email address in the markup, deduplicated */
    public static function extractEmails(string $html): array
    {
        $found = [];
        if (preg_match_all('/mailto:([^"\'>\s?]+)/i', $html, $m)) {
            $found = array_merge($found, $m[1]);
        }
        if (preg_match_all('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', strip_tags($html), $m)) {
            $found = array_merge($found, $m[0]);
        }

        $clean = [];
        foreach ($found as $email) {
            $email = strtolower(trim(rawurldecode($email)));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            // Drop asset filenames and tracking pixels that look like addresses
            if (preg_match('/\.(png|jpe?g|gif|svg|webp|css|js|woff2?)$/i', $email)) {
                continue;
            }
            if (preg_match('/^(no-?reply|postmaster|abuse|sentry|example|test)@/i', $email)) {
                continue;
            }
            $clean[$email] = true;
        }
        return array_keys($clean);
    }

    /** Israeli phone numbers (landline + mobile), normalized to digits */
    public static function extractPhones(string $html): array
    {
        $text = strip_tags($html);
        $found = [];

        if (preg_match_all('/href\s*=\s*["\']\s*tel:([+0-9\-\s().]{7,})/i', $html, $m)) {
            $found = array_merge($found, $m[1]);
        }
        if (preg_match_all('/(?:\+972[-\s]?|0)(?:[23489]|5[0-9]|7[0-9])[-\s]?\d{3}[-\s]?\d{4}/', $text, $m)) {
            $found = array_merge($found, $m[0]);
        }

        $clean = [];
        foreach ($found as $phone) {
            $digits = preg_replace('/[^0-9+]/', '', $phone);
            if ($digits === null || $digits === '') {
                continue;
            }
            if (str_starts_with($digits, '+972')) {
                $digits = '0' . substr($digits, 4);
            }
            if (strlen($digits) < 9 || strlen($digits) > 11) {
                continue;
            }
            $clean[$digits] = true;
        }
        return array_keys($clean);
    }

    /**
     * JSON-LD blocks parsed into arrays (spec §7 step 2 — founder/employee often
     * sits in schema.org markup). Malformed blocks are skipped silently.
     */
    public static function extractJsonLd(string $html): array
    {
        if (!preg_match_all('/<script[^>]+type\s*=\s*["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $m)) {
            return [];
        }
        $blocks = [];
        foreach ($m[1] as $json) {
            $decoded = json_decode(trim($json), true);
            if (is_array($decoded)) {
                $blocks[] = $decoded;
            }
        }
        return $blocks;
    }

    /**
     * Pull a person's name out of JSON-LD (founder, employee, author) — the
     * cheapest reliable contact_name source when it exists.
     */
    public static function personNameFromJsonLd(array $blocks): ?string
    {
        $walk = function ($node) use (&$walk): ?string {
            if (!is_array($node)) {
                return null;
            }
            foreach (['founder', 'founders', 'employee', 'author', 'member'] as $key) {
                if (!isset($node[$key])) {
                    continue;
                }
                $candidate = $node[$key];
                if (is_string($candidate) && self::looksLikePersonName($candidate)) {
                    return $candidate;
                }
                if (is_array($candidate)) {
                    $name = $candidate['name'] ?? ($candidate[0]['name'] ?? null);
                    if (is_string($name) && self::looksLikePersonName($name)) {
                        return $name;
                    }
                }
            }
            if (($node['@type'] ?? '') === 'Person' && is_string($node['name'] ?? null)
                && self::looksLikePersonName($node['name'])) {
                return $node['name'];
            }
            foreach ($node as $child) {
                if (is_array($child) && ($found = $walk($child)) !== null) {
                    return $found;
                }
            }
            return null;
        };

        foreach ($blocks as $block) {
            if (($found = $walk($block)) !== null) {
                return $found;
            }
        }
        return null;
    }

    /** Two-to-four words, no digits, no company suffixes */
    public static function looksLikePersonName(string $name): bool
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        if ($name === '' || mb_strlen($name) < 4 || mb_strlen($name) > 40) {
            return false;
        }
        if (preg_match('/\d|@|http|\.(com|co\.il|net)/i', $name)) {
            return false;
        }
        if (preg_match('/\b(ltd|inc|llc|בע"?מ|חברה|קבוצת)\b/iu', $name)) {
            return false;
        }
        $words = explode(' ', $name);
        return count($words) >= 2 && count($words) <= 4;
    }

    /** First name only — "היי דני" beats "שלום רב" (§7) */
    public static function firstName(string $fullName): string
    {
        $parts = preg_split('/\s+/u', trim($fullName)) ?: [];
        $first = $parts[0] ?? '';
        // Skip Hebrew and English honorifics
        if (preg_match('/^(ד"ר|דר|מר|גב|עו"ד|פרופ|dr|mr|mrs|ms|prof)\.?$/iu', $first) && isset($parts[1])) {
            $first = $parts[1];
        }
        return $first;
    }
}
