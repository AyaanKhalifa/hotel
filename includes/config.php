<?php
// ============================================================
//  ROYALE VISTA v2 — Master Config
//  My Style: Clean, WAMP-safe, full currency + language
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();

// ── Google OAuth Config — Get yours at console.cloud.google.com ──
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com'); // ← REPLACE THIS
define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY'); // ← Get yours at aistudio.google.com

// ── Auto BASE URL (works in any subfolder) ──────────────────
if (!defined('BASE')) {
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $projDir = str_replace('\\', '/', dirname(__DIR__));
    $docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
    $rel     = '';
    if (stripos($projDir, $docRoot) === 0) {
        $rel = substr($projDir, strlen($docRoot));
    }
    $rel     = rtrim($rel, '/');
    define('BASE', $scheme . '://' . $host . $rel);
}
function base(string $path = ''): string { return BASE . '/' . ltrim($path, '/'); }

// ── 30 World Currencies ─────────────────────────────────────
define('CURRENCIES', [
    'USD'=>['name'=>'US Dollar',          'symbol'=>'$',    'rate'=>1.00,    'flag'=>'🇺🇸','dec'=>2],
    'EUR'=>['name'=>'Euro',               'symbol'=>'€',    'rate'=>0.92,    'flag'=>'🇪🇺','dec'=>2],
    'GBP'=>['name'=>'British Pound',      'symbol'=>'£',    'rate'=>0.79,    'flag'=>'🇬🇧','dec'=>2],
    'INR'=>['name'=>'Indian Rupee',       'symbol'=>'₹',    'rate'=>83.12,   'flag'=>'🇮🇳','dec'=>0],
    'AED'=>['name'=>'UAE Dirham',         'symbol'=>'د.إ',  'rate'=>3.67,    'flag'=>'🇦🇪','dec'=>2],
    'SAR'=>['name'=>'Saudi Riyal',        'symbol'=>'﷼',    'rate'=>3.75,    'flag'=>'🇸🇦','dec'=>2],
    'JPY'=>['name'=>'Japanese Yen',       'symbol'=>'¥',    'rate'=>149.50,  'flag'=>'🇯🇵','dec'=>0],
    'CNY'=>['name'=>'Chinese Yuan',       'symbol'=>'¥',    'rate'=>7.24,    'flag'=>'🇨🇳','dec'=>2],
    'CAD'=>['name'=>'Canadian Dollar',    'symbol'=>'C$',   'rate'=>1.36,    'flag'=>'🇨🇦','dec'=>2],
    'AUD'=>['name'=>'Australian Dollar',  'symbol'=>'A$',   'rate'=>1.53,    'flag'=>'🇦🇺','dec'=>2],
    'CHF'=>['name'=>'Swiss Franc',        'symbol'=>'Fr',   'rate'=>0.89,    'flag'=>'🇨🇭','dec'=>2],
    'SGD'=>['name'=>'Singapore Dollar',   'symbol'=>'S$',   'rate'=>1.34,    'flag'=>'🇸🇬','dec'=>2],
    'MYR'=>['name'=>'Malaysian Ringgit',  'symbol'=>'RM',   'rate'=>4.71,    'flag'=>'🇲🇾','dec'=>2],
    'THB'=>['name'=>'Thai Baht',          'symbol'=>'฿',    'rate'=>35.20,   'flag'=>'🇹🇭','dec'=>0],
    'HKD'=>['name'=>'Hong Kong Dollar',   'symbol'=>'HK$',  'rate'=>7.82,    'flag'=>'🇭🇰','dec'=>2],
    'KRW'=>['name'=>'South Korean Won',   'symbol'=>'₩',    'rate'=>1325.0,  'flag'=>'🇰🇷','dec'=>0],
    'TRY'=>['name'=>'Turkish Lira',       'symbol'=>'₺',    'rate'=>32.10,   'flag'=>'🇹🇷','dec'=>2],
    'BRL'=>['name'=>'Brazilian Real',     'symbol'=>'R$',   'rate'=>4.97,    'flag'=>'🇧🇷','dec'=>2],
    'MXN'=>['name'=>'Mexican Peso',       'symbol'=>'MX$',  'rate'=>17.15,   'flag'=>'🇲🇽','dec'=>2],
    'ZAR'=>['name'=>'South African Rand', 'symbol'=>'R',    'rate'=>18.63,   'flag'=>'🇿🇦','dec'=>2],
    'SEK'=>['name'=>'Swedish Krona',      'symbol'=>'kr',   'rate'=>10.42,   'flag'=>'🇸🇪','dec'=>2],
    'NOK'=>['name'=>'Norwegian Krone',    'symbol'=>'kr',   'rate'=>10.56,   'flag'=>'🇳🇴','dec'=>2],
    'PLN'=>['name'=>'Polish Zloty',       'symbol'=>'zł',   'rate'=>3.97,    'flag'=>'🇵🇱','dec'=>2],
    'RUB'=>['name'=>'Russian Ruble',      'symbol'=>'₽',    'rate'=>91.50,   'flag'=>'🇷🇺','dec'=>0],
    'EGP'=>['name'=>'Egyptian Pound',     'symbol'=>'E£',   'rate'=>30.90,   'flag'=>'🇪🇬','dec'=>2],
    'PKR'=>['name'=>'Pakistani Rupee',    'symbol'=>'Rs',   'rate'=>279.50,  'flag'=>'🇵🇰','dec'=>0],
    'IDR'=>['name'=>'Indonesian Rupiah',  'symbol'=>'Rp',   'rate'=>15650.0, 'flag'=>'🇮🇩','dec'=>0],
    'NGN'=>['name'=>'Nigerian Naira',     'symbol'=>'₦',    'rate'=>1550.0,  'flag'=>'🇳🇬','dec'=>0],
    'DKK'=>['name'=>'Danish Krone',       'symbol'=>'kr',   'rate'=>6.89,    'flag'=>'🇩🇰','dec'=>2],
]);

// ── Supported Languages ────────────────────────────────────
define('LANGUAGES', [
    'en'=>['name'=>'English',   'flag'=>'🇬🇧','dir'=>'ltr','native'=>'English'],
    'ar'=>['name'=>'Arabic',    'flag'=>'🇸🇦','dir'=>'rtl','native'=>'العربية'],
    'bn'=>['name'=>'Bengali',   'flag'=>'🇧🇩','dir'=>'ltr','native'=>'বাংলা'],
    'de'=>['name'=>'German',    'flag'=>'🇩🇪','dir'=>'ltr','native'=>'Deutsch'],
    'es'=>['name'=>'Spanish',   'flag'=>'🇪🇸','dir'=>'ltr','native'=>'Español'],
    'gu'=>['name'=>'Gujarati',  'flag'=>'🇮🇳','dir'=>'ltr','native'=>'ગુજરાતી'],
    'fa'=>['name'=>'Persian',   'flag'=>'🇮🇷','dir'=>'rtl','native'=>'فارسی'],
    'fr'=>['name'=>'French',    'flag'=>'🇫🇷','dir'=>'ltr','native'=>'Français'],
    'he'=>['name'=>'Hebrew',    'flag'=>'🇮🇱','dir'=>'rtl','native'=>'עברית'],
    'zh'=>['name'=>'Chinese',   'flag'=>'🇨🇳','dir'=>'ltr','native'=>'中文'],
    'hi'=>['name'=>'Hindi',     'flag'=>'🇮🇳','dir'=>'ltr','native'=>'हिन्दी'],
    'id'=>['name'=>'Indonesian','flag'=>'🇮🇩','dir'=>'ltr','native'=>'Bahasa Indonesia'],
    'it'=>['name'=>'Italian',   'flag'=>'🇮🇹','dir'=>'ltr','native'=>'Italiano'],
    'ja'=>['name'=>'Japanese',  'flag'=>'🇯🇵','dir'=>'ltr','native'=>'日本語'],
    'ko'=>['name'=>'Korean',    'flag'=>'🇰🇷','dir'=>'ltr','native'=>'한국어'],
    'ms'=>['name'=>'Malay',     'flag'=>'🇲🇾','dir'=>'ltr','native'=>'Bahasa Melayu'],
    'nl'=>['name'=>'Dutch',     'flag'=>'🇳🇱','dir'=>'ltr','native'=>'Nederlands'],
    'pt'=>['name'=>'Portuguese','flag'=>'🇧🇷','dir'=>'ltr','native'=>'Português'],
    'ru'=>['name'=>'Russian',   'flag'=>'🇷🇺','dir'=>'ltr','native'=>'Русский'],
    'sw'=>['name'=>'Swahili',   'flag'=>'🇹🇿','dir'=>'ltr','native'=>'Kiswahili'],
    'th'=>['name'=>'Thai',      'flag'=>'🇹🇭','dir'=>'ltr','native'=>'ไทย'],
    'tr'=>['name'=>'Turkish',   'flag'=>'🇹🇷','dir'=>'ltr','native'=>'Türkçe'],
    'uk'=>['name'=>'Ukrainian', 'flag'=>'🇺🇦','dir'=>'ltr','native'=>'Українська'],
    'ur'=>['name'=>'Urdu',      'flag'=>'🇵🇰','dir'=>'rtl','native'=>'اردو'],
    'vi'=>['name'=>'Vietnamese','flag'=>'🇻🇳','dir'=>'ltr','native'=>'Tiếng Việt'],
]);

// ── Helpers ─────────────────────────────────────────────────
function getUserCurrency(): string {
    $c = $_SESSION['currency'] ?? $_COOKIE['rv_currency'] ?? 'USD';
    return isset(CURRENCIES[$c]) ? $c : 'USD';
}
function getUserLang(): string {
    $l = $_SESSION['language'] ?? $_COOKIE['rv_lang'] ?? 'en';
    return isset(LANGUAGES[$l]) ? $l : 'en';
}
function getLangDir(): string { return LANGUAGES[getUserLang()]['dir']; }
function getTheme(): string   { return $_COOKIE['rv_theme'] ?? 'dark'; }

function formatPrice(float $usd, bool $showCode = false): string {
    $c   = getUserCurrency();
    $ci  = CURRENCIES[$c];
    $val = round($usd * $ci['rate'], $ci['dec']);
    $fmt = $ci['dec'] === 0 ? number_format($val, 0) : number_format($val, $ci['dec']);
    return $ci['symbol'] . $fmt . ($showCode ? ' '.$c : '');
}
function getCurrencySymbol(): string { return CURRENCIES[getUserCurrency()]['symbol']; }
function getCurrencyRate(): float    { return CURRENCIES[getUserCurrency()]['rate']; }

function t(string $key, string $fallback = ''): string {
    global $lang;
    $val = $lang[$key] ?? $fallback ?: ucwords(str_replace('_',' ',$key));
    return htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
}

function isLoggedIn(): bool  { return !empty($_SESSION['user_id']); }
function isAdmin(): bool     { return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin'; }

/**
 * Returns an <img> or <div> avatar element for any user.
 * $img   = filename from uploads/avatars/ (or empty)
 * $name  = display name (used for initials fallback)
 * $size  = pixel size (default 36)
 * $extra = extra inline CSS
 */
function userAvatar(?string $img, string $name = 'U', int $size = 36, string $extra = ''): string {
    $initial = strtoupper(substr(trim($name) ?: 'U', 0, 1));
    $base    = defined('BASE') ? BASE : '';
    $fs      = max(10, intval($size * 0.38));
    $shared  = "width:{$size}px;height:{$size}px;border-radius:50%;flex-shrink:0;object-fit:cover;{$extra}";
    $divStyle = "width:{$size}px;height:{$size}px;border-radius:50%;flex-shrink:0;background:var(--gold);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:{$fs}px;color:#000;font-family:sans-serif;{$extra}";
    if (!empty($img)) {
        $img = trim($img);
        // Accept stored values as:
        // 1) plain filename (avatar_xxx.jpg)
        // 2) relative path (/uploads/avatars/avatar_xxx.jpg or uploads/avatars/avatar_xxx.jpg)
        // 3) full URL (http/https)
        if (preg_match('#^https?://#i', $img)) {
            $srcRaw = $img;
        } elseif (strpos($img, '/uploads/') !== false) {
            $srcRaw = (strpos($img, '/') === 0) ? ($base . $img) : ($base . '/' . ltrim($img, '/'));
        } else {
            $srcRaw = $base . '/uploads/avatars/' . ltrim($img, '/');
        }
        $src = htmlspecialchars($srcRaw, ENT_QUOTES);
        $altEsc = htmlspecialchars($name, ENT_QUOTES);
        // Use a wrapper that shows initials if image fails — no inline onerror injection
        return "<span class=\"rv-av-wrap\" style=\"display:inline-flex;flex-shrink:0;position:relative;width:{$size}px;height:{$size}px;\">"
             . "<img src=\"{$src}\" alt=\"{$altEsc}\" class=\"rv-av-img\" style=\"{$shared}\" loading=\"lazy\" onload=\"this.style.opacity=1\" onerror=\"this.style.display='none';this.nextElementSibling.style.display='flex'\">"
             . "<span class=\"rv-av-fb\" style=\"{$divStyle};display:none;align-items:center;justify-content:center;\">{$initial}</span>"
             . "</span>";
    }
    return "<span class=\"rv-av-wrap\" style=\"display:inline-flex;flex-shrink:0;\"><span class=\"rv-av-fb\" style=\"{$divStyle};display:flex;align-items:center;justify-content:center;\">{$initial}</span></span>";
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        header('Location: ' . BASE . '/login.php'); exit;
    }
}
function requireAdmin(): void {
    if (!isAdmin()) { header('Location: ' . BASE . '/admin/login.php'); exit; }
}

function clean(string $v): string {
    return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8');
}
function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verifyCsrf(): bool {
    return !empty($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']);
}

function flash(string $msg, string $type = 'success'): void {
    $_SESSION['flash'] = $msg; $_SESSION['flash_type'] = $type;
}
function getFlash(): ?array {
    if (!isset($_SESSION['flash'])) return null;
    $f = ['msg' => $_SESSION['flash'], 'type' => $_SESSION['flash_type'] ?? 'success'];
    unset($_SESSION['flash'], $_SESSION['flash_type']);
    return $f;
}

function nightsBetween(string $ci, string $co): int {
    return max(1, (int)(new DateTime($ci))->diff(new DateTime($co))->days);
}
function generateRef(string $prefix = 'BK'): string {
    return $prefix . date('Y') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
}
?>
