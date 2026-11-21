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
    <title>N3 Lesson 1 - Time & Periods | Japanese Learning</title>
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
            background: linear-gradient(45deg, #ffb700, #f59e0b);
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
            background: linear-gradient(135deg, #667eea, #764ba2);
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
            background: linear-gradient(90deg, rgba(102,126,234,0.05), rgba(118,75,162,0.05));
        }
        
        .japanese-word {
            font-family: 'Noto Sans JP', sans-serif;
            font-size: clamp(1rem, 3vw, 1.3rem);
            font-weight: 700;
            color: #2d3748;
        }
        
        .sinhala-word {
            font-family: 'Noto Sans Sinhala', sans-serif;
            font-size: clamp(0.9rem, 2.5vw, 1.1rem);
            color: #8338ec;
            font-weight: 600;
        }
        
        .romaji-word {
            color: #667eea;
            font-weight: 600;
            font-style: italic;
        }
        
        .english-word {
            color: #4a5568;
        }
        
        .category-header {
            background: linear-gradient(135deg, #06ffa5, #00cc88);
            color: white;
            padding: 0.8rem 1.2rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: clamp(1rem, 3vw, 1.2rem);
            margin: 1.5rem 0 1rem;
            display: inline-block;
            width: auto;
        }
        
        .back-btn, .next-lesson-btn {
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
        
        .next-lesson-btn {
            background: linear-gradient(45deg, #06ffa5, #00cc88);
            margin-left: 10px;
        }
        
        .back-btn:hover, .next-lesson-btn:hover {
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
            background: linear-gradient(135deg, rgba(102,126,234,0.1), rgba(118,75,162,0.1));
            border-radius: 15px;
            flex: 1;
            min-width: 120px;
        }
        
        .score-label {
            font-size: clamp(0.75rem, 2vw, 0.9rem);
            color: #667eea;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .score-value {
            font-size: clamp(1.5rem, 5vw, 2rem);
            font-weight: 900;
            background: linear-gradient(45deg, #06ffa5, #3a86ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .question-card {
            background: linear-gradient(135deg, rgba(102,126,234,0.05), rgba(118,75,162,0.05));
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 3px solid #667eea;
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
        
        .option-btn:hover:not(.correct):not(.incorrect) {
            border-color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(102,126,234,0.2);
        }
        
        .option-btn:active {
            transform: translateY(-1px);
        }
        
        .option-btn.selected {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: #667eea;
        }
        
        .option-btn.correct {
            background: linear-gradient(135deg, #06ffa5, #00cc88);
            color: white;
            border-color: #06ffa5;
            animation: correctPulse 0.5s ease;
            pointer-events: none;
        }
        
        .option-btn.incorrect {
            background: linear-gradient(135deg, #f5365c, #e11d48);
            color: white;
            border-color: #f5365c;
            animation: shake 0.5s ease;
            pointer-events: none;
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
        
        .btn-check.hide {
            display: none !important;
        }
        
        .btn-next {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            display: none;
        }
        
        .btn-next.show {
            display: inline-block !important;
        }
        
        .btn-restart {
            background: linear-gradient(45deg, #ff006e, #8338ec);
            color: white;
        }
        
        .game-btn:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .game-btn:active:not(:disabled) {
            transform: translateY(-1px);
        }
        
        .game-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .result-card {
            background: linear-gradient(135deg, #06ffa5, #00cc88);
            border-radius: 20px;
            padding: 2rem 1.5rem;
            text-align: center;
            color: white;
            display: none;
        }
        
        .result-card.show {
            display: block;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            background: linear-gradient(90deg, #06ffa5, #3a86ff);
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
            
            .back-btn, .next-lesson-btn {
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
            <a href="n3_lesson2_page.php" class="next-lesson-btn">Next Lesson →</a>
        </div>
        
        <div class="lesson-header">
            <div class="lesson-title">N3 - Lesson 1</div>
            <div class="lesson-subtitle">時間・時期 (Time & Periods)<br>කාලය සහ කාල පරිච්ඡේද</div>
        </div>
        
        <!-- Part 1: Vocabulary -->
        <div class="section-card">
            <h2 class="section-title">
                <span class="section-icon">📚</span>
                <span>Part 1: Vocabulary List | වචන මාලාව</span>
            </h2>
            
            <div class="category-header">● 時間 (Time) | කාලය</div>
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
                            <td class="japanese-word">現在</td>
                            <td class="romaji-word">Genzai</td>
                            <td class="english-word">Present / Now</td>
                            <td class="sinhala-word">වර්තමානය / දැන්</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">過去</td>
                            <td class="romaji-word">Kako</td>
                            <td class="english-word">Past</td>
                            <td class="sinhala-word">අතීතය</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">未来</td>
                            <td class="romaji-word">Mirai</td>
                            <td class="english-word">Future</td>
                            <td class="sinhala-word">අනාගතය</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">将来の夢</td>
                            <td class="romaji-word">Shōrai no yume</td>
                            <td class="english-word">Dream for the future</td>
                            <td class="sinhala-word">අනාගත සිහිනය</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">早朝</td>
                            <td class="romaji-word">Sōchō</td>
                            <td class="english-word">Early morning</td>
                            <td class="sinhala-word">උදේ පාන්දර</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">日中</td>
                            <td class="romaji-word">Nitchū</td>
                            <td class="english-word">Daytime / During the day</td>
                            <td class="sinhala-word">දවල් කාලය</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">深夜 / 真夜中</td>
                            <td class="romaji-word">Shin'ya / Mayonaka</td>
                            <td class="english-word">Middle of the night / Midnight</td>
                            <td class="sinhala-word">මධ්‍යම රාත්‍රිය</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="category-header">● 時期など (Periods / Seasons) | කාල පරිච්ඡේද</div>
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
                            <td class="japanese-word">平日</td>
                            <td class="romaji-word">Heijitsu</td>
                            <td class="english-word">Weekday</td>
                            <td class="sinhala-word">සතියේ දිනය</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">祝日</td>
                            <td class="romaji-word">Shukujitsu</td>
                            <td class="english-word">National Holiday</td>
                            <td class="sinhala-word">ජාතික නිවාඩු දිනය</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">週末</td>
                            <td class="romaji-word">Shūmatsu</td>
                            <td class="english-word">Weekend</td>
                            <td class="sinhala-word">සති අන්තය</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">年末年始</td>
                            <td class="romaji-word">Nenmatsu-nenshi</td>
                            <td class="english-word">Year-end and New Year's holidays</td>
                            <td class="sinhala-word">අවුරුදු අග සහ අලුත් අවුරුදු නිවාඩු</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">上旬</td>
                            <td class="romaji-word">Jōjun</td>
                            <td class="english-word">First 10 days of the month</td>
                            <td class="sinhala-word">මාසයේ පළමු දිනයන් 10</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">中旬</td>
                            <td class="romaji-word">Chūjun</td>
                            <td class="english-word">Second 10 days of the month</td>
                            <td class="sinhala-word">මාසයේ දෙවන දිනයන් 10</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">下旬</td>
                            <td class="romaji-word">Gejun</td>
                            <td class="english-word">Last 10 days of the month</td>
                            <td class="sinhala-word">මාසයේ අවසන් දිනයන් 10</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="category-header">● 休日 (Holidays) | නිවාඩු</div>
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
                            <td class="japanese-word">連休</td>
                            <td class="romaji-word">Renkyū</td>
                            <td class="english-word">Consecutive holidays</td>
                            <td class="sinhala-word">අඛණ්ඩ නිවාඩු</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">お盆休み</td>
                            <td class="romaji-word">Obon-yasumi</td>
                            <td class="english-word">Bon holiday (Buddhist festival in Aug)</td>
                            <td class="sinhala-word">ඕබොන් නිවාඩුව (අගෝස්තුවේ බෞද්ධ උත්සවය)</td>
                        </tr>
                        <tr>
                            <td class="japanese-word">ゴールデンウィーク</td>
                            <td class="romaji-word">Gōruden wīku</td>
                            <td class="english-word">Golden Week</td>
                            <td class="sinhala-word">ගෝල්ඩන් සතිය</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Part 2: Interactive Game -->
        <div class="game-container" id="gameContainer">
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
                <div class="progress-bar" id="progressBar" style="width: 12.5%"></div>
            </div>
            
            <div class="question-card" id="questionCard">
                <div class="question-text" id="questionText"></div>
                <div class="question-translation" id="questionEnglish"></div>
                <div class="sinhala-translation" id="questionSinhala"></div>
                
                <div class="options-grid" id="optionsGrid"></div>
            </div>
            
            <div class="game-controls">
                <button class="game-btn btn-check" id="checkBtn" type="button">
                    Check Answer | පිළිතුර පරීක්ෂා කරන්න
                </button>
                <button class="game-btn btn-next" id="nextBtn" type="button">
                    Next Question | ඊළඟ ප්‍රශ්නය →
                </button>
            </div>
            
            <div class="result-card" id="resultCard">
                <div class="result-title">🎉 Game Complete!<br>ක්‍රීඩාව සම්පූර්ණයි!</div>
                <div class="result-score">Your Score: <span id="finalScore">0</span>/8</div>
                <div class="result-message" id="resultMessage"></div>
                <button class="game-btn btn-restart" id="restartBtn" type="button">
                    Play Again | නැවත ක්‍රීඩා කරන්න
                </button>
            </div>
            
            <div class="tip-box">
                <div class="tip-title">
                    <span class="tip-icon">💡</span>
                    Learning Note | ඉගෙනීමේ සටහන
                </div>
                <p>
                    Remember that <strong>上旬 (Jōjun)</strong>, <strong>中旬 (Chūjun)</strong>, and <strong>下旬 (Gejun)</strong> are specific 10-day blocks used frequently in business and scheduling in Japan!
                    <br><br><span class="sinhala-word">ජපානයේ ව්‍යාපාර සහ කාලසටහන් සඳහා නිතර භාවිතා කරන විශේෂිත දින 10 කාල කොටස් වන බව මතක තබා ගන්න!</span>
                </p>
            </div>
        </div>
        
        <div class="navigation-buttons">
            <a href="../index.php" class="back-btn">← Back to Home</a>
            <a href="n3_lesson2_page.php" class="next-lesson-btn">Next Lesson →</a>
        </div>
    </div>
    
    <script>
        const questions = [
            {
                question: "あの学生は（　　）、新聞配達のアルバイトをしている。",
                english: "That student does a part-time job delivering newspapers ____.",
                sinhala: "ඒ ශිෂ්‍යයා ____ පුවත්පත් බෙදාහැරීමේ අර්ධකාලීන රැකියාවක් කරයි.",
                options: ["日中 (Daytime)", "現在 (Present)", "早朝 (Early morning)", "中旬 (Mid-month)"],
                correct: 2
            },
            {
                question: "（　　）だから、タクシー代がちょっと高くなっていた。",
                english: "Because it was ____, the taxi fare was a bit expensive.",
                sinhala: "____ නිසා, ටැක්සි ගාස්තුව මඳක් මිල විය.",
                options: ["深夜 (Late night)", "日中 (Daytime)", "早朝 (Early morning)", "週末 (Weekend)"],
                correct: 0
            },
            {
                question: "来月の（　　）に休みを取って、旅行に行くつもりです。",
                english: "I plan to take a break ____ of next month and go on a trip.",
                sinhala: "ලබන මාසයේ ____ විවේකයක් ගෙන සංචාරයකට යාමට මම සැලසුම් කරමි.",
                options: ["週末 (Weekend)", "現在 (Present)", "中旬 (Mid-month)", "祝日 (Holiday)"],
                correct: 2
            },
            {
                question: "私は昨年まで会社員でしたが、（　　）は自分の会社を経営しています。",
                english: "I was an office worker until last year, but ____ I run my own company.",
                sinhala: "මම පසුගිය වසර දක්වා කාර්යාල සේවකයෙක්, නමුත් ____ මම මගේම සමාගමක් පවත්වාගෙන යමි.",
                options: ["過去 (Past)", "現在 (Now)", "未来 (Future)", "早朝 (Early morning)"],
                correct: 1
            },
            {
                question: "「（　　）は何か予定、ありますか」「月曜に試験があるので、勉強します」",
                english: "Do you have plans for ____? I have an exam on Monday, so I'll study.",
                sinhala: "____ සඳහා ඔබට සැලසුම් තිබේද? සඳුදා මට විභාගයක් ඇති නිසා මම අධ්‍යයනය කරමි.",
                options: ["週末 (Weekend)", "平日 (Weekday)", "祝日 (Holiday)", "連休 (Consecutive holidays)"],
                correct: 0
            },
            {
                question: "5月上旬の（　　）に、海外旅行に行きます。",
                english: "I am going on an overseas trip during ____ in early May.",
                sinhala: "මැයි මස මුලදී ____ කාලය තුළ මම විදේශ සංචාරයකට යමි.",
                options: ["お盆休み (Obon holiday)", "年末年始 (Year-end)", "ゴールデンウィーク (Golden Week)", "連休 (Consecutive holidays)"],
                correct: 2
            },
            {
                question: "（　　）の夜は時間がないので、週末に会いましょう。",
                english: "I don't have time during ____ nights, so let's meet on the weekend.",
                sinhala: "____ රාත්‍රියේ මට කාලය නැති නිසා, සති අන්තයේදී හමුවෙමු.",
                options: ["週末 (Weekend)", "祝日 (Holiday)", "平日 (Weekday)", "連休 (Consecutive holidays)"],
                correct: 2
            },
            {
                question: "7月には「海の日」という（　　）がある。",
                english: "In July, there is a ____ called 'Marine Day'.",
                sinhala: "ජූලි මාසයේදී 'සමුද්‍ර දිනය' නමින් ____ තිබේ.",
                options: ["平日 (Weekday)", "祝日 (National Holiday)", "週末 (Weekend)", "連休 (Consecutive holidays)"],
                correct: 1
            }
        ];
        
        let currentQuestionIndex = 0;
        let score = 0;
        let selectedOption = null;
        
        // Get elements
        const checkBtn = document.getElementById('checkBtn');
        const nextBtn = document.getElementById('nextBtn');
        const restartBtn = document.getElementById('restartBtn');
        
        function loadQuestion() {
            const q = questions[currentQuestionIndex];
            document.getElementById('questionText').textContent = q.question;
            document.getElementById('questionEnglish').textContent = q.english;
            document.getElementById('questionSinhala').textContent = q.sinhala;
            document.getElementById('currentQuestion').textContent = currentQuestionIndex + 1;
            
            const optionsGrid = document.getElementById('optionsGrid');
            optionsGrid.innerHTML = '';
            
            q.options.forEach((option, index) => {
                const btn = document.createElement('div');
                btn.className = 'option-btn';
                btn.textContent = option;
                btn.dataset.index = index;
                btn.addEventListener('click', function() {
                    selectOption(index);
                });
                optionsGrid.appendChild(btn);
            });
            
            selectedOption = null;
            checkBtn.classList.remove('hide');
            checkBtn.disabled = true;
            nextBtn.classList.remove('show');
            
            // Re-enable option clicking
            const allOptions = document.querySelectorAll('.option-btn');
            allOptions.forEach(opt => {
                opt.style.pointerEvents = 'auto';
            });
            
            updateProgressBar();
        }
        
        function selectOption(index) {
            // Prevent selection if answer already checked
            if (document.querySelector('.option-btn.correct') || document.querySelector('.option-btn.incorrect')) {
                return;
            }
            
            const options = document.querySelectorAll('.option-btn');
            options.forEach(opt => opt.classList.remove('selected'));
            options[index].classList.add('selected');
            selectedOption = index;
            checkBtn.disabled = false;
        }
        
        function checkAnswer() {
            if (selectedOption === null) return;
            
            const options = document.querySelectorAll('.option-btn');
            const q = questions[currentQuestionIndex];
            
            // Disable all options
            options.forEach(opt => {
                opt.style.pointerEvents = 'none';
            });
            
            if (selectedOption === q.correct) {
                options[selectedOption].classList.remove('selected');
                options[selectedOption].classList.add('correct');
                score++;
                document.getElementById('score').textContent = score;
            } else {
                options[selectedOption].classList.remove('selected');
                options[selectedOption].classList.add('incorrect');
                options[q.correct].classList.add('correct');
            }
            
            checkBtn.classList.add('hide');
            nextBtn.classList.add('show');
        }
        
        function nextQuestion() {
            currentQuestionIndex++;
            
            if (currentQuestionIndex < questions.length) {
                loadQuestion();
            } else {
                showResults();
            }
        }
        
        function updateProgressBar() {
            const progress = ((currentQuestionIndex + 1) / questions.length) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
        }
        
        function showResults() {
            document.getElementById('questionCard').style.display = 'none';
            checkBtn.classList.add('hide');
            nextBtn.classList.remove('show');
            document.getElementById('resultCard').classList.add('show');
            document.getElementById('finalScore').textContent = score;
            
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
            
            document.getElementById('resultMessage').innerHTML = message + '<br><span class="sinhala-word">' + messageS + '</span>';
        }
        
        function restartGame() {
            currentQuestionIndex = 0;
            score = 0;
            document.getElementById('score').textContent = 0;
            document.getElementById('questionCard').style.display = 'block';
            document.getElementById('resultCard').classList.remove('show');
            loadQuestion();
        }
        
        // Event listeners
        checkBtn.addEventListener('click', function(e) {
            e.preventDefault();
            checkAnswer();
        });
        
        nextBtn.addEventListener('click', function(e) {
            e.preventDefault();
            nextQuestion();
        });
        
        restartBtn.addEventListener('click', function(e) {
            e.preventDefault();
            restartGame();
        });
        
        // Load first question when page loads
        window.addEventListener('DOMContentLoaded', function() {
            loadQuestion();
        });
    </script>
</body>
</html>