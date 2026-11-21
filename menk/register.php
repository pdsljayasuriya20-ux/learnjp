<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$host = 'localhost';
$db   = 'sampath_learnjp';
$user = 'sampath_drive';
$pass = '6BBT5vGkD0d9';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Honeypot Anti-Spam Check (ਬੋਟස් හඳුනාගැනීම)
    if (!empty($_POST['website_hp'])) {
        // Spam bot detected, silently block without error
        exit("Spam detected.");
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = "කරුණාකර සියලුම විස්තර පුරවන්න.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "වලංගු නොවන ඊමේල් ලිපිනයකි.";
    } elseif (strlen($password) < 6) {
        $error = "මුරපදය අකුරු 6කට වඩා වැඩි විය යුතුය.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "මෙම ඊමේල් ලිපිනය දැනටමත් භාවිතයේ ඇත.";
        } else {
            // Hash password securely
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, subscription_status, created_at) VALUES (?, ?, ?, 'free', NOW())");
            if ($stmt->execute([$name, $email, $hashedPassword])) {
                $success = "ලියාපදිංචිය සාර්ථකයි! දැන් ඔබට Login විය හැක.";
            } else {
                $error = "ලියාපදිංචි වීමේදී දෝෂයක් ඇති විය.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Japan Driving License Practice</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Sinhala:wght@400;500;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', 'Noto Sans Sinhala', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col justify-between">

    <header class="bg-indigo-600 text-white shadow-md">
        <div class="max-w-4xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="index.php" class="text-xl font-bold">Japan Driving License Quiz</a>
            <a href="login.php" class="text-xs bg-indigo-700 hover:bg-indigo-800 text-white px-3 py-2 rounded-lg transition font-medium">Login</a>
        </div>
    </header>

    <main class="max-w-md w-full mx-auto px-4 py-10 flex-grow">
        <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8 border border-slate-100">
            <h1 class="text-2xl font-black text-slate-900 mb-2">Create Account / ගිණුමක් සාදාගන්න</h1>
            <p class="text-slate-500 text-sm mb-6">ප්‍රශ්න පුහුණු වීමට නොමිලේ ලියාපදිංචි වන්න.</p>

            <?php if (!empty($error)): ?>
                <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm font-medium">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-medium">
                    <?php echo $success; ?>
                    <div class="mt-3">
                        <a href="login.php" class="inline-block bg-emerald-600 text-white px-4 py-2 rounded-lg text-xs font-semibold hover:bg-emerald-700 transition">Login Now →</a>
                    </div>
                </div>
            <?php else: ?>

                <form method="POST" class="space-y-4">
                    <!-- Honeypot field (hidden from real users, bots fill this) -->
                    <div style="display:none;">
                        <label>Website</label>
                        <input type="text" name="website_hp" value="">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Full Name / සම්පූර්ණ නම:</label>
                        <input type="text" name="name" required class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Email Address / ඊමේල් ලිපිනය:</label>
                        <input type="email" name="email" required class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Password / මුරපදය:</label>
                        <input type="password" name="password" required class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3.5 rounded-xl transition shadow-lg text-sm mt-2">
                        Register / ලියාපදිංචි වන්න →
                    </button>
                </form>

            <?php endif; ?>

            <p class="text-center text-xs text-slate-500 mt-6">
                Already have an account? <a href="login.php" class="text-indigo-600 font-semibold hover:underline">Login here</a>
            </p>
        </div>
    </main>

    <footer class="text-center py-6 text-xs text-slate-400 border-t bg-white">
        Japan Driving License Practice System &copy; 2026
    </footer>

</body>
</html>