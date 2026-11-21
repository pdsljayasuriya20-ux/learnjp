
<?php
/**
 * දහම් පාසල් ගුරු නාමාවලිය
 * Temple Data Management System (TDMS)
 */

require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';

// දැනට පවතින TDMS permission එක භාවිත කරයි.
Auth::requirePermission('view_dayakayan');

$db = Database::getConnection();

// සෙවුම් සහ පෙරහන්
$search = trim((string) ($_GET['search'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));

if (!in_array($statusFilter, ['', 'active', 'inactive'], true)) {
    $statusFilter = '';
}

// ගුරුවරුන් ලබාගැනීම
$sql = "
    SELECT
        t.id AS teacher_id,
        t.dayakaya_id,
        t.subject,
        t.teacher_role,
        t.start_date,
        t.status AS teacher_status,
        t.notes AS teacher_notes,
        t.qualifications, t.profile_photo,
        t.qualifications, t.profile_photo,
        d.member_id,
        d.full_name,
        d.phone,
        d.whatsapp_1,
        d.whatsapp_2,
        d.landline,
        d.nic,
        d.dob,
        d.age,
        d.email,
        d.family_id,
        f.address,
        p.name AS peruwa_name
    FROM daham_pasala_teachers t
    INNER JOIN dayakayan d
        ON d.id = t.dayakaya_id
    LEFT JOIN families f
        ON f.id = d.family_id
    LEFT JOIN peruwa p
        ON p.id = f.peruwa_id
    WHERE 1 = 1
";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            d.full_name LIKE :name_search
            OR d.member_id LIKE :member_search
            OR d.phone LIKE :phone_search
            OR d.nic LIKE :nic_search
            OR t.subject LIKE :subject_search
        )
    ";

    $keyword = '%' . $search . '%';

    $params['name_search'] = $keyword;
    $params['member_search'] = $keyword;
    $params['phone_search'] = $keyword;
    $params['nic_search'] = $keyword;
    $params['subject_search'] = $keyword;
}

if ($statusFilter !== '') {
    $sql .= " AND t.status = :teacher_status";
    $params['teacher_status'] = $statusFilter;
}

$sql .= " ORDER BY d.full_name ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);

$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// තත්ත්ව ලේබල්
$statusLabels = [
    'active' => 'සක්‍රීයයි',
    'inactive' => 'අක්‍රීයයි',
];

$statusColors = [
    'active' => 'success',
    'inactive' => 'secondary',
];

require_once __DIR__ . '/../../../includes/header.php';
?>

<style>
    .teacher-page {
        background: #f5f6fa;
        min-height: 75vh;
    }

    .teacher-title {
        font-weight: 700;
        color: #212529;
    }

    .teacher-card,
    .teacher-filter-card {
        border: none;
        border-radius: 12px;
        overflow: hidden;
    }

    .teacher-table th {
        white-space: nowrap;
        font-size: 0.9rem;
        padding: 14px 12px;
    }

    .teacher-table td {
        padding: 13px 12px;
        vertical-align: middle;
    }

    .teacher-name {
        color: #212529;
        font-weight: 600;
        text-decoration: none;
    }

    .teacher-name:hover {
        color: #0d6efd;
        text-decoration: underline;
    }

    .teacher-empty {
        padding: 40px 15px !important;
    }

    @media (max-width: 767.98px) {
        .teacher-page {
            padding: 16px !important;
        }

        .teacher-heading {
            align-items: flex-start !important;
        }

        .teacher-heading h3 {
            font-size: 1.15rem;
        }
    }

    @media print {
        .no-print,
        .admin-sidebar,
        nav,
        footer {
            display: none !important;
        }

        .admin-main {
            width: 100% !important;
        }

        .teacher-page {
            background: white !important;
            padding: 0 !important;
        }
    }
</style>

<div class="admin-shell">

    <!-- පවතින TDMS Sidebar Navigation එකම භාවිත කරයි -->
    <aside class="admin-sidebar no-print">
        <?php require_once __DIR__ . '/../../../includes/sidebar.php'; ?>
    </aside>

    <!-- ප්‍රධාන අන්තර්ගතය -->
    <main class="admin-main">

        <div class="teacher-page p-3 p-md-4">

            <!-- පිටු මාතෘකාව -->
            <div class="teacher-heading d-flex flex-wrap
                        justify-content-between align-items-center
                        gap-3 mb-4">

                <div>
                    <h3 class="teacher-title m-0">
                        <i class="fa-solid fa-chalkboard-user
                                  text-warning me-2"></i>
                        දහම් පාසල් ගුරුවරු
                    </h3>

                    <div class="text-muted small mt-1">
                        Daham Pasala Teacher Directory — TDMS
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 no-print">

                    <a
                        href="<?= e(ADMIN_URL) ?>/daham_pasala/teachers/create.php"
                        class="btn btn-warning fw-bold"
                    >
                        <i class="fa-solid fa-user-plus me-1"></i>
                        නව ගුරුවරයෙක් එකතු කරන්න
                    </a>
                
                    <button
                        type="button"
                        onclick="window.print()"
                        class="btn btn-outline-dark"
                    >
                        <i class="fa-solid fa-print me-1"></i>
                        මුද්‍රණය
                    </button>

                    <a href="<?= e(ADMIN_URL) ?>/daham_pasala/teachers/print.php?autoprint=1" target="_blank" class="btn btn-outline-primary"><i class="fa-solid fa-file-pdf me-1"></i> සියලු ගුරුවරු PDF</a>

                </div>
            </div>

            <!-- සෙවුම් සහ පෙරහන් -->
            <form
                method="GET"
                action="index.php"
                class="card teacher-filter-card shadow-sm mb-4 no-print"
            >
                <div class="card-body">

                    <div class="row g-3">

                        <div class="col-md-7">
                            <label for="search"
                                   class="form-label fw-semibold">
                                ගුරු තොරතුරු සොයන්න
                            </label>

                            <input
                                type="text"
                                id="search"
                                name="search"
                                class="form-control"
                                placeholder="නම, දායක අංකය, දුරකථනය, NIC හෝ විෂය..."
                                value="<?= e($search) ?>"
                            >
                        </div>

                        <div class="col-md-3">
                            <label for="status"
                                   class="form-label fw-semibold">
                                ගුරු තත්ත්වය
                            </label>

                            <select
                                name="status"
                                id="status"
                                class="form-select"
                            >
                                <option value="">සියලුම තත්ත්ව</option>

                                <option
                                    value="active"
                                    <?= $statusFilter === 'active'
                                        ? 'selected' : '' ?>
                                >
                                    සක්‍රීයයි
                                </option>

                                <option
                                    value="inactive"
                                    <?= $statusFilter === 'inactive'
                                        ? 'selected' : '' ?>
                                >
                                    අක්‍රීයයි
                                </option>
                            </select>
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <button
                                type="submit"
                                class="btn btn-dark w-100"
                            >
                                <i class="fa-solid fa-magnifying-glass
                                          me-1"></i>
                                සොයන්න
                            </button>
                        </div>

                    </div>

                    <?php if ($search !== '' || $statusFilter !== ''): ?>
                        <div class="mt-3">
                            <a
                                href="<?= e(ADMIN_URL) ?>/daham_pasala/teachers/index.php"
                                class="btn btn-sm btn-outline-secondary"
                            >
                                <i class="fa-solid fa-xmark me-1"></i>
                                පෙරහන් ඉවත් කරන්න
                            </a>
                        </div>
                    <?php endif; ?>

                </div>
            </form>

            <!-- ගුරු ලැයිස්තුව -->
            <div class="card teacher-card shadow-sm">

                <div class="card-header bg-white
                            d-flex flex-wrap
                            justify-content-between
                            align-items-center gap-2 py-3">

                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid fa-list
                                  me-2 text-secondary"></i>
                        ගුරු ලැයිස්තුව
                    </h5>

                    <span class="badge bg-secondary">
                        මුළු ගුරුවරුන්: <?= count($teachers) ?>
                    </span>

                </div>

                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-hover
                                      teacher-table align-middle mb-0">

                            <thead class="table-dark">
                                <tr>
                                    <th>දායක අංකය</th>
                                    <th>සම්පූර්ණ නම</th>
                                    <th>පේරුව</th>
                                    <th>දුරකථන අංකය</th>
                                    <th>විෂය</th>
                                    <th>තනතුර</th>
                                    <th>සේවා ආරම්භය (වර්ෂය / මාසය)</th>
                                    <th>තත්ත්වය</th>
                                    <th class="text-center">ක්‍රියා</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php if (empty($teachers)): ?>

                                <tr>
                                    <td colspan="8"
                                        class="text-center teacher-empty">

                                        <i class="fa-solid fa-folder-open
                                                  fa-3x text-muted mb-3"></i>

                                        <h6 class="text-muted">
                                            ගුරුවරුන් හමු නොවීය.
                                        </h6>

                                        <p class="text-muted small mb-3">
                                            සෙවුම් වචනය හෝ තත්ත්වය
                                            වෙනස් කර නැවත උත්සාහ කරන්න.
                                        </p>

                                        <a
                                            href="<?= e(ADMIN_URL) ?>/daham_pasala/teachers/index.php"
                                            class="btn btn-sm btn-outline-secondary"
                                        >
                                            සියලුම ගුරුවරු පෙන්වන්න
                                        </a>

                                    </td>
                                </tr>

                            <?php else: ?>

                                <?php foreach ($teachers as $teacher): ?>

                                    <?php
                                    $teacherStatus = (string) (
                                        $teacher['teacher_status'] ?? ''
                                    );

                                    $statusLabel =
                                        $statusLabels[$teacherStatus]
                                        ?? $teacherStatus;

                                    $statusColor =
                                        $statusColors[$teacherStatus]
                                        ?? 'secondary';

                                    $teacherId = (int) $teacher['teacher_id'];
                                    ?>

                                    <tr>

                                        <!-- දායක අංකය -->
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?= e(
                                                    (string) (
                                                        $teacher['member_id'] ?? '—'
                                                    )
                                                ) ?>
                                            </span>
                                        </td>

                                        <!-- ගුරුවරයාගේ නම -->
                                        <td>
                                            <a
                                                href="<?= e(ADMIN_URL) ?>/daham_pasala/teachers/view.php?id=<?= $teacherId ?>"
                                                class="teacher-name"
                                            >
                                                <?= e(
                                                    (string) $teacher['full_name']
                                                ) ?>
                                            </a>
                                        </td>

                                        <!-- පේරුව -->
                                        <td>
                                            <?= e(
                                                (string) (
                                                    $teacher['peruwa_name'] ?? '—'
                                                )
                                            ) ?>
                                        </td>

                                        <!-- දුරකථනය -->
                                        <td>
                                            <?php if (!empty($teacher['phone'])): ?>
                                                <a
                                                    href="tel:<?= e(
                                                        (string) $teacher['phone']
                                                    ) ?>"
                                                    class="text-decoration-none text-dark"
                                                >
                                                    <i class="fa-solid fa-phone
                                                              text-success me-1"></i>
                                                    <?= e(
                                                        (string) $teacher['phone']
                                                    ) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- විෂය -->
                                        <td>
                                            <?= e(
                                                (string) (
                                                    $teacher['subject'] ?: 'සඳහන් නැත'
                                                )
                                            ) ?>
                                        </td>

                                        <!-- තනතුර -->
                                        <td>
                                            <?= e(
                                                (string) (
                                                    $teacher['teacher_role']
                                                    ?: 'සඳහන් නැත'
                                                )
                                            ) ?>
                                        </td>

                                        <!-- සේවා ආරම්භක වර්ෂය සහ මාසය -->
                                        <td>
                                            <?php if (!empty($teacher['start_date'])): ?>
                                                <?= e(date('Y / F', strtotime($teacher['start_date']))) ?>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- තත්ත්වය -->
                                        <td>
                                            <span class="badge bg-<?= e(
                                                $statusColor
                                            ) ?>">
                                                <?= e($statusLabel) ?>
                                            </span>
                                        </td>

                                        <!-- ක්‍රියා -->
                                        <td class="text-center">
                                            <div class="d-flex gap-1
                                                        justify-content-center">

                                                <a
                                                    href="<?= e(ADMIN_URL) ?>/daham_pasala/teachers/view.php?id=<?= $teacherId ?>"
                                                    class="btn btn-sm btn-outline-primary"
                                                    title="ගුරු විස්තර බලන්න"
                                                >
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>

                                                <a
                                                    href="<?= e(ADMIN_URL) ?>/daham_pasala/teachers/edit.php?id=<?= $teacherId ?>"
                                                    class="btn btn-sm btn-outline-dark"
                                                    title="ගුරු විස්තර සංස්කරණය"
                                                >
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                                <a href="<?= e(ADMIN_URL) ?>/daham_pasala/teachers/print.php?id=<?= $teacherId ?>&amp;autoprint=1" target="_blank" class="btn btn-sm btn-outline-danger" title="PDF වාර්තාව">
                                                    <i class="fa-solid fa-file-pdf"></i>
                                                </a>

                                            </div>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                            </tbody>
                        </table>

                    </div>
                </div>

                <div class="card-footer bg-white text-muted small">
                    ගුරුවරයෙකුගේ සම්පූර්ණ තොරතුරු බැලීමට
                    ඔහුගේ හෝ ඇයගේ නම හෝ View බොත්තම ක්ලික් කරන්න.
                </div>

            </div>

        </div>
    </main>

</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
