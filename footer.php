<?php
if (!defined('BASE')) require_once __DIR__ . '/includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$B = BASE;
?>

<!-- ═══════════════════════════════════════════════════════
     LUXURY FOOTER
     ═══════════════════════════════════════════════════════ -->
<footer class="lx-footer">
  <div class="container">
    <div class="lx-footer-grid">

      <!-- Brand column -->
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px">
          <div style="width:34px;height:34px;background:var(--gold);display:flex;align-items:center;justify-content:center;font-family:var(--cinzel);font-size:14px;color:#fff;clip-path:polygon(50% 0%,93% 25%,93% 75%,50% 100%,7% 75%,7% 25%)">RV</div>
          <div class="footer-brand">Royale Vista</div>
        </div>
        <p class="footer-desc">Where timeless elegance meets modern luxury. Every stay is a journey into the extraordinary.</p>
        <div class="footer-social">
          <?php foreach ([['fab fa-instagram','#'],['fab fa-facebook-f','#'],['fab fa-x-twitter','#'],['fab fa-linkedin-in','#'],['fab fa-whatsapp','#tel:+9189899999897'],['fab fa-google-play','#https://play.google.com/store'],['fab fa-tripadvisor','#'],['fab fa-youtube','#']] as [$icon,$url]): ?>
          <a href="<?= $url ?>" <?= strpos($url, 'tel:') !== false ? 'target="_blank"' : '' ?>><i class="<?= $icon ?>"></i></a>
          <?php endforeach; ?>
        </div>
        <div style="margin-top:20px;font-size:12px;color:rgba(255,255,255,.3);line-height:2">
          <div><i class="fas fa-map-marker-alt" style="color:var(--gold);width:16px"></i> 1 Park Avenue, Midtown Manhattan, New York, NY 10016, USA</div>
          <div><i class="fas fa-phone" style="color:var(--gold);width:16px"></i> +1 (212) 555‑0199</div>
          <div><i class="fas fa-envelope" style="color:var(--gold);width:16px"></i> stay@royalevista.com</div>
        </div>
      </div>

      <!-- Accommodation -->
      <div>
        <div class="footer-title">Accommodation</div>
        <a href="<?= $B ?>/rooms.php" class="footer-link">Rooms & Suites</a>
        <a href="<?= $B ?>/rooms.php?type=1" class="footer-link">Deluxe Room</a>
        <a href="<?= $B ?>/rooms.php?type=2" class="footer-link">Exclusive Room</a>
        <a href="<?= $B ?>/rooms.php?type=3" class="footer-link">Family Room</a>
        <a href="<?= $B ?>/rooms.php?type=4" class="footer-link">Presidential Suite</a>
        <a href="<?= $B ?>/profile.php" class="footer-link">My Profile</a>
        <a href="<?= $B ?>/membership.php" class="footer-link">Membership</a>
      </div>

      <!-- Experiences -->
      <div>
        <div class="footer-title">Experiences</div>
        <a href="<?= $B ?>/dining.php"   class="footer-link">Fine Dining</a>
        <a href="<?= $B ?>/spa.php"      class="footer-link">Spa & Wellness</a>
        <a href="<?= $B ?>/services.php" class="footer-link">Concierge</a>
        <a href="<?= $B ?>/gallery.php"  class="footer-link">Gallery</a>
        <a href="<?= $B ?>/about.php"    class="footer-link">Our Story</a>
        <a href="<?= $B ?>/contact.php"  class="footer-link">Contact</a>
        <a href="<?= $B ?>/sustainability.php" class="footer-link">Sustainability</a>
        <a href="<?= $B ?>/partners.php" class="footer-link">Global Partners</a>
        <a href="<?= $B ?>/careers.php"  class="footer-link">Careers</a>
      </div>

      <!-- Newsletter -->
      <div>
        <div class="footer-title">Newsletter</div>
        <p style="font-size:12px;color:var(--muted);margin-bottom:15px">Subscribe for exclusive offers and seasonal updates.</p>
        <form id="nlForm" style="display:flex;gap:5px" onsubmit="event.preventDefault(); fetch('<?= $B ?>/api/newsletter.php', {method:'POST', body:new FormData(this)}).then(r=>r.json()).then(d=>{ alert(d.msg); if(d.success) this.reset(); })">
          <input type="email" name="email" placeholder="Your email" required style="background:var(--card2);border:1px solid var(--bdr2);padding:8px 12px;font-size:12px;color:#fff;flex:1;border-radius:2px">
          <button class="btn-gold" style="padding:8px 15px;font-size:11px">Join</button>
        </form>
      </div>

    </div>

    <div class="footer-bottom">
      <div>&copy; <?= date('Y') ?> Royale Vista Hotels & Resorts. All rights reserved.</div>
      <ul style="display:flex;gap:20px;list-style:none;padding:0;margin:0">
        <li><a href="<?= $B ?>/gallery.php"><?= t('gallery') ?></a></li>
        <li><a href="<?= $B ?>/offers.php"><?= t('offers') ?></a></li>
        <li><a href="<?= $B ?>/careers.php"><i class="fas fa-briefcase" style="font-size:10px"></i> <?= t('careers', 'Careers') ?></a></li>
        <li><a href="<?= $B ?>/privacy.php"><?= t('privacy', 'Privacy Policy') ?></a></li>
        <li><a href="<?= $B ?>/terms.php"><?= t('terms', 'Terms of Service') ?></a></li>
      </ul>
    </div>
  </div>
</footer>

<!-- ═══════════════════════════════════════════════════════
     SHAMA — AI CONCIERGE UI
     ═══════════════════════════════════════════════════════ -->
<!-- Chatbot bubble — premium concierge bell icon -->
<button class="habibi-bubble-btn" id="habibiBubble" onclick="Habibi.toggle()" title="Chat with Habibi — AI Concierge">
  <div class="habibi-bubble-inner">
    <i class="fas fa-concierge-bell habibi-bubble-icon"></i>
    <span class="habibi-bubble-pulse"></span>
  </div>
</button>

<div class="habibi-win" id="habibiWin">
  <div class="habibi-header">
    <div class="habibi-header-av">
      <!-- Premium diamond/bell icon for AI concierge -->
      <div class="habibi-header-av-inner">
        <i class="fas fa-concierge-bell" style="font-size:18px;color:#000"></i>
      </div>
    </div>
    <div style="flex:1;min-width:0">
      <div class="habibi-header-name">Habibi</div>
      <div class="habibi-header-title">AI Concierge · Royale Vista</div>
      <div class="habibi-status"><div class="habibi-status-dot"></div> Available 24/7/365</div>
    </div>
    <button class="habibi-close-btn" onclick="Habibi.toggle()" title="Close">×</button>
  </div>
  <div class="habibi-msgs" id="habibiMsgs"></div>
  <div class="habibi-qr" id="habibiQR"></div>
  <div class="habibi-input-area">
    <input type="text" class="habibi-input" id="habibiInput" placeholder="Ask Habibi anything…" maxlength="250" onkeydown="if(event.key==='Enter')Habibi.send(this.value)">
    <button class="habibi-send" onclick="Habibi.send(document.getElementById('habibiInput').value)">
      <i class="fas fa-paper-plane"></i>
    </button>
  </div>
</div>

<script>
(function() {
  // Scroll reveal for lx-reveal elements
  const lxRevObs = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.style.opacity = '1';
        e.target.style.transform = 'translateY(0)';
        lxRevObs.unobserve(e.target);
      }
    });
  }, { threshold: 0.08 });

  document.querySelectorAll('.lx-reveal').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(28px)';
    el.style.transition = 'opacity .7s ease, transform .7s ease';
    lxRevObs.observe(el);
  });

  // Move back-to-top button above chatbot bubble
  const bkTop = document.getElementById('back-top');
  if (bkTop) { bkTop.style.bottom = '90px'; }
})();
</script>

<?php if (isset($_SESSION['user_id'])): ?>
<script>
window.HabibiUser = {
    id: <?= (int)$_SESSION['user_id'] ?>,
    name: '<?= addslashes($_SESSION['display_name'] ?? $_SESSION['username'] ?? 'Guest') ?>',
    img: '<?= !empty($_SESSION['profile_img']) ? addslashes($_SESSION['profile_img']) : '' ?>'
};
</script>
<?php endif; ?>

<!-- Google Translate (auto-apply selected site language) -->
<div id="google_translate_element" style="display:none"></div>
<script>
(function(){
  const siteLang = '<?= addslashes($_SESSION['language'] ?? ($_COOKIE['rv_lang'] ?? 'en')) ?>';
  const gtMap = {
    en:'en', ar:'ar', bn:'bn', de:'de', es:'es', fa:'fa', fr:'fr', he:'iw',
    zh:'zh-CN', hi:'hi', id:'id', it:'it', ja:'ja', ko:'ko', ms:'ms',
    nl:'nl', pt:'pt', ru:'ru', sw:'sw', th:'th', tr:'tr', uk:'uk',
    ur:'ur', vi:'vi'
  };
  gtMap.gu = 'gu';
  const target = gtMap[siteLang] || 'en';

  function setCookie(name, value, days) {
    const d = new Date();
    d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = name + '=' + value + '; expires=' + d.toUTCString() + '; path=/';
  }

  // Persist same language intent for both app and Google translator.
  try { localStorage.setItem('rv_lang', siteLang); } catch(e) {}
  if (target && target !== 'en') {
    setCookie('googtrans', '/en/' + target, 365);
  } else {
    setCookie('googtrans', '/en/en', 365);
  }

  window.googleTranslateElementInit = function () {
    new google.translate.TranslateElement({
      pageLanguage: 'en',
      includedLanguages: 'en,ar,bn,de,es,gu,fa,fr,iw,zh-CN,hi,id,it,ja,ko,ms,nl,pt,ru,sw,th,tr,uk,ur,vi',
      autoDisplay: false,
      layout: google.translate.TranslateElement.InlineLayout.SIMPLE
    }, 'google_translate_element');
  };

  const s = document.createElement('script');
  s.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
  s.async = true;
  document.head.appendChild(s);
})();
</script>
<style>
/* Hide Google UI chrome, keep translated content only */
.goog-te-banner-frame,
.goog-te-banner-frame.skiptranslate,
iframe.goog-te-banner-frame,
.goog-te-gadget-icon,
.goog-te-menu-value span:first-child,
.goog-logo-link,
#goog-gt-tt,
.goog-te-spinner-pos,
.goog-te-gadget,
.goog-te-gadget-simple,
.goog-te-combo,
#google_translate_element select,
#google_translate_element a,
/* Some Google UI uses these internal container classnames */
[class*="VIpgJd"],
[class*="goog-te-"] { display: none !important; }

/* Some layouts inject the banner as an iframe + wrapper; move it offscreen as backup. */
.goog-te-banner-frame { position: absolute !important; top: -10000px !important; }

body { top: 0 !important; }
</style>

<script>
// After Google Translate injects its UI, remove/hide it so it doesn't affect your look.
(function(){
  const hide = () => {
    const sels = [
      '#google_translate_element',
      '.goog-te-banner-frame',
      '.goog-te-gadget',
      '.goog-te-gadget-simple',
      '.goog-te-combo',
      '.goog-te-gadget-icon',
      '.goog-te-spinner-pos',
      '#goog-gt-tt',
      'iframe.goog-te-banner-frame',
      'iframe[src*="translate"]',
      '[class*="VIpgJd"]',
      '[id^="goog-"]'
    ];
    document.querySelectorAll(sels.join(',')).forEach(el => {
      el.style.display = 'none';
      el.style.visibility = 'hidden';
      el.style.opacity = '0';
    });
  };

  // Run immediately + after first load.
  setTimeout(hide, 500);
  setTimeout(hide, 1200);
  setTimeout(hide, 2500);

  // Re-hide whenever Google injects UI after language changes.
  try {
    const mo = new MutationObserver(() => hide());
    mo.observe(document.documentElement, { childList: true, subtree: true });
    // Stop observing after a while to reduce overhead.
    setTimeout(() => { try { mo.disconnect(); } catch(e){} }, 12000);
  } catch(e) {}
})();
</script>

<!-- Cookie Consent Banner + Preferences -->
<div class="cookie-banner" id="cookieBanner" style="display:none; position:fixed; bottom:18px; left:0; right:0; z-index:9999; padding:0 16px;">
  <div class="cb-inner">
    <div class="cb-txt">
      <i class="fas fa-cookie-bite" style="color:var(--gold);margin-right:8px"></i>
      We use cookies to run the site and improve your experience.
      <a href="<?= BASE ?>/cookies.php" style="color:var(--gold);text-decoration:underline">Cookie Policy</a>
    </div>
    <div class="cb-btns">
      <button class="cb-btn-pref" onclick="openCookiePrefs()">Preferences</button>
      <button class="cb-btn-rej" onclick="rejectOptionalCookies()">Reject Optional</button>
      <button class="cb-btn-acc" onclick="acceptAllCookies()">Accept All</button>
    </div>
  </div>
</div>

<div id="cookiePrefModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:10000;align-items:center;justify-content:center;padding:18px">
  <div style="width:560px;max-width:100%;background:var(--card);border:1px solid var(--bdr2);border-radius:12px;overflow:hidden">
    <div style="padding:16px 18px;border-bottom:1px solid var(--bdr2);display:flex;align-items:center;justify-content:space-between">
      <div style="font-family:var(--serif);font-size:22px">Cookie Preferences</div>
      <button onclick="closeCookiePrefs()" style="border:none;background:transparent;color:var(--text2);font-size:24px;cursor:pointer">×</button>
    </div>
    <div style="padding:16px 18px">
      <p style="font-size:13px;color:var(--text2);line-height:1.6">Choose which cookies you allow. Essential cookies are always on.</p>
      <label style="display:flex;justify-content:space-between;align-items:center;margin:12px 0;padding:12px;border:1px solid var(--bdr2);border-radius:8px">
        <span><strong>Essential</strong><br><small>Required for login, security, and booking flow.</small></span>
        <input type="checkbox" checked disabled>
      </label>
      <label style="display:flex;justify-content:space-between;align-items:center;margin:12px 0;padding:12px;border:1px solid var(--bdr2);border-radius:8px">
        <span><strong>Analytics</strong><br><small>Helps us improve website performance.</small></span>
        <input type="checkbox" id="cookieAnalytics">
      </label>
      <label style="display:flex;justify-content:space-between;align-items:center;margin:12px 0;padding:12px;border:1px solid var(--bdr2);border-radius:8px">
        <span><strong>Marketing</strong><br><small>Used for offers and personalized campaigns.</small></span>
        <input type="checkbox" id="cookieMarketing">
      </label>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px">
        <button class="cb-btn-pref" onclick="closeCookiePrefs()">Cancel</button>
        <button class="cb-btn-acc" onclick="saveCookiePrefs()">Save Preferences</button>
      </div>
    </div>
  </div>
</div>

<style>
.cb-inner { background:rgba(18,16,13,.96); backdrop-filter:blur(8px); border:1px solid rgba(255,255,255,.1); border-radius:12px; padding:12px 16px; display:flex; align-items:center; justify-content:space-between; gap:16px; box-shadow:0 12px 28px rgba(0,0,0,.35); max-width:1140px; margin:0 auto; }
.cb-txt { color:rgba(255,255,255,.9); font-size:13px; line-height:1.4; }
.cb-btns { display:flex; gap:10px; flex-shrink:0; }
.cb-btn-acc,.cb-btn-rej,.cb-btn-pref { border:none; padding:9px 14px; border-radius:8px; font-size:12px; cursor:pointer; transition:all .2s; }
.cb-btn-acc { background:#c9a465; color:#151515; font-weight:700; }
.cb-btn-rej { background:transparent; color:#fff; border:1px solid rgba(255,255,255,.25); }
.cb-btn-pref { background:transparent; color:#d9bc8a; border:1px solid rgba(201,164,101,.45); }
.cb-btn-acc:hover { background:var(--gold2); transform:translateY(-1px); }
.cb-btn-rej:hover,.cb-btn-pref:hover { border-color:#c9a465; color:#c9a465; }
@media(max-width:768px){ .cb-inner{flex-direction:column; text-align:center; padding:20px;} .cb-btns{width:100%; justify-content:center; flex-wrap:wrap;} }
</style>

<script>
function closeCookieBanner(){
  const b = document.getElementById('cookieBanner');
  if(!b) return;
  if(window.anime) anime({targets:b, opacity:0, translateY:30, duration:350, easing:'easeInExpo', complete:()=>b.style.display='none'});
  else b.style.display='none';
}
function storeCookiePrefs(pref){
  localStorage.setItem('rv_cookie_pref', JSON.stringify(pref));
  localStorage.setItem('rv_cookie_choice_at', new Date().toISOString());
}
function acceptAllCookies(){
  storeCookiePrefs({essential:true, analytics:true, marketing:true});
  closeCookieBanner();
  closeCookiePrefs();
}
function rejectOptionalCookies(){
  storeCookiePrefs({essential:true, analytics:false, marketing:false});
  closeCookieBanner();
}
function openCookiePrefs(){
  const m = document.getElementById('cookiePrefModal');
  const pref = JSON.parse(localStorage.getItem('rv_cookie_pref') || '{"essential":true,"analytics":false,"marketing":false}');
  const a = document.getElementById('cookieAnalytics');
  const mk = document.getElementById('cookieMarketing');
  if(a) a.checked = !!pref.analytics;
  if(mk) mk.checked = !!pref.marketing;
  m.style.display = 'flex';
}
function closeCookiePrefs(){ const m = document.getElementById('cookiePrefModal'); if(m) m.style.display = 'none'; }
function saveCookiePrefs(){
  const pref = {
    essential: true,
    analytics: !!document.getElementById('cookieAnalytics')?.checked,
    marketing: !!document.getElementById('cookieMarketing')?.checked
  };
  storeCookiePrefs(pref);
  closeCookieBanner();
  closeCookiePrefs();
}
document.addEventListener('DOMContentLoaded', ()=>{
  if(!localStorage.getItem('rv_cookie_pref')){
    setTimeout(()=> {
      const b = document.getElementById('cookieBanner');
      b.style.display = 'block';
      if(window.anime) anime({targets:b, opacity:[0,1], translateY:[30,0], duration:600, easing:'easeOutExpo'});
    }, 900);
  }
  const modal = document.getElementById('cookiePrefModal');
  if(modal){
    modal.addEventListener('click', (e)=>{ if(e.target === modal) closeCookiePrefs(); });
  }
});
</script>

<script src="<?= BASE ?>/js/habibi.js"></script>
</body>
</html>

