<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

Auth::requireAuth();

if (!Auth::isSuperAdmin()) {
    http_response_code(403);
    exit('ඔබට නව පරිශීලකයන් නිර්මාණය කිරීමට අවසර නැත. (403 Permission Denied)');
}

$db = Database::getConnection();
$errors = [];
$success = '';
$username = '';
$email = '';
$role = 'admin';
$status = 'active';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');
    $role = (string)($_POST['role'] ?? 'admin');
    $status = (string)($_POST['status'] ?? 'active');
    $csrfToken = (string)($_POST['csrf_token'] ?? '');

    if (!verifyCSRFToken($csrfToken)) {
        $errors[] = 'ආරක්ෂක token එක වලංගු නැහැ. කරුණාකර පිටුව refresh කර නැවත උත්සාහ කරන්න.';
    }
    if ($username === '' || mb_strlen($username) < 3 || mb_strlen($username) > 50) {
        $errors[] = 'Username එක අකුරු 3ත් 50ත් අතර විය යුතුයි.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
        $errors[] = 'වලංගු email address එකක් ඇතුළත් කරන්න.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password එක අවම වශයෙන් අකුරු/සංඛ්‍යා 8ක් විය යුතුයි.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Password දෙක සමාන නැහැ.';
    }
    if (!in_array($role, ['superadmin', 'admin', 'dayakaya'], true)) {
        $errors[] = 'තෝරාගත් user role එක වලංගු නැහැ.';
    }
    if (!in_array($status, ['active', 'pending', 'suspended'], true)) {
        $errors[] = 'තෝරාගත් account status එක වලංගු නැහැ.';
    }

    if (!$errors) {
        $check = $db->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
        $check->execute(['username' => $username, 'email' => $email]);
        if ($check->fetch()) {
            $errors[] = 'මෙම username එක හෝ email address එක දැනටමත් භාවිතා වෙනවා.';
        } else {
            try {
                $db->beginTransaction();
                $insert = $db->prepare('INSERT INTO users (username, email, password, role, status) VALUES (:username, :email, :password, :role, :status)');
                $insert->execute([
                    'username' => $username,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => $role,
                    'status' => $status,
                ]);
                $newUserId = (int)$db->lastInsertId();

                // Audit log columns follow the supplied temple_db.sql schema.
                $audit = $db->prepare('INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (:user_id, :action, :details, :ip_address)');
                $currentUser = Auth::user();
                $audit->execute([
                    'user_id' => isset($currentUser['id']) ? (int)$currentUser['id'] : null,
                    'action' => 'USER_CREATED',
                    'details' => 'Created user ID ' . $newUserId . ' (' . $username . '), role=' . $role . ', status=' . $status,
                    'ip_address' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
                ]);
                $db->commit();
                redirect(ADMIN_URL . '/users/index.php?created=1');
            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                error_log('User creation failed: ' . $e->getMessage());
                $errors[] = 'පරිශීලකයා සුරැකීමට නොහැකි වුණා. Database settings සහ error log පරීක්ෂා කරන්න.';
            }
        }
    }
}

$pageTitle = 'නව පරිශීලකයෙක් එකතු කරන්න';
$currentUser = Auth::user();
?>
<!doctype html>
<html lang="si">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 265px; }
        body { background: #f4f6f9; color: #212529; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; }
        .topbar { min-height: 66px; background: #202124; border-bottom: 3px solid #ffc107; }
        .brand { color: #ffc107; font-weight: 750; text-decoration: none; }
        .admin-layout { display: flex; min-height: calc(100vh - 66px); }
        .sidebar-wrap { flex: 0 0 var(--sidebar-width); width: var(--sidebar-width); background: #25282c; }
        .sidebar { min-height: calc(100vh - 66px); padding: 0 10px 18px; }
        .sidebar ul { list-style: none; margin: 0; padding: 0; }
        .sidebar .nav-link { color: #e1e5e9; display: block; margin: 5px 0; padding: 11px 13px; border-radius: 8px; text-decoration: none; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #212529; background: #ffc107; }
        .main-content { min-width: 0; flex: 1; padding: 28px; }
        .form-card { max-width: 950px; border: 0; border-radius: 14px; box-shadow: 0 5px 22px rgba(22,34,51,.07); }
        .form-label { font-weight: 650; }
        .required::after { content: ' *'; color: #dc3545; }
        @media (max-width: 850px) {
            .admin-layout { display: block; }
            .sidebar-wrap { width: 100%; }
            .sidebar { min-height: auto; padding-bottom: 10px; }
            .sidebar ul { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 2px 8px; }
            .main-content { padding: 18px 12px; }
        }
        @media (max-width: 480px) { .sidebar ul { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<header class="topbar d-flex align-items-center justify-content-between px-3 px-lg-4">
    <a class="brand d-flex align-items-center gap-2" href="<?= e(ADMIN_URL) ?>/dashboard.php">
        <i class="fa-solid fa-gopuram fs-3"></i><span><?= e(APP_NAME) ?></span>
    </a>
    <div class="d-flex align-items-center gap-2 text-white small">
        <span class="d-none d-sm-inline"><i class="fa-solid fa-user-shield text-warning me-1"></i><?= e((string)($currentUser['username'] ?? 'Super Admin')) ?></span>
        <a class="btn btn-sm btn-outline-warning" href="<?= e(ADMIN_URL) ?>/users/index.php"><i class="fa-solid fa-arrow-left me-1"></i> පරිශීලක ලැයිස්තුව</a>
    </div>
</header>
<div class="admin-layout">
    <aside class="sidebar-wrap">
        <div class="sidebar">
            <div class="text-center text-warning fw-bold border-bottom border-secondary py-3 mb-2"><i class="fa-solid fa-shield-halved me-2"></i>පරිපාලන පාලක පුවරුව</div>
            <ul>
                <li><a class="nav-link" href="<?= e(ADMIN_URL) ?>/dashboard.php"><i class="fa-solid fa-chart-line me-2"></i> Dashboard</a></li>
                <?php if (Auth::hasPermission('view_dayakayan')): ?><li><a class="nav-link" href="<?= e(ADMIN_URL) ?>/dayakayan/index.php"><i class="fa-solid fa-users me-2"></i> දායක නාමාවලිය</a></li><?php endif; ?>
                <?php if (Auth::hasPermission('approve_registrations')): ?><li><a class="nav-link" href="<?= e(ADMIN_URL) ?>/approvals/registrations.php"><i class="fa-solid fa-user-check me-2"></i> ලියාපදිංචි අනුමැතිය</a></li><?php endif; ?>
                <?php if (Auth::hasPermission('manage_donations')): ?><li><a class="nav-link" href="<?= e(ADMIN_URL) ?>/donations/index.php"><i class="fa-solid fa-hand-holding-dollar me-2"></i> දායක මුදල්</a></li><?php endif; ?>
                <?php if (Auth::hasPermission('manage_pinkam')): ?><li><a class="nav-link" href="<?= e(ADMIN_URL) ?>/pinkam/katina.php"><i class="fa-solid fa-dharmachakra me-2"></i> පිංකම් / කඨින</a></li><?php endif; ?>
                <?php if (Auth::hasPermission('manage_associations')): ?><li><a class="nav-link" href="<?= e(ADMIN_URL) ?>/master_data/associations.php"><i class="fa-solid fa-sitemap me-2"></i> සමිති / සංගම්</a></li><?php endif; ?>
                <?php if (Auth::hasPermission('view_reports')): ?><li><a class="nav-link" href="<?= e(ADMIN_URL) ?>/reports/dayaka_reports.php"><i class="fa-solid fa-file-invoice me-2"></i> වාර්තා</a></li><?php endif; ?>
                <li><a class="nav-link active" href="<?= e(ADMIN_URL) ?>/users/index.php"><i class="fa-solid fa-user-shield me-2"></i> පරිපාලකයන්</a></li>
                <li><a class="nav-link" href="<?= e(ADMIN_URL) ?>/system/audit_trail.php"><i class="fa-solid fa-list-check me-2"></i> Audit Logs</a></li>
            </ul>
        </div>
    </aside>
    <main class="main-content">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1"><i class="fa-solid fa-user-plus text-warning me-2"></i><?= e($pageTitle) ?></h1>
                <p class="text-secondary mb-0">පද්ධතියට නව user account එකක් ආරක්ෂිතව එකතු කරන්න.</p>
            </div>
            <span class="badge text-bg-dark p-2"><i class="fa-solid fa-lock me-1"></i> Super Admin only</span>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert"><div class="fw-bold mb-1">කරුණාකර පහත කරුණු නිවැරදි කරන්න:</div><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <section class="card form-card">
            <div class="card-header bg-white border-0 px-4 pt-4 pb-0"><h2 class="h5 fw-bold mb-0">Account Details</h2><p class="small text-secondary mt-1">* ලකුණ ඇති fields අනිවාර්යයි.</p></div>
            <div class="card-body p-4">
                <form method="post" action="" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required" for="username">Username</label>
                            <input class="form-control" id="username" name="username" value="<?= e($username) ?>" minlength="3" maxlength="50" required autocomplete="off">
                            <div class="form-text">අකුරු 3–50 අතර විය යුතුයි.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="email">Email address</label>
                            <input class="form-control" type="email" id="email" name="email" value="<?= e($email) ?>" maxlength="100" required autocomplete="off">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="password">Password</label>
                            <input class="form-control" type="password" id="password" name="password" minlength="8" required autocomplete="new-password">
                            <div class="form-text">අවම වශයෙන් අකුරු/සංඛ්‍යා 8ක්.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="confirm_password">Confirm password</label>
                            <input class="form-control" type="password" id="confirm_password" name="confirm_password" minlength="8" required autocomplete="new-password">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="role">User role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin — පරිපාලක</option>
                                <option value="superadmin" <?= $role === 'superadmin' ? 'selected' : '' ?>>Super Admin — ප්‍රධාන පරිපාලක</option>
                                <option value="dayakaya" <?= $role === 'dayakaya' ? 'selected' : '' ?>>Dayakaya — දායකයා</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="status">Account status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active — සක්‍රිය</option>
                                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending — බලාපොරොත්තුවෙන්</option>
                                <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended — අත්හිටුවා ඇත</option>
                            </select>
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                        <a class="btn btn-light border px-4" href="<?= e(ADMIN_URL) ?>/users/index.php"><i class="fa-solid fa-xmark me-1"></i> අවලංගු කරන්න</a>
                        <button class="btn btn-warning fw-bold px-4" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i> User එක සුරකින්න</button>
                    </div>
                </form>
            </div>
        </section>
        <p class="text-secondary small mt-3"><i class="fa-solid fa-circle-info me-1"></i> Password එක database එකේ hash කරලා පමණක් සුරකිනු ලැබේ.</p>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
