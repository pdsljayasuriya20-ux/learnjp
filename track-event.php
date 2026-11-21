<?php
/**
 * track-event.php - logs one user event (WhatsApp click, book click, app install click).
 * POST: csrf, type (wa|book|install), title, detail, ref.   Answers 204.  Logged-in users only.
 */
require_once __DIR__ . '/includes/progress.php';
require_once __DIR__ . '/includes/user-activity.php';

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit; }
if (empty($_SESSION['user_id']))                  { http_response_code(204); exit; }          // guests are simply not logged
if (!la_csrf_ok($_POST['csrf'] ?? null))          { http_response_code(403); exit; }

$type = (string) ($_POST['type'] ?? '');
if (!in_array($type, ['wa', 'book', 'install'], true)) { http_response_code(400); exit; }

// max 30 events / 10 minutes per session (keeps the table clean)
$now = time();
$win = array_values(array_filter((array) ($_SESSION['ua_rate'] ?? []), function ($t) use ($now) { return $t > $now - 600; }));
if (count($win) >= 30) { http_response_code(429); exit; }
$win[] = $now;
$_SESSION['ua_rate'] = $win;

$title  = trim((string) ($_POST['title'] ?? ''));
$detail = trim((string) ($_POST['detail'] ?? ''));
$ref    = trim((string) ($_POST['ref'] ?? ''));
if ($title === '') { http_response_code(400); exit; }

// the same click twice within 20 seconds = one event
$sig = md5($type . '|' . $title . '|' . $detail);
if (($_SESSION['ua_last'][0] ?? '') === $sig && ($_SESSION['ua_last'][1] ?? 0) > $now - 20) { http_response_code(204); exit; }
$_SESSION['ua_last'] = [$sig, $now];

ua_log((int) $_SESSION['user_id'], $type, $title, $detail, $ref);
http_response_code(204);
