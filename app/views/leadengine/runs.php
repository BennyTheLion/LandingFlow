<?php ob_start() ?>
<style>
.le-page{max-width:1100px}
.le-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px}
.le-head h1{margin:0;font-size:1.5rem}
.le-nav{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px}
.le-nav a{padding:7px 14px;border-radius:8px;font-size:.82rem;font-weight:600;text-decoration:none;background:var(--surface);border:1px solid var(--border);color:var(--ink-soft)}
.le-nav a.active,.le-nav a:hover{background:var(--primary);color:#fff;border-color:var(--primary)}
.flash{padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:.85rem}
.flash.success{background:#dcfce7;color:#166534}.flash.error{background:#fef2f2;color:#991b1b}
.btn{padding:7px 15px;border-radius:8px;border:none;cursor:pointer;font-family:inherit;font-size:.82rem;font-weight:600}
.btn-primary{background:var(--primary);color:#fff}
.btn-ghost{background:var(--surface);border:1px solid var(--border);color:var(--ink-soft)}
.toolbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:18px}
.toolbar form{margin:0;display:flex;gap:8px;align-items:center}
.run{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:16px;margin-bottom:12px}
.run.has-errors{border-color:#fecaca}
.run-top{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:10px}
.run-when{font-weight:700;font-size:.9rem}
.run-when small{font-weight:400;color:var(--ink-soft);font-size:.76rem}
.counters{display:flex;gap:8px;flex-wrap:wrap}
.counter{background:var(--surface-2);border-radius:7px;padding:5px 11px;font-size:.75rem}
.counter b{font-family:var(--font-mono);font-weight:700}
.counter.err{background:rgba(220,38,38,.1);color:var(--danger)}
.counter.ok{background:rgba(22,163,74,.1);color:var(--success)}
details{margin-top:10px}
summary{cursor:pointer;font-size:.79rem;color:var(--primary);font-weight:600}
.log{background:#111827;color:#E5E7EB;border-radius:8px;padding:12px;font-family:var(--font-mono);font-size:.71rem;line-height:1.8;white-space:pre-wrap;max-height:340px;overflow:auto;margin-top:8px;direction:ltr;text-align:left}
.empty{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:44px;text-align:center;color:var(--ink-soft)}
.cron-box{background:var(--surface-2);border:1px solid var(--border);border-radius:10px;padding:14px;font-size:.78rem;line-height:1.85;margin-top:20px}
.cron-box code{background:var(--surface);padding:2px 6px;border-radius:4px;font-family:var(--font-mono);font-size:.73rem;direction:ltr;display:inline-block}
</style>

<div class="le-page">
<div class="le-head"><h1>⚙️ הרצות</h1></div>

<div class="le-nav">
  <a href="<?= $url('admin/lead-engine') ?>">דשבורד</a>
  <a href="<?= $url('admin/lead-engine/queue') ?>">תור אישורים</a>
  <a href="<?= $url('admin/lead-engine/prospects') ?>">כל הלידים</a>
  <a href="<?= $url('admin/lead-engine/runs') ?>" class="active">הרצות</a>
  <a href="<?= $url('admin/lead-engine/settings') ?>">הגדרות</a>
</div>

<?php if (!empty($flashMsg)): ?>
  <div class="flash <?= htmlspecialchars($flashMsg['type']) ?>"><?= htmlspecialchars($flashMsg['message']) ?></div>
<?php endif; ?>

<div class="toolbar">
  <form method="POST" action="<?= $url('admin/lead-engine/run') ?>">
    <?= $csrf() ?>
    <label style="font-size:.8rem;color:var(--ink-soft)"><input type="checkbox" name="with_sourcing" value="1"> כולל איסוף מ-Places</label>
    <button type="submit" class="btn btn-primary">▶ הרץ צינור עכשיו</button>
  </form>
  <form method="POST" action="<?= $url('admin/lead-engine/digest') ?>">
    <?= $csrf() ?>
    <button type="submit" class="btn btn-ghost">✉ שלח מייל אישורים עכשיו</button>
  </form>
</div>

<?php if (empty($runs)): ?>
  <div class="empty">טרם בוצעה הרצה. לחץ "הרץ צינור עכשיו", או הגדר את ה-cron למטה.</div>
<?php endif; ?>

<?php foreach ($runs as $run):
  $errors = (int) $run['errors'];
  $log = [];
  if (!empty($run['log_json'])) {
    $decoded = json_decode((string) $run['log_json'], true);
    if (is_array($decoded)) $log = $decoded;
  }
  $duration = $run['finished_at']
    ? max(0, strtotime((string) $run['finished_at']) - strtotime((string) $run['started_at']))
    : null;
?>
<div class="run <?= $errors > 0 ? 'has-errors' : '' ?>">
  <div class="run-top">
    <div class="run-when">
      <?= date('d/m/Y H:i', strtotime((string) $run['started_at'])) ?>
      <small>
        · <?= $run['trigger'] === 'cron' ? 'אוטומטי (cron)' : 'ידני' ?>
        <?php if ($duration !== null): ?> · <?= $duration ?> שנ'<?php else: ?> · <span style="color:var(--warning)">לא הושלמה</span><?php endif; ?>
      </small>
    </div>
    <div class="counters">
      <span class="counter">נאספו <b><?= (int) $run['sourced'] ?></b></span>
      <span class="counter">כפולים <b><?= (int) $run['skipped_duplicate'] ?></b></span>
      <span class="counter">חסומים <b><?= (int) $run['skipped_dnc'] ?></b></span>
      <span class="counter">נבדקו <b><?= (int) $run['audited'] ?></b></span>
      <span class="counter">מתחת לסף <b><?= (int) $run['below_threshold'] ?></b></span>
      <span class="counter">הועשרו <b><?= (int) $run['enriched'] ?></b></span>
      <span class="counter ok">טיוטות <b><?= (int) $run['drafted'] ?></b></span>
      <?php if ($errors > 0): ?><span class="counter err">שגיאות <b><?= $errors ?></b></span><?php endif; ?>
    </div>
  </div>

  <?php if (!empty($log)): ?>
    <details>
      <summary>לוג מפורט (<?= count($log) ?> רשומות) — מה דולג ולמה</summary>
      <div class="log"><?= htmlspecialchars(json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></div>
    </details>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<div class="cron-box">
  <strong>תזמון אוטומטי</strong> — הוסף ל-crontab (§3, §9):<br><br>
  צינור שבועי, יום א' 06:00:<br>
  <code>0 6 * * 0 php <?= htmlspecialchars(BASE_PATH) ?>/bin/lead-engine-pipeline.php</code><br><br>
  מייל אישורים יומי, א'–ה' 08:00:<br>
  <code>0 8 * * 0-4 php <?= htmlspecialchars(BASE_PATH) ?>/bin/lead-engine-digest.php</code><br><br>
  <span style="color:var(--ink-soft)">שני הסקריפטים לא שולחים דבר לנמענים — הצינור עוצר בטיוטה, והמייל היומי נשלח אליך בלבד.</span>
</div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../admin/layout.php'; ?>
