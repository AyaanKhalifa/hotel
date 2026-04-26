<?php
require 'includes/config.php';
require 'includes/db.php';

echo "GALLERY IMAGES:\n";
$imgs = $pdo->query("SELECT id, title, filename, image_url, is_local FROM gallery_images")->fetchAll(PDO::FETCH_ASSOC);
print_r($imgs);

echo "ROOMS TABLE EXIST:\n";
try {
    $res = $pdo->query("SELECT id, room_number, status, room_type_id FROM rooms LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    print_r($res);
} catch (Exception $e) { echo $e->getMessage() . "\n"; }

echo "BOOKINGS STATUSES:\n";
try {
    $res = $pdo->query("SELECT booking_ref, status, check_in, check_out FROM bookings ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    print_r($res);
} catch (Exception $e) { echo $e->getMessage() . "\n"; }
