<?php ob_start(); $r = $report; $fr = $r['full_report'] ?? []; ?>
<div class="top-bar"><h1>דוח #<?= $r['id'] ?></h1><a href="<?= $url('admin/audit-reports') ?>" class="btn btn-ghost">← חזרה</a></div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">
  <div class="kpi"><div class="kpi-label">כתובת</div><div class="kpi-value" style="font-size:1rem"><?= htmlspecialchars($r['url']) ?></div></div>
  <div class="kpi"><div class="kpi-label">ציון כללי</div><div class="kpi-value" style="color:<?= $r['overall_score'] >= 80 ? 'var(--success)' : ($r['overall_score'] >= 60 ? 'var(--warning)' : 'var(--danger)') ?>"><?= $r['overall_score'] ?>/100</div></div>
</div>

<div class="kpi-grid">
  <div class="kpi"><div class="kpi-label">SEO</div><div class="kpi-value"><?= $r['seo_score'] ?? '-' ?></div></div>
  <div class="kpi"><div class="kpi-label">אבטחה</div><div class="kpi-value"><?= $r['security_score'] ?? '-' ?></div></div>
  <div class="kpi"><div class="kpi-label">משפטי</div><div class="kpi-value"><?= $r['legal_score'] ?? '-' ?></div></div>
  <div class="kpi"><div class="kpi-label">נגישות</div><div class="kpi-value"><?= $r['accessibility_score'] ?? '-' ?></div></div>
</div>

<?php if (!empty($r['recommendations'])): ?>
<div class="panel"><h3>המלצות</h3>
<?php foreach (array_slice($r['recommendations'], 0, 15) as $rec): ?>
<div class="quick-row"><span><?= htmlspecialchars(is_string($rec) ? $rec : ($rec['action'] ?? $rec['check'] ?? '')) ?></span></div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<p style="color:var(--ink-faint);font-size:.8rem;margin-top:16px">נסרק בתאריך: <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></p>
<?php $content = ob_get_clean(); include __DIR__ . '/layout.php'; ?>
