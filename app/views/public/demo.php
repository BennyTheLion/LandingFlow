<?php ob_start(); ?>
<section style="padding:80px 0;background:var(--bg)"><div class="container" style="text-align:center">
  <div class="head-center" style="margin-bottom:40px">
    <span class="section-eyebrow" style="background:var(--surface-2);color:var(--ink-soft)">עוזר חכם</span>
    <h2 class="section-title" style="margin:0 auto 12px;color:var(--ink)">תארו את העסק — וקבלו אתר מרשים תוך שניות</h2>
    <p class="section-sub" style="margin:0 auto 30px;color:var(--ink-soft)">פשוט הקלידו תיאור חופשי. העוזר יבין לבד את סוג העסק, הסגנון והשם — וייצר תצוגה מקדימה</p>
  </div>

  <style>
    .chat-widget{max-width:700px;margin:0 auto;background:var(--surface);border-radius:14px;overflow:hidden;box-shadow:var(--shadow-lg);text-align:right}
    .chat-header{background:var(--surface-2);padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}.chat-header .avatar{width:32px;height:32px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1rem}.chat-header strong{font-size:.9rem}.chat-header span{font-size:.72rem;color:var(--ink-faint)}
    .chat-body{padding:16px;min-height:300px;max-height:500px;overflow-y:auto;display:flex;flex-direction:column;gap:10px}
    .chat-msg{max-width:85%;padding:10px 14px;border-radius:10px;font-size:.88rem;line-height:1.55;animation:fadeIn .3s ease}
    .chat-msg.assistant{background:var(--surface-2);color:#000 !important;align-self:flex-end;border-bottom-left-radius:4px}
    .chat-msg.user{background:var(--primary);color:#fff;align-self:flex-start;border-bottom-right-radius:4px}
    .chat-options{display:flex;flex-wrap:wrap;gap:6px;margin-top:4px}
    .chat-opt{background:var(--surface);border:1.5px solid var(--border);padding:6px 12px;border-radius:100px;font-size:.76rem;cursor:pointer;transition:.2s;font-family:inherit;color:var(--ink)}.chat-opt:hover,.chat-opt.selected{background:var(--primary);color:#fff;border-color:var(--primary)}
    .chat-input-row{display:flex;gap:6px;padding:8px 12px;border-top:1px solid var(--border);background:var(--surface-2)}
    .chat-input-row input{flex:1;padding:8px 10px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:.82rem;outline:none}.chat-input-row input:focus{border-color:var(--primary)}
    .chat-input-row button{background:var(--primary);color:#fff;border:none;padding:8px 14px;border-radius:8px;font-family:inherit;font-weight:700;cursor:pointer;transition:.2s;font-size:.82rem}.chat-input-row button:hover{background:var(--primary-dark)}
    .preview-modal{display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.6);align-items:center;justify-content:center;padding:20px}.preview-modal.open{display:flex}
    .preview-window{background:#fff;border-radius:16px;width:100%;max-width:900px;max-height:90vh;overflow:hidden;display:flex;flex-direction:column}
    .preview-toolbar{display:flex;align-items:center;justify-content:center;padding:12px 50px;background:var(--bg);border-bottom:1px solid var(--border);position:relative}.preview-close{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:var(--bg);border:1.5px solid var(--border);border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.9rem}.preview-close:hover{background:#DC2626;color:#fff;border-color:#DC2626}.preview-open-tab{position:absolute;left:56px;top:50%;transform:translateY(-50%);background:var(--primary);color:#fff;border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.95rem;transition:.2s}.preview-open-tab:hover{background:var(--primary-dark)}
    .preview-frame{flex:1;min-height:500px;border:none;width:100%}
    @keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
    .model-toggle{display:flex;gap:0;background:var(--surface-2);border-radius:8px;padding:2px;margin-left:auto}.model-opt{padding:5px 12px;border-radius:6px;font-size:.68rem;cursor:pointer;border:none;font-family:inherit;background:transparent;color:var(--ink-soft)}.model-opt.active{background:var(--surface);font-weight:700;color:var(--ink);box-shadow:0 1px 2px rgba(0,0,0,.08)}.model-opt.premium{color:var(--primary)}.model-opt.premium.active{color:var(--primary);font-weight:700}
  </style>

  <div class="chat-widget" id="chatWidget">
    <?= $csrf() ?>
    <div class="chat-header"><div class="avatar">🤖</div><div><strong>העוזר של LandingFlow</strong><br><span>אונליין · עונה תוך שניות</span></div><div class="model-toggle"><button class="model-opt active" data-model="free">🏠 חינם</button><button class="model-opt premium" data-model="ai">✨ AI</button></div></div>
    <div class="chat-body" id="chatBody"></div>
    <div class="chat-input-row" id="chatInputRow"><input type="text" id="chatInput" placeholder="הקלידו תשובה..."><button type="button" id="chatSend">שלח</button></div>
  </div>

  <div class="preview-modal" id="previewModal" onclick="if(event.target===this)closePreview()">
    <div class="preview-window">
      <div class="preview-toolbar"><h3>תצוגה מקדימה — האתר שלכם</h3><button class="preview-close" onclick="closePreview()">✕</button><button class="preview-open-tab" onclick="openInNewTab()" title="פתח בלשונית חדשה">🔗</button></div>
      <iframe class="preview-frame" id="previewFrame" sandbox="allow-scripts"></iframe>
    </div>
  </div>
</div></section>

<script>
var selectedModel = 'free';
document.querySelectorAll('.model-opt').forEach(function(btn){
  btn.addEventListener('click', function(){
    document.querySelectorAll('.model-opt').forEach(function(b){ b.classList.remove('active'); });
    this.classList.add('active');
    selectedModel = this.dataset.model;
  });
});

(function(){
  console.log('✅ JS STARTED');
  document.title = 'JS OK' ;
  var body=document.getElementById("chatBody"),input=document.getElementById("chatInput"),send=document.getElementById("chatSend");
  var step=0,answers={type:"",color:"",name:"",desc:""};
  var waitingForInput=true;
  var prototypeCount = 0;

  send.onclick = function(){ processDescription(); };
  input.addEventListener('keydown',function(e){ if(e.key==='Enter'){ processDescription(); } });

  var keywords={
    type:{"מסעדה":"🍽️ מסעדה","מסעדת":"🍽️ מסעדה","אוכל":"🍽️ מסעדה","בית קפה":"☕ בית קפה","קפה":"☕ בית קפה","מאפייה":"🍞 מאפייה","קייטרינג":"🍽️ מסעדה",
      "חנות":"🛍️ חנות אונליין","אונליין":"🛍️ חנות אונליין","שופ":"🛍️ חנות אונליין","בוטיק":"👗 בוטיק","בגדים":"👗 בוטיק","אופנה":"👗 בוטיק",
      "עורך דין":"⚖️ משרד עורכי דין","עורכת דין":"⚖️ משרד עורכי דין","משפטי":"⚖️ משרד עורכי דין","עו\"ד":"⚖️ משרד עורכי דין","ביטוח":"🛡️ סוכנות ביטוח","פיננסי":"💰 פיננסים",
      "קליניקה":"🏥 קליניקה","רופא":"🏥 קליניקה","רופאת":"🏥 קליניקה","מרפאה":"🏥 קליניקה","שיניים":"🦷 מרפאת שיניים","רופא שיניים":"🦷 מרפאת שיניים","וטרינר":"🐾 מרפאה וטרינרית",
      "מספרה":"💇 מספרה","ספר":"💇 מספרה","מעצב שיער":"💇 מספרה","קוסמטיקה":"💄 קוסמטיקה","טיפוח":"💄 קוסמטיקה",
      "הייטק":"💻 חברת הייטק","תוכנה":"💻 חברת הייטק","טק":"💻 חברת הייטק","סטארט אפ":"💻 חברת הייטק","אפליקציה":"📱 אפליקציה",
      "קבלן":"🏗️ קבלן/אדריכל","אדריכל":"🏗️ קבלן/אדריכל","בנייה":"🏗️ קבלן/אדריכל","שיפוץ":"🔨 שיפוצים","שיפוצים":"🔨 שיפוצים",
      "סטודיו":"🎨 סטודיו לעיצוב","מעצב":"🎨 סטודיו לעיצוב","מעצבת":"🎨 סטודיו לעיצוב","עיצוב":"🎨 סטודיו לעיצוב","גרפי":"🎨 סטודיו לעיצוב","מיתוג":"🎨 סטודיו לעיצוב",
      "סטודיו יוגה":"🧘 סטודיו יוגה","יוגה":"🧘 סטודיו יוגה","פילאטיס":"🧘 סטודיו יוגה","מאמן כושר":"🏋️ חדר כושר","מאמנת כושר":"🏋️ חדר כושר","מאמן":"🏋️ חדר כושר","אימון":"🏋️ חדר כושר","כושר":"🏋️ חדר כושר","חדר כושר":"🏋️ חדר כושר","ג'ים":"🏋️ חדר כושר","fitness":"🏋️ חדר כושר","trainer":"🏋️ חדר כושר","coach":"🏋️ חדר כושר",
      "צילום":"📸 צילום","צלם":"📸 צילום","נדלן":"🏘️ נדלן","נדל\"ן":"🏘️ נדל\"ן","תיווך":"🏘️ נדל\"ן","מוסך":"🔧 מוסך","רכב":"🚗 רכב","חינוך":"📚 חינוך","גן":"👶 גן ילדים",
      "ייעוץ":"📋 ייעוץ","יועץ":"📋 ייעוץ","משרד":"🏢 משרד","הפקות":"🎬 הפקות"
    },
    color:{"מודרני":"🌑 מודרני-כהה","כהה":"🌑 מודרני-כהה","שחור":"🌑 מודרני-כהה","אלגנטי":"🌊 אלגנטי-כחול","כחול":"🌊 אלגנטי-כחול","נקי":"🌊 אלגנטי-כחול",
      "ירוק":"🌿 טבעי-ירוק","טבעי":"🌿 טבעי-ירוק","טבע":"🌿 טבעי-ירוק","אדום":"🔥 נועז-אדום","נועז":"🔥 נועז-אדום","בולט":"🔥 נועז-אדום","חם":"🔥 נועז-אדום",
      "זהב":"🌟 יוקרתי-זהב","יוקרתי":"🌟 יוקרתי-זהב","יוקרה":"🌟 יוקרתי-זהב","פרימיום":"🌟 יוקרתי-זהב","כתום":"☀️ חם-כתום","שמש":"☀️ חם-כתום",
      "לבן":"🤍 מינימלי-לבן","מינימלי":"🤍 מינימלי-לבן","בהיר":"🤍 מינימלי-לבן","סגול":"💜 סגול-יצירתי","יצירתי":"💜 סגול-יצירתי","ורוד":"🩷 ורוד-רך","רך":"🩷 ורוד-רך","נעים":"🩷 ורוד-רך"
    }
  };

  function parseDescription(text){
    var t="",c="",n="";
    // Detect type FIRST
    for(var k in keywords.type){if(text.indexOf(k)>=0&&!t){t=keywords.type[k];break}}
    // Extract name from FIRST segment only (before comma/period/semicolon)
    var segments=text.split(/[,;\n]+/);
    var first=segments[0].trim();
    // Try quoted name from first segment
    var qm=first.match(/['"\u201C\u201D\u201E\u201F]([^'"\u201C\u201D\u201E\u201F]+)['"\u201C\u201D\u201E\u201F]/);
    if(qm){n=qm[1].trim()}
    // Try 'בשם X' in first segment
    if(!n){var re=/בשם\s+([\u0590-\u05FF\w\s&-]{2,30})/;var m=first.match(re);if(m)n=m[1].trim()}
    // If still no name, try whole-text patterns (explicit business naming)
    if(!n){re=/שקוראים? לי?\s+([\u0590-\u05FF\w\s&-]{2,30})/;m=text.match(re);if(m)n=m[1].trim()}
    if(!n){re=/העסק שלי הוא\s+([\u0590-\u05FF\w\s&-]{2,30})/;m=text.match(re);if(m)n=m[1].trim()}
    if(!n){re=/שם העסק\s+([\u0590-\u05FF\w\s&-]{2,30})/;m=text.match(re);if(m)n=m[1].trim()}
    // Fallback: full-text quoted name
    if(!n){var fqm=text.match(/['"\u201C\u201D\u201E\u201F]([^'"\u201C\u201D\u201E\u201F]+)['"\u201C\u201D\u201E\u201F]/);if(fqm)n=fqm[1].trim()}
    // Final fallback: first segment (remove quotes, max 30 chars)
    if(!n){n=first.replace(/['"\u201C\u201D\u201E\u201F]/g,"").substring(0,30)||"העסק שלכם"}
    for(var k in keywords.color){if(text.indexOf(k)>=0&&!c){c=keywords.color[k];break}}
    return {type:t||"",color:c||"",name:n||""};
  }

  function addMsg(text,cls){var d=document.createElement("div");d.className="chat-msg "+cls;d.innerHTML=text;body.appendChild(d);body.scrollTop=body.scrollHeight}
  function addOptions(opts,callback){var d=document.createElement("div");d.className="chat-options";opts.forEach(function(o){var b=document.createElement("button");b.className="chat-opt";b.textContent=o.label;b.onclick=function(){d.querySelectorAll('.chat-opt').forEach(function(x){x.classList.remove('selected')});b.classList.add('selected');callback(o.value)};d.appendChild(b)});body.appendChild(d);body.scrollTop=body.scrollHeight}
  function showInput(){input.style.display="block";document.querySelector(".chat-input-row").style.display="flex";input.value="";input.focus();waitingForInput=true}

  function startWizard(){
    console.log('startWizard running');
    addMsg("היי! 👋 אני הבונה החכם של LandingFlow.<br><br>פשוט <b>תארו לי את העסק שלכם</b> — מי אתם, מה אתם עושים, איזה סגנון אתם אוהבים.<br><br><i>לדוגמה: \"מסעדת פיצה רומא, אוכל איטלקי אותנטי, סטודיו לעיצוב גרפי בשם 'צורה וצבע', סגנון יצירתי-צעיר\"</i>","assistant");
    showInput();
  }

  function processDescription(){
    var v=input.value.trim();if(!v)return;
    addMsg(v,"user");answers.desc=v;input.value="";input.style.display="none";document.querySelector(".chat-input-row").style.display="none";
    waitingForInput=false;
    var parsed=parseDescription(v);
    if(!parsed.type)parsed.type="💻 חברת הייטק";
    if(!parsed.color)parsed.color="🌊 אלגנטי-כחול";
    if(!parsed.name){parsed.name=v.replace(/['"\u201C\u201D\u201E\u201F]/g,"").split(/[,.\\n\\\\]/)[0].trim().substring(0,30)||"העסק שלכם"}
    answers.type=parsed.type;answers.color=parsed.color;answers.name=parsed.name;
    addMsg("הבנתי! ✨<br><br>📌 <b>סוג עסק:</b> "+answers.type+"<br>🎨 <b>סגנון:</b> "+answers.color+"<br>🏷️ <b>שם:</b> "+answers.name+"<br><br>האם ליצור אתר עם הנתונים האלה?","assistant");
    addOptions([{label:"✅ כן, תמשיך!",value:"yes"},{label:"❌ לא, תן לי להקליד שוב",value:"redo"}],function(val){
      if(val==="yes"){sendDemoLead()}else{addMsg("בסדר, תאר שוב — אני מקשיב 😊","assistant");showInput()}
    });
  }

  function askEmail(){
    addMsg("מעולה! 📧<br><br>כדי שנוכל לשלוח לכם את האתר ולשמור את הפרטים — <b>מה האימייל שלכם?</b>","assistant");
    input.style.display="block";input.placeholder="your@email.com";input.value="";document.querySelector(".chat-input-row").style.display="flex";input.focus();
    send.onclick = function(){
      var em=input.value.trim();
      if(!em||!em.includes('@')){addMsg("⚠️ נא להזין אימייל תקין","assistant");return}
      answers.email=em;addMsg(em,"user");input.value="";input.style.display="none";document.querySelector(".chat-input-row").style.display="none";
      sendDemoLead();
    };
    input.onkeydown = function(e){ if(e.key==='Enter'){ send.onclick(); } };
  }

  function sendDemoLead(){
    addMsg("⏳ AI בונה לכם אתר...","assistant");
    var csrf=document.querySelector('input[name="csrf_token"]');
    // Open new tab during click (user gesture) — will replace content when ready
    var newTab = window.open('about:blank', '_blank');
    if(newTab){ newTab.document.title = 'בונה אתר...'; }
    window._pendingTab = newTab;
    // Only send lead capture if email was provided
    if(answers.email){
      fetch('/demo/request',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'name='+encodeURIComponent(answers.name)+'&phone=&email='+encodeURIComponent(answers.email)+'&project_type='+encodeURIComponent(answers.type)+'&message='+encodeURIComponent(answers.desc)+'&csrf_token='+(csrf?csrf.value:'')
      }).catch(function(){});
    }
    fetch('/demo/build?csrf_token='+encodeURIComponent(csrf?csrf.value:''),{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({name:answers.name,type:answers.type,description:answers.desc,model:selectedModel})
    }).then(function(r){return r.json()}).then(function(d){
      if(d.success){
        if(d.trials_remaining!==undefined)addMsg('📊 נותרו '+d.trials_remaining+' ניסיונות חינמיים','assistant');
        window._prototype = d.prototype;
        buildPreview();
        var tab = window._pendingTab;
        if(tab && window._previewHtml){
          var blob = new Blob([window._previewHtml], {type:'text/html;charset=utf-8'});
          tab.location.replace(URL.createObjectURL(blob));
        }
      }else if(d.limit_reached){
        if(newTab) try { newTab.close(); } catch(e){}
        addMsg('🚫 '+d.message+'<br><br>📧 <b>'+d.contact.email+'</b><br>📞 <b>'+d.contact.phone+'</b><br><a href="'+d.contact.whatsapp+'" target="_blank" style="color:var(--success)">💬 WhatsApp</a>','assistant');
        document.querySelector('.chat-input-row').style.display='none';
      }else{
        buildPreview();
        if(newTab && window._previewHtml){
          try { newTab.document.open(); newTab.document.write(window._previewHtml); newTab.document.close(); } catch(e){}
        }
        setTimeout(function(){ if(window._previewHtml) showPreview(); }, 200);
      }
    }).catch(function(){
      setTimeout(showPreview,300);
    });
  }

  function showPreview(){
    prototypeCount++;
    var previewMsg = "🎉 בום! יצרתי לכם דמו מרשים!<br><br><a href='#' onclick='openPreview()' style='color:var(--primary);font-weight:700;font-size:1rem;border:2px dashed var(--primary);padding:10px 20px;border-radius:12px;display:inline-block'>👁️ לחצו כאן לצפייה באתר ←</a>";
    addMsg(previewMsg,"assistant");
    buildPreview();

    if (prototypeCount >= 2) {
      addMsg("יצרת כבר "+prototypeCount+" אבות-טיפוס! 🚀<br><br>נראה שאתה רציני לגבי האתר שלך.<br><b>צור איתנו קשר עכשיו ונתאים לך אתר מושלם:</b><br><br>📧 <b>info@landingflow.co.il</b><br>📞 <b>052-8529448</b><br><a href='https://wa.me/972528529448' target='_blank' style='color:var(--success);font-weight:700'>💬 WhatsApp</a>","assistant");
    } else {
      addMsg("רוצה לנסות שוב עם רעיון אחר? 🤔","assistant");
      addOptions([{label:"🔄 כן, בוא נתחיל מחדש",value:"again"},{label:"📞 צור קשר",value:"contact"}],function(val){
        if(val==="again"){
          answers={type:"",color:"",name:"",desc:""};
          addMsg("מעולה! תאר לי את העסק מחדש ואני אבנה לך אתר נוסף 😊","assistant");
          showInput();
        } else {
          addMsg("נהדר! צור איתנו קשר:<br><br>📧 <b>info@landingflow.co.il</b><br>📞 <b>052-8529448</b><br><a href='https://wa.me/972528529448' target='_blank' style='color:var(--success);font-weight:700'>💬 WhatsApp</a>","assistant");
        }
      });
    }
  }
  window.openPreview=function(){document.getElementById("previewModal").classList.add("open")};
  window.closePreview=function(){document.getElementById("previewModal").classList.remove("open")};

  var themes={
    "🌊 אלגנטי-כחול":{bg:"#0f172a",accent:"#3b82f6",accent2:"#8b5cf6",light:"#f8fafc",card:"#fff",text:"#1e293b",soft:"#94a3b8",gradient:"linear-gradient(135deg,#0f172a 0%,#1e3a5f 50%,#3b82f6 100%)"},
    "🌿 טבעי-ירוק":{bg:"#064e3b",accent:"#10b981",accent2:"#34d399",light:"#f0fdf4",card:"#fff",text:"#1e293b",soft:"#6b7280",gradient:"linear-gradient(135deg,#064e3b 0%,#047857 50%,#10b981 100%)"},
    "🔥 נועז-אדום":{bg:"#1a0a0a",accent:"#ef4444",accent2:"#f97316",light:"#fef2f2",card:"#fff",text:"#1e293b",soft:"#9ca3af",gradient:"linear-gradient(135deg,#1a0a0a 0%,#7f1d1d 50%,#ef4444 100%)"},
    "🌟 יוקרתי-זהב":{bg:"#0c0a09",accent:"#d4a843",accent2:"#f5d06b",light:"#fffbeb",card:"#fff",text:"#1e293b",soft:"#78716c",gradient:"linear-gradient(135deg,#0c0a09 0%,#292524 50%,#d4a843 100%)"},
    "☀️ חם-כתום":{bg:"#1c0e00",accent:"#f97316",accent2:"#fbbf24",light:"#fff7ed",card:"#fff",text:"#1e293b",soft:"#9ca3af",gradient:"linear-gradient(135deg,#1c0e00 0%,#9a3412 50%,#f97316 100%)"},
    "🌑 מודרני-כהה":{bg:"#0a0a0a",accent:"#6366f1",accent2:"#a78bfa",light:"#fafafa",card:"#fff",text:"#1e293b",soft:"#71717a",gradient:"linear-gradient(135deg,#0a0a0a 0%,#18181b 50%,#6366f1 100%)"},
    "🤍 מינימלי-לבן":{bg:"#ffffff",accent:"#1e293b",accent2:"#64748b",light:"#f8fafc",card:"#fff",text:"#0f172a",soft:"#64748b",gradient:"linear-gradient(135deg,#ffffff 0%,#f1f5f9 50%,#e2e8f0 100%)"},
    "💜 סגול-יצירתי":{bg:"#1a0a2e",accent:"#a855f7",accent2:"#c084fc",light:"#faf5ff",card:"#fff",text:"#1e293b",soft:"#a1a1aa",gradient:"linear-gradient(135deg,#1a0a2e 0%,#4c1d95 50%,#a855f7 100%)"},
    "🩷 ורוד-רך":{bg:"#1a0a14",accent:"#ec4899",accent2:"#f472b6",light:"#fdf2f8",card:"#fff",text:"#1e293b",soft:"#9ca3af",gradient:"linear-gradient(135deg,#1a0a14 0%,#831843 50%,#ec4899 100%)"}
  };

  function buildPreview(){
    var p = window._prototype; if(!p) return;
    var frame = document.getElementById('previewFrame');
    if(!frame) return;
    var t = themes[answers.color] || themes["🌊 אלגנטי-כחול"];
    var sections = p.sections || [];
    var h = '<!DOCTYPE html><html dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>'+p.name+'</title><style>';
    h += '*{margin:0;padding:0;box-sizing:border-box}body{font-family:system-ui,sans-serif;background:'+t.light+';color:'+t.text+'}';
    h += '.container{max-width:1100px;margin:0 auto;padding:0 20px}';
    h += '.btn{display:inline-block;padding:14px 32px;border-radius:60px;font-weight:700;text-decoration:none;transition:.2s;font-family:inherit}';
    h += '.btn-primary{background:'+t.accent+';color:#fff}.btn-secondary{background:transparent;border:2px solid '+t.accent+';color:'+t.accent+'}';
    h += '.anim{opacity:0;transform:translateY(30px);transition:opacity .6s ease,transform .6s ease}.anim.visible{opacity:1;transform:translateY(0)}';
    h += '.anim-delay-1{transition-delay:.1s}.anim-delay-2{transition-delay:.2s}.anim-delay-3{transition-delay:.3s}.anim-delay-4{transition-delay:.4s}.anim-delay-5{transition-delay:.5s}.anim-delay-6{transition-delay:.6s}'
    h += '@keyframes heroZoom{0%{transform:scale(1)}100%{transform:scale(1.08)}}';
    h += '.nb{position:fixed;top:0;left:0;right:0;z-index:1000;padding:12px 24px;display:flex;align-items:center;justify-content:space-between;transition:.3s;background:transparent}';
    h += '.nb.scrolled{background:'+t.bg+';box-shadow:0 2px 20px rgba(0,0,0,.15)}.nb-brand{font-size:1.3rem;font-weight:800;color:#fff;text-decoration:none}';
    h += '.nb-links{display:flex;gap:24px;list-style:none}.nb-links a{color:rgba(255,255,255,.85);text-decoration:none;font-size:.88rem;transition:.2s}.nb-links a:hover{color:#fff}';
    h += '.nb-hamburger{display:none;background:none;border:none;color:#fff;font-size:1.6rem;cursor:pointer;padding:4px}';
    h += '.nb-mobile{display:none;position:fixed;top:56px;left:0;right:0;background:'+t.bg+';padding:12px 24px;flex-direction:column;gap:10px;z-index:999;box-shadow:0 4px 12px rgba(0,0,0,.2)}';
    h += '.nb-mobile.open{display:flex}.nb-mobile a{color:rgba(255,255,255,.85);text-decoration:none;font-size:.95rem;padding:8px 0}';
    h += '@media(max-width:768px){.nb-links{display:none}.nb-hamburger{display:block}}';
    h += '</style></head><body style="direction:rtl">';
    // Navbar
    var navItems = [{label:'בית',href:'#hero'},{label:'אודות',href:'#features'},{label:'שירותים',href:'#process'},{label:'צור קשר',href:'#contact'}];
    h += '<nav class="nb" id="topNav"><a href="#hero" class="nb-brand">'+p.name+'</a><ul class="nb-links">'+navItems.map(function(l){return '<li><a href="'+l.href+'">'+l.label+'</a></li>'}).join('')+'</ul><button class="nb-hamburger" onclick="document.getElementById(\'mobileMenu\').classList.toggle(\'open\')">☰</button></nav>';
    h += '<div class="nb-mobile" id="mobileMenu">'+navItems.map(function(l){return '<a href="'+l.href+'">'+l.label+'</a>'}).join('')+'</div>';
    sections.forEach(function(s){
      if(s.type==='hero'){
        var heroBg = s.image_url ? 'background:url('+s.image_url+') center/cover;' : 'background:'+t.gradient+';';
        var heroExtra = s.image_url ? 'background-size:cover;animation:heroZoom 20s ease-in-out infinite alternate;' : '';
        h += '<section id="hero" style="'+heroBg+'color:#fff;'+heroExtra+'padding:100px 24px 80px;text-align:center;position:relative;min-height:60vh;display:flex;align-items:center;overflow:hidden">';
        h += '<div style="position:absolute;inset:0;background:'+t.gradient+';opacity:.35"></div>';
        h += '<div class="container" style="position:relative;z-index:1">';
        h += '<h1 style="font-size:2.8rem;font-weight:800;margin-bottom:16px;line-height:1.2">'+s.title+'</h1>';
        h += '<p style="font-size:1.2rem;opacity:.85;max-width:600px;margin:0 auto 32px">'+s.subtitle+'</p>';
        h += '<a href="#" class="btn" style="background:#fff;color:'+t.accent+';padding:16px 40px;border-radius:60px">'+s.cta+'</a>';
        h += '</div></section>';
      } else if(s.type==='features'){
        h += '<section id="features" style="padding:80px 24px;background:'+t.card+'"><div class="container">';
        h += '<h2 style="text-align:center;font-size:2.2rem;font-weight:700;margin-bottom:12px">'+s.title+'</h2>';
        h += '<p style="text-align:center;color:#64748b;max-width:600px;margin:0 auto 40px">'+s.subtitle+'</p>';
        h += '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:24px">';
        (s.cards||s.items||[]).forEach(function(c){
          h += '<div style="background:'+t.light+';padding:28px;border-radius:16px">';
          if(Array.isArray(c)) { h += '<div style="font-size:2rem;margin-bottom:12px">'+c[0]+'</div><h3 style="font-size:1.1rem;font-weight:700;margin-bottom:8px">'+c[1]+'</h3><p style="color:#64748b;font-size:.9rem">'+c[2]+'</p>'; }
          else { h += '<div style="font-size:2rem;margin-bottom:12px">'+(c.icon||'✨')+'</div><h3 style="font-size:1.1rem;font-weight:700;margin-bottom:8px">'+c.title+'</h3><p style="color:#64748b;font-size:.9rem">'+(c.desc||c.body||'')+'</p>'; }
          h += '</div>';
        });
        h += '</div></div></section>';
      } else if(s.type==='contact'){
        h += '<section id="contact" style="padding:70px 24px;background:'+t.card+'"><div class="container" style="max-width:1100px;margin:0 auto">';
        h += '<h2 style="text-align:center;font-size:2.2rem;font-weight:700;margin-bottom:12px">'+s.title+'</h2>';
        h += '<p style="text-align:center;color:#64748b;max-width:600px;margin:0 auto 40px">'+s.subtitle+'</p>';
        h += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:50px;max-width:800px;margin:0 auto">';
        h += '<div><div style="margin-bottom:16px"><strong>📞 טלפון:</strong><br>'+(s.phone||"052-8529448")+'</div>';
        h += '<div style="margin-bottom:16px"><strong>📧 אימייל:</strong><br>'+(s.email||"info@landingflow.co.il")+'</div><div style="margin-bottom:16px"><strong>📍 מיקום:</strong><br>ישראל</div></div>';
        h += '<form onsubmit="return false"><input style="width:100%;padding:12px 16px;border:1px solid #d1d5db;border-radius:12px;font-family:inherit;font-size:1rem;margin-bottom:16px" placeholder="שם מלא">';
        h += '<input style="width:100%;padding:12px 16px;border:1px solid #d1d5db;border-radius:12px;font-family:inherit;font-size:1rem;margin-bottom:16px" placeholder="אימייל">';
        h += '<textarea style="width:100%;padding:12px 16px;border:1px solid #d1d5db;border-radius:12px;font-family:inherit;height:100px;margin-bottom:16px" placeholder="הודעה"></textarea>';
        h += '<button style="width:100%;background:'+t.accent+';color:#fff;border:none;padding:14px;border-radius:60px;font-weight:600;cursor:pointer;font-family:inherit;font-size:1rem">'+s.cta+'</button></form></div></div></section>';
      } else if(s.type==='stats'){
        h += '<section style="padding:60px 24px;background:'+t.accent+';color:#fff">';
        h += '<div class="container" style="display:grid;grid-template-columns:repeat(4,1fr);gap:24px;text-align:center">';
        (s.items||[]).forEach(function(st){ h += '<div><div style="font-size:2.5rem;font-weight:800">'+st.value+'</div><div style="font-size:.85rem;opacity:.8">'+st.label+'</div></div>'; });
        h += '</div></section>';
      } else if(s.type==='testimonials' || s.type==='reviews'){
        h += '<section style="padding:80px 24px;background:'+t.light+'"><div class="container">';
        h += '<h2 style="text-align:center;font-size:2.2rem;font-weight:700;margin-bottom:12px">'+s.title+'</h2>';
        h += '<p style="text-align:center;color:#64748b;max-width:600px;margin:0 auto 40px">'+s.subtitle+'</p>';
        h += '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px">';
        (s.items||[]).forEach(function(q){ h += '<div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.05)"><div style="color:#f59e0b;margin-bottom:8px">★★★★★</div><p style="font-style:italic;margin-bottom:8px;line-height:1.6">"'+q.quote+'"</p><strong style="font-size:.85rem">'+q.name+'</strong></div>'; });
        h += '</div></div></section>';
      } else if(s.type==='benefits' || s.type==='process'){
        h += '<section id="'+s.type+'" style="padding:80px 24px;background:'+t.card+'"><div class="container">';
        h += '<h2 style="text-align:center;font-size:2.2rem;font-weight:700;margin-bottom:12px">'+s.title+'</h2>';
        if(s.subtitle) h += '<p style="text-align:center;color:#64748b;max-width:600px;margin:0 auto 40px">'+s.subtitle+'</p>';
        h += '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px">';
        (s.items||[]).forEach(function(b){ h += '<div style="background:'+t.light+';padding:24px;border-radius:12px;text-align:center"><div style="font-size:2rem;margin-bottom:8px">'+(b.icon||'✅')+'</div><h3 style="font-size:1rem;font-weight:700;margin-bottom:4px">'+b.title+'</h3><p style="color:#64748b;font-size:.85rem">'+(b.desc||b.body)+'</p></div>'; });
        h += '</div></div></section>';
      } else if(s.type==='pricing'){
        h += '<section style="padding:80px 24px;background:'+t.light+'"><div class="container">';
        h += '<h2 style="text-align:center;font-size:2.2rem;font-weight:700;margin-bottom:12px">'+s.title+'</h2>';
        h += '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px">';
        (s.plans||[]).forEach(function(p){ h += '<div style="background:#fff;padding:32px 24px;border-radius:14px;border:1px solid #e5e7eb;text-align:center"><h3 style="font-size:1.1rem;margin-bottom:8px">'+p.name+'</h3><div style="font-size:2.5rem;font-weight:800;color:'+t.accent+';margin-bottom:4px">'+p.price+'</div><div style="color:#64748b;margin-bottom:16px">'+p.period+'</div><ul style="list-style:none;text-align:right;margin-bottom:20px">'+(p.features||[]).map(function(f){return '<li style="padding:4px 0;font-size:.85rem">✓ '+f+'</li>'}).join('')+'</ul></div>'; });
        h += '</div></div></section>';
      } else if(s.type==='faq'){
        h += '<section style="padding:80px 24px;background:'+t.card+'"><div class="container" style="max-width:700px">';
        h += '<h2 style="text-align:center;font-size:2.2rem;font-weight:700;margin-bottom:32px">'+s.title+'</h2>';
        (s.items||[]).forEach(function(f){ h += '<div style="margin-bottom:16px;padding:16px 20px;background:'+t.light+';border-radius:10px"><strong style="display:block;margin-bottom:4px">'+f.q+'</strong><p style="color:#64748b;font-size:.9rem">'+f.a+'</p></div>'; });
        h += '</div></section>';
      } else if(s.type==='team'){
        h += '<section style="padding:80px 24px;background:'+t.light+'"><div class="container">';
        h += '<h2 style="text-align:center;font-size:2.2rem;font-weight:700;margin-bottom:32px">'+s.title+'</h2>';
        h += '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px">';
        (s.members||[]).forEach(function(m){ h += '<div style="text-align:center;padding:16px;background:#fff;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,.05)"><div style="font-size:2.8rem;margin-bottom:8px;line-height:1">'+(m.avatar||'👤')+'</div><h3 style="font-size:1rem;font-weight:700;margin-bottom:2px">'+m.name+'</h3><p style="color:#64748b;font-size:.78rem;margin-bottom:6px">'+m.role+'</p><p style="color:#94a3b8;font-size:.7rem;line-height:1.4">'+(m.bio||'')+'</p></div>'; });
        h += '</div></div></section>';
      } else if(s.type==='gallery'){
        h += '<section style="padding:80px 24px;background:'+t.card+'"><div class="container">';
        h += '<h2 style="text-align:center;font-size:2.2rem;font-weight:700;margin-bottom:12px">'+s.title+'</h2>';
        if(s.subtitle) h += '<p style="text-align:center;color:#64748b;max-width:600px;margin:0 auto 40px">'+s.subtitle+'</p>';
        h += '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px">';
        (s.images||[]).forEach(function(img){ h += '<div style="border-radius:14px;overflow:hidden;aspect-ratio:1;position:relative;background:'+t.gradient+'"><img src="'+img.thumb+'" alt="'+img.alt+'" loading="lazy" style="width:100%;height:100%;object-fit:cover" onerror="this.parentElement.style.display=\'none\'"><div style="position:absolute;inset:0;background:linear-gradient(transparent 40%,rgba(0,0,0,.75));display:flex;flex-direction:column;justify-content:flex-end;padding:16px;color:#fff"><strong style="font-size:.9rem;margin-bottom:2px">'+(img.name||'')+'</strong><span style="font-size:.72rem;opacity:.85">'+(img.desc||'')+'</span></div></div>'; });
        h += '</div></div></section>';
      } else if(s.type==='appointments'){
        var apptBg = t.accent || '#3b82f6';
        h += '<section style="padding:50px 24px;background:#fef3c7;text-align:center;border-top:4px solid #ef4444;border-bottom:4px solid #ef4444"><div class="container" style="max-width:500px;margin:0 auto">';
        h += '<h2 style="font-size:1.5rem;color:#991b1b;margin-bottom:8px">📅 '+(s.title||'קבע פגישה')+'</h2>';
        if(s.subtitle) h += '<p style="color:#b91c1c;margin-bottom:20px">'+s.subtitle+'</p>';
        h += '<div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-bottom:12px">';
        h += '<input type="date" id="apptDate" style="padding:10px 14px;border:2px solid #ef4444;border-radius:8px;font-family:inherit;font-size:.95rem;color:#991b1b;background:#fff" min="'+new Date().toISOString().split('T')[0]+'">';
        h += '<select id="apptTime" style="padding:10px 14px;border:2px solid #ef4444;border-radius:8px;font-family:inherit;font-size:.95rem;color:#991b1b;background:#fff"><option value="">בחר שעה</option><option>09:00</option><option>10:00</option><option>11:00</option><option>12:00</option><option>13:00</option><option>14:00</option><option>15:00</option><option>16:00</option><option>17:00</option><option>18:00</option></select>';
        h += '</div>';
        h += '<button onclick="var d=document.getElementById(\'apptDate\').value;var t=document.getElementById(\'apptTime\').value;if(d&&t)alert(\'תור נקבע ל-\'+d+\' בשעה \'+t);else alert(\'נא לבחור תאריך ושעה\')" style="background:#ef4444;color:#fff;border:none;padding:12px 30px;border-radius:8px;font-family:inherit;font-size:1rem;font-weight:700;cursor:pointer">'+(s.cta||'קבע תור')+'</button>';
        if(s.hint) h += '<p style="margin-top:14px;font-size:.82rem;color:#991b1b">'+s.hint+'</p>';
        // Weekly class schedule
        var days = ['ראשון','שני','שלישי','רביעי','חמישי','שישי','שבת'];
        var classes = ['יוגה בוקר 08:00','פילאטיס 10:00','ספינינג 12:00','אימון כוח 16:00','TRX 17:30','קרוספיט 19:00','מנוחה'];
        h += '<div style="margin-top:20px"><h3 style="font-size:1.1rem;color:#991b1b;margin-bottom:12px">📋 לוח שיעורים שבועי</h3>';
        h += '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;font-size:.72rem">';
        days.forEach(function(d,i){ h += '<div style="background:#fff;padding:6px 2px;border-radius:4px;border:1px solid #fca5a5"><strong style="display:block;color:#991b1b;margin-bottom:2px">'+d+'</strong><span style="color:#b91c1c">'+classes[i]+'</span></div>'; });
        h += '</div></div>';
        h += '</div></section>';
      } else if(s.type==='cta'){
        h += '<section style="padding:80px 24px;background:'+t.gradient+';text-align:center;color:#fff"><div class="container">';
        h += '<h2 style="font-size:2.2rem;font-weight:700;margin-bottom:12px">'+s.title+'</h2>';
        if(s.subtitle) h += '<p style="opacity:.85;margin-bottom:24px">'+s.subtitle+'</p>';
        h += '<a href="#" class="btn" style="background:#fff;color:'+t.accent+';padding:16px 40px">'+s.cta+'</a>';
        h += '</div></section>';
       } else if(s.type==='footer'){
        var flinks = (s.links||[]).map(function(l){return '<a href="'+l.href+'" style="color:rgba(255,255,255,.5);text-decoration:none;font-size:.82rem;display:block;margin-bottom:6px">'+l.label+'</a>'}).join('');
        h += '<footer style="background:#111827;color:#fff;padding:50px 24px 30px"><div class="container" style="max-width:1100px;margin:0 auto"><div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:30px;text-align:right;margin-bottom:30px"><div><h4 style="font-size:1.1rem;margin-bottom:12px;color:#fff">'+s.brand+'</h4><p style="font-size:.82rem;color:rgba(255,255,255,.5);line-height:1.7">'+(s.description||'הפתרון המושלם לעסק שלך — אתר מקצועי, שירות אישי, תוצאות אמיתיות')+'</p></div><div><h4 style="font-size:.9rem;margin-bottom:12px;color:rgba(255,255,255,.7)">ניווט מהיר</h4>'+flinks+'</div><div><h4 style="font-size:.9rem;margin-bottom:12px;color:rgba(255,255,255,.7)">צרו קשר</h4><div style="font-size:.82rem;color:rgba(255,255,255,.5);line-height:2"><div>📞 '+(s.phone||'052-8529448')+'</div><div>📧 '+(s.email||'info@landingflow.co.il')+'</div><div>📍 '+(s.address||'ישראל')+'</div></div></div></div><div style="border-top:1px solid rgba(255,255,255,.1);padding-top:20px;text-align:center;font-size:.78rem;color:rgba(255,255,255,.3)">© '+new Date().getFullYear()+' '+s.brand+'. כל הזכויות שמורות. | תנאי שימוש | פרטיות</div></div></footer>';
      }
    });
    h += '</body><script>(function(){window.addEventListener("scroll",function(){var n=document.getElementById("topNav");if(n)n.classList.toggle("scrolled",window.scrollY>50)});var els=document.querySelectorAll("section > div > div > div, section img");els.forEach(function(el,i){el.classList.add("anim","anim-delay-"+(i%6+1))});var ob=new IntersectionObserver(function(entries){entries.forEach(function(e){if(e.isIntersecting){e.target.classList.add("visible");ob.unobserve(e.target)}})},{threshold:0.15});els.forEach(function(el){ob.observe(el)})})();<' + '/script></html>';
    frame.srcdoc = h;
    // Store for open-in-tab
    window._previewHtml = h;
  }

  function openInNewTab(){
    if(!window._previewHtml) return;
    var blob = new Blob([window._previewHtml], {type:'text/html;charset=utf-8'});
    var url = URL.createObjectURL(blob);
    window.open(url, '_blank');
  }

  setTimeout(startWizard, 400);
})();
</script>

<?php $content = ob_get_clean(); include __DIR__ . '/../partials/layout.php'; ?>
