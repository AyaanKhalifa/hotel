<?php
require 'includes/db.php';

echo "=== PROPERTIES TABLE COLUMNS ===\n";
$cols = $pdo->query('DESCRIBE properties')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo $c['Field'] . ' | ' . $c['Type'] . ' | Null:' . $c['Null'] . ' | Default:' . ($c['Default'] ?? 'NULL') . "\n";
}

echo "\n=== SAMPLE PROPERTIES DATA (first 3) ===\n";
$rows = $pdo->query('SELECT id, name, slug, min_price_usd, is_active FROM properties LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo json_encode($r) . "\n";
}

echo "\n=== PROPERTIES WITH NULL SLUG OR min_price_usd ===\n";
$bad = $pdo->query("SELECT id, name, slug, min_price_usd FROM properties WHERE slug IS NULL OR min_price_usd IS NULL")->fetchAll(PDO::FETCH_ASSOC);
echo "Count: " . count($bad) . "\n";
foreach ($bad as $r) {
    echo json_encode($r) . "\n";
}
