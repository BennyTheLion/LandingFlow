<?php ob_start() ?>
<style>
.sol-page{max-width:800px}
.sol-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:14px}
.sol-card h3{font-size:1rem;margin-bottom:8px;display:flex;align-items:center;gap:8px}
.sol-label{display:inline-block;padding:3px 10px;border-radius:100px;font-size:.7rem;font-weight:700}
.sol-high{background:rgba(220,38,38,.1);color:var(--danger)}
.sol-medium{background:rgba(245,158,11,.1);color:var(--warning)}
.sol-low{background:rgba(22,163,74,.1);color:var(--success)}
.sol-critical{background:rgba(220,38,38,.15);color:var(--danger)}
.sol-impact{font-size:.82rem;color:var(--ink-soft);margin-top:4px}
.scores-row{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:24px}
.score-box{text-align:center;padding:18px;background:var(--surface-2);border-radius:10px}
.score-box .num{font-size:2rem;font-weight:800;font-family:var(--font-mono)}
.score-box .lbl{font-size:.75rem;color:var(--ink-soft);margin-top:4px}
.btn-back{display:inline-block;padding:8px 14px;border-radius:8px;border:1px solid var(--border);color:var(--ink-soft);text-decoration:none;font-size:.85rem;margin-bottom:20px}
.btn-back:hover{background:var(--surface-2)}
.cta-box{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border-radius:12px;padding:20px 24px;margin-top:24px;text-align:center}
.cta-box p{margin-bottom:12px;opacity:.9}
.cta-box a{display:inline-block;background:#fff;color:var(--primary);padding:10px 24px;border-radius:8px;font-weight:700;text-decoration:none;font-size:.9rem}
</style>

<div class="sol-page">
<a href="<?= $url('admin/monitoring') ?>" class="btn-back">← חזרה לניטור</a>
<div class="top-bar"><h1>💡 המלצות — <?= htmlspecialchars($site['name']) ?></h1></div>

<div class="scores-row">
  <div class="score-box"><div class="num" style="color:<?= ($current['seo_score']??0)>=70?'var(--success)':(($current['seo_score']??0)>=40?'var(--warning)':'var(--danger)') ?>"><?= $current['seo_score'] ?? '-' ?></div><div class="lbl">ציון SEO</div></div>
  <div class="score-box"><div class="num" style="color:<?= ($current['security_score']??0)>=70?'var(--success)':(($current['security_score']??0)>=40?'var(--warning)':'var(--danger)') ?>"><?= $current['security_score'] ?? '-' ?></div><div class="lbl">ציון אבטחה</div></div>
  <div class="score-box"><div class="num"><?= isset($current['response_time_ms']) ? $current['response_time_ms'].'ms' : '-' ?></div><div class="lbl">זמן תגובה</div></div>
</div>

<?php foreach ($recommendations as $r): ?>
<div class="sol-card">
  <h3>
    <span class="sol-label sol-<?= $r['priority'] ?>"><?= $r['category'] ?></span>
    <?= htmlspecialchars($r['action']) ?>
  </h3>
  <div class="sol-impact">💡 <?= htmlspecialchars($r['impact']) ?></div>
</div>
<?php endforeach; ?>

<div class="cta-box">
  <p><strong>רוצה שנטפל בזה בשבילך?</strong><br>צוות המומחים שלנו ישפר לך את האתר — SEO, אבטחה, ומהירות.</p>
  <a href="<?= $url('contact') ?>">📞 צור קשר עכשיו</a>
</div>
</div>

<?php $content = ob_get_clean(); include __DIR__ . '/../admin/layout.php'; ?>
