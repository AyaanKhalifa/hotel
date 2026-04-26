<?php
error_reporting(E_ALL);
ini_set('display_errors','1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Staff Attendance — Royale Vista';

// Check if admin
if (!isAdmin()) {
    header('Location: ' . BASE . '/login.php');
    exit;
}

// Handle actions
$action = $_GET['action'] ?? 'dashboard';

// Check in staff
if ($action === 'checkin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $staffId = (int)($_POST['staff_id'] ?? 0);
    $date = clean($_POST['date'] ?? date('Y-m-d'));
    $checkInTime = clean($_POST['checkin_time'] ?? date('H:i:s'));
    $notes = clean($_POST['notes'] ?? '');
    
    if ($staffId && $date) {
        // Check if already checked in
        $stmt = $pdo->prepare("SELECT id FROM attendance WHERE staff_id=? AND date=? AND checkin_time IS NOT NULL");
        $stmt->execute([$staffId, $date]);
        if (!$stmt->fetch()) {
            $insertStmt = $pdo->prepare("INSERT INTO attendance (staff_id,date,checkin_time,notes) VALUES (?,?,?)");
            $insertStmt->execute([$staffId, $date, $checkInTime, $notes]);
            $_SESSION['success'] = 'Staff member checked in successfully';
        } else {
            $_SESSION['error'] = 'Staff member already checked in today';
        }
    }
    header('Location: ' . BASE . '/attendance.php');
    exit;
}

// Check out staff
if ($action === 'checkout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $attendanceId = (int)($_POST['attendance_id'] ?? 0);
    $checkOutTime = clean($_POST['checkout_time'] ?? date('H:i:s'));
    $notes = clean($_POST['notes'] ?? '');
    
    if ($attendanceId) {
        $updateStmt = $pdo->prepare("UPDATE attendance SET checkout_time=?, notes=? WHERE id=?");
        $updateStmt->execute([$checkOutTime, $notes, $attendanceId]);
        $_SESSION['success'] = 'Staff member checked out successfully';
    }
    header('Location: ' . BASE . '/attendance.php');
    exit;
}

// Get attendance data
function getAttendanceData($pdo, $date = null) {
    if (!$date) $date = date('Y-m-d');
    
    $stmt = $pdo->prepare("
        SELECT a.*, s.name, s.position, s.department,
               CASE 
                 WHEN a.checkout_time IS NULL THEN 'checked_in'
                 ELSE 'checked_out'
               END as attendance_status
        FROM attendance a
        JOIN staff s ON s.id = a.staff_id
        WHERE a.date = ?
        ORDER BY a.checkin_time DESC
    ");
    $stmt->execute([$date]);
    return $stmt->fetchAll();
}

function getAttendanceStats($pdo, $startDate, $endDate) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_days,
            COUNT(CASE WHEN checkin_time IS NOT NULL THEN 1 END) as present_days,
            COUNT(CASE WHEN checkout_time IS NULL THEN 1 END) as currently_checked_in,
            s.name as staff_name,
            s.position
        FROM attendance a
        JOIN staff s ON s.id = a.staff_id
        WHERE a.date BETWEEN ? AND ?
        GROUP BY s.id, s.name, s.position
        ORDER BY s.name
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll();
}

// Get data for current view
$currentDate = $_GET['date'] ?? date('Y-m-d');
$attendanceData = getAttendanceData($pdo, $currentDate);
$staffList = $pdo->query("SELECT id, name, position FROM staff WHERE status='active' ORDER BY name")->fetchAll();

// Get monthly stats
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');
$monthlyStats = getAttendanceStats($pdo, $monthStart, $monthEnd);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="<?= BASE ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .attendance-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .attendance-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .attendance-title { font-family: var(--serif); font-size: 28px; color: var(--text); }
        .date-filter { padding: 10px; border: 1px solid var(--bdr2); border-radius: 6px; background: var(--card2); }
        .btn-checkin { background: #51cf66; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-checkin:hover { background: #40c057; }
        
        .attendance-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; margin-bottom: 30px; }
        .attendance-section { background: var(--card); border: 1px solid var(--bdr2); border-radius: 12px; padding: 25px; }
        .section-title { font-size: 18px; font-weight: 600; color: var(--text); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .section-title i { color: var(--gold); }
        
        .attendance-list { max-height: 400px; overflow-y: auto; }
        .attendance-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--bdr2); }
        .attendance-item:last-child { border-bottom: none; }
        .staff-info { flex: 1; }
        .staff-name { font-weight: 600; color: var(--text); }
        .staff-position { font-size: 13px; color: var(--text2); }
        .time-info { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }
        .checkin-time, .checkout-time { font-size: 14px; }
        .checkin-time { color: #51cf66; }
        .checkout-time { color: #ff6b6b; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .status-checked-in { background: #e8f5e8; color: #155724; }
        .status-checked-out { background: #d1ecf1; color: #0f5132; }
        .action-buttons { display: flex; gap: 8px; }
        .btn-checkout { background: #ff6b6b; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .btn-checkout:hover { background: #ff5252; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .stat-card { background: var(--card); border: 1px solid var(--bdr2); border-radius: 12px; padding: 20px; text-align: center; }
        .stat-number { font-size: 32px; font-weight: 700; color: var(--gold); margin-bottom: 8px; }
        .stat-label { font-size: 14px; color: var(--text2); }
        .stat-detail { font-size: 12px; color: var(--text2); margin-top: 5px; }
        
        .checkin-form { background: var(--card); border: 1px solid var(--bdr2); border-radius: 12px; padding: 25px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 6px; font-weight: 600; color: var(--text); }
        .form-input, .form-select { width: 100%; padding: 10px; border: 1px solid var(--bdr2); border-radius: 6px; font-size: 14px; background: var(--card2); color: var(--text); }
        .form-textarea { width: 100%; padding: 10px; border: 1px solid var(--bdr2); border-radius: 6px; font-size: 14px; background: var(--card2); color: var(--text); min-height: 80px; resize: vertical; }
        .form-buttons { display: flex; gap: 10px; justify-content: flex-end; }
        .btn-submit { background: var(--gold); color: #000; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-submit:hover { background: var(--gold2); }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <main class="attendance-container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="attendance-header">
            <h1 class="attendance-title">Staff Attendance Management</h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <label style="font-weight: 600;">Date:</label>
                <input type="date" class="date-filter" value="<?= $currentDate ?>" onchange="location.href='?date='+this.value">
                <a href="?action=checkin" class="btn-checkin">
                    <i class="fas fa-sign-in-alt"></i> Quick Check In
                </a>
            </div>
        </div>
        
        <div class="attendance-grid">
            <!-- Today's Attendance -->
            <div class="attendance-section">
                <h2 class="section-title">
                    <i class="fas fa-calendar-day"></i>
                    Today's Attendance - <?= date('F j, Y', strtotime($currentDate)) ?>
                </h2>
                
                <?php if ($action === 'checkin'): ?>
                    <!-- Check In Form -->
                    <div class="checkin-form">
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Staff Member *</label>
                                <select name="staff_id" class="form-select" required>
                                    <option value="">Select Staff Member</option>
                                    <?php foreach ($staffList as $staff): ?>
                                        <option value="<?= $staff['id'] ?>"><?= htmlspecialchars($staff['name']) ?> - <?= htmlspecialchars($staff['position']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Date</label>
                                <input type="date" name="date" class="form-input" value="<?= $currentDate ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Check In Time</label>
                                <input type="time" name="checkin_time" class="form-input" value="<?= date('H:i') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-textarea" placeholder="Optional notes..."></textarea>
                            </div>
                            
                            <div class="form-buttons">
                                <button type="submit" class="btn-submit">Check In</button>
                                <a href="<?= BASE ?>/attendance.php" class="btn-cancel">Cancel</a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Attendance List -->
                    <div class="attendance-list">
                        <?php if (empty($attendanceData)): ?>
                            <p style="text-align: center; color: var(--text2); padding: 40px;">No attendance records for this date.</p>
                        <?php else: ?>
                            <?php foreach ($attendanceData as $record): ?>
                                <div class="attendance-item">
                                    <div class="staff-info">
                                        <div class="staff-name"><?= htmlspecialchars($record['name']) ?></div>
                                        <div class="staff-position"><?= htmlspecialchars($record['position']) ?></div>
                                    </div>
                                    
                                    <div class="time-info">
                                        <div class="checkin-time">
                                            <i class="fas fa-sign-in-alt"></i> <?= date('h:i A', strtotime($record['checkin_time'])) ?>
                                        </div>
                                        <?php if ($record['checkout_time']): ?>
                                            <div class="checkout-time">
                                                <i class="fas fa-sign-out-alt"></i> <?= date('h:i A', strtotime($record['checkout_time'])) ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="action-buttons">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="attendance_id" value="<?= $record['id'] ?>">
                                                    <input type="hidden" name="checkout_time" value="<?= date('H:i:s') ?>">
                                                    <button type="submit" name="action" value="checkout" class="btn-checkout">
                                                        <i class="fas fa-sign-out-alt"></i> Check Out
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="status-badge <?= $record['attendance_status'] === 'checked_in' ? 'status-checked-in' : 'status-checked-out' ?>">
                                        <?= str_replace('_', ' ', $record['attendance_status']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Monthly Statistics -->
            <div class="attendance-section">
                <h2 class="section-title">
                    <i class="fas fa-chart-bar"></i>
                    Monthly Statistics - <?= date('F Y') ?>
                </h2>
                
                <div class="stats-grid">
                    <?php foreach ($monthlyStats as $stat): ?>
                        <div class="stat-card">
                            <div class="stat-number"><?= $stat['present_days'] ?>/<?= $stat['total_days'] ?></div>
                            <div class="stat-label"><?= htmlspecialchars($stat['staff_name']) ?></div>
                            <div class="stat-detail"><?= htmlspecialchars($stat['position']) ?></div>
                            <?php if ($stat['currently_checked_in'] > 0): ?>
                                <div class="stat-detail" style="color: #ff6b6b;">
                                    <i class="fas fa-clock"></i> Currently Checked In
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
