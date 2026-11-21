<?php
declare(strict_types=1);

/**
 * Daham Pasala Student Management
 * Temple Data Management System (TDMS)
 * Features: Add Student, Profile Photo, Search, Filter, Pagination
 */

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

/* ---------------------------------------------------------
   Helpers
--------------------------------------------------------- */

function dpStudentEscape(mixed $value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function dpStudentColumn(array $columns, array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        foreach ($columns as $column) {
            if (strcasecmp($column, $candidate) === 0) {
                return $column;
            }
        }
    }

    return null;
}

function dpStudentQuote(?string $column): string
{
    if ($column === null) {
        return '';
    }

    return '`' . str_replace('`', '``', $column) . '`';
}

function dpStudentPhotoUrl(mixed $photo, string $baseUrl): ?string
{
    $photo = trim((string)($photo ?? ''));

    if ($photo === '' || str_contains($photo, '..')) {
        return null;
    }

    // Only use local paths.
    if (preg_match('#^(https?:)?//#i', $photo)) {
        return null;
    }

    $photo = ltrim(str_replace('\\', '/', $photo), '/');

    if (str_starts_with($photo, 'uploads/')) {
        return rtrim($baseUrl, '/') . '/' . $photo;
    }

    // Support legacy filenames.
    if (basename($photo) === $photo) {
        return rtrim($baseUrl, '/')
            . '/uploads/daham_pasala/students/'
            . rawurlencode($photo);
    }

    return null;
}

function dpStudentRedirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/* ---------------------------------------------------------
   Configuration
--------------------------------------------------------- */

$baseUrl = defined('BASE_URL')
    ? rtrim((string)BASE_URL, '/')
    : '/temple';

$adminUrl = defined('ADMIN_URL')
    ? rtrim((string)ADMIN_URL, '/')
    : $baseUrl . '/admin';

$studentCardsUrl = $adminUrl . '/daham_pasala/students/cards.php';

$pageTitle = 'දහම් පාසල් ශිෂ්‍ය කළමනාකරණය';

$rootPath = dirname(__DIR__, 3);

$uploadDirectory = $rootPath
    . '/uploads/daham_pasala/students';

$uploadRelativePath = 'uploads/daham_pasala/students';

$perPage = 15;
$message = '';
$error = '';

/* ---------------------------------------------------------
   Verify the student table and discover its columns
--------------------------------------------------------- */

try {
    $tableStmt = $db->prepare(
        'SELECT COUNT(*)
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_name = ?'
    );

    $tableStmt->execute(['daham_pasala_students']);

    if ((int)$tableStmt->fetchColumn() === 0) {
        throw new RuntimeException(
            'daham_pasala_students table එක database එකේ නොමැත.'
        );
    }

    $columnStmt = $db->query(
        'SHOW COLUMNS FROM `daham_pasala_students`'
    );

    $columns = [];

    foreach ($columnStmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $columns[] = $column['Field'];
    }
} catch (Throwable $exception) {
    error_log('Student table error: ' . $exception->getMessage());

    http_response_code(500);

    exit(
        'Student table එක කියවීමට නොහැක. '
        . 'Database table structure සහ error log පරීක්ෂා කරන්න.'
    );
}

$idColumn = dpStudentColumn($columns, ['id', 'student_id']);
$nameColumn = dpStudentColumn(
    $columns,
    ['full_name', 'student_name', 'name']
);
$dobColumn = dpStudentColumn($columns, ['dob', 'date_of_birth']);
$genderColumn = dpStudentColumn($columns, ['gender']);
$gradeColumn = dpStudentColumn($columns, ['grade', 'grade_name']);
$classColumn = dpStudentColumn($columns, ['class_name', 'class']);
$parentColumn = dpStudentColumn(
    $columns,
    ['parent_dayakaya_id', 'dayakaya_id']
);
$statusColumn = dpStudentColumn(
    $columns,
    ['status', 'student_status']
);
$notesColumn = dpStudentColumn($columns, ['notes', 'remarks']);
$photoColumn = dpStudentColumn(
    $columns,
    ['photo_path', 'photo', 'profile_photo', 'image_path']
);

if ($idColumn === null || $nameColumn === null) {
    error_log('Student table columns: ' . implode(', ', $columns));

    http_response_code(500);

    exit(
        'Student ID හෝ student name column එක හඳුනාගත නොහැක.'
    );
}

/* ---------------------------------------------------------
   CSRF token
--------------------------------------------------------- */

$csrfToken = generateCSRFToken();

/* ---------------------------------------------------------
   Parent / Dayakaya dropdown
--------------------------------------------------------- */

$parents = [];

if ($parentColumn !== null) {
    try {
        $parentStmt = $db->query(
            'SELECT id, full_name, member_id
             FROM dayakayan
             ORDER BY full_name ASC'
        );

        $parents = $parentStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $exception) {
        error_log('Parent dropdown error: ' . $exception->getMessage());
        $parents = [];
    }
}

/* ---------------------------------------------------------
   Add New Student
--------------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'add_student') {

    $uploadedFile = null;

    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new RuntimeException(
                'ආරක්ෂිත token එක වලංගු නැත. නැවත උත්සාහ කරන්න.'
            );
        }

        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $dob = trim((string)($_POST['dob'] ?? ''));
        $gender = trim((string)($_POST['gender'] ?? ''));
        $grade = trim((string)($_POST['grade'] ?? ''));
        $className = trim((string)($_POST['class_name'] ?? ''));
        $parentId = trim((string)($_POST['parent_dayakaya_id'] ?? ''));
        $status = trim((string)($_POST['status'] ?? 'active'));
        $notes = trim((string)($_POST['notes'] ?? ''));

        if ($fullName === '') {
            throw new RuntimeException(
                'ශිෂ්‍යයාගේ සම්පූර්ණ නම ඇතුළත් කරන්න.'
            );
        }

        if (mb_strlen($fullName) > 255) {
            throw new RuntimeException(
                'ශිෂ්‍ය නම අක්ෂර 255 ට වඩා වැඩි විය නොහැක.'
            );
        }

        if ($dob !== '') {
            $date = DateTime::createFromFormat('!Y-m-d', $dob);

            if (!$date || $date->format('Y-m-d') !== $dob) {
                throw new RuntimeException(
                    'නිවැරදි උපන් දිනයක් තෝරන්න.'
                );
            }
        }

        if ($gender !== ''
            && !in_array($gender, ['Male', 'Female', 'Other'], true)) {
            throw new RuntimeException(
                'කරුණාකර නිවැරදි ලිංගිකත්වය තෝරන්න.'
            );
        }

        if (!in_array($status, ['active', 'inactive', 'pending'], true)) {
            throw new RuntimeException(
                'තෝරාගත් student status එක වලංගු නැත.'
            );
        }

        if ($parentId !== '') {
            if (!ctype_digit($parentId) || (int)$parentId <= 0) {
                throw new RuntimeException(
                    'කරුණාකර නිවැරදි දෙමාපිය / දායක වාර්තාව තෝරන්න.'
                );
            }

            $parentCheck = $db->prepare(
                'SELECT id FROM dayakayan WHERE id = ? LIMIT 1'
            );
            $parentCheck->execute([(int)$parentId]);

            if (!$parentCheck->fetchColumn()) {
                throw new RuntimeException(
                    'තෝරාගත් දායක වාර්තාව හමු නොවීය.'
                );
            }
        }

        /* Upload profile image if selected */

        $photoPath = '';

        if (isset($_FILES['profile_image'])
            && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {

            $file = $_FILES['profile_image'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException(
                    'Profile image upload කිරීම අසාර්ථක විය.'
                );
            }

            $maxSize = 5 * 1024 * 1024;

            if ((int)$file['size'] > $maxSize) {
                throw new RuntimeException(
                    'Profile image එක 5 MB ට වඩා කුඩා විය යුතුයි.'
                );
            }

            if (!is_uploaded_file($file['tmp_name'])) {
                throw new RuntimeException(
                    'වලංගු image upload එකක් නොවේ.'
                );
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
            ];

            if (!isset($allowedTypes[$mimeType])) {
                throw new RuntimeException(
                    'JPG, PNG හෝ WEBP image එකක් පමණක් upload කරන්න.'
                );
            }

            if (!is_dir($uploadDirectory)) {
                if (!mkdir($uploadDirectory, 0755, true)
                    && !is_dir($uploadDirectory)) {
                    throw new RuntimeException(
                        'Image upload folder එක සෑදීමට නොහැක.'
                    );
                }
            }

            if (!is_writable($uploadDirectory)) {
                throw new RuntimeException(
                    'Image upload folder එකට write permission නැත.'
                );
            }

            $imageInfo = getimagesize($file['tmp_name']);

            if ($imageInfo === false) {
                throw new RuntimeException(
                    'Upload කළ file එක වලංගු image එකක් නොවේ.'
                );
            }

            $fileName = bin2hex(random_bytes(16))
                . '.'
                . $allowedTypes[$mimeType];

            $destination = $uploadDirectory . '/' . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new RuntimeException(
                    'Profile image එක save කිරීමට නොහැක.'
                );
            }

            $uploadedFile = $destination;
            $photoPath = $uploadRelativePath . '/' . $fileName;
        }

        /* Build INSERT using the actual table columns */

        $insertData = [
            $nameColumn => $fullName,
        ];

        if ($dobColumn !== null) {
            $insertData[$dobColumn] = $dob !== '' ? $dob : null;
        }

        if ($genderColumn !== null) {
            $insertData[$genderColumn] = $gender !== '' ? $gender : null;
        }

        if ($gradeColumn !== null) {
            $insertData[$gradeColumn] = $grade !== '' ? $grade : null;
        }

        if ($classColumn !== null) {
            $insertData[$classColumn] = $className !== '' ? $className : null;
        }

        if ($parentColumn !== null) {
            $insertData[$parentColumn] = $parentId !== ''
                ? (int)$parentId
                : null;
        }

        if ($statusColumn !== null) {
            $insertData[$statusColumn] = $status;
        }

        if ($notesColumn !== null) {
            $insertData[$notesColumn] = $notes !== '' ? $notes : null;
        }

        if ($photoColumn !== null) {
            $insertData[$photoColumn] = $photoPath !== ''
                ? $photoPath
                : null;
        }

        $insertColumns = [];
        $placeholders = [];
        $values = [];

        foreach ($insertData as $column => $value) {
            $insertColumns[] = dpStudentQuote($column);
            $placeholders[] = '?';
            $values[] = $value;
        }

        $insertSql = 'INSERT INTO `daham_pasala_students` ('
            . implode(', ', $insertColumns)
            . ') VALUES ('
            . implode(', ', $placeholders)
            . ')';

        $db->beginTransaction();

        $insertStmt = $db->prepare($insertSql);
        $insertStmt->execute($values);

        $newStudentId = (int)$db->lastInsertId();

        $db->commit();

        dpStudentRedirect(
            strtok($_SERVER['REQUEST_URI'] ?? '', '?')
            . '?added=1&id=' . $newStudentId
        );

    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        if ($uploadedFile !== null && is_file($uploadedFile)) {
            @unlink($uploadedFile);
        }

        if ($exception instanceof RuntimeException) {
            $error = $exception->getMessage();
        } else {
            error_log(
                'Add student error: ' . $exception->getMessage()
            );

            $error = 'ශිෂ්‍ය තොරතුරු save කිරීමට නොහැකි විය. '
                . 'Database columns සහ server error log පරීක්ෂා කරන්න.';
        }
    }
}

if (isset($_GET['added']) && $_GET['added'] === '1') {
    $message = 'නව ශිෂ්‍යයා සාර්ථකව ඇතුළත් කරන ලදී.';
}

/* ---------------------------------------------------------
   Search and filters
--------------------------------------------------------- */

$search = trim((string)($_GET['search'] ?? ''));
$gradeFilter = trim((string)($_GET['grade'] ?? ''));

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);

if ($page === false || $page === null || $page < 1) {
    $page = 1;
}

$whereParts = [];
$params = [];

if ($search !== '') {
    $searchColumns = array_filter([
        $nameColumn,
        $classColumn,
    ]);

    $searchParts = [];

    foreach (array_unique($searchColumns) as $column) {
        $searchParts[] = dpStudentQuote($column) . ' LIKE ?';
        $params[] = '%' . $search . '%';
    }

    if ($idColumn !== null && ctype_digit($search)) {
        $searchParts[] = dpStudentQuote($idColumn) . ' = ?';
        $params[] = (int)$search;
    }

    if ($searchParts) {
        $whereParts[] = '(' . implode(' OR ', $searchParts) . ')';
    }
}

if ($gradeFilter !== '' && $gradeColumn !== null) {
    $whereParts[] = dpStudentQuote($gradeColumn) . ' = ?';
    $params[] = $gradeFilter;
}

$whereSql = $whereParts
    ? ' WHERE ' . implode(' AND ', $whereParts)
    : '';

/* ---------------------------------------------------------
   Fetch students, totals and grade options
--------------------------------------------------------- */

try {
    $countStmt = $db->prepare(
        'SELECT COUNT(*) FROM `daham_pasala_students`' . $whereSql
    );
    $countStmt->execute($params);

    $totalStudents = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalStudents / $perPage));

    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $selectParts = [
        dpStudentQuote($idColumn) . ' AS student_id',
        dpStudentQuote($nameColumn) . ' AS student_name',
    ];

    $optionalColumns = [
        'dob_value' => $dobColumn,
        'gender_value' => $genderColumn,
        'grade_value' => $gradeColumn,
        'class_value' => $classColumn,
        'parent_value' => $parentColumn,
        'status_value' => $statusColumn,
        'notes_value' => $notesColumn,
        'photo_value' => $photoColumn,
    ];

    foreach ($optionalColumns as $alias => $column) {
        $selectParts[] = $column !== null
            ? dpStudentQuote($column) . ' AS `' . $alias . '`'
            : 'NULL AS `' . $alias . '`';
    }

    $sql = 'SELECT ' . implode(', ', $selectParts)
        . ' FROM `daham_pasala_students`'
        . $whereSql
        . ' ORDER BY ' . dpStudentQuote($nameColumn) . ' ASC'
        . ' LIMIT ' . (int)$perPage
        . ' OFFSET ' . (int)$offset;

    $studentStmt = $db->prepare($sql);
    $studentStmt->execute($params);

    $students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

    $grades = [];

    if ($gradeColumn !== null) {
        $gradeStmt = $db->query(
            'SELECT DISTINCT ' . dpStudentQuote($gradeColumn)
            . ' FROM `daham_pasala_students`'
            . ' WHERE ' . dpStudentQuote($gradeColumn) . ' IS NOT NULL'
            . ' AND ' . dpStudentQuote($gradeColumn) . " <> ''"
            . ' ORDER BY ' . dpStudentQuote($gradeColumn) . ' ASC'
        );

        $grades = $gradeStmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (Throwable $exception) {
    error_log('Student listing error: ' . $exception->getMessage());

    http_response_code(500);

    exit(
        'Student list එක ලබාගැනීමට නොහැක. '
        . 'Database structure සහ server error log පරීක්ෂා කරන්න.'
    );
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<style>
.dp-students {
    background: #f5f7fb;
    min-height: 75vh;
    padding: 24px;
    color: #202a44;
}
.dp-students * { box-sizing: border-box; }
.dp-students .dp-hero {
    color: #fff;
    border-radius: 18px;
    background: linear-gradient(125deg,#202a44,#344b73 60%,#8b6a24);
}
.dp-students .dp-panel {
    background: #fff;
    border: 1px solid #e9edf4;
    border-radius: 15px;
    box-shadow: 0 .25rem 1rem rgba(25,40,70,.045);
}
.dp-students .dp-muted { color: #778196; }
.dp-students .dp-filter {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.dp-students .form-control,
.dp-students .form-select {
    min-height: 43px;
    border-radius: 9px;
}
.dp-students .table-wrap { width: 100%; overflow-x: auto; }
.dp-students .table { margin-bottom: 0; vertical-align: middle; }
.dp-students .table thead th {
    background: #f8f9fc;
    color: #566078;
    font-size: .8rem;
    white-space: nowrap;
}
.dp-students .table td,
.dp-students .table th { padding: 12px; }
.dp-students .student-photo {
    width: 48px;
    height: 48px;
    object-fit: cover;
    border-radius: 50%;
    border: 2px solid #edf0f5;
}
.dp-students .photo-placeholder {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #e8edfa;
    color: #344b73;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.dp-students .pagination-area {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-top: 18px;
}
.dp-students .pagination-links {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}
.dp-students .pagination-links .active {
    background: #202a44;
    border-color: #202a44;
    color: #fff;
}
.dp-students .required-mark { color: #dc3545; }
@media(max-width:767.98px) {
    .dp-students { padding: 14px; }
    .dp-students .dp-hero { border-radius: 14px; }
}
@media print {
    .no-print, .admin-sidebar, nav, footer { display: none !important; }
    .admin-shell { display: block !important; }
    .dp-students { padding: 0 !important; background: #fff !important; }
}
</style>

<div class="admin-shell">

    <aside class="admin-sidebar no-print">
        <?php require __DIR__ . '/../../../includes/sidebar.php'; ?>
    </aside>

    <main class="admin-main">
        <div class="dp-students">

            <section class="dp-hero shadow-sm p-4 p-lg-5 mb-4">
                <div class="d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="small text-uppercase fw-semibold mb-2"
                             style="letter-spacing:.1em;color:#ffe7a0">
                            Temple Data Management System
                        </div>

                        <h1 class="h3 fw-bold mb-2">
                            <i class="fa-solid fa-user-graduate me-2"></i>
                            දහම් පාසල් ශිෂ්‍ය කළමනාකරණය
                        </h1>

                        <p class="mb-0 text-white-50">
                            නව ශිෂ්‍යයන් ඇතුළත් කරන්න, ඡායාරූප එකතු කරන්න
                            සහ ශිෂ්‍ය තොරතුරු කළමනාකරණය කරන්න.
                        </p>
                    </div>

                    <i class="fa-solid fa-users d-none d-md-block"
                       style="font-size:3.5rem;opacity:.25"></i>
                </div>
            </section>

            <?php if ($message !== ''): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fa-solid fa-circle-check me-2"></i>
                    <?= dpStudentEscape($message) ?>
                    <button type="button" class="btn-close"
                            data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    <?= dpStudentEscape($error) ?>
                    <button type="button" class="btn-close"
                            data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- ADD NEW STUDENT -->
            <section class="dp-panel mb-4" id="add-student">
                <div class="card-body p-3 p-lg-4">

                    <div class="d-flex align-items-center gap-2 mb-4">
                        <span class="rounded-3 bg-primary-subtle text-primary p-3">
                            <i class="fa-solid fa-user-plus fa-lg"></i>
                        </span>
                        <div>
                            <h2 class="h5 fw-bold mb-1">නව ශිෂ්‍යයෙකු ඇතුළත් කරන්න</h2>
                            <p class="dp-muted small mb-0">
                                Student details සහ profile image එක එකතු කරන්න.
                            </p>
                        </div>
                    </div>

                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token"
                               value="<?= dpStudentEscape($csrfToken) ?>">
                        <input type="hidden" name="action" value="add_student">

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    සම්පූර්ණ නම
                                    <span class="required-mark">*</span>
                                </label>
                                <input type="text" name="full_name"
                                       class="form-control" maxlength="255"
                                       placeholder="ශිෂ්‍යයාගේ සම්පූර්ණ නම"
                                       required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">උපන් දිනය</label>
                                <input type="date" name="dob" class="form-control">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">ලිංගිකත්වය</label>
                                <select name="gender" class="form-select">
                                    <option value="">තෝරන්න</option>
                                    <option value="Male">පිරිමි</option>
                                    <option value="Female">ගැහැණු</option>
                                    <option value="Other">වෙනත්</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">ශ්‍රේණිය</label>
                                <select name="grade" class="form-select">
                                    <option value="">ශ්‍රේණිය තෝරන්න</option>
                                    <?php
                                    $gradeOptions = [
                                        'Grade 1', 'Grade 2', 'Grade 3',
                                        'Grade 4', 'Grade 5', 'Grade 6',
                                        'Grade 7', 'Grade 8', 'Grade 9',
                                        'Grade 10', 'Grade 11'
                                    ];
                                    foreach ($gradeOptions as $gradeOption):
                                    ?>
                                        <option value="<?= dpStudentEscape($gradeOption) ?>">
                                            <?= dpStudentEscape($gradeOption) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">පන්තිය</label>
                                <input type="text" name="class_name"
                                       class="form-control"
                                       placeholder="උදා: Sunday Class">
                            </div>

                            <?php if ($parentColumn !== null): ?>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        දෙමාපියා / දායකයා
                                    </label>
                                    <select name="parent_dayakaya_id" class="form-select">
                                        <option value="">සම්බන්ධ නොකරන්න</option>
                                        <?php foreach ($parents as $parent): ?>
                                            <option value="<?= (int)$parent['id'] ?>">
                                                <?= dpStudentEscape($parent['full_name'] ?? '') ?>
                                                <?php if (!empty($parent['member_id'])): ?>
                                                    (<?= dpStudentEscape($parent['member_id']) ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">තත්ත්වය</label>
                                <select name="status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="pending">Pending</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Profile Image
                                </label>
                                <input type="file" name="profile_image"
                                       class="form-control"
                                       accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                       id="profileImageInput">
                                <div class="form-text">
                                    JPG, PNG හෝ WEBP. උපරිම 5 MB.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Image Preview</label>
                                <div class="d-flex align-items-center gap-3">
                                    <img id="profileImagePreview"
                                         src=""
                                         alt="Profile preview"
                                         style="display:none;width:80px;height:80px;
                                                object-fit:cover;border-radius:50%;
                                                border:2px solid #e9edf4">
                                    <span id="profileImagePlaceholder"
                                          class="photo-placeholder">
                                        <i class="fa-solid fa-user fa-lg"></i>
                                    </span>
                                    <span class="small dp-muted">
                                        තෝරාගත් ඡායාරූපය මෙහි පෙන්වයි.
                                    </span>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">අමතර සටහන්</label>
                                <textarea name="notes" class="form-control"
                                          rows="3"
                                          placeholder="අවශ්‍ය අමතර තොරතුරු..."></textarea>
                            </div>

                            <div class="col-12 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fa-solid fa-floppy-disk me-2"></i>
                                    Save Student
                                </button>

                                <button type="reset"
                                        class="btn btn-outline-secondary"
                                        id="resetStudentForm">
                                    <i class="fa-solid fa-rotate-left me-2"></i>
                                    Reset
                                </button>
                            </div>

                        </div>
                    </form>
                </div>
            </section>

            <!-- SUMMARY -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <div class="dp-panel p-4 h-100">
                        <div class="dp-muted small mb-2">සොයාගත් ශිෂ්‍යයන්</div>
                        <div class="h2 fw-bold mb-0">
                            <?= number_format($totalStudents) ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="dp-panel p-4 h-100">
                        <div class="dp-muted small mb-2">වත්මන් පිටුව</div>
                        <div class="h2 fw-bold mb-0">
                            <?= number_format($page) ?>
                            <span class="fs-6 dp-muted">
                                / <?= number_format($totalPages) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="dp-panel p-4 h-100">
                        <div class="dp-muted small mb-2">එක් පිටුවකට</div>
                        <div class="h2 fw-bold mb-0"><?= $perPage ?></div>
                    </div>
                </div>
            </div>

            <!-- SEARCH -->
            <section class="dp-panel mb-4 no-print">
                <div class="card-body p-3 p-lg-4">
                    <h2 class="h6 fw-bold mb-3">
                        <i class="fa-solid fa-magnifying-glass me-2 text-primary"></i>
                        ශිෂ්‍යයන් සොයන්න
                    </h2>

                    <form method="get" class="dp-filter">
                        <div class="flex-grow-1">
                            <input type="search" name="search"
                                   class="form-control"
                                   value="<?= dpStudentEscape($search) ?>"
                                   placeholder="නම, පන්තිය හෝ ID අංකය">
                        </div>

                        <?php if ($gradeColumn !== null): ?>
                            <div style="min-width:190px">
                                <select name="grade" class="form-select">
                                    <option value="">සියලුම ශ්‍රේණි</option>
                                    <?php foreach ($grades as $gradeOption): ?>
                                        <option
                                            value="<?= dpStudentEscape($gradeOption) ?>"
                                            <?= (string)$gradeOption === $gradeFilter
                                                ? 'selected' : '' ?>>
                                            <?= dpStudentEscape($gradeOption) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fa-solid fa-search me-1"></i> Search
                        </button>

                        <a href="<?= dpStudentEscape(strtok($_SERVER['REQUEST_URI'] ?? '', '?') ?: '') ?>"
                           class="btn btn-outline-secondary">
                            Reset
                        </a>
                    </form>
                </div>
            </section>

            <!-- STUDENT LIST -->
            <section class="dp-panel">
                <div class="card-body p-3 p-lg-4">

                    <div class="d-flex justify-content-between align-items-center
                                flex-wrap gap-2 mb-3">
                        <h2 class="h5 fw-bold mb-0">
                            <i class="fa-solid fa-users me-2 text-primary"></i>
                            ශිෂ්‍ය නාමාවලිය
                        </h2>

                        <button type="button"
                                onclick="window.print()"
                                class="btn btn-outline-secondary btn-sm no-print">
                            <i class="fa-solid fa-print me-1"></i> Print / PDF
                        </button>
                    </div>

                    <div class="table-wrap">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ඡායාරූපය</th>
                                    <th>ශිෂ්‍යයා</th>

                                    <?php if ($dobColumn !== null): ?>
                                        <th>උපන් දිනය</th>
                                    <?php endif; ?>

                                    <?php if ($gradeColumn !== null): ?>
                                        <th>ශ්‍රේණිය</th>
                                    <?php endif; ?>

                                    <?php if ($classColumn !== null): ?>
                                        <th>පන්තිය</th>
                                    <?php endif; ?>

                                    <?php if ($statusColumn !== null): ?>
                                        <th>තත්ත්වය</th>
                                    <?php endif; ?>

                                    <th class="no-print">කාඩ්පත</th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php if (!$students): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="fa-solid fa-folder-open fa-2x mb-3 d-block text-muted"></i>
                                        <strong>ශිෂ්‍යයන් හමු නොවීය</strong>
                                        <div class="small dp-muted mt-1">
                                            නව ශිෂ්‍යයෙකු එකතු කරන්න හෝ සෙවුම වෙනස් කරන්න.
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                    <?php
                                    $photoUrl = dpStudentPhotoUrl(
                                        $student['photo_value'] ?? null,
                                        $baseUrl
                                    );

                                    $studentId = (string)$student['student_id'];

                                    $cardLink = $studentCardsUrl
                                        . '?id=' . rawurlencode($studentId);
                                    ?>
                                    <tr>
                                        <td>
                                            <?php if ($photoUrl !== null): ?>
                                                <img src="<?= dpStudentEscape($photoUrl) ?>"
                                                     class="student-photo"
                                                     alt="Student profile"
                                                     loading="lazy"
                                                     onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
                                                <span class="photo-placeholder"
                                                      style="display:none">
                                                    <i class="fa-solid fa-user"></i>
                                                </span>
                                            <?php else: ?>
                                                <span class="photo-placeholder">
                                                    <i class="fa-solid fa-user"></i>
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <div class="fw-bold">
                                                <?= dpStudentEscape($student['student_name']) ?>
                                            </div>
                                            <div class="small dp-muted">
                                                ID: <?= dpStudentEscape($studentId) ?>
                                            </div>
                                        </td>

                                        <?php if ($dobColumn !== null): ?>
                                            <td>
                                                <?= dpStudentEscape($student['dob_value'] ?: '—') ?>
                                            </td>
                                        <?php endif; ?>

                                        <?php if ($gradeColumn !== null): ?>
                                            <td>
                                                <?= dpStudentEscape($student['grade_value'] ?: '—') ?>
                                            </td>
                                        <?php endif; ?>

                                        <?php if ($classColumn !== null): ?>
                                            <td>
                                                <?= dpStudentEscape($student['class_value'] ?: '—') ?>
                                            </td>
                                        <?php endif; ?>

                                        <?php if ($statusColumn !== null): ?>
                                            <td>
                                                <?php
                                                $statusValue = (string)($student['status_value'] ?? '—');
                                                $statusClass = $statusValue === 'active'
                                                    ? 'success'
                                                    : ($statusValue === 'inactive'
                                                        ? 'secondary' : 'warning');
                                                ?>
                                                <span class="badge text-bg-<?= $statusClass ?>">
                                                    <?= dpStudentEscape(ucfirst($statusValue)) ?>
                                                </span>
                                            </td>
                                        <?php endif; ?>

                                        <td class="no-print">
                                            <a href="<?= dpStudentEscape($cardLink) ?>"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fa-solid fa-id-card me-1"></i>
                                                කාඩ්පත
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION -->
                    <div class="pagination-area">
                        <div class="small dp-muted">
                            පෙන්වන්නේ
                            <?= $totalStudents > 0
                                ? number_format($offset + 1) : '0' ?>
                            –
                            <?= number_format(min($offset + $perPage, $totalStudents)) ?>
                            / <?= number_format($totalStudents) ?>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <nav class="pagination-links no-print"
                                 aria-label="Student pages">
                                <?php
                                $pageQuery = $_GET;
                                $pageQuery['page'] = max(1, $page - 1);
                                ?>
                                <a class="btn btn-sm btn-outline-secondary"
                                   href="?<?= dpStudentEscape(http_build_query($pageQuery)) ?>">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </a>

                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);

                                for ($pageNumber = $startPage;
                                     $pageNumber <= $endPage;
                                     $pageNumber++):
                                    $pageQuery['page'] = $pageNumber;
                                ?>
                                    <a class="btn btn-sm <?= $pageNumber === $page
                                        ? 'active' : 'btn-outline-secondary' ?>"
                                       href="?<?= dpStudentEscape(http_build_query($pageQuery)) ?>">
                                        <?= $pageNumber ?>
                                    </a>
                                <?php endfor; ?>

                                <?php
                                $pageQuery['page'] = min($totalPages, $page + 1);
                                ?>
                                <a class="btn btn-sm btn-outline-secondary"
                                   href="?<?= dpStudentEscape(http_build_query($pageQuery)) ?>">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            </nav>
                        <?php endif; ?>
                    </div>

                </div>
            </section>

        </div>
    </main>
</div>

<script>
(function () {
    const input = document.getElementById('profileImageInput');
    const preview = document.getElementById('profileImagePreview');
    const placeholder = document.getElementById('profileImagePlaceholder');
    const form = document.querySelector('form[enctype="multipart/form-data"]');

    if (!input || !preview || !placeholder) {
        return;
    }

    let previewUrl = null;

    input.addEventListener('change', function () {
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }

        const file = input.files && input.files[0];

        if (!file) {
            preview.style.display = 'none';
            preview.removeAttribute('src');
            placeholder.style.display = 'inline-flex';
            return;
        }

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            alert('JPG, PNG හෝ WEBP image එකක් තෝරන්න.');
            input.value = '';
            preview.style.display = 'none';
            placeholder.style.display = 'inline-flex';
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            alert('Image එක 5 MB ට වඩා කුඩා විය යුතුයි.');
            input.value = '';
            preview.style.display = 'none';
            placeholder.style.display = 'inline-flex';
            return;
        }

        previewUrl = URL.createObjectURL(file);
        preview.src = previewUrl;
        preview.style.display = 'inline-block';
        placeholder.style.display = 'none';
    });

    if (form) {
        form.addEventListener('reset', function () {
            setTimeout(function () {
                if (previewUrl) {
                    URL.revokeObjectURL(previewUrl);
                    previewUrl = null;
                }

                preview.style.display = 'none';
                preview.removeAttribute('src');
                placeholder.style.display = 'inline-flex';
            }, 0);
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>