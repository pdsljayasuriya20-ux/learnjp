<?php
/**
 * includes/progress.php  -  learning progress + plan helpers
 * All functions start with la_ (same family as includes/auth.php).
 */
require_once __DIR__ . '/auth.php';

if (!defined('LA_LEVELS')) define('LA_LEVELS', ['N5', 'N4', 'N3', 'N2', 'N1']);

/**
 * How many grammar cards / lessons each level has, so the dashboard can draw a progress bar.
 * Leave 0 = unknown (then only the counts are shown, no bar). Fill these in when you know them.
 */
function la_level_totals(): array {
    return [
        'N5' => ['grammar' => 0, 'lesson' => 0],
        'N4' => ['grammar' => 0, 'lesson' => 0],
        'N3' => ['grammar' => 0, 'lesson' => 0],
        'N2' => ['grammar' => 0, 'lesson' => 0],
        'N1' => ['grammar' => 0, 'lesson' => 0],
    ];
}

/**
 * Prints the config + loads assets/js/progress.js. Called from footer.php for logged-in users,
 * so any page can then use LAProgress.grammar(...) / .lesson(...) / .quiz(...).
 */
function la_progress_boot(): void {
    if (empty($_SESSION['user_id'])) return;
    $cfg = ['csrf' => la_csrf(), 'api' => '/progress-api.php'];
    echo '<script>window.LA_PROGRESS = '
       . json_encode($cfg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>' . "\n";
    echo '<script src="/assets/js/progress.js?v=1" defer></script>' . "\n";
}

/* ---------------------------------------------------------------- saving progress */

/** grammar: learned | mastered      lesson: done.   Never downgrades "mastered" back to "learned". */
function la_progress_mark(int $uid, string $type, string $key, string $level, string $status): void {
    la_db()->prepare(
        "INSERT INTO web_progress (user_id, item_type, item_key, level, status) VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE level = VALUES(level),
            status = IF(status = 'mastered' AND VALUES(status) = 'learned', status, VALUES(status))"
    )->execute([$uid, $type, $key, $level, $status]);
}

/** Quiz result. Keeps the BEST score and counts attempts. */
function la_progress_quiz(int $uid, string $key, string $level, int $score, int $total): void {
    $db = la_db();
    $st = $db->prepare("SELECT id, score, total FROM web_progress WHERE user_id = ? AND item_type = 'quiz' AND item_key = ? LIMIT 1");
    $st->execute([$uid, $key]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $db->prepare("INSERT INTO web_progress (user_id, item_type, item_key, level, status, score, total)
                      VALUES (?, 'quiz', ?, ?, 'done', ?, ?)
                      ON DUPLICATE KEY UPDATE attempts = attempts + 1")
           ->execute([$uid, $key, $level, $score, $total]);
        return;
    }

    $old    = ($row['total'] !== null && (int) $row['total'] > 0) ? ((int) $row['score'] / (int) $row['total']) : -1;
    $better = ($score / $total) > $old;
    if ($better) {
        $db->prepare('UPDATE web_progress SET attempts = attempts + 1, level = ?, score = ?, total = ? WHERE id = ?')
           ->execute([$level, $score, $total, $row['id']]);
    } else {
        $db->prepare('UPDATE web_progress SET attempts = attempts + 1 WHERE id = ?')->execute([$row['id']]);
    }
}

/* ---------------------------------------------------------------- reading progress */

/** @return array level => [grammar, mastered, lessons, quizzes, quiz_score, quiz_total] */
function la_progress_summary(int $uid): array {
    $out = [];
    foreach (LA_LEVELS as $lv) {
        $out[$lv] = ['grammar' => 0, 'mastered' => 0, 'lessons' => 0, 'quizzes' => 0, 'quiz_score' => 0, 'quiz_total' => 0];
    }
    $st = la_db()->prepare(
        'SELECT level, item_type, status, COUNT(*) AS c, COALESCE(SUM(score), 0) AS s, COALESCE(SUM(total), 0) AS t
           FROM web_progress WHERE user_id = ? GROUP BY level, item_type, status'
    );
    $st->execute([$uid]);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $lv = $r['level'];
        if (!isset($out[$lv])) continue;
        $c = (int) $r['c'];
        if ($r['item_type'] === 'grammar') {
            $out[$lv]['grammar'] += $c;
            if ($r['status'] === 'mastered') $out[$lv]['mastered'] += $c;
        } elseif ($r['item_type'] === 'lesson') {
            $out[$lv]['lessons'] += $c;
        } elseif ($r['item_type'] === 'quiz') {
            $out[$lv]['quizzes']    += $c;
            $out[$lv]['quiz_score'] += (int) $r['s'];
            $out[$lv]['quiz_total'] += (int) $r['t'];
        }
    }
    return $out;
}

/* ---------------------------------------------------------------- plans */

/** All non-revoked activations of a user, live ones first. 'live' = started and not expired. */
function la_user_plans(int $uid): array {
    $st = la_db()->prepare(
        "SELECT up.id, up.starts_at, up.expires_at, up.note, p.name, p.levels,
                (up.starts_at <= NOW() AND (up.expires_at IS NULL OR up.expires_at > NOW())) AS live
           FROM web_user_plans up
           JOIN web_plans p ON p.id = up.plan_id
          WHERE up.user_id = ? AND up.status = 'active'
          ORDER BY live DESC, up.id DESC"
    );
    $st->execute([$uid]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) { $r['live'] = (bool) (int) $r['live']; }
    unset($r);
    return $rows;
}

/** Does this user have a live plan covering this level ("N5".."N1")?  Use it to lock paid content. */
function la_user_has_access(int $uid, string $level): bool {
    $level = strtoupper(trim($level));
    foreach (la_user_plans($uid) as $p) {
        if (!$p['live']) continue;
        $lv = strtoupper(trim((string) $p['levels']));
        if ($lv === 'ALL') return true;
        if (in_array($level, array_map('trim', explode(',', $lv)), true)) return true;
    }
    return false;
}

/** "N5,N4" -> "N5 · N4"   /   "ALL" -> "N5 – N1" */
function la_levels_label(string $levels): string {
    $l = strtoupper(trim($levels));
    if ($l === 'ALL') return 'N5 – N1';
    return implode(' · ', array_map('trim', explode(',', $l)));
}
