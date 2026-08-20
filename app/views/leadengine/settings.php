<?php ob_start() ?>
<style>
.le-page{max-width:1000px}
.le-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px}
.le-head h1{margin:0;font-size:1.5rem}
.le-nav{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px}
.le-nav a{padding:7px 14px;border-radius:8px;font-size:.82rem;font-weight:600;text-decoration:none;background:var(--surface);border:1px solid var(--border);color:var(--ink-soft)}
.le-nav a.active,.le-nav a:hover{background:var(--primary);color:#fff;border-color:var(--primary)}
.flash{padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:.85rem}
.flash.success{background:#dcfce7;color:#166534}.flash.error{background:#fef2f2;color:#991b1b}
.btn{padding:8px 16px;border-radius:8px;border:none;cursor:pointer;font-family:inherit;font-size:.82rem;font-weight:600}
.btn-primary{background:var(--primary);color:#fff}
.btn-danger{background:var(--danger);color:#fff}
.btn-ghost{background:var(--surface);border:1px solid var(--border);color:var(--ink-soft)}
.btn-xs{padding:3px 9px;font-size:.7rem;border-radius:5px}
.panel{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:18px}
.panel h3{font-size:.92rem;font-weight:700;margin-bottom:6px}
.panel .sub{font-size:.77rem;color:var(--ink-soft);margin-bottom:16px;line-height:1.6}
.grid2{display:grid;grid-template-columns:1fr;gap:14px}
@media(min-width:700px){.grid2{grid-template-columns:1fr 1fr}}
.field label{display:block;font-size:.77rem;font-weight:700;color:var(--ink-soft);margin-bottom:5px}
.field input,.field textarea{width:100%;padding:9px 11px;border:1px solid var(--border);border-radius:8px;font-family:inherit;font-size:.85rem;background:var(--bg)}
.field .note{font-size:.72rem;color:var(--ink-faint);margin-top:4px;line-height:1.5}
.chk{display:flex;align-items:flex-start;gap:8px;font-size:.83rem;padding:11px 0;border-top:1px solid var(--border);line-height:1.6}
.chk:first-of-type{border-top:none}
.chk strong{display:block}
.chk span.d{color:var(--ink-soft);font-size:.76rem}
.status-list{display:grid;grid-template-columns:1fr;gap:8px}
@media(min-width:700px){.status-list{grid-template-columns:1fr 1fr}}
.status{display:flex;align-items:center;gap:9px;font-size:.82rem;padding:9px 12px;border-radius:8px;background:var(--surface-2)}
.dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.dot-on{background:var(--success)}.dot-off{background:var(--ink-faint)}.dot-crit{background:var(--danger)}
.tbl{width:100%;border-collapse:collapse;font-size:.8rem}
.tbl th{text-align:right;color:var(--ink-soft);font-size:.68rem;text-transform:uppercase;padding:8px;border-bottom:1px solid var(--border)}
.tbl td{padding:8px;border-bottom:1px solid var(--border)}
.tbl tr:last-child td{border:none}
.empty{color:var(--ink-faint);font-size:.81rem;text-align:center;padding:18px 0}
.dnc-form{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}
.dnc-form input{padding:8px 10px;border:1px solid var(--border);border-radius:7px;font-family:inherit;font-size:.8rem;background:var(--bg);flex:1;min-width:130px}
.legal{background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:10px;padding:14px 16px;font-size:.79rem;line-height:1.8}
.legal ol{margin:8px 0 0;padding-inline-start:20px}
</style>

<div class="le-page">
<div class="le-head"><h1>🔧 הגדרות מנוע לידים</h1></div>

<div class="le-nav">
  <a href="<?= $url('admin/lead-engine') ?>">דשבורד</a>
  <a href="<?= $url('admin/lead-engine/queue') ?>">תור אישורים</a>
  <a href="<?= $url('admin/lead-engine/prospects') ?>">כל הלידים</a>
  <a href="<?= $url('admin/lead-engine/runs') ?>">הרצות</a>
  <a href="<?= $url('admin/lead-engine/settings') ?>" class="active">הגדרות</a>
</div>

<?php if (!empty($flashMsg)): ?>
  <div class="flash <?= htmlspecialchars($flashMsg['type']) ?>"><?= htmlspecialchars($flashMsg['message']) ?></div>
<?php endif; ?>

<div class="panel">
  <h3>אינטגרציות</h3>
  <div class="sub">כל אלה מוגדרים ב-<code>config/.env.php</code>, לא כאן.</div>
  <div class="status-list">
    <?php
    $checks = [
      'token_secret'  => ['APPROVAL_TOKEN_SECRET', 'חובה לזרימת האישור — בלעדיו לא ניתן להנפיק קישורי אישור', true],
      'notify_email'  => ['ADMIN_NOTIFY_EMAIL', 'יעד המייל היומי', true],
      'pagespeed'     => ['PAGESPEED_API_KEY', 'ללא מפתח — ציון המהירות הוא הערכה מקומית', false],
      'google_places' => ['GOOGLE_PLACES_API_KEY', 'ללא מפתח — אין איסוף אוטומטי', false],
      'llm'           => ['AI_API_KEY', 'ללא מפתח — הטיוטות נכתבות מתבנית', false],
    ];
    foreach ($checks as $key => [$label, $note, $critical]):
      $on = !empty($integrations[$key]);
      $dotClass = $on ? 'dot-on' : ($critical ? 'dot-crit' : 'dot-off');
    ?>
      <div class="status">
        <span class="dot <?= $dotClass ?>"></span>
        <span><strong><?= $label ?></strong><br>
          <span style="color:var(--ink-soft);font-size:.74rem"><?= $on ? 'מוגדר' : $note ?></span>
        </span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<form method="POST" action="<?= $url('admin/lead-engine/settings') ?>">
  <?= $csrf() ?>

  <div class="panel">
    <h3>ספי ניקוד ומגבלות שליחה</h3>
    <div class="sub">מגבלות השליחה נבדקות מחדש ברגע השליחה עצמה, לא רק בעת האישור.</div>
    <div class="grid2">
      <div class="field">
        <label for="hst">סף כניסה לצינור (hot score)</label>
        <input type="number" id="hst" name="hot_score_threshold" min="0" max="100"
               value="<?= htmlspecialchars((string) ($settings['hot_score_threshold'] ?? 55)) ?>">
        <div class="note">מתחת לסף — הליד נסגר אוטומטית. ברירת מחדל: 55.</div>
      </div>
      <div class="field">
        <label for="mds">מקסימום שליחות ביום</label>
        <input type="number" id="mds" name="max_daily_sends" min="0" max="100"
               value="<?= htmlspecialchars((string) ($settings['max_daily_sends'] ?? 8)) ?>">
        <div class="note">ברירת מחדל: 8.</div>
      </div>
      <div class="field">
        <label for="mbs">מינימום דקות בין שליחות</label>
        <input type="number" id="mbs" name="min_minutes_between_sends" min="0" max="1440"
               value="<?= htmlspecialchars((string) ($settings['min_minutes_between_sends'] ?? 5)) ?>">
        <div class="note">ברירת מחדל: 5.</div>
      </div>
      <div class="field">
        <label for="crm">שמירת לידים סגורים (חודשים)</label>
        <input type="number" id="crm" name="closed_retention_months" min="1" max="120"
               value="<?= htmlspecialchars((string) ($settings['closed_retention_months'] ?? 12)) ?>">
        <div class="note">מדיניות מחיקה לפי §11.6. לידים סגורים מעל התקופה נמחקים בהרצה.</div>
      </div>
      <div class="field">
        <label for="sws">תחילת חלון שליחה</label>
        <input type="time" id="sws" name="send_window_start"
               value="<?= htmlspecialchars((string) ($settings['send_window_start'] ?? '09:00')) ?>">
        <div class="note">שעון ישראל, ימים א׳–ה׳ בלבד.</div>
      </div>
      <div class="field">
        <label for="swe">סוף חלון שליחה</label>
        <input type="time" id="swe" name="send_window_end"
               value="<?= htmlspecialchars((string) ($settings['send_window_end'] ?? '18:00')) ?>">
      </div>
    </div>
  </div>

  <div class="panel">
    <h3>נישות וערים פעילות</h3>
    <div class="sub">מה שהצינור השבועי מחפש ב-Google Places. מופרד בפסיקים.</div>
    <div class="field" style="margin-bottom:14px">
      <label for="an">נישות</label>
      <input type="text" id="an" name="active_niches" dir="ltr"
             value="<?= htmlspecialchars((string) ($settings['active_niches'] ?? '')) ?>">
      <div class="note">מפתחות זמינים: <?= htmlspecialchars(implode(', ', array_keys($nicheList))) ?></div>
    </div>
    <div class="field">
      <label for="ac">ערים</label>
      <input type="text" id="ac" name="active_cities"
             value="<?= htmlspecialchars((string) ($settings['active_cities'] ?? '')) ?>">
      <div class="note">לדוגמה: תל אביב, ירושלים, חיפה</div>
    </div>
  </div>

  <div class="panel">
    <h3>מפסקים</h3>
    <label class="chk">
      <input type="checkbox" name="pipeline_enabled" value="1"
             <?= !empty($settings['pipeline_enabled']) && $settings['pipeline_enabled'] !== '0' ? 'checked' : '' ?>>
      <span><strong>הצינור מופעל</strong>
        <span class="d">כשמכובה, הרצות cron יוצאות מיד בלי לעשות דבר. לא משפיע על שליחה ידנית של טיוטה קיימת.</span>
      </span>
    </label>
    <label class="chk">
      <input type="checkbox" name="sending_halted" value="1"
             <?= !empty($settings['sending_halted']) && $settings['sending_halted'] !== '0' ? 'checked' : '' ?>>
      <span><strong>⛔ עצור הכל — הקפאת כל השליחות</strong>
        <span class="d">חוסם כל שליחה, כולל שליחה ידנית מעמוד התצוגה המקדימה. השאר מסומן בכל ספק.</span>
      </span>
    </label>
    <button type="submit" class="btn btn-primary" style="margin-top:14px">שמור הגדרות</button>
  </div>
</form>

<div class="panel">
  <h3>רשימת do-not-contact (<?= count($dncList) ?>)</h3>
  <div class="sub">נבדקת לפני יצירת טיוטה ושוב לפני כל שליחה בפועל. בקשת הסרה נכנסת לכאן מיידית (§11.3).</div>

  <form method="POST" action="<?= $url('admin/lead-engine/dnc') ?>" class="dnc-form">
    <?= $csrf() ?>
    <input type="text" name="domain" placeholder="דומיין" dir="ltr">
    <input type="email" name="email" placeholder="מייל" dir="ltr">
    <input type="text" name="phone" placeholder="טלפון" dir="ltr">
    <input type="text" name="reason" placeholder="סיבה">
    <button type="submit" class="btn btn-danger">חסום</button>
  </form>

  <?php if (empty($dncList)): ?>
    <div class="empty">הרשימה ריקה.</div>
  <?php else: ?>
    <table class="tbl">
      <thead><tr><th>דומיין</th><th>מייל</th><th>טלפון</th><th>סיבה</th><th>נוסף</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($dncList as $entry): ?>
        <tr>
          <td dir="ltr"><?= htmlspecialchars((string) ($entry['domain'] ?? '—')) ?></td>
          <td dir="ltr"><?= htmlspecialchars((string) ($entry['email'] ?? '—')) ?></td>
          <td dir="ltr"><?= htmlspecialchars((string) ($entry['phone'] ?? '—')) ?></td>
          <td style="font-size:.76rem"><?= htmlspecialchars((string) ($entry['reason'] ?? '')) ?></td>
          <td style="font-size:.74rem;color:var(--ink-soft)"><?= date('d/m/y', strtotime((string) $entry['added_at'])) ?></td>
          <td>
            <form method="POST" action="<?= $url('admin/lead-engine/dnc/' . (int) $entry['id'] . '/remove') ?>"
                  onsubmit="return confirm('להסיר מרשימת החסימה? שים לב: אם הנמען ביקש להיות מוסר, אין להסיר אותו מכאן.')">
              <?= $csrf() ?>
              <button type="submit" class="btn btn-ghost btn-xs">הסר</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="legal">
  <strong>⚠ תזכורת משפטית (§11 במפרט — לא ייעוץ משפטי)</strong>
  <ol>
    <li>סעיף 30א לחוק התקשורת מגביל שליחת דבר פרסומת ללא הסכמה מראש, גם לנמענים עסקיים. כדאי לבדוק עם עו"ד לפני הפעלה בקצב.</li>
    <li>כל הודעה יוצאת כוללת אוטומטית פרטי שולח מזוהים ואפשרות הסרה. אין דרך לכבות את זה מהפאנל.</li>
    <li>בקשת הסרה נכנסת ל-do-not-contact מיידית ובלי שאלות.</li>
    <li>ערוץ וואטסאפ חסום בקוד לפנייה קרה — הודעות יזומות מפרות את מדיניות מטא ועלולות לחסום את המספר.</li>
    <li>המערכת לא ממלאת טפסים של עסקים אחרים. הדגל "טופס לא עובד" נקבע ידנית בלבד, אחרי בדיקה שלך.</li>
  </ol>
</div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../admin/layout.php'; ?>
