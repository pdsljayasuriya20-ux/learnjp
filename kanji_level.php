<?php
// ---------------------------------------------------------------
// kanji_level.php — shared page for JLPT N4 / N3 / N2 / N1 kanji lists.
// Do not open directly. kanjin4.php, kanjin3.php, kanjin2.php, kanjin1.php
// set the variables below and then require this file.
//   * first $pgFreeLimit kanji (30) are free for everybody
//   * the rest need an activated package for that level (table user_packages)
//   * locked kanji are NEVER sent to the browser
//
// FIX (images): meaning / stroke-order / example images showed as broken
//   icons. Now:
//   1) pg_url() understands full URLs (https://...), data: URIs, "./", "../",
//      backslashes, spaces and Japanese file names.
//   2) $pgImgBase lets you prefix a folder if images live in a sub folder.
//   3) In the browser, an image that fails to load is replaced by a fallback
//      (stroke count text) or its block is hidden — no more "?" icons.
//   4) Set $pgDebug = true to see the exact failing path inside the modal.
// ---------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) { session_start(); }
ini_set('display_errors', 0);
error_reporting(E_ALL);

// ----- values the wrapper pages must set -----
// $pgLevel, $pgBackUrl, $pgQuizUrl, $pageTitle, $pageDescription, $pageKeywords
if (empty($pgLevel)) { http_response_code(404); exit; }
$pgFreeLimit = 30;
$pgDbName    = '';            // other database on same server? e.g. 'kanji_book'
$pgDebug     = false;         // true = show the exact image path that fails to load
$pgImgBase   = '';            // e.g. 'kanji_book/' if DB paths are relative to a sub folder
$pgStrokeBox = '0 0 109 109'; // viewBox of the SVG paths in table practice_strokes (KanjiVG = 109 x 109)
$pgBooksUrl  = 'books.php';
$pgUpgradeUrl = 'upgrade.php?level=' . urlencode($pgLevel);
$pgOwnDb     = ['host' => 'localhost', 'name' => '', 'user' => '', 'pass' => ''];
$pgBackUrl   = $pgBackUrl  ?? 'index.php';
$pgQuizUrl   = $pgQuizUrl  ?? '#';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config.php';

$pgIsLoggedIn = !empty($_SESSION['user_id']);

// ---------- helpers ----------
if (!function_exists('pg_db_all')) {
    function pg_db_all($db, string $sql, array $params = []): array {
        if ($db instanceof PDO) {
            $st = $db->prepare($sql);
            if ($st === false) { throw new RuntimeException('prepare failed: ' . implode(' | ', $db->errorInfo())); }
            if (!$st->execute($params)) { throw new RuntimeException('query failed: ' . implode(' | ', $st->errorInfo())); }
            return $st->fetchAll(PDO::FETCH_ASSOC);
        }
        if ($db instanceof mysqli) {
            $i = 0;
            $sql = preg_replace_callback('/\?/', function () use (&$i, $params, $db) {
                $v = $params[$i++] ?? null;
                return is_int($v) ? (string)$v : "'" . $db->real_escape_string((string)$v) . "'";
            }, $sql);
            $res = $db->query($sql);
            return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        }
        return [];
    }
    function pg_get_db() {
        global $pgOwnDb;
        if (!empty($pgOwnDb['name'])) {
            return new PDO('mysql:host=' . $pgOwnDb['host'] . ';dbname=' . $pgOwnDb['name'] . ';charset=utf8mb4',
                           $pgOwnDb['user'], $pgOwnDb['pass'],
                           [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        }
        if (function_exists('getDB')) { return getDB(); }
        foreach (['pdo', 'db', 'conn', 'mysqli', 'link', 'dbh', 'connection', 'con'] as $n) {
            if (isset($GLOBALS[$n]) && ($GLOBALS[$n] instanceof PDO || $GLOBALS[$n] instanceof mysqli)) { return $GLOBALS[$n]; }
        }
        $sets = [
            ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'],
            ['DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'],
            ['DB_SERVER', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'],
            ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'],
        ];
        foreach ($sets as $s) {
            if (defined($s[0]) && defined($s[1]) && defined($s[2])) {
                return new PDO('mysql:host=' . constant($s[0]) . ';dbname=' . constant($s[1]) . ';charset=utf8mb4',
                               constant($s[2]), defined($s[3]) ? constant($s[3]) : '',
                               [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
            }
        }
        throw new RuntimeException('No database connection found in config.php. Fill $pgOwnDb in kanji_level.php.');
    }
    function pg_t(string $name): string {
        global $pgDbName;
        return ($pgDbName !== '' ? '`' . str_replace('`', '', $pgDbName) . '`.' : '') . '`' . $name . '`';
    }
    /**
     * Turn a DB image path into a safe URL.
     *  - full URLs (http://, https://, //) and data: URIs are returned untouched
     *  - backslashes, "./" and "../" prefixes are cleaned
     *  - each path segment is URL-encoded (spaces, Japanese names, # ? %)
     *  - always rooted at "/" (plus optional $pgImgBase)
     */
    function pg_url($p): string {
        global $pgImgBase;
        $p = trim((string)$p);
        if ($p === '') { return ''; }
        if (preg_match('#^(https?:)?//#i', $p) || stripos($p, 'data:') === 0) { return $p; }
        $p = str_replace('\\', '/', $p);
        $p = preg_replace('#^(\.{1,2}/)+#', '', $p);
        $p = ltrim($p, '/');
        if ($pgImgBase !== '' && strpos($p, trim($pgImgBase, '/') . '/') !== 0) {
            $p = trim($pgImgBase, '/') . '/' . $p;
        }
        $out = [];
        foreach (explode('/', $p) as $seg) {
            if ($seg === '' || $seg === '.' || $seg === '..') { continue; }
            $out[] = rawurlencode(rawurldecode($seg));   // decode first = no double encoding
        }
        return $out ? '/' . implode('/', $out) : '';
    }
    function pg_e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

/** Does this user own an active package for $level? (table: user_packages, see upgrade.sql) */
function pg_has_package(string $level): bool {
    if (empty($_SESSION['user_id'])) { return false; }
    try {
        // user data lives in the site's normal DB (getDB()), NOT necessarily the kanji DB
        $udb = function_exists('getDB') ? getDB() : pg_get_db();
        $rows = pg_db_all($udb,
            "SELECT 1 FROM user_packages
              WHERE user_id = ? AND level_code IN (?, 'ALL')
                AND (expires_at IS NULL OR expires_at > NOW())
              LIMIT 1", [(int)$_SESSION['user_id'], $level]);
        return !empty($rows);
    } catch (Throwable $e) {
        error_log('pg_has_package: ' . $e->getMessage());
        return false;
    }
}

// ---------- load data ----------
$pgHasAccess = pg_has_package($pgLevel);
$pgError   = '';
$pgLevelRow = ['title_si' => 'JLPT ' . $pgLevel . ' කංජි පොත', 'edition_si' => ''];
$pgKanji   = [];
$pgLessons = [];
$pgJs      = [];

try {
    $db = pg_get_db();
    if ($db instanceof PDO) { $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); }

    $lv = pg_db_all($db, 'SELECT * FROM ' . pg_t('levels') . ' WHERE code = ? LIMIT 1', [$pgLevel]);
    if ($lv) { $pgLevelRow = $lv[0]; }

    $rows = pg_db_all($db,
        'SELECT k.*, p.subtitle AS page_subtitle, l.id AS lesson_id, l.title AS lesson_title
           FROM ' . pg_t('kanji') . ' k
           JOIN ' . pg_t('pages') . '   p  ON p.id = k.page_id
           JOIN ' . pg_t('lessons') . ' l  ON l.id = p.lesson_id
           JOIN ' . pg_t('levels') . '  lv ON lv.id = l.level_id
          WHERE lv.code = ?
          ORDER BY l.sort_order, p.sort_order, k.sort_order, k.id', [$pgLevel]);
    if (!$rows) {
        throw new RuntimeException('No kanji rows found for level "' . $pgLevel . '".');
    }

    $seq = 0;
    $openIds = [];
    foreach ($rows as $r) {
        $seq++;
        $locked = (!$pgHasAccess && $seq > $pgFreeLimit);
        $lid = (int)$r['lesson_id'];
        if (!isset($pgLessons[$lid])) { $pgLessons[$lid] = ['title' => $r['lesson_title'], 'count' => 0]; }
        $pgLessons[$lid]['count']++;

        if ($locked) {
            $pgKanji[] = ['seq' => $seq, 'locked' => true, 'lesson_id' => $lid, 'no' => (int)$r['kanji_number']];
        } else {
            $pgKanji[] = ['seq' => $seq, 'locked' => false, 'lesson_id' => $lid, 'row' => $r];
            $openIds[] = (int)$r['id'];
        }
    }

    $exByKanji = [];
    if ($openIds) {
        $ph  = implode(',', array_fill(0, count($openIds), '?'));
        $exs = pg_db_all($db, "SELECT * FROM " . pg_t('kanji_examples') . " WHERE kanji_id IN ($ph) ORDER BY kanji_id, sort_order, id", $openIds);
        foreach ($exs as $ex) { $exByKanji[(int)$ex['kanji_id']][] = $ex; }
    }

    // stroke order paths — table practice_strokes (kanji_id, stroke_no, path_d)
    $strokesByKanji = [];
    if ($openIds) {
        try {
            $ph2 = implode(',', array_fill(0, count($openIds), '?'));
            $sts = pg_db_all($db, 'SELECT kanji_id, stroke_no, path_d FROM ' . pg_t('practice_strokes') .
                                  " WHERE kanji_id IN ($ph2) ORDER BY kanji_id, stroke_no", $openIds);
            foreach ($sts as $s) {
                $d = trim((string)$s['path_d']);
                // only real SVG path-data characters are allowed
                if ($d !== '' && preg_match('/^[MmLlHhVvCcSsQqTtAaZz0-9eE.,\s+\-]+$/', $d)) {
                    $strokesByKanji[(int)$s['kanji_id']][] = $d;
                }
            }
        } catch (Throwable $e) {
            error_log('kanji_level.php practice_strokes: ' . $e->getMessage());
        }
    }

    foreach ($pgKanji as $k) {
        if ($k['locked']) continue;
        $r = $k['row'];
        $ex = [];
        foreach ($exByKanji[(int)$r['id']] ?? [] as $e) {
            $ex[] = ['w' => $e['word'], 'r' => $e['reading'], 'm' => $e['meaning_si'],
                     'img' => pg_url($e['image']), 'alt' => $e['image_alt_si']];
        }
        $pgJs[(string)$r['id']] = [
            'no'     => (int)$r['kanji_number'],
            'ch'     => $r['character'],
            'on'     => $r['reading_on'],
            'kun'    => $r['reading_kun'],
            'kana'   => $r['kana_under'],
            'mnum'   => $r['meaning_number'],
            'si'     => $r['meaning_si'],
            'strokes'=> (int)$r['stroke_count'],
            'stype'  => $r['stroke_type'],
            'simg'   => pg_url($r['stroke_image']),
            'sp'     => $strokesByKanji[(int)$r['id']] ?? [],
            'mtype'  => $r['meaning_type'],
            'msvg'   => $r['meaning_svg'],
            'mimg'   => pg_url($r['meaning_image']),
            'vimg'   => pg_url($r['visual_image']),   'vcap'  => $r['visual_caption_si'],
            'mnimg'  => pg_url($r['mnemonic_image']), 'mncap' => $r['mnemonic_caption_si'],
            'pimg'   => pg_url($r['parts_image']),    'pcap'  => $r['parts_caption_si'],
            'lesson' => $r['lesson_title'],
            'page'   => $r['page_subtitle'],
            'ex'     => $ex,
        ];
    }
} catch (Throwable $e) {
    error_log('kanji_level.php (' . $pgLevel . '): ' . $e->getMessage());
    $pgError = 'දත්ත පූරණය කිරීමට නොහැකි විය. කරුණාකර පසුව නැවත උත්සාහ කරන්න.';
    if ($pgDebug) { $pgError .= ' [DEBUG: ' . $e->getMessage() . ']'; }
}

$pgTotal   = count($pgKanji);
$pgOpenCnt = count($pgJs);
$pgOrder   = array_keys($pgJs);
$pgLockedExists = ($pgTotal > $pgOpenCnt);

$pgJsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>

<!-- =====================================================
     NEUMORPHISM THEME — Kanji N4–N1 (mobile-first)
     ===================================================== -->
<style>
    :root {
        --nm-bg: #e6e9f2; --nm-light: #ffffff; --nm-dark: #c3c9dc;
        --nm-text: #2d3250; --nm-muted: #7a8099;
        --nm-brand: #4f46e5; --nm-brand-2: #7c6cf5; --nm-green: #10b981; --nm-gold: #f59e0b;
        --nm-radius: 24px; --nm-radius-sm: 16px;
        --nm-raised:    8px 8px 18px var(--nm-dark), -8px -8px 18px var(--nm-light);
        --nm-raised-sm: 5px 5px 11px var(--nm-dark), -5px -5px 11px var(--nm-light);
        --nm-inset:     inset 5px 5px 11px var(--nm-dark), inset -5px -5px 11px var(--nm-light);
        --nm-inset-sm:  inset 3px 3px 7px var(--nm-dark), inset -3px -3px 7px var(--nm-light);
    }
    body { background: var(--nm-bg) !important; color: var(--nm-text); -webkit-tap-highlight-color: transparent; overflow-x: hidden; }
    body.pg-lock { overflow: hidden; }
    .text-muted { color: var(--nm-muted) !important; }
    .jp { font-family: 'Noto Sans JP', sans-serif; }

    @keyframes nmFadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
    @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }

    .pg-wrap { padding-bottom: 1.5rem; }

    .back-btn {
        display: inline-flex; align-items: center; gap: 6px; background: var(--nm-bg); color: var(--nm-muted);
        border-radius: 999px; padding: 9px 18px; font-size: .85rem; font-weight: 700; text-decoration: none;
        box-shadow: var(--nm-raised-sm); transition: box-shadow .2s ease, color .2s ease;
    }
    .back-btn:hover { color: var(--nm-brand); }
    .back-btn:active { box-shadow: var(--nm-inset-sm); }

    .hero-banner {
        background: linear-gradient(135deg, var(--nm-brand) 0%, var(--nm-brand-2) 100%);
        border-radius: var(--nm-radius); color: #fff; padding: 2.2rem 1.5rem; text-align: center;
        box-shadow: var(--nm-raised); position: relative; overflow: hidden; animation: nmFadeUp .5s ease both;
    }
    .hero-banner::before, .hero-banner::after { content: ""; position: absolute; border-radius: 50%; background: rgba(255,255,255,.12); }
    .hero-banner::before { width: 150px; height: 150px; top: -55px; right: -30px; }
    .hero-banner::after  { width: 100px; height: 100px; bottom: -40px; left: 10%; }
    .hero-banner > * { position: relative; z-index: 1; }
    .hero-banner h1 { letter-spacing: .1em; }
    .hero-buy {
        display: inline-flex; align-items: center; gap: 8px; margin-top: 16px;
        background: var(--nm-bg); color: var(--nm-brand); text-decoration: none;
        font-weight: 800; font-size: .92rem; padding: 11px 22px; border-radius: 999px;
        box-shadow: 5px 5px 12px rgba(30, 20, 120, .35), -3px -3px 9px rgba(255, 255, 255, .25);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .hero-buy:hover { color: var(--nm-brand); transform: translateY(-2px); }
    .hero-buy:active { box-shadow: inset 3px 3px 7px var(--nm-dark), inset -3px -3px 7px var(--nm-light); transform: scale(.97); }

    .stat-card { background: var(--nm-bg); border-radius: var(--nm-radius-sm); box-shadow: var(--nm-raised-sm); padding: 14px 6px; text-align: center; height: 100%; animation: nmFadeUp .55s ease both; }
    .stat-card .num {
        font-size: 1.45rem; font-weight: 800; line-height: 1.2;
        background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2));
        -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent;
    }
    .stat-card .small { font-size: .72rem; }

    .app-card { background: var(--nm-bg); border-radius: var(--nm-radius); padding: 1.3rem 1.1rem; border: 0; box-shadow: var(--nm-raised); margin-bottom: 1.5rem; animation: nmFadeUp .55s ease both; }
    .pg-search { display: flex; align-items: center; gap: 10px; background: var(--nm-bg); border-radius: 999px; box-shadow: var(--nm-inset); padding: 4px 18px; }
    .pg-search i { color: var(--nm-muted); }
    .pg-search input { flex: 1; min-width: 0; border: 0; outline: 0; background: transparent; color: var(--nm-text); font-size: .95rem; padding: 11px 0; font-family: inherit; }
    .pg-pills { display: flex; gap: 10px; overflow-x: auto; padding: 14px 6px 8px; margin: 0 -6px; scrollbar-width: none; -webkit-overflow-scrolling: touch; }
    .pg-pills::-webkit-scrollbar { display: none; }
    .pg-pill { flex: 0 0 auto; border: 0; border-radius: 999px; padding: 8px 16px; font-size: .82rem; font-weight: 700; color: var(--nm-muted); background: var(--nm-bg); box-shadow: var(--nm-raised-sm); cursor: pointer; font-family: inherit; white-space: nowrap; transition: box-shadow .2s ease, color .2s ease; }
    .pg-pill:hover { color: var(--nm-brand); }
    .pg-pill[aria-pressed="true"] { color: var(--nm-brand); box-shadow: var(--nm-inset-sm); }
    @media (min-width: 768px) { .pg-pills { flex-wrap: wrap; overflow: visible; } }

    .pg-guest { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; background: var(--nm-bg); border-radius: var(--nm-radius-sm); box-shadow: var(--nm-inset-sm); padding: 14px 16px; border-left: 5px solid var(--nm-gold); }
    .pg-guest .ic { font-size: 1.5rem; }
    .pg-guest .tx { flex: 1 1 220px; font-size: .88rem; font-weight: 600; }

    .kanji-col { animation: nmFadeUp .45s ease both; }
    .kanji-box {
        width: 100%; height: 100%; border: 0; text-align: center; color: var(--nm-text); font-family: inherit;
        background: var(--nm-bg); border-radius: var(--nm-radius-sm); box-shadow: var(--nm-raised-sm);
        padding: 1.1rem .7rem .9rem; position: relative; cursor: pointer;
        display: flex; flex-direction: column; justify-content: space-between; min-width: 0;
        transition: box-shadow .2s ease, transform .2s ease;
    }
    .kanji-box:hover  { transform: translateY(-3px); }
    .kanji-box:active { box-shadow: var(--nm-inset-sm); transform: scale(.97); }
    .kanji-box:focus-visible { outline: 3px solid var(--nm-brand-2); outline-offset: 2px; }
    .kanji-badge { position: absolute; top: 10px; right: 10px; font-size: .7rem; font-weight: 800; color: var(--nm-muted); background: var(--nm-bg); box-shadow: var(--nm-inset-sm); border-radius: 999px; padding: 2px 9px; }
    .kanji-char {
        font-family: 'Noto Sans JP', sans-serif; font-size: 3.1rem; font-weight: 700; line-height: 1.1; margin: 1rem 0 .4rem;
        background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2));
        -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent;
    }
    .kanji-meaning { font-size: .86rem; font-weight: 700; margin-bottom: .6rem; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 2.2em; }
    .reading-box { text-align: left; background: var(--nm-bg); box-shadow: var(--nm-inset-sm); border-radius: 12px; padding: 7px 10px; font-size: .74rem; line-height: 1.5; }
    .reading-box div { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .reading-box b.on  { color: var(--nm-brand); }
    .reading-box b.kun { color: var(--nm-green); }

    .kanji-box.is-locked .kanji-char { -webkit-text-fill-color: initial; background: none; color: var(--nm-dark); font-size: 2.6rem; display: flex; align-items: center; justify-content: center; min-height: 3.4rem; }
    .kanji-box.is-locked .lock-txt { font-size: .76rem; font-weight: 700; color: var(--nm-muted); }
    .kanji-box.is-locked .lock-btn { margin-top: .6rem; font-size: .74rem; font-weight: 800; color: var(--nm-brand); background: var(--nm-bg); box-shadow: var(--nm-raised-sm); border-radius: 999px; padding: 6px 12px; display: inline-block; }

    .pg-empty { display: none; text-align: center; padding: 2rem 1rem; color: var(--nm-muted); font-weight: 600; }

    .btn-brand, .btn-brand-outline { border-radius: 999px; font-weight: 800; border: 0; text-decoration: none; cursor: pointer; font-family: inherit; font-size: 1rem; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: box-shadow .2s ease, transform .2s ease; }
    .btn-brand { background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); color: #fff; padding: 13px 26px; box-shadow: var(--nm-raised-sm); }
    .btn-brand:hover { color: #fff; transform: translateY(-2px); }
    .btn-brand-outline { background: var(--nm-bg); color: var(--nm-brand); padding: 13px 24px; box-shadow: var(--nm-raised-sm); }
    .btn-brand-outline:hover { color: var(--nm-brand); transform: translateY(-2px); }
    .btn-brand:active, .btn-brand-outline:active { box-shadow: var(--nm-inset-sm); transform: scale(.97); }
    .btn-sm-nm { padding: 9px 18px; font-size: .88rem; }

    .pg-modal { position: fixed; inset: 0; z-index: 99999; display: none; align-items: center; justify-content: center; background: rgba(45, 50, 80, .5); -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px); padding: 16px; opacity: 0; transition: opacity .2s ease; }
    .pg-modal.show { display: flex; }
    .pg-modal.vis  { opacity: 1; }
    .pg-sheet { position: relative; width: 100%; max-width: 560px; max-height: 92vh; overflow-y: auto; -webkit-overflow-scrolling: touch; background: var(--nm-bg); border-radius: 28px; padding: 22px 20px 20px; box-shadow: 0 20px 50px rgba(0,0,0,.3); transform: translateY(18px); transition: transform .25s ease; }
    .pg-modal.vis .pg-sheet { transform: translateY(0); }
    .pg-x { position: absolute; top: 12px; right: 14px; width: 38px; height: 38px; border: 0; border-radius: 50%; background: var(--nm-bg); color: var(--nm-muted); box-shadow: var(--nm-raised-sm); font-size: 1.3rem; line-height: 1; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 2; }
    .pg-x:active { box-shadow: var(--nm-inset-sm); }
    .pg-mtitle { font-weight: 800; font-size: 1rem; margin: 0 48px 12px 0; }

    .d-char { font-family: 'Noto Sans JP', sans-serif; font-size: 5.2rem; font-weight: 700; line-height: 1.1; text-align: center; margin: .2rem 0; background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent; }
    .d-mean { text-align: center; font-weight: 800; font-size: 1.25rem; margin-bottom: .2rem; }
    .d-kana { text-align: center; color: var(--nm-muted); font-weight: 700; font-size: .9rem; margin-bottom: 1rem; }
    .d-box { background: var(--nm-bg); box-shadow: var(--nm-inset-sm); border-radius: var(--nm-radius-sm); padding: 12px 14px; margin-bottom: 14px; }
    .d-box small { display: block; color: var(--nm-muted); font-weight: 700; font-size: .72rem; margin-bottom: 2px; }
    .d-box .v { font-weight: 700; font-size: .98rem; word-break: break-word; }
    .d-box hr { margin: 10px 0; border-color: var(--nm-dark); opacity: .6; }
    .d-sec { font-weight: 800; font-size: .85rem; color: var(--nm-brand); margin: 16px 0 8px; }
    .d-meaning-vis { text-align: center; }
    .d-meaning-vis svg { width: 130px; max-width: 60%; height: auto; fill: var(--nm-brand); }
    .d-meaning-vis img, .d-fig img, .d-stroke img { max-width: 100%; height: auto; border-radius: 12px; }
    .d-fig { margin: 0 0 12px; text-align: center; }
    .d-fig figcaption { font-size: .8rem; color: var(--nm-muted); font-weight: 600; margin-top: 6px; }
    .d-stroke { text-align: center; font-weight: 700; }
    .d-strokes { width: 220px; max-width: 75%; height: auto; display: block; margin: 0 auto 10px; background: var(--nm-bg); box-shadow: var(--nm-inset-sm); border-radius: 16px; }
    .d-strokes .guide path { fill: none; stroke: var(--nm-dark); stroke-width: 3.5; stroke-linecap: round; stroke-linejoin: round; }
    .d-strokes .sp { fill: none; stroke: var(--nm-brand); stroke-width: 3.5; stroke-linecap: round; stroke-linejoin: round; stroke-dasharray: 1; stroke-dashoffset: 1; animation: spDraw .8s ease forwards; }
    .d-strokes .nums text { font-size: 6px; font-weight: 700; fill: #dc2626; font-family: sans-serif; }
    @keyframes spDraw { to { stroke-dashoffset: 0; } }
    @media (prefers-reduced-motion: reduce) { .d-strokes .sp { stroke-dashoffset: 0 !important; } }
    .d-replay { margin-top: 4px; }
    .d-imgerr { font-size: .72rem; color: #dc2626; font-weight: 700; word-break: break-all; padding: 6px 8px; }
    .d-ex { display: flex; gap: 12px; align-items: center; background: var(--nm-bg); box-shadow: var(--nm-raised-sm); border-radius: 14px; padding: 10px 12px; margin-bottom: 10px; }
    .d-ex .w { font-family: 'Noto Sans JP', sans-serif; font-weight: 700; font-size: 1.15rem; color: var(--nm-brand); min-width: 3.2em; }
    .d-ex .r { font-size: .78rem; color: var(--nm-muted); font-weight: 700; }
    .d-ex .m { font-size: .86rem; font-weight: 600; }
    .d-ex img { width: 54px; height: 54px; object-fit: cover; border-radius: 10px; margin-left: auto; flex: 0 0 auto; }
    .d-nav { display: flex; justify-content: space-between; gap: 10px; margin-top: 16px; }
    .d-nav .btn-brand-outline[disabled] { opacity: .4; pointer-events: none; }

    .tips-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px; }
    .tips-list li { background: var(--nm-bg); box-shadow: var(--nm-inset-sm); border-radius: 14px; padding: 10px 14px; font-size: .9rem; line-height: 1.5; }
    .login-ic { font-size: 2.6rem; text-align: center; margin-bottom: 4px; }

    @media (max-width: 575.98px) {
        .app-card { padding: 1.1rem .85rem; border-radius: 20px; }
        .hero-banner { padding: 1.7rem 1.1rem; border-radius: 20px; }
        .hero-banner h1 { font-size: 1.9rem !important; }
        .hero-banner p  { font-size: .9rem !important; }
        .stat-card .num { font-size: 1.25rem; }
        .kanji-char { font-size: 2.6rem; margin-top: .9rem; }
        .kanji-box.is-locked .kanji-char { font-size: 2.2rem; }
        .reading-box { font-size: .7rem; }
        .pg-modal { align-items: flex-end; padding: 0; }
        .pg-sheet { border-radius: 26px 26px 0 0; max-height: 94vh; padding: 20px 16px calc(18px + env(safe-area-inset-bottom)); }
        .d-char { font-size: 4.4rem; }
        .pg-actions .btn-brand, .pg-actions .btn-brand-outline { width: 100%; }
        .d-nav .btn-brand-outline { flex: 1; }
    }
</style>

<div class="pg-wrap">

    <a href="<?php echo pg_e($pgBackUrl); ?>" class="back-btn mb-3">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> ආපසු Dashboard වෙත
    </a>

    <div class="hero-banner mb-4 mt-3">
        <h1 class="fw-extrabold fs-2 mb-2 jp">漢字 <?php echo pg_e($pgLevel); ?></h1>
        <p class="mb-0 opacity-90 fs-6"><?php echo pg_e($pgLevelRow['title_si']); ?> — කංජි අක්ෂර <?php echo (int)$pgTotal; ?></p>
        <?php if (!empty($pgLevelRow['edition_si'])): ?>
            <p class="mb-0 mt-1 small opacity-75"><?php echo pg_e($pgLevelRow['edition_si']); ?></p>
        <?php endif; ?>
        <?php if ($pgLockedExists): ?>
        <a href="<?php echo pg_e($pgUpgradeUrl); ?>" class="hero-buy">
            <i class="fa-solid fa-crown" aria-hidden="true"></i> සම්පූර්ණ කංජි විවෘත කරන්න (Upgrade)
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
        <?php else: ?>
        <a href="<?php echo pg_e($pgBooksUrl); ?>" class="hero-buy">
            <i class="fa-solid fa-book" aria-hidden="true"></i> මුද්‍රිත පොත මිල දී ගන්න
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
        <?php endif; ?>
    </div>

    <?php if ($pgError): ?>
        <div class="app-card text-center"><p class="mb-0 fw-bold"><?php echo pg_e($pgError); ?></p></div>
    <?php else: ?>

    <div class="row g-2 g-md-3 mb-4">
        <div class="col-6 col-md-3"><div class="stat-card"><div class="num"><?php echo (int)$pgTotal; ?></div><div class="small text-muted">මුළු කංජි</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="num" id="pgShown"><?php echo (int)$pgTotal; ?></div><div class="small text-muted">පෙන්වන ගණන</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="num"><?php echo (int)min($pgFreeLimit, $pgTotal); ?></div><div class="small text-muted">නොමිලේ බැලිය හැක</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="num"><?php echo $pgHasAccess ? '✓' : (int)$pgOpenCnt; ?></div><div class="small text-muted"><?php echo $pgHasAccess ? 'සියල්ල විවෘතයි' : 'ඔබට විවෘත'; ?></div></div></div>
    </div>

    <?php if ($pgLockedExists): ?>
        <div class="pg-guest mb-4">
            <span class="ic" aria-hidden="true">🔒</span>
            <div class="tx">පළමු කංජි <?php echo (int)$pgFreeLimit; ?> සියලු දෙනාට නොමිලේ. ඉතිරි කංජි <?php echo (int)($pgTotal - $pgOpenCnt); ?> බැලීමට <?php echo pg_e($pgLevel); ?> Package එකට Upgrade කරන්න.</div>
            <a href="<?php echo pg_e($pgUpgradeUrl); ?>" class="btn-brand btn-sm-nm"><i class="fa-solid fa-crown" aria-hidden="true"></i> Upgrade</a>
        </div>
    <?php endif; ?>

    <div class="app-card">
        <label class="pg-search" for="pgQ">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" id="pgQ" placeholder="කංජි අක්ෂරය, තේරුම හෝ කියවීම සොයන්න..." autocomplete="off">
        </label>
        <div class="pg-pills" role="group" aria-label="පාඩම්">
            <button type="button" class="pg-pill" data-lesson="all" aria-pressed="true">සියල්ල</button>
            <?php foreach ($pgLessons as $lid => $ls): ?>
                <button type="button" class="pg-pill" data-lesson="<?php echo (int)$lid; ?>" aria-pressed="false"><?php echo pg_e($ls['title']); ?></button>
            <?php endforeach; ?>
        </div>
        <?php if ($pgLockedExists): ?>
            <p class="small text-muted mb-0 mt-1 px-1" id="pgSearchNote" style="display:none">සෙවීම ඔබට විවෘත කංජි අතර පමණි. සියල්ල සෙවීමට Package එක Upgrade කරන්න.</p>
        <?php endif; ?>
    </div>

    <div class="row g-3 mb-4" id="pgGrid">
        <?php foreach ($pgKanji as $k): ?>
            <?php if ($k['locked']): ?>
                <div class="col-6 col-md-4 col-lg-3 kanji-col" data-lesson="<?php echo (int)$k['lesson_id']; ?>" data-locked="1" data-search="">
                    <button type="button" class="kanji-box is-locked" data-open-login="1" aria-label="කංජි #<?php echo (int)$k['seq']; ?> — Upgrade අවශ්‍යයි">
                        <span class="kanji-badge">#<?php echo (int)$k['seq']; ?></span>
                        <div class="kanji-char" aria-hidden="true"><i class="fa-solid fa-lock"></i></div>
                        <div>
                            <div class="lock-txt">Package එකක් අවශ්‍යයි</div>
                            <span class="lock-btn">Upgrade</span>
                        </div>
                    </button>
                </div>
            <?php else:
                $r  = $k['row'];
                $kun = ($r['reading_kun'] !== null && $r['reading_kun'] !== '') ? $r['reading_kun'] : '—';
                $needle = mb_strtolower($r['character'] . ' ' . $r['meaning_si'] . ' ' . $r['reading_on'] . ' ' . $r['reading_kun'] . ' ' . $r['kana_under'] . ' ' . $r['meaning_number'], 'UTF-8');
            ?>
                <div class="col-6 col-md-4 col-lg-3 kanji-col" data-lesson="<?php echo (int)$k['lesson_id']; ?>" data-locked="0" data-search="<?php echo pg_e($needle); ?>">
                    <button type="button" class="kanji-box" data-open="<?php echo (int)$r['id']; ?>" aria-label="<?php echo pg_e($r['character'] . ' ' . $r['meaning_si']); ?>">
                        <span class="kanji-badge">#<?php echo (int)$k['seq']; ?></span>
                        <div class="kanji-char"><?php echo pg_e($r['character']); ?></div>
                        <div class="kanji-meaning"><?php echo pg_e($r['meaning_si']); ?></div>
                        <div class="reading-box">
                            <div><b class="on">音:</b> <span class="jp"><?php echo pg_e($r['reading_on']); ?></span></div>
                            <div><b class="kun">訓:</b> <span class="jp"><?php echo pg_e($kun); ?></span></div>
                        </div>
                    </button>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <div class="pg-empty" id="pgEmpty">ගැළපෙන කංජි හමු නොවීය.</div>

    <div class="pg-actions d-flex flex-wrap align-items-center justify-content-center gap-3 my-4 py-2">
        <a href="<?php echo pg_e($pgQuizUrl); ?>" class="btn-brand"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> පුහුණු ප්‍රශ්නාවලිය</a>
        <button type="button" class="btn-brand-outline" data-open-tips="1"><i class="fa-solid fa-lightbulb" aria-hidden="true"></i> අධ්‍යයන උපදෙස්</button>
        <?php if ($pgLockedExists): ?>
            <a href="<?php echo pg_e($pgUpgradeUrl); ?>" class="btn-brand-outline"><i class="fa-solid fa-crown" aria-hidden="true"></i> Package Upgrade</a>
        <?php else: ?>
            <a href="<?php echo pg_e($pgBooksUrl); ?>" class="btn-brand-outline"><i class="fa-solid fa-book" aria-hidden="true"></i> මුද්‍රිත පොත මිල දී ගන්න</a>
        <?php endif; ?>
    </div>

    <?php endif; ?>
</div>

<!-- Kanji detail -->
<div class="pg-modal" id="pgDetail" role="dialog" aria-modal="true" aria-labelledby="dTitle">
    <div class="pg-sheet">
        <button type="button" class="pg-x" data-close aria-label="වසන්න">&times;</button>
        <div class="pg-mtitle" id="dTitle"></div>
        <div id="dBody"></div>
        <div class="d-nav">
            <button type="button" class="btn-brand-outline btn-sm-nm" id="dPrev"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i> පෙර</button>
            <button type="button" class="btn-brand-outline btn-sm-nm" id="dNext">ඊළඟ <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
        </div>
    </div>
</div>

<!-- Upgrade required -->
<div class="pg-modal" id="pgLogin" role="dialog" aria-modal="true" aria-labelledby="lgTitle">
    <div class="pg-sheet text-center">
        <button type="button" class="pg-x" data-close aria-label="වසන්න">&times;</button>
        <div class="login-ic" aria-hidden="true">👑</div>
        <h2 class="fs-5 fw-bold mb-2" id="lgTitle">සම්පූර්ණ <?php echo pg_e($pgLevel); ?> කංජි බැලීමට Upgrade කරන්න</h2>
        <p class="text-muted small mb-3">පළමු කංජි <?php echo (int)$pgFreeLimit; ?> සියලු දෙනාට නොමිලේ. ඉතිරි කංජි අක්ෂර බැලීමට <?php echo pg_e($pgLevel); ?> Package එක activate කරගන්න.</p>
        <div class="d-flex flex-column gap-2">
            <a href="<?php echo pg_e($pgUpgradeUrl); ?>" class="btn-brand"><i class="fa-solid fa-crown" aria-hidden="true"></i> Packages බලන්න</a>
            <button type="button" class="btn-brand-outline" data-close>පසුව</button>
        </div>
    </div>
</div>

<!-- Study tips -->
<div class="pg-modal" id="pgTips" role="dialog" aria-modal="true" aria-labelledby="tpTitle">
    <div class="pg-sheet">
        <button type="button" class="pg-x" data-close aria-label="වසන්න">&times;</button>
        <div class="pg-mtitle" id="tpTitle">💡 අධ්‍යයන උපදෙස්</div>
        <ul class="tips-list">
            <li>📌 <strong>දිනපතා ටිකක් ඉගෙන ගන්න:</strong> දිනකට කංජි 3-5ක් බැගින් නිතර පුහුණු වන්න.</li>
            <li>📌 <strong>මූල කොටස් හඳුනා ගන්න:</strong> කංජි වල පොදු කොටස් (radicals) තේරුම් ගන්න.</li>
            <li>📌 <strong>ලියා පුහුණු වන්න:</strong> නිවැරදි ඉරි ඇඳීමේ අනුපිළිවෙල (Stroke order) පුහුණු වන්න.</li>
            <li>📌 <strong>වචන ලෙස ඉගෙන ගන්න:</strong> තනි කංජි අක්ෂරයට වඩා වචන සන්දර්භය තුළ මතක තබා ගන්න.</li>
        </ul>
    </div>
</div>

<script>
(function () {
    var DATA  = <?php echo json_encode($pgJs, $pgJsonFlags | JSON_FORCE_OBJECT); ?>;
    var ORDER = <?php echo json_encode($pgOrder, $pgJsonFlags); ?>;
    var HAS_LOCKED = <?php echo $pgLockedExists ? 'true' : 'false'; ?>;
    var DEBUG = <?php echo $pgDebug ? 'true' : 'false'; ?>;
    var STROKE_BOX = <?php echo json_encode($pgStrokeBox, $pgJsonFlags); ?>;

    var body = document.body;
    var lastFocus = null;
    function openModal(el) {
        lastFocus = document.activeElement;
        el.classList.add('show');
        requestAnimationFrame(function () { el.classList.add('vis'); });
        body.classList.add('pg-lock');
        var x = el.querySelector('.pg-x'); if (x) x.focus();
    }
    function closeModal(el) {
        el.classList.remove('vis');
        setTimeout(function () { el.classList.remove('show'); }, 200);
        if (!document.querySelector('.pg-modal.show.vis:not(#' + el.id + ')')) body.classList.remove('pg-lock');
        if (lastFocus && lastFocus.focus) lastFocus.focus();
    }
    document.querySelectorAll('.pg-modal').forEach(function (m) {
        m.addEventListener('click', function (e) {
            if (e.target === m || e.target.closest('[data-close]')) closeModal(m);
        });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { document.querySelectorAll('.pg-modal.show').forEach(closeModal); }
    });

    var detail = document.getElementById('pgDetail');
    var login  = document.getElementById('pgLogin');
    var tips   = document.getElementById('pgTips');
    var dTitle = document.getElementById('dTitle');
    var dBody  = document.getElementById('dBody');
    var dPrev  = document.getElementById('dPrev');
    var dNext  = document.getElementById('dNext');
    var current = null;

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // ---- broken image handling ----
    // data-fb="block"  -> remove the whole section (heading + image)
    // data-fb="text"   -> replace the image with data-fb-text (e.g. "5 ඉරි")
    // data-fb="hide"   -> just hide the image (example thumbnails)
    function imgFailed(img) {
        var mode = img.getAttribute('data-fb');
        if (DEBUG) {
            var msg = document.createElement('div');
            msg.className = 'd-imgerr';
            msg.textContent = '⚠ Image not found: ' + img.getAttribute('src');
            img.parentNode.insertBefore(msg, img);
            img.style.display = 'none';
            return;
        }
        if (mode === 'block') {
            var blk = img.closest('.d-block');
            if (blk && blk.parentNode) blk.parentNode.removeChild(blk);
        } else if (mode === 'text') {
            var t = img.getAttribute('data-fb-text') || '';
            if (t) {
                var span = document.createElement('span');
                span.textContent = t;
                img.parentNode.replaceChild(span, img);
            } else {
                var blk2 = img.closest('.d-block');
                if (blk2 && blk2.parentNode) blk2.parentNode.removeChild(blk2);
            }
        } else {
            img.style.display = 'none';
        }
    }
    function watchImages(root) {
        root.querySelectorAll('img[data-fb]').forEach(function (img) {
            if (img.complete && img.naturalWidth === 0 && img.getAttribute('src')) { imgFailed(img); return; }
            img.addEventListener('error', function () { imgFailed(img); }, { once: true });
        });
    }

    function render(id) {
        var k = DATA[id]; if (!k) return;
        current = id;
        dTitle.textContent = '#' + k.no + ' · ' + (k.lesson || '');

        var h = '';
        h += '<div class="d-char">' + esc(k.ch) + '</div>';
        h += '<div class="d-mean">' + esc(k.si) + '</div>';
        if (k.kana) h += '<div class="d-kana jp">' + esc(k.kana) + '</div>';

        h += '<div class="d-box">'
          +  '<small>ඔන්යෝමි (On\'yomi)</small><div class="v jp">' + esc(k.on) + '</div><hr>'
          +  '<small>කුන්යෝමි (Kun\'yomi)</small><div class="v jp">' + esc(k.kun || '—') + '</div>'
          +  '</div>';

        // meaning (svg or image) — whole block is dropped if the image can't load
        if (k.mtype === 'svg' && k.msvg) {
            h += '<div class="d-block"><div class="d-sec">තේරුම (' + esc(k.mnum) + ')</div>'
              +  '<div class="d-meaning-vis"><svg viewBox="0 0 42 42" role="img" aria-label="' + esc(k.mnum) + '">' + k.msvg + '</svg></div></div>';
        } else if (k.mimg) {
            h += '<div class="d-block"><div class="d-sec">තේරුම (' + esc(k.mnum) + ')</div>'
              +  '<div class="d-meaning-vis"><img data-fb="block" src="' + esc(k.mimg) + '" alt="' + esc(k.si) + '" loading="lazy"></div></div>';
        }

        // stroke order — falls back to "N ඉරි" text if the image can't load
        var strokeTxt = (k.sp && k.sp.length) ? (k.sp.length + ' ඉරි') : (k.strokes > 0 ? (k.strokes + ' ඉරි') : '');
        if (k.sp && k.sp.length) {
            // stroke order drawn from table practice_strokes (SVG path data)
            var sv = '<svg class="d-strokes" viewBox="' + esc(STROKE_BOX) + '" role="img" aria-label="ලිවීමේ ක්‍රමය">';
            sv += '<g class="guide">';
            k.sp.forEach(function (d) { sv += '<path d="' + esc(d) + '"/>'; });
            sv += '</g><g class="anim">';
            k.sp.forEach(function (d, i) {
                sv += '<path class="sp" pathLength="1" style="animation-delay:' + (i * 0.9).toFixed(1) + 's" d="' + esc(d) + '"/>';
            });
            sv += '</g><g class="nums">';
            k.sp.forEach(function (d, i) {
                var m = d.match(/^[Mm]\s*(-?[\d.]+)[,\s]+(-?[\d.]+)/);
                if (m) sv += '<text x="' + (parseFloat(m[1]) - 4).toFixed(1) + '" y="' + (parseFloat(m[2]) - 1).toFixed(1) + '">' + (i + 1) + '</text>';
            });
            sv += '</g></svg>';
            h += '<div class="d-block"><div class="d-sec">ලිවීමේ ක්‍රමය</div><div class="d-stroke">' + sv
              +  '<div>' + esc(strokeTxt) + '</div>'
              +  '<button type="button" class="btn-brand-outline btn-sm-nm d-replay" data-replay="1"><i class="fa-solid fa-rotate-right" aria-hidden="true"></i> නැවත පෙන්වන්න</button>'
              +  '</div></div>';
        } else if (k.stype === 'image' && k.simg) {
            h += '<div class="d-block"><div class="d-sec">ලිවීමේ ක්‍රමය</div><div class="d-stroke">'
              +  '<img data-fb="text" data-fb-text="' + esc(strokeTxt) + '" src="' + esc(k.simg) + '" alt="stroke order" loading="lazy">'
              +  '</div></div>';
        } else if (strokeTxt) {
            h += '<div class="d-block"><div class="d-sec">ලිවීමේ ක්‍රමය</div><div class="d-stroke">' + esc(strokeTxt) + '</div></div>';
        }

        [['vimg', 'vcap', 'රූපමය'], ['mnimg', 'mncap', 'මතක ක්‍රමය'], ['pimg', 'pcap', 'කොටස්']].forEach(function (f) {
            if (k[f[0]]) {
                h += '<div class="d-block"><div class="d-sec">' + f[2] + '</div><figure class="d-fig"><img data-fb="block" src="' + esc(k[f[0]]) + '" alt="' + esc(k[f[1]] || f[2]) + '" loading="lazy">'
                  +  (k[f[1]] ? '<figcaption>' + esc(k[f[1]]) + '</figcaption>' : '') + '</figure></div>';
            }
        });

        if (k.ex && k.ex.length) {
            h += '<div class="d-sec">උදාහරණ වචන</div>';
            k.ex.forEach(function (e) {
                h += '<div class="d-ex"><div class="w">' + esc(e.w) + '</div><div><div class="r">' + esc(e.r) + '</div><div class="m">' + esc(e.m) + '</div></div>'
                  +  (e.img ? '<img data-fb="hide" src="' + esc(e.img) + '" alt="' + esc(e.alt || e.m) + '" loading="lazy">' : '') + '</div>';
            });
        }
        dBody.innerHTML = h;
        watchImages(dBody);
        dBody.parentNode.scrollTop = 0;

        var i = ORDER.indexOf(id);
        dPrev.disabled = (i <= 0);
        // last open kanji: if some are locked, "next" leads to the upgrade prompt
        dNext.disabled = (i >= ORDER.length - 1) && !HAS_LOCKED;
    }

    dPrev.addEventListener('click', function () {
        var i = ORDER.indexOf(current); if (i > 0) render(ORDER[i - 1]);
    });
    dNext.addEventListener('click', function () {
        var i = ORDER.indexOf(current);
        if (i < ORDER.length - 1) { render(ORDER[i + 1]); }
        else if (HAS_LOCKED) { closeModal(detail); setTimeout(function () { openModal(login); }, 220); }
    });

    document.addEventListener('click', function (e) {
        var o = e.target.closest('[data-open]');
        if (o) { render(o.getAttribute('data-open')); openModal(detail); return; }
        if (e.target.closest('[data-replay]')) {
            var g = dBody.querySelector('.d-strokes .anim');
            if (g) { var c = g.cloneNode(true); g.parentNode.replaceChild(c, g); }   // clone = animation restarts
            return;
        }
        if (e.target.closest('[data-open-login]')) { openModal(login); return; }
        if (e.target.closest('[data-open-tips]'))  { openModal(tips); }
    });

    var q = document.getElementById('pgQ');
    var pills = document.querySelectorAll('.pg-pill');
    var cols  = document.querySelectorAll('#pgGrid .kanji-col');
    var shown = document.getElementById('pgShown');
    var empty = document.getElementById('pgEmpty');
    var note  = document.getElementById('pgSearchNote');
    var lesson = 'all';

    function apply() {
        var term = (q ? q.value : '').trim().toLowerCase();
        var n = 0;
        cols.forEach(function (c) {
            var okLesson = (lesson === 'all' || c.getAttribute('data-lesson') === lesson);
            var locked = c.getAttribute('data-locked') === '1';
            var okSearch = !term || (!locked && c.getAttribute('data-search').indexOf(term) !== -1);
            var vis = okLesson && okSearch;
            c.style.display = vis ? '' : 'none';
            if (vis) n++;
        });
        if (shown) shown.textContent = n;
        if (empty) empty.style.display = n ? 'none' : 'block';
        if (note)  note.style.display  = term ? 'block' : 'none';
    }
    if (q) q.addEventListener('input', apply);
    pills.forEach(function (p) {
        p.addEventListener('click', function () {
            lesson = p.getAttribute('data-lesson');
            pills.forEach(function (x) { x.setAttribute('aria-pressed', x === p ? 'true' : 'false'); });
            apply();
        });
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>