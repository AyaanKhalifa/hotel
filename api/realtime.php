<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Cache-Control');

// Function to get current availability
function getCurrentAvailability($pdo) {
    $stmt = $pdo->query("
        SELECT rt.id, rt.name, 
            COUNT(CASE WHEN r.status = 'available' AND r.id NOT IN (
                SELECT DISTINCT bra.room_id
                FROM booking_room_assignments bra
                JOIN bookings b ON b.booking_ref = bra.booking_ref
                WHERE bra.room_type_id = rt.id
                  AND b.status NOT IN ('cancelled','checked_out')
                  AND b.check_in <= CURDATE()
                  AND b.check_out > CURDATE()
            ) THEN 1 END) as available_count
        FROM room_types rt
        LEFT JOIN rooms r ON r.room_type_id = rt.id
        GROUP BY rt.id, rt.name
        ORDER BY rt.id
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get recent booking activity
function getRecentActivity($pdo, $lastCheck) {
    $stmt = $pdo->prepare("
        SELECT booking_ref, status, created_at, updated_at,
               CASE 
                 WHEN created_at > ? THEN 'created'
                 WHEN status = 'cancelled' AND updated_at > ? THEN 'cancelled'
                 ELSE 'updated'
               END as activity_type
        FROM bookings 
        WHERE (created_at > ? OR (status = 'cancelled' AND updated_at > ?))
        ORDER BY created_at DESC, updated_at DESC
        LIMIT 10
    ");
    $stmt->execute([$lastCheck, $lastCheck, $lastCheck, $lastCheck]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Initial state
$availability = getCurrentAvailability($pdo);
echo "data: " . json_encode(['type' => 'initial', 'data' => $availability]) . "\n\n";
flush();

$lastAvailability = $availability;
$lastCheck = date('Y-m-d H:i:s');

// Poll for changes every 2 seconds
$maxLoops = 300; // 10 minutes max

for ($i = 0; $i < $maxLoops; $i++) {
    sleep(2);
    
    // Check for recent activity
    $currentCheck = date('Y-m-d H:i:s');
    $recentActivity = getRecentActivity($pdo, $lastCheck);
    
    if (!empty($recentActivity)) {
        echo "data: " . json_encode([
            'type' => 'booking_activity', 
            'timestamp' => time(),
            'activity' => $recentActivity
        ]) . "\n\n";
        flush();
        
        // Trigger availability refresh after activity
        $currentAvailability = getCurrentAvailability($pdo);
        $changes = [];
        
        foreach ($currentAvailability as $room) {
            $roomId = $room['id'];
            $lastCount = $lastAvailability[$roomId]['available_count'] ?? 0;
            $currentCount = $room['available_count'];
            
            if ($lastCount !== $currentCount) {
                $changes[] = [
                    'room_type_id' => $roomId,
                    'room_name' => $room['name'],
                    'old_count' => $lastCount,
                    'new_count' => $currentCount,
                    'change' => $currentCount > $lastCount ? 'increased' : 'decreased'
                ];
            }
        }
        
        if (!empty($changes)) {
            echo "data: " . json_encode([
                'type' => 'availability_change', 
                'timestamp' => time(),
                'changes' => $changes,
                'availability' => $currentAvailability
            ]) . "\n\n";
            flush();
            $lastAvailability = $currentAvailability;
        }
        
        $lastCheck = $currentCheck;
    }
    
    // Send periodic heartbeat
    if ($i % 15 === 0) { // Every 30 seconds
        echo "data: " . json_encode(['type' => 'heartbeat', 'timestamp' => time()]) . "\n\n";
        flush();
    }
}

echo "data: " . json_encode(['type' => 'timeout']) . "\n\n";
flush();
?>
