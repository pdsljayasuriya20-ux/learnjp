<?php
/**
 * නෙත්සිත ජෝතිශ්‍ය සේවය - දත්ත සමුදාය පිටපත් කිරීමේ පිටුව
 */

// පරිපාලක ශීර්ෂය ඇතුළත් කරන්න
include('includes/header.php'); 

$message = '';

// දත්ත සමුදා සම්බන්ධතාවය තහවුරු කිරීම
if (!isset($conn) || $conn->connect_error) {
    $message = '<div class="alert alert-danger alert-custom">
                    <i class="fas fa-exclamation-circle me-2"></i>දත්ත සමුදාය සම්බන්ධ කිරීමේ දෝෂයයි. Backup කළ නොහැක.
                </div>';
    $conn = null;
}

// Get database statistics
$total_tables = 0;
$total_rows = 0;
$db_size = '0 MB';
$db_name = 'nethsitha_db'; // Database name - change this to match your database

if ($conn) {
    // Get current database name from connection
    $db_result = $conn->query("SELECT DATABASE() as db_name");
    if ($db_result) {
        $db_info = $db_result->fetch_assoc();
        $db_name = $db_info['db_name'];
    }
    
    // Get table count and row count
    $tables_result = $conn->query("SHOW TABLES");
    if ($tables_result) {
        $total_tables = $tables_result->num_rows;
        
        while ($table = $tables_result->fetch_array()) {
            $table_name = $table[0];
            $row_result = $conn->query("SELECT COUNT(*) as count FROM `$table_name`");
            if ($row_result) {
                $total_rows += $row_result->fetch_assoc()['count'];
            }
        }
    }
    
    // Get database size
    $size_result = $conn->query("SELECT 
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
        FROM information_schema.TABLES 
        WHERE table_schema = '$db_name'");
    if ($size_result) {
        $size = $size_result->fetch_assoc();
        $db_size = ($size['size_mb'] ?: '0') . ' MB';
    }
}
?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>දත්ත සමුදාය කළමනාකරණය - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css-main.css">
</head>
<body>
    <div class="backup-container">
        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-database me-2"></i>දත්ත සමුදාය කළමනාකරණය</h2>
            <p>දත්ත සමුදා Backup සහ Restore කළමනාකරණය</p>
        </div>

        <?php echo $message; ?>

        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat-box tables">
                <div class="stat-icon">
                    <i class="fas fa-table"></i>
                </div>
                <div class="stat-info">
                    <h6>දත්ත වගු</h6>
                    <div class="stat-value"><?php echo $total_tables; ?></div>
                </div>
            </div>
            <div class="stat-box rows">
                <div class="stat-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-info">
                    <h6>මුළු දත්ත පේළි</h6>
                    <div class="stat-value"><?php echo number_format($total_rows); ?></div>
                </div>
            </div>
            <div class="stat-box size">
                <div class="stat-icon">
                    <i class="fas fa-hdd"></i>
                </div>
                <div class="stat-info">
                    <h6>දත්ත සමුදා ප්‍රමාණය</h6>
                    <div class="stat-value"><?php echo $db_size; ?></div>
                </div>
            </div>
        </div>

        <!-- Backup Options -->
        <div class="backup-grid">
            <!-- SQL Backup Card -->
            <div class="backup-card sql">
                <div class="backup-icon">
                    <i class="fas fa-database"></i>
                </div>
                <h3 class="backup-title">SQL Backup</h3>
                <p class="backup-description">
                    සම්පූර්ණ දත්ත සමුදාය ව්‍යුහය සහ දත්ත සමඟ .sql ගොනුවක් ලෙස ලබා ගන්න
                </p>
                <div class="backup-features">
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>සියලුම වගු සහ දත්ත</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>දත්ත සමුදා ව්‍යුහය</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Restore කිරීමට හොඳම</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>සම්පූර්ණ Backup</span>
                    </div>
                </div>
                <?php if ($conn): ?>
                    <a href="download_db.php" class="btn-download sql">
                        <i class="fas fa-download"></i>
                        SQL බාගත කරන්න
                    </a>
                <?php else: ?>
                    <button class="btn-download" disabled>
                        <i class="fas fa-times"></i>
                        සම්බන්ධතාවය නැත
                    </button>
                <?php endif; ?>
            </div>

            <!-- CSV Backup Card -->
            <div class="backup-card csv">
                <div class="backup-icon">
                    <i class="fas fa-file-csv"></i>
                </div>
                <h3 class="backup-title">CSV/Excel Backup</h3>
                <p class="backup-description">
                    සියලුම වගු වෙනම CSV ගොනු ලෙස ZIP ආකෘතියෙන් ලබා ගන්න
                </p>
                <div class="backup-features">
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>වෙනම CSV ගොනු</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Excel සමඟ විවෘත කළ හැක</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>දත්ත විශ්ලේෂණයට</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>ZIP ආකෘතියෙන්</span>
                    </div>
                </div>
                <?php if ($conn): ?>
                    <a href="download_csv.php" class="btn-download csv">
                        <i class="fas fa-file-download"></i>
                        CSV ZIP බාගත කරන්න
                    </a>
                <?php else: ?>
                    <button class="btn-download" disabled>
                        <i class="fas fa-times"></i>
                        සම්බන්ධතාවය නැත
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Restore Section -->
        <div class="restore-card">
            <div class="restore-header">
                <div class="restore-icon">
                    <i class="fas fa-upload"></i>
                </div>
                <h3 class="restore-title">දත්ත ආනයනය (Restore)</h3>
            </div>

            <div class="warning-box">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="warning-text">
                    <strong>අවධානය!</strong><br>
                    දත්ත ආනයනය කිරීම දැනට පවතින සියලුම දත්ත මකා නැවත ලියනු ඇත. මෙම ක්‍රියාව ආපසු හැරවිය නොහැක. ඉදිරියට යාමට පෙර වත්මන් දත්ත සුරක්ෂිත කර ඇති බවට වග බලා ගන්න.
                </div>
            </div>

            <div class="info-box">
                <strong><i class="fas fa-info-circle me-2"></i>නිර්දේශිත ක්‍රමය</strong>
                <ul>
                    <li>විශාල ගොනු සඳහා phpMyAdmin භාවිතා කරන්න</li>
                    <li>Hosting Control Panel මෙවලම් භාවිතා කරන්න</li>
                    <li>ආරක්ෂිත වාතාවරණයක් තහවුරු කර ගන්න</li>
                    <li>Restore කිරීමට පෙර Backup එකක් සාදන්න</li>
                </ul>
            </div>

            <div class="file-upload-area">
                <div class="file-upload-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <p style="color: #666; margin-bottom: 15px;">
                    ආරක්ෂක හේතු සහ විශාල ගොනු හැසිරවීම සඳහා,<br>
                    කරුණාකර <strong>phpMyAdmin</strong> හෝ වෙනත් Hosting මෙවලම් භාවිතා කරන්න
                </p>
                <form method="POST" action="#" enctype="multipart/form-data">
                    <input type="file" name="sql_file" class="form-control mb-3" accept=".sql" disabled style="opacity: 0.5;">
                    <button type="button" class="btn btn-secondary w-100" disabled style="opacity: 0.5; cursor: not-allowed;">
                        <i class="fas fa-lock me-2"></i>ආනයනය අබල කර ඇත
                    </button>
                </form>
            </div>

            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 20px;">
                <h6 style="color: #555; margin-bottom: 10px;">
                    <i class="fas fa-lightbulb me-2" style="color: #f6b93b;"></i>phpMyAdmin භාවිත කර Restore කරන්නේ කෙසේද?
                </h6>
                <ol style="color: #666; font-size: 14px; margin: 0; padding-left: 20px;">
                    <li>cPanel හෝ Hosting Panel එකට පිවිසෙන්න</li>
                    <li>phpMyAdmin විවෘත කරන්න</li>
                    <li>දත්ත සමුදාය තෝරන්න</li>
                    <li>"Import" tab එක ක්ලික් කරන්න</li>
                    <li>SQL ගොනුව තෝරා "Go" ක්ලික් කරන්න</li>
                </ol>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide success messages
        setTimeout(function() {
            var alert = document.querySelector('.alert-success');
            if(alert) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);
    </script>
</body>
</html>

<?php
include('includes/footer.php');
?>