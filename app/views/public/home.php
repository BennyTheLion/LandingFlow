<?php ob_start() ?>
<style>
.hero{position:relative;padding:160px 0 80px;background:var(--bg);overflow:hidden}
.hero-inner{max-width:720px;margin:0 auto;text-align:center}
.hero h1{font-size:clamp(1.8rem,4.5vw,2.8rem);font-weight:800;line-height:1.25;max-width:680px;margin:0 auto 16px;color:var(--ink)}
.hero h1 span{color:var(--primary)}
.hero-sub{font-size:1rem;color:var(--ink-soft);max-width:540px;margin:0 auto 32px;line-height:1.6}
.hero-ctas{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-bottom:48px}
.hero-metric{display:inline-flex;align-items:center;gap:6px;font-size:.82rem;color:var(--ink-faint);margin-bottom:64px}
.hero-metric strong{color:var(--success);font-family:var(--font-mono)}

.stats-strip{background:var(--ink);color:#fff;padding:48px 0}
.stats-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:32px;text-align:center}
@media(min-width:640px){.stats-grid{grid-template-columns:repeat(4,1fr)}}
.stat-val{font-family:var(--font-mono);font-size:2rem;font-weight:700;color:var(--success)}
.stat-label{font-size:.85rem;color:var(--ink-faint);margin-top:8px}

.features-section{padding:80px 0}
.features-header{text-align:center;margin-bottom:48px}
.features-header h2{font-size:clamp(1.6rem,4vw,2.4rem);font-weight:800;line-height:1.25;max-width:680px;margin-bottom:12px}
.features-header p{color:var(--ink-soft);font-size:1rem;max-width:540px;margin:0 auto}
.features-grid{display:grid;grid-template-columns:1fr;gap:24px;max-width:1050px;margin:0 auto}
@media(min-width:640px){.features-grid{grid-template-columns:repeat(2,1fr)}}
@media(min-width:900px){.features-grid{grid-template-columns:repeat(3,1fr)}}
.feature-item{display:flex;gap:16px;align-items:flex-start}
.feature-icon{width:44px;height:44px;border-radius:10px;background:rgba(0,0,0,.06);display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0}
.feature-text h3{font-size:.95rem;font-weight:700;margin-bottom:4px}
.feature-text p{font-size:.85rem;color:var(--ink-soft);line-height:1.5}

.why-me-section{padding:80px 0;background:var(--surface);border-top:1px solid var(--border)}
.why-me-header{text-align:center;margin-bottom:48px}
.why-me-header h2{font-size:clamp(1.6rem,4vw,2.4rem);font-weight:800;margin-bottom:12px}
.why-me-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;max-width:900px;margin:0 auto}
@media(max-width:700px){.why-me-grid{grid-template-columns:1fr}}
.why-me-card{background:var(--bg);border:1px solid var(--border);border-radius:12px;padding:28px 24px;text-align:center;transition:all .25s}
.why-me-card:hover{box-shadow:var(--shadow-md);transform:translateY(-2px)}
.why-me-card .icon{font-size:2rem;margin-bottom:12px}
.why-me-card h3{font-size:.95rem;font-weight:700;margin-bottom:6px}
.why-me-card p{font-size:.82rem;color:var(--ink-soft);line-height:1.5}

.audit-section{padding:80px 0;background:var(--surface);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
.audit-grid{display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center;max-width:900px;margin:0 auto}
@media(max-width:700px){.audit-grid{grid-template-columns:1fr;text-align:center}}
.audit-info h2{font-size:clamp(1.6rem,4vw,2.4rem);font-weight:800;line-height:1.25;margin-bottom:12px;text-align:center}
.audit-info p{color:var(--ink-soft);font-size:1rem;margin-bottom:24px;line-height:1.6}
.audit-checks{display:flex;flex-direction:column;gap:10px}
.audit-check{display:flex;align-items:center;gap:10px;font-size:.88rem}
.audit-check .dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.audit-check .dot.good{background:var(--success)}
.audit-check .dot.med{background:var(--warning)}
.audit-check .dot.bad{background:var(--danger)}

.cta-section{padding:80px 0;text-align:center}
.cta-section h2{font-size:clamp(1.6rem,4vw,2.4rem);font-weight:800;line-height:1.25;margin-bottom:12px}
.cta-section p{color:var(--ink-soft);font-size:1rem;max-width:540px;margin:0 auto 28px}
</style>

<section class="hero">
  <div class="hero-inner">
    <h1>פתרונות טכנולוגיים לעסק שלך,<br><span>בליווי אישי צמוד.</span></h1>
    <p class="hero-sub">דפי נחיתה, אתרים, אוטומציות AI וניטור — הכל מאדם אחד שמבין טכנולוגיה ומכיר אותך אישית.</p>
    <div class="hero-ctas">
      <a href="<?= $url('demo') ?>" class="btn btn-primary btn-lg">🚀 צור אתר דמו עכשיו</a>
      <a href="<?= $url('audit') ?>" class="btn btn-secondary btn-lg">🔍 בדיקת אתר חינם</a>
    </div>
    <div class="hero-metric">💬 <strong>וואטסאפ ישיר</strong> — לא כרטיס תמיכה, קשר אישי</div>
  </div>
</section>

<section class="features-section">
  <div class="container">
    <div class="features-header">
      <h2>פתרונות טכנולוגיים — הכל מאדם אחד</h2>
      <p>דפי נחיתה, אתרים, אוטומציות מבוססות AI, ניטור וניהול לידים — אין צורך במספר ספקים.</p>
    </div>
    <div class="features-grid">
      <div class="feature-item"><div class="feature-icon">📄</div><div class="feature-text"><h3>דפי נחיתה</h3><p>דפים מהירים להמרה — טפסים חכמים, A/B טסטינג ואנליטיקס.</p></div></div>
      <div class="feature-item"><div class="feature-icon">💻</div><div class="feature-text"><h3>פיתוח אתרים</h3><p>אתרי תדמית, חנויות אונליין ומערכות — קוד נקי, SEO מובנה.</p></div></div>
      <div class="feature-item"><div class="feature-icon">🤖</div><div class="feature-text"><h3>אוטומציות AI</h3><p>צ'אטבוטים, מענה אוטומטי ללידים, אינטגרציות חכמות — AI שעובד בשבילך.</p></div></div>
      <div class="feature-item"><div class="feature-icon">📡</div><div class="feature-text"><h3>ניטור 24/7</h3><p>בדיקה כל 60 שניות ממספר מוקדים, התראות מיידיות בוואטסאפ.</p></div></div>
      <div class="feature-item"><div class="feature-icon">🔍</div><div class="feature-text"><h3>ביקורות אוטומטיות</h3><p>SEO, אבטחה, נגישות וביצועים — דוח שבועי מסודר.</p></div></div>
      <div class="feature-item"><div class="feature-icon">📊</div><div class="feature-text"><h3>CRM ולידים</h3><p>כל פנייה נכנסת אוטומטית, עם ניתוב ומעקב מכירה.</p></div></div>
    </div>
  </div>
</section>

<section class="why-me-section">
  <div class="container">
    <div class="why-me-header">
      <h2>למה לעבוד עם אדם אחד ולא עם חברה?</h2>
    </div>
    <div class="why-me-grid">
      <div class="why-me-card">
        <div class="icon">👤</div>
        <h3>קשר ישיר</h3>
        <p>אתה מדבר ישירות עם מי שבונה לך את הפתרון. אין מתווכים, אין מוקדנים.</p>
      </div>
      <div class="why-me-card">
        <div class="icon">⚡</div>
        <h3>החלטות מהירות</h3>
        <p>אין בירוקרטיה, אין אישורי הנהלה. רוצה לשנות משהו? זה קורה עכשיו.</p>
      </div>
      <div class="why-me-card">
        <div class="icon">💰</div>
        <h3>מחיר הוגן</h3>
        <p>בלי עלויות תקורה של משרד, מזכירות וצוות מיותר — אתה משלם רק על העבודה.</p>
      </div>
      <div class="why-me-card">
        <div class="icon">🎯</div>
        <h3>מחויבות מלאה</h3>
        <p>הפרויקט שלך הוא הפרויקט שלי. אין "אעביר למישהו אחר" — אני איתך עד הסוף.</p>
      </div>
      <div class="why-me-card">
        <div class="icon">🔧</div>
        <h3>גמישות מקסימלית</h3>
        <p>צריך שינוי קטן? תוספת דחופה? רעיון חדש? הכל אפשרי, בלי חוזים נוקשים.</p>
      </div>
      <div class="why-me-card">
        <div class="icon">💬</div>
        <h3>זמינות אמיתית</h3>
        <p>לא כרטיס תמיכה, לא מחכה בתור. וואטסאפ ישיר — ואתה מקבל מענה אמיתי.</p>
      </div>
    </div>
  </div>
</section>

<section class="audit-section">
  <div class="container audit-grid">
    <div class="audit-info">
      <h2>בדיקת אתר מקיפה בחינם</h2>
      <p>הזינו כתובת URL וקבלו תוך דקות דוח מלא על SEO, אבטחה, נגישות, ביצועים ודרישות משפטיות.</p>
      <div class="audit-checks">
        <div class="audit-check"><span class="dot good"></span> ציון כללי + פירוט לפי קטגוריה</div>
        <div class="audit-check"><span class="dot good"></span> רשימת בעיות מדורגת לפי השפעה</div>
        <div class="audit-check"><span class="dot med"></span> המלצות מעשיות לתיקון</div>
        <div class="audit-check"><span class="dot bad"></span> שליחה במייל + וואטסאפ</div>
      </div>
      <a href="<?= $url('audit') ?>" class="btn btn-primary btn-lg" style="margin-top:24px">בדוק את האתר שלך עכשיו</a>
    </div>
  </div>
</section>

<section class="cta-section" style="background:linear-gradient(135deg,var(--primary-dark),var(--primary))">
  <div class="container">
    <h2 style="color:#fff">רוצה לראות איך האתר שלך ייראה?</h2>
    <p style="color:rgba(255,255,255,.8)">שלח לי הודעה — אני אחזור אליך אישית עם הצעה מותאמת.</p>
    <a href="<?= $url('contact') ?>" class="btn btn-lg" style="background:#fff;color:var(--primary);font-weight:800">📞 דברו איתי</a>
  </div>
</section>

<?php $content = ob_get_clean(); include __DIR__ . '/../partials/layout.php'; ?>
