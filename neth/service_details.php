<?php
include('db_connection.php');
include 'header.php';

// --- 1. Service Data Retrieval ---
$service_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$service_data = null;
$message = '';
$form_visible = true;

if ($service_id > 0) {
    $sql = "SELECT * FROM services WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $service_data = $result->fetch_assoc();
    } else {
        header('Location: services.php');
        exit;
    }
    $stmt->close();
} else {
    header('Location: services.php');
    exit;
}

// Dynamic Icon & Color Mapping
function getServiceIconAndGradient($service_name, $category) {
    // Jothishya icon mappings
    $jothishya_icons = [
        'හෝරා' => ['icon' => 'fa-moon', 'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'],
        'නැකත්' => ['icon' => 'fa-star', 'gradient' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'],
        'අංක' => ['icon' => 'fa-calculator', 'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'],
        'මැණික්' => ['icon' => 'fa-gem', 'gradient' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'],
        'නම' => ['icon' => 'fa-signature', 'gradient' => 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'],
        'යෝග' => ['icon' => 'fa-om', 'gradient' => 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)'],
        'කේන්දර' => ['icon' => 'fa-calendar-alt', 'gradient' => 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)'],
        'වැඩ දවස' => ['icon' => 'fa-calendar-check', 'gradient' => 'linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%)'],
        'ග්‍රහ' => ['icon' => 'fa-globe', 'gradient' => 'linear-gradient(135deg, #fad0c4 0%, #ffd1ff 100%)'],
        'පලාඵල' => ['icon' => 'fa-star-half-alt', 'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'],
    ];
    
    // Vasthu icon mappings
    $vasthu_icons = [
        'නිවස' => ['icon' => 'fa-home', 'gradient' => 'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)'],
        'ගෘහ' => ['icon' => 'fa-home', 'gradient' => 'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)'],
        'කඩ' => ['icon' => 'fa-store', 'gradient' => 'linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%)'],
        'කාර්යාල' => ['icon' => 'fa-building', 'gradient' => 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)'],
        'ව්‍යාපාර' => ['icon' => 'fa-briefcase', 'gradient' => 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)'],
        'කර්මාන්ත' => ['icon' => 'fa-industry', 'gradient' => 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'],
        'ඉඩම' => ['icon' => 'fa-map', 'gradient' => 'linear-gradient(135deg, #fad0c4 0%, #ffd1ff 100%)'],
        'දොර' => ['icon' => 'fa-door-open', 'gradient' => 'linear-gradient(135deg, #d299c2 0%, #fef9d7 100%)'],
        'කොටස්' => ['icon' => 'fa-th-large', 'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'],
        'දිශාව' => ['icon' => 'fa-compass', 'gradient' => 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)'],
        'සැලසුම' => ['icon' => 'fa-drafting-compass', 'gradient' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'],
        'නිර්මාණ' => ['icon' => 'fa-pencil-ruler', 'gradient' => 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)'],
    ];
    
    // Select appropriate icon set
    $icons = ($category === 'Vasthu') ? $vasthu_icons : $jothishya_icons;
    $default = ($category === 'Vasthu') 
        ? ['icon' => 'fa-home', 'gradient' => 'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)']
        : ['icon' => 'fa-star-half-alt', 'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'];
    
    // Find matching icon
    foreach ($icons as $keyword => $style) {
        if (strpos($service_name, $keyword) !== false) {
            return $style;
        }
    }
    
    return $default;
}

// Get icon and gradient for current service
$iconData = getServiceIconAndGradient($service_data['service_name'], $service_data['category']);

// --- 2. Request Form Submission Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $service_data) {
    $client_name = $conn->real_escape_string($_POST['client_name']);
    $contact_whatsapp = $conn->real_escape_string($_POST['contact_whatsapp']);
    $request_details = $conn->real_escape_string($_POST['request_details']);
    
    $sql = "INSERT INTO client_requests (client_name, contact_whatsapp, service_id, request_details) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssis", $client_name, $contact_whatsapp, $service_id, $request_details);
    
    if ($stmt->execute()) {
        $new_request_id = $conn->insert_id;
        $upload_link = "request_upload.php?request_id=" . $new_request_id; 
        
        $message = '
        <div class="alert alert-success alert-custom border-success shadow-sm">
            <div class="text-center">
                <div class="success-icon mb-3">
                    <i class="fas fa-check-circle fa-4x text-success"></i>
                </div>
                <h4 class="fw-bold"><i class="fas fa-check-circle me-2"></i> ඔබගේ සේවා ඉල්ලීම සාර්ථකයි!</h4>
                <p class="lead mb-3">ඔබගේ ඉල්ලීම් අංකය: <span class="badge bg-success fs-5">#' . $new_request_id . '</span></p>
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>මීළඟ පියවර:</strong> කරුණාකර දැන් ගෙවීම් ස්ලිප් සහ අවශ්‍ය ලේඛන උඩුගත කරන්න.
                </div>
                <a href="' . $upload_link . '" class="btn btn-lg btn-success shadow">
                    <i class="fas fa-upload me-2"></i> ගෙවීම් ස්ලිප් සහ ගොනු උඩුගත කරන්න
                </a>
                <p class="mt-4 text-muted small">
                    <i class="fas fa-clipboard-list me-2"></i>
                    ඔබගේ ඉල්ලීම් අංකය සුරක්ෂිතව තබා ගන්න. ඔබට පසුවද මෙම සබැඳිය භාවිතයෙන් ගොනු උඩුගත කළ හැකිය.
                </p>
            </div>
        </div>';
        
        $form_visible = false; 
        unset($_POST);
    } else {
        $message = '<div class="alert alert-danger alert-custom border-danger"><i class="fas fa-exclamation-triangle me-2"></i>ඉල්ලීම ඉදිරිපත් කිරීමේදී දෝෂයක් ඇති විය. කරුණාකර නැවත උත්සාහ කරන්න.</div>';
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($service_data['service_name']); ?> | නෙත්සිත ජෝතිශ්‍ය සේවය</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/servisd.css">
    
</head>
<body>

    <!-- Hero Section -->
    <?php if ($service_data): ?>
    <div class="hero-section">
        <!-- Animated stars -->
        <div class="hero-stars">
            <div class="hero-star"></div>
            <div class="hero-star"></div>
            <div class="hero-star"></div>
            <div class="hero-star"></div>
            <div class="hero-star"></div>
        </div>
        
        <div class="container text-center fade-in">
            <!-- Dynamic Service Icon -->
            <div class="service-hero-icon" style="background: <?php echo $iconData['gradient']; ?>;">
                <i class="fas <?php echo $iconData['icon']; ?> fa-3x text-white"></i>
            </div>
            
            <h1 class="display-4 fw-bold mb-3"><?php echo htmlspecialchars($service_data['service_name']); ?></h1>
            <span class="category-badge">
                <?php 
                if ($service_data['category'] === 'Vasthu') {
                    echo '<i class="fas fa-home me-2"></i>වාස්තු සේවාව';
                } else {
                    echo '<i class="fas fa-star me-2"></i>ජ්‍යෝතිෂ සේවාව';
                }
                ?>
            </span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="container my-5">
        <!-- Back Button -->
        <div class="mb-4 fade-in">
            <a href="services.php" class="btn btn-back">
                <i class="fas fa-arrow-left me-2"></i> සියලුම සේවා වෙත ආපසු යන්න
            </a>
        </div>

        <!-- Success/Error Message -->
        <?php if ($message): ?>
            <div class="fade-in">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($service_data): ?>
            <div class="row">
                <!-- Service Details Column -->
                <div class="col-lg-7 mb-4 fade-in">
                    <!-- Service Description -->
                    <div class="details-card p-4">
                        <h2 class="fw-bold text-primary mb-4">
                            <i class="fas fa-info-circle me-2"></i>සේවාව පිළිබඳ විස්තරය
                        </h2>
                        <p class="lead mb-4"><?php echo nl2br(htmlspecialchars($service_data['description'])); ?></p>
                        
                        <hr class="my-4">
                        
                        <div class="text-center mb-4">
                            <h3 class="mb-3">සේවා ගාස්තුව</h3>
                            <span class="price-badge">
                                <i class="fas fa-tag me-2"></i>රු. <?php echo number_format($service_data['price'], 2); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Important Notice -->
                    <div class="info-box fade-in mt-4">
                        <h5 class="fw-bold mb-3">
                            <i class="fas fa-exclamation-circle me-2"></i>සැලකිය යුතු කරුණු
                        </h5>
                        <ul class="mb-0">
                            <li class="mb-2">ඔබගේ ඉල්ලීම ලැබුණු පසු, අපගේ විශේෂඥයෝ ඔබව <strong>WhatsApp</strong> හරහා සම්බන්ධ කර ගනී.</li>
                            <li class="mb-2">අවශ්‍ය අතිරේක තොරතුරු (උපන් දිනය, වේලාව, ස්ථානය ආදිය) ලබා ගනු ඇත.</li>
                            <li class="mb-2">ගෙවීම් ක්‍රමවේදය සහ බැංකු තොරතුරු දැනුම් දෙනු ඇත.</li>
                            <li class="mb-0">ගෙවීම සම්පූර්ණ කළ පසු, ගෙවීම් ස්ලිප් පිටපත උඩුගත කිරීමට ඔබට අවස්ථාව ලැබේ.</li>
                        </ul>
                    </div>

                    <!-- Contact Support -->
                    <div class="text-center mt-4">
                        <p class="lead mb-3">ප්‍රශ්න තිබේද? අදම අප හා සම්බන්ධ වන්න</p>
                        <a href="https://wa.me/94712391317" target="_blank" class="btn btn-lg whatsapp-btn text-white shadow">
                            <i class="fab fa-whatsapp fa-2x me-2"></i> WhatsApp හරහා අමතන්න
                        </a>
                    </div>
                </div>

                <!-- Request Form Column -->
                <?php if ($form_visible): ?>
                <div class="col-lg-5 mb-4 fade-in">
                    <div class="form-card sticky-top" style="top: 100px;">
                        <div class="text-center mb-4">
                            <div class="feature-icon warning mx-auto mb-3">
                                <i class="fas fa-paper-plane fa-3x text-white"></i>
                            </div>
                            <h3 class="fw-bold">සේවාවක් ඉල්ලා සිටින්න</h3>
                            <p class="mb-0 opacity-75">පහත පෝරමය සම්පූර්ණ කරන්න</p>
                        </div>

                        <form method="POST" action="service_details.php?id=<?php echo $service_id; ?>" id="requestForm">
                            
                            <div class="mb-4">
                                <label for="client_name" class="form-label">
                                    <i class="fas fa-user me-2"></i>ඔබගේ සම්පූර්ණ නම
                                </label>
                                <input type="text" class="form-control" id="client_name" name="client_name" 
                                       placeholder="ඔබගේ නම ඇතුළත් කරන්න" required>
                            </div>

                            <div class="mb-4">
                                <label for="contact_whatsapp" class="form-label">
                                    <i class="fab fa-whatsapp me-2"></i>WhatsApp අංකය
                                </label>
                                <input type="text" class="form-control" id="contact_whatsapp" name="contact_whatsapp" 
                                       placeholder="උදා: +94771234567" required>
                                <div class="form-text text-white-50">
                                    <small><i class="fas fa-info-circle me-1"></i>අප ඔබව සම්බන්ධ කර ගන්නේ මෙම අංකය හරහායි</small>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="request_details" class="form-label">
                                    <i class="fas fa-align-left me-2"></i>අවශ්‍ය විස්තර
                                </label>
                                <textarea class="form-control" id="request_details" name="request_details" rows="5" 
                                          placeholder="උදා: උපන් දිනය, වේලාව, ස්ථානය හෝ වාස්තු ගැටලුව පිළිබඳ කෙටි සටහනක්" required></textarea>
                            </div>

                            <button type="submit" class="btn btn-submit w-100">
                                <i class="fas fa-paper-plane me-2"></i> ඉල්ලීම ඉදිරිපත් කරන්න
                            </button>
                            
                            <div class="text-center mt-3">
                                <small class="text-white-50">
                                    <i class="fas fa-lock me-1"></i> ඔබගේ තොරතුරු සම්පූර්ණයෙන්ම රහස්‍යව පවතී
                                </small>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

   
    
    <script>
        // Form validation enhancement
        document.getElementById('requestForm')?.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>ඉල්ලීම යවමින්...';
            submitBtn.disabled = true;
        });

        // Smooth scroll to top after page load
        window.addEventListener('load', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    </script>

    <?php $conn->close(); ?>
</body>
</html>
<?php include('footer.php'); ?>