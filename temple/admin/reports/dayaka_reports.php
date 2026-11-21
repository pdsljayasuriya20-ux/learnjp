<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

Auth::requireAuth();
if (!Auth::hasPermission('view_reports')) {
    http_response_code(403);
    exit('ඔබට මෙම වාර්තාව බැලීමට අවසර නැත.');
}

$db = Database::getConnection();
$status = (string)($_GET['status'] ?? 'all');
$peruwaId = filter_input(INPUT_GET, 'peruwa_id', FILTER_VALIDATE_INT) ?: 0;
$allowedStatus = ['all', 'approved', 'pending', 'rejected'];
if (!in_array($status, $allowedStatus, true)) {
    $status = 'all';
}

$where = [];
$params = [];
if ($status !== 'all') {
    $where[] = 'd.status = :status';
    $params['status'] = $status;
}
if ($peruwaId > 0) {
    $where[] = 'f.peruwa_id = :peruwa_id';
    $params['peruwa_id'] = $peruwaId;
}
$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$summary = $db->query("SELECT COUNT(*) AS total,
    COALESCE(SUM(status = 'approved'), 0) AS approved,
    COALESCE(SUM(status = 'pending'), 0) AS pending,
    COALESCE(SUM(status = 'rejected'), 0) AS rejected
    FROM dayakayan")->fetch() ?: [];
$peruwas = $db->query('SELECT id, name FROM peruwa ORDER BY id')->fetchAll();

$stmt = $db->prepare("SELECT d.id, d.member_id, d.full_name, d.phone, d.status, d.created_at,
    f.family_code, p.name AS peruwa_name
    FROM dayakayan d
    LEFT JOIN families f ON f.id = d.family_id
    LEFT JOIN peruwa p ON p.id = f.peruwa_id
    $sqlWhere
    ORDER BY d.created_at DESC, d.id DESC
    LIMIT 500");
$stmt->execute($params);
$rows = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<style>
.admin-shell{display:flex;align-items:stretch;min-height:calc(100vh - 100px)}
.admin-sidebar{flex:0 0 260px;width:260px;background:#212529;color:#fff}
.admin-sidebar .sidebar{position:sticky;top:0;min-height:calc(100vh - 100px);padding:0 12px 16px}
.admin-sidebar .nav{padding:12px 0;gap:4px}
.admin-sidebar .nav-link{color:#dee2e6;border-radius:8px;padding:10px 12px}
.admin-sidebar .nav-link:hover,.admin-sidebar .nav-link.active{color:#212529;background:#ffc107}
.admin-main{flex:1 1 auto;min-width:0}
@media(max-width:767.98px){.admin-shell{display:block}.admin-sidebar{width:100%}.admin-sidebar .sidebar{min-height:auto;position:static}.admin-sidebar .nav{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}.admin-sidebar .nav-link{font-size:.875rem}}
@media print{.admin-sidebar,.no-print,form,.btn,.navbar,footer{display:none!important}.admin-shell{display:block;min-height:0}.admin-main{width:100%;padding:0!important}.container-fluid{padding:0!important}.card{box-shadow:none!important;border:1px solid #ddd!important}.card-body{padding:.5rem!important}body{font-size:10pt}.table{font-size:9pt}.table-responsive{overflow:visible!important}thead{display:table-header-group}tr{break-inside:avoid}}
</style>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <?php require __DIR__ . '/../../includes/sidebar.php'; ?>
    </aside>
    <main class="admin-main">
        <div class="container-fluid my-4 px-3 px-md-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                <div>
                    <h3 class="fw-bold mb-1"><i class="fa-solid fa-file-invoice text-warning me-2"></i>දායක වාර්තා</h3>
                    <p class="text-muted mb-0">දායක ලියාපදිංචි තත්ත්වය සහ පේරුව අනුව වාර්තා</p>
                </div>
                <button type="button" class="btn btn-outline-secondary no-print" onclick="window.print()"><i class="fa-solid fa-print me-1"></i> මුද්‍රණය</button>
            </div>

            <div class="row g-3 mb-4">
                <?php foreach ([['මුළු දායකයින්', 'total', 'primary'], ['අනුමත', 'approved', 'success'], ['අනුමැතිය බලාපොරොත්තුවෙන්', 'pending', 'warning'], ['ප්‍රතික්ෂේප කළ', 'rejected', 'danger']] as [$label, $key, $color]): ?>
                    <div class="col-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="text-muted small"><?= e($label) ?></div>
                                <div class="fs-2 fw-bold text-<?= e($color) ?>"><?= number_format((int)($summary[$key] ?? 0)) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card border-0 shadow-sm mb-4 no-print">
                <div class="card-body">
                    <form method="get" class="row g-3 align-items-end">
                        <div class="col-12 col-sm-5">
                            <label class="form-label" for="status">ලියාපදිංචි තත්ත්වය</label>
                            <select id="status" name="status" class="form-select">
                                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>සියලුම තත්ත්වයන්</option>
                                <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>අනුමත</option>
                                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>ප්‍රතික්ෂේප කළ</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-5">
                            <label class="form-label" for="peruwa_id">පේරුව</label>
                            <select id="peruwa_id" name="peruwa_id" class="form-select">
                                <option value="0">සියලුම පේරු</option>
                                <?php foreach ($peruwas as $p): ?>
                                    <option value="<?= (int)$p['id'] ?>" <?= $peruwaId === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-sm-2"><button type="submit" class="btn btn-warning fw-bold w-100">පෙන්වන්න</button></div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center gap-2">
                    <strong>දායක වාර්තා ලැයිස්තුව</strong>
                    <span class="badge bg-secondary"><?= count($rows) ?> records</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Member ID</th><th>නම</th><th>දුරකථන</th><th>පේරුව</th><th>පවුල් කේතය</th><th>තත්ත්වය</th><th>ලියාපදිංචි දිනය</th></tr></thead>
                        <tbody>
                        <?php if (!$rows): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">ගැළපෙන වාර්තා නැත.</td></tr>
                        <?php else: foreach ($rows as $r): ?>
                            <tr>
                                <td class="text-nowrap"><?= e((string)$r['member_id']) ?></td>
                                <td class="fw-semibold"><?= e((string)$r['full_name']) ?></td>
                                <td><?= e((string)($r['phone'] ?: '—')) ?></td>
                                <td><?= e((string)($r['peruwa_name'] ?: '—')) ?></td>
                                <td><?= e((string)($r['family_code'] ?: '—')) ?></td>
                                <td><?php $c = ['approved' => 'success', 'pending' => 'warning', 'rejected' => 'danger'][$r['status']] ?? 'secondary'; ?><span class="badge bg-<?= e($c) ?> <?= $c === 'warning' ? 'text-dark' : '' ?>"><?= e((string)$r['status']) ?></span></td>
                                <td class="text-nowrap"><?= e((string)$r['created_at']) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <p class="text-muted small mt-3 mb-0">සටහන: මෙම ලැයිස්තුවේ උපරිම වාර්තා 500ක් පෙන්වයි.</p>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
