<?php
error_reporting(E_ALL);ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['upd_type'])){
    $id=(int)$_POST['rtid'];$pr=(float)$_POST['price'];
    if($id&&$pr>0){$pdo->prepare("UPDATE room_types SET price_usd=? WHERE id=?")->execute([$pr,$id]);$msg='Price updated for room type #'.$id.'.';}
}
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['add_override'])){
    $rtid=(int)$_POST['rtid'];$df=clean($_POST['date_from']??'');$dt=clean($_POST['date_to']??'');$pr=(float)$_POST['price'];$rsn=clean($_POST['reason']??'');
    if($rtid&&$df&&$dt&&$pr>0){$pdo->prepare("INSERT INTO price_overrides (room_type_id,date_from,date_to,price_usd,reason) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE price_usd=VALUES(price_usd)")->execute([$rtid,$df,$dt,$pr,$rsn]);$msg='Price override added.';}
}
if(isset($_GET['del_ov'])&&is_numeric($_GET['del_ov'])){$pdo->prepare("DELETE FROM price_overrides WHERE id=?")->execute([(int)$_GET['del_ov']]);header('Location: '.$_SERVER['PHP_SELF']);exit;}
$rts=$pdo->query("SELECT rt.*,(SELECT AVG(final_usd) FROM bookings b JOIN booked_rooms br ON br.booking_ref=b.booking_ref WHERE br.room_type_id=rt.id AND b.pay_status='paid') avg_rev FROM room_types rt ORDER BY rt.sort_order")->fetchAll();
$ovs=$pdo->query("SELECT po.*,rt.name rname FROM price_overrides po JOIN room_types rt ON po.room_type_id=rt.id ORDER BY po.date_from DESC LIMIT 50")->fetchAll();
ob_start();?>
<?php if($msg):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?=htmlspecialchars($msg)?></div><?php endif;?>
<div class="adm-ph"><div><h1>Pricing</h1><p class="sub">Base rates and seasonal overrides</p></div>
  <button class="btn btn-gold btn-sm" onclick="openModal(document.getElementById('override-tpl').innerHTML)"><i class="fas fa-plus"></i> Add Override</button></div>
<div class="ac" style="margin-bottom:22px">
  <div class="ac-hd"><div class="ac-title"><i class="fas fa-bed" style="color:var(--gold)"></i> Room Type Base Prices</div></div>
  <div class="ac-body" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px">
    <?php foreach($rts as $rt):?>
    <div style="background:var(--card2);border:1px solid var(--br2);border-radius:10px;padding:18px;cursor:pointer;transition:border-color .2s" onmouseover="this.style.borderColor='var(--gold)'" onmouseout="this.style.borderColor=''">
      <div style="font-family:var(--serif);font-size:16px;font-weight:400;margin-bottom:4px"><?=htmlspecialchars($rt['name'])?></div>
      <form method="POST" style="display:flex;gap:8px;margin-top:10px" onsubmit="return confirm('Update price?')">
        <input type="hidden" name="upd_type" value="1"><input type="hidden" name="rtid" value="<?=$rt['id']?>">
        <div style="position:relative;flex:1"><span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--gold);font-size:13px">$</span><input type="number" name="price" value="<?=$rt['price_usd']?>" min="1" step="1" style="padding-left:24px" class="fc" required></div>
        <button type="submit" class="btn btn-gold btn-sm"><i class="fas fa-save"></i></button>
      </form>
      <div style="font-size:11px;color:var(--mu);margin-top:8px">Avg booking: <?=formatPrice($rt['avg_rev']??0)?> · Max <?=$rt['max_guests']?> guests</div>
    </div>
    <?php endforeach;?>
  </div>
</div>
<div class="ac">
  <div class="ac-hd"><div class="ac-title"><i class="fas fa-calendar-alt" style="color:var(--gold)"></i> Seasonal Price Overrides</div></div>
  <div class="tw"><table>
    <thead><tr><th>Room Type</th><th>From</th><th>To</th><th>Override Price</th><th>Reason</th><th>Del</th></tr></thead>
    <tbody>
      <?php foreach($ovs as $o):?>
      <tr><td><?=htmlspecialchars($o['rname'])?></td><td><?=date('d M Y',strtotime($o['date_from']))?></td><td><?=date('d M Y',strtotime($o['date_to']))?></td><td style="font-family:var(--serif);color:var(--gold)"><?=formatPrice($o['price_usd'])?></td><td style="font-size:12.5px;color:var(--mu)"><?=htmlspecialchars($o['reason']??'—')?></td><td><a href="?del_ov=<?=$o['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a></td></tr>
      <?php endforeach;?>
      <?php if(empty($ovs)):?><tr><td colspan="6" style="text-align:center;padding:30px;color:var(--mu)">No overrides set</td></tr><?php endif;?>
    </tbody>
  </table></div>
</div>
<template id="override-tpl">
  <div class="adm-modal"><div class="adm-modal-hd"><div class="adm-modal-title">Add Price Override</div><button class="adm-modal-x" onclick="closeModal()">×</button></div>
  <form method="POST" class="adm-modal-bd"><input type="hidden" name="add_override" value="1">
    <div class="fg"><label class="fl">Room Type *</label><select class="fc" name="rtid" required>
      <?php foreach($rts as $rt):?><option value="<?=$rt['id']?>"><?=htmlspecialchars($rt['name'])?></option><?php endforeach;?>
    </select></div>
    <div class="fr"><div class="fg"><label class="fl">From Date *</label><input class="fc" type="date" name="date_from" min="<?=date('Y-m-d')?>" required></div>
    <div class="fg"><label class="fl">To Date *</label><input class="fc" type="date" name="date_to" min="<?=date('Y-m-d',strtotime('+1 day'))?>" required></div></div>
    <div class="fg"><label class="fl">Override Price (USD) *</label><input class="fc" type="number" name="price" min="1" step="1" placeholder="e.g. 450" required></div>
    <div class="fg"><label class="fl">Reason</label><input class="fc" name="reason" placeholder="e.g. Christmas peak season"></div>
    <div class="adm-modal-ft"><button type="button" class="btn btn-ghost btn-sm" onclick="closeModal()">Cancel</button><button type="submit" class="btn btn-gold btn-sm"><i class="fas fa-save"></i> Save</button></div>
  </form></div>
</template>
<?php $body=ob_get_clean(); adminPage('Pricing — Admin',$body); ?>
