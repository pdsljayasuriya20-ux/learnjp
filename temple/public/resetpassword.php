<?php
declare(strict_types=1);

/**
 * Super Admin Password Reset Utility
 * Temple Dayaka Management System - Version 1.0
 */

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Dynamic Absolute Path Resolution (Fixes No such file or directory error)
$baseDir = __DIR__;
if (file_exists($baseDir . '/config/app.php')) {
    require_once $baseDir . '/config/app.php';
    require_once $baseDir . '/config/database.php';
} elseif (file_exists(dirname($baseDir) . '/config/app.php')) {
    require_once dirname($baseDir) . '/config/app.php';
    require_once dirname($baseDir) . '/config/database.php';
} else {
    die("<h3 style='color:red; font-family:sans-serif;'>System Error: config/app.php file standard path එකෙහි හමු නොවීය!</h3>");
}

try {
    $db = Database::getConnection();

    // Reset කිරීමට අවශ්‍ය Credentials
    $username    = 'superadmin';
    $newPassword = 'TempleAdmin@2026';

    // bcrypt hashing
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);

    // Super Admin පරිශීලකයා ඇත්දැයි පරීක්ෂා කිරීම
    $stmt = $db->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user) {
        // පවතින Account එකෙහි Password සහ Status සක්‍රිය කිරීම
        $updateStmt = $db->prepare("
            UPDATE users 
            SET password = :password, status = 'active', role = 'superadmin' 
            WHERE username = :username
        ");
        $updateStmt->execute([
            'password' => $hash,
            'username' => $username
        ]);

        echo "<div style='padding:25px; font-family:sans-serif; background:#d4edda; color:#155724; border:2px solid #c3e6cb; margin:30px auto; max-width:600px; border-radius:8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
                <h2 style='margin-top:0;'>✅ Password Reset Successful!</h2>
                <p style='font-size:16px;'>Super Admin ගිණුමේ මුරපදය සාර්ථකව යාවත්කාලීන විය.</p>
                <hr style='border:0; border-top:1px solid #c3e6cb;'>
                <p><strong>Username:</strong> <code style='background:#fff; padding:3px 8px; border-radius:4px;'>superadmin</code></p>
                <p><strong>New Password:</strong> <code style='background:#fff; padding:3px 8px; border-radius:4px;'>TempleAdmin@2026</code></p>
                <br>
                <a href='" . APP_URL . "/public/login.php' style='display:inline-block; padding:10px 20px; background:#28a745; color:#fff; text-decoration:none; border-radius:5px; font-weight:bold;'>Login Page එකට යන්න →</a>
              </div>";
    } else {
        // Account එක නැත්නම් අලුතින් Insert කිරීම
        $insertStmt = $db->prepare("
            INSERT INTO users (id, username, email, password, role, status, created_at) 
            VALUES (1, :username, 'admin@temple.lk', :password, 'superadmin', 'active', NOW())
        ");
        $insertStmt->execute([
            'username' => $username,
            'password' => $hash
        ]);

        echo "<div style='padding:25px; font-family:sans-serif; background:#d4edda; color:#155724; border:2px solid #c3e6cb; margin:30px auto; max-width:600px; border-radius:8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
                <h2 style='margin-top:0;'>✅ Super Admin Account Created!</h2>
                <p style='font-size:16px;'>නැවුම් Super Admin ගිණුම සාර්ථකව නිර්මාණය විය.</p>
                <hr style='border:0; border-top:1px solid #c3e6cb;'>
                <p><strong>Username:</strong> <code style='background:#fff; padding:3px 8px; border-radius:4px;'>superadmin</code></p>
                <p><strong>New Password:</strong> <code style='background:#fff; padding:3px 8px; border-radius:4px;'>TempleAdmin@2026</code></p>
                <br>
                <a href='" . APP_URL . "/public/login.php' style='display:inline-block; padding:10px 20px; background:#28a745; color:#fff; text-decoration:none; border-radius:5px; font-weight:bold;'>Login Page එකට යන්න →</a>
              </div>";
    }

} catch (Exception $e) {
    echo "<div style='padding:25px; font-family:sans-serif; background:#f8d7da; color:#721c24; border:2px solid #f5c6cb; margin:30px auto; max-width:600px; border-radius:8px;'>
            <h2 style='margin-top:0;'>❌ Error Occurred</h2>
            <p><strong>Details:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
          </div>";
}