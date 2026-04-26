<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';
$expSuccess = false; $expError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_experience'])) {
    $expTitle = clean($_POST['experience_title'] ?? '');
    $date = clean($_POST['exp_date'] ?? '');
    $guests = max(1, (int)($_POST['guests'] ?? 1));
    $name = clean($_POST['name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $notes = clean($_POST['message'] ?? '');
    if (!$expTitle || !$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$date) {
        $expError = 'Please fill all required fields.';
    } else {
        $reqText = "Experience: {$expTitle}\nDate: {$date}\nGuests: {$guests}\nName: {$name}\nEmail: {$email}\nNotes: {$notes}";
        $ref = 'EX' . date('Y') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        $pdo->prepare("INSERT INTO concierge_requests (ref,user_id,category,request,preferred_date,status,is_read) VALUES (?,?,?,?,?,'pending',0)")
            ->execute([$ref, $_SESSION['user_id'] ?? null, 'activity', $reqText, $date]);
        $expSuccess = true;
    }
}
$pageTitle = 'Experiences — Royale Vista';

try {
    $experiences = $pdo->query("SELECT * FROM experiences WHERE is_active=1 ORDER BY sort_order")->fetchAll();
} catch(Exception $e) { $experiences = []; }
if (empty($experiences)) {
    try { $experiences = $pdo->query("SELECT * FROM experiences ORDER BY sort_order")->fetchAll(); } catch (Exception $e) {}
}

$catFilter = clean($_GET['cat'] ?? 'All');
$categories = array_unique(array_column($experiences, 'category'));
$filtered   = $catFilter === 'All' ? $experiences : array_filter($experiences, fn($e) => $e['category'] === $catFilter);

require __DIR__ . '/header.php';
?>
<style>
.exp-page { padding-top: 70px; }
.exp-hero {
  height: 60vh; min-height: 460px; position: relative;
  display: flex; align-items: flex-end; overflow: hidden;
  background: url('https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=1600&q=80') center/cover;
}
.exp-hero::before { content: ''; position: absolute; inset: 0; background: linear-gradient(to bottom,transparent 20%,rgba(0,0,0,.75)); }
.exp-hero-content { position: relative; z-index: 1; padding-bottom: 56px; width: 100%; }
.exp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px,1fr)); gap: 24px; }
.cat-filter-bar { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 36px; }
.cat-filter-btn {
  padding: 8px 22px; border-radius: 2px; font-family: var(--cinzel); font-size: 9px;
  letter-spacing: 2px; text-transform: uppercase; border: 1px solid var(--border);
  color: var(--text2); background: transparent; cursor: pointer; transition: all .2s; text-decoration: none;
}
.cat-filter-btn:hover,.cat-filter-btn.active { background: var(--charcoal); color: var(--gold); border-color: var(--charcoal); }
.exp-price-badge { display: inline-flex; align-items: center; gap: 5px; background: var(--gold); color: #fff; font-size: 10px; font-family: var(--cinzel); letter-spacing: 1.5px; padding: 3px 11px; border-radius: 2px; text-transform: uppercase; margin-bottom: 8px; }
.exp-title-lg { font-family: var(--serif); font-size: 26px; font-weight: 300; margin-bottom: 10px; }
.exp-desc-full { font-size: 13.5px; color: var(--text2); line-height: 1.8; }
.exp-meta-row { display: flex; gap: 16px; font-size: 12px; color: var(--muted); margin: 12px 0; flex-wrap: wrap; }
.exp-meta-row span { display: flex; align-items: center; gap: 5px; }
.exp-meta-row i { color: var(--gold); }
</style>

<div class="exp-page">
  <!-- Hero -->
  <div class="exp-hero">
    <div class="container exp-hero-content">
      <div class="lx-eyebrow" style="justify-content:flex-start;color:rgba(255,255,255,.5)" data-reveal="up">Beyond Accommodation</div>
      <h1 class="lx-heading" style="color:#fff" data-reveal="up" data-delay="100">Curated <em>Experiences</em></h1>
      <p style="color:rgba(255,255,255,.7);font-size:15px;max-width:520px;line-height:1.8;margin:14px 0 0" data-reveal="up" data-delay="200">From private helicopter tours to underground truffle hunts — we curate moments that transcend the ordinary.</p>
    </div>
  </div>

  <section class="section">
    <div class="container">
      <!-- Category filter -->
      <?php if ($expSuccess): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Experience request submitted successfully.</div><?php endif; ?>
      <?php if ($expError): ?><div class="alert alert-error"><?= htmlspecialchars($expError) ?></div><?php endif; ?>
      <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:36px">
        <div>
          <div class="lx-eyebrow lx-reveal">Our Collection</div>
          <h2 class="lx-heading lx-reveal"><?= count($filtered) ?> <em>Experiences</em></h2>
        </div>
        <div class="cat-filter-bar">
          <a href="?cat=All" class="cat-filter-btn <?= $catFilter==='All'?'active':'' ?>">All</a>
          <?php foreach ($categories as $cat): ?>
          <a href="?cat=<?= urlencode($cat) ?>" class="cat-filter-btn <?= $catFilter===$cat?'active':'' ?>"><?= htmlspecialchars($cat) ?></a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Grid -->
      <div class="exp-grid">
        <?php foreach ($filtered as $i => $exp): ?>
        <div class="exp-card rv-tilt" data-reveal="up" data-delay="<?= ($i%3)*80 ?>">
          <div class="exp-img">
            <img src="<?= htmlspecialchars($exp['image_url']) ?>" alt="<?= htmlspecialchars($exp['title']) ?>" loading="lazy"
                 onerror="this.style.opacity='0'">
            <div class="exp-cat"><?= htmlspecialchars($exp['category']) ?></div>
          </div>
          <div class="exp-body">
            <div class="exp-price-badge">From <?= formatPrice($exp['price_usd']) ?> per person</div>
            <div class="exp-title"><?= htmlspecialchars($exp['title']) ?></div>
            <div class="exp-meta">
              <span><i class="fas fa-clock" style="color:var(--gold)"></i><?= htmlspecialchars($exp['duration']) ?></span>
              <span><i class="fas fa-users" style="color:var(--gold)"></i>Up to <?= $exp['max_guests'] ?> guests</span>
            </div>
            <p class="exp-desc"><?= htmlspecialchars($exp['description']) ?></p>
            <button class="btn btn-gold btn-block" style="margin-top:16px" onclick="openBookExp(<?= $exp['id'] ?>,'<?= htmlspecialchars(addslashes($exp['title'])) ?>',<?= $exp['price_usd'] ?>)">
              <i class="fas fa-calendar-check"></i> Reserve Experience
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Bespoke CTA -->
      <div style="background:var(--charcoal);border-radius:var(--radius-lg);padding:60px;text-align:center;margin-top:64px;position:relative;overflow:hidden" data-reveal="up">
        <div style="position:absolute;inset:0;background:repeating-linear-gradient(45deg,rgba(192,155,91,.03) 0,rgba(192,155,91,.03) 1px,transparent 0,transparent 50%);background-size:20px 20px"></div>
        <div style="position:relative;z-index:1">
          <div class="lx-eyebrow" style="justify-content:center;margin-bottom:16px">Bespoke Experiences</div>
          <h2 style="font-family:var(--serif);font-size:clamp(28px,4vw,44px);font-weight:300;color:#fff;margin-bottom:14px">Something <em style="color:var(--gold)">Truly Unique?</em></h2>
          <p style="color:rgba(255,255,255,.6);font-size:15px;max-width:480px;margin:0 auto 28px;line-height:1.8">Our Lifestyle Managers specialise in creating one-of-a-kind experiences tailored precisely to your desires.</p>
          <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
            <a href="<?= $B ?>/concierge.php" class="btn btn-gold btn-lg">Talk to a Lifestyle Manager</a>
            <a href="<?= $B ?>/contact.php" class="btn" style="border:1px solid rgba(255,255,255,.3);color:rgba(255,255,255,.8);background:transparent;padding:15px 36px;font-size:13px">Contact Us</a>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- Experience Booking Modal -->
<div class="lx-modal-back" id="expBookModal" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="lx-modal" style="max-width:480px">
    <div class="lx-modal-hd">
      <div class="lx-modal-title" id="expModalTitle">Reserve Experience</div>
      <button class="lx-modal-close" onclick="document.getElementById('expBookModal').classList.remove('open')">×</button>
    </div>
    <form method="POST" class="lx-modal-bd">
      <input type="hidden" name="submit_experience" value="1">
      <input type="hidden" name="experience_title" id="expTitleVal">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Your Name</label>
          <input class="form-control" name="name" required placeholder="Full name" value="<?= isLoggedIn()?htmlspecialchars($_SESSION['username']??''):'' ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input class="form-control" type="email" name="email" required placeholder="you@email.com" value="<?= isLoggedIn()?htmlspecialchars($_SESSION['email']??''):'' ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Preferred Date</label>
          <input class="form-control" type="date" name="exp_date" min="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Number of Guests</label>
          <select class="form-control" name="guests">
            <?php for($g=1;$g<=8;$g++): ?><option value="<?=$g?>"><?=$g?></option><?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Special Requests</label>
        <textarea class="form-control" name="message" rows="3" placeholder="Any special arrangements, dietary requirements, etc."></textarea>
      </div>
      <div class="lx-modal-ft">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('expBookModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-gold"><i class="fas fa-calendar-check"></i> Send Request</button>
      </div>
    </form>
  </div>
</div>

<script>
function openBookExp(id, name, price) {
  document.getElementById('expModalTitle').textContent = 'Reserve: ' + name;
  document.getElementById('expTitleVal').value = name;
  document.getElementById('expBookModal').classList.add('open');
  if (window.anime) anime({ targets: '.lx-modal', scale:[.9,1], opacity:[0,1], duration:350, easing:'easeOutBack' });
}
</script>

<?php require __DIR__ . '/footer.php'; ?>
