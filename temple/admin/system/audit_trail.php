<?php
/**
 * Temple Dayaka Management System
 * පද්ධති Audit Trail — Super Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

Auth::requireAuth();

if (!Auth::isSuperAdmin()) {
    http_response_code(403);
    exit("<!doctype html><html lang='si'><meta charset='utf-8'><title>ප්‍රවේශය ප්‍රතික්ෂේපයි</title><body style='font-family:Arial,sans-serif;text-align:center;padding:60px'><h2>මෙම කොටස පරීක්ෂා කිරීමට Super Admin හට පමණක් අවසර ඇත.</h2></body></html>");
}

$db = Database::getConnection();

$search = trim((string)($_GET['q'] ?? ''));
$actionFilter = trim((string)($_GET['action'] ?? ''));
$limit = 100;

try {
    // Read only the fields needed by the viewer.
    $sql = "
        SELECT a.id, a.user_id, a.action, a.table_name, a.record_id,
               a.ip_address, a.created_at, u.username
        FROM audit_logs a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE 1 = 1
    ";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (
            CAST(a.id AS CHAR) LIKE ?
            OR COALESCE(u.username, '') LIKE ?
            OR COALESCE(a.action, '') LIKE ?
            OR COALESCE(a.table_name, '') LIKE ?
            OR CAST(COALESCE(a.record_id, '') AS CHAR) LIKE ?
            OR COALESCE(a.ip_address, '') LIKE ?
        )";
        $needle = '%' . $search . '%';
        array_push($params, $needle, $needle, $needle, $needle, $needle, $needle);
    }

    if ($actionFilter !== '') {
        $sql .= " AND a.action = ?";
        $params[] = $actionFilter;
    }

    $sql .= " ORDER BY a.id DESC LIMIT " . (int)$limit;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $auditLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalLogs = (int)$db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
    $actionStmt = $db->query("
        SELECT DISTINCT action
        FROM audit_logs
        WHERE action IS NOT NULL AND action <> ''
        ORDER BY action ASC
    ");
    $availableActions = $actionStmt->fetchAll(PDO::FETCH_COLUMN);
    $todayStmt = $db->query("SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at) = CURRENT_DATE()");
    $todayLogs = (int)$todayStmt->fetchColumn();
} catch (Throwable $exception) {
    error_log('Audit trail viewer error: ' . $exception->getMessage());
    $auditLogs = [];
    $totalLogs = 0;
    $todayLogs = 0;
    $availableActions = [];
    $loadError = 'Audit logs ලබාගැනීමේ දෝෂයක් ඇති විය. audit_logs වගුවේ schema එක පරීක්ෂා කරන්න.';
}

require_once __DIR__ . '/../../includes/header.php';
?>
<style>
.audit-shell{display:flex;align-items:stretch;min-height:calc(100vh - 100px);background:#f5f7fb}
.audit-sidebar{flex:0 0 260px;width:260px;background:#212529;color:#fff}
.audit-sidebar .sidebar{position:sticky;top:0;min-height:calc(100vh - 100px);padding:0 12px 16px}
.audit-sidebar .nav{padding:12px 0;gap:4px}
.audit-sidebar .nav-link{color:#dee2e6;border-radius:8px;padding:10px 12px}
.audit-sidebar .nav-link:hover,.audit-sidebar .nav-link.active{color:#212529;background:#ffc107}
.audit-main{flex:1 1 auto;min-width:0;padding-bottom:24px}
.audit-hero{position:relative;overflow:hidden;border-radius:18px;padding:26px;background:linear-gradient(120deg,#212529,#343a40 65%,#72551a);color:#fff}
.audit-hero:after{content:"";position:absolute;width:220px;height:220px;border-radius:50%;right:-60px;top:-95px;background:rgba(255,193,7,.15)}
.audit-hero>*{position:relative;z-index:1}
.audit-card{border:0;border-radius:14px;box-shadow:0 4px 18px rgba(15,23,42,.06)}
.audit-table th{white-space:nowrap;font-size:.8rem}
.audit-table td{font-size:.86rem;vertical-align:middle}
.audit-table code{font-size:.8rem}
.audit-ip{white-space:nowrap}
.audit-empty{padding:48px 16px;text-align:center;color:#6c757d}
@media(max-width:767.98px){
 .audit-shell{display:block}
 .audit-sidebar{width:100%;flex:0 0 auto}
 .audit-sidebar .sidebar{min-height:auto;position:static}
 .audit-sidebar .nav{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));padding:12px 0;gap:4px}
 .audit-sidebar .nav-link{font-size:.875rem}
 .audit-hero{padding:20px}
}
@media print{
 .audit-sidebar,.audit-actions,.audit-filters,header,footer,.navbar,.btn{display:none!important}
 .audit-shell{display:block;min-height:0;background:#fff}
 .audit-main{width:100%;padding:0!important}
 .container-fluid{padding:0!important;margin:0!important}
 .audit-card{box-shadow:none!important;border:1px solid #ddd!important}
 body{background:#fff!important}
}
</style>

<div class="audit-shell">
    <aside class="audit-sidebar">
        <?php require __DIR__ . '/../../includes/sidebar.php'; ?>
    </aside>

    <main class="audit-main">
        <div class="container-fluid my-4 px-3 px-md-4">
            <section class="audit-hero mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <div class="small text-warning fw-bold mb-2"><i class="fa-solid fa-shield-halved me-1"></i> SUPER ADMIN CONTROL</div>
                        <h2 class="fw-bold mb-2"><i class="fa-solid fa-list-check text-warning me-2"></i>පද්ධති ක්‍රියාකාරකම් වාර්තාව</h2>
                        <p class="mb-0 text-white-50">පද්ධතියේ සිදු වූ ක්‍රියාකාරකම්, පරිශීලකයන් සහ වේලාවන් පරීක්ෂා කරන්න.</p>
                    </div>
                    <div class="audit-actions d-flex flex-wrap gap-2">
                        <a class="btn btn-outline-light" href="<?= e(ADMIN_URL) ?>/users/index.php"><i class="fa-solid fa-arrow-left me-1"></i> පරිශීලකයින්</a>
                        <button type="button" class="btn btn-warning fw-bold" onclick="window.print()"><i class="fa-solid fa-print me-1"></i> මුද්‍රණය</button>
                    </div>
                </div>
            </section>

            <?php if (isset($loadError)): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= e($loadError) ?></div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-4">
                    <div class="card audit-card h-100"><div class="card-body">
                        <div class="text-muted small">මුළු ක්‍රියාකාරකම්</div>
                        <div class="fs-2 fw-bold text-primary"><?= number_format($totalLogs) ?></div>
                    </div></div>
                </div>
                <div class="col-6 col-xl-4">
                    <div class="card audit-card h-100"><div class="card-body">
                        <div class="text-muted small">අද සිදු වූ ක්‍රියාකාරකම්</div>
                        <div class="fs-2 fw-bold text-success"><?= number_format($todayLogs) ?></div>
                    </div></div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="card audit-card h-100"><div class="card-body">
                        <div class="text-muted small">දැනට පෙන්වන වාර්තා</div>
                        <div class="fs-2 fw-bold text-warning"><?= number_format(count($auditLogs)) ?></div>
                        <div class="small text-muted">උපරිම වාර්තා 100ක් පෙන්වයි</div>
                    </div></div>
                </div>
            </div>

            <section class="card audit-card mb-4 audit-filters">
                <div class="card-body p-3 p-md-4">
                    <form method="get" action="" class="row g-3 align-items-end">
                        <div class="col-12 col-lg-6">
                            <label for="q" class="form-label fw-bold">වාර්තා සොයන්න</label>
                            <input class="form-control" type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="පරිශීලක, ක්‍රියාව, වගුව, IP හෝ ID...">
                        </div>
                        <div class="col-12 col-lg-4">
                            <label for="action" class="form-label fw-bold">ක්‍රියාව අනුව පෙරහන් කරන්න</label>
                            <select class="form-select" id="action" name="action">
                                <option value="">සියලු ක්‍රියා</option>
                                <?php foreach ($availableActions as $availableAction): ?>
                                    <option value="<?= e((string)$availableAction) ?>" <?= $actionFilter === (string)$availableAction ? 'selected' : '' ?>>
                                        <?= e((string)$availableAction) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-lg-1 d-grid">
                            <button class="btn btn-dark" type="submit" aria-label="සොයන්න"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </div>
                        <div class="col-6 col-lg-1 d-grid">
                            <a class="btn btn-outline-secondary" href="<?= e(ADMIN_URL) ?>/audit/index.php" aria-label="පෙරහන් ඉවත් කරන්න" title="පෙරහන් ඉවත් කරන්න"><i class="fa-solid fa-rotate-left"></i></a>
                        </div>
                    </form>
                </div>
            </section>

            <section class="card audit-card">
                <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <strong><i class="fa-solid fa-clock-rotate-left text-warning me-2"></i>ක්‍රියාකාරකම් ලැයිස්තුව</strong>
                    <span class="badge bg-secondary"><?= number_format(count($auditLogs)) ?> වාර්තා</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0 audit-table">
                        <thead class="table-dark">
                            <tr>
                                <th>අංකය</th>
                                <th>පරිශීලකයා</th>
                                <th>ක්‍රියාව</th>
                                <th>වගුව</th>
                                <th>වාර්තා ID</th>
                                <th>IP ලිපිනය</th>
                                <th>දිනය / වේලාව</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$auditLogs): ?>
                            <tr><td colspan="7" class="audit-empty">
                                <i class="fa-solid fa-folder-open fs-2 d-block mb-3"></i>
                                ගැළපෙන ක්‍රියාකාරකම් වාර්තා හමු නොවීය.
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($auditLogs as $log): ?>
                                <?php
                                    $actionText = strtolower((string)($log['action'] ?? ''));
                                    $badgeClass = 'info';
                                    if (str_contains($actionText, 'delete') || str_contains($actionText, 'remove')) {
                                        $badgeClass = 'danger';
                                    } elseif (str_contains($actionText, 'create') || str_contains($actionText, 'insert') || str_contains($actionText, 'add')) {
                                        $badgeClass = 'success';
                                    } elseif (str_contains($actionText, 'update') || str_contains($actionText, 'edit')) {
                                        $badgeClass = 'warning';
                                    }
                                ?>
                                <tr>
                                    <td class="text-muted"><?= (int)$log['id'] ?></td>
                                    <td class="fw-semibold text-nowrap"><?= e((string)($log['username'] ?: 'පද්ධතිය / අමුත්තා')) ?></td>
                                    <td><span class="badge bg-<?= e($badgeClass) ?> <?= $badgeClass === 'warning' ? 'text-dark' : '' ?>"><?= e((string)($log['action'] ?? '—')) ?></span></td>
                                    <td><code><?= e((string)($log['table_name'] ?? '—')) ?></code></td>
                                    <td><?= e((string)($log['record_id'] ?? '—')) ?></td>
                                    <td class="audit-ip"><?= e((string)($log['ip_address'] ?? '—')) ?></td>
                                    <td class="text-nowrap"><?= e((string)($log['created_at'] ?? '—')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white text-muted small">
                    ආරක්ෂක හේතූන් මත මෙම වාර්තා බැලිය හැක්කේ Super Admin හට පමණි. නවතම වාර්තා 100ක් දක්වා පෙන්වයි.
                </div>
            </section>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
