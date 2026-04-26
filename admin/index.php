<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
requireAdmin();
require_once __DIR__ . '/_helper.php';

$today = date('Y-m-d');

// ── Metrics
$todayRevQ = $pdo->prepare("SELECT COALESCE(SUM(final_usd),0) FROM bookings WHERE DATE(created_at)=? AND pay_status='paid'");
$todayRevQ->execute([$today]); $todayRevenue = (float)$todayRevQ->fetchColumn();
$totalRevenue    = (float)$pdo->query("SELECT COALESCE(SUM(final_usd),0) FROM bookings WHERE pay_status='paid'")->fetchColumn();
$todayBookQ = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE DATE(created_at)=?");
$todayBookQ->execute([$today]); $todayBookings = (int)$todayBookQ->fetchColumn();
$totalBookings   = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('confirmed','checked_in','checked_out')")->fetchColumn();
$pendingBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed' AND pay_status='pending'")->fetchColumn();
$totalUsers      = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$nuQ = $pdo->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at)=? AND role='user'");
$nuQ->execute([$today]); $newUsersToday = (int)$nuQ->fetchColumn();
$availRooms    = (int)$pdo->query("SELECT COUNT(*) FROM rooms WHERE status='available'")->fetchColumn();
$occupiedRooms = (int)$pdo->query("SELECT COUNT(*) FROM rooms WHERE status='occupied'")->fetchColumn();
$totalRooms    = (int)$pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$occupancyPct  = $totalRooms > 0 ? round($occupiedRooms / $totalRooms * 100) : 0;
$unreadMessages= (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn();

// New Job Applications
$newAppsCount = 0;
try {
    $newAppsCount = (int)$pdo->query("SELECT COUNT(*) FROM job_applications WHERE status='new'")->fetchColumn();
    $recentApps = $pdo->query("SELECT * FROM job_applications ORDER BY applied_at DESC LIMIT 5")->fetchAll();
} catch(\Exception $e) { $recentApps = []; }

// Staff Activity
$activities = $pdo->query("
    SELECT al.*, u.name as staff_name, u.profile_img 
    FROM activity_log al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC LIMIT 10
")->fetchAll();

// ── Last 7 days revenue for chart
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $q = $pdo->prepare("SELECT COALESCE(SUM(final_usd),0) FROM bookings WHERE DATE(created_at)=? AND pay_status='paid'");
    $q->execute([$d]);
    $chartData[] = ['date' => date('D', strtotime($d)), 'revenue' => (float)$q->fetchColumn()];
}

// ── Recent bookings
$recentBookings = $pdo->query("
    SELECT b.*, u.profile_img, GROUP_CONCAT(br.room_type_name SEPARATOR ', ') as room_names
    FROM bookings b
    LEFT JOIN booked_rooms br ON br.booking_ref=b.booking_ref
    LEFT JOIN users u ON b.user_id=u.id
    GROUP BY b.id ORDER BY b.created_at DESC LIMIT 8
")->fetchAll();

// ── Top room types
$topRooms = $pdo->query("
    SELECT rt.name, COUNT(br.id) as bookings, COALESCE(SUM(br.total_usd),0) as revenue
    FROM room_types rt LEFT JOIN booked_rooms br ON br.room_type_id=rt.id
    GROUP BY rt.id ORDER BY bookings DESC
")->fetchAll();

// ── Membership breakdown
$memberships = $pdo->query("
    SELECT m.name, m.icon, COUNT(um.id) as members, m.gradient_from, m.gradient_to
    FROM memberships m LEFT JOIN user_memberships um ON um.membership_id=m.id AND um.status='active'
    GROUP BY m.id ORDER BY m.sort_order
")->fetchAll();

$B = BASE;
ob_start(); ?>
<div class="adm-ph">
  <div>
    <h1>Dashboard</h1>
    <p class="sub">Overview for <?= date('l, d F Y') ?></p>
  </div>
  <div style="display:flex;gap:10px">
    <a href="<?= $B ?>/admin/bookings.php" class="btn btn-ghost btn-sm">All Bookings</a>
    <a href="<?= $B ?>/rooms.php" class="btn btn-gold btn-sm" target="_blank"><i class="fas fa-external-link-alt"></i> View Hotel</a>
  </div>
</div>

<!-- Metric Cards -->
<div class="mc-grid mc-4" style="margin-bottom:24px">
  <?php
  $metrics = [
    ["Today's Revenue",  formatPrice($todayRevenue), 'fas fa-coins',        '#d4af37', formatPrice($totalRevenue).' total'],
    ["Today's Bookings", $todayBookings,              'fas fa-calendar-plus','#22c55e', $totalBookings.' all-time'],
    ['Occupancy Rate',   $occupancyPct.'%',            'fas fa-bed',          '#3b82f6', $availRooms.' available'],
    ['New Applicants',   $newAppsCount,                'fas fa-briefcase',    '#ef4444', 'New for HR'],
    ['Total Guests',     number_format($totalUsers),   'fas fa-users',        '#f59e0b', $newUsersToday.' users today'],
    ['Pending Payments', $pendingBookings,              'fas fa-clock',        '#d4a040', 'Pay at Hotel'],
    ['Unread Messages',  $unreadMessages,               'fas fa-envelope',     '#8b5cf6', 'Inbound'],
    ['Habibi Status',    'Online',                     'fas fa-robot',        '#c09b5b', 'AI Concierge'],
  ];
  foreach ($metrics as [$label,$value,$icon,$color,$sub]):
    $isTrend = strpos($sub,'+')!==false || strpos($sub,'total')!==false;
  ?>
  <div class="mc" style="--mc:<?= $color ?>; position:relative; overflow:hidden">
    <?php if($label==='Habibi Status'): ?><div style="position:absolute; top:8px; right:8px; width:6px; height:6px; border-radius:50%; background:#22c55e; box-shadow:0 0 10px #22c55e; animation:pulse 2s infinite"></div><?php endif; ?>
    <div class="mc-ico" style="background:<?= $color ?>20;color:<?= $color ?>"><i class="<?= $icon ?>"></i></div>
    <div>
      <div class="mc-v"><?= $value ?></div>
      <div class="mc-l"><?= $label ?></div>
      <div class="mc-s" style="display:flex; align-items:center; gap:4px">
        <?php if($isTrend): ?><i class="fas fa-chart-line" style="font-size:10px; opacity:.5"></i><?php endif; ?>
        <?= $sub ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Recent Job Applications (Hidden on Small) -->
<?php if(!empty($recentApps)): ?>
<div class="ac" style="margin-bottom:24px">
  <div class="ac-hd">
    <div class="ac-title"><i class="fas fa-briefcase" style="color:var(--gold)"></i> Recent Job Applications</div>
    <a href="<?= $B ?>/admin/careers.php" class="btn btn-ghost btn-sm">Manage HR</a>
  </div>
  <div class="tw">
    <table>
      <thead><tr><th>#</th><th>Applicant</th><th>Position</th><th>Location</th><th>Applied At</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach($recentApps as $app): ?>
        <tr>
          <td><span style="font-size:11px;color:var(--mu)">#<?= $app['id'] ?></span></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <?= userAvatar(null, $app['name'], 30) ?>
              <div style="font-size:13.5px;font-weight:500"><?= htmlspecialchars($app['name']) ?></div>
            </div>
          </td>
          <td style="font-size:13px"><?= htmlspecialchars($app['position']??'General') ?></td>
          <td style="font-size:13px;color:var(--mu)"><?= htmlspecialchars($app['location']??'Remote') ?></td>
          <td style="font-size:12px;color:var(--mu)"><?= date('d M Y', strtotime($app['applied_at'])) ?></td>
          <td><span class="badge" style="background:var(--gold-dim);color:var(--gold);font-size:10px"><?= ucfirst($app['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Charts row -->
<div class="g2" style="margin-bottom:24px">
  <div class="ac">
    <div class="ac-hd">
      <div class="ac-title"><i class="fas fa-chart-bar" style="color:var(--gold)"></i> Revenue — Last 7 Days</div>
      <div style="font-size:12px;color:var(--mu)"><?= formatPrice($totalRevenue) ?> total</div>
    </div>
    <div style="padding:20px"><canvas id="revenueChart" height="160"></canvas></div>
  </div>

  <div class="ac">
    <div class="ac-hd"><div class="ac-title"><i class="fas fa-bed" style="color:var(--gold)"></i> Room Performance</div></div>
    <div style="padding:8px 0">
      <?php foreach ($topRooms as $tr):
        $maxRev = max(array_column($topRooms,'revenue') ?: [1]);
        $pct    = $maxRev > 0 ? round($tr['revenue']/$maxRev*100) : 0; ?>
      <div style="padding:12px 22px;border-bottom:1px solid var(--br2)">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:13.5px">
          <span style="font-weight:500"><?= htmlspecialchars($tr['name']) ?></span>
          <span style="color:var(--gold)"><?= formatPrice($tr['revenue']) ?></span>
        </div>
        <div style="height:5px;background:var(--card2);border-radius:3px;overflow:hidden">
          <div style="height:100%;width:<?= $pct ?>%;background:linear-gradient(90deg,var(--gold2),var(--gold));border-radius:3px;transition:width .6s"></div>
        </div>
        <div style="font-size:11px;color:var(--mu);margin-top:4px"><?= $tr['bookings'] ?> bookings</div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="ac">
    <div class="ac-hd"><div class="ac-title"><i class="fas fa-history" style="color:var(--gold)"></i> Staff Activity</div><a href="#" class="btn btn-ghost btn-sm" style="font-size:10px">Live Feed</a></div>
    <div style="padding:4px 0;max-height:280px;overflow-y:auto">
      <?php if(empty($activities)): ?>
        <div style="text-align:center;padding:30px;color:var(--mu);font-size:12px">No recent staff actions</div>
      <?php else: foreach($activities as $act): ?>
        <div style="display:flex;align-items:flex-start;gap:10px;padding:12px 20px;border-bottom:1px solid var(--br2)">
          <?= userAvatar($act['profile_img'], $act['staff_name']??'System', 28) ?>
          <div style="flex:1">
            <div style="font-size:12.5px;font-weight:600"><?= htmlspecialchars($act['staff_name']??'System') ?></div>
            <div style="font-size:11.5px;color:var(--mu);line-height:1.4"><?= htmlspecialchars($act['details']) ?></div>
            <div style="font-size:10px;color:var(--gold);margin-top:2px;opacity:.8"><?= date('H:i',strtotime($act['created_at'])) ?> · <?= ucfirst(str_replace('_',' ',$act['action'])) ?></div>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<!-- Recent Bookings -->
<div class="ac" style="margin-bottom:24px">
  <div class="ac-hd">
    <div class="ac-title"><i class="fas fa-list" style="color:var(--gold)"></i> Recent Bookings</div>
    <a href="<?= $B ?>/admin/bookings.php" class="btn btn-ghost btn-sm">View All</a>
  </div>
  <div class="tw">
    <table>
      <thead><tr><th>Ref</th><th>Guest</th><th>Rooms</th><th>Dates</th><th>Amount</th><th>Status</th><th>Payment</th></tr></thead>
      <tbody>
        <?php foreach ($recentBookings as $bk):
          $sc = ['confirmed'=>'bgold','cancelled'=>'br','checked_in'=>'bg','checked_out'=>'bb'];
          $pc = ['paid'=>'bg','pending'=>'ba','failed'=>'br']; ?>
        <tr>
          <td><code><?= htmlspecialchars($bk['booking_ref']) ?></code></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <?= userAvatar($bk['profile_img'] ?? null, $bk['guest_name'], 32) ?>
              <div>
                <div style="font-size:13.5px;font-weight:500"><?= htmlspecialchars($bk['guest_name']) ?></div>
                <div style="font-size:11px;color:var(--mu)"><?= htmlspecialchars($bk['guest_email']) ?></div>
              </div>
            </div>
          </td>
          <td style="font-size:13px"><?= htmlspecialchars($bk['room_names'] ?? '—') ?></td>
          <td style="font-size:12.5px;white-space:nowrap"><?= date('d M',strtotime($bk['check_in'])) ?> → <?= date('d M Y',strtotime($bk['check_out'])) ?></td>
          <td style="font-family:var(--serif);font-size:15px;color:var(--gold)"><?= formatPrice($bk['final_usd']) ?></td>
          <td><span class="badge <?= $sc[$bk['status']] ?? 'bgold' ?>"><?= ucfirst(str_replace('_',' ',$bk['status'])) ?></span></td>
          <td><span class="badge <?= $pc[$bk['pay_status']] ?? 'ba' ?>"><?= ucfirst($bk['pay_status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($recentBookings)): ?>
        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--mu)">No bookings yet</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Memberships + Quick Actions -->
<div class="g2">
  <div class="ac">
    <div class="ac-hd"><div class="ac-title"><i class="fas fa-crown" style="color:var(--gold)"></i> Memberships Active</div></div>
    <div style="padding:16px 0">
      <?php foreach ($memberships as $m): ?>
      <div style="display:flex;align-items:center;gap:14px;padding:12px 22px;border-bottom:1px solid var(--br2)">
        <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,<?= htmlspecialchars($m['gradient_from']) ?>,<?= htmlspecialchars($m['gradient_to']) ?>);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0"><?= $m['icon'] ?></div>
        <div style="flex:1"><div style="font-size:14px;font-weight:500"><?= htmlspecialchars($m['name']) ?></div></div>
        <div style="font-family:var(--serif);font-size:22px;color:var(--gold)"><?= $m['members'] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="ac">
    <div class="ac-hd"><div class="ac-title"><i class="fas fa-bolt" style="color:var(--gold)"></i> Quick Actions</div></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:20px">
      <?php foreach ([
        [$B.'/admin/bookings.php',   'fas fa-calendar-check','Bookings','#22c55e'],
        [$B.'/admin/rooms.php',      'fas fa-bed',            'Rooms',   '#3b82f6'],
        [$B.'/admin/users.php',      'fas fa-users',          'Guests',  '#f59e0b'],
        [$B.'/admin/careers.php',    'fas fa-briefcase',      'Careers', '#ef4444'],
        [$B.'/admin/messages.php',   'fas fa-envelope',       'Messages','#8b5cf6'],
        [$B.'/admin/offers.php',     'fas fa-tag',            'Offers',  '#d4af37'],
      ] as [$href,$icon,$label,$color]): ?>
      <a href="<?= $href ?>" class="adm-quick-btn" style="--qc:<?= $color ?>">
        <i class="<?= $icon ?>"></i> <?= $label ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php
$body = ob_get_clean();
$head = '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>';
$js = 'document.addEventListener("DOMContentLoaded",()=>{
    const ctx = document.getElementById("revenueChart").getContext("2d");
    const gradient = ctx.createLinearGradient(0, 0, 0, 200);
    gradient.addColorStop(0, "rgba(192, 155, 91, 0.4)");
    gradient.addColorStop(1, "rgba(192, 155, 91, 0.0)");

    new Chart(ctx, {
      type: "line",
      data: {
        labels: chartData.map(d => d.date),
        datasets: [{
          label: "Revenue (USD)",
          data: chartData.map(d => d.revenue),
          backgroundColor: gradient,
          borderColor: "#c09b5b",
          borderWidth: 3,
          fill: true,
          tension: 0.4,
          pointBackgroundColor: "#c09b5b",
          pointBorderColor: "#fff",
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            mode: "index",
            intersect: false,
            backgroundColor: "rgba(28, 24, 19, 0.9)",
            titleFont: { family: "serif", size: 14 },
            bodyFont: { size: 13 },
            padding: 12,
            displayColors: false,
            callbacks: { label: c => " $" + c.raw.toLocaleString() }
          }
        },
        scales: {
          x: { grid: { color: gc }, ticks: { color: tc, font: { size: 11 } } },
          y: { 
            beginAtZero: true,
            grid: { color: gc }, 
            ticks: { 
              color: tc, 
              font: { size: 11 },
              callback: v => "$" + v.toLocaleString() 
            } 
          }
        }
      }
    });
  });';
adminPage('Dashboard — Admin', $body, $js, $head);
