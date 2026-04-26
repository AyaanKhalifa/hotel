<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
require_once dirname(__DIR__).'/includes/service_requests.php';
requireAdmin();
ensureServiceRequestSchema($pdo);
$B = BASE;
$theme = getTheme();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_req'])) {
    $id = (int)($_POST['id'] ?? 0);
    $status = clean($_POST['admin_status'] ?? 'pending');
    $note = clean($_POST['admin_note'] ?? '');
    if ($id > 0 && in_array($status, ['pending','approved','rejected'], true)) {
        $opStatus = $status === 'approved' ? 'confirmed' : ($status === 'rejected' ? 'cancelled' : 'confirmed');
        $pdo->prepare("UPDATE dining_reservations SET admin_status=?, admin_note=?, decided_at=NOW(), status=? WHERE id=?")
            ->execute([$status, $note ?: null, $opStatus, $id]);
        $row = $pdo->prepare("SELECT id,ref,user_id FROM dining_reservations WHERE id=?");
        $row->execute([$id]);
        $rq = $row->fetch();
        if ($rq) {
            $title = "Dining request {$rq['ref']} " . ($status === 'approved' ? 'approved' : ($status === 'rejected' ? 'rejected' : 'updated'));
            $message = $status === 'approved'
                ? "Your dining request {$rq['ref']} is approved."
                : ($status === 'rejected' ? "Your dining request {$rq['ref']} is rejected." : "Your dining request {$rq['ref']} is updated.");
            pushUserNotification($pdo, $rq['user_id'] ? (int)$rq['user_id'] : null, 'booking', $title, trim($message . ' ' . $note), BASE . '/notifications.php');
        }
        $msg = 'Dining request updated.';
    }
}
if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $pdo->prepare("DELETE FROM dining_reservations WHERE id=?")->execute([(int)$_GET['del']]);
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

$filter = clean($_GET['filter'] ?? 'all');
$where = $filter === 'all' ? '' : "WHERE dr.admin_status=" . $pdo->quote($filter);
$rows = $pdo->query("SELECT dr.*,u.name as uname,u.email as uemail FROM dining_reservations dr LEFT JOIN users u ON u.id=dr.user_id $where ORDER BY dr.created_at DESC LIMIT 200")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $theme ?>">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dining Requests — Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500&family=DM+Sans:wght@300;400;500;600&family=Cinzel:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= $B ?>/admin/css/admin.css">
</head><body>
<?php include __DIR__.'/partials/topbar.php'; ?>
<div class="adm-layout">
<?php include __DIR__.'/partials/sidebar.php'; ?>
<main class="adm-main">
<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="adm-ph"><div><h1>Dining Requests</h1><p class="sub"><?= count($rows) ?> shown</p></div></div>
<div style="display:flex;gap:8px;margin-bottom:14px">
  <?php foreach(['all'=>'All','pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $k=>$v): ?>
    <a href="?filter=<?= $k ?>" class="btn <?= $filter===$k?'btn-gold':'btn-ghost' ?> btn-sm"><?= $v ?></a>
  <?php endforeach; ?>
</div>
<?php foreach($rows as $r): ?>
<div class="ac" style="margin-bottom:10px">
  <div style="padding:14px 18px">
    <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap">
      <div>
        <div style="font-size:12px;color:var(--gold);font-family:var(--cinzel)"><?= htmlspecialchars($r['ref']) ?></div>
        <div style="font-size:14px;font-weight:600"><?= htmlspecialchars($r['name']) ?> · <?= (int)$r['guests'] ?> guests</div>
        <div style="font-size:12px;color:var(--mu)"><?= htmlspecialchars($r['email']) ?><?= $r['phone'] ? ' · ' . htmlspecialchars($r['phone']) : '' ?></div>
        <div style="font-size:12px;color:var(--mu)"><?= htmlspecialchars($r['venue_name'] ?: 'Venue TBD') ?> · <?= htmlspecialchars($r['date']) ?> <?= htmlspecialchars($r['time']) ?></div>
      </div>
      <span class="badge <?= $r['admin_status']==='approved'?'bg':($r['admin_status']==='rejected'?'br':'ba') ?>"><?= ucfirst($r['admin_status']) ?></span>
    </div>
    <?php if ($r['requests']): ?><div style="margin-top:8px;font-size:13px;color:var(--tx2)"><?= nl2br(htmlspecialchars($r['requests'])) ?></div><?php endif; ?>
    <form method="POST" style="display:flex;gap:8px;align-items:center;margin-top:10px;flex-wrap:wrap">
      <input type="hidden" name="update_req" value="1"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
      <select class="fc" name="admin_status" style="max-width:160px">
        <?php foreach(['pending','approved','rejected'] as $s): ?><option value="<?= $s ?>" <?= $r['admin_status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
      </select>
      <input class="fc" name="admin_note" value="<?= htmlspecialchars($r['admin_note'] ?? '') ?>" placeholder="Admin note (optional)" style="flex:1;min-width:200px">
      <button class="btn btn-gold btn-sm" type="submit"><i class="fas fa-check"></i> Save</button>
      <a href="?del=<?= (int)$r['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this dining request?')"><i class="fas fa-trash"></i></a>
    </form>
  </div>
</div>
<?php endforeach; ?>
</main></div>
<script>
function toggleTheme(){const h=document.documentElement,n=h.getAttribute('data-theme')==='dark'?'light':'dark';h.setAttribute('data-theme',n);document.cookie='rv_theme='+n+';path=/;max-age=31536000';const i=document.getElementById('themeIcon');if(i)i.className='fas fa-'+(n==='dark'?'sun':'moon');}
function toggleSb(){const s=document.getElementById('sb');if(s)s.classList.toggle('open');}
</script>
</body></html>
