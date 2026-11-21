
<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';

Auth::requireAuth();
Auth::requirePermission('view_dayakayan');

$db = Database::getConnection();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['dp_csrf'])) {
    $_SESSION['dp_csrf'] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION['dp_csrf'];
$base = defined('ADMIN_URL')
    ? rtrim((string) ADMIN_URL, '/')
    : '/temple/admin';

$pageTitle = 'දහම් පාසල් විභාග ලකුණු ඇතුළත් කිරීම';
$message = '';
$messageType = 'info';
$errors = [];

function resultH(mixed $v): string
{
    return htmlspecialchars(
        (string) ($v ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

$examName = trim((string) ($_POST['exam_name'] ?? ''));
$examYear = (string) ($_POST['exam_year'] ?? date('Y'));
$studentId = (int) ($_POST['student_id'] ?? 0);
$subject = trim((string) ($_POST['subject'] ?? ''));
$marksInput = trim((string) ($_POST['marks'] ?? ''));
$maxMarksInput = trim((string) ($_POST['max_marks'] ?? '100'));

$students = [];
$recentResults = [];

try {
    $q = $db->query(
        "SELECT id, full_name, grade, class_name
         FROM daham_pasala_students
         WHERE status = 'active'
         ORDER BY grade, class_name, full_name"
    );

    $students = $q->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Daham Pasala students query error: ' . $e->getMessage());
    $errors[] = 'සිසුන්ගේ තොරතුරු ලබාගැනීමට නොහැකි විය. Database table එක පරීක්ෂා කරන්න.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(
        $csrf,
        (string) ($_POST['csrf'] ?? '')
    )) {
        http_response_code(403);
        exit('Invalid security token. Refresh the page and try again.');
    }

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save') {
        if ($examName === '') {
            $errors[] = 'විභාගයේ නම ඇතුළත් කරන්න.';
        }

        if (
            !ctype_digit($examYear) ||
            (int) $examYear < 2000 ||
            (int) $examYear > 2100
        ) {
            $errors[] = 'නිවැරදි විභාග වර්ෂයක් තෝරන්න.';
        }

        if ($studentId <= 0) {
            $errors[] = 'ලියාපදිංචි සිසුවෙකු තෝරන්න.';
        }

        if ($subject === '' || mb_strlen($subject) > 150) {
            $errors[] = 'නිවැරදි විෂය නාමයක් ඇතුළත් කරන්න.';
        }

        if (
            $marksInput === '' ||
            !is_numeric($marksInput)
        ) {
            $errors[] = 'ලබාගත් ලකුණු නිවැරදිව ඇතුළත් කරන්න.';
        }

        if (
            $maxMarksInput === '' ||
            !is_numeric($maxMarksInput)
        ) {
            $errors[] = 'උපරිම ලකුණු නිවැරදිව ඇතුළත් කරන්න.';
        }

        $marks = is_numeric($marksInput)
            ? (float) $marksInput
            : -1;

        $maxMarks = is_numeric($maxMarksInput)
            ? (float) $maxMarksInput
            : 0;

        if ($maxMarks <= 0 || $maxMarks > 999.99) {
            $errors[] = 'උපරිම ලකුණු 0 ට වැඩි සහ 999.99 හෝ ඊට අඩු විය යුතුය.';
        }

        if (
            $marks < 0 ||
            ($maxMarks > 0 && $marks > $maxMarks)
        ) {
            $errors[] = 'ලබාගත් ලකුණු 0 සහ උපරිම ලකුණු අතර විය යුතුය.';
        }

        if (!$errors) {
            try {
                $studentQuery = $db->prepare(
                    "SELECT id, full_name, grade, class_name
                     FROM daham_pasala_students
                     WHERE id = ?
                       AND status = 'active'
                     LIMIT 1"
                );

                $studentQuery->execute([$studentId]);
                $student = $studentQuery->fetch(PDO::FETCH_ASSOC);

                if (!$student) {
                    $errors[] = 'තෝරාගත් සක්‍රීය සිසුවා හමු නොවීය.';
                } else {
                    $insert = $db->prepare(
                        "INSERT INTO daham_pasala_exam_results
                        (
                            student_id,
                            exam_name,
                            exam_year,
                            grade,
                            class_name,
                            subject,
                            marks,
                            max_marks
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                    );

                    $insert->execute([
                        (int) $student['id'],
                        $examName,
                        (int) $examYear,
                        (string) ($student['grade'] ?? ''),
                        (string) ($student['class_name'] ?? ''),
                        $subject,
                        $marks,
                        $maxMarks
                    ]);

                    $message = 'විභාග ලකුණු සාර්ථකව සුරක්ෂිත කරන ලදී.';
                    $messageType = 'success';

                    $examName = '';
                    $examYear = date('Y');
                    $studentId = 0;
                    $subject = '';
                    $marksInput = '';
                    $maxMarksInput = '100';
                }
            } catch (Throwable $e) {
                error_log('Daham Pasala result insert error: ' . $e->getMessage());
                $errors[] = 'ලකුණු සුරක්ෂිත කිරීමට නොහැකි විය. Database table සහ columns පරීක්ෂා කරන්න.';
            }
        }
    }
}

try {
    $q = $db->query(
        "SELECT r.id, r.exam_name, r.exam_year, r.grade,
                r.class_name, r.subject, r.marks, r.max_marks,
                s.full_name
         FROM daham_pasala_exam_results r
         LEFT JOIN daham_pasala_students s
             ON s.id = r.student_id
         ORDER BY r.id DESC
         LIMIT 8"
    );

    $recentResults = $q->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Daham Pasala recent results error: ' . $e->getMessage());
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<style>
.dp-page {
    background: #f5f7fb;
    min-height: 75vh;
    padding: 24px;
    color: #202a44;
}

.dp-hero {
    border-radius: 18px;
    color: #fff;
    background: linear-gradient(
        125deg,
        #202a44,
        #344b73 60%,
        #8b6a24
    );
}

.dp-panel {
    border: 1px solid #e9edf4;
    border-radius: 15px;
    background: #fff;
    box-shadow: 0 .25rem 1rem #1928460b;
}

.dp-muted {
    color: #778196;
}

.dp-page .form-label {
    color: #303b54;
    font-size: .9rem;
    font-weight: 650;
    margin-bottom: .45rem;
}

.dp-page .form-control,
.dp-page .form-select {
    min-height: 43px;
    border-color: #dfe5ef;
    border-radius: 9px;
}

.dp-page .form-control:focus,
.dp-page .form-select:focus {
    border-color: #7186b1;
    box-shadow: 0 0 0 .2rem #344b7318;
}

.dp-stat {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 16px;
    border: 1px solid #e9edf4;
    border-radius: 13px;
    background: #fff;
    height: 100%;
}

.dp-stat-icon {
    display: grid;
    place-items: center;
    flex: 0 0 44px;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #edf2fb;
    color: #344b73;
    font-size: 19px;
}

.dp-stat small {
    color: #778196;
    display: block;
    margin-bottom: 3px;
}

.dp-stat strong {
    font-size: 1.25rem;
    color: #202a44;
}

.dp-page .table thead th {
    background: #f7f8fc;
    color: #647087;
    font-size: .82rem;
    white-space: nowrap;
    border-bottom-color: #e9edf4;
}

.dp-page .table td {
    font-size: .9rem;
}

.dp-page .btn {
    border-radius: 8px;
}

.dp-student-preview {
    background: #f5f7fc;
    border: 1px solid #e7ebf4;
    border-radius: 11px;
    padding: 14px 16px;
}

.dp-student-preview .preview-label {
    display: block;
    color: #778196;
    font-size: .8rem;
    margin-bottom: 3px;
}

.dp-student-preview .preview-value {
    font-weight: 650;
    color: #202a44;
}

.dp-required {
    color: #dc3545;
}

@media(max-width: 767px) {
    .dp-page {
        padding: 12px;
    }

    .dp-hero {
        padding: 22px !important;
    }
}

@media print {
    .no-print,
    .admin-sidebar,
    nav,
    footer {
        display: none !important;
    }

    .admin-shell {
        display: block !important;
    }

    .admin-main {
        width: 100% !important;
    }

    .dp-page {
        padding: 0;
        background: #fff;
    }

    .dp-panel {
        box-shadow: none;
        break-inside: avoid;
    }
}
</style>

<div class="admin-shell">
    <aside class="admin-sidebar no-print">
        <?php require __DIR__ . '/../../../includes/sidebar.php'; ?>
    </aside>

    <main class="admin-main">
        <div class="dp-page">

            <section class="dp-hero p-4 p-lg-5 mb-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <div class="small mb-2"
                             style="color:#ffe7a0;letter-spacing:.1em">
                            TEMPLE DATA MANAGEMENT SYSTEM
                        </div>

                        <h1 class="h3 fw-bold mb-2">
                            <i class="fa-solid fa-file-pen me-2"></i>
                            විභාග ලකුණු ඇතුළත් කිරීම
                        </h1>

                        <p class="mb-0 text-white-50">
                            සිසුන්ගේ විභාග ප්‍රතිඵල සහ විෂය ලකුණු කළමනාකරණය කරන්න.
                        </p>
                    </div>

                    <i class="fa-solid fa-graduation-cap fa-3x opacity-25"></i>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4 no-print">
                    <a href="index.php"
                       class="btn btn-light btn-sm">
                        <i class="fa-solid fa-chart-column me-1"></i>
                        විභාග වාර්තා
                    </a>
                </div>
            </section>

            <?php if ($message !== ''): ?>
                <div class="alert alert-<?=resultH($messageType)?> alert-dismissible fade show">
                    <i class="fa-solid fa-circle-check me-2"></i>
                    <?=resultH($message)?>
                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <div class="fw-bold mb-1">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        කරුණාකර පහත කරුණු පරීක්ෂා කරන්න.
                    </div>

                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?=resultH($error)?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-4">
                    <div class="dp-stat">
                        <div class="dp-stat-icon">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div>
                            <small>සක්‍රීය සිසුන්</small>
                            <strong><?=count($students)?></strong>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <div class="dp-stat">
                        <div class="dp-stat-icon">
                            <i class="fa-solid fa-clipboard-check"></i>
                        </div>
                        <div>
                            <small>මෑත ප්‍රතිඵල</small>
                            <strong><?=count($recentResults)?></strong>
                            <div class="dp-muted small">අලුත්ම වාර්තා 8 දක්වා</div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-12 col-xl-4">
                    <div class="dp-stat">
                        <div class="dp-stat-icon">
                            <i class="fa-solid fa-calendar-days"></i>
                        </div>
                        <div>
                            <small>වත්මන් වර්ෂය</small>
                            <strong><?=date('Y')?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">

                <div class="col-lg-5">
                    <section class="dp-panel p-3 p-lg-4">
                        <div class="d-flex align-items-center gap-2 mb-4">
                            <div class="dp-stat-icon">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </div>
                            <div>
                                <h2 class="h5 fw-bold mb-1">
                                    නව ප්‍රතිඵලයක්
                                </h2>
                                <div class="dp-muted small">
                                    අවශ්‍ය තොරතුරු ඇතුළත් කරන්න.
                                </div>
                            </div>
                        </div>

                        <form method="post" action="">
                            <input type="hidden"
                                   name="csrf"
                                   value="<?=resultH($csrf)?>">

                            <input type="hidden"
                                   name="action"
                                   value="save">

                            <div class="mb-3">
                                <label class="form-label" for="exam_name">
                                    විභාගයේ නම <span class="dp-required">*</span>
                                </label>

                                <input type="text"
                                       class="form-control"
                                       id="exam_name"
                                       name="exam_name"
                                       maxlength="150"
                                       placeholder="උදා: වාර්ෂික විභාගය"
                                       value="<?=resultH($examName)?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="exam_year">
                                    විභාග වර්ෂය <span class="dp-required">*</span>
                                </label>

                                <select class="form-select"
                                        id="exam_year"
                                        name="exam_year"
                                        required>
                                    <?php for ($y = (int)date('Y') + 1; $y >= 2000; $y--): ?>
                                        <option value="<?=$y?>"
                                            <?=(string)$y === $examYear ? 'selected' : ''?>>
                                            <?=$y?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="student_id">
                                    සිසුවා <span class="dp-required">*</span>
                                </label>

                                <select class="form-select"
                                        id="student_id"
                                        name="student_id"
                                        required>
                                    <option value="">-- ලියාපදිංචි සිසුවෙකු තෝරන්න --</option>

                                    <?php foreach ($students as $student): ?>
                                        <option
                                            value="<?=(int)$student['id']?>"
                                            data-grade="<?=resultH($student['grade'] ?? '')?>"
                                            data-class="<?=resultH($student['class_name'] ?? '')?>"
                                            <?=(int)$studentId === (int)$student['id'] ? 'selected' : ''?>>
                                            <?=resultH($student['full_name'])?>
                                            — <?=resultH($student['grade'] ?? 'ශ්‍රේණිය සඳහන් නැත')?>
                                            / <?=resultH($student['class_name'] ?? 'පන්තිය සඳහන් නැත')?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <?php if (!$students): ?>
                                    <div class="form-text text-danger">
                                        සක්‍රීය සිසුන් හමු නොවීය. සිසුන්ගේ ලියාපදිංචිය පරීක්ෂා කරන්න.
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="dp-student-preview mb-3">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <span class="preview-label">ශ්‍රේණිය</span>
                                        <span class="preview-value" id="preview-grade">—</span>
                                    </div>

                                    <div class="col-6">
                                        <span class="preview-label">පන්තිය</span>
                                        <span class="preview-value" id="preview-class">—</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="subject">
                                    විෂය <span class="dp-required">*</span>
                                </label>

                                <input type="text"
                                       class="form-control"
                                       id="subject"
                                       name="subject"
                                       maxlength="150"
                                       placeholder="උදා: බුද්ධ ධර්මය"
                                       value="<?=resultH($subject)?>"
                                       required>
                            </div>

                            <div class="row g-3">
                                <div class="col-6 mb-3">
                                    <label class="form-label" for="marks">
                                        ලබාගත් ලකුණු <span class="dp-required">*</span>
                                    </label>

                                    <input type="number"
                                           class="form-control"
                                           id="marks"
                                           name="marks"
                                           min="0"
                                           max="999.99"
                                           step="0.01"
                                           placeholder="85"
                                           value="<?=resultH($marksInput)?>"
                                           required>
                                </div>

                                <div class="col-6 mb-3">
                                    <label class="form-label" for="max_marks">
                                        උපරිම ලකුණු <span class="dp-required">*</span>
                                    </label>

                                    <input type="number"
                                           class="form-control"
                                           id="max_marks"
                                           name="max_marks"
                                           min="0.01"
                                           max="999.99"
                                           step="0.01"
                                           value="<?=resultH($maxMarksInput)?>"
                                           required>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <button type="submit"
                                        class="btn btn-primary"
                                        <?=!$students ? 'disabled' : ''?>>
                                    <i class="fa-solid fa-floppy-disk me-1"></i>
                                    ලකුණු සුරකින්න
                                </button>

                                <button type="reset"
                                        class="btn btn-outline-secondary"
                                        id="reset-form">
                                    <i class="fa-solid fa-rotate-left me-1"></i>
                                    නැවත සකසන්න
                                </button>
                            </div>
                        </form>
                    </section>
                </div>

                <div class="col-lg-7">
                    <section class="dp-panel p-3 p-lg-4">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <div>
                                <h2 class="h5 fw-bold mb-1">
                                    <i class="fa-solid fa-clock-rotate-left me-2"></i>
                                    මෑතකදී ඇතුළත් කළ ප්‍රතිඵල
                                </h2>
                                <p class="dp-muted small mb-0">
                                    අවසන් වරට සුරක්ෂිත කළ ප්‍රතිඵල 8 දක්වා.
                                </p>
                            </div>

                            <button type="button"
                                    onclick="window.print()"
                                    class="btn btn-sm btn-outline-secondary no-print">
                                <i class="fa-solid fa-print me-1"></i>
                                Print / PDF
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>සිසුවා</th>
                                        <th>විෂය / විභාගය</th>
                                        <th>ලකුණු</th>
                                        <th>වර්ෂය</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php if (!$recentResults): ?>
                                        <tr>
                                            <td colspan="4"
                                                class="text-center text-secondary p-4">
                                                <i class="fa-solid fa-inbox fa-2x mb-2 d-block"></i>
                                                තවමත් ප්‍රතිඵල ඇතුළත් කර නැත.
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php foreach ($recentResults as $result): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">
                                                    <?=resultH($result['full_name'] ?? 'සිසුවා හමු නොවීය')?>
                                                </div>
                                                <div class="dp-muted small">
                                                    <?=resultH($result['grade'] ?? '')?>
                                                    <?=resultH($result['class_name'] ?? '')?>
                                                </div>
                                            </td>

                                            <td>
                                                <div class="fw-semibold">
                                                    <?=resultH($result['subject'])?>
                                                </div>
                                                <div class="dp-muted small">
                                                    <?=resultH($result['exam_name'])?>
                                                </div>
                                            </td>

                                            <td>
                                                <span class="fw-bold">
                                                    <?=resultH($result['marks'])?>
                                                </span>
                                                <span class="dp-muted">
                                                    / <?=resultH($result['max_marks'])?>
                                                </span>
                                            </td>

                                            <td><?=resultH($result['exam_year'])?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="small dp-muted mt-2">
                            <i class="fa-solid fa-circle-info me-1"></i>
                            සම්පූර්ණ වාර්තා බැලීමට විභාග වාර්තා පිටුවට යන්න.
                        </div>
                    </section>
                </div>

            </div>
        </div>
    </main>
</div>

<script>
(function () {
    const studentSelect = document.getElementById('student_id');
    const gradeDisplay = document.getElementById('preview-grade');
    const classDisplay = document.getElementById('preview-class');
    const resetButton = document.getElementById('reset-form');

    function updateStudentPreview() {
        const option = studentSelect.options[studentSelect.selectedIndex];

        if (!option || !option.value) {
            gradeDisplay.textContent = '—';
            classDisplay.textContent = '—';
            return;
        }

        gradeDisplay.textContent = option.dataset.grade || '—';
        classDisplay.textContent = option.dataset.class || '—';
    }

    studentSelect.addEventListener('change', updateStudentPreview);

    resetButton.addEventListener('click', function () {
        window.setTimeout(updateStudentPreview, 0);
    });

    updateStudentPreview();
})();
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
