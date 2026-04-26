<?php
error_reporting(E_ALL);
ini_set('display_errors','1');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Staff Management — Royale Vista';

// Check if admin
if (!isAdmin()) {
    header('Location: ' . BASE . '/login.php');
    exit;
}

// Handle actions
$action = $_GET['action'] ?? 'list';

// Add new staff
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $password = clean($_POST['password'] ?? '');
    $position = clean($_POST['position'] ?? '');
    $department = clean($_POST['department'] ?? '');
    $salary = (float)($_POST['salary'] ?? 0);
    $hireDate = clean($_POST['hire_date'] ?? date('Y-m-d'));
    
    if ($name && $email) {
        $hashedPassword = $password ? password_hash($password, PASSWORD_DEFAULT) : '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // Default password: staff123
        $stmt = $pdo->prepare("INSERT INTO staff (name,email,phone,password,position,department,salary,hire_date,status) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$name, $email, $phone, $hashedPassword, $position, $department, $salary, $hireDate, 'active']);
        $_SESSION['success'] = 'Staff member added successfully';
    }
    header('Location: ' . BASE . '/staff.php');
    exit;
}

// Update staff
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $name = clean($_POST['name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $password = clean($_POST['password'] ?? '');
    $position = clean($_POST['position'] ?? '');
    $department = clean($_POST['department'] ?? '');
    $salary = (float)($_POST['salary'] ?? 0);
    $status = clean($_POST['status'] ?? 'active');
    
    if ($id && $name && $email) {
        if ($password) {
            // Update with new password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE staff SET name=?,email=?,phone=?,password=?,position=?,department=?,salary=?,status=? WHERE id=?");
            $stmt->execute([$name, $email, $phone, $hashedPassword, $position, $department, $salary, $status, $id]);
        } else {
            // Update without changing password
            $stmt = $pdo->prepare("UPDATE staff SET name=?,email=?,phone=?,position=?,department=?,salary=?,status=? WHERE id=?");
            $stmt->execute([$name, $email, $phone, $position, $department, $salary, $status, $id]);
        }
        $_SESSION['success'] = 'Staff member updated successfully';
    }
    header('Location: ' . BASE . '/staff.php');
    exit;
}

// Delete staff
if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM staff WHERE id=?");
        $stmt->execute([$id]);
        $_SESSION['success'] = 'Staff member deleted successfully';
    }
    header('Location: ' . BASE . '/staff.php');
    exit;
}

// Get staff list
$staff = $pdo->query("SELECT * FROM staff ORDER BY name")->fetchAll();

// Get single staff for editing
$editStaff = null;
if ($action === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE id=?");
        $stmt->execute([$id]);
        $editStaff = $stmt->fetch();
    }
}
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
        .staff-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .staff-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .staff-title { font-family: var(--serif); font-size: 28px; color: var(--text); }
        .btn-add { background: var(--gold); color: #000; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; text-decoration: none; }
        .btn-add:hover { background: var(--gold2); transform: translateY(-1px); }
        
        .staff-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .staff-card { background: var(--card); border: 1px solid var(--bdr2); border-radius: 12px; padding: 20px; transition: all 0.3s ease; }
        .staff-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
        .staff-avatar { width: 62px; height: 62px; border-radius: 50%; overflow: hidden; border: 2px solid var(--gold); display: flex; align-items: center; justify-content: center; background: var(--gold); color: #fff; font-weight: 700; font-size: 20px; margin-bottom: 12px; }
        .staff-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .staff-name { font-size: 18px; font-weight: 600; color: var(--text); margin-bottom: 8px; }
        .staff-position { color: var(--gold); font-size: 14px; margin-bottom: 12px; }
        .staff-info { display: grid; grid-template-columns: auto 1fr; gap: 8px; font-size: 13px; color: var(--text2); line-height: 1.6; }
        .staff-info i { color: var(--gold); width: 16px; }
        .staff-status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; margin-top: 12px; }
        .status-active { background: #51cf66; color: white; }
        .status-inactive { background: #ff6b6b; color: white; }
        .staff-actions { margin-top: 15px; display: flex; gap: 8px; }
        .btn-edit, .btn-delete { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; text-decoration: none; }
        .btn-edit { background: #339af0; color: white; }
        .btn-delete { background: #ff6b6b; color: white; }
        .btn-edit:hover { background: #2980b9; }
        .btn-delete:hover { background: #ff5252; }
        
        .form-container { max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--text); }
        .form-input { width: 100%; padding: 12px; border: 1px solid var(--bdr2); border-radius: 6px; font-size: 14px; background: var(--card2); color: var(--text); }
        .form-input:focus { outline: none; border-color: var(--gold); }
        .form-select { width: 100%; padding: 12px; border: 1px solid var(--bdr2); border-radius: 6px; font-size: 14px; background: var(--card2); color: var(--text); }
        .form-buttons { display: flex; gap: 10px; justify-content: flex-end; }
        .btn-submit { background: var(--gold); color: #000; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-submit:hover { background: var(--gold2); }
        .btn-cancel { background: transparent; color: var(--text2); border: 1px solid var(--bdr2); padding: 12px 24px; border-radius: 6px; cursor: pointer; }
        .btn-cancel:hover { border-color: var(--gold); color: var(--gold); }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <main class="staff-container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if ($action === 'edit' && $editStaff): ?>
            <!-- Edit Form -->
            <div class="form-container">
                <h2 class="staff-title">Edit Staff Member</h2>
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $editStaff['id'] ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($editStaff['name']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($editStaff['email']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-input" placeholder="Leave blank to keep current password">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-input" value="<?= htmlspecialchars($editStaff['phone']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Position</label>
                        <input type="text" name="position" class="form-input" value="<?= htmlspecialchars($editStaff['position']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" class="form-input" value="<?= htmlspecialchars($editStaff['department']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Salary</label>
                        <input type="number" name="salary" class="form-input" value="<?= htmlspecialchars($editStaff['salary']) ?>" step="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= $editStaff['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $editStaff['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn-submit">Update Staff</button>
                        <a href="<?= BASE ?>/staff.php" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        <?php elseif ($action === 'add'): ?>
            <!-- Add Form -->
            <div class="form-container">
                <h2 class="staff-title">Add New Staff Member</h2>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-input" placeholder="Leave blank for default password (staff123)">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Position</label>
                        <input type="text" name="position" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Salary</label>
                        <input type="number" name="salary" class="form-input" step="0.01">
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn-submit">Add Staff</button>
                        <a href="<?= BASE ?>/staff.php" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Staff List -->
            <div class="staff-header">
                <h1 class="staff-title">Staff Management</h1>
                <a href="?action=add" class="btn-add">
                    <i class="fas fa-plus"></i> Add Staff Member
                </a>
            </div>
            
            <div class="staff-grid">
                <?php foreach ($staff as $member): ?>
                    <div class="staff-card">
                        <div class="staff-avatar">
                            <?php if (!empty($member['profile_img'])): ?>
                                <img src="<?= BASE ?>/uploads/staff/<?= htmlspecialchars($member['profile_img']) ?>" alt="<?= htmlspecialchars($member['name']) ?>">
                            <?php else: ?>
                                <?= strtoupper(substr($member['name'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div class="staff-name"><?= htmlspecialchars($member['name']) ?></div>
                        <div class="staff-position"><?= htmlspecialchars($member['position']) ?></div>
                        
                        <div class="staff-info">
                            <div><i class="fas fa-envelope"></i> <?= htmlspecialchars($member['email']) ?></div>
                            <?php if ($member['phone']): ?>
                                <div><i class="fas fa-phone"></i> <?= htmlspecialchars($member['phone']) ?></div>
                            <?php endif; ?>
                            <div><i class="fas fa-building"></i> <?= htmlspecialchars($member['department']) ?></div>
                            <div><i class="fas fa-dollar-sign"></i> $<?= number_format($member['salary'], 2) ?></div>
                            <div><i class="fas fa-calendar"></i> <?= date('M j, Y', strtotime($member['hire_date'])) ?></div>
                        </div>
                        
                        <div class="staff-status <?= $member['status'] === 'active' ? 'status-active' : 'status-inactive' ?>">
                            <?= ucfirst($member['status']) ?>
                        </div>
                        
                        <div class="staff-actions">
                            <a href="?action=edit&id=<?= $member['id'] ?>" class="btn-edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="?action=delete&id=<?= $member['id'] ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this staff member?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
