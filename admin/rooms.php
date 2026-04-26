<?php
error_reporting(E_ALL);ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';

$msg='';

if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='bulk_rooms'){
    $selected = array_values(array_filter(array_map('intval', (array)($_POST['room_ids'] ?? []))));
    $bulk = clean($_POST['bulk_action'] ?? '');
    if(!empty($selected) && in_array($bulk, ['set_available','set_maintenance','delete'], true)){
        $in = implode(',', array_fill(0, count($selected), '?'));
        if($bulk === 'set_available'){
            $pdo->prepare("UPDATE rooms SET status='available' WHERE id IN ($in)")->execute($selected);
            $msg = count($selected).' room(s) set to available.';
        } elseif($bulk === 'set_maintenance'){
            $pdo->prepare("UPDATE rooms SET status='maintenance' WHERE id IN ($in)")->execute($selected);
            $msg = count($selected).' room(s) set to maintenance.';
        } else {
            $pdo->prepare("DELETE FROM rooms WHERE id IN ($in)")->execute($selected);
            $msg = count($selected).' room(s) deleted.';
        }
    }
}

// Toggle Room Status
if(isset($_GET['tog'])&&is_numeric($_GET['tog'])){
    $rid=(int)$_GET['tog'];
    $cQ=$pdo->prepare("SELECT status FROM rooms WHERE id=?");$cQ->execute([$rid]);$cs=$cQ->fetchColumn();
    $ns=$cs==='maintenance'?'available':'maintenance';
    $pdo->prepare("UPDATE rooms SET status=? WHERE id=?")->execute([$ns,$rid]);
    header('Location: '.$_SERVER['PHP_SELF'].'?msg='.urlencode("Room #$rid status → $ns."));exit;
}

// Delete Room
if(isset($_GET['del_rm'])&&is_numeric($_GET['del_rm'])){
    $pdo->prepare("DELETE FROM rooms WHERE id=?")->execute([(int)$_GET['del_rm']]);
    header('Location: rooms.php?msg='.urlencode("Room deleted successfully."));exit;
}

// Handle POST actions
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['action'])){
    $a = $_POST['action'];
    if($a === 'save_rt') {
        $id = (int)($_POST['rtid']??0);
        $n = clean($_POST['name']);
        $s = clean($_POST['slug']);
        $d = clean($_POST['desc']);
        $p = (float)$_POST['price'];
        $mg = (int)$_POST['max_guests'];
        $hb = isset($_POST['has_breakfast'])?1:0;
        
        if($id) {
           $pdo->prepare("UPDATE room_types SET name=?, slug=?, description=?, price_usd=?, max_guests=?, has_breakfast=? WHERE id=?")->execute([$n,$s,$d,$p,$mg,$hb,$id]);
           $msg = 'Room Type updated successfully.';
        } else {
           $pdo->prepare("INSERT INTO room_types (name,slug,description,price_usd,max_guests,has_breakfast) VALUES (?,?,?,?,?,?)")->execute([$n,$s,$d,$p,$mg,$hb]);
           $msg = 'Room Type created successfully.';
        }
    } elseif ($a === 'add_rm') {
        $rtid = (int)$_POST['rtid'];
        $rn = clean($_POST['room_number']);
        $fl = (int)$_POST['floor'];
        $vt = clean($_POST['view_type']);
        if($rtid && $rn) {
            try {
                $pdo->prepare("INSERT INTO rooms (room_number,room_type_id,floor,view_type) VALUES (?,?,?,?)")->execute([$rn,$rtid,$fl,$vt]);
                $msg = 'Room added successfully.';
            } catch (PDOException $e) {
                $msg = 'Error: Room number may already exist.';
            }
        }
    }
}
if(isset($_GET['msg'])) $msg=clean($_GET['msg']);

$tF=(int)($_GET['type']??0);
$stF=clean($_GET['st']??'all');
$w='1=1';$pa=[];
if($tF){$w.=' AND r.room_type_id=?';$pa[]=$tF;}
if($stF!=='all'){$w.=' AND r.status=?';$pa[]=$stF;}

$rms=$pdo->prepare("SELECT r.*,rt.name tname,rt.price_usd FROM rooms r JOIN room_types rt ON r.room_type_id=rt.id WHERE $w ORDER BY r.floor,r.room_number LIMIT 300");
$rms->execute($pa);$rooms=$rms->fetchAll();

$rts=$pdo->query("SELECT * FROM room_types ORDER BY sort_order")->fetchAll();
$statsQ=$pdo->query("SELECT status,COUNT(*) c FROM rooms GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

ob_start();?>
<?php if($msg):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?=htmlspecialchars($msg)?></div><?php endif;?>
<div class="adm-ph">
  <div><h1>Rooms Engine</h1><p class="sub"><?=count($rooms)?> rooms shown</p></div>
  <div style="display:flex;gap:10px">
    <button onclick="editRT(null)" class="btn btn-gold btn-sm"><i class="fas fa-plus"></i> New Room Type</button>
    <button onclick="addRmModal()" class="btn btn-ghost btn-sm"><i class="fas fa-door-open"></i> Add Room</button>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;margin-bottom:22px">
  <?php foreach($rts as $rt):?>
  <div style="background:var(--card);border:1px solid var(--br2);border-radius:10px;padding:16px;transition:border-color .2s;cursor:pointer" onmouseover="this.style.borderColor='var(--gold)'" onmouseout="this.style.borderColor=''" onclick='editRT(<?=json_encode($rt)?>)'>
    <div style="font-family:var(--serif);font-size:15px;margin-bottom:3px"><?=htmlspecialchars($rt['name'])?></div>
    <div style="font-family:var(--serif);font-size:22px;color:var(--gold);margin:6px 0"><?=formatPrice($rt['price_usd'])?></div>
    <div style="font-size:11px;color:var(--mu)"><?=htmlspecialchars($rt['slug'])?> · <?=$rt['max_guests']?> guests max</div>
    <div style="margin-top:12px;font-size:12px;color:var(--gold);display:flex;gap:15px;align-items:center">
      <div onclick="event.stopPropagation();editRT(<?=htmlspecialchars(json_encode($rt),ENT_QUOTES)?>)" style="flex:1;text-align:center;padding:6px;background:var(--bg);border-radius:4px;cursor:pointer"><i class="fas fa-edit"></i> Edit</div>
      <div onclick="event.stopPropagation();manageMedia(<?=$rt['id']?>,'<?=htmlspecialchars($rt['name'],ENT_QUOTES)?>')" style="flex:1;text-align:center;padding:6px;background:var(--bg);border-radius:4px;cursor:pointer"><i class="fas fa-images"></i> Media</div>
    </div>
  </div>
  <?php endforeach;?>
</div>

<div class="mc-grid mc-3" style="margin-bottom:20px">
  <div class="mc" style="--mc:#22c55e"><div class="mc-ico" style="background:rgba(34,197,94,.12);color:var(--gr)"><i class="fas fa-check-circle"></i></div><div><div class="mc-v"><?=$statsQ['available']??0?></div><div class="mc-l">Available</div></div></div>
  <div class="mc" style="--mc:#f59e0b"><div class="mc-ico" style="background:rgba(245,158,11,.12);color:var(--am)"><i class="fas fa-bed"></i></div><div><div class="mc-v"><?=$statsQ['occupied']??0?></div><div class="mc-l">Occupied</div></div></div>
  <div class="mc" style="--mc:#e07070"><div class="mc-ico" style="background:rgba(224,112,112,.12);color:var(--rd)"><i class="fas fa-tools"></i></div><div><div class="mc-v"><?=$statsQ['maintenance']??0?></div><div class="mc-l">Maintenance</div></div></div>
</div>

<div class="ac" style="margin-bottom:18px"><div class="ac-body" style="padding:12px 18px">
  <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <select name="type" class="fc" style="width:180px" onchange="this.form.submit()"><option value="">All Types</option><?php foreach($rts as $rt):?><option value="<?=$rt['id']?>" <?=$tF==$rt['id']?'selected':''?>><?=htmlspecialchars($rt['name'])?></option><?php endforeach;?></select>
    <select name="st" class="fc" style="width:150px" onchange="this.form.submit()"><option value="all" <?=$stF==='all'?'selected':''?>>All Status</option><?php foreach(['available'=>'Available','occupied'=>'Occupied','maintenance'=>'Maintenance'] as $v=>$l):?><option value="<?=$v?>" <?=$stF===$v?'selected':''?>><?=$l?></option><?php endforeach;?></select>
    <?php if($tF||$stF!=='all'):?><a href="?" class="btn btn-ghost btn-sm"><i class="fas fa-times"></i> Clear</a><?php endif;?>
    <span style="margin-left:auto;font-size:13px;color:var(--mu)"><?=count($rooms)?> rooms</span>
  </form>
</div></div>

<div class="ac">
<div class="ac-body" style="padding:10px 18px;border-bottom:1px solid var(--br2)">
  <form method="POST" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap" id="bulkForm">
    <input type="hidden" name="action" value="bulk_rooms">
    <select class="fc" name="bulk_action" style="max-width:220px">
      <option value="">Bulk action on selected...</option>
      <option value="set_available">Set Available</option>
      <option value="set_maintenance">Set Maintenance</option>
      <option value="delete">Delete Selected</option>
    </select>
    <button class="btn btn-gold btn-sm" type="submit" onclick="return confirmBulk()">Apply</button>
    <span style="font-size:12px;color:var(--mu)">Checkbox appears before room number.</span>
  </form>
</div>
<div class="tw"><table>
  <thead><tr><th style="width:32px"><input type="checkbox" id="chkAll" onclick="toggleAllRooms(this)"></th><th>Room #</th><th>Type</th><th>Floor</th><th>View</th><th>Nightly Rate</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
    <?php $sc=['available'=>'bg','occupied'=>'ba','maintenance'=>'br'];
    foreach($rooms as $rm):?>
    <tr><td><input type="checkbox" name="room_ids[]" value="<?=$rm['id']?>" form="bulkForm" class="room-cb"></td><td><code><?=htmlspecialchars($rm['room_number'])?></code></td>
    <td style="font-size:13px"><?=htmlspecialchars($rm['tname'])?></td>
    <td style="color:var(--mu)">F<?=$rm['floor']?></td>
    <td style="font-size:12.5px;color:var(--mu)"><?=htmlspecialchars($rm['view_type']??'—')?></td>
    <td style="color:var(--gold);font-family:var(--serif)"><?=formatPrice((float)($rm['price_usd']??0))?></td>
    <td><span class="badge <?=$sc[$rm['status']]??'bgold'?>"><?=ucfirst($rm['status'])?></span></td>
    <td>
      <div style="display:flex;gap:4px">
        <a href="?tog=<?=$rm['id']?>&type=<?=$tF?>&st=<?=$stF?>" class="btn <?=$rm['status']==='maintenance'?'btn-success':'btn-ghost'?> btn-sm" title="Toggle Maintenance"><i class="fas fa-<?=$rm['status']==='maintenance'?'check':'tools'?>"></i></a>
        <a href="?del_rm=<?=$rm['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this room definitively?')" title="Delete"><i class="fas fa-trash"></i></a>
      </div>
    </td></tr>
    <?php endforeach;?>
    <?php if(empty($rooms)):?><tr><td colspan="8" style="text-align:center;padding:40px;color:var(--mu)">No rooms match</td></tr><?php endif;?>
  </tbody>
</table></div></div>

<script>
const RT = <?=json_encode($rts)?>;

function editRT(rt) {
  const isNew = !rt;
  rt = rt || {id:0,name:'',slug:'',description:'',price_usd:100,max_guests:2,has_breakfast:0};
  openModal(`
  <div class="adm-modal" style="width:600px;max-width:95vw">
    <div class="adm-modal-hd">
      <div class="adm-modal-title">${isNew ? 'Create Room Type' : 'Edit Room Type'}</div>
      <button class="adm-modal-x" onclick="closeModal()">×</button>
    </div>
    <form method="POST" class="adm-modal-bd">
      <input type="hidden" name="action" value="save_rt">
      <input type="hidden" name="rtid" value="${rt.id}">
      
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px">
        <div class="fg">
          <label class="fl">Type Name</label>
          <input class="fc" name="name" value="${rt.name}" required>
        </div>
        <div class="fg">
          <label class="fl">Slug (URL)</label>
          <input class="fc" name="slug" value="${rt.slug}" required pattern="[a-z0-9-]+">
        </div>
      </div>
      
      <div class="fg">
        <label class="fl">Description</label>
        <textarea class="fc" name="desc" rows="3" required>${rt.description}</textarea>
      </div>
      
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px">
        <div class="fg">
          <label class="fl">Price (USD)</label>
          <input type="number" step="0.01" class="fc" name="price" value="${rt.price_usd}" required>
        </div>
        <div class="fg">
          <label class="fl">Max Guests</label>
          <input type="number" class="fc" name="max_guests" value="${rt.max_guests}" required>
        </div>
      </div>
      
      <div class="fg" style="display:flex;align-items:center;gap:10px;margin-top:10px">
        <input type="checkbox" name="has_breakfast" value="1" id="hb_hk" ${rt.has_breakfast==1?'checked':''}>
        <label for="hb_hk" style="cursor:pointer;font-size:14px">Includes Breakfast by Default</label>
      </div>
      
      <div class="adm-modal-ft" style="margin-top:20px">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> Save Type</button>
      </div>
    </form>
  </div>`);
}

function addRmModal() {
  let opts = '<option value="">Select Type</option>';
  RT.forEach(r => opts += `<option value="${r.id}">${r.name}</option>`);
  
  openModal(`
  <div class="adm-modal">
    <div class="adm-modal-hd">
      <div class="adm-modal-title">Add Physical Room</div>
      <button class="adm-modal-x" onclick="closeModal()">×</button>
    </div>
    <form method="POST" class="adm-modal-bd">
      <input type="hidden" name="action" value="add_rm">
      
      <div class="fg">
        <label class="fl">Room Number (e.g. D001)</label>
        <input class="fc" name="room_number" required>
      </div>
      
      <div class="fg">
        <label class="fl">Room Type</label>
        <select class="fc" name="rtid" required>${opts}</select>
      </div>
      
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px">
        <div class="fg">
          <label class="fl">Floor</label>
          <input type="number" class="fc" name="floor" value="1" required>
        </div>
        <div class="fg">
          <label class="fl">View Category</label>
          <select class="fc" name="view_type">
            <option value="City View">City View</option>
            <option value="Ocean View">Ocean View</option>
            <option value="Garden View">Garden View</option>
            <option value="Panoramic View">Panoramic View</option>
          </select>
        </div>
      </div>
      
      <div class="adm-modal-ft" style="margin-top:20px">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-gold"><i class="fas fa-plus"></i> Add Room</button>
      </div>
    </form>
  </div>`);
}

async function manageMedia(rtId, rtName) {
  openModal(`
  <div class="adm-modal" style="width:700px;max-width:95vw;max-height:90vh;display:flex;flex-direction:column">
    <div class="adm-modal-hd">
      <div class="adm-modal-title">Manage Media: ${rtName}</div>
      <button class="adm-modal-x" onclick="closeModal()">×</button>
    </div>
    <div class="adm-modal-bd" style="flex:1;overflow-y:auto;padding-bottom:0">
      <div id="mediaMap" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:15px;margin-bottom:20px">
        <div style="color:var(--mu);grid-column:1/-1">Loading media...</div>
      </div>
    </div>
    <div class="adm-modal-ft" style="background:var(--bg);padding:15px 24px;border-top:1px solid var(--br2)">
      <form id="mediaForm" method="POST" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:center;width:100%" onsubmit="uploadMedia(event, ${rtId})">
        <input type="file" name="media" class="fc" style="flex:1;padding:4px" accept="image/*,video/mp4,video/webm" required>
        <div style="display:flex;align-items:center;gap:6px">
          <input type="checkbox" name="is_primary" id="pri_cb" value="1">
          <label for="pri_cb" style="font-size:12px;cursor:pointer">Primary Cover</label>
        </div>
        <button type="submit" class="btn btn-gold btn-sm"><i class="fas fa-upload"></i> Upload</button>
      </form>
    </div>
  </div>`);
  loadMedia(rtId);
}

async function loadMedia(rtId) {
  const c = document.getElementById('mediaMap');
  if(!c) return;
  try {
    const res = await fetch(BASE+'/api/admin_media.php?action=list&room_type_id='+rtId);
    const data = await res.json();
    if (data.ok) {
      if (data.media.length === 0) {
         c.innerHTML = '<div style="color:var(--mu);grid-column:1/-1;text-align:center;padding:30px">No images/videos uploaded yet.</div>';
         return;
      }
      c.innerHTML = data.media.map(m => `
        <div style="position:relative;border-radius:var(--r);overflow:hidden;background:#000;aspect-ratio:4/3;border:${m.is_primary==1?'2px solid var(--gold)':'1px solid var(--br2)'}">
          ${m.media_type==='video' 
             ? `<video src="${BASE+m.image_url}" style="width:100%;height:100%;object-fit:cover" muted></video>
                <div style="position:absolute;top:5px;left:5px;background:rgba(0,0,0,.6);color:#fff;padding:2px 6px;border-radius:4px;font-size:10px"><i class="fas fa-video"></i> Video</div>`
             : `<img src="${BASE+m.image_url}" style="width:100%;height:100%;object-fit:cover">`
          }
          ${m.is_primary==1 ? `<div style="position:absolute;bottom:0;left:0;right:0;background:rgba(212,175,55,.9);color:#000;text-align:center;font-size:10px;font-weight:bold;padding:4px 0">PRIMARY</div>` : ''}
          <button type="button" onclick="deleteMedia(${m.id}, ${rtId})" class="btn" style="position:absolute;top:5px;right:5px;background:rgba(255,0,0,.8);color:#fff;border:none;border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;cursor:pointer" title="Delete"><i class="fas fa-trash" style="font-size:11px"></i></button>
        </div>
      `).join('');
    }
  } catch(e) { c.innerHTML = '<div style="color:red">Error loading media</div>'; }
}

async function uploadMedia(e, rtId) {
  e.preventDefault();
  const f = e.target;
  const btn = f.querySelector('button');
  const org = btn.innerHTML;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
  btn.disabled = true;
  
  const fd = new FormData(f);
  fd.append('action', 'upload');
  fd.append('room_type_id', rtId);
  
  try {
    const res = await fetch(BASE+'/api/admin_media.php', { method:'POST', body:fd });
    const data = await res.json();
    if(data.ok) {
       f.reset();
       loadMedia(rtId);
       toast('Upload successful', 'success');
    } else {
       toast(data.error || 'Upload failed', 'error');
    }
  } catch(e) { toast('Error uploading media', 'error'); }
  
  btn.innerHTML = org;
  btn.disabled = false;
}

async function deleteMedia(id, rtId) {
  if(!confirm("Delete this media permanently?")) return;
  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('media_id', id);
  try {
    const res = await fetch(BASE+'/api/admin_media.php', { method:'POST', body:fd });
    const data = await res.json();
    if(data.ok) {
      loadMedia(rtId);
      toast('Media deleted', 'success');
    } else toast(data.error || 'Failed to delete', 'error');
  } catch(e) { toast('Error deleting media', 'error'); }
}
function toggleAllRooms(el){
  document.querySelectorAll('.room-cb').forEach(cb=>{ cb.checked = el.checked; });
}
function confirmBulk(){
  const act = document.querySelector('[name="bulk_action"]').value;
  const checked = document.querySelectorAll('.room-cb:checked').length;
  if(!act){ toast('Choose a bulk action first', 'warning'); return false; }
  if(!checked){ toast('Select at least one room', 'warning'); return false; }
  return act !== 'delete' || confirm('Delete selected rooms permanently?');
}
</script>
<?php
$body=ob_get_clean();
adminPage('Rooms Engine — Admin',$body,'');
?>
