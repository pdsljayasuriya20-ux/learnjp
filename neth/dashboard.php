<?php
session_start();
require_once 'db_connection.php';

// Check if client is logged in
if (!isset($_SESSION['client_logged_in']) || $_SESSION['client_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Get client details from session
$client_id = $_SESSION['client_id'];
$client_name = $_SESSION['client_name'];

// Get additional client details from database
$sql = "SELECT email, created_at FROM client WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
$client_details = $result->fetch_assoc();

// Get statistics (if you have a consultations or inquiries table)
$total_consultations = 0;
$pending_consultations = 0;
$completed_consultations = 0;

// Check if contact_inquiries table exists and get stats
$inquiry_check = $conn->query("SHOW TABLES LIKE 'contact_inquiries'");
if($inquiry_check->num_rows > 0) {
    $stats_query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM contact_inquiries WHERE client_id = ?";
    $stats_stmt = $conn->prepare($stats_query);
    $stats_stmt->bind_param("i", $client_id);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result->fetch_assoc();
    
    $total_consultations = $stats['total'] ?? 0;
    $pending_consultations = $stats['pending'] ?? 0;
    $completed_consultations = $stats['completed'] ?? 0;
    $stats_stmt->close();
}

// Calculate account age
$account_age = '';
if($client_details['created_at']) {
    $created = new DateTime($client_details['created_at']);
    $now = new DateTime();
    $diff = $created->diff($now);
    
    if($diff->y > 0) {
        $account_age = $diff->y . ' වසර';
    } elseif($diff->m > 0) {
        $account_age = $diff->m . ' මාස';
    } else {
        $account_age = $diff->d . ' දින';
    }
}

$stmt->close();
$conn->close();

// Set page title
$page_title = 'Client Dashboard';
?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - නෙත්සිත ජෝතිශ්‍ය සේවය</title>
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
            position: relative;
            overflow-x: hidden;
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
            0%, 100% { opacity: 0.3; }
            50% { opacity: 1; }
        }

        /* Header */
        .dashboard-header {
            background: rgba(45, 27, 78, 0.95);
            border-bottom: 2px solid rgba(218, 165, 32, 0.3);
            padding: 20px 0;
            position: relative;
            z-index: 10;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-title {
            color: #daa520;
            font-size: 24px;
        }

        .header-title i {
            margin-right: 10px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 2px solid;
        }

        .btn-home {
            background: rgba(218, 165, 32, 0.2);
            color: #daa520;
            border-color: rgba(218, 165, 32, 0.4);
        }

        .btn-home:hover {
            background: rgba(218, 165, 32, 0.3);
            transform: translateY(-2px);
        }

        .btn-logout {
            background: rgba(244, 67, 54, 0.2);
            color: #ff6b6b;
            border-color: rgba(244, 67, 54, 0.4);
        }

        .btn-logout:hover {
            background: rgba(244, 67, 54, 0.3);
            transform: translateY(-2px);
        }

        /* Main Container */
        .dashboard-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            position: relative;
            z-index: 10;
        }

        /* Welcome Section */
        .welcome-section {
            background: rgba(45, 27, 78, 0.95);
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .welcome-section h2 {
            color: #daa520;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .welcome-section p {
            color: #b8a7d4;
            line-height: 1.6;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(45, 27, 78, 0.95);
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-radius: 15px;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(218, 165, 32, 0.3);
            border-color: #daa520;
        }

        .stat-icon {
            font-size: 40px;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
        }

        .stat-icon.total {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .stat-icon.pending {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .stat-icon.completed {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .stat-icon.account {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }

        .stat-info h3 {
            color: #daa520;
            font-size: 32px;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #b8a7d4;
            font-size: 14px;
        }

        /* Account Details */
        .account-details {
            background: rgba(45, 27, 78, 0.95);
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .account-details h3 {
            color: #daa520;
            font-size: 22px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .detail-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .detail-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(218, 165, 32, 0.2);
            border-radius: 10px;
            color: #daa520;
            font-size: 18px;
        }

        .detail-content {
            flex: 1;
        }

        .detail-label {
            color: #8a7ba8;
            font-size: 13px;
            margin-bottom: 3px;
        }

        .detail-value {
            color: #e0d5f0;
            font-size: 16px;
            font-weight: 500;
        }

        /* Quick Actions */
        .quick-actions {
            background: rgba(45, 27, 78, 0.95);
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .quick-actions h3 {
            color: #daa520;
            font-size: 22px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .action-card {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(218, 165, 32, 0.2);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .action-card:hover {
            background: rgba(218, 165, 32, 0.1);
            border-color: #daa520;
            transform: translateY(-5px);
        }

        .action-icon {
            font-size: 36px;
            color: #daa520;
            margin-bottom: 10px;
        }

        .action-title {
            color: #e0d5f0;
            font-size: 16px;
            font-weight: 600;
        }

        .action-desc {
            color: #8a7ba8;
            font-size: 13px;
            margin-top: 5px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .header-title {
                font-size: 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .actions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Cosmic Background -->
    <div class="stars" id="stars"></div>

    <!-- Header -->
    <header class="dashboard-header">
        <div class="header-content">
            <h1 class="header-title">
                <i class="fas fa-star"></i>
                Client Dashboard
            </h1>
            <div class="header-actions">
                <a href="index.php" class="btn btn-home">
                    <i class="fas fa-home"></i>
                    <span>මුල් පිටුව</span>
                </a>
                <a href="logout.php" class="btn btn-logout" onclick="return confirm('ඔබට logout වීමට අවශ්‍යද?');">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h2>👋 සාදරයෙන් පිළිගනිමු, <?php echo htmlspecialchars($client_name); ?>!</h2>
            <p>මෙය ඔබගේ පුද්ගලික Dashboard එකයි. මෙතනින් ඔබට අවශ්‍ය සේවාවන් හා ඔබගේ ගිණුම කළමනාකරණය කළ හැකියි.</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_consultations; ?></h3>
                    <p>මුළු උපදේශන</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $pending_consultations; ?></h3>
                    <p>අපේක්ෂිත</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon completed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $completed_consultations; ?></h3>
                    <p>සම්පූර්ණ කළ</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon account">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $account_age; ?></h3>
                    <p>ගිණුම් වයස</p>
                </div>
            </div>
        </div>

        <!-- Account Details -->
        <div class="account-details">
            <h3>
                <i class="fas fa-user-circle"></i>
                ඔබගේ ගිණුම් විස්තර
            </h3>
            
            <div class="detail-item">
                <div class="detail-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">නම</div>
                    <div class="detail-value"><?php echo htmlspecialchars($client_name); ?></div>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">විද්‍යුත් තැපෑල</div>
                    <div class="detail-value"><?php echo htmlspecialchars($client_details['email']); ?></div>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">ලියාපදිංචි වූ දිනය</div>
                    <div class="detail-value"><?php echo date('Y-m-d', strtotime($client_details['created_at'])); ?></div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3>
                <i class="fas fa-bolt"></i>
                ඉක්මන් ක්‍රියා
            </h3>
            
            <div class="actions-grid">
                <a href="services.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-th-large"></i>
                    </div>
                    <div class="action-title">සේවා බලන්න</div>
                    <div class="action-desc">අපගේ සියලුම සේවා බලන්න</div>
                </a>

                <a href="contact.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="action-title">උපදේශනයක් ඉල්ලන්න</div>
                    <div class="action-desc">නව උපදේශනයක් ඇණවුම් කරන්න</div>
                </a>

                <a href="profile.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div class="action-title">Profile සංස්කරණය</div>
                    <div class="action-desc">ඔබගේ තොරතුරු යාවත්කාලීන කරන්න</div>
                </a>

                <a href="my_consultations.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="action-title">මගේ උපදේශන</div>
                    <div class="action-desc">ඉතිහාසය බලන්න</div>
                </a>

                <a href="https://wa.me/94712391317" target="_blank" class="action-card">
                    <div class="action-icon">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <div class="action-title">WhatsApp සහාය</div>
                    <div class="action-desc">ක්ෂණික සහාය ලබාගන්න</div>
                </a>

                <a href="about.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="action-title">අප ගැන</div>
                    <div class="action-desc">අපගේ සේවාව ගැන දැනගන්න</div>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Create animated stars
        const starsContainer = document.getElementById('stars');
        for(let i = 0; i < 150; i++) {
            const star = document.createElement('div');
            star.className = 'star';
            star.style.left = Math.random() * 100 + '%';
            star.style.top = Math.random() * 100 + '%';
            star.style.animationDelay = Math.random() * 3 + 's';
            starsContainer.appendChild(star);
        }

        // Add smooth scroll behavior
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if(target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>