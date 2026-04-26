<?php
/**
 * ROYALE VISTA — Invoice / Receipt
 * Access: /invoice.php?ref=BKXXX
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$ref = clean($_GET['ref'] ?? '');
if (!$ref) {
  header('Location: ' . BASE . '/bookings.php');
  exit;
}

$uid = (int) ($_SESSION['user_id'] ?? 0);

// Load booking — accessible by owner or admin
$bkQ = $pdo->prepare("SELECT b.* FROM bookings b WHERE b.booking_ref = ? AND (b.user_id = ? OR ? = 1) LIMIT 1");
$bkQ->execute([$ref, $uid, isAdmin() ? 1 : 0]);
$bk = $bkQ->fetch();

if (!$bk) {
  if (!$uid) {
    header('Location: ' . BASE . '/login.php');
    exit;
  }
  http_response_code(404);
  echo '<!DOCTYPE html><html><head><title>Not Found</title><style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#0f0d0a;color:#f2ece2}.b{text-align:center}.b h2{color:#c09b5b;font-size:2rem;margin-bottom:12px}.b a{color:#c09b5b}</style></head><body><div class="b"><h2>Invoice Not Found</h2><p>This booking does not exist or you don\'t have access.</p><a href="' . BASE . '/bookings.php">← My Bookings</a></div></body></html>';
  exit;
}

// Load booked rooms
$roomsQ = $pdo->prepare("SELECT br.*, GROUP_CONCAT(bra.room_number ORDER BY bra.room_number SEPARATOR ', ') as assigned_rooms FROM booked_rooms br LEFT JOIN booking_room_assignments bra ON bra.booking_ref=br.booking_ref AND bra.room_type_id=br.room_type_id WHERE br.booking_ref=? GROUP BY br.id ORDER BY br.id");
$roomsQ->execute([$ref]);
$rooms = $roomsQ->fetchAll();

// Helpers
function inv_fmt(float $v): string
{
  return '$' . number_format($v, 2);
}

$statusColor = [
  'confirmed' => '#c09b5b',
  'cancelled' => '#e07070',
  'checked_in' => '#52b788',
  'checked_out' => '#74a8e0',
][$bk['status']] ?? '#888';

$payNames = ['card' => 'Credit / Debit Card', 'upi' => 'UPI Transfer', 'hotel' => 'Pay at Hotel'];
$payIcons = ['card' => '💳', 'upi' => '📱', 'hotel' => '🏨'];
$psColors = ['paid' => '#dcfce7:#15803d', 'pending' => '#fef3c7:#b45309', 'failed' => '#fee2e2:#dc2626', 'refunded' => '#dbeafe:#1d4e89'];
[$psBg, $psTx] = explode(':', $psColors[$bk['pay_status']] ?? '#f3f4f6:#374151');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Invoice <?= htmlspecialchars($ref) ?> — Royale Vista</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300;1,400&family=DM+Sans:wght@300;400;500;600&family=Cinzel:wght@400;500&display=swap"
    rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <style>
    /* ── Base ── */
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0
    }

    html {
      scroll-behavior: smooth
    }

    body {
      font-family: 'DM Sans', sans-serif;
      background: #f0ece4;
      color: #1a1612;
      min-height: 100vh
    }

    a {
      color: #c09b5b
    }

    /* ── Toolbar ── */
    .tb {
      background: #1c1813;
      padding: 12px 36px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 100;
      flex-wrap: wrap;
      gap: 10px
    }

    .tb-logo {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none
    }

    .tb-emblem {
      width: 32px;
      height: 32px;
      background: #c09b5b;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Cinzel', serif;
      font-size: 13px;
      color: #fff;
      clip-path: polygon(50% 0%, 93% 25%, 93% 75%, 50% 100%, 7% 75%, 7% 25%)
    }

    .tb-name {
      font-family: 'Cinzel', serif;
      font-size: 18px;
      color: #c09b5b;
      letter-spacing: 2px
    }

    .tb-btns {
      display: flex;
      gap: 8px;
      flex-wrap: wrap
    }

    .tbtn {
      padding: 9px 18px;
      border-radius: 7px;
      font-size: 12.5px;
      font-weight: 600;
      cursor: pointer;
      border: none;
      font-family: 'DM Sans', sans-serif;
      display: inline-flex;
      align-items: center;
      gap: 7px;
      transition: all .2s;
      text-decoration: none;
      white-space: nowrap
    }

    .tbtn-back {
      background: rgba(255, 255, 255, .08);
      color: rgba(255, 255, 255, .8)
    }

    .tbtn-back:hover {
      background: rgba(255, 255, 255, .15);
      color: #fff
    }

    .tbtn-print {
      background: #c09b5b;
      color: #000
    }

    .tbtn-print:hover {
      background: #a07a3a
    }

    .tbtn-pdf {
      background: #ef4444;
      color: #fff
    }

    .tbtn-pdf:hover {
      background: #dc2626
    }

    /* ── Page ── */
    .page {
      max-width: 820px;
      margin: 28px auto;
      padding: 0 16px 48px
    }

    .inv {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 4px 48px rgba(0, 0, 0, .12);
      overflow: hidden
    }

    /* ── Invoice Header ── */
    .inv-hd {
      background: linear-gradient(135deg, #1c1813 0%, #2d2520 100%);
      padding: 38px 46px;
      position: relative;
      overflow: hidden
    }

    .inv-hd::before {
      content: '✦';
      position: absolute;
      right: 24px;
      top: 6px;
      font-size: 140px;
      color: rgba(192, 155, 91, .05);
      line-height: 1;
      pointer-events: none;
      font-family: serif
    }

    .inv-hd-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 24px;
      flex-wrap: wrap
    }

    .inv-brand {
      font-family: 'Cinzel', serif;
      font-size: 30px;
      color: #c09b5b;
      letter-spacing: 3px
    }

    .inv-brand-sub {
      font-size: 10px;
      color: rgba(255, 255, 255, .35);
      letter-spacing: 3px;
      text-transform: uppercase;
      margin-top: 5px
    }

    .inv-status {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      margin-top: 11px;
      padding: 5px 14px;
      border-radius: 20px;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 1px;
      text-transform: uppercase
    }

    .inv-no-lbl {
      font-size: 9px;
      color: rgba(255, 255, 255, .35);
      letter-spacing: 2px;
      text-transform: uppercase;
      margin-bottom: 5px;
      text-align: right
    }

    .inv-no {
      font-family: 'Cormorant Garamond', serif;
      font-size: 22px;
      color: #c09b5b;
      text-align: right;
      letter-spacing: 1px
    }

    .inv-dt {
      font-size: 11px;
      color: rgba(255, 255, 255, .4);
      text-align: right;
      margin-top: 3px
    }

    /* ── Body ── */
    .inv-bd {
      padding: 36px 46px
    }

    .g2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 28px;
      margin-bottom: 26px
    }

    .sec-ttl {
      font-family: 'Cinzel', serif;
      font-size: 9px;
      color: #c09b5b;
      letter-spacing: 2.5px;
      text-transform: uppercase;
      font-weight: 500;
      margin-bottom: 13px;
      display: flex;
      align-items: center;
      gap: 10px
    }

    .sec-ttl::after {
      content: '';
      flex: 1;
      height: 1px;
      background: #e8e0d0
    }

    .il {
      font-size: 11px;
      color: #9a8e82;
      margin-bottom: 2px
    }

    .iv {
      font-size: 14px;
      font-weight: 500;
      margin-bottom: 8px
    }

    /* ── Dates bar ── */
    .dates-bar {
      background: linear-gradient(135deg, #faf7f2, #f0ece4);
      border: 1px solid #e0d8cc;
      border-radius: 12px;
      display: grid;
      grid-template-columns: 1fr 88px 1fr;
      margin-bottom: 26px;
      overflow: hidden
    }

    .dc {
      padding: 18px 22px
    }

    .dc:last-child {
      text-align: right
    }

    .dlbl {
      font-family: 'Cinzel', serif;
      font-size: 9px;
      color: #9a8e82;
      letter-spacing: 2px;
      text-transform: uppercase;
      margin-bottom: 5px
    }

    .dv {
      font-family: 'Cormorant Garamond', serif;
      font-size: 18px;
      font-weight: 400
    }

    .ds {
      font-size: 11px;
      color: #9a8e82;
      margin-top: 2px
    }

    .dm {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 18px 10px;
      border-left: 1px solid #e0d8cc;
      border-right: 1px solid #e0d8cc;
      background: #fff;
      text-align: center
    }

    .dn {
      font-family: 'Cormorant Garamond', serif;
      font-size: 30px;
      font-weight: 300;
      color: #c09b5b;
      line-height: 1
    }

    .dl {
      font-size: 10px;
      color: #9a8e82
    }

    /* ── Rooms table ── */
    .rt {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 22px
    }

    .rt thead th {
      padding: 10px 14px;
      font-size: 9px;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: #9a8e82;
      background: #faf7f2;
      border-bottom: 1px solid #e0d8cc;
      text-align: left;
      font-weight: 600;
      font-family: 'Cinzel', serif
    }

    .rt thead th:last-child {
      text-align: right
    }

    .rt tbody td {
      padding: 14px;
      font-size: 13.5px;
      border-bottom: 1px solid #f0ece4;
      vertical-align: middle
    }

    .rt tbody td:last-child {
      text-align: right;
      font-family: 'Cormorant Garamond', serif;
      font-size: 17px;
      color: #c09b5b
    }

    .rt tbody tr:last-child td {
      border-bottom: none
    }

    /* ── Price summary ── */
    .pbox {
      background: #faf7f2;
      border: 1px solid #e0d8cc;
      border-radius: 12px;
      overflow: hidden;
      margin-bottom: 20px
    }

    .pr {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 20px;
      border-bottom: 1px solid #e8e0d0;
      font-size: 13.5px
    }

    .pr:last-child {
      border-bottom: none
    }

    .pr .l {
      color: #6a6460
    }

    .pr .v {
      font-weight: 500
    }

    .pr.disc .l,
    .pr.disc .v {
      color: #2d6a4f
    }

    .pr.total {
      background: linear-gradient(135deg, #1c1813, #2d2520);
      padding: 18px 20px
    }

    .pr.total .l {
      color: rgba(255, 255, 255, .65);
      font-size: 15px;
      font-weight: 600
    }

    .pr.total .v {
      font-family: 'Cormorant Garamond', serif;
      font-size: 30px;
      color: #c09b5b
    }

    /* ── Payment info ── */
    .pay-row {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 16px 20px;
      background: #faf7f2;
      border: 1px solid #e0d8cc;
      border-radius: 12px;
      margin-bottom: 18px
    }

    .pay-ic {
      font-size: 28px
    }

    .pay-nm {
      font-size: 14px;
      font-weight: 600
    }

    .pay-dt {
      font-size: 12px;
      color: #9a8e82;
      margin-top: 2px
    }

    .ps-pill {
      padding: 3px 11px;
      border-radius: 10px;
      font-size: 11px;
      font-weight: 700;
      margin-left: auto
    }

    /* ── Special requests ── */
    .req-box {
      padding: 13px 16px;
      background: #faf7f2;
      border: 1px solid #e0d8cc;
      border-radius: 10px;
      margin-bottom: 18px;
      font-size: 13px;
      color: #4a4239;
      line-height: 1.7
    }

    /* ── Note ── */
    .note {
      padding: 13px 16px;
      background: #fff7e6;
      border: 1px solid #e8d5a0;
      border-radius: 10px;
      font-size: 12px;
      color: #7a5a1a;
      line-height: 1.8
    }

    /* ── Footer ── */
    .inv-ft {
      background: #1c1813;
      padding: 22px 46px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px
    }

    .inv-ft-brand {
      font-family: 'Cinzel', serif;
      color: #c09b5b;
      font-size: 15px
    }

    .inv-ft-info {
      font-size: 11px;
      color: rgba(255, 255, 255, .35);
      line-height: 1.9
    }

    .inv-ft-ct {
      font-size: 11px;
      color: rgba(255, 255, 255, .35);
      text-align: right;
      line-height: 1.9
    }

    /* ── Print ── */
    @media print {
      body {
        background: #fff !important
      }

      .tb {
        display: none !important
      }

      .page {
        margin: 0;
        padding: 0;
        max-width: 100%
      }

      .inv {
        box-shadow: none;
        border-radius: 0
      }

      .inv-hd,
      .pr.total {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact
      }

      .dates-bar,
      .pbox,
      .pay-row {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact
      }

      .inv-bd {
        padding: 24px 32px
      }

      @page {
        margin: .5cm;
        size: A4
      }
    }

    @media(max-width:640px) {

      .inv-hd,
      .inv-bd {
        padding: 22px 20px
      }

      .g2 {
        grid-template-columns: 1fr
      }

      .dates-bar {
        grid-template-columns: 1fr
      }

      .dm {
        border: none;
        border-top: 1px solid #e0d8cc;
        border-bottom: 1px solid #e0d8cc
      }

      .dc:last-child {
        text-align: left
      }

      .tb {
        padding: 12px 16px
      }

      .tb-name {
        display: none
      }

      .inv-hd-row {
        flex-direction: column;
        gap: 16px
      }

      .inv-no,
      .inv-no-lbl,
      .inv-dt {
        text-align: left
      }
    }
  </style>
</head>

<body>

  <!-- Toolbar -->
  <div class="tb">
    <a href="<?= BASE ?>" class="tb-logo">
      <div class="tb-emblem">RV</div>
      <span class="tb-name">Royale Vista</span>
    </a>
    <div class="tb-btns">
      <a href="<?= BASE ?>/bookings.php" class="tbtn tbtn-back">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <polyline points="15 18 9 12 15 6" />
        </svg>
        My Bookings
      </a>
      <button class="tbtn tbtn-pdf" onclick="downloadPDF()">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
          <polyline points="14 2 14 8 20 8" />
          <line x1="16" y1="13" x2="8" y2="13" />
          <line x1="16" y1="17" x2="8" y2="17" />
        </svg>
        Save & Download PDF
      </button>
    </div>
  </div>

  <div class="page">
    <div class="inv">

      <!-- Header -->
      <div class="inv-hd">
        <div class="inv-hd-row">
          <div>
            <div class="inv-brand">Royale Vista</div>
            <div class="inv-brand-sub">Official Tax Invoice</div>
            <div class="inv-status"
              style="background:rgba(255,255,255,.07);color:<?= $statusColor ?>;border:1px solid <?= $statusColor ?>40">
              <svg width="6" height="6">
                <circle cx="3" cy="3" r="3" fill="<?= $statusColor ?>" />
              </svg>
              <?= ucfirst(str_replace('_', ' ', $bk['status'])) ?>
            </div>
          </div>
          <div>
            <div class="inv-no-lbl">Invoice Number</div>
            <div class="inv-no"><?= htmlspecialchars($bk['invoice_no'] ?? $ref) ?></div>
            <div class="inv-dt">Booking: <strong style="color:#c09b5b"><?= htmlspecialchars($ref) ?></strong></div>
            <div class="inv-dt">Issued: <?= date('d M Y', strtotime($bk['created_at'])) ?></div>
          </div>
        </div>
      </div>

      <!-- Body -->
      <div class="inv-bd">

        <!-- Billed To / Hotel -->
        <div class="g2">
          <div>
            <div class="sec-ttl">Billed To</div>
            <div class="il">Guest Name</div>
            <div class="iv"><?= htmlspecialchars($bk['guest_name']) ?></div>
            <div class="il">Email Address</div>
            <div class="iv"><?= htmlspecialchars($bk['guest_email']) ?></div>
<?php if ($bk['guest_phone']): ?>
              <div class="il">Phone</div>
              <div class="iv"><?= htmlspecialchars($bk['guest_phone']) ?></div>
            <?php endif; ?>
            <?php if ($bk['adults']): ?>
              <div class="il">Guests</div>
              <div class="iv"><?= $bk['adults'] ?>
                Adult<?= $bk['adults'] > 1 ? 's' : '' ?><?= $bk['children'] > 0 ? ' · ' . $bk['children'] . ' Child' . ($bk['children'] > 1 ? 'ren' : '') : '' ?>
              </div>
            <?php endif; ?>
          </div>
          <div>
            <div class="sec-ttl">Property</div>
            <div style="font-weight:700;font-size:15px;margin-bottom:5px">Royale Vista</div>
            <div class="il" style="font-size:12.5px;line-height:2.1">
              1 Park Avenue, Midtown Manhattan<br>
              New York, NY 10016, USA<br>
              Tel: +1 212 555 0100<br>
              stay@royalevista.com<br>
              TRN: 100234567890003
            </div>
          </div>
        </div>

        <!-- Dates -->
        <div class="dates-bar">
          <div class="dc">
            <div class="dlbl">Check-In</div>
            <div class="dv"><?= date('D, d M Y', strtotime($bk['check_in'])) ?></div>
            <div class="ds">From 2:00 PM</div>
          </div>
          <div class="dm">
            <div class="dn"><?= $bk['nights'] ?></div>
            <div class="dl">Night<?= $bk['nights'] != 1 ? 's' : '' ?></div>
          </div>
          <div class="dc">
            <div class="dlbl">Check-Out</div>
            <div class="dv"><?= date('D, d M Y', strtotime($bk['check_out'])) ?></div>
            <div class="ds">By 12:00 PM</div>
          </div>
        </div>

        <!-- Rooms -->
        <div class="sec-ttl" style="margin-bottom:13px">Accommodation Details</div>
        <table class="rt">
          <thead>
            <tr>
              <th>Room Type</th>
              <th style="text-align:center">Qty</th>
              <th style="text-align:center">Nights</th>
              <th style="text-align:right">Nightly Rate</th>
              <th style="text-align:right">Amount</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rooms as $rm): ?>
              <tr>
                <td>
                  <div style="font-weight:600;margin-bottom:2px"><?= htmlspecialchars($rm['room_type_name']) ?></div>
                  <?php if (!empty($rm['assigned_rooms'])): ?>
                    <div style="font-size:11px;color:#9a8e82;margin-top:2px">🏷
                      Room<?= substr_count($rm['assigned_rooms'], ',') >= 1 ? 's' : '' ?>:
                      <?= htmlspecialchars($rm['assigned_rooms']) ?></div>
                  <?php endif; ?>
                  <?php if ($bk['member_number']): ?>
                    <span
                      style="background:#fff7e6;color:#c09b5b;font-size:9px;padding:1px 8px;border-radius:8px;border:1px solid #e8d5a0">Member
                      Rate</span>
                  <?php endif; ?>
                </td>
                <td style="text-align:center"><?= $rm['quantity'] ?></td>
                <td style="text-align:center"><?= $rm['nights'] ?></td>
                <td style="text-align:right;color:#6a6460"><?= inv_fmt($rm['price_usd']) ?></td>
                <td><?= inv_fmt($rm['total_usd']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Price Summary -->
        <div class="sec-ttl" style="margin-bottom:13px">Price Summary</div>
        <div class="pbox">
          <div class="pr"><span class="l">Room Charges Subtotal</span><span
              class="v"><?= inv_fmt($bk['total_usd']) ?></span></div>
          <?php if ($bk['discount_usd'] > 0): ?>
            <div class="pr disc">
              <span
                class="l">Discounts<?= $bk['offer_code'] ? ' — Code: ' . htmlspecialchars($bk['offer_code']) : '' ?><?= $bk['member_number'] ? ' · Member Rate' : '' ?></span>
              <span class="v">−<?= inv_fmt($bk['discount_usd']) ?></span>
            </div>
          <?php endif; ?>
          <div class="pr"><span class="l">Taxes & Service Fees (18% GST)</span><span
              class="v"><?= inv_fmt($bk['taxes_usd']) ?></span></div>
          <div class="pr total">
            <span class="l">Total Amount <?= $bk['pay_status'] === 'paid' ? 'Paid' : 'Due' ?></span>
            <span class="v"><?= inv_fmt($bk['final_usd']) ?></span>
          </div>
        </div>

        <!-- Payment -->
        <div class="pay-row">
          <div class="pay-ic"><?= $payIcons[$bk['pay_method']] ?? '💳' ?></div>
          <div>
            <div class="pay-nm"><?= $payNames[$bk['pay_method']] ?? 'Payment' ?></div>
            <?php if ($bk['paid_at']): ?>
              <div class="pay-dt">Paid on <?= date('d M Y H:i', strtotime($bk['paid_at'])) ?></div>
            <?php else: ?>
              <div class="pay-dt">Payment due at hotel check-in</div>
            <?php endif; ?>
          </div>
          <span class="ps-pill" style="background:<?= $psBg ?>;color:<?= $psTx ?>">
            <?= ucfirst($bk['pay_status']) ?>
          </span>
        </div>

        <!-- Special Requests -->
        <?php if ($bk['special_req']): ?>
          <div class="req-box">
            <div
              style="font-weight:600;color:#c09b5b;font-size:11px;letter-spacing:1px;text-transform:uppercase;margin-bottom:6px">
              Special Requests</div>
            <?= nl2br(htmlspecialchars($bk['special_req'])) ?>
          </div>
        <?php endif; ?>

        <!-- Note -->
        <div class="note">
          <strong>Important:</strong> Please present this invoice at check-in. All charges are in USD unless otherwise
          specified.
          Complimentary cancellation is available per your rate conditions.
          For assistance: <a href="tel:+12125550100">+1 212 555 0100</a> · <a
            href="mailto:stay@royalevista.com">stay@royalevista.com</a>
        </div>
      </div>

      <!-- Footer -->
      <div class="inv-ft">
        <div>
          <div class="inv-ft-brand">Royale Vista</div>
          <div class="inv-ft-info">Where Every Stay Becomes a Story</div>
        </div>
        <div class="inv-ft-ct">
          stay@royalevista.com<br>
          +1 212 555 0100<br>
          royalevista.com
        </div>
      </div>

    </div><!-- .inv -->
  </div><!-- .page -->

  <script>
    function downloadPDF() {
      const tb = document.querySelector('.tb');
      if (tb) tb.style.display = 'none';
      const element = document.querySelector('.inv');

      const opt = {
        margin: [0.5, 0.5, 0.5, 0.5],
        filename: 'Invoice_<?= preg_replace('/[^A-Z0-9_]/i', '', $ref) ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
      };

      const btn = document.querySelector('.tbtn-pdf');
      if (btn) btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';

      html2pdf().set(opt).from(element).outputPdf('blob').then(async function (pdfBlob) {
        // 1. Save to Server
        const fd = new FormData();
        fd.append('ref', '<?= $ref ?>');
        fd.append('pdf', pdfBlob, opt.filename);
        try {
          await fetch('<?= BASE ?>/api/save_invoice.php', { method: 'POST', body: fd });
        } catch (e) { console.error('Failed to save on server', e); }

        // 2. Download Client Side
        html2pdf().set(opt).from(element).save().then(function () {
          if (tb) tb.style.display = 'flex';
          if (btn) btn.innerHTML = '<svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg> Download PDF';
        });
      });
    }
    // Ctrl+P shortcut
    document.addEventListener('keydown', e => {
      if ((e.ctrlKey || e.metaKey) && e.key === 'p') { e.preventDefault(); window.print(); }
    });
  </script>
</body>

</html>