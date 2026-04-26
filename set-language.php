<?php
require_once __DIR__ . '/includes/config.php';
$lang = $_GET['lang'] ?? 'en';
if (isset(LANGUAGES[$lang])) {
    $_SESSION['language'] = $lang;
    setcookie('rv_lang', $lang, time()+86400*365, '/', '', false, false);
    if (!empty($_SESSION['user_id'])) {
        require_once __DIR__ . '/includes/db.php';
        $pdo->prepare("UPDATE users SET language=? WHERE id=?")->execute([$lang, $_SESSION['user_id']]);
    }
}
$ret = $_GET['ret'] ?? '';
if (!$ret || (strpos($ret, '/') !== 0 && strpos($ret, BASE) !== 0)) $ret = BASE . '/index.php';
session_write_close();
header('Location: ' . $ret); exit;
