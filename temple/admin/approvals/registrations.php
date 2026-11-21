<?php

/**

 * Pending Registrations Review & Approval Controller

 * Temple Dayaka Management System - Version 1.0

 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';

require_once __DIR__ . '/../../config/database.php';

require_once __DIR__ . '/../../includes/auth.php';

require_once __DIR__ . '/../../includes/helpers.php';

require_once __DIR__ . '/../../includes/audit_logger.php';

// Access Control - Requires 'approve_registrations' permission

Auth::requirePermission('approve_registrations');

$db = Database::getConnection();

$message = '';

$error = '';

// Handle Approval / Rejection Actions

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {

            throw new Exception("ආරක්ෂිත CSRF Token එක වලංගු නැත.");

        }

        $dayakayaId = (int)($_POST['dayakaya_id'] ?? 0);

        $action     = $_POST['action'] ?? '';

        $reason     = trim($_POST['reason'] ?? '');

        if ($dayakayaId <= 0 || !in_array($action, ['approve', 'reject'])) {

            throw new Exception("අවලංගු ක්‍රියාවලියකි.");

        }

        // Fetch target record

        $stmt = $db->prepare("SELECT * FROM dayakayan WHERE id = :id");

        $stmt->execute(['id' => $dayakayaId]);

        $dayakaya = $stmt->fetch();

        if (!$dayakaya) {

            throw new Exception("අදාළ දායක වාර්තාව හමු නොවීය.");

        }

        $db->beginTransaction();

        if ($action === 'approve') {

            // Update Dayakaya status to Approved

            $updStmt = $db->prepare("UPDATE dayakayan SET status = 'approved', rejection_reason = NULL WHERE id = :id");

            $updStmt->execute(['id' => $dayakayaId]);

            // Audit Trail Record

            AuditLogger::log(

                "APPROVE_REGISTRATION",

                "dayakayan",

                $dayakayaId,

                ['status' => 'pending'],

                ['status' => 'approved']

            );

            $message = "දායක ලියාපදිංචිය සාර්ථකව අනුමත කරන ලදී! (Member ID: " . e($dayakaya['member_id']) . ")";

        } elseif ($action === 'reject') {

            if (empty($reason)) {

                throw new Exception("ප්‍රතික්ෂේප කිරීමට හේතුව සඳහන් කිරීම අනිවාර්ය වේ.");

            }

            // Update status to Rejected

            $updStmt = $db->prepare("UPDATE dayakayan SET status = 'rejected', rejection_reason = :reason WHERE id = :id");

            $updStmt->execute(['reason' => $reason, 'id' => $dayakayaId]);

            // Audit Trail Record

            AuditLogger::log(

                "REJECT_REGISTRATION",

                "dayakayan",

                $dayakayaId,

                ['status' => 'pending'],

                ['status' => 'rejected', 'reason' => $reason]

            );

            $message = "ලියාපදිංචි ඉල්ලීම ප්‍රතික්ෂේප කරන ලදී.";

        }

        $db->commit();

    } catch (Exception $e) {

        if ($db->inTransaction()) {

            $db->rollBack();

        }

        $error = $e->getMessage();

    }

}

// Fetch all Pending Registrations with Family & Peruwa details

$pendingQuery = $db->query("

    SELECT d.*, p.name AS peruwa_name, f.address, d.job_type AS job_title

    FROM dayakayan d

    LEFT JOIN families f ON d.family_id = f.id

    LEFT JOIN peruwa p ON f.peruwa_id = p.id

    WHERE d.status = 'pending'

    ORDER BY d.created_at ASC

");

$pendingList = $pendingQuery->fetchAll();

$csrfToken = generateCSRFToken();

require_once __DIR__ . '/../../includes/header.php';

?>

<style>
    .admin-shell {
        display: flex;
        align-items: stretch;
        min-height: calc(100vh - 100px);
    }
    .admin-sidebar {
        flex: 0 0 260px;
        width: 260px;
        background: #212529;
        color: #fff;
    }
    .admin-sidebar .sidebar {
        position: sticky;
        top: 0;
        min-height: calc(100vh - 100px);
        padding: 0 12px 16px;
    }
    .admin-sidebar .nav {
        padding: 12px 0;
        gap: 4px;
    }
    .admin-sidebar .nav-link {
        color: #dee2e6;
        border-radius: 8px;
        padding: 10px 12px;
    }
    .admin-sidebar .nav-link:hover,
    .admin-sidebar .nav-link.active {
        color: #212529;
        background: #ffc107;
    }
    .admin-main {
        flex: 1 1 auto;
        min-width: 0;
    }
    @media (max-width: 767.98px) {
        .admin-shell { display: block; }
        .admin-sidebar { width: 100%; }
        .admin-sidebar .sidebar {
            min-height: auto;
            position: static;
        }
        .admin-sidebar .nav {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .admin-sidebar .nav-link { font-size: .875rem; }
    }
</style>

<div class="admin-shell">
    <aside class="admin-sidebar">
        <?php require __DIR__ . '/../../includes/sidebar.php'; ?>
    </aside>

    <main class="admin-main">
        <div class="container-fluid p-4">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h3 class="m-0"><i class="fa-solid fa-user-check text-warning me-2"></i>ලියාපදිංචි ඉල්ලීම් අනුමත කිරීම් (Pending Registrations)</h3>

                <span class="badge bg-warning text-dark fs-6 px-3 py-2">පෙන්ඩින් සංඛ්‍යාව: <?= count($pendingList) ?></span>

            </div>

            <?php if ($message): ?>

                <div class="alert alert-success alert-dismissible fade show" role="alert">

                    <i class="fa-solid fa-circle-check me-2"></i><?= $message ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>

                </div>

            <?php endif; ?>

            <?php if ($error): ?>

                <div class="alert alert-danger alert-dismissible fade show" role="alert">

                    <i class="fa-solid fa-triangle-exclamation me-2"></i><?= e($error) ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>

                </div>

            <?php endif; ?>

            <?php if (empty($pendingList)): ?>

                <div class="card text-center p-5 shadow-sm border-0">

                    <div class="card-body">

                        <i class="fa-solid fa-clipboard-check text-success display-4 mb-3"></i>

                        <h5>අනුමත කිරීමට කිසිදු නව ලියාපදිංචි ඉල්ලීමක් නොමැත.</h5>

                        <p class="text-muted small mb-0">සියලුම ලියාපදිංචි කිරීම් සමාලෝචනය කර අනුමත කර ඇත.</p>

                    </div>

                </div>

            <?php else: ?>

                <div class="accordion shadow-sm" id="pendingAccordion">

                    <?php foreach ($pendingList as $index => $item): ?>

                        <?php

                            // Fetch associated children & associations

                            $childStmt = $db->prepare("SELECT * FROM children WHERE dayakaya_id = :did");

                            $childStmt->execute(['did' => $item['id']]);

                            $children = $childStmt->fetchAll();

                            $assocStmt = $db->prepare("

                                SELECT a.name FROM associations a 

                                JOIN dayakaya_associations da ON a.id = da.association_id 

                                WHERE da.dayakaya_id = :did

                            ");

                            $assocStmt->execute(['did' => $item['id']]);

                            $assocList = $assocStmt->fetchAll(PDO::FETCH_COLUMN);

                        ?>

                        <div class="accordion-item border mb-3 rounded shadow-sm overflow-hidden">

                            <h2 class="accordion-header" id="heading<?= $item['id'] ?>">

                                <button class="accordion-button <?= $index !== 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $item['id'] ?>">

                                    <div class="d-flex justify-content-between align-items-center w-100 me-3">

                                        <div>

                                            <strong class="text-primary me-2"><?= e($item['full_name']) ?></strong>

                                            <span class="badge bg-secondary me-2"><?= e($item['member_id']) ?></span>

                                            <small class="text-muted"><i class="fa-solid fa-map-pin me-1"></i><?= e($item['peruwa_name']) ?></small>

                                        </div>

                                        <small class="text-muted"><i class="fa-regular fa-clock me-1"></i><?= date('Y-m-d H:i', strtotime($item['created_at'])) ?></small>

                                    </div>

                                </button>

                            </h2>

                            <div id="collapse<?= $item['id'] ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#pendingAccordion">

                                <div class="accordion-body bg-light">

                                    <div class="row g-3">

                                        <!-- Personal Info -->

                                        <div class="col-md-6 border-end">

                                            <h6 class="fw-bold text-dark border-bottom pb-1"><i class="fa-solid fa-id-card me-1"></i> පුද්ගලික විස්තර</h6>

                                            <table class="table table-sm table-borderless small">

                                                <tr><td class="fw-bold text-muted" style="width:35%;">දුරකථන අංකය:</td><td><?= e($item['phone']) ?></td></tr>

                                                <tr><td class="fw-bold text-muted">WhatsApp 01 / 02:</td><td><?= e($item['whatsapp_1'] ?: 'N/A') ?> / <?= e($item['whatsapp_2'] ?: 'N/A') ?></td></tr>

                                                <tr><td class="fw-bold text-muted">NIC / ID:</td><td><?= e($item['nic'] ?: 'N/A') ?></td></tr>

                                                <tr><td class="fw-bold text-muted">උපන් දිනය (වයස):</td><td><?= e($item['dob']) ?> (<?= calculateAge($item['dob']) ?> අවුරුදු)</td></tr>

                                                <tr><td class="fw-bold text-muted">ස්ත්‍රී / පුරුෂ:</td><td><?= e(ucfirst($item['marital_status'])) ?></td></tr>

                                                <tr><td class="fw-bold text-muted">ලිපිනය:</td><td><?= e($item['address'] ?? '') ?></td></tr>

                                            </table>

                                        </div>

                                        <!-- Additional Info -->

                                        <div class="col-md-6">

                                            <h6 class="fw-bold text-dark border-bottom pb-1"><i class="fa-solid fa-briefcase me-1"></i> රැකියාව, සමිති හා පවුල</h6>

                                            <table class="table table-sm table-borderless small">

                                                <tr><td class="fw-bold text-muted" style="width:35%;">රැකියාව:</td><td><?= e($item['job_title'] ?: 'නැත') ?></td></tr>

                                                <tr><td class="fw-bold text-muted">දහම් පාසල:</td><td><?= ($item['attends_daham_pasala'] ?? 'no') === 'yes' ? e(($item['daham_pasala_name'] ?? '') . ' (' . ($item['daham_pasala_grade'] ?? '') . ')') : 'නැත' ?></td></tr>

                                                <tr><td class="fw-bold text-muted">සමිති සාමාජිකත්වය:</td><td><?= !empty($assocList) ? e(implode(', ', $assocList)) : 'නැත' ?></td></tr>

                                                <tr><td class="fw-bold text-muted">දරුවන් ගණන:</td><td><?= count($children) ?> දෙනෙකි</td></tr>

                                            </table>

                                            <?php if (!empty($children)): ?>

                                                <div class="mt-2 bg-white p-2 border rounded">

                                                    <small class="fw-bold d-block mb-1 text-secondary">දරුවන්ගේ ලැයිස්තුව:</small>

                                                    <ul class="list-unstyled mb-0 small">

                                                        <?php foreach ($children as $child): ?>

                                                            <li>• <?= e($child['child_name']) ?> (<?= calculateAge($child['dob']) ?> yrs) - <?= ($child['is_student'] ?? 'no') === 'yes' ? e(($child['school_name'] ?? '') . ' ' . ($child['grade'] ?? '')) : 'Student නොවේ' ?></li>

                                                        <?php endforeach; ?>

                                                    </ul>

                                                </div>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                    <!-- Action Controls -->

                                    <div class="border-top pt-3 mt-3">

                                        <form method="POST" class="row g-2 align-items-center">

                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                                            <input type="hidden" name="dayakaya_id" value="<?= $item['id'] ?>">

                                            <div class="col-md-7">

                                                <input type="text" name="reason" class="form-control form-control-sm" placeholder="ප්‍රතික්ෂේප කරන්නේ නම් හේතුව සඳහන් කරන්න...">

                                            </div>

                                            <div class="col-md-5 text-end">

                                                <button type="submit" name="action" value="approve" class="btn btn-success btn-sm px-4 fw-bold me-2">

                                                    <i class="fa-solid fa-check me-1"></i> Approve & Publish

                                                </button>

                                                <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm px-3 fw-bold" onclick="return confirm('මෙම ලියාපදිංචි ඉල්ලීම ප්‍රතික්ෂේප කිරීමට ඔබට විශ්වාසද?');">

                                                    <i class="fa-solid fa-xmark me-1"></i> Reject

                                                </button>

                                            </div>

                                        </form>

                                    </div>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
