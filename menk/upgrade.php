<?php
// Error reporting for debugging (දෝෂ පරීක්ෂා කිරීමට)
error_reporting(E_ALL);
mq: ini_set('display_errors', 1);

session_start();

// Database Configuration
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

$connError = "";
$pdo = null;

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    $connError = "Database Connection Failed: " . $e->getMessage();
}

// Check if user is logged in (ඔබේ Login Session එකේ නම අනුව වෙනස් විය හැක)
$isLoggedIn = isset($_SESSION['user_id']);
$successMessage = "";
$errorMessage = "";

// Handle plan selection / form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isLoggedIn) {
        header("Location: login.php?redirect=upgrade.php");
        exit();
    }

    $plan = $_POST['plan'] ?? '';
    $userId = $_SESSION['user_id'];

    if (($plan === '6_months' || $plan === '1_year') && $pdo) {
        $durationMonths = ($plan === '6_months') ? 6 : 12;
        $expiryDate = date('Y-m-d H:i:s', strtotime("+$durationMonths months"));

        try {
            $stmt = $pdo->prepare("UPDATE users SET subscription_status = 'active', plan_type = ?, subscription_expiry = ? WHERE id = ?");
            $stmt->execute([$plan, $expiryDate, $userId]);

            $_SESSION['subscription_status'] = 'active';
            $_SESSION['plan_type'] = $plan;
            $successMessage = "🎉 ඔබගේ ගිණුම සාර්ථකව Upgrade විය! දැන් ඔබට සියලුම ප්‍රශ්න සහ විශේෂාංග වෙත පිවිසිය හැක.";
        } catch (\PDOException $e) {
            $errorMessage = "ගිණුම Upgrade කිරීමේදී දෝෂයක් ඇති විය: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upgrade to Premium - Japan Driving License Practice</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Sinhala:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', 'Noto Sans Sinhala', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col justify-between">

    <!-- Header -->
    <header class="bg-indigo-600 text-white shadow-md">
        <div class="max-w-4xl mx-auto px-4 py-4 flex justify-between items-center">
            <div>
                <a href="index.php" class="text-xl font-bold">Japan Driving License Quiz</a>
                <p class="text-xs text-indigo-200">ප්‍රිමියම් ගිණුම් ප්‍රවේශය (Upgrade Account)</p>
            </div>
            <div>
                <?php if ($isLoggedIn): ?>
                    <a href="user_dashboard.php" class="text-xs bg-indigo-700 hover:bg-indigo-800 text-white px-3 py-2 rounded-lg transition font-medium">👤 Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="text-xs bg-white text-indigo-700 px-3 py-2 rounded-lg transition font-semibold">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-3xl w-full mx-auto px-4 py-10 flex-grow">
        
        <!-- Heading Section -->
        <div class="text-center mb-10">
            <span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">Unlock Full Access</span>
            <h1 class="text-3xl font-black text-slate-900 mt-3 mb-2">ජපන් රියදුරු බලපත්‍ර විභාගය පළමු වරටම සමත් වන්න</h1>
            <p class="text-slate-600 text-sm max-w-xl mx-auto">ප්‍රශ්න 1000 කට අධික සංඛ්‍යාවක්, ආදර්ශ විභාග (Mock Tests) සහ වැදගත් රීති මාලාවක් සමඟ සම්පූර්ණ ප්‍රවේශය ලබා ගන්න.</p>
        </div>

        <?php if (!empty($connError)): ?>
            <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-center font-medium">
                <?php echo $connError; ?>
            </div>
        <?php endif; ?>

        <!-- Alerts -->
        <?php if (!empty($successMessage)): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-center font-medium">
                <?php echo $successMessage; ?>
                <div class="mt-3">
                    <a href="index.php" class="inline-block bg-emerald-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-emerald-700 transition">Start Practicing Now →</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-center font-medium">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <!-- Pricing Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- 6 Months Plan -->
            <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8 border border-slate-100 flex flex-col justify-between relative overflow-hidden">
                <div>
                    <span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-3 py-1 rounded-full">Most Popular</span>
                    <h3 class="font-bold text-xl text-slate-900 mt-4">6 Months Plan</h3>
                    <p class="text-4xl font-black text-indigo-600 my-3">3,500 <span class="text-base font-normal text-slate-500">Yen</span></p>
                    <p class="text-xs text-slate-500 mb-6">මාස 6ක් පුරා අඛණ්ඩ පුහුණු වීමේ පහසුකම සහ සියලුම ප්‍රශ්න.</p>
                    
                    <ul class="space-y-3 text-sm text-slate-600 mb-8">
                        <li class="flex items-center gap-2">✅ ප්‍රශ්න 1000+ කට සම්පූර්ණ ප්‍රවේශය</li>
                        <li class="flex items-center gap-2">✅ අසීමිත Mock Tests පුහුණුව</li>
                        <li class="flex items-center gap-2">✅ සිංහල සහ ඉංග්‍රීසි භාෂා දෙකෙන්ම</li>
                    </ul>
                </div>

                <form method="POST">
                    <input type="hidden" name="plan" value="6_months">
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl transition shadow-md text-center block">
                        Choose 6 Months Plan →
                    </button>
                </form>
            </div>

            <!-- 1 Year Plan -->
            <div class="bg-gradient-to-br from-indigo-900 to-slate-900 text-white rounded-2xl shadow-xl p-6 sm:p-8 border border-indigo-800 flex flex-col justify-between relative overflow-hidden">
                <div>
                    <span class="bg-emerald-500 text-white text-xs font-bold px-2.5 py-1 rounded-full">Best Value (Save More)</span>
                    <h3 class="font-bold text-xl text-white mt-4">1 Year Plan</h3>
                    <p class="text-4xl font-black text-emerald-300 my-3">5,000 <span class="text-base font-normal text-indigo-200">Yen</span></p>
                    <p class="text-xs text-indigo-200 mb-6">වසරක පූර්ණ ප්‍රවේශය සහ විශේෂ විභාග මඟපෙන්වීම්.</p>
                    
                    <ul class="space-y-3 text-sm text-indigo-100 mb-8">
                        <li class="flex items-center gap-2">✅ වසරක පූර්ණ ප්‍රවේශය (1 Year Access)</li>
                        <li class="flex items-center gap-2">✅ විශේෂ විභාග කෙටි ක්‍රම සහ සටහන්</li>
                        <li class="flex items-center gap-2">✅ ප්‍රමුඛතා පදනම මත සහය</li>
                    </ul>
                </div>

                <form method="POST">
                    <input type="hidden" name="plan" value="1_year">
                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-3 rounded-xl transition shadow-lg text-center block">
                        Choose 1 Year Plan →
                    </button>
                </form>
            </div>

        </div>

        <!-- Back Link -->
        <div class="text-center mt-8">
            <a href="index.php" class="text-sm text-slate-500 hover:text-indigo-600 font-medium transition">← Back to Practice Quiz / මුල් පිටුවට යන්න</a>
        </div>

    </main>

    <!-- Footer -->
    <footer class="text-center py-6 text-xs text-slate-400 border-t bg-white">
        Japan Driving License Practice System &copy; 2026
    </footer>

</body>
</html>