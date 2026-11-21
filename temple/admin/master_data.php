<?php
declare(strict_types=1);

/**
 * Dynamic Master Data Controller (Associations & Pinkam)
 * Temple Dayaka Management System - Version 1.0 Blueprint
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireSuperAdmin();
$db = Database::getConnection();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $name = trim($_POST['name'] ?? '');

    if (!empty($name)) {
        if ($type === 'association') {
            $stmt = $db->prepare("INSERT INTO associations (name, status) VALUES (:name, 'active')");
            $stmt->execute(['name' => $name]);
            $message = "නව සමිතිය/සංගමය සාර්ථකව එකතු විය!";
        } elseif ($type === 'pinkam') {
            $stmt = $db->prepare("INSERT INTO pinkam_types (name, status) VALUES (:name, 'active')");
            $stmt->execute(['name' => $name]);
            $message = "නව පිංකම් වර්ගය සාර්ථකව එකතු විය!";
        }
    }
}

$associations = $db->query("SELECT * FROM associations ORDER BY name ASC")->fetchAll();
$pinkamTypes  = $db->query("SELECT * FROM pinkam_types ORDER BY name ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h3 class="fw-bold text-dark mb-0"><i class="fa-solid fa-layer-group text-warning me-2"></i>Master Data කළමනාකරණය</h3>
        <a href="<?= e(ADMIN_URL) ?>/dashboard.php" class="btn btn-outline-dark fw-bold btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> Dashboard</a>
    </div>

    <?php if ($message): ?><div class="alert alert-success fw-bold"><?= e($message) ?></div><?php endif; ?>

    <div class="row g-4">
        <!-- Associations Manager -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-warning bg-gradient text-dark fw-bold">සමිති / සංගම් (Associations)</div>
                <div class="card-body">
                    <form method="POST" action="" class="mb-3 d-flex gap-2">
                        <input type="hidden" name="type" value="association">
                        <input type="text" name="name" class="form-control" placeholder="නව සමිතියේ නම..." required>
                        <button type="submit" class="btn btn-dark fw-bold px-3">+ Add</button>
                    </form>
                    <ul class="list-group">
                        <?php foreach ($associations as $a): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= e($a['name']) ?>
                                <span class="badge bg-success">Active</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Pinkam Types Manager -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-dark text-white fw-bold">විශේෂ පිංකම් වර්ග (Pinkam Types)</div>
                <div class="card-body">
                    <form method="POST" action="" class="mb-3 d-flex gap-2">
                        <input type="hidden" name="type" value="pinkam">
                        <input type="text" name="name" class="form-control" placeholder="නව පිංකම් වර්ගය..." required>
                        <button type="submit" class="btn btn-warning text-dark fw-bold px-3">+ Add</button>
                    </form>
                    <ul class="list-group">
                        <?php foreach ($pinkamTypes as $p): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= e($p['name']) ?>
                                <span class="badge bg-success">Active</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>