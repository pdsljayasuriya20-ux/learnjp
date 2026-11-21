<?php
/**
 * user-login.php  -  public user login
 *   Google  ·  email / WhatsApp + password  ·  one-time code (guided, for forgotten passwords)
 *
 * (The old login.php stays as the ADMIN login.)
 */
require_once __DIR__ . '/includes/auth.php';

$next = la_safe_next($_GET['next'] ?? '');
if (!empty($_SESSION['user_id'])) { header('Location: ' . $next); exit; }

$csrf = la_csrf();
$tab  = (($_GET['mode'] ?? '') === 'code') ? 'code' : 'password';
$hasGoogle = LA_GOOGLE_CLIENT_ID !== '';
$loggedOut = isset($_GET['logged_out']);

// SEO overrides read by header.php
$pageTitle       = 'ඇතුල් වන්න | Learn Japanese';
$pageDescription = 'Learn Japanese ගිණුමට ඇතුල් වන්න.';

// texts handed to assets/js/auth.js
$jsText = [
    'network'    => 'ජාල දෝෂයක්. අන්තර්ජාල සම්බන්ධතාවය පරීක්ෂා කර නැවත උත්සාහ කරන්න.',
    'welcome'    => 'සාර්ථකයි! ඔබව රැගෙන යනවා…',
    'hintBoth'   => 'ඊමේල් ලිපිනය හෝ WhatsApp අංකය ඇතුළත් කරන්න',
    'hintEmail'  => 'කේතය ඔබේ ඊමේල් එකට එවනු ලැබේ',
    'hintPhone'  => 'කේතය WhatsApp මගින් එවනු ලැබේ',
    'forgotInfo' => 'කමක් නැහැ! පහත පියවර 3 අනුගමනය කර මුරපදයෙන් තොරව ඇතුල් වන්න.',
    'viaWa'      => 'WhatsApp කේතය එවා ඇත:',
    'viaMail'    => 'ඊමේල් කේතය එවා ඇත:',
    'resend'     => 'නැවත එවන්න',
    'resendIn'   => 'නැවත එවන්න ({s}s)',
];
$jsCfg = [
    'csrf'   => $csrf,
    'api'    => 'auth-api.php',
    'google' => LA_GOOGLE_CLIENT_ID,
    'next'   => $next,
    'tab'    => $tab,
    'gText'  => 'signin_with',
    't'      => $jsText,
];

require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="assets/css/auth.css">

<div class="auth-wrap">
  <div class="auth-card">

    <div class="auth-head">
      <div class="ico"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i></div>
      <h1>ඇතුල් වන්න</h1>
      <p>ඔබේ ගිණුමට පිවිසෙන්න</p>
    </div>

    <div class="la-msg<?php echo $loggedOut ? ' show ok' : ''; ?>" id="laMsg" role="alert" aria-live="polite"><?php
        echo $loggedOut ? 'ඔබ සාර්ථකව ඉවත් විය.' : ''; ?></div>

    <?php if ($hasGoogle): ?>
      <!-- Google button is drawn into #gbtn by assets/js/auth.js -->
      <div class="la-google"><div id="gbtn"></div></div>
      <div class="la-divider"><span>හෝ</span></div>
    <?php endif; ?>

    <div class="la-tabs" role="tablist">
      <button type="button" class="la-tab active" data-tab="password" role="tab">මුරපදයෙන්</button>
      <button type="button" class="la-tab" data-tab="code" role="tab">කේතයෙන් (මුරපදය නැතිව)</button>
    </div>

    <!-- ============ TAB 1: password ============ -->
    <div data-panel="password">
      <form id="formPw" autocomplete="on" novalidate>
        <div class="la-field">
          <label for="pwId">ඊමේල් හෝ WhatsApp අංකය</label>
          <div class="la-row">
            <select class="la-select" data-cc hidden aria-label="Country code"><?php echo la_cc_options(); ?></select>
            <input class="la-input" id="pwId" data-ident type="text" inputmode="email" autocomplete="username"
                   autocapitalize="off" spellcheck="false" placeholder="you@gmail.com / 0771234567" required>
          </div>
          <div class="la-hint" data-ident-hint></div>
        </div>

        <div class="la-field">
          <label for="pwPass">මුරපදය</label>
          <div class="la-pass">
            <input class="la-input" id="pwPass" type="password" autocomplete="current-password" placeholder="••••••••" required>
            <button type="button" class="la-eye" data-eye aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
          </div>
        </div>

        <button type="submit" class="la-btn">ඇතුල් වන්න</button>

        <div class="la-between">
          <button type="button" class="la-link" id="forgotBtn"><i class="fa-solid fa-key"></i> මුරපදය මතක නැද්ද?</button>
        </div>
      </form>
    </div>

    <!-- ============ TAB 2: one-time code (guided) ============ -->
    <div data-panel="code" hidden>
      <div data-flow data-purpose="login">

        <ol class="la-steps" data-steps>
          <li>ඊමේල් / WhatsApp<br>ඇතුළත් කරන්න</li>
          <li>එවූ කේතය<br>ඇතුළත් කරන්න</li>
          <li>ඇතුල් විය!</li>
        </ol>

        <!-- step 1 -->
        <form data-stage="1" novalidate>
          <div class="la-guide">
            <b>මුරපදය අමතක වුණාද?</b> ගැටලුවක් නැහැ.<br>
            1) ඔබේ ඊමේල් හෝ WhatsApp අංකය ඇතුළත් කරන්න.<br>
            2) අපි එවන ඉලක්කම් 6ක කේතය ඇතුළත් කරන්න.<br>
            3) ඇතුල් වූ පසු <b>Dashboard</b> එකෙන් අලුත් මුරපදයක් සකසන්න (අවශ්‍ය නම් පමණි).
          </div>
          <div class="la-field">
            <label for="codeId">ඊමේල් හෝ WhatsApp අංකය</label>
            <div class="la-row">
              <select class="la-select" data-cc hidden aria-label="Country code"><?php echo la_cc_options(); ?></select>
              <input class="la-input" id="codeId" data-ident type="text" inputmode="email" autocomplete="username"
                     autocapitalize="off" spellcheck="false" placeholder="you@gmail.com / 0771234567" required>
            </div>
            <div class="la-hint" data-ident-hint></div>
          </div>
          <button type="submit" class="la-btn">කේතයක් එවන්න</button>
        </form>

        <!-- step 2 -->
        <form data-stage="2" novalidate hidden>
          <div class="la-guide"><span data-sent-to></span><br>එම කේතය පහත ඇතුළත් කරන්න. (විනාඩි 10ක් වලංගුයි)</div>
          <div class="la-field">
            <label for="codeInput">ඉලක්කම් 6ක කේතය</label>
            <input class="la-input la-code-input" id="codeInput" data-code type="text" inputmode="numeric"
                   autocomplete="one-time-code" maxlength="6" placeholder="••••••" required>
          </div>
          <button type="submit" class="la-btn">තහවුරු කර ඇතුල් වන්න</button>
          <div class="la-between">
            <button type="button" class="la-link" data-change>← වෙනත් ඊමේල් / අංකයක්</button>
            <button type="button" class="la-link" data-resend disabled>නැවත එවන්න</button>
          </div>
        </form>

      </div>
    </div>

    <div class="la-foot">
      ගිණුමක් නැද්ද? <a class="la-link" href="user-register.php<?php echo $next !== LA_AFTER_LOGIN ? '?next=' . urlencode($next) : ''; ?>">ලියාපදිංචි වන්න</a>
    </div>
  </div>
</div>

<script>window.LA = <?php echo json_encode($jsCfg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
<?php if ($hasGoogle): ?><script src="https://accounts.google.com/gsi/client" async defer></script><?php endif; ?>
<script src="assets/js/auth.js" defer></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
