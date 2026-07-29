<?php ob_start() ?>
<div class="top-bar"><h1>חשבונות אחסון</h1><a href="<?= $url("admin/hosting/create") ?>" class="btn btn-primary">+ חשבון חדש</a></div>
<?php $succ = $flash("success"); if ($succ): ?><div class="alert alert-success"><?= htmlspecialchars($succ) ?></div><?php endif; ?>
<style>.table-wrap{background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;overflow-x:auto}.table-row{display:grid;grid-template-columns:1.2fr 1fr .8fr .8fr .5fr;gap:12px;align-items:center;padding:12px 18px;border-bottom:1px solid var(--border);font-size:.85rem;min-width:560px}@media(max-width:760px){.table-row{grid-template-columns:1fr .6fr;font-size:.8rem}.table-row.head{display:none}.table-row span:nth-child(2),.table-row span:nth-child(3){display:none}}.table-row.head{background:var(--surface-2);font-weight:700;color:var(--ink-soft);font-size:.76rem}</style>
<div class="table-wrap">
  <div class="table-row head"><span>דומיין</span><span>תוכנית</span><span>תאריך תפוגה</span><span>סטטוס</span><span></span></div>
  <?php foreach($accounts as $a): ?>
  <div class="table-row"><span style="font-weight:600"><?= htmlspecialchars($a["domain"]) ?></span><span><?= $a["hosting_plan"] ?></span><span><?= $a["expiration_date"] ?></span><span><?= $a["status"] ?></span><span><a href="<?= $url("admin/hosting/".$a["id"]) ?>" class="btn btn-ghost btn-sm">פרטים</a></span></div>
  <?php endforeach; ?>
</div>
<?php $content=ob_get_clean(); include __DIR__."/../admin/layout.php"; ?>

