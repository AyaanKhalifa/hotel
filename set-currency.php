<?php
require_once __DIR__ . '/includes/config.php';
$currency = strtoupper($_GET['currency'] ?? 'USD');
if (isset(CURRENCIES[$currency])) {
    $_SESSION['currency'] = $currency;
    setcookie('rv_currency', $currency, time()+86400*365, '/', '', false, false);
    if (!empty($_SESSION['user_id'])) {
        require_once __DIR__ . '/includes/db.php';
        $pdo->prepare("UPDATE users SET currency=? WHERE id=?")->execute([$currency, $_SESSION['user_id']]);
    }
}
$ret = $_GET['ret'] ?? '';
if (!$ret || (strpos($ret, '/') !== 0 && strpos($ret, BASE) !== 0)) $ret = BASE . '/index.php';
session_write_close();
header('Location: ' . $ret); exit;
