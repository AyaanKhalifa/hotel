<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Rooms & Suites — Royale Vista';

// Filters
$checkin     = clean($_GET['checkin']  ?? date('Y-m-d'));
$checkout    = clean($_GET['checkout'] ?? date('Y-m-d', strtotime('+2 days')));
$guestFilter = max(0, (int)($_GET['guests'] ?? 0));
$sortBy      = clean($_GET['sort']    ?? 'price_asc');
$maxPrice    = (float)($_GET['max']   ?? 0);
$typeFilter  = (int)($_GET['type']    ?? 0);

// All room types with live data
$roomTypes = $pdo->query("
    SELECT rt.*,
        (SELECT image_url FROM room_images ri WHERE ri.room_type_id=rt.id AND ri.is_primary=1 LIMIT 1) AS primary_img,
        (SELECT AVG(rating) FROM room_ratings rr WHERE rr.room_type_id=rt.id AND rr.is_approved=1) AS live_rating,
        (SELECT COUNT(*) FROM room_ratings rr WHERE rr.room_type_id=rt.id AND rr.is_approved=1) AS live_reviews,
        0 AS avail_rooms
    FROM room_types rt ORDER BY rt.sort_order
")->fetchAll();

// Refresh initial availability per selected date range (prevents stale sold-out labels).
try {
  $avStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM rooms r
    WHERE r.room_type_id=?
      AND r.status='available'
      AND r.id NOT IN (
        SELECT DISTINCT bra.room_id
        FROM booking_room_assignments bra
        JOIN bookings b ON b.booking_ref = bra.booking_ref
        WHERE bra.room_type_id=?
          AND b.status NOT IN ('cancelled','checked_out')
          AND b.check_in <= ?
          AND b.check_out > ?
      )
  ");
  foreach ($roomTypes as &$rt) {
    $avStmt->execute([$rt['id'], $rt['id'], $checkout, $checkin]);
    $rt['avail_rooms'] = (int)$avStmt->fetchColumn();
  }
  unset($rt);
} catch (Exception $e) {}

// All images
$allImages = []; $allFacs = [];
foreach ($pdo->query("SELECT * FROM room_images ORDER BY room_type_id, is_primary DESC, sort_order")->fetchAll() as $img)
    $allImages[$img['room_type_id']][] = $img;
foreach ($pdo->query("SELECT * FROM room_facilities ORDER BY room_type_id, sort_order")->fetchAll() as $f)
    $allFacs[$f['room_type_id']][] = $f;

// Recent reviews per type (last 2)
$reviewMap = [];
foreach ($pdo->query("SELECT rr.*, rt.id as rt_id FROM room_ratings rr JOIN room_types rt ON rr.room_type_id=rt.id WHERE rr.is_approved=1 ORDER BY rr.created_at DESC")->fetchAll() as $rv)
    if (count($reviewMap[$rv['rt_id']] ?? []) < 2) $reviewMap[$rv['rt_id']][] = $rv;

// Wishlist
$wishlist = [];
if (isLoggedIn()) {
    $wl = $pdo->prepare("SELECT room_type_id FROM wishlists WHERE user_id=?");
    $wl->execute([$_SESSION['user_id']]);
    $wishlist = array_column($wl->fetchAll(), 'room_type_id');
}

// Loyalty balance
$loyaltyBal = 0;
if (isLoggedIn()) {
    $lp = $pdo->prepare("SELECT total_points FROM loyalty_points WHERE user_id=?");
    $lp->execute([$_SESSION['user_id']]);
    $loyaltyBal = (int)($lp->fetchColumn() ?? 0);
}

// Membership
$userMembership = null;
if (isLoggedIn()) {
    $ms = $pdo->prepare("SELECT um.member_number,m.name,m.discount_pct FROM user_memberships um JOIN memberships m ON um.membership_id=m.id WHERE um.user_id=? AND um.status='active' ORDER BY m.discount_pct DESC LIMIT 1");
    $ms->execute([$_SESSION['user_id']]);
    $userMembership = $ms->fetch();
}

// Apply filters
$filtered = array_filter($roomTypes, function($rt) use ($typeFilter,$guestFilter,$maxPrice) {
    if ($typeFilter && $rt['id'] != $typeFilter) return false;
    if ($guestFilter && $rt['max_guests'] < $guestFilter) return false;
    if ($maxPrice && $rt['price_usd'] > $maxPrice) return false;
    return true;
});
usort($filtered, fn($a,$b) => ['price_desc' => $b['price_usd'] <=> $a['price_usd'], 'rating' => ($b['live_rating']??4.5) <=> ($a['live_rating']??4.5)][$sortBy] ?? ($a['price_usd'] <=> $b['price_usd']));

require __DIR__ . '/header.php';
?>

<!-- Anime.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>

<style>
/* ── Page base ── */
.rp { padding-top: 0; min-height: 100vh; }

/* ── Cart Bar ── */
.cart-bar {
  position: fixed; bottom: 0; left: 0; right: 0; z-index: 500;
  background: linear-gradient(135deg, #1c1813, #2d2218);
  border-top: 2px solid var(--gold); padding: 14px 24px;
  display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
  transform: translateY(100%); transition: transform .35s cubic-bezier(.34,1.3,.64,1);
  box-shadow: 0 -8px 40px rgba(0,0,0,.45);
}
.cart-bar.show { transform: translateY(0); }
.cart-bar-items { flex: 1; display: flex; gap: 10px; flex-wrap: wrap; overflow-x: auto; }
.cart-chip {
  background: rgba(255,255,255,.08); border: 1px solid rgba(192,155,91,.35);
  border-radius: 20px; padding: 5px 12px; font-size: 12px; color: rgba(255,255,255,.85);
  display: flex; align-items: center; gap: 7px; white-space: nowrap;
}
.cart-chip-rm { background: none; border: none; color: rgba(255,255,255,.4); cursor: pointer; font-size: 14px; padding: 0 0 0 2px; line-height: 1; }
.cart-chip-rm:hover { color: var(--red); }
.cart-bar-total { font-family: var(--serif); font-size: 20px; color: var(--gold); white-space: nowrap; }
.cart-bar-btn { background: var(--gold); color: #000; border: none; border-radius: 10px; padding: 11px 24px; font-family: var(--cinzel); font-size: 10px; letter-spacing: 2px; cursor: pointer; transition: background .2s; white-space: nowrap; }
.cart-bar-btn:hover { background: var(--gold-dk); }
.cart-btn-add { font-size: 12px; padding: 8px 14px; border-radius: var(--radius); border: 1px solid var(--gold); background: var(--gold-dim); color: var(--gold); cursor: pointer; font-family: var(--sans); display: inline-flex; align-items: center; gap: 5px; transition: all .2s; }
.cart-btn-add:hover,.cart-btn-add.in-cart { background: var(--gold); color: #000; }

/* ── Hero ── */
.rp-hero {
  padding: 98px 0 0; position: relative;
  background: linear-gradient(160deg, #1a1612 0%, #2d2720 55%, var(--bg2) 100%);
  border-bottom: 1px solid var(--bdr2);
  min-height: 220px;
}
.rp-hero-title { color: #fff !important; }
.rp-hero-sub { color: rgba(255,255,255,.65) !important; }
.rp-hero-inner { display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 16px; padding-bottom: 24px; }
.rp-hero-title { font-family: var(--serif); font-size: clamp(26px,4vw,46px); font-weight: 400; line-height: 1.1; }
.rp-hero-sub   { font-size: 14px; color: var(--text2); margin-top: 8px; }

/* ── Search bar ── */
.srch-bar {
  background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-lg);
  padding: 16px 20px; display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;
  box-shadow: var(--shadow2); position: relative; z-index: 10; margin-bottom: 28px;
}
.srch-grp { display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 120px; }
.srch-lbl { font-size: 10px; color: var(--gold); letter-spacing: 1.5px; text-transform: uppercase; font-weight: 600; }
.srch-ctrl { background: var(--input); border: 1px solid var(--bdr2); border-radius: 8px; color: var(--text); font-family: var(--sans); font-size: 13.5px; padding: 9px 12px; outline: none; transition: border-color .2s; width: 100%; }
.srch-ctrl:focus { border-color: var(--gold); }
.srch-ctrl option { background: var(--card); }
.srch-btn { padding: 10px 24px; background: linear-gradient(135deg,var(--gold),var(--gold-dk)); color: #000; border: none; border-radius: 9px; font-family: var(--sans); font-size: 14px; font-weight: 700; cursor: pointer; white-space: nowrap; transition: all .2s; align-self: flex-end; }
.srch-btn:hover { transform: translateY(-2px); box-shadow: var(--gold-glow); }

/* ── Type filter pills ── */
.type-pills { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 24px; }
.tpill { padding: 7px 18px; border-radius: 24px; font-size: 13px; font-weight: 500; border: 1.5px solid var(--border); color: var(--text2); background: transparent; cursor: pointer; transition: all .2s; text-decoration: none; display: flex; align-items: center; gap: 6px; }
.tpill:hover, .tpill.act { background: var(--gold); color: #000; border-color: var(--gold); font-weight: 700; }
.tpill-price { font-size: 11px; background: var(--gold-dim); color: var(--gold); border-radius: 10px; padding: 1px 7px; }
.tpill.act .tpill-price { background: rgba(0,0,0,.15); color: #000; }
.results-meta { font-size: 13px; color: var(--muted); margin-bottom: 22px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }

/* ── ROOM CARDS (matching reference) ── */
.room-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(320px,1fr)); gap: 22px; }
.rc {
  background: var(--card); border: 1px solid var(--bdr2);
  border-radius: 16px; overflow: hidden; display: flex; flex-direction: column;
  transition: box-shadow .3s, transform .3s, border-color .3s;
  cursor: default; position: relative;
}
.rc:hover { transform: translateY(-5px); box-shadow: 0 12px 48px rgba(0,0,0,.18); border-color: rgba(212,175,55,.25); }
[data-theme="light"] .rc { box-shadow: 0 2px 16px rgba(0,0,0,.07); }
[data-theme="light"] .rc:hover { box-shadow: 0 12px 40px rgba(0,0,0,.13); }

/* Image area */
.rc-img { position: relative; height: 210px; overflow: hidden; background: var(--card2); display: flex; align-items: center; justify-content: center; font-size: 56px; }
.rc-img img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transition: transform .5s ease; }
.rc:hover .rc-img img { transform: scale(1.07); }
/* Top badges */
.rc-badges { position: absolute; top: 12px; left: 12px; display: flex; gap: 6px; z-index: 2; }
.rc-badge { padding: 4px 11px; border-radius: 20px; font-size: 11px; font-weight: 700; display: flex; align-items: center; gap: 5px; }
.badge-avail { background: #22c55e; color: #fff; }
.badge-occup { background: #ef4444; color: #fff; }
.badge-maint { background: #f59e0b; color: #000; }
.badge-bfast { background: #f59e0b; color: #000; }
/* Price box on image */
.rc-price-box { position: absolute; bottom: 12px; left: 12px; background: rgba(255,255,255,.95); backdrop-filter: blur(8px); border-radius: 10px; padding: 8px 14px; z-index: 2; }
[data-theme="dark"] .rc-price-box { background: rgba(22,22,31,.92); }
.rc-price-num { font-family: var(--serif); font-size: 22px; font-weight: 600; color: var(--text); line-height: 1; }
.rc-price-unit { font-size: 11px; color: var(--muted); }
/* Wishlist / compare buttons */
.rc-actions { position: absolute; bottom: 14px; right: 12px; display: flex; gap: 6px; z-index: 2; }
.rc-act-btn { width: 34px; height: 34px; border-radius: 50%; background: rgba(255,255,255,.85); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; transition: all .2s; backdrop-filter: blur(6px); }
[data-theme="dark"] .rc-act-btn { background: rgba(30,30,40,.85); }
.rc-act-btn:hover { background: var(--gold); color: #000; }
.rc-act-btn.wished { background: #ef4444; color: #fff; }

/* Card body */
.rc-body { padding: 18px 20px 0; flex: 1; display: flex; flex-direction: column; }
/* Name + rating row */
.rc-title-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 6px; }
.rc-name { font-family: var(--serif); font-size: 20px; font-weight: 400; color: var(--text); line-height: 1.2; flex: 1; }
.rc-rating-pill { display: flex; align-items: center; gap: 5px; background: var(--card2); border: 1px solid var(--bdr2); border-radius: 20px; padding: 4px 10px; flex-shrink: 0; }
.rc-stars { color: #f59e0b; font-size: 12px; letter-spacing: 1px; }
.rc-rating-num { font-size: 13px; font-weight: 700; }
.rc-review-ct  { font-size: 11px; color: var(--muted); }
.rc-desc { font-size: 13px; color: var(--text2); line-height: 1.6; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid var(--bdr2); }
/* Meta: guests, size */
.rc-meta { display: flex; gap: 14px; font-size: 12px; color: var(--text2); margin-bottom: 12px; flex-wrap: wrap; }
.rc-meta span { display: flex; align-items: center; gap: 5px; }
.rc-meta i { color: var(--gold); font-size: 11px; }
/* Amenities */
.rc-fac-title { font-size: 11px; color: var(--gold); font-weight: 600; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; cursor: pointer; }
.rc-fac-title i { font-size: 10px; }
.rc-facs { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 14px; }
.rc-fac { background: var(--card2); border: 1px solid var(--bdr2); border-radius: 16px; padding: 3px 10px; font-size: 11px; color: var(--text2); display: flex; align-items: center; gap: 5px; }
.rc-fac i { color: var(--gold); font-size: 10px; }
/* Recent reviews mini */
.rc-reviews-hd { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.rc-reviews-lbl { font-size: 11px; color: var(--text2); font-weight: 600; display: flex; align-items: center; gap: 6px; }
.rc-reviews-link { font-size: 11px; color: var(--gold); font-weight: 600; text-decoration: none; }
.rc-reviews-link:hover { text-decoration: underline; }
.rc-mini-review { font-size: 12px; color: var(--muted); font-style: italic; padding: 6px 0; border-bottom: 1px solid var(--bdr2); line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.rc-mini-review:last-child { border-bottom: none; }
.rc-no-review { display: flex; flex-direction: column; align-items: center; padding: 14px 0; color: var(--muted); }
.rc-no-review i { font-size: 22px; margin-bottom: 5px; opacity: .3; }
.rc-no-review span { font-size: 11px; }
/* Footer buttons */
.rc-footer { display: flex; gap: 10px; padding: 16px 20px; border-top: 1px solid var(--bdr2); margin-top: auto; }
.rc-btn-book { flex: 1; padding: 10px; background: var(--gold); color: #000; border: none; border-radius: 9px; font-family: var(--sans); font-size: 13.5px; font-weight: 700; cursor: pointer; transition: all .2s; display: flex; align-items: center; justify-content: center; gap: 7px; }
.rc-btn-book:hover:not(:disabled) { background: var(--gold-dk); color: #fff; transform: translateY(-1px); box-shadow: var(--gold-glow); }
.rc-btn-book:disabled { background: var(--card2); color: var(--muted); border: 1px solid var(--bdr2); cursor: not-allowed; opacity: .6; }
.rc-btn-review { flex: 1; padding: 10px; background: transparent; color: var(--text2); border: 1.5px solid var(--border); border-radius: 9px; font-family: var(--sans); font-size: 13.5px; font-weight: 600; cursor: pointer; transition: all .2s; display: flex; align-items: center; justify-content: center; gap: 7px; text-decoration: none; }
.rc-btn-review:hover { border-color: var(--gold); color: var(--gold); }
/* Availability indicator */
.rc-avail-bar { height: 3px; position: absolute; top: 0; left: 0; right: 0; }
.rc-avail-bar.avail { background: linear-gradient(90deg, #22c55e, #16a34a); }
.rc-avail-bar.occup { background: linear-gradient(90deg, #ef4444, #dc2626); }
.rc-avail-bar.maint { background: linear-gradient(90deg, #f59e0b, #d97706); }

/* ── BOOKING MODAL (matching reference image 2) ── */
.bk-backdrop {
  position: fixed; inset: 0; background: rgba(0,0,0,.65); z-index: 1000;
  display: none; align-items: flex-start; justify-content: center;
  padding: 20px; overflow-y: auto;
  backdrop-filter: blur(4px);
}
.bk-backdrop.open { display: flex; }
.bk-modal {
  background: var(--card); border-radius: 16px; width: 540px; max-width: 100%;
  margin: auto; box-shadow: 0 24px 80px rgba(0,0,0,.35);
  border: 1px solid var(--border);
  animation: modalSlide .35s cubic-bezier(.34,1.56,.64,1);
  overflow: hidden;
}
@keyframes modalSlide { from{opacity:0;transform:translateY(30px) scale(.95)} to{opacity:1;transform:none} }
.bk-hd {
  padding: 18px 22px; border-bottom: 1px solid var(--bdr2);
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, var(--card2), var(--card));
  position: sticky; top: 0; z-index: 2;
}
.bk-hd-title { font-family: var(--serif); font-size: 20px; font-weight: 400; display: flex; align-items: center; gap: 9px; }
.bk-hd-close { background: var(--card2); border: 1px solid var(--bdr2); border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 18px; color: var(--muted); transition: all .2s; }
.bk-hd-close:hover { background: var(--red-bg); color: var(--red); border-color: var(--red); }

/* Sections */
.bk-sec { border: 1px solid var(--bdr2); border-radius: 12px; overflow: hidden; margin: 0 18px 14px; }
.bk-sec-hd { background: var(--card2); padding: 12px 16px; font-size: 13.5px; font-weight: 700; display: flex; align-items: center; gap: 8px; color: var(--text); }
.bk-sec-hd i { color: var(--gold); }
.bk-sec-bd { padding: 14px 16px; }

/* Room selector rows */
.room-sel-row {
  display: flex; align-items: center; gap: 12px; padding: 11px 14px;
  border: 2px solid var(--bdr2); border-radius: 10px; margin-bottom: 8px;
  cursor: pointer; transition: all .2s; background: var(--card);
}
.room-sel-row:hover { border-color: var(--gold-dk); background: var(--gold-dim); }
.room-sel-row.selected { border-color: #2563eb; background: rgba(37,99,235,.07); }
.room-sel-chk { width: 20px; height: 20px; border-radius: 5px; border: 2px solid var(--border); background: var(--card); display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all .2s; }
.room-sel-row.selected .room-sel-chk { background: #2563eb; border-color: #2563eb; }
.room-sel-chk i { font-size: 10px; color: #fff; display: none; }
.room-sel-row.selected .room-sel-chk i { display: block; }
.room-sel-name { flex: 1; font-size: 13.5px; font-weight: 600; }
.room-sel-price { font-size: 12px; color: var(--gold); font-weight: 700; }
/* Qty control */
.qty-wrap { display: flex; align-items: center; gap: 6px; }
.qty-btn { width: 26px; height: 26px; border-radius: 6px; border: 1px solid var(--border); background: var(--card2); cursor: pointer; font-size: 16px; color: var(--text2); display: flex; align-items: center; justify-content: center; transition: all .15s; font-family: var(--sans); }
.qty-btn:hover { border-color: var(--gold); color: var(--gold); }
.qty-num { width: 24px; text-align: center; font-size: 14px; font-weight: 700; }
/* Add room type */
.add-rt-btn { width: 100%; padding: 10px; border: 2px dashed var(--border); border-radius: 10px; background: transparent; color: var(--muted); cursor: pointer; font-family: var(--sans); font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 7px; transition: all .2s; margin-top: 4px; }
.add-rt-btn:hover { border-color: var(--gold); color: var(--gold); }

/* Form controls in modal */
.bk-field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.bk-fg { display: flex; flex-direction: column; gap: 4px; }
.bk-lbl { font-size: 11px; color: var(--muted); font-weight: 600; letter-spacing: .5px; display: flex; align-items: center; gap: 5px; }
.bk-lbl span.req { color: var(--red); }
.bk-ctrl { background: var(--input); border: 1.5px solid var(--border); border-radius: 8px; color: var(--text); font-family: var(--sans); font-size: 13.5px; padding: 9px 12px; outline: none; width: 100%; transition: border-color .2s; }
.bk-ctrl:focus { border-color: var(--gold); box-shadow: 0 0 0 3px rgba(212,175,55,.1); }
.bk-ctrl::placeholder { color: var(--muted); }
.bk-ctrl option { background: var(--card); }

/* Pay method */
.pay-methods { display: flex; flex-direction: column; gap: 8px; }
.pay-m { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border: 2px solid var(--bdr2); border-radius: 10px; cursor: pointer; transition: all .2s; }
.pay-m:hover { border-color: var(--border); }
.pay-m.sel { border-color: #2563eb; background: rgba(37,99,235,.06); }
.pay-m-radio { width: 18px; height: 18px; border-radius: 50%; border: 2px solid var(--border); display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all .2s; }
.pay-m.sel .pay-m-radio { border-color: #2563eb; }
.pay-m.sel .pay-m-radio::after { content:''; width: 8px; height: 8px; border-radius: 50%; background: #2563eb; display: block; }
.pay-m-icon { font-size: 22px; }
.pay-m-name { font-size: 13.5px; font-weight: 600; flex: 1; }
.pay-m-sub  { font-size: 11px; color: var(--muted); }
.pay-extra  { margin-top: 10px; padding: 12px; background: var(--card2); border-radius: 8px; display: none; }
.pay-extra.open { display: block; }

/* Offer + loyalty */
.code-row { display: flex; gap: 8px; }
.code-btn { padding: 9px 16px; background: var(--gold); color: #000; border: none; border-radius: 8px; font-family: var(--sans); font-size: 13px; font-weight: 700; cursor: pointer; white-space: nowrap; transition: all .2s; }
.code-btn:hover { background: var(--gold-dk); }
.code-msg { font-size: 12px; margin-top: 6px; min-height: 16px; }
.code-msg.ok  { color: var(--green); }
.code-msg.err { color: var(--red); }

/* Booking summary */
.bk-sum { border: 1px solid var(--bdr2); border-radius: 12px; overflow: hidden; margin: 0 18px 14px; }
.bk-sum-hd { background: var(--card2); padding: 12px 16px; font-size: 13.5px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
.bk-sum-hd i { color: var(--gold); }
.bk-sum-bd { padding: 12px 16px; }
.sum-row { display: flex; justify-content: space-between; font-size: 13px; padding: 7px 0; border-bottom: 1px solid var(--bdr2); color: var(--text2); }
.sum-row:last-child { border-bottom: none; }
.sum-row.disc { color: var(--green); }
.sum-row.total { font-size: 15px; font-weight: 700; color: var(--text); padding-top: 12px; }
.sum-row.total .sum-val { font-family: var(--serif); font-size: 22px; color: var(--gold); }
.sum-avail-note { font-size: 11.5px; color: var(--muted); padding: 8px 0; }
.sum-avail-note.err { color: var(--red); }
.sum-avail-note.ok  { color: var(--green); }

/* Footer buttons */
.bk-ft { padding: 14px 18px; display: flex; gap: 10px; border-top: 1px solid var(--bdr2); background: var(--card); position: sticky; bottom: 0; }
.bk-ft-cancel { flex: 1; padding: 12px; border: 1.5px solid var(--border); background: transparent; color: var(--text2); border-radius: 10px; font-family: var(--sans); font-size: 14px; font-weight: 600; cursor: pointer; transition: all .2s; }
.bk-ft-cancel:hover { border-color: var(--red); color: var(--red); }
.bk-ft-book { flex: 2; padding: 12px; background: var(--gold); color: #000; border: none; border-radius: 10px; font-family: var(--sans); font-size: 14px; font-weight: 700; cursor: pointer; transition: all .2s; display: flex; align-items: center; justify-content: center; gap: 8px; }
.bk-ft-book:hover:not(:disabled) { background: var(--gold-dk); color: #fff; transform: translateY(-2px); box-shadow: var(--gold-glow); }
.bk-ft-book:disabled { opacity: .5; cursor: not-allowed; }

/* ── SUCCESS STATE ── */
.bk-success { text-align: center; padding: 40px 28px; display: none; }
.bk-success-icon { font-size: 64px; margin-bottom: 16px; animation: bounceIn .6s; }
@keyframes bounceIn { 0%{transform:scale(0)} 70%{transform:scale(1.1)} 100%{transform:scale(1)} }
.bk-ref-box { background: var(--gold-dim); border: 2px solid var(--gold); border-radius: 12px; padding: 14px 20px; display: inline-block; font-family: var(--serif); font-size: 22px; color: var(--gold); letter-spacing: 2px; margin: 14px 0; }
.bk-sum-success { background: var(--card2); border: 1px solid var(--bdr2); border-radius: 12px; padding: 16px 20px; margin: 14px 0; text-align: left; max-width: 360px; margin-left: auto; margin-right: auto; }

/* ── Loading state ── */
.bk-loading { text-align: center; padding: 40px; display: none; }
.spin { width: 48px; height: 48px; border: 3px solid var(--bdr2); border-top-color: var(--gold); border-radius: 50%; animation: spin .8s linear infinite; margin: 0 auto 16px; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Avail badge on card real-time update ── */
.avail-realtime { display: flex; align-items: center; gap: 5px; font-size: 11px; }
.avail-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.avail-dot.av { background: var(--green); animation: blink 2s infinite; }
.avail-dot.un { background: var(--red); }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.4} }

/* ── Responsive ── */
@media(max-width:768px) {
  .srch-bar { flex-direction: column; }
  .srch-grp { min-width: 100%; }
  .room-grid { grid-template-columns: 1fr; }
  .bk-field-grid { grid-template-columns: 1fr; }
}
</style>

<div class="rp">
  <!-- Hero -->
  <div class="rp-hero">
    <div class="container rp-hero-inner">
      <div>
        <div class="section-label" id="heroLabel">Luxury Accommodations</div>
        <h1 class="rp-hero-title" id="heroTitle">Find Your<br><em style="color:var(--gold);font-style:normal">Perfect Room</em></h1>
        <p class="rp-hero-sub" id="heroSub"><?= count($filtered) ?> room type<?= count($filtered)!=1?'s':'' ?> · <?= $pdo->query("SELECT COUNT(*) FROM rooms WHERE status='available'")->fetchColumn() ?> rooms available now</p>
      </div>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <a href="<?= $B ?>/reviews.php" class="btn btn-outline btn-sm" style="gap:6px"><i class="fas fa-star"></i> All Reviews</a>
        <a href="<?= $B ?>/wishlist.php" class="btn btn-ghost btn-sm" style="gap:6px"><i class="fas fa-heart"></i> Wishlist</a>
      </div>
    </div>
  </div>

  <div class="container" style="padding-top:24px;padding-bottom:60px">

    <!-- Search bar -->
    <form id="filterForm" method="GET" action="">
      <div class="srch-bar" id="srchBar">
        <div class="srch-grp">
          <span class="srch-lbl">Check-In</span>
          <input type="date" name="checkin" class="srch-ctrl" id="ci" value="<?= htmlspecialchars($checkin) ?>" min="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="srch-grp">
          <span class="srch-lbl">Check-Out</span>
          <input type="date" name="checkout" class="srch-ctrl" id="co" value="<?= htmlspecialchars($checkout) ?>" min="<?= date('Y-m-d',strtotime('+1 day')) ?>" required>
        </div>
        <div class="srch-grp" style="max-width:110px">
          <span class="srch-lbl">Guests</span>
          <select name="guests" class="srch-ctrl">
            <option value="">Any</option>
            <?php for($g=1;$g<=6;$g++): ?><option value="<?=$g?>" <?=$guestFilter==$g?'selected':''?>><?=$g?> <?=$g==1?'Guest':'Guests'?></option><?php endfor; ?>
          </select>
        </div>
        <div class="srch-grp" style="max-width:130px">
          <span class="srch-lbl">Sort By</span>
          <select name="sort" class="srch-ctrl">
            <option value="price_asc"  <?=$sortBy==='price_asc'?'selected':''?>>Price ↑</option>
            <option value="price_desc" <?=$sortBy==='price_desc'?'selected':''?>>Price ↓</option>
            <option value="rating"     <?=$sortBy==='rating'?'selected':''?>>Top Rated</option>
          </select>
        </div>
        <?php if ($typeFilter): ?><input type="hidden" name="type" value="<?= $typeFilter ?>"><?php endif; ?>
        <button type="submit" class="srch-btn"><i class="fas fa-search" style="margin-right:6px"></i>Search</button>
        <?php if ($typeFilter||$guestFilter||$maxPrice): ?><a href="<?= $B ?>/rooms.php?checkin=<?= $checkin ?>&checkout=<?= $checkout ?>" class="btn btn-ghost btn-sm" style="align-self:flex-end">✕ Clear</a><?php endif; ?>
      </div>
    </form>

    <!-- Type pills -->
    <div class="type-pills" id="typePills">
      <a href="?checkin=<?= $checkin ?>&checkout=<?= $checkout ?>&sort=<?= $sortBy ?>" class="tpill <?= !$typeFilter?'act':'' ?>">All Rooms</a>
      <?php foreach ($roomTypes as $rt): ?>
      <a href="?checkin=<?= $checkin ?>&checkout=<?= $checkout ?>&sort=<?= $sortBy ?>&type=<?= $rt['id'] ?>" class="tpill <?= $typeFilter==$rt['id']?'act':'' ?>">
        <?= htmlspecialchars($rt['name']) ?>
        <span class="tpill-price"><?= formatPrice($rt['price_usd']) ?></span>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Results meta -->
    <div class="results-meta" id="resultsMeta">
      <span><strong><?= count($filtered) ?></strong> room type<?= count($filtered)!=1?'s':'' ?> found · <?= nightsBetween($checkin,$checkout) ?> night<?= nightsBetween($checkin,$checkout)!=1?'s':'' ?>: <strong><?= date('d M',strtotime($checkin)) ?></strong> → <strong><?= date('d M Y',strtotime($checkout)) ?></strong></span>
      <span style="display:flex;align-items:center;gap:8px;font-size:12px;color:var(--muted)"><span class="avail-dot av"></span> Live availability</span>
    </div>

    <!-- ══ ROOM CARDS GRID ══ -->
    <div class="room-grid" id="roomGrid">
      <?php
      $emojis = [1=>'🛏',2=>'🛋',3=>'🏡',4=>'👑'];
      foreach (array_values($filtered) as $i => $rt):
        $imgs    = $allImages[$rt['id']] ?? [];
        $facs    = $allFacs[$rt['id']] ?? [];
        $reviews = $reviewMap[$rt['id']] ?? [];
        $rating  = $rt['live_rating'] ?? 4.5;
        $stars   = str_repeat('★',(int)round($rating)) . str_repeat('☆',5-(int)round($rating));
        $avail   = (int)$rt['avail_rooms'];
        $availClass = $avail > 0 ? 'avail' : 'un';
        $isWished = in_array($rt['id'], $wishlist);
        $hasBreakfast = (bool)$rt['has_breakfast'];
      ?>
      <div class="rc <?= $avail===0?'sold-out':'' ?>" id="rc-<?= $rt['id'] ?>" style="opacity:0;transform:translateY(30px)" data-id="<?= $rt['id'] ?>" data-price="<?= $rt['price_usd'] ?>">
        <div class="rc-avail-bar <?= $avail>0?'avail':'occup' ?>" id="avbar-<?= $rt['id'] ?>"></div>

        <!-- Image -->
        <div class="rc-img" id="rcimg-<?= $rt['id'] ?>" <?= !empty($imgs) ? 'onclick=\'openGallery('.htmlspecialchars(json_encode($imgs),ENT_QUOTES).')\' style="cursor:pointer"' : '' ?>>
          <?php if (!empty($imgs)): $m = $imgs[0]; $isVid = ($m['media_type']??'image')==='video'; ?>
          <?php if($isVid): ?>
             <video src="<?= htmlspecialchars($m['image_url']) ?>" style="width:100%;height:100%;object-fit:cover" autoplay muted loop playsinline></video>
             <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:42px;color:rgba(255,255,255,.7);pointer-events:none;z-index:2"><i class="far fa-play-circle"></i></div>
          <?php else: ?>
             <img src="<?= htmlspecialchars($m['image_url']) ?>" alt="<?= htmlspecialchars($rt['name']) ?>" id="mainimg-<?= $rt['id'] ?>" onerror="this.style.display='none'">
          <?php endif; ?>
          
          <?php if(count($imgs)>1): ?>
             <div style="position:absolute;top:12px;right:12px;background:rgba(0,0,0,.6);color:#fff;padding:4px 10px;border-radius:12px;font-size:11px;z-index:2;display:flex;align-items:center;gap:5px;backdrop-filter:blur(4px);pointer-events:none"><i class="fas fa-images"></i> <?=count($imgs)?> Media</div>
          <?php endif; ?>
          <?php else: ?>
          <span><?= $emojis[$rt['id']] ?? '🏨' ?></span>
          <?php endif; ?>

          <!-- Badges -->
          <div class="rc-badges">
            <span class="rc-badge <?= $avail>0?'badge-avail':'badge-occup' ?>" id="availbadge-<?= $rt['id'] ?>">
              <i class="fas fa-circle" style="font-size:7px"></i>
              <?= $avail>0 ? 'Available' : 'Sold Out' ?>
            </span>
            <?php if ($hasBreakfast): ?>
            <span class="rc-badge badge-bfast"><i class="fas fa-utensils" style="font-size:9px"></i> Breakfast</span>
            <?php endif; ?>
          </div>

          <!-- Price -->
          <div class="rc-price-box" id="rcprice-<?= $rt['id'] ?>">
            <div class="rc-price-num"><?= formatPrice($rt['price_usd']) ?></div>
            <div class="rc-price-unit">/night</div>
          </div>

          <!-- Actions -->
          <div class="rc-actions">
            <button class="rc-act-btn" onclick="event.stopPropagation();window.location='<?= $B ?>/booking-details.php?room=<?= $rt['id'] ?>&checkin='+encodeURIComponent(document.getElementById('ci')?.value||'<?= date('Y-m-d') ?>')+'&checkout='+encodeURIComponent(document.getElementById('co')?.value||'<?= date('Y-m-d',strtotime('+2 days')) ?>')" title="Select rooms">
              <i class="fas fa-layer-group" style="font-size:12px"></i>
            </button>
            <button class="rc-act-btn <?= $isWished?'wished':'' ?>"
                    id="wish-<?= $rt['id'] ?>"
                    onclick="event.stopPropagation();toggleWishlist(<?= $rt['id'] ?>,this)"
                    title="<?= $isWished?'Remove from':'Add to' ?> wishlist">
              <i class="fas fa-heart" style="font-size:12px"></i>
            </button>
          </div>
        </div>

        <!-- Body -->
        <div class="rc-body">
          <div class="rc-title-row">
            <div class="rc-name"><?= htmlspecialchars($rt['name']) ?></div>
            <div class="rc-rating-pill">
              <span class="rc-stars"><?= str_repeat('★',(int)round($rating)) ?></span>
              <span class="rc-rating-num"><?= number_format($rating,1) ?></span>
              <span class="rc-review-ct">(<?= $rt['live_reviews']??0 ?>)</span>
            </div>
          </div>

          <p class="rc-desc"><?= htmlspecialchars(mb_strimwidth($rt['description']??'',0,80,'…')) ?></p>

          <div class="rc-meta">
            <span><i class="fas fa-users"></i> <?= $rt['max_guests'] ?> Guests</span>
            <span><i class="fas fa-expand"></i> 45–85 m²</span>
            <?php if ($hasBreakfast): ?><span><i class="fas fa-coffee"></i> Breakfast</span><?php endif; ?>
            <span class="avail-realtime" id="availtxt-<?= $rt['id'] ?>">
              <span class="avail-dot <?= $availClass ?>"></span>
              <span id="availnum-<?= $rt['id'] ?>"><?= $avail > 0 ? $avail.' room'.($avail!=1?'s':'').' available' : 'Currently unavailable' ?></span>
            </span>
          </div>

          <!-- Amenities -->
          <?php if (!empty($facs)): ?>
          <div class="rc-fac-title" onclick="toggleFacs(<?= $rt['id'] ?>)">
            <i class="fas fa-concierge-bell"></i> Amenities
            <i class="fas fa-chevron-down" id="fac-arrow-<?= $rt['id'] ?>" style="margin-left:auto;transition:transform .2s"></i>
          </div>
          <div class="rc-facs" id="facs-<?= $rt['id'] ?>">
            <?php foreach (array_slice($facs,0,5) as $f): ?>
            <span class="rc-fac"><i class="<?= htmlspecialchars($f['icon']??'fas fa-check') ?>"></i><?= htmlspecialchars($f['name']) ?></span>
            <?php endforeach; ?>
            <?php if (count($facs)>5): ?><span class="rc-fac" style="cursor:pointer" onclick="window.location='<?= $B ?>/booking-details.php?room=<?= $rt['id'] ?>&checkin='+encodeURIComponent(document.getElementById('ci')?.value||'<?= date('Y-m-d') ?>')+'&checkout='+encodeURIComponent(document.getElementById('co')?.value||'<?= date('Y-m-d',strtotime('+2 days')) ?>')">+<?= count($facs)-5 ?> more</span><?php endif; ?>
          </div>
          <?php endif; ?>

          <!-- Recent Reviews -->
          <div class="rc-reviews-hd">
            <span class="rc-reviews-lbl"><i class="fas fa-comments" style="color:var(--gold)"></i> Recent Reviews</span>
            <a href="<?= $B ?>/reviews.php?room=<?= $rt['id'] ?>" class="rc-reviews-link">View All →</a>
          </div>
          <?php if (!empty($reviews)): ?>
          <?php foreach (array_slice($reviews,0,1) as $rv):
            $rvUser = $pdo->prepare("SELECT name,profile_img FROM users WHERE id=? LIMIT 1");
            $rvUser->execute([$rv['user_id']]);
            $rvUData = $rvUser->fetch();
            $rvName = $rvUData['name'] ?? $rv['guest_name'] ?? 'Guest';
            $rvImg  = $rvUData['profile_img'] ?? null;
            $firstName = explode(' ', trim($rvName))[0];
          ?>
          <div style="background:var(--card2);border-radius:10px;padding:10px 13px;margin-top:6px">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:7px">
              <?= userAvatar($rvImg, $rvName, 26) ?>
              <div>
                <div style="font-size:12px;font-weight:600;color:var(--text);line-height:1.2"><?= htmlspecialchars($firstName) ?></div>
                <?php if (!empty($rv['rating'])): ?>
                <div style="font-size:10px;color:#f59e0b;letter-spacing:1px"><?= str_repeat('★', (int)$rv['rating']) ?><?= str_repeat('☆', 5-(int)$rv['rating']) ?></div>
                <?php endif; ?>
              </div>
            </div>
            <div class="rc-mini-review" style="border-bottom:none;padding:0;margin:0;font-size:12px"><?= htmlspecialchars($rv['review']) ?></div>
          </div>
          <?php endforeach; ?>
          <?php else: ?>
          <div class="rc-no-review">
            <i class="fas fa-comment-slash"></i>
            <span>Be the first to review this room</span>
          </div>
          <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="rc-footer">
          <?php if ($avail > 0): ?>
          <button class="rc-btn-book" onclick="window.location='<?= $B ?>/booking-details.php?room=<?= $rt['id'] ?>&checkin='+encodeURIComponent(document.getElementById('ci')?.value||'<?= date('Y-m-d') ?>')+'&checkout='+encodeURIComponent(document.getElementById('co')?.value||'<?= date('Y-m-d',strtotime('+2 days')) ?>')">
            <i class="fas fa-calendar-check" style="font-size:13px"></i> Book Now
          </button>
          <?php else: ?>
          <button class="rc-btn-book" disabled style="opacity:.45;cursor:not-allowed" title="No rooms currently available for these dates">
            <i class="fas fa-ban" style="font-size:13px"></i> Sold Out
          </button>
          <?php endif; ?>
          <button class="cart-btn-add" id="cart-btn-<?= $rt['id'] ?>" onclick="cartToggle(<?= $rt['id'] ?>, <?= (int)$rt['price_usd'] ?>, '<?= addslashes($rt['name']) ?>')">
            <i class="fas fa-cart-plus" style="font-size:11px"></i> <span id="cart-btn-lbl-<?= $rt['id'] ?>">Add</span>
          </button>
          <a class="rc-btn-review" href="<?= $B ?>/reviews.php?room=<?= $rt['id'] ?>">
            <i class="fas fa-pencil-alt" style="font-size:12px"></i> Review
          </a>
        </div>
      </div>
      <?php endforeach; ?>

      <?php if (empty($filtered)): ?>
      <div style="grid-column:1/-1;text-align:center;padding:80px 20px;color:var(--muted)">
        <div style="font-size:48px;margin-bottom:16px">🔍</div>
        <h3 style="font-family:var(--serif);font-size:22px;margin-bottom:8px">No rooms match your filters</h3>
        <a href="<?= $B ?>/rooms.php" class="btn btn-gold" style="margin-top:18px">Clear Filters</a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ══ CART BAR ══ -->
<div class="cart-bar" id="cartBar">
  <div class="cart-bar-items" id="cartChips"></div>
  <div style="display:flex;align-items:center;gap:14px;flex-shrink:0">
    <div>
      <div style="font-size:10px;color:rgba(255,255,255,.4);letter-spacing:1px">TOTAL / NIGHT</div>
      <div class="cart-bar-total" id="cartTotal">$0</div>
    </div>
    <button class="cart-bar-btn" onclick="openBookingModal(null)"><i class="fas fa-calendar-check" style="margin-right:6px"></i>Book Selected Rooms</button>
  </div>
</div>

<!-- ══ BOOKING MODAL ══ -->
<div class="bk-backdrop" id="bkBackdrop" onclick="if(event.target===this&&!isBooking)closeBkModal()">
  <div class="bk-modal" id="bkModal">

    <!-- Header -->
    <div class="bk-hd">
      <div class="bk-hd-title"><i class="fas fa-calendar-check" style="color:var(--gold)"></i> Complete Your Booking</div>
      <button class="bk-hd-close" onclick="closeBkModal()">×</button>
    </div>

    <!-- Loading state -->
    <div class="bk-loading" id="bkLoading">
      <div class="spin"></div>
      <div style="font-family:var(--serif);font-size:18px" id="bkLoadingMsg">Processing your booking…</div>
    </div>

    <!-- Success state -->
    <div class="bk-success" id="bkSuccess">
      <div class="bk-success-icon">🎉</div>
      <h2 style="font-family:var(--serif);font-size:26px;margin-bottom:8px">Booking Confirmed!</h2>
      <p style="color:var(--text2);font-size:14px" id="bkSuccessMsg">Your reservation has been made.</p>
      <div class="bk-ref-box" id="bkSuccessRef">—</div>
      <div class="bk-sum-success" id="bkSuccessDetails"></div>
      <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:16px">
        <a href="#" id="bkInvoiceLink" target="_blank" class="btn btn-gold"><i class="fas fa-file-invoice"></i> View Invoice</a>
        <a href="<?= $B ?>/bookings.php" class="btn btn-outline"><i class="fas fa-calendar-check"></i> My Bookings</a>
        <button onclick="closeBkModal()" class="btn btn-ghost">Close</button>
      </div>
    </div>

    <!-- Main form -->
    <div id="bkForm">
      <!-- 1. Select Rooms -->
      <div class="bk-sec" style="margin-top:18px">
        <div class="bk-sec-hd"><i class="fas fa-bed"></i> Select Rooms <span style="color:var(--muted);font-size:11px;margin-left:6px">Choose one or more room types for your stay</span></div>
        <div class="bk-sec-bd" id="roomSelList">
          <?php foreach ($roomTypes as $rt): ?>
          <div class="room-sel-row" id="selrow-<?= $rt['id'] ?>" onclick="toggleRoomSel(<?= $rt['id'] ?>)">
            <div class="room-sel-chk" id="chk-<?= $rt['id'] ?>"><i class="fas fa-check"></i></div>
            <div style="flex:1">
              <div class="room-sel-name"><?= htmlspecialchars($rt['name']) ?></div>
              <div class="room-sel-price"><?= formatPrice($rt['price_usd']) ?> per night</div>
            </div>
            <div class="qty-wrap" id="qty-wrap-<?= $rt['id'] ?>" style="display:none" onclick="event.stopPropagation()">
              <span style="font-size:11px;color:var(--muted);margin-right:3px">Qty:</span>
              <button class="qty-btn" onclick="changeQty(<?= $rt['id'] ?>,-1)">−</button>
              <span class="qty-num" id="qty-<?= $rt['id'] ?>">1</span>
              <button class="qty-btn" onclick="changeQty(<?= $rt['id'] ?>,1)">+</button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- 2. Guest Info -->
      <div class="bk-sec">
        <div class="bk-sec-hd"><i class="fas fa-user"></i> Guest Information</div>
        <div class="bk-sec-bd">
          <div class="bk-field-grid">
            <div class="bk-fg">
              <label class="bk-lbl"><i class="fas fa-user" style="color:var(--gold)"></i> Full Name <span class="req">*</span></label>
              <input class="bk-ctrl" id="bk_name" placeholder="John Smith" value="<?= isLoggedIn()?htmlspecialchars($_SESSION['username']??''):'' ?>">
            </div>
            <div class="bk-fg">
              <label class="bk-lbl"><i class="fas fa-envelope" style="color:var(--gold)"></i> Email <span class="req">*</span></label>
              <input class="bk-ctrl" id="bk_email" type="email" placeholder="you@email.com" value="<?= isLoggedIn()?htmlspecialchars($_SESSION['email']??''):'' ?>">
            </div>
            <div class="bk-fg">
              <label class="bk-lbl"><i class="fas fa-phone" style="color:var(--gold)"></i> Phone</label>
              <input class="bk-ctrl" id="bk_phone" placeholder="+1 555 000 0000">
            </div>
            <div class="bk-fg">
              <label class="bk-lbl"><i class="fas fa-users" style="color:var(--gold)"></i> Number of Guests</label>
              <select class="bk-ctrl" id="bk_adults">
                <?php for($g=1;$g<=8;$g++): ?><option value="<?=$g?>" <?=$g===2?'selected':''?>><?=$g?></option><?php endfor; ?>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- 3. Stay Details -->
      <div class="bk-sec">
        <div class="bk-sec-hd"><i class="fas fa-calendar-alt"></i> Stay Details</div>
        <div class="bk-sec-bd">
          <div class="bk-field-grid">
            <div class="bk-fg">
              <label class="bk-lbl"><i class="fas fa-arrow-right" style="color:var(--gold)"></i> Check-In <span class="req">*</span></label>
              <input class="bk-ctrl" id="bk_ci" type="date" value="<?= htmlspecialchars($checkin) ?>" min="<?= date('Y-m-d') ?>" oninput="onDateChange()">
            </div>
            <div class="bk-fg">
              <label class="bk-lbl"><i class="fas fa-arrow-left" style="color:var(--gold)"></i> Check-Out <span class="req">*</span></label>
              <input class="bk-ctrl" id="bk_co" type="date" value="<?= htmlspecialchars($checkout) ?>" min="<?= date('Y-m-d',strtotime('+1 day')) ?>" oninput="onDateChange()">
            </div>
          </div>
          <div class="bk-fg" style="margin-top:10px">
            <label class="bk-lbl"><i class="fas fa-comment" style="color:var(--gold)"></i> Special Requests</label>
            <textarea class="bk-ctrl" id="bk_special" rows="2" placeholder="Any special requirements for your stay?" style="resize:none"></textarea>
          </div>
        </div>
      </div>

      <!-- 4. Discounts -->
      <div class="bk-sec">
        <div class="bk-sec-hd"><i class="fas fa-tag"></i> Discounts & Offers</div>
        <div class="bk-sec-bd">
          <?php if ($userMembership): ?>
          <div style="background:var(--gold-dim);border:1px solid var(--border);border-radius:8px;padding:10px 13px;margin-bottom:12px;display:flex;align-items:center;gap:9px;font-size:13px">
            <i class="fas fa-crown" style="color:var(--gold)"></i>
            <div><strong><?= htmlspecialchars($userMembership['name']) ?> Member</strong> — <?= $userMembership['discount_pct'] ?>% auto-applied</div>
          </div>
          <?php endif; ?>
          <div class="code-row" style="margin-bottom:6px">
            <input class="bk-ctrl" id="bk_offer" placeholder="Offer code (e.g. WELCOME10)" style="text-transform:uppercase" oninput="this.value=this.value.toUpperCase()">
            <button class="code-btn" onclick="applyOffer()">Apply</button>
          </div>
          <div class="code-msg" id="offerMsg"></div>
          <?php if ($loyaltyBal >= 100): ?>
          <div style="margin-top:12px">
            <label class="bk-lbl" style="margin-bottom:6px"><i class="fas fa-coins" style="color:var(--gold)"></i> Loyalty Points <span style="color:var(--gold);font-weight:700"><?= number_format($loyaltyBal) ?> available</span></label>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
              <input type="number" class="bk-ctrl" id="bk_pts" placeholder="0" min="0" max="<?= min($loyaltyBal,5000) ?>" step="100" style="width:110px" oninput="calcSummary()">
              <span style="font-size:13px;color:var(--text2)">= <strong id="ptsVal" style="color:var(--gold)">$0</strong> off</span>
              <button type="button" class="code-btn" style="padding:9px 12px" onclick="document.getElementById('bk_pts').value=<?= min($loyaltyBal,5000) ?>;calcSummary()">Max</button>
            </div>
            <div style="font-size:11px;color:var(--muted);margin-top:4px">100 points = $1.00 discount</div>
          </div>
          <?php endif; ?>
          <div style="margin-top:12px">
            <label class="bk-lbl" style="margin-bottom:6px"><i class="fas fa-gift" style="color:var(--gold)"></i> Gift Card(s)</label>
            <div class="code-row" style="margin-bottom:6px">
              <input class="bk-ctrl" id="bk_gc" placeholder="Enter gift card code" style="text-transform:uppercase" oninput="this.value=this.value.toUpperCase()">
              <button class="code-btn" onclick="applyGiftCard()">Apply</button>
            </div>
            <div id="gcMsg" class="code-msg"></div>
            <div id="gcList" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px"></div>
          </div>
        </div>
      </div>

      <!-- 5. Payment -->
      <div class="bk-sec">
        <div class="bk-sec-hd"><i class="fas fa-credit-card"></i> Payment Method</div>
        <div class="bk-sec-bd">
          <div class="pay-methods">
            <div class="pay-m sel" id="pm-hotel" onclick="selectPay('hotel')">
              <div class="pay-m-radio"></div>
              <div class="pay-m-icon">🏨</div>
              <div><div class="pay-m-name">Pay at Hotel</div><div class="pay-m-sub">Reserve now, pay on arrival</div></div>
            </div>
            <div class="pay-m" id="pm-card" onclick="selectPay('card')">
              <div class="pay-m-radio"></div>
              <div class="pay-m-icon">💳</div>
              <div><div class="pay-m-name">Credit / Debit Card</div><div class="pay-m-sub">Visa, Mastercard, Amex</div></div>
            </div>
            <div class="pay-m" id="pm-upi" onclick="selectPay('upi')">
              <div class="pay-m-radio"></div>
              <div class="pay-m-icon">📱</div>
              <div><div class="pay-m-name">UPI Transfer</div><div class="pay-m-sub">Instant payment via UPI</div></div>
            </div>
          </div>
          <!-- Card fields -->
          <div class="pay-extra" id="card-extra">
            <div class="bk-fg" style="margin-bottom:10px"><label class="bk-lbl">Card Number</label><input class="bk-ctrl" id="cardNum" placeholder="1234 5678 9012 3456" maxlength="19" oninput="let v=this.value.replace(/\D/g,'');this.value=v.replace(/(.{4})/g,'$1 ').trim()"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
              <div class="bk-fg"><label class="bk-lbl">Expiry</label><input class="bk-ctrl" id="cardExp" placeholder="MM/YY" maxlength="5" oninput="let v=this.value.replace(/\D/g,'');if(v.length>2)v=v.slice(0,2)+'/'+v.slice(2,4);this.value=v"></div>
              <div class="bk-fg"><label class="bk-lbl">CVV</label><input class="bk-ctrl" id="cardCvv" placeholder="123" maxlength="4" type="password"></div>
            </div>
          </div>
          <!-- UPI fields -->
          <div class="pay-extra" id="upi-extra">
            <div style="text-align:center;margin-bottom:12px">
              <div style="font-size:36px;margin-bottom:6px">📱</div>
              <div style="font-size:13px;color:var(--text2)">UPI ID: <strong style="color:var(--gold)">royalevista@upi</strong></div>
            </div>
            <div class="bk-fg"><label class="bk-lbl">Transaction Reference</label><input class="bk-ctrl" id="upiRef" placeholder="Enter UPI transaction ID"></div>
          </div>
        </div>
      </div>

      <!-- 6. Summary -->
      <div class="bk-sum" id="bkSumSection">
        <div class="bk-sum-hd"><i class="fas fa-receipt"></i> Booking Summary</div>
        <div class="bk-sum-bd" id="bkSumBody">
          <div style="text-align:center;color:var(--muted);padding:14px;font-size:13px"><i class="fas fa-info-circle"></i> Please select check-in and check-out dates</div>
        </div>
      </div>

      <!-- Footer buttons -->
      <div class="bk-ft">
        <button class="bk-ft-cancel" onclick="closeBkModal()">Cancel</button>
        <button class="bk-ft-book" id="bkSubmitBtn" onclick="submitBooking()" disabled>
          <i class="fas fa-lock" style="font-size:13px"></i>
          Confirm Booking
        </button>
      </div>
    </div><!-- #bkForm -->

  </div><!-- .bk-modal -->
</div><!-- .bk-backdrop -->

<script>
// ══════════════════════════════════════════════════════
//  ROYALE VISTA — Real-Time Rooms + Booking JS
// ══════════════════════════════════════════════════════
const BASE     = '<?= $B ?>';
const IS_LOGIN = <?= isLoggedIn()?'true':'false' ?>;
const CHECKIN  = '<?= $checkin ?>';
const CHECKOUT = '<?= $checkout ?>';
const TAX_RATE = 0.18;
const MEMBER_DISC = <?= $userMembership ? $userMembership['discount_pct'] : 0 ?>;

// State
let selectedRooms = {}; // {id: qty}
let currentPay    = 'hotel';
let offerDiscount = 0;
let isBooking     = false;
let appliedGiftCards = [];
window._avails    = {}; // {id: max_avail_qty}

// ── Anime.js: Card entrance animation ────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  // Hero text
  anime({ targets: '#heroLabel', opacity:[0,1], translateX:[-20,0], duration: 600, easing:'easeOutCubic', delay:200 });
  anime({ targets: '#heroTitle', opacity:[0,1], translateY:[20,0], duration: 700, easing:'easeOutCubic', delay:350 });
  anime({ targets: '#heroSub',   opacity:[0,1], translateY:[10,0], duration: 600, easing:'easeOutCubic', delay:500 });
  anime({ targets: '#srchBar',   opacity:[0,1], translateY:[16,0], duration: 600, easing:'easeOutCubic', delay:400 });
  anime({ targets: '#typePills .tpill', opacity:[0,1], translateY:[10,0], duration:400, easing:'easeOutCubic', delay: anime.stagger(60, {start:500}) });
  // Card grid stagger
  anime({ targets: '.rc', opacity:[0,1], translateY:[30,0], duration:550, easing:'easeOutBack', delay: anime.stagger(80, {start:600}) });

  // Real-time availability for current dates
  refreshAllAvailability();
  calcSummary();

  // Date change listener
  document.getElementById('bk_ci').addEventListener('change', onDateChange);
  document.getElementById('bk_co').addEventListener('change', onDateChange);
});

// ── Wishlist ─────────────────────────────────────────────────
async function toggleWishlist(roomTypeId, btn) {
  if (!IS_LOGIN) { toast('Login to save rooms to your wishlist', 'info'); return; }
  const wished = btn.classList.contains('wished');
  btn.classList.toggle('wished');
  anime({ targets: btn, scale:[1,1.3,1], duration:300, easing:'easeOutBack' });
  try {
    const fd = new FormData(); fd.append('room_type_id', roomTypeId);
    const res = await fetch(BASE+'/api/wishlist.php',{method:'POST',body:fd});
    const d   = await res.json();
    toast(d.wishlisted ? '❤️ Added to wishlist' : 'Removed from wishlist', 'success');
  } catch(e) { btn.classList.toggle('wished'); }
}

// ── Toggle amenities ──────────────────────────────────────────
function toggleFacs(id) {
  const el = document.getElementById('facs-'+id);
  const ar = document.getElementById('fac-arrow-'+id);
  if (!el) return;
  const open = el.style.display !== 'none';
  el.style.display = open ? 'none' : 'flex';
  ar.style.transform = open ? '' : 'rotate(180deg)';
}

// ── Real-time availability refresh ───────────────────────────
async function refreshAllAvailability() {
  const ci = document.getElementById('bk_ci')?.value || CHECKIN;
  const co = document.getElementById('bk_co')?.value || CHECKOUT;
  if (!ci || !co || ci >= co) return;

  const roomIds = <?= json_encode(array_column($roomTypes,'id')) ?>;
  const rooms   = roomIds.map(id => ({id, qty:1}));

  try {
    const fd = new FormData();
    fd.append('action','availability');
    fd.append('checkin', ci);
    fd.append('checkout', co);
    fd.append('rooms', JSON.stringify(rooms));
    const res  = await fetch(BASE+'/api/book.php',{method:'POST',body:fd});
    const data = await res.json();

    if (data.ok && data.rooms) {
      data.rooms.forEach(r => {
        window._avails[r.id] = r.avail_count;
        const badge = document.getElementById('availbadge-'+r.id);
        const avbar = document.getElementById('avbar-'+r.id);
        const avnum = document.getElementById('availnum-'+r.id);
        const atxt  = document.getElementById('availtxt-'+r.id);

        if (badge) {
          badge.className = 'rc-badge ' + (r.available?'badge-avail':'badge-occup');
          badge.innerHTML = `<i class="fas fa-circle" style="font-size:7px"></i> ${r.available?'Available':'Sold Out'}`;
        }
        if (avbar) avbar.className = 'rc-avail-bar '+(r.available?'avail':'occup');
        if (avnum) avnum.textContent = r.avail_count+' room'+(r.avail_count!==1?'s':'')+' available';
        if (atxt) {
          const dot = atxt.querySelector('.avail-dot');
          if (dot) dot.className = 'avail-dot '+(r.available?'av':'un');
        }
        // Animate updated badge
        if (badge) anime({targets:badge,scale:[.85,1],opacity:[.5,1],duration:400,easing:'easeOutBack'});
      });
    }
  } catch(e) {}
}

// ── Cart ────────────────────────────────────────────────────
const cart = {}; // {id: {name, price}}

function cartToggle(id, price, name) {
  if (cart[id]) {
    delete cart[id];
  } else {
    cart[id] = {name, price};
  }
  renderCart();
  // Also sync with modal selection
  if (document.getElementById('bkBackdrop').classList.contains('open')) {
    const row = document.getElementById('selrow-'+id);
    const isSelected = row?.classList.contains('selected');
    if (cart[id] && !isSelected) toggleRoomSel(id);
    if (!cart[id] && isSelected) toggleRoomSel(id);
  }
}

function renderCart() {
  const bar   = document.getElementById('cartBar');
  const chips = document.getElementById('cartChips');
  const total = document.getElementById('cartTotal');
  const keys  = Object.keys(cart);
  chips.innerHTML = '';
  let sum = 0;
  keys.forEach(id => {
    const {name, price} = cart[id];
    sum += price;
    const chip = document.createElement('div');
    chip.className = 'cart-chip';
    chip.innerHTML = `<span>${name}</span><span style="color:var(--gold)">\$${price}/n</span><button class="cart-chip-rm" onclick="cartToggle(${id},${price},'${name.replace(/'/g,"\\'")}')">×</button>`;
    chips.appendChild(chip);
    const btn = document.getElementById('cart-btn-'+id);
    const lbl = document.getElementById('cart-btn-lbl-'+id);
    if (btn) btn.classList.add('in-cart');
    if (lbl) lbl.textContent = '✓ Added';
  });
  // Reset buttons not in cart
  document.querySelectorAll('.cart-btn-add').forEach(btn => {
    const bid = btn.id.replace('cart-btn-','');
    if (!cart[bid]) {
      btn.classList.remove('in-cart');
      const lbl = document.getElementById('cart-btn-lbl-'+bid);
      if (lbl) lbl.textContent = 'Add';
    }
  });
  total.textContent = '$' + sum.toLocaleString() + '/n';
  keys.length > 0 ? bar.classList.add('show') : bar.classList.remove('show');
  // Shift chatbot and back-to-top up
  const bubble = document.getElementById('habibiBubble');
  const bkTop  = document.getElementById('back-top');
  const offset = keys.length > 0 ? '80px' : '24px';
  if (bubble) bubble.style.bottom = keys.length > 0 ? '90px' : '24px';
  if (bkTop)  bkTop.style.bottom  = keys.length > 0 ? '150px' : '90px';
}

// ── Open booking modal ──────────────────────────────────────────
function openBookingModal(preSelectId) {
  selectedRooms = {};
  document.querySelectorAll('.room-sel-row').forEach(row => row.classList.remove('selected'));
  document.querySelectorAll('.qty-wrap').forEach(w => w.style.display='none');
  document.querySelectorAll('.qty-num').forEach(n => n.textContent='1');
  document.getElementById('bkForm').style.display    = '';
  document.getElementById('bkLoading').style.display = 'none';
  document.getElementById('bkSuccess').style.display = 'none';

  // Pre-select if coming from a specific room
  if (preSelectId) toggleRoomSel(preSelectId);

  document.getElementById('bkBackdrop').classList.add('open');
  document.body.style.overflow = 'hidden';

  // Animate modal in
  anime({ targets: '.bk-modal', translateY:[-30,0], opacity:[0,1], duration:350, easing:'easeOutBack' });
}

function closeBkModal() {
  if (isBooking) return;
  anime({
    targets: '.bk-modal', translateY:[0,20], opacity:[1,0], duration:250, easing:'easeInCubic',
    complete() {
      document.getElementById('bkBackdrop').classList.remove('open');
      document.body.style.overflow = '';
    }
  });
}

// ── Room selection ────────────────────────────────────────────
function toggleRoomSel(id) {
  const row  = document.getElementById('selrow-'+id);
  const qwrp = document.getElementById('qty-wrap-'+id);
  if (!row) return;

  if (selectedRooms[id]) {
    delete selectedRooms[id];
    row.classList.remove('selected');
    if (qwrp) { qwrp.style.display='none'; }
    anime({ targets: row, backgroundColor: '', duration: 300 });
  } else {
    selectedRooms[id] = 1;
    row.classList.add('selected');
    if (qwrp) {
      qwrp.style.display = 'flex';
      anime({ targets: qwrp, opacity:[0,1], scale:[.8,1], duration:250, easing:'easeOutBack' });
    }
    anime({ targets: row, scale:[1,1.02,1], duration:200, easing:'easeOutQuad' });
  }
  calcSummary();
}

function changeQty(id, delta) {
  if (!selectedRooms[id]) return;
  const maxAvail = window._avails[id] !== undefined ? window._avails[id] : 10;
  const newQty = Math.max(1, Math.min(maxAvail, (selectedRooms[id] || 1) + delta));
  
  if (newQty === selectedRooms[id]) return; // reached limit
  
  selectedRooms[id] = newQty;
  const numEl = document.getElementById('qty-'+id);
  if (numEl) {
    numEl.textContent = newQty;
    anime({ targets: numEl, scale:[1.4,1], duration:200, easing:'easeOutBack' });
  }
  calcSummary();
}

// ── Date change ───────────────────────────────────────────────
function onDateChange() {
  // Sync filter form dates too
  const ci = document.getElementById('bk_ci').value;
  const co = document.getElementById('bk_co').value;
  document.getElementById('ci').value = ci;
  document.getElementById('co').value = co;
  calcSummary();
  // Refresh availability real-time
  clearTimeout(window._avTimer);
  window._avTimer = setTimeout(refreshAllAvailability, 700);
}

// ── Apply offer code ──────────────────────────────────────────
async function applyOffer() {
  const code = document.getElementById('bk_offer').value.trim().toUpperCase();
  const msg  = document.getElementById('offerMsg');
  if (!code) { msg.textContent='Enter a code first'; msg.className='code-msg err'; return; }
  msg.textContent='Checking…'; msg.className='code-msg';

  const ci = document.getElementById('bk_ci').value;
  const co = document.getElementById('bk_co').value;
  const rooms = buildRoomsPayload();
  const subtotal = calcSubtotal();

  try {
    const fd = new FormData();
    fd.append('action','availability');
    fd.append('checkin',ci); fd.append('checkout',co);
    fd.append('rooms',JSON.stringify(rooms));
    fd.append('offer_code',code);
    const res  = await fetch(BASE+'/api/book.php',{method:'POST',body:fd});
    const data = await res.json();
    if (data.ok) {
      offerDiscount = data.discount_usd - (subtotal * MEMBER_DISC/100);
      msg.textContent = '✓ Offer applied! Saving ' + (data.discount_fmt||'');
      msg.className = 'code-msg ok';
    } else {
      offerDiscount = 0;
      msg.textContent = '✗ ' + data.error;
      msg.className = 'code-msg err';
    }
  } catch(e) {
    msg.textContent='Connection error'; msg.className='code-msg err';
  }
  calcSummary();
}

// ── Payment select ────────────────────────────────────────────
function selectPay(method) {
  currentPay = method;
  ['hotel','card','upi'].forEach(m => {
    document.getElementById('pm-'+m).classList.toggle('sel', m===method);
  });
  document.getElementById('card-extra').className = 'pay-extra' + (method==='card'?' open':'');
  document.getElementById('upi-extra').className  = 'pay-extra' + (method==='upi'?' open':'');
}

// ── Price calculation ─────────────────────────────────────────
function calcSubtotal() {
  const ci = document.getElementById('bk_ci').value;
  const co = document.getElementById('bk_co').value;
  if (!ci || !co || ci >= co) return 0;
  const nights = Math.max(1, Math.round((new Date(co)-new Date(ci))/86400000));
  const prices = <?= json_encode(array_column($roomTypes,'price_usd','id')) ?>;
  let sub = 0;
  Object.entries(selectedRooms).forEach(([id,qty]) => { sub += (prices[id]||0) * qty * nights; });
  return sub;
}

function calcSummary() {
  const ci = document.getElementById('bk_ci').value;
  const co = document.getElementById('bk_co').value;
  const body = document.getElementById('bkSumBody');
  const btn  = document.getElementById('bkSubmitBtn');
  const names = <?= json_encode(array_column($roomTypes,'name','id')) ?>;
  const prices= <?= json_encode(array_column($roomTypes,'price_usd','id')) ?>;
  const ptsEl = document.getElementById('bk_pts');
  const ptsVal= document.getElementById('ptsVal');

  const redeemPts = parseInt(ptsEl?.value||0)||0;
  const loyaltyD  = Math.floor(redeemPts/100);
  if (ptsVal) ptsVal.textContent = '$'+loyaltyD.toFixed(2);

  if (!ci || !co || ci >= co) {
    body.innerHTML='<div style="text-align:center;color:var(--muted);padding:14px;font-size:13px"><i class="fas fa-calendar"></i> Please select valid check-in and check-out dates</div>';
    btn.disabled = true; return;
  }

  const nights = Math.max(1, Math.round((new Date(co)-new Date(ci))/86400000));
  const selected = Object.entries(selectedRooms);

  if (!selected.length) {
    body.innerHTML='<div style="text-align:center;color:var(--muted);padding:14px;font-size:13px"><i class="fas fa-bed"></i> Select at least one room type above</div>';
    btn.disabled = true; return;
  }

  const memberD = calcSubtotal() * (MEMBER_DISC/100);
  const sub     = calcSubtotal();
  const totalD  = memberD + offerDiscount + loyaltyD;
  const beforeGc = Math.max(0, sub - totalD);
  const gcD = calcGiftCardDiscount(beforeGc);
  const after   = Math.max(0, beforeGc - gcD);
  const tax     = after * TAX_RATE;
  const final   = after + tax;

  let html = '';
  selected.forEach(([id,qty]) => {
    const n = Math.max(1, Math.round((new Date(co)-new Date(ci))/86400000));
    const t = (prices[id]||0)*qty*n;
    html += `<div class="sum-row"><span>${names[id]||'Room'} × ${qty} × ${n}n</span><span>${fmtPrice(t)}</span></div>`;
  });
  html += `<div class="sum-row"><span style="color:var(--muted)">Dates</span><span>${formatDate(ci)} → ${formatDate(co)}</span></div>`;
  if (totalD>0) html += `<div class="sum-row disc"><span>Discounts</span><span>−${fmtPrice(totalD)}</span></div>`;
  if (gcD>0) html += `<div class="sum-row disc"><span>Gift Card(s)</span><span>−${fmtPrice(gcD)}</span></div>`;
  html += `<div class="sum-row"><span style="color:var(--muted)">Taxes (18%)</span><span>${fmtPrice(tax)}</span></div>`;
  html += `<div class="sum-row total"><span>Total</span><span class="sum-val">${fmtPrice(final)}</span></div>`;
  body.innerHTML = html;

  // Animate total
  anime({targets:'#bkSumBody .sum-val',scale:[.9,1],opacity:[.6,1],duration:300,easing:'easeOutBack'});

  btn.disabled = !IS_LOGIN;
  if (!IS_LOGIN) btn.title = 'Please log in to book';
}

function calcGiftCardDiscount(preTaxAmount) {
  let remaining = Math.max(0, preTaxAmount);
  let used = 0;
  appliedGiftCards.forEach(gc => {
    const useNow = Math.min(remaining, gc.balance || 0);
    gc.use = useNow;
    used += useNow;
    remaining = Math.max(0, remaining - useNow);
  });
  return used;
}

function renderGiftCards() {
  const list = document.getElementById('gcList');
  if (!list) return;
  list.innerHTML = appliedGiftCards.map((gc, idx) =>
    `<span style="background:var(--card2);border:1px solid var(--bdr2);border-radius:999px;padding:6px 10px;font-size:12px">
      <i class="fas fa-gift" style="color:var(--gold);margin-right:4px"></i>${gc.code} · ${fmtPrice(gc.use || 0)}
      <button type="button" onclick="removeGiftCard(${idx})" style="margin-left:6px;border:none;background:none;color:var(--muted);cursor:pointer">×</button>
    </span>`
  ).join('');
}

async function applyGiftCard() {
  const input = document.getElementById('bk_gc');
  const msg = document.getElementById('gcMsg');
  const code = (input?.value || '').trim().toUpperCase();
  if (!code) return;
  if (appliedGiftCards.some(g => g.code === code)) {
    msg.textContent = 'This gift card is already applied.';
    msg.className = 'code-msg err';
    return;
  }
  msg.textContent = 'Checking...';
  msg.className = 'code-msg';
  try {
    const res = await fetch(BASE + '/api/validate_gc.php?code=' + encodeURIComponent(code));
    const data = await res.json();
    if (!data.success) {
      msg.textContent = data.error || 'Invalid gift card';
      msg.className = 'code-msg err';
      return;
    }
    appliedGiftCards.push({ code: data.code, balance: Number(data.balance || 0), use: 0 });
    if (input) input.value = '';
    calcSummary();
    renderGiftCards();
    msg.textContent = `${data.code} applied.`;
    msg.className = 'code-msg ok';
  } catch (e) {
    msg.textContent = 'Connection error';
    msg.className = 'code-msg err';
  }
}

function removeGiftCard(idx) {
  appliedGiftCards.splice(idx, 1);
  calcSummary();
  renderGiftCards();
}

function formatDate(d) { const dt=new Date(d); return dt.toLocaleDateString('en-GB',{day:'numeric',month:'short'}); }
function buildRoomsPayload() { return Object.entries(selectedRooms).map(([id,qty])=>({id:parseInt(id),qty})); }

// ── Submit Booking ────────────────────────────────────────────
async function submitBooking() {
  if (isBooking) return;

  const name  = document.getElementById('bk_name').value.trim();
  const email = document.getElementById('bk_email').value.trim();
  const ci    = document.getElementById('bk_ci').value;
  const co    = document.getElementById('bk_co').value;
  const rooms = buildRoomsPayload();

  if (!name)           { toast('Please enter your name','error');  return; }
  if (!email || !email.includes('@')) { toast('Please enter a valid email','error'); return; }
  if (!ci || !co || ci >= co) { toast('Invalid dates','error'); return; }
  if (!rooms.length)   { toast('Please select at least one room','error'); return; }

  isBooking = true;
  const formEl = document.getElementById('bkForm');
  formEl.style.display = 'none';
  document.getElementById('bkLoading').style.display = 'block';
  anime({ targets: '#bkLoading', opacity:[0,1], duration:300 });

  const fd = new FormData();
  fd.append('action','create');
  fd.append('guest_name',  name);
  fd.append('guest_email', email);
  fd.append('guest_phone', document.getElementById('bk_phone').value.trim());
  fd.append('adults',      document.getElementById('bk_adults').value);
  fd.append('children',    0);
  fd.append('special_req', document.getElementById('bk_special').value);
  fd.append('checkin',     ci);
  fd.append('checkout',    co);
  fd.append('pay_method',  currentPay);
  fd.append('offer_code',  document.getElementById('bk_offer').value.toUpperCase());
  fd.append('redeem_points', document.getElementById('bk_pts')?.value||0);
  fd.append('gc_codes', JSON.stringify(appliedGiftCards.map(g => g.code)));
  fd.append('rooms',       JSON.stringify(rooms));

  try {
    const res  = await fetch(BASE+'/api/book.php',{method:'POST',body:fd});
    const data = await res.json();

    document.getElementById('bkLoading').style.display = 'none';

    if (data.ok) {
      showSuccess(data);
      // Refresh availability on cards
      setTimeout(refreshAllAvailability, 500);
      toast('🎉 Booking confirmed!','success');
    } else {
      formEl.style.display = '';
      toast('✗ '+data.error,'error');
    }
  } catch(e) {
    document.getElementById('bkLoading').style.display = 'none';
    formEl.style.display = '';
    toast('Connection error. Please try again.','error');
  }
  isBooking = false;
}

function showSuccess(data) {
  const el = document.getElementById('bkSuccess');
  el.style.display = 'block';
  document.getElementById('bkSuccessRef').textContent = data.booking_ref;
  document.getElementById('bkSuccessMsg').textContent = `Confirmation sent to ${data.guest_email}`;
  document.getElementById('bkInvoiceLink').href = data.invoice_url;

  let details = `<div style="font-size:13px;line-height:2">`;
  data.rooms.forEach(r => details += `<div style="display:flex;justify-content:space-between"><span>${r.name} ×${r.qty}</span><span>${fmtPrice(r.total)}</span></div>`);
  details += `<div style="display:flex;justify-content:space-between"><span>${data.nights} night(s)</span><span>${formatDate(data.checkin)} → ${formatDate(data.checkout)}</span></div>`;
  if (data.earned_pts>0) details += `<div style="color:var(--gold);margin-top:6px">+${data.earned_pts.toLocaleString()} loyalty points earned! ✨</div>`;
  details += `</div>`;
  document.getElementById('bkSuccessDetails').innerHTML = details;

  anime({
    targets: el,
    opacity: [0,1],
    translateY: [20,0],
    duration: 500,
    easing: 'easeOutBack'
  });
  anime({
    targets: '.bk-success-icon',
    scale: [0, 1.2, 1],
    duration: 600,
    easing: 'easeOutElastic(1,.5)'
  });

  // Emit booking completion event for real-time updates
  window.parent.postMessage({type: 'booking_completed', bookingRef: data.booking_ref}, '*');
  
  // Auto-refresh availability after booking
  setTimeout(() => {
    refreshAllAvailability();
    closeBkModal();
  }, 2000);
}

// ── Scroll reveal for cards ───────────────────────────────────
const obs = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      anime({ targets: e.target, opacity:[0,1], translateY:[20,0], duration:450, easing:'easeOutCubic' });
      obs.unobserve(e.target);
    }
  });
}, { threshold: 0.1 });
document.querySelectorAll('.rc').forEach(c => obs.observe(c));

// ── Helper: price format ──────────────────────────────────────
function fmtPrice(usd) { return (window.RV && window.RV.fmt) ? window.RV.fmt(usd) : ('$'+parseFloat(usd).toFixed(2)); }

// ── Real-time Server-Sent Events ───────────────────────
let eventSource = null;
let reconnectAttempts = 0;
const maxReconnectAttempts = 5;

function connectRealtimeUpdates() {
    if (eventSource) {
        eventSource.close();
    }
    
    try {
        eventSource = new EventSource(BASE + '/api/realtime.php');
        
        eventSource.onopen = function() {
            console.log('Real-time updates connected');
            reconnectAttempts = 0;
        };
        
        eventSource.onmessage = function(event) {
            const data = JSON.parse(event.data);
            
            switch(data.type) {
                case 'initial':
                    console.log('Initial availability loaded:', data.data);
                    break;
                    
                case 'availability_change':
                    console.log('Availability changed:', data.changes);
                    data.changes.forEach(change => {
                        updateRoomAvailability(change.room_type_id, change.new_count, change.change);
                    });
                    break;
                    
                case 'booking_activity':
                    console.log('Booking activity detected:', data.activity);
                    data.activity.forEach(activity => {
                        if (activity.activity_type === 'created') {
                            showNotification('New Booking!', `Booking ${activity.booking_ref} created`, 'info');
                        } else if (activity.activity_type === 'cancelled') {
                            showNotification('Booking Cancelled!', `Booking ${activity.booking_ref} cancelled`, 'warning');
                        }
                    });
                    // Refresh availability after activity
                    setTimeout(refreshAllAvailability, 1000);
                    break;
                    
                case 'heartbeat':
                    console.log('Real-time connection alive');
                    break;
                    
                case 'timeout':
                    console.log('Real-time connection timeout, reconnecting...');
                    if (reconnectAttempts < maxReconnectAttempts) {
                        setTimeout(connectRealtimeUpdates, 2000);
                        reconnectAttempts++;
                    }
                    break;
            }
        };
        
        eventSource.onerror = function(event) {
            console.error('Real-time connection error:', event);
            if (reconnectAttempts < maxReconnectAttempts) {
                setTimeout(connectRealtimeUpdates, 3000);
                reconnectAttempts++;
            }
        };
        
    } catch (error) {
        console.error('Failed to connect to real-time updates:', error);
    }
}

function updateRoomAvailability(roomTypeId, newCount, changeType) {
    const badge = document.getElementById('availbadge-' + roomTypeId);
    const avbar = document.getElementById('avbar-' + roomTypeId);
    const avnum = document.getElementById('availnum-' + roomTypeId);
    const atxt = document.getElementById('availtxt-' + roomTypeId);
    
    const isAvailable = newCount > 0;
    
    if (badge) {
        badge.className = 'rc-badge ' + (isAvailable ? 'badge-avail' : 'badge-occup');
        badge.innerHTML = `<i class="fas fa-circle" style="font-size:7px"></i> ${isAvailable ? 'Available' : 'Sold Out'}`;
        
        // Add animation for change
        badge.style.animation = 'none';
        setTimeout(() => {
            badge.style.animation = 'pulse 0.6s ease-in-out';
        }, 10);
    }
    
    if (avbar) avbar.className = 'rc-avail-bar ' + (isAvailable ? 'avail' : 'occup');
    if (avnum) avnum.textContent = newCount + ' room' + (newCount !== 1 ? 's' : '') + ' available';
    if (atxt) {
        const dot = atxt.querySelector('.avail-dot');
        if (dot) dot.className = 'avail-dot ' + (isAvailable ? 'av' : 'un');
    }
    
    // Show notification for significant changes
    if (changeType === 'decreased' && newCount <= 2) {
        showNotification('Room availability decreased!', `Only ${newCount} rooms left!`, 'warning');
    } else if (changeType === 'increased') {
        showNotification('Room available!', `${newCount} rooms now available`, 'success');
    }
}

function showNotification(title, message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `realtime-notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <strong>${title}</strong>
            <div>${message}</div>
        </div>
        <button onclick="this.parentElement.remove()">&times;</button>
    `;
    
    // Set background color based on type
    let bgColor = '#51cf66'; // default success
    if (type === 'warning') {
        bgColor = '#ff6b6b';
    } else if (type === 'info') {
        bgColor = '#339af0';
    }
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${bgColor};
        color: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        max-width: 300px;
        animation: slideIn 0.3s ease-out;
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    .realtime-notification button {
        position: absolute;
        top: 5px;
        right: 5px;
        background: none;
        border: none;
        color: white;
        font-size: 18px;
        cursor: pointer;
        opacity: 0.7;
    }
    .realtime-notification button:hover {
        opacity: 1;
    }
`;
document.head.appendChild(style);
document.addEventListener('DOMContentLoaded', () => {
  // Run on load
  refreshAllAvailability();

  // Connect to real-time updates
  connectRealtimeUpdates();

  // Re-run when date inputs change
  ['ci', 'co', 'bk_ci', 'bk_co'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', () => {
      setTimeout(refreshAllAvailability, 200);
    });
  });

  // Listen for booking completion events (if any)
  window.addEventListener('message', (event) => {
    if (event.data?.type === 'booking_completed' || event.data?.type === 'booking_cancelled') {
      console.log('Booking event detected, refreshing availability...');
      setTimeout(refreshAllAvailability, 500);
    }
  });

  // Auto-refresh every 30 seconds as fallback
  setInterval(() => {
    refreshAllAvailability();
  }, 30000);
});
</script>

<!-- Lightbox -->
<div id="lb" style="position:fixed;inset:0;background:rgba(0,0,0,.95);z-index:9999;display:none;flex-direction:column;backdrop-filter:blur(8px)">
  <div style="padding:15px 25px;display:flex;justify-content:space-between;align-items:center;color:#fff">
    <div id="lb-cnt" style="font-family:var(--serif);font-size:20px;letter-spacing:2px;color:var(--gold)">1 / 1</div>
    <button onclick="closeGallery()" style="background:none;border:none;color:#fff;font-size:32px;cursor:pointer;opacity:.7;transition:opacity .2s" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity='.7'">×</button>
  </div>
  <div style="flex:1;position:relative;display:flex;align-items:center;justify-content:center;overflow:hidden">
    <button onclick="lbPrev(event)" style="position:absolute;left:20px;background:rgba(255,255,255,.1);border:none;color:#fff;width:50px;height:50px;border-radius:50%;font-size:20px;cursor:pointer;z-index:10;transition:all .2s;backdrop-filter:blur(4px)" onmouseover="this.style.background='var(--gold)';this.style.color='#000'" onmouseout="this.style.background='rgba(255,255,255,.1)';this.style.color='#fff'"><i class="fas fa-chevron-left"></i></button>
    <div id="lb-stage" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;padding:20px"></div>
    <button onclick="lbNext(event)" style="position:absolute;right:20px;background:rgba(255,255,255,.1);border:none;color:#fff;width:50px;height:50px;border-radius:50%;font-size:20px;cursor:pointer;z-index:10;transition:all .2s;backdrop-filter:blur(4px)" onmouseover="this.style.background='var(--gold)';this.style.color='#000'" onmouseout="this.style.background='rgba(255,255,255,.1)';this.style.color='#fff'"><i class="fas fa-chevron-right"></i></button>
  </div>
</div>

<script>
let lbG = [];
let lbI = 0;
function openGallery(imgs) {
  if(!imgs||!imgs.length) return;
  lbG = imgs; lbI = 0;
  document.getElementById('lb').style.display='flex';
  document.body.style.overflow='hidden';
  renderLb();
}
function closeGallery() {
  document.getElementById('lb').style.display='none';
  document.body.style.overflow='';
  document.getElementById('lb-stage').innerHTML = '';
}
function lbNext(e) { if(e) e.stopPropagation(); lbI = (lbI+1)%lbG.length; renderLb(); }
function lbPrev(e) { if(e) e.stopPropagation(); lbI = (lbI-1+lbG.length)%lbG.length; renderLb(); }
function renderLb() {
  document.getElementById('lb-cnt').textContent = (lbI+1)+' / '+lbG.length;
  const m = lbG[lbI];
  const stage = document.getElementById('lb-stage');
  if((m.media_type||'image')==='video') {
    stage.innerHTML = `<video src="${m.image_url}" controls autoplay style="max-width:100%;max-height:100%;box-shadow:0 10px 40px rgba(0,0,0,.5);border-radius:8px"></video>`;
  } else {
    stage.innerHTML = `<img src="${m.image_url}" style="max-width:100%;max-height:100%;object-fit:contain;box-shadow:0 10px 40px rgba(0,0,0,.5);border-radius:8px">`;
  }
}
</script>

<?php require __DIR__ . '/footer.php'; ?>

