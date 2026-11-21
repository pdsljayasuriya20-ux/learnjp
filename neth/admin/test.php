<?php
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400&display=swap" rel="stylesheet">
<style>
body { font-family: 'Noto Sans Sinhala', sans-serif; font-size: 24px; padding: 20px; }
</style>
</head>
<body>
<h1>Direct Sinhala Test:</h1>
<p>නෙත්සිත ජෝතිශ්‍ය සේවය</p>

<?php
include('../db_connection.php');
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

$result = $conn->query("SELECT setting_value FROM app_settings WHERE setting_key = 'site_title'");
$row = $result->fetch_assoc();
?>

<h1>From Database:</h1>
<p><?php echo $row['setting_value']; ?></p>
</body>
</html>