<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Our Properties — Royale Vista';

// Fetch all active properties
$props = $pdo->query("SELECT * FROM properties WHERE is_active=1 ORDER BY sort_order")->fetchAll();
$continents = [];
foreach ($props as $p) {
    $continents[$p['continent']][] = $p;
}
require __DIR__ . '/header.php';
?>

<style>
.props-page { padding-top: 70px; }

/* Hero */
.props-hero {
  height: 70vh; min-height: 500px; position: relative;
  display: flex; align-items: flex-end; overflow: hidden;
}
.props-hero-bg {
  position: absolute; inset: 0;
  background: url('https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=1800&q=80') center/cover;
}
.props-hero-bg::after {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(to bottom, rgba(0,0,0,.15) 0%, rgba(0,0,0,.65) 100%);
}
.props-hero-content { position: relative; z-index: 1; color: #fff; width: 100%; padding: 60px 0; }

/* World map section */
.world-map-section {
  background: var(--charcoal); padding: 80px 0; position: relative; overflow: hidden;
}
.world-map-bg {
  position: absolute; inset: 0; opacity: .05;
  background: url('https://images.unsplash.com/photo-1524661135-423995f22d0b?w=1600&q=40') center/cover;
}
.world-stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 0; margin-top: 56px; }
.ws-item { padding: 28px 24px; text-align: center; border-right: 1px solid rgba(255,255,255,.06); }
.ws-item:last-child { border-right: none; }
.ws-num { font-family: var(--serif); font-size: 52px; font-weight: 300; color: var(--gold); line-height: 1; }
.ws-lbl { font-family: var(--cinzel); font-size: 9px; letter-spacing: 2px; color: rgba(255,255,255,.4); margin-top: 8px; text-transform: uppercase; }

/* Location pins overlay */
.map-canvas { position: relative; max-width: 900px; margin: 0 auto; }
.map-pin {
  position: absolute; cursor: pointer; z-index: 2;
  transform: translate(-50%, -100%);
  transition: transform .2s;
}
.map-pin:hover { transform: translate(-50%, -100%) scale(1.2); }
.pin-dot { width: 12px; height: 12px; background: var(--gold); border-radius: 50%; box-shadow: 0 0 0 4px rgba(212,175,55,.3); animation: pingPulse 2s ease-out infinite; }
.pin-dot::after { content: ''; position: absolute; top: -4px; left: -4px; right: -4px; bottom: -4px; border-radius: 50%; background: rgba(192,155,91,.2); animation: pingRipple 2s ease-out infinite; }
@keyframes pingPulse { 0%,100%{box-shadow:0 0 0 0 rgba(192,155,91,.5)} 70%{box-shadow:0 0 0 10px rgba(192,155,91,0)} }
@keyframes pingRipple { 0%{opacity:1;transform:scale(1)} 100%{opacity:0;transform:scale(2.5)} }
.pin-label { position: absolute; bottom: 16px; left: 50%; transform: translateX(-50%); background: var(--charcoal); border: 1px solid var(--gold); color: var(--gold); font-family: var(--cinzel); font-size: 9px; letter-spacing: 1.5px; padding: 3px 9px; white-space: nowrap; opacity: 0; transition: opacity .2s; pointer-events: none; }
.map-pin:hover .pin-label { opacity: 1; }

/* Property cards */
.prop-continent-title { font-family: var(--cinzel); font-size: 10px; color: var(--gold); letter-spacing: 3px; text-transform: uppercase; margin-bottom: 32px; display: flex; align-items: center; gap: 16px; }
.prop-continent-title::after { content: ''; flex: 1; height: 1px; background: var(--border); }

.prop-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(360px,1fr)); gap: 1px; background: var(--bdr2); margin-bottom: 64px; }
.prop-card {
  background: var(--card); position: relative; overflow: hidden; display: flex; flex-direction: column;
  cursor: pointer; transition: none;
}
.prop-card-img { height: 300px; overflow: hidden; position: relative; }
.prop-card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .7s var(--ease); display: block; }
.prop-card:hover .prop-card-img img { transform: scale(1.07); }
.prop-card-flag { position: absolute; top: 16px; right: 16px; width: 36px; height: 26px; border-radius: 3px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.3); }
.prop-card-flag img { width: 100%; height: 100%; object-fit: cover; }
.prop-flagship-badge { position: absolute; top: 16px; left: 16px; background: var(--gold); color: #fff; font-family: var(--cinzel); font-size: 9px; letter-spacing: 2px; padding: 4px 12px; text-transform: uppercase; }
.prop-card-body { padding: 26px 28px; flex: 1; display: flex; flex-direction: column; }
.prop-city { font-family: var(--cinzel); font-size: 10px; letter-spacing: 3px; color: var(--gold); margin-bottom: 7px; text-transform: uppercase; }
.prop-name { font-family: var(--serif); font-size: 26px; font-weight: 300; margin-bottom: 6px; line-height: 1.2; }
.prop-tagline { font-size: 13.5px; color: var(--text2); line-height: 1.7; margin-bottom: 18px; }
.prop-meta { display: flex; gap: 18px; font-size: 12px; color: var(--muted); margin-bottom: 18px; flex-wrap: wrap; }
.prop-meta span { display: flex; align-items: center; gap: 5px; }
.prop-meta i { color: var(--gold); font-size: 11px; }
.prop-card-ft { display: flex; gap: 10px; margin-top: auto; }
.prop-btn-view { flex: 1; padding: 12px; background: var(--charcoal); color: var(--gold); border: none; font-family: var(--cinzel); font-size: 10px; letter-spacing: 2px; text-transform: uppercase; cursor: pointer; transition: background .2s; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; }
.prop-btn-view:hover { background: var(--gold); color: #fff; }
.prop-btn-book { flex: 1; padding: 12px; background: var(--gold); color: #fff; border: none; font-family: var(--cinzel); font-size: 10px; letter-spacing: 2px; text-transform: uppercase; cursor: pointer; transition: background .2s; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; }
.prop-btn-book:hover { background: var(--gold-dk); }

/* Compare strip */
.compare-strip {
  position: fixed; bottom: 0; left: 0; right: 0; z-index: 500;
  background: var(--charcoal); border-top: 1px solid var(--gold);
  padding: 14px 40px; display: flex; align-items: center; gap: 16px;
  transform: translateY(100%); transition: transform .3s var(--ease);
}
.compare-strip.open { transform: translateY(0); }

@media(max-width:768px) {
  .prop-grid { grid-template-columns: 1fr; }
  .world-stats { grid-template-columns: repeat(2,1fr); }
  .props-hero { height: 50vh; }
}
</style>

<div class="props-page">

  <!-- Hero -->
  <div class="props-hero">
    <div class="props-hero-bg"></div>
    <div class="props-hero-content">
      <div class="container">
        <div class="lx-eyebrow" style="justify-content:flex-start;color:rgba(255,255,255,.5)" id="prEye">Our Global Collection</div>
        <h1 style="font-family:var(--serif);font-size:clamp(40px,6vw,80px);font-weight:300;color:#fff;line-height:1.1;max-width:700px" id="prTitle">
          Royale Vista,<br><em style="color:var(--gold)">Everywhere You Go</em>
        </h1>
        <p style="color:rgba(255,255,255,.75);font-size:16px;max-width:520px;line-height:1.8;margin-top:16px" id="prSub">
          Eight extraordinary addresses across four continents. Each property unique, every experience unforgettable.
        </p>
      </div>
    </div>
  </div>

  <!-- World Stats -->
  <div class="world-map-section">
    <div class="world-map-bg"></div>
    <div class="container" style="position:relative;z-index:1">
      <div style="text-align:center">
        <div class="lx-eyebrow" style="justify-content:center;color:rgba(255,255,255,.4)">Our Footprint</div>
        <h2 style="font-family:var(--serif);font-size:clamp(32px,4vw,52px);font-weight:300;color:#fff">
          A <em style="color:var(--gold)">World</em> of Luxury
        </h2>
      </div>
      <div class="world-stats" id="worldStats">
        <div class="ws-item"><div class="ws-num" data-target="8">0</div><div class="ws-lbl">Properties</div></div>
        <div class="ws-item"><div class="ws-num" data-target="4">0</div><div class="ws-lbl">Continents</div></div>
        <div class="ws-item"><div class="ws-num" data-target="2180">0</div><div class="ws-lbl">Guest Rooms</div></div>
        <div class="ws-item"><div class="ws-num" data-target="50000">0</div><div class="ws-lbl">Happy Guests</div></div>
      </div>
    </div>
  </div>

  <!-- Properties by Continent -->
  <section class="section">
    <div class="container">
      <?php foreach ($continents as $continent => $list): ?>
      <div class="prop-continent-title lx-reveal"><?= htmlspecialchars($continent) ?></div>
      <div class="prop-grid">
        <?php foreach ($list as $prop): ?>
        <div class="prop-card" id="prop-<?= $prop['id'] ?>">
          <div class="prop-card-img">
            <img src="<?= htmlspecialchars($prop['hero_image']) ?>" alt="<?= htmlspecialchars($prop['name']) ?>" loading="lazy" onerror="this.style.opacity='0'">
            <?php if ($prop['is_flagship']): ?><div class="prop-flagship-badge">Flagship</div><?php endif; ?>
            <img class="prop-card-flag" src="https://flagcdn.com/w40/<?= strtolower(htmlspecialchars($prop['country_code'])) ?>.png" alt="<?= htmlspecialchars($prop['country']) ?>" onerror="this.style.display='none'" style="position:absolute;top:16px;right:16px;width:36px;height:auto;border-radius:3px;box-shadow:0 2px 8px rgba(0,0,0,.3)">
          </div>
          <div class="prop-card-body">
            <div class="prop-city"><?= htmlspecialchars($prop['city']) ?>, <?= htmlspecialchars($prop['country']) ?></div>
            <div class="prop-name"><?= htmlspecialchars($prop['name']) ?></div>
            <p class="prop-tagline"><?= htmlspecialchars($prop['tagline']) ?></p>
            <div class="prop-meta">
              <span><i class="fas fa-bed"></i> <?= $prop['rooms_count'] ?> Rooms</span>
              <span><i class="fas fa-star"></i> <?= $prop['stars'] ?>-Star</span>
              <?php if ($prop['year_opened']): ?><span><i class="fas fa-calendar"></i> Since <?= $prop['year_opened'] ?></span><?php endif; ?>
            </div>
            <div class="prop-card-ft">
              <a href="<?= $B ?>/property.php?code=<?= urlencode($prop['code']) ?>" class="prop-btn-view">Discover</a>
              <a href="<?= $B ?>/rooms.php?checkin=<?= date('Y-m-d') ?>&checkout=<?= date('Y-m-d',strtotime('+2 days')) ?>" class="prop-btn-book">Book a Stay</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- CTA -->
  <section style="background:var(--charcoal);padding:80px 0;text-align:center">
    <div class="container">
      <div class="lx-eyebrow lx-reveal" style="justify-content:center;color:rgba(255,255,255,.4)">Coming Soon</div>
      <h2 style="font-family:var(--serif);font-size:clamp(28px,4vw,50px);font-weight:300;color:#fff;margin-bottom:16px" class="lx-reveal">
        More Destinations <em style="color:var(--gold)">in 2025</em>
      </h2>
      <p style="color:rgba(255,255,255,.55);font-size:15px;max-width:480px;margin:0 auto 32px;line-height:1.8" class="lx-reveal">
        Bali, Maldives, Cape Town and Marrakech. Four extraordinary new properties arriving this year.
      </p>
      <a href="<?= $B ?>/contact.php" class="btn btn-gold btn-xl lx-reveal">Register Your Interest</a>
    </div>
  </section>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Hero entrance
  anime({targets:'#prEye',opacity:[0,1],translateX:[-30,0],duration:800,easing:'easeOutCubic',delay:300});
  anime({targets:'#prTitle',opacity:[0,1],translateY:[40,0],duration:900,easing:'easeOutCubic',delay:500});
  anime({targets:'#prSub', opacity:[0,1],translateY:[20,0],duration:700,easing:'easeOutCubic',delay:700});

  // Property card stagger
  anime({
    targets:'.prop-card',
    opacity:[0,1], translateY:[30,0],
    duration:600, easing:'easeOutCubic',
    delay:anime.stagger(80)
  });

  // Counter animation on scroll
  const cntObs = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.querySelectorAll('[data-target]').forEach(el => {
          const target = parseInt(el.dataset.target);
          anime({
            targets: el,
            innerHTML: [0, target],
            round: 1,
            duration: 2200,
            easing: 'easeOutExpo',
            update() { el.innerHTML = parseInt(el.innerHTML).toLocaleString(); }
          });
        });
        cntObs.unobserve(e.target);
      }
    });
  }, {threshold: 0.3});
  document.querySelectorAll('#worldStats').forEach(el => cntObs.observe(el));

  // Card hover 3D
  document.querySelectorAll('.prop-card').forEach(card => {
    card.addEventListener('mousemove', e => {
      const r = card.getBoundingClientRect();
      const x = (e.clientX - r.left - r.width/2) / r.width * 6;
      const y = (e.clientY - r.top - r.height/2) / r.height * 6;
      card.style.transform = `perspective(800px) rotateY(${x}deg) rotateX(${-y}deg)`;
    });
    card.addEventListener('mouseleave', () => { card.style.transform = ''; });
  });
});
</script>

<?php require __DIR__ . '/footer.php'; ?>
