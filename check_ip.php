<?php
require_once 'config.php';

$ip = getClientIP();

echo "<h2>🌐 IP & Location Check</h2>";
echo "<hr>";

echo "<h3>Your IP Address:</h3>";
echo "<strong style='font-size: 24px; color: #ff006e;'>$ip</strong><br><br>";

if ($ip === 'Unknown' || $ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0) {
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px;'>";
    echo "⚠️ <strong>Local/Private IP detected!</strong><br>";
    echo "Geolocation won't work with this IP.<br>";
    echo "This is normal for local testing.<br>";
    echo "On live server with real visitors, it will work fine.";
    echo "</div>";
} else {
    echo "✅ This is a valid public IP address!<br><br>";
    
    // Try to get location
    echo "<h3>Testing Geolocation API...</h3>";
    
    $url = "https://ipapi.co/{$ip}/json/";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($error) {
        echo "❌ <span style='color: red;'>CURL Error: $error</span><br>";
        echo "<strong>Solution:</strong> Contact hosting to enable CURL<br>";
    } else {
        echo "HTTP Code: <strong>$httpCode</strong><br>";
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            
            echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin-top: 20px;'>";
            echo "✅ <strong style='color: #155724;'>Geolocation Working!</strong><br><br>";
            echo "Country: <strong>" . ($data['country_name'] ?? 'Unknown') . " " . ($data['flag'] ?? '') . "</strong><br>";
            echo "City: <strong>" . ($data['city'] ?? 'Unknown') . "</strong><br>";
            echo "Region: <strong>" . ($data['region'] ?? 'Unknown') . "</strong><br>";
            echo "</div>";
            
            echo "<br><h4>Full API Response:</h4>";
            echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 10px; color: #333; overflow-x: auto;'>";
            echo json_encode($data, JSON_PRETTY_PRINT);
            echo "</pre>";
        } elseif ($httpCode === 429) {
            echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px;'>";
            echo "⚠️ <strong>API Rate Limit Exceeded!</strong><br>";
            echo "Free tier: 1000 requests/day<br>";
            echo "Try again tomorrow or use alternative API.";
            echo "</div>";
        } else {
            echo "❌ API returned error code: $httpCode<br>";
            echo "Response: <pre>$response</pre>";
        }
    }
}

echo "<hr>";
echo "<h3>Alternative: Use Different API</h3>";
echo "<p>If ipapi.co doesn't work, we can switch to:</p>";
echo "<ul>";
echo "<li>ip-api.com (free, unlimited for non-commercial)</li>";
echo "<li>geoplugin.net (free, no key needed)</li>";
echo "</ul>";
?>