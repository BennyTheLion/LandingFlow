<?php ob_start() ?>
<style>
.receipts-page { max-width:1100px }
.page-bar { display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px }
.page-bar h1 { margin:0;font-size:1.5rem }
.search-row { display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px }
.search-row input,.search-row button { padding:8px 14px;border-radius:6px;border:1px solid #e5e7eb;font-size:.9rem }
.search-row button { background:var(--primary,#2563EB);color:#fff;border:none;cursor:pointer }
.receipts-table { width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06) }
.receipts-table th,.receipts-table td { padding:12px 16px;text-align:right;font-size:.9rem }
.receipts-table th { background:#f9fafb;color:#6b7280;font-weight:600;font-size:.8rem;text-transform:uppercase }
.receipts-table tr { border-bottom:1px solid #f3f4f6 }
.receipts-table tr:hover { background:#f9fafb }
.actions { display:flex;gap:6px }
.actions a,.actions button { padding:6px 12px;border-radius:6px;font-size:.8rem;text-decoration:none;border:none;cursor:pointer }
.btn-download { background:#e0f2fe;color:#0284c7 }
.btn-email { background:#fef3c7;color:#d97706 }
.btn-view { background:#f3f4f6;color:#374151 }
.btn-create { background:var(--primary,#2563EB);color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600;display:inline-flex;align-items:center;gap:6px }
.empty { text-align:center;padding:60px 20px;color:#9ca3af }
.amount { font-family:monospace;font-weight:600 }
.flash { padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:.9rem }
.flash.success { background:#dcfce7;color:#166534 }
.flash.error { background:#fef2f2;color:#991b1b }
</style>

<div class="receipts-page">
<div class="page-bar">
<h1>🧾 קבלות</h1>
<a href="<?= $url('admin/receipts/create') ?>" class="btn-create">+ קבלה חדשה</a>
</div>

<?php if (!empty($flashMsg)): ?>
<div class="flash <?= htmlspecialchars($flashMsg['type']) ?>"><?= htmlspecialchars($flashMsg['message']) ?></div>
<?php endif; ?>

<form method="GET" class="search-row">
<input type="text" name="search" placeholder="חיפוש לפי שם לקוח / מס' קבלה..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
<button type="submit">🔍 חיפוש</button>
<?php if (!empty($_GET['search'])): ?>
<a href="<?= $url('admin/receipts') ?>" style="padding:8px 14px;color:#6b7280;font-size:.9rem">✕ נקה</a>
<?php endif; ?>
</form>

<?php if (empty($receipts)): ?>
<div class="empty">
<p>📭 אין קבלות להצגה</p>
<a href="<?= $url('admin/receipts/create') ?>" class="btn-create">צור קבלה ראשונה</a>
</div>
<?php else: ?>
<table class="receipts-table">
<thead><tr>
<th>מס' קבלה</th><th>לקוח</th><th>סכום</th><th>תאריך</th><th>נשלח</th><th>פעולות</th>
</tr></thead>
<tbody>
<?php foreach ($receipts as $r): ?>
<tr>
<td><b><?= htmlspecialchars($r['receipt_number']) ?></b></td>
<td>
<?= htmlspecialchars($r['customer_name']) ?><br>
<small style="color:#9ca3af"><?= htmlspecialchars($r['customer_email']) ?></small>
</td>
<td class="amount">₪<?= number_format($r['amount'], 2) ?></td>
<td><?= date('d/m/Y', strtotime($r['receipt_date'])) ?></td>
<td><?= $r['emailed_at'] ? '✅ ' . date('d/m/Y H:i', strtotime($r['emailed_at'])) : '❌ טרם' ?></td>
<td class="actions">
<?php if ($r['pdf_path']): ?>
<a href="<?= $url('admin/receipts/' . $r['id'] . '/download') ?>" class="btn-download">📥 PDF</a>
<?php endif; ?>
<a href="<?= $url('admin/receipts/' . $r['id'] . '/resend') ?>" class="btn-email" onclick="return confirm('לשלוח שוב את הקבלה ל-<?= htmlspecialchars($r['customer_email']) ?>?')">📧 שלח</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
<?php $content = ob_get_clean(); include APP_PATH . '/views/admin/layout.php'; ?>
