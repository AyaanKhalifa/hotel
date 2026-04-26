<?php
error_reporting(E_ALL);ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';

$logs=$pdo->query("SELECT pl.*,b.guest_name,b.guest_email,b.status bk_status FROM payment_logs pl LEFT JOIN bookings b ON b.booking_ref=pl.booking_ref ORDER BY pl.created_at DESC LIMIT 300")->fetchAll();
$tPaid=(float)$pdo->query("SELECT COALESCE(SUM(amount_usd),0) FROM payment_logs WHERE status='success'")->fetchColumn();
$tCount=(int)$pdo->query("SELECT COUNT(*) FROM payment_logs WHERE status='success'")->fetchColumn();
$tFail=(int)$pdo->query("SELECT COUNT(*) FROM payment_logs WHERE status='failed'")->fetchColumn();
$tRef=(float)$pdo->query("SELECT COALESCE(SUM(amount_usd),0) FROM payment_logs WHERE status='refunded'")->fetchColumn();
$methods=$pdo->query("SELECT method,COUNT(*) cnt FROM payment_logs GROUP BY method")->fetchAll();
ob_start();?>
<div class="adm-ph"><div><h1>Payments</h1><p class="sub">All payment transactions and logs</p></div></div>
<div class="mc-grid mc-4" style="margin-bottom:22px">
  <div class="mc" style="--mc:#22c55e"><div class="mc-ico" style="background:rgba(34,197,94,.12);color:var(--gr)"><i class="fas fa-check-circle"></i></div><div><div class="mc-v"><?=formatPrice($tPaid)?></div><div class="mc-l">Total Collected</div></div></div>
  <div class="mc" style="--mc:#d4af37"><div class="mc-ico" style="background:rgba(192,155,91,.12);color:var(--gold)"><i class="fas fa-receipt"></i></div><div><div class="mc-v"><?=$tCount?></div><div class="mc-l">Successful</div></div></div>
  <div class="mc" style="--mc:#e07070"><div class="mc-ico" style="background:rgba(224,112,112,.12);color:var(--rd)"><i class="fas fa-times-circle"></i></div><div><div class="mc-v"><?=$tFail?></div><div class="mc-l">Failed</div></div></div>
  <div class="mc" style="--mc:#74a8e0"><div class="mc-ico" style="background:rgba(116,168,224,.12);color:var(--bl)"><i class="fas fa-rotate-left"></i></div><div><div class="mc-v"><?=formatPrice($tRef)?></div><div class="mc-l">Refunded</div></div></div>
</div>
<div class="g2" style="gap:20px;margin-bottom:22px">
  <div class="ac" style="grid-column:2/3"><div class="ac-hd"><div class="ac-title"><i class="fas fa-chart-pie" style="color:var(--gold)"></i> Payment Methods</div></div>
    <div class="ac-body"><table><thead><tr><th>Method</th><th>Count</th><th>% of Total</th></tr></thead><tbody>
    <?php $tot=array_sum(array_column($methods,'cnt'));foreach($methods as $m):?>
    <tr><td><?= ['card'=>'💳 Credit/Debit Card','upi'=>'📱 UPI','hotel'=>'🏨 Pay at Hotel'][$m['method']] ?? ucfirst($m['method']) ?></td><td><span class="badge bgold"><?=$m['cnt']?></span></td><td><?=$tot>0?round($m['cnt']/$tot*100).'%':'—'?></td></tr>
    <?php endforeach;?></tbody></table></div></div>
</div>
<div class="ac"><div class="tw"><table>
  <thead><tr><th>Transaction ID</th><th>Booking Ref</th><th>Guest</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead>
  <tbody>
    <?php $pc=['success'=>'bg','pending'=>'ba','failed'=>'br','refunded'=>'bb'];
    foreach($logs as $l):?>
    <tr>
      <td><code style="font-size:11px"><?=htmlspecialchars(mb_strimwidth($l['transaction_id']??'—',0,20,'…'))?></code></td>
      <td><code><?=htmlspecialchars($l['booking_ref'])?></code></td>
      <td><div style="font-size:13px"><?=htmlspecialchars($l['guest_name']??'—')?></div></td>
      <td style="font-family:var(--serif);font-size:16px;color:var(--gold)"><?=formatPrice($l['amount_usd'])?></td>
      <td><?= ['card'=>'💳','upi'=>'📱','hotel'=>'🏨'][$l['method']] ?? '💰' ?> <?=ucfirst($l['method'])?></td>
      <td><span class="badge <?=$pc[$l['status']]??'ba'?>"><?=ucfirst($l['status'])?></span></td>
      <td style="font-size:12px;color:var(--mu);white-space:nowrap"><?=date('d M Y H:i',strtotime($l['created_at']))?></td>
    </tr>
    <?php endforeach;?>
    <?php if(empty($logs)):?><tr><td colspan="7" style="text-align:center;padding:40px;color:var(--mu)">No transactions yet</td></tr><?php endif;?>
  </tbody>
</table></div></div>
<?php $body=ob_get_clean(); adminPage('Payments — Admin',$body); ?>
