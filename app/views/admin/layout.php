<!DOCTYPE html><html lang="he" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title><?= htmlspecialchars($pageTitle ?? 'LandingFlow') ?></title><link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700;800;900&family=IBM+Plex+Mono:wght@500;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#F9FAFB;--surface:#FFFFFF;--surface-2:#F3F4F6;--border:#E5E7EB;--ink:#111827;--ink-soft:#6B7280;--ink-faint:#9CA3AF;--primary:#2563EB;--primary-2:#3B82F6;--primary-dark:#1E40AF;--success:#16A34A;--warning:#F59E0B;--danger:#DC2626;--info:#0EA5E9;--container:1200px;--font:"Rubik","system-ui",-apple-system,sans-serif;--font-mono:"IBM Plex Mono",monospace}
*{margin:0;padding:0;box-sizing:border-box}body{font-family:var(--font);background:var(--bg);color:var(--ink);line-height:1.6}
.admin-layout{display:flex;min-height:100vh}
.sidebar{width:240px;background:#111827;color:#fff;padding:24px 20px;position:fixed;top:0;right:0;bottom:0;overflow-y:auto;z-index:700;transform:translateX(100%);transition:transform .3s ease}
.sidebar.open{transform:translateX(0)}
@media(min-width:900px){.sidebar{transform:none}}
.sidebar-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:650;opacity:0;visibility:hidden;transition:opacity .3s}
.sidebar-backdrop.open{opacity:1;visibility:visible}
@media(min-width:900px){.sidebar-backdrop{display:none}}
.sidebar .logo{display:flex;align-items:center;gap:8px;font-size:1.1rem;font-weight:800;margin-bottom:32px;color:#fff;text-decoration:none}.sidebar .logo-mark{width:30px;height:30px;border-radius:9px;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-family:var(--font-mono);font-size:.85rem}
.side-nav{display:flex;flex-direction:column;gap:4px}
.side-nav a{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;color:#9CA3AF;font-size:.9rem;font-weight:600;transition:.2s;text-decoration:none}.side-nav a:focus-visible{outline:2px solid var(--primary);outline-offset:2px}
.side-nav a:hover,.side-nav a.active{background:rgba(37,99,235,.15);color:#fff}
.main-content{margin-right:0;flex:1;padding:20px;min-width:0}
@media(min-width:900px){.main-content{margin-right:240px;padding:32px}}
.mobile-topbar{display:flex;align-items:center;gap:12px;margin-bottom:20px}
@media(min-width:900px){.mobile-topbar{display:none}}
.sidebar-toggle{width:40px;height:40px;border-radius:8px;background:var(--surface);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.top-bar{display:flex;align-items:center;justify-content:space-between;margin-bottom:32px;flex-wrap:wrap;gap:12px}
.top-bar h1{font-size:1.5rem;font-weight:800}
.kpi-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px;margin-bottom:28px}
@media(min-width:900px){.kpi-grid{grid-template-columns:repeat(4,1fr)}}
.kpi{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:20px}
.kpi-label{font-size:.78rem;color:var(--ink-soft);margin-bottom:8px}
.kpi-value{font-family:var(--font-mono);font-size:1.6rem;font-weight:700}
.kpi-change{font-size:.74rem;margin-top:4px}.kpi-change.up{color:var(--success)}.kpi-change.down{color:var(--danger)}
.panels{display:grid;grid-template-columns:1fr;gap:18px}@media(min-width:900px){.panels{grid-template-columns:1fr 1fr}}
/* Accessibility widget */
.a11y-float{position:fixed;bottom:88px;left:24px;z-index:900;width:50px;height:50px;border-radius:12px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.4rem;box-shadow:0 4px 16px rgba(0,0,0,.15);transition:transform .25s;border:none;cursor:pointer}.a11y-float:hover{transform:scale(1.08)}
.a11y-panel{position:fixed;bottom:150px;left:24px;z-index:901;background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:16px;width:220px;box-shadow:0 8px 30px rgba(0,0,0,.12);display:none;flex-direction:column;gap:6px}.a11y-panel.open{display:flex}.a11y-panel h4{font-size:.85rem;font-weight:700;margin-bottom:6px;text-align:center}
.a11y-btn{width:100%;padding:9px 14px;font-size:.78rem;font-weight:600;border:1px solid var(--border);border-radius:8px;background:var(--bg);cursor:pointer;transition:all .15s;font-family:inherit;text-align:center}.a11y-btn:hover{background:var(--primary);color:#fff;border-color:var(--primary)}.a11y-reset{color:var(--danger);border-color:rgba(220,38,38,.2)}.a11y-reset:hover{background:var(--danger);color:#fff;border-color:var(--danger)}
body.high-contrast{filter:contrast(1.4) grayscale(.3)}body.large-text{font-size:115%}body.no-anim *,body.no-anim *::before,body.no-anim *::after{animation-duration:.001s!important;transition-duration:.001s!important}
.whatsapp-float{position:fixed;bottom:24px;left:24px;z-index:900;width:50px;height:50px;border-radius:12px;background:#25D366;color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.3rem;box-shadow:0 4px 16px rgba(37,211,102,.2);transition:transform .25s}.whatsapp-float:hover{transform:scale(1.08)}
.panel{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:20px}
.panel h3{font-size:.95rem;font-weight:700;margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid var(--border)}
.quick-row{display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:.85rem}
.quick-row:last-child{border:none}
.badge{display:inline-block;font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:100px}
.alert{padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:.85rem}
.badge-new{background:rgba(37,99,235,.1);color:var(--primary)}.badge-won{background:rgba(22,163,74,.12);color:var(--success)}.badge-active{background:rgba(14,165,233,.1);color:var(--info)}
</style></head><body>
<div class="admin-layout">
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<aside class="sidebar" id="sidebar">
  <a href="<?= $url('') ?>" class="logo"><span class="logo-mark">LF</span>LandingFlow</a>
  <nav class="side-nav">
    <?php
    // The dashboard link is active only when no other section owns the URL.
    $adminUri = $_SERVER['REQUEST_URI'] ?? '';
    $adminSections = ['/admin/monitoring', '/admin/leads', '/admin/lead-engine', '/admin/projects',
                      '/admin/hosting', '/admin/audit-reports', '/admin/receipts', '/admin/dashboard'];
    $inSection = false;
    foreach ($adminSections as $adminSection) {
        if (str_contains($adminUri, $adminSection)) { $inSection = true; break; }
    }
    ?>
    <a href="<?= $url('admin') ?>" class="<?= $inSection ? '' : 'active' ?>">📊 דשבורד</a>
    <a href="<?= $url('admin/leads') ?>" class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/leads') ? 'active' : '' ?>">📇 לידים</a>
    <a href="<?= $url('admin/lead-engine') ?>" class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/lead-engine') ? 'active' : '' ?>">🎯 מנוע לידים</a>
    <a href="<?= $url('admin/projects') ?>" class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/projects') ? 'active' : '' ?>">📁 פרויקטים</a>
    <a href="<?= $url('admin/hosting') ?>" class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/hosting') ? 'active' : '' ?>">☁️ אחסון</a>
    <a href="<?= $url('admin/monitoring') ?>" class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/monitoring') ? 'active' : '' ?>">📡 ניטור</a>
    <a href="<?= $url('admin/audit-reports') ?>" class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/audit-reports') ? 'active' : '' ?>">🔍 ביקורות</a>
    <a href="<?= $url('admin/receipts') ?>" class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/receipts') ? 'active' : '' ?>">🧾 קבלות</a>
    <a href="<?= $url('logout') ?>" style="margin-top:24px;color:var(--danger)">🚪 יציאה</a>
  </nav>
</aside>
<main class="main-content">
<div class="mobile-topbar">
  <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="תפריט">☰</button>
  <span class="logo"><span class="logo-mark">LF</span>LandingFlow</span>
</div>
<?= $content ?? '' ?>
</main></div>

<?php include __DIR__ . '/../partials/widgets.php'; ?>

<script>
(function(){
  var waFloat=document.getElementById("waFloat"),waTooltip=document.getElementById("waTooltip");
  if(waFloat&&waTooltip){waFloat.addEventListener("mouseenter",function(){waTooltip.classList.add("visible")});waFloat.addEventListener("mouseleave",function(){waTooltip.classList.remove("visible")})}
  var a11yToggle=document.getElementById("a11yToggle"),a11yPanel=document.getElementById("a11yPanel");
  if(a11yToggle){a11yToggle.addEventListener("click",function(e){e.stopPropagation();a11yPanel.classList.toggle("open")});document.addEventListener("click",function(e){if(!a11yPanel.contains(e.target)&&e.target!==a11yToggle)a11yPanel.classList.remove("open")})}
  var sidebar=document.getElementById("sidebar"),sidebarToggle=document.getElementById("sidebarToggle"),sidebarBackdrop=document.getElementById("sidebarBackdrop");
  function closeSidebar(){sidebar.classList.remove("open");sidebarBackdrop.classList.remove("open")}
  if(sidebarToggle){sidebarToggle.addEventListener("click",function(){sidebar.classList.toggle("open");sidebarBackdrop.classList.toggle("open")})}
  if(sidebarBackdrop){sidebarBackdrop.addEventListener("click",closeSidebar)}
})();
</script>
</body></html>
