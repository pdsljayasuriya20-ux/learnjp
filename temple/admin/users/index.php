<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

Auth::requireAuth();
if (!Auth::isSuperAdmin()) {
    http_response_code(403);
    exit('මෙම පිටුව Super Admin සඳහා පමණි.');
}

$db = Database::getConnection();
$rows = $db->query(
    'SELECT id, username, email, role, status, created_at
     FROM users
     ORDER BY id DESC'
)->fetchAll(PDO::FETCH_ASSOC);

$totalUsers = count($rows);
$activeUsers = count(array_filter($rows, static fn(array $r): bool => ($r['status'] ?? '') === 'active'));
$pendingUsers = count(array_filter($rows, static fn(array $r): bool => ($r['status'] ?? '') === 'pending'));

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
@media(max-width:767.98px){
    .admin-shell{display:block}
    .admin-sidebar{width:100%;flex:0 0 auto}
    .admin-sidebar .sidebar{min-height:auto;position:static}
    .admin-sidebar .nav{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));padding:12px 0;gap:4px}
    .admin-sidebar .nav-link{font-size:.875rem}
}
@media print{
    .admin-sidebar,.page-actions,header,footer,.navbar,.btn{display:none!important}
    .admin-shell{display:block;min-height:0}
    .admin-main{width:100%;padding:0!important}
    .container-fluid{padding:0!important;margin:0!important}
    .card{box-shadow:none!important;border:1px solid #ddd!important}
    body{background:#fff!important}
}
</style>

<div class="admin-shell">
    <aside class="admin-sidebar">
        <?php require __DIR__ . '/../../includes/sidebar.php'; ?>
    </aside>

    <main class="admin-main">
        <div class="container-fluid my-4 px-3 px-md-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h3 class="fw-bold mb-1"><i class="fa-solid fa-user-shield text-warning me-2"></i>පරිපාලක පරිශීලකයින්</h3>
                    <p class="text-muted mb-0">Admin accounts, roles සහ account status පරීක්ෂා කරන්න</p>
                </div>
                <div class="d-flex flex-wrap gap-2 page-actions">
                    <a class="btn btn-outline-primary" href="<?= e(ADMIN_URL) ?>/users/permissions.php"><i class="fa-solid fa-key me-1"></i> අවසර</a>
                    <a class="btn btn-warning fw-bold" href="<?= e(ADMIN_URL) ?>/users/create.php"><i class="fa-solid fa-user-plus me-1"></i> නව පරිශීලකයෙක්</a>
                    <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="fa-solid fa-print me-1"></i> මුද්‍රණය</button>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100"><div class="card-body">
                        <div class="text-muted small">මුළු පරිශීලකයින්</div>
                        <div class="fs-2 fw-bold text-primary"><?= number_format($totalUsers) ?></div>
                    </div></div>
                </div>
                <div class="col-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100"><div class="card-body">
                        <div class="text-muted small">Active ගිණුම්</div>
                        <div class="fs-2 fw-bold text-success"><?= number_format($activeUsers) ?></div>
                    </div></div>
                </div>
                <div class="col-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100"><div class="card-body">
                        <div class="text-muted small">Pending ගිණුම්</div>
                        <div class="fs-2 fw-bold text-warning"><?= number_format($pendingUsers) ?></div>
                    </div></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <strong>පරිශීලක ලැයිස්තුව</strong>
                    <span class="badge bg-secondary"><?= number_format($totalUsers) ?> users</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr>
                            <th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th>
                        </tr></thead>
                        <tbody>
                        <?php if (!$rows): ?>
                            <tr><td colspan="7" class="text-center text-muted py-5">පරිශීලකයින් නොමැත.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $r): ?>
                                <?php $color = ['active' => 'success', 'pending' => 'warning', 'suspended' => 'danger'][$r['status'] ?? ''] ?? 'secondary'; ?>
                                <tr>
                                    <td><?= (int)$r['id'] ?></td>
                                    <td class="fw-semibold text-nowrap"><?= e((string)$r['username']) ?></td>
                                    <td><?= e((string)$r['email']) ?></td>
                                    <td><span class="badge bg-dark"><?= e((string)$r['role']) ?></span></td>
                                    <td><span class="badge bg-<?= e($color) ?> <?= $color === 'warning' ? 'text-dark' : '' ?>"><?= e((string)$r['status']) ?></span></td>
                                    <td class="text-nowrap"><?= e((string)$r['created_at']) ?></td>
                                    <td><a class="btn btn-sm btn-outline-primary" href="<?= e(ADMIN_URL) ?>/users/permissions.php?user_id=<?= (int)$r['id'] ?>" title="Permissions" aria-label="Permissions for <?= e((string)$r['username']) ?>"><i class="fa-solid fa-key"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white small text-muted">Password hash එක ආරක්ෂාව සඳහා මෙහි නොපෙන්වයි.</div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
