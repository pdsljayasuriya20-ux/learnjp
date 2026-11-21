<?php require_once __DIR__ . '/includes/header.php'; ?>

        <?php
        // ⚠️ Oyage login session key eka methana wenas karanna (user_id / logged_in / etc.)
        $isLoggedIn = !empty($_SESSION['user_id']);

        // Driving License module -> direct link to the Menkyoo.jp website
        $drivingUrl = 'https://menkyoo.jp/';

        // Dictionary section texts (si / en / ja)
        $__ixLang = (isset($currentLang) && in_array($currentLang, ['si', 'en', 'ja'], true)) ? $currentLang : 'si';
        $IX = [
            'si' => [
                'tag'   => 'නව',
                'title' => 'JLPT වචන කෝෂය',
                'desc'  => 'N5 – N1 වචන 5,000+ ක්. ජපන්, romaji, සිංහල හෝ Singlish වලින් සොයන්න.',
                'ph'    => 'せいしゅん · seishun · තරුණ',
                'open'  => 'කෝෂය විවෘත කරන්න',
                'try'   => 'උදාහරණ',
            ],
            'en' => [
                'tag'   => 'New',
                'title' => 'JLPT Dictionary',
                'desc'  => '5,000+ words from N5 to N1. Search in Japanese, romaji, Sinhala or Singlish.',
                'ph'    => 'せいしゅん · seishun · තරුණ',
                'open'  => 'Open dictionary',
                'try'   => 'Try',
            ],
            'ja' => [
                'tag'   => 'NEW',
                'title' => 'JLPT 単語辞典',
                'desc'  => 'N5〜N1の単語5,000語以上。日本語・ローマ字・シンハラ語で検索できます。',
                'ph'    => 'せいしゅん · seishun · තරුණ',
                'open'  => '辞典を開く',
                'try'   => '例',
            ],
        ][$__ixLang];
        ?>

<!-- =====================================================
     NEUMORPHISM THEME (mobile-first, responsive)
     ===================================================== -->
<style>
    :root {
        --nm-bg: #e6e9f2;
        --nm-light: #ffffff;
        --nm-dark: #c3c9dc;
        --nm-text: #2d3250;
        --nm-muted: #7a8099;
        --nm-brand: #4f46e5;
        --nm-brand-2: #7c6cf5;
        --nm-radius: 24px;
        --nm-radius-sm: 16px;

        --nm-raised: 8px 8px 18px var(--nm-dark), -8px -8px 18px var(--nm-light);
        --nm-raised-sm: 5px 5px 11px var(--nm-dark), -5px -5px 11px var(--nm-light);
        --nm-inset: inset 5px 5px 11px var(--nm-dark), inset -5px -5px 11px var(--nm-light);
        --nm-inset-sm: inset 3px 3px 7px var(--nm-dark), inset -3px -3px 7px var(--nm-light);
    }

    body {
        background: var(--nm-bg) !important;
        color: var(--nm-text);
        -webkit-tap-highlight-color: transparent;
    }

    .text-muted { color: var(--nm-muted) !important; }

    /* ---------- Entrance animation ---------- */
    @keyframes nmFadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .hero-banner, .stat-card, .app-card, .cta-wrap {
        animation: nmFadeUp .55s ease both;
    }
    .stat-card:nth-child(1) { animation-delay: .05s; }
    @media (prefers-reduced-motion: reduce) {
        * { animation: none !important; transition: none !important; }
    }

    /* ---------- Hero ---------- */
    .hero-banner {
        background: linear-gradient(135deg, var(--nm-brand) 0%, var(--nm-brand-2) 100%);
        color: #fff;
        border-radius: var(--nm-radius);
        padding: 28px 22px;
        box-shadow: var(--nm-raised);
        position: relative;
        overflow: hidden;
    }
    .hero-banner::before,
    .hero-banner::after {
        content: ""; position: absolute; border-radius: 50%;
        background: rgba(255, 255, 255, .12);
    }
    .hero-banner::before { width: 140px; height: 140px; top: -50px; right: -30px; }
    .hero-banner::after  { width: 90px;  height: 90px;  bottom: -35px; left: 12%; }
    .hero-banner > * { position: relative; z-index: 1; }

    /* ---------- Stats ---------- */
    .stat-card {
        background: var(--nm-bg);
        border-radius: var(--nm-radius-sm);
        box-shadow: var(--nm-raised-sm);
        padding: 14px 6px;
        text-align: center;
        height: 100%;
        transition: box-shadow .25s ease;
    }
    .stat-card:hover { box-shadow: var(--nm-inset-sm); }
    .stat-card .num {
        font-size: 1.35rem; font-weight: 800; line-height: 1.2;
        background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2));
        -webkit-background-clip: text; background-clip: text;
        -webkit-text-fill-color: transparent; color: transparent;
    }
    .stat-card .small { font-size: .72rem; }

    /* ---------- Generic card ---------- */
    .app-card {
        background: var(--nm-bg);
        border-radius: var(--nm-radius);
        box-shadow: var(--nm-raised);
        padding: 22px 18px;
        height: 100%;
        border: 0;
    }
    .app-card h2 { color: var(--nm-text); }

    .section-head {
        display: flex; align-items: flex-start; justify-content: space-between;
        gap: 12px; flex-wrap: wrap;
    }

    .featured-tag {
        display: inline-flex; align-items: center; gap: 6px;
        background: var(--nm-bg); color: var(--nm-brand);
        box-shadow: var(--nm-inset-sm);
        border-radius: 999px; padding: 5px 12px;
        font-size: .72rem; font-weight: 700; margin-bottom: 10px;
    }

    .see-all-link {
        display: inline-flex; align-items: center;
        background: var(--nm-bg); color: var(--nm-brand);
        box-shadow: var(--nm-raised-sm);
        border-radius: 999px; padding: 8px 16px;
        font-size: .82rem; font-weight: 700; text-decoration: none;
        transition: box-shadow .2s ease, transform .2s ease;
        white-space: nowrap;
    }
    .see-all-link:hover  { color: var(--nm-brand); }
    .see-all-link:active { box-shadow: var(--nm-inset-sm); transform: scale(.98); }

    /* ---------- Dictionary quick-search card ---------- */
    .dict-form { position: relative; margin-top: 16px; }
    .dict-input {
        width: 100%; border: 0; outline: 0;
        background: var(--nm-bg); color: var(--nm-text);
        border-radius: 999px; padding: 15px 60px 15px 20px; font-size: 1rem;
        box-shadow: var(--nm-inset);
        -webkit-appearance: none; appearance: none;
    }
    .dict-input::placeholder { color: var(--nm-muted); }
    .dict-input:focus { box-shadow: var(--nm-inset), 0 0 0 2px rgba(79, 70, 229, .35); }
    .dict-go {
        position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
        width: 44px; height: 44px; border: 0; border-radius: 50%;
        background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); color: #fff;
        box-shadow: var(--nm-raised-sm);
        display: flex; align-items: center; justify-content: center;
        transition: box-shadow .2s ease, transform .2s ease;
    }
    .dict-go:active { box-shadow: var(--nm-inset-sm); transform: translateY(-50%) scale(.95); }
    .dict-chips { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-top: 16px; }
    .dict-chips .lbl { font-size: .75rem; color: var(--nm-muted); font-weight: 600; }
    .dict-chip {
        background: var(--nm-bg); color: var(--nm-text); text-decoration: none;
        box-shadow: var(--nm-raised-sm); border-radius: 999px;
        padding: 7px 15px; font-size: .85rem; font-weight: 700;
        transition: box-shadow .2s ease, transform .2s ease;
    }
    .dict-chip:hover  { color: var(--nm-brand); }
    .dict-chip:active { box-shadow: var(--nm-inset-sm); transform: scale(.97); }

    /* ---------- Book carousel ---------- */
    .book-carousel-wrap { position: relative; margin-left: -4px; margin-right: -4px; }

    .book-carousel {
        display: flex; gap: 18px;
        overflow-x: auto; overflow-y: hidden;
        scroll-snap-type: x mandatory;
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
        padding: 12px 10px 22px;   /* room for shadows */
        scrollbar-width: none;
    }
    .book-carousel::-webkit-scrollbar { display: none; }

    .book-carousel-card {
        flex: 0 0 148px;
        scroll-snap-align: start;
        background: var(--nm-bg);
        border-radius: var(--nm-radius-sm);
        box-shadow: var(--nm-raised-sm);
        padding: 10px;
        text-decoration: none; color: var(--nm-text);
        transition: box-shadow .25s ease, transform .25s ease;
    }
    .book-carousel-card:hover  { transform: translateY(-3px); color: var(--nm-text); }
    .book-carousel-card:active { box-shadow: var(--nm-inset-sm); transform: scale(.98); }

    .bc-cover-wrap {
        position: relative;
        aspect-ratio: 3 / 4;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: var(--nm-inset-sm);
        background: var(--nm-bg);
        padding: 6px;
    }
    .bc-cover-wrap img {
        width: 100%; height: 100%; object-fit: cover;
        border-radius: 8px; display: block;
    }

    .bc-latest-badge, .bc-discount-badge {
        position: absolute; top: 10px; z-index: 2;
        font-size: .65rem; font-weight: 800;
        padding: 3px 9px; border-radius: 999px; color: #fff;
        box-shadow: 2px 2px 6px rgba(0, 0, 0, .2);
    }
    .bc-latest-badge   { left: 10px;  background: linear-gradient(135deg, #10b981, #34d399); }
    .bc-discount-badge { right: 10px; background: linear-gradient(135deg, #ef4444, #f97316); }

    .bc-body { padding: 10px 4px 2px; }
    .bc-title {
        font-size: .82rem; font-weight: 700; line-height: 1.3;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        overflow: hidden; min-height: 2.15em;
    }
    .bc-price-row { display: flex; align-items: baseline; gap: 6px; margin-top: 6px; flex-wrap: wrap; }
    .bc-price     { font-weight: 800; color: var(--nm-brand); font-size: .92rem; }
    .bc-price-old { font-size: .74rem; color: var(--nm-muted); text-decoration: line-through; }

    .carousel-arrow {
        position: absolute; top: 42%; transform: translateY(-50%);
        width: 42px; height: 42px; border: 0; border-radius: 50%;
        background: var(--nm-bg); color: var(--nm-brand);
        box-shadow: var(--nm-raised-sm);
        display: flex; align-items: center; justify-content: center;
        z-index: 5; cursor: pointer;
        transition: box-shadow .2s ease;
    }
    .carousel-arrow:active { box-shadow: var(--nm-inset-sm); }
    .carousel-arrow.left  { left: -6px; }
    .carousel-arrow.right { right: -6px; }
    @media (hover: none), (max-width: 575.98px) {
        .carousel-arrow { display: none; }   /* swipe on touch devices */
    }

    .book-empty-note {
        margin: 16px 0 4px; padding: 18px; text-align: center;
        border-radius: var(--nm-radius-sm); box-shadow: var(--nm-inset);
        color: var(--nm-muted); font-size: .9rem;
    }

    /* ---------- JLPT grid ---------- */
    .jlpt-grid {
        display: grid; gap: 14px;
        grid-template-columns: repeat(3, 1fr);
    }
    @media (min-width: 420px) { .jlpt-grid { grid-template-columns: repeat(5, 1fr); } }
    @media (min-width: 992px) and (max-width: 1199.98px) { .jlpt-grid { grid-template-columns: repeat(3, 1fr); } }

    .jlpt-btn {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 4px; padding: 14px 4px;
        background: var(--nm-bg);
        border-radius: var(--nm-radius-sm);
        box-shadow: var(--nm-raised-sm);
        text-decoration: none; color: var(--nm-text);
        transition: box-shadow .2s ease, transform .2s ease;
        min-height: 78px;
    }
    .jlpt-btn:hover  { color: var(--nm-text); transform: translateY(-2px); }
    .jlpt-btn:active { box-shadow: var(--nm-inset-sm); transform: scale(.97); }
    .jlpt-btn .tag {
        font-weight: 800; font-size: 1.15rem; color: var(--nm-brand);
    }
    .jlpt-btn .desc {
        font-size: .66rem; color: var(--nm-muted); text-align: center; line-height: 1.2;
    }

    /* ---------- Driving card ---------- */
    .driving-card {
        display: block; text-decoration: none; color: #fff;
        background: linear-gradient(135deg, var(--nm-brand) 0%, var(--nm-brand-2) 100%);
        border-radius: var(--nm-radius-sm);
        padding: 18px 16px;
        box-shadow: var(--nm-raised-sm);
        transition: transform .2s ease, box-shadow .2s ease;
        position: relative; overflow: hidden;
    }
    .driving-card::after {
        content: ""; position: absolute; width: 110px; height: 110px; border-radius: 50%;
        background: rgba(255, 255, 255, .12); right: -30px; top: -40px;
    }
    .driving-card:hover  { color: #fff; transform: translateY(-2px); }
    .driving-card:active { transform: scale(.98); box-shadow: var(--nm-inset-sm); }
    .external-badge {
        display: inline-flex; align-items: center; gap: 6px;
        margin-top: 10px; font-size: .7rem; font-weight: 600;
        background: rgba(255, 255, 255, .2); border-radius: 999px; padding: 4px 10px;
    }

    /* ---------- CTA ---------- */
    .cta-wrap { max-width: 420px; margin: 0 auto; padding: 0 4px; }
    .btn-brand {
        background: var(--nm-bg) !important;
        color: var(--nm-brand) !important;
        border: 0 !important;
        border-radius: 999px !important;
        padding: 15px 24px !important;
        font-weight: 800; font-size: 1.02rem;
        box-shadow: var(--nm-raised);
        transition: box-shadow .2s ease, transform .2s ease;
    }
    .btn-brand:hover  { transform: translateY(-2px); }
    .btn-brand:active { box-shadow: var(--nm-inset); transform: scale(.98); }

    /* ---------- Small screens ---------- */
    @media (max-width: 575.98px) {
        .hero-banner { padding: 22px 18px; border-radius: 20px; }
        .hero-banner h1 { font-size: 1.35rem !important; }
        .app-card { padding: 18px 14px; border-radius: 20px; }
        .book-carousel-card { flex-basis: 138px; }
    }
    @media (min-width: 768px) {
        .book-carousel-card { flex-basis: 170px; }
        .stat-card .num { font-size: 1.7rem; }
    }

    /* =====================================================
       MOBILE APP VIEW (< 992px: phone + tablet)  -  desktop is untouched
       ===================================================== */
    /* tablets: 4 big app buttons in one row */
    @media (min-width: 576px) {
        .mob-tiles { grid-template-columns: repeat(4, 1fr); }
        .mob-hero { padding: 26px 26px 28px; }
        .mob-hero h1 { font-size: 1.6rem; }
    }

    .mob-hero {
        background: linear-gradient(135deg, var(--nm-brand) 0%, var(--nm-brand-2) 100%);
        color: #fff; border-radius: 26px; padding: 20px 18px 22px;
        box-shadow: var(--nm-raised); position: relative; overflow: hidden;
        animation: nmFadeUp .5s ease both;
    }
    .mob-hero::after {
        content: ""; position: absolute; width: 130px; height: 130px; border-radius: 50%;
        background: rgba(255,255,255,.12); right: -35px; top: -45px;
    }
    .mob-hero > * { position: relative; z-index: 1; }
    .mob-hero h1 { font-size: 1.25rem; font-weight: 800; margin: 0 0 4px; }
    .mob-hero p  { font-size: .82rem; opacity: .9; margin: 0 0 14px; }
    .mob-hero .dict-form { margin-top: 0; }
    .mob-hero .dict-input { padding: 13px 58px 13px 18px; font-size: .95rem; }

    /* big app-style launcher buttons */
    .mob-tiles { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; margin: 18px 0; }
    .mob-tile {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 10px; min-height: 124px; padding: 16px 10px; text-align: center;
        background: var(--nm-bg); color: var(--nm-text); text-decoration: none;
        border-radius: 22px; box-shadow: var(--nm-raised);
        transition: box-shadow .2s ease, transform .2s ease;
        animation: nmFadeUp .55s ease both;
    }
    .mob-tile:hover  { color: var(--nm-text); }
    .mob-tile:active { box-shadow: var(--nm-inset); transform: scale(.97); }
    .mob-tile .ico {
        width: 54px; height: 54px; border-radius: 18px; color: #fff; font-size: 1.4rem;
        display: flex; align-items: center; justify-content: center;
        box-shadow: var(--nm-raised-sm);
    }
    .mob-tile .ico.i1 { background: linear-gradient(135deg, #4f46e5, #7c6cf5); }
    .mob-tile .ico.i2 { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
    .mob-tile .ico.i3 { background: linear-gradient(135deg, #10b981, #34d399); }
    .mob-tile .ico.i4 { background: linear-gradient(135deg, #ef4444, #f97316); }
    .mob-tile .lbl { font-size: .84rem; font-weight: 800; line-height: 1.25; }
    .mob-tile:nth-child(2) { animation-delay: .05s; }
    .mob-tile:nth-child(3) { animation-delay: .10s; }
    .mob-tile:nth-child(4) { animation-delay: .15s; }

</style>


        <!-- ================= MOBILE APP VIEW (phones only) ================= -->
        <div class="d-lg-none mob-home">
            <div class="mob-hero">
                <h1><?php echo $t['title']; ?></h1>
                <p><?php echo $t['subtitle']; ?></p>
                <form class="dict-form" action="dictionary.php" method="get" role="search">
                    <input type="search" name="q" class="dict-input" autocomplete="off" autocapitalize="off" spellcheck="false"
                           enterkeyhint="search" placeholder="<?php echo htmlspecialchars($IX['ph']); ?>"
                           aria-label="<?php echo htmlspecialchars($IX['title']); ?>">
                    <button type="submit" class="dict-go" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>

            <!-- Big app buttons -->
            <div class="mob-tiles">
                <a href="dictionary.php" class="mob-tile">
                    <span class="ico i1"><i class="fa-solid fa-book-open" aria-hidden="true"></i></span>
                    <span class="lbl"><?php echo htmlspecialchars($IX['title']); ?></span>
                </a>
                <a href="books.php" class="mob-tile">
                    <span class="ico i2"><i class="fa-solid fa-book" aria-hidden="true"></i></span>
                    <span class="lbl"><?php echo htmlspecialchars(strip_tags($t['books_title'])); ?></span>
                </a>
                <a href="n5.php" class="mob-tile">
                    <span class="ico i3"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i></span>
                    <span class="lbl"><?php echo htmlspecialchars(strip_tags($t['jlpt_mastery'])); ?></span>
                </a>
                <a href="<?php echo htmlspecialchars($drivingUrl); ?>" class="mob-tile" target="_blank" rel="noopener noreferrer">
                    <span class="ico i4"><i class="fa-solid fa-car" aria-hidden="true"></i></span>
                    <span class="lbl"><?php echo htmlspecialchars(strip_tags($t['driving_license'])); ?></span>
                </a>
            </div>

            <!-- JLPT level quick buttons -->
            <div class="app-card mb-4" style="height:auto;">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="fs-4" aria-hidden="true">🎌</span>
                    <h2 class="fs-6 fw-bold mb-0"><?php echo $t['jlpt_mastery']; ?></h2>
                </div>
                <div class="jlpt-grid">
                    <a href="n5.php" class="jlpt-btn"><span class="tag">N5</span><span class="desc"><?php echo $t['beginner']; ?></span></a>
                    <a href="n4.php" class="jlpt-btn"><span class="tag">N4</span><span class="desc"><?php echo $t['elementary']; ?></span></a>
                    <a href="n3.php" class="jlpt-btn"><span class="tag">N3</span><span class="desc"><?php echo $t['intermediate']; ?></span></a>
                    <a href="n2.php" class="jlpt-btn"><span class="tag">N2</span><span class="desc"><?php echo $t['upper_int']; ?></span></a>
                    <a href="n1.php" class="jlpt-btn"><span class="tag">N1</span><span class="desc"><?php echo $t['advanced']; ?></span></a>
                </div>
            </div>
        </div>

        <!-- ================= DESKTOP VIEW (tablet / desktop only) ================= -->
        <div class="d-none d-lg-block">
        <!-- Hero Banner -->
        <div class="hero-banner mb-4">
            <h1 class="fw-extrabold fs-2 mb-2"><?php echo $t['title']; ?></h1>
            <p class="mb-0 opacity-90 fs-6"><?php echo $t['subtitle']; ?></p>
        </div>

        <!-- Quick Stats -->
        <div class="row g-3 mb-4">
            <div class="col-4">
                <div class="stat-card">
                    <div class="num">5</div>
                    <div class="small text-muted"><?php echo $t['jlpt_levels']; ?></div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-card">
                    <div class="num">1000+</div>
                    <div class="small text-muted"><?php echo $t['practice_questions']; ?></div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-card">
                    <div class="num">100%</div>
                    <div class="small text-muted"><?php echo $t['study_access']; ?></div>
                </div>
            </div>
        </div>

        <!-- JLPT Dictionary — quick search, opens dictionary.php -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="app-card">
                    <div class="section-head">
                        <div>
                            <span class="featured-tag"><i class="fa-solid fa-bolt" aria-hidden="true"></i> <?php echo htmlspecialchars($IX['tag']); ?></span>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fs-3" aria-hidden="true">📖</span>
                                <h2 class="fw-bold mb-0"><?php echo htmlspecialchars($IX['title']); ?></h2>
                            </div>
                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($IX['desc']); ?></p>
                        </div>
                        <a href="dictionary.php" class="see-all-link"><?php echo htmlspecialchars($IX['open']); ?> <i class="fa-solid fa-arrow-right ms-1"></i></a>
                    </div>

                    <form class="dict-form" action="dictionary.php" method="get" role="search">
                        <input type="search" name="q" class="dict-input" autocomplete="off" autocapitalize="off" spellcheck="false"
                               enterkeyhint="search" placeholder="<?php echo htmlspecialchars($IX['ph']); ?>"
                               aria-label="<?php echo htmlspecialchars($IX['title']); ?>">
                        <button type="submit" class="dict-go" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </form>

                    <div class="dict-chips">
                        <span class="lbl"><?php echo htmlspecialchars($IX['try']); ?>:</span>
                        <a class="dict-chip" href="dictionary.php?q=<?php echo urlencode('学校'); ?>">学校</a>
                        <a class="dict-chip" href="dictionary.php?q=gakkou">gakkou</a>
                        <a class="dict-chip" href="dictionary.php?q=<?php echo urlencode('තරුණ'); ?>">තරුණ</a>
                        <a class="dict-chip" href="dictionary.php?q=pirimi">pirimi</a>
                    </div>
                </div>
            </div>
        </div>
        </div>
   <?php include __DIR__ . '/includes/wotd_card.php'; ?>
        <!-- Vocabulary Books Module — live carousel from the admin-managed shop -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="app-card book-carousel-section">
                    <div class="section-head">
                        <div>
                            <span class="featured-tag"><i class="fa-solid fa-star" aria-hidden="true"></i> <?php echo htmlspecialchars($t['shop_books']); ?></span>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fs-3" aria-hidden="true">📚</span>
                                <h2 class="fw-bold mb-0"><?php echo $t['books_title']; ?></h2>
                            </div>
                            <p class="text-muted small mb-0"><?php echo $t['books_desc']; ?></p>
                        </div>
                        <a href="books.php" class="see-all-link"><?php echo htmlspecialchars($t['see_all']); ?> <i class="fa-solid fa-arrow-right ms-1"></i></a>
                    </div>

                    <?php if (!empty($books)): ?>
                    <div class="book-carousel-wrap mt-3">
                        <button type="button" class="carousel-arrow left" onclick="scrollBookCarousel(-1)" aria-label="Scroll left">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>

                        <div class="book-carousel" id="bookCarousel">
                            <?php foreach ($books as $bId => $bk):
                                $bkHasDiscount = !empty($bk['discount_price_cents'])
                                    && $bk['discount_price_cents'] < $bk['price_cents'];
                                $bkCover = $bk['cover'] ?: 'https://placehold.co/300x400/eef2ff/4f46e5?text=' . urlencode($bk['level']);
                            ?>
                                <a href="book_detail.php?id=<?php echo urlencode($bId); ?>" class="book-carousel-card">
                                    <div class="bc-cover-wrap">
                                        <?php if (!empty($bk['is_featured'])): ?>
                                            <span class="bc-latest-badge">New</span>
                                        <?php endif; ?>
                                        <?php if ($bkHasDiscount): ?>
                                            <span class="bc-discount-badge">
                                                -<?php echo (int) round((1 - $bk['discount_price_cents'] / $bk['price_cents']) * 100); ?>%
                                            </span>
                                        <?php endif; ?>
                                        <img src="<?php echo htmlspecialchars($bkCover); ?>"
                                             alt="<?php echo htmlspecialchars($bk['title'][$currentLang] ?? $bk['title']['en']); ?>"
                                             loading="lazy"
                                             onerror="this.onerror=null;this.src='https://placehold.co/300x400/eef2ff/4f46e5?text=<?php echo urlencode($bk['level']); ?>';">
                                    </div>
                                    <div class="bc-body">
                                        <div class="bc-title"><?php echo htmlspecialchars($bk['title'][$currentLang] ?? $bk['title']['en']); ?></div>
                                        <div class="bc-price-row">
                                            <?php if ($bkHasDiscount): ?>
                                                <span class="bc-price-old"><?php echo format_price($bk['price_cents'], $shopCurrency); ?></span>
                                                <span class="bc-price"><?php echo format_price($bk['discount_price_cents'], $shopCurrency); ?></span>
                                            <?php else: ?>
                                                <span class="bc-price"><?php echo format_price($bk['price_cents'], $shopCurrency); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <button type="button" class="carousel-arrow right" onclick="scrollBookCarousel(1)" aria-label="Scroll right">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
                    <?php else: ?>
                        <p class="book-empty-note"><?php echo htmlspecialchars($t['no_books_yet']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="d-none d-lg-block">
        <!-- Modules Grid -->
        <div class="row g-4 mb-3">
            <!-- JLPT Prep -->
            <div class="col-lg-6">
                <div class="app-card">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="fs-4" aria-hidden="true">🎌</span>
                        <h2 class="fs-5 fw-bold mb-0"><?php echo $t['jlpt_mastery']; ?></h2>
                    </div>

                    <div class="jlpt-grid">
                        <a href="n5.php" class="jlpt-btn" title="JLPT N5 - <?php echo $t['beginner']; ?>">
                            <span class="tag">N5</span>
                            <span class="desc"><?php echo $t['beginner']; ?></span>
                        </a>
                        <a href="n4.php" class="jlpt-btn" title="JLPT N4 - <?php echo $t['elementary']; ?>">
                            <span class="tag">N4</span>
                            <span class="desc"><?php echo $t['elementary']; ?></span>
                        </a>
                        <a href="n3.php" class="jlpt-btn" title="JLPT N3 - <?php echo $t['intermediate']; ?>">
                            <span class="tag">N3</span>
                            <span class="desc"><?php echo $t['intermediate']; ?></span>
                        </a>
                        <a href="n2.php" class="jlpt-btn" title="JLPT N2 - <?php echo $t['upper_int']; ?>">
                            <span class="tag">N2</span>
                            <span class="desc"><?php echo $t['upper_int']; ?></span>
                        </a>
                        <a href="n1.php" class="jlpt-btn" title="JLPT N1 - <?php echo $t['advanced']; ?>">
                            <span class="tag">N1</span>
                            <span class="desc"><?php echo $t['advanced']; ?></span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Driving License Module — direct external link to menkyoo.jp -->
            <div class="col-lg-6">
                <div class="app-card d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="fs-4" aria-hidden="true">🚗</span>
                            <h2 class="fs-5 fw-bold mb-0"><?php echo $t['driving_license']; ?></h2>
                        </div>
                        <p class="text-muted small mb-3"><?php echo $t['driving_desc']; ?></p>
                    </div>

                    <a href="<?php echo htmlspecialchars($drivingUrl); ?>"
                       class="driving-card"
                       target="_blank"
                       rel="noopener noreferrer"
                       aria-label="<?php echo htmlspecialchars($t['start_journey'] . ' - Menkyoo.jp'); ?>">
                        <div class="d-flex align-items-center justify-content-between position-relative">
                            <div>
                                <div class="fw-bold fs-6"><?php echo $t['start_journey']; ?></div>
                                <div class="small text-white-50"><?php echo $t['driving_sub']; ?></div>
                                <div class="external-badge">
                                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                                    <?php echo htmlspecialchars($t['external_note']); ?>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right text-white-50" aria-hidden="true"></i>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- CTA Action -->
        <div class="text-center my-4 py-2">
            <div class="cta-wrap">
                <button class="btn btn-brand w-100" onclick="alert('Registration opening soon!')">
                    <?php echo $t['start_learning']; ?>
                </button>
            </div>
        </div>
        </div>

<script>
// Carousel scroll helper (footer.php eke define karala thiyenawanam eka use wenawa)
window.scrollBookCarousel = window.scrollBookCarousel || function (dir) {
    var el = document.getElementById('bookCarousel');
    if (!el) return;
    var card = el.querySelector('.book-carousel-card');
    var step = card ? (card.offsetWidth + 18) * 2 : 300;
    el.scrollBy({ left: dir * step, behavior: 'smooth' });
};
</script>

<?php
/* ===== Add to Home Screen popup (shared file: includes/a2hs.php) =====
   $a2hsLoginOnly = true  -> only logged-in users see it (same as before)
   $a2hsLoginOnly = false -> everybody on mobile sees it
   Test on phone: open this page with ?a2hs=1                                    */
$a2hsLoginOnly = true;
require __DIR__ . '/includes/a2hs.php';
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>