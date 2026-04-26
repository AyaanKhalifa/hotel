<?php
require 'includes/db.php';
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `notifications` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `type` varchar(32) NOT NULL DEFAULT 'system',
          `title` varchar(128) NOT NULL,
          `message` text NOT NULL,
          `link` varchar(255) DEFAULT NULL,
          `is_read` tinyint(1) NOT NULL DEFAULT 0,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `idx_notf_user` (`user_id`),
          CONSTRAINT `fk_notf_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "OK: notifications table created/exists\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
