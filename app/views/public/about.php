<?php ob_start() ?>
<section class="page-hero" style="padding-top:140px;padding-bottom:60px;text-align:center;background:var(--bg)"><div class="container"><h1 style="font-size:clamp(2rem,5vw,3rem);font-weight:800;margin-bottom:16px">אודות</h1><p style="color:var(--ink-soft);font-size:1.1rem;max-width:600px;margin:0 auto">אדם אחד. טכנולוגיה. תוצאות.</p></div></section>
<section style="padding-top:0"><div class="container" style="max-width:800px">
  <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:20px;color:var(--primary-dark)">מי אני</h2>
  <p style="color:var(--ink-soft);line-height:1.8;margin-bottom:16px">אני מפתח ומנהל פתרונות טכנולוגיים לעסקים — עצמאי, לא חברה. אני בונה דפי נחיתה, אתרים, אוטומציות AI ומערכות CRM — והכל מאדם אחד שמלווה אותך אישית.</p>
  <p style="color:var(--ink-soft);line-height:1.8;margin-bottom:16px">בניגוד לחברות גדולות, כשאתה עובד איתי — אתה מדבר ישירות עם מי שבונה לך את הפתרון. אין מתווכים, אין מוקד תמיכה, אין בירוקרטיה. רק וואטסאפ ישיר, זמינות אמיתית, ותוצאות מהירות.</p>
  <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:20px;color:var(--primary-dark);margin-top:32px">מה אני עושה</h2>
  <ul style="margin-bottom:20px;padding-right:20px">
    <li style="color:var(--ink-soft);margin-bottom:12px;position:relative;padding-right:24px"><span style="position:absolute;right:0;color:var(--primary);font-weight:700">🤖</span><strong>אוטומציות AI:</strong> צ'אטבוטים חכמים, מענה אוטומטי ללידים, חיבורים בין מערכות — AI שעובד בשבילך 24/7.</li>
    <li style="color:var(--ink-soft);margin-bottom:12px;position:relative;padding-right:24px"><span style="position:absolute;right:0;color:var(--primary);font-weight:700">📄</span><strong>דפי נחיתה ואתרים:</strong> בנייה מהירה, עיצוב מודרני, התאמה מלאה למובייל — דפים שמביאים לקוחות.</li>
    <li style="color:var(--ink-soft);margin-bottom:12px;position:relative;padding-right:24px"><span style="position:absolute;right:0;color:var(--primary);font-weight:700">📊</span><strong>CRM וניהול לידים:</strong> מערכת שמרכזת את כל הפניות, שולחת התראות, ועוזרת לך לסגור יותר עסקאות.</li>
    <li style="color:var(--ink-soft);margin-bottom:12px;position:relative;padding-right:24px"><span style="position:absolute;right:0;color:var(--primary);font-weight:700">☁️</span><strong>אחסון וניטור:</strong> האתר שלך תמיד זמין, מהיר ומאובטח — עם גיבויים יומיים וניטור 24/7.</li>
    <li style="color:var(--ink-soft);margin-bottom:12px;position:relative;padding-right:24px"><span style="position:absolute;right:0;color:var(--primary);font-weight:700">🔍</span><strong>ביקורות אתרים:</strong> סריקה מקיפה ל-SEO, אבטחה, נגישות וביצועים — עם דוח מפורט והמלצות.</li>
  </ul>
  <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:20px;color:var(--primary-dark);margin-top:32px">למה לעבוד איתי</h2>
  <ul style="margin-bottom:20px;padding-right:20px">
    <li style="color:var(--ink-soft);margin-bottom:10px;position:relative;padding-right:24px"><span style="position:absolute;right:0;color:var(--primary);font-weight:700">•</span><strong>זמינות אמיתית:</strong> וואטסאפ ישיר, לא כרטיס תמיכה.</li>
    <li style="color:var(--ink-soft);margin-bottom:10px;position:relative;padding-right:24px"><span style="position:absolute;right:0;color:var(--primary);font-weight:700">•</span><strong>שקיפות:</strong> אתה יודע מה קורה בכל שלב.</li>
    <li style="color:var(--ink-soft);margin-bottom:10px;position:relative;padding-right:24px"><span style="position:absolute;right:0;color:var(--primary);font-weight:700">•</span><strong>גמישות:</strong> שינויים קורים מהר, בלי בירוקרטיה.</li>
    <li style="color:var(--ink-soft);margin-bottom:10px;position:relative;padding-right:24px"><span style="position:absolute;right:0;color:var(--primary);font-weight:700">•</span><strong>מחיר הוגן:</strong> בלי עלויות תקורה של משרדים וצוותים.</li>
    <li style="color:var(--ink-soft);margin-bottom:10px;position:relative;padding-right:24px"><span style="position:absolute;right:0;color:var(--primary);font-weight:700">•</span><strong>פתרונות מותאמים:</strong> AI, אוטומציות, CRM — לא רק אתר, מערכת שלמה.</li>
  </ul>
  <div style="text-align:center;margin:40px 0 60px"><a href="<?= $url('contact') ?>" class="btn btn-primary btn-lg">📞 דברו איתי</a></div>
</div></section>
<?php $content = ob_get_clean(); include __DIR__ . "/../partials/layout.php"; ?>
