<?php // Floating widgets — included in all layouts ?>

<!-- WhatsApp Floating -->
<a href="https://wa.me/972528529448" target="_blank" rel="noopener" class="whatsapp-float" id="waFloat" aria-label="WhatsApp" title="דברו איתנו בוואטסאפ!">💬</a>

<!-- Accessibility Floating -->
<button class="a11y-float" id="a11yToggle" aria-label="נגישות" title="תפריט נגישות">♿</button>
<div class="a11y-panel" id="a11yPanel">
  <h4>הגדרות נגישות</h4>
  <button class="a11y-btn" onclick="document.body.classList.toggle('high-contrast')">ניגודיות גבוהה</button>
  <button class="a11y-btn" onclick="document.body.classList.toggle('large-text')">הגדלת טקסט</button>
  <button class="a11y-btn" onclick="document.body.classList.toggle('no-anim')">ביטול אנימציות</button>
  <button class="a11y-btn a11y-reset" onclick="document.body.removeAttribute('class')">איפוס כל ההגדרות</button>
</div>

<script>
(function(){
  var t = document.getElementById('a11yToggle');
  var p = document.getElementById('a11yPanel');
  if (!t || !p) return;
  t.addEventListener('click', function(e){ e.stopPropagation(); p.classList.toggle('open'); });
  document.addEventListener('click', function(e){ if (!p.contains(e.target) && e.target !== t) p.classList.remove('open'); });
  // persist settings
  var classes = ['high-contrast','large-text','no-anim'];
  classes.forEach(function(c){ if (localStorage.getItem('a11y_'+c) === '1') document.body.classList.add(c); });
  classes.forEach(function(c){
    var obs = new MutationObserver(function(){ localStorage.setItem('a11y_'+c, document.body.classList.contains(c) ? '1' : '0'); });
    obs.observe(document.body, {attributes: true, attributeFilter: ['class']});
  });
})();
</script>
