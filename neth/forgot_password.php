<?php
session_start();
include('db_connection.php');

$message = '';
$messageType = '';
$validToken = false;
$token = '';

// Check if token is provided
if(isset($_GET['token'])){
    $token = $_GET['token'];
    
    // Validate token (using token_expiry as per your schema)
    $stmt = $conn->prepare("SELECT id, name, email, token_expiry FROM client WHERE reset_token=? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 1){
        $user = $result->fetch_assoc();
        
        // Check if token is not expired
        if(strtotime($user['token_expiry']) >= time()){
            $validToken = true;
        } else {
            $message = "මුරපද යළි පිහිටුවීමේ link එක කල් ඉකුත් වී ඇත. කරුණාකර නව link එකක් ඉල්ලන්න.";
            $messageType = 'error';
        }
    } else {
        $message = "අවලංගු හෝ කල් ඉකුත් වූ link එකක්.";
        $messageType = 'error';
    }
    $stmt->close();
}

// Handle password reset
if($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken){
    // CSRF Protection
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        $message = "අවලංගු ඉල්ලීමක්. නැවත උත්සාහ කරන්න.";
        $messageType = 'error';
    } else {
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Validation
        if(strlen($password) < 6){
            $message = "මුරපදය අකුරු 6කට වඩා විශාල විය යුතුය.";
            $messageType = 'error';
        } elseif($password !== $confirmPassword){
            $message = "මුරපද ගැලපෙන්නේ නැත.";
            $messageType = 'error';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password and clear reset token (using token_expiry)
            $stmt = $conn->prepare("UPDATE client SET password=?, reset_token=NULL, token_expiry=NULL WHERE reset_token=?");
            $stmt->bind_param("ss", $hashedPassword, $token);
            
            if($stmt->execute()){
                $message = "මුරපදය සාර්ථකව යළි පිහිටුවන ලදී! දැන් Login වන්න.";
                $messageType = 'success';
                $validToken = false; // Hide form
                header("refresh:2; url=login.php");
            } else {
                $message = "මුරපදය යළි පිහිටුවීම අසාර්ථක විය. නැවත උත්සාහ කරන්න.";
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

$conn->close();
?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>මුරපදය යළි පිහිටුවීම - නෙත්සිත ජෝතිශ්‍ය සේවය</title>
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

        /* Reset Container */
        .reset-container {
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

        .reset-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .reset-icon {
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

        input[type="password"]:focus {
            outline: none;
            border-color: #daa520;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 15px rgba(218, 165, 32, 0.3);
        }

        input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

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
        .btn-reset {
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

        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(218, 165, 32, 0.4);
        }

        .btn-reset:active {
            transform: translateY(0);
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

        /* Password Strength Indicator */
        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
        }

        .strength-bar.weak { width: 33%; background: #ff6b6b; }
        .strength-bar.medium { width: 66%; background: #ffd93d; }
        .strength-bar.strong { width: 100%; background: #6bcf7f; }

        .strength-text {
            font-size: 12px;
            margin-top: 5px;
            color: #b8a7d4;
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            .reset-container {
                padding: 30px 20px;
            }

            h2 {
                font-size: 24px;
            }

            .reset-icon {
                font-size: 50px;
            }
        }
    </style>
</head>
<body>
    <!-- Cosmic Background -->
    <div class="stars" id="stars"></div>

    <div class="reset-container">
        <div class="reset-header">
            <div class="reset-icon">
                <i class="fas fa-lock"></i>
            </div>
            <h2>නව මුරපදයක් සාදන්න</h2>
            <p class="subtitle">ඔබගේ ගිණුම සඳහා ශක්තිමත් මුරපදයක් තෝරන්න</p>
        </div>

        <?php if($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'error' ? 'exclamation-circle' : 'check-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if($validToken): ?>
            <form method="POST" action="" id="resetForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="form-group">
                    <label for="password">නව මුරපදය</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" 
                               placeholder="අකුරු 6ක් හෝ වැඩි ගණනක්" 
                               required minlength="6">
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                    <div class="password-strength">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="strength-text" id="strengthText"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">මුරපදය තහවුරු කරන්න</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="මුරපදය නැවත ඇතුළත් කරන්න" 
                               required minlength="6">
                        <i class="fas fa-eye password-toggle" id="toggleConfirmPassword"></i>
                    </div>
                </div>

                <button type="submit" class="btn-reset">
                    <i class="fas fa-check-circle"></i> මුරපදය යළි පිහිටුවන්න
                </button>
            </form>
        <?php endif; ?>

        <div class="back-link">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Login පිටුවට යන්න</a>
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

        // Password visibility toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const confirmPasswordInput = document.getElementById('confirm_password');

        if(togglePassword){
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }

        if(toggleConfirmPassword){
            toggleConfirmPassword.addEventListener('click', function() {
                const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPasswordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }

        // Password strength checker
        if(passwordInput){
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const strengthBar = document.getElementById('strengthBar');
                const strengthText = document.getElementById('strengthText');
                
                let strength = 0;
                if(password.length >= 6) strength++;
                if(password.length >= 10) strength++;
                if(/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
                if(/\d/.test(password)) strength++;
                if(/[^a-zA-Z\d]/.test(password)) strength++;
                
                strengthBar.className = 'strength-bar';
                if(strength <= 2) {
                    strengthBar.classList.add('weak');
                    strengthText.textContent = 'දුර්වල මුරපදයකි';
                } else if(strength <= 3) {
                    strengthBar.classList.add('medium');
                    strengthText.textContent = 'මධ්‍යම මුරපදයකි';
                } else {
                    strengthBar.classList.add('strong');
                    strengthText.textContent = 'ශක්තිමත් මුරපදයකි';
                }
            });
        }

        // Form validation
        const resetForm = document.getElementById('resetForm');
        if(resetForm){
            resetForm.addEventListener('submit', function(e) {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if(password !== confirmPassword) {
                    e.preventDefault();
                    alert('මුරපද ගැලපෙන්නේ නැත!');
                }
            });
        }
    </script>
</body>
</html>