<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
$pageTitle = 'Cookie Policy — Royale Vista';
require __DIR__ . '/header.php';
?>
<style>
.pol-hero{background:linear-gradient(135deg,var(--bg2),var(--bg));padding:110px 20px 60px;text-align:center;border-bottom:1px solid var(--bdr2)}
.pol-body{max-width:820px;margin:0 auto;padding:60px 20px 100px}
.pol-body h2{font-family:var(--serif);font-size:24px;font-weight:400;color:var(--gold);margin:40px 0 14px}
.pol-body h3{font-size:16px;font-weight:700;margin:28px 0 8px}
.pol-body p,.pol-body li{font-size:15px;color:var(--text2);line-height:1.9;margin-bottom:12px}
.pol-body ul{padding-left:22px}
.pol-toc{background:var(--card);border:1px solid var(--bdr2);border-radius:12px;padding:24px 28px;margin-bottom:40px}
.pol-toc a{display:block;font-size:14px;color:var(--gold);text-decoration:none;padding:4px 0;border-bottom:1px solid var(--bdr2)}
.pol-toc a:last-child{border-bottom:none}
.pol-toc a:hover{text-decoration:underline}
.cookie-table{width:100%;border-collapse:collapse;margin:18px 0 24px;font-size:14px}
.cookie-table th{background:var(--card2);padding:10px 14px;text-align:left;font-weight:600;color:var(--text);border:1px solid var(--bdr2)}
.cookie-table td{padding:10px 14px;border:1px solid var(--bdr2);color:var(--text2);vertical-align:top}
.cookie-table tr:nth-child(even) td{background:var(--bg2)}
/* Preference Manager */
.pref-box{background:var(--card);border:1px solid var(--gold);border-radius:12px;padding:28px;margin-top:32px}
.pref-row{display:flex;justify-content:space-between;align-items:center;padding:14px 0;border-bottom:1px solid var(--bdr2)}
.pref-row:last-of-type{border-bottom:none}
.pref-label{font-size:15px;font-weight:600}
.pref-desc{font-size:13px;color:var(--text2);margin-top:3px}
.toggle-sw{position:relative;width:46px;height:26px;flex-shrink:0}
.toggle-sw input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;inset:0;background:var(--bdr2);border-radius:26px;cursor:pointer;transition:.3s}
.toggle-slider:before{content:'';position:absolute;width:18px;height:18px;left:4px;bottom:4px;background:#fff;border-radius:50%;transition:.3s}
.toggle-sw input:checked+.toggle-slider{background:var(--gold)}
.toggle-sw input:checked+.toggle-slider:before{transform:translateX(20px)}
.pref-save-btn{margin-top:20px;width:100%}
</style>

<div class="pol-hero">
  <div class="lx-eyebrow" style="justify-content:center;margin-bottom:14px">Legal</div>
  <h1 style="font-family:var(--serif);font-size:clamp(32px,5vw,56px);font-weight:300">Cookie <em style="color:var(--gold)">Policy</em></h1>
  <p style="color:var(--text2);margin-top:14px;font-size:15px">Last updated: <?= date('d F Y') ?> &nbsp;·&nbsp; Royale Vista Hotels &amp; Resorts</p>
</div>

<div class="pol-body">

  <div class="pol-toc">
    <div style="font-weight:700;margin-bottom:12px;font-family:var(--serif)">Contents</div>
    <a href="#what">1. What Are Cookies?</a>
    <a href="#use">2. How We Use Cookies</a>
    <a href="#types">3. Types of Cookies We Set</a>
    <a href="#third">4. Third-Party Cookies</a>
    <a href="#control">5. Your Cookie Choices</a>
    <a href="#contact">6. Contact Us</a>
  </div>

  <h2 id="what">1. What Are Cookies?</h2>
  <p>Cookies are small text files placed on your device when you visit our website. They are widely used to make websites work more efficiently, provide a better user experience, and give us information about how our site is used.</p>
  <p>Cookies do not typically contain any information that personally identifies a user, but personal information that we store about you may be linked to the information stored in and obtained from cookies.</p>

  <h2 id="use">2. How We Use Cookies</h2>
  <p>Royale Vista uses cookies for purposes including:</p>
  <ul>
    <li>Keeping you signed in to your account and remembering your preferences</li>
    <li>Saving your language and currency selection across sessions</li>
    <li>Tracking which rooms and services you have viewed to personalise your experience</li>
    <li>Analysing how visitors use our website to improve performance and design</li>
    <li>Delivering relevant offers and promotions to you</li>
  </ul>

  <h2 id="types">3. Types of Cookies We Set</h2>

  <h3>Strictly Necessary</h3>
  <p>These cookies are essential for the website to function. They cannot be switched off in our systems.</p>
  <table class="cookie-table">
    <tr><th>Cookie Name</th><th>Purpose</th><th>Expiry</th></tr>
    <tr><td>PHPSESSID</td><td>Maintains your logged-in session</td><td>Session</td></tr>
    <tr><td>rv_currency</td><td>Stores your selected currency</td><td>30 Days</td></tr>
    <tr><td>rv_lang</td><td>Stores your selected language</td><td>30 Days</td></tr>
    <tr><td>rv_theme</td><td>Stores dark/light mode preference</td><td>1 Year</td></tr>
  </table>

  <h3>Performance &amp; Analytics</h3>
  <p>These cookies help us understand how visitors interact with our website so we can improve the experience.</p>
  <table class="cookie-table">
    <tr><th>Cookie Name</th><th>Purpose</th><th>Expiry</th></tr>
    <tr><td>_ga / _gid</td><td>Google Analytics — tracks page views and interactions anonymously</td><td>2 Years / 24 Hours</td></tr>
    <tr><td>rv_session_data</td><td>Stores anonymous booking funnel progress for analysis</td><td>Session</td></tr>
  </table>

  <h3>Functional</h3>
  <p>These cookies enhance your experience by remembering choices you make.</p>
  <table class="cookie-table">
    <tr><th>Cookie Name</th><th>Purpose</th><th>Expiry</th></tr>
    <tr><td>habibi_guest_name</td><td>Stores your preferred name for the Habibi AI concierge</td><td>90 Days</td></tr>
    <tr><td>rv_wishlist</td><td>Saves your wishlisted rooms for your next visit</td><td>7 Days</td></tr>
  </table>

  <h3>Marketing &amp; Targeting</h3>
  <p>These cookies track your browsing habits so we can tailor advertisements to your interests across the web.</p>
  <table class="cookie-table">
    <tr><th>Cookie Name</th><th>Purpose</th><th>Expiry</th></tr>
    <tr><td>_fbp</td><td>Facebook Pixel — tracks conversions for advertising</td><td>3 Months</td></tr>
    <tr><td>rv_referral</td><td>Records your traffic source so we can reward partners</td><td>30 Days</td></tr>
  </table>

  <h2 id="third">4. Third-Party Cookies</h2>
  <p>We use a number of third-party services that may also set cookies on your device when you visit our website. These include:</p>
  <ul>
    <li><strong>Google Analytics</strong> — for website performance statistics</li>
    <li><strong>Unsplash / Cloudinary</strong> — for optimised image delivery</li>
    <li><strong>QR Server API</strong> — for generating UPI payment QR codes</li>
    <li><strong>Font Awesome / Google Fonts</strong> — for typography and icons (these are loaded from CDN)</li>
  </ul>
  <p>We have no control over these third-party cookies. You should review the Privacy Policies of the relevant third parties for further information.</p>

  <h2 id="control">5. Your Cookie Choices</h2>
  <p>You may set your preferences below. Please note that disabling certain cookies may affect the functionality of the website.</p>

  <div class="pref-box">
    <div style="font-family:var(--serif);font-size:18px;margin-bottom:4px">Manage Cookie Preferences</div>
    <p style="font-size:13px;color:var(--text2);margin-bottom:20px">Your choices are saved in your browser and will persist on your next visit.</p>

    <div class="pref-row">
      <div>
        <div class="pref-label">Strictly Necessary</div>
        <div class="pref-desc">Required for login, language and currency. Cannot be disabled.</div>
      </div>
      <label class="toggle-sw" style="opacity:.5;pointer-events:none">
        <input type="checkbox" checked disabled>
        <span class="toggle-slider"></span>
      </label>
    </div>

    <div class="pref-row">
      <div>
        <div class="pref-label">Performance &amp; Analytics</div>
        <div class="pref-desc">Helps us understand how you use the website to improve it.</div>
      </div>
      <label class="toggle-sw">
        <input type="checkbox" id="ck_perf" checked>
        <span class="toggle-slider"></span>
      </label>
    </div>

    <div class="pref-row">
      <div>
        <div class="pref-label">Functional</div>
        <div class="pref-desc">Remembers your Habibi AI name, wishlist and other preferences.</div>
      </div>
      <label class="toggle-sw">
        <input type="checkbox" id="ck_func" checked>
        <span class="toggle-slider"></span>
      </label>
    </div>

    <div class="pref-row">
      <div>
        <div class="pref-label">Marketing &amp; Targeting</div>
        <div class="pref-desc">Tailors advertising to your interests across the web.</div>
      </div>
      <label class="toggle-sw">
        <input type="checkbox" id="ck_mkt">
        <span class="toggle-slider"></span>
      </label>
    </div>

    <button class="btn btn-gold pref-save-btn" onclick="saveCookiePrefs()">
      <i class="fas fa-check"></i> Save My Preferences
    </button>
    <div id="pref-saved" style="display:none;text-align:center;padding:10px;color:var(--gold);font-size:14px">
      ✓ Preferences saved successfully.
    </div>
  </div>

  <h2 id="contact">6. Contact Us</h2>
  <p>If you have any questions about our use of cookies, please contact our Data Protection team:</p>
  <ul>
    <li><strong>Email:</strong> privacy@royalevista.com</li>
    <li><strong>Address:</strong> 1 Park Avenue, Midtown Manhattan, New York, NY 10016, USA</li>
    <li><strong>Phone:</strong> +1 (212) 555‑0199</li>
  </ul>
  <p>For further information on your rights under GDPR or any applicable data protection law, please visit our <a href="<?= BASE ?>/privacy.php" style="color:var(--gold)">Privacy Policy</a>.</p>

</div>

<script>
function saveCookiePrefs(){
  const prefs = {
    perf: document.getElementById('ck_perf').checked,
    func: document.getElementById('ck_func').checked,
    mkt:  document.getElementById('ck_mkt').checked
  };
  localStorage.setItem('rv_cookie_prefs', JSON.stringify(prefs));
  if(!prefs.func){
    localStorage.removeItem('habibi_guest_name');
    localStorage.removeItem('rv_wishlist');
  }
  document.getElementById('pref-saved').style.display = 'block';
  setTimeout(() => document.getElementById('pref-saved').style.display = 'none', 3000);
}
// Load saved prefs on page load
window.addEventListener('DOMContentLoaded', () => {
  const saved = JSON.parse(localStorage.getItem('rv_cookie_prefs') || '{}');
  if('perf' in saved) document.getElementById('ck_perf').checked = saved.perf;
  if('func' in saved) document.getElementById('ck_func').checked = saved.func;
  if('mkt'  in saved) document.getElementById('ck_mkt').checked  = saved.mkt;
});
</script>

<?php require __DIR__ . '/footer.php'; ?>
