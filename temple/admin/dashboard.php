<?php
declare(strict_types=1);

/**
 * Super Admin & Admin Dashboard Controller
 * Temple Dayaka Management System - Version 1.0 Blueprint
 */

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireAuth();

try {
    $db = Database::getConnection();

    // 1. System Summary Metrics
    $totalDayakayan = (int) $db->query("SELECT COUNT(*) FROM dayakayan")->fetchColumn();
    $approvedCount  = (int) $db->query("SELECT COUNT(*) FROM dayakayan WHERE status = 'approved'")->fetchColumn();
    $pendingCount   = (int) $db->query("SELECT COUNT(*) FROM dayakayan WHERE status = 'pending'")->fetchColumn();
    $rejectedCount  = (int) $db->query("SELECT COUNT(*) FROM dayakayan WHERE status = 'rejected'")->fetchColumn();

    // 2. Peruwa Breakdown Statistics
    $peruwaStats = $db->query("
        SELECT p.name, COUNT(d.id) AS total
        FROM peruwa p
        LEFT JOIN families f ON p.id = f.peruwa_id
        LEFT JOIN dayakayan d ON f.id = d.family_id AND d.status = 'approved'
        GROUP BY p.id, p.name
        ORDER BY p.name
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Pending Change Requests Count
    $pendingChangesCount = 0;
    $changeRequestsTable = $db->query("SHOW TABLES LIKE 'change_requests'")->fetchColumn();
    if ($changeRequestsTable) {
        $pendingChangesCount = (int) $db->query(
            "SELECT COUNT(*) FROM change_requests WHERE status = 'pending'"
        )->fetchColumn();
    }

    // 4. Safe Monthly Donation Total Calculation
    $thisMonthDonations = 0.00;
    $donationsTable = $db->query("SHOW TABLES LIKE 'monthly_donations'")->fetchColumn();
    if ($donationsTable) {
        $donationSum = $db->query("
            SELECT COALESCE(SUM(amount), 0)
            FROM monthly_donations
            WHERE MONTH(payment_date) = MONTH(CURRENT_DATE())
              AND YEAR(payment_date) = YEAR(CURRENT_DATE())
              AND status = 'paid'
        ")->fetchColumn();
        $thisMonthDonations = (float) ($donationSum ?? 0.00);
    }
} catch (Throwable $e) {
    error_log('Admin dashboard error: ' . $e->getMessage());
    http_response_code(500);
    die("<div class='container my-5 alert alert-danger'><strong>Dashboard System Error:</strong> Dashboard data could not be loaded. Please check the database tables and configuration.</div>");
}

require_once __DIR__ . '/../includes/header.php';

?>

<style>
    .admin-shell { display: flex; align-items: stretch; min-height: calc(100vh - 100px); }
    .admin-sidebar { flex: 0 0 260px; width: 260px; background: #212529; color: #fff; }
    .admin-sidebar .sidebar { position: sticky; top: 0; min-height: calc(100vh - 100px); padding: 0 12px 16px; }
    .admin-sidebar .nav { padding: 12px 0; gap: 4px; }
    .admin-sidebar .nav-link { color: #dee2e6; border-radius: 8px; padding: 10px 12px; }
    .admin-sidebar .nav-link:hover, .admin-sidebar .nav-link.active { color: #212529; background: #ffc107; }
    .admin-main { flex: 1 1 auto; min-width: 0; }
    @media (max-width: 767.98px) {
        .admin-shell { display: block; }
        .admin-sidebar { width: 100%; }
        .admin-sidebar .sidebar { min-height: auto; position: static; }
        .admin-sidebar .nav { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .admin-sidebar .nav-link { font-size: .875rem; }
    }
</style>

<div class="admin-shell">
    <aside class="admin-sidebar">
        <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    </aside>
    <main class="admin-main">
<div class="container-fluid my-4 px-3 px-md-4">
    <!-- Title Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-gauge-high text-warning me-2"></i>පරිපාලන පුවරුව (Admin Dashboard)
            </h3>
            <p class="text-muted small mb-0"><?= e(APP_NAME) ?> - සාරාංශ දත්ත සහ පාලන තොරතුරු</p>
        </div>
        <div class="mt-3 mt-md-0">
            <span class="badge bg-warning text-dark px-3 py-2 fw-bold fs-6 shadow-sm">
                <i class="fa-solid fa-user-shield me-1"></i>
                Role: <?= e(strtoupper((string) (Auth::user()['role'] ?? 'ADMIN'))) ?>
            </span>
        </div>
    </div>

    <!-- Core Metrics Overview Widgets -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm bg-white border-start border-4 border-primary h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold d-block text-uppercase">මුළු දායකයින්</span>
                    <h2 class="fw-bold text-primary my-1"><?= number_format($totalDayakayan) ?></h2>
                    <small class="text-muted">ලියාපදිංචි මුළු සංඛ්‍යාව</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm bg-white border-start border-4 border-success h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold d-block text-uppercase">අනුමත දායකයින්</span>
                    <h2 class="fw-bold text-success my-1"><?= number_format($approvedCount) ?></h2>
                    <small class="text-muted">Public Directory එකේ පෙනෙන</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm bg-white border-start border-4 border-warning h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold d-block text-uppercase">අනුමැතියට ඇති ලියාපදිංචි</span>
                    <h2 class="fw-bold text-warning my-1"><?= number_format($pendingCount) ?></h2>
                    <small class="text-muted">Pending Review Registrations</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm bg-white border-start border-4 border-info h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold d-block text-uppercase">මෙම මාසයේ දායක මුදල්</span>
                    <h2 class="fw-bold text-info my-1">¥<?= number_format($thisMonthDonations, 2) ?></h2>
                    <small class="text-muted">Paid Monthly Donations</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Peruwa Breakdown & Quick System Actions -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fa-solid fa-layer-group text-warning me-2"></i>පේරු මට්ටමේ දායක සංඛ්‍යාලේඛන (Peruwa Breakdown)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php if (empty($peruwaStats)): ?>
                            <div class="col-12"><p class="text-muted mb-0">පේරු දත්ත තවම ඇතුළත් කර නැත.</p></div>
                        <?php else: ?>
                            <?php foreach ($peruwaStats as $stat): ?>
                                <div class="col-6 col-md-3">
                                    <div class="p-3 bg-light rounded text-center border h-100">
                                        <small class="text-muted fw-bold d-block mb-1"><?= e((string) $stat['name']) ?></small>
                                        <span class="fs-3 fw-bold text-dark"><?= number_format((int) $stat['total']) ?></span>
                                        <small class="d-block text-muted">අනුමත දායකයින්</small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links Panel -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fa-solid fa-bolt text-warning me-2"></i>ඉක්මන් ක්‍රියාකාරකම් (Quick Actions)
                    </h6>
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="<?= e(PUBLIC_URL) ?>/register.php" class="btn btn-outline-warning text-dark fw-bold text-start p-2">
                        <i class="fa-solid fa-user-plus me-2 text-warning"></i> නව දායකයෙකු ඇතුළත් කිරීම
                    </a>
                    <a href="<?= e(ADMIN_URL) ?>/approvals/registrations.php" class="btn btn-outline-primary fw-bold text-start p-2">
                        <i class="fa-solid fa-user-check me-2"></i> ලියාපදිංචි අනුමත කිරීම් (<?= number_format($pendingCount) ?>)
                    </a>
                    <a href="<?= e(ADMIN_URL) ?>/approvals/change_requests.php" class="btn btn-outline-info text-dark fw-bold text-start p-2">
                        <i class="fa-solid fa-code-compare me-2"></i> දත්ත සංස්කරණ අනුමත කිරීම් (<?= number_format($pendingChangesCount) ?>)
                    </a>
                    <?php if (Auth::isSuperAdmin()): ?>
                        <a href="<?= e(ADMIN_URL) ?>/users/index.php" class="btn btn-outline-dark fw-bold text-start p-2">
                            <i class="fa-solid fa-users-gear me-2 text-warning"></i> Admin පරිශීලකයින් කළමනාකරණය
                        </a>
                    <?php endif; ?>
                    <a href="<?= e(ADMIN_URL) ?>/reports/dayaka_reports.php" class="btn btn-outline-secondary fw-bold text-start p-2">
                        <i class="fa-solid fa-chart-column me-2"></i> දායක වාර්තා
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
