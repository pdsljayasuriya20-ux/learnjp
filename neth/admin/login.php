<?php
// Session ආරම්භ කිරීම
session_start();

// දත්ත සමුදා සම්බන්ධතාවය
include('../db_connection.php');

if (!isset($conn) || $conn->connect_error) {
    die("දත්ත සමුදාය සම්බන්ධ කිරීමේ දෝෂය: " . $conn->connect_error);
}

// දැනටමත් ලොග් වී ඇත්නම් dashboard වෙත යොමු කරන්න
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error_message = '';
$success_message = '';

// මුරපදය වෙනස් කිරීමෙන් පසු සාර්ථක පණිවිඩය
if (isset($_GET['success']) && $_GET['success'] === 'password_changed') {
    $success_message = 'ඔබගේ මුරපදය සාර්ථකව වෙනස් කරන ලදී. කරුණාකර නව මුරපදය භාවිතා කර පිවිසෙන්න.';
}

// Form Submit කර ඇත්දැයි පරීක්ෂා කිරීම
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // CSRF Token verification
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = 'අවලංගු ඉල්ලීමක්. නැවත උත්සාහ කරන්න.';
    } else {
        // දත්ත සමුදායෙන් පරිශීලකයා සොයා ගැනීම
        $sql = "SELECT id, username, password_hash FROM admins WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // මුරපදය පරීක්ෂා කිරීම
            if (password_verify($password, $user['password_hash'])) {
                // Session සකස් කිරීම
                session_regenerate_id(true); // Security: Session fixation වළක්වයි
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];

                // Dashboard වෙත යොමු කිරීම
                header('Location: dashboard.php');
                exit;
            } else {
                $error_message = 'වැරදි පරිශීලක නාමය හෝ මුරපදය.';
            }
        } else {
            $error_message = 'වැරදි පරිශීලක නාමය හෝ මුරපදය.';
        }
        $stmt->close();
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>පරිපාලක පිවිසුම - නෙත්සිත ජෝතිශ්‍ය සේවය</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@300;400;500;600;700&display=swap" rel="stylesheet">
     <link rel="stylesheet" href="css-main.css">
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
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Cosmic Background */
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
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.2); }
        }

        /* Zodiac Symbols */
        .zodiac-symbols {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 1;
        }

        .zodiac-symbol {
            position: absolute;
            font-size: 40px;
            opacity: 0.05;
            animation: float 20s infinite ease-in-out;
            color: #ffd700;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            25% { transform: translateY(-20px) rotate(5deg); }
            50% { transform: translateY(0) rotate(0deg); }
            75% { transform: translateY(20px) rotate(-5deg); }
        }

        /* Login Container */
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        .login-container {
            background: rgba(45, 27, 78, 0.95);
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            animation: slideIn 0.6s ease-out;
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

        /* Logo */
        .login-logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            animation: pulse 2s infinite;
            border: 5px solid rgba(218, 165, 32, 0.3);
            box-shadow: 0 0 30px rgba(218, 165, 32, 0.5);
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .login-title {
            color: #daa520;
            text-align: center;
            margin-bottom: 10px;
            font-weight: 700;
            font-size: 28px;
        }

        .login-subtitle {
            color: #b8a7d4;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }

        /* Alert Messages */
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.5);
            color: #90ee90;
        }

        .alert-danger {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.5);
            color: #ff6b6b;
        }

        /* Form Groups */
        .form-label {
            color: #daa520;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .input-group {
            margin-bottom: 20px;
            position: relative;
        }

        .input-group-text {
            background: rgba(218, 165, 32, 0.2);
            border: 2px solid rgba(218, 165, 32, 0.3);
            color: #daa520;
            border-right: none;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-left: none;
            color: white;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #daa520;
            box-shadow: 0 0 15px rgba(218, 165, 32, 0.3);
            color: white;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        /* Password Toggle */
        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #daa520;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
        }

        .password-toggle:hover {
            color: #ffd700;
        }

        /* Submit Button */
        .btn-primary {
            background: linear-gradient(135deg, #daa520 0%, #b8860b 100%);
            border: none;
            color: #1a0033;
            padding: 12px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary span {
            position: relative;
            z-index: 1;
        }

        .btn-primary::before {
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

        .btn-primary:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(218, 165, 32, 0.4);
        }

        /* Back to Website Link */
        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #b8a7d4;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-link a:hover {
            color: #daa520;
        }

        .back-link a i {
            transition: all 0.3s ease;
        }

        .back-link a:hover i {
            transform: translateX(-5px);
        }

        /* Remember Me Checkbox */
        .form-check {
            margin-bottom: 20px;
        }

        .form-check-input {
            background-color: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(218, 165, 32, 0.3);
        }

        .form-check-input:checked {
            background-color: #daa520;
            border-color: #daa520;
        }

        .form-check-label {
            color: #b8a7d4;
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .login-container {
                padding: 30px 20px;
            }

            .login-logo {
                width: 80px;
                height: 80px;
                font-size: 2.5rem;
            }

            .login-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Cosmic Background Stars -->
    <div class="stars" id="stars"></div>

    <!-- Zodiac Symbols -->
    <div class="zodiac-symbols">
        <div class="zodiac-symbol" style="top: 10%; left: 10%;">♈</div>
        <div class="zodiac-symbol" style="top: 20%; right: 15%;">♉</div>
        <div class="zodiac-symbol" style="bottom: 20%; left: 15%;">♊</div>
        <div class="zodiac-symbol" style="bottom: 15%; right: 10%;">♋</div>
    </div>

    <!-- Login Wrapper -->
    <div class="login-wrapper">
        <div class="login-container">
            <!-- Logo -->
            <div class="login-logo">
                <i class="fas fa-user-shield"></i>
            </div>

            <!-- Title -->
            <h3 class="login-title">පරිපාලක පිවිසුම</h3>
            <p class="login-subtitle">නෙත්සිත ජෝතිශ්‍ය සේවය - Admin Panel</p>

            <!-- Success Message -->
            <?php if ($success_message): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="login.php">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <!-- Username -->
                <div class="mb-3">
                    <label for="username" class="form-label">පරිශීලක නාමය</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="admin" required autofocus>
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label for="password" class="form-label">මුරපදය</label>
                    <div class="input-group password-wrapper">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="••••••••" required>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label" for="remember">
                        මා මතක තබා ගන්න
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary w-100">
                    <span>
                        <i class="fas fa-sign-in-alt me-2"></i>පිවිසෙන්න
                    </span>
                </button>
            </form>

            <!-- Back to Website Link -->
            <div class="back-link">
                <a href="../index.php">
                    <i class="fas fa-arrow-left"></i>
                    <span>වෙබ් අඩවියට ආපසු යන්න</span>
                </a>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
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

        // Auto-hide success message after 5 seconds
        const successAlert = document.querySelector('.alert-success');
        if(successAlert) {
            setTimeout(() => {
                successAlert.style.transition = 'opacity 0.5s ease';
                successAlert.style.opacity = '0';
                setTimeout(() => {
                    successAlert.remove();
                }, 500);
            }, 5000);
        }

        // Form validation feedback
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;

            if(!username || !password) {
                e.preventDefault();
                alert('කරුණාකර සියලුම ක්ෂේත්‍ර පුරවන්න.');
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>