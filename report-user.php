<?php
/**
 * ROYALE VISTA — User Spending Report (Printable/PDF)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$uid = (int)$_SESSION['user_id'];
$userQ = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userQ->execute([$uid]);
$user = $userQ->fetch();

if (!$user) { header('Location: ' . BASE . '/login.php'); exit; }

// Fetch booking history
$bksQ = $pdo->prepare("SELECT b.*, GROUP_CONCAT(br.room_type_name SEPARATOR ', ') as rooms_list 
                       FROM bookings b 
                       LEFT JOIN booked_rooms br ON br.booking_ref = b.booking_ref 
                       WHERE b.user_id = ? 
                       GROUP BY b.id 
                       ORDER BY b.created_at DESC");
$bksQ->execute([$uid]);
$bookings = $bksQ->fetchAll();

// Stats
$totalSpent = 0;
$totalNights = 0;
foreach ($bookings as $bk) {
    if ($bk['pay_status'] === 'paid') {
        $totalSpent += $bk['final_usd'];
        $totalNights += $bk['nights'];
    }
}

$loyQ = $pdo->prepare("SELECT total_points FROM loyalty_points WHERE user_id = ?");
$loyQ->execute([$uid]);
$loyalty = (int)($loyQ->fetchColumn() ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Spending Report — <?= htmlspecialchars($user['display_name'] ?? $user['username']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500&family=DM+Sans:wght@400;500;600&family=Cinzel:wght@400;500&display=swap" rel="stylesheet">
<style>
:root { --gold: #c09b5b; --text: #1a1612; --muted: #8a7e72; --border: #e8e0d0; --bg: #fdfaf5; }
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;color:var(--text);background:#eee;padding:20px}
.paper{max-width:840px;margin:0 auto;background:#fff;padding:60px;box-shadow:0 10px 30px rgba(0,0,0,0.1);min-height:29.7cm}
.hd{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid var(--gold);padding-bottom:30px;margin-bottom:40px}
.brand{font-family:'Cinzel',serif;font-size:28px;color:var(--gold);letter-spacing:2px}
.brand-sub{font-size:10px;text-transform:uppercase;letter-spacing:4px;color:var(--muted);margin-top:4px}
.report-title{text-align:right}
.report-title h2{font-family:'Cinzel',serif;font-size:20px;letter-spacing:2px}
.report-title p{font-size:12px;color:var(--muted);margin-top:5px}

.summary-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:40px}
.stat-box{border:1px solid var(--border);padding:15px;border-radius:8px;text-align:center}
.stat-val{font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--gold);font-weight:600}
.stat-lbl{font-size:10px;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-top:4px}

.section-title{font-family:'Cinzel',serif;font-size:14px;margin-bottom:15px;border-bottom:1px solid var(--border);padding-bottom:8px}
.guest-info{margin-bottom:30px}
.guest-info p{font-size:14px;margin-bottom:5px}

table{width:100%;border-collapse:collapse;margin-bottom:40px}
th{text-align:left;padding:12px;font-size:11px;text-transform:uppercase;letter-spacing:1px;background:#faf8f5;border-bottom:2px solid var(--border)}
td{padding:12px;font-size:13px;border-bottom:1px solid #f5f0e8}
.price{font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:600;color:var(--gold)}
.status-paid{color:#2d6a4f;font-weight:600}
.status-other{color:var(--muted)}

.ft{margin-top:auto;text-align:center;border-top:1px solid var(--border);padding-top:20px;font-size:11px;color:var(--muted)}

@media print{
  body{background:white!important;padding:0!important}
  .paper{box-shadow:none!important;margin:0!important;max-width:none!important;width:100%!important}
  .no-print{display:none!important}
  @page{margin:1.5cm;size:A4}
}
.no-print{background:var(--gold);color:white;border:none;padding:10px 20px;border-radius:6px;cursor:pointer;margin-bottom:20px;font-family:inherit;font-weight:600}
</style>
</head>
<body>

<div style="text-align:center">
  <button class="no-print" onclick="window.print()"><i class="fas fa-print"></i> Print / Save as PDF</button>
</div>

<div class="paper">
  <div class="hd">
    <div>
      <div class="brand">Royale Vista</div>
      <div class="brand-sub">Luxury Resorts & Spas</div>
    </div>
    <div class="report-title">
      <h2>Spending & Activity Report</h2>
      <p>Report Generated: <?= date('d F Y') ?></p>
    </div>
  </div>

  <div class="guest-info">
    <div class="section-title">Guest Profile</div>
    <p><strong>Name:</strong> <?= htmlspecialchars($user['display_name'] ?? $user['username']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
    <p><strong>Account ID:</strong> RV-<?= str_pad($user['id'], 6, '0', STR_PAD_LEFT) ?></p>
  </div>

  <div class="summary-grid">
    <div class="stat-box"><div class="stat-val"><?= count($bookings) ?></div><div class="stat-lbl">Total Stays</div></div>
    <div class="stat-box"><div class="stat-val"><?= $totalNights ?></div><div class="stat-lbl">Nights Spent</div></div>
    <div class="stat-box"><div class="stat-val"><?= formatPrice($totalSpent) ?></div><div class="stat-lbl">Lifetime Spend</div></div>
    <div class="stat-box"><div class="stat-val"><?= number_format($loyalty) ?></div><div class="stat-lbl">Loyalty Points</div></div>
  </div>

  <div class="section-title">Reservation History</div>
  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Reference</th>
        <th>Accommodation</th>
        <th>Nights</th>
        <th>Status</th>
        <th>Amount</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($bookings as $bk): ?>
      <tr>
        <td><?= date('d M Y', strtotime($bk['created_at'])) ?></td>
        <td style="font-family:monospace"><?= htmlspecialchars($bk['booking_ref']) ?></td>
        <td><?= htmlspecialchars($bk['rooms_list'] ?: 'Room') ?></td>
        <td style="text-align:center"><?= $bk['nights'] ?></td>
        <td><span class="<?= $bk['pay_status']==='paid'?'status-paid':'status-other' ?>"><?= ucfirst(str_replace('_',' ',$bk['status'])) ?></span></td>
        <td class="price"><?= formatPrice($bk['final_usd']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="ft">
    <p>Royale Vista Luxury Hotels — 1 Park Avenue, Midtown Manhattan, New York, NY 10016</p>
    <p>www.royalevista.com · stay@royalevista.com · +1 212 555 0100</p>
    <p style="margin-top:10px;text-transform:uppercase;letter-spacing:1px">Thank you for choosing Royale Vista.</p>
  </div>
</div>

</body>
</html>
