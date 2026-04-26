<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$pageTitle = 'Loyalty Rewards — Royale Vista';
$uid = (int)$_SESSION['user_id'];

// Ensure loyalty row
$pdo->prepare("INSERT IGNORE INTO loyalty_points (user_id,total_points,lifetime_points,tier) VALUES (?,0,0,'bronze')")->execute([$uid]);

$lp = $pdo->prepare("SELECT * FROM loyalty_points WHERE user_id=?");
$lp->execute([$uid]);
$loyalty = $lp->fetch();

$transactions = $pdo->prepare("SELECT * FROM loyalty_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 30");
$transactions->execute([$uid]);
$txs = $transactions->fetchAll();

$balance  = (int)($loyalty['total_points'] ?? 0);
$lifetime = (int)($loyalty['lifetime_points'] ?? 0);

// Tier system
$tiers = [
    'bronze'   => ['name'=>'Bronze',   'min'=>0,     'max'=>1999,  'color'=>'#cd7f32','emoji'=>'🔸','mult'=>'1x', 'perks'=>['Earn 10 pts per $1','Birthday bonus points','Member newsletter']],
    'silver'   => ['name'=>'Silver',   'min'=>2000,  'max'=>4999,  'color'=>'#94a3b8','emoji'=>'🥉','mult'=>'1.2x','perks'=>['Earn 12 pts per $1','Free room upgrade request','Early check-in (noon)']],
    'gold'     => ['name'=>'Gold',     'min'=>5000,  'max'=>9999,  'color'=>'#d4af37','emoji'=>'🥇','mult'=>'1.5x','perks'=>['Earn 15 pts per $1','Guaranteed room upgrade','Late check-out 2PM','Priority support']],
    'platinum' => ['name'=>'Platinum', 'min'=>10000, 'max'=>null,  'color'=>'#8b5cf6','emoji'=>'👑','mult'=>'2x', 'perks'=>['Earn 20 pts per $1','Complimentary breakfast daily','Late check-out 4PM','Airport limousine pickup','Dedicated concierge']],
];

$currentTier = $loyalty['tier'] ?? 'bronze';
$tierInfo = $tiers[$currentTier] ?? $tiers['bronze'];
$nextTierKey = ['bronze'=>'silver', 'silver'=>'gold', 'gold'=>'platinum'][$currentTier] ?? null;
$nextTier    = $nextTierKey ? $tiers[$nextTierKey] : null;
$progressPct = 0;
if ($nextTier) {
    $range = $nextTier['min'] - $tierInfo['min'];
    $done  = max(0, $lifetime - $tierInfo['min']);
    $progressPct = min(100, round($done / max($range,1) * 100));
}
$ptsToNext = $nextTier ? max(0, $nextTier['min'] - $lifetime) : 0;

require __DIR__ . '/header.php';
?>
<style>
/* Loyalty specific */
.tier-card {
  background: linear-gradient(135deg, var(--tier-from), var(--tier-to));
  border-radius: 30px; padding: 50px;
  position: relative; overflow: hidden; color: #000; margin-bottom: 40px;
  box-shadow: 0 15px 45px rgba(0,0,0,0.1);
}
.tier-card::before { content: '✦'; position: absolute; right: 20px; top: 10px; font-size: 100px; opacity: .07; line-height: 1; }
.tier-badge { display: inline-flex; align-items: center; gap: 8px; background: rgba(0,0,0,.1); padding: 5px 16px; border-radius: 20px; font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; }
.points-big { font-family: var(--serif); font-size: 56px; font-weight: 400; line-height: 1; }
.progress-wrap { background: rgba(0,0,0,.1); border-radius: 6px; height: 6px; overflow: hidden; margin-top: 14px; }
.progress-fill { height: 100%; border-radius: 6px; background: rgba(0,0,0,.3); transition: width 1s ease; }
.tx-item { display: flex; align-items: center; gap: 14px; padding: 14px 20px; border-bottom: 1px solid var(--bdr2); }
.tx-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
.tx-earn { background: rgba(34,197,94,.1); }
.tx-redeem { background: rgba(239,68,68,.1); }
.tx-bonus { background: rgba(245,158,11,.1); }
.tx-amount-pos { color: #22c55e; font-weight: 700; font-size: 14px; }
.tx-amount-neg { color: #ef4444; font-weight: 700; font-size: 14px; }
</style>

<div class="loyalty-page" style="padding-top:120px; padding-bottom:100px;">
  <div class="container">
    <div class="section-label">Rewards</div>
    <h1 style="font-family:var(--serif);font-size:42px;margin-bottom:10px">Royale Rewards</h1>
    <p style="color:var(--muted); margin-bottom:40px;">Track your journey to greatness. Every stay brings you closer to the next tier of luxury.</p>

    <div class="loyalty-grid" style="display:grid; grid-template-columns: 1fr 350px; gap:40px; align-items:start;">
      <div>
        <div class="tier-card" style="--tier-from:<?= $tierInfo['color'] ?>;--tier-to:<?= $nextTier?$nextTier['color']:$tierInfo['color'] ?>; background:linear-gradient(135deg, var(--tier-from), var(--tier-to)); padding:50px; border-radius:30px; color:#000; position:relative; overflow:hidden; box-shadow:0 15px 45px rgba(0,0,0,0.1);">
          <div style="position:absolute; right:-20px; top:-20px; font-size:180px; opacity:0.05; font-family:var(--serif);">✦</div>
          <div class="tier-badge" style="background:rgba(0,0,0,0.1); display:inline-block; padding:6px 18px; border-radius:30px; font-size:12px; font-weight:700; letter-spacing:2px; text-transform:uppercase; margin-bottom:20px;"><?= $tierInfo['emoji'] ?> <?= $tierInfo['name'] ?> MEMBER</div>
          <div class="points-big" style="font-family:var(--serif); font-size:72px; font-weight:400; line-height:1;"><?= number_format($balance) ?></div>
          <div style="font-size:16px; font-weight:600; opacity:0.7; margin-top:10px;">Available Points</div>
          
          <?php if ($nextTier): ?>
          <div style="margin-top:40px;">
            <div style="display:flex; justify-content:space-between; font-size:13px; font-weight:700; opacity:0.8; margin-bottom:10px;">
              <span><?= number_format($ptsToNext) ?> points to <?= $nextTier['name'] ?></span>
              <span><?= $progressPct ?>%</span>
            </div>
            <div class="progress-wrap" style="height:10px; background:rgba(0,0,0,0.1); border-radius:5px; overflow:hidden;">
              <div class="progress-fill" style="width:<?= $progressPct ?>%; height:100%; background:rgba(0,0,0,0.2); transition:width 1s ease;"></div>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <div class="card" style="margin-top:40px;">
          <div class="card-header"><div class="card-title">Points History</div></div>
          <div style="padding:10px 0;">
            <?php if (empty($txs)): ?>
            <div style="text-align:center;padding:50px;color:var(--muted)">No transactions recorded yet</div>
            <?php else: ?>
            <?php foreach ($txs as $tx):
              $isPos = $tx['points'] > 0;
            ?>
            <div class="tx-item" style="display:flex; align-items:center; justify-content:space-between; padding:20px; border-bottom:1px solid var(--bdr2);">
              <div style="display:flex; align-items:center; gap:20px;">
                <div style="width:45px; height:45px; border-radius:15px; background:var(--card2); display:flex; align-items:center; justify-content:center; font-size:20px;">
                  <?= ['earn'=>'🏨','bonus'=>'🌟','redeem'=>'💸'][$tx['type']] ?? '✨' ?>
                </div>
                <div>
                  <div style="font-weight:600; font-size:15px;"><?= htmlspecialchars($tx['description'] ?? ucfirst($tx['type'])) ?></div>
                  <div style="font-size:12px; color:var(--muted)"><?= date('d M Y', strtotime($tx['created_at'])) ?></div>
                </div>
              </div>
              <div style="font-family:var(--serif); font-size:18px; font-weight:700; color:<?= $isPos?'#22c55e':'#ef4444' ?>;">
                <?= $isPos?'+':'' ?><?= number_format($tx['points']) ?>
              </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div>
        <div class="card" style="margin-bottom:30px; border-color:var(--gold); background:var(--gold-dim);">
          <div class="card-header" style="border-bottom-color:rgba(192,155,91,0.2);"><div class="card-title" style="color:var(--gold)">Reward Balance</div></div>
          <div class="card-body" style="text-align:center; padding:40px 20px;">
            <div style="font-size:13px; color:var(--muted); text-transform:uppercase; letter-spacing:2px; margin-bottom:10px;">CASH VALUE</div>
            <div style="font-family:var(--serif); font-size:48px; color:var(--gold);">$<?= number_format($balance/100, 2) ?></div>
            <p style="font-size:14px; color:var(--text2); margin:20px 0 30px;">Redeem your points for future stays or room enhancements.</p>
            <a href="<?= $B ?>/rooms.php" class="btn btn-gold btn-block">Use My Points</a>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title"><?= $tierInfo['name'] ?> Perks</div></div>
          <div class="card-body">
            <?php foreach ($tierInfo['perks'] as $perk): ?>
            <div style="display:flex; gap:12px; margin-bottom:15px; align-items:flex-start;">
              <i class="fas fa-check-circle" style="color:var(--gold); margin-top:3px;"></i>
              <span style="font-size:14px; color:var(--text2); line-height:1.4;"><?= $perk ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<style>
@media(max-width:1200px){.inner-layout{grid-template-columns:1fr!important}}
</style>
<?php require __DIR__ . '/footer.php'; ?>
