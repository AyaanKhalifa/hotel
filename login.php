<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mailer.php';

if (isLoggedIn()) { header('Location: ' . BASE . '/index.php'); exit; }

$theme      = getTheme();
$B          = BASE;
$activeTab  = $_GET['tab'] ?? 'login';
$loginErr   = $regErr = '';

// ── Handle Login ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_login'])) {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if (!$email || !$pass) {
        $loginErr = 'Please enter your email and password.';
    } else {
        $user = $pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $user->execute([$email]);
        $u = $user->fetch();
        if ($u && password_verify($pass, $u['password'])) {
            $_SESSION['user_id']      = $u['id'];
            $_SESSION['username']     = $u['name'];
            $_SESSION['display_name'] = $u['name'];
            $_SESSION['email']        = $u['email'];
            $_SESSION['role']         = $u['role'];
            $_SESSION['profile_img']  = $u['profile_img'];
            if (!empty($u['language'])) $_SESSION['language'] = $u['language'];
            if (!empty($u['currency'])) $_SESSION['currency']  = $u['currency'];
            flash('Welcome back, ' . explode(' ',$u['name'])[0] . '! 👋');

            // ── Send login notification email ──
            try { sendLoginNotificationEmail($u['email'], $u['name']); } catch (\Exception $e) {}

            $redirect = $_SESSION['after_login'] ?? $B . '/index.php';
            unset($_SESSION['after_login']);
            header('Location: ' . $redirect); exit;
        }
        $loginErr = 'Invalid email or password.';
    }
    $activeTab = 'login';
}

// ── Handle Register ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_register'])) {
    $name    = clean($_POST['name'] ?? '');
    $email   = clean($_POST['email'] ?? '');
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $phone   = clean($_POST['phone'] ?? '');
    $country = clean($_POST['country'] ?? '');

    if (!$name || !$email || !$pass) {
        $regErr = 'Name, email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $regErr = 'Please enter a valid email address.';
    } elseif (strlen($pass) < 6) {
        $regErr = 'Password must be at least 6 characters.';
    } elseif ($pass !== $confirm) {
        $regErr = 'Passwords do not match.';
    } else {
        $exists = $pdo->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $exists->execute([$email]);
        if ($exists->fetch()) {
            $regErr = 'This email is already registered. Please login.';
        } else {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (name,email,password,phone,country) VALUES (?,?,?,?,?)")
                ->execute([$name,$email,$hashed,$phone,$country]);
            $_SESSION['user_id']      = $pdo->lastInsertId();
            $_SESSION['username']     = $name;
            $_SESSION['display_name'] = $name;
            $_SESSION['email']        = $email;
            $_SESSION['role']         = 'user';
            // ── Send welcome email ──
            try { sendWelcomeEmail($email, $name); } catch (\Exception $e) {}

            flash('Welcome to Royale Vista, ' . explode(' ',$name)[0] . '! 🎉');
            header('Location: ' . $B . '/index.php'); exit;
        }
    }
    $activeTab = 'register';
}

$pageTitle = t('login') . ' / ' . t('register');
?>
<!DOCTYPE html>
<html lang="<?= getUserLang() ?>" dir="<?= getLangDir() ?>" data-theme="<?= $theme ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> | Royale Vista</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= $B ?>/css/style.css">
<link rel="stylesheet" href="<?= $B ?>/css/luxury.css">
<style>
/* Exclusive Auth UI Overrides */
.auth-split { min-height: 100vh; display: flex; background: var(--bg); }
.auth-visual { flex: 1.2; display: flex; flex-direction: column; justify-content: flex-end; padding: 70px; position: relative; overflow: hidden; }
.auth-visual::before { content:''; position:absolute; inset:0; background: url('https://images.unsplash.com/photo-1542314831-c6a4d14d8c85?q=80&w=2000&auto=format&fit=crop') center/cover; animation: bgZoom 40s infinite alternate; }
@keyframes bgZoom { from { transform: scale(1); } to { transform: scale(1.15); } }
.auth-visual-overlay { position: absolute; inset: 0; background: linear-gradient(0deg, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.3) 50%, rgba(0,0,0,0.1) 100%); z-index: 1; backdrop-filter: blur(2px); }
.auth-visual-content { position: relative; z-index: 2; padding: 40px; border-radius: 24px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(16px); color: #fff; max-width: 600px; transform: translateY(0); transition: transform 0.8s var(--ease); box-shadow: 0 32px 64px rgba(0,0,0,0.5); }
.auth-form-side { width: 520px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; padding: 60px 52px; background: var(--card); z-index: 5; box-shadow: -10px 0 40px rgba(0,0,0,0.05); }

.auth-styled-input {
  position: relative; margin-bottom: 24px;
}
.auth-styled-input .form-control {
  background: transparent; border: none; border-bottom: 2px solid var(--border); border-radius: 0; padding: 12px 10px 12px 40px; box-shadow: none; font-size: 15px; transition: all 0.3s;
}
.auth-styled-input .form-control:focus {
  border-bottom-color: var(--gold); background: rgba(192,155,91,0.02);
}
.auth-styled-input .input-icon {
  left: 10px; font-size: 16px; color: var(--muted); transition: color 0.3s;
}
.auth-styled-input .form-control:focus ~ .input-icon, 
.auth-styled-input .form-control:valid ~ .input-icon {
  color: var(--gold);
}
.auth-title { font-size: 38px; font-weight: 500; font-family: var(--serif); margin-bottom: 12px; letter-spacing: -0.5px; }
.auth-subtitle { font-size: 14px; color: var(--muted); margin-bottom: 36px; line-height: 1.6; }
</style>
</head>
<body>
<div class="auth-split">

  <!-- Left panel -->
  <div class="auth-visual">
    <div class="auth-visual-overlay"></div>
    <div class="auth-visual-content">
      <a href="<?= $B ?>/index.php" style="text-decoration:none">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:30px">
          <div style="width:50px;height:50px;border-radius:12px;background:linear-gradient(135deg,var(--gold),#a07a3a);display:flex;align-items:center;justify-content:center;font-family:var(--serif);font-size:26px;color:#fff;font-weight:700">RV</div>
          <div class="auth-brand-name" style="font-size:32px; font-family:var(--serif); color:#fff;">Royale Vista</div>
        </div>
      </a>
      <p style="font-size:18px; line-height:1.8; color:rgba(255,255,255,0.85); margin-bottom:32px; font-family:var(--sans); font-weight:300;">An oasis of uncompromised luxury and unparalleled comfort. Where every stay becomes an unforgettable story.</p>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
        <?php foreach ([['fa-trophy','Best Rate Guaranteed'],['fa-level-up-alt','Room Upgrades'],['fa-coffee','Free Breakfast'],['fa-car','Airport Transfer']] as [$icon,$feat]): ?>
        <div style="display:flex; align-items:center; gap:12px; font-size:14px; font-weight:400; color:#fff;">
          <i class="fas <?= $icon ?>" style="color:var(--gold); width:20px;"></i> <?= $feat ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Right form -->
  <div class="auth-form-side">
    <div class="auth-form-wrap">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:28px">
        <div>
          <div class="auth-title"><?= $activeTab==='login' ? t('sign_in') : t('create_account') ?></div>
          <p style="font-size:13px;color:var(--muted);margin-bottom:28px"><?= $activeTab==='login' ? 'Sign in to manage your bookings' : 'Join Royale Vista today' ?></div>
        </div>
        <!-- Theme toggle -->
        <button onclick="toggleTheme()" style="background:var(--card2);border:1px solid var(--bdr2);border-radius:8px;padding:8px 12px;cursor:pointer;color:var(--text2);font-size:16px">
          <i class="fas fa-<?= $theme==='dark'?'sun':'moon' ?>" id="themeBtn"></i>
        </button>
      </div>

      <!-- Tabs -->
      <div class="auth-tabs">
        <button class="auth-tab <?= $activeTab==='login'?'active':'' ?>" onclick="switchTab('login')"><?= t('sign_in') ?></button>
        <button class="auth-tab <?= $activeTab==='register'?'active':'' ?>" onclick="switchTab('register')"><?= t('create_account') ?></button>
      </div>

      <!-- LOGIN -->
      <div id="tab-login" style="display:<?= $activeTab==='login'?'block':'none' ?>">
        <?php if ($loginErr): ?><div class="alert alert-error" style="background:rgba(239,68,68,.1);color:#ef4444;padding:12px;border-radius:6px;margin-bottom:20px;font-size:13px"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($loginErr) ?></div><?php endif; ?>
        
        <script src="https://accounts.google.com/gsi/client" async defer></script>
        <div id="g_id_onload"
             data-client_id="<?= GOOGLE_CLIENT_ID ?>"
             data-context="signin"
             data-ux_mode="popup"
             data-login_uri="<?= BASE ?>/google-callback.php"
             data-auto_prompt="false">
        </div>
        <div class="g_id_signin"
             data-type="standard"
             data-shape="rectangular"
             data-theme="outline"
             data-text="continue_with"
             data-size="large"
             data-logo_alignment="left"
             style="width: 100%; margin-bottom: 24px;">
        </div>

        
        <div style="display:flex;align-items:center;margin-bottom:20px">
            <div style="flex:1;height:1px;background:var(--bdr2)"></div>
            <div style="padding:0 15px;font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:1px">Or sign in with email</div>
            <div style="flex:1;height:1px;background:var(--bdr2)"></div>
        </div>

        <form method="POST">
          <input type="hidden" name="do_login" value="1">
          <div class="auth-styled-input">
            <input class="form-control" type="email" name="email" placeholder="Email Address" required autocomplete="email" value="<?= htmlspecialchars($_POST['email']??'') ?>">
            <i class="fas fa-envelope input-icon" style="position:absolute; top:12px;"></i>
          </div>
          <div class="auth-styled-input">
            <input class="form-control" type="password" name="password" placeholder="Password" required autocomplete="current-password" id="loginPass">
            <i class="fas fa-lock input-icon" style="position:absolute; top:12px;"></i>
            <button type="button" onclick="togglePass('loginPass')" style="position:absolute;right:12px;top:12px;background:none;border:none;color:var(--muted);cursor:pointer"><i class="fas fa-eye" id="loginPassIcon"></i></button>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;color:var(--text2)">
              <input type="checkbox" name="remember"> <?= t('remember_me') ?>
            </label>
            <a href="<?= $B ?>/forgot-password.php" style="font-size:13px;color:var(--gold)"><?= t('forgot_password') ?></a>
          </div>
          <button type="submit" class="btn btn-gold btn-block btn-lg">
            <i class="fas fa-sign-in-alt"></i> <?= t('sign_in') ?>
          </button>
        </form>
        <div style="text-align:center;margin-top:20px;font-size:13px;color:var(--muted)">
          <?= t('no_account') ?> <a href="#" onclick="switchTab('register')" class="text-gold"><?= t('create_account') ?></a>
        </div>
        <div style="text-align:center;margin-top:10px">
          <a href="<?= $B ?>/admin/login.php" style="font-size:12px;color:var(--muted)">Admin Portal →</a>
        </div>
      </div>

      <!-- REGISTER -->
      <div id="tab-register" style="display:<?= $activeTab==='register'?'block':'none' ?>">
        <?php if ($regErr): ?><div class="alert alert-error" style="background:rgba(239,68,68,.1);color:#ef4444;padding:12px;border-radius:6px;margin-bottom:20px;font-size:13px"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($regErr) ?></div><?php endif; ?>
        
        <div class="g_id_signin"
             data-type="standard"
             data-shape="rectangular"
             data-theme="outline"
             data-text="continue_with"
             data-size="large"
             data-logo_alignment="left"
             style="width: 100%; margin-bottom: 24px;">
        </div>
        
        <div style="display:flex;align-items:center;margin-bottom:20px">
            <div style="flex:1;height:1px;background:var(--bdr2)"></div>
            <div style="padding:0 15px;font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:1px">Or sign up with email</div>
            <div style="flex:1;height:1px;background:var(--bdr2)"></div>
        </div>

        <form method="POST">
          <input type="hidden" name="do_register" value="1">
          <div class="auth-styled-input">
            <input class="form-control" name="name" placeholder="Full Name" required value="<?= htmlspecialchars($_POST['name']??'') ?>">
            <i class="fas fa-user input-icon" style="position:absolute; top:12px;"></i>
          </div>
          <div class="auth-styled-input">
            <input class="form-control" type="email" name="email" placeholder="Email Address" required value="<?= htmlspecialchars($_POST['email']??'') ?>">
            <i class="fas fa-envelope input-icon" style="position:absolute; top:12px;"></i>
          </div>
          <div class="auth-styled-input">
            <input class="form-control" type="password" name="password" id="regPass" placeholder="Password (Min 6 chars)" required>
            <i class="fas fa-lock input-icon" style="position:absolute; top:12px;"></i>
            <button type="button" onclick="togglePass('regPass')" style="position:absolute;right:12px;top:12px;background:none;border:none;color:var(--muted);cursor:pointer"><i class="fas fa-eye" id="regPassIcon"></i></button>
          </div>
          <div class="auth-styled-input">
            <input class="form-control" type="password" name="confirm" id="confPass" placeholder="Confirm Password" required>
            <i class="fas fa-lock input-icon" style="position:absolute; top:12px;"></i>
          </div>
          
          <div class="form-row" style="margin-bottom:24px;">
            <div class="auth-styled-input" style="margin-bottom:0;">
              <input class="form-control" name="phone" placeholder="Phone (Optional)" value="<?= htmlspecialchars($_POST['phone']??'') ?>">
              <i class="fas fa-phone input-icon" style="position:absolute; top:12px;"></i>
            </div>
            <div class="auth-styled-input" style="margin-bottom:0;">
              <input class="form-control" name="country" placeholder="Country" value="<?= htmlspecialchars($_POST['country']??'') ?>">
              <i class="fas fa-globe input-icon" style="position:absolute; top:12px;"></i>
            </div>
          </div>
          <button type="submit" class="btn btn-gold btn-block btn-lg">
            <i class="fas fa-user-plus"></i> <?= t('create_account') ?>
          </button>
        </form>
        <div style="text-align:center;margin-top:20px;font-size:13px;color:var(--muted)">
          <?= t('have_account') ?> <a href="#" onclick="switchTab('login')" class="text-gold"><?= t('sign_in') ?></a>
        </div>
      </div>

    </div>
  </div>
</div>

<div class="toast-stack" id="toastStack"></div>
<script>
function switchTab(tab){
  document.getElementById('tab-login').style.display=tab==='login'?'block':'none';
  document.getElementById('tab-register').style.display=tab==='register'?'block':'none';
  document.querySelectorAll('.auth-tab').forEach((t,i)=>t.classList.toggle('active',(i===0&&tab==='login')||(i===1&&tab==='register')));
}
function toggleTheme(){
  const h=document.documentElement,n=h.getAttribute('data-theme')==='dark'?'light':'dark';
  h.setAttribute('data-theme',n);
  document.cookie='rv_theme='+n+';path=/;max-age=31536000';
  document.getElementById('themeBtn').className='fas fa-'+(n==='dark'?'sun':'moon');
}
function togglePass(id){
  const f=document.getElementById(id),i=document.getElementById(id+'Icon');
  f.type=f.type==='password'?'text':'password';
  i.className='fas fa-'+(f.type==='password'?'eye':'eye-slash');
}
// If tab in URL
const p=new URLSearchParams(location.search);
if(p.get('tab')==='register') switchTab('register');

// Toast system for this page
function toast(msg,type='info'){
  const s=document.getElementById('toastStack');
  const el=document.createElement('div');
  el.className='toast '+type;
  el.innerHTML=`<span>${{success:'✓',error:'✕',info:'ℹ'}[type]}</span><span>${msg}</span>`;
  s.appendChild(el);setTimeout(()=>el&&el.remove(),3500);
}
</script>
</body></html>