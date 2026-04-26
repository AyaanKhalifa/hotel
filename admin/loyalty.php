<?php
error_reporting(E_ALL);ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['award'])){
    $uid=(int)$_POST['uid'];$pts=(int)$_POST['pts'];$rsn=clean($_POST['rsn']??'Admin');
    if($uid&&$pts!=0){
        $pdo->prepare("INSERT IGNORE INTO loyalty_points (user_id,total_points,lifetime_points) VALUES (?,0,0)")->execute([$uid]);
        if($pts>0)$pdo->prepare("UPDATE loyalty_points SET total_points=total_points+?,lifetime_points=lifetime_points+? WHERE user_id=?")->execute([$pts,$pts,$uid]);
        else$pdo->prepare("UPDATE loyalty_points SET total_points=GREATEST(0,total_points+?) WHERE user_id=?")->execute([$pts,$uid]);
        $pdo->prepare("INSERT INTO loyalty_transactions (user_id,type,points,balance_after,description) SELECT ?,?,?,(SELECT total_points FROM loyalty_points WHERE user_id=?),?")->execute([$uid,$pts>0?'bonus':'deduct',$pts,$uid,$rsn]);
        $msg='Points '.(abs($pts)).' '.(($pts>0)?'awarded':'deducted').'.';
    }
}
$q=clean($_GET['q']??'');
$us=$pdo->prepare("SELECT u.id,u.name,u.email,COALESCE(lp.total_points,0) pts,COALESCE(lp.lifetime_points,0) life,COALESCE(lp.tier,'bronze') tier FROM users u LEFT JOIN loyalty_points lp ON lp.user_id=u.id WHERE u.role='user'".($q?" AND (u.name LIKE ? OR u.email LIKE ?)":"")." ORDER BY life DESC LIMIT 100");
$q?$us->execute(['%'.$q.'%','%'.$q.'%']):$us->execute();$mems=$us->fetchAll();
$totPts=$pdo->query("SELECT COALESCE(SUM(total_points),0) FROM loyalty_points")->fetchColumn();
$platCt=$pdo->query("SELECT COUNT(*) FROM loyalty_points WHERE lifetime_points>=10000")->fetchColumn();
$trans=$pdo->query("SELECT lt.*,u.name FROM loyalty_transactions lt LEFT JOIN users u ON lt.user_id=u.id ORDER BY lt.created_at DESC LIMIT 30")->fetchAll();
$tc=['bronze'=>'#cd7f32','silver'=>'#aaa9ad','gold'=>'#d4af37','platinum'=>'#e5e4e2'];
ob_start();?>
<?php if($msg):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?=htmlspecialchars($msg)?></div><?php endif;?>
<div class="adm-ph"><div><h1>Loyalty Programme</h1><p class="sub">Points, tiers &amp; transactions</p></div>
  <button class="btn btn-gold btn-sm" onclick="openModal(document.getElementById('aw-tpl').innerHTML)"><i class="fas fa-plus"></i> Award Points</button></div>
<div class="mc-grid mc-4" style="margin-bottom:22px">
  <div class="mc" style="--mc:#d4af37"><div class="mc-ico" style="background:rgba(212,175,55,.12);color:var(--gold)"><i class="fas fa-coins"></i></div><div><div class="mc-v"><?=number_format($totPts)?></div><div class="mc-l">Active Points</div></div></div>
  <div class="mc" style="--mc:#e5e4e2"><div class="mc-ico" style="background:rgba(229,228,226,.1);color:#e5e4e2"><i class="fas fa-gem"></i></div><div><div class="mc-v"><?=$platCt?></div><div class="mc-l">Platinum</div></div></div>
  <div class="mc" style="--mc:#22c55e"><div class="mc-ico" style="background:rgba(34,197,94,.12);color:var(--gr)"><i class="fas fa-users"></i></div><div><div class="mc-v"><?=count($mems)?></div><div class="mc-l">Members</div></div></div>
  <div class="mc" style="--mc:#3b82f6"><div class="mc-ico" style="background:rgba(59,130,246,.12);color:var(--bl)"><i class="fas fa-history"></i></div><div><div class="mc-v"><?=count($trans)?></div><div class="mc-l">Recent Tx</div></div></div>
</div>
<div class="g2" style="gap:20px">
  <div class="ac"><div class="ac-hd"><div class="ac-title"><i class="fas fa-trophy" style="color:var(--gold)"></i> Leaderboard</div>
    <form method="GET" style="display:flex;gap:6px"><input name="q" class="fc" style="width:150px;padding:6px 10px;font-size:13px" placeholder="Search…" value="<?=htmlspecialchars($q)?>"><button class="btn btn-gold btn-sm" type="submit"><i class="fas fa-search"></i></button></form>
  </div>
  <div class="tw"><table>
    <thead><tr><th>#</th><th>Member</th><th>Tier</th><th style="text-align:right">Active</th><th style="text-align:right">Lifetime</th><th>Manage</th></tr></thead>
    <tbody>
      <?php foreach($mems as $i=>$m):$tier=$m['tier']??'bronze';?>
      <tr><td style="font-family:var(--serif);font-size:16px;color:var(--mu)"><?=$i+1?></td>
        <td><div style="font-weight:500"><?=htmlspecialchars($m['name'])?></div><div style="font-size:11px;color:var(--mu)">#<?=$m['id']?></div></td>
        <td><span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:10px;font-size:11px;font-weight:700;background:<?=$tc[$tier]??'#c09b5b'?>20;color:<?=$tc[$tier]??'#c09b5b'?>"><?=ucfirst($tier)?></span></td>
        <td style="text-align:right;font-family:var(--serif);font-size:16px;color:var(--gold)"><?=number_format($m['pts'])?></td>
        <td style="text-align:right;color:var(--mu);font-size:13px"><?=number_format($m['life'])?></td>
        <td><button class="btn btn-ghost btn-sm" onclick="quickAward(<?=$m['id']?>,<?=json_encode($m['name'])?>)"><i class="fas fa-coins"></i></button></td>
      </tr>
      <?php endforeach;?>
      <?php if(empty($mems)):?><tr><td colspan="6" style="text-align:center;padding:28px;color:var(--mu)">No members</td></tr><?php endif;?>
    </tbody>
  </table></div></div>
  <div class="ac"><div class="ac-hd"><div class="ac-title"><i class="fas fa-history" style="color:var(--gold)"></i> Recent Transactions</div></div>
    <div style="max-height:480px;overflow-y:auto;padding:6px 8px">
      <?php foreach($trans as $t):$p=(int)$t['points'];?>
      <div style="display:flex;align-items:center;gap:10px;padding:9px 6px;border-bottom:1px solid var(--br2);font-size:13px">
        <div style="width:30px;height:30px;border-radius:50%;background:<?=$p>0?'rgba(34,197,94,.12)':'rgba(224,112,112,.1)'?>;color:<?=$p>0?'var(--gr)':'var(--rd)'?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:12px;font-weight:700"><?=$p>0?'+':'';echo$p?></div>
        <div style="flex:1"><div style="font-weight:500"><?=htmlspecialchars($t['name']??'Guest')?></div><div style="font-size:11px;color:var(--mu)"><?=htmlspecialchars($t['description']??$t['type'])?> · <?=date('d M H:i',strtotime($t['created_at']))?></div></div>
      </div>
      <?php endforeach;?>
    </div>
  </div>
</div>
<template id="aw-tpl">
  <div class="adm-modal"><div class="adm-modal-hd"><div class="adm-modal-title">Award / Deduct Points</div><button class="adm-modal-x" onclick="closeModal()">×</button></div>
  <form method="POST" class="adm-modal-bd"><input type="hidden" name="award" value="1">
    <div class="fg"><label class="fl">Guest ID (from table)</label><input class="fc" name="uid" type="number" min="1" required placeholder="e.g. 3"></div>
    <div class="fg"><label class="fl">Points (negative = deduct)</label><input class="fc" name="pts" type="number" required placeholder="e.g. 500 or -200"></div>
    <div class="fg"><label class="fl">Reason</label><input class="fc" name="rsn" placeholder="e.g. Goodwill gesture" required></div>
    <div class="adm-modal-ft"><button type="button" class="btn btn-ghost btn-sm" onclick="closeModal()">Cancel</button><button type="submit" class="btn btn-gold btn-sm"><i class="fas fa-save"></i> Confirm</button></div>
  </form></div>
</template>
<?php $body=ob_get_clean();
$js='function quickAward(uid,name){
  openModal(`<div class="adm-modal"><div class="adm-modal-hd"><div class="adm-modal-title">Manage Points — ${name}</div><button class="adm-modal-x" onclick="closeModal()">×</button></div>
<form method="POST" class="adm-modal-bd"><input type="hidden" name="award" value="1"><input type="hidden" name="uid" value="${uid}">
<div class="fg"><label class="fl">Points (negative = deduct)</label><input class="fc" name="pts" type="number" required placeholder="e.g. 500 or -100" autofocus></div>
<div class="fg"><label class="fl">Reason</label><input class="fc" name="rsn" value="Admin adjustment" required></div>
<div class="adm-modal-ft"><button type="button" class="btn btn-ghost btn-sm" onclick="closeModal()">Cancel</button><button type="submit" class="btn btn-gold btn-sm"><i class="fas fa-save"></i> Save</button></div></form></div>`);
}';
adminPage('Loyalty — Admin',$body,$js); ?>
