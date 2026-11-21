<?php
/**
 * නෙත්සිත ජෝතිශ්‍ය සේවය - දත්ත සමුදාය පිටපත් කිරීමේ පිටුව
 */

// පරිපාලක ශීර්ෂය ඇතුළත් කරන්න (සැසිය සහ සම්බන්ධතාවය සැපයිය යුතුය)
include('includes/header.php'); 

$message = '';

// දත්ත සමුදා සම්බන්ධතාවය තහවුරු කිරීම
if (!isset($conn) || $conn->connect_error) {
    $message = '<div class="alert alert-danger">දත්ත සමුදාය සම්බන්ධ කිරීමේ දෝෂයයි. Backup කළ නොහැක.</div>';
    $conn = null;
}
?>

<div class="container my-5">

    <h2 class="mb-4"><i class="fas fa-history"></i> දත්ත සමුදාය කළමනාකරණය</h2>
    <hr>

    <?php echo $message; ?>

    <div class="row justify-content-center">
        <div class="col-lg-10 col-md-12">
            <div class="row">
                
                <div class="col-md-6 mb-4">
                    <div class="card shadow-lg border-warning h-100">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-file-export"></i> SQL Backup (තාක්ෂණික)</h5>
                        </div>
                        <div class="card-body text-center d-flex flex-column justify-content-between">
                            <div>
                                <i class="fas fa-database fa-4x text-secondary mb-3"></i>
                                <p class="card-text text-muted">
                                    සියලුම වගු සහ ව්‍යුහය ඇතුළුව සම්පූර්ණ දත්ත සමුදාය `.sql` ගොනුවක් ලෙස ලබා ගන්න. (Restore සඳහා හොඳම ආකෘතිය)
                                </p>
                            </div>
                            <?php if ($conn): ?>
                                <a href="download_db.php" class="btn btn-warning btn-lg w-100 mt-3 fw-bold">
                                    <i class="fas fa-download me-2"></i>SQL ගොනුව බාගත කරන්න
                                </a>
                            <?php else: ?>
                                <button class="btn btn-warning btn-lg w-100 mt-3" disabled>
                                    <i class="fas fa-times me-2"></i>සම්බන්ධතාවය නැත
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card shadow-lg border-success h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-file-csv"></i> Excel/CSV Backup (පහසු)</h5>
                        </div>
                        <div class="card-body text-center d-flex flex-column justify-content-between">
                            <div>
                                <i class="fas fa-file-excel fa-4x text-success mb-3"></i>
                                <p class="card-text text-muted">
                                    සියලුම වගු වල දත්ත වෙනම `.csv` ගොනු ලෙස ZIP ආකෘතියෙන් ලබා ගන්න. (පරීක්ෂා කිරීමට/විශ්ලේෂණයට හොඳයි)
                                </p>
                            </div>
                            <?php if ($conn): ?>
                                <a href="download_csv.php" class="btn btn-success btn-lg w-100 mt-3 fw-bold">
                                    <i class="fas fa-file-download me-2"></i>CSV ZIP බාගත කරන්න
                                </a>
                            <?php else: ?>
                                <button class="btn btn-success btn-lg w-100 mt-3" disabled>
                                    <i class="fas fa-times me-2"></i>සම්බන්ධතාවය නැත
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
            </div> <div class="card shadow-sm mt-4 border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-upload"></i> දත්ත ආනයනය (Restore)</h5>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">
                        දත්ත නැවත ආනයනය කිරීමට, ඔබ විසින් කලින් Backup කරන ලද `.sql` ගොනුවක් භාවිතා කළ යුතුය.
                    </p>
                    <p class="card-text text-danger fw-bold">
                        ආනයනය කිරීම දැනට පවතින සියලුම දත්ත නැවත ලියනු ඇත.
                    </p>
                    
                    <div class="alert alert-secondary mt-3">
                        **💡 නිර්දේශය:** ආරක්ෂක හේතු සහ විශාල ගොනු හැසිරවීම සඳහා, දත්ත නැවත ආනයනය (Restore) කිරීමට කරුණාකර **phpMyAdmin** හෝ වෙනත් Hosting Control Panel මෙවලම් භාවිතා කරන්න.
                    </div>
                    
                    <form method="POST" action="#" enctype="multipart/form-data">
                        <input type="file" name="sql_file" class="form-control mb-2" accept=".sql" disabled>
                        <button type="button" class="btn btn-secondary w-100" disabled>
                            <i class="fas fa-lock me-2"></i>ආනයනය කළ නොහැක
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// පරිපාලක පාදකය ඇතුළත් කරන්න
include('includes/footer.php');
?>