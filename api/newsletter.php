<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

header('Content-Type: application/json');

// Ensure table exists safely
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS newsletter_subscribers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('active','unsubscribed') DEFAULT 'active'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch(Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false, 'msg'=>'Invalid method.']); exit;
}

$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success'=>false, 'msg'=>'Please provide a valid email address.']); exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?) ON DUPLICATE KEY UPDATE status='active'");
    $stmt->execute([$email]);
    echo json_encode(['success'=>true, 'msg'=>'Thank you! You have been subscribed to the Royale Vista newsletter.']);
} catch(Exception $e) {
    echo json_encode(['success'=>false, 'msg'=>'A server error occurred. Please try again later.']);
}
