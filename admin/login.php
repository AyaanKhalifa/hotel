<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

if (isAdmin()) { header('Location: ' . BASE . '/admin/index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($email && $pass) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? AND role='admin' LIMIT 1");
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        if ($u && password_verify($pass, $u['password'])) {
            $_SESSION['user_id']      = $u['id'];
            $_SESSION['username']     = $u['name'];
            $_SESSION['display_name'] = $u['name'];
            $_SESSION['email']        = $u['email'];
            $_SESSION['role']         = 'admin';
            $_SESSION['profile_img']  = $u['profile_img'] ?? null;
            if (!empty($u['language'])) $_SESSION['language'] = $u['language'];
            if (!empty($u['currency'])) $_SESSION['currency']  = $u['currency'];
            header('Location: ' . BASE . '/admin/index.php'); exit;
        }
    }
    $error = 'Invalid credentials or insufficient permissions.';
}
$theme = getTheme();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $theme ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login — Royale Vista</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE ?>/css/style.css">
<style>
body { min-height:100vh; display:flex; align-items:center; justify-content:center; background:var(--bg); }
.admin-login-card { width:420px; max-width:95vw; }
.admin-logo { text-align:center; margin-bottom:32px; }
.admin-logo-mark { width:60px; height:60px; border-radius:14px; background:linear-gradient(135deg,var(--gold),var(--gold2)); display:flex; align-items:center; justify-content:center; font-family:var(--serif); font-size:28px; font-weight:700; color:#000; margin:0 auto 12px; }
.admin-logo-text { font-family:var(--serif); font-size:26px; color:var(--gold); letter-spacing:2px; }
.admin-logo-sub { font-size:12px; color:var(--mu); letter-spacing:2px; text-transform:uppercase; margin-top:4px; }
</style>
</head>
<body>
<div class="admin-login-card">
  <div class="admin-logo">
    <div class="admin-logo-mark">RV</div>
    <div class="admin-logo-text">Royale Vista</div>
    <div class="admin-logo-sub">Administration Portal</div>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-error" style="margin-bottom:20px"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form method="POST">
        <div class="form-group">
          <label class="form-label">Admin Email</label>
          <div class="input-icon-wrap">
            <i class="fas fa-envelope input-icon"></i>
            <input class="form-control" type="email" name="email" placeholder="admin@royalevista.com" required autocomplete="email" value="<?= htmlspecialchars($_POST['email']??'') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-icon-wrap">
            <i class="fas fa-lock input-icon"></i>
            <input class="form-control" type="password" name="password" id="adminPass" placeholder="••••••••" required autocomplete="current-password">
            <button type="button" onclick="const f=document.getElementById('adminPass');f.type=f.type==='password'?'text':'password'" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--mu);cursor:pointer"><i class="fas fa-eye"></i></button>
          </div>
        </div>
        <button type="submit" class="btn btn-gold btn-block btn-lg" style="margin-top:6px">
          <i class="fas fa-shield-alt"></i> Sign In to Admin
        </button>
      </form>

      <div style="margin-top:20px;padding:14px;background:var(--golddim);border-radius:var(--r);border:1px solid var(--br)">
        <div style="font-size:10px;color:var(--gold);letter-spacing:1.5px;text-transform:uppercase;margin-bottom:6px">Demo</div>
        <div style="font-size:13px;color:var(--tx2)">admin@royalevista.com / <strong>password</strong></div>
      </div>
    </div>
  </div>

  <div style="text-align:center;margin-top:20px">
    <a href="<?= BASE ?>/index.php" style="font-size:13px;color:var(--mu)">← Back to Hotel Website</a>
  </div>
</div>
</body>
</html>
