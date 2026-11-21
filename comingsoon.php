<?php
/**
 * learnjapanese.jp - Coming Soon page
 *
 * Same neumorphism theme + includes/header.php + includes/footer.php as index.php / dictionary.php.
 * Put it next to index.php (public_html) and link any unfinished feature to it:
 *
 *   comingsoon.php                      -> generic message
 *   comingsoon.php?f=Mock+Tests         -> "Mock Tests" is coming soon   (feature name shown in the title)
 *
 * >>> Edit the 4 settings just below (launch date, WhatsApp number, links). <<<
 */

require_once __DIR__ . '/config.php';

/* ================================ SETTINGS ================================ */
$CS_LAUNCH_DATE = '';                 // e.g. '2026-12-01 09:00:00' (Japan time). Leave '' to hide the countdown.
$CS_WHATSAPP    = '';                 // e.g. '81901234567' (country code + number, no + or spaces). '' hides the WhatsApp button.
$CS_HOME        = 'index.php';
$CS_LINKS       = [                   // quick links shown under the card: [url, label_si, label_en, label_ja]
    ['words_according_to_level.php', '📚 මට්ටම අනුව වචන', '📚 Vocabulary by level', '📚 レベル別単語'],
    ['dictionary.php',               '📖 වචන කෝෂය',        '📖 Dictionary',          '📖 辞典'],
    ['books.php',                    '📕 පොත්',            '📕 Books',               '📕 本'],
];
/* ========================================================================== */

/* optional feature name from ?f=  (plain text, max 50 chars) */
$csFeature = trim(preg_replace('/\s+/u', ' ', (string) ($_GET['f'] ?? '')));
$csFeature = preg_replace('/^(.{0,50}).*$/us', '$1', $csFeature);

$csT = [
  'si' => ['title' => 'ඉක්මනින් එනවා', 'badge' => 'Coming Soon',
           'p'  => 'අපි මේක ලස්සනට හදමින් ඉන්නවා. ටික කාලෙකින් ඔබට භාවිතා කරන්න පුළුවන් වෙයි.',
           'pf' => ' ඉක්මනින් ඔබ වෙත එනවා. අපි තවම හදමින් ඉන්නවා.',
           'home' => '🏠 මුල් පිටුවට', 'wa' => '💬 WhatsApp එකෙන් දැනුම් දෙන්න', 'also' => 'ඒ අතරතුර මේවා බලන්න',
           'cd' => ['දින', 'පැය', 'මිනි', 'තත්'], 'launched' => 'දැන් එළිදක්වනවා!', 'wa_msg' => 'ඉක්මනින් එන දේ ගැන දැනුම් දෙන්න'],
  'en' => ['title' => 'Coming Soon', 'badge' => 'Coming Soon',
           'p'  => 'We are building something nice here. It will be ready for you very soon.',
           'pf' => ' is coming your way soon. We are still working on it.',
           'home' => '🏠 Back to home', 'wa' => '💬 Notify me on WhatsApp', 'also' => 'Meanwhile, try these',
           'cd' => ['Days', 'Hours', 'Min', 'Sec'], 'launched' => 'Launching now!', 'wa_msg' => 'Please let me know when this is ready'],
  'ja' => ['title' => 'まもなく公開', 'badge' => 'Coming Soon',
           'p'  => '現在準備中です。もうしばらくお待ちください。',
           'pf' => ' は近日公開予定です。現在準備中です。',
           'home' => '🏠 ホームへ', 'wa' => '💬 WhatsAppで通知を受け取る', 'also' => 'それまでこちらをどうぞ',
           'cd' => ['日', '時間', '分', '秒'], 'launched' => 'まもなく公開！', 'wa_msg' => '公開されたら教えてください'],
];
$csLang = (isset($currentLang) && isset($csT[$currentLang])) ? $currentLang : 'si';
$csL    = $csT[$csLang];
$csIdx  = ['si' => 1, 'en' => 2, 'ja' => 3][$csLang];

/* launch timestamp (ms, Tokyo time) for the JS countdown */
$csTs = 0;
if ($CS_LAUNCH_DATE !== '') {
    try { $csTs = (new DateTime($CS_LAUNCH_DATE, new DateTimeZone('Asia/Tokyo')))->getTimestamp() * 1000; } catch (Throwable $e) { $csTs = 0; }
}
$csWa = preg_replace('/\D/', '', $CS_WHATSAPP);

function cs_h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

http_response_code(200);
$pageTitle = $csFeature !== '' ? $csFeature . ' · ' . $csL['badge'] : $csL['title'];
/* NOTE: header.php may reuse generic variable names, so every variable here is cs-prefixed. */
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
    @keyframes csUp    { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
    @keyframes csFloat { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-9px); } }
    @keyframes csPulse { 0%,100% { box-shadow: var(--nm-raised); } 50% { box-shadow: 10px 10px 26px var(--nm-dark), -10px -10px 26px var(--nm-light); } }
    @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }

    .cs-wrap { max-width: 640px; margin: 18px auto 34px; }
    .cs-card { background: var(--nm-bg); border-radius: var(--nm-radius); box-shadow: var(--nm-raised); padding: 34px 22px 28px;
               text-align: center; animation: csUp .5s ease both; position: relative; overflow: hidden; }
    .cs-ico { width: 104px; height: 104px; margin: 0 auto 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
              font-size: 3rem; background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); color: #fff;
              animation: csFloat 3.2s ease-in-out infinite, csPulse 3.2s ease-in-out infinite; }
    .cs-badge { display: inline-block; font-size: .72rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--nm-brand);
                padding: 6px 16px; border-radius: 999px; box-shadow: var(--nm-inset-sm); margin-bottom: 14px; }
    .cs-card h1 { font-weight: 800; font-size: 1.7rem; margin: 0 0 10px; line-height: 1.3; word-break: break-word; }
    .cs-card p  { color: var(--nm-muted); font-size: 1rem; line-height: 1.7; margin: 0 auto; max-width: 460px; }
    .cs-ja { font-size: 1.15rem; font-weight: 700; color: var(--nm-brand); margin-top: 12px; letter-spacing: .08em; }

    .cs-cd { display: none; gap: 12px; justify-content: center; margin: 24px 0 4px; }
    .cs-cd.on { display: flex; }
    .cs-cd div { min-width: 66px; padding: 12px 6px; border-radius: var(--nm-radius-sm); box-shadow: var(--nm-inset-sm); }
    .cs-cd b { display: block; font-size: 1.6rem; font-weight: 800; color: var(--nm-brand); line-height: 1.1; font-variant-numeric: tabular-nums; }
    .cs-cd span { font-size: .72rem; color: var(--nm-muted); font-weight: 700; }
    .cs-live { display: none; margin-top: 20px; font-weight: 800; color: #10b981; }

    .cs-act { display: flex; flex-wrap: wrap; gap: 14px; justify-content: center; margin-top: 26px; }
    .cs-btn { text-decoration: none; font-weight: 800; font-size: .92rem; padding: 13px 24px; border-radius: 999px; border: 0;
              background: var(--nm-bg); color: var(--nm-brand); box-shadow: var(--nm-raised-sm); transition: box-shadow .2s ease, transform .2s ease; }
    .cs-btn:hover { color: var(--nm-brand); transform: translateY(-2px); }
    .cs-btn:active { box-shadow: var(--nm-inset-sm); transform: scale(.98); }
    .cs-btn.pri { background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); color: #fff; }
    .cs-btn.pri:hover { color: #fff; }
    .cs-btn.wa { background: linear-gradient(135deg, #10b981, #34d399); color: #fff; }
    .cs-btn.wa:hover { color: #fff; }

    .cs-also { margin-top: 26px; text-align: center; animation: csUp .6s ease .1s both; }
    .cs-also small { display: block; color: var(--nm-muted); font-weight: 700; margin-bottom: 12px; }
    .cs-also a { display: inline-block; margin: 5px; text-decoration: none; color: var(--nm-text); font-weight: 700; font-size: .85rem;
                 padding: 9px 18px; border-radius: 999px; background: var(--nm-bg); box-shadow: var(--nm-raised-sm); }
    .cs-also a:active { box-shadow: var(--nm-inset-sm); }
</style>

<div class="cs-wrap">
    <div class="cs-card">
        <div class="cs-ico" aria-hidden="true">🚀</div>
        <span class="cs-badge"><?php echo cs_h($csL['badge']); ?></span>

        <?php if ($csFeature !== ''): ?>
            <h1><?php echo cs_h($csFeature); ?></h1>
            <p><?php echo cs_h(ltrim($csL['pf'])); ?></p>
        <?php else: ?>
            <h1><?php echo cs_h($csL['title']); ?></h1>
            <p><?php echo cs_h($csL['p']); ?></p>
        <?php endif; ?>
        <div class="cs-ja">もうすぐ公開 ・ 準備中</div>

        <?php if ($csTs): ?>
            <div class="cs-cd" id="csCd" aria-live="off">
                <div><b id="csD">0</b><span><?php echo cs_h($csL['cd'][0]); ?></span></div>
                <div><b id="csH">0</b><span><?php echo cs_h($csL['cd'][1]); ?></span></div>
                <div><b id="csM">0</b><span><?php echo cs_h($csL['cd'][2]); ?></span></div>
                <div><b id="csS">0</b><span><?php echo cs_h($csL['cd'][3]); ?></span></div>
            </div>
            <div class="cs-live" id="csLive">🎉 <?php echo cs_h($csL['launched']); ?></div>
        <?php endif; ?>

        <div class="cs-act">
            <a class="cs-btn pri" href="<?php echo cs_h($CS_HOME); ?>"><?php echo cs_h($csL['home']); ?></a>
            <?php if ($csWa !== ''): ?>
                <a class="cs-btn wa" target="_blank" rel="noopener"
                   href="https://wa.me/<?php echo cs_h($csWa); ?>?text=<?php echo rawurlencode(($csFeature !== '' ? $csFeature . ' - ' : '') . $csL['wa_msg']); ?>"><?php echo cs_h($csL['wa']); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($CS_LINKS): ?>
        <div class="cs-also">
            <small><?php echo cs_h($csL['also']); ?></small>
            <?php foreach ($CS_LINKS as $csLk): ?>
                <a href="<?php echo cs_h($csLk[0]); ?>"><?php echo cs_h($csLk[$csIdx]); ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($csTs): ?>
<script>
(function () {
  'use strict';
  var end = <?php echo (int) $csTs; ?>;
  var box = document.getElementById('csCd'), live = document.getElementById('csLive');
  var d = document.getElementById('csD'), h = document.getElementById('csH'), m = document.getElementById('csM'), s = document.getElementById('csS');
  function two(n) { return n < 10 ? '0' + n : '' + n; }
  function tick() {
    var left = Math.floor((end - Date.now()) / 1000);
    if (left <= 0) { box.classList.remove('on'); live.style.display = 'block'; return false; }
    box.classList.add('on');
    d.textContent = Math.floor(left / 86400); h.textContent = two(Math.floor(left % 86400 / 3600));
    m.textContent = two(Math.floor(left % 3600 / 60)); s.textContent = two(left % 60);
    return true;
  }
  if (tick()) { var t = setInterval(function () { if (!tick()) clearInterval(t); }, 1000); }
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
