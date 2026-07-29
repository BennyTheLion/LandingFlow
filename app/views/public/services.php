<?php ob_start();

$services = [
  "index" => ["title" => "שירותים", "desc" => "LandingFlow מציעה מגוון שירותים לניהול הנוכחות הדיגיטלית שלך — הכל במקום אחד.", "items" => [
    ["url" => "services/landing-pages", "icon" => "📄", "title" => "דפי נחיתה", "desc" => "דפים מהירים ומותאמים להמרה — טפסים חכמים, A/B טסטינג ואנליטיקס."],
    ["url" => "services/website-development", "icon" => "💻", "title" => "פיתוח אתרים", "desc" => "אתרי תדמית, חנויות אונליין ומערכות — קוד נקי, SEO מובנה."],
    ["url" => "services/hosting", "icon" => "☁️", "title" => "אחסון + גיבוי", "desc" => "אחסון SSD מהיר, SSL חינם, גיבוי יומי אוטומטי."],
    ["url" => "contact", "icon" => "📡", "title" => "ניטור 24/7", "desc" => "בדיקה כל 60 שניות ממספר מוקדים, התראות מיידיות."],
    ["url" => "audit", "icon" => "🔍", "title" => "ביקורות אוטומטיות", "desc" => "SEO, אבטחה, נגישות וביצועים — דוח שבועי מסודר."],
    ["url" => "contact", "icon" => "📊", "title" => "CRM ולידים", "desc" => "כל פנייה נכנסת אוטומטית, עם ניתוב ומעקב מכירה."],
    ["url" => "contact", "icon" => "📈", "title" => "דוחות חכמים", "desc" => "סיכום חודשי ברור עם כל הנתונים שחשובים לעסק."],
    ["url" => "contact", "icon" => "💬", "title" => "תמיכה", "desc" => "בהתאם לחבילה — תמיכה במייל, וואטסאפ, או VIP אישית."],
  ]],

  "landing" => ["title" => "דפי נחיתה", "desc" => "דפי נחיתה מקצועיים שמותאמים להמרה — אנחנו בונים דפים שמביאים תוצאות.", "long" => "דף נחיתה הוא הכלי השיווקי החזק ביותר שלך. אנחנו בונים דפים מהירים, מותאמים לנייד, עם טפסים חכמים שמתחברים ישירות ל-CRM. כל דף עובר אופטימיזציית המרה (CRO) — מבדיקות A/B ועד אנליטיקס מתקדם. התוצאה: יותר לידים, יותר מכירות, פחות בזבוז.", "features" => ["עיצוב מותאם אישית", "טפסים חכמים עם אינטגרציה ל-CRM", "מותאם לנייד (Mobile-First)", "אופטימיזציית המרה (CRO)", "A/B טסטינג מובנה", "אנליטיקס ודוחות", "זמן טעינה מהיר (Core Web Vitals)"]],
  "website" => ["title" => "פיתוח אתרים", "desc" => "בונים אתרים מקצועיים מכל סוג — קוד נקי, מהיר וידידותי לקידום.", "long" => "בין אם אתה צריך אתר תדמית, חנות אונליין, או מערכת מותאמת אישית — אנחנו בונים אתרים שעובדים. הצוות שלנו משתמש בטכנולוגיות הכי מתקדמות כדי להבטיח אתר מהיר, מאובטח ומותאם SEO. כל פרויקט מלווה באפיון מלא, עיצוב UI/UX, פיתוח, בדיקות והשקה. ואנחנו לא עוזבים — התמיכה ממשיכה גם אחרי העלייה לאוויר.", "features" => ["אתרי תדמית לעסקים", "חנויות אונליין (WooCommerce)", "מערכות ניהול תוכן מותאמות", "עיצוב UI/UX רספונסיבי", "SEO אופטימיזציה מובנית", "אבטחה מתקדמת + SSL", "תמיכה ועדכונים שוטפים"]],
  "hosting" => ["title" => "אחסון + גיבוי", "desc" => "אחסון מהיר ומאובטח — SSL חינם, גיבויים יומיים, והגנת DDoS.", "long" => "האתר שלך חייב להיות זמין תמיד. שירות האחסון שלנו כולל שרתי SSD מהירים, תעודת SSL חינם, גיבויים יומיים אוטומטיים, והגנת DDoS מתקדמת. אנחנו מנטרים את השרתים 24/7 ומוודאים שהאתר שלך זמין ועובד מהר — מכל מקום בעולם. שדרוג קל בכל שלב, בלי כאבי ראש של העברות.", "features" => ["אחסון SSD מהיר במיוחד", "SSL חינם", "גיבויים יומיים אוטומטיים", "99.9% Uptime מובטח", "הגנת DDoS מתקדמת", "לוח בקרה ידידותי", "שדרוג קל בלחיצת כפתור"]],
  "monitoring" => ["title" => "ניטור 24/7", "desc" => "ניטור uptime, SSL, ביצועים והתראות מיידיות — שקט נפשי מלא.", "long" => "מערכת הניטור שלנו בודקת את האתר שלך כל 60 שניות ממספר מוקדים בעולם. אנחנו מודדים uptime, זמני תגובה, תוקף SSL, ציוני SEO ואבטחה. ברגע שמשהו משתבש — אתה מקבל התראה מיידית בוואטסאפ. דוחות שבועיים מסכמים את כל מה שחשוב. ככה אתה ישן בשקט.", "features" => ["בדיקת uptime כל 60 שניות", "מעקב תוקף SSL", "מדידת זמני תגובה", "התראות וואטסאפ מיידיות", "דוחות שבועיים מסוכמים", "בדיקה ממספר מוקדים בעולם", "לוג היסטורי מלא"]],
  "audit" => ["title" => "ביקורות אתרים", "desc" => "סריקה מקיפה של האתר — SEO, אבטחה, נגישות וביצועים עם דוח מפורט.", "long" => "ביקורת האתר שלנו סורקת את האתר שלך לעומק ובודקת עשרות פרמטרים: SEO (כותרות, meta, robots, sitemap, canonical, OG), אבטחה (HTTPS, SSL, headers אבטחה, mixed content), נגישות (ARIA, alt לתמונות, WCAG), ביצועים (זמן תגובה, tracking, cookies). התוצאה — דוח מפורט עם ציון כולל והמלצות מעשיות לתיקון.", "features" => ["בדיקת SEO מקיפה (7 פרמטרים)", "סריקת אבטחה (8 פרמטרים)", "בדיקת נגישות (7 פרמטרים)", "מדידת ביצועים", "ציון כללי + פילוח לפי קטגוריה", "המלצות מפורטות לתיקון", "דוח PDF לשליחה במייל"]],
  "crm" => ["title" => "CRM ולידים", "desc" => "ניהול לידים אוטומטי — כל פנייה נכנסת, מנותבת ומתועדת.", "long" => "מערכת ה-CRM שלנו תופסת כל ליד שנכנס — מטופס באתר, מוואטסאפ, ממייל, או ממודעה — ומרכזת אותו במקום אחד. אפשר לנתב פניות אוטומטית, לעקוב אחר סטטוס מכירה, להוסיף הערות, ולקבל התראות כשמשהו זז. ככה שום ליד לא נופל בין הכיסאות.", "features" => ["תפיסת לידים אוטומטית מכל ערוץ", "ניתוב אוטומטי לפי סוג פנייה", "מעקב סטטוס מכירה", "הוספת הערות ופעולות", "התראות על לידים חדשים", "דוחות סיכום חודשיים", "אינטגרציה עם וואטסאפ"]],
  "reports" => ["title" => "דוחות חכמים", "desc" => "סיכומים חודשיים ברורים — uptime, לידים, תנועה, המרות.", "long" => "אנחנו מאמינים שמה שלא נמדד — לא מנוהל. מערכת הדוחות שלנו מרכזת עבורך מדי חודש את כל הנתונים שחשובים לעסק: כמה לידים נכנסו, מה ה-uptime של האתר, כמה מבקרים היו, איזה דפים הכי פופולריים, ומה שיעור ההמרה. הכל מוגש בדוח ברור ומסודר — כדי שתדע בדיוק איפה אתה עומד ולאן להתקדם.", "features" => ["סיכום חודשי אוטומטי", "מעקב uptime וזמינות", "ספירת לידים והמרות", "ניתוח תנועה ומבקרים", "דפים פופולריים", "השוואה חודשית", "שליחה ישירות למייל"]],
];

$svcType = $serviceType ?? "index";
$s = $services[$svcType] ?? $services["index"];
?>

<section style="padding-top:140px"><div class="container">
  <div class="head-center" style="margin-bottom:50px">
    <span class="section-eyebrow">שירותים</span>
    <h1 class="section-title" style="margin:0 auto 12px"><?= htmlspecialchars($s["title"]) ?></h1>
    <p class="section-sub" style="margin:0 auto 42px"><?= $s["desc"] ?></p>
  </div>

<?php if (isset($s["items"])): ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;max-width:1050px;margin:0 auto">
<?php foreach ($s["items"] as $item): ?>
<a href="<?= $url($item["url"]) ?>" style="background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:28px 24px;display:block;transition:transform .25s,box-shadow .25s;text-decoration:none;color:inherit">
  <div style="width:50px;height:50px;border-radius:12px;background:rgba(37,99,235,.09);display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin-bottom:16px"><?= $item["icon"] ?></div>
  <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:8px"><?= $item["title"] ?></h3>
  <p style="color:var(--ink-soft);font-size:.92rem;line-height:1.6"><?= $item["desc"] ?></p>
</a>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (isset($s["features"])): ?>
  <?php if (!empty($s["long"])): ?>
  <div style="max-width:750px;margin:0 auto 30px;background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:24px">
    <p style="color:var(--ink-soft);font-size:.95rem;line-height:1.8"><?= nl2br($s["long"]) ?></p>
  </div>
  <?php endif; ?>
<div style="display:grid;grid-template-columns:1fr;gap:12px;max-width:700px;margin:0 auto">
<?php foreach ($s["features"] as $f): ?>
<div style="display:flex;align-items:center;gap:10px;background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:14px 18px;font-size:.95rem">
  <span style="color:var(--success);font-weight:700;font-size:1.1rem">✓</span><?= $f ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div></section>

<section style="background:var(--primary-dark);color:#fff;text-align:center"><div class="container">
  <h2 style="font-size:1.8rem;font-weight:800;margin-bottom:14px">מוכנים להתחיל?</h2>
  <p style="color:rgba(255,255,255,.75);max-width:480px;margin:0 auto 30px">הצטרפו עוד היום ותתחילו לראות תוצאות.</p>
  <a href="<?= $url("contact") ?>" class="btn btn-primary btn-lg" style="background:#fff;color:var(--primary-dark)">צור קשר</a>
</div></section>

<?php $content = ob_get_clean(); include __DIR__ . "/../partials/layout.php"; ?>
