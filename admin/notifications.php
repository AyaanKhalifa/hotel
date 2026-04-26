<?php
error_reporting(E_ALL);ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['send_notif'])){
    $uid=(int)($_POST['uid']??0);$tp=clean($_POST['type']??'system');$ti=clean($_POST['title']??'');$ms=clean($_POST['message']??'');
    if($ti&&$ms){
        if($uid>0){$pdo->prepare("INSERT INTO notifications (user_id,type,title,message) VALUES (?,?,?,?)")->execute([$uid,$tp,$ti,$ms]);}
        else{$users=$pdo->query("SELECT id FROM users WHERE role='user'")->fetchAll();foreach($users as $u){$pdo->prepare("INSERT INTO notifications (user_id,type,title,message) VALUES (?,?,?,?)")->execute([$u['id'],$tp,$ti,$ms]);}}
        $msg='Notification sent.';
    }
}
if(isset($_GET['del'])&&is_numeric($_GET['del'])){$pdo->prepare("DELETE FROM notifications WHERE id=?")->execute([(int)$_GET['del']]);header('Location: '.$_SERVER['PHP_SELF']);exit;}
$ns=$pdo->query("SELECT n.*,u.name uname FROM notifications n LEFT JOIN users u ON u.id=n.user_id ORDER BY n.created_at DESC LIMIT 200")->fetchAll();
$allUsers=$pdo->query("SELECT id,name FROM users WHERE role='user' ORDER BY name LIMIT 200")->fetchAll();
$tN=(int)$pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
$tU=(int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read=0")->fetchColumn();
ob_start();?>
<?php if($msg):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?=htmlspecialchars($msg)?></div><?php endif;?>
<div class="adm-ph"><div><h1>Notifications</h1><p class="sub"><?=$tU?> unread · <?=$tN?> total</p></div>
  <button class="btn btn-gold btn-sm" onclick="openModal(document.getElementById('notif-tpl').innerHTML)"><i class="fas fa-paper-plane"></i> Send Notification</button></div>
<div class="ac"><div class="tw"><table>
  <thead><tr><th>User</th><th>Type</th><th>Title</th><th>Message</th><th>Read</th><th>Date</th><th>Del</th></tr></thead>
  <tbody>
    <?php $tc=['booking'=>'bgold','cancellation'=>'br','system'=>'bb','review'=>'ba','payment'=>'bg','loyalty'=>'bgold','offer'=>'ba'];
    foreach($ns as $n):?>
    <tr>
      <td style="font-size:12.5px"><?=htmlspecialchars($n['uname']??'All')?></td>
      <td><span class="badge <?=$tc[$n['type']]??'bb'?>"><?=ucfirst($n['type'])?></span></td>
      <td style="font-weight:500;font-size:13px"><?=htmlspecialchars($n['title'])?></td>
      <td style="font-size:12.5px;color:var(--mu);max-width:220px"><?=htmlspecialchars(mb_strimwidth($n['message'],0,80,'…'))?></td>
      <td><span class="badge <?=$n['is_read']?'bg':'ba'?>"><?=$n['is_read']?'Read':'Unread'?></span></td>
      <td style="font-size:12px;color:var(--mu);white-space:nowrap"><?=date('d M Y H:i',strtotime($n['created_at']))?></td>
      <td><a href="?del=<?=$n['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a></td>
    </tr>
    <?php endforeach;?>
    <?php if(empty($ns)):?><tr><td colspan="7" style="text-align:center;padding:40px;color:var(--mu)">No notifications</td></tr><?php endif;?>
  </tbody>
</table></div></div>
<template id="notif-tpl">
  <div class="adm-modal"><div class="adm-modal-hd"><div class="adm-modal-title">Send Notification</div><button class="adm-modal-x" onclick="closeModal()">×</button></div>
  <form method="POST" class="adm-modal-bd"><input type="hidden" name="send_notif" value="1">
    <div class="fg"><label class="fl">Send To</label><select class="fc" name="uid"><option value="0">📢 All Guests (Broadcast)</option>
    <?php foreach($allUsers as $u):?><option value="<?=$u['id']?>"><?=htmlspecialchars($u['name'])?></option><?php endforeach;?></select></div>
    <div class="fg"><label class="fl">Type</label><select class="fc" name="type">
      <?php foreach(['system'=>'System','booking'=>'Booking','offer'=>'Offer','loyalty'=>'Loyalty','review'=>'Review'] as $v=>$l):?><option value="<?=$v?>"><?=$l?></option><?php endforeach;?>
    </select></div>
    <div class="fg"><label class="fl">Title *</label><input class="fc" name="title" required placeholder="Notification title"></div>
    <div class="fg"><label class="fl">Message *</label><textarea class="fc" name="message" required placeholder="Notification message…"></textarea></div>
    <div class="adm-modal-ft"><button type="button" class="btn btn-ghost btn-sm" onclick="closeModal()">Cancel</button><button type="submit" class="btn btn-gold btn-sm"><i class="fas fa-paper-plane"></i> Send</button></div>
  </form></div>
</template>
<?php $body=ob_get_clean(); adminPage('Notifications — Admin',$body); ?>
