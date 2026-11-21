<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔐 Password Hash Generator</h2>";
echo "<hr>";

$password = 'admin123';

echo "<h3>Server Information:</h3>";
echo "PHP Version: <strong>" . phpversion() . "</strong><br>";
echo "Password: <strong>$password</strong><br>";
echo "<hr>";

echo "<h3>Generating New Hash...</h3>";

// Generate hash
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h4>New Hash Generated:</h4>";
echo "<textarea style='width: 100%; height: 80px; padding: 10px; font-family: monospace;'>$hash</textarea>";

echo "<hr>";

// Test it
echo "<h3>Testing Hash:</h3>";
if (password_verify($password, $hash)) {
    echo "✅ <span style='color: green; font-size: 18px;'><strong>Hash works perfectly!</strong></span><br><br>";
    
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; border-left: 5px solid #ffc107; margin: 20px 0;'>";
    echo "<h4 style='margin: 0 0 15px 0;'>📋 Follow These Steps:</h4>";
    echo "<ol style='line-height: 1.8;'>";
    echo "<li>Copy the hash from the textarea above</li>";
    echo "<li>Go to phpMyAdmin</li>";
    echo "<li>Select database: <strong>sampath_learnjp</strong></li>";
    echo "<li>Click on table: <strong>admin_users</strong></li>";
    echo "<li>Click <strong>Edit</strong> on admin user</li>";
    echo "<li>Paste the hash in <strong>password_hash</strong> field</li>";
    echo "<li>Click <strong>Go</strong> to save</li>";
    echo "</ol>";
    echo "</div>";
    
    // Auto-update option
    echo "<hr>";
    echo "<h3>Or Click Here to Auto-Update:</h3>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='new_hash' value='" . htmlspecialchars($hash) . "'>";
    echo "<button type='submit' name='auto_update' style='background: linear-gradient(45deg, #ff006e, #8338ec); padding: 15px 30px; border: none; border-radius: 50px; color: white; font-size: 16px; font-weight: bold; cursor: pointer;'>🚀 Auto-Update Password Now</button>";
    echo "</form>";
    
} else {
    echo "❌ <span style='color: red;'>Hash test failed!</span><br>";
}

// Handle auto-update
if (isset($_POST['auto_update'])) {
    echo "<hr>";
    echo "<h3>🔄 Auto-Updating Password...</h3>";
    
    try {
        require_once 'config.php';
        $db = getDBConnection();
        
        $newHash = $_POST['new_hash'];
        
        $stmt = $db->prepare("UPDATE admin_users SET password_hash = ? WHERE username = 'admin'");
        $result = $stmt->execute([$newHash]);
        
        if ($result) {
            echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; border-left: 5px solid #28a745; margin: 20px 0;'>";
            echo "✅ <h3 style='color: #155724; margin: 0 0 10px 0;'>Password Updated Successfully!</h3>";
            echo "<p style='margin: 5px 0;'>Username: <strong>admin</strong></p>";
            echo "<p style='margin: 5px 0;'>Password: <strong>admin123</strong></p>";
            echo "<br>";
            
            // Test the saved hash
            $stmt = $db->prepare("SELECT password_hash FROM admin_users WHERE username = 'admin'");
            $stmt->execute();
            $savedHash = $stmt->fetch()['password_hash'];
            
            if (password_verify($password, $savedHash)) {
                echo "<p style='color: green; font-weight: bold;'>✅ Verified: Password works correctly!</p>";
                echo "<br>";
                echo "<a href='login.php' style='background: linear-gradient(45deg, #ff006e, #8338ec); padding: 15px 30px; border-radius: 50px; color: white; text-decoration: none; display: inline-block; font-weight: bold;'>Login Now →</a>";
            } else {
                echo "<p style='color: red; font-weight: bold;'>⚠️ Warning: Hash may not work properly</p>";
            }
            
            echo "</div>";
        } else {
            echo "<p style='color: red;'>❌ Update failed!</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<h4>⚠️ Security:</h4>";
echo "<p style='color: red; font-weight: bold;'>DELETE this file after fixing the password!</p>";
?>