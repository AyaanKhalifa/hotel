<?php
// ============================================================
//  ROYALE VISTA — AI Chatbot API (Claude-powered)
// ============================================================
error_reporting(0);
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST only']); exit;
}

$message   = clean(trim($_POST['message'] ?? ''));
$sessionId = clean($_POST['session_id'] ?? 'anon_' . session_id());

if (!$message) { echo json_encode(['reply' => 'I didn\'t catch that. Could you rephrase?']); exit; }

// ── Save session + message ────────────────────────────────────
try {
    $pdo->prepare("INSERT IGNORE INTO chat_sessions (id, user_id) VALUES (?,?)")
        ->execute([$sessionId, $_SESSION['user_id'] ?? null]);
    $pdo->prepare("INSERT INTO chat_messages (session_id, role, content) VALUES (?,?,?)")
        ->execute([$sessionId, 'user', $message]);

    // Fetch last 6 messages for context
    $hist = $pdo->prepare("SELECT role,content FROM chat_messages WHERE session_id=? ORDER BY created_at DESC LIMIT 6");
    $hist->execute([$sessionId]);
    $history = array_reverse($hist->fetchAll());
} catch (Exception $e) {
    $history = [];
}

// ── Fetch live hotel data for context ────────────────────────
try {
    $roomData = $pdo->query("SELECT name, price_usd, max_guests, avg_rating FROM room_types ORDER BY sort_order")->fetchAll();
    $membershipData = $pdo->query("SELECT name, price_usd, discount_pct FROM memberships ORDER BY sort_order")->fetchAll();
    $offerData = $pdo->query("SELECT code, value, type, description FROM offers WHERE is_active=1 AND (valid_to IS NULL OR valid_to>=CURDATE()) ORDER BY value DESC LIMIT 4")->fetchAll();
    
    // Live Availability grouped by Room Type
    $roomAvail = $pdo->query("
        SELECT rt.name, COUNT(r.id) as avail 
        FROM room_types rt 
        LEFT JOIN rooms r ON r.room_type_id = rt.id AND r.status='available' 
        GROUP BY rt.id
    ")->fetchAll();
    $availByRoom = implode(', ', array_map(fn($r) => "{$r['name']}: {$r['avail']} available", $roomAvail));

} catch (Exception $e) {
    $roomData = $membershipData = $offerData = [];
    $availByRoom = 'Standard rooms available';
}

$roomInfo = implode(' | ', array_map(fn($r) => "{$r['name']}: \${$r['price_usd']}/night (max {$r['max_guests']} guests, ⭐{$r['avg_rating']})", $roomData));
$offerInfo = implode(' | ', array_map(fn($o) => "{$o['code']} = {$o['value']}{$o['type'][0]} off - {$o['description']}", $offerData));
$memInfo   = implode(' | ', array_map(fn($m) => "{$m['name']}: \${$m['price_usd']} ({$m['discount_pct']}% discount)", $membershipData));

// ── Build Claude API request ──────────────────────────────────
$systemPrompt = <<<PROMPT
You are Habibi, the AI concierge for Royale Vista — a 5-star luxury hotel. You are warm, professional, and knowledgeable. Keep responses concise (2-4 sentences max), helpful and elegant. Never use bullet lists longer than 3 items.

CURRENT HOTEL DATA:
Rooms: {$roomInfo}
Active Offers: {$offerInfo}
Memberships: {$memInfo}
Current Live Room Availability: {$availByRoom}

WHAT YOU CAN HELP WITH:
- Room types, prices, availability, and amenities
- Booking process and how to use offer codes
- Membership benefits (Silver 5%, Gold 15%, Platinum 25% off)
- Hotel facilities: pool, spa, restaurant, gym, concierge, airport transfers
- Check-in/out (2PM check-in, 12PM checkout — Platinum: 4PM late checkout)
- Loyalty points: earn 10 points per \$1 spent, redeem 100pts = \$1

ESCALATION: If asked for specific reservations or complaints, direct them to contact@royalevista.com or call +971 800 ROYALE.

PERSONALITY: Speak like a warm, knowledgeable luxury hotel concierge. Be concise. End with a gentle invitation to ask more.
PROMPT;

$messages = [];
foreach ($history as $msg) {
    $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
}
// Ensure last message is user
if (empty($messages) || end($messages)['role'] !== 'user') {
    $messages[] = ['role' => 'user', 'content' => $message];
}

// ── Call Anthropic API ────────────────────────────────────────
$apiUrl = 'https://api.anthropic.com/v1/messages';
$payload = [
    'model'      => 'claude-sonnet-4-6',
    'max_tokens' => 300,
    'system'     => $systemPrompt,
    'messages'   => $messages,
];

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'anthropic-version: 2023-06-01',
        'x-api-key: ' . ($_SERVER['ANTHROPIC_API_KEY'] ?? getenv('ANTHROPIC_API_KEY') ?? ''),
    ],
    CURLOPT_TIMEOUT => 15,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$reply     = '';
$quickReplies = [];

if ($httpCode === 200 && $response) {
    $data  = json_decode($response, true);
    $reply = $data['content'][0]['text'] ?? '';
}

// ── Fallback rule-based if API fails ─────────────────────────
if (!$reply) {
    $lower = strtolower($message);
    if (str_contains($lower, 'room') || str_contains($lower, 'suite') || str_contains($lower, 'price')) {
        $reply = "We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?";
        $quickReplies = ['🛏 Deluxe Room', '👑 Presidential Suite', '👨‍👩‍👧 Family Room'];
    } elseif (str_contains($lower, 'avail') || str_contains($lower, 'book') || str_contains($lower, 'reserv')) {
        $reply = "Here is our current live availability by room type:<br/>" . str_replace(',', '<br/>•', '• ' . $availByRoom) . "<br/><br/><a href='" . BASE . "/rooms.php' class='habibi-deep-link'>Select dates and secure your room here!</a>";
        $quickReplies = ['🏷 View Offers', '💳 Payment Options', '📞 Contact Support'];
    } elseif (str_contains($lower, 'offer') || str_contains($lower, 'discount') || str_contains($lower, 'deal')) {
        $codes = implode(', ', array_column($offerData, 'code'));
        $reply = "We have some wonderful offers right now! Active codes: {$codes}. Apply them at checkout for instant savings. Our members also enjoy up to 25% off every booking!";
        $quickReplies = ['👑 Get Membership', '📅 Book Now'];
    } elseif (str_contains($lower, 'pool') || str_contains($lower, 'spa') || str_contains($lower, 'facilit') || str_contains($lower, 'gym')) {
        $reply = "Royale Vista features a stunning rooftop infinity pool, award-winning Aria Spa, state-of-the-art fitness center, and multiple dining venues including our Michelin-starred restaurant. All facilities are exclusively for hotel guests.";
        $quickReplies = ['🛁 Spa Details', '🍽 Restaurant', '🏊 Pool'];
    } elseif (str_contains($lower, 'loyal') || str_contains($lower, 'point')) {
        $reply = "Our Royale Rewards loyalty programme lets you earn 10 points for every \$1 spent. Redeem 100 points for \$1 off your next stay. Gold members earn 1.5x points, Platinum members earn 2x points!";
        $quickReplies = ['💎 View My Points', '👑 Upgrade Membership'];
    } elseif (str_contains($lower, 'check') && (str_contains($lower, 'in') || str_contains($lower, 'out'))) {
        $reply = "Standard check-in is from 2:00 PM and check-out is by 12:00 PM (noon). Gold members enjoy early check-in from 12 PM, and Platinum members have guaranteed late check-out until 4:00 PM.";
        $quickReplies = ['🎫 Get Membership', '📞 Special Request'];
    } elseif (str_contains($lower, 'contact') || str_contains($lower, 'phone') || str_contains($lower, 'email')) {
        $reply = "Our team is available 24/7! Reach us at: 📧 stay@royalevista.com | 📞 +971 800 ROYALE | Or visit our Contact page. We typically respond to emails within 2 hours.";
        $quickReplies = ['📧 Contact Us', '📍 Location'];
    } elseif (str_contains($lower, 'airport') || str_contains($lower, 'transfer') || str_contains($lower, 'transport')) {
        $reply = "We offer complimentary luxury airport transfers for Presidential Suite guests, and available at a premium for other rooms. Platinum members receive complimentary transfers for all bookings. Shall I arrange this for you?";
        $quickReplies = ['📅 Book Transfer', '👑 Platinum Benefits'];
    } elseif (str_contains($lower, 'memb') || str_contains($lower, 'vip')) {
        $reply = "Our membership plans start from \$100: Silver (5% off), Gold (15% off + priority service), and Platinum (25% off + VIP treatment + complimentary breakfast + airport transfers). Members earn bonus loyalty points too!";
        $quickReplies = ['🥉 Silver Plan', '🥇 Gold Plan', '👑 Platinum Plan'];
    } elseif (str_contains($lower, 'hello') || str_contains($lower, 'hi') || str_contains($lower, 'hey')) {
        $reply = "Welcome to Royale Vista! 🏨✨ I'm Habibi, your personal concierge. Whether you're planning a stay, want to know about our facilities, or need assistance with a booking — I'm here for you!";
        $quickReplies = ['🛏 View Rooms', '💰 Check Prices', '🎁 Current Offers', '📞 Contact'];
    } else {
        $reply = "Thank you for reaching out to Royale Vista! I'd be delighted to help with room bookings, hotel facilities, special offers, or anything else about your stay. What can I assist you with?";
        $quickReplies = ['🛏 View Rooms', '💰 Check Prices', '🎁 Offers & Deals', '📞 Contact Us'];
    }
}

// ── Save AI reply ─────────────────────────────────────────────
try {
    $pdo->prepare("INSERT INTO chat_messages (session_id, role, content) VALUES (?,?,?)")
        ->execute([$sessionId, 'assistant', strip_tags($reply)]);
} catch (Exception $e) {}

echo json_encode([
    'reply'         => $reply,
    'quick_replies' => $quickReplies,
    'session_id'    => $sessionId,
]);
?>

