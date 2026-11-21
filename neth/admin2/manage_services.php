<?php
// Header ගොනුව ඇතුළත් කිරීම (ආරක්ෂාව සහ DB සම්බන්ධතාවය සකස් වේ)
include('includes/header.php');

$message = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- 1. Service ADD / EDIT (CREATE & UPDATE) Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_name = $conn->real_escape_string($_POST['service_name']);
    $category = $conn->real_escape_string($_POST['category']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = (float)$_POST['price'];
    $service_id = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;

    if ($service_id > 0) {
        // UPDATE (සංස්කරණය කිරීම)
        $sql = "UPDATE services SET service_name=?, category=?, description=?, price=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssid", $service_name, $category, $description, $price, $service_id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">සේවාව සාර්ථකව යාවත්කාලීන කරන ලදි.</div>';
        } else {
            $message = '<div class="alert alert-danger">සේවාව යාවත්කාලීන කිරීමේ දෝෂයක්.</div>';
        }
    } else {
        // CREATE (අලුතින් ඇතුළු කිරීම)
        $sql = "INSERT INTO services (service_name, category, description, price) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssd", $service_name, $category, $description, $price);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">නව සේවාව සාර්ථකව ඇතුළු කරන ලදි.</div>';
        } else {
            $message = '<div class="alert alert-danger">සේවාව ඇතුළු කිරීමේ දෝෂයක්.</div>';
        }
    }
    $stmt->close();
    $action = 'list'; // ලැයිස්තුවට නැවත යොමු කිරීම
}

// --- 2. DELETE (ඉවත් කිරීම) Logic ---
if ($action === 'delete' && $edit_id > 0) {
    $sql = "DELETE FROM services WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $edit_id);
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">සේවාව සාර්ථකව ඉවත් කරන ලදි.</div>';
    } else {
        $message = '<div class="alert alert-danger">සේවාව ඉවත් කිරීමේ දෝෂයක්.</div>';
    }
    $stmt->close();
    $action = 'list';
}
?>
<div class="container my-5">

<h2 class="mb-4"><i class="fas fa-list"></i> සේවා කළමනාකරණය</h2>
<hr>

<?php echo $message; ?>

<?php if ($action === 'list'): ?>
    <a href="manage_services.php?action=add" class="btn btn-success mb-3"><i class="fas fa-plus"></i> නව සේවාවක් ඇතුළු කරන්න</a>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>සේවාවේ නම</th>
                <th>වර්ගය</th>
                <th>මිල (රු.)</th>
                <th>ක්‍රියාකාරීත්වය</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $result = $conn->query("SELECT * FROM services ORDER BY category, service_name");
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                        <td><?php echo ($row['category'] === 'Vasthu' ? 'වාස්තු' : 'ජ්‍යෝතිෂ'); ?></td>
                        <td><?php echo number_format($row['price'], 2); ?></td>
                        <td>
                            <a href="manage_services.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> සංස්කරණය</a>
                            <a href="manage_services.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('මෙම සේවාව ඉවත් කිරීමට ඔබ සහතිකද?');"><i class="fas fa-trash"></i> ඉවත් කරන්න</a>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                echo '<tr><td colspan="5">සේවා කිසිවක් ඇතුළත් කර නොමැත.</td></tr>';
            }
            ?>
        </tbody>
    </table>

<?php elseif ($action === 'add' || $action === 'edit'):
    // Service Add/Edit Form (සේවා ඇතුළු කිරීමේ/සංස්කරණය කිරීමේ පෝරමය)
    $service_data = ['service_name' => '', 'category' => 'Jothishya', 'description' => '', 'price' => 0.00, 'id' => 0];
    $form_title = "නව සේවාවක් ඇතුළු කිරීම";

    if ($action === 'edit' && $edit_id > 0) {
        $form_title = "සේවාව සංස්කරණය කිරීම (#" . $edit_id . ")";
        $sql = "SELECT * FROM services WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $service_data = $result->fetch_assoc();
            $service_data['id'] = $edit_id;
        } else {
            $message = '<div class="alert alert-danger">සේවාව සොයාගත නොහැක.</div>';
            $action = 'list';
            echo '<a href="manage_services.php" class="btn btn-secondary">ආපසු යන්න</a>';
            // If service not found, stop displaying form
            include('includes/footer.php');
            exit;
        }
    }
    ?>

    <h3><?php echo $form_title; ?></h3>
    <form method="POST" action="manage_services.php">
        <input type="hidden" name="service_id" value="<?php echo $service_data['id']; ?>">

        <div class="mb-3">
            <label for="service_name" class="form-label">සේවාවේ නම</label>
            <input type="text" class="form-control" id="service_name" name="service_name" value="<?php echo htmlspecialchars($service_data['service_name']); ?>" required>
        </div>

        <div class="mb-3">
            <label for="category" class="form-label">වර්ගය</label>
            <select class="form-select" id="category" name="category" required>
                <option value="Jothishya" <?php echo ($service_data['category'] === 'Jothishya' ? 'selected' : ''); ?>>ජ්‍යෝතිෂ සේවා</option>
                <option value="Vasthu" <?php echo ($service_data['category'] === 'Vasthu' ? 'selected' : ''); ?>>වාස්තු සේවා</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">විස්තරය</label>
            <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($service_data['description']); ?></textarea>
        </div>

        <div class="mb-3">
            <label for="price" class="form-label">මිල (රු.)</label>
            <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo $service_data['price']; ?>" required>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> සේවාව සුරකින්න</button>
        <a href="manage_services.php" class="btn btn-secondary">අවලංගු කරන්න</a>
    </form>

<?php endif; ?>

<?php
include('includes/footer.php');
?>