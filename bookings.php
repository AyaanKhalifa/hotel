<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$B   = BASE;
$uid = (int)$_SESSION['user_id'];
$sf  = clean($_GET['s'] ?? 'all');

$where  = 'b.user_id = ?';
$params = [$uid];
if ($sf !== 'all') { $where .= ' AND b.status = ?'; $params[] = $sf; }

$bks = $pdo->prepare("
    SELECT b.*,
           GROUP_CONCAT(br.room_type_name ORDER BY br.id SEPARATOR ', ') rooms_str
    FROM   bookings b
    LEFT JOIN booked_rooms br ON br.booking_ref = b.booking_ref
    WHERE  $where
    GROUP  BY b.id
    ORDER  BY b.created_at DESC
");
$bks->execute($params);
$allBks = $bks->fetchAll();

$statQ = $pdo->prepare("SELECT COUNT(*) total, COALESCE(SUM(final_usd),0) spent,
    SUM(CASE WHEN status IN ('confirmed','checked_in') THEN 1 ELSE 0 END) active
    FROM bookings WHERE user_id=?");
$statQ->execute([$uid]);
$stats = $statQ->fetch();

$loyQ = $pdo->prepare("SELECT total_points FROM loyalty_points WHERE user_id=?");
$loyQ->execute([$uid]);
$loyPts = (int)($loyQ->fetchColumn() ?? 0);

$pageTitle = 'My Bookings — Royale Vista';
require __DIR__ . '/header.php';
?>
<style>
.bk-page { padding: 88px 0 80px; min-height: calc(100vh - 68px); background: var(--bg2); }

/* Summary strip */
.bk-summary {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 32px;
}
@media(max-width:768px){ .bk-summary{ grid-template-columns: repeat(2,1fr); } }
.bk-sum-box {
  background: var(--card); border: 1px solid var(--bdr2); border-radius: 14px;
  padding: 18px 20px; display: flex; align-items: center; gap: 14px;
}
.bk-sum-ico {
  width: 42px; height: 42px; border-radius: 12px; background: var(--gold-dim);
  display: flex; align-items: center; justify-content: center;
  color: var(--gold); font-size: 16px; flex-shrink: 0;
}
.bk-sum-val { font-family: var(--serif); font-size: 22px; color: var(--text); line-height: 1.1; }
.bk-sum-lbl { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }

/* Tab bar */
.bk-tabs { display: flex; gap: 4px; margin-bottom: 28px; flex-wrap: wrap; }
.bk-tab {
  padding: 8px 18px; border-radius: 20px; font-size: 12.5px; font-weight: 500;
  border: 1px solid var(--bdr2); color: var(--text2); text-decoration: none;
  transition: all .2s; background: transparent;
}
.bk-tab:hover { border-color: var(--gold); color: var(--gold); }
.bk-tab.active { background: var(--gold); color: #000; border-color: var(--gold); font-weight: 700; }

/* Booking card */
.bk-card {
  background: var(--card); border: 1px solid var(--bdr2); border-radius: 18px;
  margin-bottom: 18px; overflow: hidden; transition: box-shadow .25s, transform .25s, border-color .25s;
}
.bk-card:hover { box-shadow: 0 12px 40px rgba(0,0,0,.09); transform: translateY(-2px); }
.bk-card.cancelled { opacity: .7; }

.bk-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 13px 22px; background: var(--card2); border-bottom: 1px solid var(--bdr2);
  flex-wrap: wrap; gap: 8px;
}
.bk-ref { font-family: monospace; font-size: 13px; color: var(--gold); font-weight: 700; letter-spacing: 1px; }
.bk-created { font-size: 11px; color: var(--muted); }

.bk-body {
  display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 20px;
  padding: 22px; align-items: center;
}
@media(max-width:960px){ .bk-body{ grid-template-columns: 1fr 1fr 1fr; } }
@media(max-width:640px){ .bk-body{ grid-template-columns: 1fr 1fr; } }
@media(max-width:400px){ .bk-body{ grid-template-columns: 1fr; } }

.bk-lbl  { font-size: 9.5px; color: var(--gold); letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 5px; font-weight: 600; }
.bk-val  { font-size: 14px; font-weight: 600; color: var(--text); }
.bk-sub  { font-size: 11.5px; color: var(--muted); margin-top: 3px; }
.bk-price{ font-family: var(--serif); font-size: 22px; color: var(--gold); font-weight: 300; }

/* Actions column */
.bk-acts { display: flex; flex-direction: column; gap: 8px; min-width: 160px; }
@media(max-width:960px){ .bk-acts{ grid-column: 1 / -1; flex-direction: row; flex-wrap: wrap; } }

.bk-status-badge {
  display: flex; align-items: center; justify-content: center; gap: 6px;
  padding: 8px 14px; border-radius: 10px; font-size: 11px; font-weight: 700;
  letter-spacing: .5px; text-transform: uppercase;
}

/* Real-time indicator */
.rt-live {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 10px; color: var(--green); font-weight: 600;
}
.rt-live-dot {
  width: 6px; height: 6px; border-radius: 50%; background: var(--green);
  animation: livePulse 1.8s ease-in-out infinite;
}
@keyframes livePulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.4;transform:scale(.7)} }

/* Empty state */
.bk-empty { text-align: center; padding: 80px 20px; }
.bk-empty-ico { font-size: 56px; opacity: .15; margin-bottom: 20px; }

/* Cancel modal */
.modal-back {
  position: fixed; inset: 0; background: rgba(0,0,0,.6); backdrop-filter: blur(4px);
  z-index: 2000; display: none; align-items: center; justify-content: center; padding: 20px;
}
.modal-back.open { display: flex; }
.modal-box {
  background: var(--card); border: 1px solid var(--border);
  border-radius: 20px; width: 420px; max-width: 100%; padding: 36px;
  text-align: center; box-shadow: 0 32px 80px rgba(0,0,0,.35);
  animation: modalIn .3s cubic-bezier(.34,1.5,.64,1);
}
@keyframes modalIn { from{opacity:0;transform:scale(.9) translateY(20px)} to{opacity:1;transform:none} }
.modal-box h3 { font-family: var(--serif); font-size: 24px; font-weight: 400; margin: 14px 0 10px; }
.modal-box p  { font-size: 14px; color: var(--text2); line-height: 1.7; margin-bottom: 24px; }
.modal-ref    { font-family: monospace; color: var(--gold); font-weight: 700; font-size: 15px; display: block; margin: 8px 0; }
.modal-btns   { display: flex; gap: 10px; }
.modal-btn-no {
  flex: 1; padding: 12px; background: transparent; border: 1.5px solid var(--bdr2);
  color: var(--text2); border-radius: 10px; font-size: 14px; cursor: pointer;
  transition: all .2s; font-family: var(--sans);
}
.modal-btn-no:hover { border-color: var(--gold); color: var(--gold); }
.modal-btn-yes {
  flex: 1; padding: 12px; background: var(--red); color: #fff;
  border: none; border-radius: 10px; font-size: 14px; font-weight: 700;
  cursor: pointer; transition: all .2s; font-family: var(--sans);
}
.modal-btn-yes:hover { opacity: .88; }
.modal-btn-yes:disabled { opacity: .5; cursor: not-allowed; }

/* Availability checker */
.avail-widget {
  background: var(--card); border: 1px solid var(--bdr2); border-radius: 18px;
  padding: 28px; margin-bottom: 28px;
}
.avail-widget h3 { font-family: var(--serif); font-size: 20px; font-weight: 400; margin-bottom: 18px; }
.avail-grid { display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 12px; align-items: end; }
@media(max-width:700px){ .avail-grid{ grid-template-columns: 1fr 1fr; } }
.avail-result {
  margin-top: 18px; padding: 14px 18px; border-radius: 12px;
  display: none; align-items: center; gap: 12px; font-size: 14px; font-weight: 500;
}
.avail-result.show { display: flex; }
.avail-result.ok   { background: var(--green-bg); color: var(--green); border: 1px solid rgba(45,106,79,.2); }
.avail-result.no   { background: var(--red-bg);   color: var(--red);   border: 1px solid rgba(155,35,53,.2); }
.avail-result.maint{ background: var(--amber-bg); color: var(--amber); border: 1px solid rgba(154,90,.2); }
</style>

<div class="bk-page">
  <div class="container">

    <!-- Page heading -->
    <div style="margin-bottom: 28px">
      <div class="section-badge"><i class="fas fa-calendar-check"></i> Reservations</div>
      <h1 style="font-family:var(--serif);font-size:clamp(28px,4vw,42px);font-weight:300;margin-bottom:8px">My Bookings</h1>
      <p style="color:var(--muted);font-size:15px">Manage your reservations and relive your Royale Vista experiences.</p>
    </div>

    <!-- Summary strip -->
    <div class="bk-summary">
      <div class="bk-sum-box">
        <div class="bk-sum-ico"><i class="fas fa-calendar-check"></i></div>
        <div>
          <div class="bk-sum-val"><?= $stats['total'] ?></div>
          <div class="bk-sum-lbl">Total Stays</div>
        </div>
      </div>
      <div class="bk-sum-box">
        <div class="bk-sum-ico"><i class="fas fa-bed"></i></div>
        <div>
          <div class="bk-sum-val"><?= $stats['active'] ?></div>
          <div class="bk-sum-lbl">Active</div>
        </div>
      </div>
      <div class="bk-sum-box">
        <div class="bk-sum-ico"><i class="fas fa-dollar-sign"></i></div>
        <div>
          <div class="bk-sum-val"><?= formatPrice($stats['spent']) ?></div>
          <div class="bk-sum-lbl">Total Spent</div>
        </div>
      </div>
      <div class="bk-sum-box">
        <div class="bk-sum-ico"><i class="fas fa-coins"></i></div>
        <div>
          <div class="bk-sum-val"><?= number_format($loyPts) ?></div>
          <div class="bk-sum-lbl">Loyalty Points</div>
        </div>
      </div>
    </div>

    <!-- Real-time availability checker -->
    <div class="avail-widget">
      <h3>
        <i class="fas fa-search" style="color:var(--gold);margin-right:10px;font-size:16px"></i>
        Check Real-Time Availability
        <span class="rt-live" style="margin-left:12px"><span class="rt-live-dot"></span> Live</span>
      </h3>
      <div class="avail-grid">
        <div class="form-group" style="margin:0">
          <label class="form-label">Check-in</label>
          <input type="date" class="form-control" id="avChIn" min="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">Check-out</label>
          <input type="date" class="form-control" id="avChOut">
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">Room Type</label>
          <select class="form-control" id="avRoomType">
            <option value="">Select type…</option>
            <?php
            $rtypes = $pdo->query("SELECT id, name FROM room_types ORDER BY price_usd")->fetchAll();
            foreach ($rtypes as $rt):
            ?>
            <option value="<?= $rt['id'] ?>"><?= htmlspecialchars($rt['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-gold" onclick="checkAvail()" id="avBtn" style="white-space:nowrap">
          <i class="fas fa-search"></i> Check
        </button>
      </div>
      <div class="avail-result" id="avResult"></div>
    </div>

    <!-- Filter tabs -->
    <div class="bk-tabs">
      <?php foreach ([
        'all'         => ['All',          'fa-list'],
        'confirmed'   => ['Upcoming',     'fa-clock'],
        'checked_in'  => ['In Residence', 'fa-bed'],
        'checked_out' => ['Past Stays',   'fa-history'],
        'cancelled'   => ['Cancelled',    'fa-times-circle'],
      ] as $v => [$l, $ico]): ?>
      <a href="?s=<?= $v ?>" class="bk-tab <?= $sf===$v?'active':'' ?>">
        <i class="fas <?= $ico ?>" style="font-size:11px;margin-right:5px"></i><?= $l ?>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Booking cards -->
    <?php if (empty($allBks)): ?>
    <div class="bk-empty">
      <div class="bk-empty-ico"><i class="fas fa-calendar-times"></i></div>
      <h3 style="font-family:var(--serif);font-size:26px;margin-bottom:10px">No reservations found</h3>
      <p style="color:var(--muted);margin-bottom:24px">Your next adventure awaits.</p>
      <a href="<?= $B ?>/rooms.php" class="btn btn-gold"><i class="fas fa-bed"></i> Browse Rooms</a>
    </div>
    <?php else: ?>
    <?php foreach ($allBks as $bk):
      $isCancelled = $bk['status'] === 'cancelled';
      $isCheckedOut= $bk['status'] === 'checked_out';
      $isConfirmed = $bk['status'] === 'confirmed';
      $canCancel   = $isConfirmed && strtotime($bk['check_in']) > strtotime('+2 days');
      $statusColors = [
        'confirmed'   => ['#c09b5b','rgba(192,155,91,.1)'],
        'checked_in'  => ['#22c55e','rgba(34,197,94,.1)'],
        'checked_out' => ['#60a5fa','rgba(96,165,250,.1)'],
        'cancelled'   => ['#ef4444','rgba(239,68,68,.1)'],
        'pending'     => ['#f59e0b','rgba(245,158,11,.1)'],
      ];
      [$sColor, $sBg] = $statusColors[$bk['status']] ?? ['#888','rgba(128,128,128,.1)'];
    ?>
    <div class="bk-card <?= $isCancelled?'cancelled':'' ?>" id="bkcard-<?= $bk['booking_ref'] ?>">

      <!-- Card header -->
      <div class="bk-head">
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
          <span class="bk-ref"><?= htmlspecialchars($bk['booking_ref']) ?></span>
          <span id="badge-<?= $bk['booking_ref'] ?>" class="badge" style="background:<?= $sBg ?>;color:<?= $sColor ?>">
            <?= strtoupper(str_replace('_',' ',$bk['status'])) ?>
          </span>
          <?php if ($bk['pay_status'] === 'paid'): ?>
          <span class="badge badge-green"><i class="fas fa-check-circle" style="font-size:9px"></i> Paid</span>
          <?php elseif ($bk['pay_status'] === 'pending'): ?>
          <span class="badge badge-amber"><i class="fas fa-clock" style="font-size:9px"></i> Pending</span>
          <?php endif; ?>
        </div>
        <span class="bk-created">Booked <?= date('d M Y', strtotime($bk['created_at'])) ?></span>
      </div>

      <!-- Card body -->
      <div class="bk-body">

        <!-- Dates -->
        <div>
          <div class="bk-lbl">Dates</div>
          <div class="bk-val"><?= date('d M', strtotime($bk['check_in'])) ?> — <?= date('d M Y', strtotime($bk['check_out'])) ?></div>
          <div class="bk-sub"><?= nightsBetween($bk['check_in'],$bk['check_out']) ?> night<?= nightsBetween($bk['check_in'],$bk['check_out'])!=1?'s':'' ?></div>
        </div>

        <!-- Room -->
        <div>
          <div class="bk-lbl">Accommodation</div>
          <div class="bk-val"><?= htmlspecialchars($bk['rooms_str'] ?? 'Bespoke Selection') ?></div>
          <div class="bk-sub"><?= htmlspecialchars($bk['guest_name']) ?></div>
        </div>

        <!-- Guests -->
        <div>
          <div class="bk-lbl">Guests</div>
          <div class="bk-val"><?= (int)$bk['adults'] ?> adult<?= $bk['adults']!=1?'s':'' ?><?= $bk['children']>0?' + '.$bk['children'].' child'.($bk['children']!=1?'ren':''):'' ?></div>
          <div class="bk-sub">Property: <?= htmlspecialchars($bk['property_name'] ?? $bk['location_name'] ?? 'Royale Vista Dubai') ?></div>
        </div>

        <!-- Total -->
        <div>
          <div class="bk-lbl">Total</div>
          <div class="bk-price"><?= formatPrice($bk['final_usd']) ?></div>
          <?php if ($bk['offer_code']): ?>
          <div class="bk-sub"><i class="fas fa-tag" style="color:var(--gold);font-size:10px"></i> <?= htmlspecialchars($bk['offer_code']) ?></div>
          <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="bk-acts">
          <a href="<?= $B ?>/invoice.php?ref=<?= urlencode($bk['booking_ref']) ?>" target="_blank" class="btn btn-ghost btn-sm" style="justify-content:center">
            <i class="fas fa-file-invoice"></i> Invoice
          </a>
          <?php if ($isConfirmed): ?>
          <button onclick="refreshStatus('<?= $bk['booking_ref'] ?>')" class="btn btn-ghost btn-sm" style="justify-content:center" title="Refresh status from server">
            <i class="fas fa-sync-alt" id="sync-<?= $bk['booking_ref'] ?>"></i> Refresh
          </button>
          <?php endif; ?>
          <?php if ($isConfirmed && !$isCancelled): ?>
            <?php if ($canCancel): ?>
            <button onclick="showCancel('<?= $bk['booking_ref'] ?>')" class="btn btn-sm" style="justify-content:center;background:transparent;border:1.5px solid rgba(239,68,68,.3);color:var(--red)">
              <i class="fas fa-times"></i> Cancel
            </button>
            <?php else: ?>
            <button disabled class="btn btn-sm" style="justify-content:center;background:transparent;border:1.5px solid var(--bdr2);color:var(--muted);cursor:not-allowed;opacity:.6" title="Check-in is within 48 hours — contact reception to cancel">
              <i class="fas fa-lock" style="font-size:10px"></i> Cancel (call us)
            </button>
            <?php endif; ?>
          <?php endif; ?>
        </div>

      </div><!-- /bk-body -->
    </div><!-- /bk-card -->
    <?php endforeach; ?>
    <?php endif; ?>

  </div>
</div>

<!-- Cancel Confirmation Modal -->
<div class="modal-back" id="cancelModal" onclick="if(event.target===this)closeCancel()">
  <div class="modal-box">
    <div style="font-size:48px;line-height:1">🥀</div>
    <h3>Cancel Reservation?</h3>
    <p>This action is <strong>irreversible</strong>. Any loyalty points earned will be partially refunded.<span class="modal-ref" id="modalRef">—</span></p>
    <div class="modal-btns">
      <button class="modal-btn-no" onclick="closeCancel()">Keep Reservation</button>
      <button class="modal-btn-yes" id="cancelYesBtn" onclick="doCancel()">
        <i class="fas fa-times-circle"></i> Confirm Cancellation
      </button>
    </div>
  </div>
</div>

<script>
const BASE = '<?= $B ?>';
let cancelRef = null;

/* ── Cancel flow ── */
function showCancel(ref) {
  cancelRef = ref;
  document.getElementById('modalRef').textContent = ref;
  document.getElementById('cancelModal').classList.add('open');
}
function closeCancel() {
  cancelRef = null;
  document.getElementById('cancelModal').classList.remove('open');
}
async function doCancel() {
  if (!cancelRef) return;
  const ref = cancelRef;
  const btn = document.getElementById('cancelYesBtn');
  btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Processing…';
  btn.disabled = true;
  try {
    const fd = new FormData();
    fd.append('action','cancel');
    fd.append('booking_ref', ref);
    const res  = await fetch(BASE + '/api/book.php', { method:'POST', body:fd });
    const data = await res.json();
    if (data.ok) {
      // Update UI without page reload
      const badge = document.getElementById('badge-' + ref);
      if (badge) { badge.style.background = 'rgba(239,68,68,.1)'; badge.style.color = '#ef4444'; badge.textContent = 'CANCELLED'; }
      const card = document.getElementById('bkcard-' + ref);
      if (card) { card.classList.add('cancelled'); }
      // Remove cancel button
      document.querySelectorAll(`#bkcard-${ref} .bk-acts button`).forEach(b => { if(b.textContent.trim().includes('Cancel')) b.remove(); });
      toast('Reservation ' + ref + ' cancelled successfully.', 'success');
      closeCancel();
    } else {
      toast(data.error || 'Cancellation failed. Please try again.', 'error');
    }
  } catch (e) {
    toast('Connection error. Please check your network.', 'error');
  }
  btn.innerHTML = '<i class="fas fa-times-circle"></i> Confirm Cancellation';
  btn.disabled = false;
}

/* ── Real-time status refresh ── */
async function refreshStatus(ref) {
  const ico = document.getElementById('sync-' + ref);
  if (ico) ico.classList.add('fa-spin');
  try {
    const res  = await fetch(BASE + '/api/book.php?action=status&ref=' + encodeURIComponent(ref));
    const data = await res.json();
    if (data.ok) {
      const statusColors = {
        confirmed:   ['#c09b5b','rgba(192,155,91,.1)'],
        checked_in:  ['#22c55e','rgba(34,197,94,.1)'],
        checked_out: ['#60a5fa','rgba(96,165,250,.1)'],
        cancelled:   ['#ef4444','rgba(239,68,68,.1)'],
        pending:     ['#f59e0b','rgba(245,158,11,.1)'],
      };
      const badge = document.getElementById('badge-' + ref);
      const s = data.booking.status;
      if (badge && statusColors[s]) {
        const [c, bg] = statusColors[s];
        badge.style.color = c; badge.style.background = bg;
        badge.textContent = s.replace(/_/g,' ').toUpperCase();
      }
      toast('Status updated: ' + s.replace(/_/g,' '), 'info');
    }
  } catch(e) { toast('Could not refresh status.', 'error'); }
  if (ico) setTimeout(()=>ico.classList.remove('fa-spin'), 600);
}

/* ── Availability checker ── */
async function checkAvail() {
  const chIn   = document.getElementById('avChIn').value;
  const chOut  = document.getElementById('avChOut').value;
  const typeId = document.getElementById('avRoomType').value;
  const result = document.getElementById('avResult');
  const btn    = document.getElementById('avBtn');

  if (!chIn || !chOut || !typeId) {
    result.className = 'avail-result show no';
    result.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please fill in all fields.';
    return;
  }
  if (new Date(chOut) <= new Date(chIn)) {
    result.className = 'avail-result show no';
    result.innerHTML = '<i class="fas fa-exclamation-circle"></i> Check-out must be after check-in.';
    return;
  }

  btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Checking…';
  btn.disabled = true;
  result.className = 'avail-result';

  try {
    const fd = new FormData();
    fd.append('checkin', chIn);
    fd.append('checkout', chOut);
    fd.append('room_type_id', typeId);
    const res  = await fetch(BASE + '/api/availability.php', { method:'POST', body:fd });
    const data = await res.json();

    if (data.error) {
      result.className = 'avail-result show no';
      result.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${data.error}`;
    } else if (data.available) {
      const nights = data.nights;
      result.className = 'avail-result show ok';
      result.innerHTML = `
        <i class="fas fa-check-circle" style="font-size:22px;flex-shrink:0"></i>
        <div>
          <strong>${data.count} room${data.count>1?'s':''} available</strong> — ${data.room}<br>
          <span style="font-size:13px">${nights} night${nights>1?'s':''} · ${data.total} total</span>
          <a href="${BASE}/rooms.php" class="btn btn-gold btn-sm" style="margin-left:14px;display:inline-flex">
            <i class="fas fa-bed"></i> Book Now
          </a>
        </div>`;
    } else {
      result.className = 'avail-result show no';
      result.innerHTML = `<i class="fas fa-times-circle" style="font-size:22px;flex-shrink:0"></i>
        <div><strong>No rooms available</strong> for those dates.<br>
        <span style="font-size:13px">Try different dates or room type.</span></div>`;
    }
  } catch(e) {
    result.className = 'avail-result show no';
    result.innerHTML = '<i class="fas fa-wifi"></i> Connection error. Please try again.';
  }

  btn.innerHTML = '<i class="fas fa-search"></i> Check';
  btn.disabled = false;
}

// Set min checkout when checkin changes
document.getElementById('avChIn').addEventListener('change', function(){
  const d = new Date(this.value);
  d.setDate(d.getDate()+1);
  document.getElementById('avChOut').min = d.toISOString().split('T')[0];
  if (document.getElementById('avChOut').value && new Date(document.getElementById('avChOut').value) <= new Date(this.value)) {
    document.getElementById('avChOut').value = '';
  }
});

// Auto-refresh confirmed bookings status every 60s
setInterval(async () => {
  document.querySelectorAll('.bk-card:not(.cancelled)').forEach(card => {
    const ref = card.id.replace('bkcard-','');
    if (ref) refreshStatus(ref);
  });
}, 60000);
</script>

<?php require __DIR__ . '/footer.php'; ?>
