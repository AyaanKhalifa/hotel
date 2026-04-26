<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/lang.php';
require_once __DIR__.'/includes/db.php';
$pageTitle = t('offers').' — Royale Vista';
$offers = $pdo->query("SELECT * FROM offers WHERE is_active=1 AND (valid_to IS NULL OR valid_to>=CURDATE()) ORDER BY value DESC")->fetchAll();
require __DIR__.'/header.php';
?>
<div style="padding-top:90px">
<section class="section">
<div class="container">
  <div style="text-align:center;margin-bottom:48px">
    <div class="section-label" style="justify-content:center">Limited Time</div>
    <h1 class="section-heading"><?= t('offers','Special Offers') ?></h1>
    <p class="section-sub" style="margin:0 auto">Exclusive deals for discerning travellers. Book before they expire.</p>
  </div>
  <div class="grid-3">
  <?php
  $offerIcons=['WELCOME10'=>'🎁','SUMMER20'=>'☀️','HOLIDAY25'=>'🎄','WEEKEND15'=>'🌅','FAMILY30'=>'👨‍👩‍👧','FLAT50'=>'💰'];
  $offerBg=['WELCOME10'=>'#d4af37,#b89628','SUMMER20'=>'#f59e0b,#d97706','HOLIDAY25'=>'#8b5cf6,#7c3aed','WEEKEND15'=>'#3b82f6,#2563eb','FAMILY30'=>'#22c55e,#15803d','FLAT50'=>'#ef4444,#dc2626'];
  foreach($offers as $offer):
    $bg=$offerBg[$offer['code']]??'#d4af37,#b89628';
    $icon=$offerIcons[$offer['code']]??'🏷️';
    $daysLeft=null;
    if($offer['valid_to']) $daysLeft=max(0,(int)floor((strtotime($offer['valid_to'])-time())/86400));
  ?>
  <div style="background:var(--card);border:1px solid var(--bdr2);border-radius:var(--radius-lg);overflow:hidden;transition:all var(--t)" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='var(--shadow)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
    <div style="background:linear-gradient(135deg,<?=$bg?>);padding:28px 24px;text-align:center">
      <div style="font-size:44px;margin-bottom:10px"><?=$icon?></div>
      <div style="font-family:var(--serif);font-size:48px;font-weight:300;color:#000;line-height:1"><?=$offer['value']?><span style="font-size:20px"><?=$offer['type']==='percent'?'%':'$'?> off</span></div>
    </div>
    <div style="padding:22px 24px">
      <div style="font-family:var(--serif);font-size:20px;margin-bottom:8px"><?=htmlspecialchars($offer['description'])?></div>
      <?php if($daysLeft!==null): ?>
      <div style="font-size:12px;color:<?=$daysLeft<7?'var(--red)':'var(--muted)'?>;margin-bottom:14px">
        <?=$daysLeft>0?"⏱ Expires in $daysLeft day".($daysLeft!=1?'s':''):'⚡ Last day!'?>
      </div>
      <?php endif; ?>
      <div style="background:var(--card2);border:1px dashed var(--border);border-radius:8px;padding:12px;text-align:center;cursor:pointer;transition:all var(--t)" onclick="copyCode('<?= htmlspecialchars($offer['code'],ENT_QUOTES) ?>')" title="Click to copy">
        <div style="font-size:10px;color:var(--muted);letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px">Promo Code</div>
        <div style="font-family:monospace;font-size:20px;font-weight:700;color:var(--gold);letter-spacing:3px"><?=htmlspecialchars($offer['code'])?></div>
        <div style="font-size:11px;color:var(--muted);margin-top:4px">Click to copy</div>
      </div>
      <a href="<?=$B?>/rooms.php" class="btn btn-gold btn-block" style="margin-top:14px"><i class="fas fa-bed"></i> Book Now</a>
    </div>
  </div>
  <?php endforeach; ?>
  </div>
</div>
</section>
</div>
<script>
function copyCode(code){
  navigator.clipboard.writeText(code).then(()=>toast('Code "'+code+'" copied! Use it at checkout ✓','success'));
}
</script>
<?php require __DIR__.'/footer.php'; ?>
