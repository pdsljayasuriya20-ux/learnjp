<?php
/**
 * user-register.php  -  public user registration
 *   Google  ·  email / WhatsApp number (verified with a one-time code)  ·  password is OPTIONAL
 */
require_once __DIR__ . '/includes/auth.php';

$next = la_safe_next($_GET['next'] ?? '');
if (!empty($_SESSION['user_id'])) { header('Location: ' . $next); exit; }

$csrf      = la_csrf();
$hasGoogle = LA_GOOGLE_CLIENT_ID !== '';

$pageTitle       = 'ලියාපදිංචි වන්න | Learn Japanese';
$pageDescription = 'Learn Japanese හි නොමිලේ ගිණුමක් සාදන්න.';

$jsText = [
    'network'   => 'ජාල දෝෂයක්. අන්තර්ජාල සම්බන්ධතාවය පරීක්ෂා කර නැවත උත්සාහ කරන්න.',
    'welcome'   => 'ගිණුම සාදා අවසන්! ඔබව රැගෙන යනවා…',
    'hintBoth'  => 'ඊමේල් ලිපිනය හෝ WhatsApp අංකය ඇතුළත් කරන්න',
    'hintEmail' => 'තහවුරු කිරීමේ කේතය ඔබේ ඊමේල් එකට එවනු ලැබේ',
    'hintPhone' => 'තහවුරු කිරීමේ කේතය WhatsApp මගින් එවනු ලැබේ',
    'viaWa'     => 'WhatsApp කේතය එවා ඇත:',
    'viaMail'   => 'ඊමේල් කේතය එවා ඇත:',
    'resend'    => 'නැවත එවන්න',
    'resendIn'  => 'නැවත එවන්න ({s}s)',
];
$jsCfg = [
    'csrf'   => $csrf,
    'api'    => 'auth-api.php',
    'google' => LA_GOOGLE_CLIENT_ID,
    'next'   => $next,
    'gText'  => 'signup_with',
    't'      => $jsText,
];

require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="assets/css/auth.css">

<div class="auth-wrap">
  <div class="auth-card">

    <div class="auth-head">
      <div class="ico"><i class="fa-solid fa-user-plus" aria-hidden="true"></i></div>
      <h1>ලියාපදිංචි වන්න</h1>
      <p>නොමිලේ ගිණුමක් සාදා ඉගෙනීම ආරම්භ කරන්න</p>
    </div>

    <div class="la-msg" id="laMsg" role="alert" aria-live="polite"></div>

    <?php if ($hasGoogle): ?>
      <div class="la-google"><div id="gbtn"></div></div>
      <div class="la-divider"><span>හෝ ඊමේල් / WhatsApp මගින්</span></div>
    <?php endif; ?>

    <div data-flow data-purpose="register">

      <ol class="la-steps" data-steps>
        <li>ඔබේ<br>විස්තර</li>
        <li>කේතය<br>තහවුරු කරන්න</li>
        <li>ගිණුම<br>සූදානම්!</li>
      </ol>

      <!-- step 1: details -->
      <form data-stage="1" novalidate>
        <div class="la-field">
          <label for="regName">ඔබේ නම</label>
          <input class="la-input" id="regName" data-name type="text" autocomplete="name" maxlength="60" placeholder="උදා: කමල් පෙරේරා" required>
        </div>

        <div class="la-field">
          <label for="regId">ඊමේල් හෝ WhatsApp අංකය</label>
          <div class="la-row">
            <select class="la-select" data-cc hidden aria-label="Country code"><?php echo la_cc_options(); ?></select>
            <input class="la-input" id="regId" data-ident type="text" inputmode="email" autocomplete="username"
                   autocapitalize="off" spellcheck="false" placeholder="you@gmail.com / 0771234567" required>
          </div>
          <div class="la-hint" data-ident-hint></div>
        </div>

        <div class="la-field">
          <label for="regPass">මුරපදය <span class="opt">(අවශ්‍ය නැත)</span></label>
          <div class="la-pass">
            <input class="la-input" id="regPass" data-pass type="password" autocomplete="new-password" placeholder="අකුරු 8ක් හෝ වැඩි">
            <button type="button" class="la-eye" data-eye aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
          </div>
          <div class="la-hint">මුරපදයක් නැතිව වුවත් කමක් නැහැ — ඊළඟ වතාවේ ඊමේල් / WhatsApp කේතයෙන් ඇතුල් විය හැක.</div>
        </div>

        <button type="submit" class="la-btn">කේතයක් එවන්න</button>
      </form>

      <!-- step 2: verify -->
      <form data-stage="2" novalidate hidden>
        <div class="la-guide"><span data-sent-to></span><br>එම කේතය පහත ඇතුළත් කරන්න. (විනාඩි 10ක් වලංගුයි)</div>
        <div class="la-field">
          <label for="regCode">ඉලක්කම් 6ක කේතය</label>
          <input class="la-input la-code-input" id="regCode" data-code type="text" inputmode="numeric"
                 autocomplete="one-time-code" maxlength="6" placeholder="••••••" required>
        </div>
        <button type="submit" class="la-btn">තහවුරු කර ගිණුම සාදන්න</button>
        <div class="la-between">
          <button type="button" class="la-link" data-change>← විස්තර වෙනස් කරන්න</button>
          <button type="button" class="la-link" data-resend disabled>නැවත එවන්න</button>
        </div>
      </form>

    </div>

    <div class="la-foot">
      දැනටමත් ගිණුමක් ඇද්ද? <a class="la-link" href="user-login.php<?php echo $next !== LA_AFTER_LOGIN ? '?next=' . urlencode($next) : ''; ?>">ඇතුල් වන්න</a>
    </div>
  </div>
</div>

<script>window.LA = <?php echo json_encode($jsCfg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
<?php if ($hasGoogle): ?><script src="https://accounts.google.com/gsi/client" async defer></script><?php endif; ?>
<script src="assets/js/auth.js" defer></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
