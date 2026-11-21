<?php
// ---------------------------------------------------------------
// upgrade.php — JLPT packages (N5–N1): Kanji + Grammar + Vocabulary, prices in JPY + live LKR rate,
// activation through a WhatsApp click-to-chat message.
// ---------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) { session_start(); }
ini_set('display_errors', 0);
error_reporting(E_ALL);

// ----- settings -----
$upWhatsApp   = '819050295822';   // +81 09050295822 → country code 81, leading 0 dropped
$upBasePrice  = 4000;             // N5 price (JPY)
$upStep       = 1500;             // every next level costs this much more
$upFallbackRate = 2.0;            // LKR per 1 JPY, used only if the live rate cannot be fetched
$upRateCache  = sys_get_temp_dir() . '/learnjapanes_jpy_lkr.json';
$upRateTtl    = 6 * 3600;         // refresh every 6 hours
// --------------------

$pageTitle       = 'Packages & Upgrade | JLPT N5 – N1 කංජි, ව්‍යාකරණ සහ වචන මාලාව';
$pageDescription = 'JLPT N5 සිට N1 දක්වා කංජි, ව්‍යාකරණ සහ සම්පූර්ණ වචන මාලාව Packages. WhatsApp හරහා පහසුවෙන් activate කරගන්න.';
$pageKeywords    = 'jlpt packages sinhala, jlpt n4 package, jlpt vocabulary sinhala, learnjapanes upgrade';

require_once __DIR__ . '/includes/header.php';

function up_e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** JPY → LKR rate: cached file → open.er-api.com → fallback. Returns [rate, isLive]. */
function up_rate(string $cache, int $ttl, float $fallback): array {
    $stale = null;
    if (is_file($cache)) {
        $j = json_decode((string)@file_get_contents($cache), true);
        if (is_array($j) && !empty($j['rate'])) {
            $stale = (float)$j['rate'];
            if ((time() - (int)($j['t'] ?? 0)) < $ttl) { return [$stale, true]; }
        }
    }
    $ctx = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true]]);
    $raw = @file_get_contents('https://open.er-api.com/v6/latest/JPY', false, $ctx);
    if ($raw) {
        $j = json_decode($raw, true);
        if (isset($j['rates']['LKR']) && (float)$j['rates']['LKR'] > 0) {
            $rate = (float)$j['rates']['LKR'];
            @file_put_contents($cache, json_encode(['rate' => $rate, 't' => time()]), LOCK_EX);
            return [$rate, true];
        }
    }
    return $stale !== null ? [$stale, true] : [$fallback, false];
}

[$upRate, $upLive] = up_rate($upRateCache, $upRateTtl, $upFallbackRate);

$upLevels = ['N5', 'N4', 'N3', 'N2', 'N1'];
$upTitles = [
    'N5' => 'ආරම්භක මට්ටම',
    'N4' => 'මූලික මට්ටම',
    'N3' => 'මධ්‍යම මට්ටම',
    'N2' => 'ඉහළ මධ්‍යම මට්ටම',
    'N1' => 'උසස් මට්ටම',
];
$upPackages = [];
foreach ($upLevels as $i => $code) {
    $yen = $upBasePrice + $i * $upStep;
    $lkr = (int)(round($yen * $upRate / 10) * 10);   // rounded to nearest 10 rupees
    $upPackages[] = ['code' => $code, 'yen' => $yen, 'lkr' => $lkr, 'title' => $upTitles[$code]];
}

$upSelected = strtoupper((string)($_GET['level'] ?? ''));
if (!in_array($upSelected, $upLevels, true)) { $upSelected = ''; }

$upUser = '';
if (!empty($_SESSION['user_id'])) {
    $upUser = 'User ID: ' . (int)$_SESSION['user_id'];
    foreach (['username', 'user_name', 'name', 'email'] as $key) {
        if (!empty($_SESSION[$key]) && is_string($_SESSION[$key])) { $upUser .= ' (' . $_SESSION[$key] . ')'; break; }
    }
}

function up_wa(string $number, array $p, string $user): string {
    $msg = "ආයුබෝවන්! මට JLPT {$p['code']} Package එක activate කරගන්න ඕනේ.\n"
         . "Package: JLPT {$p['code']} (Kanji + Grammar + Vocabulary)\n"
         . "මිල: ¥" . number_format($p['yen']) . " (≈ LKR " . number_format($p['lkr']) . ")\n"
         . ($user !== '' ? $user . "\n" : '')
         . "කරුණාකර payment විස්තර එවන්න.";
    return 'https://wa.me/' . $number . '?text=' . rawurlencode($msg);
}
?>

<style>
    :root {
        --nm-bg: #e6e9f2; --nm-light: #ffffff; --nm-dark: #c3c9dc;
        --nm-text: #2d3250; --nm-muted: #7a8099;
        --nm-brand: #4f46e5; --nm-brand-2: #7c6cf5; --nm-green: #10b981; --nm-wa: #25d366; --nm-red: #ef4444;
        --nm-radius: 24px; --nm-radius-sm: 16px;
        --nm-raised:    8px 8px 18px var(--nm-dark), -8px -8px 18px var(--nm-light);
        --nm-raised-sm: 5px 5px 11px var(--nm-dark), -5px -5px 11px var(--nm-light);
        --nm-inset-sm:  inset 3px 3px 7px var(--nm-dark), inset -3px -3px 7px var(--nm-light);
    }
    body { background: var(--nm-bg) !important; color: var(--nm-text); -webkit-tap-highlight-color: transparent; overflow-x: hidden; }
    .text-muted { color: var(--nm-muted) !important; }
    .jp { font-family: 'Noto Sans JP', sans-serif; }
    @keyframes nmFadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
    @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }

    .up-wrap { padding-bottom: 2rem; }
    .back-btn { display: inline-flex; align-items: center; gap: 6px; background: var(--nm-bg); color: var(--nm-muted); border-radius: 999px; padding: 9px 18px; font-size: .85rem; font-weight: 700; text-decoration: none; box-shadow: var(--nm-raised-sm); }
    .back-btn:hover { color: var(--nm-brand); }
    .back-btn:active { box-shadow: var(--nm-inset-sm); }

    .hero-banner { background: linear-gradient(135deg, var(--nm-brand) 0%, var(--nm-brand-2) 100%); border-radius: var(--nm-radius); color: #fff; padding: 2.2rem 1.5rem; text-align: center; box-shadow: var(--nm-raised); position: relative; overflow: hidden; animation: nmFadeUp .5s ease both; }
    .hero-banner::before, .hero-banner::after { content: ""; position: absolute; border-radius: 50%; background: rgba(255,255,255,.12); }
    .hero-banner::before { width: 150px; height: 150px; top: -55px; right: -30px; }
    .hero-banner::after  { width: 100px; height: 100px; bottom: -40px; left: 10%; }
    .hero-banner > * { position: relative; z-index: 1; }

    .pk-card { background: var(--nm-bg); border-radius: var(--nm-radius); box-shadow: var(--nm-raised); padding: 1.5rem 1.3rem; height: 100%; display: flex; flex-direction: column; position: relative; animation: nmFadeUp .55s ease both; transition: transform .2s ease; }
    .pk-card:hover { transform: translateY(-4px); }
    .pk-card.is-sel { outline: 3px solid var(--nm-brand-2); outline-offset: 3px; }
    .pk-tag { position: absolute; top: 14px; right: 14px; font-size: .7rem; font-weight: 800; color: var(--nm-brand); background: var(--nm-bg); box-shadow: var(--nm-inset-sm); border-radius: 999px; padding: 3px 10px; }
    .pk-level { font-family: 'Noto Sans JP', sans-serif; font-size: 2.4rem; font-weight: 800; line-height: 1.1; background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent; }
    .pk-sub { font-size: .85rem; color: var(--nm-muted); font-weight: 700; margin-bottom: 1rem; }
    .pk-price { background: var(--nm-bg); box-shadow: var(--nm-inset-sm); border-radius: var(--nm-radius-sm); padding: 12px 14px; text-align: center; margin-bottom: 1rem; }
    .pk-yen { font-size: 1.7rem; font-weight: 800; line-height: 1.2; }
    .pk-lkr { font-size: .95rem; font-weight: 700; color: var(--nm-green); }
    .pk-feat { list-style: none; padding: 0; margin: 0 0 1.2rem; display: flex; flex-direction: column; gap: 8px; flex: 1; }
    .pk-feat li { font-size: .9rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
    .pk-feat .ok  { color: var(--nm-green); }
    .pk-feat .no  { color: var(--nm-red); }
    .pk-feat li.off { color: var(--nm-muted); font-weight: 600; }

    .btn-wa { display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; border: 0; border-radius: 999px; padding: 13px 20px; font-weight: 800; font-size: .95rem; text-decoration: none; color: #fff; background: linear-gradient(135deg, #1ebe5a, var(--nm-wa)); box-shadow: var(--nm-raised-sm); transition: transform .2s ease, box-shadow .2s ease; }
    .btn-wa:hover { color: #fff; transform: translateY(-2px); }
    .btn-wa:active { box-shadow: var(--nm-inset-sm); transform: scale(.97); }

    .app-card { background: var(--nm-bg); border-radius: var(--nm-radius); padding: 1.3rem 1.1rem; box-shadow: var(--nm-raised); animation: nmFadeUp .55s ease both; }
    .how-list { list-style: none; counter-reset: s; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px; }
    .how-list li { counter-increment: s; background: var(--nm-bg); box-shadow: var(--nm-inset-sm); border-radius: 14px; padding: 10px 14px 10px 48px; font-size: .9rem; line-height: 1.5; position: relative; font-weight: 600; }
    .how-list li::before { content: counter(s); position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 26px; height: 26px; border-radius: 50%; background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); color: #fff; font-weight: 800; font-size: .8rem; display: flex; align-items: center; justify-content: center; }

    @media (max-width: 575.98px) {
        .hero-banner { padding: 1.7rem 1.1rem; border-radius: 20px; }
        .hero-banner h1 { font-size: 1.7rem !important; }
        .pk-card, .app-card { border-radius: 20px; }
    }
</style>

<div class="up-wrap">

    <a href="javascript:history.length > 1 ? history.back() : (location.href='index.php')" class="back-btn mb-3">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> ආපසු
    </a>

    <div class="hero-banner mb-4 mt-3">
        <h1 class="fw-bold fs-2 mb-2">👑 JLPT Packages</h1>
        <p class="mb-0 opacity-90">N5 සිට N1 දක්වා සම්පූර්ණ කංජි, ව්‍යාකරණ සහ වචන මාලාව විවෘත කරගන්න</p>
    </div>

    <div class="row g-3 g-lg-4 mb-4">
        <?php foreach ($upPackages as $i => $p): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="pk-card<?php echo $upSelected === $p['code'] ? ' is-sel' : ''; ?>" id="pk-<?php echo up_e($p['code']); ?>" style="animation-delay: <?php echo $i * 0.06; ?>s">
                    <?php if ($upSelected === $p['code']): ?><span class="pk-tag">ඔබ තෝරාගත්</span><?php endif; ?>
                    <div class="pk-level">JLPT <?php echo up_e($p['code']); ?></div>
                    <div class="pk-sub"><?php echo up_e($p['title']); ?></div>

                    <div class="pk-price">
                        <div class="pk-yen">¥<?php echo number_format($p['yen']); ?></div>
                        <div class="pk-lkr">≈ LKR <?php echo number_format($p['lkr']); ?></div>
                    </div>

                    <ul class="pk-feat">
                        <li><i class="fa-solid fa-circle-check ok" aria-hidden="true"></i> <?php echo up_e($p['code']); ?> කංජි (සම්පූර්ණ)</li>
                        <li><i class="fa-solid fa-circle-check ok" aria-hidden="true"></i> <?php echo up_e($p['code']); ?> ව්‍යාකරණ (Grammar)</li>
                        <li><i class="fa-solid fa-circle-check ok" aria-hidden="true"></i> <?php echo up_e($p['code']); ?> වචන මාලාව (සම්පූර්ණ ලැයිස්තුව)</li>
                        <li class="off"><i class="fa-solid fa-circle-xmark no" aria-hidden="true"></i> Listening — මෙම Package එකට ඇතුළත් නැත</li>
                        <li class="off"><i class="fa-solid fa-circle-xmark no" aria-hidden="true"></i> Reading — මෙම Package එකට ඇතුළත් නැත</li>
                    </ul>

                    <a class="btn-wa" target="_blank" rel="noopener" href="<?php echo up_e(up_wa($upWhatsApp, $p, $upUser)); ?>">
                        <i class="fa-brands fa-whatsapp" aria-hidden="true"></i> WhatsApp හරහා Activate කරන්න
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="app-card mb-3">
        <h2 class="fs-6 fw-bold mb-3">Package එකක් activate කරගන්නා ආකාරය</h2>
        <ol class="how-list">
            <li>ඔබට අවශ්‍ය Package එකේ <strong>WhatsApp හරහා Activate කරන්න</strong> බොත්තම click කරන්න.</li>
            <li>WhatsApp විවෘත වී Package විස්තර සහිත පණිවිඩය ස්වයංක්‍රීයව සකස් වී ඇත — එය <strong>Send</strong> කරන්න.</li>
            <li>Payment තහවුරු කළ පසු ඔබගේ Package එක activate කරනු ලැබේ.</li>
        </ol>
        <p class="small text-muted mt-3 mb-0">
            LKR මිල ආසන්න අගයකි<?php echo $upLive ? ' (අද දින JPY → LKR විනිමය අනුපාතය අනුව, 1 JPY ≈ ' . number_format($upRate, 3) . ' LKR)' : ' (ආසන්න අනුපාතයක් භාවිතා කර ඇත)'; ?>.
            වෙනස් විය හැක. දැනට Packages වල ඇත්තේ කංජි, ව්‍යාකරණ සහ වචන මාලාව පමණි.
        </p>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>