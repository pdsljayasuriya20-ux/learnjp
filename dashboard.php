<?php
/**
 * dashboard.php - user dashboard: profile, PLAN, PROGRESS, ACTIVITY history,
 * install-as-app link, set/change password, logout
 */
require_once __DIR__ . '/includes/progress.php';
require_once __DIR__ . '/includes/activity.php';      // NEW: activity log helpers

if (empty($_SESSION['user_id'])) { header('Location: user-login.php?next=dashboard.php'); exit; }

$user = la_get_user((int) $_SESSION['user_id']);
if (!$user || !(int) $user['is_active']) {       // account removed / disabled -> force logout
    header('Location: user-logout.php');
    exit;
}

$uid         = (int) $user['id'];
$csrf        = la_csrf();
$hasPassword = !empty($user['password_hash']);
$initial     = mb_strtoupper(mb_substr($user['name'], 0, 1, 'UTF-8'), 'UTF-8');
$phonePretty = $user['phone'] ? '+' . $user['phone'] : '';

/* ---------- plans + progress ---------- */
$plans  = la_user_plans($uid);
$prog   = la_progress_summary($uid);
$totals = la_level_totals();

$hasLive = false;
foreach ($plans as $p) { if ($p['live']) { $hasLive = true; break; } }

$sum = ['grammar' => 0, 'mastered' => 0, 'lessons' => 0, 'quizzes' => 0, 'qs' => 0, 'qt' => 0];
foreach ($prog as $row) {
    $sum['grammar']  += $row['grammar'];
    $sum['mastered'] += $row['mastered'];
    $sum['lessons']  += $row['lessons'];
    $sum['quizzes']  += $row['quizzes'];
    $sum['qs']       += $row['quiz_score'];
    $sum['qt']       += $row['quiz_total'];
}
$quizAvg = $sum['qt'] > 0 ? (int) round($sum['qs'] / $sum['qt'] * 100) : null;
$anyProg = ($sum['grammar'] + $sum['lessons'] + $sum['quizzes']) > 0;

/* ---------- NEW: activity history (filter + "load more" via ?act=type&n=20) ---------- */
$actTypes  = [
    ''         => ['සියල්ල',     'fa-list'],
    'whatsapp' => ['WhatsApp',    'fa-brands fa-whatsapp'],
    'book'     => ['පොත්',       'fa-book'],
    'lesson'   => ['පාඩම්',      'fa-book-open'],
    'quiz'     => ['Quiz',        'fa-circle-question'],
];
$actFilter = isset($_GET['act']) && isset($actTypes[$_GET['act']]) ? (string) $_GET['act'] : '';
$actLimit  = min(100, max(20, (int) ($_GET['n'] ?? 20)));
$actRows   = la_user_activity($uid, $actFilter, $actLimit + 1);   // +1 to know if more exist
$actMore   = count($actRows) > $actLimit;
if ($actMore) array_pop($actRows);
$actCounts = la_user_activity_counts($uid);

// icon + colour per activity type
$actIcon = [
    'whatsapp' => ['fa-brands fa-whatsapp', '#25d366'],
    'book'     => ['fa-solid fa-book',       '#f59e0b'],
    'lesson'   => ['fa-solid fa-book-open',  '#3a86ff'],
    'quiz'     => ['fa-solid fa-circle-question', '#8338ec'],
    'grammar'  => ['fa-solid fa-layer-group','#06b6d4'],
    'login'    => ['fa-solid fa-right-to-bracket', '#94a3b8'],
    'plan'     => ['fa-solid fa-crown',      '#eab308'],
    'other'    => ['fa-solid fa-circle',     '#94a3b8'],
];

// WhatsApp contact for plan activation (set LA_PLAN_WA in includes/auth-config.php, e.g. '94771234567')
$waLink = '';
if (defined('LA_PLAN_WA') && LA_PLAN_WA !== '') {
    $waText = 'Plan එකක් activate කරන්න ඕන. ගිණුම් අංකය #' . $uid . ' (' . $user['name'] . ')';
    $waLink = 'https://wa.me/' . preg_replace('/\D/', '', LA_PLAN_WA) . '?text=' . rawurlencode($waText);
}

$pageTitle = 'මගේ ගිණුම | Learn Japanese';

$jsCfg = [
    'csrf' => $csrf,
    'api'  => 'auth-api.php',
    't'    => [
        'network' => 'ජාල දෝෂයක්. නැවත උත්සාහ කරන්න.',
        'pwSaved' => 'මුරපදය සුරැකිණි! ඊළඟ වතාවේ ඊමේල් / අංකය සහ මුරපදයෙන් ඇතුල් විය හැක.',
    ],
];

require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="assets/css/auth.css">
<link rel="stylesheet" href="assets/css/dashboard.css?v=2">

<div class="auth-wrap dash-wide">

  <!-- ===================== profile ===================== -->
  <div class="auth-card">
    <div class="dash-user">
      <div class="dash-avatar">
        <?php if (!empty($user['avatar_url'])): ?>
          <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="" referrerpolicy="no-referrer" onerror="this.remove();">
        <?php endif; ?>
        <?php echo htmlspecialchars($initial); ?>
      </div>
      <div>
        <h1><?php echo htmlspecialchars($user['name']); ?></h1>
        <div class="la-hint" style="margin:2px 0 0;">සාමාජිකයා ලෙස එක්වූයේ <?php echo htmlspecialchars(substr((string) $user['created_at'], 0, 10)); ?></div>
      </div>
    </div>

    <!-- which sign-in methods are active on this account -->
    <div class="dash-chips" style="margin-bottom:0;">
      <?php if ($user['email']): ?>
        <span class="dash-chip"><i class="fa-solid fa-envelope"></i><?php echo htmlspecialchars($user['email']); ?><?php echo $user['email_verified_at'] ? ' ✓' : ''; ?></span>
      <?php endif; ?>
      <?php if ($phonePretty): ?>
        <span class="dash-chip"><i class="fa-brands fa-whatsapp"></i><?php echo htmlspecialchars($phonePretty); ?><?php echo $user['phone_verified_at'] ? ' ✓' : ''; ?></span>
      <?php endif; ?>
      <span class="dash-chip <?php echo $user['google_id'] ? '' : 'off'; ?>"><i class="fa-brands fa-google"></i>Google</span>
      <span class="dash-chip <?php echo $hasPassword ? '' : 'off'; ?>"><i class="fa-solid fa-key"></i><?php echo $hasPassword ? 'මුරපදය ඇත' : 'මුරපදය නැත'; ?></span>
    </div>
  </div>

  <!-- ===================== NEW: install as app banner (hidden when already installed) ===================== -->
  <a href="add-to-home.php" class="app-banner" id="appBanner">
    <span class="app-banner-ico"><i class="fa-solid fa-mobile-screen-button"></i></span>
    <span class="app-banner-txt">
      <b>Mobile App එකක් ලෙස භාවිතා කරන්න</b>
      <small>Browser එකේ type නොකර, Home screen එකෙන්ම open කරන්න — පියවර බලන්න</small>
    </span>
    <i class="fa-solid fa-chevron-right"></i>
  </a>

  <!-- ===================== plan ===================== -->
  <div class="auth-card">
    <h2 class="dash-sec-title"><i class="fa-solid fa-crown"></i> මගේ Plan</h2>

    <?php foreach ($plans as $p): ?>
      <div class="plan-item<?php echo $p['live'] ? '' : ' expired'; ?>">
        <div class="plan-top">
          <span class="plan-name"><?php echo htmlspecialchars($p['name']); ?></span>
          <span class="plan-state <?php echo $p['live'] ? 'on' : 'off'; ?>"><?php echo $p['live'] ? 'Active' : 'කල් ඉකුත් වී ඇත'; ?></span>
        </div>
        <div class="plan-meta">
          මට්ටම්: <b><?php echo htmlspecialchars(la_levels_label((string) $p['levels'])); ?></b><br>
          <?php if ($p['expires_at'] === null): ?>
            වලංගු කාලය: <b>ජීවිත කාලය පුරාම</b>
          <?php else: ?>
            <?php echo $p['live'] ? 'වලංගු' : 'කල් ඉකුත් වූයේ'; ?>:
            <b><?php echo htmlspecialchars(substr((string) $p['expires_at'], 0, 10)); ?></b><?php echo $p['live'] ? ' දක්වා' : ''; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <?php if (!$hasLive): ?>
      <div class="plan-none">
        <div class="la-guide">
          <?php echo $plans ? 'ඔබේ plan එක කල් ඉකුත් වී ඇත.' : 'ඔබට තවම active plan එකක් නැහැ.'; ?>
          Plan එකක් ගෙවා ගත් පසු අපි ඔබේ ගිණුම <b>activate</b> කරනවා.<br>
          අපට දැනුම් දෙන විට ඔබේ <b>ගිණුම් අංකය</b> එවන්න: <span class="plan-id">#<?php echo $uid; ?></span>
        </div>
        <div class="plan-contact">
          <?php if ($waLink): ?>
            <a class="la-btn" href="<?php echo htmlspecialchars($waLink); ?>" target="_blank" rel="noopener">
              <i class="fa-brands fa-whatsapp"></i> WhatsApp මගින් Activate කරන්න
            </a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- ===================== progress ===================== -->
  <div class="auth-card">
    <h2 class="dash-sec-title"><i class="fa-solid fa-chart-line"></i> මගේ ඉගෙනීමේ ප්‍රගතිය</h2>

    <div class="stat-grid">
      <div class="stat"><b><?php echo $sum['grammar']; ?></b><span>ඉගෙනගත් Grammar</span></div>
      <div class="stat"><b><?php echo $sum['mastered']; ?></b><span>Mastered</span></div>
      <div class="stat"><b><?php echo $sum['lessons']; ?></b><span>අවසන් පාඩම්</span></div>
      <div class="stat"><b><?php echo $quizAvg === null ? '–' : $quizAvg . '%'; ?></b><span>Quiz සාමාන්‍ය</span></div>
    </div>

    <?php foreach (LA_LEVELS as $lv):
        $r   = $prog[$lv];
        $tot = (int) $totals[$lv]['grammar'] + (int) $totals[$lv]['lesson'];
        $pct = $tot > 0 ? min(100, (int) round(($r['grammar'] + $r['lessons']) / $tot * 100)) : null;
        $act = ($r['grammar'] + $r['lessons'] + $r['quizzes']) > 0;
        $qa  = $r['quiz_total'] > 0 ? (int) round($r['quiz_score'] / $r['quiz_total'] * 100) : null;
    ?>
      <div class="lv-row<?php echo $act ? '' : ' empty'; ?>">
        <div class="lv-top">
          <span class="lv-name"><?php echo $lv; ?></span>
          <?php if (la_user_has_access($uid, $lv)): ?><span class="lv-badge"><i class="fa-solid fa-check"></i> Plan</span><?php endif; ?>
          <?php if ($pct !== null): ?><span class="lv-pct"><?php echo $pct; ?>%</span><?php endif; ?>
        </div>
        <?php if ($pct !== null): ?><div class="bar"><span style="width:<?php echo $pct; ?>%"></span></div><?php endif; ?>
        <div class="lv-meta">
          <span><i class="fa-solid fa-layer-group"></i>Grammar <b><?php echo $r['grammar']; ?></b><?php echo $r['mastered'] ? ' (mastered ' . $r['mastered'] . ')' : ''; ?></span>
          <span><i class="fa-solid fa-book-open"></i>පාඩම් <b><?php echo $r['lessons']; ?></b></span>
          <span><i class="fa-solid fa-circle-question"></i>Quiz <b><?php echo $r['quizzes']; ?></b><?php echo $qa !== null ? ' · ' . $qa . '%' : ''; ?></span>
        </div>
      </div>
    <?php endforeach; ?>

    <?php if (!$anyProg): ?>
      <div class="dash-empty">තවම ඉගෙනීම ආරම්භ කර නැහැ. Grammar cards, පාඩම් සහ quiz කරද්දී ඔබේ ප්‍රගතිය මෙතන පෙනේවි.</div>
    <?php endif; ?>
  </div>

  <!-- ===================== NEW: activity history ===================== -->
  <div class="auth-card" id="activity">
    <h2 class="dash-sec-title"><i class="fa-solid fa-clock-rotate-left"></i> මගේ Activity History</h2>

    <!-- filter chips (plain links, no JS needed) -->
    <div class="act-filters">
      <?php foreach ($actTypes as $key => [$label, $ico]):
          $cnt = $key === '' ? array_sum($actCounts) : (int) ($actCounts[$key] ?? 0); ?>
        <a class="act-chip<?php echo $actFilter === $key ? ' on' : ''; ?>"
           href="dashboard.php<?php echo $key !== '' ? '?act=' . $key : ''; ?>#activity">
          <i class="<?php echo (strpos($ico, 'fa-brands') === 0 ? $ico : 'fa-solid ' . $ico); ?>"></i>
          <?php echo htmlspecialchars($label); ?> <em><?php echo $cnt; ?></em>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($actRows): ?>
      <ul class="act-list">
        <?php foreach ($actRows as $a):
            [$ic, $col] = $actIcon[$a['type']] ?? $actIcon['other']; ?>
          <li class="act-item">
            <span class="act-ico" style="color:<?php echo $col; ?>;border-color:<?php echo $col; ?>33;background:<?php echo $col; ?>14;"><i class="<?php echo $ic; ?>"></i></span>
            <div class="act-body">
              <div class="act-title">
                <?php echo htmlspecialchars($a['title']); ?>
                <?php if ($a['level']): ?><span class="act-lv"><?php echo htmlspecialchars($a['level']); ?></span><?php endif; ?>
              </div>
              <?php if ($a['detail']): ?><div class="act-detail"><?php echo htmlspecialchars($a['detail']); ?></div><?php endif; ?>
            </div>
            <time class="act-time" title="<?php echo htmlspecialchars($a['created_at']); ?>"><?php echo la_time_ago($a['created_at']); ?></time>
          </li>
        <?php endforeach; ?>
      </ul>

      <?php if ($actMore): ?>
        <a class="la-btn la-btn-soft" style="display:block;text-align:center;text-decoration:none;margin-top:12px;"
           href="dashboard.php?<?php echo ($actFilter !== '' ? 'act=' . $actFilter . '&' : ''); ?>n=<?php echo $actLimit + 20; ?>#activity">තව පෙන්වන්න</a>
      <?php endif; ?>
    <?php else: ?>
      <div class="dash-empty">තවම activity නැහැ. පොත්, පාඩම්, quiz සහ WhatsApp පණිවිඩ මෙතන පෙනේවි.</div>
    <?php endif; ?>
  </div>

  <!-- ===================== password + logout ===================== -->
  <div class="auth-card">
    <div class="la-msg" id="laMsg" role="alert" aria-live="polite"></div>

    <!-- set / change password -->
    <div class="dash-sec" style="margin-top:0;">
      <h2><?php echo $hasPassword ? 'මුරපදය වෙනස් කරන්න' : 'මුරපදයක් සකසන්න'; ?></h2>
      <?php if (!$hasPassword): ?>
        <div class="la-guide">දැනට ඔබ <b>කේතයෙන් / Google මගින්</b> පමණක් ඇතුල් වේ. අවශ්‍ය නම් මුරපදයක් සකසන්න — නැතිනම් එලෙසම පවතිනු ඇත.</div>
      <?php endif; ?>

      <form id="formSetPw" novalidate>
        <?php if ($hasPassword): ?>
          <div class="la-field">
            <label for="curPw">දැනට ඇති මුරපදය</label>
            <input class="la-input" id="curPw" name="current" type="password" autocomplete="current-password" required>
          </div>
        <?php endif; ?>
        <div class="la-field">
          <label for="newPw">අලුත් මුරපදය</label>
          <div class="la-pass">
            <input class="la-input" id="newPw" name="new" type="password" autocomplete="new-password" placeholder="අකුරු 8ක් හෝ වැඩි" required>
            <button type="button" class="la-eye" data-eye aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
          </div>
        </div>
        <button type="submit" class="la-btn">සුරකින්න</button>
      </form>
    </div>

    <div class="dash-sec">
      <a href="user-logout.php" class="la-btn la-btn-soft" style="display:block;text-align:center;text-decoration:none;">
        <i class="fa-solid fa-right-from-bracket"></i> ඉවත් වන්න
      </a>
    </div>
  </div>
</div>

<script>window.LA = <?php echo json_encode($jsCfg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
<script src="assets/js/auth.js?v=2" defer></script>
<script>
// hide the "install app" banner when the site is already running as an installed app
(function () {
  var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  if (standalone) { var b = document.getElementById('appBanner'); if (b) b.style.display = 'none'; }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>