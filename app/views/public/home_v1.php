<?php ob_start() ?>
<style>
/* ===== HOME-SPECIFIC STYLES ===== */
.hero{position:relative;padding-top:150px;padding-bottom:30px;overflow:hidden;background:var(--bg)}
.hero-inner{text-align:center;display:flex;flex-direction:column;align-items:center;}
.eyebrow-pill{display:inline-flex;align-items:center;gap:8px;font-size:.82rem;font-weight:600;background:var(--surface);border:1px solid var(--border);padding:8px 16px;border-radius:100px;box-shadow:var(--shadow-sm);margin-bottom:24px;color:var(--ink-soft);}
.hero h1{font-size:clamp(2rem,6vw,3.5rem);font-weight:800;letter-spacing:-.02em;line-height:1.18;max-width:760px;margin-bottom:20px;}
.hero h1 span{color:var(--primary);}
.hero-sub{font-size:clamp(1rem,2vw,1.15rem);color:var(--ink-soft);max-width:580px;margin-bottom:30px;}
.hero-ctas{display:flex;flex-wrap:wrap;gap:14px;justify-content:center;margin-bottom:18px;}
.hero-trust-note{font-size:.82rem;color:var(--ink-faint);margin-bottom:50px;}

.dash-wrap{max-width:1080px;margin:0 auto;}
.dashboard-window{background:var(--surface);border-radius:var(--radius-lg);border:1px solid var(--border);box-shadow:var(--shadow-lg);overflow:hidden;}
.dash-chrome{display:flex;align-items:center;gap:10px;padding:14px 18px;background:var(--surface-2);border-bottom:1px solid var(--border);}
.dash-chrome .dot{width:10px;height:10px;border-radius:50%;}
.dash-url{margin-right:auto;background:#fff;border:1px solid var(--border);border-radius:100px;padding:5px 16px;font-family:var(--font-mono);font-size:.72rem;color:var(--ink-soft);direction:ltr;}
.dash-body{display:grid;grid-template-columns:1fr;}
@media(min-width:760px){.dash-body{grid-template-columns:72px 1fr;}}
.dash-sidebar{display:none;flex-direction:column;align-items:center;gap:18px;padding:22px 0;background:var(--surface-2);border-left:1px solid var(--border);}
@media(min-width:760px){.dash-sidebar{display:flex;}}
.side-ico{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:var(--ink-faint);}
.side-ico.active{background:var(--primary);color:#fff;}
.dash-main{padding:18px;}
@media(min-width:600px){.dash-main{padding:26px;}}
.dash-greet{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:8px;}
.dash-greet h3{font-size:1.05rem;font-weight:700;}
.dash-greet span{font-size:.78rem;color:var(--ink-faint);font-family:var(--font-mono);}
.kpi-row{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:16px;}
@media(min-width:560px){.kpi-row{grid-template-columns:repeat(4,1fr);}}
.kpi-card{background:var(--surface-2);border:1px solid var(--border);border-radius:12px;padding:14px 16px;}
.kpi-label{font-size:.72rem;color:var(--ink-soft);margin-bottom:8px;}
.kpi-value{font-family:var(--font-mono);font-weight:700;font-size:1.25rem;}
.kpi-trend{font-size:.72rem;font-weight:700;margin-top:4px;}
.kpi-trend.up{color:var(--success)}.kpi-trend.down{color:var(--danger)}
.dash-grid-main{display:grid;grid-template-columns:1fr;gap:14px;}
@media(min-width:900px){.dash-grid-main{grid-template-columns:1.6fr 1fr;}}
.chart-card,.sites-card{background:var(--surface-2);border:1px solid var(--border);border-radius:12px;padding:18px;}
.chart-card h4,.sites-card h4{font-size:.85rem;font-weight:700;margin-bottom:16px;}
.bars{display:flex;align-items:flex-end;gap:5px;height:120px;}
.bars .bar{flex:1;border-radius:5px 5px 0 0;background:linear-gradient(180deg,var(--primary-2),var(--primary));height:0%;transition:height 1.1s cubic-bezier(.2,.8,.2,1);}
.site-row{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--border);font-size:.78rem;}
.site-row:last-child{border-bottom:none;}
.status-dot{width:8px;height:8px;border-radius:50%;background:var(--success);position:relative;flex:0 0 8px;}
.status-dot::after{content:'';position:absolute;inset:-4px;border-radius:50%;border:1px solid var(--success);animation:pulse 1.8s ease-out infinite;}
.status-dot.warn{background:var(--warning);}.status-dot.warn::after{border-color:var(--warning);}
@keyframes pulse{0%{transform:scale(.6);opacity:.8;}100%{transform:scale(1.9);opacity:0;}}
.site-name{flex:1;font-weight:600;}
.site-ms{font-family:var(--font-mono);color:var(--ink-faint);}

.stats-strip{background:var(--ink);color:#fff;}
.stats-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;text-align:center;}
@media(min-width:760px){.stats-grid{grid-template-columns:repeat(4,1fr);}}
.stat-num{font-family:var(--font-mono);font-size:clamp(1.7rem,3.4vw,2.4rem);font-weight:700;color:var(--success);}
.stat-label{font-size:.84rem;color:var(--ink-faint);margin-top:8px;}

.features-grid{display:grid;grid-template-columns:1fr;gap:16px;}
@media(min-width:680px){.features-grid{grid-template-columns:repeat(2,1fr);}}
@media(min-width:1040px){.features-grid{grid-template-columns:repeat(3,1fr);}}
.feature-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:26px 24px;transition:transform .25s ease,box-shadow .25s ease;}
.feature-card:hover{transform:translateY(-5px);box-shadow:var(--shadow-md);}
.feature-icon{width:46px;height:46px;border-radius:12px;background:rgba(37,99,235,.09);display:flex;align-items:center;justify-content:center;font-size:1.25rem;margin-bottom:16px;}
.feature-card h3{font-size:1.05rem;font-weight:700;margin-bottom:8px;}
.feature-card p{color:var(--ink-soft);font-size:.92rem;}

.kanban{display:flex;gap:14px;overflow-x:auto;padding-bottom:10px;scroll-snap-type:x proximity;}
.kanban-col{flex:0 0 240px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px;scroll-snap-align:start;}
.kanban-col-head{display:flex;align-items:center;justify-content:space-between;font-size:.84rem;font-weight:700;margin-bottom:14px;padding:0 4px;}
.kanban-count{background:var(--bg);border-radius:100px;padding:2px 9px;font-family:var(--font-mono);font-size:.72rem;color:var(--ink-soft);}
.lead-card{background:var(--surface-2);border:1px solid var(--border);border-radius:10px;padding:12px;margin-bottom:10px;}
.lead-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;}
.lead-avatar{width:26px;height:26px;border-radius:50%;background:var(--primary);color:#fff;font-size:.68rem;font-weight:700;display:flex;align-items:center;justify-content:center;}
.lead-value{font-family:var(--font-mono);font-size:.76rem;font-weight:700;color:var(--primary-dark);}
.lead-name{font-size:.84rem;font-weight:600;margin-bottom:6px;}
.lead-tag{display:inline-block;font-size:.68rem;background:rgba(37,99,235,.08);color:var(--primary);padding:2px 9px;border-radius:100px;}

.monitor-table{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;}
.monitor-row{display:grid;grid-template-columns:1.4fr 1fr;gap:8px;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border);font-size:.84rem;}
@media(min-width:640px){.monitor-row{grid-template-columns:1.6fr .8fr .8fr .8fr 1fr;}}
.monitor-row:last-child{border-bottom:none;}
.monitor-row.head{background:var(--surface-2);font-weight:700;color:var(--ink-soft);font-size:.76rem;display:none;}
@media(min-width:640px){.monitor-row.head{display:grid;}}
.monitor-site{display:flex;align-items:center;gap:10px;font-weight:600;}
.monitor-val{font-family:var(--font-mono);color:var(--ink-soft);}

.audit-preview-grid{display:grid;grid-template-columns:1fr;gap:30px;align-items:center;}
@media(min-width:900px){.audit-preview-grid{grid-template-columns:auto 1fr;}}
.score-circle{--p:0;width:180px;height:180px;border-radius:50%;background:conic-gradient(var(--success) calc(var(--p)*1%), var(--border) 0);display:flex;align-items:center;justify-content:center;margin:0 auto;}
.score-circle-inner{width:140px;height:140px;border-radius:50%;background:var(--surface);display:flex;flex-direction:column;align-items:center;justify-content:center;box-shadow:var(--shadow-sm);}
.score-num{font-family:var(--font-mono);font-size:2.2rem;font-weight:700;}
.score-label{font-size:.74rem;color:var(--ink-faint);margin-top:2px;}
.issue-list{display:flex;flex-direction:column;gap:12px;}
.issue-row{display:flex;align-items:center;gap:12px;background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:14px 16px;font-size:.9rem;}
.issue-tag{flex:0 0 auto;font-size:.68rem;font-weight:700;padding:4px 10px;border-radius:100px;margin-right:auto;}
.issue-tag.high{background:rgba(220,38,38,.12);color:var(--danger);}
.issue-tag.med{background:rgba(245,158,11,.15);color:#92400E;}
.issue-tag.ok{background:rgba(22,163,74,.18);color:var(--success);}

.cta-banner{background:var(--primary-dark);color:#fff;text-align:center;position:relative;overflow:hidden;}
.cta-banner-inner{position:relative;display:flex;flex-direction:column;align-items:center;}
.cta-banner h2{font-size:clamp(1.6rem,4vw,2.3rem);font-weight:800;max-width:600px;margin-bottom:14px;}
.cta-banner p{color:rgba(255,255,255,.75);max-width:480px;margin-bottom:30px;}
.cta-banner .btn-primary{background:#fff;color:var(--primary-dark);box-shadow:none;}
.cta-banner .btn-primary:hover{background:var(--success);color:#fff;}
</style>

<main id="top">

  <section class="hero">
    <div class="container hero-inner">
      <span class="eyebrow-pill">📊 הדשבורד שמרכז את כל מה שקורה באתר שלכם</span>
      <h1>כל הנתונים על האתר שלכם,<br><span>במקום אחד.</span></h1>
      <p class="hero-sub">LandingFlow מרכז ניטור, ביקורות, CRM ותחזוקה בדשבורד אחד — כדי שתדעו בדיוק מה קורה באתר שלכם, בכל רגע.</p>
      <div class="hero-ctas">
        <a href="<?= $url('audit') ?>" class="btn btn-primary btn-lg">בדוק את האתר שלך עכשיו</a>
        <a href="<?= $url('demo') ?>" class="btn btn-ghost btn-lg">צור דמו של אתר</a>
      </div>
      <span class="hero-trust-note">ללא כרטיס אשראי · הרשמה תוך 2 דקות · ביטול בכל עת</span>

      <div class="dash-wrap">
        <div class="dashboard-window">
          <div class="dash-chrome">
            <span class="dot" style="background:#FF5F57"></span>
            <span class="dot" style="background:#FEBC2E"></span>
            <span class="dot" style="background:#28C840"></span>
            <span class="dash-url">app.landingflow.co.il/dashboard</span>
          </div>
          <div class="dash-body">
            <div class="dash-sidebar">
              <div class="side-ico active">🏠</div>
              <div class="side-ico">📊</div>
              <div class="side-ico">📇</div>
              <div class="side-ico">📡</div>
              <div class="side-ico">🔍</div>
              <div class="side-ico">⚙️</div>
            </div>
            <div class="dash-main">
              <div class="dash-greet">
                <h3>שלום, דנה 👋</h3>
                <span id="liveClock">22.06.2026</span>
              </div>
              <div class="kpi-row">
                <div class="kpi-card"><div class="kpi-label">ביקורים החודש</div><div class="kpi-value" data-count="12480">0</div><div class="kpi-trend up">↑ 18%</div></div>
                <div class="kpi-card"><div class="kpi-label">לידים חדשים</div><div class="kpi-value" data-count="86">0</div><div class="kpi-trend up">↑ 7%</div></div>
                <div class="kpi-card"><div class="kpi-label">זמן פעולה</div><div class="kpi-value" data-count="99.8" data-decimal="1">0</div><div class="kpi-trend up">↑ 0.2%</div></div>
                <div class="kpi-card"><div class="kpi-label">ציון אתר</div><div class="kpi-value" data-count="91">0</div><div class="kpi-trend up">↑ 4</div></div>
              </div>
              <div class="dash-grid-main">
                <div class="chart-card">
                  <h4>תנועה לאתר — 30 הימים האחרונים</h4>
                  <div class="bars" id="heroBars"></div>
                </div>
                <div class="sites-card">
                  <h4>אתרים מנוטרים</h4>
                  <div class="site-row"><span class="status-dot"></span><span class="site-name">novaretail.co.il</span><span class="site-ms">210ms</span></div>
                  <div class="site-row"><span class="status-dot"></span><span class="site-name">almog-dental.co.il</span><span class="site-ms">340ms</span></div>
                  <div class="site-row"><span class="status-dot warn"></span><span class="site-name">rootscafe.co.il</span><span class="site-ms">890ms</span></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="stats-strip">
    <div class="container stats-grid">
      <div><div class="stat-num" data-count="1240">0</div><div class="stat-label">אתרים מנוטרים 24/7</div></div>
      <div><div class="stat-num" data-count="98.6" data-decimal="1">0</div><div class="stat-label">זמן פעולה ממוצע (%)</div></div>
      <div><div class="stat-num" data-count="1.1" data-decimal="1">0</div><div class="stat-label">זמן טעינה חציוני (שנ')</div></div>
      <div><div class="stat-num" data-count="4800">0</div><div class="stat-label">לידים שנוצרו דרך CRM</div></div>
    </div>
  </section>

  <section id="features">
    <div class="container">
      <div class="head-center">
        <span class="section-eyebrow">תכונות המערכת</span>
        <h2 class="section-title" style="margin:0 auto 12px;">כל מה שצריך כדי לנהל אתר בלי הפתעות</h2>
        <p class="section-sub" style="margin:0 auto 42px;">שש תכונות שעובדות יחד ברקע, כדי שתוכלו להתמקד בעסק.</p>
      </div>
      <div class="features-grid">
        <div class="feature-card"><div class="feature-icon">🔔</div><h3>התראות בזמן אמת</h3><p>הודעה מיידית בנייד או באימייל בכל תקלה, נפילה או חריגה בביצועים.</p></div>
        <div class="feature-card"><div class="feature-icon">📊</div><h3>דוחות אוטומטיים</h3><p>סיכום חודשי ברור עם הנתונים החשובים — בלי שתצטרכו לחפש אותם.</p></div>
        <div class="feature-card"><div class="feature-icon">🧭</div><h3>ניהול לידים חכם</h3><p>כל פנייה מהאתר נכנסת אוטומטית לצנרת המכירה, עם תזכורות למעקב.</p></div>
        <div class="feature-card"><div class="feature-icon">🗄️</div><h3>גיבויים יומיים</h3><p>גיבוי אוטומטי של האתר כל יום, עם שחזור בלחיצה אחת במקרה הצורך.</p></div>
        <div class="feature-card"><div class="feature-icon">🔎</div><h3>בדיקות SEO אוטומטיות</h3><p>סקירה שבועית שמאתרת בעיות SEO לפני שהן משפיעות על התנועה.</p></div>
        <div class="feature-card"><div class="feature-icon">💬</div><h3>תמיכה אנושית 24/7</h3><p>צוות תמיכה אמיתי שעונה בעברית, בכל שעה ובכל יום בשבוע.</p></div>
      </div>
    </div>
  </section>

  <section id="crm">
    <div class="container">
      <div class="head-center">
        <span class="section-eyebrow">CRM וניהול לידים</span>
        <h2 class="section-title" style="margin:0 auto 12px;">כל ליד, מהרגע שהגיע ועד שנסגר</h2>
        <p class="section-sub" style="margin:0 auto 42px;">צנרת מכירה אחת וברורה, עם כל הפניות מהאתר במקום אחד.</p>
      </div>
      <div class="kanban">
        <div class="kanban-col">
          <div class="kanban-col-head">לידים חדשים <span class="kanban-count">2</span></div>
          <div class="lead-card"><div class="lead-top"><span class="lead-avatar">דכ</span><span class="lead-value">₪14,200</span></div><div class="lead-name">דנה כהן — סטודיו אורות</div><span class="lead-tag">פיתוח אתר</span></div>
          <div class="lead-card"><div class="lead-top"><span class="lead-avatar">ימ</span><span class="lead-value">₪8,500</span></div><div class="lead-name">יוסי מזרחי — מזרחי פיננסים</div><span class="lead-tag">תחזוקה</span></div>
        </div>
        <div class="kanban-col">
          <div class="kanban-col-head">בקשר <span class="kanban-count">2</span></div>
          <div class="lead-card"><div class="lead-top"><span class="lead-avatar">רל</span><span class="lead-value">₪22,000</span></div><div class="lead-name">רותם לוי — קליניק רותם</div><span class="lead-tag">פיתוח אתר</span></div>
          <div class="lead-card"><div class="lead-top"><span class="lead-avatar">אש</span><span class="lead-value">₪31,000</span></div><div class="lead-name">אורי שביט — שביט הנדסה</div><span class="lead-tag">אתר + CRM</span></div>
        </div>
        <div class="kanban-col">
          <div class="kanban-col-head">הצעת מחיר <span class="kanban-count">2</span></div>
          <div class="lead-card"><div class="lead-top"><span class="lead-avatar">נג</span><span class="lead-value">₪17,500</span></div><div class="lead-name">נועה גבע — Geva Design</div><span class="lead-tag">פיתוח אתר</span></div>
          <div class="lead-card"><div class="lead-top"><span class="lead-avatar">תב</span><span class="lead-value">₪9,900</span></div><div class="lead-name">תומר בר — TomTech</div><span class="lead-tag">ביקורת אתר</span></div>
        </div>
        <div class="kanban-col">
          <div class="kanban-col-head">נסגר 🎉 <span class="kanban-count">2</span></div>
          <div class="lead-card"><div class="lead-top"><span class="lead-avatar">מא</span><span class="lead-value">₪12,300</span></div><div class="lead-name">מיכל אזולאי — אזולאי יופי</div><span class="lead-tag">פיתוח אתר</span></div>
          <div class="lead-card"><div class="lead-top"><span class="lead-avatar">רל</span><span class="lead-value">₪26,000</span></div><div class="lead-name">רועי לוין — Lior Wear</div><span class="lead-tag">אתר + תחזוקה</span></div>
        </div>
      </div>
    </div>
  </section>

  <section id="monitoring">
    <div class="container">
      <div class="head-center">
        <span class="section-eyebrow">מוניטורינג אתרים</span>
        <h2 class="section-title" style="margin:0 auto 12px;">ניטור 24/7 שלא מפספס שום תקלה</h2>
        <p class="section-sub" style="margin:0 auto 42px;">בדיקה כל 60 שניות מכמה מוקדים בעולם, עם התראה לפני שהלקוחות מרגישים.</p>
      </div>
      <div class="monitor-table">
        <div class="monitor-row head">
          <span>אתר</span><span>סטטוס</span><span>זמן פעולה</span><span>זמן תגובה</span><span>בדיקה אחרונה</span>
        </div>
        <div class="monitor-row"><span class="monitor-site"><span class="status-dot"></span>novaretail.co.il</span><span class="monitor-val">פעיל</span><span class="monitor-val">99.98%</span><span class="monitor-val">210ms</span><span class="monitor-val">לפני 12 שנ'</span></div>
        <div class="monitor-row"><span class="monitor-site"><span class="status-dot"></span>almog-dental.co.il</span><span class="monitor-val">פעיל</span><span class="monitor-val">99.95%</span><span class="monitor-val">340ms</span><span class="monitor-val">לפני 8 שנ'</span></div>
        <div class="monitor-row"><span class="monitor-site"><span class="status-dot"></span>barzilay-law.co.il</span><span class="monitor-val">פעיל</span><span class="monitor-val">100%</span><span class="monitor-val">180ms</span><span class="monitor-val">לפני 20 שנ'</span></div>
        <div class="monitor-row"><span class="monitor-site"><span class="status-dot warn"></span>rootscafe.co.il</span><span class="monitor-val">אזהרה</span><span class="monitor-val">98.20%</span><span class="monitor-val">890ms</span><span class="monitor-val">לפני 5 שנ'</span></div>
        <div class="monitor-row"><span class="monitor-site"><span class="status-dot"></span>liorwear.com</span><span class="monitor-val">פעיל</span><span class="monitor-val">99.99%</span><span class="monitor-val">150ms</span><span class="monitor-val">לפני 3 שנ'</span></div>
      </div>
    </div>
  </section>

  <section id="audit">
    <div class="container">
      <div class="head-center">
        <span class="section-eyebrow">ביקורת אתרים</span>
        <h2 class="section-title" style="margin:0 auto 12px;">ביקורת אתר שמראה בדיוק איפה לשפר</h2>
        <p class="section-sub" style="margin:0 auto 42px;">דוח אוטומטי שבודק מהירות, אבטחה, נגישות ו-SEO — עם רשימת פעולות מדורגת לפי השפעה.</p>
      </div>
      <div class="audit-preview-grid">
        <div class="score-circle" id="auditScoreCircle">
          <div class="score-circle-inner">
            <span class="score-num" id="auditScoreNum">0</span>
            <span class="score-label">ציון כללי</span>
          </div>
        </div>
        <div class="issue-list">
          <div class="issue-row">⚠️ Meta description חסר בעמוד הבית <span class="issue-tag high">עדיפות גבוהה</span></div>
          <div class="issue-row">🖼️ 3 תמונות לא מאופטימיזציות <span class="issue-tag med">עדיפות בינונית</span></div>
          <div class="issue-row">🔒 תעודת SSL בתוקף, ללא HSTS <span class="issue-tag med">עדיפות בינונית</span></div>
          <div class="issue-row">✅ Core Web Vitals תקינים <span class="issue-tag ok">תקין</span></div>
        </div>
      </div>
    </div>
  </section>

  <section class="cta-banner" id="cta">
    <div class="container cta-banner-inner">
      <h2>מוכנים לראות את האתר שלכם בצורה אחרת?</h2>
      <p>חברו את האתר שלכם לדשבורד והתחילו לקבל נתונים אמיתיים תוך דקות.</p>
      <a href="<?= $url('audit') ?>" class="btn btn-primary btn-lg">בדוק את האתר שלך עכשיו</a>
    </div>
  </section>

</main>

<script>
// Count-up helper
function animateCount(el){
  const target = parseFloat(el.dataset.count);
  const decimals = parseInt(el.dataset.decimal || '0');
  const steps = 46;
  let count = 0, current = 0;
  const increment = target / steps;
  const t = setInterval(() => {
    current += increment; count++;
    el.textContent = current.toFixed(decimals).toLocaleString('en-US');
    if(count >= steps){
      el.textContent = decimals ? target.toFixed(decimals) : target.toLocaleString('en-US');
      clearInterval(t);
    }
  }, 24);
}

// Hero dashboard bars + KPI counters
window.addEventListener('DOMContentLoaded', () => {
  const heights = [38,55,42,68,50,72,60,80,66,90,74,95];
  const barsWrap = document.getElementById('heroBars');
  heights.forEach(h => {
    const bar = document.createElement('div');
    bar.className = 'bar';
    barsWrap.appendChild(bar);
    requestAnimationFrame(() => setTimeout(() => bar.style.height = h + '%', 250));
  });
  document.querySelectorAll('.kpi-value').forEach(el => setTimeout(() => animateCount(el), 300));
});

// Scroll-triggered counters (stats strip)
const statObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if(entry.isIntersecting){
      animateCount(entry.target);
      statObserver.unobserve(entry.target);
    }
  });
}, { threshold:0.4 });
document.querySelectorAll('.stat-num').forEach(el => statObserver.observe(el));

// Audit score circle animate on scroll
const scoreCircle = document.getElementById('auditScoreCircle');
const scoreNum = document.getElementById('auditScoreNum');
const auditObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if(entry.isIntersecting){
      const target = 78;
      let current = 0;
      const t = setInterval(() => {
        current = Math.min(target, current + 2);
        scoreCircle.style.setProperty('--p', current);
        scoreNum.textContent = current;
        if(current >= target) clearInterval(t);
      }, 18);
      auditObserver.unobserve(entry.target);
    }
  });
}, { threshold:0.4 });
auditObserver.observe(scoreCircle);

// Static date display
document.getElementById('liveClock').textContent = 'יום שני, 22.06.2026';
</script>

<?php $content = ob_get_clean(); include __DIR__ . '/../partials/layout.php'; ?>
