<?php
require_once __DIR__ . '/config.php';

function ensureServiceRequestSchema(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
        if (!$dbName) {
            return;
        }

        $needed = [
            'dining_reservations' => ['admin_status', 'admin_note', 'decided_at'],
            'spa_appointments' => ['admin_status', 'admin_note', 'decided_at'],
        ];

        foreach ($needed as $table => $cols) {
            $in = str_repeat('?,', count($cols) - 1) . '?';
            $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME IN ($in)");
            $stmt->execute(array_merge([$dbName, $table], $cols));
            $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!in_array('admin_status', $existing, true)) {
                $pdo->exec("ALTER TABLE {$table} ADD COLUMN admin_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER status");
            }
            if (!in_array('admin_note', $existing, true)) {
                $pdo->exec("ALTER TABLE {$table} ADD COLUMN admin_note TEXT NULL AFTER admin_status");
            }
            if (!in_array('decided_at', $existing, true)) {
                $pdo->exec("ALTER TABLE {$table} ADD COLUMN decided_at DATETIME NULL AFTER admin_note");
            }
        }
    } catch (Exception $e) {
        // Soft fail to avoid breaking customer pages if schema migrations are blocked.
    }
}

function pushUserNotification(PDO $pdo, ?int $userId, string $type, string $title, string $message, ?string $link = null): void
{
    if (!$userId) {
        return;
    }
    try {
        $pdo->prepare("INSERT INTO notifications (user_id,type,title,message,link) VALUES (?,?,?,?,?)")
            ->execute([$userId, $type, $title, $message, $link]);
    } catch (Exception $e) {
        // Notification failures should not block primary action.
    }
}

