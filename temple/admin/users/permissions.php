<?php
declare(strict_types=1);

/**
 * Temple System — පරිශීලක අවසර කළමනාකරණය
 * File: admin/users/permissions.php
 *
 * Uses the existing admin_permissions table:
 *   user_id, permission_key
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

Auth::requireAuth();

if (!Auth::isSuperAdmin()) {
    http_response_code(403);
    exit('මෙම පිටුව Super Admin සඳහා පමණි.');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$db = Database::getConnection();

/** Escape HTML output. */
function permission_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Return true if a database table exists. */
function permission_table_exists(PDO $db, string $table): bool
{
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = ?'
    );
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

/** Return column names for a table. */
function permission_columns(PDO $db, string $table): array
{
    // Table names passed here are fixed internal identifiers, not user input.
    $stmt = $db->query('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '`');
    return array_map(
        static fn(array $row): string => (string)$row['Field'],
        $stmt->fetchAll(PDO::FETCH_ASSOC)
    );
}

/** Pick the first available column name from known schema variants. */
function permission_first_column(array $columns, array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) {
            return $candidate;
        }
    }
    return null;
}

$permissionGroups = [
    'දායක කළමනාකරණය' => [
        'view_dayakayan' => 'දායකයන්ගේ තොරතුරු බැලීම',
        'add_dayakayan' => 'නව දායකයෙකු එක් කිරීම',
        'edit_dayakayan' => 'දායක තොරතුරු සංස්කරණය',
        'delete_dayakayan' => 'දායක තොරතුරු මකා දැමීම',
        'approve_registrations' => 'නව ලියාපදිංචි කිරීම් අනුමත කිරීම',
        'approve_change_requests' => 'වෙනස්කම් ඉල්ලීම් අනුමත කිරීම',
    ],
    'පරිත්‍යාග සහ පින්කම්' => [
        'view_donations' => 'පරිත්‍යාග වාර්තා බැලීම',
        'manage_donations' => 'පරිත්‍යාග කළමනාකරණය',
        'view_pinkam' => 'පින්කම් තොරතුරු බැලීම',
        'manage_pinkam' => 'පින්කම් කළමනාකරණය',
        'manage_katina' => 'කඨින පින්කම් කළමනාකරණය',
    ],
    'මූලික දත්ත සහ වාර්තා' => [
        'manage_associations' => 'සංගම් කළමනාකරණය',
        'manage_job_types' => 'තනතුරු සහ සේවා වර්ග කළමනාකරණය',
        'view_reports' => 'වාර්තා බැලීම',
        'export_data' => 'දත්ත අපනයනය කිරීම',
    ],
    'පරිපාලනය සහ ආරක්ෂාව' => [
        'manage_users' => 'පරිශීලකයන් කළමනාකරණය',
        'manage_admins' => 'පරිපාලක ගිණුම් කළමනාකරණය',
        'manage_permissions' => 'අවසර කළමනාකරණය',
        'system_settings' => 'පද්ධති සැකසුම් වෙනස් කිරීම',
        'view_audit_logs' => 'ක්‍රියාකාරකම් වාර්තා බැලීම',
        'backup_restore' => 'උපස්ථ ගැනීම සහ ප්‍රතිස්ථාපනය',
        'final_approval' => 'අවසාන අනුමැතිය ලබාදීම',
    ],
];

$errors = [];
$success = isset($_GET['saved']) && $_GET['saved'] === '1'
    ? 'අවසර සාර්ථකව සුරැකුණා.'
    : '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string)$_SESSION['csrf_token'];

try {
    $permissionsTableExists = permission_table_exists($db, 'admin_permissions');
    $permissionColumns = $permissionsTableExists
        ? permission_columns($db, 'admin_permissions')
        : [];
    $permissionUserColumn = permission_first_column($permissionColumns, ['user_id', 'admin_user_id']);
    $permissionKeyColumn = permission_first_column($permissionColumns, ['permission_key', 'permission', 'permission_name']);

    $userColumns = permission_table_exists($db, 'users')
        ? permission_columns($db, 'users')
        : [];
    $userIdColumn = permission_first_column($userColumns, ['id', 'user_id']);
    $userNameColumn = permission_first_column($userColumns, ['username', 'full_name', 'name', 'email']);
    $userEmailColumn = permission_first_column($userColumns, ['email']);
    $userRoleColumn = permission_first_column($userColumns, ['role', 'user_role', 'role_name']);
    $userStatusColumn = permission_first_column($userColumns, ['status', 'account_status']);

    if (!$permissionsTableExists || !$permissionUserColumn || !$permissionKeyColumn) {
        $errors[] = 'admin_permissions වගුව හෝ අවශ්‍ය user_id / permission_key තීරු හමු නොවීය. දත්ත සමුදා සැකසුම් පරීක්ෂා කරන්න.';
    }
    if (!$userIdColumn) {
        $errors[] = 'users වගුවේ ID තීරුව හමු නොවීය.';
    }

    $users = [];
    if ($userIdColumn) {
        $select = ['`' . $userIdColumn . '` AS id'];
        $select[] = $userNameColumn ? '`' . $userNameColumn . '` AS display_name' : '`' . $userIdColumn . '` AS display_name';
        $select[] = $userEmailColumn ? '`' . $userEmailColumn . '` AS email' : "'' AS email";
        $select[] = $userRoleColumn ? '`' . $userRoleColumn . '` AS role_name' : "'' AS role_name";
        $select[] = $userStatusColumn ? '`' . $userStatusColumn . '` AS status_name' : "'' AS status_name";
        $users = $db->query(
            'SELECT ' . implode(', ', $select) .
            ' FROM `users` ORDER BY display_name ASC'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    $selectedUserId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT) ?: 0;
    $postedPermissionsAfterSave = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postedToken = (string)($_POST['csrf_token'] ?? '');
        if (!hash_equals($csrfToken, $postedToken)) {
            $errors[] = 'ආරක්ෂක ටෝකනය කල් ඉකුත් වී ඇත. පිටුව නැවත පූරණය කර උත්සාහ කරන්න.';
        }

        $postedUserId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT) ?: 0;
        $postedPermissions = $_POST['permissions'] ?? [];
        if (!is_array($postedPermissions)) {
            $postedPermissions = [];
        }

        $allowedPermissionKeys = [];
        foreach ($permissionGroups as $group) {
            foreach ($group as $key => $_label) {
                $allowedPermissionKeys[] = $key;
            }
        }
        $postedPermissions = array_values(array_unique(array_intersect(
            array_map('strval', $postedPermissions),
            $allowedPermissionKeys
        )));

        $validUserIds = array_map(static fn(array $u): int => (int)$u['id'], $users);
        if (!in_array($postedUserId, $validUserIds, true)) {
            $errors[] = 'කරුණාකර වලංගු පරිශීලකයෙකු තෝරන්න.';
        }

        if (!$errors && $permissionsTableExists && $permissionUserColumn && $permissionKeyColumn) {
            try {
                $db->beginTransaction();

                $delete = $db->prepare(
                    'DELETE FROM `admin_permissions` WHERE `' . $permissionUserColumn . '` = ?'
                );
                $delete->execute([$postedUserId]);

                if ($postedPermissions) {
                    $insert = $db->prepare(
                        'INSERT INTO `admin_permissions` (`' . $permissionUserColumn . '`, `' . $permissionKeyColumn . '`) VALUES (?, ?)'
                    );
                    foreach ($postedPermissions as $permissionKey) {
                        $insert->execute([$postedUserId, $permissionKey]);
                    }
                }

                $db->commit();
                header('Location: ' . e(ADMIN_URL) . '/users/permissions.php?user_id=' . $postedUserId . '&saved=1');
                exit;
            } catch (Throwable $exception) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                error_log('Permission save error: ' . $exception->getMessage());
                $errors[] = 'අවසර සුරැකීමට නොහැකි විය. දත්ත සමුදායේ වගු සහ සීමා පරීක්ෂා කරන්න.';
            }
        }

        $selectedUserId = $postedUserId;
    }

    $selectedUser = null;
    foreach ($users as $user) {
        if ((int)$user['id'] === $selectedUserId) {
            $selectedUser = $user;
            break;
        }
    }
    if (!$selectedUser && $users && $selectedUserId === 0) {
        $selectedUser = $users[0];
        $selectedUserId = (int)$selectedUser['id'];
    }

    $assignedPermissions = [];
    if ($selectedUser && $permissionsTableExists && $permissionUserColumn && $permissionKeyColumn) {
        $stmt = $db->prepare(
            'SELECT `' . $permissionKeyColumn . '` FROM `admin_permissions` WHERE `' . $permissionUserColumn . '` = ?'
        );
        $stmt->execute([$selectedUserId]);
        $assignedPermissions = array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
} catch (Throwable $exception) {
    error_log('Permissions page error: ' . $exception->getMessage());
    $errors[] = 'පිටුවේ දත්ත ලබාගැනීමේ දෝෂයක් ඇති විය. දත්ත සමුදා සැකසුම් පරීක්ෂා කරන්න.';
    $users = $users ?? [];
    $selectedUser = $selectedUser ?? null;
    $selectedUserId = $selectedUserId ?? 0;
    $assignedPermissions = $assignedPermissions ?? [];
}

require_once __DIR__ . '/../../includes/header.php';
?>
<style>
.permissions-shell{display:flex;align-items:stretch;min-height:calc(100vh - 100px);background:#f5f7fb}
.permissions-sidebar{flex:0 0 260px;width:260px;background:#212529;color:#fff}
.permissions-sidebar .sidebar{position:sticky;top:0;min-height:calc(100vh - 100px);padding:0 12px 16px}
.permissions-sidebar .nav{padding:12px 0;gap:4px}
.permissions-sidebar .nav-link{color:#dee2e6;border-radius:8px;padding:10px 12px}
.permissions-sidebar .nav-link:hover,.permissions-sidebar .nav-link.active{color:#212529;background:#ffc107}
.permissions-main{flex:1 1 auto;min-width:0;padding-bottom:24px}
.permissions-hero{position:relative;overflow:hidden;border-radius:18px;padding:26px;background:linear-gradient(120deg,#212529,#343a40 65%,#72551a);color:#fff}
.permissions-hero:after{content:"";position:absolute;width:220px;height:220px;border-radius:50%;right:-60px;top:-95px;background:rgba(255,193,7,.15)}
.permissions-hero>*{position:relative;z-index:1}
.permissions-card{border:0;border-radius:14px;box-shadow:0 4px 18px rgba(15,23,42,.06)}
.permission-group{overflow:hidden;border:1px solid #e9ecef;border-radius:12px;background:#fff}
.permission-group-title{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:14px 16px;background:#f8f9fa;border-bottom:1px solid #e9ecef;font-weight:700}
.permission-row{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:13px 16px;border-bottom:1px solid #f1f3f5}
.permission-row:last-child{border-bottom:0}
.permission-row label{margin:0;cursor:pointer}
.permission-row .form-check-input{width:2.5em;height:1.3em;cursor:pointer;flex:0 0 auto}
.permission-key{display:block;color:#6c757d;font-size:.75rem;margin-top:3px}
.permission-tools{display:flex;flex-wrap:wrap;gap:8px}
.permission-count{font-size:.8rem;color:#6c757d}
@media(max-width:767.98px){
 .permissions-shell{display:block}
 .permissions-sidebar{width:100%;flex:0 0 auto}
 .permissions-sidebar .sidebar{min-height:auto;position:static}
 .permissions-sidebar .nav{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));padding:12px 0;gap:4px}
 .permissions-sidebar .nav-link{font-size:.875rem}
 .permissions-hero{padding:20px}
 .permission-row{padding:12px}
}
@media print{
 .permissions-sidebar,.page-actions,.permissions-tools,.btn,header,footer,.navbar{display:none!important}
 .permissions-shell{display:block;min-height:0;background:#fff}
 .permissions-main{width:100%;padding:0!important}
 .container-fluid{padding:0!important;margin:0!important}
 .permissions-card{box-shadow:none!important;border:1px solid #ddd!important}
}
</style>

<div class="permissions-shell">
    <aside class="permissions-sidebar">
        <?php require __DIR__ . '/../../includes/sidebar.php'; ?>
    </aside>

    <main class="permissions-main">
        <div class="container-fluid my-4 px-3 px-md-4">
            <section class="permissions-hero mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <div class="small text-warning fw-bold mb-2"><i class="fa-solid fa-shield-halved me-1"></i> SUPER ADMIN CONTROL</div>
                        <h2 class="fw-bold mb-2">පරිශීලක අවසර කළමනාකරණය</h2>
                        <p class="mb-0 text-white-50">එක් එක් පරිශීලකයාට ලබාදිය යුතු පද්ධති ප්‍රවේශ අවසර මෙතැනින් තෝරන්න.</p>
                    </div>
                    <div class="page-actions d-flex flex-wrap gap-2">
                        <a class="btn btn-outline-light" href="<?= e(ADMIN_URL) ?>/users/index.php"><i class="fa-solid fa-arrow-left me-1"></i> පරිශීලක ලැයිස්තුව</a>
                        <button type="button" class="btn btn-warning fw-bold" onclick="window.print()"><i class="fa-solid fa-print me-1"></i> මුද්‍රණය</button>
                    </div>
                </div>
            </section>

            <?php if ($success !== ''): ?>
                <div class="alert alert-success alert-dismissible fade show" role="status">
                    <i class="fa-solid fa-circle-check me-2"></i><?= permission_e($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="වසන්න"></button>
                </div>
            <?php endif; ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger" role="alert"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= permission_e($error) ?></div>
            <?php endforeach; ?>

            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="card permissions-card h-100"><div class="card-body">
                        <div class="text-muted small">මුළු පරිශීලකයින්</div>
                        <div class="fs-2 fw-bold text-primary"><?= number_format(count($users ?? [])) ?></div>
                    </div></div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card permissions-card h-100"><div class="card-body">
                        <div class="text-muted small">අවසර කාණ්ඩ</div>
                        <div class="fs-2 fw-bold text-warning"><?= number_format(count($permissionGroups)) ?></div>
                    </div></div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card permissions-card h-100"><div class="card-body">
                        <div class="text-muted small">තෝරාගත් අවසර</div>
                        <div class="fs-2 fw-bold text-success" id="selectedPermissionTotal"><?= number_format(count($assignedPermissions ?? [])) ?></div>
                    </div></div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card permissions-card h-100"><div class="card-body">
                        <div class="text-muted small">තෝරාගත් පරිශීලකයා</div>
                        <div class="fs-5 fw-bold text-dark text-truncate"><?= $selectedUser ? permission_e($selectedUser['display_name']) : 'නැත' ?></div>
                    </div></div>
                </div>
            </div>

            <section class="card permissions-card mb-4">
                <div class="card-body p-3 p-md-4">
                    <form method="get" action="<?= e(ADMIN_URL) ?>/users/permissions.php" class="row g-3 align-items-end">
                        <div class="col-12 col-lg-8">
                            <label for="user_id" class="form-label fw-bold">පරිශීලකයෙකු තෝරන්න</label>
                            <select class="form-select" id="user_id" name="user_id" required>
                                <?php if (!$users): ?>
                                    <option value="">පරිශීලකයන් නොමැත</option>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?= (int)$user['id'] ?>" <?= (int)$user['id'] === (int)$selectedUserId ? 'selected' : '' ?>>
                                            <?= permission_e($user['display_name']) ?>
                                            <?= $user['email'] !== '' ? ' — ' . permission_e($user['email']) : '' ?>
                                            <?= $user['role_name'] !== '' ? ' (' . permission_e($user['role_name']) . ')' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-12 col-lg-4">
                            <button class="btn btn-dark w-100" type="submit"><i class="fa-solid fa-magnifying-glass me-1"></i> අවසර පෙන්වන්න</button>
                        </div>
                    </form>
                    <?php if ($selectedUser): ?>
                        <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                            <span class="badge bg-dark"><i class="fa-solid fa-user me-1"></i><?= permission_e($selectedUser['display_name']) ?></span>
                            <?php if ($selectedUser['role_name'] !== ''): ?><span class="badge bg-warning text-dark">භූමිකාව: <?= permission_e($selectedUser['role_name']) ?></span><?php endif; ?>
                            <?php if ($selectedUser['status_name'] !== ''): ?><span class="badge bg-secondary">තත්ත්වය: <?= permission_e($selectedUser['status_name']) ?></span><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <?php if ($selectedUser): ?>
                <form method="post" action="<?= e(ADMIN_URL) ?>/users/permissions.php?user_id=<?= (int)$selectedUserId ?>" id="permissionsForm">
                    <input type="hidden" name="csrf_token" value="<?= permission_e($csrfToken) ?>">
                    <input type="hidden" name="user_id" value="<?= (int)$selectedUserId ?>">

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h4 class="fw-bold mb-1">අවසර සැකසුම්</h4>
                            <p class="text-muted small mb-0">අවශ්‍ය ක්‍රියාකාරකම් පමණක් සක්‍රිය කරන්න.</p>
                        </div>
                        <div class="permission-tools permissions-tools">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllPermissions">සියල්ල තෝරන්න</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearAllPermissions">සියල්ල ඉවත් කරන්න</button>
                        </div>
                    </div>

                    <div class="row g-3">
                        <?php foreach ($permissionGroups as $groupName => $permissions): ?>
                            <?php $groupId = 'group_' . substr(sha1($groupName), 0, 10); ?>
                            <div class="col-12 col-xl-6">
                                <section class="permission-group h-100">
                                    <div class="permission-group-title">
                                        <span><i class="fa-solid fa-folder-open text-warning me-2"></i><?= permission_e($groupName) ?></span>
                                        <button type="button" class="btn btn-sm btn-outline-dark group-toggle" data-group="<?= permission_e($groupId) ?>">කාණ්ඩය තෝරන්න</button>
                                    </div>
                                    <div>
                                        <?php foreach ($permissions as $key => $label): ?>
                                            <div class="permission-row">
                                                <div>
                                                    <label for="perm_<?= permission_e($key) ?>" class="fw-semibold"><?= permission_e($label) ?></label>
                                                    <span class="permission-key"><?= permission_e($key) ?></span>
                                                </div>
                                                <input
                                                    class="form-check-input permission-checkbox"
                                                    type="checkbox"
                                                    role="switch"
                                                    id="perm_<?= permission_e($key) ?>"
                                                    name="permissions[]"
                                                    value="<?= permission_e($key) ?>"
                                                    data-group="<?= permission_e($groupId) ?>"
                                                    <?= in_array($key, $assignedPermissions, true) ? 'checked' : '' ?>
                                                >
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex flex-wrap justify-content-end gap-2 mt-4 mb-4 page-actions">
                        <a class="btn btn-outline-secondary" href="<?= e(ADMIN_URL) ?>/users/index.php">අවලංගු කරන්න</a>
                        <button class="btn btn-warning fw-bold px-4" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>අවසර සුරකින්න</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="card permissions-card"><div class="card-body text-center py-5">
                    <i class="fa-solid fa-users-slash fs-1 text-muted mb-3"></i>
                    <h5>පරිශීලකයන් නොමැත</h5>
                    <p class="text-muted mb-3">අවසර සැකසීමට පෙර පරිශීලක ගිණුමක් සාදන්න.</p>
                    <a class="btn btn-warning fw-bold" href="<?= e(ADMIN_URL) ?>/users/create.php"><i class="fa-solid fa-user-plus me-1"></i> නව පරිශීලකයෙක්</a>
                </div></div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = Array.from(document.querySelectorAll('.permission-checkbox'));
    const total = document.getElementById('selectedPermissionTotal');

    function updateCount() {
        if (total) {
            total.textContent = String(checkboxes.filter((checkbox) => checkbox.checked).length);
        }
    }

    document.getElementById('selectAllPermissions')?.addEventListener('click', function () {
        checkboxes.forEach((checkbox) => { checkbox.checked = true; });
        updateCount();
    });

    document.getElementById('clearAllPermissions')?.addEventListener('click', function () {
        checkboxes.forEach((checkbox) => { checkbox.checked = false; });
        updateCount();
    });

    document.querySelectorAll('.group-toggle').forEach((button) => {
        button.addEventListener('click', function () {
            const groupId = this.dataset.group;
            const groupBoxes = checkboxes.filter((checkbox) => checkbox.dataset.group === groupId);
            const shouldCheck = groupBoxes.some((checkbox) => !checkbox.checked);
            groupBoxes.forEach((checkbox) => { checkbox.checked = shouldCheck; });
            updateCount();
        });
    });

    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateCount));
    updateCount();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
