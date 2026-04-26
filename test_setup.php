<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__ . '/includes/config.php';
$theme = getTheme();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $theme ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Setup Diagnostic — Royale Vista</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE ?>/css/style.css">
<style>
body{max-width:680px;margin:40px auto;padding:0 24px}
.row{display:flex;align-items:flex-start;gap:14px;padding:14px 0;border-bottom:1px solid var(--bdr2)}
.row:last-child{border-bottom:none}
.icon{font-size:18px;flex-shrink:0;margin-top:2px}
.ok{color:var(--green)}.err{color:var(--red)}.warn{color:var(--amber)}
code{background:var(--card2);border:1px solid var(--bdr2);border-radius:6px;padding:6px 12px;font-size:12px;display:block;margin:8px 0;color:var(--gold)}
</style>
</head>
<body>
<div class="card" style="padding:32px;margin-bottom:20px">
  <div style="font-family:var(--serif);font-size:32px;color:var(--gold);margin-bottom:4px">Royale Vista v2</div>
  <div style="font-size:13px;color:var(--muted);margin-bottom:28px">Setup Diagnostic — <?= date('d M Y H:i') ?></div>

<?php
$allOk = true;

// PHP Version
$phpOk = version_compare(PHP_VERSION,'7.4','>=');
$allOk = $allOk && $phpOk;
echo '<div class="row"><span class="icon '.($phpOk?'ok':'err').'">'  .($phpOk?'✓':'✗').'</span><div><strong>PHP '.PHP_VERSION.'</strong><div style="font-size:12px;color:var(--muted)">'.($phpOk?'Compatible ✓':'Requires PHP 7.4+').'</div></div></div>';

// BASE URL
echo '<div class="row"><span class="icon ok">✓</span><div><strong>Base URL</strong><code>'.BASE.'</code></div></div>';

// PDO MySQL
$pdoOk = extension_loaded('pdo_mysql');
$allOk = $allOk && $pdoOk;
echo '<div class="row"><span class="icon '.($pdoOk?'ok':'err').'">' .($pdoOk?'✓':'✗').'</span><div><strong>PDO MySQL Extension</strong><div style="font-size:12px;color:var(--'.($pdoOk?'green':'red').'">' .($pdoOk?'Loaded ✓':'NOT loaded — enable pdo_mysql in php.ini').'</div></div></div>';

// DB Connection
$dbOk = false; $dbErr = ''; $tables = [];
try {
    require_once __DIR__.'/includes/db.php';
    $dbOk = true;
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
} catch(Exception $e){ $dbErr = $e->getMessage(); }
$allOk = $allOk && $dbOk;
echo '<div class="row"><span class="icon '.($dbOk?'ok':'err').'">'.($dbOk?'✓':'✗').'</span><div><strong>Database Connection</strong>';
if($dbOk) echo '<div style="font-size:12px;color:var(--green)">Connected to <strong>royalevista</strong> ('.count($tables).' tables)</div>';
else echo '<code>'.$dbErr.'</code><div style="font-size:12px;color:var(--muted)">Edit <strong>includes/db.php</strong> and set DB_PASS / DB_NAME</div>';
echo '</div></div>';

// Required tables
if($dbOk){
    $req = ['users','rooms','room_types','bookings','memberships','offers','wishlists','room_ratings'];
    $missing = array_diff($req,$tables);
    $tok = empty($missing);
    $allOk = $allOk && $tok;
    echo '<div class="row"><span class="icon '.($tok?'ok':'err').'">'.($tok?'✓':'✗').'</span><div><strong>Database Tables</strong>';
    if($tok) echo '<div style="font-size:12px;color:var(--green)">All '.count($req).' required tables exist ✓</div>';
    else echo '<div style="font-size:12px;color:var(--red)">Missing: '.implode(', ',$missing).'<br>Import <strong>setup.sql</strong> in phpMyAdmin</div>';
    echo '</div></div>';

    // Seed data check
    $adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
    $roomCount  = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
    $seedOk = $adminCount>0 && $roomCount>0;
    echo '<div class="row"><span class="icon '.($seedOk?'ok':'warn').'">'.($seedOk?'✓':'⚠').'</span><div><strong>Seed Data</strong><div style="font-size:12px;color:var(--'.($seedOk?'green':'amber').'">'.$adminCount.' admin(s) · '.$roomCount.' rooms · Default password: <strong>password</strong></div></div></div>';
}

// Uploads writable
$upOk = is_writable(__DIR__.'/uploads/avatars');
echo '<div class="row"><span class="icon '.($upOk?'ok':'warn').'">'.($upOk?'✓':'⚠').'</span><div><strong>Uploads Directory</strong><div style="font-size:12px;color:var(--'.($upOk?'green':'amber').'">'  .($upOk?'Writable ✓':'chmod 755 uploads/avatars/ required').'</div></div></div>';

// Currencies / Languages
echo '<div class="row"><span class="icon ok">✓</span><div><strong>Currency & Language System</strong><div style="font-size:12px;color:var(--green)">'.count(CURRENCIES).' currencies · '.count(LANGUAGES).' languages · Current: '.getUserCurrency().' '.htmlspecialchars(CURRENCIES[getUserCurrency()]['symbol']).'</div></div></div>';

// .htaccess
$htOk = file_exists(__DIR__.'/.htaccess');
$htHasPhpFlag = $htOk && (str_contains(file_get_contents(__DIR__.'/.htaccess'),'php_flag') || str_contains(file_get_contents(__DIR__.'/.htaccess'),'php_value'));
$htFinal = $htOk && !$htHasPhpFlag;
echo '<div class="row"><span class="icon '.($htFinal?'ok':($htHasPhpFlag?'err':'warn')).'">'.($htFinal?'✓':($htHasPhpFlag?'✗':'⚠')).'</span><div><strong>.htaccess</strong><div style="font-size:12px;color:var(--'.($htFinal?'green':($htHasPhpFlag?'red':'amber')).'">'  .($htFinal?'OK — no php_flag directives ✓':($htHasPhpFlag?'Has php_flag/php_value — WILL CAUSE 500 on WAMP!':'Not found')).'</div></div></div>';
if($htHasPhpFlag) $allOk=false;
?>

  <?php if($allOk): ?>
  <div style="margin-top:22px;padding:16px 20px;background:var(--green-bg);border-radius:10px;border-left:4px solid var(--green);color:var(--green);font-weight:600">
    🎉 Everything looks good! Royale Vista v2 is ready.
  </div>
  <?php else: ?>
  <div style="margin-top:22px;padding:16px 20px;background:var(--red-bg);border-radius:10px;border-left:4px solid var(--red);color:var(--red);font-size:14px">
    ⚠ Some issues need fixing. Check the items marked above.
  </div>
  <?php endif; ?>

  <div style="margin-top:22px;display:flex;gap:10px;flex-wrap:wrap">
    <a href="<?= BASE ?>/index.php"       style="padding:10px 18px;border-radius:8px;background:linear-gradient(135deg,var(--gold),var(--gold-dk));color:#000;font-weight:600;text-decoration:none;font-size:13px">🌐 Hotel Home</a>
    <a href="<?= BASE ?>/rooms.php"       style="padding:10px 18px;border-radius:8px;background:var(--card2);border:1px solid var(--border);color:var(--text2);text-decoration:none;font-size:13px">🏨 Rooms</a>
    <a href="<?= BASE ?>/login.php"       style="padding:10px 18px;border-radius:8px;background:var(--card2);border:1px solid var(--border);color:var(--text2);text-decoration:none;font-size:13px">👤 Guest Login</a>
    <a href="<?= BASE ?>/admin/login.php" style="padding:10px 18px;border-radius:8px;background:var(--card2);border:1px solid var(--border);color:var(--text2);text-decoration:none;font-size:13px">🔐 Admin</a>
  </div>

  <div style="margin-top:16px;padding:12px 16px;background:var(--card2);border-radius:8px;font-size:12.5px;color:var(--text2)">
    <strong>Admin:</strong> admin@royalevista.com / <strong>password</strong><br>
    <strong>Guest:</strong> john@example.com / <strong>password</strong>
  </div>
</div>
</body>
</html>
