<?php

/**

 * දත්ත සමුදා සම්බන්ධතා ගොනුව - db_connection.php

 * සියලුම PHP පිටු වලට සම්බන්ධතාවය සපයයි.

 */

// දත්ත සමුදා සම්බන්ධතා තොරතුරු

// ⚠️ සජීවී සේවාදායකයේ (Live Server) ඔබගේ සත්‍ය තොරතුරු යොදන්න.

$servername = "localhost";

$username = "sampath_nethsitha"; 

$password = "nethsitha1";       

$dbname = "sampath_nethsitha_db"; 

// සම්බන්ධතාවය නිර්මාණය කිරීම

$conn = new mysqli($servername, $username, $password, $dbname);

// සම්බන්ධතාවය පරීක්ෂා කිරීම

if ($conn->connect_error) {

    // සත්‍ය දෝෂය පෙන්වීම (ආරක්ෂාව සඳහා සජීවී සේවාදායකයේදී වෙනස් කළ යුතුය)

    die("සම්බන්ධතාවය අසාර්ථකයි: " . $conn->connect_error);

}

// 💡 සිංහල (UTF-8) අකුරු නිවැරදිව කියවීමට සහ ලිවීමට Charset සැකසීම

// මෙමගින් "????" ගැටළුව විසඳෙනු ඇත.

if (!$conn->set_charset("utf8mb4")) {

    // චරිත කට්ටලය පැටවීමේ දෝෂයක් ඇත්නම්, එය වාර්තා කිරීම.

    // ඔබට මෙය සම්පූර්ණයෙන්ම ඉවත් කළ හැක.

    // error_log("Error loading character set utf8mb4: " . $conn->error);

}

// මෙම ගොනුව සාර්ථකව ක්‍රියාත්මක වේ.

?>