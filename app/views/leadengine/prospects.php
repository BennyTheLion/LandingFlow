<?php ob_start() ?>
<style>
.le-page{max-width:1300px}
.le-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px}
.le-head h1{margin:0;font-size:1.5rem}
.le-nav{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px}
.le-nav a{padding:7px 14px;border-radius:8px;font-size:.82rem;font-weight:600;text-decoration:none;background:var(--surface);border:1px solid var(--border);color:var(--ink-soft)}
.le-nav a.active,.le-nav a:hover{background:var(--primary);color:#fff;border-color:var(--primary)}
.flash{padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:.85rem}
.flash.success{background:#dcfce7;color:#166534}.flash.error{background:#fef2f2;color:#991b1b}
.btn{padding:7px 15px;border-radius:8px;border:none;cursor:pointer;font-family:inherit;font-size:.82rem;font-weight:600}
.btn-primary{background:var(--primary);color:#fff}
.btn-ghost{background:var(--surface);border:1px solid var(--border);color:var(--ink-soft)}
.tools{display:grid;grid-template-columns:1fr;gap:14px;margin-bottom:20px}
@media(min-width:1000px){.tools{grid-template-columns:1.4fr 1fr 1fr}}
.tool{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:16px}
.tool h3{font-size:.85rem;font-weight:700;margin-bottom:12px}
.tool .grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.tool input,.tool select{width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:7px;font-family:inherit;font-size:.8rem;background:var(--bg)}
.tool label.chk{display:flex;align-items:center;gap:6px;font-size:.78rem;color:var(--ink-soft);grid-column:1/-1}
.tool button{margin-top:10px;width:100%}
.hint{font-size:.72rem;color:var(--ink-faint);margin-top:8px;line-height:1.6}
.filters{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:14px;background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:12px}
.filters input,.filters select{padding:7px 10px;border:1px solid var(--border);border-radius:7px;font-family:inherit;font-size:.8rem;background:var(--bg)}
.table-wrap{background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow-x:auto}
.tbl{width:100%;border-collapse:collapse;font-size:.83rem;min-width:1000px}
.tbl th{background:var(--surface-2);color:var(--ink-soft);font-weight:700;font-size:.7rem;text-transform:uppercase;padding:10px 12px;text-align:right;white-space:nowrap}
.tbl td{padding:9px 12px;border-bottom:1px solid var(--border);vertical-align:middle}
.tbl tr:hover td{background:var(--surface-2)}
.tbl a{color:var(--primary);text-decoration:none;font-weight:600}
.score{font-family:var(--font-mono);font-weight:700}
.s-hot{color:var(--danger)}.s-warm{color:var(--warning)}.s-cool{color:var(--ink-faint)}
.badge{display:inline-block;padding:2px 9px;border-radius:100px;font-size:.68rem;font-weight:700;white-space:nowrap}
.b-new{background:rgba(107,114,128,.14);color:var(--ink-soft)}
.b-audited{background:rgba(14,165,233,.12);color:var(--info)}
.b-enriched{background:rgba(37,99,235,.12);color:var(--primary)}
.b-drafted{background:rgba(245,158,11,.16);color:#92400e}
.b-approved,.b-sent{background:rgba(22,163,74,.14);color:var(--success)}
.b-replied{background:var(--success);color:#fff}
.b-closed,.b-rejected{background:rgba(107,114,128,.1);color:var(--ink-faint)}
.b-blocked{background:rgba(245,158,11,.14);color:#92400e}
.b-do_not_contact{background:rgba(220,38,38,.12);color:var(--danger)}
.empty{text-align:center;padding:40px;color:var(--ink-faint)}
.status-help{background:var(--surface);border:1px solid var(--border);border-radius:12px;margin-bottom:20px}
.status-help summary{cursor:pointer;padding:12px 16px;font-size:.85rem;font-weight:700;list-style:none;display:flex;align-items:center;gap:8px}
.status-help summary::-webkit-details-marker{display:none}
.status-help summary::before{content:'▸';color:var(--ink-faint);transition:transform .15s}
.status-help[open] summary::before{transform:rotate(90deg)}
.status-help-body{padding:0 16px 16px;font-size:.8rem;line-height:1.7;color:var(--ink-soft)}
.status-help-body dl{display:grid;grid-template-columns:auto 1fr;gap:6px 14px;margin:0}
.status-help-body dt{white-space:nowrap}
.status-help-body dd{margin:0;color:var(--ink-faint)}
.status-help-note{margin-top:14px;padding-top:12px;border-top:1px solid var(--border);color:var(--ink-faint)}
</style>

<div class="le-page">
<div class="le-head"><h1>📇 כל הלידים</h1><span class="hint"><?= count($prospects) ?> רשומות</span></div>

<div class="le-nav">
  <a href="<?= $url('admin/lead-engine') ?>">דשבורד</a>
  <a href="<?= $url('admin/lead-engine/queue') ?>">תור אישורים</a>
  <a href="<?= $url('admin/lead-engine/prospects') ?>" class="active">כל הלידים</a>
  <a href="<?= $url('admin/lead-engine/runs') ?>">הרצות</a>
  <a href="<?= $url('admin/lead-engine/settings') ?>">הגדרות</a>
</div>

<?php if (!empty($flashMsg)): ?>
  <div class="flash <?= htmlspecialchars($flashMsg['type']) ?>"><?= htmlspecialchars($flashMsg['message']) ?></div>
<?php endif; ?>

<details class="status-help">
  <summary>❓ מה המשמעות של כל סטטוס?</summary>
  <div class="status-help-body">
    <dl>
      <dt><span class="badge b-new">חדש</span></dt>
      <dd>נוסף למערכת (ידני / CSV / Google Places) — עדיין לא נבדק.</dd>

      <dt><span class="badge b-audited">נבדק</span></dt>
      <dd>הבדיקה רצה וה-hot score עבר את הסף — ממתין לאיתור איש קשר.</dd>

      <dt><span class="badge b-enriched">הועשר</span></dt>
      <dd>נמצא איש קשר (טלפון / מייל / שם) — ממתין לכתיבת טיוטה.</dd>

      <dt><span class="badge b-drafted">טיוטה</span></dt>
      <dd>נכתבו הודעה ותסריט סרטון — ממתינים בתור האישורים.</dd>

      <dt><span class="badge b-approved">אושר</span></dt>
      <dd>אושר בעמוד האישור, אך טרם נשלח בפועל.</dd>

      <dt><span class="badge b-sent">נשלח</span></dt>
      <dd>ההודעה נשלחה בפועל ללקוח.</dd>

      <dt><span class="badge b-replied">הגיב</span></dt>
      <dd>הליד הגיב — קודם אוטומטית ל-CRM כלקוח פוטנציאלי, והפולואפים הבאים בוטלו.</dd>

      <dt><span class="badge b-blocked">חסם בדיקה</span></dt>
      <dd>האתר חסם את הבדיקה (קוד 401/403/429/503, או robots.txt) — לא ידוע אם זה ליד טוב, פשוט לא הצלחנו להסתכל. נבדק שוב אוטומטית כעבור 3 ימים.</dd>

      <dt><span class="badge b-closed">נסגר</span></dt>
      <dd>הניקוד מתחת לסף הכניסה, או שהאתר לא נגיש כלל (לא בגלל חסימת בוטים) — לא שווה מרדף כרגע. נבדק שוב אוטומטית כעבור 60 יום, כדי לתפוס שיפור או הרעה עתידית.</dd>

      <dt><span class="badge b-rejected">נדחה</span></dt>
      <dd>אדם דחה את הטיוטה באופן ידני — החלטה שיקולית, לא תוצאה של בדיקה.</dd>

      <dt><span class="badge b-do_not_contact">ברשימה שחורה</span></dt>
      <dd>הדומיין / מייל / טלפון ברשימת do-not-contact — חסום לצמיתות. נבדק לפני כל הוספה ולפני כל שליחה, בלי יוצא מן הכלל.</dd>
    </dl>
    <div class="status-help-note">
      💡 ליד שהגיע ל"נשלח" לא יחזור אחורה בבדיקה חוזרת — גם אם הניקוד יורד בבדיקה הבאה, הסטטוס נשאר "נשלח" (כדי לא לאבד את ההיסטוריה של פנייה שכבר יצאה), והציון המעודכן פשוט מוצג לצד הליד כהזדמנות לפולואפ.
    </div>
  </div>
</details>

<div class="tools">
  <div class="tool">
    <h3>+ הוסף ליד ידנית</h3>
    <form method="POST" action="<?= $url('admin/lead-engine/prospects') ?>">
      <?= $csrf() ?>
      <div class="grid">
        <input type="text" name="business_name" placeholder="שם העסק">
        <input type="url" name="url" placeholder="https://example.co.il" required dir="ltr">
        <input type="text" name="contact_name" placeholder="שם איש קשר">
        <input type="email" name="email" placeholder="מייל" dir="ltr">
        <input type="text" name="phone" placeholder="טלפון" dir="ltr">
        <input type="text" name="city" placeholder="עיר">
        <select name="niche">
          <option value="">— נישה —</option>
          <?php foreach ($nicheList as $key => $label): ?>
            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>
        <input type="text" name="notes" placeholder="הערה">
        <label class="chk"><input type="checkbox" name="spends_on_ads" value="1"> מפרסם במודעות (ליד חם — ספריית המודעות של מטא)</label>
      </div>
      <button type="submit" class="btn btn-primary">הוסף ליד</button>
    </form>
    <div class="hint">זרימת §5 מקור B: 15 דק' בשבוע בספריית המודעות של מטא, 8–10 לידים חמים.</div>
  </div>

  <div class="tool">
    <h3>⬆ ייבוא CSV</h3>
    <form method="POST" action="<?= $url('admin/lead-engine/import') ?>" enctype="multipart/form-data">
      <?= $csrf() ?>
      <input type="file" name="csv" accept=".csv,text/csv" required>
      <button type="submit" class="btn btn-ghost">ייבא</button>
    </form>
    <div class="hint">כותרות נתמכות: business_name, url, phone, email, city, niche, contact_name, spends_on_ads. עד 2MB.</div>
  </div>

  <div class="tool">
    <h3>🔍 איסוף מ-Google Places</h3>
    <?php if ($placesOn): ?>
      <form method="POST" action="<?= $url('admin/lead-engine/source/places') ?>">
        <?= $csrf() ?>
        <select name="niche" required>
          <option value="">— נישה —</option>
          <?php foreach ($nicheList as $key => $label): ?>
            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>
        <input type="text" name="city" placeholder="עיר" required style="margin-top:8px">
        <button type="submit" class="btn btn-ghost">חפש והוסף</button>
      </form>
      <div class="hint">מסננים: דירוג ≥ 4.0, ≥ 30 ביקורות, קיים אתר. ~$0.05 לחיפוש.</div>
    <?php else: ?>
      <div class="hint">GOOGLE_PLACES_API_KEY לא מוגדר. הוסף אותו ל-config/.env.php כדי להפעיל איסוף אוטומטי.</div>
    <?php endif; ?>
  </div>
</div>

<form method="GET" action="<?= $url('admin/lead-engine/prospects') ?>" class="filters">
  <input type="search" name="q" placeholder="חיפוש: שם, דומיין, איש קשר, מייל" value="<?= htmlspecialchars((string) $filters['search']) ?>">
  <select name="status">
    <option value="">כל הסטטוסים</option>
    <?php foreach (['new'=>'חדש','audited'=>'נבדק','enriched'=>'הועשר','drafted'=>'טיוטה','approved'=>'אושר','sent'=>'נשלח','replied'=>'הגיב','blocked'=>'חסם בדיקה','closed'=>'נסגר','rejected'=>'נדחה','do_not_contact'=>'ברשימה שחורה'] as $k=>$v): ?>
      <option value="<?= $k ?>" <?= $filters['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
    <?php endforeach; ?>
  </select>
  <select name="niche">
    <option value="">כל הנישות</option>
    <?php foreach ($niches as $n): ?>
      <option value="<?= htmlspecialchars((string) $n) ?>" <?= $filters['niche'] === $n ? 'selected' : '' ?>><?= htmlspecialchars((string) ($nicheList[$n] ?? $n)) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="source">
    <option value="">כל המקורות</option>
    <?php foreach (['google_places'=>'Google Places','meta_ads'=>'ספריית מודעות','manual'=>'ידני','csv'=>'CSV','referral'=>'הפניה'] as $k=>$v): ?>
      <option value="<?= $k ?>" <?= $filters['source'] === $k ? 'selected' : '' ?>><?= $v ?></option>
    <?php endforeach; ?>
  </select>
  <input type="number" name="min_score" min="0" max="100" placeholder="ניקוד מ-" style="width:110px"
         value="<?= htmlspecialchars((string) $filters['min_score']) ?>">
  <button type="submit" class="btn btn-ghost">סנן</button>
  <a href="<?= $url('admin/lead-engine/prospects') ?>" class="btn btn-ghost" style="text-decoration:none">נקה</a>
</form>

<div class="table-wrap"><table class="tbl">
<thead><tr>
  <th>עסק</th><th>דומיין</th><th>ניקוד</th><th>הבעיה</th><th>איש קשר</th>
  <th>מייל</th><th>נישה</th><th>עיר</th><th>מקור</th><th>סטטוס</th><th>בדיקה</th><th>שלח דוח</th>
</tr></thead>
<tbody>
<?php
$issueLabels = ['broken_form'=>'טופס לא עובד','no_analytics_with_ads'=>'מפרסם בלי מעקב','slow_mobile'=>'איטי במובייל','no_accessibility'=>'אין נגישות','no_click_to_call'=>'אין חיוג','weak_seo'=>'SEO חלש','none'=>'—'];
$sourceLabels = ['google_places'=>'Places','meta_ads'=>'מודעות','manual'=>'ידני','csv'=>'CSV','referral'=>'הפניה'];
// Defaults to your own address, never the prospect's — sending a report to the
// business itself is outreach and belongs in the draft approval flow.
$reportTo = (defined('ADMIN_NOTIFY_EMAIL') && ADMIN_NOTIFY_EMAIL !== '')
    ? ADMIN_NOTIFY_EMAIL
    : (defined('ALERT_EMAIL') && ALERT_EMAIL !== '' ? ALERT_EMAIL : (defined('SMTP_USER') ? SMTP_USER : ''));
foreach ($prospects as $p):
  $score = $p['hot_score'] !== null ? (int) $p['hot_score'] : null;
  $sc = $score === null ? 's-cool' : ($score >= 75 ? 's-hot' : ($score >= 55 ? 's-warm' : 's-cool'));
?>
<tr>
  <td>
    <a href="<?= $url('admin/lead-engine/prospects/' . (int) $p['id']) ?>"><?= htmlspecialchars((string) $p['business_name']) ?></a>
    <?php if (!empty($p['spends_on_ads'])): ?> <span title="מפרסם במודעות">★</span><?php endif; ?>
  </td>
  <td dir="ltr" style="font-size:.77rem;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars((string) $p['domain']) ?></td>
  <td><span class="score <?= $sc ?>"><?= $score ?? '—' ?></span></td>
  <td style="font-size:.77rem"><?= $issueLabels[(string) ($p['primary_issue'] ?? 'none')] ?? '—' ?></td>
  <td><?= $p['contact_name'] ? htmlspecialchars((string) $p['contact_name']) : '<span style="color:var(--ink-faint)">—</span>' ?></td>
  <td dir="ltr" style="font-size:.75rem;max-width:170px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars((string) ($p['email'] ?? '—')) ?></td>
  <td style="font-size:.77rem"><?= htmlspecialchars((string) ($nicheList[$p['niche']] ?? $p['niche'] ?? '—')) ?></td>
  <td style="font-size:.77rem"><?= htmlspecialchars((string) ($p['city'] ?? '—')) ?></td>
  <td style="font-size:.75rem"><?= htmlspecialchars((string) ($sourceLabels[$p['source']] ?? $p['source'])) ?></td>
  <td><span class="badge b-<?= htmlspecialchars((string) $p['status']) ?>"><?= htmlspecialchars((string) $p['status']) ?></span></td>
  <td style="font-size:.73rem;color:var(--ink-soft)"><?= $p['last_audit_at'] ? date('d/m', strtotime((string) $p['last_audit_at'])) : 'טרם' ?></td>
  <td>
    <form method="POST" action="<?= $url('admin/lead-engine/prospects/' . (int) $p['id'] . '/email-report') ?>" style="display:flex;gap:4px;align-items:center">
      <?= $csrf() ?>
      <input type="email" name="email" value="<?= htmlspecialchars((string) $reportTo) ?>" required dir="ltr"
             style="width:145px;font-size:.72rem;padding:3px 5px" title="לאיזו כתובת לשלוח את הדוח">
      <button type="submit" class="btn btn-ghost" style="padding:3px 8px"
              title="הרץ בדיקה מלאה ושלח את הדוח (PDF) לכתובת שבשדה">📧</button>
    </form>
  </td>
</tr>
<?php endforeach; ?>
<?php if (empty($prospects)): ?>
  <tr><td colspan="12" class="empty">אין לידים שתואמים לסינון.</td></tr>
<?php endif; ?>
</tbody></table></div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../admin/layout.php'; ?>
