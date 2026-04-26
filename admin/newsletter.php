<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';

$msg = '';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_email'])){
    $email = clean($_POST['email'] ?? '');
    if(filter_var($email,FILTER_VALIDATE_EMAIL)){
        $pdo->prepare("INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)")->execute([$email]);
        $msg = 'Subscriber saved.';
    }
}
if(isset($_GET['del']) && is_numeric($_GET['del'])){
    $pdo->prepare("DELETE FROM newsletter_subscribers WHERE id=?")->execute([(int)$_GET['del']]);
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}
$rows = $pdo->query("SELECT * FROM newsletter_subscribers ORDER BY created_at DESC LIMIT 500")->fetchAll();
ob_start(); ?>
<?php if($msg):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?=htmlspecialchars($msg)?></div><?php endif; ?>
<div class="adm-ph"><div><h1>Newsletter Subscribers</h1><p class="sub"><?=count($rows)?> subscribers</p></div></div>
<div class="ac" style="margin-bottom:12px"><div class="ac-body" style="padding:12px 16px">
  <form method="POST" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <input type="hidden" name="add_email" value="1">
    <input class="fc" type="email" name="email" required placeholder="new@subscriber.com" style="max-width:300px">
    <button class="btn btn-gold btn-sm" type="submit">Add Subscriber</button>
  </form>
</div></div>
<div class="ac"><div class="tw"><table>
  <thead><tr><th>Email</th><th>Subscribed At</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach($rows as $r): ?>
  <tr><td><?=htmlspecialchars($r['email'])?></td><td><?=date('d M Y H:i',strtotime($r['created_at']))?></td>
  <td><a class="btn btn-danger btn-sm" href="?del=<?=$r['id']?>" onclick="return confirm('Delete subscriber?')"><i class="fas fa-trash"></i></a></td></tr>
  <?php endforeach; if(empty($rows)): ?><tr><td colspan="3" style="text-align:center;padding:40px;color:var(--mu)">No subscribers yet.</td></tr><?php endif; ?>
  </tbody>
</table></div></div>
<?php
$body=ob_get_clean();
adminPage('Newsletter Subscribers — Admin',$body);

