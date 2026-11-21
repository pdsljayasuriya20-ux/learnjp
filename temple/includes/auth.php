<?php
declare(strict_types=1);

/**
 * Authorization Guard Engine
 * Temple Dayaka Management System - Version 1.0
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

class Auth 
{
    public static function check(): bool 
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public static function user(): ?array 
    {
        return self::check() ? ($_SESSION['user'] ?? null) : null;
    }

    public static function isSuperAdmin(): bool 
    {
        $user = self::user();
        return $user && ($user['role'] === ROLE_SUPER_ADMIN || $user['role'] === 'super_admin');
    }

    public static function hasPermission(string $permissionKey): bool 
    {
        if (self::isSuperAdmin()) return true;

        $user = self::user();
        if (!$user) return false;

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT COUNT(*) FROM admin_permissions WHERE user_id = :uid AND permission_key = :perm");
            $stmt->execute(['uid' => $user['id'], 'perm' => $permissionKey]);
            return ((int)$stmt->fetchColumn()) > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function requireAuth(): void 
    {
        if (!self::check()) {
            redirect(APP_URL . '/public/login.php');
        }
    }

    public static function requirePermission(string $permissionKey): void 
    {
        self::requireAuth();
        if (!self::hasPermission($permissionKey)) {
            http_response_code(403);
            die("<h2 style='color:red; text-align:center; margin-top:50px;'>ඔබට මෙම පිටුවට ප්‍රවේශ වීමට අවසර නැත. (403 Permission Denied)</h2>");
        }
    }
}