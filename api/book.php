<?php
// ============================================================
//  ROYALE VISTA — Real-Time Booking API
//  Actions: availability | create | cancel | status | invoice
// ============================================================
error_reporting(0);
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/mailer.php';
header('Content-Type: application/json; charset=utf-8');

$action = clean($_POST['action'] ?? $_GET['action'] ?? 'availability');

// ── AVAILABILITY ──────────────────────────────────────────────
if ($action === 'availability') {
    // Free rooms from expired stays before computing availability.
    try {
        $expiredRefs = $pdo->query("SELECT booking_ref FROM bookings WHERE status IN ('confirmed','checked_in') AND check_out <= CURDATE()")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($expiredRefs as $exRef) {
            $pdo->prepare("UPDATE bookings SET status='checked_out' WHERE booking_ref=?")->execute([$exRef]);
            $idsStmt = $pdo->prepare("SELECT room_id FROM booking_room_assignments WHERE booking_ref=?");
            $idsStmt->execute([$exRef]);
            $ids = $idsStmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($ids)) {
                $ph = implode(',', array_fill(0, count($ids), '?'));
                $pdo->prepare("UPDATE rooms SET status='available' WHERE id IN ($ph)")->execute($ids);
            }
        }
    } catch (Exception $e) {}
    
    $checkin  = clean($_POST['checkin']  ?? '');
    $checkout = clean($_POST['checkout'] ?? '');
    $rooms    = json_decode($_POST['rooms'] ?? '[]', true); // [{id, qty}]

    if (!$checkin || !$checkout || strtotime($checkout) <= strtotime($checkin)) {
        echo json_encode(['ok' => false, 'error' => 'Invalid dates']); exit;
    }

    $nights  = max(1, nightsBetween($checkin, $checkout));
    $results = [];
    $totalUsd = 0;

    foreach ($rooms as $r) {
        $typeId = (int)$r['id'];
        $qty    = max(1, (int)($r['qty'] ?? 1));

        // Retrieve exact available room numbers
        $avStmt = $pdo->prepare("
            SELECT room_number FROM rooms
            WHERE room_type_id = ?
              AND status = 'available'
              AND id NOT IN (
                SELECT DISTINCT bra.room_id
                FROM booking_room_assignments bra
                JOIN bookings b ON b.booking_ref = bra.booking_ref
                WHERE bra.room_type_id = ?
                  AND b.status NOT IN ('cancelled','checked_out')
                  AND b.check_in <= ?
                  AND b.check_out > ?
              )
        ");
        $avStmt->execute([$typeId, $typeId, $checkout, $checkin]);
        $availRoomsList = $avStmt->fetchAll(PDO::FETCH_COLUMN);
        $availCount = count($availRoomsList);

        $rtStmt = $pdo->prepare("SELECT id,name,price_usd,max_guests,has_breakfast FROM room_types WHERE id=?");
        $rtStmt->execute([$typeId]);
        $rt = $rtStmt->fetch();

        if ($rt) {
            $roomTotal  = $rt['price_usd'] * $qty * $nights;
            $totalUsd  += $roomTotal;
            $isAvailable = $availCount >= $qty;
            $results[]  = [
                'id'           => $typeId,
                'name'         => $rt['name'],
                'price_usd'    => (float)$rt['price_usd'],
                'qty'          => $qty,
                'available'    => $isAvailable,
                'avail_count'  => $availCount,
                'avail_numbers'=> $availRoomsList,
                'nights'       => $nights,
                'room_total'   => $roomTotal,
                'price_fmt'    => formatPrice($rt['price_usd']),
                'total_fmt'    => formatPrice($roomTotal),
            ];
        }
    }

    // All rooms must be available
    $allAvailable = !empty($results) && array_reduce($results, fn($c, $r) => $c && $r['available'], true);

    // Apply offer if provided
    $offerCode   = strtoupper(clean($_POST['offer_code'] ?? ''));
    $discountUsd = 0;
    $offerMsg    = '';
    if ($offerCode) {
        $offerStmt = $pdo->prepare("SELECT * FROM offers WHERE code=? AND is_active=1 AND (valid_from IS NULL OR valid_from<=CURDATE()) AND (valid_to IS NULL OR valid_to>=CURDATE()) AND (uses_max IS NULL OR uses_count<uses_max) LIMIT 1");
        $offerStmt->execute([$offerCode]);
        $offer = $offerStmt->fetch();
        if ($offer) {
            $discountUsd = $offer['type'] === 'percent' ? $totalUsd * ($offer['value']/100) : min((float)$offer['value'], $totalUsd);
            $offerMsg = "Code applied: {$offer['value']}" . ($offer['type']==='percent'?'%':'$') . " off";
        } else {
            echo json_encode(['ok'=>false,'error'=>'Invalid or expired offer code']); exit;
        }
    }

    // Membership discount
    $memberDisc  = 0; $memberName = '';
    if (isLoggedIn()) {
        $memStmt = $pdo->prepare("SELECT m.name,m.discount_pct FROM user_memberships um JOIN memberships m ON um.membership_id=m.id WHERE um.user_id=? AND um.status='active' AND (um.expires_at IS NULL OR um.expires_at>NOW()) ORDER BY m.discount_pct DESC LIMIT 1");
        $memStmt->execute([$_SESSION['user_id']]);
        $mem = $memStmt->fetch();
        if ($mem) { $memberDisc = $totalUsd * ($mem['discount_pct']/100); $memberName = $mem['name']; }
    }

    // Loyalty discount
    $loyaltyPts  = max(0, min(5000, (int)($_POST['redeem_points'] ?? 0)));
    $loyaltyDisc = floor($loyaltyPts / 100); // $1 per 100 pts

    $totalDisc   = $discountUsd + $memberDisc + $loyaltyDisc;
    $afterDisc   = max(0, $totalUsd - $totalDisc);
    $taxes       = round($afterDisc * 0.18, 2);
    $finalUsd    = round($afterDisc + $taxes, 2);

    echo json_encode([
        'ok'           => true,
        'available'    => $allAvailable,
        'rooms'        => $results,
        'nights'       => $nights,
        'checkin'      => $checkin,
        'checkout'     => $checkout,
        'subtotal_usd' => $totalUsd,
        'subtotal_fmt' => formatPrice($totalUsd),
        'discount_usd' => $totalDisc,
        'discount_fmt' => formatPrice($totalDisc),
        'offer_msg'    => $offerMsg,
        'member_name'  => $memberName,
        'loyalty_save' => $loyaltyPts > 0 ? formatPrice($loyaltyDisc) : '',
        'taxes_usd'    => $taxes,
        'taxes_fmt'    => formatPrice($taxes),
        'final_usd'    => $finalUsd,
        'final_fmt'    => formatPrice($finalUsd),
        'currency'     => getUserCurrency(),
    ]);
    exit;
}

// ── CREATE BOOKING ────────────────────────────────────────────
if ($action === 'create') {
    if (!isLoggedIn()) {
        echo json_encode(['ok'=>false,'error'=>'Please log in to complete your booking.']); exit;
    }

    $guestName  = clean($_POST['guest_name']  ?? '');
    $guestEmail = clean($_POST['guest_email'] ?? '');
    $guestPhone = clean($_POST['guest_phone'] ?? '');
    $adults     = max(1,(int)($_POST['adults']??1));
    $children   = max(0,(int)($_POST['children']??0));
    $specialReq = clean($_POST['special_req'] ?? '');
    $checkin    = clean($_POST['checkin']  ?? '');
    $checkout   = clean($_POST['checkout'] ?? '');
    $payMethod  = in_array($_POST['pay_method']??'',['card','upi','hotel'])?$_POST['pay_method']:'hotel';
    $offerCode  = strtoupper(clean($_POST['offer_code'] ?? ''));
    $redeemPts  = max(0,min(5000,(int)($_POST['redeem_points']??0)));
    $rooms      = json_decode($_POST['rooms'] ?? '[]', true);

    // Validate
    if (!$guestName)                                { echo json_encode(['ok'=>false,'error'=>'Please enter your name.']); exit; }
    if (!filter_var($guestEmail,FILTER_VALIDATE_EMAIL)) { echo json_encode(['ok'=>false,'error'=>'Please enter a valid email address.']); exit; }
    if (empty($rooms))                              { echo json_encode(['ok'=>false,'error'=>'No rooms selected.']); exit; }
    if (!$checkin || !$checkout || strtotime($checkout)<=strtotime($checkin)) { echo json_encode(['ok'=>false,'error'=>'Invalid dates.']); exit; }

    $nights  = max(1,nightsBetween($checkin,$checkout));
    $subtotal = 0;
    $roomsConfirmed = [];

    foreach ($rooms as $r) {
        $typeId = (int)$r['id'];
        $qty    = max(1,(int)($r['qty']??1));
        $rt     = $pdo->prepare("SELECT * FROM room_types WHERE id=?"); $rt->execute([$typeId]); $rt = $rt->fetch();
        if (!$rt) { echo json_encode(['ok'=>false,'error'=>"Room type #{$typeId} not found."]); exit; }
        $rTotal   = $rt['price_usd'] * $qty * $nights;
        $subtotal += $rTotal;
        $roomsConfirmed[] = ['id'=>$typeId,'name'=>$rt['name'],'price_usd'=>(float)$rt['price_usd'],'qty'=>$qty,'nights'=>$nights,'total'=>$rTotal];
    }

    $discountAmt = 0;
    // Offer
    $offer = null;
    if ($offerCode) {
        $ofS = $pdo->prepare("SELECT * FROM offers WHERE code=? AND is_active=1 AND (valid_from IS NULL OR valid_from<=CURDATE()) AND (valid_to IS NULL OR valid_to>=CURDATE()) LIMIT 1");
        $ofS->execute([$offerCode]); $offer = $ofS->fetch();
        if ($offer) $discountAmt += $offer['type']==='percent' ? $subtotal*($offer['value']/100) : min((float)$offer['value'],$subtotal);
    }
    // Member
    $memberNumber = '';
    $memS = $pdo->prepare("SELECT um.member_number,m.discount_pct FROM user_memberships um JOIN memberships m ON um.membership_id=m.id WHERE um.user_id=? AND um.status='active' ORDER BY m.discount_pct DESC LIMIT 1");
    $memS->execute([$_SESSION['user_id']]); $mem = $memS->fetch();
    if ($mem) { $discountAmt += $subtotal*($mem['discount_pct']/100); $memberNumber = $mem['member_number']; }
    // Loyalty
    $loyaltyDisc = floor($redeemPts/100);
    $discountAmt += $loyaltyDisc;

    $afterDisc = max(0, $subtotal - $discountAmt);
    $taxes     = round($afterDisc * 0.18, 2);
    $finalUsd  = round($afterDisc + $taxes, 2);
    $paidAt    = $payMethod !== 'hotel' ? date('Y-m-d H:i:s') : null;
    $payStatus = $payMethod !== 'hotel' ? 'paid' : 'pending';
    $bookRef   = generateRef('BK');
    $invoiceNo = generateRef('INV');
    $uid       = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        $pdo->prepare("INSERT INTO bookings (booking_ref,invoice_no,user_id,guest_name,guest_email,guest_phone,check_in,check_out,nights,adults,children,special_req,total_usd,discount_usd,taxes_usd,final_usd,currency,offer_code,member_number,pay_method,pay_status,paid_at,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$bookRef,$invoiceNo,$uid,$guestName,$guestEmail,$guestPhone,$checkin,$checkout,$nights,$adults,$children,$specialReq,$subtotal,round($discountAmt,2),$taxes,$finalUsd,getUserCurrency(),$offerCode?:null,$memberNumber?:null,$payMethod,$payStatus,$paidAt,'confirmed']);

        foreach ($roomsConfirmed as $r) {
            $pdo->prepare("INSERT INTO booked_rooms (booking_ref,room_type_id,room_type_name,quantity,price_usd,nights,total_usd) VALUES (?,?,?,?,?,?,?)")
                ->execute([$bookRef,$r['id'],$r['name'],$r['qty'],$r['price_usd'],$r['nights'],$r['total']]);

            // Assign specific physical rooms that are not already reserved for overlapping dates.
            $assignStmt = $pdo->prepare("
                SELECT rr.id, rr.room_number
                FROM rooms rr
                WHERE rr.room_type_id = ?
                  AND rr.status = 'available'
                  AND rr.id NOT IN (
                    SELECT DISTINCT bra.room_id
                    FROM booking_room_assignments bra
                    JOIN bookings b ON b.booking_ref = bra.booking_ref
                    WHERE bra.room_type_id = ?
                      AND b.status NOT IN ('cancelled','checked_out')
                      AND b.check_in <= ?
                      AND b.check_out > ?
                  )
                ORDER BY rr.room_number
                LIMIT ".(int)$r['qty']);
            $assignStmt->execute([$r['id'], $r['id'], $checkout, $checkin]);
            $assignedRooms = $assignStmt->fetchAll();
            if (count($assignedRooms) < (int)$r['qty']) {
                throw new Exception("Insufficient room inventory for {$r['name']}.");
            }
            foreach ($assignedRooms as $ar) {
                $pdo->prepare("INSERT INTO booking_room_assignments (booking_ref,room_id,room_number,room_type_id,room_type_name) VALUES (?,?,?,?,?)")
                    ->execute([$bookRef, $ar['id'], $ar['room_number'], $r['id'], $r['name']]);
                $pdo->prepare("UPDATE rooms SET status='occupied' WHERE id=?")->execute([$ar['id']]);
            }
        }

        if ($offer) $pdo->prepare("UPDATE offers SET uses_count=uses_count+1 WHERE code=?")->execute([$offerCode]);

        // Loyalty
        $pdo->prepare("INSERT IGNORE INTO loyalty_points (user_id,total_points,lifetime_points,tier) VALUES (?,0,0,'bronze')")->execute([$uid]);
        if ($redeemPts > 0) $pdo->prepare("UPDATE loyalty_points SET total_points=GREATEST(0,total_points-?) WHERE user_id=?")->execute([$redeemPts,$uid]);
        $earnPts = (int)round($finalUsd * 10);
        $pdo->prepare("UPDATE loyalty_points SET total_points=total_points+?,lifetime_points=lifetime_points+? WHERE user_id=?")->execute([$earnPts,$earnPts,$uid]);
        $pdo->prepare("INSERT INTO loyalty_transactions (user_id,booking_ref,type,points,balance_after,description) SELECT ?,?,'earn',?,(SELECT total_points FROM loyalty_points WHERE user_id=?),?")->execute([$uid,$bookRef,$earnPts,$uid,"Earned for $bookRef"]);

        $pdo->commit();

        // Send booking confirmation email
        try {
            $bookingData = [
                'booking_ref' => $bookRef,
                'invoice_no'  => $invoiceNo,
                'guest_name'  => $guestName,
                'guest_email' => $guestEmail,
                'guest_phone' => $guestPhone,
                'check_in'    => $checkin,
                'check_out'   => $checkout,
                'nights'      => $nights,
                'adults'      => $adults,
                'children'    => $children,
                'special_req' => $specialReq,
                'pay_method'  => $payMethod,
                'pay_status'  => $payStatus,
                'paid_at'     => $paidAt,
                'total_usd'   => $subtotal,
                'discount_usd'=> round($discountAmt,2),
                'taxes_usd'   => $taxes,
                'final_usd'   => $finalUsd,
                'offer_code'  => $offerCode,
                'member_number'=> $memberNumber,
            ];
            sendBookingConfirmationEmail($guestEmail, $guestName, $bookingData, $roomsConfirmed);
        } catch (\Exception $e) { error_log('[Mailer] ' . $e->getMessage()); }

        // Send booking notification
        try {
            $pdo->prepare("INSERT INTO notifications (user_id,type,title,message,link) VALUES (?,?,?,?,?)")
                ->execute([$uid,'booking','Booking Confirmed — '.$bookRef,"Your reservation has been confirmed for {$nights} night(s). Check-in: {$checkin}.",BASE.'/bookings.php']);
        } catch(Exception $e) {}

        // Trigger immediate availability recalculation for affected room types
        try {
            $refreshTypes = array_unique(array_column($roomsConfirmed, 'id'));
            foreach ($refreshTypes as $typeId) {
                // Update cached availability count for this room type
                $refreshStmt = $pdo->prepare("
                    SELECT COUNT(*) as available_count
                    FROM rooms r
                    WHERE r.room_type_id = ?
                      AND r.status = 'available'
                      AND r.id NOT IN (
                        SELECT DISTINCT bra.room_id
                        FROM booking_room_assignments bra
                        JOIN bookings b ON b.booking_ref = bra.booking_ref
                        WHERE bra.room_type_id = ?
                          AND b.status NOT IN ('cancelled','checked_out')
                          AND b.check_in <= ?
                          AND b.check_out > ?
                      )
                ");
                $refreshStmt->execute([$typeId, $typeId, $checkout, $checkin]);
                $newCount = $refreshStmt->fetchColumn();
                
                // Log availability change
                error_log("BOOKING UPDATE: Room type $typeId now has $newCount rooms available after booking $bookRef");
                
                // Trigger real-time update via file-based signaling
                $updateFile = dirname(__DIR__) . '/temp/availability_update.json';
                file_put_contents($updateFile, json_encode([
                    'type' => 'booking_created',
                    'timestamp' => time(),
                    'booking_ref' => $bookRef,
                    'room_types' => $refreshTypes,
                    'availability' => ['room_type_id' => $typeId, 'count' => $newCount]
                ]));
            }
        } catch (Exception $e) {
            error_log("Failed to refresh availability after booking: " . $e->getMessage());
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok'=>false,'error'=>'Booking failed: '.$e->getMessage()]); exit;
    }

    echo json_encode([
        'ok'         => true,
        'booking_ref'=> $bookRef,
        'invoice_no' => $invoiceNo,
        'guest_name' => $guestName,
        'guest_email'=> $guestEmail,
        'checkin'    => $checkin,
        'checkout'   => $checkout,
        'nights'     => $nights,
        'rooms'      => $roomsConfirmed,
        'subtotal'   => formatPrice($subtotal),
        'discount'   => formatPrice(round($discountAmt,2)),
        'taxes'      => formatPrice($taxes),
        'final'      => formatPrice($finalUsd),
        'final_usd'  => $finalUsd,
        'pay_method' => $payMethod,
        'pay_status' => $payStatus,
        'earned_pts' => $earnPts,
        'invoice_url'=> BASE . '/invoice.php?ref=' . urlencode($bookRef),
    ]);
    exit;
}

// ── CANCEL BOOKING ────────────────────────────────────────────
if ($action === 'cancel') {
    if (!isLoggedIn()) { echo json_encode(['ok'=>false,'error'=>'Not logged in']); exit; }
    $ref = clean($_POST['booking_ref'] ?? '');
    if (!$ref) { echo json_encode(['ok'=>false,'error'=>'No booking reference']); exit; }

    $bk = $pdo->prepare("SELECT * FROM bookings WHERE booking_ref=? AND user_id=? AND status IN ('confirmed','pending')");
    $bk->execute([$ref, $_SESSION['user_id']]);
    $booking = $bk->fetch();

    if (!$booking) { echo json_encode(['ok'=>false,'error'=>'Booking not found or already cancelled']); exit; }

    // Use assignments to precisely release rooms
    $brStmt = $pdo->prepare("SELECT room_id FROM booking_room_assignments WHERE booking_ref=?");
    $brStmt->execute([$ref]);
    $assignedIds = $brStmt->fetchAll(PDO::FETCH_COLUMN);
    $pdo->prepare("UPDATE bookings SET status='cancelled' WHERE booking_ref=?")->execute([$ref]);
    if (!empty($assignedIds)) {
        $placeholders = implode(',', array_fill(0, count($assignedIds), '?'));
        $pdo->prepare("UPDATE rooms SET status='available' WHERE id IN ($placeholders)")->execute($assignedIds);
    }

    // Refund loyalty points (partial)
    $refundPts = (int)round($booking['final_usd'] * 10 * 0.5); // 50% refund on points
    if ($refundPts > 0) {
        $pdo->prepare("UPDATE loyalty_points SET total_points=total_points-? WHERE user_id=? AND total_points>=?")->execute([$refundPts,$_SESSION['user_id'],$refundPts]);
    }


    // Send cancellation notification
    try {
        $pdo->prepare("INSERT INTO notifications (user_id,type,title,message,link) VALUES (?,?,?,?,?)")
            ->execute([$_SESSION['user_id'],'cancellation','Booking Cancelled — '.$ref,"Your booking {$ref} has been cancelled successfully.",BASE.'/bookings.php']);
    } catch(Exception $e) {}

    // Send cancellation email
    try {
        sendCancellationEmail($booking['guest_email'], $booking['guest_name'], $booking);
    } catch (\Exception $e) { error_log('[Mailer] ' . $e->getMessage()); }

    // Trigger immediate availability recalculation for cancelled booking room types
    try {
        $cancelledTypes = $pdo->prepare("
            SELECT DISTINCT bra.room_type_id 
            FROM booking_room_assignments bra 
            WHERE bra.booking_ref = ?
        ");
        $cancelledTypes->execute([$ref]);
        $affectedTypes = $cancelledTypes->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($affectedTypes as $typeId) {
            // Recalculate availability for this room type
            $refreshStmt = $pdo->prepare("
                SELECT COUNT(*) as available_count
                FROM rooms r
                WHERE r.room_type_id = ?
                  AND r.status = 'available'
                  AND r.id NOT IN (
                    SELECT DISTINCT bra.room_id
                    FROM booking_room_assignments bra
                    JOIN bookings b ON b.booking_ref = bra.booking_ref
                    WHERE bra.room_type_id = ?
                      AND b.status NOT IN ('cancelled','checked_out')
                      AND b.check_in <= CURDATE()
                      AND b.check_out > CURDATE()
                  )
            ");
            $refreshStmt->execute([$typeId, $typeId]);
            $newCount = $refreshStmt->fetchColumn();
            
            // Log the availability change
            error_log("CANCELLATION UPDATE: Room type $typeId now has $newCount rooms available after cancelling $ref");
            
            // Trigger real-time update via file-based signaling
            $updateFile = dirname(__DIR__) . '/../temp/availability_update.json';
            file_put_contents($updateFile, json_encode([
                'type' => 'booking_cancelled',
                'timestamp' => time(),
                'booking_ref' => $ref,
                'room_types' => $affectedTypes,
                'availability' => ['room_type_id' => $typeId, 'count' => $newCount]
            ]));
        }
    } catch (Exception $e) {
        error_log("Failed to refresh availability after cancellation: " . $e->getMessage());
    }

    echo json_encode(['ok'=>true,'message'=>"Booking $ref cancelled successfully.",'booking_ref'=>$ref]);
    exit;
}

// ── BOOKING STATUS ────────────────────────────────────────────
if ($action === 'status') {
    if (!isLoggedIn()) { echo json_encode(['ok'=>false,'error'=>'Not logged in']); exit; }
    $ref = clean($_GET['ref'] ?? clean($_POST['ref'] ?? ''));
    if (!$ref) { echo json_encode(['ok'=>false,'error'=>'No ref']); exit; }

    $bk = $pdo->prepare("SELECT b.*,GROUP_CONCAT(br.room_type_name ORDER BY br.id SEPARATOR ', ') as rooms FROM bookings b LEFT JOIN booked_rooms br ON br.booking_ref=b.booking_ref WHERE b.booking_ref=? AND (b.user_id=? OR ?=1) GROUP BY b.id");
    $bk->execute([$ref,$_SESSION['user_id'],isAdmin()?1:0]);
    $booking = $bk->fetch();

    if (!$booking) { echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }
    echo json_encode(['ok'=>true,'booking'=>$booking,'rooms_str'=>$booking['rooms']]);
    exit;
}

// ── USER BOOKINGS LIST ────────────────────────────────────────
if ($action === 'my_bookings') {
    if (!isLoggedIn()) { echo json_encode(['ok'=>false,'error'=>'Not logged in']); exit; }
    $filter = clean($_GET['filter'] ?? 'all');
    $where  = $filter === 'all' ? '' : " AND b.status='{$filter}'";
    $bkS = $pdo->prepare("SELECT b.*,GROUP_CONCAT(CONCAT(br.quantity,'x ',br.room_type_name) SEPARATOR ', ') as rooms_str FROM bookings b LEFT JOIN booked_rooms br ON br.booking_ref=b.booking_ref WHERE b.user_id=? {$where} GROUP BY b.id ORDER BY b.created_at DESC LIMIT 50");
    $bkS->execute([$_SESSION['user_id']]);
    echo json_encode(['ok'=>true,'bookings'=>$bkS->fetchAll()]);
    exit;
}

// ── ADMIN: UPDATE STATUS ──────────────────────────────────────
if ($action === 'admin_status') {
    if (!isAdmin()) { echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit; }
    $ref    = clean($_POST['booking_ref'] ?? '');
    $status = clean($_POST['status'] ?? '');
    $allowed = ['confirmed','cancelled','checked_in','checked_out'];
    if (!$ref || !in_array($status,$allowed)) { echo json_encode(['ok'=>false,'error'=>'Invalid']); exit; }
    $pdo->prepare("UPDATE bookings SET status=? WHERE booking_ref=?")->execute([$status,$ref]);
    logActivity('update_booking_status', "Changed status of booking $ref to $status");
    echo json_encode(['ok'=>true,'status'=>$status,'ref'=>$ref]);
    exit;
}

// ── ROOM STATUS (admin) ───────────────────────────────────────
if ($action === 'room_status') {
    if (!isAdmin()) { echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit; }
    $roomId = (int)($_POST['room_id'] ?? 0);
    $status = clean($_POST['status'] ?? '');
    $allowed = ['available','occupied','maintenance'];
    if (!$roomId || !in_array($status,$allowed)) { echo json_encode(['ok'=>false,'error'=>'Invalid']); exit; }
    $pdo->prepare("UPDATE rooms SET status=? WHERE id=?")->execute([$status,$roomId]);
    logActivity('update_room_status', "Changed status of room #$roomId to $status");
    echo json_encode(['ok'=>true,'status'=>$status,'room_id'=>$roomId]);
    exit;
}

// ── ROOM STATUS GET (admin) ────────────────────────────────────
if ($action === 'room_status_get') {
    if (!isAdmin()) { echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit; }
    $roomId = (int)($_GET['room_id'] ?? 0);
    if (!$roomId) { echo json_encode(['ok'=>false,'error'=>'No room_id']); exit; }
    $r = $pdo->prepare("SELECT id,room_number,status,room_type_id FROM rooms WHERE id=?");
    $r->execute([$roomId]);
    $room = $r->fetch();
    if (!$room) { echo json_encode(['ok'=>false,'error'=>'Room not found']); exit; }
    echo json_encode(['ok'=>true,'room'=>$room]);
    exit;
}

echo json_encode(['ok'=>false,'error'=>'Unknown action: '.$action]);