<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$pageTitle = 'My Notifications';
$userId = (int)$_SESSION['user_id'];

// Default pagination/filtering setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Fetch total count and notifications
$uid = (int)$_SESSION['user_id'];
$total = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id=$uid")->fetchColumn();
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$uid, $limit, $offset]);
$notifications = $stmt->fetchAll();
$totalPages = ceil($total / $limit);

require __DIR__ . '/header.php';
?>
<style>
.rv-page { background: linear-gradient(to bottom, var(--bg2), var(--bg)); min-height: 100vh; padding-top: 100px; padding-bottom: 60px; }
.notif-container { max-width: 800px; margin: 0 auto; }
.notif-card { background: var(--card); border: 1px solid var(--bdr2); border-radius: 12px; margin-bottom: 12px; padding: 20px 24px; display: flex; gap: 16px; align-items: flex-start; transition: all .2s; cursor: pointer; text-decoration: none; color: inherit; }
.notif-card:hover { border-color: var(--gold); transform: translateY(-2px); box-shadow: var(--shadow2); }
.notif-card.unread { background: var(--gold-dim); border-left: 3px solid var(--gold); }
.n-icon { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.n-content { flex: 1; min-width: 0; }
.n-title { font-size: 15px; font-weight: 600; margin-bottom: 4px; color: var(--text); }
.n-text { font-size: 14px; color: var(--text2); line-height: 1.5; margin-bottom: 8px; }
.n-time { font-size: 11px; color: var(--muted); }
.n-action { font-size: 13px; color: var(--gold); font-weight: 500; margin-top: 6px; display: inline-block; }

/* Pagination styling */
.pg-btns { display: flex; gap: 8px; margin-top: 30px; justify-content: center; flex-wrap: wrap; }
.pg-btn { padding: 8px 16px; border-radius: 8px; font-size: 13px; border: 1px solid var(--bdr); color: var(--text2); text-decoration: none; transition: all .2s; }
.pg-btn:hover,.pg-btn.cur { background: var(--gold); color: #fff; border-color: var(--gold); }
</style>

<div class="rv-page">
    <div class="container notif-container">
        
        <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:30px;flex-wrap:wrap;gap:15px">
            <div>
                <h1 style="font-family:var(--serif);font-size:36px;font-weight:400;margin-bottom:8px">My Notifications</h1>
                <div style="color:var(--text2);font-size:14px"><?= t('stay_updated', 'Stay informed about your bookings and rewards.') ?></div>
            </div>
            <?php if ($total > 0): ?>
            <button onclick="markAll()" class="btn btn-outline"><i class="fas fa-check-double"></i> Mark all as read</button>
            <?php endif; ?>
        </div>

        <?php if (empty($notifications)): ?>
        <div style="text-align:center;padding:80px 20px;background:var(--card);border:1px solid var(--bdr2);border-radius:12px">
            <i class="fas fa-bell-slash" style="font-size:48px;color:var(--muted);opacity:.4;margin-bottom:16px;display:block"></i>
            <h3 style="font-family:var(--serif);font-size:20px;margin-bottom:8px">No Notifications Yet</h3>
            <p style="color:var(--text2);font-size:14px">When you have updates regarding your account, they will appear here.</p>
        </div>
        <?php else: ?>
            
            <?php foreach ($notifications as $n): 
                $icons = ['booking'=>'📅','cancellation'=>'❌','payment'=>'✅','system'=>'🔔','review'=>'⭐','loyalty'=>'🪙','offer'=>'🎁'];
                $bgcolors = ['booking'=>'rgba(212,175,55,.15)','cancellation'=>'rgba(239,68,68,.15)','payment'=>'rgba(34,197,94,.15)','system'=>'rgba(59,130,246,.15)','review'=>'rgba(245,158,11,.15)'];
                $ic = $icons[$n['type']] ?? '🔔';
                $bg = $bgcolors[$n['type']] ?? 'var(--card2)';
            ?>
            <a href="<?= htmlspecialchars($n['link'] ?: '#') ?>" class="notif-card <?= !$n['is_read'] ? 'unread' : '' ?>" id="n-<?= $n['id'] ?>" onclick="<?= $n['link'] ? '' : 'event.preventDefault();' ?> markSingleRead(<?= $n['id'] ?>)">
                <div class="n-icon" style="background:<?= $bg ?>"><?= $ic ?></div>
                <div class="n-content">
                    <div class="n-title"><?= htmlspecialchars($n['title']) ?></div>
                    <div class="n-text"><?= nl2br(htmlspecialchars($n['message'])) ?></div>
                    <div class="n-time"><?= date('d F Y, H:i', strtotime($n['created_at'])) ?></div>
                    <?php if ($n['link']): ?>
                    <span class="n-action">View details <i class="fas fa-arrow-right" style="font-size:10px;margin-left:4px"></i></span>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
            
            <?php if ($totalPages > 1): ?>
            <div class="pg-btns">
                <?php if ($page > 1): ?><a href="?page=<?=$page-1?>" class="pg-btn">← Prev</a><?php endif; ?>
                <?php for($p=1; $p<=$totalPages; $p++): ?>
                    <a href="?page=<?=$p?>" class="pg-btn <?=$p==$page?'cur':''?>"><?=$p?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?><a href="?page=<?=$page+1?>" class="pg-btn">Next →</a><?php endif; ?>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<script>
async function markSingleRead(id) {
    const el = document.getElementById('n-' + id);
    if(el) {
        el.classList.remove('unread');
        el.style.borderLeft = 'none';
        el.style.background = 'var(--card)';
    }
    const fd = new FormData();
    fd.append('action', 'mark_read');
    fd.append('id', id);
    await fetch(BASE + '/api/notifications.php', { method: 'POST', body: fd });
}
async function markAll() {
    const fd = new FormData();
    fd.append('action', 'mark_all_read');
    await fetch(BASE + '/api/notifications.php', { method: 'POST', body: fd });
    document.querySelectorAll('.notif-card.unread').forEach(el => {
        el.classList.remove('unread');
        el.style.borderLeft = 'none';
        el.style.background = 'var(--card)';
    });
    // Let the header polling mechanism naturally discover it reached 0.
}
</script>

<?php require __DIR__ . '/footer.php'; ?>
