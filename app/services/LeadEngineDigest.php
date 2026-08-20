<?php
namespace App\Services;

use App\Core\Logger;
use App\LeadEngine\ApprovalToken;
use App\LeadEngine\HotScore;
use App\LeadEngine\LeadEngineConfig;
use App\Repositories\OutreachRepository;

/**
 * LeadEngineDigest — the 08:00 Sun-Thu approval email (spec §9).
 *
 * ⚠ Security rule from §9: a button in an email NEVER sends. Mail scanners and
 * inbox previews prefetch links, which would cause unintended sends. Every link
 * here is a GET that opens the panel's preview page; the send is a separate POST
 * with a CSRF token from that page.
 */
class LeadEngineDigest
{
    private OutreachRepository $outreach;

    public function __construct()
    {
        $this->outreach = new OutreachRepository();
    }

    /**
     * @param bool $dryRun Build the email and return it without sending
     * @return array{sent:bool,count:int,subject:string,body:string,reason:string}
     */
    public function sendDaily(bool $dryRun = false): array
    {
        $drafts = $this->outreach->queue(20);
        $pending = array_values(array_filter($drafts, fn($d) => ($d['status'] ?? '') === 'draft'));

        if ($pending === []) {
            return ['sent' => false, 'count' => 0, 'subject' => '', 'body' => '', 'reason' => 'nothing_pending'];
        }

        $recipient = LeadEngineConfig::notifyEmail();
        if ($recipient === '') {
            Logger::warning('leadengine: no ADMIN_NOTIFY_EMAIL configured, digest not sent');
            return ['sent' => false, 'count' => count($pending), 'subject' => '', 'body' => '', 'reason' => 'no_recipient'];
        }

        $avgScore = (int) round(array_sum(array_map(fn($d) => (int) ($d['hot_score'] ?? 0), $pending)) / count($pending));
        $subject = sprintf('%d לידים מוכנים לאישור — hot score ממוצע %d', count($pending), $avgScore);
        $body = $this->buildBody($pending);

        if ($dryRun) {
            return ['sent' => false, 'count' => count($pending), 'subject' => $subject, 'body' => $body, 'reason' => 'dry_run'];
        }

        $ok = Mailer::send($recipient, $subject, $body, LeadEngineConfig::emailFrom(), 'LandingFlow Lead Engine');
        Logger::info('leadengine: daily digest sent', ['to' => $recipient, 'count' => count($pending), 'ok' => $ok]);

        return [
            'sent' => (bool) $ok, 'count' => count($pending),
            'subject' => $subject, 'body' => $body, 'reason' => $ok ? 'sent' : 'send_failed',
        ];
    }

    /**
     * One card per lead: business, score, issue, contact, the draft, and links
     * into the panel. Plain text — it goes to one inbox (the owner's) and stays
     * readable in the mail log.
     */
    private function buildBody(array $drafts): string
    {
        $base = defined('APP_URL') ? rtrim((string) APP_URL, '/') : '';
        $lines = [];

        $lines[] = sprintf('%d לידים ממתינים לאישור.', count($drafts));
        $lines[] = '';
        $lines[] = 'לכל ליד: הקלט סרטון, הדבק את הקישור בפאנל, ואשר.';
        $lines[] = 'שים לב: הקישורים כאן פותחים את הפאנל בלבד. שום קישור במייל לא שולח הודעה.';
        $lines[] = str_repeat('=', 60);

        foreach ($drafts as $index => $draft) {
            $draftId = (int) $draft['id'];
            $issue = (string) ($draft['primary_issue'] ?? 'none');

            // A fresh single-use token per digest, valid 72h (§9)
            $confirmUrl = $base . '/admin/lead-engine/queue#draft-' . $draftId;
            try {
                $token = ApprovalToken::issue($draftId);
                $this->outreach->updateDraft($draftId, [
                    'approval_token'   => $token['hash'],
                    'token_expires_at' => $token['expires_at'],
                    'token_used_at'    => null,
                ]);
                $confirmUrl = $base . '/admin/lead-engine/drafts/' . $draftId . '/confirm?token=' . $token['token'];
            } catch (\Throwable $e) {
                // Missing APPROVAL_TOKEN_SECRET — fall back to a plain panel link
                Logger::error('leadengine: could not issue approval token', [
                    'draft_id' => $draftId, 'message' => $e->getMessage(),
                ]);
            }

            $lines[] = '';
            $lines[] = sprintf('%d) %s  —  hot score %d',
                $index + 1,
                (string) ($draft['business_name'] ?? ''),
                (int) ($draft['hot_score'] ?? 0)
            );
            $lines[] = '   דומיין: ' . ($draft['domain'] ?? '');
            $lines[] = '   הבעיה: ' . HotScore::issueLabel($issue);
            $lines[] = '   איש קשר: ' . (($draft['contact_name'] ?? '') !== ''
                ? $draft['contact_name']
                : '— לא נמצא (שיעור תגובה נמוך)');
            $lines[] = '   מייל: ' . ($draft['prospect_email'] ?? '—');
            if (!empty($draft['spends_on_ads'])) {
                $lines[] = '   ★ מפרסם במודעות — ליד חם';
            }
            if (($draft['perf_source'] ?? '') === 'heuristic') {
                $lines[] = '   ⚠ ציון המהירות הוא הערכה מקומית, לא PageSpeed';
            }
            $lines[] = '';
            $lines[] = '   --- טיוטה (' . ($draft['generated_by'] ?? 'template') . ') ---';
            $lines[] = '   נושא: ' . ($draft['subject'] ?? '');
            foreach (explode("\n", (string) ($draft['body'] ?? '')) as $bodyLine) {
                $lines[] = '   ' . $bodyLine;
            }
            $lines[] = '';
            $lines[] = '   👁 פתח בפאנל (תצוגה מקדימה + אישור):';
            $lines[] = '   ' . $confirmUrl;
            $lines[] = '   ' . str_repeat('-', 56);
        }

        $lines[] = '';
        $lines[] = 'תור האישורים המלא: ' . $base . '/admin/lead-engine/queue';
        $lines[] = 'לעצור הכל: ' . $base . '/admin/lead-engine/settings';

        return implode("\n", $lines);
    }

    /**
     * Turn due follow-ups into approval-queue drafts (§9). They appear in
     * tomorrow's digest like any other draft — nothing is auto-sent.
     *
     * @return int Number of follow-up drafts created
     */
    public function queueFollowups(): int
    {
        $sender = new OutreachSender();
        $created = 0;

        foreach ($this->outreach->followupsDue() as $sentDraft) {
            // A reply cancels the sequence
            if (($sentDraft['prospect_status'] ?? '') === 'replied') {
                continue;
            }

            $step = (int) $sentDraft['due_step'];
            try {
                $draftId = $sender->createFollowupDraft($sentDraft, $step);
                if ($draftId > 0) {
                    $sender->inheritVideoUrl($draftId, (int) $sentDraft['id']);
                    $created++;
                }
            } catch (\Throwable $e) {
                Logger::error('leadengine: follow-up creation failed', [
                    'draft_id' => $sentDraft['id'], 'step' => $step, 'message' => $e->getMessage(),
                ]);
            }
        }

        if ($created > 0) {
            Logger::info('leadengine: follow-up drafts queued', ['count' => $created]);
        }
        return $created;
    }
}
