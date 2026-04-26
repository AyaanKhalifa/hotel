<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = "Global Partners";
$pageCss = "
<style>
.partner-hero { background: linear-gradient(rgba(0,0,0,0.6),rgba(0,0,0,0.8)), url('https://images.unsplash.com/photo-1554200876-56c2f25224fa?w=1400&q=80') center/cover; padding: 120px 20px; text-align: center; color: #fff; }
.partner-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; max-width: 1100px; margin: 0 auto; }
.partner-logo { background: var(--card); border: 1px solid var(--bdr); border-radius: 8px; padding: 40px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 180px; transition: all 0.4s ease; filter: grayscale(100%) opacity(0.7); }
.partner-logo:hover { filter: grayscale(0%) opacity(1); border-color: var(--gold); transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
.partner-name { margin-top: 15px; font-family: var(--sans); font-size: 14px; font-weight: 600; letter-spacing: 1px; }
</style>
";
require __DIR__ . '/header.php';
?>

<div class="partner-hero">
    <div class="section-label"><?= t('our_network', 'Our Network') ?></div>
    <h1 style="font-family:var(--cinzel);font-size:3rem;margin:15px 0"><?= t('global_partners', 'Global Partners') ?></h1>
    <p style="max-width:600px;margin:0 auto;line-height:1.6;opacity:.9"><?= t('partners_desc', 'Royale Vista collaborates with the world\'s finest brands across aviation, luxury goods, and bespoke experiences to offer our guests unparalleled privileges.') ?></p>
</div>

<div class="container" style="padding-top:80px;padding-bottom:100px">
    <div class="partner-grid">
        <div class="partner-logo lx-reveal" style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.9)), url('https://images.unsplash.com/photo-1542296332-2e4473faf563?w=500&q=80') center/cover">
            <i class="fas fa-plane-departure" style="font-size:32px;color:rgba(255,255,255,.5);margin-bottom:10px"></i>
            <div class="partner-name" style="color:#fff;font-size:16px">Habibi Airways</div>
        </div>
        <div class="partner-logo lx-reveal" style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.9)), url('https://images.unsplash.com/photo-1582216507421-eb37c569a912?w=500&q=80') center/cover">
            <i class="fas fa-gem" style="font-size:32px;color:rgba(255,255,255,.5);margin-bottom:10px"></i>
            <div class="partner-name" style="color:#fff;font-size:16px">Cartier</div>
        </div>
        <div class="partner-logo lx-reveal" style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.9)), url('https://images.unsplash.com/photo-1631478546506-69a42db46d5c?w=500&q=80') center/cover">
            <i class="fas fa-car" style="font-size:32px;color:rgba(255,255,255,.5);margin-bottom:10px"></i>
            <div class="partner-name" style="color:#fff;font-size:16px">Rolls-Royce Motor Cars</div>
        </div>
        <div class="partner-logo lx-reveal" style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.9)), url('https://images.unsplash.com/photo-1585553616435-2dc0a54e271d?w=500&q=80') center/cover">
            <i class="fas fa-wine-glass" style="font-size:32px;color:rgba(255,255,255,.5);margin-bottom:10px"></i>
            <div class="partner-name" style="color:#fff;font-size:16px">Moët & Chandon</div>
        </div>
        <div class="partner-logo lx-reveal" style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.9)), url('https://images.unsplash.com/photo-1599839619722-39751411ea63?w=500&q=80') center/cover">
            <i class="fas fa-ship" style="font-size:32px;color:rgba(255,255,255,.5);margin-bottom:10px"></i>
            <div class="partner-name" style="color:#fff;font-size:16px">Orient Express</div>
        </div>
        <div class="partner-logo lx-reveal" style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.9)), url('https://images.unsplash.com/photo-1556740749-887f6717d7e4?w=500&q=80') center/cover">
            <i class="fas fa-credit-card" style="font-size:32px;color:rgba(255,255,255,.5);margin-bottom:10px"></i>
            <div class="partner-name" style="color:#fff;font-size:16px">Amex Centurion</div>
        </div>
    </div>
    
    <div style="margin-top:80px;text-align:center;background:var(--card);padding:50px;border-radius:12px;border:1px solid var(--bdr)">
        <h2 style="font-family:var(--serif);font-size:24px;margin-bottom:15px"><?= t('become_partner', 'Become a Partner') ?></h2>
        <p style="color:var(--muted);max-width:500px;margin:0 auto 25px auto"><?= t('partner_cta', 'Are you a luxury brand looking to collaborate with Royale Vista? Reach out to our corporate partnerships team.') ?></p>
        <a href="<?= BASE ?>/contact.php" class="btn-gold"><?= t('contact_partnerships', 'Contact Partnerships') ?></a>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
