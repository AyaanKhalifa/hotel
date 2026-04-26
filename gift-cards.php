<?php
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/lang.php';
require_once __DIR__.'/includes/db.php';
$pageTitle='Gift Cards — Royale Vista';
$success = false;
if (isset($_GET['success']) && isset($_SESSION['gc_success'])) {
    $success = $_SESSION['gc_success'];
    unset($_SESSION['gc_success']);
}
$error = '';
require __DIR__.'/header.php';
?>
<div style="padding-top:70px">
  <div style="height:55vh;min-height:400px;position:relative;display:flex;align-items:flex-end;overflow:hidden">
    <div style="position:absolute;inset:0;background:url('https://images.unsplash.com/photo-1606103836293-0a058e2e1e5e?w=1600&q=80') center/cover"></div>
    <div style="position:absolute;inset:0;background:linear-gradient(to bottom,rgba(0,0,0,.1),rgba(0,0,0,.7))"></div>
    <div style="position:relative;z-index:1;width:100%;padding:60px 0;color:#fff">
      <div class="container">
        <div class="lx-eyebrow" style="justify-content:flex-start;color:rgba(255,255,255,.5)">The Perfect Gift</div>
        <h1 style="font-family:var(--serif);font-size:clamp(36px,5vw,72px);font-weight:300;color:#fff;line-height:1.1">
          Gift <em style="color:var(--gold)">Cards</em>
        </h1>
        <p style="color:rgba(255,255,255,.75);font-size:15px;max-width:480px;line-height:1.8;margin-top:14px">Give the gift of extraordinary luxury. Royale Vista gift cards are redeemable at all eight properties worldwide.</p>
      </div>
    </div>
  </div>
  <section class="section"><div class="container">
    <?php if($success): ?>
    <div style="text-align:center;max-width:520px;margin:0 auto;padding:60px 0">
      <div style="font-size:56px;margin-bottom:20px">🎁</div>
      <h2 style="font-family:var(--serif);font-size:36px;font-weight:300;margin-bottom:14px">Gift Card Purchased!</h2>
      <div style="background:linear-gradient(135deg,var(--charcoal),var(--charcoal2));color:#fff;border-radius:16px;padding:28px;margin:20px 0">
        <div style="font-family:var(--cinzel);font-size:9px;letter-spacing:2px;color:rgba(255,255,255,.5);margin-bottom:8px">Gift Card Code</div>
        <div style="font-family:var(--serif);font-size:28px;color:var(--gold);letter-spacing:3px"><?=htmlspecialchars($success["code"])?></div>
        <div style="font-size:32px;color:#fff;margin-top:10px">$<?=number_format($success["amount"],2)?></div>
        <div style="font-size:12px;color:rgba(255,255,255,.4);margin-top:6px">Valid until <?=date("d M Y",strtotime($success["expires"]))?></div>
      </div>
      <p style="color:var(--text2);font-size:14px">The gift card code has been sent to <?=htmlspecialchars($success["for"])?> and is ready to use at checkout.</p>
      <a href="<?=$B?>/gift-cards.php" class="btn btn-outline" style="margin-top:20px">Buy Another</a>
    </div>
    <?php else: ?>
    <div class="grid-2" style="gap:56px;align-items:start">
      <div>
        <div class="lx-eyebrow" style="justify-content:flex-start">Purchase a Gift Card</div>
        <h2 style="font-family:var(--serif);font-size:clamp(26px,3vw,42px);font-weight:300;margin-bottom:24px">A Gift They <em style="color:var(--gold)">Will Never Forget</em></h2>
        <?php if($error): ?><div class="alert alert-error"><?=htmlspecialchars($error)?></div><?php endif; ?>
        <form method="POST" action="<?= $B ?>/checkout-giftcard.php" class="lx-card"><div class="lx-card-bd">
          <input type="hidden" name="purchase_gc" value="1">
          <div class="form-group"><label class="form-label">Gift Card Value (USD)</label>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px">
              <?php foreach([250,500,1000,2500,5000] as $v): ?>
              <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('gcAmt').value=<?=$v?>"><?=$v<1000?'$'.$v:'$'.($v/1000).'K'?></button>
              <?php endforeach; ?>
            </div>
            <input class="form-control" type="number" name="amount" id="gcAmt" placeholder="Custom amount (min $100)" min="100" required>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Recipient Name</label><input class="form-control" name="for_name" placeholder="Their full name"></div>
            <div class="form-group"><label class="form-label">Recipient Email *</label><input class="form-control" type="email" name="for_email" required placeholder="their@email.com"></div>
          </div>
          <div class="form-group"><label class="form-label">Personal Message</label><textarea class="form-control" name="message" rows="3" placeholder="Write a heartfelt message…"></textarea></div>
          <button type="submit" class="btn btn-gold btn-block btn-lg"><i class="fas fa-gift"></i> Purchase Gift Card</button>
        </div></form>
      </div>
      <div>
        <div style="background:linear-gradient(135deg,var(--charcoal),var(--charcoal2));border-radius:16px;padding:36px;color:#fff;margin-bottom:24px;position:relative;overflow:hidden">
          <div style="position:absolute;right:20px;top:20px;font-size:80px;opacity:.06">✦</div>
          <div style="font-family:var(--cinzel);font-size:10px;letter-spacing:3px;color:var(--gold);text-transform:uppercase;margin-bottom:16px">Royale Vista</div>
          <div style="font-family:var(--serif);font-size:42px;font-weight:300;color:#fff">Gift Card</div>
          <div style="font-family:var(--cinzel);font-size:22px;color:var(--gold);margin-top:16px">$• • • •</div>
          <div style="font-size:12px;color:rgba(255,255,255,.4);margin-top:28px;border-top:1px solid rgba(255,255,255,.1);padding-top:14px">Valid at all Royale Vista properties worldwide</div>
        </div>
        <?php foreach([['🌍','Global Redemption','Use at any of our 8 worldwide properties'],['♾️','No Expiry Worries','Valid for 2 full years from purchase date'],['🎁','Instant Delivery','Recipient gets their code by email immediately'],['💎','Full Flexibility','Applies to rooms, dining, spa and events']] as [$ic,$t,$d]): ?>
        <div style="display:flex;gap:12px;margin-bottom:16px"><div style="font-size:22px"><?=$ic?></div><div><div style="font-size:14px;font-weight:600"><?=$t?></div><div style="font-size:13px;color:var(--text2)"><?=$d?></div></div></div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div></section>
</div>
<?php require __DIR__.'/footer.php'; ?>