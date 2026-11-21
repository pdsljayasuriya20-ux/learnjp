<?php
// ---------------------------------------------------------------
// grammar_level.php — shared grammar list page for JLPT N5 / N4 / N3 / N2 / N1.
// Do not open directly. grammarn5.php ... grammarn1.php set $gLevel (+ SEO vars)
// and then require this file.
//
// Data comes from includes/w-grammar-render.php (the same functions w-grammar.php uses):
//   grammar_levels(), grammar_list_cards($level), grammar_h(), grammar_plain(), GC_FREE_LIMIT
//   * first GC_FREE_LIMIT cards are free for everybody (set it to 30 in w-grammar-render.php)
//   * the rest need an active package for that level (table user_packages, see upgrade.sql)
//   * locked cards are NEVER printed (no title/formula/meaning in the HTML)
// Single card page stays: w-grammar_card.php?slug=...
// ---------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($gLevel)) { http_response_code(404); exit; }

require_once __DIR__ . '/includes/w-grammar-render.php';

$gFree      = defined('GC_FREE_LIMIT') ? (int)GC_FREE_LIMIT : 30;
$gBackUrl   = $gBackUrl ?? strtolower($gLevel) . '.php';
$gBooksUrl  = 'books.php';
$gUpgrade   = 'upgrade.php?level=' . urlencode($gLevel);
$gPageBase  = 'grammar' . strtolower($gLevel) . '.php';

require_once __DIR__ . '/includes/header.php';

/** active package for $level (or 'ALL')? — same rule as kanji_level.php */
if (!function_exists('gl_has_package')) {
    function gl_has_package(string $level): bool {
        if (empty($_SESSION['user_id']) || !function_exists('getDB')) { return false; }
        try {
            $st = getDB()->prepare(
                "SELECT 1 FROM user_packages
                  WHERE user_id = ? AND level_code IN (?, 'ALL')
                    AND (expires_at IS NULL OR expires_at > NOW())
                  LIMIT 1");
            $st->execute([(int)$_SESSION['user_id'], $level]);
            return (bool)$st->fetchColumn();
        } catch (Throwable $e) {
            error_log('gl_has_package: ' . $e->getMessage());
            return false;
        }
    }
}

$gHasAccess = gl_has_package($gLevel);

// ---------- load data ----------
$gError  = '';
$gLevels = [];
$gCards  = [];
try {
    $gLevels = grammar_levels();
    $avail   = array_column($gLevels, 'level');
    if (in_array($gLevel, $avail, true)) {
        $gCards = grammar_list_cards($gLevel);
    }
} catch (Throwable $e) {
    error_log('grammar_level.php (' . $gLevel . '): ' . $e->getMessage());
    $gError = 'දත්ත පූරණය කිරීමට නොහැකි විය. කරුණාකර පසුව නැවත උත්සාහ කරන්න.';
}

$gTotal    = count($gCards);
$gOpenCnt  = $gHasAccess ? $gTotal : min($gFree, $gTotal);
$gHasLock  = ($gTotal > $gOpenCnt);

// card counts per level (for the tabs)
$gCounts = [];
foreach ($gLevels as $lv) { $gCounts[strtoupper($lv['level'])] = (int)$lv['total']; }
$gTabs = ['N5', 'N4', 'N3', 'N2', 'N1'];
?>

<!-- =====================================================
     NEUMORPHISM THEME — Grammar N5–N1 (mobile-first)
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
    .text-muted { color: var(--nm-muted) !important; }
    .jp { font-family: 'Noto Sans JP', sans-serif; }

    @keyframes nmFadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
    @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }

    .gr-wrap { padding-bottom: 1.5rem; }

    .back-btn { display: inline-flex; align-items: center; gap: 6px; background: var(--nm-bg); color: var(--nm-muted); border-radius: 999px; padding: 9px 18px; font-size: .85rem; font-weight: 700; text-decoration: none; box-shadow: var(--nm-raised-sm); transition: box-shadow .2s ease, color .2s ease; }
    .back-btn:hover { color: var(--nm-brand); }
    .back-btn:active { box-shadow: var(--nm-inset-sm); }

    .hero-banner { background: linear-gradient(135deg, var(--nm-brand) 0%, var(--nm-brand-2) 100%); border-radius: var(--nm-radius); color: #fff; padding: 2.2rem 1.5rem; text-align: center; box-shadow: var(--nm-raised); position: relative; overflow: hidden; animation: nmFadeUp .5s ease both; }
    .hero-banner::before, .hero-banner::after { content: ""; position: absolute; border-radius: 50%; background: rgba(255,255,255,.12); }
    .hero-banner::before { width: 150px; height: 150px; top: -55px; right: -30px; }
    .hero-banner::after  { width: 100px; height: 100px; bottom: -40px; left: 10%; }
    .hero-banner > * { position: relative; z-index: 1; }
    .hero-btn { display: inline-flex; align-items: center; gap: 8px; margin-top: 16px; background: var(--nm-bg); color: var(--nm-brand); text-decoration: none; font-weight: 800; font-size: .92rem; padding: 11px 22px; border-radius: 999px; box-shadow: 5px 5px 12px rgba(30,20,120,.35), -3px -3px 9px rgba(255,255,255,.25); transition: transform .2s ease, box-shadow .2s ease; }
    .hero-btn:hover { color: var(--nm-brand); transform: translateY(-2px); }
    .hero-btn:active { box-shadow: inset 3px 3px 7px var(--nm-dark), inset -3px -3px 7px var(--nm-light); transform: scale(.97); }

    .stat-card { background: var(--nm-bg); border-radius: var(--nm-radius-sm); box-shadow: var(--nm-raised-sm); padding: 14px 6px; text-align: center; height: 100%; animation: nmFadeUp .55s ease both; }
    .stat-card .num { font-size: 1.45rem; font-weight: 800; line-height: 1.2; background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent; }
    .stat-card .small { font-size: .72rem; }

    .app-card { background: var(--nm-bg); border-radius: var(--nm-radius); padding: 1.3rem 1.1rem; border: 0; box-shadow: var(--nm-raised); margin-bottom: 1.5rem; animation: nmFadeUp .55s ease both; }

    .gr-tabs { display: flex; gap: 10px; overflow-x: auto; padding: 4px 6px 12px; margin: 0 -6px; scrollbar-width: none; -webkit-overflow-scrolling: touch; }
    .gr-tabs::-webkit-scrollbar { display: none; }
    .gr-tab { flex: 0 0 auto; display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 8px 16px; font-size: .85rem; font-weight: 800; color: var(--nm-muted); background: var(--nm-bg); box-shadow: var(--nm-raised-sm); text-decoration: none; transition: box-shadow .2s ease, color .2s ease; }
    .gr-tab small { font-size: .68rem; font-weight: 700; opacity: .75; }
    .gr-tab:hover { color: var(--nm-brand); }
    .gr-tab.active { color: var(--nm-brand); box-shadow: var(--nm-inset-sm); }

    .pg-search { display: flex; align-items: center; gap: 10px; background: var(--nm-bg); border-radius: 999px; box-shadow: var(--nm-inset); padding: 4px 18px; }
    .pg-search i { color: var(--nm-muted); }
    .pg-search input { flex: 1; min-width: 0; border: 0; outline: 0; background: transparent; color: var(--nm-text); font-size: .95rem; padding: 11px 0; font-family: inherit; }

    .pg-guest { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; background: var(--nm-bg); border-radius: var(--nm-radius-sm); box-shadow: var(--nm-inset-sm); padding: 14px 16px; border-left: 5px solid var(--nm-gold); }
    .pg-guest .ic { font-size: 1.5rem; }
    .pg-guest .tx { flex: 1 1 220px; font-size: .88rem; font-weight: 600; }

    .gr-col { animation: nmFadeUp .45s ease both; }
    .gr-card { display: flex; align-items: center; gap: 14px; width: 100%; height: 100%; text-decoration: none; color: var(--nm-text); background: var(--nm-bg); border: 0; border-radius: var(--nm-radius-sm); box-shadow: var(--nm-raised-sm); padding: 14px 16px; text-align: left; font-family: inherit; cursor: pointer; transition: box-shadow .2s ease, transform .2s ease; }
    .gr-card:hover  { transform: translateY(-3px); color: var(--nm-text); }
    .gr-card:active { box-shadow: var(--nm-inset-sm); transform: scale(.98); }
    .gr-card:focus-visible { outline: 3px solid var(--nm-brand-2); outline-offset: 2px; }
    .gr-no { flex: 0 0 auto; min-width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .85rem; color: var(--nm-brand); background: var(--nm-bg); box-shadow: var(--nm-inset-sm); }
    .gr-body { min-width: 0; flex: 1; }
    .gr-title { font-family: 'Noto Sans JP', sans-serif; font-weight: 800; font-size: 1.05rem; color: var(--nm-brand); line-height: 1.3; word-break: break-word; }
    .gr-formula { font-size: .76rem; color: var(--nm-muted); font-weight: 700; line-height: 1.4; margin-top: 2px; word-break: break-word; }
    .gr-en { font-size: .84rem; font-weight: 700; margin-top: 6px; line-height: 1.35; }
    .gr-si { font-family: 'Noto Sans Sinhala', sans-serif; font-size: .84rem; font-weight: 600; color: var(--nm-text); margin-top: 2px; line-height: 1.45; }
    .gr-go { flex: 0 0 auto; color: var(--nm-muted); }

    .gr-card.is-locked .gr-no { color: var(--nm-muted); }
    .gr-lock-t { font-weight: 800; font-size: .88rem; color: var(--nm-muted); }
    .gr-lock-b { display: inline-block; margin-top: 6px; font-size: .72rem; font-weight: 800; color: var(--nm-brand); background: var(--nm-bg); box-shadow: var(--nm-raised-sm); border-radius: 999px; padding: 5px 12px; }

    .pg-empty { display: none; text-align: center; padding: 2rem 1rem; color: var(--nm-muted); font-weight: 600; }

    .btn-brand, .btn-brand-outline { border-radius: 999px; font-weight: 800; border: 0; text-decoration: none; cursor: pointer; font-family: inherit; font-size: 1rem; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: box-shadow .2s ease, transform .2s ease; }
    .btn-brand { background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); color: #fff; padding: 13px 26px; box-shadow: var(--nm-raised-sm); }
    .btn-brand:hover { color: #fff; transform: translateY(-2px); }
    .btn-brand-outline { background: var(--nm-bg); color: var(--nm-brand); padding: 13px 24px; box-shadow: var(--nm-raised-sm); }
    .btn-brand-outline:hover { color: var(--nm-brand); transform: translateY(-2px); }
    .btn-brand:active, .btn-brand-outline:active { box-shadow: var(--nm-inset-sm); transform: scale(.97); }
    .btn-sm-nm { padding: 9px 18px; font-size: .88rem; }

    @media (max-width: 575.98px) {
        .app-card { padding: 1.1rem .85rem; border-radius: 20px; }
        .hero-banner { padding: 1.7rem 1.1rem; border-radius: 20px; }
        .hero-banner h1 { font-size: 1.7rem !important; }
        .hero-banner p  { font-size: .9rem !important; }
        .stat-card .num { font-size: 1.25rem; }
        .gr-actions .btn-brand, .gr-actions .btn-brand-outline { width: 100%; }
    }
</style>

<div class="gr-wrap">

    <a href="<?php echo grammar_h($gBackUrl); ?>" class="back-btn mb-3">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> ආපසු Dashboard වෙත
    </a>

    <div class="hero-banner mb-4 mt-3">
        <h1 class="fw-bold fs-2 mb-2">🎌 JLPT <?php echo grammar_h($gLevel); ?> ව්‍යාකරණ</h1>
        <p class="mb-0 opacity-90 fs-6">ව්‍යාකරණ කාඩ්පත් <?php echo (int)$gTotal; ?> — පිහිටුම, තේරුම සහ උදාහරණ වාක්‍ය සිංහලෙන්</p>
        <?php if ($gHasLock): ?>
            <a href="<?php echo grammar_h($gUpgrade); ?>" class="hero-btn">
                <i class="fa-solid fa-crown" aria-hidden="true"></i> සම්පූර්ණ ව්‍යාකරණ විවෘත කරන්න (Upgrade)
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
        <?php else: ?>
            <a href="<?php echo grammar_h($gBooksUrl); ?>" class="hero-btn">
                <i class="fa-solid fa-book" aria-hidden="true"></i> මුද්‍රිත පොත මිල දී ගන්න
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
        <?php endif; ?>
    </div>

    <?php if ($gError): ?>
        <div class="app-card text-center"><p class="mb-0 fw-bold"><?php echo grammar_h($gError); ?></p></div>
    <?php elseif (!$gCards): ?>
        <div class="app-card text-center"><p class="mb-0 fw-bold text-muted">තවම කාඩ්පත් නැත. (No grammar cards yet.)</p></div>
    <?php else: ?>

    <div class="row g-2 g-md-3 mb-4">
        <div class="col-6 col-md-3"><div class="stat-card"><div class="num"><?php echo (int)$gTotal; ?></div><div class="small text-muted">මුළු කාඩ්පත්</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="num" id="grShown"><?php echo (int)$gTotal; ?></div><div class="small text-muted">පෙන්වන ගණන</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="num"><?php echo (int)min($gFree, $gTotal); ?></div><div class="small text-muted">නොමිලේ බැලිය හැක</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="num"><?php echo $gHasAccess ? '✓' : (int)$gOpenCnt; ?></div><div class="small text-muted"><?php echo $gHasAccess ? 'සියල්ල විවෘතයි' : 'ඔබට විවෘත'; ?></div></div></div>
    </div>

    <?php if ($gHasLock): ?>
        <div class="pg-guest mb-4">
            <span class="ic" aria-hidden="true">🔒</span>
            <div class="tx">පළමු කාඩ්පත් <?php echo (int)$gFree; ?> සියලු දෙනාට නොමිලේ. ඉතිරි කාඩ්පත් <?php echo (int)($gTotal - $gOpenCnt); ?> බැලීමට <?php echo grammar_h($gLevel); ?> Package එකට Upgrade කරන්න.</div>
            <a href="<?php echo grammar_h($gUpgrade); ?>" class="btn-brand btn-sm-nm"><i class="fa-solid fa-crown" aria-hidden="true"></i> Upgrade</a>
        </div>
    <?php endif; ?>

    <div class="app-card">
        <div class="gr-tabs" role="navigation" aria-label="JLPT මට්ටම්">
            <?php foreach ($gTabs as $t): ?>
                <a class="gr-tab<?php echo $t === $gLevel ? ' active' : ''; ?>" href="grammar<?php echo strtolower($t); ?>.php"<?php echo $t === $gLevel ? ' aria-current="page"' : ''; ?>>
                    <?php echo $t; ?><?php if (isset($gCounts[$t])): ?><small><?php echo (int)$gCounts[$t]; ?></small><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
        <label class="pg-search" for="grQ">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" id="grQ" placeholder="ව්‍යාකරණය, තේරුම හෝ පිහිටුම සොයන්න..." autocomplete="off">
        </label>
        <?php if ($gHasLock): ?>
            <p class="small text-muted mb-0 mt-2 px-1" id="grNote" style="display:none">සෙවීම ඔබට විවෘත කාඩ්පත් අතර පමණි. සියල්ල සෙවීමට Package එක Upgrade කරන්න.</p>
        <?php endif; ?>
    </div>

    <div class="row g-3 mb-4" id="grGrid">
        <?php foreach ($gCards as $i => $c): ?>
            <?php if ($i >= $gOpenCnt): ?>
                <div class="col-12 col-md-6 gr-col" data-locked="1" data-search="">
                    <a class="gr-card is-locked" href="<?php echo grammar_h($gUpgrade); ?>" aria-label="කාඩ්පත <?php echo sprintf('%02d', (int)$c['card_no']); ?> — Upgrade අවශ්‍යයි">
                        <span class="gr-no"><?php echo sprintf('%02d', (int)$c['card_no']); ?></span>
                        <span class="gr-body">
                            <div class="gr-lock-t"><i class="fa-solid fa-lock" aria-hidden="true"></i> Package එකක් අවශ්‍යයි</div>
                            <span class="gr-lock-b">Upgrade</span>
                        </span>
                    </a>
                </div>
            <?php else:
                $en  = grammar_plain($c['gloss_en'] ?? '');
                $si  = grammar_plain($c['gloss_si'] ?? '');
                $needle = mb_strtolower(($c['title_jp'] ?? '') . ' ' . ($c['formula'] ?? '') . ' ' . $en . ' ' . $si . ' ' . sprintf('%02d', (int)$c['card_no']), 'UTF-8');
            ?>
                <div class="col-12 col-md-6 gr-col" data-locked="0" data-search="<?php echo grammar_h($needle); ?>">
                    <a class="gr-card" href="w-grammar_card.php?slug=<?php echo rawurlencode($c['slug']); ?>">
                        <span class="gr-no"><?php echo sprintf('%02d', (int)$c['card_no']); ?></span>
                        <span class="gr-body">
                            <div class="gr-title"><?php echo grammar_h($c['title_jp']); ?></div>
                            <?php if (!empty($c['formula'])): ?><div class="gr-formula jp"><?php echo grammar_h($c['formula']); ?></div><?php endif; ?>
                            <?php if ($en !== ''): ?><div class="gr-en"><?php echo grammar_h($en); ?></div><?php endif; ?>
                            <?php if ($si !== ''): ?><div class="gr-si"><?php echo grammar_h($si); ?></div><?php endif; ?>
                        </span>
                        <i class="fa-solid fa-chevron-right gr-go" aria-hidden="true"></i>
                    </a>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <div class="pg-empty" id="grEmpty">ගැළපෙන ව්‍යාකරණ හමු නොවීය.</div>

    <div class="gr-actions d-flex flex-wrap align-items-center justify-content-center gap-3 my-4 py-2">
        <?php if ($gHasLock): ?>
            <a href="<?php echo grammar_h($gUpgrade); ?>" class="btn-brand"><i class="fa-solid fa-crown" aria-hidden="true"></i> Package Upgrade</a>
        <?php endif; ?>
        <a href="<?php echo grammar_h($gBooksUrl); ?>" class="btn-brand-outline"><i class="fa-solid fa-book" aria-hidden="true"></i> මුද්‍රිත පොත මිල දී ගන්න</a>
    </div>

    <?php endif; ?>
</div>

<script>
(function () {
    var q     = document.getElementById('grQ');
    var cols  = document.querySelectorAll('#grGrid .gr-col');
    var shown = document.getElementById('grShown');
    var empty = document.getElementById('grEmpty');
    var note  = document.getElementById('grNote');
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
