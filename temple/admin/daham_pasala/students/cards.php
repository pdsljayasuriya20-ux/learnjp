<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';

Auth::requirePermission('view_dayakayan');

$db = Database::getConnection();
$table = 'daham_pasala_students';

/* -----------------------------------------
   Detect available database columns
----------------------------------------- */
$columns = $db->query("SHOW COLUMNS FROM `$table`")
              ->fetchAll(PDO::FETCH_ASSOC);

$colNames = array_column($columns, 'Field');

$pick = static function (array $choices) use ($colNames): ?string {
    foreach ($choices as $column) {
        if (in_array($column, $colNames, true)) {
            return $column;
        }
    }

    return null;
};

$idCol     = $pick(['id', 'student_id']);
$nameCol   = $pick(['full_name', 'student_name', 'name', 'name_with_initials', 'child_name']);
$photoCol  = $pick(['photo_path', 'profile_photo', 'student_photo', 'photo', 'image', 'image_path']);
$gradeCol  = $pick(['grade', 'class_grade', 'grade_name', 'level']);
$classCol  = $pick(['class_name', 'class']);
$numberCol = $pick(['student_number', 'admission_no', 'registration_no', 'index_no']);
$yearCol   = $pick(['academic_year', 'school_year', 'year']);

if (!$idCol || !$nameCol) {
    http_response_code(500);
    exit('Student table is missing a recognizable ID or name column.');
}

/* -----------------------------------------
   Application URLs
----------------------------------------- */
$baseUrl = defined('BASE_URL')
    ? rtrim((string) BASE_URL, '/')
    : '/temple';

$adminUrl = defined('ADMIN_URL')
    ? rtrim((string) ADMIN_URL, '/')
    : $baseUrl . '/admin';

/* -----------------------------------------
   Safely resolve student photo URL
----------------------------------------- */
$photoUrl = static function (?string $path) use ($baseUrl): string {
    $path = trim((string) $path);

    if ($path === '') {
        return '';
    }

    // Allow HTTP and HTTPS image URLs.
    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }

    // Reject other URI schemes.
    if (preg_match('~^[a-z][a-z0-9+.-]*:~i', $path)) {
        return '';
    }

    $path = str_replace('\\', '/', $path);

    // Absolute local web path.
    if (strpos($path, '/') === 0) {
        return $path;
    }

    // Relative path inside the application.
    return $baseUrl . '/' . ltrim($path, '/');
};

/* -----------------------------------------
   Search students
----------------------------------------- */
$search = trim((string) ($_GET['search'] ?? ''));

$sql = "SELECT * FROM `$table`";
$params = [];

if ($search !== '') {
    $conditions = [
        "CAST(`$idCol` AS CHAR) LIKE :search_id",
        "`$nameCol` LIKE :search_name"
    ];

    $params['search_id'] = '%' . $search . '%';
    $params['search_name'] = '%' . $search . '%';

    if ($numberCol) {
        $conditions[] = "CAST(`$numberCol` AS CHAR) LIKE :search_number";
        $params['search_number'] = '%' . $search . '%';
    }

    if ($gradeCol) {
        $conditions[] = "`$gradeCol` LIKE :search_grade";
        $params['search_grade'] = '%' . $search . '%';
    }

    if ($classCol) {
        $conditions[] = "`$classCol` LIKE :search_class";
        $params['search_class'] = '%' . $search . '%';
    }

    $sql .= ' WHERE ' . implode(' OR ', $conditions);
}

$sql .= " ORDER BY `$nameCol` ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../../../includes/header.php';
?>

<style>
.cards-page {
    background: #f4f6fb;
    min-height: 75vh;
}

.student-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 18px;
}

.student-id-card,
.profile-card {
    background: #fff;
    border: 1px solid #dce3ed;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 14px rgba(23, 43, 77, .07);
    break-inside: avoid;
    page-break-inside: avoid;
}

.id-head,
.profile-top {
    background: linear-gradient(110deg, #123d69, #1976a5);
    color: #fff;
}

.id-head {
    padding: 13px 15px;
}

.id-body {
    display: flex;
    align-items: flex-start;
    gap: 13px;
    padding: 15px;
}

.student-photo,
.photo-placeholder {
    width: 88px;
    height: 105px;
    flex: 0 0 88px;
    border-radius: 8px;
}

.student-photo {
    object-fit: cover;
    border: 1px solid #dee2e6;
    background: #f1f3f5;
}

.photo-placeholder {
    background: #edf1f6;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #748094;
    font-size: 27px;
}

.student-name {
    font-weight: 700;
    overflow-wrap: anywhere;
}

.profile-top {
    padding: 15px;
    display: flex;
    align-items: center;
    gap: 13px;
}

.profile-details {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px 14px;
    padding: 16px;
}

.detail {
    border-bottom: 1px solid #edf0f4;
    padding: 7px 0;
    min-width: 0;
    overflow-wrap: anywhere;
}

.detail small {
    display: block;
    color: #6c757d;
    margin-bottom: 3px;
}

.card-footer-actions {
    padding: 0 15px 15px;
}

@media (max-width: 576px) {
    .student-cards-grid {
        grid-template-columns: 1fr;
    }

    .profile-details {
        grid-template-columns: 1fr;
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

    .cards-page {
        padding: 0 !important;
        background: #fff !important;
    }

    .student-cards-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8mm;
    }

    .student-id-card,
    .profile-card {
        box-shadow: none;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .profile-card {
        margin-bottom: 5mm;
    }

    .card-grid-title {
        display: none !important;
    }

    @page {
        size: A4;
        margin: 12mm;
    }
}
</style>

<div class="admin-shell">

    <aside class="admin-sidebar no-print">
        <?php require __DIR__ . '/../../../includes/sidebar.php'; ?>
    </aside>

    <main class="admin-main">
        <div class="cards-page p-3 p-md-4">

            <!-- Page heading -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 no-print">
                <div>
                    <h3 class="fw-bold mb-1">
                        <i class="fa-solid fa-id-card text-primary me-2"></i>
                        දහම් පාසල් ශිෂ්‍ය කාඩ්පත්
                    </h3>

                    <div class="text-muted small">
                        Student ID Cards &amp; Profile Cards
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a
                        class="btn btn-outline-secondary"
                        href="<?= e($adminUrl) ?>/daham_pasala/students/cards.php"
                    >
                        <i class="fa-solid fa-rotate-left me-1"></i>
                        Reset
                    </a>

                    <button
                        type="button"
                        class="btn btn-primary"
                        onclick="window.print()"
                    >
                        <i class="fa-solid fa-print me-1"></i>
                        සියල්ල Print / PDF
                    </button>
                </div>
            </div>

            <?php if (!$photoCol): ?>
                <div class="alert alert-warning no-print">
                    <strong>Profile photo column එක හමු නොවීය.</strong>
                    <br>
                    `daham_pasala_students` table එකේ `photo_path`
                    වැනි column එකක් තිබේදැයි පරීක්ෂා කරන්න.
                </div>
            <?php endif; ?>

            <!-- Search -->
            <form class="card border-0 shadow-sm mb-4 no-print" method="get">
                <div class="card-body row g-2 align-items-end">

                    <div class="col-md-9">
                        <label for="studentSearch" class="form-label">
                            ශිෂ්‍යයා සොයන්න
                        </label>

                        <input
                            id="studentSearch"
                            type="search"
                            class="form-control"
                            name="search"
                            value="<?= e($search) ?>"
                            placeholder="නම, ශිෂ්‍ය අංකය, ශ්‍රේණිය..."
                        >
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-dark w-100">
                            <i class="fa-solid fa-magnifying-glass me-1"></i>
                            සොයන්න
                        </button>
                    </div>

                </div>
            </form>

            <!-- Student ID cards -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-grid-title mb-0">
                    ශිෂ්‍ය ID කාඩ්පත්
                </h5>

                <span class="badge text-bg-primary no-print">
                    ශිෂ්‍යයන්: <?= count($students) ?>
                </span>
            </div>

            <?php if (!$students): ?>

                <div class="alert alert-info">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    ශිෂ්‍යයන් හමු නොවීය. Search එක වෙනස් කර බලන්න
                    හෝ ශිෂ්‍ය දත්ත ඇතුළත් කරන්න.
                </div>

            <?php else: ?>

                <div class="student-cards-grid mb-5">

                    <?php foreach ($students as $s): ?>

                        <?php
                        $sid = (string) ($s[$idCol] ?? '');
                        $sname = (string) ($s[$nameCol] ?? '');

                        $photo = $photoCol
                            ? $photoUrl((string) ($s[$photoCol] ?? ''))
                            : '';

                        $grade = $gradeCol
                            ? trim((string) ($s[$gradeCol] ?? ''))
                            : '';

                        $className = $classCol
                            ? trim((string) ($s[$classCol] ?? ''))
                            : '';

                        $studentNumber = $numberCol
                            ? trim((string) ($s[$numberCol] ?? ''))
                            : '';

                        if ($studentNumber === '') {
                            $studentNumber = $sid;
                        }

                        $year = $yearCol
                            ? trim((string) ($s[$yearCol] ?? ''))
                            : '';

                        if ($year === '') {
                            $year = date('Y');
                        }

                        $profileLink = $adminUrl
                            . '/daham_pasala/students/card.php?id='
                            . rawurlencode($sid);
                        ?>

                        <article class="student-id-card">

                            <div class="id-head d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold">දහම් පාසල</div>
                                    <small>STUDENT ID CARD</small>
                                </div>

                                <i class="fa-solid fa-dharmachakra fs-3"></i>
                            </div>

                            <div class="id-body">

                                <?php if ($photo !== ''): ?>

                                    <img
                                        class="student-photo"
                                        src="<?= e($photo) ?>"
                                        alt="Student photo"
                                        loading="lazy"
                                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                                    >

                                    <div
                                        class="photo-placeholder"
                                        style="display:none"
                                    >
                                        <i class="fa-solid fa-user"></i>
                                    </div>

                                <?php else: ?>

                                    <div class="photo-placeholder">
                                        <i class="fa-solid fa-user"></i>
                                    </div>

                                <?php endif; ?>

                                <div class="flex-grow-1">

                                    <div class="student-name fs-6 mb-2">
                                        <?= e($sname) ?>
                                    </div>

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

                                    <div class="small text-muted">
                                        අධ්‍යයන වර්ෂය: <?= e($year) ?>
                                    </div>

                                </div>
                            </div>

                            <div class="px-3 pb-3 d-flex justify-content-between align-items-center">
                                <small class="text-muted">TDMS</small>

                                <a
                                    class="small no-print"
                                    href="<?= e($profileLink) ?>"
                                >
                                    විස්තර / Profile
                                    <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

                <!-- Full profile cards -->
                <h5 class="mb-3 card-grid-title">
                    සම්පූර්ණ ශිෂ්‍ය Profile කාඩ්පත්
                </h5>

                <div class="row g-3">

                    <?php foreach ($students as $s): ?>

                        <?php
                        $sid = (string) ($s[$idCol] ?? '');
                        $sname = (string) ($s[$nameCol] ?? '');

                        $photo = $photoCol
                            ? $photoUrl((string) ($s[$photoCol] ?? ''))
                            : '';

                        $studentNumber = $numberCol
                            ? trim((string) ($s[$numberCol] ?? ''))
                            : '';

                        if ($studentNumber === '') {
                            $studentNumber = $sid;
                        }

                        $profileLink = $adminUrl
                            . '/daham_pasala/students/card.php?id='
                            . rawurlencode($sid);
                        ?>

                        <div class="col-12 col-lg-6">

                            <article class="profile-card">

                                <div class="profile-top">

                                    <?php if ($photo !== ''): ?>

                                        <img
                                            src="<?= e($photo) ?>"
                                            alt="Student photo"
                                            class="student-photo"
                                            style="width:75px;height:88px;flex-basis:75px"
                                            loading="lazy"
                                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                                        >

                                        <div
                                            class="photo-placeholder"
                                            style="display:none;width:75px;height:88px;flex-basis:75px"
                                        >
                                            <i class="fa-solid fa-user"></i>
                                        </div>

                                    <?php else: ?>

                                        <div
                                            class="photo-placeholder"
                                            style="width:75px;height:88px;flex-basis:75px"
                                        >
                                            <i class="fa-solid fa-user"></i>
                                        </div>

                                    <?php endif; ?>

                                    <div class="flex-grow-1">
                                        <h5 class="mb-1">
                                            <?= e($sname) ?>
                                        </h5>

                                        <div class="small">
                                            ශිෂ්‍ය අංකය:
                                            <?= e($studentNumber) ?>
                                        </div>

                                        <div class="small">
                                            Student Profile
                                        </div>
                                    </div>

                                </div>

                                <div class="profile-details">

                                    <?php foreach ($s as $key => $value): ?>

                                        <?php
                                        if (
                                            $key === $photoCol ||
                                            $key === $nameCol ||
                                            $key === $idCol ||
                                            $value === null ||
                                            trim((string) $value) === ''
                                        ) {
                                            continue;
                                        }

                                        $label = ucwords(
                                            str_replace(['_', '-'], ' ', $key)
                                        );
                                        ?>

                                        <div class="detail">
                                            <small><?= e($label) ?></small>
                                            <span><?= e((string) $value) ?></span>
                                        </div>

                                    <?php endforeach; ?>

                                </div>

                                <div class="card-footer-actions no-print">
                                    <a
                                        class="btn btn-sm btn-outline-primary"
                                        href="<?= e($profileLink) ?>"
                                    >
                                        <i class="fa-solid fa-user me-1"></i>
                                        Individual Profile / Print
                                    </a>
                                </div>

                            </article>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>