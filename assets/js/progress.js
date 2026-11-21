/* =====================================================
   assets/js/progress.js  -  report learning progress from any page
   Loaded by la_progress_boot() (footer.php) only for logged-in users.

   LAProgress.grammar('n5-wa-ha', 'N5');          // learned
   LAProgress.grammar('n5-wa-ha', 'N5', true);    // mastered
   LAProgress.lesson('n5-lesson-3', 'N5');        // lesson finished
   LAProgress.quiz('n5-quiz-1', 'N5', 8, 10);     // score 8 out of 10

   Each returns a Promise -> { ok: true } or { ok: false, code: '...' }.
   Logged-out visitors: nothing is sent, you get { ok: false, code: 'login' }.
   ===================================================== */
(function () {
    'use strict';

    var C = window.LA_PROGRESS || null;

    function post(action, data) {
        if (!C) return Promise.resolve({ ok: false, code: 'login' });
        data.action = action;
        data.csrf = C.csrf;
        return fetch(C.api, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(data)
        }).then(function (r) {
            return r.json().catch(function () { return { ok: false, code: 'server' }; });
        }).catch(function () { return { ok: false, code: 'network' }; });
    }

    window.LAProgress = {
        loggedIn: !!C,
        grammar: function (key, level, mastered) {
            return post('mark', { type: 'grammar', key: key, level: level, status: mastered ? 'mastered' : 'learned' });
        },
        lesson: function (key, level) {
            return post('mark', { type: 'lesson', key: key, level: level, status: 'done' });
        },
        quiz: function (key, level, score, total) {
            return post('quiz', { key: key, level: level, score: score, total: total });
        }
    };
})();
