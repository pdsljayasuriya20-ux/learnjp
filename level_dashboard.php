<?php
// ---------------------------------------------------------------
// level_dashboard.php — shared dashboard for JLPT N5 / N4 / N3 / N2 / N1.
// Do not open directly. n5.php ... n1.php set the variables below
// and then require this file.
//
// Wrapper must set:  $dLevel ('N5'..'N1'), $dHero, $dSub, $dLinks (array)
// ($pageTitle / $pageDescription / $pageKeywords go before the require too)
//
// $dLinks keys: hiragana, katakana, kanji, grammar, listening, reading,
//               vocabulary, pastpapers (NEW), driving
//   - a key that is missing falls back to 'comingsoon.php'
// ---------------------------------------------------------------
if (empty($dLevel)) { http_response_code(404); exit; }

$dBooksUrl = 'books.php';           // printed books page
$dUpgrade  = 'upgrade.php';         // packages page
$dBase = 4000; $dStep = 1500;       // must match upgrade.php

require_once __DIR__ . '/includes/header.php';

$pt = [
    'study_categories' => 'අධ්‍යයන කාණ්ඩ',
    'jlpt_levels'      => 'JLPT මට්ටම්',
    'choose_level'     => 'ඔබට අවශ්‍ය ප්‍රවීණතා මට්ටම තෝරන්න',
    'hiragana'         => 'හිරගනා',
    'katakana'         => 'කතකනා',
    'kanji'            => 'කන්ජි',
    'grammar'          => 'ව්‍යාකරණ',
    'listening'        => 'අසන්න',
    'reading'          => 'කියවන්න',
    'vocabulary'       => 'වචන මාලාව',
    'pastpapers'       => 'ප්‍රශ්න පත්‍ර',          // NEW: past papers tile title
    'driving'          => 'රියැදුරු විභාගය',
    'beginner'         => 'ආරම්භක',
    'elementary'       => 'මූලික',
    'intermediate'     => 'මධ්‍යම',
    'upper_int'        => 'ඉහළ මධ්‍යම',
    'advanced'         => 'උසස්',
];

$levels = [
    'N5' => ['url' => 'n5.php', 'desc' => 'beginner'],
    'N4' => ['url' => 'n4.php', 'desc' => 'elementary'],
    'N3' => ['url' => 'n3.php', 'desc' => 'intermediate'],
    'N2' => ['url' => 'n2.php', 'desc' => 'upper_int'],
    'N1' => ['url' => 'n1.php', 'desc' => 'advanced'],
];

// Package price of THIS level (same formula as upgrade.php)
$dIdx   = array_search($dLevel, array_keys($levels), true);
$dPrice = $dBase + (int)$dIdx * $dStep;

// Category list. Hiragana + Katakana only on N5.
$catItems = [];
if ($dLevel === 'N5') {
    $catItems[] = ['link' => $dLinks['hiragana'] ?? 'hiragana.php', 'icon' => 'あ', 'key' => 'hiragana'];
    $catItems[] = ['link' => $dLinks['katakana'] ?? 'katakana.php', 'icon' => 'ア', 'key' => 'katakana'];
}
$catItems[] = ['link' => $dLinks['kanji']      ?? 'comingsoon.php', 'icon' => '漢', 'key' => 'kanji'];
$catItems[] = ['link' => $dLinks['grammar']    ?? 'comingsoon.php', 'icon' => '文', 'key' => 'grammar'];
$catItems[] = ['link' => $dLinks['listening']  ?? 'comingsoon.php', 'icon' => '🎧', 'key' => 'listening'];
$catItems[] = ['link' => $dLinks['reading']    ?? 'comingsoon.php', 'icon' => '読', 'key' => 'reading'];
$catItems[] = ['link' => $dLinks['vocabulary'] ?? 'comingsoon.php', 'icon' => '語', 'key' => 'vocabulary'];
$catItems[] = ['link' => $dLinks['pastpapers'] ?? 'comingsoon.php', 'icon' => '📝', 'key' => 'pastpapers'];   // NEW tile
$catItems[] = ['link' => $dLinks['driving']    ?? 'driving.php',    'icon' => '🚗', 'key' => 'driving'];
?>

<!-- =====================================================
     NEUMORPHISM THEME (mobile-first, responsive)
     ===================================================== -->
<style>
    :root {
        --nm-bg: #e6e9f2; --nm-light: #ffffff; --nm-dark: #c3c9dc;
        --nm-text: #2d3250; --nm-muted: #7a8099;
        --nm-brand: #4f46e5; --nm-brand-2: #7c6cf5; --nm-gold: #f59e0b;
        --nm-radius: 24px; --nm-radius-sm: 16px;
        --nm-raised: 8px 8px 18px var(--nm-dark), -8px -8px 18px var(--nm-light);
        --nm-raised-sm: 5px 5px 11px var(--nm-dark), -5px -5px 11px var(--nm-light);
        --nm-inset: inset 5px 5px 11px var(--nm-dark), inset -5px -5px 11px var(--nm-light);
        --nm-inset-sm: inset 3px 3px 7px var(--nm-dark), inset -3px -3px 7px var(--nm-light);
    }
    body { background: var(--nm-bg) !important; color: var(--nm-text); -webkit-tap-highlight-color: transparent; }
    .text-muted { color: var(--nm-muted) !important; }

    @keyframes nmFadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
    .hero-banner, .app-card, .cat-col, .shop-card { animation: nmFadeUp .55s ease both; }
    .cat-col:nth-child(1) { animation-delay: .04s; } .cat-col:nth-child(2) { animation-delay: .08s; }
    .cat-col:nth-child(3) { animation-delay: .12s; } .cat-col:nth-child(4) { animation-delay: .16s; }
    .cat-col:nth-child(5) { animation-delay: .20s; } .cat-col:nth-child(6) { animation-delay: .24s; }
    .cat-col:nth-child(7) { animation-delay: .28s; } .cat-col:nth-child(8) { animation-delay: .32s; }
    .cat-col:nth-child(9) { animation-delay: .36s; } .cat-col:nth-child(10) { animation-delay: .40s; }   /* N5 has 10 tiles now */
    @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }

    .hero-banner { background: linear-gradient(135deg, var(--nm-brand) 0%, var(--nm-brand-2) 100%); color: #fff; border-radius: var(--nm-radius); padding: 28px 22px; box-shadow: var(--nm-raised); position: relative; overflow: hidden; }
    .hero-banner::before, .hero-banner::after { content: ""; position: absolute; border-radius: 50%; background: rgba(255, 255, 255, .12); }
    .hero-banner::before { width: 140px; height: 140px; top: -50px; right: -30px; }
    .hero-banner::after  { width: 90px;  height: 90px;  bottom: -35px; left: 12%; }
    .hero-banner > * { position: relative; z-index: 1; }

    .app-card { background: var(--nm-bg); border-radius: var(--nm-radius); box-shadow: var(--nm-raised); padding: 22px 18px; border: 0; }
    .app-card h2 { color: var(--nm-text); }

    .cat-card { background: var(--nm-bg); border: 0; border-radius: var(--nm-radius-sm); box-shadow: var(--nm-raised-sm); padding: 1.1rem 0.5rem; display: flex; flex-direction: column; align-items: center; justify-content: center; text-decoration: none; color: var(--nm-text); transition: box-shadow .25s ease, transform .25s ease; height: 100%; min-height: 96px; }
    .cat-card:hover  { transform: translateY(-3px); color: var(--nm-text); }
    .cat-card:active { box-shadow: var(--nm-inset-sm); transform: scale(.97); }
    .cat-icon { font-size: 1.6rem; font-weight: 800; margin-bottom: 0.4rem; line-height: 1; background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent; }
    .cat-icon.is-emoji { background: none; -webkit-text-fill-color: initial; color: inherit; }
    .cat-title { font-size: 0.85rem; font-weight: 700; text-align: center; color: var(--nm-text); }

    /* ---------- Books + Packages ---------- */
    .shop-card { display: flex; align-items: center; gap: 14px; background: var(--nm-bg); border-radius: var(--nm-radius-sm); box-shadow: var(--nm-raised-sm); padding: 16px 16px; text-decoration: none; color: var(--nm-text); height: 100%; transition: box-shadow .25s ease, transform .25s ease; }
    .shop-card:hover  { transform: translateY(-3px); color: var(--nm-text); }
    .shop-card:active { box-shadow: var(--nm-inset-sm); transform: scale(.98); }
    .shop-ic { flex: 0 0 52px; height: 52px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: #fff; background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); box-shadow: var(--nm-raised-sm); }
    .shop-card.is-gold .shop-ic { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
    .shop-t { font-weight: 800; font-size: .95rem; line-height: 1.3; }
    .shop-s { font-size: .78rem; color: var(--nm-muted); font-weight: 600; margin-top: 2px; }
    .shop-go { margin-left: auto; color: var(--nm-brand); }

    /* ---------- JLPT grid ---------- */
    .jlpt-grid { display: grid; gap: 14px; grid-template-columns: repeat(3, 1fr); }
    @media (min-width: 420px) { .jlpt-grid { grid-template-columns: repeat(5, 1fr); } }
    .jlpt-btn { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; padding: 14px 4px; background: var(--nm-bg); border: 0; border-radius: var(--nm-radius-sm); box-shadow: var(--nm-raised-sm); text-decoration: none; color: var(--nm-text); transition: box-shadow .2s ease, transform .2s ease; min-height: 78px; }
    .jlpt-btn:hover  { color: var(--nm-text); transform: translateY(-2px); }
    .jlpt-btn:active { box-shadow: var(--nm-inset-sm); transform: scale(.97); }
    .jlpt-btn .tag { font-weight: 800; font-size: 1.15rem; color: var(--nm-brand); }
    .jlpt-btn .desc { font-size: .66rem; color: var(--nm-muted); text-align: center; line-height: 1.2; }
    .jlpt-btn.active { box-shadow: var(--nm-inset-sm); transform: none; }
    .jlpt-btn.active .tag  { color: var(--nm-brand) !important; }
    .jlpt-btn.active .desc { color: var(--nm-brand) !important; font-weight: 700; }

    @media (max-width: 575.98px) {
        .hero-banner { padding: 22px 18px; border-radius: 20px; }
        .hero-banner h1 { font-size: 1.35rem !important; }
        .app-card { padding: 18px 14px; border-radius: 20px; }
    }
</style>

<div class="hero-banner mb-4">
    <h1 class="fw-extrabold fs-2 mb-2"><?php echo htmlspecialchars($dHero); ?></h1>
    <p class="mb-0 opacity-90 fs-6"><?php echo htmlspecialchars($dSub); ?></p>
</div>

<!-- Categories Section -->
<div class="app-card mb-4">
    <div class="d-flex align-items-center gap-2 mb-3">
        <span class="fs-4" aria-hidden="true">📖</span>
        <h2 class="fs-5 fw-bold mb-0"><?php echo $pt['study_categories']; ?></h2>
    </div>

    <div class="row g-3">
        <?php foreach ($catItems as $item):
            $isEmoji = preg_match('/[\x{1F300}-\x{1FAFF}]/u', $item['icon']);
        ?>
            <div class="col-6 col-sm-4 col-lg-3 cat-col">
                <a href="<?php echo htmlspecialchars($item['link']); ?>" class="cat-card">
                    <div class="cat-icon<?php echo $isEmoji ? ' is-emoji' : ''; ?>" aria-hidden="true"><?php echo $item['icon']; ?></div>
                    <div class="cat-title"><?php echo htmlspecialchars($pt[$item['key']]); ?></div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Printed books + Package prices -->
<div class="app-card mb-4">
    <div class="d-flex align-items-center gap-2 mb-3">
        <span class="fs-4" aria-hidden="true">🛒</span>
        <h2 class="fs-5 fw-bold mb-0">පොත් සහ Packages</h2>
    </div>
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <a href="<?php echo htmlspecialchars($dBooksUrl); ?>" class="shop-card">
                <span class="shop-ic" aria-hidden="true"><i class="fa-solid fa-book"></i></span>
                <span>
                    <div class="shop-t">මුද්‍රිත පොත් මිල දී ගන්න</div>
                    <div class="shop-s"><?php echo htmlspecialchars($dLevel); ?> මුද්‍රිත පොත් බලන්න</div>
                </span>
                <i class="fa-solid fa-chevron-right shop-go" aria-hidden="true"></i>
            </a>
        </div>
        <div class="col-12 col-md-6">
            <a href="<?php echo htmlspecialchars($dUpgrade . '?level=' . $dLevel); ?>" class="shop-card is-gold">
                <span class="shop-ic" aria-hidden="true"><i class="fa-solid fa-crown"></i></span>
                <span>
                    <div class="shop-t"><?php echo htmlspecialchars($dLevel); ?> Package මිල ගණන්</div>
                    <div class="shop-s">කංජි + ව්‍යාකරණ සම්පූර්ණයෙන් · ¥<?php echo number_format($dPrice); ?> සිට</div>
                </span>
                <i class="fa-solid fa-chevron-right shop-go" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</div>

<!-- Level Selector Section -->
<div class="app-card mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center gap-2">
            <span class="fs-4" aria-hidden="true">🎯</span>
            <h2 class="fs-5 fw-bold mb-0"><?php echo $pt['jlpt_levels']; ?></h2>
        </div>
        <span class="small text-muted d-none d-sm-inline"><?php echo $pt['choose_level']; ?></span>
    </div>

    <div class="jlpt-grid">
        <?php foreach ($levels as $code => $lv): $isActive = ($code === $dLevel); ?>
            <a href="<?php echo htmlspecialchars($lv['url']); ?>" class="jlpt-btn<?php echo $isActive ? ' active' : ''; ?>"<?php echo $isActive ? ' aria-current="page"' : ''; ?>>
                <span class="tag"><?php echo $code; ?></span>
                <span class="desc"><?php echo $pt[$lv['desc']]; ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
