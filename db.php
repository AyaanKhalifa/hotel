<?php
// ============================================================
//  ROYALE VISTA v2 — Database (PDO, WAMP/XAMPP safe)
// ============================================================
require_once __DIR__ . '/config.php';

$DB_HOST = 'sql100.infinityfree.com';
$DB_NAME = 'if0_41757366_royalevista';
$DB_USER = if0_41757366';
$DB_PASS = 'ayaan4432';          // ← Change if your MySQL has a password

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // Friendly error page — no raw exceptions exposed
    http_response_code(503);
    $theme = getTheme();
    echo '<!DOCTYPE html><html data-theme="' . $theme . '"><head><meta charset="UTF-8">
    <title>Database Error — Royale Vista</title>
    <style>
    :root{--gold:#d4af37;--bg:' . ($theme === 'dark' ? '#0a0a0f' : '#f5f1e8') . ';--card:' . ($theme === 'dark' ? '#1a1a2e' : '#fff') . ';--text:' . ($theme === 'dark' ? '#fff' : '#111') . ';}
    *{margin:0;padding:0;box-sizing:border-box}body{font-family:sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
    .box{background:var(--card);border:1px solid rgba(212,175,55,.2);border-radius:16px;padding:40px;max-width:560px;width:100%;text-align:center}
    h1{color:var(--gold);font-size:1.8rem;margin-bottom:12px}p{opacity:.7;line-height:1.6;margin-bottom:16px}
    code{background:rgba(212,175,55,.1);border:1px solid rgba(212,175,55,.2);border-radius:6px;padding:8px 14px;font-size:13px;display:block;margin:12px 0;color:var(--gold)}
    a{color:var(--gold)}
    </style></head><body><div class="box">
    <h1>🔌 Database Connection Failed</h1>
    <p>Could not connect to MySQL. Please check your database settings.</p>
    <code>' . htmlspecialchars($e->getMessage()) . '</code>
    <p>Edit <strong>includes/db.php</strong> and set the correct password,<br>then make sure MySQL is running in WAMP/XAMPP.</p>
    <a href="' . BASE . '/test_setup.php">→ Run Setup Diagnostic</a>
    </div></body></html>';
    exit;
}

/**
 * Log administrative or system activity
 * @param string $action Slug like 'approve_review'
 * @param string $details Human readable info
 */
function logActivity($action, $details = '')
{
    global $pdo;
    $uid = $_SESSION['user_id'] ?? null;
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$uid, $action, $details]);
    } catch (Exception $e) {
        // Silently fail to not break the UI if logging fails
    }
}
?>