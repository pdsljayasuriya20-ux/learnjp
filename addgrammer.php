<?php
include 'header.php';

// ------------------------------
// DATABASE CONNECTION
// ------------------------------
require_once 'config.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ------------------------------
// LANGUAGE HANDLING
// ------------------------------
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$valid_langs = ['en', 'ja', 'si'];
if (!in_array($lang, $valid_langs)) $lang = 'en';

// ------------------------------
// PAGE PARAMETERS
// ------------------------------
$page_set = isset($_GET['set']) ? (int)$_GET['set'] : 1; // 1 for 1-10, 2 for 11-20, 3 for 21-30
$start_num = ($page_set - 1) * 10 + 1;
$end_num = $page_set * 10;

// ------------------------------
// FETCH GRAMMAR RULES FROM DATABASE
// ------------------------------
$grammar_query = "SELECT * FROM grammar_rules WHERE rule_number BETWEEN $start_num AND $end_num ORDER BY rule_number ASC";
$grammar_result = $conn->query($grammar_query);

$grammar_sections = [];
if ($grammar_result->num_rows > 0) {
    while($row = $grammar_result->fetch_assoc()) {
        $grammar_sections[] = $row;
    }
}

// ------------------------------
// PAGE CONTENT
// ------------------------------
$pageContent = [
    'title' => [
        'en' => "N5 Grammar Rules $start_num-$end_num - Learn Japanese",
        'ja' => "N5文法ルール$start_num-$end_num - 日本語を学ぶ",
        'si' => "N5 ව්‍යාකරණ නීති $start_num-$end_num - ජපන් ඉගෙන ගන්න"
    ],
    'hero_title' => [
        'en' => "N5 Grammar Rules $start_num-$end_num",
        'ja' => "N5文法ルール$start_num-$end_num",
        'si' => "N5 ව්‍යාකරණ නීති $start_num-$end_num"
    ],
    'hero_subtitle' => [
        'en' => 'Master Basic Japanese Grammar',
        'ja' => '基本的な日本語文法をマスターする',
        'si' => 'මූලික ජපන් ව්‍යාකරණය ප්‍රගුණ කරන්න'
    ]
];

$t = [
    'page_title' => $pageContent['title'][$lang],
    'hero_title' => $pageContent['hero_title'][$lang],
    'hero_subtitle' => $pageContent['hero_subtitle'][$lang]
];

// Navigation buttons
$buttons = [];
if ($page_set > 1) {
    $prev_set = $page_set - 1;
    $prev_start = ($prev_set - 1) * 10 + 1;
    $prev_end = $prev_set * 10;
    $buttons[] = ['label'=>"Back $prev_start-$prev_end", 'url'=>"?set=$prev_set&lang=$lang", 'class'=>'btn-warning'];
}
$buttons[] = ['label'=>'Expand All', 'onclick'=>'expandAllGrammar()', 'class'=>'btn-primary'];
$buttons[] = ['label'=>'Collapse All', 'onclick'=>'collapseAllGrammar()', 'class'=>'btn-danger'];
$buttons[] = ['label'=>'All Grammar', 'url'=>'n5grammer.php?lang='.$lang, 'class'=>'btn-dark'];
if ($page_set < 3) {
    $next_set = $page_set + 1;
    $next_start = ($next_set - 1) * 10 + 1;
    $next_end = $next_set * 10;
    $buttons[] = ['label'=>"Next $next_start-$next_end", 'url'=>"?set=$next_set&lang=$lang", 'class'=>'btn-info'];
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['page_title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;700;900&family=Poppins:wght@300;400;600;700;800&family=Noto+Sans+Sinhala:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #0a0a0a;
            color: white;
            line-height: 1.6;
        }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .hero-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 40px;
            border: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 30px;
        }
        .hero-title {
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 900;
            background: linear-gradient(45deg, #ff006e, #8338ec, #3a86ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .character-section {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            border: 1px solid rgba(255,255,255,0.1);
            padding: 25px;
            margin-bottom: 20px;
        }
        .toggle-header {
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        .toggle-header:hover { background: rgba(255,255,255,0.1); }
        .grammar-number {
            font-size: 2.5rem;
            font-weight: 900;
            background: linear-gradient(45deg, #ff006e, #8338ec);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            min-width: 60px;
        }
        .section-title {
            font-size: clamp(1.5rem, 4vw, 2rem);
            font-weight: 700;
            color: #ff006e;
            margin-bottom: 5px;
        }
        .romaji-text {
            font-size: 1rem;
            color: #8338ec;
            font-style: italic;
        }
        .section-subtitle {
            font-size: 1.1rem;
            opacity: 0.8;
            margin: 5px 0;
        }
        .toggle-icon {
            font-size: 1.5rem;
            transition: transform 0.3s ease;
            color: #ff006e;
        }
        .toggle-icon.collapsed { transform: rotate(0deg); }
        .toggle-icon:not(.collapsed) { transform: rotate(90deg); }
        .grammar-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease;
            padding: 0 15px;
        }
        .grammar-content:not(.collapsed) {
            max-height: 5000px;
            padding: 25px 15px;
        }
        .grammar-description {
            background: rgba(255,255,255,0.03);
            border-radius: 15px;
            padding: 20px;
            margin: 15px 0;
        }
        .sound-group {
            background: rgba(255,255,255,0.05);
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #ff006e;
            margin: 15px 0;
        }
        .japanese-text { font-family: 'Noto Sans JP', sans-serif; }
        .sinhala-text { font-family: 'Noto Sans Sinhala', sans-serif; }
        .language-content { display: none; }
        .language-content.active { display: inline; }
        .btn { margin: 5px; }
    </style>
</head>
<body>
    <div class="container">

        <!-- Hero Card -->
        <div class="hero-card text-center">
            <h1 class="hero-title"><?php echo $t['hero_title']; ?></h1>
            <p class="lead"><?php echo $t['hero_subtitle']; ?></p>
        </div>

        <!-- Navigation Buttons -->
        <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
            <?php foreach($buttons as $btn): ?>
                <button class="btn btn-lg <?php echo $btn['class']; ?>" 
                    <?php if(isset($btn['onclick'])) echo 'onclick="'.$btn['onclick'].'"'; else echo 'onclick="window.location.href=\''.$btn['url'].'\'"'; ?>>
                    <?php echo $btn['label']; ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Grammar Sections -->
        <?php foreach($grammar_sections as $section): ?>
            <div class="character-section">
                <div class="toggle-header" onclick="toggleGrammar('g<?php echo $section['rule_number']; ?>')">
                    <div class="d-flex align-items-center gap-3 flex-grow-1">
                        <span class="grammar-number"><?php echo $section['rule_number']; ?></span>
                        <div>
                            <h2 class="section-title japanese-text"><?php echo $section['title_ja']; ?></h2>
                            <div class="romaji-text"><?php echo $section['romaji']; ?></div>
                            <p class="section-subtitle">
                                <span class="language-content <?php if($lang=='en') echo 'active'; ?>">
                                    <?php echo $section['description_en']; ?>
                                </span>
                                <span class="language-content japanese-text <?php if($lang=='ja') echo 'active'; ?>">
                                    <?php echo $section['description_ja']; ?>
                                </span>
                                <span class="language-content sinhala-text <?php if($lang=='si') echo 'active'; ?>">
                                    <?php echo $section['description_si']; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    <span class="toggle-icon collapsed" id="g<?php echo $section['rule_number']; ?>-icon">▶</span>
                </div>
                <div class="grammar-content collapsed" id="g<?php echo $section['rule_number']; ?>-content">
                    <div class="grammar-description">
                        <h3 style="color:#8338ec;margin-bottom:15px;">
                            <span class="language-content <?php if($lang=='en') echo 'active'; ?>">Description:</span>
                            <span class="language-content japanese-text <?php if($lang=='ja') echo 'active'; ?>">解説：</span>
                            <span class="language-content sinhala-text <?php if($lang=='si') echo 'active'; ?>">විස්තරය:</span>
                        </h3>
                        
                        <p>
                            <span class="language-content <?php if($lang=='en') echo 'active'; ?>">
                                <?php echo nl2br($section['explanation_en']); ?>
                            </span>
                            <span class="language-content japanese-text <?php if($lang=='ja') echo 'active'; ?>">
                                <?php echo nl2br($section['explanation_ja']); ?>
                            </span>
                            <span class="language-content sinhala-text <?php if($lang=='si') echo 'active'; ?>">
                                <?php echo nl2br($section['explanation_si']); ?>
                            </span>
                        </p>
                    </div>

                    <!-- Examples -->
                    <?php
                    $examples_query = "SELECT * FROM grammar_examples WHERE rule_id = {$section['id']} ORDER BY example_order ASC";
                    $examples_result = $conn->query($examples_query);
                    
                    if ($examples_result->num_rows > 0):
                    ?>
                    <div class="pronunciation-guide">
                        <h3 style="color:#8338ec;margin-bottom:15px;">
                            <span class="language-content <?php if($lang=='en') echo 'active'; ?>">Examples:</span>
                            <span class="language-content japanese-text <?php if($lang=='ja') echo 'active'; ?>">例文：</span>
                            <span class="language-content sinhala-text <?php if($lang=='si') echo 'active'; ?>">උදාහරණ:</span>
                        </h3>
                        <?php while($example = $examples_result->fetch_assoc()): ?>
                        <div class="sound-group">
                            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">
                                <?php echo $example['sentence_ja']; ?>
                            </div>
                            <p style="color:#8338ec;font-style:italic;"><?php echo $example['romaji']; ?></p>
                            <p>
                                <span class="language-content <?php if($lang=='en') echo 'active'; ?>">
                                    <?php echo $example['translation_en']; ?>
                                </span>
                                <span class="language-content japanese-text <?php if($lang=='ja') echo 'active'; ?>">
                                    <?php echo $example['translation_ja']; ?>
                                </span>
                                <span class="language-content sinhala-text <?php if($lang=='si') echo 'active'; ?>">
                                    <?php echo $example['translation_si']; ?>
                                </span>
                            </p>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Quiz Button -->
        <div class="d-flex justify-content-center mb-4">
            <button class="btn btn-success btn-lg w-100" style="max-width: 400px;" 
                onclick="window.location.href='quiz.php?lang=<?php echo $lang; ?>&set=<?php echo $page_set; ?>'">
                <span class="language-content <?php if($lang=='en') echo 'active'; ?>">Start Quiz</span>
                <span class="language-content <?php if($lang=='ja') echo 'active'; ?>">クイズを開始</span>
                <span class="language-content <?php if($lang=='si') echo 'active'; ?>">ප්‍රශ්නාවලිය ආරම්භ කරන්න</span>
            </button>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
</body>
</html>
<?php
$conn->close();
include 'footer.php';
?>