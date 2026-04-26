<?php
error_reporting(E_ALL);ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';
if(isset($_GET['apr'])&&is_numeric($_GET['apr'])){
    $pdo->prepare("UPDATE room_ratings SET is_approved=1 WHERE id=?")->execute([(int)$_GET['apr']]);
    logActivity('approve_review', "Approved Review #".(int)$_GET['apr']);
    header('Location: '.$_SERVER['PHP_SELF']);exit;
}
if(isset($_GET['hid'])&&is_numeric($_GET['hid'])){
    $pdo->prepare("UPDATE room_ratings SET is_approved=0 WHERE id=?")->execute([(int)$_GET['hid']]);
    logActivity('hide_review', "Hid Review #".(int)$_GET['hid']);
    header('Location: '.$_SERVER['PHP_SELF']);exit;
}
if(isset($_GET['del'])&&is_numeric($_GET['del'])){
    $pdo->prepare("DELETE FROM room_ratings WHERE id=?")->execute([(int)$_GET['del']]);
    logActivity('delete_review', "Permanently Deleted Review #".(int)$_GET['del']);
    header('Location: '.$_SERVER['PHP_SELF']);exit;
}
$f=clean($_GET['f']??'all');$rtF=(int)($_GET['rt']??0);
$w='1=1';$pa=[];
if($f==='pending')$w.=' AND rr.is_approved=0';elseif($f==='approved')$w.=' AND rr.is_approved=1';
if($rtF){$w.=' AND rr.room_type_id=?';$pa[]=$rtF;}
$rvs=$pdo->prepare("SELECT rr.*,rt.name rname,u.email, u.profile_img, (SELECT GROUP_CONCAT(CONCAT(id,'|~|',type,'|~|',url) SEPARATOR '||') FROM review_media WHERE review_id=rr.id) as media_list FROM room_ratings rr LEFT JOIN room_types rt ON rr.room_type_id=rt.id LEFT JOIN users u ON rr.user_id=u.id WHERE $w ORDER BY rr.created_at DESC LIMIT 100");
$rvs->execute($pa);$reviews=$rvs->fetchAll();
$rts=$pdo->query("SELECT id,name FROM room_types ORDER BY sort_order")->fetchAll();
$tot=(int)$pdo->query("SELECT COUNT(*) FROM room_ratings")->fetchColumn();
$pend=(int)$pdo->query("SELECT COUNT(*) FROM room_ratings WHERE is_approved=0")->fetchColumn();
$avg=$pdo->query("SELECT AVG(rating) FROM room_ratings WHERE is_approved=1")->fetchColumn();
ob_start();?>
<style>
  .mgrid{display:flex;gap:4px;flex-wrap:wrap;margin-top:6px}
  .mthumb{width:40px;height:40px;border-radius:4px;object-fit:cover;border:1px solid var(--bdr2);cursor:pointer;transition:transform .2s}
  .mthumb:hover{transform:scale(1.1);border-color:var(--gold)}
  .mvid{width:40px;height:40px;border-radius:4px;background:#000;display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;border:1px solid var(--bdr2);cursor:pointer}
</style>
<div class="adm-ph"><div><h1>Reviews</h1><p class="sub"><?=$tot?> total · <?=$pend?> pending</p></div></div>
<div class="mc-grid mc-3" style="margin-bottom:20px">
  <div class="mc" style="--mc:#c09b5b"><div class="mc-ico" style="background:rgba(192,155,91,.12);color:var(--gold)"><i class="fas fa-star"></i></div><div><div class="mc-v"><?=number_format((float)$avg,1)?></div><div class="mc-l">Avg Rating</div></div></div>
  <div class="mc" style="--mc:#22c55e"><div class="mc-ico" style="background:rgba(34,197,94,.12);color:var(--gr)"><i class="fas fa-check-circle"></i></div><div><div class="mc-v"><?=$tot-$pend?></div><div class="mc-l">Published</div></div></div>
  <div class="mc" style="--mc:#f59e0b"><div class="mc-ico" style="background:rgba(245,158,11,.12);color:var(--am)"><i class="fas fa-clock"></i></div><div><div class="mc-v"><?=$pend?></div><div class="mc-l">Pending</div></div></div>
</div>
<div style="display:flex;gap:7px;margin-bottom:16px;flex-wrap:wrap">
  <?php foreach(['all'=>'All','approved'=>'Published','pending'=>'Pending'] as $v=>$l):?>
  <a href="?f=<?=$v?>&rt=<?=$rtF?>" class="btn <?=$f===$v?'btn-gold':'btn-ghost'?> btn-sm"><?=$l?></a>
  <?php endforeach;?>
  <select class="fc" style="width:180px;padding:6px 10px;font-size:13px" onchange="window.location='?f=<?=$f?>&rt='+this.value">
    <option value="0">All Room Types</option>
    <?php foreach($rts as $rt):?><option value="<?=$rt['id']?>" <?=$rtF==$rt['id']?'selected':''?>><?=htmlspecialchars($rt['name'])?></option><?php endforeach;?>
  </select>
</div>
<div class="ac"><div class="tw"><table>
  <thead><tr><th>Guest</th><th>Room</th><th>Rating</th><th>Review</th><th>Media</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach($reviews as $rv):?>
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          <?= userAvatar($rv['profile_img'] ?? null, $rv['guest_name'] ?? 'Guest', 32) ?>
          <div>
            <div style="font-weight:500"><?=htmlspecialchars($rv['guest_name']??'Guest')?></div>
            <?php if($rv['email']):?><div style="font-size:11px;color:var(--mu)"><?=htmlspecialchars($rv['email'])?></div><?php endif;?>
            <?php if($rv['is_verified']):?><span class="badge bg" style="font-size:9px;margin-top:3px">Verified</span><?php endif;?>
          </div>
        </div>
      </td>
      <td style="font-size:13px"><?=htmlspecialchars($rv['rname']??'—')?></td>
      <td><span style="color:var(--gold);letter-spacing:2px"><?=str_repeat('★',$rv['rating'])?></span></td>
      <td style="max-width:240px"><div style="font-size:13px;font-weight:500;margin-bottom:2px"><?=htmlspecialchars($rv['title']??'')?></div><div style="font-size:12.5px;color:var(--mu);overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical"><?=htmlspecialchars($rv['review'])?></div></td>
      <td>
        <div class="mgrid">
          <?php 
          if($rv['media_list']){
            $ms = explode('||', $rv['media_list']);
            foreach($ms as $m){
              [$mid, $type, $url] = explode('|~|', $m);
              echo '<div class="mitem" id="mitem-'.$mid.'">';
              if($type==='image') echo '<img src="'.$url.'" class="mthumb" onclick="window.open(\''.$url.'\')">';
              else echo '<div class="mvid" onclick="window.open(\''.$url.'\')"><i class="fas fa-play"></i></div>';
              echo '<button class="mdel" onclick="deleteMedia('.$mid.')" title="Delete Media"><i class="fas fa-times"></i></button>';
              echo '</div>';
            }
          } else { echo '<span style="font-size:11px;color:var(--mu)">None</span>'; }
          ?>
        </div>
      </td>
      <td style="font-size:12px;color:var(--mu);white-space:nowrap"><?=date('d M Y',strtotime($rv['created_at']))?></td>
      <td><span class="badge <?=$rv['is_approved']?'bg':'ba'?>"><?=$rv['is_approved']?'Published':'Pending'?></span></td>
      <td><div style="display:flex;gap:4px">
        <?php if(!$rv['is_approved']):?><a href="?apr=<?=$rv['id']?>&f=<?=$f?>&rt=<?=$rtF?>" class="btn btn-ghost btn-sm" title="Approve"><i class="fas fa-check" style="color:var(--gr)"></i></a>
        <?php else:?><a href="?hid=<?=$rv['id']?>&f=<?=$f?>&rt=<?=$rtF?>" class="btn btn-ghost btn-sm" title="Hide"><i class="fas fa-eye-slash" style="color:var(--am)"></i></a><?php endif;?>
        <a href="?del=<?=$rv['id']?>&f=<?=$f?>&rt=<?=$rtF?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete review?')"><i class="fas fa-trash"></i></a>
      </div></td>
    </tr>
    <?php endforeach;?>
    <?php if(empty($reviews)):?><tr><td colspan="8" style="text-align:center;padding:40px;color:var(--mu)">No reviews</td></tr><?php endif;?>
  </tbody>
</table></div></div>
<script>
async function deleteMedia(mediaId){
  if(!confirm('Delete this review media?')) return;
  const fd = new FormData();
  fd.append('action','delete_review_media');
  fd.append('media_id', mediaId);
  try{
    const res = await fetch('<?= BASE ?>/api/admin_media.php',{method:'POST',body:fd});
    const data = await res.json();
    if(data.ok){
      const el = document.getElementById('mitem-'+mediaId);
      if(el) el.remove();
      if(window.toast) toast('Media deleted','success');
    }else{
      if(window.toast) toast(data.error||'Delete failed','error');
    }
  }catch(e){
    if(window.toast) toast('Delete failed','error');
  }
}
</script>
<?php $body=ob_get_clean(); adminPage('Reviews — Admin',$body); ?>
