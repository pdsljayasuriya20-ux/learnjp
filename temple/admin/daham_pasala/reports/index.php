
<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';

Auth::requireAuth();
Auth::requirePermission('view_dayakayan');

$db = Database::getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pageTitle = 'දහම් පාසල් විභාග වාර්තා';

$filters = [
    'year'    => trim((string) ($_GET['year'] ?? '')),
    'class'   => trim((string) ($_GET['class'] ?? '')),
    'subject' => trim((string) ($_GET['subject'] ?? '')),
    'exam'    => trim((string) ($_GET['exam'] ?? '')),
];

$years = [];
$classes = [];
$subjects = [];
$exams = [];
$results = [];

$summary = [
    'result_count'   => 0,
    'student_count'  => 0,
    'average_percent'=> 0,
    'highest_percent'=> 0,
    'pass_count'     => 0,
    'fail_count'     => 0,
];

$errorMessage = '';

/**
 * Escape output safely.
 */
function dpReportEscape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/**
 * Check whether a required table exists.
 */
function dpReportTableExists(PDO $db, string $table): bool
{
    $allowedTables = [
        'daham_pasala_exam_results',
        'daham_pasala_students',
    ];

    if (!in_array($table, $allowedTables, true)) {
        return false;
    }

    $stmt = $db->prepare(
        'SELECT COUNT(*)
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
         AND table_name = ?'
    );

    $stmt->execute([$table]);

    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Get unique dropdown options.
 */
function dpReportOptions(
    PDO $db,
    string $column,
    array $allowedColumns
): array {
    if (!in_array($column, $allowedColumns, true)) {
        return [];
    }

    $sql = "SELECT DISTINCT `$column`
            FROM daham_pasala_exam_results
            WHERE `$column` IS NOT NULL
              AND `$column` <> ''
            ORDER BY `$column` DESC";

    return $db->query($sql)->fetchAll(PDO::FETCH_COLUMN);
}

try {
    if (!dpReportTableExists($db, 'daham_pasala_exam_results')) {
        throw new RuntimeException(
            'Table daham_pasala_exam_results does not exist.'
        );
    }

    if (!dpReportTableExists($db, 'daham_pasala_students')) {
        throw new RuntimeException(
            'Table daham_pasala_students does not exist.'
        );
    }

    // Filter dropdown data.
    $years = dpReportOptions(
        $db,
        'exam_year',
        ['exam_year']
    );

    $classes = dpReportOptions(
        $db,
        'class_name',
        ['class_name']
    );

    $subjects = dpReportOptions(
        $db,
        'subject',
        ['subject']
    );

    $exams = dpReportOptions(
        $db,
        'exam_name',
        ['exam_name']
    );

    // Validate year.
    if (
        $filters['year'] !== ''
        && !ctype_digit($filters['year'])
    ) {
        $filters['year'] = '';
    }

    // Build filters safely using prepared statements.
    $where = [];
    $params = [];

    if ($filters['year'] !== '') {
        $where[] = 'r.exam_year = :year';
        $params['year'] = (int) $filters['year'];
    }

    if ($filters['class'] !== '') {
        $where[] = 'r.class_name = :class_name';
        $params['class_name'] = $filters['class'];
    }

    if ($filters['subject'] !== '') {
        $where[] = 'r.subject = :subject';
        $params['subject'] = $filters['subject'];
    }

    if ($filters['exam'] !== '') {
        $where[] = 'r.exam_name = :exam_name';
        $params['exam_name'] = $filters['exam'];
    }

    $whereSql = $where
        ? ' WHERE ' . implode(' AND ', $where)
        : '';

    // Summary statistics.
    $summarySql = "
        SELECT
            COUNT(*) AS result_count,

            COUNT(DISTINCT r.student_id) AS student_count,

            COALESCE(
                AVG(
                    CASE
                        WHEN r.max_marks > 0
                        THEN (r.marks / r.max_marks) * 100
                        ELSE NULL
                    END
                ), 0
            ) AS average_percent,

            COALESCE(
                MAX(
                    CASE
                        WHEN r.max_marks > 0
                        THEN (r.marks / r.max_marks) * 100
                        ELSE NULL
                    END
                ), 0
            ) AS highest_percent,

            COALESCE(
                SUM(
                    CASE
                        WHEN r.max_marks > 0
                         AND (r.marks / r.max_marks) * 100 >= 40
                        THEN 1
                        ELSE 0
                    END
                ), 0
            ) AS pass_count,

            COALESCE(
                SUM(
                    CASE
                        WHEN r.max_marks <= 0
                          OR (r.marks / r.max_marks) * 100 < 40
                        THEN 1
                        ELSE 0
                    END
                ), 0
            ) AS fail_count

        FROM daham_pasala_exam_results r
        $whereSql
    ";

    $stmt = $db->prepare($summarySql);
    $stmt->execute($params);

    $summary = array_merge(
        $summary,
        $stmt->fetch(PDO::FETCH_ASSOC) ?: []
    );

    // Detailed student results.
    $resultsSql = "
        SELECT
            r.id,
            r.student_id,
            r.exam_name,
            r.exam_year,
            r.grade,
            r.class_name,
            r.subject,
            r.marks,
            r.max_marks,
            s.full_name,

            COALESCE(
                NULLIF(s.grade, ''),
                r.grade
            ) AS display_grade,

            COALESCE(
                NULLIF(s.class_name, ''),
                r.class_name
            ) AS display_class

        FROM daham_pasala_exam_results r

        LEFT JOIN daham_pasala_students s
            ON s.id = r.student_id

        $whereSql

        ORDER BY
            CASE
                WHEN r.max_marks > 0
                THEN r.marks / r.max_marks
                ELSE 0
            END DESC,
            r.marks DESC,
            s.full_name ASC
    ";

    $stmt = $db->prepare($resultsSql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log('Daham Pasala reports: ' . $e->getMessage());

    $errorMessage =
        'වාර්තා ලබාගැනීමට නොහැකි විය. Database tables සහ settings පරීක්ෂා කරන්න.';
}

$totalResults = (int) $summary['result_count'];
$totalPass = (int) $summary['pass_count'];
$totalFail = (int) $summary['fail_count'];

$passRate = $totalResults > 0
    ? ($totalPass / $totalResults) * 100
    : 0;

// Load the shared header ONCE, before the page content.
require_once __DIR__ . '/../../../includes/header.php';
?>

<style>
.daham-report-page {
    background: #f5f7fb;
    min-height: 75vh;
    color: #263449;
}

.daham-report-hero {
    border: 0;
    border-radius: 18px;
    color: #fff;
    background: linear-gradient(
        125deg,
        #202a44 0%,
        #344b73 60%,
        #8b6a24 100%
    );
    overflow: hidden;
}

.daham-report-hero-icon {
    font-size: 4rem;
    opacity: .2;
}

.daham-report-card {
    border: 0;
    border-radius: 15px;
    height: 100%;
    transition: transform .15s ease, box-shadow .15s ease;
}

.daham-report-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 .7rem 1.5rem rgba(0,0,0,.09) !important;
}

.daham-report-icon {
    width: 46px;
    height: 46px;
    border-radius: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.daham-report-panel {
    background: #fff;
    border: 1px solid #e9edf4;
    border-radius: 15px;
    padding: 22px;
    box-shadow: 0 3px 12px rgba(0,0,0,.025);
}

.daham-report-muted {
    color: #778196;
}

.daham-report-page .form-select,
.daham-report-page .form-control {
    min-height: 43px;
    border-color: #dce3ed;
}

.daham-report-page .form-label {
    font-size: .86rem;
    font-weight: 600;
}

.daham-report-page .table thead th {
    background: #f0f4fa;
    color: #47566d;
    font-size: .82rem;
    white-space: nowrap;
    padding: 13px 12px;
}

.daham-report-page .table tbody td {
    padding: 12px;
    vertical-align: middle;
}

.daham-report-page .table-responsive {
    border-radius: 10px;
}

.daham-report-page .daham-section-title {
    font-size: 1.05rem;
    font-weight: 700;
}

.daham-report-page .progress {
    height: 11px;
    border-radius: 20px;
    background: #edf0f5;
}

.daham-report-page .progress-bar {
    border-radius: 20px;
}

.daham-report-page .report-toolbar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.daham-report-page .report-footer {
    color: #778196;
    font-size: .8rem;
}

@media (max-width: 767.98px) {
    .daham-report-page .daham-report-panel {
        padding: 15px;
    }

    .daham-report-page .daham-report-hero-icon {
        font-size: 2.4rem;
    }

    .daham-report-page .report-toolbar > * {
        flex: 1 1 auto;
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
        margin: 0 !important;
        padding: 0 !important;
    }

    .daham-report-page {
        background: #fff !important;
        padding: 0 !important;
    }

    .daham-report-hero {
        background: #fff !important;
        color: #202a44 !important;
        border: 1px solid #ddd;
        padding: 18px !important;
    }

    .daham-report-hero p {
        color: #444 !important;
    }

    .daham-report-panel,
    .daham-report-card {
        box-shadow: none !important;
        break-inside: avoid;
    }

    .daham-report-page .table {
        font-size: 10px;
    }

    .daham-report-page .table thead th,
    .daham-report-page .table tbody td {
        padding: 6px;
    }

    @page {
        size: landscape;
        margin: 12mm;
    }
}
</style>

<div class="admin-shell">

    <!-- Shared sidebar: correct project-root path -->
    <aside class="admin-sidebar no-print">
        <?php require __DIR__ . '/../../../includes/sidebar.php'; ?>
    </aside>

    <main class="admin-main">
        <div class="daham-report-page p-3 p-md-4">

            <!-- Hero -->
            <section class="daham-report-hero shadow-sm p-4 p-lg-5 mb-4">
                <div class="row align-items-center g-3">
                    <div class="col">
                        <div
                            class="small text-uppercase fw-semibold mb-2"
                            style="letter-spacing:.12em;color:#ffe7a0"
                        >
                            Temple Data Management System
                        </div>

                        <h1 class="h3 fw-bold mb-2">
                            <i class="fa-solid fa-chart-column me-2"></i>
                            දහම් පාසල් විභාග වාර්තා
                        </h1>

                        <p class="mb-0 text-white-50">
                            පන්ති, විෂයයන් සහ වර්ෂ අනුව ශිෂ්‍ය ප්‍රතිඵල
                            සහ ඉහළම ලකුණු පරීක්ෂා කරන්න.
                        </p>

                        <div class="report-toolbar mt-4 no-print">
                            <a
                                href="<?= dpReportEscape(rtrim(ADMIN_URL, '/') . '/daham_pasala/index.php') ?>"
                                class="btn btn-warning fw-semibold"
                            >
                                <i class="fa-solid fa-house me-2"></i>
                                Dashboard
                            </a>

                            <button
                                type="button"
                                onclick="window.print()"
                                class="btn btn-outline-light"
                            >
                                <i class="fa-solid fa-print me-2"></i>
                                Print / PDF
                            </button>
                        </div>
                    </div>

                    <div class="col-auto d-none d-md-block">
                        <i class="fa-solid fa-award daham-report-hero-icon"></i>
                    </div>
                </div>
            </section>

            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation me-2"></i>
                    <?= dpReportEscape($errorMessage) ?>
                </div>
            <?php endif; ?>

            <!-- Heading -->
            <div class="d-flex justify-content-between align-items-end
                        flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">වාර්තා සාරාංශය</h2>
                    <p class="daham-report-muted small mb-0">
                        තෝරාගත් filters අනුව ප්‍රතිඵල ගණනය කර ඇත.
                    </p>
                </div>

                <span class="badge text-bg-light border">
                    <i class="fa-regular fa-calendar me-1"></i>
                    <?= date('Y-m-d') ?>
                </span>
            </div>

            <!-- Summary cards -->
            <div class="row g-3 mb-4">

                <div class="col-6 col-xl-3">
                    <div class="card daham-report-card shadow-sm">
                        <div class="card-body p-3 p-lg-4">
                            <div class="d-flex justify-content-between gap-2">
                                <div>
                                    <div class="daham-report-muted small mb-2">
                                        විභාග ප්‍රතිඵල
                                    </div>
                                    <div class="h2 fw-bold mb-0">
                                        <?= number_format($totalResults) ?>
                                    </div>
                                </div>

                                <span class="daham-report-icon bg-primary-subtle text-primary">
                                    <i class="fa-solid fa-file-lines"></i>
                                </span>
                            </div>

                            <div class="small daham-report-muted mt-3">
                                මුළු result records
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="card daham-report-card shadow-sm">
                        <div class="card-body p-3 p-lg-4">
                            <div class="d-flex justify-content-between gap-2">
                                <div>
                                    <div class="daham-report-muted small mb-2">
                                        සිසුන්
                                    </div>
                                    <div class="h2 fw-bold mb-0">
                                        <?= number_format((int) $summary['student_count']) ?>
                                    </div>
                                </div>

                                <span class="daham-report-icon bg-success-subtle text-success">
                                    <i class="fa-solid fa-user-graduate"></i>
                                </span>
                            </div>

                            <div class="small daham-report-muted mt-3">
                                වෙනස් සිසුන් ගණන
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="card daham-report-card shadow-sm">
                        <div class="card-body p-3 p-lg-4">
                            <div class="d-flex justify-content-between gap-2">
                                <div>
                                    <div class="daham-report-muted small mb-2">
                                        සාමාන්‍යය
                                    </div>
                                    <div class="h2 fw-bold mb-0">
                                        <?= number_format((float) $summary['average_percent'], 1) ?>%
                                    </div>
                                </div>

                                <span class="daham-report-icon bg-info-subtle text-info">
                                    <i class="fa-solid fa-chart-line"></i>
                                </span>
                            </div>

                            <div class="small daham-report-muted mt-3">
                                සාමාන්‍ය ප්‍රතිශතය
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="card daham-report-card shadow-sm">
                        <div class="card-body p-3 p-lg-4">
                            <div class="d-flex justify-content-between gap-2">
                                <div>
                                    <div class="daham-report-muted small mb-2">
                                        ඉහළම ලකුණු
                                    </div>
                                    <div class="h2 fw-bold mb-0">
                                        <?= number_format((float) $summary['highest_percent'], 1) ?>%
                                    </div>
                                </div>

                                <span class="daham-report-icon bg-warning-subtle text-warning-emphasis">
                                    <i class="fa-solid fa-trophy"></i>
                                </span>
                            </div>

                            <div class="small daham-report-muted mt-3">
                                ඉහළම ප්‍රතිශතය
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Filters -->
            <section class="daham-report-panel mb-4 no-print">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="daham-report-icon bg-warning-subtle text-warning-emphasis">
                        <i class="fa-solid fa-sliders"></i>
                    </span>

                    <div>
                        <h2 class="daham-section-title mb-1">
                            වාර්තා Filter කරන්න
                        </h2>
                        <div class="small daham-report-muted">
                            පන්තිය, විෂය, විභාගය සහ වර්ෂය තෝරන්න.
                        </div>
                    </div>
                </div>

                <form method="get" action="index.php">
                    <div class="row g-3">

                        <div class="col-12 col-sm-6 col-xl-3">
                            <label class="form-label" for="year">වර්ෂය</label>
                            <select class="form-select" id="year" name="year">
                                <option value="">සියලු වර්ෂ</option>

                                <?php foreach ($years as $year): ?>
                                    <option
                                        value="<?= dpReportEscape($year) ?>"
                                        <?= $filters['year'] === (string) $year ? 'selected' : '' ?>
                                    >
                                        <?= dpReportEscape($year) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-xl-3">
                            <label class="form-label" for="class">පන්තිය</label>
                            <select class="form-select" id="class" name="class">
                                <option value="">සියලු පන්ති</option>

                                <?php foreach ($classes as $class): ?>
                                    <option
                                        value="<?= dpReportEscape($class) ?>"
                                        <?= $filters['class'] === (string) $class ? 'selected' : '' ?>
                                    >
                                        <?= dpReportEscape($class) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-xl-3">
                            <label class="form-label" for="subject">විෂය</label>
                            <select class="form-select" id="subject" name="subject">
                                <option value="">සියලු විෂයයන්</option>

                                <?php foreach ($subjects as $subject): ?>
                                    <option
                                        value="<?= dpReportEscape($subject) ?>"
                                        <?= $filters['subject'] === (string) $subject ? 'selected' : '' ?>
                                    >
                                        <?= dpReportEscape($subject) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-xl-3">
                            <label class="form-label" for="exam">විභාගය</label>
                            <select class="form-select" id="exam" name="exam">
                                <option value="">සියලු විභාග</option>

                                <?php foreach ($exams as $exam): ?>
                                    <option
                                        value="<?= dpReportEscape($exam) ?>"
                                        <?= $filters['exam'] === (string) $exam ? 'selected' : '' ?>
                                    >
                                        <?= dpReportEscape($exam) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-magnifying-glass me-2"></i>
                                වාර්තාව පෙන්වන්න
                            </button>

                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fa-solid fa-rotate-left me-2"></i>
                                Reset
                            </a>
                        </div>

                    </div>
                </form>
            </section>

            <!-- Pass / fail summary -->
            <section class="daham-report-panel mb-4">
                <div class="d-flex justify-content-between align-items-center
                            flex-wrap gap-2 mb-3">
                    <div>
                        <h2 class="daham-section-title mb-1">
                            සමත් / අසමත් සාරාංශය
                        </h2>
                        <div class="small daham-report-muted">
                            සමත් සීමාව: උපරිම ලකුණුවලින් 40%
                        </div>
                    </div>

                    <div class="fw-bold text-success">
                        <?= number_format($passRate, 1) ?>% සමත්
                    </div>
                </div>

                <div class="progress mb-3">
                    <div
                        class="progress-bar bg-success"
                        role="progressbar"
                        style="width: <?= min(100, max(0, $passRate)) ?>%"
                        aria-valuenow="<?= (int) round($passRate) ?>"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    ></div>
                </div>

                <div class="row g-3">
                    <div class="col-6">
                        <div class="rounded-3 border p-3">
                            <div class="small daham-report-muted">
                                සමත් ප්‍රතිඵල
                            </div>
                            <div class="h4 fw-bold text-success mb-0">
                                <?= number_format($totalPass) ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="rounded-3 border p-3">
                            <div class="small daham-report-muted">
                                අසමත් ප්‍රතිඵල
                            </div>
                            <div class="h4 fw-bold text-danger mb-0">
                                <?= number_format($totalFail) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Results and ranking -->
            <section class="daham-report-panel">
                <div class="d-flex justify-content-between align-items-center
                            flex-wrap gap-2 mb-3">
                    <div>
                        <h2 class="daham-section-title mb-1">
                            <i class="fa-solid fa-ranking-star text-warning me-2"></i>
                            ශිෂ්‍ය ප්‍රතිඵල සහ Ranking
                        </h2>

                        <div class="small daham-report-muted">
                            ලකුණු ප්‍රතිශතය වැඩිම අගයේ සිට පහළට.
                        </div>
                    </div>

                    <span class="badge text-bg-primary">
                        <?= number_format(count($results)) ?> records
                    </span>
                </div>

                <?php if ($errorMessage === '' && empty($results)): ?>

                    <div class="text-center py-5">
                        <i class="fa-solid fa-clipboard-list display-4 text-secondary"></i>

                        <h3 class="h5 mt-3">තවම ප්‍රතිඵල නැහැ</h3>

                        <p class="text-secondary mb-0">
                            විභාග ලකුණු ඇතුළත් කළ පසු මෙහි පෙන්වයි.
                        </p>
                    </div>

                <?php elseif (!empty($results)): ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>ශිෂ්‍යයා</th>
                                    <th>ශ්‍රේණිය / පන්තිය</th>
                                    <th>වර්ෂය</th>
                                    <th>විභාගය</th>
                                    <th>විෂය</th>
                                    <th class="text-end">ලකුණු</th>
                                    <th class="text-end">ප්‍රතිශතය</th>
                                    <th>ප්‍රතිඵලය</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($results as $index => $row): ?>
                                    <?php
                                    $marks = (float) $row['marks'];
                                    $maxMarks = (float) $row['max_marks'];

                                    $percentage = $maxMarks > 0
                                        ? ($marks / $maxMarks) * 100
                                        : 0;

                                    $passed = $maxMarks > 0
                                        && $percentage >= 40;
                                    ?>

                                    <tr>
                                        <td>
                                            <?php if ($index === 0): ?>
                                                <span class="badge text-bg-warning">
                                                    <i class="fa-solid fa-trophy me-1"></i>
                                                    1
                                                </span>
                                            <?php else: ?>
                                                <span class="fw-semibold">
                                                    <?= $index + 1 ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <div class="fw-semibold">
                                                <?= dpReportEscape($row['full_name'] ?: 'නම නොමැත') ?>
                                            </div>

                                            <small class="text-secondary">
                                                ID: <?= dpReportEscape($row['student_id']) ?>
                                            </small>
                                        </td>

                                        <td>
                                            <?= dpReportEscape($row['display_grade']) ?>

                                            <div class="small text-secondary">
                                                <?= dpReportEscape($row['display_class']) ?>
                                            </div>
                                        </td>

                                        <td>
                                            <?= dpReportEscape($row['exam_year']) ?>
                                        </td>

                                        <td>
                                            <?= dpReportEscape($row['exam_name']) ?>
                                        </td>

                                        <td>
                                            <?= dpReportEscape($row['subject']) ?>
                                        </td>

                                        <td class="text-end fw-semibold">
                                            <?= number_format($marks, 2) ?>

                                            <span class="text-secondary fw-normal">
                                                / <?= number_format($maxMarks, 2) ?>
                                            </span>
                                        </td>

                                        <td class="text-end fw-bold">
                                            <?= number_format($percentage, 1) ?>%
                                        </td>

                                        <td>
                                            <?php if ($passed): ?>
                                                <span class="badge text-bg-success">
                                                    සමත්
                                                </span>
                                            <?php else: ?>
                                                <span class="badge text-bg-danger">
                                                    අසමත්
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php endif; ?>

                <div class="report-footer mt-4">
                    Daham Pasala — Examination Report

                    <span class="float-end">
                        <?= date('Y-m-d H:i') ?>
                    </span>
                </div>
            </section>

        </div>
    </main>
</div>

<?php
// Load the shared footer ONCE, after the page content.
require_once __DIR__ . '/../../../includes/footer.php';
?>
