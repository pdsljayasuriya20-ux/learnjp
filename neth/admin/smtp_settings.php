<?php
// Note: Assumes includes/header.php provides a valid $conn (mysqli connection)
include('includes/header.php');

$message = '';
$settings = [];

/**
 * Database එකෙන් settings fetch කරන function
 * @param mysqli $conn
 * @return array
 */
function getSettings($conn) {
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM app_settings");
    // Check if preparation was successful
    if ($stmt === false) {
        error_log("Failed to prepare getSettings statement: " . $conn->error);
        return [];
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[$row['setting_key']] = $row['setting_value'];
    }
    $stmt->close();
    return $data;
}

// POST Request - settings save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $update_successful = true;
    
    $fields = [
        'smtp_host', 'smtp_port', 'smtp_username', 
        'smtp_password', 'smtp_encryption', 'sender_name', 'sender_email'
    ];

    // Using prepared statement for batch update/insert
    $stmt_update = $conn->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) 
                                   ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    
    if ($stmt_update === false) {
        error_log("Failed to prepare batch update statement: " . $conn->error);
        $update_successful = false;
    } else {
        foreach ($fields as $key) {
            if (isset($_POST[$key])) {
                $value = $_POST[$key]; // Use raw value for binding
                
                // Bind and execute
                $stmt_update->bind_param("ss", $key, $value);
                
                if (!$stmt_update->execute()) {
                    $update_successful = false;
                    error_log("Failed to update setting: $key - " . $stmt_update->error);
                    break;
                }
            }
        }
        $stmt_update->close();
    }
    
    // Check the overall success status
    if ($update_successful) {
        $message = '<div class="alert alert-success alert-custom">
                        <i class="fas fa-check-circle me-2"></i>SMTP සැකසුම් සාර්ථකව යාවත්කාලීන කරන ලදි!
                    </div>';
    } else {
        $message = '<div class="alert alert-danger alert-custom">
                        <i class="fas fa-times-circle me-2"></i>යාවත්කාලීන කිරීමේ දෝෂයක් ඇති විය.
                    </div>';
    }
    
    // Updated settings fetch
    $settings = getSettings($conn);
    
} else {
    // GET request - current settings fetch
    $settings = getSettings($conn);
}

// Default values merge
$settings = array_merge([
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => '587',
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_encryption' => 'tls',
    'sender_name' => 'නෙත්සිත ජෝතිශ්‍ය සේවය',
    'sender_email' => ''
], $settings);
?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMTP සැකසුම් - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- css-main.css file එක නොමැති බැවින්, අවශ්‍ය styling මෙහි inline කර ඇත. -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;700&display=swap');
        
        body {
            font-family: 'Noto Sans Sinhala', sans-serif;
            background-color: #f4f7f6;
            padding: 20px;
        }

        .settings-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .page-header {
            background: linear-gradient(135deg, #1e90ff, #00bfff);
            color: white;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .page-header h2 {
            margin: 0 0 5px 0;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
        }
        
        .page-header p {
            margin: 0;
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .settings-card {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        /* Alerts */
        .alert-custom {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 700;
            display: flex;
            align-items: center;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .me-2 { margin-right: 0.5rem; }

        /* Warning Box */
        .warning-box {
            display: flex;
            align-items: flex-start;
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .warning-box .fas {
            font-size: 1.5rem;
            margin-right: 15px;
            color: #ffc107;
        }

        .warning-text strong {
            display: block;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        /* Form Styling */
        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e90ff;
            border-bottom: 2px solid #e0f0ff;
            padding-bottom: 10px;
            margin: 30px 0 20px 0;
            display: flex;
            align-items: center;
        }
        .section-title .fas {
            margin-right: 10px;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 15px;
        }

        .form-group {
            flex: 1 1 300px; /* Flexible width for responsiveness */
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
        }
        .form-label .fas {
            margin-right: 5px;
            color: #999;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
            box-sizing: border-box; /* Padding is included in width */
            transition: border-color 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: #1e90ff;
            outline: none;
            box-shadow: 0 0 5px rgba(30, 144, 255, 0.3);
        }

        /* Buttons */
        .btn-submit {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 700;
            transition: background-color 0.3s, transform 0.1s;
            margin-top: 20px;
            display: flex;
            align-items: center;
        }

        .btn-submit:hover {
            background-color: #218838;
        }
        
        .btn-submit .fas {
            margin-right: 8px;
        }
        
        .btn-test {
            background-color: #ffc107;
            color: #333;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 700;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        
        .btn-test:hover {
            background-color: #e0a800;
        }
        
        .btn-test .fas {
            margin-right: 8px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="settings-container">
        <div class="page-header">
            <h2><i class="fas fa-envelope-open-text me-2"></i>SMTP සැකසුම්</h2>
            <p>Email යැවීම සඳහා SMTP සැකසුම් කළමනාකරණය කරන්න</p>
        </div>

        <?php echo $message; ?>

        <div class="settings-card">
            <!-- Warning -->
            <div class="warning-box">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="warning-text">
                    <strong>වැදගත්!</strong><br>
                    Gmail භාවිතා කරන්නේ නම්, සාමාන්‍ය මුරපදය වෙනුවට <strong>App Password</strong> භාවිතා කරන්න. 
                    Google Account → Security → 2-Step Verification → App Passwords → Create App Password.
                    <br><br>
                    <span style="font-weight:bold; color:red;">ආරක්ෂක අවවාදය:</span> මෙම මුරපදය දත්ත ගබඩාවේ ගබඩා කර ඇත්තේ සරල පාඨ ලෙසය. ඔබේ යෙදුමේ ආරක්ෂාව තහවුරු කරන්න.
                </div>
            </div>

            <form method="POST" action="">
                <div class="section-title">
                    <i class="fas fa-user"></i>
                    යවන්නාගේ තොරතුරු
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-signature"></i> යවන්නාගේ නම</label>
                        <input type="text" class="form-control" name="sender_name" 
                               value="<?php echo htmlspecialchars($settings['sender_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-at"></i> යවන්නාගේ Email</label>
                        <input type="email" class="form-control" name="sender_email" 
                               value="<?php echo htmlspecialchars($settings['sender_email'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="section-title">
                    <i class="fas fa-server"></i>
                    SMTP Server විස්තර
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-network-wired"></i> SMTP Host</label>
                        <input type="text" class="form-control" name="smtp_host" 
                               value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-hashtag"></i> SMTP Port</label>
                        <input type="number" class="form-control" name="smtp_port" 
                               value="<?php echo htmlspecialchars($settings['smtp_port'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-shield-alt"></i> Encryption</label>
                        <select class="form-select" name="smtp_encryption" required>
                            <option value="tls" <?php echo ($settings['smtp_encryption'] ?? '') == 'tls' ? 'selected' : ''; ?>>TLS (587)</option>
                            <option value="ssl" <?php echo ($settings['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : ''; ?>>SSL (465)</option>
                        </select>
                    </div>
                </div>

                <div class="section-title"><i class="fas fa-key"></i> සත්‍යාපන තොරතුරු</div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-envelope"></i> Username</label>
                        <input type="text" class="form-control" name="smtp_username" 
                               value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-lock"></i> Password / App Password</label>
                        <input type="password" class="form-control" name="smtp_password" 
                               value="<?php echo htmlspecialchars($settings['smtp_password'] ?? ''); ?>" required>
                    </div>
                </div>

                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> සැකසුම් සුරකින්න</button>

                <div class="test-section" style="margin-top:20px;">
                    <a href="test_email.php" class="btn-test"><i class="fas fa-paper-plane"></i> Test Email යවන්න</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

<?php include('includes/footer.php'); ?>