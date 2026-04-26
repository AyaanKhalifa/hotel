<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Home';

// Global properties
try {
    $properties = $pdo->query("SELECT * FROM properties WHERE is_active=1 ORDER BY sort_order LIMIT 12")->fetchAll();
} catch(Exception $e) { $properties = []; }

// Room types
$roomTypes = $pdo->query("
    SELECT rt.*, ri.image_url,
        (SELECT AVG(rating) FROM room_ratings WHERE room_type_id=rt.id AND is_approved=1) avg_r,
        (SELECT COUNT(*) FROM room_ratings WHERE room_type_id=rt.id AND is_approved=1) rev_ct
    FROM room_types rt
    LEFT JOIN room_images ri ON ri.room_type_id=rt.id AND ri.is_primary=1
    ORDER BY rt.sort_order
")->fetchAll();

// Stats
$totalRooms  = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$availRooms  = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status='available'")->fetchColumn();
$totalGuests = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalProps  = count($properties) ?: 12;
$totalCountries = count(array_unique(array_column($properties,'country'))) ?: 8;

// Featured reviews
$reviews = $pdo->query("
    SELECT rr.*, rt.name room_name FROM room_ratings rr
    JOIN room_types rt ON rr.room_type_id=rt.id
    WHERE rr.is_approved=1
    ORDER BY rr.rating DESC, rr.created_at DESC LIMIT 6
")->fetchAll();

// Hero slides (multiple hotel properties for slideshow)
$heroSlides = [
    ['image'=>'https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=1920&q=85','city'=>'Dubai','tagline'=>'Where the Desert Meets the Sky'],
    ['image'=>'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?w=1920&q=85','city'=>'New York','tagline'=>'The Crown Jewel of Manhattan'],
    ['image'=>'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=1920&q=85','city'=>'Paris','tagline'=>"L'Essence du Luxe"],
    ['image'=>'https://images.unsplash.com/photo-1573843981267-be1999ff37cd?w=1920&q=85','city'=>'Maldives','tagline'=>'A Paradise Found'],
    ['image'=>'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=1920&q=85','city'=>'Tokyo','tagline'=>'Where East Meets Eternity'],
];

require __DIR__ . '/header.php';
?>

<!-- Anime.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
<!-- Split.js for text splitting -->
<style>
/* ════════════════════════════════════════════════════════════
   HOME PAGE — JUMEIRAH / FOUR SEASONS LEVEL
   ════════════════════════════════════════════════════════════ */

/* ── HERO: Full-screen slideshow ── */
.hero-fs {
  height: 100vh; min-height: 700px; position: relative;
  overflow: hidden; display: flex; align-items: center;
}
.hero-slides { position: absolute; inset: 0; }
.hero-slide {
  position: absolute; inset: 0; opacity: 0;
  background-size: cover; background-position: center;
  transition: opacity 1.6s cubic-bezier(.4,0,.2,1);
}
.hero-slide.active { opacity: 1; }
.hero-slide::after {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(to bottom right,
    rgba(0,0,0,.55) 0%,
    rgba(0,0,0,.25) 50%,
    rgba(0,0,0,.5) 100%);
}
/* Ken Burns on active slide */
.hero-slide.active { animation: kenBurns 12s ease forwards; }
@keyframes kenBurns { 0%{transform:scale(1)} 100%{transform:scale(1.08)} }

.hero-content-wrap {
  position: relative; z-index: 5; color: #fff;
  width: 100%; padding-top: 70px;
}
.hero-city-label {
  font-family: var(--cinzel); font-size: 10px; letter-spacing: 6px;
  text-transform: uppercase; color: var(--gold); margin-bottom: 18px;
  display: flex; align-items: center; gap: 12px; opacity: 0;
}
.hero-city-label::before { content: ''; width: 36px; height: 1px; background: var(--gold); }
.hero-h1 {
  font-family: var(--serif); font-size: clamp(44px,8vw,96px);
  font-weight: 300; line-height: 1.05; letter-spacing: -2px;
  max-width: 800px; margin-bottom: 20px; opacity: 0;
}
.hero-h1 span { display: block; overflow: hidden; }
.hero-h1 span em { font-style: italic; color: var(--gold); display: block; }
.hero-tagline {
  font-size: 17px; color: rgba(255,255,255,.75); max-width: 500px;
  line-height: 1.8; margin-bottom: 36px; opacity: 0;
}
.hero-btns { display: flex; gap: 14px; flex-wrap: wrap; opacity: 0; }
.btn-hero-primary {
  padding: 16px 40px; background: var(--gold); color: #fff; border: none;
  font-family: var(--cinzel); font-size: 10px; letter-spacing: 3px;
  text-transform: uppercase; cursor: pointer; transition: all .3s;
  text-decoration: none; display: inline-flex; align-items: center; gap: 10px;
}
.btn-hero-primary:hover { background: var(--gold-dk); color: #fff; transform: translateY(-2px); box-shadow: 0 8px 28px rgba(192,155,91,.45); }
.btn-hero-secondary {
  padding: 15px 36px; background: transparent; color: #fff;
  border: 1px solid rgba(255,255,255,.4); font-family: var(--cinzel);
  font-size: 10px; letter-spacing: 3px; text-transform: uppercase; cursor: pointer;
  transition: all .3s; text-decoration: none; display: inline-flex; align-items: center; gap: 10px;
}
.btn-hero-secondary:hover { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.7); color: #fff; }

/* Slide counter */
.slide-counter {
  position: absolute; bottom: 120px; right: 48px; z-index: 10;
  color: #fff; display: flex; align-items: center; gap: 12px;
}
.slide-num { font-family: var(--serif); font-size: 28px; font-weight: 300; color: var(--gold); }
.slide-total { font-size: 14px; color: rgba(255,255,255,.4); }
.slide-dots { display: flex; flex-direction: column; gap: 8px; }
.slide-dot { width: 2px; height: 20px; background: rgba(255,255,255,.25); cursor: pointer; transition: all .3s; }
.slide-dot.act { background: var(--gold); height: 32px; }

/* Slide arrows */
.hero-arrows { position: absolute; right: 48px; top: 50%; transform: translateY(-50%); z-index: 10; display: flex; flex-direction: column; gap: 10px; }
.hero-arrow { width: 44px; height: 44px; border: 1px solid rgba(255,255,255,.25); background: transparent; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .2s; }
.hero-arrow:hover { background: var(--gold); border-color: var(--gold); }

/* Scroll line */
.scroll-line {
  position: absolute; bottom: 0; left: 48px; z-index: 10;
  display: flex; flex-direction: column; align-items: center; gap: 8px;
}
.scroll-line-text { font-family: var(--cinzel); font-size: 9px; letter-spacing: 3px; color: rgba(255,255,255,.5); writing-mode: vertical-rl; }
.scroll-line-bar { width: 1px; height: 60px; background: rgba(255,255,255,.2); position: relative; overflow: hidden; }
.scroll-line-bar::after { content: ''; position: absolute; top: -60px; left: 0; width: 100%; height: 100%; background: var(--gold); animation: scrollDown 2s ease infinite; }
@keyframes scrollDown { 0%{top:-60px} 100%{top:60px} }

/* ── SEARCH DOCK (below hero) ── */
.search-dock {
  background: var(--charcoal); position: relative; z-index: 20;
  margin-top: -1px;
}
.search-dock-inner {
  display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto;
  gap: 0; max-width: 1240px; margin: 0 auto;
}
.sd-field {
  padding: 22px 28px; border-right: 1px solid rgba(255,255,255,.06);
  display: flex; flex-direction: column; gap: 5px; cursor: pointer;
  transition: background .2s;
}
.sd-field:hover, .sd-field:focus-within { background: rgba(255,255,255,.04); }
.sd-label { font-family: var(--cinzel); font-size: 8px; letter-spacing: 2px; color: var(--gold); text-transform: uppercase; }
.sd-ctrl {
  background: transparent; border: none; color: #fff; font-family: var(--sans);
  font-size: 15px; outline: none; width: 100%; cursor: pointer;
}
.sd-ctrl::placeholder { color: rgba(255,255,255,.4); font-size: 13px; }
.sd-ctrl option { background: #1c1813; color: #fff; }
.sd-btn {
  padding: 0 40px; background: var(--gold); border: none; cursor: pointer;
  font-family: var(--cinzel); font-size: 10px; letter-spacing: 3px;
  text-transform: uppercase; color: #fff; transition: background .2s;
  display: flex; align-items: center; gap: 10px; white-space: nowrap;
}
.sd-btn:hover { background: var(--gold-dk); }
.sd-checking { display: flex; align-items: center; gap: 8px; color: var(--gold); font-size: 12px; padding: 22px 28px; }

/* ── MARQUEE ── */
.ticker { background: var(--cream2); border-top: 1px solid var(--sand); border-bottom: 1px solid var(--sand); overflow: hidden; padding: 14px 0; }
[data-theme="dark"] .ticker { background: var(--card2); border-color: var(--bdr2); }
.ticker-inner { display: inline-block; animation: tickerRun 50s linear infinite; white-space: nowrap; }
@keyframes tickerRun { 0%{transform:translateX(0)} 100%{transform:translateX(-50%)} }
.ticker-item { display: inline-flex; align-items: center; gap: 14px; padding: 0 44px; font-family: var(--cinzel); font-size: 9px; letter-spacing: 3px; color: var(--muted); text-transform: uppercase; }
.ticker-gem { color: var(--gold); font-size: 8px; }

/* ── WORLD PROPERTIES SECTION ── */
.world-section { background: var(--charcoal); padding: 100px 0; position: relative; overflow: hidden; }
.world-bg-map { position: absolute; inset: 0; opacity: .04; background: url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/80/World_map_-_low_resolution.svg/2560px-World_map_-_low_resolution.svg.png') center/contain no-repeat; }
.world-section-header { text-align: center; margin-bottom: 64px; position: relative; z-index: 1; }
.world-h2 { font-family: var(--serif); font-size: clamp(36px,5vw,62px); font-weight: 300; color: #fff; margin-bottom: 14px; }
.world-h2 em { color: var(--gold); font-style: italic; }
.world-sub { font-size: 15px; color: rgba(255,255,255,.5); max-width: 560px; margin: 0 auto; line-height: 1.8; }

/* World stats */
.world-stats { display: flex; justify-content: center; gap: 60px; margin-bottom: 72px; flex-wrap: wrap; }
.ws-item { text-align: center; }
.ws-num { font-family: var(--serif); font-size: 52px; font-weight: 300; color: var(--gold); line-height: 1; }
.ws-lbl { font-family: var(--cinzel); font-size: 8px; letter-spacing: 3px; color: rgba(255,255,255,.35); margin-top: 8px; text-transform: uppercase; }

/* Properties grid */
.props-showcase { position: relative; z-index: 1; }
.props-tabs { display: flex; justify-content: center; gap: 2px; margin-bottom: 40px; flex-wrap: wrap; }
.prop-tab { padding: 10px 22px; font-family: var(--cinzel); font-size: 9px; letter-spacing: 2px; text-transform: uppercase; cursor: pointer; border: 1px solid rgba(255,255,255,.1); background: transparent; color: rgba(255,255,255,.4); transition: all .2s; }
.prop-tab:hover, .prop-tab.active { background: var(--gold); border-color: var(--gold); color: #fff; }

.props-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(300px,1fr)); gap: 2px; }
.prop-card {
  position: relative; overflow: hidden; cursor: pointer;
  height: 340px; display: none; background: var(--charcoal2);
}
.prop-card.visible { display: block; }
.prop-card img { width: 100%; height: 100%; object-fit: cover; transition: transform .7s cubic-bezier(.25,.46,.45,.94), filter .5s; filter: brightness(.85); }
.prop-card:hover img { transform: scale(1.08); filter: brightness(.65); }
.prop-card-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(0,0,0,.85) 0%, rgba(0,0,0,.1) 50%);
  display: flex; flex-direction: column; justify-content: flex-end;
  padding: 24px;
  transition: background .3s;
}
.prop-card:hover .prop-card-overlay { background: linear-gradient(to top, rgba(0,0,0,.9) 0%, rgba(0,0,0,.25) 100%); }
.prop-flag { font-size: 20px; margin-bottom: 6px; }
.prop-city { font-family: var(--cinzel); font-size: 9px; letter-spacing: 3px; color: var(--gold); text-transform: uppercase; margin-bottom: 4px; }
.prop-name { font-family: var(--serif); font-size: 22px; font-weight: 300; color: #fff; margin-bottom: 6px; line-height: 1.2; }
.prop-tagline { font-size: 12px; color: rgba(255,255,255,.55); line-height: 1.5; max-width: 260px; }
.prop-cta { margin-top: 14px; display: flex; align-items: center; gap: 8px; font-family: var(--cinzel); font-size: 9px; letter-spacing: 2px; text-transform: uppercase; color: var(--gold); opacity: 0; transform: translateY(10px); transition: all .3s; }
.prop-card:hover .prop-cta { opacity: 1; transform: translateY(0); }
.prop-price { font-family: var(--serif); font-size: 14px; color: rgba(255,255,255,.6); }
.prop-price span { color: var(--gold); font-size: 18px; }
/* Featured property (first one, spans 2 cols) */
.prop-card.featured { height: auto; }

/* ── ROOMS EDITORIAL ── */
.rooms-editorial {
  display: grid;
  grid-template-columns: repeat(4,1fr);
  gap: 1px; background: var(--sand);
}
[data-theme="dark"] .rooms-editorial { background: var(--bdr2); }
.re-item { position: relative; overflow: hidden; background: var(--card); }
.re-item img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .6s, filter .4s; }
.re-item:hover img { transform: scale(1.07); filter: brightness(.75); }
.re-item-1, .re-item-2 { grid-column: span 2; }
.re-item-1 { height: 500px; }
.re-item-2 { height: 500px; }
.re-item-3, .re-item-4 { height: 320px; }
.re-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(0,0,0,.75) 0%, transparent 55%);
  display: flex; flex-direction: column; justify-content: flex-end;
  padding: 28px 32px;
}
.re-type { font-family: var(--cinzel); font-size: 9px; letter-spacing: 3px; color: var(--gold); text-transform: uppercase; margin-bottom: 6px; }
.re-name { font-family: var(--serif); font-size: clamp(20px,2.5vw,30px); font-weight: 300; color: #fff; margin-bottom: 6px; }
.re-price { font-family: var(--serif); font-size: 16px; color: rgba(255,255,255,.7); }
.re-price span { color: var(--gold-lt); }
.re-btns { display: flex; gap: 10px; margin-top: 16px; opacity: 0; transform: translateY(8px); transition: all .3s; }
.re-item:hover .re-btns { opacity: 1; transform: translateY(0); }
.re-btn { padding: 9px 20px; font-family: var(--cinzel); font-size: 9px; letter-spacing: 2px; cursor: pointer; border: none; text-transform: uppercase; }
.re-btn-book { background: var(--gold); color: #fff; }
.re-btn-more { background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.25) !important; }

/* ── EXPERIENCES STRIP ── */
.exp-strip { display: grid; grid-template-columns: repeat(6,1fr); gap: 1px; background: var(--sand); }
[data-theme="dark"] .exp-strip { background: var(--bdr2); }
.exp-cell { position: relative; overflow: hidden; cursor: pointer; }
.exp-cell img { width: 100%; height: 280px; object-fit: cover; display: block; transition: transform .5s; filter: brightness(.75); }
.exp-cell:hover img { transform: scale(1.08); filter: brightness(.55); }
.exp-overlay { position: absolute; inset: 0; display: flex; flex-direction: column; justify-content: flex-end; padding: 20px; }
.exp-icon { font-size: 24px; margin-bottom: 6px; }
.exp-name { font-family: var(--cinzel); font-size: 10px; letter-spacing: 2px; color: #fff; text-transform: uppercase; }
.exp-hover { font-size: 12px; color: rgba(255,255,255,.65); margin-top: 4px; opacity: 0; transform: translateY(6px); transition: all .3s; }
.exp-cell:hover .exp-hover { opacity: 1; transform: translateY(0); }

/* ── WHY ROYALE VISTA ── */
.why-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 0; }
.why-cell {
  padding: 44px 40px; border: 1px solid var(--bdr2);
  position: relative; overflow: hidden; transition: background .25s;
}
.why-cell:hover { background: var(--gold-dim); }
.why-cell::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 2px; background: var(--gold); transform: scaleX(0); transition: transform .4s var(--ease); transform-origin: left; }
.why-cell:hover::before { transform: scaleX(1); }
.why-num { font-family: var(--cinzel); font-size: 10px; letter-spacing: 3px; color: var(--gold); margin-bottom: 14px; display: block; }
.why-icon-wrap { width: 52px; height: 52px; border: 1px solid var(--border); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 18px; transition: all .3s; }
.why-cell:hover .why-icon-wrap { background: var(--gold); border-color: var(--gold); }
.why-title { font-family: var(--serif); font-size: 22px; font-weight: 400; margin-bottom: 10px; }
.why-desc { font-size: 13.5px; color: var(--text2); line-height: 1.8; }

/* ── REVIEW SECTION ── */
.review-masonry { columns: 3 280px; gap: 20px; }
.rm-card {
  break-inside: avoid; margin-bottom: 20px;
  background: var(--card); border: 1px solid var(--bdr2);
  border-radius: var(--radius-lg); padding: 24px;
  transition: all .3s;
}
.rm-card:hover { border-color: rgba(192,155,91,.3); transform: translateY(-2px); box-shadow: var(--shadow2); }
.rm-quote { font-family: var(--serif); font-size: 36px; color: var(--gold); opacity: .3; line-height: 1; margin-bottom: 6px; }
.rm-text { font-family: var(--serif); font-size: 16px; font-weight: 300; font-style: italic; line-height: 1.65; margin-bottom: 16px; }
.rm-author { display: flex; align-items: center; gap: 12px; }
.rm-av { width: 40px; height: 40px; border-radius: 50%; background: var(--gold); display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700; color: #fff; flex-shrink: 0; }

/* ── CTA PARALLAX ── */
.cta-parallax {
  height: 560px; position: relative; overflow: hidden;
  display: flex; align-items: center; justify-content: center;
}
.cta-bg {
  position: absolute; inset: -60px; z-index: 0;
  background: url('https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=1600&q=80') center/cover;
}
.cta-bg::after { content:'';position:absolute;inset:0;background:rgba(0,0,0,.6); }
.cta-content { position: relative; z-index: 1; text-align: center; color: #fff; padding: 0 20px; }
.cta-overline { font-family: var(--cinzel); font-size: 9px; letter-spacing: 5px; text-transform: uppercase; color: var(--gold); margin-bottom: 18px; }
.cta-h2 { font-family: var(--serif); font-size: clamp(36px,6vw,68px); font-weight: 300; line-height: 1.1; margin-bottom: 18px; }
.cta-sub { font-size: 16px; color: rgba(255,255,255,.7); max-width: 500px; margin: 0 auto 36px; line-height: 1.8; }
.cta-award-row { display: flex; justify-content: center; gap: 32px; margin-top: 36px; flex-wrap: wrap; }
.cta-award { text-align: center; }
.cta-award-icon { font-size: 28px; margin-bottom: 6px; }
.cta-award-name { font-family: var(--cinzel); font-size: 8px; letter-spacing: 2px; color: rgba(255,255,255,.5); text-transform: uppercase; }

/* ── STATS COUNTER ── */
.stats-dark { background: var(--charcoal); padding: 64px 0; }
.stats-dark-grid { display: grid; grid-template-columns: repeat(5,1fr); gap: 0; }
.sd-stat { padding: 28px 20px; text-align: center; border-right: 1px solid rgba(255,255,255,.06); }
.sd-stat:last-child { border-right: none; }
.sd-num { font-family: var(--serif); font-size: 46px; font-weight: 300; color: var(--gold); line-height: 1; }
.sd-lbl { font-family: var(--cinzel); font-size: 8px; letter-spacing: 2px; color: rgba(255,255,255,.35); margin-top: 8px; text-transform: uppercase; }

/* ── AVAILABILITY MODAL ── */
.avail-modal { position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.8);backdrop-filter:blur(12px);display:none;align-items:center;justify-content:center;padding:20px; }
.avail-modal.open { display:flex; }
.avail-box { background:var(--card);border-radius:12px;width:480px;max-width:100%;overflow:hidden;box-shadow:0 32px 80px rgba(0,0,0,.4);animation:modalIn .35s cubic-bezier(.34,1.56,.64,1);border:1px solid var(--border); }
@keyframes modalIn { from{opacity:0;transform:scale(.94) translateY(20px)} to{opacity:1;transform:none} }
.avail-box-hd { background:var(--charcoal);padding:28px;text-align:center;position:relative; }
.avail-check-icon { width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;animation:bounceIn .5s; }
@keyframes bounceIn { 0%{transform:scale(0)} 60%{transform:scale(1.15)} 100%{transform:scale(1)} }
.avail-box-hd h3 { font-family:var(--serif);font-size:26px;font-weight:300;color:#fff; }
.avail-box-hd p { font-size:14px;color:rgba(255,255,255,.6);margin-top:8px;line-height:1.6; }
.avail-box-bd { padding:26px; }
.avail-row { display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--bdr2);font-size:14px; }
.avail-row:last-child { border-bottom:none; }
.avail-row .lbl { color:var(--muted); }
.avail-row .val { font-weight:600; }
.avail-row.total .val { font-family:var(--serif);font-size:22px;color:var(--gold); }
.avail-actions { display:flex;gap:12px;margin-top:20px; }
.avail-book-btn { flex:2;padding:14px;background:var(--gold);color:#fff;border:none;border-radius:var(--radius);font-family:var(--cinzel);font-size:10px;letter-spacing:2px;text-transform:uppercase;cursor:pointer;transition:background .2s;display:flex;align-items:center;justify-content:center;gap:8px; }
.avail-book-btn:hover { background:var(--gold-dk); }
.avail-close-btn { flex:1;padding:14px;background:var(--card2);border:1px solid var(--border);border-radius:var(--radius);cursor:pointer;color:var(--text2);font-family:var(--sans);font-size:14px;transition:all .2s; }
.avail-close-btn:hover { border-color:var(--red);color:var(--red); }

@media(max-width:1100px) { .stats-dark-grid{grid-template-columns:repeat(3,1fr)} .rooms-editorial{grid-template-columns:1fr 1fr} .re-item-1,.re-item-2{grid-column:span 1;height:320px} .exp-strip{grid-template-columns:repeat(3,1fr)} .why-grid{grid-template-columns:1fr 1fr} }
@media(max-width:768px) {
  .search-dock-inner{grid-template-columns:1fr 1fr;padding:0} .sd-field{padding:16px 18px} .sd-btn{grid-column:1/-1;justify-content:center;padding:18px}
  .world-stats{gap:30px} .ws-num{font-size:36px}
  .rooms-editorial,.exp-strip{grid-template-columns:1fr} .re-item-1,.re-item-2,.re-item-3,.re-item-4{height:260px}
  .review-masonry{columns:1} .props-grid{grid-template-columns:1fr 1fr} .why-grid{grid-template-columns:1fr} .stats-dark-grid{grid-template-columns:repeat(2,1fr)}
  .slide-counter,.hero-arrows,.scroll-line{display:none}
}
@media(max-width:480px) { .props-grid{grid-template-columns:1fr} }
</style>

<!-- ════════════════════════════════════════════
     HERO — FULL-SCREEN SLIDESHOW
     ════════════════════════════════════════════ -->
<section class="hero-fs">
  <div class="hero-slides" id="heroSlides">
    <?php foreach ($heroSlides as $i => $slide): ?>
    <div class="hero-slide <?= $i===0?'active':'' ?>"
         style="background-image:url('<?= htmlspecialchars($slide['image']) ?>')"
         data-city="<?= htmlspecialchars($slide['city']) ?>"
         data-tagline="<?= htmlspecialchars($slide['tagline']) ?>">
    </div>
    <?php endforeach; ?>
  </div>

  <div class="hero-content-wrap">
    <div class="container">
      <div class="hero-city-label" id="hCity"><?= htmlspecialchars($heroSlides[0]['city']) ?></div>
      <h1 class="hero-h1" id="hTitle">
        <span>Where <em><?= htmlspecialchars($heroSlides[0]['tagline']) ?></em></span>
      </h1>
      <p class="hero-tagline" id="hTagline">Experience the pinnacle of luxury hospitality across <?= $totalProps ?> world-class properties in <?= $totalCountries ?> countries.</p>
      <div class="hero-btns" id="hBtns">
        <a href="<?= $B ?>/rooms.php" class="btn-hero-primary">
          <i class="fas fa-bed" style="font-size:12px"></i> Reserve a Room
        </a>
        <a href="<?= $B ?>/locations.php" class="btn-hero-secondary">
          <i class="fas fa-globe" style="font-size:12px"></i> Our Properties
        </a>
      </div>
    </div>
  </div>

  <!-- Slide controls -->
  <div class="slide-counter">
    <span class="slide-num" id="slideNum">01</span>
    <span class="slide-total">/ <?= str_pad(count($heroSlides),2,'0',STR_PAD_LEFT) ?></span>
    <div class="slide-dots" id="slideDots">
      <?php foreach ($heroSlides as $i => $_): ?>
      <div class="slide-dot <?= $i===0?'act':'' ?>" onclick="goSlide(<?= $i ?>)"></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="hero-arrows">
    <button class="hero-arrow" onclick="changeSlide(-1)"><i class="fas fa-chevron-up"></i></button>
    <button class="hero-arrow" onclick="changeSlide(1)"><i class="fas fa-chevron-down"></i></button>
  </div>
  <div class="scroll-line">
    <span class="scroll-line-text">Scroll</span>
    <div class="scroll-line-bar"></div>
  </div>
</section>

<!-- ════════════════════════════════════════════
     SEARCH DOCK
     ════════════════════════════════════════════ -->
<div class="search-dock">
  <form id="searchDockForm" onsubmit="return handleSearch(event)">
    <div class="search-dock-inner" id="searchDockInner">
      <div class="sd-field">
        <span class="sd-label">Room Type</span>
        <select class="sd-ctrl" id="sdType">
          <option value="">Any Room Type</option>
          <?php foreach ($roomTypes as $rt): ?>
          <option value="<?= $rt['id'] ?>"><?= htmlspecialchars($rt['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="sd-field">
        <span class="sd-label">Check-In</span>
        <input type="date" class="sd-ctrl" id="sdCI" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>">
      </div>
      <div class="sd-field">
        <span class="sd-label">Check-Out</span>
        <input type="date" class="sd-ctrl" id="sdCO" value="<?= date('Y-m-d',strtotime('+2 days')) ?>" min="<?= date('Y-m-d',strtotime('+1 day')) ?>">
      </div>
      <div class="sd-field" style="border-right:none">
        <span class="sd-label">Guests</span>
        <select class="sd-ctrl" id="sdGuests">
          <?php for($g=1;$g<=8;$g++): ?><option value="<?=$g?>" <?=$g===2?'selected':''?>><?=$g?> <?=$g===1?'Guest':'Guests'?></option><?php endfor; ?>
        </select>
      </div>
      <button type="submit" class="sd-btn" id="sdBtn">
        <i class="fas fa-search" style="font-size:12px"></i>
        Check Availability
      </button>
    </div>
    <div class="sd-checking" id="sdChecking" style="display:none">
      <i class="fas fa-circle-notch fa-spin"></i> Checking real-time availability…
    </div>
  </form>
</div>

<!-- ════════════════════════════════════════════
     TICKER
     ════════════════════════════════════════════ -->
<div class="ticker">
  <div class="ticker-inner">
    <?php
    $tItems = ['12 World Properties','30 Currencies Accepted','Habibi AI Concierge 24/7','Award-Winning Spa','Michelin-Star Dining','Royale Rewards Programme','Overwater Villas Maldives','Rooftop Infinity Pools','Airport Limousine Service','Best Rate Guaranteed','Presidential Suites Available'];
    foreach (array_merge($tItems,$tItems) as $ti):
    ?>
    <span class="ticker-item"><span class="ticker-gem">◆</span><?= $ti ?></span>
    <?php endforeach; ?>
  </div>
</div>

<!-- ════════════════════════════════════════════
     WORLD PROPERTIES
     ════════════════════════════════════════════ -->
<section class="world-section">
  <div class="world-bg-map"></div>
  <div class="world-section-header">
    <div class="lx-eyebrow" style="justify-content:center;color:rgba(255,255,255,.4);" data-reveal="up">A Global Presence</div>
    <h2 class="world-h2" data-reveal="up" data-delay="100">Our Properties,<br><em>Your World</em></h2>
    <p class="world-sub" data-reveal="up" data-delay="200">From the deserts of Arabia to the canals of Venice, Royale Vista is where exceptional lives happen.</p>
  </div>

  <!-- Stats -->
  <div class="world-stats rv-counter-section" data-reveal="up" data-delay="100">
    <div class="ws-item"><div class="ws-num" data-count="<?= $totalProps ?>">0</div><div class="ws-lbl">Properties</div></div>
    <div class="ws-item"><div class="ws-num" data-count="<?= $totalCountries ?>">0</div><div class="ws-lbl">Countries</div></div>
    <div class="ws-item"><div class="ws-num" data-count="<?= $totalRooms + 500 ?>">0</div><div class="ws-lbl">Rooms & Suites</div></div>
    <div class="ws-item"><div class="ws-num" data-count="50000">0</div><div class="ws-lbl">Annual Guests</div></div>
    <div class="ws-item"><div class="ws-num" data-count="2008">0</div><div class="ws-lbl">Est. Year</div></div>
  </div>

  <!-- Continent tabs -->
  <div class="props-tabs" id="propsTabs">
    <button class="prop-tab active" onclick="filterProps('all',this)">All</button>
    <?php
    $continents = array_unique(array_column($properties,'continent'));
    foreach ($continents as $cont): ?>
    <button class="prop-tab" onclick="filterProps('<?= htmlspecialchars(addslashes($cont)) ?>',this)"><?= htmlspecialchars($cont) ?></button>
    <?php endforeach; ?>
  </div>

  <!-- Properties grid -->
  <div class="props-grid" id="propsGrid">
    <?php foreach ($properties as $i => $prop):
      $flags = ['US'=>'🇺🇸','AE'=>'🇦🇪','GB'=>'🇬🇧','FR'=>'🇫🇷','JP'=>'🇯🇵','SG'=>'🇸🇬','MV'=>'🇲🇻','IT'=>'🇮🇹','ID'=>'🇮🇩','AU'=>'🇦🇺','GR'=>'🇬🇷','MA'=>'🇲🇦'];
      $flag = $flags[$prop['country_code'] ?? ''] ?? '🏨';
    ?>
    <div class="prop-card visible rv-tilt"
         data-continent="<?= htmlspecialchars($prop['continent']) ?>"
         onclick="window.location='<?= $B ?>/locations.php?property=<?= htmlspecialchars($prop['slug']) ?>'">
      <img src="<?= htmlspecialchars($prop['hero_image']) ?>"
           alt="<?= htmlspecialchars($prop['name']) ?>"
           loading="lazy"
           onerror="this.src='https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=600&q=60'">
      <div class="prop-card-overlay">
        <div class="prop-flag"><?= $flag ?></div>
        <div class="prop-city"><?= htmlspecialchars($prop['city']) ?>, <?= htmlspecialchars($prop['country']) ?></div>
        <div class="prop-name"><?= htmlspecialchars($prop['name']) ?></div>
        <div class="prop-tagline"><?= htmlspecialchars(mb_strimwidth($prop['headline']??'',0,60,'…')) ?></div>
        <div class="prop-price">From <span><?= formatPrice($prop['min_price_usd']) ?></span>/night</div>
        <div class="prop-cta"><i class="fas fa-arrow-right" style="font-size:10px"></i> Explore Property</div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div style="text-align:center;margin-top:40px">
    <a href="<?= $B ?>/locations.php" class="btn-hero-secondary" style="display:inline-flex">
      <i class="fas fa-globe" style="font-size:11px;margin-right:8px"></i> View All <?= $totalProps ?> Properties
    </a>
  </div>
</section>

<!-- ════════════════════════════════════════════
     ROOMS EDITORIAL GRID
     ════════════════════════════════════════════ -->
<section class="section" style="padding-bottom:0">
  <div class="container" style="margin-bottom:40px">
    <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:16px">
      <div>
        <div class="lx-eyebrow lx-reveal"><?= t('explore_rooms', 'Explore Our Rooms') ?></div>
        <h2 class="lx-heading lx-reveal" style="transition-delay:.1s"><?= t('rooms_heading', 'A Sanctuary of Comfort') ?></h2>
      </div>
      <a href="<?= $B ?>/rooms.php" class="btn btn-ghost" data-reveal="right">View All Rooms →</a>
    </div>
  </div>
</section>
<div class="rooms-editorial">
  <?php
  $reImgs = [
    'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=1200&q=80',
    'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=900&q=80',
    'https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?w=900&q=80',
    'https://images.unsplash.com/photo-1590496865381-1ce091b5a43f?w=900&q=80',
  ];
  foreach ($roomTypes as $i => $rt):
    $img = $rt['image_url'] ?: ($reImgs[$i] ?? $reImgs[0]);
    $classes = ['re-item-1','re-item-2','re-item-3','re-item-4'];
    $cls = $classes[$i] ?? 're-item-3';
  ?>
  <div class="re-item <?= $cls ?> rv-tilt">
    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($rt['name']) ?>" loading="lazy"
         onerror="this.style.opacity='0'">
    <div class="re-overlay">
      <div class="re-type">Premium Suite</div>
      <div class="re-name"><?= htmlspecialchars($rt['name']) ?></div>
      <div class="re-price">From <span><?= formatPrice($rt['price_usd']) ?></span>/night</div>
      <div class="re-btns">
        <button class="re-btn re-btn-book" onclick="event.stopPropagation();window.location='<?= $B ?>/rooms.php?type=<?= $rt['id'] ?>'">Reserve</button>
        <button class="re-btn re-btn-more" onclick="event.stopPropagation();window.location='<?= $B ?>/rooms.php?type=<?= $rt['id'] ?>'">Details</button>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ════════════════════════════════════════════
     EXPERIENCES STRIP
     ════════════════════════════════════════════ -->
<section style="padding:80px 0">
  <div class="container" style="margin-bottom:40px;text-align:center">
    <div class="lx-eyebrow" style="justify-content:center" data-reveal="up">Beyond the Room</div>
    <h2 class="lx-heading" style="text-align:center" data-reveal="up" data-delay="100">Curated <em>Experiences</em></h2>
  </div>
  <div class="exp-strip">
    <?php foreach ([
      ['Fine Dining','Michelin-starred restaurants and five distinct venues','https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600&q=75','🍽','/dining.php'],
      ['Spa & Wellness','18-room Aria Spa with ancient healing rituals','https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=600&q=75','🧖','/spa.php'],
      ['Events & Weddings','Grand ballrooms and intimate garden settings','https://images.unsplash.com/photo-1519225421980-715cb0215aed?w=600&q=75','🎊','/events.php'],
      ['Concierge','Personalised experiences tailored just for you','https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600&q=75','🎩','/concierge.php'],
      ['Adventures','Curated city tours, safaris, and private charters','https://images.unsplash.com/photo-1488085061387-422e29b40080?w=600&q=75','🌍','/experiences.php'],
      ['Gift Cards','Give the gift of extraordinary stays','https://images.unsplash.com/photo-1513267048331-5611cad62e41?w=600&q=75','🎁','/gift-cards.php'],
    ] as [$name,$desc,$img,$icon,$url]): ?>
    <div class="exp-cell" onclick="window.location='<?= $B . $url ?>'">
      <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($name) ?>" loading="lazy" onerror="this.style.opacity='0'">
      <div class="exp-overlay">
        <div class="exp-icon"><?= $icon ?></div>
        <div class="exp-name"><?= htmlspecialchars($name) ?></div>
        <div class="exp-hover"><?= htmlspecialchars($desc) ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ════════════════════════════════════════════
     WHY ROYALE VISTA
     ════════════════════════════════════════════ -->
<section style="background:var(--card2);padding:80px 0">
  <div class="container">
    <div style="text-align:center;margin-bottom:56px">
      <div class="lx-eyebrow" style="justify-content:center" data-reveal="up">Our Commitment</div>
      <h2 class="lx-heading" style="text-align:center" data-reveal="up" data-delay="100">The Royale Vista <em>Difference</em></h2>
    </div>
    <div class="why-grid">
      <?php foreach ([
        ['01','fas fa-award','Forbes Five-Star Certified','Every property holds Forbes Travel Guide Five-Star certification, placing us among fewer than 200 hotels worldwide.'],
        ['02','fas fa-hand-sparkles','Bespoke Service','Our legendary butler-to-guest ratio means every request, no matter how extraordinary, is fulfilled with grace.'],
        ['03','fas fa-globe','Global Collection','12 properties across 4 continents in the world\'s most coveted destinations, each a masterpiece of local culture.'],
        ['04','fas fa-leaf','Sustainable Luxury','Carbon-neutral by 2026. Locally sourced, zero-waste kitchens, and community partnerships in every destination.'],
        ['05','fas fa-coins','Royale Rewards','Earn on every stay, every dinner, every treatment. Platinum members enjoy complimentary nights and guaranteed upgrades.'],
        ['06','fas fa-crown','Private Members','Exclusive access to members-only suites, private islands, and charter services — privileges money alone cannot buy.'],
      ] as [$num,$icon,$title,$desc]): ?>
      <div class="why-cell" data-reveal="up">
        <span class="why-num"><?= $num ?></span>
        <div class="why-icon-wrap"><i class="<?= $icon ?>" style="color:var(--gold)"></i></div>
        <div class="why-title"><?= $title ?></div>
        <div class="why-desc"><?= $desc ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ════════════════════════════════════════════
     STATS COUNTER
     ════════════════════════════════════════════ -->
<div class="stats-dark rv-counter-section">
  <div class="container">
    <div class="stats-dark-grid">
      <?php foreach ([
        [$totalProps,'Properties Worldwide'],
        [$totalRooms+500,'Rooms & Suites'],
        [50000,'Guests Annually'],
        [25,'Years of Excellence'],
        [98,'Guest Satisfaction %'],
      ] as [$n,$l]): ?>
      <div class="sd-stat">
        <div class="sd-num" data-count="<?= $n ?>">0</div>
        <div class="sd-lbl"><?= $l ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════════
     REVIEWS MASONRY
     ════════════════════════════════════════════ -->
<?php if (!empty($reviews)): ?>
<section class="section">
  <div class="container">
    <div style="text-align:center;margin-bottom:52px">
      <div class="lx-eyebrow" style="justify-content:center" data-reveal="up">Guest Stories</div>
      <h2 class="lx-heading" style="text-align:center" data-reveal="up" data-delay="100">Voices of Our <em>Guests</em></h2>
    </div>
    <div class="review-masonry">
      <?php foreach ($reviews as $rv): ?>
      <div class="rm-card" data-reveal="up">
        <div class="rm-quote">"</div>
        <p class="rm-text"><?= htmlspecialchars($rv['review']) ?></p>
        <div class="rm-author">
          <?= userAvatar(null, $rv['guest_name'], 40) ?>
          <div>
            <div style="font-weight:600;font-size:14px"><?= htmlspecialchars($rv['guest_name']) ?></div>
            <div style="font-size:11px;color:var(--gold)"><?= str_repeat('★',$rv['rating']) ?> · <?= htmlspecialchars($rv['room_name']) ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:36px">
      <a href="<?= $B ?>/reviews.php" class="btn btn-outline">Read All Reviews →</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════
     CTA PARALLAX
     ════════════════════════════════════════════ -->
<section class="cta-parallax">
  <div class="cta-bg" data-parallax=".4"></div>
  <div class="cta-content">
    <div class="cta-overline">Begin Your Journey</div>
    <h2 class="cta-h2" data-reveal="up">Reserve Your Stay<br>at Royale Vista</h2>
    <p class="cta-sub" data-reveal="up" data-delay="100">Limited availability in our most coveted suites. Book directly for our best rates and exclusive member benefits.</p>
    <div data-reveal="up" data-delay="200" style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
      <a href="<?= $B ?>/rooms.php" class="btn-hero-primary">Book a Room</a>
      <a href="<?= $B ?>/membership.php" class="btn-hero-secondary">Join Membership</a>
    </div>
    <div class="cta-award-row" data-reveal="up" data-delay="300">
      <?php foreach ([['🏆',"Forbes 5-Star"],['⭐',"Condé Nast #1"],['🌿',"Eco-Certified"],['🎖',"World Travel Award"]] as [$ic,$lbl]): ?>
      <div class="cta-award">
        <div class="cta-award-icon"><?= $ic ?></div>
        <div class="cta-award-name"><?= $lbl ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ════════════════════════════════════════════
     SUSTAINABILITY PROMO
     ════════════════════════════════════════════ -->
<div class="lx-section" style="background:#0a0a0f;color:#fff;padding:100px 20px">
    <div class="container" style="text-align:center">
        <div class="lx-eyebrow lx-reveal" style="justify-content:center;color:var(--gold)">Eco-Luxury Promise</div>
        <h2 class="lx-heading lx-reveal" style="color:#fff;margin-bottom:20px;max-width:600px;margin-left:auto;margin-right:auto">A Greener Tomorrow</h2>
        <p class="lx-reveal" style="color:var(--muted);max-width:600px;margin:0 auto 40px;line-height:1.8">True luxury means preserving the world's most breathtaking destinations. Learn about our commitment to achieving zero-carbon operations by 2030 and our 100% renewable energy phase-in.</p>
        <a href="<?= $B ?>/sustainability.php" class="btn-gold lx-reveal">Explore Our Initiatives</a>
    </div>
</div>

<!-- ════════════════════════════════════════════
     AVAILABILITY RESULT MODAL
     ════════════════════════════════════════════ -->
<div class="avail-modal" id="availModal" onclick="if(event.target===this)closeAvail()">
  <div class="avail-box" id="availBox"></div>
</div>

<!-- ════════════════════════════════════════════
     JAVASCRIPT
     ════════════════════════════════════════════ -->
<script>
const B = '<?= $B ?>';
const ROOM_TYPES = <?= json_encode(array_map(fn($r)=>['id'=>$r['id'],'name'=>$r['name'],'price_usd'=>(float)$r['price_usd']],$roomTypes)) ?>;

// ── HERO SLIDESHOW ───────────────────────────────────────────
const slides = document.querySelectorAll('.hero-slide');
const dots   = document.querySelectorAll('.slide-dot');
let   curSlide = 0, slideTimer;

function goSlide(n) {
  slides[curSlide].classList.remove('active');
  dots[curSlide]?.classList.remove('act');
  curSlide = (n + slides.length) % slides.length;
  slides[curSlide].classList.add('active');
  dots[curSlide]?.classList.add('act');
  document.getElementById('slideNum').textContent = String(curSlide+1).padStart(2,'0');
  // Update text
  const s = slides[curSlide];
  const city    = s.dataset.city || '';
  const tagline = s.dataset.tagline || '';
  if (window.anime) {
    anime({ targets: ['#hCity','#hTitle','#hTagline'], opacity:[0,1], translateY:[12,0], duration:700, delay:anime.stagger(100), easing:'easeOutExpo' });
  }
  document.getElementById('hCity').textContent = city;
  document.getElementById('hTitle').innerHTML = `<span>Where <em>${tagline}</em></span>`;
}

function changeSlide(dir) { goSlide(curSlide + dir); resetTimer(); }
function resetTimer() { clearInterval(slideTimer); slideTimer = setInterval(()=>changeSlide(1), 7000); }
resetTimer();

// ── HERO ENTRANCE ANIMATION ──────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const tl = [
    { targets:'#hCity',    opacity:[0,1], translateX:[-20,0], duration:700, delay:300 },
    { targets:'#hTitle',   opacity:[0,1], translateY:[40,0],  duration:900, delay:500 },
    { targets:'#hTagline', opacity:[0,1], translateY:[20,0],  duration:700, delay:800 },
    { targets:'#hBtns',    opacity:[0,1], translateY:[15,0],  duration:600, delay:1000 },
  ];
  if (window.anime) tl.forEach(t => anime(t));
  else tl.forEach(t => { const el = document.querySelector(t.targets); if(el){el.style.opacity='1';el.style.transform='none';} });
  // Animate search dock
  if (window.anime) anime({ targets:'#searchDockInner .sd-field, #searchDockInner .sd-btn', opacity:[0,1], translateY:[20,0], duration:500, delay:anime.stagger(80,{start:1200}), easing:'easeOutExpo' });
});

// ── PROPERTIES FILTER ────────────────────────────────────────
function filterProps(cont, btn) {
  document.querySelectorAll('.prop-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  const cards = document.querySelectorAll('.prop-card');
  let shown = 0;
  cards.forEach(c => {
    const match = cont === 'all' || c.dataset.continent === cont;
    c.classList.toggle('visible', match);
    if (match) shown++;
  });
  if (window.anime) {
    const visible = document.querySelectorAll('.prop-card.visible');
    anime({ targets: [...visible], opacity:[0,1], translateY:[20,0], scale:[.97,1], duration:450, delay:anime.stagger(50), easing:'easeOutExpo' });
  }
}

// ── AVAILABILITY CHECK ───────────────────────────────────────
async function handleSearch(e) {
  e.preventDefault();
  const ci    = document.getElementById('sdCI').value;
  const co    = document.getElementById('sdCO').value;
  const guests= document.getElementById('sdGuests').value;
  const type  = document.getElementById('sdType').value;

  if (!ci || !co || ci >= co) { window.toast?.('Please select valid check-in and check-out dates','error'); return false; }

  document.getElementById('sdBtn').style.display = 'none';
  document.getElementById('sdChecking').style.display = 'flex';

  const rooms = type ? [{id: parseInt(type), qty: 1}] : ROOM_TYPES.map(r => ({id: r.id, qty: 1}));
  const fd = new FormData();
  fd.append('action','availability');
  fd.append('checkin', ci);
  fd.append('checkout', co);
  fd.append('rooms', JSON.stringify(rooms));

  try {
    const res  = await fetch(B + '/api/book.php', {method:'POST', body:fd});
    const data = await res.json();
    document.getElementById('sdBtn').style.display = '';
    document.getElementById('sdChecking').style.display = 'none';
    showAvailModal(data, ci, co, guests);
  } catch(e2) {
    document.getElementById('sdBtn').style.display = '';
    document.getElementById('sdChecking').style.display = 'none';
    window.toast?.('Connection error. Please try again.','error');
  }
  return false;
}

function showAvailModal(data, ci, co, guests) {
  const box = document.getElementById('availBox');
  const modal = document.getElementById('availModal');
  const nights = data.nights || 1;
  const anyAvail = (data.rooms && data.rooms.length > 0) ? data.rooms.some(r => r.available) : false;

  let roomsHtml = '';
  (data.rooms||[]).forEach(r => {
    let roomNumsHtml = r.avail_numbers?.length ? `<div style="font-size:11px;color:rgba(255,255,255,.4);margin-top:2px;margin-left:18px">Room No: ${r.avail_numbers.join(', ')}</div>` : '';
    roomsHtml += `
      <div class="avail-row" style="align-items:flex-start">
        <div class="lbl">
          <div><i class="fas fa-${r.available?'check':'times'}" style="color:${r.available?'var(--green)':'var(--red)'};margin-right:6px"></i>${r.name}</div>
          ${roomNumsHtml}
        </div>
        <div class="val" style="color:var(--gold);line-height:1.4">${r.total_fmt}</div>
      </div>`;
  });

  box.innerHTML = `
    <div class="avail-box-hd">
      <button onclick="closeAvail()" style="position:absolute;top:14px;right:16px;background:rgba(255,255,255,.1);border:none;color:#fff;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center">×</button>
      <div class="avail-check-icon" style="background:${anyAvail?'#22c55e':'var(--red)'}">
        <i class="fas fa-${anyAvail?'check':'times'}" style="color:#fff;font-size:24px"></i>
      </div>
      <h3>${anyAvail ? '🎉 Rooms Available!' : 'Unavailable'}</h3>
      <p>${anyAvail ? `${data.rooms?.filter(r=>r.available).length||1} room type(s) available for <strong style="color:#fff">${nights} night${nights!==1?'s':''}</strong>` : 'No rooms available for the selected dates. Please try alternative dates.'}</p>
    </div>
    <div class="avail-box-bd">
      <div class="avail-row"><span class="lbl">Check-In</span><span class="val">${new Date(ci+'T00:00').toLocaleDateString('en-GB',{weekday:'short',day:'numeric',month:'short',year:'numeric'})}</span></div>
      <div class="avail-row"><span class="lbl">Check-Out</span><span class="val">${new Date(co+'T00:00').toLocaleDateString('en-GB',{weekday:'short',day:'numeric',month:'short',year:'numeric'})}</span></div>
      <div class="avail-row"><span class="lbl">Duration</span><span class="val">${nights} Night${nights!==1?'s':''}</span></div>
      <div class="avail-row"><span class="lbl">Guests</span><span class="val">${guests} Guest${guests>1?'s':''}</span></div>
      ${roomsHtml}
      ${anyAvail?`<div class="avail-row total"><span class="lbl" style="font-weight:700;color:var(--text)">Estimated Total</span><span class="val">${data.final_fmt}</span></div>`:''}
      <div class="avail-actions">
        ${anyAvail
          ? `<button class="avail-book-btn" onclick="bookNow('${ci}','${co}','${guests}')"><i class="fas fa-lock" style="font-size:12px"></i> Book Now</button>`
          : `<button class="avail-book-btn" onclick="window.location='${B}/rooms.php'" style="background:var(--charcoal)"><i class="fas fa-search" style="font-size:12px"></i> View All Rooms</button>`}
        <button class="avail-close-btn" onclick="closeAvail()"><i class="fas fa-times"></i> Close</button>
      </div>
    </div>`;

  modal.classList.add('open');
  if (window.anime) {
    anime({ targets: '#availBox', scale:[.9,1], opacity:[0,1], duration:450, easing:'easeOutBack' });
    anime({ targets: '#availBox .avail-check-icon', scale:[0,1.2,1], duration:600, delay:200, easing:'easeOutElastic(1,.5)' });
  }
}

function closeAvail() {
  if (window.anime) {
    anime({ targets:'#availBox', scale:[1,.95], opacity:[1,0], duration:250, easing:'easeInExpo', complete(){ document.getElementById('availModal').classList.remove('open'); } });
  } else {
    document.getElementById('availModal').classList.remove('open');
  }
}

function bookNow(ci, co, guests) {
  closeAvail();
  window.location = B + '/rooms.php?checkin=' + ci + '&checkout=' + co + '&guests=' + guests;
}
</script>

<?php require __DIR__ . '/footer.php'; ?>

