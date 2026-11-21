<?php
session_start();
require_once 'db_connection.php';

$message = '';
$messageType = '';

// Security check: Verify temp_client_id exists
if(!isset($_SESSION['temp_client_id'])){
    header("Location: login.php");
    exit;
}

$temp_client_id = $_SESSION['temp_client_id'];

// Handle OTP Verification
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])){
    // CSRF Protection
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        $message = "අවලංගු ඉල්ලීමක්. නැවත උත්සාහ කරන්න.";
        $messageType = 'error';
    } else {
        $otp = trim($_POST['otp']);
        
        // Validate OTP format (6 digits)
        if(!preg_match('/^\d{6}$/', $otp)){
            $message = "OTP කේතය අංක 6කින් සමන්විත විය යුතුය.";
            $messageType = 'error';
        } else {
            // Fetch OTP details from database
            $stmt = $conn->prepare("SELECT otp_code, otp_expiry, name, email FROM client WHERE id=? LIMIT 1");
            $stmt->bind_param("i", $temp_client_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if($user){
                // Check if OTP matches
                if($user['otp_code'] === $otp){
                    // Check if OTP is not expired
                    if(strtotime($user['otp_expiry']) >= time()){
                        // Success! Set session variables
                        $_SESSION['client_logged_in'] = true;
                        $_SESSION['client_id'] = $temp_client_id;
                        $_SESSION['client_name'] = $user['name'];
                        $_SESSION['client_email'] = $user['email'];
                        
                        // Remove temporary session variables
                        unset($_SESSION['temp_client_id']);
                        unset($_SESSION['temp_client_email']);
                        
                        // Clear OTP from database for security
                        $update_stmt = $conn->prepare("UPDATE client SET otp_code = NULL, otp_expiry = NULL WHERE id = ?");
                        $update_stmt->bind_param("i", $_SESSION['client_id']);
                        $update_stmt->execute();
                        $update_stmt->close();
                        
                        // Redirect to dashboard
                        $message = "සත්‍යාපනය සාර්ථකයි! Dashboard එකට යොමු කරමින්...";
                        $messageType = 'success';
                        header("refresh:1; url=dashboard.php");
                    } else {
                        $message = "OTP කේතය කල් ඉකුත් වී ඇත. කරුණාකර නව OTP එකක් ඉල්ලන්න.";
                        $messageType = 'error';
                    }
                } else {
                    $message = "වැරදි OTP කේතයකි. කරුණාකර නැවත උත්සාහ කරන්න.";
                    $messageType = 'error';
                }
            } else {
                $message = "ගිණුමේ දත්ත සොයා ගැනීමට නොහැක.";
                $messageType = 'error';
            }
        }
    }
}

// Handle Resend OTP
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_otp'])){
    // CSRF Protection
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        $message = "අවලංගු ඉල්ලීමක්. නැවත උත්සාහ කරන්න.";
        $messageType = 'error';
    } else {
        // Get user details
        $stmt = $conn->prepare("SELECT name, email FROM client WHERE id=? LIMIT 1");
        $stmt->bind_param("i", $temp_client_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if($user){
            // Generate new OTP
            $new_otp = sprintf("%06d", rand(0, 999999));
            $new_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            
            // Update database
            $update_stmt = $conn->prepare("UPDATE client SET otp_code=?, otp_expiry=? WHERE id=?");
            $update_stmt->bind_param("ssi", $new_otp, $new_expiry, $temp_client_id);
            
            if($update_stmt->execute()){
                // Try to send email
                if(file_exists('includes/send_email.php')){
                    include_once('includes/send_email.php');
                    
                    $emailSent = false;
                    if(function_exists('sendOTP_Email')){
                        $emailSent = sendOTP_Email($user['email'], $user['name'], $new_otp);
                    } elseif(function_exists('sendEmail')){
                        $subject = "නව OTP කේතය - නෙත්සිත ජෝතිශ්‍ය සේවය";
                        $body = "ආයුබෝවන් " . $user['name'] . ",\n\n";
                        $body .= "ඔබගේ නව OTP කේතය: " . $new_otp . "\n\n";
                        $body .= "මෙම කේතය විනාඩි 5ක් සඳහා වලංගු වේ.\n\n";
                        $body .= "ස්තුතියි,\nනෙත්සිත ජෝතිශ්‍ය සේවය";
                        $emailSent = sendEmail($user['email'], $user['name'], $subject, $body);
                    }
                    
                    if($emailSent){
                        $message = "නව OTP කේතය ඔබගේ Email එකට යවා ඇත.";
                        $messageType = 'success';
                    } else {
                        $message = "OTP යැවීමට නොහැකි විය. නැවත උත්සාහ කරන්න.";
                        $messageType = 'error';
                    }
                } else {
                    $message = "OTP යැවීමේ පද්ධතිය ක්‍රියා විරහිතයි.";
                    $messageType = 'error';
                }
            }
            $update_stmt->close();
        }
    }
}

// Generate CSRF token
if(!isset($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get user email for display (masked)
$user_email = '';
if(isset($_SESSION['temp_client_email'])){
    $user_email = $_SESSION['temp_client_email'];
} else {
    $stmt = $conn->prepare("SELECT email FROM client WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $temp_client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if($user){
        $user_email = $user['email'];
        $_SESSION['temp_client_email'] = $user_email;
    }
    $stmt->close();
}

// Mask email for security (show only first 2 chars and domain)
function maskEmail($email) {
    $parts = explode('@', $email);
    if(count($parts) == 2){
        $name = $parts[0];
        $domain = $parts[1];
        $masked_name = substr($name, 0, 2) . str_repeat('*', strlen($name) - 2);
        return $masked_name . '@' . $domain;
    }
    return $email;
}

$masked_email = maskEmail($user_email);
$conn->close();
?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP සත්‍යාපනය - නෙත්සිත ජෝතිශ්‍ය සේවය</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Cosmic Background Effects */
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

        .planet {
            position: absolute;
            border-radius: 50%;
            opacity: 0.3;
            animation: float 20s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(30px, -30px); }
        }

        /* OTP Container */
        .otp-container {
            background: rgba(45, 27, 78, 0.95);
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 10px 50px rgba(218, 165, 32, 0.2);
            position: relative;
            z-index: 10;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .otp-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .otp-icon {
            font-size: 60px;
            color: #daa520;
            margin-bottom: 15px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        h2 {
            color: #daa520;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #b8a7d4;
            font-size: 14px;
            line-height: 1.6;
        }

        .email-info {
            background: rgba(218, 165, 32, 0.1);
            border: 1px solid rgba(218, 165, 32, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }

        .email-info i {
            color: #daa520;
            margin-right: 8px;
        }

        .email-info span {
            color: #ffd700;
            font-weight: bold;
        }

        /* Message Styles */
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

        /* OTP Input Boxes */
        .otp-inputs {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 30px 0;
        }

        .otp-input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-radius: 10px;
            color: #daa520;
            transition: all 0.3s ease;
        }

        .otp-input:focus {
            outline: none;
            border-color: #daa520;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 15px rgba(218, 165, 32, 0.3);
        }

        /* Hidden input for form submission */
        #otp-hidden {
            display: none;
        }

        /* Button Styles */
        .btn-verify {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #daa520 0%, #b8860b 100%);
            border: none;
            border-radius: 10px;
            color: #1a0033;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Noto Sans Sinhala', sans-serif;
            margin-top: 20px;
        }

        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(218, 165, 32, 0.4);
        }

        .btn-verify:active {
            transform: translateY(0);
        }

        .btn-verify:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-resend {
            width: 100%;
            padding: 12px;
            background: rgba(218, 165, 32, 0.2);
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-radius: 10px;
            color: #daa520;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Noto Sans Sinhala', sans-serif;
            margin-top: 15px;
        }

        .btn-resend:hover {
            background: rgba(218, 165, 32, 0.3);
            border-color: #daa520;
        }

        .btn-resend:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Timer */
        .timer {
            text-align: center;
            margin-top: 15px;
            color: #b8a7d4;
            font-size: 14px;
        }

        .timer.expired {
            color: #ff6b6b;
        }

        /* Links */
        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #b8a7d4;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .back-link a:hover {
            color: #daa520;
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            .otp-container {
                padding: 30px 20px;
            }

            h2 {
                font-size: 24px;
            }

            .otp-icon {
                font-size: 50px;
            }

            .otp-input {
                width: 45px;
                height: 55px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Cosmic Background -->
    <div class="stars" id="stars"></div>

    <div class="otp-container">
        <div class="otp-header">
            <div class="otp-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h2>OTP සත්‍යාපනය</h2>
            <p class="subtitle">ඔබගේ ගිණුම ආරක්ෂා කරන්න</p>
        </div>

        <div class="email-info">
            <i class="fas fa-envelope"></i>
            OTP කේතය යවා ඇත: <span><?php echo htmlspecialchars($masked_email); ?></span>
        </div>

        <?php if($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'error' ? 'exclamation-circle' : 'check-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="otpForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="verify_otp" value="1">
            <input type="hidden" name="otp" id="otp-hidden">
            
            <div class="otp-inputs">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
            </div>

            <button type="submit" class="btn-verify" id="verifyBtn">
                <i class="fas fa-check-circle"></i> සත්‍යාපනය කරන්න
            </button>
        </form>

        <form method="POST" action="" id="resendForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="resend_otp" value="1">
            <button type="submit" class="btn-resend" id="resendBtn">
                <i class="fas fa-redo"></i> නව OTP කේතයක් ඉල්ලන්න
            </button>
        </form>

        <div class="timer" id="timer">
            <i class="fas fa-clock"></i> කේතය කල් ඉකුත් වේ: <span id="countdown">5:00</span>
        </div>

        <div class="back-link">
            <a href="login.php"><i class="fas fa-arrow-left"></i> නැවත Login වන්න</a>
        </div>
    </div>

    <script>
        // Create animated stars
        const starsContainer = document.getElementById('stars');
        for(let i = 0; i < 100; i++) {
            const star = document.createElement('div');
            star.className = 'star';
            star.style.left = Math.random() * 100 + '%';
            star.style.top = Math.random() * 100 + '%';
            star.style.animationDelay = Math.random() * 3 + 's';
            starsContainer.appendChild(star);
        }

        // Create floating planets
        for(let i = 0; i < 3; i++) {
            const planet = document.createElement('div');
            planet.className = 'planet';
            planet.style.width = (20 + Math.random() * 40) + 'px';
            planet.style.height = planet.style.width;
            planet.style.left = Math.random() * 100 + '%';
            planet.style.top = Math.random() * 100 + '%';
            planet.style.background = `radial-gradient(circle at 30% 30%, rgba(218, 165, 32, 0.3), rgba(138, 43, 226, 0.2))`;
            planet.style.animationDelay = Math.random() * 10 + 's';
            starsContainer.appendChild(planet);
        }

        // OTP Input Handling
        const otpInputs = document.querySelectorAll('.otp-input');
        const otpHidden = document.getElementById('otp-hidden');
        const verifyBtn = document.getElementById('verifyBtn');

        otpInputs.forEach((input, index) => {
            // Auto-focus next input
            input.addEventListener('input', function(e) {
                if(this.value.length === 1 && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
                updateHiddenOTP();
            });

            // Handle backspace
            input.addEventListener('keydown', function(e) {
                if(e.key === 'Backspace' && this.value === '' && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });

            // Only allow numbers
            input.addEventListener('keypress', function(e) {
                if(!/[0-9]/.test(e.key)) {
                    e.preventDefault();
                }
            });

            // Handle paste
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').slice(0, 6);
                if(/^\d+$/.test(pastedData)) {
                    pastedData.split('').forEach((char, i) => {
                        if(otpInputs[i]) {
                            otpInputs[i].value = char;
                        }
                    });
                    updateHiddenOTP();
                    if(pastedData.length === 6) {
                        otpInputs[5].focus();
                    }
                }
            });
        });

        // Update hidden OTP field
        function updateHiddenOTP() {
            let otp = '';
            otpInputs.forEach(input => {
                otp += input.value;
            });
            otpHidden.value = otp;
            
            // Enable/disable verify button
            verifyBtn.disabled = otp.length !== 6;
        }

        // Focus first input on load
        otpInputs[0].focus();

        // Countdown Timer (5 minutes)
        let timeLeft = 300; // 5 minutes in seconds
        const countdownElement = document.getElementById('countdown');
        const timerElement = document.getElementById('timer');
        const resendBtn = document.getElementById('resendBtn');

        function updateCountdown() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if(timeLeft <= 0) {
                timerElement.classList.add('expired');
                countdownElement.textContent = 'කල් ඉකුත් විය';
                clearInterval(countdownTimer);
            }
            timeLeft--;
        }

        const countdownTimer = setInterval(updateCountdown, 1000);
        updateCountdown();

        // Resend cooldown (30 seconds)
        let resendCooldown = 0;
        resendBtn.addEventListener('click', function(e) {
            if(resendCooldown > 0) {
                e.preventDefault();
                return;
            }
            
            resendCooldown = 30;
            resendBtn.disabled = true;
            
            const originalText = resendBtn.innerHTML;
            const cooldownTimer = setInterval(() => {
                resendBtn.innerHTML = `<i class="fas fa-clock"></i> ${resendCooldown} තත්පර රැඳී සිටින්න`;
                resendCooldown--;
                
                if(resendCooldown < 0) {
                    clearInterval(cooldownTimer);
                    resendBtn.innerHTML = originalText;
                    resendBtn.disabled = false;
                }
            }, 1000);
        });
    </script>
</body>
</html>