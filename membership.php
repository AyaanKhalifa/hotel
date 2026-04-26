<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/lang.php';
require_once __DIR__.'/includes/db.php';
$pageTitle = t('membership').' — Royale Vista';
$memberships = $pdo->query("SELECT m.*,GROUP_CONCAT(mf.feature ORDER BY mf.sort_order SEPARATOR '||') as features,GROUP_CONCAT(mf.icon ORDER BY mf.sort_order SEPARATOR '||') as ficons,GROUP_CONCAT(mf.is_highlight ORDER BY mf.sort_order SEPARATOR '||') as fhighlights FROM memberships m LEFT JOIN membership_features mf ON mf.membership_id=m.id GROUP BY m.id ORDER BY m.sort_order")->fetchAll();
$userMembership = null;
if(isLoggedIn()){
  $q=$pdo->prepare("SELECT um.*,m.name,m.discount_pct,m.gradient_from,m.gradient_to,m.icon FROM user_memberships um JOIN memberships m ON um.membership_id=m.id WHERE um.user_id=? AND um.status='active' ORDER BY m.discount_pct DESC LIMIT 1");
  $q->execute([$_SESSION['user_id']]);$userMembership=$q->fetch();
}
require __DIR__.'/header.php';
?>
<div class="membership-page" style="padding-top:120px; padding-bottom:80px;">
  <div class="container text-center">
    <div class="section-label">Exclusivity</div>
    <h1 style="font-family:var(--serif);font-size:42px;margin-bottom:12px">Membership Tiers</h1>
    <p style="color:var(--muted);margin-bottom:50px;max-width:700px;margin-left:auto;margin-right:auto;">Elevate your stay at Royale Vista. Choose a plan that reflects your lifestyle and enjoy curated privileges globally.</p>

    <?php if($userMembership): ?>
    <div class="current-membership-banner" style="background:linear-gradient(135deg,<?=$userMembership['gradient_from']?>,<?=$userMembership['gradient_to']?>);border-radius:24px;padding:40px;color:#000;margin-bottom:50px;display:flex;align-items:center;justify-content:space-between;text-align:left;box-shadow:0 20px 50px rgba(0,0,0,.15)">
      <div style="display:flex;align-items:center;gap:30px">
        <span style="font-size:64px"><?=$userMembership['icon']?></span>
        <div>
          <div style="font-size:12px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;opacity:.6">Active Membership</div>
          <div style="font-family:var(--serif);font-size:36px;font-weight:700"><?=htmlspecialchars($userMembership['name'])?> Elite</div>
          <div style="font-size:15px;opacity:.8;margin-top:4px">Your exclusive benefits are active across all Royale Vista properties.</div>
        </div>
      </div>
      <div style="text-align:right">
        <div style="font-size:14px;opacity:.7">Member ID</div>
        <div style="font-family:monospace;font-size:20px;font-weight:700"><?=htmlspecialchars($userMembership['member_number']??'RV-'.rand(1000,9999))?></div>
      </div>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:32px">
      <?php foreach($memberships as $m):
        $feats = $m['features'] ? array_map(null, explode('||',$m['features']), explode('||',$m['ficons']), explode('||',$m['fhighlights'])) : [];
        $isCurrent = $userMembership && $userMembership['membership_id'] == $m['id'];
      ?>
      <div class="mem-card" style="<?= $m['is_popular'] ? 'border-color:var(--gold); transform:scale(1.05); z-index:2;' : '' ?>">
        <?php if($m['is_popular']): ?><div style="position:absolute;top:15px;right:15px;background:var(--gold);color:#000;font-size:11px;padding:5px 15px;border-radius:20px;font-weight:700;letter-spacing:1px;z-index:3">MOST LUXURIOUS</div><?php endif; ?>
        
        <div class="mem-card-hd" style="background:linear-gradient(135deg,<?=$m['gradient_from']?>,<?=$m['gradient_to']?>)">
          <div style="font-size:56px;margin-bottom:15px"><?=$m['icon']?></div>
          <div style="font-family:var(--serif);font-size:32px;font-weight:700"><?=htmlspecialchars($m['name'])?></div>
          <div style="font-family:var(--serif);font-size:42px;font-weight:400;margin-top:15px"><?=formatPrice($m['price_usd'])?> <span style="font-size:16px;opacity:.7">/year</span></div>
          <div style="background:rgba(0,0,0,.15);padding:8px 20px;border-radius:30px;font-size:13px;font-weight:700;display:inline-block;margin-top:20px"><?= $m['discount_pct'] ?>% Exclusive Discount</div>
        </div>

        <div class="mem-card-bd" style="padding:40px 30px;">
          <div style="margin-bottom:32px; text-align:left;">
            <?php foreach($feats as [$feat,$ficon,$fhl]): if(!$feat)continue; ?>
            <div class="mem-feat" style="<?= $fhl?'font-weight:700;color:var(--gold)':'' ?>; margin-bottom:15px; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:10px;">
              <i class="<?= $ficon ?: 'fas fa-check-circle' ?>" style="margin-right:12px;"></i>
              <span><?= htmlspecialchars($feat) ?></span>
            </div>
            <?php endforeach; ?>
          </div>

          <?php if($isCurrent): ?>
          <button class="btn btn-gold btn-block" disabled style="opacity:0.6; height:50px;"><i class="fas fa-check"></i> Current Selection</button>
          <?php else: ?>
          <a href="<?=isLoggedIn()?$B.'/checkout-membership.php?id='.$m['id'] : $B.'/login.php'?>" class="btn btn-gold btn-block" style="height:50px; display:flex; align-items:center; justify-content:center;">
            <?= isLoggedIn() ? ($userMembership ? 'Upgrade Membership' : 'Join Royale Elite') : 'Login to View' ?>
          </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php require __DIR__.'/footer.php'; ?>
