<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';

$msg = '';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_hp'])){
    $id=(int)($_POST['hid']??0);
    $fields=['slug','name','city','country','continent','hero_image','description','amenities','phone','email'];
    $vals=[]; foreach($fields as $f){ $vals[] = clean($_POST[$f]??''); }
    $vals[]=(float)($_POST['lat']??0); $vals[]=(float)($_POST['lng']??0);
    $vals[]=(int)($_POST['rooms_count']??0); $vals[]=(int)isset($_POST['is_flagship']);
    $vals[]=(int)($_POST['sort_order']??0); $vals[]=(int)($_POST['since_year']??0);
    if($id){
        $pdo->prepare("UPDATE hotel_properties SET slug=?,name=?,city=?,country=?,continent=?,hero_image=?,description=?,amenities=?,phone=?,email=?,lat=?,lng=?,rooms_count=?,is_flagship=?,sort_order=?,since_year=? WHERE id=?")->execute(array_merge($vals,[$id]));
        $msg='Hotel property updated.';
    } else {
        $pdo->prepare("INSERT INTO hotel_properties (slug,name,city,country,continent,hero_image,description,amenities,phone,email,lat,lng,rooms_count,is_flagship,sort_order,since_year) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute($vals);
        $msg='Hotel property created.';
    }
}
if(isset($_GET['del'])&&is_numeric($_GET['del'])){
    $pdo->prepare("DELETE FROM hotel_properties WHERE id=?")->execute([(int)$_GET['del']]);
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}
$rows=$pdo->query("SELECT * FROM hotel_properties ORDER BY sort_order,name")->fetchAll();
ob_start(); ?>
<?php if($msg):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?=htmlspecialchars($msg)?></div><?php endif;?>
<div class="adm-ph"><div><h1>Hotel Properties (Map Data)</h1><p class="sub"><?=count($rows)?> total</p></div>
  <button class="btn btn-gold btn-sm" onclick="editHp()"><i class="fas fa-plus"></i> Add Property</button></div>
<div class="ac"><div class="tw"><table>
<thead><tr><th>Name</th><th>Location</th><th>Continent</th><th>Rooms</th><th>Flagship</th><th>Actions</th></tr></thead>
<tbody><?php foreach($rows as $r):?><tr>
<td><div style="font-weight:600"><?=htmlspecialchars($r['name'])?></div><div style="font-size:11px;color:var(--mu)"><?=htmlspecialchars($r['slug'])?></div></td>
<td><?=htmlspecialchars($r['city'])?>, <?=htmlspecialchars($r['country'])?></td>
<td><?=htmlspecialchars($r['continent'])?></td><td><?= (int)$r['rooms_count'] ?></td>
<td><span class="badge <?=$r['is_flagship']?'bgold':'bb'?>"><?=$r['is_flagship']?'Yes':'No'?></span></td>
<td><div style="display:flex;gap:5px"><button class="btn btn-ghost btn-sm" onclick='editHp(<?=json_encode($r)?>)'><i class="fas fa-edit"></i></button><a class="btn btn-danger btn-sm" href="?del=<?=$r['id']?>" onclick="return confirm('Delete property?')"><i class="fas fa-trash"></i></a></div></td>
</tr><?php endforeach; if(empty($rows)):?><tr><td colspan="6" style="text-align:center;padding:40px;color:var(--mu)">No map properties.</td></tr><?php endif;?></tbody>
</table></div></div>
<?php $body=ob_get_clean();
$js="function editHp(p=null){const v=(k,d='')=>(p&&p[k]!=null?p[k]:d);openModal(`<div class='adm-modal adm-modal-lg'><div class='adm-modal-hd'><div class='adm-modal-title'>\${p?'Edit':'Add'} Hotel Property</div><button class='adm-modal-x' onclick='closeModal()'>×</button></div><form method='POST'><input type='hidden' name='save_hp' value='1'>\${p?`<input type='hidden' name='hid' value='\${p.id}'>`:''}<div class='adm-modal-bd' style='display:grid;grid-template-columns:1fr 1fr;gap:12px'><div class='fg' style='grid-column:1/-1'><label class='fl'>Name*</label><input class='fc' name='name' required value='\${v('name')}'></div><div class='fg'><label class='fl'>Slug*</label><input class='fc' name='slug' required value='\${v('slug')}'></div><div class='fg'><label class='fl'>City*</label><input class='fc' name='city' required value='\${v('city')}'></div><div class='fg'><label class='fl'>Country*</label><input class='fc' name='country' required value='\${v('country')}'></div><div class='fg'><label class='fl'>Continent</label><input class='fc' name='continent' value='\${v('continent')}'></div><div class='fg'><label class='fl'>Rooms Count</label><input class='fc' type='number' name='rooms_count' value='\${v('rooms_count',0)}'></div><div class='fg'><label class='fl'>Latitude</label><input class='fc' type='number' step='0.0000001' name='lat' value='\${v('lat',0)}'></div><div class='fg'><label class='fl'>Longitude</label><input class='fc' type='number' step='0.0000001' name='lng' value='\${v('lng',0)}'></div><div class='fg' style='grid-column:1/-1'><label class='fl'>Hero Image URL</label><input class='fc' name='hero_image' value='\${v('hero_image')}'></div><div class='fg' style='grid-column:1/-1'><label class='fl'>Amenities (comma separated)</label><input class='fc' name='amenities' value='\${v('amenities')}'></div><div class='fg' style='grid-column:1/-1'><label class='fl'>Description</label><textarea class='fc' rows='3' name='description'>\${v('description')}</textarea></div><div class='fg'><label class='fl'>Phone</label><input class='fc' name='phone' value='\${v('phone')}'></div><div class='fg'><label class='fl'>Email</label><input class='fc' name='email' value='\${v('email')}'></div><div class='fg'><label class='fl'>Sort Order</label><input class='fc' type='number' name='sort_order' value='\${v('sort_order',0)}'></div><div class='fg'><label class='fl'>Since Year</label><input class='fc' type='number' name='since_year' value='\${v('since_year',0)}'></div><div class='fg' style='display:flex;align-items:end'><label style='display:flex;align-items:center;gap:8px'><input type='checkbox' name='is_flagship' \${p&&p.is_flagship==1?'checked':''}> Flagship</label></div></div><div class='adm-modal-ft'><button type='button' class='btn btn-ghost btn-sm' onclick='closeModal()'>Cancel</button><button type='submit' class='btn btn-gold btn-sm'>Save</button></div></form></div>`)}";
adminPage('Hotel Properties — Admin',$body,$js);

