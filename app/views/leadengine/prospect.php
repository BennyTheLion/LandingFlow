<?php ob_start() ?>
<style>
.le-page{max-width:1100px}
.le-back{font-size:.82rem;color:var(--ink-soft);text-decoration:none;display:inline-block;margin-bottom:14px}
.flash{padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:.85rem}
.flash.success{background:#dcfce7;color:#166534}.flash.error{background:#fef2f2;color:#991b1b}
.btn{padding:7px 15px;border-radius:8px;border:none;cursor:pointer;font-family:inherit;font-size:.82rem;font-weight:600;text-decoration:none;display:inline-block}
.btn-primary{background:var(--primary);color:#fff}
.btn-success{background:var(--success);color:#fff}
.btn-danger{background:var(--danger);color:#fff}
.btn-ghost{background:var(--surface);border:1px solid var(--border);color:var(--ink-soft)}
.p-head{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:22px;margin-bottom:18px}
.p-head-top{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap}
.p-head h1{font-size:1.35rem;margin:0 0 5px}
.p-meta{font-size:.82rem;color:var(--ink-soft);line-height:1.85}
.p-meta a{color:var(--primary)}
.score-box{text-align:center;background:var(--surface-2);border-radius:12px;padding:12px 20px}
.score-num{font-family:var(--font-mono);font-size:1.8rem;font-weight:800;line-height:1}
.score-lbl{font-size:.66rem;color:var(--ink-soft);text-transform:uppercase}
.s-hot{color:var(--danger)}.s-warm{color:var(--warning)}.s-cool{color:var(--ink-faint)}
.p-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:16px;padding-top:16px;border-top:1px solid var(--border)}
.p-actions form{margin:0;display:inline}
.cols{display:grid;grid-template-columns:1fr;gap:18px}
@media(min-width:960px){.cols{grid-template-columns:1.15fr 1fr}}
.panel{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px;margin-bottom:18px}
.panel h3{font-size:.9rem;font-weight:700;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--border)}
.field{margin-bottom:11px}
.field label{display:block;font-size:.74rem;font-weight:700;color:var(--ink-soft);margin-bottom:4px}
.field input,.field textarea,.field select{width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:7px;font-family:inherit;font-size:.83rem;background:var(--bg)}
.field-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.chk{display:flex;align-items:flex-start;gap:7px;font-size:.79rem;color:var(--ink-soft);margin-bottom:10px;line-height:1.55}
.tbl{width:100%;border-collapse:collapse;font-size:.79rem}
.tbl th{text-align:right;color:var(--ink-soft);font-size:.68rem;text-transform:uppercase;padding:7px 8px;border-bottom:1px solid var(--border);white-space:nowrap}
.tbl td{padding:7px 8px;border-bottom:1px solid var(--border)}
.tbl tr:last-child td{border:none}
.mono{font-family:var(--font-mono);font-weight:700}
.delta-up{color:var(--success)}.delta-down{color:var(--danger)}
.yes{color:var(--success);font-weight:700}.no{color:var(--danger);font-weight:700}
.badge{display:inline-block;padding:2px 9px;border-radius:100px;font-size:.68rem;font-weight:700}
.b-draft{background:rgba(245,158,11,.16);color:#92400e}
.b-approved{background:rgba(22,163,74,.14);color:var(--success)}
.b-sent{background:var(--success);color:#fff}
.b-rejected,.b-expired{background:rgba(107,114,128,.12);color:var(--ink-faint)}
.empty{color:var(--ink-faint);font-size:.81rem;text-align:center;padding:18px 0}
.timeline{list-style:none;padding:0;margin:0;font-size:.8rem}
.timeline li{padding:7px 0 7px 0;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;gap:10px}
.timeline li:last-child{border:none}
.timeline time{color:var(--ink-faint);font-size:.73rem;white-space:nowrap}
.warn-box{background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:8px;padding:10px 13px;font-size:.78rem;line-height:1.65;margin-bottom:12px}
</style>

<?php
$issueLabels = ['broken_form'=>'טופס יצירת קשר לא עובד','no_analytics_with_ads'=>'מפרסמים בלי מעקב המרות','slow_mobile'=>'האתר איטי במובייל','no_accessibility'=>'אין הצהרת נגישות','no_click_to_call'=>'אין לחיצה להתקשרות','weak_seo'=>'SEO חלש','none'=>'—'];
$score = $prospect['hot_score'] !== null ? (int) $prospect['hot_score'] : null;
$sc = $score === null ? 's-cool' : ($score >= 75 ? 's-hot' : ($score >= 55 ? 's-warm' : 's-cool'));
$latest = $audits[0] ?? null;
$previous = $audits[1] ?? null;
$pid = (int) $prospect['id'];
?>

<div class="le-page">
<a href="<?= $url('admin/lead-engine/prospects') ?>" class="le-back">← חזרה לכל הלידים</a>

<?php if (!empty($flashMsg)): ?>
  <div class="flash <?= htmlspecialchars($flashMsg['type']) ?>"><?= htmlspecialchars($flashMsg['message']) ?></div>
<?php endif; ?>

<div class="p-head">
  <div class="p-head-top">
    <div>
      <h1><?= htmlspecialchars((string) $prospect['business_name']) ?></h1>
      <div class="p-meta">
        <a href="<?= htmlspecialchars((string) $prospect['url']) ?>" target="_blank" rel="noopener noreferrer nofollow" dir="ltr"><?= htmlspecialchars((string) $prospect['domain']) ?></a>
        <?php if (!empty($prospect['city'])): ?> · <?= htmlspecialchars((string) $prospect['city']) ?><?php endif; ?>
        · סטטוס: <strong><?= htmlspecialchars((string) $prospect['status']) ?></strong><br>
        הבעיה המובילה: <strong><?= $issueLabels[(string) ($prospect['primary_issue'] ?? 'none')] ?? '—' ?></strong><br>
        מקור: <?= htmlspecialchars((string) $prospect['source']) ?>
        · נוצר: <?= date('d/m/Y', strtotime((string) $prospect['created_at'])) ?>
        <?php if (!empty($prospect['crm_lead_id'])): ?>
          · <a href="<?= $url('admin/leads/' . (int) $prospect['crm_lead_id']) ?>">כרטיס CRM →</a>
        <?php endif; ?>
      </div>
    </div>
    <div class="score-box">
      <div class="score-num <?= $sc ?>"><?= $score ?? '—' ?></div>
      <div class="score-lbl">hot score</div>
    </div>
  </div>

  <div class="p-actions">
    <form method="POST" action="<?= $url('admin/lead-engine/prospects/' . $pid . '/audit') ?>">
      <?= $csrf() ?><button type="submit" class="btn btn-ghost">🔄 בדוק מחדש</button>
    </form>
    <form method="POST" action="<?= $url('admin/lead-engine/prospects/' . $pid . '/reprocess') ?>">
      <?= $csrf() ?><button type="submit" class="btn btn-primary">▶ הרץ צינור מלא (בדיקה → איש קשר → טיוטה)</button>
    </form>
    <form method="POST" action="<?= $url('admin/lead-engine/prospects/' . $pid . '/replied') ?>"
          onsubmit="return confirm('לסמן שהליד הגיב? הפולואפים יבוטלו והליד יועבר ל-CRM.')">
      <?= $csrf() ?><button type="submit" class="btn btn-success">💬 הגיב</button>
    </form>
    <form method="POST" action="<?= $url('admin/lead-engine/prospects/' . $pid . '/dnc') ?>"
          onsubmit="return confirm('להוסיף לרשימת do-not-contact? הפעולה הזו לא הפיכה מכאן.')">
      <?= $csrf() ?>
      <input type="hidden" name="reason" value="בקשת הסרה">
      <button type="submit" class="btn btn-danger">🚫 אל תפנה שוב</button>
    </form>
  </div>
</div>

<div class="cols">
  <div>
    <div class="panel">
      <h3>היסטוריית בדיקות</h3>
      <?php if ($previous !== null && $latest !== null):
        $delta = (int) $latest['hot_score'] - (int) $previous['hot_score']; ?>
        <?php if ($delta !== 0): ?>
          <div class="warn-box">
            הניקוד <?= $delta > 0 ? 'עלה' : 'ירד' ?> ב-<?= abs($delta) ?> נקודות מהבדיקה הקודמת
            (<?= date('d/m/Y', strtotime((string) $previous['run_at'])) ?>).
            <?php if ($delta > 0): ?>ציון עולה = ההזדמנות גדלה — זו פנייה חוזרת מצוינת.<?php endif; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <?php if (empty($audits)): ?>
        <div class="empty">טרם בוצעה בדיקה. לחץ "בדוק מחדש".</div>
      <?php else: ?>
        <table class="tbl">
          <thead><tr><th>תאריך</th><th>ניקוד</th><th>מובייל</th><th>נגישות</th><th>SEO</th><th>אבטחה</th><th>מקור מהירות</th></tr></thead>
          <tbody>
          <?php foreach ($audits as $a): ?>
            <tr>
              <td><?= date('d/m/y H:i', strtotime((string) $a['run_at'])) ?></td>
              <td class="mono"><?= (int) $a['hot_score'] ?></td>
              <td class="mono"><?= $a['perf_mobile'] ?? '—' ?></td>
              <td class="mono"><?= $a['a11y_score'] ?? '—' ?></td>
              <td class="mono"><?= $a['seo_score'] ?? '—' ?></td>
              <td class="mono"><?= $a['security_score'] ?? '—' ?></td>
              <td style="font-size:.72rem;color:var(--ink-soft)"><?= $a['perf_source'] === 'pagespeed' ? 'PageSpeed' : 'הערכה' ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

      <?php if ($latest !== null): ?>
        <h3 style="margin-top:20px">סימנים מהבדיקה האחרונה</h3>
        <table class="tbl">
          <tbody>
          <?php foreach ([
            'HTTPS' => 'has_ssl',
            'הצהרת נגישות' => 'has_accessibility_statement',
            'Analytics' => 'has_analytics',
            'Meta Pixel' => 'has_meta_pixel',
            'לחיצה להתקשרות' => 'has_click_to_call',
            'viewport מובייל' => 'mobile_viewport_ok',
            'טופס יצירת קשר' => 'contact_form_found',
          ] as $label => $key): ?>
            <tr>
              <td><?= $label ?></td>
              <td style="text-align:left">
                <?= !empty($latest[$key]) ? '<span class="yes">יש</span>' : '<span class="no">אין</span>' ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="panel">
      <h3>הודעות</h3>
      <?php if (empty($drafts)): ?>
        <div class="empty">אין טיוטות.</div>
      <?php else: ?>
        <table class="tbl">
          <thead><tr><th>נוצר</th><th>נושא</th><th>סוג</th><th>סטטוס</th><th>סרטון</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($drafts as $d): ?>
            <tr>
              <td><?= date('d/m/y', strtotime((string) $d['created_at'])) ?></td>
              <td style="max-width:220px"><?= htmlspecialchars((string) ($d['subject'] ?? '')) ?></td>
              <td style="font-size:.73rem"><?= (int) $d['followup_step'] > 0 ? 'פולואפ #' . (int) $d['followup_step'] : 'ראשונה' ?></td>
              <td><span class="badge b-<?= htmlspecialchars((string) $d['status']) ?>"><?= htmlspecialchars((string) $d['status']) ?></span></td>
              <td><?= !empty($d['video_url']) ? '🎬' : '<span style="color:var(--ink-faint)">—</span>' ?></td>
              <td><a href="<?= $url('admin/lead-engine/drafts/' . (int) $d['id'] . '/confirm') ?>">פתח</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="panel">
      <h3>אירועים</h3>
      <?php if (empty($events)): ?>
        <div class="empty">אין אירועים.</div>
      <?php else: ?>
        <ul class="timeline">
          <?php
          $eventLabels = ['sent'=>'נשלח','opened'=>'נפתח','clicked'=>'הוקלק','replied'=>'הגיב','followup_1'=>'פולואפ 1 נוצר','followup_2'=>'פולואפ 2 נוצר','followup_3'=>'פולואפ 3 נוצר','cancelled'=>'בוטל'];
          foreach ($events as $e): ?>
            <li>
              <span><?= $eventLabels[(string) $e['type']] ?? htmlspecialchars((string) $e['type']) ?>
                <?php if (!empty($e['subject'])): ?>
                  <span style="color:var(--ink-faint);font-size:.75rem">— <?= htmlspecialchars((string) $e['subject']) ?></span>
                <?php endif; ?>
              </span>
              <time><?= date('d/m/y H:i', strtotime((string) $e['at'])) ?></time>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <div class="panel">
      <h3>פרטי הליד</h3>
      <form method="POST" action="<?= $url('admin/lead-engine/prospects/' . $pid) ?>">
        <?= $csrf() ?>
        <div class="field">
          <label for="bn">שם העסק</label>
          <input type="text" id="bn" name="business_name" value="<?= htmlspecialchars((string) $prospect['business_name']) ?>">
        </div>
        <div class="field-row">
          <div class="field">
            <label for="cn">שם איש קשר</label>
            <input type="text" id="cn" name="contact_name" value="<?= htmlspecialchars((string) ($prospect['contact_name'] ?? '')) ?>">
          </div>
          <div class="field">
            <label for="cr">תפקיד</label>
            <input type="text" id="cr" name="contact_role" value="<?= htmlspecialchars((string) ($prospect['contact_role'] ?? '')) ?>">
          </div>
        </div>
        <div class="field-row">
          <div class="field">
            <label for="em">מייל</label>
            <input type="email" id="em" name="email" dir="ltr" value="<?= htmlspecialchars((string) ($prospect['email'] ?? '')) ?>">
          </div>
          <div class="field">
            <label for="ph">טלפון</label>
            <input type="text" id="ph" name="phone" dir="ltr" value="<?= htmlspecialchars((string) ($prospect['phone'] ?? '')) ?>">
          </div>
        </div>
        <div class="field-row">
          <div class="field">
            <label for="ni">נישה</label>
            <input type="text" id="ni" name="niche" value="<?= htmlspecialchars((string) ($prospect['niche'] ?? '')) ?>">
          </div>
          <div class="field">
            <label for="ci">עיר</label>
            <input type="text" id="ci" name="city" value="<?= htmlspecialchars((string) ($prospect['city'] ?? '')) ?>">
          </div>
        </div>
        <div class="field">
          <label for="no">הערות</label>
          <textarea id="no" name="notes" rows="3"><?= htmlspecialchars((string) ($prospect['notes'] ?? '')) ?></textarea>
        </div>

        <label class="chk">
          <input type="checkbox" name="spends_on_ads" value="1" <?= !empty($prospect['spends_on_ads']) ? 'checked' : '' ?>>
          <span>מפרסם במודעות — מפעיל בונוס ×1.4 בניקוד</span>
        </label>
        <label class="chk">
          <input type="checkbox" name="broken_form" value="1" <?= !empty($prospect['broken_form']) ? 'checked' : '' ?>>
          <span>הטופס באתר לא עובד — <strong>סמן רק אחרי שבדקת ידנית</strong>.
            המערכת לא ממלאת טפסים של עסקים אחרים (§11.5).</span>
        </label>

        <button type="submit" class="btn btn-primary">שמור</button>
      </form>
    </div>
  </div>
</div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../admin/layout.php'; ?>
