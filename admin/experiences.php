<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';

$msg = '';
$hasActive = false;
try {
    $pdo->query("SELECT is_active FROM experiences LIMIT 1");
    $hasActive = true;
} catch (Exception $e) {}

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_exp'])){
    $id = (int)($_POST['eid'] ?? 0);
    $title = clean($_POST['title'] ?? '');
    $cat = clean($_POST['category'] ?? '');
    $duration = clean($_POST['duration'] ?? '');
    $price = (float)($_POST['price_usd'] ?? 0);
    $maxGuests = max(1, (int)($_POST['max_guests'] ?? 1));
    $desc = clean($_POST['description'] ?? '');
    $img = clean($_POST['image_url'] ?? '');
    $sort = (int)($_POST['sort_order'] ?? 0);
    $active = isset($_POST['is_active']) ? 1 : 0;
    if($title){
        if($id){
            if($hasActive){
                $pdo->prepare("UPDATE experiences SET title=?,category=?,duration=?,price_usd=?,max_guests=?,description=?,image_url=?,sort_order=?,is_active=? WHERE id=?")
                    ->execute([$title,$cat?:null,$duration?:null,$price,$maxGuests,$desc?:null,$img?:null,$sort,$active,$id]);
            } else {
                $pdo->prepare("UPDATE experiences SET title=?,category=?,duration=?,price_usd=?,max_guests=?,description=?,image_url=?,sort_order=? WHERE id=?")
                    ->execute([$title,$cat?:null,$duration?:null,$price,$maxGuests,$desc?:null,$img?:null,$sort,$id]);
            }
            $msg = 'Experience updated.';
        } else {
            if($hasActive){
                $pdo->prepare("INSERT INTO experiences (title,category,duration,price_usd,max_guests,description,image_url,sort_order,is_active) VALUES (?,?,?,?,?,?,?,?,?)")
                    ->execute([$title,$cat?:null,$duration?:null,$price,$maxGuests,$desc?:null,$img?:null,$sort,$active]);
            } else {
                $pdo->prepare("INSERT INTO experiences (title,category,duration,price_usd,max_guests,description,image_url,sort_order) VALUES (?,?,?,?,?,?,?,?)")
                    ->execute([$title,$cat?:null,$duration?:null,$price,$maxGuests,$desc?:null,$img?:null,$sort]);
            }
            $msg = 'Experience created.';
        }
    }
}
if(isset($_GET['del']) && is_numeric($_GET['del'])){
    $pdo->prepare("DELETE FROM experiences WHERE id=?")->execute([(int)$_GET['del']]);
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

$rows = $pdo->query("SELECT * FROM experiences ORDER BY sort_order,id DESC")->fetchAll();
ob_start(); ?>
<?php if($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="adm-ph"><div><h1>Experiences</h1><p class="sub"><?= count($rows) ?> total</p></div>
<button class="btn btn-gold btn-sm" onclick="editExp()"><i class="fas fa-plus"></i> Add Experience</button></div>
<div class="ac"><div class="tw"><table>
<thead><tr><th>Title</th><th>Category</th><th>Duration</th><th>Price</th><th>Guests</th><th>Sort</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($rows as $r): ?>
<tr>
  <td><div style="font-weight:600"><?= htmlspecialchars($r['title']) ?></div><?php if(!empty($r['description'])): ?><div style="font-size:12px;color:var(--mu)"><?= htmlspecialchars(mb_strimwidth($r['description'],0,80,'...')) ?></div><?php endif; ?></td>
  <td><?= htmlspecialchars($r['category'] ?? '-') ?></td>
  <td><?= htmlspecialchars($r['duration'] ?? '-') ?></td>
  <td><?= formatPrice((float)($r['price_usd'] ?? 0)) ?></td>
  <td><?= (int)($r['max_guests'] ?? 0) ?></td>
  <td><?= (int)($r['sort_order'] ?? 0) ?></td>
  <td><div style="display:flex;gap:5px"><button class="btn btn-ghost btn-sm" onclick='editExp(<?= json_encode($r) ?>)'><i class="fas fa-edit"></i></button><a class="btn btn-danger btn-sm" href="?del=<?= $r['id'] ?>" onclick="return confirm('Delete this experience?')"><i class="fas fa-trash"></i></a></div></td>
</tr>
<?php endforeach; if(empty($rows)): ?><tr><td colspan="7" style="text-align:center;padding:40px;color:var(--mu)">No experiences found.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php
$body = ob_get_clean();
$js = "function editExp(e=null){const v=(k,d='')=>(e&&e[k]!=null?e[k]:d);openModal(`<div class='adm-modal adm-modal-lg'><div class='adm-modal-hd'><div class='adm-modal-title'>\${e?'Edit':'Add'} Experience</div><button class='adm-modal-x' onclick='closeModal()'>×</button></div><form method='POST'><input type='hidden' name='save_exp' value='1'>\${e?`<input type='hidden' name='eid' value='\${e.id}'>`:''}<div class='adm-modal-bd' style='display:grid;grid-template-columns:1fr 1fr;gap:12px'><div class='fg' style='grid-column:1/-1'><label class='fl'>Title*</label><input class='fc' name='title' required value='\${v('title')}'></div><div class='fg'><label class='fl'>Category</label><input class='fc' name='category' value='\${v('category')}'></div><div class='fg'><label class='fl'>Duration</label><input class='fc' name='duration' value='\${v('duration')}'></div><div class='fg'><label class='fl'>Price USD</label><input class='fc' type='number' step='0.01' name='price_usd' value='\${v('price_usd',0)}'></div><div class='fg'><label class='fl'>Max Guests</label><input class='fc' type='number' min='1' name='max_guests' value='\${v('max_guests',1)}'></div><div class='fg' style='grid-column:1/-1'><label class='fl'>Image URL</label><input class='fc' name='image_url' value='\${v('image_url')}'></div><div class='fg' style='grid-column:1/-1'><label class='fl'>Description</label><textarea class='fc' name='description' rows='4'>\${v('description')}</textarea></div><div class='fg'><label class='fl'>Sort Order</label><input class='fc' type='number' name='sort_order' value='\${v('sort_order',0)}'></div><div class='fg' style='display:flex;align-items:end'><label style='display:flex;align-items:center;gap:8px'><input type='checkbox' name='is_active' \${(!e||e.is_active==1)?'checked':''}> Active</label></div></div><div class='adm-modal-ft'><button type='button' class='btn btn-ghost btn-sm' onclick='closeModal()'>Cancel</button><button type='submit' class='btn btn-gold btn-sm'>Save</button></div></form></div>`)}";
adminPage('Experiences — Admin', $body, $js);

