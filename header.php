<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
if (!isset($pdo)) require_once __DIR__ . '/includes/db.php';

// Auto-checkout logic: release expired checked-in/confirmed bookings
try {
    $expired = $pdo->query("SELECT booking_ref FROM bookings WHERE status IN ('confirmed','checked_in') AND check_out <= CURDATE()")->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($expired)) {
        foreach ($expired as $exRef) {
            $pdo->prepare("UPDATE bookings SET status = 'checked_out' WHERE booking_ref = ?")->execute([$exRef]);
            $assignedIds = $pdo->prepare("SELECT room_id FROM booking_room_assignments WHERE booking_ref=?");
            $assignedIds->execute([$exRef]);
            $ids = $assignedIds->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($ids)) {
                $ph = implode(',', array_fill(0, count($ids), '?'));
                $pdo->prepare("UPDATE rooms SET status='available' WHERE id IN ($ph)")->execute($ids);
            }
        }
    }
} catch (Exception $e) {}

$B        = BASE;
$theme    = getTheme();
$lang_    = getUserLang();
$curr     = getUserCurrency();
$dir      = getLangDir();
$loggedIn = isLoggedIn();
$isAdm    = isAdmin();
$userName = htmlspecialchars($_SESSION['display_name'] ?? $_SESSION['username'] ?? '');
$currPage = basename($_SERVER['PHP_SELF'], '.php');
if (!isset($pageTitle)) $pageTitle = 'Royale Vista';
$flash    = getFlash();

$currRegions = [
  'Americas'            => ['USD','CAD','BRL','MXN'],
  'Europe'              => ['EUR','GBP','CHF','SEK','NOK','DKK','PLN','RUB','TRY'],
  'Asia Pacific'        => ['INR','JPY','CNY','KRW','SGD','HKD','MYR','THB','PKR','IDR'],
  'Middle East & Africa'=> ['AED','SAR','EGP','NGN','ZAR'],
];
?>
<!DOCTYPE html>
<html lang="<?= $lang_ ?>" dir="<?= $dir ?>" data-theme="<?= $theme ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="description" content="Royale Vista — Award-winning luxury hotel. Experience world-class hospitality, fine dining and bespoke service.">
<title><?= htmlspecialchars($pageTitle) ?> | Royale Vista</title>

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400;1,500&family=DM+Sans:wght@300;400;500;600&family=Cinzel:wght@400;500&display=swap" rel="stylesheet">

<!-- Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- Styles -->
<link rel="stylesheet" href="<?= $B ?>/css/luxury.css">
<link rel="stylesheet" href="<?= $B ?>/css/animations.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
<script src="<?= $B ?>/js/royale.js" defer></script>
<?php if (isset($pageCss)) echo $pageCss; ?>
</head>
<?php
// Pages that have a full-bleed hero (transparent nav)
$heroPages = ['index', 'rooms'];
$isHeroPage = in_array($currPage, $heroPages);
$bodyClass  = $isHeroPage ? '' : 'page-inner';
?>
<body class="<?= $bodyClass ?>">

<!-- Progress Bar -->
<div id="pg-bar"></div>

<!-- Flash -->
<?php if ($flash): ?>
<div class="lx-flash <?= $flash['type'] ?>" id="flashMsg">
  <i class="fas fa-<?= $flash['type']==='success'?'check-circle':($flash['type']==='error'?'exclamation-circle':'info-circle') ?>"></i>
  <?= htmlspecialchars($flash['msg']) ?>
  <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;font-size:18px;color:inherit;opacity:.7">×</button>
</div>
<script>setTimeout(()=>{const f=document.getElementById('flashMsg');if(f){f.style.opacity=0;f.style.transform='translateY(-8px)';setTimeout(()=>f?.remove(),400)}},4500)</script>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════
     LUXURY NAVIGATION
     ═══════════════════════════════════════════════════ -->
<nav class="lx-nav" id="lxNav">

  <!-- Logo -->
  <a href="<?= $B ?>/index.php" class="lx-logo">
    <div class="lx-logo-emblem">RV</div>
    <span class="lx-logo-name">Royale Vista</span>
  </a>

  <!-- Main Links -->
  <ul class="lx-links">
    <li><a href="<?= $B ?>/index.php"      class="<?= $currPage==='index'?'active':'' ?>"><?= t('home','Home') ?></a></li>
    <li><a href="<?= $B ?>/rooms.php"      class="<?= $currPage==='rooms'?'active':'' ?>"><?= t('rooms','Rooms & Suites') ?></a></li>
    <li><a href="<?= $B ?>/offers.php"     class="<?= $currPage==='offers'?'active':'' ?>"><?= t('offers','Offers') ?></a></li>
    <li><a href="<?= $B ?>/membership.php" class="<?= $currPage==='membership'?'active':'' ?>"><?= t('membership','Membership') ?></a></li>

    <!-- Discover Dropdown -->
    <li class="lx-drop">
      <button class="lx-drop-btn"><?= t('discover','Discover') ?> <span class="arr">▼</span></button>
      <ul class="lx-drop-panel">
        <li><a href="<?= $B ?>/dining.php"><i class="fas fa-utensils"></i> <?= t('dining','Dining') ?></a></li>
        <li><a href="<?= $B ?>/spa.php"><i class="fas fa-spa"></i> <?= t('spa','Spa & Wellness') ?></a></li>
        <li><a href="<?= $B ?>/gallery.php"><i class="fas fa-images"></i> <?= t('gallery','Gallery') ?></a></li>
        <li><a href="<?= $B ?>/services.php"><i class="fas fa-concierge-bell"></i> <?= t('services','Services') ?></a></li>
        <li><a href="<?= $B ?>/about.php"><i class="fas fa-hotel"></i> <?= t('about','About Us') ?></a></li>
        <li><a href="<?= $B ?>/contact.php"><i class="fas fa-envelope"></i> <?= t('contact','Contact') ?></a></li>
        <?php if ($isAdm): ?>
        <li style="border-top:1px solid var(--bdr2);margin-top:4px;padding-top:4px">
          <a href="<?= $B ?>/admin/index.php"><i class="fas fa-shield-alt"></i> Admin Panel</a>
        </li>
        <?php endif; ?>
      </ul>
    </li>
  </ul>

  <!-- Right Controls -->
  <div class="lx-controls">

    <!-- Language -->
    <div class="lx-ctrl-drop">
      <button class="lx-ctrl">
        <?= LANGUAGES[$lang_]['flag'] ?> <?= strtoupper($lang_) ?>
        <i class="fas fa-chevron-down" style="font-size:8px;opacity:.6"></i>
      </button>
      <div class="lx-ctrl-panel">
        <div style="padding:7px 12px 4px;font-size:9px;color:var(--muted);letter-spacing:2px;text-transform:uppercase">Select Language</div>
        <div class="lang-grid2">
          <?php foreach (LANGUAGES as $code => $info): ?>
          <a href="#" onclick="setLang('<?= $code ?>'); return false;" class="lang-opt <?= $lang_===$code?'active':'' ?>">
            <?= $info['flag'] ?> <?= $info['native'] ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Currency -->
    <div class="lx-ctrl-drop">
      <button class="lx-ctrl">
        <?= htmlspecialchars(CURRENCIES[$curr]['symbol']) ?> <?= $curr ?>
        <i class="fas fa-chevron-down" style="font-size:8px;opacity:.6"></i>
      </button>
      <div class="lx-ctrl-panel curr-panel">
        <?php foreach ($currRegions as $region => $codes): ?>
        <div class="curr-region"><?= $region ?></div>
        <div class="curr-grid2">
          <?php foreach ($codes as $code):
            if (!isset(CURRENCIES[$code])) continue; $curInf=CURRENCIES[$code]; ?>
          <a href="#" onclick="setCurr('<?= $code ?>'); return false;" class="curr-opt <?= $curr===$code?'active':'' ?>">
            <?= $curInf['flag'] ?>
            <span class="curr-code"><?= $code ?></span>
            <span style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($curInf['symbol']) ?></span>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Theme Toggle -->
    <button class="lx-ctrl lx-ctrl-icon" onclick="toggleTheme()" title="Toggle theme">
      <i class="fas fa-<?= $theme==='dark'?'sun':'moon' ?>" id="themeIco"></i>
    </button>

    <!-- Notifications -->
    <?php if ($loggedIn): ?>
    <div class="lx-ctrl-drop" id="notifDrop">
      <button class="lx-ctrl lx-ctrl-icon" onclick="loadNotifications()" style="position:relative">
        <i class="fas fa-bell" id="notifBell"></i>
        <span id="notifCount" style="display:none;position:absolute;top:1px;right:1px;width:15px;height:15px;background:var(--red);border-radius:50%;font-size:8px;font-weight:700;color:#fff;align-items:center;justify-content:center">0</span>
      </button>
      <div class="lx-ctrl-panel right-panel" id="notifPanel" style="min-width:320px;max-height:440px;overflow-y:auto;padding:0">
        <div style="padding:12px 16px;border-bottom:1px solid var(--bdr2);display:flex;align-items:center;justify-content:space-between;background:var(--card);position:sticky;top:0;z-index:1">
          <div style="font-family:var(--serif);font-size:15px;font-weight:600"><?= t('notifications','Notifications') ?></div>
          <div style="display:flex;gap:12px">
            <button onclick="markAllRead()" style="background:none;border:none;font-size:11px;color:var(--gold);cursor:pointer;font-family:var(--sans)"><?= t('mark_all_read','Mark all read') ?></button>
            <a href="<?= $B ?>/notifications.php" style="font-size:11px;color:var(--gold);font-family:var(--sans);font-weight:600;text-decoration:none"><?= t('view_all','View All') ?></a>
          </div>
        </div>
        <div id="notifList" style="padding:6px"></div>
      </div>
    </div>
    <?php endif; ?>

    <!-- User Menu -->
    <?php if ($loggedIn): ?>
    <div class="lx-ctrl-drop">
      <button class="lx-user-btn">
        <?= userAvatar($_SESSION['profile_img'] ?? null, $userName, 30) ?>
        <span class="lx-user-name"><?= htmlspecialchars(explode(' ',$userName?:'Account')[0]) ?></span>
        <i class="fas fa-chevron-down" style="font-size:8px;opacity:.5"></i>
      </button>
      <div class="lx-ctrl-panel right-panel lx-user-panel">
        <!-- Profile header -->
        <div class="lx-user-panel-hd">
          <?= userAvatar($_SESSION['profile_img'] ?? null, $userName, 42) ?>
          <div>
            <div class="lx-user-panel-name"><?= htmlspecialchars($userName) ?></div>
            <div class="lx-user-panel-role"><?= t('account','Account') ?></div>
          </div>
        </div>
        <div class="panel-divider"></div>
        <a href="<?= $B ?>/profile.php"        class="panel-link"><i class="fas fa-user-circle"></i> My Profile</a>
        <a href="<?= $B ?>/bookings.php"        class="panel-link"><i class="fas fa-calendar-check"></i> My Bookings</a>
        <a href="<?= $B ?>/wishlist.php"         class="panel-link"><i class="fas fa-heart"></i> Wishlist</a>
        <a href="<?= $B ?>/loyalty.php"          class="panel-link"><i class="fas fa-coins"></i> Loyalty Points</a>
        <a href="<?= $B ?>/my-gift-cards.php"    class="panel-link"><i class="fas fa-gift"></i> My Gift Cards</a>
        <?php if ($isAdm): ?>
        <div class="panel-divider"></div>
        <a href="<?= $B ?>/admin/index.php" class="panel-link"><i class="fas fa-shield-alt"></i> Admin Panel</a>
        <?php endif; ?>
        <div class="panel-divider"></div>
        <a href="<?= $B ?>/logout.php" class="panel-link panel-link-danger"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
      </div>
    </div>

    <?php else: ?>
    <a href="<?= $B ?>/login.php" class="lx-btn-login"><?= t('login','Login') ?></a>
    <a href="<?= $B ?>/login.php?tab=register" class="lx-btn-book"><?= t('register','Register') ?></a>
    <?php endif; ?>

    <!-- Hamburger -->
    <button class="lx-hamburger" id="lxHamburger" onclick="toggleMob()" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>

<!-- Mobile Nav Overlay -->
<div class="lx-mob-overlay" id="lxMobOverlay" onclick="closeMob()"></div>

<!-- Mobile Nav Drawer -->
<div class="lx-mob-nav" id="lxMobNav">
  <div class="mob-hd">
    <div style="display:flex;align-items:center;gap:10px">
      <div class="lx-logo-emblem" style="width:30px;height:30px;font-size:12px">RV</div>
      <span style="font-family:var(--cinzel);font-size:14px;color:var(--gold);letter-spacing:2px">ROYALE VISTA</span>
    </div>
    <button onclick="closeMob()" class="mob-close-btn">×</button>
  </div>

  <?php if ($loggedIn): ?>
  <div class="mob-user-card">
    <?= userAvatar($_SESSION['profile_img'] ?? null, $userName, 44) ?>
    <div>
      <div style="font-weight:600;font-size:14px"><?= htmlspecialchars($userName) ?></div>
      <div style="font-size:11px;color:var(--gold)"><?= t('account','Account') ?></div>
    </div>
  </div>
  <?php endif; ?>

  <div class="mob-section-label">Main</div>
  <a href="<?= $B ?>/index.php"      class="mob-a <?= $currPage==='index'?'active':'' ?>"><i class="fas fa-home"></i> Home</a>
  <a href="<?= $B ?>/rooms.php"      class="mob-a <?= $currPage==='rooms'?'active':'' ?>"><i class="fas fa-bed"></i> Rooms & Suites</a>
  <a href="<?= $B ?>/offers.php"     class="mob-a <?= $currPage==='offers'?'active':'' ?>"><i class="fas fa-tag"></i> Special Offers</a>
  <a href="<?= $B ?>/membership.php" class="mob-a <?= $currPage==='membership'?'active':'' ?>"><i class="fas fa-crown"></i> Membership</a>

  <div class="mob-section-label">Discover</div>
  <a href="<?= $B ?>/dining.php"    class="mob-a"><i class="fas fa-utensils"></i> Dining</a>
  <a href="<?= $B ?>/spa.php"       class="mob-a"><i class="fas fa-spa"></i> Spa & Wellness</a>
  <a href="<?= $B ?>/gallery.php"   class="mob-a"><i class="fas fa-images"></i> Gallery</a>
  <a href="<?= $B ?>/services.php"  class="mob-a"><i class="fas fa-concierge-bell"></i> Services</a>
  <a href="<?= $B ?>/about.php"     class="mob-a"><i class="fas fa-hotel"></i> About Us</a>
  <a href="<?= $B ?>/contact.php"   class="mob-a"><i class="fas fa-envelope"></i> Contact</a>

  <?php if ($loggedIn): ?>
  <div class="mob-section-label">My Account</div>
  <a href="<?= $B ?>/profile.php"       class="mob-a"><i class="fas fa-user-circle"></i> My Profile</a>
  <a href="<?= $B ?>/bookings.php"      class="mob-a"><i class="fas fa-calendar-check"></i> My Bookings</a>
  <a href="<?= $B ?>/loyalty.php"       class="mob-a"><i class="fas fa-coins"></i> Loyalty Points</a>
  <a href="<?= $B ?>/my-gift-cards.php" class="mob-a"><i class="fas fa-gift"></i> My Gift Cards</a>
  <?php if ($isAdm): ?>
  <a href="<?= $B ?>/admin/index.php"   class="mob-a"><i class="fas fa-shield-alt"></i> Admin Panel</a>
  <?php endif; ?>
  <a href="<?= $B ?>/logout.php" class="mob-a mob-a-danger"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
  <?php else: ?>
  <div class="mob-section-label">Account</div>
  <a href="<?= $B ?>/login.php"                class="mob-a"><i class="fas fa-sign-in-alt"></i> Login</a>
  <a href="<?= $B ?>/login.php?tab=register"   class="mob-a mob-a-gold"><i class="fas fa-user-plus"></i> Register</a>
  <?php endif; ?>
</div>

<!-- Toast Stack -->
<div class="toast-stack" id="toastStack"></div>

<!-- Back to top -->
<button id="back-top" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="Back to top">
  <i class="fas fa-arrow-up"></i>
</button>

<!-- Media Lightbox -->
<div class="media-lb" id="mediaLb" onclick="if(event.target===this)document.getElementById('mediaLb').classList.remove('open')">
  <button class="media-lb-close" onclick="document.getElementById('mediaLb').classList.remove('open')">×</button>
  <div id="mediaLbContent"></div>
</div>

<script>
window.BASE = '<?= $B ?>';

function toggleTheme(){
  const h=document.documentElement,n=h.getAttribute('data-theme')==='dark'?'light':'dark';
  h.setAttribute('data-theme',n);
  document.cookie='rv_theme='+n+';path=/;max-age=31536000;SameSite=Lax';
  const ico=document.getElementById('themeIco');
  if(ico) ico.className='fas fa-'+(n==='dark'?'sun':'moon');
}

const nav=document.getElementById('lxNav');
const pgBar=document.getElementById('pg-bar');

window.addEventListener('scroll',()=>{
  nav.classList.toggle('scrolled', scrollY > 50);
  if(pgBar){const p=(scrollY/(document.body.scrollHeight-innerHeight))*100;pgBar.style.width=p+'%';}
  const bt=document.getElementById('back-top');
  if(bt) bt.classList.toggle('vis', scrollY > 400);
},{passive:true});

// Mobile drawer
function toggleMob(){
  document.getElementById('lxMobNav').classList.toggle('open');
  document.getElementById('lxMobOverlay').classList.toggle('open');
  document.getElementById('lxHamburger').classList.toggle('open');
}
function closeMob(){
  document.getElementById('lxMobNav').classList.remove('open');
  document.getElementById('lxMobOverlay').classList.remove('open');
  document.getElementById('lxHamburger').classList.remove('open');
}

// Toast
function toast(msg,type='info',dur=3500){
  const s=document.getElementById('toastStack');
  const el=document.createElement('div');
  el.className='toast '+type;
  el.innerHTML=`<span style="font-size:16px">${{success:'✓',error:'✗',info:'◆',warning:'⚠'}[type]||'◆'}</span><span style="flex:1">${msg}</span><button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;font-size:17px;color:inherit;padding:0 0 0 8px;opacity:.7">×</button>`;
  s.appendChild(el);
  setTimeout(()=>el?.remove(),dur);
}
window.toast=toast;

// Close dropdowns on outside click
document.addEventListener('click',e=>{
  if(!e.target.closest('.lx-ctrl-drop')&&!e.target.closest('.lx-drop'))
    document.querySelectorAll('.lx-ctrl-panel,.lx-drop-panel').forEach(p=>{p.style.opacity='0';p.style.visibility='hidden';});
});

function setLang(code){
  const ret = encodeURIComponent(window.location.href);
  try { localStorage.setItem('rv_lang', code); } catch(e) {}
  window.location.href = '<?= BASE ?>/set-language.php?lang=' + encodeURIComponent(code) + '&ret=' + ret;
}
function setCurr(code){fetch('<?= BASE ?>/set-currency.php?currency='+code).then(()=>window.location.reload());}

window._isLoggedIn = <?= isLoggedIn()?'true':'false' ?>;
window.RV={
  currency:'<?= $curr ?>',
  symbol:'<?= addslashes(CURRENCIES[$curr]['symbol']) ?>',
  rate:<?= CURRENCIES[$curr]['rate'] ?>,
  dec:<?= CURRENCIES[$curr]['dec'] ?>,
  base:'<?= $B ?>',
  fmt:function(usd){const v=parseFloat(usd)*this.rate;return this.symbol+(this.dec===0?Math.round(v).toLocaleString():v.toFixed(this.dec));}
};
window.fmtPrice=v=>RV.fmt(v);

// Media lightbox
function openMedia(src,type){
  const lb=document.getElementById('mediaLb');
  const content=document.getElementById('mediaLbContent');
  content.innerHTML=type==='video'
    ?`<video src="${src}" controls autoplay style="max-width:90vw;max-height:88vh;border-radius:8px"></video>`
    :`<img src="${src}" style="max-width:90vw;max-height:88vh;border-radius:8px;object-fit:contain">`;
  lb.classList.add('open');
}

// Notifications
async function loadNotifications(){
  try{
    const res=await fetch((window.RV?.base||'')+'/api/notifications.php?action=get&limit=15');
    const data=await res.json();
    const list=document.getElementById('notifList');
    const badge=document.getElementById('notifCount');
    if(!list) return;
    if(data.unread>0){
      badge.textContent=data.unread>9?'9+':data.unread;
      badge.style.display='flex';
      document.getElementById('notifBell').style.color='var(--gold)';
    } else {
      badge.style.display='none';
    }
    if(!data.notifications?.length){
      list.innerHTML='<div style="padding:28px;text-align:center;color:var(--muted);font-size:13px"><i class="fas fa-bell-slash" style="display:block;font-size:26px;margin-bottom:10px;opacity:.4"></i>No notifications</div>';
      return;
    }
    list.innerHTML=data.notifications.map(n=>`
      <div style="padding:11px 14px;border-bottom:1px solid var(--bdr2);display:flex;align-items:flex-start;gap:10px;background:${!n.is_read?'var(--gold-dim)':'transparent'};cursor:pointer;transition:background .2s" onclick="markRead(${n.id},this)${n.link?`;window.location='${n.link}'`:''}">
        <div style="width:36px;height:36px;border-radius:50%;background:${{booking:'rgba(212,175,55,.15)',cancellation:'rgba(239,68,68,.12)',payment:'rgba(34,197,94,.12)',system:'rgba(59,130,246,.12)',review:'rgba(245,158,11,.12)'}[n.type]||'var(--card2)'};display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0">
          ${{booking:'📅',cancellation:'❌',payment:'✅',system:'🔔',review:'⭐',loyalty:'🪙',offer:'🎁'}[n.type]||'🔔'}
        </div>
        <div style="flex:1;min-width:0">
          <div style="font-size:13px;font-weight:${n.is_read?'400':'600'};margin-bottom:2px;color:var(--text)">${n.title}</div>
          <div style="font-size:12px;color:var(--muted);line-height:1.4">${n.message}</div>
          <div style="font-size:10px;color:var(--muted);margin-top:4px;opacity:.7">${new Date(n.created_at).toLocaleDateString('en-GB',{day:'numeric',month:'short',hour:'2-digit',minute:'2-digit'})}</div>
        </div>
      </div>`).join('');
  }catch(e){}
}
async function markRead(id,el){
  el.style.background='transparent';
  const fd=new FormData(); fd.append('action','mark_read'); fd.append('id',id);
  await fetch((window.RV?.base||'')+'/api/notifications.php',{method:'POST',body:fd});
}
async function markAllRead(){
  const fd=new FormData(); fd.append('action','mark_all_read');
  await fetch((window.RV?.base||'')+'/api/notifications.php',{method:'POST',body:fd});
  document.getElementById('notifCount').style.display='none';
  document.getElementById('notifBell').style.color='';
  document.querySelectorAll('#notifList > div').forEach(el=>el.style.background='transparent');
}
<?php if ($loggedIn): ?> setTimeout(loadNotifications,1200); <?php endif; ?>

document.addEventListener('keydown',e=>{
  if(e.key==='Escape'){
    document.querySelectorAll('.media-lb,.lx-modal-back,.avail-modal').forEach(el=>el.classList.remove('open'));
    closeMob();
  }
});
</script>
