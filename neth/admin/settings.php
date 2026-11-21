<?php
/**
 * නෙත්සිත ජෝතිශ්‍ය සේවය - පරිපාලක සැකසුම්
 */

// Set UTF-8 for output
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

// Include admin header
include('includes/header.php');

// Verify database connection
if (!isset($conn) || !$conn) {
    die("<div class='alert alert-danger'>දත්ත සමුදා සම්බන්ධතා දෝෂයක්.</div>");
}

// Initialize variables
$general_message = '';
$password_message = '';
$admin_id = (int)($_SESSION['admin_id'] ?? 0);

// Base directory configuration
$BASE_DIR = dirname(__DIR__) . '/';
$upload_dir = $BASE_DIR . 'uploads/';
$upload_web_prefix = '../uploads/'; // For admin folder
$max_file_size = 2097152; // 2MB

// ============================================
// UTILITY FUNCTIONS
// ============================================

function loadAllSettings($conn) {
    $settings = [];
    $result = $conn->query("SELECT setting_key, setting_value FROM app_settings");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings;
}

function updateSetting($conn, $key, $value) {
    // Check if key exists
    $check = $conn->prepare("SELECT setting_key FROM app_settings WHERE setting_key = ?");
    $check->bind_param("s", $key);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();
    
    if ($exists) {
        // Update existing
        $stmt = $conn->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $value, $key);
    } else {
        // Insert new
        $stmt = $conn->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->bind_param("ss", $key, $value);
    }
    
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// ============================================
// POST REQUEST HANDLERS
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Update general settings
    if (isset($_POST['action']) && $_POST['action'] === 'update_general_settings') {
        
        $fields_to_update = [
            'contact_email' => $_POST['contact_email'] ?? '',
            'contact_phone' => $_POST['contact_phone'] ?? '',
            'contact_address' => $_POST['contact_address'] ?? '',
            'map_embed_code' => $_POST['map_embed_code'] ?? '',
            'social_facebook' => $_POST['social_facebook'] ?? '',
            'social_whatsapp' => $_POST['social_whatsapp'] ?? '',
            'social_instagram' => $_POST['social_instagram'] ?? '',
            'social_youtube' => $_POST['social_youtube'] ?? '',
            'about_profile_name' => $_POST['about_profile_name'] ?? '',
            'about_profile_title' => $_POST['about_profile_title'] ?? '',
            'site_title' => $_POST['site_title'] ?? '',
            'meta_description' => $_POST['meta_description'] ?? '',
            'meta_keywords' => $_POST['meta_keywords'] ?? '',
        ];

        $update_successful = true;
        $error_messages = [];
        
        foreach ($fields_to_update as $key => $value) {
            // Sanitize (except map_embed_code)
            $clean_value = ($key === 'map_embed_code') ? trim($value) : htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            
            if (!updateSetting($conn, $key, $clean_value)) {
                $update_successful = false;
                $error_messages[] = "Failed to update {$key}";
            }
        }

        if ($update_successful) {
            $general_message = '<div class="alert alert-success alert-dismissible fade show">
                                    <i class="fas fa-check-circle me-2"></i>සියලු සැකසුම් සාර්ථකව යාවත්කාලීන කරන ලදි!
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>';
        } else {
            $general_message = '<div class="alert alert-danger alert-dismissible fade show">
                                    <i class="fas fa-times-circle me-2"></i>යාවත්කාලීන කිරීමේ දෝෂයක් ඇති විය.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>';
        }
    }

    // Password change
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $password_message = '<div class="alert alert-warning">කරුණාකර සියලුම ක්ෂේත්‍ර පුරවන්න.</div>';
        } elseif ($new_password !== $confirm_password) {
            $password_message = '<div class="alert alert-danger">නව මුරපදය සහ තහවුරු කිරීමේ මුරපදය නොගැලපේ.</div>';
        } elseif (strlen($new_password) < 8) {
            $password_message = '<div class="alert alert-danger">මුරපදය අවම වශයෙන් අකුරු 8 ක් තිබිය යුතුය.</div>';
        } else {
            $stmt_select = $conn->prepare("SELECT password_hash FROM admins WHERE id = ?");
            if ($stmt_select) {
                $stmt_select->bind_param("i", $admin_id);
                $stmt_select->execute();
                $result = $stmt_select->get_result();
                
                if ($result->num_rows === 1) {
                    $admin = $result->fetch_assoc();
                    if (password_verify($current_password, $admin['password_hash'])) {
                        $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt_update = $conn->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
                        if ($stmt_update) {
                            $stmt_update->bind_param("si", $new_hashed, $admin_id);
                            if ($stmt_update->execute()) {
                                session_unset();
                                session_destroy();
                                header('Location: login.php?success=password_changed');
                                exit;
                            }
                            $stmt_update->close();
                        }
                    } else {
                        $password_message = '<div class="alert alert-danger">වත්මන් මුරපදය වැරදියි.</div>';
                    }
                }
                $stmt_select->close();
            }
        }
    }
}

// Load current settings
$settings = loadAllSettings($conn);

// Merge with defaults
$settings = array_merge([
    'contact_email' => 'info@nethsitha.lk',
    'contact_phone' => '+94 71 123 4567',
    'contact_address' => '123, ප්‍රධාන පාර, කොළඹ',
    'map_embed_code' => '',
    'social_facebook' => '',
    'social_whatsapp' => '',
    'social_instagram' => '',
    'social_youtube' => '',
    'about_profile_name' => 'ජ්‍යෝතිෂවේදී',
    'about_profile_title' => 'ප්‍රධාන ජ්‍යෝතිෂවේදී',
    'site_title' => 'නෙත්සිත ජෝතිශ්‍ය සේවය',
    'meta_description' => 'ඔබගේ ජ්‍යෝතිෂ්‍යමය අවශ්‍යතා සඳහා විශ්වාසනීය සේවාව.',
    'meta_keywords' => 'ජෝතිශ්‍ය, කේන්දර, ශ්‍රී ලංකා',
], $settings);
?>

<!-- Page Header -->
<div class="mb-4">
    <h2 class="text-primary">
        <i class="fas fa-cog me-2"></i>පරිපාලක සැකසුම් කළමනාකරණය
    </h2>
    <p class="text-muted">වෙබ් අඩවියේ පොදු තොරතුරු, SEO සැකසුම්, සමාජ මාධ්‍ය සබැඳි සහ මුරපදය මෙතැනින් වෙනස් කළ හැකිය.</p>
</div>

<?php echo $general_message; ?>

<!-- General Settings Form -->
<form method="POST" action="" enctype="multipart/form-data">
    <input type="hidden" name="action" value="update_general_settings">

    <!-- Contact Information Card -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-phone me-2"></i>සම්බන්ධතා තොරතුරු</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="contact_email" class="form-label fw-bold">විද්‍යුත් තැපෑල</label>
                    <input type="email" class="form-control" id="contact_email" name="contact_email" 
                           value="<?php echo htmlspecialchars($settings['contact_email']); ?>" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="contact_phone" class="form-label fw-bold">දුරකථන අංකය</label>
                    <input type="text" class="form-control" id="contact_phone" name="contact_phone" 
                           value="<?php echo htmlspecialchars($settings['contact_phone']); ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="contact_address" class="form-label fw-bold">ලිපිනය</label>
                <textarea class="form-control" id="contact_address" name="contact_address" rows="2"><?php echo htmlspecialchars($settings['contact_address']); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="map_embed_code" class="form-label fw-bold">Google Map Embed Code</label>
                <textarea class="form-control" id="map_embed_code" name="map_embed_code" rows="3"><?php echo htmlspecialchars($settings['map_embed_code']); ?></textarea>
                <small class="text-muted">Google Maps වෙතින් ලැබෙන සම්පූර්ණ IFrame කේතය.</small>
            </div>
        </div>
    </div>

    <!-- Social Media Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-share-alt me-2"></i>සමාජ මාධ්‍ය සබැඳි</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold"><i class="fab fa-facebook me-2"></i>Facebook</label>
                    <input type="url" class="form-control" name="social_facebook" 
                           value="<?php echo htmlspecialchars($settings['social_facebook']); ?>" 
                           placeholder="https://facebook.com/yourpage">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold"><i class="fab fa-whatsapp me-2"></i>WhatsApp</label>
                    <input type="text" class="form-control" name="social_whatsapp" 
                           value="<?php echo htmlspecialchars($settings['social_whatsapp']); ?>" 
                           placeholder="94711234567">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold"><i class="fab fa-instagram me-2"></i>Instagram</label>
                    <input type="url" class="form-control" name="social_instagram" 
                           value="<?php echo htmlspecialchars($settings['social_instagram']); ?>" 
                           placeholder="https://instagram.com/yourpage">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold"><i class="fab fa-youtube me-2"></i>YouTube</label>
                    <input type="url" class="form-control" name="social_youtube" 
                           value="<?php echo htmlspecialchars($settings['social_youtube']); ?>" 
                           placeholder="https://youtube.com/yourchannel">
                </div>
            </div>
        </div>
    </div>

    <!-- About Page Card -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>අප ගැන පිටුවේ විස්තර</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="about_profile_name" class="form-label fw-bold">නම</label>
                <input type="text" class="form-control" id="about_profile_name" name="about_profile_name" 
                       value="<?php echo htmlspecialchars($settings['about_profile_name']); ?>">
            </div>
            
            <div class="mb-3">
                <label for="about_profile_title" class="form-label fw-bold">තනතුර/විශේෂඥතාව</label>
                <input type="text" class="form-control" id="about_profile_title" name="about_profile_title" 
                       value="<?php echo htmlspecialchars($settings['about_profile_title']); ?>">
            </div>
        </div>
    </div>

    <!-- SEO Settings Card -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-search me-2"></i>SEO සැකසුම්</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="site_title" class="form-label fw-bold">වෙබ් අඩවියේ මාතෘකාව</label>
                <input type="text" class="form-control" id="site_title" name="site_title" 
                       value="<?php echo htmlspecialchars($settings['site_title']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="meta_description" class="form-label fw-bold">Meta Description</label>
                <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($settings['meta_description']); ?></textarea>
                <small class="text-muted">වචන 160කට වඩා අඩු විය යුතුය.</small>
            </div>

            <div class="mb-3">
                <label for="meta_keywords" class="form-label fw-bold">Meta Keywords</label>
                <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" 
                       value="<?php echo htmlspecialchars($settings['meta_keywords']); ?>">
                <small class="text-muted">කොමා වලින් වෙන් කරන්න.</small>
            </div>
        </div>
    </div>

    <!-- Submit Button -->
    <div class="d-grid mb-5">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save me-2"></i>සියලු සැකසුම් සුරකින්න
        </button>
    </div>
</form>

<!-- Password Change Section -->
<div class="card mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="fas fa-key me-2"></i>මුරපදය වෙනස් කිරීම</h5>
    </div>
    <div class="card-body">
        <?php echo $password_message; ?>

        <form method="POST" action="">
            <input type="hidden" name="action" value="change_password">
            
            <div class="mb-3">
                <label for="current_password" class="form-label fw-bold">වත්මන් මුරපදය</label>
                <input type="password" class="form-control" id="current_password" name="current_password" required>
            </div>

            <div class="mb-3">
                <label for="new_password" class="form-label fw-bold">නව මුරපදය (අවම 8)</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required>
            </div>
            
            <div class="mb-3">
                <label for="confirm_password" class="form-label fw-bold">නව මුරපදය තහවුරු කරන්න</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-danger btn-lg">
                    <i class="fas fa-lock me-2"></i>මුරපදය වෙනස් කරන්න
                </button>
            </div>
        </form>
    </div>
</div>

<?php include('includes/footer.php'); ?>