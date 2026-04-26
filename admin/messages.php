<?php
error_reporting(E_ALL);ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';
if(isset($_GET['rd'])&&is_numeric($_GET['rd'])){$pdo->prepare("UPDATE contact_messages SET is_read=1 WHERE id=?")->execute([(int)$_GET['rd']]);header('Location: '.$_SERVER['PHP_SELF']);exit;}
if(isset($_GET['del'])&&is_numeric($_GET['del'])){$pdo->prepare("DELETE FROM contact_messages WHERE id=?")->execute([(int)$_GET['del']]);header('Location: '.$_SERVER['PHP_SELF']);exit;}
if(isset($_GET['all'])){$pdo->query("UPDATE contact_messages SET is_read=1");header('Location: '.$_SERVER['PHP_SELF']);exit;}
$f=clean($_GET['f']??'all');
$w=$f==='unread'?'WHERE is_read=0':($f==='read'?'WHERE is_read=1':'');
$msgs=$pdo->query("SELECT m.*, u.profile_img FROM contact_messages m LEFT JOIN users u ON m.email = u.email $w ORDER BY m.created_at DESC LIMIT 100")->fetchAll();
$unread=(int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn();
$total=(int)$pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
ob_start();?>
<div class="adm-ph"><div><h1>Messages</h1><p class="sub"><?=$unread?> unread · <?=$total?> total</p></div>
  <?php if($unread>0):?><a href="?all=1" class="btn btn-ghost btn-sm"><i class="fas fa-check-double"></i> Mark All Read</a><?php endif;?></div>
<div style="display:flex;gap:6px;margin-bottom:18px">
  <?php foreach(['all'=>'All ('.$total.')','unread'=>'Unread ('.$unread.')','read'=>'Read ('.($total-$unread).')'] as $v=>$l):?>
  <a href="?f=<?=$v?>" class="btn <?=$f===$v?'btn-gold':'btn-ghost'?> btn-sm"><?=$l?></a>
  <?php endforeach;?>
</div>
<?php if(empty($msgs)):?>
  <div style="text-align:center;padding:80px;color:var(--mu)"><i class="fas fa-envelope-open" style="font-size:40px;display:block;margin-bottom:14px;opacity:.2"></i>No messages</div>
<?php else: ?>
  <?php foreach($msgs as $m):?>
  <div class="ac" style="margin-bottom:12px;<?=$m['is_read']?'':'border-color:var(--gold);'?>">
    <div style="padding:18px 22px">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:12px">
          <?= userAvatar($m['profile_img'] ?? null, $m['name'], 42) ?>
          <div>
            <div style="font-weight:600;font-size:14px"><?=htmlspecialchars($m['name'])?><?php if(!$m['is_read']):?> <span class="badge bgold" style="font-size:9px">New</span><?php endif;?></div>
            <div style="font-size:12px;color:var(--mu)"><?=htmlspecialchars($m['email'])?> · <?=date('d M Y H:i',strtotime($m['created_at']))?></div>
          </div>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0">
          <?php if(!$m['is_read']):?><a href="?rd=<?=$m['id']?>&f=<?=$f?>" class="btn btn-ghost btn-sm"><i class="fas fa-check"></i></a><?php endif;?>
          <a href="?del=<?=$m['id']?>&f=<?=$f?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
          <a href="mailto:<?=htmlspecialchars($m['email'])?>?subject=Re: <?=urlencode($m['subject']??'')?>" class="btn btn-gold btn-sm"><i class="fas fa-reply"></i> Reply</a>
        </div>
      </div>
      <div style="margin-top:12px;padding:12px 14px;background:var(--card2);border-radius:8px;border-left:3px solid <?=$m['is_read']?'var(--br2)':'var(--gold)'?>">
        <div style="font-size:12px;color:var(--gold);font-weight:600;margin-bottom:4px"><?=htmlspecialchars($m['subject']??'No subject')?></div>
        <div style="font-size:13.5px;color:var(--tx2);line-height:1.7"><?=nl2br(htmlspecialchars($m['message']))?></div>
      </div>
    </div>
  </div>
  <?php endforeach;?>
<?php endif; $body=ob_get_clean(); adminPage('Messages — Admin',$body); ?>
