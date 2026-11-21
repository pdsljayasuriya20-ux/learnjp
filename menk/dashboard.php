<?php
session_start();

// Database Connection with your actual credentials
$host = 'localhost';
$db   = 'sampath_learnjp';
$user = 'sampath_drive';
$pass = '6BBT5vGkD0d9';
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; 

// 1. පරිශීලකයාගේ විස්තර ලබා ගැනීම (membership_status සහ subscription_expiry සමඟ)
$stmtUser = $pdo->prepare("SELECT name, membership_status, subscription_expiry FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

$userName = $userData ? $userData['name'] : 'Guest User';
$rawMembership = $userData ? strtolower(trim($userData['membership_status'])) : 'free';
$expiryDate = $userData ? $userData['subscription_expiry'] : null;

// User ID 1 සඳහා Testing පිණිස Free වුවද Full Access ලබා දීම
if ($user_id == 1) {
    $membershipStatus = 'Full Access (Test Mode)';
    $isFullAccess = true;
    $daysRemaining = null;
} else {
    // සාමාන්‍ය පරිශීලකයින් සඳහා පරීක්ෂාව
    if ($rawMembership === 'premium' || $rawMembership === 'paid' || $rawMembership === 'pro') {
        $membershipStatus = 'Premium Plan 🚀';
        $isFullAccess = true;
        
        // දින ගණන ගණනය කිරීම (Countdown)
        if (!empty($expiryDate)) {
            $now = new DateTime();
            $expiryDateTime = new DateTime($expiryDate);
            $interval = $now->diff($expiryDateTime);
            
            if ($now < $expiryDateTime) {
                $daysRemaining = $interval->days;
            } else {
                $daysRemaining = 0;
                $isFullAccess = false; // කාලය ඉකුත් වී නම්
                $membershipStatus = 'Expired';
            }
        } else {
            $daysRemaining = null;
        }
    } else {
        $membershipStatus = 'Free Plan';
        $isFullAccess = false;
        $daysRemaining = null;
    }
}

// 2. සංඛ්‍යාලේඛන (Stats) ලබා ගැනීම
$stmtStats = $pdo->prepare("SELECT COUNT(*) as total_tests, AVG(score) as avg_score FROM quiz_history WHERE user_id = ?");
$stmtStats->execute([$user_id]);
$stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

$totalTests = $stats['total_tests'] ?? 0;
$avgScore = $stats['avg_score'] ? round($stats['avg_score']) . '%' : '0%';

// 3. මෑත කාලීන ඉතිහාසය ලබා ගැනීම
$stmtHistory = $pdo->prepare("SELECT category, score, total_questions, status, test_date FROM quiz_history WHERE user_id = ? ORDER BY test_date DESC LIMIT 5");
$stmtHistory->execute([$user_id]);
$historyRows = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Japan Driving License Practice</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Sinhala:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', 'Noto Sans Sinhala', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col justify-between">

    <!-- Top Navigation Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <span class="bg-indigo-600 text-white font-bold p-2 rounded-xl text-sm">JP</span>
                <span class="font-bold text-slate-800 text-lg">Driving License Portal</span>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-slate-700"><?php echo htmlspecialchars($userName); ?></p>
                    <p class="text-xs text-emerald-600 font-semibold"><?php echo htmlspecialchars($membershipStatus); ?></p>
                </div>
                <a href="logout.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-2 rounded-xl text-sm font-semibold transition">
                    Logout / ඉවත් වන්න
                </a>
            </div>
        </div>
    </header>

    <!-- Main Dashboard Container -->
    <main class="max-w-6xl w-full mx-auto px-4 py-8 flex-grow">
        
        <!-- Welcome Banner with Subscription Countdown -->
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-2xl text-white p-6 sm:p-8 mb-8 shadow-lg flex flex-col md:flex-row justify-between items-center gap-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold mb-2">Welcome back, <?php echo htmlspecialchars(explode(' ', $userName)[0]); ?>! 👋</h1>
                <p class="text-indigo-100 text-sm sm:text-base">Ready to practice for your Japanese driving license written test today? Keep up the good work!</p>
            </div>
            
            <div class="bg-white/10 backdrop-blur-md border border-white/20 p-4 rounded-xl text-center md:text-right w-full md:w-auto">
                <?php if ($isFullAccess): ?>
                    <p class="text-xs text-emerald-300 uppercase tracking-wider font-bold mb-1">Status: Full Access Active ✨</p>
                    <?php if ($user_id == 1): ?>
                        <p class="text-xs text-indigo-200">Admin/Test Full Access Mode</p>
                    <?php elseif (isset($daysRemaining)): ?>
                        <div class="text-sm font-semibold text-white bg-indigo-900/40 px-3 py-1.5 rounded-lg border border-indigo-400/30">
                            ⏳ කාලය ඉතිරි දින: <span class="text-amber-300 font-bold text-base"><?php echo $daysRemaining; ?></span> යි
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-xs text-indigo-200 uppercase tracking-wider font-semibold mb-1">Want full access?</p>
                    <a href="upgrade.php" class="inline-block bg-white text-indigo-700 hover:bg-indigo-50 font-bold px-5 py-2.5 rounded-xl text-sm transition shadow-sm">
                        Upgrade to Premium 🚀
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Stats Grid (Real Data) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4">
                <div class="bg-indigo-50 text-indigo-600 p-4 rounded-xl text-xl font-bold">📊</div>
                <div>
                    <p class="text-xs text-slate-400 font-semibold uppercase">Tests Completed</p>
                    <h3 class="text-2xl font-black text-slate-800"><?php echo $totalTests; ?></h3>
                </div>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4">
                <div class="bg-emerald-50 text-emerald-600 p-4 rounded-xl text-xl font-bold">🎯</div>
                <div>
                    <p class="text-xs text-slate-400 font-semibold uppercase">Average Score</p>
                    <h3 class="text-2xl font-black text-slate-800"><?php echo $avgScore; ?></h3>
                </div>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4">
                <div class="bg-amber-50 text-amber-600 p-4 rounded-xl text-xl font-bold">⏱️</div>
                <div>
                    <p class="text-xs text-slate-400 font-semibold uppercase">Practice System</p>
                    <h3 class="text-2xl font-black text-slate-800">Online</h3>
                </div>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4">
                <div class="bg-rose-50 text-rose-600 p-4 rounded-xl text-xl font-bold">🔥</div>
                <div>
                    <p class="text-xs text-slate-400 font-semibold uppercase">Status</p>
                    <h3 class="text-2xl font-black text-slate-800">Active</h3>
                </div>
            </div>
        </div>

        <!-- Main Actions & Recent History Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Side: Quick Start Practice -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                    <h3 class="font-bold text-slate-900 text-lg mb-4">Start Practicing</h3>
                    <p class="text-xs text-slate-500 mb-6">Jump straight into questions or pick a category to target your weak spots.</p>
                    
                    <div class="space-y-3">
                        <a href="quiz.php" class="block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl transition shadow-sm">
                            Start Mock Test (30 Questions)
                        </a>
                        <a href="quiz.php?category=Road Signs" class="block w-full text-center bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-3 rounded-xl transition">
                            Practice Road Signs Only
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right Side: Recent Quiz History Table (Dynamic from DB) -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-slate-900 text-lg">Recent Test History</h3>
                        <span class="text-xs text-slate-400 font-semibold">Latest Sessions</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-100 text-xs text-slate-400 uppercase font-semibold">
                                    <th class="pb-3">Date</th>
                                    <th class="pb-3">Category</th>
                                    <th class="pb-3">Score</th>
                                    <th class="pb-3 text-right">Result</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <?php if (count($historyRows) > 0): ?>
                                    <?php foreach ($historyRows as $row): ?>
                                        <tr>
                                            <td class="py-3 text-slate-600"><?php echo date('Y-m-d', strtotime($row['test_date'])); ?></td>
                                            <td class="py-3 font-medium text-slate-800"><?php echo htmlspecialchars($row['category']); ?></td>
                                            <td class="py-3 font-bold text-slate-900"><?php echo $row['score'] . ' / ' . $row['total_questions']; ?></td>
                                            <td class="py-3 text-right">
                                                <?php if ($row['status'] === 'Passed'): ?>
                                                    <span class="bg-emerald-50 text-emerald-700 font-semibold px-2.5 py-1 rounded-full text-xs">Passed</span>
                                                <?php else: ?>
                                                    <span class="bg-rose-50 text-rose-700 font-semibold px-2.5 py-1 rounded-full text-xs">Failed</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="py-6 text-center text-slate-400">No test history found yet. Start your first quiz!</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </main>

    <!-- Footer -->
    <footer class="text-center py-6 text-xs text-slate-400 border-t border-slate-200 mt-10">
        Japan Driving License Practice System &copy; 2026
    </footer>

</body>
</html>