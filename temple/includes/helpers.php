<?php
declare(strict_types=1);

/**
 * Global Utilities & Privacy Masking Helpers
 * Temple Dayaka Management System - Version 1.0 Blueprint
 */

if (!function_exists('calculateAge')) {
    function calculateAge(string $dob): int {
        if (empty($dob)) return 0;
        $birthDate = new DateTime($dob);
        $today     = new DateTime('today');
        return $birthDate->diff($today)->y;
    }
}

/**
 * Mask Phone Numbers for Public View Privacy (e.g. 0712345678 -> 071*****78)
 */
if (!function_exists('maskPhoneNumber')) {
    function maskPhoneNumber(?string $phone): string {
        if (empty($phone)) return 'නොමැත';
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        $length  = strlen($cleaned);
        if ($length < 8) return '07* *** ****';
        return substr($cleaned, 0, 3) . '*****' . substr($cleaned, -2);
    }
}

if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verifyCSRFToken')) {
    function verifyCSRFToken(?string $token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
    }
}

if (!function_exists('generateMemberID')) {
    function generateMemberID(PDO $db): string {
        $year = date('Y');
        $count = (int)$db->query("SELECT COUNT(*) FROM dayakayan")->fetchColumn() + 1;
        return sprintf("DAY-%s-%05d", $year, $count);
    }
}

if (!function_exists('generateFamilyCode')) {
    function generateFamilyCode(PDO $db): string {
        $count = (int)$db->query("SELECT COUNT(*) FROM families")->fetchColumn() + 1;
        return sprintf("FAM-%05d", $count);
    }
}