<?php
/** manifest.php - web app manifest (PHP so no server config is needed for the .webmanifest MIME type) */
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=86400');
echo json_encode([
    'name'             => 'Learn Japanese - learnjapanese.jp',
    'short_name'       => 'Learn Japanese',
    'description'      => 'JLPT N5–N1 කංජි, ව්‍යාකරණ සහ වචන මාලාව සිංහලෙන්',
    'lang'             => 'si',
    'start_url'        => '/dashboard.php?source=app',
    'scope'            => '/',
    'display'          => 'standalone',
    'orientation'      => 'portrait',
    'background_color' => '#e6e9f2',
    'theme_color'      => '#4f46e5',
    'icons'            => [
        ['src' => '/assets/icons/icon-192.png',          'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => '/assets/icons/icon-512.png',          'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => '/assets/icons/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
    ],
    'shortcuts' => [
        ['name' => 'ශබ්ද කෝෂය', 'url' => '/dictionary.php'],
        ['name' => 'මගේ ගිණුම', 'url' => '/dashboard.php'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
