<?php
/**
 * නෙත්සිත ජෝතිශ්‍ය සේවය - පරිපාලක සැකසුම් (මුරපදය වෙනස් කිරීම)
 * මෙම පිටුව පරිපාලක මුරපදය වෙනස් කිරීමට භාවිතා කරයි.
 */

// පරිපාලක ශීර්ෂය ඇතුළත් කරන්න
include('includes/header.php'); 

$message = '';

$admin_id = $_SESSION['admin_id'] ?? 0;

// $conn වස්තුව header.php මගින් ඇතුළත් විය යුතුය.
if (isset($conn) && $conn->connect_error) {
    $message = '<div class="alert alert-danger">දත්ත සමුදාය සම්බන්ධ කිරීමේ දෝෂයයි.</div>';
    $conn = null;
}

// --------------------------------------------------------
// මුරපදය වෙනස් කිරීමේ Logic
// --------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // මූලික ආදාන පරීක්ෂා කිරීම
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = '<div class="alert alert-warning">කරුණාකර සියලුම ක්ෂේත්‍ර පුරවන්න.</div>';
    } elseif ($new_password !== $confirm_password) {
        $message = '<div class="alert alert-danger">නව මුරපදය සහ තහවුරු කිරීමේ මුරපදය නොගැලපේ.</div>';
    } elseif (strlen($new_password) < 6) {
        $message = '<div class="alert alert-danger">මුරපදය අවම වශයෙන් අකුරු 6 ක්වත් තිබිය යුතුය.</div>';
    } else {
        // 1. වත්මන් මුරපදය දත්ත සමුදායෙන් ලබා ගැනීම
        $stmt_select = $conn->prepare("SELECT password_hash FROM admins WHERE id = ?");
        $stmt_select->bind_param("i", $admin_id);
        $stmt_select->execute();
        $result = $stmt_select->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            $hashed_password = $admin['password_hash'];
            $stmt_select->close(); // Statement එක වසා දැමීම

            // 2. වත්මන් මුරපදය තහවුරු කිරීම
            if (password_verify($current_password, $hashed_password)) {
                
                // 3. නව මුරපදය HASH කර යාවත්කාලීන කිරීම
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $stmt_update = $conn->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
                $stmt_update->bind_param("si", $new_hashed_password, $admin_id);

                if ($stmt_update->execute()) {
                    // --------------------------------------------------------
                    // ✅ මුරපදය සාර්ථකව වෙනස් කිරීමෙන් පසු LOGOUT වීම
                    // --------------------------------------------------------
                    
                    // Session එක අවසන් කිරීම
                    session_unset();
                    session_destroy();

                    // සාර්ථක පණිවිඩයක් සමඟින් Login පිටුවට යොමු කිරීම
                    // success=1 යොමු කරන්නේ login පිටුවේදී සාර්ථක පණිවිඩය පෙන්වීමටය.
                    header('Location: login.php?success=password_changed');
                    exit;
                    
                } else {
                    $message = '<div class="alert alert-danger">මුරපදය යාවත්කාලීන කිරීමේ දෝෂයකි: ' . $stmt_update->error . '</div>';
                }
                $stmt_update->close();
                
            } else {
                $message = '<div class="alert alert-danger">වත්මන් මුරපදය වැරදියි.</div>';
            }
        } else {
            // මෙම පරිපාලක ID එක සොයා ගැනීමට නොහැකි වීම අසාමාන්‍ය තත්වයකි
            $message = '<div class="alert alert-danger">පරිපාලක තොරතුරු සොයා ගැනීමට නොහැක.</div>';
        }
        // $stmt_select->close() දැනටමත් ඉහත වසා ඇත.
    }
}
?>
<div class="container my-5">

    <h2 class="mb-4"><i class="fas fa-cog"></i> පරිපාලක සැකසුම්</h2>
    <hr>

    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-key"></i> මුරපදය වෙනස් කිරීම</h5>
                </div>
                <div class="card-body">
                    <?php echo $message; ?>

                    <form method="POST" action="">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">වත්මන් මුරපදය</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">නව මුරපදය (අවම 6)</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">නව මුරපදය තහවුරු කරන්න</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>මුරපදය වෙනස් කරන්න
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// පරිපාලක පාදකය ඇතුළත් කරන්න (footer.php මඟින් $conn->close() සිදු කරනු ඇත)
include('includes/footer.php');
?>