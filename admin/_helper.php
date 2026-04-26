<?php
/**
 * ROYALE VISTA ADMIN — Page Helper v3.0
 * Usage: require __DIR__.'/_helper.php'; then call adminPage($title, $body, $js, $head)
 */
if (!function_exists('adminPage')):
function adminPage(string $title, string $body, string $js='', string $head=''): void {
    global $pdo;
    $B    = BASE;
    $T    = getTheme();
    $user = htmlspecialchars(explode(' ',$_SESSION['username']??'Admin')[0]);
    $cur  = basename($_SERVER['PHP_SELF']);
    $unread = $pending = 0;
    try { $unread  = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn(); } catch(Exception $e){}
    try { $pending = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE pay_status='pending'")->fetchColumn(); } catch(Exception $e){}
    ?><!DOCTYPE html>
<html lang="en" data-theme="<?=$T?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=htmlspecialchars($title)?> — Royale Vista Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500&family=DM+Sans:wght@300;400;500;600&family=Cinzel:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?=$B?>/admin/css/admin.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
<?=$head?>
</head>
<body>
<header class="adm-top">
  <div class="adm-top-left">
    <button class="adm-toggle" onclick="toggleSb()"><i class="fas fa-bars"></i></button>
    <a href="<?=$B?>/admin/index.php" class="adm-brand">
      <div class="adm-emblem">RV</div>
      <span class="adm-brand-name hide-sm">Royale Vista</span>
    </a>
  </div>
  <div class="adm-top-right">
    <a href="<?=$B?>/" class="adm-tbtn" target="_blank" title="View Site"><i class="fas fa-external-link-alt"></i><span class="hide-sm"> Site</span></a>
    <button class="adm-tbtn" onclick="toggleTheme()" title="Theme"><i class="fas fa-<?=$T==='dark'?'sun':'moon'?>" id="thIco"></i></button>
    <div class="adm-user-pill"><?= userAvatar($_SESSION['profile_img']??null, $_SESSION['username']??'A', 26) ?><span class="hide-sm"><?=$user?></span></div>
  </div>
</header>
<div class="adm-layout">
<aside class="adm-sb" id="sb">
  <div class="sb-logo"><div class="adm-emblem">RV</div><span class="adm-brand-name">Admin</span></div>
  <nav class="sb-nav">
    <div class="sb-group">Overview</div>
    <a href="<?=$B?>/admin/index.php"     class="sb-link <?=$cur==='index.php'?'on':''?>"><i class="fas fa-gauge-high"></i><span>Dashboard</span></a>
    <a href="<?=$B?>/admin/analytics.php" class="sb-link <?=$cur==='analytics.php'?'on':''?>"><i class="fas fa-chart-line"></i><span>Analytics</span></a>
    <a href="<?=$B?>/admin/reports.php"   class="sb-link <?=$cur==='reports.php'?'on':''?>"><i class="fas fa-file-chart-column"></i><span>Reports</span></a>

    <div class="sb-group">Operations</div>
    <a href="<?=$B?>/admin/bookings.php"   class="sb-link <?=$cur==='bookings.php'?'on':''?>"><i class="fas fa-calendar-check"></i><span>Bookings</span><?php if($pending):?><b class="sb-badge"><?=$pending?></b><?php endif?></a>
    <a href="<?=$B?>/admin/cancellations.php" class="sb-link <?=$cur==='cancellations.php'?'on':''?>"><i class="fas fa-ban"></i><span>Cancellations</span></a>
    <a href="<?=$B?>/admin/payments.php"   class="sb-link <?=$cur==='payments.php'?'on':''?>"><i class="fas fa-credit-card"></i><span>Payments</span></a>
    <a href="<?=$B?>/admin/rooms.php"      class="sb-link <?=$cur==='rooms.php'?'on':''?>"><i class="fas fa-bed"></i><span>Rooms</span></a>
    <a href="<?=$B?>/admin/room-facilities.php" class="sb-link <?=$cur==='room-facilities.php'?'on':''?>"><i class="fas fa-list-check"></i><span>Room Facilities</span></a>
    <a href="<?=$B?>/admin/properties.php" class="sb-link <?=$cur==='properties.php'?'on':''?>"><i class="fas fa-globe"></i><span>Properties</span></a>
    <a href="<?=$B?>/admin/hotel-properties.php" class="sb-link <?=$cur==='hotel-properties.php'?'on':''?>"><i class="fas fa-map-marked-alt"></i><span>Map Properties</span></a>
    <a href="<?=$B?>/admin/event-catalog.php" class="sb-link <?=$cur==='event-catalog.php'?'on':''?>"><i class="fas fa-calendar-plus"></i><span>Event Catalog</span></a>
    <a href="<?=$B?>/admin/dining.php" class="sb-link <?=$cur==='dining.php'?'on':''?>"><i class="fas fa-utensils"></i><span>Dining Requests</span></a>
    <a href="<?=$B?>/admin/spa.php" class="sb-link <?=$cur==='spa.php'?'on':''?>"><i class="fas fa-spa"></i><span>Spa Requests</span></a>
    <a href="<?=$B?>/admin/events.php" class="sb-link <?=$cur==='events.php'?'on':''?>"><i class="fas fa-calendar-days"></i><span>Event Requests</span></a>
    <a href="<?=$B?>/admin/concierge.php" class="sb-link <?=$cur==='concierge.php'?'on':''?>"><i class="fas fa-concierge-bell"></i><span>Concierge Requests</span></a>
    <a href="<?=$B?>/admin/services.php" class="sb-link <?=$cur==='services.php'?'on':''?>"><i class="fas fa-bell-concierge"></i><span>Services Catalog</span></a>
    <a href="<?=$B?>/admin/pricing.php"    class="sb-link <?=$cur==='pricing.php'?'on':''?>"><i class="fas fa-tags"></i><span>Pricing</span></a>

    <div class="sb-group">Guests</div>
    <a href="<?=$B?>/admin/users.php"       class="sb-link <?=$cur==='users.php'?'on':''?>"><i class="fas fa-users"></i><span>Guests</span></a>
    <a href="<?=$B?>/admin/memberships.php" class="sb-link <?=$cur==='memberships.php'?'on':''?>"><i class="fas fa-crown"></i><span>Memberships</span></a>
    <a href="<?=$B?>/admin/loyalty.php"     class="sb-link <?=$cur==='loyalty.php'?'on':''?>"><i class="fas fa-coins"></i><span>Loyalty</span></a>

    <div class="sb-group">Marketing</div>
    <a href="<?=$B?>/admin/offers.php"   class="sb-link <?=$cur==='offers.php'?'on':''?>"><i class="fas fa-tag"></i><span>Offer Codes</span></a>
    <a href="<?=$B?>/admin/reviews.php"  class="sb-link <?=$cur==='reviews.php'?'on':''?>"><i class="fas fa-star"></i><span>Reviews</span></a>
    <a href="<?=$B?>/admin/messages.php" class="sb-link <?=$cur==='messages.php'?'on':''?>"><i class="fas fa-envelope"></i><span>Messages</span><?php if($unread):?><b class="sb-badge"><?=$unread?></b><?php endif?></a>
    <a href="<?=$B?>/admin/newsletter.php" class="sb-link <?=$cur==='newsletter.php'?'on':''?>"><i class="fas fa-at"></i><span>Newsletter</span></a>
    <a href="<?=$B?>/admin/gallery.php"  class="sb-link <?=$cur==='gallery.php'?'on':''?>"><i class="fas fa-images"></i><span>Gallery</span></a>
    <a href="<?=$B?>/admin/experiences.php"  class="sb-link <?=$cur==='experiences.php'?'on':''?>"><i class="fas fa-mountain-sun"></i><span>Experiences</span></a>
    <a href="<?=$B?>/admin/careers.php"  class="sb-link <?=$cur==='careers.php'?'on':''?>"><i class="fas fa-briefcase"></i><span>Careers</span></a>

    <div class="sb-group">System</div>
    <a href="<?=$B?>/admin/notifications.php" class="sb-link <?=$cur==='notifications.php'?'on':''?>"><i class="fas fa-bell"></i><span>Notifications</span></a>
    <a href="<?=$B?>/admin/database.php" class="sb-link <?=$cur==='database.php'?'on':''?>"><i class="fas fa-database"></i><span>Database Explorer</span></a>
    <a href="<?=$B?>/admin/coverage.php" class="sb-link <?=$cur==='coverage.php'?'on':''?>"><i class="fas fa-diagram-project"></i><span>Coverage Dashboard</span></a>
    <a href="<?=$B?>/admin/settings.php" class="sb-link <?=$cur==='settings.php'?'on':''?>"><i class="fas fa-gear"></i><span>Settings</span></a>

    <div class="sb-sep"></div>
    <a href="<?=$B?>/" class="sb-link" target="_blank"><i class="fas fa-external-link-alt"></i><span>View Hotel</span></a>
    <a href="<?=$B?>/admin/logout.php" class="sb-link sb-logout"><i class="fas fa-sign-out-alt"></i><span>Sign Out</span></a>
  </nav>
</aside>
<main class="adm-main" id="admMain">
<?=$body?>
</main>
</div>
<div id="ts"></div>
<div class="mbk" id="mbk" onclick="if(event.target===this)closeModal()"></div>
<div class="mbox" id="mbox"></div>
<script>
const BASE='<?=$B?>';
function toggleSb(){document.getElementById('sb').classList.toggle('open')}
function toggleTheme(){const h=document.documentElement,n=h.dataset.theme==='dark'?'light':'dark';h.dataset.theme=n;document.cookie='rv_theme='+n+';path=/;max-age=31536000';const i=document.getElementById('thIco');if(i)i.className='fas fa-'+(n==='dark'?'sun':'moon')}
function toast(msg,type='info',dur=4200){
  const st=document.getElementById('ts');if(!st)return;
  const el=document.createElement('div');el.className='adm-toast '+type;
  const icons={success:'✓',error:'✗',info:'◆',warning:'⚠'};
  el.innerHTML=`<span>${icons[type]||'◆'}</span><span class="adm-toast-msg">${msg}</span><button class="adm-toast-x" onclick="this.parentElement.remove()">×</button>`;
  el.style.pointerEvents='all';st.appendChild(el);
  if(window.anime)anime({targets:el,opacity:[0,1],translateX:[28,0],duration:320,easing:'easeOutExpo'});
  setTimeout(()=>{if(window.anime)anime({targets:el,opacity:0,translateX:28,duration:280,easing:'easeInExpo',complete:()=>el?.remove()});else el?.remove();},dur);
}
window.toast=toast;
function openModal(html){
  const bk=document.getElementById('mbk'),box=document.getElementById('mbox');
  bk.classList.add('open');box.classList.add('open');box.innerHTML=html;
  if(window.anime)anime({targets:box.firstElementChild,scale:[.92,1],opacity:[0,1],duration:320,easing:'easeOutBack'});
}
function closeModal(){
  const bk=document.getElementById('mbk'),box=document.getElementById('mbox');
  bk.classList.remove('open');box.classList.remove('open');
}
window.openModal=openModal;window.closeModal=closeModal;
document.addEventListener('DOMContentLoaded',()=>{
  if(window.anime){
    const els=[...document.querySelectorAll('.mc,.ac')];
    if(els.length)anime({targets:els,opacity:[0,1],translateY:[16,0],duration:380,delay:anime.stagger(50),easing:'easeOutExpo'});
    const h=document.querySelector('.adm-ph h1');
    if(h)anime({targets:h,opacity:[0,1],translateX:[-14,0],duration:400,easing:'easeOutExpo'});
  }
});
<?=$js?>
</script>
</body></html><?php
} // end adminPage
endif;
?>
