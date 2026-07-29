<?php
$_c = '';
$_c .= '<section style="padding-top:100px"><div class="container">';
$_c .= '<a href="' . $url("admin/leads") . '" style="color:var(--primary)">← Leads</a>';
$_c .= '<h1 style="font-size:1.4rem;margin:12px 0 24px">Lead #' . $leadId . ' · Scan Reports</h1>';
if (empty($reports)):
  $_c .= '<p style="color:var(--ink-soft)">No scan reports for this lead yet.</p>';
else:
  $_c .= '<div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse">';
  $_c .= '<thead><tr style="background:var(--surface);text-align:right">';
  $_c .= '<th style="padding:10px">ID</th><th style="padding:10px">URL</th><th style="padding:10px">Score</th><th style="padding:10px">Date</th><th></th></tr></thead><tbody>';
  foreach ($reports as $r):
    $_c .= '<tr style="border-bottom:1px solid var(--border)">';
    $_c .= '<td style="padding:10px">#' . $r['id'] . '</td>';
    $_c .= '<td style="padding:10px;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' . htmlspecialchars($r['url']) . '</td>';
    $_c .= '<td style="padding:10px"><strong>' . $r['overall_score'] . '</strong></td>';
    $_c .= '<td style="padding:10px;font-size:.85rem;color:var(--ink-soft)">' . date('d/m/Y H:i', strtotime($r['created_at'])) . '</td>';
    $_c .= '<td style="padding:10px"><a href="' . $url("dashboard/report/{$r['id']}") . '" style="color:var(--primary)">View →</a></td>';
    $_c .= '</tr>';
  endforeach;
  $_c .= '</tbody></table></div>';
endif;
$_c .= '</div></section>';
$content = $_c;
include __DIR__ . '/../partials/layout.php';
