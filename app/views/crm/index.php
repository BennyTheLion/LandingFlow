<?php ob_start() ?>
<div class="top-bar"><h1>לידים</h1><a href="<?= $url('admin/leads/create') ?>" class="btn btn-primary">+ ליד חדש</a></div>

<?php $succ = $flash('success'); if ($succ): ?><div class="alert alert-success"><?= htmlspecialchars($succ) ?></div><?php endif; ?>

<style>
.table-wrap{background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;overflow-x:auto}
.table-row{display:grid;grid-template-columns:1.2fr .8fr .6fr .6fr .5fr .5fr .4fr;gap:12px;align-items:center;padding:12px 18px;border-bottom:1px solid var(--border);font-size:.85rem;min-width:700px}
@media(max-width:760px){.table-row{grid-template-columns:1fr .6fr;font-size:.8rem}.table-row.head{display:none}.table-row span:nth-child(2),.table-row span:nth-child(3){display:none}}
.table-row.head{background:var(--surface-2);font-weight:700;color:var(--ink-soft);font-size:.76rem}
.status-badge{display:inline-block;font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:100px}
.s-new{background:rgba(14,165,233,.1);color:var(--info)}.s-contacted{background:rgba(245,158,11,.1);color:var(--warning)}.s-qualified{background:rgba(22,163,74,.1);color:var(--success)}.s-closed{background:rgba(220,38,38,.1);color:var(--danger)}
.action-links{display:flex;gap:10px;align-items:center}.action-links a{font-size:.78rem;font-weight:600;color:var(--primary)}
.delete-form{display:inline}.delete-link{font-size:.78rem;font-weight:600;color:var(--danger);background:none;border:none;padding:0;cursor:pointer;font-family:inherit}
</style>

<div class="table-wrap">
  <div class="table-row head"><span>שם</span><span>אימייל / טלפון</span><span>מקור</span><span>סטטוס</span><span>תאריך</span><span>שעה</span><span>פעולות</span></div>
  <?php foreach($leads as $l): ?>
  <div class="table-row">
    <span><strong><?= htmlspecialchars($l['name']) ?></strong></span>
    <span><?= htmlspecialchars($l['email'] ?: $l['phone']) ?></span>
    <span><?= htmlspecialchars($l['source']) ?></span>
    <span><span class="status-badge s-<?= $l['status'] ?>"><?= htmlspecialchars($l['status']) ?></span></span>
    <span><?= date('d/m/Y', strtotime($l['created_at'] ?? 'now')) ?></span>
    <span><?= date('H:i', strtotime($l['created_at'] ?? 'now')) ?></span>
    <span class="action-links">
      <a href="<?= $url('admin/leads/' . $l['id']) ?>">צפייה</a>
      <form method="POST" action="<?= $url('admin/leads/' . $l['id']) ?>" class="delete-form" onsubmit="return confirm('למחוק את הליד של <?= htmlspecialchars(addslashes($l['name'])) ?>? הפעולה בלתי הפיכה.')">
        <?= $csrf() ?>
        <input type="hidden" name="_method" value="DELETE">
        <button type="submit" class="delete-link">מחק</button>
      </form>
    </span>
  </div>
  <?php endforeach; ?>
  <?php if (empty($leads)): ?>
  <div class="table-row"><span style="grid-column:1/-1;text-align:center;color:var(--ink-faint)">אין לידים עדיין.</span></div>
  <?php endif; ?>
</div>

<?php $content = ob_get_clean(); include __DIR__ . '/../admin/layout.php'; ?>
