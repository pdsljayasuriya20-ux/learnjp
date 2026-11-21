<?php
// Header ගොනුව ඇතුළත් කිරීම (මෙහිදී ආරක්ෂාව සහ DB සම්බන්ධතාවය සකස් වේ)
include('includes/header.php');

// Dashboard එකට අවශ්‍ය දත්ත ලබා ගැනීම
$total_services_query = $conn->query("SELECT COUNT(id) AS total_services FROM services");
$total_services = $total_services_query->fetch_assoc()['total_services'];

$pending_requests_query = $conn->query("SELECT COUNT(id) AS pending_requests FROM client_requests WHERE status = 'Pending'");
$pending_requests = $pending_requests_query->fetch_assoc()['pending_requests'];

$completed_requests_query = $conn->query("SELECT COUNT(id) AS completed_requests FROM client_requests WHERE status = 'Completed'");
$completed_requests = $completed_requests_query->fetch_assoc()['completed_requests'];
?>
<div class="container my-5">
<h2 class="mb-4">📊 ප්‍රධාන පුවරුව (Dashboard)</h2>
<hr>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-primary shadow">
            <div class="card-body">
                <div class="row">
                    <div class="col-auto">
                        <i class="fas fa-list fa-2x"></i>
                    </div>
                    <div class="col">
                        <div class="h5 mb-0 fw-bold">මුළු සේවා ගණන</div>
                        <div class="fs-3"><?php echo $total_services; ?></div>
                    </div>
                </div>
            </div>
            <a href="manage_services.php" class="card-footer text-white bg-dark text-decoration-none">
                තවත් විස්තර බලන්න <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card text-white bg-warning shadow">
            <div class="card-body">
                <div class="row">
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                    <div class="col">
                        <div class="h5 mb-0 fw-bold">විමසීම් (Pending)</div>
                        <div class="fs-3"><?php echo $pending_requests; ?></div>
                    </div>
                </div>
            </div>
            <a href="manage_requests.php" class="card-footer text-white bg-dark text-decoration-none">
                ඉල්ලීම් බලන්න <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card text-white bg-success shadow">
            <div class="card-body">
                <div class="row">
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <div class="col">
                        <div class="h5 mb-0 fw-bold">සම්පූර්ණ කළ සේවා</div>
                        <div class="fs-3"><?php echo $completed_requests; ?></div>
                    </div>
                </div>
            </div>
            <a href="manage_requests.php?status=Completed" class="card-footer text-white bg-dark text-decoration-none">
                වාර්තා බලන්න <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="alert alert-info mt-4">
    **උපදෙස්:** ඔබට දැන් `admin/login.php` වෙත ගොස් **Username:** `admin` සහ **Password:** `admin123` යොදා පිවිසී Dashboard එක පරීක්ෂා කළ හැකිය.
</div>

<?php
// Footer ගොනුව ඇතුළත් කිරීම
include('includes/footer.php');
?>