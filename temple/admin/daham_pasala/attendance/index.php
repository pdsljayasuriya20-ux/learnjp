<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';
Auth::requireAuth();
Auth::requirePermission('view_dayakayan');
$db = Database::getConnection();
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['dp_attendance_csrf'])) $_SESSION['dp_attendance_csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['dp_attendance_csrf'];
$base = defined('BASE_URL') ? rtrim((string)BASE_URL, '/') : '/temple';
$admin = defined('ADMIN_URL') ? rtrim((string)ADMIN_URL, '/') : $base.'/admin';
function attH(mixed $v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function attCol(array $cols, array $candidates): ?string { foreach($candidates as $candidate) foreach($cols as $col) if(strcasecmp($candidate,$col)===0) return $col; return null; }
function attQ(?string $col): string { return $col===null?'':'`'.str_replace('`','``',$col).'`'; }

// Find actual student table columns, so the scanner can use a student ID or admission number.
try {
    $st = $db->query('SHOW COLUMNS FROM `daham_pasala_students`');
    $studentCols = array_column($st->fetchAll(PDO::FETCH_ASSOC), 'Field');
} catch(Throwable $e) {
    error_log('Attendance student schema: '.$e->getMessage());
    http_response_code(500); exit('Student table daham_pasala_students is missing.');
}
$idCol = attCol($studentCols,['id','student_id','studentId']);
$nameCol = attCol($studentCols,['full_name','student_name','name','fullName','name_with_initials','student_full_name']);
$admissionCol = attCol($studentCols,['admission_no','admission_number','student_no','student_number','registration_no','index_number']);
if(!$idCol || !$nameCol){http_response_code(500);exit('Could not detect student ID/name columns. Check SHOW COLUMNS FROM daham_pasala_students.');}

function attFindStudent(PDO $db, string $code, string $idCol, string $nameCol, ?string $admissionCol): ?array {
    $code = trim($code);
    if (str_starts_with(strtolower($code),'student:')) $code = trim(substr($code,8));
    if ($code === '') return null;
    $sql = 'SELECT '.attQ($idCol).' AS sid, '.attQ($nameCol).' AS sname FROM daham_pasala_students WHERE '.attQ($idCol).' = ?';
    $params = [$code];
    if ($admissionCol) { $sql .= ' OR '.attQ($admissionCol).' = ?'; $params[] = $code; }
    $sql .= ' LIMIT 1';
    $q=$db->prepare($sql);$q->execute($params);$row=$q->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}
function attMark(PDO $db, array $student, string $date, string $status, string $method, string $code, string $notes=''): string {
    $validDate = DateTime::createFromFormat('Y-m-d', $date);
    if (!$validDate || $validDate->format('Y-m-d') !== $date) throw new RuntimeException('Invalid attendance date.');
    if (!in_array($status,['present','late','absent','excused'],true)) $status='present';
    $q=$db->prepare('SELECT id FROM daham_pasala_attendance WHERE student_id=? AND attendance_date=? LIMIT 1');
    $q->execute([(string)$student['sid'],$date]);
    if($q->fetchColumn()) return 'Already recorded: '.(string)$student['sname'].' ('.$date.').';
    $q=$db->prepare('INSERT INTO daham_pasala_attendance (student_id, student_name, attendance_date, status, method, scanned_code, notes) VALUES (?,?,?,?,?,?,?)');
    $q->execute([(string)$student['sid'],(string)$student['sname'],$date,$status,$method,$code,$notes ?: null]);
    return 'Attendance saved: '.(string)$student['sname'].' — '.$status.'.';
}

$today = date('Y-m-d');
$date = (string)($_REQUEST['date'] ?? $today);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)) $date=$today;
$message=''; $messageType='info';

// Scanner sends AJAX here; return JSON before HTML output.
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='scan') {
    header('Content-Type: application/json; charset=utf-8');
    if(!hash_equals($csrf,(string)($_POST['csrf']??''))){http_response_code(403);echo json_encode(['ok'=>false,'message'=>'Security token expired. Refresh page.']);exit;}
    try {
        $code=trim((string)($_POST['code']??''));
        $student=attFindStudent($db,$code,$idCol,$nameCol,$admissionCol);
        if(!$student) throw new RuntimeException('Student not found. Scan the student ID/admission-number QR or barcode, or enter it manually.');
        $msg=attMark($db,$student,$date,'present','scan',$code);
        echo json_encode(['ok'=>true,'message'=>$msg,'student'=>$student['sname']],JSON_UNESCAPED_UNICODE);
    } catch(Throwable $e) {
        echo json_encode(['ok'=>false,'message'=>$e instanceof RuntimeException?$e->getMessage():'Unable to save attendance. Check the attendance SQL table.'],JSON_UNESCAPED_UNICODE);
        if(!($e instanceof RuntimeException)) error_log('Attendance scan error: '.$e->getMessage());
    }
    exit;
}

if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='manual') {
    if(!hash_equals($csrf,(string)($_POST['csrf']??''))){http_response_code(403);exit('Invalid security token. Refresh and retry.');}
    try {
        $sid=trim((string)($_POST['student_id']??''));
        $student=attFindStudent($db,$sid,$idCol,$nameCol,null);
        if(!$student) throw new RuntimeException('Student ID was not found.');
        $date=(string)($_POST['date']??$today);
        $status=(string)($_POST['status']??'present');
        $message=attMark($db,$student,$date,$status,'manual',$sid,trim((string)($_POST['notes']??'')));
        $messageType='success';
    } catch(Throwable $e) { $message=$e instanceof RuntimeException?$e->getMessage():'Could not save attendance. Import the SQL table and check error logs.'; $messageType='danger'; if(!($e instanceof RuntimeException))error_log($e->getMessage()); }
}

try {
    $q=$db->query('SELECT '.attQ($idCol).' AS sid, '.attQ($nameCol).' AS sname'.($admissionCol?', '.attQ($admissionCol).' AS admission_no':''). ' FROM daham_pasala_students ORDER BY '.attQ($nameCol).' ASC');
    $students=$q->fetchAll(PDO::FETCH_ASSOC);
    $q=$db->prepare('SELECT * FROM daham_pasala_attendance WHERE attendance_date=? ORDER BY created_at DESC, id DESC');
    $q->execute([$date]);$records=$q->fetchAll(PDO::FETCH_ASSOC);
    $countPresent=0;$countLate=0;$countAbsent=0;
    foreach($records as $r){if($r['status']==='present')$countPresent++;elseif($r['status']==='late')$countLate++;elseif($r['status']==='absent')$countAbsent++;}
} catch(Throwable $e) { error_log('Attendance list error: '.$e->getMessage()); $students=[];$records=[];$countPresent=0;$countLate=0;$countAbsent=0;$message=$message?:'Attendance table is missing. Import the supplied SQL file first.';$messageType='warning'; }
$pageTitle='දහම් පාසල් පැමිණීම';
require_once __DIR__ . '/../../../includes/header.php';
?>
<style>
.att-page{background:#f5f7fb;min-height:75vh;padding:24px;color:#202a44}.att-hero{border-radius:18px;color:#fff;background:linear-gradient(125deg,#202a44,#344b73 60%,#8b6a24)}.att-panel{border:1px solid #e9edf4;border-radius:15px;background:#fff;box-shadow:0 .25rem 1rem #1928460b}.att-muted{color:#778196}.att-video{width:100%;max-height:300px;background:#111;border-radius:12px;object-fit:cover}.att-result{min-height:25px}@media(max-width:767px){.att-page{padding:12px}}@media print{.no-print,.admin-sidebar,nav,footer{display:none!important}.admin-shell{display:block!important}.admin-main{width:100%!important}.att-page{padding:0;background:#fff}}
</style>
<div class="admin-shell"><aside class="admin-sidebar no-print"><?php require __DIR__ . '/../../../includes/sidebar.php'; ?></aside><main class="admin-main"><div class="att-page">
<section class="att-hero p-4 p-lg-5 mb-4"><div class="d-flex align-items-center justify-content-between flex-wrap gap-3"><div><div class="small mb-2" style="color:#ffe7a0;letter-spacing:.1em">TEMPLE DATA MANAGEMENT SYSTEM</div><h1 class="h3 fw-bold">ශිෂ්‍ය පැමිණීම</h1><p class="mb-0 text-white-50">QR / Barcode scan කර හෝ අතින් පැමිණීම සටහන් කරන්න.</p></div><i class="fa-solid fa-calendar-check fa-3x opacity-25"></i></div></section>
<?php if($message!==''): ?><div class="alert alert-<?=attH($messageType)?>"><?=attH($message)?></div><?php endif; ?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div><h2 class="h5 fw-bold mb-1">පැමිණීමේ සාරාංශය</h2><div class="att-muted small">දිනය: <?=attH($date)?></div></div><form class="d-flex gap-2 no-print"><input type="date" class="form-control" name="date" value="<?=attH($date)?>"><button class="btn btn-outline-primary">දිනය බලන්න</button></form></div>
<div class="row g-3 mb-4"><div class="col-4"><div class="att-panel p-3"><div class="att-muted small">පැමිණ ඇත</div><div class="h2 fw-bold text-success mb-0"><?=$countPresent?></div></div></div><div class="col-4"><div class="att-panel p-3"><div class="att-muted small">ප්‍රමාදයි</div><div class="h2 fw-bold text-warning mb-0"><?=$countLate?></div></div></div><div class="col-4"><div class="att-panel p-3"><div class="att-muted small">නොපැමිණි</div><div class="h2 fw-bold text-danger mb-0"><?=$countAbsent?></div></div></div></div>
<div class="row g-3 mb-4"><div class="col-lg-6"><section class="att-panel p-3 p-lg-4 h-100"><h2 class="h5 fw-bold"><i class="fa-solid fa-qrcode me-2 text-primary"></i>QR / Barcode scanner</h2><p class="att-muted small">QR/barcode එකේ student ID හෝ admission number එක තිබිය යුතුයි. Camera permission ලබා දෙන්න. USB barcode scanner එකකින් input field එකට scan කිරීමත් කළ හැකියි.</p><video id="attVideo" class="att-video mb-3" playsinline muted></video><div class="d-flex gap-2 flex-wrap no-print"><button type="button" class="btn btn-primary" id="startScan"><i class="fa-solid fa-camera me-1"></i> Camera Start</button><button type="button" class="btn btn-outline-secondary" id="stopScan">Stop</button></div><div id="scanResult" class="att-result small mt-3" role="status"></div><form id="scanForm" class="d-flex gap-2 mt-3 no-print"><input class="form-control" id="scanCode" placeholder="Scan/type student ID or admission no." autocomplete="off" required><button class="btn btn-success">Mark present</button></form><div class="small att-muted mt-2">Camera scanning සඳහා browser එකේ BarcodeDetector support අවශ්‍යයි. Support නැත්නම් manual/USB scanner එක භාවිත කරන්න.</div></section></div>
<div class="col-lg-6"><section class="att-panel p-3 p-lg-4 h-100"><h2 class="h5 fw-bold"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i>Manual attendance</h2><form method="post"><input type="hidden" name="csrf" value="<?=attH($csrf)?>"><input type="hidden" name="action" value="manual"><label class="form-label">ශිෂ්‍යයා *</label><select class="form-select mb-3" name="student_id" required><option value="">ශිෂ්‍යයෙක් තෝරන්න</option><?php foreach($students as $s): ?><option value="<?=attH($s['sid'])?>"><?=attH($s['sname'])?> — ID <?=attH($s['sid'])?><?=!empty($s['admission_no'])?' / '.attH($s['admission_no']):''?></option><?php endforeach; ?></select><label class="form-label">දිනය *</label><input type="date" name="date" class="form-control mb-3" value="<?=attH($date)?>" required><label class="form-label">තත්ත්වය *</label><select name="status" class="form-select mb-3"><option value="present">පැමිණ ඇත</option><option value="late">ප්‍රමාදයි</option><option value="absent">නොපැමිණි</option><option value="excused">නිදහස ලබා ඇත</option></select><label class="form-label">සටහන</label><input name="notes" class="form-control mb-3" placeholder="Optional note"><button class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> සුරකින්න</button></form></section></div></div>
<section class="att-panel p-3 p-lg-4"><div class="d-flex justify-content-between flex-wrap gap-2 mb-3"><h2 class="h5 fw-bold mb-0">පැමිණීමේ වාර්තා</h2><button class="btn btn-outline-secondary btn-sm no-print" onclick="window.print()"><i class="fa-solid fa-print me-1"></i> Print / PDF</button></div><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>ශිෂ්‍යයා</th><th>දිනය</th><th>තත්ත්වය</th><th>ක්‍රමය</th><th>සටහන</th><th>වේලාව</th></tr></thead><tbody><?php if(!$records): ?><tr><td colspan="6" class="text-center text-secondary p-4">මෙම දිනයට වාර්තා නැත.</td></tr><?php endif; ?><?php foreach($records as $r): ?><tr><td class="fw-semibold"><?=attH($r['student_name'])?><div class="small att-muted">ID: <?=attH($r['student_id'])?></div></td><td><?=attH($r['attendance_date'])?></td><td><span class="badge <?=['present'=>'text-bg-success','late'=>'text-bg-warning','absent'=>'text-bg-danger','excused'=>'text-bg-secondary'][$r['status']]??'text-bg-secondary'?>"><?=attH($r['status'])?></span></td><td><?=attH($r['method'])?></td><td><?=attH($r['notes']??'—')?></td><td><?=attH($r['created_at']??'')?></td></tr><?php endforeach; ?></tbody></table></div></section>
<div class="alert alert-warning mt-4"><strong>Face ID / fingerprint:</strong> මෙම PHP page එකෙන් පමණක් face recognition හෝ fingerprint reader එකක් ආරක්ෂිතව හඳුනාගන්න බැහැ. ඒ සඳහා compatible device/SDK සහ consent/privacy setup එකක් අවශ්‍යයි. දැනට QR/barcode සහ manual attendance ක්‍රියා කරයි.</div>
</div></main></div>
<script>
(() => {
 const video=document.getElementById('attVideo'), result=document.getElementById('scanResult'), input=document.getElementById('scanCode');
 const csrf=<?=json_encode($csrf)?>, date=<?=json_encode($date)?>;
 let stream=null, scanning=false, busy=false, detector=null;
 async function submitCode(code){code=(code||'').trim();if(!code||busy)return;busy=true;result.textContent='Checking student…';try{const body=new URLSearchParams({action:'scan',csrf,code,date});const res=await fetch(location.pathname+'?date='+encodeURIComponent(date),{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body});const data=await res.json();result.textContent=data.message||'Done';result.className='att-result small mt-3 '+(data.ok?'text-success':'text-danger');if(data.ok){input.value='';setTimeout(()=>location.reload(),900);}}catch(e){result.textContent='Could not contact the server. Try manual attendance.';result.className='att-result small mt-3 text-danger';}finally{busy=false;}}
 document.getElementById('scanForm').addEventListener('submit',e=>{e.preventDefault();submitCode(input.value);});
 document.getElementById('startScan').addEventListener('click',async()=>{if(!('BarcodeDetector' in window)){result.textContent='This browser does not support camera barcode scanning. Use the scan/type field or a USB scanner.';result.className='att-result small mt-3 text-danger';return;}try{detector=new BarcodeDetector({formats:['qr_code','code_128','code_39','ean_13','ean_8','upc_a','upc_e']});stream=await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'}},audio:false});video.srcObject=stream;await video.play();scanning=true;result.textContent='Camera active. Point it at the student QR/barcode.';scanLoop();}catch(e){result.textContent='Camera permission/device error. Allow camera access or use manual scanning.';result.className='att-result small mt-3 text-danger';}});
 async function scanLoop(){if(!scanning||!detector)return;try{const codes=await detector.detect(video);if(codes.length&&codes[0].rawValue){scanning=false;stopCamera();await submitCode(codes[0].rawValue);return;}}catch(e){}requestAnimationFrame(scanLoop);}
 function stopCamera(){scanning=false;if(stream){stream.getTracks().forEach(t=>t.stop());stream=null;}video.srcObject=null;}
 document.getElementById('stopScan').addEventListener('click',()=>{stopCamera();result.textContent='Camera stopped.';});
 window.addEventListener('beforeunload',stopCamera);
})();
</script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
