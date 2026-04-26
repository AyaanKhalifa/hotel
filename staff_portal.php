<?php
error_reporting(E_ALL);
ini_set('display_errors','1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Staff Portal — Royale Vista';

// Check if staff member is logged in
if (!isset($_SESSION['staff_id'])) {
    header('Location: ' . BASE . '/staff_login.php');
    exit;
}

// Get current staff member info
$staffId = $_SESSION['staff_id'];
$stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ? AND status = 'active'");
$stmt->execute([$staffId]);
$staff = $stmt->fetch();

if (!$staff) {
    session_destroy();
    header('Location: ' . BASE . '/staff_login.php');
    exit;
}

// Handle actions
$action = $_GET['action'] ?? 'dashboard';

// Check in/out functionality
if ($action === 'checkin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = date('Y-m-d');
    $checkInTime = date('H:i:s');
    $notes = clean($_POST['notes'] ?? '');
    
    // Check if already checked in today
    $stmt = $pdo->prepare("SELECT id FROM attendance WHERE staff_id=? AND date=? AND checkin_time IS NOT NULL");
    $stmt->execute([$staffId, $date]);
    if (!$stmt->fetch()) {
        $insertStmt = $pdo->prepare("INSERT INTO attendance (staff_id,date,checkin_time,notes) VALUES (?,?,?)");
        $insertStmt->execute([$staffId, $date, $checkInTime, $notes]);
        $_SESSION['success'] = 'Checked in successfully at ' . date('h:i A');
    } else {
        $_SESSION['error'] = 'Already checked in today';
    }
    header('Location: ' . BASE . '/staff_portal.php');
    exit;
}

if ($action === 'checkout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = date('Y-m-d');
    $checkOutTime = date('H:i:s');
    $notes = clean($_POST['notes'] ?? '');
    
    // Get today's attendance record
    $stmt = $pdo->prepare("SELECT id FROM attendance WHERE staff_id=? AND date=? AND checkin_time IS NOT NULL AND checkout_time IS NULL");
    $stmt->execute([$staffId, $date]);
    $attendance = $stmt->fetch();
    
    if ($attendance) {
        $updateStmt = $pdo->prepare("UPDATE attendance SET checkout_time=?, notes=? WHERE id=?");
        $updateStmt->execute([$checkOutTime, $notes, $attendance['id']]);
        $_SESSION['success'] = 'Checked out successfully at ' . date('h:i A');
    } else {
        $_SESSION['error'] = 'No active check-in found for today';
    }
    header('Location: ' . BASE . '/staff_portal.php');
    exit;
}

// Get staff's attendance data
function getStaffAttendance($pdo, $staffId, $limit = 30) {
    $stmt = $pdo->prepare("
        SELECT * FROM attendance 
        WHERE staff_id = ? 
        ORDER BY date DESC, checkin_time DESC 
        LIMIT ?
    ");
    $stmt->execute([$staffId, $limit]);
    return $stmt->fetchAll();
}

function getStaffStats($pdo, $staffId, $month = null) {
    if (!$month) $month = date('Y-m');
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_days,
            COUNT(CASE WHEN checkin_time IS NOT NULL THEN 1 END) as present_days,
            COUNT(CASE WHEN checkout_time IS NULL AND checkin_time IS NOT NULL THEN 1 END) as currently_checked_in,
            SUM(CASE 
                WHEN checkin_time IS NOT NULL AND checkout_time IS NOT NULL 
                THEN TIMESTAMPDIFF(MINUTE, checkin_time, checkout_time) 
                ELSE 0 
            END) as total_minutes,
            AVG(CASE 
                WHEN checkin_time IS NOT NULL AND checkout_time IS NOT NULL 
                THEN TIMESTAMPDIFF(MINUTE, checkin_time, checkout_time) 
                ELSE NULL 
            END) as avg_minutes
        FROM attendance 
        WHERE staff_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?
    ");
    $stmt->execute([$staffId, $month]);
    return $stmt->fetch();
}

// Get current attendance status
$currentDate = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE staff_id = ? AND date = ?");
$stmt->execute([$staffId, $currentDate]);
$todayAttendance = $stmt->fetch();

$attendanceHistory = getStaffAttendance($pdo, $staffId);
$monthlyStats = getStaffStats($pdo, $staffId);
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
        .portal-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .portal-header { background: var(--card); border: 1px solid var(--bdr2); border-radius: 12px; padding: 25px; margin-bottom: 30px; }
        .portal-welcome { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .welcome-info h1 { font-family: var(--serif); font-size: 28px; color: var(--text); margin-bottom: 8px; }
        .welcome-info p { color: var(--text2); font-size: 16px; }
        .welcome-actions { display: flex; gap: 10px; }
        .btn-checkin, .btn-checkout { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-checkin { background: #51cf66; color: white; }
        .btn-checkin:hover { background: #40c057; }
        .btn-checkout { background: #ff6b6b; color: white; }
        .btn-checkout:hover { background: #ff5252; }
        .btn-checkout:disabled { background: #dee2e6; cursor: not-allowed; }
        
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-bottom: 30px; }
        .stat-card { background: var(--card); border: 1px solid var(--bdr2); border-radius: 12px; padding: 25px; text-align: center; }
        .stat-icon { font-size: 32px; color: var(--gold); margin-bottom: 15px; }
        .stat-number { font-size: 36px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
        .stat-label { font-size: 14px; color: var(--text2); }
        .stat-detail { font-size: 12px; color: var(--text2); margin-top: 8px; }
        
        .attendance-section { background: var(--card); border: 1px solid var(--bdr2); border-radius: 12px; padding: 25px; }
        .section-title { font-size: 20px; font-weight: 600; color: var(--text); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .section-title i { color: var(--gold); }
        
        .attendance-list { max-height: 400px; overflow-y: auto; }
        .attendance-item { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid var(--bdr2); }
        .attendance-item:last-child { border-bottom: none; }
        .attendance-date { font-weight: 600; color: var(--text); }
        .attendance-times { display: flex; flex-direction: column; align-items: flex-end; gap: 5px; }
        .checkin-time { color: #51cf66; font-size: 14px; }
        .checkout-time { color: #ff6b6b; font-size: 14px; }
        .attendance-status { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-checked-in { background: #e8f5e8; color: #155724; }
        .status-checked-out { background: #d1ecf1; color: #0f5132; }
        .status-expected { background: #fff3cd; color: #856404; }
        
        .profile-section { background: var(--card); border: 1px solid var(--bdr2); border-radius: 12px; padding: 25px; }
        .profile-info { display: flex; gap: 20px; align-items: center; margin-bottom: 20px; }
        .profile-avatar { width: 80px; height: 80px; background: var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; color: white; font-weight: 600; overflow: hidden; border: 2px solid rgba(255,255,255,.7); }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .profile-details h3 { font-size: 20px; color: var(--text); margin-bottom: 5px; }
        .profile-details p { color: var(--text2); margin-bottom: 3px; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        
        .logout-btn { background: transparent; border: 1px solid var(--bdr2); color: var(--text2); padding: 8px 16px; border-radius: 6px; cursor: pointer; text-decoration: none; }
        .logout-btn:hover { border-color: var(--gold); color: var(--gold); }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <main class="portal-container">
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
        
        <!-- Portal Header -->
        <div class="portal-header">
            <div class="portal-welcome">
                <div class="welcome-info">
                    <h1>Welcome back, <?= htmlspecialchars($staff['name']) ?>!</h1>
                    <p><?= htmlspecialchars($staff['position']) ?> • <?= htmlspecialchars($staff['department']) ?></p>
                </div>
                <div class="welcome-actions">
                    <?php if (!$todayAttendance || !$todayAttendance['checkin_time']): ?>
                        <form method="POST" action="?action=checkin" style="display: inline;">
                            <button type="submit" class="btn-checkin">
                                <i class="fas fa-sign-in-alt"></i> Check In
                            </button>
                        </form>
                    <?php elseif ($todayAttendance['checkin_time'] && !$todayAttendance['checkout_time']): ?>
                        <form method="POST" action="?action=checkout" style="display: inline;">
                            <button type="submit" class="btn-checkout">
                                <i class="fas fa-sign-out-alt"></i> Check Out
                            </button>
                        </form>
                    <?php endif; ?>
                    <a href="<?= BASE ?>/staff_logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Stats -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-number"><?= $monthlyStats['present_days'] ?></div>
                <div class="stat-label">Days Present This Month</div>
                <div class="stat-detail"><?= date('F Y') ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?= number_format($monthlyStats['avg_minutes'] / 60, 1) ?></div>
                <div class="stat-label">Average Hours Per Day</div>
                <div class="stat-detail">Based on <?= $monthlyStats['present_days'] ?> days</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-number"><?= number_format($monthlyStats['total_minutes'] / 60, 1) ?></div>
                <div class="stat-label">Total Hours This Month</div>
                <div class="stat-detail"><?= date('F Y') ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-number"><?= htmlspecialchars($staff['position']) ?></div>
                <div class="stat-label">Your Position</div>
                <div class="stat-detail">Department: <?= htmlspecialchars($staff['department']) ?></div>
            </div>
        </div>
        
        <!-- Today's Status -->
        <div class="attendance-section" style="margin-bottom: 25px;">
            <h2 class="section-title">
                <i class="fas fa-calendar-day"></i>
                Today's Status - <?= date('F j, Y') ?>
            </h2>
            
            <?php if ($todayAttendance): ?>
                <div class="attendance-item">
                    <div>
                        <div class="attendance-date">Today</div>
                        <div style="font-size: 14px; color: var(--text2); margin-top: 5px;">
                            <?= $todayAttendance['notes'] ? 'Notes: ' . htmlspecialchars($todayAttendance['notes']) : '' ?>
                        </div>
                    </div>
                    <div class="attendance-times">
                        <div class="checkin-time">
                            <i class="fas fa-sign-in-alt"></i> 
                            <?= $todayAttendance['checkin_time'] ? date('h:i A', strtotime($todayAttendance['checkin_time'])) : 'Not checked in' ?>
                        </div>
                        <div class="checkout-time">
                            <i class="fas fa-sign-out-alt"></i> 
                            <?= $todayAttendance['checkout_time'] ? date('h:i A', strtotime($todayAttendance['checkout_time'])) : 'Not checked out' ?>
                        </div>
                    </div>
                    <div class="attendance-status <?= $todayAttendance['checkout_time'] ? 'status-checked-out' : 'status-checked-in' ?>">
                        <?= $todayAttendance['checkout_time'] ? 'Checked Out' : 'Currently Working' ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="attendance-item">
                    <div class="attendance-date">Today</div>
                    <div class="attendance-times">
                        <div style="color: var(--text2); font-style: italic;">No attendance record yet</div>
                    </div>
                    <div class="attendance-status status-expected">
                        Expected to check in
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Attendance -->
        <div class="attendance-section">
            <h2 class="section-title">
                <i class="fas fa-history"></i>
                Recent Attendance History
            </h2>
            
            <div class="attendance-list">
                <?php if (empty($attendanceHistory)): ?>
                    <p style="text-align: center; color: var(--text2); padding: 40px;">No attendance records found.</p>
                <?php else: ?>
                    <?php foreach ($attendanceHistory as $record): ?>
                        <div class="attendance-item">
                            <div>
                                <div class="attendance-date"><?= date('F j, Y', strtotime($record['date'])) ?></div>
                                <?php if ($record['notes']): ?>
                                    <div style="font-size: 12px; color: var(--text2); margin-top: 5px;">
                                        Notes: <?= htmlspecialchars($record['notes']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="attendance-times">
                                <div class="checkin-time">
                                    <i class="fas fa-sign-in-alt"></i> 
                                    <?= $record['checkin_time'] ? date('h:i A', strtotime($record['checkin_time'])) : 'Absent' ?>
                                </div>
                                <div class="checkout-time">
                                    <i class="fas fa-sign-out-alt"></i> 
                                    <?= $record['checkout_time'] ? date('h:i A', strtotime($record['checkout_time'])) : 'No checkout' ?>
                                </div>
                            </div>
                            <div class="attendance-status <?= 
                                $record['checkin_time'] && $record['checkout_time'] ? 'status-checked-out' : 
                                ($record['checkin_time'] ? 'status-checked-in' : 'status-absent') ?>">
                                <?= 
                                    $record['checkin_time'] && $record['checkout_time'] ? 'Complete' : 
                                    ($record['checkin_time'] ? 'Incomplete' : 'Absent') 
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Profile Section -->
        <div class="profile-section" style="margin-top: 25px;">
            <h2 class="section-title">
                <i class="fas fa-user"></i>
                Your Profile
            </h2>
            
            <div class="profile-info">
                <div class="profile-avatar">
                    <?php if (!empty($staff['profile_img'])): ?>
                        <img src="<?= BASE ?>/uploads/staff/<?= htmlspecialchars($staff['profile_img']) ?>" alt="<?= htmlspecialchars($staff['name']) ?>">
                    <?php else: ?>
                        <?= strtoupper(substr($staff['name'], 0, 2)) ?>
                    <?php endif; ?>
                </div>
                <div class="profile-details">
                    <h3><?= htmlspecialchars($staff['name']) ?></h3>
                    <p><i class="fas fa-briefcase" style="width: 16px; color: var(--gold);"></i> <?= htmlspecialchars($staff['position']) ?></p>
                    <p><i class="fas fa-building" style="width: 16px; color: var(--gold);"></i> <?= htmlspecialchars($staff['department']) ?></p>
                    <p><i class="fas fa-envelope" style="width: 16px; color: var(--gold);"></i> <?= htmlspecialchars($staff['email']) ?></p>
                    <?php if ($staff['phone']): ?>
                        <p><i class="fas fa-phone" style="width: 16px; color: var(--gold);"></i> <?= htmlspecialchars($staff['phone']) ?></p>
                    <?php endif; ?>
                    <p><i class="fas fa-calendar" style="width: 16px; color: var(--gold);"></i> Hired: <?= date('M j, Y', strtotime($staff['hire_date'])) ?></p>
                </div>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
