<?php
// n3.php — JLPT N3 dashboard (layout lives in level_dashboard.php)
// !! Replace 'comingsoon.php' with the real page of each section once it exists.
$dLevel = 'N3';
$dHero  = 'JLPT N3 පාඩම් මාලාව';
$dSub   = 'දෛනික ජීවිතයේ භාවිතා වන ජපන් භාෂාව තේරුම් ගැනීමට අවශ්‍ය කන්ජි, ව්‍යාකරණ සහ වචන මාලාව.';
$dLinks = [
    'kanji'      => 'kanjin3.php',
    'grammar'    => 'grammarn3.php',
    'listening'  => 'comingsoon.php',
    'reading'    => 'comingsoon.php',   // e.g. 'n3reading1.php'
    'vocabulary' => 'words_according_to_level.php?level=3',   // opens the N3 list directly (200 free words)
    'pastpapers' => 'pastpapers.php?level=N3',   // NEW: change to 'pastpapers.php?level=N3' when the past-papers page is uploaded
    'driving'    => 'driving.php',
];

$pageTitle       = 'JLPT N3 පාඩම් මාලාව සිංහලෙන් | ලංකාවේ සහ ජපානයේ ඉන්න ශ්‍රී ලාංකිකයන් සඳහා';
$pageDescription = 'JLPT N3 විභාගය සඳහා කන්ජි, ව්‍යාකරණ, වචන මාලාව සහ කියවීම් පාඩම් සිංහලෙන්.';
$pageKeywords    = 'JLPT N3 sinhala, JLPT N3 kanji, JLPT N3 grammar sinhala, JLPT N3 vocabulary sinhala, jlpt n3 sri lanka, jlpt n3 japan';

require __DIR__ . '/level_dashboard.php';