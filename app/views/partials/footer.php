<?php // shared footer — use inside layout.php ?>
<footer class="footer"><div class="container footer-grid">
  <div class="footer-brand">
    <a href="<?= $url('') ?>" class="logo"><span class="logo-mark">LF</span>LandingFlow</a>
    <p>דפי נחיתה, אתרים, אוטומציות AI ו-CRM — בליווי אישי. וואטסאפ ישיר, בלי מוקדנים.</p>
    <div class="social-row"><a href="https://www.linkedin.com/company/landingflow" target="_blank" rel="noopener" aria-label="לינקדאין">in</a><a href="https://www.instagram.com/landingflow" target="_blank" rel="noopener" aria-label="אינסטגרם">📷</a><a href="https://www.facebook.com/landingflow" target="_blank" rel="noopener" aria-label="פייסבוק">f</a></div>
  </div>
  <div class="footer-col"><h4>שירותים</h4><ul><li><a href="<?= $url('demo') ?>">דמו חי</a></li><li><a href="<?= $url('portfolio') ?>">תיק עבודות</a></li><li><a href="<?= $url('audit') ?>">בדיקת אתר</a></li><li><a href="<?= $url('pricing') ?>">מחירים</a></li><li><a href="<?= $url('services') ?>">כל השירותים</a></li></ul></div>
  <div class="footer-col"><h4>חברה</h4><ul><li><a href="<?= $url('about') ?>">אודות</a></li><li><a href="<?= $url('pricing') ?>">תמחור</a></li><li><a href="<?= $url('blog') ?>">בלוג</a></li><li><a href="<?= $url('contact') ?>">צרו קשר</a></li></ul></div>
  <div class="footer-col"><h4>צרו קשר</h4><ul><li><a href="tel:0528529448">052-8529448</a></li><li><a href="mailto:info@landingflow.co.il">info@landingflow.co.il</a></li></ul></div>
</div><div class="container footer-bottom"><span>© <?= date("Y") ?> LandingFlow. כל הזכויות שמורות.</span><div class="footer-legal"><a href="<?= $url("terms-of-service") ?>">תנאי שימוש</a><a href="<?= $url("privacy-policy") ?>">פרטיות</a></div></div><div class="container" style="max-width:900px;margin:0 auto;padding:12px 20px 24px"><p style="font-size:.7rem;color:var(--ink-faint);text-align:center;line-height:1.6"><strong>⚠️ כתב ויתור (Disclaimer):</strong> LandingFlow אינה אחראית לתוצאות השימוש במידע, בכלים ובשירותים. כל המידע מוגש כשירות לציבור ואינו מהווה ייעוץ מקצועי או משפטי. תוצאות בדיקות האתר, ציוני הביקורת, תצוגות הדמו וההמלצות הן בגדר הערכה בלבד. המשתמש עושה זאת על אחריותו הבלעדית. השימוש באתר מהווה הסכמה <a href="<?= $url("terms-of-service") ?>" style="color:var(--primary)">לתנאי השימוש</a> ו<a href="<?= $url("privacy-policy") ?>" style="color:var(--primary)">למדיניות הפרטיות</a>. <a href="mailto:hello@landingflow.co.il" style="color:var(--primary)">info@landingflow.co.il</a></p></div></footer>

<!-- WhatsApp Floating -->
<a href="https://wa.me/972528529448" target="_blank" rel="noopener" class="whatsapp-float" id="waFloat" aria-label="WhatsApp" title="דברו איתנו בוואטסאפ!">💬<span class="whatsapp-tooltip" id="waTooltip">דברו איתנו בוואטסאפ :-)</span></a>

<!-- Accessibility Floating -->
<button class="a11y-float" id="a11yToggle" aria-label="נגישות" title="תפריט נגישות"><svg class="icon-line" viewBox="0 0 24 24" style="width:24px;height:24px"><circle cx="12" cy="6" r="2" fill="currentColor" stroke="none"></circle><path d="M6.5 10c3.5-1.2 7.5-1.2 11 0M12 11v4.5M9 20l3-4.5 3 4.5"></path></svg></button>
<div class="a11y-panel" id="a11yPanel">
  <h4>הגדרות נגישות</h4>
  <button class="a11y-btn" onclick="document.body.classList.toggle('high-contrast')">ניגודיות גבוהה</button>
  <button class="a11y-btn" onclick="document.body.classList.toggle('large-text')">הגדלת טקסט</button>
  <button class="a11y-btn" onclick="document.body.classList.toggle('no-anim')">ביטול אנימציות</button>
  <button class="a11y-btn" onclick="document.body.removeAttribute('class')">איפוס כל ההגדרות</button>
</div>

<script>
(function(){
  var nav=document.getElementById("nav");
  window.addEventListener("scroll",function(){nav.classList.toggle("scrolled",window.scrollY>8)});
  var burger=document.getElementById("burger"),mobileMenu=document.getElementById("mobileMenu"),menuBackdrop=document.getElementById("menuBackdrop"),menuClose=document.getElementById("menuClose");
  function openMenu(){mobileMenu.classList.add("open");burger.classList.add("active");burger.setAttribute("aria-expanded","true");document.body.style.overflow="hidden"}
  function closeMenu(){mobileMenu.classList.remove("open");burger.classList.remove("active");burger.setAttribute("aria-expanded","false");document.body.style.overflow=""}
  burger.addEventListener("click",function(){mobileMenu.classList.contains("open")?closeMenu():openMenu()});menuBackdrop.addEventListener("click",closeMenu);menuClose.addEventListener("click",closeMenu);
  document.querySelectorAll(".mobile-links a,.menu-foot a").forEach(function(a){a.addEventListener("click",closeMenu)});
  var waFloat=document.getElementById("waFloat"),waTooltip=document.getElementById("waTooltip");
  waFloat.addEventListener("mouseenter",function(){waTooltip.classList.add("visible")});waFloat.addEventListener("mouseleave",function(){waTooltip.classList.remove("visible")});
  var a11yToggle=document.getElementById("a11yToggle"),a11yPanel=document.getElementById("a11yPanel");
  a11yToggle.addEventListener("click",function(e){e.stopPropagation();a11yPanel.classList.toggle("open")});
  document.addEventListener("click",function(e){if(!a11yPanel.contains(e.target)&&e.target!==a11yToggle)a11yPanel.classList.remove("open")});
})();
</script>

<!-- Smart Sales Agent -->
<div class="agent-float" id="agentFloat">
  <button class="agent-btn" id="agentBtn" aria-label="פתח סוכן מכירות">
    <span class="agent-icon">🤖</span>
    <span class="agent-badge">1</span>
  </button>
</div>
<div class="agent-window" id="agentWindow">
  <div class="agent-head">
    <div class="agent-head-info"><div class="agent-avatar">🤖</div><div><strong>הסוכן של LandingFlow</strong><br><span class="agent-online">אונליין · עונה תוך שניות</span></div></div>
    <div style="display:flex;gap:8px">
      <button class="agent-restart" id="agentRestart" title="התחל מחדש">🔄</button>
      <button class="agent-close" id="agentClose">✕</button>
    </div>
  </div>
  <div class="agent-body" id="agentBody">
    <div class="agent-msg bot">היי! 👋 אני הסוכן החכם של LandingFlow. אשמח לענות על כל שאלה על השירותים שלנו —<br>🔍 בדיקת אתרים, 📡 ניטור 24/7, 💻 פיתוח אתרים, ☁️ אחסון, 📊 CRM ועוד.<br><br>איך אפשר לעזור?</div>
  </div>
  <div class="agent-input-row">
    <input type="text" id="agentInput" placeholder="הקלידו הודעה...">
    <button id="agentSend">שלח</button>
  </div>
</div>

<style>
.agent-float{position:fixed;bottom:24px;right:24px;z-index:950}
.agent-btn{width:60px;height:60px;border-radius:50%;background:var(--primary);color:#fff;border:none;cursor:pointer;font-size:1.5rem;box-shadow:0 8px 30px rgba(37,99,235,.35);transition:.3s;position:relative;display:flex;align-items:center;justify-content:center}
.agent-btn:hover{transform:scale(1.08);box-shadow:0 12px 40px rgba(37,99,235,.5)}
.agent-badge{position:absolute;top:-4px;right:-4px;background:var(--danger);color:#fff;width:22px;height:22px;border-radius:50%;font-size:.65rem;font-weight:700;display:flex;align-items:center;justify-content:center;border:2px solid #fff}
.agent-window{position:fixed;bottom:100px;right:24px;width:380px;max-width:calc(100vw - 40px);height:520px;max-height:calc(100vh - 140px);background:var(--surface);border:1px solid var(--border);border-radius:20px;box-shadow:var(--shadow-lg);display:none;flex-direction:column;z-index:950;overflow:hidden;animation:slideUp .3s ease}
.agent-window.open{display:flex}
@media(max-width:480px){.agent-window{right:12px;left:12px;bottom:92px;width:auto;max-width:none;height:min(65vh,460px);max-height:calc(100vh - 112px)}}
@keyframes slideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.agent-head{display:flex;align-items:center;justify-content:space-between;padding:16px 18px;background:var(--primary);color:#fff}.agent-head-info{display:flex;align-items:center;gap:10px}.agent-avatar{width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.1rem}.agent-head strong{font-size:.9rem}.agent-online{font-size:.7rem;opacity:.85}.agent-close{width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.2);border:none;color:#fff;font-size:1rem;cursor:pointer}.agent-close:hover{background:rgba(255,255,255,.4)}.agent-restart{width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.2);border:none;color:#fff;font-size:.85rem;cursor:pointer}.agent-restart:hover{background:rgba(255,255,255,.4)}
.agent-body{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px}.agent-msg{max-width:88%;padding:10px 14px;border-radius:14px;font-size:.84rem;line-height:1.5;animation:fadeMsg .3s ease;word-break:break-word}.agent-msg.bot{background:var(--surface-2);align-self:flex-start;border-bottom-right-radius:4px}.agent-msg.user{background:var(--primary);color:#fff;align-self:flex-end;border-bottom-left-radius:4px}.agent-msg.lead-form{background:var(--primary);color:#fff;align-self:flex-start;border-bottom-right-radius:4px;padding:16px}.agent-msg.lead-form input,.agent-msg.lead-form textarea{width:100%;padding:8px 12px;border:1px solid rgba(255,255,255,.2);border-radius:8px;font-family:var(--font-display);font-size:.82rem;margin-bottom:8px;background:rgba(255,255,255,.1);color:#fff;outline:none}.agent-msg.lead-form input::placeholder{color:rgba(255,255,255,.5)}.agent-msg.lead-form .btn-submit{width:100%;padding:10px;background:#fff;color:var(--primary);border:none;border-radius:8px;font-weight:700;cursor:pointer;font-family:inherit;font-size:.85rem;margin-top:4px}
@keyframes fadeMsg{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
.agent-input-row{display:flex;gap:8px;padding:12px 16px;border-top:1px solid var(--border);background:var(--surface-2)}.agent-input-row input{flex:1;padding:10px 14px;border:1.5px solid var(--border);border-radius:12px;font-family:var(--font-display);font-size:.85rem;outline:none}.agent-input-row input:focus{border-color:var(--primary)}.agent-input-row button{background:var(--primary);color:#fff;border:none;padding:10px 18px;border-radius:12px;font-family:inherit;font-weight:700;cursor:pointer;white-space:nowrap}.agent-input-row button:hover{background:var(--primary-dark)}
.typing-dots{display:flex;gap:4px;padding:8px 0}.typing-dots span{width:6px;height:6px;border-radius:50%;background:var(--ink-faint);animation:typing 1.4s infinite}.typing-dots span:nth-child(2){animation-delay:.2s}.typing-dots span:nth-child(3){animation-delay:.4s}@keyframes typing{0%,80%,100%{opacity:.3}40%{opacity:1}}
</style>

<script>
(function(){
var body=document.getElementById("agentBody"),inp=document.getElementById("agentInput"),send=document.getElementById("agentSend"),win=document.getElementById("agentWindow"),btn=document.getElementById("agentBtn"),badge=document.querySelector(".agent-badge"),close=document.getElementById("agentClose");
var isOpen=false,step="greet",leadData={},unread=1;

function setFloatVisible(v){var el=document.getElementById("agentFloat");if(el)el.style.display=v?"":"none"}
function toggle(){isOpen=!isOpen;win.classList.toggle("open",isOpen);setFloatVisible(!isOpen);if(isOpen){unread=0;badge.style.display="none";inp.focus()}}
btn.addEventListener("click",toggle);close.addEventListener("click",function(){isOpen=false;win.classList.remove("open");setFloatVisible(true)});
var restart=document.getElementById("agentRestart");
restart.addEventListener("click",function(){body.innerHTML='<div class="agent-msg bot">היי! 👋 אני הסוכן החכם של LandingFlow. אשמח לענות על כל שאלה על השירותים שלנו —<br>🔍 בדיקת אתרים, 📡 ניטור 24/7, 💻 פיתוח אתרים, ☁️ אחסון, 📊 CRM ועוד.<br><br>איך אפשר לעזור?</div>';step="greet";leadData={}});

function addMsg(text,cls){var d=document.createElement("div");d.className="agent-msg "+cls;d.innerHTML=text;body.appendChild(d);body.scrollTop=body.scrollHeight;return d}
function showTyping(cb){var d=document.createElement("div");d.className="agent-msg bot";d.innerHTML='<div class="typing-dots"><span></span><span></span><span></span></div>';body.appendChild(d);body.scrollTop=body.scrollHeight;setTimeout(function(){d.remove();cb()},600+Math.random()*600)}

var faq={
  "שירותים":{r:"אני מציע: 🔍 <b>בדיקת אתרים חינם</b> — בודק SEO, אבטחה, נגישות וביצועים. 📡 <b>ניטור 24/7</b> — התראות בזמן אמת. 💻 <b>פיתוח אתרים</b> — דפי נחיתה ואתרים עסקיים. ☁️ <b>אחסון</b> — מהיר ומאובטח. 📊 <b>CRM</b> — ניהול לידים חכם. איזה מהם מעניין אותך?"},
  "בדיקת":{r:"🔍 בדיקת האתר שלנו כוללת: SEO (כותרת, תיאור, H1, Robots, Sitemap), אבטחה (SSL, HTTPS, Headers), נגישות (Alt, ARIA, Viewport), ביצועים (זמן טעינה), ודרישות משפטיות. <b>רוצה שאבדוק את האתר שלך?</b> הכנס כתובת 😊"},
  "ביקורת":{r:"🔍 בדיקת האתר שלנו כוללת: SEO (כותרת, תיאור, H1, Robots, Sitemap), אבטחה (SSL, HTTPS, Headers), נגישות (Alt, ARIA, Viewport), ביצועים (זמן טעינה), ודרישות משפטיות. <b>רוצה שאבדוק את האתר שלך?</b> הכנס כתובת 😊"},
  "ניטור":{r:"📡 מערכת הניטור שלי בודקת את האתר <b>כל 60 שניות</b> מכמה מוקדים בעולם. אני מודד: uptime, זמני תגובה, תוקף SSL, ואבטחה. מקבלים <b>התראות בוואטסאפ</b> כשמשהו משתבש. מחיר: החל מ-₪99 לחודש. רוצה לשמוע עוד?"},
  "מוניטורינג":{r:"📡 מערכת הניטור שלי בודקת את האתר <b>כל 60 שניות</b> מכמה מוקדים בעולם. אני מודד: uptime, זמני תגובה, תוקף SSL, ואבטחה. מקבלים <b>התראות בוואטסאפ</b> כשמשהו משתבש. מחיר: החל מ-₪99 לחודש. רוצה לשמוע עוד?"},
  "פיתוח":{r:"💻 אני בונה <b>דפי נחיתה, אתרים עסקיים, חנויות אונליין</b> ומערכות מותאמות אישית. כל האתרים: מעוצבים, מהירים, מותאמים לנייד וידידותיים לקידום אורגני. מחיר: החל מ-₪199 לחודש. רוצה שאכין לך הצעת מחיר?"},
  "אתר":{r:"💻 אני בונה <b>דפי נחיתה, אתרים עסקיים, חנויות אונליין</b> ומערכות מותאמות אישית. כל האתרים: מעוצבים, מהירים, מותאמים לנייד וידידותיים לקידום אורגני. מחיר: החל מ-₪199 לחודש. רוצה שאכין לך הצעת מחיר?"},
  "דמו":{r:"🎨 בדף הדמו אפשר לראות אתרים שבנינו, להתרשם מעיצובים, ואפילו הבונה החכם ייצר לכם דמו מותאם אישית! רוצים לנסות?"},"דף נחיתה":{r:"📄 דפי נחיתה מקצועיים עם טפסים חכמים, אופטימיזציית המרה, ועיצוב מודרני. כולל A/B טסטינג ואנליטיקס. בונים ומעלים תוך 48 שעות. מחיר: ₪199 לחודש. רוצה לראות דוגמאות?"},
  "אחסון":{r:"☁️ אחסון מהיר ומאובטח על תשתית ענן. כולל: SSL חינם, גיבויים יומיים, 99.9% uptime, הגנת DDoS, ולוח בקרה נוח. מחיר: החל מ-₪49 לחודש. רוצה לשמוע פרטים?"},
  "אחסן":{r:"☁️ אחסון מהיר ומאובטח על תשתית ענן. כולל: SSL חינם, גיבויים יומיים, 99.9% uptime, הגנת DDoS, ולוח בקרה נוח. מחיר: החל מ-₪49 לחודש. רוצה לשמוע פרטים?"},
  "crm":{r:"📊 מערכת CRM חכמה שמרכזת את כל הלידים שלך במקום אחד: ניהול צינור מכירה, תזכורות, דוחות, ואינטגרציה עם וואטסאפ. מחיר: כלול בחבילת Business (₪499 לחודש). שווה לבדוק?"},
  "מחיר":{r:"💰 החבילות שלנו:<br>🚀 <b>Starter</b> — ₪199/חודש — דף נחיתה + אחסון<br>⭐ <b>Business</b> — ₪499/חודש — 3 דפים + ניטור + CRM<br>👑 <b>Premium</b> — ₪999/חודש — הכל כלול + תמיכה VIP<br>רוצה שאסביר על חבילה ספציפית?"},
  "צור קשר":{r:"📞 בטח! אפשר ליצור קשר ב:<br>📱 טלפון: <b>052-8529448</b><br>📧 אימייל: <b>info@landingflow.co.il</b><br>📍 רוטשילד 22, תל אביב<br><br>או פשוט תשאיר לי שם וטלפון ואחזור אליך! 👇"},
  "שלום":{r:"היי! 👋 איך אפשר לעזור? אשמח לספר לך על השירותים שלנו — בדיקת אתרים, ניטור, פיתוח, אחסון, CRM ועוד. במה אתה מתעניין?"},
  "היי":{r:"היי! 👋 איך אפשר לעזור? אשמח לספר לך על השירותים שלנו — בדיקת אתרים, ניטור, פיתוח, אחסון, CRM ועוד. במה אתה מתעניין?"},
  "תודה":{r:"בשמחה! 😊 אם תרצה לשמוע עוד או לבדוק את האתר שלך — אני פה. אפשר גם להשאיר פרטים ואחזור אליך עם הצעה."},
  "כן":{r:"מעולה! 🎉 אשמח לעזור. ספר לי במה אתה מתעניין — בדיקת אתר, פיתוח, ניטור, אחסון, CRM, או משהו אחר?"},
  "איך":{r:"אשמח להסביר! 📝 ספר לי מה מעניין אותך ואתן לך את כל הפרטים. במה תרצה להתמקד?"},"מה":{r:"אני מציע: 🔍 בדיקת אתרים חינם, 📡 ניטור 24/7, 💻 פיתוח אתרים ודפי נחיתה, ☁️ אחסון, 📊 CRM לניהול לידים. מה מעניין אותך?"}
};

function randomReply(){var a=["אני פה לעזור! 😊 במה תרצה להתמקד?","אפשר לספר לי יותר? אני מכיר את כל השירותים שלנו.","מעניין! באיזה מהשירותים שלנו תרצה להתעמק?","אני מזהה שאתה מתעניין. ספר לי במה בדיוק?"];return{r:a[Math.floor(Math.random()*a.length)]}}
function findAnswer(text){
  var t=text.toLowerCase();
  for(var k in faq){if(t.indexOf(k)>=0)return faq[k]}
  if(t.indexOf("הצע")>=0||t.indexOf("מחי")>=0||t.indexOf("כמה")>=0) return faq["מחיר"];
  if(t.indexOf("דמו")>=0||t.indexOf("דוגמ")>=0) return {r:"🎨 יש לנו דף דמו חי! אפשר לראות אתרים שבנינו ולדבר עם הבונה החכם. <a href='<?= $url("demo") ?>' style='color:#fff;text-decoration:underline'>לחץ כאן לדמו ←</a>"};
  return randomReply();
}

function askLead(){
  step="lead";
  var h='<div style="margin-bottom:14px;font-weight:700;font-size:.92rem">📋 מעולה! משאיר לך פרטים ואחזור בהקדם:</div>';
  h+='<form id="leadForm" onsubmit="return submitLead()">';
  h+='<input type="text" id="leadName" placeholder="שם מלא *" required>';
  h+='<input type="tel" id="leadPhone" placeholder="טלפון *" required>';
  h+='<input type="email" id="leadEmail" placeholder="אימייל">';
  h+='<textarea id="leadMsg" placeholder="מה מעניין אותך?" rows="2"></textarea>';
  h+='<button type="submit" class="btn-submit">שלח פרטים ✉️</button></form>';
  addMsg(h,"lead-form");
}

window.submitLead=function(){
  var n=document.getElementById("leadName").value.trim(),p=document.getElementById("leadPhone").value.trim(),e=document.getElementById("leadEmail").value.trim(),m=document.getElementById("leadMsg").value.trim();
  if(!n||!p){addMsg("⚠️ אנא מלא שם וטלפון לפחות","bot");return false}
  fetch("<?= $url('contact') ?>",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"name="+encodeURIComponent(n)+"&phone="+encodeURIComponent(p)+"&email="+encodeURIComponent(e)+"&message="+encodeURIComponent("[סוכן חכם] "+m)+"&<?= defined('CSRF_TOKEN_NAME') ? CSRF_TOKEN_NAME : 'csrf' ?>="+encodeURIComponent(document.querySelector('input[name=<?= defined("CSRF_TOKEN_NAME") ? CSRF_TOKEN_NAME : "csrf_token" ?>]')?.value||"")});
  addMsg("🙏 תודה! הפרטים נשלחו בהצלחה. נחזור אליך בהקדם בימים א-ה 09:00-18:00","bot");
  step="done";document.querySelector(".agent-msg.lead-form").style.pointerEvents="none";document.querySelector(".agent-msg.lead-form").style.opacity="0.7";
  return false
};

send.addEventListener("click",handleSend);inp.addEventListener("keydown",function(e){if(e.key==="Enter")handleSend()});
function handleSend(){
  var text=inp.value.trim();if(!text)return;
  addMsg(text,"user");inp.value="";
  if(step==="lead")return;
  showTyping(function(){
    if(text.match(/פרטים|טלפון|צור קשר|השאיר|מכיר|להתקשר|שיחה/i)||text.match(/כן.*הצע/i)||text.match(/רוצה.*לשמוע/i)||text.match(/מעוניין/i)){askLead();return}
    var ans=findAnswer(text);addMsg(ans.r,"bot");
    if(Math.random()<0.5){setTimeout(function(){showTyping(function(){addMsg("אגב, אם תרצה שאחזור אליך עם הצעה מותאמת — פשוט תגיד 😊","bot")})},2000)}
  });
}
})();
</script>

</body></html>
