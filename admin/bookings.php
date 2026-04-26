<?php
error_reporting(E_ALL);ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';

$msg='';

// Delete Booking
if(isset($_GET['del_bk'])){
    $ref = clean($_GET['del_bk']);
    $pdo->prepare("DELETE FROM bookings WHERE booking_ref=?")->execute([$ref]);
    logActivity('delete_booking', "Deleted booking mission-critical: $ref");
    header("Location: bookings.php?msg=" . urlencode("Booking $ref deleted entirely."));
    exit;
}

// Handle Edit Booking
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['save_booking'])){
    $ref = clean($_POST['booking_ref']??'');
    $n = clean($_POST['guest_name']??'');
    $e = clean($_POST['guest_email']??'');
    $ci = clean($_POST['check_in']??'');
    $co = clean($_POST['check_out']??'');
    $ps = in_array($_POST['pay_status']??'',['pending','paid','failed','refunded'])?$_POST['pay_status']:'pending';

    if($ref && $n && $e && $ci && $co){
        $pdo->prepare("UPDATE bookings SET guest_name=?, guest_email=?, check_in=?, check_out=?, pay_status=? WHERE booking_ref=?")
            ->execute([$n,$e,$ci,$co,$ps,$ref]);
        logActivity('edit_booking', "Manually updated details for booking $ref");
        $msg = "Booking $ref updated successfully.";
    }
}
if(isset($_GET['msg'])) $msg=clean($_GET['msg']);

$q=clean($_GET['q']??'');$sf=clean($_GET['s']??'all');$pf=clean($_GET['p']??'all');$df=clean($_GET['d']??'');
$w='1=1';$pa=[];
if($q){$w.=' AND (b.booking_ref LIKE ? OR b.guest_name LIKE ? OR b.guest_email LIKE ?)';$s='%'.$q.'%';$pa=array_merge($pa,[$s,$s,$s]);}
if($sf!=='all'){$w.=' AND b.status=?';$pa[]=$sf;}
if($pf!=='all'){$w.=' AND b.pay_status=?';$pa[]=$pf;}
if($df){$w.=' AND DATE(b.check_in)=?';$pa[]=$df;}

$rows=$pdo->prepare("SELECT b.*, u.profile_img, GROUP_CONCAT(CONCAT(br.quantity,'× ',br.room_type_name) SEPARATOR ', ') rooms_str FROM bookings b LEFT JOIN booked_rooms br ON br.booking_ref=b.booking_ref LEFT JOIN users u ON b.user_id=u.id WHERE $w GROUP BY b.id ORDER BY b.created_at DESC LIMIT 300");
$rows->execute($pa);$bks=$rows->fetchAll();

$tBk=(int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$tRev=(float)$pdo->query("SELECT COALESCE(SUM(final_usd),0) FROM bookings WHERE pay_status='paid'")->fetchColumn();
$tPend=(int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE pay_status='pending'")->fetchColumn();
$tTQ=$pdo->query("SELECT COUNT(*) FROM bookings WHERE DATE(created_at)=CURDATE()");$tToday=(int)$tTQ->fetchColumn();

$sc=['confirmed'=>'bgold','cancelled'=>'br','checked_in'=>'bg','checked_out'=>'bb'];
$pc=['paid'=>'bg','pending'=>'ba','failed'=>'br','refunded'=>'bb'];

ob_start();?>
<?php if($msg):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?=htmlspecialchars($msg)?></div><?php endif;?>

<div class="adm-ph">
  <div><h1>Bookings Control</h1><p class="sub"><?=count($bks)?> shown · <?=$tBk?> total bookings</p></div>
  <a href="<?=BASE?>/rooms.php" target="_blank" class="btn btn-gold btn-sm"><i class="fas fa-plus"></i> New Booking</a>
</div>

<div class="mc-grid mc-4" style="margin-bottom:22px">
  <div class="mc" style="--mc:#d4af37"><div class="mc-ico" style="background:rgba(192,155,91,.12);color:var(--gold)"><i class="fas fa-calendar-day"></i></div><div><div class="mc-v"><?=$tToday?></div><div class="mc-l">Today</div></div></div>
  <div class="mc" style="--mc:#22c55e"><div class="mc-ico" style="background:rgba(34,197,94,.12);color:var(--gr)"><i class="fas fa-calendar-check"></i></div><div><div class="mc-v"><?=$tBk?></div><div class="mc-l">Total</div></div></div>
  <div class="mc" style="--mc:#f59e0b"><div class="mc-ico" style="background:rgba(245,158,11,.12);color:var(--am)"><i class="fas fa-clock"></i></div><div><div class="mc-v"><?=$tPend?></div><div class="mc-l">Pending Pay</div></div></div>
  <div class="mc" style="--mc:#3b82f6"><div class="mc-ico" style="background:rgba(59,130,246,.12);color:var(--bl)"><i class="fas fa-coins"></i></div><div><div class="mc-v"><?=formatPrice($tRev)?></div><div class="mc-l">Revenue</div></div></div>
</div>

<div class="ac" style="margin-bottom:18px"><div class="ac-body" style="padding:14px 18px">
  <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <div style="flex:1;min-width:180px;display:flex;align-items:center;gap:8px;background:var(--card2);border:1px solid var(--br2);border-radius:var(--r);padding:8px 13px">
      <i class="fas fa-search" style="color:var(--mu);font-size:12px"></i>
      <input name="q" style="background:transparent;border:none;color:var(--tx);font-family:var(--sans);font-size:13.5px;outline:none;flex:1" placeholder="Ref, name, email…" value="<?=htmlspecialchars($q)?>">
    </div>
    <select name="s" class="fc" style="width:148px" onchange="this.form.submit()">
      <option value="all" <?=$sf==='all'?'selected':''?>>All Status</option>
      <?php foreach(['confirmed'=>'Confirmed','checked_in'=>'Checked In','checked_out'=>'Checked Out','cancelled'=>'Cancelled'] as $v=>$l):?>
      <option value="<?=$v?>" <?=$sf===$v?'selected':''?>><?=$l?></option><?php endforeach;?>
    </select>
    <select name="p" class="fc" style="width:130px" onchange="this.form.submit()">
      <option value="all" <?=$pf==='all'?'selected':''?>>All Pay</option>
      <?php foreach(['paid'=>'Paid','pending'=>'Pending','failed'=>'Failed','refunded'=>'Refunded'] as $v=>$l):?>
      <option value="<?=$v?>" <?=$pf===$v?'selected':''?>><?=$l?></option><?php endforeach;?>
    </select>
    <input type="date" name="d" class="fc" style="width:155px" value="<?=htmlspecialchars($df)?>" onchange="this.form.submit()" title="Filter by check-in">
    <button class="btn btn-gold btn-sm" type="submit"><i class="fas fa-search"></i></button>
    <?php if($q||$sf!=='all'||$pf!=='all'||$df):?><a href="?" class="btn btn-ghost btn-sm"><i class="fas fa-times"></i></a><?php endif;?>
  </form>
</div></div>

<div class="ac"><div class="tw"><table>
  <thead><tr><th>Reference</th><th>Guest</th><th>Rooms</th><th>Dates</th><th>Total</th><th>Status</th><th>Payment</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach($bks as $bk):?>
    <tr id="row-<?=htmlspecialchars($bk['booking_ref'])?>">
      <td><code><?=htmlspecialchars($bk['booking_ref'])?></code><div style="font-size:10px;color:var(--mu);margin-top:2px"><?=date('d M Y H:i',strtotime($bk['created_at']))?></div></td>
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          <?= userAvatar($bk['profile_img'] ?? null, $bk['guest_name'], 32) ?>
          <div>
            <div style="font-weight:500"><?=htmlspecialchars($bk['guest_name'])?></div>
            <div style="font-size:11px;color:var(--mu)"><?=htmlspecialchars($bk['guest_email'])?></div>
          </div>
        </div>
      </td>
      <td style="font-size:12.5px;max-width:170px"><?=htmlspecialchars($bk['rooms_str']??'—')?></td>
      <td style="white-space:nowrap;font-size:12.5px"><div><?=date('d M',strtotime($bk['check_in']))?> → <?=date('d M Y',strtotime($bk['check_out']))?></div><div style="color:var(--mu)"><?=$bk['nights']?> night<?=$bk['nights']!=1?'s':''?></div></td>
      <td style="font-family:var(--serif);font-size:16px;color:var(--gold)"><?=formatPrice($bk['final_usd'])?></td>
      <td><span class="badge <?=$sc[$bk['status']]??'bgold'?>" id="sb-<?=htmlspecialchars($bk['booking_ref'])?>"><?=ucfirst(str_replace('_',' ',$bk['status']))?></span></td>
      <td><span class="badge <?=$pc[$bk['pay_status']]??'ba'?>"><?=ucfirst($bk['pay_status'])?></span></td>
      <td>
        <div style="display:flex;gap:5px;flex-wrap:wrap;align-items:center">
          <select onchange="chgStatus('<?=htmlspecialchars($bk['booking_ref'],ENT_QUOTES)?>',this.value,this)" class="fc" style="font-size:12px;padding:5px 8px;width:128px;background:var(--card)">
            <?php foreach(['confirmed'=>'Confirmed','checked_in'=>'Checked In','checked_out'=>'Checked Out','cancelled'=>'Cancelled'] as $v=>$l):?>
            <option value="<?=$v?>" <?=$bk['status']===$v?'selected':''?>><?=$l?></option><?php endforeach;?>
          </select>
          <button class="btn btn-ghost btn-sm" onclick='editBk(<?=json_encode($bk)?>)' title="Edit Details"><i class="fas fa-edit"></i></button>
          <a href="<?=BASE?>/invoice.php?ref=<?=urlencode($bk['booking_ref'])?>" target="_blank" class="btn btn-ghost btn-sm" title="Invoice"><i class="fas fa-file-invoice"></i></a>
          <a href="?del_bk=<?=urlencode($bk['booking_ref'])?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this booking entirely? This cannot be undone.')" title="Delete"><i class="fas fa-trash"></i></a>
        </div>
      </td>
    </tr>
    <?php endforeach;?>
    <?php if(empty($bks)):?><tr><td colspan="8" style="text-align:center;padding:52px;color:var(--mu)"><i class="fas fa-calendar-times" style="display:block;font-size:36px;margin-bottom:14px;opacity:.2"></i>No bookings found</td></tr><?php endif;?>
  </tbody>
</table></div></div>

<script>
async function chgStatus(ref,st,sel){
  sel.disabled=true;
  const fd=new FormData();fd.append("action","admin_status");fd.append("booking_ref",ref);fd.append("status",st);
  try{
    const r=await fetch(BASE+"/api/book.php",{method:"POST",body:fd});
    const d=await r.json();
    if(d.ok){
      const cl={confirmed:"bgold",cancelled:"br",checked_in:"bg",checked_out:"bb"};
      const lb={confirmed:"Confirmed",cancelled:"Cancelled",checked_in:"Checked In",checked_out:"Checked Out"};
      const b=document.getElementById("sb-"+ref);
      if(b){b.className="badge "+(cl[d.status]||"bgold");b.textContent=lb[d.status]||d.status;}
      const row=document.getElementById("row-"+ref);
      if(row&&window.anime)anime({targets:row,backgroundColor:["rgba(34,197,94,.12)","transparent"],duration:1400,easing:"easeOutCubic"});
      toast("Status updated","success");
    }else toast(d.error||"Update failed","error");
  }catch(e){toast("Connection error","error");}
  sel.disabled=false;
}

function editBk(bk) {
  openModal(`
  <div class="adm-modal" style="width:550px;max-width:95vw">
    <div class="adm-modal-hd">
      <div class="adm-modal-title">Edit Booking: ${bk.booking_ref}</div>
      <button class="adm-modal-x" onclick="closeModal()">×</button>
    </div>
    <form method="POST" class="adm-modal-bd">
      <input type="hidden" name="save_booking" value="1">
      <input type="hidden" name="booking_ref" value="${bk.booking_ref}">
      
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px">
        <div class="fg">
          <label class="fl">Guest Name</label>
          <input class="fc" name="guest_name" value="${bk.guest_name}" required>
        </div>
        <div class="fg">
          <label class="fl">Guest Email</label>
          <input class="fc" type="email" name="guest_email" value="${bk.guest_email}" required>
        </div>
      </div>
      
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-top:15px">
        <div class="fg">
          <label class="fl">Check In Date</label>
          <input class="fc" type="date" name="check_in" value="${bk.check_in}" required>
        </div>
        <div class="fg">
          <label class="fl">Check Out Date</label>
          <input class="fc" type="date" name="check_out" value="${bk.check_out}" required>
        </div>
      </div>
      
      <div class="fg" style="margin-top:15px">
        <label class="fl">Payment Status</label>
        <select class="fc" name="pay_status">
          <option value="pending" ${bk.pay_status==="pending"?"selected":""}>Pending</option>
          <option value="paid" ${bk.pay_status==="paid"?"selected":""}>Paid</option>
          <option value="refunded" ${bk.pay_status==="refunded"?"selected":""}>Refunded</option>
          <option value="failed" ${bk.pay_status==="failed"?"selected":""}>Failed</option>
        </select>
      </div>

      <div style="margin-top:10px;font-size:12px;color:var(--gold);line-height:1.5">
        <i class="fas fa-info-circle"></i> Note: Using the dropdown in the table is sufficient for basic status changes (Confirmed/Checked In/etc). Do not change dates if the arrival has already occurred.
      </div>
      
      <div class="adm-modal-ft" style="margin-top:20px">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Close</button>
        <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> Save Changes</button>
      </div>
    </form>
  </div>`);
}
</script>
<?php 
$body=ob_get_clean();
adminPage('Bookings Control — Admin',$body,'');
?>
