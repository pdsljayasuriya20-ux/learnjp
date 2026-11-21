<?php
session_start();

/*
  Pull the real logged-in user (set by login.php) and, if we have DB
  credentials handy, refresh a couple of fields (plan/subscription) so the
  dashboard always reflects the latest state rather than a stale session.
*/
$sessionUser = null;

if (!empty($_SESSION['user_id'])) {
    $sessionUser = [
        'id'      => $_SESSION['user_id'],
        'name'    => $_SESSION['user_name'] ?? '',
        'email'   => $_SESSION['user_email'] ?? '',
        'plan'    => $_SESSION['plan_type'] ?? 'free',
        'status'  => $_SESSION['subscription_status'] ?? 'inactive',
        'joined'  => null,
    ];

    // Best-effort refresh from the DB (kept in its own try/catch so a schema
    // mismatch never breaks the page — the session values above still work).
    try {
        $host = 'localhost';
        $db   = 'menkyoo_menkyoo';
        $user = 'menkyoo_sampath';
        $pass = 'qFeMvOx0eXmK';
        $dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        $pdo  = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$sessionUser['id']]);
        $row = $stmt->fetch();

        if ($row) {
            $sessionUser['name']   = $row['name'] ?? $sessionUser['name'];
            $sessionUser['email']  = $row['email'] ?? $sessionUser['email'];
            $sessionUser['plan']   = $row['plan_type'] ?? $sessionUser['plan'];
            $sessionUser['status'] = $row['subscription_status'] ?? $sessionUser['status'];
            if (!empty($row['created_at'])) {
                $sessionUser['joined'] = substr($row['created_at'], 0, 10);
            }
        }
    } catch (\Throwable $e) {
        // Ignore — fall back to session-only data above.
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Japan Driving License Practice Test (Sinhala) | Free Mock Exam — ජපන් රියදුරු බලපත්‍ර පුහුණු විභාගය</title>
<meta name="description" content="Pass Japan's driving license theory test on your first try. 1000+ bilingual (English / Sinhala) True-False questions with instant explanations, category practice and mock exams. Try 30 questions free.">
<meta name="keywords" content="japan driving license test sinhala, jp driving license mock exam, gimen test japan, sri lanka japan license practice, 学科試験 シンハラ語, japan license theory test">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://your-domain.example/">
<meta property="og:type" content="website">
<meta property="og:title" content="Japan Driving License Practice Test — ජපන් රියදුරු බලපත්‍ර පුහුණු විභාගය">
<meta property="og:description" content="Bilingual (English/Sinhala) practice tests for Japan's driving license theory exam. 1000+ questions, instant explanations, progress tracking.">
<meta property="og:locale" content="en_US">
<meta property="og:locale:alternate" content="si_LK">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#0F2F6E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=Noto+Sans+Sinhala:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          ink:      '#101A30',
          paper:    '#F6F5F1',
          primary:  { DEFAULT: '#0F2F6E', light: '#E7ECFA', dark: '#0A2050' },
          accent:   { DEFAULT: '#F2A73B', light: '#FDF1DD' },
          go:       '#1C8F5A',
          stop:     '#D5432E',
        },
        fontFamily: {
          display: ['"Space Grotesk"', 'sans-serif'],
          body: ['"Inter"', '"Noto Sans Sinhala"', 'sans-serif'],
          si: ['"Noto Sans Sinhala"', 'sans-serif'],
          mono: ['"JetBrains Mono"', 'monospace'],
        },
      }
    }
  }
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Course",
  "name": "Japan Driving License Theory Test Practice",
  "description": "Bilingual English/Sinhala mock exam platform for Japan's driving license theory (gakka) test, with 1000+ practice questions and instant explanations.",
  "provider": { "@type": "Organization", "name": "JP License Prep", "sameAs": "https://your-domain.example/" },
  "inLanguage": ["en", "si"],
  "hasCourseInstance": { "@type": "CourseInstance", "courseMode": "online", "courseWorkload": "PT30M" }
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {"@type":"Question","name":"Is the Japan driving license practice test free?","acceptedAnswer":{"@type":"Answer","text":"Yes. A 30-question mock exam is free for every visitor. Full access to 1000+ questions and category-wise practice is available on paid plans."}},
    {"@type":"Question","name":"Are the questions available in Sinhala?","acceptedAnswer":{"@type":"Answer","text":"Every question and explanation is written in both English and Sinhala so you can switch languages instantly while practicing."}},
    {"@type":"Question","name":"Does the app save my progress?","acceptedAnswer":{"@type":"Answer","text":"Yes. Your test progress and history are saved automatically so you can pick up where you left off and track your improvement over time."}}
  ]
}
</script>
<style>
  :root{ color-scheme: light; }
  html{ scroll-behavior: smooth; }
  body{ background:#F6F5F1; }
  .font-si, [data-si]{ font-family:'Noto Sans Sinhala', sans-serif; }

  /* Signature texture — genkouyoushi (manuscript grid) motif, used sparingly */
  .grid-texture{
    background-image:
      linear-gradient(to right, rgba(15,47,110,0.06) 1px, transparent 1px),
      linear-gradient(to bottom, rgba(15,47,110,0.06) 1px, transparent 1px);
    background-size: 28px 28px;
  }

  .skip-link{ position:absolute; left:-999px; top:0; background:#0F2F6E; color:#fff; padding:.75rem 1rem; border-radius:0 0 .5rem 0; z-index:100; }
  .skip-link:focus{ left:0; }

  *:focus-visible{ outline: 3px solid #F2A73B; outline-offset: 2px; }

  @keyframes riseIn{ from{ opacity:0; transform:translateY(10px);} to{ opacity:1; transform:translateY(0);} }
  .rise-in{ animation: riseIn .35s ease-out both; }
  @media (prefers-reduced-motion: reduce){ .rise-in{ animation:none; } html{scroll-behavior:auto;} }

  /* License card — signature dashboard element */
  .license-card{
    position:relative; overflow:hidden; border-radius:1.1rem;
    background: linear-gradient(135deg, #0F2F6E 0%, #16407F 55%, #0A2050 100%);
    color:#fff; padding:1.35rem 1.5rem;
    box-shadow: 0 18px 40px -18px rgba(15,47,110,.6);
  }
  .license-card::before{
    content:""; position:absolute; inset:0;
    background-image: linear-gradient(115deg, transparent 40%, rgba(255,255,255,.10) 50%, transparent 60%);
  }
  .license-chip{
    width:34px; height:24px; border-radius:5px;
    background: linear-gradient(135deg,#F2A73B,#D98A1E);
    box-shadow: inset 0 0 0 1px rgba(0,0,0,.15);
    position:relative;
  }
  .license-chip::after{
    content:""; position:absolute; inset:4px;
    background-image: repeating-linear-gradient(180deg, rgba(0,0,0,.18) 0 2px, transparent 2px 5px);
    border-radius:2px;
  }
  .barcode{
    height:22px;
    background-image: repeating-linear-gradient(90deg, rgba(255,255,255,.9) 0 2px, transparent 2px 4px, rgba(255,255,255,.55) 4px 5px, transparent 5px 7px);
    opacity:.85; border-radius:2px;
  }

  /* Segmented exam progress bar */
  .seg-track{ display:flex; gap:3px; }
  .seg{ flex:1; height:6px; border-radius:3px; background:#E4E2DA; transition: background .25s ease; }
  .seg.filled{ background:#0F2F6E; }
  .seg.current{ background:#F2A73B; }

  .btn-primary{ background:#0F2F6E; }
  .btn-primary:hover{ background:#0A2050; }

  dialog::backdrop{ background: rgba(16,26,48,.55); }
  dialog{ border:none; padding:0; border-radius: 1rem; }
</style>
</head>
<body class="bg-paper text-ink font-body min-h-screen flex flex-col">
<script>window.__USER__ = <?php echo $sessionUser ? json_encode($sessionUser, JSON_UNESCAPED_UNICODE) : 'null'; ?>;</script>
<a href="#main-content" class="skip-link font-semibold text-sm">Skip to content</a>

<!-- ============ NAV ============ -->
<header class="sticky top-0 z-40 bg-primary text-white shadow-lg shadow-primary/10">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-3">
    <a href="#main-content" class="flex items-center gap-2 shrink-0">
      <svg width="30" height="30" viewBox="0 0 48 48" fill="none" aria-hidden="true"><circle cx="24" cy="24" r="21" stroke="#F2A73B" stroke-width="3"/><circle cx="24" cy="24" r="5.5" fill="#F2A73B"/><path d="M24 3v10M24 35v10M3 24h10M35 24h10M9.5 9.5l7 7M31.5 31.5l7 7M9.5 38.5l7-7M31.5 16.5l7-7" stroke="#F2A73B" stroke-width="2.5" stroke-linecap="round"/></svg>
      <span class="font-display font-bold leading-tight text-sm sm:text-base" id="header-title">Japan License Prep</span>
    </a>

    <div class="flex items-center gap-2 sm:gap-3">
      <div class="bg-white/10 p-1 rounded-lg flex text-xs sm:text-sm">
        <button id="btn-en" onclick="setLanguage('en')" class="px-2.5 sm:px-3 py-1 rounded-md bg-white text-primary font-semibold transition">EN</button>
        <button id="btn-si" onclick="setLanguage('si')" class="px-2.5 sm:px-3 py-1 rounded-md text-white font-semibold transition font-si">සිං</button>
      </div>

      <div id="nav-guest">
        <a href="login.php?redirect=index.php" class="text-xs sm:text-sm font-semibold bg-accent text-ink px-3 sm:px-4 py-2 rounded-lg hover:brightness-95 transition inline-block">
          <span id="login-btn-text">Log in</span>
        </a>
      </div>
      <div id="nav-user" class="hidden items-center gap-2">
        <a href="dashboard.php" class="hidden sm:inline-flex text-sm font-semibold px-3 py-2 rounded-lg hover:bg-white/10 transition">Dashboard</a>
        <div class="w-9 h-9 rounded-full bg-accent text-ink font-display font-bold flex items-center justify-center" id="nav-avatar">U</div>
      </div>
    </div>
  </div>
</header>

<main id="main-content" class="flex-grow w-full">

  <!-- ============ GUEST / QUIZ VIEW ============ -->
  <section id="view-guest">

    <!-- Hero -->
    <div id="hero" class="grid-texture border-b border-black/5">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 pt-10 sm:pt-16 pb-10 sm:pb-14 text-center">
        <span class="inline-block text-[11px] font-bold tracking-widest uppercase bg-accent-light text-accent-dark text-primary px-3 py-1 rounded-full border border-accent/40 mb-4" data-en="Free 30-question mock exam" data-si="නොමිලේ ප්‍රශ්න 30ක පරීක්ෂණය">Free 30-question mock exam</span>
        <h1 class="font-display font-bold text-3xl sm:text-4xl md:text-5xl text-ink leading-tight" id="hero-title">
          Pass Japan's driving license test — practiced in English &amp; Sinhala
        </h1>
        <p class="mt-4 text-sm sm:text-base text-ink/60 max-w-2xl mx-auto" id="hero-sub" data-si="සත්‍ය විභාග ආකෘතියේ True/False ප්‍රශ්න, ක්ෂණික පැහැදිලි කිරීම් සහ ඔබේ ප්‍රගතිය සුරැකෙන පුහුණු වේදිකාව.">
          Real exam-style True/False questions, instant explanations, and a practice platform that remembers your progress.
        </p>
        <div class="mt-7 flex flex-wrap items-center justify-center gap-6 sm:gap-10 text-center">
          <div><p class="font-mono font-bold text-xl sm:text-2xl text-primary">1000+</p><p class="text-[11px] text-ink/50 font-semibold" data-en="Questions" data-si="ප්‍රශ්න">Questions</p></div>
          <div><p class="font-mono font-bold text-xl sm:text-2xl text-primary">EN / SI</p><p class="text-[11px] text-ink/50 font-semibold" data-en="Bilingual" data-si="භාෂා දෙකෙන්">Bilingual</p></div>
          <div><p class="font-mono font-bold text-xl sm:text-2xl text-primary">100%</p><p class="text-[11px] text-ink/50 font-semibold" data-en="Free to start" data-si="ආරම්භය නොමිලේ">Free to start</p></div>
        </div>
      </div>
    </div>

    <div class="max-w-2xl w-full mx-auto px-4 sm:px-6 py-8">

      <!-- Resume banner (guest) -->
      <div id="guest-resume-banner" class="hidden mb-6 bg-accent-light border border-accent/40 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 rise-in">
        <p class="text-sm font-semibold text-ink" id="guest-resume-text">You have a test in progress — continue?</p>
        <div class="flex gap-2 shrink-0">
          <button onclick="resumeSavedProgress()" class="text-sm font-bold bg-ink text-white px-4 py-2 rounded-lg" data-en="Resume →" data-si="දිගටම →">Resume →</button>
          <button onclick="discardSavedProgress()" class="text-sm font-semibold text-ink/60 px-3 py-2" data-en="Discard" data-si="ඉවත් කරන්න">Discard</button>
        </div>
      </div>

      <!-- Category Selector -->
      <div id="filter-box" class="mb-6 bg-white p-4 rounded-2xl shadow-sm border border-black/5">
        <label id="cat-label" class="block text-sm font-semibold text-ink/60 mb-2">Select Category / මාතෘකාව හෝ පරිච්ඡේදය තෝරන්න:</label>
        <select id="category-select" onchange="changeCategory()" class="w-full p-3 bg-paper border border-black/10 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-primary font-medium text-ink">
          <option value="All">All Categories / සියලුම ප්‍රශ්න (Mock Exam)</option>
        </select>
      </div>

      <!-- Loading -->
      <div id="loading-box" class="text-center py-20">
        <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-primary border-t-transparent"></div>
        <p class="mt-4 text-ink/60 font-medium" data-en="Loading questions…" data-si="ප්‍රශ්න පූරණය වෙමින්…">Loading questions…</p>
      </div>

      <!-- Quiz Box -->
      <div id="quiz-box" class="hidden bg-white rounded-2xl shadow-xl p-5 sm:p-8 border border-black/5 rise-in">

        <div class="flex justify-between items-center mb-3 text-sm font-medium text-ink/50">
          <span id="progress-text" class="font-mono">Question 1 of 30</span>
          <span class="bg-primary-light text-primary px-3 py-1 rounded-full text-xs font-bold font-mono">Score: <span id="score-val">0</span></span>
        </div>
        <div class="seg-track mb-6" id="seg-track" aria-hidden="true"></div>

        <div id="image-container" class="mb-6 hidden text-center">
          <img id="question-img" src="" alt="Illustration for the current practice question" class="max-h-64 mx-auto rounded-lg border shadow-sm" loading="lazy">
        </div>

        <div class="mb-8">
          <h2 id="question-text" class="text-lg sm:text-xl font-semibold text-ink leading-relaxed"></h2>
        </div>

        <div id="options-box" class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-2" role="group" aria-label="Answer options">
          <button onclick="handleAnswer(1)" class="flex items-center justify-center gap-3 p-4 rounded-xl border-2 border-go/30 bg-go/5 hover:bg-go/10 text-go font-bold transition text-lg shadow-sm active:scale-[0.98]">
            <span id="true-label">正 — True (හරි / ◯)</span>
          </button>
          <button onclick="handleAnswer(0)" class="flex items-center justify-center gap-3 p-4 rounded-xl border-2 border-stop/30 bg-stop/5 hover:bg-stop/10 text-stop font-bold transition text-lg shadow-sm active:scale-[0.98]">
            <span id="false-label">誤 — False (වැරදියයි / ✕)</span>
          </button>
        </div>

        <div id="explanation-box" class="hidden mt-4 p-4 rounded-xl bg-paper border border-black/10 rise-in" role="status" aria-live="polite">
          <div id="result-badge" class="font-bold text-sm mb-1"></div>
          <p id="explanation-text" class="text-sm text-ink/70 leading-relaxed"></p>
          <button onclick="nextQuestion()" class="mt-4 w-full btn-primary text-white font-semibold py-3 rounded-xl transition shadow-md">
            <span id="next-btn-text">Next Question →</span>
          </button>
        </div>
      </div>

      <!-- Completion -->
      <div id="complete-box" class="hidden space-y-6">

        <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8 border border-black/5 text-center rise-in">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-light text-primary rounded-full text-2xl mb-4">📊</div>
          <h2 class="text-2xl font-bold text-ink mb-1 font-display" data-en="Test Completed!" data-si="පරීක්ෂණය අවසන්!">Test Completed!</h2>
          <p class="text-sm text-ink/50 mb-6" data-en="Here's how you did on this free practice set." data-si="ඔබ නොමිලේ ලබා දෙන ප්‍රශ්නාවලිය සාර්ථකව අවසන් කර ඇත.">Here's how you did on this free practice set.</p>

          <div class="grid grid-cols-3 gap-3 mb-6 bg-paper p-4 rounded-xl border border-black/5">
            <div><span class="block text-xs text-ink/40 font-semibold" data-en="Attempted" data-si="උත්සාහ කළ">Attempted</span><span id="dash-attempted" class="text-lg font-bold text-ink font-mono">30</span></div>
            <div class="border-x border-black/10"><span class="block text-xs text-ink/40 font-semibold" data-en="Score" data-si="ලකුණු">Score</span><span id="dash-score" class="text-lg font-bold text-primary font-mono">0 / 30</span></div>
            <div><span class="block text-xs text-ink/40 font-semibold" data-en="Percentage" data-si="ප්‍රතිශතය">Percentage</span><span id="dash-percentage" class="text-lg font-bold text-go font-mono">0%</span></div>
          </div>

          <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <button onclick="restartQuiz()" class="px-5 py-2.5 bg-paper hover:bg-black/5 text-ink font-semibold rounded-xl text-sm transition border border-black/10">
              🔄 <span data-en="Retry Free Test" data-si="නැවත උත්සාහ කරන්න">Retry Free Test</span>
            </button>
            <a href="dashboard.php" class="px-5 py-2.5 bg-primary-light hover:bg-primary/20 text-primary font-semibold rounded-xl text-sm transition flex items-center justify-center gap-2">
              👤 <span data-en="Go to My Dashboard" data-si="ගිණුමේ විස්තර">Go to My Dashboard</span>
            </a>
          </div>
        </div>

        <!-- Upgrade Plans -->
        <div id="plans-section" class="bg-gradient-to-br from-primary via-primary-dark to-ink text-white rounded-2xl shadow-xl p-6 sm:p-8 border border-white/10 rise-in">
          <div class="text-center max-w-lg mx-auto mb-6">
            <span class="bg-accent/20 text-accent text-xs font-bold px-3 py-1 rounded-full border border-accent/30" data-en="Unlock Full Access" data-si="සම්පූර්ණ ප්‍රවේශය අගුළු හරින්න">Unlock Full Access</span>
            <h3 class="font-display font-bold text-xl sm:text-2xl mt-3 mb-2" data-si="ප්‍රශ්න 1000කට අධික සංඛ්‍යාවකට සහ අමතර විශේෂාංග වෙත පිවිසෙන්න">Get 1000+ questions &amp; every feature you need</h3>
            <p class="text-xs sm:text-sm text-white/70" data-si="ජපන් රියදුරු බලපත්‍ර විභාගය පළමු වරටම සමත්වීමට අවශ්‍ය සියලුම Mock Tests දැන්ම ලබා ගන්න.">Everything you need to pass Japan's driving theory test on your first attempt.</p>
          </div>

          <ul class="grid grid-cols-1 sm:grid-cols-3 gap-2 max-w-2xl mx-auto mb-7 text-xs sm:text-sm text-white/85">
            <li class="flex items-center gap-2"><span class="text-accent">✓</span><span data-en="1000+ practice questions" data-si="ප්‍රශ්න 1000+ක්">1000+ practice questions</span></li>
            <li class="flex items-center gap-2"><span class="text-accent">✓</span><span data-en="All chapters &amp; categories" data-si="සියලුම පරිච්ඡේද">All chapters &amp; categories</span></li>
            <li class="flex items-center gap-2"><span class="text-accent">✓</span><span data-en="Unlimited mock exams" data-si="අසීමිත Mock Exams">Unlimited mock exams</span></li>
            <li class="flex items-center gap-2"><span class="text-accent">✓</span><span data-en="Progress dashboard" data-si="ප්‍රගති පුවරුව">Progress dashboard</span></li>
            <li class="flex items-center gap-2"><span class="text-accent">✓</span><span data-en="Detailed explanations" data-si="විස්තරාත්මක පැහැදිලි කිරීම්">Detailed explanations</span></li>
            <li class="flex items-center gap-2"><span class="text-accent">✓</span><span data-en="Sinhala + English toggle" data-si="සිංහල + ඉංග්‍රීසි">Sinhala + English toggle</span></li>
          </ul>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl mx-auto">
            <div class="bg-white/10 backdrop-blur-md p-5 rounded-xl border border-white/15 flex flex-col justify-between">
              <div>
                <span class="bg-white/90 text-primary text-xs font-bold px-2.5 py-1 rounded-full" data-en="Most Popular" data-si="වඩාත් ජනප්‍රිය">Most Popular</span>
                <h4 class="font-bold text-white mt-3 text-lg font-display">6 Months Plan</h4>
                <p class="text-3xl font-black text-white my-2 font-mono">¥3,500<span class="text-sm font-normal text-white/60"> / 6mo</span></p>
                <p class="text-xs text-white/65" data-si="මාස 6ක් පුරා අඛණ්ඩ පුහුණු වීමේ පහසුකම.">Full access for 6 months of steady practice.</p>
              </div>
              <a href="checkout.php?plan=6_months" class="mt-6 block text-center bg-white text-primary hover:bg-white/90 font-bold py-3 rounded-xl transition shadow-lg text-sm">
                <span data-en="Choose 6 Months →" data-si="මාස 6 සැලැස්ම →">Choose 6 Months →</span>
              </a>
            </div>

            <div class="relative bg-white/10 backdrop-blur-md p-5 rounded-xl border-2 border-accent flex flex-col justify-between">
              <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-accent text-ink text-[11px] font-bold px-3 py-1 rounded-full shadow" data-en="Save 29%" data-si="29% ඉතිරි කරන්න">Save 29%</span>
              <div>
                <span class="bg-accent text-ink text-xs font-bold px-2.5 py-1 rounded-full" data-en="Best Value" data-si="හොඳම වටිනාකම">Best Value</span>
                <h4 class="font-bold text-white mt-3 text-lg font-display">1 Year Plan</h4>
                <p class="text-3xl font-black text-accent my-2 font-mono">¥5,000<span class="text-sm font-normal text-white/60"> / yr</span></p>
                <p class="text-xs text-white/65" data-si="වසරක පූර්ණ ප්‍රවේශය සහ විශේෂ විභාග ඉඟි.">Full year of access plus bonus exam tips.</p>
              </div>
              <a href="checkout.php?plan=1_year" class="mt-6 block text-center bg-accent hover:brightness-95 text-ink font-bold py-3 rounded-xl transition shadow-lg text-sm">
                <span data-en="Choose 1 Year →" data-si="වසර 1 සැලැස්ම →">Choose 1 Year →</span>
              </a>
            </div>
          </div>
          <p class="text-center text-[11px] text-white/50 mt-5" data-en="Secure checkout · Instant activation · Cancel anytime" data-si="ආරක්ෂිත ගෙවීම් · ක්ෂණික සක්‍රීය කිරීම">Secure checkout · Instant activation</p>
        </div>
      </div>
    </div>
  </section>
</main>

<footer class="text-center py-8 text-xs text-ink/40 border-t border-black/5 bg-white">
  <p>Japan Driving License Practice System &copy; 2026</p>
  <p class="mt-1" data-en="Made for Sri Lankan drivers preparing for Japan's theory test." data-si="ජපානයේ රියදුරු බලපත්‍ර විභාගය සඳහා සූදානම් වන ශ්‍රී ලාංකිකයන් සඳහා නිර්මාණය කර ඇත.">Made for Sri Lankan drivers preparing for Japan's theory test.</p>
</footer>

<script>
/* ==============================================================
   AUTH
   window.__USER__ is injected server-side by index.php from the real
   PHP session (set by login.php): { id, name, email, plan, status, joined }.
   It is null when nobody is logged in. Test history / in-progress saves
   still live in localStorage (per-browser), keyed loosely — if you want
   those synced across devices too, the next step is a small
   `user_test_history` table + api endpoint that index.php reads/writes.
   ============================================================== */

const LS_HISTORY = 'jp_quiz_history';
const LS_PROGRESS_PREFIX = 'jp_quiz_progress_';

let questions = [];
let currentIndex = 0;
let score = 0;
let currentLang = 'en';
let currentCategory = 'All';
let user = null;

const uiTexts = {
  en: { correct: '✅ Correct!', incorrect: '❌ Incorrect!' },
  si: { correct: '✅ නිවැරදියයි!', incorrect: '❌ වැරදියයි!' }
};

/* ---------------- INIT ---------------- */
window.onload = () => {
  user = window.__USER__ || null;
  applyTranslations();
  refreshNav();

  if (user) {
    renderDashboard();
    showView('dashboard');
  } else {
    showView('guest');
  }
  fetchQuestions('All');
};

/* ---------------- VIEW SWITCH ---------------- */
function showView(view) {
  document.getElementById('view-dashboard').classList.toggle('hidden', view !== 'dashboard');
  document.getElementById('view-guest').classList.toggle('hidden', view === 'dashboard');
  if (view === 'dashboard') renderDashboard();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function startFreshFromDashboard() {
  showView('guest');
  document.getElementById('filter-box').scrollIntoView({ behavior: 'smooth' });
}

/* ---------------- AUTH ---------------- */
function logout() {
  window.location.href = 'logout.php';
}

function refreshNav() {
  const guestNav = document.getElementById('nav-guest');
  const userNav = document.getElementById('nav-user');
  if (user) {
    guestNav.classList.add('hidden');
    userNav.classList.remove('hidden');
    userNav.classList.add('flex');
    document.getElementById('nav-avatar').innerText = (user.name || 'U').trim()[0].toUpperCase();
    document.getElementById('nav-avatar').onclick = logout;
    document.getElementById('nav-avatar').title = 'Log out';
    document.getElementById('nav-dashboard-link').onclick = () => showView('dashboard');
  } else {
    guestNav.classList.remove('hidden');
    userNav.classList.add('hidden');
    userNav.classList.remove('flex');
  }
}

/* ---------------- HISTORY / STATS ---------------- */
function getHistory() { return JSON.parse(localStorage.getItem(LS_HISTORY) || '[]'); }
function saveHistoryEntry(entry) {
  const h = getHistory();
  h.unshift(entry);
  localStorage.setItem(LS_HISTORY, JSON.stringify(h.slice(0, 20)));
}

function computeStats(history) {
  if (!history.length) return { tests: 0, avg: 0, best: 0, weak: null };
  const tests = history.length;
  const avg = Math.round(history.reduce((s, h) => s + h.percentage, 0) / tests);
  const best = Math.max(...history.map(h => h.percentage));
  const byCat = {};
  history.forEach(h => {
    const c = h.category || 'All';
    byCat[c] = byCat[c] || { sum: 0, n: 0 };
    byCat[c].sum += h.percentage; byCat[c].n += 1;
  });
  let weak = null, weakAvg = 101;
  Object.entries(byCat).forEach(([cat, v]) => {
    const a = v.sum / v.n;
    if (a < weakAvg) { weakAvg = a; weak = cat; }
  });
  return { tests, avg, best, weak };
}

function levelFor(avg) {
  if (avg >= 90) return { label_en: 'Test Ready', label_si: 'පරීක්ෂණයට සූදානම්', cls: 'bg-go/25 text-white border-go/40' };
  if (avg >= 70) return { label_en: 'Almost There', label_si: 'ළඟටම පැමිණ ඇත', cls: 'bg-accent/25 text-white border-accent/40' };
  return { label_en: 'Learner', label_si: 'ආරම්භකයා', cls: 'bg-white/15 text-white border-white/25' };
}

/* ---------------- DASHBOARD RENDER ---------------- */
function renderDashboard() {
  if (!user) return;
  const history = getHistory();
  const stats = computeStats(history);

  document.getElementById('dash-welcome').innerText = (currentLang === 'si' ? 'ආයුබෝවන්, ' : 'Welcome back, ') + user.name;
  document.getElementById('license-name').innerText = user.name;
  document.getElementById('license-since').innerText = (currentLang === 'si' ? 'සාමාජික සිට ' : 'Member since ') + (user.joined || '—');
  document.getElementById('license-plan-text').innerText = user.plan && user.plan !== 'free'
    ? (currentLang === 'si' ? 'ප්‍රිමියම් සැලැස්ම' : 'Premium plan')
    : (currentLang === 'si' ? 'නොමිලේ සැලැස්ම' : 'Free plan');

  const lvl = levelFor(stats.avg);
  const badge = document.getElementById('license-level-badge');
  badge.className = 'text-xs font-bold px-3 py-1 rounded-full border ' + lvl.cls;
  badge.innerText = currentLang === 'si' ? lvl.label_si : lvl.label_en;

  document.getElementById('stat-tests').innerText = stats.tests;
  document.getElementById('stat-avg').innerText = stats.avg + '%';
  document.getElementById('stat-best').innerText = stats.best + '%';
  document.getElementById('stat-weak').innerText = stats.weak || '—';

  document.getElementById('dash-upsell').classList.toggle('hidden', !!(user.plan && user.plan !== 'free'));

  const list = document.getElementById('history-list');
  const empty = document.getElementById('history-empty');
  list.querySelectorAll('.history-row').forEach(el => el.remove());
  if (history.length === 0) {
    empty.classList.remove('hidden');
  } else {
    empty.classList.add('hidden');
    history.slice(0, 6).forEach(h => {
      const li = document.createElement('li');
      li.className = 'history-row flex items-center gap-3';
      li.innerHTML = `
        <div class="w-16 text-[11px] text-ink/40 font-mono shrink-0">${h.date}</div>
        <div class="flex-1 h-2.5 rounded-full bg-paper overflow-hidden">
          <div class="h-full rounded-full ${h.percentage >= 70 ? 'bg-go' : h.percentage >= 40 ? 'bg-accent' : 'bg-stop'}" style="width:${h.percentage}%"></div>
        </div>
        <div class="w-14 text-right text-xs font-bold font-mono">${h.percentage}%</div>
      `;
      list.appendChild(li);
    });
  }

  const savedProgress = findAnySavedProgress();
  const banner = document.getElementById('dash-resume-banner');
  if (savedProgress) {
    banner.classList.remove('hidden');
    document.getElementById('dash-resume-text').innerText =
      (currentLang === 'si' ? 'පරීක්ෂණයක් අතරමග ඇත — ප්‍රශ්නය ' : 'Test in progress — Question ') +
      (savedProgress.data.currentIndex + 1) + ' / ' + savedProgress.data.questions.length;
  } else {
    banner.classList.add('hidden');
  }
}

function resumeFromDashboard() {
  const saved = findAnySavedProgress();
  if (!saved) return;
  loadFromSaved(saved.category, saved.data);
  showView('guest');
}

/* ---------------- PROGRESS SAVE / RESUME ---------------- */
function progressKey(cat) { return LS_PROGRESS_PREFIX + cat; }

function saveProgress() {
  if (!questions.length) return;
  const data = { questions, currentIndex, score, category: currentCategory, ts: Date.now() };
  localStorage.setItem(progressKey(currentCategory), JSON.stringify(data));
}

function clearProgress(cat) { localStorage.removeItem(progressKey(cat)); }

function findAnySavedProgress() {
  for (let i = 0; i < localStorage.length; i++) {
    const key = localStorage.key(i);
    if (key && key.startsWith(LS_PROGRESS_PREFIX)) {
      try {
        const data = JSON.parse(localStorage.getItem(key));
        if (data && data.currentIndex < data.questions.length) {
          return { category: data.category, data };
        }
      } catch (e) {}
    }
  }
  return null;
}

function loadFromSaved(category, data) {
  currentCategory = category;
  questions = data.questions;
  currentIndex = data.currentIndex;
  score = data.score;
  document.getElementById('category-select').value = category;
  document.getElementById('loading-box').classList.add('hidden');
  document.getElementById('complete-box').classList.add('hidden');
  document.getElementById('filter-box').classList.remove('hidden');
  document.getElementById('quiz-box').classList.remove('hidden');
  document.getElementById('guest-resume-banner').classList.add('hidden');
  loadQuestion();
}

function resumeSavedProgress() {
  const saved = findAnySavedProgress();
  if (saved) loadFromSaved(saved.category, saved.data);
}
function discardSavedProgress() {
  const saved = findAnySavedProgress();
  if (saved) clearProgress(saved.category);
  document.getElementById('guest-resume-banner').classList.add('hidden');
}

/* ---------------- SHUFFLE ---------------- */
function shuffleArray(array) {
  for (let i = array.length - 1; i > 0; i--) {
    let j = Math.floor(Math.random() * (i + 1));
    [array[i], array[j]] = [array[j], array[i]];
  }
  return array;
}

/* ---------------- FETCH QUESTIONS ---------------- */
async function fetchQuestions(category = 'All') {
  currentCategory = category;

  // If a resumable session exists for this category, surface a resume banner instead of auto-loading it.
  const savedRaw = localStorage.getItem(progressKey(category));
  if (savedRaw && !user) {
    try {
      const data = JSON.parse(savedRaw);
      if (data.currentIndex < data.questions.length) {
        document.getElementById('guest-resume-banner').classList.remove('hidden');
        document.getElementById('guest-resume-text').innerText =
          (currentLang === 'si' ? 'ඔබට අතරමග පරීක්ෂණයක් ඇත — ' : 'You have a test in progress — ') +
          `Q${data.currentIndex + 1}/${data.questions.length}`;
      }
    } catch (e) {}
  }

  const CATS_KEY = 'driving_quiz_categories';

  try {
    let storageKey = `driving_quiz_questions_${category}`;

    if (sessionStorage.getItem(storageKey)) {
      questions = JSON.parse(sessionStorage.getItem(storageKey));
      currentIndex = 0; score = 0;

      // Categories still need to be (re)populated even when questions came from cache,
      // otherwise the dropdown gets stuck on "All" after the very first load.
      let cachedCats = sessionStorage.getItem(CATS_KEY);
      populateDropdown(cachedCats ? JSON.parse(cachedCats) : null, questions, category);

      showQuizBox();
      loadQuestion();
      return;
    }

    let url = 'api/get_questions.php';
    if (category !== 'All') url += `?category=${encodeURIComponent(category)}`;

    let response = await fetch(url);
    let result = await response.json();

    if (result.status === 'error') {
      console.error('API error:', result.message);
      document.getElementById('loading-box').innerHTML = `<p class="text-stop font-medium">Server error: ${result.message}</p>`;
      return;
    }

    let allFetchedQuestions = [];
    if (result.status === 'success' && Array.isArray(result.data)) {
      allFetchedQuestions = result.data;
    } else if (Array.isArray(result)) {
      allFetchedQuestions = result;
    }

    if (allFetchedQuestions.length > 0) {
      // Cache the FULL category list (from an "All" fetch) once, so switching
      // categories later never loses the rest of the options.
      if (Array.isArray(result.categories) && result.categories.length > 0) {
        sessionStorage.setItem(CATS_KEY, JSON.stringify(result.categories));
      }

      shuffleArray(allFetchedQuestions);
      questions = allFetchedQuestions.slice(0, 30);
      sessionStorage.setItem(storageKey, JSON.stringify(questions));
      currentIndex = 0; score = 0;

      let cachedCats = sessionStorage.getItem(CATS_KEY);
      populateDropdown(cachedCats ? JSON.parse(cachedCats) : result.categories, allFetchedQuestions, category);
      showQuizBox();
      loadQuestion();
    } else {
      document.getElementById('loading-box').innerHTML = `<p class="text-stop font-medium">No questions found in database.</p>`;
    }
  } catch (error) {
    console.error('Error:', error);
    document.getElementById('loading-box').innerHTML = `<p class="text-stop font-medium">Server connection failed.</p>`;
  }
}

function showQuizBox() {
  document.getElementById('loading-box').classList.add('hidden');
  document.getElementById('complete-box').classList.add('hidden');
  document.getElementById('filter-box').classList.remove('hidden');
  document.getElementById('quiz-box').classList.remove('hidden');
}

function populateDropdown(apiCategories, fetchedQuestions, selectedCategory) {
  let select = document.getElementById('category-select');
  while (select.options.length > 1) select.remove(1);

  let catList = [];
  if (apiCategories && Array.isArray(apiCategories) && apiCategories.length > 0) {
    catList = apiCategories;
  } else {
    let uniqueCats = new Set();
    fetchedQuestions.forEach(q => {
      let c = q.category || q.chapter || q.cat_name || q.section;
      if (c) uniqueCats.add(c);
    });
    catList = Array.from(uniqueCats);
  }
  if (catList.length === 0) {
    catList = [
      "General Rules",
      "Speed Limits",
      "Stopping & Parking",
      "Traffic Signs & Signals",
      "DUI & Safety",
      "Highway Driving",
      "Pedestrians & School Zones"
    ];
  }
  catList.forEach(cat => {
    if (cat && cat.trim() !== '') {
      let opt = document.createElement('option');
      opt.value = cat; opt.innerText = cat;
      if (cat === selectedCategory) opt.selected = true;
      select.appendChild(opt);
    }
  });
}

function changeCategory() {
  let selectedCat = document.getElementById('category-select').value;
  document.getElementById('quiz-box').classList.add('hidden');
  document.getElementById('complete-box').classList.add('hidden');
  document.getElementById('loading-box').classList.remove('hidden');
  fetchQuestions(selectedCat);
}

/* ---------------- QUIZ FLOW ---------------- */
function renderSegments() {
  const track = document.getElementById('seg-track');
  track.innerHTML = '';
  questions.forEach((_, i) => {
    const seg = document.createElement('div');
    seg.className = 'seg' + (i < currentIndex ? ' filled' : i === currentIndex ? ' current' : '');
    track.appendChild(seg);
  });
}

function loadQuestion() {
  if (currentIndex >= questions.length) { showCompletion(); return; }

  let q = questions[currentIndex];
  document.getElementById('progress-text').innerText = `Question ${currentIndex + 1} of ${questions.length}`;
  document.getElementById('score-val').innerText = score;
  renderSegments();

  let qText = currentLang === 'si' && q.question_si ? q.question_si : q.question_en;
  document.getElementById('question-text').innerText = qText;

  let imgContainer = document.getElementById('image-container');
  let imgElement = document.getElementById('question-img');
  if (q.img && q.img !== 'null' && q.img !== '') {
    imgElement.src = q.img;
    imgContainer.classList.remove('hidden');
  } else {
    imgContainer.classList.add('hidden');
  }

  document.getElementById('explanation-box').classList.add('hidden');
  setButtonsEnabled(true);
  saveProgress();
}

function handleAnswer(selectedAnswer) {
  let q = questions[currentIndex];
  let isCorrect = (selectedAnswer === parseInt(q.correct_answer));
  if (isCorrect) { score++; document.getElementById('score-val').innerText = score; }

  let expBox = document.getElementById('explanation-box');
  let badge = document.getElementById('result-badge');
  let expText = document.getElementById('explanation-text');

  badge.innerHTML = isCorrect ? uiTexts[currentLang].correct : uiTexts[currentLang].incorrect;
  badge.className = `font-bold text-sm mb-1 ${isCorrect ? 'text-go' : 'text-stop'}`;

  let explanation = currentLang === 'si' && q.explanation_si ? q.explanation_si : q.explanation_en;
  expText.innerText = explanation ? explanation : (currentLang === 'si' ? 'අතිරේක විස්තරයක් නොමැත.' : 'No explanation available.');

  expBox.classList.remove('hidden');
  setButtonsEnabled(false);
  saveProgress();
}

function setButtonsEnabled(enabled) {
  document.querySelectorAll('#options-box button').forEach(btn => {
    btn.disabled = !enabled;
    btn.classList.toggle('opacity-50', !enabled);
    btn.classList.toggle('cursor-not-allowed', !enabled);
  });
}

function nextQuestion() { currentIndex++; loadQuestion(); }

/* ---------------- LANGUAGE ---------------- */
function applyTranslations() {
  document.querySelectorAll('[data-en]').forEach(el => {
    const txt = currentLang === 'si' ? (el.getAttribute('data-si') || el.getAttribute('data-en')) : el.getAttribute('data-en');
    el.innerText = txt;
  });
  document.getElementById('true-label').innerText = currentLang === 'si' ? '正 — True (හරි / ◯)' : '正 — True (◯)';
  document.getElementById('false-label').innerText = currentLang === 'si' ? '誤 — වැරදියයි (✕)' : '誤 — False (✕)';
  document.getElementById('next-btn-text').innerText = currentLang === 'si' ? 'මීළඟ ප්‍රශ්නය →' : 'Next Question →';
  document.getElementById('login-btn-text').innerText = currentLang === 'si' ? 'ලොග් වෙන්න' : 'Log in';
  document.getElementById('cat-label').innerText = currentLang === 'si'
    ? 'මාතෘකාව හෝ පරිච්ඡේදය තෝරන්න:'
    : 'Select Category / Chapter:';
}

function setLanguage(lang) {
  currentLang = lang;
  document.getElementById('btn-en').className = "px-2.5 sm:px-3 py-1 rounded-md font-semibold transition " + (lang === 'en' ? 'bg-white text-primary' : 'text-white');
  document.getElementById('btn-si').className = "px-2.5 sm:px-3 py-1 rounded-md font-semibold transition font-si " + (lang === 'si' ? 'bg-white text-primary' : 'text-white');

  applyTranslations();
  document.documentElement.lang = lang;

  if (questions.length > 0 && currentIndex < questions.length) {
    let q = questions[currentIndex];
    document.getElementById('question-text').innerText = (lang === 'si' && q.question_si) ? q.question_si : q.question_en;
    let explanation = (lang === 'si' && q.explanation_si) ? q.explanation_si : q.explanation_en;
    if (!document.getElementById('explanation-box').classList.contains('hidden')) {
      document.getElementById('explanation-text').innerText = explanation || 'No explanation available.';
    }
  }
  if (user) renderDashboard();
}

/* ---------------- COMPLETION ---------------- */
function showCompletion() {
  document.getElementById('filter-box').classList.add('hidden');
  document.getElementById('quiz-box').classList.add('hidden');
  document.getElementById('complete-box').classList.remove('hidden');

  let total = questions.length > 0 ? questions.length : 30;
  let percentage = Math.round((score / total) * 100);

  document.getElementById('dash-attempted').innerText = total;
  document.getElementById('dash-score').innerText = `${score} / ${total}`;
  document.getElementById('dash-percentage').innerText = `${percentage}%`;

  saveHistoryEntry({
    date: new Date().toISOString().slice(0, 10),
    category: currentCategory,
    score, total, percentage
  });
  clearProgress(currentCategory);
}

function restartQuiz() {
  let selectedCat = document.getElementById('category-select').value;
  sessionStorage.removeItem(`driving_quiz_questions_${selectedCat}`);
  clearProgress(selectedCat);
  document.getElementById('complete-box').classList.add('hidden');
  document.getElementById('loading-box').classList.remove('hidden');
  fetchQuestions(selectedCat);
}
</script>
</body>
</html>