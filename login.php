<?php
require_once 'config.php';
require_once __DIR__ . '/includes/activity.php';

// WhatsApp msg / OTP යවපු තැන (පණිවිඩය යැව්වට පසු)
la_log_activity($uid, 'whatsapp', 'WhatsApp පණිවිඩයක් යැව්වා', 'ලොගින් කේතය යැව්වා');

// Book එකක් ගත්තාම / download කළාම
la_log_activity($uid, 'book', $bookTitle, 'ලබා ගත්තා', $level, $bookId);

// පාඩමක් අවසන් කළාම
la_log_activity($uid, 'lesson', 'Lesson 7 අවසන් කළා', null, 'N2', $lessonId);

// Quiz එක
la_log_activity($uid, 'quiz', 'N2 Quiz', $score . '/' . $total . ' (' . $pct . '%)', 'N2');

// Login (user-login.php success වුණාම)
la_log_activity($uid, 'login', 'ඇතුල් වුණා');

// Production: errors පිටුවේ පෙන්වන්න එපා, log එකට යවන්න
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Already logged in
if (isLoggedIn()) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';
$maxAttempts = 5;
$lockoutTime = 900; // 15 minutes

$ip          = getClientIP();
$attemptsKey = 'login_attempts_' . md5($ip);
$lockoutKey  = 'login_lockout_' . md5($ip);

$locked = isset($_SESSION[$lockoutKey]) && $_SESSION[$lockoutKey] > time();
if ($locked) {
    $mins  = (int) ceil(($_SESSION[$lockoutKey] - time()) / 60);
    $error = "Too many failed attempts. Try again in {$mins} minute(s).";
}

if (!$locked && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password';
    } else {
        try {
            $db   = getDBConnection();
            $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = :username AND is_active = 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Success: session fixation වලින් ආරක්ෂාවට id අලුත් කරනවා
                session_regenerate_id(true);
                unset($_SESSION[$attemptsKey], $_SESSION[$lockoutKey]);

                $_SESSION['admin_id']        = $user['id'];
                $_SESSION['admin_username']  = $user['username'];
                $_SESSION['admin_role']      = $user['role'];
                $_SESSION['admin_name']      = $user['full_name'];
                $_SESSION['admin_logged_in'] = true;

                $stmt = $db->prepare("UPDATE admin_users SET last_login = CURRENT_TIMESTAMP WHERE id = :id");
                $stmt->execute([':id' => $user['id']]);

                $stmt = $db->prepare("INSERT INTO activity_log (admin_id, action, description, ip_address)
                                      VALUES (:admin_id, 'login', 'User logged in', :ip)");
                $stmt->execute([':admin_id' => $user['id'], ':ip' => $ip]);

                header('Location: admin_dashboard.php');
                exit;
            }

            // Failed login
            $_SESSION[$attemptsKey] = ($_SESSION[$attemptsKey] ?? 0) + 1;

            if ($_SESSION[$attemptsKey] >= $maxAttempts) {
                $_SESSION[$lockoutKey] = time() + $lockoutTime;
                $error = 'Too many failed attempts. Locked for 15 minutes.';
            } else {
                $left  = $maxAttempts - $_SESSION[$attemptsKey];
                $error = "Invalid username or password. {$left} attempt(s) remaining.";
            }

            $stmt = $db->prepare("INSERT INTO activity_log (admin_id, action, description, ip_address)
                                  VALUES (NULL, 'failed_login', :desc, :ip)");
            $stmt->execute([
                ':desc' => 'Failed login attempt for username: ' . mb_substr($username, 0, 60),
                ':ip'   => $ip
            ]);
        } catch (PDOException $e) {
            error_log('Login Error: ' . $e->getMessage());
            $error = 'Login failed. Please try again.';
        }
    }
}

$loggedOut = isset($_GET['logged_out']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login - Learn Japanese</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: #0a0a0a;
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .animated-bg {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;
            background: radial-gradient(circle at 20% 80%, #ff006e 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, #8338ec 0%, transparent 50%);
            animation: bgShift 10s ease-in-out infinite;
        }

        @keyframes bgShift {
            0%, 100% { filter: hue-rotate(0deg); }
            50% { filter: hue-rotate(180deg); }
        }

        .login-container {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 50px;
            width: 90%;
            max-width: 450px;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        }

        .logo {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(45deg, #ff006e, #8338ec);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .subtitle { text-align: center; opacity: 0.7; margin-bottom: 40px; font-size: 1.1rem; }

        .form-group { margin-bottom: 25px; }

        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #ff006e; }

        .form-input {
            width: 100%;
            padding: 15px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            outline: none;
            transition: all 0.3s ease;
        }

        .form-input:focus { border-color: #ff006e; background: rgba(255,255,255,0.15); }

        .error-message {
            background: rgba(255,0,0,0.2);
            border: 1px solid rgba(255,0,0,0.4);
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 20px;
            color: #ff6b6b;
            text-align: center;
        }

        .success-message {
            background: rgba(0,255,0,0.2);
            border: 1px solid rgba(0,255,0,0.4);
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 20px;
            color: #6bff6b;
            text-align: center;
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #ff006e, #8338ec);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .login-btn:hover { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(255,0,110,0.4); }

        .back-link { text-align: center; margin-top: 25px; }
        .back-link a { color: #8338ec; text-decoration: none; transition: color 0.3s ease; }
        .back-link a:hover { color: #ff006e; }

        @media (max-width: 768px) { .login-container { padding: 30px; } }
    </style>
</head>
<body>
    <div class="animated-bg"></div>

    <div class="login-container">
        <div class="logo">🔐 Admin</div>
        <p class="subtitle">Learn Japanese Admin Panel</p>

        <?php if ($loggedOut): ?>
            <div class="success-message">✅ You have been logged out successfully</div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message">⚠️ <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-input" placeholder="Enter your username"
                       autocomplete="username" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" placeholder="Enter your password"
                       autocomplete="current-password" required>
            </div>

            <button type="submit" name="login" class="login-btn">Login</button>
        </form>

        <div class="back-link">
            <a href="index.html">← Back to Website</a>
        </div>
    </div>
</body>
</html>