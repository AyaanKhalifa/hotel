<?php
error_reporting(E_ALL);ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['upd_mem'])){
    $id=(int)$_POST['mid'];$pr=(float)$_POST['price'];$dc=(int)$_POST['disc'];
    if($id&&$pr>0&&$dc>=0&&$dc<=100){$pdo->prepare("UPDATE memberships SET price_usd=?,discount_pct=? WHERE id=?")->execute([$pr,$dc,$id]);$msg='Updated.';}
}
if(isset($_GET['exp'])&&is_numeric($_GET['exp'])){$pdo->prepare("UPDATE user_memberships SET status='expired' WHERE id=?")->execute([(int)$_GET['exp']]);$msg='Expired.';header('Location: '.$_SERVER['PHP_SELF']);exit;}
$mems=$pdo->query("SELECT m.*,(SELECT COUNT(*) FROM user_memberships um WHERE um.membership_id=m.id AND um.status='active') ac FROM memberships m ORDER BY m.sort_order")->fetchAll();
$ums=$pdo->query("SELECT um.*,u.name uname,u.email,m.name mname,m.icon FROM user_memberships um JOIN users u ON um.user_id=u.id JOIN memberships m ON um.membership_id=m.id ORDER BY um.created_at DESC LIMIT 60")->fetchAll();
$tAct=(int)$pdo->query("SELECT COUNT(*) FROM user_memberships WHERE status='active'")->fetchColumn();
$tRev=(float)$pdo->query("SELECT COALESCE(SUM(m.price_usd),0) FROM user_memberships um JOIN memberships m ON um.membership_id=m.id WHERE um.status='active'")->fetchColumn();
ob_start();?>
<?php if($msg):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?=htmlspecialchars($msg)?></div><?php endif;?>
<div class="adm-ph"><div><h1>Memberships</h1><p class="sub"><?=$tAct?> active members</p></div></div>
<div class="mc-grid mc-3" style="margin-bottom:22px">
  <div class="mc" style="--mc:#c09b5b"><div class="mc-ico" style="background:rgba(192,155,91,.12);color:var(--gold)"><i class="fas fa-crown"></i></div><div><div class="mc-v"><?=$tAct?></div><div class="mc-l">Active Members</div></div></div>
  <div class="mc" style="--mc:#22c55e"><div class="mc-ico" style="background:rgba(34,197,94,.12);color:var(--gr)"><i class="fas fa-coins"></i></div><div><div class="mc-v"><?=formatPrice($tRev)?></div><div class="mc-l">Membership Revenue</div></div></div>
  <div class="mc" style="--mc:#8b5cf6"><div class="mc-ico" style="background:rgba(139,92,246,.12);color:#8b5cf6"><i class="fas fa-layer-group"></i></div><div><div class="mc-v"><?=count($mems)?></div><div class="mc-l">Plans</div></div></div>
</div>
<div class="ac" style="margin-bottom:22px"><div class="ac-hd"><div class="ac-title"><i class="fas fa-crown" style="color:var(--gold)"></i> Membership Plans</div></div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:0">
    <?php foreach($mems as $m):?>
    <div style="padding:20px;border-right:1px solid var(--br2)">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px"><span style="font-size:22px"><?=$m['icon']?></span><div style="font-weight:600"><?=htmlspecialchars($m['name'])?></div></div>
      <div style="font-size:12px;color:var(--gold);margin-bottom:10px"><?=$m['ac']?> active members</div>
      <form method="POST" style="display:flex;flex-direction:column;gap:9px">
        <input type="hidden" name="upd_mem" value="1"><input type="hidden" name="mid" value="<?=$m['id']?>">
        <div><label class="fl">Price (USD)</label><input class="fc" type="number" name="price" value="<?=$m['price_usd']?>" min="1" step="1" required></div>
        <div><label class="fl">Discount %</label><input class="fc" type="number" name="disc" value="<?=$m['discount_pct']?>" min="0" max="100" required></div>
        <button type="submit" class="btn btn-gold btn-sm"><i class="fas fa-save"></i> Save</button>
      </form>
    </div>
    <?php endforeach;?>
  </div></div>
<div class="ac"><div class="ac-hd"><div class="ac-title"><i class="fas fa-users" style="color:var(--gold)"></i> Active Members</div></div>
  <div class="tw"><table>
    <thead><tr><th>Member</th><th>Plan</th><th>Member No.</th><th>Expires</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
      <?php $sc=['active'=>'bg','expired'=>'br','cancelled'=>'ba'];
      foreach($ums as $um):?>
      <tr>
        <td><div style="font-weight:500"><?=htmlspecialchars($um['uname'])?></div><div style="font-size:11px;color:var(--mu)"><?=htmlspecialchars($um['email'])?></div></td>
        <td><?=$um['icon']?> <?=htmlspecialchars($um['mname'])?></td>
        <td><code><?=htmlspecialchars($um['member_number']??'—')?></code></td>
        <td style="font-size:12px;color:var(--mu)"><?=$um['expires_at']?date('d M Y',strtotime($um['expires_at'])):'Never'?></td>
        <td><span class="badge <?=$sc[$um['status']]??'bgold'?>"><?=ucfirst($um['status'])?></span></td>
        <td><?php if($um['status']==='active'):?><a href="?exp=<?=$um['id']?>" class="btn btn-ghost btn-sm" onclick="return confirm('Expire?')"><i class="fas fa-ban"></i></a><?php endif;?></td>
      </tr>
      <?php endforeach;?>
      <?php if(empty($ums)):?><tr><td colspan="6" style="text-align:center;padding:30px;color:var(--mu)">No active memberships</td></tr><?php endif;?>
    </tbody>
  </table></div></div>
<?php $body=ob_get_clean(); adminPage('Memberships — Admin',$body); ?>
