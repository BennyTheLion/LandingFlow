<?php // shared header — use inside layout.php ?>
<header class="nav" id="nav"><div class="nav-inner">
  <a href="<?= $url('') ?>" class="logo"><span class="logo-mark">LF</span>LandingFlow</a>
  <nav class="nav-links" aria-label="ניווט ראשי">
    <a href="<?= $url('') ?>">בית</a>
    <a href="<?= $url('services') ?>">שירותים</a>
    <a href="<?= $url('demo') ?>">דמו 🚀</a>
    <a href="<?= $url('pricing') ?>">מחירים</a>
    <a href="<?= $url('portfolio') ?>">תיק עבודות</a>
    <a href="<?= $url('audit') ?>">בדיקת אתר</a>
    <a href="<?= $url('about') ?>">אודות</a>
    <a href="<?= $url('contact') ?>">צור קשר</a>
  </nav>
  <div class="nav-actions">
    <?php if ($isAuthenticated()): ?>
      <a href="<?= $url('admin') ?>" title="דשבורד" class="nav-icon-btn"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg></a>
      <a href="<?= $url('logout') ?>" title="התנתקות" class="nav-icon-btn logout"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></a>
    <?php else: ?>
      <a href="<?= $url('login') ?>" class="btn btn-ghost btn-sm">התחברות</a>
      <a href="<?= $url('register') ?>" class="btn btn-primary btn-sm">הרשמה</a>
    <?php endif; ?>
  </div>
  <button class="burger" id="burger" aria-label="פתח תפריט"><span></span><span></span><span></span></button>
</div></header>

<div class="mobile-menu" id="mobileMenu"><div class="menu-backdrop" id="menuBackdrop"></div><div class="menu-panel">
  <div class="menu-head"><a href="<?= $url('') ?>" class="logo"><span class="logo-mark">LF</span>LandingFlow</a><button class="menu-close" id="menuClose">✕</button></div>
  <nav class="mobile-links">
    <a href="<?= $url('') ?>">בית</a>
    <a href="<?= $url('services') ?>">שירותים</a>
    <a href="<?= $url('pricing') ?>">מחירים</a>
    <a href="<?= $url('portfolio') ?>">תיק עבודות</a>
    <a href="<?= $url('audit') ?>">בדיקת אתר</a>
    <a href="<?= $url('about') ?>">אודות</a>
    <a href="<?= $url('contact') ?>">צור קשר</a>
  </nav>
  <div class="menu-foot">
    <?php if ($isAuthenticated()): ?>
      <a href="<?= $url('admin') ?>" class="btn btn-primary btn-block">📊 דשבורד</a>
      <a href="<?= $url('logout') ?>" style="display:block;text-align:center;margin-top:10px;color:var(--ink-soft);font-size:.9rem">🚪 התנתקות</a>
    <?php else: ?>
      <a href="<?= $url('login') ?>" class="btn btn-ghost btn-block">התחברות</a>
      <a href="<?= $url('register') ?>" class="btn btn-primary btn-block" style="margin-top:8px">הרשמה</a>
    <?php endif; ?>
  </div>
</div></div>
