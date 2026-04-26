<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$code = strtoupper(clean(trim($_GET['code'] ?? '')));
if (!$code) {
    echo json_encode(['success' => false, 'error' => 'Please enter a code']);
    exit;
}

$q = $pdo->prepare("SELECT * FROM gift_cards WHERE code=? AND is_active=1 AND balance_usd>0 AND (expires_at IS NULL OR expires_at >= CURDATE()) LIMIT 1");
$q->execute([$code]);
$gc = $q->fetch();

header('Content-Type: application/json');
if ($gc) {
    echo json_encode(['success' => true, 'balance' => (float)$gc['balance_usd'], 'code' => $gc['code']]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid or empty gift card.']);
}
