<?php ob_start() ?>
<style>
.contact-hero{padding-top:96px}
.contact-grid{display:grid;grid-template-columns:1fr;gap:24px;max-width:900px;margin:0 auto}
@media(min-width:760px){.contact-grid{grid-template-columns:1.4fr 1fr;align-items:start}}
.contact-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:32px;box-shadow:var(--shadow-md)}
.contact-info{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:32px}
.contact-info h3{font-size:1.1rem;font-weight:700;margin-bottom:18px}
.contact-info-row{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--border);font-size:.9rem;color:var(--ink-soft)}
.contact-info-row:last-child{border-bottom:none}
</style>
<section class="contact-hero"><div class="container"><div class="head-center" style="margin-bottom:24px"><h1 class="section-title" style="margin:0 auto 8px">צור קשר</h1><p class="section-sub" style="margin:0 auto">יש שאלה? רוצה לשמוע עוד? מלא את הטופס ונחזור אליך בהקדם.</p></div>
<?php $succ = $flash("success"); if ($succ): ?><div class="alert-success" style="max-width:900px;margin:0 auto 16px"><?= htmlspecialchars($succ) ?></div><?php endif; ?>
<?php $err = $flash("error"); if ($err): ?><div class="alert-error" style="max-width:900px;margin:0 auto 16px"><?= htmlspecialchars($err) ?></div><?php endif; ?>
<div class="contact-grid">
  <div class="contact-card">
    <form method="POST" action="<?= $url("contact") ?>"><?= $csrf() ?>
      <div class="form-group"><label>שם מלא *</label><input type="text" name="name" required value="<?= $old("name") ?>"></div>
      <div class="form-group"><label>אימייל *</label><input type="email" name="email" required value="<?= $old("email") ?>"></div>
      <div class="form-group"><label>טלפון *</label><input type="tel" name="phone" required value="<?= $old("phone") ?>"></div>
      <div class="form-group"><label>הודעה *</label><textarea name="message" rows="4" required><?= $old("message") ?></textarea></div>
      <div class="check-row"><input type="checkbox" name="consent" id="consent" value="1" required><label for="consent">אני מאשר/ת את <a href="<?= $url("privacy-policy") ?>" target="_blank">מדיניות הפרטיות</a> ומסכים/ה לשמירת הפרטים שלי.</label></div>
      <button type="submit" class="btn btn-primary btn-block">שלח הודעה</button>
    </form>
  </div>
  <div class="contact-info">
    <h3>פרטי התקשרות</h3>
    <div class="contact-info-row">📞 <span>052-8529448</span></div>
    <div class="contact-info-row">📧 <span>info@landingflow.co.il</span></div>
    <div class="contact-info-row">🕐 <span>א-ה 09:00-18:00</span></div>
  </div>
</div>
</div></section>
<?php $content = ob_get_clean(); include __DIR__ . "/../partials/layout.php"; ?>
