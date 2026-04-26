<?php
// ── Royale Vista — Notifications API ──────────────────────────
error_reporting(0);
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) { echo json_encode(['ok'=>false,'notifications'=>[],'unread'=>0]); exit; }
$uid    = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

// ── GET (list)
if ($action === 'get' || $action === 'list') {
    $limit = min(20, (int)($_GET['limit'] ?? 15));
    $stmt  = $pdo->prepare("SELECT id,type,title,message,link,is_read,created_at FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT $limit");
    $stmt->execute([$uid]);
    $notifs = $stmt->fetchAll();
    $uStmt  = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
    $uStmt->execute([$uid]);
    echo json_encode(['ok'=>true,'notifications'=>$notifs,'unread'=>(int)$uStmt->fetchColumn()]);
    exit;
}

// ── MARK ONE READ
if ($action === 'mark_read') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) $pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?")->execute([$id,$uid]);
    echo json_encode(['ok'=>true]); exit;
}

// ── MARK ALL READ
if ($action === 'mark_all_read' || $action === 'mark_all') {
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$uid]);
    echo json_encode(['ok'=>true]); exit;
}

echo json_encode(['ok'=>false,'error'=>'Unknown action']);
