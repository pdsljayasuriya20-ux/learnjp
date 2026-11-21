<?php
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Language Handling Logic
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ja', 'si'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$currentLang = $_SESSION['lang'] ?? 'en';

// Domain Configuration
$siteUrl  = 'https://learnjapanese.jp';
$pageUrl  = $siteUrl . strtok($_SERVER['REQUEST_URI'], '?');
$ogImage  = $siteUrl . '/assets/images/learnjapanese-og.jpg';

// UI Translations
$translations = [
    'en' => [
        'page_title' => 'JLPT N5 Grammar Rules (1-50) | Learn Japanese',
        'title'      => 'N5 Grammar Rules 1–50',
        'subtitle'   => 'Master essential Japanese grammar structures with examples & clear explanations.',
        'rules'      => 'Rules Total',
        'examples'   => 'Examples',
        'languages'  => 'Languages',
        'expand_all' => 'Expand All',
        'collapse'   => 'Collapse All',
        'next_page'  => 'Next 51–84',
        'desc_label' => 'Description',
        'ex_label'   => 'Examples',
        'back_n5'    => 'Back to N5'
    ],
    'ja' => [
        'page_title' => 'JLPT N5 文法ルール (1-50) | 日本語学習',
        'title'      => 'N5文法ルール 1–50',
        'subtitle'   => '例文とわかりやすい解説で、基本的な日本語の文法構造をマスターしましょう。',
        'rules'      => '文法総数',
        'examples'   => '例文',
        'languages'  => '対応言語',
        'expand_all' => 'すべて開く',
        'collapse'   => 'すべて閉じる',
        'next_page'  => '次へ 51–84',
        'desc_label' => '解説',
        'ex_label'   => '例文',
        'back_n5'    => 'N5に戻る'
    ],
    'si' => [
        'page_title' => 'JLPT N5 ව්‍යාකරණ නීති (1-50) | ජපන් ඉගෙන ගනිමු',
        'title'      => 'N5 ව්‍යාකරණ නීති 1–50',
        'subtitle'   => 'උදාහරණ සහ පැහැදිලි විස්තර සමඟ මූලික ජපන් ව්‍යාකරණ රටා ප්‍රගුණ කරන්න.',
        'rules'      => 'මුළු නීති ගණන',
        'examples'   => 'උදාහරණ',
        'languages'  => 'භාෂාවන්',
        'expand_all' => 'සියල්ල දිගහරින්න',
        'collapse'   => 'සියල්ල හකුළන්න',
        'next_page'  => 'ඊළඟ 51–84',
        'desc_label' => 'විස්තරය',
        'ex_label'   => 'උදාහරණ',
        'back_n5'    => 'N5 වෙත ආපසු'
    ]
];

// SEO Metadata
$seo = [
    'en' => [
        'title' => 'JLPT N5 Grammar Rules 1-50 | Learn Japanese Online',
        'description' => 'Master JLPT N5 grammar rules 1 to 50 with English, Japanese, and Sinhala explanations and practical example sentences.',
        'og_locale' => 'en_US'
    ],
    'ja' => [
        'title' => 'JLPT N5 文法ルール 1-50 | 日本語オンライン学習',
        'description' => 'JLPT N5の文法ルール1〜50を日本語、英語、シンハラ語の解説と豊富な例文で分かりやすく解説します。',
        'og_locale' => 'ja_JP'
    ],
    'si' => [
        'title' => 'JLPT N5 ව්‍යාකරණ නීති 1-50 | ජපන් භාෂාව ඉගෙන ගන්න',
        'description' => 'JLPT N5 විභාගය සඳහා අවශ්‍ය වන ප්‍රධාන ව්‍යාකරණ නීති 1 සිට 50 දක්වා සිංහල, ඉංග්‍රීසි සහ ජපන් පැහැදිලි කිරීම් සමඟ ඉගෙන ගන්න.',
        'og_locale' => 'si_LK'
    ]
];

$t = $translations[$currentLang];
$s = $seo[$currentLang];
$languages = ['en' => 'English', 'ja' => '日本語', 'si' => 'සිංහල'];
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#4f46e5">

    <!-- SEO Meta Tags -->
    <title><?php echo htmlspecialchars($s['title']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($s['description']); ?>"/>
    <link rel="canonical" href="<?php echo htmlspecialchars($pageUrl); ?>"/>

    <!-- hreflang Tags -->
    <link rel="alternate" hreflang="en" href="<?php echo $siteUrl; ?>/n5-grammar.php?lang=en"/>
    <link rel="alternate" hreflang="ja" href="<?php echo $siteUrl; ?>/n5-grammar.php?lang=ja"/>
    <link rel="alternate" hreflang="si" href="<?php echo $siteUrl; ?>/n5-grammar.php?lang=si"/>
    <link rel="alternate" hreflang="x-default" href="<?php echo $siteUrl; ?>/n5-grammar.php"/>

    <!-- Open Graph & Social Cards -->
    <meta property="og:title" content="<?php echo htmlspecialchars($s['title']); ?>"/>
    <meta property="og:description" content="<?php echo htmlspecialchars($s['description']); ?>"/>
    <meta property="og:type" content="article"/>
    <meta property="og:url" content="<?php echo htmlspecialchars($pageUrl); ?>"/>
    <meta property="og:image" content="<?php echo htmlspecialchars($ogImage); ?>"/>
    <meta property="og:locale" content="<?php echo $s['og_locale']; ?>"/>

    <!-- CSS & Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Noto+Sans+JP:wght@400;500;700&family=Noto+Sans+Sinhala:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>

    <style>
        :root {
            --brand-primary: #4f46e5;
            --brand-primary-hover: #4338ca;
            --bg-surface: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --radius-lg: 20px;
            --radius-md: 14px;
        }

        body {
            font-family: 'Plus Jakarta Sans', <?php echo $currentLang === 'ja' ? "'Noto Sans JP'," : ($currentLang === 'si' ? "'Noto Sans Sinhala'," : ''); ?> sans-serif;
            background-color: var(--bg-surface);
            color: var(--text-main);
            padding-bottom: 3rem;
            -webkit-tap-highlight-color: transparent;
        }

        .japanese-text { font-family: 'Noto Sans JP', sans-serif; }
        .sinhala-text { font-family: 'Noto Sans Sinhala', sans-serif; }

        /* Header Navigation */
        .app-header {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid #e2e8f0;
        }

        .lang-pill {
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.2s ease;
            background: #f1f5f9;
        }

        .lang-pill.active {
            background: var(--brand-primary);
            color: #ffffff;
        }

        /* Hero Container */
        .hero-banner {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            border-radius: var(--radius-lg);
            color: white;
            padding: 2.5rem 1.5rem;
            text-align: center;
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.25);
        }

        /* Stats Cards */
        .stat-card {
            background: var(--card-bg);
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-md);
            padding: 1rem;
            text-align: center;
            height: 100%;
        }

        .stat-card .num {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--brand-primary);
        }

        /* Controls Panel */
        .action-btn {
            border-radius: var(--radius-md);
            font-weight: 600;
            padding: 10px 18px;
            font-size: 0.9rem;
        }

        /* Grammar Cards */
        .grammar-card {
            background: var(--card-bg);
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-lg);
            margin-bottom: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
            transition: border-color 0.2s ease;
        }

        .grammar-card:hover {
            border-color: #cbd5e1;
        }

        .grammar-header {
            padding: 1.25rem 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            user-select: none;
        }

        .grammar-badge {
            width: 42px;
            height: 42px;
            background: #eef2ff;
            color: var(--brand-primary);
            font-weight: 800;
            font-size: 1.1rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .grammar-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-main);
            margin: 0;
        }

        .romaji-tag {
            font-size: 0.82rem;
            color: var(--brand-primary);
            font-weight: 600;
        }

        .toggle-arrow {
            color: var(--text-muted);
            transition: transform 0.3s ease;
            font-size: 0.9rem;
        }

        .grammar-card.active .toggle-arrow {
            transform: rotate(180deg);
        }

        /* Collapsible Body */
        .grammar-body {
            display: none;
            padding: 0 1.5rem 1.5rem 1.5rem;
            border-top: 1px solid #f1f5f9;
            background: #fafafa;
        }

        .grammar-card.active .grammar-body {
            display: block;
        }

        .desc-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-md);
            padding: 1.25rem;
            margin-top: 1.25rem;
            font-size: 0.95rem;
            line-height: 1.7;
        }

        .example-card {
            background: #ffffff;
            border-left: 4px solid var(--brand-primary);
            border-radius: 8px;
            padding: 1rem 1.25rem;
            margin-top: 0.75rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .example-jp {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .example-roma {
            font-size: 0.85rem;
            color: var(--brand-primary);
            font-style: italic;
            margin-bottom: 6px;
        }

        .example-trans {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin: 0;
        }

        .btn-brand {
            background-color: var(--brand-primary);
            color: white;
            font-weight: 600;
            border-radius: var(--radius-md);
            padding: 12px 24px;
            border: none;
            transition: background 0.2s ease;
        }

        .btn-brand:hover {
            background-color: var(--brand-primary-hover);
            color: white;
        }
    </style>
</head>
<body>

    <!-- App Header -->
    <header class="app-header py-2 mb-4">
        <div class="container d-flex align-items-center justify-content-between">
            <a href="index.php" class="fw-bold text-decoration-none fs-5 text-dark">
                Learn<span class="text-primary">Japanese</span>
            </a>
            <nav class="d-flex gap-1" aria-label="Language selection">
                <?php foreach ($languages as $code => $label): ?>
                    <a href="?lang=<?php echo $code; ?>" 
                       class="lang-pill <?php echo $currentLang === $code ? 'active' : ''; ?>">
                       <?php echo $label; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>

    <main id="main-content" class="container">

        <!-- Hero Banner -->
        <div class="hero-banner mb-4">
            <h1 class="fw-extrabold fs-2 mb-2"><?php echo $t['title']; ?></h1>
            <p class="mb-0 opacity-90 fs-6"><?php echo $t['subtitle']; ?></p>
        </div>

        <!-- Quick Stats -->
        <div class="row g-2 mb-4">
            <div class="col-4">
                <div class="stat-card">
                    <div class="num">50</div>
                    <div class="small text-muted"><?php echo $t['rules']; ?></div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-card">
                    <div class="num">150+</div>
                    <div class="small text-muted"><?php echo $t['examples']; ?></div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-card">
                    <div class="num">3</div>
                    <div class="small text-muted"><?php echo $t['languages']; ?></div>
                </div>
            </div>
        </div>

        <!-- Controls / Actions -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <a href="n5.php" class="btn btn-outline-secondary action-btn">
                <i class="fa-solid fa-arrow-left me-1"></i> <?php echo $t['back_n5']; ?>
            </a>
            <div class="d-flex gap-2">
                <button class="btn btn-primary action-btn" onclick="toggleAllGrammar(true)">
                    <i class="fa-solid fa-angles-down me-1"></i> <?php echo $t['expand_all']; ?>
                </button>
                <button class="btn btn-light border action-btn" onclick="toggleAllGrammar(false)">
                    <i class="fa-solid fa-angles-up me-1"></i> <?php echo $t['collapse']; ?>
                </button>
                <a href="n5grammar51-84.php" class="btn btn-indigo text-white action-btn" style="background-color: var(--brand-primary);">
                    <?php echo $t['next_page']; ?> <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>

        <!-- Grammar List Section -->
        <div id="grammar-list">

            <!-- Rule 1 -->
            <div class="grammar-card" id="rule-1">
                <div class="grammar-header" onclick="toggleCard('rule-1')">
                    <div class="d-flex align-items-center gap-3">
                        <div class="grammar-badge">1</div>
                        <div>
                            <h2 class="grammar-title japanese-text">ちゃいけない / じゃいけない</h2>
                            <div class="romaji-tag">cha ikenai / ja ikenai</div>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-down toggle-arrow"></i>
                </div>
                <div class="grammar-body">
                    <div class="desc-box">
                        <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-circle-info me-1"></i> <?php echo $t['desc_label']; ?></h6>
                        <?php if ($currentLang === 'ja'): ?>
                            「<strong>〜ちゃいけない</strong>」と「<strong>〜じゃいけない</strong>」は、「<strong>〜てはいけない</strong>」と「<strong>〜ではいけない</strong>」のカジュアルな口語表現で、「<strong>〜してはならない</strong>」を意味します。
                        <?php elseif ($currentLang === 'si'): ?>
                            <strong>〜ちゃいけない</strong> සහ <strong>〜じゃいけない</strong> යනු <strong>〜てはいけない</strong> හි අනියම්/කථන ජපන් ස්වරූපයන් වන අතර, <strong>"නොකළ යුතුයි"</strong> හෝ <strong>"තහනම්"</strong> යන්න අඟවයි.
                        <?php else: ?>
                            Contracted, spoken forms of <strong>〜てはいけない (te wa ikenai)</strong> meaning <strong>"must not do"</strong> or <strong>"is forbidden to do."</strong>
                        <?php endif; ?>
                    </div>
                    <div class="mt-3">
                        <h6 class="fw-bold text-secondary mb-2"><i class="fa-solid fa-align-left me-1"></i> <?php echo $t['ex_label']; ?></h6>
                        <div class="example-card">
                            <div class="example-jp japanese-text">ここに座っちゃいけないよ。</div>
                            <div class="example-roma">Koko ni suwaccha ikenai yo.</div>
                            <p class="example-trans">
                                <?php echo $currentLang === 'ja' ? 'ここに座ってはいけないよ。' : ($currentLang === 'si' ? 'ඔයාට මෙතන වාඩි වෙන්න බෑ.' : 'You must not sit here.'); ?>
                            </p>
                        </div>
                        <div class="example-card">
                            <div class="example-jp japanese-text">お酒を飲んじゃいけない。</div>
                            <div class="example-roma">Osake o nonja ikenai.</div>
                            <p class="example-trans">
                                <?php echo $currentLang === 'ja' ? 'お酒を飲んではいけない。' : ($currentLang === 'si' ? 'මත්පැන් පානය නොකළ යුතුයි.' : 'You must not drink alcohol.'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rule 2 -->
            <div class="grammar-card" id="rule-2">
                <div class="grammar-header" onclick="toggleCard('rule-2')">
                    <div class="d-flex align-items-center gap-3">
                        <div class="grammar-badge">2</div>
                        <div>
                            <h2 class="grammar-title japanese-text">だ / です</h2>
                            <div class="romaji-tag">da / desu</div>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-down toggle-arrow"></i>
                </div>
                <div class="grammar-body">
                    <div class="desc-box">
                        <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-circle-info me-1"></i> <?php echo $t['desc_label']; ?></h6>
                        <?php if ($currentLang === 'ja'): ?>
                            日本語の繋辞で、英語の「to be」（〜です、〜だ）に相当します。<strong>です</strong>は丁寧語、<strong>だ</strong>は普通体（カジュアル）です。
                        <?php elseif ($currentLang === 'si'): ?>
                            ජපන් සම්බන්ධක පදය වන අතර, <strong>です (desu)</strong> යනු විධිමත්/ආචාරශීලී ස්වරූපයයි. <strong>だ (da)</strong> යනු අවිධිමත්/සාමාන්‍ය ස්වරූපයයි.
                        <?php else: ?>
                            The Japanese copula functioning like "to be". <strong>です (desu)</strong> is polite, while <strong>だ (da)</strong> is casual/plain.
                        <?php endif; ?>
                    </div>
                    <div class="mt-3">
                        <h6 class="fw-bold text-secondary mb-2"><i class="fa-solid fa-align-left me-1"></i> <?php echo $t['ex_label']; ?></h6>
                        <div class="example-card">
                            <div class="example-jp japanese-text">私は学生です。</div>
                            <div class="example-roma">Watashi wa gakusei desu.</div>
                            <p class="example-trans">
                                <?php echo $currentLang === 'ja' ? '私は学生です。' : ($currentLang === 'si' ? 'මම ශිෂ්‍යයෙක්. (විධිමත්)' : 'I am a student. (polite)'); ?>
                            </p>
                        </div>
                        <div class="example-card">
                            <div class="example-jp japanese-text">これは本だ。</div>
                            <div class="example-roma">Kore wa hon da.</div>
                            <p class="example-trans">
                                <?php echo $currentLang === 'ja' ? 'これは本だ。' : ($currentLang === 'si' ? 'මේක පොතක්. (සාමාන්‍ය)' : 'This is a book. (casual)'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rule 3 -->
            <div class="grammar-card" id="rule-3">
                <div class="grammar-header" onclick="toggleCard('rule-3')">
                    <div class="d-flex align-items-center gap-3">
                        <div class="grammar-badge">3</div>
                        <div>
                            <h2 class="grammar-title japanese-text">だけ</h2>
                            <div class="romaji-tag">dake</div>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-down toggle-arrow"></i>
                </div>
                <div class="grammar-body">
                    <div class="desc-box">
                        <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-circle-info me-1"></i> <?php echo $t['desc_label']; ?></h6>
                        <?php if ($currentLang === 'ja'): ?>
                            限定を表す助詞で、「<strong>〜だけ</strong>」「<strong>〜のみ</strong>」という意味です。
                        <?php elseif ($currentLang === 'si'): ?>
                            සීමාවන් දැක්වීමට භාවිතා කරන අංශු පදයකි. <strong>"පමණක්"</strong> හෝ <strong>"හුදෙක්"</strong> යන අර්ථය ලබා දේ.
                        <?php else: ?>
                            Particle used to express restriction, meaning <strong>"only," "just,"</strong> or <strong>"no more than."</strong>
                        <?php endif; ?>
                    </div>
                    <div class="mt-3">
                        <h6 class="fw-bold text-secondary mb-2"><i class="fa-solid fa-align-left me-1"></i> <?php echo $t['ex_label']; ?></h6>
                        <div class="example-card">
                            <div class="example-jp japanese-text">これだけください。</div>
                            <div class="example-roma">Kore dake kudasai.</div>
                            <p class="example-trans">
                                <?php echo $currentLang === 'ja' ? 'これだけください。' : ($currentLang === 'si' ? 'කරුණාකර මෙය පමණක් දෙන්න.' : 'Please give me only this.'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rule 4 -->
            <div class="grammar-card" id="rule-4">
                <div class="grammar-header" onclick="toggleCard('rule-4')">
                    <div class="d-flex align-items-center gap-3">
                        <div class="grammar-badge">4</div>
                        <div>
                            <h2 class="grammar-title japanese-text">だろう</h2>
                            <div class="romaji-tag">darou</div>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-down toggle-arrow"></i>
                </div>
                <div class="grammar-body">
                    <div class="desc-box">
                        <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-circle-info me-1"></i> <?php echo $t['desc_label']; ?></h6>
                        <?php if ($currentLang === 'ja'): ?>
                            「でしょう」の普通体（カジュアル）で、推測や可能性（「<strong>〜だろう</strong>」）を表します。
                        <?php elseif ($currentLang === 'si'): ?>
                            <strong>でしょう</strong> හි සාමාන්‍ය/අවියෝඡ්‍ය ස්වරූපයයි. <strong>"සමහරවිට"</strong> හෝ <strong>"සිතනවා"</strong> යන අනුමාන අර්ථයන් ප්‍රකාශ කරයි.
                        <?php else: ?>
                            Casual form of **でしょう (deshou)**, expressing a guess or probability: <strong>"probably," "I think."</strong>
                        <?php endif; ?>
                    </div>
                    <div class="mt-3">
                        <h6 class="fw-bold text-secondary mb-2"><i class="fa-solid fa-align-left me-1"></i> <?php echo $t['ex_label']; ?></h6>
                        <div class="example-card">
                            <div class="example-jp japanese-text">明日は雨が降るだろう。</div>
                            <div class="example-roma">Ashita wa ame ga furu darou.</div>
                            <p class="example-trans">
                                <?php echo $currentLang === 'ja' ? '明日は雨が降るだろう。' : ($currentLang === 'si' ? 'හෙට වැස්ස වහියි කියලා හිතනවා.' : 'I think it will rain tomorrow.'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rule 5 -->
            <div class="grammar-card" id="rule-5">
                <div class="grammar-header" onclick="toggleCard('rule-5')">
                    <div class="d-flex align-items-center gap-3">
                        <div class="grammar-badge">5</div>
                        <div>
                            <h2 class="grammar-title japanese-text">で</h2>
                            <div class="romaji-tag">de</div>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-down toggle-arrow"></i>
                </div>
                <div class="grammar-body">
                    <div class="desc-box">
                        <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-circle-info me-1"></i> <?php echo $t['desc_label']; ?></h6>
                        <?php if ($currentLang === 'ja'): ?>
                            動作の場所、手段・道具、原因・理由などを示す多機能な助詞です。
                        <?php elseif ($currentLang === 'si'): ?>
                            ක්‍රියාවක් සිදුවන ස්ථානය, භාවිතා කරන මෙවලම/මාර්ගය, හෝ හේතුව සලකුණු කරන අංශු පදයකි.
                        <?php else: ?>
                            Particle marking **location of action**, **tool/means used**, or **cause/reason**.
                        <?php endif; ?>
                    </div>
                    <div class="mt-3">
                        <h6 class="fw-bold text-secondary mb-2"><i class="fa-solid fa-align-left me-1"></i> <?php echo $t['ex_label']; ?></h6>
                        <div class="example-card">
                            <div class="example-jp japanese-text">駅で友達に会いました。</div>
                            <div class="example-roma">Eki de tomodachi ni aimashita.</div>
                            <p class="example-trans">
                                <?php echo $currentLang === 'ja' ? '駅で友達に会いました。（場所）' : ($currentLang === 'si' ? 'මම දුම්රිය ස්ථානයේදී යාළුවෙක්ව හමුවුණා.' : 'I met my friend at the station.'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
<!-- Rule 6 -->
<div class="grammar-card" id="rule-6">
    <div class="grammar-header" onclick="toggleCard('rule-6')">
        <div class="d-flex align-items-center gap-3">
            <div class="grammar-badge">6</div>
            <div>
                <h2 class="grammar-title japanese-text">でも</h2>
                <div class="romaji-tag">demo</div>
            </div>
        </div>
        <i class="fa-solid fa-chevron-down toggle-arrow"></i>
    </div>
    <div class="grammar-body">
        <div class="desc-box">
            <h6 class="fw-bold text-primary mb-2">
                <i class="fa-solid fa-circle-info me-1"></i> <?php echo $t['desc_label']; ?>
            </h6>
            
            <?php if ($currentLang === 'ja'): ?>
                <p class="mb-2"><strong>でも (demo)</strong> には主に二つの用法があります：</p>
                <ol class="mb-0 ps-3">
                    <li class="mb-1">
                        <strong>接続詞として（文頭で）:</strong> 「しかし」「けれども」「それでも」という意味で、対照的な二つの文をつなぎます。 (A。でも、B。)
                    </li>
                    <li>
                        <strong>助詞として（名詞や疑問詞の後）:</strong> 「〜でも」「〜とか」などの例を示す時や、「〜であっても」という意味を表します。 (例: 名詞 + でも)
                    </li>
                </ol>
            <?php elseif ($currentLang === 'si'): ?>
                <p class="mb-2"><strong>でも (demo)</strong> හි ප්‍රධාන භාවිතයන් දෙකක් ඇත:</p>
                <ol class="mb-0 ps-3">
                    <li class="mb-1">
                        <strong>සම්බන්ධකයක් ලෙස (වාක්‍යයේ ආරම්භයේදී):</strong> එය <strong>"නමුත්," "කෙසේ වෙතත්,"</strong> හෝ <strong>"එසේ වුවද"</strong> යන අර්ථයෙන්, වෙනස් වාක්‍ය දෙකක් සම්බන්ධ කරයි. (A. Demo, B.)
                    </li>
                    <li>
                        <strong>අංශු පදයක් ලෙස (නාම පදයක් හෝ ප්‍රශ්න වචනයක් සමඟ):</strong> එය <strong>"වුවත්," "හෝ යම් දෙයක්,"</strong> හෝ <strong>"වැනි"</strong> යන අර්ථයෙන් උදාහරණයක් හෝ යෝජනාවක් ඇඟවීමට භාවිතා කරයි. (උදා: නාමපදය + でも)
                    </li>
                </ol>
            <?php else: ?>
                <p class="mb-2"><strong>でも (demo)</strong> has two primary uses:</p>
                <ol class="mb-0 ps-3">
                    <li class="mb-1">
                        <strong>As a Conjunction (at the start of a sentence):</strong> Means <strong>"but," "however,"</strong> or <strong>"nevertheless,"</strong> connecting two contrasting ideas. (A. Demo, B.)
                    </li>
                    <li>
                        <strong>As a Particle (following a Noun or Question Word):</strong> Means <strong>"even"</strong> or <strong>"or something,"</strong> used for offering suggestions or examples. (Noun + でも)
                    </li>
                </ol>
            <?php endif; ?>
        </div>

        <div class="mt-3">
            <h6 class="fw-bold text-secondary mb-2">
                <i class="fa-solid fa-align-left me-1"></i> <?php echo $t['ex_label']; ?>
            </h6>
            
            <!-- Example 1 -->
            <div class="example-card">
                <div class="example-jp japanese-text">難しいです。でも、頑張ります。</div>
                <div class="example-roma">Muzukashii desu. Demo, ganbarimasu.</div>
                <p class="example-trans">
                    <?php 
                        if ($currentLang === 'ja') echo '難しいです。でも、頑張ります。（接続詞）';
                        elseif ($currentLang === 'si') echo 'අමාරුයි. නමුත්, මම උත්සාහ කරනවා. (සම්බන්ධකය)';
                        else echo "It's difficult. But, I will do my best. (Conjunction)";
                    ?>
                </p>
            </div>

            <!-- Example 2 -->
            <div class="example-card">
                <div class="example-jp japanese-text">お茶でも飲みませんか。</div>
                <div class="example-roma">Ocha demo nomimasen ka.</div>
                <p class="example-trans">
                    <?php 
                        if ($currentLang === 'ja') echo 'お茶でも飲みませんか。（助詞 - 提案）';
                        elseif ($currentLang === 'si') echo 'තේ ටිකක් වගේ බොමුද? (අංශු පදය - යෝජනාව)';
                        else echo 'Won\'t you drink some tea (or something)? (Particle - Suggestion)';
                    ?>
                </p>
            </div>

            <!-- Example 3 -->
            <div class="example-card">
                <div class="example-jp japanese-text">誰も知りませんでした。</div>
                <div class="example-roma">Dare demo shirimansen deshita.</div>
                <p class="example-trans">
                    <?php 
                        if ($currentLang === 'ja') echo '誰も知りませんでした。';
                        elseif ($currentLang === 'si') echo 'කිසි කෙනෙක් දැන සිටියේ නැත. (අංශු පදය - "කවුරු වුවත්")';
                        else echo 'Nobody knew it. (Particle - "even who")';
                    ?>
                </p>
            </div>
        </div>
   
 <!-- Grammar Rule 15 -->
<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g15')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">15</span>
<div>
<h2 class="section-title japanese-text">ほうがい</h2>
<div class="romaji-text">hou ga ii</div>
<p class="section-subtitle">
<span class="language-content active">had better; it is better to</span>
<span class="language-content japanese-text">〜ほうがいい（助言）</span>
<span class="language-content sinhala-text">කිරීම වඩා හොඳයි; උපදෙස් දීම</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g15-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g15-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active"><strong>ほうがい (hou ga ii)</strong> is used to give advice or make suggestions, meaning <strong>"had better"</strong> or <strong>"it is better to."</strong> For affirmative advice, use the past tense form of the verb (Ta-form). For negative advice, use the present negative form (Nai-form).</span>
            <span class="language-content japanese-text"><strong>ほうがい (hou ga ii)</strong> は、助言や提案をするために使われ、「<strong>〜したほうがいい</strong>」という意味を表します。肯定的な助言には動詞のた形、否定的な助言にはない形を使用します。</span>
            <span class="language-content sinhala-text"><strong>හෝ ගා ඊ (hou ga ii)</strong> යනු උපදෙස් හෝ යෝජනා ලබා දීමට භාවිතා කරන අතර, <strong>"කිරීම වඩා හොඳයි"</strong> යන අර්ථය දරයි. ධනාත්මක උපදෙස් සඳහා ක්‍රියා පදයේ අතීත කාල (Ta-form) ස්වරූපයද, ඍණාත්මක උපදෙස් සඳහා Nai-form ස්වරූපයද භාවිතා කරයි.</span>
        </p>
        <p>
            <span class="language-content active"><strong>Structure:</strong> Verb (Ta-form) + <strong>ほうがいい</strong> / Verb (Nai-form) + <strong>ほうがいい</strong>.</span>
            <span class="language-content japanese-text"><strong>構造:</strong> 動詞（た形） + <strong>ほうがいい</strong> / 動詞（ない形） + <strong>ほうがいい</strong>。</span>
            <span class="language-content sinhala-text"><strong>ව්‍යුහය:</strong> ක්‍රියා පදය (Ta-form) + <strong>හෝ ගා ඊ</strong> / ක්‍රියා පදය (Nai-form) + <strong>හෝ ගා ඊ</strong>.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">薬を飲んだほうがいいです。</div>
            <p style="color:#8338ec;font-style:italic;">Kusuri o nonda hō ga ii desu.</p>
            <p>
                <span class="language-content active">You had better take medicine.</span>
                <span class="language-content japanese-text">薬を飲んだほうがいいです。</span>
                <span class="language-content sinhala-text">බෙහෙත් බොන එක වඩා හොඳයි.</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">無茶をしないほうがいい。</div>
            <p style="color:#8338ec;font-style:italic;">Mucha o shinai hō ga ii.</p>
            <p>
                <span class="language-content active">It's better not to do anything rash.</span>
                <span class="language-content japanese-text">無茶をしないほうがいい。</span>
                <span class="language-content sinhala-text">අපරික්ෂාකාරී ලෙස නොසිටින එක වඩා හොඳයි.</span>
            </p>
        </div>
    </div>
</div>
</div>

<!-- Grammar Rule 16 -->
<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g16')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">16</span>
<div>
<h2 class="section-title japanese-text">一番</h2>
<div class="romaji-text">ichiban</div>
<p class="section-subtitle">
<span class="language-content active">the most; number one; best</span>
<span class="language-content japanese-text">一番（最上級）</span>
<span class="language-content sinhala-text">වඩාත්ම; අංක එක; හොඳම</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g16-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g16-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active"><strong>一番 (ichiban)</strong> literally means "number one," but when used before an adjective or verb, it functions as a superlative meaning <strong>"the most," "best,"</strong> or <strong>"-est."</strong></span>
            <span class="language-content japanese-text"><strong>一番 (ichiban)</strong> は文字通り「1番」という意味ですが、形容詞や動詞の前に置くことで、「<strong>最も</strong>」「<strong>一番〜</strong>」という最上級の意味を表します。</span>
            <span class="language-content sinhala-text"><strong>ඉචිබන් (ichiban)</strong> හි අක්ෂරාර්ථය "අංක එක" යන්නයි. නමුත් විශේෂණ හෝ ක්‍රියා පදයකට පෙර භාවිතා කළ විට, එය <strong>"වඩාත්ම"</strong> හෝ <strong>"ඉහළම"</strong> යන උත්තමාර්ථය (superlative) ප්‍රකාශ කරයි.</span>
        </p>
        <p>
            <span class="language-content active"><strong>Structure:</strong> <strong>一番 + Adjective / Verb</strong> or <strong>[Category] で 一番 [Adjective]</strong>.</span>
            <span class="language-content japanese-text"><strong>構造:</strong> <strong>一番 + 形容詞 / 動詞</strong> または <strong>[範囲] で 一番 [形容詞]</strong>。</span>
            <span class="language-content sinhala-text"><strong>ව්‍යුහය:</strong> <strong>ඉචිබන් + විශේෂණ පදය / ක්‍රියා පදය</strong> හෝ <strong>[කාණ්ඩය] で ඉචිබන් [විශේෂණ පදය]</strong>.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">富士山は日本で一番高い山です。</div>
            <p style="color:#8338ec;font-style:italic;">Fujisan wa Nihon de ichiban takai yama desu.</p>
            <p>
                <span class="language-content active">Mt. Fuji is the tallest mountain in Japan.</span>
                <span class="language-content japanese-text">富士山は日本で一番高い山です。</span>
                <span class="language-content sinhala-text">ෆුජි කන්ද ජපානයේ උසම කන්දයි.</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">何のスポーツが一番好きですか。</div>
            <p style="color:#8338ec;font-style:italic;">Nan no supōtsu ga ichiban suki desu ka.</p>
            <p>
                <span class="language-content active">What sport do you like the most?</span>
                <span class="language-content japanese-text">何のスポーツが一番好きですか。</span>
                <span class="language-content sinhala-text">ඔබ වඩාත්ම කැමති මොන ක්‍රීඩාවටද?</span>
            </p>
        </div>
    </div>
</div>
</div>

<!-- Grammar Rule 17 -->
<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g17')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">17</span>
<div>
<h2 class="section-title japanese-text">一緒に</h2>
<div class="romaji-text">issho ni</div>
<p class="section-subtitle">
<span class="language-content active">together; along with</span>
<span class="language-content japanese-text">一緒に（共同）</span>
<span class="language-content sinhala-text">එකට; සමඟ</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g17-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g17-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active"><strong>一緒に (issho ni)</strong> is an adverb meaning <strong>"together"</strong> or <strong>"along with."</strong> It indicates that two or more people are doing an action together. When specifying who you are with, use the particle <strong>と (to)</strong> (e.g., [Person] と 一緒に).</span>
            <span class="language-content japanese-text"><strong>一緒に (issho ni)</strong> は「<strong>一緒に</strong>」または「<strong>共に</strong>」という意味の副詞です。複数の人が一緒に行動することを示します。特定の人と一緒に行う場合は、助詞「<strong>と</strong>」を伴います（例：[人] と 一緒に）。</span>
            <span class="language-content sinhala-text"><strong>ඉශ්ශො නි (issho ni)</strong> යනු <strong>"එකට"</strong> හෝ <strong>"එක්ව"</strong> යන අර්ථය දෙන ක්‍රියා විශේෂණයකි. පුද්ගලයන් දෙදෙනෙකු හෝ වැඩි ගණනක් එකට ක්‍රියාවක නිරත වන බව මෙයින් දැක්වේ. කවුරුන් සමඟද යන්න නිශ්චිතව දැක්වීමට <strong>と (to)</strong> අංශුව භාවිතා කරයි.</span>
        </p>
        <p>
            <span class="language-content active"><strong>Structure:</strong> Noun (Person) + <strong>と 一緒に + Verb</strong> / <strong>一緒に + Verb</strong>.</span>
            <span class="language-content japanese-text"><strong>構造:</strong> 名詞（人） + <strong>と 一緒に + 動詞</strong> / <strong>一緒に + 動詞</strong>。</span>
            <span class="language-content sinhala-text"><strong>ව්‍යුහය:</strong> නාම පදය (පුද්ගලයා) + <strong>තො ඉශ්ශො නි + ක්‍රියා පදය</strong> / <strong>ඉශ්ශො නි + ක්‍රියා පදය</strong>.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">友達と一緒に昼ご飯を食べました。</div>
            <p style="color:#8338ec;font-style:italic;">Tomodachi to issho ni hirugohan o tabemashita.</p>
            <p>
                <span class="language-content active">I ate lunch together with my friend.</span>
                <span class="language-content japanese-text">友達と一緒に昼ご飯を食べました。</span>
                <span class="language-content sinhala-text">මම යාළුවා එක්ක එකතු වෙලා දවල් කෑම කෑවා.</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">一緒に行きましょう！</div>
            <p style="color:#8338ec;font-style:italic;">Issho ni ikimashō!</p>
            <p>
                <span class="language-content active">Let's go together!</span>
                <span class="language-content japanese-text">一緒に行きましょう！</span>
                <span class="language-content sinhala-text">අපි එකට යමු!</span>
            </p>
        </div>
    </div>
</div>
</div>

<!-- Grammar Rule 18 -->
<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g18')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">18</span>
<div>
<h2 class="section-title japanese-text">いつも</h2>
<div class="romaji-text">itsumo</div>
<p class="section-subtitle">
<span class="language-content active">always; usually; constantly</span>
<span class="language-content japanese-text">いつも（頻度）</span>
<span class="language-content sinhala-text">සැමවිටම; සාමාන්‍යයෙන්</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g18-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g18-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active"><strong>いつも (itsumo)</strong> is an adverb of frequency meaning <strong>"always," "usually,"</strong> or <strong>"constantly."</strong> In negative sentences, it means "never" or "not always" depending on the context and structure.</span>
            <span class="language-content japanese-text"><strong>いつも (itsumo)</strong> は頻度を表す副詞で、「<strong>いつも</strong>」「<strong>常時</strong>」という意味です。否定文と一緒に使うと、文脈によって「いつも〜というわけではない」という意味になります。</span>
            <span class="language-content sinhala-text"><strong>ඉත්සුමො (itsumo)</strong> යනු සංඛ්‍යාතය (frequency) දක්වන ක්‍රියා විශේෂණයකි. එහි අර්ථය <strong>"සැමවිටම," "සාමාන්‍යයෙන්,"</strong> හෝ <strong>"නිරන්තරයෙන්"</strong> යන්නයි.</span>
        </p>
        <p>
            <span class="language-content active"><strong>Structure:</strong> <strong>いつも + Verb / Adjective</strong>.</span>
            <span class="language-content japanese-text"><strong>構造:</strong> <strong>いつも + 動詞 / 形容詞</strong>。</span>
            <span class="language-content sinhala-text"><strong>ව්‍යුහය:</strong> <strong>ඉත්සුමො + ක්‍රියා පදය / විශේෂණ පදය</strong>.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">彼はいつも元気です。</div>
            <p style="color:#8338ec;font-style:italic;">Kare wa itsumo genki desu.</p>
            <p>
                <span class="language-content active">He is always energetic.</span>
                <span class="language-content japanese-text">彼はいつも元気です。</span>
                <span class="language-content sinhala-text">ඔහු සැමවිටම සෞඛ්‍ය සම්පන්නව/උද්‍යෝගිමත්ව සිටී.</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">いつも何時に起きますか。</div>
            <p style="color:#8338ec;font-style:italic;">Itsumo nanji ni okimasu ka.</p>
            <p>
                <span class="language-content active">What time do you usually wake up?</span>
                <span class="language-content japanese-text">いつも何時に起きますか。</span>
                <span class="language-content sinhala-text">ඔබ සාමාන්‍යයෙන් කීයටද අවදි වන්නේ?</span>
            </p>
        </div>
    </div>
</div>
</div>

<!-- Grammar Rule 19 -->
<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g19')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">19</span>
<div>
<h2 class="section-title japanese-text">じゃない / ではない</h2>
<div class="romaji-text">janai / dewa nai</div>
<p class="section-subtitle">
<span class="language-content active">to not be (casual/plain negative)</span>
<span class="language-content japanese-text">〜ではない・じゃない（否定形）</span>
<span class="language-content sinhala-text">නොවේ; නෙමෙයි (සාමාන්‍ය ඍණාත්මක)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g19-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g19-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active"><strong>じゃない (janai)</strong> and <strong>ではない (dewa nai)</strong> are the plain negative forms of <strong>です (desu) / だ (da)</strong>, used with nouns and na-adjectives to mean <strong>"is not"</strong> or <strong>"are not."</strong> <strong>じゃない</strong> is casual and common in spoken Japanese, while <strong>ではない</strong> is more formal and used in written text.</span>
            <span class="language-content japanese-text"><strong>じゃない (janai)</strong> と <strong>ではない (dewa nai)</strong> は、名詞とな形容詞に接続する「です・だ」の普通体否定形です。「<strong>〜ではない</strong>」という意味を表します。<strong>じゃない</strong> は会話的でカジュアル、<strong>ではない</strong> は書き言葉やフォーマルな場で使われます。</span>
            <span class="language-content sinhala-text"><strong>ජනයි (janai)</strong> සහ <strong>දෙවා නයි (dewa nai)</strong> යනු නාම පද සහ na-විශේෂණ සමඟ භාවිතා වන <strong>"නොවේ"</strong> යන්න දැක්වෙන සාමාන්‍ය (plain) ඍණාත්මක ස්වරූපයයි. <strong>じゃない</strong> සංවාදයේදී සහ casual ලෙස භාවිතා වන අතර, <strong>ではない</strong> යනු වඩාත් formal/ලිඛිත ස්වරූපයයි.</span>
        </p>
        <p>
            <span class="language-content active"><strong>Structure:</strong> Noun / Na-Adjective + <strong>じゃない / ではない</strong>.</span>
            <span class="language-content japanese-text"><strong>構造:</strong> 名詞 / な形容詞 + <strong>じゃない / ではない</strong>。</span>
            <span class="language-content sinhala-text"><strong>ව්‍යුහය:</strong> නාම පදය / Na-විශේෂණය + <strong>ජනයි / දෙවා නයි</strong>.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">私は学生じゃない。</div>
            <p style="color:#8338ec;font-style:italic;">Watashi wa gakusei janai.</p>
            <p>
                <span class="language-content active">I am not a student.</span>
                <span class="language-content japanese-text">私は学生じゃない。</span>
                <span class="language-content sinhala-text">මම ශිෂ්‍යයෙක් නෙමෙයි.</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">ここは静かではない。</div>
            <p style="color:#8338ec;font-style:italic;">Koko wa shizuka dewa nai.</p>
            <p>
                <span class="language-content active">This place is not quiet.</span>
                <span class="language-content japanese-text">ここは静かではない。</span>
                <span class="language-content sinhala-text">මේ ස්ථානය නිශ්ශබ්ද නැත.</span>
            </p>
        </div>
    </div>
</div>
</div>
            <!-- Additional Rules (6 to 50 dynamically expanded following this structure) -->

        </div>

        <!-- Pagination Action -->
        <div class="text-center mt-4">
            <a href="n5grammar51-84.php" class="btn btn-brand w-100 max-w-md">
                <?php echo $t['next_page']; ?> <i class="fa-solid fa-arrow-right ms-2"></i>
            </a>
        </div>

    </main>

    <!-- Interactive Collapse Logic -->
    <script>
        function toggleCard(cardId) {
            const card = document.getElementById(cardId);
            if (card) {
                card.classList.toggle('active');
            }
        }

        function toggleAllGrammar(expand) {
            const cards = document.querySelectorAll('.grammar-card');
            cards.forEach(card => {
                if (expand) {
                    card.classList.add('active');
                } else {
                    card.classList.remove('active');
                }
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>