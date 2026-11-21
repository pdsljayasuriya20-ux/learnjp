<?php
declare(strict_types=1);

/**
 * Donation history with month, status, and keyword filters.
 * Uses the existing monthly_donations and dayakayan tables.
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

Auth::requireAuth();

if (!Auth::hasPermission('manage_donations')) {
    http_response_code(403);
    exit('<div style="font-family:Arial,sans-serif;max-width:700px;margin:60px auto;padding:24px;border:1px solid #dc3545;border-radius:10px;color:#842029;background:#f8d7da"><h2>ප්‍රවේශ අවසර නැත</h2><p>මෙම පිටුව භාවිතා කිරීමට <code>manage_donations</code> permission එක අවශ්‍යයි.</p><p><a href="' . e(ADMIN_URL) . '/dashboard.php">Dashboard වෙත ආපසු</a></p></div>');
}

$db = Database::getConnection();

$month = trim((string)($_GET['month'] ?? date('Y-m')));
if (!preg_match('/^\d{4}-\d{2}$/', $month) || !checkdate((int)substr($month, 5, 2), 1, (int)substr($month, 0, 4))) {
    $month = date('Y-m');
}
$statusFilter = (string)($_GET['status'] ?? 'all');
if (!in_array($statusFilter, ['all', 'paid', 'pending'], true)) {
    $statusFilter = 'all';
}
$keyword = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($keyword, 'UTF-8') > 100) {
    $keyword = mb_substr($keyword, 0, 100, 'UTF-8');
}

$monthStart = $month . '-01';
$nextMonthStart = date('Y-m-d', strtotime($monthStart . ' +1 month'));

$where = ['md.payment_date >= :start_date', 'md.payment_date < :end_date'];
$params = ['start_date' => $monthStart, 'end_date' => $nextMonthStart];

if ($statusFilter !== 'all') {
    $where[] = 'md.status = :status';
    $params['status'] = $statusFilter;
}
if ($keyword !== '') {
    $where[] = '(d.full_name LIKE :keyword OR d.member_id LIKE :keyword OR md.notes LIKE :keyword)';
    $params['keyword'] = '%' . $keyword . '%';
}
$whereSql = implode(' AND ', $where);

$statsStmt = $db->prepare("SELECT COUNT(*) AS total_count,
    COALESCE(SUM(CASE WHEN md.status = 'paid' THEN md.amount ELSE 0 END), 0) AS paid_total,
    COALESCE(SUM(CASE WHEN md.status = 'pending' THEN md.amount ELSE 0 END), 0) AS pending_total,
    COALESCE(SUM(CASE WHEN md.status = 'paid' THEN 1 ELSE 0 END), 0) AS paid_count
    FROM monthly_donations md
    INNER JOIN dayakayan d ON d.id = md.dayakaya_id
    WHERE {$whereSql}");
$statsStmt->execute($params);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: ['total_count' => 0, 'paid_total' => 0, 'pending_total' => 0, 'paid_count' => 0];

$listStmt = $db->prepare("SELECT md.id, md.amount, md.payment_date, md.payment_method, md.status, md.notes,
    d.member_id, d.full_name
    FROM monthly_donations md
    INNER JOIN dayakayan d ON d.id = md.dayakaya_id
    WHERE {$whereSql}
    ORDER BY md.payment_date DESC, md.id DESC
    LIMIT 500");
$listStmt->execute($params);
$donations = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$methodLabels = [
    'cash' => 'මුදල් (Cash)',
    'bank_transfer' => 'බැංකු හුවමාරුව',
    'online' => 'Online',
    'other' => 'වෙනත්',
];

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
</style>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <?php require __DIR__ . '/../../includes/sidebar.php'; ?>
    </aside>
    <main class="admin-main">
        <div class="container-fluid my-4 px-3 px-md-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4 pb-3 border-bottom">
                <div>
                    <h3 class="fw-bold mb-1"><i class="fa-solid fa-clock-rotate-left text-warning me-2"></i>දායක මුදල් ඉතිහාසය</h3>
                    <p class="text-muted mb-0">මාසික ගෙවීම් වාර්තා සොයන්න සහ පරීක්ෂා කරන්න.</p>
                </div>
                <a href="<?= e(ADMIN_URL) ?>/donations/collection.php" class="btn btn-warning fw-bold"><i class="fa-solid fa-circle-plus me-2"></i>නව ගෙවීමක්</a>
            </div>

            <form method="get" class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-3">
                            <label for="month" class="form-label fw-semibold">මාසය</label>
                            <input type="month" id="month" name="month" class="form-control" value="<?= e($month) ?>" required>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="status" class="form-label fw-semibold">ගෙවීම් තත්ත්වය</label>
                            <select id="status" name="status" class="form-select">
                                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>සියල්ල</option>
                                <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>>ගෙවා ඇත</option>
                                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>ගෙවීමට ඇත</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="q" class="form-label fw-semibold">දායකයා / සාමාජික අංකය / සටහන්</label>
                            <input type="search" id="q" name="q" class="form-control" maxlength="100" value="<?= e($keyword) ?>" placeholder="සෙවීමට ඇතුළත් කරන්න">
                        </div>
                        <div class="col-12 col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-dark flex-grow-1"><i class="fa-solid fa-magnifying-glass me-1"></i>සොයන්න</button>
                            <a href="<?= e(ADMIN_URL) ?>/donations/history.php" class="btn btn-outline-secondary" title="පෙරහන් ඉවත් කරන්න"><i class="fa-solid fa-rotate-left"></i></a>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small fw-bold">පෙරහන් කළ වාර්තා</div><div class="fs-3 fw-bold mt-1"><?= number_format((int)$stats['total_count']) ?></div></div></div></div>
                <div class="col-12 col-sm-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small fw-bold">ගෙවා ඇති මුදල</div><div class="fs-4 fw-bold text-success mt-1">Rs. <?= number_format((float)$stats['paid_total'], 2) ?></div></div></div></div>
                <div class="col-12 col-sm-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small fw-bold">ගෙවීමට ඇති මුදල</div><div class="fs-4 fw-bold text-warning mt-1">Rs. <?= number_format((float)$stats['pending_total'], 2) ?></div></div></div></div>
                <div class="col-12 col-sm-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small fw-bold">සම්පූර්ණ ගෙවීම් ගණන</div><div class="fs-3 fw-bold text-primary mt-1"><?= number_format((int)$stats['paid_count']) ?></div></div></div></div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                    <h5 class="mb-0 fw-bold">ගෙවීම් වාර්තා</h5>
                    <span class="badge bg-secondary"><?= count($donations) ?> records shown (maximum 500)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>#</th><th>දිනය</th><th>දායකයා</th><th>ගෙවීම් ක්‍රමය</th><th class="text-end">මුදල</th><th>තත්ත්වය</th><th>සටහන්</th></tr></thead>
                        <tbody>
                        <?php if (!$donations): ?>
                            <tr><td colspan="7" class="text-center text-muted py-5"><i class="fa-regular fa-folder-open fs-2 d-block mb-2"></i>මෙම පෙරහන්වලට ගැළපෙන වාර්තා නැත.</td></tr>
                        <?php else: foreach ($donations as $donation): ?>
                            <tr>
                                <td><?= (int)$donation['id'] ?></td>
                                <td class="text-nowrap"><?= e(date('Y-m-d', strtotime((string)$donation['payment_date']))) ?></td>
                                <td><div class="fw-semibold"><?= e($donation['full_name']) ?></div><small class="text-muted"><?= e($donation['member_id']) ?></small></td>
                                <td><?= e($methodLabels[$donation['payment_method']] ?? $donation['payment_method']) ?></td>
                                <td class="text-end text-nowrap fw-bold">Rs. <?= number_format((float)$donation['amount'], 2) ?></td>
                                <td><?php if ($donation['status'] === 'paid'): ?><span class="badge bg-success">ගෙවා ඇත</span><?php else: ?><span class="badge bg-warning text-dark">ගෙවීමට ඇත</span><?php endif; ?></td>
                                <td><span title="<?= e($donation['notes'] ?? '') ?>"><?= e(mb_strimwidth((string)($donation['notes'] ?? ''), 0, 45, '…', 'UTF-8')) ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white text-muted small">පෙන්වන්නේ <?= e($month) ?> මාසයේ වාර්තායි. ප්‍රතිඵල 500කට සීමා කර ඇත.</div>
            </div>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
