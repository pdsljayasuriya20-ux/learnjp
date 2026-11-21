<?php
/**
 * admin-users.php - ADMIN PANEL for learnjapanese.jp (own login built in)
 *   Overview  ·  Users (search / filter / activate / revoke / disable + visit history)  ·  Plans  ·  Visitors (traffic + events)
 *
 * NEW (v2):
 *   ➕ Add a user by hand (name, email, WhatsApp number) with an auto-generated password
 *   🔑 Password reset (auto-generated password) for any user
 *   📨 After add / reset the new login details are shown ONCE in a card with:
 *        Copy · WhatsApp (opens chat with the message ready) · Send by email
 *
 * Put it in public_html/ (next to dashboard.php).
 * Login: admin_users table (username + password_hash, is_active = 1)
 * Visitor data comes from track.php (run tracking-upgrade.sql so visits are linked to users).
 */
require_once __DIR__ . '/includes/progress.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

/* ---------------------------------------------------------- SETTINGS (edit these) */
const ADM_SITE_URL   = 'https://learnjapanese.jp';
const ADM_LOGIN_URL  = 'https://learnjapanese.jp/login.php';   // <- your real user login page
const ADM_MAIL_FROM  = 'no-reply@learnjapanese.jp';            // must be an address on your own domain
const ADM_MAIL_NAME  = 'learnjapanese.jp';
const ADM_CC         = '94';                                   // default country code (Sri Lanka). 0771234567 -> 94771234567
const ADM_PW_LEN     = 10;                                     // generated password length

function adm_h($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
/** track.php stores values already HTML-encoded (e.g. "JLPT &amp; Driving") - decode once, then escape. */
function adm_hd($s): string { return htmlspecialchars(html_entity_decode((string) $s, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8'); }
function la_is_admin(): bool { return !empty($_SESSION['adm_ok']); }

$db   = la_db();
$csrf = la_csrf();

/* ---------------------------------------------------------- logout */
if (isset($_GET['logout'])) {
    unset($_SESSION['adm_ok'], $_SESSION['adm_name'], $_SESSION['adm_cred']);
    header('Location: admin-users.php');
    exit;
}

/* ---------------------------------------------------------- login gate */
if (!la_is_admin()) {
    $err = '';
    $lockUntil = (int) ($_SESSION['adm_lock'] ?? 0);

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'admin_login') {
        if ($lockUntil > time()) {
            $err = 'වැරදි උත්සාහ වැඩියි. විනාඩි ' . (int) ceil(($lockUntil - time()) / 60) . ' ක් පසු නැවත උත්සාහ කරන්න.';
        } elseif (!la_csrf_ok($_POST['csrf'] ?? null)) {
            $err = 'Security token වැරදියි. පිටුව refresh කරන්න.';
        } else {
            $u = trim((string) ($_POST['username'] ?? ''));
            $p = (string) ($_POST['password'] ?? '');
            try {
                $st = $db->prepare('SELECT * FROM admin_users WHERE username = ? AND is_active = 1 LIMIT 1');
                $st->execute([$u]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if ($row && password_verify($p, (string) $row['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['adm_ok']   = 1;
                    $_SESSION['adm_name'] = $row['username'];
                    unset($_SESSION['adm_tries'], $_SESSION['adm_lock']);
                    header('Location: admin-users.php');
                    exit;
                }
                $_SESSION['adm_tries'] = (int) ($_SESSION['adm_tries'] ?? 0) + 1;
                if ($_SESSION['adm_tries'] >= 5) {
                    $_SESSION['adm_lock']  = time() + 900;
                    $_SESSION['adm_tries'] = 0;
                }
                $err = 'Username හෝ password වැරදියි.';
            } catch (Throwable $e) {
                error_log('[admin-users login] ' . $e->getMessage());
                $err = 'admin_users table එක හොයාගන්න බැහැ (DB එකේ තියෙනවාද බලන්න).';
            }
        }
    }

    http_response_code(401);
    ?>
<!doctype html><html lang="si"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow"><title>Admin Login</title>
<style>
 body{font-family:system-ui,"Noto Sans Sinhala",sans-serif;background:#f1f3f9;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0}
 form{background:#fff;padding:28px;border-radius:16px;box-shadow:0 4px 18px rgba(30,35,80,.12);width:90%;max-width:360px}
 h2{margin:0 0 16px;color:#1f2340}
 input{width:100%;padding:11px 12px;margin-bottom:12px;border:1px solid #c9cde0;border-radius:10px;font:inherit;box-sizing:border-box}
 button{width:100%;padding:11px;background:#4f46e5;color:#fff;border:0;border-radius:10px;font:inherit;font-weight:700;cursor:pointer}
 .e{background:#fee2e2;color:#991b1b;padding:10px;border-radius:10px;margin-bottom:12px;font-size:.9rem}
</style></head><body>
<form method="post">
  <h2>🔐 Admin Login</h2>
  <?php if ($err): ?><div class="e"><?php echo adm_h($err); ?></div><?php endif; ?>
  <input type="hidden" name="action" value="admin_login">
  <input type="hidden" name="csrf" value="<?php echo adm_h($csrf); ?>">
  <input type="text" name="username" placeholder="Username" autocomplete="username" required autofocus>
  <input type="password" name="password" placeholder="Password" autocomplete="current-password" required>
  <button type="submit">Login</button>
</form></body></html>
    <?php
    exit;
}

$who = (string) ($_SESSION['adm_name'] ?? 'admin');

/* SQL snippet: "this activation is live right now" */
const ADM_LIVE = "up.status = 'active' AND up.starts_at <= NOW() AND (up.expires_at IS NULL OR up.expires_at > NOW())";

/* ============================================================ helpers */

/** Activate (or extend) a plan for a user. Returns ['ok'|'err', message]. */
function adm_activate(PDO $db, int $uid, int $planId, string $daysRaw, string $note, string $who): array {
    $user = la_get_user($uid);
    $st = $db->prepare('SELECT * FROM web_plans WHERE id = ? AND is_active = 1');
    $st->execute([$planId]);
    $plan = $st->fetch(PDO::FETCH_ASSOC);
    if (!$user || !$plan) return ['err', 'User හෝ plan එක හොයාගන්න බැරි වුණා.'];

    // days: blank = plan default (NULL there = lifetime) · 0 = lifetime
    $daysRaw = trim($daysRaw);
    if ($daysRaw === '') {
        $days = $plan['duration_days'] === null ? null : (int) $plan['duration_days'];
    } else {
        $days = max(0, min(3650, (int) $daysRaw));
        if ($days === 0) $days = null;
    }

    $expires = null;
    if ($days !== null) {
        // if the same plan is still running, extend from its end date instead of from today
        $b = $db->prepare("SELECT COALESCE(MAX(up.expires_at), NOW()) FROM web_user_plans up
                            WHERE up.user_id = ? AND up.plan_id = ? AND up.status = 'active' AND up.expires_at > NOW()");
        $b->execute([$uid, $planId]);
        $base = (string) $b->fetchColumn();
        $e = $db->prepare('SELECT DATE_ADD(?, INTERVAL ' . (int) $days . ' DAY)');
        $e->execute([$base]);
        $expires = (string) $e->fetchColumn();
    }

    $note = mb_substr(trim($note), 0, 250);
    $db->prepare("INSERT INTO web_user_plans (user_id, plan_id, status, starts_at, expires_at, note, activated_by)
                  VALUES (?, ?, 'active', NOW(), ?, ?, ?)")
       ->execute([$uid, $planId, $expires, $note !== '' ? $note : null, $who]);

    return ['ok', '✓ ' . $user['name'] . ' ට "' . $plan['name'] . '" activate කළා'
                . ($expires ? ' (' . substr($expires, 0, 10) . ' දක්වා)' : ' (ජීවිත කාලය)') . '.'];
}

/* ---------------------------------------------------------- manual users / passwords */

/** random password without look-alike characters (no 0/O, 1/l/I) - always has letters AND digits */
function adm_genpw(int $len = ADM_PW_LEN): string {
    $letters = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
    $digits  = '23456789';
    $all     = $letters . $digits;
    $len     = max(8, $len);
    $pw = [$letters[random_int(0, strlen($letters) - 1)], $digits[random_int(0, strlen($digits) - 1)]];
    while (count($pw) < $len) $pw[] = $all[random_int(0, strlen($all) - 1)];
    for ($i = count($pw) - 1; $i > 0; $i--) {          // shuffle (secure)
        $j = random_int(0, $i);
        [$pw[$i], $pw[$j]] = [$pw[$j], $pw[$i]];
    }
    return implode('', $pw);
}

/** digits only, with country code: 0771234567 -> 94771234567 · 94771234567 stays · 0094... -> 94... */
function adm_phone(string $raw): string {
    $d = preg_replace('/\D/', '', $raw);
    if ($d === '') return '';
    if (strpos($d, '00') === 0)  return substr($d, 2);
    if ($d[0] === '0')           return ADM_CC . substr($d, 1);
    if (strlen($d) <= 9)         return ADM_CC . $d;
    return $d;
}

/** name of the password column in web_users (password_hash / password / ...) */
function adm_pwcol(PDO $db): ?string {
    static $col = false;
    if ($col !== false) return $col;
    $col = null;
    try {
        $cols = $db->query('SHOW COLUMNS FROM web_users')->fetchAll(PDO::FETCH_COLUMN);
        foreach (['password_hash', 'password', 'pass_hash', 'passwd', 'pwd'] as $x) {
            if (in_array($x, $cols, true)) { $col = $x; break; }
        }
    } catch (Throwable $e) { /* ignore */ }
    return $col;
}

/** the message the user receives (login details) */
function adm_cred_text(array $c): string {
    $ids = [];
    if (!empty($c['email'])) $ids[] = 'Email: ' . $c['email'];
    if (!empty($c['phone'])) $ids[] = 'WhatsApp / Phone: +' . $c['phone'];
    $head = $c['kind'] === 'reset'
        ? 'ඔබගේ learnjapanese.jp ගිණුමේ password එක අලුත් කළා. 🔑'
        : 'learnjapanese.jp ගිණුම සාර්ථකව සකස් කළා! 🎌';
    return "ආයුබෝවන් {$c['name']}!\n"
         . $head . "\n\n"
         . 'Login: ' . ADM_LOGIN_URL . "\n"
         . implode("\n", $ids) . "\n"
         . 'Password: ' . $c['pw'] . "\n\n"
         . 'ආරක්ෂාවට, login වුණාට පස්සේ password එක වෙනස් කරගන්න. 🙏';
}

/** send the login details by e-mail (mail()). true = accepted by the mail system */
function adm_send_cred_email(array $c): bool {
    if (empty($c['email']) || !filter_var($c['email'], FILTER_VALIDATE_EMAIL)) return false;
    $subject = $c['kind'] === 'reset' ? 'ඔබගේ අලුත් password එක - learnjapanese.jp' : 'ඔබගේ learnjapanese.jp ගිණුම';
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'From: =?UTF-8?B?' . base64_encode(ADM_MAIL_NAME) . '?= <' . ADM_MAIL_FROM . '>',
        'Reply-To: ' . ADM_MAIL_FROM,
        'X-Mailer: PHP/' . PHP_VERSION,
    ];
    return @mail($c['email'], '=?UTF-8?B?' . base64_encode($subject) . '?=', adm_cred_text($c), implode("\r\n", $headers), '-f' . ADM_MAIL_FROM);
}

/** Create a user by hand. Returns ['ok'|'err', message, userId|null, passwordPlain|null] */
function adm_create_user(PDO $db, string $name, string $email, string $phoneRaw): array {
    $name  = mb_substr(trim($name), 0, 100);
    $email = strtolower(trim($email));
    $phone = adm_phone($phoneRaw);

    if ($name === '')                                     return ['err', 'නම ඕනේ.', null, null];
    if ($email === '' && $phone === '')                   return ['err', 'Email එකක් හෝ WhatsApp අංකයක් අවශ්‍යයි.', null, null];
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) return ['err', 'Email එක වැරදියි.', null, null];
    if ($phone !== '' && (strlen($phone) < 9 || strlen($phone) > 15)) return ['err', 'WhatsApp අංකය වැරදියි.', null, null];

    $pwCol = adm_pwcol($db);
    if (!$pwCol) return ['err', 'web_users table එකේ password column එක හොයාගන්න බැරි වුණා.', null, null];

    // duplicates
    $st = $db->prepare('SELECT id, name FROM web_users WHERE (? <> "" AND LOWER(email) = ?) OR (? <> "" AND phone = ?) LIMIT 1');
    $st->execute([$email, $email, $phone, $phone]);
    if ($dup = $st->fetch(PDO::FETCH_ASSOC)) {
        return ['err', 'මේ email / අංකය දැනටමත් තියෙනවා: ' . $dup['name'] . ' (#' . (int) $dup['id'] . ')', null, null];
    }

    $pw   = adm_genpw();
    $cols = $db->query('SHOW COLUMNS FROM web_users')->fetchAll(PDO::FETCH_COLUMN);
    $f = ['name' => $name, 'email' => $email !== '' ? $email : null, 'phone' => $phone !== '' ? $phone : null, $pwCol => password_hash($pw, PASSWORD_DEFAULT)];
    if (in_array('is_active', $cols, true))  $f['is_active'] = 1;
    $fields = array_keys($f);
    $sql = 'INSERT INTO web_users (`' . implode('`,`', $fields) . '`' . (in_array('created_at', $cols, true) ? ',`created_at`' : '') . ') VALUES ('
         . implode(',', array_fill(0, count($fields), '?')) . (in_array('created_at', $cols, true) ? ',NOW()' : '') . ')';
    try {
        $db->prepare($sql)->execute(array_values($f));
    } catch (PDOException $e) {
        error_log('[admin-users add] ' . $e->getMessage());
        return ['err', 'User එක හදන්න බැරි වුණා: ' . $e->getMessage(), null, null];
    }
    return ['ok', '✓ ' . $name . ' ගේ ගිණුම හදලා ඉවරයි.', (int) $db->lastInsertId(), $pw];
}

/** Set a new random password. Returns ['ok'|'err', message, passwordPlain|null] */
function adm_reset_password(PDO $db, int $uid): array {
    $pwCol = adm_pwcol($db);
    if (!$pwCol) return ['err', 'web_users table එකේ password column එක හොයාගන්න බැරි වුණා.', null];
    $pw = adm_genpw();
    $st = $db->prepare('UPDATE web_users SET `' . $pwCol . '` = ? WHERE id = ?');
    $st->execute([password_hash($pw, PASSWORD_DEFAULT), $uid]);
    return ['ok', '✓ අලුත් password එක හැදුවා.', $pw];
}

/** Card with the login details + Copy / WhatsApp / Email buttons (shown once) */
function adm_cred_card(array $c, string $csrf, string $back): void {
    $text = adm_cred_text($c);
    $wa   = !empty($c['phone']) ? 'https://wa.me/' . $c['phone'] . '?text=' . rawurlencode($text) : '';
    $canMail = !empty($c['email']) && filter_var($c['email'], FILTER_VALIDATE_EMAIL);
    ?>
  <div class="card cred">
    <div class="row">
      <div><b><?php echo $c['kind'] === 'reset' ? '🔑 අලුත් password එක' : '🎉 අලුත් ගිණුම'; ?></b> · <?php echo adm_h($c['name']); ?> <span class="id">#<?php echo (int) $c['uid']; ?></span></div>
      <form method="post"><input type="hidden" name="csrf" value="<?php echo adm_h($csrf); ?>"><input type="hidden" name="back" value="<?php echo adm_h($back); ?>">
        <input type="hidden" name="action" value="dismiss_cred"><button type="submit" class="soft">✕ වසන්න</button></form>
    </div>
    <div class="none" style="margin:6px 0 8px">⚠ Password එක පෙන්වන්නේ මේ එක වතාවයි (database එකේ තියෙන්නේ hash එක විතරයි). දැන්ම යවන්න / copy කරගන්න.</div>
    <textarea id="credText" readonly rows="9"><?php echo adm_h($text); ?></textarea>
    <div class="cbtns">
      <button type="button" id="credCopy">📋 Copy</button>
      <?php if ($wa): ?><a class="btn wa" href="<?php echo adm_h($wa); ?>" target="_blank" rel="noopener">💬 WhatsApp එකෙන් යවන්න</a>
      <?php else: ?><span class="none">WhatsApp අංකයක් නැහැ</span><?php endif; ?>
      <?php if ($canMail): ?>
        <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?php echo adm_h($csrf); ?>"><input type="hidden" name="back" value="<?php echo adm_h($back); ?>">
          <input type="hidden" name="action" value="send_cred_email"><button type="submit" class="soft">✉ Email එකට යවන්න</button></form>
      <?php else: ?><span class="none">Email එකක් නැහැ</span><?php endif; ?>
    </div>
    <?php if (array_key_exists('emailed', $c) && $c['emailed'] !== null): ?>
      <div class="none" style="margin-top:8px"><?php echo $c['emailed'] ? '✉ Email එක යැව්වා (' . adm_h($c['email']) . ').' : '⚠ Email එක යවන්න බැරි වුණා (server mail සැකසුම බලන්න) - Copy / WhatsApp පාවිච්චි කරන්න.'; ?></div>
    <?php endif; ?>
  </div>
<?php }

/** Small "generate new password" form for a user (used in user cards) */
function adm_reset_form(int $uid, bool $hasEmail, string $csrf, string $back): void { ?>
  <form method="post" style="display:inline" onsubmit="return confirm('අලුත් password එකක් හදන්නද? පරණ එක වැඩ කරන්නේ නැහැ.');">
    <input type="hidden" name="csrf" value="<?php echo adm_h($csrf); ?>">
    <input type="hidden" name="back" value="<?php echo adm_h($back); ?>">
    <input type="hidden" name="action" value="reset_pw">
    <input type="hidden" name="user_id" value="<?php echo $uid; ?>">
    <?php if ($hasEmail): ?><label class="none"><input type="checkbox" name="send_email" value="1"> Email යවන්න</label> <?php endif; ?>
    <button type="submit" class="soft">🔑 Password reset (generate)</button>
  </form>
<?php }

/** The little "pick a plan + Activate" form (used on Overview and Users). */
function adm_activate_form(int $uid, array $planOpts, string $csrf, string $back): void { ?>
  <form method="post" class="act">
    <input type="hidden" name="csrf" value="<?php echo adm_h($csrf); ?>">
    <input type="hidden" name="back" value="<?php echo adm_h($back); ?>">
    <input type="hidden" name="action" value="activate">
    <input type="hidden" name="user_id" value="<?php echo $uid; ?>">
    <select name="plan_id" required>
      <?php foreach ($planOpts as $po): ?>
        <option value="<?php echo (int) $po['id']; ?>"><?php echo adm_h($po['name']); ?>
          (<?php echo $po['duration_days'] === null ? 'lifetime' : (int) $po['duration_days'] . ' days'; ?>)</option>
      <?php endforeach; ?>
    </select>
    <input type="number" name="days" min="0" max="3650" placeholder="දවස් (හිස් = default · 0 = lifetime)" title="හිස් = plan default · 0 = lifetime">
    <input type="text" name="note" maxlength="250" placeholder="සටහන (උදා: bank transfer)">
    <button type="submit">Activate</button>
  </form>
<?php }

/**
 * Describes the `visitors` table once: which column holds the time, and whether user_id exists.
 * @return array{ok:bool,tc:?string,user:bool,cols:array}
 */
function adm_vcols(PDO $db): array {
    static $c = null;
    if ($c !== null) return $c;
    $c = ['ok' => false, 'tc' => null, 'user' => false, 'cols' => []];
    try {
        $cols = $db->query('SHOW COLUMNS FROM visitors')->fetchAll(PDO::FETCH_COLUMN);
        $c['cols'] = $cols;
        foreach (['visit_timestamp', 'visit_time', 'visited_at', 'visit_date', 'created_at', 'timestamp', 'date_time'] as $x) {
            if (in_array($x, $cols, true)) { $c['tc'] = $x; break; }
        }
        $c['user'] = in_array('user_id', $cols, true);
        $c['ok']   = $c['tc'] !== null;
    } catch (Throwable $e) { /* table missing */ }
    return $c;
}

/** "5 min ago" style text from a datetime string */
function adm_ago(?string $dt): string {
    if (!$dt) return '–';
    $t = strtotime($dt);
    if (!$t) return '–';
    $d = time() - $t;
    if ($d < 90)    return 'දැන්ම';
    if ($d < 3600)  return intdiv($d, 60) . ' මිනි. පෙර';
    if ($d < 86400) return intdiv($d, 3600) . ' පැය පෙර';
    return intdiv($d, 86400) . ' දින පෙර';
}

/* current page url (used to come back after an action) */
$qs   = (string) ($_SERVER['QUERY_STRING'] ?? '');
$self = 'admin-users.php' . ($qs !== '' ? '?' . $qs : '');

/* ============================================================ actions (POST) */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $back = (string) ($_POST['back'] ?? '');
    if (!preg_match('/^admin-users\.php(\?[A-Za-z0-9_\-\.=&%+]*)?$/', $back)) $back = 'admin-users.php';

    if (!la_csrf_ok($_POST['csrf'] ?? null)) {
        $_SESSION['adm_flash'] = ['err', 'Security token වැරදියි. පිටුව refresh කර නැවත උත්සාහ කරන්න.'];
        header('Location: ' . $back); exit;
    }

    $action = (string) ($_POST['action'] ?? '');
    $uid    = (int) ($_POST['user_id'] ?? 0);

    try {
        if ($action === 'activate') {
            $_SESSION['adm_flash'] = adm_activate($db, $uid, (int) ($_POST['plan_id'] ?? 0),
                                                  (string) ($_POST['days'] ?? ''), (string) ($_POST['note'] ?? ''), $who);

        } elseif ($action === 'add_user') {
            $r = adm_create_user($db, (string) ($_POST['name'] ?? ''), (string) ($_POST['email'] ?? ''), (string) ($_POST['phone'] ?? ''));
            $_SESSION['adm_flash'] = [$r[0], $r[1]];
            if ($r[0] === 'ok') {
                $u = la_get_user((int) $r[2]);
                $cred = ['uid' => (int) $r[2], 'name' => (string) ($u['name'] ?? ''), 'email' => (string) ($u['email'] ?? ''),
                         'phone' => (string) ($u['phone'] ?? ''), 'pw' => (string) $r[3], 'kind' => 'new', 'emailed' => null];
                if (!empty($_POST['send_email']) && $cred['email'] !== '') $cred['emailed'] = adm_send_cred_email($cred);
                $_SESSION['adm_cred'] = $cred;
                header('Location: admin-users.php?tab=users&q=' . rawurlencode('#' . (int) $r[2])); exit;
            }

        } elseif ($action === 'reset_pw') {
            $u = la_get_user($uid);
            if (!$u) {
                $_SESSION['adm_flash'] = ['err', 'User හොයාගන්න බැරි වුණා.'];
            } else {
                $r = adm_reset_password($db, $uid);
                $_SESSION['adm_flash'] = [$r[0], $r[1]];
                if ($r[0] === 'ok') {
                    $cred = ['uid' => $uid, 'name' => (string) $u['name'], 'email' => (string) ($u['email'] ?? ''),
                             'phone' => (string) ($u['phone'] ?? ''), 'pw' => (string) $r[2], 'kind' => 'reset', 'emailed' => null];
                    if (!empty($_POST['send_email']) && $cred['email'] !== '') $cred['emailed'] = adm_send_cred_email($cred);
                    $_SESSION['adm_cred'] = $cred;
                }
            }

        } elseif ($action === 'send_cred_email') {
            $cred = $_SESSION['adm_cred'] ?? null;
            if (is_array($cred)) {
                $cred['emailed'] = adm_send_cred_email($cred);
                $_SESSION['adm_cred'] = $cred;
                $_SESSION['adm_flash'] = $cred['emailed'] ? ['ok', '✉ Email එක යැව්වා.'] : ['err', 'Email එක යවන්න බැරි වුණා.'];
            }

        } elseif ($action === 'dismiss_cred') {
            unset($_SESSION['adm_cred']);

        } elseif ($action === 'revoke') {
            $db->prepare("UPDATE web_user_plans SET status = 'revoked' WHERE id = ? AND user_id = ?")
               ->execute([(int) ($_POST['activation_id'] ?? 0), $uid]);
            $_SESSION['adm_flash'] = ['ok', 'Plan එක ඉවත් කළා (revoked).'];

        } elseif ($action === 'toggle_user') {
            $to = (int) ($_POST['to'] ?? 0) === 1 ? 1 : 0;
            $db->prepare('UPDATE web_users SET is_active = ? WHERE id = ?')->execute([$to, $uid]);
            $_SESSION['adm_flash'] = ['ok', $to ? 'ගිණුම නැවත සක්‍රීය කළා.' : 'ගිණුම අක්‍රීය කළා (ඇතුල් වෙන්න බැහැ).'];

        } elseif ($action === 'plan_save') {
            $pid  = (int) ($_POST['plan_id'] ?? 0);
            $name = mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 100);
            $sel  = array_values(array_intersect(LA_LEVELS, (array) ($_POST['levels'] ?? [])));
            $d    = trim((string) ($_POST['duration_days'] ?? ''));
            $dur  = $d === '' ? null : max(1, min(3650, (int) $d));
            $sort = (int) ($_POST['sort_order'] ?? 0);
            $act  = !empty($_POST['is_active']) ? 1 : 0;

            if ($name === '' || !$sel) {
                $_SESSION['adm_flash'] = ['err', 'නමත්, අවම වශයෙන් level එකක්වත් තෝරන්න.'];
            } else {
                $levels = count($sel) === count(LA_LEVELS) ? 'ALL' : implode(',', $sel);
                if ($pid > 0) {
                    $db->prepare('UPDATE web_plans SET name = ?, levels = ?, duration_days = ?, sort_order = ?, is_active = ? WHERE id = ?')
                       ->execute([$name, $levels, $dur, $sort, $act, $pid]);
                    $_SESSION['adm_flash'] = ['ok', 'Plan එක සුරැකුවා.'];
                } else {
                    $code = strtolower(trim((string) ($_POST['code'] ?? '')));
                    if (!preg_match('/^[a-z0-9_\-]{2,40}$/', $code)) {
                        $_SESSION['adm_flash'] = ['err', 'Code එක a-z, 0-9, - හෝ _ පමණක් (අකුරු 2-40).'];
                    } else {
                        try {
                            $db->prepare('INSERT INTO web_plans (code, name, levels, duration_days, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)')
                               ->execute([$code, $name, $levels, $dur, $sort, $act]);
                            $_SESSION['adm_flash'] = ['ok', 'අලුත් plan එක එකතු කළා.'];
                        } catch (PDOException $e) {
                            $_SESSION['adm_flash'] = ['err', 'මේ code එක දැනටමත් තියෙනවා.'];
                        }
                    }
                }
            }
        }
    } catch (Throwable $e) {
        error_log('[admin-users] ' . $e->getMessage());
        $_SESSION['adm_flash'] = ['err', 'දෝෂයක්: tables හදලාද? (plans_progress_tables.sql run කළාද බලන්න)'];
    }

    header('Location: ' . $back);
    exit;
}

/* ============================================================ common data */
$flash = $_SESSION['adm_flash'] ?? null;
unset($_SESSION['adm_flash']);
$cred = $_SESSION['adm_cred'] ?? null;          // stays until the admin closes it

$tab = in_array($_GET['tab'] ?? '', ['overview', 'users', 'plans', 'traffic'], true) ? $_GET['tab'] : 'overview';
$planOpts = $db->query('SELECT id, name, levels, duration_days FROM web_plans WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================ OVERVIEW data */
if ($tab === 'overview') {
    $total    = (int) $db->query('SELECT COUNT(*) FROM web_users')->fetchColumn();
    $new7     = (int) $db->query('SELECT COUNT(*) FROM web_users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')->fetchColumn();
    $liveUsr  = (int) $db->query('SELECT COUNT(DISTINCT up.user_id) FROM web_user_plans up WHERE ' . ADM_LIVE)->fetchColumn();
    $noPlan   = max(0, $total - $liveUsr);
    $learn7   = (int) $db->query('SELECT COUNT(DISTINCT user_id) FROM web_progress WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')->fetchColumn();

    // visitors snapshot (optional - ignored if visitors table is not ready)
    $ovVis = null;
    try {
        $vc = adm_vcols($db);
        if ($vc['ok']) {
            $tc = $vc['tc'];
            $ovVis = [
                'online' => (int) $db->query("SELECT COUNT(DISTINCT session_id) FROM visitors WHERE `$tc` >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)")->fetchColumn(),
                'today'  => (int) $db->query("SELECT COUNT(DISTINCT ip_address) FROM visitors WHERE DATE(`$tc`) = CURDATE()")->fetchColumn(),
            ];
        }
    } catch (Throwable $e) { $ovVis = null; }

    $expiring = $db->query("SELECT u.id, u.name, u.email, u.phone, p.name AS plan, up.expires_at
                              FROM web_user_plans up JOIN web_users u ON u.id = up.user_id JOIN web_plans p ON p.id = up.plan_id
                             WHERE up.status = 'active' AND up.expires_at > NOW() AND up.expires_at <= DATE_ADD(NOW(), INTERVAL 7 DAY)
                             ORDER BY up.expires_at ASC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    $recent = $db->query('SELECT u.* FROM web_users u ORDER BY u.id DESC LIMIT 8')->fetchAll(PDO::FETCH_ASSOC);

    $acts = $db->query("SELECT u.id, u.name, p.name AS plan, up.expires_at, up.created_at, up.activated_by, up.status
                          FROM web_user_plans up JOIN web_users u ON u.id = up.user_id JOIN web_plans p ON p.id = up.plan_id
                         ORDER BY up.id DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
}

/* ============================================================ USERS data */
if ($tab === 'users') {
    $q   = trim((string) ($_GET['q'] ?? ''));
    $flt = in_array($_GET['f'] ?? '', ['all', 'with_plan', 'no_plan', 'expiring', 'disabled'], true) ? $_GET['f'] : 'all';
    $per = 25;
    $page = max(1, (int) ($_GET['page'] ?? 1));

    $liveSql = 'EXISTS (SELECT 1 FROM web_user_plans up WHERE up.user_id = u.id AND ' . ADM_LIVE . ')';
    $where = []; $par = [];

    if ($q !== '') {
        $like = '%' . $q . '%';
        $idq  = ctype_digit(ltrim($q, '#')) ? (int) ltrim($q, '#') : 0;
        $ph   = ltrim(preg_replace('/\D/', '', $q), '0');             // 0771234567 -> 771234567
        $phL  = strlen($ph) >= 5 ? '%' . $ph . '%' : '#no-phone#';
        $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.id = ?)';
        array_push($par, $like, $like, $phL, $idq);
    }
    if ($flt === 'with_plan')      $where[] = $liveSql;
    elseif ($flt === 'no_plan')    $where[] = 'NOT ' . $liveSql;
    elseif ($flt === 'disabled')   $where[] = 'u.is_active = 0';
    elseif ($flt === 'expiring')   $where[] = "EXISTS (SELECT 1 FROM web_user_plans up WHERE up.user_id = u.id AND up.status = 'active'
                                                       AND up.expires_at > NOW() AND up.expires_at <= DATE_ADD(NOW(), INTERVAL 7 DAY))";
    $wsql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $c = $db->prepare('SELECT COUNT(*) FROM web_users u ' . $wsql);
    $c->execute($par);
    $count = (int) $c->fetchColumn();
    $pages = max(1, (int) ceil($count / $per));
    $page  = min($page, $pages);

    $st = $db->prepare('SELECT u.* FROM web_users u ' . $wsql . ' ORDER BY u.id DESC LIMIT ' . (int) $per . ' OFFSET ' . (int) (($page - 1) * $per));
    $st->execute($par);
    $users = $st->fetchAll(PDO::FETCH_ASSOC);

    /* per-user website visits (needs visitors.user_id) */
    $vstat = [];
    $vc = adm_vcols($db);
    $visitsReady = $vc['ok'] && $vc['user'];
    $lastPagesStmt = null;
    if ($visitsReady) {
        try {
            $tc  = $vc['tc'];
            $ids = array_map(fn($r) => (int) $r['id'], $users);
            if ($ids) {
                $rs = $db->query("SELECT user_id, COUNT(*) AS c, COUNT(DISTINCT DATE(`$tc`)) AS dd, MAX(`$tc`) AS last_t
                                    FROM visitors WHERE user_id IN (" . implode(',', $ids) . ') GROUP BY user_id');
                foreach ($rs->fetchAll(PDO::FETCH_ASSOC) as $r) $vstat[(int) $r['user_id']] = $r;
            }
            $lastPagesStmt = $db->prepare("SELECT `$tc` AS t, page_url, device_type, city, country
                                             FROM visitors WHERE user_id = ? ORDER BY `$tc` DESC, id DESC LIMIT 8");
        } catch (Throwable $e) { $visitsReady = false; error_log('[admin-users visits] ' . $e->getMessage()); }
    }
}

/* ============================================================ PLANS data */
if ($tab === 'plans') {
    $allPlans = $db->query("SELECT p.*, (SELECT COUNT(DISTINCT up.user_id) FROM web_user_plans up WHERE up.plan_id = p.id AND " . ADM_LIVE . ") AS live_users
                              FROM web_plans p ORDER BY p.sort_order, p.id")->fetchAll(PDO::FETCH_ASSOC);
}

/* ============================================================ TRAFFIC data (visitors / user_sessions / activity_log from track.php) */
function adm_top(PDO $db, string $col, string $tc, int $days, int $lim = 8): array {
    if (!in_array($col, ['country', 'city', 'browser', 'device_type', 'operating_system', 'referrer', 'language'], true)) return [];
    $extra = $col === 'referrer' ? " AND referrer NOT IN ('Direct','Internal','')" : '';
    $st = $db->query("SELECT `$col` AS k, COUNT(*) AS c FROM visitors
                       WHERE `$tc` >= DATE_SUB(NOW(), INTERVAL " . (int) $days . " DAY)$extra
                       GROUP BY `$col` ORDER BY c DESC LIMIT " . (int) $lim);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function adm_dur(?float $s): string {
    if ($s === null) return '–';
    $s = (int) round($s);
    return $s >= 60 ? intdiv($s, 60) . 'm ' . ($s % 60) . 's' : $s . 's';
}

if ($tab === 'traffic') {
    $days = in_array((int) ($_GET['d'] ?? 7), [1, 7, 30, 90], true) ? (int) $_GET['d'] : 7;
    $tr = ['err' => null];
    try {
        $vc = adm_vcols($db);
        if (!$vc['ok']) throw new RuntimeException('visitors table එකේ වෙලාව තියෙන column එක හොයාගන්න බැරි වුණා. columns: ' . implode(', ', $vc['cols']));
        $tc   = $vc['tc'];
        $hasU = $vc['user'];
        $tr['hasU'] = $hasU;

        $rng  = "`$tc` >= DATE_SUB(NOW(), INTERVAL " . (int) $days . " DAY)";
        $rngV = "v.`$tc` >= DATE_SUB(NOW(), INTERVAL " . (int) $days . " DAY)";

        $r = $db->query("SELECT COUNT(*) v, COUNT(DISTINCT ip_address) ips, COUNT(DISTINCT session_id) ses FROM visitors WHERE $rng")->fetch(PDO::FETCH_ASSOC);
        $tr['visits'] = (int) $r['v']; $tr['ips'] = (int) $r['ips']; $tr['ses'] = (int) $r['ses'];
        $tr['all'] = (int) $db->query('SELECT COUNT(*) FROM visitors')->fetchColumn();
        $tr['online'] = (int) $db->query("SELECT COUNT(DISTINCT session_id) FROM visitors WHERE `$tc` >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)")->fetchColumn();

        $tr['logged'] = $tr['members'] = null;
        $tr['topusers'] = [];
        if ($hasU) {
            $r = $db->query("SELECT COUNT(*) c, COUNT(DISTINCT user_id) u FROM visitors WHERE $rng AND user_id IS NOT NULL")->fetch(PDO::FETCH_ASSOC);
            $tr['logged'] = (int) $r['c']; $tr['members'] = (int) $r['u'];
            $tr['topusers'] = $db->query("SELECT v.user_id, u.name, COUNT(*) AS c, MAX(v.`$tc`) AS last_t
                                            FROM visitors v JOIN web_users u ON u.id = v.user_id
                                           WHERE $rngV AND v.user_id IS NOT NULL
                                           GROUP BY v.user_id, u.name ORDER BY c DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
        }

        $tr['pages'] = $db->query("SELECT page_url, MAX(page_title) AS t, COUNT(*) AS c FROM visitors WHERE $rng
                                    GROUP BY page_url ORDER BY c DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        $tr['daily'] = $db->query("SELECT DATE(`$tc`) AS d, COUNT(*) AS c, COUNT(DISTINCT ip_address) AS u FROM visitors WHERE $rng
                                    GROUP BY DATE(`$tc`) ORDER BY d DESC LIMIT 31")->fetchAll(PDO::FETCH_ASSOC);
        $tr['country'] = adm_top($db, 'country', $tc, $days);
        $tr['browser'] = adm_top($db, 'browser', $tc, $days);
        $tr['device']  = adm_top($db, 'device_type', $tc, $days);
        $tr['os']      = adm_top($db, 'operating_system', $tc, $days);
        $tr['ref']     = adm_top($db, 'referrer', $tc, $days, 8);

        if ($hasU) {
            $tr['recent'] = $db->query("SELECT v.`$tc` AS t, v.ip_address, v.country, v.city, v.browser, v.device_type, v.page_url, v.referrer, v.user_id, u.name AS uname
                                          FROM visitors v LEFT JOIN web_users u ON u.id = v.user_id
                                         ORDER BY v.`$tc` DESC, v.id DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $tr['recent'] = $db->query("SELECT `$tc` AS t, ip_address, country, city, browser, device_type, page_url, referrer, NULL AS user_id, NULL AS uname
                                          FROM visitors ORDER BY `$tc` DESC, id DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
        }

        try {
            $s = $db->query('SELECT AVG(total_pages_viewed) p, AVG(CASE WHEN total_time_spent > 0 THEN total_time_spent END) t FROM user_sessions
                              WHERE start_time >= DATE_SUB(NOW(), INTERVAL ' . (int) $days . ' DAY)')->fetch(PDO::FETCH_ASSOC);
            $tr['avg_pages'] = $s['p'] !== null ? round((float) $s['p'], 1) : null;
            $tr['avg_time']  = $s['t'] !== null ? (float) $s['t'] : null;
        } catch (Throwable $e) { $tr['avg_pages'] = $tr['avg_time'] = null; }

        /* clicks / outbound links / custom events (activity_log) */
        $tr['ev'] = []; $tr['wa'] = null;
        try {
            $ac = $db->query('SHOW COLUMNS FROM activity_log')->fetchAll(PDO::FETCH_COLUMN);
            $at = null;
            foreach (['created_at', 'log_time', 'logged_at', 'timestamp', 'created'] as $x) {
                if (in_array($x, $ac, true)) { $at = $x; break; }
            }
            $arng = $at ? " AND `$at` >= DATE_SUB(NOW(), INTERVAL " . (int) $days . " DAY)" : '';
            $tr['ev'] = $db->query("SELECT action, description, COUNT(*) AS c FROM activity_log
                                     WHERE action IN ('visitor_click','outbound_link','custom_event')$arng
                                     GROUP BY action, description ORDER BY c DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
            $tr['wa'] = (int) $db->query("SELECT COUNT(*) FROM activity_log
                                           WHERE action IN ('visitor_click','outbound_link')
                                             AND (description LIKE '%wa.me%' OR description LIKE '%whatsapp%')$arng")->fetchColumn();
        } catch (Throwable $e) { /* activity_log optional */ }
    } catch (Throwable $e) {
        error_log('[admin-users traffic] ' . $e->getMessage());
        $tr['err'] = 'Visitor data කියවන්න බැරි වුණා: ' . $e->getMessage();
    }
}

function adm_url(array $over = []): string {
    $p = array_merge($_GET, $over);
    foreach ($p as $k => $v) { if ($v === '' || $v === null) unset($p[$k]); }
    return 'admin-users.php' . ($p ? '?' . http_build_query($p) : '');
}
?>
<!doctype html>
<html lang="si">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Admin · Learn Japanese</title>
<style>
  *{box-sizing:border-box}
  body{font-family:system-ui,-apple-system,"Noto Sans Sinhala",sans-serif;background:#f1f3f9;color:#1f2340;margin:0}
  a{color:#4f46e5;text-decoration:none}
  .top{background:#fff;box-shadow:0 2px 8px rgba(30,35,80,.08);position:sticky;top:0;z-index:5}
  .top-in{max-width:980px;margin:0 auto;padding:12px 18px;display:flex;align-items:center;gap:14px;flex-wrap:wrap}
  .brand{font-weight:800;font-size:1.05rem}
  .brand span{color:#4f46e5}
  .nav{display:flex;gap:6px;flex-wrap:wrap}
  .nav a{padding:8px 16px;border-radius:999px;font-weight:700;font-size:.88rem;color:#5b6180}
  .nav a.on{background:#4f46e5;color:#fff}
  .spacer{flex:1}
  .wrap{max-width:980px;margin:0 auto;padding:18px}
  h2{font-size:1.05rem;margin:22px 0 10px}
  input,select,button,textarea{font:inherit}
  input[type=text],input[type=number],input[type=email],input[type=tel],select{padding:9px 12px;border:1px solid #c9cde0;border-radius:10px;background:#fff;min-width:0}
  button,.btn{background:#4f46e5;color:#fff;border:0;border-radius:10px;padding:9px 16px;font-weight:700;cursor:pointer;display:inline-block}
  button.soft,.btn.soft{background:#eef0ff;color:#4f46e5}
  .btn.wa{background:#25d366;color:#fff}
  button.danger{background:#dc2626;padding:5px 10px;font-size:.78rem}
  button.ok{background:#059669;padding:5px 10px;font-size:.78rem}
  .flash{padding:12px 14px;border-radius:12px;margin-bottom:14px;font-weight:600}
  .flash.ok{background:#d1fae5;color:#065f46}.flash.err{background:#fee2e2;color:#991b1b}
  .card{background:#fff;border-radius:16px;padding:16px;margin-bottom:14px;box-shadow:0 2px 8px rgba(30,35,80,.08)}
  .card.cred{border:2px solid #4f46e5}
  .card.cred textarea{width:100%;border:1px solid #c9cde0;border-radius:10px;padding:10px 12px;background:#f6f7fc;color:#1f2340;resize:vertical;line-height:1.55;font-size:.9rem}
  .cbtns{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:10px}
  .stats{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-top:12px}
  .stats:first-child{margin-top:0}
  @media(min-width:720px){.stats{grid-template-columns:repeat(5,1fr)}}
  .stat{background:#fff;border-radius:16px;padding:14px;text-align:center;box-shadow:0 2px 8px rgba(30,35,80,.08)}
  .stat b{display:block;font-size:1.6rem;color:#4f46e5}.stat span{font-size:.76rem;font-weight:700;color:#5b6180}
  .row{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap}
  .name{font-weight:800;font-size:1.02rem}
  .id{background:#eef0ff;color:#4f46e5;border-radius:999px;padding:2px 10px;font-weight:800;font-size:.8rem;margin-left:6px}
  .meta{font-size:.82rem;color:#5b6180;margin-top:4px;line-height:1.6}
  .sub{font-size:.78rem;font-weight:800;color:#5b6180;margin:12px 0 6px}
  .pl{display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;background:#f6f7fc;border-radius:10px;padding:8px 10px;margin-bottom:6px;font-size:.84rem}
  .on{color:#059669;font-weight:800}.off{color:#dc2626;font-weight:800}
  .nav a.on,.filters a.on{color:#fff}
  .act{display:grid;grid-template-columns:1.4fr .8fr 1.4fr auto;gap:8px;align-items:center}
  @media(max-width:700px){.act{grid-template-columns:1fr 1fr}.act select,.act input[name=note]{grid-column:1/-1}}
  .addf{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:8px;align-items:center}
  @media(max-width:700px){.addf{grid-template-columns:1fr}}
  .none{color:#8a8fab;font-size:.84rem}
  .filters{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0 14px}
  .filters a{padding:6px 14px;border-radius:999px;background:#fff;font-weight:700;font-size:.82rem;color:#5b6180;box-shadow:0 1px 4px rgba(30,35,80,.1)}
  .filters a.on{background:#4f46e5}
  .bar{display:flex;gap:8px}.bar input{flex:1}
  .pager{display:flex;gap:8px;justify-content:center;align-items:center;margin:18px 0;font-size:.86rem}
  table{width:100%;border-collapse:collapse;font-size:.84rem}
  th,td{text-align:left;padding:7px 8px;border-bottom:1px solid #eceef7}th{color:#5b6180;font-size:.74rem}
  details summary{cursor:pointer;font-size:.8rem;font-weight:800;color:#4f46e5;margin-top:10px}
  .pf{display:grid;grid-template-columns:1.4fr 1.6fr .7fr .5fr auto auto;gap:8px;align-items:center}
  @media(max-width:760px){.pf{grid-template-columns:1fr 1fr}}
  .lv{display:flex;gap:8px;flex-wrap:wrap;font-size:.82rem}
  .lv label{background:#f6f7fc;border-radius:8px;padding:5px 9px;cursor:pointer}
  .g2{display:grid;grid-template-columns:1fr;gap:12px}
  @media(min-width:720px){.g2{grid-template-columns:1fr 1fr}}
  .br{position:relative;display:flex;justify-content:space-between;gap:8px;padding:6px 9px;margin-bottom:4px;border-radius:8px;background:#f6f7fc;font-size:.84rem;overflow:hidden}
  .br i{position:absolute;left:0;top:0;bottom:0;background:#e0e3ff;z-index:0}
  .br span,.br b{position:relative;z-index:1;word-break:break-all}
  .scroll{overflow-x:auto}
  td.url{max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .tag{display:inline-block;background:#eef0ff;color:#4f46e5;border-radius:999px;padding:1px 8px;font-size:.72rem;font-weight:800}
  .tag.g{background:#d1fae5;color:#065f46}
  .dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#059669;margin-right:5px}
</style>
</head>
<body>

<div class="top"><div class="top-in">
  <div class="brand">Learn<span>Japanese</span> · Admin</div>
  <div class="nav">
    <a href="<?php echo adm_h(adm_url(['tab' => 'overview', 'q' => null, 'f' => null, 'page' => null])); ?>" class="<?php echo $tab === 'overview' ? 'on' : ''; ?>">📊 Overview</a>
    <a href="<?php echo adm_h(adm_url(['tab' => 'users', 'q' => null, 'f' => null, 'page' => null])); ?>" class="<?php echo $tab === 'users' ? 'on' : ''; ?>">👥 Users</a>
    <a href="<?php echo adm_h(adm_url(['tab' => 'plans', 'q' => null, 'f' => null, 'page' => null])); ?>" class="<?php echo $tab === 'plans' ? 'on' : ''; ?>">🎟 Plans</a>
    <a href="<?php echo adm_h(adm_url(['tab' => 'traffic', 'q' => null, 'f' => null, 'page' => null])); ?>" class="<?php echo $tab === 'traffic' ? 'on' : ''; ?>">📈 Visitors</a>
  </div>
  <div class="spacer"></div>
  <a href="/" class="none">← Site</a>
  <a href="admin-users.php?logout=1" class="none">Logout</a>
</div></div>

<div class="wrap">
<?php if ($flash): ?><div class="flash <?php echo adm_h($flash[0]); ?>"><?php echo adm_h($flash[1]); ?></div><?php endif; ?>
<?php if (is_array($cred)) adm_cred_card($cred, $csrf, $self); ?>

<?php /* ================================================= OVERVIEW ================================================= */ ?>
<?php if ($tab === 'overview'): ?>

  <div class="stats">
    <div class="stat"><b><?php echo $total; ?></b><span>මුළු users</span></div>
    <div class="stat"><b><?php echo $new7; ?></b><span>මේ සතියේ අලුත්</span></div>
    <div class="stat"><b><?php echo $liveUsr; ?></b><span>Active plan ඇති</span></div>
    <div class="stat"><b><?php echo $noPlan; ?></b><span>Plan නැති</span></div>
    <div class="stat"><b><?php echo $learn7; ?></b><span>මේ සතියේ ඉගෙන ගත්</span></div>
  </div>
  <?php if ($ovVis): ?>
    <div class="none" style="margin-top:10px"><span class="dot"></span>දැන් site එකේ (විනාඩි 5): <b><?php echo $ovVis['online']; ?></b>
      · අද unique visitors: <b><?php echo $ovVis['today']; ?></b>
      · <a href="<?php echo adm_h(adm_url(['tab' => 'traffic', 'q' => null, 'f' => null, 'page' => null])); ?>">Visitors බලන්න →</a></div>
  <?php endif; ?>

  <div style="margin-top:14px"><a class="btn" href="<?php echo adm_h(adm_url(['tab' => 'users', 'q' => null, 'f' => null, 'page' => null])); ?>#addUser">➕ අලුත් user කෙනෙක් එකතු කරන්න</a></div>

  <h2>🆕 අලුත්ම users (ඉක්මනින් activate කරන්න)</h2>
  <?php foreach ($recent as $u): $uid = (int) $u['id']; $plans = la_user_plans($uid);
        $hasLive = false; foreach ($plans as $p) { if ($p['live']) { $hasLive = true; break; } } ?>
    <div class="card">
      <div class="row">
        <div>
          <span class="name"><?php echo adm_h($u['name']); ?></span><span class="id">#<?php echo $uid; ?></span>
          <div class="meta">
            <?php if ($u['email']): ?>✉ <?php echo adm_h($u['email']); ?><?php endif; ?>
            <?php if ($u['phone']): ?> 📱 +<?php echo adm_h($u['phone']); ?><?php endif; ?>
            · <?php echo adm_h(substr((string) $u['created_at'], 0, 16)); ?>
          </div>
        </div>
        <div><?php echo $hasLive ? '<span class="on">Active plan ✓</span>' : '<span class="off">Plan නැහැ</span>'; ?></div>
      </div>
      <div class="sub">Activate</div>
      <?php adm_activate_form($uid, $planOpts, $csrf, $self); ?>
      <div style="margin-top:10px"><?php adm_reset_form($uid, !empty($u['email']), $csrf, $self); ?></div>
    </div>
  <?php endforeach; ?>
  <?php if (!$recent): ?><div class="card none">තාම users නැහැ.</div><?php endif; ?>

  <h2>⏳ දින 7ක් ඇතුළත කල් ඉකුත් වෙන plans</h2>
  <div class="card">
    <?php if (!$expiring): ?><div class="none">කිසිවක් නැහැ.</div><?php else: ?>
    <table>
      <tr><th>User</th><th>Plan</th><th>කල් ඉකුත් වෙන්නේ</th><th></th></tr>
      <?php foreach ($expiring as $r): ?>
        <tr>
          <td><?php echo adm_h($r['name']); ?> <span class="none">#<?php echo (int) $r['id']; ?></span></td>
          <td><?php echo adm_h($r['plan']); ?></td>
          <td><?php echo adm_h(substr((string) $r['expires_at'], 0, 10)); ?></td>
          <td><a href="<?php echo adm_h(adm_url(['tab' => 'users', 'q' => '#' . (int) $r['id'], 'f' => null, 'page' => null])); ?>">දීර්ඝ කරන්න →</a></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

  <h2>🧾 මෑතකදී කළ activations</h2>
  <div class="card">
    <?php if (!$acts): ?><div class="none">තාම නැහැ.</div><?php else: ?>
    <table>
      <tr><th>User</th><th>Plan</th><th>දක්වා</th><th>කළේ</th></tr>
      <?php foreach ($acts as $r): ?>
        <tr>
          <td><?php echo adm_h($r['name']); ?> <span class="none">#<?php echo (int) $r['id']; ?></span></td>
          <td><?php echo adm_h($r['plan']); ?><?php echo $r['status'] === 'revoked' ? ' <span class="off">(revoked)</span>' : ''; ?></td>
          <td><?php echo $r['expires_at'] === null ? 'lifetime' : adm_h(substr((string) $r['expires_at'], 0, 10)); ?></td>
          <td class="none"><?php echo adm_h(substr((string) $r['created_at'], 0, 16)); ?> · <?php echo adm_h($r['activated_by']); ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

<?php /* ================================================= USERS ================================================= */ ?>
<?php elseif ($tab === 'users'): ?>

  <!-- ➕ add a user by hand -->
  <details class="card" id="addUser" <?php echo (!$count && $q === '') ? 'open' : ''; ?>>
    <summary style="margin-top:0;font-size:.95rem">➕ අලුත් user කෙනෙක් එකතු කරන්න (password ස්වයංක්‍රීයව හැදේ)</summary>
    <form method="post" class="addf" style="margin-top:12px">
      <input type="hidden" name="csrf" value="<?php echo adm_h($csrf); ?>">
      <input type="hidden" name="back" value="admin-users.php?tab=users">
      <input type="hidden" name="action" value="add_user">
      <input type="text" name="name" maxlength="100" placeholder="නම" required>
      <input type="email" name="email" maxlength="150" placeholder="Email (අවශ්‍ය නැත්නම් හිස්)">
      <input type="tel" name="phone" maxlength="20" placeholder="WhatsApp අංකය (0771234567)">
      <button type="submit">Create</button>
      <label class="none" style="grid-column:1/-1"><input type="checkbox" name="send_email" value="1" checked> හදපු ගමන් Email එකටත් යවන්න (Email එකක් දුන්නොත්)</label>
      <div class="none" style="grid-column:1/-1">ශ්‍රී ලංකා අංක 0 න් පටන් ගත්තොත් +94 ස්වයංක්‍රීයව දානවා. වෙනත් රටක් නම් රට code එක සමඟ ලියන්න (උදා: 819050295822). Email හෝ අංකය එකක් අවශ්‍යයි.</div>
    </form>
  </details>

  <form class="bar" method="get">
    <input type="hidden" name="tab" value="users">
    <input type="hidden" name="f" value="<?php echo adm_h($flt); ?>">
    <input type="text" name="q" value="<?php echo adm_h($q); ?>" placeholder="නම / ඊමේල් / අංකය / #ගිණුම් අංකය">
    <button type="submit">හොයන්න</button>
  </form>

  <div class="filters">
    <?php foreach (['all' => 'සියල්ල', 'with_plan' => 'Active plan ඇති', 'no_plan' => 'Plan නැති', 'expiring' => 'ඉක්මනින් ඉකුත්වෙන', 'disabled' => 'අක්‍රීය'] as $k => $label): ?>
      <a href="<?php echo adm_h(adm_url(['tab' => 'users', 'f' => $k === 'all' ? null : $k, 'page' => null])); ?>" class="<?php echo $flt === $k ? 'on' : ''; ?>"><?php echo $label; ?></a>
    <?php endforeach; ?>
  </div>
  <div class="none" style="margin-bottom:10px">මුළු <?php echo $count; ?> ක් · පිටුව <?php echo $page; ?>/<?php echo $pages; ?></div>

  <?php if (!$users): ?><div class="card none">කිසිවෙක් හොයාගන්න බැරි වුණා.</div><?php endif; ?>

  <?php foreach ($users as $u):
      $uid   = (int) $u['id'];
      $plans = la_user_plans($uid);
      $pg    = la_progress_summary($uid);
      $g = $m = $l = $qz = 0;
      foreach ($pg as $r) { $g += $r['grammar']; $m += $r['mastered']; $l += $r['lessons']; $qz += $r['quizzes']; }
      $vs = $vstat[$uid] ?? null;
  ?>
    <div class="card">
      <div class="row">
        <div>
          <span class="name"><?php echo adm_h($u['name']); ?></span><span class="id">#<?php echo $uid; ?></span>
          <?php if (!(int) $u['is_active']): ?><span class="off"> · අක්‍රියයි</span><?php endif; ?>
          <div class="meta">
            <?php if ($u['email']): ?>✉ <?php echo adm_h($u['email']); ?><?php endif; ?>
            <?php if ($u['phone']): ?> &nbsp; 📱 +<?php echo adm_h($u['phone']); ?><?php endif; ?>
            <?php if ($u['google_id']): ?> &nbsp; (Google)<?php endif; ?><br>
            එක්වුණේ <?php echo adm_h(substr((string) $u['created_at'], 0, 10)); ?> ·
            අන්තිම login <?php echo adm_h($u['last_login'] ? substr((string) $u['last_login'], 0, 16) : '–'); ?>
            <?php if ($visitsReady): ?><br>
              👁 Site visits <b><?php echo $vs ? (int) $vs['c'] : 0; ?></b>
              <?php if ($vs): ?>· දවස් <b><?php echo (int) $vs['dd']; ?></b> · අන්තිමට <b><?php echo adm_h(adm_ago((string) $vs['last_t'])); ?></b><?php else: ?><span class="none">· තාම data නැහැ</span><?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="meta" style="text-align:right">
          Grammar <b><?php echo $g; ?></b> (mastered <?php echo $m; ?>)<br>
          පාඩම් <b><?php echo $l; ?></b> · Quiz <b><?php echo $qz; ?></b>
        </div>
      </div>

      <div class="sub">Plans</div>
      <?php if (!$plans): ?><div class="none">Plan නැහැ.</div><?php endif; ?>
      <?php foreach ($plans as $p): ?>
        <div class="pl">
          <span>
            <b><?php echo adm_h($p['name']); ?></b>
            (<?php echo adm_h(la_levels_label((string) $p['levels'])); ?>) ·
            <?php echo $p['expires_at'] === null ? 'ජීවිත කාලය' : adm_h(substr((string) $p['expires_at'], 0, 10)) . ' දක්වා'; ?>
            · <span class="<?php echo $p['live'] ? 'on' : 'off'; ?>"><?php echo $p['live'] ? 'Active' : 'Expired'; ?></span>
            <?php if ($p['note']): ?><span class="none"> · <?php echo adm_h($p['note']); ?></span><?php endif; ?>
          </span>
          <form method="post" onsubmit="return confirm('මේ plan එක ඉවත් කරන්නද?');">
            <input type="hidden" name="csrf" value="<?php echo adm_h($csrf); ?>">
            <input type="hidden" name="back" value="<?php echo adm_h($self); ?>">
            <input type="hidden" name="action" value="revoke">
            <input type="hidden" name="user_id" value="<?php echo $uid; ?>">
            <input type="hidden" name="activation_id" value="<?php echo (int) $p['id']; ?>">
            <button type="submit" class="danger">Revoke</button>
          </form>
        </div>
      <?php endforeach; ?>

      <div class="sub">Plan එකක් Activate / දීර්ඝ කරන්න</div>
      <?php adm_activate_form($uid, $planOpts, $csrf, $self); ?>

      <details>
        <summary>ඉගෙනීමේ ප්‍රගතිය (level අනුව)</summary>
        <table>
          <tr><th>Level</th><th>Grammar</th><th>Mastered</th><th>පාඩම්</th><th>Quiz</th><th>Quiz %</th></tr>
          <?php foreach (LA_LEVELS as $lv): $r = $pg[$lv]; ?>
            <tr>
              <td><b><?php echo $lv; ?></b></td><td><?php echo $r['grammar']; ?></td><td><?php echo $r['mastered']; ?></td>
              <td><?php echo $r['lessons']; ?></td><td><?php echo $r['quizzes']; ?></td>
              <td><?php echo $r['quiz_total'] > 0 ? (int) round($r['quiz_score'] / $r['quiz_total'] * 100) . '%' : '–'; ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      </details>

      <?php if ($visitsReady && $vs && $lastPagesStmt):
          $lastPagesStmt->execute([$uid]);
          $lp = $lastPagesStmt->fetchAll(PDO::FETCH_ASSOC); ?>
        <details>
          <summary>👁 අන්තිමට බැලූ පිටු</summary>
          <div class="scroll">
          <table>
            <tr><th>වෙලාව</th><th>පිටුව</th><th>Device</th><th>ස්ථානය</th></tr>
            <?php foreach ($lp as $r): ?>
              <tr>
                <td><?php echo adm_h(substr((string) $r['t'], 5, 11)); ?></td>
                <td class="url" title="<?php echo adm_hd($r['page_url']); ?>"><?php echo adm_hd($r['page_url']); ?></td>
                <td><?php echo adm_h($r['device_type']); ?></td>
                <td><?php echo adm_hd(trim(($r['city'] ?? '') . ' ' . ($r['country'] ?? ''))); ?></td>
              </tr>
            <?php endforeach; ?>
          </table>
          </div>
        </details>
      <?php endif; ?>

      <div class="sub">ගිණුම</div>
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <?php adm_reset_form($uid, !empty($u['email']), $csrf, $self); ?>
        <form method="post" style="display:inline" onsubmit="return confirm('<?php echo (int) $u['is_active'] ? 'මේ ගිණුම අක්‍රීය කරන්නද? (ඇතුල් වෙන්න බැරි වෙනවා)' : 'මේ ගිණුම නැවත සක්‍රීය කරන්නද?'; ?>');">
          <input type="hidden" name="csrf" value="<?php echo adm_h($csrf); ?>">
          <input type="hidden" name="back" value="<?php echo adm_h($self); ?>">
          <input type="hidden" name="action" value="toggle_user">
          <input type="hidden" name="user_id" value="<?php echo $uid; ?>">
          <input type="hidden" name="to" value="<?php echo (int) $u['is_active'] ? 0 : 1; ?>">
          <?php if ((int) $u['is_active']): ?>
            <button type="submit" class="danger">ගිණුම අක්‍රීය කරන්න</button>
          <?php else: ?>
            <button type="submit" class="ok">ගිණුම සක්‍රීය කරන්න</button>
          <?php endif; ?>
        </form>
      </div>
    </div>
  <?php endforeach; ?>

  <?php if ($pages > 1): ?>
    <div class="pager">
      <?php if ($page > 1): ?><a class="btn soft" href="<?php echo adm_h(adm_url(['page' => $page - 1])); ?>">← කලින්</a><?php endif; ?>
      <span>පිටුව <?php echo $page; ?> / <?php echo $pages; ?></span>
      <?php if ($page < $pages): ?><a class="btn soft" href="<?php echo adm_h(adm_url(['page' => $page + 1])); ?>">ඊළඟ →</a><?php endif; ?>
    </div>
  <?php endif; ?>

<?php /* ================================================= TRAFFIC ================================================= */ ?>
<?php elseif ($tab === 'traffic'): ?>

  <div class="filters">
    <?php foreach ([1 => 'පැය 24', 7 => 'දින 7', 30 => 'දින 30', 90 => 'දින 90'] as $k => $label): ?>
      <a href="<?php echo adm_h(adm_url(['tab' => 'traffic', 'd' => $k])); ?>" class="<?php echo $days === $k ? 'on' : ''; ?>"><?php echo $label; ?></a>
    <?php endforeach; ?>
  </div>

  <?php if ($tr['err']): ?>
    <div class="flash err"><?php echo adm_h($tr['err']); ?></div>
  <?php else:
    $lists = [
        'ref'     => '🔗 Referrers',
        'country' => '🌍 රටවල්',
        'browser' => '🧭 Browsers',
        'device'  => '📱 Devices',
        'os'      => '💻 Operating systems',
    ];
    $evIcon = ['outbound_link' => '🔗', 'visitor_click' => '👆', 'custom_event' => '⭐'];
  ?>
    <div class="stats">
      <div class="stat"><b><?php echo $tr['visits']; ?></b><span>Page views</span></div>
      <div class="stat"><b><?php echo $tr['ips']; ?></b><span>Unique IPs</span></div>
      <div class="stat"><b><?php echo $tr['ses']; ?></b><span>Sessions</span></div>
      <div class="stat"><b><?php echo $tr['avg_pages'] ?? '–'; ?></b><span>Pages / session</span></div>
      <div class="stat"><b><?php echo adm_h(adm_dur($tr['avg_time'])); ?></b><span>සාමාන්‍ය කාලය</span></div>
    </div>
    <div class="stats">
      <div class="stat"><b><span class="dot"></span><?php echo $tr['online']; ?></b><span>දැන් online (මිනි. 5)</span></div>
      <div class="stat"><b><?php echo $tr['members'] ?? '–'; ?></b><span>Login වුණු users</span></div>
      <div class="stat"><b><?php echo $tr['logged'] ?? '–'; ?></b><span>ඔවුන්ගේ page views</span></div>
      <div class="stat"><b><?php echo $tr['logged'] !== null ? max(0, $tr['visits'] - $tr['logged']) : '–'; ?></b><span>Guest page views</span></div>
      <div class="stat"><b><?php echo $tr['wa'] ?? '–'; ?></b><span>WhatsApp clicks</span></div>
    </div>
    <div class="none" style="margin-top:8px">මුළු වාර්තා (සියලු කාලය): <?php echo (int) $tr['all']; ?></div>
    <?php if (empty($tr['hasU'])): ?>
      <div class="flash err" style="margin-top:10px">⚠ visitors table එකේ <b>user_id</b> column එක නැහැ. <b>tracking-upgrade.sql</b> run කළාම visits ඒ ඒ user ට සම්බන්ධ වෙනවා.</div>
    <?php endif; ?>

    <?php if (!empty($tr['topusers'])): ?>
      <h2>🏆 වැඩිපුරම site එකේ ඉන්න users</h2>
      <div class="card">
        <?php $mx = max(1, (int) $tr['topusers'][0]['c']);
        foreach ($tr['topusers'] as $r): ?>
          <div class="br"><i style="width:<?php echo (int) round($r['c'] / $mx * 100); ?>%"></i>
            <span><a href="<?php echo adm_h(adm_url(['tab' => 'users', 'q' => '#' . (int) $r['user_id'], 'f' => null, 'page' => null, 'd' => null])); ?>"><?php echo adm_h($r['name']); ?></a>
              <small class="none">#<?php echo (int) $r['user_id']; ?> · <?php echo adm_h(adm_ago((string) $r['last_t'])); ?></small></span>
            <b><?php echo (int) $r['c']; ?></b></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <h2>📄 වැඩිපුර බැලූ පිටු</h2>
    <div class="card">
      <?php if (!$tr['pages']): ?><div class="none">දත්ත නැහැ.</div><?php else:
        $mx = max(1, (int) $tr['pages'][0]['c']);
        foreach ($tr['pages'] as $r): ?>
          <div class="br"><i style="width:<?php echo (int) round($r['c'] / $mx * 100); ?>%"></i>
            <span><?php echo adm_hd($r['page_url']); ?><?php echo $r['t'] ? ' <small class="none">· ' . adm_hd(mb_substr((string) $r['t'], 0, 50)) . '</small>' : ''; ?></span>
            <b><?php echo (int) $r['c']; ?></b></div>
      <?php endforeach; endif; ?>
    </div>

    <h2>📅 දිනපතා</h2>
    <div class="card">
      <?php if (!$tr['daily']): ?><div class="none">දත්ත නැහැ.</div><?php else:
        $mx = 1; foreach ($tr['daily'] as $r) $mx = max($mx, (int) $r['c']);
        foreach ($tr['daily'] as $r): ?>
          <div class="br"><i style="width:<?php echo (int) round($r['c'] / $mx * 100); ?>%"></i>
            <span><?php echo adm_h($r['d']); ?> <small class="none">· <?php echo (int) $r['u']; ?> unique</small></span>
            <b><?php echo (int) $r['c']; ?></b></div>
      <?php endforeach; endif; ?>
    </div>

    <h2>👆 Clicks &amp; Events</h2>
    <div class="card">
      <?php if (!$tr['ev']): ?><div class="none">තාම click/event data නැහැ (tracking.js දාලා තියෙනවාද බලන්න).</div><?php else:
        $mx = max(1, (int) $tr['ev'][0]['c']);
        foreach ($tr['ev'] as $r): ?>
          <div class="br"><i style="width:<?php echo (int) round($r['c'] / $mx * 100); ?>%"></i>
            <span><?php echo $evIcon[$r['action']] ?? '•'; ?> <?php echo adm_hd(mb_substr((string) $r['description'], 0, 140)); ?></span>
            <b><?php echo (int) $r['c']; ?></b></div>
      <?php endforeach; endif; ?>
    </div>

    <div class="g2">
      <?php foreach ($lists as $key => $title): $rows = $tr[$key]; ?>
        <div class="card">
          <div class="sub" style="margin-top:0"><?php echo $title; ?></div>
          <?php if (!$rows): ?><div class="none">දත්ත නැහැ.</div><?php else:
            $mx = max(1, (int) $rows[0]['c']);
            foreach ($rows as $r): ?>
              <div class="br"><i style="width:<?php echo (int) round($r['c'] / $mx * 100); ?>%"></i>
                <span><?php echo adm_hd(mb_substr((string) ($r['k'] ?: 'Unknown'), 0, 60)); ?></span>
                <b><?php echo (int) $r['c']; ?></b></div>
          <?php endforeach; endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <h2>🕒 මෑතකදී ආපු අය (අන්තිම 30)</h2>
    <div class="card scroll">
      <table>
        <tr><th>වෙලාව</th><th>User</th><th>IP</th><th>ස්ථානය</th><th>Browser</th><th>Device</th><th>පිටුව</th><th>Referrer</th></tr>
        <?php foreach ($tr['recent'] as $r): ?>
          <tr>
            <td><?php echo adm_h(substr((string) $r['t'], 5, 11)); ?></td>
            <td><?php if ($r['user_id']): ?>
                  <a href="<?php echo adm_h(adm_url(['tab' => 'users', 'q' => '#' . (int) $r['user_id'], 'f' => null, 'page' => null, 'd' => null])); ?>"><span class="tag g"><?php echo adm_h($r['uname'] ?: ('#' . (int) $r['user_id'])); ?></span></a>
                <?php else: ?><span class="none">guest</span><?php endif; ?></td>
            <td><?php echo adm_h($r['ip_address']); ?></td>
            <td><?php echo adm_hd(trim(($r['city'] ?? '') . ' ' . ($r['country'] ?? ''))); ?></td>
            <td><?php echo adm_h($r['browser']); ?></td>
            <td><?php echo adm_h($r['device_type']); ?></td>
            <td class="url" title="<?php echo adm_hd($r['page_url']); ?>"><?php echo adm_hd($r['page_url']); ?></td>
            <td class="url" title="<?php echo adm_hd($r['referrer']); ?>"><?php echo adm_hd($r['referrer']); ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <?php if (empty($tr['hasU'])): ?>
      <div class="none" style="margin-top:8px">⚠ user_id column එක නැති නිසා guest/user වෙන් කරලා පෙන්නන්න බැහැ.</div>
    <?php endif; ?>
  <?php endif; ?>
   <a href="admin-missed.php">🔎 Missed words</a>
<?php /* ================================================= PLANS ================================================= */ ?>
<?php else: ?>

  <h2>🎟 Plans</h2>
  <div class="none" style="margin-bottom:10px">Plan එකක් මකන්නේ නැහැ — පාවිච්චි නොකරන ඒවා "Active" tick එක අයින් කරන්න (users ගේ history රැකේ).</div>

  <?php foreach ($allPlans as $p):
      $sel = strtoupper((string) $p['levels']) === 'ALL' ? LA_LEVELS : array_map('trim', explode(',', strtoupper((string) $p['levels']))); ?>
    <div class="card">
      <form method="post" class="pf">
        <input type="hidden" name="csrf" value="<?php echo adm_h($csrf); ?>">
        <input type="hidden" name="back" value="<?php echo adm_h($self); ?>">
        <input type="hidden" name="action" value="plan_save">
        <input type="hidden" name="plan_id" value="<?php echo (int) $p['id']; ?>">
        <input type="text" name="name" value="<?php echo adm_h($p['name']); ?>" maxlength="100" required>
        <div class="lv">
          <?php foreach (LA_LEVELS as $lv): ?>
            <label><input type="checkbox" name="levels[]" value="<?php echo $lv; ?>" <?php echo in_array($lv, $sel, true) ? 'checked' : ''; ?>> <?php echo $lv; ?></label>
          <?php endforeach; ?>
        </div>
        <input type="number" name="duration_days" min="1" max="3650" value="<?php echo $p['duration_days'] === null ? '' : (int) $p['duration_days']; ?>" placeholder="දවස් (හිස්=lifetime)">
        <input type="number" name="sort_order" value="<?php echo (int) $p['sort_order']; ?>" title="පිළිවෙල">
        <label class="none"><input type="checkbox" name="is_active" value="1" <?php echo (int) $p['is_active'] ? 'checked' : ''; ?>> Active</label>
        <button type="submit">සුරකින්න</button>
      </form>
      <div class="none" style="margin-top:8px">code: <b><?php echo adm_h($p['code']); ?></b> · දැන් active users: <b><?php echo (int) $p['live_users']; ?></b></div>
    </div>
  <?php endforeach; ?>

  <h2>➕ අලුත් Plan එකක්</h2>
  <div class="card">
    <form method="post" class="pf">
      <input type="hidden" name="csrf" value="<?php echo adm_h($csrf); ?>">
      <input type="hidden" name="back" value="<?php echo adm_h($self); ?>">
      <input type="hidden" name="action" value="plan_save">
      <input type="hidden" name="plan_id" value="0">
      <input type="text" name="name" maxlength="100" placeholder="Plan නම (උදා: N2 Plan)" required>
      <div class="lv">
        <?php foreach (LA_LEVELS as $lv): ?>
          <label><input type="checkbox" name="levels[]" value="<?php echo $lv; ?>"> <?php echo $lv; ?></label>
        <?php endforeach; ?>
      </div>
      <input type="number" name="duration_days" min="1" max="3650" placeholder="දවස් (හිස්=lifetime)">
      <input type="number" name="sort_order" value="0" title="පිළිවෙල">
      <input type="text" name="code" maxlength="40" placeholder="code (උදා: n2)" required>
      <input type="hidden" name="is_active" value="1">
      <button type="submit">එකතු කරන්න</button>
    </form>
  </div>

<?php endif; ?>
</div>

<script>
/* Copy the login-details message */
(function () {
  var b = document.getElementById('credCopy'), t = document.getElementById('credText');
  if (!b || !t) return;
  b.addEventListener('click', function () {
    var done = function () { b.textContent = '✓ Copy කළා'; setTimeout(function () { b.textContent = '📋 Copy'; }, 1800); };
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(t.value).then(done, function () { t.select(); document.execCommand('copy'); done(); });
    } else { t.select(); t.setSelectionRange(0, 99999); try { document.execCommand('copy'); done(); } catch (e) {} }
  });
})();
</script>
</body>
</html>