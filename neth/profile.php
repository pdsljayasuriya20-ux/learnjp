<?php
session_start();
require_once 'db_connection.php';

// Check if client is logged in
if (!isset($_SESSION['client_logged_in']) || $_SESSION['client_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$client_id = $_SESSION['client_id'];
$client_name = $_SESSION['client_name'];

$message = '';
$messageType = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        $message = "අවලංගු ඉල්ලීමක්.";
        $messageType = 'error';
    } else {
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $birth_date = trim($_POST['birth_date']);
        $birth_time = trim($_POST['birth_time']);
        $birth_place = trim($_POST['birth_place']);
        $gender = $_POST['gender'];
        $marital_status = $_POST['marital_status'];
        
        if(strlen($name) < 3){
            $message = "නම අකුරු 3කට වඩා විශාල විය යුතුය.";
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("UPDATE client SET name=?, phone=?, address=?, birth_date=?, birth_time=?, birth_place=?, gender=?, marital_status=? WHERE id=?");
            $stmt->bind_param("ssssssssi", $name, $phone, $address, $birth_date, $birth_time, $birth_place, $gender, $marital_status, $client_id);
            
            if($stmt->execute()){
                $_SESSION['client_name'] = $name;
                $message = "Profile එක සාර්ථකව යාවත්කාලීන කරන ලදී!";
                $messageType = 'success';
            } else {
                $message = "Profile යාවත්කාලීන කිරීම අසාර්ථක විය.";
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
}

// Handle horoscope details update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_horoscope'])) {
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        $message = "අවලංගු ඉල්ලීමක්.";
        $messageType = 'error';
    } else {
        $sun_sign = trim($_POST['sun_sign']);
        $moon_sign = trim($_POST['moon_sign']);
        $ascendant = trim($_POST['ascendant']);
        $nakshatra = trim($_POST['nakshatra']);
        $rashi = trim($_POST['rashi']);
        $lagna = trim($_POST['lagna']);
        $birth_number = intval($_POST['birth_number']);
        $destiny_number = intval($_POST['destiny_number']);
        $lucky_number = intval($_POST['lucky_number']);
        $lucky_color = trim($_POST['lucky_color']);
        $lucky_day = trim($_POST['lucky_day']);
        $lucky_stone = trim($_POST['lucky_stone']);
        
        // Check if horoscope details exist
        $check = $conn->prepare("SELECT id FROM horoscope_details WHERE client_id=?");
        $check->bind_param("i", $client_id);
        $check->execute();
        $result = $check->get_result();
        
        if($result->num_rows > 0){
            // Update existing
            $stmt = $conn->prepare("UPDATE horoscope_details SET sun_sign=?, moon_sign=?, ascendant=?, nakshatra=?, rashi=?, lagna=?, birth_number=?, destiny_number=?, lucky_number=?, lucky_color=?, lucky_day=?, lucky_stone=? WHERE client_id=?");
            $stmt->bind_param("ssssssiiisssi", $sun_sign, $moon_sign, $ascendant, $nakshatra, $rashi, $lagna, $birth_number, $destiny_number, $lucky_number, $lucky_color, $lucky_day, $lucky_stone, $client_id);
        } else {
            // Insert new
            $stmt = $conn->prepare("INSERT INTO horoscope_details (client_id, sun_sign, moon_sign, ascendant, nakshatra, rashi, lagna, birth_number, destiny_number, lucky_number, lucky_color, lucky_day, lucky_stone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssiiisss", $client_id, $sun_sign, $moon_sign, $ascendant, $nakshatra, $rashi, $lagna, $birth_number, $destiny_number, $lucky_number, $lucky_color, $lucky_day, $lucky_stone);
        }
        
        if($stmt->execute()){
            $message = "හොරෝස්කෝප් විස්තර සාර්ථකව යාවත්කාලීන කරන ලදී!";
            $messageType = 'success';
        } else {
            $message = "හොරෝස්කෝප් විස්තර යාවත්කාලීන කිරීම අසාර්ථක විය.";
            $messageType = 'error';
        }
        $stmt->close();
        $check->close();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        $message = "අවලංගු ඉල්ලීමක්.";
        $messageType = 'error';
    } else {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        $stmt = $conn->prepare("SELECT password FROM client WHERE id=?");
        $stmt->bind_param("i", $client_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if(!password_verify($current_password, $user['password'])){
            $message = "වත්මන් මුරපදය වැරදියි.";
            $messageType = 'error';
        } elseif(strlen($new_password) < 6){
            $message = "නව මුරපදය අකුරු 6කට වඩා විශාල විය යුතුය.";
            $messageType = 'error';
        } elseif($new_password !== $confirm_password){
            $message = "මුරපද ගැලපෙන්නේ නැත.";
            $messageType = 'error';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE client SET password=? WHERE id=?");
            $stmt->bind_param("si", $hashed_password, $client_id);
            
            if($stmt->execute()){
                $message = "මුරපදය සාර්ථකව වෙනස් කරන ලදී!";
                $messageType = 'success';
            }
            $stmt->close();
        }
    }
}

// Get client details
$sql = "SELECT * FROM client WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$client_details = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get horoscope details
$horoscope = null;
$stmt = $conn->prepare("SELECT * FROM horoscope_details WHERE client_id=?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows > 0){
    $horoscope = $result->fetch_assoc();
}
$stmt->close();

// Get active dasha
$active_dasha = null;
$stmt = $conn->prepare("SELECT * FROM dasha_periods WHERE client_id=? AND is_active=1 ORDER BY start_date DESC LIMIT 1");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows > 0){
    $active_dasha = $result->fetch_assoc();
}
$stmt->close();

// Get remedies
$remedies = [];
$stmt = $conn->prepare("SELECT * FROM horoscope_remedies WHERE client_id=? AND is_completed=0 ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()){
    $remedies[] = $row;
}
$stmt->close();

// Calculate account age
$account_age = '';
if($client_details['created_at']) {
    $created = new DateTime($client_details['created_at']);
    $now = new DateTime();
    $diff = $created->diff($now);
    
    if($diff->y > 0) {
        $account_age = $diff->y . ' වසර';
    } elseif($diff->m > 0) {
        $account_age = $diff->m . ' මාස';
    } else {
        $account_age = $diff->d . ' දින';
    }
}

// Generate CSRF token
if(!isset($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = 'Profile සංස්කරණය';
include 'header.php';
?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - නෙත්සිත ජෝතිශ්‍ය සේවය</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans Sinhala', sans-serif;
            background: linear-gradient(135deg, #1a0033 0%, #2d1b4e 50%, #1a0033 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        .stars {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .star {
            position: absolute;
            width: 2px;
            height: 2px;
            background: white;
            border-radius: 50%;
            animation: twinkle 3s infinite;
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 1; }
        }

        .profile-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px 80px 20px;
            position: relative;
            z-index: 10;
        }

        .breadcrumb-nav {
            margin-bottom: 20px;
            color: #b8a7d4;
        }

        .breadcrumb-nav a {
            color: #daa520;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .breadcrumb-nav a:hover {
            color: #ffd700;
        }

        .breadcrumb-nav span {
            margin: 0 10px;
        }

        .page-header {
            background: rgba(45, 27, 78, 0.95);
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-header h1 {
            color: #daa520;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #b8a7d4;
            margin: 0;
        }

        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .message.success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.5);
            color: #90ee90;
        }

        .message.error {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.5);
            color: #ff6b6b;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .profile-sidebar {
            background: rgba(45, 27, 78, 0.95);
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            animation: slideInLeft 0.6s ease-out;
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: white;
            font-weight: bold;
            animation: pulse 2s infinite;
            border: 5px solid rgba(218, 165, 32, 0.3);
            box-shadow: 0 0 30px rgba(218, 165, 32, 0.5);
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .profile-name {
            color: #daa520;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .profile-email {
            color: #b8a7d4;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .profile-stats {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid rgba(218, 165, 32, 0.2);
        }

        .stat-item {
            margin-bottom: 20px;
        }

        .stat-label {
            color: #8a7ba8;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .stat-value {
            color: #e0d5f0;
            font-size: 18px;
            font-weight: 600;
        }

        .quick-links {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid rgba(218, 165, 32, 0.2);
        }

        .quick-link {
            display: block;
            padding: 12px;
            margin-bottom: 10px;
            background: rgba(218, 165, 32, 0.1);
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-radius: 10px;
            color: #daa520;
            text-decoration: none;
            transition: all 0.3s ease;
            text-align: left;
        }

        .quick-link:hover {
            background: rgba(218, 165, 32, 0.2);
            transform: translateX(5px);
        }

        .quick-link i {
            margin-right: 10px;
            width: 20px;
        }

        .profile-content {
            animation: slideInRight 0.6s ease-out;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .content-section {
            background: rgba(45, 27, 78, 0.95);
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 20px;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(218, 165, 32, 0.2);
        }

        .section-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }

        .section-title {
            color: #daa520;
            font-size: 22px;
            font-weight: bold;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 12px 24px;
            background: rgba(218, 165, 32, 0.1);
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-radius: 25px;
            color: #daa520;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Noto Sans Sinhala', sans-serif;
            font-size: 14px;
            font-weight: 600;
        }

        .tab-btn:hover {
            background: rgba(218, 165, 32, 0.2);
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #daa520 0%, #b8860b 100%);
            color: #1a0033;
            border-color: #daa520;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            color: #daa520;
            font-size: 14px;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #daa520;
            font-size: 16px;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="date"],
        input[type="time"],
        input[type="number"],
        input[type="password"],
        select,
        textarea {
            width: 100%;
            padding: 12px 15px 12px 45px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-radius: 10px;
            color: white;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: 'Noto Sans Sinhala', sans-serif;
        }

        select {
            cursor: pointer;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #daa520;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 15px rgba(218, 165, 32, 0.3);
        }

        input::placeholder,
        textarea::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        input[readonly] {
            background: rgba(255, 255, 255, 0.05);
            cursor: not-allowed;
        }

        select option {
            background: #2d1b4e;
            color: white;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #daa520;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .password-toggle:hover {
            color: #ffd700;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 25px;
            border: none;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-family: 'Noto Sans Sinhala', sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, #daa520 0%, #b8860b 100%);
            color: #1a0033;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(218, 165, 32, 0.4);
        }

        .btn-secondary {
            background: rgba(218, 165, 32, 0.2);
            color: #daa520;
            border: 2px solid rgba(218, 165, 32, 0.4);
        }

        .btn-secondary:hover {
            background: rgba(218, 165, 32, 0.3);
            transform: translateY(-2px);
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        .info-box {
            background: rgba(218, 165, 32, 0.1);
            border: 1px solid rgba(218, 165, 32, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            color: #b8a7d4;
            font-size: 13px;
            line-height: 1.6;
        }

        .info-box i {
            color: #daa520;
            margin-right: 8px;
        }

        .horoscope-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .horoscope-card {
            background: rgba(218, 165, 32, 0.1);
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .horoscope-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(218, 165, 32, 0.3);
        }

        .horoscope-card-icon {
            font-size: 30px;
            color: #daa520;
            margin-bottom: 10px;
        }

        .horoscope-card-label {
            color: #8a7ba8;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .horoscope-card-value {
            color: #e0d5f0;
            font-size: 18px;
            font-weight: 600;
        }

        .remedy-item {
            background: rgba(218, 165, 32, 0.05);
            border: 1px solid rgba(218, 165, 32, 0.2);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .remedy-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .remedy-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .remedy-type {
            color: #daa520;
            font-weight: 600;
            font-size: 16px;
        }

        .remedy-description {
            color: #b8a7d4;
            font-size: 14px;
            line-height: 1.6;
        }

        .dasha-card {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
        }

        .dasha-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .dasha-planet {
            color: #daa520;
            font-size: 24px;
            font-weight: bold;
        }

        .dasha-period {
            color: #b8a7d4;
            font-size: 14px;
        }

        @media (max-width: 1200px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }

            .profile-sidebar {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .horoscope-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .tabs {
                overflow-x: auto;
                flex-wrap: nowrap;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .horoscope-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="stars" id="stars"></div>

    <div class="profile-container">
        <!-- Breadcrumb -->
        <div class="breadcrumb-nav">
            <a href="index.php"><i class="fas fa-home"></i> මුල් පිටුව</a>
            <span>/</span>
            <a href="client_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <span>/</span>
            <span style="color: #e0d5f0;">Profile සංස්කරණය</span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-user-astronaut"></i> මගේ සම්පූර්ණ Profile</h1>
            <p>පෞද්ගලික තොරතුරු සහ හොරෝස්කෝප් විස්තර කළමනාකරණය කරන්න</p>
        </div>

        <?php if($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'error' ? 'exclamation-circle' : 'check-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="profile-grid">
            <!-- Sidebar -->
            <div class="profile-sidebar">
                <div class="profile-avatar">
                    <?php echo mb_substr($client_details['name'], 0, 1, 'UTF-8'); ?>
                </div>
                <div class="profile-name"><?php echo htmlspecialchars($client_details['name']); ?></div>
                <div class="profile-email"><?php echo htmlspecialchars($client_details['email']); ?></div>

                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-label"><i class="fas fa-calendar-check"></i> ගිණුම් වයස</div>
                        <div class="stat-value"><?php echo $account_age; ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label"><i class="fas fa-birthday-cake"></i> උපන් දිනය</div>
                        <div class="stat-value"><?php echo $client_details['birth_date'] ? date('Y-m-d', strtotime($client_details['birth_date'])) : 'සකස් කර නැත'; ?></div>
                    </div>
                    <?php if($horoscope): ?>
                    <div class="stat-item">
                        <div class="stat-label"><i class="fas fa-star"></i> රාශිය</div>
                        <div class="stat-value"><?php echo htmlspecialchars($horoscope['rashi'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label"><i class="fas fa-gem"></i> නැකත</div>
                        <div class="stat-value"><?php echo htmlspecialchars($horoscope['nakshatra'] ?? 'N/A'); ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="quick-links">
                    <a href="client_dashboard.php" class="quick-link">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="my_consultations.php" class="quick-link">
                        <i class="fas fa-list-alt"></i> මගේ උපදේශන
                    </a>
                    <a href="services.php" class="quick-link">
                        <i class="fas fa-th-large"></i> සේවා බලන්න
                    </a>
                </div>
            </div>

            <!-- Content -->
            <div class="profile-content">
                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab-btn active" onclick="openTab(event, 'personal')">
                        <i class="fas fa-user"></i> පෞද්ගලික තොරතුරු
                    </button>
                    <button class="tab-btn" onclick="openTab(event, 'horoscope')">
                        <i class="fas fa-star"></i> හොරෝස්කෝප් විස්තර
                    </button>
                    <button class="tab-btn" onclick="openTab(event, 'dasha')">
                        <i class="fas fa-calendar-alt"></i> දශා කාල
                    </button>
                    <button class="tab-btn" onclick="openTab(event, 'remedies')">
                        <i class="fas fa-hands-helping"></i> පිළියම්
                    </button>
                    <button class="tab-btn" onclick="openTab(event, 'password')">
                        <i class="fas fa-lock"></i> මුරපදය
                    </button>
                </div>

                <!-- Personal Information Tab -->
                <div id="personal" class="tab-content active">
                    <div class="content-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="section-title">පෞද්ගලික තොරතුරු</div>
                        </div>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="update_profile" value="1">

                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="name">සම්පූර්ණ නම *</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-user input-icon"></i>
                                        <input type="text" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($client_details['name']); ?>" 
                                               required minlength="3">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="email">විද්‍යුත් තැපෑල</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-envelope input-icon"></i>
                                        <input type="email" id="email" 
                                               value="<?php echo htmlspecialchars($client_details['email']); ?>" 
                                               readonly>
                                    </div>
                                    <small style="color: #8a7ba8; font-size: 12px;">Email එක වෙනස් කළ නොහැක</small>
                                </div>

                                <div class="form-group">
                                    <label for="phone">දුරකථන අංකය</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-phone input-icon"></i>
                                        <input type="tel" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($client_details['phone'] ?? ''); ?>" 
                                               placeholder="0712345678">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="gender">ස්ත්‍රී පුරුෂ භාවය</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-venus-mars input-icon"></i>
                                        <select id="gender" name="gender">
                                            <option value="">තෝරන්න</option>
                                            <option value="male" <?php echo ($client_details['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>පුරුෂ</option>
                                            <option value="female" <?php echo ($client_details['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>ස්ත්‍රී</option>
                                            <option value="other" <?php echo ($client_details['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>වෙනත්</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="marital_status">විවාහක තත්ත්වය</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-ring input-icon"></i>
                                        <select id="marital_status" name="marital_status">
                                            <option value="">තෝරන්න</option>
                                            <option value="single" <?php echo ($client_details['marital_status'] ?? '') === 'single' ? 'selected' : ''; ?>>අවිවාහක</option>
                                            <option value="married" <?php echo ($client_details['marital_status'] ?? '') === 'married' ? 'selected' : ''; ?>>විවාහක</option>
                                            <option value="divorced" <?php echo ($client_details['marital_status'] ?? '') === 'divorced' ? 'selected' : ''; ?>>දික්කසාද</option>
                                            <option value="widowed" <?php echo ($client_details['marital_status'] ?? '') === 'widowed' ? 'selected' : ''; ?>>වැන්දඹු/වැන්දඹුනිය</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="birth_date">උපන් දිනය *</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-calendar input-icon"></i>
                                        <input type="date" id="birth_date" name="birth_date" 
                                               value="<?php echo htmlspecialchars($client_details['birth_date'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="birth_time">උපන් වේලාව *</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-clock input-icon"></i>
                                        <input type="time" id="birth_time" name="birth_time" 
                                               value="<?php echo htmlspecialchars($client_details['birth_time'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="birth_place">උපන් ස්ථානය *</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-map-marker-alt input-icon"></i>
                                        <input type="text" id="birth_place" name="birth_place" 
                                               value="<?php echo htmlspecialchars($client_details['birth_place'] ?? ''); ?>" 
                                               placeholder="කොළඹ, ශ්‍රී ලංකාව">
                                    </div>
                                </div>

                                <div class="form-group full-width">
                                    <label for="address">ලිපිනය</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-home input-icon"></i>
                                        <textarea id="address" name="address" 
                                                  placeholder="ඔබගේ සම්පූර්ණ ලිපිනය"><?php echo htmlspecialchars($client_details['address'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="info-box">
                                <i class="fas fa-info-circle"></i>
                                <strong>මෙම තොරතුරු ඉතා වැදගත්:</strong> උපන් දිනය, වේලාව සහ ස්ථානය හොරෝස්කෝප් විශ්ලේෂණ සඳහා අත්‍යවශ්‍ය වේ. නිවැරදි තොරතුරු සපයන්න.
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> යාවත්කාලීන කරන්න
                                </button>
                                <a href="client_dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> අවලංගු කරන්න
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Horoscope Details Tab -->
                <div id="horoscope" class="tab-content">
                    <div class="content-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="section-title">හොරෝස්කෝප් විස්තර</div>
                        </div>

                        <?php if($horoscope): ?>
                        <div class="horoscope-grid">
                            <div class="horoscope-card">
                                <div class="horoscope-card-icon">☀️</div>
                                <div class="horoscope-card-label">Sun Sign</div>
                                <div class="horoscope-card-value"><?php echo htmlspecialchars($horoscope['sun_sign'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="horoscope-card">
                                <div class="horoscope-card-icon">🌙</div>
                                <div class="horoscope-card-label">Moon Sign</div>
                                <div class="horoscope-card-value"><?php echo htmlspecialchars($horoscope['moon_sign'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="horoscope-card">
                                <div class="horoscope-card-icon">⬆️</div>
                                <div class="horoscope-card-label">Ascendant</div>
                                <div class="horoscope-card-value"><?php echo htmlspecialchars($horoscope['ascendant'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="horoscope-card">
                                <div class="horoscope-card-icon">⭐</div>
                                <div class="horoscope-card-label">නැකත</div>
                                <div class="horoscope-card-value"><?php echo htmlspecialchars($horoscope['nakshatra'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="horoscope-card">
                                <div class="horoscope-card-icon">♈</div>
                                <div class="horoscope-card-label">රාශිය</div>
                                <div class="horoscope-card-value"><?php echo htmlspecialchars($horoscope['rashi'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="horoscope-card">
                                <div class="horoscope-card-icon">🔮</div>
                                <div class="horoscope-card-label">ලග්නය</div>
                                <div class="horoscope-card-value"><?php echo htmlspecialchars($horoscope['lagna'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="horoscope-card">
                                <div class="horoscope-card-icon">1️⃣</div>
                                <div class="horoscope-card-label">උපන් අංකය</div>
                                <div class="horoscope-card-value"><?php echo htmlspecialchars($horoscope['birth_number'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="horoscope-card">
                                <div class="horoscope-card-icon">🎯</div>
                                <div class="horoscope-card-label">ඉරණම් අංකය</div>
                                <div class="horoscope-card-value"><?php echo htmlspecialchars($horoscope['destiny_number'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="horoscope-card">
                                <div class="horoscope-card-icon">🍀</div>
                                <div class="horoscope-card-label">වාසනා අංකය</div>
                                <div class="horoscope-card-value"><?php echo htmlspecialchars($horoscope['lucky_number'] ?? 'N/A'); ?></div>
                            </div>
                        </div>

                        <div class="info-box">
                            <i class="fas fa-gem"></i>
                            <strong>වාසනාවන්ත:</strong> 
                            වර්ණය - <?php echo htmlspecialchars($horoscope['lucky_color'] ?? 'N/A'); ?> | 
                            දිනය - <?php echo htmlspecialchars($horoscope['lucky_day'] ?? 'N/A'); ?> | 
                            මැණික් - <?php echo htmlspecialchars($horoscope['lucky_stone'] ?? 'N/A'); ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="update_horoscope" value="1">

                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="sun_sign">Sun Sign</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-sun input-icon"></i>
                                        <input type="text" id="sun_sign" name="sun_sign" 
                                               value="<?php echo htmlspecialchars($horoscope['sun_sign'] ?? ''); ?>" 
                                               placeholder="Aries, Taurus, etc.">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="moon_sign">Moon Sign</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-moon input-icon"></i>
                                        <input type="text" id="moon_sign" name="moon_sign" 
                                               value="<?php echo htmlspecialchars($horoscope['moon_sign'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="ascendant">Ascendant (ලග්න රාශිය)</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-arrow-up input-icon"></i>
                                        <input type="text" id="ascendant" name="ascendant" 
                                               value="<?php echo htmlspecialchars($horoscope['ascendant'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="nakshatra">නැකත</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-star input-icon"></i>
                                        <input type="text" id="nakshatra" name="nakshatra" 
                                               value="<?php echo htmlspecialchars($horoscope['nakshatra'] ?? ''); ?>" 
                                               placeholder="අශ්විනී, භරණී, කාර්තික...">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="rashi">රාශිය</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-circle-notch input-icon"></i>
                                        <input type="text" id="rashi" name="rashi" 
                                               value="<?php echo htmlspecialchars($horoscope['rashi'] ?? ''); ?>" 
                                               placeholder="මේෂ, වෘෂභ, මිථුන...">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="lagna">ලග්නය</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-circle input-icon"></i>
                                        <input type="text" id="lagna" name="lagna" 
                                               value="<?php echo htmlspecialchars($horoscope['lagna'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="birth_number">උපන් අංකය</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-sort-numeric-up input-icon"></i>
                                        <input type="number" id="birth_number" name="birth_number" 
                                               value="<?php echo htmlspecialchars($horoscope['birth_number'] ?? ''); ?>" 
                                               min="1" max="9">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="destiny_number">ඉරණම් අංකය</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-hashtag input-icon"></i>
                                        <input type="number" id="destiny_number" name="destiny_number" 
                                               value="<?php echo htmlspecialchars($horoscope['destiny_number'] ?? ''); ?>" 
                                               min="1" max="9">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="lucky_number">වාසනා අංකය</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-clover input-icon"></i>
                                        <input type="number" id="lucky_number" name="lucky_number" 
                                               value="<?php echo htmlspecialchars($horoscope['lucky_number'] ?? ''); ?>" 
                                               min="1" max="9">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="lucky_color">වාසනාවන්ත වර්ණය</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-palette input-icon"></i>
                                        <input type="text" id="lucky_color" name="lucky_color" 
                                               value="<?php echo htmlspecialchars($horoscope['lucky_color'] ?? ''); ?>" 
                                               placeholder="රතු, නිල්, කහ...">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="lucky_day">වාසනාවන්ත දිනය</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-calendar-day input-icon"></i>
                                        <input type="text" id="lucky_day" name="lucky_day" 
                                               value="<?php echo htmlspecialchars($horoscope['lucky_day'] ?? ''); ?>" 
                                               placeholder="සඳුදා, අඟහරුවාදා...">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="lucky_stone">වාසනාවන්ත මැණික්</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-gem input-icon"></i>
                                        <input type="text" id="lucky_stone" name="lucky_stone" 
                                               value="<?php echo htmlspecialchars($horoscope['lucky_stone'] ?? ''); ?>" 
                                               placeholder="නිල්, මැණික්, මුතු...">
                                    </div>
                                </div>
                            </div>

                            <div class="info-box">
                                <i class="fas fa-user-astronaut"></i>
                                මෙම තොරතුරු අපගේ ජ්‍යෝතිෂවේදීන් විසින් පුරවා යවනු ලැබේ. ඔබ දන්නා තොරතුරු පමණක් යාවත්කාලීන කරන්න.
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> යාවත්කාලීන කරන්න
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Dasha Period Tab -->
                <div id="dasha" class="tab-content">
                    <div class="content-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="section-title">දශා කාල</div>
                        </div>

                        <?php if($active_dasha): ?>
                        <div class="dasha-card">
                            <div class="dasha-header">
                                <div>
                                    <div class="dasha-planet">
                                        <?php echo htmlspecialchars($active_dasha['planet']); ?> මහා දශාව
                                    </div>
                                    <div class="dasha-period">
                                        <?php echo date('Y-m-d', strtotime($active_dasha['start_date'])); ?> සිට 
                                        <?php echo date('Y-m-d', strtotime($active_dasha['end_date'])); ?> දක්වා
                                    </div>
                                </div>
                                <div style="font-size: 40px;">🪐</div>
                            </div>
                            <?php if($active_dasha['description']): ?>
                            <div style="color: #b8a7d4; margin-top: 15px; line-height: 1.6;">
                                <?php echo nl2br(htmlspecialchars($active_dasha['description'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="info-box">
                            <i class="fas fa-info-circle"></i>
                            දැනට ක්‍රියාත්මක දශා කාලයක් නොමැත. අපගේ ජ්‍යෝතිෂවේදීන් හා සම්බන්ධ වන්න.
                        </div>
                        <?php endif; ?>

                        <div class="info-box" style="margin-top: 20px;">
                            <i class="fas fa-book"></i>
                            <strong>දශා කාල ගැන:</strong> දශා කාල ජීවිතයේ විවිධ අවස්ථා වලදී අපට බලපාන ග්‍රහලෝක බලපෑම් පෙන්වයි. මහාදශා, අන්තර්දශා සහ ප්‍රත්‍යන්තර්දශා යනු මූලික වර්ග තුනයි.
                        </div>
                    </div>
                </div>

                <!-- Remedies Tab -->
                <div id="remedies" class="tab-content">
                    <div class="content-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-hands-helping"></i>
                            </div>
                            <div class="section-title">පිළියම් හා පරිහරණ</div>
                        </div>

                        <?php if(count($remedies) > 0): ?>
                            <?php foreach($remedies as $remedy): ?>
                            <div class="remedy-item">
                                <div class="remedy-header">
                                    <div class="remedy-icon">
                                        <?php
                                        $icons = [
                                            'gemstone' => '💎',
                                            'mantra' => '🕉️',
                                            'puja' => '🙏',
                                            'yantra' => '🔯',
                                            'charity' => '❤️',
                                            'fasting' => '🍵',
                                            'other' => '✨'
                                        ];
                                        echo $icons[$remedy['remedy_type']] ?? '✨';
                                        ?>
                                    </div>
                                    <div>
                                        <div class="remedy-type">
                                            <?php
                                            $types = [
                                                'gemstone' => 'මැණික් පැළඳීම',
                                                'mantra' => 'මන්ත්‍ර ජපය',
                                                'puja' => 'පූජා කිරීම',
                                                'yantra' => 'යන්ත්‍ර භාවිතය',
                                                'charity' => 'දානධර්ම',
                                                'fasting' => 'උපවාස',
                                                'other' => 'වෙනත්'
                                            ];
                                            echo $types[$remedy['remedy_type']] ?? $remedy['remedy_type'];
                                            ?>
                                            <?php if($remedy['planet']): ?>
                                                <span style="color: #8a7ba8; font-size: 14px; font-weight: normal;">
                                                    - <?php echo htmlspecialchars($remedy['planet']); ?> සඳහා
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="remedy-description">
                                    <?php echo nl2br(htmlspecialchars($remedy['description'])); ?>
                                </div>
                                <?php if($remedy['instructions']): ?>
                                <div class="remedy-description" style="margin-top: 10px; border-top: 1px solid rgba(218, 165, 32, 0.2); padding-top: 10px;">
                                    <strong style="color: #daa520;">උපදෙස්:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($remedy['instructions'])); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <div class="info-box">
                            <i class="fas fa-info-circle"></i>
                            දැනට යෝජිත පිළියම් නොමැත. ඔබගේ හොරෝස්කෝප් විශ්ලේෂණයෙන් පසු පිළියම් යෝජනා කෙරෙනු ඇත.
                        </div>
                        <?php endif; ?>

                        <div class="info-box" style="margin-top: 20px;">
                            <i class="fas fa-question-circle"></i>
                            <strong>පිළියම් ගැන:</strong> මෙම පිළියම් අපගේ ජ්‍යෝතිෂවේදීන් විසින් ඔබගේ හොරෝස්කෝප් විශ්ලේෂණයෙන් පසුව නිර්දේශ කරනු ලැබේ. නිසි ලෙස අනුගමනය කිරීමෙන් ජීවිතයේ ධනාත්මක වෙනස්කම් ඇති කර ගත හැක.
                        </div>
                    </div>
                </div>

                <!-- Password Tab -->
                <div id="password" class="tab-content">
                    <div class="content-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <div class="section-title">මුරපදය වෙනස් කරන්න</div>
                        </div>

                        <form method="POST" action="" id="passwordForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="change_password" value="1">

                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label for="current_password">වත්මන් මුරපදය *</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-lock input-icon"></i>
                                        <input type="password" id="current_password" name="current_password" required>
                                        <i class="fas fa-eye password-toggle" onclick="togglePassword('current_password')"></i>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="new_password">නව මුරපදය *</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-key input-icon"></i>
                                        <input type="password" id="new_password" name="new_password" 
                                               required minlength="6" placeholder="අකුරු 6ක් හෝ වැඩි">
                                        <i class="fas fa-eye password-toggle" onclick="togglePassword('new_password')"></i>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password">මුරපදය තහවුරු කරන්න *</label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-key input-icon"></i>
                                        <input type="password" id="confirm_password" name="confirm_password" 
                                               required minlength="6">
                                        <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password')"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="info-box">
                                <i class="fas fa-shield-alt"></i>
                                <strong>ආරක්ෂිත මුරපදයක් සඳහා:</strong> අකුරු 6කට වඩා, විශාල අකුරු, කුඩා අකුරු, සංඛ්‍යා සහ විශේෂ අකුරු භාවිතා කරන්න.
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key"></i> මුරපදය වෙනස් කරන්න
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Create animated stars
        const starsContainer = document.getElementById('stars');
        for(let i = 0; i < 150; i++) {
            const star = document.createElement('div');
            star.className = 'star';
            star.style.left = Math.random() * 100 + '%';
            star.style.top = Math.random() * 100 + '%';
            star.style.animationDelay = Math.random() * 3 + 's';
            starsContainer.appendChild(star);
        }

        // Tab functionality
        function openTab(evt, tabName) {
            const tabcontent = document.getElementsByClassName("tab-content");
            for (let i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            
            const tablinks = document.getElementsByClassName("tab-btn");
            for (let i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }

        // Password toggle
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling;
            
            if(field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Password form validation
        const passwordForm = document.getElementById('passwordForm');
        if(passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if(newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('නව මුරපදය සහ තහවුරු කිරීමේ මුරපදය ගැලපෙන්නේ නැත!');
                }
            });
        }

        // Auto-hide success messages
        const successMessage = document.querySelector('.message.success');
        if(successMessage) {
            setTimeout(() => {
                successMessage.style.transition = 'opacity 0.5s ease';
                successMessage.style.opacity = '0';
                setTimeout(() => {
                    successMessage.remove();
                }, 500);
            }, 5000);
        }
    </script>
</body>
</html>

<?php
$conn->close();
include('footer.php');
?>