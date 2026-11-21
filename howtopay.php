<?php
// ---------------------------------------------------------------------
// ගෙවීම් ක්‍රම තාවකාලිකව වසා ඇත්නම් true.
// නැවත විවෘත කරන්න ඕන වුණාම false කරන්න, details / බොත්තම ආයෙත් පෙනෙනවා.
// ---------------------------------------------------------------------
$yuchoClosed  = true;    // Yucho Bank (ゆうちょ銀行)
$paypayClosed = true;    // PayPay

$payWa = 'https://wa.me/c/819050295822';   // WhatsApp catalogue / chat link

$payOpen = ['Sampath Bank'];
if (!$yuchoClosed)  $payOpen[] = 'Yucho Bank';
if (!$paypayClosed) $payOpen[] = 'PayPay';

$pageTitle       = 'ගෙවීම් ක්‍රම | Sampath Bank, Yucho (ゆうちょ) සහ PayPay — learnjapanese.jp';
$pageDescription = 'ලංකාවේ ඉන්නවා නම් Sampath Bank හරහා ගෙවලා 5% discount එකක් ගන්න. දැනට ගෙවිය හැකි ක්‍රම: ' . implode(', ', $payOpen) . '.'
                 . ($yuchoClosed ? ' Yucho Bank (ゆうちょ銀行) තාවකාලිකව වසා ඇත.' : '')
                 . ($paypayClosed ? ' PayPay තාවකාලිකව වසා ඇත.' : '');
$pageKeywords    = 'sampath bank transfer discount, yucho bank sri lanka japan, paypay whatsapp payment, jlpt book payment methods sri lanka japan';

require_once __DIR__ . '/includes/header.php';
?>

<style>
    /* theme variables (same values as index.php / dictionary.php) */
    :root {
        --nm-bg: #e6e9f2; --nm-light: #ffffff; --nm-dark: #c3c9dc;
        --nm-text: #2d3250; --nm-muted: #7a8099;
        --nm-brand: #4f46e5; --nm-brand-2: #7c6cf5;
        --nm-radius: 24px; --nm-radius-sm: 16px;
        --nm-raised: 8px 8px 18px var(--nm-dark), -8px -8px 18px var(--nm-light);
        --nm-raised-sm: 5px 5px 11px var(--nm-dark), -5px -5px 11px var(--nm-light);
        --nm-inset: inset 5px 5px 11px var(--nm-dark), inset -5px -5px 11px var(--nm-light);
        --nm-inset-sm: inset 3px 3px 7px var(--nm-dark), inset -3px -3px 7px var(--nm-light);
    }
    body { background: var(--nm-bg) !important; color: var(--nm-text); -webkit-tap-highlight-color: transparent; }
    @keyframes payUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }

    .pay-hero { background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); color: #fff; border-radius: var(--nm-radius);
                padding: 26px 22px; box-shadow: var(--nm-raised); position: relative; overflow: hidden; text-align: center; animation: payUp .5s ease both; }
    .pay-hero::before { content: ""; position: absolute; width: 140px; height: 140px; border-radius: 50%; background: rgba(255,255,255,.12); top: -50px; right: -30px; }
    .pay-hero::after  { content: ""; position: absolute; width: 90px; height: 90px; border-radius: 50%; background: rgba(255,255,255,.10); bottom: -35px; left: 12%; }
    .pay-hero > * { position: relative; z-index: 1; }
    .pay-hero h1 { font-weight: 800; font-size: 1.55rem; margin: 0 0 6px; }
    .pay-hero p  { margin: 0 auto; max-width: 640px; opacity: .92; font-size: .95rem; }

    /* "available now" strip */
    .pay-now { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: center; margin: 18px 0 4px; font-size: .88rem; color: var(--nm-muted); font-weight: 700; }
    .pay-pill { padding: 7px 16px; border-radius: 999px; font-weight: 800; font-size: .82rem; box-shadow: var(--nm-raised-sm); background: var(--nm-bg); }
    .pay-pill.ok  { color: #059669; }
    .pay-pill.off { color: #94a3b8; box-shadow: var(--nm-inset-sm); text-decoration: line-through; }

    .pay-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; align-items: stretch; margin: 20px 0 26px; }
    @media (max-width: 900px) { .pay-grid { grid-template-columns: 1fr; } }

    .pay-card { background: var(--nm-bg); border-radius: var(--nm-radius); box-shadow: var(--nm-raised); padding: 24px 22px;
                display: flex; flex-direction: column; position: relative; overflow: hidden; animation: payUp .45s ease both; }
    .pay-card:nth-child(2) { animation-delay: .06s; } .pay-card:nth-child(3) { animation-delay: .12s; }
    .pay-card.closed { box-shadow: var(--nm-inset); }

    .pay-ico { width: 54px; height: 54px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
               font-size: 1.35rem; margin-bottom: 14px; background: var(--nm-bg); box-shadow: var(--nm-raised-sm); }
    .pay-card.sampath .pay-ico { color: var(--nm-brand); }
    .pay-card.yucho   .pay-ico { color: #dc2626; }
    .pay-card.paypay  .pay-ico { color: #16a34a; }
    .pay-card.closed  .pay-ico { color: #94a3b8; box-shadow: var(--nm-inset-sm); }

    .pay-title { font-weight: 800; font-size: 1.15rem; margin-bottom: 4px; }
    .pay-sub   { color: var(--nm-muted); font-size: .88rem; margin-bottom: 16px; line-height: 1.55; }
    .pay-card.closed .pay-title, .pay-card.closed .pay-sub { opacity: .6; }

    .pay-flag { position: absolute; top: 0; right: 0; color: #fff; font-weight: 800; font-size: .76rem; padding: 7px 16px 7px 20px; border-bottom-left-radius: 16px; }
    .pay-flag.off  { background: linear-gradient(135deg, #16a34a, #34d399); }
    .pay-flag.shut { background: linear-gradient(135deg, #dc2626, #f97316); }

    .pay-closed { border-radius: var(--nm-radius-sm); box-shadow: var(--nm-inset-sm); padding: 15px; color: #991b1b; font-size: .9rem; line-height: 1.6; }
    .pay-closed strong { display: block; margin-bottom: 4px; font-size: .95rem; }
    .pay-closed a { color: #991b1b; font-weight: 800; }

    .pay-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 11px 0; border-bottom: 1px dashed var(--nm-dark); }
    .pay-row:last-of-type { border-bottom: 0; }
    .pay-lbl { color: var(--nm-muted); font-size: .8rem; font-weight: 700; }
    .pay-val { font-weight: 700; font-size: .95rem; text-align: right; word-break: break-word; }
    .pay-val.mono { font-family: 'Courier New', monospace; letter-spacing: .5px; display: flex; align-items: center; gap: 8px; }

    .pay-copy { border: 0; background: var(--nm-bg); color: var(--nm-muted); width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
                box-shadow: var(--nm-raised-sm); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
    .pay-copy:active { box-shadow: var(--nm-inset-sm); }
    .pay-copy.copied { color: #16a34a; box-shadow: var(--nm-inset-sm); }

    .pay-note { margin-top: auto; padding-top: 16px; font-size: .82rem; color: var(--nm-muted); line-height: 1.6; }

    .pay-wa { display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; margin-top: auto; border: 0;
              background: linear-gradient(135deg, #16a34a, #34d399); color: #fff; font-weight: 800; padding: 14px 20px; border-radius: 999px;
              box-shadow: var(--nm-raised-sm); transition: transform .2s ease, box-shadow .2s ease; }
    .pay-wa:hover { color: #fff; transform: translateY(-2px); }
    .pay-wa:active { box-shadow: var(--nm-inset-sm); transform: scale(.98); }

    .pay-help { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; background: var(--nm-bg); border-radius: var(--nm-radius);
                box-shadow: var(--nm-raised); padding: 20px 22px; margin-bottom: 34px; }
    .pay-help .ic { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
                    font-size: 1.25rem; color: #16a34a; background: var(--nm-bg); box-shadow: var(--nm-raised-sm); }
    .pay-help .tx { flex: 1; min-width: 220px; }
    .pay-help .tx strong { display: block; margin-bottom: 2px; }
    .pay-help .tx span { color: var(--nm-muted); font-size: .88rem; }
    .pay-help .pay-wa { margin-top: 0; padding: 12px 24px; }
</style>

<div class="pay-hero mb-3">
    <h1>💳 ගෙවීම් ක්‍රම</h1>
    <p>ලංකාවේ ඉන්නවා නම් හෝ ජපානයේ ඉන්නවා නම්, ඔබට පහසු ක්‍රමයට ගෙවීම් කරන්න. පහත ක්‍රම වලින් එකක් තෝරගන්න.</p>
</div>

<div class="pay-now">
    දැන් ගෙවිය හැක්කේ:
    <span class="pay-pill ok">✔ Sampath Bank</span>
    <span class="pay-pill <?php echo $yuchoClosed ? 'off' : 'ok'; ?>"><?php echo $yuchoClosed ? '' : '✔ '; ?>Yucho Bank</span>
    <span class="pay-pill <?php echo $paypayClosed ? 'off' : 'ok'; ?>"><?php echo $paypayClosed ? '' : '✔ '; ?>PayPay</span>
</div>

<div class="pay-grid">

    <!-- 1. Sampath Bank -->
    <div class="pay-card sampath">
        <span class="pay-flag off"><i class="fa-solid fa-tag me-1"></i>5% OFF</span>
        <div class="pay-ico"><i class="fa-solid fa-building-columns"></i></div>
        <div class="pay-title">Sampath Bank</div>
        <div class="pay-sub">ලංකාවේ ඉන්නවා නම් Bank Transfer කරලා <strong>5% discount</strong> එකක් ගන්න.</div>

        <div class="pay-row">
            <span class="pay-lbl">ගිණුමේ නම</span>
            <span class="pay-val">P D S S JAYASOORIYA</span>
        </div>
        <div class="pay-row">
            <span class="pay-lbl">ගිණුම් අංකය</span>
            <span class="pay-val mono">
                1043 5235 2946
                <button type="button" class="pay-copy" data-copy="104352352946" aria-label="Copy account number"><i class="fa-regular fa-copy"></i></button>
            </span>
        </div>
        <div class="pay-row">
            <span class="pay-lbl">බැංකුව / ශාඛාව</span>
            <span class="pay-val">Sampath Bank<br>Embilipitiya</span>
        </div>

        <p class="pay-note">
            <i class="fa-solid fa-circle-info me-1"></i>
            Transfer කරන්න කලින් WhatsApp හරහා අපිට කියලා 5% discount එකත් සමඟ ගෙවිය යුතු මුළු මුදල confirm කරගන්න. Transfer කළ පසු slip එකේ screenshot එකක් WhatsApp හරහා එවන්න.
        </p>
    </div>

    <!-- 2. Yucho (Japan Post Bank) -->
    <div class="pay-card yucho<?php echo $yuchoClosed ? ' closed' : ''; ?>">
        <?php if ($yuchoClosed): ?>
            <span class="pay-flag shut"><i class="fa-solid fa-ban me-1"></i>වසා ඇත</span>
        <?php endif; ?>

        <div class="pay-ico"><i class="fa-solid fa-torii-gate"></i></div>
        <div class="pay-title">Yucho Bank <span class="fw-normal fs-6" style="color:var(--nm-muted)">(ゆうちょ銀行)</span></div>
        <div class="pay-sub">ජපානයේ ඉන්නවා නම් Yucho Bank හරහා ගෙවන්න.</div>

        <?php if ($yuchoClosed): ?>

            <div class="pay-closed">
                <strong><i class="fa-solid fa-triangle-exclamation me-1"></i>තාවකාලිකව වසා ඇත</strong>
                Yucho Bank ගෙවීම් දැනට තාවකාලිකව වසා ඇත. නැවත විවෘත කළ විට මෙතන දැනුම් දෙනවා.
                <?php if (!$paypayClosed): ?>
                    ඒ වෙනුවට <strong style="display:inline">PayPay</strong> හරහා ගෙවන්න, නැත්නම්
                <?php else: ?>
                    ජපානයේ ඉන්නවා නම් ගෙවන්නේ කොහොමද කියලා දැනගන්න
                <?php endif; ?>
                <a href="<?php echo htmlspecialchars($payWa, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a> හරහා අපිට අසන්න.
            </div>

        <?php else: ?>

            <div class="pay-row">
                <span class="pay-lbl">ශාඛා සංකේතය (記号)</span>
                <span class="pay-val mono">
                    14430
                    <button type="button" class="pay-copy" data-copy="14430" aria-label="Copy branch code"><i class="fa-regular fa-copy"></i></button>
                </span>
            </div>
            <div class="pay-row">
                <span class="pay-lbl">ගිණුම් අංකය (番号)</span>
                <span class="pay-val mono">
                    52330101
                    <button type="button" class="pay-copy" data-copy="52330101" aria-label="Copy account number"><i class="fa-regular fa-copy"></i></button>
                </span>
            </div>
            <div class="pay-row">
                <span class="pay-lbl">ගිණුමේ නම</span>
                <span class="pay-val">ペンバラ ドゥラゲ ヒル ジャヤス</span>
            </div>

            <p class="pay-note">
                <i class="fa-solid fa-circle-info me-1"></i>
                ATM එකෙන් හෝ Yucho app එකෙන් transfer කළ පසු, receipt එකේ screenshot එකක් WhatsApp හරහා එවන්න.
            </p>

        <?php endif; ?>
    </div>

    <!-- 3. PayPay -->
    <div class="pay-card paypay<?php echo $paypayClosed ? ' closed' : ''; ?>">
        <?php if ($paypayClosed): ?>
            <span class="pay-flag shut"><i class="fa-solid fa-ban me-1"></i>වසා ඇත</span>
        <?php endif; ?>

        <div class="pay-ico"><i class="fa-solid fa-mobile-screen-button"></i></div>
        <div class="pay-title">PayPay</div>
        <div class="pay-sub">PayPay එකෙන් ගෙවන්න ඕන නම්, WhatsApp හරහා අපිට කියන්න, ඔබට payment link එකක් එවනවා.</div>

        <?php if ($paypayClosed): ?>

            <div class="pay-closed">
                <strong><i class="fa-solid fa-triangle-exclamation me-1"></i>තාවකාලිකව වසා ඇත</strong>
                PayPay ගෙවීම් දැනට තාවකාලිකව වසා ඇත. නැවත විවෘත කළ විට මෙතන දැනුම් දෙනවා.
                <?php if (!$yuchoClosed): ?>
                    ඒ වෙනුවට <strong style="display:inline">Yucho Bank</strong> හරහා ගෙවන්න, නැත්නම්
                <?php else: ?>
                    ඒ වෙනුවට <strong style="display:inline">Sampath Bank</strong> transfer එකෙන් ගෙවන්න, නැත්නම් ජපානයේ ඉන්නවා නම්
                <?php endif; ?>
                <a href="<?php echo htmlspecialchars($payWa, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a> හරහා අපිට අසන්න.
            </div>

        <?php else: ?>

            <p class="pay-note" style="margin-top:0;padding-top:0;margin-bottom:16px">
                <i class="fa-solid fa-circle-info me-1"></i>
                "PayPay" කියලා message එකක් එවන්න, අපි ඔබට payment link එක WhatsApp හරහාම එවනවා.
            </p>

            <a href="<?php echo htmlspecialchars($payWa, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="pay-wa">
                <i class="fa-brands fa-whatsapp fs-5"></i> WhatsApp හරහා අසන්න
            </a>

        <?php endif; ?>
    </div>

</div>

<div class="pay-help">
    <div class="ic"><i class="fa-brands fa-whatsapp"></i></div>
    <div class="tx">
        <strong>ගෙවීමට උදව් ඕනද?</strong>
        <span>ඕනම ප්‍රශ්නයක් තියෙනවා නම්, WhatsApp හරහා අපිට msg කරන්න, කැමති ක්‍රමයට order එක process කරලා දෙන්නම්.</span>
    </div>
    <a href="<?php echo htmlspecialchars($payWa, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="pay-wa">
        <i class="fa-brands fa-whatsapp"></i> WhatsApp
    </a>
</div>

<script>
(function () {
    'use strict';
    function fallbackCopy(text) {
        var t = document.createElement('textarea');
        t.value = text; t.setAttribute('readonly', ''); t.style.position = 'fixed'; t.style.opacity = '0';
        document.body.appendChild(t); t.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(t);
    }
    document.querySelectorAll('.pay-copy').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var value = btn.getAttribute('data-copy'), icon = btn.querySelector('i');
            function done() {
                btn.classList.add('copied'); icon.className = 'fa-solid fa-check';
                setTimeout(function () { btn.classList.remove('copied'); icon.className = 'fa-regular fa-copy'; }, 1500);
            }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(value).then(done, function () { fallbackCopy(value); done(); });
            } else { fallbackCopy(value); done(); }
        });
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>