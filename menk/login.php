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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Honeypot check for login
    if (!empty($_POST['website_hp'])) {
        exit("Spam detected.");
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "කරුණාකර ඊමේල් ලිපිනය සහ මුරපදය ඇතුළත් කරන්න.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $userObj = $stmt->fetch();

        if ($userObj && password_verify($password, $userObj['password'])) {
            // Set Session data securely
            $_SESSION['user_id'] = $userObj['id'];
            $_SESSION['user_name'] = $userObj['name'];
            $_SESSION['user_email'] = $userObj['email'];
            $_SESSION['subscription_status'] = $userObj['subscription_status'];
            $_SESSION['plan_type'] = $userObj['plan_type'] ?? 'free';

            $redirect = $_GET['redirect'] ?? 'index.php';
            header("Location: $redirect");
            exit();
        } else {
            $error = "වැරදි ඊමේල් ලිපිනයක් හෝ මුරපදයකි.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log In - Japan Driving License Practice | ලොග් වෙන්න</title>
<meta name="description" content="Log in to your Japan driving license practice account to resume your saved progress and see your dashboard.">
<meta name="robots" content="noindex, follow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600;700&family=Inter:wght@400;500;600;700&family=Noto+Sans+Sinhala:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          ink: '#101A30',
          paper: '#F6F5F1',
          primary: { DEFAULT: '#0F2F6E', light: '#E7ECFA', dark: '#0A2050' },
          accent: { DEFAULT: '#F2A73B', light: '#FDF1DD' },
          go: '#1C8F5A',
          stop: '#D5432E',
        },
        fontFamily: {
          display: ['"Space Grotesk"', 'sans-serif'],
          body: ['"Inter"', '"Noto Sans Sinhala"', 'sans-serif'],
        },
      }
    }
  }
</script>
<style>
  body{ font-family:'Inter','Noto Sans Sinhala',sans-serif; }
  .grid-texture{
    background-image:
      linear-gradient(to right, rgba(15,47,110,0.06) 1px, transparent 1px),
      linear-gradient(to bottom, rgba(15,47,110,0.06) 1px, transparent 1px);
    background-size: 28px 28px;
  }
  *:focus-visible{ outline: 3px solid #F2A73B; outline-offset: 2px; }
  @keyframes riseIn{ from{ opacity:0; transform:translateY(10px);} to{ opacity:1; transform:translateY(0);} }
  .rise-in{ animation: riseIn .35s ease-out both; }
  @media (prefers-reduced-motion: reduce){ .rise-in{ animation:none; } }
</style>
</head>
<body class="bg-paper text-ink font-body min-h-screen flex flex-col">

<header class="bg-primary text-white shadow-lg">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
    <a href="index.php" class="flex items-center gap-2">
      <svg width="28" height="28" viewBox="0 0 48 48" fill="none" aria-hidden="true"><circle cx="24" cy="24" r="21" stroke="#F2A73B" stroke-width="3"/><circle cx="24" cy="24" r="5.5" fill="#F2A73B"/><path d="M24 3v10M24 35v10M3 24h10M35 24h10M9.5 9.5l7 7M31.5 31.5l7 7M9.5 38.5l7-7M31.5 16.5l7-7" stroke="#F2A73B" stroke-width="2.5" stroke-linecap="round"/></svg>
      <span class="font-display font-bold text-sm sm:text-base">Japan License Prep</span>
    </a>
    <a href="register.php" class="text-xs sm:text-sm font-semibold bg-white/10 hover:bg-white/20 text-white px-3 sm:px-4 py-2 rounded-lg transition">Register</a>
  </div>
</header>

<main class="grid-texture flex-grow flex items-center justify-center px-4 py-10 sm:py-16">
  <div class="max-w-md w-full">

    <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8 border border-black/5 rise-in">
      <h1 class="font-display text-2xl font-bold text-ink mb-1">Welcome Back <span class="font-body font-normal text-ink/40">/ පිවිසෙන්න</span></h1>
      <p class="text-ink/50 text-sm mb-6">ඔබගේ ගිණුමට ඇතුළු වී, සුරැකි ප්‍රගතිය සහ පුද්ගලික dashboard එක බලන්න.</p>

      <?php if (!empty($error)): ?>
        <div class="mb-4 p-3 bg-stop/10 border border-stop/30 text-stop rounded-xl text-sm font-medium">
          <?php echo $error; ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-4">
        <!-- Honeypot -->
        <div style="position:absolute; left:-9999px;" aria-hidden="true">
          <label>Website</label>
          <input type="text" name="website_hp" value="" tabindex="-1" autocomplete="off">
        </div>

        <div>
          <label class="block text-xs font-semibold text-ink/60 mb-1" for="email">Email Address / ඊමේල් ලිපිනය</label>
          <input id="email" type="email" name="email" required autofocus
                 class="w-full p-3 bg-paper border border-black/10 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary">
        </div>

        <div>
          <div class="flex justify-between items-center mb-1">
            <label class="text-xs font-semibold text-ink/60" for="password">Password / මුරපදය</label>
            <a href="forgot-password.php" class="text-xs text-primary hover:underline font-medium">Forgot?</a>
          </div>
          <input id="password" type="password" name="password" required
                 class="w-full p-3 bg-paper border border-black/10 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary">
        </div>

        <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white font-semibold py-3.5 rounded-xl transition shadow-lg text-sm mt-2">
          Log In / ඇතුළු වන්න →
        </button>
      </form>

      <p class="text-center text-xs text-ink/50 mt-6">
        Don't have an account? <a href="register.php" class="text-primary font-semibold hover:underline">Register here</a>
      </p>
    </div>

    <p class="text-center text-xs text-ink/40 mt-6">
      🔒 Your details are kept private and never shared.
    </p>
  </div>
</main>

<footer class="text-center py-6 text-xs text-ink/40 border-t border-black/5 bg-white">
  Japan Driving License Practice System &copy; 2026
</footer>

</body>
</html>