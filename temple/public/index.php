<?php
/**
 * Public temple page + dayaka directory (modernised UI)
 * ---------------------------------------------------------------
 * PHP / database logic is unchanged from your original file.
 * Only the markup, CSS and a small JS block (live search) are new.
 * Look for the "EDIT:" comments to find the parts you'll change most.
 */
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$db = Database::getConnection();

$search   = trim($_GET['search'] ?? '');
$peruwaId = max(0, (int)($_GET['peruwa_id'] ?? 0));

$peruwaList = [];
$dayakayanList = [];
$totalMembers = 0;
$totalPeruwa = 0;
$pageError = false;

try {
    $peruwaList = $db->query(
        "SELECT id, name FROM peruwa ORDER BY id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $totalPeruwa = count($peruwaList);

    $hasStatus = (bool) $db
        ->query("SHOW COLUMNS FROM dayakayan LIKE 'status'")
        ->fetch(PDO::FETCH_ASSOC);

    $where = [];
    $params = [];

    if ($hasStatus) {
        $where[] = "d.status = 'approved'";
    }

    if ($search !== '') {
        $where[] = "(d.full_name LIKE :s1
                     OR d.member_id LIKE :s2
                     OR d.phone LIKE :s3)";
        $term = '%' . $search . '%';
        $params['s1'] = $term;
        $params['s2'] = $term;
        $params['s3'] = $term;
    }

    if ($peruwaId > 0) {
        $where[] = "p.id = :peruwa_id";
        $params['peruwa_id'] = $peruwaId;
    }

    $whereSql = $where
        ? 'WHERE ' . implode(' AND ', $where)
        : '';

    // Public directory total count, using the same filters.
    $countSql = "
        SELECT COUNT(*)
        FROM dayakayan d
        LEFT JOIN families f ON d.family_id = f.id
        LEFT JOIN peruwa p ON f.peruwa_id = p.id
        $whereSql
    ";

    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalMembers = (int) $countStmt->fetchColumn();

    $listSql = "
        SELECT
            d.id,
            d.member_id,
            d.full_name,
            d.phone,
            p.name AS peruwa_name,
            f.family_code
        FROM dayakayan d
        LEFT JOIN families f ON d.family_id = f.id
        LEFT JOIN peruwa p ON f.peruwa_id = p.id
        $whereSql
        ORDER BY d.id DESC
        LIMIT 100
    ";

    $stmt = $db->prepare($listSql);
    $stmt->execute($params);
    $dayakayanList = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log('TDMS public index error: ' . $e->getMessage());
    $pageError = true;
}

/**
 * Small view helper: first letter of a name for the avatar circle.
 * Uses mb_* so Sinhala characters are handled correctly.
 */
function avatarInitial(string $name): string
{
    $name = trim($name);
    return $name !== '' ? mb_substr($name, 0, 1, 'UTF-8') : '?';
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Sinhala-friendly fonts (EDIT: swap families here if you prefer others) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;500;600;700&family=Noto+Serif+Sinhala:wght@600;700;800&display=swap" rel="stylesheet">

<style>
/* ============================================================
   DESIGN TOKENS  (EDIT: change colours / radii here only)
   ============================================================ */
:root {
    --ink:        #101c33;   /* deep indigo – text + dark surfaces */
    --ink-2:      #1b2f52;
    --saffron:    #e8a93a;   /* monk's-robe saffron – main accent */
    --saffron-lt: #fff1d1;
    --saffron-dk: #7a5410;
    --paper:      #f3f5f9;   /* page background */
    --card:       #ffffff;
    --line:       #e3e7ef;
    --muted:      #5f6b82;

    --r-sm: 10px;
    --r-md: 16px;
    --r-lg: 28px;

    --font-body:    'Noto Sans Sinhala', system-ui, -apple-system, 'Segoe UI', sans-serif;
    --font-display: 'Noto Serif Sinhala', 'Noto Sans Sinhala', serif;
}

body {
    background: var(--paper);
    color: var(--ink);
    font-family: var(--font-body);
}
h1, h2, h3, .display-font { font-family: var(--font-display); }

/* keyboard focus – visible everywhere */
a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible {
    outline: 3px solid var(--saffron);
    outline-offset: 2px;
}

/* ============================================================
   HERO
   ============================================================ */
.hero {
    position: relative;
    overflow: hidden;
    color: #fff;
    background:
        radial-gradient(60% 80% at 90% 0%, rgba(232,169,58,.25), transparent 60%),
        linear-gradient(140deg, var(--ink) 0%, var(--ink-2) 100%);
    padding: 88px 0 120px;
}
.hero-title {
    font-weight: 800;
    font-size: clamp(1.9rem, 4.6vw, 3.4rem);
    line-height: 1.5;
    margin-bottom: 20px;
}
.hero-text {
    color: rgba(255,255,255,.72);
    font-size: 1.08rem;
    line-height: 1.9;
    max-width: 38rem;
}
.hero-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 16px;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,.2);
    background: rgba(255,255,255,.06);
    color: #ffe3a6;
    font-size: .88rem;
    margin-bottom: 22px;
}

/* Dharmachakra – the ONE decorative motion on the page */
.chakra-wrap {
    position: absolute;
    right: -140px;
    top: 50%;
    width: 560px;
    height: 560px;
    margin-top: -280px;
    opacity: .16;
    pointer-events: none;
}
.chakra { width: 100%; height: 100%; animation: spin 90s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
@media (prefers-reduced-motion: reduce) { .chakra { animation: none; } }

.btn-saffron {
    background: var(--saffron);
    color: var(--ink);
    font-weight: 700;
    border: 0;
    border-radius: 999px;
    padding: 12px 28px;
    transition: background .15s;
}
.btn-saffron:hover { background: #f2bb59; color: var(--ink); }
.btn-ghost {
    color: #fff;
    border: 1px solid rgba(255,255,255,.35);
    border-radius: 999px;
    padding: 12px 28px;
    font-weight: 600;
}
.btn-ghost:hover { background: rgba(255,255,255,.1); color: #fff; }

/* ============================================================
   STATS STRIP  (overlaps the hero)
   ============================================================ */
.stats {
    position: relative;
    z-index: 2;
    margin-top: -64px;
}
.stats-panel {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: var(--r-lg);
    box-shadow: 0 20px 50px -20px rgba(16,28,51,.25);
    padding: 8px;
}
.stat {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 18px 22px;
}
.stat + .stat { border-left: 1px solid var(--line); }
@media (max-width: 767px) {
    .stat + .stat { border-left: 0; border-top: 1px solid var(--line); }
}
.stat-icon {
    flex: none;
    width: 52px; height: 52px;
    display: grid; place-items: center;
    border-radius: 14px;
    background: var(--saffron-lt);
    color: var(--saffron-dk);
    font-size: 1.3rem;
}
.stat-num   { font-family: var(--font-display); font-weight: 800; font-size: 1.7rem; line-height: 1.2; }
.stat-label { color: var(--muted); font-size: .88rem; }

/* ============================================================
   SECTION BASICS
   ============================================================ */
.section { padding: 72px 0; }
.section-title { font-weight: 800; font-size: clamp(1.5rem, 3vw, 2.1rem); margin-bottom: 8px; }
.section-sub   { color: var(--muted); max-width: 36rem; }

/* ============================================================
   SERVICES  (not identical cards: first one is featured)
   ============================================================ */
.svc {
    height: 100%;
    display: flex;
    flex-direction: column;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: var(--r-md);
    padding: 28px;
    text-decoration: none;
    color: inherit;
    transition: border-color .15s, transform .15s;
}
.svc:hover { border-color: var(--saffron); transform: translateY(-2px); color: inherit; }
.svc h3 { font-size: 1.2rem; font-weight: 700; margin: 18px 0 8px; }
.svc p  { color: var(--muted); margin: 0 0 18px; flex: 1; line-height: 1.8; }
.svc-link { font-weight: 700; color: var(--saffron-dk); }
.svc-icon {
    width: 50px; height: 50px; border-radius: 14px;
    display: grid; place-items: center;
    background: var(--saffron-lt); color: var(--saffron-dk); font-size: 1.25rem;
}
.svc.featured { background: var(--ink); border-color: var(--ink); color: #fff; }
.svc.featured p { color: rgba(255,255,255,.7); }
.svc.featured .svc-link { color: var(--saffron); }
.svc.featured .svc-icon { background: rgba(232,169,58,.18); color: var(--saffron); }

/* ============================================================
   DAHAM PASALA
   ============================================================ */
.pasala {
    background: linear-gradient(180deg, #fff, #fbfcfe);
    border-top: 1px solid var(--line);
    border-bottom: 1px solid var(--line);
}
.info-tile {
    height: 100%;
    background: var(--paper);
    border-radius: var(--r-md);
    padding: 20px 22px;
}
.info-tile i { color: var(--saffron-dk); margin-right: 8px; }
.info-tile p { margin: 8px 0 0; color: var(--muted); font-size: .92rem; line-height: 1.7; }

/* ============================================================
   RELIGIOUS PROGRAMS – simple icon rows
   ============================================================ */
.program {
    display: flex;
    align-items: center;
    gap: 16px;
    height: 100%;
    padding: 18px 20px;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: var(--r-md);
}
.program-icon {
    flex: none;
    width: 46px; height: 46px; border-radius: 50%;
    display: grid; place-items: center;
    background: var(--saffron-lt); color: var(--saffron-dk);
}
.program h3 { font-size: 1.02rem; font-weight: 700; margin: 0 0 2px; }
.program p  { font-size: .85rem; color: var(--muted); margin: 0; }

/* ============================================================
   DIRECTORY
   ============================================================ */
.directory { background: #e9edf4; }

.search-panel {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: var(--r-md);
    padding: 20px;
    margin-bottom: 20px;
}
.search-panel .form-control,
.search-panel .form-select {
    border-radius: var(--r-sm);
    border-color: var(--line);
    padding: 12px 14px;
}
.search-panel label { font-weight: 600; font-size: .85rem; margin-bottom: 6px; }

.result-panel {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: var(--r-md);
    overflow: hidden;
}
.result-head {
    display: flex; flex-wrap: wrap; gap: 10px;
    align-items: center; justify-content: space-between;
    padding: 18px 22px;
    border-bottom: 1px solid var(--line);
}
.result-count { color: var(--muted); font-size: .9rem; }

/* desktop table */
.dir-table { margin: 0; }
.dir-table thead th {
    background: #f7f9fc;
    color: var(--muted);
    font-weight: 600;
    font-size: .85rem;
    border-bottom: 1px solid var(--line);
    padding: 14px 16px;
    white-space: nowrap;
}
.dir-table td { padding: 14px 16px; border-color: var(--line); vertical-align: middle; }
.dir-table tbody tr:hover { background: #fafbfd; }

.avatar {
    flex: none;
    width: 40px; height: 40px; border-radius: 50%;
    display: grid; place-items: center;
    background: var(--ink); color: var(--saffron);
    font-family: var(--font-display); font-weight: 700;
}
.name-cell { display: flex; align-items: center; gap: 12px; font-weight: 600; }

.tag {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: .8rem;
    font-weight: 700;
    background: var(--saffron-lt);
    color: var(--saffron-dk);
}
.tag-peruwa { background: #e8eefb; color: #254a8a; font-weight: 600; }

/* mobile cards (shown instead of the table on small screens) */
.m-item { display: flex; gap: 14px; padding: 16px 20px; border-top: 1px solid var(--line); }
.m-item:first-child { border-top: 0; }
.m-name { font-weight: 700; }
.m-meta { display: flex; flex-wrap: wrap; gap: 6px 10px; margin-top: 8px; align-items: center; }
.m-phone { color: var(--muted); font-size: .85rem; }

.empty { text-align: center; padding: 56px 20px; color: var(--muted); }
.empty i { font-size: 2.4rem; margin-bottom: 14px; color: #b7c0d1; }
.empty strong { display: block; color: var(--ink); margin-bottom: 4px; }

.privacy-note {
    padding: 14px 22px;
    background: #f7f9fc;
    border-top: 1px solid var(--line);
    color: var(--muted);
    font-size: .85rem;
}

/* loading state while live-search is fetching */
#directory-results.is-loading { opacity: .55; transition: opacity .15s; }

/* ============================================================
   CLOSING BAND
   ============================================================ */
.closing {
    background: var(--ink);
    color: #fff;
    border-radius: var(--r-lg);
    padding: 48px 28px;
    text-align: center;
}
.closing h2 { font-size: clamp(1.4rem, 3vw, 2rem); font-weight: 700; }
.closing p  { color: rgba(255,255,255,.65); margin: 0; }

@media (max-width: 767px) {
    .hero { padding: 60px 0 100px; }
    .section { padding: 52px 0; }
    .chakra-wrap { width: 380px; height: 380px; right: -150px; margin-top: -190px; }
}
</style>

<!-- ============================================================
     HERO
     ============================================================ -->
<section class="hero">
    <!-- Decorative 8-spoke dharmachakra (inline SVG, no image needed) -->
    <div class="chakra-wrap" aria-hidden="true">
        <svg class="chakra" viewBox="0 0 200 200" fill="none" stroke="#e8a93a" stroke-width="3">
            <circle cx="100" cy="100" r="92"/>
            <circle cx="100" cy="100" r="76" stroke-width="1.5"/>
            <circle cx="100" cy="100" r="16"/>
            <g>
                <line x1="100" y1="16"  x2="100" y2="84"/>
                <line x1="100" y1="116" x2="100" y2="184"/>
                <line x1="16"  y1="100" x2="84"  y2="100"/>
                <line x1="116" y1="100" x2="184" y2="100"/>
                <line x1="40.6"  y1="40.6"  x2="88.7"  y2="88.7"/>
                <line x1="111.3" y1="111.3" x2="159.4" y2="159.4"/>
                <line x1="159.4" y1="40.6"  x2="111.3" y2="88.7"/>
                <line x1="88.7"  y1="111.3" x2="40.6"  y2="159.4"/>
            </g>
        </svg>
    </div>

    <div class="container position-relative">
        <!-- EDIT: hero copy -->
        <span class="hero-chip">
            <i class="fa-solid fa-dharmachakra"></i>
            බුදු දහම • සීලය • සමාධිය • ප්‍රඥාව
        </span>

        <h1 class="hero-title">
            ශ්‍රද්ධාවෙන් එක්වෙමු,<br>
            සසුනේ චිරස්ථිතිය උදෙසා කැපවෙමු
        </h1>

        <p class="hero-text mb-4">
            අපගේ විහාරස්ථානයේ ආගමික කටයුතු, දහම් පාසල් තොරතුරු,
            දායක නාමාවලිය සහ ඉදිරි වැඩසටහන් පිළිබඳ තොරතුරු
            එකම ස්ථානයකින් දැනගන්න.
        </p>

        <div class="d-flex flex-wrap gap-2">
            <a href="#daham-pasala" class="btn btn-saffron btn-lg">
                <i class="fa-solid fa-book-open me-2"></i>දහම් පාසල
            </a>
            <a href="#dayaka-directory" class="btn btn-ghost btn-lg">
                <i class="fa-solid fa-users me-2"></i>දායක නාමාවලිය
            </a>
        </div>
    </div>
</section>

<!-- ============================================================
     STATS
     ============================================================ -->
<section class="stats">
    <div class="container">
        <div class="stats-panel">
            <div class="row g-0">
                <div class="col-md-4">
                    <div class="stat">
                        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                        <div>
                            <div class="stat-num"><?= number_format($totalMembers) ?></div>
                            <div class="stat-label">සෙවුම් ප්‍රතිඵලවල දායකයින්</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat">
                        <div class="stat-icon"><i class="fa-solid fa-people-group"></i></div>
                        <div>
                            <div class="stat-num"><?= number_format($totalPeruwa) ?></div>
                            <div class="stat-label">ලියාපදිංචි පේරු</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat">
                        <div class="stat-icon"><i class="fa-solid fa-heart"></i></div>
                        <div>
                            <div class="stat-num">එක්ව</div>
                            <div class="stat-label">සසුනේ සහ සමාජයේ යහපත වෙනුවෙන්</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     TEMPLE SERVICES
     ============================================================ -->
<section class="section">
    <div class="container">
        <div class="mb-4 mb-lg-5">
            <h2 class="section-title">අපගේ විහාරස්ථානය</h2>
            <p class="section-sub mb-0">ආගමික, අධ්‍යාපනික සහ සමාජ සේවා තොරතුරු</p>
        </div>

        <div class="row g-3 g-lg-4">
            <div class="col-md-6 col-lg-4">
                <!-- "featured" class makes this card dark -->
                <a href="#daham-pasala" class="svc featured">
                    <div class="svc-icon"><i class="fa-solid fa-book-open"></i></div>
                    <h3>දහම් පාසල</h3>
                    <p>දහම් අධ්‍යාපනය, බෞද්ධ සාරධර්ම සහ දරුවන්ගේ යහපත් පෞරුෂ සංවර්ධනය පිළිබඳ තොරතුරු.</p>
                    <span class="svc-link">විස්තර බලන්න <i class="fa-solid fa-arrow-right ms-1"></i></span>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="#religious-programs" class="svc">
                    <div class="svc-icon"><i class="fa-solid fa-hands-praying"></i></div>
                    <h3>දාන සහ පින්කම්</h3>
                    <p>දානමය පින්කම්, බෝධි පූජා, පිරිත් දේශනා සහ වෙනත් ආගමික වැඩසටහන් පිළිබඳ තොරතුරු.</p>
                    <span class="svc-link">වැඩසටහන් බලන්න <i class="fa-solid fa-arrow-right ms-1"></i></span>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="#dayaka-directory" class="svc">
                    <div class="svc-icon"><i class="fa-solid fa-users"></i></div>
                    <h3>දායක නාමාවලිය</h3>
                    <p>අනුමත දායක තොරතුරු Member ID, නම හෝ පේරුව අනුව සොයාගන්න.</p>
                    <span class="svc-link">නාමාවලියට යන්න <i class="fa-solid fa-arrow-right ms-1"></i></span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     DAHAM PASALA
     ============================================================ -->
<section class="pasala section" id="daham-pasala">
    <div class="container">
        <div class="row align-items-center g-4 g-lg-5">
            <div class="col-lg-5">
                <h2 class="section-title">දහම් පාසල</h2>
                <p class="section-sub">
                    ගුණවත් දරු පරපුරක් බිහිකිරීමට බෞද්ධ අධ්‍යාපනය සහ සාරධර්ම පෝෂණය කරමු.
                </p>
            </div>

            <div class="col-lg-7">
                <h3 class="h5 fw-bold">දහම් අධ්‍යාපනයේ අරමුණ</h3>
                <p class="text-secondary" style="line-height:1.9">
                    බුදු දහමේ ඉගැන්වීම්, පංචසීලය, මෛත්‍රිය, කරුණාව, වැඩිහිටියන්ට ගරු කිරීම
                    සහ යහපත් සමාජ ජීවිතයක් ගත කිරීම පිළිබඳ දරුවන් දැනුවත් කිරීම.
                </p>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="info-tile">
                            <i class="fa-solid fa-calendar-days"></i><strong>පන්ති පැවැත්වෙන දින</strong>
                            <p>විහාරස්ථානයෙන් තහවුරු කළ යුතුය.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="info-tile">
                            <i class="fa-solid fa-user-graduate"></i><strong>සිසුන් ලියාපදිංචිය</strong>
                            <p>ලියාපදිංචි වීමේ තොරතුරු විහාරස්ථානයෙන් ලබාගන්න.</p>
                        </div>
                    </div>
                </div>

                <p class="small text-secondary mt-3 mb-0">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    පන්ති වේලාවන් සහ සැබෑ සිසුන්ගේ දත්ත ඇතුළත් කිරීමට දහම් පාසල් Database එක සම්බන්ධ කළ යුතුය.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     RELIGIOUS PROGRAMS
     ============================================================ -->
<section class="section" id="religious-programs">
    <div class="container">
        <div class="mb-4 mb-lg-5">
            <h2 class="section-title">ආගමික කටයුතු සහ පින්කම්</h2>
            <p class="section-sub mb-0">විහාරස්ථානයේ සිදුවන ආගමික සේවාවන්</p>
        </div>

        <div class="row g-3">
            <div class="col-sm-6 col-lg-3">
                <div class="program">
                    <div class="program-icon"><i class="fa-solid fa-leaf"></i></div>
                    <div><h3>බෝධි පූජා</h3><p>බෝධි වන්දනා සහ පූජා කටයුතු</p></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="program">
                    <div class="program-icon"><i class="fa-solid fa-bowl-food"></i></div>
                    <div><h3>දානමය පින්කම්</h3><p>සංඝගත දක්ෂිණා සහ දාන කටයුතු</p></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="program">
                    <div class="program-icon"><i class="fa-solid fa-om"></i></div>
                    <div><h3>පිරිත් දේශනා</h3><p>පිරිත් සහ ආශීර්වාද පින්කම්</p></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="program">
                    <div class="program-icon"><i class="fa-solid fa-book-open"></i></div>
                    <div><h3>ධර්ම දේශනා</h3><p>ධර්ම ශ්‍රවණය සහ බෞද්ධ දැනුම</p></div>
                </div>
            </div>
        </div>

        <p class="small text-secondary mt-4 mb-0">
            වැඩසටහන්වල නිශ්චිත දින සහ වේලාවන් පසුව පරිපාලන පද්ධතියට සම්බන්ධ කළ හැක.
        </p>
    </div>
</section>

<!-- ============================================================
     PUBLIC DIRECTORY
     ============================================================ -->
<section class="directory section" id="dayaka-directory">
    <div class="container">
        <div class="mb-4 mb-lg-5">
            <h2 class="section-title">පන්සල් දායක නාමාවලිය</h2>
            <p class="section-sub mb-0">නම, Member ID හෝ පේරුව අනුව සොයන්න</p>
        </div>

        <?php if ($pageError): ?>
            <div class="alert alert-warning">
                නාමාවලිය දැනට ලබාගත නොහැක. කරුණාකර පසුව නැවත උත්සාහ කරන්න.
            </div>
        <?php else: ?>

        <!-- Search form. Works without JS (normal GET); JS below upgrades it to live search. -->
        <div class="search-panel">
            <form method="GET" action="#dayaka-directory" id="dirForm" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="searchInput">නම / Member ID / දුරකථන අංකය</label>
                    <input
                        type="search"
                        id="searchInput"
                        name="search"
                        class="form-control"
                        placeholder="දායක තොරතුරු සොයන්න..."
                        value="<?= e($search) ?>"
                        autocomplete="off"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="peruwaSelect">පේරුව</label>
                    <select name="peruwa_id" id="peruwaSelect" class="form-select">
                        <option value="0">සියලුම පේරු</option>
                        <?php foreach ($peruwaList as $p): ?>
                            <option
                                value="<?= (int)$p['id'] ?>"
                                <?= $peruwaId === (int)$p['id'] ? 'selected' : '' ?>
                            >
                                <?= e($p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-saffron w-100" style="border-radius:10px;">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> සොයන්න
                    </button>
                </div>
            </form>
        </div>

        <!-- Everything inside #directory-results is swapped by the live-search JS -->
        <div id="directory-results">
            <div class="result-panel">
                <div class="result-head">
                    <div>
                        <h3 class="h5 fw-bold mb-1">දායක තොරතුරු</h3>
                        <div class="result-count" aria-live="polite">
                            ප්‍රතිඵල <?= number_format($totalMembers) ?> ක්
                            <?php if ($totalMembers > 100): ?>
                                (පළමු 100 පමණක් මෙහි පෙන්වයි)
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($search !== '' || $peruwaId > 0): ?>
                        <a href="<?= e(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '') ?>#dayaka-directory"
                           class="btn btn-outline-secondary btn-sm" id="clearFilters">
                            <i class="fa-solid fa-rotate-left me-1"></i> සියල්ල පෙන්වන්න
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (!$dayakayanList): ?>
                    <!-- Empty state -->
                    <div class="empty">
                        <i class="fa-solid fa-users-slash"></i>
                        <strong>තොරතුරු හමු නොවීය</strong>
                        <span class="small">වෙනත් නමක් හෝ පේරුවක් භාවිතා කර සොයන්න.</span>
                    </div>
                <?php else: ?>

                    <!-- DESKTOP / TABLET: table -->
                    <div class="table-responsive d-none d-md-block">
                        <table class="table dir-table align-middle">
                            <thead>
                                <tr>
                                    <th class="ps-4">සම්පූර්ණ නම</th>
                                    <th>Member ID</th>
                                    <th>පේරුව</th>
                                    <th>Family Code</th>
                                    <th class="pe-4 text-end">දුරකථනය</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($dayakayanList as $row): ?>
                                <?php $fullName = (string)$row['full_name']; ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="name-cell">
                                            <span class="avatar"><?= e(avatarInitial($fullName)) ?></span>
                                            <?= e($fullName) ?>
                                        </div>
                                    </td>
                                    <td><span class="tag"><?= e((string)($row['member_id'] ?? 'N/A')) ?></span></td>
                                    <td><span class="tag tag-peruwa"><?= e((string)($row['peruwa_name'] ?? 'නොමැත')) ?></span></td>
                                    <td><code><?= e((string)($row['family_code'] ?? 'N/A')) ?></code></td>
                                    <td class="pe-4 text-end">
                                        <?php
                                        $phone = (string)($row['phone'] ?? '');
                                        echo e($phone !== '' ? maskPhoneNumber($phone) : 'සඳහන් නැත');
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- MOBILE: stacked list -->
                    <div class="d-md-none">
                        <?php foreach ($dayakayanList as $row): ?>
                            <?php
                            $fullName = (string)$row['full_name'];
                            $phone    = (string)($row['phone'] ?? '');
                            ?>
                            <div class="m-item">
                                <span class="avatar"><?= e(avatarInitial($fullName)) ?></span>
                                <div>
                                    <div class="m-name"><?= e($fullName) ?></div>
                                    <div class="m-meta">
                                        <span class="tag"><?= e((string)($row['member_id'] ?? 'N/A')) ?></span>
                                        <span class="tag tag-peruwa"><?= e((string)($row['peruwa_name'] ?? 'නොමැත')) ?></span>
                                        <code><?= e((string)($row['family_code'] ?? 'N/A')) ?></code>
                                    </div>
                                    <div class="m-phone mt-1">
                                        <i class="fa-solid fa-phone me-1"></i>
                                        <?= e($phone !== '' ? maskPhoneNumber($phone) : 'සඳහන් නැත') ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php endif; ?>

                <div class="privacy-note">
                    <i class="fa-solid fa-shield-halved me-1"></i>
                    දායකයන්ගේ පෞද්ගලිකත්වය ආරක්ෂා කිරීම සඳහා දුරකථන අංක සඟවා පෙන්වයි.
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>
</section>

<!-- ============================================================
     CLOSING BAND
     ============================================================ -->
<section class="container py-5">
    <div class="closing">
        <i class="fa-solid fa-dharmachakra mb-3" style="font-size:2.2rem;color:var(--saffron);"></i>
        <h2>සියලු සත්ත්වයෝ නිදුක් වෙත්වා!</h2>
        <p>සියලු දෙනාටම සෙත්, ශාන්තිය සහ යහපත සැලසේවා.</p>
    </div>
</section>

<!-- ============================================================
     LIVE SEARCH (progressive enhancement)
     - Types in the box -> after 350ms we fetch this same page with the
       new query and swap ONLY #directory-results. No page reload.
     - If JS fails, the normal form submit still works.
     ============================================================ -->
<script>
(function () {
    var form    = document.getElementById('dirForm');
    var input   = document.getElementById('searchInput');
    var select  = document.getElementById('peruwaSelect');
    var holder  = document.getElementById('directory-results');
    if (!form || !input || !select || !holder) return;

    var timer = null;
    var controller = null;

    function load() {
        // Build the query string from the form fields
        var qs = new URLSearchParams(new FormData(form)).toString();

        // Cancel any request that's still running
        if (controller) controller.abort();
        controller = new AbortController();

        holder.classList.add('is-loading');

        fetch('?' + qs, { signal: controller.signal, headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                // Pull the new results block out of the returned page
                var doc  = new DOMParser().parseFromString(html, 'text/html');
                var next = doc.getElementById('directory-results');
                if (next) holder.innerHTML = next.innerHTML;
                holder.classList.remove('is-loading');
                // Keep the URL shareable
                history.replaceState(null, '', '?' + qs + '#dayaka-directory');
            })
            .catch(function (err) {
                if (err.name !== 'AbortError') {
                    holder.classList.remove('is-loading');
                    form.submit(); // fall back to a normal reload
                }
            });
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(load, 350);   // EDIT: debounce delay (ms)
    });
    select.addEventListener('change', load);

    // Submit button / Enter key also uses the fast path
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearTimeout(timer);
        load();
    });

    // "Clear filters" link: reset the form and reload results
    holder.addEventListener('click', function (e) {
        var a = e.target.closest('#clearFilters');
        if (!a) return;
        e.preventDefault();
        input.value = '';
        select.value = '0';
        load();
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>