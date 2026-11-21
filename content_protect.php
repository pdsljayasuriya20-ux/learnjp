<?php
// ---------------------------------------------------------------
// includes/content_protect.php — watermark + copy deterrent for lesson pages.
//
// USAGE on any page (after the site header has been included):
//     require_once __DIR__ . '/includes/content_protect.php';
//     echo cp_render_assets();
//     echo cp_open_area();
//         ... lesson content ...
//     echo cp_close_area();
//
// WHAT IT DOES
//   * tiled diagonal watermark over the whole content (site name + user id + date),
//     re-inserted if someone deletes it with dev-tools
//   * text selection, right-click / long-press menu, copy, cut, drag, Ctrl+C/X/A/S/P/U,
//     F12 and Ctrl+Shift+I/J/C are blocked inside the protected area
//   * printing shows only a "printing disabled" message
//
// HONEST LIMIT: this runs in the visitor's browser, so it is a deterrent, not a lock.
// Screenshots, "view source" and determined users can still get at the text.
// The only truly safe content is content the server never sends (that is what the
// locked cards / locked kanji already do). The watermark is what makes leaked
// screenshots traceable to an account.
// ---------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function cp_watermark_lines(): array {
    $l1 = 'learnjapanes.jp';
    $l2 = !empty($_SESSION['user_id'])
        ? 'ID ' . (int)$_SESSION['user_id'] . ' · ' . date('Y-m-d')
        : 'Guest · ' . date('Y-m-d');
    return [$l1, $l2];
}

/** data: URL of the tiled SVG watermark */
function cp_watermark_url(): string {
    [$a, $b] = cp_watermark_lines();
    $a = htmlspecialchars($a, ENT_QUOTES, 'UTF-8');
    $b = htmlspecialchars($b, ENT_QUOTES, 'UTF-8');
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="340" height="220">'
         . '<g transform="rotate(-25 170 110)" fill="#4f46e5" fill-opacity="0.10" font-family="Arial,Helvetica,sans-serif" font-weight="700" text-anchor="middle">'
         . '<text x="170" y="104" font-size="22">' . $a . '</text>'
         . '<text x="170" y="132" font-size="15">' . $b . '</text>'
         . '</g></svg>';
    return 'data:image/svg+xml,' . rawurlencode($svg);
}

function cp_render_assets(): string {
    ob_start(); ?>
<style>
    .cp-area { position: relative; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none; -webkit-touch-callout: none; }
    .cp-area input, .cp-area textarea { -webkit-user-select: text; user-select: text; }
    .cp-area img { -webkit-user-drag: none; user-drag: none; }
    .cp-wm { position: absolute; inset: 0; z-index: 60; pointer-events: none; background-repeat: repeat; }
    @media print {
        body * { visibility: hidden !important; }
        body::before { content: "මුද්‍රණය කළ නොහැක — Printing is disabled"; visibility: visible !important; display: block; padding: 2rem; font: 700 20px Arial, sans-serif; color: #000; }
    }
</style>
<script>
(function () {
    function inArea(t) { return t && t.closest && t.closest('.cp-area'); }
    function isField(t) { return t && t.closest && t.closest('input, textarea'); }
    var NOTE = 'learnjapanes.jp — copying is disabled';

    document.addEventListener('contextmenu', function (e) { if (inArea(e.target) && !isField(e.target)) e.preventDefault(); });
    ['copy', 'cut'].forEach(function (ev) {
        document.addEventListener(ev, function (e) {
            var s = window.getSelection && window.getSelection();
            var inField = isField(document.activeElement);
            if (inField) return;                       // search box still works
            if (inArea(e.target) || (s && s.anchorNode && inArea(s.anchorNode.parentNode))) {
                if (e.clipboardData) e.clipboardData.setData('text/plain', NOTE);
                e.preventDefault();
            }
        });
    });
    document.addEventListener('selectstart', function (e) { if (inArea(e.target) && !isField(e.target)) e.preventDefault(); });
    document.addEventListener('dragstart',   function (e) { if (inArea(e.target)) e.preventDefault(); });

    document.addEventListener('keydown', function (e) {
        var k = (e.key || '').toLowerCase();
        var mod = e.ctrlKey || e.metaKey;
        var inField = isField(document.activeElement);
        if (e.key === 'F12') { e.preventDefault(); return; }
        if (mod && e.shiftKey && ['i', 'j', 'c'].indexOf(k) !== -1) { e.preventDefault(); return; }
        if (mod && ['s', 'p', 'u'].indexOf(k) !== -1) { e.preventDefault(); return; }
        if (mod && ['c', 'x', 'a'].indexOf(k) !== -1 && !inField) { e.preventDefault(); }
    });

    // keep the watermark in place even if it is deleted / restyled in dev-tools
    function guard() {
        var area = document.querySelector('.cp-area'); if (!area) return;
        var url = area.getAttribute('data-wm');
        var wm = area.querySelector('.cp-wm');
        if (!wm) { wm = document.createElement('div'); wm.className = 'cp-wm'; wm.setAttribute('aria-hidden', 'true'); area.appendChild(wm); }
        wm.removeAttribute('style');
        wm.style.backgroundImage = 'url("' + url + '")';
    }
    document.addEventListener('DOMContentLoaded', function () {
        guard();
        setInterval(guard, 1500);
        var area = document.querySelector('.cp-area');
        if (area && window.MutationObserver) new MutationObserver(guard).observe(area, { childList: true });
    });
})();
</script>
<?php
    return (string)ob_get_clean();
}

function cp_open_area(): string {
    return '<div class="cp-area" data-wm="' . htmlspecialchars(cp_watermark_url(), ENT_QUOTES, 'UTF-8') . '">';
}

function cp_close_area(): string {
    return '<div class="cp-wm" aria-hidden="true" style="background-image:url(\'' . cp_watermark_url() . '\')"></div></div>';
}
