<?php ob_start(); $sites = $sites ?? []; ?>
<section style="padding-top:96px;padding-bottom:56px"><div class="container">
  <div class="head-center" style="margin-bottom:16px">
    <span class="section-eyebrow">תיק עבודות</span>
    <h1 class="section-title" style="margin:0 auto 6px;font-size:clamp(1.6rem,3.6vw,2.3rem)">ראו אתרים שבנינו</h1>
    <p class="section-sub" style="margin:0 auto 4px">גללו בין האתרים שיצרנו. כל אתר נטען בתצוגה מקדימה חיה.</p>
    <p style="font-family:var(--font-mono);font-size:.76rem;color:var(--ink-faint);margin:0 auto">✦ כל אתר כאן נבנה אישית, אחד אחד — לא מתבנית גנרית</p>
  </div>

  <style>
    .carousel-wrap{position:relative;max-width:1000px;margin:0 auto}
    .carousel-slides{display:flex;overflow-x:auto;scroll-snap-type:x mandatory;scroll-behavior:smooth;gap:0;-webkit-overflow-scrolling:touch;scrollbar-width:none;border-radius:16px;border:1px solid var(--border)}
    .carousel-slides::-webkit-scrollbar{display:none}
    .carousel-slide{flex:0 0 100%;scroll-snap-align:start;background:var(--surface);padding:16px}
    .slide-name{font-size:1.05rem;font-weight:700;margin-bottom:8px;display:flex;align-items:center;justify-content:space-between}
    .slide-name-link{color:var(--ink);text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:color .15s}
    .slide-name-link:hover{color:var(--signal)}
    .slide-name-link span{font-size:.9em}
    .slide-url{font-family:var(--font-mono);font-size:.75rem;color:var(--ink-faint);direction:ltr}
    .carousel-iframe{width:100%;height:min(430px,55vh);border:1px solid var(--border);border-radius:12px}
    .carousel-dots{display:flex;justify-content:center;gap:8px;margin-top:14px}
    .carousel-dot{width:10px;height:10px;border-radius:50%;background:var(--border);cursor:pointer;transition:.2s}
    .carousel-dot.active{background:var(--primary);width:28px;border-radius:100px}
    .carousel-nav{position:absolute;top:50%;transform:translateY(-50%);width:44px;height:44px;border-radius:50%;background:var(--surface);border:1.5px solid var(--border);font-size:1.1rem;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:10;box-shadow:var(--shadow-sm);transition:.2s;color:var(--ink)}
    .carousel-nav:hover{background:var(--primary);color:#fff;border-color:var(--primary)}
    .carousel-prev{left:-18px}.carousel-next{right:-18px}
    @media(max-width:760px){.carousel-nav{display:none}.carousel-slide{padding:12px}.carousel-iframe{height:min(340px,50vh)}}
  </style>

  <div class="carousel-wrap">
    <button class="carousel-nav carousel-prev" onclick="slideCarousel(-1)" aria-label="הקודם">◀</button>
    <button class="carousel-nav carousel-next" onclick="slideCarousel(1)" aria-label="הבא">▶</button>
    <div class="carousel-slides" id="carouselSlides">
      <?php foreach ($sites as $i => $site): ?>
      <div class="carousel-slide" id="slide<?= $i ?>">
        <div class="slide-name">
          <a href="<?= htmlspecialchars($site['url']) ?>" target="_blank" rel="noopener" class="slide-name-link"><?= htmlspecialchars($site['name']) ?> <span aria-hidden="true">↗</span></a>
        </div>
        <?php if ($i === 0): ?>
        <iframe src="<?= $site['url'] ?>" class="carousel-iframe" title="<?= htmlspecialchars($site['name']) ?>"></iframe>
        <?php else: ?>
        <iframe data-src="<?= $site['url'] ?>" class="carousel-iframe" title="<?= htmlspecialchars($site['name']) ?>"></iframe>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="carousel-dots" id="carouselDots">
      <?php for ($i = 0; $i < count($sites); $i++): ?>
      <div class="carousel-dot<?= $i===0?' active':'' ?>" data-index="<?= $i ?>" onclick="goToSlide(<?= $i ?>)"></div>
      <?php endfor; ?>
    </div>
  </div>

  <div style="text-align:center;margin-top:48px">
    <p style="color:var(--ink-soft);margin-bottom:16px">רוצים אתר כמו אלה? דברו איתנו.</p>
    <a href="<?= $url('contact') ?>" class="btn btn-primary btn-lg">📋 צור קשר</a>
  </div>
</div>

<script>
var currentSlide = 0, totalSlides = <?= count($sites) ?>, scrolling = false;
function loadSlideIframe(n) {
  var slide = document.getElementById('slide' + n);
  if (!slide) return;
  var iframe = slide.querySelector('iframe[data-src]');
  if (iframe) {
    iframe.src = iframe.getAttribute('data-src');
    iframe.removeAttribute('data-src');
  }
}
function goToSlide(n) {
  currentSlide = n;
  scrolling = true;
  loadSlideIframe(n);
  var slide = document.getElementById('slide' + n);
  if (slide) slide.scrollIntoView({behavior:'smooth',block:'nearest',inline:'start'});
  document.querySelectorAll('.carousel-dot').forEach(function(d,i){ d.classList.toggle('active', i===n); });
  setTimeout(function(){ scrolling = false; }, 600);
}
function slideCarousel(dir) {
  var n = (currentSlide + dir + totalSlides) % totalSlides;
  goToSlide(n);
}
document.getElementById('carouselSlides').addEventListener('scroll', function(){
  if (scrolling) return;
  var w = this.offsetWidth;
  var idx = Math.round(this.scrollLeft / w);
  if (idx !== currentSlide) { currentSlide = idx; loadSlideIframe(idx); document.querySelectorAll('.carousel-dot').forEach(function(d,i){ d.classList.toggle('active', i===idx); }); }
});
</script>
</section>

<?php $content = ob_get_clean(); include __DIR__ . '/../partials/layout.php'; ?>
