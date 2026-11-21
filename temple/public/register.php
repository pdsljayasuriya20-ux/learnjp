<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$db = Database::getConnection();
$success = '';
$error = '';
$values = [
    'full_name' => '', 'nic' => '', 'dob' => '', 'phone' => '',
    'whatsapp_1' => '', 'whatsapp_2' => '', 'landline' => '', 'email' => '',
    'marital_status' => 'single', 'has_job' => 'no', 'job_type' => '', 'job_details' => '',
    'attends_daham_pasala' => 'no', 'daham_pasala_name' => '', 'daham_pasala_grade' => '',
    'peruwa_id' => '', 'address' => '',
];

try {
    $peruwaList = $db->query('SELECT id, name FROM peruwa ORDER BY id')->fetchAll();
    $associationList = $db->query('SELECT id, name FROM associations ORDER BY name')->fetchAll();
} catch (Throwable $e) {
    error_log('Registration master-data error: ' . $e->getMessage());
    http_response_code(500);
    exit('මූලික දත්ත ලබාගැනීමේ දෝෂයක් ඇතිවිය. කරුණාකර පසුව නැවත උත්සාහ කරන්න.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $_) {
        $values[$key] = trim((string)($_POST[$key] ?? $values[$key]));
    }
    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new RuntimeException('ආරක්ෂිත Token එක කල් ඉකුත් වී හෝ වැරදි වී ඇත. පිටුව නැවත Refresh කරන්න.');
        }
        if ($values['full_name'] === '' || $values['phone'] === '' || $values['dob'] === '' || $values['peruwa_id'] === '' || $values['address'] === '') {
            throw new RuntimeException('සම්පූර්ණ නම, දුරකථන අංකය, උපන් දිනය, පේරුව සහ ලිපිනය අනිවාර්ය වේ.');
        }
        $dob = DateTime::createFromFormat('!Y-m-d', $values['dob']);
        if (!$dob || $dob->format('Y-m-d') !== $values['dob'] || $dob > new DateTime('today')) {
            throw new RuntimeException('වලංගු උපන් දිනයක් ඇතුළත් කරන්න.');
        }
        $peruwaStmt = $db->prepare('SELECT COUNT(*) FROM peruwa WHERE id = ?');
        $peruwaStmt->execute([(int)$values['peruwa_id']]);
        if (!(int)$peruwaStmt->fetchColumn()) {
            throw new RuntimeException('තෝරාගත් පේරුව වලංගු නොවේ.');
        }
        if ($values['nic'] !== '') {
            $dup = $db->prepare("SELECT COUNT(*) FROM dayakayan WHERE nic = ? AND nic <> ''");
            $dup->execute([$values['nic']]);
            if ((int)$dup->fetchColumn() > 0) throw new RuntimeException('මෙම ජාතික හැඳුනුම්පත් අංකය දැනටමත් ලියාපදිංචි වී ඇත.');
        }

        $db->beginTransaction();
        $familyCode = generateFamilyCode($db);
        $familyStmt = $db->prepare('INSERT INTO families (family_code, peruwa_id, address) VALUES (:code, :peruwa, :address)');
        $familyStmt->execute(['code' => $familyCode, 'peruwa' => (int)$values['peruwa_id'], 'address' => $values['address']]);
        $familyId = (int)$db->lastInsertId();

        $memberId = generateMemberID($db);
        $age = (int)$dob->diff(new DateTime('today'))->y;
        $insert = $db->prepare("INSERT INTO dayakayan
            (member_id, family_id, full_name, nic, dob, age, phone, whatsapp_1, whatsapp_2, landline, email,
             marital_status, has_job, job_type, job_details, attends_daham_pasala, daham_pasala_name, daham_pasala_grade, status)
            VALUES (:member_id, :family_id, :full_name, :nic, :dob, :age, :phone, :whatsapp_1, :whatsapp_2, :landline, :email,
             :marital_status, :has_job, :job_type, :job_details, :attends, :school_name, :school_grade, 'pending')");
        $insert->execute([
            'member_id' => $memberId, 'family_id' => $familyId, 'full_name' => $values['full_name'],
            'nic' => $values['nic'] ?: null, 'dob' => $values['dob'], 'age' => $age, 'phone' => $values['phone'],
            'whatsapp_1' => $values['whatsapp_1'] ?: null, 'whatsapp_2' => $values['whatsapp_2'] ?: null,
            'landline' => $values['landline'] ?: null, 'email' => $values['email'] ?: null,
            'marital_status' => in_array($values['marital_status'], ['married', 'single'], true) ? $values['marital_status'] : 'single',
            'has_job' => in_array($values['has_job'], ['yes', 'no', 'retired', 'self'], true) ? $values['has_job'] : 'no',
            'job_type' => $values['job_type'] ?: null, 'job_details' => $values['job_details'] ?: null,
            'attends' => $values['attends_daham_pasala'] === 'yes' ? 'yes' : 'no',
            'school_name' => $values['daham_pasala_name'] ?: null, 'school_grade' => $values['daham_pasala_grade'] ?: null,
        ]);
        $dayakayaId = (int)$db->lastInsertId();

        $associationIds = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['association_ids'] ?? [])))));
        if ($associationIds) {
            $validAssoc = $db->prepare('SELECT id FROM associations WHERE id = ?');
            $linkAssoc = $db->prepare('INSERT IGNORE INTO dayaka_associations (dayakaya_id, association_id) VALUES (?, ?)');
            foreach ($associationIds as $associationId) {
                $validAssoc->execute([$associationId]);
                if ($validAssoc->fetchColumn()) $linkAssoc->execute([$dayakayaId, $associationId]);
            }
        }
        $audit = $db->prepare('INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (NULL, ?, ?, ?)');
        $audit->execute(['NEW_REGISTRATION', 'නව දායක ලියාපදිංචිය යොමු කරන ලදී: ' . $values['full_name'] . ' (Member ID: ' . $memberId . ')', $_SERVER['REMOTE_ADDR'] ?? null]);
        $db->commit();
        $success = 'ඔබගේ ලියාපදිංචි ඉල්ලීම සාර්ථකව යොමු කරන ලදී. සාමාජික අංකය: ' . $memberId . '. පරිපාලක අනුමැතියෙන් පසු ලියාපදිංචිය සක්‍රිය වේ.';
        foreach ($values as $key => $_) $values[$key] = $key === 'marital_status' ? 'single' : ($key === 'has_job' || $key === 'attends_daham_pasala' ? 'no' : '');
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'ලියාපදිංචිය සුරැකීමට නොහැකි විය. දත්ත නැවත පරීක්ෂා කර උත්සාහ කරන්න.';
        if (!($e instanceof RuntimeException)) error_log('Registration save error: ' . $e->getMessage());
    }
}

$csrfToken = generateCSRFToken();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container my-4 my-lg-5">
  <div class="row justify-content-center"><div class="col-lg-10 col-xl-9">
    <div class="card shadow-sm border-0"><div class="card-header bg-warning text-dark p-4">
      <h3 class="mb-1 fw-bold">දායක ලියාපදිංචිය</h3><div>පන්සල් දායක කළමනාකරණ පද්ධතිය</div>
    </div><div class="card-body p-4">
      <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
      <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <h5 class="border-bottom pb-2 mb-3">පුද්ගලික විස්තර</h5>
        <div class="row g-3">
          <div class="col-md-8"><label class="form-label">සම්පූර්ණ නම *</label><input class="form-control" name="full_name" required maxlength="255" value="<?= e($values['full_name']) ?>"></div>
          <div class="col-md-4"><label class="form-label">ජාතික හැඳුනුම්පත් අංකය</label><input class="form-control" name="nic" maxlength="20" value="<?= e($values['nic']) ?>"></div>
          <div class="col-md-4"><label class="form-label">උපන් දිනය *</label><input type="date" class="form-control" name="dob" required max="<?= date('Y-m-d') ?>" value="<?= e($values['dob']) ?>"></div>
          <div class="col-md-4"><label class="form-label">දුරකථන අංකය *</label><input type="tel" class="form-control" name="phone" required maxlength="20" value="<?= e($values['phone']) ?>"></div>
          <div class="col-md-4"><label class="form-label">විවාහක තත්ත්වය</label><select class="form-select" name="marital_status"><option value="single" <?= $values['marital_status']==='single'?'selected':'' ?>>අවිවාහක</option><option value="married" <?= $values['marital_status']==='married'?'selected':'' ?>>විවාහක</option></select></div>
          <div class="col-md-4"><label class="form-label">WhatsApp 1</label><input class="form-control" name="whatsapp_1" maxlength="20" value="<?= e($values['whatsapp_1']) ?>"></div>
          <div class="col-md-4"><label class="form-label">WhatsApp 2</label><input class="form-control" name="whatsapp_2" maxlength="20" value="<?= e($values['whatsapp_2']) ?>"></div>
          <div class="col-md-4"><label class="form-label">ස්ථාවර දුරකථනය</label><input class="form-control" name="landline" maxlength="20" value="<?= e($values['landline']) ?>"></div>
          <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email" maxlength="100" value="<?= e($values['email']) ?>"></div>
        </div>
        <h5 class="border-bottom pb-2 mt-4 mb-3">පවුල සහ පේරුව</h5>
        <div class="row g-3"><div class="col-md-5"><label class="form-label">පේරුව *</label><select class="form-select" name="peruwa_id" required><option value="">තෝරන්න</option><?php foreach($peruwaList as $p): ?><option value="<?= (int)$p['id'] ?>" <?= (string)$values['peruwa_id']===(string)$p['id']?'selected':'' ?>><?= e($p['name']) ?></option><?php endforeach; ?></select></div>
          <div class="col-md-7"><label class="form-label">සම්පූර්ණ ලිපිනය *</label><textarea class="form-control" name="address" rows="2" required><?= e($values['address']) ?></textarea></div>
        </div>
        <h5 class="border-bottom pb-2 mt-4 mb-3">රැකියාව සහ දහම් පාසල</h5>
        <div class="row g-3"><div class="col-md-4"><label class="form-label">රැකියා තත්ත්වය</label><select class="form-select" name="has_job"><option value="no" <?= $values['has_job']==='no'?'selected':'' ?>>නැත</option><option value="yes" <?= $values['has_job']==='yes'?'selected':'' ?>>රැකියාවක නිරතයි</option><option value="self" <?= $values['has_job']==='self'?'selected':'' ?>>ස්වයං රැකියා</option><option value="retired" <?= $values['has_job']==='retired'?'selected':'' ?>>විශ්‍රාමික</option></select></div>
          <div class="col-md-4"><label class="form-label">රැකියා වර්ගය</label><input class="form-control" name="job_type" maxlength="100" value="<?= e($values['job_type']) ?>"></div>
          <div class="col-md-4"><label class="form-label">රැකියා විස්තර</label><input class="form-control" name="job_details" maxlength="255" value="<?= e($values['job_details']) ?>"></div>
          <div class="col-md-4"><label class="form-label">දහම් පාසල් යනවාද?</label><select class="form-select" name="attends_daham_pasala"><option value="no" <?= $values['attends_daham_pasala']==='no'?'selected':'' ?>>නැත</option><option value="yes" <?= $values['attends_daham_pasala']==='yes'?'selected':'' ?>>ඔව්</option></select></div>
          <div class="col-md-4"><label class="form-label">දහම් පාසලේ නම</label><input class="form-control" name="daham_pasala_name" maxlength="150" value="<?= e($values['daham_pasala_name']) ?>"></div>
          <div class="col-md-4"><label class="form-label">ශ්‍රේණිය</label><input class="form-control" name="daham_pasala_grade" maxlength="50" value="<?= e($values['daham_pasala_grade']) ?>"></div>
        </div>
        <?php if ($associationList): ?><h5 class="border-bottom pb-2 mt-4 mb-3">සමිති සාමාජිකත්වය (අදාළ නම්)</h5><div class="row g-2"><?php foreach($associationList as $a): ?><div class="col-md-4"><label class="form-check"><input class="form-check-input" type="checkbox" name="association_ids[]" value="<?= (int)$a['id'] ?>"> <span class="form-check-label"><?= e($a['name']) ?></span></label></div><?php endforeach; ?></div><?php endif; ?>
        <div class="d-flex flex-wrap gap-2 mt-4"><button class="btn btn-warning fw-bold px-4" type="submit">ලියාපදිංචි ඉල්ලීම යොමු කරන්න</button><a class="btn btn-outline-secondary" href="<?= e(APP_URL) ?>/public/index.php">ආපසු</a></div>
      </form>
    </div></div>
  </div></div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
