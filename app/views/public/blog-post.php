<?php ob_start(); ?>
<style>
.post-wrap{max-width:720px;margin:0 auto}
.post-back{display:inline-flex;align-items:center;gap:6px;color:var(--ink-soft);font-size:.86rem;font-weight:600;margin-bottom:24px}
.post-back:hover{color:var(--signal)}
.post-meta{display:flex;align-items:center;gap:10px;margin-bottom:14px}
.post-meta .cat{font-family:var(--font-mono);font-size:.68rem;font-weight:600;letter-spacing:.02em;color:var(--signal);background:var(--signal-soft);padding:3px 9px;border-radius:4px}
.post-meta .date{color:var(--ink-faint);font-size:.82rem;font-family:var(--font-mono)}
.post-title{font-size:clamp(1.6rem,4vw,2.3rem);font-weight:800;line-height:1.2;letter-spacing:-.02em;margin-bottom:40px}
.post-body{color:var(--ink);font-size:1.02rem;line-height:1.85}
.post-body h2{font-family:var(--font-serif);font-size:1.4rem;font-weight:700;margin:40px 0 14px}
.post-body h3{font-size:1.15rem;font-weight:700;margin:28px 0 10px}
.post-body p{margin-bottom:18px;color:var(--ink-soft)}
.post-body ul,.post-body ol{margin:0 0 18px;padding-inline-start:22px;color:var(--ink-soft)}
.post-body li{margin-bottom:8px;line-height:1.7}
.post-body strong{color:var(--ink);font-weight:700}
.post-body a{color:var(--signal);text-decoration:underline}
.post-cta{margin-top:56px;background:var(--ink);color:#fff;border-radius:16px;padding:36px 30px;text-align:center}
.post-cta h3{font-size:1.3rem;font-weight:800;margin-bottom:10px}
.post-cta p{color:rgba(255,255,255,.65);margin-bottom:22px}
</style>

<section style="padding-top:140px"><div class="container">
  <div class="post-wrap">
    <a href="<?= $url('blog') ?>" class="post-back">→ חזרה לבלוג</a>
    <div class="post-meta">
      <?php if (!empty($post['category_name'])): ?><span class="cat"><?= htmlspecialchars($post['category_name']) ?></span><?php endif; ?>
      <?php if (!empty($post['published_at'])): ?><span class="date"><?= date('d.m.Y', strtotime($post['published_at'])) ?></span><?php endif; ?>
    </div>
    <h1 class="post-title"><?= htmlspecialchars($post['title']) ?></h1>
    <div class="post-body"><?= $post['content'] ?></div>

    <div class="post-cta">
      <h3>מוכנים להתחיל?</h3>
      <p>בואו נדבר על מה שהעסק שלכם צריך — בלי התחייבות.</p>
      <a href="<?= $url('contact') ?>" class="btn btn-primary btn-lg" style="background:#fff;color:var(--ink)">צור קשר</a>
    </div>
  </div>
</div></section>

<script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $post['title'],
    'description' => $post['excerpt'] ?? '',
    'datePublished' => $post['published_at'] ?? $post['created_at'],
    'dateModified' => $post['updated_at'] ?? $post['published_at'] ?? $post['created_at'],
    'inLanguage' => 'he',
    'author' => ['@type' => 'Organization', 'name' => 'LandingFlow'],
    'publisher' => ['@type' => 'Organization', 'name' => 'LandingFlow'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

<?php $content = ob_get_clean(); include __DIR__ . "/../partials/layout.php"; ?>
