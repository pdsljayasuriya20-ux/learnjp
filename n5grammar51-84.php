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
        'page_title' => 'N5 Grammar Rules 51-84 - Learn Japanese',
        'hero_title' => 'N5 Grammar Rules 51-84',
        'hero_subtitle' => 'Master Basic Japanese Grammar',
        'back_btn' => 'Back to N5'
    ],
    'ja' => [
        'page_title' => 'N5文法ルール51-84 - 日本語を学ぶ',
        'hero_title' => 'N5文法ルール51-84',
        'hero_subtitle' => '基本的な日本語文法をマスターする',
        'back_btn' => 'N5に戻る'
    ],
    'si' => [
        'page_title' => 'N5 ව්‍යාකරණ නීති 51-84 - ජපන් ඉගෙන ගන්න',
        'hero_title' => 'N5 ව්‍යාකරණ නීති 51-84',
        'hero_subtitle' => 'මූලික ජපන් ව්‍යාකරණය ප්‍රගුණ කරන්න',
        'back_btn' => 'N5 වෙත ආපසු'
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
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #06ffa5, #00cc88);
            --danger-gradient: linear-gradient(135deg, #f5365c, #e11d48);
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
        
        .character-section {
            background: white;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .character-section:hover {
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
        }
        
        .toggle-header {
            padding: 1.5rem 2rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
        }
        
        .toggle-header:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        }
        
        .grammar-number {
            font-size: 2.5rem;
            font-weight: 900;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            min-width: 60px;
        }
        
        .section-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.25rem;
        }
        
        .romaji-text {
            font-size: 0.9rem;
            color: #8338ec;
            font-style: italic;
            margin-bottom: 0.5rem;
        }
        
        .section-subtitle {
            color: #666;
            margin-bottom: 0;
            font-size: 0.95rem;
        }
        
        .toggle-icon {
            font-size: 1.5rem;
            color: #667eea;
            transition: transform 0.3s ease;
            min-width: 30px;
            text-align: center;
        }
        
        .toggle-icon.collapsed {
            transform: rotate(-90deg);
        }
        
        .grammar-content {
            max-height: 3000px;
            overflow: hidden;
            transition: max-height 0.5s ease, padding 0.5s ease;
            padding: 0 2rem 2rem 2rem;
        }
        
        .grammar-content.collapsed {
            max-height: 0;
            padding: 0 2rem;
        }
        
        .grammar-description {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.03), rgba(118, 75, 162, 0.03));
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #667eea;
        }
        
        .grammar-description h3 {
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .grammar-description p {
            line-height: 1.8;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .grammar-description strong {
            color: #667eea;
        }
        
        .pronunciation-guide {
            background: white;
            border-radius: 15px;
        }
        
        .pronunciation-guide h3 {
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .sound-group {
            background: linear-gradient(135deg, rgba(6, 255, 165, 0.05), rgba(58, 134, 255, 0.05));
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            border-left: 4px solid #06ffa5;
            transition: all 0.3s ease;
        }
        
        .sound-group:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.15);
        }
        
        .sound-group p {
            margin: 0.5rem 0;
            line-height: 1.6;
        }
        
        .language-content {
            display: none;
        }
        
        .language-content.active {
            display: inline;
        }
        
        .btn-gradient {
            background: linear-gradient(45deg, #ff006e, #8338ec);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(255,0,110,0.4);
            color: white;
        }
        
        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
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
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(45deg, #06ffa5, #3a86ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        @media (max-width: 768px) {
            .toggle-header {
                padding: 1rem 1.5rem;
            }
            
            .grammar-content {
                padding: 0 1.5rem 1.5rem 1.5rem;
            }
            
            .grammar-content.collapsed {
                padding: 0 1.5rem;
            }
            
            .grammar-number {
                font-size: 2rem;
                min-width: 50px;
            }
            
            .section-title {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
       
        
        <!-- Hero Card -->
        <div class="hero-card">
            <h1 class="hero-title"><?php echo $t['hero_title']; ?></h1>
            <p class="lead"><?php echo $t['hero_subtitle']; ?></p>
        </div>
        
        <!-- Stats Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stat-number">84</div>
                    <div class="text-muted">Grammar Rules</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stat-number">150+</div>
                    <div class="text-muted">Example Sentences</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stat-number">3</div>
                    <div class="text-muted">Languages</div>
                </div>
            </div>
        </div>
        


        <!-- 🔹 Navigation Buttons -->
<div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
    <!-- Back 10–20 -->
   <button class="btn btn-info btn-lg" onclick="goToPage('n5grammar1-50.php')">
        Back 1-50
    </button>
    <!-- Expand All -->
    <button class="btn btn-primary btn-lg" onclick="expandAllGrammar()">
        Expand All
    </button>

    <!-- Collapse All -->
    <button class="btn btn-danger btn-lg" onclick="collapseAllGrammar()">
        Collapse All
    </button>

   

    <!-- Next 20–30 -->
   
</div>
       <div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g51')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">51</span>
<div>
<h2 class="section-title japanese-text">に / へ</h2>
<div class="romaji-text">ni / e</div>
<p class="section-subtitle">
<span class="language-content active">to (indicates direction / destination)</span>
<span class="language-content japanese-text">〜に / 〜へ（方向・目的地）</span>
<span class="language-content sinhala-text">ට (දිශාව / ගමනාන්තය දක්වයි)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g51-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g51-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">Both particles **に (ni)** and **へ (e)** (pronounced *e*) are used to mark the destination or direction of movement when combined with motion verbs like **行く (iku - go)**, **来る (kuru - come)**, and **帰る (kaeru - return)**.
            <br>• **に (ni)** generally emphasizes the **destination** (the specific goal or end-point).
            <br>• **へ (e)** generally emphasizes the **direction** (the path taken towards a location), though in many contexts, they are interchangeable to indicate the destination.</span>
            <span class="language-content japanese-text">助詞の **に (ni)** と **へ (e)**（発音は *e*）はどちらも、**行く (iku - go)**、**来る (kuru - come)**、**帰る (kaeru - return)** などの移動動詞と組み合わせて、移動の目的地または方向を示すために使われます。
            <br>• **に (ni)** は通常、**目的地**（特定の目標または終点）を強調します。
            <br>• **へ (e)** は通常、**方向**（場所に向かう経路）を強調しますが、多くの場合、目的地を示すために互換性があります。</span>
            <span class="language-content sinhala-text">**නි (ni)** සහ **ඒ (e)** (උච්චාරණය *e*) යන අංශු දෙකම භාවිතා කරනු ලබන්නේ **ඉකු (iku - go)**, **කුරු (kuru - come)**, සහ **කාඑරු (kaeru - return)** වැනි චලන ක්‍රියා පද සමඟ සංයෝජනය වන විට චලනයේ ගමනාන්තය හෝ දිශාව සලකුණු කිරීමට ය.
            <br>• **නි (ni)** සාමාන්‍යයෙන් **ගමනාන්තය** (නිශ්චිත ඉලක්කය හෝ අවසාන ලක්ෂ්‍යය) අවධාරණය කරයි.
            <br>• **ඒ (e)** සාමාන්‍යයෙන් **දිශාව** (ස්ථානයකට යන මාර්ගය) අවධාරණය කරයි, නමුත් බොහෝ සන්දර්භයන්හිදී, ඒවා ගමනාන්තය දැක්වීමට එකිනෙකට ආදේශ කළ හැකිය.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Noun (Destination) + **に / へ** + Movement Verb.</span>
            <span class="language-content japanese-text">**構造:** 名詞（目的地） + **に / へ** + 移動動詞。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** නාම පදය (ගමනාන්තය) + **නි / ඒ** + චලන ක්‍රියා පදය.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">学校に行きます。 / 学校へ行きます。</div>
            <p style="color:#8338ec;font-style:italic;">Gakkō ni ikimasu. / Gakkō e ikimasu.</p>
            <p>
                <span class="language-content active">I am going to school.</span>
                <span class="language-content japanese-text">学校に行きます。 / 学校へ行きます。</span>
                <span class="language-content sinhala-text">මම පාසලට යනවා.</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">東へ進んでください。</div>
            <p style="color:#8338ec;font-style:italic;">Higashi e susunde kudasai.</p>
            <p>
                <span class="language-content active">Please proceed eastward. (Emphasis on direction)</span>
                <span class="language-content japanese-text">東へ進んでください。</span>
                <span class="language-content sinhala-text">කරුණාකර නැගෙනහිරට යන්න.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g52')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">52</span>
<div>
<h2 class="section-title japanese-text">の</h2>
<div class="romaji-text">no</div>
<p class="section-subtitle">
<span class="language-content active">possessive particle; nominalizer; explanation marker</span>
<span class="language-content japanese-text">〜の（所有・名詞化・説明）</span>
<span class="language-content sinhala-text">අයිතිය දැක්වෙන අංශුව; නාම පදීකරණය; පැහැදිලි කිරීමේ සලකුණ</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g52-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g52-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">The particle **の (no)** is highly versatile with three main functions:
            <br>1. **Possessive:** Connects a possessor and an owned item, meaning **" 's "** or **"of"** (Noun 1 + **の** + Noun 2).
            <br>2. **Nominalizer:** Turns a verb or adjective into a **noun phrase**, allowing it to be modified by other particles or used as a subject/object.
            <br>3. **Modification/Equivalence:** Connects two nouns, where the first noun clarifies or defines the second noun (e.g., the *kind* of thing).
            </span>
            <span class="language-content japanese-text">助詞の **の (no)** には主に3つの機能があります。
            <br>1. **所有:** 所有者と所有物を結びつけ、**「〜の」** または **「〜に属する」** という意味を表します（名詞 1 + **の** + 名詞 2）。
            <br>2. **名詞化:** 動詞や形容詞を**名詞句**に変え、他の助詞で修飾されたり、主語や目的語として使われたりできるようにします。
            <br>3. **修飾・同格:** 2つの名詞を結びつけ、最初の名詞が2番目の名詞を明確にしたり定義したりします（例: *種類の*もの）。</span>
            <span class="language-content sinhala-text">**නො (no)** යන අංශුව ඉතා බහුකාර්ය වන අතර ප්‍රධාන කාර්යයන් තුනක් ඇත:
            <br>1. **අයිතිය:** හිමිකරුවෙකු සහ අයිති දෙයක් සම්බන්ධ කරයි, එහි තේරුම **"'s"** හෝ **"හි"** යන්නයි (නාම පදය 1 + **නො** + නාම පදය 2).
            <br>2. **නාම පදීකරණය:** ක්‍රියා පදයක් හෝ විශේෂණ පදයක් **නාම පද වාක්‍ය ඛණ්ඩයක්** බවට පත් කරයි, එමඟින් එයට වෙනත් අංශු වලින් වෙනස් වීමට හෝ කර්තෘ/වස්තුව ලෙස භාවිතා කිරීමට ඉඩ සලසයි.
            <br>3. **වෙනස් කිරීම/සමානතාවය:** නාම පද දෙකක් සම්බන්ධ කරයි, එහිදී පළමු නාම පදය දෙවන නාම පදය පැහැදිලි කරයි හෝ නිර්වචනය කරයි (උදා: *වර්ගයේ* දෙය).</span>
        </p>
        <p>
            <span class="language-content active">**Structure (Possessive):** Noun 1 + **の** + Noun 2.</span>
            <span class="language-content japanese-text">**構造（所有）:** 名詞 1 + **の** + 名詞 2。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය (අයිතිය):** නාම පදය 1 + **නො** + නාම පදය 2.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">私のかばんはこれです。</div>
            <p style="color:#8338ec;font-style:italic;">Watashi no kaban wa kore desu. (Possessive)</span>
            <p>
                <span class="language-content active">This is my bag.</span>
                <span class="language-content japanese-text">私のかばんはこれです。</span>
                <span class="language-content sinhala-text">මේක මගේ බෑගය.</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">新聞を読むのが好きだ。</div>
            <p style="color:#8338ec;font-style:italic;">Shinbun o yomu no ga suki da. (Nominalizer: The act of reading newspapers)</span>
            <p>
                <span class="language-content active">I like reading newspapers.</span>
                <span class="language-content japanese-text">新聞を読むのが好きだ。</span>
                <span class="language-content sinhala-text">මම පත්තර කියවන්න කැමතියි.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g53')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">53</span>
<div>
<h2 class="section-title japanese-text">のです</h2>
<div class="romaji-text">no desu</div>
<p class="section-subtitle">
<span class="language-content active">to explain something; show emphasis (formal)</span>
<span class="language-content japanese-text">〜のです（説明・強調・正式）</span>
<span class="language-content sinhala-text">යමක් පැහැදිලි කිරීමට; අවධාරණය පෙන්වීමට (විධිමත්)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g53-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g53-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">**のです (no desu)** is a formal expression used to **explain a situation, give a reason, or provide background information** for a preceding statement. It adds an explanatory or emphatic tone, linking the sentence to a context or underlying cause. It is the formal version of **んです (n desu)**.</span>
            <span class="language-content japanese-text">**のです (no desu)** は、先行する発言に対して**状況を説明し、理由を与え、または背景情報を提供する**ために使われる正式な表現です。文脈や根底にある原因に文を結びつけ、説明的または強調的なトーンを加えます。これは **んです (n desu)** の正式なバージョンです。</span>
            <span class="language-content sinhala-text">**නො දේසු (no desu)** යනු පෙර ප්‍රකාශයක් සඳහා **තත්වයක් පැහැදිලි කිරීමට, හේතුවක් දීමට හෝ පසුබිම් තොරතුරු සැපයීමට** භාවිතා කරන විධිමත් ප්‍රකාශනයකි. එය වාක්‍යයට පැහැදිලි කිරීමේ හෝ අවධාරණාත්මක ස්වරයක් එක් කරයි, වාක්‍යය සන්දර්භයක් හෝ යටින් පවතින හේතුවක් සමඟ සම්බන්ධ කරයි. එය **න් දේසු (n desu)** හි විධිමත් අනුවාදයයි.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Verb (Plain) / I-Adjective (Plain) / Na-Adjective Stem + **な** + **のです** / Noun + **な** + **のです**.</span>
            <span class="language-content japanese-text">**構造:** 動詞（普通形） / い形容詞（普通形） / な形容詞の語幹 + **な** + **のです** / 名詞 + **な** + **のです**。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** ක්‍රියා පදය (සරල) / I-විශේෂණ පදය (සරල) / නා-විශේෂණ පදයේ කඳ + **නා** + **නො දේසු** / නාම පදය + **නා** + **නො දේසු**.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">体調が悪いのですか。</div>
            <p style="color:#8338ec;font-style:italic;">Taichō ga warui no desu ka.</p>
            <p>
                <span class="language-content active">Is it that you are unwell? (Seeking explanation/concern)</span>
                <span class="language-content japanese-text">体調が悪いのですか。</span>
                <span class="language-content sinhala-text">ඔබේ සෞඛ්‍ය තත්ත්වය හොඳ නැද්ද?</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">明日引っ越しをする予定なのです。</div>
            <p style="color:#8338ec;font-style:italic;">Ashita hikkoshi o suru yotei na no desu.</p>
            <p>
                <span class="language-content active">The situation is that I have a plan to move tomorrow. (Providing background)</span>
                <span class="language-content japanese-text">明日引っ越しをする予定なのです。</span>
                <span class="language-content sinhala-text">තත්වය නම් මට හෙට ගෙයක් මාරු කිරීමට සැලසුමක් තිබීමයි.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g54')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">54</span>
<div>
<h2 class="section-title japanese-text">のが下手</h2>
<div class="romaji-text">no ga heta</div>
<p class="section-subtitle">
<span class="language-content active">to be bad at doing something</span>
<span class="language-content japanese-text">〜のが下手（苦手）</span>
<span class="language-content sinhala-text">යමක් කිරීමේ නරක වීම (නොදක්ෂ)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g54-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g54-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">The pattern **〜のが下手 (no ga heta)** is used to express a lack of skill or ability in a particular action. **下手 (heta)** is a na-adjective meaning **"unskillful"** or **"bad at,"** and it modifies the preceding action which has been nominalized by **の (no)**. The particle **が (ga)** marks the nominalized action as the subject of the adjective.</span>
            <span class="language-content japanese-text">**〜のが下手 (no ga heta)** のパターンは、特定の動作におけるスキルや能力の欠如を表現するために使われます。**下手 (heta)** は「**不器用な**」または「**苦手な**」という意味のな形容詞であり、**の (no)** によって名詞化された先行する動作を修飾します。助詞の **が (ga)** は、名詞化された動作を形容詞の主語として示します。</span>
            <span class="language-content sinhala-text">**〜නො ගා හෙතා (no ga heta)** යන රටාව භාවිතා කරනු ලබන්නේ යම් නිශ්චිත ක්‍රියාවක ඇති කුසලතා හෝ හැකියාවන් නොමැති බව ප්‍රකාශ කිරීමට ය. **හෙතා (heta)** යනු **"නොදක්ෂ"** හෝ **"නරක"** යන අර්ථය ඇති නා-විශේෂණ පදයක් වන අතර, එය **නො (no)** මගින් නාම පදීකරණය කරන ලද පෙර ක්‍රියාව වෙනස් කරයි. **ගා (ga)** යන අංශුව නාම පදීකරණය කරන ලද ක්‍රියාව විශේෂණ පදයේ කර්තෘ ලෙස සලකුණු කරයි.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Verb Plain form + **の** + **が** + **下手だ** / **下手です**.</span>
            <span class="language-content japanese-text">**構造:** 動詞の普通形 + **の** + **が** + **下手だ** / **下手です**。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** ක්‍රියා පදයේ සරල ස්වරූපය + **නො** + **ගා** + **හෙතා දා** / **හෙතා දේසු**.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">私は歌を歌うのが下手です。</div>
            <p style="color:#8338ec;font-style:italic;">Watashi wa uta o utau no ga heta desu.</p>
            <p>
                <span class="language-content active">I am bad at singing songs.</span>
                <span class="language-content japanese-text">私は歌を歌うのが下手です。</span>
                <span class="language-content sinhala-text">මම ගීත ගායනා කිරීමට නරකයි.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g55')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">55</span>
<div>
<h2 class="section-title japanese-text">のが上手</h2>
<div class="romaji-text">no ga jōzu</div>
<p class="section-subtitle">
<span class="language-content active">to be good at doing something</span>
<span class="language-content japanese-text">〜のが上手（得意）</span>
<span class="language-content sinhala-text">යමක් කිරීමේ දක්ෂ වීම</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g55-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g55-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">The pattern **〜のが上手 (no ga jōzu)** is used to express skill or ability in a particular action. **上手 (jōzu)** is a na-adjective meaning **"skillful"** or **"good at,"** and it modifies the preceding action which has been nominalized by **の (no)**. The particle **が (ga)** marks the nominalized action as the subject of the adjective. **Note:** In polite language, **上手 (jōzu)** is typically *not* used to describe the speaker's own abilities (instead, **得意 (tokui)** is often used).</span>
            <span class="language-content japanese-text">**〜のが上手 (no ga jōzu)** のパターンは、特定の動作におけるスキルや能力を表現するために使われます。**上手 (jōzu)** は「**上手な**」または「**得意な**」という意味のな形容詞であり、**の (no)** によって名詞化された先行する動作を修飾します。助詞の **が (ga)** は、名詞化された動作を形容詞の主語として示します。**注意:** 丁寧な話し方では、**上手 (jōzu)** は通常、話し手自身の能力を説明するために*使われません*（代わりに **得意 (tokui)** がよく使われます）。</span>
            <span class="language-content sinhala-text">**〜නො ගා ජෝසු (no ga jōzu)** යන රටාව භාවිතා කරනු ලබන්නේ යම් නිශ්චිත ක්‍රියාවක ඇති කුසලතාවය හෝ හැකියාව ප්‍රකාශ කිරීමට ය. **ජෝසු (jōzu)** යනු **"දක්ෂ"** හෝ **"හොඳ"** යන අර්ථය ඇති නා-විශේෂණ පදයක් වන අතර, එය **නො (no)** මගින් නාම පදීකරණය කරන ලද පෙර ක්‍රියාව වෙනස් කරයි. **ගා (ga)** යන අංශුව නාම පදීකරණය කරන ලද ක්‍රියාව විශේෂණ පදයේ කර්තෘ ලෙස සලකුණු කරයි. **සටහන:** ආචාරශීලී භාෂාවෙන්, **ජෝසු (jōzu)** සාමාන්‍යයෙන් කථිකයාගේම හැකියාවන් විස්තර කිරීමට *භාවිතා නොවේ* (ඒ වෙනුවට **තොකුයි (tokui)** බොහෝ විට භාවිතා වේ).</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Verb Plain form + **の** + **が** + **上手だ** / **上手です**.</span>
            <span class="language-content japanese-text">**構造:** 動詞の普通形 + **の** + **が** + **上手だ** / **上手です**。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** ක්‍රියා පදයේ සරල ස්වරූපය + **නො** + **ගා** + **ජෝසු දා** / **ජෝසු දේසු**.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">彼は絵を描くのが上手です。</div>
            <p style="color:#8338ec;font-style:italic;">Kare wa e o kaku no ga jōzu desu.</p>
            <p>
                <span class="language-content active">He is good at drawing pictures.</span>
                <span class="language-content japanese-text">彼は絵を描くのが上手です。</span>
                <span class="language-content sinhala-text">ඔහු චිත්‍ර ඇඳීමට දක්ෂයි.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g56')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">56</span>
<div>
<h2 class="section-title japanese-text">のが好き</h2>
<div class="romaji-text">no ga suki</div>
<p class="section-subtitle">
<span class="language-content active">to like doing something</span>
<span class="language-content japanese-text">〜のが好き（動作の好意）</span>
<span class="language-content sinhala-text">යමක් කිරීමට කැමති වීම (ක්‍රියාවට ඇති කැමැත්ත)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g56-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g56-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">The pattern **〜のが好き (no ga suki)** is used to express that the speaker **likes the action** of the preceding verb. The verb is in its plain form, followed by the nominalizer **の (no)**, which turns the entire phrase into a noun representing the *act* of doing the action. The particle **が (ga)** marks this nominalized action as the thing that is **好き (suki - liked)**.</span>
            <span class="language-content japanese-text">**〜のが好き (no ga suki)** のパターンは、話し手が先行する動詞の**動作を好む**ことを表現するために使われます。動詞は普通形であり、その後に名詞化の **の (no)** が続き、フレーズ全体がその動作を**行う行為**を表す名詞になります。助詞の **が (ga)** は、この名詞化された動作を**好き (suki - 好きなもの)** として示します。</span>
            <span class="language-content sinhala-text">**〜නො ගා සුකි (no ga suki)** යන රටාව භාවිතා කරනු ලබන්නේ කථිකයා පෙර ක්‍රියා පදයේ **ක්‍රියාවට කැමති** බව ප්‍රකාශ කිරීමට ය. ක්‍රියා පදය සරල ස්වරූපයෙන් ඇති අතර, එයට නාම පදීකරණය වන **නො (no)** අනුගමනය කරයි, එමඟින් සම්පූර්ණ වාක්‍ය ඛණ්ඩයම ක්‍රියාව කිරීමේ *ක්‍රියාව* නියෝජනය කරන නාම පදයක් බවට පත් කරයි. **ගා (ga)** යන අංශුව මෙම නාම පදීකරණය කරන ලද ක්‍රියාව **සුකි (suki - කැමති)** දෙය ලෙස සලකුණු කරයි.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Verb Plain form + **の** + **が** + **好きだ** / **好きです**.</span>
            <span class="language-content japanese-text">**構造:** 動詞の普通形 + **の** + **が** + **好きだ** / **好きです**。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** ක්‍රියා පදයේ සරල ස්වරූපය + **නො** + **ගා** + **සුකි දා** / **සුකි දේසු**.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">私は本を読むのが好きです。</div>
            <p style="color:#8338ec;font-style:italic;">Watashi wa hon o yomu no ga suki desu.</p>
            <p>
                <span class="language-content active">I like reading books.</span>
                <span class="language-content japanese-text">私は本を読むのが好きです。</span>
                <span class="language-content sinhala-text">මම පොත් කියවීමට කැමතියි.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g57')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">57</span>
<div>
<h2 class="section-title japanese-text">の中で[A]が一番</h2>
<div class="romaji-text">no naka de [A] ga ichiban</div>
<p class="section-subtitle">
<span class="language-content active">out of this group, [A] is best/most/number one</span>
<span class="language-content japanese-text">〜の中で[A]が一番（最上級）</span>
<span class="language-content sinhala-text">මේ සමූහයෙන්, [A] වඩාත් හොඳම/වැඩිම/එක (උසස්ම)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g57-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g57-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">The pattern **〜の中で[A]が一番 (no naka de [A] ga ichiban)** is used to form the **superlative degree**, indicating that an item [A] is the most/best/number one within a specific group or category. **〜の中で (no naka de)** translates to **"among"** or **"out of"** the group, and **一番 (ichiban)** means **"number one"** or **"most."**</span>
            <span class="language-content japanese-text">**〜の中で[A]が一番 (no naka de [A] ga ichiban)** のパターンは、特定のグループやカテゴリー内で項目[A]が最も/一番/最高であることを示す**最上級**を形成するために使われます。**〜の中で (no naka de)** はグループ**「の中で」** を意味し、**一番 (ichiban)** は**「一番」** または **「最も」** を意味します。</span>
            <span class="language-content sinhala-text">**〜නො නකා දෙ [A] ගා ඉචිබන් (no naka de [A] ga ichiban)** යන රටාව භාවිතා කරනු ලබන්නේ **උසස්ම උපාධිය** සෑදීමට වන අතර, එයින් පෙන්නුම් කරන්නේ [A] යනු නිශ්චිත සමූහයක් හෝ කාණ්ඩයක් තුළ වඩාත්/හොඳම/එකක් බවයි. **〜නො නකා දෙ (no naka de)** සමූහය **"අතරින්"** හෝ **"තුළින්"** ලෙස පරිවර්තනය වන අතර, **ඉචිබන් (ichiban)** යනු **"එක"** හෝ **"වඩාත්"** යන්නයි.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Noun (Group) + **の** + **中で** + Noun (A) + **が** + **一番** + Adjective/Verb.</span>
            <span class="language-content japanese-text">**構造:** 名詞（グループ） + **の** + **中で** + 名詞（A） + **が** + **一番** + 形容詞/動詞。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** නාම පදය (සමූහය) + **නො** + **නකා දෙ** + නාම පදය (A) + **ගා** + **ඉචිබන්** + විශේෂණ පදය/ක්‍රියා පදය.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">日本の食べ物の中で寿司が一番好きです。</div>
            <p style="color:#8338ec;font-style:italic;">Nihon no tabemono no naka de sushi ga ichiban suki desu.</p>
            <p>
                <span class="language-content active">Among Japanese foods, I like sushi the most.</span>
                <span class="language-content japanese-text">日本の食べ物の中で寿司が一番好きです。</span>
                <span class="language-content sinhala-text">ජපන් කෑම අතරින් මම සුෂී වලට වඩාත් කැමතියි.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g58')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">58</span>
<div>
<h2 class="section-title japanese-text">ので</h2>
<div class="romaji-text">node</div>
<p class="section-subtitle">
<span class="language-content active">because of; given that; since ~ (reason/cause)</span>
<span class="language-content japanese-text">〜ので（理由・原因・よりソフト）</span>
<span class="language-content sinhala-text">නිසා; දෙන ලද; පටන් ~ (හේතුව/කාරණය, වඩා මෘදු)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g58-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g58-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">**ので (node)** is a conjunctive particle that indicates a **reason or cause**, meaning **"because,"** **"since,"** or **"as."** It is generally considered **softer and more polite** than **から (kara)**, often used when giving a reason for an action or an excuse. It follows the plain form of the verb, adjective, or copula (**な** is added before **ので** for na-adjectives and nouns).</span>
            <span class="language-content japanese-text">**ので (node)** は、**理由や原因**を示す接続助詞で、**「〜なので」**、**「〜だから」** という意味になります。一般的に **から (kara)** よりも**柔らかく、丁寧である**と考えられており、行動の理由や言い訳をするときによく使われます。動詞、形容詞、または繋辞の普通形に続きます（な形容詞と名詞の前には **な** が付きます）。</span>
            <span class="language-content sinhala-text">**නොදෙ (node)** යනු **හේතුවක් හෝ කාරණයක්** දක්වන සම්බන්ධක අංශුවක් වන අතර, එහි තේරුම **"නිසා,"** **"පටන්,"** හෝ **"වශයෙන්"** යන්නයි. එය සාමාන්‍යයෙන් **කාරා (kara)** ට වඩා **මෘදු හා ආචාරශීලී** ලෙස සලකනු ලබන අතර, ක්‍රියාවක් සඳහා හේතුවක් හෝ නිදහසට කරුණක් දෙන විට බොහෝ විට භාවිතා වේ. එය ක්‍රියා පදයේ, විශේෂණ පදයේ හෝ සම්බන්ධක පදයේ සරල ස්වරූපය අනුගමනය කරයි (නා-විශේෂණ පද සහ නාම පද සඳහා **නොදෙ** ට පෙර **නා** එකතු වේ).</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Clause 1 (Plain form + **な** for Na-adjectives/Nouns) + **ので**, Clause 2 (Result).</span>
            <span class="language-content japanese-text">**構造:** 節 1（普通形 + な形容詞/名詞には**な**） + **ので**、 節 2（結果）。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** වගන්තිය 1 (සරල ස්වරූපය + නා-විශේෂණ පද/නාම පද සඳහා **නා**) + **නොදෙ**، වගන්තිය 2 (ප්‍රතිඵලය).</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">雨が降っているので、タクシーで帰ります。</div>
            <p style="color:#8338ec;font-style:italic;">Ame ga futte iru node, takushī de kaerimasu.</p>
            <p>
                <span class="language-content active">Because it's raining (soft excuse/reason), I'm going home by taxi.</span>
                <span class="language-content japanese-text">雨が降っているので、タクシーで帰ります。</span>
                <span class="language-content sinhala-text">වැස්ස වටින නිසා, මම ටැක්සියෙන් ගෙදර යනවා.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g59')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">59</span>
<div>
<h2 class="section-title japanese-text">お / ご</h2>
<div class="romaji-text">o / go</div>
<p class="section-subtitle">
<span class="language-content active">polite marker; honorific prefix particle</span>
<span class="language-content japanese-text">お / ご（尊敬語・謙譲語の接頭辞）</span>
<span class="language-content sinhala-text">ආචාරශීලී සලකුණ; ගෞරවනීය උපසර්ග අංශුව</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g59-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g59-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">**お (o)** and **ご (go)** are honorific prefixes added to nouns, adjectives, or verb stems to show politeness, respect, or elegance (known as *bikago* or *keigo*).
            <br>• **お (o)** is generally used before words of native Japanese origin (*wago*).
            <br>• **ご (go)** is generally used before words of Chinese origin (*kango*).
            <br>They elevate the word, especially when referring to the listener's or a third party's possessions, actions, or state.</span>
            <span class="language-content japanese-text">**お (o)** と **ご (go)** は、名詞、形容詞、または動詞の語幹に付け加えられ、丁寧さ、尊敬、または優雅さを示す敬意を表す接頭辞です（美化語または敬語として知られています）。
            <br>• **お (o)** は通常、和語（固有の日本語の語彙）の前に使われます。
            <br>• **ご (go)** は通常、漢語（中国語由来の語彙）の前に使われます。
            <br>これらは単語を高め、特に聞き手または第三者の所有物、行動、または状態に言及するときに使用されます。</span>
            <span class="language-content sinhala-text">**ඔ (o)** සහ **ගො (go)** යනු නාම පද, විශේෂණ පද හෝ ක්‍රියා පදයේ කඳට එකතු කරනු ලබන ආචාරශීලීත්වය, ගෞරවය හෝ අලංකාරය පෙන්වීමට භාවිතා කරන ගෞරවනීය උපසර්ග වේ (බිකාගෝ හෝ කේයිගෝ ලෙස හැඳින්වේ).
            <br>• **ඔ (o)** සාමාන්‍යයෙන් භාවිතා කරනු ලබන්නේ ස්වදේශීය ජපන් මූලයන් සහිත වචන (වගෝ) ඉදිරියේ ය.
            <br>• **ගො (go)** සාමාන්‍යයෙන් භාවිතා කරනු ලබන්නේ චීන මූලයන් සහිත වචන (කන්ගෝ) ඉදිරියේ ය.
            <br>ඒවා වචනය උසස් කරයි, විශේෂයෙන් සවන්දෙන්නාගේ හෝ තෙවන පාර්ශවයක දේපළ, ක්‍රියා හෝ තත්වය ගැන සඳහන් කරන විට.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** **お** / **ご** + Noun / Adjective / Verb Stem.</span>
            <span class="language-content japanese-text">**構造:** **お** / **ご** + 名詞 / 形容詞 / 動詞の語幹。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** **ඔ** / **ගො** + නාම පදය / විශේෂණ පදය / ක්‍රියා පදයේ කඳ.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">お茶 / ご家族</div>
            <p style="color:#8338ec;font-style:italic;">O-cha (tea) / Go-kazoku (family)</p>
            <p>
                <span class="language-content active">Tea (polite) / Your family (respectful)</span>
                <span class="language-content japanese-text">お茶 / ご家族</span>
                <span class="language-content sinhala-text">තේ (ආචාරශීලී) / ඔබේ පවුල (ගෞරවනීය)</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g60')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">60</span>
<div>
<h2 class="section-title japanese-text">を</h2>
<div class="romaji-text">o / wo</div>
<p class="section-subtitle">
<span class="language-content active">object marker particle</span>
<span class="language-content japanese-text">〜を（目的語マーカー）</span>
<span class="language-content sinhala-text">වස්තු සලකුණු අංශුව</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g60-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g60-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">The particle **を (o)** (written as *wo* but pronounced *o*) is the primary **direct object marker**. It is placed immediately after the noun that is the direct object of a transitive verb, indicating that the action of the verb is performed *on* that noun. It can also mark the **point of departure** or the **area traversed** for movement verbs (e.g., *walk along the road*).</span>
            <span class="language-content japanese-text">助詞の **を (o)**（*wo*と書かれますが、*o*と発音されます）は、主要な**直接目的語マーカー**です。これは他動詞の直接目的語である名詞の直後に置かれ、動詞の動作がその名詞に*対して*行われることを示します。また、移動動詞の**出発点**や**通過する領域**を示すこともあります（例: *道を歩く*）。</span>
            <span class="language-content sinhala-text">**වො (o)** (ලිවෙනුයේ *wo* ලෙස නමුත් උච්චාරණය කරනුයේ *o* ලෙස) යන අංශුව ප්‍රධාන **සෘජු වස්තු සලකුණු** වේ. එය සකර්මක ක්‍රියා පදයක සෘජු වස්තුව වන නාම පදයෙන් වහාම පසුව තබා ඇති අතර, ක්‍රියා පදයේ ක්‍රියාව එම නාම පදය *මත* සිදු වන බව පෙන්නුම් කරයි. එය චලන ක්‍රියා පද සඳහා **නික්ම යාමේ ලක්ෂ්‍යය** හෝ **ගමන් කළ ප්‍රදේශය** ද සලකුණු කළ හැකිය (උදා: *පාර දිගේ ඇවිදීම*).</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Noun (Direct Object) + **を** + Transitive Verb.</span>
            <span class="language-content japanese-text">**構造:** 名詞（直接目的語） + **を** + 他動詞。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** නාම පදය (සෘජු වස්තුව) + **වො** + සකර්මක ක්‍රියා පදය.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">水を飲みます。</div>
            <p style="color:#8338ec;font-style:italic;">Mizu o nomimasu. (Direct Object)</span>
            <p>
                <span class="language-content active">I drink water.</span>
                <span class="language-content japanese-text">水を飲みます。</span>
                <span class="language-content sinhala-text">මම වතුර බොනවා.</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">公園を散歩します。</div>
            <p style="color:#8338ec;font-style:italic;">Kōen o sanpo shimasu. (Area Traversed)</span>
            <p>
                <span class="language-content active">I walk in the park.</span>
                <span class="language-content japanese-text">公園を散歩します。</span>
                <span class="language-content sinhala-text">මම උද්‍යානයේ ඇවිදිනවා.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g61')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">61</span>
<div>
<h2 class="section-title japanese-text">をください</h2>
<div class="romaji-text">o kudasai</div>
<p class="section-subtitle">
<span class="language-content active">please give me ~ (polite request)</span>
<span class="language-content japanese-text">〜をください（丁重な依頼）</span>
<span class="language-content sinhala-text">කරුණාකර මට ~ දෙන්න (ආචාරශීලී ඉල්ලීම)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g61-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g61-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">The pattern **〜をください (o kudasai)** is used to make a **polite request for a physical object** or a measurable quantity (e.g., food, drink, a ticket), translating to **"Please give me ~."** The object being requested is marked by the object particle **を (o)**, followed by the polite imperative **ください (kudasai - please give)**.</span>
            <span class="language-content japanese-text">**〜をください (o kudasai)** のパターンは、**物理的な物**や測定可能な量（例: 食べ物、飲み物、チケット）を**丁重に依頼する**ために使われ、「**〜をください**」という意味になります。要求される目的語は目的語助詞 **を (o)** でマークされ、その後に丁寧な命令形 **ください (kudasai - please give)** が続きます。</span>
            <span class="language-content sinhala-text">**〜වො කුදසායි (o kudasai)** යන රටාව භාවිතා කරනු ලබන්නේ **භෞතික වස්තුවක්** හෝ මැනිය හැකි ප්‍රමාණයක් (උදා: ආහාර, බීම, ටිකට් පතක්) සඳහා **ආචාරශීලී ඉල්ලීමක්** කිරීමට වන අතර, එහි තේරුම **"කරුණාකර මට ~ දෙන්න"** යන්නයි. ඉල්ලා සිටින වස්තුව වස්තු අංශුව **වො (o)** මගින් සලකුණු කරනු ලබන අතර, එයට ආචාරශීලී නියෝග ස්වරූපය වන **කුදසායි (kudasai - කරුණාකර දෙන්න)** අනුගමනය කරයි.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Noun + **を** + **ください**.</span>
            <span class="language-content japanese-text">**構造:** 名詞 + **を** + **ください**。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** නාම පදය + **වො** + **කුදසායි**.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">水を一杯ください。</div>
            <p style="color:#8338ec;font-style:italic;">Mizu o ippai kudasai.</p>
            <p>
                <span class="language-content active">Please give me a glass of water.</span>
                <span class="language-content japanese-text">水を一杯ください。</span>
                <span class="language-content sinhala-text">කරුණාකර මට වතුර වීදුරුවක් දෙන්න.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g62')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">62</span>
<div>
<h2 class="section-title japanese-text">しかし</h2>
<div class="romaji-text">shikashi</div>
<p class="section-subtitle">
<span class="language-content active">but; however ~ (formal/written)</span>
<span class="language-content japanese-text">しかし（逆接の接続詞・硬い）</span>
<span class="language-content sinhala-text">නමුත්; කෙසේ වෙතත් ~ (විධිමත්/ලිඛිත)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g62-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g62-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">**しかし (shikashi)** is a formal or written conjunctive adverb that means **"but"** or **"however,"** used to introduce a statement that **contrasts** with or **contradicts** the preceding statement. It is much stronger and more formal than everyday equivalents like **けど (kedo)** or **が (ga)**. It usually appears at the beginning of a sentence.</span>
            <span class="language-content japanese-text">**しかし (shikashi)** は、**「しかし」** や **「しかしながら」** という意味の正式または文語的な接続詞で、先行する発言と**対照的**または**矛盾する**発言を導入するために使われます。これは、日常的な **けど (kedo)** や **が (ga)** よりもはるかに強く、正式です。通常、文頭に現れます。</span>
            <span class="language-content sinhala-text">**ෂිකාෂි (shikashi)** යනු **"නමුත්"** හෝ **"කෙසේ වෙතත්"** යන අර්ථය දෙන විධිමත් හෝ ලිඛිත සම්බන්ධක ක්‍රියා විශේෂණ පදයක් වන අතර, පෙර ප්‍රකාශයට **ප්‍රතිවිරුද්ධ** හෝ **ප්‍රතිවිරෝධී** ප්‍රකාශයක් හඳුන්වා දීමට භාවිතා කරයි. එය එදිනෙදා භාවිතයේ ඇති **කෙඩෝ (kedo)** හෝ **ගා (ga)** ට වඩා බෙහෙවින් ශක්තිමත් සහ විධිමත් ය. එය සාමාන්‍යයෙන් වාක්‍යයක ආරම්භයේ දිස් වේ.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Sentence 1. **しかし**, Sentence 2 (Contrast).</span>
            <span class="language-content japanese-text">**構造:** 文 1。**しかし**, 文 2（対照）。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** වාක්‍යය 1. **ෂිකාෂි**, වාක්‍යය 2 (ප්‍රතිවිරුද්ධ).</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">彼は優秀だ。しかし、経験が少ない。</div>
            <p style="color:#8338ec;font-style:italic;">Kare wa yūshū da. Shikashi, keiken ga sukunai.</p>
            <p>
                <span class="language-content active">He is excellent. However, he has little experience.</span>
                <span class="language-content japanese-text">彼は優秀だ。しかし、経験が少ない。</span>
                <span class="language-content sinhala-text">ඔහු විශිෂ්ටයි. නමුත්, ඔහුට අත්දැකීම් අඩුයි.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g63')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">63</span>
<div>
<h2 class="section-title japanese-text">それから</h2>
<div class="romaji-text">sore kara</div>
<p class="section-subtitle">
<span class="language-content active">and; and then; after that; since then (connecting clauses)</span>
<span class="language-content japanese-text">それから（順接・追加）</span>
<span class="language-content sinhala-text">සහ; ඊට පසු; එතැන් සිට (වගන්ති සම්බන්ධ කිරීම)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g63-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g63-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">**それから (sore kara)** is a conjunctive phrase used to connect sentences or items in a list. It can indicate:
            <br>1. **Sequence/Time:** **"And then"** or **"after that,"** introducing a subsequent event.
            <br>2. **Addition/Listing:** **"And,"** adding another item or piece of information to a list.
            <br>The *kara* portion is the same particle as in **〜から (kara - from/since)**.</span>
            <span class="language-content japanese-text">**それから (sore kara)** は、文やリストの項目を接続するために使われる接続詞フレーズです。以下の意味を示すことができます。
            <br>1. **順序・時間:** **「それから」** または **「その後」**、後続の出来事を導入します。
            <br>2. **追加・列挙:** **「そして」**、リストに別の項目や情報を追加します。
            <br>*kara* の部分は、**〜から (kara - from/since)** と同じ助詞です。</span>
            <span class="language-content sinhala-text">**සොරෙ කාරා (sore kara)** යනු වාක්‍ය හෝ ලැයිස්තුවක අයිතම සම්බන්ධ කිරීමට භාවිතා කරන සම්බන්ධක වාක්‍ය ඛණ්ඩයකි. එය පහත සඳහන් දේ දැක්විය හැකිය:
            <br>1. **අනුපිළිවෙල/කාලය:** **"ඊට පසු"** හෝ **"එතැන් සිට,"** පසුකාලීන සිදුවීමක් හඳුන්වා දෙයි.
            <br>2. **එකතු කිරීම/ලැයිස්තුගත කිරීම:** **"සහ,"** ලැයිස්තුවකට තවත් අයිතමයක් හෝ තොරතුරක් එක් කරයි.
            <br>**කාරා (kara)** කොටස **〜කාරා (kara - සිට)** හි ඇති එකම අංශුවයි.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Clause 1. **それから**, Clause 2.</span>
            <span class="language-content japanese-text">**構造:** 節 1。**それから**, 節 2。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** වගන්තිය 1. **සොරෙ කාරා**, වගන්තිය 2.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">まずご飯を食べて、それから宿題をします。</div>
            <p style="color:#8338ec;font-style:italic;">Mazu gohan o tabete, sore kara shukudai o shimasu.</p>
            <p>
                <span class="language-content active">First, I'll eat, and then I'll do my homework. (Sequence)</span>
                <span class="language-content japanese-text">まずご飯を食べて、それから宿題をします。</span>
                <span class="language-content sinhala-text">මුලින්ම මම කෑම කනවා, ඊට පස්සේ ගෙදර වැඩ කරනවා.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g64')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">64</span>
<div>
<h2 class="section-title japanese-text">そして</h2>
<div class="romaji-text">soshite</div>
<p class="section-subtitle">
<span class="language-content active">and; and then; thus; and now ~ (connecting sentences/clauses)</span>
<span class="language-content japanese-text">そして（並列・順接）</span>
<span class="language-content sinhala-text">සහ; ඊට පසු; ඒ අනුව; දැන් ~ (වාක්‍ය/වගන්ති සම්බන්ධ කිරීම)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g64-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g64-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">**そして (soshite)** is a common conjunctive word used to connect sentences or clauses, indicating:
            <br>1. **Addition/Listing:** **"And"** or **"in addition,"** used to add a similar idea, item, or event.
            <br>2. **Sequence:** **"And then,"** indicating a chronological order of events.
            <br>It is generally used to connect sentences that are of equal status or a similar nature, often with a sense of flow or continuity.</span>
            <span class="language-content japanese-text">**そして (soshite)** は、文や節を接続するためによく使われる接続詞で、以下のことを示します。
            <br>1. **追加・列挙:** **「そして」** または **「さらに」**、同様の考え、項目、または出来事を追加するために使用されます。
            <br>2. **順序:** **「そして」**、出来事の時系列順を示します。
            <br>一般的に、同じ地位または同様の性質の文を結びつけるために使われ、しばしば流れや連続性の感覚があります。</span>
            <span class="language-content sinhala-text">**සොෂිතෙ (soshite)** යනු වාක්‍ය හෝ වගන්ති සම්බන්ධ කිරීමට භාවිතා කරන පොදු සම්බන්ධක පදයක් වන අතර, එය පහත සඳහන් දේ දක්වයි:
            <br>1. **එකතු කිරීම/ලැයිස්තුගත කිරීම:** **"සහ"** හෝ **"ඊට අමතරව,"** සමාන අදහසක්, අයිතමයක් හෝ සිදුවීමක් එක් කිරීමට භාවිතා කරයි.
            <br>2. **අනුපිළිවෙල:** **"ඊට පසු,"** සිදුවීම්වල කාලානුක්‍රමික අනුපිළිවෙල දක්වයි.
            <br>එය සාමාන්‍යයෙන් සමාන තත්ත්වයක් හෝ සමාන ස්වභාවයක් ඇති වාක්‍ය සම්බන්ධ කිරීමට භාවිතා කරන අතර, බොහෝ විට ගලායාම හෝ අඛණ්ඩතාව පිළිබඳ හැඟීමක් ඇත.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Clause 1. **そして**, Clause 2.</span>
            <span class="language-content japanese-text">**構造:** 節 1。**そして**, 節 2。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** වගන්තිය 1. **සොෂිතෙ**, වගන්තිය 2.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">彼女は優しく、そして美しい。</div>
            <p style="color:#8338ec;font-style:italic;">Kanojo wa yasashiku, soshite utsukushii.</p>
            <p>
                <span class="language-content active">She is kind, and (also) beautiful. (Addition)</span>
                <span class="language-content japanese-text">彼女は優しく、そして美しい。</span>
                <span class="language-content sinhala-text">ඇය කරුණාවන්තයි, ඒ වගේම ලස්සනයි.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g65')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">65</span>
<div>
<h2 class="section-title japanese-text">すぎる</h2>
<div class="romaji-text">sugiru</div>
<p class="section-subtitle">
<span class="language-content active">too much; excessively ~</span>
<span class="language-content japanese-text">〜すぎる（過剰）</span>
<span class="language-content sinhala-text">වැඩිපුර; අධික ලෙස ~</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g65-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g65-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">**すぎる (sugiru)** is a verb meaning **"to exceed"** or **"to be too much/excessive."** It is attached to the stem of a verb (Masu-stem) or the stem of an adjective (I-adjective without **い**, Na-adjective stem) to indicate that the action or quality is done to an **undesirable extreme** or beyond a reasonable limit. When attached, it functions as a regular Group 2 verb.</span>
            <span class="language-content japanese-text">**すぎる (sugiru)** は、「**超過する**」または「**過度である/多すぎる**」という意味の動詞です。動詞の語幹（ます形から「ます」を取った形）や形容詞の語幹（い形容詞から**い**を取った形、な形容詞の語幹）に接続され、その動作や性質が**望ましくない極端**または合理的な限度を超えて行われることを示します。接続された後は、通常のグループ 2 動詞として機能します。</span>
            <span class="language-content sinhala-text">**සුගිරු (sugiru)** යනු **"ඉක්මවා යනවා"** හෝ **"වැඩිපුර/අධික"** යන අර්ථය දෙන ක්‍රියා පදයකි. එය ක්‍රියා පදයක කඳට (මසු-stem) හෝ විශේෂණ පදයක කඳට (I-විශේෂණ පදයෙහි **ඉ** නොමැති, නා-විශේෂණ පදයේ කඳ) සම්බන්ධ කරනු ලබන්නේ ක්‍රියාව හෝ ගුණාත්මකභාවය **අවශ්‍ය නොවන අන්තයකට** හෝ සාධාරණ සීමාවක් ඉක්මවා සිදු කරන බව දැක්වීමට ය. සම්බන්ධ වූ පසු, එය සාමාන්‍ය කණ්ඩායම් 2 ක්‍රියා පදයක් ලෙස ක්‍රියා කරයි.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Verb Masu-stem / I-Adjective Stem / Na-Adjective Stem + **すぎる**.</span>
            <span class="language-content japanese-text">**構造:** 動詞のます形 / い形容詞の語幹 / な形容詞の語幹 + **すぎる**。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** ක්‍රියා පදයේ මසු-stem / I-විශේෂණ පදයේ කඳ / නා-විශේෂණ පදයේ කඳ + **සුගිරු**.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">食べすぎました。</div>
            <p style="color:#8338ec;font-style:italic;">Tabe sugimashita. (食べる (taberu) → 食べ (tabe))</p>
            <p>
                <span class="language-content active">I ate too much.</span>
                <span class="language-content japanese-text">食べすぎました。</span>
                <span class="language-content sinhala-text">මම වැඩිපුර කෑවා.</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">このカバンは大きすぎます。</div>
            <p style="color:#8338ec;font-style:italic;">Kono kaban wa ōkisugimasu. (大きい (ōkii) → 大き (ōki))</p>
            <p>
                <span class="language-content active">This bag is too big.</span>
                <span class="language-content japanese-text">このカバンは大きすぎます。</span>
                <span class="language-content sinhala-text">මේ බෑගය හරිම ලොකුයි.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g66')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">66</span>
<div>
<h2 class="section-title japanese-text">たことがある</h2>
<div class="romaji-text">ta koto ga aru</div>
<p class="section-subtitle">
<span class="language-content active">to have done something before; have the experience of ~</span>
<span class="language-content japanese-text">〜たことがある（経験）</span>
<span class="language-content sinhala-text">මීට පෙර යමක් කර ඇත; ~ අත්දැකීම් ඇත</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g66-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g66-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">The pattern **〜たことがある (ta koto ga aru)** is used to indicate that the speaker **has the experience of performing an action** at some point in the past. The verb is in its **Ta-form** (past plain form), followed by **こと (koto - matter/thing)**, the subject particle **が (ga)**, and the verb **ある (aru - to exist)**. This structure is the Japanese equivalent of "have you ever...?" and "I have done..."</span>
            <span class="language-content japanese-text">**〜たことがある (ta koto ga aru)** のパターンは、話し手が過去のある時点で**動作を行った経験がある**ことを示すために使われます。動詞は**た形**（過去の普通形）で、その後に**こと (koto - 事/もの)**、主語助詞 **が (ga)**、そして動詞の **ある (aru - 存在する)** が続きます。この構造は、英語の「あなたは今までに〜したことがありますか？」や「私は〜したことがあります」に相当します。</span>
            <span class="language-content sinhala-text">**〜තා කොතො ගා අරු (ta koto ga aru)** යන රටාව භාවිතා කරනු ලබන්නේ අතීතයේ යම් අවස්ථාවකදී කථිකයාට **ක්‍රියාවක් සිදු කිරීමේ අත්දැකීමක් ඇති** බව දැක්වීමට ය. ක්‍රියා පදය එහි **තා-ස්වරූපයෙන්** (අතීත සරල ස්වරූපය) ඇති අතර, එයට **කොතො (koto - කාරණය/දෙය)**, කර්තෘ අංශුව **ගා (ga)**, සහ **අරු (aru - පවතී)** යන ක්‍රියා පදය අනුගමනය කරයි. මෙම ව්‍යුහය "ඔබ කවදා හෝ...?" සහ "මම... කර ඇත" යන ඉංග්‍රීසි ප්‍රකාශනයට සමාන වේ.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Verb Ta-form + **こと** + **が** + **ある**.</span>
            <span class="language-content japanese-text">**構造:** 動詞のた形 + **こと** + **が** + **ある**。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** ක්‍රියා පදයේ තා-ස්වරූපය + **කොතො** + **ගා** + **අරු**.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">日本へ行ったことがありますか。</div>
            <p style="color:#8338ec;font-style:italic;">Nihon e itta koto ga arimasu ka. (行く (iku) → 行った (itta))</p>
            <p>
                <span class="language-content active">Have you ever been to Japan?</span>
                <span class="language-content japanese-text">日本へ行ったことがありますか。</span>
                <span class="language-content sinhala-text">ඔබ ජපානයට ගොස් තිබෙනවාද?</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g67')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">67</span>
<div>
<h2 class="section-title japanese-text">たい</h2>
<div class="romaji-text">tai</div>
<p class="section-subtitle">
<span class="language-content active">want to do something (desire)</span>
<span class="language-content japanese-text">〜たい（願望）</span>
<span class="language-content sinhala-text">යමක් කිරීමට අවශ්‍යයි (අවශ්‍යතාව)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g67-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g67-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">The suffix **〜たい (tai)** is attached to the Masu-stem of a verb to express the speaker's **desire or wish to perform an action**, meaning **"want to do."** When attached, the resulting word functions as an **i-adjective** (it ends in **い**) and is conjugated like one (e.g., negative form: **たくない (takunai)**).</span>
            <span class="language-content japanese-text">接尾辞の **〜たい (tai)** は、動詞のます形に接続され、話し手の**動作を行いたいという願望や希望**を表現し、「**〜したい**」という意味になります。接続された単語は**い形容詞**として機能し（**い**で終わる）、それと同じように活用されます（例: 否定形: **たくない (takunai)**）。</span>
            <span class="language-content sinhala-text">**〜තායි (tai)** යන උපසර්ගය ක්‍රියා පදයක මසු-stem එකට සම්බන්ධ කරනු ලබන්නේ කථිකයාගේ **ක්‍රියාවක් කිරීමට ඇති අවශ්‍යතාවය හෝ කැමැත්ත** ප්‍රකාශ කිරීමට වන අතර, එහි තේරුම **"කිරීමට අවශ්‍යයි"** යන්නයි. සම්බන්ධ වූ විට, ප්‍රතිඵලය වන වචනය **i-විශේෂණ පදයක්** ලෙස ක්‍රියා කරයි (**ඉ** වලින් අවසන් වේ) සහ එය ඒ ආකාරයෙන්ම විභක්ති වේ (උදා: ඍණ ස්වරූපය: **තකුනායි (takunai)**).</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Verb Masu-stem + **たい**.</span>
            <span class="language-content japanese-text">**構造:** 動詞のます形 + **たい**。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** ක්‍රියා පදයේ මසු-stem + **තායි**.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">日本語を勉強したいです。</div>
            <p style="color:#8338ec;font-style:italic;">Nihongo o benkyō shitai desu. (勉強する (benkyō suru) → 勉強し (benkyō shi))</p>
            <p>
                <span class="language-content active">I want to study Japanese.</span>
                <span class="language-content japanese-text">日本語を勉強したいです。</span>
                <span class="language-content sinhala-text">මට ජපන් භාෂාව ඉගෙන ගැනීමට අවශ්‍යයි.</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">何も飲みたくない。</div>
            <p style="color:#8338ec;font-style:italic;">Nani mo nomitakunai. (Negative: 飲みたい (nomitai) → 飲みたくない (nomitakunai))</p>
            <p>
                <span class="language-content active">I don't want to drink anything.</span>
                <span class="language-content japanese-text">何も飲みたくない。</span>
                <span class="language-content sinhala-text">මට කිසිවක් බොන්න අවශ්‍ය නැහැ.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g68')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">68</span>
<div>
<h2 class="section-title japanese-text">たり〜たり</h2>
<div class="romaji-text">tari~tari</div>
<p class="section-subtitle">
<span class="language-content active">do such things as A and B; do A, B, and so on (examples)</span>
<span class="language-content japanese-text">〜たり〜たり（例示・羅列）</span>
<span class="language-content sinhala-text">A සහ B වැනි දේ කරන්න; A, B, සහ යනාදිය කරන්න (උදාහරණ)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g68-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g68-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">The structure **〜たり〜たりする (tari〜tari suru)** is used to give **two or more examples** of actions that are done, implying that other similar actions are also done. It is formed by using the **Ta-form** of the verb followed by **り (ri)**, and the entire phrase is concluded with the verb **する (suru - to do)**. It means **"do things such as A and B"** or **"sometimes A and sometimes B."**</span>
            <span class="language-content japanese-text">**〜たり〜たりする (tari〜tari suru)** の構造は、行われる動作の**2つ以上の例**を挙げるために使われ、他の同様の動作も行われることを示唆します。これは動詞の**た形**の後に **り (ri)** が続き、フレーズ全体は動詞の **する (suru - to do)** で締めくくられます。意味は「**AやBといったことをする**」または「**AしたりBしたりする**」です。</span>
            <span class="language-content sinhala-text">**〜තාරි〜තාරි සුරු (tari〜tari suru)** යන ව්‍යුහය භාවිතා කරනු ලබන්නේ සිදු කරන ක්‍රියාවල **උදාහරණ දෙකක් හෝ වැඩි ගණනක්** දීමට වන අතර, වෙනත් සමාන ක්‍රියා ද සිදු කරන බව එයින් ඇඟවෙයි. එය ක්‍රියා පදයේ **තා-ස්වරූපය** සහ එයට **රි (ri)** අනුගමනය කිරීමෙන් සෑදී ඇති අතර, සම්පූර්ණ වාක්‍ය ඛණ්ඩය **සුරු (suru - to do)** යන ක්‍රියා පදයෙන් අවසන් වේ. එහි තේරුම **"A සහ B වැනි දේ කරන්න"** හෝ **"සමහර විට A සහ සමහර විට B"** යන්නයි.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Verb A Ta-form + **り** + Verb B Ta-form + **り** + **する**.</span>
            <span class="language-content japanese-text">**構造:** 動詞 A のた形 + **り** + 動詞 B のた形 + **り** + **する**。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** ක්‍රියා පදය A හි තා-ස්වරූපය + **රි** + ක්‍රියා පදය B හි තා-ස්වරූපය + **රි** + **සුරු**.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">週末は映画を見たり、本を読んだりします。</div>
            <p style="color:#8338ec;font-style:italic;">Shūmatsu wa eiga o mitari, hon o yondari shimasu.</p>
            <p>
                <span class="language-content active">On the weekend, I do things like watching movies and reading books.</span>
                <span class="language-content japanese-text">週末は映画を見たり、本を読んだりします。</span>
                <span class="language-content sinhala-text">සති අන්තයේ මම චිත්‍රපට බලනවා, පොත් කියවනවා වැනි දේ කරනවා.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g69')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">69</span>
<div>
<h2 class="section-title japanese-text">てある</h2>
<div class="romaji-text">te aru</div>
<p class="section-subtitle">
<span class="language-content active">is/has been done (resulting state of a deliberate action)</span>
<span class="language-content japanese-text">〜てある（他動詞の結果の状態）</span>
<span class="language-content sinhala-text">කර ඇත/කර තිබේ (හේතුකාරක ක්‍රියාවක ප්‍රතිඵලයක්)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g69-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g69-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">The structure **〜てある (te aru)** is used with **transitive verbs** in the **Te-form** followed by **ある (aru - to exist)** to indicate the **resulting state of a deliberate action** that was performed in preparation for something. The action has been completed, and the object is now in a certain state. The focus is on the current state and the purpose behind it.</span>
            <span class="language-content japanese-text">**〜てある (te aru)** の構造は、**他動詞**を**て形**にし、その後に **ある (aru - 存在する)** が続く形で、何かの準備のために行われた**意図的な動作の結果の状態**を示すために使われます。動作は完了しており、目的語は現在ある状態にあります。焦点は、現在の状態とその背後にある目的にあります。</span>
            <span class="language-content sinhala-text">**〜තෙ අරු (te aru)** යන ව්‍යුහය භාවිතා කරනු ලබන්නේ **සකර්මක ක්‍රියා පද** **Te-ආකාරයෙන්** සහ එයට **අරු (aru - පවතී)** අනුගමනය කිරීමෙන් යම් දෙයක් සඳහා සූදානම් වීම සඳහා සිදු කරන ලද **හේතුකාරක ක්‍රියාවක ප්‍රතිඵලයක්** දැක්වීමට ය. ක්‍රියාව අවසන් වී ඇති අතර, වස්තුව දැන් නිශ්චිත තත්වයක පවතී. අවධානය යොමු කර ඇත්තේ වර්තමාන තත්වය සහ ඊට පිටුපස ඇති අරමුණ මත ය.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Noun (Object) + **が/は** + Verb Te-form + **ある**.</span>
            <span class="language-content japanese-text">**構造:** 名詞（目的語） + **が/は** + 動詞のて形 + **ある**。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** නාම පදය (වස්තුව) + **ගා/වා** + ක්‍රියා පදයේ Te-ආකාරය + **අරු**.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">窓が開けてあります。</div>
            <p style="color:#8338ec;font-style:italic;">Mado ga akete arimasu. (開ける (akeru) → 開けて (akete))</p>
            <p>
                <span class="language-content active">The window is open (because someone opened it on purpose).</span>
                <span class="language-content japanese-text">窓が開けてあります。</span>
                <span class="language-content sinhala-text">කවුළුව විවෘත කර ඇත (යමෙක් එය හිතාමතාම විවෘත කළ නිසා).</span>
            </p>
        </div>
    </div>
</div>
</div>
<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g70')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">70</span>
<div>
<h2 class="section-title japanese-text">ている</h2>
<div class="romaji-text">te iru</div>
<p class="section-subtitle">
<span class="language-content active">ongoing action or current state</span>
<span class="language-content japanese-text">〜ている</span>
<span class="language-content sinhala-text">සිදුවෙමින් පවතින ක්‍රියාව හෝ වර්තමාන තත්වය</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g70-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g70-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">The **て形 (te-form)** of a verb followed by **いる (iru)** is the main way to express continuous action (like the English "-ing" form) or a resulting state in Japanese.</span>
            <span class="language-content japanese-text">動詞の**て形 (te-form)** に **いる (iru)** が続く形は、日本語で継続的な動作（英語の「-ing」形のようなもの）や結果の状態を表す主要な方法です。</span>
            <span class="language-content sinhala-text">ක්‍රියා පදයක **තෙ-ආකාරය (te-form)** ට **ඉරු (iru)** එක් කිරීමෙන්, ජපන් බසින් අඛණ්ඩ ක්‍රියාවක් (ඉංග්‍රීසි භාෂාවේ "-ing" ආකාරය මෙන්) හෝ අවසාන තත්වයක් ප්‍රකාශ කරයි.</span>
        </p>
        <ol>
            <li>
                <span class="language-content active">**Ongoing Action (Progressive):** Describes an action currently in progress (e.g., studying, eating).</span>
                <span class="language-content japanese-text">**継続的な動作（進行形）:** 現在進行中の動作を記述する（例：勉強している、食べている）。</span>
                <span class="language-content sinhala-text">**අඛණ්ඩ ක්‍රියාව (Progressive):** දැනට සිදුවෙමින් පවතින ක්‍රියාවක් විස්තර කරයි (උදා: පාඩම් කරමින්, කමින් සිටීම).</span>
            </li>
            <li>
                <span class="language-content active">**Current State:** Describes a state resulting from a past action, especially with change-of-state verbs (e.g., getting married, being closed).</span>
                <span class="language-content japanese-text">**現在の状態:** 過去の動作の結果として生じた状態を記述する。特に状態変化動詞（例：結婚している、閉まっている）で用いられる。</span>
                <span class="language-content sinhala-text">**වර්තමාන තත්වය:** අතීත ක්‍රියාවක ප්‍රතිඵලයක් ලෙස ඇති වූ තත්වයක් විස්තර කරයි, විශේෂයෙන් තත්ව වෙනස් කිරීමේ ක්‍රියා පද සමඟ (උදා: විවාහ වී සිටීම, වසා තිබීම).</span>
            </li>
        </ol>
        <p>
            <span class="language-content active">**Structure:** Verb Te-form + **いる** (or **います** in polite form).</span>
            <span class="language-content japanese-text">**構造:** 動詞のて形 + **いる** （丁寧形では **います**）。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** ක්‍රියා පදයේ තෙ-ආකාරය + **ඉරු** (හෝ විධිමත් ආකාරයෙන් **ඉමාසු**).</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">今、本を読んでいます。</div>
            <p style="color:#8338ec;font-style:italic;">Ima, hon o yonde imasu.</p>
            <p>
                <span class="language-content active">I am reading a book now. (Ongoing Action)</span>
                <span class="language-content japanese-text">今、本を読んでいます。</span>
                <span class="language-content sinhala-text">මම දැන් පොතක් කියවමින් සිටිමි.</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">彼女は結婚しています。</div>
            <p style="color:#8338ec;font-style:italic;">Kanojo wa kekkon shite imasu.</p>
            <p>
                <span class="language-content active">She is married. (Current State)</span>
                <span class="language-content japanese-text">彼女は結婚しています。</span>
                <span class="language-content sinhala-text">ඇය විවාහ වී සිටී.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g71')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">71</span>
<div>
<h2 class="section-title japanese-text">てから</h2>
<div class="romaji-text">te kara</div>
<p class="section-subtitle">
<span class="language-content active">after doing ~</span>
<span class="language-content japanese-text">〜てから</span>
<span class="language-content sinhala-text">~ කළ පසු; ~ කිරීමෙන් පසු</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g71-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g71-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">The pattern **てから (te kara)** connects two verbs, clearly indicating that the action in the first clause (**Verb-te**) must be **completed** before the action in the second clause takes place. It means **"after doing (Verb 1), (Verb 2) is done."** This highlights a sequential order of events.</span>
            <span class="language-content japanese-text">このパターン **てから (te kara)** は、2つの動詞を接続し、第1節の動作（**動詞て形**）が完了してから第2節の動作が行われることを明確に示します。これは、「**（動詞1）をした後で、（動詞2）をする**」という意味で、出来事の順序を強調します。</span>
            <span class="language-content sinhala-text">**තෙ කාරා (te kara)** යන රටාව ක්‍රියා පද දෙකක් සම්බන්ධ කරන අතර, දෙවන වගන්තියේ ක්‍රියාව සිදුවීමට පෙර පළමු වගන්තියේ ක්‍රියාව (**ක්‍රියා පදය-te**) **අවසන් කළ යුතු** බව පැහැදිලිව පෙන්නුම් කරයි. එහි තේරුම **"(ක්‍රියා පදය 1) කළ පසු, (ක්‍රියා පදය 2) සිදු කරයි"** යන්නයි. මෙය සිදුවීම්වල අනුපිළිවෙල ඉස්මතු කරයි.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Verb Te-form + **から**.</span>
            <span class="language-content japanese-text">**構造:** 動詞のて形 + **から**。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** ක්‍රියා පදයේ Te-ආකාරය + **කාරා**.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">シャワーを浴びてから寝ます。</div>
            <p style="color:#8338ec;font-style:italic;">Shawā o abite kara nemasu.</p>
            <p>
                <span class="language-content active">I will go to sleep after taking a shower.</span>
                <span class="language-content japanese-text">シャワーを浴びてから寝ます。</span>
                <span class="language-content sinhala-text">මම ෂවර් එකක් අරගෙන නිදාගන්නවා.</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">お酒を飲んでから、運転してはいけない。</div>
            <p style="color:#8338ec;font-style:italic;">Osake o nonde kara, unten shite wa ikenai.</p>
            <p>
                <span class="language-content active">One must not drive after drinking alcohol.</span>
                <span class="language-content japanese-text">お酒を飲んでから、運転してはいけない。</span>
                <span class="language-content sinhala-text">මත්පැන් පානය කිරීමෙන් පසු රිය පැදවිය යුතු නැත.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g72')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">72</span>
<div>
<h2 class="section-title japanese-text">てください</h2>
<div class="romaji-text">te kudasai</div>
<p class="section-subtitle">
<span class="language-content active">please do ~</span>
<span class="language-content japanese-text">〜てください（依頼）</span>
<span class="language-content sinhala-text">කරුණාකර ~ කරන්න (ඉල්ලීම)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g72-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g72-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">The pattern **てください (te kudasai)** is used to make a **polite request** to the listener, translating to **"Please do ~."** It is formed by attaching the phrase **ください (kudasai)** (meaning 'please give/grant me') to the **Te-form** of a verb.</span>
            <span class="language-content japanese-text">このパターン **てください (te kudasai)** は、聞き手に対して**丁寧な依頼**をするために使われ、「**〜してください**」という意味になります。動詞の**て形**に **ください (kudasai)**（「ください/与えてください」の意）というフレーズを付けて構成されます。</span>
            <span class="language-content sinhala-text">**තෙ කුදසායි (te kudasai)** යන රටාව සවන්දෙන්නාට **ආචාරශීලී ඉල්ලීමක්** කිරීමට භාවිතා කරන අතර, එහි තේරුම **"කරුණාකර ~ කරන්න"** යන්නයි. එය ක්‍රියා පදයක **Te-ආකාරයට** **කුදසායි (kudasai)** (එනම් 'කරුණාකර මට දෙන්න') යන වාක්‍ය ඛණ්ඩය සම්බන්ධ කිරීමෙන් සෑදී ඇත.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Verb Te-form + **ください**.</span>
            <span class="language-content japanese-text">**構造:** 動詞のて形 + **ください**。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** ක්‍රියා පදයේ Te-ආකාරය + **කුදසායි**.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">ここに来てください。</div>
            <p style="color:#8338ec;font-style:italic;">Koko ni kite kudasai.</p>
            <p>
                <span class="language-content active">Please come here.</span>
                <span class="language-content japanese-text">ここに来てください。</span>
                <span class="language-content sinhala-text">කරුණාකර මෙතනට එන්න.</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">ゆっくり話してください。</div>
            <p style="color:#8338ec;font-style:italic;">Yukkuri hanashite kudasai.</p>
            <p>
                <span class="language-content active">Please speak slowly.</span>
                <span class="language-content japanese-text">ゆっくり話してください。</span>
                <span class="language-content sinhala-text">කරුණාකර සෙමින් කතා කරන්න.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g73')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">73</span>
<div>
<h2 class="section-title japanese-text">てはいけない</h2>
<div class="romaji-text">te wa ikenai</div>
<p class="section-subtitle">
<span class="language-content active">must not; may not; cannot (prohibition)</span>
<span class="language-content japanese-text">〜てはいけない（禁止）</span>
<span class="language-content sinhala-text">නොකළ යුතුයි; නොකරිය යුතුයි; බැහැ (තහනම් කිරීම)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g73-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g73-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">The pattern **てはいけない (te wa ikenai)** is used to express a **prohibition or strong warning**, meaning **"must not,"** **"may not,"** or **"cannot"** do something. It literally means, **"If you do [A], it is no good."** The particle **は (wa)** is often contracted with **て (te)** into **ちゃ (cha)** (casual) or **じゃ (ja)**. A polite version is **てはいけません (te wa ikemasen)**.</span>
            <span class="language-content japanese-text">このパターン **てはいけない (te wa ikenai)** は、**禁止や強い警告**を表すために使われ、「**〜してはいけない**」という意味になります。文字通りには、「**[A]をすると、いけない**」という意味です。助詞の **は (wa)** は、会話ではしばしば **て (te)** と縮約されて **ちゃ (cha)**（カジュアル）や **じゃ (ja)** になります。丁寧な形は **てはいけません (te wa ikemasen)** です。</span>
            <span class="language-content sinhala-text">**තෙ වා ඉකෙනායි (te wa ikenai)** යන රටාව **තහනමක් හෝ දැඩි අනතුරු ඇඟවීමක්** ප්‍රකාශ කිරීමට භාවිතා කරන අතර, එහි තේරුම යමක් **"නොකළ යුතුයි,"** **"නොකරිය යුතුයි,"** හෝ **"බැහැ"** යන්නයි. එහි වචනාර්ථය **"ඔබ [A] කළහොත්, එය හොඳ නැත"** යන්නයි. **වා (wa)** යන අංශුව බොහෝ විට **තෙ (te)** සමඟ සංකෝචනය වී **චා (cha)** (අනියම්) හෝ **ජා (ja)** බවට පත් වේ. ආචාරශීලී ස්වරූපය **තෙ වා ඉකෙමසෙන් (te wa ikemasen)** වේ.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Verb Te-form + **は いけない** (or **はいけません**).</span>
            <span class="language-content japanese-text">**構造:** 動詞のて形 + **は いけない** （または **はいけません**）。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** ක්‍රියා පදයේ Te-ආකාරය + **වා ඉකෙනායි** (හෝ **වා ඉකෙමසෙන්**).</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">ここでたばこを吸ってはいけません。</div>
            <p style="color:#8338ec;font-style:italic;">Koko de tabako o sutte wa ikemasen.</p>
            <p>
                <span class="language-content active">You must not smoke here. (Polite Prohibition)</span>
                <span class="language-content japanese-text">ここでたばこを吸ってはいけません。</span>
                <span class="language-content sinhala-text">ඔබ මෙහි දුම්පානය නොකළ යුතුයි.</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">授業中に携帯を使っちゃいけないよ。</div>
            <p style="color:#8338ec;font-style:italic;">Jugyō-chū ni keitai o tsukaccha ikenai yo. (Casual Contraction)</p>
            <p>
                <span class="language-content active">You can't use your phone during class.</span>
                <span class="language-content japanese-text">授業中に携帯を使っちゃいけないよ。</span>
                <span class="language-content sinhala-text">ඔබ පන්තිය අතරතුර දුරකථනය භාවිතා නොකළ යුතුයි.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g74')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">74</span>
<div>
<h2 class="section-title japanese-text">てもいいです</h2>
<div class="romaji-text">te mo ii desu</div>
<p class="section-subtitle">
<span class="language-content active">is OK to ~; is alright to ~; may I ~? (permission)</span>
<span class="language-content japanese-text">〜てもいいです（許可）</span>
<span class="language-content sinhala-text">~ කිරීම හොඳයි; ~ කිරීම කමක් නැත; මට ~ කිරීමට අවසර තිබේද? (අවසරය)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g74-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g74-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">The pattern **てもいいです (te mo ii desu)** is used to grant **permission** or state that something is acceptable, meaning **"It is okay/alright to do ~"** or **"You may do ~."** When used as a question (**てもいいですか**), it is used to **ask for permission**, meaning **"May I do ~?"** It is formed by combining the Te-form of a verb with the particle **も (mo)** (even) and the polite phrase **いいです (ii desu)** (is good). Literally: **"Even if you do [A], it is good."**</span>
            <span class="language-content japanese-text">このパターン **てもいいです (te mo ii desu)** は、**許可**を与えたり、何かが容認できることを述べたりするために使われ、「**〜しても大丈夫です**」または「**〜してもよいです**」という意味になります。疑問形（**てもいいですか**）として使われるときは、**許可を求める**ために使われ、「**〜してもよろしいでしょうか**」という意味になります。動詞のて形に、助詞の **も (mo)**（〜でさえ）と丁寧なフレーズの **いいです (ii desu)**（良いです）を組み合わせて構成されます。文字通りには、「**[A]をしても、良いです**」という意味です。</span>
            <span class="language-content sinhala-text">**තෙ මො ඊ දේසු (te mo ii desu)** යන රටාව **අවසරය** දීමට හෝ යමක් පිළිගත හැකි බව පැවසීමට භාවිතා කරන අතර, එහි තේරුම **"~ කිරීම කමක් නැත/හොඳයි"** හෝ **"ඔබට ~ කළ හැක"** යන්නයි. ප්‍රශ්නයක් ලෙස භාවිතා කරන විට (**තෙ මො ඊ දේසු කා**), එය **අවසරය විමසීමට** භාවිතා කරන අතර, එහි තේරුම **"මට ~ කිරීමට අවසර තිබේද?"** යන්නයි. එය ක්‍රියා පදයක Te-ආකාරය **මෝ (mo)** (පවා) යන අංශුව සහ ආචාරශීලී වාක්‍ය ඛණ්ඩය වන **ඊ දේසු (ii desu)** (හොඳයි) සමඟ සංයෝජනය කිරීමෙන් සෑදී ඇත. වචනාර්ථයෙන්: **"ඔබ [A] කළත්, එය හොඳයි."**</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Verb Te-form + **も いいです** (or **もいいですか**).</span>
            <span class="language-content japanese-text">**構造:** 動詞のて形 + **も いいです** （または **もいいですか**）。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** ක්‍රියා පදයේ Te-ආකාරය + **මෝ ඊ දේසු** (හෝ **මෝ ඊ දේසු කා**).</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">ここで待ってもいいですか。</div>
            <p style="color:#8338ec;font-style:italic;">Koko de matte mo ii desu ka.</p>
            <p>
                <span class="language-content active">May I wait here? (Asking Permission)</span>
                <span class="language-content japanese-text">ここで待ってもいいですか。</span>
                <span class="language-content sinhala-text">මට මෙහි ඉන්න පුලුවන්ද?</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">もう帰ってもいいですよ。</div>
            <p style="color:#8338ec;font-style:italic;">Mō kaette mo ii desu yo.</p>
            <p>
                <span class="language-content active">It's okay for you to go home now. (Granting Permission)</span>
                <span class="language-content japanese-text">もう帰ってもいいですよ。</span>
                <span class="language-content sinhala-text">දැන් ඔබට ගෙදර යාම කමක් නැත.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g75')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">75</span>
<div>
<h2 class="section-title japanese-text">と</h2>
<div class="romaji-text">to</div>
<p class="section-subtitle">
<span class="language-content active">and; with; as; connecting particle (various uses)</span>
<span class="language-content japanese-text">〜と（助詞・接続詞）</span>
<span class="language-content sinhala-text">සහ; සමග; ලෙස; සම්බන්ධක අංශුව (විවිධ භාවිත)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g75-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g75-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">The particle **と (to)** has several key functions:
            <br>1. **"And" (Exhaustive Listing):** Connects two or more nouns in an exhaustive list.
            <br>2. **"With":** Indicates the person or thing one is doing something with.
            <br>3. **"If/When" (Conditional):** Follows a plain form clause (verb, i-adjective, na-adjective + **だ**, or noun + **だ**) to express an automatic or natural result (e.g., "when [A], [B] always happens").
            <br>4. **Quotation Particle:** Marks the end of a quote, thought, or spoken content (**と** 言う/思う/聞く - "say/think/hear that...").
            </span>
            <span class="language-content japanese-text">助詞の **と (to)** にはいくつかの主要な機能があります。
            <br>1. **「と」（そして/並列）:** 2つ以上の名詞を漏れのないリストとして接続します。
            <br>2. **「と」（一緒に）:** 何かを一緒に行う人や物を指します。
            <br>3. **「と」（〜すれば/〜すると）（条件）:** 普通形の節（動詞、い形容詞、な形容詞 + **だ**、または名詞 + **だ**）に続き、自動的または自然な結果を表現します（例: 「[A]のとき、[B]が必ず起こる」）。
            <br>4. **引用助詞:** 引用、考え、または話された内容の終わりを示します（**と** 言う/思う/聞く - 「〜と言う/思う/聞く」）。
            </span>
            <span class="language-content sinhala-text">**තො (to)** යන අංශුවට ප්‍රධාන කාර්යයන් කිහිපයක් ඇත:
            <br>1. **"සහ" (සවිස්තරාත්මක ලැයිස්තුගත කිරීම):** සම්පූර්ණ ලැයිස්තුවක් ලෙස නාම පද දෙකක් හෝ වැඩි ගණනක් සම්බන්ධ කරයි.
            <br>2. **"සමග":** යමෙකු යම් දෙයක් කරන පුද්ගලයා හෝ දේ පෙන්නුම් කරයි.
            <br>3. **"නම්/විට" (කොන්දේසි සහිත):** සරල ස්වරූපයේ වගන්තියක් අනුගමනය කර ස්වයංක්‍රීය හෝ ස්වාභාවික ප්‍රතිඵලයක් ප්‍රකාශ කරයි (උදා: "[A] විට, [B] සෑම විටම සිදු වේ").
            <br>4. **උද්ධෘත අංශුව:** උද්ධෘතයක්, සිතුවිල්ලක් හෝ කථා කරන ලද අන්තර්ගතයක අවසානය සලකුණු කරයි (**තො** ඉයු/ඔමෞ/කිකු - "කියනවා/හිතනවා/අහනවා...").
            </span>
        </p>
        <p>
            <span class="language-content active">**Structure (Nouns/With):** Noun 1 + **と** + Noun 2 / Noun + **と** + Verb.</span>
            <span class="language-content japanese-text">**構造（名詞/一緒に）:** 名詞 1 + **と** + 名詞 2 / 名詞 + **と** + 動詞。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය (නාම පද/සමග):** නාම පදය 1 + **තො** + නාම පදය 2 / නාම පදය + **තො** + ක්‍රියා පදය.</span>
        </p>
        <p>
            <span class="language-content active">**Structure (Conditional):** Clause (Plain form) + **と** + Clause (Result).</span>
            <span class="language-content japanese-text">**構造（条件）:** 節（普通形） + **と** + 節（結果）。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය (කොන්දේසි සහිත):** වගන්තිය (සරල ස්වරූපය) + **තො** + වගන්තිය (ප්‍රතිඵලය).</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">母とデパートに行った。</div>
            <p style="color:#8338ec;font-style:italic;">Haha to depāto ni itta.</p>
            <p>
                <span class="language-content active">I went to the department store with my mother. (With)</span>
                <span class="language-content japanese-text">母とデパートに行った。</span>
                <span class="language-content sinhala-text">මම මගේ මව සමඟ දෙපාර්තමේන්තු ගබඩාවට ගියා.</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">このボタンを押すと、ドアが開く。</div>
            <p style="color:#8338ec;font-style:italic;">Kono botan o osu to, doa ga aku.</p>
            <p>
                <span class="language-content active">When you push this button, the door opens. (Conditional/Automatic Result)</span>
                <span class="language-content japanese-text">このボタンを押すと、ドアが開く。</span>
                <span class="language-content sinhala-text">ඔබ මෙම බොත්තම එබූ විට, දොර විවෘත වේ.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g76')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">76</span>
<div>
<h2 class="section-title japanese-text">とき</h2>
<div class="romaji-text">toki</div>
<p class="section-subtitle">
<span class="language-content active">when; at this time; the time when ~</span>
<span class="language-content japanese-text">〜とき</span>
<span class="language-content sinhala-text">විට; මේ අවස්ථාවේ; ~ වූ කාලය</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g76-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g76-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">The noun **とき (toki)** means **"time"** or **"moment."** When preceded by a clause, it functions as a time-based conjunction, meaning **"when"** or **"at the time of."** The preceding verb, i-adjective, or na-adjective must be in its **plain form**. The plain past tense (**た-form**) is used to refer to an action completed before the main clause, and the non-past (dictionary form) is used for an action concurrent with or after the main clause.</span>
            <span class="language-content japanese-text">名詞の **とき (toki)** は、「**時間**」または「**瞬間**」を意味します。節の後に続く場合、時間的な接続詞として機能し、「**〜するとき**」または「**〜の時に**」という意味になります。先行する動詞、い形容詞、またはな形容詞は**普通形**でなければなりません。普通過去形（**た形**）は主節の前に完了した動作を指すために使われ、非過去形（辞書形）は主節と同時またはその後の動作のために使われます。</span>
            <span class="language-content sinhala-text">**තොකි (toki)** යන නාම පදයේ තේරුම **"කාලය"** හෝ **"මොහොත"** යන්නයි. එය වගන්තියකට පෙර ඇති විට, එය කාලය පදනම් වූ සන්ධි පදයක් ලෙස ක්‍රියා කරයි, එහි තේරුම **"විට"** හෝ **"ඒ අවස්ථාවේ"** යන්නයි. පෙර ඇති ක්‍රියා පදය, i-විශේෂණ පදය, හෝ na-විශේෂණ පදය එහි **සරල ස්වරූපයෙන්** තිබිය යුතුය. සරල අතීත කාලය (**ත-ආකාරය**) ප්‍රධාන වගන්තියට පෙර සම්පූර්ණ කරන ලද ක්‍රියාවක් හැඳින්වීමට භාවිතා කරන අතර, ප්‍රධාන වගන්තියට සමගාමීව හෝ පසුව සිදු කරන ක්‍රියාවක් සඳහා අතීත නොවන (ශබ්දකෝෂ ස්වරූපය) භාවිතා කරයි.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Verb (Plain) / I-Adjective (Plain) / Na-Adjective (Stem) + **な** + **とき** / Noun + **の** + **とき**.</span>
            <span class="language-content japanese-text">**構造:** 動詞（普通形） / い形容詞（普通形） / な形容詞（語幹）+ **な** + **とき** / 名詞 + **の** + **とき**。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** ක්‍රියා පදය (සරල) / I-විශේෂණ පදය (සරල) / Na-විශේෂණ පදය (කඳ) + **නා** + **තොකි** / නාම පදය + **නො** + **තොකි**.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">暇なとき、映画を見ます。</div>
            <p style="color:#8338ec;font-style:italic;">Hima na toki, eiga o mimasu.</p>
            <p>
                <span class="language-content active">When I have free time, I watch movies.</span>
                <span class="language-content japanese-text">暇なとき、映画を見ます。</span>
                <span class="language-content sinhala-text">මට නිදහස් වෙලාවක් ලැබුණාම මම චිත්‍රපට බලනවා.</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">子供のとき、よく遊びました。</div>
            <p style="color:#8338ec;font-style:italic;">Kodomo no toki, yoku asobimashita.</p>
            <p>
                <span class="language-content active">When I was a child, I played a lot.</span>
                <span class="language-content japanese-text">子供のとき、よく遊びました。</span>
                <span class="language-content sinhala-text">මම ළමයෙක් වෙලා ඉන්නකොට, ගොඩක් සෙල්ලම් කළා.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g77')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">77</span>
<div>
<h2 class="section-title japanese-text">とても</h2>
<div class="romaji-text">totemo</div>
<p class="section-subtitle">
<span class="language-content active">very; awfully; exceedingly​</span>
<span class="language-content japanese-text">とても</span>
<span class="language-content sinhala-text">ඉතා; පුදුම විදිහට; අතිශයින්ම</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g77-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g77-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">**とても (totemo)** is a common adverb used to express a high degree or intensity of a quality or action, meaning **"very,"** **"extremely,"** or **"awfully."** It can modify verbs, i-adjectives, and na-adjectives. It is generally interchangeable with **大変 (taihen)** and **すごく (sugoku)**, although **すごく** is more casual.</span>
            <span class="language-content japanese-text">**とても (totemo)** は、性質や動作の度合いや強度が高いことを表すために使われる一般的な副詞で、「**とても**」「**非常に**」「**大変**」という意味です。動詞、い形容詞、な形容詞を修飾できます。一般的に **大変 (taihen)** や **すごく (sugoku)** と互換性がありますが、**すごく** はよりカジュアルです。</span>
            <span class="language-content sinhala-text">**තොතෙමො (totemo)** යනු ගුණාංගයක හෝ ක්‍රියාවක ඉහළ මට්ටමක් හෝ තීව්‍රතාවයක් ප්‍රකාශ කිරීමට භාවිතා කරන පොදු ක්‍රියා විශේෂණ පදයක් වන අතර, එහි තේරුම **"ඉතා,"** **"අතිශයින්ම,"** හෝ **"පුදුම විදිහට"** යන්නයි. එය ක්‍රියා පද, i-විශේෂණ පද, සහ na-විශේෂණ පද වෙනස් කළ හැක. එය සාමාන්‍යයෙන් **තායිහෙන් (taihen)** සහ **සුගොකු (sugoku)** සමඟ අන්‍යෝන්‍ය වශයෙන් හුවමාරු කළ හැකි නමුත්, **සුගොකු** වඩා අනියම් වේ.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** **とても** + Adjective / Verb.</span>
            <span class="language-content japanese-text">**構造:** **とても** + 形容詞 / 動詞。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** **තොතෙමො** + විශේෂණ පදය / ක්‍රියා පදය.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">その映画はとても面白かった。</div>
            <p style="color:#8338ec;font-style:italic;">Sono eiga wa totemo omoshirokatta.</p>
            <p>
                <span class="language-content active">That movie was very interesting.</span>
                <span class="language-content japanese-text">その映画はとても面白かった。</span>
                <span class="language-content sinhala-text">ඒ චිත්‍රපටය ඉතා රසවත් විය.</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">日本語はとても難しい。</div>
            <p style="color:#8338ec;font-style:italic;">Nihongo wa totemo muzukashii.</p>
            <p>
                <span class="language-content active">Japanese is very difficult.</span>
                <span class="language-content japanese-text">日本語はとても難しい。</span>
                <span class="language-content sinhala-text">ජපන් භාෂාව ඉතා අමාරුයි.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g78')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">78</span>
<div>
<h2 class="section-title japanese-text">つもり</h2>
<div class="romaji-text">tsumori</div>
<p class="section-subtitle">
<span class="language-content active">plan to ~; intend to ~ (intention/plan)</span>
<span class="language-content japanese-text">〜つもり</span>
<span class="language-content sinhala-text">~ කිරීමට සැලසුම් කරනවා; ~ කිරීමට අදහස් කරනවා (අභිප්‍රාය/සැලැස්ම)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g78-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g78-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">The noun **つもり (tsumori)** expresses the speaker's **intention or plan** to do (or not do) something, meaning **"I intend to ~"** or **"I plan to ~."** It follows the plain form of a verb. For a negative intention, the plain negative form (**ない-form**) is used before **つもり (tsumori)**.</span>
            <span class="language-content japanese-text">名詞の **つもり (tsumori)** は、話し手の何かをする（またはしない）という**意図や計画**を表現し、「**〜するつもりです**」または「**〜を計画しています**」という意味になります。動詞の普通形に続きます。否定の意図を表す場合は、**つもり (tsumori)** の前に否定の普通形（**ない形**）が使われます。</span>
            <span class="language-content sinhala-text">**ත්සුමොරි (tsumori)** යන නාම පදය කථිකයාගේ යමක් කිරීමට (හෝ නොකිරීමට) ඇති **අභිප්‍රාය හෝ සැලැස්ම** ප්‍රකාශ කරන අතර, එහි තේරුම **"මම ~ කිරීමට අදහස් කරනවා"** හෝ **"මම ~ කිරීමට සැලසුම් කරනවා"** යන්නයි. එය ක්‍රියා පදයක සරල ස්වරූපය අනුගමනය කරයි. ඍණාත්මක අභිප්‍රායක් සඳහා, **ත්සුමොරි (tsumori)** ට පෙර සරල ඍණ ස්වරූපය (**නායි-form**) භාවිතා වේ.</span>
        </p>
        <p>
            <span class="language-content active">**Structure (Affirmative):** Verb Plain Form + **つもりです**.</span>
            <span class="language-content japanese-text">**構造（肯定）:** 動詞の普通形 + **つもりです**。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය (ස්ථිර):** ක්‍රියා පදයේ සරල ස්වරූපය + **ත්සුමොරි දේසු**.</span>
        </p>
        <p>
            <span class="language-content active">**Structure (Negative):** Verb Plain Negative Form + **つもりです**.</span>
            <span class="language-content japanese-text">**構造（否定）:** 動詞の普通形否定 + **つもりです**。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය (ඍණ):** ක්‍රියා පදයේ සරල ඍණ ස්වරූපය + **ත්සුමොරි දේසු**.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">来年、日本へ行くつもりです。</div>
            <p style="color:#8338ec;font-style:italic;">Rainen, Nihon e iku tsumori desu.</p>
            <p>
                <span class="language-content active">I plan to go to Japan next year. (Affirmative)</span>
                <span class="language-content japanese-text">来年、日本へ行くつもりです。</span>
                <span class="language-content sinhala-text">මම ලබන අවුරුද්දේ ජපානයට යන්න සැලසුම් කරනවා.</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">もう二度と食べないつもりです。</div>
            <p style="color:#8338ec;font-style:italic;">Mō nido to tabenai tsumori desu.</p>
            <p>
                <span class="language-content active">I don't intend to eat it ever again. (Negative)</span>
                <span class="language-content japanese-text">もう二度と食べないつもりです。</span>
                <span class="language-content sinhala-text">මම ආයෙත් කවදාවත් ඒක කන්න අදහස් කරන්නේ නැහැ.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g79')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">79</span>
<div>
<h2 class="section-title japanese-text">は</h2>
<div class="romaji-text">wa</div>
<p class="section-subtitle">
<span class="language-content active">topic marker</span>
<span class="language-content japanese-text">は（主題の助詞）</span>
<span class="language-content sinhala-text">විෂය සලකුණ</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g79-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g79-content">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

    <p>
        <span class="language-content active">The particle **は (wa)** (written with the character **は**) is the most fundamental particle in Japanese. It marks the **topic** of the sentence, which is what the sentence is about. The topic often coincides with the subject, but can also mark a contrasting element, or draw focus to what follows. Unlike the subject particle **が (ga)**, the topic marked by **は** can be the subject, the object, or any other element of the sentence.</span>
        <span class="language-content japanese-text">助詞の **は (wa)**（文字 **は** で書かれます）は、日本語で最も基本的な助詞です。それは、文が何についてであるかという**主題**をマークします。主題はしばしば主語と一致しますが、対照的な要素をマークしたり、後に続くものに焦点を当てたりすることもあります。主語の助詞 **が (ga)** とは異なり、**は** によってマークされた主題は、主語、目的語、または文の他の任意の要素である可能性があります。</span>
        <span class="language-content sinhala-text">**වා (wa)** (එය **හා** අක්ෂරයෙන් ලියයි) යන අංශුව ජපන් භාෂාවේ වඩාත් මූලික අංශුව වේ. එය වාක්‍යයේ **විෂයය** සලකුණු කරයි, එනම් වාක්‍යය කුමක් ගැනද යන්නයි. විෂයය බොහෝ විට කර්තෘට අනුරූප වේ, නමුත් එය ප්‍රතිවිරුද්ධ අංගයක් සලකුණු කිරීමට හෝ ඊට පසුව ඇති දේ වෙත අවධානය යොමු කිරීමට ද හැකිය. කර්තෘ අංශුව වන **ගා (ga)** මෙන් නොව, **වා** මඟින් සලකුණු කරන ලද විෂයය කර්තෘ, කර්මය හෝ වාක්‍යයේ වෙනත් ඕනෑම අංගයක් විය හැක.</span>
    </p>
    <p>
        <span class="language-content active">**Structure:** Noun / Phrase + **は** + Predicate.</span>
        <span class="language-content japanese-text">**構造:** 名詞 / フレーズ + **は** + 述語。</span>
        <span class="language-content sinhala-text">**ව්‍යුහය:** නාම පදය / වාක්‍ය ඛණ්ඩය + **වා** + කර්තෘ පදය.</span>
    </p>

    <h3 style="color:#8338ec;margin-bottom:15px;">
        <span class="language-content active">Examples:</span>
        <span class="language-content japanese-text">例文：</span>
        <span class="language-content sinhala-text">උදාහරණ:</span>
    </h3>
    <div class="sound-group">
        <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">私は学生です。</div>
        <p style="color:#8338ec;font-style:italic;">Watashi wa gakusei desu.</p>
        <p>
            <span class="language-content active">I am a student. (Topic = I)</span>
            <span class="language-content japanese-text">私は学生です。</span>
            <span class="language-content sinhala-text">මම ශිෂ්‍යයෙකි.</span>
        </p>
    </div>
    <div class="sound-group">
        <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">コーヒーは飲みますが、紅茶は飲みません。</div>
        <p style="color:#8338ec;font-style:italic;">Kōhī wa nomimasu ga, kōcha wa nomimasen.</p>
        <p>
            <span class="language-content active">I drink coffee (but not tea). (Contrasting Topic)</span>
            <span class="language-content japanese-text">コーヒーは飲みますが、紅茶は飲みません。</span>
            <span class="language-content sinhala-text">මම කෝපි බොනවා, නමුත් තේ බොන්නේ නැහැ.</span>
        </p>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g80')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">80</span>
<div>
<h2 class="section-title japanese-text">は〜より・・・です</h2>
<div class="romaji-text">wa ~yori... desu</div>
<p class="section-subtitle">
<span class="language-content active">[Noun-1] is more ~ than [Noun-2] (comparison)</span>
<span class="language-content japanese-text">は〜より・・・です（比較）</span>
<span class="language-content sinhala-text">[නාම පදය 1] [නාම පදය 2] ට වඩා ~ වේ (සංසන්දනය)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g80-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g80-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">This structure is the basic way to express a **comparison** between two items in Japanese. **Noun 1** is the topic of the sentence (marked by **は**), and **Noun 2** is the item it is being compared to (marked by **より (yori)** - 'than'). The predicate (**...です**) describes the quality that Noun 1 possesses to a greater degree.</span>
            <span class="language-content japanese-text">この構造は、日本語で2つの項目間の**比較**を表現する基本的な方法です。**名詞 1** は文の主題（**は** でマークされています）であり、**名詞 2** は比較対象の項目（**より (yori)** - 「〜よりも」でマークされています）です。述語（**...です**）は、名詞 1がより大きな程度で持っている性質を説明します。</span>
            <span class="language-content sinhala-text">මෙම ව්‍යුහය ජපන් භාෂාවෙන් අයිතම දෙකක් අතර **සංසන්දනයක්** ප්‍රකාශ කිරීමේ මූලික ක්‍රමයයි. **නාම පදය 1** යනු වාක්‍යයේ විෂයය වන අතර (**වා** මඟින් සලකුණු කර ඇත), **නාම පදය 2** යනු එය සංසන්දනය කරන අයිතමයයි (**යොරි (yori)** - 'වඩා' මඟින් සලකුණු කර ඇත). කර්තෘ පදය (**... දේසු**) නාම පදය 1 වැඩි වශයෙන් සතු ගුණාත්මකභාවය විස්තර කරයි.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Noun 1 + **は** + Noun 2 + **より** + Adjective + **です**.</span>
            <span class="language-content japanese-text">**構造:** 名詞 1 + **は** + 名詞 2 + **より** + 形容詞 + **です**。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** නාම පදය 1 + **වා** + නාම පදය 2 + **යොරි** + විශේෂණ පදය + **දේසු**.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">東京は大阪より大きいです。</div>
            <p style="color:#8338ec;font-style:italic;">Tōkyō wa Ōsaka yori ōkii desu.</p>
            <p>
                <span class="language-content active">Tokyo is bigger than Osaka.</span>
                <span class="language-content japanese-text">東京は大阪より大きいです。</span>
                <span class="language-content sinhala-text">ටෝකියෝ ඔසාකා වලට වඩා විශාලයි.</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">日本語は英語より難しいです。</div>
            <p style="color:#8338ec;font-style:italic;">Nihongo wa Eigo yori muzukashii desu.</p>
            <p>
                <span class="language-content active">Japanese is more difficult than English.</span>
                <span class="language-content japanese-text">日本語は英語より難しいです。</span>
                <span class="language-content sinhala-text">ජපන් භාෂාව ඉංග්‍රීසි භාෂාවට වඩා අමාරුයි.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g81')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">81</span>
<div>
<h2 class="section-title japanese-text">は どうですか</h2>
<div class="romaji-text">wa dō desu ka</div>
<p class="section-subtitle">
<span class="language-content active">how about ~; how is ~ (making a suggestion or asking for an opinion)</span>
<span class="language-content japanese-text">〜は どうですか（提案・意見を尋ねる）</span>
<span class="language-content sinhala-text">~ ගැන කුමක් සිතන්නේද; ~ කෙසේද (යෝජනාවක් කිරීම හෝ මතයක් විමසීම)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g81-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g81-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">The phrase **は どうですか (wa dō desu ka)** is used to **ask for an opinion** about a particular thing or person, or to **make a suggestion or offer**. It translates as **"How about ~?"** or **"How is ~?"** It is a combination of the topic particle **は (wa)** and the phrase **どうですか (dō desu ka)** (How is it?).</span>
            <span class="language-content japanese-text">このフレーズ **は どうですか (wa dō desu ka)** は、特定のものや人についての**意見を尋ねる**ため、または**提案や申し出をする**ために使われます。「**〜はどうですか**」という意味になります。これは、主題の助詞 **は (wa)** とフレーズ **どうですか (dō desu ka)**（いかがですか）を組み合わせたものです。</span>
            <span class="language-content sinhala-text">**වා දෝ දේසු කා (wa dō desu ka)** යන වාක්‍ය ඛණ්ඩය භාවිතා කරන්නේ යම්කිසි දෙයක් හෝ පුද්ගලයෙකු පිළිබඳව **මතයක් විමසීමට** හෝ **යෝජනාවක් හෝ දීමනාවක් කිරීමට** ය. එහි තේරුම **"~ ගැන කුමක් සිතන්නේද?"** හෝ **"~ කෙසේද?"** යන්නයි. එය විෂය අංශුව **වා (wa)** සහ වාක්‍ය ඛණ්ඩය **දෝ දේසු කා (dō desu ka)** (කෙසේද?) යන ඒවායේ සංයෝජනයකි.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Noun / Phrase + **は どうですか**.</span>
            <span class="language-content japanese-text">**構造:** 名詞 / フレーズ + **は どうですか**。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** නාම පදය / වාක්‍ය ඛණ්ඩය + **වා දෝ දේසු කා**.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">この新しいパソコンは、どうですか。</div>
            <p style="color:#8338ec;font-style:italic;">Kono atarashii pasokon wa, dō desu ka.</p>
            <p>
                <span class="language-content active">How is this new computer? (Asking Opinion)</span>
                <span class="language-content japanese-text">この新しいパソコンは、どうですか。</span>
                <span class="language-content sinhala-text">මේ අලුත් පරිගණකය කෙසේද?</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">お茶はどうですか。</div>
            <p style="color:#8338ec;font-style:italic;">Ocha wa dō desu ka.</p>
            <p>
                <span class="language-content active">How about some tea? (Making a Suggestion/Offer)</span>
                <span class="language-content japanese-text">お茶はどうですか。</span>
                <span class="language-content sinhala-text">තේ එකක් ගැන කුමක් සිතන්නේද?</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g82')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">82</span>
<div>
<h2 class="section-title japanese-text">や</h2>
<div class="romaji-text">ya</div>
<p class="section-subtitle">
<span class="language-content active">and; or; connecting particle (non-exhaustive list)</span>
<span class="language-content japanese-text">〜や（並列の助詞）</span>
<span class="language-content sinhala-text">සහ; හෝ; සම්බන්ධක අංශුව (සම්පූර්ණ නොවන ලැයිස්තුව)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g82-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g82-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">The particle **や (ya)** is used to list two or more nouns as **examples**, implying that there are other similar items that are not explicitly mentioned. It translates to **"and,"** **"or,"** or **"such things as ~."** The non-exhaustive nature of the list is often reinforced by adding **など (nado)** or **なんか (nanka)** (etc.) at the end. It is different from **と (to)** (G75), which implies an exhaustive list.</span>
            <span class="language-content japanese-text">助詞の **や (ya)** は、2つ以上の名詞を**例として**リストするために使われ、明示的に言及されていない他の同様の項目があることを示唆します。「**〜や〜など**」という意味になります。リストの非網羅的な性質は、しばしば最後に **など (nado)** や **なんか (nanka)**（など）を追加することで強化されます。これは、網羅的なリストを意味する **と (to)**（G75）とは異なります。</span>
            <span class="language-content sinhala-text">**යා (ya)** යන අංශුව භාවිතා කරන්නේ නාම පද දෙකක් හෝ වැඩි ගණනක් **උදාහරණ ලෙස** ලැයිස්තුගත කිරීමට වන අතර, පැහැදිලිව සඳහන් නොකළ වෙනත් සමාන අයිතම ඇති බව ඇඟවීමට ය. එහි තේරුම **"සහ,"** **"හෝ,"** හෝ **"~ වැනි දේවල්"** යන්නයි. ලැයිස්තුවේ සම්පූර්ණ නොවන ස්වභාවය බොහෝ විට අවසානයේ **නාදො (nado)** හෝ **නන්කා (nanka)** (ආදිය) එකතු කිරීමෙන් තහවුරු කරයි. මෙය සම්පූර්ණ ලැයිස්තුවක් ඇඟවුම් කරන **තො (to)** (G75) වලින් වෙනස් වේ.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Noun 1 + **や** + Noun 2 (+ Noun 3...) (+ **など**).</span>
            <span class="language-content japanese-text">**構造:** 名詞 1 + **や** + 名詞 2 （+ 名詞 3...） （+ **など**）。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** නාම පදය 1 + **යා** + නාම පදය 2 (+ නාම පදය 3...) (+ **නාදො**).</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">机の上に本やノートがあります。</div>
            <p style="color:#8338ec;font-style:italic;">Tsukue no ue ni hon ya nōto ga arimasu.</p>
            <p>
                <span class="language-content active">There are books and notebooks (among other things) on the desk.</span>
                <span class="language-content japanese-text">机の上に本やノートがあります。</span>
                <span class="language-content sinhala-text">මේසය මත පොත් සහ සටහන් පොත් (වෙනත් දේවල් අතර) ඇත.</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">スーパーで野菜や果物などを買いました。</div>
            <p style="color:#8338ec;font-style:italic;">Sūpā de yasai ya kudamono nado o kaimashita.</p>
            <p>
                <span class="language-content active">I bought vegetables, fruit, and such at the supermarket.</span>
                <span class="language-content japanese-text">スーパーで野菜や果物などを買いました。</span>
                <span class="language-content sinhala-text">මම සුපිරි වෙළඳසැලෙන් එළවළු, පලතුරු සහ ඒ වගේ දේවල් මිලදී ගත්තා.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g83')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">83</span>
<div>
<h2 class="section-title japanese-text">よ</h2>
<div class="romaji-text">yo</div>
<p class="section-subtitle">
<span class="language-content active">you know; emphasis (ending particle)</span>
<span class="language-content japanese-text">〜よ（終助詞）</span>
<span class="language-content sinhala-text">ඔබ දන්නවා; අවධාරණය (අවසාන අංශුව)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g83-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g83-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">The sentence-ending particle **よ (yo)** is used to **emphasize** a statement or to convey information the speaker believes is new to the listener. It adds a sense of conviction, assertion, or strong assurance. It can also be used to give a warning or strong advice. It is typically used with the plain form of a verb/adjective, but follows the polite form (**ます/です**) in formal speech.</span>
            <span class="language-content japanese-text">終助詞の **よ (yo)** は、発言を**強調する**ため、または話し手が聞き手にとって新しいと信じる情報を伝えるために使われます。確信、断言、または強い保証の感覚を加えます。また、警告や強いアドバイスを与えるためにも使われます。通常、動詞/形容詞の普通形と共に使われますが、丁寧な話し方では丁寧形（**ます/です**）の後に続きます。</span>
            <span class="language-content sinhala-text">වාක්‍ය අවසන් අංශුව **යො (yo)** භාවිතා කරන්නේ ප්‍රකාශයක් **අවධාරණය** කිරීමට හෝ කථිකයා සවන්දෙන්නාට අලුත් යැයි විශ්වාස කරන තොරතුරු ප්‍රකාශ කිරීමට ය. එය ස්ථිරභාවය, ප්‍රකාශ කිරීම හෝ ශක්තිමත් සහතිකයක් පිළිබඳ හැඟීමක් එක් කරයි. එය අනතුරු ඇඟවීමක් හෝ දැඩි උපදෙස් දීමට ද භාවිතා කළ හැකිය. එය සාමාන්‍යයෙන් ක්‍රියා පදයේ/විශේෂණ පදයේ සරල ස්වරූපය සමඟ භාවිතා කරන නමුත් විධිමත් කථාවේදී ආචාරශීලී ස්වරූපය (**මසු/දේසු**) අනුගමනය කරයි.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Verb/Adjective/Noun (Plain or Polite form) + **よ**.</span>
            <span class="language-content japanese-text">**構造:** 動詞/形容詞/名詞（普通形または丁寧形） + **よ**。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** ක්‍රියා පදය/විශේෂණ පදය/නාම පදය (සරල හෝ ආචාරශීලී ස්වරූපය) + **යො**.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">明日は雨が降るよ。</div>
            <p style="color:#8338ec;font-style:italic;">Ashita wa ame ga furu yo.</p>
            <p>
                <span class="language-content active">It's going to rain tomorrow, you know. (Providing Information)</span>
                <span class="language-content japanese-text">明日は雨が降るよ。</span>
                <span class="language-content sinhala-text">හෙට වැස්සක් එනවා, ඔබ දන්නවා.</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">それはダメだよ。</div>
            <p style="color:#8338ec;font-style:italic;">Sore wa dame da yo.</p>
            <p>
                <span class="language-content active">That is absolutely not allowed. (Strong Assertion/Warning)</span>
                <span class="language-content japanese-text">それはダメだよ。</span>
                <span class="language-content sinhala-text">ඒක කිසිසේත් අවසර නැහැ.</span>
            </p>
        </div>
    </div>
</div>
</div>

<div class="character-section">
<div class="toggle-header" onclick="toggleGrammar('g84')">
<div style="display:flex;align-items:center;flex:1;gap:1rem;">
<span class="grammar-number">84</span>
<div>
<h2 class="section-title japanese-text">より～ほうが</h2>
<div class="romaji-text">yori ~hō ga</div>
<p class="section-subtitle">
<span class="language-content active">[Noun-2] is more ~ than [Noun-1] / [Option B] is more than [Option A] (comparison)</span>
<span class="language-content japanese-text">より～ほうが（比較）</span>
<span class="language-content sinhala-text">[නාම පදය 2] [නාම පදය 1] ට වඩා ~ වේ (සංසන්දනය)</span>
</p>
</div>
</div>
<span class="toggle-icon collapsed" id="g84-icon">▼</span>
</div>
<div class="grammar-content collapsed" id="g84-content">
<div class="grammar-description">
<h3 style="color:#8338ec;margin-bottom:15px;">
<span class="language-content active">Description:</span>
<span class="language-content japanese-text">解説：</span>
<span class="language-content sinhala-text">විස්තරය:</span>
</h3>

        <p>
            <span class="language-content active">This structure is an alternative and very common way to express a **comparison** in Japanese, often focusing on the **preferred choice or superior option**. It means **"~ is more... than..."** (where the item with the better quality is the one marked by **ほうが (hō ga)**, meaning 'side' or 'way'). This is the inverse structure of **は〜より...です (wa ~yori... desu)** (G80), where the topic is the superior item.</span>
            <span class="language-content japanese-text">この構造は、日本語で**比較**を表現するもう一つの非常に一般的な方法であり、しばしば**好ましい選択肢や優れた選択肢**に焦点を当てます。「**〜よりも...の方が良い**」という意味になります（ここで、より良い性質を持つ項目が、**ほうが (hō ga)**（「方」または「やり方」の意）でマークされたものです）。これは、主題が優れた項目である **は〜より...です (wa ~yori... desu)** (G80) の逆の構造です。</span>
            <span class="language-content sinhala-text">මෙම ව්‍යුහය ජපන් භාෂාවෙන් **සංසන්දනයක්** ප්‍රකාශ කිරීමේ විකල්ප සහ ඉතා සුලභ ක්‍රමයකි, එය බොහෝ විට **වඩාත් කැමති තේරීම හෝ උසස් විකල්පය** කෙරෙහි අවධානය යොමු කරයි. එහි තේරුම **"~ ට වඩා ... වඩා හොඳයි..."** යන්නයි (මෙහිදී වඩා හොඳ ගුණාත්මක භාවයක් ඇති අයිතමය **හෝ ගා (hō ga)** 'පැත්ත' හෝ 'ක්‍රමය' යනුවෙන් සලකුණු කර ඇත). මෙය උසස් අයිතමය වන **වා ~යොරි... දේසු (wa ~yori... desu)** (G80) හි ප්‍රතිලෝම ව්‍යුහයයි.</span>
        </p>
        <p>
            <span class="language-content active">**Structure:** Noun 1 + **より** + Noun 2 + **のほうが** + Adjective + **です**.</span>
            <span class="language-content japanese-text">**構造:** 名詞 1 + **より** + 名詞 2 + **のほうが** + 形容詞 + **です**。</span>
            <span class="language-content sinhala-text">**ව්‍යුහය:** නාම පදය 1 + **යොරි** + නාම පදය 2 + **නො හෝ ගා** + විශේෂණ පදය + **දේසු**.</span>
        </p>
    </div>
    <div class="pronunciation-guide">
        <h3 style="color:#8338ec;margin-bottom:15px;">
            <span class="language-content active">Examples:</span>
            <span class="language-content japanese-text">例文：</span>
            <span class="language-content sinhala-text">උදාහරණ:</span>
        </h3>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">りんごよりバナナのほうが好きです。</div>
            <p style="color:#8338ec;font-style:italic;">Ringo yori banana no hō ga suki desu.</p>
            <p>
                <span class="language-content active">I like bananas more than apples. (Literally: Compared to apples, the banana side is preferable.)</span>
                <span class="language-content japanese-text">りんごよりバナナのほうが好きです。</span>
                <span class="language-content sinhala-text">මම ඇපල් වලට වඩා කෙසෙල් වලට කැමතියි.</span>
            </p>
        </div>
        <div class="sound-group">
            <div style="font-size:1.5rem;font-family:'Noto Sans JP';margin-bottom:0.5rem;">車より電車のほうが速いです。</div>
            <p style="color:#8338ec;font-style:italic;">Kuruma yori densha no hō ga hayai desu.</p>
            <p>
                <span class="language-content active">Trains are faster than cars.</span>
                <span class="language-content japanese-text">車より電車のほうが速いです。</span>
                <span class="language-content sinhala-text">කාර් වලට වඩා දුම්රිය වේගවත් වේ.</span>
            </p>
        </div>
    </div>
</div>
</div>


       
    <!-- 🔹 Start Quiz Full Width -->
<div class="d-flex justify-content-center mb-4">
    <button class="btn btn-success btn-lg w-100" style="max-width: 400px;" onclick="startQuiz()">
        Start Quiz
    </button>
</div>
<!-- 🔹 Navigation Buttons -->
<div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
    <!-- Back 10–20 -->
    <button class="btn btn-warning btn-lg" onclick="goToPage('n5grammar1-50.php')">
        Back 1-50
    </button>



   
</div>

<script>
    function expandAllGrammar() {
        document.querySelectorAll('.grammar-content').forEach(el => el.classList.remove('collapsed'));
    }

    function collapseAllGrammar() {
        document.querySelectorAll('.grammar-content').forEach(el => el.classList.add('collapsed'));
    }

    function startQuiz() {
        window.location.href = 'quiz.php';
    }

    function goToPage(url) {
        window.location.href = url;
    }
</script>

<!-- Bootstrap CDN (if not already added) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>



        </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Language handling
        const currentLang = '<?php echo $lang; ?>';
        
        function updateLanguageContent() {
            document.querySelectorAll('.language-content').forEach(el => {
                el.classList.remove('active');
            });
            
            if (currentLang === 'ja') {
                document.querySelectorAll('.language-content.japanese-text').forEach(el => {
                    el.classList.add('active');
                });
            } else if (currentLang === 'si') {
                document.querySelectorAll('.language-content.sinhala-text').forEach(el => {
                    el.classList.add('active');
                });
            } else {
                document.querySelectorAll('.language-content').forEach(el => {
                    if (!el.classList.contains('japanese-text') && !el.classList.contains('sinhala-text')) {
                        el.classList.add('active');
                    }
                });
            }
        }
        
        // Toggle grammar section
        function toggleGrammar(grammarId) {
            const content = document.getElementById(grammarId + '-content');
            const icon = document.getElementById(grammarId + '-icon');
            
            if (content.classList.contains('collapsed')) {
                content.classList.remove('collapsed');
                icon.classList.remove('collapsed');
            } else {
                content.classList.add('collapsed');
                icon.classList.add('collapsed');
            }
        }
        
        // Expand all sections
        function expandAll() {
            document.querySelectorAll('.grammar-content').forEach(content => {
                content.classList.remove('collapsed');
            });
            document.querySelectorAll('.toggle-icon').forEach(icon => {
                icon.classList.remove('collapsed');
            });
        }
        
        // Collapse all sections
        function collapseAll() {
            document.querySelectorAll('.grammar-content').forEach(content => {
                content.classList.add('collapsed');
            });
            document.querySelectorAll('.toggle-icon').forEach(icon => {
                icon.classList.add('collapsed');
            });
        }
        
        // Smooth scroll animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, {
            threshold: 0.1
        });
        
        document.querySelectorAll('.character-section, .stats-card').forEach(section => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(20px)';
            section.style.transition = 'all 0.6s ease';
            observer.observe(section);
        });
        
        // Stats counter animation
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statNumber = entry.target.querySelector('.stat-number');
                    const text = statNumber.textContent;
                    const hasPlus = text.includes('+');
                    const finalNumber = parseInt(text.replace('+', ''));
                    let currentNumber = 0;
                    const increment = finalNumber / 30;
                    
                    const counter = setInterval(() => {
                        currentNumber += increment;
                        if (currentNumber >= finalNumber) {
                            statNumber.textContent = finalNumber + (hasPlus ? '+' : '');
                            clearInterval(counter);
                        } else {
                            statNumber.textContent = Math.floor(currentNumber) + (hasPlus ? '+' : '');
                        }
                    }, 30);
                    
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        document.querySelectorAll('.stats-card').forEach(card => {
            statsObserver.observe(card);
        });
        
        // Page load animation
        window.addEventListener('load', function() {
            document.body.style.opacity = '0';
            setTimeout(() => {
                document.body.style.transition = 'opacity 0.5s ease';
                document.body.style.opacity = '1';
            }, 100);
        });
        
        // Initialize language content
        updateLanguageContent();
        
        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Press 'E' to expand all
            if (e.key === 'e' || e.key === 'E') {
                if (!e.target.matches('input, textarea')) {
                    expandAll();
                }
            }
            // Press 'C' to collapse all
            if (e.key === 'c' || e.key === 'C') {
                if (!e.target.matches('input, textarea')) {
                    collapseAll();
                }
            }
        });
        
        // Add click sound effect for grammar sections (optional)
        document.querySelectorAll('.toggle-header').forEach(header => {
            header.addEventListener('click', function() {
                // Visual feedback
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 100);
            });
        });
        
        // Auto-scroll to section if URL hash exists
        window.addEventListener('load', function() {
            if (window.location.hash) {
                const targetId = window.location.hash.substring(1);
                const targetElement = document.getElementById(targetId + '-content');
                if (targetElement) {
                    // Expand the section
                    targetElement.classList.remove('collapsed');
                    document.getElementById(targetId + '-icon').classList.remove('collapsed');
                    
                    // Scroll to it
                    setTimeout(() => {
                        targetElement.parentElement.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'start' 
                        });
                    }, 300);
                }
            }
        });
        
        // Add copy functionality to example sentences (double-click)
        document.querySelectorAll('.sound-group').forEach(group => {
            group.addEventListener('dblclick', function() {
                const japaneseText = this.querySelector('div[style*="Noto Sans JP"]');
                if (japaneseText) {
                    const text = japaneseText.textContent;
                    navigator.clipboard.writeText(text).then(() => {
                        const original = this.style.background;
                        this.style.background = 'linear-gradient(135deg, rgba(6, 255, 165, 0.2), rgba(58, 134, 255, 0.2))';
                        setTimeout(() => {
                            this.style.background = original;
                        }, 500);
                    }).catch(err => {
                        console.log('Copy failed:', err);
                    });
                }
            });
        });
        
        // Progress tracker - store opened sections in localStorage
        function trackProgress(grammarId) {
            const opened = JSON.parse(localStorage.getItem('grammarProgress') || '[]');
            if (!opened.includes(grammarId)) {
                opened.push(grammarId);
                localStorage.setItem('grammarProgress', JSON.stringify(opened));
            }
        }
        
        // Update toggle function to track progress
        const originalToggle = window.toggleGrammar;
        window.toggleGrammar = function(grammarId) {
            originalToggle(grammarId);
            const content = document.getElementById(grammarId + '-content');
            if (!content.classList.contains('collapsed')) {
                trackProgress(grammarId);
            }
        };
        
        // Add tooltip for double-click hint
        const firstSoundGroup = document.querySelector('.sound-group');
        if (firstSoundGroup) {
            firstSoundGroup.title = 'Double-click to copy Japanese text';
        }
        
        // Add smooth transitions to all collapsible elements
        document.querySelectorAll('.grammar-content').forEach(content => {
            content.style.transition = 'max-height 0.5s ease, padding 0.5s ease';
        });
        
        // Print-friendly: expand all before printing
        window.addEventListener('beforeprint', function() {
            const wasCollapsed = [];
            document.querySelectorAll('.grammar-content').forEach((content, index) => {
                wasCollapsed[index] = content.classList.contains('collapsed');
                content.classList.remove('collapsed');
            });
            
            // Store state to restore after printing
            window.printState = wasCollapsed;
        });
        
        window.addEventListener('afterprint', function() {
            if (window.printState) {
                document.querySelectorAll('.grammar-content').forEach((content, index) => {
                    if (window.printState[index]) {
                        content.classList.add('collapsed');
                    }
                });
            }
        });
    </script>
    <script src="tracking.js"></script>
</body>
</html>

<?php include 'footer.php'; ?><?php
