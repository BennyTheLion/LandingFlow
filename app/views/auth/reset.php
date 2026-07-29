<?php ob_start() ?>
<section style="padding-top:140px"><div class="container" style="max-width:440px"><div style="background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:36px 28px;box-shadow:var(--shadow-md)">
  <h1 style="font-size:1.4rem;font-weight:800;margin-bottom:6px;text-align:center">איפוס סיסמה</h1>
  <p style="text-align:center;color:var(--ink-soft);font-size:.9rem;margin-bottom:28px">הזינו סיסמה חדשה לחשבון שלכם.</p>
  <?php $err = $flash("error"); if ($err): ?><div class="alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <form method="POST" action="<?= $url("reset-password") ?>"><?= $csrf() ?>
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    <div class="form-group"><label>סיסמה חדשה</label><div class="pw-wrap"><input type="password" name="password" required minlength="8"><button type="button" class="pw-toggle" onclick="togglePw(this)" title="הצג/הסתר סיסמה">👁️</button></div></div>
    <div class="form-group"><label>אימות סיסמה</label><div class="pw-wrap"><input type="password" name="password_confirm" required minlength="8"><button type="button" class="pw-toggle" onclick="togglePw(this)" title="הצג/הסתר סיסמה">👁️</button></div></div>
    <button type="submit" class="btn btn-primary btn-block">איפוס סיסמה</button>
  </form>
</div></div></section>
<?php $content = ob_get_clean(); include __DIR__ . "/../partials/layout.php"; ?>
