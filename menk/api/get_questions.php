<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$host = 'localhost';
$db   = 'sampath_learnjp';
$user = 'sampath_drive';
$pass = '6BBT5vGkD0d9';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    $category = isset($_GET['category']) ? $_GET['category'] : '';

    // Check if category column exists, if not fallback safely
    if ($category && $category !== 'All') {
        $stmt = $pdo->prepare("SELECT id, category, question_en, question_si, img, correct_answer, explanation_en, explanation_si FROM questions WHERE category = ?");
        $stmt->execute([$category]);
    } else {
        $stmt = $pdo->query("SELECT id, category, question_en, question_si, img, correct_answer, explanation_en, explanation_si FROM questions");
    }
    
    $questions = $stmt->fetchAll();

    // Fetch distinct categories safely
    try {
        $catStmt = $pdo->query("SELECT DISTINCT category FROM questions WHERE category IS NOT NULL");
        $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (\Exception $e) {
        $categories = ['General Rules'];
    }

    echo json_encode([
        "status" => "success",
        "categories" => $categories,
        "total" => count($questions),
        "data" => $questions
    ], JSON_UNESCAPED_UNICODE);

} catch (\PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>