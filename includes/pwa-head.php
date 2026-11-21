<?php
/**
 * includes/pwa-head.php
 * Makes the site installable (home-screen icon, full-screen "app" mode).
 *
 * Easiest: put   <?php require_once __DIR__ . '/pwa-head.php'; echo pwa_head_tags(); ?>   inside <head> of includes/header.php
 *          (then every page is installable).
 * Or, for a single page, call  pwa_require_header();  instead of  require_once 'includes/header.php';
 */

function pwa_head_tags(): string {
    return '<link rel="manifest" href="/manifest.php">' . "\n"
         . '<meta name="theme-color" content="#4f46e5">' . "\n"
         . '<meta name="mobile-web-app-capable" content="yes">' . "\n"
         . '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n"
         . '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n"
         . '<meta name="apple-mobile-web-app-title" content="Learn Japanese">' . "\n"
         . '<link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">' . "\n"
         . '<script>if("serviceWorker" in navigator){window.addEventListener("load",function(){navigator.serviceWorker.register("/sw.js").catch(function(){});});}</script>' . "\n";
}

/** includes header.php and injects the tags before </head> (skips tags header.php already has) */
function pwa_require_header(): void {
    ob_start();
    require_once __DIR__ . '/header.php';
    $html = (string) ob_get_clean();
    if (stripos($html, 'rel="manifest"') === false) {
        $tags = pwa_head_tags();
        if (stripos($html, '</head>') !== false) {
            $html = preg_replace('#</head>#i', str_replace(['\\', '$'], ['\\\\', '\\$'], $tags) . '</head>', $html, 1);
        } else {
            $html = $tags . $html;
        }
    }
    echo $html;
}