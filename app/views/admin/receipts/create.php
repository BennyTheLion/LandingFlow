<?php ob_start() ?>
<style>
.create-receipt { max-width:640px }
.create-receipt h1 { margin-bottom:24px;font-size:1.5rem }
.form-group { margin-bottom:18px }
.form-group label { display:block;margin-bottom:6px;font-weight:600;font-size:.9rem;color:#374151 }
.form-group input,.form-group textarea { width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:.95rem;font-family:inherit;box-sizing:border-box }
.form-group textarea { min-height:100px;resize:vertical }
.form-row { display:flex;gap:14px }
.form-row .form-group { flex:1 }
.btn-submit { background:var(--primary,#2563EB);color:#fff;border:none;padding:12px 28px;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;width:100% }
.btn-submit:hover { opacity:.9 }
.btn-back { color:#6b7280;text-decoration:none;font-size:.9rem;margin-bottom:16px;display:inline-block }
.errors { background:#fef2f2;color:#991b1b;padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:.9rem }
.errors li { margin:4px 0 }
input[type=date] { direction:ltr;text-align:right }
.flash { padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:.9rem }
.flash.success { background:#dcfce7;color:#166534 }
.flash.error { background:#fef2f2;color:#991b1b }
</style>

<div class="receipts-page create-receipt">
<a href="<?= $url('admin/receipts') ?>" class="btn-back">← חזרה לרשימת קבלות</a>
<h1>🧾 יצירת קבלה חדשה</h1>

<?php if (!empty($flashMsg)): ?>
<div class="flash <?= htmlspecialchars($flashMsg['type']) ?>"><?= htmlspecialchars($flashMsg['message']) ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<ul class="errors"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
<?php endif; ?>

<form method="POST" action="<?= $url('admin/receipts') ?>">
<div class="form-row">
<div class="form-group">
<label>שם לקוח *</label>
<input type="text" name="customer_name" value="<?= htmlspecialchars($old['customer_name'] ?? '') ?>" required>
</div>
<div class="form-group">
<label>אימייל לקוח *</label>
<input type="email" name="customer_email" value="<?= htmlspecialchars($old['customer_email'] ?? '') ?>" required>
</div>
</div>

<div class="form-row">
<div class="form-group">
<label>מספר עסקה / חשבונית</label>
<input type="text" name="transaction_id" value="<?= htmlspecialchars($old['transaction_id'] ?? '') ?>">
</div>
<div class="form-group">
<label>תאריך קבלה *</label>
<input type="date" name="receipt_date" value="<?= htmlspecialchars($old['receipt_date'] ?? date('Y-m-d')) ?>" required>
</div>
</div>

<div class="form-group">
<label>תיאור שירות *</label>
<textarea name="service_description" required><?= htmlspecialchars($old['service_description'] ?? '') ?></textarea>
</div>

<div class="form-row">
<div class="form-group">
<label>סכום (₪) *</label>
<input type="number" step="0.01" min="0.01" name="amount" value="<?= htmlspecialchars($old['amount'] ?? '') ?>" required>
</div>
<div class="form-group">
<label>שליחה אוטומטית במייל</label>
<label style="display:flex;align-items:center;gap:8px;font-weight:400;padding-top:8px">
<input type="checkbox" name="send_email" value="1" checked> שלח קבלה ללקוח במייל
</label>
</div>
</div>

<button type="submit" class="btn-submit">📄 צור קבלה</button>
</form>
</div>
<?php $content = ob_get_clean(); include APP_PATH . '/views/admin/layout.php'; ?>
