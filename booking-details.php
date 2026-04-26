<?php
/**
 * ROYALE VISTA — Booking Step 2: Guest Details
 * URL: booking-details.php?room=ID&checkin=YYYY-MM-DD&checkout=YYYY-MM-DD&qty=1
 */
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$B      = BASE;
$roomId = (int)($_GET['room'] ?? 0);
$qty    = max(1, (int)($_GET['qty'] ?? 1));
$ci     = clean($_GET['checkin']  ?? date('Y-m-d'));
$co     = clean($_GET['checkout'] ?? date('Y-m-d', strtotime('+2 days')));

if (!$roomId) { header("Location: $B/rooms.php"); exit; }

// Load room
$rtQ = $pdo->prepare("
    SELECT rt.*, 
        (SELECT image_url FROM room_images WHERE room_type_id=rt.id AND is_primary=1 LIMIT 1) img
    FROM room_types rt WHERE rt.id=? LIMIT 1
");
$rtQ->execute([$roomId]);
$room = $rtQ->fetch();
if (!$room) { header("Location: $B/rooms.php"); exit; }

$nights   = max(1, nightsBetween($ci, $co));
$subtotal = $room['price_usd'] * $qty * $nights;
$taxRate  = 0.18;

// Membership
$memDisc = 0; $memName = ''; $memNumber = '';
$msQ = $pdo->prepare("SELECT um.member_number,m.name,m.discount_pct FROM user_memberships um JOIN memberships m ON um.membership_id=m.id WHERE um.user_id=? AND um.status='active' ORDER BY m.discount_pct DESC LIMIT 1");
$msQ->execute([$_SESSION['user_id']]);
$mem = $msQ->fetch();
if ($mem) { $memDisc = round($subtotal * ($mem['discount_pct']/100), 2); $memName = $mem['name']; $memNumber = $mem['member_number']; }

// Loyalty
$lBalQ = $pdo->prepare("SELECT total_points FROM loyalty_points WHERE user_id=?");
$lBalQ->execute([$_SESSION['user_id']]);
$lBal = (int)($lBalQ->fetchColumn() ?? 0);

// Save to session
$_SESSION['bk'] = [
    'room_id' => $roomId, 'room_name' => $room['name'], 'price' => (float)$room['price_usd'],
    'qty' => $qty, 'ci' => $ci, 'co' => $co, 'nights' => $nights,
    'subtotal' => $subtotal, 'mem_disc' => $memDisc, 'mem_name' => $memName,
    'mem_number' => $memNumber, 'img' => $room['img'],
];

$pageTitle = 'Guest Details — Royale Vista';
require __DIR__ . '/header.php';
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
<style>
.bk-page { padding-top: 88px; min-height: 100vh; background: var(--bg); }
.bk-wrap  { max-width: 1080px; margin: 0 auto; padding: 36px 24px 80px; display: grid; grid-template-columns: 1fr 360px; gap: 28px; }
@media(max-width:840px) { .bk-wrap { grid-template-columns: 1fr; } .bk-side { position: static !important; } }
/* Step bar */
.bk-steps { display: flex; align-items: center; justify-content: center; padding: 24px 0 32px; gap: 0; }
.bk-stp   { display: flex; align-items: center; }
.bk-sn    { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-family: var(--cinzel); font-size: 12px; border: 2px solid var(--border); color: var(--muted); background: var(--card); transition: all .3s; }
.bk-stp.done .bk-sn  { background: var(--green); border-color: var(--green); color: #fff; }
.bk-stp.now  .bk-sn  { background: var(--gold); border-color: var(--gold); color: #fff; }
.bk-sl    { font-size: 11px; color: var(--muted); margin-left: 7px; white-space: nowrap; }
.bk-stp.now  .bk-sl  { color: var(--text); font-weight: 600; }
.bk-stp.done .bk-sl  { color: var(--green); }
.bk-line  { width: 56px; height: 1px; background: var(--border); margin: 0 8px; }
@media(max-width:480px) { .bk-sl { display:none; } .bk-line { width: 28px; } }
/* Cards */
.bk-card  { background: var(--card); border: 1px solid var(--bdr2); border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 18px; }
.bk-card-h { padding: 16px 22px; border-bottom: 1px solid var(--bdr2); background: var(--card2); display: flex; align-items: center; gap: 10px; }
.bk-card-h .ico { width: 34px; height: 34px; border-radius: 8px; background: var(--gold-dim); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--gold); font-size: 14px; flex-shrink: 0; }
.bk-card-h h3 { font-family: var(--serif); font-size: 18px; font-weight: 400; }
.bk-card-h p  { font-size: 12px; color: var(--muted); margin-top: 2px; }
.bk-card-b { padding: 20px 22px; }
/* Fields */
.frow { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media(max-width:560px) { .frow { grid-template-columns: 1fr; } }
.fld  { display: flex; flex-direction: column; gap: 5px; margin-bottom: 14px; }
.fld label { font-family: var(--cinzel); font-size: 9px; letter-spacing: 2px; text-transform: uppercase; color: var(--gold); font-weight: 500; }
.fld input, .fld select, .fld textarea { background: var(--input); border: 1px solid var(--border); border-radius: var(--radius); color: var(--text); font-family: var(--sans); font-size: 14px; padding: 11px 14px; outline: none; width: 100%; transition: border-color .2s, box-shadow .2s; }
.fld input:focus, .fld select:focus, .fld textarea:focus { border-color: var(--gold); box-shadow: 0 0 0 3px rgba(192,155,91,.08); }
.fld input::placeholder, .fld textarea::placeholder { color: var(--muted); }
.fld select option { background: var(--card); }
/* Offer */
.offer-row  { display: flex; gap: 8px; }
.offer-btn  { padding: 11px 18px; background: var(--gold); color: #fff; border: none; border-radius: var(--radius); font-family: var(--cinzel); font-size: 9px; letter-spacing: 2px; cursor: pointer; transition: background .2s; white-space: nowrap; }
.offer-btn:hover { background: var(--gold-dk); }
.offer-msg  { font-size: 12px; margin-top: 6px; min-height: 18px; }
.offer-msg.ok  { color: var(--green); }
.offer-msg.err { color: var(--red); }
/* Summary sidebar */
.bk-side { position: sticky; top: 82px; }
.sum-card { background: var(--card); border: 1px solid var(--bdr2); border-radius: var(--radius-lg); overflow: hidden; }
.sum-img  { width: 100%; height: 170px; object-fit: cover; display: block; }
.sum-body { padding: 18px 20px; }
.sum-eyebrow { font-family: var(--cinzel); font-size: 9px; letter-spacing: 2px; color: var(--gold); text-transform: uppercase; margin-bottom: 6px; }
.sum-name { font-family: var(--serif); font-size: 21px; margin-bottom: 4px; }
.sum-meta { font-size: 12px; color: var(--muted); margin-bottom: 16px; }
.sum-row  { display: flex; justify-content: space-between; align-items: center; padding: 9px 0; border-bottom: 1px solid var(--bdr2); font-size: 13.5px; }
.sum-row:last-child { border-bottom: none; }
.sum-row .l { color: var(--text2); }
.sum-disc   { color: var(--green) !important; }
.sum-total  { padding-top: 14px; }
.sum-total .l { font-size: 15px; font-weight: 700; }
.sum-total .v { font-family: var(--serif); font-size: 26px; color: var(--gold); }
/* CTA */
.bk-cta { background: var(--gold); color: #fff; border: none; border-radius: var(--radius); padding: 16px; width: 100%; font-family: var(--cinzel); font-size: 10px; letter-spacing: 2px; text-transform: uppercase; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all .25s; margin-top: 4px; }
.bk-cta:hover:not(:disabled) { background: var(--gold-dk); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(192,155,91,.35); }
.bk-cta:disabled { opacity: .5; cursor: not-allowed; }
.secure-note { display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 11px; color: var(--muted); margin-top: 10px; }
</style>

<div class="bk-page">
  <div class="container" style="padding-top:0">
    <!-- Steps -->
    <div class="bk-steps" id="bkSteps">
      <div class="bk-stp done"><div class="bk-sn"><i class="fas fa-check" style="font-size:12px"></i></div><span class="bk-sl">Select Room</span></div>
      <div class="bk-line"></div>
      <div class="bk-stp now"><div class="bk-sn">2</div><span class="bk-sl">Your Details</span></div>
      <div class="bk-line"></div>
      <div class="bk-stp"><div class="bk-sn">3</div><span class="bk-sl">Payment</span></div>
      <div class="bk-line"></div>
      <div class="bk-stp"><div class="bk-sn">4</div><span class="bk-sl">Confirmed</span></div>
    </div>
  </div>

  <div class="bk-wrap">
    <!-- LEFT: Form -->
    <div>
      <form method="POST" action="<?= $B ?>/booking-payment.php" id="bkForm">
        <input type="hidden" name="room_id"     value="<?= $roomId ?>">
        <input type="hidden" name="qty"         value="<?= $qty ?>">
        <input type="hidden" name="ci"          value="<?= htmlspecialchars($ci) ?>">
        <input type="hidden" name="co"          value="<?= htmlspecialchars($co) ?>">
        <input type="hidden" name="offer_code"  id="hdnOffer" value="">
        <input type="hidden" name="redeem_pts"  id="hdnPts"   value="0">

        <!-- 1. Guest Info -->
        <div class="bk-card" id="c1">
          <div class="bk-card-h">
            <div class="ico"><i class="fas fa-user"></i></div>
            <div><h3>Guest Information</h3><p>Details for your reservation</p></div>
          </div>
          <div class="bk-card-b">
            <div class="frow">
              <div class="fld">
                <label>Full Name *</label>
                <input name="guest_name" required placeholder="John Smith" value="<?= htmlspecialchars($_SESSION['display_name'] ?? $_SESSION['username'] ?? '') ?>">
              </div>
              <div class="fld">
                <label>Email Address *</label>
                <input type="email" name="guest_email" required placeholder="you@example.com" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>">
              </div>
            </div>
            <div class="frow">
              <div class="fld">
                <label>Phone Number</label>
                <input type="tel" name="guest_phone" placeholder="+1 555 000 0000">
              </div>
              <div class="fld">
                <label>Nationality</label>
                <select name="nationality">
                  <option value="">Select country…</option>
                  <?php foreach (['United States','United Arab Emirates','United Kingdom','India','France','Germany','Japan','Australia','Canada','Singapore','Saudi Arabia','Italy','Spain','China','Brazil','South Korea','Russia','Other'] as $c): ?>
                  <option><?= htmlspecialchars($c) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. Stay Details -->
        <div class="bk-card" id="c2">
          <div class="bk-card-h">
            <div class="ico"><i class="fas fa-calendar-alt"></i></div>
            <div><h3>Stay Details</h3><p>Dates and number of guests</p></div>
          </div>
          <div class="bk-card-b">
            <div class="frow">
              <div class="fld">
                <label>Check-In *</label>
                <input type="date" name="ci" id="fCI" value="<?= htmlspecialchars($ci) ?>" min="<?= date('Y-m-d') ?>" required oninput="recalc()">
              </div>
              <div class="fld">
                <label>Check-Out *</label>
                <input type="date" name="co" id="fCO" value="<?= htmlspecialchars($co) ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required oninput="recalc()">
              </div>
            </div>
            <div class="frow">
              <div class="fld">
                <label>Adults *</label>
                <select name="adults">
                  <?php for($a=1;$a<=8;$a++): ?><option value="<?=$a?>" <?=$a===2?'selected':''?>><?=$a?> Adult<?=$a>1?'s':''?></option><?php endfor; ?>
                </select>
              </div>
              <div class="fld">
                <label>Children</label>
                <select name="children">
                  <?php for($c=0;$c<=4;$c++): ?><option value="<?=$c?>"><?=$c?> Child<?=$c!==1?'ren':''?></option><?php endfor; ?>
                </select>
              </div>
            </div>
            <div class="fld" style="margin-bottom:0">
              <label>Special Requests</label>
              <textarea name="special_req" placeholder="Early check-in, high floor, quiet room, dietary requirements…" rows="3"></textarea>
            </div>
          </div>
        </div>

        <!-- 3. Discounts -->
        <div class="bk-card" id="c3">
          <div class="bk-card-h">
            <div class="ico"><i class="fas fa-tag"></i></div>
            <div><h3>Discounts & Offers</h3><p>Apply promo codes and loyalty points</p></div>
          </div>
          <div class="bk-card-b">
            <?php if ($mem): ?>
            <div style="background:var(--gold-dim);border:1px solid var(--border);border-radius:8px;padding:12px 14px;margin-bottom:16px;display:flex;align-items:center;gap:10px">
              <i class="fas fa-crown" style="color:var(--gold);font-size:18px"></i>
              <div>
                <div style="font-size:13.5px;font-weight:600"><?= htmlspecialchars($memName) ?> Member — <?= $mem['discount_pct'] ?>% discount</div>
                <div style="font-size:11px;color:var(--muted)">Applied automatically · Saving <?= formatPrice($memDisc) ?> on this booking</div>
              </div>
            </div>
            <?php endif; ?>
            <div class="fld">
              <label>Promo Code</label>
              <div class="offer-row">
                <input id="offerIn" placeholder="e.g. WELCOME10, SUMMER20" style="background:var(--input);border:1px solid var(--border);border-radius:var(--radius);color:var(--text);font-family:var(--sans);font-size:14px;padding:11px 14px;outline:none;flex:1;transition:border-color .2s" oninput="this.value=this.value.toUpperCase()">
                <button type="button" class="offer-btn" onclick="applyOffer()">Apply</button>
              </div>
              <div class="offer-msg" id="offerMsg"></div>
            </div>
            <?php if ($lBal >= 100): ?>
            <div class="fld" style="margin-bottom:0">
              <label>Loyalty Points <span style="font-family:var(--sans);font-size:10px;color:var(--gold);text-transform:none;letter-spacing:0;font-weight:400"><?= number_format($lBal) ?> available = <?= formatPrice(floor($lBal/100)) ?> value</span></label>
              <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                <input type="number" id="ptsIn" min="0" max="<?= min($lBal,5000) ?>" step="100" value="0" style="width:120px;background:var(--input);border:1px solid var(--border);border-radius:var(--radius);color:var(--text);font-family:var(--sans);font-size:14px;padding:11px 14px;outline:none" oninput="updatePts(this.value)">
                <span style="font-size:13.5px">= <strong id="ptsVal" style="color:var(--gold)"><?= formatPrice(0) ?></strong> off</span>
                <button type="button" onclick="document.getElementById('ptsIn').value=<?= min($lBal,5000) ?>;updatePts(<?= min($lBal,5000) ?>)" style="background:var(--card2);border:1px solid var(--bdr2);border-radius:var(--radius);padding:9px 14px;font-size:12px;cursor:pointer;color:var(--text2);font-family:var(--sans)">Max</button>
              </div>
              <div style="font-size:11px;color:var(--muted);margin-top:5px">100 points = <?= formatPrice(1) ?> · Minimum 100 pts to redeem</div>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <button type="submit" class="bk-cta" id="ctaBtn">
          <i class="fas fa-arrow-right" style="font-size:11px"></i>
          Continue to Payment
        </button>
        <div class="secure-note"><i class="fas fa-lock" style="font-size:10px"></i> Secure &amp; encrypted · Best rate guaranteed</div>
        <div style="text-align:center;margin-top:14px">
          <a href="<?= $B ?>/rooms.php?type=<?= $roomId ?>" style="font-size:12px;color:var(--muted)">← Back to Rooms</a>
        </div>
      </form>
    </div>

    <!-- RIGHT: Summary -->
    <div class="bk-side">
      <div class="sum-card" id="sumCard">
        <?php if ($room['img']): ?>
        <img src="<?= htmlspecialchars($room['img']) ?>" alt="" class="sum-img" onerror="this.style.display='none'">
        <?php endif; ?>
        <div class="sum-body">
          <div class="sum-eyebrow">Your Selection</div>
          <div class="sum-name"><?= htmlspecialchars($room['name']) ?></div>
          <div class="sum-meta"><span id="sQty"><?= $qty ?></span> room<?= $qty>1?'s':'' ?> · <span id="sNights"><?= $nights ?></span> night<?= $nights>1?'s':'' ?></div>

          <div class="sum-row"><span class="l">Check-In</span><span id="sCI"><?= date('D d M Y', strtotime($ci)) ?></span></div>
          <div class="sum-row"><span class="l">Check-Out</span><span id="sCO"><?= date('D d M Y', strtotime($co)) ?></span></div>
          <div class="sum-row"><span class="l">Rate per Night</span><span><?= formatPrice($room['price_usd']) ?></span></div>
          <?php if ($room['has_breakfast']): ?>
          <div class="sum-row"><span class="l" style="color:var(--green)"><i class="fas fa-check" style="margin-right:4px"></i>Breakfast</span><span style="color:var(--green)">Included</span></div>
          <?php endif; ?>
          <div class="sum-row"><span class="l">Subtotal</span><span id="sSub"><?= formatPrice($subtotal) ?></span></div>
          <?php if ($memDisc > 0): ?>
          <div class="sum-row sum-disc"><span class="l"><i class="fas fa-crown" style="margin-right:4px"></i><?= htmlspecialchars($memName) ?></span><span id="sMem">−<?= formatPrice($memDisc) ?></span></div>
          <?php endif; ?>
          <div class="sum-row sum-disc" id="sOfferRow" style="display:none"><span class="l" id="sOfferLbl">Offer</span><span id="sOfferVal"></span></div>
          <div class="sum-row sum-disc" id="sPtsRow"   style="display:none"><span class="l">Loyalty Points</span><span id="sPtsVal"></span></div>
          <div class="sum-row"><span class="l">Tax (18%)</span><span id="sTax"><?= formatPrice(($subtotal-$memDisc)*$taxRate) ?></span></div>
          <div class="sum-row sum-total"><span class="l">Total</span><span class="v" id="sTotal"><?= formatPrice(($subtotal-$memDisc)*(1+$taxRate)) ?></span></div>
        </div>
      </div>

      <div style="background:var(--card);border:1px solid var(--bdr2);border-radius:var(--radius-lg);padding:16px 18px;margin-top:14px">
        <div style="font-family:var(--cinzel);font-size:8px;color:var(--gold);letter-spacing:2px;text-transform:uppercase;margin-bottom:10px">Included</div>
        <?php foreach (['Free high-speed WiFi','Daily housekeeping','24/7 concierge service','Pool & fitness access','Late checkout on request'] as $b): ?>
        <div style="font-size:12.5px;color:var(--text2);padding:4px 0;display:flex;gap:7px"><i class="fas fa-check" style="color:var(--gold);font-size:10px;margin-top:3px;width:12px;flex-shrink:0"></i><?= $b ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<script>
const PRICE  = <?= (float)$room['price_usd'] ?>;
const QTY    = <?= $qty ?>;
const MEM_D  = <?= $memDisc ?>;
const TAX    = 0.18;
const BASE   = '<?= $B ?>';
const RATE   = <?= getCurrencyRate() ?>;
const SYM    = '<?= addslashes(getCurrencySymbol()) ?>';
const DEC    = <?= CURRENCIES[getUserCurrency()]['dec'] ?>;
let offerD = 0, ptsD = 0;

function fmt(usd) {
    const v = usd * RATE;
    return SYM + (DEC === 0 ? Math.round(v).toLocaleString() : v.toFixed(DEC));
}
function nights(ci, co) { return Math.max(1, Math.round((new Date(co) - new Date(ci)) / 86400000)); }

function recalc(ci, co) {
    ci = ci || document.getElementById('fCI').value;
    co = co || document.getElementById('fCO').value;
    if (!ci || !co || ci >= co) return;
    const n   = nights(ci, co);
    const sub = PRICE * QTY * n;
    document.getElementById('sNights').textContent = n;
    document.getElementById('sCI').textContent = new Date(ci+'T00:00').toLocaleDateString('en-GB', {weekday:'short',day:'numeric',month:'short',year:'numeric'});
    document.getElementById('sCO').textContent = new Date(co+'T00:00').toLocaleDateString('en-GB', {weekday:'short',day:'numeric',month:'short',year:'numeric'});
    document.getElementById('sSub').textContent = fmt(sub);
    updateTotals(sub);
    anime && anime({ targets: '#sTotal', scale: [1.05, 1], duration: 280, easing: 'easeOutBack' });
}

function updateTotals(sub) {
    if (sub === undefined) {
        const ci = document.getElementById('fCI').value;
        const co = document.getElementById('fCO').value;
        if (!ci || !co || ci >= co) return;
        sub = PRICE * QTY * nights(ci, co);
    }
    const after = Math.max(0, sub - MEM_D - offerD - ptsD);
    const tax   = after * TAX;
    document.getElementById('sTax').textContent   = fmt(tax);
    document.getElementById('sTotal').textContent = fmt(after + tax);
}

async function applyOffer() {
    const code = document.getElementById('offerIn').value.trim().toUpperCase();
    const msg  = document.getElementById('offerMsg');
    if (!code) { msg.textContent = 'Please enter a promo code'; msg.className = 'offer-msg err'; return; }
    msg.textContent = 'Checking…'; msg.className = 'offer-msg';
    const ci = document.getElementById('fCI').value;
    const co = document.getElementById('fCO').value;
    const sub = PRICE * QTY * (ci && co && ci < co ? nights(ci, co) : <?= $nights ?>);
    try {
        const fd = new FormData();
        fd.append('action', 'validate'); fd.append('code', code); fd.append('subtotal', sub);
        const res  = await fetch(BASE + '/api/offers.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.valid) {
            offerD = data.discount;
            document.getElementById('hdnOffer').value = code;
            msg.textContent = '✓ ' + data.description + ' — saving ' + fmt(data.discount);
            msg.className = 'offer-msg ok';
            document.getElementById('sOfferRow').style.display = 'flex';
            document.getElementById('sOfferLbl').textContent = code;
            document.getElementById('sOfferVal').textContent = '−' + fmt(data.discount);
            updateTotals(sub);
        } else {
            offerD = 0;
            document.getElementById('hdnOffer').value = '';
            msg.textContent = '✗ ' + data.error;
            msg.className = 'offer-msg err';
            document.getElementById('sOfferRow').style.display = 'none';
            updateTotals(sub);
        }
    } catch(e) { msg.textContent = 'Connection error'; msg.className = 'offer-msg err'; }
}

function updatePts(pts) {
    const usd = Math.floor(parseInt(pts || 0) / 100);
    ptsD = usd;
    document.getElementById('hdnPts').value   = pts;
    document.getElementById('ptsVal').textContent = fmt(usd);
    const row = document.getElementById('sPtsRow');
    if (row) { row.style.display = usd > 0 ? 'flex' : 'none'; document.getElementById('sPtsVal').textContent = '−' + fmt(usd); }
    updateTotals();
}

document.getElementById('bkForm').addEventListener('submit', function(e) {
    const name  = this.querySelector('[name=guest_name]').value.trim();
    const email = this.querySelector('[name=guest_email]').value.trim();
    const ci    = document.getElementById('fCI').value;
    const co    = document.getElementById('fCO').value;
    if (!name || !email || !ci || !co || ci >= co) {
        e.preventDefault();
        toast && toast('Please fill in all required fields', 'error');
        return;
    }
    const btn = document.getElementById('ctaBtn');
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin" style="font-size:14px"></i> Please wait…';
    btn.disabled = true;
});

document.addEventListener('DOMContentLoaded', () => {
    anime({ targets: '#bkSteps .bk-stp', opacity:[0,1], translateY:[-12,0], duration:500, delay:anime.stagger(80), easing:'easeOutExpo' });
    anime({ targets: ['#c1','#c2','#c3'], opacity:[0,1], translateY:[22,0], duration:550, delay:anime.stagger(90,{start:300}), easing:'easeOutExpo' });
    anime({ targets: '#sumCard', opacity:[0,1], translateX:[16,0], duration:600, delay:500, easing:'easeOutExpo' });
});
</script>

<?php require __DIR__ . '/footer.php'; ?>
