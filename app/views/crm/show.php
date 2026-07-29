<?php ob_start() ?>
<div class="top-bar"><h1><?= htmlspecialchars($lead['name']) ?></h1>
  <div style="display:flex;gap:8px">
    <a href="<?= $url('admin/leads/' . $lead['id'] . '/edit') ?>" class="btn btn-primary">ערוך</a>
    <a href="<?= $url('admin/leads') ?>" class="btn btn-ghost">← חזרה</a>
  </div>
</div>

<style>
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}
@media(min-width:900px){.detail-grid{grid-template-columns:1fr 1fr}}
.detail-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:22px}
.detail-card h3{font-size:.92rem;font-weight:700;margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border)}
.detail-row{display:flex;justify-content:space-between;padding:8px 0;font-size:.85rem;border-bottom:1px solid var(--border)}.detail-row:last-child{border:none}
.detail-label{color:var(--ink-soft)}.detail-value{font-weight:600}
.status-badge{display:inline-block;font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:100px}.s-new{background:rgba(37,99,235,.1);color:var(--primary)}.s-won{background:rgba(22,163,74,.15);color:var(--success)}.s-lost{background:rgba(220,38,38,.1);color:var(--danger)}
.status-form{display:flex;gap:8px;align-items:center;margin-bottom:16px}
.status-form select{padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:.85rem}
.note-list{margin-top:12px}.note-item{background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:12px 14px;margin-bottom:8px;font-size:.84rem}.note-item .meta{color:var(--ink-faint);font-size:.72rem;margin-bottom:4px}
.note-form{margin-top:16px}.note-form textarea{width:100%;padding:10px 12px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:.84rem;resize:vertical;min-height:60px}
.note-form .row{display:flex;gap:8px;margin-top:8px;align-items:center}
.note-form select{padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:.82rem}
</style>

<?php $succ = $flash('success'); if ($succ): ?><div class="alert-success"><?= htmlspecialchars($succ) ?></div><?php endif; ?>

<div class="detail-grid">
  <div class="detail-card">
    <h3>פרטי ליד</h3>
    <div class="detail-row"><span class="detail-label">שם</span><span class="detail-value"><?= htmlspecialchars($lead['name']) ?></span></div>
    <div class="detail-row"><span class="detail-label">אימייל</span><span class="detail-value"><?= htmlspecialchars($lead['email'] ?? '-') ?></span></div>
    <div class="detail-row"><span class="detail-label">טלפון</span><span class="detail-value"><?= htmlspecialchars($lead['phone'] ?? '-') ?></span></div>
    <div class="detail-row"><span class="detail-label">חברה</span><span class="detail-value"><?= htmlspecialchars($lead['company'] ?? '-') ?></span></div>
    <div class="detail-row"><span class="detail-label">אתר</span><span class="detail-value"><?= htmlspecialchars($lead['website'] ?? '-') ?></span></div>
    <div class="detail-row"><span class="detail-label">מקור</span><span class="detail-value"><?= $lead['source'] ?></span></div>
    <div class="detail-row"><span class="detail-label">עניין</span><span class="detail-value"><?= htmlspecialchars($lead['interest'] ?? '-') ?></span></div>
    <div class="detail-row"><span class="detail-label">תקציב</span><span class="detail-value"><?= $lead['budget'] ? '₪' . number_format($lead['budget']) : '-' ?></span></div>
  </div>
  <div>
    <div class="detail-card" style="margin-bottom:18px">
      <h3>סטטוס</h3>
      <form method="POST" action="<?= $url('admin/leads/' . $lead['id'] . '/status') ?>" class="status-form">
        <select name="status">
          <?php foreach(['new'=>'חדש','contacted'=>'נוצר קשר','qualified'=>'מתאים','proposal_sent'=>'הצעה נשלחה','negotiation'=>'משא ומתן','won'=>'נסגר','lost'=>'אבוד'] as $k=>$v): ?>
          <option value="<?= $k ?>" <?= $lead['status']===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">עדכן סטטוס</button>
      </form>
      <div>סטטוס נוכחי: <span class="status-badge s-<?= $lead['status'] ?>"><?= $lead['status'] ?></span></div>
      <?php if ($lead['notes']): ?><div style="margin-top:12px;padding:12px;background:var(--surface-2);border-radius:8px;font-size:.84rem"><?= nl2br(htmlspecialchars($lead['notes'])) ?></div><?php endif; ?>
    </div>

    <div class="detail-card">
      <h3>הערות (<?= count($notes) ?>)</h3>
      <div class="note-list">
        <?php foreach ($notes as $n): ?>
        <div class="note-item"><div class="meta"><?= $n['author'] ?? 'מערכת' ?> · <?= date('d/m/Y H:i', strtotime($n['created_at'])) ?> · <?= $n['type'] ?></div><?= nl2br(htmlspecialchars($n['content'])) ?></div>
        <?php endforeach; ?>
      </div>
      <form method="POST" action="<?= $url('admin/leads/' . $lead['id'] . '/note') ?>" class="note-form">
        <textarea name="content" placeholder="הוסף הערה..."></textarea>
        <div class="row">
          <select name="type"><option value="note">הערה</option><option value="call">שיחה</option><option value="email">אימייל</option><option value="meeting">פגישה</option><option value="whatsapp">וואטסאפ</option></select>
          <button type="submit" class="btn btn-primary btn-sm">הוסף</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../admin/layout.php'; ?>
