<?php
// Note: Assumes includes/header.php provides a valid $conn (mysqli connection)
include('includes/header.php');

// ===================================================================================
// CRITICAL DEBUG FOR LOCALHOST: These settings are enabled to display the fatal 
// PHP error that is causing the 500 Internal Server Error. 
// REMOVE or comment out these lines after the issue is resolved.
ini_set('display_errors', 1);
error_reporting(E_ALL);
// ===================================================================================

$message = '';

/**
 * Database settings fetch (Redefined for independence)
 * @param mysqli $conn
 * @return array
 */
function getSettings($conn){
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM app_settings");
    if ($stmt === false) {
        error_log("Failed to prepare getSettings statement in test_email.php: " . $conn->error);
        return [];
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while($row=$result->fetch_assoc()){
        $data[$row['setting_key']]=$row['setting_value'];
    }
    $stmt->close();
    return $data;
}

$settings = getSettings($conn);

// Set default values for safety
$settings = array_merge([
    'smtp_host' => '', 'smtp_port' => '', 'smtp_username' => '', 
    'smtp_password' => '', 'smtp_encryption' => '', 
    'sender_name' => 'Default Sender', 'sender_email' => 'default@example.com'
], $settings);


if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['test_email'])){
    $test_email = $conn->real_escape_string($_POST['test_email']);
    
    if (!filter_var($test_email, FILTER_VALIDATE_EMAIL)){
        $message = '<div class="alert alert-danger">වලංගු Email එකක් ඇතුළත් කරන්න.</div>';
    } else {
        // --- File Path Check ---
        // Based on the file structure image, the path is relative to the current file (admin/test_email.php).
        $phpmailer_path = 'PHPMailer/src/PHPMailer.php';

        if(file_exists($phpmailer_path)){
            require_once 'PHPMailer/src/PHPMailer.php';
            require_once 'PHPMailer/src/SMTP.php';
            require_once 'PHPMailer/src/Exception.php';

            // Check if essential settings are present
            if (empty($settings['smtp_host']) || empty($settings['smtp_username']) || empty($settings['smtp_password'])) {
                $message = '<div class="alert alert-danger">SMTP settings (Host, Username, Password) සම්පූර්ණ නැත. කරුණාකර settings page එකේදී ඒවා යාවත්කාලීන කරන්න.</div>';
            } else {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                try{
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = $settings['smtp_host'];
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $settings['smtp_username'];
                    $mail->Password   = $settings['smtp_password'];
                    $mail->SMTPSecure = $settings['smtp_encryption'] === 'ssl' ? 'ssl' : 'tls';
                    $mail->Port       = (int)$settings['smtp_port'];
                    $mail->CharSet    = 'UTF-8';
                    
                    // ===================================================================
                    // CRITICAL FIX: Set Timeout to prevent PHP execution expiry
                    $mail->Timeout    = 30; 
                    
                    // CRITICAL DEBUG: Enable detailed SMTP output.
                    // Set to 0 after fixing the error.
                    $mail->SMTPDebug  = 2; 
                    $mail->Debugoutput = 'html';
                    // ===================================================================

                    // Recipients
                    $mail->setFrom($settings['sender_email'], $settings['sender_name']);
                    $mail->addAddress($test_email);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'SMTP Test Email - සාර්ථක පරීක්ෂණය';
                    $mail->Body    = '<h2>නෙත්සිත ජෝතිශ්‍ය සේවය - Test Email</h2>ඔබගේ SMTP settings සාර්ථකව ක්‍රියා කරයි! මෙම පණිවිඩය සාර්ථකව යවන ලදී.';
                    $mail->AltBody = 'ඔබගේ SMTP settings සාර්ථකව ක්‍රියා කරයි! මෙම පණිවිඩය සාර්ථකව යවන ලදී.';


                    $mail->send();
                    $message = '<div class="alert alert-success">Test email සාර්ථකව යවන ලදී: '.$test_email.'</div>';
                }catch(PHPMailer\PHPMailer\Exception $e){
                    // Display the full PHPMailer error message if it fails gracefully
                    $message = '<div class="alert alert-danger">Email යැවීමට අසමත් විය. PHPMailer Error: '.$mail->ErrorInfo.'</div>';
                }
            }
        } else {
            // This message will appear if the file path is incorrect.
            $message = '<div class="alert alert-danger">PHPMailer files නොපැමිණි බව හෝ path වැරදිය. (අපේක්ෂිත ස්ථානය: '.$phpmailer_path.')</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Required styling is inline for portability -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;700&display=swap');
        
        body {
            font-family: 'Noto Sans Sinhala', sans-serif;
            background-color: #f4f7f6;
            padding: 20px;
        }
        
        .test-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .test-container h2 {
            font-size: 1.8rem;
            color: #1e90ff;
            margin-bottom: 25px;
            border-bottom: 2px solid #e0f0ff;
            padding-bottom: 15px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 700;
            text-align: left;
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

        .test-container form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: center;
        }
        
        .test-container input[type="email"] {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        .test-container button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 700;
            transition: background-color 0.3s, transform 0.1s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }

        .test-container button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>

<div class="test-container">
    <h2><i class="fas fa-paper-plane me-2"></i>Test Email යවන්න</h2>
    <?php echo $message; ?>
    <form method="POST" action="">
        <input type="email" name="test_email" placeholder="your-email@gmail.com" required>
        <button type="submit"><i class="fas fa-rocket me-2"></i>Send Test Email</button>
    </form>
</div>

</body>
</html>

<?php include('includes/footer.php'); ?>