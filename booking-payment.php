<?php
/**
 * ROYALE VISTA — Booking Step 3: Payment
 */
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$B = BASE;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: $B/rooms.php"); exit; }

// Collect from POST
$roomId     = (int)($_POST['room_id']   ?? 0);
$qty        = max(1, (int)($_POST['qty'] ?? 1));
$ci         = clean($_POST['ci']  ?? '');
$co         = clean($_POST['co']  ?? '');
$guestName  = clean($_POST['guest_name']  ?? '');
$guestEmail = clean($_POST['guest_email'] ?? '');
$guestPhone = clean($_POST['guest_phone'] ?? '');
$adults     = max(1, (int)($_POST['adults']   ?? 2));
$children   = max(0, (int)($_POST['children'] ?? 0));
$specialReq = clean($_POST['special_req'] ?? '');
$nationality= clean($_POST['nationality'] ?? '');
$offerCode  = strtoupper(clean($_POST['offer_code'] ?? ''));
$redeemPts  = max(0, min(5000, (int)($_POST['redeem_pts'] ?? 0)));

if (!$roomId || !$guestName || !$guestEmail || !$ci || !$co) {
    header("Location: $B/rooms.php"); exit;
}

// Load room
$rtQ = $pdo->prepare("SELECT rt.*, (SELECT image_url FROM room_images WHERE room_type_id=rt.id AND is_primary=1 LIMIT 1) img FROM room_types rt WHERE rt.id=? LIMIT 1");
$rtQ->execute([$roomId]); $room = $rtQ->fetch();
if (!$room) { header("Location: $B/rooms.php"); exit; }

$nights  = max(1, nightsBetween($ci, $co));
$sub     = $room['price_usd'] * $qty * $nights;
$taxRate = 0.18;

// Membership
$memDisc = 0; $memName = ''; $memNumber = '';
$msQ = $pdo->prepare("SELECT um.member_number,m.name,m.discount_pct FROM user_memberships um JOIN memberships m ON um.membership_id=m.id WHERE um.user_id=? AND um.status='active' ORDER BY m.discount_pct DESC LIMIT 1");
$msQ->execute([$_SESSION['user_id']]); $mem = $msQ->fetch();
if ($mem) { $memDisc = round($sub * ($mem['discount_pct']/100), 2); $memName = $mem['name']; $memNumber = $mem['member_number']; }

// Offer
$offerDisc = 0; $offerValid = false;
if ($offerCode) {
    $ofQ = $pdo->prepare("SELECT * FROM offers WHERE code=? AND is_active=1 AND (valid_from IS NULL OR valid_from<=CURDATE()) AND (valid_to IS NULL OR valid_to>=CURDATE()) LIMIT 1");
    $ofQ->execute([$offerCode]); $offer = $ofQ->fetch();
    if ($offer) { $offerDisc = $offer['type']==='percent' ? round($sub*($offer['value']/100),2) : min((float)$offer['value'],$sub); $offerValid = true; }
}

// Loyalty
$loyaltyDisc = floor($redeemPts / 100);
$totalDisc   = $memDisc + $offerDisc + $loyaltyDisc;
$afterDisc   = max(0, $sub - $totalDisc);
$taxes       = round($afterDisc * $taxRate, 2);
$finalPrice  = round($afterDisc + $taxes, 2);
$earnPts     = (int)round($finalPrice * 10);

// Save to session
$_SESSION['bk_pay'] = [
    'room_id'    => $roomId,   'room_name'  => $room['name'],
    'price_usd'  => (float)$room['price_usd'], 'qty' => $qty,
    'ci'         => $ci,       'co'         => $co,       'nights'     => $nights,
    'guest_name' => $guestName,'guest_email'=> $guestEmail,'guest_phone'=> $guestPhone,
    'adults'     => $adults,   'children'   => $children,  'special_req'=> $specialReq,
    'nationality'=> $nationality,
    'offer_code' => $offerValid ? $offerCode : '',
    'redeem_pts' => $redeemPts,
    'sub'        => $sub,      'mem_disc'   => $memDisc,   'offer_disc' => $offerDisc,
    'loyalty_disc'=> $loyaltyDisc,'taxes'   => $taxes,     'final'      => $finalPrice,
    'mem_name'   => $memName,  'mem_number' => $memNumber,
    'earn_pts'   => $earnPts,  'img'        => $room['img'],
];

$pageTitle = 'Payment — Royale Vista';
require __DIR__ . '/header.php';
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
<style>
.bk-page { padding-top: 88px; min-height: 100vh; }
.bk-wrap  { max-width: 1080px; margin: 0 auto; padding: 36px 24px 80px; display: grid; grid-template-columns: 1fr 360px; gap: 28px; }
@media(max-width:840px) { .bk-wrap { grid-template-columns: 1fr; } }
/* Steps */
.bk-steps { display:flex;align-items:center;justify-content:center;padding:24px 0 32px;gap:0; }
.bk-stp   { display:flex;align-items:center; }
.bk-sn    { width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--cinzel);font-size:12px;border:2px solid var(--border);color:var(--muted);background:var(--card);transition:all .3s; }
.bk-stp.done .bk-sn { background:var(--green);border-color:var(--green);color:#fff; }
.bk-stp.now  .bk-sn { background:var(--gold);border-color:var(--gold);color:#fff; }
.bk-sl    { font-size:11px;color:var(--muted);margin-left:7px;white-space:nowrap; }
.bk-stp.now  .bk-sl { color:var(--text);font-weight:600; }
.bk-stp.done .bk-sl { color:var(--green); }
.bk-line  { width:56px;height:1px;background:var(--border);margin:0 8px; }
@media(max-width:480px) { .bk-sl{display:none} .bk-line{width:28px} }
/* Card */
.bk-card  { background:var(--card);border:1px solid var(--bdr2);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:18px; }
.bk-card-h { padding:16px 22px;border-bottom:1px solid var(--bdr2);background:var(--card2);display:flex;align-items:center;gap:10px; }
.bk-card-h .ico { width:34px;height:34px;border-radius:8px;background:var(--gold-dim);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:14px;flex-shrink:0; }
.bk-card-h h3 { font-family:var(--serif);font-size:18px;font-weight:400; }
.bk-card-b { padding:20px 22px; }
/* Pay method */
.pm        { display:flex;align-items:center;gap:14px;padding:15px 20px;border:2px solid var(--bdr2);cursor:pointer;transition:all .2s; }
.pm:first-child { border-radius:var(--radius-lg) var(--radius-lg) 0 0; }
.pm:last-child  { border-radius:0 0 var(--radius-lg) var(--radius-lg); }
.pm:hover  { border-color:var(--border); }
.pm.on     { border-color:var(--gold);background:var(--gold-dim); }
.pm-radio  { width:20px;height:20px;border-radius:50%;border:2px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .2s; }
.pm.on .pm-radio { border-color:var(--gold); }
.pm.on .pm-radio::after { content:'';width:8px;height:8px;border-radius:50%;background:var(--gold);display:block; }
.pm-ic     { font-size:26px; }
.pm-n      { font-size:15px;font-weight:600; }
.pm-d      { font-size:12px;color:var(--muted);margin-top:2px; }
.pm-extra  { border:2px solid var(--gold);border-top:none;border-radius:0 0 var(--radius-lg) var(--radius-lg);padding:18px 20px;background:var(--card2);display:none; }
.pm-extra.show { display:block; }
/* Fields */
.fld    { display:flex;flex-direction:column;gap:5px;margin-bottom:14px; }
.fld label { font-family:var(--cinzel);font-size:9px;letter-spacing:2px;text-transform:uppercase;color:var(--gold);font-weight:500; }
.fld input { background:var(--input);border:1px solid var(--border);border-radius:var(--radius);color:var(--text);font-family:var(--sans);font-size:14px;padding:11px 14px;outline:none;width:100%;transition:border-color .2s; }
.fld input:focus { border-color:var(--gold);box-shadow:0 0 0 3px rgba(192,155,91,.08); }
.fld input::placeholder { color:var(--muted); }
.frow { display:grid;grid-template-columns:1fr 1fr;gap:14px; }
@media(max-width:480px) { .frow{grid-template-columns:1fr} }
/* Summary */
.sum-card { background:var(--card);border:1px solid var(--bdr2);border-radius:var(--radius-lg);overflow:hidden;position:sticky;top:82px; }
.sum-img  { width:100%;height:160px;object-fit:cover;display:block; }
.sum-body { padding:18px 20px; }
.sum-row  { display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--bdr2);font-size:13.5px; }
.sum-row:last-child { border-bottom:none; }
.sum-row .l { color:var(--text2); }
.sum-disc   { color:var(--green) !important; }
.sum-total .l { font-size:15px;font-weight:700; }
.sum-total .v { font-family:var(--serif);font-size:26px;color:var(--gold); }
/* CTA */
.bk-cta { background:var(--gold);color:#fff;border:none;border-radius:var(--radius);padding:16px;width:100%;font-family:var(--cinzel);font-size:10px;letter-spacing:2px;text-transform:uppercase;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px;transition:all .25s; }
.bk-cta:hover:not(:disabled) { background:var(--gold-dk);transform:translateY(-1px);box-shadow:0 6px 20px rgba(192,155,91,.35); }
.bk-cta:disabled { opacity:.5;cursor:not-allowed; }
</style>

<div class="bk-page">
  <div class="container" style="padding-top:0">
    <div class="bk-steps" id="bkSteps">
      <div class="bk-stp done"><div class="bk-sn"><i class="fas fa-check" style="font-size:12px"></i></div><span class="bk-sl">Select Room</span></div>
      <div class="bk-line"></div>
      <div class="bk-stp done"><div class="bk-sn"><i class="fas fa-check" style="font-size:12px"></i></div><span class="bk-sl">Your Details</span></div>
      <div class="bk-line"></div>
      <div class="bk-stp now"><div class="bk-sn">3</div><span class="bk-sl">Payment</span></div>
      <div class="bk-line"></div>
      <div class="bk-stp"><div class="bk-sn">4</div><span class="bk-sl">Confirmed</span></div>
    </div>
  </div>

  <div class="bk-wrap">
    <div>
      <form method="POST" action="<?= $B ?>/booking-confirm.php" id="payForm" onsubmit="return doSubmit(event)">
        <input type="hidden" name="pay_method" id="hdnPay" value="hotel">
        <input type="hidden" name="gc_codes" id="hdnGcCodes" value="[]">

        <!-- Payment Methods -->
        <div class="bk-card" id="pmCard">
          <div class="bk-card-h"><div class="ico"><i class="fas fa-credit-card"></i></div><div><h3>Payment Method</h3><p>Choose how to pay for your stay</p></div></div>
          <div class="bk-card-b" style="padding:0">
            <div id="pm-hotel" class="pm on" onclick="setPay('hotel')">
              <div class="pm-radio"></div>
              <div class="pm-ic">🏨</div>
              <div><div class="pm-n">Pay at Hotel</div><div class="pm-d">Reserve now, no charge today. Pay on arrival.</div></div>
              <div style="margin-left:auto;background:var(--green-bg);color:var(--green);font-size:10px;font-weight:700;padding:3px 9px;border-radius:10px">Free</div>
            </div>
            <div id="pm-card" class="pm" onclick="setPay('card')">
              <div class="pm-radio"></div>
              <div class="pm-ic">💳</div>
              <div><div class="pm-n">Credit / Debit Card</div><div class="pm-d">Visa, Mastercard, American Express, RuPay</div></div>
            </div>
            <div id="card-extra" class="pm-extra">
              <div class="fld"><label>Card Number</label><input id="cNum" placeholder="1234 5678 9012 3456" maxlength="19" oninput="let v=this.value.replace(/\D/g,'');this.value=v.replace(/(.{4})/g,'$1 ').trim()"></div>
              <div class="frow">
                <div class="fld"><label>Expiry</label><input id="cExp" placeholder="MM / YY" maxlength="7" oninput="let v=this.value.replace(/\D/g,'');if(v.length>2)v=v.slice(0,2)+' / '+v.slice(2,4);this.value=v"></div>
                <div class="fld"><label>CVV</label><input id="cCvv" type="password" placeholder="•••" maxlength="4"></div>
              </div>
              <div class="fld" style="margin-bottom:0"><label>Name on Card</label><input id="cName" placeholder="As shown on card"></div>
            </div>
            <div id="pm-upi" class="pm" onclick="setPay('upi')">
              <div class="pm-radio"></div>
              <div class="pm-ic">📱</div>
              <div><div class="pm-n">UPI / Net Banking</div><div class="pm-d">Google Pay, PhonePe, Paytm, BHIM</div></div>
            </div>
            <div id="upi-extra" class="pm-extra">
              <div style="text-align:center;padding:10px 0 14px">
                <div style="font-size:48px;margin-bottom:8px">📱</div>
                <div style="font-family:var(--cinzel);font-size:9px;color:var(--gold);letter-spacing:2px;text-transform:uppercase">UPI ID</div>
                <div style="font-size:15px;font-weight:600;margin-top:4px">royalevista@paytm</div>
              </div>
              <div class="fld" style="margin-bottom:0"><label>Transaction Reference *</label><input id="upiRef" placeholder="Enter UPI transaction ID after payment"></div>
            </div>
          </div>
        </div>

        <!-- Booking Summary confirmation -->
        <div class="bk-card" id="sumConf">
          <div class="bk-card-h"><div class="ico"><i class="fas fa-user-check"></i></div><div><h3>Confirm Details</h3><p>Review before completing</p></div></div>
          <div class="bk-card-b">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;font-size:13.5px">
              <div><div style="font-family:var(--cinzel);font-size:9px;color:var(--gold);letter-spacing:2px;text-transform:uppercase;margin-bottom:4px">Guest</div><div style="font-weight:600"><?= htmlspecialchars($guestName) ?></div><div style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($guestEmail) ?></div></div>
              <div><div style="font-family:var(--cinzel);font-size:9px;color:var(--gold);letter-spacing:2px;text-transform:uppercase;margin-bottom:4px">Room</div><div style="font-weight:600"><?= htmlspecialchars($room['name']) ?></div><div style="font-size:12px;color:var(--muted)"><?= $qty ?> rm · <?= $adults ?> adult<?= $adults>1?'s':'' ?><?= $children>0?' · '.$children.' child'.($children>1?'ren':''):'' ?></div></div>
              <div><div style="font-family:var(--cinzel);font-size:9px;color:var(--gold);letter-spacing:2px;text-transform:uppercase;margin-bottom:4px">Check-In</div><div style="font-weight:600"><?= date('D d M Y', strtotime($ci)) ?></div><div style="font-size:12px;color:var(--muted)">From 2:00 PM</div></div>
              <div><div style="font-family:var(--cinzel);font-size:9px;color:var(--gold);letter-spacing:2px;text-transform:uppercase;margin-bottom:4px">Check-Out</div><div style="font-weight:600"><?= date('D d M Y', strtotime($co)) ?></div><div style="font-size:12px;color:var(--muted)">By 12:00 PM</div></div>
            </div>
            <?php if ($specialReq): ?>
            <div style="margin-top:14px;padding:12px;background:var(--card2);border-radius:8px;border-left:3px solid var(--gold);font-size:13px;color:var(--text2)"><i class="fas fa-comment" style="color:var(--gold);margin-right:6px"></i><?= htmlspecialchars($specialReq) ?></div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Gift Card -->
        <div class="bk-card" id="gcCard">
          <div class="bk-card-h"><div class="ico"><i class="fas fa-gift"></i></div><div><h3>Gift Card(s)</h3><p>Redeem one or more Royale Vista gift cards</p></div></div>
          <div class="bk-card-b" style="display:flex;gap:10px">
            <input type="text" id="gcInp" class="fld" style="flex:1;background:var(--input);border:1px solid var(--border);border-radius:var(--radius);padding:11px 14px;color:var(--text);font-family:var(--sans);font-size:14px;outline:none" placeholder="Enter Gift Card Code" style="text-transform:uppercase">
            <button type="button" class="btn btn-outline" id="gcBtn" onclick="applyGc()" style="padding:11px 20px">Apply</button>
          </div>
          <div id="gcList" style="padding:10px 22px 0;display:flex;flex-wrap:wrap;gap:8px"></div>
          <div id="gcMsg" style="padding:0 22px 16px;font-size:13px"></div>
        </div>

        <!-- Terms -->
        <div style="display:flex;align-items:flex-start;gap:12px;padding:16px 0 18px;border-top:1px solid var(--bdr2)">
          <input type="checkbox" id="terms" required style="width:18px;height:18px;accent-color:var(--gold);margin-top:2px;flex-shrink:0;cursor:pointer">
          <label for="terms" style="font-size:13px;color:var(--text2);cursor:pointer;line-height:1.65">
            I agree to the <a href="<?= $B ?>/terms.php" target="_blank" style="color:var(--gold)">Terms & Conditions</a> and <a href="<?= $B ?>/privacy.php" target="_blank" style="color:var(--gold)">Privacy Policy</a>. I understand the cancellation policy.
          </label>
        </div>

        <button type="submit" class="bk-cta" id="ctaBtn">
          <i class="fas fa-lock" style="font-size:11px"></i>
          Confirm &amp; Pay <?= formatPrice($finalPrice) ?>
        </button>
        <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:12px;font-size:11px;color:var(--muted)">
          <i class="fas fa-shield-alt"></i> SSL Encrypted · PCI DSS Compliant
        </div>
        <div style="text-align:center;margin-top:12px">
          <a href="<?= $B ?>/booking-details.php?room=<?= $roomId ?>&qty=<?= $qty ?>&checkin=<?= urlencode($ci) ?>&checkout=<?= urlencode($co) ?>" style="font-size:12px;color:var(--muted)">← Back to Details</a>
        </div>
      </form>
    </div>

    <!-- Price Summary -->
    <div>
      <div class="sum-card">
        <?php if ($room['img']): ?>
        <img src="<?= htmlspecialchars($room['img']) ?>" alt="" class="sum-img" onerror="this.style.display='none'">
        <?php endif; ?>
        <div class="sum-body">
          <div style="font-family:var(--cinzel);font-size:9px;letter-spacing:2px;color:var(--gold);text-transform:uppercase;margin-bottom:10px">Price Breakdown</div>
          <div class="sum-row"><span class="l"><?= htmlspecialchars($room['name']) ?></span></div>
          <div class="sum-row"><span class="l"><?= $qty ?> rm × <?= $nights ?> nights × <?= formatPrice($room['price_usd']) ?></span><span><?= formatPrice($sub) ?></span></div>
          <?php if ($memDisc > 0): ?><div class="sum-row sum-disc"><span class="l"><i class="fas fa-crown" style="margin-right:4px"></i><?= htmlspecialchars($memName) ?></span><span>−<?= formatPrice($memDisc) ?></span></div><?php endif; ?>
          <?php if ($offerDisc > 0): ?><div class="sum-row sum-disc"><span class="l"><i class="fas fa-tag" style="margin-right:4px"></i><?= htmlspecialchars($offerCode) ?></span><span>−<?= formatPrice($offerDisc) ?></span></div><?php endif; ?>
          <?php if ($loyaltyDisc > 0): ?><div class="sum-row sum-disc"><span class="l"><i class="fas fa-coins" style="margin-right:4px"></i>Loyalty Points</span><span>−<?= formatPrice($loyaltyDisc) ?></span></div><?php endif; ?>
          <div id="sumGCRow" class="sum-row sum-disc" style="display:none"><span class="l"><i class="fas fa-gift" style="margin-right:4px"></i>Gift Card (<span id="sumGCName"></span>)</span><span id="sumGCAmt">−$0.00</span></div>
          <div class="sum-row"><span class="l">Tax (18% GST)</span><span><?= formatPrice($taxes) ?></span></div>
          <div class="sum-row sum-total"><span class="l">Total</span><span class="v" id="finalTotalUi"><?= formatPrice($finalPrice) ?></span></div>
          <?php if ($earnPts > 0): ?>
          <div style="margin-top:12px;background:var(--gold-dim);border:1px solid var(--border);border-radius:8px;padding:10px 13px;font-size:12.5px;display:flex;align-items:center;gap:8px">
            <i class="fas fa-coins" style="color:var(--gold)"></i> You'll earn <strong style="color:var(--gold)"><?= number_format($earnPts) ?> pts</strong> on this booking
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const BASE = '<?= $B ?>';
let curPay = 'hotel';
let gcVal = 0;
let appliedGiftCards = [];
let finalBase = <?= $finalPrice ?>;

function fmtPrice(amount) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: '<?= $_SESSION["currency"]??'USD' ?>' }).format(amount);
}

document.addEventListener('DOMContentLoaded', () => {
  anime({ targets: '.bk-steps .bk-stp', opacity:[0,1], translateY:[-10,0], duration:500, delay:anime.stagger(70), easing:'easeOutExpo' });
  anime({ targets: ['#pmCard','#sumConf'], opacity:[0,1], translateY:[20,0], duration:520, delay:anime.stagger(100,{start:300}), easing:'easeOutExpo' });
  anime({ targets: '.sum-card', opacity:[0,1], translateX:[14,0], duration:600, delay:400, easing:'easeOutExpo' });
});

function setPay(m) {
  curPay = m;
  document.getElementById('hdnPay').value = m;
  ['hotel','card','upi'].forEach(p => document.getElementById('pm-'+p).classList.toggle('on', p===m));
  document.getElementById('card-extra').classList.toggle('show', m==='card');
  document.getElementById('upi-extra').classList.toggle('show',  m==='upi');
  const extra = document.getElementById(m+'-extra');
  if (extra && extra.classList.contains('show')) {
    anime({ targets: extra, opacity:[0,1], height:['0px','auto'], duration:300, easing:'easeOutExpo' });
  }
}

async function applyGc() {
  const c = document.getElementById('gcInp').value.trim();
  const btn = document.getElementById('gcBtn');
  const msg = document.getElementById('gcMsg');
  if (!c) return;
  if (appliedGiftCards.some(g => g.code === c.toUpperCase())) {
    msg.innerHTML = `<span style="color:var(--red)"><i class="fas fa-exclamation-circle"></i> This gift card is already applied.</span>`;
    return;
  }
  btn.disabled=true; btn.innerHTML='<i class="fas fa-circle-notch fa-spin"></i>';
  try {
    const res = await fetch(`${BASE}/api/validate_gc.php?code=${c}`);
    const rx = await res.json();
    if (rx.success) {
      appliedGiftCards.push({ code: rx.code, balance: Number(rx.balance || 0) });
      recomputeGiftCards();
      msg.innerHTML = `<span style="color:var(--green)"><i class="fas fa-check"></i> Applied ${rx.code} (${fmtPrice(Number(rx.balance || 0))}).</span>`;
      document.getElementById('gcInp').value = '';
    } else {
      msg.innerHTML = `<span style="color:var(--red)"><i class="fas fa-exclamation-circle"></i> ${rx.error}</span>`;
    }
  } catch(e) { msg.innerHTML = 'Error checking card.'; }
  btn.disabled=false; btn.innerHTML='Apply';
}

function recomputeGiftCards() {
  let remaining = finalBase;
  gcVal = 0;
  for (let i = 0; i < appliedGiftCards.length; i++) {
    const canUse = Math.min(remaining, appliedGiftCards[i].balance);
    appliedGiftCards[i].use = Math.max(0, canUse);
    gcVal += appliedGiftCards[i].use;
    remaining = Math.max(0, remaining - appliedGiftCards[i].use);
  }
  const listEl = document.getElementById('gcList');
  if (listEl) {
    listEl.innerHTML = appliedGiftCards.map((g, idx) =>
      `<span style="background:var(--card2);border:1px solid var(--bdr2);border-radius:999px;padding:6px 10px;font-size:12px">
        <i class="fas fa-gift" style="color:var(--gold);margin-right:4px"></i>${g.code} · ${fmtPrice(g.use || 0)}
        <button type="button" onclick="removeGc(${idx})" style="margin-left:6px;border:none;background:none;color:var(--muted);cursor:pointer">×</button>
      </span>`
    ).join('');
  }
  document.getElementById('hdnGcCodes').value = JSON.stringify(appliedGiftCards.map(g => g.code));
  document.getElementById('sumGCRow').style.display = gcVal > 0 ? 'flex' : 'none';
  document.getElementById('sumGCName').textContent = appliedGiftCards.length > 1 ? `${appliedGiftCards.length} cards` : (appliedGiftCards[0]?.code || '');
  document.getElementById('sumGCAmt').textContent = '−' + fmtPrice(gcVal);
  document.getElementById('finalTotalUi').textContent = fmtPrice(Math.max(0, finalBase - gcVal));
  document.getElementById('ctaBtn').innerHTML = '<i class="fas fa-lock" style="font-size:11px"></i> Confirm & Pay ' + fmtPrice(Math.max(0, finalBase - gcVal));
}

function removeGc(idx) {
  appliedGiftCards.splice(idx, 1);
  recomputeGiftCards();
}

async function doSubmit(e) {
  e.preventDefault();
  if (!document.getElementById('terms').checked) {
    toast && toast('Please accept the Terms & Conditions', 'error'); return false;
  }
  if (curPay === 'card') {
    const n = document.getElementById('cNum').value.replace(/\s/g,'');
    const ex = document.getElementById('cExp').value;
    const cv = document.getElementById('cCvv').value;
    const nm = document.getElementById('cName').value.trim();
    if (n.length < 16 || !ex || cv.length < 3 || !nm) {
      toast && toast('Please complete all card details', 'error'); return false;
    }
  }
  if (curPay === 'upi' && !document.getElementById('upiRef').value.trim()) {
    toast && toast('Please enter your UPI transaction reference', 'error'); return false;
  }
  const btn = document.getElementById('ctaBtn');
  btn.innerHTML = '<i class="fas fa-circle-notch fa-spin" style="font-size:14px"></i> Processing…';
  btn.disabled = true;
  anime({ targets: btn, scale:[1,1.02,1], duration:400, easing:'easeInOutSine' });
  await new Promise(r => setTimeout(r, 900));
  document.getElementById('payForm').submit();
  return false;
}
</script>
<?php require __DIR__ . '/footer.php'; ?>
