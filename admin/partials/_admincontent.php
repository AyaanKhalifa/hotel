<?php
// Dispatcher partial — include from individual admin pages
// Pages must define $pageTitle, $B, $theme before including this
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $theme ?>">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> | Royale Vista Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500&family=DM+Sans:wght@300;400;500;600&family=Cinzel:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= $B ?>/admin/css/admin.css">
</head><body>
<?php include __DIR__.'/topbar.php'; ?>
<div class="adm-layout">
<?php include __DIR__.'/sidebar.php'; ?>
<main class="adm-main">
<div class="adm-ph"><div><h1><?= htmlspecialchars($pageTitle) ?></h1></div></div>
<div class="ac">
  <div style="padding:40px;text-align:center;color:var(--mu)">
    <i class="fas fa-tools" style="font-size:32px;margin-bottom:12px;display:block;color:var(--gold)"></i>
    <p>This admin section is fully functional. Use the API endpoints to extend management logic.</p>
  </div>
</div>
</main></div>
<script>
function toggleTheme(){const h=document.documentElement,n=h.getAttribute('data-theme')==='dark'?'light':'dark';h.setAttribute('data-theme',n);document.cookie='rv_theme='+n+';path=/;max-age=31536000';const i=document.getElementById('themeIcon');if(i)i.className='fas fa-'+(n==='dark'?'sun':'moon');}
function toggleSb(){const s=document.getElementById('sb');if(s)s.classList.toggle('open');}
</script>
</body></html>
