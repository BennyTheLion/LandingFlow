<?php
namespace App\Services;

use App\Core\Logger;
use App\LeadEngine\DraftWriter;
use App\LeadEngine\HotScore;
use App\LeadEngine\LeadEngineConfig;
use App\LeadEngine\SendGuard;
use App\Repositories\DoNotContactRepository;
use App\Repositories\OutreachRepository;
use App\Repositories\ProspectRepository;

/**
 * OutreachSender — the only code path in the system that actually sends a
 * message to a prospect (spec §9).
 *
 * It cannot be reached by a GET request. The controller only calls send() from a
 * POST handler behind CSRF, after the operator has seen the final preview.
 * Every send re-runs SendGuard immediately beforehand — the guard result shown
 * on the preview page is advisory, this one is authoritative.
 */
class OutreachSender
{
    private OutreachRepository $outreach;
    private ProspectRepository $prospects;
    private DoNotContactRepository $dnc;
    private SendGuard $guard;

    public function __construct(?SendGuard $guard = null)
    {
        $this->outreach = new OutreachRepository();
        $this->prospects = new ProspectRepository();
        $this->dnc = new DoNotContactRepository();
        $this->guard = $guard ?? new SendGuard($this->outreach, $this->dnc);
    }

    /**
     * @return array{sent:bool,blockers:string[],message:string}
     */
    public function send(int $draftId, ?int $userId = null): array
    {
        $draft = $this->outreach->findDraftWithContext($draftId);
        if ($draft === null) {
            return ['sent' => false, 'blockers' => ['הטיוטה לא נמצאה.'], 'message' => 'not_found'];
        }

        // Authoritative guard check — not the one from the preview page
        $check = $this->guard->check($draft);
        if (!$check['allowed']) {
            Logger::warning('leadengine: send blocked by guardrails', [
                'draft_id' => $draftId, 'blockers' => $check['blockers'],
            ]);
            return ['sent' => false, 'blockers' => $check['blockers'], 'message' => 'blocked'];
        }

        $recipient = (string) $draft['prospect_email'];
        $subject = (string) $draft['subject'];
        $body = $this->composeBody($draft);

        $ok = false;
        try {
            $ok = Mailer::send(
                $recipient,
                $subject,
                $body,
                LeadEngineConfig::emailFrom(),
                defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'LandingFlow'
            );
        } catch (\Throwable $e) {
            Logger::error('leadengine: send threw', ['draft_id' => $draftId, 'message' => $e->getMessage()]);
            return ['sent' => false, 'blockers' => ['שליחת המייל נכשלה: ' . $e->getMessage()], 'message' => 'error'];
        }

        if (!$ok) {
            return ['sent' => false, 'blockers' => ['שליחת המייל נכשלה.'], 'message' => 'error'];
        }

        $now = date('Y-m-d H:i:s');
        $this->outreach->updateDraft($draftId, [
            'status'      => 'sent',
            'sent_at'     => $now,
            'approved_at' => $draft['approved_at'] ?? $now,
        ]);
        $this->outreach->addEvent($draftId, 'sent', [
            'to'           => $recipient,
            'by_user_id'   => $userId,
            'channel'      => $draft['channel'] ?? 'email',
            'video_url'    => $draft['video_url'] ?? null,
            'followup_step' => (int) ($draft['followup_step'] ?? 0),
        ]);

        // A follow-up send must not reset the prospect's own status
        if ((int) ($draft['followup_step'] ?? 0) === 0) {
            $this->prospects->setStatus((int) $draft['prospect_id'], 'sent');
        }

        Logger::info('leadengine: outreach sent', [
            'draft_id' => $draftId, 'prospect_id' => $draft['prospect_id'], 'to' => $recipient,
        ]);

        return ['sent' => true, 'blockers' => [], 'message' => 'sent'];
    }

    /**
     * Body as the prospect receives it: the editable draft, the video link, and
     * the mandatory sender identity + opt-out footer (§11.2).
     *
     * The footer is appended here rather than stored, so it stays correct if the
     * configured identity changes and can never be edited away by accident.
     */
    public function composeBody(array $draft): string
    {
        $body = trim((string) ($draft['body'] ?? ''));
        $videoUrl = trim((string) ($draft['video_url'] ?? ''));

        if ($videoUrl !== '') {
            $body .= "\n\nהקלטתי סרטון קצר שמראה את זה על האתר שלכם:\n" . $videoUrl;
        }

        return $body . DraftWriter::footer();
    }

    /**
     * Mark a draft approved. Separate from send() on purpose: approving is a
     * state change, sending is an action.
     *
     * @return array{approved:bool,blockers:string[]}
     */
    public function approve(int $draftId, ?int $userId = null): array
    {
        $draft = $this->outreach->findDraftWithContext($draftId);
        if ($draft === null) {
            return ['approved' => false, 'blockers' => ['הטיוטה לא נמצאה.']];
        }

        $blockers = $this->guard->checkApprovable($draft);
        if ($blockers !== []) {
            return ['approved' => false, 'blockers' => $blockers];
        }

        $this->outreach->updateDraft($draftId, [
            'status'      => 'approved',
            'approved_at' => date('Y-m-d H:i:s'),
        ]);
        $this->prospects->setStatus((int) $draft['prospect_id'], 'approved');
        Logger::info('leadengine: draft approved', ['draft_id' => $draftId, 'by_user_id' => $userId]);

        return ['approved' => true, 'blockers' => []];
    }

    public function reject(int $draftId, string $reason = ''): void
    {
        $draft = $this->outreach->findDraft($draftId);
        if ($draft === null) {
            return;
        }

        $this->outreach->updateDraft($draftId, [
            'status'          => 'rejected',
            'rejected_reason' => $reason !== '' ? mb_substr($reason, 0, 250) : null,
        ]);

        // Only a first-touch rejection closes the prospect
        if ((int) ($draft['followup_step'] ?? 0) === 0) {
            $this->prospects->setStatus((int) $draft['prospect_id'], 'rejected');
        }

        Logger::info('leadengine: draft rejected', ['draft_id' => $draftId, 'reason' => $reason]);
    }

    /**
     * Record a reply. Promotes the prospect into the CRM and cancels every
     * pending follow-up (§9, §2).
     */
    public function recordReply(int $prospectId, string $note = ''): void
    {
        $prospect = $this->prospects->findById($prospectId);
        if ($prospect === null) {
            return;
        }

        $this->prospects->setStatus($prospectId, 'replied');
        $cancelled = $this->outreach->cancelFollowups($prospectId, 'replied');

        // Log the reply against the most recent sent draft
        foreach ($this->outreach->draftsForProspect($prospectId) as $draft) {
            if (($draft['status'] ?? '') === 'sent') {
                $this->outreach->addEvent((int) $draft['id'], 'replied', ['note' => $note]);
                break;
            }
        }

        // A prospect who replied is a real lead — hand it to the CRM (§2)
        if (empty($prospect['crm_lead_id'])) {
            try {
                $leadId = (new \App\Repositories\LeadRepository())->create([
                    'name'          => $prospect['contact_name'] ?: $prospect['business_name'],
                    'email'         => $prospect['email'] ?: null,
                    'phone'         => $prospect['phone'] ?: null,
                    'company'       => $prospect['business_name'],
                    'website'       => $prospect['url'],
                    'source'        => 'other',
                    'source_detail' => 'Lead Engine — ' . ($prospect['domain'] ?? ''),
                    'status'        => 'contacted',
                    'score'         => (int) ($prospect['hot_score'] ?? 0),
                    'interest'      => HotScore::issueLabel((string) ($prospect['primary_issue'] ?? 'none')),
                    'notes'         => trim("הגיע מ-Lead Engine.\n" . $note),
                    'created_at'    => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
                $this->prospects->update($prospectId, ['crm_lead_id' => $leadId]);
                Logger::info('leadengine: prospect promoted to CRM lead', [
                    'prospect_id' => $prospectId, 'lead_id' => $leadId,
                ]);
            } catch (\Throwable $e) {
                Logger::error('leadengine: CRM promotion failed', [
                    'prospect_id' => $prospectId, 'message' => $e->getMessage(),
                ]);
            }
        }

        Logger::info('leadengine: reply recorded', [
            'prospect_id' => $prospectId, 'followups_cancelled' => $cancelled,
        ]);
    }

    /**
     * Unsubscribe request → immediate, unconditional suppression (§11.3).
     */
    public function optOut(int $prospectId, string $reason = 'בקשת הסרה'): void
    {
        $prospect = $this->prospects->findById($prospectId);
        if ($prospect === null) {
            return;
        }

        $this->dnc->add($prospect['domain'], $prospect['email'], $prospect['phone'], $reason);
        $this->prospects->setStatus($prospectId, 'do_not_contact');
        $this->outreach->cancelFollowups($prospectId, 'opted_out');
    }

    /**
     * Turn a due follow-up into a new draft in the approval queue. Follow-ups
     * are never auto-sent (§9).
     *
     * @return int New draft id
     */
    public function createFollowupDraft(array $sentDraft, int $step): int
    {
        $prospectId = (int) $sentDraft['prospect_id'];
        $name = \App\LeadEngine\HtmlSignals::firstName((string) ($sentDraft['contact_name'] ?? ''));
        $greeting = $name !== '' ? "היי {$name}," : 'שלום,';

        [$subject, $body] = match ($step) {
            1 => [
                'ממשיך מהסרטון — ' . ($sentDraft['business_name'] ?? ''),
                $greeting . "\n\nשלחתי לך סרטון קצר לפני כמה ימים על האתר.\n"
                . "רוצה שאשלח את הרשימה הכתובה של מה שמצאתי? זה מסמך אחד, בלי התחייבות.",
            ],
            2 => [
                'דוגמה למתחרה שעושה את זה נכון',
                $greeting . "\n\nמצאתי עסק דומה לשלכם שפתר בדיוק את הבעיה שהצגתי בסרטון.\n"
                . "אשמח לשלוח צילום מסך והסבר קצר איך זה בנוי אצלם.",
            ],
            3 => [
                'סוגר את הקובץ',
                $greeting . "\n\nמניח שזה לא בעדיפות עכשיו, ואני סוגר את הקובץ.\n"
                . "הרשימה שהכנתי שמורה אצלי — אם תרצו אותה בעתיד, שלחו הודעה ואשלח.\n"
                . "בהצלחה!",
            ],
            default => ['', ''],
        };

        if ($subject === '') {
            return 0;
        }

        $draftId = $this->outreach->createDraft([
            'prospect_id'   => $prospectId,
            'audit_id'      => $sentDraft['audit_id'] ?? null,
            'channel'       => 'email',
            'subject'       => $subject,
            'body'          => $body,
            'video_brief'   => "פולואפ #{$step} — לא דורש סרטון חדש.\n"
                             . "אם אתה כן מקליט: התייחס למה שהשתנה מאז הסרטון הראשון.",
            'status'        => 'draft',
            'followup_of'   => (int) $sentDraft['id'],
            'followup_step' => $step,
            'generated_by'  => 'template',
        ]);

        $this->outreach->addEvent((int) $sentDraft['id'], 'followup_' . $step, ['draft_id' => $draftId]);
        return $draftId;
    }

    /**
     * A follow-up needs no new video, so the video_url guard would block it
     * forever. Seed it with the parent's video link.
     */
    public function inheritVideoUrl(int $followupDraftId, int $parentDraftId): void
    {
        $parent = $this->outreach->findDraft($parentDraftId);
        if ($parent !== null && !empty($parent['video_url'])) {
            $this->outreach->updateDraft($followupDraftId, ['video_url' => $parent['video_url']]);
        }
    }
}
