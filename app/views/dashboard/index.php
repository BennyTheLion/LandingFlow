<?php
// Safety defaults (Controller extract may not reach through scope)
if (!isset($total)) $total = 0;
if (!isset($avg)) $avg = 0;
if (!isset($recent)) $recent = [];
// Build content as a string — no nested ob_start
$_content = '';
$_content .= '<section style="padding-top:100px;min-height:100vh;background:#0f172a;color:#e2e8f0"><div class="container">';
$_content .= '<h1 style="font-size:1.6rem;margin-bottom:24px;color:#fff">📊 Dashboard</h1>';
$_content .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:32px">';
$_content .= '<div style="background:#1e293b;border:1px solid #334155;border-radius:12px;padding:20px;text-align:center">';
$_content .= '<div style="font-size:2rem;font-weight:800;color:var(--primary)">' . $total . '</div>';
$_content .= '<div style="color:#94a3b8">Total Scans</div></div>';
$_content .= '<div style="background:#1e293b;border:1px solid #334155;border-radius:12px;padding:20px;text-align:center">';
$_content .= '<div style="font-size:2rem;font-weight:800;color:' . ($avg >= 70 ? '#16A34A' : '#DC2626') . '">' . $avg . '%</div>';
$_content .= '<div style="color:#94a3b8">Average Score</div></div></div>';
$_content .= '<h2 style="margin-bottom:16px;color:#e2e8f0">Recent Scans</h2>';
$_content .= '<div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;color:#e2e8f0">';
$_content .= '<thead><tr style="background:#1e293b;text-align:right">';
$_content .= '<th style="padding:10px;color:#94a3b8">ID</th><th style="padding:10px;color:#94a3b8">URL</th><th style="padding:10px;color:#94a3b8">Score</th><th style="padding:10px;color:#94a3b8">Date</th><th></th></tr></thead><tbody>';
foreach ($recent as $r) {
    $_content .= '<tr style="border-bottom:1px solid #334155">';
    $_content .= '<td style="padding:10px">#' . $r['id'] . '</td>';
    $_content .= '<td style="padding:10px;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' . htmlspecialchars($r['url']) . '</td>';
    $_content .= '<td style="padding:10px"><strong>' . $r['overall_score'] . '</strong></td>';
    $_content .= '<td style="padding:10px;font-size:.85rem;color:#94a3b8">' . date('d/m/Y H:i', strtotime($r['created_at'])) . '</td>';
    $_content .= '<td style="padding:10px"><a href="' . $url("dashboard/report/{$r['id']}") . '" style="color:var(--primary)">View →</a></td>';
    $_content .= '</tr>';
}
$_content .= '</tbody></table></div></div></section>';

$content = $_content;
include __DIR__ . '/../partials/layout.php';
