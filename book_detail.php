<?php
// Include Data
require_once __DIR__ . '/includes/shop-data.php';

// Safe fallbacks if $st or $shopCurrency aren't initialized in shop-data.php
$shopCurrency = $shopCurrency ?? 'usd';
$st = $st ?? [
    'books_nav' => 'පොත්',
    'back_to_books' => 'පොත් වෙතට ආපසු',
    'book_cover' => 'පොත් කවරය',
    'free_shipping_note' => 'ජපානය තුළ නොමිලේ බෙදාහැරීම',
    'sri_lanka_shipping_note' => 'ශ්‍රී ලංකාවට බෙදාහැරීම: ¥1,900',
    'quantity' => 'ප්‍රමාණය',
    'buy_now' => 'දැන් මිලදී ගන්න',
    'secure_note' => 'Stripe හරහා ආරක්ෂිත ගෙවීමක්',
    'whats_inside' => 'ඇතුළත මොනවාද',
    'about_book' => 'මෙම පොත ගැන',
    'shipping_title' => 'බෙදාහැරීමේ ලිපිනය',
    'shipping_sub' => 'කරුණාකර පහත බෙදාහැරීමේ විස්තර පුරවන්න.',
    'full_name' => 'සම්පූර්ණ නම',
    'phone' => 'දුරකථන අංකය',
    'email' => 'විද්‍යුත් තැපැල් ලිපිනය',
    'address_line' => 'ලිපිනය',
    'city' => 'නගරය',
    'postal_code' => 'තැපැල් කේතය',
    'country' => 'රට',
    'select_country' => 'බෙදාහැරීමේ රට තෝරන්න',
    'shipping_fee_label' => 'බෙදාහැරීමේ ගාස්තුව',
    'total' => 'මුළු එකතුව',
    'continue_to_payment' => 'ගෙවීමට යන්න'
];

// --- TEMPORARY: WhatsApp ordering --------------------------------------
// WhatsApp number in international format WITHOUT "+" and WITHOUT the
// leading 0 after the country code (wa.me requires this format).
define('WHATSAPP_NUMBER', '819050295822');
define('WHATSAPP_NUMBER_DISPLAY', '+81 90-5029-5822');

// How-to-pay pages (open in a new tab)
define('KYASH_PAGE_URL', 'kyash.php');
define('PAYPAY_PAGE_URL', 'paypay.php');

// Texts for the temporary WhatsApp flow (kept local so shop-data.php
// doesn't need to change).
$wa = [
    'modal_title'  => 'ගෙවීම් විස්තර ලබාගන්න',
    'modal_desc'   => 'ගෙවීම් කරන ආකාරය පිළිබඳ විස්තර ලබාගැනීමට කරුණාකර අපගේ WhatsApp අංකයට පණිවිඩයක් එවන්න. පහත බොත්තම එබූ විට ඔබේ ඇණවුමේ විස්තර සහිත පණිවිඩයක් සකසා WhatsApp විවෘත වේ.',
    'order_label'  => 'ඔබේ ඇණවුම',
    'our_number'   => 'අපගේ WhatsApp අංකය',
    'open_wa'      => 'WhatsApp විවෘත කරන්න',
    'wa_note'      => 'බෙදාහැරීමේ ගාස්තුව සහ ගෙවීම් ක්‍රම WhatsApp හරහා තහවුරු කරන්නෙමු.',
    'close'        => 'වසන්න',
    // Kyash / PayPay
    'apps_title'   => 'Kyash හෝ PayPay app එකෙන් ගෙවන්න',
    'apps_desc'    => 'App එක install කරන ආකාරය, top up කරන ආකාරය සහ අපට මුදල් යවන ආකාරය බලන්න.',
    'kyash_btn'    => 'Kyash මගින් ගෙවන ආකාරය බලන්න',
    'paypay_btn'   => 'PayPay මගින් ගෙවන ආකාරය බලන්න',
    'kyash_link'   => 'Kyash මගින් ගෙවන්නේ කෙසේද?',
    'paypay_link'  => 'PayPay මගින් ගෙවන්නේ කෙසේද?',
];

// Sinhala-only site
$currentLang = 'si';

$siteUrl  = 'https://learnjapanese.jp';
$basePath = strtok($_SERVER['REQUEST_URI'], '?');
$pageUrl  = $siteUrl . $basePath;

$bookId = $_GET['id'] ?? '';
if (!isset($books[$bookId])) {
    http_response_code(404);
    $bookId = null;
}
$book = $bookId ? $books[$bookId] : null;

// --- Discount handling -----------------------------------------------
// If a discount_price_cents is set (and it's lower than the normal price),
// that becomes the *effective* price everywhere on this page: display AND
// what actually gets charged at checkout.
$hasDiscount = $book
    && !empty($book['discount_price_cents'])
    && $book['discount_price_cents'] < $book['price_cents'];
$effectivePriceCents = $book
    ? ($hasDiscount ? (int) $book['discount_price_cents'] : (int) $book['price_cents'])
    : 0;
$discountPct = $hasDiscount
    ? (int) round((1 - $book['discount_price_cents'] / $book['price_cents']) * 100)
    : 0;

// Pull in Stripe Config
define('SHOP_BOOTSTRAP', true);
if (file_exists(__DIR__ . '/checkout/config.php')) {
    require_once __DIR__ . '/checkout/config.php';
}

// TEMPORARILY DISABLED: Stripe Buy Button (WhatsApp ordering is used instead)
// To re-enable Stripe, delete the "$useBuyButton = false;" line below and
// remove the // from the original line.
// $useBuyButton = $book && !empty($book['buy_button_id']) && defined('STRIPE_PUBLISHABLE_KEY');
$useBuyButton = false;

// --- Shipping / currency-display config ---------------------------------
// NOTE: This shipping fee amount MUST also be enforced server-side in
// checkout/create-checkout-session.php — never trust the country/fee the
// client sends. See the note at the bottom of this file's <script> block.
if (!defined('SRI_LANKA_SHIPPING_JPY')) {
    define('SRI_LANKA_SHIPPING_JPY', 1900); // must match SRI_LANKA_SHIPPING_JPY in checkout/create-checkout-session.php
}

// --- Live JPY -> LKR rate ------------------------------------------------
// Fetches today's rate from a free API (no key needed), caches it for
// 6 hours, and falls back to the last cached value (or a fixed number)
// if the API is unreachable. DISPLAY-ONLY — actual charge is always in JPY.
if (!defined('JPY_LKR_FALLBACK_RATE')) {
    define('JPY_LKR_FALLBACK_RATE', 2.05);   // used only if API + cache both fail
}
if (!defined('JPY_LKR_CACHE_TTL')) {
    define('JPY_LKR_CACHE_TTL', 6 * 3600);   // refresh every 6 hours
}

if (!function_exists('get_jpy_lkr_rate')) {
    function get_jpy_lkr_rate() {
        $cacheFile = sys_get_temp_dir() . '/learnjapanese_jpy_lkr_rate.json';

        // 1) Fresh cache?
        $cached = null;
        if (is_readable($cacheFile)) {
            $decoded = json_decode((string) @file_get_contents($cacheFile), true);
            if (is_array($decoded) && isset($decoded['rate'], $decoded['time'])) {
                $cached = $decoded;
                if ((time() - (int) $decoded['time']) < JPY_LKR_CACHE_TTL) {
                    return (float) $decoded['rate'];
                }
            }
        }

        // 2) Fetch a new rate
        $url  = 'https://open.er-api.com/v6/latest/JPY';
        $body = false;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 4,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $body = curl_exec($ch);
            curl_close($ch);
        } elseif (ini_get('allow_url_fopen')) {
            $ctx  = stream_context_create(['http' => ['timeout' => 4]]);
            $body = @file_get_contents($url, false, $ctx);
        }

        if ($body) {
            $json = json_decode($body, true);
            $rate = $json['rates']['LKR'] ?? null;
            // sanity check (1 JPY is roughly 1.5 - 3 LKR)
            if (is_numeric($rate) && $rate > 0.5 && $rate < 5) {
                @file_put_contents(
                    $cacheFile,
                    json_encode(['rate' => (float) $rate, 'time' => time()]),
                    LOCK_EX
                );
                return (float) $rate;
            }
        }

        // 3) API failed -> use stale cache if we have one, else fallback
        if ($cached) {
            return (float) $cached['rate'];
        }
        return (float) JPY_LKR_FALLBACK_RATE;
    }
}

if (!defined('JPY_TO_LKR_RATE')) {
    define('JPY_TO_LKR_RATE', get_jpy_lkr_rate());
}

// Price Formatter Function Fallback
if (!function_exists('format_price')) {
    function format_price($cents, $curr) {
        if (strtolower($curr) === 'jpy') return '¥' . number_format($cents);
        $symbols = ['usd' => '$', 'lkr' => 'Rs. '];
        $symbol = $symbols[strtolower($curr)] ?? strtoupper($curr) . ' ';
        return $symbol . number_format($cents / 100, 2);
    }
}

// Display helper: appends an approx LKR conversion when the shop's real
// currency is JPY. Display-only — never used for charging.
if (!function_exists('display_price')) {
    function display_price($amount, $curr, $lang) {
        $base = format_price($amount, $curr);
        if ($lang === 'si' && strtolower($curr) === 'jpy') {
            $lkr = (int) round($amount * JPY_TO_LKR_RATE);
            $base .= ' <span class="lkr-approx">(≈ Rs. ' . number_format($lkr) . ')</span>';
        }
        return $base;
    }
}

// --- SEO: per-book title/description/keywords/canonical, picked up by
// includes/header.php, targeting both Sri Lanka and the Sri Lankan
// community living in Japan.
if ($book) {
    $bookTitle       = $book['title']['si'] ?? ($book['title']['en'] ?? reset($book['title']));
    $bookTagline     = $book['tagline']['si'] ?? ($book['tagline']['en'] ?? '');
    $pageTitle       = $bookTitle . ' — JLPT ' . $book['level'] . ' | ලංකාවේ සහ ජපානයේ ඉන්න ශ්‍රී ලාංකිකයන් සඳහා';
    $pageDescription = $bookTagline !== ''
        ? $bookTagline . ' ලංකාවට හෝ ජපානයේ ලිපිනයකට බෙදාහරිනවා.'
        : 'JLPT ' . $book['level'] . ' සඳහා සිංහලෙන් සම්පූර්ණ වචන මාලා පොත. ලංකාවට හෝ ජපානයේ ලිපිනයකට බෙදාහරිනවා.';
    $pageKeywords    = 'JLPT ' . $book['level'] . ' book sinhala, JLPT ' . $book['level'] . ' vocabulary book, jlpt sinhala book sri lanka, jlpt book japan delivery, sri lankans in japan jlpt book';
    $pageCanonical   = $siteUrl . $basePath . '?id=' . urlencode($bookId);
} else {
    $pageTitle       = 'පොත සොයාගත නොහැක | learnjapanese.jp';
    $pageDescription = 'ඔබ සොයන පොත සොයාගත නොහැක. අපගේ JLPT වචන මාලා පොත් සියල්ල මෙතනින් බලන්න.';
    $pageCanonical   = $pageUrl;
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
        /* =========================================================
           BOOK DETAIL — NEUMORPHISM
           ========================================================= */
        .breadcrumb-link {
            display: inline-flex; align-items: center; gap: 6px;
            color: var(--nm-muted); text-decoration: none;
            font-size: .85rem; font-weight: 700;
            background: var(--nm-bg); border-radius: 999px;
            padding: 8px 16px; box-shadow: var(--nm-raised-sm);
            transition: box-shadow .2s ease, color .2s ease;
        }
        .breadcrumb-link:hover  { color: var(--nm-brand); }
        .breadcrumb-link:active { box-shadow: var(--nm-inset-sm); }

        .product-wrap { display: grid; grid-template-columns: 380px 1fr; gap: 2.25rem; align-items: start; }
        @media (max-width: 860px) { .product-wrap { grid-template-columns: 1fr; gap: 1.75rem; } }

        .cover-frame {
            background: var(--nm-bg);
            border-radius: var(--nm-radius);
            padding: 1.1rem;
            box-shadow: var(--nm-raised);
            position: sticky; top: 90px;
        }
        .cover-frame img {
            width: 100%; aspect-ratio: 210 / 297; object-fit: cover;
            border-radius: var(--nm-radius-sm); display: block;
            background: var(--nm-bg);
            box-shadow: 4px 4px 14px rgba(45, 50, 80, .28);
        }
        @media (max-width: 860px) { .cover-frame { position: static; max-width: 320px; margin: 0 auto; } }

        .level-pill, .discount-pill {
            display: inline-block; font-weight: 800; font-size: .8rem;
            padding: 6px 15px; border-radius: 999px; margin-bottom: .7rem;
        }
        .level-pill    { background: var(--nm-bg); color: var(--nm-brand); box-shadow: var(--nm-inset-sm); }
        .discount-pill { background: var(--nm-bg); color: #16a34a; box-shadow: var(--nm-raised-sm); margin-left: 8px; }

        .product-title   { font-size: 1.7rem; font-weight: 800; line-height: 1.3; margin-bottom: .4rem; color: var(--nm-text); }
        .product-tagline { color: var(--nm-muted); font-size: 1rem; margin-bottom: 1.1rem; line-height: 1.6; }

        .product-price-row {
            display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap;
            margin-bottom: 1.1rem; padding: 14px 18px;
            border-radius: var(--nm-radius-sm); box-shadow: var(--nm-inset-sm);
            background: var(--nm-bg);
        }
        .product-price     { font-size: 1.85rem; font-weight: 800; color: var(--nm-brand); }
        .product-price-old { font-size: 1.1rem; font-weight: 600; color: var(--nm-muted); text-decoration: line-through; }
        .lkr-approx        { font-size: .92rem; font-weight: 600; color: var(--nm-muted); margin-left: 4px; }
        .ship-note         { color: var(--nm-muted); font-size: .85rem; line-height: 1.6; }

        /* Quantity */
        .qty-row { display: flex; align-items: center; gap: .9rem; margin: 1.4rem 0; flex-wrap: wrap; }
        .qty-control {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px; border-radius: 999px; background: var(--nm-bg);
            box-shadow: var(--nm-inset-sm);
        }
        .qty-control button {
            background: var(--nm-bg); border: none; width: 38px; height: 38px;
            border-radius: 50%; font-size: 1.1rem; font-weight: 800; color: var(--nm-brand);
            cursor: pointer; box-shadow: 3px 3px 7px var(--nm-dark), -3px -3px 7px var(--nm-light);
            transition: box-shadow .2s ease, transform .2s ease;
        }
        .qty-control button:active { box-shadow: var(--nm-inset-sm); transform: scale(.94); }
        .qty-control input {
            width: 44px; text-align: center; border: none; background: transparent;
            height: 38px; font-weight: 800; color: var(--nm-text);
        }
        .qty-control input:focus { outline: none; }

        /* Buy button */
        .btn-buy {
            background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2));
            color: #fff; font-weight: 800; border-radius: 999px;
            padding: 15px 28px; border: none; font-size: 1.02rem;
            width: 100%; max-width: 380px;
            box-shadow: var(--nm-raised);
            transition: box-shadow .2s ease, transform .2s ease;
        }
        .btn-buy:hover  { color: #fff; transform: translateY(-2px); }
        .btn-buy:active { box-shadow: var(--nm-inset); transform: scale(.98); }
        .btn-buy:disabled { opacity: .65; cursor: wait; }
        .stripe-buy-wrap { max-width: 380px; }

        /* Kyash / PayPay inline links */
        .pay-links { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 1.2rem; }
        .kyash-inline-link, .paypay-inline-link {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: .85rem; font-weight: 700; text-decoration: none;
            border-radius: 999px; padding: 9px 16px;
            background: var(--nm-bg); box-shadow: var(--nm-raised-sm);
            transition: box-shadow .2s ease, transform .2s ease;
        }
        .kyash-inline-link  { color: var(--nm-brand); }
        .paypay-inline-link { color: #e11d48; }
        .kyash-inline-link:hover  { color: var(--nm-brand); transform: translateY(-1px); }
        .paypay-inline-link:hover { color: #be123c; transform: translateY(-1px); }
        .kyash-inline-link:active, .paypay-inline-link:active { box-shadow: var(--nm-inset-sm); transform: scale(.98); }

        /* Description + gallery */
        .product-description-box {
            background: var(--nm-bg); border-radius: var(--nm-radius);
            box-shadow: var(--nm-raised); padding: 1.4rem 1.3rem; margin-top: 1.5rem;
        }
        .whats-inside-title { font-weight: 800; font-size: 1.15rem; margin: 2.5rem 0 1.1rem; color: var(--nm-text); }
        .product-description-box .whats-inside-title { margin-top: 0 !important; }
        .product-description-text { color: var(--nm-text); font-size: .98rem; line-height: 1.8; white-space: pre-line; margin: 0; }

        .gallery-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; padding: 4px; }
        @media (max-width: 560px) { .gallery-grid { grid-template-columns: 1fr; } }
        .gallery-item {
            background: var(--nm-bg); border-radius: var(--nm-radius-sm);
            box-shadow: var(--nm-raised-sm); padding: 10px; overflow: hidden;
            transition: box-shadow .25s ease;
        }
        .gallery-item:hover { box-shadow: var(--nm-raised); }
        .gallery-item img {
            width: 100%; aspect-ratio: 4 / 3; object-fit: cover; display: block;
            background: var(--nm-bg); cursor: zoom-in; border-radius: 12px;
            box-shadow: var(--nm-inset-sm);
        }
        .gallery-item .cap { padding: .75rem .5rem .25rem; font-size: .85rem; color: var(--nm-muted); }

        /* Modals (shared) */
        .modal-content {
            background: var(--nm-bg); border-radius: var(--nm-radius); border: none;
            box-shadow: 12px 12px 30px rgba(0,0,0,.28), -6px -6px 20px rgba(255,255,255,.25);
        }
        .btn-close {
            background-color: var(--nm-bg); border-radius: 50%; opacity: 1;
            width: 34px; height: 34px; padding: 0; background-size: 12px;
            box-shadow: var(--nm-raised-sm);
        }
        .btn-close:active { box-shadow: var(--nm-inset-sm); }

        .form-label { font-weight: 700; font-size: .85rem; color: var(--nm-text); }
        .form-label .req-mark { color: #dc2626; margin-left: 2px; }
        .form-control, .form-select {
            background-color: var(--nm-bg); color: var(--nm-text);
            border: 0; border-radius: 14px; padding: .7rem 1rem;
            box-shadow: var(--nm-inset-sm);
        }
        .form-control:focus, .form-select:focus {
            background-color: var(--nm-bg); color: var(--nm-text);
            box-shadow: var(--nm-inset-sm), 0 0 0 2px rgba(79,70,229,.35);
        }
        .order-summary-box { background: var(--nm-bg); border-radius: var(--nm-radius-sm); box-shadow: var(--nm-inset-sm); padding: 1rem 1.1rem; font-size: .9rem; }
        .summary-old-price { text-decoration: line-through; color: var(--nm-muted); font-size: .8rem; margin-right: 6px; }
        .secure-note { font-size: .78rem; color: var(--nm-muted); text-align: center; margin-top: .75rem; }
        .form-error { color: #dc2626; font-size: .85rem; display: none; }

        /* WhatsApp modal */
        .wa-modal .modal-dialog { margin: .75rem auto; max-width: 480px; }
        @media (max-width: 575.98px) { .wa-modal .modal-dialog { margin: .75rem; max-width: none; } }
        .wa-modal .modal-body { padding: .5rem 1.4rem 1.6rem; }
        @media (max-width: 575.98px) { .wa-modal .modal-body { padding: .5rem 1rem 1.3rem; } }

        .wa-icon-circle {
            width: 72px; height: 72px; border-radius: 50%;
            background: var(--nm-bg); color: #25D366;
            box-shadow: var(--nm-raised);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.3rem; margin: 0 auto 1rem;
        }
        .wa-title { font-weight: 800; font-size: 1.2rem; text-align: center; margin-bottom: .5rem; color: var(--nm-text); }
        .wa-desc  { color: var(--nm-muted); font-size: .9rem; line-height: 1.75; text-align: center; margin-bottom: 1.1rem; }

        .wa-order-box { background: var(--nm-bg); border-radius: var(--nm-radius-sm); box-shadow: var(--nm-inset-sm); padding: .95rem 1.1rem; font-size: .9rem; margin-bottom: 1rem; }
        .wa-order-box .wa-order-label { font-size: .72rem; font-weight: 800; color: var(--nm-muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: .4rem; }
        .wa-order-row { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; }
        .wa-order-row .wa-book  { font-weight: 700; word-break: break-word; }
        .wa-order-row .wa-total { font-weight: 800; color: var(--nm-brand); text-align: right; }

        .wa-number-box {
            display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;
            background: var(--nm-bg); border-radius: var(--nm-radius-sm);
            box-shadow: var(--nm-inset-sm); padding: .8rem 1.1rem; margin-bottom: 1.1rem;
        }
        .wa-number-box .wa-number-label { font-size: .8rem; color: var(--nm-muted); font-weight: 700; }
        .wa-number-box .wa-number-val   { font-weight: 800; font-size: 1.05rem; color: #15803d; letter-spacing: .02em; }

        .btn-whatsapp, .btn-kyash, .btn-paypay {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            color: #fff !important; font-weight: 800; border: none; border-radius: 999px;
            width: 100%; text-decoration: none;
            box-shadow: var(--nm-raised-sm);
            transition: box-shadow .2s ease, transform .2s ease;
        }
        .btn-whatsapp { background: linear-gradient(135deg, #1ebe5b, #25D366); font-size: 1.02rem; padding: 14px 20px; }
        .btn-whatsapp i { font-size: 1.4rem; }
        .btn-kyash  { background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); font-size: .92rem; padding: 12px 18px; }
        .btn-paypay { background: linear-gradient(135deg, #e11d48, #fb4f6f); font-size: .92rem; padding: 12px 18px; }
        .btn-whatsapp:hover, .btn-kyash:hover, .btn-paypay:hover { color: #fff; transform: translateY(-1px); }
        .btn-whatsapp:active, .btn-kyash:active, .btn-paypay:active { box-shadow: var(--nm-inset-sm); transform: scale(.98); }

        .wa-note { font-size: .78rem; color: var(--nm-muted); text-align: center; margin: .85rem 0 0; line-height: 1.6; }
        .wa-close-link { display: block; text-align: center; margin-top: .7rem; font-size: .88rem; font-weight: 700; color: var(--nm-muted); background: none; border: none; width: 100%; padding: 8px; }

        .apps-box {
            background: var(--nm-bg); border-radius: var(--nm-radius-sm);
            box-shadow: var(--nm-inset-sm); padding: 1rem 1.05rem; margin-top: 1.2rem; text-align: center;
        }
        .apps-box .apps-title { font-weight: 800; font-size: .96rem; margin-bottom: .25rem; color: var(--nm-text); }
        .apps-box .apps-desc  { font-size: .82rem; color: var(--nm-muted); line-height: 1.65; margin-bottom: .85rem; }
        .apps-box .apps-btns  { display: flex; flex-direction: column; gap: 12px; }

        /* Lightbox */
        .lightbox-overlay {
            display: none; position: fixed; inset: 0; background: rgba(45, 50, 80, .88);
            -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px);
            z-index: 2000; align-items: center; justify-content: center; padding: 20px;
        }
        .lightbox-overlay.show { display: flex; }
        .lightbox-overlay img { max-width: 100%; max-height: 90vh; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,.4); }
        .lightbox-close {
            position: absolute; top: 18px; right: 20px; width: 44px; height: 44px;
            color: var(--nm-brand); font-size: 1.8rem; line-height: 1; cursor: pointer;
            background: var(--nm-bg); border: none; border-radius: 50%; box-shadow: var(--nm-raised-sm);
        }

        @media (max-width: 560px) {
            .product-title { font-size: 1.4rem; }
            .product-price { font-size: 1.6rem; }
            .btn-buy { max-width: 100%; }
            .product-description-box { padding: 1.1rem 1rem; border-radius: 20px; }
            .cover-frame { border-radius: 20px; }
        }
</style>

<?php if ($useBuyButton): ?>
<script async src="https://js.stripe.com/v3/buy-button.js"></script>
<?php endif; ?>

<div class="pb-5">
    <?php if (!$book): ?>
        <div class="text-center py-5">
            <h1 class="fs-3 fw-bold mb-4">පොත සොයාගත නොහැක</h1>
            <a href="books.php" class="btn btn-brand">&larr; <?php echo htmlspecialchars($st['back_to_books']); ?></a>
        </div>
    <?php else: ?>

        <a href="books.php" class="breadcrumb-link mb-4">
            <i class="fa-solid fa-arrow-left"></i> <?php echo htmlspecialchars($st['back_to_books']); ?>
        </a>

        <div class="product-wrap mb-4 mt-3">
            <div class="cover-frame">
                <img src="<?php echo htmlspecialchars($book['cover']); ?>"
                     alt="<?php echo htmlspecialchars($bookTitle); ?> — <?php echo htmlspecialchars($st['book_cover']); ?> (A4)"
                     onerror="this.onerror=null;this.src='https://placehold.co/700x990/eef2ff/4f46e5?text=<?php echo urlencode($book['level']); ?>+Cover';">
            </div>

            <div>
                <span class="level-pill">JLPT <?php echo htmlspecialchars($book['level']); ?></span>
                <?php if ($hasDiscount): ?>
                    <span class="discount-pill"><i class="fa-solid fa-tag me-1"></i>-<?php echo $discountPct; ?>%</span>
                <?php endif; ?>
                <h1 class="product-title"><?php echo htmlspecialchars($bookTitle); ?></h1>
                <p class="product-tagline"><?php echo htmlspecialchars($bookTagline); ?></p>

                <div class="product-price-row">
                    <?php if ($hasDiscount): ?>
                        <span class="product-price-old"><?php echo format_price($book['price_cents'], $shopCurrency); ?></span>
                    <?php endif; ?>
                    <span class="product-price"><?php echo display_price($effectivePriceCents, $shopCurrency, $currentLang); ?></span>
                </div>

                <p class="ship-note mb-3">
                    <i class="fa-solid fa-truck-fast me-1"></i> <?php echo htmlspecialchars($st['free_shipping_note']); ?>
                    &nbsp;&middot;&nbsp; <?php echo htmlspecialchars($st['sri_lanka_shipping_note']); ?>
                </p>

                <?php if ($useBuyButton): ?>
                    <div class="stripe-buy-wrap">
                        <stripe-buy-button
                            buy-button-id="<?php echo htmlspecialchars($book['buy_button_id']); ?>"
                            publishable-key="<?php echo htmlspecialchars(STRIPE_PUBLISHABLE_KEY); ?>"
                        ></stripe-buy-button>
                    </div>
                    <p class="secure-note mt-2 mb-0"><i class="fa-solid fa-lock me-1"></i><?php echo htmlspecialchars($st['secure_note']); ?></p>
                <?php else: ?>
                    <div class="qty-row">
                        <label class="form-label mb-0" for="qtyInput"><?php echo htmlspecialchars($st['quantity']); ?></label>
                        <div class="qty-control">
                            <button type="button" id="qtyMinus" aria-label="Decrease quantity">&minus;</button>
                            <input type="text" id="qtyInput" value="1" inputmode="numeric" readonly>
                            <button type="button" id="qtyPlus" aria-label="Increase quantity">&plus;</button>
                        </div>
                    </div>

                    <!-- TEMP: opens the WhatsApp modal instead of the old shipping form (#shippingModal) -->
                    <button type="button" class="btn-buy" data-bs-toggle="modal" data-bs-target="#whatsappModal">
                        <i class="fa-solid fa-bag-shopping me-2"></i><?php echo htmlspecialchars($st['buy_now']); ?>
                    </button>

                    <!-- Kyash / PayPay: how to pay (open in a new tab) -->
                    <div class="pay-links">
                        <a href="<?php echo htmlspecialchars(KYASH_PAGE_URL); ?>"
                           class="kyash-inline-link"
                           target="_blank"
                           rel="noopener noreferrer">
                            <i class="fa-solid fa-wallet" aria-hidden="true"></i>
                            <?php echo htmlspecialchars($wa['kyash_link']); ?>
                            <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:.75rem;" aria-hidden="true"></i>
                        </a>
                        <a href="<?php echo htmlspecialchars(PAYPAY_PAGE_URL); ?>"
                           class="paypay-inline-link"
                           target="_blank"
                           rel="noopener noreferrer">
                            <i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i>
                            <?php echo htmlspecialchars($wa['paypay_link']); ?>
                            <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:.75rem;" aria-hidden="true"></i>
                        </a>
                    </div>

                    <?php /* TEMP DISABLED (Stripe secure note)
                    <p class="secure-note mt-2 mb-0"><i class="fa-solid fa-lock me-1"></i><?php echo htmlspecialchars($st['secure_note']); ?></p>
                    */ ?>
                <?php endif; ?>
            </div>
        </div>

        <?php
        $descriptionText = $book['description']['si'] ?? ($book['description']['en'] ?? '');
        ?>
        <?php if (trim($descriptionText) !== ''): ?>
        <div class="product-description-box">
            <h2 class="whats-inside-title">
                <?php echo htmlspecialchars($st['about_book']); ?>
            </h2>
            <p class="product-description-text"><?php echo nl2br(htmlspecialchars($descriptionText)); ?></p>
        </div>
        <?php endif; ?>

        <h2 class="whats-inside-title"><?php echo htmlspecialchars($st['whats_inside']); ?></h2>
        <div class="gallery-grid">
            <?php foreach (($book['gallery'] ?? []) as $i => $img): ?>
                <div class="gallery-item">
                    <img src="<?php echo htmlspecialchars($img); ?>"
                         alt="<?php echo htmlspecialchars($bookTitle); ?> — Page <?php echo $i + 1; ?>"
                         loading="lazy"
                         onclick="openLightbox(this.src)"
                         onerror="this.onerror=null;this.src='https://placehold.co/600x450/f8fafc/64748b?text=Page+<?php echo $i + 1; ?>';">
                    <div class="cap"><?php echo htmlspecialchars($book['gallery_captions']['si'][$i] ?? $book['gallery_captions']['en'][$i] ?? ''); ?></div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($book['gallery'])): ?>
                <p class="text-muted small">No preview images added yet.</p>
            <?php endif; ?>
        </div>

        <div class="lightbox-overlay" id="lightboxOverlay" onclick="closeLightbox(event)">
            <button class="lightbox-close" onclick="closeLightbox(event)" aria-label="Close">&times;</button>
            <img id="lightboxImg" src="" alt="">
        </div>

        <?php if (!$useBuyButton): ?>
        <!-- ================= TEMP: WhatsApp modal ================= -->
        <div class="modal fade wa-modal" id="whatsappModal" tabindex="-1" aria-labelledby="whatsappModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <span></span>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="wa-icon-circle"><i class="fa-brands fa-whatsapp"></i></div>
                        <h5 class="wa-title" id="whatsappModalLabel"><?php echo htmlspecialchars($wa['modal_title']); ?></h5>
                        <p class="wa-desc"><?php echo htmlspecialchars($wa['modal_desc']); ?></p>

                        <div class="wa-order-box">
                            <div class="wa-order-label"><?php echo htmlspecialchars($wa['order_label']); ?></div>
                            <div class="wa-order-row">
                                <span class="wa-book"><?php echo htmlspecialchars($bookTitle); ?> (JLPT <?php echo htmlspecialchars($book['level']); ?>) &times; <span id="waQty">1</span></span>
                                <span class="wa-total" id="waTotal"><?php echo display_price($effectivePriceCents, $shopCurrency, $currentLang); ?></span>
                            </div>
                        </div>

                        <div class="wa-number-box">
                            <span class="wa-number-label"><?php echo htmlspecialchars($wa['our_number']); ?></span>
                            <span class="wa-number-val"><?php echo htmlspecialchars(WHATSAPP_NUMBER_DISPLAY); ?></span>
                        </div>

                        <a href="#" id="waOpenBtn" class="btn-whatsapp" target="_blank" rel="noopener noreferrer">
                            <i class="fa-brands fa-whatsapp"></i> <?php echo htmlspecialchars($wa['open_wa']); ?>
                        </a>
                        <p class="wa-note"><?php echo htmlspecialchars($wa['wa_note']); ?></p>

                        <!-- Kyash / PayPay: how to pay (open in a new tab) -->
                        <div class="apps-box">
                            <div class="apps-title"><span aria-hidden="true">💳</span> <?php echo htmlspecialchars($wa['apps_title']); ?></div>
                            <p class="apps-desc"><?php echo htmlspecialchars($wa['apps_desc']); ?></p>
                            <div class="apps-btns">
                                <a href="<?php echo htmlspecialchars(KYASH_PAGE_URL); ?>"
                                   class="btn-kyash"
                                   target="_blank"
                                   rel="noopener noreferrer">
                                    <i class="fa-solid fa-wallet" aria-hidden="true"></i>
                                    <?php echo htmlspecialchars($wa['kyash_btn']); ?>
                                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:.8rem;" aria-hidden="true"></i>
                                </a>
                                <a href="<?php echo htmlspecialchars(PAYPAY_PAGE_URL); ?>"
                                   class="btn-paypay"
                                   target="_blank"
                                   rel="noopener noreferrer">
                                    <i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i>
                                    <?php echo htmlspecialchars($wa['paypay_btn']); ?>
                                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:.8rem;" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>

                        <button type="button" class="wa-close-link" data-bs-dismiss="modal"><?php echo htmlspecialchars($wa['close']); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <!-- ================= /WhatsApp modal ================= -->

        <?php /* =================== TEMP DISABLED: old shipping modal ===================
        (Re-enable together with the old checkout <script> block further below,
         and change the button's data-bs-target back to "#shippingModal".)

        <div class="modal fade" id="shippingModal" tabindex="-1" aria-labelledby="shippingModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="shippingModalLabel"><?php echo htmlspecialchars($st['shipping_title']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3"><?php echo htmlspecialchars($st['shipping_sub']); ?></p>

                        <form id="shippingForm" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="full_name"><?php echo htmlspecialchars($st['full_name']); ?><span class="req-mark">*</span></label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="phone"><?php echo htmlspecialchars($st['phone']); ?><span class="req-mark">*</span></label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="email"><?php echo htmlspecialchars($st['email']); ?><span class="req-mark">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="address_line"><?php echo htmlspecialchars($st['address_line']); ?><span class="req-mark">*</span></label>
                                    <input type="text" class="form-control" id="address_line" name="address_line" required minlength="5">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="city"><?php echo htmlspecialchars($st['city']); ?><span class="req-mark">*</span></label>
                                    <input type="text" class="form-control" id="city" name="city" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="postal_code"><?php echo htmlspecialchars($st['postal_code']); ?><span class="req-mark">*</span></label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="country"><?php echo htmlspecialchars($st['country']); ?><span class="req-mark">*</span></label>
                                    <select class="form-select" id="country" name="country" required>
                                        <option value="Japan">🇯🇵 Japan</option>
                                        <option value="Sri Lanka">🇱🇰 Sri Lanka</option>
                                    </select>
                                </div>
                            </div>

                            <div class="order-summary-box mt-4">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><?php echo htmlspecialchars($bookTitle); ?> &times; <span id="summaryQty">1</span></span>
                                    <span>
                                        <?php if ($hasDiscount): ?>
                                            <span class="summary-old-price" id="summaryOldLineTotal"><?php echo format_price($book['price_cents'], $shopCurrency); ?></span>
                                        <?php endif; ?>
                                        <span id="summaryLineTotal"><?php echo format_price($effectivePriceCents, $shopCurrency); ?></span>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between mb-1" id="summaryShippingRow" style="display:none;">
                                    <span><?php echo htmlspecialchars($st['shipping_fee_label']); ?></span>
                                    <span id="summaryShipping"></span>
                                </div>
                                <div class="d-flex justify-content-between fw-bold mt-2 pt-2" style="border-top:1px solid var(--border-soft);">
                                    <span><?php echo htmlspecialchars($st['total']); ?></span>
                                    <span id="summaryTotal"><?php echo format_price($effectivePriceCents, $shopCurrency); ?></span>
                                </div>
                            </div>

                            <p class="form-error mt-3" id="formError"></p>

                            <button type="submit" class="btn-buy mt-3" id="submitPayBtn" style="max-width:100%;">
                                <?php echo htmlspecialchars($st['continue_to_payment']); ?>
                            </button>
                            <p class="secure-note"><i class="fa-solid fa-lock me-1"></i><?php echo htmlspecialchars($st['secure_note']); ?></p>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        =================== /TEMP DISABLED: old shipping modal =================== */ ?>
        <?php endif; ?>

    <?php endif; ?>
</div>

<script>
    function openLightbox(src) {
        document.getElementById('lightboxImg').src = src;
        document.getElementById('lightboxOverlay').classList.add('show');
    }
    function closeLightbox(e) {
        if (e.target.id === 'lightboxOverlay' || e.target.classList.contains('lightbox-close')) {
            document.getElementById('lightboxOverlay').classList.remove('show');
        }
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            var o = document.getElementById('lightboxOverlay');
            if (o) o.classList.remove('show');
        }
    });
</script>

<?php if ($book && !$useBuyButton): ?>
<!-- ================= TEMP: WhatsApp ordering script ================= -->
<script>
    const UNIT_PRICE_CENTS = <?php echo (int) $effectivePriceCents; ?>;
    const CURRENCY = <?php echo json_encode($shopCurrency); ?>;
    const LANG = <?php echo json_encode($currentLang); ?>;
    // Live JPY->LKR rate (fetched + cached server-side, display only)
    const LKR_RATE = <?php echo json_encode((float) JPY_TO_LKR_RATE); ?>;
    const WA_NUMBER = <?php echo json_encode(WHATSAPP_NUMBER); ?>;
    const BOOK_TITLE = <?php echo json_encode($bookTitle); ?>;
    const BOOK_LEVEL = <?php echo json_encode($book['level']); ?>;

    // Display-only price formatter.
    function formatPrice(amount) {
        let base;
        if (CURRENCY === 'jpy' || CURRENCY === 'JPY') {
            base = '¥' + Math.round(amount).toLocaleString();
        } else {
            const symbols = { usd: '$', lkr: 'Rs. ' };
            const symbol = symbols[String(CURRENCY).toLowerCase()] || (String(CURRENCY).toUpperCase() + ' ');
            base = symbol + (amount / 100).toFixed(2);
        }
        if (LANG === 'si' && (CURRENCY === 'jpy' || CURRENCY === 'JPY')) {
            const lkr = Math.round(amount * LKR_RATE);
            base += ' (≈ Rs. ' + lkr.toLocaleString() + ')';
        }
        return base;
    }

    const qtyInput = document.getElementById('qtyInput');
    const waQty = document.getElementById('waQty');
    const waTotal = document.getElementById('waTotal');
    const waOpenBtn = document.getElementById('waOpenBtn');

    function currentQty() { return parseInt(qtyInput.value, 10) || 1; }

    // Builds the pre-filled WhatsApp message + link and updates the modal.
    function refreshWhatsApp() {
        const qty = currentQty();
        const total = UNIT_PRICE_CENTS * qty;

        if (waQty) waQty.textContent = qty;
        if (waTotal) waTotal.textContent = formatPrice(total);

        const message =
            'ආයුබෝවන්! learnjapanese.jp වෙතින් පොතක් මිලදී ගැනීමට කැමතියි.\n\n' +
            '📚 පොත: ' + BOOK_TITLE + ' (JLPT ' + BOOK_LEVEL + ')\n' +
            '🔢 ප්‍රමාණය: ' + qty + '\n' +
            '💴 මුළු මිල: ' + formatPrice(total) + '\n\n' +
            'කරුණාකර ගෙවීම් විස්තර සහ බෙදාහැරීමේ විස්තර දන්වන්න. ස්තූතියි!';

        if (waOpenBtn) {
            waOpenBtn.href = 'https://wa.me/' + WA_NUMBER + '?text=' + encodeURIComponent(message);
        }
    }

    document.getElementById('qtyMinus').addEventListener('click', () => {
        qtyInput.value = Math.max(1, currentQty() - 1);
        refreshWhatsApp();
    });
    document.getElementById('qtyPlus').addEventListener('click', () => {
        qtyInput.value = Math.min(20, currentQty() + 1);
        refreshWhatsApp();
    });

    // Refresh whenever the modal opens (safety) and on page load.
    const waModalEl = document.getElementById('whatsappModal');
    if (waModalEl) waModalEl.addEventListener('show.bs.modal', refreshWhatsApp);
    refreshWhatsApp();
</script>
<!-- ================= /WhatsApp ordering script ================= -->

<?php /* =================== TEMP DISABLED: old Stripe checkout script ===================
<script>
    // IMPORTANT: this is the price actually shown/used on the client side.
    // If a discount is active, this is already the DISCOUNTED price.
    // Your server-side checkout script MUST look up the price by BOOK_ID
    // from the database itself (and apply the same discount logic) —
    // never trust a price sent from the browser.
    const UNIT_PRICE_CENTS = <?php echo (int) $effectivePriceCents; ?>;
    const CURRENCY = <?php echo json_encode($shopCurrency); ?>;
    const BOOK_ID = <?php echo json_encode($bookId); ?>;
    const LANG = <?php echo json_encode($currentLang); ?>;
    const LKR_RATE = <?php echo json_encode((float) JPY_TO_LKR_RATE); ?>;
    const SRI_LANKA_SHIPPING = <?php echo (int) SRI_LANKA_SHIPPING_JPY; ?>;

    function formatPrice(amount) {
        let base;
        if (CURRENCY === 'jpy' || CURRENCY === 'JPY') {
            base = '¥' + Math.round(amount).toLocaleString();
        } else {
            const symbols = { usd: '$', lkr: 'Rs. ' };
            const symbol = symbols[String(CURRENCY).toLowerCase()] || (String(CURRENCY).toUpperCase() + ' ');
            base = symbol + (amount / 100).toFixed(2);
        }
        if (LANG === 'si' && (CURRENCY === 'jpy' || CURRENCY === 'JPY')) {
            const lkr = Math.round(amount * LKR_RATE);
            base += ' (≈ Rs. ' + lkr.toLocaleString() + ')';
        }
        return base;
    }

    const qtyInput = document.getElementById('qtyInput');
    const countrySelect = document.getElementById('country');
    const summaryQty = document.getElementById('summaryQty');
    const summaryLineTotal = document.getElementById('summaryLineTotal');
    const summaryOldLineTotal = document.getElementById('summaryOldLineTotal');
    const summaryShippingRow = document.getElementById('summaryShippingRow');
    const summaryShipping = document.getElementById('summaryShipping');
    const summaryTotal = document.getElementById('summaryTotal');

    function currentQty() { return parseInt(qtyInput.value, 10) || 1; }

    function currentShipping() {
        return (countrySelect && countrySelect.value === 'Sri Lanka') ? SRI_LANKA_SHIPPING : 0;
    }

    function refreshSummary() {
        const qty = currentQty();
        const lineTotal = UNIT_PRICE_CENTS * qty;
        const shipping = currentShipping();
        const grandTotal = lineTotal + shipping;

        if (summaryQty) summaryQty.textContent = qty;
        if (summaryLineTotal) summaryLineTotal.textContent = formatPrice(lineTotal);
        if (summaryOldLineTotal) summaryOldLineTotal.textContent = formatPrice(<?php echo (int) ($book['price_cents'] ?? 0); ?> * qty);

        if (shipping > 0) {
            summaryShippingRow.style.display = 'flex';
            summaryShipping.textContent = formatPrice(shipping);
        } else {
            summaryShippingRow.style.display = 'none';
        }

        if (summaryTotal) summaryTotal.textContent = formatPrice(grandTotal);
    }

    document.getElementById('qtyMinus').addEventListener('click', () => {
        qtyInput.value = Math.max(1, currentQty() - 1);
        refreshSummary();
    });
    document.getElementById('qtyPlus').addEventListener('click', () => {
        qtyInput.value = Math.min(20, currentQty() + 1);
        refreshSummary();
    });
    countrySelect.addEventListener('change', refreshSummary);

    // Initialize summary on load (default country = Japan, no shipping fee)
    refreshSummary();

    const form = document.getElementById('shippingForm');
    const submitBtn = document.getElementById('submitPayBtn');
    const formError = document.getElementById('formError');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        formError.style.display = 'none';

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const payload = {
            book_id: BOOK_ID,
            quantity: currentQty(),
            full_name: document.getElementById('full_name').value.trim(),
            phone: document.getElementById('phone').value.trim(),
            email: document.getElementById('email').value.trim(),
            address_line: document.getElementById('address_line').value.trim(),
            city: document.getElementById('city').value.trim(),
            postal_code: document.getElementById('postal_code').value.trim(),
            country: countrySelect.value,
        };

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing...';

        try {
            const res = await fetch('checkout/create-checkout-session.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json();

            if (!res.ok || !data.url) {
                throw new Error(data.error || 'Something went wrong.');
            }

            window.location.href = data.url;
        } catch (err) {
            formError.textContent = err.message;
            formError.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.innerHTML = <?php echo json_encode($st['continue_to_payment']); ?>;
        }
    });
</script>
=================== /TEMP DISABLED: old Stripe checkout script =================== */ ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>