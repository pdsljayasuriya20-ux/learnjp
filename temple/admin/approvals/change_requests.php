<?php

/**

 * Staging Change Request Review & Side-by-Side Diff Controller

 * Temple Dayaka Management System - Version 1.0

 */



declare(strict_types=1);



require_once __DIR__ . '/../../config/app.php';

require_once __DIR__ . '/../../config/database.php';

require_once __DIR__ . '/../../includes/auth.php';

require_once __DIR__ . '/../../includes/helpers.php';

require_once __DIR__ . '/../../includes/audit_logger.php';



// Access Control - Super Admin or Granted Admin Permission

Auth::requirePermission('approve_registrations');



$db = Database::getConnection();

$message = '';$error = '';



// Handle Change Request Approval / Rejection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {

            throw new Exception("ආරක්ෂිත CSRF Token එක වලංගු නැත.");

        }



        $requestId = (int)($_POST['request_id'] ?? 0);

        $action    =$_POST['action'] ?? '';

        $reason    = trim($_POST['reason'] ?? '');



        if ($requestId <= 0 || !in_array($action, ['approve', 'reject'])) {

            throw new Exception("අවලංගු ක්‍රියාවලියකි.");

        }



        // Fetch pending change request

        $stmt =$db->prepare("SELECT * FROM change_requests WHERE id = :id AND status = 'pending'");

        $stmt->execute(['id' =>$requestId]);

        $changeReq =$stmt->fetch();



        if (!$changeReq) {

            throw new Exception("අදාළ දත්ත වෙනස්කම් ඉල්ලීම හමු නොවීය.");

        }



        $dayakayaId = (int)$changeReq['dayakaya_id'];

        $newValues  = json_decode($changeReq['new_data'], true) ?? [];

        $oldValues  = json_decode($changeReq['old_data'], true) ?? [];



        $db->beginTransaction();



        if ($action === 'approve') {

            // Apply new values directly to the active dayakayan table

            if (!empty($newValues)) {$updateFields = [];

                $params = ['id' =>$dayakayaId];



                foreach ($newValues as$col => $val) {if (!in_array($col, ['full_name','nic','dob','age','phone','whatsapp_1','whatsapp_2','landline','email','marital_status','has_job','job_type','job_details','attends_daham_pasala','daham_pasala_name','daham_pasala_grade','profile_photo'], true)) { continue; }

                    $updateFields[] = "`$col` = :$col";

                    $params[$col] = $val;

                }



                if ($updateFields) {

                    $sql = "UPDATE dayakayan SET " . implode(', ', $updateFields) . " WHERE id = :id";

                    $updStmt = $db->prepare($sql);

                    $updStmt->execute($params);

                }

            }



            // Update change_requests record

            $reqUpd =$db->prepare("

                UPDATE change_requests SET status = 'approved' WHERE id = :id

            ");

            $reqUpd->execute(['id' => $requestId]);



            // Audit Trail Log

            AuditLogger::log("APPROVE_CHANGE_REQUEST", "dayakayan", $dayakayaId, $oldValues,$newValues);



            $message = "දත්ත වෙනස්කම සාර්ථකව අනුමත කර සක්‍රිය (Live) කරන ලදී!";

        } elseif ($action === 'reject') {

            // Update change_requests record to rejected

            $reqUpd =$db->prepare("

                UPDATE change_requests SET status = 'rejected' WHERE id = :id

            ");

            $reqUpd->execute(['id' => $requestId]);



            // Audit Trail Log

            AuditLogger::log("REJECT_CHANGE_REQUEST", "dayakayan", $dayakayaId, $oldValues, ['reason' =>$reason]);



            $message = "දත්ත වෙනස් කිරීමේ ඉල්ලීම ප්‍රතික්ෂේප කරන ලදී.";

        }



        $db->commit();

    } catch (Exception $e) {

        if ($db->inTransaction()) {$db->rollBack();

        }

        $error =$e->getMessage();

    }

}



// Fetch Pending Change Requests

$pendingRequests =$db->query("

    SELECT 

        cr.*,

        d.full_name,

        d.member_id,

        u.username as requested_by_user

    FROM change_requests cr

    JOIN dayakayan d ON cr.dayakaya_id = d.id

    JOIN users u ON cr.requested_by = u.id

    WHERE cr.status = 'pending'

    ORDER BY cr.created_at ASC

")->fetchAll();



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
        .admin-shell {
            display: block;
        }

        .admin-sidebar {
            width: 100%;
        }

        .admin-sidebar .sidebar {
            min-height: auto;
            position: static;
        }

        .admin-sidebar .nav {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .admin-sidebar .nav-link {
            font-size: .875rem;
        }
    }
</style>

<div class="admin-shell">
    <aside class="admin-sidebar">
        <?php require __DIR__ . '/../../includes/sidebar.php'; ?>
    </aside>

    <main class="admin-main">
        <div class="container-fluid my-4 px-3 px-md-4">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h3 class="m-0"><i class="fa-solid fa-code-compare text-warning me-2"></i>දත්ත වෙනස්කම් අනුමතය (Change Requests Approval)</h3>

                <span class="badge bg-danger text-white fs-6 px-3 py-2">පෙන්ඩින් වෙනස්කම්: <?= count($pendingRequests) ?></span>

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



            <?php if (empty($pendingRequests)): ?>

                <div class="card text-center p-5 shadow-sm border-0">

                    <div class="card-body">

                        <i class="fa-solid fa-check-double text-success display-4 mb-3"></i>

                        <h5>අනුමත කිරීමට කිසිදු දත්ත වෙනස් කිරීමේ ඉල්ලීමක් නොමැත.</h5>

                        <p class="text-muted small mb-0">සියලුම Admin වෙනස්කම් සක්‍රිය තත්ත්වයේ පවතී.</p>

                    </div>

                </div>

            <?php else: ?>

                <?php foreach ($pendingRequests as$req): ?>

                    <?php 

                        $oldData = json_decode($req['old_data'], true) ?? [];

                        $newData = json_decode($req['new_data'], true) ?? [];

                    ?>

                    <div class="card border-warning shadow-sm mb-4">

                        <div class="card-header bg-warning bg-opacity-25 d-flex justify-content-between align-items-center py-3">

                            <div>

                                <strong class="fs-6 text-dark me-2">දායකයා: <?= e($req['full_name']) ?></strong>

                                <span class="badge bg-secondary"><?= e($req['member_id']) ?></span>

                            </div>

                            <small class="text-dark">

                                <i class="fa-solid fa-user-pen me-1"></i> වෙනස් කළේ: <strong><?= e($req['requested_by_user']) ?></strong> | 

                                <i class="fa-regular fa-clock ms-2 me-1"></i><?= date('Y-m-d H:i', strtotime($req['created_at'])) ?>

                            </small>

                        </div>

                        <div class="card-body p-4">

                            <h6 class="fw-bold mb-3"><i class="fa-solid fa-table-columns me-2 text-warning"></i>සසඳා බැලීම (Side-by-Side Data Diff View):</h6>

                            <table class="table table-bordered align-middle">

                                <thead class="table-dark">

                                    <tr>

                                        <th style="width: 30%;">විස්තරය (Field Name)</th>

                                        <th style="width: 35%;" class="table-danger text-danger"><i class="fa-solid fa-minus-circle me-1"></i>පැරණි දත්ත (Old Value)</th>

                                        <th style="width: 35%;" class="table-success text-success"><i class="fa-solid fa-plus-circle me-1"></i>නව දත්ත (New Value)</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach ($newData as $field =>$newVal): ?>

                                        <?php $oldVal = $oldData[$field] ?? 'N/A'; ?>

                                        <tr>

                                            <td class="fw-bold text-secondary"><?= e(strtoupper(str_replace('_', ' ', $field))) ?></td>

                                            <td class="bg-danger bg-opacity-10 text-danger text-decoration-line-through">

                                                <?= e((string)($oldVal ?: 'None')) ?>

                                            </td>

                                            <td class="bg-success bg-opacity-10 text-success fw-bold">

                                                <?= e((string)($newVal ?: 'None')) ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>



                            <!-- Action Form -->

                            <form method="POST" class="mt-4 border-top pt-3">

                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">



                                <div class="row g-2 align-items-center">

                                    <div class="col-md-7">

                                        <input type="text" name="reason" class="form-control" placeholder="ප්‍රතික්ෂේප කරන්නේ නම් හේතුව සටහන් කරන්න...">

                                    </div>

                                    <div class="col-md-5 text-end">

                                        <button type="submit" name="action" value="approve" class="btn btn-success px-4 fw-bold me-2">

                                            <i class="fa-solid fa-check me-1"></i> Approve & Overwrite Live Data

                                        </button>

                                        <button type="submit" name="action" value="reject" class="btn btn-danger px-3 fw-bold" onclick="return confirm('මෙම දත්ත වෙනස්කම ප්‍රතික්ෂේප කිරීමට ඔබට විශ්වාසද?');">

                                            <i class="fa-solid fa-xmark me-1"></i> Reject Change

                                        </button>

                                    </div>

                                </div>

                            </form>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </div>

</div>



<?php require_once __DIR__ . '/../../includes/footer.php'; ?>