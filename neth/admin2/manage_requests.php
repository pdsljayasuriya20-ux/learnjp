<?php
// includes/header.php හි දත්ත සමුදා සම්බන්ධතාවය (db_connection.php) ඇතුළත් විය යුතුය.
include('includes/header.php'); 

$message = '';

// පෙරනිමි filter: 'pending' (නව ඉල්ලීම්)
$status_filter = isset($_GET['filter']) ? $conn->real_escape_string($_GET['filter']) : 'pending'; 

// --- 1. Status UPDATE Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $request_id = (int)$_POST['request_id'];
    $new_status = $conn->real_escape_string($_POST['new_status'] ?? '');
    $new_payment_status = $conn->real_escape_string($_POST['new_payment_status'] ?? '');
    $admin_notes = $conn->real_escape_string($_POST['admin_notes'] ?? '');
    
    // යාවත්කාලීන කළ යුතු තීරු පරීක්ෂා කර SQL විමසුම සකස් කිරීම
    $update_fields = [];
    $bind_types = '';
    $bind_params = [];

    if (!empty($new_status)) {
        $update_fields[] = "status=?";
        $bind_types .= "s";
        $bind_params[] = &$new_status;
    }
    if (!empty($new_payment_status)) {
        $update_fields[] = "payment_status=?";
        $bind_types .= "s";
        $bind_params[] = &$new_payment_status;
    }
    // Admin notes යාවත්කාලීන කිරීම
    $update_fields[] = "admin_notes=?";
    $bind_types .= "s";
    $bind_params[] = &$admin_notes;

    if (!empty($update_fields)) {
        $sql = "UPDATE client_requests SET " . implode(", ", $update_fields) . " WHERE id=?";
        $bind_types .= "i";
        $bind_params[] = &$request_id;
        
        $stmt = $conn->prepare($sql);
        
        // bind_param සඳහා ගතිකව පරාමිතීන් යැවීම
        array_unshift($bind_params, $bind_types);
        call_user_func_array([$stmt, 'bind_param'], $bind_params);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success alert-custom fade-in">
                            <i class="fas fa-check-circle me-2"></i>ඉල්ලීමේ තත්ත්වය සාර්ථකව යාවත්කාලීන කරන ලදි.
                        </div>';
        } else {
            $message = '<div class="alert alert-danger alert-custom fade-in">
                            <i class="fas fa-times-circle me-2"></i>යාවත්කාලීන කිරීමේ දෝෂයක්: ' . $conn->error . '
                        </div>';
        }
        $stmt->close();
    }
}

// --- 2. Data Retrieval ---
// service_name ලබා ගැනීම සඳහා services වගුව JOIN කරයි.
$where_clause = ($status_filter === 'All') ? "" : "WHERE cr.status = '$status_filter'";
$sql = "SELECT 
            cr.*, 
            s.service_name 
        FROM client_requests cr
        LEFT JOIN services s ON cr.service_id = s.id
        $where_clause 
        ORDER BY cr.request_date DESC";
$result = $conn->query($sql);

?>

<div class="container my-5">
    <h2 class="mb-4"><i class="fas fa-clipboard-list"></i> සේවා ඉල්ලීම් කළමනාකරණය</h2>
    <hr>

    <?php echo $message; ?>

    <div class="mb-4">
        <a href="manage_requests.php?filter=pending" class="btn btn-<?php echo ($status_filter=='pending'?'primary':'outline-primary'); ?>">පොරොත්තු (Pending)</a>
        <a href="manage_requests.php?filter=in_progress" class="btn btn-<?php echo ($status_filter=='in_progress'?'info':'outline-info'); ?>">ක්‍රියාත්මක වෙමින් පවතින</a>
        <a href="manage_requests.php?filter=completed" class="btn btn-<?php echo ($status_filter=='completed'?'success':'outline-success'); ?>">සම්පූර්ණයි (Completed)</a>
        <a href="manage_requests.php?filter=rejected" class="btn btn-<?php echo ($status_filter=='rejected'?'danger':'outline-danger'); ?>">ප්‍රතික්ෂේපිත</a>
        <a href="manage_requests.php?filter=All" class="btn btn-<?php echo ($status_filter=='All'?'dark':'outline-dark'); ?>">සියල්ල (All)</a>
    </div>

    <div class="table-responsive fade-in">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>දිනය</th>
                    <th>සේවාව</th>
                    <th>ගනුදෙනුකරු</th>
                    <th>WhatsApp</th>
                    <th>ගෙවීම් තත්ත්වය</th>
                    <th>තත්ත්වය</th>
                    <th>ක්‍රියාකාරීත්වය</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                    $status_class = '';
                    if ($row['status'] == 'pending') $status_class='table-warning';
                    else if ($row['status']=='in_progress') $status_class='table-info';
                    else if ($row['status']=='completed') $status_class='table-success';
                    else if ($row['status']=='rejected') $status_class='table-danger';

                    $payment_badge_class = '';
                    if ($row['payment_status'] == 'pending') $payment_badge_class='badge bg-danger';
                    else if ($row['payment_status']=='verified') $payment_badge_class='badge bg-success';
                    else if ($row['payment_status']=='uploaded') $payment_badge_class='badge bg-warning text-dark';
                    
                    // 🚨 දෝෂය නිවැරදි කරන ලද කොටස (admin_notes Null Coalescing Operator භාවිත කර ඇත)
                    $admin_notes_safe = $row['admin_notes'] ?? '';
                ?>
                <tr class="<?php echo $status_class; ?>">
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo date('Y-m-d H:i', strtotime($row['request_date'])); ?></td>
                    <td><?php echo htmlspecialchars($row['service_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['contact_whatsapp']); ?></td>
                    <td>
                        <span class="<?php echo $payment_badge_class; ?>">
                            <?php echo ucfirst($row['payment_status']); ?>
                        </span>
                        <?php 
                        // ගොනුව බැලීමට සාපේක්ෂ මාර්ගය භාවිත කිරීම 
                        // admin ෆෝල්ඩරයෙන් පිටත ඇති 'payment_slip_path' වෙත යොමු කරයි.
                        if (!empty($row['payment_slip_path'])): 
                            $full_slip_path = '../' . htmlspecialchars($row['payment_slip_path']); 
                        ?>
                            <a href="<?php echo $full_slip_path; ?>" target="_blank" class="ms-2 text-decoration-none">
                                <i class="fas fa-receipt text-success"></i> ස්ලිප්
                            </a>
                        <?php endif; ?>
                    </td>
                    <td><?php echo ucfirst($row['status']); ?></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-info text-white"
                            data-bs-toggle="modal"
                            data-bs-target="#requestModal"
                            data-id="<?php echo $row['id']; ?>"
                            data-client-name="<?php echo htmlspecialchars($row['client_name']); ?>"
                            data-whatsapp="<?php echo htmlspecialchars($row['contact_whatsapp']); ?>"
                            data-service="<?php echo htmlspecialchars($row['service_name'] ?? 'N/A'); ?>"
                            data-details="<?php echo htmlspecialchars($row['request_details']); ?>"
                            data-uploaded-file="<?php echo htmlspecialchars($row['uploaded_file_path']); ?>"
                            data-payment-slip="<?php echo htmlspecialchars($row['payment_slip_path']); ?>"
                            data-status="<?php echo htmlspecialchars($row['status']); ?>"
                            data-payment-status="<?php echo htmlspecialchars($row['payment_status']); ?>"
                            data-admin-notes="<?php echo htmlspecialchars($admin_notes_safe); ?>"> <i class="fas fa-eye"></i> බලන්න/කළමනාකරණය කරන්න
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8" class="text-center">මෙම තත්ත්වය යටතේ කිසිදු සේවා ඉල්ලීමක් නොමැත.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="manage_requests.php?filter=<?php echo $status_filter; ?>">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="requestModalLabel">ඉල්ලීම #<span id="modal-request-id-display"></span> කළමනාකරණය</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="request_id" id="modal-request-id">

                    <div class="row mb-3">
                        <div class="col-md-6"><p><strong>ගනුදෙනුකරු:</strong> <span id="modal-client-name"></span></p></div>
                        <div class="col-md-6"><p><strong>WhatsApp:</strong> <span id="modal-whatsapp"></span></p></div>
                        <div class="col-12"><p><strong>සේවාව:</strong> <span id="modal-service"></span></p></div>
                    </div>
                    
                    <p><strong>ඉල්ලීමේ විස්තර:</strong></p>
                    <textarea class="form-control mb-3" id="modal-details" rows="4" readonly></textarea>
                    
                    <hr>

                    <div class="mb-3">
                        <p class="fw-bold"><i class="fas fa-paperclip me-2"></i>උඩුගත කළ ගොනු/ස්ලිප්:</p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-file-invoice me-2"></i>ගෙවීම් ස්ලිප්: <span id="modal-payment-slip-link">N/A</span></li>
                            <li><i class="fas fa-file-upload me-2"></i>අතිරේක ගොනු: <span id="modal-uploaded-file-link">N/A</span></li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="modal-new-payment-status" class="form-label"><i class="fas fa-money-check-alt me-1"></i>ගෙවීම් තත්ත්වය</label>
                            <select class="form-select" name="new_payment_status" id="modal-new-payment-status">
                                <option value="pending">පොරොත්තු (Pending)</option>
                                <option value="uploaded">උඩුගත කර ඇත (Uploaded)</option>
                                <option value="verified">තහවුරු කර ඇත (Verified)</option>
                                <option value="refunded">මුදල් ආපසු ගෙවන ලදී (Refunded)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="modal-new-status" class="form-label"><i class="fas fa-sync-alt me-1"></i>ඉල්ලීමේ තත්ත්වය</label>
                            <select class="form-select" name="new_status" id="modal-new-status">
                                <option value="pending">පොරොත්තු (Pending)</option>
                                <option value="in_progress">ක්‍රියාත්මක වෙමින් පවතින (In Progress)</option>
                                <option value="completed">සම්පූර්ණයි (Completed)</option>
                                <option value="rejected">ප්‍රතික්ෂේපිත (Rejected)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="modal-admin-notes" class="form-label"><i class="fas fa-sticky-note me-1"></i>පරිපාලක සටහන්</label>
                        <textarea class="form-control" name="admin_notes" id="modal-admin-notes" rows="3" placeholder="මෙම ඉල්ලීම පිළිබඳ අභ්‍යන්තර සටහන්..."></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">වසා දමන්න</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> වෙනස්කම් සුරකින්න</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var requestModal = document.getElementById('requestModal');
requestModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    
    // Data retrieval and population
    var id = button.getAttribute('data-id');
    var status = button.getAttribute('data-status');
    var paymentStatus = button.getAttribute('data-payment-status');
    
    // admin_notes වල NULL දෝෂය වළක්වා ගැනීමට
    var adminNotes = button.getAttribute('data-admin-notes') || ''; 
    
    // ගොනු මාර්ග (File Paths) දත්ත ගුණාංගවලින් ලබා ගැනීම
    var uploadedFile = button.getAttribute('data-uploaded-file');
    var paymentSlip = button.getAttribute('data-payment-slip');

    document.getElementById('modal-request-id-display').textContent = id;
    document.getElementById('modal-request-id').value = id;
    document.getElementById('modal-client-name').textContent = button.getAttribute('data-client-name');
    document.getElementById('modal-whatsapp').textContent = button.getAttribute('data-whatsapp');
    document.getElementById('modal-service').textContent = button.getAttribute('data-service');
    document.getElementById('modal-details').value = button.getAttribute('data-details');
    
    // Status Dropdowns
    document.getElementById('modal-new-status').value = status;
    document.getElementById('modal-new-payment-status').value = paymentStatus;
    
    // 🚨 දෝෂය නිවැරදි කළ කොටස (JavaScript)
    document.getElementById('modal-admin-notes').value = adminNotes; 
    
    // File Links - සාපේක්ෂ මාර්ගය නිවැරදි කිරීම
    var paymentSlipLink = document.getElementById('modal-payment-slip-link');
    var uploadedFileLink = document.getElementById('modal-uploaded-file-link');

    // Payment Slip Link
    if (paymentSlip && paymentSlip !== 'N/A' && paymentSlip.trim() !== '') {
        // '../' එකතු කිරීමෙන් නිවැරදි මාර්ගය සාදයි
        var fullSlipPath = '../' + paymentSlip;
        paymentSlipLink.innerHTML = '<a href="' + fullSlipPath + '" target="_blank" class="btn btn-sm btn-outline-success"><i class="fas fa-download"></i> බලන්න</a>';
    } else {
        paymentSlipLink.innerHTML = '<span class="text-danger">නොමැත</span>';
    }

    // Additional Uploaded File Link
    if (uploadedFile && uploadedFile !== 'N/A' && uploadedFile.trim() !== '') {
        // '../' එකතු කිරීමෙන් නිවැරදි මාර්ගය සාදයි
        var fullFilePath = '../' + uploadedFile;
        uploadedFileLink.innerHTML = '<a href="' + fullFilePath + '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i> බලන්න</a>';
    } else {
        uploadedFileLink.innerHTML = '<span class="text-danger">නොමැත</span>';
    }
});
</script>

<?php
include('includes/footer.php');
?>