<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';

Auth::requirePermission('view_dayakayan');

$db = Database::getConnection();
$table = 'daham_pasala_students';

$columns = $db->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
$colNames = array_column($columns, 'Field');

$pick = static function (array $choices) use ($colNames): ?string {
    foreach ($choices as $column) {
        if (in_array($column, $choicesFromTable = $colNames, true)) {
            return $column;
        }
    }
    return null;
};

$idCol = $pick(['id', 'student_id']);
$nameCol = $pick(['full_name', 'student_name', 'name', 'name_with_initials', 'child_name']);
$photoCol = $pick(['photo_path', 'profile_photo', 'student_photo', 'photo', 'image', 'image_path']);

if (!$idCol || !$nameCol) {
    http_response_code(500);
    exit('Student table is missing a recognizable ID or name column.');
}

$id = trim((string) ($_GET['id'] ?? ''));

if ($id === '') {
    http_response_code(400);
    exit('Student ID is required.');
}

$stmt = $db->prepare("SELECT * FROM `$table` WHERE `$idCol` = :student_id LIMIT 1");
$stmt->execute(['student_id' => $id]);
$s = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$s) {
    http_response_code(404);
    exit('Student not found.');
}

$baseUrl = defined('BASE_URL') ? rtrim((string) BASE_URL, '/') : '/temple';
$adminUrl = defined('ADMIN_URL') ? rtrim((string) ADMIN_URL, '/') : $baseUrl . '/admin';

$photo = '';

if ($photoCol) {
    $storedPhoto = trim((string) ($s[$photoCol] ?? ''));

    if ($storedPhoto !== '') {
        if (preg_match('~^https?://~i', $storedPhoto)) {
            $photo = $storedPhoto;
        } elseif (!preg_match('~^[a-z][a-z0-9+.-]*:~i', $storedPhoto)) {
            $storedPhoto = str_replace('\\', '/', $storedPhoto);
            $photo = strpos($storedPhoto, '/') === 0
                ? $storedPhoto
                : $baseUrl . '/' . ltrim($storedPhoto, '/');
        }
    }
}

$sid = (string) ($s[$idCol] ?? '');
$sname = (string) ($s[$nameCol] ?? '');

$numberCol = $pick(['student_number', 'admission_no', 'registration_no', 'index_no']);
$studentNumber = $numberCol ? trim((string) ($s[$numberCol] ?? '')) : '';

if ($studentNumber === '') {
    $studentNumber = $sid;
}

$gradeCol = $pick(['grade', 'class_grade', 'grade_name', 'level']);
$classCol = $pick(['class_name', 'class']);
$dobCol = $pick(['dob', 'date_of_birth', 'birth_date']);
$genderCol = $pick(['gender', 'sex']);
$statusCol = $pick(['status']);
$parentCol = $pick(['parent_dayakaya_id', 'parent_id', 'guardian_id']);

$grade = $gradeCol ? trim((string) ($s[$gradeCol] ?? '')) : '';
$className = $classCol ? trim((string) ($s[$classCol] ?? '')) : '';

require_once __DIR__ . '/../../../includes/header.php';
?>

<style>
.student-print-page {
    max-width: 1000px;
    margin: 0 auto;
}

.student-page-heading {
    color: #123d69;
}

.id-large {
    width: 390px;
    max-width: 100%;
    border: 1px solid #d8dee8;
    border-radius: 14px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 4px 16px rgba(23, 43, 77, .08);
    break-inside: avoid;
}

.id-top {
    background: linear-gradient(110deg, #123d69, #1976a5);
    color: #fff;
    padding: 15px;
}

.student-photo {
    width: 100px;
    height: 120px;
    flex: 0 0 100px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    background: #f1f3f5;
}

.student-placeholder {
    width: 100px;
    height: 120px;
    flex: 0 0 100px;
    border-radius: 8px;
    background: #edf1f6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    color: #748094;
}

.id-student-details {
    overflow-wrap: anywhere;
}

.profile-sheet {
    border: 1px solid #dfe4ec;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    break-inside: avoid;
}

.profile-fields {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px 14px;
    padding: 18px;
}

.profile-field {
    border-bottom: 1px solid #edf0f4;
    padding: 8px;
    min-width: 0;
    overflow-wrap: anywhere;
}

.profile-field small {
    display: block;
    color: #6c757d;
    margin-bottom: 4px;
}

@media (max-width: 576px) {
    .profile-fields {
        grid-template-columns: 1fr;
        padding: 12px;
    }
}

@media print {
    body {
        background: #fff !important;
    }

    .admin-sidebar,
    nav,
    footer,
    .no-print {
        display: none !important;
    }

    .admin-shell {
        display: block !important;
    }

    .admin-main {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .student-print-page {
        max-width: none;
        padding: 0 !important;
    }

    .id-large,
    .profile-sheet {
        box-shadow: none;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .print-break {
        break-before: page;
        page-break-before: always;
    }

    @page {
        size: A4;
        margin: 14mm;
    }
}
</style>

<div class="admin-shell">
    <aside class="admin-sidebar no-print">
        <?php require __DIR__ . '/../../../includes/sidebar.php'; ?>
    </aside>

    <main class="admin-main">
        <div class="student-print-page p-3 p-md-4">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 no-print">
                <a class="btn btn-outline-secondary"
                   href="<?= e($adminUrl) ?>/daham_pasala/students/cards.php">
                    <i class="fa-solid fa-arrow-left me-1"></i>
                    සියලු කාඩ්පත්
                </a>

                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="fa-solid fa-print me-1"></i>
                    Print / Save as PDF
                </button>
            </div>

            <h4 class="student-page-heading fw-bold mb-3">
                <i class="fa-solid fa-id-card me-2"></i>
                ශිෂ්‍ය හැඳුනුම්පත
            </h4>

            <section class="id-large mb-4">
                <div class="id-top d-flex justify-content-between align-items-center">
                    <div>
                        <strong>දහම් පාසල</strong>
                        <div class="small">STUDENT ID CARD</div>
                    </div>
                    <i class="fa-solid fa-dharmachakra fs-2"></i>
                </div>

                <div class="d-flex gap-3 p-3 align-items-center">
                    <?php if ($photo !== ''): ?>
                        <img
                            src="<?= e($photo) ?>"
                            class="student-photo"
                            alt="Student photo"
                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                        >
                        <div class="student-placeholder" style="display:none">
                            <i class="fa-solid fa-user"></i>
                        </div>
                    <?php else: ?>
                        <div class="student-placeholder">
                            <i class="fa-solid fa-user"></i>
                        </div>
                    <?php endif; ?>

                    <div class="id-student-details">
                        <h5 class="fw-bold mb-2"><?= e($sname) ?></h5>

                        <div class="small mb-1">
                            <strong>ශිෂ්‍ය අංකය:</strong>
                            <?= e($studentNumber) ?>
                        </div>

                        <?php if ($grade !== ''): ?>
                            <div class="small mb-1">
                                <strong>ශ්‍රේණිය:</strong>
                                <?= e($grade) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($className !== ''): ?>
                            <div class="small mb-1">
                                <strong>පන්තිය:</strong>
                                <?= e($className) ?>
                            </div>
                        <?php endif; ?>

                        <div class="small text-muted mt-2">
                            TDMS · <?= date('Y') ?>
                        </div>
                    </div>
                </div>
            </section>

            <h4 class="fw-bold mb-3 print-break">
                සම්පූර්ණ ශිෂ්‍ය විස්තරය
            </h4>

            <section class="profile-sheet">
                <div class="id-top d-flex align-items-center gap-3">
                    <?php if ($photo !== ''): ?>
                        <img
                            src="<?= e($photo) ?>"
                            class="student-photo"
                            style="width:75px;height:88px;flex-basis:75px"
                            alt="Student photo"
                        >
                    <?php else: ?>
                        <div class="student-placeholder"
                             style="width:75px;height:88px;flex-basis:75px">
                            <i class="fa-solid fa-user"></i>
                        </div>
                    <?php endif; ?>

                    <div>
                        <h4 class="mb-1"><?= e($sname) ?></h4>
                        <div class="small">
                            ශිෂ්‍ය අංකය: <?= e($studentNumber) ?>
                        </div>
                        <div class="small">Student Profile Card</div>
                    </div>
                </div>

                <div class="profile-fields">
                    <?php foreach ($s as $key => $value): ?>
                        <?php
                        if (
                            $key === $photoCol ||
                            $value === null ||
                            trim((string) $value) === ''
                        ) {
                            continue;
                        }

                        $label = ucwords(str_replace(['_', '-'], ' ', $key));
                        ?>

                        <div class="profile-field">
                            <small><?= e($label) ?></small>
                            <span><?= e((string) $value) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="mt-4 no-print">
                <a class="btn btn-outline-secondary"
                   href="<?= e($adminUrl) ?>/daham_pasala/students/cards.php">
                    <i class="fa-solid fa-arrow-left me-1"></i>
                    ආපසු
                </a>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>