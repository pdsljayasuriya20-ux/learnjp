<?php
/**
 * නෙත්සිත ජෝතිශ්‍ය සේවය - Contact Inquiries Viewer
 * මෙම පිටුව contact.php මගින් ලැබුණු සියලුම විමසීම් පෙන්වයි.
 */

// පරිපාලක ශීර්ෂය සහ දත්ත සමුදා සම්බන්ධතාවය ඇතුළත් කරන්න
include('includes/header.php'); 

$message = '';

// දත්ත සමුදා සම්බන්ධතාවය පරීක්ෂා කරන්න
if (!isset($conn) || $conn->connect_error) {
    $message = '<div class="alert alert-danger">දත්ත සමුදාය සම්බන්ධ කිරීමේ දෝෂයයි. විමසීම් පෙන්විය නොහැක.</div>';
    $conn = null;
}

// --- Status UPDATE Logic (Mark as Read) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id']) && $conn) {
    $inquiry_id = (int)$_POST['mark_read_id'];
    
    $stmt = $conn->prepare("UPDATE contact_inquiries SET is_read = 1 WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $inquiry_id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success alert-custom fade-in">
                            <i class="fas fa-check-circle me-2"></i>විමසීම කියවූ සේ සලකුණු කරන ලදී.
                        </div>';
        } else {
            $message = '<div class="alert alert-danger alert-custom fade-in">
                            <i class="fas fa-times-circle me-2"></i>දෝෂයක්: ' . $stmt->error . '
                        </div>';
        }
        $stmt->close();
    }
}

// --- DELETE Logic ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && $conn) {
    $inquiry_id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM contact_inquiries WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $inquiry_id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success alert-custom">
                            <i class="fas fa-check-circle me-2"></i>විමසීම සාර්ථකව ඉවත් කරන ලදී.
                        </div>';
        }
        $stmt->close();
    }
}

// --- Data Retrieval ---
$result = false;
$unread_count = 0;
$total_count = 0;

if ($conn) {
    // Get counts
    $unread_count = $conn->query("SELECT COUNT(*) as count FROM contact_inquiries WHERE is_read = 0")->fetch_assoc()['count'];
    $total_count = $conn->query("SELECT COUNT(*) as count FROM contact_inquiries")->fetch_assoc()['count'];
    
    // Get inquiries
    $sql = "SELECT * FROM contact_inquiries ORDER BY is_read ASC, created_at DESC"; 
    $result = $conn->query($sql);
}

?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>සම්බන්ධතා විමසීම් - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
      <link rel="stylesheet" href="css-main.css">
   
</head>
<body>
    <div class="inquiries-container">
        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-comments me-2"></i>සම්බන්ධතා විමසීම්</h2>
            <p>ගනුදෙනුකරුවන්ගේ විමසීම් බලන්න සහ කළමනාකරණය කරන්න</p>
        </div>

        <?php echo $message; ?>

        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat-box total">
                <div class="stat-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="stat-info">
                    <h6>මුළු විමසීම්</h6>
                    <div class="stat-value"><?php echo $total_count; ?></div>
                </div>
            </div>
            <div class="stat-box unread">
                <div class="stat-icon">
                    <i class="fas fa-envelope-open-text"></i>
                </div>
                <div class="stat-info">
                    <h6>කියවා නැති</h6>
                    <div class="stat-value"><?php echo $unread_count; ?></div>
                </div>
            </div>
            <div class="stat-box read">
                <div class="stat-icon">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="stat-info">
                    <h6>කියවා ඇති</h6>
                    <div class="stat-value"><?php echo $total_count - $unread_count; ?></div>
                </div>
            </div>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="inquiries-grid">
                <?php while ($row = $result->fetch_assoc()): 
                    $is_unread = !$row['is_read'];
                    $card_class = $is_unread ? 'unread' : 'read';
                    $badge_class = $is_unread ? 'unread' : 'read';
                    $badge_text = $is_unread ? '⭐ අලුත්' : '✓ කියවා ඇත';
                ?>
                    <div class="inquiry-card <?php echo $card_class; ?>">
                        <span class="inquiry-badge <?php echo $badge_class; ?>">
                            <?php echo $badge_text; ?>
                        </span>

                        <div class="inquiry-header">
                            <div class="inquiry-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div style="flex: 1;">
                                <h3 class="inquiry-name"><?php echo htmlspecialchars($row['name']); ?></h3>
                                <div class="inquiry-date">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?>
                                </div>
                            </div>
                        </div>

                        <div class="inquiry-contact">
                            <?php if($row['email']): ?>
                            <div class="inquiry-contact-item">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo htmlspecialchars($row['email']); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="inquiry-contact-item">
                                <i class="fab fa-whatsapp"></i>
                                <span><?php echo htmlspecialchars($row['phone']); ?></span>
                            </div>
                        </div>

                        <?php if($row['subject']): ?>
                        <div class="inquiry-subject">
                            <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($row['subject']); ?>
                        </div>
                        <?php endif; ?>

                        <div class="inquiry-message">
                            <?php echo htmlspecialchars(substr($row['message'], 0, 120)) . (strlen($row['message']) > 120 ? '...' : ''); ?>
                        </div>

                        <div class="inquiry-actions">
                            <button type="button" class="btn-view"
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
                            <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="btn-delete">
                                <i class="fas fa-trash"></i> ඉවත් කරන්න
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h4>විමසීම් නොමැත</h4>
                <p>තවමත් කිසිදු සම්බන්ධතා විමසීමක් ලැබී නොමැත.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="inquiryModalLabel">
                        <i class="fas fa-envelope-open-text me-2"></i>විමසීම් විස්තර #<span id="modal-inquiry-id-display"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="info-section">
                        <div class="info-row">
                            <div class="info-item">
                                <div class="info-label"><i class="fas fa-user me-1"></i>නම</div>
                                <div class="info-value" id="modal-name"></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label"><i class="fas fa-envelope me-1"></i>Email</div>
                                <div class="info-value" id="modal-email"></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label"><i class="fab fa-whatsapp me-1"></i>දුරකථන</div>
                                <div class="info-value" id="modal-phone"></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label"><i class="fas fa-tag me-1"></i>විෂයය</div>
                                <div class="info-value" id="modal-subject"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="info-label mb-2"><i class="fas fa-comment-dots me-1"></i>පණිවුඩය</div>
                        <div class="message-box" id="modal-message"></div>
                    </div>

                    <div id="modal-read-status" class="status-box"></div>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="mark_read_id" id="modal-mark-read-id">
                        <button type="submit" id="mark-read-btn" class="btn-mark-read" style="display: none;">
                            <i class="fas fa-check-double me-2"></i>කියවූ සේ සලකුණු කරන්න
                        </button>
                    </form>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>වසා දමන්න
                    </button>
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
                    
                    var id = button.getAttribute('data-id');
                    var isRead = button.getAttribute('data-is-read');

                    document.getElementById('modal-inquiry-id-display').textContent = id;
                    document.getElementById('modal-name').textContent = button.getAttribute('data-name');
                    document.getElementById('modal-email').textContent = button.getAttribute('data-email');
                    document.getElementById('modal-phone').textContent = button.getAttribute('data-phone');
                    document.getElementById('modal-subject').textContent = button.getAttribute('data-subject');
                    document.getElementById('modal-message').textContent = button.getAttribute('data-message');
                    
                    document.getElementById('modal-mark-read-id').value = id;

                    var markReadBtn = document.getElementById('mark-read-btn');
                    var readStatusDiv = document.getElementById('modal-read-status');

                    if (isRead === '0') {
                        markReadBtn.style.display = 'inline-block';
                        readStatusDiv.className = 'status-box unread';
                        readStatusDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i><p class="mb-0 fw-bold" style="color: #e55039;">මෙය තවමත් කියවා නැති අලුත් විමසීමකි.</p>';
                    } else {
                        markReadBtn.style.display = 'none';
                        readStatusDiv.className = 'status-box read';
                        readStatusDiv.innerHTML = '<i class="fas fa-check-circle"></i><p class="mb-0 fw-bold" style="color: #38ada9;">මෙය දැනටමත් කියවා ඇත.</p>';
                    }
                });
            }
        });

        function confirmDelete(id) {
            if(confirm('මෙම විමසීම ඉවත් කිරීමට ඔබ සහතිකද? මෙම ක්‍රියාව ආපසු හැරවිය නොහැක.')) {
                window.location.href = '?action=delete&id=' + id;
            }
        }

        // Auto-hide success messages
        setTimeout(function() {
            var alert = document.querySelector('.alert-success');
            if(alert) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);
    </script>
</body>
</html>

<?php
include('includes/footer.php');
?>