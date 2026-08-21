<?php ob_start() ?>
<style>
.audit-hero{padding-top:96px;padding-bottom:40px}
.audit-card-wrap{position:relative;max-width:600px;margin:0 auto}
.audit-card{background:var(--surface);border:1px solid var(--border);border-radius:22px;padding:28px;box-shadow:var(--shadow-lg)}
.audit-stamp{position:absolute;top:-28px;left:-26px;width:100px;height:100px;transform:rotate(-10deg);mix-blend-mode:multiply;opacity:.9;pointer-events:none;z-index:2}
@media(max-width:640px){.audit-stamp{width:76px;height:76px;top:-16px;left:-10px}}
.audit-note{display:flex;align-items:center;gap:7px;justify-content:center;margin-top:18px;font-family:var(--font-mono);font-size:.74rem;color:var(--ink-faint);letter-spacing:.01em}
</style>
<section class="audit-hero"><div class="container">
  <div class="head-center" style="margin-bottom:20px"><span class="section-eyebrow">בדיקת אתר חינם</span><h1 class="section-title" style="margin:0 auto 6px;font-size:clamp(1.5rem,3.4vw,2.2rem)">בדקו את האתר שלכם עכשיו</h1><p class="section-sub" style="margin:0 auto">הזינו URL ונקבל דוח מקיף על SEO, אבטחה, נגישות, דרישות משפטיות וביצועים.</p></div>
  <div class="audit-card-wrap">
    <svg class="audit-stamp" viewBox="0 0 140 140" aria-hidden="true">
      <defs>
        <filter id="inkRoughAudit" x="-30%" y="-30%" width="160%" height="160%">
          <feTurbulence type="fractalNoise" baseFrequency=".85" numOctaves="2" result="n"/>
          <feDisplacementMap in="SourceGraphic" in2="n" scale="3.2"/>
        </filter>
        <path id="stampRingAudit" d="M70,70 m-54,0 a54,54 0 1,1 108,0 a54,54 0 1,1 -108,0"/>
      </defs>
      <g filter="url(#inkRoughAudit)">
        <circle cx="70" cy="70" r="62" fill="none" stroke="var(--stamp)" stroke-width="2.5"/>
        <circle cx="70" cy="70" r="48" fill="none" stroke="var(--stamp)" stroke-width="1.5"/>
      </g>
      <text font-size="12.5" font-weight="700" fill="var(--stamp)" letter-spacing="1.3" direction="ltr" style="unicode-bidi:bidi-override"><textPath href="#stampRingAudit" startOffset="2%">דבלב היצמוטוא אל ✦ תישיא קדבנ ✦</textPath></text>
      <text x="70" y="78" text-anchor="middle" font-family="var(--font-serif)" font-weight="900" font-size="27" fill="var(--stamp)">LF</text>
    </svg>
    <div class="audit-card">
    <form id="af"><?= $csrf() ?><div class="form-group"><label>כתובת האתר *</label><input type="url" name="url" id="au" placeholder="https://example.com" required style="direction:ltr;text-align:left;font-family:var(--font-mono)"></div>
    <div class="form-group"><label>אימייל לקבלת הדוח *</label><div style="display:flex;gap:8px"><input type="email" name="email" id="ae" placeholder="your@email.com" required style="direction:ltr;text-align:left;flex:1"><button type="button" id="sendCodeBtn" class="btn btn-secondary" style="white-space:nowrap">שלח קוד אימות</button></div></div>
    <div id="codeRow" class="form-group" style="display:none"><label>קוד אימות</label><input type="text" name="code" id="ac" placeholder="הזן קוד בן 6 ספרות" maxlength="6" style="direction:ltr;text-align:center;font-family:var(--font-mono);font-size:1.2rem;letter-spacing:4px"><p style="font-size:.78rem;color:var(--success);margin-top:4px" id="codeMsg"></p></div>
    <div class="check-row" style="margin-bottom:16px"><input type="checkbox" id="auditConsent" required><label for="auditConsent">אני מאשר/ת את <a href="<?= $url('terms-of-service') ?>" target="_blank">תנאי השימוש</a></label></div>
    <button type="submit" class="btn btn-primary btn-lg btn-block" id="scanBtn">התחל בדיקה מקיפה</button></form>
    <div id="load" style="display:none;padding:30px 10px 10px;max-width:320px;margin:0 auto">
      <div id="loadSteps" style="display:flex;flex-direction:column;gap:9px;font-size:.85rem"></div>
    </div>
    <style>
    .load-step{display:flex;align-items:center;gap:9px;color:var(--ink-faint);transition:color .2s}
    .load-step.active{color:var(--primary);font-weight:700}
    .load-step.done{color:var(--success)}
    .load-step .dot{width:16px;text-align:center;flex-shrink:0}
    .load-step.active .dot{animation:pulse 1s ease-in-out infinite}
    @keyframes pulse{0%,100%{opacity:1}50%{opacity:.35}}
    .chk-tip{position:relative;display:inline-flex;align-items:center;justify-content:center;width:16px;height:16px;margin:0 6px;border-radius:50%;background:var(--surface-2);color:var(--ink-faint);cursor:pointer;font-size:.68rem;font-weight:700;line-height:1;vertical-align:middle;font-style:normal}
    .chk-tip:hover,.chk-tip.active{background:var(--primary);color:#fff}
    .chk-tip::after{content:attr(data-tip);position:absolute;bottom:135%;right:50%;transform:translateX(50%);width:220px;max-width:65vw;background:#1e1e2e;color:#fff;padding:9px 11px;border-radius:8px;font-size:.72rem;font-weight:400;line-height:1.5;white-space:normal;text-align:right;box-shadow:0 6px 20px rgba(0,0,0,.28);opacity:0;visibility:hidden;transition:opacity .15s;pointer-events:none;z-index:30}
    .chk-tip:hover::after,.chk-tip.active::after{opacity:1;visibility:visible}
    </style>
    <div id="res" style="display:none;margin-top:20px">
      <div id="domainInfo" style="background:var(--surface-2);border:1px solid var(--border);border-radius:12px;padding:16px 20px;margin-bottom:20px;display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:.85rem"></div>
      <div id="sc" style="width:150px;height:150px;border-radius:50%;background:conic-gradient(var(--success) 0deg,var(--border) 0deg);display:flex;align-items:center;justify-content:center;margin:0 auto 20px"><div style="width:110px;height:110px;border-radius:50%;background:var(--surface);display:flex;flex-direction:column;align-items:center;justify-content:center"><span id="sn" style="font-family:var(--font-mono);font-size:2.5rem;font-weight:800">0</span><span style="font-size:.75rem;color:var(--ink-faint)">/100</span></div></div>
      <div id="de"></div>
      <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:24px"><a id="dl" href="#" class="btn btn-secondary">⬇️ הורד דוח PDF</a><button type="button" id="pl" class="btn btn-primary">📧 שלח דוח למייל</button><button type="button" id="wl" class="btn btn-ghost">💬 שלח בוואטסאפ</button></div>
      <div id="mailBox" style="display:none;max-width:430px;margin:14px auto 0;background:var(--surface-2);border:1px solid var(--border);border-radius:12px;padding:14px">
        <label for="mailTo" style="display:block;font-size:.82rem;font-weight:600;margin-bottom:6px">לאיזו כתובת לשלוח את הדוח?</label>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <input type="email" id="mailTo" placeholder="name@example.com" style="direction:ltr;text-align:left;flex:1;min-width:170px">
          <button type="button" id="mailSend" class="btn btn-primary">שלח</button>
          <button type="button" id="mailCancel" class="btn btn-ghost">ביטול</button>
        </div>
        <p style="font-size:.74rem;color:var(--ink-faint);margin-top:7px">ניתן לשנות לכתובת אחרת — למשל לשלוח את הדוח לעצמכם או לאיש מקצוע.</p>
      </div>
      <div id="waBox" style="display:none;max-width:430px;margin:14px auto 0;background:var(--surface-2);border:1px solid var(--border);border-radius:12px;padding:14px">
        <label for="waPhone" style="display:block;font-size:.82rem;font-weight:600;margin-bottom:6px">לאיזה מספר וואטסאפ לשלוח?</label>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <input type="tel" id="waPhone" placeholder="05X-XXXXXXX" style="direction:ltr;text-align:left;flex:1;min-width:170px">
          <button type="button" id="waSend" class="btn btn-primary">שלח</button>
          <button type="button" id="waCancel" class="btn btn-ghost">ביטול</button>
        </div>
        <p style="font-size:.74rem;color:var(--ink-faint);margin-top:7px">ייפתח וואטסאפ עם ההודעה מוכנה, למספר שהזנתם — למשל שלכם או של איש מקצוע.</p>
      </div>
      <p id="mailNote" style="text-align:center;font-size:.78rem;color:var(--success);margin-top:10px"></p>
    </div>
    </div>
    <p class="audit-note">✦ כל דוח נבדק אישית — לא רק אוטומציה שיורקת ציון</p>
  </div>
</div></section>
<script>
var csrfToken=document.querySelector('input[name="<?= CSRF_TOKEN_NAME ?>"]').value;
// Fake but honest step-by-step progress: the scan is one synchronous request
// (no server-sent progress), so this cycles through the six real category
// names — matching SiteAuditReport::run()'s order — while the fetch is in
// flight. Parks on the last step instead of finishing early if the request
// runs long, so it never claims to be done before the response arrives.
var LOAD_STEPS=["טוען את הדף...","בודק SEO...","בודק אבטחה...","בודק תאימות משפטית...","בודק נגישות...","בודק ביצועים...","בודק סימני ספאם ואמון..."];
var loadTimer=null,loadIdx=0;
function renderLoadSteps(){
  var html="";
  for(var i=0;i<LOAD_STEPS.length;i++){
    var state=i<loadIdx?"done":(i===loadIdx?"active":"");
    var icon=i<loadIdx?"✔":(i===loadIdx?"◐":"○");
    html+='<div class="load-step '+state+'"><span class="dot">'+icon+'</span><span>'+LOAD_STEPS[i]+'</span></div>';
  }
  document.getElementById("loadSteps").innerHTML=html;
}
function startLoadProgress(){
  loadIdx=0;renderLoadSteps();
  loadTimer=setInterval(function(){
    if(loadIdx<LOAD_STEPS.length-1){loadIdx++;renderLoadSteps()}
  },1400);
}
function stopLoadProgress(){if(loadTimer){clearInterval(loadTimer);loadTimer=null}}
// The audit endpoints are rate limited and answer 429 with an HTML error page, not
// JSON — parse that as JSON and the user sees a SyntaxError instead of the reason.
var RATE_LIMIT_MSG="שלחתם יותר מדי בקשות. נסו שוב בעוד מספר דקות.";
function isRateLimited(r){return r.status===429}
// Tap/click-to-toggle check-explanation tooltips (works on touch, not just hover)
document.getElementById("de").addEventListener("click",function(e){
  var t=e.target.closest(".chk-tip");
  document.querySelectorAll(".chk-tip.active").forEach(function(el){if(el!==t)el.classList.remove("active")});
  if(t){t.classList.toggle("active");e.stopPropagation()}
});
document.addEventListener("click",function(e){
  if(!e.target.closest(".chk-tip"))document.querySelectorAll(".chk-tip.active").forEach(function(el){el.classList.remove("active")});
});
// Send verification code
document.getElementById("sendCodeBtn").addEventListener("click",async function(){
  var em=document.getElementById("ae").value;
  if(!em){alert("נא להזין אימייל");return}
  var btn=this;btn.disabled=true;btn.textContent="שולח...";
  try{
    var r=await fetch("<?= $url('audit/check') ?>",{method:"POST",body:new URLSearchParams({email:em,action:'sendCode','<?= CSRF_TOKEN_NAME ?>':csrfToken})});
    if(isRateLimited(r)){alert(RATE_LIMIT_MSG);btn.disabled=false;btn.textContent="שלח קוד אימות";return}
    var d=await r.json();
    if(d.success){document.getElementById("codeRow").style.display="block";document.getElementById("codeMsg").textContent=d.message;btn.textContent="נשלח ✓"}else{alert(d.error);btn.disabled=false;btn.textContent="שלח קוד אימות"}
  }catch(err){alert("שגיאה בשליחת הקוד");btn.disabled=false;btn.textContent="שלח קוד אימות"}
});
// Scan submission
document.getElementById("af").addEventListener("submit",async function(e){e.preventDefault();var u=document.getElementById("au").value;var em=document.getElementById("ae").value;var code=document.getElementById("ac").value;if(!em){alert("נא להזין אימייל");return}if(!code){alert("יש להזין קוד אימות. לחץ על שלח קוד");return}document.getElementById("load").style.display="block";document.getElementById("res").style.display="none";startLoadProgress();try{var r=await fetch("<?= $url('audit/check') ?>",{method:"POST",body:new URLSearchParams({url:u,email:em,code:code,'<?= CSRF_TOKEN_NAME ?>':csrfToken})});if(isRateLimited(r)){stopLoadProgress();alert(RATE_LIMIT_MSG);document.getElementById("load").style.display="none";return}var d=await r.json();if(!d.success){stopLoadProgress();alert("Error: "+d.error);document.getElementById("load").style.display="none";return}stopLoadProgress();document.getElementById("load").style.display="none";document.getElementById("res").style.display="block";
    var ui=d.urlInfo||{};document.getElementById("domainInfo").innerHTML="<div><strong>כתובת:</strong> "+ui.url+"</div><div><strong>פרוטוקול:</strong> "+(ui.protocol||"-")+"</div><div><strong>דומיין:</strong> "+ui.domain+"</div><div><strong>סיומת:</strong> ."+ui.tld+"</div><div><strong>WWW:</strong> "+(ui.has_www?"כן":"לא")+"</div><div><strong>נתיב:</strong> "+(ui.path||"/")+"</div>";
    var sn=document.getElementById("sn"),sc=document.getElementById("sc"),t=d.overall,c=0;var iv=setInterval(function(){c=Math.min(t,c+2);sn.textContent=c;sc.style.background="conic-gradient(var(--success) "+(c*3.6)+"deg,var(--border) 0deg)";if(c>=t)clearInterval(iv)},20);var h="";for(var[cat,data]of Object.entries(d.results)){h+="<h3 style=margin:20px 0 10px;font-size:.95rem;font-weight:700;color:var(--primary-dark)>"+data.label+" ("+data.score+"/100)</h3>";for(var ck of Object.values(data.checks)){var tipHtml=ck.tip?"<span class=chk-tip tabindex=0 role=button aria-label=\"הסבר: "+ck.tip.replace(/\"/g,"&quot;")+"\" data-tip=\""+ck.tip.replace(/\"/g,"&quot;")+"\">?</span>":"";h+="<div style=display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);font-size:.85rem><span>"+ck.label+tipHtml+"</span><span style=font-size:.78rem;color:var(--ink-faint);margin:0 8px>"+(ck.value||"")+"</span><span style=font-weight:700;color:"+(ck.passed?"var(--success)":"var(--danger)")+">"+(ck.passed?"✔":"✘")+"</span></div>";if(ck.detail&&ck.detail!==ck.value)h+="<div style=font-size:.75rem;color:var(--ink-faint);padding:2px 0 8px 16px;border-bottom:1px solid var(--border)>📋 "+ck.detail+"</div>";if(!ck.passed&&ck.impact)h+="<div style=font-size:.75rem;color:var(--danger);padding:4px 0 8px 16px;border-bottom:1px solid var(--border)>⚠️ "+ck.impact+"</div>"}}h+="<div style=margin-top:20px;padding:16px;background:var(--surface-2);border-radius:12px><h4 style=font-size:.9rem;font-weight:700;margin-bottom:8px>המלצות לשיפור</h4>";for(var rec of(d.recommendations||[]))h+="<div style=font-size:.82rem;color:var(--ink-soft);padding:4px 0>• "+rec.action+"</div>";h+="</div>";document.getElementById("de").innerHTML=h;var msg="דוח ביקורת "+u+"%0A%0Aציון: "+d.overall+"/100%0A";document.getElementById("wl").dataset.msg=msg;var plBtn=document.getElementById("pl");plBtn.dataset.reportId=d.reportId;plBtn.dataset.email=em;plBtn.disabled=false;plBtn.textContent=d.emailed?"📧 שלח שוב למייל":"📧 שלח דוח למייל";document.getElementById("mailNote").textContent=d.emailed?"✅ הדוח נשלח לאימייל "+em+" (כולל קובץ PDF מצורף)":"";
    // Both the download and the re-send need a saved report to point at
    var dlBtn=document.getElementById("dl");
    if(d.reportId){dlBtn.style.display="";dlBtn.href="<?= $url('audit/download') ?>/"+d.reportId}
    else{dlBtn.style.display="none";plBtn.disabled=true;plBtn.title="הדוח לא נשמר — לא ניתן לשלוח או להוריד"}
    document.getElementById("mailBox").style.display="none"}catch(err){stopLoadProgress();alert("שגיאה: "+(err.message||"ודאו שהURL תקין"));document.getElementById("load").style.display="none";console.error(err)}});
// Sending asks which address first — the scan address is only the default
var mailBox=document.getElementById("mailBox"),mailTo=document.getElementById("mailTo");
document.getElementById("pl").addEventListener("click",function(){
  var rid=this.dataset.reportId;
  if(!rid){alert("יש להריץ בדיקה קודם");return}
  var open=mailBox.style.display!=="none";
  mailBox.style.display=open?"none":"block";
  if(!open){
    if(!mailTo.value)mailTo.value=this.dataset.email||"";
    // The box sits after the full check list (30+ rows) — on a long report it
    // can open well below the fold, and focus() alone doesn't reliably scroll
    // every browser to it. Make it impossible to miss.
    mailBox.scrollIntoView({behavior:"smooth",block:"center"});
    setTimeout(function(){mailTo.focus();mailTo.select()},300);
  }
});
document.getElementById("mailCancel").addEventListener("click",function(){mailBox.style.display="none"});
mailTo.addEventListener("keydown",function(e){if(e.key==="Enter"){e.preventDefault();document.getElementById("mailSend").click()}});
document.getElementById("mailSend").addEventListener("click",async function(){
  var btn=this,plBtn=document.getElementById("pl"),rid=plBtn.dataset.reportId,em=(mailTo.value||"").trim();
  if(!rid){alert("יש להריץ בדיקה קודם");return}
  // Same shape the server validates with, so an obvious typo is caught before the round trip
  if(!em||!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)){alert("נא להזין כתובת אימייל תקינה");mailTo.focus();return}
  var orig=btn.textContent;btn.disabled=true;btn.textContent="שולח...";
  try{
    var r=await fetch("<?= $url('audit/report') ?>",{method:"POST",body:new URLSearchParams({reportId:rid,email:em,'<?= CSRF_TOKEN_NAME ?>':csrfToken})});
    if(isRateLimited(r)){alert(RATE_LIMIT_MSG);btn.textContent=orig;btn.disabled=false;return}
    var d=await r.json();
    if(d.success){mailBox.style.display="none";document.getElementById("mailNote").textContent="✅ הדוח נשלח לאימייל "+em+" (כולל קובץ PDF מצורף)";plBtn.textContent="📧 שלח שוב למייל"}
    else{alert(d.error||"שליחת הדוח נכשלה")}
  }catch(err){alert("שגיאה בשליחת הדוח")}
  finally{btn.textContent=orig;btn.disabled=false}
});
// WhatsApp: ask which number first, same pattern as the email box — the
// message is prepared once per scan and stashed on #wl's dataset, then
// reused however many times the box gets opened for a different number.
var waBox=document.getElementById("waBox"),waPhone=document.getElementById("waPhone");
document.getElementById("wl").addEventListener("click",function(){
  if(!this.dataset.msg){alert("יש להריץ בדיקה קודם");return}
  var open=waBox.style.display!=="none";
  waBox.style.display=open?"none":"block";
  if(!open){
    waBox.scrollIntoView({behavior:"smooth",block:"center"});
    setTimeout(function(){waPhone.focus()},300);
  }
});
document.getElementById("waCancel").addEventListener("click",function(){waBox.style.display="none"});
waPhone.addEventListener("keydown",function(e){if(e.key==="Enter"){e.preventDefault();document.getElementById("waSend").click()}});
document.getElementById("waSend").addEventListener("click",function(){
  var msg=document.getElementById("wl").dataset.msg||"";
  var digits=(waPhone.value||"").replace(/\D/g,"");
  // Israeli local format (0XX-XXXXXXX) -> international (972XXXXXXXXX);
  // already-international numbers pass through unchanged.
  var intl=digits.startsWith("972")?digits:digits.replace(/^0/,"972");
  if(intl.length<11){alert("נא להזין מספר טלפון תקין");waPhone.focus();return}
  window.open("https://wa.me/"+intl+"?text="+msg,"_blank");
  waBox.style.display="none";
});
</script>
<?php $content = ob_get_clean(); include __DIR__ . '/../partials/layout.php'; ?>
