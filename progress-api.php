<?php
/**
 * progress-api.php  -  saves learning progress (goes in public_html/, next to auth-api.php)
 *
 * Actions:  mark  {type: grammar|lesson, key, level, status}
 *           quiz  {key, level, score, total}
 * POST + JSON, logged-in users only, must carry the CSRF token.
 */
require_once __DIR__ . '/includes/progress.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') la_fail('server', 405);

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) la_fail('server', 400);
if (!la_csrf_ok($in['csrf'] ?? null)) la_fail('csrf', 403);
if (empty($_SESSION['user_id'])) la_fail('login_first', 401);

$uid = (int) $_SESSION['user_id'];

try {
    if (!la_throttle('prog:' . $uid, 300, 600)) la_fail('too_many');

    $key   = (string) ($in['key'] ?? '');
    $level = strtoupper((string) ($in['level'] ?? ''));
    if (!preg_match('/^[A-Za-z0-9_\-\.]{1,100}$/', $key)) la_fail('server', 400);
    if (!in_array($level, LA_LEVELS, true))               la_fail('server', 400);

    switch ($in['action'] ?? '') {

        case 'mark':
            $type   = (string) ($in['type'] ?? '');
            $status = (string) ($in['status'] ?? '');
            $okType = ($type === 'grammar' && in_array($status, ['learned', 'mastered'], true))
                   || ($type === 'lesson'  && $status === 'done');
            if (!$okType) la_fail('server', 400);
            la_progress_mark($uid, $type, $key, $level, $status);
            la_ok();

        case 'quiz':
            $score = (int) ($in['score'] ?? -1);
            $total = (int) ($in['total'] ?? 0);
            if ($total < 1 || $total > 1000 || $score < 0 || $score > $total) la_fail('server', 400);
            la_progress_quiz($uid, $key, $level, $score, $total);
            la_ok();

        default:
            la_fail('server', 400);
    }
} catch (Throwable $e) {
    error_log('[progress-api] ' . $e->getMessage());
    la_fail('server', 500);
}
