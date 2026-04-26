<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'reply' => 'Invalid method.']);
    exit;
}

$message = trim($_POST['message'] ?? '');
if (!$message) {
    echo json_encode(['success' => false, 'reply' => 'Please ask a question!']);
    exit;
}

$messageLower = strtolower($message);
$reply = '';

// Check dynamic DB states first (Live Availability/Data)
if (preg_match('/how many rooms/i', $messageLower) || preg_match('/room count/i', $messageLower)) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM rooms");
    $total = $stmt->fetchColumn();
    $reply = "🏨 We currently feature <b>{$total}</b> luxurious rooms across our property. Would you like me to check availability for specific dates?";
} elseif (preg_match('/pool/i', $messageLower)) {
    $reply = "🏊 <b>Infinity Pool</b><br>Our breathtaking rooftop infinity pool is open daily <b>7AM–11PM</b>. Panoramic city views and poolside service are available. Towels are complimentary.";
} elseif (preg_match('/spa|massage|wellness/i', $messageLower)) {
    $reply = "🧖 <b>Aria Spa & Wellness</b><br>18 treatment rooms merging ancient healing & modern techniques.<br>Open daily <b>8AM–10PM</b>.<br><a href='" . BASE . "/spa.php' class='habibi-deep-link'>→ Explore Treatments</a>";
} elseif (preg_match('/breakfast|dining|food|restaurant|eat/i', $messageLower)) {
    $reply = "🍳 <b>Dining at Royale Vista</b><br>• Breakfast included with Exclusive, Family & Presidential bookings<br>• Platinum members receive complimentary breakfast always<br>• Our Michelin-starred restaurant serves 7AM–11PM<br><a href='" . BASE . "/dining.php' class='habibi-deep-link'>→ View Menu</a>";
} elseif (preg_match('/wifi|internet/i', $messageLower)) {
    $reply = "📶 <b>Complimentary High-Speed WiFi</b><br>Available throughout the entire hotel — guest rooms, common areas, pool deck and spa. No passwords required for guests — use your room number + last name.";
} elseif (preg_match('/loyalty|points|rewards|bronze|silver|gold|platinum/i', $messageLower)) {
    $reply = "⭐ <b>Loyale Vista Loyalty Program</b><br>• Earn <b>10 points</b> per $1 spent<br>• Redeem 100pts = $1 off<br>• Platinum gets 2× points, free breakfast & free airport transfer!<br><a href='" . BASE . "/loyalty.php' class='habibi-deep-link'>→ Join Today</a>";
} elseif (preg_match('/cancel|refund/i', $messageLower)) {
    $reply = "❌ <b>Cancellation Policy</b><br>• Silver: Free cancellation up to <b>24h</b> before check-in<br>• Gold: Free cancellation up to <b>48h</b><br>• Platinum: Cancel <b>any time</b><br><a href='" . BASE . "/bookings.php' class='habibi-deep-link'>→ My Bookings</a>";
} elseif (preg_match('/price|cost|how much|rate/i', $messageLower)) {
    $reply = "💰 <b>Room Rates</b><br>Our rooms start from <b>" . formatPrice(299, true) . "/night</b> for a Deluxe Room up to <b>" . formatPrice(1299, true) . "/night</b> for the Presidential Suite.<br>Prices include all taxes. Members receive discounts automatically.<br><a href='" . BASE . "/rooms.php' class='habibi-deep-link'>→ Check Prices</a>";
} elseif (preg_match('/book|reserve|availability/i', $messageLower)) {
    $reply = "📅 <b>Ready to Book?</b><br>I can guide you! Let's find your perfect dates:<br><a href='" . BASE . "/rooms.php' class='habibi-deep-link'>→ Check Live Availability</a>";
} elseif (preg_match('/gift card|giftcard|gift/i', $messageLower)) {
    $reply = "🎁 <b>Gift Cards</b><br>You can now apply <b>multiple gift cards</b> in one booking. Habibi will auto-use balances in order until your total due is covered.<br><a href='" . BASE . "/gift-cards.php' class='habibi-deep-link'>→ Buy Gift Cards</a>";
} elseif (preg_match('/invoice|receipt|bill/i', $messageLower)) {
    $reply = "🧾 <b>Invoice Help</b><br>After booking, your invoice includes room details and assigned room number(s) when available.<br><a href='" . BASE . "/bookings.php' class='habibi-deep-link'>→ Open My Bookings</a>";
} elseif (preg_match('/check.?in|check.?out|arrival|departure/i', $messageLower)) {
    $reply = "🛎 <b>Check-in / Check-out</b><br>Standard check-in is <b>2:00 PM</b> and check-out is <b>12:00 PM</b>. Early check-in and late check-out are subject to availability.";
} elseif (preg_match('/hello|hi|hey|salam|assalam/i', $messageLower)) {
    $reply = "👋 <b>Welcome to Royale Vista</b><br>I'm Habibi, your AI concierge. I can help with room availability, rates, offers, gift cards, and invoice guidance.";
} else {
    // Basic fallback using PHP session to alternate responses
    if (!isset($_SESSION['habibi_fallback'])) {
        $_SESSION['habibi_fallback'] = 0;
    }
    
    $fallbacks = [
        "I may have missed that, but I can still help with room availability, rates, booking steps, invoices, and gift cards. 😊",
        "Hmm, let me pass that feedback to our human concierge team! You can also <a href='" . BASE . "/contact.php' class='habibi-deep-link'>contact us directly</a> for personalised assistance.",
        "Great question! For the most accurate answer, please <a href='" . BASE . "/contact.php' class='habibi-deep-link'>reach out to our team</a>. What else would you like to explore?"
    ];
    
    $reply = $fallbacks[$_SESSION['habibi_fallback'] % count($fallbacks)];
    $_SESSION['habibi_fallback']++;
}

// Simulate slight AI typing delay logic
$typingDelay = strlen($reply) * 15; // 15ms per character returned
if ($typingDelay < 600) $typingDelay = 600;
if ($typingDelay > 2000) $typingDelay = 2000;

echo json_encode([
    'success' => true,
    'reply'   => $reply,
    'delay'   => $typingDelay
]);
