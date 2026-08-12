<?php ob_start() ?>
<style>
.hero{position:relative;padding:150px 0 90px;background:var(--bg);overflow:hidden}
.hero-grid{display:grid;grid-template-columns:1fr;gap:48px;align-items:center;max-width:1100px;margin:0 auto;padding:0 24px}
@media(min-width:960px){.hero-grid{grid-template-columns:1.05fr 1fr;gap:64px}}
.hero-text{text-align:center}
@media(min-width:960px){.hero-text{text-align:right}}
.hero h1{font-size:clamp(2rem,4.3vw,3.1rem);font-weight:900;line-height:1.22;margin-bottom:18px;color:var(--ink)}
.hero h1 em{font-style:normal;color:var(--signal);position:relative;display:inline-block}
.underline-stroke{position:absolute;right:0;left:0;bottom:-8px;width:100%;height:16px;overflow:visible;pointer-events:none}
.underline-stroke path{fill:none;stroke:var(--signal);stroke-width:5;stroke-linecap:round;stroke-dasharray:420;stroke-dashoffset:420;animation:drawUnderline .9s ease .6s forwards}
@keyframes drawUnderline{to{stroke-dashoffset:0}}
.hero-visual{position:relative}
.stamp-mark{position:absolute;bottom:-30px;left:-30px;width:124px;height:124px;transform:rotate(-11deg);mix-blend-mode:multiply;opacity:.9;pointer-events:none;z-index:2}
@media(max-width:959px){.stamp-mark{width:92px;height:92px;bottom:-18px;left:-8px}}
.stamp-mark text{font-family:var(--font-mono)}
.hero-sub{font-size:1.05rem;color:var(--ink-soft);max-width:460px;margin:0 auto 28px;line-height:1.65}
@media(min-width:960px){.hero-sub{margin-right:0;margin-left:0}}
.hero-ctas{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-bottom:22px}
@media(min-width:960px){.hero-ctas{justify-content:flex-start}}
@media(min-width:960px){.hero-badge-row{display:flex;justify-content:flex-start}}
.hero-badge-row{display:flex;justify-content:center}

.audit-demo{background:var(--surface);border:1px solid var(--border);border-radius:20px;box-shadow:var(--shadow-lg);overflow:hidden;max-width:400px;margin:0 auto}
.audit-demo-bar{display:flex;align-items:center;gap:8px;padding:14px 16px;border-bottom:1px solid var(--border);background:var(--surface-2)}
.audit-demo-dot{width:9px;height:9px;border-radius:50%}
.audit-demo-dot.r{background:#E05252}.audit-demo-dot.y{background:#E0B152}.audit-demo-dot.g{background:var(--signal)}
.audit-demo-url{font-family:var(--font-mono);font-size:.76rem;color:var(--ink-soft);margin-right:6px;direction:ltr}
.audit-demo-body{display:flex;align-items:center;gap:22px;padding:26px 22px}
.audit-demo-score{position:relative;width:100px;height:100px;flex-shrink:0}
.score-ring{width:100px;height:100px;transform:rotate(0deg)}
.score-track{fill:none;stroke:var(--surface-2);stroke-width:8}
.score-fill{fill:none;stroke:var(--signal);stroke-width:8;stroke-linecap:round;stroke-dasharray:264;stroke-dashoffset:264;transform-origin:50px 50px;transform:rotate(-90deg);animation:scoreFill 1.3s cubic-bezier(.3,.8,.4,1) .3s forwards}
@keyframes scoreFill{to{stroke-dashoffset:34}}
.score-num{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-family:var(--font-mono);font-size:1.6rem;font-weight:700;color:var(--ink)}
.audit-demo-list{display:flex;flex-direction:column;gap:9px;flex:1}
.audit-demo-list li{display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:.84rem;color:var(--ink);opacity:0;transform:translateY(4px);animation:demoItemIn .45s ease forwards}
.audit-demo-list li b{font-family:var(--font-mono);font-weight:600;color:var(--ink-soft);font-size:.78rem}
@keyframes demoItemIn{to{opacity:1;transform:translateY(0)}}

.proof-strip{background:var(--ink);color:#fff;padding:64px 0}
.proof-header{text-align:center;max-width:600px;margin:0 auto 40px}
.proof-header .eyebrow{display:inline-block;font-family:var(--font-mono);font-size:.72rem;letter-spacing:.05em;color:var(--signal);margin-bottom:14px}
.proof-header h2{font-family:var(--font-serif);font-size:clamp(1.4rem,3.4vw,2rem);font-weight:800;line-height:1.32;margin-bottom:10px;color:#fff}
.proof-header p{color:rgba(255,255,255,.6);font-size:.94rem;line-height:1.65}
.proof-grid{display:grid;grid-template-columns:1fr;gap:1px;max-width:960px;margin:0 auto;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.14);border-radius:14px;overflow:hidden}
@media(min-width:760px){.proof-grid{grid-template-columns:repeat(3,1fr)}}
.proof-item{background:var(--ink);padding:28px 26px;display:flex;flex-direction:column;gap:9px}
.proof-item .proof-icon{font-size:1.3rem}
.proof-item h3{font-size:.98rem;font-weight:700;color:#fff}
.proof-item p{font-size:.83rem;color:rgba(255,255,255,.58);line-height:1.55;flex:1}
.proof-item a{display:inline-flex;align-items:center;gap:6px;font-family:var(--font-mono);font-size:.78rem;font-weight:600;color:var(--signal);margin-top:2px}
.proof-item a:hover{text-decoration:underline}

.features-section{padding:80px 0}
.features-header{text-align:center;margin-bottom:52px}
.features-header .eyebrow{display:inline-block;font-family:var(--font-mono);font-size:.76rem;letter-spacing:.07em;color:var(--signal);margin-bottom:14px}
.features-header h2{font-size:clamp(1.6rem,4vw,2.4rem);font-weight:800;line-height:1.25;max-width:680px;margin:0 auto 12px}
.features-header p{color:var(--ink-soft);font-size:1rem;max-width:540px;margin:0 auto}
.features-list{display:grid;grid-template-columns:1fr;column-gap:48px;max-width:1000px;margin:0 auto;border-top:1px solid var(--border)}
@media(min-width:760px){.features-list{grid-template-columns:1fr 1fr}}
.feature-item{display:flex;gap:16px;align-items:flex-start;padding:26px 2px;border-bottom:1px solid var(--border)}
.feature-icon{width:30px;height:30px;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:var(--ink);padding-top:3px;transition:color .2s}
.feature-icon .icon-line{width:26px;height:26px}
.feature-item:hover .feature-icon{color:var(--signal)}
.feature-text{flex:1}
.feature-text h3{display:flex;align-items:center;gap:9px;font-size:.95rem;font-weight:700;margin-bottom:6px}
.feature-included{font-family:var(--font-mono);font-size:.62rem;font-weight:600;letter-spacing:.02em;color:var(--signal);background:var(--signal-soft);padding:2px 7px;border-radius:4px;flex-shrink:0}
.feature-text p{font-size:.85rem;color:var(--ink-soft);line-height:1.5}

.why-me-section{padding:80px 0;background:var(--surface);border-top:1px solid var(--border)}
.why-me-header{text-align:center;margin-bottom:48px}
.doc-ref{display:inline-flex;align-items:center;gap:8px;font-family:var(--font-mono);font-size:.72rem;letter-spacing:.05em;color:var(--stamp);background:var(--stamp-soft);padding:5px 12px;border-radius:100px;margin-bottom:16px}
.why-me-header h2{font-size:clamp(1.6rem,4vw,2.4rem);font-weight:800;margin-bottom:10px}
.why-me-header p{color:var(--ink-soft);font-size:.98rem;max-width:480px;margin:0 auto}
.contract-list{list-style:none;max-width:760px;margin:0 auto;border-top:1.5px solid var(--ink)}
.contract-item{display:grid;grid-template-columns:auto 1fr auto;gap:20px;align-items:start;padding:24px 2px;border-bottom:1px solid var(--border)}
@media(max-width:640px){.contract-item{grid-template-columns:auto 1fr}}
.clause-mark{font-family:var(--font-serif);font-size:1.5rem;font-weight:900;color:var(--stamp);width:34px;text-align:center;line-height:1.3;padding-top:2px}
.clause-body h3{font-size:1rem;font-weight:700;margin-bottom:5px}
.clause-body p{font-size:.86rem;color:var(--ink-soft);line-height:1.55;max-width:480px}
.clause-note{font-family:var(--font-mono);font-size:.72rem;color:var(--ink-faint);white-space:nowrap;align-self:center;letter-spacing:.02em}
@media(max-width:640px){.clause-note{display:none}}
.contract-sign{max-width:760px;margin:36px auto 0;display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap;padding-top:28px;border-top:1px dashed var(--border)}
.sign-scribble{width:150px;height:48px;flex-shrink:0}
.sign-scribble path{fill:none;stroke:var(--stamp);stroke-linecap:round;stroke-linejoin:round;stroke-dasharray:340;stroke-dashoffset:340;transition:stroke-dashoffset 1s ease}
.sign-scribble path.ink{stroke-width:3}
.sign-scribble path.flourish{stroke-width:2;opacity:.75;transition-delay:.35s}
.sign-scribble.inview path{stroke-dashoffset:0}
.sign-meta{display:flex;flex-direction:column;gap:3px}
.sign-meta .who{font-weight:700;font-size:.92rem}
.sign-meta .note{font-family:var(--font-mono);font-size:.74rem;color:var(--ink-faint)}

.audit-section{padding:80px 0;background:var(--surface);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
.audit-inner{max-width:620px;margin:0 auto;text-align:center}
.audit-inner .eyebrow{display:inline-block;font-family:var(--font-mono);font-size:.76rem;letter-spacing:.07em;color:var(--signal);margin-bottom:14px}
.audit-inner h2{font-size:clamp(1.6rem,4vw,2.4rem);font-weight:800;line-height:1.25;margin-bottom:12px}
.audit-inner p{color:var(--ink-soft);font-size:1rem;margin-bottom:28px;line-height:1.6}
.audit-checks{display:flex;flex-direction:column;gap:10px;max-width:360px;margin:0 auto 28px;text-align:right}
.audit-check{display:flex;align-items:center;gap:10px;font-size:.88rem}
.audit-check .dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.audit-check .dot.good{background:var(--signal)}
.audit-check .dot.med{background:var(--warning)}
.audit-check .dot.bad{background:var(--danger)}

.cta-section{padding:90px 0;text-align:center;background:var(--ink)}
.cta-badge{display:inline-flex;align-items:center;gap:7px;font-size:.78rem;font-weight:600;color:var(--signal);background:rgba(31,170,109,.15);padding:6px 12px;border-radius:100px;margin-bottom:20px}
.cta-section h2{font-size:clamp(1.6rem,4vw,2.4rem);font-weight:800;line-height:1.25;margin-bottom:12px;color:#fff}
.cta-section p{color:rgba(255,255,255,.65);font-size:1rem;max-width:480px;margin:0 auto 28px}
</style>

<section class="hero">
  <div class="hero-grid">
    <div class="hero-text">
      <h1>פתרונות טכנולוגיים לעסק שלך,<br><em>בליווי אישי צמוד.<svg class="underline-stroke" viewBox="0 0 300 20" preserveAspectRatio="none" aria-hidden="true"><path d="M3,13 C50,18 95,5 148,11 C200,17 250,6 297,12"/></svg></em></h1>
      <p class="hero-sub">דפי נחיתה, אתרים, אוטומציות AI וניטור — הכל מאדם אחד שמבין טכנולוגיה ומכיר אותך אישית.</p>
      <div class="hero-ctas">
        <a href="<?= $url('demo') ?>" class="btn btn-primary btn-lg">🚀 צור אתר דמו עכשיו</a>
        <a href="<?= $url('audit') ?>" class="btn btn-secondary btn-lg">🔍 בדיקת אתר חינם</a>
      </div>
      <div class="hero-badge-row"><span class="live-badge"><span class="live-dot"></span>וואטסאפ ישיר — לא כרטיס תמיכה, קשר אישי</span></div>
    </div>
    <div class="hero-visual">
      <svg class="stamp-mark" viewBox="0 0 140 140" aria-hidden="true">
        <defs>
          <filter id="inkRoughHero" x="-30%" y="-30%" width="160%" height="160%">
            <feTurbulence type="fractalNoise" baseFrequency=".85" numOctaves="2" result="n"/>
            <feDisplacementMap in="SourceGraphic" in2="n" scale="3.2"/>
          </filter>
          <path id="stampRingHero" d="M70,70 m-54,0 a54,54 0 1,1 108,0 a54,54 0 1,1 -108,0"/>
        </defs>
        <g filter="url(#inkRoughHero)">
          <circle cx="70" cy="70" r="62" fill="none" stroke="var(--stamp)" stroke-width="2.5"/>
          <circle cx="70" cy="70" r="48" fill="none" stroke="var(--stamp)" stroke-width="1.5"/>
        </g>
        <text font-size="12.5" font-weight="700" fill="var(--stamp)" letter-spacing="1.3" direction="ltr" style="unicode-bidi:bidi-override"><textPath href="#stampRingHero" startOffset="2%">דבלב היצמוטוא אל ✦ תישיא קדבנ ✦</textPath></text>
        <text x="70" y="78" text-anchor="middle" font-family="var(--font-serif)" font-weight="900" font-size="27" fill="var(--stamp)">LF</text>
      </svg>
      <div class="audit-demo">
        <div class="audit-demo-bar">
          <span class="audit-demo-dot r"></span><span class="audit-demo-dot y"></span><span class="audit-demo-dot g"></span>
          <span class="audit-demo-url">example-business.co.il</span>
        </div>
        <div class="audit-demo-body">
          <div class="audit-demo-score">
            <svg class="score-ring" viewBox="0 0 100 100">
              <circle class="score-track" cx="50" cy="50" r="42"></circle>
              <circle class="score-fill" cx="50" cy="50" r="42"></circle>
            </svg>
            <div class="score-num" id="heroScoreNum">0</div>
          </div>
          <ul class="audit-demo-list">
            <li style="animation-delay:.4s">SEO <b>92</b></li>
            <li style="animation-delay:.65s">אבטחה <b>88</b></li>
            <li style="animation-delay:.9s">נגישות <b>95</b></li>
            <li style="animation-delay:1.15s">ביצועים <b>79</b></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>
<script>(function(){
  var el=document.getElementById('heroScoreNum');
  if(!el)return;
  var target=87,start=null;
  function step(ts){
    if(!start)start=ts;
    var p=Math.min((ts-start)/1300,1);
    el.textContent=Math.round(p*target);
    if(p<1)requestAnimationFrame(step);
  }
  requestAnimationFrame(step);
})();</script>

<section class="proof-strip">
  <div class="container">
    <div class="proof-header">
      <span class="eyebrow">בלי ביקורות עדיין — ובכוונה</span>
      <h2>אני לא מבקש שתאמינו לי. תבדקו בעצמכם.</h2>
      <p>אני עדיין בונה את קבוצת הלקוחות הראשונה שלי — מה שאומר גישה ישירה אליי, לא לצוות תמיכה. במקום ביקורות, הנה שלוש דרכים לבדוק את זה תוך דקות.</p>
    </div>
    <div class="proof-grid">
      <div class="proof-item">
        <span class="proof-icon">🔍</span>
        <h3>בדיקת אתר חינם</h3>
        <p>הזינו כתובת אתר וקבלו ציון אמיתי על SEO, אבטחה, נגישות וביצועים — תוך דקות, לא הבטחה.</p>
        <a href="<?= $url('audit') ?>">בדקו עכשיו ←</a>
      </div>
      <div class="proof-item">
        <span class="proof-icon">💬</span>
        <h3>מבחן זמינות</h3>
        <p>שלחו הודעה עכשיו בוואטסאפ. תקבלו תשובה ממני אישית — לא מבוט ולא מתור המתנה.</p>
        <a href="https://wa.me/972528529448" target="_blank" rel="noopener">שלחו הודעה ←</a>
      </div>
      <div class="proof-item">
        <span class="proof-icon">🚀</span>
        <h3>דמו חי תוך דקה</h3>
        <p>ענו על כמה שאלות קצרות וקבלו אב-טיפוס אמיתי לאתר שלכם, לא הדגמה גנרית.</p>
        <a href="<?= $url('demo') ?>">צרו דמו ←</a>
      </div>
    </div>
  </div>
</section>

<section class="features-section">
  <div class="container">
    <div class="features-header">
      <span class="eyebrow">מה כלול</span>
      <h2>פתרונות טכנולוגיים — הכל מאדם אחד</h2>
      <p>דפי נחיתה, אתרים, אוטומציות מבוססות AI, ניטור וניהול לידים — אין צורך במספר ספקים.</p>
    </div>
    <div class="features-list">
      <div class="feature-item"><div class="feature-icon"><svg class="icon-line" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="7" y1="13" x2="13" y2="13"></line><line x1="7" y1="16" x2="17" y2="16"></line></svg></div><div class="feature-text"><h3>דפי נחיתה <span class="feature-included">כלול</span></h3><p>דפים מהירים להמרה — טפסים חכמים, A/B טסטינג ואנליטיקס.</p></div></div>
      <div class="feature-item"><div class="feature-icon"><svg class="icon-line" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><circle cx="6" cy="7" r=".6" fill="currentColor" stroke="none"></circle><circle cx="8.6" cy="7" r=".6" fill="currentColor" stroke="none"></circle><path d="M9.5 13.5l-2 2 2 2M14.5 13.5l2 2-2 2"></path></svg></div><div class="feature-text"><h3>פיתוח אתרים <span class="feature-included">כלול</span></h3><p>אתרי תדמית, חנויות אונליין ומערכות — קוד נקי, SEO מובנה.</p></div></div>
      <div class="feature-item"><div class="feature-icon"><svg class="icon-line" viewBox="0 0 24 24"><path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3z"></path><path d="M19 15l.7 2 2 .7-2 .7-.7 2-.7-2-2-.7 2-.7.7-2z"></path></svg></div><div class="feature-text"><h3>אוטומציות AI <span class="feature-included">כלול</span></h3><p>צ'אטבוטים, מענה אוטומטי ללידים, אינטגרציות חכמות — AI שעובד בשבילך.</p></div></div>
      <div class="feature-item"><div class="feature-icon"><svg class="icon-line" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M7 12h2l1.5-4 3 8 1.5-4h2"></path></svg></div><div class="feature-text"><h3>ניטור 24/7 <span class="feature-included">כלול</span></h3><p>בדיקה כל 60 שניות ממספר מוקדים, התראות מיידיות בוואטסאפ.</p></div></div>
      <div class="feature-item"><div class="feature-icon"><svg class="icon-line" viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="6.5"></circle><path d="M8 10.5l1.8 1.8L14 8.5"></path><line x1="15.5" y1="15.5" x2="21" y2="21"></line></svg></div><div class="feature-text"><h3>ביקורות אוטומטיות <span class="feature-included">כלול</span></h3><p>SEO, אבטחה, נגישות וביצועים — דוח שבועי מסודר.</p></div></div>
      <div class="feature-item"><div class="feature-icon"><svg class="icon-line" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"></circle><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"></path><circle cx="17.5" cy="7.5" r="2.2"></circle><path d="M15.5 13.2c2.7.3 4.8 2.5 5 5.3"></path></svg></div><div class="feature-text"><h3>CRM ולידים <span class="feature-included">כלול</span></h3><p>כל פנייה נכנסת אוטומטית, עם ניתוב ומעקב מכירה.</p></div></div>
    </div>
  </div>
</section>

<section class="why-me-section">
  <div class="container">
    <div class="why-me-header">
      <span class="doc-ref">מסמך התחייבות · LF-01</span>
      <h2>למה לעבוד עם אדם אחד ולא עם חברה?</h2>
      <p>לא סיסמת שיווק — שש התחייבויות שאני עומד מאחוריהן אישית, בכל פרויקט.</p>
    </div>
    <ol class="contract-list">
      <li class="contract-item">
        <span class="clause-mark">א</span>
        <div class="clause-body"><h3>קשר ישיר</h3><p>אתה מדבר ישירות עם מי שבונה לך את הפתרון. אין מתווכים, אין מוקדנים.</p></div>
        <span class="clause-note">בלי מוקדנים</span>
      </li>
      <li class="contract-item">
        <span class="clause-mark">ב</span>
        <div class="clause-body"><h3>החלטות מהירות</h3><p>אין בירוקרטיה, אין אישורי הנהלה. רוצה לשנות משהו? זה קורה עכשיו.</p></div>
        <span class="clause-note">בלי אישורים</span>
      </li>
      <li class="contract-item">
        <span class="clause-mark">ג</span>
        <div class="clause-body"><h3>מחיר הוגן</h3><p>בלי עלויות תקורה של משרד, מזכירות וצוות מיותר — אתה משלם רק על העבודה.</p></div>
        <span class="clause-note">בלי תקורה</span>
      </li>
      <li class="contract-item">
        <span class="clause-mark">ד</span>
        <div class="clause-body"><h3>מחויבות מלאה</h3><p>הפרויקט שלך הוא הפרויקט שלי. אין "אעביר למישהו אחר" — אני איתך עד הסוף.</p></div>
        <span class="clause-note">עד הסוף</span>
      </li>
      <li class="contract-item">
        <span class="clause-mark">ה</span>
        <div class="clause-body"><h3>גמישות מקסימלית</h3><p>צריך שינוי קטן? תוספת דחופה? רעיון חדש? הכל אפשרי, בלי חוזים נוקשים.</p></div>
        <span class="clause-note">בלי חוזה נוקשה</span>
      </li>
      <li class="contract-item">
        <span class="clause-mark">ו</span>
        <div class="clause-body"><h3>זמינות אמיתית</h3><p>לא כרטיס תמיכה, לא מחכה בתור. וואטסאפ ישיר — ואתה מקבל מענה אמיתי.</p></div>
        <span class="clause-note">מענה אמיתי</span>
      </li>
    </ol>
    <div class="contract-sign">
      <svg class="sign-scribble" viewBox="0 0 220 70" aria-hidden="true">
        <path class="ink" d="M6,44 C12,20 16,54 24,30 C28,16 32,48 38,32 C42,20 44,38 52,26 C60,12 64,46 74,32 Q80,22 86,34 C94,48 98,18 108,30 C116,40 112,48 122,36 C132,24 128,14 140,26 C148,34 144,44 156,32 C166,22 170,15 182,24 C188,29 188,20 196,22"/>
        <path class="flourish" d="M10,58 C56,68 150,68 202,52"/>
      </svg>
      <div class="sign-meta">
        <span class="who">נחתם אישית — מייסד LandingFlow</span>
        <span class="note">מתחדש בכל פרויקט חדש, לא רק בחוזה</span>
      </div>
    </div>
  </div>
</section>

<section class="audit-section">
  <div class="container audit-inner">
    <span class="eyebrow">ניתוח חינם, תוך דקות</span>
    <h2>בדיקת אתר מקיפה בחינם</h2>
    <p>הזינו כתובת URL וקבלו תוך דקות דוח מלא על SEO, אבטחה, נגישות, ביצועים ודרישות משפטיות.</p>
    <div class="audit-checks">
      <div class="audit-check"><span class="dot good"></span> ציון כללי + פירוט לפי קטגוריה</div>
      <div class="audit-check"><span class="dot good"></span> רשימת בעיות מדורגת לפי השפעה</div>
      <div class="audit-check"><span class="dot med"></span> המלצות מעשיות לתיקון</div>
      <div class="audit-check"><span class="dot bad"></span> שליחה במייל + וואטסאפ</div>
    </div>
    <a href="<?= $url('audit') ?>" class="btn btn-primary btn-lg">בדוק את האתר שלך עכשיו</a>
  </div>
</section>

<section class="cta-section">
  <div class="container">
    <span class="cta-badge"><span class="live-dot"></span>זמין עכשיו</span>
    <h2>רוצה לראות איך האתר שלך ייראה?</h2>
    <p>שלח לי הודעה — אני אחזור אליך אישית עם הצעה מותאמת.</p>
    <a href="<?= $url('contact') ?>" class="btn btn-lg" style="background:#fff;color:var(--ink);font-weight:800">📞 דברו איתי</a>
  </div>
</section>

<script>(function(){
  var sig=document.querySelector('.sign-scribble');
  if(!sig||!('IntersectionObserver' in window)){if(sig)sig.classList.add('inview');return}
  var io=new IntersectionObserver(function(entries){
    entries.forEach(function(e){if(e.isIntersecting){sig.classList.add('inview');io.disconnect()}})
  },{threshold:.6});
  io.observe(sig);
})();</script>
<?php $content = ob_get_clean(); include __DIR__ . '/../partials/layout.php'; ?>
