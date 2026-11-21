<?php
/**
 * add-to-home.php - step-by-step guide: add learnjapanese.jp to the phone Home Screen like an app
 * Android (Chrome / Samsung Internet) + iPhone (Safari). Works for logged-out visitors too.
 */
require_once __DIR__ . '/includes/progress.php';   // session + helpers (same as dashboard)

$pageTitle = 'App එකක් ලෙස Install කරන්න | Learn Japanese';
$isUser    = !empty($_SESSION['user_id']);
require_once __DIR__ . '/includes/header.php';
?>
<style>
  /* ---- page-only styles (kept inline so this file is self-contained) ---- */
  .ath{max-width:720px;margin:0 auto;padding:16px}
  .ath-hero{text-align:center;padding:24px 8px}
  .ath-hero .ico{width:84px;height:84px;margin:0 auto 14px;border-radius:22px;display:grid;place-items:center;font-size:2.4rem;color:#fff;
    background:linear-gradient(135deg,#8338ec,#3a86ff);box-shadow:0 12px 30px rgba(131,56,236,.35)}
  .ath-hero h1{font-size:1.5rem;margin:0 0 6px}
  .ath-hero p{opacity:.75;margin:0}
  .ath-card{border-radius:22px;padding:20px;margin-bottom:16px;border:1px solid rgba(128,128,128,.2);background:rgba(128,128,128,.06)}
  .ath-tabs{display:flex;gap:8px;margin-bottom:16px}
  .ath-tab{flex:1;padding:12px;border-radius:14px;border:1px solid rgba(128,128,128,.25);background:transparent;color:inherit;
    font-weight:700;cursor:pointer;font-size:.95rem}
  .ath-tab.on{background:linear-gradient(45deg,#ff006e,#8338ec);color:#fff;border-color:transparent}
  .ath-pane{display:none}.ath-pane.on{display:block}
  .ath-steps{list-style:none;counter-reset:s;margin:0;padding:0}
  .ath-steps li{counter-increment:s;position:relative;padding:0 0 18px 52px;line-height:1.55}
  .ath-steps li::before{content:counter(s);position:absolute;left:0;top:0;width:36px;height:36px;border-radius:50%;
    display:grid;place-items:center;font-weight:800;color:#fff;background:linear-gradient(135deg,#ff006e,#8338ec)}
  .ath-steps li:not(:last-child)::after{content:'';position:absolute;left:17px;top:38px;bottom:2px;width:2px;background:rgba(128,128,128,.25)}
  .ath-steps b{display:block;margin-bottom:2px}
  .ath-steps small{opacity:.7;display:block}
  .ath-key{display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:8px;background:rgba(128,128,128,.18);font-weight:600;white-space:nowrap}
  .ath-btn{display:block;width:100%;padding:14px;border:0;border-radius:50px;color:#fff;font-weight:800;font-size:1rem;cursor:pointer;
    background:linear-gradient(45deg,#ff006e,#8338ec);text-align:center;text-decoration:none;margin-top:6px}
  .ath-btn[disabled]{opacity:.5;cursor:not-allowed}
  .ath-note{font-size:.82rem;opacity:.7;margin-top:10px;line-height:1.5}
  .ath-warn{background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.35);border-radius:14px;padding:12px 14px;font-size:.85rem;margin-bottom:14px}
  .ath-done{display:none;text-align:center;padding:20px}
  .ath-why{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px}
  .ath-why div{padding:12px;border-radius:14px;background:rgba(128,128,128,.08);font-size:.85rem}
  .ath-why i{display:block;font-size:1.2rem;margin-bottom:6px;color:#8338ec}
</style>

<div class="ath">

  <div class="ath-hero">
    <div class="ico"><i class="fa-solid fa-mobile-screen-button"></i></div>
    <h1>App එකක් ලෙස Install කරන්න</h1>
    <p>Browser එකේ web address type නොකර, Home screen එකෙන්ම open කරන්න</p>
  </div>

  <!-- already installed -->
  <div class="ath-card ath-done" id="athDone">
    <div style="font-size:2.4rem;">✅</div>
    <h2 style="margin:6px 0;">ඔබ දැනටමත් App එක භාවිතා කරනවා!</h2>
    <a class="ath-btn" href="<?php echo $isUser ? 'dashboard.php' : 'index.php'; ?>">ඉදිරියට යන්න</a>
  </div>

  <div id="athMain">

    <div class="ath-card">
      <div class="ath-why">
        <div><i class="fa-solid fa-bolt"></i>එක tap එකකින් open වෙනවා</div>
        <div><i class="fa-solid fa-expand"></i>Full screen — address bar නැහැ</div>
        <div><i class="fa-solid fa-user-check"></i>Login එක මතකයි</div>
        <div><i class="fa-solid fa-hard-drive"></i>ඉඩ ගන්නේ නැහැ (free)</div>
      </div>
    </div>

    <!-- device tabs (auto-selected by JS) -->
    <div class="ath-tabs">
      <button class="ath-tab" data-tab="android"><i class="fa-brands fa-android"></i> Android</button>
      <button class="ath-tab" data-tab="ios"><i class="fa-brands fa-apple"></i> iPhone</button>
    </div>

    <!-- ============ ANDROID ============ -->
    <div class="ath-pane" id="pane-android">
      <div class="ath-card">
        <!-- one-tap install (only appears if Chrome fires beforeinstallprompt) -->
        <button class="ath-btn" id="athInstall" style="display:none;margin:0 0 18px;"><i class="fa-solid fa-download"></i> එක tap එකකින් Install කරන්න</button>

        <h3 style="margin:0 0 14px;">අතින් කරන ක්‍රමය (Chrome)</h3>
        <ol class="ath-steps">
          <li><b>Chrome එකෙන් මේ site එක open කරන්න</b>
            <small>Facebook / WhatsApp ඇතුළත browser එකෙන් නොවේ — "Open in Chrome" කරන්න.</small></li>
          <li><b>දකුණු පැත්තේ උඩ <span class="ath-key"><i class="fa-solid fa-ellipsis-vertical"></i> ⋮ (තිත් 3)</span> tap කරන්න</b></li>
          <li><b><span class="ath-key">Add to Home screen</span> හෝ <span class="ath-key">Install app</span> තෝරන්න</b>
            <small>සමහර phone වල "Install app" කියලා පෙනේ.</small></li>
          <li><b>පෙනෙන box එකේ <span class="ath-key">Install</span> / <span class="ath-key">Add</span> tap කරන්න</b></li>
          <li><b>Home screen එකේ <i class="fa-solid fa-mobile-screen-button"></i> Learn Japanese icon එක තියෙනවා 🎉</b>
            <small>ඒක tap කළාම app එකක් වගේම open වෙනවා.</small></li>
        </ol>

        <div class="ath-note"><b>Samsung Internet:</b> යටි menu එකේ <span class="ath-key">☰</span> → <span class="ath-key">Add page to</span> → <span class="ath-key">Home screen</span>.</div>
      </div>
    </div>

    <!-- ============ IPHONE ============ -->
    <div class="ath-pane" id="pane-ios">
      <div class="ath-card">
        <div class="ath-warn"><i class="fa-solid fa-triangle-exclamation"></i> iPhone වල මේක <b>Safari</b> එකෙන්ම කළ යුතුයි. Chrome / Facebook / WhatsApp browser වල "Add to Home Screen" නැහැ.</div>

        <ol class="ath-steps">
          <li><b>Safari එකෙන් මේ site එක open කරන්න</b>
            <small>Link එක copy කරලා Safari එකේ paste කරන්න පුළුවන්.</small></li>
          <li><b>යටි bar එකේ <span class="ath-key"><i class="fa-solid fa-arrow-up-from-bracket"></i> Share</span> button එක tap කරන්න</b>
            <small>(කොටුවකින් උඩට express වෙන arrow එක). iPad නම් උඩ දකුණේ.</small></li>
          <li><b>පහළට scroll කරලා <span class="ath-key"><i class="fa-regular fa-square-plus"></i> Add to Home Screen</span> tap කරන්න</b>
            <small>නොපෙනේ නම් list එකේ අගට ගොස් <i>Edit Actions</i> ක් තියේ නම් එයින් add කරන්න.</small></li>
          <li><b>නම පරීක්ෂා කරලා උඩ දකුණේ <span class="ath-key">Add</span> tap කරන්න</b></li>
          <li><b>Home screen එකේ icon එක තියෙනවා 🎉</b>
            <small>ඒක tap කළාම address bar නැතිව app එකක් වගේ open වෙනවා.</small></li>
        </ol>
      </div>
    </div>

    <div class="ath-card" style="text-align:center;">
      <a class="ath-btn" style="background:rgba(128,128,128,.18);color:inherit;" href="<?php echo $isUser ? 'dashboard.php' : 'index.php'; ?>">← ආපසු</a>
    </div>
  </div>
</div>

<script>
(function () {
  var ua  = navigator.userAgent || '';
  var isIOS = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

  // already installed -> show "done" card
  if (standalone) {
    document.getElementById('athMain').style.display = 'none';
    document.getElementById('athDone').style.display = 'block';
    return;
  }

  // tabs
  function show(tab) {
    document.querySelectorAll('.ath-tab').forEach(function (b) { b.classList.toggle('on', b.dataset.tab === tab); });
    document.querySelectorAll('.ath-pane').forEach(function (p) { p.classList.toggle('on', p.id === 'pane-' + tab); });
  }
  document.querySelectorAll('.ath-tab').forEach(function (b) { b.addEventListener('click', function () { show(b.dataset.tab); }); });
  show(isIOS ? 'ios' : 'android');                 // auto-pick by device

  // Android Chrome: real one-tap install
  var deferred = null;
  var btn = document.getElementById('athInstall');
  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault(); deferred = e; btn.style.display = 'block';
  });
  btn.addEventListener('click', function () {
    if (!deferred) return;
    deferred.prompt();
    deferred.userChoice.then(function () { deferred = null; btn.style.display = 'none'; });
  });
  window.addEventListener('appinstalled', function () { location.reload(); });

  // register service worker (required for Android install prompt)
  if ('serviceWorker' in navigator) navigator.serviceWorker.register('sw.js').catch(function () {});
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>