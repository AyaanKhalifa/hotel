<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'About — Royale Vista';
require __DIR__ . '/header.php';
?>
<div style="padding-top:80px">
  <div style="position:relative;min-height:60vh;display:flex;align-items:center;overflow:hidden">
    <div style="position:absolute;inset:0;background:url('https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1400&q=70') center/cover no-repeat;opacity:.15"></div>
    <div class="container" style="position:relative;z-index:1;padding:80px 36px">
      <div class="section-label">Our Story</div>
      <h1 style="font-family:var(--serif);font-size:clamp(36px,6vw,72px);font-weight:400;line-height:1.1;max-width:700px;margin-bottom:24px">Where Timeless Elegance Meets Modern Luxury</h1>
      <p style="font-size:16px;color:var(--text2);max-width:580px;line-height:1.8">Founded in 2008, Royale Vista has redefined luxury hospitality with an unwavering commitment to bespoke service and exceptional experiences that transcend the ordinary.</p>
    </div>
  </div>
  <div class="container section">
    <div class="grid-2" style="gap:60px;align-items:center">
      <div>
        <div class="section-label">Our Philosophy</div>
        <h2 style="font-family:var(--serif);font-size:clamp(28px,4vw,42px);font-weight:400;margin-bottom:20px">Excellence is not a Standard — It's Our Only Option</h2>
        <p style="color:var(--text2);line-height:1.8;margin-bottom:16px">Every member of our 400-strong team undergoes rigorous training to ensure that every interaction, every detail, and every moment of your stay exceeds expectation.</p>
        <p style="color:var(--text2);line-height:1.8;margin-bottom:28px">We believe true luxury is deeply personal. That is why no two stays at Royale Vista are ever the same — each is uniquely crafted around you.</p>
        <div class="grid-2" style="gap:14px">
          <?php foreach([['2008','Year Founded'],['400+','Team Members'],['4.9★','Guest Rating'],['50K+','Happy Stays']] as [$n,$l]): ?>
          <div style="background:var(--card2);border:1px solid var(--bdr2);border-radius:var(--radius);padding:18px;text-align:center">
            <div style="font-family:var(--serif);font-size:32px;color:var(--gold)"><?= $n ?></div>
            <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-top:4px"><?= $l ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div style="border-radius:var(--radius-lg);overflow:hidden">
        <img src="https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=700&q=70" alt="Royale Vista Hotel" style="width:100%;display:block" loading="lazy">
      </div>
    </div>
    <!-- Awards -->
    <div style="margin-top:60px;text-align:center">
      <div class="section-label" style="justify-content:center">Recognition</div>
      <h2 style="font-family:var(--serif);font-size:clamp(24px,3vw,36px);font-weight:400;margin-bottom:36px">Award-Winning Hospitality</h2>
      <div style="display:flex;justify-content:center;flex-wrap:wrap;gap:20px">
        <?php foreach([['🏆','World\'s Best Hotels','Travel & Leisure 2024'],['⭐','5-Star Excellence','Forbes Travel Guide'],['🎖','Best Luxury Hotel','Condé Nast Traveller'],['🌿','Green Excellence','Sustainable Tourism Award']] as [$e,$t,$s]): ?>
        <div style="background:var(--card);border:1px solid var(--bdr2);border-radius:var(--radius-lg);padding:24px 28px;text-align:center;min-width:180px;transition:all var(--t)" onmouseover="this.style.borderColor='var(--gold)';this.style.transform='translateY(-3px)'" onmouseout="this.style.borderColor='var(--bdr2)';this.style.transform=''">
          <div style="font-size:36px;margin-bottom:10px"><?= $e ?></div>
          <div style="font-weight:600;font-size:14px;margin-bottom:4px"><?= $t ?></div>
          <div style="font-size:12px;color:var(--muted)"><?= $s ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- Sustainability Promo -->
<div class="lx-section" style="background:#0a0a0f;color:#fff;border-top:1px solid rgba(255,255,255,.05);padding:100px 20px">
    <div class="container" style="text-align:center">
        <div class="lx-eyebrow lx-reveal" style="justify-content:center;color:var(--gold)">Eco-Luxury Promise</div>
        <h2 class="lx-heading lx-reveal" style="color:#fff;margin-bottom:20px;max-width:600px;margin-left:auto;margin-right:auto">A Greener Tomorrow</h2>
        <p class="lx-reveal" style="color:var(--muted);max-width:600px;margin:0 auto 40px;line-height:1.8">True luxury means preserving the world's most breathtaking destinations. Learn about our commitment to achieving zero-carbon operations by 2030 and our 100% renewable energy phase-in.</p>
        <a href="<?= BASE ?>/sustainability.php" class="btn-gold lx-reveal">Explore Our Initiatives</a>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>