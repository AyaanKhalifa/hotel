<?php
error_reporting(E_ALL);ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';

$msg='';

// Delete User
if(isset($_GET['del'])&&is_numeric($_GET['del'])){
    $uid=(int)$_GET['del'];
    if($uid!==(int)$_SESSION['user_id']){
        $pdo->prepare("DELETE FROM users WHERE id=? AND role!='admin'")->execute([$uid]);
        header('Location: '.$_SERVER['PHP_SELF'].'?msg='.urlencode("Guest deleted."));exit;
    }
}

// Handle Add/Edit User
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['save_user'])){
    $uid=(int)($_POST['uid']??0);
    $n=clean($_POST['name']??'');
    $e=clean($_POST['email']??'');
    $p=clean($_POST['phone']??'');
    $c=clean($_POST['country']??'');
    $rl=in_array($_POST['role']??'',['user','admin'])?$_POST['role']:'user';
    $ia=$rl==='admin'?1:0;
    
    if($n && $e){
        if($uid){
            $pdo->prepare("UPDATE users SET name=?,email=?,phone=?,country=?,role=?,is_admin=? WHERE id=?")
                ->execute([$n,$e,$p,$c,$rl,$ia,$uid]);
            $msg='Guest updated successfully.';
        } else {
            // Check if email exists
            $ch = $pdo->prepare("SELECT id FROM users WHERE email=?");$ch->execute([$e]);
            if($ch->fetch()){
                $msg="Error: Email already exists.";
            } else {
                $hash = password_hash('password', PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO users (name,email,password,phone,country,role,is_admin) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$n,$e,$hash,$p,$c,$rl,$ia]);
                $msg='Guest created successfully (Default password: "password").';
            }
        }
    }
}

if(isset($_GET['msg'])) $msg=clean($_GET['msg']);

$q=clean($_GET['q']??'');$sf=clean($_GET['s']??'all');
$w='1=1';$pa=[];
if($q){$w.=' AND (u.name LIKE ? OR u.email LIKE ?)';$s='%'.$q.'%';$pa=[$s,$s];}
if($sf!=='all'){$w.=' AND u.role=?';$pa[]=$sf;}

$us=$pdo->prepare("SELECT u.*,
  (SELECT COUNT(*) FROM bookings b WHERE b.user_id=u.id AND b.status!='cancelled') bkc,
  (SELECT COALESCE(SUM(final_usd),0) FROM bookings b WHERE b.user_id=u.id AND b.pay_status='paid') spent,
  (SELECT total_points FROM loyalty_points lp WHERE lp.user_id=u.id) pts 
  FROM users u WHERE $w ORDER BY u.created_at DESC LIMIT 200");
$us->execute($pa);$users=$us->fetchAll();

$tU=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$nMQ=$pdo->query("SELECT COUNT(*) FROM users WHERE role='user' AND created_at>=DATE_FORMAT(NOW(),'%Y-%m-01')");$nM=(int)$nMQ->fetchColumn();

ob_start();?>
<?php if($msg):?><div class="alert <?=strpos($msg,'Error')!==false?'alert-danger':'alert-success'?>"><i class="fas <?=strpos($msg,'Error')!==false?'fa-exclamation-circle':'fa-check-circle'?>"></i> <?=htmlspecialchars($msg)?></div><?php endif;?>

<div class="adm-ph">
  <div><h1>Guests DB</h1><p class="sub"><?=count($users)?> shown · <?=$tU?> total</p></div>
  <button onclick="editUser(null)" class="btn btn-gold btn-sm"><i class="fas fa-plus"></i> New Guest</button>
</div>

<div class="mc-grid mc-3" style="margin-bottom:20px">
  <div class="mc" style="--mc:#c09b5b"><div class="mc-ico" style="background:rgba(192,155,91,.12);color:var(--gold)"><i class="fas fa-users"></i></div><div><div class="mc-v"><?=$tU?></div><div class="mc-l">Total Guests</div></div></div>
  <div class="mc" style="--mc:#22c55e"><div class="mc-ico" style="background:rgba(34,197,94,.12);color:var(--gr)"><i class="fas fa-user-plus"></i></div><div><div class="mc-v"><?=$nM?></div><div class="mc-l">New This Month</div></div></div>
  <div class="mc" style="--mc:#8b5cf6"><div class="mc-ico" style="background:rgba(139,92,246,.12);color:#8b5cf6"><i class="fas fa-crown"></i></div><div><div class="mc-v"><?=(int)$pdo->query("SELECT COUNT(*) FROM user_memberships WHERE status='active'")->fetchColumn()?></div><div class="mc-l">Active Members</div></div></div>
</div>

<div class="ac" style="margin-bottom:18px"><div class="ac-body" style="padding:14px 18px">
  <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <div style="flex:1;min-width:180px;display:flex;align-items:center;gap:8px;background:var(--card2);border:1px solid var(--br2);border-radius:var(--r);padding:8px 13px">
      <i class="fas fa-search" style="color:var(--mu);font-size:12px"></i>
      <input name="q" style="background:transparent;border:none;color:var(--tx);font-family:var(--sans);font-size:13.5px;outline:none;flex:1" placeholder="Name, email…" value="<?=htmlspecialchars($q)?>">
    </div>
    <select name="s" class="fc" style="width:140px" onchange="this.form.submit()">
      <option value="all" <?=$sf==='all'?'selected':''?>>All Roles</option>
      <option value="user" <?=$sf==='user'?'selected':''?>>Guests</option>
      <option value="admin" <?=$sf==='admin'?'selected':''?>>Admins</option>
    </select>
    <button class="btn btn-gold btn-sm" type="submit"><i class="fas fa-search"></i></button>
    <?php if($q||$sf!=='all'):?><a href="?" class="btn btn-ghost btn-sm"><i class="fas fa-times"></i></a><?php endif;?>
  </form>
</div></div>

<div class="ac"><div class="tw"><table>
  <thead><tr><th>Guest</th><th>Contact</th><th>Joined</th><th style="text-align:center">Bookings</th><th style="text-align:right">Spent</th><th style="text-align:right">Points</th><th>Role</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach($users as $u):?>
    <tr>
      <td><div style="display:flex;align-items:center;gap:10px"><?= userAvatar($u['profile_img'], $u['name'], 36) ?><div><div style="font-weight:500"><?=htmlspecialchars($u['name'])?></div><div style="font-size:10px;color:var(--mu)">#<?=$u['id']?></div></div></div></td>
      <td><div style="font-size:13px"><?=htmlspecialchars($u['email'])?></div><?php if($u['phone']??''):?><div style="font-size:11px;color:var(--mu)"><?=htmlspecialchars($u['phone'])?></div><?php endif;?></td>
      <td style="font-size:12px;color:var(--mu);white-space:nowrap"><?=date('d M Y',strtotime($u['created_at']))?></td>
      <td style="text-align:center;font-family:var(--serif);font-size:18px;color:var(--gold)"><?=$u['bkc']??0?></td>
      <td style="text-align:right;font-family:var(--serif);color:var(--gold)"><?=formatPrice($u['spent']??0)?></td>
      <td style="text-align:right;font-size:13px;font-weight:600;color:<?=($u['pts']??0)>1000?'var(--gold)':'var(--mu)'?>"><?=number_format($u['pts']??0)?></td>
      <td><span class="badge <?=$u['role']==='admin'?'ba':'bb'?>"><?=ucfirst($u['role'])?></span></td>
      <td><div style="display:flex;gap:4px">
        <button class="btn btn-ghost btn-sm" onclick='editUser(<?=json_encode($u)?>)' title="Edit"><i class="fas fa-edit"></i></button>
        <?php if($u['id']!=$_SESSION['user_id']&&$u['role']!=='admin'):?>
          <a href="?del=<?=$u['id']?>&q=<?=urlencode($q)?>&s=<?=$sf?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete guest permanently?')" title="Delete"><i class="fas fa-trash"></i></a>
        <?php endif;?>
      </div></td>
    </tr>
    <?php endforeach;?>
    <?php if(empty($users)):?><tr><td colspan="8" style="text-align:center;padding:44px;color:var(--mu)">No guests found</td></tr><?php endif;?>
  </tbody>
</table></div></div>

<script>
function editUser(u){
  const isNew = !u;
  u = u || {id:0, name:'', email:'', phone:'', country:'', role:'user'};
  openModal(`
  <div class="adm-modal" style="width:500px;max-width:95vw">
    <div class="adm-modal-hd">
      <div class="adm-modal-title">${isNew ? 'New Guest / Admin' : 'Edit Guest'}</div>
      <button class="adm-modal-x" onclick="closeModal()">×</button>
    </div>
    <form method="POST" class="adm-modal-bd">
      <input type="hidden" name="save_user" value="1">
      <input type="hidden" name="uid" value="${u.id}">
      
      <div class="fg">
        <label class="fl">Full Name</label>
        <input class="fc" name="name" value="${u.name || ''}" required>
      </div>
      
      <div class="fg">
        <label class="fl">Email Address</label>
        <input class="fc" type="email" name="email" value="${u.email || ''}" required>
      </div>
      
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px">
        <div class="fg">
          <label class="fl">Phone</label>
          <input class="fc" name="phone" value="${u.phone || ''}">
        </div>
        <div class="fg">
          <label class="fl">Country</label>
          <input class="fc" name="country" value="${u.country || ''}">
        </div>
      </div>
      
      <div class="fg">
        <label class="fl">Role</label>
        <select class="fc" name="role">
          <option value="user" ${u.role==="user"?"selected":""}>Guest</option>
          <option value="admin" ${u.role==="admin"?"selected":""}>Admin</option>
        </select>
        ${isNew ? '<div style="font-size:11px;color:var(--gold);margin-top:6px">New users will be created with the password: <b>password</b></div>' : ''}
      </div>
      
      <div class="adm-modal-ft" style="margin-top:20px">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> Save Guest</button>
      </div>
    </form>
  </div>`);
}
</script>
<?php 
$body=ob_get_clean();
adminPage('Guests DB — Admin',$body,'');
?>
