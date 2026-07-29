<?php ob_start(); $isEdit=isset($project) ?>
<div class="top-bar"><h1><?= $isEdit ? "עריכת פרויקט" : "פרויקט חדש" ?></h1><a href="<?= $url("admin/projects") ?>" class="btn btn-ghost">←</a></div>
<style>.form-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:28px;max-width:680px}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}@media(max-width:600px){.form-grid{grid-template-columns:1fr}}.form-group{margin-bottom:14px}.form-group.full{grid-column:1/-1}.form-group label{display:block;font-size:.82rem;font-weight:600;margin-bottom:4px}.form-group input,.form-group select,.form-group textarea{width:100%;padding:10px 12px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:.88rem}</style>
<div class="form-card"><form method="POST" action="<?= $url($isEdit ? "admin/projects/".$project["id"] : "admin/projects") ?>"><?= $csrf() ?>
<div class="form-grid">
  <div class="form-group full"><label>שם *</label><input type="text" name="title" required value="<?= htmlspecialchars($project["title"] ?? "") ?>"></div>
  <div class="form-group"><label>סוג</label><select name="type"><option value="landing_page">דף נחיתה</option><option value="business_site" selected>אתר עסקי</option><option value="ecommerce">חנות</option><option value="custom">מותאם</option></select></div>
  <div class="form-group"><label>חבילה</label><select name="package"><option value="starter">Starter</option><option value="business" selected>Business</option><option value="premium">Premium</option></select></div>
  <div class="form-group"><label>דומיין</label><input type="text" name="url" value="<?= htmlspecialchars($project["url"] ?? "") ?>"></div>
  <div class="form-group"><label>Staging URL</label><input type="text" name="staging_url" value="<?= htmlspecialchars($project["staging_url"] ?? "") ?>"></div>
  <div class="form-group"><label>מחיר (₪)</label><input type="number" name="price" step="0.01" value="<?= htmlspecialchars($project["price"] ?? "") ?>"></div>
  <div class="form-group"><label>תאריך התחלה</label><input type="date" name="start_date" value="<?= $project["start_date"] ?? "" ?>"></div>
  <div class="form-group"><label>דדליין</label><input type="date" name="deadline" value="<?= $project["deadline"] ?? "" ?>"></div>
  <div class="form-group full"><label>הערות</label><textarea name="notes" rows="3"><?= htmlspecialchars($project["notes"] ?? "") ?></textarea></div>
  <div class="form-group full"><button type="submit" class="btn btn-primary btn-block"><?= $isEdit ? "שמור" : "צור פרויקט" ?></button></div>
</div></form></div>
<?php $content=ob_get_clean(); include __DIR__."/../admin/layout.php"; ?>
