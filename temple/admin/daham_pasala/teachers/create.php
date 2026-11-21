<?php
require_once __DIR__.'/common.php';
$errors=[]; $dayakayaId=(int)($_POST['dayakaya_id']??0); $subject=trim($_POST['subject']??''); $teacherRole=$_POST['teacher_role']??'ගුරුවරයා'; $startDate=$_POST['start_date']??date('Y-m-d'); $status=$_POST['status']??'active'; $notes=trim($_POST['notes']??''); $quals=$_POST['qualifications']??[];
$members=$db->query("SELECT id,member_id,full_name FROM dayakayan WHERE status='approved' ORDER BY full_name")->fetchAll();
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!$dayakayaId) $errors[]='දායකයෙකු තෝරන්න.';
 if(!in_array($status,['active','inactive'],true)) $errors[]='තත්ත්වය නිවැරදිව තෝරන්න.';
 $roles=['ගුරුවරයා','ප්‍රධාන ගුරුවරයා','සහකාර ප්‍රධාන ගුරුවරයා','ස්වේච්ඡා ගුරුවරයා']; if(!in_array($teacherRole,$roles,true)) $errors[]='තනතුර නිවැරදිව තෝරන්න.';
 if($startDate!==''){ $dt=DateTime::createFromFormat('!Y-m-d',$startDate); if(!$dt||$dt->format('Y-m-d')!==$startDate)$errors[]='සේවා ආරම්භක දිනය නිවැරදි නොවේ.'; }
 if(!is_array($quals)) $quals=[]; $quals=array_values(array_intersect($qualificationOptions,$quals));
 $s=$db->prepare("SELECT id FROM dayakayan WHERE id=? AND status='approved'");$s->execute([$dayakayaId]);if(!$s->fetch())$errors[]='තෝරාගත් දායකයා අනුමත කර නැත.';
 $s=$db->prepare('SELECT id FROM daham_pasala_teachers WHERE dayakaya_id=?');$s->execute([$dayakayaId]);if($s->fetch())$errors[]='මෙම දායකයා දැනටමත් ගුරුවරයෙකි.';
 if(!$errors){try{$photo=teacherPhotoUpload('profile_photo');$s=$db->prepare('INSERT INTO daham_pasala_teachers(dayakaya_id,subject,teacher_role,start_date,status,notes,qualifications,profile_photo) VALUES(?,?,?,?,?,?,?,?)');$s->execute([$dayakayaId,$subject,$teacherRole,$startDate?:null,$status,$notes,json_encode($quals,JSON_UNESCAPED_UNICODE),$photo]);header('Location: '.ADMIN_URL.'/daham_pasala/teachers/view.php?id='.$db->lastInsertId().'&created=1');exit;}catch(Throwable $e){error_log('Teacher create: '.$e->getMessage());$errors[]=$e instanceof RuntimeException?$e->getMessage():'ගුරුවරයා සුරැකීමට නොහැකි විය. Migration SQL ක්‍රියාත්මක කර ඇතිදැයි බලන්න.';}}
}
require_once __DIR__.'/../../../includes/header.php';
?>
<div class="admin-shell"><aside class="admin-sidebar no-print"><?php require __DIR__.'/../../../includes/sidebar.php'; ?></aside><main class="admin-main"><div class="container-fluid p-3 p-md-4">
<div class="d-flex justify-content-between flex-wrap gap-2 mb-4"><div><h3>නව දහම් පාසල් ගුරුවරයෙක්</h3><p class="text-muted">අධ්‍යාපන සුදුසුකම්, සේවා දිනය සහ profile photo එක ඇතුළත් කරන්න.</p></div><a class="btn btn-outline-secondary" href="index.php">ගුරුවරුන් වෙත</a></div>
<?php if($errors):?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e):?><li><?=e($e)?></li><?php endforeach;?></ul></div><?php endif;?>
<form method="post" enctype="multipart/form-data"><div class="card shadow-sm mb-3"><div class="card-header fw-bold">ගුරු සහ පුද්ගල තොරතුරු</div><div class="card-body"><div class="row g-3">
<div class="col-md-6"><label class="form-label">අනුමත දායකයා *</label><select name="dayakaya_id" class="form-select" required><option value="">-- තෝරන්න --</option><?php foreach($members as $m):?><option value="<?=$m['id']?>" <?=$dayakayaId===(int)$m['id']?'selected':''?>><?=e($m['full_name'])?> (<?=e($m['member_id'])?>)</option><?php endforeach;?></select></div>
<div class="col-md-6"><label class="form-label">Profile photo (JPG/PNG/WEBP, max 4MB)</label><input class="form-control" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp"></div>
<div class="col-md-4"><label class="form-label">තනතුර</label><select name="teacher_role" class="form-select"><?php foreach(['ගුරුවරයා','ප්‍රධාන ගුරුවරයා','සහකාර ප්‍රධාන ගුරුවරයා','ස්වේච්ඡා ගුරුවරයා'] as $r):?><option <?=$teacherRole===$r?'selected':''?>><?=e($r)?></option><?php endforeach;?></select></div>
<div class="col-md-4"><label class="form-label">උගන්වන විෂය</label><input name="subject" class="form-control" maxlength="150" value="<?=e($subject)?>" placeholder="උදා: බුද්ධ ධර්මය"></div>
<div class="col-md-4"><label class="form-label">සේවය ආරම්භ කළ දිනය</label><input type="date" name="start_date" class="form-control" value="<?=e($startDate)?>" max="<?=date('Y-m-d')?>"></div>
<div class="col-md-4"><label class="form-label">සේවා තත්ත්වය</label><select name="status" class="form-select"><option value="active" <?=$status==='active'?'selected':''?>>සක්‍රීය</option><option value="inactive" <?=$status==='inactive'?'selected':''?>>අක්‍රීය</option></select></div>
<div class="col-12"><label class="form-label fw-bold">අධ්‍යාපන සුදුසුකම් (එකකට වඩා තෝරාගත හැක)</label><div class="row g-2"><?php foreach($qualificationOptions as $q):?><div class="col-md-6 col-lg-4"><label class="border rounded p-2 w-100"><input class="form-check-input me-2" type="checkbox" name="qualifications[]" value="<?=e($q)?>" <?=in_array($q,$quals,true)?'checked':''?>><?=e($q)?></label></div><?php endforeach;?></div></div>
<div class="col-12"><label class="form-label">සටහන්</label><textarea name="notes" rows="3" class="form-control"><?=e($notes)?></textarea></div></div></div></div><button class="btn btn-warning fw-bold" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i> සුරකින්න</button> <a href="index.php" class="btn btn-outline-secondary">අවලංගු කරන්න</a></form>
</div></main></div><?php require_once __DIR__.'/../../../includes/footer.php'; ?>