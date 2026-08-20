<?php
namespace App\Repositories;

use App\Core\Database;

/**
 * OutreachRepository — drafts + their event timeline (spec §4).
 */
class OutreachRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    // ---------------------------------------------------------------- drafts

    public function findDraft(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM outreach_drafts WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /** Draft joined with the prospect and its audit — what every screen needs */
    public function findDraftWithContext(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT d.*,
                    p.business_name, p.domain, p.url, p.city, p.niche,
                    p.contact_name, p.contact_role, p.email AS prospect_email,
                    p.phone AS prospect_phone, p.spends_on_ads, p.hot_score,
                    p.status AS prospect_status,
                    a.perf_mobile, a.a11y_score, a.seo_score, a.has_analytics,
                    a.has_meta_pixel, a.has_click_to_call, a.has_ssl,
                    a.has_accessibility_statement, a.perf_source
             FROM outreach_drafts d
             JOIN prospects p ON p.id = d.prospect_id
             LEFT JOIN prospect_audits a ON a.id = d.audit_id
             WHERE d.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * The approval queue (§10) — the core screen, ordered by hot_score.
     */
    public function queue(int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT d.*,
                    p.business_name, p.domain, p.url, p.city, p.niche,
                    p.contact_name, p.email AS prospect_email, p.spends_on_ads,
                    p.hot_score, p.primary_issue,
                    a.perf_mobile, a.a11y_score, a.seo_score, a.has_analytics,
                    a.has_click_to_call, a.perf_source
             FROM outreach_drafts d
             JOIN prospects p ON p.id = d.prospect_id
             LEFT JOIN prospect_audits a ON a.id = d.audit_id
             WHERE d.status IN ('draft', 'approved')
             ORDER BY p.hot_score DESC, d.created_at ASC
             LIMIT " . (int) $limit
        );
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function draftsForProspect(int $prospectId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM outreach_drafts WHERE prospect_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$prospectId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** True when this prospect already has an unsent or sent first-touch draft */
    public function hasOpenDraft(int $prospectId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM outreach_drafts
             WHERE prospect_id = ? AND status IN ('draft', 'approved', 'sent')"
        );
        $stmt->execute([$prospectId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function createDraft(array $data): int
    {
        $cols = implode('`, `', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $this->pdo->prepare("INSERT INTO outreach_drafts (`$cols`) VALUES ($placeholders)")
            ->execute(array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    public function updateDraft(int $id, array $data): void
    {
        if ($data === []) {
            return;
        }
        $set = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
        $vals = array_values($data);
        $vals[] = $id;
        $this->pdo->prepare("UPDATE outreach_drafts SET $set, updated_at = NOW() WHERE id = ?")
            ->execute($vals);
    }

    /**
     * Consumes a single-use approval token (§9).
     *
     * Conditional UPDATE rather than SELECT-then-UPDATE so two concurrent
     * clicks cannot both win: exactly one gets rowCount() === 1.
     */
    public function consumeToken(int $draftId, string $tokenHash): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE outreach_drafts
             SET token_used_at = NOW()
             WHERE id = ? AND approval_token = ? AND token_used_at IS NULL
               AND token_expires_at >= NOW()"
        );
        $stmt->execute([$draftId, $tokenHash]);
        return $stmt->rowCount() === 1;
    }

    /** Expire stale drafts so the queue does not fill with dead leads */
    public function expireStaleDrafts(int $days = 14): int
    {
        $cutoff = gmdate('Y-m-d H:i:s', time() - $days * 86400);
        $stmt = $this->pdo->prepare(
            "UPDATE outreach_drafts SET status = 'expired', updated_at = NOW()
             WHERE status = 'draft' AND created_at <= ?"
        );
        $stmt->execute([$cutoff]);
        return $stmt->rowCount();
    }

    // ------------------------------------------------------------ send limits

    /** Sends completed since local midnight — feeds the daily cap (§9) */
    public function sentTodayCount(): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM outreach_drafts WHERE status = 'sent' AND sent_at >= ?"
        );
        $stmt->execute([date('Y-m-d 00:00:00')]);
        return (int) $stmt->fetchColumn();
    }

    public function lastSentAt(): ?string
    {
        $value = $this->pdo->query("SELECT MAX(sent_at) FROM outreach_drafts WHERE status = 'sent'")
            ->fetchColumn();
        return is_string($value) && $value !== '' ? $value : null;
    }

    // ---------------------------------------------------------------- events

    public function addEvent(int $draftId, string $type, array $meta = []): void
    {
        $this->pdo->prepare(
            "INSERT INTO outreach_events (draft_id, type, meta_json) VALUES (?, ?, ?)"
        )->execute([
            $draftId,
            $type,
            $meta === [] ? null : json_encode($meta, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function eventsForProspect(int $prospectId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT e.*, d.channel, d.subject, d.followup_step
             FROM outreach_events e
             JOIN outreach_drafts d ON d.id = e.draft_id
             WHERE d.prospect_id = ?
             ORDER BY e.at DESC"
        );
        $stmt->execute([$prospectId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function hasEvent(int $draftId, string $type): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM outreach_events WHERE draft_id = ? AND type = ?");
        $stmt->execute([$draftId, $type]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // --------------------------------------------------------------- followups

    /**
     * Sent drafts whose prospect has not replied and whose next follow-up step
     * is due (§9: day 3, 7, 14). Returns rows annotated with `due_step`.
     *
     * Follow-ups become approval-queue tasks — they are never auto-sent.
     */
    public function followupsDue(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT d.*, p.business_name, p.contact_name, p.email AS prospect_email,
                    p.domain, p.status AS prospect_status
             FROM outreach_drafts d
             JOIN prospects p ON p.id = d.prospect_id
             WHERE d.status = 'sent'
               AND d.followup_step = 0
               AND p.status = 'sent'
               AND d.sent_at IS NOT NULL
             ORDER BY d.sent_at ASC"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $schedule = [1 => 3, 2 => 7, 3 => 14];
        $due = [];
        foreach ($rows as $row) {
            $sentAt = strtotime((string) $row['sent_at']);
            if ($sentAt === false) {
                continue;
            }
            $daysSince = (int) floor((time() - $sentAt) / 86400);

            foreach ($schedule as $step => $dayThreshold) {
                if ($daysSince < $dayThreshold) {
                    continue;
                }
                if ($this->hasEvent((int) $row['id'], 'followup_' . $step)) {
                    continue;
                }
                $row['due_step'] = $step;
                $row['days_since_sent'] = $daysSince;
                $due[] = $row;
                break; // one step at a time
            }
        }
        return $due;
    }

    /** A reply cancels every pending follow-up (§9) */
    public function cancelFollowups(int $prospectId, string $reason = 'replied'): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM outreach_drafts
             WHERE prospect_id = ? AND followup_step > 0 AND status IN ('draft', 'approved')"
        );
        $stmt->execute([$prospectId]);
        $ids = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'id');

        foreach ($ids as $id) {
            $this->updateDraft((int) $id, ['status' => 'rejected', 'rejected_reason' => $reason]);
            $this->addEvent((int) $id, 'cancelled', ['reason' => $reason]);
        }
        return count($ids);
    }

    // ---------------------------------------------------------------- metrics

    /** Reply rate by source and by niche — the monthly review question (§14) */
    public function replyRateBy(string $dimension): array
    {
        $column = $dimension === 'niche' ? 'niche' : 'source';
        $stmt = $this->pdo->prepare(
            "SELECT p.`$column` AS bucket,
                    COUNT(DISTINCT CASE WHEN d.status = 'sent' THEN d.id END) AS sent,
                    COUNT(DISTINCT CASE WHEN p.status = 'replied' THEN p.id END) AS replied
             FROM prospects p
             LEFT JOIN outreach_drafts d ON d.prospect_id = p.id
             GROUP BY p.`$column`
             HAVING sent > 0
             ORDER BY sent DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function sentThisWeek(): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM outreach_drafts WHERE status = 'sent' AND sent_at >= ?"
        );
        $stmt->execute([date('Y-m-d 00:00:00', strtotime('-7 days'))]);
        return (int) $stmt->fetchColumn();
    }

    public function countByStatus(): array
    {
        $rows = $this->pdo->query("SELECT status, COUNT(*) AS n FROM outreach_drafts GROUP BY status")
            ->fetchAll(\PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $row) {
            $out[$row['status']] = (int) $row['n'];
        }
        return $out;
    }
}
