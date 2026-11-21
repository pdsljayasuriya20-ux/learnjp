<?php
/**
 * includes/a2hs.php - shared "Add to Home Screen" popup (mobile only)   |   v2 (with debug)
 *
 * USAGE (put just BEFORE the footer include on any page):
 *     $a2hsLoginOnly = true;    // true  = only logged-in users see it (default)
 *                               // false = everybody on mobile sees it
 *     require __DIR__ . '/includes/a2hs.php';
 *
 * TEST   : open any page with  ?a2hs=1      (ignores "dismissed / installed / desktop" checks)
 * DEBUG  : open any page with  ?a2hs=debug  (shows an alert that says WHY the popup is hidden)
 * RESET  : clear site data, or run in the browser console:
 *          localStorage.removeItem('a2hs_dismissed_at'); localStorage.removeItem('a2hs_installed');
 */
if (session_status() === PHP_SESSION_NONE) { @session_start(); }   // make sure $_SESSION exists on public pages

$__a2hsLoginOnly = isset($a2hsLoginOnly) ? (bool) $a2hsLoginOnly : true;
$__a2hsLoggedIn  = !empty($_SESSION['user_id']);                    // <- change the key here if your login uses another one

// PHP side: not allowed for this visitor -> in debug mode tell the developer, otherwise print nothing
if ($__a2hsLoginOnly && !$__a2hsLoggedIn) {
    if (isset($_GET['a2hs']) && $_GET['a2hs'] === 'debug') {
        echo '<script>alert("a2hs DEBUG: hidden because this page is login-only and you are NOT logged in (session user_id missing).");</script>';
    }
    return;
}
if (defined('LA_A2HS_LOADED')) { return; }                          // never print the popup twice
define('LA_A2HS_LOADED', true);
?>
<style>
    /* theme vars have fallbacks, so this also works on pages that don't define them */
    .a2hs-overlay {
        --a-bg: var(--nm-bg, #e6e9f2); --a-text: var(--nm-text, #2d3250); --a-muted: var(--nm-muted, #7a8099);
        --a-brand: var(--nm-brand, #4f46e5); --a-brand2: var(--nm-brand-2, #7c6cf5);
        --a-raised: var(--nm-raised-sm, 5px 5px 11px #c3c9dc, -5px -5px 11px #fff);
        --a-inset: var(--nm-inset-sm, inset 3px 3px 7px #c3c9dc, inset -3px -3px 7px #fff);
        position: fixed; inset: 0; z-index: 2147483000;
        background: rgba(45, 50, 80, .45);
        -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px);
        display: none; align-items: flex-end; justify-content: center;
        opacity: 0; transition: opacity .25s ease;
    }
    .a2hs-overlay.show { display: flex; }
    .a2hs-overlay.visible { opacity: 1; }
    .a2hs-sheet {
        width: 100%; max-width: 480px; background: var(--a-bg); color: var(--a-text);
        border-radius: 28px 28px 0 0;
        padding: 26px 22px calc(22px + env(safe-area-inset-bottom));
        box-shadow: 0 -12px 40px rgba(0, 0, 0, .25);
        transform: translateY(100%); transition: transform .3s ease;
        text-align: center; position: relative;
        -webkit-user-select: none; user-select: none;
    }
    .a2hs-overlay.visible .a2hs-sheet { transform: translateY(0); }
    .a2hs-x {
        position: absolute; top: 12px; right: 14px; width: 40px; height: 40px; border: 0; border-radius: 50%;
        background: var(--a-bg); color: var(--a-muted); box-shadow: var(--a-raised);
        font-size: 1.3rem; line-height: 1; display: flex; align-items: center; justify-content: center;
    }
    .a2hs-x:active { box-shadow: var(--a-inset); }
    .a2hs-logo {
        width: 76px; height: 76px; border-radius: 22px; object-fit: cover; margin-bottom: 14px;
        box-shadow: var(--a-raised); border: 4px solid var(--a-bg);
    }
    .a2hs-h { font-weight: 800; font-size: 1.12rem; margin-bottom: 6px; }
    .a2hs-p { font-size: .88rem; color: var(--a-muted); margin-bottom: 18px; }
    .a2hs-steps {
        display: none; text-align: left; background: var(--a-bg); box-shadow: var(--a-inset);
        border-radius: 16px; padding: 14px 16px; font-size: .85rem; margin-bottom: 16px; line-height: 1.7;
    }
    .a2hs-primary {
        width: 100%; background: linear-gradient(135deg, var(--a-brand), var(--a-brand2)); color: #fff; border: 0;
        border-radius: 999px; padding: 14px; font-weight: 700; font-size: 1rem; min-height: 50px;
        box-shadow: var(--a-raised); transition: box-shadow .2s ease, transform .2s ease;
    }
    .a2hs-primary:active { box-shadow: var(--a-inset); transform: scale(.98); }
    .a2hs-link { display: block; margin-top: 12px; font-size: .82rem; font-weight: 700; color: var(--a-brand); text-decoration: none; }
    .a2hs-later { width: 100%; background: none; border: 0; color: var(--a-muted); padding: 12px; margin-top: 4px; font-size: .9rem; }
</style>

<div class="a2hs-overlay" id="a2hsOverlay" role="dialog" aria-modal="true" aria-labelledby="a2hsTitle">
    <div class="a2hs-sheet">
        <button type="button" class="a2hs-x" id="a2hsX" aria-label="Close">&times;</button>
        <img class="a2hs-logo" src="img/logo.jpg" alt="ජපන් උගනිමු" onerror="this.style.display='none'">
        <div class="a2hs-h" id="a2hsTitle">ජපන් උගනිමු Home Screen ekata add karaganna</div>
        <div class="a2hs-p">Ekama tap ekakin app ekak wage open karanna puluwan. Install karanna one naha.</div>
        <div class="a2hs-steps" id="a2hsSteps"></div>
        <button type="button" class="a2hs-primary" id="a2hsAdd">Home Screen ekata add karanna</button>
        <a class="a2hs-link" href="add-to-home.php">පියවර සම්පූර්ණයෙන් බලන්න →</a>
        <button type="button" class="a2hs-later" id="a2hsLater">Passe</button>
    </div>
</div>

<script>
(function () {
    // Service worker (needed for the native Android install prompt)
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function () {});
        });
    }

    var overlay  = document.getElementById('a2hsOverlay');
    var steps    = document.getElementById('a2hsSteps');
    var addBtn   = document.getElementById('a2hsAdd');
    var laterBtn = document.getElementById('a2hsLater');
    var xBtn     = document.getElementById('a2hsX');

    var KEY_DISMISS  = 'a2hs_dismissed_at';
    var KEY_DONE     = 'a2hs_installed';
    var DISMISS_DAYS = 3;                       // "Passe" -> ask again after 3 days
    var deferredPrompt = null;

    var qs    = location.search;
    var force = qs.indexOf('a2hs=1') !== -1;           // test mode: always show
    var debug = qs.indexOf('a2hs=debug') !== -1;       // debug mode: explain why it is hidden

    var ua = navigator.userAgent || '';
    var isIOS = /iPhone|iPad|iPod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    var isIOSNotSafari = isIOS && /CriOS|FxiOS|EdgiOS|FBAN|FBAV|Instagram|Line\//i.test(ua);   // iPhone Chrome / in-app browsers can't add
    var isMobile = isIOS || /Android/i.test(ua);
    var isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    function get(k)    { try { return localStorage.getItem(k); } catch (e) { return null; } }
    function set(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
    function recentlyDismissed() {
        var t = parseInt(get(KEY_DISMISS) || '0', 10);
        return t && (Date.now() - t) < DISMISS_DAYS * 24 * 60 * 60 * 1000;
    }
    function open()  { overlay.classList.add('show'); requestAnimationFrame(function () { overlay.classList.add('visible'); }); }
    function close(remember) {
        overlay.classList.remove('visible');
        setTimeout(function () { overlay.classList.remove('show'); }, 300);
        if (remember) set(KEY_DISMISS, String(Date.now()));
    }

    // ---- should we show it at all? ----
    var why = '';
    if (!isMobile)                      why = 'this is NOT a phone browser (desktop is ignored)';
    else if (isStandalone)              why = 'the site is already opened as an installed app';
    else if (get(KEY_DONE) === '1')     why = 'a2hs_installed is set in localStorage (already added once)';
    else if (recentlyDismissed())       why = 'you tapped "Passe" / X in the last ' + DISMISS_DAYS + ' days (a2hs_dismissed_at)';

    if (debug) {
        alert('a2hs DEBUG\n' + (why ? 'HIDDEN because: ' + why : 'It SHOULD show in 1.5 seconds') +
              '\n\nTip: add ?a2hs=1 to force it, or clear the two a2hs_ keys.');
        if (why) return;
    } else if (!force && why) {
        return;
    }

    window.addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); deferredPrompt = e; });
    window.addEventListener('appinstalled', function () { set(KEY_DONE, '1'); close(false); });

    addBtn.addEventListener('click', function () {
        // Android Chrome: real one-tap install
        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function (r) {
                if (r && r.outcome === 'accepted') set(KEY_DONE, '1');
                deferredPrompt = null; close(false);
            });
            return;
        }
        // otherwise show manual steps
        if (isIOSNotSafari) {
            steps.innerHTML = '<b>iPhone:</b> meka <b>Safari</b> eken witharai wada karanne.<br>1. Link eka copy karala <b>Safari</b> eke open karanna<br>2. <b>Share ⬆️</b> → <b>Add to Home Screen</b> → <b>Add</b>';
        } else if (isIOS) {
            steps.innerHTML = '<b>iPhone / iPad (Safari):</b><br>1. Yatha thiyena <b>Share ⬆️</b> button eka tap karanna<br>2. <b>Add to Home Screen</b> select karanna<br>3. <b>Add</b> tap karanna';
        } else {
            steps.innerHTML = '<b>Android (Chrome):</b><br>1. Udu thiyena <b>⋮ menu</b> eka tap karanna<br>2. <b>Add to Home screen</b> (ho <b>Install app</b>) select karanna<br>3. <b>Add</b> tap karanna';
        }
        steps.style.display = 'block';
        addBtn.textContent = 'Hari, therunaa';
        addBtn.onclick = function () { close(true); };
    });

    laterBtn.addEventListener('click', function () { close(true); });
    xBtn.addEventListener('click', function () { close(true); });
    overlay.addEventListener('click', function (e) { if (e.target === overlay) close(true); });

    setTimeout(open, 1500);   // small delay so the page loads first
})();
</script>