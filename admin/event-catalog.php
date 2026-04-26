<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';

$msg = '';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_event'])){
    $id=(int)($_POST['eid']??0);
    $type=clean($_POST['type']??'corporate');
    $name=clean($_POST['name']??'');
    $desc=clean($_POST['description']??'');
    $cap=max(1,(int)($_POST['capacity']??1));
    $price=(float)($_POST['price_from']??0);
    $img=clean($_POST['hero_image']??'');
    $active=isset($_POST['is_active'])?1:0;
    $sort=(int)($_POST['sort_order']??0);
    if($name){
        if($id){
            $pdo->prepare("UPDATE events SET type=?,name=?,description=?,capacity=?,price_from=?,hero_image=?,is_active=?,sort_order=? WHERE id=?")
                ->execute([$type,$name,$desc?:null,$cap,$price,$img?:null,$active,$sort,$id]);
            $msg='Event updated.';
        } else {
            $pdo->prepare("INSERT INTO events (type,name,description,capacity,price_from,hero_image,is_active,sort_order) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$type,$name,$desc?:null,$cap,$price,$img?:null,$active,$sort]);
            $msg='Event created.';
        }
    }
}
if(isset($_GET['del']) && is_numeric($_GET['del'])){
    $pdo->prepare("DELETE FROM events WHERE id=?")->execute([(int)$_GET['del']]);
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}
$rows=$pdo->query("SELECT * FROM events ORDER BY sort_order,id DESC")->fetchAll();
ob_start(); ?>
<?php if($msg):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?=htmlspecialchars($msg)?></div><?php endif; ?>
<div class="adm-ph"><div><h1>Event Catalog</h1><p class="sub"><?=count($rows)?> event types</p></div>
  <button class="btn btn-gold btn-sm" onclick="editEvt()"><i class="fas fa-plus"></i> Add Event Type</button></div>
<div class="ac"><div class="tw">
<table>
<thead><tr><th>Name</th><th>Type</th><th>Capacity</th><th>From Price</th><th>Status</th><th>Sort</th><th>Actions</th></tr></thead>
<tbody><?php foreach($rows as $r):?><tr>
   <td><div style="font-weight:600"><?=htmlspecialchars($r['name'])?></div><div style="font-size:12px;color:var(--mu)"><?=htmlspecialchars(mb_strimwidth($r['description']??'',0,80,'...'))?></div></td>
   <td><?=htmlspecialchars($r['type'])?></td><td><?= (int)$r['capacity'] ?></td><td><?= formatPrice((float)$r['price_from']) ?></td>
   <td><span class="badge <?=$r['is_active']?'bg':'br'?>"><?=$r['is_active']?'Active':'Inactive'?></span></td>
   <td><?= (int)$r['sort_order'] ?></td>
   <td><div style="display:flex;gap:5px"><button class="btn btn-ghost btn-sm" onclick='editEvt(<?=json_encode($r)?>)'><i class="fas fa-edit"></i></button><a class="btn btn-danger btn-sm" href="?del=<?=$r['id']?>" onclick="return confirm('Delete event type?')"><i class="fas fa-trash"></i></a></div></td>
</tr><?php endforeach; if(empty($rows)):?><tr><td colspan="7" style="text-align:center;padding:40px;color:var(--mu)">No event types.</td></tr><?php endif;?></tbody>
</table>
</div></div>
<?php
$body=ob_get_clean();
$js="function editEvt(e=null){
    const v=(k,d='')=>(e&&e[k]!=null?e[k]:d);
    const types=['wedding','corporate','birthday','gala','conference','private'];
    const tOpts=types.map(function(t){
        return '<option value=\"'+t+'\" '+(v('type','corporate')===t?'selected':'')+'>'+t+'</option>';
    }).join('');
    var modalContent = '<div class=\"adm-modal adm-modal-lg\">' +
        '<div class=\"adm-modal-hd\">' +
            '<div class=\"adm-modal-title\">' + (e?'Edit':'Add') + ' Event Type</div>' +
            '<button class=\"adm-modal-x\" onclick=\"closeModal()\">×</button>' +
        '</div>' +
        '<form method=\"POST\">' +
            '<input type=\"hidden\" name=\"save_event\" value=\"1\">' +
            (e?'<input type=\"hidden\" name=\"eid\" value=\"'+e.id+'\">':'') +
            '<div class=\"adm-modal-bd\" style=\"display:grid;grid-template-columns:1fr 1fr;gap:12px\">' +
                '<div class=\"fg\" style=\"grid-column:1/-1\">' +
                    '<label class=\"fl\">Name*</label>' +
                    '<input class=\"fc\" name=\"name\" required value=\"'+v('name')+'\">' +
                '</div>' +
                '<div class=\"fg\">' +
                    '<label class=\"fl\">Type</label>' +
                    '<select class=\"fc\" name=\"type\">' + tOpts + '</select>' +
                '</div>' +
                '<div class=\"fg\">' +
                    '<label class=\"fl\">Capacity</label>' +
                    '<input class=\"fc\" type=\"number\" min=\"1\" name=\"capacity\" value=\"'+v('capacity',100)+'\">' +
                '</div>' +
                '<div class=\"fg\">' +
                    '<label class=\"fl\">Price From</label>' +
                    '<input class=\"fc\" type=\"number\" step=\"0.01\" name=\"price_from\" value=\"'+v('price_from',0)+'\">' +
                '</div>' +
                '<div class=\"fg\">' +
                    '<label class=\"fl\">Sort Order</label>' +
                    '<input class=\"fc\" type=\"number\" name=\"sort_order\" value=\"'+v('sort_order',0)+'\">' +
                '</div>' +
                '<div class=\"fg\" style=\"display:flex;align-items:end\">' +
                    '<label style=\"display:flex;align-items:center;gap:8px\">' +
                        '<input type=\"checkbox\" name=\"is_active\" ' + ((!e||e.is_active==1)?'checked':'') + '> Active' +
                    '</label>' +
                '</div>' +
                '<div class=\"fg\" style=\"grid-column:1/-1\">' +
                    '<label class=\"fl\">Hero Image URL</label>' +
                    '<input class=\"fc\" name=\"hero_image\" value=\"'+v('hero_image')+'\">' +
                '</div>' +
                '<div class=\"fg\" style=\"grid-column:1/-1\">' +
                    '<label class=\"fl\">Description</label>' +
                    '<textarea class=\"fc\" rows=\"4\" name=\"description\">'+v('description')+'</textarea>' +
                '</div>' +
            '</div>' +
            '<div class=\"adm-modal-ft\">' +
                '<button type=\"button\" class=\"btn btn-ghost btn-sm\" onclick=\"closeModal()\">Cancel</button>' +
                '<button type=\"submit\" class=\"btn btn-gold btn-sm\">Save</button>' +
            '</div>' +
        '</form>' +
    '</div>';
    openModal(modalContent);
}";
adminPage('Event Catalog — Admin',$body,$js);
?>