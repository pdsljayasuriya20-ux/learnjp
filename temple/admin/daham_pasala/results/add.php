
<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';

Auth::requireAuth();
Auth::requirePermission('view_dayakayan');

$db = Database::getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pageTitle = 'දහම් පාසල් විභාග ලකුණු ඇතුළත් කිරීම';

function resultEscape($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$errors = [];
$success = '';

$examName = trim((string) ($_POST['exam_name'] ?? ''));
$examYear = (int) ($_POST['exam_year'] ?? date('Y'));
$subject = trim((string) ($_POST['subject'] ?? ''));
$studentId = (int) ($_POST['student_id'] ?? 0);
$marksInput = trim((string) ($_POST['marks'] ?? ''));
$maxMarksInput = trim((string) ($_POST['max_marks'] ?? '100'));

$students = [];
$selectedStudent = null;

try {
    $studentQuery = $db->query(
        "SELECT id, full_name, grade, class_name
         FROM daham_pasala_students
         ORDER BY grade ASC, class_name ASC, full_name ASC"
    );

    $students = $studentQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = 'සිසුන්ගේ තොරතුරු ලබාගැනීමට නොහැකි විය. Database එක පරීක්ෂා කරන්න.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($examName === '') {
        $errors[] = 'විභාගයේ නම ඇතුළත් කරන්න.';
    }

    if ($examYear < 2000 || $examYear > 2100) {
        $errors[] = 'නිවැරදි විභාග වර්ෂයක් ඇතුළත් කරන්න.';
    }

    if ($subject === '') {
        $errors[] = 'විෂය ඇතුළත් කරන්න.';
    }

    if (strlen($subject) > 150) {
        $errors[] = 'විෂය නාමය අක්ෂර 150කට සීමා කරන්න.';
    }

    if ($studentId <= 0) {
        $errors[] = 'ලියාපදිංචි සිසුවෙකු තෝරන්න.';
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
        $errors[] = 'උපරිම ලකුණු 0 ට වැඩි සහ 999.99 ට අඩු විය යුතුය.';
    }

    if ($marks < 0 || ($maxMarks > 0 && $marks > $maxMarks)) {
        $errors[] = 'ලබාගත් ලකුණු 0 සිට උපරිම ලකුණු දක්වා විය යුතුය.';
    }

    if ($studentId > 0) {
        try {
            $studentStmt = $db->prepare(
                "SELECT id, full_name, grade, class_name
                 FROM daham_pasala_students
                 WHERE id = :id
                 LIMIT 1"
            );

            $studentStmt->execute(['id' => $studentId]);
            $selectedStudent = $studentStmt->fetch(PDO::FETCH_ASSOC);

            if (!$selectedStudent) {
                $errors[] = 'තෝරාගත් සිසුවා හමු නොවීය.';
            }
        } catch (PDOException $e) {
            $errors[] = 'සිසුවාගේ තොරතුරු පරීක්ෂා කිරීමේදී දෝෂයක් ඇති විය.';
        }
    }

    if (empty($errors) && $selectedStudent) {
        try {
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
                VALUES
                (
                    :student_id,
                    :exam_name,
                    :exam_year,
                    :grade,
                    :class_name,
                    :subject,
                    :marks,
                    :max_marks
                )"
            );

            $insert->execute([
                'student_id' => $selectedStudent['id'],
                'exam_name'  => $examName,
                'exam_year'  => $examYear,
                'grade'      => $selectedStudent['grade'],
                'class_name' => $selectedStudent['class_name'],
                'subject'    => $subject,
                'marks'      => $marks,
                'max_marks'  => $maxMarks,
            ]);

            $success = 'විභාග ලකුණු සාර්ථකව සුරක්ෂිත කරන ලදී.';

            $examName = '';
            $subject = '';
            $studentId = 0;
            $marksInput = '';
            $maxMarksInput = '100';
            $selectedStudent = null;
        } catch (PDOException $e) {
            $errors[] = 'ලකුණු සුරක්ෂිත කිරීමට නොහැකි විය. Database එක පරීක්ෂා කරන්න.';
        }
    }
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<style>
.result-page {
    padding: 24px;
    color: #1f2937;
}

.result-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    margin-bottom: 24px;
}

.result-heading h2 {
    margin: 0 0 6px;
    font-size: 25px;
    font-weight: 750;
}

.result-heading p {
    margin: 0;
    color: #6b7280;
}

.result-card {
    max-width: 850px;
    margin: 0 auto;
    overflow: hidden;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    box-shadow: 0 5px 22px rgba(15, 23, 42, .06);
}

.result-card-header {
    padding: 22px 26px;
    background: #0f2747;
    color: #fff;
}

.result-card-header h3 {
    margin: 0 0 5px;
    font-size: 19px;
}

.result-card-header p {
    margin: 0;
    color: #dbeafe;
    font-size: 14px;
}

.result-form {
    padding: 26px;
}

.result-form .form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 650;
}

.result-form .form-control,
.result-form .form-select {
    width: 100%;
    min-height: 46px;
    border: 1px solid #d1d5db;
    border-radius: 9px;
    padding: 10px 12px;
    background-color: #fff;
}

.result-form .form-control:focus,
.result-form .form-select:focus {
    border-color: #007aff;
    outline: 3px solid rgba(0, 122, 255, .12);
}

.result-form .field {
    margin-bottom: 20px;
}

.student-info {
    padding: 14px 16px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    margin-bottom: 22px;
}

.student-info strong {
    color: #1d4ed8;
}

.result-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 10px;
}

.result-actions .btn {
    min-height: 44px;
    border-radius: 9px;
    padding: 10px 18px;
    font-weight: 650;
}

@media (max-width: 600px) {
    .result-page {
        padding: 14px;
    }

    .result-form {
        padding: 18px;
    }

    .result-card-header {
        padding: 20px 18px;
    }

    .result-heading h2 {
        font-size: 21px;
    }
}

@media print {
    .result-actions,
    .result-heading a,
    .sidebar,
    aside {
        display: none !important;
    }

    .result-page {
        padding: 0;
    }

    .result-card {
        box-shadow: none;
    }
}
</style>

<div class="admin-shell">
    <?php require __DIR__ . '/../../../includes/sidebar.php'; ?>

    <main class="admin-content">
        <div class="result-page">

            <div class="result-heading">
                <div>
                    <h2>
                        <i class="bi bi-journal-check"></i>
                        විභාග ලකුණු ඇතුළත් කිරීම
                    </h2>
                    <p>ලියාපදිංචි සිසුන්ගේ විෂය ලකුණු සුරක්ෂිත කරන්න.</p>
                </div>

                <a
                    href="../reports/index.php"
                    class="btn btn-outline-secondary"
                >
                    <i class="bi bi-arrow-left"></i>
                    වාර්තා වෙත
                </a>
            </div>

            <?php if ($success !== ''): ?>
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    <?= resultEscape($success) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <strong>කරුණාකර පහත කරුණු පරීක්ෂා කරන්න:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?= resultEscape($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <section class="result-card">
                <div class="result-card-header">
                    <h3>
                        <i class="bi bi-pencil-square"></i>
                        නව විභාග ප්‍රතිඵලය
                    </h3>
                    <p>සියලුම අනිවාර්ය තොරතුරු නිවැරදිව ඇතුළත් කරන්න.</p>
                </div>

                <form method="POST" class="result-form">

                    <div class="field">
                        <label class="form-label" for="exam_name">
                            විභාගයේ නම *
                        </label>
                        <input
                            type="text"
                            class="form-control"
                            id="exam_name"
                            name="exam_name"
                            maxlength="150"
                            placeholder="උදා: වාර්ෂික විභාගය"
                            value="<?= resultEscape($examName) ?>"
                            required
                        >
                    </div>

                    <div class="field">
                        <label class="form-label" for="exam_year">
                            විභාග වර්ෂය *
                        </label>
                        <input
                            type="number"
                            class="form-control"
                            id="exam_year"
                            name="exam_year"
                            min="2000"
                            max="2100"
                            value="<?= resultEscape($examYear) ?>"
                            required
                        >
                    </div>

                    <div class="field">
                        <label class="form-label" for="student_id">
                            ලියාපදිංචි සිසුවා තෝරන්න *
                        </label>

                        <select
                            class="form-select"
                            id="student_id"
                            name="student_id"
                            required
                        >
                            <option value="">-- සිසුවෙකු තෝරන්න --</option>

                            <?php foreach ($students as $student): ?>
                                <option
                                    value="<?= (int) $student['id'] ?>"
                                    data-grade="<?= resultEscape($student['grade']) ?>"
                                    data-class="<?= resultEscape($student['class_name']) ?>"
                                    <?= $studentId === (int) $student['id'] ? 'selected' : '' ?>
                                >
                                    <?= resultEscape($student['full_name']) ?>
                                    — ශ්‍රේණිය <?= resultEscape($student['grade']) ?>
                                    — පන්තිය <?= resultEscape($student['class_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <?php if (empty($students)): ?>
                            <small class="text-danger">
                                ලියාපදිංචි සිසුන් හමු නොවීය.
                                පළමුව සිසුන් ලියාපදිංචි කරන්න.
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="student-info">
                        <div>
                            <strong>ශ්‍රේණිය:</strong>
                            <span id="selected-grade">—</span>
                        </div>
                        <div class="mt-1">
                            <strong>පන්තිය:</strong>
                            <span id="selected-class">—</span>
                        </div>
                        <small class="text-muted">
                            සිසුවා තෝරාගත් විට මෙම තොරතුරු ස්වයංක්‍රීයව පෙන්වයි.
                        </small>
                    </div>

                    <div class="field">
                        <label class="form-label" for="subject">
                            විෂය *
                        </label>
                        <input
                            type="text"
                            class="form-control"
                            id="subject"
                            name="subject"
                            maxlength="150"
                            placeholder="උදා: බුද්ධ ධර්මය"
                            value="<?= resultEscape($subject) ?>"
                            required
                        >
                    </div>

                    <div class="row">
                        <div class="col-md-6 field">
                            <label class="form-label" for="marks">
                                ලබාගත් ලකුණු *
                            </label>
                            <input
                                type="number"
                                class="form-control"
                                id="marks"
                                name="marks"
                                min="0"
                                step="0.01"
                                placeholder="උදා: 85"
                                value="<?= resultEscape($marksInput) ?>"
                                required
                            >
                        </div>

                        <div class="col-md-6 field">
                            <label class="form-label" for="max_marks">
                                උපරිම ලකුණු *
                            </label>
                            <input
                                type="number"
                                class="form-control"
                                id="max_marks"
                                name="max_marks"
                                min="0.01"
                                max="999.99"
                                step="0.01"
                                value="<?= resultEscape($maxMarksInput) ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="result-actions">
                        <button
                            type="submit"
                            class="btn btn-primary"
                            <?= empty($students) ? 'disabled' : '' ?>
                        >
                            <i class="bi bi-save"></i>
                            ලකුණු සුරක්ෂිත කරන්න
                        </button>

                        <button
                            type="reset"
                            class="btn btn-outline-secondary"
                            id="reset-form"
                        >
                            <i class="bi bi-arrow-counterclockwise"></i>
                            නැවත සකසන්න
                        </button>
                    </div>

                </form>
            </section>

        </div>
    </main>
</div>

<script>
(function () {
    const studentSelect = document.getElementById('student_id');
    const gradeDisplay = document.getElementById('selected-grade');
    const classDisplay = document.getElementById('selected-class');
    const resetButton = document.getElementById('reset-form');

    function updateStudentInfo() {
        const option = studentSelect.options[studentSelect.selectedIndex];

        if (!option || !option.value) {
            gradeDisplay.textContent = '—';
            classDisplay.textContent = '—';
            return;
        }

        gradeDisplay.textContent = option.dataset.grade || '—';
        classDisplay.textContent = option.dataset.class || '—';
    }

    studentSelect.addEventListener('change', updateStudentInfo);

    resetButton.addEventListener('click', function () {
        setTimeout(updateStudentInfo, 0);
    });

    updateStudentInfo();
})();
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
