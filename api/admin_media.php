<?php
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
requireAdmin();

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if ($action === 'list') {
    $rtId = (int)($_GET['room_type_id'] ?? 0);
    $media = $pdo->prepare("SELECT * FROM room_images WHERE room_type_id = ? ORDER BY is_primary DESC, sort_order ASC, id ASC");
    $media->execute([$rtId]);
    echo json_encode(['ok' => true, 'media' => $media->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rtId = (int)($_POST['room_type_id'] ?? 0);
    $isPrimary = !empty($_POST['is_primary']) ? 1 : 0;
    
    if (!$rtId || empty($_FILES['media']['tmp_name'])) {
        echo json_encode(['ok' => false, 'error' => 'Missing data or file']);
        exit;
    }
    
    $file = $_FILES['media'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'webm'];
    
    if (!in_array($ext, $allowed)) {
        echo json_encode(['ok' => false, 'error' => 'Invalid file type']);
        exit;
    }
    
    $mediaType = in_array($ext, ['mp4', 'webm']) ? 'video' : 'image';
    $dir = dirname(__DIR__) . '/uploads/rooms';
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    
    $filename = 'rt'.$rtId.'_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
    $target = $dir . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target)) {
        if ($isPrimary) {
            $pdo->prepare("UPDATE room_images SET is_primary=0 WHERE room_type_id=?")->execute([$rtId]);
        }
        $url = '/uploads/rooms/' . $filename;
        $pdo->prepare("INSERT INTO room_images (room_type_id, image_url, media_type, is_primary) VALUES (?, ?, ?, ?)")
            ->execute([$rtId, $url, $mediaType, $isPrimary]);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Failed to save file']);
    }
    exit;
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['media_id'] ?? 0);
    
    $m = $pdo->prepare("SELECT image_url FROM room_images WHERE id = ?");
    $m->execute([$id]);
    $media = $m->fetch();
    
    if ($media) {
        $path = dirname(__DIR__) . $media['image_url'];
        if (file_exists($path)) unlink($path);
        
        $pdo->prepare("DELETE FROM room_images WHERE id = ?")->execute([$id]);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Media not found']);
    }
    exit;
}

if ($action === 'delete_review_media' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['media_id'] ?? 0);
    
    $m = $pdo->prepare("SELECT url FROM review_media WHERE id = ?");
    $m->execute([$id]);
    $media = $m->fetch();
    
    if ($media) {
        $url = $media['url'] ?? '';
        $rel = str_replace(BASE, '', $url);
        $path = dirname(__DIR__) . $rel;
        if (file_exists($path)) unlink($path);
        
        $pdo->prepare("DELETE FROM review_media WHERE id = ?")->execute([$id]);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Media not found']);
    }
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Invalid action']);
