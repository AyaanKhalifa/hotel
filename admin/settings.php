<?php
error_reporting(E_ALL);ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';
$msg='';
// Password change
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['chg_pass'])){
    $cur=clean($_POST['cur_pass']??'');$nw=clean($_POST['new_pass']??'');$cf=clean($_POST['cfm_pass']??'');
    $uQ=$pdo->prepare("SELECT password FROM users WHERE id=?");$uQ->execute([$_SESSION['user_id']]);$ph=$uQ->fetchColumn();
    if(!password_verify($cur,$ph)){$msg='Current password incorrect.';$msgT='error';}
    elseif(strlen($nw)<8){$msg='Password must be at least 8 characters.';$msgT='error';}
    elseif($nw!==$cf){$msg='Passwords do not match.';$msgT='error';}
    else{$pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($nw,PASSWORD_DEFAULT),$_SESSION['user_id']]);$msg='Password updated.';$msgT='success';}
}
$me=$pdo->prepare("SELECT * FROM users WHERE id=?");$me->execute([$_SESSION['user_id']]);$me=$me->fetch();
ob_start();?>
<?php if($msg):?><div class="alert alert-<?=$msgT??'info'?>"><i class="fas fa-<?=($msgT??'info')==='success'?'check-circle':'exclamation-circle'?>"></i> <?=htmlspecialchars($msg)?></div><?php endif;?>
<div class="adm-ph"><div><h1>Settings</h1><p class="sub">Admin account and system configuration</p></div></div>
<div class="g2" style="gap:20px">
  <div class="ac"><div class="ac-hd"><div class="ac-title"><i class="fas fa-user-shield" style="color:var(--gold)"></i> Account Details</div></div>
    <div class="ac-body">
      <div style="display:flex;align-items:center;gap:16px;margin-bottom:22px">
        <div style="width:60px;height:60px;border-radius:50%;background:var(--gold);display:flex;align-items:center;justify-content:center;font-family:var(--cinzel);font-size:24px;font-weight:500;color:#000;flex-shrink:0"><?=strtoupper(substr($me['name'],0,1))?></div>
        <div><div style="font-family:var(--serif);font-size:20px"><?=htmlspecialchars($me['name'])?></div><div style="font-size:13px;color:var(--mu)"><?=htmlspecialchars($me['email'])?></div><span class="badge bgold" style="margin-top:5px">Administrator</span></div>
      </div>
      <div class="sep"></div>
      <form method="POST">
        <input type="hidden" name="chg_pass" value="1">
        <div class="fg"><label class="fl">Current Password</label><input class="fc" type="password" name="cur_pass" required></div>
        <div class="fg"><label class="fl">New Password</label><input class="fc" type="password" name="new_pass" required placeholder="Min. 8 characters"></div>
        <div class="fg"><label class="fl">Confirm New Password</label><input class="fc" type="password" name="cfm_pass" required></div>
        <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> Update Password</button>
      </form>
    </div></div>
  <div>
    <div class="ac" style="margin-bottom:20px"><div class="ac-hd"><div class="ac-title"><i class="fas fa-info-circle" style="color:var(--gold)"></i> System Info</div></div>
    <div class="ac-body">
      <?php foreach([['PHP Version',PHP_VERSION],['Database','MySQL / MariaDB'],['Hotel','Royale Vista'],['Platform','XAMPP / WAMP'],['Admin Email',$me['email']],['Login Credentials','admin@royalevista.com / password']] as [$l,$v]):?>
      <div class="stat-row"><span style="color:var(--mu)"><?=$l?></span><span style="font-weight:500"><?=htmlspecialchars($v)?></span></div>
      <?php endforeach;?>
    </div></div>
    <div class="ac"><div class="ac-hd"><div class="ac-title"><i class="fas fa-palette" style="color:var(--gold)"></i> Appearance</div></div>
    <div class="ac-body">
      <div style="display:flex;align-items:center;justify-content:space-between">
        <span>Admin Panel Theme</span>
        <button class="btn btn-ghost btn-sm" onclick="toggleTheme()"><i class="fas fa-circle-half-stroke"></i> Toggle Theme</button>
      </div>
    </div></div>
  </div>
</div>
<?php $body=ob_get_clean(); adminPage('Settings — Admin',$body); ?>
