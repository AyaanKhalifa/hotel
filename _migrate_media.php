<?php
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/db.php';

try {
    $pdo->exec("ALTER TABLE room_images ADD COLUMN media_type ENUM('image','video') DEFAULT 'image' AFTER room_type_id");
    echo "Media type column added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
