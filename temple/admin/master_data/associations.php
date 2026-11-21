<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

Auth::requireAuth();
if (!Auth::hasPermission('manage_associations')) {
    http_response_code(403);
    exit('ඔබට සමිති / සංගම් කළමනාකරණයට අවසර නැත.');
}

$db = Database::getConnection();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? 'add');

    if (!verifyCSRFToken(isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : null)) {
        $error = 'ආරක්ෂක token එක වලංගු නැත. නැවත උත්සාහ කරන්න.';
    } elseif ($action === 'add') {
        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));

        if ($name === '') {
            $error = 'සමිති / සංගම් නම ඇතුළත් කරන්න.';
        } elseif (mb_strlen($name) > 150) {
            $error = 'සමිති / සංගම් නම අක්ෂර 150කට වඩා දිගු විය නොහැක.';
        } else {
            try {
                $check = $db->prepare('SELECT id FROM associations WHERE name = ? LIMIT 1');
                $check->execute([$name]);

                if ($check->fetchColumn()) {
                    $error = 'මෙම නම දැනටමත් තිබේ.';
                } else {
                    $ins = $db->prepare('INSERT INTO associations (name, description) VALUES (?, ?)');
                    $ins->execute([$name, $description === '' ? null : $description]);
                    $success = 'සමිති / සංගමය සාර්ථකව එකතු කරන ලදී.';
                }
            } catch (Throwable $e) {
                error_log('Association save error: ' . $e->getMessage());
                $error = 'දත්ත සුරැකීමට නොහැකි විය. Error log පරීක්ෂා කරන්න.';
            }
        }
    } elseif ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            $error = 'වලංගු සමිති / සංගම් වාර්තාවක් තෝරන්න.';
        } else {
            try {
                // Do not delete an association while dayaka links still exist.
                $check = $db->prepare('SELECT COUNT(*) FROM dayaka_associations WHERE association_id = ?');
                $check->execute([$id]);
                $memberCount = (int)$check->fetchColumn();

                if ($memberCount > 0) {
                    $error = 'මෙම සමිතියට දායකයින් සම්බන්ධ කර ඇත. පළමුව එම සම්බන්ධතා ඉවත් කරන්න.';
                } else {
                    $del = $db->prepare('DELETE FROM associations WHERE id = ?');
                    $del->execute([$id]);
                    $success = $del->rowCount() ? 'සමිති / සංගමය ඉවත් කරන ලදී.' : 'වාර්තාව හමු නොවීය.';
                }
            } catch (Throwable $e) {
                error_log('Association delete error: ' . $e->getMessage());
                $error = 'වාර්තාව ඉවත් කිරීමට නොහැකි විය. Error log පරීක්ෂා කරන්න.';
            }
        }
    } else {
        $error = 'වලංගු ක්‍රියාවක් තෝරා නැත.';
    }
}

try {
    $rows = $db->query(
        'SELECT a.id, a.name, a.description, a.created_at,
                COUNT(da.dayakaya_id) AS member_count
         FROM associations a
         LEFT JOIN dayaka_associations da ON da.association_id = a.id
         GROUP BY a.id, a.name, a.description, a.created_at
         ORDER BY a.name'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Association list error: ' . $e->getMessage());
    $rows = [];
    $error = $error ?: 'සමිති / සංගම් වාර්තා ලබාගැනීමට නොහැකි විය.';
}

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
.association-card{border:0;border-radius:14px;overflow:hidden}
.association-card .card-header{border-bottom:1px solid #edf0f2}
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
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h3 class="fw-bold mb-1"><i class="fa-solid fa-sitemap text-warning me-2"></i>සමිති / සංගම් පාලනය</h3>
                    <p class="text-muted mb-0">දායකයින් සම්බන්ධ කළ හැකි සමිති සහ සංගම් කළමනාකරණය</p>
                </div>
                <span class="badge text-bg-dark px-3 py-2">සමිති ගණන: <?= count($rows) ?></span>
            </div>

            <?php if ($success !== ''): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= e($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= e($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4 align-items-start">
                <div class="col-12 col-xl-4">
                    <div class="card association-card shadow-sm">
                        <div class="card-header bg-dark text-white py-3">
                            <strong><i class="fa-solid fa-circle-plus me-2 text-warning"></i>නව සමිතියක් එකතු කරන්න</strong>
                        </div>
                        <div class="card-body p-3 p-md-4">
                            <form method="post" class="row g-3">
                                <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                <input type="hidden" name="action" value="add">
                                <div class="col-12">
                                    <label class="form-label" for="association-name">සමිති / සංගම් නම <span class="text-danger">*</span></label>
                                    <input id="association-name" class="form-control" name="name" maxlength="150" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="association-description">විස්තර</label>
                                    <textarea id="association-description" class="form-control" name="description" rows="4" maxlength="2000" placeholder="සමිති / සංගමය පිළිබඳ විස්තර (විකල්පයි)"></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-warning fw-bold w-100"><i class="fa-solid fa-floppy-disk me-2"></i>සුරකින්න</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-8">
                    <div class="card association-card shadow-sm">
                        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <strong><i class="fa-solid fa-list me-2 text-warning"></i>ලියාපදිංචි සමිති / සංගම්</strong>
                            <span class="badge bg-secondary"><?= count($rows) ?> වාර්තා</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>නම</th>
                                        <th>විස්තර</th>
                                        <th class="text-center text-nowrap">සම්බන්ධිත දායකයින්</th>
                                        <th class="text-nowrap">එකතු කළ දිනය</th>
                                        <th class="text-end">ක්‍රියා</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (!$rows): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-5"><i class="fa-solid fa-folder-open d-block fs-2 mb-2"></i>සමිති / සංගම් නැත.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $r): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= e((string)$r['name']) ?></td>
                                            <td style="min-width:160px;max-width:280px;white-space:normal"><?= e((string)($r['description'] ?? '—')) ?></td>
                                            <td class="text-center"><span class="badge bg-info text-dark"><?= (int)$r['member_count'] ?></span></td>
                                            <td class="text-nowrap"><?= e((string)($r['created_at'] ?? '—')) ?></td>
                                            <td class="text-end">
                                                <form method="post" class="d-inline" onsubmit="return confirm('මෙම සමිතිය ඉවත් කරන්නද?');">
                                                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" <?= (int)$r['member_count'] > 0 ? 'disabled title="දායක සම්බන්ධතා ඇති බැවින් පළමුව ඒවා ඉවත් කරන්න"' : 'title="සමිතිය ඉවත් කරන්න"' ?> aria-label="සමිතිය ඉවත් කරන්න">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer bg-white text-muted small">දායකයින් සම්බන්ධ කර ඇති සමිතියක් අහම්බෙන් මකා දැමීම වැළැක්වීමට ඉවත් කිරීම අක්‍රිය කර ඇත.</div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
