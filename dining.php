<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/service_requests.php';
ensureServiceRequestSchema($pdo);
$pageTitle = 'Fine Dining — Royale Vista';

$diningOk = false;
$diningErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_dining'])) {
  $venue = clean($_POST['venue_name'] ?? '');
  $date = clean($_POST['date'] ?? '');
  $time = clean($_POST['time'] ?? '');
  $guests = max(1, (int)($_POST['guests'] ?? 2));
  $occasion = clean($_POST['occasion'] ?? '');
  $requests = clean($_POST['requests'] ?? '');
  $name = clean($_POST['name'] ?? '');
  $email = clean($_POST['email'] ?? '');
  $phone = clean($_POST['phone'] ?? '');

  if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$date || !$time) {
    $diningErr = 'Please fill name, valid email, date and time.';
  } else {
    $ref = 'DR' . date('Y') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    $pdo->prepare("INSERT INTO dining_reservations (ref,user_id,venue_name,date,time,guests,occasion,requests,name,email,phone,status,admin_status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
      ->execute([$ref, $_SESSION['user_id'] ?? null, $venue ?: null, $date, $time, $guests, $occasion ?: null, $requests ?: null, $name, $email, $phone ?: null, 'confirmed', 'pending']);
    pushUserNotification(
      $pdo,
      $_SESSION['user_id'] ?? null,
      'booking',
      "Dining request received — {$ref}",
      "Your table request is submitted and waiting for approval.",
      BASE . '/notifications.php'
    );
    $diningOk = true;
  }
}
require __DIR__ . '/header.php';
?>

<style>
.dining-page { padding-top: 88px; }
.dining-hero {
  position: relative; height: 75vh; min-height: 560px;
  display: flex; align-items: center; overflow: hidden;
}
.dining-hero-bg {
  position: absolute; inset: 0;
  background: url('https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1800&q=80') center/cover;
}
.dining-hero-bg::after {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(to right, rgba(0,0,0,.8) 50%, rgba(0,0,0,.35));
}
.dining-hero-content { position: relative; z-index: 1; color: #fff; }

.menu-category { margin-bottom: 48px; }
.menu-cat-title { font-family: var(--cinzel); font-size: 10px; letter-spacing: 3px; text-transform: uppercase; color: var(--gold); margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid var(--bdr2); display: flex; align-items: center; gap: 12px; }
.menu-cat-title::after { content: ''; flex: 1; height: 1px; background: var(--bdr2); }
.menu-items { display: flex; flex-direction: column; gap: 0; }
.menu-item { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; padding: 14px 0; border-bottom: 1px solid var(--bdr2); }
.menu-item:last-child { border-bottom: none; }
.menu-item-left { flex: 1; }
.menu-item-name { font-size: 15px; font-weight: 600; margin-bottom: 3px; }
.menu-item-desc { font-size: 12px; color: var(--text2); line-height: 1.5; }
.menu-item-price { font-family: var(--serif); font-size: 16px; color: var(--gold); white-space: nowrap; flex-shrink: 0; }
.menu-tag { display: inline-block; padding: 1px 7px; border-radius: 10px; font-size: 9px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-left: 8px; }
.tag-v { background: rgba(34,197,94,.15); color: #22c55e; }
.tag-s { background: rgba(239,68,68,.15); color: #ef4444; }

.restaurant-card {
  background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-lg);
  overflow: hidden; position: relative; transition: transform .3s, box-shadow .3s;
}
.restaurant-card:hover { transform: translateY(-4px); box-shadow: var(--shadow2); }
.restaurant-card-img { height: 220px; width: 100%; object-fit: cover; display: block; }
.restaurant-card-body { padding: 24px; }
.restaurant-card-tag { font-family: var(--cinzel); font-size: 9px; letter-spacing: 2px; color: var(--gold); text-transform: uppercase; margin-bottom: 8px; }
.restaurant-card-name { font-family: var(--serif); font-size: 22px; margin-bottom: 10px; }
.restaurant-card-desc { font-size: 13px; color: var(--text2); line-height: 1.7; }
.restaurant-card-hours { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--muted); margin-top: 14px; }
</style>

<div class="dining-page">

  <!-- Hero -->
  <section class="dining-hero">
    <div class="dining-hero-bg"></div>
    <div class="container dining-hero-content">
      <div class="lx-eyebrow" style="color:rgba(255,255,255,.5);justify-content:flex-start">Culinary Excellence</div>
      <h1 class="lx-heading" style="color:#fff;max-width:660px;font-size:clamp(36px,5vw,68px)">
        Fine Dining<br><em>Redefined</em>
      </h1>
      <p style="color:rgba(255,255,255,.72);font-size:16px;max-width:520px;line-height:1.8;margin:20px 0 32px">
        Four distinct venues. One Michelin star. An unwavering commitment to seasonal, locally sourced ingredients and stories told through flavour.
      </p>
      <div style="display:flex;gap:12px;flex-wrap:wrap">
        <a href="#menu" class="btn btn-gold btn-lg" onclick="document.getElementById('menu').scrollIntoView({behavior:'smooth'});return false">View Menu</a>
        <a href="<?= $B ?>/contact.php?table=1" class="btn" style="border:1px solid rgba(255,255,255,.35);color:#fff;background:transparent;padding:15px 36px">Reserve a Table</a>
      </div>
    </div>
  </section>

  <!-- Restaurants Grid -->
  <section class="section">
    <div class="container">
      <div style="text-align:center;margin-bottom:52px">
        <div class="lx-eyebrow lx-reveal" style="justify-content:center">Our Venues</div>
        <h2 class="lx-heading lx-reveal" style="text-align:center">Four <em>Unique Experiences</em></h2>
      </div>
      <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:24px" class="lx-reveal">
        <?php foreach ([
          ['Al Qibla', 'Arabic Fine Dining', 'Authentic Emirati and Levantine cuisine reimagined with modern techniques. Dine under a celestial canopy of a thousand lanterns.', 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=600&q=80', '7PM – 12AM'],
          ['Horizon 52', 'Rooftop Steakhouse', 'World-class dry-aged cuts, premium seafood and panoramic 52nd-floor views of the city skyline and sea.', 'https://images.unsplash.com/photo-1544025162-d76694265947?w=600&q=80', '6PM – 11PM'],
          ['The Brasserie', 'All-Day Dining', 'A vibrant hub serving breakfast, afternoon tea and dinner. Fresh, seasonal menus inspired by the Mediterranean.', 'https://images.unsplash.com/photo-1424847651672-bf20a4b0982b?w=600&q=80', '7AM – 11PM'],
          ['Cigar & Cognac Lounge', 'Premium Lounge', 'An intimate bar with five hundred whiskeys, exclusive cognac and a curated premium cigar selection. Jacket preferred.', 'https://images.unsplash.com/photo-1470337458703-46ad1756a187?w=600&q=80', '5PM – 2AM'],
        ] as [$name, $tag, $desc, $img, $hours]): ?>
        <div class="restaurant-card">
          <img class="restaurant-card-img" src="<?= $img ?>" alt="<?= $name ?>" loading="lazy">
          <div class="restaurant-card-body">
            <div class="restaurant-card-tag"><?= $tag ?></div>
            <div class="restaurant-card-name"><?= $name ?></div>
            <div class="restaurant-card-desc"><?= $desc ?></div>
            <div class="restaurant-card-hours"><i class="fas fa-clock" style="color:var(--gold)"></i> <?= $hours ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Menu -->
  <section class="section" id="menu" style="background:var(--card)">
    <div class="container" style="max-width:860px">
      <div style="text-align:center;margin-bottom:52px">
        <div class="lx-eyebrow lx-reveal" style="justify-content:center">Al Qibla — Tonight's Menu</div>
        <h2 class="lx-heading lx-reveal" style="text-align:center">Signature <em>Selection</em></h2>
      </div>

      <div class="menu-category">
        <div class="menu-cat-title">Starters</div>
        <div class="menu-items">
          <?php foreach ([
            ['Burrata & Truffle', 'Creamy burrata, 24-hour truffle oil, toasted pine nuts, heritage tomatoes', '$38', 'v'],
            ['Arabian Sea Octopus', 'Char-grilled, lemon confit, paprika aioli, micro greens', '$52', 's'],
            ['Wagyu Beef Carpaccio', '40-day dry-aged Wagyu A5, Grana Padano, capers, rocket', '$64', ''],
          ] as [$name, $desc, $price, $tag]): ?>
          <div class="menu-item">
            <div class="menu-item-left">
              <div class="menu-item-name"><?= $name ?><?= $tag === 'v' ? '<span class="menu-tag tag-v">V</span>' : ($tag === 's' ? '<span class="menu-tag tag-s">Signature</span>' : '') ?></div>
              <div class="menu-item-desc"><?= $desc ?></div>
            </div>
            <div class="menu-item-price"><?= $price ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="menu-category">
        <div class="menu-cat-title">Main Courses</div>
        <div class="menu-items">
          <?php foreach ([
            ['Whole Roasted Lamb Shoulder', '48-hour slow-roasted, pomegranate jus, saffron rice, charred flatbread', '$128', 's'],
            ['Catch of the Day', 'Market-fresh whole fish, chermoula, preserved lemon, harissa', '$96', ''],
            ['Mushroom & Black Truffle Risotto', 'Arborio rice, mixed wild mushrooms, truffle shavings, Parmesan foam', '$72', 'v'],
            ['Tomahawk Ribeye (1.2kg)', 'Premium 40-day aged, bone-in, served with three sauces & sides', '$220', 's'],
          ] as [$name, $desc, $price, $tag]): ?>
          <div class="menu-item">
            <div class="menu-item-left">
              <div class="menu-item-name"><?= $name ?><?= $tag === 'v' ? '<span class="menu-tag tag-v">V</span>' : ($tag === 's' ? '<span class="menu-tag tag-s">Signature</span>' : '') ?></div>
              <div class="menu-item-desc"><?= $desc ?></div>
            </div>
            <div class="menu-item-price"><?= $price ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="menu-category">
        <div class="menu-cat-title">Desserts</div>
        <div class="menu-items">
          <?php foreach ([
            ['Umm Ali', 'Traditional Egyptian bread pudding, rose water, pistachios, cream', '$28', ''],
            ['Chocolate Sphere', 'Valrhona 70% sphere, caramel centre, vanilla ice cream, gold leaf', '$42', 's'],
            ['Luqaimat', 'Crispy honey dumplings, date syrup, black sesame dipping sauce', '$22', 'v'],
          ] as [$name, $desc, $price, $tag]): ?>
          <div class="menu-item">
            <div class="menu-item-left">
              <div class="menu-item-name"><?= $name ?><?= $tag === 'v' ? '<span class="menu-tag tag-v">V</span>' : ($tag === 's' ? '<span class="menu-tag tag-s">Signature</span>' : '') ?></div>
              <div class="menu-item-desc"><?= $desc ?></div>
            </div>
            <div class="menu-item-price"><?= $price ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div style="margin-top:40px;padding:28px;background:var(--gold-dim);border:1px solid var(--bdr2);border-radius:12px">
        <div style="font-family:var(--serif);font-size:26px;margin-bottom:8px;text-align:center">Reserve Your Table</div>
        <p style="color:var(--text2);font-size:13px;margin-bottom:18px;text-align:center">Available daily from 7PM. Private dining rooms available for parties of 8+.</p>
        <?php if ($diningOk): ?>
          <div class="alert alert-success"><i class="fas fa-check-circle"></i> Dining request submitted. Admin approval will be shared in notifications.</div>
        <?php else: ?>
          <?php if ($diningErr): ?><div class="alert alert-error"><?= htmlspecialchars($diningErr) ?></div><?php endif; ?>
          <form method="POST">
            <input type="hidden" name="submit_dining" value="1">
            <div class="form-row">
              <div class="form-group"><label class="form-label">Venue</label>
                <select class="form-control" name="venue_name">
                  <option>Al Qibla</option><option>Horizon 52</option><option>The Brasserie</option><option>Cigar & Cognac Lounge</option>
                </select>
              </div>
              <div class="form-group"><label class="form-label">Guests</label>
                <input class="form-control" type="number" min="1" max="20" name="guests" value="2">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group"><label class="form-label">Date *</label><input class="form-control" type="date" name="date" min="<?= date('Y-m-d') ?>" required></div>
              <div class="form-group"><label class="form-label">Time *</label><input class="form-control" type="time" name="time" required></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label class="form-label">Name *</label><input class="form-control" name="name" required></div>
              <div class="form-group"><label class="form-label">Email *</label><input class="form-control" type="email" name="email" required></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label class="form-label">Phone</label><input class="form-control" name="phone"></div>
              <div class="form-group"><label class="form-label">Occasion</label><input class="form-control" name="occasion" placeholder="Birthday, Anniversary..."></div>
            </div>
            <div class="form-group"><label class="form-label">Special Requests</label><textarea class="form-control" name="requests" rows="3"></textarea></div>
            <button class="btn btn-gold btn-block" type="submit"><i class="fas fa-utensils"></i> Submit Dining Request</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </section>

</div>

<?php require __DIR__ . '/footer.php'; ?>