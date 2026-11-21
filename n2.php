<?php
// n2.php — JLPT N2 dashboard (layout lives in level_dashboard.php)
// !! Replace 'comingsoon.php' with the real page of each section once it exists.
$dLevel = 'N2';
$dHero  = 'JLPT N2 පාඩම් මාලාව';
$dSub   = 'ව්‍යාපාරික සහ සමාජීය තත්ත්වයන්ට ගැලපෙන ඉහළ මට්ටමේ කන්ජි, ව්‍යාකරණ සහ වචන මාලාව.';
$dLinks = [
    'kanji'      => 'kanjin2.php',
    'grammar'    => 'grammarn2.php',
    'listening'  => 'comingsoon.php',
    'reading'    => 'comingsoon.php',   // e.g. 'n2reading1.php'
    'vocabulary' => 'words_according_to_level.php',   // e.g. 'vocabularyn2.php'
    'driving'    => 'driving.php',
];

$pageTitle       = 'JLPT N2 පාඩම් මාලාව සිංහලෙන් | ලංකාවේ සහ ජපානයේ ඉන්න ශ්‍රී ලාංකිකයන් සඳහා';
$pageDescription = 'JLPT N2 විභාගය සඳහා කන්ජි, ව්‍යාකරණ, වචන මාලාව සහ කියවීම් පාඩම් සිංහලෙන්.';
$pageKeywords    = 'JLPT N2 sinhala, JLPT N2 kanji, JLPT N2 grammar sinhala, JLPT N2 vocabulary sinhala, jlpt n2 sri lanka, jlpt n2 japan';

require __DIR__ . '/level_dashboard.php';
