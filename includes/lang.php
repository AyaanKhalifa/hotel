<?php
require_once __DIR__ . '/config.php';

$userLang = getUserLang();
$langFile = __DIR__ . '/../lang/' . $userLang . '.php';
if (file_exists($langFile)) {
    require_once $langFile;
} else {
    require_once __DIR__ . '/../lang/en.php';
}
$GLOBALS['lang'] = $lang ?? [];
$GLOBALS['current_lang'] = $userLang;

/**
 * Build translation pairs from English source value -> selected language value.
 * This helps convert leftover hardcoded English strings in templates.
 */
function rvBuildOutputTranslationMap(string $targetLang): array {
    $en = [];
    $langFileEn = __DIR__ . '/../lang/en.php';
    if (file_exists($langFileEn)) {
        include $langFileEn; // sets local $lang
        if (isset($lang) && is_array($lang)) {
            $en = $lang;
        }
    }
    $current = $GLOBALS['lang'] ?? [];
    if (!is_array($current) || !$current || !$en || $targetLang === 'en') {
        return [];
    }
    $pairs = [];
    foreach ($en as $key => $enValue) {
        if (!isset($current[$key])) continue;
        $to = (string)$current[$key];
        $from = (string)$enValue;
        if ($to === '' || $from === '' || $to === $from) continue;
        $pairs[$from] = $to;
    }
    // Longest phrases first to prevent partial replacements.
    uksort($pairs, static fn($a, $b) => strlen($b) <=> strlen($a));
    return $pairs;
}

/**
 * Translate HTML output while skipping script/style blocks.
 */
function rvTranslateOutputHtml(string $html): string {
    $lang = $GLOBALS['current_lang'] ?? 'en';
    if ($lang === 'en') return $html;
    static $map = null;
    if ($map === null) {
        $map = rvBuildOutputTranslationMap($lang);
    }
    if (!$map) return $html;

    $parts = preg_split('/(<script\b[^>]*>.*?<\/script>|<style\b[^>]*>.*?<\/style>)/is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (!is_array($parts)) return $html;

    foreach ($parts as $i => $chunk) {
        // Keep script/style untouched.
        if ($i % 2 === 1) continue;
        foreach ($map as $from => $to) {
            $chunk = str_replace($from, $to, $chunk);
        }
        $parts[$i] = $chunk;
    }
    return implode('', $parts);
}

// Register output translator once per request.
if (!defined('RV_LANG_OB_ACTIVE')) {
    define('RV_LANG_OB_ACTIVE', true);
    ob_start('rvTranslateOutputHtml');
}
?>
