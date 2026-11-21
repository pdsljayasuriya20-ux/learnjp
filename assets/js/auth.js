/* =====================================================
   assets/js/auth.js  -  login / register / dashboard behaviour
   Needs window.LA = { csrf, api, google, next, tab, t:{...texts} }  (set by the PHP page)
   ===================================================== */
(function () {
    'use strict';

    var C = window.LA || {};
    var T = C.t || {};
    function $(s, r) { return (r || document).querySelector(s); }
    function $$(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }

    var msgBox = $('#laMsg');
    function say(text, type) {
        if (!msgBox) return;
        msgBox.textContent = text || '';
        msgBox.className = 'la-msg' + (text ? ' show ' + (type || 'err') : '');
        if (text && msgBox.scrollIntoView) msgBox.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
    function busy(btn, on) {
        if (!btn) return;
        btn.disabled = !!on;
        btn.classList.toggle('loading', !!on);
    }
    function go(url) { window.location.href = url || C.next || 'dashboard.php'; }

    /** POST JSON to auth-api.php */
    function api(action, data) {
        data = data || {};
        data.action = action;
        data.csrf = C.csrf;
        return fetch(C.api, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(data)
        }).then(function (r) {
            return r.json().catch(function () { return { ok: false, error: T.network }; });
        }).catch(function () { return { ok: false, error: T.network }; });
    }

    /* ---------- show the country-code box only when a phone number is typed ---------- */
    $$('[data-ident]').forEach(function (inp) {
        var wrap = inp.closest('.la-field');
        var sel  = wrap && $('[data-cc]', wrap);
        var hint = wrap && $('[data-ident-hint]', wrap);
        function upd() {
            var v = inp.value.trim();
            var phone = v !== '' && v.indexOf('@') === -1 && /[0-9+]/.test(v);
            if (sel) sel.hidden = !phone;
            if (hint) hint.textContent = v === '' ? T.hintBoth : (phone ? T.hintPhone : T.hintEmail);
        }
        inp.addEventListener('input', upd);
        upd();
    });

    /* ---------- show / hide password ---------- */
    $$('[data-eye]').forEach(function (b) {
        b.addEventListener('click', function () {
            var i = b.parentNode.querySelector('input');
            if (!i) return;
            var show = i.type === 'password';
            i.type = show ? 'text' : 'password';
            b.querySelector('i').className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        });
    });

    /* ---------- login page: Password / Code tabs ---------- */
    var tabs = $$('[data-tab]');
    function showTab(name) {
        tabs.forEach(function (t) { t.classList.toggle('active', t.getAttribute('data-tab') === name); });
        $$('[data-panel]').forEach(function (p) { p.hidden = p.getAttribute('data-panel') !== name; });
        say('');
    }
    tabs.forEach(function (t) { t.addEventListener('click', function () { showTab(t.getAttribute('data-tab')); }); });
    if (tabs.length) showTab(C.tab === 'code' ? 'code' : 'password');

    /* ---------- password login ---------- */
    var formPw = $('#formPw');
    if (formPw) {
        formPw.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = $('button[type=submit]', formPw);
            var ccSel = $('[data-cc]', formPw);
            say('');
            busy(btn, true);
            api('password_login', {
                identifier: $('[data-ident]', formPw).value.trim(),
                cc: ccSel ? ccSel.value : '',
                password: $('#pwPass').value,
                next: C.next
            }).then(function (r) {
                if (r.ok) { say(T.welcome, 'ok'); go(r.redirect); return; }
                busy(btn, false);
                say(r.error);
                // account has no password -> jump straight to the guided code flow
                if (r.code === 'no_password') $('#forgotBtn').click();
            });
        });
    }

    /* "Forgot password?" -> same contact, switch to the guided code tab */
    var forgot = $('#forgotBtn');
    if (forgot) {
        forgot.addEventListener('click', function () {
            var from = $('#formPw [data-ident]'), flow = $('[data-flow][data-purpose="login"]');
            if (!flow) return;
            var to = $('[data-ident]', flow);
            if (from && to && from.value) {
                to.value = from.value;
                to.dispatchEvent(new Event('input'));
                var fc = $('#formPw [data-cc]'), tc = $('[data-cc]', flow);
                if (fc && tc) tc.value = fc.value;
            }
            showTab('code');
            say(T.forgotInfo, 'info');
            to.focus();
        });
    }

    /* ---------- one-time code flow (used by BOTH login and register) ---------- */
    $$('[data-flow]').forEach(function (flow) {
        var purpose = flow.getAttribute('data-purpose');
        var f1 = $('form[data-stage="1"]', flow), f2 = $('form[data-stage="2"]', flow);
        var steps = $$('[data-steps] li', flow);
        var ident = $('[data-ident]', flow), cc = $('[data-cc]', flow), code = $('[data-code]', flow);
        var sentTo = $('[data-sent-to]', flow), resend = $('[data-resend]', flow), change = $('[data-change]', flow);
        var used = null, timer = null;

        function stage(n, finished) {
            f1.hidden = n !== 1;
            f2.hidden = n !== 2;
            steps.forEach(function (li, i) {
                li.classList.toggle('done', finished || i < n - 1);
                li.classList.toggle('active', !finished && i === n - 1);
            });
        }

        function countdown(sec) {
            clearTimeout(timer);
            resend.disabled = true;
            (function tick() {
                if (sec <= 0) { resend.disabled = false; resend.textContent = T.resend; return; }
                resend.textContent = T.resendIn.replace('{s}', sec--);
                timer = setTimeout(tick, 1000);
            })();
        }

        function send(btn) {
            var data = {
                purpose: purpose,
                identifier: ident.value.trim(),
                cc: cc ? cc.value : '',
                next: C.next
            };
            var nm = $('[data-name]', flow), pw = $('[data-pass]', flow);
            if (nm) data.name = nm.value.trim();
            if (pw) data.password = pw.value;

            say('');
            busy(btn, true);
            return api('send_otp', data).then(function (r) {
                busy(btn, false);
                if (!r.ok) { say(r.error); return; }
                used = { identifier: data.identifier, cc: data.cc };
                sentTo.textContent = (r.channel === 'phone' ? T.viaWa : T.viaMail) + ' ' + r.masked;
                stage(2);
                code.value = '';
                code.focus();
                countdown(r.cooldown || 30);
                say(r.dev_code ? ('DEV MODE – code: ' + r.dev_code) : '', 'info');
            });
        }

        f1.addEventListener('submit', function (e) { e.preventDefault(); send($('button[type=submit]', f1)); });
        resend.addEventListener('click', function () { send(resend); });
        change.addEventListener('click', function () { clearTimeout(timer); stage(1); say(''); ident.focus(); });

        f2.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!used) { stage(1); return; }
            var btn = $('button[type=submit]', f2);
            say('');
            busy(btn, true);
            api('verify_otp', { purpose: purpose, identifier: used.identifier, cc: used.cc, code: code.value, next: C.next })
                .then(function (r) {
                    if (r.ok) { stage(2, true); say(T.welcome, 'ok'); go(r.redirect); return; }
                    busy(btn, false);
                    say(r.error);
                    code.value = '';
                    code.focus();
                });
        });

        // auto-submit as soon as 6 digits are typed / pasted
        code.addEventListener('input', function () {
            code.value = code.value.replace(/\D/g, '').slice(0, 6);
            if (code.value.length === 6) $('button[type=submit]', f2).click();
        });

        stage(1);
    });

    /* ---------- Google Sign-In ---------- */
    window.laGoogleCb = function (resp) {
        say('');
        api('google', { credential: resp.credential, next: C.next }).then(function (r) {
            if (r.ok) { say(T.welcome, 'ok'); go(r.redirect); } else { say(r.error); }
        });
    };
    function initGoogle() {
        var el = $('#gbtn');
        if (!C.google || !el || !window.google || !google.accounts) return false;
        google.accounts.id.initialize({ client_id: C.google, callback: window.laGoogleCb, auto_select: false });
        google.accounts.id.renderButton(el, {
            theme: 'outline', size: 'large', shape: 'pill',
            text: C.gText || 'continue_with',
            width: Math.max(200, Math.min(360, el.offsetWidth || 300))
        });
        return true;
    }
    var gTries = 0;
    (function waitGoogle() { if (!initGoogle() && gTries++ < 40) setTimeout(waitGoogle, 250); })();

    /* ---------- dashboard: set / change password ---------- */
    var formSet = $('#formSetPw');
    if (formSet) {
        formSet.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = $('button[type=submit]', formSet), cur = $('[name=current]', formSet);
            say('');
            busy(btn, true);
            api('set_password', { current_password: cur ? cur.value : '', new_password: $('[name=new]', formSet).value })
                .then(function (r) {
                    busy(btn, false);
                    if (r.ok) { say(T.pwSaved, 'ok'); formSet.reset(); } else { say(r.error); }
                });
        });
    }
})();
