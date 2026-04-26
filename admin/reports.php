<?php
error_reporting(E_ALL);ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';

$period = clean($_GET['period'] ?? '30');
$from   = clean($_GET['from'] ?? '');
$to     = clean($_GET['to'] ?? '');

if ($from && $to) {
    $start = $from . ' 00:00:00';
    $end   = $to . ' 23:59:59';
    $isCustom = true;
} else {
    $start = date('Y-m-d 00:00:00', strtotime("-{$period} days"));
    $end   = date('Y-m-d 23:59:59');
    $isCustom = false;
}

$revQ = $pdo->prepare("SELECT COALESCE(SUM(final_usd),0) FROM bookings WHERE created_at BETWEEN ? AND ? AND pay_status='paid'");
$revQ->execute([$start, $end]); $rev = (float)$revQ->fetchColumn();

$bkQ = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE created_at BETWEEN ? AND ?");
$bkQ->execute([$start, $end]); $bkCt = (int)$bkQ->fetchColumn();

$gstQ = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM bookings WHERE created_at BETWEEN ? AND ?");
$gstQ->execute([$start, $end]); $gstCt = (int)$gstQ->fetchColumn();

$topRm = $pdo->prepare("SELECT rt.name, COUNT(br.id) c, COALESCE(SUM(br.total_usd),0) r FROM booked_rooms br JOIN room_types rt ON rt.id=br.room_type_id JOIN bookings b ON b.booking_ref=br.booking_ref WHERE b.created_at BETWEEN ? AND ? AND b.pay_status='paid' GROUP BY rt.id ORDER BY c DESC");
$topRm->execute([$start, $end]); $topRms = $topRm->fetchAll();

$byDay = $pdo->prepare("SELECT DATE(created_at) d, COUNT(*) c, COALESCE(SUM(final_usd),0) r FROM bookings WHERE created_at BETWEEN ? AND ? AND pay_status='paid' GROUP BY DATE(created_at) ORDER BY d");
$byDay->execute([$start, $end]); $daily = $byDay->fetchAll();

$byStatus = $pdo->prepare("SELECT status, COUNT(*) c FROM bookings WHERE created_at BETWEEN ? AND ? GROUP BY status");
$byStatus->execute([$start, $end]); $byS = $byStatus->fetchAll();

ob_start(); ?>
<style>
  .filter-bar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; margin-bottom: 22px; background: var(--card); padding: 16px 20px; border-radius: var(--radius-lg); border: 1px solid var(--border); }
  .filter-group { display: flex; align-items: center; gap: 6px; }
  .date-input { background: var(--input); border: 1px solid var(--border); color: var(--text); padding: 6px 12px; border-radius: 6px; font-family: var(--sans); font-size: 13px; }
  
  @media print {
    .adm-top, .adm-sb, .filter-bar, .adm-ph .btn-sm, .ac-hd .btn-sm { display: none !important; }
    .adm-layout { display: block !important; padding: 0 !important; }
    .adm-main { margin: 0 !important; padding: 0 !important; width: 100% !important; background: #fff !important; color: #000 !important; }
    .mc-grid { grid-template-columns: repeat(3, 1fr) !important; gap: 10px !important; }
    .mc { border: 1px solid #ddd !important; box-shadow: none !important; color: #000 !important; }
    .ac { border: 1px solid #ddd !important; box-shadow: none !important; break-inside: avoid; }
    .tw table { width: 100% !important; border-collapse: collapse; }
    .tw th, .tw td { border: 1px solid #ddd !important; color: #000 !important; }
    .badge { border: 1px solid #ccc !important; color: #000 !important; background: transparent !important; }
    @page { margin: 1cm; size: A4 landscape; }
  }
</style>

<div class="adm-ph">
  <div>
    <h1>Reports</h1>
    <p class="sub">
      <?php if ($isCustom): ?>
        Custom Range: <?= date('d M Y', strtotime($start)) ?> — <?= date('d M Y', strtotime($end)) ?>
      <?php else: ?>
        Business intelligence for the last <?= $period ?> days
      <?php endif; ?>
    </p>
  </div>
  <div style="display:flex;gap:8px">
    <button class="btn btn-gold btn-sm" onclick="window.print()"><i class="fas fa-file-pdf"></i> Download PDF</button>
    <a href="?period=<?= $period ?>&from=<?= $from ?>&to=<?= $to ?>&export=1" class="btn btn-ghost btn-sm"><i class="fas fa-file-csv"></i> Export CSV</a>
  </div>
</div>

<div class="filter-bar">
  <div class="filter-group">
    <?php foreach (['7' => '7D', '14' => '14D', '30' => '30D', '90' => '90D', '365' => '1Y'] as $d => $l): ?>
      <a href="?period=<?= $d ?>" class="btn <?= ($period === $d && !$isCustom) ? 'btn-gold' : 'btn-ghost' ?> btn-sm"><?= $l ?></a>
    <?php endforeach; ?>
  </div>
  <form class="filter-group" method="GET">
    <span style="font-size:12px;color:var(--muted)">Custom Range:</span>
    <input type="date" name="from" class="date-input" value="<?= htmlspecialchars($from) ?>" required>
    <span style="color:var(--muted)">→</span>
    <input type="date" name="to" class="date-input" value="<?= htmlspecialchars($to) ?>" required>
    <button type="submit" class="btn btn-gold btn-sm">Filter</button>
    <?php if ($isCustom): ?>
      <a href="?" class="btn btn-ghost btn-sm"><i class="fas fa-times"></i></a>
    <?php endif; ?>
  </form>
</div>

<div class="mc-grid mc-3" style="margin-bottom:22px">
  <div class="mc" style="--mc:#c09b5b"><div class="mc-ico" style="background:rgba(192,155,91,0.12);color:var(--gold)"><i class="fas fa-coins"></i></div><div><div class="mc-v"><?= formatPrice($rev) ?></div><div class="mc-l">Revenue</div></div></div>
  <div class="mc" style="--mc:#22c55e"><div class="mc-ico" style="background:rgba(34,197,94,0.12);color:var(--gr)"><i class="fas fa-calendar-check"></i></div><div><div class="mc-v"><?= $bkCt ?></div><div class="mc-l">Total Bookings</div></div></div>
  <div class="mc" style="--mc:#3b82f6"><div class="mc-ico" style="background:rgba(59,130,246,0.12);color:var(--bl)"><i class="fas fa-users"></i></div><div><div class="mc-v"><?= $gstCt ?></div><div class="mc-l">Unique Guests</div></div></div>
</div>

<div class="g2" style="gap:20px;margin-bottom:22px">
  <div class="ac"><div class="ac-hd"><div class="ac-title"><i class="fas fa-trophy" style="color:var(--gold)"></i> Top Rooms</div></div>
    <div class="tw"><table><thead><tr><th>#</th><th>Room</th><th>Bookings</th><th>Revenue</th></tr></thead><tbody>
    <?php foreach ($topRms as $i => $r): ?>
    <tr><td style="color:var(--mu);font-family:var(--serif)"><?= $i + 1 ?></td><td style="font-weight:500"><?= htmlspecialchars($r['name']) ?></td><td><span class="badge bgold"><?= $r['c'] ?></span></td><td style="font-family:var(--serif);color:var(--gold)"><?= formatPrice($r['r']) ?></td></tr>
    <?php endforeach; ?><?php if (empty($topRms)): ?><tr><td colspan="4" style="text-align:center;padding:20px;color:var(--mu)">No data for this period</td></tr><?php endif; ?>
    </tbody></table></div></div>
  <div class="ac"><div class="ac-hd"><div class="ac-title"><i class="fas fa-chart-pie" style="color:var(--gold)"></i> Status Breakdown</div></div>
    <div class="ac-body"><table><thead><tr><th>Status</th><th>Count</th></tr></thead><tbody>
    <?php $sc = ['confirmed' => 'bgold', 'cancelled' => 'br', 'checked_in' => 'bg', 'checked_out' => 'bb'];
    foreach ($byS as $s): ?><tr><td><span class="badge <?= $sc[$s['status']] ?? 'bgold' ?>"><?= ucfirst(str_replace('_', ' ', $s['status'])) ?></span></td><td style="font-family:var(--serif);font-size:16px"><?= $s['c'] ?></td></tr><?php endforeach; ?><?php if (empty($byS)): ?><tr><td colspan="2" style="text-align:center;padding:20px;color:var(--mu)">No data</td></tr><?php endif; ?></tbody></table></div></div>
</div>

<div class="ac"><div class="ac-hd"><div class="ac-title"><i class="fas fa-table" style="color:var(--gold)"></i> Daily Revenue Breakdown</div></div>
<?php
if (isset($_GET['export'])) {
  ob_end_clean();
  header('Content-Type:text/csv');
  header('Content-Disposition:attachment;filename="report_'.date('Y-m-d').'.csv"');
  echo "Date,Bookings,Revenue (USD)\n";
  foreach ($daily as $d) echo $d['d'] . ',' . $d['c'] . ',' . $d['r'] . "\n";
  exit;
} ?>
<div class="tw"><table><thead><tr><th>Date</th><th>Bookings</th><th>Revenue</th></tr></thead><tbody>
<?php $tRev = 0; $tBk = 0; foreach ($daily as $d): $tRev += $d['r']; $tBk += $d['c']; ?>
<tr><td><?= date('D d M Y', strtotime($d['d'])) ?></td><td><span class="badge bgold"><?= $d['c'] ?></span></td><td style="font-family:var(--serif);color:var(--gold)"><?= formatPrice($d['r']) ?></td></tr>
<?php endforeach; ?><?php if (empty($daily)): ?><tr><td colspan="3" style="text-align:center;padding:40px;color:var(--mu)">No active bookings recorded in this period</td></tr><?php endif; ?>
<tfoot><tr><td style="font-weight:700">Total Summary</td><td style="font-weight:700"><?= $tBk ?> Bookings</td><td style="font-family:var(--serif);font-size:18px;color:var(--gold);border-top:2px solid var(--br)"><?= formatPrice($tRev) ?></td></tr></tfoot>
</table></div></div>
<?php $body = ob_get_clean(); adminPage('Reports — Admin', $body); ?>
