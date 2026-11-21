<?php
// Dashboard with proper content area integration
include('includes/header.php');

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Get stats with error handling
$total_services = 0;
$pending_requests = 0;
$completed_requests = 0;
$total_clients = 0;
$recent_clients = 0;
$horoscope_profiles = 0;

try {
    $result = $conn->query("SELECT COUNT(id) AS cnt FROM services");
    if ($result) $total_services = $result->fetch_assoc()['cnt'];
    
    $result = $conn->query("SELECT COUNT(id) AS cnt FROM client_requests WHERE status = 'pending'");
    if ($result) $pending_requests = $result->fetch_assoc()['cnt'];
    
    $result = $conn->query("SELECT COUNT(id) AS cnt FROM client_requests WHERE status = 'completed'");
    if ($result) $completed_requests = $result->fetch_assoc()['cnt'];
    
    $result = $conn->query("SELECT COUNT(id) AS cnt FROM client");
    if ($result) $total_clients = $result->fetch_assoc()['cnt'];
    
    $result = $conn->query("SELECT COUNT(id) AS cnt FROM client WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    if ($result) $recent_clients = $result->fetch_assoc()['cnt'];
    
    $result = $conn->query("SELECT COUNT(id) AS cnt FROM horoscope_details");
    if ($result) $horoscope_profiles = $result->fetch_assoc()['cnt'];
} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
}

$admin_name = $_SESSION['admin_username'] ?? 'Admin';

// Get recent inquiries
$recent_inquiries = $conn->query("SELECT cr.*, s.service_name 
                                   FROM client_requests cr 
                                   LEFT JOIN services s ON cr.service_id = s.id 
                                   ORDER BY cr.request_date DESC 
                                   LIMIT 5");
?>

<!-- Additional CSS for dashboard -->
<style>
    .dashboard-welcome {
        background: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }
    
    .dashboard-welcome h2 {
        color: #667eea;
        margin: 0;
        font-weight: bold;
    }
    
    .dashboard-welcome p {
        color: #666;
        margin: 0.5rem 0 0 0;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
    }
    
    .stat-card.primary::before { background: #667eea; }
    .stat-card.warning::before { background: #f39c12; }
    .stat-card.success::before { background: #27ae60; }
    .stat-card.info::before { background: #3498db; }
    .stat-card.danger::before { background: #e74c3c; }
    .stat-card.purple::before { background: #9b59b6; }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .stat-icon {
        font-size: 2.5rem;
        margin-bottom: 1rem;
    }
    
    .stat-card.primary .stat-icon { color: #667eea; }
    .stat-card.warning .stat-icon { color: #f39c12; }
    .stat-card.success .stat-icon { color: #27ae60; }
    .stat-card.info .stat-icon { color: #3498db; }
    .stat-card.danger .stat-icon { color: #e74c3c; }
    .stat-card.purple .stat-icon { color: #9b59b6; }
    
    .stat-label {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }
    
    .stat-value {
        font-size: 2.5rem;
        font-weight: bold;
        color: #333;
        margin: 0.5rem 0;
    }
    
    .stat-footer {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #f0f0f0;
    }
    
    .stat-footer a {
        color: #667eea;
        text-decoration: none;
        font-weight: 500;
    }
    
    .stat-footer a:hover {
        color: #764ba2;
    }
    
    .section-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }
    
    .section-title {
        font-weight: bold;
        color: #333;
        margin-bottom: 1.5rem;
    }
    
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 1.5rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        text-decoration: none;
        transition: all 0.3s ease;
        text-align: center;
    }
    
    .action-btn:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        color: white;
    }
    
    .action-btn i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    
    .inquiry-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .inquiry-item {
        padding: 1rem;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .inquiry-item:last-child {
        border-bottom: none;
    }
    
    .inquiry-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }
    
    .inquiry-name {
        font-weight: 600;
        color: #333;
    }
    
    .inquiry-date {
        color: #999;
        font-size: 0.875rem;
    }
    
    .inquiry-message {
        color: #666;
        margin: 0.5rem 0;
    }
    
    .inquiry-status {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.875rem;
        font-weight: 600;
    }
    
    .inquiry-status.pending {
        background: #fff3cd;
        color: #856404;
    }
    
    .inquiry-status.completed {
        background: #d1e7dd;
        color: #0f5132;
    }
    
    .empty-state {
        text-align: center;
        padding: 2rem;
        color: #999;
    }
    
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
    }
    
    .info-alert {
        background: #e7f3ff;
        border-left: 4px solid #3498db;
        padding: 1rem;
        border-radius: 10px;
        color: #2c3e50;
    }
</style>

<!-- Dashboard Content -->
<div class="dashboard-welcome">
    <h2>🙏 ආයුබෝවන්, <?php echo htmlspecialchars($admin_name); ?>!</h2>
    <p>නෙත්සිත ජෝතිශ්‍ය සේවය - පරිපාලක පුවරුව</p>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <!-- Total Services -->
    <div class="stat-card primary">
        <div class="stat-icon">
            <i class="fas fa-th-large"></i>
        </div>
        <div class="stat-label">මුළු සේවා ගණන</div>
        <div class="stat-value"><?php echo $total_services; ?></div>
        <div class="stat-footer">
            <a href="manage_services.php">
                <i class="fas fa-arrow-right me-1"></i>සේවා කළමනාකරණය
            </a>
        </div>
    </div>

    <!-- Pending Requests -->
    <div class="stat-card warning">
        <div class="stat-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-label">අපේක්ෂිත ඉල්ලීම්</div>
        <div class="stat-value"><?php echo $pending_requests; ?></div>
        <div class="stat-footer">
            <a href="manage_requests.php?filter=pending">
                <i class="fas fa-arrow-right me-1"></i>ඉල්ලීම් බලන්න
            </a>
        </div>
    </div>

    <!-- Completed Requests -->
    <div class="stat-card success">
        <div class="stat-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-label">සම්පූර්ණ කළ සේවා</div>
        <div class="stat-value"><?php echo $completed_requests; ?></div>
        <div class="stat-footer">
            <a href="manage_requests.php?filter=completed">
                <i class="fas fa-arrow-right me-1"></i>වාර්තා බලන්න
            </a>
        </div>
    </div>

    <!-- Total Clients -->
    <div class="stat-card info">
        <div class="stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-label">ලියාපදිංචි සේවාදායකයින්</div>
        <div class="stat-value"><?php echo $total_clients; ?></div>
        <div class="stat-footer">
            <a href="manage_clients.php">
                <i class="fas fa-arrow-right me-1"></i>සේවාදායකයින් බලන්න
            </a>
        </div>
    </div>

    <!-- Recent Clients -->
    <div class="stat-card danger">
        <div class="stat-icon">
            <i class="fas fa-user-plus"></i>
        </div>
        <div class="stat-label">නව සේවාදායකයින් (7 දින)</div>
        <div class="stat-value"><?php echo $recent_clients; ?></div>
        <div class="stat-footer">
            <i class="fas fa-calendar me-1"></i>අවසන් සතිය
        </div>
    </div>

    <!-- Horoscope Profiles -->
    <div class="stat-card purple">
        <div class="stat-icon">
            <i class="fas fa-star"></i>
        </div>
        <div class="stat-label">හොරෝස්කෝප් Profile</div>
        <div class="stat-value"><?php echo $horoscope_profiles; ?></div>
        <div class="stat-footer">
            <a href="manage_horoscopes.php">
                <i class="fas fa-arrow-right me-1"></i>Profile බලන්න
            </a>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="section-card">
    <h3 class="section-title">
        <i class="fas fa-bolt me-2" style="color: #f6b93b;"></i>ඉක්මන් ක්‍රියා
    </h3>
    <div class="quick-actions">
        <a href="manage_services.php" class="action-btn">
            <i class="fas fa-plus-circle"></i>
            <span>නව සේවාවක් එක් කරන්න</span>
        </a>
        <a href="manage_requests.php" class="action-btn">
            <i class="fas fa-envelope-open-text"></i>
            <span>ඉල්ලීම් කළමනාකරණය</span>
        </a>
        <a href="manage_clients.php" class="action-btn">
            <i class="fas fa-users-cog"></i>
            <span>සේවාදායකයින් කළමනාකරණය</span>
        </a>
        <a href="settings.php" class="action-btn">
            <i class="fas fa-cog"></i>
            <span>සැකසුම්</span>
        </a>
    </div>
</div>

<!-- Recent Inquiries -->
<div class="section-card">
    <h3 class="section-title">
        <i class="fas fa-comments me-2" style="color: #667eea;"></i>නවතම ඉල්ලීම්
    </h3>

    <?php if($recent_inquiries && $recent_inquiries->num_rows > 0): ?>
        <ul class="inquiry-list">
            <?php while($inquiry = $recent_inquiries->fetch_assoc()): ?>
                <li class="inquiry-item">
                    <div class="inquiry-header">
                        <span class="inquiry-name">
                            <i class="fas fa-user me-2"></i>
                            <?php echo htmlspecialchars($inquiry['client_name']); ?>
                        </span>
                        <span class="inquiry-date">
                            <i class="fas fa-clock me-1"></i>
                            <?php echo date('Y-m-d H:i', strtotime($inquiry['request_date'])); ?>
                        </span>
                    </div>
                    <div class="inquiry-message">
                        <i class="fas fa-concierge-bell me-2"></i>
                        <?php echo htmlspecialchars($inquiry['service_name'] ?? 'Service'); ?>
                    </div>
                    <div>
                        <span class="inquiry-status <?php echo strtolower($inquiry['status'] ?? 'pending'); ?>">
                            <?php 
                            $status_labels = [
                                'pending' => 'අපේක්ෂිත',
                                'in_progress' => 'ක්‍රියාත්මක',
                                'completed' => 'සම්පූර්ණයි'
                            ];
                            echo $status_labels[$inquiry['status']] ?? 'අපේක්ෂිත';
                            ?>
                        </span>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>ඉල්ලීම් නොමැත</p>
        </div>
    <?php endif; ?>
</div>

<!-- Info Alert -->
<div class="info-alert">
    <strong><i class="fas fa-info-circle me-2"></i>උපදෙස්:</strong> 
    ඔබට Admin Panel එක හරහා සේවා, ඉල්ලීම්, සේවාදායකයින් සහ හොරෝස්කෝප් දත්ත කළමනාකරණය කළ හැක.
</div>

<script>
    // Animate numbers on load
    document.addEventListener('DOMContentLoaded', function() {
        const statValues = document.querySelectorAll('.stat-value');
        statValues.forEach(stat => {
            const target = parseInt(stat.textContent);
            if (isNaN(target)) return;
            
            let current = 0;
            const increment = target / 30;
            const timer = setInterval(() => {
                current += increment;
                if(current >= target) {
                    stat.textContent = target;
                    clearInterval(timer);
                } else {
                    stat.textContent = Math.floor(current);
                }
            }, 30);
        });
    });
    
    console.log('Dashboard content loaded!');
</script>

<?php include('includes/footer.php'); ?>