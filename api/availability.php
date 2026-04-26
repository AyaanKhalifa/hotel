<?php
error_reporting(0);
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
header('Content-Type: application/json');

// Auto-checkout expired stays so rooms are freed before availability checks
try {
    $expired = $pdo->query("SELECT booking_ref FROM bookings WHERE status IN ('confirmed','checked_in') AND check_out <= CURDATE()")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($expired as $exRef) {
        $pdo->prepare("UPDATE bookings SET status='checked_out' WHERE booking_ref=?")->execute([$exRef]);
        $ids = $pdo->prepare("SELECT room_id FROM booking_room_assignments WHERE booking_ref=?");
        $ids->execute([$exRef]);
        $roomIds = $ids->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($roomIds)) {
            $ph = implode(',', array_fill(0, count($roomIds), '?'));
            $pdo->prepare("UPDATE rooms SET status='available' WHERE id IN ($ph)")->execute($roomIds);
        }
    }
} catch (Exception $e) {}

$checkin      = clean($_POST['checkin']  ?? '');
$checkout     = clean($_POST['checkout'] ?? '');
$typeId       = (int)($_POST['room_type_id'] ?? 0);

if (!$checkin || !$checkout || !$typeId) {
    echo json_encode(['error'=>'Invalid parameters']); exit;
}
if (new DateTime($checkout) <= new DateTime($checkin)) {
    echo json_encode(['error'=>'Check-out must be after check-in']); exit;
}

$nights = nightsBetween($checkin, $checkout);
$rt = $pdo->prepare("SELECT * FROM room_types WHERE id=?");
$rt->execute([$typeId]);
$room = $rt->fetch();
if (!$room) { echo json_encode(['error'=>'Room not found']); exit; }

// Count truly available rooms for the requested dates
$avail = $pdo->prepare("
    SELECT COUNT(*) FROM rooms
    WHERE room_type_id = ?
      AND status = 'available'
      AND id NOT IN (
          SELECT DISTINCT ra.id
          FROM rooms ra
          JOIN booked_rooms br ON br.room_type_id = ra.room_type_id
          JOIN bookings b ON b.booking_ref = br.booking_ref
          WHERE ra.room_type_id = ?
            AND b.status NOT IN ('cancelled','checked_out')
            AND b.check_in  < ?
            AND b.check_out > ?
      )
");
$avail->execute([$typeId, $typeId, $checkout, $checkin]);
$count = (int)$avail->fetchColumn();

echo json_encode([
    'available' => $count > 0,
    'count'     => $count,
    'room'      => $room['name'],
    'price'     => formatPrice($room['price_usd']),
    'price_usd' => (float)$room['price_usd'],
    'nights'    => $nights,
    'total'     => formatPrice($room['price_usd'] * $nights),
    'total_usd' => $room['price_usd'] * $nights,
]);
