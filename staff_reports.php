<?php
error_reporting(E_ALL);
ini_set('display_errors','1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Staff Reports — Royale Vista';

// Check if admin
if (!isAdmin()) {
    header('Location: ' . BASE . '/login.php');
    exit;
}

// Get report parameters
$reportType = $_GET['report'] ?? 'attendance';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$staffId = (int)($_GET['staff_id'] ?? 0);

// Generate different reports
function generateAttendanceReport($pdo, $startDate, $endDate, $staffId = 0) {
    $sql = "
        SELECT 
            s.id as staff_id,
            s.name as staff_name,
            s.position,
            s.department,
            COUNT(a.id) as total_days,
            COUNT(CASE WHEN a.checkin_time IS NOT NULL THEN 1 END) as present_days,
            COUNT(CASE WHEN a.checkout_time IS NULL AND a.checkin_time IS NOT NULL THEN 1 END) as currently_checked_in,
            SUM(CASE 
                WHEN a.checkin_time IS NOT NULL AND a.checkout_time IS NOT NULL 
                THEN TIMESTAMPDIFF(MINUTE, a.checkin_time, a.checkout_time) 
                ELSE 0 
            END) as total_minutes,
            AVG(CASE 
                WHEN a.checkin_time IS NOT NULL AND a.checkout_time IS NOT NULL 
                THEN TIMESTAMPDIFF(MINUTE, a.checkin_time, a.checkout_time) 
                ELSE NULL 
            END) as avg_minutes
        FROM staff s
        LEFT JOIN attendance a ON s.id = a.staff_id 
            AND a.date BETWEEN ? AND ?
    ";
    
    $params = [$startDate, $endDate];
    
    if ($staffId > 0) {
        $sql .= " WHERE s.id = ?";
        $params[] = $staffId;
    }
    
    $sql .= " GROUP BY s.id, s.name, s.position, s.department
        ORDER BY s.name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function generateThairReport($pdo, $startDate, $endDate) {
    $stmt = $pdo->prepare("
        SELECT 
            DATE(a.date) as report_date,
            COUNT(*) as total_staff,
            COUNT(CASE WHEN a.checkin_time IS NOT NULL THEN 1 END) as checked_in,
            COUNT(CASE WHEN a.checkout_time IS NULL AND a.checkin_time IS NOT NULL THEN 1 END) as still_working,
            COUNT(CASE WHEN a.checkin_time IS NULL THEN 1 END) as absent,
            GROUP_CONCAT(CASE 
                WHEN a.checkin_time IS NULL THEN CONCAT(s.name, ' (Absent)')
                ELSE NULL 
            END SEPARATOR ', ') as absent_staff
        FROM attendance a
        RIGHT JOIN staff s ON s.id = a.staff_id AND s.status = 'active'
        WHERE a.date BETWEEN ? AND ?
        GROUP BY DATE(a.date)
        ORDER BY DATE(a.date)
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll();
}

function generateProductivityReport($pdo, $startDate, $endDate) {
    $stmt = $pdo->prepare("
        SELECT 
            s.name as staff_name,
            s.position,
            COUNT(a.id) as working_days,
            SUM(CASE 
                WHEN a.checkin_time IS NOT NULL AND a.checkout_time IS NOT NULL 
                THEN TIMESTAMPDIFF(MINUTE, a.checkin_time, a.checkout_time) 
                ELSE 0 
            END) as total_minutes,
            AVG(CASE 
                WHEN a.checkin_time IS NOT NULL AND a.checkout_time IS NOT NULL 
                THEN TIMESTAMPDIFF(MINUTE, a.checkin_time, a.checkout_time) 
                ELSE NULL 
            END) as avg_daily_minutes,
            CASE 
                WHEN AVG(CASE 
                    WHEN a.checkin_time IS NOT NULL AND a.checkout_time IS NOT NULL 
                    THEN TIMESTAMPDIFF(MINUTE, a.checkin_time, a.checkout_time) 
                    ELSE NULL 
                END) >= 480 THEN 'Excellent'
                WHEN AVG(CASE 
                    WHEN a.checkin_time IS NOT NULL AND a.checkout_time IS NOT NULL 
                    THEN TIMESTAMPDIFF(MINUTE, a.checkin_time, a.checkout_time) 
                    ELSE NULL 
                END) >= 420 THEN 'Good'
                WHEN AVG(CASE 
                    WHEN a.checkin_time IS NOT NULL AND a.checkout_time IS NOT NULL 
                    THEN TIMESTAMPDIFF(MINUTE, a.checkin_time, a.checkout_time) 
                    ELSE NULL 
                END) >= 360 THEN 'Average'
                ELSE 'Needs Improvement'
            END as performance_rating
        FROM staff s
        LEFT JOIN attendance a ON s.id = a.staff_id 
            AND a.date BETWEEN ? AND ?
            AND a.checkin_time IS NOT NULL AND a.checkout_time IS NOT NULL
        WHERE s.status = 'active'
        GROUP BY s.id, s.name, s.position
        ORDER BY total_minutes DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll();
}

// Get report data based on type
switch ($reportType) {
    case 'attendance':
        $reportData = generateAttendanceReport($pdo, $startDate, $endDate, $staffId);
        break;
    case 'thair':
        $reportData = generateThairReport($pdo, $startDate, $endDate);
        break;
    case 'productivity':
        $reportData = generateProductivityReport($pdo, $startDate, $endDate);
        break;
    default:
        $reportData = generateAttendanceReport($pdo, $startDate, $endDate, $staffId);
}

// Get staff list for filter dropdown
$staffList = $pdo->query("SELECT id, name, position FROM staff WHERE status='active' ORDER BY name")->fetchAll();
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
        .reports-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .reports-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .reports-title { font-family: var(--serif); font-size: 28px; color: var(--text); }
        .filters { display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-label { font-weight: 600; color: var(--text); font-size: 14px; }
        .filter-input { padding: 8px 12px; border: 1px solid var(--bdr2); border-radius: 6px; background: var(--card2); color: var(--text); }
        .btn-generate { background: var(--gold); color: #000; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-generate:hover { background: var(--gold2); transform: translateY(-1px); }
        
        .report-tabs { display: flex; gap: 0; margin-bottom: 30px; border-bottom: 2px solid var(--bdr2); }
        .tab { padding: 12px 24px; background: transparent; border: none; cursor: pointer; font-weight: 600; color: var(--text2); border-bottom: 2px solid transparent; }
        .tab.active { background: var(--card); color: var(--gold); border-bottom-color: var(--gold); }
        .tab:hover { color: var(--gold); }
        
        .report-content { background: var(--card); border: 1px solid var(--bdr2); border-radius: 12px; padding: 30px; }
        .report-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .report-table th { background: var(--gold); color: #000; padding: 12px 8px; text-align: left; font-weight: 600; border-bottom: 2px solid var(--gold); }
        .report-table td { padding: 12px 8px; border-bottom: 1px solid var(--bdr2); }
        .report-table tr:hover { background: var(--card2); }
        
        .stat-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .stat-card { background: var(--card); border: 1px solid var(--bdr2); border-radius: 12px; padding: 20px; text-align: center; }
        .stat-number { font-size: 24px; font-weight: 700; color: var(--gold); margin-bottom: 8px; }
        .stat-label { font-size: 14px; color: var(--text2); margin-bottom: 5px; }
        .stat-detail { font-size: 12px; color: var(--text2); }
        
        .performance-excellent { color: #51cf66; }
        .performance-good { color: #339af0; }
        .performance-average { color: #f59e0b; }
        .performance-poor { color: #ff6b6b; }
        
        .export-buttons { margin-top: 20px; display: flex; gap: 10px; }
        .btn-export { background: #339af0; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px; }
        .btn-export:hover { background: #2980b9; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <main class="reports-container">
        <div class="reports-header">
            <h1 class="reports-title">Staff Reports</h1>
        </div>
        
        <div class="filters">
            <div class="filter-group">
                <label class="filter-label">Start Date</label>
                <input type="date" class="filter-input" name="start_date" value="<?= $startDate ?>" onchange="updateFilters()">
            </div>
            
            <div class="filter-group">
                <label class="filter-label">End Date</label>
                <input type="date" class="filter-input" name="end_date" value="<?= $endDate ?>" onchange="updateFilters()">
            </div>
            
            <?php if ($reportType === 'attendance'): ?>
                <div class="filter-group">
                    <label class="filter-label">Staff Member</label>
                    <select class="filter-input" name="staff_id" onchange="updateFilters()">
                        <option value="0">All Staff</option>
                        <?php foreach ($staffList as $staff): ?>
                            <option value="<?= $staff['id'] ?>" <?= $staffId == $staff['id'] ? 'selected' : '' ?>><?= htmlspecialchars($staff['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <button class="btn-generate" onclick="exportReport()">
                <i class="fas fa-download"></i> Export Report
            </button>
        </div>
        
        <!-- Report Tabs -->
        <div class="report-tabs">
            <button class="tab <?= $reportType === 'attendance' ? 'active' : '' ?>" onclick="location.href='?report=attendance&start_date='+document.querySelector('[name=start_date]').value+'&end_date='+document.querySelector('[name=end_date]').value">
                <i class="fas fa-users"></i> Attendance Report
            </button>
            <button class="tab <?= $reportType === 'thair' ? 'active' : '' ?>" onclick="location.href='?report=thair&start_date='+document.querySelector('[name=start_date]').value+'&end_date='+document.querySelector('[name=end_date]').value">
                <i class="fas fa-calendar-check"></i> Thair Report
            </button>
            <button class="tab <?= $reportType === 'productivity' ? 'active' : '' ?>" onclick="location.href='?report=productivity&start_date='+document.querySelector('[name=start_date]').value+'&end_date='+document.querySelector('[name=end_date]').value">
                <i class="fas fa-chart-line"></i> Productivity Report
            </button>
        </div>
        
        <!-- Report Content -->
        <div class="report-content">
            <?php if ($reportType === 'attendance'): ?>
                <h2 style="margin-bottom: 20px;">
                    <i class="fas fa-users"></i>
                    Attendance Report 
                    <?php if ($staffId > 0): ?>
                        for <?= htmlspecialchars($staffList[array_search($staffId, array_column($staffList, 'id'))]['name']) ?>
                    <?php endif; ?>
                    (<?= date('M j, Y', strtotime($startDate)) ?> - <?= date('M j, Y', strtotime($endDate)) ?>)
                </h2>
                
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Staff Name</th>
                            <th>Position</th>
                            <th>Total Days</th>
                            <th>Present Days</th>
                            <th>Currently Checked In</th>
                            <th>Total Hours</th>
                            <th>Avg Daily Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['staff_name']) ?></td>
                                <td><?= htmlspecialchars($row['position']) ?></td>
                                <td><?= $row['total_days'] ?></td>
                                <td><?= $row['present_days'] ?></td>
                                <td><?= $row['currently_checked_in'] > 0 ? 'Yes' : 'No' ?></td>
                                <td><?= number_format($row['total_minutes'] / 60, 2) ?></td>
                                <td><?= number_format($row['avg_minutes'] / 60, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
            <?php elseif ($reportType === 'thair'): ?>
                <h2 style="margin-bottom: 20px;">
                    <i class="fas fa-calendar-check"></i>
                    Thair Report (<?= date('M j, Y', strtotime($startDate)) ?> - <?= date('M j, Y', strtotime($endDate)) ?>)
                </h2>
                
                <div class="stat-cards">
                    <?php foreach ($reportData as $row): ?>
                        <div class="stat-card">
                            <div class="stat-number"><?= $row['checked_in'] ?>/<?= $row['total_staff'] ?></div>
                            <div class="stat-label"><?= date('F j, Y', strtotime($row['report_date'])) ?></div>
                            <div class="stat-detail">Present: <?= $row['checked_in'] ?></div>
                            <div class="stat-detail">Absent: <?= $row['absent'] ?></div>
                            <div class="stat-detail">Still Working: <?= $row['still_working'] ?></div>
                            <?php if ($row['absent_staff']): ?>
                                <div class="stat-detail" style="font-size: 11px; margin-top: 10px;">
                                    <strong>Absent:</strong> <?= htmlspecialchars($row['absent_staff']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
            <?php elseif ($reportType === 'productivity'): ?>
                <h2 style="margin-bottom: 20px;">
                    <i class="fas fa-chart-line"></i>
                    Productivity Report (<?= date('M j, Y', strtotime($startDate)) ?> - <?= date('M j, Y', strtotime($endDate)) ?>)
                </h2>
                
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Staff Name</th>
                            <th>Position</th>
                            <th>Working Days</th>
                            <th>Total Hours</th>
                            <th>Avg Daily Hours</th>
                            <th>Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['staff_name']) ?></td>
                                <td><?= htmlspecialchars($row['position']) ?></td>
                                <td><?= $row['working_days'] ?></td>
                                <td><?= number_format($row['total_minutes'] / 60, 2) ?></td>
                                <td><?= number_format($row['avg_daily_minutes'] / 60, 2) ?></td>
                                <td class="performance-<?= strtolower($row['performance_rating']) ?>"><?= $row['performance_rating'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <div class="export-buttons">
                <button class="btn-export" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <button class="btn-export" onclick="exportToCSV()">
                    <i class="fas fa-file-csv"></i> Export to CSV
                </button>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <script>
        function updateFilters() {
            const startDate = document.querySelector('[name=start_date]').value;
            const endDate = document.querySelector('[name=end_date]').value;
            const staffId = document.querySelector('[name=staff_id]')?.value || 0;
            
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('start_date', startDate);
            currentUrl.searchParams.set('end_date', endDate);
            if (staffId) currentUrl.searchParams.set('staff_id', staffId);
            
            window.location.href = currentUrl.toString();
        }
        
        function exportToCSV() {
            let csv = '';
            const table = document.querySelector('.report-table');
            const rows = table.querySelectorAll('tr');
            
            rows.forEach(row => {
                const cols = row.querySelectorAll('td, th');
                const rowData = Array.from(cols).map(col => '"' + col.textContent.trim() + '"').join(',');
                csv += rowData + '\n';
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'staff_report_<?= date('Y-m-d') ?>.csv';
            a.click();
        }
        
        function exportReport() {
            // Main export function - can be extended for PDF, Excel etc.
            exportToCSV();
        }
    </script>
</body>
</html>
