<?php
session_start();
// Clear all geo cache
foreach ($_SESSION as $key => $value) {
    if (strpos($key, 'geo_') === 0) {
        unset($_SESSION[$key]);
    }
}
echo "✅ Cache cleared! Now test again.";
?>