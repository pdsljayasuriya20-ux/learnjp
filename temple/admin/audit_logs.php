<?php
declare(strict_types=1);

/**
 * System Audit Trail Inspector
 * Temple Dayaka Management System - Version 1.0 Blueprint
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireSuperAdmin();

$db = Database::getConnection();

$logs = $db->query("
    SELECT a.*, u.username 
    FROM audit_logs a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 100
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h3 class="fw-bold text-dark mb-0"><i class="fa-solid fa-list-check text-warning me-2"></i>Audit Logs (ක්‍රියාකාරකම් සටහන)</h3>
        <a href="<?= e(ADMIN_URL) ?>/dashboard.php" class="btn btn-outline-dark fw-bold btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> Dashboard</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>දිනය සහ වේලාව</th>
                            <th>පරිශීලකයා</th>
                            <th>ක්‍රියාව (Action)</th>
                            <th>විස්තරය (Details)</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Audit logs සටහන් වී නැත.</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><small class="fw-bold"><?= e($log['created_at']) ?></small></td>
                                    <td><span class="badge bg-secondary"><?= e($log['username'] ?? 'System / Public') ?></span></td>
                                    <td><span class="badge bg-warning text-dark fw-bold"><?= e($log['action']) ?></span></td>
                                    <td class="small"><?= e($log['details']) ?></td>
                                    <td><code><?= e($log['ip_address'] ?? 'N/A') ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>