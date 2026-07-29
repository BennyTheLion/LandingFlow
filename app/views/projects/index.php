<?php ob_start() ?>
<div class="top-bar"><h1>פרויקטים</h1><a href="<?= $url("admin/projects/create") ?>" class="btn btn-primary">+ פרויקט חדש</a></div>
<?php $succ = $flash("success"); if ($succ): ?><div class="alert alert-success"><?= htmlspecialchars($succ) ?></div><?php endif; ?>
<style>.btn{display:inline-flex;align-items:center;gap:6px;font-weight:700;font-size:.88rem;border-radius:10px;padding:10px 20px;transition:.2s;cursor:pointer;border:none;font-family:inherit;white-space:nowrap;text-decoration:none}.btn-primary{background:var(--primary);color:#fff}.btn-sm{padding:6px 14px;font-size:.78rem}.btn-ghost{background:transparent;border:1px solid var(--border);color:var(--ink)}.alert-success{background:rgba(22,163,74,.12);color:var(--success);border:1px solid rgba(22,163,74,.3);padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:.85rem}.table-wrap{background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden}.table-row{display:grid;grid-template-columns:1.5fr .8fr .8fr .6fr .4fr;gap:12px;align-items:center;padding:12px 18px;border-bottom:1px solid var(--border);font-size:.85rem}.table-row.head{background:var(--surface-2);font-weight:700;color:var(--ink-soft);font-size:.76rem}.status-badge{display:inline-block;font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:100px}.s-active{background:rgba(31,161,92,.1);color:var(--success)}</style>
<div class="table-wrap">
  <div class="table-row head"><span>שם</span><span>סוג</span><span>סטטוס</span><span>מחיר</span><span></span></div>
  <?php foreach($projects as $p): ?>
  <div class="table-row">
    <span><strong><?= htmlspecialchars($p['name']) ?></strong></span>
    <span><?= htmlspecialchars($p['type'] ?? '—') ?></span>
    <span><span class="status-badge s-<?= $p['status'] ?>"><?= $p['status'] ?></span></span>
    <span><?= $p['price'] ? '₪'.number_format($p['price']) : '—' ?></span>
    <span><a href="<?= $url('admin/projects/'.$p['id']) ?>" class="btn btn-sm btn-ghost">צפייה</a></span>
  </div>
  <?php endforeach; ?>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../admin/layout.php'; ?>
