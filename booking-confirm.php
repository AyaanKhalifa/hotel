<?php
/**
 * ROYALE VISTA — Booking Step 4: Confirm & Process
 */
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mailer.php';
requireLogin();

$B = BASE;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['bk_pay'])) {
    header("Location: $B/rooms.php"); exit;
}

$bk      = $_SESSION['bk_pay'];
$payMeth = clean($_POST['pay_method'] ?? 'hotel');
$uid     = (int)$_SESSION['user_id'];

// Create booking in DB
$bookRef   = generateRef('BK');
$invoiceNo = generateRef('INV');
$paidAt    = ($payMeth !== 'hotel') ? date('Y-m-d H:i:s') : null;
$payStatus = ($payMeth !== 'hotel') ? 'paid' : 'pending';
$currency  = getUserCurrency();

// Process gift card(s)
$gcCodesRaw = $_POST['gc_codes'] ?? '[]';
$gcCodes = json_decode($gcCodesRaw, true);
if (!is_array($gcCodes)) $gcCodes = [];
$gcCodes = array_values(array_unique(array_filter(array_map(fn($c) => strtoupper(clean(trim((string)$c))), $gcCodes))));
$gcUsed = 0;
$gcApplied = [];

try {
    $pdo->beginTransaction();

    if (!empty($gcCodes)) {
        foreach ($gcCodes as $gcCode) {
            if ($bk['final'] <= 0) break;
            $q = $pdo->prepare("SELECT * FROM gift_cards WHERE code=? AND is_active=1 AND balance_usd>0 AND (expires_at IS NULL OR expires_at>=CURDATE()) LIMIT 1 FOR UPDATE");
            $q->execute([$gcCode]);
            $gcObj = $q->fetch();
            if (!$gcObj) continue;
            $usedNow = min($bk['final'], (float)$gcObj['balance_usd']);
            if ($usedNow <= 0) continue;
            $gcUsed += $usedNow;
            $gcApplied[] = ['id' => (int)$gcObj['id'], 'code' => $gcObj['code'], 'used' => $usedNow];
            $bk['final'] = max(0, $bk['final'] - $usedNow);
        }
        if ($bk['final'] == 0) {
            $payMeth = 'giftcard';
            $payStatus = 'paid';
            $paidAt = date('Y-m-d H:i:s');
        }
    }


    $pdo->prepare("
        INSERT INTO bookings
            (booking_ref,invoice_no,user_id,guest_name,guest_email,guest_phone,
             check_in,check_out,nights,adults,children,special_req,
             total_usd,discount_usd,taxes_usd,final_usd,currency,
             offer_code,member_number,pay_method,pay_status,paid_at,status)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ")->execute([
        $bookRef, $invoiceNo, $uid,
        $bk['guest_name'], $bk['guest_email'], $bk['guest_phone'],
        $bk['ci'], $bk['co'], $bk['nights'],
        $bk['adults'], $bk['children'], $bk['special_req'],
        $bk['sub'], round($bk['mem_disc']+$bk['offer_disc']+$bk['loyalty_disc'], 2),
        $bk['taxes'], $bk['final'],
        $currency, $bk['offer_code'] ?: null, $bk['mem_number'] ?: null,
        $payMeth, $payStatus, $paidAt, 'confirmed'
    ]);

    // Booked room
    $pdo->prepare("
        INSERT INTO booked_rooms (booking_ref,room_type_id,room_type_name,quantity,price_usd,nights,total_usd)
        VALUES (?,?,?,?,?,?,?)
    ")->execute([
        $bookRef, $bk['room_id'], $bk['room_name'], $bk['qty'], $bk['price_usd'], $bk['nights'], $bk['sub']
    ]);

    // Assign physical room numbers and lock inventory
    $assignStmt = $pdo->prepare("
        SELECT rr.id, rr.room_number
        FROM rooms rr
        WHERE rr.room_type_id = ?
          AND rr.status = 'available'
          AND rr.id NOT IN (
            SELECT DISTINCT bra.room_id
            FROM booking_room_assignments bra
            JOIN bookings b ON b.booking_ref = bra.booking_ref
            WHERE bra.room_type_id = ?
              AND b.status NOT IN ('cancelled','checked_out')
              AND b.check_in <= ?
              AND b.check_out > ?
          )
        ORDER BY rr.room_number
        LIMIT ".(int)$bk['qty']);
    $assignStmt->execute([$bk['room_id'], $bk['room_id'], $bk['co'], $bk['ci']]);
    $assignedRooms = $assignStmt->fetchAll();
    if (count($assignedRooms) < (int)$bk['qty']) {
        throw new Exception('Insufficient room inventory for selected dates.');
    }
    foreach ($assignedRooms as $ar) {
        $pdo->prepare("INSERT INTO booking_room_assignments (booking_ref,room_id,room_number,room_type_id,room_type_name) VALUES (?,?,?,?,?)")
            ->execute([$bookRef, $ar['id'], $ar['room_number'], $bk['room_id'], $bk['room_name']]);
        $pdo->prepare("UPDATE rooms SET status='occupied' WHERE id=?")->execute([$ar['id']]);
    }

    // Update offer use count
    if ($bk['offer_code']) {
        $pdo->prepare("UPDATE offers SET uses_count=uses_count+1 WHERE code=?")->execute([$bk['offer_code']]);
    }

    // Gift card usage & balance update (supports multiple cards)
    if ($gcUsed > 0 && !empty($gcApplied)) {
        foreach ($gcApplied as $gc) {
            $pdo->prepare("UPDATE gift_cards SET balance_usd=balance_usd-? WHERE id=?")->execute([$gc['used'], $gc['id']]);
            $pdo->prepare("INSERT INTO gift_card_usage (card_id,booking_ref,amount_used) VALUES (?,?,?)")->execute([$gc['id'], $bookRef, $gc['used']]);
        }
    }

    // Loyalty: deduct redeemed, earn new
    $pdo->prepare("INSERT IGNORE INTO loyalty_points (user_id,total_points,lifetime_points,tier) VALUES (?,0,0,'bronze')")->execute([$uid]);
    if ($bk['redeem_pts'] > 0) {
        $pdo->prepare("UPDATE loyalty_points SET total_points=GREATEST(0,total_points-?) WHERE user_id=?")->execute([$bk['redeem_pts'], $uid]);
    }
    $pdo->prepare("UPDATE loyalty_points SET total_points=total_points+?,lifetime_points=lifetime_points+? WHERE user_id=?")->execute([$bk['earn_pts'], $bk['earn_pts'], $uid]);
    $pdo->prepare("INSERT INTO loyalty_transactions (user_id,booking_ref,type,points,balance_after,description) SELECT ?,?,'earn',?,(SELECT total_points FROM loyalty_points WHERE user_id=?),?")->execute([$uid,$bookRef,$bk['earn_pts'],$uid,"Booking $bookRef"]);

    // Payment log
    try {
        $pdo->prepare("INSERT INTO payment_logs (booking_ref,user_id,amount_usd,method,status,transaction_id) VALUES (?,?,?,?,?,?)")
            ->execute([$bookRef,$uid,$bk['final'],$payMeth,$payStatus,'TXN'.strtoupper(substr(bin2hex(random_bytes(5)),0,10))]);
    } catch(Exception $e) {}

    // Notification
    try {
        $pdo->prepare("INSERT INTO notifications (user_id,type,title,message,link) VALUES (?,?,?,?,?)")
            ->execute([$uid,'booking','Booking Confirmed — '.$bookRef,"Check-in: ".date('d M Y',strtotime($bk['ci'])).". Ref: $bookRef",$B.'/bookings.php']);
    } catch(Exception $e) {}

    $pdo->commit();

    // Send booking confirmation email to registered/guest email.
    try {
        $bookingData = [
            'booking_ref' => $bookRef,
            'invoice_no'  => $invoiceNo,
            'discount_usd'=> round($bk['mem_disc']+$bk['offer_disc']+$bk['loyalty_disc'] + $gcUsed, 2),
            'offer_code'  => $bk['offer_code'] ?: '',
            'pay_method'  => $payMeth,
            'paid_at'     => $paidAt,
            'pay_status'  => $payStatus,
            'nights'      => $bk['nights'],
            'adults'      => $bk['adults'],
            'children'    => $bk['children'],
            'special_req' => $bk['special_req'],
            'check_in'    => $bk['ci'],
            'check_out'   => $bk['co'],
            'total_usd'   => $bk['sub'],
            'taxes_usd'   => $bk['taxes'],
            'final_usd'   => $bk['final'],
            'guest_name'  => $bk['guest_name']
        ];
        $roomData = [[
            'name'      => $bk['room_name'],
            'qty'       => $bk['qty'],
            'nights'    => $bk['nights'],
            'price_usd' => $bk['price_usd'],
            'total'     => $bk['sub']
        ]];
        sendBookingConfirmationEmail($bk['guest_email'], $bk['guest_name'], $bookingData, $roomData);
    } catch (Exception $e) {}

    unset($_SESSION['bk_pay'], $_SESSION['bk']);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Booking confirm error: '.$e->getMessage());
    $_SESSION['bk_err'] = 'Booking failed. Please try again.';
    header("Location: $B/booking-payment.php"); exit;
}

// Load booking for display
$bkForDisplay = [
    'ref'       => $bookRef,
    'invoice'   => $invoiceNo,
    'name'      => $bk['guest_name'],
    'email'     => $bk['guest_email'],
    'room_name' => $bk['room_name'],
    'qty'       => $bk['qty'],
    'ci'        => $bk['ci'],
    'co'        => $bk['co'],
    'nights'    => $bk['nights'],
    'adults'    => $bk['adults'],
    'children'  => $bk['children'],
    'sub'       => $bk['sub'],
    'mem_disc'  => $bk['mem_disc'],
    'offer_disc'=> $bk['offer_disc'],
    'loyalty_disc'=> $bk['loyalty_disc'],
    'taxes'     => $bk['taxes'],
    'final'     => $bk['final'],
    'earn_pts'  => $bk['earn_pts'],
    'pay_meth'  => $payMeth,
    'pay_status'=> $payStatus,
    'mem_name'  => $bk['mem_name'],
    'offer_code'=> $bk['offer_code'],
    'gc_used'   => $gcUsed,
];

$pageTitle = 'Booking Confirmed — Royale Vista';
require __DIR__ . '/header.php';
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
<style>
.conf-page { padding-top: 88px; min-height: 100vh; background: var(--bg); }
.conf-wrap { max-width: 740px; margin: 0 auto; padding: 48px 24px 80px; }
/* Steps */
.bk-steps { display:flex;align-items:center;justify-content:center;padding:0 0 40px;gap:0; }
.bk-stp   { display:flex;align-items:center; }
.bk-sn    { width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--cinzel);font-size:12px;border:2px solid var(--green);background:var(--green);color:#fff;transition:all .3s; }
.bk-sl    { font-size:11px;color:var(--green);margin-left:7px;white-space:nowrap;font-weight:600; }
.bk-line  { width:56px;height:1px;background:var(--green);margin:0 8px; }
@media(max-width:480px) { .bk-sl{display:none} .bk-line{width:28px} }
/* Success */
.conf-hd  { text-align: center; margin-bottom: 36px; }
.conf-ico { width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg,#22c55e,#16a34a);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:38px;color:#fff;box-shadow:0 8px 32px rgba(34,197,94,.4); }
.conf-h1  { font-family:var(--serif);font-size:clamp(28px,5vw,46px);font-weight:300;margin-bottom:10px; }
.conf-sub { font-size:14px;color:var(--text2);line-height:1.75; }
.conf-ref { display:inline-block;background:var(--gold-dim);border:2px solid var(--gold);border-radius:10px;padding:13px 26px;font-family:var(--cinzel);font-size:22px;color:var(--gold);letter-spacing:3px;margin:16px 0; }
/* Points earned */
.pts-box  { background:linear-gradient(135deg,var(--gold),var(--gold-dk));border-radius:10px;padding:16px 20px;color:#fff;display:flex;align-items:center;gap:16px;margin-bottom:18px; }
.pts-num  { font-family:var(--serif);font-size:36px;font-weight:300;line-height:1; }
/* Detail cards */
.conf-card { background:var(--card);border:1px solid var(--bdr2);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:16px; }
.conf-card-h { padding:15px 22px;border-bottom:1px solid var(--bdr2);background:var(--card2);font-family:var(--serif);font-size:18px;display:flex;align-items:center;gap:10px; }
.conf-card-b { padding:20px 22px; }
.conf-row { display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--bdr2);font-size:13.5px; }
.conf-row:last-child { border-bottom:none; }
.conf-row .l { color:var(--text2); }
.conf-row .v { font-weight:600; }
.conf-disc   { color:var(--green) !important; }
.conf-total .l { font-size:15px;font-weight:700; }
.conf-total .v { font-family:var(--serif);font-size:26px;color:var(--gold); }
/* Actions */
.conf-actions { display:flex;flex-direction:column;gap:10px;margin-top:24px; }
.ca-primary { padding:16px;background:var(--gold);color:#fff;border:none;border-radius:var(--radius);font-family:var(--cinzel);font-size:10px;letter-spacing:2px;text-transform:uppercase;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px;text-decoration:none;transition:all .25s; }
.ca-primary:hover { background:var(--gold-dk);color:#fff;transform:translateY(-1px); }
.ca-secondary { padding:14px;background:transparent;color:var(--text2);border:1px solid var(--border);border-radius:var(--radius);font-family:var(--sans);font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;transition:all .2s; }
.ca-secondary:hover { border-color:var(--gold);color:var(--gold); }
/* 2-col detail grid */
.dg2 { display:grid;grid-template-columns:1fr 1fr;gap:14px; }
@media(max-width:480px) { .dg2{grid-template-columns:1fr} }
.dg-item { background:var(--card2);border-radius:8px;padding:12px 14px; }
.dg-lbl  { font-family:var(--cinzel);font-size:8px;color:var(--gold);letter-spacing:2px;text-transform:uppercase;margin-bottom:4px; }
.dg-val  { font-size:14px;font-weight:600; }
.dg-sub  { font-size:12px;color:var(--muted);margin-top:2px; }
</style>

<div class="conf-page">
  <div class="conf-wrap">

    <!-- All steps done -->
    <div class="bk-steps" id="confSteps">
      <?php foreach (['Select Room','Your Details','Payment','Confirmed'] as $i => $s): ?>
      <div class="bk-stp"><div class="bk-sn"><i class="fas fa-check" style="font-size:11px"></i></div><span class="bk-sl"><?= $s ?></span></div>
      <?php if ($i < 3): ?><div class="bk-line"></div><?php endif; ?>
      <?php endforeach; ?>
    </div>

    <!-- Success Header -->
    <div class="conf-hd" id="confHd">
      <div class="conf-ico" id="confIco"><i class="fas fa-check" style="font-size:36px"></i></div>
      <h1 class="conf-h1">Booking Confirmed!</h1>
      <p class="conf-sub">Thank you, <strong><?= htmlspecialchars($bkForDisplay['name']) ?></strong>!<br>A confirmation has been sent to <strong><?= htmlspecialchars($bkForDisplay['email']) ?></strong></p>
      <div class="conf-ref"><?= htmlspecialchars($bookRef) ?></div>
    </div>

    <!-- Points Earned -->
    <?php if ($bkForDisplay['earn_pts'] > 0): ?>
    <div class="pts-box" id="ptsBox">
      <div style="font-size:28px">🪙</div>
      <div>
        <div class="pts-num"><?= number_format($bkForDisplay['earn_pts']) ?></div>
        <div style="font-size:13px;opacity:.85">Loyalty points earned · Redeemable on your next stay</div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Stay Details -->
    <div class="conf-card" id="confDet">
      <div class="conf-card-h"><i class="fas fa-bed" style="color:var(--gold)"></i> Reservation Details</div>
      <div class="conf-card-b">
        <div class="dg2" style="margin-bottom:16px">
          <div class="dg-item"><div class="dg-lbl">Room</div><div class="dg-val"><?= htmlspecialchars($bkForDisplay['room_name']) ?></div><div class="dg-sub"><?= $bkForDisplay['qty'] ?> room<?= $bkForDisplay['qty']>1?'s':'' ?></div></div>
          <div class="dg-item"><div class="dg-lbl">Duration</div><div class="dg-val"><?= $bkForDisplay['nights'] ?> Night<?= $bkForDisplay['nights']!=1?'s':'' ?></div><div class="dg-sub"><?= $bkForDisplay['adults'] ?> guest<?= $bkForDisplay['adults']>1?'s':'' ?></div></div>
          <div class="dg-item"><div class="dg-lbl">Check-In</div><div class="dg-val"><?= date('D d M Y', strtotime($bkForDisplay['ci'])) ?></div><div class="dg-sub">From 2:00 PM</div></div>
          <div class="dg-item"><div class="dg-lbl">Check-Out</div><div class="dg-val"><?= date('D d M Y', strtotime($bkForDisplay['co'])) ?></div><div class="dg-sub">By 12:00 PM</div></div>
        </div>
        <div class="conf-row"><span class="l">Invoice Number</span><span class="v" style="font-family:var(--sans)"><?= htmlspecialchars($invoiceNo) ?></span></div>
        <div class="conf-row"><span class="l">Payment</span>
          <span class="v" style="display:flex;align-items:center;gap:7px">
            <?= ['card'=>'💳 Card','upi'=>'📱 UPI','hotel'=>'🏨 Pay at Hotel','giftcard'=>'🎁 Gift Card'][$payMeth] ?? 'Payment' ?>
            <span class="badge <?= $payStatus==='paid'?'badge-green':'badge-amber' ?>"><?= ucfirst($payStatus) ?></span>
          </span>
        </div>
      </div>
    </div>

    <!-- Price -->
    <div class="conf-card" id="confPrice">
      <div class="conf-card-h"><i class="fas fa-receipt" style="color:var(--gold)"></i> Price Breakdown</div>
      <div class="conf-card-b">
        <div class="conf-row"><span class="l"><?= htmlspecialchars($bkForDisplay['room_name']) ?> × <?= $bkForDisplay['qty'] ?> × <?= $bkForDisplay['nights'] ?> nights</span><span class="v"><?= formatPrice($bkForDisplay['sub']) ?></span></div>
        <?php if ($bkForDisplay['mem_disc'] > 0): ?><div class="conf-row conf-disc"><span class="l"><i class="fas fa-crown" style="margin-right:4px"></i><?= htmlspecialchars($bkForDisplay['mem_name']) ?></span><span class="v">−<?= formatPrice($bkForDisplay['mem_disc']) ?></span></div><?php endif; ?>
        <?php if ($bkForDisplay['offer_disc'] > 0): ?><div class="conf-row conf-disc"><span class="l"><i class="fas fa-tag" style="margin-right:4px"></i><?= htmlspecialchars($bkForDisplay['offer_code']) ?></span><span class="v">−<?= formatPrice($bkForDisplay['offer_disc']) ?></span></div><?php endif; ?>
        <?php if ($bkForDisplay['loyalty_disc'] > 0): ?><div class="conf-row conf-disc"><span class="l"><i class="fas fa-coins" style="margin-right:4px"></i>Loyalty Points</span><span class="v">−<?= formatPrice($bkForDisplay['loyalty_disc']) ?></span></div><?php endif; ?>
        <?php if ($bkForDisplay['gc_used'] > 0): ?><div class="conf-row conf-disc"><span class="l"><i class="fas fa-gift" style="margin-right:4px"></i>Gift Card</span><span class="v">−<?= formatPrice($bkForDisplay['gc_used']) ?></span></div><?php endif; ?>
        <div class="conf-row"><span class="l">Tax (18% GST)</span><span class="v"><?= formatPrice($bkForDisplay['taxes']) ?></span></div>
        <div class="conf-row conf-total"><span class="l">Total <?= $payStatus==='paid'?'Paid':'Due at Hotel' ?></span><span class="v"><?= formatPrice($bkForDisplay['final']) ?></span></div>
      </div>
    </div>

    <!-- Actions -->
    <div class="conf-actions" id="confAct">
      <a href="<?= $B ?>/invoice.php?ref=<?= urlencode($bookRef) ?>" class="ca-primary" target="_blank">
        <i class="fas fa-file-invoice" style="font-size:13px"></i> View &amp; Download Invoice
      </a>
      <a href="<?= $B ?>/bookings.php" class="ca-secondary"><i class="fas fa-calendar-check"></i> Manage My Bookings</a>
      <a href="<?= $B ?>/rooms.php"    class="ca-secondary"><i class="fas fa-bed"></i> Book Another Room</a>
    </div>

    <!-- Habibi -->
    <div style="margin-top:22px;padding:18px 20px;background:var(--card);border:1px solid var(--bdr2);border-radius:var(--radius-lg);display:flex;align-items:center;gap:14px" id="confHabibi">
      <div style="width:46px;height:46px;border-radius:50%;background:var(--charcoal,#1c1813);border:2px solid var(--gold);display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0">🌙</div>
      <div>
        <div style="font-family:var(--serif);font-size:16px;margin-bottom:4px">Habibi is here for you</div>
        <div style="font-size:13px;color:var(--text2)">Need a restaurant, airport transfer, or anything at all?
          <button onclick="window.Habibi&&Habibi.toggle()" style="background:none;border:none;color:var(--gold);cursor:pointer;font-family:var(--sans);font-size:13px;text-decoration:underline;padding:0">Chat with Habibi →</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const BASE = '<?= $B ?>';

document.addEventListener('DOMContentLoaded', () => {
    // Confetti
    launchConfetti();

    anime.timeline({ easing: 'easeOutExpo' })
      .add({ targets: '#confSteps .bk-stp', opacity:[0,1], translateY:[-12,0], duration:500, delay:anime.stagger(70) })
      .add({ targets: '#confIco', scale:[0,1.2,1], opacity:[0,1], duration:700, easing:'easeOutElastic(1,.5)' }, '-=200')
      .add({ targets: '#confHd h1, #confHd p', opacity:[0,1], translateY:[18,0], duration:500, delay:anime.stagger(80) }, '-=400')
      .add({ targets: '#confHd .conf-ref', opacity:[0,1], scale:[.85,1], duration:450 }, '-=200')
      .add({ targets: '#ptsBox', opacity:[0,1], translateX:[-18,0], duration:450 }, '-=100')
      .add({ targets: ['#confDet','#confPrice'], opacity:[0,1], translateY:[16,0], duration:450, delay:anime.stagger(80) }, '-=200')
      .add({ targets: '#confAct > *', opacity:[0,1], translateY:[12,0], duration:380, delay:anime.stagger(70) }, '-=200')
      .add({ targets: '#confHabibi', opacity:[0,1], translateY:[10,0], duration:380 }, '-=100');
});

function launchConfetti() {
    const cols = ['#c09b5b','#d4b483','#f59e0b','#22c55e','#ffffff','#3b82f6'];
    for (let i = 0; i < 60; i++) {
        const el = document.createElement('div');
        const size = 4 + Math.random() * 7;
        el.style.cssText = `position:fixed;top:-20px;left:${Math.random()*100}vw;width:${size}px;height:${size}px;background:${cols[Math.floor(Math.random()*cols.length)]};border-radius:${Math.random()>.5?'50%':'2px'};z-index:9998;pointer-events:none;`;
        document.body.appendChild(el);
        anime({
            targets: el,
            translateY: ['0px', (window.innerHeight + 50) + 'px'],
            translateX: [(Math.random() - .5) * 250 + 'px'],
            rotate: [0, Math.random() * 720],
            opacity: [1, 0],
            duration: 2200 + Math.random() * 1800,
            delay: Math.random() * 800,
            easing: 'easeInCubic',
            complete: () => el.remove()
        });
    }
}
</script>
<?php require __DIR__ . '/footer.php'; ?>

