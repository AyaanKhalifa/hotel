<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';

$msg = '';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(($_POST['action'] ?? '') === 'create'){
        $code = strtoupper(clean($_POST['code'] ?? ''));
        $val = (float)($_POST['value_usd'] ?? 0);
        $name = clean($_POST['for_name'] ?? '');
        $email = clean($_POST['for_email'] ?? '');
        $exp = clean($_POST['expires_at'] ?? '');
        if($code && $val > 0){
            $pdo->prepare("INSERT INTO gift_cards (code,value_usd,balance_usd,for_name,for_email,expires_at,is_active) VALUES (?,?,?,?,?,?,1)")
                ->execute([$code,$val,$val,$name?:null,$email?:null,$exp?:null]);
            $msg = 'Gift card created.';
        }
    }
    if(($_POST['action'] ?? '') === 'toggle'){
        $id=(int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE gift_cards SET is_active = IF(is_active=1,0,1) WHERE id=?")->execute([$id]);
        $msg='Gift card status updated.';
    }
}

if(isset($_GET['del']) && is_numeric($_GET['del'])){
    $pdo->prepare("DELETE FROM gift_cards WHERE id=?")->execute([(int)$_GET['del']]);
    header('Location: gift-cards.php?msg='.urlencode('Gift card deleted.'));
    exit;
}
if(isset($_GET['msg'])) $msg = clean($_GET['msg']);

$rows = $pdo->query("SELECT gc.*,u.name purchased_user FROM gift_cards gc LEFT JOIN users u ON u.id=gc.purchased_by ORDER BY gc.created_at DESC LIMIT 300")->fetchAll();
$tot = (int)$pdo->query("SELECT COUNT(*) FROM gift_cards")->fetchColumn();
$act = (int)$pdo->query("SELECT COUNT(*) FROM gift_cards WHERE is_active=1")->fetchColumn();
$bal = (float)$pdo->query("SELECT COALESCE(SUM(balance_usd),0) FROM gift_cards WHERE is_active=1")->fetchColumn();

ob_start();?>
<?php if($msg):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?=htmlspecialchars($msg)?></div><?php endif;?>
<div class="adm-ph">
  <div><h1>Gift Cards</h1><p class="sub">Manage gift card inventory and balances</p></div>
  <button class="btn btn-gold btn-sm" onclick="openCreateGc()"><i class="fas fa-plus"></i> New Gift Card</button>
</div>
<div class="mc-grid mc-3" style="margin-bottom:16px">
  <div class="mc"><div class="mc-ico"><i class="fas fa-ticket"></i></div><div><div class="mc-v"><?=$tot?></div><div class="mc-l">Total Cards</div></div></div>
  <div class="mc"><div class="mc-ico"><i class="fas fa-circle-check"></i></div><div><div class="mc-v"><?=$act?></div><div class="mc-l">Active</div></div></div>
  <div class="mc"><div class="mc-ico"><i class="fas fa-wallet"></i></div><div><div class="mc-v"><?=formatPrice($bal)?></div><div class="mc-l">Active Balance</div></div></div>
</div>
<div class="ac"><div class="tw"><table>
  <thead><tr><th>Code</th><th>Value</th><th>Balance</th><th>Recipient</th><th>Status</th><th>Expires</th><th>Created</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><code><?=htmlspecialchars($r['code'])?></code></td>
        <td><?=formatPrice((float)$r['value_usd'])?></td>
        <td><?=formatPrice((float)$r['balance_usd'])?></td>
        <td style="font-size:12.5px"><?=htmlspecialchars($r['for_name'] ?: '—')?><?php if($r['for_email']): ?><br><span style="color:var(--mu)"><?=htmlspecialchars($r['for_email'])?></span><?php endif; ?></td>
        <td><span class="badge <?=$r['is_active']?'bg':'br'?>"><?=$r['is_active']?'Active':'Inactive'?></span></td>
        <td style="font-size:12.5px;color:var(--mu)"><?=htmlspecialchars($r['expires_at'] ?: 'No expiry')?></td>
        <td style="font-size:12px;color:var(--mu)"><?=date('d M Y',strtotime($r['created_at']))?></td>
        <td>
          <div style="display:flex;gap:4px">
            <form method="POST"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn btn-ghost btn-sm" title="Toggle active"><i class="fas fa-power-off"></i></button></form>
            <a href="?del=<?=$r['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this gift card?')"><i class="fas fa-trash"></i></a>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if(empty($rows)): ?><tr><td colspan="8" style="text-align:center;padding:40px;color:var(--mu)">No gift cards yet.</td></tr><?php endif; ?>
  </tbody>
</table></div></div>
<script>
function openCreateGc(){
  openModal(`<div class="adm-modal">
    <div class="adm-modal-hd"><div class="adm-modal-title">Create Gift Card</div><button class="adm-modal-x" onclick="closeModal()">×</button></div>
    <form method="POST" class="adm-modal-bd">
      <input type="hidden" name="action" value="create">
      <div class="fg"><label class="fl">Code</label><input class="fc" name="code" required placeholder="RVGIFT1001"></div>
      <div class="fg"><label class="fl">Value (USD)</label><input type="number" step="0.01" min="1" class="fc" name="value_usd" required></div>
      <div class="fg"><label class="fl">Recipient Name</label><input class="fc" name="for_name"></div>
      <div class="fg"><label class="fl">Recipient Email</label><input class="fc" type="email" name="for_email"></div>
      <div class="fg"><label class="fl">Expiry Date</label><input class="fc" type="date" name="expires_at"></div>
      <div class="adm-modal-ft"><button type="button" class="btn btn-ghost btn-sm" onclick="closeModal()">Cancel</button><button type="submit" class="btn btn-gold btn-sm">Create</button></div>
    </form></div>`);
}
</script>
<?php
$body=ob_get_clean();
adminPage('Gift Cards — Admin',$body);
