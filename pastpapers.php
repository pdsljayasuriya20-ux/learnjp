<?php
/**
 * learnjapanese.jp - Past papers / JLPT-style practice papers   |   v3
 *
 *   pastpapers.php?level=N3            -> list of papers of that level
 *   pastpapers.php?paper=12            -> practice mode (instant answer + explanation)
 *   pastpapers.php?paper=12&mode=exam  -> exam mode (timer, no hints, score at the end)
 *   pastpapers.php?api=1  (POST json)  -> check one answer / submit the whole paper
 *
 * - Furigana (hiragana over kanji) ON/OFF and Sinhala meaning ON/OFF toggles (remembered in the browser).
 *   Exam mode starts with both OFF (like the real exam); they unlock again in the review.
 * - Correct answers are NEVER written into the page. They stay in the database and the server tells the
 *   browser the answer only after the user has chosen (practice) or submitted (exam).
 * - Papers with is_free = 0 need pp_full_access() (false for everybody for now) -> upgrade box.
 * - Copy protection is hard-coded (copy / cut / right-click / drag / select / print / save / dev-tools keys).
 * - v2: guests (not logged in) get a login / register popup 2 minutes after a paper opens.
 *       After login / register (user-login.php / user-register.php ?next=...) they return to the same paper.
 * - v3: line breaks (\n) in passages / dialogues are shown (nl2br in pp_ruby);
 *       questions can have an image (past_questions.img -> uploads/pastpapers/<file>).
 *
 * Tables: past_papers, past_questions (needs column `img` VARCHAR(255) NULL), past_attempts  (sql/011_past_papers.sql)
 * Needs : config.php (getDBConnection()), includes/header.php, includes/footer.php
 */

require_once __DIR__ . '/config.php';
$pdo = getDBConnection();
if (session_status() === PHP_SESSION_NONE) { @session_start(); }

/* ================================================================== SETTINGS ================================================================== */
$PP_UPGRADE_URL = 'upgrade.php';          // package page (the lock box button)
$PP_BOOKS_URL   = 'books.php';
$PP_DASH        = ['N5' => 'n5.php', 'N4' => 'n4.php', 'N3' => 'n3.php', 'N2' => 'n2.php', 'N1' => 'n1.php'];
$PP_LOGIN_URL   = 'user-login.php';       // login page    (reads ?next=)
$PP_REGISTER_URL = 'user-register.php';   // register page (reads ?next=)
$PP_IMG_DIR     = 'uploads/pastpapers/';  // question images are loaded from here (file name comes from past_questions.img)

const PP_API_MAX = 400;                   // API calls per IP ...
const PP_API_SEC = 600;                   // ... in this many seconds
const PP_PAGE_MAX = 120;                  // page loads per IP ...
const PP_PAGE_SEC = 600;
const PP_GATE_MS  = 120000;               // guests see the login / register popup after this many ms (2 minutes)

/** Does this visitor own the full paper library?  false for everybody for now - put the real package check here. */
function pp_full_access(): bool
{
    return false;
}
/* ================================================================================================================================================ */

/* ---------- helpers ---------- */
function pp_h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

/**
 * Markup -> HTML.   漢字{かんじ} = furigana,   [[word]] = underlined target,   new line = <br>.
 * $stripTarget : remove the furigana inside [[ ]] (reading questions - it would give the answer away)
 * $stripAll    : remove ALL furigana (kanji-choice questions)
 */
function pp_ruby(string $raw, bool $stripTarget = false, bool $stripAll = false): string
{
    $s = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
    $s = preg_replace_callback('/\[\[(.+?)\]\]/u', function ($m) use ($stripTarget) {
        $in = $stripTarget ? preg_replace('/\{[^}]*\}/u', '', $m[1]) : $m[1];
        return '<u class="pp-t">' . $in . '</u>';
    }, $s);
    if ($stripAll) {
        return nl2br(preg_replace('/\{[^}]*\}/u', '', $s));
    }
    return nl2br(preg_replace('/([\x{4E00}-\x{9FFF}々〆]+)\{([^}]+)\}/u', '<ruby>$1<rt>$2</rt></ruby>', $s));
}

function pp_rate_ok(string $bucket, int $max, int $window): bool
{
    $ip  = (string) ($_SERVER['REMOTE_ADDR'] ?? '0');
    $f   = sys_get_temp_dir() . '/pp_rl_' . preg_replace('/\W/', '', $bucket) . '_' . md5($ip) . '.json';
    $now = time();
    $fh  = @fopen($f, 'c+');
    if (!$fh) { return true; }
    flock($fh, LOCK_EX);
    $a = json_decode((string) stream_get_contents($fh), true);
    if (!is_array($a)) { $a = []; }
    $a  = array_values(array_filter($a, function ($t) use ($now, $window) { return (int) $t > $now - $window; }));
    $ok = count($a) < $max;
    if ($ok) { $a[] = $now; }
    ftruncate($fh, 0); rewind($fh); fwrite($fh, json_encode($a)); flock($fh, LOCK_UN); fclose($fh);
    return $ok;
}

function pp_json($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, noarchive');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function pp_paper(PDO $pdo, int $id)
{
    $st = $pdo->prepare('SELECT * FROM past_papers WHERE id = :i AND is_active = 1');
    $st->execute(['i' => $id]);
    return $st->fetch(PDO::FETCH_ASSOC);
}

function pp_can_open(array $paper): bool
{
    return (int) $paper['is_free'] === 1 || pp_full_access();
}

if (empty($_SESSION['pp_csrf'])) { $_SESSION['pp_csrf'] = bin2hex(random_bytes(16)); }
$PP_CSRF = $_SESSION['pp_csrf'];

/* ---------- guest gate: where to come back to after login / register ---------- */
$ppGuest  = empty($_SESSION['user_id']);
$ppReturn = 'pastpapers.php';
if (isset($_GET['paper'])) {
    $ppReturn .= '?paper=' . (int) $_GET['paper'] . ((($_GET['mode'] ?? '') === 'exam') ? '&mode=exam' : '');
} elseif (isset($_GET['level'])) {
    $ppReturn .= '?level=' . rawurlencode((string) $_GET['level']);
}
$ppLoginUrl = $PP_LOGIN_URL    . '?next=' . rawurlencode($ppReturn);
$ppRegUrl   = $PP_REGISTER_URL . '?next=' . rawurlencode($ppReturn);

/* ======================================================================= API (POST json) ======================================================================= */
if (isset($_GET['api'])) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { pp_json(['error' => 'method'], 405); }
    if (!pp_rate_ok('api', PP_API_MAX, PP_API_SEC)) { header('Retry-After: 120'); pp_json(['error' => 'rate'], 429); }
    if (!hash_equals($PP_CSRF, (string) ($_SERVER['HTTP_X_CSRF'] ?? ''))) { pp_json(['error' => 'csrf'], 403); }

    $in = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($in)) { pp_json(['error' => 'bad'], 400); }
    $paper = pp_paper($pdo, (int) ($in['paper'] ?? 0));
    if (!$paper || !pp_can_open($paper)) { pp_json(['error' => 'locked'], 403); }

    if (($in['action'] ?? '') === 'check') {                       // practice mode: one question
        $st = $pdo->prepare('SELECT answer, explain_si FROM past_questions WHERE id = :q AND paper_id = :p');
        $st->execute(['q' => (int) ($in['q'] ?? 0), 'p' => (int) $paper['id']]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        if (!$r) { pp_json(['error' => 'nf'], 404); }
        pp_json(['correct' => (int) $r['answer'], 'explain' => (string) $r['explain_si']]);
    }

    if (($in['action'] ?? '') === 'submit') {                      // whole paper
        $picked = is_array($in['answers'] ?? null) ? $in['answers'] : [];
        $st = $pdo->prepare('SELECT id, answer, explain_si FROM past_questions WHERE paper_id = :p ORDER BY sort_order, id');
        $st->execute(['p' => (int) $paper['id']]);
        $score = 0; $total = 0; $res = []; $save = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $q) {
            $total++;
            $pick = (int) ($picked[$q['id']] ?? 0);
            if ($pick === (int) $q['answer']) { $score++; }
            $save[$q['id']] = $pick;
            $res[$q['id']] = ['correct' => (int) $q['answer'], 'explain' => (string) $q['explain_si']];
        }
        $mode = (($in['mode'] ?? '') === 'practice') ? 'practice' : 'exam';
        $secs = max(0, min(86400, (int) ($in['seconds'] ?? 0)));
        $uid  = (int) ($_SESSION['user_id'] ?? 0);
        if ($uid > 0) {
            try {
                $pdo->prepare('INSERT INTO past_attempts (user_id, paper_id, mode, score, total, seconds, answers) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$uid, (int) $paper['id'], $mode, $score, $total, $secs, json_encode($save)]);
                // dashboard "Activity History" (includes/activity.php from the dashboard upgrade) - optional
                if (is_file(__DIR__ . '/includes/activity.php')) {
                    require_once __DIR__ . '/includes/activity.php';
                    if (function_exists('la_log_activity')) {
                        $pct    = $total > 0 ? (int) round($score / $total * 100) : 0;
                        $detail = $score . '/' . $total . ' · ' . $pct . '% · ' . ($mode === 'exam' ? 'විභාග ක්‍රමය' : 'පුහුණු ක්‍රමය');
                        la_log_activity($uid, 'quiz', (string) $paper['title'], $detail, (string) $paper['level'], (int) $paper['id']);
                    }
                }
            } catch (Throwable $e) { error_log('[pastpapers] ' . $e->getMessage()); }
        }
        pp_json(['score' => $score, 'total' => $total, 'results' => $res, 'saved' => $uid > 0]);
    }
    pp_json(['error' => 'action'], 400);
}

/* ======================================================================= PAGE ======================================================================= */
if (!pp_rate_ok('page', PP_PAGE_MAX, PP_PAGE_SEC)) {
    http_response_code(429); header('Retry-After: 120'); header('Content-Type: text/plain; charset=utf-8');
    echo 'Too many requests. Please slow down.'; exit;
}
header('X-Robots-Tag: noarchive');

$ppLevel = strtoupper((string) ($_GET['level'] ?? 'N3'));
if (!in_array($ppLevel, ['N5', 'N4', 'N3', 'N2', 'N1'], true)) { $ppLevel = 'N3'; }

$ppState  = 'list';      // list | paper | locked | notfound
$ppPaper  = null;
$ppQs     = [];
$ppMode   = (($_GET['mode'] ?? '') === 'exam') ? 'exam' : 'practice';
$ppPapers = [];
$ppBest   = [];          // best score per paper for the logged in user

try {
    if (isset($_GET['paper'])) {
        $ppPaper = pp_paper($pdo, (int) $_GET['paper']);
        if (!$ppPaper) {
            $ppState = 'notfound'; http_response_code(404);
        } else {
            $ppLevel = (string) $ppPaper['level'];
            header('X-Robots-Tag: noindex, noarchive');                       // paper pages are never indexed
            if (!pp_can_open($ppPaper)) {
                $ppState = 'locked';                                          // no question is loaded at all
            } else {
                $ppState = 'paper';
                // NOTE: the `answer` and `explain_si` columns are intentionally NOT selected here
                $st = $pdo->prepare('SELECT id, mondai_no, mondai_type, instr_jp, instr_si, q_no, text_jp, text_si, choices_jp, choices_si, img
                                     FROM past_questions WHERE paper_id = :p ORDER BY sort_order, id');
                $st->execute(['p' => (int) $ppPaper['id']]);
                $ppQs = $st->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    } else {
        $st = $pdo->prepare('SELECT p.*, (SELECT COUNT(*) FROM past_questions q WHERE q.paper_id = p.id) AS qn
                             FROM past_papers p WHERE p.level = :l AND p.is_active = 1 ORDER BY p.sort_order, p.id');
        $st->execute(['l' => $ppLevel]);
        $ppPapers = $st->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($_SESSION['user_id'])) {
            $st = $pdo->prepare('SELECT paper_id, MAX(ROUND(score / total * 100)) AS best FROM past_attempts WHERE user_id = :u AND total > 0 GROUP BY paper_id');
            $st->execute(['u' => (int) $_SESSION['user_id']]);
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) { $ppBest[(int) $r['paper_id']] = (int) $r['best']; }
        }
    }
} catch (Throwable $e) {
    error_log('[pastpapers] ' . $e->getMessage());
    $ppState = 'notfound';
}

/* ---------- texts ---------- */
$PT = [
  'title' => 'ප්‍රශ්න පත්‍ර', 'sub' => 'JLPT ආකෘතියේ පුහුණු ප්‍රශ්න පත්‍ර · ෆුරිගනා සහ සිංහල තේරුම් on / off කරන්න',
  'start_practice' => 'පුහුණුව', 'start_exam' => 'විභාග ක්‍රමය', 'q' => 'ප්‍රශ්න', 'min' => 'මිනිත්තු',
  'free' => 'නොමිලේ', 'locked' => 'Upgrade', 'best' => 'හොඳම ලකුණු', 'back' => 'ආපසු', 'none' => 'තවම ප්‍රශ්න පත්‍ර නැහැ. ඉක්මනින් එනවා!',
  'furi' => 'ෆුරිගනා (ひらがな)', 'si' => 'සිංහල තේරුම', 'submit' => 'අවසන් කරන්න', 'left' => 'ඉතිරි කාලය', 'result' => 'ඔබේ ලකුණු',
  'again' => 'නැවත කරන්න', 'saved' => 'ලකුණු ඔබේ ගිණුමට සුරැකුවා', 'login' => 'ලකුණු සුරකින්න Login වන්න',
  'ex' => 'විස්තරය', 'mode_p' => 'පුහුණු ක්‍රමය', 'mode_e' => 'විභාග ක්‍රමය', 'unans' => 'පිළිතුරු නොදුන් ප්‍රශ්න ඇත. ඒත් අවසන් කරනවාද?',
  'lock_t' => 'මේ ප්‍රශ්න පත්‍රය විවෘත කරන්න Package එක Upgrade කරන්න', 'lock_s' => 'නොමිලේ ප්‍රශ්න පත්‍රය අවසන්. ඉතිරි සියල්ල ලබාගන්න Upgrade කරන්න.',
  'lock_up' => 'Upgrade කරන්න', 'lock_book' => 'පොත් බලන්න', 'time_up' => 'කාලය අවසන්!',
  'gate_t' => 'දිගටම කරගෙන යන්න Login වන්න',
  'gate_s' => 'ඔබේ ලකුණු සුරකින්න සහ සියලු ප්‍රශ්න පත්‍ර ලබාගන්න Login වන්න, නැත්නම් නොමිලේ ලියාපදිංචි වන්න. අවසන් වූ පසු ඔබ නැවතත් මේ ප්‍රශ්න පත්‍රයටම පැමිණෙනවා.',
  'gate_login' => 'Login වන්න', 'gate_reg' => 'ලියාපදිංචි වන්න',
];

$pageTitle = $ppState === 'paper' ? $ppPaper['title'] : ($PT['title'] . ' ' . $ppLevel);
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
    @keyframes ppUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }
    [lang="ja"] { font-family: 'Noto Sans JP', 'Hiragino Sans', 'Yu Gothic', 'Meiryo', sans-serif; }
    [lang="si"] { font-family: 'Noto Sans Sinhala', 'Iskoola Pota', 'Nirmala UI', sans-serif; }

    /* ---- copy protection: nothing can be selected / long-pressed / dragged ---- */
    body, .pp-wrap { -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none; -webkit-touch-callout: none; }
    img, a { -webkit-user-drag: none; user-drag: none; }
    ::selection { background: transparent; }
    @media print { html, body { display: none !important; } }

    .pp-hero { background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); color: #fff; border-radius: var(--nm-radius); padding: 24px 20px;
               box-shadow: var(--nm-raised); position: relative; overflow: hidden; animation: ppUp .5s ease both; }
    .pp-hero::before { content: ""; position: absolute; width: 130px; height: 130px; border-radius: 50%; background: rgba(255,255,255,.12); top: -45px; right: -25px; }
    .pp-hero > * { position: relative; z-index: 1; }
    .pp-hero h1 { font-weight: 800; font-size: 1.4rem; margin: 0 0 4px; }
    .pp-hero p { margin: 0; opacity: .92; font-size: .9rem; }
    .pp-badge { display: inline-block; font-weight: 800; background: rgba(255,255,255,.22); border-radius: 999px; padding: 3px 14px; margin-right: 8px; }

    .pp-nav { display: flex; flex-wrap: wrap; gap: 10px; margin: 16px 0 8px; }
    .pp-chip { border: 0; background: var(--nm-bg); color: var(--nm-text); font-weight: 700; font-size: .85rem; padding: 9px 18px; border-radius: 999px;
               box-shadow: var(--nm-raised-sm); text-decoration: none !important; white-space: nowrap; display: inline-block; min-height: 40px; }
    .pp-chip:active, .pp-chip.on { color: var(--nm-brand); box-shadow: var(--nm-inset-sm); }

    /* ---- list ---- */
    .pp-grid { display: grid; grid-template-columns: 1fr; gap: 18px; margin: 14px 0 28px; }
    @media (min-width: 720px) { .pp-grid { grid-template-columns: 1fr 1fr; } }
    .pp-card { background: var(--nm-bg); border-radius: var(--nm-radius); box-shadow: var(--nm-raised); padding: 20px; animation: ppUp .4s ease both; }
    .pp-card h2 { font-size: 1.05rem; font-weight: 800; margin: 0 0 8px; }
    .pp-meta { display: flex; flex-wrap: wrap; gap: 8px; font-size: .78rem; color: var(--nm-muted); margin-bottom: 14px; }
    .pp-tag { font-weight: 800; font-size: .7rem; padding: 3px 10px; border-radius: 999px; color: #fff; background: linear-gradient(135deg,#10b981,#34d399); }
    .pp-tag.lock { background: linear-gradient(135deg,#f59e0b,#fbbf24); }
    .pp-tag.best { background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); }
    .pp-act { display: flex; gap: 10px; flex-wrap: wrap; }
    .pp-act a { flex: 1 1 130px; text-align: center; text-decoration: none !important; font-weight: 800; font-size: .88rem; padding: 12px 10px; border-radius: 999px; min-height: 46px;
                background: var(--nm-bg); color: var(--nm-brand); box-shadow: var(--nm-raised-sm); }
    .pp-act a.pri { background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); color: #fff; }
    .pp-act a:active { box-shadow: var(--nm-inset-sm); }
    .pp-empty { text-align: center; padding: 34px 18px; margin: 14px 0; border-radius: var(--nm-radius-sm); box-shadow: var(--nm-inset); color: var(--nm-muted); }

    /* ---- sticky tool bar on the paper page ---- */
    .pp-bar { position: sticky; top: 0; z-index: 30; background: var(--nm-bg); padding: 10px 4px; margin: 10px -4px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    .pp-sw { display: inline-flex; align-items: center; gap: 8px; font-weight: 700; font-size: .82rem; background: var(--nm-bg); border-radius: 999px; padding: 8px 14px;
             box-shadow: var(--nm-raised-sm); cursor: pointer; border: 0; color: var(--nm-text); min-height: 42px; }
    .pp-sw i { width: 34px; height: 20px; border-radius: 99px; background: var(--nm-dark); position: relative; box-shadow: var(--nm-inset-sm); transition: background .2s; }
    .pp-sw i::after { content: ""; position: absolute; top: 3px; left: 3px; width: 14px; height: 14px; border-radius: 50%; background: #fff; transition: transform .2s; }
    .pp-sw.on i { background: var(--nm-brand); } .pp-sw.on i::after { transform: translateX(14px); }
    .pp-sw[disabled] { opacity: .45; cursor: not-allowed; }
    .pp-timer { font-weight: 800; color: var(--nm-brand); background: var(--nm-bg); border-radius: 999px; padding: 8px 16px; box-shadow: var(--nm-inset-sm); font-variant-numeric: tabular-nums; }
    .pp-timer.warn { color: #dc2626; }

    /* ---- questions ---- */
    .pp-m { margin: 22px 4px 10px; font-weight: 800; color: var(--nm-brand); font-size: .95rem; }
    .pp-m small { display: block; color: var(--nm-text); font-weight: 600; margin-top: 4px; font-size: .85rem; }
    .pp-q { background: var(--nm-bg); border-radius: var(--nm-radius-sm); box-shadow: var(--nm-raised-sm); padding: 16px 16px 12px; margin-bottom: 16px; }
    .pp-qn { display: inline-block; font-weight: 800; font-size: .75rem; color: var(--nm-muted); box-shadow: var(--nm-inset-sm); border-radius: 999px; padding: 4px 12px; margin-bottom: 8px; }
    .pp-qt { font-size: 1.18rem; line-height: 2.1; font-weight: 600; word-break: break-word; }
    .pp-t { text-decoration: underline; text-underline-offset: 5px; text-decoration-thickness: 2px; text-decoration-color: var(--nm-brand); }
    .pp-img { display: block; max-width: 100%; height: auto; margin: 10px 0; border-radius: 12px; pointer-events: none; -webkit-user-drag: none; }
    ruby rt { font-size: .5em; color: var(--nm-brand); font-weight: 600; }
    .pp-noruby rt { display: none; }
    .pp-si { display: block; font-size: .85rem; color: var(--nm-muted); line-height: 1.55; font-weight: 500; margin-top: 2px; }
    .pp-nosi .pp-si { display: none; }
    .pp-cs { display: grid; grid-template-columns: 1fr; gap: 10px; margin-top: 10px; }
    @media (min-width: 640px) { .pp-cs { grid-template-columns: 1fr 1fr; } }
    .pp-c { display: flex; gap: 10px; align-items: flex-start; text-align: left; width: 100%; border: 0; background: var(--nm-bg); color: var(--nm-text); border-radius: 14px;
            box-shadow: var(--nm-raised-sm); padding: 11px 14px; min-height: 50px; font-size: 1.05rem; transition: box-shadow .15s ease; }
    .pp-c:active { box-shadow: var(--nm-inset-sm); }
    .pp-c b { flex: 0 0 auto; width: 26px; height: 26px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: .78rem; box-shadow: var(--nm-inset-sm); color: var(--nm-muted); }
    .pp-c.sel { box-shadow: var(--nm-inset-sm); color: var(--nm-brand); }
    .pp-c.sel b { background: var(--nm-brand); color: #fff; box-shadow: none; }
    .pp-c.ok { box-shadow: var(--nm-inset-sm); outline: 2px solid #10b981; } .pp-c.ok b { background: #10b981; color: #fff; box-shadow: none; }
    .pp-c.bad { box-shadow: var(--nm-inset-sm); outline: 2px solid #ef4444; } .pp-c.bad b { background: #ef4444; color: #fff; box-shadow: none; }
    .pp-c.done { pointer-events: none; }
    .pp-exp { display: none; margin-top: 12px; padding: 12px 14px; border-radius: 14px; box-shadow: var(--nm-inset-sm); font-size: .92rem; line-height: 1.65; }
    .pp-exp.show { display: block; }
    .pp-exp strong { color: var(--nm-brand); }

    .pp-submit { display: block; width: 100%; max-width: 420px; margin: 22px auto 10px; border: 0; color: #fff; font-weight: 800; font-size: 1.05rem; padding: 15px; border-radius: 999px;
                 background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); box-shadow: var(--nm-raised); min-height: 52px; }
    .pp-submit:active { box-shadow: var(--nm-inset); }
    .pp-res { display: none; text-align: center; background: var(--nm-bg); border-radius: var(--nm-radius); box-shadow: var(--nm-raised); padding: 24px 18px; margin: 14px 0 22px; }
    .pp-res.show { display: block; animation: ppUp .4s ease both; }
    .pp-res .big { font-size: 2.6rem; font-weight: 800; color: var(--nm-brand); line-height: 1.1; }
    .pp-res p { color: var(--nm-muted); margin: 6px 0 0; font-size: .9rem; }

    .pp-lock { text-align: center; background: var(--nm-bg); border-radius: var(--nm-radius); box-shadow: var(--nm-raised); padding: 26px 18px; margin: 22px auto; max-width: 640px; }
    .pp-lock .l-t { font-weight: 800; font-size: 1.1rem; margin: 10px 0 6px; }
    .pp-lock .l-s { color: var(--nm-muted); font-size: .92rem; line-height: 1.6; margin: 0 0 16px; }
    .pp-lock .l-act { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; }
    .pp-lock .l-act a { flex: 1 1 200px; min-height: 50px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none !important; font-weight: 800; border-radius: 999px; padding: 12px 18px; }
    .pp-lock .l-u { color: #fff; background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); box-shadow: var(--nm-raised-sm); }
    .pp-lock .l-b { color: var(--nm-brand); background: var(--nm-bg); box-shadow: var(--nm-raised-sm); }

    /* ---- guest gate popup (login / register after 2 minutes) ---- */
    .pp-gate { display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(45,50,80,.55); align-items: center; justify-content: center; padding: 18px; }
    .pp-gate.show { display: flex; animation: ppUp .3s ease both; }
    .pp-gate .box { background: var(--nm-bg); border-radius: var(--nm-radius); box-shadow: var(--nm-raised); padding: 26px 20px; max-width: 460px; width: 100%; text-align: center; }
    .pp-gate .g-t { font-weight: 800; font-size: 1.1rem; margin: 8px 0 6px; }
    .pp-gate .g-s { color: var(--nm-muted); font-size: .9rem; line-height: 1.6; margin: 0 0 16px; }
    .pp-gate .g-act { display: flex; gap: 12px; flex-wrap: wrap; }
    .pp-gate .g-act a { flex: 1 1 160px; min-height: 50px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none !important; font-weight: 800;
                        border-radius: 999px; padding: 12px 18px; background: var(--nm-bg); color: var(--nm-brand); box-shadow: var(--nm-raised-sm); }
    .pp-gate .g-act a.pri { color: #fff; background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); }
</style>

<div class="pp-wrap">

<?php if ($ppState === 'list'): /* ===================================== LIST ===================================== */ ?>

    <div class="pp-hero mb-3">
        <h1>📝 <span class="pp-badge"><?php echo pp_h($ppLevel); ?></span><?php echo pp_h($PT['title']); ?></h1>
        <p><?php echo pp_h($PT['sub']); ?></p>
    </div>

    <div class="pp-nav">
        <a class="pp-chip" href="<?php echo pp_h($PP_DASH[$ppLevel]); ?>">← <?php echo pp_h($PT['back']); ?> <?php echo pp_h($ppLevel); ?></a>
        <?php foreach (['N5', 'N4', 'N3', 'N2', 'N1'] as $n): ?>
            <a class="pp-chip <?php echo $n === $ppLevel ? 'on' : ''; ?>" href="pastpapers.php?level=<?php echo $n; ?>"><?php echo $n; ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (!$ppPapers): ?>
        <div class="pp-empty"><?php echo pp_h($PT['none']); ?></div>
    <?php else: ?>
    <div class="pp-grid">
        <?php foreach ($ppPapers as $i => $p):
            $open = ((int) $p['is_free'] === 1) || pp_full_access(); ?>
            <div class="pp-card" style="animation-delay:<?php echo $i * 0.05; ?>s">
                <h2 lang="ja"><?php echo pp_h((string) $p['title']); ?></h2>
                <div class="pp-meta">
                    <span class="pp-tag <?php echo $open ? '' : 'lock'; ?>"><?php echo $open ? pp_h($PT['free']) : '🔒 ' . pp_h($PT['locked']); ?></span>
                    <span><?php echo (int) $p['qn']; ?> <?php echo pp_h($PT['q']); ?></span>
                    <span><?php echo (int) $p['minutes']; ?> <?php echo pp_h($PT['min']); ?></span>
                    <?php if (isset($ppBest[(int) $p['id']])): ?><span class="pp-tag best"><?php echo pp_h($PT['best']); ?> <?php echo (int) $ppBest[(int) $p['id']]; ?>%</span><?php endif; ?>
                </div>
                <div class="pp-act">
                    <?php if ($open): ?>
                        <a class="pri" href="pastpapers.php?paper=<?php echo (int) $p['id']; ?>">📖 <?php echo pp_h($PT['start_practice']); ?></a>
                        <a href="pastpapers.php?paper=<?php echo (int) $p['id']; ?>&amp;mode=exam">⏱ <?php echo pp_h($PT['start_exam']); ?></a>
                    <?php else: ?>
                        <a class="pri" href="<?php echo pp_h($PP_UPGRADE_URL . '?level=' . $ppLevel); ?>">👑 <?php echo pp_h($PT['lock_up']); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

<?php elseif ($ppState === 'locked'): /* ===================================== LOCKED ===================================== */ ?>

    <div class="pp-hero mb-3"><h1>🔒 <?php echo pp_h((string) $ppPaper['title']); ?></h1></div>
    <div class="pp-lock">
        <div style="font-size:2.2rem">🔒</div>
        <div class="l-t"><?php echo pp_h($PT['lock_t']); ?></div>
        <p class="l-s"><?php echo pp_h($PT['lock_s']); ?></p>
        <div class="l-act">
            <a class="l-u" href="<?php echo pp_h($PP_UPGRADE_URL . '?level=' . $ppLevel); ?>">👑 <?php echo pp_h($PT['lock_up']); ?></a>
            <a class="l-b" href="<?php echo pp_h($PP_BOOKS_URL); ?>">📘 <?php echo pp_h($PT['lock_book']); ?></a>
        </div>
    </div>
    <div class="pp-nav"><a class="pp-chip" href="pastpapers.php?level=<?php echo pp_h($ppLevel); ?>">← <?php echo pp_h($PT['back']); ?></a></div>

<?php elseif ($ppState === 'notfound'): ?>

    <div class="pp-empty" style="margin-top:20px"><a class="pp-chip" href="pastpapers.php">← <?php echo pp_h($PT['title']); ?></a></div>

<?php else: /* ===================================== PAPER ===================================== */ ?>

    <div class="pp-hero mb-2">
        <h1 lang="ja"><span class="pp-badge"><?php echo pp_h($ppLevel); ?></span><?php echo pp_h((string) $ppPaper['title']); ?></h1>
        <p><?php echo count($ppQs); ?> <?php echo pp_h($PT['q']); ?> · <?php echo $ppMode === 'exam' ? pp_h($PT['mode_e']) : pp_h($PT['mode_p']); ?></p>
    </div>

    <div class="pp-bar">
        <span style="display:flex;gap:10px;flex-wrap:wrap">
            <button type="button" class="pp-sw on" id="swFuri"><i></i><?php echo pp_h($PT['furi']); ?></button>
            <button type="button" class="pp-sw on" id="swSi"><i></i><?php echo pp_h($PT['si']); ?></button>
        </span>
        <?php if ($ppMode === 'exam'): ?><span class="pp-timer" id="ppTimer">--:--</span><?php endif; ?>
    </div>

    <div class="pp-res" id="ppRes">
        <div class="big" id="ppScore"></div>
        <p id="ppScoreSub"></p>
        <div class="pp-act" style="justify-content:center;margin-top:14px">
            <a class="pri" href="pastpapers.php?paper=<?php echo (int) $ppPaper['id']; ?><?php echo $ppMode === 'exam' ? '&amp;mode=exam' : ''; ?>">🔁 <?php echo pp_h($PT['again']); ?></a>
            <a href="pastpapers.php?level=<?php echo pp_h($ppLevel); ?>">← <?php echo pp_h($PT['back']); ?></a>
        </div>
    </div>

    <div id="ppRoot" class="pp-root">
    <?php
    $lastM = null;
    foreach ($ppQs as $q):
        $type    = (string) $q['mondai_type'];
        $stripT  = ($type === 'read');            // reading questions: never show furigana on the underlined word
        $stripC  = ($type === 'write');           // kanji-choice questions: never show furigana on the choices
        $chJp    = json_decode((string) $q['choices_jp'], true) ?: [];
        $chSi    = json_decode((string) $q['choices_si'], true) ?: [];
        if ($lastM !== (int) $q['mondai_no']):
            $lastM = (int) $q['mondai_no']; ?>
        <div class="pp-m" lang="ja">問題 <?php echo $lastM; ?>
            <?php if (!empty($q['instr_jp'])): ?><small><?php echo pp_ruby((string) $q['instr_jp']); ?></small><?php endif; ?>
            <?php if (!empty($q['instr_si'])): ?><small class="pp-si" lang="si"><?php echo pp_h((string) $q['instr_si']); ?></small><?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="pp-q" data-q="<?php echo (int) $q['id']; ?>">
            <span class="pp-qn"><?php echo (int) $q['q_no']; ?></span>
            <div class="pp-qt" lang="ja"><?php echo pp_ruby((string) $q['text_jp'], $stripT); ?></div>
            <?php if (!empty($q['img'])): ?>
                <img class="pp-img" src="<?php echo pp_h($PP_IMG_DIR . rawurlencode(basename((string) $q['img']))); ?>" alt="" loading="lazy" draggable="false">
            <?php endif; ?>
            <?php if (!empty($q['text_si'])): ?><span class="pp-si" lang="si"><?php echo pp_h((string) $q['text_si']); ?></span><?php endif; ?>
            <div class="pp-cs">
                <?php foreach ($chJp as $k => $c): ?>
                    <button type="button" class="pp-c" data-n="<?php echo $k + 1; ?>">
                        <b><?php echo $k + 1; ?></b>
                        <span lang="ja"><?php echo pp_ruby((string) $c, false, $stripC); ?>
                            <?php if (!empty($chSi[$k])): ?><span class="pp-si" lang="si"><?php echo pp_h((string) $chSi[$k]); ?></span><?php endif; ?>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="pp-exp" lang="si"></div>
        </div>
    <?php endforeach; ?>
    </div>

    <button type="button" class="pp-submit" id="ppSubmit"><?php echo pp_h($PT['submit']); ?></button>

    <?php if ($ppGuest): /* guest gate popup - cannot be closed, shown by the JS after PP_GATE_MS */ ?>
    <div class="pp-gate" id="ppGate" role="dialog" aria-modal="true" aria-labelledby="ppGateT">
        <div class="box">
            <div style="font-size:2.2rem">🔐</div>
            <div class="g-t" id="ppGateT"><?php echo pp_h($PT['gate_t']); ?></div>
            <p class="g-s"><?php echo pp_h($PT['gate_s']); ?></p>
            <div class="g-act">
                <a class="pri" href="<?php echo pp_h($ppLoginUrl); ?>"><?php echo pp_h($PT['gate_login']); ?></a>
                <a href="<?php echo pp_h($ppRegUrl); ?>"><?php echo pp_h($PT['gate_reg']); ?></a>
            </div>
        </div>
    </div>
    <?php endif; ?>

<?php endif; ?>

</div>

<script>
(function () {
  'use strict';

  /* ---------- COPY PROTECTION (hard-coded) ---------- */
  var inField = function (t) { return !!(t && t.closest && t.closest('input,textarea')); };
  ['copy', 'cut'].forEach(function (n) {
    document.addEventListener(n, function (e) { if (inField(e.target)) return; e.preventDefault(); try { e.clipboardData.setData('text/plain', location.hostname); } catch (x) {} }, true);
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

  var root = document.getElementById('ppRoot');
  if (!root) return;                                   // list / lock pages have nothing else to do

  var CFG = <?php echo json_encode([
      'paper' => $ppState === 'paper' ? (int) $ppPaper['id'] : 0,
      'mode'  => $ppMode,
      'min'   => $ppState === 'paper' ? (int) $ppPaper['minutes'] : 0,
      'csrf'  => $PP_CSRF,
      't'     => ['unans' => $PT['unans'], 'ex' => $PT['ex'], 'result' => $PT['result'], 'saved' => $PT['saved'], 'login' => $PT['login'], 'time_up' => $PT['time_up']],
  ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); ?>;
  var $ = function (s) { return document.querySelector(s); };
  var exam = CFG.mode === 'exam', finished = false, started = Date.now();
  var picked = {};                                     // questionId -> choice

  /* ---------- guest gate: login / register popup after 2 minutes ---------- */
  var gate = document.getElementById('ppGate');
  if (gate) { setTimeout(function () { gate.classList.add('show'); }, <?php echo (int) PP_GATE_MS; ?>); }

  function lsGet(k, d) { try { var v = localStorage.getItem(k); return v === null ? d : v; } catch (e) { return d; } }
  function lsSet(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }

  /* ---------- furigana / Sinhala toggles ---------- */
  var swF = $('#swFuri'), swS = $('#swSi');
  function applyToggles(furi, si) {
    root.classList.toggle('pp-noruby', !furi); root.classList.toggle('pp-nosi', !si);
    swF.classList.toggle('on', furi); swS.classList.toggle('on', si);
  }
  var furi = lsGet('pp_furi', '1') === '1', si = lsGet('pp_si', '1') === '1';
  if (exam) { furi = false; si = false; swF.disabled = true; swS.disabled = true; }   // exam = like the real test
  applyToggles(furi, si);
  swF.onclick = function () { if (swF.disabled) return; furi = !furi; lsSet('pp_furi', furi ? '1' : '0'); applyToggles(furi, si); };
  swS.onclick = function () { if (swS.disabled) return; si = !si; lsSet('pp_si', si ? '1' : '0'); applyToggles(furi, si); };

  /* ---------- api ---------- */
  function api(body) {
    body.paper = CFG.paper;
    return fetch('pastpapers.php?api=1', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF': CFG.csrf }, body: JSON.stringify(body) })
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); });
  }

  function showExplain(qEl, text) {
    var box = qEl.querySelector('.pp-exp');
    if (!text) return;
    box.textContent = '';
    var s = document.createElement('strong'); s.textContent = CFG.t.ex + ': ';
    box.appendChild(s); box.appendChild(document.createTextNode(text)); box.classList.add('show');
  }
  function mark(qEl, pick, correct) {
    [].forEach.call(qEl.querySelectorAll('.pp-c'), function (b) {
      var n = +b.getAttribute('data-n'); b.classList.add('done'); b.classList.remove('sel');
      if (n === correct) b.classList.add('ok'); else if (n === pick) b.classList.add('bad');
    });
  }

  /* ---------- choose an answer ---------- */
  root.addEventListener('click', function (ev) {
    var btn = ev.target.closest('.pp-c'); if (!btn || finished) return;
    var qEl = btn.closest('.pp-q'), qid = qEl.getAttribute('data-q'), n = +btn.getAttribute('data-n');
    if (qEl.getAttribute('data-done')) return;
    picked[qid] = n;
    if (exam) {                                        // exam: just remember the choice (can be changed until submit)
      [].forEach.call(qEl.querySelectorAll('.pp-c'), function (b) { b.classList.toggle('sel', b === btn); });
      return;
    }
    qEl.setAttribute('data-done', '1');                // practice: ask the server right now
    btn.classList.add('sel');
    api({ action: 'check', q: +qid }).then(function (r) {
      mark(qEl, n, r.correct); showExplain(qEl, r.explain);
    }).catch(function () { qEl.removeAttribute('data-done'); btn.classList.remove('sel'); });
  });

  /* ---------- submit ---------- */
  var submitBtn = $('#ppSubmit'), resBox = $('#ppRes');
  function finish(auto) {
    if (finished) return;
    var total = root.querySelectorAll('.pp-q').length, answered = Object.keys(picked).length;
    if (!auto && answered < total && !confirm(CFG.t.unans)) return;
    finished = true; submitBtn.disabled = true;
    api({ action: 'submit', mode: CFG.mode, answers: picked, seconds: Math.round((Date.now() - started) / 1000) }).then(function (r) {
      [].forEach.call(root.querySelectorAll('.pp-q'), function (qEl) {
        var id = qEl.getAttribute('data-q'), info = r.results[id]; if (!info) return;
        mark(qEl, picked[id] || 0, info.correct); showExplain(qEl, info.explain);
      });
      $('#ppScore').textContent = r.score + ' / ' + r.total;
      $('#ppScoreSub').textContent = Math.round(r.score / Math.max(1, r.total) * 100) + '%  ·  ' + (r.saved ? CFG.t.saved : CFG.t.login);
      resBox.classList.add('show'); submitBtn.style.display = 'none';
      swF.disabled = false; swS.disabled = false;      // exam is over: toggles unlock for the review
      if (exam) { furi = lsGet('pp_furi', '1') === '1'; si = lsGet('pp_si', '1') === '1'; applyToggles(furi, si); }
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }).catch(function () { finished = false; submitBtn.disabled = false; });
  }
  submitBtn.onclick = function () { finish(false); };

  /* ---------- exam timer ---------- */
  var tEl = $('#ppTimer');
  if (exam && tEl) {
    var end = Date.now() + CFG.min * 60000;
    var tick = setInterval(function () {
      var left = Math.max(0, Math.round((end - Date.now()) / 1000));
      tEl.textContent = String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2, '0');
      tEl.classList.toggle('warn', left <= 60);
      if (finished) { clearInterval(tick); }
      if (left <= 0) { clearInterval(tick); alert(CFG.t.time_up); finish(true); }
    }, 500);
  }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>