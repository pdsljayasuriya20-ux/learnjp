<?php
session_start();
include('db_connection.php');

// Redirect if already logged in
if(isset($_SESSION['client_id'])){
    header("Location: dashboard.php");
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        $message = "අවලංගු ඉල්ලීමක්. නැවත උත්සාහ කරන්න.";
        $messageType = 'error';
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        // Validation
        if(empty($email) || empty($password)){
            $message = "සියලුම ක්ෂේත්‍ර පුරවන්න.";
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("SELECT id, name, email, password FROM client WHERE email=? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result->num_rows === 1){
                $user = $result->fetch_assoc();
                
                if(password_verify($password, $user['password'])){
                    // Generate OTP
                    $otp_code = sprintf("%06d", rand(0, 999999)); // Ensures 6 digits with leading zeros
                    $otp_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                    
                    $update = $conn->prepare("UPDATE client SET otp_code=?, otp_expiry=? WHERE id=?");
                    $update->bind_param("ssi", $otp_code, $otp_expiry, $user['id']);
                    
                    if($update->execute()){
                        // Check if send_email.php has OTP function
                        if(file_exists('includes/send_email.php')){
                            include_once('includes/send_email.php');
                            
                            // Try to send OTP email
                            $emailSent = false;
                            if(function_exists('sendOTP_Email')){
                                $emailSent = sendOTP_Email($user['email'], $user['name'], $otp_code);
                            } elseif(function_exists('sendEmail')){
                                // Fallback to generic sendEmail if sendOTP_Email doesn't exist
                                $subject = "ඔබගේ OTP කේතය - නෙත්සිත ජෝතිශ්‍ය සේවය";
                                $body = "ආයුබෝවන් " . $user['name'] . ",\n\n";
                                $body .= "ඔබගේ OTP කේතය: " . $otp_code . "\n\n";
                                $body .= "මෙම කේතය විනාඩි 5ක් සඳහා වලංගු වේ.\n\n";
                                $body .= "ස්තුතියි,\nනෙත්සිත ජෝතිශ්‍ය සේවය";
                                $emailSent = sendEmail($user['email'], $user['name'], $subject, $body);
                            }
                            
                            if($emailSent){
                                // Store temporary client ID for OTP verification
                                $_SESSION['temp_client_id'] = $user['id'];
                                $_SESSION['temp_client_email'] = $user['email'];
                                header("Location: verify_otp.php");
                                exit();
                            } else {
                                $message = "OTP email යැවීමට නොහැකි විය. කරුණාකර නැවත උත්සාහ කරන්න.";
                                $messageType = 'error';
                            }
                        } else {
                            // If email function doesn't exist, direct login (for testing)
                            $_SESSION['client_id'] = $user['id'];
                            $_SESSION['client_name'] = $user['name'];
                            $_SESSION['client_email'] = $user['email'];
                            header("Location: dashboard.php");
                            exit();
                        }
                    } else {
                        $message = "OTP ජනනය අසාර්ථකයි. නැවත උත්සාහ කරන්න.";
                        $messageType = 'error';
                    }
                    $update->close();
                } else {
                    $message = "වැරදි මුරපදයකි. නැවත උත්සාහ කරන්න.";
                    $messageType = 'error';
                }
            } else {
                $message = "මෙම Email ලියාපදිංචි වී නැත.";
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
}

// Generate CSRF token
if(!isset($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - නෙත්සිත ජෝතිශ්‍ය සේවය</title>
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

        /* Login Container */
        .login-container {
            background: rgba(45, 27, 78, 0.95);
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-radius: 20px;
            padding: 40px;
            max-width: 450px;
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

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-icon {
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

        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            color: #daa520;
            font-size: 16px;
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
            font-size: 18px;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px 12px 45px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-radius: 10px;
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
            font-family: 'Noto Sans Sinhala', sans-serif;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #daa520;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 15px rgba(218, 165, 32, 0.3);
        }

        input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #daa520;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .password-toggle:hover {
            color: #ffd700;
        }

        /* Button Styles */
        .btn-login {
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
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(218, 165, 32, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Links */
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #b8a7d4;
            font-size: 14px;
        }

        .register-link a {
            color: #daa520;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .register-link a:hover {
            color: #ffd700;
            text-decoration: underline;
        }

        .back-home {
            text-align: center;
            margin-top: 15px;
        }

        .back-home a {
            color: #b8a7d4;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .back-home a:hover {
            color: #daa520;
        }

        .forgot-password {
            text-align: right;
            margin-top: 10px;
        }

        .forgot-password a {
            color: #b8a7d4;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .forgot-password a:hover {
            color: #daa520;
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            .login-container {
                padding: 30px 20px;
            }

            h2 {
                font-size: 24px;
            }

            .login-icon {
                font-size: 50px;
            }
        }
    </style>
</head>
<body>
    <!-- Cosmic Background -->
    <div class="stars" id="stars"></div>

    <div class="login-container">
        <div class="login-header">
            <div class="login-icon">
                <i class="fas fa-sign-in-alt"></i>
            </div>
            <h2>ඇතුල් වන්න</h2>
            <p class="subtitle">ඔබගේ ගිණුමට පිවිසෙන්න</p>
        </div>

        <?php if($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'error' ? 'exclamation-circle' : 'check-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="email">විද්‍යුත් තැපෑල</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" id="email" name="email" placeholder="example@email.com" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">මුරපදය</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" placeholder="ඔබගේ මුරපදය ඇතුළත් කරන්න" required>
                    <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                </div>
                <div class="forgot-password">
                    <a href="forgot_password.php"><i class="fas fa-key"></i> මුරපදය අමතකද?</a>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login වන්න
            </button>
        </form>

        <div class="register-link">
            නව ගිණුමක් අවශ්‍යද? <a href="register.php"><i class="fas fa-user-plus"></i> ලියාපදිංචි වන්න</a>
        </div>

        <div class="back-home">
            <a href="index.php"><i class="fas fa-home"></i> මුල් පිටුවට යන්න</a>
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

        // Password toggle functionality
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle icon
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>