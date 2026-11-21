<?php
declare(strict_types=1);

/**
 * Monthly donation collection form.
 * Uses the existing monthly_donations and dayakayan tables.
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/audit_logger.php';

Auth::requireAuth();

if (!Auth::hasPermission('manage_donations')) {
    http_response_code(403);
    exit('<div style="font-family:Arial,sans-serif;max-width:700px;margin:60px auto;padding:24px;border:1px solid #dc3545;border-radius:10px;color:#842029;background:#f8d7da"><h2>ප්‍රවේශ අවසර නැත</h2><p>මෙම පිටුව භාවිතා කිරීමට <code>manage_donations</code> permission එක අවශ්‍යයි.</p><p><a href="' . e(ADMIN_URL) . '/dashboard.php">Dashboard වෙත ආපසු</a></p></div>');
}

$db = Database::getConnection();
$successMessage = '';
$errorMessage = '';

$methodLabels = [
    'cash' => 'මුදල් (Cash)',
    'bank_transfer' => 'බැංකු හුවමාරුව',
    'online' => 'Online',
    'other' => 'වෙනත්',
];

$selectedMember = (string)($_POST['dayakaya_id'] ?? '');
$amountValue = (string)($_POST['amount'] ?? '');
$dateValue = (string)($_POST['payment_date'] ?? date('Y-m-d'));
$methodValue = (string)($_POST['payment_method'] ?? 'cash');
$statusValue = (string)($_POST['status'] ?? 'paid');
$notesValue = (string)($_POST['notes'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? null;
    $dayakayaId = filter_input(INPUT_POST, 'dayakaya_id', FILTER_VALIDATE_INT);
    $amountRaw = trim($amountValue);
    $paymentDate = trim($dateValue);
    $paymentMethod = $methodValue;
    $status = $statusValue;
    $notes = trim($notesValue);
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
    } elseif (strlen($notes) > 500) {
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
                $selectedMember = '';
                $amountValue = '';
                $dateValue = date('Y-m-d');
                $methodValue = 'cash';
                $statusValue = 'paid';
                $notesValue = '';
            }
        } catch (Throwable $e) {
            error_log('Donation collection save error: ' . $e->getMessage());
            $errorMessage = 'දත්ත සුරැකීමට නොහැකි විය. Database error log පරීක්ෂා කරන්න.';
        }
    }
}

$members = $db->query("SELECT id, member_id, full_name FROM dayakayan WHERE status = 'approved' ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);

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
                    <h3 class="fw-bold mb-1"><i class="fa-solid fa-hand-holding-dollar text-warning me-2"></i>දායක මුදල් එකතු කිරීම</h3>
                    <p class="text-muted mb-0">අනුමත දායකයෙකුගේ මාසික දායක මුදල සටහන් කරන්න.</p>
                </div>
                <a href="<?= e(ADMIN_URL) ?>/donations/history.php" class="btn btn-outline-dark"><i class="fa-solid fa-clock-rotate-left me-2"></i>ගෙවීම් ඉතිහාසය</a>
            </div>

            <?php if ($successMessage !== ''): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= e($successMessage) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
            <?php if ($errorMessage !== ''): ?><div class="alert alert-danger" role="alert"><?= e($errorMessage) ?></div><?php endif; ?>

            <div class="row g-4">
                <div class="col-12 col-xl-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-dark text-white py-3"><h5 class="mb-0"><i class="fa-solid fa-circle-plus text-warning me-2"></i>නව ගෙවීමක් ඇතුළත් කරන්න</h5></div>
                        <div class="card-body p-4">
                            <?php if (!$members): ?>
                                <div class="alert alert-warning mb-0">දැනට අනුමත දායකයින් නොමැත. මුලින් දායක ලියාපදිංචිය අනුමත කරන්න.</div>
                            <?php else: ?>
                            <form method="post" action="<?= e(ADMIN_URL) ?>/donations/collection.php" class="row g-3">
                                <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                <div class="col-12">
                                    <label for="dayakaya_id" class="form-label fw-semibold">දායකයා <span class="text-danger">*</span></label>
                                    <select class="form-select" id="dayakaya_id" name="dayakaya_id" required>
                                        <option value="">දායකයෙකු තෝරන්න</option>
                                        <?php foreach ($members as $member): ?>
                                            <option value="<?= (int)$member['id'] ?>" <?= (string)$member['id'] === $selectedMember ? 'selected' : '' ?>><?= e($member['member_id']) ?> — <?= e($member['full_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="amount" class="form-label fw-semibold">මුදල (Rs.) <span class="text-danger">*</span></label>
                                    <input class="form-control" type="number" id="amount" name="amount" min="0.01" step="0.01" value="<?= e($amountValue) ?>" required placeholder="උදා: 1000.00">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="payment_date" class="form-label fw-semibold">ගෙවූ දිනය <span class="text-danger">*</span></label>
                                    <input class="form-control" type="date" id="payment_date" name="payment_date" value="<?= e($dateValue) ?>" required>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="payment_method" class="form-label fw-semibold">ගෙවීම් ක්‍රමය</label>
                                    <select class="form-select" id="payment_method" name="payment_method">
                                        <?php foreach ($methodLabels as $value => $label): ?><option value="<?= e($value) ?>" <?= $methodValue === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="status" class="form-label fw-semibold">තත්ත්වය</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="paid" <?= $statusValue === 'paid' ? 'selected' : '' ?>>ගෙවා ඇත (Paid)</option>
                                        <option value="pending" <?= $statusValue === 'pending' ? 'selected' : '' ?>>ගෙවීමට ඇත (Pending)</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="notes" class="form-label fw-semibold">සටහන්</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="500" placeholder="අමතර විස්තර (අත්‍යවශ්‍ය නොවේ)"><?= e($notesValue) ?></textarea>
                                </div>
                                <div class="col-12 d-flex flex-wrap gap-2">
                                    <button class="btn btn-warning fw-bold" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>දායක මුදල සුරකින්න</button>
                                    <a class="btn btn-outline-secondary" href="<?= e(ADMIN_URL) ?>/donations/index.php">Overview වෙත</a>
                                </div>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="fw-bold"><i class="fa-solid fa-circle-info text-warning me-2"></i>සටහන</h6>
                            <p class="text-muted mb-0">“ගෙවා ඇත” ලෙස තෝරාගත් වාර්තා පමණක් ගෙවූ මුදල් එකතුවට ඇතුළත් වේ. “ගෙවීමට ඇත” වාර්තා Pending එකතුවට ඇතුළත් වේ.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
