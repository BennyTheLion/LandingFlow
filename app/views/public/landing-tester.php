<?php ob_start() ?>

<style>
/* ---- Landing Page Tester — standalone styles ---- */
:root {
  --lpt-primary: #5B47E0;
  --lpt-surface: #fff;
  --lpt-border: #e2e5ed;
  --lpt-ink: #1a1a2e;
  --lpt-soft: #6b7280;
  --lpt-pass: #10b981;
  --lpt-warn: #f59e0b;
  --lpt-fail: #ef4444;
  --lpt-pass-bg: #ecfdf5;
  --lpt-warn-bg: #fffbeb;
  --lpt-fail-bg: #fef2f2;
}

.lpt-hero {
  padding: 140px 0 60px;
  text-align: center;
}
.lpt-hero h1 {
  font-size: 2.6rem;
  font-weight: 800;
  margin-bottom: 12px;
  background: linear-gradient(135deg, #5B47E0, #7c3aed);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.lpt-hero .lpt-sub {
  font-size: 1.1rem;
  color: var(--lpt-soft);
  max-width: 550px;
  margin: 0 auto 30px;
}
.lpt-card {
  background: var(--lpt-surface);
  border: 1px solid var(--lpt-border);
  border-radius: 22px;
  padding: 32px;
  max-width: 680px;
  margin: 0 auto;
  box-shadow: 0 4px 24px rgba(0,0,0,.06);
}
.lpt-card label {
  font-weight: 600;
  font-size: .88rem;
  margin-bottom: 6px;
  display: block;
  color: var(--lpt-ink);
}
.lpt-card input[type="url"],
.lpt-card textarea {
  width: 100%;
  padding: 14px 16px;
  border: 2px solid var(--lpt-border);
  border-radius: 12px;
  font-size: .95rem;
  font-family: 'Courier New', monospace;
  transition: border .2s;
  direction: ltr;
  text-align: left;
}
.lpt-card input:focus,
.lpt-card textarea:focus {
  border-color: var(--lpt-primary);
  outline: none;
}
.lpt-card textarea {
  min-height: 120px;
  font-size: .82rem;
  resize: vertical;
}
.lpt-submit {
  width: 100%;
  padding: 15px;
  background: linear-gradient(135deg, #5B47E0, #7c3aed);
  color: #fff;
  border: none;
  border-radius: 14px;
  font-size: 1.05rem;
  font-weight: 700;
  cursor: pointer;
  transition: transform .15s, box-shadow .2s;
}
.lpt-submit:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 24px rgba(91,71,224,.3);
}
.lpt-submit:disabled {
  opacity: .6;
  cursor: not-allowed;
  transform: none;
}
.lpt-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
  overflow-x: auto;
}
.lpt-tab {
  padding: 10px 18px;
  border: 1px solid var(--lpt-border);
  border-radius: 10px;
  background: var(--lpt-surface);
  cursor: pointer;
  font-size: .82rem;
  font-weight: 600;
  white-space: nowrap;
  transition: all .15s;
}
.lpt-tab.active {
  background: var(--lpt-primary);
  color: #fff;
  border-color: var(--lpt-primary);
}
.lpt-tab:hover:not(.active) {
  background: #f4f5fa;
}

/* Results */
#lpt-results {
  display: none;
  margin-top: 40px;
  max-width: 900px;
  margin-left: auto;
  margin-right: auto;
}
.lpt-score-circle {
  width: 140px;
  height: 140px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
  position: relative;
}
.lpt-score-inner {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: #fff;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
.lpt-score-num {
  font-size: 2.4rem;
  font-weight: 800;
  font-family: monospace;
}
.lpt-score-label {
  font-size: .7rem;
  color: var(--lpt-soft);
}
.lpt-summary-box {
  background: #f4f5fa;
  border-radius: 16px;
  padding: 20px 24px;
  text-align: center;
  font-size: .95rem;
  margin-bottom: 24px;
  line-height: 1.5;
}

/* Category grid */
.lpt-cat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
  gap: 10px;
  margin-bottom: 28px;
}
.lpt-cat {
  background: var(--lpt-surface);
  border: 1px solid var(--lpt-border);
  border-radius: 14px;
  padding: 14px 10px;
  text-align: center;
}
.lpt-cat-bar {
  height: 6px;
  border-radius: 3px;
  background: #e5e7eb;
  margin: 8px 0 4px;
  overflow: hidden;
}
.lpt-cat-fill {
  height: 100%;
  border-radius: 3px;
  transition: width .6s ease;
}
.lpt-cat-name {
  font-size: .7rem;
  font-weight: 600;
  color: var(--lpt-soft);
  text-transform: uppercase;
  letter-spacing: .5px;
}
.lpt-cat-score {
  font-size: 1.1rem;
  font-weight: 800;
}

/* Check list */
.lpt-check {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 10px 14px;
  border-radius: 10px;
  margin-bottom: 6px;
  font-size: .85rem;
}
.lpt-check.passed { background: var(--lpt-pass-bg); }
.lpt-check.warning { background: var(--lpt-warn-bg); }
.lpt-check.failed  { background: var(--lpt-fail-bg); }
.lpt-check-icon {
  font-size: 1.1rem;
  flex-shrink: 0;
  margin-top: 1px;
}
.lpt-check.passed .lpt-check-icon { color: var(--lpt-pass); }
.lpt-check.warning .lpt-check-icon { color: var(--lpt-warn); }
.lpt-check.failed .lpt-check-icon { color: var(--lpt-fail); }
.lpt-check-body { flex: 1; }
.lpt-check-name { font-weight: 600; margin-bottom: 2px; }
.lpt-check-detail { font-size: .78rem; color: var(--lpt-soft); }
.lpt-check-rec { font-size: .78rem; color: var(--lpt-primary); margin-top: 4px; }

/* Priority fixes */
.lpt-fixes {
  display: grid;
  gap: 16px;
  margin-top: 24px;
}
.lpt-fix-section h4 {
  font-size: .9rem;
  font-weight: 700;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.lpt-fix-badge {
  font-size: .7rem;
  padding: 2px 10px;
  border-radius: 20px;
  font-weight: 700;
}
.lpt-fix-badge.critical { background: #fef2f2; color: var(--lpt-fail); }
.lpt-fix-badge.important { background: #fffbeb; color: var(--lpt-warn); }
.lpt-fix-badge.nice { background: #f0fdf4; color: var(--lpt-pass); }
.lpt-fix-list {
  list-style: none;
  padding: 0;
}
.lpt-fix-list li {
  padding: 8px 14px;
  background: var(--lpt-surface);
  border: 1px solid var(--lpt-border);
  border-radius: 8px;
  margin-bottom: 6px;
  font-size: .84rem;
  display: flex;
  align-items: flex-start;
  gap: 8px;
}
.lpt-fix-num {
  background: var(--lpt-primary);
  color: #fff;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .7rem;
  font-weight: 700;
  flex-shrink: 0;
}

/* Stats bar */
.lpt-stats {
  display: flex;
  gap: 12px;
  justify-content: center;
  margin: 16px 0 24px;
  flex-wrap: wrap;
}
.lpt-stat {
  text-align: center;
  padding: 10px 18px;
  background: var(--lpt-surface);
  border-radius: 12px;
  border: 1px solid var(--lpt-border);
  min-width: 80px;
}
.lpt-stat-val {
  font-size: 1.4rem;
  font-weight: 800;
  font-family: monospace;
}
.lpt-stat-label {
  font-size: .7rem;
  color: var(--lpt-soft);
}

/* Loading */
.lpt-loading {
  display: none;
  text-align: center;
  padding: 60px 20px;
}
.lpt-spinner {
  width: 48px;
  height: 48px;
  border: 4px solid var(--lpt-border);
  border-top-color: var(--lpt-primary);
  border-radius: 50%;
  animation: lpt-spin .8s linear infinite;
  margin: 0 auto 20px;
}
@keyframes lpt-spin { to { transform: rotate(360deg); } }

.lpt-loading-text {
  font-size: .95rem;
  color: var(--lpt-soft);
}

@media (max-width: 600px) {
  .lpt-hero h1 { font-size: 1.8rem; }
  .lpt-card { padding: 20px; }
  .lpt-cat-grid { grid-template-columns: repeat(3, 1fr); }
}
</style>

<section class="lpt-hero">
  <div class="container">
    <h1>🧪 Landing Page Tester</h1>
    <p class="lpt-sub">
      Test any landing page for visibility, design, mobile responsiveness,
      UX, performance, content, and conversion optimization.
    </p>
  </div>
</section>

<section style="padding-bottom: 80px;">
  <div class="container">

    <!-- Input Card -->
    <div class="lpt-card" id="lpt-input-card">
      <div class="lpt-tabs">
        <div class="lpt-tab active" data-tab="url">🔗 Test URL</div>
        <div class="lpt-tab" data-tab="html">📋 Paste HTML</div>
      </div>

      <!-- URL mode -->
      <div id="lpt-tab-url">
        <div style="margin-bottom: 16px;">
          <label>Landing Page URL *</label>
          <input type="url" id="lpt-url" placeholder="https://example.com/landing-page" required>
        </div>
      </div>

      <!-- HTML mode -->
      <div id="lpt-tab-html" style="display:none;">
        <div style="margin-bottom: 16px;">
          <label>Paste Full HTML Code *</label>
          <textarea id="lpt-html" placeholder="<html><head>...</head><body>..."></textarea>
        </div>
        <div style="margin-bottom: 16px;">
          <label>Page URL (for reference)</label>
          <input type="url" id="lpt-html-url" placeholder="https://example.com">
        </div>
      </div>

      <button class="lpt-submit" id="lpt-submit-btn" onclick="runTest()">
        🚀 Run Landing Page Test
      </button>
    </div>

    <!-- Loading -->
    <div class="lpt-loading" id="lpt-loading">
      <div class="lpt-spinner"></div>
      <p class="lpt-loading-text">Analyzing landing page...<br><small style="color:var(--lpt-soft)">This may take up to 15 seconds</small></p>
    </div>

    <!-- Results -->
    <div id="lpt-results">
      <div style="text-align:center; margin-bottom: 16px;">
        <p style="font-size:.82rem;color:var(--lpt-soft)">
          🔗 <strong id="lpt-result-url"></strong> &nbsp;|&nbsp;
          📅 <span id="lpt-result-date"></span> &nbsp;|&nbsp;
          ⏱️ <span id="lpt-result-time"></span>s
        </p>
      </div>

      <!-- Score circle -->
      <div class="lpt-score-circle" id="lpt-score-circle">
        <div class="lpt-score-inner">
          <span class="lpt-score-num" id="lpt-score-num">0</span>
          <span class="lpt-score-label">/100</span>
        </div>
      </div>

      <!-- Summary -->
      <div class="lpt-summary-box" id="lpt-summary"></div>

      <!-- Stats -->
      <div class="lpt-stats">
        <div class="lpt-stat">
          <div class="lpt-stat-val" style="color:var(--lpt-pass)" id="lpt-stat-pass">0</div>
          <div class="lpt-stat-label">✅ Passed</div>
        </div>
        <div class="lpt-stat">
          <div class="lpt-stat-val" style="color:var(--lpt-warn)" id="lpt-stat-warn">0</div>
          <div class="lpt-stat-label">⚠️ Warnings</div>
        </div>
        <div class="lpt-stat">
          <div class="lpt-stat-val" style="color:var(--lpt-fail)" id="lpt-stat-fail">0</div>
          <div class="lpt-stat-label">❌ Failed</div>
        </div>
      </div>

      <!-- Category scores -->
      <div class="lpt-cat-grid" id="lpt-cat-grid"></div>

      <!-- Detailed checks -->
      <div style="margin-top: 28px;">
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:16px">📋 Detailed Checks</h3>
        <div id="lpt-checks"></div>
      </div>

      <!-- Priority Fixes -->
      <div class="lpt-fixes" id="lpt-fixes"></div>
    </div>

  </div>
</section>

<script>
// ---- Tab switching ----
document.querySelectorAll('.lpt-tab').forEach(tab => {
  tab.addEventListener('click', function() {
    document.querySelectorAll('.lpt-tab').forEach(t => t.classList.remove('active'));
    this.classList.add('active');
    var mode = this.dataset.tab;
    document.getElementById('lpt-tab-url').style.display = mode === 'url' ? 'block' : 'none';
    document.getElementById('lpt-tab-html').style.display = mode === 'html' ? 'block' : 'none';
  });
});

// ---- Run Test ----
async function runTest() {
  var isHtmlMode = document.querySelector('.lpt-tab.active').dataset.tab === 'html';
  var url, html, endpoint, body;

  if (isHtmlMode) {
    html = document.getElementById('lpt-html').value.trim();
    url  = document.getElementById('lpt-html-url').value.trim() || 'https://example.com';
    if (!html) { alert('Please paste HTML code.'); return; }
    endpoint = '<?= $url('landing-tester/test-html') ?>';
    body = new URLSearchParams({ html: html, url: url });
  } else {
    url = document.getElementById('lpt-url').value.trim();
    if (!url) { alert('Please enter a URL.'); return; }
    if (!url.startsWith('http://') && !url.startsWith('https://')) {
      url = 'https://' + url;
    }
    endpoint = '<?= $url('landing-tester/test') ?>';
    body = new URLSearchParams({ url: url });
  }

  // Show loading
  document.getElementById('lpt-input-card').style.display = 'none';
  document.getElementById('lpt-results').style.display = 'none';
  document.getElementById('lpt-loading').style.display = 'block';

  try {
    var res = await fetch(endpoint, { method: 'POST', body: body });
    var data = await res.json();

    if (!data.success) {
      alert('Error: ' + (data.error || 'Unknown error'));
      document.getElementById('lpt-loading').style.display = 'none';
      document.getElementById('lpt-input-card').style.display = 'block';
      return;
    }

    renderResults(data);
    document.getElementById('lpt-loading').style.display = 'none';
    document.getElementById('lpt-results').style.display = 'block';
    document.getElementById('lpt-results').scrollIntoView({ behavior: 'smooth' });

  } catch (err) {
    alert('Network error: ' + err.message);
    document.getElementById('lpt-loading').style.display = 'none';
    document.getElementById('lpt-input-card').style.display = 'block';
  }
}

// ---- Render Results ----
function renderResults(data) {
  // Meta
  document.getElementById('lpt-result-url').textContent = data.url;
  document.getElementById('lpt-result-date').textContent = data.date;
  document.getElementById('lpt-result-time').textContent = data.test_time;

  // Animated score
  animateScore(data.overall_score);

  // Stats
  document.getElementById('lpt-stat-pass').textContent = data.counts.passed;
  document.getElementById('lpt-stat-warn').textContent = data.counts.warnings;
  document.getElementById('lpt-stat-fail').textContent = data.counts.failed;

  // Summary
  document.getElementById('lpt-summary').textContent = data.summary;

  // Category grid
  var cats = data.categories;
  var catNames = {
    render: 'Render', technical: 'Technical', design: 'Design & Layout',
    mobile: 'Mobile', performance: 'Performance', ux: 'UX & Conversion', content: 'Content'
  };
  var catColors = { 80: '#10b981', 60: '#f59e0b', 0: '#ef4444' };
  var catHtml = '';
  for (var [key, name] of Object.entries(catNames)) {
    var score = cats[key] || 0;
    var color = score >= 80 ? catColors[80] : score >= 60 ? catColors[60] : catColors[0];
    catHtml += '<div class="lpt-cat">' +
      '<div class="lpt-cat-name">' + name + '</div>' +
      '<div class="lpt-cat-score" style="color:' + color + '">' + score + '</div>' +
      '<div class="lpt-cat-bar"><div class="lpt-cat-fill" style="width:' + score + '%;background:' + color + '"></div></div>' +
    '</div>';
  }
  document.getElementById('lpt-cat-grid').innerHTML = catHtml;

  // Detailed checks
  var checksHtml = '';
  var checks = data.checks || [];
  if (checks.length === 0) {
    checksHtml = '<p style="color:var(--lpt-soft)">No detailed checks available.</p>';
  } else {
    for (var c of checks) {
      var icon = c.status === 'passed' ? '✅' : c.status === 'warning' ? '⚠️' : '❌';
      checksHtml += '<div class="lpt-check ' + c.status + '">' +
        '<span class="lpt-check-icon">' + icon + '</span>' +
        '<div class="lpt-check-body">' +
          '<div class="lpt-check-name">[' + (c.category || '') + '] ' + (c.check || '') + '</div>' +
          (c.detail ? '<div class="lpt-check-detail">' + c.detail + '</div>' : '') +
          (c.recommendation ? '<div class="lpt-check-rec">💡 ' + c.recommendation + '</div>' : '') +
        '</div>' +
      '</div>';
    }
  }
  document.getElementById('lpt-checks').innerHTML = checksHtml;

  // Priority fixes
  var fixesHtml = '';
  if (data.critical_fixes && data.critical_fixes.length > 0) {
    fixesHtml += '<div class="lpt-fix-section"><h4><span class="lpt-fix-badge critical">CRITICAL</span> Fix Immediately</h4><ol class="lpt-fix-list">';
    data.critical_fixes.forEach(function(f, i) {
      fixesHtml += '<li><span class="lpt-fix-num">' + (i+1) + '</span>' + f + '</li>';
    });
    fixesHtml += '</ol></div>';
  }
  if (data.important_fixes && data.important_fixes.length > 0) {
    fixesHtml += '<div class="lpt-fix-section"><h4><span class="lpt-fix-badge important">IMPORTANT</span> Fix Soon</h4><ol class="lpt-fix-list">';
    data.important_fixes.forEach(function(f, i) {
      fixesHtml += '<li><span class="lpt-fix-num">' + (i+1) + '</span>' + f + '</li>';
    });
    fixesHtml += '</ol></div>';
  }
  if (data.nice_fixes && data.nice_fixes.length > 0) {
    fixesHtml += '<div class="lpt-fix-section"><h4><span class="lpt-fix-badge nice">NICE-TO-HAVE</span> Optional Improvements</h4><ol class="lpt-fix-list">';
    data.nice_fixes.forEach(function(f, i) {
      fixesHtml += '<li><span class="lpt-fix-num">' + (i+1) + '</span>' + f + '</li>';
    });
    fixesHtml += '</ol></div>';
  }
  document.getElementById('lpt-fixes').innerHTML = fixesHtml || '<p style="color:var(--lpt-soft);text-align:center">No actionable fixes suggested.</p>';
}

function animateScore(target) {
  var el = document.getElementById('lpt-score-num');
  var circle = document.getElementById('lpt-score-circle');
  var current = 0;
  var step = Math.max(1, Math.floor(target / 40));
  var color = target >= 80 ? '#10b981' : target >= 60 ? '#f59e0b' : '#ef4444';

  var iv = setInterval(function() {
    current = Math.min(target, current + step);
    el.textContent = current;
    circle.style.background = 'conic-gradient(' + color + ' ' + (current * 3.6) + 'deg, #e5e7eb 0deg)';
    if (current >= target) clearInterval(iv);
  }, 25);
}
</script>

<?php $content = ob_get_clean(); include __DIR__ . '/../partials/layout.php'; ?>
