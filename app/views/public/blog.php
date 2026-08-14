<?php ob_start(); ?>
<style>
.blog-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;max-width:1050px;margin:0 auto}
.blog-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:28px 24px;display:flex;flex-direction:column;gap:12px;transition:all .25s;text-decoration:none;color:inherit}
.blog-card:hover{border-color:var(--signal);box-shadow:var(--shadow-md);transform:translateY(-2px)}
.blog-card .cat{font-family:var(--font-mono);font-size:.68rem;font-weight:600;letter-spacing:.02em;color:var(--signal);background:var(--signal-soft);padding:3px 9px;border-radius:4px;align-self:flex-start}
.blog-card h2{font-size:1.15rem;font-weight:700;line-height:1.35}
.blog-card p{color:var(--ink-soft);font-size:.92rem;line-height:1.6;flex:1}
.blog-card .meta{color:var(--ink-faint);font-size:.78rem;font-family:var(--font-mono)}
.blog-empty{text-align:center;color:var(--ink-soft);padding:60px 0}
</style>

<section style="padding-top:140px"><div class="container">
  <div class="head-center" style="margin-bottom:50px">
    <span class="section-eyebrow">בלוג</span>
    <h1 class="section-title" style="margin:0 auto 12px">טיפים ומדריכים לבעלי עסקים</h1>
    <p class="section-sub" style="margin:0 auto 42px">מדריכים מעשיים על אתרים, נגישות, תמחור ובחירת ספק טכנולוגי — בעברית פשוטה, בלי ז'רגון.</p>
  </div>

<?php if (empty($posts)): ?>
  <p class="blog-empty">אין עדיין פוסטים. חוזרים בקרוב.</p>
<?php else: ?>
<div class="blog-grid">
<?php foreach ($posts as $post): ?>
<a href="<?= $url('blog/' . $post['slug']) ?>" class="blog-card">
  <?php if (!empty($post['category_name'])): ?><span class="cat"><?= htmlspecialchars($post['category_name']) ?></span><?php endif; ?>
  <h2><?= htmlspecialchars($post['title']) ?></h2>
  <p><?= htmlspecialchars($post['excerpt'] ?? '') ?></p>
  <span class="meta"><?= $post['published_at'] ? date('d.m.Y', strtotime($post['published_at'])) : '' ?></span>
</a>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div></section>

<?php $content = ob_get_clean(); include __DIR__ . "/../partials/layout.php"; ?>
