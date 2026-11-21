<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

/** Writes audit events using the columns present in temple_db.sql. */
class AuditLogger
{
    public static function log(string $action, string $tableName = '', int $recordId = 0, ?array $oldValues = null, ?array $newValues = null): void
    {
        try {
            $db = Database::getConnection();
            $user = Auth::user();
            $details = [];
            if ($tableName !== '') $details['table'] = $tableName;
            if ($recordId > 0) $details['record_id'] = $recordId;
            if ($oldValues !== null) $details['old_values'] = $oldValues;
            if ($newValues !== null) $details['new_values'] = $newValues;
            $stmt = $db->prepare('INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (:user_id, :action, :details, :ip_address)');
            $stmt->execute([
                'user_id' => $user ? (int)$user['id'] : null,
                'action' => strtoupper($action),
                'details' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (Throwable $e) {
            error_log('Audit Logger Failure: ' . $e->getMessage());
        }
    }
}
