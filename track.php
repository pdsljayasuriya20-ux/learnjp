<?php
/**
 * track.php - Visitor Tracking Handler (upgraded)
 * Receives data from tracking.js and stores it in the database.
 *
 * What's new:
 *  - links every visit to the logged-in user (visitors.user_id) + back-fills earlier hits of the same session
 *  - real external referrer (sent from JS - HTTP_REFERER on an AJAX call is just the current page)
 *  - ignores bots, admin pages and the admin's own visits
 *  - scroll events de-duplicated, per-session rate limit
 *  - no wildcard CORS (same-origin only)
 *  - works even before the user_id column is added (run tracking-upgrade.sql to enable it)
 */

require_once 'config.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

/* ============================================================ helpers */

/** Logged-in site user id (0 = guest). Put YOUR login session key first in the list if different. */
function trackUserId(): int {
    if (function_exists('la_current_user_id')) {
        return (int) la_current_user_id();
    }
    foreach (['la_user_id', 'la_uid', 'user_id', 'uid', 'web_user_id'] as $k) {
        if (!empty($_SESSION[$k]) && is_numeric($_SESSION[$k])) return (int) $_SESSION[$k];
    }
    return 0;
}

function trackIsBot(string $ua): bool {
    if ($ua === '' || $ua === 'Unknown') return true;
    return (bool) preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|uptime|curl|wget|python|httpclient|headless|lighthouse|pingdom|semrush|ahrefs/i', $ua);
}

function trackSkipPage(string $url): bool {
    $path = strtolower((string) parse_url($url, PHP_URL_PATH));
    return $path === '' ? false : (bool) preg_match('#(^/admin|admin-users\.php|track\.php|/includes/)#', $path);
}

function trackHasUserCol(PDO $db): bool {
    static $has = null;
    if ($has === null) {
        try {
            $has = (bool) $db->query("SHOW COLUMNS FROM visitors LIKE 'user_id'")->fetch();
        } catch (Throwable $e) { $has = false; }
    }
    return $has;
}

/** max ~400 tracking events per session per hour */
function trackAllowed(): bool {
    $w = (int) ($_SESSION['trk_win'] ?? 0);
    if ($w < time() - 3600) { $_SESSION['trk_win'] = time(); $_SESSION['trk_n'] = 0; }
    $_SESSION['trk_n'] = (int) ($_SESSION['trk_n'] ?? 0) + 1;
    return $_SESSION['trk_n'] <= 400;
}

function trackClean(string $s, int $max): string {
    return mb_substr(sanitizeInput($s), 0, $max);
}

/** external referrer or 'Internal' / 'Direct' */
function trackReferrer(string $ref): string {
    $ref = trim($ref);
    if ($ref === '') return 'Direct';
    $rh = strtolower((string) parse_url($ref, PHP_URL_HOST));
    $mh = strtolower(preg_replace('/^www\./', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
    if ($rh !== '' && preg_replace('/^www\./', '', $rh) === $mh) return 'Internal';
    return mb_substr(sanitizeInput($ref), 0, 255);
}

/* ============================================================ actions */

function trackVisitor(string $page_url, string $page_title, string $referrer): bool {
    try {
        $db = getDBConnection();

        $ip_address = getClientIP();
        $user_agent = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 500);
        $language   = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en', 0, 2);
        $agent_info = parseUserAgent($user_agent);
        $uid        = trackUserId();

        if (!isset($_SESSION['visitor_session_id'])) {
            $_SESSION['visitor_session_id'] = session_id();
            $_SESSION['session_start'] = time();
        }
        $session_id = $_SESSION['visitor_session_id'];

        if (!isset($_SESSION['geo_data'])) {
            $_SESSION['geo_data'] = getGeolocation($ip_address);
        }
        $geo = $_SESSION['geo_data'] ?: [];

        $screen = trackClean((string) ($_POST['screen_resolution'] ?? ($_COOKIE['screen_resolution'] ?? 'Unknown')), 20);

        $cols = ['ip_address', 'user_agent', 'page_url', 'page_title', 'referrer', 'country', 'city',
                 'browser', 'device_type', 'operating_system', 'screen_resolution', 'language', 'session_id'];
        $vals = [$ip_address, $user_agent, $page_url, $page_title, $referrer, $geo['country'] ?? null, $geo['city'] ?? null,
                 $agent_info['browser'], $agent_info['device'], $agent_info['os'], $screen, $language, $session_id];

        $hasU = trackHasUserCol($db);
        if ($hasU) { $cols[] = 'user_id'; $vals[] = $uid > 0 ? $uid : null; }

        $db->prepare('INSERT INTO visitors (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')')
           ->execute($vals);

        // user just logged in -> attach this session's earlier (guest) hits to them
        if ($hasU && $uid > 0 && ($_SESSION['trk_linked'] ?? 0) !== $uid) {
            $db->prepare('UPDATE visitors SET user_id = ? WHERE session_id = ? AND user_id IS NULL')->execute([$uid, $session_id]);
            $_SESSION['trk_linked'] = $uid;
        }

        $db->prepare("
            INSERT INTO user_sessions (session_id, ip_address, total_pages_viewed)
            VALUES (:session_id, :ip_address, 1)
            ON DUPLICATE KEY UPDATE
                total_pages_viewed = total_pages_viewed + 1,
                end_time = CURRENT_TIMESTAMP
        ")->execute([':session_id' => $session_id, ':ip_address' => $ip_address]);

        return true;
    } catch (Throwable $e) {
        error_log('Visitor Tracking Error: ' . $e->getMessage());
        return false;
    }
}

function updateTimeSpent(string $session_id, int $time_spent): bool {
    try {
        $db = getDBConnection();
        $st = $db->prepare('UPDATE user_sessions SET total_time_spent = :t, end_time = CURRENT_TIMESTAMP WHERE session_id = :s');
        return $st->execute([':t' => $time_spent, ':s' => $session_id]);
    } catch (Throwable $e) {
        error_log('Time Update Error: ' . $e->getMessage());
        return false;
    }
}

/** write one row into activity_log */
function trackLog(string $action, string $description): bool {
    try {
        $db = getDBConnection();
        $st = $db->prepare('INSERT INTO activity_log (admin_id, action, description, ip_address) VALUES (NULL, :a, :d, :ip)');
        return $st->execute([':a' => $action, ':d' => mb_substr($description, 0, 500), ':ip' => getClientIP()]);
    } catch (Throwable $e) {
        error_log("Tracking log ($action) error: " . $e->getMessage());
        return false;
    }
}

/* ============================================================ routing */

$response = ['success' => false, 'message' => 'No valid action specified'];

try {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $pageUrl = trackClean((string) ($_POST['page_url'] ?? ''), 500);

    if (!empty($_SESSION['adm_ok']) || trackIsBot($ua) || trackSkipPage($pageUrl)) {
        // admin's own visits, bots and admin pages are never counted
        echo json_encode(['success' => true, 'message' => 'Ignored']);
        exit;
    }
    if (!trackAllowed()) {
        echo json_encode(['success' => false, 'message' => 'Rate limited']);
        exit;
    }

    if (!empty($_POST['track_visit'])) {
        $title = trackClean((string) ($_POST['page_title'] ?? ''), 255);
        if (isset($_POST['screen_resolution'])) {
            setcookie('screen_resolution', trackClean((string) $_POST['screen_resolution'], 20), time() + 86400 * 30, '/');
        }
        $ref = trackReferrer((string) ($_POST['referrer'] ?? ''));
        $response = trackVisitor($pageUrl, $title, $ref)
            ? ['success' => true, 'message' => 'Visit tracked']
            : ['success' => false, 'message' => 'Failed to track visit'];

    } elseif (!empty($_POST['update_time'])) {
        $sid = $_SESSION['visitor_session_id'] ?? null;
        $t = min(86400, max(0, (int) ($_POST['time_spent'] ?? 0)));
        $response = ($sid && $t > 0 && updateTimeSpent($sid, $t))
            ? ['success' => true, 'message' => 'Time updated']
            : ['success' => false, 'message' => 'Invalid session or time'];

    } elseif (!empty($_POST['track_click'])) {
        $d = sprintf('Click on %s: %s (URL: %s)',
            trackClean((string) ($_POST['element_type'] ?? 'unknown'), 30),
            trackClean((string) ($_POST['element_text'] ?? ''), 100),
            trackClean((string) ($_POST['element_href'] ?? 'N/A'), 250));
        $response = trackLog('visitor_click', $d)
            ? ['success' => true, 'message' => 'Click tracked']
            : ['success' => false, 'message' => 'Failed to track click'];

    } elseif (!empty($_POST['track_outbound'])) {
        $dest = trackClean((string) ($_POST['destination'] ?? ''), 250);
        $response = trackLog('outbound_link', "Outbound link clicked: {$dest} from {$pageUrl}")
            ? ['success' => true, 'message' => 'Outbound link tracked']
            : ['success' => false, 'message' => 'Failed to track outbound link'];

    } elseif (!empty($_POST['track_scroll'])) {
        $depth = min(100, max(0, (int) ($_POST['scroll_depth'] ?? 0)));
        $key = $pageUrl . '#' . $depth;
        if (!empty($_SESSION['trk_scroll'][$key])) {
            $response = ['success' => true, 'message' => 'Already tracked'];
        } else {
            $_SESSION['trk_scroll'][$key] = 1;
            if (count($_SESSION['trk_scroll']) > 200) $_SESSION['trk_scroll'] = [];
            $response = trackLog('scroll_depth', "Scrolled {$depth}% on {$pageUrl}")
                ? ['success' => true, 'message' => 'Scroll tracked']
                : ['success' => false, 'message' => 'Failed to track scroll'];
        }

    } elseif (!empty($_POST['track_custom'])) {
        $n = trackClean((string) ($_POST['event_name'] ?? ''), 80);
        $d = trackClean((string) ($_POST['event_data'] ?? ''), 250);
        $response = trackLog('custom_event', "Event: {$n} | Data: {$d}")
            ? ['success' => true, 'message' => 'Custom event tracked']
            : ['success' => false, 'message' => 'Failed to track custom event'];

    } elseif (!empty($_POST['track_error'])) {
        error_log('JS Error: ' . trackClean((string) ($_POST['error_message'] ?? ''), 300)
            . ' in ' . trackClean((string) ($_POST['error_file'] ?? ''), 200)
            . ':' . trackClean((string) ($_POST['error_line'] ?? ''), 10));
        $response = ['success' => true, 'message' => 'Error logged'];
    }
} catch (Throwable $e) {
    error_log('Tracking Error: ' . $e->getMessage());
    $response = ['success' => false, 'message' => 'Server error occurred'];
}

echo json_encode($response);
exit;
