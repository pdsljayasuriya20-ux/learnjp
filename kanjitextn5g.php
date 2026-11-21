<?php
session_start();
include 'header.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

// Language handling
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$valid_langs = ['en', 'ja', 'si'];
if (!in_array($lang, $valid_langs)) {
    $lang = 'en';
}

// Page content arrays
$content = [
    'en' => [
        'page_title' => 'N5 Kanji Master Game - Learn Japanese',
        'hero_title' => 'N5 Kanji Challenge',
        'hero_subtitle' => 'Master 110 Essential N5 Kanji in 10 Steps',
        'back_btn' => 'Back to N5',
        'login_required' => 'Please login to save your progress',
        'login_btn' => 'Login',
        'register_btn' => 'Register',
        'step' => 'Step',
        'progress' => 'Progress',
        'mastered' => 'Mastered',
        'remaining' => 'Remaining',
        'start_step' => 'Start Step',
        'continue' => 'Continue',
        'next_step' => 'Next Step',
        'complete_step' => 'Complete Step',
        'retry' => 'Retry',
        'kanji' => 'Kanji',
        'meaning' => 'Meaning',
        'reading' => 'Reading (Kunyomi)',
        'check_answer' => 'Check Answer',
        'correct' => 'Correct!',
        'incorrect' => 'Try Again',
        'step_complete' => 'Step Complete!',
        'all_complete' => 'Congratulations! You\'ve mastered all N5 Kanji!',
        'score' => 'Score',
        'accuracy' => 'Accuracy',
        'instructions' => 'Learn kanji step by step. Each step contains 11 kanji. Complete all questions to unlock the next step.',
        'question' => 'Question',
        'what_meaning' => 'What is the meaning of this kanji?',
        'what_reading' => 'What is the reading of this kanji?',
        'unlock_msg' => 'Complete previous steps to unlock',
        'your_progress' => 'Your Progress',
        'total_mastered' => 'Total Mastered',
        'locked' => 'Locked'
    ],
    'ja' => [
        'page_title' => 'N5漢字マスターゲーム - 日本語を学ぶ',
        'hero_title' => 'N5漢字チャレンジ',
        'hero_subtitle' => '10段階で必須N5漢字110字をマスター',
        'back_btn' => 'N5に戻る',
        'login_required' => '進捗を保存するにはログインしてください',
        'login_btn' => 'ログイン',
        'register_btn' => '登録',
        'step' => 'ステップ',
        'progress' => '進捗',
        'mastered' => '習得済み',
        'remaining' => '残り',
        'start_step' => 'ステップ開始',
        'continue' => '続ける',
        'next_step' => '次のステップ',
        'complete_step' => 'ステップ完了',
        'retry' => 'もう一度',
        'kanji' => '漢字',
        'meaning' => '意味',
        'reading' => '読み方（訓読み）',
        'check_answer' => '答えを確認',
        'correct' => '正解！',
        'incorrect' => 'もう一度',
        'step_complete' => 'ステップ完了！',
        'all_complete' => 'おめでとう！N5漢字全てマスターしました！',
        'score' => 'スコア',
        'accuracy' => '正解率',
        'instructions' => '段階的に漢字を学習します。各ステップには11字が含まれています。',
        'question' => '問題',
        'what_meaning' => 'この漢字の意味は？',
        'what_reading' => 'この漢字の読み方は？',
        'unlock_msg' => '前のステップを完了してください',
        'your_progress' => 'あなたの進捗',
        'total_mastered' => '習得総数',
        'locked' => 'ロック中'
    ],
    'si' => [
        'page_title' => 'N5 කංජි මාස්ටර් ක්‍රීඩාව - ජපන් ඉගෙන ගන්න',
        'hero_title' => 'N5 කංජි අභියෝගය',
        'hero_subtitle' => 'පියවර 10කින් අත්‍යවශ්‍ය N5 කංජි 110 ප්‍රගුණ කරන්න',
        'back_btn' => 'N5 වෙත ආපසු',
        'login_required' => 'ඔබේ ප්‍රගතිය සුරැකීමට කරුණාකර පුරන්න',
        'login_btn' => 'ප්‍රවේශ වන්න',
        'register_btn' => 'ලියාපදිංචි වන්න',
        'step' => 'පියවර',
        'progress' => 'ප්‍රගතිය',
        'mastered' => 'ප්‍රගුණ කළ',
        'remaining' => 'ඉතිරි',
        'start_step' => 'පියවර ආරම්භ කරන්න',
        'continue' => 'දිගටම කරන්න',
        'next_step' => 'ඊළඟ පියවර',
        'complete_step' => 'පියවර සම්පූර්ණ කරන්න',
        'retry' => 'නැවත උත්සාහ කරන්න',
        'kanji' => 'කංජි',
        'meaning' => 'අර්ථය',
        'reading' => 'කියවීම (කුන්-යොමි)',
        'check_answer' => 'පිළිතුර පරීක්ෂා කරන්න',
        'correct' => 'නිවැරදියි!',
        'incorrect' => 'නැවත උත්සාහ කරන්න',
        'step_complete' => 'පියවර සම්පූර්ණයි!',
        'all_complete' => 'සුභ පැතුම්! ඔබ සියලු N5 කංජි ප්‍රගුණ කළා!',
        'score' => 'ලකුණු',
        'accuracy' => 'නිරවද්‍යතාව',
        'instructions' => 'පියවරෙන් පියවර කංජි ඉගෙන ගන්න. එක් එක් පියවරේ කංජි 11 ඇත.',
        'question' => 'ප්‍රශ්නය',
        'what_meaning' => 'මෙම කංජි වල අර්ථය කුමක්ද?',
        'what_reading' => 'මෙම කංජි වල කියවීම කුමක්ද?',
        'unlock_msg' => 'විවෘත කිරීමට පෙර පියවර සම්පූර්ණ කරන්න',
        'your_progress' => 'ඔබේ ප්‍රගතිය',
        'total_mastered' => 'මුළු ප්‍රගුණ කළ',
        'locked' => 'අගුළු දමා ඇත'
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
        }
        
        .sinhala-text {
            font-family: 'Noto Sans Sinhala', sans-serif;
        }
        
        .container {
            max-width: 1200px;
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
        
        .login-prompt {
            background: linear-gradient(135deg, rgba(255, 0, 110, 0.1), rgba(131, 56, 236, 0.1));
            padding: 2rem;
            border-radius: 20px;
            text-align: center;
            margin: 2rem 0;
            border: 2px solid rgba(255, 0, 110, 0.3);
        }
        
        .login-prompt h3 {
            color: #ff006e;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }
        
        .login-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .progress-overview {
            background: white;
            border-radius: 25px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }
        
        .progress-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .progress-header h2 {
            font-size: 2rem;
            font-weight: 900;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            border: 2px solid rgba(102, 126, 234, 0.2);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 900;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
        }
        
        .overall-progress {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .progress-bar-custom {
            height: 40px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.5);
            overflow: hidden;
            position: relative;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 20px;
            transition: width 1s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .step-card {
            background: white;
            border-radius: 20px;
            padding: 2rem 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 3px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .step-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .step-card:hover::before {
            opacity: 1;
        }
        
        .step-card:hover {
            transform: translateY(-10px) scale(1.05);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        .step-card.locked {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f5f5f5;
        }
        
        .step-card.locked:hover {
            transform: none;
            box-shadow: none;
            border-color: transparent;
        }
        
        .step-card.completed {
            border-color: #06ffa5;
            background: linear-gradient(135deg, rgba(6, 255, 165, 0.1), rgba(0, 204, 136, 0.1));
        }
        
        .step-card.in-progress {
            border-color: #ff006e;
            background: linear-gradient(135deg, rgba(255, 0, 110, 0.1), rgba(131, 56, 236, 0.1));
        }
        
        .step-number {
            font-size: 3rem;
            font-weight: 900;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .step-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }
        
        .step-info {
            font-size: 0.9rem;
            color: #666;
            position: relative;
            z-index: 1;
        }
        
        .step-progress {
            margin-top: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .step-progress-bar {
            height: 8px;
            background: rgba(0,0,0,0.1);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .step-progress-fill {
            height: 100%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .step-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            z-index: 2;
        }
        
        .step-badge.completed {
            background: linear-gradient(45deg, #06ffa5, #00cc88);
            color: white;
        }
        
        .step-badge.locked {
            background: #ddd;
            color: #999;
        }
        
        .game-container {
            background: white;
            border-radius: 25px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
            display: none;
            animation: slideIn 0.5s ease;
        }
        
        .game-container.active {
            display: block;
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
        
        .game-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .game-title {
            font-size: 2rem;
            font-weight: 900;
            background: linear-gradient(45deg, #ff006e, #8338ec);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .game-score {
            display: flex;
            gap: 2rem;
        }
        
        .score-item {
            text-align: center;
        }
        
        .score-value {
            font-size: 2rem;
            font-weight: 900;
            color: #667eea;
        }
        
        .score-label {
            font-size: 0.8rem;
            color: #666;
        }
        
        .question-card {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
            border-radius: 20px;
            padding: 3rem 2rem;
            margin-bottom: 2rem;
            text-align: center;
            border: 2px solid rgba(102, 126, 234, 0.2);
        }
        
        .question-number {
            font-size: 1rem;
            color: #667eea;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .kanji-display {
            font-size: 8rem;
            font-family: 'Noto Sans JP', sans-serif;
            font-weight: 900;
            color: #333;
            margin: 2rem 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            animation: kanjiPop 0.5s ease;
        }
        
        @keyframes kanjiPop {
            0% {
                transform: scale(0.5);
                opacity: 0;
            }
            70% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .question-text {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 2rem;
        }
        
        .answer-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .answer-option {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            border: 3px solid rgba(102, 126, 234, 0.2);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.1rem;
            font-weight: 600;
            position: relative;
            overflow: hidden;
        }
        
        .answer-option::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(102, 126, 234, 0.2);
            transform: translate(-50%, -50%);
            transition: all 0.4s ease;
        }
        
        .answer-option:hover::before {
            width: 200%;
            height: 200%;
        }
        
        .answer-option:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .answer-option.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        }
        
        .answer-option.correct {
            border-color: #06ffa5;
            background: linear-gradient(135deg, rgba(6, 255, 165, 0.2), rgba(0, 204, 136, 0.2));
            animation: correctPulse 0.5s ease;
        }
        
        .answer-option.incorrect {
            border-color: #f5365c;
            background: linear-gradient(135deg, rgba(245, 54, 92, 0.2), rgba(225, 29, 72, 0.2));
            animation: shake 0.5s ease;
        }
        
        @keyframes correctPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .feedback {
            margin-top: 1.5rem;
            padding: 1.5rem;
            border-radius: 15px;
            font-weight: 600;
            font-size: 1.2rem;
            display: none;
            align-items: center;
            justify-content: center;
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
            font-size: 2.5rem;
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
        }
        
        .btn-secondary-gradient {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 700;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-secondary-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .button-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
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
            position: relative;
            overflow: hidden;
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
        
        .modal-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .modal-stat {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            padding: 1.5rem;
            border-radius: 15px;
        }
        
        .modal-stat-value {
            font-size: 2.5rem;
            font-weight: 900;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .modal-stat-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .steps-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 1rem;
            }
            
            .step-card {
                padding: 1.5rem 1rem;
            }
            
            .step-number {
                font-size: 2rem;
            }
            
            .kanji-display {
                font-size: 5rem;
            }
            
            .answer-options {
                grid-template-columns: 1fr;
            }
            
            .game-score {
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
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
            <div class="hero-content">
                <h1 class="hero-title"><?php echo $t['hero_title']; ?></h1>
                <p class="hero-subtitle"><?php echo $t['hero_subtitle']; ?></p>
                <div class="instructions">
                    <i class="bi bi-info-circle"></i> <?php echo $t['instructions']; ?>
                </div>
            </div>
        </div>
        
        <?php if (!$isLoggedIn): ?>
        <!-- Login Prompt -->
        <div class="login-prompt">
            <h3><i class="bi bi-lock-fill"></i> <?php echo $t['login_required']; ?></h3>
            <p>Create an account or login to track your progress and unlock achievements!</p>
            <div class="login-buttons">
                <a href="login.php" class="btn btn-gradient">
                    <i class="bi bi-box-arrow-in-right"></i> <?php echo $t['login_btn']; ?>
                </a>
                <a href="register.php" class="btn btn-secondary-gradient">
                    <i class="bi bi-person-plus"></i> <?php echo $t['register_btn']; ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Progress Overview -->
        <div class="progress-overview" id="progressOverview">
            <div class="progress-header">
                <h2><?php echo $t['your_progress']; ?></h2>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number" id="totalMastered">0</div>
                    <div class="stat-label"><?php echo $t['total_mastered']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">110</div>
                    <div class="stat-label">Total Kanji</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="currentStep">1</div>
                    <div class="stat-label">Current <?php echo $t['step']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="accuracyRate">0%</div>
                    <div class="stat-label"><?php echo $t['accuracy']; ?></div>
                </div>
            </div>
            
            <div class="overall-progress">
                <div class="progress-bar-custom">
                    <div class="progress-fill" id="overallProgress" style="width: 0%">
                        <span id="progressText">0%</span>
                    </div>
                </div>
            </div>
            
            <!-- Steps Grid -->
            <div class="steps-grid" id="stepsGrid"></div>
        </div>
        
        <!-- Game Container -->
        <div class="game-container" id="gameContainer">
            <div class="game-header">
                <div>
                    <div class="game-title" id="gameTitle">Step 1</div>
                    <div style="color: #666; margin-top: 0.5rem;" id="gameSubtitle">Kanji 1-11</div>
                </div>
                <div class="game-score">
                    <div class="score-item">
                        <div class="score-value" id="gameScore">0</div>
                        <div class="score-label"><?php echo $t['score']; ?></div>
                    </div>
                    <div class="score-item">
                        <div class="score-value" id="gameAccuracy">100%</div>
                        <div class="score-label"><?php echo $t['accuracy']; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="question-card" id="questionCard">
                <div class="question-number" id="questionNumber"><?php echo $t['question']; ?> 1/11</div>
                <div class="kanji-display japanese-text" id="kanjiDisplay">日</div>
                <div class="question-text" id="questionText"><?php echo $t['what_meaning']; ?></div>
                
                <div class="answer-options" id="answerOptions"></div>
                
                <div class="feedback" id="feedback">
                    <span class="feedback-icon"></span>
                    <span class="feedback-text"></span>
                </div>
            </div>
            
            <div class="button-group">
                <button class="btn btn-secondary-gradient" onclick="backToSteps()">
                    <i class="bi bi-arrow-left"></i> Back to Steps
                </button>
                <button class="btn btn-gradient" id="nextBtn" onclick="nextQuestion()" style="display: none;">
                    <?php echo $t['next_step']; ?> <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Completion Modal -->
    <div class="completion-modal" id="completionModal">
        <div class="modal-content-custom">
            <div class="modal-icon">🎉</div>
            <h2 class="modal-title" id="modalTitle"><?php echo $t['step_complete']; ?></h2>
            <p id="modalMessage">Great job! You've completed this step.</p>
            
            <div class="modal-stats">
                <div class="modal-stat">
                    <div class="modal-stat-value" id="finalScore">0</div>
                    <div class="modal-stat-label"><?php echo $t['score']; ?></div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-value" id="finalAccuracy">0%</div>
                    <div class="modal-stat-label"><?php echo $t['accuracy']; ?></div>
                </div>
            </div>
            
            <div class="button-group">
                <button class="btn btn-secondary-gradient" onclick="closeModal()">
                    <i class="bi bi-arrow-left"></i> Back to Steps
                </button>
                <button class="btn btn-gradient" id="nextStepBtn" onclick="startNextStep()">
                    Next Step <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const translations = <?php echo json_encode($t); ?>;
        const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
        const userId = <?php echo $userId ? $userId : 'null'; ?>;
        
        // N5 Kanji Data (110 kanji)
        const kanjiData = [
            {number: 1, character: '日', kunyomi: 'ひ', meaning: 'day, sun', meaningSi: 'දින, හිරු'},
            {number: 2, character: '一', kunyomi: 'ひとつ', meaning: 'one', meaningSi: 'එක'},
            {number: 3, character: '国', kunyomi: 'くに', meaning: 'country', meaningSi: 'රට'},
            {number: 4, character: '人', kunyomi: 'ひと', meaning: 'person', meaningSi: 'මිනිසා'},
            {number: 5, character: '年', kunyomi: 'とし', meaning: 'year', meaningSi: 'වසර'},
            {number: 6, character: '大', kunyomi: 'おおきい', meaning: 'large, big', meaningSi: 'ලොකු'},
            {number: 7, character: '十', kunyomi: 'とお', meaning: 'ten', meaningSi: 'දහය'},
            {number: 8, character: '二', kunyomi: 'ふたつ', meaning: 'two', meaningSi: 'දෙක'},
            {number: 9, character: '本', kunyomi: 'もと', meaning: 'book, origin', meaningSi: 'පොත'},
            {number: 10, character: '中', kunyomi: 'なか', meaning: 'inside, middle', meaningSi: 'අග, මධ්‍යය'},
            {number: 11, character: '長', kunyomi: 'ながい', meaning: 'long', meaningSi: 'දිගු'},
            {number: 12, character: '出', kunyomi: 'でる', meaning: 'exit, go out', meaningSi: 'පිටවීම'},
            {number: 13, character: '三', kunyomi: 'みつ', meaning: 'three', meaningSi: 'තුන'},
            {number: 14, character: '時', kunyomi: 'とき', meaning: 'time', meaningSi: 'වේලාව'},
            {number: 15, character: '行', kunyomi: 'いく', meaning: 'go', meaningSi: 'යනවා'},
            {number: 16, character: '見', kunyomi: 'みる', meaning: 'see, look', meaningSi: 'බැලීම'},
            {number: 17, character: '月', kunyomi: 'つき', meaning: 'month, moon', meaningSi: 'මාසය, සඳ'},
            {number: 18, character: '分', kunyomi: 'わける', meaning: 'minute, divide', meaningSi: 'මිනිත්තුව'},
            {number: 19, character: '後', kunyomi: 'あと', meaning: 'after, behind', meaningSi: 'පසු'},
            {number: 20, character: '前', kunyomi: 'まえ', meaning: 'before, front', meaningSi: 'පෙර'},
            {number: 21, character: '生', kunyomi: 'いきる', meaning: 'live, life', meaningSi: 'ජීවිතය'},
            {number: 22, character: '五', kunyomi: 'いつつ', meaning: 'five', meaningSi: 'පහ'},
            {number: 23, character: '間', kunyomi: 'あいだ', meaning: 'interval, between', meaningSi: 'අතර'},
            {number: 24, character: '上', kunyomi: 'うえ', meaning: 'up, above', meaningSi: 'උඩ'},
            {number: 25, character: '東', kunyomi: 'ひがし', meaning: 'east', meaningSi: 'නැගෙනහිර'},
            {number: 26, character: '四', kunyomi: 'よつ', meaning: 'four', meaningSi: 'හතර'},
            {number: 27, character: '今', kunyomi: 'いま', meaning: 'now', meaningSi: 'දැන්'},
            {number: 28, character: '金', kunyomi: 'かね', meaning: 'gold, money', meaningSi: 'රත්රන්'},
            {number: 29, character: '私', kunyomi: 'わたし', meaning: 'I, me', meaningSi: 'මම'},
            {number: 30, character: '来', kunyomi: 'くる', meaning: 'come', meaningSi: 'එනවා'},
            {number: 31, character: '下', kunyomi: 'した', meaning: 'down, below', meaningSi: 'යට'},
            {number: 32, character: '学', kunyomi: 'まなぶ', meaning: 'study', meaningSi: 'ඉගෙනීම'},
            {number: 33, character: '高', kunyomi: 'たかい', meaning: 'tall, high', meaningSi: 'උස'},
            {number: 34, character: '円', kunyomi: 'まるい', meaning: 'yen, circle', meaningSi: 'යෙන්'},
            {number: 35, character: '子', kunyomi: 'こ', meaning: 'child', meaningSi: 'දරුවා'},
            {number: 36, character: '外', kunyomi: 'そと', meaning: 'outside', meaningSi: 'පිටත'},
            {number: 37, character: '八', kunyomi: 'やつ', meaning: 'eight', meaningSi: 'අට'},
            {number: 38, character: '九', kunyomi: 'ここのつ', meaning: 'nine', meaningSi: 'නවය'},
            {number: 39, character: '入', kunyomi: 'はいる', meaning: 'enter', meaningSi: 'ඇතුල්වීම'},
            {number: 40, character: '話', kunyomi: 'はなす', meaning: 'talk, speak', meaningSi: 'කතා කරනවා'},
            {number: 41, character: '西', kunyomi: 'にし', meaning: 'west', meaningSi: 'බටහිර'},
            {number: 42, character: '小', kunyomi: 'ちいさい', meaning: 'small', meaningSi: 'කුඩා'},
            {number: 43, character: '読', kunyomi: 'よむ', meaning: 'read', meaningSi: 'කියවීම'},
            {number: 44, character: '六', kunyomi: 'むつ', meaning: 'six', meaningSi: 'හය'},
            {number: 45, character: '先', kunyomi: 'さき', meaning: 'previous, ahead', meaningSi: 'පෙර'},
            {number: 46, character: '千', kunyomi: 'ち', meaning: 'thousand', meaningSi: 'දහස'},
            {number: 47, character: '水', kunyomi: 'みず', meaning: 'water', meaningSi: 'ජලය'},
            {number: 48, character: '女', kunyomi: 'おんな', meaning: 'woman', meaningSi: 'කාන්තාව'},
            {number: 49, character: '男', kunyomi: 'おとこ', meaning: 'man', meaningSi: 'පුරුෂයා'},
            {number: 50, character: '何', kunyomi: 'なに', meaning: 'what', meaningSi: 'කුමක්'},
            {number: 51, character: '口', kunyomi: 'くち', meaning: 'mouth', meaningSi: 'කට'},
            {number: 52, character: '手', kunyomi: 'て', meaning: 'hand', meaningSi: 'අත'},
            {number: 53, character: '百', kunyomi: 'ひゃく', meaning: 'hundred', meaningSi: 'සියය'},
            {number: 54, character: '休', kunyomi: 'やすむ', meaning: 'rest', meaningSi: 'විවේකය'},
            {number: 55, character: '名', kunyomi: 'な', meaning: 'name', meaningSi: 'නම'},
            {number: 56, character: '天', kunyomi: 'あまつ', meaning: 'sky, heaven', meaningSi: 'අහස'},
            {number: 57, character: '気', kunyomi: 'き', meaning: 'spirit, air', meaningSi: 'ආත්මය'},
            {number: 58, character: '右', kunyomi: 'みぎ', meaning: 'right', meaningSi: 'දකුණ'},
            {number: 59, character: '左', kunyomi: 'ひだり', meaning: 'left', meaningSi: 'වම'},
            {number: 60, character: '土', kunyomi: 'つち', meaning: 'soil, earth', meaningSi: 'පස'},
            {number: 61, character: '木', kunyomi: 'き', meaning: 'tree, wood', meaningSi: 'ගස'},
            {number: 62, character: '火', kunyomi: 'ひ', meaning: 'fire', meaningSi: 'ගින්න'},
            {number: 63, character: '雨', kunyomi: 'あめ', meaning: 'rain', meaningSi: 'වැස්ස'},
            {number: 64, character: '車', kunyomi: 'くるま', meaning: 'car', meaningSi: 'මෝටර් රථය'},
            {number: 65, character: '電', kunyomi: 'いなずま', meaning: 'electricity', meaningSi: 'විදුලිය'},
            {number: 66, character: '語', kunyomi: 'かたる', meaning: 'language', meaningSi: 'භාෂාව'},
            {number: 67, character: '食', kunyomi: 'たべる', meaning: 'eat, food', meaningSi: 'කනවා'},
            {number: 68, character: '飲', kunyomi: 'のむ', meaning: 'drink', meaningSi: 'බොනවා'},
            {number: 69, character: '聞', kunyomi: 'きく', meaning: 'hear, listen', meaningSi: 'අසනවා'},
            {number: 70, character: '山', kunyomi: 'やま', meaning: 'mountain', meaningSi: 'කන්ද'},
            {number: 71, character: '川', kunyomi: 'かわ', meaning: 'river', meaningSi: 'ගඟ'},
            {number: 72, character: '父', kunyomi: 'ちち', meaning: 'father', meaningSi: 'පියා'},
            {number: 73, character: '母', kunyomi: 'はは', meaning: 'mother', meaningSi: 'මව'},
            {number: 74, character: '友', kunyomi: 'とも', meaning: 'friend', meaningSi: 'මිතුරා'},
            {number: 75, character: '白', kunyomi: 'しろい', meaning: 'white', meaningSi: 'සුදු'},
            {number: 76, character: '書', kunyomi: 'かく', meaning: 'write', meaningSi: 'ලියනවා'},
            {number: 77, character: '北', kunyomi: 'きた', meaning: 'north', meaningSi: 'උතුර'},
            {number: 78, character: '南', kunyomi: 'みなみ', meaning: 'south', meaningSi: 'දකුණ'},
            {number: 79, character: '毎', kunyomi: 'ごと', meaning: 'every', meaningSi: 'සෑම'},
            {number: 80, character: '午', kunyomi: 'うま', meaning: 'noon', meaningSi: 'දහවල්'},
            {number: 81, character: '七', kunyomi: 'ななつ', meaning: 'seven', meaningSi: 'හත'},
            {number: 82, character: '万', kunyomi: 'よろず', meaning: 'ten thousand', meaningSi: 'දහ දහස'},
            {number: 83, character: '安', kunyomi: 'やすい', meaning: 'cheap, safe', meaningSi: 'ලාභ'},
            {number: 84, character: '半', kunyomi: 'なかば', meaning: 'half', meaningSi: 'භාගය'},
            {number: 85, character: '古', kunyomi: 'ふるい', meaning: 'old', meaningSi: 'පැරණි'},
            {number: 86, character: '多', kunyomi: 'おおい', meaning: 'many', meaningSi: 'බොහෝ'},
            {number: 87, character: '早', kunyomi: 'はやい', meaning: 'early, fast', meaningSi: 'ඉක්මන්'},
            {number: 88, character: '新', kunyomi: 'あたらしい', meaning: 'new', meaningSi: 'අලුත්'},
            {number: 89, character: '空', kunyomi: 'そら', meaning: 'sky, empty', meaningSi: 'අහස'},
            {number: 90, character: '言', kunyomi: 'いう', meaning: 'say', meaningSi: 'කියනවා'},
            {number: 91, character: '道', kunyomi: 'みち', meaning: 'road, way', meaningSi: 'පාර'},
            {number: 92, character: '立', kunyomi: 'たつ', meaning: 'stand', meaningSi: 'නැගී සිටිනවා'},
            {number: 93, character: '会', kunyomi: 'あう', meaning: 'meet', meaningSi: 'හමුවීම'},
            {number: 94, character: '耳', kunyomi: 'みみ', meaning: 'ear', meaningSi: 'කන'},
            {number: 95, character: '足', kunyomi: 'あし', meaning: 'foot, leg', meaningSi: 'පාදය'},
            {number: 96, character: '体', kunyomi: 'からだ', meaning: 'body', meaningSi: 'ශරීරය'},
            {number: 97, character: '目', kunyomi: 'め', meaning: 'eye', meaningSi: 'ඇස'},
            {number: 98, character: '店', kunyomi: 'みせ', meaning: 'shop', meaningSi: 'කඩය'},
            {number: 99, character: '駅', kunyomi: 'えき', meaning: 'station', meaningSi: 'දුම්රිය ස්ථානය'},
            {number: 100, character: '社', kunyomi: 'やしろ', meaning: 'company', meaningSi: 'සමාගම'},
            {number: 101, character: '魚', kunyomi: 'さかな', meaning: 'fish', meaningSi: 'මාළු'},
            {number: 102, character: '買', kunyomi: 'かう', meaning: 'buy', meaningSi: 'මිලදී ගන්නවා'},
            {number: 103, character: '米', kunyomi: 'こめ', meaning: 'rice', meaningSi: 'සහල්'},
            {number: 104, character: '教', kunyomi: 'おしえる', meaning: 'teach', meaningSi: 'උගන්වනවා'},
            {number: 105, character: '近', kunyomi: 'ちかい', meaning: 'near', meaningSi: 'ළඟ'},
            {number: 106, character: '弱', kunyomi: 'よわい', meaning: 'weak', meaningSi: 'දුර්වල'},
            {number: 107, character: '室', kunyomi: 'むろ', meaning: 'room', meaningSi: 'කාමරය'},
            {number: 108, character: '京', kunyomi: 'みやこ', meaning: 'capital', meaningSi: 'අගනුවර'},
            {number: 109, character: '力', kunyomi: 'ちから', meaning: 'power, force', meaningSi: 'බලය'},
            {number: 110, character: '夜', kunyomi: 'よる', meaning: 'night', meaningSi: 'රාත්‍රිය'}
        ];
        
        // Game state
        let gameState = {
            currentStep: 1,
            currentQuestion: 0,
            score: 0,
            totalQuestions: 0,
            correctAnswers: 0,
            stepKanji: [],
            userProgress: {},
            completedSteps: [],
            activeSession: null,
            questionOrder: [],
            answersGiven: [],
            answerLocked: false
        };
        
        // Load progress from localStorage
        function loadProgress() {
            const saved = localStorage.getItem('n5KanjiGameProgress');
            if (saved) {
                try {
                    gameState.userProgress = JSON.parse(saved);
                } catch (e) {
                    console.error('Error loading progress:', e);
                    gameState.userProgress = {};
                }
            }
            
            // Load active session
            const session = localStorage.getItem('n5KanjiGameSession');
            if (session) {
                try {
                    const sessionData = JSON.parse(session);
                    const sessionAge = Date.now() - sessionData.timestamp;
                    if (sessionAge < 24 * 60 * 60 * 1000) {
                        gameState.activeSession = sessionData;
                        resumeSession();
                    } else {
                        clearSession();
                    }
                } catch (e) {
                    console.error('Error loading session:', e);
                    clearSession();
                }
            }
        }
        
        // Save progress
        function saveProgress() {
            try {
                localStorage.setItem('n5KanjiGameProgress', JSON.stringify(gameState.userProgress));
                if (isLoggedIn) {
                    saveToServer();
                }
            } catch (e) {
                console.error('Error saving progress:', e);
            }
        }
        
        // Save session
        function saveSession() {
            try {
                const sessionData = {
                    currentStep: gameState.currentStep,
                    currentQuestion: gameState.currentQuestion,
                    score: gameState.score,
                    correctAnswers: gameState.correctAnswers,
                    totalQuestions: gameState.totalQuestions,
                    stepKanji: gameState.stepKanji,
                    questionOrder: gameState.questionOrder,
                    answersGiven: gameState.answersGiven,
                    timestamp: Date.now()
                };
                localStorage.setItem('n5KanjiGameSession', JSON.stringify(sessionData));
            } catch (e) {
                console.error('Error saving session:', e);
            }
        }
        
        // Clear session
        function clearSession() {
            try {
                localStorage.removeItem('n5KanjiGameSession');
                gameState.activeSession = null;
            } catch (e) {
                console.error('Error clearing session:', e);
            }
        }
        
        // Resume session
        function resumeSession() {
            if (!gameState.activeSession) return;
            
            const session = gameState.activeSession;
            
            // Validate session data
            if (!session.stepKanji || session.stepKanji.length === 0) {
                console.error('Invalid session data');
                clearSession();
                return;
            }
            
            gameState.currentStep = session.currentStep;
            gameState.currentQuestion = session.currentQuestion;
            gameState.score = session.score;
            gameState.correctAnswers = session.correctAnswers;
            gameState.totalQuestions = session.totalQuestions;
            gameState.stepKanji = session.stepKanji;
            gameState.questionOrder = session.questionOrder || [];
            gameState.answersGiven = session.answersGiven || [];
            
            // Validate question index
            if (gameState.currentQuestion >= gameState.totalQuestions) {
                console.warn('Session completed, clearing');
                clearSession();
                backToSteps();
                return;
            }
            
            document.getElementById('progressOverview').style.display = 'none';
            document.getElementById('gameContainer').classList.add('active');
            
            document.getElementById('gameTitle').textContent = `${translations.step} ${gameState.currentStep}`;
            if (gameState.stepKanji.length > 0) {
                document.getElementById('gameSubtitle').textContent = 
                    `Kanji ${gameState.stepKanji[0].number}-${gameState.stepKanji[gameState.stepKanji.length-1].number}`;
            }
            
            showQuestion();
            showNotification('📚 Resuming your previous session...', 'info');
        }
        
        // Save to server
        function saveToServer() {
            if (!isLoggedIn) return;
            
            fetch('api/save_n5kanji_progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    userId: userId,
                    progress: gameState.userProgress,
                    session: {
                        currentStep: gameState.currentStep,
                        currentQuestion: gameState.currentQuestion,
                        score: gameState.score
                    }
                })
            }).catch(error => console.error('Error saving to server:', error));
        }
        
        // Show notification
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'info' ? 'linear-gradient(45deg, #667eea, #764ba2)' : 'linear-gradient(45deg, #06ffa5, #00cc88)'};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                z-index: 10000;
                animation: slideInRight 0.5s ease;
                font-weight: 600;
                max-width: 300px;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.5s ease';
                setTimeout(() => notification.remove(), 500);
            }, 3000);
        }
        
        // Initialize steps
        function initializeSteps() {
            const stepsGrid = document.getElementById('stepsGrid');
            stepsGrid.innerHTML = '';
            
            const kanjiPerStep = 11;
            
            for (let i = 0; i < 10; i++) {
                const stepNumber = i + 1;
                const startIndex = i * kanjiPerStep;
                const endIndex = Math.min(startIndex + kanjiPerStep, kanjiData.length);
                const stepKanji = kanjiData.slice(startIndex, endIndex);
                
                const progress = gameState.userProgress[stepNumber] || { completed: false, score: 0 };
                const isLocked = i > 0 && !(gameState.userProgress[i] && gameState.userProgress[i].completed);
                const isCompleted = progress.completed;
                const isInProgress = !isCompleted && !isLocked && stepNumber === gameState.currentStep;
                
                let cardClass = 'step-card';
                if (isLocked) cardClass += ' locked';
                if (isCompleted) cardClass += ' completed';
                if (isInProgress) cardClass += ' in-progress';
                
                const card = document.createElement('div');
                card.className = cardClass;
                card.onclick = () => {
                    if (!isLocked) {
                        startStep(stepNumber, stepKanji);
                    }
                };
                
                let badgeIcon = '';
                let badgeClass = '';
                if (isCompleted) {
                    badgeIcon = '✓';
                    badgeClass = 'completed';
                } else if (isLocked) {
                    badgeIcon = '🔒';
                    badgeClass = 'locked';
                }
                
                card.innerHTML = `
                    ${badgeIcon ? `<div class="step-badge ${badgeClass}">${badgeIcon}</div>` : ''}
                    <div class="step-number">${stepNumber}</div>
                    <div class="step-title">${translations.step} ${stepNumber}</div>
                    <div class="step-info">${stepKanji.length} Kanji</div>
                    ${!isLocked ? `
                        <div class="step-progress">
                            <div class="step-progress-bar">
                                <div class="step-progress-fill" style="width: ${progress.score || 0}%"></div>
                            </div>
                        </div>
                    ` : `<div class="step-info" style="color: #999; margin-top: 1rem;">${translations.locked}</div>`}
                `;
                
                stepsGrid.appendChild(card);
            }
        }
        
        // Start a step
        function startStep(stepNumber, stepKanji) {
            // Validate stepKanji
            if (!stepKanji || stepKanji.length === 0) {
                console.error('Invalid stepKanji data');
                showNotification('Error loading step data', 'error');
                return;
            }
            
            // Check if resuming same step
            if (gameState.activeSession && 
                gameState.activeSession.currentStep === stepNumber && 
                gameState.currentQuestion < gameState.totalQuestions) {
                document.getElementById('progressOverview').style.display = 'none';
                document.getElementById('gameContainer').classList.add('active');
                showQuestion();
                return;
            }
            
            // Initialize new step
            gameState.currentStep = stepNumber;
            gameState.currentQuestion = 0;
            gameState.score = 0;
            gameState.correctAnswers = 0;
            gameState.stepKanji = stepKanji;
            gameState.totalQuestions = stepKanji.length;
            gameState.answersGiven = [];
            gameState.answerLocked = false;
            
            // Generate randomized question order
            gameState.questionOrder = stepKanji.map((_, index) => index);
            for (let i = gameState.questionOrder.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [gameState.questionOrder[i], gameState.questionOrder[j]] = 
                [gameState.questionOrder[j], gameState.questionOrder[i]];
            }
            
            document.getElementById('progressOverview').style.display = 'none';
            document.getElementById('gameContainer').classList.add('active');
            
            document.getElementById('gameTitle').textContent = `${translations.step} ${stepNumber}`;
            document.getElementById('gameSubtitle').textContent = 
                `Kanji ${stepKanji[0].number}-${stepKanji[stepKanji.length-1].number}`;
            
            saveSession();
            showQuestion();
        }
        
        // Show current question
        function showQuestion() {
            // Validate indices
            if (gameState.currentQuestion >= gameState.totalQuestions || 
                gameState.currentQuestion >= gameState.questionOrder.length) {
                console.error('Invalid question index');
                completeStep();
                return;
            }
            
            const questionIndex = gameState.questionOrder[gameState.currentQuestion];
            const currentKanji = gameState.stepKanji[questionIndex];
            
            // Validate kanji data
            if (!currentKanji || !currentKanji.character) {
                console.error('Invalid kanji data');
                return;
            }
            
            // Reset answer lock
            gameState.answerLocked = false;
            
            // Update question number
            document.getElementById('questionNumber').textContent = 
                `${translations.question} ${gameState.currentQuestion + 1}/${gameState.totalQuestions}`;
            
            // Display kanji
            document.getElementById('kanjiDisplay').textContent = currentKanji.character;
            
            // Determine question type
            let questionType;
            if (gameState.answersGiven[gameState.currentQuestion]) {
                questionType = gameState.answersGiven[gameState.currentQuestion].questionType;
            } else {
                questionType = (questionIndex % 2 === 0) ? 'meaning' : 'reading';
                if (questionType === 'reading' && !currentKanji.kunyomi) {
                    questionType = 'meaning';
                }
            }
            
            // Update question text
            if (questionType === 'meaning') {
                document.getElementById('questionText').textContent = translations.what_meaning;
                generateMeaningOptions(currentKanji, questionIndex);
            } else {
                document.getElementById('questionText').textContent = translations.what_reading;
                generateReadingOptions(currentKanji, questionIndex);
            }
            
            // Reset feedback and next button
            document.getElementById('feedback').classList.remove('show', 'correct', 'incorrect');
            document.getElementById('nextBtn').style.display = 'none';
            
            // Update game stats
            updateGameStats();
            saveSession();
        }
        
        // Generate meaning options
        function generateMeaningOptions(currentKanji, seed) {
            if (!currentKanji || !currentKanji.meaning) {
                console.error('Invalid kanji data');
                return;
            }
            
            const options = [currentKanji.meaning];
            const usedIndices = new Set([currentKanji.number - 1]);
            
            let attempts = 0;
            const maxAttempts = 100;
            
            while (options.length < 4 && attempts < maxAttempts) {
                attempts++;
                const randomIndex = Math.floor(Math.random() * kanjiData.length);
                
                if (!usedIndices.has(randomIndex)) {
                    const randomKanji = kanjiData[randomIndex];
                    if (randomKanji && randomKanji.meaning && !options.includes(randomKanji.meaning)) {
                        options.push(randomKanji.meaning);
                        usedIndices.add(randomIndex);
                    }
                }
            }
            
            // Ensure we have 4 options
            while (options.length < 4) {
                options.push(`Option ${options.length + 1}`);
            }
            
            // Shuffle options
            const shuffledOptions = [...options];
            for (let i = shuffledOptions.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [shuffledOptions[i], shuffledOptions[j]] = [shuffledOptions[j], shuffledOptions[i]];
            }
            
            renderOptions(shuffledOptions, currentKanji.meaning, 'meaning');
        }
        
        // Generate reading options
        function generateReadingOptions(currentKanji, seed) {
            if (!currentKanji || !currentKanji.kunyomi) {
                console.warn('No kunyomi available, switching to meaning');
                generateMeaningOptions(currentKanji, seed);
                return;
            }
            
            const options = [currentKanji.kunyomi];
            const usedIndices = new Set([currentKanji.number - 1]);
            
            let attempts = 0;
            const maxAttempts = 100;
            
            while (options.length < 4 && attempts < maxAttempts) {
                attempts++;
                const randomIndex = Math.floor(Math.random() * kanjiData.length);
                
                if (!usedIndices.has(randomIndex)) {
                    const randomKanji = kanjiData[randomIndex];
                    const reading = randomKanji ? randomKanji.kunyomi : null;
                    if (reading && !options.includes(reading)) {
                        options.push(reading);
                        usedIndices.add(randomIndex);
                    }
                }
            }
            
            // Ensure we have 4 options
            if (options.length < 4) {
                console.warn('Not enough reading options, using meanings');
                generateMeaningOptions(currentKanji, seed);
                return;
            }
            
            // Shuffle options
            const shuffledOptions = [...options];
            for (let i = shuffledOptions.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [shuffledOptions[i], shuffledOptions[j]] = [shuffledOptions[j], shuffledOptions[i]];
            }
            
            renderOptions(shuffledOptions, currentKanji.kunyomi, 'reading');
        }
        
        // Render options
        function renderOptions(options, correctAnswer, questionType) {
            const container = document.getElementById('answerOptions');
            container.innerHTML = '';
            
            options.forEach(option => {
                const optionDiv = document.createElement('div');
                optionDiv.className = 'answer-option';
                optionDiv.innerHTML = questionType === 'reading' ? 
                    `<span class="japanese-text">${option}</span>` : option;
                
                optionDiv.onclick = () => checkAnswer(option, correctAnswer, optionDiv, questionType);
                container.appendChild(optionDiv);
            });
        }
        
        // Check answer
        function checkAnswer(selectedAnswer, correctAnswer, optionElement, questionType) {
            // Prevent multiple clicks
            if (gameState.answerLocked) return;
            gameState.answerLocked = true;
            
            // Disable all options
            const allOptions = document.querySelectorAll('.answer-option');
            allOptions.forEach(opt => {
                opt.style.pointerEvents = 'none';
            });
            
            const isCorrect = selectedAnswer === correctAnswer;
            const feedbackEl = document.getElementById('feedback');
            const feedbackIcon = feedbackEl.querySelector('.feedback-icon');
            const feedbackText = feedbackEl.querySelector('.feedback-text');
            
            // Record answer
            gameState.answersGiven[gameState.currentQuestion] = {
                questionType: questionType,
                selectedAnswer: selectedAnswer,
                correctAnswer: correctAnswer,
                isCorrect: isCorrect
            };
            
            if (isCorrect) {
                gameState.correctAnswers++;
                gameState.score += 10;
                
                optionElement.classList.add('correct');
                feedbackEl.classList.add('show', 'correct');
                feedbackEl.classList.remove('incorrect');
                feedbackIcon.textContent = '✓';
                feedbackText.textContent = translations.correct;
                
                createMiniConfetti();
            } else {
                optionElement.classList.add('incorrect');
                
                // Highlight correct answer
                allOptions.forEach(opt => {
                    const optText = opt.textContent.trim();
                    if (optText === correctAnswer) {
                        opt.classList.add('correct');
                    }
                });
                
                feedbackEl.classList.add('show', 'incorrect');
                feedbackEl.classList.remove('correct');
                feedbackIcon.textContent = '✗';
                feedbackText.textContent = `${translations.incorrect} - Correct: ${correctAnswer}`;
            }
            
            // Show next button
            document.getElementById('nextBtn').style.display = 'inline-block';
            
            updateGameStats();
            saveSession();
        }
        
        // Next question
        function nextQuestion() {
            gameState.currentQuestion++;
            
            if (gameState.currentQuestion < gameState.totalQuestions) {
                showQuestion();
            } else {
                completeStep();
            }
        }
        
        // Complete step
        function completeStep() {
            const accuracy = Math.round((gameState.correctAnswers / gameState.totalQuestions) * 100);
            
            gameState.userProgress[gameState.currentStep] = {
                completed: true,
                score: accuracy,
                date: new Date().toISOString()
            };
            saveProgress();
            clearSession();
            
            document.getElementById('modalTitle').textContent = translations.step_complete;
            document.getElementById('modalMessage').textContent = 
                `Great job! You've completed Step ${gameState.currentStep}.`;
            document.getElementById('finalScore').textContent = gameState.score;
            document.getElementById('finalAccuracy').textContent = accuracy + '%';
            
            const nextStepBtn = document.getElementById('nextStepBtn');
            if (gameState.currentStep < 10) {
                nextStepBtn.style.display = 'inline-block';
            } else {
                nextStepBtn.style.display = 'none';
                document.getElementById('modalTitle').textContent = translations.all_complete;
                document.getElementById('modalMessage').textContent = 
                    'Amazing! You\'ve mastered all 110 N5 Kanji! 🎊';
            }
            
            document.getElementById('completionModal').classList.add('show');
            createConfetti();
        }
        
        // Update game stats
        function updateGameStats() {
            document.getElementById('gameScore').textContent = gameState.score;
            const accuracy = gameState.totalQuestions > 0 
                ? Math.round((gameState.correctAnswers / Math.max(gameState.currentQuestion, 1)) * 100)
                : 100;
            document.getElementById('gameAccuracy').textContent = accuracy + '%';
        }
        
        // Update overall progress
        function updateOverallProgress() {
            const completedSteps = Object.values(gameState.userProgress).filter(p => p.completed).length;
            const totalMastered = completedSteps * 11;
            const percentage = Math.round((totalMastered / kanjiData.length) * 100);
            
            document.getElementById('totalMastered').textContent = Math.min(totalMastered, 110);
            document.getElementById('currentStep').textContent = Math.min(completedSteps + 1, 10);
            
            const avgAccuracy = Object.values(gameState.userProgress)
                .filter(p => p.completed)
                .reduce((sum, p) => sum + p.score, 0) / (completedSteps || 1);
            document.getElementById('accuracyRate').textContent = Math.round(avgAccuracy) + '%';
            
            document.getElementById('overallProgress').style.width = percentage + '%';
            document.getElementById('progressText').textContent = percentage + '%';
        }
        
        // Back to steps
        function backToSteps() {
            document.getElementById('gameContainer').classList.remove('active');
            document.getElementById('progressOverview').style.display = 'block';
            initializeSteps();
            updateOverallProgress();
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('completionModal').classList.remove('show');
            backToSteps();
        }
        
        // Start next step
        function startNextStep() {
            document.getElementById('completionModal').classList.remove('show');
            const nextStep = gameState.currentStep + 1;
            
            if (nextStep <= 10) {
                const kanjiPerStep = 11;
                const startIndex = (nextStep - 1) * kanjiPerStep;
                const endIndex = Math.min(startIndex + kanjiPerStep, kanjiData.length);
                const stepKanji = kanjiData.slice(startIndex, endIndex);
                
                startStep(nextStep, stepKanji);
            }
        }
        
        // Create confetti
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
                    
                    setTimeout(() => confetti.remove(), 3100);
                }, i * 30);
            }
        }
        
        // Create mini confetti
        function createMiniConfetti() {
            const colors = ['#06ffa5', '#00cc88'];
            const confettiCount = 20;
            
            for (let i = 0; i < confettiCount; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.style.position = 'fixed';
                    confetti.style.width = '8px';
                    confetti.style.height = '8px';
                    confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.left = '50%';
                    confetti.style.top = '30%';
                    confetti.style.opacity = '1';
                    confetti.style.transform = 'translate(-50%, -50%)';
                    confetti.style.transition = 'all 1s ease-out';
                    confetti.style.zIndex = '9999';
                    confetti.style.borderRadius = '50%';
                    
                    document.body.appendChild(confetti);
                    
                    const angle = (Math.PI * 2 * i) / confettiCount;
                    const velocity = 100 + Math.random() * 100;
                    
                    setTimeout(() => {
                        confetti.style.left = (50 + Math.cos(angle) * velocity) + '%';
                        confetti.style.top = (30 + Math.sin(angle) * velocity) + '%';
                        confetti.style.opacity = '0';
                    }, 100);
                    
                    setTimeout(() => confetti.remove(), 1100);
                }, i * 20);
            }
        }
        
        // Initialize on page load
        window.addEventListener('load', function() {
            document.body.style.opacity = '0';
            setTimeout(() => {
                document.body.style.transition = 'opacity 0.5s ease';
                document.body.style.opacity = '1';
                
                loadProgress();
                initializeSteps();
                updateOverallProgress();
            }, 100);
        });
        
        // Save session before unload
        window.addEventListener('beforeunload', function() {
            if (document.getElementById('gameContainer').classList.contains('active')) {
                saveSession();
            }
        });
    </script>
  <script src="tracking.js"></script>
</body>
</html>

<?php include 'footer.php'; ?>