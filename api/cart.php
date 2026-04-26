<?php
error_reporting(0);
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
header('Content-Type: application/json');

$action     = clean($_POST['action'] ?? $_GET['action'] ?? 'get');
$sessionKey = clean(session_id() . '_' . ($_SESSION['user_id'] ?? 'guest'));

// ── Helper: get cart items ────────────────────────────────────
function getCartItems(PDO $pdo, string $sessionKey): array {
    $q = $pdo->prepare("
        SELECT rc.*, rt.name, rt.price_usd, rt.max_guests,
               ri.image_url
        FROM room_cart rc
        JOIN room_types rt ON rc.room_type_id = rt.id
        LEFT JOIN room_images ri ON ri.room_type_id = rt.id AND ri.is_primary = 1
        WHERE rc.session_key = ?
        ORDER BY rc.added_at
    ");
    $q->execute([$sessionKey]);
    $items = $q->fetchAll();
    $emojis = [1=>'🛏',2=>'🛋',3=>'🏡',4=>'👑'];
    foreach ($items as &$item) {
        $item['emoji'] = $emojis[$item['room_type_id']] ?? '🏨';
    }
    return $items;
}

function getCartDates(PDO $pdo, string $sessionKey): array {
    $q = $pdo->prepare("SELECT check_in, check_out FROM room_cart WHERE session_key=? LIMIT 1");
    $q->execute([$sessionKey]);
    $row = $q->fetch();
    return ['checkin' => $row['check_in'] ?? '', 'checkout' => $row['check_out'] ?? ''];
}

switch ($action) {
    case 'get':
        $items = getCartItems($pdo, $sessionKey);
        $dates = getCartDates($pdo, $sessionKey);
        echo json_encode(['items' => $items, 'checkin' => $dates['checkin'], 'checkout' => $dates['checkout']]);
        break;

    case 'add':
        $roomTypeId = (int)($_POST['room_type_id'] ?? 0);
        $quantity   = max(1, (int)($_POST['quantity'] ?? 1));
        $checkin    = clean($_POST['check_in']  ?? date('Y-m-d'));
        $checkout   = clean($_POST['check_out'] ?? date('Y-m-d', strtotime('+2 days')));
        $guests     = (int)($_POST['guests'] ?? 2);

        if (!$roomTypeId) { echo json_encode(['error' => 'Invalid room']); break; }

        // Validate dates
        if (strtotime($checkout) <= strtotime($checkin)) {
            echo json_encode(['error' => 'Invalid dates']); break;
        }

        // Verify room exists
        $rt = $pdo->prepare("SELECT id FROM room_types WHERE id=?");
        $rt->execute([$roomTypeId]);
        if (!$rt->fetch()) { echo json_encode(['error' => 'Room not found']); break; }

        // Check if already in cart - update quantity
        $existing = $pdo->prepare("SELECT id, quantity FROM room_cart WHERE session_key=? AND room_type_id=?");
        $existing->execute([$sessionKey, $roomTypeId]);
        $row = $existing->fetch();

        if ($row) {
            $newQty = min($row['quantity'] + $quantity, 10);
            $pdo->prepare("UPDATE room_cart SET quantity=?, check_in=?, check_out=?, guests=? WHERE id=?")
                ->execute([$newQty, $checkin, $checkout, $guests, $row['id']]);
        } else {
            // Update other cart items' dates to match
            $pdo->prepare("UPDATE room_cart SET check_in=?, check_out=? WHERE session_key=?")
                ->execute([$checkin, $checkout, $sessionKey]);

            $pdo->prepare("INSERT INTO room_cart (session_key, room_type_id, quantity, check_in, check_out, guests) VALUES (?,?,?,?,?,?)")
                ->execute([$sessionKey, $roomTypeId, $quantity, $checkin, $checkout, $guests]);
        }

        $items = getCartItems($pdo, $sessionKey);
        $dates = getCartDates($pdo, $sessionKey);
        echo json_encode(['success' => true, 'items' => $items, 'checkin' => $dates['checkin'], 'checkout' => $dates['checkout']]);
        break;

    case 'remove':
        $roomTypeId = (int)($_POST['room_type_id'] ?? 0);
        $pdo->prepare("DELETE FROM room_cart WHERE session_key=? AND room_type_id=?")->execute([$sessionKey, $roomTypeId]);
        $items = getCartItems($pdo, $sessionKey);
        $dates = getCartDates($pdo, $sessionKey);
        echo json_encode(['success' => true, 'items' => $items, 'checkin' => $dates['checkin'], 'checkout' => $dates['checkout']]);
        break;

    case 'qty':
        $roomTypeId = (int)($_POST['room_type_id'] ?? 0);
        $delta      = (int)($_POST['delta'] ?? 0);
        $existing   = $pdo->prepare("SELECT id, quantity FROM room_cart WHERE session_key=? AND room_type_id=?");
        $existing->execute([$sessionKey, $roomTypeId]);
        $row = $existing->fetch();
        if ($row) {
            $newQty = max(0, $row['quantity'] + $delta);
            if ($newQty === 0) {
                $pdo->prepare("DELETE FROM room_cart WHERE id=?")->execute([$row['id']]);
            } else {
                $pdo->prepare("UPDATE room_cart SET quantity=? WHERE id=?")->execute([$newQty, $row['id']]);
            }
        }
        $items = getCartItems($pdo, $sessionKey);
        $dates = getCartDates($pdo, $sessionKey);
        echo json_encode(['success' => true, 'items' => $items, 'checkin' => $dates['checkin'], 'checkout' => $dates['checkout']]);
        break;

    case 'clear':
        $pdo->prepare("DELETE FROM room_cart WHERE session_key=?")->execute([$sessionKey]);
        echo json_encode(['success' => true, 'items' => [], 'checkin' => '', 'checkout' => '']);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
?>
