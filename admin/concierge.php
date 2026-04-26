<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
require_once dirname(__DIR__).'/includes/service_requests.php';
requireAdmin();
$B = BASE; $theme = getTheme();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond'])) {
    $id     = (int)$_POST['req_id'];
    $status = clean($_POST['status'] ?? 'in_progress');
    $resp   = clean($_POST['response'] ?? '');
    $pdo->prepare("UPDATE concierge_requests SET status=?,response=?,is_read=1 WHERE id=?")->execute([$status,$resp,$id]);
    $rq = $pdo->prepare("SELECT ref,user_id FROM concierge_requests WHERE id=?");
    $rq->execute([$id]);
    $row = $rq->fetch();
    if ($row) {
        pushUserNotification(
            $pdo,
            $row['user_id'] ? (int)$row['user_id'] : null,
            'system',
            "Concierge request {$row['ref']} updated",
            "Your concierge request is now " . str_replace('_', ' ', $status) . ($resp ? ". Response: {$resp}" : '.'),
            BASE . '/notifications.php'
        );
    }
    $msg = 'Request updated.';
}
if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $pdo->prepare("DELETE FROM concierge_requests WHERE id=?")->execute([(int)$_GET['del']]);
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}
$filter = clean($_GET['filter'] ?? 'all');
$where  = $filter === 'all' ? '' : "WHERE cr.status='$filter'";
$reqs   = $pdo->query("SELECT cr.*,u.name as guest_name,u.email as guest_email FROM concierge_requests cr LEFT JOIN users u ON cr.user_id=u.id $where ORDER BY cr.created_at DESC LIMIT 100")->fetchAll();
$unread = $pdo->query("SELECT COUNT(*) FROM concierge_requests WHERE is_read=0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $theme ?>">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Concierge Requests — Royale Vista Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500&family=DM+Sans:wght@300;400;500;600&family=Cinzel:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= $B ?>/admin/css/admin.css">
</head><body>
<?php include __DIR__.'/partials/topbar.php'; ?>
<div class="adm-layout">
<?php include __DIR__.'/partials/sidebar.php'; ?>
<main class="adm-main">
<?php if ($msg): ?><div class="alert alert-success" style="margin-bottom:18px"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="adm-ph">
  <div><h1>Concierge Requests</h1><p class="sub"><?= count($reqs) ?> shown · <?= $unread ?> unread</p></div>
</div>
<div style="display:flex;gap:7px;margin-bottom:20px;flex-wrap:wrap">
  <?php foreach (['all'=>'All','pending'=>'Pending','in_progress'=>'In Progress','completed'=>'Completed','cancelled'=>'Cancelled'] as $v => $l): ?>
  <a href="?filter=<?= $v ?>" class="btn <?= $filter === $v ? 'btn-gold' : 'btn-ghost' ?> btn-sm"><?= $l ?></a>
  <?php endforeach; ?>
</div>
<div style="display:flex;flex-direction:column;gap:12px">
  <?php if (empty($reqs)): ?>
  <div style="text-align:center;padding:60px;color:var(--mu)"><i class="fas fa-concierge-bell" style="font-size:32px;display:block;margin-bottom:12px"></i>No requests found</div>
  <?php else: ?>
  <?php foreach ($reqs as $rq):
    $sc = ['pending'=>'ba','in_progress'=>'bb','completed'=>'bg','cancelled'=>'br'][$rq['status']] ?? 'bb'; ?>
  <div class="ac" style="<?= !$rq['is_read'] ? 'border-color:var(--gold)' : '' ?>">
    <div style="padding:18px 22px">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:10px">
        <div>
          <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px">
            <code style="color:var(--gold);font-size:12px"><?= htmlspecialchars($rq['ref']) ?></code>
            <span class="badge <?= $sc ?>"><?= ucfirst(str_replace('_',' ',$rq['status'])) ?></span>
            <?php if (!$rq['is_read']): ?><span class="badge bgold">New</span><?php endif; ?>
            <span style="font-size:12px;background:var(--card2);padding:2px 8px;border-radius:10px;color:var(--tx2);text-transform:capitalize"><?= htmlspecialchars($rq['category']) ?></span>
          </div>
          <div style="font-size:14px;font-weight:600"><?= htmlspecialchars($rq['guest_name'] ?? 'Guest') ?></div>
          <div style="font-size:12px;color:var(--mu)"><?= htmlspecialchars($rq['guest_email'] ?? '') ?> · <?= date('d M Y H:i', strtotime($rq['created_at'])) ?></div>
          <?php if ($rq['preferred_date']): ?><div style="font-size:12px;color:var(--mu);margin-top:2px"><i class="fas fa-calendar" style="color:var(--gold)"></i> <?= date('d M Y', strtotime($rq['preferred_date'])) . ($rq['preferred_time'] ? ' at ' . $rq['preferred_time'] : '') ?></div><?php endif; ?>
        </div>
        <div style="display:flex;gap:6px">
          <button class="btn btn-ghost btn-sm" onclick="openResp(<?= $rq['id'] ?>,'<?= htmlspecialchars(addslashes($rq['status'])) ?>')"><i class="fas fa-reply"></i> Respond</button>
          <a href="?del=<?= (int)$rq['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this concierge request?')"><i class="fas fa-trash"></i></a>
        </div>
      </div>
      <div style="margin-top:12px;padding:12px;background:var(--card2);border-radius:8px;font-size:13.5px;color:var(--tx2);line-height:1.7"><?= nl2br(htmlspecialchars($rq['request'])) ?></div>
      <?php if ($rq['response']): ?><div style="margin-top:10px;padding:10px 13px;background:var(--grbg);border-radius:8px;font-size:13px;color:var(--gr)"><i class="fas fa-check-circle"></i> <?= nl2br(htmlspecialchars($rq['response'])) ?></div><?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>
</main></div>

<!-- Respond Modal -->
<div id="respModal" style="position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:1000;display:none;align-items:center;justify-content:center;padding:20px">
  <div style="background:var(--card);border:1px solid var(--br);border-radius:var(--rlg);width:480px;max-width:100%">
    <div style="padding:18px 22px;border-bottom:1px solid var(--br2);display:flex;justify-content:space-between">
      <div style="font-family:var(--serif);font-size:20px">Respond to Request</div>
      <button onclick="document.getElementById('respModal').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--mu)">×</button>
    </div>
    <form method="POST" style="padding:20px 22px">
      <input type="hidden" name="respond" value="1">
      <input type="hidden" name="req_id" id="respId">
      <div class="fg"><label class="fl">Update Status</label>
        <select class="fc" name="status" id="respStatus">
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
          <option value="pending">Pending</option>
        </select>
      </div>
      <div class="fg"><label class="fl">Response to Guest</label>
        <textarea class="fc" name="response" rows="4" placeholder="Your response message…"></textarea>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('respModal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-gold"><i class="fas fa-paper-plane"></i> Send Response</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleTheme(){const h=document.documentElement,n=h.getAttribute('data-theme')==='dark'?'light':'dark';h.setAttribute('data-theme',n);document.cookie='rv_theme='+n+';path=/;max-age=31536000';const i=document.getElementById('themeIcon');if(i)i.className='fas fa-'+(n==='dark'?'sun':'moon');}
function toggleSb(){const s=document.getElementById('sb');if(s)s.classList.toggle('open');}
function openResp(id,status){document.getElementById('respId').value=id;document.getElementById('respStatus').value=status;document.getElementById('respModal').style.display='flex';}
document.getElementById('respModal').addEventListener('click',function(e){if(e.target===this)this.style.display='none';});
</script>
</body></html>
