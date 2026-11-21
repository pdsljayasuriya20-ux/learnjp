<?php
/**
 * includes/tracker-inject.php
 * Automatically adds <script src="/tracking.js"> before </body> on every HTML page,
 * so you do NOT have to edit each page by hand.
 *
 * Enable it once (pick ONE):
 *   .user.ini  (public_html/.user.ini):   auto_prepend_file = "/home/YOURUSER/public_html/includes/tracker-inject.php"
 *   .htaccess  (Apache + mod_php only):   php_value auto_prepend_file "/home/YOURUSER/public_html/includes/tracker-inject.php"
 * (.user.ini changes can take ~5 minutes to apply.)
 */
(function () {
    if (PHP_SAPI === 'cli') return;

    $script = strtolower(basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
    if (in_array($script, ['track.php', 'admin-users.php'], true)) return;
    if (strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') return;
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;

    ob_start(function (string $buf): string {
        $t = ltrim($buf);
        if ($t === '' || $t[0] === '{' || $t[0] === '[') return $buf;           // JSON / empty
        if (stripos($buf, 'tracking.js') !== false) return $buf;                // already on the page
        $pos = strripos($buf, '</body>');
        if ($pos === false) return $buf;                                        // not a full HTML page
        return substr_replace($buf, '<script src="/tracking.js" defer></script>' . "\n", $pos, 0);
    });
})();
