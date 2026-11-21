
<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';

Auth::requireAuth();
Auth::requirePermission('view_dayakayan');

$db = Database::getConnection();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['dp_csrf'])) {
    $_SESSION['dp_csrf'] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION['dp_csrf'];
$message = '';
$messageType = 'info';
$classes = [];
$edit = null;
$search = trim((string)($_GET['search'] ?? ''));

function classH(mixed $value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/**
 * Use the existing "classes" table.
 * Do not use "daham_pasala_classes".
 */
try {
    $columnQuery = $db->query('SHOW COLUMNS FROM `classes`');
    $columns = array_column(
        $columnQuery->fetchAll(PDO::FETCH_ASSOC),
        'Field'
    );

    $requiredColumns = [
        'id',
        'class_name',
        'status'
    ];

    foreach ($requiredColumns as $column) {
        if (!in_array($column, $columns, true)) {
            throw new RuntimeException(
                'Missing required column: ' . $column
            );
        }
    }
} catch (Throwable $e) {
    error_log('Classes schema error: ' . $e->getMessage());
    http_response_code(500);
    exit(
        'The classes table or required columns are missing. ' .
        'Check the temple_db database structure.'
    );
}

/**
 * Save, update or delete a class.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(
        $csrf,
        (string)($_POST['csrf'] ?? '')
    )) {
        http_response_code(403);
        exit('Invalid security token. Refresh the page and try again.');
    }

    $action = (string)($_POST['action'] ?? '');

    try {
        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim((string)($_POST['class_name'] ?? ''));
            $grade = trim((string)($_POST['grade'] ?? ''));
            $teacher = trim((string)($_POST['teacher_name'] ?? ''));
            $day = trim((string)($_POST['day_of_week'] ?? ''));
            $time = trim((string)($_POST['start_time'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $status = (string)($_POST['status'] ?? 'active');

            if ($name === '') {
                throw new RuntimeException(
                    'Class name is required.'
                );
            }

            if (!in_array($status, ['active', 'inactive'], true)) {
                $status = 'active';
            }

            /*
             * Only write columns that exist in the database.
             * This avoids errors if optional columns have not
             * yet been added.
             */
            $values = [
                'class_name' => $name,
                'grade' => $grade !== '' ? $grade : null,
                'teacher_name' => $teacher !== '' ? $teacher : null,
                'day_of_week' => $day !== '' ? $day : null,
                'start_time' => $time !== '' ? $time : null,
                'description' => $description !== '' ? $description : null,
                'status' => $status
            ];

            $saveValues = [];

            foreach ($values as $column => $value) {
                if (in_array($column, $columns, true)) {
                    $saveValues[$column] = $value;
                }
            }

            if ($id > 0) {
                $setParts = [];

                foreach (array_keys($saveValues) as $column) {
                    $setParts[] = '`' . $column . '` = ?';
                }

                $sql = 'UPDATE `classes` SET '
                    . implode(', ', $setParts)
                    . ' WHERE `id` = ?';

                $params = array_values($saveValues);
                $params[] = $id;

                $q = $db->prepare($sql);
                $q->execute($params);

                $message = 'පන්තිය සාර්ථකව යාවත්කාලීන කළා.';
            } else {
                $fieldNames = array_map(
                    static fn($column) => '`' . $column . '`',
                    array_keys($saveValues)
                );

                $placeholders = array_fill(
                    0,
                    count($saveValues),
                    '?'
                );

                $sql = 'INSERT INTO `classes` ('
                    . implode(', ', $fieldNames)
                    . ') VALUES ('
                    . implode(', ', $placeholders)
                    . ')';

                $q = $db->prepare($sql);
                $q->execute(array_values($saveValues));

                $message = 'නව පන්තිය සාර්ථකව එක් කළා.';
            }

            $messageType = 'success';
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new RuntimeException('Invalid class ID.');
            }

            $q = $db->prepare(
                'DELETE FROM `classes` WHERE `id` = ?'
            );
            $q->execute([$id]);

            if ($q->rowCount() > 0) {
                $message = 'පන්තිය මකා දැමුවා.';
                $messageType = 'success';
            } else {
                $message = 'මකා දැමීමට පන්තියක් හමු වුණේ නැහැ.';
                $messageType = 'warning';
            }
        } else {
            throw new RuntimeException('Invalid action.');
        }
    } catch (Throwable $e) {
        error_log('Class management error: ' . $e->getMessage());

        $message = 'පන්තිය සුරැකීමට නොහැකි වුණා. '
            . 'Database error log එක පරීක්ෂා කරන්න.';
        $messageType = 'danger';
    }
}

/**
 * Load class for editing.
 */
if (isset($_GET['edit'])) {
    try {
        $q = $db->prepare(
            'SELECT * FROM `classes` WHERE `id` = ?'
        );
        $q->execute([(int)$_GET['edit']]);

        $edit = $q->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$edit) {
            $message = $message ?: 'අදාළ පන්තිය හමු වුණේ නැහැ.';
            $messageType = 'warning';
        }
    } catch (Throwable $e) {
        error_log('Class edit error: ' . $e->getMessage());
        $message = 'පන්තියේ තොරතුරු ලබාගැනීමට නොහැකි වුණා.';
        $messageType = 'danger';
    }
}

/**
 * Search and list classes.
 */
try {
    $hasGrade = in_array('grade', $columns, true);
    $hasTeacher = in_array('teacher_name', $columns, true);

    if ($search !== '') {
        $conditions = ['`class_name` LIKE ?'];
        $params = ['%' . $search . '%'];

        if ($hasGrade) {
            $conditions[] = '`grade` LIKE ?';
            $params[] = '%' . $search . '%';
        }

        if ($hasTeacher) {
            $conditions[] = '`teacher_name` LIKE ?';
            $params[] = '%' . $search . '%';
        }

        $order = $hasGrade
            ? '`grade`, `class_name`'
            : '`class_name`';

        $sql = 'SELECT * FROM `classes` WHERE '
            . implode(' OR ', $conditions)
            . ' ORDER BY ' . $order;

        $q = $db->prepare($sql);
        $q->execute($params);
    } else {
        $order = $hasGrade
            ? '`grade`, `class_name`'
            : '`class_name`';

        $q = $db->query(
            'SELECT * FROM `classes` ORDER BY ' . $order
        );
    }

    $classes = $q->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Class list error: ' . $e->getMessage());
    $classes = [];

    if ($message === '') {
        $message = 'පන්ති ලැයිස්තුව ලබාගැනීමට නොහැකි වුණා.';
        $messageType = 'danger';
    }
}

$pageTitle = 'දහම් පාසල් ශ්‍රේණි සහ පන්ති';

require_once __DIR__ . '/../../../includes/header.php';
?>

<style>
.dp-page {
    background: #f5f7fb;
    min-height: 75vh;
    padding: 24px;
    color: #202a44;
}
.dp-hero {
    border-radius: 18px;
    color: #fff;
    background: linear-gradient(
        125deg,
        #202a44,
        #344b73 60%,
        #8b6a24
    );
}
.dp-panel {
    border: 1px solid #e9edf4;
    border-radius: 15px;
    background: #fff;
    box-shadow: 0 .25rem 1rem #1928460b;
}
.dp-muted {
    color: #778196;
}
@media (max-width: 767px) {
    .dp-page {
        padding: 12px;
    }
}
@media print {
    .no-print,
    .admin-sidebar,
    nav,
    footer {
        display: none !important;
    }
    .admin-shell {
        display: block !important;
    }
    .admin-main {
        width: 100% !important;
    }
    .dp-page {
        padding: 0;
        background: #fff;
    }
}
</style>

<div class="admin-shell">
    <aside class="admin-sidebar no-print">
        <?php require __DIR__ . '/../../../includes/sidebar.php'; ?>
    </aside>

    <main class="admin-main">
        <div class="dp-page">

            <section class="dp-hero p-4 p-lg-5 mb-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <div class="small mb-2"
                             style="color:#ffe7a0;letter-spacing:.1em">
                            TEMPLE DATA MANAGEMENT SYSTEM
                        </div>

                        <h1 class="h3 fw-bold">
                            දහම් පාසල් ශ්‍රේණි සහ පන්ති
                        </h1>

                        <p class="mb-0 text-white-50">
                            පන්ති, ගුරුවරුන් සහ පන්ති පැවැත්වෙන
                            වේලාවන් කළමනාකරණය කරන්න.
                        </p>
                    </div>

                    <i class="fa-solid fa-school fa-3x opacity-25"></i>
                </div>
            </section>

            <?php if ($message !== ''): ?>
                <div class="alert alert-<?= classH($messageType) ?>">
                    <?= classH($message) ?>
                </div>
            <?php endif; ?>

            <div class="row g-3">

                <div class="col-lg-4">
                    <section class="dp-panel p-3 p-lg-4">
                        <h2 class="h5 fw-bold mb-3">
                            <?= $edit ? 'පන්තිය සංස්කරණය' : 'නව පන්තියක් එක් කරන්න' ?>
                        </h2>

                        <form method="post">
                            <input type="hidden" name="csrf"
                                   value="<?= classH($csrf) ?>">
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="id"
                                   value="<?= classH($edit['id'] ?? '') ?>">

                            <label class="form-label">පන්ති නම *</label>
                            <input class="form-control mb-3"
                                   name="class_name"
                                   required
                                   maxlength="150"
                                   value="<?= classH($edit['class_name'] ?? '') ?>"
                                   placeholder="උදා: N5 / 1 ශ්‍රේණිය">

                            <?php if (in_array('grade', $columns, true)): ?>
                                <label class="form-label">ශ්‍රේණිය</label>
                                <input class="form-control mb-3"
                                       name="grade"
                                       value="<?= classH($edit['grade'] ?? '') ?>">
                            <?php endif; ?>

                            <?php if (in_array('teacher_name', $columns, true)): ?>
                                <label class="form-label">භාර ගුරුවරයා</label>
                                <input class="form-control mb-3"
                                       name="teacher_name"
                                       value="<?= classH($edit['teacher_name'] ?? '') ?>">
                            <?php endif; ?>

                            <?php if (in_array('day_of_week', $columns, true)): ?>
                                <label class="form-label">දිනය</label>
                                <select class="form-select mb-3" name="day_of_week">
                                    <option value="">තෝරන්න</option>

                                    <?php
                                    $days = [
                                        'Sunday' => 'ඉරිදා',
                                        'Monday' => 'සඳුදා',
                                        'Tuesday' => 'අඟහරුවාදා',
                                        'Wednesday' => 'බදාදා',
                                        'Thursday' => 'බ්‍රහස්පතින්දා',
                                        'Friday' => 'සිකුරාදා',
                                        'Saturday' => 'සෙනසුරාදා'
                                    ];

                                    foreach ($days as $en => $si):
                                    ?>
                                        <option value="<?= classH($en) ?>"
                                            <?= ($edit['day_of_week'] ?? '') === $en
                                                ? 'selected' : '' ?>>
                                            <?= classH($si) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>

                            <?php if (in_array('start_time', $columns, true)): ?>
                                <label class="form-label">ආරම්භක වේලාව</label>
                                <input type="time"
                                       class="form-control mb-3"
                                       name="start_time"
                                       value="<?= classH(
                                           isset($edit['start_time'])
                                               ? substr((string)$edit['start_time'], 0, 5)
                                               : ''
                                       ) ?>">
                            <?php endif; ?>

                            <?php if (in_array('description', $columns, true)): ?>
                                <label class="form-label">විස්තරය</label>
                                <textarea class="form-control mb-3"
                                          name="description"
                                          rows="2"><?= classH($edit['description'] ?? '') ?></textarea>
                            <?php endif; ?>

                            <label class="form-label">තත්ත්වය</label>
                            <select class="form-select mb-3" name="status">
                                <option value="active"
                                    <?= ($edit['status'] ?? 'active') === 'active'
                                        ? 'selected' : '' ?>>
                                    සක්‍රීය
                                </option>
                                <option value="inactive"
                                    <?= ($edit['status'] ?? '') === 'inactive'
                                        ? 'selected' : '' ?>>
                                    අක්‍රීය
                                </option>
                            </select>

                            <button class="btn btn-primary" type="submit">
                                <i class="fa-solid fa-floppy-disk me-1"></i>
                                සුරකින්න
                            </button>

                            <?php if ($edit): ?>
                                <a class="btn btn-outline-secondary"
                                   href="<?= classH(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) ?>">
                                    අවලංගු කරන්න
                                </a>
                            <?php endif; ?>
                        </form>
                    </section>
                </div>

                <div class="col-lg-8">
                    <section class="dp-panel p-3 p-lg-4">

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <h2 class="h5 fw-bold mb-0">
                                පන්ති ලැයිස්තුව (<?= count($classes) ?>)
                            </h2>

                            <form method="get" class="d-flex gap-2 no-print">
                                <input class="form-control form-control-sm"
                                       name="search"
                                       value="<?= classH($search) ?>"
                                       placeholder="සොයන්න">

                                <button class="btn btn-sm btn-outline-primary">
                                    සොයන්න
                                </button>

                                <button type="button"
                                        onclick="window.print()"
                                        class="btn btn-sm btn-outline-secondary">
                                    PDF / Print
                                </button>
                            </form>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>පන්තිය</th>

                                        <?php if (in_array('grade', $columns, true)): ?>
                                            <th>ශ්‍රේණිය</th>
                                        <?php endif; ?>

                                        <?php if (in_array('teacher_name', $columns, true)): ?>
                                            <th>ගුරුවරයා</th>
                                        <?php endif; ?>

                                        <?php if (
                                            in_array('day_of_week', $columns, true)
                                            || in_array('start_time', $columns, true)
                                        ): ?>
                                            <th>දිනය / වේලාව</th>
                                        <?php endif; ?>

                                        <th>තත්ත්වය</th>
                                        <th class="no-print">ක්‍රියාමාර්ග</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php if (!$classes): ?>
                                        <tr>
                                            <td colspan="6"
                                                class="text-center text-secondary p-4">
                                                පන්ති නොමැත. නව පන්තියක් එක් කරන්න.
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php foreach ($classes as $c): ?>
                                        <tr>
                                            <td class="fw-semibold">
                                                <?= classH($c['class_name'] ?? '') ?>
                                            </td>

                                            <?php if (in_array('grade', $columns, true)): ?>
                                                <td>
                                                    <?= classH($c['grade'] ?? '—') ?>
                                                </td>
                                            <?php endif; ?>

                                            <?php if (in_array('teacher_name', $columns, true)): ?>
                                                <td>
                                                    <?= classH($c['teacher_name'] ?? '—') ?>
                                                </td>
                                            <?php endif; ?>

                                            <?php if (
                                                in_array('day_of_week', $columns, true)
                                                || in_array('start_time', $columns, true)
                                            ): ?>
                                                <td>
                                                    <?= classH($c['day_of_week'] ?? '—') ?>

                                                    <?php if (!empty($c['start_time'])): ?>
                                                        <?= classH(
                                                            substr((string)$c['start_time'], 0, 5)
                                                        ) ?>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>

                                            <td>
                                                <span class="badge <?= ($c['status'] ?? 'active') === 'active'
                                                    ? 'text-bg-success'
                                                    : 'text-bg-secondary' ?>">
                                                    <?= classH($c['status'] ?? 'active') ?>
                                                </span>
                                            </td>

                                            <td class="no-print">
                                                <a class="btn btn-sm btn-outline-primary"
                                                   href="?edit=<?= (int)$c['id'] ?>">
                                                    Edit
                                                </a>

                                                <form method="post"
                                                      class="d-inline"
                                                      onsubmit="return confirm('මෙම පන්තිය මකා දමන්නද?')">
                                                    <input type="hidden" name="csrf"
                                                           value="<?= classH($csrf) ?>">
                                                    <input type="hidden" name="action"
                                                           value="delete">
                                                    <input type="hidden" name="id"
                                                           value="<?= (int)$c['id'] ?>">

                                                    <button class="btn btn-sm btn-outline-danger"
                                                            type="submit">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
