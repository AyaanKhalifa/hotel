<?php
/**
 * Royale Vista — Google OAuth Handler
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['credential'])) {
    header('Location: login.php');
    exit;
}

$id_token = $_POST['credential'];

// --- JWT DECODER (Simplistic for demo/integration, ideally use a library) ---
function decode_jwt($jwt) {
    if (!$jwt) return false;
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return false;
    return json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
}

$payload = decode_jwt($id_token);

if (!$payload || empty($payload['sub'])) {
    die("Invalid Google authentication response.");
}

$google_id = $payload['sub'];
$email     = $payload['email'];
$name      = $payload['name'];
$picture   = $payload['picture'] ?? null;

// --- Database Logic ---
$u = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ? LIMIT 1");
$u->execute([$google_id, $email]);
$user = $u->fetch();

if ($user) {
    // Linked account or email match
    if (empty($user['google_id'])) {
        // Link google_id if not already set
        $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?")->execute([$google_id, $user['id']]);
    }
} else {
    // New User — Auto Register
    $pdo->prepare("INSERT INTO users (name, email, google_id, profile_img, role) VALUES (?, ?, ?, ?, 'user')")
        ->execute([$name, $email, $google_id, $picture]);
    
    $uid = $pdo->lastInsertId();
    $u = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $u->execute([$uid]);
    $user = $u->fetch();

    // Send Welcome Email
    try { sendWelcomeEmail($email, $name); } catch(\Exception $e) {}
}

// --- Log them in ---
$_SESSION['user_id']      = $user['id'];
$_SESSION['username']     = $user['name'];
$_SESSION['display_name'] = $user['name'];
$_SESSION['email']        = $user['email'];
$_SESSION['role']         = $user['role'];
$_SESSION['profile_img']  = $user['profile_img'];

flash("Welcome, " . explode(' ', $user['name'])[0] . "! Logged in via Google.");

header("Location: index.php");
exit;
