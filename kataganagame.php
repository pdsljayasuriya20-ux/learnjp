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
        'page_title' => 'Katakana Memory Game - Learn Japanese',
        'hero_title' => 'Katakana Memory Game',
        'hero_subtitle' => 'Match Katakana with Romaji',
        'back_btn' => 'Back to Katakana',
        'start_game' => 'Start Game',
        'restart_game' => 'Play Again',
        'moves' => 'Moves',
        'time' => 'Time',
        'best_score' => 'Best Score',
        'game_complete' => 'Congratulations!',
        'you_won' => 'You Won!',
        'choose_difficulty' => 'Choose Difficulty',
        'easy' => 'Easy',
        'medium' => 'Medium',
        'hard' => 'Hard',
        'easy_desc' => '8 pairs - Basic characters',
        'medium_desc' => '12 pairs - More characters',
        'hard_desc' => '16 pairs - All characters'
    ],
    'ja' => [
        'page_title' => 'カタカナ神経衰弱 - 日本語を学ぶ',
        'hero_title' => 'カタカナ神経衰弱',
        'hero_subtitle' => 'カタカナとローマ字を一致させる',
        'back_btn' => 'カタカナに戻る',
        'start_game' => 'ゲーム開始',
        'restart_game' => 'もう一度',
        'moves' => '手数',
        'time' => '時間',
        'best_score' => 'ベストスコア',
        'game_complete' => 'おめでとう！',
        'you_won' => '勝利！',
        'choose_difficulty' => '難易度を選択',
        'easy' => '簡単',
        'medium' => '普通',
        'hard' => '難しい',
        'easy_desc' => '8組 - 基本文字',
        'medium_desc' => '12組 - より多くの文字',
        'hard_desc' => '16組 - すべての文字'
    ],
    'si' => [
        'page_title' => 'කටකනා මතක ක්‍රීඩාව - ජපන් ඉගෙන ගන්න',
        'hero_title' => 'කටකනා මතක ක්‍රීඩාව',
        'hero_subtitle' => 'කටකනා සහ රෝමාජි ගැලපෙන්න',
        'back_btn' => 'කටකනා වෙත ආපසු',
        'start_game' => 'ක්‍රීඩාව ආරම්භ කරන්න',
        'restart_game' => 'නැවත ක්‍රීඩා කරන්න',
        'moves' => 'චලන',
        'time' => 'කාලය',
        'best_score' => 'හොඳම ලකුණු',
        'game_complete' => 'සුභ පැතුම්!',
        'you_won' => 'ඔබ ජයග්‍රහණය කළා!',
        'choose_difficulty' => 'දුෂ්කරතා මට්ටම තෝරන්න',
        'easy' => 'පහසු',
        'medium' => 'මධ්‍යම',
        'hard' => 'අපහසු',
        'easy_desc' => 'යුගල 8 - මූලික අක්ෂර',
        'medium_desc' => 'යුගල 12 - වැඩි අක්ෂර',
        'hard_desc' => 'යුගල 16 - සියලු අක්ෂර'
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
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #ff006e 0%, #8338ec 100%);
            --success-gradient: linear-gradient(135deg, #06ffa5, #00cc88);
            --danger-gradient: linear-gradient(135deg, #f5365c, #e11d48);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #ff006e 0%, #8338ec 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .japanese-text {
            font-family: 'Noto Sans JP', sans-serif;
        }
        
        .sinhala-text {
            font-family: 'Noto Sans Sinhala', sans-serif;
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
            font-size: clamp(2.5rem, 6vw, 4rem);
            font-weight: 900;
            background: linear-gradient(45deg, #ff006e, #8338ec, #3a86ff, #06ffa5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }
        
        .game-container {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .stats-bar {
            display: flex;
            justify-content: space-around;
            margin-bottom: 2rem;
            gap: 1rem;
        }
        
        .stat-item {
            background: linear-gradient(135deg, rgba(255, 0, 110, 0.1), rgba(131, 56, 236, 0.1));
            padding: 1rem 2rem;
            border-radius: 15px;
            text-align: center;
            flex: 1;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 900;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .game-board {
            display: grid;
            gap: 10px;
            margin: 2rem auto;
            max-width: 600px;
        }
        
        .game-board.easy {
            grid-template-columns: repeat(4, 1fr);
        }
        
        .game-board.medium {
            grid-template-columns: repeat(4, 1fr);
        }
        
        .game-board.hard {
            grid-template-columns: repeat(4, 1fr);
        }
        
        .card {
            aspect-ratio: 1;
            background: linear-gradient(135deg, rgba(255, 0, 110, 0.1), rgba(131, 56, 236, 0.1));
            border: 3px solid rgba(255, 0, 110, 0.2);
            border-radius: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            transition: all 0.3s ease;
            position: relative;
            transform-style: preserve-3d;
        }
        
        .card:hover:not(.flipped):not(.matched) {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(255, 0, 110, 0.3);
        }
        
        .card.flipped {
            background: white;
            border-color: #ff006e;
            transform: rotateY(180deg);
        }
        
        .card.matched {
            background: var(--success-gradient);
            color: white;
            border-color: transparent;
            animation: matchPulse 0.5s ease;
        }
        
        @keyframes matchPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .card-content {
            backface-visibility: hidden;
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .card-back {
            background: var(--primary-gradient);
            border-radius: 12px;
            color: white;
            font-size: 2rem;
        }
        
        .card-front {
            transform: rotateY(180deg);
        }
        
        .card.katakana .card-front {
            font-family: 'Noto Sans JP', sans-serif;
            font-size: 2.5rem;
        }
        
        .card.romaji .card-front {
            font-size: 1.5rem;
            text-transform: lowercase;
            color: #ff006e;
        }
        
        .difficulty-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .difficulty-btn {
            background: white;
            border: 3px solid rgba(255, 0, 110, 0.2);
            border-radius: 20px;
            padding: 2rem 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .difficulty-btn:hover {
            transform: translateY(-5px);
            border-color: #ff006e;
            box-shadow: 0 10px 30px rgba(255, 0, 110, 0.3);
        }
        
        .difficulty-btn h3 {
            color: #ff006e;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .difficulty-btn p {
            color: #666;
            margin: 0;
            font-size: 0.9rem;
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
        }
        
        .btn-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(255,0,110,0.4);
            color: white;
        }
        
        .modal-overlay {
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
        }
        
        .modal-overlay.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 25px;
            padding: 3rem;
            text-align: center;
            max-width: 500px;
            animation: modalPop 0.3s ease;
        }
        
        @keyframes modalPop {
            from {
                transform: scale(0.8);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .modal-title {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .modal-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .modal-stat {
            background: linear-gradient(135deg, rgba(255, 0, 110, 0.1), rgba(131, 56, 236, 0.1));
            padding: 1rem;
            border-radius: 15px;
        }
        
        .start-screen, .game-screen {
            display: none;
        }
        
        .start-screen.active, .game-screen.active {
            display: block;
        }
        
        @media (max-width: 768px) {
            .game-board {
                gap: 8px;
            }
            
            .card {
                font-size: 1.5rem;
            }
            
            .card.katakana .card-front {
                font-size: 2rem;
            }
            
            .card.romaji .card-front {
                font-size: 1.2rem;
            }
            
            .stats-bar {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Button -->
       
        
        <!-- Hero Card -->
        <div class="hero-card">
            <h1 class="hero-title japanese-text"><?php echo $t['hero_title']; ?></h1>
            <p class="lead"><?php echo $t['hero_subtitle']; ?></p>
        </div>
        
        <!-- Start Screen -->
        <div class="game-container start-screen active" id="startScreen">
            <h2 style="color: #ff006e; text-align: center; margin-bottom: 2rem;"><?php echo $t['choose_difficulty']; ?></h2>
            
            <div class="difficulty-selector">
                <div class="difficulty-btn" onclick="startGame('easy')">
                    <h3>🟢 <?php echo $t['easy']; ?></h3>
                    <p><?php echo $t['easy_desc']; ?></p>
                </div>
                <div class="difficulty-btn" onclick="startGame('medium')">
                    <h3>🟡 <?php echo $t['medium']; ?></h3>
                    <p><?php echo $t['medium_desc']; ?></p>
                </div>
                <div class="difficulty-btn" onclick="startGame('hard')">
                    <h3>🔴 <?php echo $t['hard']; ?></h3>
                    <p><?php echo $t['hard_desc']; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Game Screen -->
        <div class="game-container game-screen" id="gameScreen">
            <!-- Stats Bar -->
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-label"><?php echo $t['moves']; ?></div>
                    <div class="stat-value" id="moves">0</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label"><?php echo $t['time']; ?></div>
                    <div class="stat-value" id="time">0:00</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label"><?php echo $t['best_score']; ?></div>
                    <div class="stat-value" id="bestScore">--</div>
                </div>
            </div>
            
            <!-- Game Board -->
            <div class="game-board" id="gameBoard"></div>
            
            <!-- Restart Button -->
            <div class="text-center mt-4">
                <button class="btn btn-gradient" onclick="restartGame()">
                    <?php echo $t['restart_game']; ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Win Modal -->
    <div class="modal-overlay" id="winModal">
        <div class="modal-content">
            <div class="modal-title">🎉</div>
            <h2 style="color: #ff006e; margin-bottom: 1rem;"><?php echo $t['game_complete']; ?></h2>
            <p style="font-size: 1.5rem; font-weight: 700;"><?php echo $t['you_won']; ?></p>
            
            <div class="modal-stats">
                <div class="modal-stat">
                    <div style="color: #666; font-size: 0.9rem;"><?php echo $t['moves']; ?></div>
                    <div style="font-size: 2rem; font-weight: 900; color: #ff006e;" id="finalMoves">0</div>
                </div>
                <div class="modal-stat">
                    <div style="color: #666; font-size: 0.9rem;"><?php echo $t['time']; ?></div>
                    <div style="font-size: 2rem; font-weight: 900; color: #ff006e;" id="finalTime">0:00</div>
                </div>
            </div>
            
            <button class="btn btn-gradient" onclick="closeModal()">
                <?php echo $t['restart_game']; ?>
            </button>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const translations = <?php echo json_encode($t); ?>;
        
        // Katakana character map
        const katakanaMap = {
            'a': 'ア', 'i': 'イ', 'u': 'ウ', 'e': 'エ', 'o': 'オ',
            'ka': 'カ', 'ki': 'キ', 'ku': 'ク', 'ke': 'ケ', 'ko': 'コ',
            'sa': 'サ', 'shi': 'シ', 'su': 'ス', 'se': 'セ', 'so': 'ソ',
            'ta': 'タ', 'chi': 'チ', 'tsu': 'ツ', 'te': 'テ', 'to': 'ト',
            'na': 'ナ', 'ni': 'ニ', 'nu': 'ヌ', 'ne': 'ネ', 'no': 'ノ',
            'ha': 'ハ', 'hi': 'ヒ', 'fu': 'フ', 'he': 'ヘ', 'ho': 'ホ',
            'ma': 'マ', 'mi': 'ミ', 'mu': 'ム', 'me': 'メ', 'mo': 'モ',
            'ya': 'ヤ', 'yu': 'ユ', 'yo': 'ヨ',
            'ra': 'ラ', 'ri': 'リ', 'ru': 'ル', 're': 'レ', 'ro': 'ロ',
            'wa': 'ワ', 'wo': 'ヲ', 'n': 'ン',
            'ga': 'ガ', 'gi': 'ギ', 'gu': 'グ', 'ge': 'ゲ', 'go': 'ゴ',
            'za': 'ザ', 'ji': 'ジ', 'zu': 'ズ', 'ze': 'ゼ', 'zo': 'ゾ',
            'da': 'ダ', 'de': 'デ', 'do': 'ド',
            'ba': 'バ', 'bi': 'ビ', 'bu': 'ブ', 'be': 'ベ', 'bo': 'ボ',
            'pa': 'パ', 'pi': 'ピ', 'pu': 'プ', 'pe': 'ペ', 'po': 'ポ',
            'kya': 'キャ', 'kyu': 'キュ', 'kyo': 'キョ',
            'sha': 'シャ', 'shu': 'シュ', 'sho': 'ショ',
            'cha': 'チャ', 'chu': 'チュ', 'cho': 'チョ',
            'nya': 'ニャ', 'nyu': 'ニュ', 'nyo': 'ニョ',
            'hya': 'ヒャ', 'hyu': 'ヒュ', 'hyo': 'ヒョ',
            'mya': 'ミャ', 'myu': 'ミュ', 'myo': 'ミョ',
            'rya': 'リャ', 'ryu': 'リュ', 'ryo': 'リョ',
            'gya': 'ギャ', 'gyu': 'ギュ', 'gyo': 'ギョ',
            'ja': 'ジャ', 'ju': 'ジュ', 'jo': 'ジョ',
            'bya': 'ビャ', 'byu': 'ビュ', 'byo': 'ビョ',
            'pya': 'ピャ', 'pyu': 'ピュ', 'pyo': 'ピョ'
        };
        
        let gameState = {
            difficulty: 'easy',
            cards: [],
            flippedCards: [],
            matchedPairs: 0,
            moves: 0,
            startTime: null,
            timerInterval: null,
            bestScores: JSON.parse(localStorage.getItem('katakanaGameBestScores') || '{}')
        };
        
        function startGame(difficulty) {
            gameState.difficulty = difficulty;
            gameState.moves = 0;
            gameState.matchedPairs = 0;
            gameState.flippedCards = [];
            gameState.startTime = Date.now();
            
            document.getElementById('startScreen').classList.remove('active');
            document.getElementById('gameScreen').classList.add('active');
            
            initializeGame();
            startTimer();
            updateBestScore();
        }
        
        function initializeGame() {
            const pairCount = {
                easy: 8,
                medium: 12,
                hard: 16
            }[gameState.difficulty];
            
            // Get random pairs
            const allPairs = Object.entries(katakanaMap);
            const shuffled = allPairs.sort(() => Math.random() - 0.5);
            const selected = shuffled.slice(0, pairCount);
            
            // Create cards
            gameState.cards = [];
            selected.forEach(([romaji, katakana], index) => {
                gameState.cards.push(
                    { id: index * 2, type: 'katakana', value: katakana, pair: index },
                    { id: index * 2 + 1, type: 'romaji', value: romaji, pair: index }
                );
            });
            
            // Shuffle cards
            gameState.cards.sort(() => Math.random() - 0.5);
            
            // Render board
            renderBoard();
        }
        
        function renderBoard() {
            const board = document.getElementById('gameBoard');
            board.className = `game-board ${gameState.difficulty}`;
            board.innerHTML = '';
            
            gameState.cards.forEach(card => {
                const cardEl = document.createElement('div');
                cardEl.className = `card ${card.type}`;
                cardEl.dataset.id = card.id;
                cardEl.dataset.pair = card.pair;
                cardEl.innerHTML = `
                    <div class="card-content card-back">?</div>
                    <div class="card-content card-front">${card.value}</div>
                `;
                cardEl.onclick = () => flipCard(card.id);
                board.appendChild(cardEl);
            });
        }
        
        function flipCard(cardId) {
            if (gameState.flippedCards.length >= 2) return;
            
            const cardEl = document.querySelector(`[data-id="${cardId}"]`);
            if (cardEl.classList.contains('flipped') || cardEl.classList.contains('matched')) return;
            
            cardEl.classList.add('flipped');
            gameState.flippedCards.push(cardId);
            
            if (gameState.flippedCards.length === 2) {
                gameState.moves++;
                document.getElementById('moves').textContent = gameState.moves;
                checkMatch();
            }
        }
        
        function checkMatch() {
            const [id1, id2] = gameState.flippedCards;
            const card1 = gameState.cards.find(c => c.id === id1);
            const card2 = gameState.cards.find(c => c.id === id2);
            
            if (card1.pair === card2.pair) {
                // Match!
                setTimeout(() => {
                    document.querySelector(`[data-id="${id1}"]`).classList.add('matched');
                    document.querySelector(`[data-id="${id2}"]`).classList.add('matched');
                    gameState.matchedPairs++;
                    gameState.flippedCards = [];
                    
                    if (gameState.matchedPairs === gameState.cards.length / 2) {
                        endGame();
                    }
                }, 500);
            } else {
                // No match
                setTimeout(() => {
                    document.querySelector(`[data-id="${id1}"]`).classList.remove('flipped');
                    document.querySelector(`[data-id="${id2}"]`).classList.remove('flipped');
                    gameState.flippedCards = [];
                }, 1000);
            }
        }
        
        function startTimer() {
            gameState.timerInterval = setInterval(() => {
                const elapsed = Math.floor((Date.now() - gameState.startTime) / 1000);
                const minutes = Math.floor(elapsed / 60);
                const seconds = elapsed % 60;
                document.getElementById('time').textContent = 
                    `${minutes}:${seconds.toString().padStart(2, '0')}`;
            }, 1000);
        }
        
        function endGame() {
            clearInterval(gameState.timerInterval);
            const elapsed = Math.floor((Date.now() - gameState.startTime) / 1000);
            const minutes = Math.floor(elapsed / 60);
            const seconds = elapsed % 60;
            
            // Update best score
            const currentScore = gameState.moves;
            const bestScore = gameState.bestScores[gameState.difficulty];
            if (!bestScore || currentScore < bestScore) {
                gameState.bestScores[gameState.difficulty] = currentScore;
                localStorage.setItem('katakanaGameBestScores', JSON.stringify(gameState.bestScores));
                updateBestScore();
            }
            
            // Show modal
            document.getElementById('finalMoves').textContent = gameState.moves;
            document.getElementById('finalTime').textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            document.getElementById('winModal').classList.add('show');
        }
        
        function updateBestScore() {
            const best = gameState.bestScores[gameState.difficulty];
            document.getElementById('bestScore').textContent = best || '--';
        }
        
        function closeModal() {
            document.getElementById('winModal').classList.remove('show');
            restartGame();
        }
        
        function restartGame() {
            clearInterval(gameState.timerInterval);
            document.getElementById('gameScreen').classList.remove('active');
            document.getElementById('startScreen').classList.add('active');
            document.getElementById('moves').textContent = '0';
            document.getElementById('time').textContent = '0:00';
        }
        
        // Page load animation
        window.addEventListener('load', function() {
            document.body.style.opacity = '0';
            setTimeout(() => {
                document.body.style.transition = 'opacity 0.5s ease';
                document.body.style.opacity = '1';
            }, 100);
        });
    </script>
  <script src="tracking.js"></script>
</body>
</html>

<?php include 'footer.php'; ?>