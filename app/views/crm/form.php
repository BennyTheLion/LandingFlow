<?php ob_start(); $isEdit = isset($lead); ?>
<div class="top-bar"><h1><?= $isEdit ? 'עריכת ליד' : 'ליד חדש' ?></h1><a href="<?= $url('admin/leads') ?>" class="btn btn-ghost">← חזרה</a></div>

<style>
.form-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:28px;max-width:680px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:600px){.form-grid{grid-template-columns:1fr}}
.form-group{margin-bottom:16px}.form-group.full{grid-column:1/-1}
.form-group label{display:block;font-size:.82rem;font-weight:600;margin-bottom:4px}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:10px 12px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:.88rem}
.check-row{display:flex;align-items:center;gap:8px;font-size:.85rem}
.check-row input[type=checkbox]{width:auto}
</style>

<?php $err = $flash('error'); if ($err): ?><div class="alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="form-card">
  <form method="POST" action="<?= $url($isEdit ? 'admin/leads/' . $lead['id'] : 'admin/leads') ?>">
    <?= $csrf() ?>
    <div class="form-grid">
      <div class="form-group"><label>שם מלא *</label><input type="text" name="name" required value="<?= htmlspecialchars($lead['name'] ?? '') ?>"></div>
      <div class="form-group"><label>אימייל</label><input type="email" name="email" value="<?= htmlspecialchars($lead['email'] ?? '') ?>"></div>
      <div class="form-group"><label>טלפון</label><input type="text" name="phone" value="<?= htmlspecialchars($lead['phone'] ?? '') ?>"></div>
      <div class="form-group"><label>חברה</label><input type="text" name="company" value="<?= htmlspecialchars($lead['company'] ?? '') ?>"></div>
      <div class="form-group"><label>אתר</label><input type="text" name="website" value="<?= htmlspecialchars($lead['website'] ?? '') ?>"></div>
      <div class="form-group"><label>מקור</label><select name="source"><option value="website" <?= ($lead['source']??'')==='website'?'selected':'' ?>>אתר</option><option value="audit" <?= ($lead['source']??'')==='audit'?'selected':'' ?>>ביקורת</option><option value="referral" <?= ($lead['source']??'')==='referral'?'selected':'' ?>>המלצה</option><option value="social" <?= ($lead['source']??'')==='social'?'selected':'' ?>>רשתות חברתיות</option><option value="phone" <?= ($lead['source']??'')==='phone'?'selected':'' ?>>טלפון</option><option value="other" <?= ($lead['source']??'')==='other'?'selected':'' ?>>אחר</option></select></div>
      <div class="form-group"><label>עניין</label><input type="text" name="interest" value="<?= htmlspecialchars($lead['interest'] ?? '') ?>"></div>
      <div class="form-group"><label>תקציב (₪)</label><input type="number" name="budget" step="0.01" value="<?= htmlspecialchars($lead['budget'] ?? '') ?>"></div>
      <div class="form-group full"><label>הערות</label><textarea name="notes" rows="3"><?= htmlspecialchars($lead['notes'] ?? '') ?></textarea></div>
      <?php if (!$isEdit): ?>
      <div class="form-group full"><div class="check-row"><input type="checkbox" name="consent" value="1" id="consent"><label for="consent">אני מאשר שהלקוח נתן הסכמה לשמירת מידע</label></div></div>
      <?php endif; ?>
      <div class="form-group full"><button type="submit" class="btn btn-primary btn-block"><?= $isEdit ? 'שמור שינויים' : 'צור ליד' ?></button></div>
    </div>
  </form>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../admin/layout.php'; ?>
