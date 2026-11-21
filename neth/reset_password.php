<?php
session_start();
include('db_connection.php');

$message = '';

// Token check
if(!isset($_GET['token'])){
    die("Invalid request");
}

$token = $_GET['token'];

// Verify token
$stmt = $conn->prepare("SELECT * FROM client WHERE reset_token=? AND token_expiry >= NOW() LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows != 1){
    die("Invalid or expired token.");
}

$user = $result->fetch_assoc();

// Handle new password submission
if(isset($_POST['password']) && isset($_POST['confirm_password'])){
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if($password !== $confirm_password){
        $message = "Password and Confirm Password එකට සමාන විය යුතුයි.";
    } else {
        // Hash password (recommended)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Update password and clear token
        $update = $conn->prepare("UPDATE client SET password=?, reset_token=NULL, token_expiry=NULL WHERE id=?");
        $update->bind_param("si", $hashed_password, $user['id']);
        if($update->execute()){
            $message = "Password සාර්ථකව update විය. ඔබ දැන් login වෙන්න පුළුවන්.";
        } else {
            $message = "Password update කිරීමේදී දෝෂයක් සිදු විය.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>
<h2>Reset Password</h2>
<?php if($message) echo "<p style='color:red;'>$message</p>"; ?>
<form method="POST">
    <label>New Password:</label><br>
    <input type="password" name="password" required><br><br>
    <label>Confirm Password:</label><br>
    <input type="password" name="confirm_password" required><br><br>
    <button type="submit">Update Password</button>
</form>
</body>
</html>
