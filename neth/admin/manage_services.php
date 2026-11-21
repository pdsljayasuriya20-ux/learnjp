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
        $stmt->bind_param("sssdi", $service_name, $category, $description, $price, $service_id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success alert-custom">
                            <i class="fas fa-check-circle me-2"></i>සේවාව සාර්ථකව යාවත්කාලීන කරන ලදි.
                        </div>';
        } else {
            $message = '<div class="alert alert-danger alert-custom">
                            <i class="fas fa-times-circle me-2"></i>සේවාව යාවත්කාලීන කිරීමේ දෝෂයක්.
                        </div>';
        }
    } else {
        // CREATE (අලුතින් ඇතුළු කිරීම)
        $sql = "INSERT INTO services (service_name, category, description, price) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssd", $service_name, $category, $description, $price);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success alert-custom">
                            <i class="fas fa-check-circle me-2"></i>නව සේවාව සාර්ථකව ඇතුළු කරන ලදි.
                        </div>';
        } else {
            $message = '<div class="alert alert-danger alert-custom">
                            <i class="fas fa-times-circle me-2"></i>සේවාව ඇතුළු කිරීමේ දෝෂයක්.
                        </div>';
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
        $message = '<div class="alert alert-success alert-custom">
                        <i class="fas fa-check-circle me-2"></i>සේවාව සාර්ථකව ඉවත් කරන ලදි.
                    </div>';
    } else {
        $message = '<div class="alert alert-danger alert-custom">
                        <i class="fas fa-times-circle me-2"></i>සේවාව ඉවත් කිරීමේ දෝෂයක්.
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
    <title>සේවා කළමනාකරණය - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css-main.css">
</head>
    
</head>
<body>
    <div class="services-container">
        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-concierge-bell me-2"></i>සේවා කළමනාකරණය</h2>
            <p>ජ්‍යෝතිෂ හා වාස්තු සේවා එකතු කරන්න, සංස්කරණය කරන්න සහ කළමනාකරණය කරන්න</p>
        </div>

        <?php echo $message; ?>

        <?php if ($action === 'list'): ?>
            <?php
            // Get statistics
            $total_services = $conn->query("SELECT COUNT(*) as count FROM services")->fetch_assoc()['count'];
            $jothishya_count = $conn->query("SELECT COUNT(*) as count FROM services WHERE category='Jothishya'")->fetch_assoc()['count'];
            $vasthu_count = $conn->query("SELECT COUNT(*) as count FROM services WHERE category='Vasthu'")->fetch_assoc()['count'];
            ?>

            <!-- Stats Bar -->
            <div class="stats-bar">
                <div class="stat-box total">
                    <div class="stat-icon">
                        <i class="fas fa-th-large"></i>
                    </div>
                    <div class="stat-info">
                        <h6>මුළු සේවා</h6>
                        <div class="stat-value"><?php echo $total_services; ?></div>
                    </div>
                </div>
                <div class="stat-box jothishya">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <h6>ජ්‍යෝතිෂ සේවා</h6>
                        <div class="stat-value"><?php echo $jothishya_count; ?></div>
                    </div>
                </div>
                <div class="stat-box vasthu">
                    <div class="stat-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="stat-info">
                        <h6>වාස්තු සේවා</h6>
                        <div class="stat-value"><?php echo $vasthu_count; ?></div>
                    </div>
                </div>
            </div>

            <a href="manage_services.php?action=add" class="add-btn">
                <i class="fas fa-plus-circle"></i> නව සේවාවක් ඇතුළු කරන්න
            </a>

            <?php
            $result = $conn->query("SELECT * FROM services ORDER BY category, service_name");
            if ($result->num_rows > 0):
            ?>
                <div class="services-grid">
                    <?php while ($row = $result->fetch_assoc()): 
                        $category_class = strtolower($row['category']);
                        $icon = ($row['category'] === 'Vasthu') ? 'fa-home' : 'fa-star';
                    ?>
                        <div class="service-card <?php echo $category_class; ?>">
                            <div class="service-icon">
                                <i class="fas <?php echo $icon; ?>"></i>
                            </div>
                            <span class="service-category <?php echo $category_class; ?>">
                                <?php echo ($row['category'] === 'Vasthu' ? 'වාස්තු' : 'ජ්‍යෝතිෂ'); ?>
                            </span>
                            <h3 class="service-title"><?php echo htmlspecialchars($row['service_name']); ?></h3>
                            <p class="service-description"><?php echo htmlspecialchars($row['description']); ?></p>
                            <div class="service-price">
                                රු. <?php echo number_format($row['price'], 2); ?>
                            </div>
                            <div class="service-actions">
                                <a href="manage_services.php?action=edit&id=<?php echo $row['id']; ?>" class="btn-edit">
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
                    <i class="fas fa-inbox"></i>
                    <h4>සේවා නොමැත</h4>
                    <p>තවම සේවා කිසිවක් ඇතුළත් කර නොමැත. ඉහත බොත්තම ක්ලික් කර නව සේවාවක් එකතු කරන්න.</p>
                </div>
            <?php endif; ?>

        <?php elseif ($action === 'add' || $action === 'edit'):
            // Service Add/Edit Form
            $service_data = ['service_name' => '', 'category' => 'Jothishya', 'description' => '', 'price' => 0.00, 'id' => 0];
            $form_title = "නව සේවාවක් ඇතුළු කිරීම";
            $form_icon = "fa-plus-circle";

            if ($action === 'edit' && $edit_id > 0) {
                $form_title = "සේවාව සංස්කරණය කිරීම";
                $form_icon = "fa-edit";
                $sql = "SELECT * FROM services WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $edit_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 1) {
                    $service_data = $result->fetch_assoc();
                    $service_data['id'] = $edit_id;
                } else {
                    echo '<div class="alert alert-danger alert-custom">
                            <i class="fas fa-exclamation-circle me-2"></i>සේවාව සොයාගත නොහැක.
                          </div>';
                    echo '<a href="manage_services.php" class="btn-cancel">ආපසු යන්න</a>';
                    include('includes/footer.php');
                    exit;
                }
            }
            ?>

            <div class="form-container">
                <div class="form-header">
                    <h3><i class="fas <?php echo $form_icon; ?> me-2"></i><?php echo $form_title; ?></h3>
                </div>

                <form method="POST" action="manage_services.php">
                    <input type="hidden" name="service_id" value="<?php echo $service_data['id']; ?>">

                    <div class="mb-4">
                        <label for="service_name" class="form-label">
                            <i class="fas fa-tag"></i> සේවාවේ නම
                        </label>
                        <input type="text" class="form-control" id="service_name" name="service_name" 
                               value="<?php echo htmlspecialchars($service_data['service_name']); ?>" 
                               placeholder="උදා: පූර්ණ හොරෝස්කෝප් විශ්ලේෂණය" required>
                    </div>

                    <div class="mb-4">
                        <label for="category" class="form-label">
                            <i class="fas fa-folder"></i> වර්ගය
                        </label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="Jothishya" <?php echo ($service_data['category'] === 'Jothishya' ? 'selected' : ''); ?>>
                                ⭐ ජ්‍යෝතිෂ සේවා
                            </option>
                            <option value="Vasthu" <?php echo ($service_data['category'] === 'Vasthu' ? 'selected' : ''); ?>>
                                🏠 වාස්තු සේවා
                            </option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label">
                            <i class="fas fa-align-left"></i> විස්තරය
                        </label>
                        <textarea class="form-control" id="description" name="description" 
                                  placeholder="සේවාව පිළිබඳ සවිස්තරාත්මක විස්තරයක් ලියන්න..." required><?php echo htmlspecialchars($service_data['description']); ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label for="price" class="form-label">
                            <i class="fas fa-rupee-sign"></i> මිල (රු.)
                        </label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" 
                               value="<?php echo $service_data['price']; ?>" 
                               placeholder="0.00" required>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save me-2"></i>සේවාව සුරකින්න
                        </button>
                        <a href="manage_services.php" class="btn-cancel">
                            <i class="fas fa-times me-2"></i>අවලංගු කරන්න
                        </a>
                    </div>
                </form>
            </div>

        <?php endif; ?>
    </div>

    <script>
        function confirmDelete(id) {
            if(confirm('මෙම සේවාව ඉවත් කිරීමට ඔබ සහතිකද? මෙම ක්‍රියාව ආපසු හැරවිය නොහැක.')) {
                window.location.href = 'manage_services.php?action=delete&id=' + id;
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