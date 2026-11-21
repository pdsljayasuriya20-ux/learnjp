<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/audit_logger.php';

Auth::requirePermission('edit_dayakayan');
$db = Database::getConnection();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) { http_response_code(400); exit('වලංගු දායක අංකයක් ලබාදී නැත.'); }
$stmt = $db->prepare('SELECT * FROM dayakayan WHERE id = ?');
$stmt->execute([$id]);
$dayakaya = $stmt->fetch();
if (!$dayakaya) { http_response_code(404); exit('දායක වාර්තාව හමු නොවීය.'); }

$editable = ['full_name','nic','dob','phone','whatsapp_1','whatsapp_2','landline','email','marital_status','has_job','job_type','job_details','attends_daham_pasala','daham_pasala_name','daham_pasala_grade'];
$labels = [
    'full_name'=>'සම්පූර්ණ නම','nic'=>'ජාතික හැඳුනුම්පත් අංකය','dob'=>'උපන් දිනය','phone'=>'දුරකථන අංකය',
    'whatsapp_1'=>'WhatsApp 1','whatsapp_2'=>'WhatsApp 2','landline'=>'ස්ථාවර දුරකථනය','email'=>'Email',
    'marital_status'=>'විවාහක තත්ත්වය','has_job'=>'රැකියා තත්ත්වය','job_type'=>'රැකියා වර්ගය','job_details'=>'රැකියා විස්තර',
    'attends_daham_pasala'=>'දහම් පාසල් යනවාද?','daham_pasala_name'=>'දහම් පාසලේ නම','daham_pasala_grade'=>'දහම් පාසල් ශ්‍රේණිය'
];
$message = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) throw new RuntimeException('ආරක්ෂිත Token එක වලංගු නැත.');
        $old = []; $new = [];
        foreach ($editable as $field) {
            $old[$field] = $dayakaya[$field] ?? null;
            $value = trim((string)($_POST[$field] ?? ''));
            if ($value === '') $value = null;
            if ($field === 'full_name' && $value === null) throw new RuntimeException('සම්පූර්ණ නම අනිවාර්ය වේ.');
            if ($field === 'marital_status' && !in_array($value, ['married','single'], true)) throw new RuntimeException('විවාහක තත්ත්වය තෝරන්න.');
            if ($field === 'has_job' && !in_array($value, ['yes','no','retired','self'], true)) throw new RuntimeException('රැකියා තත්ත්වය තෝරන්න.');
            if ($field === 'attends_daham_pasala' && !in_array($value, ['yes','no'], true)) throw new RuntimeException('දහම් පාසල් තත්ත්වය තෝරන්න.');
            $new[$field] = $value;
        }
        if ($new['dob'] !== null) {
            $date = DateTime::createFromFormat('!Y-m-d', (string)$new['dob']);
            if (!$date || $date->format('Y-m-d') !== $new['dob'] || $date > new DateTime('today')) throw new RuntimeException('වලංගු උපන් දිනයක් ඇතුළත් කරන්න.');
            $new['age'] = $date->diff(new DateTime('today'))->y;
        }
        // Include age in the staged request when DOB changes.
        if (isset($new['age'])) { $old['age'] = $dayakaya['age']; }
        if ($old === $new) throw new RuntimeException('වෙනස්කමක් සිදු කර නැත.');
        $ins = $db->prepare("INSERT INTO change_requests (dayakaya_id, requested_by, old_data, new_data, status) VALUES (:did, :uid, :old, :new, 'pending')");
        $ins->execute(['did'=>$id, 'uid'=>(int)Auth::user()['id'], 'old'=>json_encode($old, JSON_UNESCAPED_UNICODE), 'new'=>json_encode($new, JSON_UNESCAPED_UNICODE)]);
        AuditLogger::log('SUBMIT_CHANGE_REQUEST', 'dayakayan', $id, $old, $new);
        $message = 'වෙනස්කම් ඉල්ලීම සාර්ථකව යොමු කරන ලදී. අනුමත වන තෙක් දැනට ඇති දත්ත වෙනස් නොවේ.';
        $stmt->execute([$id]); $dayakaya = $stmt->fetch();
    } catch (Throwable $e) {
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'වෙනස්කම් ඉල්ලීම සුරැකීමට නොහැකි විය.';
        if (!($e instanceof RuntimeException)) error_log('Dayakaya edit request error: '.$e->getMessage());
    }
}
$csrfToken = generateCSRFToken();
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="container-fluid"><div class="row"><div class="col-md-2 p-0"><?php require_once __DIR__ . '/../../includes/sidebar.php'; ?></div>
<div class="col-md-10 p-4"><h3 class="mb-3">දායක දත්ත සංස්කරණ ඉල්ලීම</h3>
<div class="alert alert-info">මෙහි වෙනස්කම් සෘජුවම ක්‍රියාත්මක නොවේ. ඒවා අනුමත කිරීම සඳහා Change Requests වෙත යොමු වේ.</div>
<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<div class="card"><div class="card-header">සාමාජික අංකය: <strong><?= e($dayakaya['member_id']) ?></strong> — <?= e($dayakaya['full_name']) ?></div><div class="card-body"><form method="post"><input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="id" value="<?= $id ?>"><div class="row g-3">
<?php foreach ($editable as $field): $value = (string)($dayakaya[$field] ?? ''); ?>
<div class="col-md-6"><label class="form-label"><?= e($labels[$field]) ?></label>
<?php if (in_array($field, ['marital_status','has_job','attends_daham_pasala'], true)): $opts = $field==='marital_status' ? ['single'=>'අවිවාහක','married'=>'විවාහක'] : ($field==='has_job' ? ['no'=>'නැත','yes'=>'රැකියාවක නිරතයි','self'=>'ස්වයං රැකියා','retired'=>'විශ්‍රාමික'] : ['no'=>'නැත','yes'=>'ඔව්']); ?>
<select class="form-select" name="<?= e($field) ?>"><?php foreach ($opts as $k=>$label): ?><option value="<?= e($k) ?>" <?= $value===$k?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select>
<?php else: ?><input class="form-control" name="<?= e($field) ?>" value="<?= e($value) ?>" <?= $field==='dob'?'type="date" max="'.date('Y-m-d').'"':($field==='email'?'type="email"':'') ?> <?= $field==='full_name'?'required':'' ?>><?php endif; ?>
</div><?php endforeach; ?></div><div class="mt-4"><button class="btn btn-warning fw-bold" type="submit">වෙනස්කම් අනුමැතියට යොමු කරන්න</button> <a class="btn btn-outline-secondary" href="index.php">ආපසු</a></div></form></div></div></div></div></div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
