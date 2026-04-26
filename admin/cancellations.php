<?php
error_reporting(E_ALL);ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';

// Process refund
$msg='';
if(isset($_POST['refund_bk'])){
    $ref=clean($_POST['ref']??'');
    if($ref){
        $pdo->prepare("UPDATE bookings SET pay_status='refunded',status='cancelled' WHERE booking_ref=?")->execute([$ref]);
        $msg="Refund processed for $ref.";
    }
}

$rows=$pdo->query("SELECT b.*,GROUP_CONCAT(br.room_type_name SEPARATOR ', ') rooms_str FROM bookings b LEFT JOIN booked_rooms br ON br.booking_ref=b.booking_ref WHERE b.status='cancelled' GROUP BY b.id ORDER BY b.created_at DESC LIMIT 200")->fetchAll();
$tCan=(int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='cancelled'")->fetchColumn();
$tRef=(int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE pay_status='refunded'")->fetchColumn();
$tPendRef=(int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='cancelled' AND pay_status='paid'")->fetchColumn();
$refAmt=(float)$pdo->query("SELECT COALESCE(SUM(final_usd),0) FROM bookings WHERE pay_status='refunded'")->fetchColumn();
ob_start();?>
<?php if($msg):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?=htmlspecialchars($msg)?></div><?php endif;?>
<div class="adm-ph"><div><h1>Cancellations</h1><p class="sub">Manage cancelled bookings and refunds</p></div></div>
<div class="mc-grid mc-4" style="margin-bottom:22px">
  <div class="mc" style="--mc:#e07070"><div class="mc-ico" style="background:rgba(224,112,112,.12);color:var(--rd)"><i class="fas fa-ban"></i></div><div><div class="mc-v"><?=$tCan?></div><div class="mc-l">Cancelled</div></div></div>
  <div class="mc" style="--mc:#f59e0b"><div class="mc-ico" style="background:rgba(245,158,11,.12);color:var(--am)"><i class="fas fa-clock"></i></div><div><div class="mc-v"><?=$tPendRef?></div><div class="mc-l">Pending Refund</div></div></div>
  <div class="mc" style="--mc:#74a8e0"><div class="mc-ico" style="background:rgba(116,168,224,.12);color:var(--bl)"><i class="fas fa-rotate-left"></i></div><div><div class="mc-v"><?=$tRef?></div><div class="mc-l">Refunded</div></div></div>
  <div class="mc" style="--mc:#52b788"><div class="mc-ico" style="background:rgba(82,183,136,.12);color:var(--gr)"><i class="fas fa-dollar-sign"></i></div><div><div class="mc-v"><?=formatPrice($refAmt)?></div><div class="mc-l">Total Refunded</div></div></div>
</div>
<div class="ac"><div class="tw"><table>
  <thead><tr><th>Reference</th><th>Guest</th><th>Rooms</th><th>Check-In</th><th>Total</th><th>Pay Status</th><th>Cancelled</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach($rows as $b):$pc=['paid'=>'bg','pending'=>'ba','failed'=>'br','refunded'=>'bb'];?>
    <tr>
      <td><code><?=htmlspecialchars($b['booking_ref'])?></code></td>
      <td><div style="font-weight:500"><?=htmlspecialchars($b['guest_name'])?></div><div style="font-size:11px;color:var(--mu)"><?=htmlspecialchars($b['guest_email'])?></div></td>
      <td style="font-size:12.5px"><?=htmlspecialchars($b['rooms_str']??'—')?></td>
      <td style="font-size:13px;white-space:nowrap"><?=date('d M Y',strtotime($b['check_in']))?></td>
      <td style="font-family:var(--serif);font-size:16px;color:var(--gold)"><?=formatPrice($b['final_usd'])?></td>
      <td><span class="badge <?=$pc[$b['pay_status']]??'ba'?>"><?=ucfirst($b['pay_status'])?></span></td>
      <td style="font-size:12px;color:var(--mu)"><?=date('d M Y H:i',strtotime($b['created_at']))?></td>
      <td>
        <div style="display:flex;gap:5px">
          <?php if($b['pay_status']==='paid'):?>
          <form method="POST" style="display:inline" onsubmit="return confirm('Process refund for <?=htmlspecialchars($b['booking_ref'],ENT_QUOTES)?>?')">
            <input type="hidden" name="refund_bk" value="1"><input type="hidden" name="ref" value="<?=htmlspecialchars($b['booking_ref'])?>">
            <button class="btn btn-success btn-sm" type="submit"><i class="fas fa-rotate-left"></i> Refund</button>
          </form>
          <?php elseif($b['pay_status']==='refunded'):?>
          <span class="badge bb"><i class="fas fa-check" style="margin-right:4px"></i>Refunded</span>
          <?php endif;?>
          <a href="<?=BASE?>/invoice.php?ref=<?=urlencode($b['booking_ref'])?>" target="_blank" class="btn btn-ghost btn-sm"><i class="fas fa-file-invoice"></i></a>
        </div>
      </td>
    </tr>
    <?php endforeach;?>
    <?php if(empty($rows)):?><tr><td colspan="8" style="text-align:center;padding:48px;color:var(--mu)"><i class="fas fa-ban" style="display:block;font-size:36px;margin-bottom:12px;opacity:.2"></i>No cancellations</td></tr><?php endif;?>
  </tbody>
</table></div></div>
<?php $body=ob_get_clean(); adminPage('Cancellations — Admin',$body); ?>
