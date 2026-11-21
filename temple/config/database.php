<?php
declare(strict_types=1);

/**
 * Database Connection Engine (PDO)
 * Temple Dayaka Management System - Version 1.0
 */

// MAMP MySQL Settings
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306'); // MAMP MySQL Default Port එක 8889 වේ. (3306 වැඩ නොකළහොත් 8889 යොදන්න)
define('DB_NAME', 'sampath_temple_db');
define('DB_USER', 'sampath_55');
define('DB_PASS', 'mkcriLS5C8tL'); // MAMP Default Password එක root වේ
define('DB_CHARSET', 'utf8mb4');

class Database 
{
    private static ?PDO $instance = null;

    /**
     * Get active PDO database instance
     */
    public static function getConnection(): PDO 
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // If Port 8889 fails, fallback attempt to Port 3306
                if (DB_PORT === '8889') {
                    try {
                        $fallbackDsn = sprintf("mysql:host=%s;port=3306;dbname=%s;charset=%s", DB_HOST, DB_NAME, DB_CHARSET);
                        self::$instance = new PDO($fallbackDsn, DB_USER, DB_PASS, $options);
                        return self::$instance;
                    } catch (PDOException $ex) {
                        // Throw original exception if both ports fail
                    }
                }

                error_log("Database Connection Error: " . $e->getMessage());
                die("<div style='padding:20px; font-family:Arial; color:red; border:1px solid red; margin:20px; background:#fff;'>
                        <h3>දත්ත සමුදාය හා සම්බන්ධ වීමට නොහැකි විය (Database Connection Error)</h3>
                        <p><strong>Error Details:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                        <p>කරුණාකර MAMP හි MySQL Server එක Start කර ඇත්දැයි සහ phpMyAdmin මඟින් <code>temple_db</code> නමින් Database එක සාදා ඇත්දැයි පරීක්ෂා කරන්න.</p>
                     </div>");
            }
        }

        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
}