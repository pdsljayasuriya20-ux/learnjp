<?php
// Shared helpers for Daham Pasala teachers module
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';
Auth::requirePermission('view_dayakayan');
$db = Database::getConnection();
$qualificationOptions = ['අ.පො.ස. සාමාන්‍ය පෙළ (G.C.E. O/L)','අ.පො.ස. උසස් පෙළ (G.C.E. A/L)','දහම් පාසල් අවසාන සහතිකය','ධර්මාචාර්ය විභාගය','බෞද්ධ ධර්මය පිළිබඳ ඩිප්ලෝමාව','උපාධිය (Degree)','පශ්චාත් උපාධිය (Postgraduate)','ගුරු පුහුණු සහතිකය','වෙනත් වෘත්තීය සුදුසුකම්'];
function teacherQualifications($raw): array { $v=json_decode((string)$raw,true); return is_array($v)?$v:[]; }
function teacherPhotoUpload(string $field, ?string $old = null): ?string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return $old;
    $f=$_FILES[$field]; if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('ඡායාරූපය upload කිරීම අසාර්ථකයි.');
    if ($f['size'] > 4*1024*1024) throw new RuntimeException('ඡායාරූපය 4MB ට අඩු විය යුතුයි.');
    $info=@getimagesize($f['tmp_name']); if (!$info || !in_array($info['mime'],['image/jpeg','image/png','image/webp'],true)) throw new RuntimeException('JPG, PNG හෝ WEBP ඡායාරූපයක් තෝරන්න.');
    $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$info['mime']];
    $dir=__DIR__.'/uploads'; if (!is_dir($dir) && !mkdir($dir,0755,true)) throw new RuntimeException('uploads folder එක සෑදිය නොහැක.');
    $name='teacher_'.bin2hex(random_bytes(12)).'.'.$ext; if (!move_uploaded_file($f['tmp_name'],$dir.'/'.$name)) throw new RuntimeException('ඡායාරූපය සුරැකීමට නොහැකි විය.');
    if ($old && preg_match('/^teacher_[a-f0-9]+\.(jpg|png|webp)$/',$old) && is_file($dir.'/'.$old)) @unlink($dir.'/'.$old);
    return $name;
}
function teacherPhotoUrl($name): string { return $name ? ADMIN_URL.'/daham_pasala/teachers/uploads/'.rawurlencode($name) : ''; }
function teacherFetch(PDO $db, int $id): ?array {
 $s=$db->prepare("SELECT t.*,d.member_id,d.full_name,d.phone,d.whatsapp_1,d.whatsapp_2,d.landline,d.nic,d.dob,d.age,d.email,d.family_id,f.address,p.name AS peruwa_name FROM daham_pasala_teachers t JOIN dayakayan d ON d.id=t.dayakaya_id LEFT JOIN families f ON f.id=d.family_id LEFT JOIN peruwa p ON p.id=f.peruwa_id WHERE t.id=?"); $s->execute([$id]); $r=$s->fetch(PDO::FETCH_ASSOC); return $r?:null;
}
?>