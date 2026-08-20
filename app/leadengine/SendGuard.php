<?php
namespace App\LeadEngine;

use App\Repositories\DoNotContactRepository;
use App\Repositories\OutreachRepository;

/**
 * SendGuard — the guardrails from spec §9, evaluated immediately before every
 * actual send.
 *
 *   - video_url is mandatory (no send without a video)
 *   - max 8 sends/day, minimum 5 minutes apart
 *   - Sun–Thu, 09:00–18:00 Israel time only
 *   - second do_not_contact check
 *   - global "stop everything" switch
 *
 * Every check runs and all failures are collected, so the panel can show the
 * full reason list instead of one error at a time.
 */
class SendGuard
{
    private OutreachRepository $outreach;
    private DoNotContactRepository $dnc;

    public function __construct(?OutreachRepository $outreach = null, ?DoNotContactRepository $dnc = null)
    {
        $this->outreach = $outreach ?? new OutreachRepository();
        $this->dnc = $dnc ?? new DoNotContactRepository();
    }

    /**
     * @param array $draft Row from OutreachRepository::findDraftWithContext()
     * @return array{allowed:bool,blockers:string[],warnings:string[]}
     */
    public function check(array $draft, ?\DateTimeImmutable $now = null): array
    {
        $blockers = [];
        $warnings = [];
        $now ??= new \DateTimeImmutable('now', new \DateTimeZone('Asia/Jerusalem'));

        // --- kill switch ----------------------------------------------------
        if (LeadEngineConfig::bool('sending_halted')) {
            $blockers[] = 'כל השליחות מוקפאות — "עצור הכל" מופעל בהגדרות.';
        }

        // --- draft state ----------------------------------------------------
        $status = (string) ($draft['status'] ?? '');
        if (!in_array($status, ['draft', 'approved'], true)) {
            $blockers[] = 'הטיוטה בסטטוס "' . $status . '" ולא ניתן לשלוח אותה.';
        }

        // --- video is mandatory (§9) ----------------------------------------
        if (trim((string) ($draft['video_url'] ?? '')) === '') {
            $blockers[] = 'חסר קישור לסרטון — אין שליחה בלי סרטון.';
        } elseif (!filter_var($draft['video_url'], FILTER_VALIDATE_URL)) {
            $blockers[] = 'קישור הסרטון אינו כתובת תקינה.';
        }

        // --- recipient ------------------------------------------------------
        $recipient = trim((string) ($draft['prospect_email'] ?? ''));
        if ($recipient === '') {
            $blockers[] = 'אין כתובת מייל לנמען.';
        } elseif (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $blockers[] = 'כתובת המייל של הנמען אינה תקינה.';
        }

        if (trim((string) ($draft['body'] ?? '')) === '') {
            $blockers[] = 'גוף ההודעה ריק.';
        }
        if (trim((string) ($draft['subject'] ?? '')) === '' && ($draft['channel'] ?? 'email') === 'email') {
            $blockers[] = 'חסרה שורת נושא.';
        }

        // --- second do_not_contact check (§9) -------------------------------
        if ($this->dnc->isBlocked($draft['domain'] ?? null, $recipient ?: null, $draft['prospect_phone'] ?? null)) {
            $blockers[] = 'הנמען נמצא ברשימת do-not-contact.';
        }

        // --- WhatsApp cold outreach (§11.4) ---------------------------------
        if (($draft['channel'] ?? '') === 'whatsapp') {
            $blockers[] = 'ערוץ וואטסאפ חסום לפנייה קרה — ראה §11.4 במפרט.';
        }

        // --- send window ----------------------------------------------------
        // ISO-8601: 1=Mon … 7=Sun. The Israeli work week is Sun–Thu, so Friday
        // (5) and Saturday (6) are out.
        $weekday = (int) $now->format('N');
        if ($weekday === 5 || $weekday === 6) {
            $blockers[] = 'מחוץ לחלון השליחה — שליחה רק בימים א׳–ה׳.';
        }

        $start = (string) LeadEngineConfig::get('send_window_start');
        $end = (string) LeadEngineConfig::get('send_window_end');
        $current = $now->format('H:i');
        if ($current < $start || $current >= $end) {
            $blockers[] = "מחוץ לחלון השליחה ({$start}–{$end} שעון ישראל). כרגע {$current}.";
        }

        // --- rate limits ----------------------------------------------------
        $maxDaily = LeadEngineConfig::int('max_daily_sends');
        $sentToday = $this->outreach->sentTodayCount();
        if ($sentToday >= $maxDaily) {
            $blockers[] = "הגעת למקסימום השליחות היומי ({$sentToday}/{$maxDaily}).";
        } elseif ($sentToday >= $maxDaily - 2) {
            $warnings[] = "נשלחו {$sentToday} מתוך {$maxDaily} היום.";
        }

        $minGap = LeadEngineConfig::int('min_minutes_between_sends');
        $lastSentAt = $this->outreach->lastSentAt();
        if ($lastSentAt !== null && $minGap > 0) {
            $elapsed = time() - (strtotime($lastSentAt) ?: 0);
            if ($elapsed < $minGap * 60) {
                $wait = (int) ceil(($minGap * 60 - $elapsed) / 60);
                $blockers[] = "השליחה הקודמת הייתה לפני פחות מ-{$minGap} דקות. המתן עוד ~{$wait} דק׳.";
            }
        }

        // --- soft quality warnings ------------------------------------------
        if (trim((string) ($draft['contact_name'] ?? '')) === '') {
            $warnings[] = 'אין שם איש קשר — שיעור התגובה יהיה נמוך משמעותית.';
        }
        if (($draft['perf_source'] ?? '') === 'heuristic') {
            $warnings[] = 'ציון המהירות הוא הערכה מקומית ולא PageSpeed — אל תצטט אותו כמספר PageSpeed.';
        }

        return [
            'allowed'  => $blockers === [],
            'blockers' => $blockers,
            'warnings' => $warnings,
        ];
    }

    /**
     * Approval-time subset: enforces what must be true to mark a draft approved,
     * without the time-window and rate checks (those belong at send time).
     *
     * @return string[] Blocking reasons; empty means approvable
     */
    public function checkApprovable(array $draft): array
    {
        $blockers = [];

        if (trim((string) ($draft['video_url'] ?? '')) === '') {
            $blockers[] = 'חסר קישור לסרטון — אין אישור בלי סרטון.';
        }
        if (trim((string) ($draft['body'] ?? '')) === '') {
            $blockers[] = 'גוף ההודעה ריק.';
        }
        if (($draft['status'] ?? '') !== 'draft') {
            $blockers[] = 'ניתן לאשר רק טיוטה בסטטוס draft.';
        }
        if ($this->dnc->isBlocked(
            $draft['domain'] ?? null,
            $draft['prospect_email'] ?? null,
            $draft['prospect_phone'] ?? null
        )) {
            $blockers[] = 'הנמען נמצא ברשימת do-not-contact.';
        }

        return $blockers;
    }
}
