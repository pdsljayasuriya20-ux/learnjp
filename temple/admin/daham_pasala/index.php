<?php
declare(strict_types=1);

/**
 * Daham Pasala Management Dashboard
 * Temple Data Management System (TDMS)
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

Auth::requireAuth();
// Follow the existing TDMS permission model. Super Admins pass this check automatically.
Auth::requirePermission('view_dayakayan');

$db = Database::getConnection();

/**
 * Safely check whether a table exists.
 */
function dahamTableExists(PDO $pdo, string $table): bool
{
    $allowedTables = [
        'daham_pasala_students',
        'daham_pasala_teachers',
        'daham_pasala_classes',
        'daham_pasala_attendance',
        'daham_pasala_exams',
    ];

    if (!in_array($table, $allowedTables, true)) {
        return false;
    }

    $sql = "
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = :table_name
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['table_name' => $table]);

    return (int) $stmt->fetchColumn() > 0;
}
/** Count rows safely when optional modules/tables have not been installed yet. */
function dahamCount(PDO $db, string $table, ?string $where = null, array $params = []): int
{
    $allowedTables = [
        'daham_pasala_students',
        'daham_pasala_teachers',
        'daham_pasala_classes',
        'daham_pasala_attendance',
        'daham_pasala_exams',
    ];

    if (!in_array($table, $allowedTables, true) || !dahamTableExists($db, $table)) {
        return 0;
    }

    $sql = 'SELECT COUNT(*) FROM `' . $table . '`';
    if ($where !== null && $where !== '') {
        // $where is only supplied as a fixed SQL fragment below.
        $sql .= ' WHERE ' . $where;
    }

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('Daham Pasala dashboard count failed for ' . $table . ': ' . $e->getMessage());
        return 0;
    }
}

$studentCount = dahamCount($db, 'daham_pasala_students');
$teacherCount = dahamCount($db, 'daham_pasala_teachers');
$classCount = dahamCount($db, 'daham_pasala_classes');
$attendanceCount = dahamCount($db, 'daham_pasala_attendance');
$examCount = dahamCount($db, 'daham_pasala_exams');

$activeTeacherCount = dahamCount($db, 'daham_pasala_teachers', "status = ?", ['active']);
$inactiveTeacherCount = dahamCount($db, 'daham_pasala_teachers', "status = ?", ['inactive']);

// Check optional tables so the dashboard remains usable before all modules are installed.
$availableModules = [
    'students' => dahamTableExists($db, 'daham_pasala_students'),
    'teachers' => dahamTableExists($db, 'daham_pasala_teachers'),
    'classes' => dahamTableExists($db, 'daham_pasala_classes'),
    'attendance' => dahamTableExists($db, 'daham_pasala_attendance'),
    'exams' => dahamTableExists($db, 'daham_pasala_exams'),
];

$pageTitle = 'දහම් පාසල් කළමනාකරණය';
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
    .daham-dashboard { background: #f5f7fb; min-height: 75vh; }
    .daham-hero {
        border: 0; border-radius: 18px; color: #fff;
        background: linear-gradient(125deg, #202a44 0%, #344b73 60%, #8b6a24 100%);
        overflow: hidden;
    }
    .daham-hero-icon { font-size: 4.2rem; opacity: .2; }
    .daham-stat-card { border: 0; border-radius: 15px; height: 100%; transition: transform .15s ease, box-shadow .15s ease; }
    .daham-stat-card:hover { transform: translateY(-3px); box-shadow: 0 .7rem 1.5rem rgba(0,0,0,.09)!important; }
    .daham-stat-icon { width: 48px; height: 48px; border-radius: 13px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem; }
    .daham-module-card { border: 1px solid #e9edf4; border-radius: 14px; height: 100%; transition: border-color .15s ease, transform .15s ease; }
    .daham-module-card:hover { border-color: #d5a52b; transform: translateY(-2px); }
    .daham-module-icon { width: 44px; height: 44px; display: inline-flex; align-items: center; justify-content: center; border-radius: 12px; background: #fff5d6; color: #795b00; font-size: 1.15rem; }
    .daham-muted { color: #778196; }
    @media print { .no-print, .admin-sidebar, nav, footer { display:none!important; } .admin-shell { display:block!important; } .admin-main { width:100%!important; } .daham-dashboard { background:#fff!important; padding:0!important; } }
</style>

<div class="admin-shell">
    <aside class="admin-sidebar no-print">
        <?php require __DIR__ . '/../../includes/sidebar.php'; ?>
    </aside>

    <main class="admin-main">
        <div class="daham-dashboard p-3 p-md-4">
            <section class="daham-hero shadow-sm p-4 p-lg-5 mb-4">
                <div class="row align-items-center g-3">
                    <div class="col">
                        <div class="small text-uppercase fw-semibold mb-2" style="letter-spacing:.12em;color:#ffe7a0;">Temple Data Management System</div>
                        <h1 class="h3 h2-md fw-bold mb-2">දහම් පාසල් කළමනාකරණය</h1>
                        <p class="mb-0 text-white-50">ශිෂ්‍යයන්, ගුරුවරුන් සහ දහම් පාසල් කටයුතු එකම ස්ථානයකින් කළමනාකරණය කරන්න.</p>
                        <div class="d-flex flex-wrap gap-2 mt-4 no-print">
                            <a href="<?= e(ADMIN_URL) ?>/daham_pasala/teachers/index.php" class="btn btn-warning fw-semibold">
                                <i class="fa-solid fa-chalkboard-user me-2"></i>ගුරු නාමාවලිය
                            </a>
                            <a href="<?= e(ADMIN_URL) ?>/daham_pasala/students/cards.php" class="btn btn-outline-light">
                                <i class="fa-solid fa-id-card me-2"></i>ශිෂ්‍ය කාඩ්පත්
                            </a>
                            <button type="button" onclick="window.print()" class="btn btn-outline-light">
                                <i class="fa-solid fa-print me-2"></i>මුද්‍රණය / PDF
                            </button>
                        </div>
                    </div>
                    <div class="col-auto d-none d-md-block"><i class="fa-solid fa-book-open-reader daham-hero-icon"></i></div>
                </div>
            </section>

            <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">සාරාංශය</h2>
                    <p class="daham-muted small mb-0">දැනට database එකේ ඇති දත්ත අනුව</p>
                </div>
                <span class="badge text-bg-light border"><i class="fa-regular fa-calendar me-1"></i><?= e(date('Y-m-d')) ?></span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="card daham-stat-card shadow-sm"><div class="card-body p-3 p-lg-4">
                        <div class="d-flex justify-content-between align-items-start gap-2"><div><div class="daham-muted small mb-2">මුළු ශිෂ්‍යයන්</div><div class="h2 fw-bold mb-0"><?= number_format($studentCount) ?></div></div><span class="daham-stat-icon bg-primary-subtle text-primary"><i class="fa-solid fa-user-graduate"></i></span></div>
                        <div class="small daham-muted mt-3">ලියාපදිංචි ශිෂ්‍යයන්</div>
                    </div></div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card daham-stat-card shadow-sm"><div class="card-body p-3 p-lg-4">
                        <div class="d-flex justify-content-between align-items-start gap-2"><div><div class="daham-muted small mb-2">මුළු ගුරුවරු</div><div class="h2 fw-bold mb-0"><?= number_format($teacherCount) ?></div></div><span class="daham-stat-icon bg-success-subtle text-success"><i class="fa-solid fa-chalkboard-user"></i></span></div>
                        <div class="small daham-muted mt-3">සක්‍රීය: <?= number_format($activeTeacherCount) ?> · අක්‍රීය: <?= number_format($inactiveTeacherCount) ?></div>
                    </div></div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card daham-stat-card shadow-sm"><div class="card-body p-3 p-lg-4">
                        <div class="d-flex justify-content-between align-items-start gap-2"><div><div class="daham-muted small mb-2">ශ්‍රේණි / පන්ති</div><div class="h2 fw-bold mb-0"><?= number_format($classCount) ?></div></div><span class="daham-stat-icon bg-warning-subtle text-warning-emphasis"><i class="fa-solid fa-school"></i></span></div>
                        <div class="small daham-muted mt-3">පන්ති කළමනාකරණය</div>
                    </div></div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card daham-stat-card shadow-sm"><div class="card-body p-3 p-lg-4">
                        <div class="d-flex justify-content-between align-items-start gap-2"><div><div class="daham-muted small mb-2">පැමිණීමේ වාර්තා</div><div class="h2 fw-bold mb-0"><?= number_format($attendanceCount) ?></div></div><span class="daham-stat-icon bg-info-subtle text-info"><i class="fa-solid fa-calendar-check"></i></span></div>
                        <div class="small daham-muted mt-3">දත්ත තිබේ නම් පෙන්වයි</div>
                    </div></div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
                <div><h2 class="h5 fw-bold mb-1">කළමනාකරණ මොඩියුල</h2><p class="daham-muted small mb-0">අවශ්‍ය කොටස තෝරන්න</p></div>
            </div>

            <div class="row g-3">
                <div class="col-sm-6 col-xl-4">
                    <div class="card daham-module-card shadow-sm"><div class="card-body p-4 d-flex flex-column">
                        <span class="daham-module-icon mb-3"><i class="fa-solid fa-user-graduate"></i></span><h3 class="h6 fw-bold">ශිෂ්‍ය කළමනාකරණය</h3><p class="small daham-muted">ශිෂ්‍ය ලැයිස්තුව සහ ලියාපදිංචි තොරතුරු.</p><div class="mt-auto"><a href="<?= e(ADMIN_URL) ?>/daham_pasala/students/index.php" class="btn btn-sm btn-outline-primary">විවෘත කරන්න <i class="fa-solid fa-arrow-right ms-1"></i></a></div>
                    </div></div>
                </div>
                <div class="col-sm-6 col-xl-4">
                    <div class="card daham-module-card shadow-sm"><div class="card-body p-4 d-flex flex-column">
                        <span class="daham-module-icon mb-3"><i class="fa-solid fa-id-card"></i></span><h3 class="h6 fw-bold">ශිෂ්‍ය ID / Profile කාඩ්පත්</h3><p class="small daham-muted">ශිෂ්‍ය කාඩ්පත් බලන්න, මුද්‍රණය කරන්න හෝ PDF ලෙස සුරකින්න.</p><div class="mt-auto"><a href="<?= e(ADMIN_URL) ?>/daham_pasala/students/cards.php" class="btn btn-sm btn-outline-primary">කාඩ්පත් විවෘත කරන්න <i class="fa-solid fa-arrow-right ms-1"></i></a></div>
                    </div></div>
                </div>
                <div class="col-sm-6 col-xl-4">
                    <div class="card daham-module-card shadow-sm"><div class="card-body p-4 d-flex flex-column">
                        <span class="daham-module-icon mb-3"><i class="fa-solid fa-chalkboard-user"></i></span><h3 class="h6 fw-bold">ගුරු කළමනාකරණය</h3><p class="small daham-muted">ගුරුවරුන්ගේ විස්තර සහ සේවා තොරතුරු.</p><div class="mt-auto"><a href="<?= e(ADMIN_URL) ?>/daham_pasala/teachers/index.php" class="btn btn-sm btn-outline-primary">ගුරුවරු බලන්න <i class="fa-solid fa-arrow-right ms-1"></i></a></div>
                    </div></div>
                </div>
                <div class="col-sm-6 col-xl-4">
                    <div class="card daham-module-card shadow-sm"><div class="card-body p-4 d-flex flex-column">
                        <span class="daham-module-icon mb-3"><i class="fa-solid fa-school"></i></span><h3 class="h6 fw-bold">ශ්‍රේණි සහ පන්ති</h3><p class="small daham-muted">පන්ති සහ ශ්‍රේණි තොරතුරු කළමනාකරණය.</p><div class="mt-auto"><a href="<?= e(ADMIN_URL) ?>/daham_pasala/classes/index.php" class="btn btn-sm btn-outline-primary">පන්ති වෙත <i class="fa-solid fa-arrow-right ms-1"></i></a></div>
                    </div></div>
                </div>
                <div class="col-sm-6 col-xl-4">
                    <div class="card daham-module-card shadow-sm"><div class="card-body p-4 d-flex flex-column">
                        <span class="daham-module-icon mb-3"><i class="fa-solid fa-calendar-check"></i></span><h3 class="h6 fw-bold">ශිෂ්‍ය පැමිණීම</h3><p class="small daham-muted">දෛනික පැමිණීම් ලකුණු කිරීම සහ වාර්තා.</p><div class="mt-auto"><a href="<?= e(ADMIN_URL) ?>/daham_pasala/attendance/index.php" class="btn btn-sm btn-outline-primary">පැමිණීම වෙත <i class="fa-solid fa-arrow-right ms-1"></i></a></div>
                    </div></div>
                </div>
                <div class="col-sm-6 col-xl-4">
                    <div class="card daham-module-card shadow-sm"><div class="card-body p-4 d-flex flex-column">
                        <span class="daham-module-icon mb-3"><i class="fa-solid fa-file-pen"></i></span><h3 class="h6 fw-bold">විභාග සහ ලකුණු</h3><p class="small daham-muted">විභාග ප්‍රතිඵල සහ ශිෂ්‍ය ප්‍රගතිය.</p><div class="mt-auto"><a href="<?= e(ADMIN_URL) ?>/daham_pasala/exams/index.php" class="btn btn-sm btn-outline-primary">විභාග වෙත <i class="fa-solid fa-arrow-right ms-1"></i></a></div>
                    </div></div>
                </div>
                <div class="col-sm-6 col-xl-4">
                    <div class="card daham-module-card shadow-sm"><div class="card-body p-4 d-flex flex-column">
                        <span class="daham-module-icon mb-3"><i class="fa-solid fa-chart-column"></i></span><h3 class="h6 fw-bold">දහම් පාසල් වාර්තා</h3><p class="small daham-muted">දහම් පාසල් දත්තවල සාරාංශ සහ වාර්තා.</p><div class="mt-auto"><a href="<?= e(ADMIN_URL) ?>/daham_pasala/reports/index.php" class="btn btn-sm btn-outline-primary">වාර්තා වෙත <i class="fa-solid fa-arrow-right ms-1"></i></a></div>
                    </div></div>
                </div>
            </div>

            <?php if (!$availableModules['classes'] || !$availableModules['attendance'] || !$availableModules['exams']): ?>
                <div class="alert alert-info mt-4 mb-0">
                    <i class="fa-solid fa-circle-info me-2"></i>
                    <strong>සටහන:</strong> ශ්‍රේණි/පන්ති, පැමිණීම හෝ විභාග වගු database එකේ තවම නොමැති නම්, ඒවායේ ගණන් 0 ලෙස පෙන්වයි. මේ Dashboard එක ඒ වගු නොමැති වුවත් error නොදී විවෘත වීමට සකසා ඇත.
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
