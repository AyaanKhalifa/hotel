<?php
error_reporting(0);
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
header('Content-Type: application/json');

$action = clean($_POST['action'] ?? $_GET['action'] ?? 'balance');

// ── Tier calculation ──────────────────────────────────────────
function getTier(int $lifetime): array {
    if ($lifetime >= 10000) return ['name' => 'Platinum', 'color' => '#8b5cf6', 'emoji' => '👑', 'multiplier' => 2.0, 'next' => null,  'nextPts' => null];
    if ($lifetime >= 5000)  return ['name' => 'Gold',     'color' => '#d4af37', 'emoji' => '🥇', 'multiplier' => 1.5, 'next' => 'Platinum', 'nextPts' => 10000];
    if ($lifetime >= 2000)  return ['name' => 'Silver',   'color' => '#94a3b8', 'emoji' => '🥉', 'multiplier' => 1.2, 'next' => 'Gold',     'nextPts' => 5000];
    return                         ['name' => 'Bronze',   'color' => '#cd7f32', 'emoji' => '🔸', 'multiplier' => 1.0, 'next' => 'Silver',   'nextPts' => 2000];
}

// ── Ensure loyalty row exists ────────────────────────────────
function ensureLoyaltyRow(PDO $pdo, int $userId): void {
    $pdo->prepare("INSERT IGNORE INTO loyalty_points (user_id, total_points, lifetime_points) VALUES (?,0,0)")
        ->execute([$userId]);
}

switch ($action) {
    case 'balance':
        if (!isLoggedIn()) { echo json_encode(['error' => 'Not logged in']); exit; }
        $uid = (int)$_SESSION['user_id'];
        ensureLoyaltyRow($pdo, $uid);
        $row = $pdo->prepare("SELECT * FROM loyalty_points WHERE user_id=?");
        $row->execute([$uid]);
        $lp  = $row->fetch();
        $tier = getTier($lp['lifetime_points'] ?? 0);
        echo json_encode([
            'balance'   => (int)($lp['total_points'] ?? 0),
            'lifetime'  => (int)($lp['lifetime_points'] ?? 0),
            'tier'      => $tier,
            'redeemable'=> floor(($lp['total_points'] ?? 0) / 100),  // $1 per 100 pts
        ]);
        break;

    case 'earn':
        // Called internally after booking
        $userId  = (int)($_POST['user_id'] ?? 0);
        $bookRef = clean($_POST['booking_ref'] ?? '');
        $amtUsd  = (float)($_POST['amount_usd'] ?? 0);
        if (!$userId || !$amtUsd) { echo json_encode(['error' => 'Invalid']); exit; }

        ensureLoyaltyRow($pdo, $userId);
        $lp = $pdo->prepare("SELECT * FROM loyalty_points WHERE user_id=?");
        $lp->execute([$userId]);
        $lpRow = $lp->fetch();

        $tier = getTier($lpRow['lifetime_points'] ?? 0);
        $earned = (int)round($amtUsd * 10 * $tier['multiplier']); // 10pts per $1 * tier multiplier

        $newTotal    = ($lpRow['total_points'] ?? 0) + $earned;
        $newLifetime = ($lpRow['lifetime_points'] ?? 0) + $earned;
        $newTier     = getTier($newLifetime)['name'];

        $pdo->prepare("UPDATE loyalty_points SET total_points=?, lifetime_points=?, tier=? WHERE user_id=?")
            ->execute([strtolower($newTier), $newTotal, $newLifetime, $userId]);
        // wait - fix this:
        $pdo->prepare("UPDATE loyalty_points SET total_points=?, lifetime_points=?, tier=? WHERE user_id=?")
            ->execute([$newTotal, $newLifetime, strtolower($newTier), $userId]);

        $pdo->prepare("INSERT INTO loyalty_transactions (user_id,booking_ref,type,points,balance_after,description) VALUES (?,?,?,?,?,?)")
            ->execute([$userId, $bookRef, 'earn', $earned, $newTotal, "Earned for booking {$bookRef}"]);

        echo json_encode(['earned' => $earned, 'new_balance' => $newTotal]);
        break;

    case 'redeem':
        if (!isLoggedIn()) { echo json_encode(['error' => 'Not logged in']); exit; }
        $uid    = (int)$_SESSION['user_id'];
        $points = (int)($_POST['points'] ?? 0);
        if ($points < 100) { echo json_encode(['error' => 'Minimum 100 points']); exit; }

        ensureLoyaltyRow($pdo, $uid);
        $lp = $pdo->prepare("SELECT total_points FROM loyalty_points WHERE user_id=?");
        $lp->execute([$uid]);
        $balance = (int)($lp->fetchColumn() ?? 0);

        if ($balance < $points) { echo json_encode(['error' => 'Insufficient points']); exit; }

        $newBalance   = $balance - $points;
        $dollarValue  = round($points / 100, 2);

        $pdo->prepare("UPDATE loyalty_points SET total_points=? WHERE user_id=?")->execute([$newBalance, $uid]);
        $pdo->prepare("INSERT INTO loyalty_transactions (user_id,type,points,balance_after,description) VALUES (?,?,?,?,?)")
            ->execute([$uid, 'redeem', -$points, $newBalance, "Redeemed {$points} points = \${$dollarValue}"]);

        echo json_encode(['success' => true, 'redeemed' => $points, 'dollar_value' => $dollarValue, 'new_balance' => $newBalance]);
        break;

    case 'transactions':
        if (!isLoggedIn()) { echo json_encode(['error' => 'Not logged in']); exit; }
        $uid = (int)$_SESSION['user_id'];
        $txs = $pdo->prepare("SELECT * FROM loyalty_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
        $txs->execute([$uid]);
        echo json_encode(['transactions' => $txs->fetchAll()]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
?>
