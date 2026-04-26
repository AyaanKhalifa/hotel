<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/lang.php';
require_once __DIR__.'/includes/db.php';
$pageTitle = t('contact').' — Royale Vista';
$success = false;
if($_SERVER['REQUEST_METHOD']==='POST'){
    $name=clean($_POST['name']??'');$email=clean($_POST['email']??'');
    $subject=clean($_POST['subject']??'');$message=clean($_POST['message']??'');
    $phone=clean($_POST['phone']??'');
    if($name&&$email&&$subject&&$message&&filter_var($email,FILTER_VALIDATE_EMAIL)){
        $pdo->prepare("INSERT INTO contact_messages (name,email,phone,subject,message) VALUES (?,?,?,?,?)")->execute([$name,$email,$phone,$subject,$message]);
        $success=true;
    }
}
require __DIR__.'/header.php';
?>
<div style="padding-top:90px">
<section class="section">
<div class="container">
<div class="grid-2" style="align-items:start;gap:48px">
  <div>
    <div class="section-label">Get in Touch</div>
    <h1 style="font-family:var(--serif);font-size:clamp(28px,4vw,44px);font-weight:400;margin-bottom:14px"><?= t('contact_us','Contact Us') ?></h1>
    <p style="color:var(--text2);line-height:1.75;margin-bottom:32px">Our team is here to help you plan the perfect stay. Reach out anytime.</p>
    <?php foreach([['fas fa-map-marker-alt','Address','1 Park Avenue, Midtown Manhattan, New York, NY 10016, USA'],['fas fa-phone','Phone','+1 (212) 555‑0199'],['fas fa-envelope','Email','stay@royalevista.com'],['fas fa-clock','Front Desk','Open 24 hours, 7 days a week']] as [$icon,$label,$val]): ?>
    <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:20px">
      <div style="width:42px;height:42px;border-radius:10px;background:var(--gold-dim);display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="<?=$icon?>" style="color:var(--gold)"></i></div>
      <div><div style="font-size:11px;color:var(--muted);letter-spacing:1.5px;text-transform:uppercase;margin-bottom:3px"><?=$label?></div><div style="font-size:14px"><?=$val?></div></div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title"><?= t('send_message') ?></div></div>
    <div class="card-body">
      <?php if($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= t('message_sent') ?></div><?php endif; ?>
      <form method="POST">
        <div class="form-row">
          <div class="form-group"><label class="form-label"><?= t('name') ?> *</label><input class="form-control" name="name" required placeholder="Your name"></div>
          <div class="form-group"><label class="form-label"><?= t('email') ?> *</label><input class="form-control" type="email" name="email" required placeholder="you@email.com"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label"><?= t('phone') ?></label><input class="form-control" name="phone" placeholder="+1 555 000 0000"></div>
          <div class="form-group"><label class="form-label"><?= t('subject') ?> *</label>
            <select class="form-control" name="subject" required>
              <option value="">Select subject</option>
              <option>Reservation Enquiry</option><option>Booking Modification</option><option>Special Occasion</option><option>Membership</option><option>Feedback</option><option>Other</option>
            </select>
          </div>
        </div>
        <div class="form-group"><label class="form-label"><?= t('message') ?> *</label><textarea class="form-control" name="message" rows="4" required placeholder="How can we help you?"></textarea></div>
        <button type="submit" class="btn btn-gold btn-block"><i class="fas fa-paper-plane"></i> <?= t('send_message') ?></button>
      </form>
    </div>
  </div>
</div>
</div>
</section>
</div>
<?php require __DIR__.'/footer.php'; ?>
