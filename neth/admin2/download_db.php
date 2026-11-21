<?php
// Session ආරම්භ කිරීම
session_start();

// පරිපාලක ආරක්ෂක පරීක්ෂාව
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// දත්ත සමුදා සම්බන්ධතාවය ඇතුළත් කිරීම (ඔබේ මාර්ගය නිවැරදිදැයි පරීක්ෂා කරන්න)
include('../db_connection.php');

// ⚠️ වැදගත්: මෙම විචල්‍යයන් ඔබේ db_connection.php ගොනුව තුළ අර්ථ දක්වා නොමැති නම්,
// ඔබට ඒවා මෙහි අතින් සැකසීමට සිදුවේ.
// උදාහරණය:
// $DB_HOST = 'localhost';
// $DB_USER = 'root';
// $DB_PASS = '';
// $DB_NAME = 'nethsitha_db'; 

// $conn නොමැති නම්, සම්බන්ධතාවය නැවත ඇති කිරීමට උත්සාහ කරන්න (ආරක්ෂාව සඳහා)
if (!isset($conn) || $conn->connect_error) {
    // ⚠️ අවවාදයයි: සත්‍ය සේවාදායකයේදී $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME භාවිතා කරන්න.
    // ඔබගේ ව්‍යාපෘතියේ $conn වස්තුව නැතිනම්, මෙහිදී ඔබට සැබෑ සම්බන්ධතා තොරතුරු ඇතුළත් කිරීමට සිදුවේ.
    die("දත්ත සමුදාය සම්බන්ධ කිරීමේ දෝෂය. Backup කළ නොහැක.");
}

// Backup Logic
$tables = array();
$result = $conn->query("SHOW TABLES");
if ($result) {
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
} else {
    die("වගු ලැයිස්තුව ලබා ගැනීමේ දෝෂය.");
}

$return = '';
foreach ($tables as $table) {
    $result = $conn->query("SELECT * FROM " . $table);
    if (!$result) continue; // දත්ත ලබා ගැනීමට නොහැකි වුවහොත් මඟ හරින්න
    
    $num_fields = $result->field_count;

    // Table Creation SQL
    $return .= 'DROP TABLE IF EXISTS ' . $table . ';';
    $row2_result = $conn->query("SHOW CREATE TABLE " . $table);
    if ($row2_result) {
        $row2 = $row2_result->fetch_row();
        $return .= "\n" . $row2[1] . ";\n";
    }

    // Data Insertion SQL
    while ($row = $result->fetch_row()) {
        $return .= 'INSERT INTO ' . $table . ' VALUES(';
        for ($j = 0; $j < $num_fields; $j++) {
            $value = $row[$j];
            
            // NULL අගයන් හැසිරවීම
            if ($value === null) {
                $return .= 'NULL';
            } else {
                // විශේෂ අක්ෂර හැසිරවීම
                $value = addslashes($value);
                $value = str_replace("\n", "\\n", $value);
                $return .= '"' . $value . '"';
            }
            
            if ($j < ($num_fields - 1)) {
                $return .= ',';
            }
        }
        $return .= ");\n";
    }
    $return .= "\n\n\n";
}

// ගොනු නාමය සකස් කිරීම
$db_name_clean = preg_replace('/[^a-zA-Z0-9_]/', '', $conn->real_escape_string($conn->current_db));
$backup_file_name = ($db_name_clean ?: 'database') . '_backup_' . date('Ymd_His') . '.sql';

// Headers සකස් කිරීම (Browser එක හරහා ගොනුව බාගත කිරීම සඳහා)
header('Content-Type: application/octet-stream');
header('Content-Transfer-Encoding: Binary');
header('Content-disposition: attachment; filename="' . $backup_file_name . '"');
echo $return;

// දත්ත සමුදා සම්බන්ධතාවය වසා දැමීම
$conn->close();
exit;
?>