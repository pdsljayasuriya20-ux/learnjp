<?php
/**
 * Simple Autoloader for Stripe PHP SDK
 */

spl_autoload_register(function ($class) {
    $prefix = 'Stripe\\';
    $base_dir = __DIR__ . '/stripe/stripe-php/lib/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Load init.php if available in manual download
if (file_exists(__DIR__ . '/stripe/stripe-php/init.php')) {
    require_once __DIR__ . '/stripe/stripe-php/init.php';
}