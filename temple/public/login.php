<?php
declare(strict_types=1);

/**
 * Authentication Engine
 * Temple Dayaka Management System
 */

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

// Session must be started by app.php or the application's bootstrap.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Redirect an already authenticated user.
if (!empty($_SESSION['user_id'])) {
    redirect(ADMIN_URL . '/dashboard.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF protection
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new RuntimeException(
                'ආරක්ෂිත Token එක කල් ඉකුත් වී හෝ වලංගු නොවේ. පිටුව Refresh කර නැවත උත්සාහ කරන්න.'
            );
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        // Do not trim passwords; spaces can be part of a password.
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            throw new RuntimeException(
                'පරිශීලක නාමය සහ මුරපදය ඇතුළත් කරන්න.'
            );
        }

        // Connect to the configured database.
        $db = Database::getConnection();

        // Find the account.
        $stmt = $db->prepare(
            'SELECT id, username, email, password, role, status
             FROM users
             WHERE username = :username
             LIMIT 1'
        );

        $stmt->execute([
            'username' => $username
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Use a generic message for invalid credentials.
        if (
            !$user ||
            !isset($user['password']) ||
            !password_verify($password, (string) $user['password'])
        ) {
            throw new RuntimeException(
                'ඇතුළත් කළ පරිශීලක නාමය හෝ මුරපදය වැරදියි.'
            );
        }

        // Only active accounts may log in.
        if (($user['status'] ?? '') !== 'active') {
            if (($user['status'] ?? '') === 'suspended') {
                throw new RuntimeException(
                    'ඔබගේ ගිණුම අත්හිටුවා ඇත. Super Admin අමතන්න.'
                );
            }

            if (($user['status'] ?? '') === 'pending') {
                throw new RuntimeException(
                    'ඔබගේ ගිණුම තවමත් අනුමත කර නොමැත.'
                );
            }

            throw new RuntimeException(
                'ඔබගේ ගිණුම සක්‍රිය නොවේ. පද්ධති පරිපාලක අමතන්න.'
            );
        }

        // Regenerate the session ID after successful authentication.
        if (!session_regenerate_id(true)) {
            throw new RuntimeException(
                'Session එක සකස් කළ නොහැක. නැවත උත්සාහ කරන්න.'
            );
        }

        // Store authenticated user details.
        $_SESSION['user_id'] = $user['id'];

        $_SESSION['user'] = [
            'id'       => $user['id'],
            'username' => $user['username'],
            'email'    => $user['email'] ?? '',
            'role'     => $user['role'] ?? ''
        ];

        // Redirect before rendering any HTML.
        redirect(ADMIN_URL . '/dashboard.php');
        exit;

    } catch (RuntimeException $e) {
        $error = $e->getMessage();

    } catch (Throwable $e) {
        // Record technical details in the server error log,
        // never display database credentials or internal errors.
        error_log(
            'Login error: ' . get_class($e) . ': ' . $e->getMessage()
        );

        $error = 'පද්ධතියේ දෝෂයක් ඇතිවිය. කරුණාකර පසුව නැවත උත්සාහ කරන්න.';
    }
}

$csrfToken = generateCSRFToken();

// Render HTML only after authentication and redirects are handled.
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">

            <div class="card shadow-lg border-0 rounded-3">

                <div class="card-header bg-warning bg-gradient
                            text-dark text-center p-4 rounded-top">

                    <div class="mb-2">
                        <i class="fa-solid fa-gopuram fs-1"></i>
                    </div>

                    <h4 class="fw-bold mb-0">
                        <?= e(APP_NAME) ?>
                    </h4>

                    <small class="text-muted fw-bold">
                        පරිපාලන පිවිසුම (System Login)
                    </small>
                </div>

                <div class="card-body p-4">

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger alert-dismissible
                                    fade show p-3 small"
                             role="alert">

                            <i class="fa-solid fa-circle-exclamation me-2"></i>
                            <?= e($error) ?>

                            <button type="button"
                                    class="btn-close"
                                    data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST"
                          action=""
                          autocomplete="on">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?= e($csrfToken) ?>">

                        <div class="mb-3">
                            <label for="username"
                                   class="form-label fw-bold small text-secondary">
                                පරිශීලක නාමය (Username) *
                            </label>

                            <div class="input-group">
                                <span class="input-group-text bg-light
                                             border-end-0">
                                    <i class="fa-solid fa-user text-muted"></i>
                                </span>

                                <input type="text"
                                       id="username"
                                       name="username"
                                       class="form-control border-start-0"
                                       required
                                       autofocus
                                       autocomplete="username"
                                       placeholder="Username"
                                       value="<?= e($username) ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password"
                                   class="form-label fw-bold small text-secondary">
                                මුරපදය (Password) *
                            </label>

                            <div class="input-group">
                                <span class="input-group-text bg-light
                                             border-end-0">
                                    <i class="fa-solid fa-lock text-muted"></i>
                                </span>

                                <input type="password"
                                       id="password"
                                       name="password"
                                       class="form-control border-start-0"
                                       required
                                       autocomplete="current-password"
                                       placeholder="Password">
                            </div>
                        </div>

                        <button type="submit"
                                class="btn btn-warning w-100 fw-bold py-2 shadow-sm">
                            <i class="fa-solid fa-right-to-bracket me-2"></i>
                            ඇතුළු වන්න (Login)
                        </button>

                    </form>
                </div>

                <div class="card-footer bg-light p-3 text-center
                            border-0 rounded-bottom">
                    <small class="text-muted">
                        <i class="fa-solid fa-shield-halved me-1"></i>
                        ආරක්ෂිත පරිපාලන පිවිසුම
                    </small>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>