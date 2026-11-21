<?php
// n4.php — JLPT N4 dashboard (layout lives in level_dashboard.php)
// !! Replace 'comingsoon.php' with the real page of each section once it exists.
$dLevel = 'N4';
$dHero  = 'JLPT N4 පාඩම් මාලාව';
$dSub   = 'මූලික ජපන් භාෂා දැනුම තවත් ඉහළට — කන්ජි, ව්‍යාකරණ සහ වචන මාලාව ඉගෙන ගන්න.';
$dLinks = [
    'kanji'      => 'kanjin4.php',
    'grammar'    => 'grammarn4.php',
    'listening'  => 'comingsoon.php',
    'reading'    => 'comingsoon.php',   // e.g. 'n4reading1.php'
    'vocabulary' => 'words_according_to_level.php',   // e.g. 'vocabularyn4.php'
    'driving'    => 'driving.php',
];

$pageTitle       = 'JLPT N4 පාඩම් මාලාව සිංහලෙන් | ලංකාවේ සහ ජපානයේ ඉන්න ශ්‍රී ලාංකිකයන් සඳහා';
$pageDescription = 'JLPT N4 විභාගය සඳහා කන්ජි, ව්‍යාකරණ, වචන මාලාව සහ කියවීම් පාඩම් සිංහලෙන්.';
$pageKeywords    = 'JLPT N4 sinhala, JLPT N4 kanji, JLPT N4 grammar sinhala, JLPT N4 vocabulary sinhala, jlpt n4 sri lanka, jlpt n4 japan';

require __DIR__ . '/level_dashboard.php';
