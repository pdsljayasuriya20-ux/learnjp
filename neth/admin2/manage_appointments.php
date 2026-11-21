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

    // DATETIME ආකෘතිය නිවැරදිදැයි පරීක්ෂා කිරීම (YYYY-MM-DD HH:MM:SS)
    // HTML datetime-local මඟින් YYYY-MM-DDTHH:MM ආකෘතිය ලබා දෙන බැවින්, අපි 'T' ඉවත් කරමු
    $appointment_datetime = str_replace('T', ' ', $datetime_str) . ':00'; 

    if ($appointment_id > 0) {
        // UPDATE (සංස්කරණය කිරීම)
        $sql = "UPDATE appointments SET client_name=?, contact_details=?, appointment_type=?, description=?, appointment_datetime=?, status=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $client_name, $contact_details, $appointment_type, $description, $appointment_datetime, $status, $appointment_id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">හමුවීම සාර්ථකව යාවත්කාලීන කරන ලදි.</div>';
        } else {
            $message = '<div class="alert alert-danger">හමුවීම යාවත්කාලීන කිරීමේ දෝෂයක්.</div>';
        }
    } else {
        // CREATE (අලුතින් ඇතුළු කිරීම)
        $sql = "INSERT INTO appointments (client_name, contact_details, appointment_type, description, appointment_datetime, status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $client_name, $contact_details, $appointment_type, $description, $appointment_datetime, $status);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">නව හමුවීමක් සාර්ථකව ඇතුළු කරන ලදි.</div>';
        } else {
            $message = '<div class="alert alert-danger">හමුවීම ඇතුළු කිරීමේ දෝෂයක්.</div>';
        }
    }
    $stmt->close();
    $action = 'list'; // ලැයිස්තුවට නැවත යොමු කිරීම
}

// --- 2. DELETE (ඉවත් කිරීම) Logic ---
if ($action === 'delete' && $edit_id > 0) {
    $sql = "DELETE FROM appointments WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $edit_id);
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">හමුවීම සාර්ථකව ඉවත් කරන ලදි.</div>';
    } else {
        $message = '<div class="alert alert-danger">හමුවීම ඉවත් කිරීමේ දෝෂයක්.</div>';
    }
    $stmt->close();
    $action = 'list';
}
?>
<div class="container my-5">
<h2 class="mb-4"><i class="fas fa-calendar-alt"></i> හමුවීම්/මුහුර්ත කළමනාකරණය</h2>
<hr>

<?php echo $message; ?>

<?php if ($action === 'list'): ?>
    <a href="manage_appointments.php?action=add" class="btn btn-success mb-3"><i class="fas fa-plus"></i> නව හමුවීමක්/මුහුර්තයක් ඇතුළු කරන්න</a>

    <div class="alert alert-info">
        දැනට ඉදිරියේ ඇති හමුවීම් සහ මුහුර්ත දිනයන් පමණක් පෙන්වනු ලැබේ.
    </div>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>දිනය හා වේලාව</th>
                <th>වර්ගය</th>
                <th>ගනුදෙනුකරු</th>
                <th>විස්තරය</th>
                <th>තත්ත්වය</th>
                <th>ක්‍රියාකාරීත්වය</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // අද දිනයෙන් පසු ඇති දත්ත පමණක් ලබා ගැනීම
            $current_datetime = date('Y-m-d H:i:s');
            $result = $conn->query("SELECT * FROM appointments WHERE appointment_datetime >= '$current_datetime' OR status IN ('Scheduled') ORDER BY appointment_datetime ASC");
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // තත්ත්වය අනුව වර්ණ ගැන්වීම
                    $status_class = '';
                    if ($row['status'] == 'Scheduled') $status_class = 'table-info';
                    else if ($row['status'] == 'Completed') $status_class = 'table-success';
                    else if ($row['status'] == 'Cancelled') $status_class = 'table-danger';
                    ?>
                    <tr class="<?php echo $status_class; ?>">
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($row['appointment_datetime'])); ?></td>
                        <td><?php echo htmlspecialchars($row['appointment_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                        <td><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . (strlen($row['description']) > 50 ? '...' : ''); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td>
                            <a href="manage_appointments.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> සංස්කරණය</a>
                            <a href="manage_appointments.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('මෙම හමුවීම ඉවත් කිරීමට ඔබ සහතිකද?');"><i class="fas fa-trash"></i> ඉවත් කරන්න</a>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                echo '<tr><td colspan="7">ඉදිරියේදී හමුවීම් හෝ මුහුර්ත දිනයන් ඇතුළත් කර නොමැත.</td></tr>';
            }
            ?>
        </tbody>
    </table>

<?php elseif ($action === 'add' || $action === 'edit'):
    // Appointment Add/Edit Form (හමුවීම් ඇතුළු කිරීමේ/සංස්කරණය කිරීමේ පෝරමය)
    $appointment_data = [
        'client_name' => '', 'contact_details' => '', 'appointment_type' => 'Appointment', 
        'description' => '', 'appointment_datetime' => '', 'status' => 'Scheduled', 'id' => 0
    ];
    $form_title = "නව හමුවීමක්/මුහුර්තයක් ඇතුළු කිරීම";

    if ($action === 'edit' && $edit_id > 0) {
        $form_title = "හමුවීම සංස්කරණය කිරීම (#" . $edit_id . ")";
        $sql = "SELECT * FROM appointments WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $appointment_data = $result->fetch_assoc();
            $appointment_data['id'] = $edit_id;
            // HTML datetime-local ආකෘතියට සකස් කිරීම
            $appointment_data['appointment_datetime'] = date('Y-m-d\TH:i', strtotime($appointment_data['appointment_datetime']));
        } else {
            $message = '<div class="alert alert-danger">හමුවීම සොයාගත නොහැක.</div>';
            $action = 'list';
            include('includes/footer.php');
            exit;
        }
    }
    ?>

    <h3><?php echo $form_title; ?></h3>
    <form method="POST" action="manage_appointments.php">
        <input type="hidden" name="appointment_id" value="<?php echo $appointment_data['id']; ?>">

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="client_name" class="form-label">ගනුදෙනුකරුගේ නම</label>
                <input type="text" class="form-control" id="client_name" name="client_name" value="<?php echo htmlspecialchars($appointment_data['client_name']); ?>" required>
            </div>

            <div class="col-md-6 mb-3">
                <label for="contact_details" class="form-label">සම්බන්ධතා විස්තර (දුරකථන/Whatsapp)</label>
                <input type="text" class="form-control" id="contact_details" name="contact_details" value="<?php echo htmlspecialchars($appointment_data['contact_details']); ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="appointment_datetime" class="form-label">දිනය හා වේලාව</label>
                <input type="datetime-local" class="form-control" id="appointment_datetime" name="appointment_datetime" value="<?php echo $appointment_data['appointment_datetime']; ?>" required>
            </div>

            <div class="col-md-4 mb-3">
                <label for="appointment_type" class="form-label">වර්ගය</label>
                <select class="form-select" id="appointment_type" name="appointment_type" required>
                    <option value="Appointment" <?php echo ($appointment_data['appointment_type'] === 'Appointment' ? 'selected' : ''); ?>>හමුවීම</option>
                    <option value="Mukurtha" <?php echo ($appointment_data['appointment_type'] === 'Mukurtha' ? 'selected' : ''); ?>>සුබ මුහුර්තය</option>
                    <option value="Other" <?php echo ($appointment_data['appointment_type'] === 'Other' ? 'selected' : ''); ?>>වෙනත්</option>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label for="status" class="form-label">තත්ත්වය</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="Scheduled" <?php echo ($appointment_data['status'] === 'Scheduled' ? 'selected' : ''); ?>>නියමිතයි (Scheduled)</option>
                    <option value="Completed" <?php echo ($appointment_data['status'] === 'Completed' ? 'selected' : ''); ?>>සම්පූර්ණයි (Completed)</option>
                    <option value="Cancelled" <?php echo ($appointment_data['status'] === 'Cancelled' ? 'selected' : ''); ?>>අවලංගුයි (Cancelled)</option>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">විස්තරය / අරමුණ</label>
            <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($appointment_data['description']); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> සුරකින්න</button>
        <a href="manage_appointments.php" class="btn btn-secondary">අවලංගු කරන්න</a>
    </form>

<?php endif; ?>

<?php
include('includes/footer.php');
?>