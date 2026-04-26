<?php
error_reporting(0);
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
header('Content-Type: application/json');

$action = clean($_POST['action'] ?? $_GET['action'] ?? 'validate');

if ($action === 'validate') {
    $code     = strtoupper(clean($_POST['code'] ?? ''));
    $subtotal = (float)($_POST['subtotal'] ?? 0);

    if (!$code) { echo json_encode(['valid'=>false,'error'=>'No code entered']); exit; }

    $stmt = $pdo->prepare("SELECT * FROM offers WHERE code=? AND is_active=1 AND (valid_from IS NULL OR valid_from<=CURDATE()) AND (valid_to IS NULL OR valid_to>=CURDATE()) AND (uses_max IS NULL OR uses_count<uses_max) LIMIT 1");
    $stmt->execute([$code]);
    $offer = $stmt->fetch();

    if (!$offer) {
        echo json_encode(['valid'=>false,'error'=>'Invalid or expired code']);
        exit;
    }

    $discount = $offer['type']==='percent'
        ? round($subtotal * ($offer['value']/100), 2)
        : min((float)$offer['value'], $subtotal);

    echo json_encode([
        'valid'       => true,
        'code'        => $code,
        'discount'    => $discount,
        'type'        => $offer['type'],
        'value'       => $offer['value'],
        'description' => $offer['description'] ?? $code,
    ]);
} elseif ($action === 'list') {
    $offers = $pdo->query("SELECT code,type,value,description FROM offers WHERE is_active=1 AND (valid_to IS NULL OR valid_to>=CURDATE()) ORDER BY value DESC")->fetchAll();
    echo json_encode(['offers' => $offers]);
} else {
    echo json_encode(['error' => 'Unknown action']);
}
?>
