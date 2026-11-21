<?php
    include 'header.php';
  
/**
 * නෙත්සිත ජෝතිශ්‍ය සේවය - Main Index Page
 * Improved version with security, error handling, SEO enhancements and beautiful animations
 */

// Error handling and security
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start session if needed
session_start();

// Define base URL
define('BASE_URL', '/');

// Database connection with error handling
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

$panchangaya_data = null;
$today_date = date('Y-m-d'); // අද දින (YYYY-MM-DD format)

if (!$db_error) { // දත්ත සමුදා සම්බන්ධතාවයේ දෝෂයක් නොමැති නම් පමණක් ඉදිරියට යන්න
    try {
        // CURDATE() භාවිතයෙන් අද දිනට අදාළ දත්ත ලබා ගන්න
        $stmt = $conn->prepare("SELECT * FROM daily_panchangaya WHERE date = ? LIMIT 1");
        $stmt->bind_param("s", $today_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $panchangaya_data = $result->fetch_assoc();
        }
        $stmt->close();
        
    } catch (Exception $e) {
        // දත්ත ලබා ගැනීමේ දෝෂ
        error_log("Panchangaya Data Fetch Error: " . $e->getMessage());
        $db_error = true;
    }
}
// Helper function for safe output
function safe_output($text, $max_length = null) {
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    if ($max_length && mb_strlen($text) > $max_length) {
        $text = mb_substr($text, 0, $max_length) . '...';
    }
    return $text;
}
?>
<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
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
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" 
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" 
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <!-- Custom Styles -->
 <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>

    <!-- Hero Section with Cosmic Animations -->
    <header class="hero-section text-center">
        <!-- Animated Stars -->
        <div class="stars">
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
        </div>
        
        <!-- Zodiac Symbols -->
        <div class="zodiac-symbols">
            <div class="zodiac-symbol">♈</div>
            <div class="zodiac-symbol">♉</div>
            <div class="zodiac-symbol">♊</div>
            <div class="zodiac-symbol">♋</div>
        </div>
        
        <!-- Orbiting Planets -->
        <div class="cosmic-orbit">
            <div class="planet planet-1"></div>
            <div class="planet planet-2"></div>
            <div class="planet planet-3"></div>
        </div>
        
        <!-- Moon -->
        <div class="moon"></div>
        
        <div class="container">
            <h1 class="display-3 fw-bolder mb-4">
                ඔබේ ජීවිතයට නිවැරදි දිශාව
            </h1>
            <p class="lead fs-4 mb-4">
                සියලුම ජ්‍යෝතිෂ හා වාස්තු ගැටලු සඳහා විශ්වාසවන්ත හා පළපුරුදු සේවාව අපෙන්...
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="services.php" class="btn btn-primary btn-lg shadow">
                    <i class="fas fa-list me-2"></i>සියලුම සේවා බලන්න
                </a>
                <a href="contact.php" class="btn btn-outline-light btn-lg shadow">
                    <i class="fas fa-paper-plane me-2"></i>විමසීමක් කරන්න
                </a>
            </div>
        </div>
    </header>

    <!-- Daily Panchangaya Section -->
 
    
    <section class="py-5 bg-white shadow-sm scroll-reveal">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h2 class="section-heading mb-4 text-dark"><i class="fas fa-sun fa-spin text-warning me-2"></i> අද දින පංචාංගම සටහන</h2>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card shadow-lg border-0 testimonial-card" style="background: linear-gradient(135deg, #e9ecef 0%, #ffffff 100%);">
                        <div class="card-body">
                            <h3 class="text-center mb-4" style="color: var(--cosmic-blue);"><?php echo safe_output(date('Y-m-d')); ?> දිනය සඳහා</h3>

                            <?php if ($db_error): ?>
                                <div class="alert alert-danger alert-custom text-center mt-3" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i> දත්ත සමුදා සම්බන්ධතා දෝෂයක්. කරුණාකර නැවත පරීක්ෂා කරන්න.
                                </div>
                            <?php elseif ($panchangaya_data): 
                                // දත්ත සාර්ථකව ලැබී ඇත්නම්, ඒවා පෙන්වන්න
                                $nakatha_name = safe_output($panchangaya_data['nakatha']);
                                $rahu_start = safe_output($panchangaya_data['rahu_kala_start']);
                                $rahu_end = safe_output($panchangaya_data['rahu_kala_end']);
                                $yama_start = safe_output($panchangaya_data['yama_kanda_start']);
                                $yama_end = safe_output($panchangaya_data['yama_kanda_end']);
                            ?>
                                <div class="row text-center mt-4">
                                    
                                    <div class="col-md-4 mb-3">
                                        <div class="p-3 border rounded-3 bg-light h-100 shadow-sm">
                                            <i class="fas fa-star fa-2x text-warning mb-2 stat-icon"></i>
                                            <h5 class="text-muted small">නැකත</h5>
                                            <p class="h4 text-primary"><?php echo $nakatha_name; ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <div class="p-3 border rounded-3 bg-light h-100 shadow-sm">
                                            <i class="fas fa-skull fa-2x text-danger mb-2 stat-icon" style="animation-delay: 0.2s;"></i>
                                            <h5 class="text-muted small">රාහු කාලය (අවස්ථාව)</h5>
                                            <p class="h5 text-danger"><?php echo $rahu_start . ' - ' . $rahu_end; ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <div class="p-3 border rounded-3 bg-light h-100 shadow-sm">
                                            <i class="fas fa-clock fa-2x text-success mb-2 stat-icon" style="animation-delay: 0.4s;"></i>
                                            <h5 class="text-muted small">යම කණ්ඩය (ශුභ)</h5>
                                            <p class="h5 text-success"><?php echo $yama_start . ' - ' . $yama_end; ?></p>
                                        </div>
                                    </div>

                                    <div class="col-12 mt-4 text-muted small">
                                        <p class="mb-1"><i class="fas fa-angle-right me-2 text-primary"></i> <b>තිථිය:</b> <?php echo safe_output($panchangaya_data['thithiya']); ?></p>
                                        <p class="mb-1"><i class="fas fa-angle-right me-2 text-primary"></i> <b>යෝගය:</b> <?php echo safe_output($panchangaya_data['yoga']); ?></p>
                                        <p class="mb-1"><i class="fas fa-angle-right me-2 text-primary"></i> <b>කරණය:</b> <?php echo safe_output($panchangaya_data['karana']); ?></p>
                                        <p class="mb-1"><i class="fas fa-angle-right me-2 text-primary"></i> <b>හිරු උදාව/බැසීම:</b> <?php echo safe_output($panchangaya_data['sunrise_time']) . ' / ' . safe_output($panchangaya_data['sunset_time']); ?></p>
                                    </div>
                                    
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning alert-custom text-center mt-3" role="alert">
                                    <i class="fas fa-hourglass-half me-2"></i> අද දින පංචාංගම දත්ත තවම **යාවත්කාලීන කර නොමැත**. කරුණාකර පසුව නැවත පරීක්ෂා කරන්න.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    

    <!-- Services Overview Section -->
    <section id="services-overview" class="py-5 position-relative">
        <!-- Vasthu Patterns -->
        <div class="vasthu-pattern">
            <i class="fas fa-home fa-5x text-success"></i>
        </div>
        <div class="vasthu-pattern">
            <i class="fas fa-star fa-5x text-warning"></i>
        </div>
        <div class="vasthu-pattern">
            <i class="fas fa-yin-yang fa-5x text-info"></i>
        </div>
        
        <div class="container">
            <div class="text-center mb-5 scroll-reveal">
                <h2 class="fw-bold section-heading">අපගේ ප්‍රධාන සේවාවන්</h2>
            </div>
            
            <?php if ($db_error): ?>
                <!-- Database Error Message -->
                <div class="alert alert-warning alert-custom text-center" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>දත්ත පූරණය කිරීමේදී දෝෂයක් ඇතිවිය.</strong> කරුණාකර පසුව නැවත උත්සාහ කරන්න.
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php
                    $stmt = $conn->prepare("SELECT id, service_name, description, category FROM services ORDER BY FIELD(category, 'Jothishya', 'Vasthu') LIMIT 3");
                    
                    if ($stmt) {
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            while ($service = $result->fetch_assoc()) {
                                $icon = 'fas fa-star';
                                $icon_color = 'text-primary';
                                $card_class = 'jothishya';
                                
                                if (isset($service['category'])) {
                                    switch($service['category']) {
                                        case 'Jothishya':
                                            $icon = 'fas fa-star';
                                            $icon_color = 'text-primary';
                                            $card_class = 'jothishya';
                                            break;
                                        case 'Vasthu':
                                            $icon = 'fas fa-home';
                                            $icon_color = 'text-success';
                                            $card_class = 'vasthu';
                                            break;
                                        default:
                                            $icon = 'fas fa-magic';
                                            $icon_color = 'text-info';
                                            $card_class = '';
                                    }
                                }
                                ?>
                                <div class="col-lg-4 col-md-6 scroll-reveal">
                                    <div class="card service-card <?php echo $card_class; ?> shadow-sm h-100">
                                        <div class="card-body text-center">
                                            <i class="<?php echo $icon . ' ' . $icon_color; ?> fa-3x mb-3" aria-hidden="true"></i>
                                            <h5 class="card-title fw-bold mb-3">
                                                <?php echo safe_output($service['service_name']); ?>
                                            </h5>
                                            <p class="card-text text-muted">
                                                <?php echo safe_output($service['description'], 100); ?>
                                            </p>
                                            <a href="service_details.php?id=<?php echo intval($service['id']); ?>" 
                                               class="btn btn-outline-primary mt-2">
                                                <i class="fas fa-arrow-right me-2"></i>තව දැනගන්න
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            ?>
                            <div class="col-12">
                                <div class="alert alert-info alert-custom text-center" role="alert">
                                    <i class="fas fa-info-circle me-2"></i>
                                    ප්‍රදර්ශනය කිරීමට සේවා නොමැත. කරුණාකර Admin Panel හරහා සේවා ඇතුළත් කරන්න.
                                </div>
                            </div>
                            <?php
                        }
                        
                        $stmt->close();
                    } else {
                        ?>
                        <div class="col-12">
                            <div class="alert alert-danger alert-custom text-center" role="alert">
                                <i class="fas fa-times-circle me-2"></i>
                                දත්ත ලබා ගැනීමේදී දෝෂයක් ඇතිවිය.
                            </div>
                        </div>
                        <?php
                        error_log("SQL Prepare Error: " . $conn->error);
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="text-center mt-5 scroll-reveal">
                <a href="services.php" class="btn btn-lg btn-secondary shadow">
                    <i class="fas fa-th me-2"></i>සියලුම සේවාවන් ලැයිස්තුව
                </a>
            </div>
        </div>
    </section>
    
    <!-- Testimonials Section -->
    <section id="testimonials" class="py-5 bg-light position-relative overflow-hidden">
        <!-- Floating elements background -->
        <div class="floating-elements">
            <div class="float-element" style="top: 10%; left: 5%;">⭐</div>
            <div class="float-element" style="top: 20%; right: 10%;">✨</div>
            <div class="float-element" style="bottom: 20%; left: 15%;">🌟</div>
            <div class="float-element" style="bottom: 10%; right: 5%;">💫</div>
        </div>
        
        <div class="container position-relative" style="z-index: 10;">
            <div class="text-center mb-5 scroll-reveal">
                <h2 class="fw-bold section-heading">පාරිභෝගික අදහස්</h2>
                <p class="text-muted">අපගේ සේවා ලැබූ සතුටුදායක පාරිභෝගිකයන්</p>
            </div>
            
            <div class="row g-4">
                <!-- Testimonial 1 -->
                <div class="col-lg-4 col-md-6 scroll-reveal">
                    <div class="testimonial-card">
                        <div class="quote-icon">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <div class="rating mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="testimonial-text">
                            "ඉතා විශ්වාසවන්ත සහ නිවැරදි උපදේශනයක් ලැබුණා. මගේ ජීවිතයේ වැදගත් තීරණ ගන්න මට හොඳ මග පෙන්වීමක් ලැබුණා."
                        </p>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">නිමල් පෙරේරා</h6>
                                <small class="text-muted">කොළඹ</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Testimonial 2 -->
                <div class="col-lg-4 col-md-6 scroll-reveal">
                    <div class="testimonial-card">
                        <div class="quote-icon">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <div class="rating mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="testimonial-text">
                            "වාස්තු විද්‍යා උපදෙස් අනුව නිවස සකස් කළ පසු අපේ පවුලේ සාමය හා සෞභාග්‍යය වැඩිවුණා. ස්තූතියි!"
                        </p>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">සමන් විජේසිංහ</h6>
                                <small class="text-muted">ගම්පහ</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Testimonial 3 -->
                <div class="col-lg-4 col-md-6 scroll-reveal">
                    <div class="testimonial-card">
                        <div class="quote-icon">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <div class="rating mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="testimonial-text">
                            "හොඳ වැඩ දවසක් තෝරා ගැනීමෙන් අපේ ව්‍යාපාරය ඉතා සාර්ථකව ආරම්භ කළ හැකි වුණා. ඉතා ස්තූතියි!"
                        </p>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">රංජිත් ප්‍රනාන්දු</h6>
                                <small class="text-muted">කළුතර</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Services Process Section -->
    <section id="process" class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5 scroll-reveal">
                <h2 class="fw-bold section-heading">අපගේ සේවා ක්‍රියාවලිය</h2>
                <p class="text-muted">සරල හා කාර්යක්ෂම ක්‍රමවේදයකින් ඔබට සේවාව</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-3 col-sm-6 scroll-reveal">
                    <div class="process-step text-center">
                        <div class="process-number">
                            <span>01</span>
                        </div>
                        <div class="process-icon mb-3">
                            <i class="fas fa-phone-alt fa-2x text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-2">අමතන්න</h5>
                        <p class="text-muted">අප හා සම්බන්ධ වී ඔබගේ අවශ්‍යතා කියන්න</p>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6 scroll-reveal">
                    <div class="process-step text-center">
                        <div class="process-number">
                            <span>02</span>
                        </div>
                        <div class="process-icon mb-3">
                            <i class="fas fa-calendar-check fa-2x text-success"></i>
                        </div>
                        <h5 class="fw-bold mb-2">වේලාව වෙන්කරන්න</h5>
                        <p class="text-muted">ඔබට හිතකර වේලාවක් වෙන් කරගන්න</p>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6 scroll-reveal">
                    <div class="process-step text-center">
                        <div class="process-number">
                            <span>03</span>
                        </div>
                        <div class="process-icon mb-3">
                            <i class="fas fa-comments fa-2x text-info"></i>
                        </div>
                        <h5 class="fw-bold mb-2">උපදෙස් ලබා ගන්න</h5>
                        <p class="text-muted">විශේෂඥ උපදේශනය ලබා ගන්න</p>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6 scroll-reveal">
                    <div class="process-step text-center">
                        <div class="process-number">
                            <span>04</span>
                        </div>
                        <div class="process-icon mb-3">
                            <i class="fas fa-smile-beam fa-2x text-warning"></i>
                        </div>
                        <h5 class="fw-bold mb-2">ප්‍රතිඵල භුක්ති විඳින්න</h5>
                        <p class="text-muted">ඔබේ ජීවිතයේ ධනාත්මක වෙනස්කම්</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- FAQ Section -->
    <section id="faq" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5 scroll-reveal">
                <h2 class="fw-bold section-heading">නිතර අසන ප්‍රශ්න</h2>
                <p class="text-muted">ඔබගේ ප්‍රශ්නවලට පිළිතුරු</p>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="faqAccordion">
                        <!-- FAQ 1 -->
                        <div class="accordion-item faq-item scroll-reveal">
                            <h2 class="accordion-header" id="faq1">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapse1" aria-expanded="false" aria-controls="collapse1">
                                    <i class="fas fa-question-circle me-2 text-primary"></i>
                                    ජෝතිශ්‍ය සේවාවක් සඳහා මට කුමක් අවශ්‍යද?
                                </button>
                            </h2>
                            <div id="collapse1" class="accordion-collapse collapse" aria-labelledby="faq1" 
                                 data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    ඔබගේ උපන් දිනය, වේලාව සහ ස්ථානය අවශ්‍ය වේ. එමගින් නිවැරදි හොරෝස්කෝප් විශ්ලේෂණයක් කළ හැකිය.
                                </div>
                            </div>
                        </div>
                        
                        <!-- FAQ 2 -->
                        <div class="accordion-item faq-item scroll-reveal">
                            <h2 class="accordion-header" id="faq2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                                    <i class="fas fa-question-circle me-2 text-primary"></i>
                                    වාස්තු උපදෙස් ලබා ගැනීමට කෙසේ කටයුතු කරන්නද?
                                </button>
                            </h2>
                            <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="faq2" 
                                 data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    ඔබගේ නිවස හෝ ව්‍යාපාරික ස්ථානයේ සැලැස්ම සහ දිශාව පිළිබඳ තොරතුරු අප හා බෙදා ගන්න. අපි ඔබට සම්පූර්ණ වාස්තු විශ්ලේෂණයක් සහ යෝජනා ලබා දෙන්නෙමු.
                                </div>
                            </div>
                        </div>
                        
                        <!-- FAQ 3 -->
                        <div class="accordion-item faq-item scroll-reveal">
                            <h2 class="accordion-header" id="faq3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
                                    <i class="fas fa-question-circle me-2 text-primary"></i>
                                    සේවා ගාස්තු කෙසේද?
                                </button>
                            </h2>
                            <div id="collapse3" class="accordion-collapse collapse" aria-labelledby="faq3" 
                                 data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    සේවා වර්ගය අනුව ගාස්තු වෙනස් වේ. මුල් උපදෙස් වීමක් නොමිලේ. වැඩිදුර විස්තර සඳහා අප හා සම්බන්ධ වන්න.
                                </div>
                            </div>
                        </div>
                        
                        <!-- FAQ 4 -->
                        <div class="accordion-item faq-item scroll-reveal">
                            <h2 class="accordion-header" id="faq4">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapse4" aria-expanded="false" aria-controls="collapse4">
                                    <i class="fas fa-question-circle me-2 text-primary"></i>
                                    Online උපදෙස් ලබා ගත හැකිද?
                                </button>
                            </h2>
                            <div id="collapse4" class="accordion-collapse collapse" aria-labelledby="faq4" 
                                 data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    ඔව්, අපි WhatsApp, Zoom සහ දුරකථන හරහා online උපදෙස් ලබා දෙන්නෙමු. ඔබට සුදුසු ක්‍රමය තෝරා ගන්න.
                                </div>
                            </div>
                        </div>
                        
                        <!-- FAQ 5 -->
                        <div class="accordion-item faq-item scroll-reveal">
                            <h2 class="accordion-header" id="faq5">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapse5" aria-expanded="false" aria-controls="collapse5">
                                    <i class="fas fa-question-circle me-2 text-primary"></i>
                                    හොඳ වැඩ දවසක් තෝරා ගන්නේ කෙසේද?
                                </button>
                            </h2>
                            <div id="collapse5" class="accordion-collapse collapse" aria-labelledby="faq5" 
                                 data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    ඔබගේ හොරෝස්කෝප් සහ කරන්නට යන කාර්යය අනුව අපි හොඳම වැඩ දවස සහ වේලාව තීරණය කරන්නෙමු. මේ සඳහා ඔබගේ උපන් දිනය සහ කාර්යය පිළිබඳ විස්තර අවශ්‍ය වේ.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Call to Action Section -->
    <section id="cta" class="bg-primary text-white py-5 position-relative overflow-hidden">
        <!-- Animated background stars for CTA -->
        <div class="stars">
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
        </div>
        
        <div class="container text-center position-relative" style="z-index: 10;">
            <h3 class="fw-bold mb-3">ඔබේ ජීවිතය වෙනස් කරන්න අදම!</h3>
            <p class="lead mb-4">විශේෂඥ උපදේශනයක් සඳහා අදම අප හා සම්බන්ධ වන්න.</p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="contact.php" class="btn btn-light btn-lg shadow">
                    <i class="fas fa-phone me-2"></i>අප අමතන්න
                </a>
                <a href="https://wa.me/94712391317" target="_blank" rel="noopener noreferrer" 
                   class="btn btn-success whatsapp-btn btn-lg shadow">
                    <i class="fab fa-whatsapp me-2"></i>WhatsApp මගින් සම්බන්ධ වන්න
                </a>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section id="newsletter" class="py-5 position-relative overflow-hidden" 
             style="background: linear-gradient(135deg, #1a1a3e 0%, #2d1b4e 100%);">
        <!-- Animated background -->
        <div class="stars">
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
        </div>
        
        <div class="container position-relative" style="z-index: 10;">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0 scroll-reveal">
                    <div class="text-white">
                        <h3 class="fw-bold mb-3">
                            <i class="fas fa-envelope-open-text me-2"></i>
                            නවතම යාවත්කාලීන ලබා ගන්න
                        </h3>
                        <p class="lead mb-0">
                            ජ්‍යෝතිෂ, වාස්තු සහ අධ්‍යාත්මික දැනුම පිළිබඳ නවතම තොරතුරු ඔබගේ email එකට ලබා ගන්න
                        </p>
                    </div>
                </div>
                <div class="col-lg-6 scroll-reveal">
                    <form class="newsletter-form">
                        <div class="input-group input-group-lg">
                            <input type="email" class="form-control" placeholder="ඔබගේ Email ලිපිනය" 
                                   aria-label="Email address" required>
                            <button class="btn btn-warning" type="submit">
                                <i class="fas fa-paper-plane me-2"></i>Subscribe
                            </button>
                        </div>
                        <small class="text-white-50 d-block mt-2">
                            <i class="fas fa-lock me-1"></i>අපි ඔබගේ තොරතුරු ආරක්ෂිතව තබා ගන්නෙමු
                        </small>
                    </form>
                </div>
            </div>
        </div>
    </section>

    
    
    <!-- Custom JavaScript -->
    <script>
        // Back to Top Button functionality (button in footer.php)
        const backToTopButton = document.getElementById('backToTop');
        
        if (backToTopButton) {
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    backToTopButton.classList.remove('d-none');
                } else {
                    backToTopButton.classList.add('d-none');
                }
            });
            
            backToTopButton.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
        
        // Smooth scroll for anchor links
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
        
        // Initial reveal check
        revealOnScroll();
        
        // Counter Animation for Statistics
        function animateCounter() {
            const counters = document.querySelectorAll('.counter');
            const speed = 200; // Animation speed
            
            counters.forEach(counter => {
                const updateCount = () => {
                    const target = +counter.getAttribute('data-target');
                    const count = +counter.innerText;
                    const increment = target / speed;
                    
                    if (count < target) {
                        counter.innerText = Math.ceil(count + increment);
                        setTimeout(updateCount, 1);
                    } else {
                        counter.innerText = target + '+';
                    }
                };
                
                // Check if element is in viewport
                const rect = counter.getBoundingClientRect();
                const isInView = rect.top < window.innerHeight && rect.bottom >= 0;
                
                if (isInView && counter.innerText === '0') {
                    updateCount();
                }
            });
        }
        
        // Trigger counter on scroll
        let counterAnimated = false;
        window.addEventListener('scroll', function() {
            if (!counterAnimated) {
                const statsSection = document.getElementById('statistics');
                if (statsSection) {
                    const rect = statsSection.getBoundingClientRect();
                    if (rect.top < window.innerHeight * 0.75) {
                        animateCounter();
                        counterAnimated = true;
                    }
                }
            }
        });
        
        // Enhanced scroll reveal with stagger effect
        function enhancedReveal() {
            const reveals = document.querySelectorAll('.scroll-reveal');
            
            reveals.forEach((element, index) => {
                const elementTop = element.getBoundingClientRect().top;
                const windowHeight = window.innerHeight;
                const revealPoint = 100;
                
                if (elementTop < windowHeight - revealPoint) {
                    setTimeout(() => {
                        element.classList.add('active');
                    }, index * 100); // Stagger effect
                }
            });
        }
        
        window.addEventListener('scroll', enhancedReveal);
        window.addEventListener('load', enhancedReveal);
        enhancedReveal();
    </script>

    <?php 
    // Close database connection
    if ($conn && !$db_error) {
        $conn->close();
    }
    ?>
</body>
</html>
<?php include('footer.php'); ?>

 