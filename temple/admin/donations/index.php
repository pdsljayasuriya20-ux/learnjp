<?php
declare(strict_types=1);

/**
 * Monthly donations overview and quick collection form.
 * Uses the existing monthly_donations and dayakayan schema.
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/audit_logger.php';

Auth::requireAuth();

if (!Auth::hasPermission('manage_donations')) {
    http_response_code(403);
    exit('<div style="font-family:Arial,sans-serif;max-width:700px;margin:60px auto;padding:24px;border:1px solid #dc3545;border-radius:10px;color:#842029;background:#f8d7da"><h2>ප්‍රවේශ අවසර නැත</h2><p>මෙම පිටුව භාවිතා කිරීමට ඔබගේ පරිපාලක ගිණුමට <code>manage_donations</code> permission එක ලබා දෙන්න.</p><p><a href="' . e(ADMIN_URL) . '/dashboard.php">Dashboard වෙත ආපසු</a></p></div>');
}

$db = Database::getConnection();
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? null;
    $dayakayaId = filter_input(INPUT_POST, 'dayakaya_id', FILTER_VALIDATE_INT);
    $amountRaw = trim((string)($_POST['amount'] ?? ''));
    $paymentDate = trim((string)($_POST['payment_date'] ?? date('Y-m-d')));
    $paymentMethod = (string)($_POST['payment_method'] ?? 'cash');
    $status = (string)($_POST['status'] ?? 'paid');
    $notes = trim((string)($_POST['notes'] ?? ''));
    $allowedMethods = ['cash', 'bank_transfer', 'online', 'other'];
    $allowedStatuses = ['paid', 'pending'];
    $validDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $paymentDate) === 1
        && checkdate((int)substr($paymentDate, 5, 2), (int)substr($paymentDate, 8, 2), (int)substr($paymentDate, 0, 4));

    if (!verifyCSRFToken(is_string($csrf) ? $csrf : null)) {
        $errorMessage = 'ආරක්ෂක token එක කල් ඉකුත් වී ඇත. කරුණාකර නැවත submit කරන්න.';
    } elseif (!$dayakayaId || $dayakayaId < 1) {
        $errorMessage = 'දායකයෙකු තෝරන්න.';
    } elseif ($amountRaw === '' || !is_numeric($amountRaw) || (float)$amountRaw <= 0) {
        $errorMessage = 'ශූන්‍යයට වඩා වැඩි නිවැරදි මුදලක් ඇතුළත් කරන්න.';
    } elseif (!$validDate || !in_array($paymentMethod, $allowedMethods, true) || !in_array($status, $allowedStatuses, true)) {
        $errorMessage = 'දිනය, ගෙවීම් ක්‍රමය හෝ තත්ත්වය නිවැරදි නැත.';
    } elseif (mb_strlen($notes, 'UTF-8') > 500) {
        $errorMessage = 'සටහන් අකුරු 500කට වඩා දිගු විය නොහැක.';
    } else {
        try {
            $memberCheck = $db->prepare("SELECT id, member_id, full_name FROM dayakayan WHERE id = :id AND status = 'approved' LIMIT 1");
            $memberCheck->execute(['id' => $dayakayaId]);
            $member = $memberCheck->fetch(PDO::FETCH_ASSOC);

            if (!$member) {
                $errorMessage = 'අනුමත කළ දායකයෙකු තෝරන්න.';
            } else {
                $amount = number_format((float)$amountRaw, 2, '.', '');
                $insert = $db->prepare('INSERT INTO monthly_donations (dayakaya_id, amount, payment_date, payment_method, status, notes) VALUES (:dayakaya_id, :amount, :payment_date, :payment_method, :status, :notes)');
                $insert->execute([
                    'dayakaya_id' => $dayakayaId,
                    'amount' => $amount,
                    'payment_date' => $paymentDate,
                    'payment_method' => $paymentMethod,
                    'status' => $status,
                    'notes' => $notes !== '' ? $notes : null,
                ]);

                $donationId = (int)$db->lastInsertId();
                AuditLogger::log('MONTHLY_DONATION_CREATED', 'monthly_donations', $donationId, null, [
                    'member_id' => $member['member_id'],
                    'amount' => $amount,
                    'payment_date' => $paymentDate,
                    'payment_method' => $paymentMethod,
                    'status' => $status,
                ]);
                $successMessage = 'දායක මුදල් වාර්තාව සාර්ථකව සුරැකිණි.';
            }
        } catch (Throwable $e) {
            error_log('Donation save error: ' . $e->getMessage());
            $errorMessage = 'දත්ත සුරැකීමට නොහැකි විය. Database error log පරීක්ෂා කරන්න.';
        }
    }
}

$month = (string)($_GET['month'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $month) || !checkdate((int)substr($month, 5, 2), 1, (int)substr($month, 0, 4))) {
    $month = date('Y-m');
}
$monthStart = $month . '-01';
$nextMonthStart = date('Y-m-d', strtotime($monthStart . ' +1 month'));

$statsStmt = $db->prepare("SELECT COUNT(*) AS total_count,
    COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) AS paid_total,
    COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) AS pending_total,
    COALESCE(SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END), 0) AS paid_count
    FROM monthly_donations WHERE payment_date >= :start_date AND payment_date < :end_date");
$statsStmt->execute(['start_date' => $monthStart, 'end_date' => $nextMonthStart]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: ['total_count' => 0, 'paid_total' => 0, 'pending_total' => 0, 'paid_count' => 0];

$recentStmt = $db->prepare("SELECT md.id, md.amount, md.payment_date, md.payment_method, md.status, md.notes,
    d.member_id, d.full_name
    FROM monthly_donations md
    INNER JOIN dayakayan d ON d.id = md.dayakaya_id
    WHERE md.payment_date >= :start_date AND md.payment_date < :end_date
    ORDER BY md.payment_date DESC, md.id DESC LIMIT 100");
$recentStmt->execute(['start_date' => $monthStart, 'end_date' => $nextMonthStart]);
$donations = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
$members = $db->query("SELECT id, member_id, full_name FROM dayakayan WHERE status = 'approved' ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);

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
@media(max-width:767.98px){
    .admin-shell{display:block}
    .admin-sidebar{width:100%}
    .admin-sidebar .sidebar{min-height:auto;position:static}
    .admin-sidebar .nav{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}
    .admin-sidebar .nav-link{font-size:.875rem}
}
</style>

<div class="admin-shell">
    <aside class="admin-sidebar">
        <?php require __DIR__ . '/../../includes/sidebar.php'; ?>
    </aside>

    <main class="admin-main">
        <div class="container-fluid my-4 px-3 px-md-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4 pb-3 border-bottom">
                <div>
                    <h3 class="fw-bold mb-1"><i class="fa-solid fa-hand-holding-dollar text-warning me-2"></i>මාසික දායක මුදල්</h3>
                    <p class="text-muted mb-0">දායක මුදල් සටහන් කිරීම සහ මාසික එකතුව පරීක්ෂා කිරීම</p>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <a href="<?= e(ADMIN_URL) ?>/donations/collection.php" class="btn btn-warning fw-bold"><i class="fa-solid fa-circle-plus me-2"></i>නව ගෙවීමක්</a>
                    <a href="<?= e(ADMIN_URL) ?>/donations/history.php" class="btn btn-outline-dark"><i class="fa-solid fa-clock-rotate-left me-2"></i>ගෙවීම් ඉතිහාසය</a>
                    <form method="get" class="d-flex align-items-center gap-2">
                        <label for="month" class="form-label mb-0 fw-semibold">මාසය</label>
                        <input type="month" id="month" name="month" class="form-control" value="<?= e($month) ?>" required>
                        <button class="btn btn-outline-dark" type="submit">පෙන්වන්න</button>
                    </form>
                </div>
            </div>

            <?php if ($successMessage !== ''): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert"><?= e($successMessage) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
            <?php endif; ?>
            <?php if ($errorMessage !== ''): ?><div class="alert alert-danger" role="alert"><?= e($errorMessage) ?></div><?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small fw-bold">ගෙවා ඇති මුළු මුදල</div><div class="fs-3 fw-bold text-success mt-1">Rs. <?= number_format((float)$stats['paid_total'], 2) ?></div></div></div></div>
                <div class="col-12 col-sm-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small fw-bold">ගෙවීමට නියමිත මුදල</div><div class="fs-3 fw-bold text-warning mt-1">Rs. <?= number_format((float)$stats['pending_total'], 2) ?></div></div></div></div>
                <div class="col-12 col-sm-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small fw-bold">ගෙවීම් වාර්තා</div><div class="fs-3 fw-bold mt-1"><?= number_format((int)$stats['total_count']) ?></div></div></div></div>
                <div class="col-12 col-sm-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small fw-bold">සම්පූර්ණ ගෙවීම්</div><div class="fs-3 fw-bold text-primary mt-1"><?= number_format((int)$stats['paid_count']) ?></div></div></div></div>
            </div>

            <div class="row g-4 align-items-start">
                <div class="col-12 col-xl-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-dark text-white py-3"><h5 class="mb-0"><i class="fa-solid fa-circle-plus text-warning me-2"></i>නව දායක මුදලක් ඇතුළත් කරන්න</h5></div>
                        <div class="card-body">
                            <?php if (!$members): ?>
                                <div class="alert alert-warning mb-0">දැනට අනුමත දායකයින් නොමැත. මුලින් දායක ලියාපදිංචිය අනුමත කරන්න.</div>
                            <?php else: ?>
                            <form method="post" action="<?= e(ADMIN_URL) ?>/donations/index.php?month=<?= e($month) ?>" class="row g-3">
                                <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                <div class="col-12">
                                    <label for="dayakaya_id" class="form-label fw-semibold">දායකයා <span class="text-danger">*</span></label>
                                    <select class="form-select" id="dayakaya_id" name="dayakaya_id" required>
                                        <option value="">දායකයෙකු තෝරන්න</option>
                                        <?php foreach ($members as $member): ?><option value="<?= (int)$member['id'] ?>"><?= e($member['member_id']) ?> — <?= e($member['full_name']) ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12"><label for="amount" class="form-label fw-semibold">මුදල (Rs.) <span class="text-danger">*</span></label><input class="form-control" type="number" id="amount" name="amount" min="0.01" step="0.01" required placeholder="උදා: 1000.00"></div>
                                <div class="col-12"><label for="payment_date" class="form-label fw-semibold">ගෙවූ දිනය <span class="text-danger">*</span></label><input class="form-control" type="date" id="payment_date" name="payment_date" value="<?= e(date('Y-m-d')) ?>" required></div>
                                <div class="col-12"><label for="payment_method" class="form-label fw-semibold">ගෙවීම් ක්‍රමය</label><select class="form-select" id="payment_method" name="payment_method"><?php foreach ($methodLabels as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select></div>
                                <div class="col-12"><label for="status" class="form-label fw-semibold">තත්ත්වය</label><select class="form-select" id="status" name="status"><option value="paid">ගෙවා ඇත (Paid)</option><option value="pending">ගෙවීමට ඇත (Pending)</option></select></div>
                                <div class="col-12"><label for="notes" class="form-label fw-semibold">සටහන්</label><textarea class="form-control" id="notes" name="notes" rows="2" maxlength="500" placeholder="අමතර විස්තර (අත්‍යවශ්‍ය නොවේ)"></textarea></div>
                                <div class="col-12"><button class="btn btn-warning fw-bold w-100" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>දායක මුදල සුරකින්න</button></div>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center"><h5 class="mb-0 fw-bold">මාසයේ ගෙවීම් ලැයිස්තුව</h5><span class="badge bg-secondary"><?= count($donations) ?> records</span></div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light"><tr><th>දිනය</th><th>දායකයා</th><th>ගෙවීම් ක්‍රමය</th><th class="text-end">මුදල</th><th>තත්ත්වය</th><th>සටහන්</th></tr></thead>
                                <tbody>
                                <?php if (!$donations): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-5"><i class="fa-regular fa-folder-open fs-2 d-block mb-2"></i>මෙම මාසයට ගෙවීම් වාර්තා නැත.</td></tr>
                                <?php else: foreach ($donations as $donation): ?>
                                    <tr>
                                        <td class="text-nowrap"><?= e(date('Y-m-d', strtotime((string)$donation['payment_date']))) ?></td>
                                        <td><div class="fw-semibold"><?= e($donation['full_name']) ?></div><small class="text-muted"><?= e($donation['member_id']) ?></small></td>
                                        <td><?= e($methodLabels[$donation['payment_method']] ?? $donation['payment_method']) ?></td>
                                        <td class="text-end text-nowrap fw-bold">Rs. <?= number_format((float)$donation['amount'], 2) ?></td>
                                        <td><?php if ($donation['status'] === 'paid'): ?><span class="badge bg-success">ගෙවා ඇත</span><?php else: ?><span class="badge bg-warning text-dark">Pending</span><?php endif; ?></td>
                                        <td><span title="<?= e($donation['notes'] ?? '') ?>"><?= e(mb_strimwidth((string)($donation['notes'] ?? ''), 0, 35, '…', 'UTF-8')) ?></span></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer bg-white text-muted small">මෙහි පෙන්වන්නේ <?= e($month) ?> මාසයේ වාර්තා පමණි.</div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
