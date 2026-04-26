<?php
error_reporting(0);
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode(['error'=>'Login required']); exit; }
$roomTypeId = (int)($_POST['room_type_id'] ?? 0);
if (!$roomTypeId) { echo json_encode(['error'=>'Invalid']); exit; }
$uid = $_SESSION['user_id'];
$exists = $pdo->prepare("SELECT id FROM wishlists WHERE user_id=? AND room_type_id=?");
$exists->execute([$uid,$roomTypeId]);
if ($exists->fetch()) {
    $pdo->prepare("DELETE FROM wishlists WHERE user_id=? AND room_type_id=?")->execute([$uid,$roomTypeId]);
    echo json_encode(['wishlisted'=>false]);
} else {
    $pdo->prepare("INSERT INTO wishlists (user_id,room_type_id) VALUES (?,?)")->execute([$uid,$roomTypeId]);
    echo json_encode(['wishlisted'=>true]);
}
