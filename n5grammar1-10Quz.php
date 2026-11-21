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
        'page_title' => 'N5 Grammar Quiz 1-10 - Learn Japanese',
        'hero_title' => 'N5 Grammar Quiz',
        'hero_subtitle' => 'Test Your Knowledge: Rules 1-10',
        'back_btn' => 'Back to Grammar',
        'start_quiz' => 'Start Quiz',
        'next_question' => 'Next Question',
        'submit_answer' => 'Submit Answer',
        'view_results' => 'View Results',
        'restart_quiz' => 'Restart Quiz',
        'question' => 'Question',
        'of' => 'of',
        'score' => 'Your Score',
        'correct' => 'Correct!',
        'incorrect' => 'Incorrect',
        'explanation' => 'Explanation',
        'select_answer' => 'Please select an answer',
        'quiz_complete' => 'Quiz Complete!',
        'excellent' => 'Excellent!',
        'good_job' => 'Good Job!',
        'keep_practicing' => 'Keep Practicing!',
        'time_taken' => 'Time Taken',
        'accuracy' => 'Accuracy'
    ],
    'ja' => [
        'page_title' => 'N5文法クイズ1-10 - 日本語を学ぶ',
        'hero_title' => 'N5文法クイズ',
        'hero_subtitle' => '知識をテスト：ルール1-10',
        'back_btn' => '文法に戻る',
        'start_quiz' => 'クイズ開始',
        'next_question' => '次の質問',
        'submit_answer' => '回答を送信',
        'view_results' => '結果を見る',
        'restart_quiz' => 'クイズを再開',
        'question' => '問題',
        'of' => '/',
        'score' => 'あなたのスコア',
        'correct' => '正解！',
        'incorrect' => '不正解',
        'explanation' => '解説',
        'select_answer' => '回答を選択してください',
        'quiz_complete' => 'クイズ完了！',
        'excellent' => '素晴らしい！',
        'good_job' => 'よくできました！',
        'keep_practicing' => '練習を続けましょう！',
        'time_taken' => '所要時間',
        'accuracy' => '正答率'
    ],
    'si' => [
        'page_title' => 'N5 ව්‍යාකරණ ප්‍රශ්නාවලිය 1-10 - ජපන් ඉගෙන ගන්න',
        'hero_title' => 'N5 ව්‍යාකරණ ප්‍රශ්නාවලිය',
        'hero_subtitle' => 'ඔබේ දැනුම පරීක්ෂා කරන්න: නීති 1-10',
        'back_btn' => 'ව්‍යාකරණයට ආපසු',
        'start_quiz' => 'ප්‍රශ්නාවලිය ආරම්භ කරන්න',
        'next_question' => 'ඊළඟ ප්‍රශ්නය',
        'submit_answer' => 'පිළිතුර ඉදිරිපත් කරන්න',
        'view_results' => 'ප්‍රතිඵල බලන්න',
        'restart_quiz' => 'නැවත ආරම්භ කරන්න',
        'question' => 'ප්‍රශ්නය',
        'of' => '/',
        'score' => 'ඔබේ ලකුණු',
        'correct' => 'නිවැරදියි!',
        'incorrect' => 'වැරදියි',
        'explanation' => 'පැහැදිලි කිරීම',
        'select_answer' => 'කරුණාකර පිළිතුරක් තෝරන්න',
        'quiz_complete' => 'ප්‍රශ්නාවලිය සම්පූර්ණයි!',
        'excellent' => 'විශිෂ්ටයි!',
        'good_job' => 'හොඳ වැඩක්!',
        'keep_practicing' => 'දිගටම පුහුණු වන්න!',
        'time_taken' => 'ගත වූ කාලය',
        'accuracy' => 'නිරවද්‍යතාවය'
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
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #06ffa5, #00cc88);
            --danger-gradient: linear-gradient(135deg, #f5365c, #e11d48);
        }
        
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
            position: relative;
            overflow: hidden;
        }
        
        .hero-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(from 0deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            animation: rotate 20s linear infinite;
            z-index: 0;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero-title {
            font-size: clamp(2.5rem, 6vw, 4rem);
            font-weight: 900;
            background: linear-gradient(45deg, #ff006e, #8338ec, #3a86ff, #06ffa5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }
        
        .quiz-container {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .progress-bar-container {
            background: rgba(102, 126, 234, 0.1);
            height: 12px;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: var(--primary-gradient);
            transition: width 0.5s ease;
            border-radius: 10px;
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(102, 126, 234, 0.1);
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .question-number {
            font-size: 1.2rem;
            font-weight: 700;
            color: #667eea;
        }
        
        .timer {
            color: #667eea;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .question-text {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .sentence-display {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
            padding: 2rem;
            border-radius: 15px;
            margin: 1.5rem 0;
            font-size: 1.8rem;
            font-family: 'Noto Sans JP', sans-serif;
            text-align: center;
            border-left: 4px solid #667eea;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            line-height: 2.5;
        }
        
        .kanji-with-furigana {
            position: relative;
            display: inline-block;
            cursor: help;
            padding: 0 2px;
        }
        
        .kanji-with-furigana .furigana {
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%) scale(0);
            font-size: 0.9rem;
            color: #667eea;
            background: rgba(255, 255, 255, 0.95);
            padding: 4px 8px;
            border-radius: 8px;
            white-space: nowrap;
            opacity: 0;
            transition: all 0.3s ease;
            pointer-events: none;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            border: 1px solid #667eea;
            font-weight: 600;
            z-index: 100;
        }
        
        .kanji-with-furigana:hover .furigana {
            opacity: 1;
            transform: translateX(-50%) scale(1);
        }
        
        .kanji-with-furigana:hover {
            color: #667eea;
            text-shadow: 0 0 8px rgba(102, 126, 234, 0.3);
        }
        
        .options-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .option-btn {
            background: white;
            border: 3px solid rgba(102, 126, 234, 0.2);
            border-radius: 15px;
            padding: 1.5rem;
            font-size: 1.4rem;
            font-family: 'Noto Sans JP', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            font-weight: 600;
            position: relative;
            overflow: hidden;
        }
        
        .option-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(102, 126, 234, 0.1);
            transform: translate(-50%, -50%);
            transition: all 0.5s ease;
        }
        
        .option-btn:hover::before {
            width: 200%;
            height: 200%;
        }
        
        .option-btn:hover {
            background: rgba(102, 126, 234, 0.05);
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .option-btn.selected {
            background: var(--primary-gradient);
            color: white;
            border-color: transparent;
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .option-btn.correct {
            background: var(--success-gradient);
            color: white;
            border-color: transparent;
            animation: pulse 0.5s ease;
        }
        
        .option-btn.incorrect {
            background: var(--danger-gradient);
            color: white;
            border-color: transparent;
            animation: shake 0.5s ease;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .option-btn:disabled {
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .feedback-box {
            background: linear-gradient(135deg, rgba(6, 255, 165, 0.1), rgba(58, 134, 255, 0.1));
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
            display: none;
            border-left: 4px solid #06ffa5;
        }
        
        .feedback-box.show {
            display: block;
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .feedback-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .feedback-title.correct {
            color: #00cc88;
        }
        
        .feedback-title.correct::before {
            content: '✓';
            font-size: 1.8rem;
        }
        
        .feedback-title.incorrect {
            color: #f5365c;
        }
        
        .feedback-title.incorrect::before {
            content: '✗';
            font-size: 1.8rem;
        }
        
        .feedback-explanation {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #555;
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
            font-size: 1.1rem;
            cursor: pointer;
        }
        
        .btn-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(255,0,110,0.4);
            color: white;
        }
        
        .btn-gradient:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.3);
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 900;
            background: linear-gradient(45deg, #06ffa5, #3a86ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 1rem;
            color: #666;
            font-weight: 600;
        }
        
        .results-container {
            display: none;
        }
        
        .results-container.show {
            display: block;
        }
        
        .result-message {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin: 2rem 0;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .score-circle {
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin: 2rem auto;
            color: white;
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
            animation: scaleIn 0.5s ease;
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .score-circle .score {
            font-size: 3.5rem;
            font-weight: 900;
        }
        
        .score-circle .label {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-top: 0.5rem;
        }
        
        .start-screen {
            text-align: center;
            padding: 3rem 1rem;
        }
        
        .start-screen.hide {
            display: none;
        }
        
        .quiz-info {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
            text-align: left;
        }
        
        .quiz-info-item {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            font-size: 1.1rem;
        }
        
        .quiz-info-item .emoji {
            font-size: 2rem;
            margin-right: 1rem;
            min-width: 40px;
        }
        
        .button-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .options-container {
                grid-template-columns: 1fr;
            }
            
            .quiz-container {
                padding: 1.5rem;
            }
            
            .sentence-display {
                font-size: 1.4rem;
                padding: 1.5rem;
            }
            
            .option-btn {
                padding: 1.2rem;
                font-size: 1.2rem;
            }
            
            .hero-title {
                font-size: 2.5rem;
            }
            
            .score-circle {
                width: 180px;
                height: 180px;
            }
            
            .score-circle .score {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
       
        
        <!-- Hero Card -->
        <div class="hero-card">
            <div class="hero-content">
                <h1 class="hero-title"><?php echo $t['hero_title']; ?></h1>
                <p class="lead"><?php echo $t['hero_subtitle']; ?></p>
            </div>
        </div>
        
        <!-- Start Screen -->
        <div class="quiz-container start-screen" id="startScreen">
            <h2 style="color: #667eea; margin-bottom: 2rem;">Ready to Test Your Knowledge?</h2>
            
            <div class="quiz-info">
                <div class="quiz-info-item">
                    <span class="emoji">📝</span>
                    <span><strong>25 Questions</strong> covering N5 Grammar Rules 1-10</span>
                </div>
                <div class="quiz-info-item">
                    <span class="emoji">🔀</span>
                    <span><strong>Randomized</strong> - Questions and answers shuffled each time</span>
                </div>
                <div class="quiz-info-item">
                    <span class="emoji">⏱️</span>
                    <span><strong>No time limit</strong> - Take your time to think</span>
                </div>
                <div class="quiz-info-item">
                    <span class="emoji">✅</span>
                    <span><strong>Instant feedback</strong> with explanations</span>
                </div>
                <div class="quiz-info-item">
                    <span class="emoji">🌍</span>
                    <span><strong>Multi-language support</strong> (EN/日本語/සිං)</span>
                </div>
            </div>
            
            <button class="btn btn-gradient" onclick="startQuiz()">
                <?php echo $t['start_quiz']; ?>
            </button>
        </div>
        
        <!-- Quiz Screen -->
        <div class="quiz-container" id="quizScreen" style="display: none;">
            <!-- Progress Bar -->
            <div class="progress-bar-container">
                <div class="progress-bar-fill" id="progressBar"></div>
            </div>
            
            <!-- Question Header -->
            <div class="question-header">
                <span class="question-number" id="questionNumber"></span>
                <span class="timer" id="timer">⏱️ 0:00</span>
            </div>
            
            <!-- Question Content -->
            <div id="questionContent">
                <div class="question-text" id="questionText"></div>
                <div class="sentence-display japanese-text" id="sentenceDisplay"></div>
                
                <!-- Options -->
                <div class="options-container" id="optionsContainer"></div>
                
                <!-- Feedback -->
                <div class="feedback-box" id="feedbackBox">
                    <div class="feedback-title" id="feedbackTitle"></div>
                    <p class="feedback-explanation" id="feedbackExplanation"></p>
                </div>
            </div>
            
            <!-- Navigation Buttons -->
            <div class="button-group">
                <button class="btn btn-gradient" id="submitBtn" onclick="submitAnswer()">
                    <?php echo $t['submit_answer']; ?>
                </button>
                <button class="btn btn-gradient" id="nextBtn" onclick="nextQuestion()" style="display: none;">
                    <?php echo $t['next_question']; ?> <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>
        
        <!-- Results Screen -->
        <div class="quiz-container results-container" id="resultsScreen">
            <h2 class="result-message" id="resultMessage"></h2>
            
            <div class="score-circle">
                <div class="score" id="finalScore"></div>
                <div class="label"><?php echo $t['score']; ?></div>
            </div>
            
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="stat-number" id="correctAnswers">0</div>
                        <div class="stat-label">Correct</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="stat-number" id="accuracy">0%</div>
                        <div class="stat-label"><?php echo $t['accuracy']; ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="stat-number" id="timeTaken">0:00</div>
                        <div class="stat-label"><?php echo $t['time_taken']; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="button-group">
                <button class="btn btn-gradient" onclick="restartQuiz()">
                    <i class="bi bi-arrow-repeat"></i> <?php echo $t['restart_quiz']; ?>
                </button>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const currentLang = '<?php echo $lang; ?>';
        const translations = <?php echo json_encode($t); ?>;
        
        // Kanji to Hiragana readings mapping
        const kanjiReadings = {
            '彼女': 'かのじょ',
            '医者': 'いしゃ',
            '本': 'ほん',
            '入': 'はい',
            '吸': 'す',
            '欲': 'ほ',
            '明日': 'あした',
            '雨': 'あめ',
            '降': 'ふ',
            '彼': 'かれ',
            '今': 'いま',
            '忙': 'いそが',
            '駅': 'えき',
            '友達': 'ともだち',
            '会': 'あ',
            '車': 'くるま',
            '会社': 'かいしゃ',
            '行': 'い',
            '難': 'むずか',
            '頑張': 'がんば',
            '茶': 'ちゃ',
            '飲': 'の',
            '電車': 'でんしゃ',
            '遅': 'おく',
            '人': 'ひと',
            '食': 'た',
            '物': 'もの',
            '好': 'す',
            '来': 'こ',
            '嘘': 'うそ',
            '学校': 'がっこう',
            '家': 'いえ',
            '熱': 'ねつ',
            '休': 'やす',
            '日本語': 'にほんご',
            '勉強': 'べんきょう',
            '疲': 'つか',
            '図書館': 'としょかん',
            '問題': 'もんだい',
            '入って': 'はいって',
            'これ': 'これ',
            'ここ': 'ここ',
            'タバコ': 'たばこ',
            'パーティー': 'ぱーてぃー'
        };
        
        // Function to add furigana to text
        function addFurigana(text) {
            let result = text;
            
            // Sort by length (longest first) to match compound kanji first
            const sortedKanji = Object.keys(kanjiReadings).sort((a, b) => b.length - a.length);
            
            sortedKanji.forEach(kanji => {
                const reading = kanjiReadings[kanji];
                // Only add furigana if the kanji exists in the text
                if (result.includes(kanji)) {
                    const regex = new RegExp(kanji, 'g');
                    result = result.replace(regex, 
                        `<span class="kanji-with-furigana">${kanji}<span class="furigana">${reading}</span></span>`
                    );
                }
            });
            
            return result;
        }
        
        // Shuffle function
        function shuffleArray(array) {
            const newArray = [...array];
            for (let i = newArray.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [newArray[i], newArray[j]] = [newArray[j], newArray[i]];
            }
            return newArray;
        }
        
        // Process quiz data to shuffle options and track correct answer
        function processQuizData(data) {
            return data.map(item => {
                const shuffledOptions = shuffleArray(item.options);
                const correctIndex = shuffledOptions.indexOf(item.correctAnswer);
                
                return {
                    ...item,
                    options: shuffledOptions,
                    correct: correctIndex
                };
            });
        }
        
        // Original quiz data with correct answers marked
        const originalQuizData = [
            {
                question: {
                    en: "Choose the correct polite copula:",
                    ja: "正しい丁寧な繋辞を選んでください：",
                    si: "නිවැරදි ආචාරශීලී සම්බන්ධක පදය තෝරන්න:"
                },
                sentenceJP: "彼女は医者___。",
                options: ["です", "だ", "だろう", "でしょう"],
                correctAnswer: "です",
                explanation: {
                    en: "です (desu) is the polite form of the copula 'to be'.",
                    ja: "「です」は繋辞「だ」の丁寧形です。",
                    si: "です (desu) යනු 'වීම' යන සම්බන්ධක පදයේ විධිමත් ස්වරූපයයි."
                }
            },
            {
                question: {
                    en: "Choose the correct casual copula:",
                    ja: "正しい普通の繋辞を選んでください：",
                    si: "නිවැරදි සාමාන්‍ය සම්බන්ධක පදය තෝරන්න:"
                },
                sentenceJP: "これは本___。",
                options: ["だ", "です", "だろう", "でしょうか"],
                correctAnswer: "だ",
                explanation: {
                    en: "だ (da) is the casual form of the copula 'to be'.",
                    ja: "「だ」は繋辞「です」の普通形です。",
                    si: "だ (da) යනු 'වීම' යන සම්බන්ධක පදයේ සාමාන්‍ය ස්වරූපයයි."
                }
            },
            {
                question: {
                    en: "Choose the correct prohibition form:",
                    ja: "正しい禁止形を選んでください：",
                    si: "නිවැරදි තහනම් ස්වරූපය තෝරන්න:"
                },
                sentenceJP: "入って___。",
                options: ["いけない", "いいです", "いたい", "いく"],
                correctAnswer: "いけない",
                explanation: {
                    en: "〜てはいけない (te wa ikenai) is the standard form for 'must not do'.",
                    ja: "「〜てはいけない」は「〜してはならない」の標準形です。",
                    si: "〜てはいけない (te wa ikenai) යනු 'නොකළ යුතුයි' සඳහා වන සම්මත ස්වරූපයයි."
                }
            },
            {
                question: {
                    en: "Complete the sentence with 'must not do':",
                    ja: "「〜してはならない」で文を完成させてください：",
                    si: "'නොකළ යුතුයි' යන්නෙන් වාක්‍යය සම්පූර්ණ කරන්න:"
                },
                sentenceJP: "ここでタバコを吸っ___。",
                options: ["ちゃいけない", "でもいい", "でしょう", "だ"],
                correctAnswer: "ちゃいけない",
                explanation: {
                    en: "ちゃいけない (cha ikenai) is a casual, spoken form of prohibition.",
                    ja: "「ちゃいけない」は、禁止を表すカジュアルな口語形です。",
                    si: "ちゃいけない (cha ikenai) යනු තහනමක් ප්‍රකාශ කිරීමේ අනියම් කථන ස්වරූපයකි."
                }
            },
            {
                question: {
                    en: "Choose the correct form for 'only':",
                    ja: "「〜だけ」の意味に合う形を選んでください：",
                    si: "'පමණක්' සඳහා නිවැරදි ස්වරූපය තෝරන්න:"
                },
                sentenceJP: "これ___欲しいです。",
                options: ["だけ", "でも", "で", "だろう"],
                correctAnswer: "だけ",
                explanation: {
                    en: "だけ (dake) is a particle meaning 'only' or 'just'.",
                    ja: "「だけ」は「〜だけ」または「〜のみ」を意味する助詞です。",
                    si: "だけ (dake) යනු 'පමණක්' හෝ 'හුදෙක්' යන අර්ථය දෙන අංශු පදයකි."
                }
            },
            {
                question: {
                    en: "Fill in the blank for 'as much as you want':",
                    ja: "「欲しいだけ」で文を完成させてください：",
                    si: "'ඔබට අවශ්‍ය තරම්' සඳහා හිස් තැන පුරවන්න:"
                },
                sentenceJP: "食べたい___食べていいよ。",
                options: ["だけ", "でも", "でしょう", "どうやって"],
                correctAnswer: "だけ",
                explanation: {
                    en: "V-tai + だけ means 'as much as you want to V'.",
                    ja: "動詞の「たい」形＋「だけ」は、「〜したいだけ」を意味します。",
                    si: "V-tai + だけ යනු 'ඔබට V කිරීමට අවශ්‍ය තරම්' යන අර්ථය දෙයි."
                }
            },
            {
                question: {
                    en: "Choose the correct casual expression for 'probably':",
                    ja: "「おそらく」の正しい普通形を選んでください：",
                    si: "'සමහරවිට' සඳහා නිවැරදි සාමාන්‍ය ප්‍රකාශනය තෝරන්න:"
                },
                sentenceJP: "明日は雨が降る___。",
                options: ["だろう", "でしょう", "だ", "です"],
                correctAnswer: "だろう",
                explanation: {
                    en: "だろう (darou) is the casual form expressing probability.",
                    ja: "「だろう」は可能性を表す「でしょう」の普通形です。",
                    si: "だろう (darou) යනු සම්භාවිතාව ප්‍රකාශ කරන සාමාන්‍ය ස්වරූපයයි."
                }
            },
            {
                question: {
                    en: "Choose the correct polite expression for 'probably':",
                    ja: "「おそらく」の正しい丁寧形を選んでください：",
                    si: "'සමහරවිට' සඳහා නිවැරදි විධිමත් ප්‍රකාශනය තෝරන්න:"
                },
                sentenceJP: "彼は今、忙しい___。",
                options: ["でしょう", "だろう", "です", "だ"],
                correctAnswer: "でしょう",
                explanation: {
                    en: "でしょう (deshou) is the polite form for expressing probability.",
                    ja: "「でしょう」は推測や可能性を表す丁寧形です。",
                    si: "でしょう (deshou) යනු සම්භාවිතාව ප්‍රකාශ කිරීමේ විධිමත් ස්වරූපයයි."
                }
            },
            {
                question: {
                    en: "Choose the correct particle for location of action:",
                    ja: "動作の場所を表す正しい助詞を選んでください：",
                    si: "ක්‍රියාවේ ස්ථානය සඳහා නිවැරදි අංශු පදය තෝරන්න:"
                },
                sentenceJP: "駅___友達に会いました。",
                options: ["で", "に", "へ", "と"],
                correctAnswer: "で",
                explanation: {
                    en: "で (de) marks the location where an action takes place.",
                    ja: "「で」は動作が行われる場所を表します。",
                    si: "で (de) යනු ක්‍රියාවක් සිදුවන ස්ථානය සලකුණු කරයි."
                }
            },
            {
                question: {
                    en: "Choose the correct particle for means of transport:",
                    ja: "交通手段を表す正しい助詞を選んでください：",
                    si: "ප්‍රවාහන මාධ්‍යයක් සඳහා නිවැරදි අංශු පදය තෝරන්න:"
                },
                sentenceJP: "車___会社に行きます。",
                options: ["で", "に", "を", "へ"],
                correctAnswer: "で",
                explanation: {
                    en: "で (de) indicates the tool, means, or method of an action.",
                    ja: "「で」は行為の道具、手段、または方法を示します。",
                    si: "で (de) ක්‍රියාවක මෙවලම, මාර්ගය හෝ ක්‍රමය දක්වයි."
                }
            },
            {
                question: {
                    en: "Choose the correct conjunction:",
                    ja: "正しい接続詞を選んでください：",
                    si: "නිවැරදි සම්බන්ධකය තෝරන්න:"
                },
                sentenceJP: "難しいです。___、頑張ります。",
                options: ["でも", "だけ", "どうして", "どんな"],
                correctAnswer: "でも",
                explanation: {
                    en: "でも (demo) means 'but' or 'however' at the start of a sentence.",
                    ja: "「でも」は文頭で「しかし」を意味する接続詞です。",
                    si: "でも (demo) වාක්‍යයක ආරම්භයේ 'නමුත්' යන අර්ථය දෙයි."
                }
            },
            {
                question: {
                    en: "Choose the correct particle for a suggestion:",
                    ja: "提案を表す正しい助詞を選んでください：",
                    si: "යෝජනාවක් සඳහා නිවැරදි අංශු පදය තෝරන්න:"
                },
                sentenceJP: "お茶___飲みませんか。",
                options: ["でも", "だけ", "と", "が"],
                correctAnswer: "でも",
                explanation: {
                    en: "Noun + でも suggests an example, meaning 'or something'.",
                    ja: "名詞＋「でも」は例を挙げ、「〜とか」を意味します。",
                    si: "නාමපදය + でも උදාහරණයක් යෝජනා කරයි, 'හෝ යමක්' යන අර්ථයෙන්."
                }
            },
            {
                question: {
                    en: "Fill in with the polite 'probably':",
                    ja: "丁寧な「おそらく」で空欄を埋めてください：",
                    si: "විධිමත් 'සමහරවිට' යන්නෙන් පුරවන්න:"
                },
                sentenceJP: "電車は遅れている___。",
                options: ["でしょう", "だろう", "です", "だ"],
                correctAnswer: "でしょう",
                explanation: {
                    en: "でしょう seeks confirmation, like a tag question.",
                    ja: "「でしょう」は付加疑問文のように確認を求めます。",
                    si: "でしょう තහවුරු කිරීමක් සොයයි, ටැග් ප්‍රශ්නයක් මෙන්."
                }
            },
            {
                question: {
                    en: "Choose the expression for 'what kind of':",
                    ja: "「どんな〜」の正しい表現を選んでください：",
                    si: "'මොන වගේ' සඳහා ප්‍රකාශනය තෝරන්න:"
                },
                sentenceJP: "彼は___人ですか。",
                options: ["どんな", "どうして", "どうやって", "どれ"],
                correctAnswer: "どんな",
                explanation: {
                    en: "どんな (donna) asks about type or characteristic.",
                    ja: "「どんな」は種類や特徴について尋ねます。",
                    si: "どんな (donna) වර්ගය හෝ ලක්ෂණය ගැන විමසයි."
                }
            },
            {
                question: {
                    en: "Choose the correct word for 'why':",
                    ja: "「なぜ」の正しい言葉を選んでください：",
                    si: "'ඇයි' යන වචනය තෝරන්න:"
                },
                sentenceJP: "パーティーに___来なかったの？",
                options: ["どうして", "どうやって", "どんな", "でも"],
                correctAnswer: "どうして",
                explanation: {
                    en: "どうして (doushite) is a conversational word for 'why'.",
                    ja: "「どうして」は「なぜ」の口語形です。",
                    si: "どうして (doushite) 'ඇයි' සඳහා සංවාදශීලී වචනයකි."
                }
            },
            {
                question: {
                    en: "Choose the word for 'how (method)':",
                    ja: "「どのように（方法）」の正しい言葉を選んでください：",
                    si: "'කෙසේ (ක්‍රමය)' යන වචනය තෝරන්න:"
                },
                sentenceJP: "駅まで___行けばいいですか。",
                options: ["どうやって", "どうして", "どんな", "でも"],
                correctAnswer: "どうやって",
                explanation: {
                    en: "どうやって (dōyatte) asks about the means or method.",
                    ja: "「どうやって」は手段や方法について尋ねます。",
                    si: "どうやって (dōyatte) මාර්ගය හෝ ක්‍රමය ගැන විමසයි."
                }
            },
            {
                question: {
                    en: "Choose the particle for 'only':",
                    ja: "「〜だけ」の正しい助詞を選んでください：",
                    si: "'පමණක්' සඳහා අංශු පදය තෝරන්න:"
                },
                sentenceJP: "三人___います。",
                options: ["だけ", "で", "に", "も"],
                correctAnswer: "だけ",
                explanation: {
                    en: "だけ (dake) specifies a limitation, meaning 'only'.",
                    ja: "「だけ」は限定を表し、「〜だけ」を意味します。",
                    si: "だけ (dake) සීමාවක් නියම කරයි, 'පමණක්' යන අර්ථයෙන්."
                }
            },
            {
                question: {
                    en: "Choose the casual negative prohibition:",
                    ja: "カジュアルな否定形を選んでください：",
                    si: "අනියම් ඍණාත්මක තහනම තෝරන්න:"
                },
                sentenceJP: "嘘を___。",
                options: ["ついちゃいけない", "ついてもいい", "ついたい", "ついました"],
                correctAnswer: "ついちゃいけない",
                explanation: {
                    en: "ついちゃいけない is the casual form meaning 'must not lie'.",
                    ja: "「ついちゃいけない」は「嘘をついてはならない」を意味します。",
                    si: "ついちゃいけない 'බොරු නොකළ යුතුයි' යන අර්ථය දරයි."
                }
            },
            {
                question: {
                    en: "Choose the particle for 'by car':",
                    ja: "「車で」の正しい助詞を選んでください：",
                    si: "'කාර් එකෙන්' සඳහා අංශු පදය තෝරන්න:"
                },
                sentenceJP: "学校に車___行きます。",
                options: ["で", "に", "と", "を"],
                correctAnswer: "で",
                explanation: {
                    en: "で (de) expresses the means of transportation.",
                    ja: "「で」は交通手段を表します。",
                    si: "で (de) ප්‍රවාහන මාධ්‍යය ප්‍රකාශ කරයි."
                }
            },
            {
                question: {
                    en: "Choose the polite copula:",
                    ja: "正しい丁寧な形を選んでください：",
                    si: "විධිමත් ස්වරූපය තෝරන්න:"
                },
                sentenceJP: "彼は学生___。",
                options: ["です", "だ", "だろう", "でしょう"],
                correctAnswer: "です",
                explanation: {
                    en: "です (desu) is the polite copula for stating a fact.",
                    ja: "「です」は事実を述べるための丁寧な繋辞です。",
                    si: "です (desu) යථාර්ථයක් ප්‍රකාශ කිරීමේ විධිමත් පදයයි."
                }
            },
            {
                question: {
                    en: "Choose the particle for a reason:",
                    ja: "理由を表す正しい助詞を選んでください：",
                    si: "හේතුවක් සඳහා අංශු පදය තෝරන්න:"
                },
                sentenceJP: "熱___学校を休みました。",
                options: ["で", "に", "から", "と"],
                correctAnswer: "で",
                explanation: {
                    en: "で (de) can indicate a cause or reason for an action.",
                    ja: "「で」は行為の原因や理由を示します。",
                    si: "で (de) ක්‍රියාවක් සඳහා හේතුවක් දක්වයි."
                }
            },
            {
                question: {
                    en: "Choose the correct question word:",
                    ja: "正しい疑問詞を選んでください：",
                    si: "නිවැරදි ප්‍රශ්න වචනය තෝරන්න:"
                },
                sentenceJP: "___日本語を勉強すればいいですか。",
                options: ["どうやって", "どうして", "どんな", "でも"],
                correctAnswer: "どうやって",
                explanation: {
                    en: "どうやって asks 'how' a specific action is performed.",
                    ja: "「どうやって」は行為がどのように行われるかを尋ねます。",
                    si: "どうやって ක්‍රියාවක් 'කෙසේ' සිදු කරන්නේදැයි ඇසීමට භාවිතා වේ."
                }
            },
            {
                question: {
                    en: "Complete with the casual copula:",
                    ja: "普通の繋辞で完成させてください：",
                    si: "සාමාන්‍ය පදයෙන් සම්පූර්ණ කරන්න:"
                },
                sentenceJP: "それは問題___。",
                options: ["だ", "です", "でしょう", "だろう"],
                correctAnswer: "だ",
                explanation: {
                    en: "だ (da) is the casual copula.",
                    ja: "「だ」は繋辞の普通形です。",
                    si: "だ (da) සාමාන්‍ය සම්බන්ධක පදයයි."
                }
            },
            {
                question: {
                    en: "Choose for 'even though':",
                    ja: "「でも」の正しい使い方を選んでください：",
                    si: "'නමුත්' සඳහා තෝරන්න:"
                },
                sentenceJP: "疲れています。___、行きます。",
                options: ["でも", "だけ", "だろう", "どうして"],
                correctAnswer: "でも",
                explanation: {
                    en: "でも (demo) is used as 'but' or 'however'.",
                    ja: "「でも」は「しかし」を意味します。",
                    si: "でも (demo) 'නමුත්' ලෙස භාවිතා වේ."
                }
            },
            {
                question: {
                    en: "Choose the particle for location:",
                    ja: "場所を表す助詞を選んでください：",
                    si: "ස්ථානය සඳහා අංශු පදය තෝරන්න:"
                },
                sentenceJP: "図書館___勉強します。",
                options: ["で", "に", "へ", "を"],
                correctAnswer: "で",
                explanation: {
                    en: "で (de) marks the location of an action.",
                    ja: "「で」は動作の場所を表します。",
                    si: "で (de) ක්‍රියාවේ ස්ථානය සලකුණු කරයි."
                }
            },
            {
                question: {
                    en: "Choose 'what kind of food':",
                    ja: "「どんな食べ物」を選んでください：",
                    si: "'මොන වගේ කෑම' තෝරන්න:"
                },
                sentenceJP: "___食べ物が好きですか。",
                options: ["どんな", "どうして", "どうやって", "どれ"],
                correctAnswer: "どんな",
                explanation: {
                    en: "どんな (donna) asks 'what kind of'.",
                    ja: "「どんな」は「どのような」を尋ねます。",
                    si: "どんな (donna) 'මොන වගේද' යන්න විමසයි."
                }
            },
            {
                question: {
                    en: "Choose for 'must not enter':",
                    ja: "「入ってはいけない」を選んでください：",
                    si: "'ඇතුල් විය නොහැක' තෝරන්න:"
                },
                sentenceJP: "ここに入って___。",
                options: ["はいけない", "もいい", "ください", "ました"],
                correctAnswer: "はいけない",
                explanation: {
                    en: "〜てはいけない expresses prohibition.",
                    ja: "「〜てはいけない」は禁止を表します。",
                    si: "〜てはいけない තහනමක් ප්‍රකාශ කරයි."
                }
            }
        ];
        
        // Shuffle and process quiz data
        let quizData = [];
        
        let currentQuestion = 0;
        let selectedAnswer = null;
        let score = 0;
        let startTime = null;
        let answers = [];
        let timerInterval = null;
        let isAnswerSubmitted = false;
        
        function startQuiz() {
            // Shuffle questions and their options
            const shuffledQuestions = shuffleArray(originalQuizData);
            quizData = processQuizData(shuffledQuestions);
            
            currentQuestion = 0;
            selectedAnswer = null;
            score = 0;
            answers = [];
            isAnswerSubmitted = false;
            
            document.getElementById('startScreen').classList.add('hide');
            document.getElementById('quizScreen').style.display = 'block';
            startTime = new Date();
            
            // Start timer
            if (timerInterval) clearInterval(timerInterval);
            timerInterval = setInterval(updateTimer, 1000);
            
            loadQuestion();
        }
        
        function loadQuestion() {
            if (currentQuestion >= quizData.length) {
                showResults();
                return;
            }
            
            const question = quizData[currentQuestion];
            selectedAnswer = null;
            isAnswerSubmitted = false;
            
            // Update progress
            const progress = ((currentQuestion + 1) / quizData.length) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
            
            // Update question number
            document.getElementById('questionNumber').textContent = 
                `${translations.question} ${currentQuestion + 1} ${translations.of} ${quizData.length}`;
            
            // Update question text
            document.getElementById('questionText').textContent = question.question[currentLang];
            
            // Update sentence with furigana
            const sentenceWithFurigana = addFurigana(question.sentenceJP);
            document.getElementById('sentenceDisplay').innerHTML = sentenceWithFurigana;
            
            // Create options
            const optionsContainer = document.getElementById('optionsContainer');
            optionsContainer.innerHTML = '';
            
            question.options.forEach((option, index) => {
                const btn = document.createElement('button');
                btn.className = 'option-btn';
                btn.textContent = option;
                btn.onclick = () => selectAnswer(index);
                optionsContainer.appendChild(btn);
            });
            
            // Reset feedback and buttons
            document.getElementById('feedbackBox').classList.remove('show');
            document.getElementById('submitBtn').style.display = 'inline-block';
            document.getElementById('submitBtn').disabled = false;
            document.getElementById('nextBtn').style.display = 'none';
        }
        
        function selectAnswer(index) {
            if (isAnswerSubmitted) return;
            
            selectedAnswer = index;
            const buttons = document.querySelectorAll('.option-btn');
            buttons.forEach((btn, i) => {
                btn.classList.remove('selected');
                if (i === index) {
                    btn.classList.add('selected');
                }
            });
        }
        
        function submitAnswer() {
            if (selectedAnswer === null) {
                alert(translations.select_answer);
                return;
            }
            
            if (isAnswerSubmitted) return;
            isAnswerSubmitted = true;
            
            const question = quizData[currentQuestion];
            const isCorrect = selectedAnswer === question.correct;
            
            if (isCorrect) {
                score++;
            }
            
            answers.push({
                question: currentQuestion,
                selected: selectedAnswer,
                correct: question.correct,
                isCorrect: isCorrect
            });
            
            // Show feedback
            const feedbackBox = document.getElementById('feedbackBox');
            const feedbackTitle = document.getElementById('feedbackTitle');
            const feedbackExplanation = document.getElementById('feedbackExplanation');
            
            feedbackTitle.textContent = isCorrect ? translations.correct : translations.incorrect;
            feedbackTitle.className = 'feedback-title ' + (isCorrect ? 'correct' : 'incorrect');
            feedbackExplanation.textContent = question.explanation[currentLang];
            feedbackBox.classList.add('show');
            
            // Update option buttons
            const buttons = document.querySelectorAll('.option-btn');
            buttons.forEach((btn, index) => {
                btn.disabled = true;
                if (index === question.correct) {
                    btn.classList.add('correct');
                } else if (index === selectedAnswer && !isCorrect) {
                    btn.classList.add('incorrect');
                }
                btn.classList.remove('selected');
            });
            
            // Update buttons
            document.getElementById('submitBtn').style.display = 'none';
            document.getElementById('nextBtn').style.display = 'inline-block';
        }
        
        function nextQuestion() {
            currentQuestion++;
            loadQuestion();
        }
        
        function showResults() {
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }
            
            document.getElementById('quizScreen').style.display = 'none';
            document.getElementById('resultsScreen').classList.add('show');
            
            const endTime = new Date();
            const timeTaken = Math.floor((endTime - startTime) / 1000);
            const minutes = Math.floor(timeTaken / 60);
            const seconds = timeTaken % 60;
            
            const accuracy = Math.round((score / quizData.length) * 100);
            
            // Set result message
            let message = translations.quiz_complete;
            if (accuracy >= 90) {
                message = translations.excellent;
            } else if (accuracy >= 70) {
                message = translations.good_job;
            } else {
                message = translations.keep_practicing;
            }
            
            document.getElementById('resultMessage').textContent = message;
            document.getElementById('finalScore').textContent = `${score}/${quizData.length}`;
            document.getElementById('correctAnswers').textContent = score;
            document.getElementById('accuracy').textContent = accuracy + '%';
            document.getElementById('timeTaken').textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            // Animate stats
            setTimeout(animateStats, 300);
        }
        
        function animateStats() {
            const statNumbers = document.querySelectorAll('.stats-card .stat-number');
            statNumbers.forEach((stat, index) => {
                stat.style.opacity = '0';
                stat.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    stat.style.transition = 'all 0.5s ease';
                    stat.style.opacity = '1';
                    stat.style.transform = 'translateY(0)';
                }, index * 100);
            });
        }
        
        function restartQuiz() {
            document.getElementById('resultsScreen').classList.remove('show');
            document.getElementById('startScreen').classList.remove('hide');
            
            // Reset quiz data for fresh shuffle on next start
            quizData = [];
        }
        
        // Timer display
        function updateTimer() {
            if (startTime && document.getElementById('quizScreen').style.display === 'block') {
                const now = new Date();
                const elapsed = Math.floor((now - startTime) / 1000);
                const minutes = Math.floor(elapsed / 60);
                const seconds = elapsed % 60;
                document.getElementById('timer').textContent = 
                    `⏱️ ${minutes}:${seconds.toString().padStart(2, '0')}`;
            }
        }
        
        // Page load animation
        window.addEventListener('load', function() {
            document.body.style.opacity = '0';
            setTimeout(() => {
                document.body.style.transition = 'opacity 0.5s ease';
                document.body.style.opacity = '1';
            }, 100);
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (document.getElementById('quizScreen').style.display === 'block') {
                // Number keys 1-4 to select answers
                if (e.key >= '1' && e.key <= '4' && !isAnswerSubmitted) {
                    const index = parseInt(e.key) - 1;
                    const buttons = document.querySelectorAll('.option-btn');
                    if (index < buttons.length) {
                        selectAnswer(index);
                    }
                }
                // Enter to submit/next
                if (e.key === 'Enter') {
                    if (document.getElementById('submitBtn').style.display !== 'none' && !isAnswerSubmitted) {
                        submitAnswer();
                    } else if (document.getElementById('nextBtn').style.display !== 'none') {
                        nextQuestion();
                    }
                }
            }
        });
        
        // Prevent accidental page refresh during quiz
        window.addEventListener('beforeunload', function(e) {
            if (document.getElementById('quizScreen').style.display === 'block' && 
                currentQuestion < quizData.length && 
                currentQuestion > 0) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        
        // Cleanup on page unload
        window.addEventListener('unload', function() {
            if (timerInterval) {
                clearInterval(timerInterval);
            }
        });
    </script>
  <script src="tracking.js"></script>
</body>
</html>

<?php include 'footer.php'; ?>