<?php ob_start() ?>
<div class="top-bar"><h1>דשבורד</h1><span style="color:var(--ink-soft)"><?= date('d/m/Y') ?></span></div>
<div class="kpi-grid">
  <div class="kpi"><div class="kpi-label">סה"כ לידים</div><div class="kpi-value"><?= $stats['totalLeads'] ?? 0 ?></div><div class="kpi-change up">החודש</div></div>
  <div class="kpi"><div class="kpi-label">פרויקטים פעילים</div><div class="kpi-value"><?= $stats['activeProjects'] ?? 0 ?></div><div class="kpi-change up">בתהליך</div></div>
  <div class="kpi"><div class="kpi-label">אתרים מנוטרים</div><div class="kpi-value"><?= $stats['monitoredSites'] ?? 0 ?></div><div class="kpi-change up">פעילים</div></div>
  <div class="kpi"><div class="kpi-label">חשבונות אחסון</div><div class="kpi-value"><?= $stats['hostingAccounts'] ?? 0 ?></div><div class="kpi-change up">פעילים</div></div>
</div>
<div class="panels">
  <div class="panel"><h3>לידים אחרונים</h3>
    <?php foreach ($stats['recentLeads'] ?? [] as $lead): ?>
    <div class="quick-row"><span><strong><?= htmlspecialchars($lead['name']) ?></strong> — <?= htmlspecialchars($lead['company'] ?? '') ?></span><span class="badge badge-new"><?= $lead['status'] ?></span></div>
    <?php endforeach; ?>
    <?php if (empty($stats['recentLeads'])): ?><p style="color:var(--ink-soft);font-size:.85rem">אין לידים עדיין.</p><?php endif; ?>
  </div>
  <div class="panel"><h3>פרויקטים אחרונים</h3>
    <?php foreach ($stats['recentProjects'] ?? [] as $proj): ?>
    <div class="quick-row"><span><strong><?= htmlspecialchars($proj['title']) ?></strong></span><span class="badge badge-active"><?= $proj['status'] ?></span></div>
    <?php endforeach; ?>
    <?php if (empty($stats['recentProjects'])): ?><p style="color:var(--ink-soft);font-size:.85rem">אין פרויקטים עדיין.</p><?php endif; ?>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/layout.php'; ?>
