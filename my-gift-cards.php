<?php
error_reporting(E_ALL); ini_set('display_errors', '1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$pageTitle = 'My Gift Cards — Royale Vista';
$uid = (int)$_SESSION['user_id'];

// Fetch Gift Cards purchased by this user
$q = $pdo->prepare("SELECT * FROM gift_cards WHERE purchased_by=? ORDER BY created_at DESC");
$q->execute([$uid]);
$gcs = $q->fetchAll();

require __DIR__ . '/header.php';
?>
<style>
.mg-page { padding-top: 88px; min-height: 100vh; background: var(--bg); }
.mg-hero { background: linear-gradient(135deg, var(--bg2), var(--bg)); padding: 56px 0 40px; border-bottom: 1px solid var(--bdr2); }
.mg-wrap { max-width: 1080px; margin: 0 auto; padding: 40px 24px 80px; }
.gc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
.gc-card { background: var(--card); border: 1px solid var(--bdr2); border-radius: var(--radius-lg); overflow: hidden; display: flex; flex-direction: column; transition: transform .2s, box-shadow .2s; }
.gc-card:hover { transform: translateY(-3px); box-shadow: var(--shadow); }
.gc-hd { padding: 24px; background: linear-gradient(135deg, var(--charcoal), var(--charcoal2)); color: #fff; position: relative; overflow: hidden; }
.gc-hd-bg { position: absolute; right: -20px; top: -20px; font-size: 100px; opacity: .05; pointer-events: none; }
.gc-code { font-family: var(--serif); font-size: 26px; color: var(--gold); letter-spacing: 2px; margin-bottom: 8px; }
.gc-val { font-size: 20px; font-weight: 600; }
.gc-bd { padding: 20px 24px; display: flex; flex-direction: column; gap: 12px; flex: 1; }
.gc-row { display: flex; justify-content: space-between; font-size: 13.5px; }
.gc-row .l { color: var(--muted); }
.gc-row .v { font-weight: 500; color: var(--text); }
.gc-bal { display: flex; justify-content: space-between; padding-top: 14px; margin-top: auto; border-top: 1px solid var(--bdr2); font-size: 15px; font-weight: 600; color: var(--gold); }
.empty-state { text-align: center; padding: 60px 20px; background: var(--card); border: 1px dashed var(--bdr2); border-radius: var(--radius); }
</style>

<div class="mg-page">
  <div class="mg-hero">
    <div class="container">
      <div class="lx-eyebrow" style="justify-content:flex-start">Your Purchases</div>
      <h1 class="lx-heading">My <em style="color:var(--gold)">Gift Cards</em></h1>
      <p style="color:var(--text2);font-size:15px;max-width:500px;margin-top:10px">Manage and track the gift cards you have securely purchased for your friends, family, or colleagues.</p>
    </div>
  </div>
  
  <div class="mg-wrap">
    <?php if (empty($gcs)): ?>
    <div class="empty-state">
      <div style="font-size:48px;margin-bottom:16px;opacity:.5">🎁</div>
      <h3 style="font-family:var(--serif);font-size:22px;margin-bottom:8px">No Gift Cards Purchased</h3>
      <p style="color:var(--text2);margin-bottom:20px">You haven't bought any gift cards yet. Surprise someone special today.</p>
      <a href="<?= $B ?>/gift-cards.php" class="btn btn-gold"><i class="fas fa-gift"></i> Buy a Gift Card</a>
    </div>
    <?php else: ?>
    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center">
      <div style="font-size: 14px; color: var(--text2)"><?= count($gcs) ?> Card<?= count($gcs)>1?'s':'' ?> Found</div>
      <a href="<?= $B ?>/gift-cards.php" class="btn btn-outline btn-sm"><i class="fas fa-plus"></i> Buy Another</a>
    </div>
    
    <div class="gc-grid">
      <?php foreach ($gcs as $g): 
        $isExp = $g['expires_at'] && strtotime($g['expires_at']) < time();
        $isActive = $g['is_active'] && !$isExp && $g['balance_usd'] > 0;
      ?>
      <div class="gc-card">
        <div class="gc-hd">
          <div class="gc-hd-bg">✦</div>
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px">
            <span class="badge <?= $isActive?'badge-green':($g['balance_usd']==0?'badge-amber':'badge-red') ?>" style="font-size:10px; padding:3px 8px">
              <?= $g['balance_usd']==0 ? 'Exhausted' : ($isActive ? 'Active' : 'Expired/Inactive') ?>
            </span>
            <div style="font-size:12px; opacity:.7"><?= date('M d, Y', strtotime($g['created_at'])) ?></div>
          </div>
          <div class="gc-code"><?= htmlspecialchars($g['code']) ?></div>
          <div class="gc-val">Original Value: <?= formatPrice($g['value_usd']) ?></div>
        </div>
        <div class="gc-bd">
          <div class="gc-row"><span class="l">Recipient</span><span class="v"><?= htmlspecialchars($g['for_name'] ?: 'None') ?></span></div>
          <div class="gc-row"><span class="l">Email</span><span class="v" style="font-family:var(--sans)"><?= htmlspecialchars($g['for_email']) ?></span></div>
          <div class="gc-row"><span class="l">Expires</span><span class="v" style="<?= $isExp?'color:var(--red)':'' ?>"><?= $g['expires_at'] ? date('M d, Y', strtotime($g['expires_at'])) : 'Never' ?></span></div>
          
          <?php if ($g['message']): ?>
          <div style="margin-top:8px; padding:12px; background:var(--card2); border-radius:8px; border-left:2px solid var(--gold); font-size:12.5px; color:var(--text2); font-style:italic">
            "<?= nl2br(htmlspecialchars($g['message'])) ?>"
          </div>
          <?php endif; ?>
          
          <div class="gc-bal">
            <span>Remaining Balance</span>
            <span><?= formatPrice($g['balance_usd']) ?></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  anime({
    targets: '.gc-card',
    translateY: [20, 0],
    opacity: [0, 1],
    duration: 600,
    delay: anime.stagger(100),
    easing: 'easeOutExpo'
  });
});
</script>

<?php require __DIR__ . '/footer.php'; ?>
