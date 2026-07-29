<?php
$r = $report; $fr = $r['full_report'] ?? [];
$_c = '';
$_c .= '<section style="padding-top:100px"><div class="container" style="max-width:900px">';
$_c .= '<a href="' . $url("dashboard") . '" style="color:var(--primary)">← Dashboard</a>';
$_c .= '<h1 style="font-size:1.4rem;margin:12px 0 4px">Report #' . $r['id'] . '</h1>';
$_c .= '<p style="color:var(--ink-soft);margin-bottom:24px">' . htmlspecialchars($r['url']) . ' · ' . date('d/m/Y H:i', strtotime($r['created_at'])) . '</p>';
$_c .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:32px">';
$layers = [
  'Overall' => $r['overall_score'] ?? 0,
  'SEO' => $r['seo_score'] ?? 0, 'Performance' => $r['performance_score'] ?? 0,
  'Security' => $r['security_score'] ?? 0, 'Accessibility' => $r['accessibility_score'] ?? 0
];
foreach ($layers as $label => $score):
  $color = $score >= 80 ? 'var(--success)' : ($score >= 60 ? 'var(--warning)' : 'var(--danger)');
  $_c .= '<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:16px;text-align:center">';
  $_c .= '<div style="font-size:1.8rem;font-weight:800;color:' . $color . '">' . $score . '</div>';
  $_c .= '<div style="font-size:.85rem;color:var(--ink-soft)">' . $label . '</div></div>';
endforeach;
$_c .= '</div>';
if (!empty($r['recommendations'])):
  $_c .= '<h2 style="margin-bottom:12px">Recommendations</h2><ul style="list-style:none;padding:0">';
  foreach (array_slice($r['recommendations'],0,10) as $rec):
    $_c .= '<li style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:10px 14px;margin-bottom:8px">🔧 ' . htmlspecialchars(is_string($rec) ? $rec : ($rec['issue'] ?? '')) . '</li>';
  endforeach;
  $_c .= '</ul>';
endif;
$_c .= '</div></section>';
$content = $_c;
include __DIR__ . '/../partials/layout.php';
