<?php
/**
 * නෙත්සිත ජෝතිශ්‍ය සේවය - පොදු Header ගොනුව
 * Nethsitha Jyothishya Sewaya - Universal Header File
 */

// CRITICAL: Set UTF-8 encoding for output
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// 1. Session ආරම්භ කිරීම
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Database සම්බන්ධතාවය
include('db_connection.php');

// සම්බන්ධතාවය නිර්මාණය කිරීම
$conn = new mysqli($servername, $username, $password, $dbname);

// සම්බන්ධතාවය පරීක්ෂා කිරීම
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    $conn = false; 
} else {
    // CRITICAL: Set charset to UTF-8
    $conn->set_charset("utf8mb4");
}

// 3. සැකසුම් පූරණය කිරීම
$app_settings_defaults = [
    'contact_email' => 'info@netsita.lk', 
    'contact_phone' => '071 239 1317', 
    'map_embed_code' => '',
    'site_title' => 'නෙත්සිත ජෝතිශ්‍ය සේවය',
    'meta_description' => 'ඔබගේ ජ්‍යොතිෂ්‍යමය අවශ්‍යතා සඳහා විශ්වාසනීය සහ නිවැරදි සේවාව.',
    'meta_keywords' => 'ජෝතිෂය, හෝරා, නැකත්, අංකශාස්ත්‍රය, astrology, horoscope, Sri Lanka',
    'site_logo_path' => 'uploads/default_logo.png',
    'site_favicon_path' => 'images/favicon.png',
    'og_image_path' => 'uploads/default_og_image.png',
];

$db_settings = [];
if (is_object($conn)) { 
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM app_settings");
    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $db_settings[$row['setting_key']] = $row['setting_value'];
        }
        $stmt->close();
    } else {
        error_log("Settings load failed in header.php: " . ($conn->error ?? 'Unknown error'));
    }
}
$app_settings = array_merge($app_settings_defaults, $db_settings);

// 4. පරිශීලක තත්ත්වය පරීක්ෂා කිරීම
$isLoggedIn = isset($_SESSION['client_logged_in']) && $_SESSION['client_logged_in'] === true;
$clientName = $isLoggedIn ? $_SESSION['client_name'] : '';
$clientEmail = $isLoggedIn ? $_SESSION['client_email'] : '';

// 5. ප්‍රදර්ශනය සඳහා පළමු නම ලබා ගැනීම
$firstName = $clientName;
if(strpos($clientName, ' ') !== false) {
    $firstName = explode(' ', $clientName)[0];
}
?>
<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($app_settings['meta_description']); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($app_settings['meta_keywords']); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($app_settings['site_title']); ?>">
    
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' . htmlspecialchars($app_settings['site_title']) : htmlspecialchars($app_settings['site_title']); ?></title>
    
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($app_settings['site_favicon_path']); ?>">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/header.css">
    
    
</head>
<body>

    <div class="cosmic-particles d-none d-lg-block">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="top-info-bar text-white py-2 d-none d-md-block">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <small>
                        <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($app_settings['contact_phone']); ?>
                    </small>
                    <small class="ms-3">
                        <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($app_settings['contact_email']); ?>
                    </small>
                </div>
                <div class="col-md-6 text-end">
                    <small>
                        <i class="fas fa-clock me-2"></i>සඳුදා - ඉරිදා: 8:00 AM - 8:00 PM
                    </small>
                </div>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark sticky-top" id="mainNavbar">
        <div class="navbar-stars">
            <div class="navbar-star"></div>
            <div class="navbar-star"></div>
            <div class="navbar-star"></div>
            <div class="navbar-star"></div>
            <div class="navbar-star"></div>
        </div>
        
        <div class="container">
            <a class="navbar-brand fw-bold fs-4" href="index.php">
                <i class="fas fa-star text-warning me-2"></i>
                <?php echo htmlspecialchars($app_settings['site_title']); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" 
                            href="index.php">
                            <i class="fas fa-home me-1"></i>මුල් පිටුව
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'services.php') ? 'active' : ''; ?>" 
                            href="services.php">
                            <i class="fas fa-th-large me-1"></i>සේවා
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active' : ''; ?>" 
                            href="about.php">
                            <i class="fas fa-info-circle me-1"></i>අප ගැන
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'contact.php') ? 'active' : ''; ?>" 
                            href="contact.php">
                            <i class="fas fa-envelope me-1"></i>සම්බන්ධ වන්න
                        </a>
                    </li>
                    
                    <?php if($isLoggedIn): ?>
                        <li class="nav-item dropdown user-dropdown ms-lg-2">
                            <a class="user-menu-btn dropdown-toggle" href="#" id="userDropdown" role="button" 
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="user-avatar">
                                    <?php echo mb_substr($firstName, 0, 1, 'UTF-8'); ?>
                                </div>
                                <span class="user-name d-none d-lg-inline"><?php echo htmlspecialchars($firstName); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li>
                                    <h6 class="dropdown-header">
                                        <a href="dashboard.php">
                                            <i class="fas fa-user-circle me-2"></i>
                                            <?php echo htmlspecialchars($clientName); ?>
                                        </a>
                                    </h6>
                                </li>
                                <li><div class="user-email"><?php echo htmlspecialchars($clientEmail); ?></div></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="dashboard.php">
                                        <i class="fas fa-tachometer-alt"></i>
                                        <span>Dashboard</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="my_consultations.php">
                                        <i class="fas fa-list-alt"></i>
                                        <span>මගේ උපදේශන</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="profile.php">
                                        <i class="fas fa-user-edit"></i>
                                        <span>පැතිකඩ සංස්කරණය</span>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="logout.php" 
                                       onclick="return confirm('ඔබට logout වීමට අවශ්‍යද?');">
                                        <i class="fas fa-sign-out-alt"></i>
                                        <span>Logout</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item ms-lg-2">
    <a class="btn login-btn fw-bold" href="admin/login.php" target="_blank">
        <i class="fas fa-sign-in-alt me-2"></i>
        <span>Login</span>
    </a>
</li>


                    <?php endif; ?>
                    
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a class="btn btn-success whatsapp-btn text-white fw-bold" 
                            href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', htmlspecialchars($app_settings['contact_phone'])); ?>" 
                            target="_blank" 
                            rel="noopener noreferrer"
                            aria-label="WhatsApp හරහා අමතන්න">
                            <i class="fab fa-whatsapp me-2"></i>WhatsApp
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <script>
        // Add scrolled class to navbar on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('mainNavbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
        
        // Mobile menu animation trigger
        const navbarToggler = document.querySelector('.navbar-toggler');
        const navbarCollapse = document.querySelector('.navbar-collapse');
        
        if (navbarToggler && navbarCollapse) {
            navbarToggler.addEventListener('click', function() {
                if (!navbarCollapse.classList.contains('show')) {
                    setTimeout(() => {
                        const navItems = document.querySelectorAll('.nav-item');
                        navItems.forEach((item, index) => {
                            item.style.animation = `slideInFromLeft 0.3s ease-out ${index * 0.1}s forwards`;
                        });
                    }, 100);
                }
            });
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>