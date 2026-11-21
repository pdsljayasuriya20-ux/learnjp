```php
<?php
declare(strict_types=1);

/**
 * Dayakaya Full Profile View
 * Temple Data Management System (TDMS)
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

Auth::requirePermission('view_dayakayan');

$db = Database::getConnection();

// Get Dayakaya ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    http_response_code(400);
    exit('වලංගු දායක අංකයක් ලබාදී නැත.');
}

// Fetch member details and family information
$sql = "
    SELECT
        d.*,
        f.family_code,
        f.address,
        p.name AS peruwa_name
    FROM dayakayan d
    LEFT JOIN families f ON d.family_id = f.id
    LEFT JOIN peruwa p ON f.peruwa_id = p.id
    WHERE d.id = :id
    LIMIT 1
";

$stmt = $db->prepare($sql);
$stmt->execute(['id' => $id]);
$dayakaya = $stmt->fetch();

if (!$dayakaya) {
    http_response_code(404);
    exit('අදාළ දායකයාගේ තොරතුරු හමු නොවීය.');
}

// Display helpers
function displayValue($value, string $empty = 'සඳහන් කර නැත'): string
{
    if ($value === null || trim((string)$value) === '') {
        return $empty;
    }

    return e((string)$value);
}

function translateValue($value, array $translations): string
{
    $key = strtolower(trim((string)$value));

    return isset($translations[$key])
        ? $translations[$key]
        : displayValue($value);
}

$status = strtolower((string)($dayakaya['status'] ?? ''));

$statusLabels = [
    'approved' => 'අනුමතයි',
    'pending'  => 'අනුමැතිය අපේක්ෂාවෙන්',
    'rejected' => 'ප්‍රතික්ෂේප කර ඇත',
    'inactive' => 'අක්‍රියයි',
];

$statusColors = [
    'approved' => 'success',
    'pending'  => 'warning',
    'rejected' => 'danger',
    'inactive' => 'secondary',
];

$maritalLabels = [
    'single'  => 'අවිවාහක',
    'married' => 'විවාහක',
];

$jobLabels = [
    'yes'     => 'රැකියාවක නිරත වේ',
    'no'      => 'රැකියාවක නිරත නොවේ',
    'self'    => 'ස්වයං රැකියා',
    'retired' => 'විශ්‍රාමික',
];

$yesNoLabels = [
    'yes' => 'ඔව්',
    'no'  => 'නැත',
];

$statusLabel = $statusLabels[$status] ?? 'වෙනත්';
$statusColor = $statusColors[$status] ?? 'secondary';

// Calculate age from DOB if available
$age = $dayakaya['age'] ?? null;

if (!empty($dayakaya['dob'])) {
    try {
        $birthDate = new DateTime((string)$dayakaya['dob']);
        $today = new DateTime('today');

        if ($birthDate <= $today) {
            $age = $birthDate->diff($today)->y;
        }
    } catch (Throwable $e) {
        // Retain stored age if DOB is invalid.
    }
}

// Profile image URL.
// Assumes profile_photo contains a path relative to the project root,
// for example: public/uploads/profile.jpg
$photoUrl = null;
$photoPath = trim((string)($dayakaya['profile_photo'] ?? ''));

if (
    $photoPath !== ''
    && !preg_match('/[\x00-\x1F\x7F]/', $photoPath)
    && !preg_match('/^(?:[a-z][a-z0-9+.-]*:|\/\/)/i', $photoPath)
    && !str_contains($photoPath, '\\')
    && !in_array('..', explode('/', $photoPath), true)
) {
    $photoUrl = rtrim(APP_URL, '/') . '/' . ltrim($photoPath, '/');
}

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
    .profile-page {
        background: #f5f6fa;
        min-height: 75vh;
        padding: 24px;
    }

    .profile-heading {
        font-weight: 700;
        color: #212529;
    }

    .profile-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        margin-bottom: 22px;
    }

    .profile-card .card-header {
        background: #212529;
        color: #ffc107;
        font-weight: 700;
        padding: 14px 18px;
    }

    .profile-card .card-body {
        padding: 22px;
    }

    .profile-photo {
        width: 145px;
        height: 145px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #ffc107;
        background: #f1f3f5;
    }

    .profile-placeholder {
        width: 145px;
        height: 145px;
        border-radius: 50%;
        background: #e9ecef;
        border: 4px solid #ffc107;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: auto;
        color: #6c757d;
        font-size: 55px;
    }

    .info-item {
        height: 100%;
        padding: 12px 14px;
        border: 1px solid #edf0f2;
        border-radius: 9px;
        background: #fff;
        overflow-wrap: anywhere;
    }

    .info-label {
        font-size: 12px;
        color: #6c757d;
        margin-bottom: 5px;
    }

    .info-value {
        font-weight: 600;
        color: #212529;
        white-space: pre-wrap;
    }

    .member-number {
        display: inline-block;
        background: #fff3cd;
        color: #664d03;
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: 700;
    }

    @media print {
        .no-print,
        nav,
        footer,
        .sidebar {
            display: none !important;
        }

        .profile-page {
            padding: 0;
            background: #fff;
        }

        .profile-card {
            box-shadow: none;
            border: 1px solid #ddd;
            break-inside: avoid;
        }

        .profile-card .card-header {
            background: #eee !important;
            color: #222 !important;
            print-color-adjust: exact;
        }

        .col-md-2 {
            display: none !important;
        }

        .col-md-10 {
            width: 100% !important;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">

        <!-- Admin Sidebar -->
        <div class="col-md-2 p-0 no-print">
            <?php require __DIR__ . '/../../includes/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 p-0">

            <div class="profile-page">

                <!-- Page Heading -->
                <div class="d-flex flex-wrap justify-content-between
                            align-items-center gap-3 mb-4">

                    <div>
                        <h3 class="profile-heading mb-1">
                            <i class="fa-solid fa-address-card text-warning me-2"></i>
                            දායක තොරතුරු
                        </h3>

                        <div class="text-muted small">
                            Dayakaya Profile / Member Details
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 no-print">

                        <a href="index.php"
                           class="btn btn-outline-secondary">
                            <i class="fa-solid fa-arrow-left me-1"></i>
                            නාමාවලියට ආපසු
                        </a>

                        <?php if (Auth::hasPermission('edit_dayakayan')): ?>
                            <a href="edit.php?id=<?= (int)$dayakaya['id'] ?>"
                               class="btn btn-warning fw-bold">
                                <i class="fa-solid fa-pen-to-square me-1"></i>
                                සංස්කරණ ඉල්ලීම
                            </a>
                        <?php endif; ?>

                        <button type="button"
                                onclick="window.print()"
                                class="btn btn-dark">
                            <i class="fa-solid fa-print me-1"></i>
                            Print
                        </button>

                    </div>
                </div>

                <!-- Profile Summary -->
                <div class="card profile-card">
                    <div class="card-body">
                        <div class="row align-items-center g-4">

                            <div class="col-md-3 text-center">

                                <?php if ($photoUrl !== null): ?>

                                    <img
                                        src="<?= e($photoUrl) ?>"
                                        alt="දායක ඡායාරූපය"
                                        class="profile-photo"
                                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                                    >

                                    <div class="profile-placeholder"
                                         style="display:none;">
                                        <i class="fa-solid fa-user"></i>
                                    </div>

                                <?php else: ?>

                                    <div class="profile-placeholder">
                                        <i class="fa-solid fa-user"></i>
                                    </div>

                                <?php endif; ?>

                            </div>

                            <div class="col-md-6">

                                <h4 class="fw-bold mb-2">
                                    <?= displayValue($dayakaya['full_name']) ?>
                                </h4>

                                <p class="mb-3 text-muted">
                                    <i class="fa-solid fa-id-card me-1"></i>
                                    සාමාජික අංකය:
                                    <span class="member-number">
                                        <?= displayValue($dayakaya['member_id']) ?>
                                    </span>
                                </p>

                                <p class="mb-2">
                                    <i class="fa-solid fa-phone text-success me-2"></i>
                                    <?= displayValue($dayakaya['phone']) ?>
                                </p>

                                <p class="mb-2">
                                    <i class="fa-solid fa-location-dot text-danger me-2"></i>
                                    පේරුව:
                                    <strong>
                                        <?= displayValue($dayakaya['peruwa_name'] ?? null) ?>
                                    </strong>
                                </p>

                                <p class="mb-0">
                                    <i class="fa-solid fa-users text-primary me-2"></i>
                                    පවුල් අංකය:
                                    <strong>
                                        <?= displayValue($dayakaya['family_code'] ?? null) ?>
                                    </strong>
                                </p>

                            </div>

                            <div class="col-md-3 text-md-end">

                                <span class="badge bg-<?= e($statusColor) ?>
                                             <?= $status === 'pending' ? 'text-dark' : '' ?>
                                             fs-6 px-3 py-2">
                                    <?= e($statusLabel) ?>
                                </span>

                                <div class="small text-muted mt-3">
                                    ලියාපදිංචි කළ දිනය
                                </div>

                                <div class="fw-semibold">
                                    <?php
                                    $createdAt = $dayakaya['created_at'] ?? null;

                                    if (!empty($createdAt)) {
                                        $timestamp = strtotime((string)$createdAt);
                                        echo $timestamp !== false
                                            ? e(date('Y-m-d', $timestamp))
                                            : 'සඳහන් කර නැත';
                                    } else {
                                        echo 'සඳහන් කර නැත';
                                    }
                                    ?>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="card profile-card">

                    <div class="card-header">
                        <i class="fa-solid fa-user me-2"></i>
                        පුද්ගලික තොරතුරු (Personal Information)
                    </div>

                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-md-6 col-lg-4">
                                <div class="info-item">
                                    <div class="info-label">සම්පූර්ණ නම</div>
                                    <div class="info-value">
                                        <?= displayValue($dayakaya['full_name']) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="info-item">
                                    <div class="info-label">ජාතික හැඳුනුම්පත් අංකය</div>
                                    <div class="info-value">
                                        <?= displayValue($dayakaya['nic']) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="info-item">
                                    <div class="info-label">උපන් දිනය</div>
                                    <div class="info-value">
                                        <?= displayValue($dayakaya['dob']) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="info-item">
                                    <div class="info-label">වයස</div>
                                    <div class="info-value">
                                        <?= $age !== null && $age !== ''
                                            ? e((string)$age) . ' අවුරුදු'
                                            : 'සඳහන් කර නැත' ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="info-item">
                                    <div class="info-label">විවාහක තත්ත්වය</div>
                                    <div class="info-value">
                                        <?= translateValue(
                                            $dayakaya['marital_status'] ?? null,
                                            $maritalLabels
                                        ) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="info-item">
                                    <div class="info-label">Email ලිපිනය</div>
                                    <div class="info-value">
                                        <?= displayValue($dayakaya['email']) ?>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="card profile-card">

                    <div class="card-header">
                        <i class="fa-solid fa-address-book me-2"></i>
                        සම්බන්ධතා තොරතුරු (Contact Information)
                    </div>

                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-md-6 col-lg-4">
                                <div class="info-item">
                                    <div class="info-label">ප්‍රධාන දුරකථන අංකය</div>
                                    <div class="info-value">
                                        <?= displayValue($dayakaya['phone']) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="info-item">
                                    <div class="info-label">WhatsApp අංකය 1</div>
                                    <div class="info-value">
                                        <?= displayValue($dayakaya['whatsapp_1']) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="info-item">
                                    <div class="info-label">WhatsApp අංකය 2</div>
                                    <div class="info-value">
                                        <?= displayValue($dayakaya['whatsapp_2']) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="info-item">
                                    <div class="info-label">ස්ථාවර දුරකථන අංකය</div>
                                    <div class="info-value">
                                        <?= displayValue($dayakaya['landline']) ?>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Family Information -->
                <div class="card profile-card">

                    <div class="card-header">
                        <i class="fa-solid fa-house-user me-2"></i>
                        පවුල් තොරතුරු (Family Information)
                    </div>

                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">පවුල් අංකය</div>
                                    <div class="info-value">
                                        <?= displayValue($dayakaya['family_code'] ?? null) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">පේරුව</div>
                                    <div class="info-value">
                                        <?= displayValue($dayakaya['peruwa_name'] ?? null) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="info-item">
                                    <div class="info-label">ලිපිනය</div>
                                    <div class="info-value">
                                        <?= displayValue($dayakaya['address'] ?? null) ?>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Employment Information -->
                <div class="card profile-card">

                    <div class="card-header">
                        <i class="fa-solid fa-briefcase me-2"></i>
                        රැකියා තොරතුරු (Employment Information)
                    </div>

                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-md-6 col-lg-4">
                                <div class="info-item">
                                    <div class="info-label">රැකියා තත්ත්වය</div>
                                    <div class="info-value">
                                        <?= translateValue(
                                            $dayakaya['has_job'] ?? null,
                                            $jobLabels
                                        ) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="info-item">
                                    <div class="info-label">රැකියා වර්ගය</div>
                                    <div class="info-value">
                                        <?= displayValue($dayakaya['job_type']) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="info-item">
                                    <div class="info-label">රැකියා විස්තර</div>
                                    <div class="info-value">
                                        <?= displayValue($dayakaya['job_details']) ?>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Dhamma School Information -->
                <div class="card profile-card">

                    <div class="card-header">
                        <i class="fa-solid fa-book-open me-2"></i>
                        දහම් පාසල් තොරතුරු (Daham Pasala)
                    </div>

                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-md-4">
                                <div class="info-item">
                                    <div class="info-label">දහම් පාසල් සහභාගීත්වය</div>
                                    <div class="info-value">
                                        <?= translateValue(
                                            $dayakaya['attends_daham_pasala'] ?? null,
                                            $yesNoLabels
                                        ) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="info-item">
                                    <div class="info-label">දහම් පාසලේ නම</div>
                                    <div class="info-value">
                                        <?= displayValue($dayakaya['daham_pasala_name']) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="info-item">
                                    <div class="info-label">දහම් පාසල් ශ්‍රේණිය</div>
                                    <div class="info-value">
                                        <?= displayValue($dayakaya['daham_pasala_grade']) ?>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Registration / Rejection Information -->
                <?php if ($status === 'rejected' || !empty($dayakaya['rejection_reason'])): ?>

                    <div class="card profile-card">

                        <div class="card-header">
                            <i class="fa-solid fa-circle-exclamation me-2"></i>
                            ලියාපදිංචි තත්ත්වය පිළිබඳ විස්තර
                        </div>

                        <div class="card-body">
                            <div class="info-item">
                                <div class="info-label">ප්‍රතික්ෂේප කිරීමට හේතුව / සටහන</div>
                                <div class="info-value">
                                    <?= displayValue($dayakaya['rejection_reason']) ?>
                                </div>
                            </div>
                        </div>

                    </div>

                <?php endif; ?>

                <!-- Bottom Actions -->
                <div class="d-flex justify-content-between flex-wrap gap-2 mb-3 no-print">

                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i>
                        දායක නාමාවලියට ආපසු
                    </a>

                    <button type="button"
                            onclick="window.scrollTo({top: 0, behavior: 'smooth'})"
                            class="btn btn-outline-dark">
                        <i class="fa-solid fa-arrow-up me-1"></i>
                        ඉහළට
                    </button>

                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
```