<?php
/**
 * includes/auth.php  -  helpers for public user login / register
 * (email / WhatsApp one-time codes, password, Google Sign-In)
 *
 * All functions start with la_ so they never clash with the admin helpers in config.php.
 * Require this file at the very top of any page / endpoint that needs login state.
 */
require_once __DIR__ . '/auth-config.php';

// Your existing config.php gives us getDBConnection() (PDO). Load it if it is not loaded yet.
$__cfg = dirname(__DIR__) . '/config.php';
if (is_file($__cfg)) { require_once $__cfg; }
unset($__cfg);

// Start the session once (header.php also checks before starting)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* ---------------------------------------------------------------- basics */

function la_db(): PDO {
    static $db = null;
    if ($db) return $db;
    if (!function_exists('getDBConnection')) {
        throw new RuntimeException('getDBConnection() not found - check config.php');
    }
    $db = getDBConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
}

function la_ip(): string { return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; }

function la_csrf(): string {
    if (empty($_SESSION['la_csrf'])) $_SESSION['la_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['la_csrf'];
}
function la_csrf_ok($t): bool {
    return is_string($t) && !empty($_SESSION['la_csrf']) && hash_equals($_SESSION['la_csrf'], $t);
}

/** Allow only simple relative paths after login (stops open-redirect tricks). */
function la_safe_next($n): string {
    $def = defined('LA_AFTER_LOGIN') ? LA_AFTER_LOGIN : 'dashboard.php';
    $n = trim((string) $n);
    if ($n === '' || strpos($n, '//') !== false || strpos($n, '\\') !== false) return $def;
    if (!preg_match('/^[A-Za-z0-9_\-\.\/]+(\?[A-Za-z0-9_\-\.=&%]*)?$/', $n)) return $def;
    if (preg_match('#^/?(user-login|user-register|user-logout|auth-api)#', $n)) return $def;
    return $n;
}

/* ---------------------------------------------------------------- messages (Sinhala) */

function la_msg(string $code): string {
    static $M = [
        'csrf'        => 'ආරක්ෂක ටෝකනය කල් ඉකුත් වී ඇත. පිටුව refresh කර නැවත උත්සාහ කරන්න.',
        'bad_id'      => 'වලංගු ඊමේල් ලිපිනයක් හෝ WhatsApp අංකයක් ඇතුළත් කරන්න.',
        'too_many'    => 'උත්සාහ ගණන වැඩියි. විනාඩි කිහිපයකින් නැවත උත්සාහ කරන්න.',
        'bad_login'   => 'ඊමේල් / අංකය හෝ මුරපදය වැරදියි.',
        'no_password' => 'මෙම ගිණුමට මුරපදයක් නැත. "කේතයෙන් ඇතුල් වන්න" භාවිතා කරන්න.',
        'no_account'  => 'මෙම ඊමේල් / අංකයට ගිණුමක් නැත. කරුණාකර ලියාපදිංචි වන්න.',
        'exists'      => 'මෙම ඊමේල් / අංකයට දැනටමත් ගිණුමක් ඇත. ඇතුල් වන්න.',
        'send_fail'   => 'කේතය යැවීමට නොහැකි විය. පසුව නැවත උත්සාහ කරන්න.',
        'bad_code'    => 'කේතය වැරදියි. නැවත පරීක්ෂා කරන්න.',
        'expired'     => 'කේතය කල් ඉකුත් වී ඇත. අලුත් කේතයක් ඉල්ලන්න.',
        'locked'      => 'වැරදි උත්සාහ ගණන වැඩියි. අලුත් කේතයක් ඉල්ලන්න.',
        'bad_name'    => 'නම අකුරු 2ක් හෝ වැඩි විය යුතුයි.',
        'weak_pw'     => 'මුරපදය අවම වශයෙන් අකුරු 8ක් විය යුතුයි.',
        'google_fail' => 'Google තහවුරු කිරීම අසාර්ථකයි. නැවත උත්සාහ කරන්න.',
        'inactive'    => 'මෙම ගිණුම අක්‍රියයි. කරුණාකර අප අමතන්න.',
        'login_first' => 'කරුණාකර මුලින් ඇතුල් වන්න.',
        'server'      => 'සේවාදායක දෝෂයක්. පසුව නැවත උත්සාහ කරන්න.',
    ];
    return $M[$code] ?? $M['server'];
}

/* ---------------------------------------------------------------- JSON replies */

function la_ok(array $data = []): void {
    echo json_encode(['ok' => true] + $data, JSON_UNESCAPED_UNICODE);
    exit;
}
function la_fail(string $code, int $http = 200): void {
    http_response_code($http);
    echo json_encode(['ok' => false, 'code' => $code, 'error' => la_msg($code)], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------------------------------------------------------------- rate limiting */

/** Returns false when $bucket already used $max attempts in the last $window seconds. */
function la_throttle(string $bucket, int $max, int $window): bool {
    $db  = la_db();
    $now = time();
    if (random_int(1, 50) === 1) {   // occasional cleanup
        $db->prepare('DELETE FROM web_attempts WHERE created_at < ?')->execute([$now - 86400]);
    }
    $st = $db->prepare('SELECT COUNT(*) FROM web_attempts WHERE bucket = ? AND created_at > ?');
    $st->execute([$bucket, $now - $window]);
    if ((int) $st->fetchColumn() >= $max) return false;
    $db->prepare('INSERT INTO web_attempts (bucket, created_at) VALUES (?, ?)')->execute([$bucket, $now]);
    return true;
}

/* ---------------------------------------------------------------- email / phone input */

/**
 * Turns what the user typed into ['type' => 'email'|'phone', 'value' => ...] or null.
 * Phone numbers are stored as digits with country code (no "+"), e.g. 94771234567.
 */
function la_parse_identifier($raw, $cc = null): ?array {
    $raw = trim((string) $raw);
    if ($raw === '') return null;

    if (strpos($raw, '@') !== false) {
        $e = strtolower($raw);
        return filter_var($e, FILTER_VALIDATE_EMAIL) ? ['type' => 'email', 'value' => $e] : null;
    }

    $cc     = preg_replace('/\D/', '', (string) ($cc ?: LA_DEFAULT_CC));
    $digits = preg_replace('/\D/', '', $raw);
    if ($digits === '') return null;

    if ($raw[0] === '+') {                       // +94 77 123 4567
        $num = $digits;
    } elseif (substr($digits, 0, 2) === '00') {  // 0094 77 ...
        $num = substr($digits, 2);
    } elseif ($digits[0] !== '0' && strpos($digits, $cc) === 0 && strlen($digits) >= strlen($cc) + 8) {
        $num = $digits;                          // already typed with country code, no "+"
    } else {                                     // 077 123 4567  -> add country code
        $num = $cc . ltrim($digits, '0');
    }
    if (strlen($num) < 9 || strlen($num) > 15) return null;
    return ['type' => 'phone', 'value' => $num];
}

function la_mask(array $id): string {
    $v = $id['value'];
    if ($id['type'] === 'email') {
        [$u, $d] = explode('@', $v, 2);
        return mb_substr($u, 0, 1) . '***@' . $d;
    }
    return '+' . substr($v, 0, 2) . str_repeat('•', max(0, strlen($v) - 5)) . substr($v, -3);
}

/* ---------------------------------------------------------------- users */

function la_find_user(string $type, string $value): ?array {
    $col = $type === 'email' ? 'email' : 'phone';
    $st  = la_db()->prepare("SELECT * FROM web_users WHERE $col = ? LIMIT 1");
    $st->execute([$value]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

function la_get_user(int $id): ?array {
    $st = la_db()->prepare('SELECT * FROM web_users WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

/** Marks the user as logged in. These session keys are the ones header.php reads. */
function la_login_user(array $u): void {
    session_regenerate_id(true);
    unset($_SESSION['la_csrf']);
    $_SESSION['user_id']    = (int) $u['id'];
    $_SESSION['user_name']  = $u['name'];
    $_SESSION['user_photo'] = $u['avatar_url'] ?? '';
    la_db()->prepare('UPDATE web_users SET last_login = NOW() WHERE id = ?')->execute([$u['id']]);
}

/* ---------------------------------------------------------------- one-time codes */

function la_hash_code(string $code): string { return hash_hmac('sha256', $code, LA_SECRET); }

/** Creates a fresh code (old unused ones for the same contact + purpose are cancelled). */
function la_issue_otp(string $type, string $value, string $purpose, ?string $payload = null): string {
    $db = la_db();
    $db->prepare('UPDATE web_otps SET consumed = 1 WHERE ident_type = ? AND ident_value = ? AND purpose = ? AND consumed = 0')
       ->execute([$type, $value, $purpose]);

    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $db->prepare('INSERT INTO web_otps (ident_type, ident_value, purpose, code_hash, payload, expires_at, created_at, ip)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
       ->execute([$type, $value, $purpose, la_hash_code($code), $payload, time() + LA_OTP_TTL, time(), la_ip()]);
    return $code;
}

/** @return array ['ok'=>true,'payload'=>?string] or ['ok'=>false,'error'=>code] */
function la_verify_otp(string $type, string $value, string $purpose, string $code): array {
    $db = la_db();
    $st = $db->prepare('SELECT * FROM web_otps WHERE ident_type = ? AND ident_value = ? AND purpose = ? AND consumed = 0
                        ORDER BY id DESC LIMIT 1');
    $st->execute([$type, $value, $purpose]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row || (int) $row['expires_at'] < time()) return ['ok' => false, 'error' => 'expired'];
    if ((int) $row['attempts'] >= LA_OTP_MAX_TRIES)  return ['ok' => false, 'error' => 'locked'];

    $db->prepare('UPDATE web_otps SET attempts = attempts + 1 WHERE id = ?')->execute([$row['id']]);
    if (!hash_equals($row['code_hash'], la_hash_code($code))) return ['ok' => false, 'error' => 'bad_code'];

    $db->prepare('UPDATE web_otps SET consumed = 1 WHERE id = ?')->execute([$row['id']]);
    return ['ok' => true, 'payload' => $row['payload']];
}

/* ---------------------------------------------------------------- sending codes */

function la_send_email(string $to, string $code): bool {
    $mins    = (int) (LA_OTP_TTL / 60);
    $subject = 'Learn Japanese — ඔබේ කේතය: ' . $code;
    $body    = "ඔබේ Learn Japanese තහවුරු කිරීමේ කේතය:\r\n\r\n    $code\r\n\r\n"
             . "මෙම කේතය විනාඩි $mins කින් කල් ඉකුත් වේ. ඔබ මෙය ඉල්ලා නැත්නම් නොසලකා හරින්න.\r\n\r\n"
             . "Your Learn Japanese verification code is $code (valid for $mins minutes).";
    $headers = [
        'From: ' . LA_MAIL_FROM_NAME . ' <' . LA_MAIL_FROM . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
}

/** Sends the code with an approved WhatsApp *authentication* template (Cloud API). */
function la_send_whatsapp(string $toDigits, string $code): bool {
    if (LA_WA_TOKEN === '' || LA_WA_PHONE_ID === '') return false;

    $payload = [
        'messaging_product' => 'whatsapp',
        'to'                => $toDigits,
        'type'              => 'template',
        'template'          => [
            'name'       => LA_WA_TEMPLATE,
            'language'   => ['code' => LA_WA_LANG],
            'components' => [
                ['type' => 'body',   'parameters' => [['type' => 'text', 'text' => $code]]],
                // authentication templates have a "copy code" button that needs the code again
                ['type' => 'button', 'sub_type' => 'url', 'index' => '0',
                 'parameters' => [['type' => 'text', 'text' => $code]]],
            ],
        ],
    ];

    $ch = curl_init('https://graph.facebook.com/' . LA_WA_API_VERSION . '/' . LA_WA_PHONE_ID . '/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . LA_WA_TOKEN, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload),
    ]);
    $res  = curl_exec($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http < 200 || $http >= 300) {
        error_log('[auth] WhatsApp send failed (' . $http . '): ' . substr((string) $res, 0, 300));
        return false;
    }
    return true;
}

/* ---------------------------------------------------------------- Google */

/** Verifies a Google Sign-In ID token. Returns ['sub','email','name','picture'] or null. */
function la_google_verify(string $idToken): ?array {
    if (LA_GOOGLE_CLIENT_ID === '' || $idToken === '') return null;

    $ch = curl_init('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8]);
    $res  = curl_exec($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http !== 200) return null;

    $j = json_decode((string) $res, true);
    if (!is_array($j)) return null;
    if (($j['aud'] ?? '') !== LA_GOOGLE_CLIENT_ID) return null;                       // token made for OUR site
    if (!in_array($j['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true)) return null;
    if ((int) ($j['exp'] ?? 0) < time()) return null;
    if (!in_array($j['email_verified'] ?? '', ['true', true], true)) return null;     // verified Google email only
    if (empty($j['sub']) || empty($j['email'])) return null;

    return [
        'sub'     => (string) $j['sub'],
        'email'   => strtolower((string) $j['email']),
        'name'    => (string) ($j['name'] ?? ''),
        'picture' => (string) ($j['picture'] ?? ''),
    ];
}

/* ---------------------------------------------------------------- small UI helper */

/** <option> list for the country-code box (default = LA_DEFAULT_CC). Add more countries here. */
function la_cc_options(): string {
    $list = ['94' => '🇱🇰 +94', '81' => '🇯🇵 +81', '91' => '🇮🇳 +91', '971' => '🇦🇪 +971', '974' => '🇶🇦 +974', '966' => '🇸🇦 +966'];
    $out = '';
    foreach ($list as $v => $label) {
        $out .= '<option value="' . $v . '"' . ($v === LA_DEFAULT_CC ? ' selected' : '') . '>' . $label . '</option>';
    }
    return $out;
}
