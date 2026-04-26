<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/service_requests.php';
ensureServiceRequestSchema($pdo);
$pageTitle = 'Spa & Wellness — Royale Vista';

$spaOk = false;
$spaErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_spa'])) {
  $treatment = clean($_POST['treatment'] ?? '');
  $date = clean($_POST['date'] ?? '');
  $time = clean($_POST['time'] ?? '');
  $guests = max(1, (int)($_POST['guests'] ?? 1));
  $duration = max(30, (int)($_POST['duration_min'] ?? 60));
  $name = clean($_POST['name'] ?? '');
  $email = clean($_POST['email'] ?? '');
  $phone = clean($_POST['phone'] ?? '');
  $requests = clean($_POST['requests'] ?? '');

  if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$treatment || !$date || !$time) {
    $spaErr = 'Please fill required details correctly.';
  } else {
    $ref = 'SP' . date('Y') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    $pdo->prepare("INSERT INTO spa_appointments (ref,user_id,treatment,date,time,duration_min,guests,name,email,phone,requests,status,admin_status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
      ->execute([$ref, $_SESSION['user_id'] ?? null, $treatment, $date, $time, $duration, $guests, $name, $email, $phone ?: null, $requests ?: null, 'confirmed', 'pending']);
    pushUserNotification(
      $pdo,
      $_SESSION['user_id'] ?? null,
      'booking',
      "Spa request received — {$ref}",
      "Your spa request is submitted and waiting for approval.",
      BASE . '/notifications.php'
    );
    $spaOk = true;
  }
}
require __DIR__ . '/header.php';
?>

<style>
.spa-page { padding-top: 88px; }
.spa-hero {
  position: relative; height: 75vh; min-height: 560px;
  display: flex; align-items: center; overflow: hidden;
}
.spa-hero-bg {
  position: absolute; inset: 0;
  background: url('https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=1800&q=80') center/cover;
}
.spa-hero-bg::after {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(135deg, rgba(0,0,0,.8) 40%, rgba(0,0,0,.35));
}
.spa-hero-content { position: relative; z-index: 1; color: #fff; }

.spa-feature-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 1px; background: var(--bdr2); }
@media(max-width:900px){.spa-feature-grid{grid-template-columns:1fr;}}
.spa-feature-item { background: var(--bg); padding: 48px 36px; text-align: center; transition: background .3s; }
.spa-feature-item:hover { background: var(--card); }
.spa-feature-icon { font-size: 40px; margin-bottom: 20px; }
.spa-feature-title { font-family: var(--serif); font-size: 22px; margin-bottom: 12px; }
.spa-feature-desc { font-size: 14px; color: var(--text2); line-height: 1.8; }

.treatment-card {
  background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-lg);
  overflow: hidden; display: flex; transition: transform .3s, box-shadow .3s;
}
.treatment-card:hover { transform: translateY(-3px); box-shadow: var(--shadow2); }
.treatment-img { width: 200px; flex-shrink: 0; object-fit: cover; }
@media(max-width:700px){ .treatment-card { flex-direction: column; } .treatment-img { width: 100%; height: 180px; } }
.treatment-body { padding: 24px 28px; display: flex; flex-direction: column; }
.treatment-tag { font-family: var(--cinzel); font-size: 9px; letter-spacing: 2px; color: var(--gold); text-transform: uppercase; margin-bottom: 8px; }
.treatment-name { font-family: var(--serif); font-size: 22px; margin-bottom: 10px; }
.treatment-desc { font-size: 14px; color: var(--text2); line-height: 1.75; flex: 1; }
.treatment-footer { display: flex; align-items: center; justify-content: space-between; margin-top: 18px; flex-wrap: wrap; gap: 10px; }
.treatment-price { font-family: var(--serif); font-size: 22px; color: var(--gold); }
.treatment-duration { font-size: 12px; color: var(--muted); background: var(--card2); padding: 4px 10px; border-radius: 16px; }
</style>

<div class="spa-page">

  <!-- Hero -->
  <section class="spa-hero">
    <div class="spa-hero-bg"></div>
    <div class="container spa-hero-content">
      <div class="lx-eyebrow" style="color:rgba(255,255,255,.5);justify-content:flex-start">Wellbeing & Serenity</div>
      <h1 class="lx-heading" style="color:#fff;max-width:700px;font-size:clamp(36px,5vw,72px)">
        Aria<br><em>Spa & Wellness</em>
      </h1>
      <p style="color:rgba(255,255,255,.72);font-size:16px;max-width:560px;line-height:1.8;margin:20px 0 32px">
        A sanctuary of calm across three floors. Eighteen treatment rooms, hydrotherapy pools, 
        a hammam, and specialist practitioners merging ancient healing with modern science.
      </p>
      <div style="display:flex;gap:12px;flex-wrap:wrap">
        <a href="#treatments" class="btn btn-gold btn-lg" onclick="document.getElementById('treatments').scrollIntoView({behavior:'smooth'});return false">Explore Treatments</a>
        <a href="<?= $B ?>/contact.php" class="btn" style="border:1px solid rgba(255,255,255,.35);color:#fff;background:transparent;padding:15px 36px">Book a Session</a>
      </div>
    </div>
  </section>

  <!-- Features -->
  <div class="spa-feature-grid">
    <?php foreach ([
      ['🧖', 'Signature Treatments', 'Exclusive therapies curated by our master practitioners using rare botanicals and ancient healing traditions.'],
      ['💧', 'Hydrotherapy', 'Vitality pools, ice fountains, Roman baths and thermal chambers for complete cellular renewal.'],
      ['🌿', 'Organic Rituals', 'Certified organic products, free from harsh chemicals. Pure ingredients, extraordinary results.'],
      ['🧘', 'Yoga & Pilates', 'Daily classes from sunrise to evening in our sky-lit studio overlooking the city skyline.'],
      ['💆', 'Personal Wellness Plans', 'Our experts craft bespoke multi-day programmes for deep transformation and lasting wellbeing.'],
      ['🌅', 'Rooftop Meditation', 'Dawn meditation sessions on our exclusive sky terrace — a transcendent start to your day.'],
    ] as [$icon, $title, $desc]): ?>
    <div class="spa-feature-item lx-reveal">
      <div class="spa-feature-icon"><?= $icon ?></div>
      <div class="spa-feature-title"><?= $title ?></div>
      <div class="spa-feature-desc"><?= $desc ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Treatments -->
  <section class="section" id="treatments">
    <div class="container">
      <div style="text-align:center;margin-bottom:56px">
        <div class="lx-eyebrow lx-reveal" style="justify-content:center">Signature Experiences</div>
        <h2 class="lx-heading lx-reveal" style="text-align:center">Our <em>Signature Treatments</em></h2>
      </div>
      <div style="display:flex;flex-direction:column;gap:24px">
        <?php foreach ([
          ['Arabian Nights Ritual', 'Body', 'A deeply immersive 3-hour journey inspired by ancient Arabian bathing traditions. Begins with a dark rose hammam, followed by organic argan oil body wrap and finishing with a Moroccan rose petal bath.', 'https://images.unsplash.com/photo-1600334089648-b0d9d20cf2f3?w=400&q=80', '$380', '180 min'],
          ['Gold Leaf Facial', 'Face', '24-karat gold leaf infusion combined with hyaluronic peptides and a precision lifting massage. Leaves skin luminous for up to 30 days.', 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=400&q=80', '$220', '90 min'],
          ['Himalayan Stone Therapy', 'Body', 'Warm pink Himalayan salt stones paired with deep-tissue massage oil to release tension, ground the spirit and restore mineral balance to skin.', 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=400&q=80', '$180', '75 min'],
          ['Couples Harmony Journey', 'Couples', 'Begin with a private champagne ritual, move through partner yoga, synchronised massages and conclude with a candlelit soak in your private hydrotherapy suite.', 'https://images.unsplash.com/photo-1515377905703-c4788e51af15?w=400&q=80', '$580', '3 hrs'],
        ] as [$name, $tag, $desc, $img, $price, $dur]): ?>
        <div class="treatment-card lx-reveal">
          <img class="treatment-img" src="<?= $img ?>" alt="<?= $name ?>" loading="lazy">
          <div class="treatment-body">
            <div class="treatment-tag"><?= $tag ?></div>
            <div class="treatment-name"><?= $name ?></div>
            <div class="treatment-desc"><?= $desc ?></div>
            <div class="treatment-footer">
              <div class="treatment-price"><?= $price ?></div>
              <span class="treatment-duration">⏱ <?= $dur ?></span>
              <a href="<?= $B ?>/contact.php?spa=<?= urlencode($name) ?>" class="btn btn-gold" style="padding:10px 22px;font-size:12px">Book This</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="section" style="background:var(--card)">
    <div class="container" style="max-width:860px">
      <div style="text-align:center;margin-bottom:28px">
        <div class="lx-eyebrow" style="justify-content:center">Book Wellness Session</div>
        <h2 class="lx-heading" style="text-align:center">Request a <em>Spa Appointment</em></h2>
      </div>
      <?php if ($spaOk): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Spa request submitted. Admin approval will be sent in notifications.</div>
      <?php else: ?>
        <?php if ($spaErr): ?><div class="alert alert-error"><?= htmlspecialchars($spaErr) ?></div><?php endif; ?>
        <form method="POST">
          <input type="hidden" name="submit_spa" value="1">
          <div class="form-row">
            <div class="form-group"><label class="form-label">Treatment *</label><input class="form-control" name="treatment" required placeholder="Gold Leaf Facial"></div>
            <div class="form-group"><label class="form-label">Guests</label><input class="form-control" type="number" min="1" max="4" name="guests" value="1"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Date *</label><input class="form-control" type="date" min="<?= date('Y-m-d') ?>" name="date" required></div>
            <div class="form-group"><label class="form-label">Time *</label><input class="form-control" type="time" name="time" required></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Duration (min)</label><input class="form-control" type="number" name="duration_min" min="30" step="15" value="60"></div>
            <div class="form-group"><label class="form-label">Phone</label><input class="form-control" name="phone"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Name *</label><input class="form-control" name="name" required></div>
            <div class="form-group"><label class="form-label">Email *</label><input class="form-control" type="email" name="email" required></div>
          </div>
          <div class="form-group"><label class="form-label">Special Requests</label><textarea class="form-control" name="requests" rows="3"></textarea></div>
          <button type="submit" class="btn btn-gold btn-block"><i class="fas fa-spa"></i> Submit Spa Request</button>
        </form>
      <?php endif; ?>
    </div>
  </section>

  <!-- Info Strip -->
  <div class="section-dark" style="padding:48px 0">
    <div class="container">
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:2px;background:rgba(255,255,255,.04);border-radius:16px;overflow:hidden">
        <?php foreach ([['8AM – 10PM','Daily Hours'],['18','Treatment Rooms'],['100%','Organic Products'],['Level 3','Sky Terrace Studio']] as [$v,$l]): ?>
        <div style="padding:32px;text-align:center;background:rgba(255,255,255,.02)">
          <div style="font-family:var(--serif);font-size:36px;color:var(--gold);font-weight:300;margin-bottom:6px"><?= $v ?></div>
          <div style="font-family:var(--cinzel);font-size:9px;letter-spacing:2px;color:rgba(255,255,255,.4);text-transform:uppercase"><?= $l ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>

<?php require __DIR__ . '/footer.php'; ?>