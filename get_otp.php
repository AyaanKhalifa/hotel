<?php
require 'includes/db.php';
$s = $pdo->prepare('SELECT reset_otp FROM users WHERE email = ?');
$s->execute(['admin@royalevista.com']);
echo "OTP:" . $s->fetchColumn();
?>
