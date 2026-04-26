<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Careers — Royale Vista';
$msg = '';

// Handle job application submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_job'])) {
  $name = clean($_POST['name'] ?? '');
  $email = clean($_POST['email'] ?? '');
  $phone = clean($_POST['phone'] ?? '');
  $position = clean($_POST['position'] ?? '');
  $location = clean($_POST['location'] ?? '');
  $message = clean($_POST['cover'] ?? '');
  $exp = clean($_POST['experience'] ?? '');

  if ($name && $email && $position) {
    // Handle CV upload
    $cvPath = null;
    if (!empty($_FILES['cv']['name'])) {
      $ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
      if (in_array($ext, ['pdf', 'doc', 'docx']) && $_FILES['cv']['size'] <= 5 * 1024 * 1024) {
        $newName = 'cv_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $upDir = __DIR__ . '/uploads/cvs/';
        if (!is_dir($upDir))
          @mkdir($upDir, 0755, true);
        if (move_uploaded_file($_FILES['cv']['tmp_name'], $upDir . $newName)) {
          $cvPath = 'uploads/cvs/' . $newName;
        }
      }
    }

    if (empty($errors)) {
      try {
        $pdo->prepare("INSERT INTO job_applications (name, email, phone, position, location, experience_years, cover_letter, cv_path, applied_at)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())")
          ->execute([$name, $email, $phone, $position, $location, $exp, $message, $cvPath]);
        $msg = 'success';
      } catch (\Exception $e) { $msg = 'error'; }
    } else { $msg = 'error'; }
  } else { $msg = 'error'; }
}

$jobs = [
  ['title' => 'Guest Experience Manager', 'loc' => 'Dubai, UAE', 'type' => 'Full-Time', 'dept' => 'Operations', 'id' => 'gem'],
  ['title' => 'Executive Sous Chef', 'loc' => 'New York, USA', 'type' => 'Full-Time', 'dept' => 'Culinary', 'id' => 'esc'],
  ['title' => 'Senior Spa Therapist', 'loc' => 'Paris, France', 'type' => 'Part-Time', 'dept' => 'Wellness', 'id' => 'sst'],
  ['title' => 'Director of Sales & Marketing', 'loc' => 'Singapore', 'type' => 'Full-Time', 'dept' => 'Commercial', 'id' => 'dsm'],
  ['title' => 'AI & Technology Lead', 'loc' => 'Remote / Dubai', 'type' => 'Full-Time', 'dept' => 'Technology', 'id' => 'atl'],
  ['title' => 'Luxury Concierge Specialist', 'loc' => 'London, UK', 'type' => 'Full-Time', 'dept' => 'Guest Services', 'id' => 'lcs'],
];

require __DIR__ . '/header.php';
?>
<style>
  .car-hero {
    position: relative;
    height: 60vh;
    min-height: 420px;
    display: flex;
    align-items: center;
    overflow: hidden
  }

  .car-hero-bg {
    position: absolute;
    inset: 0;
    background: url('https://images.unsplash.com/photo-1556761175-5973dc0f32d7?w=1600&q=80') center/cover;
    transform: scale(1.05);
    animation: heroZoom 14s ease-in-out infinite alternate
  }

  @keyframes heroZoom {
    from {
      transform: scale(1.05)
    }

    to {
      transform: scale(1.12)
    }
  }

  .car-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to right, rgba(0, 0, 0, .7) 40%, rgba(0, 0, 0, .2))
  }

  .car-hero-ct {
    position: relative;
    z-index: 2;
    padding: 0 40px;
    color: #fff;
    max-width: 680px
  }

  .car-hero-ct h1 {
    font-family: var(--serif);
    font-size: clamp(36px, 6vw, 72px);
    font-weight: 300;
    line-height: 1.15;
    margin-bottom: 20px
  }

  .car-hero-ct p {
    font-size: 16px;
    line-height: 1.8;
    color: rgba(255, 255, 255, .8);
    max-width: 480px
  }

  .car-section {
    padding: 80px 0
  }

  .job-card {
    background: var(--card);
    border: 1px solid var(--bdr2);
    border-radius: 14px;
    padding: 28px 32px;
    margin-bottom: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    transition: all .3s
  }

  .job-card:hover {
    transform: translateY(-4px);
    border-color: var(--gold);
    box-shadow: 0 12px 40px rgba(0, 0, 0, .18)
  }

  .job-title {
    font-family: var(--serif);
    font-size: 22px;
    font-weight: 400;
    color: var(--text);
    margin-bottom: 8px
  }

  .job-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 13px;
    color: var(--text2)
  }

  .job-meta span {
    display: flex;
    align-items: center;
    gap: 5px
  }

  .job-badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .5px;
    text-transform: uppercase
  }

  .badge-ft {
    background: rgba(34, 197, 94, .12);
    color: #22c55e
  }

  .badge-pt {
    background: rgba(245, 158, 11, .12);
    color: #f59e0b
  }

  .values-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 24px;
    margin-top: 40px
  }

  .val-card {
    padding: 32px 28px;
    background: var(--card);
    border: 1px solid var(--bdr2);
    border-radius: 14px;
    text-align: center;
    transition: .3s
  }

  .val-card:hover {
    border-color: var(--gold)
  }

  .val-icon {
    font-size: 40px;
    margin-bottom: 16px
  }

  .val-title {
    font-family: var(--serif);
    font-size: 18px;
    margin-bottom: 8px
  }

  .val-desc {
    font-size: 14px;
    color: var(--text2);
    line-height: 1.7
  }

  /* Application Modal */
  .apl-modal-bg {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .8);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    padding: 20px
  }

  .apl-modal-bg.open {
    display: flex
  }

  .apl-modal {
    background: var(--card);
    border: 1px solid var(--gold);
    border-radius: 20px;
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 36px;
    position: relative;
    animation: popIn .3s cubic-bezier(.34, 1.3, .64, 1)
  }

  @keyframes popIn {
    from {
      opacity: 0;
      transform: scale(.9)
    }

    to {
      opacity: 1;
      transform: none
    }
  }

  .apl-close {
    position: absolute;
    top: 16px;
    right: 20px;
    font-size: 24px;
    cursor: pointer;
    color: var(--text2);
    background: none;
    border: none;
    line-height: 1;
    transition: .2s
  }

  .apl-close:hover {
    color: var(--red)
  }

  .apl-title {
    font-family: var(--serif);
    font-size: 26px;
    color: var(--gold);
    margin-bottom: 6px
  }

  .apl-sub {
    font-size: 14px;
    color: var(--text2);
    margin-bottom: 26px
  }

  .apl-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px
  }

  @media(max-width:560px) {
    .apl-grid {
      grid-template-columns: 1fr
    }

    .job-card {
      flex-direction: column;
      align-items: flex-start
    }
  }

  .apl-field {
    display: flex;
    flex-direction: column;
    gap: 6px
  }

  .apl-field label {
    font-size: 11px;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--gold);
    font-weight: 600
  }

  .apl-field input,
  .apl-field select,
  .apl-field textarea {
    background: var(--card2);
    border: 1px solid var(--bdr2);
    border-radius: 8px;
    padding: 11px 14px;
    color: var(--text);
    font-family: var(--sans);
    font-size: 14px;
    outline: none;
    transition: border-color .2s;
    width: 100%
  }

  .apl-field input:focus,
  .apl-field select:focus,
  .apl-field textarea:focus {
    border-color: var(--gold)
  }

  .success-screen {
    text-align: center;
    padding: 30px 0
  }
</style>

<!-- Application Modal -->
<div class="apl-modal-bg" id="aplModal">
  <div class="apl-modal">
    <button class="apl-close" onclick="closeApply()">×</button>
    <div id="aplFormWrap">
      <div class="apl-title">Apply for Position</div>
      <div class="apl-sub" id="aplPositionLabel"></div>
      <form method="POST" id="aplForm" enctype="multipart/form-data">
        <input type="hidden" name="apply_job" value="1">
        <input type="hidden" name="position" id="aplPositionInput">
        <input type="hidden" name="location" id="aplLocationInput">
        <div class="apl-grid" style="margin-bottom:16px">
          <div class="apl-field">
            <label>Full Name *</label>
            <input type="text" name="name" placeholder="Your name" required>
          </div>
          <div class="apl-field">
            <label>Email Address *</label>
            <input type="email" name="email" placeholder="your@email.com" required>
          </div>
        </div>
        <div class="apl-grid" style="margin-bottom:16px">
          <div class="apl-field">
            <label>Phone Number</label>
            <input type="tel" name="phone" placeholder="+1 234 567 8900">
          </div>
          <div class="apl-field">
            <label>Years of Experience</label>
            <select name="experience">
              <option value="">Select range</option>
              <option value="0-1">0 – 1 year</option>
              <option value="1-3">1 – 3 years</option>
              <option value="3-5">3 – 5 years</option>
              <option value="5-10">5 – 10 years</option>
              <option value="10+">10+ years</option>
            </select>
          </div>
        </div>
        <div class="apl-field" style="margin-bottom:20px">
          <label>Cover Letter / Message *</label>
          <textarea name="cover" rows="4" placeholder="Tell us why you'd be a perfect fit for this role…"
            required></textarea>
        </div>
        <div class="apl-field" style="margin-bottom:24px">
          <label>Upload CV (PDF/DOCX) *</label>
          <div
            style="background:var(--card2);border:1px dashed var(--gold);border-radius:10px;padding:14px;text-align:center;cursor:pointer"
            onclick="document.getElementById('cvInp').click()">
            <div id="cvLabel"><i class="fas fa-file-upload"
                style="color:var(--gold);font-size:20px;display:block;margin-bottom:6px"></i> Select CV File</div>
            <input type="file" name="cv" id="cvInp" accept=".pdf,.doc,.docx" style="display:none" required
              onchange="document.getElementById('cvLabel').textContent=this.files[0].name">
          </div>
        </div>
        <button type="submit" class="btn btn-gold" style="width:100%;padding:14px;font-size:15px">
    <div class="apl-inner" id="aplInner">
      <div id="aplFormWrap" style="<?= $msg === 'success' ? 'display:none' : '' ?>">
        <div style="font-family:var(--serif);font-size:28px;margin-bottom:8px">Apply for Position</div>
        <p style="color:var(--mu);margin-bottom:24px;font-size:14px">Your journey with Royale Vista begins here.</p>
        <form method="POST" id="aplForm" enctype="multipart/form-data">
          <input type="hidden" name="apply_job" value="1">
          <input type="hidden" name="position" id="aplPositionInput">
          <input type="hidden" name="location" id="aplLocationInput">
          <div class="apl-grid" style="margin-bottom:16px">
            <div class="apl-field">
              <label>Full Name *</label>
              <input type="text" name="name" placeholder="Your name" required>
            </div>
            <div class="apl-field">
              <label>Email Address *</label>
              <input type="email" name="email" placeholder="your@email.com" required>
            </div>
          </div>
          <div class="apl-grid" style="margin-bottom:16px">
            <div class="apl-field">
              <label>Phone Number</label>
              <input type="tel" name="phone" placeholder="+1 234 567 8900">
            </div>
            <div class="apl-field">
              <label>Years of Experience</label>
              <select name="experience">
                <option value="">Select range</option>
                <option value="0-1">0 – 1 year</option>
                <option value="1-3">1 – 3 years</option>
                <option value="3-5">3 – 5 years</option>
                <option value="5-10">5 – 10 years</option>
                <option value="10+">10+ years</option>
              </select>
            </div>
          </div>
          <div class="apl-field" style="margin-bottom:20px">
            <label>Cover Letter / Message *</label>
            <textarea name="cover" rows="4" placeholder="Tell us why you'd be a perfect fit for this role…"
              required></textarea>
          </div>
          <div class="apl-field" style="margin-bottom:24px">
            <label>Upload CV (PDF/DOCX) *</label>
            <div
              style="background:var(--card2);border:1px dashed var(--gold);border-radius:10px;padding:14px;text-align:center;cursor:pointer"
              onclick="document.getElementById('cvInp').click()">
              <div id="cvLabel"><i class="fas fa-file-upload"
                  style="color:var(--gold);font-size:20px;display:block;margin-bottom:6px"></i> Select CV File</div>
              <input type="file" name="cv" id="cvInp" accept=".pdf,.doc,.docx" style="display:none" required
                onchange="document.getElementById('cvLabel').textContent=this.files[0].name">
            </div>
          </div>
          <button type="submit" class="btn btn-gold" style="width:100%;padding:14px;font-size:15px">
            <i class="fas fa-paper-plane"></i> Submit Application
          </button>
        </form>
      </div>
      <div id="aplSuccess" class="success-screen" style="<?= $msg === 'success' ? 'display:flex' : 'display:none' ?>; flex-direction:column; align-items:center; text-align:center; padding:40px 0;">
        <div style="font-size:56px;margin-bottom:16px">✨</div>
        <div style="font-family:var(--serif);font-size:28px;margin-bottom:10px">Application Received</div>
        <p style="color:var(--text2);font-size:15px;line-height:1.7;max-width:400px">Thank you for your interest in joining Royale Vista. Our talent team will review your application and reach out within 3-5 business days.</p>
        <button onclick="closeApply()" class="btn btn-gold" style="margin-top:28px; padding:12px 40px">Return to Careers</button>
      </div>
    </div>
  </div>
</div>

<!-- Hero -->
<div class="car-hero">
  <div class="car-hero-bg"></div>
  <div class="car-hero-overlay"></div>
  <div class="car-hero-ct">
    <div class="lx-eyebrow" style="color:var(--gold);margin-bottom:14px">Join Our Team</div>
    <h1>Craft the <em style="color:var(--gold)">Extraordinary</em></h1>
    <p>At Royale Vista, we are seeking passionate individuals dedicated to the art of luxury hospitality. Bring your
      talent to the world's most prestigious properties.</p>
    <div style="display:flex;gap:12px;margin-top:28px;flex-wrap:wrap">
      <span style="background:rgba(255,255,255,.1);padding:8px 16px;border-radius:20px;font-size:13px"><i
          class="fas fa-briefcase" style="color:var(--gold);margin-right:6px"></i> <?= count($jobs) ?> Open
        Positions</span>
      <span style="background:rgba(255,255,255,.1);padding:8px 16px;border-radius:20px;font-size:13px"><i
          class="fas fa-globe" style="color:var(--gold);margin-right:6px"></i> 8 Global Properties</span>
    </div>
  </div>
</div>

<!-- Values -->
<section style="background:var(--bg2);border-bottom:1px solid var(--bdr2);padding:60px 0">
  <div class="container">
    <div class="lx-eyebrow" style="margin-bottom:14px">Why Royale Vista?</div>
    <div class="values-grid">
      <?php foreach ([
        ['🌟', 'World-Class Training', 'Access to the industry\'s best hospitality programs and mentorship.'],
        ['🌍', 'Global Mobility', 'Work and grow across our eight prestigious international properties.'],
        ['💎', 'Premium Benefits', 'Luxury hotel stays, dining credits, wellness packages and more.'],
        ['🚀', 'Career Growth', 'Clear pathways from junior roles to executive leadership.'],
      ] as [$ic, $t, $d]): ?>
        <div class="val-card lx-reveal">
          <div class="val-icon"><?= $ic ?></div>
          <div class="val-title"><?= $t ?></div>
          <div class="val-desc"><?= $d ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Jobs -->
<section class="car-section">
  <div class="container">
    <div style="text-align:center;margin-bottom:48px">
      <div class="lx-eyebrow" style="margin-bottom:12px">Open Roles</div>
      <h2 style="font-family:var(--serif);font-size:clamp(28px,4vw,48px);font-weight:300">Current <em
          style="color:var(--gold)">Openings</em></h2>
    </div>

    <?php if ($msg === 'success'): ?>
      <div
        style="background:rgba(34,197,94,.1);border:1px solid #22c55e;border-radius:12px;padding:20px 24px;margin-bottom:30px;display:flex;gap:14px;align-items:center">
        <i class="fas fa-check-circle" style="color:#22c55e;font-size:22px;flex-shrink:0"></i>
        <div>
          <div style="font-weight:700;margin-bottom:4px">Application Received!</div>
          <div style="font-size:14px;color:var(--text2)">Thank you for applying. Our team will reach out within 5 business
            days.</div>
        </div>
      </div>
    <?php endif; ?>

    <div style="max-width:880px;margin:0 auto">
      <?php foreach ($jobs as $job): ?>
        <div class="job-card lx-reveal">
          <div style="flex:1">
            <div class="job-title"><?= htmlspecialchars($job['title']) ?></div>
            <div class="job-meta">
              <span><i class="fas fa-map-marker-alt" style="color:var(--gold)"></i>
                <?= htmlspecialchars($job['loc']) ?></span>
              <span><i class="fas fa-building" style="color:var(--gold)"></i> <?= htmlspecialchars($job['dept']) ?></span>
              <span><span
                  class="job-badge <?= $job['type'] === 'Full-Time' ? 'badge-ft' : 'badge-pt' ?>"><?= $job['type'] ?></span></span>
            </div>
          </div>
          <button class="btn btn-gold"
            onclick="openApply('<?= addslashes($job['title']) ?>','<?= addslashes($job['loc']) ?>')">
            <i class="fas fa-paper-plane"></i> Apply Now
          </button>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA Bar -->
<section
  style="background:linear-gradient(135deg,var(--charcoal,#1a1612),#2a1f14);padding:70px 20px;text-align:center;border-top:1px solid var(--bdr2)">
  <div class="container">
    <h2 style="font-family:var(--serif);font-size:clamp(24px,4vw,44px);font-weight:300;color:#fff;margin-bottom:14px">
      Don't See Your <em style="color:var(--gold)">Perfect Role?</em></h2>
    <p style="color:rgba(255,255,255,.6);font-size:15px;max-width:500px;margin:0 auto 28px">Send us your CV anyway.
      We're always open to exceptional talent across all departments.</p>
    <button onclick="openApply('General Application','All Locations')" class="btn btn-gold btn-lg">
      <i class="fas fa-envelope"></i> Send Speculative Application
    </button>
  </div>
</section>

<script>
  function openApply(position, loc) {
    document.getElementById('aplPositionLabel').textContent = position + ' · ' + loc;
    document.getElementById('aplPositionInput').value = position;
    document.getElementById('aplLocationInput').value = loc;
    document.getElementById('aplModal').classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeApply() {
    document.getElementById('aplModal').classList.remove('open');
    document.body.style.overflow = '';
  }
  document.getElementById('aplModal').addEventListener('click', function (e) {
    if (e.target === this) closeApply();
  });

  <?php if ($msg === 'success'): ?>
    // Show success screen inside modal if form was submitted
    window.addEventListener('DOMContentLoaded', () => {
      document.getElementById('aplFormWrap').style.display = 'none';
      document.getElementById('aplSuccess').style.display = 'block';
      openApply('', '');
    });
  <?php endif; ?>

  // Handle AJAX submission for seamless UX
  document.getElementById('aplForm')?.addEventListener('submit', async function (e) {
    const btn = this.querySelector('button[type=submit]');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting…';
    btn.disabled = true;
    // Let it naturally POST, which will reload and show success banner
  });
</script>

<?php require __DIR__ . '/footer.php'; ?>