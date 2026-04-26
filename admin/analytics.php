<?php
error_reporting(E_ALL);ini_set('display_errors','1');
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();
require_once __DIR__.'/_helper.php';

$thisM=date('Y-m');$lastM=date('Y-m',strtotime('-1 month'));
$revMQ=$pdo->prepare("SELECT COALESCE(SUM(final_usd),0) FROM bookings WHERE DATE_FORMAT(created_at,'%Y-%m')=? AND pay_status='paid'");$revMQ->execute([$thisM]);$revM=(float)$revMQ->fetchColumn();
$revLQ=$pdo->prepare("SELECT COALESCE(SUM(final_usd),0) FROM bookings WHERE DATE_FORMAT(created_at,'%Y-%m')=? AND pay_status='paid'");$revLQ->execute([$lastM]);$revL=(float)$revLQ->fetchColumn();
$revG=$revL>0?round(($revM-$revL)/$revL*100,1):0;
$revT=(float)$pdo->query("SELECT COALESCE(SUM(final_usd),0) FROM bookings WHERE pay_status='paid'")->fetchColumn();
$bkMQ=$pdo->prepare("SELECT COUNT(*) FROM bookings WHERE DATE_FORMAT(created_at,'%Y-%m')=?");$bkMQ->execute([$thisM]);$bkM=(int)$bkMQ->fetchColumn();
$bkT=(int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$rms=(int)$pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$occ=(int)$pdo->query("SELECT COUNT(*) FROM rooms WHERE status='occupied'")->fetchColumn();
$occP=$rms>0?round($occ/$rms*100):0;
$uT=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$uMQ=$pdo->prepare("SELECT COUNT(*) FROM users WHERE role='user' AND DATE_FORMAT(created_at,'%Y-%m')=?");$uMQ->execute([$thisM]);$uM=(int)$uMQ->fetchColumn();
// 30-day data
$days=[];$revs=[];$bks=[];
for($i=29;$i>=0;$i--){$d=date('Y-m-d',strtotime("-{$i} days"));$days[]=date('d M',strtotime($d));$rq=$pdo->prepare("SELECT COALESCE(SUM(final_usd),0) FROM bookings WHERE DATE(created_at)=? AND pay_status='paid'");$rq->execute([$d]);$revs[]=(float)$rq->fetchColumn();$bq=$pdo->prepare("SELECT COUNT(*) FROM bookings WHERE DATE(created_at)=?");$bq->execute([$d]);$bks[]=(int)$bq->fetchColumn();}
$mLbls=[];$mRevs=[];for($i=11;$i>=0;$i--){$m=date('Y-m',strtotime("-{$i} months"));$mLbls[]=date('M y',strtotime($m.'-01'));$mq=$pdo->prepare("SELECT COALESCE(SUM(final_usd),0) FROM bookings WHERE DATE_FORMAT(created_at,'%Y-%m')=? AND pay_status='paid'");$mq->execute([$m]);$mRevs[]=(float)$mq->fetchColumn();}
$topRm=$pdo->query("SELECT rt.name,COUNT(br.id) c,COALESCE(SUM(br.total_usd),0) r FROM room_types rt LEFT JOIN booked_rooms br ON br.room_type_id=rt.id GROUP BY rt.id ORDER BY c DESC")->fetchAll();
$payM=$pdo->query("SELECT pay_method,COUNT(*) c FROM bookings GROUP BY pay_method")->fetchAll();

$head='<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>';
ob_start();?>
<div class="adm-ph"><div><h1>Analytics</h1><p class="sub">Revenue, occupancy &amp; guest intelligence</p></div></div>
<div class="mc-grid mc-4" style="margin-bottom:22px">
  <div class="mc" style="--mc:#c09b5b"><div class="mc-ico" style="background:rgba(192,155,91,.12);color:var(--gold)"><i class="fas fa-coins"></i></div><div><div class="mc-v"><?=formatPrice($revM)?></div><div class="mc-l">Revenue This Month</div><div class="mc-s" style="color:<?=$revG>=0?'var(--gr)':'var(--rd)'?>"><?=$revG>=0?'▲':'▼'?> <?=abs($revG)?>% vs last month</div></div></div>
  <div class="mc" style="--mc:#22c55e"><div class="mc-ico" style="background:rgba(34,197,94,.12);color:var(--gr)"><i class="fas fa-calendar-check"></i></div><div><div class="mc-v"><?=$bkM?></div><div class="mc-l">Bookings This Month</div><div class="mc-s"><?=$bkT?> all-time</div></div></div>
  <div class="mc" style="--mc:#3b82f6"><div class="mc-ico" style="background:rgba(59,130,246,.12);color:var(--bl)"><i class="fas fa-bed"></i></div><div><div class="mc-v"><?=$occP?>%</div><div class="mc-l">Occupancy Rate</div><div class="mc-s"><?=$occ?>/<?=$rms?> rooms</div></div></div>
  <div class="mc" style="--mc:#8b5cf6"><div class="mc-ico" style="background:rgba(139,92,246,.12);color:#8b5cf6"><i class="fas fa-users"></i></div><div><div class="mc-v"><?=number_format($uT)?></div><div class="mc-l">Total Guests</div><div class="mc-s">+<?=$uM?> this month</div></div></div>
</div>
<div class="g2" style="gap:20px;margin-bottom:20px">
  <div class="ac"><div class="ac-hd"><div class="ac-title"><i class="fas fa-chart-area" style="color:var(--gold)"></i> 30-Day Revenue Trend</div></div><div style="padding:16px"><canvas id="c1" height="200"></canvas></div></div>
  <div class="ac"><div class="ac-hd"><div class="ac-title"><i class="fas fa-chart-pie" style="color:var(--gold)"></i> Payment Methods</div></div><div style="padding:16px"><canvas id="c2" height="200"></canvas></div></div>
</div>
<div class="g2" style="gap:20px;margin-bottom:20px">
  <div class="ac"><div class="ac-hd"><div class="ac-title"><i class="fas fa-chart-bar" style="color:var(--gold)"></i> 12-Month Revenue</div></div><div style="padding:16px"><canvas id="c3" height="210"></canvas></div></div>
  <div class="ac"><div class="ac-hd"><div class="ac-title"><i class="fas fa-trophy" style="color:var(--gold)"></i> Room Performance</div></div>
    <div class="tw"><table><thead><tr><th>#</th><th>Room Type</th><th style="text-align:center">Bookings</th><th style="text-align:right">Revenue</th></tr></thead><tbody>
    <?php foreach($topRm as $i=>$r):?><tr><td style="font-family:var(--serif);font-size:16px;color:var(--mu)"><?=$i+1?></td><td style="font-weight:500"><?=htmlspecialchars($r['name'])?></td><td style="text-align:center"><span class="badge bgold"><?=$r['c']?></span></td><td style="text-align:right;font-family:var(--serif);color:var(--gold)"><?=formatPrice($r['r'])?></td></tr><?php endforeach;?>
    <?php if(empty($topRm)):?><tr><td colspan="4" style="text-align:center;padding:20px;color:var(--mu)">No data yet</td></tr><?php endif;?>
    </tbody></table></div></div>
</div>
<?php $body=ob_get_clean();
$d=json_encode($days);$rv=json_encode($revs);$bk=json_encode($bks);
$ml=json_encode($mLbls);$mr=json_encode($mRevs);
$pml=json_encode(array_column($payM,'pay_method'));$pmc=json_encode(array_column($payM,'c'));
$js="const dk=document.documentElement.dataset.theme==='dark';
const gc=dk?'rgba(255,255,255,.04)':'rgba(0,0,0,.04)';const tc='#9a8e82';const g='#c09b5b';
const co={responsive:true,plugins:{legend:{display:false},tooltip:{mode:'index',intersect:false,backgroundColor:'rgba(28,24,19,.95)',callbacks:{label:d=>'$'+d.raw.toFixed(2)}}},scales:{x:{grid:{color:gc},ticks:{color:tc,maxTicksLimit:8}},y:{grid:{color:gc},ticks:{color:tc,callback:v=>'$'+v}}}};
new Chart(document.getElementById('c1').getContext('2d'),{type:'line',data:{labels:$d,datasets:[{label:'Revenue',data:$rv,borderColor:g,backgroundColor:'rgba(192,155,91,.07)',borderWidth:2,fill:true,tension:.45,pointRadius:0,pointHoverRadius:5}]},options:co});
new Chart(document.getElementById('c2').getContext('2d'),{type:'doughnut',data:{labels:$pml,datasets:[{data:$pmc,backgroundColor:['#c09b5b','#3b82f6','#22c55e','#f59e0b'],borderWidth:0,hoverOffset:5}]},options:{responsive:true,plugins:{legend:{position:'bottom',labels:{color:tc,padding:14}}}}});
new Chart(document.getElementById('c3').getContext('2d'),{type:'bar',data:{labels:$ml,datasets:[{label:'Revenue',data:$mr,backgroundColor:'rgba(192,155,91,.55)',borderColor:g,borderWidth:1,borderRadius:4}]},options:{...co,plugins:{legend:{display:false}}}});";
adminPage('Analytics — Admin',$body,$js,$head); ?>
