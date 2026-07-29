<?php ob_start() ?>
<style>
.monitor-page{max-width:1200px}
.top-bar{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.top-bar h1{margin:0;font-size:1.5rem}
.add-form{display:flex;gap:8px;flex-wrap:wrap}
.add-form input,.add-form select{padding:6px 10px;border:1px solid var(--border);border-radius:6px;font-size:.82rem;font-family:inherit}
.add-form button{padding:6px 14px;border-radius:6px;background:var(--primary);color:#fff;border:none;cursor:pointer;font-size:.82rem}
.toolbar{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;align-items:center}
.toolbar .btn{font-size:.8rem;padding:6px 14px}
.table-wrap{background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;overflow-x:auto}
.tbl{width:100%;border-collapse:collapse;font-size:.84rem;min-width:900px}
.tbl th{background:var(--surface-2);color:var(--ink-soft);font-weight:700;font-size:.72rem;text-transform:uppercase;padding:10px 12px;text-align:right}
.tbl td{padding:10px 12px;border-bottom:1px solid var(--border);vertical-align:middle}
.tbl tr:hover td{background:var(--surface-2)}
.tbl tr.demo td{background:rgba(37,99,235,.03)}
.tbl tr.demo:hover td{background:rgba(37,99,235,.06)}
.dot{width:7px;height:7px;border-radius:50%;display:inline-block;margin-left:4px}
.dot-online{background:var(--success)}.dot-issues{background:var(--warning)}.dot-offline,.dot-error{background:var(--danger)}
.badge{display:inline-block;padding:2px 8px;border-radius:100px;font-size:.7rem;font-weight:700}
.badge-demo{background:rgba(37,99,235,.1);color:var(--primary)}
.badge-monitored{background:rgba(22,163,74,.1);color:var(--success)}
.btn-sm{padding:4px 10px;font-size:.7rem;border-radius:4px;border:1px solid var(--border);background:var(--surface);cursor:pointer;font-family:inherit;color:var(--ink-soft);transition:.15s;text-decoration:none;display:inline-block}
.btn-sm:hover{background:var(--primary);color:#fff;border-color:var(--primary)}
.btn-sol{background:rgba(37,99,235,.1);color:var(--primary);border-color:var(--primary)}
.btn-sol:hover{background:var(--primary);color:#fff}
.score{font-weight:700;font-family:var(--font-mono)}.score.good{color:var(--success)}.score.warn{color:var(--warning)}.score.bad{color:var(--danger)}
.flash{padding:8px 14px;border-radius:8px;margin-bottom:16px;font-size:.85rem}
.flash.success{background:#dcfce7;color:#166534}.flash.error{background:#fef2f2;color:#991b1b}
.empty{text-align:center;padding:40px;color:var(--ink-faint)}
</style>
<div class="monitor-page">
<div class="top-bar"><h1>📡 ניטור אתרים</h1>
<form method="POST" action="<?= $url('admin/monitoring/add') ?>" class="add-form">
<?= $csrf() ?><input type="text" name="name" placeholder="שם האתר" required><input type="url" name="url" placeholder="https://..." required>
<select name="check_interval"><option value="5">5 דק'</option><option value="15">15 דק'</option><option value="30">30 דק'</option><option value="60" selected>60 דק'</option></select>
<button type="submit">+ הוסף</button>
</form>
</div>

<?php if (!empty($flashMsg)): ?><div class="flash <?= htmlspecialchars($flashMsg['type']) ?>"><?= htmlspecialchars($flashMsg['message']) ?></div><?php endif; ?>

<div class="toolbar"><span style="font-size:.82rem;color:var(--ink-soft);margin-left:auto">
<?= count($sites) ?> אתרים &middot;
<span style="color:var(--success)"><?= count(array_filter($sites, fn($s)=>($s['status']??'')==='online')) ?> פעילים</span> &middot;
<span style="color:var(--warning)"><?= count(array_filter($sites, fn($s)=>($s['status']??'')==='issues')) ?> בעיות</span> &middot;
<span style="color:var(--danger)"><?= count(array_filter($sites, fn($s)=>in_array($s['status']??'',['offline','error']))) ?> לא זמינים</span>
</span></div>

<div class="table-wrap"><table class="tbl">
<thead><tr><th>שם</th><th>URL</th><th>סטטוס</th><th>זמן תגובה</th><th>SSL</th><th>SEO</th><th>אבטחה</th><th>בדיקה אחרונה</th><th>פעולות</th></tr></thead>
<tbody>
<?php foreach ($sites as $s): ?>
<tr class="<?= ($s['source']??'')==='demo'?'demo':'' ?>">
<td><strong><?= htmlspecialchars($s['name']) ?></strong>
<?php if (($s['source']??'')==='demo'): ?><span class="badge badge-demo">דמו</span><?php else: ?><span class="badge badge-monitored">מנוטר</span><?php endif; ?></td>
<td style="direction:ltr;text-align:left;font-size:.78rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($s['url']) ?></td>
<td><span class="dot dot-<?= $s['status'] ?>"></span> <?= $s['status'] ?></td>
<td><?= isset($s['response_time_ms']) ? $s['response_time_ms'].'ms' : '-' ?></td>
<td><?= ($s['ssl_valid']??null)===true?'🔒':(($s['ssl_valid']??null)===false?'⚠️':'-') ?></td>
<td><span class="score <?= ($s['seo_score']??0)>=70?'good':(($s['seo_score']??0)>=40?'warn':'bad') ?>"><?= $s['seo_score']??'-' ?></span></td>
<td><span class="score <?= ($s['security_score']??0)>=70?'good':(($s['security_score']??0)>=40?'warn':'bad') ?>"><?= $s['security_score']??'-' ?></span></td>
<td style="font-size:.75rem;color:var(--ink-soft)"><?= $s['last_checked_at'] ? date('d/m H:i', strtotime($s['last_checked_at'])) : 'טרם' ?></td>
<td>
<?php if ($s['id']): ?>
<a href="<?= $url('admin/monitoring/'.$s['id']) ?>" class="btn-sm">📋 פרטים</a>
<a href="<?= $url('admin/monitoring/'.$s['id'].'/check') ?>" class="btn-sm">🔄 בדוק</a>
<a href="<?= $url('admin/monitoring/'.$s['id'].'/solutions') ?>" class="btn-sm btn-sol">💡 המלצות</a>
<a href="<?= $url('admin/monitoring/'.$s['id'].'/delete') ?>" class="btn-sm" onclick="return confirm('להסיר את האתר מניטור?')">🗑️</a>
<?php else: ?>
<span style="font-size:.72rem;color:var(--ink-faint)">אתר דמו</span>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
<?php if (empty($sites)): ?><tr><td colspan="9" class="empty">אין אתרים לניטור.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php $content=ob_get_clean(); include __DIR__."/../admin/layout.php"; ?>
