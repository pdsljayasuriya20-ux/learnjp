<?php
// ඔබට සකස් කිරීමට අවශ්‍ය නව මුරපදය මෙහි ඇතුළු කරන්න
$new_password = "admin1"; 
$new_hash = password_hash($new_password, PASSWORD_DEFAULT);
echo $new_hash;
?>