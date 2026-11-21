<?php
declare(strict_types=1);

/**
 * Global Dynamic Navigation Header
 * විහාරස්ථාන දත්ත කළමණාකරණ පද්දතිය (TDMS)
 * Version 1.0
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e(APP_NAME) ?></title>

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Font Awesome Icons -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    >

    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .bg-temple {
            background: linear-gradient(
                135deg,
                #1e1e1e 0%,
                #2c2c2c 100%
            );
        }

        .navbar-brand-title {
            color: #ffc107;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .navbar-brand-subtitle {
            color: #adb5bd;
            font-size: 11px;
            letter-spacing: 0.4px;
        }

        /* Shared Admin Navigation */
        .admin-shell {
            display: flex;
            align-items: stretch;
            min-height: calc(100vh - 100px);
        }

        .admin-sidebar {
            flex: 0 0 260px;
            width: 260px;
            background: #212529;
            color: #fff;
        }

        .admin-sidebar .sidebar {
            position: sticky;
            top: 0;
            min-height: calc(100vh - 100px);
            padding: 0 12px 16px;
        }

        .admin-sidebar .nav {
            padding: 12px 0;
            gap: 4px;
        }

        .admin-sidebar .nav-link {
            color: #dee2e6;
            border-radius: 8px;
            padding: 10px 12px;
            transition: background 0.15s ease, color 0.15s ease;
        }

        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            color: #212529;
            background: #ffc107;
        }

        .admin-main {
            flex: 1 1 auto;
            min-width: 0;
        }

        @media (max-width: 767.98px) {
            .admin-shell {
                display: block;
            }

            .admin-sidebar {
                width: 100%;
            }

            .admin-sidebar .sidebar {
                min-height: auto;
                position: static;
            }

            .admin-sidebar .nav {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .admin-sidebar .nav-link {
                font-size: 0.875rem;
            }

            .navbar-brand-title {
                font-size: 0.95rem !important;
            }
        }
    </style>
</head>

<body>

<!-- Main Navigation Header -->
<nav class="navbar navbar-expand-lg navbar-dark bg-temple shadow-sm py-3 border-bottom border-warning border-3">
    <div class="container">

        <!-- Application Brand -->
        <a
            class="navbar-brand d-flex align-items-center me-auto"
            href="<?= e(PUBLIC_URL) ?>/index.php"
        >
            <i class="fa-solid fa-gopuram text-warning fs-2 me-3"></i>

            <div>
                <span class="navbar-brand-title fs-5 d-block">
                    විහාරස්ථාන දත්ත කළමණාකරණ පද්දතිය
                </span>

                <small class="navbar-brand-subtitle d-block mt-1">
                    Temple Data Management System (TDMS)
                </small>
            </div>
        </a>

        <!-- Mobile Navigation Toggle -->
        <button
            class="navbar-toggler ms-2"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
            aria-controls="mainNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Header Actions -->
        <div class="collapse navbar-collapse" id="mainNavbar">

            <div class="d-flex align-items-center gap-2 ms-auto mt-3 mt-lg-0">

                <?php if (Auth::check()): ?>

                    <!-- Admin Dashboard -->
                    <a
                        href="<?= e(ADMIN_URL) ?>/dashboard.php"
                        class="btn btn-outline-warning btn-sm fw-bold px-3"
                    >
                        <i class="fa-solid fa-gauge-high me-1"></i>
                        Dashboard
                    </a>

                    <!-- Logout -->
                    <a
                        href="<?= e(PUBLIC_URL) ?>/logout.php"
                        class="btn btn-danger btn-sm fw-bold px-3"
                    >
                        <i class="fa-solid fa-right-from-bracket me-1"></i>
                        Logout
                    </a>

                <?php else: ?>

                    <!-- Admin Login -->
                    <a
                        href="<?= e(PUBLIC_URL) ?>/login.php"
                        class="btn btn-warning text-dark btn-sm fw-bold px-3 shadow-sm"
                    >
                        <i class="fa-solid fa-right-to-bracket me-1"></i>
                        පරිපාලන පිවිසුම (Login)
                    </a>

                <?php endif; ?>

            </div>
        </div>

    </div>
</nav>
