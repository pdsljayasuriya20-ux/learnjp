<?php
session_start();
ini_set('display_errors', 0); // Turned off for production safety
error_reporting(E_ALL);

// Sinhala-only site — language switching removed
$currentLang = 'si';

// Current page (used for nav "active" state)
$current_page = basename($_SERVER['PHP_SELF']);

// Domain Configuration
$siteUrl  = 'https://learnjapanese.jp';
$basePath = strtok($_SERVER['REQUEST_URI'], '?');
$pageUrl  = $siteUrl . $basePath;
$ogImage  = $siteUrl . '/assets/images/learnjapanese-og.jpg';

// Live book data (for the homepage carousel) — same $books array shape
// used by books.php / book_detail.php, driven by the admin panel.
require_once __DIR__ . '/shop-data.php';

// External driving-test partner site
$drivingExternalUrl = 'https://menkyoo.jp';

// =====================================================
// LOGIN STATE + APP LINKS  (header, bottom-nav & footer all use these)
// ⚠️ Oyage login system ekata anuwa methana wenas karanna
// =====================================================
$isLoggedIn   = !empty($_SESSION['user_id']);                 // ← login session key
$userName     = $_SESSION['user_name']                        // ← user name key
                ?? $_SESSION['name'] ?? '';
$userPhoto    = $_SESSION['user_photo']                       // ← profile photo key (url / path)
                ?? $_SESSION['profile_photo']
                ?? $_SESSION['avatar'] ?? '';
$loginUrl     = 'user-login.php';       // ← login page
$dashboardUrl = 'dashboard.php';   // ← dashboard page
$profileUrl   = $dashboardUrl;     // ← avatar tap karama yana page eka
$contactUrl   = 'contact.php';     // ← contact page

// UI Strings
$translations = [
    'si' => [
        'title'             => 'ජපන් භාෂාව පහසුවෙන් ඉගෙන ගනිමු',
        'subtitle'          => 'JLPT N5 සිට N1 දක්වා ',
        'jlpt_levels'       => 'JLPT මට්ටම්',
        'practice_questions'=> 'පුහුණු ප්‍රශ්න',
        'study_access'      => 'නොමිලේ ප්‍රවේශය',
        'jlpt_mastery'      => 'JLPT පාඩම්',
        'driving_license'   => 'රියැදුරු බලපත්‍රය',
        'start_journey'     => 'සූදානම ආරම්භ කරන්න',
        'driving_sub'       => 'නීති, ලකුණු සහ පුහුණු පරීක්ෂණ — Menkyoo.jp හරහා',
        'driving_desc'      => 'ජපන් මාර්ග නීති සහ රියදුරු විභාගය සඳහා සම්පූර්ණ පුහුණු ප්‍රශ්නාවලිය.',
        'start_learning'    => 'අදම ආරම්භ කරන්න',
        'beginner'          => 'ආරම්භක',
        'elementary'        => 'මූලික',
        'intermediate'      => 'මධ්‍යම',
        'upper_int'         => 'ඉහළ මධ්‍යම',
        'advanced'          => 'උසස්',
        'nav_home'          => 'මුල් පිටුව',
        'nav_vocab'         => 'වචන මාලාව',
        'nav_driving'       => 'රියදුරු විභාගය',
        'login'             => 'ඇතුල් වන්න',
        'my_profile'        => 'මගේ ගිණුම',
        'footer_about'      => 'JLPT N5 සිට N1 දක්වාත්, ජපාන රියදුරු බලපත්‍ර විභාගය සඳහාත් සම්පූර්ණ මාර්ගෝපදේශය.',
        'quick_links'       => 'ඉක්මන් සබැඳි',
        'contact_us'        => 'අප අමතන්න',
        'newsletter'        => 'පුවත් ලිපිය',
        'newsletter_ph'     => 'ඔබේ විද්‍යුත් තැපෑල',
        'theory'            => 'න්‍යාය',
        'theory_desc'       => 'රථවාහන නීති',
        'road_signs'        => 'මාර්ග ලකුණු',
        'road_signs_desc'   => 'දෘශ්‍ය මාර්ගෝපදේශය',
        'practice'          => 'පුහුණුව',
        'practice_desc'     => 'පුහුණු පරීක්ෂණ',
        'technique'         => 'ක්‍රම',
        'technique_desc'    => 'රිය පැදවීමේ උපදෙස්',
        'books_title'       => 'JLPT වචන මාලා පොත්',
        'books_sub'         => 'N5–N1 පූර්ණ වර්ණ මුද්‍රිත වචන පොත්',
        'books_desc'        => 'සෑම JLPT වචනයක්ම සිංහලෙන් පැහැදිලි කර, උදාහරණ වාක්‍ය සහ පුහුණු ප්‍රශ්න සමඟ ඔබේ නිවසටම එවනු ලැබේ.',
        'shop_books'        => 'පොත් බලන්න',
        'see_all'           => 'සියල්ල බලන්න',
        'no_books_yet'      => 'අලුත් පොත් ළඟදීම — මොහොතකින් නැවත බලන්න!',
        'external_note'     => 'Menkyoo.jp අලුත් tab එකක විවෘත වේ'
    ]
];

// SEO Configuration
$seo = [
    'si' => [
        'title' => 'ජපන් භාෂාව ඉගෙන ගන්න | ලංකාවේ සහ ජපානයේ ඉන්න ශ්‍රී ලාංකිකයන් සඳහා JLPT N5-N1 සහ රියදුරු බලපත්‍ර සූදානම',
        'description' => 'ලංකාවේ ඉඳන් හෝ ජපානයේ ඉන්නවා නම්, JLPT N5 සිට N1 දක්වා ව්‍යාකරණ/වචන පාඩම් සහ ජපාන රියදුරු බලපත්‍ර විභාගය (Komon) සඳහා සිංහලෙන් සම්පූර්ණ නොමිලේ මාර්ගෝපදේශය.',
        'keywords' => 'JLPT sinhala, JLPT N5 sinhala, JLPT N4 sinhala, JLPT N3 sinhala, JLPT vyakarana, japan wala inna sri lankikayo, sri lankans in japan, japan driving license test sinhala, komon exam sinhala, japan ganna sinhala, jlpt vocabulary sinhala',
        'og_locale' => 'si_LK'
    ]
];

$t = $translations[$currentLang];
$s = $seo[$currentLang];

// Individual pages can set $pageTitle / $pageDescription / $pageKeywords
// before requiring this file to override the site-wide SEO defaults above
// (e.g. w-grammar.php sets a level-specific title & description).
if (!empty($pageTitle))       { $s['title'] = $pageTitle; }
if (!empty($pageDescription)) { $s['description'] = $pageDescription; }
if (!empty($pageKeywords))    { $s['keywords'] = $pageKeywords; }

// Desktop-only top navigation (phones / tablets use the bottom app bar instead)
$navItems = [
    ['href' => 'index.php',      'key' => 'index.php',      'label' => $t['nav_home']],
    ['href' => 'n5.php',         'key' => 'n5.php',          'label' => 'N5'],
    ['href' => 'n4.php',         'key' => 'n4.php',          'label' => 'N4'],
    ['href' => 'n3.php',         'key' => 'n3.php',          'label' => 'N3'],
    ['href' => 'n2.php',         'key' => 'n2.php',          'label' => 'N2'],
    ['href' => 'n1.php',         'key' => 'n1.php',          'label' => 'N1'],
    ['href' => 'howtopay.php',   'key' => 'howtopay.php',    'label' => 'ගෙවීම්'],
];

// First letter of the name (shown when the user has no profile photo)
$userInitial = $userName !== '' ? mb_strtoupper(mb_substr($userName, 0, 1, 'UTF-8'), 'UTF-8') : '';
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <meta name="theme-color" content="#e6e9f2">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/img/logo.jpg">
  <!-- PWA / install-as-app -->
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#8338ec">
<meta name="mobile-web-app-capable" content="yes">
<!-- iPhone (Safari ignores the manifest icons, uses these) -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="LearnJP">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png"> <!-- 180x180 PNG -->
    <!-- SEO Meta Tags -->
    <title><?php echo htmlspecialchars($s['title']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($s['description']); ?>"/>
    <meta name="keywords" content="<?php echo htmlspecialchars($s['keywords'] ?? ''); ?>"/>
    <meta name="author" content="Sampath"/>
    <link rel="canonical" href="<?php echo htmlspecialchars($pageCanonical ?? $pageUrl); ?>"/>

    <!-- Targets both Sri Lanka and the Sri Lankan community living in Japan -->
    <meta name="geo.region" content="LK"/>
    <meta name="geo.placename" content="Sri Lanka, Japan"/>

    <link rel="alternate" hreflang="si" href="<?php echo htmlspecialchars($pageUrl); ?>"/>
    <link rel="alternate" hreflang="x-default" href="<?php echo htmlspecialchars($pageUrl); ?>"/>

    <!-- Open Graph & Social Cards -->
    <meta property="og:title" content="<?php echo htmlspecialchars($s['title']); ?>"/>
    <meta property="og:description" content="<?php echo htmlspecialchars($s['description']); ?>"/>
    <meta property="og:type" content="website"/>
    <meta property="og:url" content="<?php echo htmlspecialchars($pageCanonical ?? $pageUrl); ?>"/>
    <meta property="og:image" content="<?php echo htmlspecialchars($ogImage); ?>"/>
    <meta property="og:image:width" content="1200"/>
    <meta property="og:image:height" content="630"/>
    <meta property="og:locale" content="<?php echo $s['og_locale']; ?>"/>
    <meta property="og:site_name" content="Learn Japanese"/>

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image"/>
    <meta name="twitter:title" content="<?php echo htmlspecialchars($s['title']); ?>"/>
    <meta name="twitter:description" content="<?php echo htmlspecialchars($s['description']); ?>"/>
    <meta name="twitter:image" content="<?php echo htmlspecialchars($ogImage); ?>"/>

    <link rel="icon" type="image/png" href="/assets/favicon.png"/>
    <link rel="apple-touch-icon" href="/assets/apple-touch-icon.png"/>

    <!-- Performance -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>

    <!-- CSS & Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Noto+Sans+JP:wght@400;700&family=Noto+Sans+Sinhala:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>

    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "EducationalOrganization",
      "name": "Learn Japanese",
      "url": "<?php echo $siteUrl; ?>/",
      "logo": "<?php echo $siteUrl; ?>/assets/favicon.png",
      "description": <?php echo json_encode($seo['si']['description']); ?>,
      "email": "info@learnjapanese.jp",
      "hasCourse": [
        { "@type": "Course", "name": "JLPT N5", "description": "Beginner level JLPT preparation", "provider": { "@type": "Organization", "name": "Learn Japanese", "sameAs": "<?php echo $siteUrl; ?>/" } },
        { "@type": "Course", "name": "JLPT N4", "description": "Elementary level JLPT preparation", "provider": { "@type": "Organization", "name": "Learn Japanese", "sameAs": "<?php echo $siteUrl; ?>/" } },
        { "@type": "Course", "name": "Japan Driving License Test Prep", "description": "Written test preparation covering road signs and traffic rules", "provider": { "@type": "Organization", "name": "Learn Japanese", "sameAs": "<?php echo $siteUrl; ?>/" } }
      ]
    }
    </script>
  <script src="/tracking.js" defer></script>

    <style>
        /* =====================================================
           NEUMORPHISM DESIGN TOKENS (global)
           ===================================================== */
        :root {
            --nm-bg: #e6e9f2;
            --nm-light: #ffffff;
            --nm-dark: #c3c9dc;
            --nm-text: #2d3250;
            --nm-muted: #7a8099;
            --nm-brand: #4f46e5;
            --nm-brand-2: #7c6cf5;
            --nm-radius: 24px;
            --nm-radius-sm: 16px;

            --nm-raised:    8px 8px 18px var(--nm-dark), -8px -8px 18px var(--nm-light);
            --nm-raised-sm: 5px 5px 11px var(--nm-dark), -5px -5px 11px var(--nm-light);
            --nm-inset:     inset 5px 5px 11px var(--nm-dark), inset -5px -5px 11px var(--nm-light);
            --nm-inset-sm:  inset 3px 3px 7px var(--nm-dark), inset -3px -3px 7px var(--nm-light);

            /* legacy aliases (other pages may still use these) */
            --brand-primary: var(--nm-brand);
            --brand-primary-hover: #4338ca;
            --bg-surface: var(--nm-bg);
            --card-bg: var(--nm-bg);
            --text-main: var(--nm-text);
            --text-muted: var(--nm-muted);
            --border-soft: transparent;
            --radius-lg: var(--nm-radius);
            --radius-md: var(--nm-radius-sm);
        }

        * { -webkit-tap-highlight-color: transparent; }
        html { scroll-behavior: smooth; }

        body {
            font-family: 'Plus Jakarta Sans', 'Noto Sans Sinhala', sans-serif;
            background-color: var(--nm-bg);
            color: var(--nm-text);
            padding-bottom: env(safe-area-inset-bottom, 0);
            min-height: 100vh;
        }
        .text-muted { color: var(--nm-muted) !important; }

        @media (prefers-reduced-motion: reduce) {
            * { animation: none !important; transition: none !important; scroll-behavior: auto !important; }
        }

        .skip-link {
            position: absolute; left: -999px; top: auto;
            background: var(--nm-bg); color: var(--nm-brand);
            padding: 10px 16px; border-radius: 12px; z-index: 2000;
            text-decoration: none; font-weight: 600; box-shadow: var(--nm-raised-sm);
        }
        .skip-link:focus { left: 10px; top: 10px; }

        /* =====================================================
           HEADER & NAV
           Desktop (>= 992px): logo + full nav + login/avatar
           Phone / tablet (< 992px): ONLY logo + login/avatar
           (menu links live in the bottom app bar)
           ===================================================== */
        .app-header {
            background: rgba(230, 233, 242, 0.88);
            -webkit-backdrop-filter: blur(14px); backdrop-filter: blur(14px);
            position: sticky; top: 0; z-index: 1000;
            box-shadow: 0 10px 22px -12px var(--nm-dark);
            padding-top: env(safe-area-inset-top, 0);
        }

        .logo {
            font-weight: 800; font-size: 1.15rem;
            color: var(--nm-text); text-decoration: none; white-space: nowrap;
            display: inline-flex; align-items: center;
            padding: 8px 16px; border-radius: 999px;
            background: var(--nm-bg); box-shadow: var(--nm-raised-sm);
            transition: box-shadow .2s ease;
        }
        .logo span { color: var(--nm-brand); }
        .logo:hover { color: var(--nm-text); }
        .logo:active { box-shadow: var(--nm-inset-sm); }

        .nav-links { display: flex; align-items: center; gap: 10px; }

        .nav-link-item {
            padding: 8px 16px; border-radius: 999px;
            font-size: 0.86rem; font-weight: 700;
            color: var(--nm-muted); text-decoration: none; white-space: nowrap;
            background: var(--nm-bg);
            box-shadow: 4px 4px 9px var(--nm-dark), -4px -4px 9px var(--nm-light);
            transition: box-shadow .2s ease, color .2s ease, transform .2s ease;
        }
        .nav-link-item:hover { color: var(--nm-brand); transform: translateY(-1px); }
        .nav-link-item:active { box-shadow: var(--nm-inset-sm); transform: scale(.97); }
        .nav-link-item.active {
            color: var(--nm-brand);
            box-shadow: var(--nm-inset-sm);
            transform: none;
        }

        /* ---- Login button (logged-out) ---- */
        .nav-login-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 9px 18px; border-radius: 999px;
            background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2));
            color: #fff; font-size: .86rem; font-weight: 800;
            text-decoration: none; white-space: nowrap;
            box-shadow: var(--nm-raised-sm);
            transition: box-shadow .2s ease, transform .2s ease;
        }
        .nav-login-btn:hover  { color: #fff; transform: translateY(-1px); }
        .nav-login-btn:active { box-shadow: var(--nm-inset-sm); transform: scale(.96); }

        /* ---- Profile avatar (logged-in) ---- */
        .nav-avatar {
            position: relative; width: 44px; height: 44px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            background: var(--nm-bg); color: var(--nm-brand);
            font-weight: 800; font-size: 1.05rem; text-decoration: none;
            box-shadow: var(--nm-raised-sm);
            overflow: hidden; flex-shrink: 0;
            border: 3px solid var(--nm-bg);
            transition: box-shadow .2s ease, transform .2s ease;
        }
        .nav-avatar:active { box-shadow: var(--nm-inset-sm); transform: scale(.95); }
        .nav-avatar img {
            position: absolute; inset: 0; width: 100%; height: 100%;
            object-fit: cover; border-radius: 50%;
        }

        /* =====================================================
           HERO / STATS / CARDS
           ===================================================== */
        @keyframes nmFadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .hero-banner {
            background: linear-gradient(135deg, var(--nm-brand) 0%, var(--nm-brand-2) 100%);
            color: #fff; border-radius: var(--nm-radius);
            padding: 2.2rem 1.5rem; text-align: center;
            box-shadow: var(--nm-raised);
            position: relative; overflow: hidden;
            animation: nmFadeUp .55s ease both;
        }
        .hero-banner::before, .hero-banner::after {
            content: ""; position: absolute; border-radius: 50%; background: rgba(255,255,255,.12);
        }
        .hero-banner::before { width: 140px; height: 140px; top: -50px; right: -30px; }
        .hero-banner::after  { width: 90px;  height: 90px;  bottom: -35px; left: 12%; }
        .hero-banner > * { position: relative; z-index: 1; }

        .stat-card {
            background: var(--nm-bg); border-radius: var(--nm-radius-sm);
            box-shadow: var(--nm-raised-sm);
            padding: 14px 6px; text-align: center; height: 100%;
            transition: box-shadow .25s ease;
            animation: nmFadeUp .55s ease both;
        }
        .stat-card:hover { box-shadow: var(--nm-inset-sm); }
        .stat-card .num {
            font-size: 1.4rem; font-weight: 800; line-height: 1.2;
            background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2));
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent; color: transparent;
        }
        .stat-card .small { font-size: .72rem; }

        .app-card {
            background: var(--nm-bg); border-radius: var(--nm-radius);
            padding: 1.5rem 1.2rem; border: 0;
            box-shadow: var(--nm-raised); height: 100%;
            animation: nmFadeUp .55s ease both;
        }
        .app-card h2 { color: var(--nm-text); }

        /* =====================================================
           JLPT GRID
           ===================================================== */
        .jlpt-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
        @media (min-width: 420px) { .jlpt-grid { grid-template-columns: repeat(5, 1fr); } }
        @media (min-width: 992px) and (max-width: 1199.98px) { .jlpt-grid { grid-template-columns: repeat(3, 1fr); } }

        .jlpt-btn {
            background: var(--nm-bg); border-radius: var(--nm-radius-sm);
            box-shadow: var(--nm-raised-sm);
            padding: 14px 4px; min-height: 78px;
            text-decoration: none; text-align: center; color: var(--nm-text);
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;
            transition: box-shadow .2s ease, transform .2s ease;
        }
        .jlpt-btn:hover { color: var(--nm-text); transform: translateY(-2px); }
        .jlpt-btn:active, .jlpt-btn:focus-visible { box-shadow: var(--nm-inset-sm); transform: scale(.97); outline: none; }
        .jlpt-btn .tag  { font-size: 1.15rem; font-weight: 800; color: var(--nm-brand); }
        .jlpt-btn .desc { font-size: 0.66rem; color: var(--nm-muted); line-height: 1.2; }

        /* =====================================================
           BUTTONS
           ===================================================== */
        .btn-brand {
            background: var(--nm-bg) !important; color: var(--nm-brand) !important;
            border: 0 !important; border-radius: 999px !important;
            padding: 15px 24px !important; font-weight: 800; font-size: 1.02rem;
            box-shadow: var(--nm-raised);
            transition: box-shadow .2s ease, transform .2s ease;
        }
        .btn-brand:hover  { transform: translateY(-2px); }
        .btn-brand:active { box-shadow: var(--nm-inset); transform: scale(.98); }
        .cta-wrap { max-width: 420px; margin: 0 auto; padding: 0 4px; animation: nmFadeUp .55s ease both; }

        /* =====================================================
           DRIVING CARD
           ===================================================== */
        .driving-card {
            background: linear-gradient(135deg, var(--nm-brand) 0%, var(--nm-brand-2) 100%);
            border-radius: var(--nm-radius-sm); padding: 1.2rem 1.1rem;
            color: #fff; cursor: pointer; border: none; width: 100%;
            text-align: left; text-decoration: none; display: block;
            position: relative; overflow: hidden;
            box-shadow: var(--nm-raised-sm);
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .driving-card::after {
            content: ""; position: absolute; width: 110px; height: 110px; border-radius: 50%;
            background: rgba(255,255,255,.12); right: -30px; top: -40px; pointer-events: none;
        }
        .driving-card:hover  { transform: translateY(-2px); color: #fff; }
        .driving-card:active { transform: scale(.98); box-shadow: var(--nm-inset-sm); }
        .driving-card .external-badge {
            display: inline-flex; align-items: center; gap: 6px;
            margin-top: 10px; font-size: .7rem; font-weight: 600;
            background: rgba(255,255,255,.2); border-radius: 999px; padding: 4px 10px;
        }

        /* =====================================================
           BOOK CAROUSEL
           ===================================================== */
        .book-carousel-section .section-head {
            display: flex; align-items: flex-start; justify-content: space-between;
            gap: 12px; flex-wrap: wrap;
        }
        .book-carousel-section h2 { font-size: 1.4rem; }
        .book-carousel-section .featured-tag,
        .featured-tag {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--nm-bg); color: var(--nm-brand);
            box-shadow: var(--nm-inset-sm); border-radius: 999px;
            padding: 5px 12px; font-size: .72rem; font-weight: 700; margin-bottom: 10px;
        }
        .see-all-link {
            display: inline-flex; align-items: center;
            background: var(--nm-bg); color: var(--nm-brand);
            box-shadow: var(--nm-raised-sm); border-radius: 999px;
            padding: 8px 16px; font-size: .82rem; font-weight: 700;
            text-decoration: none; white-space: nowrap;
            transition: box-shadow .2s ease, transform .2s ease;
        }
        .see-all-link:hover  { color: var(--nm-brand); }
        .see-all-link:active { box-shadow: var(--nm-inset-sm); transform: scale(.98); }

        .book-carousel-wrap { position: relative; margin-left: -4px; margin-right: -4px; }
        .book-carousel {
            display: flex; gap: 18px; overflow-x: auto; overflow-y: hidden;
            scroll-snap-type: x mandatory; scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            padding: 12px 10px 22px; scrollbar-width: none; -ms-overflow-style: none;
        }
        .book-carousel::-webkit-scrollbar { display: none; }

        .book-carousel-card {
            flex: 0 0 148px; scroll-snap-align: start;
            background: var(--nm-bg); border-radius: var(--nm-radius-sm);
            box-shadow: var(--nm-raised-sm); padding: 10px;
            text-decoration: none; color: var(--nm-text);
            transition: box-shadow .25s ease, transform .25s ease;
        }
        .book-carousel-card:hover  { transform: translateY(-3px); color: var(--nm-text); }
        .book-carousel-card:active { box-shadow: var(--nm-inset-sm); transform: scale(.98); }

        .bc-cover-wrap {
            position: relative; aspect-ratio: 3 / 4; border-radius: 12px;
            overflow: hidden; box-shadow: var(--nm-inset-sm);
            background: var(--nm-bg); padding: 6px;
        }
        .bc-cover-wrap img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; display: block; }

        .bc-latest-badge, .bc-discount-badge {
            position: absolute; top: 10px; z-index: 2;
            font-size: .65rem; font-weight: 800; padding: 3px 9px;
            border-radius: 999px; color: #fff; box-shadow: 2px 2px 6px rgba(0,0,0,.2);
        }
        .bc-latest-badge   { right: 10px; background: linear-gradient(135deg, #ef4444, #f97316); text-transform: uppercase; }
        .bc-discount-badge { left: 10px;  background: linear-gradient(135deg, #10b981, #34d399); }

        .bc-body { padding: 10px 4px 2px; }
        .bc-title {
            font-size: .84rem; font-weight: 700; line-height: 1.3;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
            overflow: hidden; min-height: 2.4em;
        }
        .bc-price-row { display: flex; align-items: baseline; gap: 6px; margin-top: 6px; flex-wrap: wrap; }
        .bc-price     { font-weight: 800; color: var(--nm-brand); font-size: .95rem; }
        .bc-price-old { font-size: .74rem; color: var(--nm-muted); text-decoration: line-through; }

        .carousel-arrow {
            position: absolute; top: 42%; transform: translateY(-50%);
            width: 42px; height: 42px; border: 0; border-radius: 50%;
            background: var(--nm-bg); color: var(--nm-brand);
            box-shadow: var(--nm-raised-sm);
            display: flex; align-items: center; justify-content: center;
            z-index: 5; cursor: pointer; transition: box-shadow .2s ease;
        }
        .carousel-arrow:active { box-shadow: var(--nm-inset-sm); }
        .carousel-arrow.left  { left: -6px; }
        .carousel-arrow.right { right: -6px; }
        @media (hover: none), (max-width: 767.98px) { .carousel-arrow { display: none; } }

        .book-empty-note {
            margin: 16px 0 4px; padding: 18px; text-align: center;
            border-radius: var(--nm-radius-sm); box-shadow: var(--nm-inset);
            color: var(--nm-muted); font-size: .9rem;
        }

        /* =====================================================
           FOOTER (desktop only — phones/tablets use the bottom app bar)
           ===================================================== */
        .site-footer { margin-top: 3rem; padding-top: 1rem; padding-bottom: 1.5rem; }
        .footer-card {
            background: var(--nm-bg); border-radius: var(--nm-radius);
            box-shadow: var(--nm-raised); padding: 2rem 1.5rem;
        }
        .footer-title { font-size: 1.05rem; font-weight: 800; color: var(--nm-text); margin-bottom: 1rem; }
        .footer-text  { color: var(--nm-muted); font-size: .9rem; line-height: 1.7; margin-bottom: 1rem; }

        .social-icons { display: flex; gap: 14px; flex-wrap: wrap; }
        .social-icon {
            width: 42px; height: 42px; border-radius: 50%;
            background: var(--nm-bg); color: var(--nm-brand);
            box-shadow: var(--nm-raised-sm);
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; transition: box-shadow .2s ease, transform .2s ease;
        }
        .social-icon:hover  { color: var(--nm-brand); transform: translateY(-2px); }
        .social-icon:active { box-shadow: var(--nm-inset-sm); transform: scale(.96); }

        .footer-links, .footer-contact { list-style: none; padding: 0; margin: 0; }
        .footer-links li { margin-bottom: .6rem; }
        .footer-links a {
            color: var(--nm-muted); text-decoration: none; font-size: .9rem;
            transition: color .2s ease, padding-left .2s ease;
        }
        .footer-links a:hover { color: var(--nm-brand); padding-left: 4px; }
        .footer-links i, .footer-contact i { color: var(--nm-brand); margin-right: 8px; width: 18px; text-align: center; }
        .footer-contact li { color: var(--nm-muted); margin-bottom: .7rem; font-size: .9rem; }

        .newsletter-form { display: flex; gap: 10px; margin-top: .6rem; }
        .newsletter-form input {
            flex: 1; min-width: 0; padding: .7rem 1.1rem; border: 0; border-radius: 999px;
            background: var(--nm-bg); color: var(--nm-text);
            box-shadow: var(--nm-inset-sm); font-size: .85rem;
        }
        .newsletter-form input::placeholder { color: var(--nm-muted); }
        .newsletter-form input:focus { outline: none; box-shadow: var(--nm-inset-sm), 0 0 0 2px rgba(79,70,229,.35); }
        .newsletter-form button {
            width: 46px; height: 46px; flex-shrink: 0; border: 0; border-radius: 50%;
            background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2));
            color: #fff; cursor: pointer; box-shadow: var(--nm-raised-sm);
            transition: box-shadow .2s ease, transform .2s ease;
        }
        .newsletter-form button:active { box-shadow: var(--nm-inset-sm); transform: scale(.95); }

        .footer-bottom {
            margin-top: 1.5rem; padding-top: 1.25rem; text-align: center;
            border-top: 2px solid rgba(195, 201, 220, .45);
        }
        .copyright { color: var(--nm-muted); font-size: .82rem; margin: 0; }

        /* =====================================================
           RESPONSIVE
           ===================================================== */
        /* Phone + tablet: header = logo + login/avatar only, no top menu */
        @media (max-width: 991.98px) {
            .nav-links.desktop-only { display: none; }
            .app-header { margin-bottom: 1rem !important; }
            /* normal footer is replaced by the bottom app bar */
            .site-footer { display: none !important; }
        }
        @media (max-width: 380px) {
            /* very small phones: login button becomes icon-only */
            .nav-login-btn { padding: 10px 13px; }
            .nav-login-btn span { display: none; }
        }
        @media (max-width: 576px) {
            .hero-banner { padding: 1.8rem 1.1rem; border-radius: 20px; }
            .hero-banner h1 { font-size: 1.4rem !important; }
            .app-card { padding: 1.1rem .95rem; border-radius: 20px; }
            .footer-card { padding: 1.5rem 1.1rem; border-radius: 20px; }
            .book-carousel-card { flex-basis: 138px; }
            .book-carousel-section h2 { font-size: 1.2rem; }
            .logo { font-size: 1.02rem; padding: 7px 14px; }
        }
        @media (min-width: 768px) {
            .book-carousel-card { flex-basis: 170px; }
            .stat-card .num { font-size: 1.7rem; }
        }
    </style>
</head>
<body>
  <script src="/tracking.js" defer></script>
    <a class="skip-link" href="#main-content">Skip to content</a>

    <!-- Header & Navigation -->
    <header class="app-header py-2 mb-4">
        <div class="container d-flex align-items-center justify-content-between gap-2">
            <a href="/" class="logo">Learn<span>Japanese</span></a>

            <!-- Desktop-only top nav (hidden on phone / tablet) -->
            <nav class="nav-links desktop-only" aria-label="Main navigation">
                <?php foreach ($navItems as $item): ?>
                    <a href="<?php echo htmlspecialchars($item['href']); ?>"
                       class="nav-link-item <?php echo $current_page === $item['key'] ? 'active' : ''; ?>"
                       <?php echo $current_page === $item['key'] ? 'aria-current="page"' : ''; ?>>
                        <?php echo htmlspecialchars($item['label']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Login button  /  profile photo (all screen sizes) -->
            <div class="d-flex align-items-center gap-2">
                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo htmlspecialchars($profileUrl); ?>" class="nav-avatar"
                       aria-label="<?php echo htmlspecialchars($t['my_profile']); ?>"
                       title="<?php echo htmlspecialchars($userName !== '' ? $userName : $t['my_profile']); ?>">
                        <?php if ($userPhoto !== ''): ?>
                            <!-- if the photo fails to load, the img is removed and the initial / icon below shows -->
                            <img src="<?php echo htmlspecialchars($userPhoto); ?>" alt=""
                                 onerror="this.remove();">
                        <?php endif; ?>
                        <?php if ($userInitial !== ''): ?>
                            <?php echo htmlspecialchars($userInitial); ?>
                        <?php else: ?>
                            <i class="fa-solid fa-user" aria-hidden="true"></i>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars($loginUrl); ?>" class="nav-login-btn">
                        <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                        <span><?php echo htmlspecialchars($t['login']); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main id="main-content" class="container">
