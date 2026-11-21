<?php
declare(strict_types=1);

/**
 * System Constants & Global Rules Configuration
 * Temple Dayaka Management System - Version 1.0
 */

// User Roles
define('ROLE_SUPER_ADMIN', 'superadmin');
define('ROLE_ADMIN', 'admin');
define('ROLE_DAYAKAYA', 'dayakaya');

// Status Types
define('STATUS_PENDING', 'pending');
define('STATUS_APPROVED', 'approved');
define('STATUS_REJECTED', 'rejected');
define('STATUS_ACTIVE', 'active');
define('STATUS_SUSPENDED', 'suspended');

// Granular Admin Permission Matrix
define('PERMISSIONS_LIST', [
    'view_dayakayan'        => 'දායකයන් පරීක්ෂාව (View Dayakayan)',
    'add_dayakayan'         => 'අලුත් දායකයන් ඇතුළත් කිරීම (Add Dayakayan)',
    'edit_dayakayan'        => 'දායක තොරතුරු සංස්කරණය (Edit Dayakayan)',
    'delete_dayakayan'      => 'දායකයන් ඉවත් කිරීම (Delete Dayakayan)',
    'approve_registrations' => 'ලියාපදිංචි අනුමත කිරීම (Approve Registrations)',
    'manage_donations'      => 'මාසික දායක මුදල් පාලනය (Manage Donations)',
    'manage_pinkam'         => 'පිංකම් සහ කඨින පාලනය (Manage Pinkam)',
    'manage_associations'   => 'සමිති/සංගම් පාලනය (Manage Associations)',
    'view_reports'          => 'වාර්තා පරීක්ෂාව (View Reports)',
    'export_data'           => 'දත්ත පිටපත් ලබාගැනීම (Export Data)',
    'manage_users'          => 'පරිපාලකයන් පාලනය (Manage Users)'
]);

// Peruwa Master List
define('PERUWA_LIST', [
    1 => '1 පේරුව',
    2 => '2 පේරුව',
    3 => '3 පේරුව',
    4 => '4 පේරුව'
]);