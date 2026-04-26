<?php
error_reporting(0);
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Invalid method']);
    exit;
}

$ref = clean($_POST['ref'] ?? '');
if (!$ref || !isset($_FILES['pdf'])) {
    echo json_encode(['ok' => false, 'error' => 'Missing data']);
    exit;
}

$uid = (int)($_SESSION['user_id'] ?? 0);

// Basic check if user has access to this booking (if not admin)
$bkQ = $pdo->prepare("SELECT id FROM bookings WHERE booking_ref = ? AND (user_id = ? OR ? = 1) LIMIT 1");
$bkQ->execute([$ref, $uid, isAdmin() ? 1 : 0]);
if (!$bkQ->fetch()) {
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$uploadDir = dirname(__DIR__) . '/uploads/invoices';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$filename = 'Invoice_' . preg_replace('/[^A-Z0-9_]/i', '', $ref) . '_' . time() . '.pdf';
$dest = $uploadDir . '/' . $filename;

if (move_uploaded_file($_FILES['pdf']['tmp_name'], $dest)) {
    // Save the path to the database
    $pdo->prepare("UPDATE bookings SET invoice_path = ? WHERE booking_ref = ?")->execute(['/uploads/invoices/' . $filename, $ref]);
    echo json_encode(['ok' => true, 'path' => '/uploads/invoices/' . $filename]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Failed to save PDF']);
}
