<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'FAQ — Royale Vista';
require __DIR__ . '/header.php';
$faqs=[['What are the check-in and check-out times?','Standard check-in is from 2:00 PM and check-out is by 12:00 PM (noon). Gold members may check in from 12 PM, and Platinum members enjoy guaranteed late check-out until 4:00 PM. Early arrivals are subject to availability.'],['What currencies and payment methods do you accept?','We accept 30 world currencies including USD, EUR, GBP, INR, AED, JPY, CNY, and many more. Payment options include credit/debit card, UPI, and Pay at Hotel.'],['How do Loyalty Points work?','Earn 10 points for every $1 spent. Redeem 100 points for $1 off your next booking. Tier multipliers apply: Silver 1.2x, Gold 1.5x, Platinum 2x points.'],['Can I modify or cancel my booking?','Silver members get free cancellation up to 24h before check-in. Gold members 48h, Platinum any time. Standard bookings can be modified up to 48h prior.'],['Is breakfast included?','Breakfast is included with Exclusive Room, Family Room, and Presidential Suite bookings. Platinum members receive complimentary breakfast regardless of room type.'],['Do you offer airport transfers?','Complimentary luxury airport transfer is provided for Presidential Suite guests and all Platinum members. Premium transfer service is available for other rooms at an additional charge.'],['What loyalty membership tiers are there?','Four tiers: Bronze (0 pts), Silver (2,000+ pts), Gold (5,000+ pts), Platinum (10,000+ pts). Each tier offers increasing discounts, perks, and point multipliers.'],['Can I use offer codes with membership discounts?','Yes! Offer codes and membership discounts stack. For example, a Gold member using WELCOME10 gets 15% membership + 10% offer code discount applied to their booking.']];
?>
<style>.faq-item{border:1px solid var(--bdr2);border-radius:var(--radius);overflow:hidden;margin-bottom:10px;transition:border-color var(--t)}.faq-item.open{border-color:var(--gold)}.faq-q{padding:18px 22px;cursor:pointer;display:flex;justify-content:space-between;align-items:center;font-size:15px;font-weight:500;background:var(--card);transition:background var(--t)}.faq-q:hover{background:var(--gold-dim)}.faq-a{display:none;padding:16px 22px;font-size:14px;color:var(--text2);line-height:1.75;background:var(--card);border-top:1px solid var(--bdr2)}.faq-icon{color:var(--gold);transition:transform var(--t);flex-shrink:0}</style>
<div style="padding-top:90px;min-height:80vh">
  <div style="background:linear-gradient(135deg,var(--bg2),var(--bg));padding:60px 0 40px;border-bottom:1px solid var(--bdr2);margin-bottom:48px"><div class="container">
    <div class="section-label">Need Help?</div>
    <h1 style="font-family:var(--serif);font-size:clamp(28px,4vw,48px);font-weight:400;margin-bottom:10px">Frequently Asked Questions</h1>
    <p style="color:var(--text2);font-size:15px">Can't find your answer? <a href="<?= $B ?>/contact.php" style="color:var(--gold)">Contact us</a> or <a href="#" onclick="Habibi.toggle();return false" style="color:var(--gold)">chat with Habibi</a>.</p>
  </div></div>
  <div class="container" style="max-width:780px;padding-bottom:60px">
    <?php foreach($faqs as $i=>[$q,$a]): ?>
    <div class="faq-item" onclick="toggleFaq(this)">
      <div class="faq-q"><?= htmlspecialchars($q) ?><i class="fas fa-plus faq-icon"></i></div>
      <div class="faq-a"><?= htmlspecialchars($a) ?></div>
    </div>
    <?php endforeach; ?>
    <div style="text-align:center;margin-top:40px;padding:28px;background:var(--card);border-radius:var(--radius-lg);border:1px solid var(--bdr2)">
      <div style="font-size:24px;margin-bottom:10px">💬</div>
      <div style="font-family:var(--serif);font-size:20px;margin-bottom:8px">Still have questions?</div>
      <div style="color:var(--text2);font-size:14px;margin-bottom:18px">Our AI concierge Habibi is available 24/7 to help.</div>
      <button onclick="Habibi.toggle()" class="btn btn-gold">Chat with Habibi</button>
    </div>
  </div>
</div>
<script>function toggleFaq(el){const a=el.querySelector('.faq-a'),i=el.querySelector('.faq-icon'),o=el.classList.contains('open');document.querySelectorAll('.faq-item').forEach(f=>{f.classList.remove('open');f.querySelector('.faq-a').style.display='none';f.querySelector('.faq-icon').style.transform='';f.querySelector('.faq-icon').className='fas fa-plus faq-icon'});if(!o){el.classList.add('open');a.style.display='block';i.style.transform='rotate(45deg)';i.className='fas fa-times faq-icon'}}</script>
<?php require __DIR__ . '/footer.php'; ?>
