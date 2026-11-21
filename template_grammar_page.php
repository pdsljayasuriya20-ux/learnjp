<?php
include 'header.php';

// ------------------------------
// Language Handling
// ------------------------------
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$valid_langs = ['en', 'ja', 'si'];
if (!in_array($lang, $valid_langs)) $lang = 'en';

// ------------------------------
// Current Page / Grammar Range
// ------------------------------
$pageRanges = [
    '1-10' => ['title'=>'N5 Grammar Rules 1-10', 'next'=>'11-20', 'prev'=>null, 'quiz'=>'quiz1-10.php'],
    '11-20' => ['title'=>'N5 Grammar Rules 11-20', 'next'=>'21-30', 'prev'=>'1-10', 'quiz'=>'quiz11-20.php'],
    '21-30' => ['title'=>'N5 Grammar Rules 21-30', 'next'=>null, 'prev'=>'11-20', 'quiz'=>'quiz21-30.php']
];

// Current range from query parameter or default
$currentRange = isset($_GET['range']) && isset($pageRanges[$_GET['range']]) ? $_GET['range'] : '1-10';
$pageInfo = $pageRanges[$currentRange];

// ------------------------------
// Dynamic Page Content
// ------------------------------
$pageContent = [
    'title' => [
        'en' => $pageInfo['title'].' - Learn Japanese',
        'ja' => $pageInfo['title'].' - 日本語を学ぶ',
        'si' => $pageInfo['title'].' - ජපන් ඉගෙන ගන්න'
    ],
    'hero_title' => [
        'en' => $pageInfo['title'],
        'ja' => $pageInfo['title'],
        'si' => $pageInfo['title']
    ],
    'hero_subtitle' => [
        'en' => 'Master Basic Japanese Grammar',
        'ja' => '基本的な日本語文法をマスターする',
        'si' => 'මූලික ජපන් ව්‍යාකරණය ප්‍රගුණ කරන්න'
    ],
    'grammar_sections' => [
        [
            'id' => 'cha-ikenai',
            'number' => '1',
            'title' => 'ちゃいけない / じゃいけない',
            'romaji' => 'cha ikenai / ja ikenai',
            'description' => [
                'en' => 'Must not do (spoken Japanese)',
                'ja' => '〜してはいけない（口語）',
                'si' => 'නොකළ යුතුයි (කථන ජපන්)'
            ]
        ],
        // Add more grammar sections here dynamically
    ]
];

$t = [
    'page_title' => $pageContent['title'][$lang],
    'hero_title' => $pageContent['hero_title'][$lang],
    'hero_subtitle' => $pageContent['hero_subtitle'][$lang]
];
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['page_title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;700;900&family=Poppins:wght@300;400;600;700;800&family=Noto+Sans+Sinhala:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container my-4">

    <!-- Hero Section -->
    <div class="hero-card text-center mb-4">
        <h1 class="hero-title"><?php echo $t['hero_title']; ?></h1>
        <p class="lead"><?php echo $t['hero_subtitle']; ?></p>
    </div>

    <!-- Navigation Buttons -->
    <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
        <?php if($pageInfo['prev']): ?>
            <button class="btn btn-warning btn-lg" onclick="goToPage('n5grammer.php?range=<?php echo $pageInfo['prev']; ?>&lang=<?php echo $lang; ?>')">
                Back <?php echo $pageInfo['prev']; ?>
            </button>
        <?php endif; ?>

        <?php if($pageInfo['next']): ?>
            <button class="btn btn-info btn-lg" onclick="goToPage('n5grammer.php?range=<?php echo $pageInfo['next']; ?>&lang=<?php echo $lang; ?>')">
                Next <?php echo $pageInfo['next']; ?>
            </button>
        <?php endif; ?>

        <button class="btn btn-primary btn-lg" onclick="expandAllGrammar()">Expand All</button>
        <button class="btn btn-danger btn-lg" onclick="collapseAllGrammar()">Collapse All</button>
        <button class="btn btn-dark btn-lg" onclick="goToPage('n5grammer.php?lang=<?php echo $lang; ?>')">All Grammar</button>
    </div>

    <!-- Grammar Sections -->
    <?php foreach($pageContent['grammar_sections'] as $section): ?>
        <div class="character-section border p-3 mb-3 rounded">
            <div class="toggle-header d-flex justify-content-between align-items-center" onclick="toggleGrammar('<?php echo $section['id']; ?>')">
                <div>
                    <h2 class="section-title japanese-text mb-1"><?php echo $section['title']; ?> <small>(<?php echo $section['romaji']; ?>)</small></h2>
                    <p class="mb-0">
                        <span class="language-content <?php if($lang=='en') echo 'active'; ?>"><?php echo $section['description']['en']; ?></span>
                        <span class="language-content japanese-text <?php if($lang=='ja') echo 'active'; ?>"><?php echo $section['description']['ja']; ?></span>
                        <span class="language-content sinhala-text <?php if($lang=='si') echo 'active'; ?>"><?php echo $section['description']['si']; ?></span>
                    </p>
                </div>
                <span class="toggle-icon collapsed" id="<?php echo $section['id']; ?>-icon">▶</span>
            </div>
            <div class="grammar-content collapsed mt-2" id="<?php echo $section['id']; ?>-content">
                <p>Example content for <?php echo $section['title']; ?> goes here.</p>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Start Quiz Button -->
    <div class="d-flex justify-content-center mb-4">
        <button class="btn btn-success btn-lg w-100" style="max-width: 400px;"
            onclick="goToPage('<?php echo $pageInfo['quiz']; ?>')">
            Start Quiz
        </button>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function goToPage(url){ window.location.href = url; }

function toggleGrammar(id){
    const content = document.getElementById(id+'-content');
    const icon = document.getElementById(id+'-icon');
    content.classList.toggle('collapsed');
    icon.classList.toggle('collapsed');
}

function expandAllGrammar(){
    document.querySelectorAll('.grammar-content').forEach(el=>el.classList.remove('collapsed'));
    document.querySelectorAll('.toggle-icon').forEach(el=>el.classList.remove('collapsed'));
}

function collapseAllGrammar(){
    document.querySelectorAll('.grammar-content').forEach(el=>el.classList.add('collapsed'));
    document.querySelectorAll('.toggle-icon').forEach(el=>el.classList.add('collapsed'));
}
</script>

<style>
/* Simple collapse/expand styles */
.grammar-content.collapsed { display: none; }
.toggle-icon { cursor: pointer; transition: transform 0.2s; }
.toggle-icon.collapsed { transform: rotate(0deg); }
.toggle-icon:not(.collapsed) { transform: rotate(90deg); }
.language-content { display: none; }
.language-content.active { display: inline; }
</style>

<?php include 'footer.php'; ?>
</body>
</html>
