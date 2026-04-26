<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
require_once dirname(__DIR__).'/includes/service_requests.php';
requireAdmin();
$B = BASE; $theme = getTheme(); $msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_req'])) {
    $id = (int)($_POST['id'] ?? 0);
    $status = clean($_POST['status'] ?? 'enquiry');
    $note = clean($_POST['admin_note'] ?? '');
    if ($id > 0 && in_array($status, ['enquiry','quoted','confirmed','cancelled'], true)) {
        $pdo->prepare("UPDATE event_bookings SET status=?, message=CONCAT(COALESCE(message,''), ?) WHERE id=?")
            ->execute([$status, $note ? "\n\nAdmin Note: {$note}" : '', $id]);
        $row = $pdo->prepare("SELECT ref,user_id FROM event_bookings WHERE id=?");
        $row->execute([$id]);
        $rq = $row->fetch();
        if ($rq) {
            pushUserNotification($pdo, $rq['user_id'] ? (int)$rq['user_id'] : null, 'booking', "Event request {$rq['ref']} {$status}", trim("Your event booking status is now {$status}. {$note}"), BASE . '/notifications.php');
        }
        $msg = 'Event request updated.';
    }
}
if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $pdo->prepare("DELETE FROM event_bookings WHERE id=?")->execute([(int)$_GET['del']]);
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

$rows = $pdo->query("SELECT eb.*,e.name as event_name,u.name as uname FROM event_bookings eb LEFT JOIN events e ON e.id=eb.event_id LEFT JOIN users u ON u.id=eb.user_id ORDER BY eb.created_at DESC LIMIT 200")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $theme ?>">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Event Requests — Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500&family=DM+Sans:wght@300;400;500;600&family=Cinzel:wght@400;500&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link rel="stylesheet" href="<?= $B ?>/admin/css/admin.css"></head><body>
<?php include __DIR__.'/partials/topbar.php'; ?><div class="adm-layout"><?php include __DIR__.'/partials/sidebar.php'; ?><main class="adm-main">
<?php if($msg):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="adm-ph"><div><h1>Event Requests</h1><p class="sub"><?= count($rows) ?> shown</p></div></div>
<?php foreach($rows as $r): ?>
<div class="ac" style="margin-bottom:10px"><div style="padding:14px 18px">
  <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap"><div>
    <div style="font-size:12px;color:var(--gold);font-family:var(--cinzel)"><?= htmlspecialchars($r['ref']) ?></div>
    <div style="font-size:14px;font-weight:600"><?= htmlspecialchars($r['name']) ?> · <?= htmlspecialchars($r['event_name'] ?: 'Custom Event') ?></div>
    <div style="font-size:12px;color:var(--mu)"><?= htmlspecialchars($r['email']) ?> · <?= (int)$r['guests'] ?> guests<?= $r['event_date'] ? ' · ' . htmlspecialchars($r['event_date']) : '' ?></div>
  </div>
  <span class="badge <?= $r['status']==='confirmed'?'bg':($r['status']==='cancelled'?'br':'ba') ?>"><?= ucfirst($r['status']) ?></span></div>
  <?php if($r['message']):?><div style="margin:8px 0;font-size:13px;color:var(--tx2)"><?= nl2br(htmlspecialchars($r['message'])) ?></div><?php endif; ?>
  <form method="POST" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <input type="hidden" name="update_req" value="1"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
    <select class="fc" name="status" style="max-width:180px"><?php foreach(['enquiry','quoted','confirmed','cancelled'] as $s): ?><option value="<?= $s ?>" <?= $r['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select>
    <input class="fc" name="admin_note" placeholder="Admin note" style="flex:1;min-width:200px">
    <button class="btn btn-gold btn-sm" type="submit"><i class="fas fa-check"></i> Save</button>
    <a href="?del=<?= (int)$r['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this event request?')"><i class="fas fa-trash"></i></a>
  </form>
</div></div>
<?php endforeach; ?>
</main></div>
<script>function toggleTheme(){const h=document.documentElement,n=h.getAttribute('data-theme')==='dark'?'light':'dark';h.setAttribute('data-theme',n);document.cookie='rv_theme='+n+';path=/;max-age=31536000';const i=document.getElementById('themeIcon');if(i)i.className='fas fa-'+(n==='dark'?'sun':'moon');}function toggleSb(){const s=document.getElementById('sb');if(s)s.classList.toggle('open');}</script>
</body></html>
