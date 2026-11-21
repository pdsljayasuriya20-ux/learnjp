<?php
declare(strict_types=1);

/**
 * Registration Approval Engine
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

$db = Database::getConnection();
$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dayakayaId = (int)($_POST['dayakaya_id'] ?? 0);
    $action     = $_POST['action'] ?? '';
    $reason     = trim($_POST['rejection_reason'] ?? '');

    if ($dayakayaId > 0) {
        try {
            $db->beginTransaction();

            if ($action === 'approve') {
                $stmt = $db->prepare("UPDATE dayakayan SET status = 'approved', rejection_reason = NULL WHERE id = :id");
                $stmt->execute(['id' => $dayakayaId]);

                $info = $db->query("SELECT full_name, member_id FROM dayakayan WHERE id = {$dayakayaId}")->fetch();

                $audit = $db->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (:uid, 'REGISTRATION_APPROVED', :det, :ip)");
                $audit->execute([
                    'uid' => Auth::user()['id'] ?? 1,
                    'det' => "ලියාපදිංචිය අනුමත කරන ලදී: {$info['full_name']} ({$info['member_id']})",
                    'ip'  => $_SERVER['REMOTE_ADDR'] ?? ''
                ]);

                $message = "දායක ලියාපදිංචිය සාර්ථකව අනුමත කරන ලදී!";

            } elseif ($action === 'reject') {
                if (empty($reason)) {
                    throw new Exception("Reject කිරීමට හේතුව (Reason) අනිවාර්යයෙන් ඇතුළත් කළ යුතුය.");
                }

                $stmt = $db->prepare("UPDATE dayakayan SET status = 'rejected', rejection_reason = :reason WHERE id = :id");
                $stmt->execute(['id' => $dayakayaId, 'reason' => $reason]);

                $info = $db->query("SELECT full_name, member_id FROM dayakayan WHERE id = {$dayakayaId}")->fetch();

                $audit = $db->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (:uid, 'REGISTRATION_REJECTED', :det, :ip)");
                $audit->execute([
                    'uid' => Auth::user()['id'] ?? 1,
                    'det' => "ලියාපදිංචිය ප්‍රතික්ෂේප කරන ලදී: {$info['full_name']} ({$info['member_id']}) - හේතුව: {$reason}",
                    'ip'  => $_SERVER['REMOTE_ADDR'] ?? ''
                ]);

                $message = "දායක ලියාපදිංචිය ප්‍රතික්ෂේප කරන ලදී.";
            }

            $db->commit();

        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}

$pendingQuery = "
    SELECT d.*, p.name as peruwa_name, f.address, f.family_code,
           (SELECT COUNT(*) FROM children c WHERE c.dayakaya_id = d.id) as children_count
    FROM dayakayan d
    LEFT JOIN families f ON d.family_id = f.id
    LEFT JOIN peruwa p ON f.peruwa_id = p.id
    WHERE d.status = 'pending'
    ORDER BY d.created_at ASC
";
$pendingList = $db->query($pendingQuery)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid my-4 px-md-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-user-check text-warning me-2"></i>ලියාපදිංචි අනුමත කිරීම් (Registration Approvals)
            </h3>
            <p class="text-muted small mb-0">නව දායකයින්ගේ ලියාපදිංචි ඉල්ලීම් පරීක්ෂා කර අනුමත කිරීම හෝ ප්‍රතික්ෂේප කිරීම</p>
        </div>
        <div class="mt-3 mt-md-0">
            <a href="<?= e(ADMIN_URL) ?>/dashboard.php" class="btn btn-outline-dark fw-bold btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show fw-bold mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?= e($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show fw-bold mb-4" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?= e($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($pendingList)): ?>
        <div class="card border-0 shadow-sm p-5 text-center bg-white">
            <i class="fa-solid fa-clipboard-check text-success display-1 mb-3"></i>
            <h4 class="fw-bold text-dark">අනුමැතියට හිඟ ලියාපදිංචි කිරීම් කිසිවක් නැත!</h4>
            <p class="text-muted">සියලුම දායක ලියාපදිංචි ඉල්ලීම් පරීක්ෂා කර අවසන් කර ඇත.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($pendingList as $item): ?>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100 border-top border-warning border-4">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-warning text-dark fw-bold px-2 py-1 mb-1 d-inline-block">
                                    Member ID: <?= e($item['member_id']) ?>
                                </span>
                                <h5 class="fw-bold text-dark mb-0"><?= e($item['full_name']) ?></h5>
                            </div>
                            <span class="badge bg-secondary">Family: <?= e($item['family_code'] ?? 'N/A') ?></span>
                        </div>
                        <div class="card-body">
                            <div class="row g-2 small mb-3">
                                <div class="col-6"><strong>පේරුව:</strong> <?= e($item['peruwa_name'] ?? 'නොමැත') ?></div>
                                <div class="col-6"><strong>දුරකථන අංකය:</strong> <?= e($item['phone']) ?></div>
                                <div class="col-6"><strong>උපන් දිනය:</strong> <?= e($item['dob']) ?> (වයස: <?= e((string)$item['age']) ?>)</div>
                                <div class="col-6"><strong>NIC / ID:</strong> <?= e($item['nic'] ?? 'නොමැත') ?></div>
                                <div class="col-6"><strong>විවාහක/අවිවාහක:</strong> <?= e($item['marital_status'] === 'married' ? 'විවාහක' : 'අවිවාහක') ?></div>
                                <div class="col-6"><strong>දරුවන් සංඛ්‍යාව:</strong> <?= e((string)$item['children_count']) ?></div>
                                <div class="col-12"><strong>ලිපිනය:</strong> <?= e($item['address'] ?? 'නොමැත') ?></div>
                            </div>

                            <div class="border-top pt-3 d-flex flex-column gap-2">
                                <form method="POST" action="" onsubmit="return confirm('මෙම දායකයා අනුමත කිරීමට තහවුරු කරන්නද?');">
                                    <input type="hidden" name="dayakaya_id" value="<?= $item['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-success fw-bold w-100 shadow-sm">
                                        <i class="fa-solid fa-check me-1"></i> අනුමත කරන්න (Approve)
                                    </button>
                                </form>

                                <button class="btn btn-outline-danger fw-bold w-100" type="button" data-bs-toggle="collapse" data-bs-target="#rejectBox_<?= $item['id'] ?>">
                                    <i class="fa-solid fa-xmark me-1"></i> ප්‍රතික්ෂේප කරන්න (Reject)
                                </button>

                                <div class="collapse mt-2" id="rejectBox_<?= $item['id'] ?>">
                                    <form method="POST" action="" class="p-3 bg-light rounded border">
                                        <input type="hidden" name="dayakaya_id" value="<?= $item['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <label class="form-label fw-bold small text-danger">ප්‍රතික්ෂේප කිරීමට හේතුව (Reason) *</label>
                                        <textarea name="rejection_reason" class="form-control form-control-sm mb-2" rows="2" required placeholder="හේතුව සඳහන් කරන්න..."></textarea>
                                        <button type="submit" class="btn btn-danger btn-sm fw-bold w-100">
                                            ප්‍රතික්ෂේප කිරීම තහවුරු කරන්න
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>