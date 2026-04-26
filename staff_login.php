<?php
error_reporting(E_ALL);
ini_set('display_errors','1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Staff Login — Royale Vista';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($_POST['email'] ?? '');
    $password = clean($_POST['password'] ?? '');
    
    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $staff = $stmt->fetch();
        
        if ($staff && password_verify($password, $staff['password'])) {
            $_SESSION['staff_id'] = $staff['id'];
            $_SESSION['staff_name'] = $staff['name'];
            $_SESSION['staff_role'] = $staff['position'];
            
            header('Location: ' . BASE . '/staff_portal.php');
            exit;
        } else {
            $error = 'Invalid email or password';
        }
    } else {
        $error = 'Please enter email and password';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="<?= BASE ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .login-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); }
        .login-card { background: var(--card); border: 1px solid var(--bdr2); border-radius: 16px; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
        .login-header { text-align: center; margin-bottom: 30px; }
        .login-logo { width: 60px; height: 60px; background: var(--gold); display: flex; align-items: center; justify-content: center; font-family: var(--cinzel); font-size: 18px; color: #fff; clip-path: polygon(50% 0%,93% 25%,93% 75%,50% 100%,7% 75%,7% 25%); margin: 0 auto 20px; }
        .login-title { font-family: var(--serif); font-size: 24px; color: var(--text); margin-bottom: 8px; }
        .login-subtitle { color: var(--text2); font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--text); }
        .form-input { width: 100%; padding: 12px 16px; border: 1px solid var(--bdr2); border-radius: 8px; font-size: 14px; background: var(--card2); color: var(--text); transition: all 0.3s ease; }
        .form-input:focus { outline: none; border-color: var(--gold); box-shadow: 0 0 0 3px rgba(192,155,91,0.1); }
        .btn-login { width: 100%; background: var(--gold); color: #000; border: none; padding: 14px; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
        .btn-login:hover { background: var(--gold2); transform: translateY(-1px); }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .admin-link { text-align: center; margin-top: 20px; }
        .admin-link a { color: var(--gold); text-decoration: none; font-size: 14px; }
        .admin-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">RV</div>
                <h1 class="login-title">Staff Portal</h1>
                <p class="login-subtitle">Royale Vista Hotel Management</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" required placeholder="your.email@royalevista.com">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
            
            <div class="admin-link">
                <a href="<?= BASE ?>/login.php">
                    <i class="fas fa-user-shield"></i> Admin Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>
