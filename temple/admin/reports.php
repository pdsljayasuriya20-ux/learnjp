<?php
declare(strict_types=1);

/**
 * System Reporting & CSV Export Engine
 * Temple Dayaka Management System - Version 1.0 Blueprint
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireAuth();
$db = Database::getConnection();

$peruwaId = !empty($_GET['peruwa_id']) ? (int)$_GET['peruwa_id'] : 0;
$export   = $_GET['export'] ?? '';

$query = "
    SELECT d.member_id, d.full_name, d.nic, d.dob, d.age, d.phone, p.name as peruwa_name, d.status
    FROM dayakayan d
    LEFT JOIN families f ON d.family_id = f.id
    LEFT JOIN peruwa p ON f.peruwa_id = p.id
    WHERE 1=1
";

$params = [];
if ($peruwaId > 0) {
    $query .= " AND p.id = :pid";
    $params['pid'] = $peruwaId;
}

$query .= " ORDER BY d.id DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$reportData = $stmt->fetchAll();

// CSV Export Processing
if ($export === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=dayaka_report_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Member ID', 'Full Name', 'NIC', 'DOB', 'Age', 'Phone', 'Peruwa', 'Status']);
    foreach ($reportData as $row) {
        fputcsv($output, [$row['member_id'], $row['full_name'], $row['nic'], $row['dob'], $row['age'], $row['phone'], $row['peruwa_name'], $row['status']]);
    }
    fclose($output);
    exit;
}

$peruwaList = $db->query("SELECT * FROM peruwa ORDER BY id ASC")->fetchAll();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h3 class="fw-bold text-dark mb-0"><i class="fa-solid fa-file-invoice text-warning me-2"></i>දායක වාර්තා (System Reports)</h3>
        <div>
            <a href="?peruwa_id=<?= $peruwaId ?>&export=csv" class="btn btn-success fw-bold btn-sm me-2"><i class="fa-solid fa-file-excel me-1"></i> Export Excel/CSV</a>
            <a href="<?= e(ADMIN_URL) ?>/dashboard.php" class="btn btn-outline-dark fw-bold btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> Dashboard</a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm mb-4 bg-light">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-8">
                    <select name="peruwa_id" class="form-select">
                        <option value="0">සියලුම පේරු (All Peruwa)</option>
                        <?php foreach ($peruwaList as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $peruwaId === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-warning text-dark fw-bold w-100">වාර්තාව ලබාගන්න (Generate)</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Member ID</th>
                        <th>නම</th>
                        <th>NIC</th>
                        <th>වයස</th>
                        <th>දුරකථන අංකය</th>
                        <th>පේරුව</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $r): ?>
                        <tr>
                            <td><span class="badge bg-warning text-dark fw-bold"><?= e($r['member_id']) ?></span></td>
                            <td class="fw-bold"><?= e($r['full_name']) ?></td>
                            <td><?= e($r['nic'] ?? 'N/A') ?></td>
                            <td><?= e((string)$r['age']) ?></td>
                            <td><?= e($r['phone']) ?></td>
                            <td><span class="badge bg-secondary"><?= e($r['peruwa_name'] ?? 'N/A') ?></span></td>
                            <td><span class="badge bg-<?= $r['status'] === 'approved' ? 'success' : 'warning text-dark' ?>"><?= e($r['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>