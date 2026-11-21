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
        'hero_title' => 'Kanji Master Challenge',
        'hero_subtitle' => 'Master 167 N5 Kanji in 10 Progressive Steps',
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
        'instructions' => 'Learn kanji step by step. Each step contains 16-17 kanji. Complete all questions to unlock the next step.',
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
        'hero_title' => '漢字マスターチャレンジ',
        'hero_subtitle' => '10段階でN5漢字167字をマスター',
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
        'instructions' => '段階的に漢字を学習します。各ステップには16-17字が含まれています。',
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
        'hero_title' => 'කංජි මාස්ටර් අභියෝගය',
        'hero_subtitle' => 'පියවර 10කින් N5 කංජි 167 ප්‍රගුණ කරන්න',
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
        'instructions' => 'පියවරෙන් පියවර කංජි ඉගෙන ගන්න. එක් එක් පියවරේ කංජි 16-17 ඇත.',
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
            color: #666;}
        
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
                    <div class="stat-number">167</div>
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
                    <div style="color: #666; margin-top: 0.5rem;" id="gameSubtitle">Kanji 1-17</div>
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
                <div class="question-number" id="questionNumber"><?php echo $t['question']; ?> 1/17</div>
                <div class="kanji-display japanese-text" id="kanjiDisplay">会</div>
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
        
        // Complete N5 Kanji Data (167 kanji) - only kunyomi
        const kanjiData = [
            {number: 1, character: '会', kunyomi: 'あう', meaning: 'meeting; meet', meaningSi: 'හමුවීම'},
            {number: 2, character: '同', kunyomi: 'おなじ', meaning: 'same, equal', meaningSi: 'එකම, සමාන'},
            {number: 3, character: '事', kunyomi: 'こと', meaning: 'matter, thing', meaningSi: 'කාරණය, දෙය'},
            {number: 4, character: '自', kunyomi: 'みずから', meaning: 'oneself', meaningSi: 'තමාම'},
            {number: 5, character: '社', kunyomi: 'やしろ', meaning: 'company', meaningSi: 'සමාගම'},
            {number: 6, character: '発', kunyomi: '', meaning: 'departure', meaningSi: 'පිටත්වීම'},
            {number: 7, character: '者', kunyomi: 'もの', meaning: 'person', meaningSi: 'පුද්ගලයා'},
            {number: 8, character: '地', kunyomi: '', meaning: 'ground', meaningSi: 'බිම'},
            {number: 9, character: '業', kunyomi: 'わざ', meaning: 'business', meaningSi: 'ව්‍යාපාර'},
            {number: 10, character: '方', kunyomi: 'かた', meaning: 'direction', meaningSi: 'දිශාව'},
            {number: 11, character: '新', kunyomi: 'あたらしい', meaning: 'new', meaningSi: 'අලුත්'},
            {number: 12, character: '場', kunyomi: 'ば', meaning: 'place', meaningSi: 'ස්ථානය'},
            {number: 13, character: '員', kunyomi: '', meaning: 'member', meaningSi: 'සාමාජික'},
            {number: 14, character: '立', kunyomi: 'たつ', meaning: 'stand', meaningSi: 'නැගිටිනවා'},
            {number: 15, character: '開', kunyomi: 'ひらく', meaning: 'open', meaningSi: 'අරිනවා'},
            {number: 16, character: '手', kunyomi: 'て', meaning: 'hand', meaningSi: 'අත'},
            {number: 17, character: '力', kunyomi: 'ちから', meaning: 'power', meaningSi: 'බලය'},
            {number: 18, character: '問', kunyomi: 'とう', meaning: 'question', meaningSi: 'ප්‍රශ්නය'},
            {number: 19, character: '代', kunyomi: 'かわり', meaning: 'substitute', meaningSi: 'ආදේශක'},
            {number: 20, character: '明', kunyomi: 'あかるい', meaning: 'bright', meaningSi: 'දීප්තිමත්'},
            {number: 21, character: '動', kunyomi: 'うごく', meaning: 'move', meaningSi: 'චලනය'},
            {number: 22, character: '京', kunyomi: 'みやこ', meaning: 'capital', meaningSi: 'අගනුවර'},
            {number: 23, character: '目', kunyomi: 'め', meaning: 'eye', meaningSi: 'ඇස'},
            {number: 24, character: '通', kunyomi: 'とおる', meaning: 'pass through', meaningSi: 'හරහා යනවා'},
            {number: 25, character: '言', kunyomi: 'いう', meaning: 'say', meaningSi: 'කියනවා'},
            {number: 26, character: '理', kunyomi: '', meaning: 'logic', meaningSi: 'තර්කනය'},
            {number: 27, character: '体', kunyomi: 'からだ', meaning: 'body', meaningSi: 'ශරීරය'},
            {number: 28, character: '田', kunyomi: 'た', meaning: 'rice field', meaningSi: 'වී කෙත'},
            {number: 29, character: '主', kunyomi: 'ぬし', meaning: 'master', meaningSi: 'ස්වාමියා'},
            {number: 30, character: '題', kunyomi: '', meaning: 'topic', meaningSi: 'මාතෘකාව'},
            {number: 31, character: '意', kunyomi: '', meaning: 'idea', meaningSi: 'අදහස'},
            {number: 32, character: '不', kunyomi: '', meaning: 'negative', meaningSi: 'ඍණ'},
            {number: 33, character: '作', kunyomi: 'つくる', meaning: 'make', meaningSi: 'සෑදීම'},
            {number: 34, character: '用', kunyomi: 'もちいる', meaning: 'use', meaningSi: 'භාවිතා'},
            {number: 35, character: '度', kunyomi: 'たび', meaning: 'degrees', meaningSi: 'අංශක'},
            {number: 36, character: '強', kunyomi: 'つよい', meaning: 'strong', meaningSi: 'ශක්තිමත්'},
            {number: 37, character: '公', kunyomi: '', meaning: 'public', meaningSi: 'පොදු'},
            {number: 38, character: '持', kunyomi: 'もつ', meaning: 'hold', meaningSi: 'අල්ලා ගන්නවා'},
            {number: 39, character: '野', kunyomi: 'の', meaning: 'field', meaningSi: 'තැනිතලා'},
            {number: 40, character: '以', kunyomi: 'もって', meaning: 'by means of', meaningSi: 'ඒ මගින්'},
            {number: 41, character: '思', kunyomi: 'おもう', meaning: 'think', meaningSi: 'හිතනවා'},
            {number: 42, character: '家', kunyomi: 'いえ', meaning: 'house', meaningSi: 'නිවස'},
            {number: 43, character: '世', kunyomi: 'よ', meaning: 'world', meaningSi: 'ලෝකය'},
            {number: 44, character: '多', kunyomi: 'おおい', meaning: 'many', meaningSi: 'බොහෝ'},
            {number: 45, character: '正', kunyomi: 'ただしい', meaning: 'correct', meaningSi: 'නිවැරදි'},
            {number: 46, character: '安', kunyomi: 'やすい', meaning: 'cheap', meaningSi: 'ලාභ'},
            {number: 47, character: '院', kunyomi: '', meaning: 'institution', meaningSi: 'ආයතනය'},
            {number: 48, character: '心', kunyomi: 'こころ', meaning: 'heart', meaningSi: 'හදවත'},
            {number: 49, character: '界', kunyomi: '', meaning: 'world', meaningSi: 'ලෝකය'},
            {number: 50, character: '教', kunyomi: 'おしえる', meaning: 'teach', meaningSi: 'උගන්වනවා'},
            {number: 51, character: '文', kunyomi: 'ふみ', meaning: 'sentence', meaningSi: 'වාක්‍යය'},
            {number: 52, character: '元', kunyomi: 'もと', meaning: 'origin', meaningSi: 'මූලාරම්භය'},
            {number: 53, character: '重', kunyomi: 'おもい', meaning: 'heavy', meaningSi: 'බර'},
            {number: 54, character: '近', kunyomi: 'ちかい', meaning: 'near', meaningSi: 'ළඟ'},
            {number: 55, character: '考', kunyomi: 'かんがえる', meaning: 'think', meaningSi: 'කල්පනා කරනවා'},
            {number: 56, character: '画', kunyomi: 'かく', meaning: 'picture', meaningSi: 'පින්තූරය'},
            {number: 57, character: '海', kunyomi: 'うみ', meaning: 'sea', meaningSi: 'මුහුද'},
            {number: 58, character: '売', kunyomi: 'うる', meaning: 'sell', meaningSi: 'විකුණනවා'},
            {number: 59, character: '知', kunyomi: 'しる', meaning: 'know', meaningSi: 'දන්නවා'},
            {number: 60, character: '道', kunyomi: 'みち', meaning: 'road', meaningSi: 'පාර'},
            {number: 61, character: '集', kunyomi: 'あつめる', meaning: 'gather', meaningSi: 'එකතු කරනවා'},
            {number: 62, character: '別', kunyomi: 'わかれる', meaning: 'separate', meaningSi: 'වෙන් කරනවා'},
            {number: 63, character: '物', kunyomi: 'もの', meaning: 'thing', meaningSi: 'දේ'},
            {number: 64, character: '使', kunyomi: 'つかう', meaning: 'use', meaningSi: 'භාවිතා කරනවා'},
            {number: 65, character: '品', kunyomi: 'しな', meaning: 'goods', meaningSi: 'භාණ්ඩ'},
            {number: 66, character: '計', kunyomi: 'はかる', meaning: 'plan', meaningSi: 'සැලසුම'},
            {number: 67, character: '死', kunyomi: 'しぬ', meaning: 'die', meaningSi: 'මැරෙනවා'},
            {number: 68, character: '特', kunyomi: '', meaning: 'special', meaningSi: 'විශේෂ'},
            {number: 69, character: '私', kunyomi: 'わたし', meaning: 'I, me', meaningSi: 'මම'},
            {number: 70, character: '始', kunyomi: 'はじめる', meaning: 'begin', meaningSi: 'ආරම්භ කරනවා'},
            {number: 71, character: '朝', kunyomi: 'あさ', meaning: 'morning', meaningSi: 'උදෑසන'},
            {number: 72, character: '運', kunyomi: 'はこぶ', meaning: 'carry', meaningSi: 'ගෙන යනවා'},
            {number: 73, character: '終', kunyomi: 'おわる', meaning: 'end', meaningSi: 'අවසාන කරනවා'},
            {number: 74, character: '台', kunyomi: 'うてな', meaning: 'stand', meaningSi: 'පාදම'},
            {number: 75, character: '広', kunyomi: 'ひろい', meaning: 'wide', meaningSi: 'පළල්'},
            {number: 76, character: '住', kunyomi: 'すむ', meaning: 'live', meaningSi: 'වාසය කරනවා'},
            {number: 77, character: '無', kunyomi: 'ない', meaning: 'nothing', meaningSi: 'කිසිවක් නැත'},
            {number: 78, character: '真', kunyomi: 'ま', meaning: 'true', meaningSi: 'සත්‍ය'},
            {number: 79, character: '有', kunyomi: 'ある', meaning: 'exist', meaningSi: 'තිබෙනවා'},
            {number: 80, character: '口', kunyomi: 'くち', meaning: 'mouth', meaningSi: 'කට'},
            {number: 81, character: '少', kunyomi: 'すくない', meaning: 'few', meaningSi: 'ටිකක්'},
            {number: 82, character: '町', kunyomi: 'まち', meaning: 'town', meaningSi: 'නගරය'},
            {number: 83, character: '料', kunyomi: '', meaning: 'fee', meaningSi: 'ගාස්තු'},
            {number: 84, character: '工', kunyomi: '', meaning: 'craft', meaningSi: 'අත්කම්'},
            {number: 85, character: '建', kunyomi: 'たてる', meaning: 'build', meaningSi: 'ගොඩනඟනවා'},
            {number: 86, character: '空', kunyomi: 'そら', meaning: 'sky', meaningSi: 'අහස'},
            {number: 87, character: '急', kunyomi: 'いそぐ', meaning: 'hurry', meaningSi: 'ඉක්මන් කරනවා'},
            {number: 88, character: '止', kunyomi: 'とまる', meaning: 'stop', meaningSi: 'නතර වෙනවා'},
            {number: 89, character: '送', kunyomi: 'おくる', meaning: 'send', meaningSi: 'යවනවා'},
            {number: 90, character: '切', kunyomi: 'きる', meaning: 'cut', meaningSi: 'කපනවා'},
            {number: 91, character: '転', kunyomi: 'ころがる', meaning: 'turn', meaningSi: 'කැරකෙනවා'},
            {number: 92, character: '研', kunyomi: 'とぐ', meaning: 'polish', meaningSi: 'ඔප දමනවා'},
            {number: 93, character: '足', kunyomi: 'あし', meaning: 'foot', meaningSi: 'කකුල'},
            {number: 94, character: '究', kunyomi: '', meaning: 'research', meaningSi: 'පර්යේෂණ'},
            {number: 95, character: '楽', kunyomi: 'たのしい', meaning: 'music, fun', meaningSi: 'සංගීතය'},
            {number: 96, character: '起', kunyomi: 'おきる', meaning: 'wake up', meaningSi: 'අවදි වෙනවා'},
            {number: 97, character: '着', kunyomi: 'きる', meaning: 'wear', meaningSi: 'අඳිනවා'},
            {number: 98, character: '店', kunyomi: 'みせ', meaning: 'shop', meaningSi: 'කඩය'},
            {number: 99, character: '病', kunyomi: 'やむ', meaning: 'sick', meaningSi: 'අසනීප'},
            {number: 100, character: '質', kunyomi: 'たち', meaning: 'quality', meaningSi: 'ගුණය'},
            {number: 101, character: '待', kunyomi: 'まつ', meaning: 'wait', meaningSi: 'බල සිටිනවා'},
            {number: 102, character: '試', kunyomi: 'ためす', meaning: 'test', meaningSi: 'පරීක්ෂා කරනවා'},
            {number: 103, character: '族', kunyomi: '', meaning: 'tribe', meaningSi: 'ගෝත්‍රය'},
            {number: 104, character: '銀', kunyomi: '', meaning: 'silver', meaningSi: 'රිදී'},
            {number: 105, character: '早', kunyomi: 'はやい', meaning: 'early', meaningSi: 'ඉක්මන්'},
            {number: 106, character: '映', kunyomi: 'うつる', meaning: 'reflect', meaningSi: 'පරාවර්තනය'},
            {number: 107, character: '親', kunyomi: 'おや', meaning: 'parent', meaningSi: 'දෙමව්පියන්'},
            {number: 108, character: '験', kunyomi: '', meaning: 'testing', meaningSi: 'පරීක්ෂාව'},
            {number: 109, character: '英', kunyomi: '', meaning: 'England', meaningSi: 'එංගලන්තය'},
            {number: 110, character: '医', kunyomi: '', meaning: 'doctor', meaningSi: 'වෛද්‍ය'},
            {number: 111, character: '仕', kunyomi: '', meaning: 'serve', meaningSi: 'සේවය'},
            {number: 112, character: '去', kunyomi: 'さる', meaning: 'leave', meaningSi: 'යනවා'},
            {number: 113, character: '味', kunyomi: 'あじ', meaning: 'taste', meaningSi: 'රසය'},
            {number: 114, character: '写', kunyomi: 'うつる', meaning: 'copy', meaningSi: 'පිටපත්'},
            {number: 115, character: '字', kunyomi: '', meaning: 'letter', meaningSi: 'අකුර'},
            {number: 116, character: '答', kunyomi: 'こたえる', meaning: 'answer', meaningSi: 'පිළිතුරු'},
            {number: 117, character: '夜', kunyomi: 'よる', meaning: 'night', meaningSi: 'රාත්‍රිය'},
            {number: 118, character: '音', kunyomi: 'おと', meaning: 'sound', meaningSi: 'ශබ්දය'},
            {number: 119, character: '注', kunyomi: 'そそぐ', meaning: 'pour', meaningSi: 'වත් කරනවා'},
            {number: 120, character: '帰', kunyomi: 'かえる', meaning: 'return', meaningSi: 'ආපසු යනවා'},
            {number: 121, character: '古', kunyomi: 'ふるい', meaning: 'old', meaningSi: 'පැරණි'},
            {number: 122, character: '歌', kunyomi: 'うた', meaning: 'song', meaningSi: 'ගීතය'},
            {number: 123, character: '買', kunyomi: 'かう', meaning: 'buy', meaningSi: 'මිලදී ගන්නවා'},
            {number: 124, character: '悪', kunyomi: 'わるい', meaning: 'bad', meaningSi: 'නරක'},
            {number: 125, character: '図', kunyomi: 'はかる', meaning: 'map', meaningSi: 'සිතියම'},
            {number: 126, character: '週', kunyomi: '', meaning: 'week', meaningSi: 'සතිය'},
            {number: 127, character: '室', kunyomi: 'むろ', meaning: 'room', meaningSi: 'කාමරය'},
            {number: 128, character: '歩', kunyomi: 'あるく', meaning: 'walk', meaningSi: 'ඇවිදිනවා'},
            {number: 129, character: '風', kunyomi: 'かぜ', meaning: 'wind', meaningSi: 'සුළඟ'},
            {number: 130, character: '紙', kunyomi: 'かみ', meaning: 'paper', meaningSi: 'කඩදාසි'},
            {number: 131, character: '黒', kunyomi: 'くろ', meaning: 'black', meaningSi: 'කළු'},
            {number: 132, character: '花', kunyomi: 'はな', meaning: 'flower', meaningSi: 'මල්'},
            {number: 133, character: '春', kunyomi: 'はる', meaning: 'spring', meaningSi: 'වසන්තය'},
            {number: 134, character: '赤', kunyomi: 'あかい', meaning: 'red', meaningSi: 'රතු'},
            {number: 135, character: '青', kunyomi: 'あおい', meaning: 'blue', meaningSi: 'නිල්'},
            {number: 136, character: '館', kunyomi: 'やかた', meaning: 'building', meaningSi: 'ගොඩනැගිල්ල'},
            {number: 137, character: '屋', kunyomi: 'や', meaning: 'shop', meaningSi: 'කඩය'},
            {number: 138, character: '色', kunyomi: 'いろ', meaning: 'color', meaningSi: 'වර්ණය'},
            {number: 139, character: '走', kunyomi: 'はしる', meaning: 'run', meaningSi: 'දුවනවා'},
            {number: 140, character: '秋', kunyomi: 'あき', meaning: 'autumn', meaningSi: 'සරත් සමය'},
            {number: 141, character: '夏', kunyomi: 'なつ', meaning: 'summer', meaningSi: 'ග්‍රීෂ්ම කාලය'},
            {number: 142, character: '習', kunyomi: 'ならう', meaning: 'learn', meaningSi: 'ඉගෙන ගන්නවා'},
            {number: 143, character: '駅', kunyomi: '', meaning: 'station', meaningSi: 'දුම්රිය ස්ථානය'},
            {number: 144, character: '洋', kunyomi: '', meaning: 'ocean', meaningSi: 'සාගරය'},
            {number: 145, character: '旅', kunyomi: 'たび', meaning: 'trip', meaningSi: 'ගමන'},
            {number: 146, character: '服', kunyomi: '', meaning: 'clothing', meaningSi: 'ඇඳුම්'},
            {number: 147, character: '夕', kunyomi: 'ゆう', meaning: 'evening', meaningSi: 'සවස'},
            {number: 148, character: '借', kunyomi: 'かりる', meaning: 'borrow', meaningSi: 'ණයට ගන්නවා'},
            {number: 149, character: '曜', kunyomi: '', meaning: 'weekday', meaningSi: 'සතියේ දිනය'},
            {number: 150, character: '飲', kunyomi: 'のむ', meaning: 'drink', meaningSi: 'පානය කරනවා'},
            {number: 151, character: '肉', kunyomi: '', meaning: 'meat', meaningSi: 'මස්'},
            {number: 152, character: '貸', kunyomi: 'かす', meaning: 'lend', meaningSi: 'ණයට දෙනවා'},
            {number: 153, character: '堂', kunyomi: '', meaning: 'hall', meaningSi: 'ශාලාව'},
            {number: 154, character: '鳥', kunyomi: 'とり', meaning: 'bird', meaningSi: 'කුරුල්ලා'},
            {number: 155, character: '飯', kunyomi: 'めし', meaning: 'meal', meaningSi: 'ආහාර'},
            {number: 156, character: '勉', kunyomi: 'つとめる', meaning: 'effort', meaningSi: 'වෑයම්'},
            {number: 157, character: '冬', kunyomi: 'ふゆ', meaning: 'winter', meaningSi: 'ශීත ඍතුව'},
            {number: 158, character: '昼', kunyomi: 'ひる', meaning: 'noon', meaningSi: 'දහවල්'},
            {number: 159, character: '茶', kunyomi: '', meaning: 'tea', meaningSi: 'තේ'},
            {number: 160, character: '弟', kunyomi: 'おとうと', meaning: 'younger brother', meaningSi: 'බාල සහෝදරයා'},
            {number: 161, character: '牛', kunyomi: 'うし', meaning: 'cow', meaningSi: 'ගවයා'},
            {number: 162, character: '魚', kunyomi: 'さかな', meaning: 'fish', meaningSi: 'මාළු'},
            {number: 163, character: '兄', kunyomi: 'あに', meaning: 'elder brother', meaningSi: 'වැඩිමහල් සහෝදරයා'},
            {number: 164, character: '犬', kunyomi: 'いぬ', meaning: 'dog', meaningSi: 'බල්ලා'},
            {number: 165, character: '妹', kunyomi: 'いもうと', meaning: 'younger sister', meaningSi: 'බාල සහෝදරිය'},
            {number: 166, character: '姉', kunyomi: 'あね', meaning: 'elder sister', meaningSi: 'වැඩිමහල් සහෝදරිය'},
            {number: 167, character: '漢', kunyomi: '', meaning: 'China', meaningSi: 'චීනය'}
        ];
        
        // Game state with session persistence
        let gameState = {
            currentStep: 1,
            currentQuestion: 0,
            score: 0,
            totalQuestions: 0,
            correctAnswers: 0,
            stepKanji: [],
            userProgress: {},
            completedSteps: [],
            activeSession: null,  // Track active game session
            questionOrder: [],     // Store question order
            answersGiven: []       // Store already answered questions
        };
        
        // Load progress from localStorage
        function loadProgress() {
            const saved = localStorage.getItem('kanjiGameProgress');
            if (saved) {
                gameState.userProgress = JSON.parse(saved);
            }
            
            // Load active session
            const session = localStorage.getItem('kanjiGameSession');
            if (session) {
                const sessionData = JSON.parse(session);
                // Check if session is less than 24 hours old
                const sessionAge = Date.now() - sessionData.timestamp;
                if (sessionAge < 24 * 60 * 60 * 1000) { // 24 hours
                    gameState.activeSession = sessionData;
                    resumeSession();
                } else {
                    // Clear old session
                    localStorage.removeItem('kanjiGameSession');
                }
            }
        }
        
        // Save progress to localStorage
        function saveProgress() {
            localStorage.setItem('kanjiGameProgress', JSON.stringify(gameState.userProgress));
            
            // If logged in, also save to server
            if (isLoggedIn) {
                saveToServer();
            }
        }
        
        // Save active session
        function saveSession() {
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
            localStorage.setItem('kanjiGameSession', JSON.stringify(sessionData));
        }
        
        // Resume saved session
        function resumeSession() {
            if (!gameState.activeSession) return;
            
            const session = gameState.activeSession;
            gameState.currentStep = session.currentStep;
            gameState.currentQuestion = session.currentQuestion;
            gameState.score = session.score;
            gameState.correctAnswers = session.correctAnswers;
            gameState.totalQuestions = session.totalQuestions;
            gameState.stepKanji = session.stepKanji;
            gameState.questionOrder = session.questionOrder;
            gameState.answersGiven = session.answersGiven;
            
            // Show game container
            document.getElementById('progressOverview').style.display = 'none';
            document.getElementById('gameContainer').classList.add('active');
            
            document.getElementById('gameTitle').textContent = `${translations.step} ${gameState.currentStep}`;
            if (gameState.stepKanji.length > 0) {
                document.getElementById('gameSubtitle').textContent = 
                    `Kanji ${gameState.stepKanji[0].number}-${gameState.stepKanji[gameState.stepKanji.length-1].number}`;
            }
            
            showQuestion();
            
            // Show resume notification
            showNotification('📚 Resuming your previous session...', 'info');
        }
        
        // Clear session
        function clearSession() {
            localStorage.removeItem('kanjiGameSession');
            gameState.activeSession = null;
            gameState.questionOrder = [];
            gameState.answersGiven = [];
        }
        
        // Save to server
        // Save to server (implement with your backend)
        function saveToServer() {
            // TODO: Implement AJAX call to save progress to database
            fetch('api/save_kanji_progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    userId: userId,
                    progress: gameState.userProgress
                })
            });
        }
        
        // Initialize steps
        function initializeSteps() {
            const stepsGrid = document.getElementById('stepsGrid');
            stepsGrid.innerHTML = '';
            
            // Divide 167 kanji into 10 steps (16-17 kanji each)
            const kanjiPerStep = Math.ceil(kanjiData.length / 10);
            
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
            gameState.currentStep = stepNumber;
            gameState.currentQuestion = 0;
            gameState.score = 0;
            gameState.correctAnswers = 0;
            gameState.stepKanji = stepKanji;
            gameState.totalQuestions = stepKanji.length;
            
            document.getElementById('progressOverview').style.display = 'none';
            document.getElementById('gameContainer').classList.add('active');
            
            document.getElementById('gameTitle').textContent = `${translations.step} ${stepNumber}`;
            document.getElementById('gameSubtitle').textContent = `Kanji ${stepKanji[0].number}-${stepKanji[stepKanji.length-1].number}`;
            
            showQuestion();
        }
        
        // Show current question
        function showQuestion() {
            const currentKanji = gameState.stepKanji[gameState.currentQuestion];
            
            document.getElementById('questionNumber').textContent = 
                `${translations.question} ${gameState.currentQuestion + 1}/${gameState.totalQuestions}`;
            
            document.getElementById('kanjiDisplay').textContent = currentKanji.character;
            
            // Alternate between meaning and reading questions
            const questionType = Math.random() > 0.5 ? 'meaning' : 'reading';
            
            if (questionType === 'meaning') {
                document.getElementById('questionText').textContent = translations.what_meaning;
                generateMeaningOptions(currentKanji);
            } else {
                document.getElementById('questionText').textContent = translations.what_reading;
                generateReadingOptions(currentKanji);
            }
            
            document.getElementById('feedback').classList.remove('show', 'correct', 'incorrect');
            document.getElementById('nextBtn').style.display = 'none';
            
            updateGameStats();
        }
        
        // Generate meaning options
        function generateMeaningOptions(currentKanji) {
            const options = [currentKanji.meaning];
            
            // Add 3 random wrong answers
            while (options.length < 4) {
                const randomKanji = kanjiData[Math.floor(Math.random() * kanjiData.length)];
                if (!options.includes(randomKanji.meaning)) {
                    options.push(randomKanji.meaning);
                }
            }
            
            // Shuffle options
            options.sort(() => Math.random() - 0.5);
            
            renderOptions(options, currentKanji.meaning);
        }
        
        // Generate reading options
        function generateReadingOptions(currentKanji) {
            const options = [currentKanji.kunyomi || 'N/A'];
            
            // Add 3 random wrong answers
            while (options.length < 4) {
                const randomKanji = kanjiData[Math.floor(Math.random() * kanjiData.length)];
                const reading = randomKanji.kunyomi || 'N/A';
                if (!options.includes(reading) && reading !== 'N/A') {
                    options.push(reading);
                }
            }
            
            // If current kanji has no kunyomi, use different question
            if (currentKanji.kunyomi === '') {
                generateMeaningOptions(currentKanji);
                return;
            }
            
            // Shuffle options
            options.sort(() => Math.random() - 0.5);
            
            renderOptions(options, currentKanji.kunyomi);
        }
        
        // Render answer options
        function renderOptions(options, correctAnswer) {
            const container = document.getElementById('answerOptions');
            container.innerHTML = '';
            
            options.forEach(option => {
                const optionDiv = document.createElement('div');
                optionDiv.className = 'answer-option';
                optionDiv.textContent = option;
                optionDiv.onclick = () => checkAnswer(option, correctAnswer, optionDiv);
                container.appendChild(optionDiv);
            });
        }
        
        // Check answer
        function checkAnswer(selected, correct, optionElement) {
            // Disable all options
            const allOptions = document.querySelectorAll('.answer-option');
            allOptions.forEach(opt => opt.style.pointerEvents = 'none');
            
            const feedback = document.getElementById('feedback');
            const feedbackIcon = feedback.querySelector('.feedback-icon');
            const feedbackText = feedback.querySelector('.feedback-text');
            
            if (selected === correct) {
                // Correct answer
                optionElement.classList.add('correct');
                feedback.classList.add('show', 'correct');
                feedbackIcon.textContent = '✓';
                feedbackText.textContent = translations.correct;
                
                gameState.score += 10;
                gameState.correctAnswers++;
                
                // Show confetti for correct answer
                createMiniConfetti();
            } else {
                // Wrong answer
                optionElement.classList.add('incorrect');
                feedback.classList.add('show', 'incorrect');
                feedbackIcon.textContent = '✗';
                feedbackText.textContent = translations.incorrect;
                
                // Highlight correct answer
                allOptions.forEach(opt => {
                    if (opt.textContent === correct) {
                        opt.classList.add('correct');
                    }
                });
            }
            
            updateGameStats();
            document.getElementById('nextBtn').style.display = 'inline-block';
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
            
            // Save progress
            gameState.userProgress[gameState.currentStep] = {
                completed: true,
                score: accuracy,
                date: new Date().toISOString()
            };
            saveProgress();
            
            // Show completion modal
            document.getElementById('modalTitle').textContent = translations.step_complete;
            document.getElementById('modalMessage').textContent = 
                `Great job! You've completed Step ${gameState.currentStep}.`;
            document.getElementById('finalScore').textContent = gameState.score;
            document.getElementById('finalAccuracy').textContent = accuracy + '%';
            
            // Show/hide next step button
            const nextStepBtn = document.getElementById('nextStepBtn');
            if (gameState.currentStep < 10) {
                nextStepBtn.style.display = 'inline-block';
            } else {
                nextStepBtn.style.display = 'none';
                document.getElementById('modalTitle').textContent = translations.all_complete;
                document.getElementById('modalMessage').textContent = 
                    'Amazing! You\'ve mastered all 167 N5 Kanji! 🎊';
            }
            
            document.getElementById('completionModal').classList.add('show');
            
            // Celebration confetti
            createConfetti();
        }
        
        // Update game statistics
        function updateGameStats() {
            document.getElementById('gameScore').textContent = gameState.score;
            const accuracy = gameState.totalQuestions > 0 
                ? Math.round((gameState.correctAnswers / (gameState.currentQuestion + 1)) * 100)
                : 100;
            document.getElementById('gameAccuracy').textContent = accuracy + '%';
        }
        
        // Update overall progress
        function updateOverallProgress() {
            const completedSteps = Object.values(gameState.userProgress).filter(p => p.completed).length;
            const totalMastered = completedSteps * Math.ceil(kanjiData.length / 10);
            const percentage = Math.round((totalMastered / kanjiData.length) * 100);
            
            document.getElementById('totalMastered').textContent = Math.min(totalMastered, 167);
            document.getElementById('currentStep').textContent = 
                Math.min(completedSteps + 1, 10);
            
            const avgAccuracy = Object.values(gameState.userProgress)
                .filter(p => p.completed)
                .reduce((sum, p) => sum + p.score, 0) / (completedSteps || 1);
            document.getElementById('accuracyRate').textContent = 
                Math.round(avgAccuracy) + '%';
            
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
                const kanjiPerStep = Math.ceil(kanjiData.length / 10);
                const startIndex = (nextStep - 1) * kanjiPerStep;
                const endIndex = Math.min(startIndex + kanjiPerStep, kanjiData.length);
                const stepKanji = kanjiData.slice(startIndex, endIndex);
                
                startStep(nextStep, stepKanji);
            }
        }
        
        // Create confetti effect
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
        
        // Create mini confetti for correct answers
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
                    
                    setTimeout(() => {
                        confetti.remove();
                    }, 1100);
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
    </script>
  <script src="tracking.js"></script>
</body>
</html>

<?php include 'footer.php'; ?>