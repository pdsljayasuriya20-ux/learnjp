<?php
/**
 * Pinkam Management
 * Temple Data Management System (TDMS)
 * File: admin/pinkam/manage_pinkam.php
 *
 * FIXES IN THIS VERSION
 * 1. Editing a record whose type is now inactive (or whose donor is no
 *    longer approved) no longer fails. Unchanged values are allowed;
 *    only NEW choices must be active / approved.
 * 2. Edit modal now lists the record's current inactive type and
 *    non-approved donors, so the dropdown/checkboxes never silently blank.
 * 3. GROUP_CONCAT length raised, so records with many co-donors are no
 *    longer truncated (default limit is 1024 chars).
 * 4. Schema setup (CREATE/ALTER/SHOW COLUMNS) runs once per session
 *    instead of on every page load.
 * 5. Add form checks for ACTIVE types (not just any type).
 * 6. `never` return type removed (needs PHP 8.1+); now `void`.
 * 7. Year field auto-follows the selected date on the add form.
 * 8. Search/filter no longer touches the "empty" placeholder row.
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

Auth::requireAuth();
Auth::requirePermission('manage_pinkam');

$db = Database::getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pageTitle = 'පිංකම් කළමනාකරණය';

/* ======================================================
   HELPERS
====================================================== */

function pinkamEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// FIX: was `: never` (PHP 8.1+ only). exit keeps behaviour identical.
function pinkamRedirect(): void
{
    header('Location: manage_pinkam.php');
    exit;
}

function pinkamFlash(string $type, string $message): void
{
    $_SESSION['pinkam_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function pinkamGetFlash(): ?array
{
    $flash = $_SESSION['pinkam_flash'] ?? null;
    unset($_SESSION['pinkam_flash']);

    return is_array($flash) ? $flash : null;
}

function pinkamExecute(
    PDO $db,
    string $sql,
    array $params = []
): PDOStatement {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return $stmt;
}

function pinkamAll(
    PDO $db,
    string $sql,
    array $params = []
): array {
    return pinkamExecute($db, $sql, $params)
        ->fetchAll(PDO::FETCH_ASSOC);
}

function pinkamOne(
    PDO $db,
    string $sql,
    array $params = []
): ?array {
    $row = pinkamExecute($db, $sql, $params)
        ->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Avoid parameter placeholders in SHOW COLUMNS.
 * Table and column identifiers are validated first.
 */
function pinkamColumnExists(
    PDO $db,
    string $table,
    string $column
): bool {
    if (
        !preg_match('/^[a-zA-Z0-9_]+$/', $table) ||
        !preg_match('/^[a-zA-Z0-9_]+$/', $column)
    ) {
        return false;
    }

    $stmt = $db->query("SHOW COLUMNS FROM `{$table}`");

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
        if (
            isset($item['Field']) &&
            strcasecmp($item['Field'], $column) === 0
        ) {
            return true;
        }
    }

    return false;
}

function pinkamVerifyCsrf(): bool
{
    $submitted = (string)($_POST['csrf_token'] ?? '');
    $stored = (string)($_SESSION['pinkam_csrf_token'] ?? '');

    return $submitted !== ''
        && $stored !== ''
        && hash_equals($stored, $submitted);
}

function pinkamValidDate(string $value): bool
{
    $date = DateTime::createFromFormat('!Y-m-d', $value);

    return $date !== false
        && $date->format('Y-m-d') === $value;
}

/**
 * Validate an active pinkam type.
 */
function pinkamRequireActiveType(PDO $db, int $typeId): void
{
    $type = pinkamOne(
        $db,
        "SELECT id FROM pinkam_types
         WHERE id = ? AND status = 'active'",
        [$typeId]
    );

    if (!$type) {
        throw new RuntimeException(
            'සක්‍රීය පිංකම් වර්ගයක් තෝරන්න.'
        );
    }
}

/**
 * Validate approved dayakayo.
 * $legacyAllowedId: the record's CURRENT principal (edit mode) may stay
 * even if that donor is no longer approved.
 */
function pinkamRequireApprovedDonor(
    PDO $db,
    int $donorId,
    ?int $legacyAllowedId = null
): void {
    if ($legacyAllowedId !== null && $donorId === $legacyAllowedId) {
        return;
    }

    $donor = pinkamOne(
        $db,
        "SELECT id FROM dayakayan
         WHERE id = ? AND status = 'approved'",
        [$donorId]
    );

    if (!$donor) {
        throw new RuntimeException(
            'අනුමත දායකයෙකු තෝරන්න.'
        );
    }
}

/**
 * Validate and normalize the co-donor list.
 * $legacyAllowedIds: co-donors already linked to this record (edit mode)
 * that may stay even if no longer approved.
 */
function pinkamValidateDonorIds(
    PDO $db,
    array $donorIds,
    int $principalId,
    array $legacyAllowedIds = []
): array {
    $cleanIds = [];

    foreach ($donorIds as $donorId) {
        $validatedId = filter_var(
            $donorId,
            FILTER_VALIDATE_INT
        );

        if ($validatedId && (int)$validatedId > 0) {
            $validatedId = (int)$validatedId;

            if ($validatedId !== $principalId) {
                $cleanIds[] = $validatedId;
            }
        }
    }

    $cleanIds = array_values(array_unique($cleanIds));

    // Only IDs that are not already legacy-linked need the approval check.
    $legacyAllowedIds = array_map('intval', $legacyAllowedIds);
    $mustBeApproved = array_values(
        array_diff($cleanIds, $legacyAllowedIds)
    );

    if ($mustBeApproved) {
        $placeholders = implode(
            ',',
            array_fill(0, count($mustBeApproved), '?')
        );

        $approved = pinkamAll(
            $db,
            "SELECT id FROM dayakayan
             WHERE status = 'approved'
             AND id IN ($placeholders)",
            $mustBeApproved
        );

        if (count($approved) !== count($mustBeApproved)) {
            throw new RuntimeException(
                'තෝරාගත් දායකයන් අතර අනුමත නොකළ දායකයෙකු සිටිනවා.'
            );
        }
    }

    return $cleanIds;
}

/**
 * Save registration co-donors.
 */
function pinkamSaveDonors(
    PDO $db,
    int $registrationId,
    array $donorIds
): void {
    pinkamExecute(
        $db,
        'DELETE FROM pinkam_registration_donors
         WHERE registration_id = ?',
        [$registrationId]
    );

    if (!$donorIds) {
        return;
    }

    $stmt = $db->prepare(
        'INSERT INTO pinkam_registration_donors
            (registration_id, dayakaya_id)
         VALUES (?, ?)'
    );

    foreach ($donorIds as $donorId) {
        $stmt->execute([$registrationId, $donorId]);
    }
}

/* ======================================================
   CSRF TOKEN
====================================================== */

if (empty($_SESSION['pinkam_csrf_token'])) {
    $_SESSION['pinkam_csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['pinkam_csrf_token'];

/* ======================================================
   DATABASE SETUP
   Existing data is preserved.
   FIX: runs once per session (flag below) instead of every request.
   To force it again, bump the version string.
====================================================== */

$schemaVersion = 'v2';

if (($_SESSION['pinkam_schema_ready'] ?? '') !== $schemaVersion) {
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS pinkam_types (
                id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(150) NOT NULL,
                status ENUM('active','inactive')
                    NOT NULL DEFAULT 'active',
                created_at DATETIME
                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_pinkam_type_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        if (!pinkamColumnExists($db, 'pinkam_types', 'status')) {
            $db->exec("
                ALTER TABLE pinkam_types
                ADD COLUMN status ENUM('active','inactive')
                NOT NULL DEFAULT 'active'
            ");
        }

        if (!pinkamColumnExists($db, 'pinkam_types', 'created_at')) {
            $db->exec("
                ALTER TABLE pinkam_types
                ADD COLUMN created_at DATETIME
                NOT NULL DEFAULT CURRENT_TIMESTAMP
            ");
        }

        $db->exec("
            CREATE TABLE IF NOT EXISTS pinkam_registrations (
                id INT NOT NULL AUTO_INCREMENT,
                pinkam_type_id INT NOT NULL,
                year INT NOT NULL,
                held_on DATE NOT NULL,
                amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                principal_dayakaya_id INT NOT NULL,
                notes TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_pinkam_type (pinkam_type_id),
                KEY idx_pinkam_year (year),
                KEY idx_pinkam_date (held_on),
                KEY idx_pinkam_principal (principal_dayakaya_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS pinkam_registration_donors (
                id INT NOT NULL AUTO_INCREMENT,
                registration_id INT NOT NULL,
                dayakaya_id INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_pinkam_registration_donor
                    (registration_id, dayakaya_id),
                KEY idx_pinkam_donor (dayakaya_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $defaultTypes = [
            'කඨින පිංකම',
            'වස් ආරාධනා පිංකම',
            'නව වසරේ ආශිර්වාද පූජාව',
        ];

        $seedStmt = $db->prepare(
            'INSERT IGNORE INTO pinkam_types (name) VALUES (?)'
        );

        foreach ($defaultTypes as $defaultType) {
            $seedStmt->execute([$defaultType]);
        }

        $_SESSION['pinkam_schema_ready'] = $schemaVersion;

    } catch (Throwable $e) {
        error_log('Pinkam database setup error: ' . $e->getMessage());

        http_response_code(500);

        echo '<div style="font-family:Arial,sans-serif;padding:24px">';
        echo '<h2>Pinkam Database Setup Error</h2>';
        echo '<pre style="background:#f8d7da;padding:16px;white-space:pre-wrap">';
        echo pinkamEscape($e->getMessage());
        echo '</pre></div>';

        exit;
    }
}

/* ======================================================
   FORM ACTIONS
====================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pinkamVerifyCsrf()) {
        pinkamFlash(
            'danger',
            'ආරක්ෂක token එක කල් ඉකුත් වී ඇත. නැවත උත්සාහ කරන්න.'
        );
        pinkamRedirect();
    }

    $action = (string)($_POST['action'] ?? '');

    try {
        /* ----------------------------------------------
           ADD PINKAM TYPE
        ---------------------------------------------- */
        if ($action === 'add_type') {
            $name = trim((string)($_POST['type_name'] ?? ''));

            if (
                $name === '' ||
                mb_strlen($name) > 150
            ) {
                throw new RuntimeException(
                    'නිවැරදි පිංකම් වර්ගයේ නමක් ඇතුළත් කරන්න.'
                );
            }

            $exists = pinkamOne(
                $db,
                'SELECT id FROM pinkam_types WHERE name = ?',
                [$name]
            );

            if ($exists) {
                throw new RuntimeException(
                    'මෙම පිංකම් වර්ගය දැනටමත් තිබෙනවා.'
                );
            }

            pinkamExecute(
                $db,
                "INSERT INTO pinkam_types (name, status)
                 VALUES (?, 'active')",
                [$name]
            );

            pinkamFlash('success', 'පිංකම් වර්ගය එකතු කළා.');
            pinkamRedirect();
        }

        /* ----------------------------------------------
           EDIT PINKAM TYPE NAME
        ---------------------------------------------- */
        if ($action === 'edit_type') {
            $typeId = filter_var(
                $_POST['type_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $name = trim((string)($_POST['type_name'] ?? ''));

            if (!$typeId || $name === '' || mb_strlen($name) > 150) {
                throw new RuntimeException(
                    'පිංකම් වර්ගයේ නම සහ ID එක පරීක්ෂා කරන්න.'
                );
            }

            $exists = pinkamOne(
                $db,
                'SELECT id FROM pinkam_types
                 WHERE name = ? AND id <> ?',
                [$name, $typeId]
            );

            if ($exists) {
                throw new RuntimeException(
                    'මෙම නම වෙනත් පිංකම් වර්ගයකට දැනටමත් භාවිතා වෙනවා.'
                );
            }

            $type = pinkamOne(
                $db,
                'SELECT id FROM pinkam_types WHERE id = ?',
                [$typeId]
            );

            if (!$type) {
                throw new RuntimeException(
                    'පිංකම් වර්ගය සොයාගත නොහැක.'
                );
            }

            pinkamExecute(
                $db,
                'UPDATE pinkam_types SET name = ? WHERE id = ?',
                [$name, $typeId]
            );

            pinkamFlash('success', 'පිංකම් වර්ගයේ නම වෙනස් කළා.');
            pinkamRedirect();
        }

        /* ----------------------------------------------
           ACTIVATE / DEACTIVATE TYPE
        ---------------------------------------------- */
        if ($action === 'toggle_type') {
            $typeId = filter_var(
                $_POST['type_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $newStatus = (string)($_POST['new_status'] ?? '');

            if (
                !$typeId ||
                !in_array($newStatus, ['active', 'inactive'], true)
            ) {
                throw new RuntimeException(
                    'පිංකම් වර්ගයේ තොරතුරු නිවැරදි නැහැ.'
                );
            }

            pinkamExecute(
                $db,
                'UPDATE pinkam_types SET status = ? WHERE id = ?',
                [$newStatus, $typeId]
            );

            pinkamFlash('success', 'පිංකම් වර්ගයේ තත්ත්වය වෙනස් කළා.');
            pinkamRedirect();
        }

        /* ----------------------------------------------
           DELETE TYPE IF NOT USED
        ---------------------------------------------- */
        if ($action === 'delete_type') {
            $typeId = filter_var(
                $_POST['type_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$typeId) {
                throw new RuntimeException(
                    'පිංකම් වර්ගය හඳුනාගත නොහැක.'
                );
            }

            $used = pinkamOne(
                $db,
                'SELECT COUNT(*) AS total
                 FROM pinkam_registrations
                 WHERE pinkam_type_id = ?',
                [$typeId]
            );

            if ((int)($used['total'] ?? 0) > 0) {
                throw new RuntimeException(
                    'මෙම වර්ගයට පිංකම් වාර්තා තිබෙනවා. ' .
                    'වාර්තා ඇති වර්ගයක් මකා දැමිය නොහැක.'
                );
            }

            pinkamExecute(
                $db,
                'DELETE FROM pinkam_types WHERE id = ?',
                [$typeId]
            );

            pinkamFlash('success', 'පිංකම් වර්ගය මකා දැමුවා.');
            pinkamRedirect();
        }

        /* ----------------------------------------------
           ADD / EDIT REGISTRATION
        ---------------------------------------------- */
        if (
            $action === 'add_registration' ||
            $action === 'edit_registration'
        ) {
            $isEdit = $action === 'edit_registration';

            $registrationId = filter_var(
                $_POST['registration_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if ($isEdit && !$registrationId) {
                throw new RuntimeException(
                    'සංස්කරණය කළ යුතු වාර්තාව හඳුනාගත නොහැක.'
                );
            }

            $typeId = filter_var(
                $_POST['pinkam_type_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $year = filter_var(
                $_POST['year'] ?? null,
                FILTER_VALIDATE_INT
            );

            $heldOn = trim((string)($_POST['held_on'] ?? ''));
            $amountInput = trim((string)($_POST['amount'] ?? '0'));
            $principalId = filter_var(
                $_POST['principal_dayakaya_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $notes = trim((string)($_POST['notes'] ?? ''));
            $donorIds = $_POST['donor_ids'] ?? [];

            if (!is_array($donorIds)) {
                $donorIds = [];
            }

            if (!$typeId || !$year || !$principalId) {
                throw new RuntimeException(
                    'අවශ්‍ය තොරතුරු සියල්ල ඇතුළත් කරන්න.'
                );
            }

            if ($year < 1900 || $year > 2200) {
                throw new RuntimeException(
                    'වර්ෂය නිවැරදිව ඇතුළත් කරන්න.'
                );
            }

            if (!pinkamValidDate($heldOn)) {
                throw new RuntimeException(
                    'නිවැරදි පිංකම් දිනයක් තෝරන්න.'
                );
            }

            if (
                !is_numeric($amountInput) ||
                !is_finite((float)$amountInput) ||
                (float)$amountInput < 0 ||
                (float)$amountInput > 9999999999.99
            ) {
                throw new RuntimeException(
                    'මුදල නිවැරදිව ඇතුළත් කරන්න.'
                );
            }

            // FIX: load the existing record first (edit mode) so values
            // that are UNCHANGED stay valid even if the type became
            // inactive or a donor is no longer approved.
            $existingRegistration = null;
            $legacyPrincipalId = null;
            $legacyDonorIds = [];

            if ($isEdit) {
                $existingRegistration = pinkamOne(
                    $db,
                    'SELECT id, pinkam_type_id, principal_dayakaya_id
                     FROM pinkam_registrations WHERE id = ?',
                    [$registrationId]
                );

                if (!$existingRegistration) {
                    throw new RuntimeException(
                        'මෙම පිංකම් වාර්තාව දැනට නොමැත.'
                    );
                }

                $legacyPrincipalId =
                    (int)$existingRegistration['principal_dayakaya_id'];

                $legacyDonorIds = array_map(
                    'intval',
                    array_column(
                        pinkamAll(
                            $db,
                            'SELECT dayakaya_id
                             FROM pinkam_registration_donors
                             WHERE registration_id = ?',
                            [$registrationId]
                        ),
                        'dayakaya_id'
                    )
                );
            }

            // Type must be active unless it is the record's current type.
            $typeUnchanged = $isEdit
                && (int)$existingRegistration['pinkam_type_id'] === (int)$typeId;

            if ($typeUnchanged) {
                $typeRow = pinkamOne(
                    $db,
                    'SELECT id FROM pinkam_types WHERE id = ?',
                    [$typeId]
                );

                if (!$typeRow) {
                    throw new RuntimeException(
                        'පිංකම් වර්ගය සොයාගත නොහැක.'
                    );
                }
            } else {
                pinkamRequireActiveType($db, (int)$typeId);
            }

            pinkamRequireApprovedDonor(
                $db,
                (int)$principalId,
                $legacyPrincipalId
            );

            $cleanDonorIds = pinkamValidateDonorIds(
                $db,
                $donorIds,
                (int)$principalId,
                array_merge(
                    $legacyDonorIds,
                    $legacyPrincipalId !== null ? [$legacyPrincipalId] : []
                )
            );

            $db->beginTransaction();

            if ($isEdit) {
                pinkamExecute(
                    $db,
                    'UPDATE pinkam_registrations
                     SET pinkam_type_id = ?,
                         year = ?,
                         held_on = ?,
                         amount = ?,
                         principal_dayakaya_id = ?,
                         notes = ?
                     WHERE id = ?',
                    [
                        $typeId,
                        $year,
                        $heldOn,
                        number_format((float)$amountInput, 2, '.', ''),
                        $principalId,
                        $notes !== '' ? $notes : null,
                        $registrationId,
                    ]
                );

                $savedRegistrationId = (int)$registrationId;

            } else {
                pinkamExecute(
                    $db,
                    'INSERT INTO pinkam_registrations
                        (pinkam_type_id, year, held_on, amount,
                         principal_dayakaya_id, notes)
                     VALUES (?, ?, ?, ?, ?, ?)',
                    [
                        $typeId,
                        $year,
                        $heldOn,
                        number_format((float)$amountInput, 2, '.', ''),
                        $principalId,
                        $notes !== '' ? $notes : null,
                    ]
                );

                $savedRegistrationId = (int)$db->lastInsertId();
            }

            // Replace the co-donor links with the submitted list.
            pinkamSaveDonors(
                $db,
                $savedRegistrationId,
                $cleanDonorIds
            );

            $db->commit();

            pinkamFlash(
                'success',
                $isEdit
                    ? 'පිංකම් වාර්තාව සාර්ථකව යාවත්කාලීන කළා.'
                    : 'පිංකම් වාර්තාව සාර්ථකව එකතු කළා.'
            );

            pinkamRedirect();
        }

        /* ----------------------------------------------
           DELETE REGISTRATION
        ---------------------------------------------- */
        if ($action === 'delete_registration') {
            $registrationId = filter_var(
                $_POST['registration_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$registrationId) {
                throw new RuntimeException(
                    'පිංකම් වාර්තාව හඳුනාගත නොහැක.'
                );
            }

            $exists = pinkamOne(
                $db,
                'SELECT id FROM pinkam_registrations WHERE id = ?',
                [$registrationId]
            );

            if (!$exists) {
                throw new RuntimeException(
                    'මකා දැමීමට අවශ්‍ය වාර්තාව දැනට නොමැත.'
                );
            }

            $db->beginTransaction();

            pinkamExecute(
                $db,
                'DELETE FROM pinkam_registration_donors
                 WHERE registration_id = ?',
                [$registrationId]
            );

            pinkamExecute(
                $db,
                'DELETE FROM pinkam_registrations WHERE id = ?',
                [$registrationId]
            );

            $db->commit();

            pinkamFlash('success', 'පිංකම් වාර්තාව මකා දැමුවා.');
            pinkamRedirect();
        }

        throw new RuntimeException('හඳුනා නොගත් ක්‍රියාවක්.');

    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        error_log('Pinkam action error: ' . $e->getMessage());

        if ($e instanceof RuntimeException) {
            $message = $e->getMessage();
        } elseif (
            $e instanceof PDOException &&
            (string)$e->getCode() === '23000'
        ) {
            $message = 'මෙම දත්ත දැනටමත් තිබෙනවා හෝ වෙනත් වාර්තාවකට සම්බන්ධයි.';
        } else {
            $message = 'දත්ත සුරැකීමේදී දෝෂයක් ඇති වුණා. නැවත පරීක්ෂා කරන්න.';
        }

        pinkamFlash('danger', $message);
        pinkamRedirect();
    }
}

/* ======================================================
   LOAD PAGE DATA
====================================================== */

try {
    // FIX: default GROUP_CONCAT limit is 1024 chars and silently truncates
    // long co-donor lists / ID lists. Raise it for this session.
    $db->exec('SET SESSION group_concat_max_len = 1048576');

    $types = pinkamAll(
        $db,
        'SELECT id, name, status, created_at
         FROM pinkam_types
         ORDER BY name ASC'
    );

    // Active types only (used by the ADD form).
    $activeTypes = array_values(array_filter(
        $types,
        static fn($t) => $t['status'] === 'active'
    ));

    // Approved donors (used by the ADD form).
    $donors = pinkamAll(
        $db,
        "SELECT id, full_name
         FROM dayakayan
         WHERE status = 'approved'
         ORDER BY full_name ASC"
    );

    // All donors (used by the EDIT modal so existing non-approved
    // donors on old records are still visible / selectable).
    $editDonors = pinkamAll(
        $db,
        "SELECT id, full_name, status
         FROM dayakayan
         ORDER BY full_name ASC"
    );

    $records = pinkamAll(
        $db,
        "SELECT
            r.id,
            r.pinkam_type_id,
            r.year,
            r.held_on,
            r.amount,
            r.principal_dayakaya_id,
            r.notes,
            t.name AS type_name,
            p.full_name AS principal_name,
            COALESCE(
                GROUP_CONCAT(
                    DISTINCT co.full_name
                    ORDER BY co.full_name
                    SEPARATOR ', '
                ),
                ''
            ) AS co_donors,
            COALESCE(
                GROUP_CONCAT(
                    DISTINCT rd.dayakaya_id
                    ORDER BY rd.dayakaya_id
                    SEPARATOR ','
                ),
                ''
            ) AS co_donor_ids
         FROM pinkam_registrations r
         INNER JOIN pinkam_types t
            ON t.id = r.pinkam_type_id
         LEFT JOIN dayakayan p
            ON p.id = r.principal_dayakaya_id
         LEFT JOIN pinkam_registration_donors rd
            ON rd.registration_id = r.id
         LEFT JOIN dayakayan co
            ON co.id = rd.dayakaya_id
         GROUP BY
            r.id,
            r.pinkam_type_id,
            r.year,
            r.held_on,
            r.amount,
            r.principal_dayakaya_id,
            r.notes,
            t.name,
            p.full_name
         ORDER BY r.held_on DESC, r.id DESC"
    );

    $stats = pinkamOne(
        $db,
        'SELECT
            COUNT(*) AS total_records,
            COALESCE(SUM(amount), 0) AS total_amount,
            COUNT(DISTINCT year) AS total_years
         FROM pinkam_registrations'
    ) ?? [];

    $typeCount = count($types);
    $totalRecords = (int)($stats['total_records'] ?? 0);
    $totalAmount = (float)($stats['total_amount'] ?? 0);
    $totalYears = (int)($stats['total_years'] ?? 0);

    $recordYears = array_values(array_unique(
        array_map(
            static fn($record) => (int)$record['year'],
            $records
        )
    ));

    rsort($recordYears);

} catch (Throwable $e) {
    error_log('Pinkam page loading error: ' . $e->getMessage());

    http_response_code(500);

    echo '<div style="font-family:Arial,sans-serif;padding:24px">';
    echo '<h2>පිංකම් දත්ත ලබාගැනීමේ දෝෂයක්</h2>';
    echo '<p>Database error: ';
    echo pinkamEscape($e->getMessage());
    echo '</p></div>';

    exit;
}

$flash = pinkamGetFlash();

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
.pinkam-page {
    background: #f5f7fb;
    min-height: 75vh;
}

.pinkam-hero {
    color: #fff;
    border: 0;
    border-radius: 18px;
    background: linear-gradient(125deg, #202a44, #344b73 60%, #8b6a24);
    overflow: hidden;
}

.pinkam-hero-icon {
    font-size: 4rem;
    opacity: .22;
}

.pinkam-stat-card {
    height: 100%;
    border: 0;
    border-radius: 15px;
    transition: transform .15s ease, box-shadow .15s ease;
}

.pinkam-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 .7rem 1.5rem rgba(0,0,0,.09) !important;
}

.pinkam-stat-icon {
    display: inline-flex;
    width: 48px;
    height: 48px;
    align-items: center;
    justify-content: center;
    border-radius: 13px;
    font-size: 1.2rem;
}

.pinkam-panel {
    border: 1px solid #e9edf4;
    border-radius: 15px;
    overflow: hidden;
}

.pinkam-panel .card-header {
    background: #fff;
    border-bottom: 1px solid #edf0f5;
    padding: 1rem 1.25rem;
}

.pinkam-muted {
    color: #778196;
}

.pinkam-table th {
    background: #f8f9fc;
    color: #566176;
    font-size: .8rem;
    white-space: nowrap;
}

.pinkam-table td {
    vertical-align: middle;
}

.pinkam-type-badge {
    display: inline-block;
    border-radius: 20px;
    background: #fff5d6;
    color: #795b00;
    padding: .35rem .65rem;
    font-size: .78rem;
    font-weight: 600;
}

.pinkam-donor-list {
    max-height: 190px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 9px;
    padding: .75rem;
}

.pinkam-donor-item {
    padding: .35rem 0;
    border-bottom: 1px solid #f0f2f6;
}

.pinkam-donor-item:last-child {
    border-bottom: 0;
}

.pinkam-type-row {
    border-bottom: 1px solid #eef1f5;
}

.pinkam-type-row:last-child {
    border-bottom: 0;
}

.pinkam-section-title {
    font-weight: 700;
    color: #25314a;
}

@media print {
    .no-print,
    .admin-sidebar,
    nav,
    footer,
    .btn,
    form {
        display: none !important;
    }

    .admin-shell {
        display: block !important;
    }

    .admin-main {
        width: 100% !important;
    }

    .pinkam-page {
        background: #fff !important;
        padding: 0 !important;
    }

    .pinkam-hero {
        color: #222 !important;
        background: #fff !important;
        border: 1px solid #ddd !important;
    }

    .pinkam-hero .text-white-50 {
        color: #555 !important;
    }

    .pinkam-panel,
    .pinkam-stat-card {
        box-shadow: none !important;
    }
}
</style>

<div class="admin-shell">

    <aside class="admin-sidebar no-print">
        <?php require __DIR__ . '/../../includes/sidebar.php'; ?>
    </aside>

    <main class="admin-main">
        <div class="pinkam-page p-3 p-md-4">

            <!-- HERO -->
            <section class="pinkam-hero shadow-sm p-4 p-lg-5 mb-4">
                <div class="row align-items-center g-3">
                    <div class="col">
                        <div class="small text-uppercase fw-semibold mb-2"
                             style="letter-spacing:.12em;color:#ffe7a0">
                            Temple Data Management System
                        </div>

                        <h1 class="h3 fw-bold mb-2">
                            පිංකම් කළමනාකරණය
                        </h1>

                        <p class="mb-0 text-white-50">
                            පිංකම් වර්ග, දින, දායකයන් සහ මුදල් වාර්තා කළමනාකරණය කරන්න.
                        </p>

                        <div class="d-flex flex-wrap gap-2 mt-4 no-print">
                            <a href="#addPinkam" class="btn btn-warning fw-semibold">
                                <i class="fa-solid fa-plus me-2"></i>
                                පිංකමක් එකතු කරන්න
                            </a>

                            <a href="#pinkamTypes" class="btn btn-outline-light">
                                <i class="fa-solid fa-list me-2"></i>
                                පිංකම් වර්ග
                            </a>

                            <button type="button"
                                    onclick="window.print()"
                                    class="btn btn-outline-light">
                                <i class="fa-solid fa-print me-2"></i>
                                Print / PDF
                            </button>
                        </div>
                    </div>

                    <div class="col-auto d-none d-md-block">
                        <i class="fa-solid fa-hands-praying pinkam-hero-icon"></i>
                    </div>
                </div>
            </section>

            <!-- FLASH MESSAGE -->
            <?php if ($flash): ?>
                <div class="alert alert-<?= pinkamEscape($flash['type']) ?> alert-dismissible fade show no-print"
                     role="alert">
                    <i class="fa-solid fa-circle-info me-2"></i>
                    <?= pinkamEscape($flash['message']) ?>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="alert"
                            aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- SUMMARY -->
            <div class="mb-3">
                <h2 class="h5 fw-bold mb-1">සාරාංශය</h2>
                <p class="pinkam-muted small mb-0">
                    දැනට database එකේ ඇති පිංකම් දත්ත
                </p>
            </div>

            <div class="row g-3 mb-4">

                <div class="col-6 col-xl-3">
                    <div class="card pinkam-stat-card shadow-sm">
                        <div class="card-body p-3 p-lg-4">
                            <div class="d-flex justify-content-between gap-2">
                                <div>
                                    <div class="pinkam-muted small mb-2">
                                        මුළු පිංකම් වාර්තා
                                    </div>
                                    <div class="h2 fw-bold mb-0">
                                        <?= number_format($totalRecords) ?>
                                    </div>
                                </div>
                                <span class="pinkam-stat-icon bg-primary-subtle text-primary">
                                    <i class="fa-solid fa-list-check"></i>
                                </span>
                            </div>
                            <div class="small pinkam-muted mt-3">
                                ලියාපදිංචි වාර්තා
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="card pinkam-stat-card shadow-sm">
                        <div class="card-body p-3 p-lg-4">
                            <div class="d-flex justify-content-between gap-2">
                                <div>
                                    <div class="pinkam-muted small mb-2">
                                        මුළු මුදල
                                    </div>
                                    <div class="h3 fw-bold mb-0">
                                        Rs. <?= number_format($totalAmount, 2) ?>
                                    </div>
                                </div>
                                <span class="pinkam-stat-icon bg-success-subtle text-success">
                                    <i class="fa-solid fa-wallet"></i>
                                </span>
                            </div>
                            <div class="small pinkam-muted mt-3">
                                සියලු වාර්තාවල එකතුව
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="card pinkam-stat-card shadow-sm">
                        <div class="card-body p-3 p-lg-4">
                            <div class="d-flex justify-content-between gap-2">
                                <div>
                                    <div class="pinkam-muted small mb-2">
                                        පිංකම් වර්ග
                                    </div>
                                    <div class="h2 fw-bold mb-0">
                                        <?= number_format($typeCount) ?>
                                    </div>
                                </div>
                                <span class="pinkam-stat-icon bg-warning-subtle text-warning-emphasis">
                                    <i class="fa-solid fa-layer-group"></i>
                                </span>
                            </div>
                            <div class="small pinkam-muted mt-3">
                                සක්‍රීය සහ අක්‍රීය වර්ග
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="card pinkam-stat-card shadow-sm">
                        <div class="card-body p-3 p-lg-4">
                            <div class="d-flex justify-content-between gap-2">
                                <div>
                                    <div class="pinkam-muted small mb-2">
                                        වාර්තා ඇති වර්ෂ
                                    </div>
                                    <div class="h2 fw-bold mb-0">
                                        <?= number_format($totalYears) ?>
                                    </div>
                                </div>
                                <span class="pinkam-stat-icon bg-info-subtle text-info">
                                    <i class="fa-solid fa-calendar-days"></i>
                                </span>
                            </div>
                            <div class="small pinkam-muted mt-3">
                                වෙනස් වර්ෂ ගණන
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ADD REGISTRATION -->
            <section id="addPinkam" class="card pinkam-panel shadow-sm mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="pinkam-stat-icon bg-primary-subtle text-primary"
                          style="width:40px;height:40px">
                        <i class="fa-solid fa-calendar-plus"></i>
                    </span>
                    <div>
                        <h2 class="h6 fw-bold mb-1">නව පිංකම් වාර්තාවක්</h2>
                        <div class="pinkam-muted small">
                            පිංකමේ තොරතුරු ඇතුළත් කරන්න
                        </div>
                    </div>
                </div>

                <div class="card-body p-3 p-lg-4">

                    <?php /* FIX: check ACTIVE types, not just any type */ ?>
                    <?php if (empty($activeTypes)): ?>
                        <div class="alert alert-warning mb-0">
                            මුලින්ම සක්‍රීය පිංකම් වර්ගයක් එකතු කරන්න.
                        </div>

                    <?php elseif (empty($donors)): ?>
                        <div class="alert alert-warning mb-0">
                            අනුමත දායකයන් හමු වුණේ නැහැ.
                            dayakayan table එකේ
                            <code>status = 'approved'</code>
                            දායකයන් සිටිනවාදැයි පරීක්ෂා කරන්න.
                        </div>

                    <?php else: ?>

                        <form method="post" action="manage_pinkam.php">
                            <input type="hidden" name="csrf_token"
                                   value="<?= pinkamEscape($csrfToken) ?>">
                            <input type="hidden" name="action"
                                   value="add_registration">

                            <div class="row g-3">

                                <div class="col-md-6 col-xl-4">
                                    <label class="form-label fw-semibold">
                                        පිංකම් වර්ගය *
                                    </label>

                                    <select name="pinkam_type_id"
                                            class="form-select" required>
                                        <option value="">වර්ගය තෝරන්න</option>

                                        <?php foreach ($activeTypes as $type): ?>
                                            <option value="<?= (int)$type['id'] ?>">
                                                <?= pinkamEscape($type['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 col-xl-4">
                                    <label class="form-label fw-semibold">
                                        වර්ෂය *
                                    </label>

                                    <input type="number" name="year"
                                           id="add_year"
                                           class="form-control"
                                           min="1900" max="2200"
                                           value="<?= (int)date('Y') ?>"
                                           required>
                                </div>

                                <div class="col-md-6 col-xl-4">
                                    <label class="form-label fw-semibold">
                                        පිංකම් දිනය *
                                    </label>

                                    <input type="date" name="held_on"
                                           id="add_held_on"
                                           class="form-control"
                                           value="<?= pinkamEscape(date('Y-m-d')) ?>"
                                           required>
                                </div>

                                <div class="col-md-6 col-xl-4">
                                    <label class="form-label fw-semibold">
                                        මුදල (Rs.) *
                                    </label>

                                    <input type="number" name="amount"
                                           class="form-control"
                                           min="0" max="9999999999.99"
                                           step="0.01" value="0.00"
                                           required>
                                </div>

                                <div class="col-md-6 col-xl-8">
                                    <label class="form-label fw-semibold">
                                        ප්‍රධාන දායකයා *
                                    </label>

                                    <select name="principal_dayakaya_id"
                                            class="form-select" required>
                                        <option value="">
                                            ප්‍රධාන දායකයා තෝරන්න
                                        </option>

                                        <?php foreach ($donors as $donor): ?>
                                            <option value="<?= (int)$donor['id'] ?>">
                                                <?= pinkamEscape($donor['full_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">
                                        අනෙකුත් දායකයන්
                                    </label>

                                    <div class="pinkam-muted small mb-2">
                                        අවශ්‍ය නම් දායකයන් කිහිපදෙනෙකු තෝරන්න.
                                    </div>

                                    <div class="pinkam-donor-list">
                                        <div class="row g-2">
                                            <?php foreach ($donors as $donor): ?>
                                                <div class="col-md-6 col-xl-4 pinkam-donor-item">
                                                    <label class="form-check mb-0">
                                                        <input
                                                            class="form-check-input"
                                                            type="checkbox"
                                                            name="donor_ids[]"
                                                            value="<?= (int)$donor['id'] ?>">

                                                        <span class="form-check-label">
                                                            <?= pinkamEscape($donor['full_name']) ?>
                                                        </span>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">
                                        සටහන්
                                    </label>

                                    <textarea name="notes"
                                              class="form-control"
                                              rows="3"
                                              maxlength="5000"
                                              placeholder="අමතර විස්තර..."></textarea>
                                </div>

                                <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                                    <button type="submit"
                                            class="btn btn-primary fw-semibold">
                                        <i class="fa-solid fa-floppy-disk me-2"></i>
                                        වාර්තාව සුරකින්න
                                    </button>

                                    <button type="reset"
                                            class="btn btn-outline-secondary">
                                        <i class="fa-solid fa-rotate-left me-2"></i>
                                        නැවත සකසන්න
                                    </button>
                                </div>

                            </div>
                        </form>

                    <?php endif; ?>
                </div>
            </section>

            <!-- REGISTRATION LIST -->
            <section class="card pinkam-panel shadow-sm mb-4">
                <div class="card-header">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <h2 class="h6 fw-bold mb-1">පිංකම් වාර්තා</h2>
                            <div class="pinkam-muted small">
                                මුළු වාර්තා: <?= number_format($totalRecords) ?>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 no-print">
                            <input type="search"
                                   id="pinkamSearch"
                                   class="form-control form-control-sm"
                                   placeholder="සොයන්න..."
                                   style="min-width:180px">

                            <select id="filterYear"
                                    class="form-select form-select-sm"
                                    style="min-width:125px">
                                <option value="">සියලු වර්ෂ</option>
                                <?php foreach ($recordYears as $recordYear): ?>
                                    <option value="<?= (int)$recordYear ?>">
                                        <?= (int)$recordYear ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select id="filterType"
                                    class="form-select form-select-sm"
                                    style="min-width:160px">
                                <option value="">සියලු වර්ග</option>
                                <?php foreach ($types as $type): ?>
                                    <option value="<?= (int)$type['id'] ?>">
                                        <?= pinkamEscape($type['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover pinkam-table mb-0"
                           id="pinkamTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>පිංකම් වර්ගය</th>
                                <th>දිනය</th>
                                <th>වර්ෂය</th>
                                <th>මුදල (Rs.)</th>
                                <th>ප්‍රධාන දායකයා</th>
                                <th>අනෙකුත් දායකයන්</th>
                                <th>සටහන්</th>
                                <th class="no-print">ක්‍රියා</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php if (empty($records)): ?>
                            <tr class="pinkam-empty-row">
                                <td colspan="9" class="text-center py-5">
                                    <i class="fa-solid fa-folder-open fs-2 text-secondary mb-3 d-block"></i>
                                    <div class="fw-semibold">
                                        තවම පිංකම් වාර්තා නැහැ
                                    </div>
                                    <div class="pinkam-muted small">
                                        ඉහත form එකෙන් පළමු වාර්තාව එකතු කරන්න.
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>

                            <?php foreach ($records as $index => $record): ?>
                                <tr
                                    data-year="<?= (int)$record['year'] ?>"
                                    data-type="<?= (int)$record['pinkam_type_id'] ?>">

                                    <td><?= $index + 1 ?></td>

                                    <td>
                                        <span class="pinkam-type-badge">
                                            <?= pinkamEscape($record['type_name']) ?>
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        <?= pinkamEscape($record['held_on']) ?>
                                    </td>

                                    <td><?= (int)$record['year'] ?></td>

                                    <td class="text-nowrap fw-semibold">
                                        <?= number_format((float)$record['amount'], 2) ?>
                                    </td>

                                    <td>
                                        <?= pinkamEscape($record['principal_name'] ?? '—') ?>
                                    </td>

                                    <td style="min-width:180px">
                                        <?= pinkamEscape($record['co_donors'] ?: '—') ?>
                                    </td>

                                    <td style="min-width:150px">
                                        <?= nl2br(pinkamEscape($record['notes'] ?: '—')) ?>
                                    </td>

                                    <td class="no-print text-nowrap">
                                        <div class="d-flex gap-1">

                                            <!-- EDIT BUTTON -->
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary"
                                                    title="වාර්තාව සංස්කරණය කරන්න"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editRegistrationModal"
                                                    data-id="<?= (int)$record['id'] ?>"
                                                    data-type="<?= (int)$record['pinkam_type_id'] ?>"
                                                    data-year="<?= (int)$record['year'] ?>"
                                                    data-date="<?= pinkamEscape($record['held_on']) ?>"
                                                    data-amount="<?= pinkamEscape($record['amount']) ?>"
                                                    data-principal="<?= (int)$record['principal_dayakaya_id'] ?>"
                                                    data-notes="<?= pinkamEscape($record['notes'] ?? '') ?>"
                                                    data-donors="<?= pinkamEscape($record['co_donor_ids']) ?>">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>

                                            <!-- DELETE BUTTON -->
                                            <form method="post"
                                                  action="manage_pinkam.php"
                                                  onsubmit="return confirm('මෙම පිංකම් වාර්තාව සහ එහි දායක සම්බන්ධතා මකා දැමීමට අවශ්‍යද?');">

                                                <input type="hidden" name="csrf_token"
                                                       value="<?= pinkamEscape($csrfToken) ?>">

                                                <input type="hidden" name="action"
                                                       value="delete_registration">

                                                <input type="hidden" name="registration_id"
                                                       value="<?= (int)$record['id'] ?>">

                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="වාර්තාව මකන්න">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- PINKAM TYPES -->
            <section id="pinkamTypes" class="card pinkam-panel shadow-sm">
                <div class="card-header">
                    <h2 class="h6 fw-bold mb-1">පිංකම් වර්ග කළමනාකරණය</h2>
                    <div class="pinkam-muted small">
                        වර්ග එකතු කරන්න, නම වෙනස් කරන්න, සක්‍රීය/අක්‍රීය කරන්න හෝ මකා දමන්න.
                    </div>
                </div>

                <div class="card-body p-3 p-lg-4">

                    <!-- ADD TYPE -->
                    <form method="post"
                          action="manage_pinkam.php"
                          class="row g-2 align-items-end mb-4 no-print">

                        <input type="hidden" name="csrf_token"
                               value="<?= pinkamEscape($csrfToken) ?>">

                        <input type="hidden" name="action" value="add_type">

                        <div class="col-md-8 col-lg-9">
                            <label class="form-label fw-semibold">
                                නව පිංකම් වර්ගයේ නම
                            </label>

                            <input type="text"
                                   name="type_name"
                                   class="form-control"
                                   maxlength="150"
                                   placeholder="උදා: වාර්ෂික පිංකම"
                                   required>
                        </div>

                        <div class="col-md-4 col-lg-3">
                            <button type="submit"
                                    class="btn btn-primary w-100">
                                <i class="fa-solid fa-plus me-2"></i>
                                වර්ගය එකතු කරන්න
                            </button>
                        </div>
                    </form>

                    <!-- TYPE LIST -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:65px">#</th>
                                    <th>පිංකම් වර්ගය</th>
                                    <th>තත්ත්වය</th>
                                    <th class="no-print">ක්‍රියා</th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php if (empty($types)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        පිංකම් වර්ග නැහැ.
                                    </td>
                                </tr>
                            <?php else: ?>

                                <?php foreach ($types as $index => $type): ?>
                                    <tr class="pinkam-type-row">
                                        <td><?= $index + 1 ?></td>

                                        <td class="fw-semibold">
                                            <?= pinkamEscape($type['name']) ?>
                                        </td>

                                        <td>
                                            <?php if ($type['status'] === 'active'): ?>
                                                <span class="badge text-bg-success">
                                                    සක්‍රීය
                                                </span>
                                            <?php else: ?>
                                                <span class="badge text-bg-secondary">
                                                    අක්‍රීය
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="no-print">
                                            <div class="d-flex flex-wrap gap-2">

                                                <!-- EDIT TYPE -->
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-primary"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editTypeModal"
                                                        data-id="<?= (int)$type['id'] ?>"
                                                        data-name="<?= pinkamEscape($type['name']) ?>">
                                                    <i class="fa-solid fa-pen me-1"></i>
                                                    Edit
                                                </button>

                                                <!-- TOGGLE TYPE -->
                                                <form method="post"
                                                      action="manage_pinkam.php">

                                                    <input type="hidden" name="csrf_token"
                                                           value="<?= pinkamEscape($csrfToken) ?>">

                                                    <input type="hidden" name="action"
                                                           value="toggle_type">

                                                    <input type="hidden" name="type_id"
                                                           value="<?= (int)$type['id'] ?>">

                                                    <input type="hidden" name="new_status"
                                                           value="<?= $type['status'] === 'active' ? 'inactive' : 'active' ?>">

                                                    <button type="submit"
                                                            class="btn btn-sm btn-outline-secondary">
                                                        <i class="fa-solid fa-toggle-on me-1"></i>
                                                        <?= $type['status'] === 'active'
                                                            ? 'අක්‍රීය කරන්න'
                                                            : 'සක්‍රීය කරන්න' ?>
                                                    </button>
                                                </form>

                                                <!-- DELETE TYPE -->
                                                <form method="post"
                                                      action="manage_pinkam.php"
                                                      onsubmit="return confirm('මෙම පිංකම් වර්ගය මකා දැමීමට අවශ්‍යද? භාවිතයේ තිබේ නම් මකා දැමිය නොහැක.');">

                                                    <input type="hidden" name="csrf_token"
                                                           value="<?= pinkamEscape($csrfToken) ?>">

                                                    <input type="hidden" name="action"
                                                           value="delete_type">

                                                    <input type="hidden" name="type_id"
                                                           value="<?= (int)$type['id'] ?>">

                                                    <button type="submit"
                                                            class="btn btn-sm btn-outline-danger">
                                                        <i class="fa-solid fa-trash me-1"></i>
                                                        මකන්න
                                                    </button>
                                                </form>

                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <div class="text-center pinkam-muted small mt-4">
                Temple Data Management System · පිංකම් කළමනාකරණය
            </div>

        </div>
    </main>
</div>

<!-- =====================================================
     EDIT REGISTRATION MODAL
====================================================== -->

<div class="modal fade"
     id="editRegistrationModal"
     tabindex="-1"
     aria-labelledby="editRegistrationTitle"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <form method="post" action="manage_pinkam.php">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold" id="editRegistrationTitle">
                            පිංකම් වාර්තාව සංස්කරණය කරන්න
                        </h5>
                        <div class="small text-secondary">
                            වෙනස්කම් save කළ පසු වාර්තාව යාවත්කාලීන වේ.
                        </div>
                    </div>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="csrf_token"
                           value="<?= pinkamEscape($csrfToken) ?>">

                    <input type="hidden" name="action"
                           value="edit_registration">

                    <input type="hidden" name="registration_id"
                           id="edit_registration_id">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                පිංකම් වර්ගය *
                            </label>

                            <?php /* FIX: list ALL types here. Inactive ones are
                                     labelled so an old record keeps its type;
                                     the server only requires "active" when the
                                     type is actually changed. */ ?>
                            <select name="pinkam_type_id"
                                    id="edit_type_id"
                                    class="form-select"
                                    required>
                                <option value="">වර්ගය තෝරන්න</option>

                                <?php foreach ($types as $type): ?>
                                    <option value="<?= (int)$type['id'] ?>">
                                        <?= pinkamEscape($type['name']) ?>
                                        <?= $type['status'] === 'active' ? '' : ' (අක්‍රීය)' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                වර්ෂය *
                            </label>

                            <input type="number"
                                   name="year"
                                   id="edit_year"
                                   class="form-control"
                                   min="1900"
                                   max="2200"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                දිනය *
                            </label>

                            <input type="date"
                                   name="held_on"
                                   id="edit_held_on"
                                   class="form-control"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                මුදල (Rs.) *
                            </label>

                            <input type="number"
                                   name="amount"
                                   id="edit_amount"
                                   class="form-control"
                                   min="0"
                                   max="9999999999.99"
                                   step="0.01"
                                   required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                ප්‍රධාන දායකයා *
                            </label>

                            <select name="principal_dayakaya_id"
                                    id="edit_principal_id"
                                    class="form-select"
                                    required>
                                <option value="">දායකයා තෝරන්න</option>

                                <?php foreach ($editDonors as $donor): ?>
                                    <option value="<?= (int)$donor['id'] ?>">
                                        <?= pinkamEscape($donor['full_name']) ?>
                                        <?= $donor['status'] === 'approved' ? '' : ' (අනුමත නොවූ)' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                අනෙකුත් දායකයන්
                            </label>

                            <div class="pinkam-donor-list">
                                <div class="row g-2">
                                    <?php foreach ($editDonors as $donor): ?>
                                        <div class="col-md-6 pinkam-donor-item">
                                            <label class="form-check mb-0">
                                                <input
                                                    class="form-check-input edit-donor-checkbox"
                                                    type="checkbox"
                                                    name="donor_ids[]"
                                                    value="<?= (int)$donor['id'] ?>">

                                                <span class="form-check-label">
                                                    <?= pinkamEscape($donor['full_name']) ?>
                                                    <?= $donor['status'] === 'approved' ? '' : ' (අනුමත නොවූ)' ?>
                                                </span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                සටහන්
                            </label>

                            <textarea name="notes"
                                      id="edit_notes"
                                      class="form-control"
                                      rows="3"
                                      maxlength="5000"></textarea>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">
                        අවලංගු කරන්න
                    </button>

                    <button type="submit"
                            class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk me-2"></i>
                        වෙනස්කම් සුරකින්න
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<!-- =====================================================
     EDIT TYPE MODAL
====================================================== -->

<div class="modal fade"
     id="editTypeModal"
     tabindex="-1"
     aria-labelledby="editTypeTitle"
     aria-hidden="true">

    <div class="modal-dialog">
        <div class="modal-content">

            <form method="post" action="manage_pinkam.php">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="editTypeTitle">
                        පිංකම් වර්ගය සංස්කරණය කරන්න
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="csrf_token"
                           value="<?= pinkamEscape($csrfToken) ?>">

                    <input type="hidden" name="action"
                           value="edit_type">

                    <input type="hidden" name="type_id"
                           id="edit_type_record_id">

                    <label class="form-label fw-semibold">
                        පිංකම් වර්ගයේ නම *
                    </label>

                    <input type="text"
                           name="type_name"
                           id="edit_type_name"
                           class="form-control"
                           maxlength="150"
                           required>
                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">
                        අවලංගු කරන්න
                    </button>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk me-2"></i>
                        නම යාවත්කාලීන කරන්න
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ----------------------------------------------
       TABLE SEARCH AND FILTERS
    ---------------------------------------------- */

    const searchInput = document.getElementById('pinkamSearch');
    const yearFilter = document.getElementById('filterYear');
    const typeFilter = document.getElementById('filterType');
    const table = document.getElementById('pinkamTable');

    function filterPinkamRows() {
        if (!table) return;

        const query = (searchInput?.value || '')
            .toLocaleLowerCase()
            .trim();

        const selectedYear = yearFilter?.value || '';
        const selectedType = typeFilter?.value || '';

        table.querySelectorAll('tbody tr').forEach(function (row) {
            // FIX: leave the "no records" placeholder row alone.
            if (row.classList.contains('pinkam-empty-row')) return;

            const rowText = row.innerText.toLocaleLowerCase();
            const rowYear = row.dataset.year || '';
            const rowType = row.dataset.type || '';

            const matchesText = rowText.includes(query);
            const matchesYear = !selectedYear || rowYear === selectedYear;
            const matchesType = !selectedType || rowType === selectedType;

            row.style.display =
                matchesText && matchesYear && matchesType ? '' : 'none';
        });
    }

    searchInput?.addEventListener('input', filterPinkamRows);
    yearFilter?.addEventListener('change', filterPinkamRows);
    typeFilter?.addEventListener('change', filterPinkamRows);

    /* ----------------------------------------------
       ADD FORM: year follows the selected date
       (user can still override the year manually afterwards)
    ---------------------------------------------- */

    const addDate = document.getElementById('add_held_on');
    const addYear = document.getElementById('add_year');

    addDate?.addEventListener('change', function () {
        const parts = (addDate.value || '').split('-');
        if (parts[0] && addYear) {
            addYear.value = parts[0];
        }
    });

    /* ----------------------------------------------
       EDIT REGISTRATION MODAL
    ---------------------------------------------- */

    const editRegistrationModal =
        document.getElementById('editRegistrationModal');

    if (editRegistrationModal) {
        editRegistrationModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            if (!button) return;

            document.getElementById('edit_registration_id').value =
                button.dataset.id || '';

            document.getElementById('edit_type_id').value =
                button.dataset.type || '';

            document.getElementById('edit_year').value =
                button.dataset.year || '';

            document.getElementById('edit_held_on').value =
                button.dataset.date || '';

            document.getElementById('edit_amount').value =
                button.dataset.amount || '0.00';

            document.getElementById('edit_principal_id').value =
                button.dataset.principal || '';

            document.getElementById('edit_notes').value =
                button.dataset.notes || '';

            const selectedDonors = (button.dataset.donors || '')
                .split(',')
                .map(function (id) {
                    return id.trim();
                })
                .filter(Boolean);

            editRegistrationModal
                .querySelectorAll('.edit-donor-checkbox')
                .forEach(function (checkbox) {
                    checkbox.checked = selectedDonors.includes(checkbox.value);
                });
        });
    }

    /* ----------------------------------------------
       EDIT TYPE MODAL
    ---------------------------------------------- */

    const editTypeModal = document.getElementById('editTypeModal');

    if (editTypeModal) {
        editTypeModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            if (!button) return;

            document.getElementById('edit_type_record_id').value =
                button.dataset.id || '';

            document.getElementById('edit_type_name').value =
                button.dataset.name || '';
        });
    }

});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>