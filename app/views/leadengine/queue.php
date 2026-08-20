<?php
/**
 * The approval queue — the core screen (spec §10).
 *
 * Note there is no "send" button here: approving and sending are separate steps.
 * Sending happens only on the confirm page, behind a POST. See §9.
 */
ob_start();
?>
<style>
.le-page{max-width:1100px}
.le-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px}
.le-head h1{margin:0;font-size:1.5rem}
.le-nav{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px}
.le-nav a{padding:7px 14px;border-radius:8px;font-size:.82rem;font-weight:600;text-decoration:none;background:var(--surface);border:1px solid var(--border);color:var(--ink-soft)}
.le-nav a.active,.le-nav a:hover{background:var(--primary);color:#fff;border-color:var(--primary)}
.flash{padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:.85rem}
.flash.success{background:#dcfce7;color:#166534}.flash.error{background:#fef2f2;color:#991b1b}
.btn{padding:7px 15px;border-radius:8px;border:none;cursor:pointer;font-family:inherit;font-size:.82rem;font-weight:600;text-decoration:none;display:inline-block}
.btn-primary{background:var(--primary);color:#fff}
.btn-success{background:var(--success);color:#fff}
.btn-danger{background:var(--danger);color:#fff}
.btn-ghost{background:var(--surface);border:1px solid var(--border);color:var(--ink-soft)}
.btn:disabled{opacity:.45;cursor:not-allowed}
.card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:20px;margin-bottom:18px}
.card.approved{border-color:var(--success);box-shadow:0 0 0 1px rgba(22,163,74,.15)}
.card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:14px}
.card-title{font-size:1.08rem;font-weight:800;margin:0 0 4px}
.card-meta{font-size:.79rem;color:var(--ink-soft);line-height:1.7}
.card-meta a{color:var(--primary)}
.score-box{text-align:center;background:var(--surface-2);border-radius:12px;padding:10px 16px;min-width:82px}
.score-num{font-family:var(--font-mono);font-size:1.55rem;font-weight:800;line-height:1}
.score-lbl{font-size:.66rem;color:var(--ink-soft);text-transform:uppercase;margin-top:2px}
.s-hot{color:var(--danger)}.s-warm{color:var(--warning)}.s-cool{color:var(--ink-soft)}
.tags{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px}
.tag{font-size:.71rem;font-weight:700;padding:3px 10px;border-radius:100px;background:var(--surface-2);color:var(--ink-soft)}
.tag-issue{background:rgba(220,38,38,.1);color:var(--danger)}
.tag-ads{background:rgba(245,158,11,.16);color:#92400e}
.tag-llm{background:rgba(37,99,235,.1);color:var(--primary)}
.tag-noname{background:rgba(220,38,38,.08);color:var(--danger)}
.tag-followup{background:rgba(14,165,233,.12);color:var(--info)}
.metrics{display:flex;gap:14px;flex-wrap:wrap;font-size:.76rem;color:var(--ink-soft);margin-bottom:14px;padding:10px 12px;background:var(--surface-2);border-radius:8px}
.metrics b{font-family:var(--font-mono);color:var(--ink)}
.split{display:grid;grid-template-columns:1fr;gap:16px}
@media(min-width:900px){.split{grid-template-columns:1fr 1fr}}
.field{margin-bottom:12px}
.field label{display:block;font-size:.75rem;font-weight:700;color:var(--ink-soft);margin-bottom:5px}
.field input,.field textarea{width:100%;padding:9px 11px;border:1px solid var(--border);border-radius:8px;font-family:inherit;font-size:.85rem;background:var(--bg)}
.field textarea{resize:vertical;line-height:1.7}
.field input:focus,.field textarea:focus{outline:2px solid var(--primary);outline-offset:-1px;border-color:var(--primary)}
.brief{background:#111827;color:#E5E7EB;border-radius:10px;padding:14px;font-family:var(--font-mono);font-size:.74rem;line-height:1.85;white-space:pre-wrap;max-height:340px;overflow:auto;direction:rtl}
.brief-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px}
.brief-head span{font-size:.75rem;font-weight:700;color:var(--ink-soft)}
.copy-btn{font-size:.7rem;padding:3px 10px;border-radius:6px;border:1px solid var(--border);background:var(--surface);cursor:pointer;font-family:inherit;color:var(--ink-soft)}
.guard{border-radius:8px;padding:10px 12px;font-size:.78rem;margin-bottom:12px;line-height:1.7}
.guard-block{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
.guard-warn{background:#fffbeb;color:#92400e;border:1px solid #fde68a}
.guard ul{margin:4px 0 0;padding-inline-start:18px}
.actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center;padding-top:14px;border-top:1px solid var(--border);margin-top:4px}
.actions form{margin:0;display:inline}
.hint{font-size:.73rem;color:var(--ink-faint)}
.empty-state{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:48px 24px;text-align:center;color:var(--ink-soft)}
.empty-state h3{font-size:1.05rem;margin-bottom:8px;color:var(--ink)}
</style>

<div class="le-page">
<div class="le-head"><h1>✅ תור אישורים</h1>
  <span class="hint"><?= count($drafts) ?> טיוטות · מסודר לפי hot score</span>
</div>

<div class="le-nav">
  <a href="<?= $url('admin/lead-engine') ?>">דשבורד</a>
  <a href="<?= $url('admin/lead-engine/queue') ?>" class="active">תור אישורים</a>
  <a href="<?= $url('admin/lead-engine/prospects') ?>">כל הלידים</a>
  <a href="<?= $url('admin/lead-engine/runs') ?>">הרצות</a>
  <a href="<?= $url('admin/lead-engine/settings') ?>">הגדרות</a>
</div>

<?php if (!empty($flashMsg)): ?>
  <div class="flash <?= htmlspecialchars($flashMsg['type']) ?>"><?= htmlspecialchars($flashMsg['message']) ?></div>
<?php endif; ?>

<?php if (empty($drafts)): ?>
  <div class="empty-state">
    <h3>אין טיוטות ממתינות</h3>
    <p>הרץ את הצינור מהדשבורד, או הוסף ליד ידנית מ"כל הלידים".</p>
  </div>
<?php endif; ?>

<?php
$issueLabels = [
  'broken_form' => 'טופס לא עובד', 'no_analytics_with_ads' => 'מפרסם בלי מעקב',
  'slow_mobile' => 'איטי במובייל', 'no_accessibility' => 'אין הצהרת נגישות',
  'no_click_to_call' => 'אין click-to-call', 'weak_seo' => 'SEO חלש', 'none' => '—',
];
foreach ($drafts as $d):
  $id = (int) $d['id'];
  $score = (int) ($d['hot_score'] ?? 0);
  $scoreClass = $score >= 75 ? 's-hot' : ($score >= 55 ? 's-warm' : 's-cool');
  $guard = $d['guard'];
  $isApproved = ($d['status'] ?? '') === 'approved';
  $hasVideo = trim((string) ($d['video_url'] ?? '')) !== '';
  $followupStep = (int) ($d['followup_step'] ?? 0);
?>
<div class="card <?= $isApproved ? 'approved' : '' ?>" id="draft-<?= $id ?>">
  <div class="card-top">
    <div>
      <h2 class="card-title"><?= htmlspecialchars((string) $d['business_name']) ?></h2>
      <div class="card-meta">
        <a href="https://<?= htmlspecialchars((string) $d['domain']) ?>" target="_blank" rel="noopener noreferrer nofollow" dir="ltr"><?= htmlspecialchars((string) $d['domain']) ?></a>
        <?php if (!empty($d['city'])): ?> · <?= htmlspecialchars((string) $d['city']) ?><?php endif; ?><br>
        איש קשר:
        <?php if (!empty($d['contact_name'])): ?>
          <strong><?= htmlspecialchars((string) $d['contact_name']) ?></strong>
        <?php else: ?>
          <span style="color:var(--danger)">לא נמצא</span>
        <?php endif; ?>
        · <span dir="ltr"><?= htmlspecialchars((string) ($d['prospect_email'] ?? '—')) ?></span><br>
        <a href="<?= $url('admin/lead-engine/prospects/' . (int) $d['prospect_id']) ?>">כרטיס הליד →</a>
      </div>
    </div>
    <div class="score-box">
      <div class="score-num <?= $scoreClass ?>"><?= $score ?></div>
      <div class="score-lbl">hot score</div>
    </div>
  </div>

  <div class="tags">
    <span class="tag tag-issue"><?= $issueLabels[(string) ($d['primary_issue'] ?? 'none')] ?? '—' ?></span>
    <?php if (!empty($d['spends_on_ads'])): ?><span class="tag tag-ads">★ מפרסם במודעות</span><?php endif; ?>
    <?php if (empty($d['contact_name'])): ?><span class="tag tag-noname">בלי שם — תגובה נמוכה</span><?php endif; ?>
    <?php if (($d['generated_by'] ?? '') === 'llm'): ?><span class="tag tag-llm">טיוטת LLM</span>
    <?php else: ?><span class="tag">תבנית</span><?php endif; ?>
    <?php if ($followupStep > 0): ?><span class="tag tag-followup">פולואפ #<?= $followupStep ?></span><?php endif; ?>
    <?php if ($isApproved): ?><span class="tag" style="background:rgba(22,163,74,.12);color:var(--success)">אושר</span><?php endif; ?>
  </div>

  <div class="metrics">
    <span>מהירות מובייל: <b><?= $d['perf_mobile'] ?? '?' ?></b><?= ($d['perf_source'] ?? '') === 'heuristic' ? ' (הערכה)' : '' ?></span>
    <span>נגישות: <b><?= $d['a11y_score'] ?? '?' ?></b></span>
    <span>SEO: <b><?= $d['seo_score'] ?? '?' ?></b></span>
    <span>Analytics: <b><?= !empty($d['has_analytics']) ? 'יש' : 'אין' ?></b></span>
    <span>click-to-call: <b><?= !empty($d['has_click_to_call']) ? 'יש' : 'אין' ?></b></span>
  </div>

  <?php if (!empty($guard['blockers'])): ?>
    <div class="guard guard-block"><strong>חוסם שליחה:</strong>
      <ul><?php foreach ($guard['blockers'] as $b): ?><li><?= htmlspecialchars($b) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>
  <?php if (!empty($guard['warnings'])): ?>
    <div class="guard guard-warn"><strong>לתשומת לבך:</strong>
      <ul><?php foreach ($guard['warnings'] as $w): ?><li><?= htmlspecialchars($w) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="POST" action="<?= $url('admin/lead-engine/drafts/' . $id) ?>">
    <?= $csrf() ?>
    <div class="split">
      <div>
        <div class="field">
          <label for="subject-<?= $id ?>">שורת נושא</label>
          <input type="text" id="subject-<?= $id ?>" name="subject" value="<?= htmlspecialchars((string) ($d['subject'] ?? '')) ?>" maxlength="255">
        </div>
        <div class="field">
          <label for="body-<?= $id ?>">גוף ההודעה (ניתן לעריכה)</label>
          <textarea id="body-<?= $id ?>" name="body" rows="9"><?= htmlspecialchars((string) ($d['body'] ?? '')) ?></textarea>
        </div>
        <div class="field">
          <label for="video-<?= $id ?>">קישור לסרטון <span style="color:var(--danger)">— חובה לאישור</span></label>
          <input type="url" id="video-<?= $id ?>" name="video_url" dir="ltr"
                 placeholder="https://www.loom.com/share/..."
                 value="<?= htmlspecialchars((string) ($d['video_url'] ?? '')) ?>">
        </div>
        <button type="submit" class="btn btn-ghost">💾 שמור טיוטה</button>
      </div>

      <div>
        <div class="brief-head">
          <span>🎬 תסריט לסרטון</span>
          <button type="button" class="copy-btn" data-copy="brief-<?= $id ?>">העתק</button>
        </div>
        <div class="brief" id="brief-<?= $id ?>"><?= htmlspecialchars((string) ($d['video_brief'] ?? '')) ?></div>
      </div>
    </div>
  </form>

  <div class="actions">
    <?php if (!$isApproved): ?>
      <form method="POST" action="<?= $url('admin/lead-engine/drafts/' . $id . '/approve') ?>">
        <?= $csrf() ?>
        <button type="submit" class="btn btn-success" <?= $hasVideo ? '' : 'disabled title="חסר קישור לסרטון"' ?>>
          ✅ אשר
        </button>
      </form>
    <?php endif; ?>

    <a href="<?= $url('admin/lead-engine/drafts/' . $id . '/confirm') ?>" class="btn btn-primary">
      👁 תצוגה מקדימה ושליחה
    </a>

    <form method="POST" action="<?= $url('admin/lead-engine/drafts/' . $id . '/reject') ?>"
          onsubmit="return confirm('לדחות את הטיוטה?')">
      <?= $csrf() ?>
      <input type="hidden" name="reason" value="נדחה מהתור">
      <button type="submit" class="btn btn-danger">❌ דחה</button>
    </form>

    <span class="hint">שליחה מתבצעת רק מעמוד התצוגה המקדימה.</span>
  </div>
</div>
<?php endforeach; ?>
</div>

<script>
document.querySelectorAll('.copy-btn').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var el = document.getElementById(btn.dataset.copy);
    if (!el) return;
    navigator.clipboard.writeText(el.textContent).then(function () {
      var original = btn.textContent;
      btn.textContent = 'הועתק ✓';
      setTimeout(function () { btn.textContent = original; }, 1600);
    });
  });
});
</script>
<?php $content = ob_get_clean(); include __DIR__ . '/../admin/layout.php'; ?>
