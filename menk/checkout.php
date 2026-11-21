<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header("Location: login.php?redirect=checkout.php");
    exit();
}

$plan = $_GET['plan'] ?? $_POST['plan'] ?? '';
if ($plan !== '6_months' && $plan !== '1_year') {
    header("Location: upgrade.php");
    exit();
}

$planDetails = [
    '6_months' => ['name' => '6 Months Premium Plan', 'price' => '3,500 Yen', 'months' => 6],
    '1_year'   => ['name' => '1 Year Premium Plan', 'price' => '5,000 Yen', 'months' => 12]
];

$selectedPlan = $planDetails[$plan];
$successMessage = "";

// Handle actual payment confirmation / mock gateway success
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    $userId = $_SESSION['user_id'];
    $durationMonths = $selectedPlan['months'];
    $expiryDate = date('Y-m-d H:i:s', strtotime("+$durationMonths months"));

    try {
        $stmt = $pdo->prepare("UPDATE users SET subscription_status = 'active', plan_type = ?, subscription_expiry = ? WHERE id = ?");
        $stmt->execute([$plan, $expiryDate, $userId]);

        $_SESSION['subscription_status'] = 'active';
        $_SESSION['plan_type'] = $plan;
        
        $successMessage = "გෙවීම සාර්ථකයි! ඔබගේ ගිණුම දැන් සක්‍රීය කර ඇත.";
    } catch (\PDOException $e) {
        $error = "ගිණුම යාවත්කාලීන කිරීමේදී දෝෂයක් ඇති විය: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Japan Driving License Practice</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Sinhala:wght@400;500;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', 'Noto Sans Sinhala', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col justify-between">

    <!-- Header -->
    <header class="bg-indigo-600 text-white shadow-md">
        <div class="max-w-4xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="index.php" class="text-xl font-bold">Japan Driving License Quiz</a>
            <a href="dashboard.php" class="text-xs bg-indigo-700 hover:bg-indigo-800 text-white px-3 py-2 rounded-lg transition font-medium">👤 Dashboard</a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-xl w-full mx-auto px-4 py-10 flex-grow">
        
        <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8 border border-slate-100">
            <h1 class="text-2xl font-black text-slate-900 mb-2">Order Summary / ඇණවුම් විස්තරය</h1>
            <p class="text-slate-500 text-sm mb-6">ඔබ තෝරාගත් වාරික සැලැස්ම (Selected Plan) පහත දැක්වේ.</p>

            <?php if (!empty($successMessage)): ?>
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-center font-medium mb-6">
                    <?php echo $successMessage; ?>
                    <div class="mt-4">
                        <a href="index.php" class="inline-block bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-6 py-2.5 rounded-xl text-sm transition shadow-md">
                            Start Practicing Now →
                        </a>
                    </div>
                </div>
            <?php else: ?>

                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 mb-6 space-y-3">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-600 font-medium">Plan Name:</span>
                        <span class="font-bold text-slate-900"><?php echo $selectedPlan['name']; ?></span>
                    </div>
                    <div class="flex justify-between items-center text-sm border-t pt-2">
                        <span class="text-slate-600 font-medium">Total Amount:</span>
                        <span class="font-bold text-indigo-600 text-lg"><?php echo $selectedPlan['price']; ?></span>
                    </div>
                </div>

                <div class="border-t pt-6">
                    <h3 class="font-semibold text-slate-800 mb-3 text-sm">Select Payment Method / ගෙවීම් ක්‍රමය තෝරන්න:</h3>
                    
                    <form method="POST">
                        <input type="hidden" name="plan" value="<?php echo htmlspecialchars($plan); ?>">
                        
                        <div class="space-y-3 mb-6">
                            <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-slate-50 transition">
                                <input type="radio" name="payment_gateway" value="stripe" checked class="text-indigo-600">
                                <span class="text-sm font-medium">Credit / Debit Card (Stripe)</span>
                            </label>
                            <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-slate-50 transition">
                                <input type="radio" name="payment_gateway" value="paypay" class="text-indigo-600">
                                <span class="text-sm font-medium">PayPay (Japan Online Payment)</span>
                            </label>
                        </div>

                        <button type="submit" name="confirm_payment" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3.5 rounded-xl transition shadow-lg text-center block">
                            Proceed to Payment & Activate →
                        </button>
                    </form>
                </div>

            <?php endif; ?>

            <div class="text-center mt-6">
                <a href="upgrade.php" class="text-xs text-slate-500 hover:text-indigo-600 transition">← Change Plan / වෙනත් සැලැස්මක් තෝරන්න</a>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="text-center py-6 text-xs text-slate-400 border-t bg-white">
        Japan Driving License Practice System &copy; 2026
    </footer>

</body>
</html>