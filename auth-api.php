<?php
/**
 * auth-api.php  -  JSON endpoint used by user-login.php, user-register.php and dashboard.php
 *
 * Actions: password_login · send_otp · verify_otp · google · set_password
 * Every request is POST + JSON and must carry the CSRF token.
 */
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') la_fail('server', 405);

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) la_fail('server', 400);
if (!la_csrf_ok($in['csrf'] ?? null)) la_fail('csrf', 403);

$ip = la_ip();

try {
    switch ($in['action'] ?? '') {

        /* ============ 1) email / WhatsApp number + password ============ */
        case 'password_login':
            $id = la_parse_identifier($in['identifier'] ?? '', $in['cc'] ?? null);
            if (!$id) la_fail('bad_id');
            $pw = (string) ($in['password'] ?? '');

            if (!la_throttle('pw:ip:' . $ip, 30, 900) || !la_throttle('pw:' . $id['value'], 8, 900)) la_fail('too_many');

            $u = la_find_user($id['type'], $id['value']);
            if (!$u) la_fail('bad_login');
            if (!(int) $u['is_active']) la_fail('inactive');
            if (empty($u['password_hash'])) la_fail('no_password');   // passwordless account -> guide to code login
            if (!password_verify($pw, $u['password_hash'])) la_fail('bad_login');

            la_login_user($u);
            la_ok(['redirect' => la_safe_next($in['next'] ?? '')]);

        /* ============ 2) send a one-time code (login OR register) ============ */
        case 'send_otp':
            $purpose = $in['purpose'] ?? '';
            if (!in_array($purpose, ['login', 'register'], true)) la_fail('server', 400);

            $id = la_parse_identifier($in['identifier'] ?? '', $in['cc'] ?? null);
            if (!$id) la_fail('bad_id');

            if (!la_throttle('otp:ip:' . $ip, 15, 3600) || !la_throttle('otp:' . $id['value'], 4, 900)) la_fail('too_many');

            $u       = la_find_user($id['type'], $id['value']);
            $payload = null;

            if ($purpose === 'login') {
                // (shows a clear "no account" message on purpose, so users are guided to Register)
                if (!$u) la_fail('no_account');
                if (!(int) $u['is_active']) la_fail('inactive');
            } else {
                if ($u) la_fail('exists');
                $name = trim(mb_substr((string) ($in['name'] ?? ''), 0, 60));
                if (mb_strlen($name) < 2) la_fail('bad_name');

                $pw   = (string) ($in['password'] ?? '');
                $hash = null;
                if ($pw !== '') {                       // password is optional at register
                    if (strlen($pw) < 8) la_fail('weak_pw');
                    $hash = password_hash($pw, PASSWORD_DEFAULT);
                }
                $payload = json_encode(['name' => $name, 'password_hash' => $hash], JSON_UNESCAPED_UNICODE);
            }

            $code = la_issue_otp($id['type'], $id['value'], $purpose, $payload);
            $sent = $id['type'] === 'email'
                ? la_send_email($id['value'], $code)
                : la_send_whatsapp($id['value'], $code);

            if (!$sent && !LA_DEV_MODE) la_fail('send_fail');

            $out = ['channel' => $id['type'], 'masked' => la_mask($id), 'cooldown' => 30];
            if (LA_DEV_MODE) {
                $out['dev_code'] = $code;
                error_log('[auth DEV] code ' . $code . ' for ' . $id['value']);
            }
            la_ok($out);

        /* ============ 3) check the code -> log in / create account ============ */
        case 'verify_otp':
            $purpose = $in['purpose'] ?? '';
            if (!in_array($purpose, ['login', 'register'], true)) la_fail('server', 400);

            $id = la_parse_identifier($in['identifier'] ?? '', $in['cc'] ?? null);
            if (!$id) la_fail('bad_id');
            if (!la_throttle('vfy:ip:' . $ip, 40, 900)) la_fail('too_many');

            $code = preg_replace('/\D/', '', (string) ($in['code'] ?? ''));
            $r    = la_verify_otp($id['type'], $id['value'], $purpose, $code);
            if (!$r['ok']) la_fail($r['error']);

            $db      = la_db();
            $verCol  = $id['type'] === 'email' ? 'email_verified_at' : 'phone_verified_at';
            $u       = la_find_user($id['type'], $id['value']);

            if ($purpose === 'login') {
                if (!$u) la_fail('no_account');
                if (!(int) $u['is_active']) la_fail('inactive');
                $db->prepare("UPDATE web_users SET $verCol = COALESCE($verCol, NOW()) WHERE id = ?")->execute([$u['id']]);
            } else {
                if ($u) la_fail('exists');
                $p = json_decode((string) $r['payload'], true);
                if (!is_array($p) || empty($p['name'])) la_fail('server');

                $col = $id['type'] === 'email' ? 'email' : 'phone';
                try {
                    $db->prepare("INSERT INTO web_users (name, $col, $verCol, password_hash) VALUES (?, ?, NOW(), ?)")
                       ->execute([$p['name'], $id['value'], $p['password_hash'] ?? null]);
                } catch (PDOException $e) {
                    la_fail('exists');                  // unique key hit (double submit)
                }
                $u = la_get_user((int) $db->lastInsertId());
            }

            la_login_user($u);
            la_ok(['redirect' => la_safe_next($in['next'] ?? '')]);

        /* ============ 4) Google Sign-In ============ */
        case 'google':
            if (!la_throttle('g:ip:' . $ip, 30, 900)) la_fail('too_many');
            $g = la_google_verify((string) ($in['credential'] ?? ''));
            if (!$g) la_fail('google_fail');

            $db = la_db();
            $st = $db->prepare('SELECT * FROM web_users WHERE google_id = ? LIMIT 1');
            $st->execute([$g['sub']]);
            $u = $st->fetch(PDO::FETCH_ASSOC) ?: la_find_user('email', $g['email']);

            if ($u) {
                if (!(int) $u['is_active']) la_fail('inactive');
                // link Google to the existing account (Google already verified this email)
                $db->prepare('UPDATE web_users SET google_id = COALESCE(google_id, ?),
                                  avatar_url = COALESCE(NULLIF(avatar_url, ""), ?),
                                  email_verified_at = COALESCE(email_verified_at, NOW()) WHERE id = ?')
                   ->execute([$g['sub'], $g['picture'], $u['id']]);
                $u = la_get_user((int) $u['id']);
            } else {
                $name = $g['name'] !== '' ? mb_substr($g['name'], 0, 100) : strstr($g['email'], '@', true);
                $db->prepare('INSERT INTO web_users (name, email, email_verified_at, google_id, avatar_url) VALUES (?, ?, NOW(), ?, ?)')
                   ->execute([$name, $g['email'], $g['sub'], $g['picture']]);
                $u = la_get_user((int) $db->lastInsertId());
            }

            la_login_user($u);
            la_ok(['redirect' => la_safe_next($in['next'] ?? '')]);

        /* ============ 5) set / change password (dashboard, logged-in only) ============ */
        case 'set_password':
            if (empty($_SESSION['user_id'])) la_fail('login_first', 401);
            $u = la_get_user((int) $_SESSION['user_id']);
            if (!$u) la_fail('login_first', 401);

            if (!la_throttle('setpw:' . $u['id'], 10, 900)) la_fail('too_many');

            // If the account already has a password, the old one must be confirmed first.
            // (Passwordless / Google accounts are already proven by the active login session.)
            if (!empty($u['password_hash']) && !password_verify((string) ($in['current_password'] ?? ''), $u['password_hash'])) {
                la_fail('bad_login');
            }
            $new = (string) ($in['new_password'] ?? '');
            if (strlen($new) < 8) la_fail('weak_pw');

            la_db()->prepare('UPDATE web_users SET password_hash = ? WHERE id = ?')
                   ->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
            la_ok();

        default:
            la_fail('server', 400);
    }
} catch (Throwable $e) {
    error_log('[auth-api] ' . $e->getMessage());
    la_fail('server', 500);
}
