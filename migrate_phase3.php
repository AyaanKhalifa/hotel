<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
echo "<pre>";

// 1. Room assignments table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS booking_room_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_ref VARCHAR(20) NOT NULL,
        room_id INT NOT NULL,
        room_number VARCHAR(20) NOT NULL,
        room_type_id INT NOT NULL,
        room_type_name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_bra_ref (booking_ref),
        INDEX idx_bra_room (room_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "OK: booking_room_assignments table created/exists\n";
} catch (Exception $e) { echo "ERROR: " . $e->getMessage() . "\n"; }

// 2. media_type column
$cols = $pdo->query("SHOW COLUMNS FROM gallery_images LIKE 'media_type'")->fetchAll();
if (empty($cols)) {
    $pdo->exec("ALTER TABLE gallery_images ADD COLUMN media_type ENUM('image','video') NOT NULL DEFAULT 'image'");
    echo "OK: Added media_type column\n";
} else { echo "OK: media_type already exists\n"; }

// 3. sort_order column
$cols2 = $pdo->query("SHOW COLUMNS FROM gallery_images LIKE 'sort_order'")->fetchAll();
if (empty($cols2)) {
    $pdo->exec("ALTER TABLE gallery_images ADD COLUMN sort_order INT NOT NULL DEFAULT 0");
    echo "OK: Added sort_order column\n";
} else { echo "OK: sort_order already exists\n"; }

echo "\nDone!</pre>";
