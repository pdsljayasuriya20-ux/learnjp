<?php
// ---------------------------------------------------------------
// hiragana.php — neumorphism theme, uses includes/header.php + footer.php
// header.php owns <!DOCTYPE>/<head>/<body>; this page only outputs content.
// NOTE: every variable here is prefixed "pg" so it can never overwrite
// $t / $translations / $languages etc. that header.php & footer.php use
// (that collision was what blanked the footer labels).
// ---------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) { session_start(); }
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ja', 'si'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}
$pgLang = $_SESSION['lang'] ?? 'en';
if (!in_array($pgLang, ['en', 'ja', 'si'], true)) { $pgLang = 'en'; }

$pgSeo = [
    'en' => [
        'title'       => 'Master Hiragana & Katakana | Learn Japanese Online',
        'description' => 'Interactive guide to learn Japanese Hiragana with native audio pronunciation, quizzes, and writing tips.',
        'keywords'    => 'hiragana, learn hiragana, japanese alphabet, hiragana chart, JLPT N5',
    ],
    'ja' => [
        'title'       => 'ひらがな学習ガイド | 日本語オンライン学習',
        'description' => '音声付きのインタラクティブなひらがな学習コース。クイズや練習問題で効果的にマスター。',
        'keywords'    => 'ひらがな, ひらがな表, 日本語学習, JLPT N5',
    ],
    'si' => [
        'title'       => 'හිරගනා ඉගෙන ගනිමු | Learn Japanese',
        'description' => 'නිවැරදි උච්චාරණය සහිත හිරගනා අකුරු මාලාව පහසුවෙන් ඉගෙන ගැනීමට ඇති සරලම පාඩම් මාලාව.',
        'keywords'    => 'හිරගනා, hiragana sinhala, ජපන් අකුරු, JLPT N5',
    ],
];

$pageTitle       = $pgSeo[$pgLang]['title'];
$pageDescription = $pgSeo[$pgLang]['description'];
$pageKeywords    = $pgSeo[$pgLang]['keywords'];

require_once __DIR__ . '/includes/header.php';

// ---------- Page translations (own variable name!) ----------
$pgAll = [
    'en' => [
        'back'               => 'Back to Dashboard',
        'hero_title'         => 'あ',
        'hero_subtitle'      => 'Master the foundation of Japanese writing — Hiragana.',
        'section_title'      => 'Hiragana ひらがな',
        'section_subtitle'   => 'The foundation of Japanese writing',
        'basic_tab'          => 'Basic',
        'dakuten_tab'        => 'Dakuten',
        'combo_tab'          => 'Combos',
        'dakuten_title'      => 'Dakuten (゛) - Voiced Sounds',
        'handakuten_title'   => 'Handakuten (゜) - P-sounds',
        'combo_title'        => 'Combination Sounds (拗音)',
        'voiced_combo_title' => 'Voiced Combination Sounds',
        'quick_title'        => 'Quick Practice',
        'quick_subtitle'     => 'Tap on characters to listen to native pronunciation',
        'tips_title'         => '💡 Hiragana Study Tips',
        'tip1_title'         => '🎯 Start with Vowels',
        'tip1_text'          => 'Master あ, い, う, え, お first - they are the foundation of all other sounds.',
        'tip2_title'         => '✍️ Practice Writing',
        'tip2_text'          => 'Use proper stroke order - it helps with recognition and muscle memory.',
        'tip3_title'         => '📚 Read Daily',
        'tip3_text'          => 'Find simple texts with hiragana to practice reading in context.',
        'tip4_title'         => '🎵 Use Mnemonics',
        'tip4_text'          => 'Create memory aids - like "あ looks like someone saying \'ah!\'"',
        'quiz_btn'           => 'Start Quiz',
        'game_btn'           => 'Play Game',
        'writing_btn'        => 'Writing Practice',
    ],
    'ja' => [
        'back'               => 'ダッシュボードに戻る',
        'hero_title'         => 'あ',
        'hero_subtitle'      => '日本語の基本文字「ひらがな」をマスターしよう。',
        'section_title'      => 'ひらがな',
        'section_subtitle'   => '日本語書字の基礎',
        'basic_tab'          => '基本',
        'dakuten_tab'        => '濁点',
        'combo_tab'          => '拗音',
        'dakuten_title'      => '濁点 (゛) - 濁音',
        'handakuten_title'   => '半濁点 (゜) - パ行',
        'combo_title'        => '拗音 (ようおん)',
        'voiced_combo_title' => '濁音拗音',
        'quick_title'        => 'クイック練習',
        'quick_subtitle'     => '文字をタップして発音を聞きましょう',
        'tips_title'         => '💡 ひらがな学習のコツ',
        'tip1_title'         => '🎯 母音から始める',
        'tip1_text'          => 'あ、い、う、え、おを最初にマスターしよう。',
        'tip2_title'         => '✍️ 書字練習',
        'tip2_text'          => '正しい筆順で書く練習をしましょう。',
        'tip3_title'         => '📚 毎日読む',
        'tip3_text'          => '簡単な文章で文脈の中での読み練習をしましょう。',
        'tip4_title'         => '🎵 記憶術を使う',
        'tip4_text'          => '覚えやすい連想記憶法を活用しましょう。',
        'quiz_btn'           => 'クイズ開始',
        'game_btn'           => 'ゲームをプレイ',
        'writing_btn'        => '書字練習',
    ],
    'si' => [
        'back'               => 'ආපසු Dashboard වෙත',
        'hero_title'         => 'あ',
        'hero_subtitle'      => 'ජපන් ලිවීමේ පදනම වන හිරගනා අකුරු පහසුවෙන් ප්‍රගුණ කරන්න.',
        'section_title'      => 'හිරගනා (Hiragana)',
        'section_subtitle'   => 'ජපන් ලියවීමේ සහ උච්චාරණයේ පදනම',
        'basic_tab'          => 'මූලික',
        'dakuten_tab'        => 'දකුතෙන්',
        'combo_tab'          => 'සංයෝජන',
        'dakuten_title'      => 'දකුතෙන් (゛) - Voiced Sounds',
        'handakuten_title'   => 'හන්දකුතෙන් (゜) - P-Sounds',
        'combo_title'        => 'සංයෝජන ශබ්ද (拗音)',
        'voiced_combo_title' => 'Voiced සංයෝජන ශබ්ද',
        'quick_title'        => 'ඉක්මන් පුහුණුව',
        'quick_subtitle'     => 'නිවැරදි උච්චාරණය ඇසීමට අකුරු මත ටැප් (Tap) කරන්න',
        'tips_title'         => '💡 හිරගනා ඉගෙනීමට උපදෙස්',
        'tip1_title'         => '🎯 ස්වරවලින් ආරම්භ කරන්න',
        'tip1_text'          => 'あ, い, う, え, お මුලින්ම ප්‍රගුණ කරන්න - ඒවා අනෙකුත් ශබ්දයන්හි පදනමයි.',
        'tip2_title'         => '✍️ ලිවීමේ පුහුණුව',
        'tip2_text'          => 'නිවැරදි තෙලි පහර පිළිවෙළට (Stroke order) ලිවීමට පුරුදු වන්න.',
        'tip3_title'         => '📚 දිනපතා කියවන්න',
        'tip3_text'          => 'සරල ජපන් වගන්ති කියවීමෙන් අකුරු පහසුවෙන් මතක තබා ගන්න.',
        'tip4_title'         => '🎵 මතක ක්‍රම (Mnemonics)',
        'tip4_text'          => 'අකුරුවල හැඩය රූපමය සංකල්ප සමඟ සසඳා මතකයේ තබා ගන්න.',
        'quiz_btn'           => 'ප්‍රශ්නාවලිය',
        'game_btn'           => 'ක්‍රීඩා කරන්න',
        'writing_btn'        => 'ලිවීමේ පුහුණුව',
    ],
];
$pgT = $pgAll[$pgLang];

$pgWritingMsg = [
    'en' => 'Writing practice feature coming soon! Try tracing hiragana characters on paper.',
    'ja' => '書字練習機能は準備中です！紙にひらがなをなぞって練習してください。',
    'si' => 'ලිවීමේ පුහුණුව ඉක්මනින් පැමිණේ! කඩදාසියක හිරගනා අක්ෂර ලිවීමට උත්සාහ කරන්න.',
][$pgLang];

// ---------- Kana data: "char:romaji" , "-" = empty cell ----------
$pgVowels = 'あ:a,い:i,う:u,え:e,お:o';
$pgBasic = [
    'か:ka,き:ki,く:ku,け:ke,こ:ko',
    'さ:sa,し:shi,す:su,せ:se,そ:so',
    'た:ta,ち:chi,つ:tsu,て:te,と:to',
    'な:na,に:ni,ぬ:nu,ね:ne,の:no',
    'は:ha,ひ:hi,ふ:fu,へ:he,ほ:ho',
    'ま:ma,み:mi,む:mu,め:me,も:mo',
    'や:ya,-,ゆ:yu,-,よ:yo',
    'ら:ra,り:ri,る:ru,れ:re,ろ:ro',
    'わ:wa,-,-,-,を:wo',
    'ん:n,-,-,-,-',
];
$pgDakuten = [
    'が:ga,ぎ:gi,ぐ:gu,げ:ge,ご:go',
    'ざ:za,じ:ji,ず:zu,ぜ:ze,ぞ:zo',
    'だ:da,ぢ:ji,づ:zu,で:de,ど:do',
    'ば:ba,び:bi,ぶ:bu,べ:be,ぼ:bo',
];
$pgHandakuten = ['ぱ:pa,ぴ:pi,ぷ:pu,ぺ:pe,ぽ:po'];
$pgCombo = [
    'きゃ:kya,きゅ:kyu,きょ:kyo',
    'しゃ:sha,しゅ:shu,しょ:sho',
    'ちゃ:cha,ちゅ:chu,ちょ:cho',
    'にゃ:nya,にゅ:nyu,にょ:nyo',
    'ひゃ:hya,ひゅ:hyu,ひょ:hyo',
    'みゃ:mya,みゅ:myu,みょ:myo',
    'りゃ:rya,りゅ:ryu,りょ:ryo',
];
$pgVoicedCombo = [
    'ぎゃ:gya,ぎゅ:gyu,ぎょ:gyo',
    'じゃ:ja,じゅ:ju,じょ:jo',
    'びゃ:bya,びゅ:byu,びょ:byo',
    'ぴゃ:pya,ぴゅ:pyu,ぴょ:pyo',
];
$pgQuick = 'あ:a,か:ka,さ:sa,た:ta,な:na,は:ha,ま:ma,や:ya,ら:ra,わ:wa,が:ga,ぱ:pa';

// ---------- Helpers (prefixed so they can't clash with header/footer) ----------
if (!function_exists('pg_cells')) {
    function pg_cells(string $row, string $tag = 'td'): string {
        $out = '';
        foreach (explode(',', $row) as $pair) {
            if ($pair === '-') { $out .= '<' . $tag . ' class="empty" aria-hidden="true"></' . $tag . '>'; continue; }
            [$c, $r] = explode(':', $pair, 2);
            $c = htmlspecialchars($c, ENT_QUOTES, 'UTF-8');
            $r = htmlspecialchars($r, ENT_QUOTES, 'UTF-8');
            $out .= '<' . $tag . ' data-say="' . $c . '" tabindex="0" role="button" aria-label="' . $c . ' ' . $r . '">'
                  . '<span class="k">' . $c . '</span><span class="r">' . $r . '</span></' . $tag . '>';
        }
        return $out;
    }
    function pg_rows(array $rows): string {
        $out = '';
        foreach ($rows as $row) { $out .= '<tr>' . pg_cells($row) . '</tr>'; }
        return $out;
    }
}
?>

<!-- =====================================================
     NEUMORPHISM THEME — Hiragana page (mobile-first)
     ===================================================== -->
<style>
    :root {
        --nm-bg: #e6e9f2;
        --nm-light: #ffffff;
        --nm-dark: #c3c9dc;
        --nm-text: #2d3250;
        --nm-muted: #7a8099;
        --nm-brand: #4f46e5;
        --nm-brand-2: #7c6cf5;
        --nm-radius: 24px;
        --nm-radius-sm: 16px;

        --nm-raised:    8px 8px 18px var(--nm-dark), -8px -8px 18px var(--nm-light);
        --nm-raised-sm: 5px 5px 11px var(--nm-dark), -5px -5px 11px var(--nm-light);
        --nm-inset:     inset 5px 5px 11px var(--nm-dark), inset -5px -5px 11px var(--nm-light);
        --nm-inset-sm:  inset 3px 3px 7px var(--nm-dark), inset -3px -3px 7px var(--nm-light);
    }

    body {
        background: var(--nm-bg) !important;
        color: var(--nm-text);
        -webkit-tap-highlight-color: transparent;
        overflow-x: hidden;
    }
    .text-muted { color: var(--nm-muted) !important; }
    .pg-brand   { color: var(--nm-brand) !important; }

    @keyframes nmFadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @media (prefers-reduced-motion: reduce) {
        * { animation: none !important; transition: none !important; }
    }

    .pg-wrap { padding-bottom: 1.5rem; }

    /* ---------- Back button ---------- */
    .back-btn {
        display: inline-flex; align-items: center; gap: 6px;
        background: var(--nm-bg); color: var(--nm-muted);
        border-radius: 999px; padding: 9px 18px;
        font-size: .85rem; font-weight: 700; text-decoration: none;
        box-shadow: var(--nm-raised-sm);
        transition: box-shadow .2s ease, color .2s ease;
    }
    .back-btn:hover  { color: var(--nm-brand); }
    .back-btn:active { box-shadow: var(--nm-inset-sm); }

    /* ---------- Hero ---------- */
    .hero-banner {
        background: linear-gradient(135deg, var(--nm-brand) 0%, var(--nm-brand-2) 100%);
        border-radius: var(--nm-radius); color: #fff;
        padding: 2.3rem 1.5rem; text-align: center;
        box-shadow: var(--nm-raised);
        position: relative; overflow: hidden;
        animation: nmFadeUp .5s ease both;
    }
    .hero-banner::before, .hero-banner::after {
        content: ""; position: absolute; border-radius: 50%; background: rgba(255,255,255,.12);
    }
    .hero-banner::before { width: 150px; height: 150px; top: -55px; right: -30px; }
    .hero-banner::after  { width: 100px; height: 100px; bottom: -40px; left: 10%; }
    .hero-banner > * { position: relative; z-index: 1; }
    .hero-banner h1 { font-family: 'Noto Sans JP', sans-serif; letter-spacing: .12em; }

    /* ---------- Cards ---------- */
    .app-card {
        background: var(--nm-bg); border-radius: var(--nm-radius);
        padding: 1.5rem 1.2rem; border: 0;
        box-shadow: var(--nm-raised); margin-bottom: 1.75rem;
        animation: nmFadeUp .55s ease both;
    }

    /* ---------- Tabs (own JS — no Bootstrap JS needed) ---------- */
    .pg-tabs {
        display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;
        padding: 6px 4px 10px; margin: 0 0 1rem; list-style: none;
    }
    .pg-tab {
        border: 0; border-radius: 999px; padding: 9px 22px;
        font-weight: 700; font-size: .9rem; font-family: inherit;
        color: var(--nm-muted); background: var(--nm-bg);
        box-shadow: var(--nm-raised-sm); cursor: pointer;
        transition: box-shadow .2s ease, color .2s ease;
        white-space: nowrap;
    }
    .pg-tab:hover { color: var(--nm-brand); }
    .pg-tab[aria-selected="true"] { color: var(--nm-brand); box-shadow: var(--nm-inset-sm); }
    .pg-pane { display: none; }
    .pg-pane.is-active { display: block; animation: nmFadeUp .35s ease both; }

    /* ---------- Kana tables ---------- */
    .kana-wrap { margin: 0 -4px 6px; }
    .kana-table {
        width: 100%; table-layout: fixed;
        border-collapse: separate; border-spacing: 10px;
    }
    .kana-table th, .kana-table td {
        text-align: center; vertical-align: middle;
        padding: 12px 2px; border: 0;
        border-radius: var(--nm-radius-sm);
        background: var(--nm-bg); color: var(--nm-text);
        font-family: 'Noto Sans JP', sans-serif;
        line-height: 1.15; min-width: 0;
        word-break: keep-all;
    }
    .kana-table th {
        color: var(--nm-brand); font-weight: 800; font-size: .95rem;
        box-shadow: var(--nm-inset-sm);
    }
    .kana-table th[data-say] { box-shadow: var(--nm-raised-sm); }
    .kana-table td { box-shadow: var(--nm-raised-sm); }
    .kana-table [data-say] {
        cursor: pointer; user-select: none; -webkit-user-select: none;
        transition: box-shadow .2s ease, transform .2s ease, color .2s ease;
        outline: none;
    }
    .kana-table [data-say]:hover  { color: var(--nm-brand); transform: translateY(-2px); }
    .kana-table [data-say]:active,
    .kana-table [data-say].is-playing { box-shadow: var(--nm-inset-sm); transform: scale(.96); color: var(--nm-brand); }
    .kana-table [data-say]:focus-visible { box-shadow: 0 0 0 3px var(--nm-brand-2); }
    .kana-table .empty { background: transparent; box-shadow: none; padding: 0; }
    .kana-table .k { display: block; font-size: 1.55rem; font-weight: 700; }
    .kana-table .r { display: block; font-size: .72rem; font-weight: 700; color: var(--nm-muted); margin-top: 4px; font-family: 'Plus Jakarta Sans', sans-serif; }
    .kana-table th.sect { font-size: .9rem; padding: 11px 8px; }

    /* ---------- Quick practice ---------- */
    .quick-grid {
        display: grid; grid-template-columns: repeat(3, 1fr);
        gap: 12px; padding: 6px;
    }
    @media (min-width: 480px) { .quick-grid { grid-template-columns: repeat(4, 1fr); gap: 14px; } }
    @media (min-width: 768px) { .quick-grid { grid-template-columns: repeat(6, 1fr); gap: 16px; } }
    .quick-char {
        background: var(--nm-bg); border-radius: var(--nm-radius-sm);
        padding: 14px 6px; box-shadow: var(--nm-raised-sm);
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        cursor: pointer; user-select: none; -webkit-user-select: none;
        transition: box-shadow .2s ease, transform .2s ease; outline: none;
    }
    .quick-char:hover  { transform: translateY(-2px); }
    .quick-char:active, .quick-char.is-playing { box-shadow: var(--nm-inset-sm); transform: scale(.96); }
    .quick-char:focus-visible { box-shadow: 0 0 0 3px var(--nm-brand-2); }
    .quick-char-main { font-size: 1.7rem; font-family: 'Noto Sans JP', sans-serif; font-weight: 700; color: var(--nm-brand); line-height: 1; }
    .quick-char-rom  { font-size: .75rem; color: var(--nm-muted); font-weight: 700; margin-top: 6px; }

    /* ---------- Buttons ---------- */
    .btn-brand, .btn-brand-outline {
        border-radius: 999px; font-weight: 800; border: 0;
        text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        transition: box-shadow .2s ease, transform .2s ease;
        cursor: pointer; font-family: inherit; font-size: 1rem;
    }
    .btn-brand {
        background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2));
        color: #fff; padding: 13px 26px; box-shadow: var(--nm-raised-sm);
    }
    .btn-brand:hover { color: #fff; transform: translateY(-2px); }
    .btn-brand-outline {
        background: var(--nm-bg); color: var(--nm-brand);
        padding: 13px 24px; box-shadow: var(--nm-raised-sm);
    }
    .btn-brand-outline:hover { color: var(--nm-brand); transform: translateY(-2px); }
    .btn-brand:active, .btn-brand-outline:active { box-shadow: var(--nm-inset-sm); transform: scale(.97); }

    /* ---------- Tips ---------- */
    .tip-box {
        background: var(--nm-bg); border-radius: var(--nm-radius-sm);
        padding: 1.1rem 1.15rem; height: 100%;
        box-shadow: var(--nm-inset-sm);
        border-left: 5px solid var(--nm-brand);
    }

    /* ---------- Responsive ---------- */
    @media (max-width: 575.98px) {
        .app-card { padding: 1.2rem .75rem; border-radius: 20px; margin-bottom: 1.4rem; }
        .hero-banner { padding: 1.7rem 1.1rem; border-radius: 20px; }
        .hero-banner h1 { font-size: 2.4rem !important; }
        .hero-banner p  { font-size: .92rem !important; }
        .pg-tabs { gap: 8px; flex-wrap: nowrap; justify-content: flex-start; overflow-x: auto; scrollbar-width: none; -webkit-overflow-scrolling: touch; }
        .pg-tabs::-webkit-scrollbar { display: none; }
        .pg-tab { padding: 8px 16px; font-size: .85rem; }
        .kana-wrap { margin: 0 -2px 4px; }
        .kana-table { border-spacing: 6px; }
        .kana-table th, .kana-table td { padding: 10px 1px; border-radius: 12px; }
        .kana-table .k { font-size: 1.3rem; }
        .kana-table .r { font-size: .64rem; margin-top: 3px; }
        .kana-table th { font-size: .78rem; }
        .kana-table th.sect { font-size: .8rem; padding: 9px 4px; }
        .pg-actions .btn-brand, .pg-actions .btn-brand-outline { width: 100%; }
    }
    @media (max-width: 359.98px) {
        .kana-table { border-spacing: 4px; }
        .kana-table .k { font-size: 1.1rem; }
        .kana-table .r { font-size: .58rem; }
    }
</style>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "LearningResource",
  "name": "Japanese Hiragana Guide",
  "description": <?php echo json_encode($pageDescription, JSON_UNESCAPED_UNICODE); ?>,
  "educationalLevel": "Beginner"
}
</script>

<div class="pg-wrap">

    <!-- Back to Dashboard -->
    <a href="n5.php" class="back-btn mb-3">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> <?php echo htmlspecialchars($pgT['back']); ?>
    </a>

    <!-- Hero Banner -->
    <div class="hero-banner mb-4 mt-3">
        <h1 class="fw-extrabold fs-1 mb-2"><?php echo $pgT['hero_title']; ?></h1>
        <p class="mb-0 opacity-90 fs-6"><?php echo $pgT['hero_subtitle']; ?></p>
    </div>

    <!-- Character Interactive Section -->
    <div class="app-card">
        <div class="text-center mb-3">
            <h2 class="fs-4 fw-bold mb-1"><?php echo $pgT['section_title']; ?></h2>
            <p class="text-muted small mb-0"><?php echo $pgT['section_subtitle']; ?></p>
        </div>

        <!-- Tabs -->
        <div class="pg-tabs" role="tablist">
            <button type="button" class="pg-tab" role="tab" aria-selected="true"  data-pgtab="basic"><?php echo $pgT['basic_tab']; ?></button>
            <button type="button" class="pg-tab" role="tab" aria-selected="false" data-pgtab="dakuten"><?php echo $pgT['dakuten_tab']; ?></button>
            <button type="button" class="pg-tab" role="tab" aria-selected="false" data-pgtab="combo"><?php echo $pgT['combo_tab']; ?></button>
        </div>

        <!-- Basic -->
        <div class="pg-pane is-active" id="pg-basic" role="tabpanel">
            <div class="kana-wrap">
                <table class="kana-table">
                    <thead><tr><?php echo pg_cells($pgVowels, 'th'); ?></tr></thead>
                    <tbody><?php echo pg_rows($pgBasic); ?></tbody>
                </table>
            </div>
        </div>

        <!-- Dakuten -->
        <div class="pg-pane" id="pg-dakuten" role="tabpanel">
            <div class="kana-wrap">
                <table class="kana-table">
                    <thead><tr><th class="sect" colspan="5"><?php echo $pgT['dakuten_title']; ?></th></tr></thead>
                    <tbody><?php echo pg_rows($pgDakuten); ?></tbody>
                    <thead><tr><th class="sect" colspan="5"><?php echo $pgT['handakuten_title']; ?></th></tr></thead>
                    <tbody><?php echo pg_rows($pgHandakuten); ?></tbody>
                </table>
            </div>
        </div>

        <!-- Combos -->
        <div class="pg-pane" id="pg-combo" role="tabpanel">
            <div class="kana-wrap">
                <table class="kana-table">
                    <thead><tr><th class="sect" colspan="3"><?php echo $pgT['combo_title']; ?></th></tr></thead>
                    <tbody><?php echo pg_rows($pgCombo); ?></tbody>
                </table>
            </div>
            <div class="kana-wrap">
                <table class="kana-table">
                    <thead><tr><th class="sect" colspan="3"><?php echo $pgT['voiced_combo_title']; ?></th></tr></thead>
                    <tbody><?php echo pg_rows($pgVoicedCombo); ?></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Practice -->
    <div class="app-card">
        <div class="text-center mb-3">
            <h2 class="fs-5 fw-bold mb-1"><?php echo $pgT['quick_title']; ?></h2>
            <p class="text-muted small mb-0"><?php echo $pgT['quick_subtitle']; ?></p>
        </div>
        <div class="quick-grid">
            <?php foreach (explode(',', $pgQuick) as $pair):
                [$qc, $qr] = explode(':', $pair, 2); ?>
                <div class="quick-char" data-say="<?php echo htmlspecialchars($qc); ?>" tabindex="0" role="button" aria-label="<?php echo htmlspecialchars($qc . ' ' . $qr); ?>">
                    <span class="quick-char-main"><?php echo htmlspecialchars($qc); ?></span>
                    <span class="quick-char-rom"><?php echo htmlspecialchars($qr); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Study Tips -->
    <div class="app-card">
        <h3 class="fs-5 fw-bold text-center mb-4"><?php echo $pgT['tips_title']; ?></h3>
        <div class="row g-3">
            <?php for ($i = 1; $i <= 4; $i++): ?>
                <div class="col-12 col-md-6">
                    <div class="tip-box">
                        <h4 class="fs-6 fw-bold pg-brand mb-1"><?php echo $pgT["tip{$i}_title"]; ?></h4>
                        <p class="small text-muted mb-0"><?php echo $pgT["tip{$i}_text"]; ?></p>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Actions -->
    <div class="pg-actions d-flex flex-wrap align-items-center justify-content-center gap-3 my-4 py-2">
        <a href="hiraganagame.php" class="btn-brand-outline">
            <i class="fa-solid fa-gamepad" aria-hidden="true"></i> <?php echo $pgT['game_btn']; ?>
        </a>
        <a href="startquizhiragana.php" class="btn-brand">
            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> <?php echo $pgT['quiz_btn']; ?>
        </a>
        <button type="button" class="btn-brand-outline" id="pgWriteBtn">
            <i class="fa-solid fa-pencil" aria-hidden="true"></i> <?php echo $pgT['writing_btn']; ?>
        </button>
    </div>

</div>

<script>
(function () {
    // ----- Tabs -----
    var tabs  = document.querySelectorAll('.pg-tab');
    var panes = document.querySelectorAll('.pg-pane');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.setAttribute('aria-selected', t === tab ? 'true' : 'false'); });
            panes.forEach(function (p) { p.classList.toggle('is-active', p.id === 'pg-' + tab.getAttribute('data-pgtab')); });
            if (window.innerWidth < 576 && tab.scrollIntoView) {
                tab.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' });
            }
        });
    });

    // ----- Speech (one delegated listener for every kana cell) -----
    function speak(text, rate, el) {
        if (el) {
            el.classList.add('is-playing');
            setTimeout(function () { el.classList.remove('is-playing'); }, 350);
        }
        if (!('speechSynthesis' in window)) return;
        var u = new SpeechSynthesisUtterance(text);
        u.lang = 'ja-JP';
        u.rate = rate || 0.7;
        u.pitch = 1.0;
        window.speechSynthesis.cancel();
        window.speechSynthesis.speak(u);
    }

    function handle(e) {
        var el = e.target.closest('[data-say]');
        if (!el) return;
        if (e.type === 'keydown') {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            e.preventDefault();
        }
        speak(el.getAttribute('data-say'), parseFloat(el.getAttribute('data-rate')) || 0.7, el);
    }
    document.addEventListener('click', handle);
    document.addEventListener('keydown', handle);

    // ----- Writing practice message -----
    var wb = document.getElementById('pgWriteBtn');
    if (wb) wb.addEventListener('click', function () {
        alert(<?php echo json_encode($pgWritingMsg, JSON_UNESCAPED_UNICODE); ?>);
    });
})();
</script>
<script src="tracking.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>