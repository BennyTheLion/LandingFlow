<?php
/**
 * Final send confirmation (spec §9).
 *
 * Reached by GET — from the daily email button or the queue. Rendering this page
 * sends nothing. The send is the POST form at the bottom, which carries a CSRF
 * token and an explicit confirm=send value.
 */
ob_start();
?>
<style>
.cf-page{max-width:760px;margin:0 auto}
.cf-back{font-size:.82rem;color:var(--ink-soft);text-decoration:none;display:inline-block;margin-bottom:14px}
.flash{padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:.85rem}
.flash.success{background:#dcfce7;color:#166534}.flash.error{background:#fef2f2;color:#991b1b}
.cf-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:24px;margin-bottom:18px}
.cf-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:6px}
.cf-head h1{font-size:1.3rem;margin:0}
.cf-sub{font-size:.82rem;color:var(--ink-soft);line-height:1.8;margin-bottom:18px}
.cf-sub a{color:var(--primary)}
.score-box{text-align:center;background:var(--surface-2);border-radius:12px;padding:10px 18px}
.score-num{font-family:var(--font-mono);font-size:1.6rem;font-weight:800;line-height:1}
.score-lbl{font-size:.66rem;color:var(--ink-soft);text-transform:uppercase}
.notice{border-radius:10px;padding:12px 16px;font-size:.82rem;margin-bottom:16px;line-height:1.75}
.notice ul{margin:6px 0 0;padding-inline-start:18px}
.n-block{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
.n-warn{background:#fffbeb;color:#92400e;border:1px solid #fde68a}
.n-info{background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe}
.n-ok{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0}
.preview-label{font-size:.75rem;font-weight:700;color:var(--ink-soft);text-transform:uppercase;margin-bottom:8px}
.envelope{border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:18px}
.env-row{display:flex;gap:10px;padding:9px 14px;border-bottom:1px solid var(--border);font-size:.82rem;background:var(--surface-2)}
.env-row span:first-child{color:var(--ink-soft);min-width:52px;font-weight:600}
.env-body{padding:18px;white-space:pre-wrap;line-height:1.85;font-size:.88rem;background:var(--surface)}
.send-form{background:var(--surface);border:2px solid var(--primary);border-radius:14px;padding:22px}
.send-form.blocked{border-color:var(--border);opacity:.92}
.send-form h3{font-size:.98rem;margin-bottom:6px}
.send-form p{font-size:.81rem;color:var(--ink-soft);margin-bottom:16px;line-height:1.7}
.btn{padding:11px 26px;border-radius:9px;border:none;cursor:pointer;font-family:inherit;font-size:.9rem;font-weight:700}
.btn-send{background:var(--primary);color:#fff}
.btn-send:disabled{background:var(--ink-faint);cursor:not-allowed}
.btn-ghost{background:var(--surface);border:1px solid var(--border);color:var(--ink-soft);text-decoration:none;display:inline-block;font-size:.84rem;padding:10px 20px;border-radius:9px;font-weight:600}
.meta-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;font-size:.78rem;margin-bottom:18px}
@media(min-width:700px){.meta-grid{grid-template-columns:repeat(4,1fr)}}
.meta-grid div{background:var(--surface-2);border-radius:8px;padding:8px 10px}
.meta-grid b{display:block;font-family:var(--font-mono);font-size:.95rem;color:var(--ink)}
.meta-grid span{color:var(--ink-soft);font-size:.71rem}
</style>

<div class="cf-page">
<a href="<?= $url('admin/lead-engine/queue') ?>" class="cf-back">← חזרה לתור האישורים</a>

<?php if (!empty($flashMsg)): ?>
  <div class="flash <?= htmlspecialchars($flashMsg['type']) ?>"><?= htmlspecialchars($flashMsg['message']) ?></div>
<?php endif; ?>

<?php
$status = (string) ($draft['status'] ?? '');
$alreadySent = $status === 'sent';
$score = (int) ($draft['hot_score'] ?? 0);
?>

<div class="cf-card">
  <div class="cf-head">
    <div>
      <h1><?= htmlspecialchars((string) ($draft['business_name'] ?? '')) ?></h1>
      <div class="cf-sub">
        <a href="https://<?= htmlspecialchars((string) $draft['domain']) ?>" target="_blank" rel="noopener noreferrer nofollow" dir="ltr"><?= htmlspecialchars((string) $draft['domain']) ?></a>
        · הבעיה: <strong><?= htmlspecialchars($issueLabel) ?></strong><br>
        איש קשר: <?= htmlspecialchars((string) ($draft['contact_name'] ?: '— לא נמצא')) ?>
        · סטטוס טיוטה: <strong><?= htmlspecialchars($status) ?></strong>
      </div>
    </div>
    <div class="score-box">
      <div class="score-num"><?= $score ?></div>
      <div class="score-lbl">hot score</div>
    </div>
  </div>

  <div class="meta-grid">
    <div><b><?= $draft['perf_mobile'] ?? '?' ?></b><span>מהירות מובייל<?= ($draft['perf_source'] ?? '') === 'heuristic' ? ' (הערכה)' : '' ?></span></div>
    <div><b><?= $draft['a11y_score'] ?? '?' ?></b><span>נגישות</span></div>
    <div><b><?= $draft['seo_score'] ?? '?' ?></b><span>SEO</span></div>
    <div><b><?= !empty($draft['has_analytics']) ? 'יש' : 'אין' ?></b><span>Analytics</span></div>
  </div>

  <?php if ($tokenState === 'valid'): ?>
    <div class="notice n-ok">✅ הקישור מהמייל אומת. הטוקן נוצל וכבר לא ניתן לשימוש חוזר.</div>
  <?php elseif ($tokenState === 'invalid'): ?>
    <div class="notice n-warn">
      ⚠ הקישור מהמייל אינו תקף יותר — הוא פג (72 שעות), כבר נוצל, או הוחלף בקישור חדש.
      אתה מחובר לפאנל, כך שניתן להמשיך ולשלוח מכאן.
    </div>
  <?php elseif ($tokenState === 'error'): ?>
    <div class="notice n-block">
      ⚠ לא ניתן לאמת את הטוקן — סביר ש-APPROVAL_TOKEN_SECRET חסר בקונפיגורציה.
    </div>
  <?php endif; ?>

  <?php if ($alreadySent): ?>
    <div class="notice n-info">
      ההודעה הזו כבר נשלחה ב-<?= htmlspecialchars((string) ($draft['sent_at'] ?? '')) ?>. לא תישלח שוב.
    </div>
  <?php endif; ?>

  <?php if (!empty($guard['blockers'])): ?>
    <div class="notice n-block"><strong>לא ניתן לשלוח:</strong>
      <ul><?php foreach ($guard['blockers'] as $b): ?><li><?= htmlspecialchars($b) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>
  <?php if (!empty($guard['warnings'])): ?>
    <div class="notice n-warn"><strong>לתשומת לבך:</strong>
      <ul><?php foreach ($guard['warnings'] as $w): ?><li><?= htmlspecialchars($w) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <div class="preview-label">תצוגה מקדימה סופית — כך ההודעה תיראה לנמען</div>
  <div class="envelope">
    <div class="env-row"><span>אל:</span><span dir="ltr"><?= htmlspecialchars((string) ($draft['prospect_email'] ?? '—')) ?></span></div>
    <div class="env-row"><span>נושא:</span><span><?= htmlspecialchars((string) ($draft['subject'] ?? '')) ?></span></div>
    <div class="env-body"><?= htmlspecialchars($finalBody) ?></div>
  </div>
</div>

<?php if (!$alreadySent): ?>
<div class="send-form <?= empty($guard['allowed']) ? 'blocked' : '' ?>">
  <h3><?= !empty($guard['allowed']) ? '📤 שלח עכשיו' : '🔒 השליחה חסומה' ?></h3>
  <p>
    <?php if (!empty($guard['allowed'])): ?>
      זו הפעולה שמוציאה את ההודעה בפועל. אין ביטול לאחר השליחה.
    <?php else: ?>
      תקן את החוסמים שלמעלה כדי לאפשר שליחה. הבדיקה מתבצעת שוב ברגע השליחה.
    <?php endif; ?>
  </p>
  <form method="POST" action="<?= $url('admin/lead-engine/drafts/' . (int) $draft['id'] . '/send') ?>"
        onsubmit="return confirm('לשלוח את ההודעה ל<?= htmlspecialchars((string) ($draft['prospect_email'] ?? '')) ?>?')">
    <?= $csrf() ?>
    <input type="hidden" name="confirm" value="send">
    <button type="submit" class="btn btn-send" <?= empty($guard['allowed']) ? 'disabled' : '' ?>>
      שלח עכשיו
    </button>
    <a href="<?= $url('admin/lead-engine/queue#draft-' . (int) $draft['id']) ?>" class="btn-ghost">חזור לעריכה</a>
  </form>
</div>
<?php endif; ?>

<?php if (!empty($draft['video_brief'])): ?>
<div class="cf-card" style="margin-top:18px">
  <div class="preview-label">🎬 תסריט לסרטון</div>
  <div style="background:#111827;color:#E5E7EB;border-radius:10px;padding:14px;font-family:var(--font-mono);font-size:.74rem;line-height:1.85;white-space:pre-wrap"><?= htmlspecialchars((string) $draft['video_brief']) ?></div>
</div>
<?php endif; ?>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../admin/layout.php'; ?>
