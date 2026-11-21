<?php
/**
 * learnjapanese.jp - Vocabulary by JLPT level   |   v2 (free preview + copy protection)
 *
 *   words_according_to_level.php            -> level picker (N5 ... N1, with word counts)
 *   words_according_to_level.php?level=5    -> N5 list (ONLY the first 200 words are visible = 2 pages x 100)
 *   words_according_to_level.php?level=5&q= -> search inside those free 200 words only
 *
 * HOW THE LIMIT WORKS (server side - this is the real protection):
 *   - Every level shows only its first LW_FREE (200) words (ordered by seq).
 *   - Page 3+ never loads any word from the database - only the "upgrade" box is printed (noindex).
 *   - Search can only look inside the same free 200 words, so nobody can find / list the rest by searching.
 *   - Per-IP rate limit on page loads (stops scripts / scrapers from hammering the page).
 *   - Real words are never put into JavaScript / JSON, only into the visible HTML of the free pages.
 *
 * COPY PROTECTION (hard-coded, client side - can slow people down but can never be 100% - screenshots exist):
 *   copy / cut / right-click / drag / select / print / save / view-source / dev-tools shortcuts are blocked,
 *   text is not selectable (the search box is the only exception).
 *
 * >>> MANUAL LINKS: edit the settings block just below (book pages + upgrade page). <<<
 */

require_once __DIR__ . '/config.php';
$pdo = getDBConnection();

/* ================================================================== SETTINGS ================================================================== */
$LW_UPGRADE_URL  = 'upgrade.php';      // upgrade / package page (the lock box button links here)
$LW_BOOK_DEFAULT = 'books.php';        // used when a level has no own book link below
$LW_BOOKS = [
    5 => '',   // N5 complete vocabulary book   e.g. 'book_detail.php?id=1'
    4 => '',   // N4 complete vocabulary book
    3 => '',   // N3 complete vocabulary book
    2 => '',   // N2 complete vocabulary book
    1 => '',   // N1 complete vocabulary book
];

const LW_PER_PAGE = 100;               // words per page
const LW_FREE     = 200;               // free words per level (keep it a multiple of LW_PER_PAGE)
const LW_RATE_MAX = 120;               // max page loads ...
const LW_RATE_SEC = 600;               // ... per IP in this many seconds

/**
 * Does this visitor own the full vocabulary lists?  Returns false for everybody for now.
 * When your package system is ready, put the real check here (e.g. a DB lookup on $_SESSION['user_id']).
 */
function lw_full_access(): bool
{
    return false;
}
/* ================================================================================================================================================ */

$LW_FULL = lw_full_access();

/* ---------- helpers ---------- */
function lw_h(string $s): string    { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function lw_like(string $s): string { return '%' . addcslashes($s, '\\%_') . '%'; }

function lw_to_hira(string $s): string
{
    return preg_replace_callback('/[\x{30A1}-\x{30F6}]/u', function ($m) {
        $b  = $m[0];
        $cp = ((ord($b[0]) & 0x0F) << 12) | ((ord($b[1]) & 0x3F) << 6) | (ord($b[2]) & 0x3F);
        return html_entity_decode('&#' . ($cp - 0x60) . ';', ENT_QUOTES, 'UTF-8');
    }, $s);
}

function lw_strip_punct(string $s): string
{
    return preg_replace('/[\s～〜~…\.．・、。，,？?！!\/／（）()［］\[\]「」『』―\-－:：]+/u', '', $s);
}

function lw_ro_skel(string $s): string
{
    $s = strtolower($s);
    $s = strtr($s, ['ā' => 'a', 'ī' => 'i', 'ū' => 'u', 'ē' => 'e', 'ō' => 'o', 'â' => 'a', 'î' => 'i', 'û' => 'u', 'ê' => 'e', 'ô' => 'o']);
    $s = preg_replace('/[^a-z0-9]/', '', $s);
    foreach ([
        ['cch', 'tt'], ['tch', 'tt'],
        ['sha', 'sya'], ['shu', 'syu'], ['sho', 'syo'], ['she', 'sye'], ['shi', 'si'],
        ['cha', 'tya'], ['chu', 'tyu'], ['cho', 'tyo'], ['che', 'tye'], ['chi', 'ti'],
        ['tsu', 'tu'], ['fu', 'hu'],
        ['ja', 'zya'], ['ju', 'zyu'], ['jo', 'zyo'], ['ji', 'zi'], ['dzu', 'zu'],
    ] as [$a, $b]) {
        $s = str_replace($a, $b, $s);
    }
    $s = preg_replace('/m(?=[bp])/', 'n', $s);
    foreach ([['ou', 'o'], ['oo', 'o'], ['uu', 'u'], ['aa', 'a'], ['ii', 'i'], ['ee', 'e'], ['ei', 'e']] as [$a, $b]) {
        $s = str_replace($a, $b, $s);
    }
    return $s;
}

/** very small per-IP rate limiter (file in the temp folder). true = allowed */
function lw_rate_ok(string $bucket, int $max, int $window): bool
{
    $ip  = (string) ($_SERVER['REMOTE_ADDR'] ?? '0');
    $f   = sys_get_temp_dir() . '/lw_rl_' . preg_replace('/\W/', '', $bucket) . '_' . md5($ip) . '.json';
    $now = time();
    $fh  = @fopen($f, 'c+');
    if (!$fh) {
        return true;                                   // cannot write -> never block real users
    }
    flock($fh, LOCK_EX);
    $a = json_decode((string) stream_get_contents($fh), true);
    if (!is_array($a)) { $a = []; }
    $a  = array_values(array_filter($a, function ($t) use ($now, $window) { return (int) $t > $now - $window; }));
    $ok = count($a) < $max;
    if ($ok) { $a[] = $now; }
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($a));
    flock($fh, LOCK_UN);
    fclose($fh);
    return $ok;
}

/* ---------- rate limit + headers (BEFORE any HTML) ---------- */
if (!lw_rate_ok('lvl', LW_RATE_MAX, LW_RATE_SEC)) {
    http_response_code(429);
    header('Retry-After: 120');
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Too many requests. Please slow down.';
    exit;
}
header('X-Robots-Tag: noarchive');

/* ---------- input ---------- */
$lwLevel = isset($_GET['level']) ? (int) $_GET['level'] : 0;
if ($lwLevel < 1 || $lwLevel > 5) $lwLevel = 0;                       // 0 = show the level picker
$lwQ    = trim(preg_replace('/\s+/u', ' ', (string) ($_GET['q'] ?? '')));
$lwQ    = preg_replace('/^(.{0,60}).*$/us', '$1', $lwQ);
$lwPage = max(1, (int) ($_GET['page'] ?? 1));

/* ---------- data ---------- */
$lwCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
try {
    foreach ($pdo->query('SELECT level, COUNT(*) c FROM dict_words GROUP BY level')->fetchAll() as $r) {
        $lwCounts[(int) $r['level']] = (int) $r['c'];
    }
} catch (Throwable $e) {
    error_log('[words_according_to_level] ' . $e->getMessage());
}

$lwRows   = [];
$lwTotal  = 0;                 // words that match (inside the free window)
$lwPages  = 1;                 // pages that really exist for this visitor
$lwLocked = false;             // does this level have more words than the visitor may see?
$lwState  = 'picker';          // picker | list | locked
$lwFreeSeq = null;             // highest seq that is free

if ($lwLevel) {
    $lwState = 'list';
    $lwLocked = !$LW_FULL && $lwCounts[$lwLevel] > LW_FREE;

    try {
        // find the seq of the 200th word: everything above it is NOT free
        if (!$LW_FULL) {
            $st = $pdo->prepare('SELECT seq FROM dict_words WHERE level = :l ORDER BY seq ASC LIMIT 1 OFFSET ' . (int) (LW_FREE - 1));
            $st->execute(['l' => $lwLevel]);
            $v = $st->fetchColumn();
            $lwFreeSeq = ($v === false) ? null : (int) $v;       // null = level has <= 200 words -> everything is free
        }

        $where  = ['level = :lvl'];
        $params = ['lvl' => $lwLevel];
        if ($lwFreeSeq !== null) {                               // search / list can never reach words after the free window
            $where[] = 'seq <= :fseq';
            $params['fseq'] = $lwFreeSeq;
        }

        if ($lwQ !== '') {
            $or = [];
            if (preg_match('/[\x{3040}-\x{30FF}\x{4E00}-\x{9FFF}]/u', $lwQ)) {          // Japanese
                $or[] = 'reading_norm LIKE :j1';  $params['j1'] = lw_like(lw_strip_punct(lw_to_hira($lwQ)));
                $or[] = 'kanji_norm LIKE :j2';    $params['j2'] = lw_like(lw_strip_punct($lwQ));
            }
            if (preg_match('/[\x{0D80}-\x{0DFF}]/u', $lwQ)) {                            // Sinhala
                $or[] = 'meaning_si LIKE :s1';    $params['s1'] = lw_like($lwQ);
            }
            if (preg_match('/[A-Za-z]/', $lwQ)) {                                         // romaji / Singlish / English
                $rk = lw_ro_skel($lwQ);
                if ($rk !== '') { $or[] = 'romaji_key LIKE :r1'; $params['r1'] = lw_like($rk); }
                $or[] = 'meaning_si LIKE :e1';    $params['e1'] = lw_like($lwQ);
            }
            $where[] = $or ? '(' . implode(' OR ', $or) . ')' : '0';
        }
        $wsql = implode(' AND ', $where);

        $st = $pdo->prepare('SELECT COUNT(*) FROM dict_words WHERE ' . $wsql);
        $st->execute($params);
        $lwTotal = (int) $st->fetchColumn();
        $lwPages = max(1, (int) ceil($lwTotal / LW_PER_PAGE));

        if ($lwPage > $lwPages) {
            if ($lwLocked && $lwQ === '' && $lwPage <= (int) ceil($lwCounts[$lwLevel] / LW_PER_PAGE)) {
                $lwState = 'locked';                              // page 3+ : NO words are loaded, only the upgrade box
            } else {
                $lwPage = $lwPages;                               // search results / out of range -> last real page
            }
        }

        if ($lwState === 'list') {
            $st = $pdo->prepare('SELECT level, seq, reading, kanji, meaning_si, romaji FROM dict_words WHERE ' . $wsql
                              . ' ORDER BY seq ASC LIMIT ' . (int) LW_PER_PAGE . ' OFFSET ' . (int) (($lwPage - 1) * LW_PER_PAGE));
            $st->execute($params);
            $lwRows = $st->fetchAll();
        }
    } catch (Throwable $e) {
        error_log('[words_according_to_level] ' . $e->getMessage());
        $lwRows = []; $lwTotal = 0;
    }
}

if ($lwState === 'locked') {
    header('X-Robots-Tag: noindex, noarchive');                   // the lock page must never be indexed
}

function lw_url(array $over = []): string
{
    $p = array_merge($_GET, $over);
    foreach ($p as $k => $v) { if ($v === '' || $v === null || ($k === 'page' && (int) $v <= 1)) unset($p[$k]); }
    return 'words_according_to_level.php' . ($p ? '?' . http_build_query($p) : '');
}

function lw_book_url(int $lvl): string
{
    global $LW_BOOKS, $LW_BOOK_DEFAULT;
    $u = trim((string) ($LW_BOOKS[$lvl] ?? ''));
    return $u !== '' ? $u : $LW_BOOK_DEFAULT;
}

/** the "free preview ended -> upgrade / buy the book" box */
function lw_lock_box(array $L, int $lvl): string
{
    global $LW_UPGRADE_URL;
    return '<div class="lw-lock"><div class="l-ico" aria-hidden="true">🔒</div>'
         . '<div class="l-t">' . lw_h(sprintf($L['lock_t'], LW_FREE)) . '</div>'
         . '<p class="l-s">' . lw_h($L['lock_s']) . '</p>'
         . '<div class="l-act"><a class="l-u" href="' . lw_h($LW_UPGRADE_URL) . '">👑 ' . lw_h($L['lock_up']) . '</a>'
         . '<a class="l-b" href="' . lw_h(lw_book_url($lvl)) . '">📘 ' . lw_h($L['lock_book']) . '</a></div></div>';
}

/* ---------- texts (si / en / ja) ---------- */
$T = [
  'si' => ['title' => 'මට්ටම අනුව වචන', 'sub' => 'JLPT N5 – N1 · ඔබට අවශ්‍ය මට්ටම තෝරන්න',
           'words' => 'වචන', 'open' => 'ලැයිස්තුව බලන්න', 'back' => 'සියලු මට්ටම්', 'dict' => 'වචන කෝෂය',
           'filter' => 'මේ මට්ටමේ සොයන්න (かな · 漢字 · romaji · සිංහල)', 'search' => 'සොයන්න', 'clear' => 'මකන්න',
           'showing' => 'පෙන්වන්නේ', 'of' => 'න්', 'none' => 'ප්‍රතිඵල නැහැ.', 'page' => 'පිටුව',
           'prev' => '← කලින්', 'next' => 'ඊළඟ →', 'listen' => 'අහන්න', 'free' => 'නොමිලේ',
           'book_t' => 'සම්පූර්ණ වචන පොත', 'book_p' => 'මේ මට්ටමේ සියලු වචන, ලස්සනට print කළ පොතක් ලෙස.', 'book_b' => 'පොත බලන්න',
           'lock_t' => 'නොමිලේ බලන්න පුළුවන් වචන %d මෙතැනින් අවසානයි',
           'lock_s' => 'මේ මට්ටමේ ඉතිරි සියලු වචන බලන්න ඔබේ Package එක Upgrade කරගන්න, නැත්නම් සම්පූර්ණ වචන පොත මිලදී ගන්න.',
           'lock_up' => 'Upgrade කරන්න', 'lock_book' => 'සම්පූර්ණ පොත මිලදී ගන්න',
           'lv' => ['N5 · ආරම්භක', 'N4 · මූලික', 'N3 · මධ්‍යම', 'N2 · ඉහළ මධ්‍යම', 'N1 · උසස්']],
  'en' => ['title' => 'Vocabulary by Level', 'sub' => 'JLPT N5 – N1 · pick your level',
           'words' => 'words', 'open' => 'View list', 'back' => 'All levels', 'dict' => 'Dictionary',
           'filter' => 'Search in this level (kana · kanji · romaji · Sinhala)', 'search' => 'Search', 'clear' => 'Clear',
           'showing' => 'Showing', 'of' => 'of', 'none' => 'No results.', 'page' => 'Page',
           'prev' => '← Prev', 'next' => 'Next →', 'listen' => 'Listen', 'free' => 'free',
           'book_t' => 'Complete vocabulary book', 'book_p' => 'Every word of this level in a nicely printed book.', 'book_b' => 'View book',
           'lock_t' => 'The free preview of %d words ends here',
           'lock_s' => 'To see all the remaining words of this level, upgrade your package or buy the complete vocabulary book.',
           'lock_up' => 'Upgrade', 'lock_book' => 'Buy the complete book',
           'lv' => ['N5 · Beginner', 'N4 · Elementary', 'N3 · Intermediate', 'N2 · Upper-intermediate', 'N1 · Advanced']],
  'ja' => ['title' => 'レベル別単語', 'sub' => 'JLPT N5 – N1 · レベルを選んでください',
           'words' => '語', 'open' => 'リストを見る', 'back' => 'すべてのレベル', 'dict' => '辞典',
           'filter' => 'このレベル内を検索（かな・漢字・ローマ字・シンハラ語）', 'search' => '検索', 'clear' => 'クリア',
           'showing' => '表示', 'of' => '/', 'none' => '見つかりませんでした。', 'page' => 'ページ',
           'prev' => '← 前へ', 'next' => '次へ →', 'listen' => '聞く', 'free' => '無料',
           'book_t' => '単語帳（完全版）', 'book_p' => 'このレベルの全単語を収録した本です。', 'book_b' => '本を見る',
           'lock_t' => '無料で見られる %d 語はここまでです',
           'lock_s' => 'このレベルの残りの単語をすべて見るには、パッケージをアップグレードするか、単語帳（完全版）をご購入ください。',
           'lock_up' => 'アップグレード', 'lock_book' => '完全版を購入する',
           'lv' => ['N5 · 初級', 'N4 · 初級', 'N3 · 中級', 'N2 · 中上級', 'N1 · 上級']],
];
$lang = (isset($currentLang) && isset($T[$currentLang])) ? $currentLang : 'si';
$lwL = $T[$lang];

$pageTitle = $lwLevel ? 'JLPT N' . $lwLevel . ' ' . $lwL['words'] : $lwL['title'];
/* NOTE: header.php may reuse generic variable names ($rows, $total, $page ...), so every variable used below is lw-prefixed. */
require_once __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --nm-bg: #e6e9f2; --nm-light: #ffffff; --nm-dark: #c3c9dc;
        --nm-text: #2d3250; --nm-muted: #7a8099;
        --nm-brand: #4f46e5; --nm-brand-2: #7c6cf5;
        --nm-radius: 24px; --nm-radius-sm: 16px;
        --nm-raised: 8px 8px 18px var(--nm-dark), -8px -8px 18px var(--nm-light);
        --nm-raised-sm: 5px 5px 11px var(--nm-dark), -5px -5px 11px var(--nm-light);
        --nm-inset: inset 5px 5px 11px var(--nm-dark), inset -5px -5px 11px var(--nm-light);
        --nm-inset-sm: inset 3px 3px 7px var(--nm-dark), inset -3px -3px 7px var(--nm-light);
    }
    body { background: var(--nm-bg) !important; color: var(--nm-text); -webkit-tap-highlight-color: transparent; }
    @keyframes lwUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }

    /* ---- copy protection: nothing can be selected / long-pressed / dragged (only the search box) ---- */
    body, .lw-wrap {
        -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none;
        -webkit-touch-callout: none;
    }
    input, textarea { -webkit-user-select: text !important; -moz-user-select: text !important; user-select: text !important; }
    img, a { -webkit-user-drag: none; user-drag: none; }
    ::selection { background: transparent; }
    input::selection { background: rgba(79,70,229,.25); }
    @media print { html, body { display: none !important; } }

    .lw-hero { background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); color: #fff; border-radius: var(--nm-radius);
               padding: 24px 20px; box-shadow: var(--nm-raised); position: relative; overflow: hidden; animation: lwUp .5s ease both; }
    .lw-hero::before { content: ""; position: absolute; width: 130px; height: 130px; border-radius: 50%; background: rgba(255,255,255,.12); top: -45px; right: -25px; }
    .lw-hero > * { position: relative; z-index: 1; }
    .lw-hero h1 { font-weight: 800; font-size: 1.45rem; margin: 0 0 4px; }
    .lw-hero p  { margin: 0; opacity: .9; font-size: .92rem; }
    .lw-hero .lw-badge { display: inline-block; font-weight: 800; background: rgba(255,255,255,.22); border-radius: 999px; padding: 3px 14px; margin-right: 8px; }

    .lw-nav { display: flex; flex-wrap: wrap; gap: 10px; margin: 16px 0 6px; }
    .lw-chip { border: 0; background: var(--nm-bg); color: var(--nm-text); font-weight: 700; font-size: .85rem; padding: 9px 18px; border-radius: 999px;
               box-shadow: var(--nm-raised-sm); text-decoration: none; white-space: nowrap; display: inline-block; min-height: 40px; }
    .lw-chip:active, .lw-chip.on { color: var(--nm-brand); box-shadow: var(--nm-inset-sm); }

    /* ---- level picker ---- */
    .lw-grid { display: grid; grid-template-columns: 1fr; gap: 18px; margin: 18px 0 26px; }
    @media (min-width: 640px) { .lw-grid { grid-template-columns: 1fr 1fr; } }
    .lw-lv { display: block; text-decoration: none; color: var(--nm-text); background: var(--nm-bg); border-radius: var(--nm-radius);
             box-shadow: var(--nm-raised); padding: 20px; animation: lwUp .4s ease both; transition: transform .2s ease, box-shadow .2s ease; }
    .lw-lv:hover { transform: translateY(-3px); color: var(--nm-text); }
    .lw-lv:active { box-shadow: var(--nm-inset); transform: scale(.985); }
    .lw-lv .lw-n { display: inline-block; color: #fff; font-weight: 800; font-size: 1.35rem; padding: 6px 18px; border-radius: 999px; box-shadow: 2px 2px 6px rgba(0,0,0,.18); }
    .lw-lv .lw-d { font-weight: 700; margin-top: 12px; }
    .lw-lv .lw-c { color: var(--nm-muted); font-size: .9rem; margin-top: 2px; }
    .lw-lv .lw-go { margin-top: 14px; font-weight: 800; color: var(--nm-brand); font-size: .9rem; }

    .l5 { background: linear-gradient(135deg,#10b981,#34d399); } .l4 { background: linear-gradient(135deg,#3b82f6,#60a5fa); }
    .l3 { background: linear-gradient(135deg,#eab308,#facc15); color: #3b3200 !important; } .l2 { background: linear-gradient(135deg,#f97316,#fb923c); }
    .l1 { background: linear-gradient(135deg,#ef4444,#f97316); }

    /* ---- list page ---- */
    .lw-bar { display: flex; gap: 10px; margin: 14px 0 8px; }
    .lw-bar input { flex: 1; min-width: 0; border: 0; outline: 0; background: var(--nm-bg); color: var(--nm-text); border-radius: 999px;
                    padding: 14px 20px; font-size: 1rem; box-shadow: var(--nm-inset); -webkit-appearance: none; appearance: none; }
    .lw-bar input:focus { box-shadow: var(--nm-inset), 0 0 0 2px rgba(79,70,229,.35); }
    .lw-bar button { border: 0; background: var(--nm-bg); color: var(--nm-brand); font-weight: 800; padding: 0 22px; border-radius: 999px; box-shadow: var(--nm-raised-sm); }
    .lw-bar button:active { box-shadow: var(--nm-inset-sm); }
    .lw-meta { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px; font-size: .8rem; color: var(--nm-muted); padding: 4px 6px 12px; }

    .lw-book { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; background: var(--nm-bg);
               border-radius: var(--nm-radius); box-shadow: var(--nm-raised); padding: 16px 20px; margin: 6px 0 18px; }
    .lw-book b { display: block; font-size: 1.05rem; }
    .lw-book span { color: var(--nm-muted); font-size: .88rem; }
    .lw-book a { text-decoration: none; background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); color: #fff; font-weight: 800;
                 padding: 11px 22px; border-radius: 999px; box-shadow: var(--nm-raised-sm); white-space: nowrap; }

    .lw-row { display: flex; align-items: flex-start; gap: 12px; background: var(--nm-bg); border-radius: var(--nm-radius-sm);
              box-shadow: var(--nm-raised-sm); padding: 13px 14px; margin-bottom: 14px; }
    .lw-no { flex: 0 0 auto; min-width: 42px; text-align: center; font-size: .72rem; font-weight: 800; color: var(--nm-muted);
             border-radius: 999px; box-shadow: var(--nm-inset-sm); padding: 5px 6px; margin-top: 4px; }
    .lw-main { flex: 1; min-width: 0; }
    .lw-read { font-size: 1.35rem; font-weight: 800; line-height: 1.25; word-break: break-word; }
    .lw-kanji { font-size: 1.05rem; color: var(--nm-muted); margin-left: 8px; font-weight: 600; }
    .lw-rom { font-size: .82rem; color: var(--nm-muted); margin-top: 1px; }
    .lw-mean { font-size: 1rem; margin-top: 6px; line-height: 1.5; }
    .lw-spk { flex: 0 0 auto; border: 0; background: var(--nm-bg); color: var(--nm-brand); width: 44px; height: 44px; border-radius: 50%;
              box-shadow: var(--nm-raised-sm); font-size: 1.05rem; }
    .lw-spk:active { box-shadow: var(--nm-inset-sm); }

    .lw-empty { text-align: center; padding: 34px 18px; margin: 10px 0; border-radius: var(--nm-radius-sm); box-shadow: var(--nm-inset); color: var(--nm-muted); }
    .lw-pager { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; align-items: center; margin: 22px 0 34px; }
    .lw-pager .lw-chip.dis { opacity: .4; pointer-events: none; }

    /* ---- lock box (free preview ended) ---- */
    .lw-lock { text-align: center; background: var(--nm-bg); border-radius: var(--nm-radius); box-shadow: var(--nm-raised);
               padding: 26px 18px; margin: 22px auto; max-width: 640px; animation: lwUp .4s ease both; }
    .lw-lock .l-ico { font-size: 2.2rem; line-height: 1; }
    .lw-lock .l-t { font-weight: 800; font-size: 1.1rem; margin: 10px 0 6px; }
    .lw-lock .l-s { color: var(--nm-muted); font-size: .92rem; line-height: 1.6; margin: 0 0 16px; }
    .lw-lock .l-act { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; }
    .lw-lock .l-act a { flex: 1 1 200px; min-height: 50px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
                        text-decoration: none !important; font-weight: 800; border-radius: 999px; padding: 12px 18px; }
    .lw-lock .l-u { color: #fff; background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); box-shadow: var(--nm-raised-sm); }
    .lw-lock .l-b { color: var(--nm-brand); background: var(--nm-bg); box-shadow: var(--nm-raised-sm); }
    .lw-lock .l-act a:active { box-shadow: var(--nm-inset-sm); }
</style>

<div class="lw-wrap">

<?php if ($lwState === 'picker'): /* ================================================ LEVEL PICKER ================================================ */ ?>

    <div class="lw-hero mb-3">
        <h1>📚 <?php echo lw_h($lwL['title']); ?></h1>
        <p><?php echo lw_h($lwL['sub']); ?></p>
    </div>

    <div class="lw-nav"><a class="lw-chip" href="dictionary.php">📖 <?php echo lw_h($lwL['dict']); ?></a></div>

    <div class="lw-grid">
        <?php foreach ([5, 4, 3, 2, 1] as $i => $n): ?>
            <a class="lw-lv" href="<?php echo lw_h(lw_url(['level' => $n, 'q' => null, 'page' => null])); ?>" style="animation-delay:<?php echo $i * 0.06; ?>s">
                <span class="lw-n l<?php echo $n; ?>">N<?php echo $n; ?></span>
                <div class="lw-d"><?php echo lw_h($lwL['lv'][5 - $n]); ?></div>
                <div class="lw-c"><?php echo number_format($lwCounts[$n]); ?> <?php echo lw_h($lwL['words']); ?><?php echo (!$LW_FULL && $lwCounts[$n] > LW_FREE) ? ' · ' . LW_FREE . ' ' . lw_h($lwL['free']) : ''; ?></div>
                <div class="lw-go"><?php echo lw_h($lwL['open']); ?> →</div>
            </a>
        <?php endforeach; ?>
    </div>

<?php else: /* ================================================ ONE LEVEL (list or locked) ================================================ */ ?>

    <div class="lw-hero mb-3">
        <h1><span class="lw-badge">N<?php echo $lwLevel; ?></span><?php echo lw_h($lwL['title']); ?></h1>
        <p><?php echo lw_h($lwL['lv'][5 - $lwLevel]); ?> · <?php echo number_format($lwCounts[$lwLevel]); ?> <?php echo lw_h($lwL['words']); ?></p>
    </div>

    <div class="lw-nav">
        <a class="lw-chip" href="words_according_to_level.php">← <?php echo lw_h($lwL['back']); ?></a>
        <?php foreach ([5, 4, 3, 2, 1] as $n): ?>
            <a class="lw-chip <?php echo $n === $lwLevel ? 'on' : ''; ?>" href="<?php echo lw_h(lw_url(['level' => $n, 'q' => null, 'page' => null])); ?>">N<?php echo $n; ?></a>
        <?php endforeach; ?>
        <a class="lw-chip" href="dictionary.php">📖 <?php echo lw_h($lwL['dict']); ?></a>
    </div>

    <div class="lw-book">
        <div><b>📕 N<?php echo $lwLevel; ?> <?php echo lw_h($lwL['book_t']); ?></b><span><?php echo lw_h($lwL['book_p']); ?></span></div>
        <a href="<?php echo lw_h(lw_book_url($lwLevel)); ?>"><?php echo lw_h($lwL['book_b']); ?> →</a>
    </div>

<?php if ($lwState === 'locked'): /* page 3+ : no words at all, only the upgrade box */ ?>

    <?php echo lw_lock_box($lwL, $lwLevel); ?>
    <div class="lw-pager"><a class="lw-chip" href="<?php echo lw_h(lw_url(['page' => null, 'q' => null])); ?>">← N<?php echo $lwLevel; ?> · <?php echo lw_h($lwL['words']); ?></a></div>

<?php else: /* ---- free list ---- */ ?>

    <form class="lw-bar" method="get" action="words_according_to_level.php">
        <input type="hidden" name="level" value="<?php echo $lwLevel; ?>">
        <input type="search" name="q" value="<?php echo lw_h($lwQ); ?>" placeholder="<?php echo lw_h($lwL['filter']); ?>" autocomplete="off" autocapitalize="off" spellcheck="false">
        <button type="submit"><?php echo lw_h($lwL['search']); ?></button>
        <?php if ($lwQ !== ''): ?><a class="lw-chip" style="display:flex;align-items:center" href="<?php echo lw_h(lw_url(['q' => null, 'page' => null])); ?>"><?php echo lw_h($lwL['clear']); ?></a><?php endif; ?>
    </form>

    <div class="lw-meta">
        <span><?php echo number_format($lwTotal); ?> <?php echo lw_h($lwL['words']); ?><?php echo $lwTotal ? ' · ' . lw_h($lwL['showing']) . ' ' . number_format(($lwPage - 1) * LW_PER_PAGE + 1) . '–' . number_format(($lwPage - 1) * LW_PER_PAGE + count($lwRows)) : ''; ?></span>
        <span><?php echo lw_h($lwL['page']); ?> <?php echo $lwPage; ?> <?php echo lw_h($lwL['of']); ?> <?php echo $lwPages; ?></span>
    </div>

    <?php if (!$lwRows): ?>
        <div class="lw-empty"><?php echo lw_h($lwL['none']); ?></div>
    <?php endif; ?>

    <div id="lwList">
        <?php foreach ($lwRows as $w): ?>
            <div class="lw-row">
                <div class="lw-no">#<?php echo (int) $w['seq']; ?></div>
                <div class="lw-main">
                    <div>
                        <span class="lw-read"><?php echo lw_h((string) $w['reading']); ?></span>
                        <?php if (!empty($w['kanji'])): ?><span class="lw-kanji"><?php echo lw_h((string) $w['kanji']); ?></span><?php endif; ?>
                    </div>
                    <div class="lw-rom"><?php echo lw_h((string) $w['romaji']); ?></div>
                    <div class="lw-mean"><?php echo lw_h((string) $w['meaning_si']); ?></div>
                </div>
                <button type="button" class="lw-spk" data-say="<?php echo lw_h((string) $w['reading']); ?>" aria-label="<?php echo lw_h($lwL['listen']); ?>">🔊</button>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($lwPages > 1):
        $from = max(1, $lwPage - 2); $to = min($lwPages, $lwPage + 2); ?>
        <div class="lw-pager">
            <a class="lw-chip <?php echo $lwPage <= 1 ? 'dis' : ''; ?>" href="<?php echo lw_h(lw_url(['page' => $lwPage - 1])); ?>"><?php echo lw_h($lwL['prev']); ?></a>
            <?php if ($from > 1): ?><a class="lw-chip" href="<?php echo lw_h(lw_url(['page' => 1])); ?>">1</a><?php if ($from > 2): ?><span>…</span><?php endif; endif; ?>
            <?php for ($i = $from; $i <= $to; $i++): ?>
                <a class="lw-chip <?php echo $i === $lwPage ? 'on' : ''; ?>" href="<?php echo lw_h(lw_url(['page' => $i])); ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($to < $lwPages): ?><?php if ($to < $lwPages - 1): ?><span>…</span><?php endif; ?><a class="lw-chip" href="<?php echo lw_h(lw_url(['page' => $lwPages])); ?>"><?php echo $lwPages; ?></a><?php endif; ?>
            <a class="lw-chip <?php echo $lwPage >= $lwPages ? 'dis' : ''; ?>" href="<?php echo lw_h(lw_url(['page' => $lwPage + 1])); ?>"><?php echo lw_h($lwL['next']); ?></a>
        </div>
    <?php endif; ?>

    <?php
    // free preview ended: show the upgrade box on the last free page (and under any search result of a locked level)
    if ($lwLocked && $lwPage >= $lwPages) { echo lw_lock_box($lwL, $lwLevel); }
    ?>

    <div class="lw-book" style="margin-bottom:34px">
        <div><b>📕 N<?php echo $lwLevel; ?> <?php echo lw_h($lwL['book_t']); ?></b><span><?php echo lw_h($lwL['book_p']); ?></span></div>
        <a href="<?php echo lw_h(lw_book_url($lwLevel)); ?>"><?php echo lw_h($lwL['book_b']); ?> →</a>
    </div>

<?php endif; /* list / locked */ ?>
<?php endif; /* picker / level */ ?>

</div>

<script>
(function () {
  'use strict';

  /* ---------- COPY PROTECTION (hard-coded) ----------
     Blocks copy / cut / right-click / drag / select / print / save / view-source / dev-tools keys.
     The search box is the only place where selecting and copying still works. */
  var inField = function (t) { return !!(t && t.closest && t.closest('input,textarea')); };
  ['copy', 'cut'].forEach(function (n) {
    document.addEventListener(n, function (e) {
      if (inField(e.target)) return;
      e.preventDefault();
      try { e.clipboardData.setData('text/plain', location.hostname); } catch (x) {}
    }, true);
  });
  ['contextmenu', 'dragstart', 'selectstart'].forEach(function (n) {
    document.addEventListener(n, function (e) { if (!inField(e.target)) e.preventDefault(); }, true);
  });
  document.addEventListener('keydown', function (e) {
    var k = (e.key || '').toLowerCase(), mod = e.ctrlKey || e.metaKey;
    if (e.key === 'F12') { e.preventDefault(); return; }
    if (mod && e.shiftKey && ['i', 'j', 'c', 'k'].indexOf(k) > -1) { e.preventDefault(); return; }
    if (mod && ['s', 'u', 'p'].indexOf(k) > -1) { e.preventDefault(); return; }
    if (mod && ['c', 'x', 'a'].indexOf(k) > -1 && !inField(e.target)) { e.preventDefault(); }
  }, true);
  window.addEventListener('beforeprint', function () { document.documentElement.style.display = 'none'; });
  window.addEventListener('afterprint', function () { document.documentElement.style.display = ''; });
  document.addEventListener('visibilitychange', function () { try { window.getSelection().removeAllRanges(); } catch (x) {} });

  /* ---------- listen button (speech only - does not copy anything) ---------- */
  var list = document.getElementById('lwList');
  if (list) {
    list.addEventListener('click', function (ev) {
      var b = ev.target.closest('.lw-spk'); if (!b || !('speechSynthesis' in window)) return;
      var u = new SpeechSynthesisUtterance((b.getAttribute('data-say') || '').replace(/[～〜~…（）()／\/・]/g, ' ').trim());
      u.lang = 'ja-JP'; u.rate = 0.85; window.speechSynthesis.cancel(); window.speechSynthesis.speak(u);
    });
  }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>