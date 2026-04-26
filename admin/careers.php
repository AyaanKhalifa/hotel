<?php
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';

// Update status
if (isset($_GET['status']) && isset($_GET['id'])) {
    $allowed = ['new','reviewing','shortlisted','rejected'];
    $st = in_array($_GET['status'], $allowed) ? $_GET['status'] : 'new';
    $pdo->prepare("UPDATE job_applications SET status=? WHERE id=?")->execute([$st, (int)$_GET['id']]);
    logActivity('update_job_status', "Changed status of application #".(int)$_GET['id']." to $st");
    header('Location: '.$_SERVER['PHP_SELF'].'?msg=updated'); exit;
}
// Delete
if (isset($_GET['del'])) {
    $pdo->prepare("DELETE FROM job_applications WHERE id=?")->execute([(int)$_GET['del']]);
    logActivity('delete_job_application', "Deleted job application #".(int)$_GET['del']);
    header('Location: '.$_SERVER['PHP_SELF'].'?msg=deleted'); exit;
}

$msg = '';
if (isset($_GET['msg'])) $msg = clean($_GET['msg']);

// CSV Export
if (isset($_GET['export'])) {
    $sf    = clean($_GET['s'] ?? 'all');
    $where = $sf !== 'all' ? "WHERE status='$sf'" : '';
    $data  = $pdo->query("SELECT id, name, email, phone, position, location, experience_years, status, applied_at FROM job_applications $where ORDER BY applied_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="applicants_'.date('Y-m-d').'.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Name', 'Email', 'Phone', 'Position', 'Location', 'Experience', 'Status', 'Date Applied']);
    foreach ($data as $row) fputcsv($out, $row);
    fclose($out);
    exit;
}

$sf    = clean($_GET['s'] ?? 'all');
$where = $sf !== 'all' ? "WHERE status='$sf'" : '';
try {
    $apps = $pdo->query("SELECT * FROM job_applications $where ORDER BY applied_at DESC LIMIT 200")->fetchAll();
    $counts = $pdo->query("SELECT status, COUNT(*) c FROM job_applications GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (\Exception $e) {
    $apps = []; $counts = [];
    $msg = 'Table not found. Please run <a href="'.BASE.'/_migrate_jobs.php" style="color:var(--gold)">_migrate_jobs.php</a> first.';
}

$statusColors = ['new'=>'#c09b5b','reviewing'=>'#3b82f6','shortlisted'=>'#22c55e','rejected'=>'#ef4444'];
$total = array_sum($counts);

ob_start(); ?>

<?php if($msg): ?>
<div class="alert alert-<?= strpos($msg,'error')!==false||strpos($msg,'Table')!==false?'danger':'success' ?>">
  <i class="fas fa-<?= strpos($msg,'del')!==false?'trash':'check-circle' ?>"></i>
  Application <?= $msg ?>. <?= strpos($msg,'Table')!==false?$msg:'' ?>
</div>
<?php endif; ?>

<div class="adm-ph">
  <div><h1>Job Applications</h1><p class="sub"><?= count($apps) ?> / <?= $total ?> total</p></div>
  <div style="display:flex;gap:10px">
    <a href="?export=1&s=<?= $sf ?>" class="btn btn-ghost btn-sm"><i class="fas fa-file-csv"></i> Export CSV</a>
  </div>
</div>

<!-- Stats row -->
<div class="mc-grid mc-4" style="margin-bottom:20px">
  <?php foreach(['new'=>['New','#c09b5b','fa-bell'],'reviewing'=>['Reviewing','#3b82f6','fa-eye'],'shortlisted'=>['Shortlisted','#22c55e','fa-check'],'rejected'=>['Rejected','#ef4444','fa-times']] as $s=>[$label,$col,$ico]): ?>
  <div class="mc" style="--mc:<?= $col ?>">
    <div class="mc-ico" style="background:<?= $col ?>22;color:<?= $col ?>"><i class="fas <?= $ico ?>"></i></div>
    <div><div class="mc-v"><?= $counts[$s]??0 ?></div><div class="mc-l"><?= $label ?></div></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filter -->
<div class="ac" style="margin-bottom:16px"><div class="ac-body" style="padding:12px 16px">
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <?php foreach(['all'=>'All','new'=>'New','reviewing'=>'Reviewing','shortlisted'=>'Shortlisted','rejected'=>'Rejected'] as $v=>$l): ?>
    <a href="?s=<?= $v ?>" class="btn btn-<?= $sf===$v?'gold':'ghost' ?> btn-sm"><?= $l ?></a>
    <?php endforeach; ?>
  </div>
</div></div>

<!-- Table -->
<div class="ac"><div class="tw"><table>
  <thead><tr>
    <th>#</th><th>Applicant</th><th>Position</th><th>Location</th><th>Experience</th><th>Applied</th><th>Status</th><th>Actions</th>
  </tr></thead>
  <tbody>
    <?php if(empty($apps)): ?>
    <tr><td colspan="8" style="text-align:center;padding:44px;color:var(--mu)">No applications found</td></tr>
    <?php endif; ?>
    <?php foreach($apps as $a): $col=$statusColors[$a['status']]??'#888'; ?>
    <tr>
      <td style="font-size:11px;color:var(--mu)">#<?= $a['id'] ?></td>
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          <?= userAvatar(null, $a['name'], 36) ?>
          <div>
            <div style="font-weight:500"><?= htmlspecialchars($a['name']) ?></div>
            <div style="font-size:11px;color:var(--mu)"><?= htmlspecialchars($a['email']) ?></div>
            <?php if($a['phone']): ?><div style="font-size:11px;color:var(--mu)"><?= htmlspecialchars($a['phone']) ?></div><?php endif; ?>
          </div>
        </div>
      </td>
      <td style="font-weight:500"><?= htmlspecialchars($a['position']??'—') ?></td>
      <td style="font-size:13px;color:var(--mu)"><?= htmlspecialchars($a['location']??'—') ?></td>
      <td style="font-size:13px"><?= htmlspecialchars($a['experience_years']??'—') ?></td>
      <td style="font-size:12px;color:var(--mu);white-space:nowrap"><?= date('d M Y',strtotime($a['applied_at'])) ?></td>
      <td>
        <select onchange="if(this.value)window.location='?id=<?= $a['id'] ?>&status='+this.value+'&s=<?= $sf ?>'"
          style="background:<?= $col ?>22;color:<?= $col ?>;border:1px solid <?= $col ?>55;border-radius:6px;padding:4px 8px;font-size:12px;font-weight:600;cursor:pointer;font-family:var(--sans)">
          <?php foreach(['new'=>'New','reviewing'=>'Reviewing','shortlisted'=>'Shortlisted','rejected'=>'Rejected'] as $sv=>$sl): ?>
          <option value="<?= $sv ?>" <?= $a['status']===$sv?'selected':'' ?>><?= $sl ?></option>
          <?php endforeach; ?>
        </select>
      </td>
      <td>
        <div style="display:flex;gap:4px">
          <button class="btn btn-ghost btn-sm" onclick="viewCover(<?= $a['id'] ?>,<?= htmlspecialchars(json_encode($a['cover_letter']??'No cover letter provided.'),ENT_QUOTES) ?>)" title="View Cover Letter"><i class="fas fa-eye"></i></button>
          <?php if(!empty($a['cv_path'])): ?>
            <a href="<?= BASE ?>/<?= htmlspecialchars($a['cv_path']) ?>" class="btn btn-ghost btn-sm" target="_blank" title="Download CV"><i class="fas fa-file-pdf"></i></a>
          <?php endif; ?>
          <a href="mailto:<?= htmlspecialchars($a['email']) ?>" class="btn btn-ghost btn-sm" title="Email Applicant"><i class="fas fa-envelope"></i></a>
          <a href="?del=<?= $a['id'] ?>&s=<?= $sf ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this application?')" title="Delete"><i class="fas fa-trash"></i></a>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table></div></div>

<script>
function viewCover(id, text) {
  openModal(`
    <div class="adm-modal" style="width:560px;max-width:95vw">
      <div class="adm-modal-hd">
        <div class="adm-modal-title">Cover Letter #${id}</div>
        <button class="adm-modal-x" onclick="closeModal()">×</button>
      </div>
      <div class="adm-modal-bd">
        <div style="line-height:1.8;font-size:14px;color:var(--tx2);white-space:pre-wrap">${text}</div>
      </div>
      <div class="adm-modal-ft">
        <button class="btn btn-gold" onclick="closeModal()">Close</button>
      </div>
    </div>`);
}
</script>

<?php
$body = ob_get_clean();
adminPage('Job Applications — Admin', $body, '');
?>
