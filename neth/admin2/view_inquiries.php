<?php
/**
 * නෙත්සිත ජෝතිශ්‍ය සේවය - Contact Inquiries Viewer
 * මෙම පිටුව contact.php මගින් ලැබුණු සියලුම විමසීම් පෙන්වයි.
 */

// පරිපාලක ශීර්ෂය සහ දත්ත සමුදා සම්බන්ධතාවය ඇතුළත් කරන්න
// header.php මගින් Session පාලනය සහ db_connection.php ඇතුළත් විය යුතුය.
include('includes/header.php'); 

$message = '';

// $conn යනු header.php මගින් ඇතුළත් වූ db_connection.php වෙතින් ලැබෙන සම්බන්ධතාවය විය යුතුය.
// දත්ත සමුදා සම්බන්ධතාවය header.php තුළ පරීක්ෂා කර ඇතැයි උපකල්පනය කරයි.
// $conn වස්තුව නොමැති නම් (සම්බන්ධතා දෝෂයක් ඇත්නම්) පණිවිඩයක් පෙන්වන්න.
if (!isset($conn) || $conn->connect_error) {
    $message = '<div class="alert alert-danger">දත්ත සමුදාය සම්බන්ධ කිරීමේ දෝෂයයි. විමසීම් පෙන්විය නොහැක.</div>';
    $conn = null; // $conn නැවතත් null ලෙස සකසයි
}

// --- Status UPDATE Logic (Mark as Read) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id']) && $conn) {
    $inquiry_id = (int)$_POST['mark_read_id'];
    
    $stmt = $conn->prepare("UPDATE contact_inquiries SET is_read = 1 WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $inquiry_id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success alert-custom fade-in">
                            <i class="fas fa-check-circle me-2"></i>විමසීම **කියවූ සේ සලකුණු** කරන ලදී.
                        </div>';
        } else {
            $message = '<div class="alert alert-danger alert-custom fade-in">
                            <i class="fas fa-times-circle me-2"></i>දෝෂයක්: ' . $stmt->error . '
                        </div>';
        }
        $stmt->close();
    }
}

// --- Data Retrieval ---
$result = false;
if ($conn) {
    // is_read = 0 (කියවා නැති) ඒවා මුලින්ම පෙන්වා, පසුව දිනය අනුව අලුත් ඒවා මුලට පෙන්වයි.
    $sql = "SELECT * FROM contact_inquiries ORDER BY is_read ASC, created_at DESC"; 
    $result = $conn->query($sql);
}

?>

<style>
    /* CSS for the alert fade-in effect */
    .alert-custom {
        animation: fadeIn 0.5s ease-in-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .table-warning {
        --bs-table-bg-state: #fff3cd; /* තරමක් ලා කහ පැහැය (අලුත් ඉල්ලීම් සඳහා) */
        font-weight: 600;
    }
</style>

<div class="container my-5">
    <h2 class="mb-4"><i class="fas fa-comment-dots"></i> සම්බන්ධතා විමසීම් (Contact Inquiries)</h2>
    <hr>

    <?php echo $message; ?>

    <div class="table-responsive fade-in">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>දිනය</th>
                    <th>නම</th>
                    <th>දුරකථන</th>
                    <th>විෂයය</th>
                    <th>තත්ත්වය</th>
                    <th style="width: 150px;">ක්‍රියාකාරීත්වය</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                    // is_read = 0 නම්, කියවා නැති ලෙස සලකුණු කරන්න
                    $row_class = $row['is_read'] ? 'table-light' : 'table-warning';
                    $status_badge = $row['is_read'] ? '<span class="badge bg-success"><i class="fas fa-check"></i> කියවා ඇත</span>' : '<span class="badge bg-danger"><i class="fas fa-star"></i> අලුත්!</span>';
                ?>
                <tr class="<?php echo $row_class; ?>">
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td><?php echo htmlspecialchars($row['subject'] ?: 'N/A'); ?></td>
                    <td><?php echo $status_badge; ?></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-info text-white view-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#inquiryModal"
                            data-id="<?php echo $row['id']; ?>"
                            data-name="<?php echo htmlspecialchars($row['name']); ?>"
                            data-email="<?php echo htmlspecialchars($row['email'] ?: 'N/A'); ?>"
                            data-phone="<?php echo htmlspecialchars($row['phone']); ?>"
                            data-subject="<?php echo htmlspecialchars($row['subject'] ?: 'N/A'); ?>"
                            data-message="<?php echo htmlspecialchars($row['message']); ?>"
                            data-is-read="<?php echo $row['is_read']; ?>">
                            <i class="fas fa-eye"></i> බලන්න
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center">කිසිදු සම්බන්ධතා විමසීමක් නොමැත.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="inquiryModalLabel">විමසීම් විස්තර #<span id="modal-inquiry-id-display"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6"><p><strong>නම:</strong> <span id="modal-name"></span></p></div>
                    <div class="col-md-6"><p><strong>දුරකථන:</strong> <span id="modal-phone"></span></p></div>
                    <div class="col-md-6"><p><strong>විද්‍යුත් තැපෑල:</strong> <span id="modal-email"></span></p></div>
                    <div class="col-md-6"><p><strong>විෂයය:</strong> <span id="modal-subject"></span></p></div>
                </div>
                
                <p><strong>පණිවිඩය:</strong></p>
                <textarea class="form-control mb-3" id="modal-message" rows="6" readonly></textarea>
                
                <div id="modal-read-status" class="mt-3 p-2 border rounded"></div>

            </div>
            <div class="modal-footer">
                <form method="POST" action="">
                    <input type="hidden" name="mark_read_id" id="modal-mark-read-id">
                    <button type="submit" id="mark-read-btn" class="btn btn-success d-none">
                        <i class="fas fa-check-double me-2"></i>කියවූ සේ සලකුණු කරන්න
                    </button>
                </form>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">වසා දමන්න</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var inquiryModal = document.getElementById('inquiryModal');
    if (inquiryModal) {
        inquiryModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            
            // Data retrieval from the button's data attributes
            var id = button.getAttribute('data-id');
            var isRead = button.getAttribute('data-is-read');

            // Populate Modal Content
            document.getElementById('modal-inquiry-id-display').textContent = id;
            document.getElementById('modal-name').textContent = button.getAttribute('data-name');
            document.getElementById('modal-email').textContent = button.getAttribute('data-email');
            document.getElementById('modal-phone').textContent = button.getAttribute('data-phone');
            document.getElementById('modal-subject').textContent = button.getAttribute('data-subject');
            document.getElementById('modal-message').value = button.getAttribute('data-message');
            
            // Hidden input for POST request
            document.getElementById('modal-mark-read-id').value = id;

            // Read Status and Button Logic
            var markReadBtn = document.getElementById('mark-read-btn');
            var readStatusDiv = document.getElementById('modal-read-status');

            if (isRead === '0') {
                markReadBtn.classList.remove('d-none'); // Show the button
                readStatusDiv.innerHTML = '<p class="text-danger fw-bold mb-0"><i class="fas fa-exclamation-circle me-1"></i>මෙය තවමත් කියවා නැති අලුත් විමසීමකි.</p>';
                readStatusDiv.classList.add('bg-light-danger');
            } else {
                markReadBtn.classList.add('d-none'); // Hide the button
                readStatusDiv.innerHTML = '<p class="text-success fw-bold mb-0"><i class="fas fa-check-circle me-1"></i>මෙය දැනටමත් කියවා ඇත.</p>';
                readStatusDiv.classList.remove('bg-light-danger');
            }
        });
    }
});
</script>

<?php
// පරිපාලක පාදකය ඇතුළත් කරන්න (footer.php මඟින් $conn->close() සිදු කරනු ඇත)
include('includes/footer.php');
?>