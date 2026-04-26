<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$pageTitle = 'My Profile — Royale Vista';
$uid = (int)$_SESSION['user_id'];
$B   = BASE;

// Fetch user
$userQ = $pdo->prepare("SELECT * FROM users WHERE id=?");
$userQ->execute([$uid]);
$user = $userQ->fetch();

// Fetch membership
$memQ = $pdo->prepare("SELECT um.*,m.name as memb_name,m.discount_pct,m.icon,m.gradient_from,m.gradient_to FROM user_memberships um JOIN memberships m ON um.membership_id=m.id WHERE um.user_id=? AND um.status='active' ORDER BY m.discount_pct DESC LIMIT 1");
$memQ->execute([$uid]);
$membership = $memQ->fetch();

// Fetch loyalty
$loyQ = $pdo->prepare("SELECT total_points FROM loyalty_points WHERE user_id=?");
$loyQ->execute([$uid]);
$loyPts = (int)($loyQ->fetchColumn() ?? 0);

// Fetch stats
$statQ = $pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(final_usd),0) as spent FROM bookings WHERE user_id=? AND status IN ('confirmed','checked_in','checked_out')");
$statQ->execute([$uid]);
$uStats = $statQ->fetch();

$errors = []; $successMsg = '';

// Handle Avatar Upload
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['do_avatar'])) {
    if (!empty($_FILES['avatar']['name'])) {
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
            $errors[] = 'Avatar must be JPG, PNG or WebP.';
        } elseif ($_FILES['avatar']['size'] > 4*1024*1024) {
            $errors[] = 'Avatar must be under 4MB.';
        } else {
            $newName   = 'avatar_'.$uid.'_'.time().'.'.$ext;
            $uploadDir = __DIR__.'/uploads/avatars/';
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir.$newName)) {
                $old = $user['profile_img'];
                if ($old && file_exists($uploadDir.$old)) @unlink($uploadDir.$old);
                $pdo->prepare("UPDATE users SET profile_img=?, updated_at=NOW() WHERE id=?")->execute([$newName, $uid]);
                $_SESSION['profile_img'] = $newName;
                flash('Profile photo updated!', 'success');
                header('Location: '.$_SERVER['PHP_SELF']); exit;
            } else {
                $errors[] = 'Failed to upload photo.';
            }
        }
    }
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['do_profile'])) {
    $name    = clean($_POST['name']    ?? '');
    $phone   = clean($_POST['phone']   ?? '');
    $country = clean($_POST['country'] ?? '');
    $lang_   = clean($_POST['language']?? 'en');
    $curr    = clean($_POST['currency']?? 'USD');
    $address = clean($_POST['address'] ?? '');

    if (!$name) { $errors[] = 'Name is required.'; }
    if (!isset(LANGUAGES[$lang_])) $lang_ = 'en';
    if (!isset(CURRENCIES[$curr])) $curr  = 'USD';

    if (empty($errors)) {
        $pdo->prepare("UPDATE users SET name=?,phone=?,country=?,language=?,currency=?,address=?,updated_at=NOW() WHERE id=?")
            ->execute([$name,$phone,$country,$lang_,$curr,$address,$uid]);
        $_SESSION['username'] = $_SESSION['display_name'] = $name;
        $_SESSION['language'] = $lang_;
        $_SESSION['currency'] = $curr;
        flash('Profile updated successfully!', 'success');
        header('Location: '.$_SERVER['PHP_SELF']); exit;
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['do_password'])) {
    $old  = $_POST['old_pass']  ?? '';
    $new  = $_POST['new_pass']  ?? '';
    $conf = $_POST['conf_pass'] ?? '';
    if (!password_verify($old, $user['password'])) {
        $errors[] = 'Current password is incorrect.';
    } elseif (strlen($new)<6) {
        $errors[] = 'New password must be at least 6 characters.';
    } elseif ($new !== $conf) {
        $errors[] = 'Passwords do not match.';
    } else {
        $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new,PASSWORD_DEFAULT),$uid]);
        flash('Password changed successfully!', 'success');
        header('Location: '.$_SERVER['PHP_SELF']); exit;
    }
}

require __DIR__ . '/header.php';
?>
<style>
/* ── Profile Page Layout ── */
.profile-wrap { min-height: calc(100vh - 68px); padding: 88px 0 80px; background: var(--bg2); }
.profile-grid { display: grid; grid-template-columns: 280px 1fr; gap: 28px; align-items: start; }
@media(max-width:900px) { .profile-grid { grid-template-columns: 1fr; } }

/* Sidebar */
.profile-sidebar { position: sticky; top: 84px; display: flex; flex-direction: column; gap: 16px; }

/* Profile card */
.profile-id-card {
  background: var(--card); border: 1px solid var(--bdr2);
  border-radius: 20px; overflow: hidden;
}
.profile-id-top {
  background: linear-gradient(135deg, var(--gold-dk) 0%, var(--gold) 50%, #e8c97a 100%);
  padding: 28px 24px 48px; text-align: center; position: relative;
}
.profile-id-top::after {
  content: ''; position: absolute; bottom: -1px; left: 0; right: 0; height: 24px;
  background: var(--card); border-radius: 50% 50% 0 0 / 100% 100% 0 0;
}
.profile-avatar-wrap {
  position: relative; width: 88px; height: 88px; margin: 0 auto;
  z-index: 1;
}
.profile-avatar-wrap img,
.profile-avatar-wrap div.lx-avatar-fallback {
  width: 88px !important; height: 88px !important;
  border-radius: 50% !important; object-fit: cover !important;
  border: 3px solid rgba(255,255,255,.9) !important;
  box-shadow: 0 8px 24px rgba(0,0,0,.25) !important;
  font-size: 32px !important;
}
.avatar-cam-btn {
  position: absolute; bottom: 0; right: 0;
  width: 28px; height: 28px; border-radius: 50%;
  background: #fff; border: 2px solid var(--gold);
  color: var(--gold); font-size: 11px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all .2s; z-index: 2;
}
.avatar-cam-btn:hover { background: var(--gold); color: #000; }
.profile-avatar-wrap { cursor: zoom-in; }
.profile-id-body { padding: 18px 20px 22px; text-align: center; }
.profile-display-name {
  font-family: var(--serif); font-size: 20px; font-weight: 400;
  color: var(--text); margin-bottom: 3px;
}
.profile-display-email { font-size: 12.5px; color: var(--muted); margin-bottom: 14px; }

/* Membership badge */
.profile-mem-badge {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;
  margin-bottom: 16px;
}

/* Stats grid */
.profile-stats { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-top: 4px; }
.profile-stat-box {
  background: var(--card2); border-radius: 12px; padding: 12px 8px;
  text-align: center; border: 1px solid var(--bdr2);
}
.profile-stat-val { font-family: var(--serif); font-size: 22px; color: var(--gold); line-height: 1.1; }
.profile-stat-lbl { font-size: 9.5px; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; margin-top: 3px; }

/* Sidebar nav */
.profile-nav-card {
  background: var(--card); border: 1px solid var(--bdr2);
  border-radius: 16px; padding: 8px; overflow: hidden;
}
.profile-nav-item {
  display: flex; align-items: center; gap: 10px;
  padding: 11px 14px; border-radius: 10px; font-size: 13.5px;
  color: var(--text2); cursor: pointer; transition: all var(--t);
  border: none; background: none; width: 100%; font-family: var(--sans);
  text-align: left; text-decoration: none; margin-bottom: 1px;
}
.profile-nav-item:hover { background: var(--card2); color: var(--text); }
.profile-nav-item.active { background: var(--gold-dim); color: var(--gold); font-weight: 500; }
.profile-nav-item i { width: 18px; font-size: 13px; text-align: center; color: var(--gold); flex-shrink: 0; }
.profile-nav-item.danger { color: var(--red); }
.profile-nav-item.danger i { color: var(--red); }
.profile-nav-item.danger:hover { background: rgba(155,35,53,.06); }
.profile-nav-sep { height: 1px; background: var(--bdr2); margin: 6px 6px; }

/* Main content */
.profile-main { display: flex; flex-direction: column; gap: 22px; }

/* Tab sections */
.profile-tab { display: none; flex-direction: column; gap: 22px; }
.profile-tab.active { display: flex; }

/* Content card */
.pf-card {
  background: var(--card); border: 1px solid var(--bdr2);
  border-radius: 16px; overflow: hidden;
}
.pf-card-header {
  padding: 18px 24px; border-bottom: 1px solid var(--bdr2);
  display: flex; align-items: center; justify-content: space-between;
}
.pf-card-title { font-family: var(--serif); font-size: 17px; font-weight: 400; color: var(--text); }
.pf-card-body { padding: 24px; }

/* Booking card on profile */
.bk-mini {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 20px; border-bottom: 1px solid var(--bdr2); gap: 12px;
  transition: background var(--t);
}
.bk-mini:last-child { border-bottom: none; }
.bk-mini:hover { background: var(--card2); }
.bk-mini-ref { font-size: 12px; color: var(--muted); margin-bottom: 3px; }
.bk-mini-name { font-size: 14px; font-weight: 600; color: var(--text); }
.bk-mini-dates { font-size: 12px; color: var(--muted); margin-top: 2px; }

.pf-avatar-modal{position:fixed;inset:0;background:rgba(0,0,0,.78);display:none;align-items:center;justify-content:center;z-index:9999;padding:20px}
.pf-avatar-modal.open{display:flex}
.pf-avatar-modal img{max-width:min(92vw,560px);max-height:88vh;border-radius:14px;border:2px solid var(--gold);box-shadow:0 20px 50px rgba(0,0,0,.5);object-fit:cover}
.pf-avatar-close{position:absolute;top:18px;right:18px;width:38px;height:38px;border-radius:50%;border:1px solid var(--bdr2);background:var(--card);color:var(--text);cursor:pointer;font-size:24px;line-height:1}
</style>

<div class="profile-wrap">
  <div class="container">

    <?php foreach ($errors as $e): ?>
    <div class="alert alert-error" style="margin-bottom:20px">
      <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($e) ?>
    </div>
    <?php endforeach; ?>

    <div class="profile-grid">

      <!-- ════ SIDEBAR ════ -->
      <aside class="profile-sidebar">

        <!-- Identity card -->
        <div class="profile-id-card">
          <div class="profile-id-top">
            <form method="POST" enctype="multipart/form-data" id="avatarForm">
              <input type="hidden" name="do_avatar" value="1">
              <div class="profile-avatar-wrap" onclick="previewProfilePhoto(event)">
                <?= userAvatar($user['profile_img'], $user['name'], 88, 'border:3px solid rgba(255,255,255,.9)!important;box-shadow:0 8px 24px rgba(0,0,0,.25)!important') ?>
                <label class="avatar-cam-btn" title="Change photo" for="avatarInput">
                  <i class="fas fa-camera"></i>
                </label>
                <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display:none" onchange="this.form.submit()">
              </div>
            </form>
          </div>
          <div class="profile-id-body">
            <div class="profile-display-name"><?= htmlspecialchars($user['name']) ?></div>
            <div class="profile-display-email"><?= htmlspecialchars($user['email']) ?></div>

            <?php if ($membership): ?>
            <div class="profile-mem-badge" style="background:linear-gradient(135deg,<?= $membership['gradient_from'] ?>,<?= $membership['gradient_to'] ?>)">
              <?= $membership['icon'] ?> <?= htmlspecialchars($membership['memb_name']) ?> — <?= $membership['discount_pct'] ?>% off
            </div>
            <?php else: ?>
            <a href="<?= $B ?>/membership.php" class="btn btn-gold btn-sm" style="margin-bottom:14px">
              <i class="fas fa-crown"></i> Get Membership
            </a>
            <?php endif; ?>

            <div class="profile-stats">
              <div class="profile-stat-box">
                <div class="profile-stat-val"><?= $uStats['cnt'] ?></div>
                <div class="profile-stat-lbl">Stays</div>
              </div>
              <div class="profile-stat-box">
                <div class="profile-stat-val"><?= number_format($uStats['spent'],0) ?></div>
                <div class="profile-stat-lbl">USD Spent</div>
              </div>
              <div class="profile-stat-box">
                <div class="profile-stat-val"><?= number_format($loyPts) ?></div>
                <div class="profile-stat-lbl">Points</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar nav -->
        <nav class="profile-nav-card">
          <button class="profile-nav-item active" onclick="switchTab('details',this)">
            <i class="fas fa-user-circle"></i> Personal Details
          </button>
          <button class="profile-nav-item" onclick="switchTab('preferences',this)">
            <i class="fas fa-sliders-h"></i> Preferences
          </button>
          <button class="profile-nav-item" onclick="switchTab('password',this)">
            <i class="fas fa-lock"></i> Change Password
          </button>
          <div class="profile-nav-sep"></div>
          <a href="<?= $B ?>/bookings.php" class="profile-nav-item">
            <i class="fas fa-calendar-check"></i> My Bookings
          </a>
          <a href="<?= $B ?>/wishlist.php" class="profile-nav-item">
            <i class="fas fa-heart"></i> Wishlist
          </a>
          <a href="<?= $B ?>/loyalty.php" class="profile-nav-item">
            <i class="fas fa-coins"></i> Loyalty Points
          </a>
          <a href="<?= $B ?>/my-gift-cards.php" class="profile-nav-item">
            <i class="fas fa-gift"></i> Gift Cards
          </a>
          <div class="profile-nav-sep"></div>
          <a href="<?= $B ?>/logout.php" class="profile-nav-item danger">
            <i class="fas fa-sign-out-alt"></i> Sign Out
          </a>
        </nav>
      </aside>

      <!-- ════ MAIN CONTENT ════ -->
      <div class="profile-main">

        <!-- Personal Details -->
        <div id="tab-details" class="profile-tab active">
          <div class="pf-card">
            <div class="pf-card-header">
              <div class="pf-card-title"><i class="fas fa-user-circle" style="color:var(--gold);margin-right:10px;font-size:16px"></i>Personal Details</div>
            </div>
            <div class="pf-card-body">
              <form method="POST">
                <input type="hidden" name="do_profile" value="1">
                <input type="hidden" name="language" value="<?= htmlspecialchars($user['language']??'en') ?>">
                <input type="hidden" name="currency" value="<?= htmlspecialchars($user['currency']??'USD') ?>">
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input class="form-control" name="name" value="<?= htmlspecialchars($user['name']) ?>" required placeholder="Your full name">
                  </div>
                  <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                    <div class="form-hint">Email cannot be changed</div>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input class="form-control" name="phone" value="<?= htmlspecialchars($user['phone']??'') ?>" placeholder="+1 555 000 0000">
                  </div>
                  <div class="form-group">
                    <label class="form-label">Country</label>
                    <input class="form-control" name="country" value="<?= htmlspecialchars($user['country']??'') ?>" placeholder="Your country">
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Address</label>
                  <textarea class="form-control" name="address" rows="2" placeholder="Your address…"><?= htmlspecialchars($user['address']??'') ?></textarea>
                </div>
                <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> Save Changes</button>
              </form>
            </div>
          </div>
        </div>

        <!-- Preferences -->
        <div id="tab-preferences" class="profile-tab">
          <div class="pf-card">
            <div class="pf-card-header">
              <div class="pf-card-title"><i class="fas fa-sliders-h" style="color:var(--gold);margin-right:10px;font-size:16px"></i>Language & Currency</div>
            </div>
            <div class="pf-card-body">
              <form method="POST">
                <input type="hidden" name="do_profile" value="1">
                <input type="hidden" name="name" value="<?= htmlspecialchars($user['name']) ?>">
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Preferred Language</label>
                    <select class="form-control" name="language">
                      <?php foreach (LANGUAGES as $code=>$info): ?>
                      <option value="<?=$code?>" <?=($user['language']??'en')===$code?'selected':''?>><?= $info['flag'] ?> <?= $info['name'] ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Preferred Currency</label>
                    <select class="form-control" name="currency">
                      <?php foreach (CURRENCIES as $code=>$ci): ?>
                      <option value="<?=$code?>" <?=($user['currency']??'USD')===$code?'selected':''?>><?= $ci['flag'] ?> <?=$code?> — <?= htmlspecialchars($ci['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> Save Preferences</button>
              </form>
            </div>
          </div>
        </div>

        <!-- Password -->
        <div id="tab-password" class="profile-tab">
          <div class="pf-card">
            <div class="pf-card-header">
              <div class="pf-card-title"><i class="fas fa-lock" style="color:var(--gold);margin-right:10px;font-size:16px"></i>Change Password</div>
            </div>
            <div class="pf-card-body">
              <form method="POST">
                <input type="hidden" name="do_password" value="1">
                <div class="form-group">
                  <label class="form-label">Current Password</label>
                  <input type="password" class="form-control" name="old_pass" required placeholder="Enter current password">
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" class="form-control" name="new_pass" required minlength="6" placeholder="Min 6 characters">
                  </div>
                  <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" name="conf_pass" required placeholder="Repeat new password">
                  </div>
                </div>
                <button type="submit" class="btn btn-gold"><i class="fas fa-key"></i> Update Password</button>
              </form>
            </div>
          </div>
        </div>

      </div><!-- /profile-main -->
    </div><!-- /profile-grid -->
  </div><!-- /container -->
</div><!-- /profile-wrap -->

<div id="pfAvatarModal" class="pf-avatar-modal" onclick="closeProfilePhoto(event)">
  <button type="button" class="pf-avatar-close" onclick="closeProfilePhoto()">×</button>
  <?php if (!empty($user['profile_img'])): ?>
  <img src="<?= $B ?>/uploads/avatars/<?= htmlspecialchars($user['profile_img']) ?>" alt="Profile photo" id="pfAvatarPreviewImg">
  <?php else: ?>
  <div style="width:220px;height:220px;border-radius:50%;background:var(--gold);display:flex;align-items:center;justify-content:center;font-size:80px;color:#000">
    <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
  </div>
  <?php endif; ?>
</div>

<script>
function switchTab(id, btn) {
  document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.profile-nav-item').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + id).classList.add('active');
  btn.classList.add('active');
  window.scrollTo({ top: 0, behavior: 'smooth' });
}
function previewProfilePhoto(ev) {
  if (ev.target.closest('.avatar-cam-btn')) return;
  const modal = document.getElementById('pfAvatarModal');
  if (!modal) return;
  modal.classList.add('open');
}
function closeProfilePhoto(ev) {
  if (ev && ev.target && ev.target.id !== 'pfAvatarModal') return;
  const modal = document.getElementById('pfAvatarModal');
  if (!modal) return;
  modal.classList.remove('open');
}
</script>

<?php require __DIR__ . '/footer.php'; ?>
