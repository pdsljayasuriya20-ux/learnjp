<?php
$host = 'localhost';
$db   = 'sampath_learnjp';
$user = 'sampath_drive';
$pass = '6BBT5vGkD0d9';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// සියලුම ප්‍රශ්න ලබා ගැනීම
$stmt = $pdo->query("SELECT id, question_en FROM questions");
$questions = $stmt->fetchAll();

foreach ($questions as $q) {
    $id = $q['id'];
    $text = strtolower($q['question_en']);
    $category = "General Rules"; // Default category

    // Keywords අනුව Category එක තීරණය කිරීම
    if (strpos($text, 'speed') !== false || strpos($text, 'fast') !== false) {
        $category = "Speed Limits";
    } elseif (strpos($text, 'park') !== false || strpos($text, 'stop') !== false) {
        $category = "Stopping & Parking";
    } elseif (strpos($text, 'sign') !== false || strpos($text, 'signal') !== false || strpos($text, 'light') !== false) {
        $category = "Traffic Signs & Signals";
    } elseif (strpos($text, 'alcohol') !== false || strpos($text, 'drink') !== false) {
        $category = "DUI & Safety";
    } elseif (strpos($text, 'highway') !== false || strpos($text, 'expressway') !== false) {
        $category = "Highway Driving";
    } elseif (strpos($text, 'pedestrian') !== false || strpos($text, 'child') !== false) {
        $category = "Pedestrians & School Zones";
    }

    // Database එක update කිරීම
    $updateStmt = $pdo->prepare("UPDATE questions SET category = ? WHERE id = ?");
    $updateStmt->execute([$category, $id]);
}

echo "🎉 සියලුම ප්‍රශ්න සාර්ථකව Categories වලට බෙදා වෙන් කරන ලදී!";
?>