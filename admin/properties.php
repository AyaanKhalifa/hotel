<?php
error_reporting(E_ALL);ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['save_prop'])){
    $id=(int)($_POST['pid']??0);
    $f=['name','slug','city','country','continent','headline','phone','email','address'];$d=[];
    foreach($f as $k)$d[]=clean($_POST[$k]??'');
    $d[]=(float)($_POST['min_price']??100);$d[]=(int)isset($_POST['is_flagship']);$d[]=(int)isset($_POST['is_active']);
    if($id){$pdo->prepare("UPDATE properties SET name=?,slug=?,city=?,country=?,continent=?,headline=?,phone=?,email=?,address=?,min_price_usd=?,is_flagship=?,is_active=? WHERE id=?")->execute(array_merge($d,[$id]));$msg='Updated.';}
    else{$pdo->prepare("INSERT INTO properties (name,slug,city,country,continent,headline,phone,email,address,min_price_usd,is_flagship,is_active,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0)")->execute($d);$msg='Created.';}
}
if(isset($_GET['tog'])&&is_numeric($_GET['tog'])){$pdo->prepare("UPDATE properties SET is_active=NOT is_active WHERE id=?")->execute([(int)$_GET['tog']]);header('Location: '.$_SERVER['PHP_SELF']);exit;}
$props=$pdo->query("SELECT * FROM properties ORDER BY sort_order,name")->fetchAll();
$active=(int)$pdo->query("SELECT COUNT(*) FROM properties WHERE is_active=1")->fetchColumn();
ob_start();?>
<?php if($msg):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?=htmlspecialchars($msg)?></div><?php endif;?>
<div class="adm-ph"><div><h1>Properties</h1><p class="sub"><?=$active?> active · <?=count($props)?> total</p></div>
  <button class="btn btn-gold btn-sm" onclick="editProp()"><i class="fas fa-plus"></i> Add Property</button></div>
<div class="ac"><div class="tw"><table>
  <thead><tr><th>Property</th><th>Location</th><th>Continent</th><th>Min Price</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach($props as $p):?>
    <tr>
      <td><div style="font-weight:600;font-size:14px"><?=htmlspecialchars($p['name'])?></div><?php if($p['headline']):?><div style="font-size:11.5px;color:var(--mu);margin-top:2px"><?=htmlspecialchars(mb_strimwidth($p['headline'],0,55,'…'))?></div><?php endif;?></td>
      <td><div style="font-size:13px"><?=htmlspecialchars($p['city'])?>, <?=htmlspecialchars($p['country'])?></div><?php if($p['phone']):?><div style="font-size:11px;color:var(--mu)"><?=htmlspecialchars($p['phone'])?></div><?php endif;?></td>
      <td style="font-size:13px"><?=htmlspecialchars($p['continent']??'—')?></td>
      <td style="font-family:var(--serif);color:var(--gold)"><?=formatPrice($p['min_price_usd'])?></td>
      <td><?php if($p['is_flagship']):?><span class="badge bgold">Flagship</span><?php else:?><span class="badge bb">Standard</span><?php endif;?></td>
      <td><span class="badge <?=$p['is_active']?'bg':'br'?>"><?=$p['is_active']?'Active':'Inactive'?></span></td>
      <td><div style="display:flex;gap:5px">
        <button class="btn btn-ghost btn-sm" onclick='editProp(<?=json_encode($p)?>)'><i class="fas fa-edit"></i></button>
        <a href="?tog=<?=$p['id']?>" class="btn btn-ghost btn-sm" title="Toggle"><i class="fas fa-toggle-<?=$p['is_active']?'on':'off'?>"></i></a>
      </div></td>
    </tr>
    <?php endforeach;?>
  </tbody>
</table></div></div>
<?php $body=ob_get_clean();
$js='function editProp(p=null){
  const v=(k,d="")=>(p&&p[k]!=null?p[k]:d);
  openModal(`<div class="adm-modal adm-modal-lg"><div class="adm-modal-hd"><div class="adm-modal-title">${p?"Edit":"Add"} Property</div><button class="adm-modal-x" onclick="closeModal()">×</button></div>
<form method="POST"><input type="hidden" name="save_prop" value="1">${p?`<input type="hidden" name="pid" value="${p.id}">`:""}
<div class="adm-modal-bd" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
<div class="fg" style="grid-column:1/-1"><label class="fl">Property Name *</label><input class="fc" name="name" value="${v("name")}" required></div>
<div class="fg"><label class="fl">Slug *</label><input class="fc" name="slug" value="${v("slug")}" placeholder="new-york" required></div>
<div class="fg"><label class="fl">City *</label><input class="fc" name="city" value="${v("city")}" required></div>
<div class="fg"><label class="fl">Country *</label><input class="fc" name="country" value="${v("country")}" required></div>
<div class="fg"><label class="fl">Continent</label><select class="fc" name="continent">
  ${["Americas","Europe","Asia Pacific","Middle East","Africa"].map(c=>`<option ${v("continent")===c?"selected":""}>${c}</option>`).join("")}
</select></div>
<div class="fg"><label class="fl">Min Price (USD) *</label><input class="fc" type="number" name="min_price" value="${v("min_price_usd",200)}" min="1" required></div>
<div class="fg" style="grid-column:1/-1"><label class="fl">Headline</label><input class="fc" name="headline" value="${v("headline")}"></div>
<div class="fg"><label class="fl">Phone</label><input class="fc" name="phone" value="${v("phone")}"></div>
<div class="fg"><label class="fl">Email</label><input class="fc" name="email" value="${v("email")}"></div>
<div class="fg" style="grid-column:1/-1"><label class="fl">Address</label><input class="fc" name="address" value="${v("address")}"></div>
<div style="grid-column:1/-1;display:flex;gap:24px;align-items:center">
  <label style="display:flex;align-items:center;gap:8px;cursor:pointer"><input type="checkbox" name="is_flagship" ${p?.is_flagship=="1"?"checked":""}> Flagship Property</label>
  <label style="display:flex;align-items:center;gap:8px;cursor:pointer"><input type="checkbox" name="is_active" ${!p||p.is_active=="1"?"checked":""}> Active</label>
</div>
</div>
<div class="adm-modal-ft"><button type="button" class="btn btn-ghost btn-sm" onclick="closeModal()">Cancel</button><button type="submit" class="btn btn-gold btn-sm"><i class="fas fa-save"></i> Save</button></div>
</form></div>`);
}';
adminPage('Properties — Admin',$body,$js); ?>
