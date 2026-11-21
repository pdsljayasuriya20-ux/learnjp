<?php 
/**
 * නෙත්සිත ජෝතිශ්‍ය සේවය - සේවාවන් පිටුව
 * Nethsitha Jyothishya Sewaya - Services Page
 */

// ============================================
// INITIALIZATION & DEPENDENCIES
// ============================================

include('db_connection.php'); 
include('header.php'); // This loads settings_loader.php

// Verify settings are loaded
if (!isset($app_settings) || empty($app_settings)) {
    error_log("Services Page: app_settings not loaded properly");
    $app_settings = [];
}

// ============================================
// LOAD SERVICES FROM DATABASE
// ============================================

// සියලුම සේවා වර්ගය අනුව ලබා ගැනීම
$jothishya_services_query = $conn->query("SELECT * FROM services WHERE category = 'Jothishya' ORDER BY id ASC");
$vasthu_services_query = $conn->query("SELECT * FROM services WHERE category = 'Vasthu' ORDER BY id ASC");

// Get WhatsApp number from settings (remove + prefix if present)
$whatsapp_number = $app_settings['social_whatsapp'] ?? '94712391317';
$whatsapp_number = ltrim($whatsapp_number, '+');

?>
<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>අපගේ සේවාවන් - <?php echo htmlspecialchars($app_settings['site_title'] ?? 'නෙත්සිත ජෝතිශ්‍ය සේවය'); ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo htmlspecialchars($app_settings['meta_description'] ?? 'විශ්වාසනීය ජ්‍යෝතිෂ හා වාස්තු සේවා'); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($app_settings['meta_keywords'] ?? 'ජෝතිශ්‍ය, වාස්තු, ශ්‍රී ලංකා'); ?>">
    
    <!-- Favicon -->
    <?php if (!empty($app_settings['site_favicon_path'])): ?>
        <link rel="icon" type="image/x-icon" href="/<?php echo htmlspecialchars($app_settings['site_favicon_path']); ?>">
    <?php endif; ?>
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="අපගේ සේවාවන් - <?php echo htmlspecialchars($app_settings['site_title'] ?? 'නෙත්සිත ජෝතිශ්‍ය සේවය'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($app_settings['meta_description'] ?? 'විශ්වාසනීය ජ්‍යෝතිෂ හා වාස්තු සේවා'); ?>">
    <?php if (!empty($app_settings['og_image_path'])): ?>
        <meta property="og:image" content="<?php echo htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/' . $app_settings['og_image_path']); ?>">
    <?php endif; ?>
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/services.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
</head>
<body>

    <!-- ============================================
         PAGE HEADER WITH COSMIC ANIMATIONS
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
            <h1 class="display-4 fw-bold mb-3">අපගේ සේවාවන්</h1>
            <p class="lead fs-5">විශ්වාසනීය, පළපුරුදු සහ පෞද්ගලික ජ්‍යෝතිෂ හා වාස්තු සේවාව</p>
        </div>
    </header>

    <!-- ============================================
         SERVICES CONTENT
    ============================================ -->
    <div class="container my-5">
        
        <!-- ============================================
             JOTHISHYA SERVICES SECTION
        ============================================ -->
        <div id="jothishya" class="mb-5 scroll-reveal">
            <div class="category-header">
                <!-- Floating symbols -->
                <div class="category-symbols">
                    <div class="floating-symbol">♈</div>
                    <div class="floating-symbol">♉</div>
                    <div class="floating-symbol">♊</div>
                </div>
                
                <div class="text-center">
                    <div class="feature-icon mx-auto mb-3">
                        <i class="fas fa-star fa-3x text-white"></i>
                    </div>
                    <h2 class="section-heading fw-bold">ජ්‍යෝතිෂ සේවා</h2>
                    <p class="mb-0 mt-3 fs-5">ඔබගේ ජීවිතයේ සියලු ජ්‍යෝතිෂ අවශ්‍යතා සඳහා විශේෂඥ උපදෙස්</p>
                </div>
            </div>

            <div class="row">
                <?php
                if ($jothishya_services_query->num_rows > 0) {
                    $delay = 0;
                    
                    // Icon mapping for Jothishya services with comprehensive keywords
                    $jothishya_icons = [
                        // ජාතක හා හෝරා සම්බන්ධ
                        'හෝරා' => ['icon' => 'fa-moon', 'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'],
                        'ජාතක' => ['icon' => 'fa-scroll', 'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'],
                        'නැකත්' => ['icon' => 'fa-star', 'gradient' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'],
                        
                        // අංක සහ ගණනය කිරීම්
                        'අංක' => ['icon' => 'fa-calculator', 'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'],
                        'ගණකය' => ['icon' => 'fa-calculator', 'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'],
                        
                        // මැණික් සහ රත්න
                        'මැණික්' => ['icon' => 'fa-gem', 'gradient' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'],
                        'රත්න' => ['icon' => 'fa-gem', 'gradient' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'],
                        
                        // නම් සහ අකුරු
                        'නම' => ['icon' => 'fa-signature', 'gradient' => 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'],
                        'අකුරු' => ['icon' => 'fa-font', 'gradient' => 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'],
                        
                        // යෝග සහ ධාර්මික
                        'යෝග' => ['icon' => 'fa-om', 'gradient' => 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)'],
                        'ධාන' => ['icon' => 'fa-praying-hands', 'gradient' => 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)'],
                        
                        // දින හා කේන්දර
                        'කේන්දර' => ['icon' => 'fa-calendar-alt', 'gradient' => 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)'],
                        'දවස' => ['icon' => 'fa-calendar-check', 'gradient' => 'linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%)'],
                        'දින' => ['icon' => 'fa-calendar-day', 'gradient' => 'linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%)'],
                        'වැඩ' => ['icon' => 'fa-briefcase', 'gradient' => 'linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%)'],
                        
                        // ග්‍රහ සහ ජ්‍යොතිර්විද්‍යාව
                        'ග්‍රහ' => ['icon' => 'fa-globe', 'gradient' => 'linear-gradient(135deg, #fad0c4 0%, #ffd1ff 100%)'],
                        'සඳ' => ['icon' => 'fa-moon', 'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'],
                        'සූර්ය' => ['icon' => 'fa-sun', 'gradient' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'],
                        
                        // පලාඵල හා අනාවැකි
                        'පලාඵල' => ['icon' => 'fa-star-half-alt', 'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'],
                        'අනාවැකි' => ['icon' => 'fa-crystal-ball', 'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'],
                        
                        // දෝෂ හා පිළියම්
                        'දෝෂ' => ['icon' => 'fa-exclamation-triangle', 'gradient' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'],
                        'පිළියම' => ['icon' => 'fa-hands', 'gradient' => 'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)'],
                        'නිවාරණ' => ['icon' => 'fa-shield-alt', 'gradient' => 'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)'],
                        
                        // විවාහ හා සම්බන්ධතා
                        'විවාහ' => ['icon' => 'fa-ring', 'gradient' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'],
                        'පොරොන්දම' => ['icon' => 'fa-heart', 'gradient' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'],
                        
                        // ව්‍යාපාර හා මුදල්
                        'ව්‍යාපාර' => ['icon' => 'fa-briefcase', 'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'],
                        'මුදල්' => ['icon' => 'fa-coins', 'gradient' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'],
                        'ධන' => ['icon' => 'fa-hand-holding-usd', 'gradient' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'],
                        
                        // සෞඛ්‍යය
                        'සෞඛ්‍ය' => ['icon' => 'fa-heartbeat', 'gradient' => 'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)'],
                        'ආයු' => ['icon' => 'fa-life-ring', 'gradient' => 'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)'],
                        
                        // රාශි
                        'රාශි' => ['icon' => 'fa-yin-yang', 'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'],
                        
                        // අධ්‍යාත්මික
                        'කර්ම' => ['icon' => 'fa-infinity', 'gradient' => 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)'],
                        'භාග්‍ය' => ['icon' => 'fa-clover', 'gradient' => 'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)'],
                    ];
                    
                    while ($service = $jothishya_services_query->fetch_assoc()) {
                        // Default icon
                        $icon = 'fa-star'; 
                        $gradient = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                        
                        $service_name = $service['service_name'];
                        $service_name_lower = mb_strtolower($service_name, 'UTF-8');
                        
                        // Keywords සමග match කිරීමට උත්සාහ කරන්න - දිගම match එක ප්‍රමුඛතාව දෙන්න
                        $matched = false;
                        $longest_match = '';
                        $matched_icon = '';
                        $matched_gradient = '';
                        
                        foreach ($jothishya_icons as $keyword => $style) {
                            $keyword_lower = mb_strtolower($keyword, 'UTF-8');
                            if (mb_strpos($service_name_lower, $keyword_lower) !== false) {
                                // දිගම match වන keyword එක track කරන්න
                                if (mb_strlen($keyword) > mb_strlen($longest_match)) {
                                    $longest_match = $keyword;
                                    $matched_icon = $style['icon'];
                                    $matched_gradient = $style['gradient'];
                                    $matched = true;
                                }
                            }
                        }
                        
                        // සොයාගත් දිගම match එක යොදන්න
                        if ($matched) {
                            $icon = $matched_icon;
                            $gradient = $matched_gradient;
                        }
                        ?>
                        <div class="col-md-6 col-lg-4 mb-4 scroll-reveal" style="animation-delay: <?php echo $delay; ?>s;">
                            <div class="card service-card h-100 shadow">
                                <div class="card-body">
                                    <div class="icon-wrapper" style="background: <?php echo $gradient; ?>;">
                                        <i class="fas <?php echo $icon; ?> fa-2x text-white"></i>
                                    </div>
                                    <h5 class="card-title fw-bold text-center mb-3">
                                        <?php echo htmlspecialchars($service['service_name']); ?>
                                    </h5>
                                    <p class="card-text text-muted text-center">
                                        <?php echo htmlspecialchars($service['description']); ?>
                                    </p>
                                    <div class="text-center mb-3">
                                        <span class="price-badge">
                                            රු. <?php echo number_format($service['price'], 2); ?>
                                        </span>
                                    </div>
                                    <div class="text-center">
                                        <a href="service_details.php?id=<?php echo $service['id']; ?>" 
                                           class="btn btn-request w-100">
                                            <i class="fas fa-paper-plane me-2"></i> ඉල්ලීමක් කරන්න
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                        $delay += 0.1;
                    }
                } else {
                    echo '<div class="col-12"><div class="alert alert-custom alert-info border-info"><i class="fas fa-info-circle me-2"></i>ජ්‍යෝතිෂ සේවා දැනට ඇතුළත් කර නොමැත.</div></div>';
                }
                ?>
            </div>
        </div>
        
        <!-- ============================================
             VASTHU SERVICES SECTION
        ============================================ -->
        <div id="vasthu" class="scroll-reveal">
            <div class="category-header vasthu">
                <!-- Floating symbols -->
                <div class="category-symbols">
                    <div class="floating-symbol">🏠</div>
                    <div class="floating-symbol">🏢</div>
                    <div class="floating-symbol">🏡</div>
                </div>
                
                <div class="text-center">
                    <div class="feature-icon info mx-auto mb-3">
                        <i class="fas fa-home fa-3x text-white"></i>
                    </div>
                    <h2 class="section-heading fw-bold text-dark">වාස්තු සේවා</h2>
                    <p class="mb-0 mt-3 text-dark fs-5">ඔබගේ නිවස හා ව්‍යාපාරය සඳහා වාස්තු විද්‍යානුකූල විසඳුම්</p>
                </div>
            </div>

            <div class="row">
                <?php
                if ($vasthu_services_query->num_rows > 0) {
                    $delay = 0;
                    
                    // Icon mapping for Vasthu services with comprehensive keywords
                    $vasthu_icons = [
                        // නිවාස හා ගොඩනැගිලි
                        'නිවස' => ['icon' => 'fa-home', 'gradient' => 'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)'],
                        'ගෘහ' => ['icon' => 'fa-home', 'gradient' => 'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)'],
                        'නිවාස' => ['icon' => 'fa-home', 'gradient' => 'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)'],
                        'ගෙදර' => ['icon' => 'fa-home', 'gradient' => 'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)'],
                        'මහල්' => ['icon' => 'fa-building', 'gradient' => 'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)'],
                        
                        // වාණිජ ස්ථාන
                        'කඩ' => ['icon' => 'fa-store', 'gradient' => 'linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%)'],
                        'කාර්යාල' => ['icon' => 'fa-building', 'gradient' => 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)'],
                        'කාර්යාලය' => ['icon' => 'fa-building', 'gradient' => 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)'],
                        'ව්‍යාපාර' => ['icon' => 'fa-briefcase', 'gradient' => 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)'],
                        'ව්‍යාපාරික' => ['icon' => 'fa-chart-line', 'gradient' => 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)'],
                        
                        // කර්මාන්තශාලා
                        'කර්මාන්ත' => ['icon' => 'fa-industry', 'gradient' => 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'],
                        'කර්මාන්තශාලා' => ['icon' => 'fa-industry', 'gradient' => 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'],
                        'කර්මාන්තය' => ['icon' => 'fa-cogs', 'gradient' => 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'],
                        
                        // ඉඩම් හා භූමිය
                        'ඉඩම' => ['icon' => 'fa-map', 'gradient' => 'linear-gradient(135deg, #fad0c4 0%, #ffd1ff 100%)'],
                        'භූමි' => ['icon' => 'fa-map-marked-alt', 'gradient' => 'linear-gradient(135deg, #fad0c4 0%, #ffd1ff 100%)'],
                        'පර්චස්' => ['icon' => 'fa-ruler-combined', 'gradient' => 'linear-gradient(135deg, #fad0c4 0%, #ffd1ff 100%)'],
                        'බිම' => ['icon' => 'fa-map', 'gradient' => 'linear-gradient(135deg, #fad0c4 0%, #ffd1ff 100%)'],
                        
                        // දොර සහ ව්‍යුහය
                        'දොර' => ['icon' => 'fa-door-open', 'gradient' => 'linear-gradient(135deg, #d299c2 0%, #fef9d7 100%)'],
                        'දොරටුව' => ['icon' => 'fa-door-closed', 'gradient' => 'linear-gradient(135deg, #d299c2 0%, #fef9d7 100%)'],
                        'ජනේල' => ['icon' => 'fa-window-restore', 'gradient' => 'linear-gradient(135deg, #d299c2 0%, #fef9d7 100%)'],
                        
                        // කොටස් හා කාමර
                        'කොටස්' => ['icon' => 'fa-th-large', 'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'],
                        'කාමර' => ['icon' => 'fa-bed', 'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'],
                        'කාමරය' => ['icon' => 'fa-bed', 'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'],
                        
                        // දිශාව හා සැලසුම්
                        'දිශාව' => ['icon' => 'fa-compass', 'gradient' => 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)'],
                        'දිශා' => ['icon' => 'fa-compass', 'gradient' => 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)'],
                        'සැලසුම' => ['icon' => 'fa-drafting-compass', 'gradient' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'],
                        'නිර්මාණ' => ['icon' => 'fa-pencil-ruler', 'gradient' => 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)'],
                        'ප්ලෑන්' => ['icon' => 'fa-drafting-compass', 'gradient' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'],
                        
                        // වත් හා උද්‍යාන
                        'වත්ත' => ['icon' => 'fa-tree', 'gradient' => 'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)'],
                        'උද්‍යාන' => ['icon' => 'fa-leaf', 'gradient' => 'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)'],
                        'ගස' => ['icon' => 'fa-tree', 'gradient' => 'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)'],
                        
                        // වතුර හා ජලය
                        'වතුර' => ['icon' => 'fa-water', 'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'],
                        'ජලය' => ['icon' => 'fa-water', 'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'],
                        'පොකුණ' => ['icon' => 'fa-tint', 'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'],
                        
                        // වර්ණ හා ආලෝකය
                        'වර්ණ' => ['icon' => 'fa-palette', 'gradient' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'],
                        'ආලෝක' => ['icon' => 'fa-lightbulb', 'gradient' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'],
                        
                        // නිදන කාමර
                        'නිදන' => ['icon' => 'fa-bed', 'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'],
                        'නින්දට' => ['icon' => 'fa-bed', 'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'],
                        
                        // කුස්සිය
                        'කුස්සිය' => ['icon' => 'fa-utensils', 'gradient' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'],
                        'කුස්සි' => ['icon' => 'fa-utensils', 'gradient' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'],
                        
                        // නාන කාමර
                        'නාන' => ['icon' => 'fa-bath', 'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'],
                        'වැසිකිලිය' => ['icon' => 'fa-toilet', 'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'],
                        
                        // පුජා කාමර
                        'පුජා' => ['icon' => 'fa-praying-hands', 'gradient' => 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)'],
                        'බුද්ධ' => ['icon' => 'fa-om', 'gradient' => 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)'],
                        'දේවාල' => ['icon' => 'fa-place-of-worship', 'gradient' => 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)'],
                        
                        // මුදල් හා ධනය
                        'මුදල්' => ['icon' => 'fa-coins', 'gradient' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'],
                        'ධන' => ['icon' => 'fa-hand-holding-usd', 'gradient' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'],
                        'සම්පත' => ['icon' => 'fa-gem', 'gradient' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'],
                        
                        // ශක්ති හා බලපෑම
                        'ශක්ති' => ['icon' => 'fa-bolt', 'gradient' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'],
                        'බලපෑම' => ['icon' => 'fa-radiation', 'gradient' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'],
                        
                        // නිවැරදි කිරීම
                        'නිවැරදි' => ['icon' => 'fa-wrench', 'gradient' => 'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)'],
                        'මාරු' => ['icon' => 'fa-exchange-alt', 'gradient' => 'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)'],
                    ];
                    
                    while ($service = $vasthu_services_query->fetch_assoc()) {
                        // Default icon
                        $icon = 'fa-home'; 
                        $gradient = 'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)';
                        
                        $service_name = $service['service_name'];
                        $service_name_lower = mb_strtolower($service_name, 'UTF-8');
                        
                        // Keywords සමග match කිරීමට උත්සාහ කරන්න - දිගම match එක ප්‍රමුඛතාව දෙන්න
                        $matched = false;
                        $longest_match = '';
                        $matched_icon = '';
                        $matched_gradient = '';
                        
                        foreach ($vasthu_icons as $keyword => $style) {
                            $keyword_lower = mb_strtolower($keyword, 'UTF-8');
                            if (mb_strpos($service_name_lower, $keyword_lower) !== false) {
                                // දිගම match වන keyword එක track කරන්න
                                if (mb_strlen($keyword) > mb_strlen($longest_match)) {
                                    $longest_match = $keyword;
                                    $matched_icon = $style['icon'];
                                    $matched_gradient = $style['gradient'];
                                    $matched = true;
                                }
                            }
                        }
                        
                        // සොයාගත් දිගම match එක යොදන්න
                        if ($matched) {
                            $icon = $matched_icon;
                            $gradient = $matched_gradient;
                        }
                        ?>
                        <div class="col-md-6 col-lg-4 mb-4 scroll-reveal" style="animation-delay: <?php echo $delay; ?>s;">
                            <div class="card service-card vasthu-card h-100 shadow">
                                <div class="card-body">
                                    <div class="icon-wrapper" style="background: <?php echo $gradient; ?>;">
                                        <i class="fas <?php echo $icon; ?> fa-2x text-white"></i>
                                    </div>
                                    <h5 class="card-title fw-bold text-center mb-3">
                                        <?php echo htmlspecialchars($service['service_name']); ?>
                                    </h5>
                                    <p class="card-text text-muted text-center">
                                        <?php echo htmlspecialchars($service['description']); ?>
                                    </p>
                                    <div class="text-center mb-3">
                                        <span class="price-badge">
                                            රු. <?php echo number_format($service['price'], 2); ?>
                                        </span>
                                    </div>
                                    <div class="text-center">
                                        <a href="service_details.php?id=<?php echo $service['id']; ?>" 
                                           class="btn btn-request w-100">
                                            <i class="fas fa-paper-plane me-2"></i> ඉල්ලීමක් කරන්න
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                        $delay += 0.1;
                    }
                } else {
                    echo '<div class="col-12"><div class="alert alert-custom alert-info border-info"><i class="fas fa-info-circle me-2"></i>වාස්තු සේවා දැනට ඇතුළත් කර නොමැත.</div></div>';
                }
                ?>
            </div>
        </div>

        <!-- ============================================
             CALL TO ACTION SECTION
        ============================================ -->
        <div class="cta-section text-center my-5 text-white scroll-reveal">
            <!-- Animated stars -->
            <div class="cta-stars">
                <?php for($i = 0; $i < 5; $i++): ?>
                    <div class="cta-star" style="left: <?php echo (10 + $i * 20); ?>%; top: <?php echo (20 + $i * 10); ?>%; animation-delay: <?php echo ($i * 0.5); ?>s;"></div>
                <?php endfor; ?>
            </div>
            
            <div class="position-relative" style="z-index: 10;">
                <h3 class="fw-bold mb-3 display-5">අදම අප හා සම්බන්ධ වන්න</h3>
                <p class="lead mb-4">ඔබගේ ගැටලුව විසඳා ගැනීමට අපගේ විශේෂඥයන් සූදානම්</p>
                <a href="https://wa.me/<?php echo $whatsapp_number; ?>" target="_blank" class="btn whatsapp-btn">
                    <i class="fab fa-whatsapp fa-2x me-2"></i> WhatsApp මගින් සම්බන්ධ වන්න
                </a>
            </div>
        </div>

    </div>

    <!-- ============================================
         BACK TO TOP BUTTON
    ============================================ -->
    <button id="backToTop" class="btn btn-primary position-fixed bottom-0 end-0 m-4 rounded-circle shadow d-none" 
            aria-label="Back to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- ============================================
         JAVASCRIPT
    ============================================ -->
    <script>
        // Back to Top Button
        const backToTopBtn = document.getElementById('backToTop');
        
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopBtn.classList.remove('d-none');
            } else {
                backToTopBtn.classList.add('d-none');
            }
        });
        
        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Scroll Reveal Animation
        function revealOnScroll() {
            const reveals = document.querySelectorAll('.scroll-reveal');
            
            reveals.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const windowHeight = window.innerHeight;
                const revealPoint = 100;
                
                if (elementTop < windowHeight - revealPoint) {
                    element.classList.add('active');
                }
            });
        }
        
        window.addEventListener('scroll', revealOnScroll);
        window.addEventListener('load', revealOnScroll);
        revealOnScroll();
    </script>
    
    <?php $conn->close(); ?>
</body>
</html>
<?php include('footer.php'); ?>