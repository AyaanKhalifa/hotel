<?php
/**
 * Royale Vista — Forgot Password with OTP
 * Steps: 1. Email Lookup -> 2. OTP Verification -> 3. Password Reset
 */
error_reporting(E_ALL); 
ini_set('display_errors', '1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mailer.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle = 'Forgot Password — Royale Vista';
$error = '';
$success = '';

// Determine current step
$step = $_SESSION['forgot_step'] ?? 'email';
$email = $_SESSION['forgot_email'] ?? '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // STEP 1: Send OTP
    if ($action === 'send_otp') {
        $email = clean($_POST['email'] ?? '');
        $u = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
        $u->execute([$email]);
        $user = $u->fetch();

        if ($user) {
            $otp = rand(100000, 999999);
            $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            $upd = $pdo->prepare("UPDATE users SET reset_otp = ?, reset_otp_expiry = ? WHERE id = ?");
            $upd->execute([$otp, $expiry, $user['id']]);

            if (sendOTPEmail($email, $user['name'], $otp)) {
                $_SESSION['forgot_step'] = 'verify';
                $_SESSION['forgot_email'] = $email;
                $step = 'verify';
                $success = "A 6-digit OTP has been sent to <strong>$email</strong>.";
            } else {
                $error = "Failed to send OTP. Please check your mail settings.";
            }
        } else {
            // Security: Don't reveal if email exists, but for better UX in this context:
            $error = "We couldn't find an account with that email.";
        }
    }

    // STEP 2: Verify OTP
    elseif ($action === 'verify_otp') {
        $otp = trim($_POST['otp'] ?? '');
        $u = $pdo->prepare("SELECT id, reset_otp_expiry FROM users WHERE email = ? AND reset_otp = ?");
        $u->execute([$email, $otp]);
        $user = $u->fetch();
        
        if ($user && strtotime($user['reset_otp_expiry']) > time()) {
            $_SESSION['forgot_step'] = 'reset';
            $step = 'reset';
            $success = "OTP verified. Please enter your new password.";
        } else {
            $error = "Invalid or expired OTP. Please try again.";
        }
    }

    // STEP 3: Reset Password
    elseif ($action === 'reset_password') {
        $pass = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (strlen($pass) < 6) {
            $error = "Password must be at least 6 characters.";
        } elseif ($pass !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE users SET password = ?, reset_otp = NULL, reset_otp_expiry = NULL WHERE email = ?");
            $upd->execute([$hash, $email]);

            unset($_SESSION['forgot_step']);
            unset($_SESSION['forgot_email']);
            $step = 'success';
        }
    }
}

// Reset session if requested
if (isset($_GET['restart'])) {
    unset($_SESSION['forgot_step']);
    unset($_SESSION['forgot_email']);
    header("Location: forgot-password.php");
    exit;
}

require __DIR__ . '/header.php';
?>

<div style="padding-top:120px;padding-bottom:80px;min-height:80vh;display:flex;align-items:center;justify-content:center;background:radial-gradient(circle at top right, rgba(212,175,55,0.05), transparent)">
  <div style="width:440px;max-width:95vw">
    
    <div style="text-align:center;margin-bottom:32px">
      <div style="font-family:var(--serif);font-size:32px;color:var(--gold);margin-bottom:8px">Account Recovery</div>
      <div style="width:40px;height:2px;background:var(--gold);margin:0 auto 12px"></div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="border-radius:12px;margin-bottom:20px;font-size:14px;border:none;background:rgba(220,53,69,0.1);color:#ff6b6b">
            <i class="fas fa-exclamation-circle" style="margin-right:8px"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success" style="border-radius:12px;margin-bottom:20px;font-size:14px;border:none;background:rgba(40,167,69,0.1);color:#51cf66">
            <i class="fas fa-check-circle" style="margin-right:8px"></i> <?= $success ?>
        </div>
    <?php endif; ?>

    <div class="card" style="padding:32px;border-radius:20px;background:var(--card-bg);border:1px solid rgba(212,175,55,0.15);box-shadow:0 20px 40px rgba(0,0,0,0.3)">
        
        <?php if ($step === 'email'): ?>
            <p style="color:var(--muted);font-size:14px;text-align:center;margin-bottom:24px">Enter your email address and we'll send you an OTP to reset your password.</p>
            <form method="POST">
                <input type="hidden" name="action" value="send_otp">
                <div class="form-group" style="margin-bottom:24px">
                    <label class="form-label">Email Address</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" class="form-control" required placeholder="name@example.com" value="<?= htmlspecialchars($email) ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-gold btn-block" style="padding:14px;font-weight:700;letter-spacing:1px">Send OTP</button>
            </form>

        <?php elseif ($step === 'verify'): ?>
            <p style="color:var(--muted);font-size:14px;text-align:center;margin-bottom:24px">Verification code sent to <strong><?= htmlspecialchars($email) ?></strong></p>
            <form method="POST">
                <input type="hidden" name="action" value="verify_otp">
                <div class="form-group" style="margin-bottom:24px">
                    <label class="form-label">6-Digit OTP</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-key input-icon"></i>
                        <input type="text" name="otp" class="form-control" required maxlength="6" placeholder="000000" style="letter-spacing:8px;text-align:center;font-weight:700;font-size:20px">
                    </div>
                </div>
                <button type="submit" class="btn btn-gold btn-block" style="padding:14px;font-weight:700">Verify Code</button>
                <div style="text-align:center;margin-top:20px">
                    <a href="?restart=1" style="font-size:13px;color:var(--gold)">Resend OTP or Change Email</a>
                </div>
            </form>

        <?php elseif ($step === 'reset'): ?>
            <p style="color:var(--muted);font-size:14px;text-align:center;margin-bottom:24px">Almost there! Choose a secure new password.</p>
            <form method="POST">
                <input type="hidden" name="action" value="reset_password">
                <div class="form-group" style="margin-bottom:16px">
                    <label class="form-label">New Password</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" class="form-control" required placeholder="Min. 6 characters">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:24px">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-shield-alt input-icon"></i>
                        <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat password">
                    </div>
                </div>
                <button type="submit" class="btn btn-gold btn-block" style="padding:14px;font-weight:700">Update Password</button>
            </form>

        <?php elseif ($step === 'success'): ?>
            <div style="text-align:center;padding:10px 0">
                <div style="font-size:60px;color:#51cf66;margin-bottom:20px"><i class="fas fa-check-circle"></i></div>
                <h2 style="font-size:22px;margin-bottom:12px;color:var(--text)">Password Reset!</h2>
                <p style="color:var(--muted);font-size:14px;margin-bottom:28px">Your password has been successfully updated. You can now log in with your new credentials.</p>
                <a href="login.php" class="btn btn-gold btn-block" style="padding:14px;font-weight:700">Go to Login</a>
            </div>
        <?php endif; ?>

    </div>

    <?php if ($step !== 'success'): ?>
    <div style="text-align:center;margin-top:24px">
        <a href="login.php" style="font-size:14px;color:var(--muted);text-decoration:none"><i class="fas fa-arrow-left" style="margin-right:6px"></i> Back to Login</a>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
