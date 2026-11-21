<?php
/**
 * w-grammar.php
 *   /w-grammar.php                     -> list of the cards (first level that has cards)
 *   /w-grammar.php?level=N2            -> list for one level
 *   /w-grammar.php?level=N2&all=1      -> every card of the level on one page (free ones full, rest locked)
 *   add &bare=1                        -> plain page without header/footer and WITHOUT copy protection
 *                                         (for printing / copying to Word) — ADMIN ONLY, see $isAdmin below
 * Single card page: w-grammar_card.php?slug=...
 *
 * THEME:  neumorphism (same as kanji / dashboard pages).
 * ACCESS: first GC_FREE_LIMIT cards of every level are free for everybody.
 *         A user with an active package for that level (table user_packages) sees all cards.
 *         Locked cards never send their content to the browser.
 * PROTECT: watermark + copy / print deterrent via includes/content_protect.php
 *
 * SEO: $pageTitle / $pageDescription / $pageKeywords are picked up by includes/header.php.
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/includes/w-grammar-render.php';
// watermark/copy protection; if the include is missing the page still loads (protection just stays off)
$__cp = __DIR__ . '/includes/content_protect.php';
if (is_file($__cp)) {
    require_once $__cp;
} else {
    error_log('content_protect.php not found in /includes — protection disabled');
    function cp_render_assets(): string { return ''; }
    function cp_open_area(): string { return ''; }
    function cp_close_area(): string { return ''; }
}

// ----- settings -----
$wgFree     = defined('GC_FREE_LIMIT') ? (int)GC_FREE_LIMIT : 30;
$wgBooksUrl = 'books.php';
// Who may use ?bare=1 (no header, no protection). Change this to match your admin session key.
$isAdmin    = !empty($_SESSION['is_admin']);
// --------------------

$levels    = grammar_levels();
$available = array_column($levels, 'level');
$level     = strtoupper((string)($_GET['level'] ?? ''));
if (!in_array($level, $available, true)) $level = $available[0] ?? 'N2';
$all  = !empty($_GET['all']);
$bare = !empty($_GET['bare']) && $isAdmin;
$q    = $bare ? '&bare=1' : '';

$wgUpgrade = 'upgrade.php?level=' . urlencode($level);

/** active package for $level (or 'ALL')? — same rule as the kanji / grammar level pages */
if (!function_exists('gl_has_package')) {
    function gl_has_package(string $lvl): bool {
        if (empty($_SESSION['user_id']) || !function_exists('getDB')) { return false; }
        try {
            $st = getDB()->prepare(
                "SELECT 1 FROM user_packages
                  WHERE user_id = ? AND level_code IN (?, 'ALL')
                    AND (expires_at IS NULL OR expires_at > NOW())
                  LIMIT 1");
            $st->execute([(int)$_SESSION['user_id'], $lvl]);
            return (bool)$st->fetchColumn();
        } catch (Throwable $e) {
            error_log('gl_has_package: ' . $e->getMessage());
            return false;
        }
    }
}
$wgHasPkg    = gl_has_package($level);
$wgOpenLimit = $wgHasPkg ? PHP_INT_MAX : $wgFree;

$pageTitle       = 'JLPT ' . $level . ' ව්‍යාකරණ සිංහලෙන් | ලංකාවේ සහ ජපානයේ ඉන්න ශ්‍රී ලාංකිකයන් සඳහා';
$pageDescription = 'JLPT ' . $level . ' ව්‍යාකරණ සියල්ල සිංහලෙන් — පිහිටුම (formation), උදාහරණ වාක්‍ය සහ විභාග ඉඟි සමඟ. ලංකාවේ ඉඳන් ඉගෙන ගන්නවා නම් හෝ ජපානයේ ජීවත් වෙනවා නම්, JLPT ' . $level . ' සමත් වෙන්න මෙතනින් පුහුණු වෙන්න.';
$pageKeywords    = 'JLPT ' . $level . ' grammar sinhala, JLPT ' . $level . ' vyakarana sinhala, JLPT ' . $level . ' grammar cards, jlpt grammar sri lanka, sri lankans in japan jlpt, japan wala inna sri lankikayo, ' . $level . ' bunpou sinhala';

$__hdr = $bare ? null : grammar_find_include('header.php');
$__ftr = $bare ? null : grammar_find_include('footer.php');
if ($__hdr) { require $__hdr; } else { echo grammar_standalone_open($pageTitle); }

echo grammar_render_assets();
echo grammar_render_lock_assets();
if (!$bare) {
    echo grammar_render_protection($bare);
    echo cp_render_assets();
}
?>
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
    .text-muted { color: var(--nm-muted) !important; }
    .jp { font-family: 'Noto Sans JP', sans-serif; }

    @keyframes nmFadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
    @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }

    .wg-wrap { padding-bottom: 1.5rem; }

    .back-btn { display: inline-flex; align-items: center; gap: 6px; background: var(--nm-bg); color: var(--nm-muted); border-radius: 999px; padding: 9px 18px; font-size: .85rem; font-weight: 700; text-decoration: none; box-shadow: var(--nm-raised-sm); transition: box-shadow .2s ease, color .2s ease; }
    .back-btn:hover { color: var(--nm-brand); }
    .back-btn:active { box-shadow: var(--nm-inset-sm); }

    .hero-banner { background: linear-gradient(135deg, var(--nm-brand) 0%, var(--nm-brand-2) 100%); border-radius: var(--nm-radius); color: #fff; padding: 2.2rem 1.5rem; text-align: center; box-shadow: var(--nm-raised); position: relative; overflow: hidden; animation: nmFadeUp .5s ease both; }
    .hero-banner::before, .hero-banner::after { content: ""; position: absolute; border-radius: 50%; background: rgba(255,255,255,.12); }
    .hero-banner::before { width: 150px; height: 150px; top: -55px; right: -30px; }
    .hero-banner::after  { width: 100px; height: 100px; bottom: -40px; left: 10%; }
    .hero-banner > * { position: relative; z-index: 1; }
    .hero-btn { display: inline-flex; align-items: center; gap: 8px; margin: 16px 6px 0; background: var(--nm-bg); color: var(--nm-brand); text-decoration: none; font-weight: 800; font-size: .92rem; padding: 11px 22px; border-radius: 999px; box-shadow: 5px 5px 12px rgba(30,20,120,.35), -3px -3px 9px rgba(255,255,255,.25); transition: transform .2s ease, box-shadow .2s ease; }
    .hero-btn:hover { color: var(--nm-brand); transform: translateY(-2px); }
    .hero-btn:active { box-shadow: inset 3px 3px 7px var(--nm-dark), inset -3px -3px 7px var(--nm-light); transform: scale(.97); }

    .stat-card { background: var(--nm-bg); border-radius: var(--nm-radius-sm); box-shadow: var(--nm-raised-sm); padding: 14px 6px; text-align: center; height: 100%; animation: nmFadeUp .55s ease both; }
    .stat-card .num { font-size: 1.45rem; font-weight: 800; line-height: 1.2; background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent; }
    .stat-card .small { font-size: .72rem; }

    .app-card { background: var(--nm-bg); border-radius: var(--nm-radius); padding: 1.3rem 1.1rem; border: 0; box-shadow: var(--nm-raised); margin-bottom: 1.5rem; animation: nmFadeUp .55s ease both; }

    .wg-tabs { display: flex; gap: 10px; overflow-x: auto; padding: 4px 6px 12px; margin: 0 -6px; scrollbar-width: none; -webkit-overflow-scrolling: touch; }
    .wg-tabs::-webkit-scrollbar { display: none; }
    .wg-tab { flex: 0 0 auto; display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 8px 16px; font-size: .85rem; font-weight: 800; color: var(--nm-muted); background: var(--nm-bg); box-shadow: var(--nm-raised-sm); text-decoration: none; transition: box-shadow .2s ease, color .2s ease; }
    .wg-tab small { font-size: .68rem; font-weight: 700; opacity: .75; }
    .wg-tab:hover { color: var(--nm-brand); }
    .wg-tab.active { color: var(--nm-brand); box-shadow: var(--nm-inset-sm); }

    .wg-search { display: flex; align-items: center; gap: 10px; background: var(--nm-bg); border-radius: 999px; box-shadow: var(--nm-inset); padding: 4px 18px; }
    .wg-search i { color: var(--nm-muted); }
    .wg-search input { flex: 1; min-width: 0; border: 0; outline: 0; background: transparent; color: var(--nm-text); font-size: .95rem; padding: 11px 0; font-family: inherit; }

    .wg-guest { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; background: var(--nm-bg); border-radius: var(--nm-radius-sm); box-shadow: var(--nm-inset-sm); padding: 14px 16px; border-left: 5px solid var(--nm-gold); }
    .wg-guest .ic { font-size: 1.5rem; }
    .wg-guest .tx { flex: 1 1 220px; font-size: .88rem; font-weight: 600; }

    .wg-col { animation: nmFadeUp .45s ease both; }
    .wg-card { display: flex; align-items: center; gap: 14px; width: 100%; height: 100%; text-decoration: none; color: var(--nm-text); background: var(--nm-bg); border: 0; border-radius: var(--nm-radius-sm); box-shadow: var(--nm-raised-sm); padding: 14px 16px; text-align: left; font-family: inherit; cursor: pointer; transition: box-shadow .2s ease, transform .2s ease; }
    .wg-card:hover  { transform: translateY(-3px); color: var(--nm-text); }
    .wg-card:active { box-shadow: var(--nm-inset-sm); transform: scale(.98); }
    .wg-card:focus-visible { outline: 3px solid var(--nm-brand-2); outline-offset: 2px; }
    .wg-no { flex: 0 0 auto; min-width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .85rem; color: var(--nm-brand); background: var(--nm-bg); box-shadow: var(--nm-inset-sm); }
    .wg-body { min-width: 0; flex: 1; }
    .wg-title { font-family: 'Noto Sans JP', sans-serif; font-weight: 800; font-size: 1.05rem; color: var(--nm-brand); line-height: 1.3; word-break: break-word; }
    .wg-formula { font-size: .76rem; color: var(--nm-muted); font-weight: 700; line-height: 1.4; margin-top: 2px; word-break: break-word; }
    .wg-en { font-size: .84rem; font-weight: 700; margin-top: 6px; line-height: 1.35; }
    .wg-si { font-family: 'Noto Sans Sinhala', sans-serif; font-size: .84rem; font-weight: 600; margin-top: 2px; line-height: 1.45; }
    .wg-go { flex: 0 0 auto; color: var(--nm-muted); }
    .wg-card.is-locked .wg-no { color: var(--nm-muted); }
    .wg-lock-t { font-weight: 800; font-size: .88rem; color: var(--nm-muted); }
    .wg-lock-b { display: inline-block; margin-top: 6px; font-size: .72rem; font-weight: 800; color: var(--nm-brand); background: var(--nm-bg); box-shadow: var(--nm-raised-sm); border-radius: 999px; padding: 5px 12px; }

    .wg-empty { display: none; text-align: center; padding: 2rem 1rem; color: var(--nm-muted); font-weight: 600; }

    .btn-brand, .btn-brand-outline { border-radius: 999px; font-weight: 800; border: 0; text-decoration: none; cursor: pointer; font-family: inherit; font-size: 1rem; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: box-shadow .2s ease, transform .2s ease; }
    .btn-brand { background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); color: #fff; padding: 13px 26px; box-shadow: var(--nm-raised-sm); }
    .btn-brand:hover { color: #fff; transform: translateY(-2px); }
    .btn-brand-outline { background: var(--nm-bg); color: var(--nm-brand); padding: 13px 24px; box-shadow: var(--nm-raised-sm); }
    .btn-brand-outline:hover { color: var(--nm-brand); transform: translateY(-2px); }
    .btn-brand:active, .btn-brand-outline:active { box-shadow: var(--nm-inset-sm); transform: scale(.97); }
    .btn-sm-nm { padding: 9px 18px; font-size: .88rem; }

    /* "all cards on one page" view — soft neumorphic frame around the cards from w-grammar-render.php */
    .wg-all { background: var(--nm-bg); border-radius: var(--nm-radius); box-shadow: var(--nm-raised); padding: 1rem; margin-bottom: 1.5rem; }
    .wg-all .gc-wrap, .wg-all .gc-scroll { background: transparent; box-shadow: none; border: 0; }

    @media (max-width: 575.98px) {
        .app-card { padding: 1.1rem .85rem; border-radius: 20px; }
        .hero-banner { padding: 1.7rem 1.1rem; border-radius: 20px; }
        .hero-banner h1 { font-size: 1.7rem !important; }
        .hero-banner p  { font-size: .9rem !important; }
        .stat-card .num { font-size: 1.25rem; }
        .wg-all { padding: .6rem; border-radius: 20px; }
        .wg-actions .btn-brand, .wg-actions .btn-brand-outline { width: 100%; }
    }
</style>

<div class="wg-wrap">

<?php if ($all):
    $cardsFull  = $available ? grammar_get_cards_full($level) : [];
    $totalCount = count($cardsFull);
    $openCount  = min($totalCount, $wgOpenLimit);
    $lockedCnt  = $totalCount - $openCount;
?>
    <a href="w-grammar.php?level=<?= grammar_h($level) . $q ?>" class="back-btn mb-3">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> ලැයිස්තුවට (Back to list)
    </a>

    <div class="hero-banner mb-4 mt-3">
        <h1 class="fw-bold fs-2 mb-2">🎌 JLPT <?= grammar_h($level) ?> ව්‍යාකරණ</h1>
        <p class="mb-0 opacity-90 fs-6">සියලු කාඩ්පත් එක පිටුවක — <?= (int)$totalCount ?></p>
    </div>

    <?php if ($lockedCnt > 0): ?>
        <div class="wg-guest mb-4">
            <span class="ic" aria-hidden="true">🔒</span>
            <div class="tx">පළමු කාඩ්පත් <?= (int)$openCount ?> නොමිලේ. ඉතිරි <?= (int)$lockedCnt ?> බැලීමට <?= grammar_h($level) ?> Package එකට Upgrade කරන්න.</div>
            <a href="<?= grammar_h($wgUpgrade) ?>" class="btn-brand btn-sm-nm"><i class="fa-solid fa-crown" aria-hidden="true"></i> Upgrade</a>
        </div>
    <?php endif; ?>

    <?= $bare ? '' : cp_open_area() ?>
    <div class="wg-all">
    <div id="gc-wrap" class="gc-wrap">
      <?php if ($bare) { echo grammar_render_toolbar(); } /* the toolbar (print / copy tools) is for admins only */ ?>

      <div class="gc-scroll">
<?php foreach ($cardsFull as $i => $card): ?>
      <?= $i < $wgOpenLimit ? grammar_render_card($card) : grammar_render_locked_card($card) ?>
<?php endforeach; ?>
      </div>

      <?php if ($lockedCnt > 0) { echo grammar_render_upsell_banner($lockedCnt); } ?>
    </div>
    </div>
    <?= $bare ? '' : cp_close_area() ?>

<?php else:
    $cards      = $available ? grammar_list_cards($level) : [];
    $totalCount = count($cards);
    $openCount  = min($totalCount, $wgOpenLimit);
    $lockedCnt  = $totalCount - $openCount;
?>
    <a href="<?= grammar_h(strtolower($level)) ?>.php" class="back-btn mb-3">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> ආපසු Dashboard වෙත
    </a>

    <div class="hero-banner mb-4 mt-3">
        <h1 class="fw-bold fs-2 mb-2">🎌 JLPT <?= grammar_h($level) ?> ව්‍යාකරණ</h1>
        <p class="mb-0 opacity-90 fs-6">ව්‍යාකරණ කාඩ්පත් <?= (int)$totalCount ?> — පිහිටුම, තේරුම සහ උදාහරණ වාක්‍ය සිංහලෙන්</p>
        <?php if ($cards): ?>
            <a class="hero-btn" href="w-grammar.php?level=<?= grammar_h($level) ?>&amp;all=1<?= $q ?>"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> සියලු කාඩ්පත් එකවර බලන්න</a>
        <?php endif; ?>
        <?php if ($lockedCnt > 0): ?>
            <a class="hero-btn" href="<?= grammar_h($wgUpgrade) ?>"><i class="fa-solid fa-crown" aria-hidden="true"></i> Upgrade</a>
        <?php else: ?>
            <a class="hero-btn" href="<?= grammar_h($wgBooksUrl) ?>"><i class="fa-solid fa-book" aria-hidden="true"></i> මුද්‍රිත පොත</a>
        <?php endif; ?>
    </div>

<?php if (!$cards): ?>
    <div class="app-card text-center"><p class="mb-0 fw-bold text-muted">තවම කාඩ්පත් නැත. (No grammar cards yet.)</p></div>
<?php else: ?>

    <div class="row g-2 g-md-3 mb-4">
        <div class="col-6 col-md-3"><div class="stat-card"><div class="num"><?= (int)$totalCount ?></div><div class="small text-muted">මුළු කාඩ්පත්</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="num" id="wgShown"><?= (int)$totalCount ?></div><div class="small text-muted">පෙන්වන ගණන</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="num"><?= (int)min($wgFree, $totalCount) ?></div><div class="small text-muted">නොමිලේ බැලිය හැක</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="num"><?= $wgHasPkg ? '✓' : (int)$openCount ?></div><div class="small text-muted"><?= $wgHasPkg ? 'සියල්ල විවෘතයි' : 'ඔබට විවෘත' ?></div></div></div>
    </div>

    <?php if ($lockedCnt > 0): ?>
        <div class="wg-guest mb-4">
            <span class="ic" aria-hidden="true">🔒</span>
            <div class="tx">පළමු කාඩ්පත් <?= (int)$wgFree ?> සියලු දෙනාට නොමිලේ. ඉතිරි කාඩ්පත් <?= (int)$lockedCnt ?> බැලීමට <?= grammar_h($level) ?> Package එකට Upgrade කරන්න.</div>
            <a href="<?= grammar_h($wgUpgrade) ?>" class="btn-brand btn-sm-nm"><i class="fa-solid fa-crown" aria-hidden="true"></i> Upgrade</a>
        </div>
    <?php endif; ?>

    <div class="app-card">
        <div class="wg-tabs" role="navigation" aria-label="JLPT මට්ටම්">
<?php foreach ($levels as $lv): ?>
            <a class="wg-tab<?= $lv['level'] === $level ? ' active' : '' ?>" href="w-grammar.php?level=<?= grammar_h($lv['level']) . $q ?>"<?= $lv['level'] === $level ? ' aria-current="page"' : '' ?>><?= grammar_h($lv['level']) ?><small><?= (int)$lv['total'] ?></small></a>
<?php endforeach; ?>
        </div>
        <label class="wg-search" for="wgQ">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" id="wgQ" placeholder="ව්‍යාකරණය, තේරුම හෝ පිහිටුම සොයන්න..." autocomplete="off">
        </label>
        <?php if ($lockedCnt > 0): ?>
            <p class="small text-muted mb-0 mt-2 px-1" id="wgNote" style="display:none">සෙවීම ඔබට විවෘත කාඩ්පත් අතර පමණි. සියල්ල සෙවීමට Package එක Upgrade කරන්න.</p>
        <?php endif; ?>
    </div>

    <?= $bare ? '' : cp_open_area() ?>
    <div class="row g-3 mb-4" id="wgGrid">
<?php foreach ($cards as $i => $c): ?>
<?php if ($i >= $wgOpenLimit): ?>
        <div class="col-12 col-md-6 wg-col" data-locked="1" data-search="">
            <a class="wg-card is-locked" href="<?= grammar_h($wgUpgrade) ?>" aria-label="කාඩ්පත <?= sprintf('%02d', (int)$c['card_no']) ?> — Upgrade අවශ්‍යයි">
                <span class="wg-no"><?= sprintf('%02d', (int)$c['card_no']) ?></span>
                <span class="wg-body">
                    <div class="wg-lock-t"><i class="fa-solid fa-lock" aria-hidden="true"></i> Package එකක් අවශ්‍යයි</div>
                    <span class="wg-lock-b">Upgrade</span>
                </span>
            </a>
        </div>
<?php else:
        $en = grammar_plain($c['gloss_en'] ?? '');
        $si = grammar_plain($c['gloss_si'] ?? '');
        $needle = mb_strtolower(($c['title_jp'] ?? '') . ' ' . ($c['formula'] ?? '') . ' ' . $en . ' ' . $si . ' ' . sprintf('%02d', (int)$c['card_no']), 'UTF-8');
?>
        <div class="col-12 col-md-6 wg-col" data-locked="0" data-search="<?= grammar_h($needle) ?>">
            <a class="wg-card" href="w-grammar_card.php?slug=<?= rawurlencode($c['slug']) . $q ?>">
                <span class="wg-no"><?= sprintf('%02d', (int)$c['card_no']) ?></span>
                <span class="wg-body">
                    <div class="wg-title"><?= grammar_h($c['title_jp']) ?></div>
<?php if (!empty($c['formula'])): ?>                    <div class="wg-formula jp"><?= grammar_h($c['formula']) ?></div>
<?php endif; ?>
<?php if ($en !== ''): ?>                    <div class="wg-en"><?= grammar_h($en) ?></div>
<?php endif; ?>
<?php if ($si !== ''): ?>                    <div class="wg-si"><?= grammar_h($si) ?></div>
<?php endif; ?>
                </span>
                <i class="fa-solid fa-chevron-right wg-go" aria-hidden="true"></i>
            </a>
        </div>
<?php endif; ?>
<?php endforeach; ?>
    </div>
    <?= $bare ? '' : cp_close_area() ?>
    <div class="wg-empty" id="wgEmpty">ගැළපෙන ව්‍යාකරණ හමු නොවීය.</div>

    <div class="wg-actions d-flex flex-wrap align-items-center justify-content-center gap-3 my-4 py-2">
        <?php if ($lockedCnt > 0): ?>
            <a href="<?= grammar_h($wgUpgrade) ?>" class="btn-brand"><i class="fa-solid fa-crown" aria-hidden="true"></i> Package Upgrade</a>
        <?php endif; ?>
        <a href="<?= grammar_h($wgBooksUrl) ?>" class="btn-brand-outline"><i class="fa-solid fa-book" aria-hidden="true"></i> මුද්‍රිත පොත මිල දී ගන්න</a>
    </div>

    <script>
    (function () {
        var q = document.getElementById('wgQ');
        var cols = document.querySelectorAll('#wgGrid .wg-col');
        var shown = document.getElementById('wgShown');
        var empty = document.getElementById('wgEmpty');
        var note = document.getElementById('wgNote');
        if (!q) return;
        q.addEventListener('input', function () {
            var term = q.value.trim().toLowerCase();
            var n = 0;
            cols.forEach(function (c) {
                var locked = c.getAttribute('data-locked') === '1';
                var vis = !term || (!locked && c.getAttribute('data-search').indexOf(term) !== -1);
                c.style.display = vis ? '' : 'none';
                if (vis) n++;
            });
            if (shown) shown.textContent = n;
            if (empty) empty.style.display = n ? 'none' : 'block';
            if (note)  note.style.display  = term ? 'block' : 'none';
        });
    })();
    </script>
<?php endif; ?>
<?php endif; ?>

</div>
<?php
echo grammar_render_book_modal($levels);

if ($__ftr) { require $__ftr; } else { echo "\n</body></html>"; }
