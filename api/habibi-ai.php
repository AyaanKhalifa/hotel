<?php
/**
 * Royale Vista — Advanced Gemini-Powered AI Concierge (Habibi AI)
 * Features: Live context, user personalization, and rich UI cards.
 */
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success'=>false, 'reply'=>'Invalid request.']));
}

$message   = trim($_POST['message'] ?? '');
$sessionId = $_POST['session_id'] ?? 'session_' . session_id();
$pageTitle = trim($_POST['page_title'] ?? 'Main Page');
$pageUrl   = trim($_POST['page_url'] ?? '/');
$guestName = trim($_POST['guest_name'] ?? '');

if (!$message) {
    die(json_encode(['success'=>false, 'reply'=>'...']));
}

// 1. Fetch User Context (If logged in)
$userContext = "Guest (Anonymous)";
if (isLoggedIn()) {
    $u = $pdo->prepare("SELECT name, role, loyalty_points FROM users WHERE id = ?");
    $u->execute([$_SESSION['user_id']]);
    $userData = $u->fetch();
    if ($userData) {
        $tier = "Bronze";
        if ($userData['loyalty_points'] >= 2000) $tier = "Platinum";
        elseif ($userData['loyalty_points'] >= 800) $tier = "Gold";
        elseif ($userData['loyalty_points'] >= 300) $tier = "Silver";
        
        $userContext = "Name: {$userData['name']} | Role: {$userData['role']} | Points: {$userData['loyalty_points']} | Tier: {$tier}";
    }
} elseif ($guestName) {
    $userContext = "Name: {$guestName} | Role: Returning Guest";
}

// 2. Fetch Expanded Hotel Context
try {
    // Rooms & Offers (Existing)
    $rooms = $pdo->query("SELECT rt.id, rt.name, rt.price_usd, rt.max_guests FROM room_types rt ORDER BY price_usd ASC")->fetchAll();
    $roomStr = implode("\n", array_map(fn($r) => "- {$r['name']}: \$" . number_format($r['price_usd']) . "/night (ID: {$r['id']})", $rooms));

    $offers = $pdo->query("SELECT code, value, type, description FROM offers WHERE is_active=1 LIMIT 5")->fetchAll();
    $offerStr = implode("\n", array_map(fn($o) => "- Code: {$o['code']} ({$o['value']}" . ($o['type'] === 'percentage' ? '%' : '$') . " off) - {$o['description']}", $offers));

    // Locations (Global Properties)
    $props = $pdo->query("SELECT name, city, country, address, code FROM properties WHERE is_active=1")->fetchAll();
    $propStr = implode("\n", array_map(fn($p) => "- {$p['name']} in {$p['city']}, {$p['country']} (Code: {$p['code']})", $props));

    // Memberships
    $mems = $pdo->query("SELECT name, discount_pct, description FROM memberships ORDER BY discount_pct ASC")->fetchAll();
    $memStr = implode("\n", array_map(fn($m) => "- {$m['name']}: {$m['discount_pct']}% off. {$m['description']}", $mems));

    // Events
    $evts = $pdo->query("SELECT name, type, capacity, price_from FROM events WHERE is_active=1")->fetchAll();
    $evtStr = implode("\n", array_map(fn($e) => "- {$e['name']} ({$e['type']}): Up to {$e['capacity']} guests. From \$" . number_format($e['price_from']), $evts));

} catch (Exception $e) {
    $roomStr = $propStr = $memStr = $evtStr = "Data temporarily unavailable.";
}

// 3. Save User Message & Fetch History
try {
    $pdo->prepare("INSERT INTO chat_messages (session_id, role, content) VALUES (?, 'user', ?)")->execute([$sessionId, $message]);
    $hist = $pdo->prepare("SELECT role, content FROM chat_messages WHERE session_id = ? ORDER BY created_at DESC LIMIT 10");
    $hist->execute([$sessionId]);
    $history = array_reverse($hist->fetchAll());
} catch (Exception $e) {
    $history = [['role'=>'user', 'content'=>$message]];
}

// 4. Build System Instruction for Gemini
$systemInstruction = "You are Habibi, the AI Concierge for Royale Vista.
PERSONALITY: Warm, sophisticated, and LUXURIOUS.

IMPORTANT: Always greet the user by name if it is provided in the Context.
User Context: {$userContext}

SITE NAVIGATION (Use these exact relative filenames):
- Rooms: rooms.php
- Locations: locations.php
- Membership: membership.php
- Offers: offers.php
- Dining: dining.php
- Spa: spa.php
- Gallery: gallery.php
- Contact: contact.php
- Bookings: bookings.php

CURRENT PAGE: \"{$pageTitle}\" ({$pageUrl})

GREETING RULE:
If the user says 'Hi', 'Hello', or 'Hey', you MUST respond with 'Hello [Name]!' or 'Welcome back, [Name]!' plus a small luxury greeting.

DATA SUMMARY:
Rooms: {$roomStr}
Offers: {$offerStr}
Locations: {$propStr}
Memberships: {$memStr}

RICH TRIGGERS:
- Suggest a room: [ROOM_CARD: ID]
- Navigate: [NAVIGATE: FILENAME.php]";

// 5. Call Gemini API
$msgs = [];
foreach ($history as $m) {
    if (!$m['content']) continue;
    $msgs[] = ['role' => ($m['role'] === 'assistant' ? 'model' : 'user'), 'parts' => [['text' => $m['content']]]];
}

$payload = [
    'contents' => $msgs,
    'systemInstruction' => [
        'parts' => [['text' => $systemInstruction]]
    ],
    'generationConfig' => [
        'maxOutputTokens' => 1000,
        'temperature' => 0.7,
    ]
];

$apiKey = GEMINI_API_KEY;
if ($apiKey === 'YOUR_GEMINI_API_KEY') {
    $reply = "Welcome back! I am currently expanding my knowledge to better serve you across our global properties. How may I assist you today? ✨";
} else {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $resp = curl_exec($ch);
    $data = json_decode($resp, true);
    curl_close($ch);
    $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? "I am momentarily restoring my energy. How may I serve you today? 🌟";
}

// --- Rich Meta Extraction ---
$richMeta = [];

// Room Cards
if (preg_match_all('/\[ROOM_CARD:\s*(\d+)\]/i', $reply, $matches)) {
    foreach (array_unique($matches[1]) as $id) {
        $r = $pdo->prepare("SELECT rt.id, rt.name, rt.price_usd, rt.description, (SELECT image_url FROM room_images ri WHERE ri.room_type_id=rt.id AND ri.is_primary=1 LIMIT 1) as image FROM room_types rt WHERE rt.id = ?");
        $r->execute([$id]);
        $room = $r->fetch();
        if ($room) $richMeta[] = ['type' => 'room_card', 'id' => $room['id'], 'name' => $room['name'], 'price' => formatPrice($room['price_usd']), 'desc' => mb_strimwidth($room['description'], 0, 80, '...'), 'img' => $room['image'] ?: (BASE . '/assets/img/placeholder.jpg')];
    }
}

// Navigation
if (preg_match('/\[NAVIGATE:\s*([a-z0-9_\-\.\/]+)\]/i', $reply, $match)) {
    $targetUrl = $match[1];
    $richMeta[] = ['type' => 'navigate', 'url' => $targetUrl];
}

// 6. Save Assistant Reply & Return
try {
    $pdo->prepare("INSERT INTO chat_messages (session_id, role, content) VALUES (?, 'assistant', ?)")->execute([$sessionId, $reply]);
} catch (Exception $e) {}

echo json_encode([
    'success'    => true,
    'reply'      => $reply,
    'rich_meta'  => $richMeta,
    'session_id' => $sessionId
]);
