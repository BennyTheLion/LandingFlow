<?php ob_start() ?>
<div class="top-bar"><h1>אחסון — <?= htmlspecialchars($account["domain"]) ?></h1><a href="<?= $url("admin/hosting") ?>" class="btn btn-ghost">←</a></div>
<style>.btn{display:inline-flex;align-items:center;gap:6px;font-weight:700;font-size:.88rem;border-radius:10px;padding:10px 20px;border:none;font-family:inherit;cursor:pointer;white-space:nowrap;text-decoration:none}.btn-ghost{background:transparent;border:1px solid var(--border);color:var(--ink)}.detail-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:22px;max-width:500px}.detail-row{display:flex;justify-content:space-between;padding:8px 0;font-size:.85rem;border-bottom:1px solid var(--border)}.detail-label{color:var(--ink-soft)}.detail-value{font-weight:600}</style>
<div class="detail-card">
  <div class="detail-row"><span class="detail-label">דומיין</span><span class="detail-value"><?= $account["domain"] ?></span></div>
  <div class="detail-row"><span class="detail-label">תוכנית</span><span class="detail-value"><?= $account["hosting_plan"] ?></span></div>
  <div class="detail-row"><span class="detail-label">ספק</span><span class="detail-value"><?= $account["hosting_provider"] ?></span></div>
  <div class="detail-row"><span class="detail-label">תחילת אחסון</span><span class="detail-value"><?= $account["start_date"] ?></span></div>
  <div class="detail-row"><span class="detail-label">תפוגה</span><span class="detail-value"><?= $account["expiration_date"] ?></span></div>
  <div class="detail-row"><span class="detail-label">סטטוס</span><span class="detail-value"><?= $account["status"] ?></span></div>
</div>
<?php $content=ob_get_clean(); include __DIR__."/../admin/layout.php"; ?>
