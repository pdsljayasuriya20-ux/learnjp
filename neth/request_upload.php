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
    
    <!-- SEO Meta Tags -->
    <title>නෙත්සිත ජෝතිශ්‍ය සේවය - විශ්වාසවන්ත උපදේශනය | Astrology & Vasthu Services</title>
    <meta name="description" content="ශ්‍රී ලංකාවේ විශ්වාසවන්ත ජ්‍යෝතිෂ හා වාස්තු සේවා. පළපුරුදු උපදේශනය, හොරෝස්කෝප් විශ්ලේෂණය, වාස්තු නිර්මාණ සහ වැඩිදුර සේවා.">
    <meta name="keywords" content="ජෝතිශ්‍ය, වාස්තු, astrology, vasthu, horoscope, Sri Lanka, නක්ෂත්‍ර විද්‍යාව, හෝරාව">
    <meta name="author" content="නෙත්සිත ජෝතිශ්‍ය සේවය">
    
    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="නෙත්සිත ජෝතිශ්‍ය සේවය - විශ්වාසවන්ත උපදේශනය">
    <meta property="og:description" content="ශ්‍රී ලංකාවේ විශ්වාසවන්ත ජ්‍යෝතිෂ හා වාස්තු සේවා">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>assets/images/favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" 
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" 
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <!-- Custom Styles -->
    <style>
        /* Modern Design Styles with Astrology Theme */
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
            --dark-color: #212529;
            --gold-color: #ffd700;
            --purple-color: #9b59b6;
            --cosmic-blue: #1a1a3e;
        }
        
        body { 
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        /* ============================================
           HERO SECTION WITH COSMIC ANIMATIONS
        ============================================ */
        .hero-section {
            background: linear-gradient(135deg, #1a1a3e 0%, #2d1b4e 50%, #1a1a3e 100%);
            color: white;
            padding: 120px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
            position: relative;
            overflow: hidden;
            min-height: 600px;
        }
        
        /* Animated Stars Background */
        .stars {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        
        .star {
            position: absolute;
            background: white;
            border-radius: 50%;
            animation: twinkle 3s infinite;
        }
        
        .star:nth-child(1) { width: 2px; height: 2px; top: 10%; left: 20%; animation-delay: 0s; }
        .star:nth-child(2) { width: 3px; height: 3px; top: 20%; left: 40%; animation-delay: 0.5s; }
        .star:nth-child(3) { width: 2px; height: 2px; top: 30%; left: 60%; animation-delay: 1s; }
        .star:nth-child(4) { width: 3px; height: 3px; top: 40%; left: 80%; animation-delay: 1.5s; }
        .star:nth-child(5) { width: 2px; height: 2px; top: 50%; left: 10%; animation-delay: 2s; }
        .star:nth-child(6) { width: 3px; height: 3px; top: 60%; left: 30%; animation-delay: 2.5s; }
        .star:nth-child(7) { width: 2px; height: 2px; top: 70%; left: 50%; animation-delay: 3s; }
        .star:nth-child(8) { width: 3px; height: 3px; top: 80%; left: 70%; animation-delay: 0.3s; }
        .star:nth-child(9) { width: 2px; height: 2px; top: 15%; left: 85%; animation-delay: 1.2s; }
        .star:nth-child(10) { width: 3px; height: 3px; top: 25%; left: 15%; animation-delay: 1.8s; }
        .star:nth-child(11) { width: 2px; height: 2px; top: 35%; left: 45%; animation-delay: 2.3s; }
        .star:nth-child(12) { width: 3px; height: 3px; top: 45%; left: 65%; animation-delay: 0.8s; }
        .star:nth-child(13) { width: 2px; height: 2px; top: 55%; left: 25%; animation-delay: 1.6s; }
        .star:nth-child(14) { width: 3px; height: 3px; top: 65%; left: 55%; animation-delay: 2.1s; }
        .star:nth-child(15) { width: 2px; height: 2px; top: 75%; left: 75%; animation-delay: 0.6s; }
        
        @keyframes twinkle {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.2); }
        }
        
        /* Zodiac Symbols Animation */
        .zodiac-symbols {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
        }
        
        .zodiac-symbol {
            position: absolute;
            font-size: 40px;
            opacity: 0.1;
            animation: float 20s infinite ease-in-out;
            color: var(--gold-color);
        }
        
        .zodiac-symbol:nth-child(1) { top: 5%; left: 10%; animation-delay: 0s; }
        .zodiac-symbol:nth-child(2) { top: 15%; right: 15%; animation-delay: 2s; }
        .zodiac-symbol:nth-child(3) { bottom: 20%; left: 15%; animation-delay: 4s; }
        .zodiac-symbol:nth-child(4) { bottom: 10%; right: 10%; animation-delay: 6s; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            25% { transform: translateY(-20px) rotate(5deg); }
            50% { transform: translateY(0) rotate(0deg); }
            75% { transform: translateY(20px) rotate(-5deg); }
        }
        
        /* Orbiting Planets */
        .cosmic-orbit {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 600px;
            height: 600px;
            border: 1px solid rgba(255, 215, 0, 0.1);
            border-radius: 50%;
            animation: rotate 60s linear infinite;
        }
        
        .planet {
            position: absolute;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            box-shadow: 0 0 20px currentColor;
        }
        
        .planet-1 {
            top: 0;
            left: 50%;
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            animation: pulse 2s infinite;
        }
        
        .planet-2 {
            bottom: 0;
            left: 50%;
            background: linear-gradient(135deg, #4a90e2, #7fb3ff);
            animation: pulse 3s infinite;
        }
        
        .planet-3 {
            top: 50%;
            left: 0;
            background: linear-gradient(135deg, #e74c3c, #ff6b6b);
            animation: pulse 2.5s infinite;
        }
        
        @keyframes rotate {
            from { transform: translate(-50%, -50%) rotate(0deg); }
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.3); opacity: 1; }
        }
        
        /* Moon Phases */
        .moon {
            position: absolute;
            top: 10%;
            right: 10%;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f4f4f4, #d4d4d4);
            box-shadow: 0 0 40px rgba(255, 255, 255, 0.5);
            animation: moonGlow 4s infinite ease-in-out;
        }
        
        @keyframes moonGlow {
            0%, 100% { box-shadow: 0 0 40px rgba(255, 255, 255, 0.5); }
            50% { box-shadow: 0 0 60px rgba(255, 255, 255, 0.8); }
        }
        
        .hero-section .container {
            position: relative;
            z-index: 10;
        }
        
        .hero-section h1 {
            animation: fadeInDown 1s ease-out;
            text-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
        }
        
        .hero-section p {
            animation: fadeInUp 1s ease-out 0.3s both;
        }
        
        .hero-section .btn {
            animation: fadeInUp 1s ease-out 0.6s both;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* ============================================
           SERVICE CARDS WITH ANIMATIONS
        ============================================ */
        .service-card {
            transition: all 0.4s ease;
            min-height: 280px;
            border: none;
            border-radius: 15px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
            background: white;
        }
        
        .service-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                transparent 30%,
                rgba(255, 215, 0, 0.1) 50%,
                transparent 70%
            );
            transform: rotate(0deg);
            transition: all 0.6s ease;
        }
        
        .service-card:hover::before {
            transform: rotate(180deg);
        }
        
        .service-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }
        
        .service-card .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }
        
        .service-card .card-text {
            flex: 1;
            margin-bottom: 1rem;
        }
        
        .service-card i {
            transition: all 0.4s ease;
            display: inline-block;
        }
        
        .service-card:hover i {
            transform: scale(1.2) rotateY(360deg);
        }
        
        /* Animated service card backgrounds */
        .service-card.jothishya {
            background: linear-gradient(135deg, #ffffff 0%, #f8f4ff 100%);
        }
        
        .service-card.vasthu {
            background: linear-gradient(135deg, #ffffff 0%, #f0fff4 100%);
        }
        
        /* ============================================
           FEATURE ICONS WITH ANIMATIONS
        ============================================ */
        .feature-icon {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            animation: iconBounce 2s infinite ease-in-out;
        }
        
        @keyframes iconBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .feature-icon::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 2px solid;
            border-color: inherit;
            animation: ripple 2s infinite;
        }
        
        @keyframes ripple {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            100% {
                transform: scale(1.5);
                opacity: 0;
            }
        }
        
        .feature-icon.success {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
            animation-delay: 0.2s;
        }
        
        .feature-icon.warning {
            background: linear-gradient(135deg, #fbc2eb 0%, #a6c1ee 100%);
            animation-delay: 0.4s;
        }
        
        .feature-icon.info {
            background: linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%);
            animation-delay: 0.6s;
        }
        
        .feature-icon i {
            animation: iconRotate 3s infinite ease-in-out;
        }
        
        @keyframes iconRotate {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-10deg); }
            75% { transform: rotate(10deg); }
        }
        
        /* ============================================
           VASTHU PATTERN ANIMATIONS
        ============================================ */
        .vasthu-pattern {
            position: absolute;
            width: 100px;
            height: 100px;
            opacity: 0.05;
            animation: patternMove 30s infinite linear;
        }
        
        .vasthu-pattern:nth-child(1) {
            top: 10%;
            left: 5%;
            animation-delay: 0s;
        }
        
        .vasthu-pattern:nth-child(2) {
            top: 50%;
            right: 10%;
            animation-delay: 5s;
        }
        
        .vasthu-pattern:nth-child(3) {
            bottom: 15%;
            left: 15%;
            animation-delay: 10s;
        }
        
        @keyframes patternMove {
            0% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(20px, 20px) rotate(90deg); }
            50% { transform: translate(0, 40px) rotate(180deg); }
            75% { transform: translate(-20px, 20px) rotate(270deg); }
            100% { transform: translate(0, 0) rotate(360deg); }
        }
        
        /* ============================================
           WHATSAPP BUTTON ANIMATION
        ============================================ */
        .whatsapp-btn {
            background-color: #25D366;
            border-color: #25D366;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .whatsapp-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .whatsapp-btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .whatsapp-btn:hover {
            background-color: #128C7E;
            border-color: #128C7E;
            transform: scale(1.05);
        }
        
        .whatsapp-btn i {
            animation: whatsappBounce 2s infinite;
        }
        
        @keyframes whatsappBounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* ============================================
           SECTION HEADINGS
        ============================================ */
        .section-heading {
            position: relative;
            display: inline-block;
            padding-bottom: 15px;
            animation: headingGlow 3s infinite ease-in-out;
        }
        
        @keyframes headingGlow {
            0%, 100% { text-shadow: 0 0 5px rgba(13, 110, 253, 0.3); }
            50% { text-shadow: 0 0 15px rgba(13, 110, 253, 0.6); }
        }
        
        .section-heading::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), var(--gold-color));
            animation: underlineExpand 2s infinite ease-in-out;
        }
        
        @keyframes underlineExpand {
            0%, 100% { width: 60px; }
            50% { width: 80px; }
        }
        
        /* ============================================
           SCROLL ANIMATIONS
        ============================================ */
        .scroll-reveal {
            opacity: 0;
            transform: translateY(50px);
            transition: all 0.8s ease;
        }
        
        .scroll-reveal.active {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* ============================================
           RESPONSIVE DESIGN
        ============================================ */
        @media (max-width: 768px) {
            .hero-section {
                padding: 80px 0;
                min-height: 500px;
            }
            
            .hero-section h1 {
                font-size: 2rem;
            }
            
            .service-card {
                min-height: 200px;
                margin-bottom: 1rem;
            }
            
            .cosmic-orbit {
                width: 300px;
                height: 300px;
            }
            
            .moon {
                width: 50px;
                height: 50px;
            }
            
            .zodiac-symbol {
                font-size: 30px;
            }
        }
        
        /* ============================================
           LOADING SPINNER
        ============================================ */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* ============================================
           ALERT BOX ENHANCEMENT
        ============================================ */
        .alert-custom {
            border-left: 4px solid;
            border-radius: 8px;
            animation: slideInFromLeft 0.5s ease;
        }
        
        @keyframes slideInFromLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* ============================================
           TESTIMONIALS SECTION
        ============================================ */
        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        
        .float-element {
            position: absolute;
            font-size: 40px;
            opacity: 0.15;
            animation: gentleFloat 6s ease-in-out infinite;
        }
        
        .float-element:nth-child(1) { animation-delay: 0s; }
        .float-element:nth-child(2) { animation-delay: 1.5s; }
        .float-element:nth-child(3) { animation-delay: 3s; }
        .float-element:nth-child(4) { animation-delay: 4.5s; }
        
        @keyframes gentleFloat {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            25% { transform: translateY(-15px) rotate(5deg); }
            50% { transform: translateY(0) rotate(0deg); }
            75% { transform: translateY(15px) rotate(-5deg); }
        }
        
        .testimonial-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        
        .testimonial-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2, #667eea);
            background-size: 200% 100%;
            animation: shimmer 3s linear infinite;
        }
        
        @keyframes shimmer {
            0% { background-position: 0% 0%; }
            100% { background-position: 200% 0%; }
        }
        
        .testimonial-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
        }
        
        .quote-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            animation: rotateIcon 10s linear infinite;
        }
        
        @keyframes rotateIcon {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-5deg); }
            75% { transform: rotate(5deg); }
        }
        
        .quote-icon i {
            color: white;
            font-size: 1.2rem;
        }
        
        .rating i {
            font-size: 1rem;
            margin: 0 2px;
            animation: starShine 2s ease-in-out infinite;
        }
        
        .rating i:nth-child(1) { animation-delay: 0s; }
        .rating i:nth-child(2) { animation-delay: 0.2s; }
        .rating i:nth-child(3) { animation-delay: 0.4s; }
        .rating i:nth-child(4) { animation-delay: 0.6s; }
        .rating i:nth-child(5) { animation-delay: 0.8s; }
        
        @keyframes starShine {
            0%, 100% { transform: scale(1); filter: brightness(1); }
            50% { transform: scale(1.2); filter: brightness(1.5); }
        }
        
        .testimonial-text {
            font-style: italic;
            color: #555;
            line-height: 1.8;
            margin-bottom: 1.5rem;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #f0f0f0;
        }
        
        .author-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #84fab0, #8fd3f4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: avatarPulse 3s ease-in-out infinite;
        }
        
        @keyframes avatarPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(132, 250, 176, 0.7); }
            50% { box-shadow: 0 0 0 10px rgba(132, 250, 176, 0); }
        }
        
        .author-avatar i {
            color: white;
            font-size: 1.5rem;
        }
        
        /* ============================================
           STATISTICS SECTION
        ============================================ */
        .stat-box {
            padding: 2rem;
            transition: all 0.3s ease;
        }
        
        .stat-box:hover {
            transform: scale(1.1);
        }
        
        .stat-icon {
            animation: iconFloat 3s ease-in-out infinite;
        }
        
        .stat-box:nth-child(1) .stat-icon { animation-delay: 0s; }
        .stat-box:nth-child(2) .stat-icon { animation-delay: 0.5s; }
        .stat-box:nth-child(3) .stat-icon { animation-delay: 1s; }
        .stat-box:nth-child(4) .stat-icon { animation-delay: 1.5s; }
        
        @keyframes iconFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .counter {
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
        }
        
        /* ============================================
           PROCESS SECTION
        ============================================ */
        .process-step {
            position: relative;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.4s ease;
            height: 100%;
        }
        
        .process-step::after {
            content: '→';
            position: absolute;
            right: -20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2rem;
            color: #ddd;
            font-weight: bold;
        }
        
        .process-step:last-child::after {
            display: none;
        }
        
        .process-step:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
        }
        
        .process-number {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            animation: numberPulse 2s ease-in-out infinite;
        }
        
        @keyframes numberPulse {
            0%, 100% { transform: translateX(-50%) scale(1); }
            50% { transform: translateX(-50%) scale(1.1); }
        }
        
        .process-number span {
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .process-icon {
            margin-top: 1.5rem;
            animation: processIconBounce 3s ease-in-out infinite;
        }
        
        .process-step:nth-child(1) .process-icon { animation-delay: 0s; }
        .process-step:nth-child(2) .process-icon { animation-delay: 0.3s; }
        .process-step:nth-child(3) .process-icon { animation-delay: 0.6s; }
        .process-step:nth-child(4) .process-icon { animation-delay: 0.9s; }
        
        @keyframes processIconBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        
        /* Mobile adjustments for process section */
        @media (max-width: 768px) {
            .process-step::after {
                content: '↓';
                right: 50%;
                top: auto;
                bottom: -30px;
                transform: translateX(50%);
            }
        }
        
        /* ============================================
           FAQ SECTION
        ============================================ */
        .faq-item {
            border: none;
            margin-bottom: 1rem;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .faq-item:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transform: translateX(5px);
        }
        
        .faq-item .accordion-button {
            background: white;
            color: #333;
            font-weight: 600;
            padding: 1.25rem 1.5rem;
            border: none;
            transition: all 0.3s ease;
        }
        
        .faq-item .accordion-button:not(.collapsed) {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: none;
        }
        
        .faq-item .accordion-button:not(.collapsed) i {
            color: white;
            animation: iconSpin 0.5s ease;
        }
        
        @keyframes iconSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .faq-item .accordion-button:focus {
            box-shadow: none;
            border: none;
        }
        
        .faq-item .accordion-button::after {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23333'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
            transition: transform 0.3s ease;
        }
        
        .faq-item .accordion-button:not(.collapsed)::after {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23fff'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
            transform: rotate(-180deg);
        }
        
        .faq-item .accordion-body {
            padding: 1.5rem;
            background: #f8f9fa;
            color: #555;
            line-height: 1.8;
            border-top: 2px solid #e9ecef;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* ============================================
           NEWSLETTER SECTION
        ============================================ */
        .newsletter-form .form-control {
            border: none;
            border-radius: 50px 0 0 50px;
            padding: 1rem 1.5rem;
            font-size: 1rem;
        }
        
        .newsletter-form .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.25);
            border-color: #ffc107;
        }
        
        .newsletter-form .btn {
            border-radius: 0 50px 50px 0;
            padding: 1rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .newsletter-form .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(255, 193, 7, 0.4);
        }
        
        /* ============================================
           BACK TO TOP BUTTON
        ============================================ */
        #backToTop {
            animation: fadeInUp 0.3s ease;
        }
        
        #backToTop:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.3);
        }
    </style>
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

    <!-- Why Choose Us Section -->
    <section id="why-choose-us" class="bg-white py-5">
        <div class="container">
            <div class="text-center mb-5 scroll-reveal">
                <h2 class="fw-bold section-heading">අප තෝරාගත යුත්තේ ඇයි?</h2>
            </div>
            <div class="row g-4 text-center">
                <div class="col-md-4 scroll-reveal">
                    <div class="feature-icon success">
                        <i class="fas fa-handshake fa-2x text-white" aria-hidden="true"></i>
                    </div>
                    <h5 class="fw-bold mb-3">විශ්වාසනීය සේවාව</h5>
                    <p class="text-muted px-3">
                        පාරම්පරික දැනුම සහ නවීන විද්‍යාව මුසු වූ විශ්වාසවන්ත උපදේශනය.
                    </p>
                </div>
                <div class="col-md-4 scroll-reveal">
                    <div class="feature-icon warning">
                        <i class="fas fa-certificate fa-2x text-white" aria-hidden="true"></i>
                    </div>
                    <h5 class="fw-bold mb-3">පළපුරුද්ද</h5>
                    <p class="text-muted px-3">
                        වසර ගණනාවක සාර්ථක සේවා පළපුරුද්ද සහ පාරිභෝගික තෘප්තිය.
                    </p>
                </div>
                <div class="col-md-4 scroll-reveal">
                    <div class="feature-icon info">
                        <i class="fas fa-comments fa-2x text-white" aria-hidden="true"></i>
                    </div>
                    <h5 class="fw-bold mb-3">පෞද්ගලික අවධානය</h5>
                    <p class="text-muted px-3">
                        ඔබගේ සෑම ගැටලුවකටම පෞද්ගලිකව සවන් දී විසඳුම් ලබා දීම.
                    </p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Statistics Section -->
    <section id="statistics" class="py-5 position-relative overflow-hidden" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="stars">
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
        </div>
        
        <div class="container position-relative" style="z-index: 10;">
            <div class="row g-4 text-center text-white">
                <div class="col-md-3 col-6 scroll-reveal">
                    <div class="stat-box">
                        <div class="stat-icon mb-3">
                            <i class="fas fa-users fa-3x"></i>
                        </div>
                        <h2 class="display-4 fw-bold counter" data-target="5000">0</h2>
                        <p class="mb-0">සතුටුදායක පාරිභෝගිකයන්</p>
                    </div>
                </div>
                <div class="col-md-3 col-6 scroll-reveal">
                    <div class="stat-box">
                        <div class="stat-icon mb-3">
                            <i class="fas fa-star fa-3x"></i>
                        </div>
                        <h2 class="display-4 fw-bold counter" data-target="15">0</h2>
                        <p class="mb-0">වසර පළපුරුද්ද</p>
                    </div>
                </div>
                <div class="col-md-3 col-6 scroll-reveal">
                    <div class="stat-box">
                        <div class="stat-icon mb-3">
                            <i class="fas fa-clipboard-check fa-3x"></i>
                        </div>
                        <h2 class="display-4 fw-bold counter" data-target="10000">0</h2>
                        <p class="mb-0">සම්පූර්ණ කළ සේවා</p>
                    </div>
                </div>
                <div class="col-md-3 col-6 scroll-reveal">
                    <div class="stat-box">
                        <div class="stat-icon mb-3">
                            <i class="fas fa-award fa-3x"></i>
                        </div>
                        <h2 class="display-4 fw-bold">100%</h2>
                        <p class="mb-0">තෘප්තිමත් සේවාව</p>
                    </div>
                </div>
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

    <!-- Back to Top Button -->
    <button id="backToTop" class="btn btn-primary position-fixed bottom-0 end-0 m-4 rounded-circle shadow d-none" 
            style="width: 50px; height: 50px; z-index: 1000;" aria-label="Back to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Bootstrap JS Bundle -->
    
    
    <!-- Custom JavaScript -->
    <script>
        // Back to Top Button
        const backToTopButton = document.getElementById('backToTop');
        
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