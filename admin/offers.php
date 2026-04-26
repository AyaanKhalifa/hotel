<?php
error_reporting(E_ALL);ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['save_offer'])){
    $id=(int)($_POST['oid']??0);$code=strtoupper(clean($_POST['code']??''));$tp=in_array($_POST['type']??'',['percent','fixed'])?$_POST['type']:'percent';
    $val=(float)($_POST['value']??0);$desc=clean($_POST['desc']??'');$from=clean($_POST['from']??'');$to=clean($_POST['to']??'');$act=(int)isset($_POST['active']);
    if($code&&$val>0){
        if($id){$pdo->prepare("UPDATE offers SET code=?,type=?,value=?,description=?,valid_from=?,valid_to=?,is_active=? WHERE id=?")->execute([$code,$tp,$val,$desc,$from?:null,$to?:null,$act,$id]);$msg='Updated.';}
        else{$pdo->prepare("INSERT INTO offers (code,type,value,description,valid_from,valid_to,is_active) VALUES (?,?,?,?,?,?,?)")->execute([$code,$tp,$val,$desc,$from?:null,$to?:null,$act]);$msg='Created.';}
    }
}
if(isset($_GET['del'])&&is_numeric($_GET['del'])){$pdo->prepare("DELETE FROM offers WHERE id=?")->execute([(int)$_GET['del']]);header('Location: '.$_SERVER['PHP_SELF']);exit;}
if(isset($_GET['tog'])&&is_numeric($_GET['tog'])){$pdo->prepare("UPDATE offers SET is_active=NOT is_active WHERE id=?")->execute([(int)$_GET['tog']]);header('Location: '.$_SERVER['PHP_SELF']);exit;}
$offers=$pdo->query("SELECT * FROM offers ORDER BY created_at DESC")->fetchAll();
$act=(int)$pdo->query("SELECT COUNT(*) FROM offers WHERE is_active=1")->fetchColumn();
ob_start();?>
<?php if($msg):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?=htmlspecialchars($msg)?></div><?php endif;?>
<div class="adm-ph"><div><h1>Offer Codes</h1><p class="sub"><?=$act?> active · <?=count($offers)?> total</p></div>
  <button class="btn btn-gold btn-sm" onclick="showOffer()"><i class="fas fa-plus"></i> New Code</button></div>
<div class="ac"><div class="tw"><table>
  <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Description</th><th>Valid Period</th><th>Uses</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach($offers as $o):$exp=$o['valid_to']&&strtotime($o['valid_to'])<time();?>
    <tr>
      <td><code style="font-size:14px;letter-spacing:1px"><?=htmlspecialchars($o['code'])?></code></td>
      <td><span class="badge <?=$o['type']==='percent'?'bb':'ba'?>"><?=$o['type']==='percent'?'% Off':'$ Off'?></span></td>
      <td style="font-family:var(--serif);font-size:17px;color:var(--gold)"><?=$o['type']==='percent'?$o['value'].'%':'$'.$o['value']?></td>
      <td style="font-size:12.5px;max-width:200px;color:var(--mu)"><?=htmlspecialchars($o['description']??'')?></td>
      <td style="font-size:12px;color:var(--mu)"><?=$o['valid_from']?date('d M y',strtotime($o['valid_from'])):'—'?> → <?=$o['valid_to']?date('d M y',strtotime($o['valid_to'])):'Forever'?></td>
      <td style="text-align:center"><?=$o['uses_count']??0?><?=$o['uses_max']?'/'.$o['uses_max']:''?></td>
      <td><span class="badge <?=$o['is_active']&&!$exp?'bg':($exp?'br':'ba')?>"><?=$o['is_active']&&!$exp?'Active':($exp?'Expired':'Inactive')?></span></td>
      <td><div style="display:flex;gap:4px">
        <button class="btn btn-ghost btn-sm" onclick='showOffer(<?=json_encode($o)?>)'><i class="fas fa-edit"></i></button>
        <a href="?tog=<?=$o['id']?>" class="btn btn-ghost btn-sm" title="Toggle"><i class="fas fa-<?=$o['is_active']?'pause':'play'?>"></i></a>
        <a href="?del=<?=$o['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
      </div></td>
    </tr>
    <?php endforeach;?>
    <?php if(empty($offers)):?><tr><td colspan="8" style="text-align:center;padding:40px;color:var(--mu)">No codes yet</td></tr><?php endif;?>
  </tbody>
</table></div></div>
<?php $body=ob_get_clean();
$js='function showOffer(o=null){
  const v=(k,d="")=>(o&&o[k]!=null?o[k]:d);
  openModal(`<div class="adm-modal"><div class="adm-modal-hd"><div class="adm-modal-title">${o?"Edit":"New"} Offer Code</div><button class="adm-modal-x" onclick="closeModal()">×</button></div>
<form method="POST"><input type="hidden" name="save_offer" value="1">${o?`<input type="hidden" name="oid" value="${o.id}">`:""}
<div class="adm-modal-bd" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
<div class="fg" style="grid-column:1/-1"><label class="fl">Code *</label><input class="fc" name="code" value="${v("code")}" placeholder="SUMMER20" required oninput="this.value=this.value.toUpperCase()"></div>
<div class="fg"><label class="fl">Type</label><select class="fc" name="type"><option value="percent" ${v("type")==="percent"?"selected":""}>Percent %</option><option value="fixed" ${v("type")==="fixed"?"selected":""}>Fixed $</option></select></div>
<div class="fg"><label class="fl">Value *</label><input class="fc" type="number" name="value" value="${v("value")}" min="0.01" step="0.01" required></div>
<div class="fg" style="grid-column:1/-1"><label class="fl">Description</label><input class="fc" name="desc" value="${v("description")}" placeholder="Brief description"></div>
<div class="fg"><label class="fl">Valid From</label><input class="fc" type="date" name="from" value="${v("valid_from")}"></div>
<div class="fg"><label class="fl">Valid To</label><input class="fc" type="date" name="to" value="${v("valid_to")}"></div>
<div style="grid-column:1/-1"><label style="display:flex;align-items:center;gap:8px;cursor:pointer"><input type="checkbox" name="active" ${!o||o.is_active=="1"?"checked":""}> Active immediately</label></div>
</div>
<div class="adm-modal-ft"><button type="button" class="btn btn-ghost btn-sm" onclick="closeModal()">Cancel</button><button type="submit" class="btn btn-gold btn-sm"><i class="fas fa-save"></i> Save</button></div></form></div>`);
}';
adminPage('Offers — Admin',$body,$js); ?>
