<?php
declare(strict_types=1);

/**
 * Admin Users & Permission Matrix Controller
 * Temple Dayaka Management System - Version 1.0 Blueprint
 */

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireSuperAdmin();

$db = Database::getConnection();
$message = '';
$error   = '';

// Permissions List Definition
$availablePermissions = [
    'view_dayakayan'     => 'දායකයින් නැරඹීම',
    'add_dayakayan'      => 'දායකයින් ඇතුළත් කිරීම',
    'edit_dayakayan'     => 'දායකයින් සංස්කරණය',
    'delete_dayakayan'   => 'දායකයින් ඉවත් කිරීම',
    'approve_register'   => 'ලියාපදිංචි අනුමත කිරීම',
    'manage_donations'   => 'මාසික දානය පාලනය',
    'manage_pinkam'      => 'පිංකම් කළමනාකරණය',
    'manage_associations'=> 'සමිති කළමනාකරණය',
    'view_reports'       => 'වර්තා නැරඹීම',
    'export_data'        => 'දත්ත Export කිරීම'
];

// Add / Update Admin User
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role     = $_POST['role'] ?? 'admin';
        $perms    = $_POST['permissions'] ?? [];

        if (empty($username) || empty($email)) {
            throw new Exception("Username සහ Email අනිවාර්ය වේ.");
        }

        $db->beginTransaction();

        // Check duplicate user
        $checkStmt = $db->prepare("SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1");
        $checkStmt->execute(['u' => $username, 'e' => $email]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            $userId = (int)$existing['id'];
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $db->prepare("UPDATE users SET password = :p, role = :r WHERE id = :id")->execute(['p' => $hash, 'r' => $role, 'id' => $userId]);
            } else {
                $db->prepare("UPDATE users SET role = :r WHERE id = :id")->execute(['r' => $role, 'id' => $userId]);
            }
        } else {
            if (empty($password)) {
                throw new Exception("නව පරිශීලකයෙකු සෑදීමට මුරපදයක් (Password) ඇතුළත් කරන්න.");
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password, role, status) VALUES (:u, :e, :p, :r, 'active')");
            $stmt->execute(['u' => $username, 'e' => $email, 'p' => $hash, 'r' => $role]);
            $userId = (int)$db->lastInsertId();
        }

        // Update Permissions Matrix
        $db->prepare("DELETE FROM admin_permissions WHERE user_id = :uid")->execute(['uid' => $userId]);
        if ($role === 'admin' && !empty($perms)) {
            $pStmt = $db->prepare("INSERT INTO admin_permissions (user_id, permission_key) VALUES (:uid, :pkey)");
            foreach ($perms as $pkey) {
                if (array_key_exists($pkey, $availablePermissions)) {
                    $pStmt->execute(['uid' => $userId, 'pkey' => $pkey]);
                }
            }
        }

        $db->commit();
        $message = "පරිශීලක ගිණුම සහ අවසරයන් (Permissions) සාර්ථකව යාවත්කාලීන විය!";

    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}

// Fetch Admin Users
$adminUsers = $db->query("SELECT * FROM users WHERE role IN ('superadmin', 'admin') ORDER BY id ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h3 class="fw-bold text-dark mb-0"><i class="fa-solid fa-users-gear text-warning me-2"></i>Admin පරිශීලකයින් සහ අවසරයන් (Permissions)</h3>
        <a href="<?= e(ADMIN_URL) ?>/dashboard.php" class="btn btn-outline-dark fw-bold btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> Dashboard</a>
    </div>

    <?php if ($message): ?><div class="alert alert-success fw-bold"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger fw-bold"><?= e($error) ?></div><?php endif; ?>

    <div class="row g-4">
        <!-- Add Admin Form -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-warning bg-gradient text-dark fw-bold py-3">
                    <i class="fa-solid fa-user-plus me-1"></i> පරිශීලකයෙකු ඇතුළත් / යාවත්කාලීන කිරීම
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Username *</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="වෙනස් කිරීමට පමණක් ටයිප් කරන්න">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Role</label>
                            <select name="role" class="form-select">
                                <option value="admin">Admin</option>
                                <option value="superadmin">Super Admin</option>
                            </select>
                        </div>

                        <label class="form-label fw-bold small text-dark d-block border-bottom pb-1 mb-2">Granular Permissions Matrix</label>
                        <div class="row g-2 mb-3">
                            <?php foreach ($availablePermissions as $key => $label): ?>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $key ?>" id="p_<?= $key ?>">
                                        <label class="form-check-label small fw-bold" for="p_<?= $key ?>"><?= e($label) ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="submit" class="btn btn-warning text-dark fw-bold w-100 py-2"><i class="fa-solid fa-save me-1"></i> සුරකින්න (Save User)</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white fw-bold py-3">පද්ධතියේ පරිශීලකයින්</div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($adminUsers as $u): ?>
                                <tr>
                                    <td class="fw-bold"><?= e($u['username']) ?><br><small class="text-muted"><?= e($u['email']) ?></small></td>
                                    <td><span class="badge bg-<?= $u['role'] === 'superadmin' ? 'warning text-dark' : 'info text-dark' ?>"><?= e(strtoupper($u['role'])) ?></span></td>
                                    <td><span class="badge bg-success"><?= e($u['status']) ?></span></td>
                                    <td><small><?= e($u['created_at']) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>