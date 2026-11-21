<?php

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
// Include header which loads settings_loader.php
include('header.php');
/**
 * නෙත්සිත ජෝතිශ්‍ය සේවය - Privacy Policy Page
 * Comprehensive privacy policy with settings integration
 */

// ============================================
// INITIALIZATION & CONFIGURATION
// ============================================

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base URL
define('BASE_URL', '/');

// ============================================
// DATABASE CONNECTION
// ============================================

$db_error = false;
$conn = null;

try {
    include('db_connection.php');
    
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed");
    }
    
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    $db_error = true;
    error_log("DB Connection Error: " . $e->getMessage());
}



// Verify settings are loaded
if (!isset($app_settings) || empty($app_settings)) {
    error_log("Privacy Page: app_settings not loaded properly");
    $app_settings = [];
}

// ============================================
// EXTRACT SETTINGS WITH DEFAULTS
// ============================================

$site_title = htmlspecialchars($app_settings['site_title'] ?? 'නෙත්සිත ජෝතිශ්‍ය සේවය', ENT_QUOTES, 'UTF-8');
$contact_email = htmlspecialchars($app_settings['contact_email'] ?? 'info@nethsitha.lk', ENT_QUOTES, 'UTF-8');
$contact_phone = htmlspecialchars($app_settings['contact_phone'] ?? '+94 71 239 1317', ENT_QUOTES, 'UTF-8');
$contact_address = htmlspecialchars($app_settings['contact_address'] ?? 'නො. 123, ප්‍රධාන පාර, කොළඹ', ENT_QUOTES, 'UTF-8');

// WhatsApp number (clean - numbers only)
$whatsapp_number_clean = preg_replace('/[^0-9]/', '', $app_settings['social_whatsapp'] ?? '94712391317');
$social_whatsapp = 'https://wa.me/' . $whatsapp_number_clean;

// Social media links
$social_facebook = htmlspecialchars($app_settings['social_facebook'] ?? 'https://facebook.com', ENT_QUOTES, 'UTF-8');
$social_instagram = htmlspecialchars($app_settings['social_instagram'] ?? 'https://instagram.com', ENT_QUOTES, 'UTF-8');
$social_youtube = htmlspecialchars($app_settings['social_youtube'] ?? 'https://youtube.com', ENT_QUOTES, 'UTF-8');

$current_year = date('Y');

?>
<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- SEO Meta Tags -->
    <title>පුද්ගලිකත්ව ප්‍රතිපත්තිය - <?php echo $site_title; ?></title>
    <meta name="description" content="<?php echo $site_title; ?> හි පුද්ගලිකත්ව ප්‍රතිපත්තිය. ඔබගේ දත්ත රහස්‍යභාවය සහ ආරක්ෂාව පිළිබඳ අපගේ ප්‍රතිපත්ති.">
    <meta name="keywords" content="පුද්ගලිකත්ව ප්‍රතිපත්තිය, privacy policy, රහස්‍යභාවය, data protection">
    <meta name="author" content="<?php echo $site_title; ?>">
    <meta name="robots" content="index, follow">
    
    <!-- Favicon from Settings -->
    <?php if (!empty($app_settings['site_favicon_path'])): ?>
        <link rel="icon" type="image/x-icon" href="/<?php echo htmlspecialchars($app_settings['site_favicon_path']); ?>">
    <?php endif; ?>
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="පුද්ගලිකත්ව ප්‍රතිපත්තිය - <?php echo $site_title; ?>">
    <meta property="og:description" content="ඔබගේ දත්ත රහස්‍යභාවය සහ ආරක්ෂාව පිළිබඳ අපගේ ප්‍රතිපත්ති">
    <meta property="og:type" content="website">
    <?php if (!empty($app_settings['og_image_path'])): ?>
        <meta property="og:image" content="<?php echo htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/' . $app_settings['og_image_path']); ?>">
    <?php endif; ?>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/priv.css">
</head>
<body>

    <!-- ============================================
         PAGE HEADER
    ============================================ -->
    <header class="page-header text-center">
        <!-- Animated stars -->
        <div class="header-stars">
            <?php for($i = 0; $i < 8; $i++): ?>
                <div class="header-star"></div>
            <?php endfor; ?>
        </div>
        
        <!-- Orbiting element -->
        <div class="orbit-ring">
            <div class="orbit-dot"></div>
        </div>
        
        <div class="container">
            <!-- Privacy Icon -->
            <div class="privacy-icon mb-4">
                <i class="fas fa-shield-alt fa-4x text-white"></i>
            </div>
            
            <h1 class="display-4 fw-bold mb-3">පුද්ගලිකත්ව ප්‍රතිපත්තිය</h1>
            <p class="lead fs-5">ඔබගේ දත්ත රහස්‍යභාවය සහ ආරක්ෂාව අපගේ ප්‍රමුඛතාවයයි</p>
        </div>
    </header>

    <!-- ============================================
         MAIN CONTENT
    ============================================ -->
    <div class="container my-5">
        <div class="row">
            
            <!-- ============================================
                 TABLE OF CONTENTS (SIDEBAR)
            ============================================ -->
            <div class="col-lg-3">
                <div class="toc sticky-top" style="top: 100px;">
                    <h4><i class="fas fa-list me-2"></i>අන්තර්ගතය</h4>
                    <ul>
                        <?php
                        $toc_items = [
                            'introduction' => 'හැඳින්වීම',
                            'data-collection' => 'දත්ත එකතු කිරීම',
                            'data-usage' => 'දත්ත භාවිතය',
                            'data-protection' => 'දත්ත ආරක්ෂාව',
                            'data-sharing' => 'දත්ත බෙදාහැරීම',
                            'your-rights' => 'ඔබගේ අයිතිවාසිකම්',
                            'cookies' => 'කුකී ප්‍රතිපත්තිය',
                            'children' => 'දරුවන්ගේ රහස්‍යභාවය',
                            'changes' => 'වෙනස්කම්',
                            'contact-us' => 'අප හා සම්බන්ධ වන්න'
                        ];
                        
                        foreach($toc_items as $id => $title):
                        ?>
                            <li><a href="#<?php echo $id; ?>"><i class="fas fa-chevron-right"></i><?php echo $title; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- ============================================
                 MAIN CONTENT AREA
            ============================================ -->
            <div class="col-lg-9">
                
                <!-- Last Updated -->
                <div class="last-updated alert alert-info">
                    <i class="fas fa-calendar-alt me-2"></i>
                    <strong>අවසන් යාවත්කාලීන දිනය:</strong> <?php echo date('Y F d', strtotime('2024-01-01')); ?>
                </div>

                <!-- 1. Introduction -->
                <div class="policy-section scroll-reveal" id="introduction">
                    <h2><i class="fas fa-info-circle me-2"></i>1. හැඳින්වීම</h2>
                    
                    <div class="highlight-box">
                        <i class="fas fa-shield-alt"></i>
                        <strong>අපගේ කැපවීම:</strong> <?php echo $site_title; ?> ඔබගේ පුද්ගලික තොරතුරු රහස්‍යභාවය සහ ආරක්ෂාව පිළිබඳව දැඩි කැපවීමක් දක්වයි.
                    </div>

                    <p>මෙම පුද්ගලිකත්ව ප්‍රතිපත්තිය විස්තර කරන්නේ:</p>
                    <ul>
                        <li>අප විසින් එකතු කරනු ලබන තොරතුරු වර්ග</li>
                        <li>එම තොරතුරු භාවිතා කරන ආකාරය</li>
                        <li>ඔබගේ තොරතුරු ආරක්ෂා කරන ආකාරය</li>
                        <li>ඔබගේ පුද්ගලික දත්ත සම්බන්ධයෙන් ඔබට ඇති අයිතිවාසිකම්</li>
                    </ul>

                    <p>අපගේ වෙබ් අඩවිය භාවිතා කිරීමෙන් හෝ අපගේ සේවා ලබා ගැනීමෙන්, ඔබ මෙම ප්‍රතිපත්තියට එකඟ වන බව සැලකේ.</p>
                </div>

                <!-- 2. Data Collection -->
                <div class="policy-section scroll-reveal" id="data-collection">
                    <h2><i class="fas fa-database me-2"></i>2. අප එකතු කරන දත්ත</h2>
                    
                    <h3>2.1 ඔබ සපයන තොරතුරු</h3>
                    <p>ඔබ අපගේ සේවා ලබා ගන්නා විට, ඔබෙන් පහත තොරතුරු එකතු කළ හැක:</p>
                    
                    <div class="info-card">
                        <h5><i class="fas fa-user me-2 text-primary"></i>පුද්ගලික තොරතුරු:</h5>
                        <ul class="mb-0">
                            <li>සම්පූර්ණ නම</li>
                            <li>දුරකථන අංකය</li>
                            <li>විද්‍යුත් තැපැල් ලිපිනය</li>
                            <li>ලිපිනය (අවශ්‍ය නම්)</li>
                        </ul>
                    </div>

                    <div class="info-card">
                        <h5><i class="fas fa-star me-2 text-warning"></i>ජ්‍යෝතිෂ සේවා සඳහා තොරතුරු:</h5>
                        <ul class="mb-0">
                            <li>උපන් දිනය, වේලාව සහ ස්ථානය</li>
                            <li>පවුල් තොරතුරු (මැඩැම් සේවා සඳහා)</li>
                            <li>ඔබගේ විශේෂ අවශ්‍යතා හා විමසීම්</li>
                        </ul>
                    </div>

                    <div class="info-card">
                        <h5><i class="fas fa-home me-2 text-success"></i>වාස්තු සේවා සඳහා තොරතුරු:</h5>
                        <ul class="mb-0">
                            <li>නිවසේ/ඉඩමේ සැලැස්ම</li>
                            <li>ඡායාරූප (අවශ්‍ය නම්)</li>
                            <li>ස්ථාන විස්තර</li>
                        </ul>
                    </div>

                    <h3>2.2 ස්වයංක්‍රීයව එකතු කරන තොරතුරු</h3>
                    <p>ඔබ අපගේ වෙබ් අඩවිය භාවිතා කරන විට, පහත තොරතුරු ස්වයංක්‍රීයව එකතු කළ හැක:</p>
                    <ul>
                        <li>IP ලිපිනය</li>
                        <li>බ්‍රව්සර් වර්ගය සහ අනුවාදය</li>
                        <li>භාවිතා කරන උපාංගය (mobile/desktop)</li>
                        <li>වෙබ් අඩවියේ නැරඹූ පිටු</li>
                        <li>දිනය සහ වේලාව</li>
                    </ul>
                </div>

                <!-- 3. Data Usage -->
                <div class="policy-section scroll-reveal" id="data-usage">
                    <h2><i class="fas fa-cogs me-2"></i>3. දත්ත භාවිතය</h2>
                    
                    <p>අප විසින් එකතු කරනු ලබන තොරතුරු පහත අරමුණු සඳහා භාවිතා කරයි:</p>

                    <h3>3.1 සේවා සැපයීම</h3>
                    <ul>
                        <li>ජ්‍යෝතිෂ සහ වාස්තු සේවා සැපයීම</li>
                        <li>හොරෝස්කෝප් සකස් කිරීම</li>
                        <li>විශ්ලේෂණ සහ උපදෙස් ලබා දීම</li>
                        <li>වාස්තු සැලසුම් සකස් කිරීම</li>
                    </ul>

                    <h3>3.2 සන්නිවේදනය</h3>
                    <ul>
                        <li>ඔබගේ විමසීම්වලට පිළිතුරු දීම</li>
                        <li>සේවා යාවත්කාලීන කිරීම් දැනුම් දීම</li>
                        <li>අනුමත කිරීම් සහ තහවුරු කිරීම් යැවීම</li>
                        <li>වැදගත් දැනුම්දීම් යැවීම</li>
                    </ul>

                    <h3>3.3 සේවා වැඩිදියුණු කිරීම</h3>
                    <ul>
                        <li>පාරිභෝගික තෘප්තිය විශ්ලේෂණය කිරීම</li>
                        <li>වෙබ් අඩවියේ ක්‍රියාකාරීත්වය වැඩිදියුණු කිරීම</li>
                        <li>නව සේවා සංවර්ධනය කිරීම</li>
                    </ul>

                    <div class="highlight-box">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>වැදගත්:</strong> ඔබගේ ජ්‍යෝතිෂ හෝ වාස්තු තොරතුරු ඔබගේ අවසරයකින් තොරව කිසිදු තුන්වන පාර්ශවයකට විකුණනු හෝ බෙදා හරිනු නොලැබේ.
                    </div>
                </div>

                <!-- 4. Data Protection -->
                <div class="policy-section scroll-reveal" id="data-protection">
                    <h2><i class="fas fa-lock me-2"></i>4. දත්ත ආරක්ෂාව</h2>
                    
                    <p>ඔබගේ පුද්ගලික තොරතුරු ආරක්ෂා කිරීම සඳහා අප පහත පියවර ගනිමු:</p>

                    <div class="row mt-4">
                        <div class="col-md-6 mb-3">
                            <div class="info-card">
                                <h5><i class="fas fa-server me-2 text-primary"></i>තාක්ෂණික ආරක්ෂාව</h5>
                                <ul class="mb-0">
                                    <li>SSL/TLS සංකේතනය</li>
                                    <li>ආරක්ෂිත සේවාදායක පරිසරය</li>
                                    <li>දත්ත සංග්‍රහ සංකේතනය</li>
                                    <li>Firewall ආරක්ෂාව</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="info-card">
                                <h5><i class="fas fa-users-cog me-2 text-success"></i>පරිපාලනමය ආරක්ෂාව</h5>
                                <ul class="mb-0">
                                    <li>සීමිත ප්‍රවේශය</li>
                                    <li>කාර්ය මණ්ඩල පුහුණුව</li>
                                    <li>රහස්‍යභාවය ගිවිසුම්</li>
                                    <li>නිතිපතා විගණනය</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <h3>4.1 දත්ත ගබඩා කිරීම</h3>
                    <ul>
                        <li>දත්ත ආරක්ෂිත සේවාදායකවල ගබඩා කරනු ලැබේ</li>
                        <li>නිතිපතා backup ගනු ලැබේ</li>
                        <li>අවශ්‍ය කාලයක් පමණක් තබා ගනු ලැබේ</li>
                    </ul>

                    <h3>4.2 ගෙවීම් ආරක්ෂාව</h3>
                    <p>සියලුම මූල්‍ය ගනුදෙනු ආරක්ෂිත මාර්ග හරහා සිදු කරනු ලැබේ. අපගේ සේවාදායකවල කිසිදු ක්‍රෙඩිට් කාඩ් තොරතුරක් ගබඩා නොකරයි.</p>
                </div>

                <!-- 5. Data Sharing -->
                <div class="policy-section scroll-reveal" id="data-sharing">
                    <h2><i class="fas fa-share-alt me-2"></i>5. දත්ත බෙදාහැරීම</h2>
                    
                    <p>අපි ඔබගේ පුද්ගලික තොරතුරු පහත අවස්ථා වලදී පමණක් බෙදා හරිමු:</p>

                    <h3>5.1 ඔබගේ අවසරය සමඟ</h3>
                    <ul>
                        <li>ඔබ පැහැදිලිව අවසර දුන් විට</li>
                        <li>විශේෂ සේවා සඳහා අවශ්‍ය නම්</li>
                    </ul>

                    <h3>5.2 සේවා සපයන්නන්</h3>
                    <ul>
                        <li>ගෙවීම් සැකසීම සඳහා</li>
                        <li>තාක්ෂණික සහාය සඳහා</li>
                        <li>විද්‍යුත් තැපැල් සේවා සඳහා</li>
                    </ul>

                    <h3>5.3 නීතිමය අවශ්‍යතා</h3>
                    <ul>
                        <li>නීතිමය බලාත්මක කිරීම් අවශ්‍යතා</li>
                        <li>අධිකරණ නියෝග</li>
                        <li>නීතිමය ක්‍රියාමාර්ග</li>
                    </ul>

                    <div class="highlight-box">
                        <i class="fas fa-ban"></i>
                        <strong>අපි කිසිවිටෙක:</strong> ඔබගේ පුද්ගලික තොරතුරු වෙළඳ අරමුණු සඳහා විකුණනු, කුලියට දෙනු හෝ බෙදා හරිනු නොලැබේ.
                    </div>
                </div>

                <!-- 6. Your Rights -->
                <div class="policy-section scroll-reveal" id="your-rights">
                    <h2><i class="fas fa-user-shield me-2"></i>6. ඔබගේ අයිතිවාසිකම්</h2>
                    
                    <p>ඔබගේ පුද්ගලික දත්ත සම්බන්ධයෙන් ඔබට පහත අයිතිවාසිකම් ඇත:</p>

                    <?php
                    $rights = [
                        ['icon' => 'fa-eye', 'color' => 'primary', 'title' => 'ප්‍රවේශ වීමේ අයිතිය', 'desc' => 'අප සතුව ඇති ඔබගේ දත්ත බැලීමට ඔබට අයිතියක් ඇත.'],
                        ['icon' => 'fa-edit', 'color' => 'info', 'title' => 'නිවැරදි කිරීමේ අයිතිය', 'desc' => 'වැරදි හෝ අසම්පූර්ණ දත්ත නිවැරදි කිරීමට ඉල්ලීම් කිරීමට ඔබට හැකියාව ඇත.'],
                        ['icon' => 'fa-trash-alt', 'color' => 'danger', 'title' => 'මකා දැමීමේ අයිතිය', 'desc' => 'ඔබගේ පුද්ගලික දත්ත මකා දමන ලෙස ඉල්ලීමට ඔබට හැකියාව ඇත.'],
                        ['icon' => 'fa-ban', 'color' => 'warning', 'title' => 'සැකසීම සීමා කිරීමේ අයිතිය', 'desc' => 'ඔබගේ දත්ත සැකසීම සීමා කිරීමට ඉල්ලා සිටීමට ඔබට හැකියාව ඇත.'],
                        ['icon' => 'fa-download', 'color' => 'success', 'title' => 'දත්ත ගෙන යාමේ අයිතිය', 'desc' => 'ඔබගේ දත්ත යන්ත්‍ර කියවිය හැකි ආකෘතියකින් ලබා ගැනීමට ඔබට හැකියාව ඇත.']
                    ];
                    
                    foreach($rights as $right):
                    ?>
                        <div class="info-card">
                            <h5><i class="fas <?php echo $right['icon']; ?> me-2 text-<?php echo $right['color']; ?>"></i><?php echo $right['title']; ?></h5>
                            <p class="mb-0"><?php echo $right['desc']; ?></p>
                        </div>
                    <?php endforeach; ?>

                    <h3>6.1 අයිතිවාසිකම් භාවිතා කරන්නේ කෙසේද</h3>
                    <p>ඔබගේ අයිතිවාසිකම් භාවිතා කිරීමට:</p>
                    <ul>
                        <li>අප හා සම්බන්ධ වන්න: <a href="mailto:<?php echo $contact_email; ?>"><?php echo $contact_email; ?></a></li>
                        <li>WhatsApp: <a href="<?php echo $social_whatsapp; ?>" target="_blank"><?php echo $contact_phone; ?></a></li>
                        <li>ඔබගේ ඉල්ලීම සහ හැඳුනුම් සනාථ කිරීම සපයන්න</li>
                    </ul>
                </div>

                <!-- 7. Cookies Policy -->
                <div class="policy-section scroll-reveal" id="cookies">
                    <h2><i class="fas fa-cookie-bite me-2"></i>7. කුකී ප්‍රතිපත්තිය</h2>
                    
                    <h3>7.1 කුකී යනු කුමක්ද?</h3>
                    <p>කුකී යනු ඔබගේ උපාංගයේ තැන්පත් කරනු ලබන කුඩා පෙළ ගොනු වේ. මේවා වෙබ් අඩවිය වඩාත් කාර්යක්ෂමව ක්‍රියා කිරීමට උපකාරී වේ.</p>

                    <h3>7.2 අපි භාවිතා කරන කුකී</h3>
                    <ul>
                        <li><strong>අත්‍යවශ්‍ය කුකී:</strong> වෙබ් අඩවිය ක්‍රියා කිරීමට අවශ්‍ය</li>
                        <li><strong>විශ්ලේෂණ කුකී:</strong> වෙබ් අඩවිය භාවිතය තේරුම් ගැනීමට</li>
                        <li><strong>කාර්යක්ෂමතා කුකී:</strong> ඔබගේ මනාපයන් මතක තබා ගැනීමට</li>
                    </ul>

                    <h3>7.3 කුකී කළමනාකරණය</h3>
                    <p>ඔබට ඔබගේ බ්‍රව්සර් සැකසුම් හරහා කුකී වසා දැමීමට හෝ මකා දැමීමට හැකියාව ඇත. නමුත්, මෙය වෙබ් අඩවියේ සමහර විශේෂාංග ක්‍රියා කිරීමට බාධා කළ හැකිය.</p>
                </div>

                <!-- 8. Children's Privacy -->
                <div class="policy-section scroll-reveal" id="children">
                    <h2><i class="fas fa-child me-2"></i>8. දරුවන්ගේ රහස්‍යභාවය</h2>
                    
                    <p>අපගේ සේවා වයස අවුරුදු 18 ට අඩු දරුවන් සඳහා අදහස් නොකෙරේ. අපි දැනුවත්ව වයස අවුරුදු 18 ට අඩු දරුවන්ගෙන් පුද්ගලික තොරතුරු එකතු නොකරමු.</p>
                    
                    <p>ඔබ දෙමාපියෙකු හෝ භාරකරුවෙකු නම් සහ ඔබගේ දරුවා අපට පුද්ගලික තොරතුරු සපයා ඇති බව ඔබ දන්නේ නම්, කරුණාකර අප හා සම්බන්ධ වන්න.</p>
                </div>

                <!-- 9. Changes to Policy -->
                <div class="policy-section scroll-reveal" id="changes">
                    <h2><i class="fas fa-sync-alt me-2"></i>9. ප්‍රතිපත්තියේ වෙනස්කම්</h2>
                    
                    <p>අපි කලින් කලට මෙම පුද්ගලිකත්ව ප්‍රතිපත්තිය යාවත්කාලීන කළ හැක. වෙනස්කම් සිදු වූ විට:</p>
                    
                    <ul>
                        <li>නව ප්‍රතිපත්තිය වෙබ් අඩවියේ පළ කෙරේ</li>
                        <li>"අවසන් යාවත්කාලීන දිනය" යාවත්කාලීන කෙරේ</li>
                        <li>වැදගත් වෙනස්කම් සඳහා විද්‍යුත් තැපෑලෙන් දැනුම් දෙමු</li>
                    </ul>

                    <div class="highlight-box">
                        <i class="fas fa-bell"></i>
                        <strong>නිර්දේශය:</strong> මෙම පිටුව නිතිපතා පරීක්ෂා කර යාවත්කාලීන වීම් පිළිබඳව දැනුවත් වන්න.
                    </div>
                </div>

                <!-- 10. Contact Us -->
                <div class="policy-section scroll-reveal" id="contact-us">
                    <h2><i class="fas fa-phone-alt me-2"></i>10. අප හා සම්බන්ධ වන්න</h2>
                    
                    <p>මෙම පුද්ගලිකත්ව ප්‍රතිපත්තිය සම්බන්ධයෙන් ඔබට කිසියම් ප්‍රශ්නයක් හෝ අවශ්‍යතාවයක් ඇත්නම්, කරුණාකර අප හා සම්බන්ධ වන්න:</p>

                    <div class="row mt-4">
                        <div class="col-md-6 mb-3">
                            <div class="info-card">
                                <h5><i class="fas fa-building me-2 text-primary"></i>සංවිධානය</h5>
                                <p class="mb-2"><strong><?php echo $site_title; ?></strong></p>
                                <p class="mb-0"><i class="fas fa-map-marker-alt me-2"></i><?php echo $contact_address; ?></p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="info-card">
                                <h5><i class="fas fa-envelope me-2 text-info"></i>සම්බන්ධතා විස්තර</h5>
                                <p class="mb-2"><i class="fas fa-phone me-2"></i><a href="tel:<?php echo $contact_phone; ?>"><?php echo $contact_phone; ?></a></p>
                                <p class="mb-0"><i class="fas fa-at me-2"></i><a href="mailto:<?php echo $contact_email; ?>"><?php echo $contact_email; ?></a></p>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <a href="contact.php" class="btn btn-primary btn-lg me-2">
                            <i class="fas fa-paper-plane me-2"></i>විමසීමක් යවන්න
                        </a>
                        <a href="<?php echo $social_whatsapp; ?>" target="_blank" rel="noopener noreferrer" 
                           class="btn btn-success whatsapp-btn btn-lg">
                            <i class="fab fa-whatsapp me-2"></i>WhatsApp මගින්
                        </a>
                    </div>
                </div>

                <!-- Acknowledgment -->
                <div class="policy-section scroll-reveal">
                    <div class="highlight-box text-center">
                        <h5><i class="fas fa-handshake me-2"></i>ස්තූතියි</h5>
                        <p class="mb-0">අපගේ පුද්ගලිකත්ව ප්‍රතිපත්තිය කියවීම සහ අපගේ සේවා භාවිතා කිරීම සඳහා ස්තූතියි. ඔබගේ විශ්වාසය අපට ඉතා වැදගත්ය.</p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ============================================
         BOOTSTRAP & CUSTOM JAVASCRIPT
    ============================================ -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Smooth scroll for TOC links
        document.querySelectorAll('.toc a').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Highlight active section in TOC
        window.addEventListener('scroll', function() {
            let current = '';
            const sections = document.querySelectorAll('.policy-section');
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                if (pageYOffset >= sectionTop - 100) {
                    current = section.getAttribute('id');
                }
            });

            document.querySelectorAll('.toc a').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        });

        // Scroll reveal animation
        function revealOnScroll() {
            const reveals = document.querySelectorAll('.scroll-reveal');
            
            reveals.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const windowHeight = window.innerHeight;
                
                if (elementTop < windowHeight - 100) {
                    element.classList.add('active');
                }
            });
        }
        
        window.addEventListener('scroll', revealOnScroll);
        window.addEventListener('load', revealOnScroll);
        revealOnScroll();
    </script>

    <?php 
    // Close database connection
    if ($conn && !$db_error) {
        $conn->close();
    }
    
    // Include footer
    include('footer.php');
    ?>
</body>
</html>