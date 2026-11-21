<?php
include 'header.php'; 

// Language handling
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$valid_langs = ['en', 'ja', 'si'];
if (!in_array($lang, $valid_langs)) {
    $lang = 'en';
}

// Page content arrays
$content = [
    'en' => [
        'page_title' => 'N5 Reading Practice - Learn Japanese',
        'hero_title' => 'N5 Reading Practice',
        'hero_subtitle' => 'Test Your Reading Comprehension',
        'back_btn' => 'Back to N5',
        'passage' => 'Passage',
        'question' => 'Question',
        'check_answer' => 'Check Answer',
        'next_question' => 'Next Question',
        'show_answer' => 'Show Answer',
        'hide_answer' => 'Hide Answer',
        'correct' => 'Correct!',
        'incorrect' => 'Incorrect. Try again!',
        'score' => 'Score',
        'total_questions' => 'Total Questions',
        'restart' => 'Restart Practice',
        'complete_title' => 'Practice Complete!',
        'complete_msg' => 'Great job! You finished all reading passages.',
        'your_score' => 'Your Score',
        'try_again' => 'Try Again',
        'instructions' => 'Read each passage carefully and answer the questions below. Hover over kanji to see readings.',
        'show_furigana' => 'Show Furigana',
        'hide_furigana' => 'Hide Furigana'
    ],
    'ja' => [
        'page_title' => 'N5読解練習 - 日本語を学ぶ',
        'hero_title' => 'N5読解練習',
        'hero_subtitle' => '読解力をテストしよう',
        'back_btn' => 'N5に戻る',
        'passage' => '文章',
        'question' => '質問',
        'check_answer' => '答えを確認',
        'next_question' => '次の質問',
        'show_answer' => '答えを表示',
        'hide_answer' => '答えを隠す',
        'correct' => '正解！',
        'incorrect' => '不正解。もう一度やってみよう！',
        'score' => 'スコア',
        'total_questions' => '全問題数',
        'restart' => '最初から',
        'complete_title' => '練習完了！',
        'complete_msg' => 'お疲れ様でした！全ての読解問題を終了しました。',
        'your_score' => 'あなたのスコア',
        'try_again' => 'もう一度',
        'instructions' => '各文章を注意深く読んで、下の質問に答えてください。漢字の上にカーソルを置くと読みが表示されます。',
        'show_furigana' => 'ふりがなを表示',
        'hide_furigana' => 'ふりがなを隠す'
    ],
    'si' => [
        'page_title' => 'N5 කියවීමේ පුහුණුව - ජපන් ඉගෙන ගන්න',
        'hero_title' => 'N5 කියවීමේ පුහුණුව',
        'hero_subtitle' => 'ඔබේ කියවීමේ හැකියාව පරීක්ෂා කරන්න',
        'back_btn' => 'N5 වෙත ආපසු',
        'passage' => 'ඡේදය',
        'question' => 'ප්‍රශ්නය',
        'check_answer' => 'පිළිතුර පරීක්ෂා කරන්න',
        'next_question' => 'ඊළඟ ප්‍රශ්නය',
        'show_answer' => 'පිළිතුර පෙන්වන්න',
        'hide_answer' => 'පිළිතුර සඟවන්න',
        'correct' => 'නිවැරදියි!',
        'incorrect' => 'වැරදියි. නැවත උත්සාහ කරන්න!',
        'score' => 'ලකුණු',
        'total_questions' => 'මුළු ප්‍රශ්න',
        'restart' => 'නැවත ආරම්භ කරන්න',
        'complete_title' => 'පුහුණුව සම්පූර්ණයි!',
        'complete_msg' => 'හොඳයි! ඔබ සියලු කියවීමේ ඡේද අවසන් කළා.',
        'your_score' => 'ඔබේ ලකුණු',
        'try_again' => 'නැවත උත්සාහ කරන්න',
        'instructions' => 'එක් එක් ඡේදය හොඳින් කියවා පහත ප්‍රශ්නවලට පිළිතුරු දෙන්න. කංජි මත කර්සරය තබන්න.',
        'show_furigana' => 'ෆුරිගානා පෙන්වන්න',
        'hide_furigana' => 'ෆුරිගානා සඟවන්න'
    ]
];

$t = $content[$lang];
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['page_title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;700;900&family=Poppins:wght@300;400;600;700;800&family=Noto+Sans+Sinhala:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .japanese-text {
            font-family: 'Noto Sans JP', sans-serif;
            line-height: 2;
        }
        
        .sinhala-text {
            font-family: 'Noto Sans Sinhala', sans-serif;
        }
        
        .container {
            max-width: 1300px;
        }
        
        .hero-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 25px;
            padding: 3rem 2rem;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            margin-bottom: 2rem;
            border: none;
        }
        
        .hero-title {
            font-size: clamp(2rem, 6vw, 3.5rem);
            font-weight: 900;
            background: linear-gradient(45deg, #ff006e, #8338ec, #3a86ff, #06ffa5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 1rem;
        }
        
        .instructions {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            padding: 1rem;
            border-radius: 15px;
            margin-top: 1rem;
            border-left: 4px solid #667eea;
        }
        
        .furigana-toggle {
            margin-top: 1rem;
        }
        
        .toggle-btn {
            background: linear-gradient(45deg, #3a86ff, #06ffa5);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .toggle-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(58, 134, 255, 0.4);
        }
        
        .progress-bar-container {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        
        .custom-progress {
            height: 30px;
            border-radius: 15px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
            overflow: hidden;
            position: relative;
        }
        
        .custom-progress-bar {
            height: 100%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 15px;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
        }
        
        .reading-card {
            background: white;
            border-radius: 25px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .passage-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid #667eea;
        }
        
        .passage-icon {
            font-size: 2.5rem;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .passage-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: #667eea;
            margin: 0;
        }
        
        .passage-text {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
            padding: 2rem;
            border-radius: 20px;
            font-size: 1.3rem;
            line-height: 2.2;
            margin-bottom: 2rem;
            border: 2px solid rgba(102, 126, 234, 0.2);
        }
        
        /* Furigana styling */
        ruby {
            position: relative;
            display: inline-block;
        }
        
        rt {
            font-size: 0.6em;
            line-height: 1;
            text-align: center;
            color: #667eea;
            font-weight: 600;
            display: none;
        }
        
        ruby.show-reading rt {
            display: block;
        }
        
        .kanji-word {
            position: relative;
            display: inline-block;
            cursor: help;
            border-bottom: 2px dotted transparent;
            transition: all 0.3s ease;
            padding: 0 2px;
        }
        
        .kanji-word:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-bottom-color: #667eea;
            border-radius: 4px;
        }
        
        .furigana-popup {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%) translateY(-5px);
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease;
            z-index: 100;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .furigana-popup::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-top-color: #764ba2;
        }
        
        .kanji-word:hover .furigana-popup {
            opacity: 1;
            transform: translateX(-50%) translateY(-10px);
        }
        
        .show-all-furigana .furigana-popup {
            opacity: 1;
            transform: translateX(-50%) translateY(-10px);
        }
        
        .question-section {
            background: linear-gradient(135deg, rgba(255, 0, 110, 0.05), rgba(131, 56, 236, 0.05));
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            border: 2px solid rgba(255, 0, 110, 0.2);
        }
        
        .question-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .question-number {
            background: linear-gradient(45deg, #ff006e, #8338ec);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1.2rem;
        }
        
        .question-text {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            flex: 1;
        }
        
        .answer-options {
            display: grid;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .answer-option {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            border: 3px solid rgba(102, 126, 234, 0.2);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .answer-option:hover {
            transform: translateX(10px);
            border-color: #667eea;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }
        
        .answer-option.selected {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-color: #667eea;
        }
        
        .answer-option.correct {
            background: linear-gradient(135deg, rgba(6, 255, 165, 0.2), rgba(0, 204, 136, 0.2));
            border-color: #06ffa5;
            animation: correctPulse 0.5s ease;
        }
        
        .answer-option.incorrect {
            background: linear-gradient(135deg, rgba(245, 54, 92, 0.2), rgba(225, 29, 72, 0.2));
            border-color: #f5365c;
            animation: shake 0.5s ease;
        }
        
        @keyframes correctPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .option-label {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .option-text {
            flex: 1;
            font-size: 1.1rem;
        }
        
        .feedback {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 15px;
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 1rem;
        }
        
        .feedback.show {
            display: flex;
        }
        
        .feedback.correct {
            background: linear-gradient(135deg, rgba(6, 255, 165, 0.2), rgba(0, 204, 136, 0.2));
            color: #00cc88;
            border: 2px solid #06ffa5;
        }
        
        .feedback.incorrect {
            background: linear-gradient(135deg, rgba(245, 54, 92, 0.2), rgba(225, 29, 72, 0.2));
            color: #e11d48;
            border: 2px solid #f5365c;
        }
        
        .feedback-icon {
            font-size: 2rem;
        }
        
        .btn-gradient {
            background: linear-gradient(45deg, #ff006e, #8338ec);
            border: none;
            color: white;
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .btn-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(255,0,110,0.4);
            color: white;
        }
        
        .btn-secondary-gradient {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        
        .btn-secondary-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .score-display {
            display: flex;
            justify-content: space-around;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .score-item {
            background: white;
            padding: 1.5rem;
            border-radius: 20px;
            text-align: center;
            flex: 1;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .score-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .score-value {
            font-size: 2.5rem;
            font-weight: 900;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .completion-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }
        
        .completion-modal.show {
            display: flex;
        }
        
        .modal-content-custom {
            background: white;
            border-radius: 30px;
            padding: 3rem;
            text-align: center;
            max-width: 500px;
            width: 90%;
            animation: modalPop 0.5s ease;
        }
        
        @keyframes modalPop {
            from {
                transform: scale(0.5);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .modal-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
        }
        
        .modal-title {
            font-size: 2.5rem;
            font-weight: 900;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }
        
        .final-score {
            font-size: 4rem;
            font-weight: 900;
            background: linear-gradient(45deg, #ff006e, #8338ec);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 2rem 0;
        }
        
        .button-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .reading-card {
                padding: 1.5rem;
            }
            
            .passage-text {
                font-size: 1.1rem;
                padding: 1.5rem;
            }
            
            .answer-option {
                padding: 1rem;
            }
            
            .option-text {
                font-size: 1rem;
            }
            
            .score-display {
                flex-direction: column;
            }
            
            .furigana-popup {
                font-size: 0.8rem;
                padding: 4px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Button -->
        <div class="mb-3">
            <a href="n5.php" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> <?php echo $t['back_btn']; ?>
            </a>
        </div>
        
        <!-- Hero Card -->
        <div class="hero-card">
            <h1 class="hero-title"><?php echo $t['hero_title']; ?></h1>
            <p class="hero-subtitle"><?php echo $t['hero_subtitle']; ?></p>
            <div class="instructions">
                <i class="bi bi-info-circle"></i> <?php echo $t['instructions']; ?>
            </div>
            <div class="furigana-toggle">
                <button class="toggle-btn" id="furiganaToggle" onclick="toggleFurigana()">
                    <i class="bi bi-eye"></i> <span id="toggleText"><?php echo $t['show_furigana']; ?></span>
                </button>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="progress-bar-container">
            <div class="score-display">
                <div class="score-item">
                    <div class="score-label"><?php echo $t['score']; ?></div>
                    <div class="score-value" id="currentScore">0</div>
                </div>
                <div class="score-item">
                    <div class="score-label"><?php echo $t['total_questions']; ?></div>
                    <div class="score-value">5</div>
                </div>
            </div>
            <div class="custom-progress">
                <div class="custom-progress-bar" id="progressBar" style="width: 0%">
                    <span id="progressText">0%</span>
                </div>
            </div>
        </div>
        
        <!-- Reading Passages Container -->
        <div id="readingContainer"></div>
        
        <!-- Navigation Buttons -->
        <div class="text-center button-group" id="navigationButtons" style="display: none;">
            <button class="btn btn-secondary-gradient" onclick="showResults()">
                <i class="bi bi-check-circle"></i> <?php echo $t['check_answer']; ?>
            </button>
        </div>
    </div>
    
    <!-- Completion Modal -->
    <div class="completion-modal" id="completionModal">
        <div class="modal-content-custom">
            <div class="modal-icon">🎉</div>
            <h2 class="modal-title"><?php echo $t['complete_title']; ?></h2>
            <p><?php echo $t['complete_msg']; ?></p>
            <div class="final-score" id="finalScore">0/5</div>
            <p style="color: #666; font-size: 1.1rem;"><?php echo $t['your_score']; ?></p>
            <div class="button-group">
                <button class="btn btn-gradient" onclick="restartPractice()">
                    <i class="bi bi-arrow-clockwise"></i> <?php echo $t['try_again']; ?>
                </button>
                <a href="n5.php" class="btn btn-secondary-gradient">
                    <i class="bi bi-house"></i> <?php echo $t['back_btn']; ?>
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const translations = <?php echo json_encode($t); ?>;
        
        // Kanji to Hiragana mapping for furigana
        const kanjiReadings = {
           '皆': 'みな - everyone (noun)',
    '来週': 'らいしゅう - next week (noun)',
    '月曜日': 'げつようび - Monday (noun)',
    '火曜日': 'かようび - Tuesday (noun)',
    '午前': 'ごぜん - a.m.; morning (noun)',
    '午後': 'ごご - p.m.; afternoon (noun)',
    '時': 'じ - time; o\'clock (noun)',
    '使': 'つか - to use (verb stem)',
    '階段': 'かいだん - stairs (noun)',
    '友': 'とも - friend (noun)',
    '達': 'だち - plural suffix (suffix)',
    '電話': 'でんわ - telephone (noun)',
    '時間': 'じかん - time; hour (noun)',
    '先月': 'せんげつ - last month (noun)',
    '学校': 'がっこう - school (noun)',
    '旅行': 'りょこう - travel; trip (noun)',
    '行': 'い - to go (verb stem)',
    '前': 'まえ - before; front (noun/adverb)',
    '来': 'き - to come (verb stem)',
    '日本人': 'にほんじん - Japanese person (noun)',
    '言': 'い - to say; to speak (verb stem)',
    '南': 'みなみ - south (noun)',
    '暑': 'あつ - hot (i-adjective stem)',
    '夏': 'なつ - summer (noun)',
    '服': 'ふく - clothes (noun)',
    '持': 'も - to hold; to possess (verb stem)',
    '出': 'で - to go out; to leave (verb stem)',
    '入': 'い - to enter (verb stem)',
    '洋服': 'ようふく - (Western-style) clothes (noun)',
    '泳': 'およ - to swim (verb stem)',
    '思': 'おも - to think (verb stem)',
    '外': 'そと - outside (noun)',
    '話': 'はな - to speak; to talk (verb stem)',
    '答': 'こた - answer (verb stem/noun)',
    '呼': 'よ - to call (verb stem)',
    '聞': 'き - to listen; to hear (verb stem)',
    '一日中': 'いちにちじゅう - all day long (noun/adverb)',
    '町': 'まち - town; city (noun)',
    '海': 'うみ - sea (noun)'
        };
        
        // Reading passages data with furigana
        const readingData = [
            {
                id: 1,
                passage: "アパートの皆さんへ\n来週の月曜日と火曜日の午前１０時から午後５時までエレベーターを使わないでください。階段を使ってください。",
                question: "アパートの人は、来週の月曜日と火曜日には、外に出る時、どうしますか。",
                options: [
                    "来週の月曜日の午前１１時には、階段を使います。",
                    "来週の月曜日の午後３時には、エレベーターを使います。",
                    "来週の火曜日の午前１１時には、エレベーターを使います。",
                    "来週の火曜日の午後３時には、階段を使いません。"
                ],
                correctAnswer: 0
            },
            {
                id: 2,
                passage: "きのうのよる１０時に友だちのうちに電話をしましたが、友だちはいませんでした。１時間あとでもういちどかけました。でも友だちはまだかえっていませんでした。わたしは１１時ごろねました。",
                question: "この文章の内容として正しいのはどれですか。",
                options: [
                    "わたしはきのうのよる友だちと話したあとでねました。",
                    "わたしはきのうのよる友だちに１かいしか電話しませんでした。",
                    "わたしはきのうのよる友だちと２かい電話で話しました。",
                    "わたしはきのうのよる友だちと電話で話しませんでした。"
                ],
                correctAnswer: 3
            },
            {
                id: 3,
                passage: "先月わたしは学校の友達といっしょに、おきなわへ旅行に行きました。わたしたちは、旅行の前に、おきなわから来た日本人の友達にいろいろ（ア）。友達は、「おきなわはとうきょうよりずっと南だから、とても暑いよ。（イ）、夏の服をたくさん持っていったほうがいい」と言いました。そのころとうきょうはまだ４月だったので、きおんが１４どぐらいでした。（ウ）、わたしはおしいれのおくから、夏の洋服を出して、かばんにいっぱい入れて出かけることにしました。２はく３日の旅行ですが、一日中、おきなわの町をけんぶつしたり、海で泳いだりするので、ようふくはたくさんあったほうがいいと思ったのです。",
                question: "（ア）には何を入れますか。",
                options: [
                    "話しました",
                    "答えました",
                    "呼びました",
                    "聞きました"
                ],
                correctAnswer: 3
            },
            {
                id: 4,
                passage: "先月わたしは学校の友達といっしょに、おきなわへ旅行に行きました。わたしたちは、旅行の前に、おきなわから来た日本人の友達にいろいろ（ア）。友達は、「おきなわはとうきょうよりずっと南だから、とても暑いよ。（イ）、夏の服をたくさん持っていったほうがいい」と言いました。そのころとうきょうはまだ４月だったので、きおんが１４どぐらいでした。（ウ）、わたしはおしいれのおくから、夏の洋服を出して、かばんにいっぱい入れて出かけることにしました。２はく３日の旅行ですが、一日中、おきなわの町をけんぶつしたり、海で泳いだりするので、ようふくはたくさんあったほうがいいと思ったのです。",
                question: "（イ）には何を入れますか。",
                options: [
                    "だから",
                    "それから",
                    "すると",
                    "だけど"
                ],
                correctAnswer: 0
            },
            {
                id: 5,
                passage: "先月わたしは学校の友達といっしょに、おきなわへ旅行に行きました。わたしたちは、旅行の前に、おきなわから来た日本人の友達にいろいろ（ア）。友達は、「おきなわはとうきょうよりずっと南だから、とても暑いよ。（イ）、夏の服をたくさん持っていったほうがいい」と言いました。そのころとうきょうはまだ４月だったので、きおんが１４どぐらいでした。（ウ）、わたしはおしいれのおくから、夏の洋服を出して、かばんにいっぱい入れて出かけることにしました。２はく３日の旅行ですが、一日中、おきなわの町をけんぶつしたり、海で泳いだりするので、ようふくはたくさんあったほうがいいと思ったのです。",
                question: "（ウ）には何を入れますか。",
                options: [
                    "そうすると",
                    "ところが",
                    "それで",
                    "それに"
                ],
                correctAnswer: 2
            }
        ];
        
        let currentQuestion = 0;
        let score = 0;
        let userAnswers = [];
        let showAllFurigana = false;
        
        function addFuriganaToText(text) {
            let result = text;
            
            // Sort by length (longest first) to match longer words first
            const sortedKanji = Object.keys(kanjiReadings).sort((a, b) => b.length - a.length);
            
            sortedKanji.forEach(kanji => {
                const reading = kanjiReadings[kanji];
                const regex = new RegExp(kanji, 'g');
                result = result.replace(regex, `<span class="kanji-word">${kanji}<span class="furigana-popup">${reading}</span></span>`);
            });
            
            return result;
        }
        
        function toggleFurigana() {
            showAllFurigana = !showAllFurigana;
            const container = document.getElementById('readingContainer');
            const toggleText = document.getElementById('toggleText');
            
            if (showAllFurigana) {
                container.classList.add('show-all-furigana');
                toggleText.textContent = translations.hide_furigana;
            } else {
                container.classList.remove('show-all-furigana');
                toggleText.textContent = translations.show_furigana;
            }
        }
        
        function initializeReading() {
            renderAllQuestions();
            document.getElementById('navigationButtons').style.display = 'flex';
        }
        
        function renderAllQuestions() {
            const container = document.getElementById('readingContainer');
            container.innerHTML = '';
            
            readingData.forEach((item, index) => {
                const card = document.createElement('div');
                card.className = 'reading-card';
                
                const passageWithFurigana = addFuriganaToText(item.passage.replace(/\n/g, '<br>'));
                const questionWithFurigana = addFuriganaToText(item.question);
                
                card.innerHTML = `
                    <div class="passage-header">
                        <div class="passage-icon">📖</div>
                        <h2 class="passage-title">${translations.passage} ${index + 1}</h2>
                    </div>
                    
                    <div class="passage-text japanese-text">
                        ${passageWithFurigana}
                    </div>
                    
                    <div class="question-section">
                        <div class="question-header">
                            <div class="question-number">${index + 1}</div>
                            <div class="question-text japanese-text">${questionWithFurigana}</div>
                        </div>
                        
                        <div class="answer-options" id="options-${index}">
                            ${item.options.map((option, optIndex) => {
                                const optionWithFurigana = addFuriganaToText(option);
                                return `
                                <div class="answer-option" data-question="${index}" data-option="${optIndex}" onclick="selectAnswer(${index}, ${optIndex})">
                                    <div class="option-label">${String.fromCharCode(65 + optIndex)}</div>
                                    <div class="option-text japanese-text">${optionWithFurigana}</div>
                                </div>
                            `}).join('')}
                        </div>
                        
                        <div class="feedback" id="feedback-${index}">
                            <span class="feedback-icon"></span>
                            <span class="feedback-text"></span>
                        </div>
                    </div>
                `;
                container.appendChild(card);
            });
        }
        
        function selectAnswer(questionIndex, optionIndex) {
            // Remove previous selection for this question
            const options = document.querySelectorAll(`[data-question="${questionIndex}"]`);
            options.forEach(opt => opt.classList.remove('selected'));
            
            // Add selection to clicked option
            const selectedOption = document.querySelector(`[data-question="${questionIndex}"][data-option="${optionIndex}"]`);
            selectedOption.classList.add('selected');
            
            // Store user's answer
            userAnswers[questionIndex] = optionIndex;
        }
        
        function showResults() {
            let correctCount = 0;
            
            readingData.forEach((item, index) => {
                const userAnswer = userAnswers[index];
                const correctAnswer = item.correctAnswer;
                const feedback = document.getElementById(`feedback-${index}`);
                const options = document.querySelectorAll(`[data-question="${index}"]`);
                
                if (userAnswer !== undefined) {
                    options.forEach((opt, optIndex) => {
                        opt.style.pointerEvents = 'none';
                        
                        if (optIndex === correctAnswer) {
                            opt.classList.add('correct');
                        }
                        
                        if (userAnswer === optIndex && userAnswer !== correctAnswer) {
                            opt.classList.add('incorrect');
                        }
                    });
                    
                    if (userAnswer === correctAnswer) {
                        correctCount++;
                        feedback.classList.add('show', 'correct');
                        feedback.querySelector('.feedback-icon').innerHTML = '✓';
                        feedback.querySelector('.feedback-text').textContent = translations.correct;
                    } else {
                        feedback.classList.add('show', 'incorrect');
                        feedback.querySelector('.feedback-icon').innerHTML = '✗';
                        feedback.querySelector('.feedback-text').textContent = translations.incorrect;
                    }
                }
            });
            
            score = correctCount;
            updateScore();
            updateProgress(100);
            
            // Hide check button, show completion after delay
            document.getElementById('navigationButtons').style.display = 'none';
            
            setTimeout(() => {
                showCompletionModal();
            }, 1500);
        }
        
        function updateScore() {
            document.getElementById('currentScore').textContent = score;
        }
        
        function updateProgress(percentage) {
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            progressBar.style.width = percentage + '%';
            progressText.textContent = Math.round(percentage) + '%';
        }
        
        function showCompletionModal() {
            document.getElementById('finalScore').textContent = `${score}/5`;
            document.getElementById('completionModal').classList.add('show');
            
            // Confetti effect
            if (score >= 4) {
                createConfetti();
            }
        }
        
        function createConfetti() {
            const colors = ['#667eea', '#764ba2', '#ff006e', '#8338ec', '#06ffa5'];
            const confettiCount = 50;
            
            for (let i = 0; i < confettiCount; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.style.position = 'fixed';
                    confetti.style.width = '10px';
                    confetti.style.height = '10px';
                    confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.left = Math.random() * 100 + '%';
                    confetti.style.top = '-10px';
                    confetti.style.opacity = '1';
                    confetti.style.transform = 'rotate(' + Math.random() * 360 + 'deg)';
                    confetti.style.transition = 'all 3s ease-out';
                    confetti.style.zIndex = '9999';
                    confetti.style.borderRadius = '50%';
                    
                    document.body.appendChild(confetti);
                    
                    setTimeout(() => {
                        confetti.style.top = '100vh';
                        confetti.style.opacity = '0';
                        confetti.style.transform = 'rotate(' + (Math.random() * 720) + 'deg)';
                    }, 100);
                    
                    setTimeout(() => {
                        confetti.remove();
                    }, 3100);
                }, i * 30);
            }
        }
        
        function restartPractice() {
            score = 0;
            currentQuestion = 0;
            userAnswers = [];
            showAllFurigana = false;
            
            document.getElementById('completionModal').classList.remove('show');
            document.getElementById('readingContainer').classList.remove('show-all-furigana');
            document.getElementById('toggleText').textContent = translations.show_furigana;
            
            updateScore();
            updateProgress(0);
            
            // Reset all questions
            const container = document.getElementById('readingContainer');
            container.innerHTML = '';
            initializeReading();
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        // Initialize on page load
        window.addEventListener('load', function() {
            document.body.style.opacity = '0';
            setTimeout(() => {
                document.body.style.transition = 'opacity 0.5s ease';
                document.body.style.opacity = '1';
                initializeReading();
            }, 100);
        });
    </script>
  <script src="tracking.js"></script>
</body>
</html>

<?php include 'footer.php'; ?>