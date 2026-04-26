<?php
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/lang.php';
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/service_requests.php';
$pageTitle='Concierge — Royale Vista';
$success=false;$error='';
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['submit_concierge'])){
    $category=clean($_POST['category']??'other');$request=clean($_POST['request']??'');
    $date=clean($_POST['preferred_date']??'');$time=clean($_POST['preferred_time']??'');
    if(!$request){$error='Please describe your request.';}
    else{
        $ref='CR'.date('Y').strtoupper(substr(bin2hex(random_bytes(3)),0,5));
        $pdo->prepare("INSERT INTO concierge_requests (ref,user_id,category,request,preferred_date,preferred_time) VALUES (?,?,?,?,?,?)")->execute([$ref,$_SESSION['user_id']??null,$category,$request,$date?:null,$time?:null]);
        pushUserNotification(
            $pdo,
            $_SESSION['user_id'] ?? null,
            'system',
            "Concierge request received — {$ref}",
            "Your concierge request is submitted and awaiting team action.",
            BASE . '/notifications.php'
        );
        $success=true;
    }
}
$myRequests=[];
if(isLoggedIn()){$rq=$pdo->prepare("SELECT * FROM concierge_requests WHERE user_id=? ORDER BY created_at DESC LIMIT 10");$rq->execute([$_SESSION['user_id']]);$myRequests=$rq->fetchAll();}
require __DIR__.'/header.php';
?>
<style>
.srv-card{background:var(--card);padding:32px;border:1px solid var(--bdr2);border-radius:var(--radius-lg);cursor:pointer;transition:all .2s;border-bottom:3px solid transparent}
.srv-card:hover,.srv-card.selected{border-color:var(--gold);background:var(--gold-dim)}
.req-status{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:3px 10px;border-radius:12px}
.rs-pending{background:var(--amber-bg);color:var(--amber)}.rs-in_progress{background:var(--blue-bg);color:var(--blue)}.rs-completed{background:var(--green-bg);color:var(--green)}.rs-cancelled{background:var(--red-bg);color:var(--red)}
</style>
<div style="padding-top:70px">
  <div style="height:55vh;min-height:400px;position:relative;display:flex;align-items:flex-end;overflow:hidden">
    <div style="position:absolute;inset:0;background:url('https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1600&q=80') center/cover"></div>
    <div style="position:absolute;inset:0;background:linear-gradient(to bottom,rgba(0,0,0,.1),rgba(0,0,0,.7))"></div>
    <div style="position:relative;z-index:1;width:100%;padding:60px 0;color:#fff"><div class="container">
      <div class="lx-eyebrow" style="justify-content:flex-start;color:rgba(255,255,255,.5)">At Your Service</div>
      <h1 style="font-family:var(--serif);font-size:clamp(36px,5vw,72px);font-weight:300;color:#fff;line-height:1.1">Concierge <em style="color:var(--gold)">Services</em></h1>
      <p style="color:rgba(255,255,255,.75);font-size:15px;max-width:480px;margin-top:14px;line-height:1.8">Your personal guide to the extraordinary. Available 24 hours a day, 365 days a year.</p>
    </div></div>
  </div>
  <section class="section"><div class="container">
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:56px" id="srvGrid">
      <?php foreach([['transport','✈️','Transport & Travel'],['restaurant','🍽','Dining Reservations'],['activity','🎭','Experiences'],['shopping','🛍','Personal Shopping'],['medical','🏥','Medical Support'],['other','💎','Special Requests']] as [$cat,$icon,$title]): ?>
      <div class="srv-card lx-reveal" onclick="selectSrv('<?=$cat?>')">
        <div style="font-size:32px;margin-bottom:12px"><?=$icon?></div>
        <div style="font-family:var(--serif);font-size:20px;font-weight:400"><?=$title?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="grid-2" style="gap:48px;align-items:start">
      <div>
        <div class="lx-eyebrow" style="justify-content:flex-start">Submit a Request</div>
        <h2 style="font-family:var(--serif);font-size:clamp(24px,3vw,38px);font-weight:300;margin-bottom:22px">How Can We <em style="color:var(--gold)">Help?</em></h2>
        <?php if($success): ?>
        <div style="background:var(--card);border:1px solid var(--green);border-radius:var(--radius-lg);padding:32px;text-align:center">
          <div style="font-size:48px;margin-bottom:14px">✅</div>
          <h3 style="font-family:var(--serif);font-size:24px;margin-bottom:8px">Request Received</h3>
          <p style="color:var(--text2);font-size:14px">Your concierge will confirm within 2 hours. Urgent? Call +971 800 ROYALE.</p>
          <a href="<?=$B?>/concierge.php" class="btn btn-gold" style="margin-top:18px">New Request</a>
        </div>
        <?php else: ?>
        <?php if($error): ?><div class="alert alert-error"><?=htmlspecialchars($error)?></div><?php endif; ?>
        <div class="lx-card"><div class="lx-card-bd">
          <form method="POST">
            <input type="hidden" name="submit_concierge" value="1">
            <div class="form-group"><label class="form-label">Category</label>
              <select class="form-control" name="category" id="catSel">
                <option value="transport">✈️ Transport & Travel</option>
                <option value="restaurant">🍽 Dining Reservations</option>
                <option value="activity">🎭 Experiences & Activities</option>
                <option value="shopping">🛍 Personal Shopping</option>
                <option value="medical">🏥 Medical Support</option>
                <option value="other">💎 Special Request</option>
              </select>
            </div>
            <div class="form-row">
              <div class="form-group"><label class="form-label">Preferred Date</label><input class="form-control" type="date" name="preferred_date" min="<?=date('Y-m-d')?>"></div>
              <div class="form-group"><label class="form-label">Preferred Time</label>
                <select class="form-control" name="preferred_time"><option value="">Anytime</option><?php foreach(['08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00','22:00'] as $t): ?><option><?=$t?></option><?php endforeach; ?></select>
              </div>
            </div>
            <div class="form-group"><label class="form-label">Your Request *</label>
              <textarea class="form-control" name="request" rows="5" required placeholder="Describe your request in detail — the more we know, the better we can help…"></textarea>
            </div>
            <button type="submit" class="btn btn-gold btn-block btn-lg"><i class="fas fa-concierge-bell"></i> Submit Request</button>
            <div style="text-align:center;margin-top:12px;font-size:12.5px;color:var(--muted)">Urgent? Call <a href="tel:+97180012345" style="color:var(--gold)">+971 800 ROYALE</a></div>
          </form>
        </div></div>
        <?php endif; ?>
      </div>
      <div>
        <div class="lx-eyebrow" style="justify-content:flex-start;margin-bottom:14px">Our Promise</div>
        <?php foreach([['⚡','2 Hours','Standard requests confirmed within 2 hours'],['🚨','30 Minutes','Urgent matters addressed within 30 minutes'],['🌙','24/7','Our desk never closes'],['🌍','Global','Any city, any country']] as [$ic,$t,$d]): ?>
        <div style="display:flex;gap:14px;padding:16px;background:var(--card2);border-radius:var(--radius);margin-bottom:12px">
          <div style="font-size:24px"><?=$ic?></div>
          <div><div style="font-family:var(--serif);font-size:17px;margin-bottom:3px"><?=$t?></div><div style="font-size:13px;color:var(--text2)"><?=$d?></div></div>
        </div>
        <?php endforeach; ?>
        <?php if(!empty($myRequests)): ?>
        <div style="margin-top:24px">
          <div class="lx-eyebrow" style="justify-content:flex-start;margin-bottom:12px">Your Requests</div>
          <?php foreach($myRequests as $rq): ?>
          <div style="padding:13px;background:var(--card);border:1px solid var(--bdr2);border-radius:var(--radius);margin-bottom:8px">
            <div style="display:flex;justify-content:space-between;margin-bottom:5px">
              <span style="font-size:11px;color:var(--gold);font-family:var(--cinzel)"><?=htmlspecialchars($rq['ref'])?></span>
              <span class="req-status rs-<?=$rq['status']?>"><?=ucfirst(str_replace('_',' ',$rq['status']))?></span>
            </div>
            <div style="font-size:13px;color:var(--text2)"><?=htmlspecialchars(mb_strimwidth($rq['request'],0,70,'…'))?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div></section>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
<script>
anime({targets:'.srv-card',opacity:[0,1],translateY:[20,0],duration:400,easing:'easeOutCubic',delay:anime.stagger(60)});
function selectSrv(cat){
  document.querySelectorAll('.srv-card').forEach(c=>c.classList.remove('selected'));
  event.currentTarget.classList.add('selected');
  const s=document.getElementById('catSel');if(s){s.value=cat;}
  document.querySelector('.lx-card')?.scrollIntoView({behavior:'smooth',block:'center'});
}
</script>
<?php require __DIR__.'/footer.php'; ?>
