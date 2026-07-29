<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WeBoost — בונה דפי נחיתה</title>
<style>
:root {
  --bg: #f1f5f9;
  --surface: #ffffff;
  --surface2: #f8fafc;
  --border: #e2e8f0;
  --ink: #0f172a;
  --inkSoft: #64748b;
  --inkFaint: #94a3b8;
  --primary: #2563eb;
  --primaryDark: #1d4ed8;
  --success: #10b981;
  --radius: 16px;
  --shadow: 0 4px 24px rgba(0,0,0,.06);
}
*{margin:0;padding:0;box-sizing:border-box}
body{
  font-family: 'Assistant', 'Rubik', system-ui, sans-serif;
  background: var(--bg);
  height: 100vh;
  display: flex;
  flex-direction: column;
  direction: ltr;
}

/* Header */
.wb-header{
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  padding: 14px 24px;
  display: flex;
  align-items: center;
  gap: 12px;
}
.wb-logo{font-weight:800;font-size:1.1rem;color:var(--ink)}
.wb-badge{
  font-size:.7rem;
  background: var(--primary);
  color: #fff;
  padding: 4px 12px;
  border-radius: 100px;
  font-weight: 600;
}
.wb-step{margin-right:auto;font-size:.78rem;color:var(--inkSoft)}

/* Progress bar */
.wb-progress-bar{
  height: 3px;
  background: var(--border);
  overflow: hidden;
}
.wb-progress-fill{
  height: 100%;
  background: linear-gradient(90deg, var(--primary), #3b82f6);
  transition: width .4s ease;
  border-radius: 0 3px 3px 0;
}

/* Chat area */
.wb-main{
  flex: 1;
  display: flex;
  flex-direction: column;
  max-width: 700px;
  width: 100%;
  margin: 0 auto;
  overflow: hidden;
}
.wb-chat{
  flex: 1;
  overflow-y: auto;
  padding: 20px 24px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.wb-chat::-webkit-scrollbar{width:5px}
.wb-chat::-webkit-scrollbar-thumb{background:var(--border);border-radius:10px}

/* Messages */
.wb-msg{
  max-width: 85%;
  padding: 12px 16px;
  border-radius: 14px;
  font-size: .9rem;
  line-height: 1.55;
  animation: fadeIn .35s ease;
}
.wb-msg.bot{
  background: var(--surface);
  border: 1px solid var(--border);
  align-self: flex-start;
  border-bottom-right-radius: 4px;
}
.wb-msg.user{
  background: var(--primary);
  color: #fff;
  align-self: flex-end;
  border-bottom-left-radius: 4px;
}
.wb-msg.summary{
  background: rgba(37,99,235,.04);
  border: 2px solid var(--primary);
  align-self: stretch;
  max-width: 100%;
  font-size: .85rem;
}
.wb-msg.summary h3{
  font-size: 1rem;
  margin-bottom: 14px;
  color: var(--primary);
}
.wb-msg.summary .row{
  display: flex;
  padding: 5px 0;
  font-size: .84rem;
  border-bottom: 1px solid rgba(0,0,0,.04);
}
.wb-msg.summary .lbl{
  color: var(--inkSoft);
  width: 90px;
  flex-shrink: 0;
}
.wb-hint{
  font-size: .76rem;
  color: var(--inkFaint);
  margin-top: -8px;
}

/* Quick-reply buttons */
.wb-options{
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-self: flex-start;
}
.wb-opt{
  background: var(--surface);
  border: 1.5px solid var(--border);
  padding: 9px 18px;
  border-radius: 100px;
  font-size: .84rem;
  cursor: pointer;
  transition: all .2s;
  font-family: inherit;
  color: var(--ink);
  white-space: nowrap;
}
.wb-opt:hover{
  background: var(--primary);
  color: #fff;
  border-color: var(--primary);
}
.wb-opt.selected{
  background: var(--primary);
  color: #fff;
  border-color: var(--primary);
}

/* Input area */
.wb-input-row{
  display: flex;
  gap: 10px;
  padding: 14px 24px;
  border-top: 1px solid var(--border);
  background: var(--surface);
}
.wb-input-row textarea{
  flex: 1;
  padding: 12px 16px;
  border: 1.5px solid var(--border);
  border-radius: 12px;
  font-family: inherit;
  font-size: .9rem;
  resize: none;
  min-height: 56px;
  outline: none;
  direction: ltr;
}
.wb-input-row textarea:focus{
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(37,99,235,.1);
}
.wb-input-row button{
  background: var(--primary);
  color: #fff;
  border: none;
  padding: 12px 24px;
  border-radius: 12px;
  font-family: inherit;
  font-weight: 700;
  cursor: pointer;
  font-size: .9rem;
  white-space: nowrap;
  transition: background .2s;
}
.wb-input-row button:hover{background:var(--primaryDark)}
.wb-input-row button:disabled{opacity:.5;cursor:not-allowed}

/* Generation progress */
.gen-bar{
  width: 200px;
  height: 6px;
  background: var(--surface2);
  border-radius: 8px;
  overflow: hidden;
  margin-top: 8px;
}
.gen-bar .fill{
  height: 100%;
  background: linear-gradient(90deg, var(--primary), #3b82f6);
  border-radius: 8px;
  transition: width .3s ease;
}

/* Preview link */
.preview-link{
  display: inline-block;
  margin-top: 10px;
  padding: 10px 24px;
  background: var(--success);
  color: #fff;
  border-radius: 100px;
  font-weight: 700;
  font-size: .85rem;
  text-decoration: none;
}

@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

/* Mobile */
@media(max-width:500px){
  .wb-header{padding:10px 14px}
  .wb-chat{padding:14px 12px}
  .wb-input-row{padding:10px 12px}
  .wb-msg{max-width:90%}
}
</style>
</head>
<body>

<div class="wb-header">
  <span class="wb-logo">WEBOOST</span>
  <span class="wb-badge">בונה דפי נחיתה חכם</span>
  <span class="wb-step" id="wbStep">שלב 1/6</span>
</div>

<div class="wb-progress-bar"><div class="wb-progress-fill" id="wbProgress" style="width:0%"></div></div>

<div class="wb-main">
  <div class="wb-chat" id="wbChat"></div>

  <div class="wb-input-row" id="wbInput">
    <textarea id="wbText" placeholder="הקלד תשובה כאן..." rows="2"></textarea>
    <button id="wbSend">שלח</button>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
var chat = document.getElementById('wbChat'),
    input = document.getElementById('wbText'),
    send = document.getElementById('wbSend'),
    step = 0,
    totalSteps = 6,
    userData = {};

var questions = [
  { id: 'ownerName',  q: 'היי! 👋 מה השם שלך?',                           hint: 'ישראל ישראלי' },
  { id: 'bizName',    q: 'מה שם העסק?',                                  hint: '"פיצה רומא", "סטודיו דיזיין"...' },
  { id: 'category',   q: 'באיזה תחום עוסק העסק?',                        type: 'options',
    options: [
      { label:'🍽️ מסעדה / בית קפה',    value:'restaurant' },
      { label:'⚖️ שירותים מקצועיים',    value:'professional' },
      { label:'💻 טכנולוגיה / SaaS',     value:'tech' },
      { label:'🏗️ בניה / שיפוצים',      value:'construction' },
      { label:'🏠 נדל״ן',                value:'realestate' },
      { label:'🏋️ כושר / חדר כושר',     value:'fitness' },
      { label:'🛍️ חנות / קמעונאות',     value:'retail' },
      { label:'💅 יופי / ספא',           value:'beauty' },
      { label:'📚 חינוך / קורסים',       value:'education' },
      { label:'🔧 רכב / מוסך',           value:'auto' }
    ]
  },
  { id: 'tone',       q: 'איזה סגנון מדבר אלייך?',                       type: 'options',
    options: [
      { label:'💼 מודרני-מקצועי',  value:'modern' },
      { label:'🤗 חם וידידותי',    value:'warm' },
      { label:'🔥 נועז ודינמי',    value:'bold' },
      { label:'✨ יוקרתי ואלגנטי', value:'luxury' },
      { label:'🤍 נקי ומינימליסטי',value:'minimal' }
    ]
  },
  { id: 'description', q: 'תאר/י בקצרה מה העסק עושה:',                    hint: 'פיצה איטלקית אותנטית, פסטות בעבודת יד...' },
  { id: 'action',     q: 'מה הפעולה העיקרית שהלקוח צריך לעשות?',          type: 'options',
    options: [
      { label:'📞 להתקשר',        value:'call' },
      { label:'📅 לקבוע פגישה',   value:'book' },
      { label:'🛒 להזמין / לקנות',value:'order' },
      { label:'📧 להשאיר פרטים',  value:'contact' }
    ]
  }
];

function addMsg(text, cls) {
  var d = document.createElement('div');
  d.className = 'wb-msg ' + (cls || 'bot');
  d.innerHTML = text;
  chat.appendChild(d);
  chat.scrollTop = chat.scrollHeight;
}

function addHint(text) {
  var d = document.createElement('div');
  d.className = 'wb-hint';
  d.innerHTML = '<span style="color:var(--inkFaint);font-size:.76rem">💡 ' + text + '</span>';
  chat.appendChild(d);
  chat.scrollTop = chat.scrollHeight;
}

function addOpts(opts, cb) {
  var d = document.createElement('div');
  d.className = 'wb-options';
  opts.forEach(function(o) {
    var b = document.createElement('button');
    b.className = 'wb-opt';
    b.textContent = o.label;
    b.onclick = function() {
      d.querySelectorAll('.wb-opt').forEach(function(y) { y.classList.remove('selected'); });
      b.classList.add('selected');
      cb(o.value, o.label);
    };
    d.appendChild(b);
  });
  chat.appendChild(d);
  chat.scrollTop = chat.scrollHeight;
}

function updateProgress() {
  var pct = Math.round((step / totalSteps) * 100);
  document.getElementById('wbProgress').style.width = pct + '%';
  document.getElementById('wbStep').textContent = 'שלב ' + Math.min(step+1,totalSteps) + '/' + totalSteps;
}

function askQuestion() {
  if (step >= questions.length) {
    showSummary();
    return;
  }
  var q = questions[step];
  addMsg(q.q, 'bot');
  if (q.hint) addHint(q.hint);
  step++;
  updateProgress();

  if (q.type === 'options') {
    document.getElementById('wbInput').style.display = 'none';
    addOpts(q.options, function(val, label) {
      userData[q.id] = val;
      addMsg(label, 'user');
      askQuestion();
    });
  } else {
    showInput();
  }
}

function showInput() {
  var q = questions[step - 1];
  input.value = '';
  input.placeholder = q ? (q.hint || 'הקלד כאן...') : 'הקלד כאן...';
  document.getElementById('wbInput').style.display = 'flex';
  input.focus();
}

function hideInput() {
  document.getElementById('wbInput').style.display = 'none';
}

send.onclick = function() {
  var v = input.value.trim();
  if (!v) return;
  var q = questions[step - 1];
  userData[q.id] = v;
  addMsg(v, 'user');
  input.value = '';
  hideInput();
  askQuestion();
};

input.addEventListener('keydown', function(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    send.click();
  }
});

// --- Summary ---
function showSummary() {
  hideInput();
  updateProgress();
  step = totalSteps;
  document.getElementById('wbProgress').style.width = '100%';
  document.getElementById('wbStep').textContent = 'סיכום';

  var catLabel = '';
  var catOpts = questions[2].options;
  for (var i = 0; i < catOpts.length; i++) {
    if (catOpts[i].value === userData.category) { catLabel = catOpts[i].label; break; }
  }
  var toneLabel = '';
  var toneOpts = questions[3].options;
  for (var j = 0; j < toneOpts.length; j++) {
    if (toneOpts[j].value === userData.tone) { toneLabel = toneOpts[j].label; break; }
  }
  var actionLabel = '';
  var actOpts = questions[5].options;
  for (var k = 0; k < actOpts.length; k++) {
    if (actOpts[k].value === userData.action) { actionLabel = actOpts[k].label; break; }
  }

  var s = '<h3>🎉 סיכום — דף הנחיתה שלך</h3>';
  s += '<div class="row"><span class="lbl">שם:</span> ' + (userData.ownerName || '—') + '</div>';
  s += '<div class="row"><span class="lbl">עסק:</span> ' + (userData.bizName || '—') + '</div>';
  s += '<div class="row"><span class="lbl">תחום:</span> ' + catLabel + '</div>';
  s += '<div class="row"><span class="lbl">סגנון:</span> ' + toneLabel + '</div>';
  s += '<div class="row"><span class="lbl">תיאור:</span> ' + (userData.description || '—') + '</div>';
  s += '<div class="row"><span class="lbl">פעולה:</span> ' + actionLabel + '</div>';
  addMsg(s, 'summary');

  addOpts([
    { label: '✅ מאשר — צור דף נחיתה!', value: 'approve' },
    { label: '✏️ רוצה לשנות פרט', value: 'edit' }
  ], function(val) {
    if (val === 'approve') {
      generatePage();
    } else {
      addMsg('איזה פרט תרצה לשנות? כרגע אפשר להתחיל מחדש.', 'bot');
      addOpts([{ label: '🔄 התחל מחדש', value: 'restart' }], function() {
        location.reload();
      });
    }
  });
}

function generatePage() {
  addMsg('⏳ יוצר דף נחיתה...', 'bot');
  var bar = document.createElement('div');
  bar.className = 'gen-bar';
  bar.innerHTML = '<div class="fill" style="width:0"></div>';
  chat.lastChild.appendChild(bar);
  var fill = bar.querySelector('.fill'), pct = 0;
  var iv = setInterval(function() { if (pct < 90) { pct += 10; fill.style.width = pct + '%'; } }, 300);

  fetch('generate.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(userData)
  })
  .then(function(r) { return r.json(); })
  .then(function(d) {
    clearInterval(iv);
    fill.style.width = '100%';
    if (d.success) {
      addMsg('✅ דף הנחיתה מוכן!<br><br><a href="' + d.url + '" target="_blank" class="preview-link">🚀 פתח דף בדפדפן</a>', 'bot');
      window.open(d.url, '_blank');
    } else {
      addMsg('❌ שגיאה: ' + (d.error || 'לא ידוע'), 'bot');
    }
  })
  .catch(function(e) {
    clearInterval(iv);
    addMsg('❌ שגיאת רשת: ' + e.message, 'bot');
  });
}

// --- Start ---
setTimeout(function() {
  addMsg('היי! 👋 אני <strong>WEBOOST</strong> — בונה דפי הנחיתה החכם. אשאל 6 שאלות קצרות, ואבנה לך דף נחיתה מרשים שמותאם בדיוק לעסק שלך. מוכנה? בוא נתחיל!', 'bot');
  setTimeout(askQuestion, 500);
}, 300);

});
</script>
</body>
</html>
