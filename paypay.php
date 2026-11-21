<?php require_once __DIR__ . '/includes/header.php'; ?>
<?php
// ============================================================
//  SETTINGS - methana wenas karanna
// ============================================================
$whatsappNumber  = '819050295822';       // WhatsApp number (+ nathuwa, country code ekka, 0 nathuwa)
$whatsappDisplay = '+81 90-5029-5822';
$paypayId        = '';                   // ⚠️ Oyage PayPay ID eka methana danna (e.g. 'miraijp'). Empty nam "ID ekata yawanna" section eka hide wenawa
$booksPageUrl    = 'books.php';

$appStoreUrl     = 'https://apps.apple.com/jp/app/paypay/id1435783608';
$playStoreUrl    = 'https://play.google.com/store/apps/details?id=jp.ne.paypay.android.app';

$hasId = ($paypayId !== '');

// ---------- Texts (en / si). Aniwa languages walata 'en' fallback wenawa ----------
$K = [
 'en' => [
  'title'      => 'How to pay with PayPay',
  'subtitle'   => 'Install the PayPay app, top up your balance, then pay us by PayPay ID or with a PayPay request link.',
  'step1'      => 'Step 1 · Download the PayPay app',
  'step1_d'    => 'PayPay is free. Download it from your phone\'s store.',
  'ios'        => 'iPhone (App Store)',
  'android'    => 'Android (Google Play)',
  'step2'      => 'Step 2 · Install & set up',
  'inst'       => [
     'Open the app and sign up with your mobile number. Verify the SMS code.',
     'Complete identity verification (本人確認) in the app with your Residence Card / ID. You need this to link a bank account.',
     'Your PayPay wallet is now ready.',
  ],
  'step3'      => 'Step 3 · Top up your balance',
  'step3_d'    => 'Choose the method that is easiest for you. Menu names may change slightly with app updates.',
  'konbini'    => 'Convenience store ATM (cash)',
  'konbini_s'  => [
     'Go to a <b>Seven-Eleven (Seven Bank ATM)</b> or <b>Lawson (Lawson Bank ATM)</b>. Other stores such as FamilyMart do not support PayPay cash top-up.',
     'On the ATM, choose <b>Smartphone transaction</b> (スマートフォンでの取引) and then <b>Charge</b>.',
     'In PayPay tap <b>[+]</b> on the home screen → <b>ATM Charge</b>, and scan the QR code shown on the ATM.',
     'Enter the number shown in the app on the ATM (finish within 10 minutes), insert your cash and confirm.',
     'Note: ATMs may not give change, so prepare the exact amount.',
  ],
  'bank'       => 'Bank account',
  'bank_s'     => [
     'In PayPay open <b>Account</b> → <b>Manage payment methods</b> (支払い方法の管理) and add your bank account (identity verification needed).',
     'Follow the bank\'s screens to approve the link.',
     'On the home screen tap <b>[+] / Charge</b>, choose your bank account and enter the amount.',
     'Tap <b>Charge</b>. Your balance updates right away.',
  ],
  'step4'      => 'Step 4 · Pay for your book',
  'step4_d'    => 'Pick one of the ways below.',
  'optA'       => 'Option A · Send money to our PayPay ID',
  'kid_label'  => 'Our PayPay ID',
  'copy'       => 'Copy',
  'copied'     => 'Copied!',
  'optA_s'     => [
     'In PayPay, tap <b>Send</b> (送る) on the home screen.',
     'Search for our PayPay ID <b>%s</b> and select it.',
     'Enter the total amount of your book order and send.',
     'Send us a WhatsApp message with your order and a screenshot of the payment so we can confirm it.',
  ],
  'optB'       => 'Option B · Ask for a request link',
  'optB_d'     => 'Message us on WhatsApp with your book name and quantity. We will create a PayPay request link for the exact amount of your book order and send it to you. Open the link and pay.',
  'optB_s'     => [
     'Tap the WhatsApp button below and send us your order.',
     'We reply with a <b>PayPay request link</b> (請求リンク) for your book amount.',
     'Open the link in the PayPay app and confirm the payment.',
  ],
  'wa_btn'     => 'Message us on WhatsApp',
  'wa_msg'     => 'Hello! I want to pay with PayPay. Please send me a PayPay request link for my book order amount. Book name and quantity: ',
  'books_title'=> 'Ready to order?',
  'books_d'    => 'Pick your book and come back here when you are ready to pay.',
  'books_btn'  => 'Go to Books',
  'note'       => 'PayPay is a service of PayPay Corporation. Fees and limits are set by PayPay.',
 ],
 'si' => [
  'title'      => 'PayPay මගින් ගෙවන ආකාරය',
  'subtitle'   => 'PayPay app එක install කර, balance එක top up කර, අපගේ PayPay ID එකට මුදල් යවන්න, නැත්නම් PayPay request link එකකින් ගෙවන්න.',
  'step1'      => 'පියවර 1 · PayPay app එක download කරන්න',
  'step1_d'    => 'PayPay නොමිලේ ලැබේ. ඔබේ දුරකථනයේ store එකෙන් download කරන්න.',
  'ios'        => 'iPhone (App Store)',
  'android'    => 'Android (Google Play)',
  'step2'      => 'පියවර 2 · Install සහ setup කරන්න',
  'inst'       => [
     'App එක open කර ඔබේ mobile number එකෙන් sign up වී SMS code එක තහවුරු කරන්න.',
     'App එකේ Residence Card / ID එකෙන් අනන්‍යතාව තහවුරු කරන්න (本人確認). බැංකු ගිණුමක් සම්බන්ධ කිරීමට මෙය අවශ්‍යයි.(මෙය සිදු නොකර වූවද මුදල් ගනුදෙනු සිදු කල හැක)',
     'ඔබේ PayPay wallet එක දැන් සූදානම්.',
  ],
  'step3'      => 'පියවර 3 · Balance එක top up කරන්න',
  'step3_d'    => 'ඔබට පහසුම ක්‍රමය තෝරන්න. App update සමඟ menu නම් තරමක් වෙනස් විය හැක.',
  'konbini'    => 'Convenience store ATM (මුදල්)',
  'konbini_s'  => [
     '<b>Seven-Eleven (Seven Bank ATM)</b> හෝ <b>Lawson (Lawson Bank ATM)</b> එකකට යන්න. FamilyMart වැනි වෙනත් සාප්පුවල PayPay cash top-up කළ නොහැක.',
     'ATM එකේ <b>Smartphone transaction</b> (スマートフォンでの取引) ඉන්පසු <b>Charge</b> තෝරන්න.',
     'PayPay home screen එකේ <b>[+]</b> → <b>ATM Charge</b> ඔබා ATM එකේ පෙන්වන QR code එක scan කරන්න.',
     'App එකේ පෙන්වන අංකය ATM එකේ ඇතුළත් කර (විනාඩි 10ක් ඇතුළත), මුදල් දමා තහවුරු කරන්න.',
     'සටහන: ATM වලින් ඉතිරි මුදල් නොලැබිය හැක. නිවැරදි මුදල සූදානම් කරගන්න.',
  ],
  'bank'       => 'Bank account',
  'bank_s'     => [
     'PayPay හි <b>Account</b> → <b>Manage payment methods</b> (支払い方法の管理) තුළ ඔබේ බැංකු ගිණුම add කරන්න (අනන්‍යතා තහවුරු කිරීම අවශ්‍යයි).',
     'බැංකුවේ තිරවල උපදෙස් අනුව සම්බන්ධ කිරීම අනුමත කරන්න.',
     'Home screen එකේ <b>[+] / Charge</b> ඔබා ඔබේ බැංකු ගිණුම තෝරා මුදල ඇතුළත් කරන්න.',
     '<b>Charge</b> ඔබන්න. Balance එක වහාම update වේ.',
  ],
  'step4'      => 'පියවර 4 · ඔබේ පොතට ගෙවන්න',
  'step4_d'    => 'පහත ක්‍රමවලින් එකක් තෝරන්න.',
  'optA'       => 'ක්‍රමය A · අපගේ PayPay ID එකට මුදල් යවන්න',
  'kid_label'  => 'අපගේ PayPay ID',
  'copy'       => 'Copy',
  'copied'     => 'Copy විය!',
  'optA_s'     => [
     'PayPay home screen එකේ <b>Send</b> (送る) ඔබන්න.',
     'අපගේ PayPay ID <b>%s</b> සොයා තෝරන්න.',
     'ඔබේ පොත් ඇණවුමේ මුළු මුදල ඇතුළත් කර යවන්න.',
     'ඇණවුමේ විස්තර සහ ගෙවීමේ screenshot එකක් WhatsApp හරහා එවන්න. එවිට අපට තහවුරු කරගත හැක.',
  ],
  'optB'       => 'ක්‍රමය B · Request link එකක් ඉල්ලන්න',
  'optB_d'     => 'ඔබේ පොතේ නම සහ ප්‍රමාණය WhatsApp හරහා අපට එවන්න. අපි ඔබේ පොත් ඇණවුමේ නිවැරදි මුදලට PayPay request link එකක් සාදා ඔබට එවන්නෙමු. එම link එක open කර ගෙවන්න.',
  'optB_s'     => [
     'පහත WhatsApp බොත්තම ඔබා ඔබේ ඇණවුම අපට එවන්න.',
     'අපි ඔබේ පොතේ මුදලට <b>PayPay request link</b> (請求リンク) එකක් එවන්නෙමු.',
     'Link එක PayPay app එකේ open කර ගෙවීම තහවුරු කරන්න.',
  ],
  'wa_btn'     => 'WhatsApp හරහා අපට පණිවිඩයක් එවන්න',
  'wa_msg'     => 'Hello! I want to pay with PayPay. Please send me a PayPay request link for my book order amount. Book name and quantity: ',
  'books_title'=> 'Order කිරීමට සූදානම්ද?',
  'books_d'    => 'ඔබේ පොත තෝරන්න. ගෙවීමට සූදානම් වූ පසු නැවත මෙහි එන්න.',
  'books_btn'  => 'Books වෙත යන්න',
  'note'       => 'PayPay යනු PayPay Corporation සේවාවකි. ගාස්තු සහ සීමා PayPay විසින් තීරණය කෙරේ.',
 ],
];
$lang = (isset($currentLang) && isset($K[$currentLang])) ? $currentLang : 'en';
$k = $K[$lang];

$waUrl = 'https://wa.me/' . preg_replace('/\D+/', '', $whatsappNumber)
       . '?text=' . rawurlencode($k['wa_msg']);
?>

<style>
    .pp-hero { background: linear-gradient(135deg, #ff0033, #ff5a5f); color: #fff; border-radius: 20px; padding: 24px 20px; }
    .pp-step { display: flex; gap: 12px; align-items: flex-start; }
    .pp-num {
        flex-shrink: 0; width: 28px; height: 28px; border-radius: 50%;
        background: #ffe4e8; color: #e11d48; font-weight: 700; font-size: .85rem;
        display: flex; align-items: center; justify-content: center; margin-top: 1px;
    }
    .pp-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px; }
    .pp-store {
        display: flex; align-items: center; gap: 12px; padding: 14px 16px;
        border-radius: 14px; border: 1px solid #ffe4e8; background: #fff;
        text-decoration: none; color: inherit; font-weight: 600;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .pp-store:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(225,29,72,.15); color: inherit; }
    .pp-store i { font-size: 1.7rem; color: #e11d48; }
    .pp-store small { display: block; font-weight: 400; color: #6b7280; font-size: .75rem; }
    .pp-method { background: #fff8f9; border: 1px solid #ffe4e8; border-radius: 16px; padding: 16px; height: 100%; }
    .pp-method h3 { font-size: 1rem; font-weight: 700; margin-bottom: 12px; }
    .pp-id-box {
        display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;
        background: #fff; border: 2px dashed #fda4af; border-radius: 14px; padding: 12px 14px; margin-bottom: 14px;
    }
    .pp-id-label { font-size: .75rem; color: #6b7280; font-weight: 600; }
    .pp-id-val { font-size: 1.35rem; font-weight: 800; color: #e11d48; letter-spacing: .02em; word-break: break-all; }
    .pp-copy {
        background: #e11d48; color: #fff; border: 0; border-radius: 10px;
        padding: 8px 16px; font-weight: 700; font-size: .85rem;
    }
    .pp-copy.done { background: #16a34a; }
    .pp-wa {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%;
        background: #25d366; color: #fff; border: 0; border-radius: 12px;
        padding: 13px; font-weight: 700; font-size: 1rem; text-decoration: none;
    }
    .pp-wa:hover { color: #fff; filter: brightness(.95); }
    .pp-wa-num { text-align: center; font-size: .8rem; color: #6b7280; margin-top: 8px; }
    .pp-note { font-size: .78rem; color: #9ca3af; text-align: center; }
</style>

        <!-- Hero -->
        <div class="pp-hero mb-4">
            <div class="fs-1 mb-1" aria-hidden="true">📱</div>
            <h1 class="fw-extrabold fs-3 mb-2"><?php echo $k['title']; ?></h1>
            <p class="mb-0 opacity-90"><?php echo $k['subtitle']; ?></p>
        </div>

        <!-- Step 1: Download -->
        <div class="app-card mb-3">
            <h2 class="fs-5 fw-bold mb-1"><?php echo $k['step1']; ?></h2>
            <p class="text-muted small mb-3"><?php echo $k['step1_d']; ?></p>
            <div class="row g-2">
                <div class="col-12 col-sm-6">
                    <a class="pp-store" href="<?php echo htmlspecialchars($appStoreUrl); ?>" target="_blank" rel="noopener noreferrer">
                        <i class="fa-brands fa-apple" aria-hidden="true"></i>
                        <span><?php echo $k['ios']; ?></span>
                    </a>
                </div>
                <div class="col-12 col-sm-6">
                    <a class="pp-store" href="<?php echo htmlspecialchars($playStoreUrl); ?>" target="_blank" rel="noopener noreferrer">
                        <i class="fa-brands fa-google-play" aria-hidden="true"></i>
                        <span><?php echo $k['android']; ?></span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Step 2: Install -->
        <div class="app-card mb-3">
            <h2 class="fs-5 fw-bold mb-3"><?php echo $k['step2']; ?></h2>
            <ol class="pp-list">
                <?php foreach ($k['inst'] as $i => $s): ?>
                    <li class="pp-step"><span class="pp-num"><?php echo $i + 1; ?></span><span><?php echo $s; ?></span></li>
                <?php endforeach; ?>
            </ol>
        </div>

        <!-- Step 3: Top up -->
        <div class="app-card mb-3">
            <h2 class="fs-5 fw-bold mb-1"><?php echo $k['step3']; ?></h2>
            <p class="text-muted small mb-3"><?php echo $k['step3_d']; ?></p>
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="pp-method">
                        <h3><span aria-hidden="true">🏪</span> <?php echo $k['konbini']; ?></h3>
                        <ol class="pp-list">
                            <?php foreach ($k['konbini_s'] as $i => $s): ?>
                                <li class="pp-step"><span class="pp-num"><?php echo $i + 1; ?></span><span><?php echo $s; ?></span></li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="pp-method">
                        <h3><span aria-hidden="true">🏦</span> <?php echo $k['bank']; ?></h3>
                        <ol class="pp-list">
                            <?php foreach ($k['bank_s'] as $i => $s): ?>
                                <li class="pp-step"><span class="pp-num"><?php echo $i + 1; ?></span><span><?php echo $s; ?></span></li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 4: Pay -->
        <div class="app-card mb-3">
            <h2 class="fs-5 fw-bold mb-1"><?php echo $k['step4']; ?></h2>
            <p class="text-muted small mb-3"><?php echo $k['step4_d']; ?></p>

            <div class="row g-3">
                <?php if ($hasId): ?>
                <!-- Option A: PayPay ID -->
                <div class="col-lg-6">
                    <div class="pp-method">
                        <h3><span aria-hidden="true">💸</span> <?php echo $k['optA']; ?></h3>

                        <div class="pp-id-box">
                            <div>
                                <div class="pp-id-label"><?php echo $k['kid_label']; ?></div>
                                <div class="pp-id-val" id="paypayId"><?php echo htmlspecialchars($paypayId); ?></div>
                            </div>
                            <button type="button" class="pp-copy" id="ppCopyBtn"
                                    data-copy="<?php echo htmlspecialchars($paypayId); ?>"
                                    data-label="<?php echo htmlspecialchars($k['copy']); ?>"
                                    data-done="<?php echo htmlspecialchars($k['copied']); ?>">
                                <?php echo $k['copy']; ?>
                            </button>
                        </div>

                        <ol class="pp-list">
                            <?php foreach ($k['optA_s'] as $i => $s): ?>
                                <li class="pp-step"><span class="pp-num"><?php echo $i + 1; ?></span><span><?php echo sprintf($s, '<b>' . htmlspecialchars($paypayId) . '</b>'); ?></span></li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Option B: request link via WhatsApp -->
                <div class="<?php echo $hasId ? 'col-lg-6' : 'col-12'; ?>">
                    <div class="pp-method">
                        <h3><span aria-hidden="true">🔗</span> <?php echo $k['optB']; ?></h3>
                        <p class="small text-muted mb-3"><?php echo $k['optB_d']; ?></p>
                        <ol class="pp-list mb-4">
                            <?php foreach ($k['optB_s'] as $i => $s): ?>
                                <li class="pp-step"><span class="pp-num"><?php echo $i + 1; ?></span><span><?php echo $s; ?></span></li>
                            <?php endforeach; ?>
                        </ol>
                        <a class="pp-wa" href="<?php echo htmlspecialchars($waUrl); ?>" target="_blank" rel="noopener noreferrer">
                            <i class="fa-brands fa-whatsapp fs-5" aria-hidden="true"></i>
                            <?php echo $k['wa_btn']; ?>
                        </a>
                        <div class="pp-wa-num"><?php echo htmlspecialchars($whatsappDisplay); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Go to Books -->
        <div class="app-card text-center mb-3">
            <div class="fs-2 mb-1" aria-hidden="true">📚</div>
            <h2 class="fs-5 fw-bold mb-1"><?php echo $k['books_title']; ?></h2>
            <p class="text-muted small mb-3"><?php echo $k['books_d']; ?></p>
            <a href="<?php echo htmlspecialchars($booksPageUrl); ?>" class="btn btn-brand w-100">
                <?php echo $k['books_btn']; ?> <i class="fa-solid fa-arrow-right ms-1" aria-hidden="true"></i>
            </a>
        </div>

        <p class="pp-note mb-4"><?php echo $k['note']; ?></p>

<?php if ($hasId): ?>
<script>
(function () {
    var btn = document.getElementById('ppCopyBtn');
    if (!btn) return;
    btn.addEventListener('click', function () {
        var text = btn.getAttribute('data-copy');
        function done() {
            btn.textContent = btn.getAttribute('data-done');
            btn.classList.add('done');
            setTimeout(function () {
                btn.textContent = btn.getAttribute('data-label');
                btn.classList.remove('done');
            }, 1800);
        }
        function fallback() {
            var ta = document.createElement('textarea');
            ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
            document.body.appendChild(ta); ta.select();
            try { document.execCommand('copy'); done(); } catch (e) {}
            document.body.removeChild(ta);
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done).catch(fallback);
        } else { fallback(); }
    });
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
