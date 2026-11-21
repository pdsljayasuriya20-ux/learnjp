<?php
// Session ආරම්භ කිරීම
session_start();

// දත්ත සමුදා සම්බන්ධතාවය ඇතුළත් කිරීම
include('../db_connection.php');

// පරිපාලක ආරක්ෂක පරීක්ෂාව
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// මේ පිටුවට එන සියලුම ගොනු වලට අවශ්‍ය මූලික HTML සහ Bootstrap සම්බන්ධතාවය
?>
<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>නෙත්සිත ජෝතිශ්‍ය සේවය - Admin Panel</title> 
    
    <link rel="icon" type="image/png" href="../images/favicon.png">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" 
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <meta property="og:title" content="නෙත්සිත ජෝතිශ්‍ය සේවය - ඔබේ දෛනික අනාවැකි" />
    <meta property="og:description" content="අපගේ නවතම ජ්‍යෝතිෂ අනාවැකි සහ සේවාවන් පිළිබඳව දැනුවත් වන්න." />
    <meta property="og:image" content="../images/feature_image.jpeg" /> 
    <meta property="og:url" content="https://www.yourwebsite.com/" />
    <meta property="og:type" content="website" />
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* -------------------------------------- */
        /* Desktop/Default Styles */
        /* -------------------------------------- */
        :root {
            --sidebar-width: 250px;
        }
        
        body {
            /* Navbar සහ Sidebar සඳහා පෑඩිං සකසයි */
            padding-top: 56px;
        }

        .sidebar {
            height: 100vh;
            width: var(--sidebar-width);
            position: fixed;
            top: 0;
            left: 0;
            background-color: #343a40; /* Dark background */
            padding-top: 56px; /* Navbar උසට ඉඩ දීමට */
            z-index: 1030; /* Navbar එකට යටින් තැබීමට */
            transition: transform 0.3s ease-in-out;
        }
        
        .sidebar a {
            padding: 10px 15px;
            text-decoration: none;
            font-size: 16px; /* කුඩා කර ඇත */
            color: #ccc;
            display: block;
        }
        .sidebar a:hover {
            background-color: #495057;
            color: white;
        }
        
        .content {
            margin-left: var(--sidebar-width); /* Sidebar එකට ඉඩ දීමට */
            padding: 20px;
            transition: margin-left 0.3s ease-in-out;
        }
        
        /* Sidebar Toggle Button (Desktop එකේ සැඟවීමට) */
        .sidebar-toggler {
            display: none;
        }

        /* -------------------------------------- */
        /* Mobile Responsive Styles */
        /* -------------------------------------- */
        @media (max-width: 768px) {
            
            /* Sidebar එක Off-canvas ලෙස සකසයි */
            .sidebar {
                transform: translateX(-100%); /* තිරයෙන් පිටත සැඟවීම */
            }
            
            .sidebar.active {
                transform: translateX(0); /* තිරය මත පෙන්වීම */
            }
            
            /* Content එක Sidebar එකට යටින් පෙන්වයි, margin-left ඉවත් කරයි */
            .content {
                margin-left: 0;
                padding-left: 10px;
                padding-right: 10px;
            }
            
            /* Sidebar Toggle Button (Mobile එකේ පෙන්වයි) */
            .sidebar-toggler {
                display: block;
            }
            
            /* Page Overlay (Sidebar එක විවෘත වූ විට අඳුරු තිරය) */
            .overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1029; /* Sidebar එකට යටින්, Navbar එකට උඩින් */
                display: none;
            }
            
            .overlay.active {
                display: block;
            }
        }
    </style>
</head>
<body>
    
    <div class="overlay" id="sidebar-overlay"></div>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <button class="btn btn-outline-light sidebar-toggler me-3" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>

            <a class="navbar-brand" href="dashboard.php">නෙත්සිත Admin Panel</a>
            
            <div class="d-flex ms-auto">
                <span class="navbar-text text-white me-3 d-none d-sm-block">
                    සාදරයෙන් පිළිගනිමු, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!
                </span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="sidebar" id="adminSidebar">
        <a href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
        <a href="manage_requests.php"><i class="fas fa-users me-2"></i> සේවා ඉල්ලීම්</a>
        <a href="manage_services.php"><i class="fas fa-list me-2"></i> සේවා කළමනාකරණය</a>
        <a href="manage_appointments.php"><i class="fas fa-calendar-alt me-2"></i> හමුවීම්</a>
        <a href="view_inquiries.php"><i class="fas fa-question-circle me-2"></i> විමසීම්</a>
        <a href="backup_db.php"><i class="fas fa-database me-2"></i> Backup</a>
        <a href="settings.php"><i class="fas fa-cog me-2"></i> සැකසීම්</a>
        </div>

    <div class="content">