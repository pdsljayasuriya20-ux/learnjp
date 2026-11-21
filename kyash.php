<?php require_once __DIR__ . '/includes/header.php'; ?>
<?php
// ============================================================
//  SETTINGS - methana wenas karanna
// ============================================================
$whatsappNumber = '819050295822';   // WhatsApp number (+ nathuwa, country code ekka, 0 nathuwa)
$whatsappDisplay = '+81 90-5029-5822';
$kyashId        = 'miraijp';        // Oyage Kyash ID eka
$booksPageUrl   = 'books.php';

$appStoreUrl    = 'https://apps.apple.com/jp/app/id1084264883';
$playStoreUrl   = 'https://play.google.com/store/apps/details?id=co.kyash';

// ---------- Texts (en / si). Aniwa languages walata 'en' fallback wenawa ----------
$K = [
 'en' => [
  'title'      => 'How to pay with Kyash',
  'subtitle'   => 'Install the Kyash app, top up your balance, then pay us by Kyash ID or with a Kyash request link.',
  'step1'      => 'Step 1 · Download the Kyash app',
  'step1_d'    => 'Kyash is free. Download it from your phone\'s store.',
  'ios'        => 'iPhone (App Store)',
  'android'    => 'Android (Google Play)',
  'step2'      => 'Step 2 · Install & set up',
  'inst'       => [
     'Open the app and tap <b>Sign up</b>. Enter your mobile number and verify the SMS code.',
     'Complete identity verification (eKYC) with your Residence Card / ID, as the app asks.',
     'Your virtual Visa card is created automatically. Your wallet is now ready.',
  ],
  'step3'      => 'Step 3 · Top up your balance',
  'step3_d'    => 'Choose the method that is easiest for you. Menu names may change slightly with app updates.',
  'konbini'    => 'Convenience store (cash)',
  'konbini_s'  => [
     'In Kyash, tap <b>Top up</b> (残高に入金) and choose the cash / ATM option.',
     'Go to a <b>Seven-Eleven</b> and use the <b>Seven Bank ATM</b>.',
     'Follow the ATM screen (scan the QR code shown in the app), insert cash and confirm.',
     'The balance appears in your Kyash wallet right away.',
  ],
  'bank'       => 'Bank account / Bank card',
  'bank_s'     => [
     'In Kyash, tap <b>Top up</b> (残高に入金) → <b>Top up from your bank account</b> (お持ちの銀行口座から入金).',
     'Select your bank and enter your account details, then approve it on the bank\'s page.',
     'Choose the amount and tap <b>Top up</b>. Your balance updates in the app.',
     'You can also add a credit / debit card in the app if your account supports it.',
  ],
  'step4'      => 'Step 4 · Pay for your book',
  'step4_d'    => 'Pick one of the two ways below.',
  'optA'       => 'Option A · Send money to our Kyash ID',
  'kid_label'  => 'Our Kyash ID',
  'copy'       => 'Copy',
  'copied'     => 'Copied!',
  'optA_s'     => [
     'In Kyash, tap <b>Send</b> (送る).',
     'Search for our Kyash ID <b>%s</b> and select it.',
     'Enter the total amount of your book order and send.',
     'Send us a WhatsApp message with your order and a screenshot of the payment so we can confirm it.',
  ],
  'optB'       => 'Option B · Can\'t send directly? Ask for a request link',
  'optB_d'     => 'Message us on WhatsApp with your book name and quantity. We will create a Kyash request link for the exact amount of your book order and send it to you. Open the link and pay.',
  'optB_s'     => [
     'Tap the WhatsApp button below and send us your order.',
     'We reply with a <b>Kyash request link</b> (請求リンク) for your book amount.',
     'Open the link in the Kyash app and confirm the payment.',
  ],
  'wa_btn'     => 'Message us on WhatsApp',
  'wa_msg'     => 'Hello! I want to pay with Kyash. Please send me a Kyash request link for my book order amount. Book name and quantity: ',
  'books_title'=> 'Ready to order?',
  'books_d'    => 'Pick your book and come back here when you are ready to pay.',
  'books_btn'  => 'Go to Books',
  'note'       => 'Kyash is a service of Kyash Inc. Fees and limits are set by Kyash.',
 ],
 'si' => [
  'title'      => 'Kyash මගින් ගෙවන ආකාරය',
  'subtitle'   => 'Kyash app එක install කර, balance එක top up කර, අපගේ Kyash ID එකට මුදල් යවන්න, නැත්නම් Kyash request link එකකින් ගෙවන්න.',
  'step1'      => 'පියවර 1 · Kyash app එක download කරන්න',
  'step1_d'    => 'Kyash නොමිලේ ලැබේ. ඔබේ දුරකථනයේ store එකෙන් download කරන්න.',
  'ios'        => 'iPhone (App Store)',
  'android'    => 'Android (Google Play)',
  'step2'      => 'පියවර 2 · Install සහ setup කරන්න',
  'inst'       => [
     'App එක open කර <b>Sign up</b> ඔබා ඔබේ mobile number එක ඇතුළත් කර SMS code එක තහවුරු කරන්න.',
     'App එක ඉල්ලන පරිදි Residence Card / ID එකෙන් අනන්‍යතාව තහවුරු කරන්න (eKYC).',
     'ඔබේ virtual Visa card එක ස්වයංක්‍රීයව සෑදේ. Wallet එක දැන් සූදානම්.',
  ],
  'step3'      => 'පියවර 3 · Balance එක top up කරන්න',
  'step3_d'    => 'ඔබට පහසුම ක්‍රමය තෝරන්න. App update සමඟ menu නම් තරමක් වෙනස් විය හැක.',
  'konbini'    => 'Convenience store (මුදල්)',
  'konbini_s'  => [
     'Kyash හි <b>Top up</b> (残高に入金) ඔබා cash / ATM විකල්පය තෝරන්න.',
     '<b>Seven-Eleven</b> එකකට ගොස් <b>Seven Bank ATM</b> එක භාවිතා කරන්න.',
     'ATM තිරයේ උපදෙස් අනුගමනය කරන්න (app එකේ පෙන්වන QR code එක scan කර), මුදල් දමා තහවුරු කරන්න.',
     'Balance එක වහාම ඔබේ Kyash wallet එකේ පෙන්වයි.',
  ],
  'bank'       => 'Bank account / Bank card',
  'bank_s'     => [
     'Kyash හි <b>Top up</b> (残高に入金) → <b>බැංකු ගිණුමෙන් ඇතුළත් කරන්න</b> (お持ちの銀行口座から入金) තෝරන්න.',
     'ඔබේ බැංකුව තෝරා ගිණුම් විස්තර ඇතුළත් කර, බැංකුවේ පිටුවේදී අනුමත කරන්න.',
     'මුදල තෝරා <b>Top up</b> ඔබන්න. Balance එක app එකේ update වේ.',
     'ඔබේ ගිණුම මඟින් සහාය දක්වන්නේ නම් credit / debit card එකක්ද app එකට add කළ හැක.',
  ],
  'step4'      => 'පියවර 4 · ඔබේ පොතට ගෙවන්න',
  'step4_d'    => 'පහත ක්‍රම දෙකෙන් එකක් තෝරන්න.',
  'optA'       => 'ක්‍රමය A · අපගේ Kyash ID එකට මුදල් යවන්න',
  'kid_label'  => 'අපගේ Kyash ID',
  'copy'       => 'Copy',
  'copied'     => 'Copy විය!',
  'optA_s'     => [
     'Kyash හි <b>Send</b> (送る) ඔබන්න.',
     'අපගේ Kyash ID <b>%s</b> සොයා තෝරන්න.',
     'ඔබේ පොත් ඇණවුමේ මුළු මුදල ඇතුළත් කර යවන්න.',
     'ඇණවුමේ විස්තර සහ ගෙවීමේ screenshot එකක් WhatsApp හරහා එවන්න. එවිට අපට තහවුරු කරගත හැක.',
  ],
  'optB'       => 'ක්‍රමය B · කෙලින්ම යවන්න බැරිද? Request link එකක් ඉල්ලන්න',
  'optB_d'     => 'ඔබේ පොතේ නම සහ ප්‍රමාණය WhatsApp හරහා අපට එවන්න. අපි ඔබේ පොත් ඇණවුමේ නිවැරදි මුදලට Kyash request link එකක් සාදා ඔබට එවන්නෙමු. එම link එක open කර ගෙවන්න.',
  'optB_s'     => [
     'පහත WhatsApp බොත්තම ඔබා ඔබේ ඇණවුම අපට එවන්න.',
     'අපි ඔබේ පොතේ මුදලට <b>Kyash request link</b> (請求リンク) එකක් එවන්නෙමු.',
     'Link එක Kyash app එකේ open කර ගෙවීම තහවුරු කරන්න.',
  ],
  'wa_btn'     => 'WhatsApp හරහා අපට පණිවිඩයක් එවන්න',
  'wa_msg'     => 'Hello! I want to pay with Kyash. Please send me a Kyash request link for my book order amount. Book name and quantity: ',
  'books_title'=> 'Order කිරීමට සූදානම්ද?',
  'books_d'    => 'ඔබේ පොත තෝරන්න. ගෙවීමට සූදානම් වූ පසු නැවත මෙහි එන්න.',
  'books_btn'  => 'Books වෙත යන්න',
  'note'       => 'Kyash යනු Kyash Inc. සේවාවකි. ගාස්තු සහ සීමා Kyash විසින් තීරණය කෙරේ.',
 ],
];
$lang = (isset($currentLang) && isset($K[$currentLang])) ? $currentLang : 'en';
$k = $K[$lang];

$waUrl = 'https://wa.me/' . preg_replace('/\D+/', '', $whatsappNumber)
       . '?text=' . rawurlencode($k['wa_msg']);
?>

<style>
    .ky-hero { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff; border-radius: 20px; padding: 24px 20px; }
    .ky-step { display: flex; gap: 12px; align-items: flex-start; }
    .ky-num {
        flex-shrink: 0; width: 28px; height: 28px; border-radius: 50%;
        background: #eef2ff; color: #4f46e5; font-weight: 700; font-size: .85rem;
        display: flex; align-items: center; justify-content: center; margin-top: 1px;
    }
    .ky-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px; }
    .ky-store {
        display: flex; align-items: center; gap: 12px; padding: 14px 16px;
        border-radius: 14px; border: 1px solid #e0e7ff; background: #fff;
        text-decoration: none; color: inherit; font-weight: 600;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .ky-store:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(79,70,229,.15); color: inherit; }
    .ky-store i { font-size: 1.7rem; color: #4f46e5; }
    .ky-store small { display: block; font-weight: 400; color: #6b7280; font-size: .75rem; }
    .ky-method { background: #f8f9ff; border: 1px solid #e0e7ff; border-radius: 16px; padding: 16px; height: 100%; }
    .ky-method h3 { font-size: 1rem; font-weight: 700; margin-bottom: 12px; }
    .ky-id-box {
        display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;
        background: #fff; border: 2px dashed #a5b4fc; border-radius: 14px; padding: 12px 14px; margin-bottom: 14px;
    }
    .ky-id-label { font-size: .75rem; color: #6b7280; font-weight: 600; }
    .ky-id-val { font-size: 1.35rem; font-weight: 800; color: #4f46e5; letter-spacing: .02em; word-break: break-all; }
    .ky-copy {
        background: #4f46e5; color: #fff; border: 0; border-radius: 10px;
        padding: 8px 16px; font-weight: 700; font-size: .85rem;
    }
    .ky-copy.done { background: #16a34a; }
    .ky-wa {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%;
        background: #25d366; color: #fff; border: 0; border-radius: 12px;
        padding: 13px; font-weight: 700; font-size: 1rem; text-decoration: none;
    }
    .ky-wa:hover { color: #fff; filter: brightness(.95); }
    .ky-wa-num { text-align: center; font-size: .8rem; color: #6b7280; margin-top: 8px; }
    .ky-note { font-size: .78rem; color: #9ca3af; text-align: center; }
</style>

        <!-- Hero -->
        <div class="ky-hero mb-4">
            <div class="fs-1 mb-1" aria-hidden="true">💳</div>
            <h1 class="fw-extrabold fs-3 mb-2"><?php echo $k['title']; ?></h1>
            <p class="mb-0 opacity-90"><?php echo $k['subtitle']; ?></p>
        </div>

        <!-- Step 1: Download -->
        <div class="app-card mb-3">
            <h2 class="fs-5 fw-bold mb-1"><?php echo $k['step1']; ?></h2>
            <p class="text-muted small mb-3"><?php echo $k['step1_d']; ?></p>
            <div class="row g-2">
                <div class="col-12 col-sm-6">
                    <a class="ky-store" href="<?php echo htmlspecialchars($appStoreUrl); ?>" target="_blank" rel="noopener noreferrer">
                        <i class="fa-brands fa-apple" aria-hidden="true"></i>
                        <span><?php echo $k['ios']; ?><small>iOS 16.0+</small></span>
                    </a>
                </div>
                <div class="col-12 col-sm-6">
                    <a class="ky-store" href="<?php echo htmlspecialchars($playStoreUrl); ?>" target="_blank" rel="noopener noreferrer">
                        <i class="fa-brands fa-google-play" aria-hidden="true"></i>
                        <span><?php echo $k['android']; ?><small>Android 7.1+</small></span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Step 2: Install -->
        <div class="app-card mb-3">
            <h2 class="fs-5 fw-bold mb-3"><?php echo $k['step2']; ?></h2>
            <ol class="ky-list">
                <?php foreach ($k['inst'] as $i => $s): ?>
                    <li class="ky-step"><span class="ky-num"><?php echo $i + 1; ?></span><span><?php echo $s; ?></span></li>
                <?php endforeach; ?>
            </ol>
        </div>

        <!-- Step 3: Top up -->
        <div class="app-card mb-3">
            <h2 class="fs-5 fw-bold mb-1"><?php echo $k['step3']; ?></h2>
            <p class="text-muted small mb-3"><?php echo $k['step3_d']; ?></p>
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="ky-method">
                        <h3><span aria-hidden="true">🏪</span> <?php echo $k['konbini']; ?></h3>
                        <ol class="ky-list">
                            <?php foreach ($k['konbini_s'] as $i => $s): ?>
                                <li class="ky-step"><span class="ky-num"><?php echo $i + 1; ?></span><span><?php echo $s; ?></span></li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="ky-method">
                        <h3><span aria-hidden="true">🏦</span> <?php echo $k['bank']; ?></h3>
                        <ol class="ky-list">
                            <?php foreach ($k['bank_s'] as $i => $s): ?>
                                <li class="ky-step"><span class="ky-num"><?php echo $i + 1; ?></span><span><?php echo $s; ?></span></li>
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
                <!-- Option A: Kyash ID -->
                <div class="col-lg-6">
                    <div class="ky-method">
                        <h3><span aria-hidden="true">💸</span> <?php echo $k['optA']; ?></h3>

                        <div class="ky-id-box">
                            <div>
                                <div class="ky-id-label"><?php echo $k['kid_label']; ?></div>
                                <div class="ky-id-val" id="kyashId"><?php echo htmlspecialchars($kyashId); ?></div>
                            </div>
                            <button type="button" class="ky-copy" id="kyCopyBtn"
                                    data-copy="<?php echo htmlspecialchars($kyashId); ?>"
                                    data-label="<?php echo htmlspecialchars($k['copy']); ?>"
                                    data-done="<?php echo htmlspecialchars($k['copied']); ?>">
                                <?php echo $k['copy']; ?>
                            </button>
                        </div>

                        <ol class="ky-list">
                            <?php foreach ($k['optA_s'] as $i => $s): ?>
                                <li class="ky-step"><span class="ky-num"><?php echo $i + 1; ?></span><span><?php echo sprintf($s, '<b>' . htmlspecialchars($kyashId) . '</b>'); ?></span></li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>

                <!-- Option B: request link via WhatsApp -->
                <div class="col-lg-6">
                    <div class="ky-method">
                        <h3><span aria-hidden="true">🔗</span> <?php echo $k['optB']; ?></h3>
                        <p class="small text-muted mb-3"><?php echo $k['optB_d']; ?></p>
                        <ol class="ky-list mb-4">
                            <?php foreach ($k['optB_s'] as $i => $s): ?>
                                <li class="ky-step"><span class="ky-num"><?php echo $i + 1; ?></span><span><?php echo $s; ?></span></li>
                            <?php endforeach; ?>
                        </ol>
                        <a class="ky-wa" href="<?php echo htmlspecialchars($waUrl); ?>" target="_blank" rel="noopener noreferrer">
                            <i class="fa-brands fa-whatsapp fs-5" aria-hidden="true"></i>
                            <?php echo $k['wa_btn']; ?>
                        </a>
                        <div class="ky-wa-num"><?php echo htmlspecialchars($whatsappDisplay); ?></div>
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

        <p class="ky-note mb-4"><?php echo $k['note']; ?></p>

<script>
(function () {
    var btn = document.getElementById('kyCopyBtn');
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
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done).catch(fallback);
        } else { fallback(); }
        function fallback() {
            var ta = document.createElement('textarea');
            ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
            document.body.appendChild(ta); ta.select();
            try { document.execCommand('copy'); done(); } catch (e) {}
            document.body.removeChild(ta);
        }
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>