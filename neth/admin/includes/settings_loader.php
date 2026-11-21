<?php
/**
 * නෙත්සිත ජෝතිශ්‍ය සේවය - පොදු සැකසුම් Load කරන්නා (Settings Loader)
 * Nethsitha Jyothishya Sewaya - Application Settings Loader
 *
 * This file loads all application settings from the 'app_settings' table into the global $app_settings array.
 * If database connection fails, default values are used as fallback.
 *
 * Prerequisites: db_connection.php must be included before this file ($conn object must exist).
 *
 * Usage: Simply include this file after db_connection.php, then access settings via $app_settings array.
 * Example: echo $app_settings['site_title'];
 */

// ============================================
// DEFAULT SETTINGS CONFIGURATION
// ============================================

$app_settings_defaults = [
    
    // ----------------------------------------
    // General & Site Identity
    // ----------------------------------------
    'site_title' => 'නෙත්සිත ජෝතිශ්‍ය සේවය - විශ්වාසනීය ජ්‍යෝතිෂ සේවාව',
    
    // ----------------------------------------
    // Contact Information
    // ----------------------------------------
    'contact_email' => 'info@nethsitha.lk',
    'contact_phone' => '+94 71 123 4567',
    'contact_address' => '123, ප්‍රධාන පාර, කොළඹ',
    'map_embed_code' => '',
    
    // ----------------------------------------
    // Social Media Links
    // ----------------------------------------
    'social_facebook' => 'https://facebook.com/nethsitha',
    'social_whatsapp' => '94711234567',
    'social_instagram' => 'https://instagram.com/nethsitha',
    'social_youtube' => 'https://youtube.com/nethsitha',
    
    // ----------------------------------------
    // About Page Content
    // ----------------------------------------
    'about_profile_image' => 'uploads/default_guru.png', // ✅ ROOT සිට path එක
    'about_profile_name' => 'ජ්‍යෝතිෂවේදී ඒ. බී. සී. පෙරේරා',
    'about_profile_title' => 'ප්‍රධාන ජ්‍යෝතිෂවේදී',
    'about_description_short' => 'පාරම්පරික ජ්‍යෝතිෂ දැනුම නවීන සමාජයට ගැලපෙන අයුරින් ලබාදීම.',
    'about_experience_years' => '10+',
    
    // ----------------------------------------
    // SEO Configuration
    // ----------------------------------------
    'meta_description' => 'ඔබගේ ජ්‍යොතිෂ්‍යමය අවශ්‍යතා සඳහා විශ්වාසනීය සහ නිවැරදි සේවාව.',
    'meta_keywords' => 'ජෝතිශ්‍ය, කේන්දර, ශ්‍රී ලංකා, වාස්තු',
    
    // ----------------------------------------
    // Site Images & Branding
    // ----------------------------------------
    'site_logo_path' => 'uploads/default_logo.png',       // ✅ ROOT සිට
    'site_favicon_path' => 'uploads/default_favicon.ico', // ✅ ROOT සිට
    'og_image_path' => 'uploads/default_og_image.png',    // ✅ ROOT සිට
];

// ============================================
// DATABASE SETTINGS RETRIEVAL
// ============================================

// Initialize empty array for database settings
$db_settings = [];

// Validate database connection
if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
    
    try {
        // Prepare SQL query to fetch all settings
        $sql = "SELECT setting_key, setting_value FROM app_settings";
        
        if ($stmt = $conn->prepare($sql)) {
            
            // Execute query
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                
                // Fetch all settings into associative array
                while ($row = $result->fetch_assoc()) {
                    $db_settings[$row['setting_key']] = $row['setting_value'];
                }
                
                $stmt->close();
                
            } else {
                // Log execution error
                error_log("Settings Loader - SQL Execution Failed: " . $stmt->error);
            }
            
        } else {
            // Log preparation error
            error_log("Settings Loader - SQL Prepare Failed: " . $conn->error);
        }
        
    } catch (Exception $e) {
        // Log any unexpected errors
        error_log("Settings Loader - Exception: " . $e->getMessage());
    }
    
} else {
    // Log connection failure
    error_log("Settings Loader - Database connection not established or failed. Using default settings only.");
}

// ============================================
// MERGE & FINALIZE SETTINGS
// ============================================

/**
 * Merge database settings with defaults.
 * Database values override defaults, ensuring fallback behavior if DB is unavailable.
 */
$app_settings = array_merge($app_settings_defaults, $db_settings);

// ============================================
// HELPER FUNCTIONS (Optional)
// ============================================

/**
 * Get a specific setting value with optional default
 * @param string $key Setting key
 * @param mixed $default Default value if key not found
 * @return mixed Setting value or default
 */
function get_setting($key, $default = null) {
    global $app_settings;
    return $app_settings[$key] ?? $default;
}

/**
 * Check if a setting exists
 * @param string $key Setting key
 * @return bool True if setting exists
 */
function has_setting($key) {
    global $app_settings;
    return isset($app_settings[$key]);
}

/**
 * Get setting with HTML escaping (safe for output)
 * @param string $key Setting key
 * @param mixed $default Default value if key not found
 * @return string Escaped setting value
 */
function get_setting_safe($key, $default = '') {
    $value = get_setting($key, $default);
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// ============================================
// USAGE EXAMPLES (Commented Out)
// ============================================

/*
// Access settings directly from array:
echo $app_settings['site_title'];

// Or use helper functions:
echo get_setting('site_title');
echo get_setting_safe('contact_email');

// Check if setting exists:
if (has_setting('map_embed_code')) {
    echo $app_settings['map_embed_code'];
}

// Use in HTML with escaping:
<title><?php echo get_setting_safe('site_title'); ?></title>
<meta name="description" content="<?php echo get_setting_safe('meta_description'); ?>">
*/

// ============================================
// DEBUG OUTPUT (Enable only for debugging)
// ============================================

/*
// Uncomment below lines to debug loaded settings:
echo "<pre>";
echo "=== DEFAULT SETTINGS ===\n";
print_r($app_settings_defaults);
echo "\n=== DATABASE SETTINGS ===\n";
print_r($db_settings);
echo "\n=== FINAL MERGED SETTINGS ===\n";
print_r($app_settings);
echo "</pre>";
*/
?>