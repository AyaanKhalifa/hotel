<?php
require 'includes/db.php';

// Step 1: Add 'slug' column to properties table
try {
    $pdo->exec("ALTER TABLE properties ADD COLUMN slug VARCHAR(100) NULL AFTER code");
    echo "Added 'slug' column.\n";
} catch (PDOException $e) {
    echo "slug column: " . $e->getMessage() . "\n";
}

// Step 2: Add 'headline' column if missing (used on line 515)
try {
    $pdo->exec("ALTER TABLE properties ADD COLUMN headline VARCHAR(300) NULL AFTER tagline");
    echo "Added 'headline' column.\n";
} catch (PDOException $e) {
    echo "headline column: " . $e->getMessage() . "\n";
}

// Step 3: Add 'min_price_usd' column
try {
    $pdo->exec("ALTER TABLE properties ADD COLUMN min_price_usd DECIMAL(10,2) NULL AFTER rooms_count");
    echo "Added 'min_price_usd' column.\n";
} catch (PDOException $e) {
    echo "min_price_usd column: " . $e->getMessage() . "\n";
}

// Step 4: Populate slug from code/name
$props = $pdo->query("SELECT id, code, name FROM properties")->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare("UPDATE properties SET slug=? WHERE id=?");
foreach ($props as $p) {
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($p['name'])));
    $slug = trim($slug, '-');
    if (!$slug) $slug = strtolower($p['code']);
    $stmt->execute([$slug, $p['id']]);
    echo "Set slug='{$slug}' for property id={$p['id']} ({$p['name']})\n";
}

// Step 5: Populate min_price_usd from room_types (cheapest price per property)
// Try via property_id on rooms table, or fall back to global min
echo "\nPopulating min_price_usd...\n";
$hasPropCol = false;
try {
    $check = $pdo->query("SHOW COLUMNS FROM rooms LIKE 'property_id'")->fetchColumn();
    if ($check) $hasPropCol = true;
} catch(Exception $e){}

echo "rooms.property_id exists: " . ($hasPropCol ? 'yes' : 'no') . "\n";

if ($hasPropCol) {
    $pdo->exec("
        UPDATE properties p
        SET p.min_price_usd = (
            SELECT MIN(rt.price_usd)
            FROM rooms r
            JOIN room_types rt ON r.room_type_id = rt.id
            WHERE r.property_id = p.id
        )
    ");
    echo "Updated min_price_usd per property.\n";
} else {
    // No property_id on rooms — just use global min per room type
    $globalMin = $pdo->query("SELECT MIN(price_usd) FROM room_types")->fetchColumn();
    $pdo->exec("UPDATE properties SET min_price_usd = " . (float)$globalMin . " WHERE min_price_usd IS NULL");
    echo "Set global min_price_usd = $globalMin for all properties.\n";
}

// Step 6: Populate headline from tagline where null
$pdo->exec("UPDATE properties SET headline = tagline WHERE headline IS NULL AND tagline IS NOT NULL");
echo "Copied tagline -> headline where missing.\n";

echo "\nDone! Verifying...\n";
$verify = $pdo->query("SELECT id, name, slug, min_price_usd, headline FROM properties LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
foreach ($verify as $r) {
    echo json_encode($r) . "\n";
}
