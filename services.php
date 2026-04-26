<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Services — Royale Vista';
require __DIR__ . '/header.php';
try {
  $pdo->exec("CREATE TABLE IF NOT EXISTS services_catalog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    icon VARCHAR(20) DEFAULT NULL,
    category VARCHAR(100) NOT NULL,
    name VARCHAR(200) NOT NULL,
    image_url VARCHAR(600) DEFAULT NULL,
    description TEXT,
    hours VARCHAR(120) DEFAULT NULL,
    cta_link VARCHAR(300) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}
$services = [];
try {
  $services = $pdo->query("SELECT * FROM services_catalog WHERE is_active=1 ORDER BY sort_order,id")->fetchAll();
} catch (Exception $e) {}
if (empty($services)) {
  $services=[['icon'=>'🍽','category'=>'Fine Dining','name'=>'The Royale Table','image_url'=>'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=700&q=70','description'=>'Michelin-starred restaurant featuring seasonal tasting menus. An extraordinary culinary journey.','hours'=>'Open 7AM–11PM','cta_link'=>$B.'/contact.php'],['icon'=>'🧖','category'=>'Spa & Wellness','name'=>'Aria Spa','image_url'=>'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=700&q=70','description'=>'Ancient healing traditions meet modern techniques across 18 treatment rooms.','hours'=>'Daily 8AM–10PM','cta_link'=>$B.'/spa.php'],['icon'=>'🏊','category'=>'Infinity Pool','name'=>'Rooftop Oasis','image_url'=>'https://images.unsplash.com/photo-1613977257363-707ba9348227?w=700&q=70','description'=>'Breathtaking rooftop infinity pool with panoramic city views.','hours'=>'Daily 7AM–11PM','cta_link'=>$B.'/contact.php'],['icon'=>'💪','category'=>'Fitness','name'=>'The Athletic Club','image_url'=>'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=700&q=70','description'=>'State-of-the-art equipment, personal training, yoga and pilates studios.','hours'=>'24 Hours','cta_link'=>$B.'/contact.php'],['icon'=>'🥂','category'=>'Cocktail Bar','name'=>'The Gold Lounge','image_url'=>'https://images.unsplash.com/photo-1572116469696-31de0f17cc34?w=700&q=70','description'=>'200+ premium spirits and bespoke cocktails by award-winning mixologists.','hours'=>'Daily 4PM–2AM','cta_link'=>$B.'/contact.php'],['icon'=>'🎭','category'=>'Events','name'=>'Grand Occasions','image_url'=>'https://images.unsplash.com/photo-1519225421980-715cb0215aed?w=700&q=70','description'=>'Seven versatile spaces from boardrooms to ballrooms for up to 800 guests.','hours'=>'By Appointment','cta_link'=>$B.'/events.php']];
}
?>
<style>.svc-card{background:var(--card);border:1px solid var(--bdr2);border-radius:var(--radius-lg);overflow:hidden;transition:all var(--t)}.svc-card:hover{transform:translateY(-5px);box-shadow:var(--shadow);border-color:var(--border)}.svc-img{height:200px;overflow:hidden;background:var(--card2)}.svc-img img{width:100%;height:100%;object-fit:cover;transition:transform .5s var(--ease)}.svc-card:hover .svc-img img{transform:scale(1.06)}</style>
<div style="padding-top:80px">
  <div style="background:linear-gradient(135deg,var(--bg2),var(--bg));padding:60px 0 40px;border-bottom:1px solid var(--bdr2)"><div class="container">
    <div class="section-label">World-Class Amenities</div>
    <h1 style="font-family:var(--serif);font-size:clamp(28px,4vw,48px);font-weight:400;margin-bottom:10px">Our Services</h1>
    <p style="color:var(--text2);font-size:15px;max-width:560px;line-height:1.75">Every detail curated for your comfort. An unrivalled collection of experiences awaits.</p>
  </div></div>
  <div class="container section">
    <div class="grid-3">
      <?php foreach($services as $i=>$s):
        $icon = $s['icon'] ?? '✨'; $cat = $s['category'] ?? ''; $name = $s['name'] ?? '';
        $img = $s['image_url'] ?? ''; $desc = $s['description'] ?? ''; $hrs = $s['hours'] ?? '';
        $cta = $s['cta_link'] ?? ($B.'/contact.php');
      ?>
      <div class="svc-card" style="transition-delay:<?= ($i%3)*0.1 ?>s">
        <div class="svc-img"><img src="<?= htmlspecialchars($img) ?>" alt="<?= $name ?>" loading="lazy" onerror="this.style.display='none'"></div>
        <div style="padding:22px">
          <div style="font-size:10px;color:var(--gold);letter-spacing:2px;text-transform:uppercase;margin-bottom:4px"><?= $cat ?></div>
          <h3 style="font-family:var(--serif);font-size:20px;font-weight:400;margin-bottom:8px"><?= $icon ?> <?= $name ?></h3>
          <p style="font-size:13px;color:var(--text2);line-height:1.7;margin-bottom:12px"><?= $desc ?></p>
          <div style="font-size:12px;color:var(--muted);margin-bottom:16px"><i class="fas fa-clock" style="color:var(--gold)"></i> <?= $hrs ?></div>
          <a href="<?= htmlspecialchars($cta) ?>" class="btn btn-outline btn-sm">Enquire</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="background:linear-gradient(135deg,var(--gold-dk),var(--gold));border-radius:var(--radius-lg);padding:52px;text-align:center;margin-top:60px;color:#000">
      <h2 style="font-family:var(--serif);font-size:32px;font-weight:400;margin-bottom:12px">🎩 Your Personal Concierge Awaits</h2>
      <p style="font-size:15px;opacity:.8;max-width:480px;margin:0 auto 24px;line-height:1.7">From private tours to exclusive reservations — we make the impossible happen.</p>
      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
        <a href="<?= $B ?>/contact.php" class="btn" style="background:rgba(0,0,0,.2);color:#000;border:1px solid rgba(0,0,0,.25);font-weight:600">Contact Us</a>
        <button onclick="Habibi.toggle()" class="btn" style="background:rgba(0,0,0,.15);color:#000;border:1px solid rgba(0,0,0,.2)">💬 Chat with Habibi</button>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
