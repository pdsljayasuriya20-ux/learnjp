<?php
// Session ආරම්භ කිරීම (Login තත්ත්වය පවත්වා ගැනීමට)
session_start();

// දත්ත සමුදා සම්බන්ධතාවය ඇතුළත් කිරීම
// ඔබගේ db_connection.php ගොනුව admin ෆෝල්ඩරයෙන් පිටත තිබිය යුතුය.
include('../db_connection.php');

// $conn වස්තුව සාර්ථකව සම්බන්ධ නොවූයේ නම් පණිවිඩයක් සැකසීම
if (!isset($conn) || $conn->connect_error) {
    // දෝෂය අනිවාර්යයෙන්ම පෙන්වීම
    die("දත්ත සමුදාය සම්බන්ධ කිරීමේ දෝෂය: " . $conn->connect_error);
}

// පරිපාලක දැනටමත් ලොග් වී ඇත්නම්, dashboard වෙත යොමු කරන්න
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error_message = '';
$success_message = '';

// මුරපදය වෙනස් කිරීමෙන් පසු ලැබෙන සාර්ථක පණිවිඩය පරීක්ෂා කිරීම
if (isset($_GET['success']) && $_GET['success'] === 'password_changed') {
    $success_message = '✅ ඔබගේ මුරපදය සාර්ථකව වෙනස් කරන ලදී. කරුණාකර නව මුරපදය භාවිතා කර පිවිසෙන්න.';
}


// Form එක Submit කර ඇත්දැයි පරීක්ෂා කිරීම
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Real Escape String අනවශ්‍යයි, prepared statements භාවිතා කරන බැවින්.
    $username = $_POST['username'];
    $password = $_POST['password'];

    // දත්ත සමුදායෙන් පරිශීලකයා සොයා ගැනීම (admins වගුව භාවිතා කිරීම)
    $sql = "SELECT id, username, password_hash FROM admins WHERE username = ?";
    
    // වැදගත්: ඔබගේ admins වගුවේ මුරපද තීරුවේ නම 'password' වෙනුවට 'password_hash' විය යුතුය.
    // ඔබගේ settings.php කේතයට අනුකූලව මෙහි 'password_hash' ලෙස වෙනස් කර ඇත.
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // මුරපදය පරීක්ෂා කිරීම (Hash කළ මුරපදය සමඟ සසඳයි)
        if (password_verify($password, $user['password_hash'])) {
            // සාර්ථක නම් Session එක සකස් කිරීම
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];

            // Dashboard වෙත යොමු කිරීම
            header('Location: dashboard.php');
            exit;
        } else {
            // මුරපදය වැරදි නම්
            $error_message = 'වැරදි පරිශීලක නාමය හෝ මුරපදය.';
        }
    } else {
        // පරිශීලක නාමය වැරදි නම්
        $error_message = 'වැරදි පරිශීලක නාමය හෝ මුරපදය.';
    }
    $stmt->close();
}

// PHP කේතය අවසානයේ සම්බන්ධතාවය වසා දැමීම
$conn->close();
?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>පරිපාලක පිවිසුම - නෙත්සිත ජෝතිශ්‍ය සේවය</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
          
    <style>
        .login-container {
            max-width: 400px;
            margin-top: 100px;
            padding: 20px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        .login-logo {
            font-size: 2.5rem;
            color: #0d6efd; /* Bootstrap Primary color */
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 login-container bg-white">
                <div class="text-center">
                    <i class="fas fa-user-shield login-logo"></i>
                    <h3 class="mb-4">පරිපාලක පිවිසුම</h3>
                </div>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label for="username" class="form-label">පරිශීලක නාමය</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">මුරපදය</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-2">
                        <i class="fas fa-sign-in-alt me-2"></i>පිවිසෙන්න
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>