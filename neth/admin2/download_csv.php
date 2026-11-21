<?php
// Session ආරම්භ කිරීම
session_start();

// පරිපාලක ආරක්ෂක පරීක්ෂාව
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// දත්ත සමුදා සම්බන්ධතාවය ඇතුළත් කිරීම
include('../db_connection.php');

if (!isset($conn) || $conn->connect_error) {
    die("දත්ත සමුදාය සම්බන්ධ කිරීමේ දෝෂය. CSV Backup කළ නොහැක.");
}

// --------------------------------------------------------
// CSV Backup Logic (ZIP ගොනුවක් ලෙස)
// --------------------------------------------------------

// තාවකාලික ෆෝල්ඩරය ව්‍යාපෘතියේම සකසන්න (MAMP දෝෂ මඟහරවා ගැනීමට)
// ⚠️ අවවාදයයි: මෙම 'temp_backups' ෆෝල්ඩරයට ලිවීමේ අවසර (777) ලබා දී තිබිය යුතුය.
$temp_dir = __DIR__ . '/temp_backups';

// ෆෝල්ඩරය නොමැති නම් සාදන්න
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

// ZIP ගොනුව සඳහා මාර්ගය සහ නම
$zip_file = $temp_dir . '/csv_backup_' . uniqid() . '.zip'; 

$zip = new ZipArchive();
if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("ZIP ගොනුව නිර්මාණය කිරීමේ දෝෂය: " . $zip_file);
}

$tables = array();
$result = $conn->query("SHOW TABLES");
if ($result) {
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
}

$temp_csv_files = []; 

foreach ($tables as $table) {
    $result = $conn->query("SELECT * FROM " . $table);
    if (!$result) continue; 

    // තාවකාලික CSV ගොනුවක් නිර්මාණය කිරීම
    $csv_temp_file = $temp_dir . '/' . $table . '_' . uniqid() . '.csv';
    $temp_csv_files[] = $csv_temp_file; 
    
    // CSV ගොනුව UTF-8 BOM සමඟ විවෘත කිරීම (Excel ගැටළු මඟහරවා ගැනීමට)
    $fp = fopen($csv_temp_file, 'w');
    fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF)); // Add UTF-8 BOM

    // ශීර්ෂ (Headers) CSV වෙත ලිවීම
    $headers = [];
    while ($field = $result->fetch_field()) {
        $headers[] = $field->name;
    }
    fputcsv($fp, $headers);

    // දත්ත CSV වෙත ලිවීම
    while ($row = $result->fetch_assoc()) {
        fputcsv($fp, $row);
    }

    fclose($fp);
    
    // CSV ගොනුව ZIP එකට එකතු කිරීම
    $zip->addFile($csv_temp_file, $table . '.csv'); 
}

// ZIP ගොනුව වසා දැමීම (Line 67 දෝෂය නිවැරදි කරනු ඇතැයි අපේක්ෂා කෙරේ)
if (!$zip->close()) {
    // දෝෂයක් ඇත්නම්, cleanup කර දෝෂය පෙන්වන්න
    foreach ($temp_csv_files as $file) {
        if (file_exists($file)) unlink($file);
    }
    // ZIP ගොනුව ඉවත් කිරීමට පෙර
    if (file_exists($zip_file)) unlink($zip_file);
    die("ZIP ගොනුව වසා දැමීමේ දෝෂයකි.");
}

// --------------------------------------------------------
// ZIP ගොනුව Browser එකට යැවීම
// --------------------------------------------------------

// දත්ත සමුදා නාමය ලබා ගැනීම (සම්බන්ධතාවය වසා දැමීමට පෙර)
$db_name = $conn->current_db ?? 'database';
$db_name_clean = preg_replace('/[^a-zA-Z0-9_]/', '', $conn->real_escape_string($db_name));
$backup_file_name = ($db_name_clean ?: 'database') . '_csv_backup_' . date('Ymd_His') . '.zip';


// Headers යැවීම
header('Content-Type: application/zip');
header('Content-Length: ' . filesize($zip_file));
header('Content-Disposition: attachment; filename="' . $backup_file_name . '"');

// ගොනුව කියවා පිටතට යැවීම
readfile($zip_file);

// --------------------------------------------------------
// අවසන් Cleanup Logic (සම්බන්ධතාවය සහ තාවකාලික ගොනු වසා දැමීම)
// --------------------------------------------------------

// දත්ත සමුදා සම්බන්ධතාවය වසා දැමීම (දැන් ආරක්ෂිතයි)
$conn->close(); // Line 73 දෝෂය නිවැරදි කර ඇත.

// තාවකාලික ZIP ගොනුව ඉවත් කිරීම
if (file_exists($zip_file)) {
    unlink($zip_file);
}

// තාවකාලික CSV ගොනු ඉවත් කිරීම
foreach ($temp_csv_files as $file) {
    if (file_exists($file)) unlink($file);
}

exit;
?>