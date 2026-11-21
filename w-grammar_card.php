<?php
/**
 * w-grammar_card.php?slug=zaru-o-enai
 * Single grammar card with navigation — normal website theme (Bootstrap look, no neumorphism, no printed-book strip).
 *
 * ACCESS:  cards beyond GC_FREE_LIMIT are locked — their content is never rendered into the HTML —
 *          unless the user has an active package for that level (table user_packages).
 *          Titles of locked neighbours are hidden from the prev / next buttons too.
 * PROTECT: watermark + copy / print deterrent via includes/content_protect.php
 *
 * ?bare=1 (no header/footer, no protection, toolbar visible) is ADMIN ONLY — see $isAdmin.
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/includes/w-grammar-render.php';
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
$wcUpgrade = 'upgrade.php';
$wcBase = 4000; $wcStep = 1500;                 // must match upgrade.php (N5 price, step per level)
$wcOrder = ['N5', 'N4', 'N3', 'N2', 'N1'];
$isAdmin = !empty($_SESSION['is_admin']);       // who may use ?bare=1
// --------------------

set_exception_handler(function (Throwable $e) use ($isAdmin) {
    error_log('w-grammar_card.php: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>'
       . '<body style="font-family:sans-serif;max-width:560px;margin:60px auto;padding:24px;text-align:center;color:#212529">'
       . '<h2>දෝෂයක් සිදු විය</h2><p>කරුණාකර පසුව නැවත උත්සාහ කරන්න. <a href="w-grammar.php">← ලැයිස්තුවට</a></p>';
    if ($isAdmin) {
        echo '<pre style="text-align:left;white-space:pre-wrap;font-size:12px;color:#991b1b">'
           . htmlspecialchars(get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine()) . '</pre>';
    }
    echo '</body></html>';
    exit;
});

/** active package for $level (or 'ALL')? */
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

$slug = (string)($_GET['slug'] ?? '');
$card = $slug !== '' ? grammar_get_card_by_slug($slug) : null;
$bare = !empty($_GET['bare']) && $isAdmin;
$q    = $bare ? '&bare=1' : '';

$hasPkg   = false;
$isLocked = false;
if ($card) {
    $hasPkg   = gl_has_package($card['level']);
    $rank     = grammar_card_rank($card['level'], $card['slug']);
    $isLocked = !$hasPkg && $rank !== null && grammar_is_locked_rank($rank);
}

/** is a neighbour card locked for this visitor? (then its title must not be shown) */
function wc_locked_nb(?array $nb, string $level, bool $hasPkg): bool {
    if (!$nb || $hasPkg) return false;
    $r = grammar_card_rank($level, $nb['slug']);
    return $r !== null && grammar_is_locked_rank($r);
}

if ($card) {
    $pageTitle = $isLocked
        ? 'JLPT ' . $card['level'] . ' Grammar Card'
        : $card['title_jp'] . ' — JLPT ' . $card['level'] . ' Grammar';
    $pageDescription = $isLocked
        ? 'JLPT ' . $card['level'] . ' ව්‍යාකරණ කාඩ්පත් සිංහලෙන් — සම්පූර්ණ කාඩ්පත් බැලීමට Package එක activate කරගන්න.'
        : $card['formula'] . ($card['gloss_en'] !== '' ? ' — ' . grammar_plain($card['gloss_en']) : '');
} else {
    http_response_code(404);
    $pageTitle       = 'Grammar card not found';
    $pageDescription = '';
}

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
    /* ===== same neumorphism theme + width as index.php (page uses the site's normal container width) ===== */
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
    .text-muted { color: var(--nm-muted) !important; }

    @keyframes nmFadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
    .hero-banner, .app-card, .wc-nav, .pkg-card { animation: nmFadeUp .55s ease both; }
    @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }

    .hero-banner { background: linear-gradient(135deg, var(--nm-brand) 0%, var(--nm-brand-2) 100%); color: #fff; border-radius: var(--nm-radius); padding: 28px 22px; box-shadow: var(--nm-raised); position: relative; overflow: hidden; }
    .hero-banner::before, .hero-banner::after { content: ""; position: absolute; border-radius: 50%; background: rgba(255,255,255,.12); }
    .hero-banner::before { width: 140px; height: 140px; top: -50px; right: -30px; }
    .hero-banner::after  { width: 90px; height: 90px; bottom: -35px; left: 12%; }
    .hero-banner > * { position: relative; z-index: 1; }

    .app-card { background: var(--nm-bg); border-radius: var(--nm-radius); box-shadow: var(--nm-raised); padding: 22px 18px; border: 0; }

    .see-all-link { display: inline-flex; align-items: center; gap: 6px; background: var(--nm-bg); color: var(--nm-brand); box-shadow: var(--nm-raised-sm); border-radius: 999px; padding: 8px 16px; font-size: .82rem; font-weight: 700; text-decoration: none; transition: box-shadow .2s ease, transform .2s ease; white-space: nowrap; }
    .see-all-link:hover { color: var(--nm-brand); transform: translateY(-2px); }
    .see-all-link:active { box-shadow: var(--nm-inset-sm); transform: scale(.98); }

    .wc-lock { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; background: var(--nm-bg); border-radius: var(--nm-radius-sm); box-shadow: var(--nm-inset-sm); padding: 14px 16px; border-left: 5px solid #f59e0b; }
    .wc-lock .tx { flex: 1 1 220px; font-size: .88rem; font-weight: 700; }

    /* full width like index.php: w-grammar-render.php may give the card a narrow max-width + its own background, so reset it here */
    .wc-frame .gc-wrap, .wc-frame .gc-scroll { width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; background: transparent !important; box-shadow: none !important; border: 0 !important; }
    .wc-frame [class^="gc-"], .wc-frame [class*=" gc-"] { max-width: none; }
    .wc-frame .gc-scroll { overflow-x: auto; }

    /* the narrow "book page" column in the screenshot wraps the whole page (header too), so it comes from a
       body / wrapper rule (book layout width + background). Undo it here so the page is as wide as index.php */
    html { background: var(--nm-bg) !important; }
    body { max-width: none !important; width: auto !important; margin: 0 !important; box-shadow: none !important; border: 0 !important; }

    .wc-nav { display: grid; grid-template-columns: 1fr auto 1fr; gap: 12px; align-items: stretch; }
    .jlpt-btn { display: flex; align-items: center; justify-content: center; gap: 8px; min-width: 0; padding: 14px 16px; background: var(--nm-bg); border-radius: var(--nm-radius-sm); box-shadow: var(--nm-raised-sm); text-decoration: none; color: var(--nm-brand); font-weight: 800; font-size: .88rem; transition: box-shadow .2s ease, transform .2s ease; }
    .jlpt-btn:hover { color: var(--nm-brand); transform: translateY(-2px); }
    .jlpt-btn:active { box-shadow: var(--nm-inset-sm); transform: scale(.97); }
    .jlpt-btn span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-family: 'Noto Sans JP', sans-serif; }
    .jlpt-btn.next { justify-content: flex-end; }
    .jlpt-btn.prev { justify-content: flex-start; }
    .jlpt-btn.is-locked { color: var(--nm-muted); }
    .wc-nav-empty { visibility: hidden; }

    .pkg-card { display: block; text-decoration: none; color: #fff; background: linear-gradient(135deg, var(--nm-brand) 0%, var(--nm-brand-2) 100%); border-radius: var(--nm-radius-sm); padding: 18px 16px; box-shadow: var(--nm-raised-sm); transition: transform .2s ease, box-shadow .2s ease; position: relative; overflow: hidden; }
    .pkg-card::after { content: ""; position: absolute; width: 110px; height: 110px; border-radius: 50%; background: rgba(255,255,255,.12); right: -30px; top: -40px; }
    .pkg-card:hover { color: #fff; transform: translateY(-2px); }
    .pkg-card:active { transform: scale(.98); box-shadow: var(--nm-inset-sm); }
    .pkg-card.is-ok { background: linear-gradient(135deg, #059669, #10b981); }

    .wc-notfound { text-align: center; max-width: 520px; margin: 20px auto; }
    .wc-notfound h1 { font-size: 1.3rem; font-weight: 800; color: var(--nm-brand); }
    .wc-notfound a { color: var(--nm-brand); font-weight: 800; text-decoration: none; }

    @media (max-width: 575.98px) {
        .hero-banner { padding: 22px 18px; border-radius: 20px; }
        .hero-banner h1 { font-size: 1.35rem !important; }
        .app-card { padding: 14px 10px; border-radius: 20px; }
        .wc-nav { grid-template-columns: 1fr 1fr; }
        .wc-nav-list { grid-column: 1 / -1; order: 3; }
    }
</style>

<?php if (!$card): ?>
    <div class="app-card wc-notfound">
        <h1>කාඩ්පත සොයාගත නොහැක</h1>
        <p class="text-muted mb-0">Requested Grammar Card Not Found. <a href="w-grammar.php">← ලැයිස්තුවට යන්න (Back to List)</a></p>
    </div>
<?php else:
    $nb      = grammar_neighbours($card);
    $lvl     = $card['level'];
    $lvlIdx  = array_search($lvl, $wcOrder, true);
    $price   = $wcBase + ($lvlIdx === false ? 0 : (int)$lvlIdx) * $wcStep;
    $pkgUrl  = $wcUpgrade . '?level=' . urlencode($lvl);
    $prevLocked = wc_locked_nb($nb['prev'] ?? null, $lvl, $hasPkg);
    $nextLocked = wc_locked_nb($nb['next'] ?? null, $lvl, $hasPkg);
?>
    <div class="mb-3">
        <a href="w-grammar.php?level=<?= grammar_h($lvl) . $q ?>" class="see-all-link"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> ලැයිස්තුවට (List)</a>
    </div>

    <div class="hero-banner mb-4">
        <h1 class="fw-bold fs-2 mb-2">🎌 JLPT <?= grammar_h($lvl) ?> ව්‍යාකරණ</h1>
        <p class="mb-0 opacity-90 fs-6">ව්‍යාකරණ කාඩ්පත<?= isset($card['card_no']) ? ' #' . sprintf('%02d', (int)$card['card_no']) : '' ?></p>
    </div>

    <?php if ($isLocked): ?>
        <div class="wc-lock mb-4">
            <span class="fs-4" aria-hidden="true">🔒</span>
            <div class="tx">මෙම කාඩ්පත බැලීමට <?= grammar_h($lvl) ?> Package එක activate කරගන්න.</div>
            <a href="<?= grammar_h($pkgUrl) ?>" class="see-all-link"><i class="fa-solid fa-crown" aria-hidden="true"></i> Upgrade</a>
        </div>
    <?php endif; ?>

    <?= $bare ? '' : cp_open_area() ?>
    <div class="app-card wc-frame mb-4">
        <div id="gc-wrap" class="gc-wrap">
          <?php if ($bare) { echo grammar_render_toolbar(); } /* print / copy tools: admin (bare) mode only */ ?>
          <div class="gc-scroll">
<?php if ($isLocked): ?>
            <?= grammar_render_locked_card($card) ?>
<?php else: ?>
            <?= grammar_render_card($card) ?>
<?php endif; ?>
          </div>
<?php if ($isLocked) { echo grammar_render_upsell_banner(1); } ?>
        </div>
    </div>
    <?= $bare ? '' : cp_close_area() ?>

    <!-- prev / list / next (locked neighbours show no title) -->
    <div class="wc-nav mb-4">
<?php if (!empty($nb['prev'])): ?>
        <a class="jlpt-btn prev<?= $prevLocked ? ' is-locked' : '' ?>" href="<?= $prevLocked ? grammar_h($pkgUrl) : 'w-grammar_card.php?slug=' . rawurlencode($nb['prev']['slug']) . $q ?>">
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            <span><?= $prevLocked ? '🔒 පෙර කාඩ්පත' : grammar_h($nb['prev']['title_jp']) ?></span>
        </a>
<?php else: ?>
        <span class="jlpt-btn wc-nav-empty">&nbsp;</span>
<?php endif; ?>
        <a class="jlpt-btn wc-nav-list" href="w-grammar.php?level=<?= grammar_h($lvl) . $q ?>"><i class="fa-solid fa-list" aria-hidden="true"></i> <span>ලැයිස්තුව</span></a>
<?php if (!empty($nb['next'])): ?>
        <a class="jlpt-btn next<?= $nextLocked ? ' is-locked' : '' ?>" href="<?= $nextLocked ? grammar_h($pkgUrl) : 'w-grammar_card.php?slug=' . rawurlencode($nb['next']['slug']) . $q ?>">
            <span><?= $nextLocked ? 'ඊළඟ කාඩ්පත 🔒' : grammar_h($nb['next']['title_jp']) ?></span>
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        </a>
<?php else: ?>
        <span class="jlpt-btn next wc-nav-empty">&nbsp;</span>
<?php endif; ?>
    </div>

    <!-- package -->
    <div class="mb-4">
<?php if ($hasPkg): ?>
        <a href="<?= grammar_h($pkgUrl) ?>" class="pkg-card is-ok">
            <div class="d-flex align-items-center justify-content-between position-relative">
                <div>
                    <div class="fw-bold fs-6"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> <?= grammar_h($lvl) ?> Package සක්‍රියයි</div>
                    <div class="small text-white-50">සියලු කාඩ්පත් ඔබට විවෘතයි · අනෙක් Packages බලන්න</div>
                </div>
                <i class="fa-solid fa-chevron-right text-white-50" aria-hidden="true"></i>
            </div>
        </a>
<?php else: ?>
        <a href="<?= grammar_h($pkgUrl) ?>" class="pkg-card">
            <div class="d-flex align-items-center justify-content-between position-relative">
                <div>
                    <div class="fw-bold fs-6"><i class="fa-solid fa-crown" aria-hidden="true"></i> <?= grammar_h($lvl) ?> Package මිල ගණන්</div>
                    <div class="small text-white-50">කංජි + ව්‍යාකරණ සම්පූර්ණයෙන් · ¥<?= number_format($price) ?> සිට</div>
                </div>
                <i class="fa-solid fa-chevron-right text-white-50" aria-hidden="true"></i>
            </div>
        </a>
<?php endif; ?>
    </div>

<?php endif; ?>

<script>
/* safety net: if some wrapper above the card is still a narrow "book page" box on a wide screen, release it */
(function () {
    function release() {
        var el = document.getElementById('gc-wrap') || document.querySelector('.wc-frame');
        if (!el || window.innerWidth < 1100) return;
        for (var p = el.parentElement; p && p !== document.documentElement; p = p.parentElement) {
            var w = p.getBoundingClientRect().width;
            if (w < 1100 && w < window.innerWidth * 0.8) {
                var st = p.style;
                st.setProperty('max-width', 'none', 'important'); st.setProperty('width', 'auto', 'important');
                st.setProperty('margin-left', '0', 'important'); st.setProperty('margin-right', '0', 'important');
                st.setProperty('box-shadow', 'none', 'important');
                if (p === document.body) { st.setProperty('padding-left', '0', 'important'); st.setProperty('padding-right', '0', 'important'); }
                else { st.setProperty('background', 'transparent', 'important'); }
            }
        }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', release); else release();
    window.addEventListener('load', release);
})();
</script>

<?php
if ($__ftr) { require $__ftr; } else { echo "\n</body></html>"; }