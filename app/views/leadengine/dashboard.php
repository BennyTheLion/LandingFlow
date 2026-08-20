<?php ob_start() ?>
<style>
.le-page{max-width:1200px}
.le-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.le-head h1{margin:0;font-size:1.5rem}
.le-nav{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:24px}
.le-nav a{padding:7px 14px;border-radius:8px;font-size:.82rem;font-weight:600;text-decoration:none;background:var(--surface);border:1px solid var(--border);color:var(--ink-soft)}
.le-nav a.active,.le-nav a:hover{background:var(--primary);color:#fff;border-color:var(--primary)}
.flash{padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:.85rem}
.flash.success{background:#dcfce7;color:#166534}.flash.error{background:#fef2f2;color:#991b1b}
.halt-banner{display:flex;align-items:center;justify-content:space-between;gap:12px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:.86rem;font-weight:600;flex-wrap:wrap}
.halt-banner form{margin:0}
.btn{padding:7px 16px;border-radius:8px;border:none;cursor:pointer;font-family:inherit;font-size:.82rem;font-weight:600}
.btn-primary{background:var(--primary);color:#fff}
.btn-danger{background:var(--danger);color:#fff}
.btn-ghost{background:var(--surface);border:1px solid var(--border);color:var(--ink-soft)}
.le-kpis{display:grid;grid-template-columns:repeat(2,1fr);gap:14px;margin-bottom:24px}
@media(min-width:900px){.le-kpis{grid-template-columns:repeat(4,1fr)}}
.kpi{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px}
.kpi.hot{border-color:var(--primary);box-shadow:0 0 0 1px rgba(37,99,235,.15)}
.kpi-label{font-size:.76rem;color:var(--ink-soft);margin-bottom:6px}
.kpi-value{font-family:var(--font-mono);font-size:1.7rem;font-weight:700;line-height:1.1}
.kpi-note{font-size:.72rem;color:var(--ink-faint);margin-top:4px}
.le-cols{display:grid;grid-template-columns:1fr;gap:18px}
@media(min-width:900px){.le-cols{grid-template-columns:1fr 1fr}}
.panel{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px}
.panel h3{font-size:.92rem;font-weight:700;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--border)}
.row{display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border);font-size:.84rem}
.row:last-child{border:none}
.row .num{font-family:var(--font-mono);font-weight:700}
.mini{width:100%;border-collapse:collapse;font-size:.8rem}
.mini th{text-align:right;color:var(--ink-soft);font-size:.7rem;text-transform:uppercase;padding:6px 8px;border-bottom:1px solid var(--border)}
.mini td{padding:6px 8px;border-bottom:1px solid var(--border)}
.mini tr:last-child td{border:none}
.empty{color:var(--ink-faint);font-size:.82rem;padding:14px 0;text-align:center}
.pill{display:inline-block;padding:2px 9px;border-radius:100px;font-size:.7rem;font-weight:700}
.pill-ok{background:rgba(22,163,74,.12);color:var(--success)}
.pill-warn{background:rgba(245,158,11,.14);color:#92400e}
</style>

<div class="le-page">
<div class="le-head"><h1>🎯 מנוע לידים</h1>
  <form method="POST" action="<?= $url('admin/lead-engine/run') ?>" style="display:flex;gap:8px;align-items:center">
    <?= $csrf() ?>
    <label style="font-size:.8rem;color:var(--ink-soft)"><input type="checkbox" name="with_sourcing" value="1"> כולל איסוף</label>
    <button type="submit" class="btn btn-primary">▶ הרץ צינור</button>
  </form>
</div>

<div class="le-nav">
  <a href="<?= $url('admin/lead-engine') ?>" class="active">דשבורד</a>
  <a href="<?= $url('admin/lead-engine/queue') ?>">תור אישורים<?= $pendingQueue > 0 ? ' (' . $pendingQueue . ')' : '' ?></a>
  <a href="<?= $url('admin/lead-engine/prospects') ?>">כל הלידים</a>
  <a href="<?= $url('admin/lead-engine/runs') ?>">הרצות</a>
  <a href="<?= $url('admin/lead-engine/settings') ?>">הגדרות</a>
</div>

<?php if (!empty($flashMsg)): ?>
  <div class="flash <?= htmlspecialchars($flashMsg['type']) ?>"><?= htmlspecialchars($flashMsg['message']) ?></div>
<?php endif; ?>

<div class="halt-banner" style="<?= $sendingHalted ? '' : 'background:#f0fdf4;border-color:#bbf7d0;color:#166534' ?>">
  <span><?= $sendingHalted ? '⛔ כל השליחות מוקפאות כרגע.' : '✅ השליחות פעילות.' ?></span>
  <form method="POST" action="<?= $url('admin/lead-engine/halt') ?>">
    <?= $csrf() ?>
    <button type="submit" class="btn <?= $sendingHalted ? 'btn-primary' : 'btn-danger' ?>"
      onclick="return confirm('<?= $sendingHalted ? 'להפעיל מחדש את השליחות?' : 'להקפיא את כל השליחות?' ?>')">
      <?= $sendingHalted ? 'הפעל מחדש' : '⛔ עצור הכל' ?>
    </button>
  </form>
</div>

<?php
$statusLabels = [
  'new' => 'חדשים', 'audited' => 'נבדקו', 'enriched' => 'הועשרו', 'drafted' => 'טיוטה',
  'approved' => 'אושרו', 'sent' => 'נשלחו', 'replied' => 'הגיבו', 'closed' => 'נסגרו',
  'rejected' => 'נדחו', 'do_not_contact' => 'חסומים',
];
$totalProspects = array_sum($prospectCounts);
$sentCount = $prospectCounts['sent'] ?? 0;
$repliedCount = $prospectCounts['replied'] ?? 0;
$replyRate = $sentCount + $repliedCount > 0 ? round($repliedCount / ($sentCount + $repliedCount) * 100) : 0;
?>

<div class="le-kpis">
  <div class="kpi hot">
    <div class="kpi-label">ממתינים לאישור</div>
    <div class="kpi-value"><?= (int) $pendingQueue ?></div>
    <div class="kpi-note"><a href="<?= $url('admin/lead-engine/queue') ?>">פתח את התור →</a></div>
  </div>
  <div class="kpi">
    <div class="kpi-label">נשלחו השבוע</div>
    <div class="kpi-value"><?= (int) $sentThisWeek ?></div>
    <div class="kpi-note">היום: <?= (int) $sentToday ?> / <?= (int) $maxDaily ?></div>
  </div>
  <div class="kpi">
    <div class="kpi-label">תגובות</div>
    <div class="kpi-value"><?= $repliedCount ?></div>
    <div class="kpi-note">שיעור תגובה <?= $replyRate ?>% (יעד: 15%)</div>
  </div>
  <div class="kpi">
    <div class="kpi-label">סה"כ לידים במערכת</div>
    <div class="kpi-value"><?= $totalProspects ?></div>
    <div class="kpi-note"><?= (int) $dncCount ?> ברשימת חסימה</div>
  </div>
</div>

<div class="le-cols">
  <div class="panel">
    <h3>לידים לפי שלב בצינור</h3>
    <?php foreach ($statusLabels as $key => $label): $n = $prospectCounts[$key] ?? 0; ?>
      <div class="row">
        <span><?= $label ?></span>
        <span class="num" style="<?= $n === 0 ? 'color:var(--ink-faint)' : '' ?>">
          <?php if ($n > 0): ?>
            <a href="<?= $url('admin/lead-engine/prospects?status=' . $key) ?>"><?= $n ?></a>
          <?php else: ?>0<?php endif; ?>
        </span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="panel">
    <h3>שיעור תגובה לפי מקור</h3>
    <?php if (empty($replyBySource)): ?>
      <div class="empty">אין עוד נתונים — יופיעו אחרי השליחות הראשונות.</div>
    <?php else: ?>
      <table class="mini">
        <thead><tr><th>מקור</th><th>נשלחו</th><th>הגיבו</th><th>שיעור</th></tr></thead>
        <tbody>
        <?php foreach ($replyBySource as $r):
          $sent = (int) $r['sent']; $rep = (int) $r['replied'];
          $rate = $sent > 0 ? round($rep / $sent * 100) : 0; ?>
          <tr>
            <td><?= htmlspecialchars((string) ($r['bucket'] ?? '—')) ?></td>
            <td><?= $sent ?></td><td><?= $rep ?></td>
            <td><span class="pill <?= $rate >= 15 ? 'pill-ok' : 'pill-warn' ?>"><?= $rate ?>%</span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="panel">
    <h3>שיעור תגובה לפי נישה</h3>
    <?php if (empty($replyByNiche)): ?>
      <div class="empty">אין עוד נתונים.</div>
    <?php else: ?>
      <table class="mini">
        <thead><tr><th>נישה</th><th>נשלחו</th><th>הגיבו</th><th>שיעור</th></tr></thead>
        <tbody>
        <?php foreach ($replyByNiche as $r):
          $sent = (int) $r['sent']; $rep = (int) $r['replied'];
          $rate = $sent > 0 ? round($rep / $sent * 100) : 0; ?>
          <tr>
            <td><?= htmlspecialchars((string) ($r['bucket'] ?? '—')) ?></td>
            <td><?= $sent ?></td><td><?= $rep ?></td>
            <td><span class="pill <?= $rate >= 15 ? 'pill-ok' : 'pill-warn' ?>"><?= $rate ?>%</span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="panel">
    <h3>הרצות אחרונות</h3>
    <?php if (empty($lastRuns)): ?>
      <div class="empty">טרם בוצעה הרצה.</div>
    <?php else: ?>
      <table class="mini">
        <thead><tr><th>מתי</th><th>סוג</th><th>נאספו</th><th>נבדקו</th><th>טיוטות</th></tr></thead>
        <tbody>
        <?php foreach ($lastRuns as $run): ?>
          <tr>
            <td><?= date('d/m H:i', strtotime((string) $run['started_at'])) ?></td>
            <td><?= $run['trigger'] === 'cron' ? 'אוטומטי' : 'ידני' ?></td>
            <td><?= (int) $run['sourced'] ?></td>
            <td><?= (int) $run['audited'] ?></td>
            <td><?= (int) $run['drafted'] ?><?= (int) $run['errors'] > 0 ? ' <span style="color:var(--danger)">⚠</span>' : '' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <div style="margin-top:12px"><a href="<?= $url('admin/lead-engine/runs') ?>" class="btn btn-ghost">כל ההרצות →</a></div>
    <?php endif; ?>
  </div>
</div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../admin/layout.php'; ?>
