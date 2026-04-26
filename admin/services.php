<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';

$msg = '';
try{
    $pdo->exec("CREATE TABLE IF NOT EXISTS services_catalog (
      id INT AUTO_INCREMENT PRIMARY KEY,
      icon VARCHAR(20) DEFAULT NULL,
      category VARCHAR(100) NOT NULL,
      name VARCHAR(200) NOT NULL,
      image_url VARCHAR(600) DEFAULT NULL,
      description TEXT,
      hours VARCHAR(120) DEFAULT NULL,
      cta_link VARCHAR(300) DEFAULT NULL,
      is_active TINYINT(1) DEFAULT 1,
      sort_order INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}catch(Exception $e){}

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_service'])){
    $id=(int)($_POST['sid']??0);
    $icon=clean($_POST['icon']??'');
    $cat=clean($_POST['category']??'');
    $name=clean($_POST['name']??'');
    $img=clean($_POST['image_url']??'');
    $desc=clean($_POST['description']??'');
    $hrs=clean($_POST['hours']??'');
    $cta=clean($_POST['cta_link']??'');
    $active=isset($_POST['is_active'])?1:0;
    $sort=(int)($_POST['sort_order']??0);
    if($cat && $name){
        if($id){
            $pdo->prepare("UPDATE services_catalog SET icon=?,category=?,name=?,image_url=?,description=?,hours=?,cta_link=?,is_active=?,sort_order=? WHERE id=?")
                ->execute([$icon?:null,$cat,$name,$img?:null,$desc?:null,$hrs?:null,$cta?:null,$active,$sort,$id]);
            $msg='Service updated.';
        } else {
            $pdo->prepare("INSERT INTO services_catalog (icon,category,name,image_url,description,hours,cta_link,is_active,sort_order) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$icon?:null,$cat,$name,$img?:null,$desc?:null,$hrs?:null,$cta?:null,$active,$sort]);
            $msg='Service created.';
        }
    }
}
if(isset($_GET['del']) && is_numeric($_GET['del'])){
    $pdo->prepare("DELETE FROM services_catalog WHERE id=?")->execute([(int)$_GET['del']]);
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}
$rows = [];
try{$rows = $pdo->query("SELECT * FROM services_catalog ORDER BY sort_order,id")->fetchAll();}catch(Exception $e){}

ob_start(); ?>
<?php if($msg):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?=htmlspecialchars($msg)?></div><?php endif; ?>
<div class="adm-ph"><div><h1>Services Catalog</h1><p class="sub"><?=count($rows)?> services</p></div><button class="btn btn-gold btn-sm" onclick="editSvc()"><i class="fas fa-plus"></i> Add Service</button></div>
<div class="ac"><div class="tw">\
  <thead>\
<th>Name</th><th>Category</th><th>Hours</th><th>Status</th><th>Sort</th><th>Actions</th>\
</thead>
  <tbody>
  <?php foreach($rows as $r): ?>
    <tr>
      <td><div style="font-weight:600"><?=htmlspecialchars($r['icon'].' '.$r['name'])?></div><div style="font-size:12px;color:var(--mu)"><?=htmlspecialchars(mb_strimwidth($r['description']??'',0,90,'...'))?></div></td>
      <td><?=htmlspecialchars($r['category'])?></td>
      <td><?=htmlspecialchars($r['hours']??'-')?></td>
      <td><span class="badge <?=$r['is_active']?'bg':'br'?>"><?=$r['is_active']?'Active':'Inactive'?></span></td>
      <td><?= (int)$r['sort_order'] ?></td>
      <td><div style="display:flex;gap:5px"><button class="btn btn-ghost btn-sm" onclick='editSvc(<?=htmlspecialchars(json_encode($r))?>)'><i class="fas fa-edit"></i></button><a href="?del=<?=$r['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete service?')"><i class="fas fa-trash"></i></a></div></td>
    </tr>
  <?php endforeach; if(empty($rows)): ?><tr><td colspan="6" style="text-align:center;padding:40px;color:var(--mu)">No services yet.</td></tr><?php endif; ?>
  </tbody>
</table></div></div>
<?php
$body=ob_get_clean();

// Fixed JavaScript - using single quotes for outer string and escaping
$js = "function editSvc(s=null){
    const v=(k,d='')=>(s&&s[k]!=null?s[k]:d);
    var modalHtml = '<div class=\"adm-modal adm-modal-lg\">' +
        '<div class=\"adm-modal-hd\">' +
        '<div class=\"adm-modal-title\">' + (s ? 'Edit' : 'Add') + ' Service</div>' +
        '<button class=\"adm-modal-x\" onclick=\"closeModal()\">×</button>' +
        '</div>' +
        '<form method=\"POST\">' +
        '<input type=\"hidden\" name=\"save_service\" value=\"1\">' +
        (s ? '<input type=\"hidden\" name=\"sid\" value=\"' + s.id + '\">' : '') +
        '<div class=\"adm-modal-bd\" style=\"display:grid;grid-template-columns:1fr 1fr;gap:12px\">' +
        '<div class=\"fg\"><label class=\"fl\">Icon (emoji)</label><input class=\"fc\" name=\"icon\" value=\"' + v('icon') + '\" placeholder=\"🍽\"></div>' +
        '<div class=\"fg\"><label class=\"fl\">Category*</label><input class=\"fc\" name=\"category\" required value=\"' + v('category') + '\"></div>' +
        '<div class=\"fg\" style=\"grid-column:1/-1\"><label class=\"fl\">Name*</label><input class=\"fc\" name=\"name\" required value=\"' + v('name') + '\"></div>' +
        '<div class=\"fg\" style=\"grid-column:1/-1\"><label class=\"fl\">Image URL</label><input class=\"fc\" name=\"image_url\" value=\"' + v('image_url') + '\"></div>' +
        '<div class=\"fg\"><label class=\"fl\">Hours</label><input class=\"fc\" name=\"hours\" value=\"' + v('hours') + '\"></div>' +
        '<div class=\"fg\"><label class=\"fl\">CTA Link</label><input class=\"fc\" name=\"cta_link\" value=\"' + v('cta_link','/contact.php') + '\"></div>' +
        '<div class=\"fg\" style=\"grid-column:1/-1\"><label class=\"fl\">Description</label><textarea class=\"fc\" rows=\"4\" name=\"description\">' + v('description') + '</textarea></div>' +
        '<div class=\"fg\"><label class=\"fl\">Sort Order</label><input class=\"fc\" type=\"number\" name=\"sort_order\" value=\"' + v('sort_order',0) + '\"></div>' +
        '<div class=\"fg\" style=\"display:flex;align-items:end\"><label style=\"display:flex;align-items:center;gap:8px\"><input type=\"checkbox\" name=\"is_active\" ' + ((!s||s.is_active==1) ? 'checked' : '') + '> Active</label></div>' +
        '</div>' +
        '<div class=\"adm-modal-ft\">' +
        '<button type=\"button\" class=\"btn btn-ghost btn-sm\" onclick=\"closeModal()\">Cancel</button>' +
        '<button class=\"btn btn-gold btn-sm\" type=\"submit\">Save</button>' +
        '</div>' +
        '</form>' +
        '</div>';
    openModal(modalHtml);
}";

adminPage('Services Catalog — Admin',$body,$js);
?>