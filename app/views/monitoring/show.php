<?php ob_start() ?>
<?php
// Read flash messages
$flashMsg = null;
if ($msg = \App\Core\Session::flash('success')) { $flashMsg = ['type' => 'success', 'message' => $msg]; }
elseif ($msg = \App\Core\Session::flash('error')) { $flashMsg = ['type' => 'error', 'message' => $msg]; }
?>
<?php if($flashMsg): ?><div class="flash" style="padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:.85rem;background:<?= $flashMsg['type']==='success'?'#dcfce7':'#fef2f2' ?>;color:<?= $flashMsg['type']==='success'?'#166534':'#991b1b' ?>"><?= htmlspecialchars($flashMsg['message']) ?></div><?php endif; ?>
<div class="top-bar"><h1><?= htmlspecialchars($site["name"]) ?></h1><a href="<?= $url("admin/monitoring") ?>" class="btn btn-ghost">← חזרה</a></div>
<style>.btn{display:inline-flex;align-items:center;gap:6px;font-weight:700;font-size:.88rem;border-radius:10px;padding:10px 20px;border:none;font-family:inherit;cursor:pointer;white-space:nowrap;text-decoration:none}.btn-ghost{background:transparent;border:1px solid var(--border);color:var(--ink)}.detail-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:22px;margin-bottom:18px}.detail-row{display:flex;justify-content:space-between;padding:8px 0;font-size:.85rem;border-bottom:1px solid var(--border)}.detail-label{color:var(--ink-soft)}.detail-value{font-weight:600}</style>
<div class="detail-card"><div class="detail-row"><span class="detail-label">URL</span><span class="detail-value" style="direction:ltr"><?= htmlspecialchars($site["url"]) ?></span></div><div class="detail-row"><span class="detail-label">סטטוס</span><span class="detail-value"><?= $site["status"] ?></span></div><div class="detail-row"><span class="detail-label">% uptime</span><span class="detail-value"><?= $site["uptime_percentage"] ?>%</span></div><div class="detail-row"><span class="detail-label">זמן תגובה</span><span class="detail-value"><?= $site["response_time_ms"] ?? "-" ?>ms</span></div><div class="detail-row"><span class="detail-label">בדיקה אחרונה</span><span class="detail-value"><?= $site["last_checked_at"] ?? "טרם נבדק" ?></span></div></div>
<div class="detail-card"><h3 style="margin-bottom:12px">לוג בדיקות אחרונות</h3>
  <?php if($logs): foreach($logs as $l): ?>
  <div class="detail-row"><span><?= $l["checked_at"] ?></span><span style="color:<?= $l["status"]==="up"?"var(--success)":"var(--danger)" ?>"><?= $l["status"]==="up"?"▲":"▼" ?></span><span><?= $l["response_time_ms"] ?>ms</span></div>
  <?php endforeach; else: ?><p style="color:var(--ink-soft);font-size:.84rem">אין בדיקות להצגה.</p><?php endif; ?>
</div>
<?php $content=ob_get_clean(); include __DIR__."/../admin/layout.php"; ?>
