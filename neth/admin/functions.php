<?php
// uploads ෆෝල්ඩරයේ නිවැරදි නිරපේක්ෂ මාර්ගය ලබා ගැනීම (admin_settings.php ගොනුවට සාපේක්ෂව)
// admin_settings.php තිබෙන directory එකට සාපේක්ෂව uploads ෆෝල්ඩරය තිබිය යුතුය.
$BASE_DIR = __DIR__ . '/';
$upload_dir = $BASE_DIR . '../uploads/'; // ඔබගේ uploads folder එක admin folder එකෙන් පිටත root එකේ තිබේ නම්.
// නැතිනම් uploads folder එක admin folder එක ඇතුලත තිබේ නම් $upload_dir = $BASE_DIR . 'uploads/'; ලෙස වෙනස් කරන්න.

// ඔබට දෝෂ ඇතිවීම වළක්වා ගැනීමට අපි uploads folder එක root එකේ ඇතැයි උපකල්පනය කරමු.

/**
 * රූප ගොනුවක් උඩුගත කිරීම සහ ගෙනයාම කළමනාකරණය කරයි.
 *
 * @param array $file_array $_FILES සුපිරි ග්ලෝබල් එකෙන් ලැබෙන අරාව.
 * @param string $file_info ගොනුවේ විස්තරය (උදා: 'ගුරුතුමාගේ රූපය').
 * @param string $current_path දැනට දත්ත ගබඩාවේ ඇති ගොනුවේ මාර්ගය (පැරණි රූපය).
 * @return array [සාර්ථකද (bool), පණිවිඩය (string), නව මාර්ගය (string)]
 */
function handleImageUpload($file_array, $file_info, $current_path) {
    global $upload_dir; // ගෝලීය වශයෙන් $upload_dir භාවිතා කිරීම

    if ($file_array['error'] === UPLOAD_ERR_NO_FILE) {
        // ගොනුවක් තෝරාගෙන නැත. පැරණි මාර්ගයම ආපසු යවන්න.
        return [true, '', $current_path];
    }

    // 1. දෝෂ පරීක්ෂා කිරීම
    if ($file_array['error'] !== UPLOAD_ERR_OK) {
        return [false, "{$file_info} උඩුගත කිරීමේ දෝෂයකි. දෝෂ කේතය: {$file_array['error']}", $current_path];
    }

    // 2. ගොනු වර්ගය සහ ප්‍රමාණය පරීක්ෂා කිරීම (විකල්ප)
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file_array['type'], $allowed_types)) {
        return [false, "{$file_info} සඳහා අවසර ඇත්තේ JPEG, PNG, හෝ GIF ගොනු පමණි.", $current_path];
    }

    // 3. අද්විතීය ගොනු නාමයක් ජනනය කිරීම
    $file_ext = pathinfo($file_array['name'], PATHINFO_EXTENSION);
    $new_file_name = uniqid('img_', true) . '.' . $file_ext;
    $new_file_path = $upload_dir . $new_file_name;

    // 4. පැරණි ගොනුව ඉවත් කිරීම (නව ගොනුව සාර්ථකව උඩුගත වුවහොත්)
    // Localhost එකේදී unlink() සාමාන්‍යයෙන් සාර්ථකයි.
    
    // 5. ගොනුව ගෙනයාම
    if (move_uploaded_file($file_array['tmp_name'], $new_file_path)) {
        // ගොනුව සාර්ථකයි. පැරණි ගොනුව මකන්න.
        if ($current_path && file_exists($current_path)) {
            unlink($current_path);
        }
        
        // දත්ත ගබඩාවේ ගබඩා කිරීම සඳහා සාපේක්ෂ මාර්ගය ආපසු යවන්න.
        // අපි උපකල්පනය කරන්නේ වෙබ් root එකට සාපේක්ෂව uploads/ නමින් ෆෝල්ඩරයක් ඇති බවයි.
        $relative_path = 'uploads/' . $new_file_name; 
        return [true, "{$file_info} සාර්ථකව උඩුගත කරන ලදී.", $relative_path];
    } else {
        // move_uploaded_file() අසාර්ථකයි.
        return [false, "{$file_info} ගොනුව චලනය කිරීමේ දෝෂයකි. (මාර්ගය: '{$new_file_path}')", $current_path];
    }
}
?>