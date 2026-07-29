<?php ob_start() ?>
<section style="padding-top:140px"><div class="container" style="max-width:440px"><div style="background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:36px 28px;box-shadow:var(--shadow-md)">
  <h1 style="font-size:1.4rem;font-weight:800;margin-bottom:6px;text-align:center">התחברות</h1>
  <p style="text-align:center;color:var(--ink-soft);font-size:.9rem;margin-bottom:28px">ברוכים השבים. התחברו לחשבון שלכם.</p>
  <?php $err = $flash("error"); if ($err): ?><div class="alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php $succ = $flash("success"); if ($succ): ?><div class="alert-success"><?= htmlspecialchars($succ) ?></div><?php endif; ?>
  <form method="POST" action="<?= $url("login") ?>"><?= $csrf() ?>
    <div class="form-group"><label>אימייל</label><input type="email" name="email" value="<?= $old("email") ?>" required autofocus></div>
    <div class="form-group"><label>סיסמה</label><div class="pw-wrap"><input type="password" name="password" required><button type="button" class="pw-toggle" onclick="togglePw(this)" title="הצג/הסתר סיסמה">👁️</button></div></div>
    <button type="submit" class="btn btn-primary btn-block">התחברות</button>
  </form>
  <p style="text-align:center;margin-top:20px;font-size:.88rem;color:var(--ink-soft)"><a href="<?= $url("forgot-password") ?>" style="color:var(--primary)">שכחת סיסמה?</a> · אין לך חשבון? <a href="<?= $url("register") ?>" style="color:var(--primary);font-weight:600">הרשמה</a></p>
</div></div></section>
<?php $content = ob_get_clean(); include __DIR__ . "/../partials/layout.php"; ?>
