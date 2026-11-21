<?php
/**
 * Dayakaya Directory Master Index
 * Temple Data Management System (TDMS)
 * Version 1.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

Auth::requirePermission('view_dayakayan');

$db = Database::getConnection();

$peruwaFilter = trim((string)($_GET['peruwa_id'] ?? ''));
$search       = trim((string)($_GET['search'] ?? ''));

// Validate peruwa filter
if ($peruwaFilter !== '' && !ctype_digit($peruwaFilter)) {
    $peruwaFilter = '';
}

// Fetch Dayakaya records
$query = "
    SELECT
        d.*,
        p.name AS peruwa_name,
        f.address,
        d.job_type AS job_title
    FROM dayakayan d
    LEFT JOIN families f ON d.family_id = f.id
    LEFT JOIN peruwa p ON f.peruwa_id = p.id
    WHERE 1 = 1
";

$params = [];

if ($peruwaFilter !== '') {
    $query .= " AND p.id = :pid";
    $params['pid'] = (int)$peruwaFilter;
}

if ($search !== '') {
    $query .= "
        AND (
            d.full_name LIKE :name_search
            OR d.member_id LIKE :member_search
            OR d.phone LIKE :phone_search
            OR d.nic LIKE :nic_search
        )
    ";

    $searchTerm = '%' . $search . '%';

    $params['name_search']   = $searchTerm;
    $params['member_search'] = $searchTerm;
    $params['phone_search']  = $searchTerm;
    $params['nic_search']    = $searchTerm;
}

$query .= " ORDER BY d.id DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);

$dayakayan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Peruwa list
$peruwaList = $db
    ->query("SELECT id, name FROM peruwa ORDER BY name ASC")
    ->fetchAll(PDO::FETCH_ASSOC);

// Status labels and Bootstrap colors
$statusLabels = [
    'approved' => 'අනුමතයි',
    'pending'  => 'අපේක්ෂිතයි',
    'rejected' => 'ප්‍රතික්ෂේපයි',
    'inactive' => 'අක්‍රියයි',
];

$statusColors = [
    'approved' => 'success',
    'pending'  => 'warning text-dark',
    'rejected' => 'danger',
    'inactive' => 'secondary',
];

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
    .directory-page {
        background: #f5f6fa;
        min-height: 75vh;
    }

    .directory-title {
        font-weight: 700;
        color: #212529;
    }

    .directory-card {
        border: none;
        border-radius: 12px;
        overflow: hidden;
    }

    .directory-table th {
        white-space: nowrap;
        font-size: 0.9rem;
        padding: 14px 12px;
    }

    .directory-table td {
        padding: 13px 12px;
        vertical-align: middle;
    }

    .member-name {
        color: #212529;
        font-weight: 600;
        text-decoration: none;
    }

    .member-name:hover {
        color: #0d6efd;
        text-decoration: underline;
    }

    .member-id {
        font-size: 0.8rem;
        letter-spacing: 0.3px;
    }

    .filter-card {
        border: none;
        border-radius: 12px;
    }

    .table-empty {
        padding: 40px 15px !important;
    }

    @media (max-width: 767.98px) {
        .directory-page {
            padding: 16px !important;
        }

        .directory-heading {
            align-items: flex-start !important;
        }

        .directory-heading h3 {
            font-size: 1.15rem;
        }
    }

    @media print {
        .no-print,
        .admin-sidebar,
        .admin-sidebar *,
        nav,
        footer {
            display: none !important;
        }

        .admin-main {
            width: 100% !important;
        }

        .directory-page {
            background: white !important;
            padding: 0 !important;
        }
    }
</style>

<div class="admin-shell">

    <!-- Admin Sidebar -->
    <aside class="admin-sidebar no-print">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">

        <div class="directory-page p-3 p-md-4">

            <!-- Page Heading -->
            <div class="directory-heading d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

                <div>
                    <h3 class="directory-title m-0">
                        <i class="fa-solid fa-users text-warning me-2"></i>
                        දායක නාමාවලිය
                    </h3>

                    <div class="text-muted small mt-1">
                        Dayaka Directory — TDMS
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 no-print">

                    <?php if (Auth::hasPermission('add_dayakayan')): ?>

                        <a
                            href="<?= e(PUBLIC_URL) ?>/register.php"
                            class="btn btn-warning fw-bold"
                        >
                            <i class="fa-solid fa-user-plus me-1"></i>
                            නව දායකයෙකු ඇතුළත් කරන්න
                        </a>

                    <?php endif; ?>

                    <button
                        type="button"
                        onclick="window.print()"
                        class="btn btn-outline-dark"
                    >
                        <i class="fa-solid fa-print me-1"></i>
                        Print
                    </button>

                </div>

            </div>

            <!-- Search and Filters -->
            <form method="GET" class="card filter-card shadow-sm mb-4 no-print">

                <div class="card-body">

                    <div class="row g-3">

                        <div class="col-md-6">

                            <label for="search" class="form-label fw-semibold">
                                දායක තොරතුරු සොයන්න
                            </label>

                            <input
                                type="text"
                                id="search"
                                name="search"
                                class="form-control"
                                placeholder="නම, සාමාජික අංකය, දුරකථනය හෝ NIC..."
                                value="<?= e($search) ?>"
                            >

                        </div>

                        <div class="col-md-4">

                            <label for="peruwa_id" class="form-label fw-semibold">
                                පේරුව අනුව තෝරන්න
                            </label>

                            <select
                                name="peruwa_id"
                                id="peruwa_id"
                                class="form-select"
                            >

                                <option value="">සියලුම පේරු</option>

                                <?php foreach ($peruwaList as $p): ?>

                                    <option
                                        value="<?= (int)$p['id'] ?>"
                                        <?= $peruwaFilter === (string)$p['id'] ? 'selected' : '' ?>
                                    >
                                        <?= e($p['name']) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-md-2 d-flex align-items-end">

                            <button
                                type="submit"
                                class="btn btn-dark w-100"
                            >
                                <i class="fa-solid fa-magnifying-glass me-1"></i>
                                සොයන්න
                            </button>

                        </div>

                    </div>

                    <?php if ($search !== '' || $peruwaFilter !== ''): ?>

                        <div class="mt-3">

                            <a
                                href="<?= e(ADMIN_URL) ?>/dayakayan/index.php"
                                class="btn btn-sm btn-outline-secondary"
                            >
                                <i class="fa-solid fa-xmark me-1"></i>
                                Filters ඉවත් කරන්න
                            </a>

                        </div>

                    <?php endif; ?>

                </div>

            </form>

            <!-- Directory Results -->
            <div class="card directory-card shadow-sm">

                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">

                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid fa-list me-2 text-secondary"></i>
                        දායක ලැයිස්තුව
                    </h5>

                    <span class="badge bg-secondary">
                        මුළු ප්‍රතිඵල: <?= count($dayakayan) ?>
                    </span>

                </div>

                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-hover directory-table align-middle mb-0">

                            <thead class="table-dark">

                                <tr>
                                    <th>සාමාජික අංකය</th>
                                    <th>සම්පූර්ණ නම</th>
                                    <th>පේරුව</th>
                                    <th>දුරකථන අංකය</th>
                                    <th>රැකියාව</th>
                                    <th>තත්ත්වය</th>
                                    <th class="text-center">ක්‍රියා</th>
                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach ($dayakayan as $d): ?>

                                    <?php
                                    $status = strtolower((string)($d['status'] ?? ''));

                                    $statusLabel = $statusLabels[$status] ?? strtoupper($status ?: 'UNKNOWN');

                                    $statusColor = $statusColors[$status] ?? 'secondary';

                                    $viewUrl = e(ADMIN_URL)
                                        . '/dayakayan/view.php?id='
                                        . (int)$d['id'];
                                    ?>

                                    <tr>

                                        <!-- Member ID -->
                                        <td>
                                            <span class="badge bg-secondary member-id">
                                                <?= e((string)($d['member_id'] ?? '—')) ?>
                                            </span>
                                        </td>

                                        <!-- Clickable Member Name -->
                                        <td>

                                            <a
                                                href="<?= $viewUrl ?>"
                                                class="member-name"
                                                title="සම්පූර්ණ දායක විස්තර බලන්න"
                                            >
                                                <?= e((string)$d['full_name']) ?>

                                                <i class="fa-solid fa-arrow-up-right-from-square ms-1 small text-primary"></i>
                                            </a>

                                        </td>

                                        <!-- Peruwa -->
                                        <td>
                                            <?= e((string)($d['peruwa_name'] ?? '—')) ?>
                                        </td>

                                        <!-- Phone -->
                                        <td>
                                            <?php if (!empty($d['phone'])): ?>

                                                <a
                                                    href="tel:<?= e((string)$d['phone']) ?>"
                                                    class="text-decoration-none text-dark"
                                                >
                                                    <i class="fa-solid fa-phone text-success me-1"></i>
                                                    <?= e((string)$d['phone']) ?>
                                                </a>

                                            <?php else: ?>

                                                <span class="text-muted">—</span>

                                            <?php endif; ?>
                                        </td>

                                        <!-- Job -->
                                        <td>
                                            <?= e((string)($d['job_title'] ?: 'සඳහන් නැත')) ?>
                                        </td>

                                        <!-- Status -->
                                        <td>
                                            <span class="badge bg-<?= e($statusColor) ?>">
                                                <?= e($statusLabel) ?>
                                            </span>
                                        </td>

                                        <!-- View Action -->
                                        <td class="text-center">

                                            <a
                                                href="<?= $viewUrl ?>"
                                                class="btn btn-sm btn-outline-primary"
                                                title="දායක විස්තර බලන්න"
                                                aria-label="දායක විස්තර බලන්න"
                                            >
                                                <i class="fa-solid fa-eye"></i>
                                                View
                                            </a>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                                <?php if (empty($dayakayan)): ?>

                                    <tr>
                                        <td colspan="7" class="text-center table-empty">

                                            <i class="fa-solid fa-folder-open fa-3x text-muted mb-3"></i>

                                            <h6 class="text-muted">
                                                දායක තොරතුරු හමු නොවීය.
                                            </h6>

                                            <p class="text-muted small mb-3">
                                                සෙවුම් වචනය හෝ පේරුව වෙනස් කර නැවත උත්සාහ කරන්න.
                                            </p>

                                            <a
                                                href="<?= e(ADMIN_URL) ?>/dayakayan/index.php"
                                                class="btn btn-sm btn-outline-secondary"
                                            >
                                                සියලුම දායකයන් පෙන්වන්න
                                            </a>

                                        </td>
                                    </tr>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

                <div class="card-footer bg-white text-muted small">
                    දායකයෙකුගේ සම්පූර්ණ තොරතුරු බැලීමට ඔහුගේ නම හෝ View බොත්තම ක්ලික් කරන්න.
                </div>

            </div>

        </div>

    </main>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>