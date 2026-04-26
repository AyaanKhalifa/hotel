<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';

$msg = '';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_fac'])){
    $id=(int)($_POST['fid']??0);
    $rt=(int)($_POST['room_type_id']??0);
    $name=clean($_POST['name']??'');
    $icon=clean($_POST['icon']??'fas fa-check');
    $sort=(int)($_POST['sort_order']??0);
    if($rt && $name){
        if($id){
            $pdo->prepare("UPDATE room_facilities SET room_type_id=?,name=?,icon=?,sort_order=? WHERE id=?")->execute([$rt,$name,$icon,$sort,$id]);
            $msg='Facility updated.';
        } else {
            $pdo->prepare("INSERT INTO room_facilities (room_type_id,name,icon,sort_order) VALUES (?,?,?,?)")->execute([$rt,$name,$icon,$sort]);
            $msg='Facility added.';
        }
    }
}
if(isset($_GET['del']) && is_numeric($_GET['del'])){
    $pdo->prepare("DELETE FROM room_facilities WHERE id=?")->execute([(int)$_GET['del']]);
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

$types = $pdo->query("SELECT id,name FROM room_types ORDER BY sort_order,name")->fetchAll();
$rows = $pdo->query("SELECT rf.*,rt.name room_type_name FROM room_facilities rf JOIN room_types rt ON rt.id=rf.room_type_id ORDER BY rt.name,rf.sort_order,rf.id")->fetchAll();

ob_start(); ?>
<?php if($msg): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<div class="adm-ph">
    <div>
        <h1>Room Facilities</h1>
        <p class="sub"><?= count($rows) ?> facilities</p>
    </div>
    <button class="btn btn-gold btn-sm" onclick="editFac()">
        <i class="fas fa-plus"></i> Add Facility
    </button>
</div>

<div class="ac">
    <div class="tw">
        <table class="table">
            <thead>
                <tr>
                    <th>Room Type</th>
                    <th>Name</th>
                    <th>Icon</th>
                    <th>Sort</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['room_type_name']) ?></td>
                    <td><?= htmlspecialchars($r['name']) ?></td>
                    <td>
                        <i class="<?= htmlspecialchars($r['icon'] ?: 'fas fa-check') ?>"></i> 
                        <code><?= htmlspecialchars($r['icon']) ?></code>
                    </td>
                    <td><?= (int)$r['sort_order'] ?></td>
                    <td>
                        <div style="display:flex;gap:5px">
                            <button class="btn btn-ghost btn-sm" onclick='editFac(<?= json_encode($r) ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?del=<?= $r['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete facility?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($rows)): ?>
                <tr>
                    <td colspan="5" style="text-align:center;padding:40px;color:var(--mu)">
                        No facilities found.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$body = ob_get_clean();
$js = "const RTYPES = " . json_encode($types) . "; 
function editFac(f=null){
    const v=(k,d='')=>(f&&f[k]!=null?f[k]:d);
    const opts=RTYPES.map(t=>`<option value='\${t.id}' \${(String(v('room_type_id'))===String(t.id))?'selected':''}>\${t.name}</option>`).join('');
    openModal(`<div class='adm-modal'>
        <div class='adm-modal-hd'>
            <div class='adm-modal-title'>\${f?'Edit':'Add'} Facility</div>
            <button class='adm-modal-x' onclick='closeModal()'>×</button>
        </div>
        <form method='POST'>
            <input type='hidden' name='save_fac' value='1'>
            \${f?`<input type='hidden' name='fid' value='\${f.id}'>`:''}
            <div class='adm-modal-bd'>
                <div class='fg'>
                    <label class='fl'>Room Type</label>
                    <select class='fc' name='room_type_id' required>\${opts}</select>
                </div>
                <div class='fg'>
                    <label class='fl'>Facility Name</label>
                    <input class='fc' name='name' required value='\${v('name')}'>
                </div>
                <div class='fg'>
                    <label class='fl'>Icon Class</label>
                    <input class='fc' name='icon' value='\${v('icon','fas fa-check')}' placeholder='fas fa-wifi'>
                </div>
                <div class='fg'>
                    <label class='fl'>Sort Order</label>
                    <input class='fc' type='number' name='sort_order' value='\${v('sort_order',0)}'>
                </div>
            </div>
            <div class='adm-modal-ft'>
                <button type='button' class='btn btn-ghost btn-sm' onclick='closeModal()'>Cancel</button>
                <button class='btn btn-gold btn-sm' type='submit'>Save</button>
            </div>
        </form>
    </div>`);
}";

adminPage('Room Facilities — Admin', $body, $js);
?>