<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/service_requests.php';
$pageTitle = 'Events & Celebrations — Royale Vista';

$events = $pdo->query("SELECT * FROM events WHERE is_active=1 ORDER BY sort_order")->fetchAll();

// Handle enquiry submission
$success = false; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_enquiry'])) {
    $eventId   = (int)($_POST['event_id'] ?? 0);
    $name      = clean($_POST['name'] ?? '');
    $email     = clean($_POST['email'] ?? '');
    $phone     = clean($_POST['phone'] ?? '');
    $eventDate = clean($_POST['event_date'] ?? '');
    $guests    = (int)($_POST['guests'] ?? 50);
    $budget    = (float)($_POST['budget'] ?? 0);
    $message   = clean($_POST['message'] ?? '');

    if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter your name and a valid email.';
    } else {
        $ref = 'EV' . date('Y') . strtoupper(substr(bin2hex(random_bytes(3)),0,5));
        $pdo->prepare("INSERT INTO event_bookings (ref,event_id,user_id,name,email,phone,event_date,guests,budget_usd,message) VALUES (?,?,?,?,?,?,?,?,?,?)")
            ->execute([$ref, $eventId ?: null, $_SESSION['user_id'] ?? null, $name, $email, $phone, $eventDate ?: null, $guests, $budget ?: null, $message]);
        pushUserNotification(
          $pdo,
          $_SESSION['user_id'] ?? null,
          'booking',
          "Event request received — {$ref}",
          "Your event request is submitted and waiting for admin review.",
          BASE . '/notifications.php'
        );
        $success = true;
    }
}

require __DIR__ . '/header.php';
?>

<style>
.ev-page { padding-top: 70px; }
.ev-hero {
  height: 75vh; min-height: 550px; position: relative;
  display: flex; align-items: flex-end; overflow: hidden;
}
.ev-hero-bg { position: absolute; inset: 0; background: url('https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=1800&q=80') center/cover; }
.ev-hero-bg::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to bottom, rgba(0,0,0,.1) 0%, rgba(0,0,0,.7) 100%); }
.ev-hero-inner { position: relative; z-index: 1; width: 100%; padding: 60px 0; color: #fff; }

/* Event cards */
.ev-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 1px; background: var(--bdr2); margin-bottom: 80px; }
.ev-card { background: var(--card); display: flex; flex-direction: column; cursor: pointer; overflow: hidden; transition: none; }
.ev-card-img { height: 260px; overflow: hidden; position: relative; }
.ev-card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .6s var(--ease); display: block; }
.ev-card:hover .ev-card-img img { transform: scale(1.06); }
.ev-card-body { padding: 28px 30px; flex: 1; display: flex; flex-direction: column; }
.ev-type { font-family: var(--cinzel); font-size: 9px; letter-spacing: 3px; color: var(--gold); text-transform: uppercase; margin-bottom: 8px; }
.ev-name { font-family: var(--serif); font-size: 24px; font-weight: 400; margin-bottom: 10px; }
.ev-desc { font-size: 13.5px; color: var(--text2); line-height: 1.7; margin-bottom: 18px; flex: 1; }
.ev-from { font-family: var(--serif); font-size: 20px; color: var(--gold); margin-bottom: 18px; }
.ev-from span { font-size: 13px; color: var(--muted); font-family: var(--sans); }
.ev-enquire { padding: 12px 24px; background: var(--charcoal); color: var(--gold); border: none; font-family: var(--cinzel); font-size: 10px; letter-spacing: 2px; cursor: pointer; transition: background .2s; text-transform: uppercase; }
.ev-enquire:hover { background: var(--gold); color: #fff; }

/* Enquiry form */
.ev-form-section { background: var(--cream2); padding: 80px 0; }
[data-theme="dark"] .ev-form-section { background: var(--card2); }
.ev-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 64px; align-items: start; }
.ev-features { display: flex; flex-direction: column; gap: 24px; }
.ev-feat { display: flex; gap: 16px; }
.ev-feat-icon { width: 52px; height: 52px; background: var(--gold-dim); border: 1px solid var(--gold); flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 22px; }
.ev-feat-title { font-family: var(--serif); font-size: 18px; margin-bottom: 5px; }
.ev-feat-desc { font-size: 13.5px; color: var(--text2); line-height: 1.65; }

@media(max-width:900px) { .ev-grid{grid-template-columns:1fr 1fr} .ev-form-grid{grid-template-columns:1fr} }
@media(max-width:600px) { .ev-grid{grid-template-columns:1fr} }
</style>

<div class="ev-page">
  <!-- Hero -->
  <div class="ev-hero">
    <div class="ev-hero-bg"></div>
    <div class="ev-hero-inner">
      <div class="container">
        <div class="lx-eyebrow" style="justify-content:flex-start;color:rgba(255,255,255,.5)" id="evEye">Extraordinary Occasions</div>
        <h1 style="font-family:var(--serif);font-size:clamp(38px,6vw,80px);font-weight:300;color:#fff;line-height:1.1;max-width:680px" id="evTitle">
          Events & <em style="color:var(--gold)">Celebrations</em>
        </h1>
        <p style="font-size:16px;color:rgba(255,255,255,.75);max-width:500px;line-height:1.8;margin-top:16px" id="evSub">
          From the wedding of your dreams to corporate summits that inspire. Let us orchestrate your most important moments.
        </p>
      </div>
    </div>
  </div>

  <!-- Event Types -->
  <section class="section" style="padding-top:60px">
    <div class="container">
      <div style="text-align:center;margin-bottom:56px">
        <div class="lx-eyebrow lx-reveal" style="justify-content:center">Our Event Venues</div>
        <h2 class="lx-heading lx-reveal">Every <em>Occasion</em>, Perfectly Staged</h2>
      </div>
      <div class="ev-grid">
        <?php foreach ($events as $ev): ?>
        <div class="ev-card">
          <div class="ev-card-img">
            <img src="<?= htmlspecialchars($ev['hero_image']) ?>" alt="<?= htmlspecialchars($ev['name']) ?>" loading="lazy" onerror="this.style.opacity='0'">
          </div>
          <div class="ev-card-body">
            <div class="ev-type"><?= ucfirst($ev['type']) ?></div>
            <div class="ev-name"><?= htmlspecialchars($ev['name']) ?></div>
            <p class="ev-desc"><?= htmlspecialchars($ev['description']) ?></p>
            <div class="ev-from">From <?= formatPrice($ev['price_from']) ?> <span>— up to <?= $ev['capacity'] ?> guests</span></div>
            <button class="ev-enquire" onclick="openEnquiry(<?= $ev['id'] ?>, '<?= htmlspecialchars(addslashes($ev['name'])) ?>')">
              Enquire Now
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Why Royale Vista -->
  <div class="ev-form-section">
    <div class="container">
      <div class="ev-form-grid">
        <div>
          <div class="lx-eyebrow" style="justify-content:flex-start;margin-bottom:14px">Why Royale Vista</div>
          <h2 style="font-family:var(--serif);font-size:clamp(28px,4vw,44px);font-weight:300;margin-bottom:32px">
            Your Vision,<br><em style="color:var(--gold)">Our Expertise</em>
          </h2>
          <div class="ev-features">
            <?php foreach ([
              ['🎯', 'Dedicated Event Team', 'A personal event director guides you from initial concept to the last toast, ensuring every detail exceeds expectation.'],
              ['🌹', 'Bespoke Floral & Décor', 'Our in-house design studio creates breathtaking environments with exclusive florals, custom lighting and artistic installations.'],
              ['🍽', 'Michelin-Starred Catering', 'Personalised menus crafted by our culinary team, from elegant canapés to multi-course banquets with premium wine pairings.'],
              ['📸', 'Preferred Partners', 'Exclusive network of world-renowned photographers, entertainment, and luxury transport to complete your event.'],
            ] as [$icon, $title, $desc]): ?>
            <div class="ev-feat lx-reveal">
              <div class="ev-feat-icon"><?= $icon ?></div>
              <div><div class="ev-feat-title"><?= $title ?></div><div class="ev-feat-desc"><?= $desc ?></div></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Enquiry Form -->
        <div>
          <div style="background:var(--card);border:1px solid var(--bdr2);padding:36px">
            <?php if ($success): ?>
            <div style="text-align:center;padding:24px">
              <div style="font-size:52px;margin-bottom:16px">🎉</div>
              <h3 style="font-family:var(--serif);font-size:26px;margin-bottom:10px">Enquiry Received</h3>
              <p style="color:var(--text2)">Thank you! Our events team will contact you within 24 hours to discuss your event in detail.</p>
            </div>
            <?php else: ?>
            <div style="font-family:var(--cinzel);font-size:10px;color:var(--gold);letter-spacing:2px;text-transform:uppercase;margin-bottom:20px">Submit Your Enquiry</div>
            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="POST" id="evForm">
              <input type="hidden" name="submit_enquiry" value="1">
              <input type="hidden" name="event_id" id="evFormId" value="">
              <div class="form-group">
                <label class="form-label">Event Type</label>
                <select class="form-control" name="event_id" id="evFormSelect">
                  <option value="">— Choose Event Type —</option>
                  <?php foreach ($events as $ev): ?>
                  <option value="<?= $ev['id'] ?>"><?= htmlspecialchars($ev['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Your Name *</label>
                  <input class="form-control" name="name" required placeholder="Full name">
                </div>
                <div class="form-group">
                  <label class="form-label">Email *</label>
                  <input class="form-control" type="email" name="email" required placeholder="you@email.com">
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Phone</label>
                  <input class="form-control" name="phone" placeholder="+1 555 000 0000">
                </div>
                <div class="form-group">
                  <label class="form-label">Event Date</label>
                  <input class="form-control" type="date" name="event_date" min="<?= date('Y-m-d',strtotime('+30 days')) ?>">
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Number of Guests</label>
                  <select class="form-control" name="guests">
                    <?php foreach ([10=>1,25=>1,50=>1,100=>1,200=>1,300=>1,400=>1,500=>1,'500+'=>1] as $n=>$v): ?>
                    <option value="<?= is_int($n)?$n:500 ?>"><?= $n ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Estimated Budget (USD)</label>
                  <input class="form-control" type="number" name="budget" placeholder="e.g. 50000" min="0">
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Tell Us About Your Vision</label>
                <textarea class="form-control" name="message" rows="4" placeholder="Share any special requirements, theme ideas, or questions…"></textarea>
              </div>
              <button type="submit" class="btn btn-gold btn-block btn-lg" style="letter-spacing:2px;font-family:var(--cinzel);font-size:11px;text-transform:uppercase">
                Submit Enquiry
              </button>
            </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  anime({targets:'#evEye', opacity:[0,1],translateX:[-20,0],duration:700,easing:'easeOutCubic',delay:300});
  anime({targets:'#evTitle',opacity:[0,1],translateY:[40,0],duration:900,easing:'easeOutCubic',delay:500});
  anime({targets:'#evSub', opacity:[0,1],translateY:[20,0],duration:700,easing:'easeOutCubic',delay:700});
  anime({targets:'.ev-card',opacity:[0,1],translateY:[24,0],duration:500,easing:'easeOutCubic',delay:anime.stagger(80)});
});

function openEnquiry(id, name) {
  const sel = document.getElementById('evFormSelect');
  if (sel) { sel.value = id; sel.dispatchEvent(new Event('change')); }
  document.getElementById('evFormId').value = id;
  document.querySelector('.ev-form-section')?.scrollIntoView({behavior:'smooth',block:'start'});
}
</script>

<?php require __DIR__ . '/footer.php'; ?>
