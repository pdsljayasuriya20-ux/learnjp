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

$token = $_GET['token'] ?? '';
$error = "";
$success = "";
$validToken = false;

// Token එක වලංගු දැයි සහ කාලය ඉකුත් වී ඇත්දැයි පරීක්ෂා කිරීම
if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expiry > NOW()");
    $stmt->execute([$token]);
    $userObj = $stmt->fetch();

    if ($userObj) {
        $validToken = true;
        $userId = $userObj['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPassword = $_POST['password'] ?? '';

            if (strlen($newPassword) < 6) {
                $error = "නව මුරපදය අකුරු 6කට වඩා වැඩි විය යුතුය.";
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                // මුරපදය Update කර Token එක ඉවත් කිරීම
                $updateStmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
                $updateStmt->execute([$hashedPassword, $userId]);

                $success = "ඔබගේ මුරපදය සාර්ථකව වෙනස් කරන ලදී! දැන් ඔබට නව මුරපදයෙන් ඇතුළු විය හැක.";
                $validToken = false; // Form එක hide කිරීමට
            }
        }
    } else {
        $error = "මෙම මුරපද යළි සැකසීමේ ලින්ක් එක කල් ඉකුත් වී ඇත හෝ වැරදිය.";
    }
} else {
    $error = "වලංගු නොවන ඉල්ලීමකි.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Japan Driving License Practice</title>
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
            <h1 class="text-2xl font-black text-slate-900 mb-2">Set New Password / නව මුරපදයක් සකසන්න</h1>
            <p class="text-slate-500 text-sm mb-6">ඔබගේ ගිණුම සඳහා නව මුරපඩයක් ඇතුළත් කරන්න.</p>

            <?php if (!empty($success)): ?>
                <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-medium leading-relaxed">
                    <?php echo $success; ?>
                    <div class="mt-4">
                        <a href="login.php" class="inline-block bg-emerald-600 text-white px-4 py-2 rounded-lg text-xs font-semibold hover:bg-emerald-700 transition">Login Now →</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($error) && !$validToken): ?>
                <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm font-medium">
                    <?php echo $error; ?>
                    <div class="mt-4">
                        <a href="forgot-password.php" class="text-xs text-indigo-600 font-semibold hover:underline">Request new reset link →</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($validToken): ?>
                <?php if (!empty($error)): ?>
                    <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm font-medium">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">New Password / නව මුරපදය:</label>
                        <input type="password" name="password" required class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3.5 rounded-xl transition shadow-lg text-sm mt-2">
                        Update Password / මුරපදය යාවත්කාලීන කරන්න →
                    </button>
                </form>
            <?php endif; ?>

        </div>
    </main>

    <footer class="text-center py-6 text-xs text-slate-400 border-t bg-white">
        Japan Driving License Practice System &copy; 2026
    </footer>

</body>
</html>