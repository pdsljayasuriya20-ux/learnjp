<?php
/**
 * Role-Based Dynamic Navigation Sidebar
 * Temple Dayaka & Daham Pasala Management System
 * Version 2.0
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$currentPage = $_SERVER['PHP_SELF'] ?? '';

/**
 * Check whether the current page belongs to a menu section.
 */
if (!function_exists('sidebarIsActive')) {
    function sidebarIsActive(string $path): bool
    {
        $current = $_SERVER['PHP_SELF'] ?? '';
        return strpos($current, $path) !== false;
    }
}

/**
 * Active class helper.
 */
if (!function_exists('sidebarActiveClass')) {
    function sidebarActiveClass(string $path): string
    {
        return sidebarIsActive($path) ? 'active' : '';
    }
}
?>

<div class="sidebar d-flex flex-column shadow">

    <!-- Sidebar Header -->
    <div class="p-3 text-center border-bottom border-secondary bg-dark bg-opacity-50">
        <h6 class="m-0 text-warning fw-bold">
            <i class="fa-solid fa-shield-halved me-2"></i>
            පරිපාලන පාලක පුවරුව
        </h6>
    </div>

    <!-- Navigation -->
    <ul class="nav nav-pills flex-column mb-auto">

        <!-- Dashboard -->
        <li class="nav-item">
            <a href="<?= ADMIN_URL ?>/dashboard.php"
               class="nav-link <?= sidebarActiveClass('/dashboard.php') ?>">
                <i class="fa-solid fa-chart-line me-2"></i>
                ඩෑෂ්බෝඩ් එක (Dashboard)
            </a>
        </li>

        <!-- Dayakayan -->
        <?php if (Auth::hasPermission('view_dayakayan')): ?>
        <li class="nav-item">
            <a href="<?= ADMIN_URL ?>/dayakayan/index.php"
               class="nav-link <?= sidebarActiveClass('/dayakayan/') ?>">
                <i class="fa-solid fa-users me-2"></i>
                දායක නාමාවලිය
            </a>
        </li>
        <?php endif; ?>

        <!-- Registration Approvals -->
        <?php if (Auth::hasPermission('approve_registrations')): ?>
        <li class="nav-item">
            <a href="<?= ADMIN_URL ?>/approvals/registrations.php"
               class="nav-link <?= sidebarActiveClass('registrations.php') ?>">
                <i class="fa-solid fa-user-check me-2"></i>
                ලියාපදිංචි ඉල්ලීම් අනුමතය
            </a>
        </li>

        <li class="nav-item">
            <a href="<?= ADMIN_URL ?>/approvals/change_requests.php"
               class="nav-link <?= sidebarActiveClass('change_requests.php') ?>">
                <i class="fa-solid fa-code-compare me-2"></i>
                දත්ත වෙනස්කම් අනුමතය
            </a>
        </li>
        <?php endif; ?>

        <!-- Donations -->
        <?php if (Auth::hasPermission('manage_donations')): ?>
        <li class="nav-item">
            <a href="<?= ADMIN_URL ?>/donations/index.php"
               class="nav-link <?= sidebarActiveClass('/donations/') ?>">
                <i class="fa-solid fa-hand-holding-dollar me-2"></i>
                මාසික දායක මුදල්
            </a>
        </li>
        <?php endif; ?>

        <!-- Pinkam Management -->
        <?php if (Auth::hasPermission('manage_pinkam')): ?>
        <li class="nav-item">
            <a href="<?= ADMIN_URL ?>/pinkam/manage_pinkam.php"
               class="nav-link <?= sidebarActiveClass('/pinkam/') ?>">
                <i class="fa-solid fa-dharmachakra me-2"></i>
            පිංකම් පාලනය
            </a>
        </li>
        <?php endif; ?>

        <!-- Associations -->
        <?php if (Auth::hasPermission('manage_associations')): ?>
        <li class="nav-item">
            <a href="<?= ADMIN_URL ?>/master_data/associations.php"
               class="nav-link <?= sidebarActiveClass('/master_data/') ?>">
                <i class="fa-solid fa-sitemap me-2"></i>
                සමිති / සංගම් පාලනය
            </a>
        </li>
        <?php endif; ?>

        <!-- Reports -->
        <?php if (Auth::hasPermission('view_reports')): ?>
        <li class="nav-item">
            <a href="<?= ADMIN_URL ?>/reports/dayaka_reports.php"
               class="nav-link <?= sidebarActiveClass('/reports/') ?>">
                <i class="fa-solid fa-file-invoice me-2"></i>
                වාර්තා සහ විශ්ලේෂණ
            </a>
        </li>
        <?php endif; ?>


        <!-- ========================================= -->
        <!-- DAHAM PASALA MANAGEMENT -->
        <!-- ========================================= -->

        <?php
        /*
         * The Daham Pasala menu uses a permission check.
         * Add 'manage_daham_pasala' to the relevant user's
         * permissions, or allow super admins.
         */
        $canManageDahamPasala =
            Auth::isSuperAdmin()
            || Auth::hasPermission('manage_daham_pasala');
        ?>

        <?php if ($canManageDahamPasala): ?>

        <li class="nav-item border-top border-secondary mt-2 pt-2">

            <div class="px-3 pt-2 pb-1 text-warning small fw-bold">
                <i class="fa-solid fa-book-open me-2"></i>
                දහම් පාසල් කළමනාකරණය
            </div>

        </li>

        <!-- Daham Pasala Dashboard -->
        <li class="nav-item">
            <a href="<?= ADMIN_URL ?>/daham_pasala/index.php"
               class="nav-link <?= sidebarActiveClass('/daham_pasala/index.php') ?>">
                <i class="fa-solid fa-gauge-high me-2"></i>
                දහම් පාසල් Dashboard
            </a>
        </li>

        <!-- Students -->
        <li class="nav-item">
            <a href="<?= ADMIN_URL ?>/daham_pasala/students/index.php"
               class="nav-link <?= sidebarActiveClass('/daham_pasala/students/') ?>">
                <i class="fa-solid fa-user-graduate me-2"></i>
                ශිෂ්‍ය කළමනාකරණය
            </a>
        </li>

        <!-- Student Cards -->
        <li class="nav-item">
            <a href="<?= ADMIN_URL ?>/daham_pasala/students/cards.php"
               class="nav-link <?= sidebarActiveClass('/daham_pasala/students/cards.php') ?>">
                <i class="fa-solid fa-id-card me-2"></i>
                ශිෂ්‍ය හැඳුනුම්පත්
            </a>
        </li>

        <!-- Teachers -->
        <li class="nav-item">
            <a href="<?= ADMIN_URL ?>/daham_pasala/teachers/index.php"
               class="nav-link <?= sidebarActiveClass('/daham_pasala/teachers/') ?>">
                <i class="fa-solid fa-chalkboard-user me-2"></i>
                ගුරු කළමනාකරණය
            </a>
        </li>

        <!-- Classes -->
        <li class="nav-item">
            <a href="<?= ADMIN_URL ?>/daham_pasala/classes/index.php"
               class="nav-link <?= sidebarActiveClass('/daham_pasala/classes/') ?>">
                <i class="fa-solid fa-school me-2"></i>
                ශ්‍රේණි සහ පන්ති
            </a>
        </li>

        <!-- Attendance -->
        <li class="nav-item">
            <a href="<?= ADMIN_URL ?>/daham_pasala/attendance/index.php"
               class="nav-link <?= sidebarActiveClass('/daham_pasala/attendance/') ?>">
                <i class="fa-solid fa-calendar-check me-2"></i>
                ශිෂ්‍ය පැමිණීම
            </a>
        </li>

        <!-- Exams / Results -->
        <li class="nav-item">
            <a href="<?= ADMIN_URL ?>/daham_pasala/exams/index.php"
               class="nav-link <?= sidebarActiveClass('/daham_pasala/exams/') ?>">
                <i class="fa-solid fa-file-pen me-2"></i>
                විභාග සහ ලකුණු
            </a>
        </li>

        <!-- Daham Pasala Reports -->
        <li class="nav-item">
            <a href="<?= ADMIN_URL ?>/daham_pasala/reports/index.php"
               class="nav-link <?= sidebarActiveClass('/daham_pasala/reports/') ?>">
                <i class="fa-solid fa-chart-column me-2"></i>
                දහම් පාසල් වාර්තා
            </a>
        </li>

        <?php endif; ?>


        <!-- ========================================= -->
        <!-- SUPER ADMIN -->
        <!-- ========================================= -->

        <?php if (Auth::isSuperAdmin()): ?>

        <li class="nav-item border-top border-secondary mt-2 pt-2">
            <a href="<?= ADMIN_URL ?>/users/index.php"
               class="nav-link <?= sidebarActiveClass('/users/') ?>">
                <i class="fa-solid fa-user-shield me-2"></i>
                පරිපාලකයන් (Admins)
            </a>
        </li>

        <li class="nav-item">
            <a href="<?= ADMIN_URL ?>/system/audit_trail.php"
               class="nav-link <?= sidebarActiveClass('audit_trail.php') ?>">
                <i class="fa-solid fa-list-check me-2"></i>
                System Audit Logs
            </a>
        </li>

        <?php endif; ?>

    </ul>
</div>