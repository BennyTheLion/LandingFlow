<?php ob_start() ?>
<style>
.about-hero{padding:150px 0 60px;text-align:center;background:var(--bg)}
.about-hero .eyebrow{display:inline-block;font-family:var(--font-mono);font-size:.76rem;letter-spacing:.06em;color:var(--signal);margin-bottom:14px;text-transform:uppercase}
.about-hero h1{font-family:var(--font-serif);font-size:clamp(2rem,5vw,3.1rem);font-weight:900;margin-bottom:14px;line-height:1.2}
.about-hero p{color:var(--ink-soft);font-size:1.05rem;max-width:520px;margin:0 auto}

.about-quote{max-width:680px;margin:0 auto;padding:52px 24px 20px;text-align:center}
.about-quote blockquote{font-family:var(--font-serif);font-size:clamp(1.25rem,3vw,1.8rem);font-weight:700;line-height:1.55;color:var(--ink)}
.about-quote cite{display:block;margin-top:16px;font-family:var(--font-mono);font-size:.78rem;color:var(--ink-faint);font-style:normal}

.about-body{max-width:640px;margin:0 auto;padding:0 24px 60px}
.about-body p{color:var(--ink-soft);line-height:1.85;margin-bottom:18px;font-size:1rem}

.about-list-section{padding:60px 0;background:var(--surface);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
.about-list-section h2{font-family:var(--font-serif);font-size:1.5rem;font-weight:800;margin-bottom:32px;text-align:center}
.about-services{max-width:680px;margin:0 auto;display:flex;flex-direction:column}
.about-service{display:flex;gap:16px;align-items:flex-start;padding:20px 2px;border-bottom:1px solid var(--border)}
.about-service:last-child{border-bottom:none}
.about-service .ic{font-size:1.25rem;width:30px;text-align:center;flex-shrink:0;padding-top:2px}
.about-service h3{font-size:.95rem;font-weight:700;margin-bottom:4px}
.about-service p{font-size:.85rem;color:var(--ink-soft);line-height:1.55}

.about-why{max-width:680px;margin:0 auto;padding:60px 24px}
.about-why h2{font-family:var(--font-serif);font-size:1.5rem;font-weight:800;margin-bottom:28px;text-align:center}
.about-why-grid{display:grid;grid-template-columns:1fr;gap:14px}
@media(min-width:640px){.about-why-grid{grid-template-columns:1fr 1fr}}
.about-why-item{display:flex;gap:10px;align-items:flex-start;font-size:.9rem;color:var(--ink-soft);line-height:1.5}
.about-why-item .chk{color:var(--signal);flex-shrink:0;font-weight:700}
.about-why-item strong{color:var(--ink)}

.about-cta{text-align:center;padding:10px 0 90px}
</style>

<section class="about-hero">
  <div class="container">
    <span class="eyebrow">מי מאחורי LandingFlow</span>
    <h1>אדם אחד. טכנולוגיה. תוצאות.</h1>
    <p>לא סוכנות, לא צוות, לא מוקד תמיכה — פשוט מישהו שבונה ומלווה אישית.</p>
  </div>
</section>

<section class="about-quote">
  <blockquote>"אתה לא עוד כרטיס בתור. אתה לקוח שאני מכיר בשם, ומלווה מהשיחה הראשונה ועד שהאתר באוויר."</blockquote>
  <cite>— מייסד LandingFlow</cite>
</section>

<section class="about-body">
  <p>אני מפתח ומנהל פתרונות טכנולוגיים לעסקים — עצמאי, לא חברה. אני בונה דפי נחיתה, אתרים, אוטומציות AI ומערכות CRM, והכל מאדם אחד שמלווה אותך אישית מההתחלה ועד הסוף.</p>
  <p>בניגוד לחברות גדולות, כשאתה עובד איתי — אתה מדבר ישירות עם מי שבונה לך את הפתרון. אין מתווכים, אין מוקד תמיכה, אין בירוקרטיה. רק וואטסאפ ישיר, זמינות אמיתית, ותוצאות מהירות.</p>
</section>

<section class="about-list-section">
  <div class="container">
    <h2>מה אני עושה</h2>
    <div class="about-services">
      <div class="about-service"><span class="ic">🤖</span><div><h3>אוטומציות AI</h3><p>צ'אטבוטים חכמים, מענה אוטומטי ללידים, חיבורים בין מערכות — AI שעובד בשבילך 24/7.</p></div></div>
      <div class="about-service"><span class="ic">📄</span><div><h3>דפי נחיתה ואתרים</h3><p>בנייה מהירה, עיצוב מודרני, התאמה מלאה למובייל — דפים שמביאים לקוחות.</p></div></div>
      <div class="about-service"><span class="ic">📊</span><div><h3>CRM וניהול לידים</h3><p>מערכת שמרכזת את כל הפניות, שולחת התראות, ועוזרת לך לסגור יותר עסקאות.</p></div></div>
      <div class="about-service"><span class="ic">☁️</span><div><h3>אחסון וניטור</h3><p>האתר שלך תמיד זמין, מהיר ומאובטח — עם גיבויים יומיים וניטור 24/7.</p></div></div>
      <div class="about-service"><span class="ic">🔍</span><div><h3>ביקורות אתרים</h3><p>סריקה מקיפה ל-SEO, אבטחה, נגישות וביצועים — עם דוח מפורט והמלצות.</p></div></div>
    </div>
  </div>
</section>

<section class="about-why">
  <h2>למה לעבוד איתי</h2>
  <div class="about-why-grid">
    <div class="about-why-item"><span class="chk">✓</span><span><strong>זמינות אמיתית</strong> — וואטסאפ ישיר, לא כרטיס תמיכה.</span></div>
    <div class="about-why-item"><span class="chk">✓</span><span><strong>שקיפות</strong> — אתה יודע מה קורה בכל שלב.</span></div>
    <div class="about-why-item"><span class="chk">✓</span><span><strong>גמישות</strong> — שינויים קורים מהר, בלי בירוקרטיה.</span></div>
    <div class="about-why-item"><span class="chk">✓</span><span><strong>מחיר הוגן</strong> — בלי עלויות תקורה של משרדים וצוותים.</span></div>
    <div class="about-why-item"><span class="chk">✓</span><span><strong>פתרונות מותאמים</strong> — AI, אוטומציות, CRM — לא רק אתר, מערכת שלמה.</span></div>
  </div>
</section>

<section class="about-cta">
  <a href="<?= $url('contact') ?>" class="btn btn-primary btn-lg">📞 דברו איתי</a>
</section>

<?php $content = ob_get_clean(); include __DIR__ . "/../partials/layout.php"; ?>
