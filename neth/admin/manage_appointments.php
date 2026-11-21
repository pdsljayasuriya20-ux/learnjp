<?php
// Header ගොනුව ඇතුළත් කිරීම (ආරක්ෂාව සහ DB සම්බන්ධතාවය සකස් වේ)
include('includes/header.php');

$message = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- 1. Appointment ADD / EDIT (CREATE & UPDATE) Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_name = $conn->real_escape_string($_POST['client_name']);
    $contact_details = $conn->real_escape_string($_POST['contact_details']);
    $appointment_type = $conn->real_escape_string($_POST['appointment_type']);
    $description = $conn->real_escape_string($_POST['description']);
    $datetime_str = $conn->real_escape_string($_POST['appointment_datetime']);
    $status = $conn->real_escape_string($_POST['status']);
    $appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;

    // DATETIME ආකෘතිය නිවැරදිදැයි පරීක්ෂා කිරීම
    $appointment_datetime = str_replace('T', ' ', $datetime_str) . ':00'; 

    if ($appointment_id > 0) {
        // UPDATE (සංස්කරණය කිරීම)
        $sql = "UPDATE appointments SET client_name=?, contact_details=?, appointment_type=?, description=?, appointment_datetime=?, status=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $client_name, $contact_details, $appointment_type, $description, $appointment_datetime, $status, $appointment_id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success alert-custom">
                            <i class="fas fa-check-circle me-2"></i>හමුවීම සාර්ථකව යාවත්කාලීන කරන ලදි.
                        </div>';
        } else {
            $message = '<div class="alert alert-danger alert-custom">
                            <i class="fas fa-times-circle me-2"></i>හමුවීම යාවත්කාලීන කිරීමේ දෝෂයක්.
                        </div>';
        }
    } else {
        // CREATE (අලුතින් ඇතුළු කිරීම)
        $sql = "INSERT INTO appointments (client_name, contact_details, appointment_type, description, appointment_datetime, status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $client_name, $contact_details, $appointment_type, $description, $appointment_datetime, $status);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success alert-custom">
                            <i class="fas fa-check-circle me-2"></i>නව හමුවීමක් සාර්ථකව ඇතුළු කරන ලදි.
                        </div>';
        } else {
            $message = '<div class="alert alert-danger alert-custom">
                            <i class="fas fa-times-circle me-2"></i>හමුවීම ඇතුළු කිරීමේ දෝෂයක්.
                        </div>';
        }
    }
    $stmt->close();
    $action = 'list';
}

// --- 2. DELETE (ඉවත් කිරීම) Logic ---
if ($action === 'delete' && $edit_id > 0) {
    $sql = "DELETE FROM appointments WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $edit_id);
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success alert-custom">
                        <i class="fas fa-check-circle me-2"></i>හමුවීම සාර්ථකව ඉවත් කරන ලදි.
                    </div>';
    } else {
        $message = '<div class="alert alert-danger alert-custom">
                        <i class="fas fa-times-circle me-2"></i>හමුවීම ඉවත් කිරීමේ දෝෂයක්.
                    </div>';
    }
    $stmt->close();
    $action = 'list';
}
?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>හමුවීම්/මුහුර්ත කළමනාකරණය - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
 <link rel="stylesheet" href="css-main.css">
</head>
<body>
    <div class="appointments-container">
        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-calendar-check me-2"></i>හමුවීම්/මුහුර්ත කළමනාකරණය</h2>
            <p>ගනුදෙනුකරුවන්ගේ හමුවීම් සහ සුබ මුහුර්ත දිනයන් කළමනාකරණය කරන්න</p>
        </div>

        <?php echo $message; ?>

        <?php if ($action === 'list'): ?>
            <?php
            // Get statistics
            $current_datetime = date('Y-m-d H:i:s');
            $today_start = date('Y-m-d 00:00:00');
            $today_end = date('Y-m-d 23:59:59');
            
            $total_upcoming = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_datetime >= '$current_datetime'")->fetch_assoc()['count'];
            $scheduled_count = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status='Scheduled' AND appointment_datetime >= '$current_datetime'")->fetch_assoc()['count'];
            $mukurtha_count = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_type='Mukurtha' AND appointment_datetime >= '$current_datetime'")->fetch_assoc()['count'];
            $today_count = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_datetime BETWEEN '$today_start' AND '$today_end'")->fetch_assoc()['count'];
            ?>

            <!-- Stats Bar -->
            <div class="stats-bar">
                <div class="stat-box total">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h6>ඉදිරි හමුවීම්</h6>
                        <div class="stat-value"><?php echo $total_upcoming; ?></div>
                    </div>
                </div>
                <div class="stat-box scheduled">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h6>නියමිත හමුවීම්</h6>
                        <div class="stat-value"><?php echo $scheduled_count; ?></div>
                    </div>
                </div>
                <div class="stat-box mukurtha">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <h6>සුබ මුහුර්ත</h6>
                        <div class="stat-value"><?php echo $mukurtha_count; ?></div>
                    </div>
                </div>
                <div class="stat-box today">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-info">
                        <h6>අද දින</h6>
                        <div class="stat-value"><?php echo $today_count; ?></div>
                    </div>
                </div>
            </div>

            <a href="manage_appointments.php?action=add" class="add-btn">
                <i class="fas fa-plus-circle"></i> නව හමුවීමක්/මුහුර්තයක් ඇතුළු කරන්න
            </a>

            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <span>දැනට ඉදිරියේ ඇති හමුවීම් සහ මුහුර්ත දිනයන් පමණක් පෙන්වනු ලැබේ.</span>
            </div>

            <?php
            $result = $conn->query("SELECT * FROM appointments WHERE appointment_datetime >= '$current_datetime' OR status IN ('Scheduled') ORDER BY appointment_datetime ASC");
            
            if ($result && $result->num_rows > 0):
            ?>
                <div class="appointments-grid">
                    <?php while ($row = $result->fetch_assoc()): 
                        $status_class = strtolower($row['status']);
                        $type_class = strtolower($row['appointment_type']);
                        $type_icon = ($row['appointment_type'] === 'Mukurtha') ? 'fa-star' : 
                                    (($row['appointment_type'] === 'Appointment') ? 'fa-user-clock' : 'fa-calendar');
                        
                        $status_icon = ($row['status'] === 'Scheduled') ? 'fa-clock' :
                                      (($row['status'] === 'Completed') ? 'fa-check-circle' : 'fa-times-circle');
                    ?>
                        <div class="appointment-card <?php echo $status_class; ?>">
                            <div class="appointment-header">
                                <div class="appointment-icon">
                                    <i class="fas <?php echo $type_icon; ?>"></i>
                                </div>
                                <span class="appointment-type <?php echo $type_class; ?>">
                                    <?php 
                                    $type_labels = [
                                        'Appointment' => 'හමුවීම',
                                        'Mukurtha' => 'මුහුර්තය',
                                        'Other' => 'වෙනත්'
                                    ];
                                    echo $type_labels[$row['appointment_type']] ?? $row['appointment_type'];
                                    ?>
                                </span>
                            </div>

                            <div class="appointment-date">
                                <i class="fas fa-calendar"></i>
                                <?php echo date('Y-m-d H:i', strtotime($row['appointment_datetime'])); ?>
                            </div>

                            <h3 class="appointment-client">
                                <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($row['client_name']); ?>
                            </h3>

                            <?php if($row['contact_details']): ?>
                            <div class="appointment-contact">
                                <i class="fab fa-whatsapp me-1"></i>
                                <?php echo htmlspecialchars($row['contact_details']); ?>
                            </div>
                            <?php endif; ?>

                            <p class="appointment-description">
                                <i class="fas fa-comment-dots me-1"></i>
                                <?php echo htmlspecialchars($row['description']); ?>
                            </p>

                            <div class="appointment-status <?php echo $status_class; ?>">
                                <i class="fas <?php echo $status_icon; ?>"></i>
                                <?php 
                                $status_labels = [
                                    'Scheduled' => 'නියමිතයි',
                                    'Completed' => 'සම්පූර්ණයි',
                                    'Cancelled' => 'අවලංගුයි'
                                ];
                                echo $status_labels[$row['status']] ?? $row['status'];
                                ?>
                            </div>

                            <div class="appointment-actions">
                                <a href="manage_appointments.php?action=edit&id=<?php echo $row['id']; ?>" class="btn-edit">
                                    <i class="fas fa-edit"></i> සංස්කරණය
                                </a>
                                <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="btn-delete">
                                    <i class="fas fa-trash"></i> ඉවත් කරන්න
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h4>හමුවීම් නොමැත</h4>
                    <p>ඉදිරියේදී හමුවීම් හෝ මුහුර්ත දිනයන් ඇතුළත් කර නොමැත.</p>
                </div>
            <?php endif; ?>

        <?php elseif ($action === 'add' || $action === 'edit'):
            // Appointment Add/Edit Form
            $appointment_data = [
                'client_name' => '', 'contact_details' => '', 'appointment_type' => 'Appointment', 
                'description' => '', 'appointment_datetime' => '', 'status' => 'Scheduled', 'id' => 0
            ];
            $form_title = "නව හමුවීමක්/මුහුර්තයක් ඇතුළු කිරීම";
            $form_icon = "fa-plus-circle";

            if ($action === 'edit' && $edit_id > 0) {
                $form_title = "හමුවීම සංස්කරණය කිරීම";
                $form_icon = "fa-edit";
                $sql = "SELECT * FROM appointments WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $edit_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 1) {
                    $appointment_data = $result->fetch_assoc();
                    $appointment_data['id'] = $edit_id;
                    $appointment_data['appointment_datetime'] = date('Y-m-d\TH:i', strtotime($appointment_data['appointment_datetime']));
                } else {
                    echo '<div class="alert alert-danger alert-custom">
                            <i class="fas fa-exclamation-circle me-2"></i>හමුවීම සොයාගත නොහැක.
                          </div>';
                    echo '<a href="manage_appointments.php" class="btn-cancel">ආපසු යන්න</a>';
                    include('includes/footer.php');
                    exit;
                }
            }
            ?>

            <div class="form-container">
                <div class="form-header">
                    <h3><i class="fas <?php echo $form_icon; ?> me-2"></i><?php echo $form_title; ?></h3>
                </div>

                <form method="POST" action="manage_appointments.php">
                    <input type="hidden" name="appointment_id" value="<?php echo $appointment_data['id']; ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="client_name" class="form-label">
                                <i class="fas fa-user"></i> ගනුදෙනුකරුගේ නම
                            </label>
                            <input type="text" class="form-control" id="client_name" name="client_name" 
                                   value="<?php echo htmlspecialchars($appointment_data['client_name']); ?>" 
                                   placeholder="නිමල් සිල්වා" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="contact_details" class="form-label">
                                <i class="fab fa-whatsapp"></i> සම්බන්ධතා විස්තර
                            </label>
                            <input type="text" class="form-control" id="contact_details" name="contact_details" 
                                   value="<?php echo htmlspecialchars($appointment_data['contact_details']); ?>"
                                   placeholder="077 123 4567">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="appointment_datetime" class="form-label">
                                <i class="fas fa-calendar-alt"></i> දිනය හා වේලාව
                            </label>
                            <input type="datetime-local" class="form-control" id="appointment_datetime" 
                                   name="appointment_datetime" value="<?php echo $appointment_data['appointment_datetime']; ?>" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="appointment_type" class="form-label">
                                <i class="fas fa-tag"></i> වර්ගය
                            </label>
                            <select class="form-select" id="appointment_type" name="appointment_type" required>
                                <option value="Appointment" <?php echo ($appointment_data['appointment_type'] === 'Appointment' ? 'selected' : ''); ?>>🕐 හමුවීම</option>
                                <option value="Mukurtha" <?php echo ($appointment_data['appointment_type'] === 'Mukurtha' ? 'selected' : ''); ?>>⭐ සුබ මුහුර්තය</option>
                                <option value="Other" <?php echo ($appointment_data['appointment_type'] === 'Other' ? 'selected' : ''); ?>>📋 වෙනත්</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="status" class="form-label">
                                <i class="fas fa-info-circle"></i> තත්ත්වය
                            </label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Scheduled" <?php echo ($appointment_data['status'] === 'Scheduled' ? 'selected' : ''); ?>>⏰ නියමිතයි</option>
                                <option value="Completed" <?php echo ($appointment_data['status'] === 'Completed' ? 'selected' : ''); ?>>✅ සම්පූර්ණයි</option>
                                <option value="Cancelled" <?php echo ($appointment_data['status'] === 'Cancelled' ? 'selected' : ''); ?>>❌ අවලංගුයි</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">
                            <i class="fas fa-comment-dots"></i> විස්තරය / අරමුණ
                        </label>
                        <textarea class="form-control" id="description" name="description" 
                                  placeholder="හමුවීමේ අරමුණ හෝ මුහුර්තය පිළිබඳ විස්තර..." required><?php echo htmlspecialchars($appointment_data['description']); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save me-2"></i>සුරකින්න
                        </button>
                        <a href="manage_appointments.php" class="btn-cancel">
                            <i class="fas fa-times me-2"></i>අවලංගු කරන්න
                        </a>
                    </div>
                </form>
            </div>

        <?php endif; ?>
    </div>

    <script>
        function confirmDelete(id) {
            if(confirm('මෙම හමුවීම ඉවත් කිරීමට ඔබ සහතිකද? මෙම ක්‍රියාව ආපසු හැරවිය නොහැක.')) {
                window.location.href = 'manage_appointments.php?action=delete&id=' + id;
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