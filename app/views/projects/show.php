<?php ob_start() ?>
<div class="top-bar"><h1><?= htmlspecialchars($project["title"]) ?></h1><div style="display:flex;gap:8px"><a href="<?= $url("admin/projects/".$project["id"]) ?>" class="btn btn-primary btn-sm">ערוך</a><a href="<?= $url("admin/projects") ?>" class="btn btn-ghost btn-sm">←</a></div></div>
<style>.btn{display:inline-flex;align-items:center;gap:6px;font-weight:700;font-size:.88rem;border-radius:10px;padding:10px 20px;border:none;font-family:inherit;cursor:pointer;white-space:nowrap;text-decoration:none}.btn-primary{background:var(--primary);color:#fff}.btn-sm{padding:6px 14px;font-size:.78rem}.btn-ghost{background:transparent;border:1px solid var(--border);color:var(--ink)}.detail-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:22px;max-width:600px;margin-bottom:18px}.detail-row{display:flex;justify-content:space-between;padding:8px 0;font-size:.85rem;border-bottom:1px solid var(--border)}.detail-label{color:var(--ink-soft)}.detail-value{font-weight:600}.status-form{display:flex;gap:8px;align-items:center}.status-form select{padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:.85rem}</style>
<div class="detail-card"><h3 style="font-size:.92rem;font-weight:700;margin-bottom:14px">פרטי פרויקט</h3>
  <div class="detail-row"><span class="detail-label">שם</span><span class="detail-value"><?= htmlspecialchars($project["title"]) ?></span></div>
  <div class="detail-row"><span class="detail-label">סוג</span><span class="detail-value"><?= $project["type"] ?></span></div>
  <div class="detail-row"><span class="detail-label">חבילה</span><span class="detail-value"><?= $project["package"] ?></span></div>
  <div class="detail-row"><span class="detail-label">דומיין</span><span class="detail-value"><?= htmlspecialchars($project["url"] ?? "-") ?></span></div>
  <div class="detail-row"><span class="detail-label">מחיר</span><span class="detail-value"><?= $project["price"] ? "₪".number_format($project["price"]) : "-" ?></span></div>
  <div class="detail-row"><span class="detail-label">דדליין</span><span class="detail-value"><?= $project["deadline"] ?? "-" ?></span></div>
  <div class="detail-row"><span class="detail-label">סטטוס</span><span class="detail-value"><?= $project["status"] ?></span></div>
  <div class="detail-row"><span class="detail-label">התקדמות</span><span class="detail-value"><?= $project["progress"] ?? 0 ?>%</span></div>
</div>
<div class="detail-card"><h3 style="font-size:.92rem;font-weight:700;margin-bottom:14px">עדכון סטטוס</h3>
  <form method="POST" action="<?= $url("admin/projects/".$project["id"]."/status") ?>" class="status-form">
    <select name="status"><?php foreach(["new_request"=>"בקשה חדשה","in_review"=>"בבדיקה","in_development"=>"בפיתוח","testing"=>"בדיקות","delivered"=>"הושלם","on_hold"=>"בהמתנה","cancelled"=>"בוטל"] as $k=>$v): ?><option value="<?= $k ?>" <?= $project["status"]===$k?"selected":"" ?>><?= $v ?></option><?php endforeach; ?></select>
    <button type="submit" class="btn btn-primary btn-sm">עדכן</button>
  </form>
</div>
<?php $content=ob_get_clean(); include __DIR__."/../admin/layout.php"; ?>
