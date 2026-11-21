<?php
/**
 * includes/activity.php - user activity log helpers
 * Usage anywhere:  la_log_activity($uid, 'book', 'N2 Grammar Book', 'ලබා ගත්තා', 'N2', 12);
 */
require_once __DIR__ . '/progress.php';   // gives us the DB helper + session

/** PDO/mysqli: change this ONE function to match how your project gets the DB connection */
function la_db_conn() {
    if (function_exists('la_db')) return la_db();        // <- if you already have la_db() PDO
    global $pdo;                                          // <- fallback: global $pdo
    return $pdo;
}

/** Insert one activity row (never throws - logging must not break the page) */
function la_log_activity(int $uid, string $type, string $title, ?string $detail = null, ?string $level = null, ?int $refId = null): void {
    try {
        $st = la_db_conn()->prepare(
            'INSERT INTO user_activity (user_id, type, title, detail, level, ref_id) VALUES (?,?,?,?,?,?)'
        );
        $st->execute([$uid, $type, mb_substr($title, 0, 255), $detail !== null ? mb_substr($detail, 0, 500) : null, $level, $refId]);
    } catch (Throwable $e) { /* ignore */ }
}

/** Fetch history. $type = '' for all. */
function la_user_activity(int $uid, string $type = '', int $limit = 20, int $offset = 0): array {
    $allowed = ['whatsapp','book','lesson','quiz','grammar','login','plan','other'];
    $sql  = 'SELECT type, title, detail, level, created_at FROM user_activity WHERE user_id = :u';
    if ($type !== '' && in_array($type, $allowed, true)) $sql .= ' AND type = :t';
    $sql .= ' ORDER BY created_at DESC, id DESC LIMIT :l OFFSET :o';
    $st = la_db_conn()->prepare($sql);
    $st->bindValue(':u', $uid, PDO::PARAM_INT);
    if ($type !== '' && in_array($type, $allowed, true)) $st->bindValue(':t', $type);
    $st->bindValue(':l', $limit, PDO::PARAM_INT);
    $st->bindValue(':o', $offset, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

/** Count per type (for the filter chips) */
function la_user_activity_counts(int $uid): array {
    $st = la_db_conn()->prepare('SELECT type, COUNT(*) c FROM user_activity WHERE user_id = ? GROUP BY type');
    $st->execute([$uid]);
    return $st->fetchAll(PDO::FETCH_KEY_PAIR);
}

/** Time ago in Sinhala */
function la_time_ago(string $dt): string {
    $diff = time() - strtotime($dt);
    if ($diff < 60)     return 'දැන්';
    if ($diff < 3600)   return floor($diff / 60) . ' මිනිත්තු කට පෙර';
    if ($diff < 86400)  return floor($diff / 3600) . ' පැය කට පෙර';
    if ($diff < 604800) return floor($diff / 86400) . ' දින කට පෙර';
    return substr($dt, 0, 10);
}