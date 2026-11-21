<?php
/**
 * includes/bottom-nav.php  -  phone + tablet bottom navigation bar (app style)
 *
 * footer.php already includes this ONCE, so every page gets the bar automatically.
 * Visible below 992px (phones + iPad/tablets). Hidden on desktop.
 *
 * Tabs: Home · Dictionary · Books · JLPT · Contact · Dashboard
 * (Dashboard tab -> dashboard page if logged in, otherwise the login page)
 */
if (defined('BOTTOM_NAV_RENDERED')) { return; }   // never render twice
define('BOTTOM_NAV_RENDERED', true);

$__lang = (isset($currentLang) && in_array($currentLang, ['si', 'en', 'ja'], true)) ? $currentLang : 'si';

// Values from header.php (with safe fallbacks if this file is used on its own)
$__loggedIn = isset($isLoggedIn)   ? (bool) $isLoggedIn : !empty($_SESSION['user_id']);
$__dashUrl  = isset($dashboardUrl) ? $dashboardUrl      : 'dashboard.php';
$__loginUrl = isset($loginUrl)     ? $loginUrl          : 'login.php';
$__contact  = isset($contactUrl)   ? $contactUrl        : 'contact.php';

$__labels = [
    'si' => ['home' => 'මුල්පිටුව', 'dict' => 'කෝෂය',       'books' => 'පොත්',  'jlpt' => 'JLPT', 'contact' => 'සම්බන්ධ',  'dash' => 'ඩෑෂ්බෝඩ්'],
    'en' => ['home' => 'Home',      'dict' => 'Dictionary', 'books' => 'Books', 'jlpt' => 'JLPT', 'contact' => 'Contact',  'dash' => 'Dashboard'],
    'ja' => ['home' => 'ホーム',     'dict' => '辞典',        'books' => '書籍',   'jlpt' => 'JLPT', 'contact' => '連絡',      'dash' => 'マイページ'],
][$__lang];

$__cur = basename(parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH) ?: '');

// 'pages' = which files should light up this tab as "active"
$__items = [
    ['key' => 'home',    'href' => 'index.php',      'icon' => 'fa-house',          'pages' => ['index.php', '']],
    ['key' => 'dict',    'href' => 'dictionary.php', 'icon' => 'fa-book-open',      'pages' => ['dictionary.php']],
    ['key' => 'books',   'href' => 'books.php',      'icon' => 'fa-book',           'pages' => ['books.php', 'book_detail.php', 'cart.php']],
    ['key' => 'jlpt',    'href' => 'n5.php',         'icon' => 'fa-graduation-cap', 'pages' => ['n5.php', 'n4.php', 'n3.php', 'n2.php', 'n1.php', 'w-grammar.php']],
    ['key' => 'contact', 'href' => $__contact,       'icon' => 'fa-envelope',       'pages' => [basename($__contact)]],
    ['key' => 'dash',    'href' => $__loggedIn ? $__dashUrl : $__loginUrl,
                         'icon' => 'fa-user-gear',   'pages' => [basename($__dashUrl), basename($__loginUrl)]],
];
?>
<style>
    .m-nav { display: none; }
    @media (max-width: 991.98px) {
        body { padding-bottom: calc(86px + env(safe-area-inset-bottom)) !important; }
        .m-nav {
            display: flex; gap: 4px;
            position: fixed; left: 0; right: 0; bottom: 0; z-index: 1040;
            padding: 10px 10px calc(10px + env(safe-area-inset-bottom));
            background: var(--nm-bg, #e6e9f2);
            border-radius: 24px 24px 0 0;
            box-shadow: 0 -8px 20px rgba(195, 201, 220, .85), 0 -2px 0 rgba(255, 255, 255, .8);
            transition: transform .2s ease;
        }
        .m-nav.m-nav-hide { transform: translateY(110%); }
        .m-nav-item {
            flex: 1 1 0; min-width: 0;
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;
            padding: 9px 2px; border-radius: 16px;
            color: var(--nm-muted, #7a8099); text-decoration: none;
            font-size: .64rem; font-weight: 700; line-height: 1.1;
            transition: box-shadow .2s ease, color .2s ease, transform .2s ease;
            -webkit-tap-highlight-color: transparent;
        }
        .m-nav-item i { font-size: 1.12rem; }
        .m-nav-item span { max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .m-nav-item:active { transform: scale(.94); }
        .m-nav-item.active {
            color: var(--nm-brand, #4f46e5);
            box-shadow: inset 3px 3px 7px var(--nm-dark, #c3c9dc), inset -3px -3px 7px var(--nm-light, #fff);
        }
    }
    /* tablets: a bit more breathing room, bar stays centred */
    @media (min-width: 768px) and (max-width: 991.98px) {
        .m-nav { padding-left: 8%; padding-right: 8%; }
        .m-nav-item { font-size: .72rem; }
        .m-nav-item i { font-size: 1.25rem; }
    }
</style>

<nav class="m-nav" id="mNav" aria-label="Main navigation">
    <?php foreach ($__items as $__it):
        $__active = in_array($__cur, $__it['pages'], true);
    ?>
        <a class="m-nav-item<?php echo $__active ? ' active' : ''; ?>"
           href="<?php echo htmlspecialchars($__it['href'], ENT_QUOTES, 'UTF-8'); ?>"
           <?php if ($__active): ?>aria-current="page"<?php endif; ?>>
            <i class="fa-solid <?php echo $__it['icon']; ?>" aria-hidden="true"></i>
            <span><?php echo htmlspecialchars($__labels[$__it['key']], ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<script>
// hide the bar while the on-screen keyboard is open (search boxes, forms)
(function () {
    var nav = document.getElementById('mNav');
    if (!nav) return;
    var isField = function (t) { return t && /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName); };
    document.addEventListener('focusin',  function (e) { if (isField(e.target)) nav.classList.add('m-nav-hide'); });
    document.addEventListener('focusout', function ()  { nav.classList.remove('m-nav-hide'); });
})();
</script>
