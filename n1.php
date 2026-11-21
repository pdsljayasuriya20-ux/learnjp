<?php
// n1.php — JLPT N1 dashboard (layout lives in level_dashboard.php)
// !! Replace 'comingsoon.php' with the real page of each section once it exists.
$dLevel = 'N1';
$dHero  = 'JLPT N1 පාඩම් මාලාව';
$dSub   = 'ජපන් භාෂාවේ උසස්ම මට්ටම — සංකීර්ණ කන්ජි, ව්‍යාකරණ සහ වචන මාලාව.';
$dLinks = [
    'kanji'      => 'kanjin1.php',
    'grammar'    => 'grammarn1.php',
    'listening'  => 'comingsoon.php',
    'reading'    => 'comingsoon.php',   // e.g. 'n1reading1.php'
    'vocabulary' => 'words_according_to_level.php',   // e.g. 'vocabularyn1.php'
    'driving'    => 'driving.php',
];

$pageTitle       = 'JLPT N1 පාඩම් මාලාව සිංහලෙන් | ලංකාවේ සහ ජපානයේ ඉන්න ශ්‍රී ලාංකිකයන් සඳහා';
$pageDescription = 'JLPT N1 විභාගය සඳහා කන්ජි, ව්‍යාකරණ, වචන මාලාව සහ කියවීම් පාඩම් සිංහලෙන්.';
$pageKeywords    = 'JLPT N1 sinhala, JLPT N1 kanji, JLPT N1 grammar sinhala, JLPT N1 vocabulary sinhala, jlpt n1 sri lanka, jlpt n1 japan';

require __DIR__ . '/level_dashboard.php';
