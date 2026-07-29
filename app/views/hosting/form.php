<?php ob_start() ?>
<div class="top-bar"><h1>חשבון אחסון חדש</h1><a href="<?= $url("admin/hosting") ?>" class="btn btn-ghost">←</a></div>
<style>.btn{display:inline-flex;align-items:center;gap:6px;font-weight:700;font-size:.88rem;border-radius:10px;padding:10px 20px;border:none;font-family:inherit;cursor:pointer;white-space:nowrap;text-decoration:none}.btn-primary{background:var(--primary);color:#fff}.btn-ghost{background:transparent;border:1px solid var(--border);color:var(--ink)}.btn-block{width:100%}.form-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:28px;max-width:560px}.form-group{margin-bottom:14px}.form-group label{display:block;font-size:.82rem;font-weight:600;margin-bottom:4px}.form-group input,.form-group select{width:100%;padding:10px 12px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:.88rem}</style>
<div class="form-card"><form method="POST" action="<?= $url("admin/hosting") ?>"><?= $csrf() ?>
  <div class="form-group"><label>דומיין *</label><input type="text" name="domain" required></div>
  <div class="form-group"><label>תוכנית</label><input type="text" name="hosting_plan" value="Business Hosting"></div>
  <div class="form-group"><label>ספק</label><select name="hosting_provider"><option>Hostinger</option><option>Cloudways</option><option>AWS</option><option>אחר</option></select></div>
  <div class="form-group"><label>תחילת אחסון *</label><input type="date" name="start_date" required></div>
  <div class="form-group"><label>תאריך תפוגה *</label><input type="date" name="expiration_date" required></div>
  <div class="form-group"><label>מחיר חידוש (₪)</label><input type="number" name="renewal_price" step="0.01"></div>
  <button type="submit" class="btn btn-primary btn-block">צור חשבון</button>
</form></div>
<?php $content=ob_get_clean(); include __DIR__."/../admin/layout.php"; ?>
