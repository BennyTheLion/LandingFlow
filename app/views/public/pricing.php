<?php ob_start(); ?>
<style>
.pricing-hero{padding:160px 0 0;text-align:center}
.pricing-hero h1{font-size:clamp(1.8rem,4.5vw,2.6rem);font-weight:800;line-height:1.25;max-width:720px;margin:0 auto 16px}
.pricing-hero h1 span{color:var(--primary)}
.pricing-hero .sub{font-size:1.05rem;color:var(--ink-soft);max-width:560px;margin:0 auto 28px;line-height:1.6}
.hero-ctas{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
.btn-wa{background:#25D366;color:#fff;box-shadow:0 4px 12px rgba(37,211,102,.25)}.btn-wa:hover{background:#1da851;transform:translateY(-1px)}

.value-section{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:48px 32px;max-width:900px;margin:0 auto 0;text-align:center}
.value-section h2{font-size:1.5rem;font-weight:800;margin-bottom:20px}
.value-section p{color:var(--ink-soft);font-size:.95rem;line-height:1.7;max-width:700px;margin:0 auto}
.value-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-top:28px}
.value-item{display:flex;align-items:center;gap:8px;font-size:.85rem;font-weight:600;color:var(--ink-soft)}
.value-item .icon{font-size:1.1rem;width:36px;height:36px;border-radius:8px;background:var(--signal-soft);display:flex;align-items:center;justify-content:center;flex-shrink:0}

.plans-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;max-width:1100px;margin:0 auto 0}
.plan-card{background:var(--surface);border:1.5px solid var(--border);border-radius:var(--radius-lg);padding:36px 28px;position:relative;transition:all .25s;display:flex;flex-direction:column}
.plan-card:hover{box-shadow:var(--shadow-lg);transform:translateY(-2px)}
.plan-card.featured{border-color:var(--stamp);box-shadow:0 0 0 1px var(--stamp),var(--shadow-md)}
.plan-card.featured:hover{box-shadow:0 0 0 1px var(--stamp),var(--shadow-lg)}
.plan-badge{position:absolute;top:-13px;left:50%;transform:translateX(-50%);background:var(--stamp);color:#fff;font-size:.72rem;font-weight:700;padding:5px 18px;border-radius:100px;white-space:nowrap}
.c-start{border-top:4px solid var(--border)}
.c-business{border-top:4px solid var(--stamp)}
.c-premium{border-top:4px solid var(--border)}
.c-enterprise{border-top:4px solid var(--border)}
.plan-name{font-size:1.2rem;font-weight:800;margin-bottom:4px}
.plan-desc{font-size:.8rem;color:var(--ink-faint);margin-bottom:20px}
.plan-price-setup{font-size:.85rem;color:var(--ink-soft);margin-bottom:2px}
.plan-price-setup strong{color:var(--ink);font-family:var(--font-mono)}
.plan-price{font-family:var(--font-mono);font-size:2.4rem;font-weight:800;color:var(--ink);margin-bottom:2px}
.plan-price-sub{font-size:.78rem;color:var(--ink-faint);margin-bottom:24px}
.plan-price-options{display:flex;flex-direction:column;gap:8px;margin-bottom:12px}
.price-option{padding:12px 14px;border-radius:10px;background:var(--surface-2)}
.price-option .plan-price{font-size:1.6rem}
.price-option .plan-price-setup{margin-bottom:0}
.price-option .plan-price-sub{margin-bottom:0;font-size:.7rem}
.price-divider{text-align:center;font-size:.72rem;font-weight:700;color:var(--ink-faint);margin:2px 0}
.price-alt{border:1.5px dashed var(--border);background:transparent}
.plan-features{display:flex;flex-direction:column;gap:12px;margin-bottom:28px;flex:1}
.plan-features li{font-size:.9rem;color:var(--ink-soft);display:flex;align-items:center;gap:10px}
.plan-features li .check{color:var(--success);font-weight:700;flex-shrink:0}

.compare-section{margin-bottom:0}
.compare-section h2{text-align:center;font-size:1.4rem;font-weight:800;margin-bottom:32px}
.compare-wrap{overflow-x:auto;max-width:900px;margin:0 auto}
.compare-table{width:100%;border-collapse:collapse;font-size:.88rem}
.compare-table th,.compare-table td{padding:12px 16px;text-align:center;border-bottom:1px solid var(--border)}
.compare-table th{background:var(--surface-2);font-weight:700;color:var(--ink-soft);font-size:.75rem;text-transform:uppercase}
.compare-table td:first-child,.compare-table th:first-child{text-align:right;font-weight:700;background:var(--surface);color:var(--ink)}
.compare-table tr:hover td{background:rgba(31,170,109,.05)}
.compare-table .yes{color:var(--success);font-weight:700}
.compare-table .no{color:var(--ink-faint)}

.extra-section{max-width:800px;margin:0 auto 0}
.extra-section h2{text-align:center;font-size:1.4rem;font-weight:800;margin-bottom:28px}
.extra-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px}
.extra-item{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.9rem}
.extra-price{font-family:var(--font-mono);font-weight:700;color:var(--primary);white-space:nowrap}

.positioning{max-width:680px;margin:0 auto;padding:20px 24px;text-align:center}
.positioning blockquote{font-family:var(--font-serif);font-size:clamp(1.2rem,2.8vw,1.6rem);font-weight:700;line-height:1.55;color:var(--ink)}
.positioning cite{display:block;margin-top:16px;font-family:var(--font-mono);font-size:.78rem;color:var(--ink-faint);font-style:normal}
.positioning p.sub{color:var(--ink-soft);font-size:.95rem;line-height:1.65;max-width:560px;margin:16px auto 0}

.cta-section{text-align:center;padding:48px 0 40px}
.cta-section h2{font-size:1.6rem;font-weight:800;margin-bottom:12px}
.cta-section p{color:var(--ink-soft);font-size:1rem;margin-bottom:28px}
.cta-highlight{display:inline-flex;align-items:center;gap:8px;background:var(--stamp-soft);color:var(--stamp);font-size:.85rem;font-weight:700;padding:8px 18px;border-radius:100px;margin-bottom:20px}
</style>

<!-- HERO -->
<section class="pricing-hero"><div class="container">
  <h1>אתר לעסק שלך שלא רק <span>נראה טוב</span> — אלא <span>עובד ומביא לקוחות</span></h1>
  <p class="sub">בניית אתרים + תחזוקה + לידים + אוטומציה — שירות מנוהל חודשי</p>
  <div class="hero-ctas">
    <a href="<?= $url('contact') ?>" class="btn btn-primary btn-lg">📋 קבל הצעת מחיר</a>
    <a href="https://wa.me/972528529448" target="_blank" rel="noopener" class="btn btn-wa btn-lg">💬 דברו איתנו בוואטסאפ</a>
  </div>
  <div class="cta-highlight" style="margin-top:24px">⏱️ תוך 24 שעות אתה יכול להתחיל לקבל לידים מהאתר שלך</div>
</div></section>

<!-- VALUE -->
<section><div class="container">
  <div class="value-section">
    <h2>לא מוכרים אתר — מנהלים מערכת עסקית חיה</h2>
    <p>רוב בוני האתרים נותנים לך קוד ועוזבים. אנחנו נותנים לך מערכת שממשיכה לעבוד בשבילך — אחסון, אבטחה, לידים, CRM, ניטור, גיבויים ועדכונים. הכל כלול במחיר החודשי.</p>
    <div class="value-grid">
      <div class="value-item"><span class="icon">☁️</span> אחסון + SSL</div>
      <div class="value-item"><span class="icon">🛡️</span> אבטחה 24/7</div>
      <div class="value-item"><span class="icon">📊</span> CRM + לידים</div>
      <div class="value-item"><span class="icon">⚡</span> אוטומציות</div>
      <div class="value-item"><span class="icon">📡</span> ניטור + גיבויים</div>
      <div class="value-item"><span class="icon">🔄</span> עדכונים שוטפים</div>
    </div>
  </div>
</div></section>

<!-- PLANS -->
<section><div class="container">
  <div class="head-center" style="margin-bottom:44px">
    <span class="section-eyebrow">חבילות שירות</span>
    <h2 class="section-title" style="margin:0 auto 12px">בחר את החבילה שמתאימה לעסק שלך</h2>
    <p class="section-sub" style="margin:0 auto 42px">כל החבילות כוללות שירות מנוהל — לא רק בנייה חד-פעמית</p>
  </div>

  <div class="plans-grid">
    <!-- START -->
    <div class="plan-card c-start">
      <div class="plan-name">START</div>
      <div class="plan-desc">לעסקים בתחילת הדרך</div>
      <div class="plan-price-options">
        <div class="price-option">
          <div class="plan-price-setup"><strong>₪1,500</strong> הקמה</div>
          <div class="plan-price">₪150</div>
          <div class="plan-price-sub">לחודש</div>
        </div>
        <div class="price-divider">או</div>
        <div class="price-option price-alt">
          <div class="plan-price-setup"><strong>ללא עלות</strong> הקמה</div>
          <div class="plan-price">₪300</div>
          <div class="plan-price-sub">לחודש · 24 חודשים</div>
        </div>
      </div>
      <ul class="plan-features">
        <li><span class="check">✓</span> דף נחיתה אחד</li>
        <li><span class="check">✓</span> אחסון + SSL</li>
        <li><span class="check">✓</span> גיבויים אוטומטיים</li>
        <li><span class="check">✓</span> ניטור 24/7</li>
        <li><span class="check">✓</span> טופס יצירת קשר</li>
        <li><span class="check">✓</span> חיבור WhatsApp</li>
        <li><span class="check">✓</span> SEO בסיסי</li>
        <li><span class="check">✓</span> תמיכה במייל</li>
      </ul>
      <a href="<?= $url('contact') ?>" class="btn btn-primary btn-block">התחל עכשיו</a>
    </div>

    <!-- BUSINESS -->
    <div class="plan-card c-business featured">
      <div class="plan-badge">⭐ ההמלצה שלי</div>
      <div class="plan-name">BUSINESS</div>
      <div class="plan-desc">לעסקים שרוצים לגדול</div>
      <div class="plan-price-options">
        <div class="price-option">
          <div class="plan-price-setup"><strong>₪2,990</strong> הקמה</div>
          <div class="plan-price">₪290</div>
          <div class="plan-price-sub">לחודש</div>
        </div>
        <div class="price-divider">או</div>
        <div class="price-option price-alt">
          <div class="plan-price-setup"><strong>ללא עלות</strong> הקמה</div>
          <div class="plan-price">₪550</div>
          <div class="plan-price-sub">לחודש · 24 חודשים</div>
        </div>
      </div>
      <ul class="plan-features">
        <li><span class="check">✓</span> אתר עד 10 עמודים</li>
        <li><span class="check">✓</span> מערכת CRM מובנית</li>
        <li><span class="check">✓</span> מערכת ניהול לידים</li>
        <li><span class="check">✓</span> אחסון + גיבויים יומיים</li>
        <li><span class="check">✓</span> SEO מתקדם</li>
        <li><span class="check">✓</span> אבטחה מלאה</li>
        <li><span class="check">✓</span> דוחות תקינות חודשיים</li>
        <li><span class="check">✓</span> עדכונים שוטפים</li>
        <li><span class="check">✓</span> תמיכה בוואטסאפ</li>
      </ul>
      <a href="<?= $url('contact') ?>" class="btn btn-primary btn-block">התחל עכשיו</a>
    </div>

    <!-- PREMIUM -->
    <div class="plan-card c-premium">
      <div class="plan-name">PREMIUM</div>
      <div class="plan-desc">לעסקים שרוצים אוטומציה מלאה</div>
      <div class="plan-price-options">
        <div class="price-option">
          <div class="plan-price-setup"><strong>₪4,990</strong> הקמה</div>
          <div class="plan-price">₪490</div>
          <div class="plan-price-sub">לחודש</div>
        </div>
        <div class="price-divider">או</div>
        <div class="price-option price-alt">
          <div class="plan-price-setup"><strong>ללא עלות</strong> הקמה</div>
          <div class="plan-price">₪950</div>
          <div class="plan-price-sub">לחודש · 24 חודשים</div>
        </div>
      </div>
      <ul class="plan-features">
        <li><span class="check">✓</span> כל מה שב-BUSINESS</li>
        <li><span class="check">✓</span> אוטומציות מותאמות</li>
        <li><span class="check">✓</span> WhatsApp אוטומטי</li>
        <li><span class="check">✓</span> Dashboard ניהול</li>
        <li><span class="check">✓</span> דוחות חודשיים מפורטים</li>
        <li><span class="check">✓</span> שיפור מהירות מתמשך</li>
        <li><span class="check">✓</span> SLA משופר</li>
        <li><span class="check">✓</span> תמיכת VIP</li>
      </ul>
      <a href="<?= $url('contact') ?>" class="btn btn-primary btn-block">התחל עכשיו</a>
    </div>

    <!-- ENTERPRISE -->
    <div class="plan-card c-enterprise">
      <div class="plan-name">ENTERPRISE</div>
      <div class="plan-desc">פתרונות מותאמים לארגונים</div>
      <div class="plan-price-options">
        <div class="price-option">
          <div class="plan-price-setup"><strong>החל מ-₪9,990</strong> הקמה</div>
          <div class="plan-price">₪990</div>
          <div class="plan-price-sub">לחודש</div>
        </div>
        <div class="price-divider">או</div>
        <div class="price-option price-alt">
          <div class="plan-price-setup"><strong>ללא עלות</strong> הקמה</div>
          <div class="plan-price">₪1,890</div>
          <div class="plan-price-sub">לחודש · 24 חודשים</div>
        </div>
      </div>
      <ul class="plan-features">
        <li><span class="check">✓</span> פיתוח מותאם אישית</li>
        <li><span class="check">✓</span> אינטגרציות API</li>
        <li><span class="check">✓</span> חיבור למספר מערכות</li>
        <li><span class="check">✓</span> הרשאות מתקדמות</li>
        <li><span class="check">✓</span> תמיכה מועדפת 24/7</li>
        <li><span class="check">✓</span> SLA גבוה</li>
        <li><span class="check">✓</span> ייעוץ אסטרטגי</li>
        <li><span class="check">✓</span> פיתוחים בהתאמה</li>
      </ul>
      <a href="<?= $url('contact') ?>" class="btn btn-primary btn-block">צור קשר</a>
    </div>
  </div>
</div></section>

<!-- COMPARISON TABLE -->
<section><div class="container">
  <div class="compare-section">
    <h2>השוואת חבילות</h2>
    <div class="compare-wrap">
      <table class="compare-table">
        <thead><tr>
          <th>שירות</th><th>START</th><th>BUSINESS</th><th>PREMIUM</th><th>ENTERPRISE</th>
        </tr></thead>
        <tbody>
          <tr><td>אחסון + SSL</td><td class="yes">✓</td><td class="yes">✓</td><td class="yes">✓</td><td class="yes">✓</td></tr>
          <tr><td>גיבויים</td><td class="yes">✓</td><td class="yes">✓</td><td class="yes">✓</td><td class="yes">✓</td></tr>
          <tr><td>ניטור</td><td class="yes">✓</td><td class="yes">✓</td><td class="yes">✓</td><td class="yes">✓</td></tr>
          <tr><td>CRM</td><td class="no">—</td><td class="yes">✓</td><td class="yes">✓</td><td class="yes">✓</td></tr>
          <tr><td>מערכת לידים</td><td class="no">—</td><td class="yes">✓</td><td class="yes">✓</td><td class="yes">✓</td></tr>
          <tr><td>אוטומציות</td><td class="no">—</td><td class="no">—</td><td class="yes">✓</td><td class="yes">✓</td></tr>
          <tr><td>WhatsApp אוטומטי</td><td class="no">—</td><td class="no">—</td><td class="yes">✓</td><td class="yes">✓</td></tr>
          <tr><td>SEO</td><td>בסיסי</td><td class="yes">✓</td><td class="yes">✓</td><td class="yes">✓</td></tr>
          <tr><td>דוחות</td><td class="no">—</td><td>חודשי</td><td>מפורט</td><td>מותאם</td></tr>
          <tr><td>תמיכה</td><td>מייל</td><td>וואטסאפ</td><td>VIP</td><td>24/7</td></tr>
          <tr><td>אבטחה</td><td>בסיסית</td><td class="yes">✓</td><td class="yes">✓</td><td class="yes">✓</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div></section>

<!-- ONE-TIME SERVICES -->
<section><div class="container">
  <div class="extra-section">
    <h2>שירותים חד-פעמיים</h2>
    <div class="extra-grid">
      <div class="extra-item"><span>🔍 ביקורת אתר</span><span class="extra-price">₪290</span></div>
      <div class="extra-item"><span>📈 קידום SEO</span><span class="extra-price">₪790</span></div>
      <div class="extra-item"><span>⚡ שיפור מהירות</span><span class="extra-price">₪490</span></div>
      <div class="extra-item"><span>📄 דף נחיתה נוסף</span><span class="extra-price">₪890</span></div>
      <div class="extra-item"><span>📝 עמוד תוכן נוסף</span><span class="extra-price">₪290</span></div>
      <div class="extra-item"><span>💬 חיבור WhatsApp</span><span class="extra-price">₪190</span></div>
      <div class="extra-item"><span>📊 Google Analytics</span><span class="extra-price">₪190</span></div>
      <div class="extra-item"><span>🔒 תיקון פריצה</span><span class="extra-price">₪590</span></div>
      <div class="extra-item"><span>🚚 מעבר אתר</span><span class="extra-price">₪990</span></div>
    </div>
  </div>
</div></section>

<!-- POSITIONING -->
<section><div class="container">
  <div class="positioning">
    <blockquote>"אני לא בונה אתרים — אני בונה מערכות דיגיטליות שמביאות לידים לעסק שלך."</blockquote>
    <cite>— מייסד LandingFlow</cite>
    <p class="sub">האתר שלך הוא לא עוד פרויקט שאני מסיים ועוזב. זאת מערכת חיה שאני מתחזק, משפר ומנטר — כדי שהיא תמשיך להביא לך לקוחות כל חודש.</p>
  </div>
</div></section>

<!-- CTA -->
<section class="cta-section"><div class="container">
  <div class="cta-highlight">📞 מענה אישי — לא צוות מכירות</div>
  <h2>מוכן להתחיל לקבל לידים?</h2>
  <p>השאירו פרטים וניצור קשר תוך 24 שעות עם הצעה מותאמת</p>
  <div class="hero-ctas">
    <a href="<?= $url('contact') ?>" class="btn btn-primary btn-lg">📋 קבל הצעת מחיר</a>
    <a href="https://wa.me/972528529448" target="_blank" rel="noopener" class="btn btn-wa btn-lg">💬 וואטסאפ</a>
  </div>
</div></section>

<?php $content = ob_get_clean(); include __DIR__ . '/../partials/layout.php'; ?>
