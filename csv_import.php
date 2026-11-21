<?php
// =======================================
// CSV Import Script for Grammar Data
// =======================================

require_once 'config.php'; // your config file with getDBConnection()

// Database table name — Change this to your actual table name
$table = 'n5_grammar';

// CSV file path
$csvFile = __DIR__ . '/n5_grammar_11_20.csv'; // Make sure this file exists in same folder

// Check if file exists
if (!file_exists($csvFile)) {
    die("❌ CSV File not found: $csvFile");
}

// Open the file
$handle = fopen($csvFile, 'r');
if ($handle === false) {
    die("❌ Unable to open the file: $csvFile");
}

// DB connection
$db = getDBConnection();

// Optional: Truncate table before import (uncomment if needed)
// $db->exec("TRUNCATE TABLE $table");

$importCount = 0;
$errorCount = 0;

// Skip the first line if it's a header
$header = fgetcsv($handle);

// Prepare SQL insert statement
$stmt = $db->prepare("
    INSERT INTO $table (japanese_text, romaji_text, sinhala_meaning)
    VALUES (:japanese, :romaji, :sinhala)
");

while (($row = fgetcsv($handle, 1000, ",")) !== false) {
    // Example: [0]=Japanese, [1]=Romaji, [2]=Sinhala
    $japanese = trim($row[0] ?? '');
    $romaji   = trim($row[1] ?? '');
    $sinhala  = trim($row[2] ?? '');

    if ($japanese === '' && $romaji === '' && $sinhala === '') {
        continue; // skip empty lines
    }

    try {
        $stmt->execute([
            ':japanese' => $japanese,
            ':romaji' => $romaji,
            ':sinhala' => $sinhala
        ]);
        $importCount++;
    } catch (PDOException $e) {
        error_log("Import error: " . $e->getMessage());
        $errorCount++;
    }
}

fclose($handle);

echo "✅ Import completed.<br>";
echo "✔️ Successful inserts: $importCount<br>";
echo "❌ Failed inserts: $errorCount<br>";
?>
