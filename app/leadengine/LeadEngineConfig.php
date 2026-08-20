<?php
namespace App\LeadEngine;

use App\Repositories\LeadEngineRepository;

/**
 * LeadEngineConfig — resolves a setting from the DB first, then the env
 * constant, then a hard default.
 *
 * The settings screen (§10) writes to lead_engine_settings so limits can be
 * changed without a deploy; env constants stay as the deployment baseline.
 */
class LeadEngineConfig
{
    /** Identified crawler UA per spec §7 */
    public const USER_AGENT = 'LandingFlowBot/1.0 (+https://landingflow.co.il)';

    /** One request per 2 seconds per domain (§7) */
    public const CRAWL_DELAY_SECONDS = 2;

    /** Approval token lifetime (§9) */
    public const TOKEN_TTL_HOURS = 72;

    private const DEFAULTS = [
        'hot_score_threshold'       => 55,
        'max_daily_sends'           => 8,
        'min_minutes_between_sends' => 5,
        'send_window_start'         => '09:00',
        'send_window_end'           => '18:00',
        'pipeline_enabled'          => 1,
        'sending_halted'            => 0,
        'active_niches'             => 'dental_clinic,law_firm,aesthetics,contractor',
        'active_cities'             => '',
        'closed_retention_months'   => 12,
        'reaudit_closed_days'       => 60,
        'reaudit_blocked_days'      => 3,
    ];

    /** Setting key => env constant name */
    private const ENV_MAP = [
        'hot_score_threshold' => 'HOT_SCORE_THRESHOLD',
        'max_daily_sends'     => 'MAX_DAILY_SENDS',
        'send_window_start'   => 'SEND_WINDOW_START',
        'send_window_end'     => 'SEND_WINDOW_END',
        'pipeline_enabled'    => 'PIPELINE_ENABLED',
    ];

    private static ?array $cache = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::$cache === null) {
            self::$cache = self::load();
        }
        return self::$cache[$key] ?? $default ?? self::DEFAULTS[$key] ?? null;
    }

    public static function int(string $key): int
    {
        return (int) self::get($key);
    }

    public static function bool(string $key): bool
    {
        $v = self::get($key);
        return $v === true || $v === 1 || $v === '1' || $v === 'true';
    }

    /** Comma-separated setting → trimmed non-empty list */
    public static function list(string $key): array
    {
        $raw = (string) self::get($key);
        if (trim($raw) === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $raw)), fn($v) => $v !== ''));
    }

    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = self::load();
        }
        return self::$cache;
    }

    /** Call after writing settings so the next read sees them */
    public static function flush(): void
    {
        self::$cache = null;
    }

    private static function load(): array
    {
        $resolved = self::DEFAULTS;

        // Env constants override the hard defaults
        foreach (self::ENV_MAP as $key => $constant) {
            if (defined($constant)) {
                $resolved[$key] = constant($constant);
            }
        }

        // DB settings override env — the panel is the live control surface.
        // A missing table (migration not yet run) must not break the app.
        try {
            foreach ((new LeadEngineRepository())->allSettings() as $key => $value) {
                if ($value !== null && $value !== '') {
                    $resolved[$key] = $value;
                }
            }
        } catch (\Throwable) {
            // keep env/defaults
        }

        return $resolved;
    }

    /** Env-only values that never belong in a DB settings row */
    public static function tokenSecret(): string
    {
        return defined('APPROVAL_TOKEN_SECRET') ? (string) APPROVAL_TOKEN_SECRET : '';
    }

    public static function notifyEmail(): string
    {
        if (defined('ADMIN_NOTIFY_EMAIL') && ADMIN_NOTIFY_EMAIL !== '') {
            return (string) ADMIN_NOTIFY_EMAIL;
        }
        return defined('ALERT_EMAIL') ? (string) ALERT_EMAIL : '';
    }

    public static function emailFrom(): string
    {
        if (defined('EMAIL_FROM') && EMAIL_FROM !== '') {
            return (string) EMAIL_FROM;
        }
        return defined('SMTP_FROM') ? (string) SMTP_FROM : '';
    }

    /** Sender identity required in every outbound message (§11.2) */
    public static function senderIdentity(): string
    {
        return defined('OUTREACH_SENDER_IDENTITY')
            ? (string) OUTREACH_SENDER_IDENTITY
            : 'LandingFlow — landingflow.co.il';
    }

    /** Where an unsubscribe request lands (§11.2, §11.3) */
    public static function unsubscribeUrl(): string
    {
        if (defined('OUTREACH_UNSUBSCRIBE_URL') && OUTREACH_UNSUBSCRIBE_URL !== '') {
            return (string) OUTREACH_UNSUBSCRIBE_URL;
        }
        $base = defined('APP_URL') ? rtrim((string) APP_URL, '/') : '';
        return $base . '/data-deletion';
    }
}
