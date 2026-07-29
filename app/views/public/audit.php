<?php ob_start() ?>
<section style="padding-top:140px"><div class="container">
  <div class="head-center" style="margin-bottom:40px"><span class="section-eyebrow">בדיקת אתר חינם</span><h1 class="section-title" style="margin:0 auto 12px">בדקו את האתר שלכם עכשיו</h1><p class="section-sub" style="margin:0 auto 30px">הזינו URL ונקבל דוח מקיף על SEO, אבטחה, נגישות, דרישות משפטיות וביצועים.</p></div>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:22px;padding:28px;max-width:600px;margin:0 auto;box-shadow:var(--shadow-md)">
    <form id="af"><?= $csrf() ?><div class="form-group"><label>כתובת האתר *</label><input type="url" name="url" id="au" placeholder="https://example.com" required style="direction:ltr;text-align:left;font-family:var(--font-mono)"></div>
    <div class="form-group"><label>אימייל לקבלת הדוח *</label><div style="display:flex;gap:8px"><input type="email" name="email" id="ae" placeholder="your@email.com" required style="direction:ltr;text-align:left;flex:1"><button type="button" id="sendCodeBtn" class="btn btn-secondary" style="white-space:nowrap">שלח קוד אימות</button></div></div>
    <div id="codeRow" class="form-group" style="display:none"><label>קוד אימות</label><input type="text" name="code" id="ac" placeholder="הזן קוד בן 6 ספרות" maxlength="6" style="direction:ltr;text-align:center;font-family:var(--font-mono);font-size:1.2rem;letter-spacing:4px"><p style="font-size:.78rem;color:var(--success);margin-top:4px" id="codeMsg"></p></div>
    <div class="check-row" style="margin-bottom:16px"><input type="checkbox" id="auditConsent" required><label for="auditConsent">אני מאשר/ת את <a href="<?= $url('terms-of-service') ?>" target="_blank">תנאי השימוש</a></label></div>
    <button type="submit" class="btn btn-primary btn-lg btn-block" id="scanBtn">התחל בדיקה מקיפה</button></form>
    <div id="load" style="display:none;text-align:center;padding:30px"><div style="width:40px;height:40px;border:3px solid var(--border);border-top-color:var(--primary);border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 16px"></div><p style="color:var(--ink-soft)">בודק את האתר...</p></div>
    <style>@keyframes spin{to{transform:rotate(360deg)}}</style>
    <div id="res" style="display:none;margin-top:20px">
      <div id="domainInfo" style="background:var(--surface-2);border:1px solid var(--border);border-radius:12px;padding:16px 20px;margin-bottom:20px;display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:.85rem"></div>
      <div id="sc" style="width:150px;height:150px;border-radius:50%;background:conic-gradient(var(--success) 0deg,var(--border) 0deg);display:flex;align-items:center;justify-content:center;margin:0 auto 20px"><div style="width:110px;height:110px;border-radius:50%;background:var(--surface);display:flex;flex-direction:column;align-items:center;justify-content:center"><span id="sn" style="font-family:var(--font-mono);font-size:2.5rem;font-weight:800">0</span><span style="font-size:.75rem;color:var(--ink-faint)">/100</span></div></div>
      <div id="de"></div>
      <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:24px"><a id="pl" href="#" class="btn btn-primary" target="_blank">📥 הורד PDF</a><a id="wl" href="#" class="btn btn-ghost" target="_blank">💬 שלח בוואטסאפ</a></div>
    </div>
  </div>
</div></section>
<script>
var csrfToken=document.querySelector('input[name="<?= CSRF_TOKEN_NAME ?>"]').value;
// Send verification code
document.getElementById("sendCodeBtn").addEventListener("click",async function(){
  var em=document.getElementById("ae").value;
  if(!em){alert("נא להזין אימייל");return}
  var btn=this;btn.disabled=true;btn.textContent="שולח...";
  try{
    var r=await fetch("<?= $url('audit/check') ?>",{method:"POST",body:new URLSearchParams({email:em,action:'sendCode','<?= CSRF_TOKEN_NAME ?>':csrfToken})});
    var d=await r.json();
    if(d.success){document.getElementById("codeRow").style.display="block";document.getElementById("codeMsg").textContent=d.message;btn.textContent="נשלח ✓"}else{alert(d.error);btn.disabled=false;btn.textContent="שלח קוד אימות"}
  }catch(err){alert("שגיאה בשליחת הקוד");btn.disabled=false;btn.textContent="שלח קוד אימות"}
});
// Scan submission
document.getElementById("af").addEventListener("submit",async function(e){e.preventDefault();var u=document.getElementById("au").value;var em=document.getElementById("ae").value;var code=document.getElementById("ac").value;if(!em){alert("נא להזין אימייל");return}if(!code){alert("יש להזין קוד אימות. לחץ על שלח קוד");return}document.getElementById("load").style.display="block";document.getElementById("res").style.display="none";try{var r=await fetch("<?= $url('audit/check') ?>",{method:"POST",body:new URLSearchParams({url:u,email:em,code:code,'<?= CSRF_TOKEN_NAME ?>':csrfToken})});var d=await r.json();if(!d.success){alert("Error: "+d.error);document.getElementById("load").style.display="none";return}document.getElementById("load").style.display="none";document.getElementById("res").style.display="block";
    var ui=d.urlInfo||{};document.getElementById("domainInfo").innerHTML="<div><strong>כתובת:</strong> "+ui.url+"</div><div><strong>פרוטוקול:</strong> "+(ui.protocol||"-")+"</div><div><strong>דומיין:</strong> "+ui.domain+"</div><div><strong>סיומת:</strong> ."+ui.tld+"</div><div><strong>WWW:</strong> "+(ui.has_www?"כן":"לא")+"</div><div><strong>נתיב:</strong> "+(ui.path||"/")+"</div>";
    var sn=document.getElementById("sn"),sc=document.getElementById("sc"),t=d.overall,c=0;var iv=setInterval(function(){c=Math.min(t,c+2);sn.textContent=c;sc.style.background="conic-gradient(var(--success) "+(c*3.6)+"deg,var(--border) 0deg)";if(c>=t)clearInterval(iv)},20);var h="";for(var[cat,data]of Object.entries(d.results)){h+="<h3 style=margin:20px 0 10px;font-size:.95rem;font-weight:700;color:var(--primary-dark)>"+data.label+" ("+data.score+"/100)</h3>";for(var ck of Object.values(data.checks)){h+="<div style=display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);font-size:.85rem><span>"+ck.label+"</span><span style=font-size:.78rem;color:var(--ink-faint);margin:0 8px>"+(ck.value||"")+"</span><span style=font-weight:700;color:"+(ck.passed?"var(--success)":"var(--danger)")+">"+(ck.passed?"✔":"✘")+"</span></div>";if(ck.detail&&ck.detail!==ck.value)h+="<div style=font-size:.75rem;color:var(--ink-faint);padding:2px 0 8px 16px;border-bottom:1px solid var(--border)>📋 "+ck.detail+"</div>";if(!ck.passed&&ck.impact)h+="<div style=font-size:.75rem;color:var(--danger);padding:4px 0 8px 16px;border-bottom:1px solid var(--border)>⚠️ "+ck.impact+"</div>"}}h+="<div style=margin-top:20px;padding:16px;background:var(--surface-2);border-radius:12px><h4 style=font-size:.9rem;font-weight:700;margin-bottom:8px>המלצות לשיפור</h4>";for(var rec of(d.recommendations||[]))h+="<div style=font-size:.82rem;color:var(--ink-soft);padding:4px 0>• "+rec.action+"</div>";h+="</div>";document.getElementById("de").innerHTML=h;var msg="דוח ביקורת "+u+"%0A%0Aציון: "+d.overall+"/100%0A";document.getElementById("wl").href="https://wa.me/972528529448?text="+msg;document.getElementById("pl").href="<?= $url('audit/pdf') ?>/"+d.reportId}catch(err){alert("שגיאה: "+(err.message||"ודאו שהURL תקין"));document.getElementById("load").style.display="none";console.error(err)}});
</script>
<?php $content = ob_get_clean(); include __DIR__ . '/../partials/layout.php'; ?>
