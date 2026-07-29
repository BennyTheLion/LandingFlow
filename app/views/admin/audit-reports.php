<?php ob_start() ?>
<style>
.audit-page{max-width:1100px}
.search-bar{display:flex;gap:10px;margin-bottom:20px}
.search-bar input{padding:8px 14px;border:1px solid var(--border);border-radius:8px;font-size:.9rem;flex:1;max-width:400px;font-family:inherit}
.search-bar button{padding:8px 16px;border-radius:8px;background:var(--primary);color:#fff;border:none;cursor:pointer;font-family:inherit}
.table-wrap{background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden}
.table-row{display:grid;grid-template-columns:.4fr 2fr .7fr .8fr .7fr;gap:12px;align-items:center;padding:12px 18px;border-bottom:1px solid var(--border);font-size:.85rem;cursor:pointer;transition:.15s}
.table-row:hover{background:var(--surface-2)}
.table-row.head{background:var(--surface-2);font-weight:700;color:var(--ink-soft);font-size:.76rem;cursor:default}
.table-row.head:hover{background:var(--surface-2)}
.row-active{background:rgba(37,99,235,.06)!important}
.btn-sm{padding:5px 12px;font-size:.75rem;border-radius:6px;border:1px solid var(--border);background:var(--surface);cursor:pointer;color:var(--ink-soft);transition:.15s;font-family:inherit}
.btn-sm:hover{background:var(--danger);color:#fff;border-color:var(--danger)}
.flash{padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:.9rem}
.flash.success{background:#dcfce7;color:#166534}
.flash.error{background:#fef2f2;color:#991b1b}

/* DETAIL PANEL */
.detail-overlay{display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.35);align-items:center;justify-content:center}
.detail-overlay.open{display:flex}
.detail-panel{background:var(--surface);border-radius:16px;width:90%;max-width:750px;max-height:85vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);padding:0}
.detail-head{display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--surface);z-index:1;border-radius:16px 16px 0 0}
.detail-head h3{margin:0;font-size:1.1rem}
.detail-close{width:36px;height:36px;border-radius:8px;background:var(--surface-2);border:none;cursor:pointer;font-size:1.1rem;display:flex;align-items:center;justify-content:center;transition:.15s;color:var(--ink-soft)}
.detail-close:hover{background:var(--danger);color:#fff}
.detail-body{padding:24px}
.detail-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px}
.detail-kpi{text-align:center;padding:16px;background:var(--surface-2);border-radius:10px}
.detail-kpi .val{font-size:1.5rem;font-weight:800;font-family:var(--font-mono)}
.detail-kpi .lbl{font-size:.72rem;color:var(--ink-soft);margin-top:4px}
.detail-score{text-align:center;margin-bottom:24px}
.detail-score .big{font-size:3rem;font-weight:900}
.detail-rec{margin-top:16px}
.detail-rec h4{margin-bottom:12px}
.detail-rec-item{padding:8px 0;border-bottom:1px solid var(--border);font-size:.85rem;color:var(--ink-soft)}
.empty{text-align:center;padding:40px;color:var(--ink-faint)}
</style>

<div class="audit-page">
<div class="top-bar"><h1>דוחות ביקורת</h1></div>

<?php if ($flashMsg = $flash('flash')): ?>
<div class="flash <?= $flashMsg['type'] ?>"><?= htmlspecialchars($flashMsg['message']) ?></div>
<?php endif; ?>

<form method="GET" class="search-bar">
  <input type="text" name="q" placeholder="חיפוש לפי URL או מספר דוח..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
  <button type="submit">🔍 חיפוש</button>
  <?php if (!empty($_GET['q'])): ?>
  <a href="<?= $url('admin/audit-reports') ?>" style="padding:8px 14px;color:var(--ink-soft);font-size:.9rem;text-decoration:none">✕ נקה</a>
  <?php endif; ?>
</form>

<div class="table-wrap">
  <div class="table-row head"><span>ID</span><span>URL</span><span>ציון</span><span>תאריך</span><span></span></div>
  <?php foreach ($reports as $r): ?>
  <div class="table-row" data-id="<?= $r['id'] ?>" onclick="openDetail(<?= $r['id'] ?>)">
    <span style="color:var(--primary);font-weight:600">#<?= $r['id'] ?></span>
    <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($r['url']) ?></span>
    <span><strong style="color:<?= $r['overall_score'] >= 80 ? 'var(--success)' : ($r['overall_score'] >= 60 ? 'var(--warning)' : 'var(--danger)') ?>"><?= $r['overall_score'] ?>/100</strong></span>
    <span style="color:var(--ink-soft);font-size:.8rem"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></span>
    <span onclick="event.stopPropagation()">
      <button class="btn-sm" onclick="deleteReport(<?= $r['id'] ?>)" title="מחק">🗑️</button>
    </span>
  </div>
  <?php endforeach; ?>
  <?php if (empty($reports)): ?>
  <div class="empty">אין דוחות להצגה.</div>
  <?php endif; ?>
</div>
</div>

<!-- DETAIL OVERLAY -->
<div class="detail-overlay" id="detailOverlay" onclick="if(event.target===this)closeDetail()">
  <div class="detail-panel">
    <div class="detail-head">
      <h3 id="detailTitle">דוח ביקורת</h3>
      <button class="detail-close" onclick="closeDetail()">✕</button>
    </div>
    <div class="detail-body" id="detailBody"></div>
  </div>
</div>

<script>
function openDetail(id){
  document.getElementById('detailOverlay').classList.add('open');
  document.getElementById('detailBody').innerHTML='<div style="text-align:center;padding:40px;color:var(--ink-soft)">⏳ טוען...</div>';
  document.querySelectorAll('.table-row').forEach(r=>r.classList.remove('row-active'));
  document.querySelector('.table-row[data-id="'+id+'"]')?.classList.add('row-active');

  fetch('<?= $url('admin/audit-reports') ?>/'+id+'/detail')
    .then(r=>r.json())
    .then(d=>{
      var scoreColor = d.overall_score >= 80 ? 'var(--success)' : (d.overall_score >= 60 ? 'var(--warning)' : 'var(--danger)');
      var recs='';
      if(d.recommendations && d.recommendations.length){
        recs='<div class="detail-rec"><h4>המלצות</h4>';
        d.recommendations.forEach(function(rec){
          var txt = typeof rec==='string' ? rec : (rec.action||rec.check||'');
          recs += '<div class="detail-rec-item">'+txt+'</div>';
        });
        recs+='</div>';
      }
      document.getElementById('detailTitle').textContent = 'דוח #'+d.id+' — '+d.url;
      document.getElementById('detailBody').innerHTML =
        '<div class="detail-score"><div class="big" style="color:'+scoreColor+'">'+d.overall_score+'/100</div><div style="color:var(--ink-soft);font-size:.85rem">ציון כללי</div></div>'+
        '<div class="detail-kpis">'+
          '<div class="detail-kpi"><div class="val">'+(d.seo_score||'-')+'</div><div class="lbl">SEO</div></div>'+
          '<div class="detail-kpi"><div class="val">'+(d.security_score||'-')+'</div><div class="lbl">אבטחה</div></div>'+
          '<div class="detail-kpi"><div class="val">'+(d.legal_score||'-')+'</div><div class="lbl">משפטי</div></div>'+
          '<div class="detail-kpi"><div class="val">'+(d.accessibility_score||'-')+'</div><div class="lbl">נגישות</div></div>'+
        '</div>'+
        '<p style="color:var(--ink-faint);font-size:.8rem">נסרק: '+d.created_at+'</p>'+
        recs;
    }).catch(function(){
      document.getElementById('detailBody').innerHTML='<div style="text-align:center;padding:40px;color:var(--danger)">שגיאה בטעינת הדוח</div>';
    });
}

function closeDetail(){
  document.getElementById('detailOverlay').classList.remove('open');
}

function deleteReport(id){
  if(!confirm('האם למחוק את הדוח #'+id+'?')) return;
  window.location.href = '<?= $url('admin/audit-reports') ?>/'+id+'/delete';
}

// ESC to close
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeDetail()});
</script>

<?php $content = ob_get_clean(); include __DIR__ . '/layout.php'; ?>
