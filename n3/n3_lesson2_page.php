<?php
session_start();
include '../header.php'; 

// Initialize language if not set
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Language switching
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>N3 Lesson 2 - Family & Relationships | Japanese Learning</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;700;900&family=Noto+Sans+Sinhala:wght@400;700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 10px 0;
            margin: 0;
        }
        
        .lesson-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 10px;
        }
        
        .lesson-header {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            text-align: center;
        }
        
        .lesson-title {
            font-size: clamp(1.5rem, 5vw, 3rem);
            font-weight: 900;
            background: linear-gradient(45deg, #ff006e, #8338ec);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            line-height: 1.2;
        }
        
        .lesson-subtitle {
            color: #667eea;
            font-size: clamp(0.9rem, 3vw, 1.2rem);
            font-weight: 600;
            line-height: 1.4;
        }
        
        .section-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        
        .section-title {
            font-size: clamp(1.3rem, 4vw, 1.8rem);
            font-weight: 800;
            background: linear-gradient(45deg, #8338ec, #667eea);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .section-icon {
            font-size: 1.5rem;
        }
        
        .vocab-table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 2rem;
        }
        
        .vocab-table {
            width: 100%;
            min-width: 600px;
            border-collapse: separate;
            border-spacing: 0;
            overflow: hidden;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .vocab-table thead {
            background: linear-gradient(135deg, #ff006e, #8338ec);
            color: white;
        }
        
        .vocab-table th {
            padding: 0.8rem 0.5rem;
            font-weight: 700;
            text-align: left;
            font-size: clamp(0.8rem, 2vw, 1rem);
        }
        
        .vocab-table td {
            padding: 0.8rem 0.5rem;
            border-bottom: 1px solid #f0f0f0;
            font-size: clamp(0.85rem, 2vw, 1rem);
        }
        
        .vocab-table tbody tr {
            transition: all 0.3s ease;
        }
        
        .vocab-table tbody tr:hover {
            background: linear-gradient(90deg, rgba(255,0,110,0.05), rgba(131,56,236,0.05));
        }
        
        .japanese-word {
            font-family: 'Noto Sans JP', sans-serif;
            font-size: clamp(1rem, 3vw, 1.3rem);
            font-weight: 700;
            color: #2d3748;
        }
        
        .romaji-word {
            color: #667eea;
            font-weight: 600;
            font-style: italic;
            font-size: clamp(0.85rem, 2vw, 1rem);
        }
        
        .sinhala-word {
            font-family: 'Noto Sans Sinhala', sans-serif;
            font-size: clamp(0.9rem, 2.5vw, 1.1rem);
            color: #8338ec;
            font-weight: 600;
        }
        
        .english-word {
            color: #4a5568;
        }
        
        .category-header {
            background: linear-gradient(135deg, #ff006e, #8338ec);
            color: white;
            padding: 0.8rem 1.2rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: clamp(1rem, 3vw, 1.2rem);
            margin: 1.5rem 0 1rem;
            display: inline-block;
            width: auto;
        }
        
        .back-btn, .next-lesson-btn, .prev-lesson-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 10px 25px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            display: inline-block;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            font-size: clamp(0.9rem, 2.5vw, 1rem);
            border: none;
        }
        
        .prev-lesson-btn {
            background: linear-gradient(45deg, #3a86ff, #2563eb);
        }
        
        .next-lesson-btn {
            background: linear-gradient(45deg, #06ffa5, #00cc88);
            margin-left: 10px;
        }
        
        .back-btn:hover, .next-lesson-btn:hover, .prev-lesson-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102,126,234,0.4);
            color: white;
        }
        
        .navigation-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }
        
        /* Game Styles */
        .game-container {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        
        .score-board {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .score-item {
            text-align: center;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, rgba(255,0,110,0.1), rgba(131,56,236,0.1));
            border-radius: 15px;
            flex: 1;
            min-width: 120px;
        }
        
        .score-label {
            font-size: clamp(0.75rem, 2vw, 0.9rem);
            color: #ff006e;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .score-value {
            font-size: clamp(1.5rem, 5vw, 2rem);
            font-weight: 900;
            background: linear-gradient(45deg, #ff006e, #8338ec);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .question-card {
            background: linear-gradient(135deg, rgba(255,0,110,0.05), rgba(131,56,236,0.05));
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 3px solid #ff006e;
        }
        
        .question-text {
            font-size: clamp(1.1rem, 4vw, 1.5rem);
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1rem;
            line-height: 1.6;
            word-wrap: break-word;
        }
        
        .question-translation {
            font-size: clamp(0.9rem, 2.5vw, 1rem);
            color: #718096;
            font-style: italic;
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }
        
        .sinhala-translation {
            font-family: 'Noto Sans Sinhala', sans-serif;
            font-size: clamp(0.9rem, 2.5vw, 1rem);
            color: #8338ec;
            font-weight: 600;
            line-height: 1.5;
        }
        
        .options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.8rem;
            margin-top: 1.5rem;
        }
        
        .option-btn {
            padding: 1.2rem 0.8rem;
            background: white;
            border: 3px solid #e2e8f0;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: clamp(0.9rem, 2.5vw, 1.1rem);
            text-align: center;
            word-wrap: break-word;
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
        }
        
        .option-btn:hover:not(.correct):not(.incorrect):not(.disabled) {
            border-color: #ff006e;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255,0,110,0.2);
        }
        
        .option-btn.selected {
            background: linear-gradient(135deg, #ff006e, #8338ec);
            color: white;
            border-color: #ff006e;
        }
        
        .option-btn.correct {
            background: linear-gradient(135deg, #06ffa5, #00cc88) !important;
            color: white !important;
            border-color: #06ffa5 !important;
            animation: correctPulse 0.5s ease;
        }
        
        .option-btn.incorrect {
            background: linear-gradient(135deg, #f5365c, #e11d48) !important;
            color: white !important;
            border-color: #f5365c !important;
            animation: shake 0.5s ease;
        }
        
        .option-btn.disabled {
            cursor: not-allowed;
            opacity: 0.6;
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
        
        .game-controls {
            display: flex;
            justify-content: center;
            gap: 0.8rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        
        .game-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: clamp(0.9rem, 2.5vw, 1.1rem);
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            min-width: 180px;
            max-width: 300px;
        }
        
        .btn-check {
            background: linear-gradient(45deg, #06ffa5, #00cc88);
            color: white;
        }
        
        .btn-next {
            background: linear-gradient(45deg, #ff006e, #8338ec);
            color: white;
        }
        
        .btn-restart {
            background: linear-gradient(45deg, #ff006e, #8338ec);
            color: white;
        }
        
        .game-btn:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .game-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .result-card {
            background: linear-gradient(135deg, #ff006e, #8338ec);
            border-radius: 20px;
            padding: 2rem 1.5rem;
            text-align: center;
            color: white;
        }
        
        .result-title {
            font-size: clamp(1.8rem, 6vw, 3rem);
            font-weight: 900;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .result-score {
            font-size: clamp(1.3rem, 4vw, 2rem);
            margin-bottom: 1.5rem;
        }
        
        .result-message {
            font-size: clamp(1rem, 3vw, 1.3rem);
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        
        .progress-bar-container {
            background: rgba(255,255,255,0.3);
            border-radius: 10px;
            height: 15px;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .progress-bar {
            background: linear-gradient(90deg, #ff006e, #8338ec);
            height: 100%;
            transition: width 0.3s ease;
            border-radius: 10px;
        }
        
        .tip-box {
            background: linear-gradient(135deg, #fff5e6, #ffe4b3);
            border-left: 5px solid #ffb700;
            border-radius: 12px;
            padding: 1.2rem;
            margin-top: 1.5rem;
        }
        
        .tip-icon {
            font-size: 1.3rem;
            margin-right: 8px;
        }
        
        .tip-title {
            font-weight: 800;
            color: #f59e0b;
            font-size: clamp(1rem, 3vw, 1.2rem);
            margin-bottom: 0.5rem;
        }
        
        .tip-box p {
            margin: 0;
            font-weight: 600;
            color: #2d3748;
            font-size: clamp(0.85rem, 2.5vw, 1rem);
            line-height: 1.6;
        }
        
        .hidden {
            display: none !important;
        }
        
        /* Mobile Specific Optimizations */
        @media (max-width: 768px) {
            .options-grid {
                grid-template-columns: 1fr;
            }
            
            .game-controls {
                flex-direction: column;
            }
            
            .game-btn {
                width: 100%;
                max-width: 100%;
            }
            
            .navigation-buttons {
                flex-direction: column;
            }
            
            .back-btn, .next-lesson-btn, .prev-lesson-btn {
                width: 100%;
                text-align: center;
                margin-left: 0;
            }
        }
        
        @media (max-width: 480px) {
            .vocab-table {
                min-width: 500px;
            }
            
            .option-btn {
                min-height: 70px;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="lesson-container">
        <div class="navigation-buttons">
            <a href="../index.php" class="back-btn">← Back to Home</a>
            <a href="n3_lesson1_page.php" class="prev-lesson-btn">← Previous Lesson</a>
            <a href="n3_lesson3_page.php" class="next-lesson-btn">Next Lesson →</a>
        </div>
        
        <!-- Part 2: Interactive Game -->
        <div class="game-container">
            <h2 class="section-title">
                <span class="section-icon">🎮</span>
                <span>Part 2: Practice Game | අභ්‍යාස ක්‍රීඩාව</span>
            </h2>
            
            <div class="score-board">
                <div class="score-item">
                    <div class="score-label">Score | ලකුණු</div>
                    <div class="score-value" id="score">0</div>
                </div>
                <div class="score-item">
                    <div class="score-label">Question | ප්‍රශ්නය</div>
                    <div class="score-value"><span id="currentQuestion">1</span>/8</div>
                </div>
            </div>
            
            <div class="progress-bar-container">
                <div class="progress-bar" id="progressBar"></div>
            </div>
            
            <div id="gameArea">
                <div class="question-card" id="questionCard">
                    <div class="question-text" id="questionText"></div>
                    <div class="question-translation" id="questionEnglish"></div>
                    <div class="sinhala-translation" id="questionSinhala"></div>
                    
                    <div class="options-grid" id="optionsGrid"></div>
                </div>
                
                <div class="game-controls">
                    <button class="game-btn btn-check" id="checkBtn">
                        Check Answer | පිළිතුර පරීක්ෂා කරන්න
                    </button>
                    <button class="game-btn btn-next hidden" id="nextBtn">
                        Next Question | ඊළඟ ප්‍රශ්නය →
                    </button>
                </div>
            </div>
            
            <div class="result-card hidden" id="resultCard">
                <div class="result-title">🎉 Game Complete!<br>ක්‍රීඩාව සම්පූර්ණයි!</div>
                <div class="result-score">Your Score: <span id="finalScore">0</span>/8</div>
                <div class="result-message" id="resultMessage"></div>
                <button class="game-btn btn-restart" id="restartBtn">
                    Play Again | නැවත ක්‍රීඩා කරන්න
                </button>
            </div>
            
            <div class="tip-box">
                <div class="tip-title">
                    <span class="tip-icon">💡</span>
                    Learning Note | ඉගෙනීමේ සටහන
                </div>
                <p>
                    In Japanese, family terms change based on whether you're talking about your own family or someone else's family. Remember to use humble forms for your own family!
                    <br><br><span class="sinhala-word">ජපන් භාෂාවේදී, ඔබේ පවුල ගැන කතා කරන විට හා වෙනත් කෙනෙකුගේ පවුල ගැන කතා කරන විට පවුල් යෙදුම් වෙනස් වේ. ඔබේම පවුල සඳහා නිහතමානී ස්වරූප භාවිතා කිරීමට මතක තබා ගන්න!</span>
                </p>
            </div>
        </div>
        
        <div class="navigation-buttons">
            <a href="../index.php" class="back-btn">← Back to Home</a>
            <a href="n3_lesson1_page.php" class="prev-lesson-btn">← Previous Lesson</a>
            <a href="n3_lesson3_page.php" class="next-lesson-btn">Next Lesson →</a>
        </div>
    </div>
    
    <script>
        // Questions data
        const questions = [
            {
                question: "私には息子が一人と、（　　）が二人います。",
                english: "I have one son and two ____.",
                sinhala: "මට එක් පුතෙක් සහ ____ දෙදෙනෙක් සිටී.",
                options: ["嫁 (Daughter-in-law)", "姪 (Niece)", "長男 (Eldest son)", "娘 (Daughter)"],
                correct: 3
            },
            {
                question: "息子と（　　）は、結婚して5年になります。",
                english: "My son and his ____ have been married for 5 years.",
                sinhala: "මගේ පුතා සහ ඔහුගේ ____ විවාහ වී අවුරුදු 5ක් වේ.",
                options: ["嫁 (Daughter-in-law)", "姪 (Niece)", "孫 (Grandchild)", "娘 (Daughter)"],
                correct: 0
            },
            {
                question: "彼にはお兄さんがいるから、（　　）ではないですよ。",
                english: "He has an older brother, so he is not the ____.",
                sinhala: "ඔහුට වැඩිමහල් සහෝදරයෙක් සිටින නිසා, ඔහු ____ නොවේ.",
                options: ["次男 (Second son)", "長男 (Eldest son)", "長女 (Eldest daughter)", "親せき (Relative)"],
                correct: 1
            },
            {
                question: "昨日、妹が、甥と（　　）を連れて遊びに来た。",
                english: "Yesterday, my younger sister came over with my nephew and ____.",
                sinhala: "ඊයේ, මගේ නංගි මගේ බෑණා (පිරිමි) සහ ____ සමඟ එළවළු වලට ආවා.",
                options: ["嫁 (Daughter-in-law)", "姪 (Niece)", "孫 (Grandchild)", "娘 (Daughter)"],
                correct: 1
            },
            {
                question: "（　　）から食事に誘われたら、なかなか断れない。",
                english: "When you are invited to a meal by your ____, it's hard to refuse.",
                sinhala: "ඔබේ ____ විසින් ආහාරයට ආරාධනා කරන විට, එය ප්‍රතික්ෂේප කිරීම අපහසුය.",
                options: ["部下 (Subordinate)", "上司 (Boss)", "後輩 (Junior)", "他人 (Stranger)"],
                correct: 1
            },
            {
                question: "父は毎月、（　　）と一緒に山登りに出かけます。",
                english: "Every month, my father goes mountain climbing with his ____.",
                sinhala: "සෑම මසකම, මගේ පියා ඔහුගේ ____ සමඟ කඳු තරණයට යනවා.",
                options: ["同級生 (Classmate)", "他人 (Stranger)", "独身 (Single)", "仲間 (Friends/Group)"],
                correct: 3
            },
            {
                question: "私と彼は、同じ高校の（　　）です。",
                english: "He and I are ____ from the same high school.",
                sinhala: "ඔහු සහ මම එකම උසස් පාසලේ ____ යි.",
                options: ["同級生 (Classmate)", "先輩 (Senior)", "同僚 (Co-worker)", "部下 (Subordinate)"],
                correct: 0
            },
            {
                question: "佐藤さんは、私の一年（　　）です。",
                english: "Mr. Sato is my ____ by one year.",
                sinhala: "සාටෝ මහතා මගේ අවුරුද්දකින් ____ ය.",
                options: ["後輩 (Junior)", "部下 (Subordinate)", "先輩 (Senior)", "同僚 (Co-worker)"],
                correct: 2
            }
        ];
        
        // Game state
        let currentQuestionIndex = 0;
        let score = 0;
        let selectedAnswer = null;
        let answered = false;
        
        // Get DOM elements
        const scoreEl = document.getElementById('score');
        const currentQuestionEl = document.getElementById('currentQuestion');
        const progressBar = document.getElementById('progressBar');
        const questionText = document.getElementById('questionText');
        const questionEnglish = document.getElementById('questionEnglish');
        const questionSinhala = document.getElementById('questionSinhala');
        const optionsGrid = document.getElementById('optionsGrid');
        const checkBtn = document.getElementById('checkBtn');
        const nextBtn = document.getElementById('nextBtn');
        const gameArea = document.getElementById('gameArea');
        const resultCard = document.getElementById('resultCard');
        const finalScoreEl = document.getElementById('finalScore');
        const resultMessage = document.getElementById('resultMessage');
        const restartBtn = document.getElementById('restartBtn');
        
        // Initialize game
        function initGame() {
            currentQuestionIndex = 0;
            score = 0;
            scoreEl.textContent = '0';
            gameArea.classList.remove('hidden');
            resultCard.classList.add('hidden');
            loadQuestion();
        }
        
        // Load question
        function loadQuestion() {
            const q = questions[currentQuestionIndex];
            answered = false;
            selectedAnswer = null;
            
            // Update UI
            currentQuestionEl.textContent = currentQuestionIndex + 1;
            questionText.textContent = q.question;
            questionEnglish.textContent = q.english;
            questionSinhala.textContent = q.sinhala;
            
            // Update progress
            const progress = ((currentQuestionIndex + 1) / questions.length) * 100;
            progressBar.style.width = progress + '%';
            
            // Create options
            optionsGrid.innerHTML = '';
            q.options.forEach((option, index) => {
                const btn = document.createElement('button');
                btn.className = 'option-btn';
                btn.textContent = option;
                btn.onclick = () => selectOption(index, btn);
                optionsGrid.appendChild(btn);
            });
            
            // Reset buttons
            checkBtn.disabled = true;
            checkBtn.classList.remove('hidden');
            nextBtn.classList.add('hidden');
        }
        
        // Select option
        function selectOption(index, btn) {
            if (answered) return;
            
            // Remove previous selection
            document.querySelectorAll('.option-btn').forEach(b => {
                b.classList.remove('selected');
            });
            
            // Mark new selection
            btn.classList.add('selected');
            selectedAnswer = index;
            checkBtn.disabled = false;
        }
        
        // Check answer
        function checkAnswer() {
            if (selectedAnswer === null || answered) return;
            
            answered = true;
            const q = questions[currentQuestionIndex];
            const buttons = document.querySelectorAll('.option-btn');
            
            // Disable all buttons
            buttons.forEach(btn => btn.classList.add('disabled'));
            
            // Show correct/incorrect
            if (selectedAnswer === q.correct) {
                buttons[selectedAnswer].classList.remove('selected');
                buttons[selectedAnswer].classList.add('correct');
                score++;
                scoreEl.textContent = score;
            } else {
                buttons[selectedAnswer].classList.remove('selected');
                buttons[selectedAnswer].classList.add('incorrect');
                buttons[q.correct].classList.add('correct');
            }
            
            // Show next button
            checkBtn.classList.add('hidden');
            nextBtn.classList.remove('hidden');
        }
        
        // Next question
        function nextQuestion() {
            currentQuestionIndex++;
            
            if (currentQuestionIndex < questions.length) {
                loadQuestion();
            } else {
                showResults();
            }
        }
        
        // Show results
        function showResults() {
            gameArea.classList.add('hidden');
            resultCard.classList.remove('hidden');
            finalScoreEl.textContent = score;
            
            let message = '';
            let messageS = '';
            
            if (score === 8) {
                message = 'Perfect! You are a master! 完璧です！';
                messageS = 'පරිපූර්ණයි! ඔබ ප්‍රවීණයෙක්!';
            } else if (score >= 6) {
                message = 'Excellent work! Great job! 素晴らしい！';
                messageS = 'විශිෂ්ට වැඩක්! ඉතා හොඳයි!';
            } else if (score >= 4) {
                message = 'Good effort! Keep practicing! 頑張りましょう！';
                messageS = 'හොඳ උත්සාහයක්! දිගටම පුහුණුවන්න!';
            } else {
                message = 'Keep learning! You can do it! もっと勉強しましょう！';
                messageS = 'දිගටම ඉගෙන ගන්න! ඔබට පුළුවන්!';
            }
            
            resultMessage.innerHTML = message + '<br><span class="sinhala-word">' + messageS + '</span>';
        }
        
        // Restart game
        function restartGame() {
            initGame();
        }
        
        // Event listeners
        checkBtn.addEventListener('click', checkAnswer);
        nextBtn.addEventListener('click', nextQuestion);
        restartBtn.addEventListener('click', restartGame);
        
        // Start game
        initGame();
    </script>
</body>
</html>>
        
        <div class="lesson-header">
            <div class="lesson-title">N3 - Lesson 2</div>
            <div class="lesson-subtitle">家族・人間関係 (Family & Relationships)<br>පවුල සහ මිනිස් සබඳතා</div>
        </div>
        
        <!-- Part 1: Vocabulary -->
        <div class="section-card">
            <h2 class="section-title">
                <span class="section-icon">📚</span>
                <span>Part 1: Vocabulary List | වචන මාලාව</span>
            </h2>
            
            <div class="category-header">● 家族 (Family) | පවුල</div>
            <div class="vocab-table-container">
                <table class="vocab-table">
                    <thead>
                        <tr>
                            <th>Japanese</th>
                            <th>Romaji</th>
                            <th>English</th>
                            <th>Sinhala</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="japanese-word">親</td>
                            <td class="romaji-word">oya</td>
                            <td class="english-word">parent</td>
                            <td class="sinhala-word">මව්පියන්</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">父親</td>
                            <td class="romaji-word">chichioya</td>
                            <td class="english-word">father</td>
                            <td class="sinhala-word">පියා</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">母親</td>
                            <td class="romaji-word">hahaoya</td>
                            <td class="english-word">mother</td>
                            <td class="sinhala-word">මව</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">両親</td>
                            <td class="romaji-word">ryōshin</td>
                            <td class="english-word">parents</td>
                            <td class="sinhala-word">දෙමාපියන්</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">祖父</td>
                            <td class="romaji-word">sofu</td>
                            <td class="english-word">grandfather</td>
                            <td class="sinhala-word">සීයා</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">祖母</td>
                            <td class="romaji-word">sobo</td>
                            <td class="english-word">grandmother</td>
                            <td class="sinhala-word">ආච්චි</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">おじ</td>
                            <td class="romaji-word">oji</td>
                            <td class="english-word">uncle</td>
                            <td class="sinhala-word">මාමා</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">おば</td>
                            <td class="romaji-word">oba</td>
                            <td class="english-word">aunt</td>
                            <td class="sinhala-word">නැන්දා</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">息子</td>
                            <td class="romaji-word">musuko</td>
                            <td class="english-word">son</td>
                            <td class="sinhala-word">පුතා</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">娘</td>
                            <td class="romaji-word">musume</td>
                            <td class="english-word">daughter</td>
                            <td class="sinhala-word">දුව</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">甥</td>
                            <td class="romaji-word">oi</td>
                            <td class="english-word">nephew</td>
                            <td class="sinhala-word">බෑණා (පිරිමි)</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">姪</td>
                            <td class="romaji-word">mei</td>
                            <td class="english-word">niece</td>
                            <td class="sinhala-word">බෑණා (ගැහැණු)</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">孫</td>
                            <td class="romaji-word">mago</td>
                            <td class="english-word">grandchild</td>
                            <td class="sinhala-word">මුනුබුරා</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">嫁</td>
                            <td class="romaji-word">yome</td>
                            <td class="english-word">daughter-in-law</td>
                            <td class="sinhala-word">මල්ලී</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">長男</td>
                            <td class="romaji-word">chōnan</td>
                            <td class="english-word">eldest son</td>
                            <td class="sinhala-word">ජේෂ්ඨ පුතා</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">次男</td>
                            <td class="romaji-word">jinan</td>
                            <td class="english-word">second son</td>
                            <td class="sinhala-word">දෙවන පුතා</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">長女</td>
                            <td class="romaji-word">chōjo</td>
                            <td class="english-word">eldest daughter</td>
                            <td class="sinhala-word">ජේෂ්ඨ දුව</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">次女</td>
                            <td class="romaji-word">jijo</td>
                            <td class="english-word">second daughter</td>
                            <td class="sinhala-word">දෙවන දුව</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">親せき</td>
                            <td class="romaji-word">shinseki</td>
                            <td class="english-word">relative</td>
                            <td class="sinhala-word">ඤාතීන්</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="category-header">● 友人・上司 (Friend / Boss) | මිතුරන් / ලොක්කා</div>
            <div class="vocab-table-container">
                <table class="vocab-table">
                    <thead>
                        <tr>
                            <th>Japanese</th>
                            <th>Romaji</th>
                            <th>English</th>
                            <th>Sinhala</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="japanese-word">友人</td>
                            <td class="romaji-word">yūjin</td>
                            <td class="english-word">friend</td>
                            <td class="sinhala-word">මිතුරා</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">知人 / 知り合い</td>
                            <td class="romaji-word">chijin / shiriai</td>
                            <td class="english-word">acquaintance</td>
                            <td class="sinhala-word">හඳුනන අය</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">先輩</td>
                            <td class="romaji-word">senpai</td>
                            <td class="english-word">one's senior</td>
                            <td class="sinhala-word">ජේෂ්ඨයා</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">後輩</td>
                            <td class="romaji-word">kōhai</td>
                            <td class="english-word">one's junior</td>
                            <td class="sinhala-word">කනිෂ්ඨයා</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">上司</td>
                            <td class="romaji-word">jōshi</td>
                            <td class="english-word">boss</td>
                            <td class="sinhala-word">ලොක්කා</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">部下</td>
                            <td class="romaji-word">buka</td>
                            <td class="english-word">subordinate</td>
                            <td class="sinhala-word">යටතේ සේවය කරන්නා</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">同僚</td>
                            <td class="romaji-word">dōryō</td>
                            <td class="english-word">co-worker</td>
                            <td class="sinhala-word">සම සේවකයා</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">仲間</td>
                            <td class="romaji-word">nakama</td>
                            <td class="english-word">friends / company</td>
                            <td class="sinhala-word">කණ්ඩායම</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">同級生</td>
                            <td class="romaji-word">dōkyūsei</td>
                            <td class="english-word">classmate</td>
                            <td class="sinhala-word">පන්ති මිතුරා</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="category-header">● その他 (Other) | වෙනත්</div>
            <div class="vocab-table-container">
                <table class="vocab-table">
                    <thead>
                        <tr>
                            <th>Japanese</th>
                            <th>Romaji</th>
                            <th>English</th>
                            <th>Sinhala</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="japanese-word">実家</td>
                            <td class="romaji-word">jikka</td>
                            <td class="english-word">one's parents' home</td>
                            <td class="sinhala-word">මව්පිය නිවස</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">他人</td>
                            <td class="romaji-word">tanin</td>
                            <td class="english-word">others / stranger</td>
                            <td class="sinhala-word">අන්නයා / ආගන්තුකයා</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">独身</td>
                            <td class="romaji-word">dokushin</td>
                            <td class="english-word">single</td>
                            <td class="sinhala-word">තනිව</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div