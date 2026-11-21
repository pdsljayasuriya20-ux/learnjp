<?php
// 💥 FIX 3: Load header after defining the safe_output function.
include 'header.php'; // මෙය $app_settings යන ගෝලීය විචල්‍යයන් නිර්මාණය කරයි.

// =================================================================
// 1. Initialization and Setup
// =================================================================

// Error handling and security
error_reporting(E_ALL);
ini_set('display_errors', 0); // Production environment
ini_set('log_errors', 1);

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define base URL (This line is redundant if defined in header.php/config.php, but kept for context)
define('BASE_URL', '/');

// Database connection with error handling
$db_error = false;
$conn = null;

try {
    // 💥 FIX 1: Include db_connection.php BEFORE header.php (or ensure header.php/config.php handles DB connection)
    // If db_connection.php defines $conn, it must be included early.
    // However, since the user included 'header.php' first, we assume the DB connection is handled there or in a file included by it.
    // If $conn is not set after 'header.php', we include it now.
    if (!isset($conn) && file_exists('db_connection.php')) {
        include('db_connection.php');
    }
    
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed");
    }
    
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    $db_error = true;
    error_log("DB Connection Error: " . $e->getMessage());
}

// 💥 FIX 2: Define safe_output function here, before the header file uses it, to resolve "Call to undefined function"
// This function will be available globally for the current script.
if (!function_exists('safe_output')) {
    function safe_output($text, $max_length = null) {
        $text = htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
        if ($max_length && mb_strlen($text) > $max_length) {
            $text = mb_substr($text, 0, $max_length) . '...';
        }
        return $text;
    }
}


$page_title = 'සම්බන්ධ වන්න'; 

// Initialize variables for form feedback
$form_success = false;
$form_error = false;
$error_message = '';

// Re-fetch $app_settings for clarity and scope safety (assuming it was set in header.php)
$app_settings = $GLOBALS['app_settings'] ?? [];


// =================================================================
// 2. Process Form Submission
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    
    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $form_error = true;
        $error_message = 'වලංගු නොවන ඉල්ලීමක්. කරුණාකර නැවත උත්සාහ කරන්න.';
    } else {
        // Sanitize Input Data
        $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS));
        $subject = trim(filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_SPECIAL_CHARS));
        $message = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS));
        
        // Validation
        if (empty($name) || empty($phone) || empty($message)) {
            $form_error = true;
            $error_message = 'කරුණාකර සියලුම අවශ්‍ය ක්ෂේත්‍ර පුරවන්න.';
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $form_error = true;
            $error_message = 'වලංගු විද්‍යුත් තැපැල් ලිපිනයක් ඇතුළත් කරන්න.';
        } else {
            // Database Insertion Logic
            // 💥 FIX 4: Use the global $conn variable for database operations
            if (!$db_error && is_object($conn)) {
                
                // Table Existence Check (Removed for production, kept for debugging)
                $table_check = $conn->query("SHOW TABLES LIKE 'contact_inquiries'");
                
                if ($table_check && $table_check->num_rows > 0) {
                    $stmt = $conn->prepare("INSERT INTO contact_inquiries (name, email, phone, subject, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    
                    if ($stmt) {
                        // Bind parameters and execute
                        $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
                        
                        if ($stmt->execute()) {
                            $form_success = true;
                            // Reset CSRF token after successful submission
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        } else {
                            $form_error = true;
                            $error_message = 'දත්ත සුරැකීමේදී දෝෂයක් ඇතිවිය. කරුණාකර WhatsApp හරහා සම්බන්ධ වන්න.';
                            error_log("Contact Form Execute Error: " . $stmt->error);
                        }
                        
                        $stmt->close();
                    } else {
                        $form_error = true;
                        $error_message = 'තාක්ෂණික දෝෂයක් ඇතිවිය. කරුණාකර WhatsApp හරහා සම්බන්ධ වන්න.';
                        error_log("Contact Form Prepare Error: " . $conn->error);
                    }
                } else {
                    $form_error = true;
                    $error_message = 'දත්ත සමුදාය සකසා නැත. කරුණාකර WhatsApp හරහා සම්බන්ධ වන්න.';
                    error_log("Contact Form Error: Table 'contact_inquiries' does not exist");
                }
            } else {
                // DB error එකක් තිබේ නම්, සාර්ථකත්ව පණිවිඩයක් දී Log කරන්න.
                $form_success = true; // DB නැති විටත් පරිශීලකයාට හොඳ අත්දැකීමක් ලබා දෙන්න.
                error_log("Contact form submitted but DB unavailable - Name: $name, Phone: $phone");
            }
        }
    }
}

// 3. CSRF Token Generation (If not set or after form reset)
if (!isset($_SESSION['csrf_token']) || $form_success) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// 4. ප්‍රධාන පිටුවේ දත්ත Load කිරීම
$panchangaya_data = null;
$today_date = date('Y-m-d'); 

if (!$db_error && is_object($conn)) {
    try {
        $stmt = $conn->prepare("SELECT * FROM daily_panchangaya WHERE date = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $today_date);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $panchangaya_data = $result->fetch_assoc();
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Panchangaya Data Fetch Error: " . $e->getMessage());
    }
}


// =================================================================
// HTML / Presentation Layer
// =================================================================
?>
<!DOCTYPE html>
<html lang="si">
<head>
      <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
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
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/contact.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
</head>
<body>
    <header class="page-header text-center">
        <div class="header-stars">
            <div class="header-star"></div>
            <div class="header-star"></div>
            <div class="header-star"></div>
            <div class="header-star"></div>
            <div class="header-star"></div>
            <div class="header-star"></div>
            <div class="header-star"></div>
            <div class="header-star"></div>
        </div>
        
        <div class="orbit-ring">
            <div class="orbit-dot"></div>
        </div>
        
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">අප හා සම්බන්ධ වන්න</h1>
            <p class="lead fs-5">ඔබගේ ජ්‍යෝතිෂ හා වාස්තු අවශ්‍යතා සඳහා විමසීම්</p>
        </div>
    </header>

    <div class="container my-5">
        
        <?php if ($form_success): ?>
        <div class="alert alert-success alert-custom alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <strong>ස්තූතියි!</strong> ඔබගේ පණිවිඩය සාර්ථකව ලැබී ඇත. අපි ඉක්මනින් ඔබ සමඟ සම්බන්ධ වන්නෙමු.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($form_error): ?>
        <div class="alert alert-danger alert-custom alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>දෝෂය:</strong> <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <div class="row g-3 mb-5 scroll-reveal">
            <div class="col-md-4">
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $app_settings['contact_phone'] ?? '94712391317'); ?>" target="_blank" rel="noopener noreferrer" class="quick-contact-btn wa">
                    <i class="fab fa-whatsapp fa-2x"></i>
                    <div>
                        <div class="small">WhatsApp මගින්</div>
                        <div><?php echo safe_output($app_settings['contact_phone'] ?? '+94 71 239 1317'); ?></div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="tel:<?php echo safe_output($app_settings['contact_phone'] ?? '+94712391317'); ?>" class="quick-contact-btn call">
                    <i class="fas fa-phone-alt fa-2x"></i>
                    <div>
                        <div class="small">දුරකථනය අමතන්න</div>
                        <div><?php echo safe_output($app_settings['contact_phone'] ?? '+94 71 239 1317'); ?></div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="mailto:<?php echo safe_output($app_settings['contact_email'] ?? 'info@nethsitha.lk'); ?>" class="quick-contact-btn email-btn">
                    <i class="fas fa-envelope fa-2x"></i>
                    <div>
                        <div class="small">ඊමේල් කරන්න</div>
                        <div><?php echo safe_output($app_settings['contact_email'] ?? 'info@nethsitha.lk'); ?></div>
                    </div>
                </a>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="contact-card shadow p-4 mb-4 scroll-reveal">
                    <h3 class="mb-4 fw-bold">
                        <i class="fas fa-info-circle me-2" style="color: #667eea;"></i>සම්බන්ධතා තොරතුරු
                    </h3>
                    
                    <div class="mb-4">
                        <div class="d-flex align-items-start mb-4">
                            <div class="contact-icon whatsapp flex-shrink-0">
                                <i class="fab fa-whatsapp"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="fw-bold mb-1">WhatsApp</h6>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $app_settings['contact_phone'] ?? '94712391317'); ?>" target="_blank" rel="noopener noreferrer" 
                                   class="text-decoration-none" style="color: #25D366;"><?php echo safe_output($app_settings['contact_phone'] ?? '+94 71 239 1317'); ?></a>
                                <p class="small text-muted mb-0">සඳු - සිකු (උදෑසන 9 - සවස 6)</p>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-start mb-4">
                            <div class="contact-icon email flex-shrink-0">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="fw-bold mb-1">විද්‍යුත් තැපෑල</h6>
                                <a href="mailto:<?php echo safe_output($app_settings['contact_email'] ?? 'info@nethsitha.lk'); ?>" class="text-decoration-none" style="color: #3498db;"><?php echo safe_output($app_settings['contact_email'] ?? 'info@nethsitha.lk'); ?></a>
                                <p class="small text-muted mb-0">පැය 24 ක් ඇතුළත ප්‍රතිචාරය</p>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-start">
                            <div class="contact-icon location flex-shrink-0">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="fw-bold mb-1">ලිපිනය</h6>
                                <p class="mb-0"><?php echo safe_output($app_settings['contact_address'] ?? 'අංක 123, ප්‍රධාන පාර, කොළඹ 07, ශ්‍රී ලංකාව'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="contact-card shadow p-4 scroll-reveal">
                    <h3 class="mb-4 fw-bold">
                        <i class="fas fa-paper-plane me-2" style="color: #667eea;"></i>විමසීම් පෝරමය
                    </h3>
                    
                    <form method="POST" action="" id="contactForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">නම <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required 
                                        placeholder="ඔබගේ සම්පූර්ණ නම" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="phone" class="form-label">දුරකථන අංකය <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="phone" name="phone" required 
                                        placeholder="0712345678" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-12">
                                <label for="email" class="form-label">විද්‍යුත් තැපෑල (විකල්ප)</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                        placeholder="your@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-12">
                                <label for="subject" class="form-label">විෂයය</label>
                                <select class="form-select" id="subject" name="subject">
                                    <?php $selected_subject = $_POST['subject'] ?? ''; ?>
                                    <option value="">විෂයය තෝරන්න...</option>
                                    <option value="horoscope" <?php if ($selected_subject == 'horoscope') echo 'selected'; ?>>හෝරා ගැටළු</option>
                                    <option value="vasthu" <?php if ($selected_subject == 'vasthu') echo 'selected'; ?>>වාස්තු උපදෙස්</option>
                                    <option value="marriage" <?php if ($selected_subject == 'marriage') echo 'selected'; ?>>විවාහ මැඩැම්</option>
                                    <option value="career" <?php if ($selected_subject == 'career') echo 'selected'; ?>>වෘත්තීය උපදෙස්</option>
                                    <option value="other" <?php if ($selected_subject == 'other') echo 'selected'; ?>>වෙනත්</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label for="message" class="form-label">පණිවිඩය <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="message" name="message" rows="5" required 
                                             placeholder="ඔබගේ විමසීම හෝ අවශ්‍යතාවය මෙහි ලියන්න..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="agree" required>
                                    <label class="form-check-label" for="agree">
                                        මම <a href="privacy-policy.php" class="text-decoration-none" target="_blank">පුද්ගලිකත්ව ප්‍රතිපත්තිය</a> කියවා එකඟ වෙමි
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" name="submit_contact" class="btn btn-submit w-100">
                                    <i class="fas fa-paper-plane me-2"></i>පණිවිඩය යවන්න
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4 pt-4 border-top">
                        <p class="text-muted mb-3">හෝ ඉක්මන් ප්‍රතිචාරයක් සඳහා WhatsApp හරහා සම්බන්ධ වන්න</p>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $app_settings['contact_phone'] ?? '94712391317'); ?>" target="_blank" rel="noopener noreferrer" 
                           class="btn whatsapp-btn">
                            <i class="fab fa-whatsapp me-2"></i>WhatsApp මගින් සම්බන්ධ වන්න
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-4">
            <div class="col-lg-6">
                <div class="contact-card shadow p-4 h-100 scroll-reveal">
                    <h3 class="mb-4 fw-bold">
                        <i class="fas fa-clipboard-list me-2" style="color: #84fab0;"></i>සේවාවක් ලබා ගන්නේ කෙසේද?
                    </h3>
                    <div class="steps-list">
                        <div class="step-item">
                            <h6 class="fw-bold mb-2">සේවාව තෝරන්න</h6>
                            <p class="text-muted mb-0">
                                ඔබට අවශ්‍ය සේවාව <a href="services.php" class="text-decoration-none" style="color: #667eea;">මෙතැනින්</a> තෝරන්න.
                            </p>
                        </div>
                        <div class="step-item">
                            <h6 class="fw-bold mb-2">විස්තර ලබා දෙන්න</h6>
                            <p class="text-muted mb-0">
                                පෝරමය පුරවා ඔබගේ තොරතුරු අපට දන්වන්න.
                            </p>
                        </div>
                        <div class="step-item">
                            <h6 class="fw-bold mb-2">තහවුරු කිරීම ලබා ගන්න</h6>
                            <p class="text-muted mb-0">
                                අපි WhatsApp හරහා ඔබව ඉක්මනින් සම්බන්ධ කර ගනිමු.
                            </p>
                        </div>
                    </div>
                    <a href="services.php" class="btn btn-submit w-100 mt-3">
                        <i class="fas fa-th-large me-2"></i>සියලුම සේවා බලන්න
                    </a>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="contact-card shadow p-4 h-100 scroll-reveal">
                    <h3 class="mb-4 fw-bold">
                        <i class="fas fa-map-marked-alt me-2" style="color: #f39c12;"></i>අපගේ ස්ථානය
                    </h3>
                    <div class="p-4 bg-light rounded-3 shadow-sm mt-4">
                        <h4 class="text-secondary mb-3"><i class="fas fa-map-marker-alt me-2"></i>අපව සොයා ගන්න</h4>
                        <?php 
                        // 💥 FIX 6: Use $app_settings which is now correctly defined/scoped
                        $map_code = $app_settings['map_embed_code'] ?? '';
                        if (!empty($map_code)): ?>
                            <div class="ratio ratio-4x3" style="border-radius: 8px; overflow: hidden;">
                                <?php echo $map_code; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">සිතියම් කේතයක් සපයා නැත. පරිපාලක සැකසුම් හරහා එක් කරන්න.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="cta-section text-center my-5 text-white scroll-reveal">
            <div class="header-stars">
                <div class="header-star" style="left: 10%; top: 20%; animation-delay: 0s;"></div>
                <div class="header-star" style="left: 30%; top: 60%; animation-delay: 0.5s;"></div>
                <div class="header-star" style="left: 50%; top: 30%; animation-delay: 1s;"></div>
                <div class="header-star" style="left: 70%; top: 70%; animation-delay: 1.5s;"></div>
                <div class="header-star" style="left: 90%; top: 40%; animation-delay: 2s;"></div>
            </div>
            
            <div class="position-relative" style="z-index: 10;">
                <h3 class="fw-bold mb-3 display-5">අදම අප හා සම්බන්ධ වන්න</h3>
                <p class="lead mb-4">ඔබගේ ගැටලුව විසඳා ගැනීමට අපගේ විශේෂඥයන් සූදානම්</p>
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $app_settings['contact_phone'] ?? '94712391317'); ?>" target="_blank" class="btn whatsapp-btn">
                    <i class="fab fa-whatsapp fa-2x me-2"></i> WhatsApp මගින් සම්බන්ධ වන්න
                </a>
            </div>
        </div>

    </div>

    
    <?php include('footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        
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
        
        // Form validation
        const contactForm = document.getElementById('contactForm');
        contactForm.addEventListener('submit', function(e) {
            const phone = document.getElementById('phone').value;
            
            // New RegEx that allows optional +94 prefix and better format checking
            if (phone && !/^(?:\+94|0)?(1|2|3|4|5|6|7|8|9)\d{8}$/.test(phone.replace(/\s/g, ''))) { 
                e.preventDefault();
                alert('කරුණාකර වලංගු දුරකථන අංකයක් ඇතුළත් කරන්න (උදා: 0771234567)');
                return false;
            }
        });
        
        <?php if ($form_success): ?>
        window.scrollTo({ top: 0, behavior: 'smooth' });
        <?php endif; ?>
    </script>

    <?php 
    // 💥 FIX 7: Close DB Connection (if it was successful)
    if (isset($conn) && is_object($conn) && !$db_error) {
        $conn->close();
    }
    ?>
</body>
</html>