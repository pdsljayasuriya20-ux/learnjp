<?php
declare(strict_types=1);

/**
 * Core Application Configuration
 * Temple Dayaka Management System - Version 1.0 Blueprint
 */

date_default_timezone_set('Asia/Colombo');

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Dynamic Base URL Resolution
$scheme  = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host    = $_SERVER['HTTP_HOST'] ?? 'localhost:8888';
$baseUrl = $scheme . '://' . $host . '/temple';

define('APP_NAME', 'TDMS');
define('APP_VERSION', '1.0.0');
define('APP_URL', $baseUrl);
define('ADMIN_URL', APP_URL . '/admin');
define('PUBLIC_URL', APP_URL . '/public');

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

if (!function_exists('redirect')) {
    function redirect(string $url): void {
        header("Location: " . $url);
        exit;
    }
}

if (!function_exists('e')) {
    function e(?string $string): string {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}