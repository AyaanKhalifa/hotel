<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Our Properties — Royale Vista';

try {
    $properties = $pdo->query("SELECT * FROM hotel_properties ORDER BY sort_order")->fetchAll();
    $continents = array_unique(array_column($properties,'continent'));
} catch (Exception $e) {
    $properties = [];
    $continents = [];
}

$continentFilter = clean($_GET['continent'] ?? 'All');
$filtered = $continentFilter === 'All' ? $properties : array_filter($properties, fn($p) => $p['continent'] === $continentFilter);

require __DIR__ . '/header.php';
?>

<style>
.lp-page { padding-top: 70px; }

/* ── Hero ── */
.lp-hero {
  position: relative; height: 68vh; min-height: 520px;
  display: flex; align-items: center; overflow: hidden;
  background: var(--charcoal);
}
.lp-hero-bg {
  position: absolute; inset: 0; z-index: 0;
  background: url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1800&q=80') center/cover;
}
.lp-hero-bg::after { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(0,0,0,.75) 40%, rgba(0,0,0,.4) 100%); }
.lp-hero-content { position: relative; z-index: 1; color: #fff; padding: 0 0 40px; }

/* ── World Map ── */
.world-map-container {
  position: relative; background: var(--charcoal);
  padding: 60px 0; overflow: hidden;
}
.world-map-inner {
  position: relative; max-width: 1200px; margin: 0 auto;
  height: 480px;
}
.world-map-img {
  width: 100%; height: 100%; object-fit: contain; opacity: .55; display: block;
  filter: brightness(1.2) contrast(1.1) hue-rotate(-15deg);
}
.map-pin { position: absolute; cursor: pointer; transform: translate(-50%,-50%); z-index: 10; }
.map-pin-dot {
  width: 18px; height: 18px; border-radius: 50%;
  background: var(--gold); border: 2px solid rgba(255,255,255,.85);
  animation: pinPulse 2.5s ease-in-out infinite;
  transition: transform .2s;
  box-shadow: 0 0 0 3px rgba(192,155,91,.35);
}
.map-pin:hover .map-pin-dot { transform: scale(1.6); }
@keyframes pinPulse {
  0%,100% { box-shadow: 0 0 0 3px rgba(192,155,91,.35); }
  50%      { box-shadow: 0 0 0 16px rgba(192,155,91,0); }
}
.map-pin-label {
  position: absolute; bottom: 24px; left: 50%;
  transform: translateX(-50%); background: rgba(0,0,0,.9);
  border: 1px solid var(--gold); color: #fff;
  padding: 5px 12px; border-radius: 6px; font-size: 11px;
  white-space: nowrap; opacity: 0; pointer-events: none;
  transition: opacity .2s; font-family: var(--cinzel); letter-spacing: 1px;
}
.map-pin:hover .map-pin-label { opacity: 1; }
.map-flagship { width: 22px; height: 22px; background: linear-gradient(135deg,var(--gold),#ffe88a); box-shadow: 0 0 0 4px rgba(255,215,0,.3); }
.map-count-badge {
  position: absolute; top: 20px; right: 20px;
  background: rgba(192,155,91,.15); border: 1px solid var(--gold);
  padding: 12px 20px; border-radius: var(--radius-lg);
  text-align: center; color: #fff;
}
.map-count-num { font-family: var(--serif); font-size: 36px; font-weight: 300; color: var(--gold); line-height: 1; }
.map-count-label { font-family: var(--cinzel); font-size: 9px; letter-spacing: 2px; text-transform: uppercase; color: rgba(255,255,255,.5); margin-top: 4px; }

/* ── Continent filter ── */
.continent-tabs { display: flex; gap: 4px; flex-wrap: wrap; margin-bottom: 36px; }
.cont-tab { padding: 8px 20px; border-radius: 2px; font-family: var(--cinzel); font-size: 9px; letter-spacing: 2px; text-transform: uppercase; border: 1px solid var(--border); color: var(--text2); background: transparent; cursor: pointer; transition: all .2s; text-decoration: none; }
.cont-tab:hover,.cont-tab.active { background: var(--gold); color: #fff; border-color: var(--gold); }

/* ── Property Cards Grid ── */
.prop-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 2px; background: var(--sand); }
.prop-grid .prop-card { height: 400px; }
.prop-grid .prop-card.flagship { grid-column: span 2; height: 400px; }

/* Detail modal */
.prop-modal { position: fixed; inset: 0; z-index: 1000; background: rgba(0,0,0,.85); backdrop-filter: blur(12px); display: none; align-items: center; justify-content: center; padding: 20px; overflow-y: auto; }
.prop-modal.open { display: flex; }
.prop-modal-box {
  background: var(--card); border-radius: var(--radius-lg); width: 860px;
  max-width: 100%; margin: auto; overflow: hidden; box-shadow: 0 32px 80px rgba(0,0,0,.4);
  animation: modalIn .35s cubic-bezier(.34,1.56,.64,1);
}
.prop-modal-img { height: 320px; position: relative; overflow: hidden; }
.prop-modal-img img { width: 100%; height: 100%; object-fit: cover; }
.prop-modal-img-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to right, rgba(0,0,0,.6) 40%, transparent);
  display: flex; flex-direction: column; justify-content: flex-end; padding: 32px;
}
.prop-modal-body { padding: 32px 36px; display: grid; grid-template-columns: 1fr 300px; gap: 32px; }
.prop-amenities { display: flex; flex-wrap: wrap; gap: 8px; }
.prop-amenity { background: var(--cream2); border: 1px solid var(--sand); padding: 5px 14px; border-radius: 20px; font-size: 12px; color: var(--text2); display: flex; align-items: center; gap: 6px; }
[data-theme="dark"] .prop-amenity { background: var(--card2); border-color: var(--bdr2); }
.prop-amenity i { color: var(--gold); font-size: 10px; }
.prop-sidebar-card { background: var(--charcoal); border-radius: var(--radius-lg); padding: 24px; color: rgba(255,255,255,.8); }
.prop-sidebar-card h4 { font-family: var(--serif); font-size: 20px; color: #fff; margin-bottom: 16px; }
.prop-detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,.07); font-size: 13px; }
.prop-detail-row .l { color: rgba(255,255,255,.45); }
.prop-detail-row .r { font-weight: 600; color: #fff; }

@media(max-width:900px) { .prop-grid { grid-template-columns: 1fr; } .prop-grid .prop-card.flagship { grid-column:auto; } .prop-modal-body { grid-template-columns:1fr; } }
@media(max-width:600px) { .prop-modal-img { height: 220px; } .prop-modal-body { padding: 20px; } }
</style>

<div class="lp-page">

  <!-- ── HERO ── -->
  <section class="lp-hero">
    <div class="lp-hero-bg"></div>
    <div class="container lp-hero-content">
      <div class="lx-eyebrow" style="color:rgba(255,255,255,.5);justify-content:flex-start" data-reveal="up">Our Global Collection</div>
      <h1 class="lx-heading" style="color:#fff;max-width:700px" data-reveal="up" data-delay="100">
        Royale Vista<br><em>Around the World</em>
      </h1>
      <p class="lx-subtext" style="color:rgba(255,255,255,.7);margin:20px 0 32px" data-reveal="up" data-delay="200">
        Twelve extraordinary addresses across five continents. Each property a masterpiece — sharing our commitment to flawless service and timeless luxury.
      </p>
      <div style="display:flex;gap:12px;flex-wrap:wrap" data-reveal="up" data-delay="300">
        <a href="#properties" class="btn btn-gold btn-lg" onclick="document.getElementById('properties').scrollIntoView({behavior:'smooth'});return false">Discover Properties</a>
        <a href="<?= $B ?>/rooms.php" class="btn" style="border:1px solid rgba(255,255,255,.35);color:#fff;background:transparent;padding:15px 36px;font-size:13px">Book Your Stay</a>
      </div>
    </div>
  </section>

  <!-- ── WORLD MAP ── -->
  <div class="world-map-container">
    <div style="text-align:center;margin-bottom:40px">
      <div class="lx-eyebrow" style="justify-content:center;color:var(--gold)">Worldwide Presence</div>
      <h2 class="lx-heading" style="color:#fff">12 Cities. 5 Continents.</h2>
    </div>
    <div class="world-map-inner" style="margin:0 auto;position:relative;max-width:1100px;padding:0 40px">
      <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/80/World_map_-_low_resolution.svg/1200px-World_map_-_low_resolution.svg.png" class="world-map-img" alt="World Map">
      <?php
      // Map pin positions (as % of container)
      $pins = [
        ['Dubai',        55, 47, 1, 1],
        ['New York',     22, 38, 2, 0],
        ['London',       46, 29, 3, 0],
        ['Paris',        47, 30, 4, 0],
        ['Tokyo',        80, 36, 5, 0],
        ['Maldives',     62, 55, 6, 0],
        ['Singapore',    75, 55, 7, 0],
        ['Rome',         49, 34, 8, 0],
        ['Bali',         77, 60, 9, 0],
        ['Sydney',       83, 70,10, 0],
        ['Miami',        23, 46,11, 0],
        ['St. Moritz',   49, 31,12, 0],
      ];
      foreach ($pins as [$city,$x,$y,$pid,$flag]):
      ?>
      <div class="map-pin <?= $flag?'flagship':'' ?>" style="left:<?=$x?>%;top:<?=$y?>%" onclick="openPropModal(<?= $pid ?>)">
        <div class="map-pin-dot <?= $flag?'map-flagship':'' ?>"></div>
        <div class="map-pin-label"><?= $city ?></div>
      </div>
      <?php endforeach; ?>
      <div class="map-count-badge">
        <div class="map-count-num">12</div>
        <div class="map-count-label">Properties</div>
      </div>
    </div>
    <div style="display:flex;justify-content:center;gap:20px;margin-top:24px;flex-wrap:wrap;padding-bottom:16px">
      <div style="display:flex;align-items:center;gap:7px;font-size:12px;color:rgba(255,255,255,.5)"><div style="width:12px;height:12px;border-radius:50%;background:linear-gradient(135deg,var(--gold),#fff);border:1px solid rgba(255,255,255,.4)"></div>Flagship Property</div>
      <div style="display:flex;align-items:center;gap:7px;font-size:12px;color:rgba(255,255,255,.5)"><div style="width:12px;height:12px;border-radius:50%;background:var(--gold);border:1px solid rgba(255,255,255,.4)"></div>Royale Vista Property</div>
    </div>
  </div>

  <!-- ── PROPERTIES GRID ── -->
  <section class="section" id="properties">
    <div class="container">
      <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:36px;flex-wrap:wrap;gap:16px">
        <div>
          <div class="lx-eyebrow lx-reveal">Explore Our Collection</div>
          <h2 class="lx-heading lx-reveal">Select Your <em>Destination</em></h2>
        </div>
        <div class="continent-tabs">
          <a href="?continent=All" class="cont-tab <?= $continentFilter==='All'?'active':'' ?>">All</a>
          <?php foreach ($continents as $c): ?>
          <a href="?continent=<?= urlencode($c) ?>" class="cont-tab <?= $continentFilter===$c?'active':'' ?>"><?= htmlspecialchars($c) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Grid -->
    <div class="prop-grid" id="propGrid">
      <?php foreach ($filtered as $i => $p):
        $isFlag = (bool)$p['is_flagship'];
      ?>
      <div class="prop-card <?= $isFlag?'flagship':'' ?> rv-tilt" onclick="openPropModal(<?= $p['id'] ?>)" data-reveal="fade" data-delay="<?= ($i%3)*80 ?>">
        <img src="<?= htmlspecialchars($p['hero_image']) ?>" alt="<?= htmlspecialchars($p['city']) ?>" loading="lazy"
             onerror="this.style.opacity='0'">
        <div class="prop-card-overlay">
          <?php if ($isFlag): ?><div class="prop-tag"><i class="fas fa-star" style="font-size:8px"></i> Flagship</div><?php endif; ?>
          <div class="prop-flag"><?= htmlspecialchars($p['continent']) ?> · <?= htmlspecialchars($p['country']) ?></div>
          <div class="prop-city"><?= htmlspecialchars($p['city']) ?></div>
          <div class="prop-country"><?= htmlspecialchars($p['name']) ?></div>
          <div class="prop-btns" style="margin-top:14px">
            <span class="prop-btn prop-btn-primary">Explore</span>
            <span class="prop-btn prop-btn-secondary" onclick="event.stopPropagation();window.location='<?= $B ?>/rooms.php'">Book Stay</span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ── STATS ── -->
  <div class="section-dark section rv-counter-section">
    <div class="container">
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:2px;background:rgba(255,255,255,.04);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:0">
        <?php foreach ([['12','Global Properties'],['4,200+','Luxury Rooms'],['85,000+','Happy Guests'],['14','Years of Excellence']] as [$v,$l]): ?>
        <div style="padding:36px 28px;text-align:center;background:rgba(255,255,255,.02)">
          <div data-count="<?= preg_replace('/[^0-9.]/','',$v) ?>" data-suf="<?= strpos($v,'+')!==false?'+':'' ?>" style="font-family:var(--serif);font-size:48px;font-weight:300;color:var(--gold);line-height:1;margin-bottom:8px"><?= $v ?></div>
          <div style="font-family:var(--cinzel);font-size:9px;color:rgba(255,255,255,.4);letter-spacing:2px;text-transform:uppercase"><?= $l ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>

<!-- ── PROPERTY DETAIL MODAL ── -->
<div class="prop-modal" id="propModal" onclick="if(event.target===this)closePropModal()">
  <div class="prop-modal-box" id="propModalBox">
    <div id="propModalContent"></div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
<script>
const PROPS = <?= json_encode($properties) ?>;
const BASE  = '<?= $B ?>';

function openPropModal(id) {
  const p = PROPS.find(x => x.id == id);
  if (!p) return;
  const amenities = (p.amenities || '').split(',').filter(Boolean);

  document.getElementById('propModalContent').innerHTML = `
    <div style="position:relative">
      <div class="prop-modal-img">
        <img src="${p.hero_image || ''}" alt="${p.city}" onerror="this.style.opacity='0'">
        <div class="prop-modal-img-overlay">
          ${p.is_flagship ? '<div class="prop-tag" style="margin-bottom:12px"><i class="fas fa-star" style="font-size:8px"></i> Flagship Property</div>' : ''}
          <div style="font-family:var(--cinzel);font-size:9px;letter-spacing:3px;color:rgba(255,255,255,.6);text-transform:uppercase;margin-bottom:6px">${p.continent} · ${p.country}</div>
          <div style="font-family:var(--serif);font-size:36px;font-weight:300;color:#fff;margin-bottom:4px">${p.city}</div>
          <div style="font-size:14px;color:rgba(255,255,255,.7)">${p.name}</div>
        </div>
      </div>
      <button onclick="closePropModal()" style="position:absolute;top:16px;right:16px;background:rgba(0,0,0,.5);border:none;color:#fff;font-size:20px;cursor:pointer;width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px)">×</button>
    </div>
    <div class="prop-modal-body">
      <div>
        <div style="font-family:var(--cinzel);font-size:9px;letter-spacing:2px;color:var(--gold);text-transform:uppercase;margin-bottom:14px">About This Property</div>
        <p style="font-size:14px;color:var(--text2);line-height:1.8;margin-bottom:22px">${p.description || ''}</p>
        ${amenities.length ? `<div style="font-family:var(--cinzel);font-size:9px;letter-spacing:2px;color:var(--gold);text-transform:uppercase;margin-bottom:12px">Signature Amenities</div>
        <div class="prop-amenities">
          ${amenities.map(a => `<div class="prop-amenity"><i class="fas fa-check"></i>${a.trim()}</div>`).join('')}
        </div>` : ''}
      </div>
      <div>
        <div class="prop-sidebar-card">
          <h4>${p.city}, ${p.country}</h4>
          <div class="prop-detail-row"><span class="l">Rooms & Suites</span><span class="r">${p.rooms_count}</span></div>
          <div class="prop-detail-row"><span class="l">Star Rating</span><span class="r">${'★'.repeat(p.star_rating || 5)} ${p.star_rating || 5}-Star</span></div>
          <div class="prop-detail-row"><span class="l">Established</span><span class="r">${p.since_year || 2008}</span></div>
          ${p.phone ? `<div class="prop-detail-row"><span class="l">Phone</span><span class="r" style="font-size:11px">${p.phone}</span></div>` : ''}
          ${p.email ? `<div class="prop-detail-row"><span class="l">Email</span><span class="r" style="font-size:11px">${p.email}</span></div>` : ''}
          <a href="${BASE}/rooms.php" class="btn btn-gold btn-block" style="margin-top:20px"><i class="fas fa-calendar-check"></i> Book at ${p.city}</a>
          <a href="${BASE}/contact.php" class="btn btn-ghost btn-block" style="margin-top:8px;color:rgba(255,255,255,.6);border-color:rgba(255,255,255,.15)">Contact Property</a>
        </div>
      </div>
    </div>
  `;

  const modal = document.getElementById('propModal');
  modal.classList.add('open');
  if (window.anime) anime({ targets: '.prop-modal-box', scale:[.92,1], opacity:[0,1], duration:400, easing:'easeOutBack' });
}

function closePropModal() {
  const modal = document.getElementById('propModal');
  if (window.anime) {
    anime({ targets: '.prop-modal-box', scale:[1,.94], opacity:[1,0], duration:250, easing:'easeInExpo', complete: () => modal.classList.remove('open') });
  } else { modal.classList.remove('open'); }
}

// Animate map pins
document.addEventListener('DOMContentLoaded', () => {
  if (window.anime) {
    anime({ targets: '.map-pin', opacity:[0,1], scale:[0,1], duration:400, easing:'easeOutBack', delay: anime.stagger(80, {start:300}) });
    anime({ targets: '.prop-card', opacity:[0,1], translateY:[20,0], scale:[.97,1], duration:500, easing:'easeOutExpo', delay: anime.stagger(60, {start:200}) });
  }
});
</script>

<?php require __DIR__ . '/footer.php'; ?>
