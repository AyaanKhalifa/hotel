<?php
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/lang.php';
require_once __DIR__.'/includes/db.php';
$pageTitle='Sustainability — Royale Vista';
require __DIR__.'/header.php';
?>
<div style="padding-top:70px">
  <div style="height:55vh;min-height:400px;position:relative;display:flex;align-items:flex-end;overflow:hidden">
    <div style="position:absolute;inset:0;background:url('https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=1600&q=80') center/cover"></div>
    <div style="position:absolute;inset:0;background:linear-gradient(to bottom,rgba(0,0,0,.1),rgba(0,0,0,.65))"></div>
    <div style="position:relative;z-index:1;width:100%;padding:60px 0;color:#fff">
      <div class="container">
        <div class="lx-eyebrow" style="justify-content:flex-start;color:rgba(255,255,255,.5)">Our Commitment</div>
        <h1 style="font-family:var(--serif);font-size:clamp(36px,5vw,72px);font-weight:300;color:#fff;line-height:1.1">Luxury Without <em style="color:var(--gold)">Compromise</em></h1>
        <p style="color:rgba(255,255,255,.75);font-size:15px;max-width:500px;margin-top:14px;line-height:1.8">We believe the world's finest hotels should also be its most responsible. Our sustainability journey is as ambitious as our hospitality.</p>
      </div>
    </div>
  </div>
  <section class="section"><div class="container">
    <div style="text-align:center;margin-bottom:64px">
      <div class="lx-eyebrow lx-reveal" style="justify-content:center">Our 2030 Goals</div>
      <h2 class="lx-heading lx-reveal">Caring for the <em>World</em> We Share</h2>
    </div>
    <div class="grid-2" style="gap:1px;background:var(--bdr2);margin-bottom:64px">
      <?php foreach([
        ['🌱','Carbon Neutral by 2030','We are committed to achieving carbon neutrality across all properties by 2030, with verified offsets and renewable energy transitions underway.','40% CO₂ Reduced'],
        ['💧','Water Stewardship','Advanced water recycling systems and rainwater harvesting across all properties. Our Dubai property saves over 30 million litres of water annually.','30M Litres Saved'],
        ['♻️','Zero Waste to Landfill','Comprehensive waste reduction programme: composting, upcycling, and partnerships with local charities for food donation.','90% Diverted'],
        ['🐾','Biodiversity Protection','Native planting programmes, bee sanctuaries, and wildlife corridor partnerships around all rural and coastal properties.','8 Sanctuaries'],
      ] as [$ic,$t,$d,$stat]): ?>
      <div style="background:var(--card);padding:40px">
        <div style="font-size:40px;margin-bottom:16px"><?=$ic?></div>
        <h3 style="font-family:var(--serif);font-size:26px;font-weight:400;margin-bottom:10px"><?=$t?></h3>
        <p style="font-size:13.5px;color:var(--text2);line-height:1.75;margin-bottom:16px"><?=$d?></p>
        <div style="font-family:var(--cinzel);font-size:18px;color:var(--gold)"><?=$stat?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="background:linear-gradient(135deg,#1a3a1a,#0d200d);border-radius:var(--radius-lg);padding:56px;text-align:center;color:#fff">
      <div style="font-size:48px;margin-bottom:16px">🌿</div>
      <h2 style="font-family:var(--serif);font-size:36px;font-weight:300;margin-bottom:14px">Every Stay Plants a Tree</h2>
      <p style="color:rgba(255,255,255,.7);max-width:480px;margin:0 auto 28px;line-height:1.8">For every night a guest spends at any Royale Vista property, we plant a tree in partnership with global reforestation programmes.</p>
      <div style="font-family:var(--cinzel);font-size:24px;color:#22c55e">42,000+ Trees Planted</div>
    </div>
  </div></section>
</div>
<?php require __DIR__.'/footer.php'; ?>