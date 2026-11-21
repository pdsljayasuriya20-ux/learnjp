<?php
/**
 * නෙත්සිත ජෝතිශ්‍ය සේවය - About Page
 * Nethsitha Jyothishya Sewaya - About Us Page
 * 
 * Displays information about the astrology service, astrologer profile,
 * core values, expertise, and journey timeline with cosmic-themed design.
 */

// ============================================
// INITIALIZATION & DEPENDENCIES
// ============================================

// Include header (loads db_connection.php and settings_loader.php)
// This makes $conn object and $app_settings array available
include 'header.php';

// Define base URL for proper image path resolution
define('BASE_URL', '/');

// ============================================
// DEBUG & VALIDATION (Remove in production)
// ============================================

// Verify settings are loaded properly
if (isset($app_settings['about_profile_image'])) {
    // Settings loaded successfully
    // echo "<!-- DEBUG: Settings loaded. Image: " . htmlspecialchars($app_settings['about_profile_image']) . " -->";
} else {
    // Log error if settings not loaded
    error_log("About Page - Settings not loaded properly");
}

?>
<!DOCTYPE html>
<html lang="si">
<head>
    <!-- ============================================
         META TAGS & SEO
    ============================================ -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <title>අප ගැන - <?php echo htmlspecialchars($app_settings['site_title']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($app_settings['meta_description']); ?>">
    
    <!-- ============================================
         STYLESHEETS
    ============================================ -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- ============================================
         PAGE HEADER WITH COSMIC ANIMATIONS
    ============================================ -->
    <header class="page-header text-center">
        <!-- Animated Stars -->
        <div class="header-stars">
            <?php for ($i = 0; $i < 8; $i++): ?>
                <div class="header-star"></div>
            <?php endfor; ?>
        </div>
        
        <!-- Orbit Ring -->
        <div class="orbit-ring">
            <div class="orbit-dot"></div>
        </div>
        
        <!-- Header Content -->
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">අප ගැන</h1>
            <p class="lead fs-5">විශ්වාසනීය, පළපුරුදු සහ පෞද්ගලික ජ්‍යෝතිෂ හා වාස්තු සේවාව</p>
        </div>
    </header>

    <!-- ============================================
         MAIN CONTENT
    ============================================ -->
    <div class="container my-5">
        
        <!-- ========================================
             PROFILE SECTION
        ======================================== -->
        <div class="row align-items-center mb-5 scroll-reveal">
            <!-- Profile Image & Contact -->
            <div class="col-lg-4 text-center mb-4 mb-lg-0">
                <img src="<?php echo htmlspecialchars($app_settings['about_profile_image']); ?>" 
                     class="img-fluid rounded-circle profile-image" 
                     alt="<?php echo htmlspecialchars($app_settings['about_profile_name']); ?>"
                     onerror="this.src='https://via.placeholder.com/250x250/667eea/ffffff?text=ජ්‍යෝතිෂවේදියා'"
                     style="max-width: 250px;">
                
                <h3 class="fw-bold mt-4 mb-2">
                    <?php echo htmlspecialchars($app_settings['about_profile_name']); ?>
                </h3>
                
                <p class="text-muted mb-3">
                    <i class="fas fa-user-tie me-2"></i>
                    <?php echo htmlspecialchars($app_settings['about_profile_title']); ?>
                </p>
                
                <p class="small text-muted mb-3">ජ්‍යෝතිෂවේදී කැළණිය විශ්ව විද්‍යාලය</p> 
                
                <!-- WhatsApp Contact Button -->
                <div class="d-flex justify-content-center gap-3">
                    <a href="https://wa.me/<?php echo htmlspecialchars(str_replace([' ', '+'], '', $app_settings['social_whatsapp'])); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="btn btn-success whatsapp-btn">
                        <i class="fab fa-whatsapp me-2"></i>සම්බන්ධ වන්න
                    </a>
                </div>
            </div>
            
            <!-- Vision & Mission -->
            <div class="col-lg-8">
                <div class="info-card shadow p-4">
                    <h2 class="fw-bold mb-4" style="color: #667eea;">
                        <i class="fas fa-eye me-2"></i>අපගේ දැක්ම සහ මෙහෙවර
                    </h2>
                    <p class="lead mb-3">
                        නෙත්සිත ජෝතිශ්‍ය සේවය ආරම්භ කරන ලද්දේ පාරම්පරික ජ්‍යෝතිෂ දැනුම නවීන සමාජයේ අවශ්‍යතා සමඟ මුසු කරමින්, ජනතාවට නිවැරදි දිශාව පෙන්වීමේ අරමුණෙනි.
                    </p>
                    <p class="mb-3">
                        අපගේ මෙහෙවර වන්නේ සෑම පුද්ගලයෙකුගේම ජීවන ගමනට බලපාන ග්‍රහ පිහිටීම් සහ වාස්තු දෝෂ නිවැරදිව හඳුනාගෙන, ඒවාට විද්‍යානුකූල සහ විශ්වාසනීය පිළියම් ලබා දීමයි.
                    </p>
                    <div class="alert alert-info border-0 shadow-sm" 
                         style="background: linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%); border-left: 4px solid #667eea !important;">
                        <i class="fas fa-shield-alt me-2"></i>
                        <strong>අප සෑම සේවාවක්ම සිදු කරන්නේ උපරිම රහස්‍යභාවය සහ පෞද්ගලික අවධානය තහවුරු කරමිනි.</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================
             STATISTICS SECTION
        ======================================== -->
        <div class="row mb-5">
            <?php
            $stats = [
                [
                    'icon' => 'fas fa-users',
                    'number' => '500+',
                    'label' => 'තෘප්තිමත් පාරිභෝගිකයින්',
                    'delay' => '0s'
                ],
                [
                    'icon' => 'fas fa-calendar-check',
                    'number' => '10+',
                    'label' => 'වසරක පළපුරුද්ද',
                    'delay' => '0.2s'
                ],
                [
                    'icon' => 'fas fa-star',
                    'number' => '100%',
                    'label' => 'රහස්‍යභාවය',
                    'delay' => '0.4s'
                ]
            ];
            
            foreach ($stats as $stat): ?>
                <div class="col-md-4 scroll-reveal" style="animation-delay: <?php echo $stat['delay']; ?>;">
                    <div class="stat-card">
                        <i class="<?php echo $stat['icon']; ?>" aria-hidden="true"></i>
                        <h3><?php echo $stat['number']; ?></h3>
                        <p class="mb-0"><?php echo $stat['label']; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ========================================
             CORE VALUES SECTION
        ======================================== -->
        <div class="mb-5 scroll-reveal">
            <h2 class="text-center fw-bold mb-5" style="color: #1a1a3e;">අපගේ මූලධර්ම</h2>
            <div class="row g-4">
                <?php
                $values = [
                    [
                        'icon' => 'fas fa-handshake',
                        'class' => 'trust',
                        'title' => 'විශ්වාසනීයත්වය',
                        'description' => 'සියලුම ගණනය කිරීම් සහ උපදේශනය සඳහා ඉහළම නිරවද්‍යතාවය සහ ප්‍රමිතීන් අනුගමනය කිරීම.'
                    ],
                    [
                        'icon' => 'fas fa-book-reader',
                        'class' => 'knowledge',
                        'title' => 'ගැඹුරු දැනුම',
                        'description' => 'වසර ගණනාවක් තිස්සේ පැවත එන පාරම්පරික ජ්‍යෝතිෂ ශාස්ත්‍රය සහ වාස්තු විද්‍යාව පිළිබඳ ප්‍රවීණත්වය.'
                    ],
                    [
                        'icon' => 'fas fa-heart',
                        'class' => 'dedication',
                        'title' => 'කැපවීම',
                        'description' => 'සෑම පාරිභෝගිකයෙකුටම පෞද්ගලිකව සවන් දී, ඔවුන්ගේ අවශ්‍යතා අනුව විසඳුම් ලබා දීම.'
                    ]
                ];
                
                foreach ($values as $value): ?>
                    <div class="col-md-4">
                        <div class="value-box">
                            <div class="value-icon <?php echo $value['class']; ?>">
                                <i class="<?php echo $value['icon']; ?>" aria-hidden="true"></i>
                            </div>
                            <h5 class="fw-bold mb-3"><?php echo $value['title']; ?></h5>
                            <p class="text-muted mb-0"><?php echo $value['description']; ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ========================================
             EXPERTISE SECTION
        ======================================== -->
        <div class="info-card shadow p-4 mb-5 scroll-reveal">
            <h2 class="fw-bold mb-4" style="color: #1a1a3e;">
                <i class="fas fa-certificate me-2" style="color: #ffc107;"></i>අපගේ විශේෂත්වයන්
            </h2>
            <ul class="list-group list-group-flush expertise-list">
                <?php
                $expertise = [
                    [
                        'title' => 'සම්පූර්ණ විශ්වාසනීයත්වය',
                        'description' => 'සියලුම ගණනය කිරීම් සඳහා ඉහළම ගණිතමය නිරවද්‍යතාවයක් භාවිතා කිරීම.'
                    ],
                    [
                        'title' => 'පාරම්පරික දැනුම',
                        'description' => 'වසර ගණනාවක් තිස්සේ පැවත එන ජ්‍යෝතිෂ ශාස්ත්‍රය පිළිබඳ ගැඹුරු අවබෝධය.'
                    ],
                    [
                        'title' => 'වාස්තු හා ඉංජිනේරු දැනුම',
                        'description' => 'වාස්තු විද්‍යානුකූල සැලසුම්කරණය සඳහා නවීන දැනුම යෙදවීම.'
                    ],
                    [
                        'title' => 'නවීන තාක්ෂණය',
                        'description' => 'නිවැරදි ගණනය කිරීම් සහ විශ්ලේෂණ සඳහා නවීන මෘදුකාංග භාවිතය.'
                    ],
                    [
                        'title' => 'පෞද්ගලික සේවාව',
                        'description' => 'සෑම පාරිභෝගිකයෙකුටම විශේෂ අවධානයක් සහ රහස්‍යභාවයක් ලබා දීම.'
                    ]
                ];
                
                foreach ($expertise as $item): ?>
                    <li class="list-group-item">
                        <i class="fas fa-check-circle me-2" style="color: #84fab0;"></i>
                        <strong><?php echo $item['title']; ?>:</strong> <?php echo $item['description']; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- ========================================
             JOURNEY TIMELINE
        ======================================== -->
        <div class="info-card shadow p-4 mb-5 scroll-reveal">
            <h2 class="fw-bold mb-4" style="color: #1a1a3e;">
                <i class="fas fa-route me-2" style="color: #667eea;"></i>අපගේ ගමන
            </h2>
            <div class="timeline">
                <?php
                $journey = [
                    [
                        'icon' => 'fas fa-graduation-cap',
                        'title' => 'අධ්‍යාපනය සහ පුහුණුව',
                        'description' => 'සම්ප්‍රදායික ජ්‍යෝතිෂ ශාස්ත්‍රය සහ වාස්තු විද්‍යාව පිළිබඳ උසස් අධ්‍යාපනය සහ විශේෂඥ පුහුණුව.'
                    ],
                    [
                        'icon' => 'fas fa-rocket',
                        'title' => 'සේවය ආරම්භය',
                        'description' => 'ජනතාවට විශ්වාසනීය සහ නිවැරදි ජ්‍යෝතිෂ උපදේශනය ලබා දීමේ අරමුණින් නෙත්සිත ජෝතිශ්‍ය සේවය ආරම්භ කිරීම.'
                    ],
                    [
                        'icon' => 'fas fa-users',
                        'title' => 'පාරිභෝගික විශ්වාසය',
                        'description' => 'සියගණනක් තෘප්තිමත් පාරිභෝගිකයින්ගේ විශ්වාසය හා සහයෝගය අත්කර ගැනීම.'
                    ]
                ];
                
                foreach ($journey as $milestone): ?>
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="<?php echo $milestone['icon']; ?>"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold"><?php echo $milestone['title']; ?></h5>
                            <p class="text-muted"><?php echo $milestone['description']; ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ========================================
             CALL TO ACTION SECTION
        ======================================== -->
        <div class="cta-section text-center text-white scroll-reveal">
            <!-- Animated Stars -->
            <div class="header-stars">
                <?php
                $starPositions = [
                    ['left' => '10%', 'top' => '20%', 'delay' => '0s'],
                    ['left' => '30%', 'top' => '60%', 'delay' => '0.5s'],
                    ['left' => '50%', 'top' => '30%', 'delay' => '1s'],
                    ['left' => '70%', 'top' => '70%', 'delay' => '1.5s'],
                    ['left' => '90%', 'top' => '40%', 'delay' => '2s']
                ];
                
                foreach ($starPositions as $pos): ?>
                    <div class="header-star" style="left: <?php echo $pos['left']; ?>; top: <?php echo $pos['top']; ?>; animation-delay: <?php echo $pos['delay']; ?>;"></div>
                <?php endforeach; ?>
            </div>
            
            <!-- CTA Content -->
            <div class="position-relative" style="z-index: 10;">
                <h3 class="fw-bold mb-3 display-5">ඔබේ ජීවිතයට නිවැරදි මාර්ගය සොයා ගන්න</h3>
                <p class="lead mb-4">විශේෂඥ උපදේශනයක් සඳහා අදම අප හා සම්බන්ධ වන්න</p>
                
                <!-- CTA Buttons -->
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <?php
                    $ctaButtons = [
                        [
                            'url' => 'services.php',
                            'icon' => 'fas fa-list',
                            'text' => 'සේවා බලන්න',
                            'style' => 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white;'
                        ],
                        [
                            'url' => 'contact.php',
                            'icon' => 'fas fa-paper-plane',
                            'text' => 'සම්බන්ධ වන්න',
                            'style' => 'background: white; color: #667eea; border: 2px solid white;'
                        ],
                        [
                            'url' => 'https://wa.me/' . str_replace([' ', '+'], '', $app_settings['social_whatsapp']),
                            'icon' => 'fab fa-whatsapp',
                            'text' => 'WhatsApp',
                            'class' => 'whatsapp-btn',
                            'external' => true
                        ]
                    ];
                    
                    foreach ($ctaButtons as $button): ?>
                        <a href="<?php echo $button['url']; ?>" 
                           class="btn btn-lg shadow <?php echo $button['class'] ?? ''; ?>" 
                           <?php if (isset($button['external'])): ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>
                           style="<?php echo $button['style'] ?? ''; ?> border-radius: 50px; padding: 15px 40px; font-weight: 600; transition: all 0.3s ease;">
                            <i class="<?php echo $button['icon']; ?> me-2"></i><?php echo $button['text']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- ============================================
         BACK TO TOP BUTTON
    ============================================ -->
    <button id="backToTop" 
            class="btn btn-primary position-fixed bottom-0 end-0 m-4 rounded-circle shadow d-none" 
            aria-label="Back to top">
        <i class="fas fa-arrow-up"></i>
    </button>
    
    <!-- ============================================
         JAVASCRIPT
    ============================================ -->
    <script>
        // ========================================
        // Back to Top Button Functionality
        // ========================================
        (function() {
            const backToTopButton = document.getElementById('backToTop');
            
            // Show/hide button based on scroll position
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    backToTopButton.classList.remove('d-none');
                } else {
                    backToTopButton.classList.add('d-none');
                }
            });
            
            // Scroll to top on click
            backToTopButton.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        })();
        
        // ========================================
        // Scroll Reveal Animation
        // ========================================
        function revealOnScroll() {
            const reveals = document.querySelectorAll('.scroll-reveal');
            const windowHeight = window.innerHeight;
            const revealPoint = 100;
            
            reveals.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                
                if (elementTop < windowHeight - revealPoint) {
                    element.classList.add('active');
                }
            });
        }
        
        // Initialize scroll reveal
        window.addEventListener('scroll', revealOnScroll);
        window.addEventListener('load', revealOnScroll);
        revealOnScroll();
        
        // ========================================
        // Smooth Scroll for Anchor Links
        // ========================================
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const target = this.getAttribute('href');
                if (target !== '#' && document.querySelector(target)) {
                    e.preventDefault();
                    document.querySelector(target).scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // ========================================
        // CTA Button Hover Effects
        // ========================================
        document.querySelectorAll('.cta-section .btn').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.05)';
            });
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>

<?php 
// ============================================
// CLEANUP & FOOTER
// ============================================

// Close database connection
if (isset($conn) && $conn) {
    $conn->close();
}

// Include footer
include('footer.php'); 
?>