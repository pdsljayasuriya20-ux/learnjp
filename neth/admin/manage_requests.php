<?php
/**
 * නෙත්සිත ජෝතිශ්‍ය සේවය - Admin Client Requests Management
 * Manage client_requests table with status updates and file viewing
 */

// Include header with database connection
// Adjust path if file is in admin folder
if (file_exists('includes/header.php')) {
    include('includes/header.php');
} else if (file_exists('../includes/header.php')) {
    include('../includes/header.php');
} 

$message = '';

// Default filter: 'pending' (new requests)
$status_filter = isset($_GET['filter']) ? $conn->real_escape_string($_GET['filter']) : 'pending'; 

// --- 1. Status UPDATE Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $request_id = (int)$_POST['request_id'];
    $new_status = $conn->real_escape_string($_POST['new_status'] ?? '');
    $new_payment_status = $conn->real_escape_string($_POST['new_payment_status'] ?? '');
    $admin_notes = $conn->real_escape_string($_POST['admin_notes'] ?? '');
    
    // Build update query dynamically
    $update_fields = [];
    
    if (!empty($new_status)) {
        $update_fields[] = "status='$new_status'";
    }
    if (!empty($new_payment_status)) {
        $update_fields[] = "payment_status='$new_payment_status'";
    }
    if (!empty($admin_notes)) {
        $update_fields[] = "admin_notes='$admin_notes'";
    }

    if (!empty($update_fields)) {
        $sql = "UPDATE client_requests SET " . implode(", ", $update_fields) . " WHERE id=$request_id";
        
        if ($conn->query($sql)) {
            $message = '<div class="alert alert-success alert-custom fade-in">
                            <i class="fas fa-check-circle me-2"></i>ඉල්ලීමේ තත්ත්වය සාර්ථකව යාවත්කාලීන කරන ලදි.
                        </div>';
        } else {
            $message = '<div class="alert alert-danger alert-custom fade-in">
                            <i class="fas fa-times-circle me-2"></i>යාවත්කාලීන කිරීමේ දෝෂයක්: ' . $conn->error . '
                        </div>';
        }
    }
}

// --- 2. Data Retrieval ---
$where_clause = ($status_filter === 'All') ? "" : "WHERE cr.status = '$status_filter'";
$sql = "SELECT cr.*, s.service_name, s.category 
        FROM client_requests cr
        LEFT JOIN services s ON cr.service_id = s.id
        $where_clause 
        ORDER BY cr.request_date DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>සේවා ඉල්ලීම් කළමනාකරණය - Admin Panel</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .requests-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .page-header h2 {
            margin: 0;
            font-weight: bold;
        }
        
        .page-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }
        
        .filter-buttons {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            color: #666;
            background: #f8f9fa;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .filter-btn:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        .filter-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            overflow-x: auto;
        }
        
        .custom-table {
            width: 100%;
            margin: 0;
        }
        
        .custom-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .custom-table th {
            padding: 1rem;
            font-weight: 600;
            border: none;
        }
        
        .custom-table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .custom-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-badge.in_progress {
            background: #cfe2ff;
            color: #084298;
        }
        
        .status-badge.completed {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        .status-badge.rejected {
            background: #f8d7da;
            color: #842029;
        }
        
        .action-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }
        
        .info-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            color: #333;
            font-size: 1.1rem;
        }
        
        .file-info {
            background: #e7f3ff;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }
        
        .file-link {
            color: #0d6efd;
            text-decoration: none;
            font-weight: 600;
        }
        
        .file-link:hover {
            text-decoration: underline;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #999;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .alert-custom {
            border-radius: 10px;
            border-left: 4px solid;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="requests-container">
        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-clipboard-list me-2"></i>සේවා ඉල්ලීම් කළමනාකරණය</h2>
            <p>ගනුදෙනුකරුවන්ගේ සේවා ඉල්ලීම් පරීක්ෂා කර කළමනාකරණය කරන්න</p>
        </div>

        <?php echo $message; ?>

        <!-- Filter Buttons -->
        <div class="filter-buttons">
            <a href="manage_requests.php?filter=pending" 
               class="filter-btn <?php echo ($status_filter=='pending'?'active':''); ?>">
                <i class="fas fa-clock me-1"></i> අපේක්ෂිත
            </a>
            <a href="manage_requests.php?filter=in_progress" 
               class="filter-btn <?php echo ($status_filter=='in_progress'?'active':''); ?>">
                <i class="fas fa-spinner me-1"></i> ක්‍රියාත්මක වෙමින්
            </a>
            <a href="manage_requests.php?filter=completed" 
               class="filter-btn <?php echo ($status_filter=='completed'?'active':''); ?>">
                <i class="fas fa-check-circle me-1"></i> සම්පූර්ණයි
            </a>
            <a href="manage_requests.php?filter=rejected" 
               class="filter-btn <?php echo ($status_filter=='rejected'?'active':''); ?>">
                <i class="fas fa-times-circle me-1"></i> ප්‍රතික්ෂේපිත
            </a>
            <a href="manage_requests.php?filter=All" 
               class="filter-btn <?php echo ($status_filter=='All'?'active':''); ?>">
                <i class="fas fa-list me-1"></i> සියල්ල
            </a>
        </div>

        <!-- Table Container -->
        <div class="table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>දිනය</th>
                        <th>නම</th>
                        <th>WhatsApp</th>
                        <th>සේවාව</th>
                        <th>ගෙවීම</th>
                        <th>තත්ත්වය</th>
                        <th>ක්‍රියාකාරීත්වය</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): 
                        $status = $row['status'] ?? 'pending';
                        $payment_status = $row['payment_status'] ?? 'pending';
                    ?>
                    <tr>
                        <td><strong>#<?php echo $row['id']; ?></strong></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($row['request_date'])); ?></td>
                        <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                        <td>
                            <i class="fab fa-whatsapp text-success me-1"></i>
                            <?php echo htmlspecialchars($row['contact_whatsapp']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['service_name'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="status-badge <?php echo ($payment_status === 'paid' || $payment_status === 'verified') ? 'completed' : 'pending'; ?>">
                                <?php 
                                $payment_labels = [
                                    'pending' => 'අපේක්ෂිත',
                                    'paid' => 'ගෙවා ඇත',
                                    'verified' => 'තහවුරු'
                                ];
                                echo $payment_labels[$payment_status] ?? ucfirst($payment_status);
                                ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $status; ?>">
                                <?php 
                                $status_labels = [
                                    'pending' => 'අපේක්ෂිත',
                                    'in_progress' => 'ක්‍රියාත්මක',
                                    'completed' => 'සම්පූර්ණ',
                                    'rejected' => 'ප්‍රතික්ෂේප'
                                ];
                                echo $status_labels[$status] ?? ucfirst($status);
                                ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="action-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#requestModal"
                                data-id="<?php echo $row['id']; ?>"
                                data-name="<?php echo htmlspecialchars($row['client_name']); ?>"
                                data-phone="<?php echo htmlspecialchars($row['contact_whatsapp']); ?>"
                                data-service="<?php echo htmlspecialchars($row['service_name'] ?? 'N/A'); ?>"
                                data-message="<?php echo htmlspecialchars($row['request_details'] ?? 'N/A'); ?>"
                                data-status="<?php echo htmlspecialchars($status); ?>"
                                data-payment-status="<?php echo htmlspecialchars($payment_status); ?>"
                                data-admin-notes="<?php echo htmlspecialchars($row['admin_notes'] ?? ''); ?>"
                                data-payment-slip="<?php echo htmlspecialchars($row['payment_slip_path'] ?? ''); ?>"
                                data-uploaded-file="<?php echo htmlspecialchars($row['uploaded_file_path'] ?? ''); ?>">
                                <i class="fas fa-eye me-1"></i> බලන්න
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h4>ඉල්ලීම් නොමැත</h4>
                                <p>මෙම තත්ත්වය යටතේ කිසිදු සේවා ඉල්ලීමක් නොමැත.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="manage_requests.php?filter=<?php echo $status_filter; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="requestModalLabel">
                            <i class="fas fa-edit me-2"></i>ඉල්ලීම #<span id="modal-request-id-display"></span> කළමනාකරණය
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="request_id" id="modal-request-id">

                        <!-- Info Section -->
                        <div class="info-section">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="info-label"><i class="fas fa-user me-1"></i>නම</div>
                                    <div class="info-value" id="modal-name"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-label"><i class="fab fa-whatsapp me-1"></i>WhatsApp</div>
                                    <div class="info-value" id="modal-phone"></div>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <div class="info-label"><i class="fas fa-concierge-bell me-1"></i>සේවාව</div>
                                    <div class="info-value" id="modal-service"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Message -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-comment me-1"></i>ඉල්ලීමේ විස්තර</label>
                            <textarea class="form-control" id="modal-message" rows="4" readonly></textarea>
                        </div>

                        <!-- Uploaded Files -->
                        <div class="file-info" id="uploaded-files-section" style="display: none;">
                            <h6><i class="fas fa-paperclip me-2"></i>උඩුගත කළ ගොනු</h6>
                            <div id="payment-slip-link"></div>
                            <div id="uploaded-file-link"></div>
                        </div>

                        <hr>

                        <!-- Status Updates -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal-new-status" class="form-label">
                                    <i class="fas fa-sync-alt me-1"></i>ඉල්ලීමේ තත්ත්වය
                                </label>
                                <select class="form-select" name="new_status" id="modal-new-status">
                                    <option value="pending">අපේක්ෂිත (Pending)</option>
                                    <option value="in_progress">ක්‍රියාත්මක වෙමින් (In Progress)</option>
                                    <option value="completed">සම්පූර්ණයි (Completed)</option>
                                    <option value="rejected">ප්‍රතික්ෂේපිත (Rejected)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal-new-payment-status" class="form-label">
                                    <i class="fas fa-money-check-alt me-1"></i>ගෙවීම් තත්ත්වය
                                </label>
                                <select class="form-select" name="new_payment_status" id="modal-new-payment-status">
                                    <option value="pending">අපේක්ෂිත</option>
                                    <option value="paid">ගෙවා ඇත</option>
                                    <option value="verified">තහවුරු කර ඇත</option>
                                    <option value="refunded">ආපසු ගෙවන ලදී</option>
                                </select>
                            </div>
                        </div>

                        <!-- Admin Notes -->
                        <div class="mb-3">
                            <label for="modal-admin-notes" class="form-label">
                                <i class="fas fa-sticky-note me-1"></i>පරිපාලක සටහන්
                            </label>
                            <textarea class="form-control" name="admin_notes" id="modal-admin-notes" rows="3" 
                                      placeholder="මෙම ඉල්ලීම පිළිබඳ අභ්‍යන්තර සටහන්..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>වසා දමන්න
                        </button>
                        <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                            <i class="fas fa-save me-1"></i>වෙනස්කම් සුරකින්න
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        var requestModal = document.getElementById('requestModal');
        requestModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            
            // Get data from button attributes
            var id = button.getAttribute('data-id');
            var name = button.getAttribute('data-name');
            var phone = button.getAttribute('data-phone');
            var service = button.getAttribute('data-service');
            var message = button.getAttribute('data-message');
            var status = button.getAttribute('data-status');
            var paymentStatus = button.getAttribute('data-payment-status');
            var adminNotes = button.getAttribute('data-admin-notes') || '';
            var paymentSlip = button.getAttribute('data-payment-slip') || '';
            var uploadedFile = button.getAttribute('data-uploaded-file') || '';
            
            // Populate modal fields
            document.getElementById('modal-request-id-display').textContent = id;
            document.getElementById('modal-request-id').value = id;
            document.getElementById('modal-name').textContent = name;
            document.getElementById('modal-phone').textContent = phone;
            document.getElementById('modal-service').textContent = service;
            document.getElementById('modal-message').value = message;
            document.getElementById('modal-new-status').value = status;
            document.getElementById('modal-new-payment-status').value = paymentStatus;
            document.getElementById('modal-admin-notes').value = adminNotes;
            
            // Handle uploaded files
            var filesSection = document.getElementById('uploaded-files-section');
            var paymentSlipLink = document.getElementById('payment-slip-link');
            var uploadedFileLink = document.getElementById('uploaded-file-link');
            
            paymentSlipLink.innerHTML = '';
            uploadedFileLink.innerHTML = '';
            
            if (paymentSlip || uploadedFile) {
                filesSection.style.display = 'block';
                
                if (paymentSlip) {
                    // Add ../ to go up from admin folder to root
                    var slipPath = paymentSlip.startsWith('uploads/') ? '../' + paymentSlip : paymentSlip;
                    paymentSlipLink.innerHTML = '<p class="mb-2"><i class="fas fa-receipt me-2 text-success"></i><a href="' + slipPath + '" target="_blank" class="file-link">ගෙවීම් ස්ලිප් බලන්න</a></p>';
                }
                
                if (uploadedFile) {
                    // Add ../ to go up from admin folder to root
                    var filePath = uploadedFile.startsWith('uploads/') ? '../' + uploadedFile : uploadedFile;
                    uploadedFileLink.innerHTML = '<p class="mb-0"><i class="fas fa-file-alt me-2 text-info"></i><a href="' + filePath + '" target="_blank" class="file-link">උඩුගත කළ ලේඛනය බලන්න</a></p>';
                }
            } else {
                filesSection.style.display = 'none';
            }
        });

        // Auto-hide success message after 5 seconds
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
// Include footer
if (file_exists('includes/footer.php')) {
    include('includes/footer.php');
} else if (file_exists('../includes/footer.php')) {
    include('../includes/footer.php');
}
?>