<?php
/**
 * includes/dict_extra.php  -  shared helpers for the JLPT dictionary
 *
 *  - search normalisers (moved here from dictionary.php so admin-missed.php can reuse them)
 *  - dict_missed_log()  : remember searches that returned nothing (table dict_missed)
 *  - dict_wotd()        : "word of the day" (table dict_wotd), picked once per day and then fixed
 *  - dict_wotd_text()   : ready-made WhatsApp / Facebook post text
 *  - dict_add_word()    : add a word to dict_words with all the search columns filled
 *
 * Needs: migration 003_dict_features.sql (dict_missed, dict_wotd, dict_sentences, dict_words.pos)
 * Safe to include more than once.
 */

if (defined('DICT_EXTRA_LOADED')) {
    return;
}
define('DICT_EXTRA_LOADED', 1);

// ---- 1. Normalisers (must match the Python used to build dict_words.sql) ----
function dict_to_hira(string $s): string
{
    // katakana -> hiragana (pure PHP, no mbstring needed)
    return preg_replace_callback('/[\x{30A1}-\x{30F6}]/u', function ($m) {
        $b = $m[0];
        $cp = ((ord($b[0]) & 0x0F) << 12) | ((ord($b[1]) & 0x3F) << 6) | (ord($b[2]) & 0x3F);
        return html_entity_decode('&#' . ($cp - 0x60) . ';', ENT_QUOTES, 'UTF-8');
    }, $s);
}

function dict_strip_punct(string $s): string
{
    return preg_replace('/[\s～〜~…\.．・、。，,？?！!\/／（）()［］\[\]「」『』―\-－:：]+/u', '', $s);
}

function dict_ro_skel(string $s): string
{
    $s = strtolower($s);
    $s = strtr($s, [
        'ā' => 'a', 'ī' => 'i', 'ū' => 'u', 'ē' => 'e', 'ō' => 'o',
        'â' => 'a', 'î' => 'i', 'û' => 'u', 'ê' => 'e', 'ô' => 'o',
    ]);
    $s = preg_replace('/[^a-z0-9]/', '', $s);
    foreach ([
        ['cch', 'tt'], ['tch', 'tt'],
        ['sha', 'sya'], ['shu', 'syu'], ['sho', 'syo'], ['she', 'sye'], ['shi', 'si'],
        ['cha', 'tya'], ['chu', 'tyu'], ['cho', 'tyo'], ['che', 'tye'], ['chi', 'ti'],
        ['tsu', 'tu'], ['fu', 'hu'],
        ['ja', 'zya'], ['ju', 'zyu'], ['jo', 'zyo'], ['ji', 'zi'], ['dzu', 'zu'],
    ] as [$a, $b]) {
        $s = str_replace($a, $b, $s);
    }
    $s = preg_replace('/m(?=[bp])/', 'n', $s);
    foreach ([['ou', 'o'], ['oo', 'o'], ['uu', 'u'], ['aa', 'a'], ['ii', 'i'], ['ee', 'e'], ['ei', 'e']] as [$a, $b]) {
        $s = str_replace($a, $b, $s);
    }
    return $s;
}

function dict_si_skel(string $s): string
{
    $s = strtolower($s);
    $s = preg_replace('/[^a-z]/', '', $s);
    foreach ([['sh', 's'], ['ch', 'c'], ['th', 't'], ['dh', 'd'], ['kh', 'k'], ['gh', 'g'], ['bh', 'b'], ['ph', 'p'], ['jh', 'j']] as [$a, $b]) {
        $s = str_replace($a, $b, $s);
    }
    $s = str_replace(['w', 'q', 'z', 'x'], ['v', 'k', 'j', 'ks'], $s);
    foreach ([['aa', 'a'], ['ae', 'a'], ['ii', 'i'], ['ee', 'i'], ['oo', 'o'], ['uu', 'u']] as [$a, $b]) {
        $s = str_replace($a, $b, $s);
    }
    $s = preg_replace('/[nm](?=[gdjb])/', '', $s);
    $s = preg_replace('/(.)\1+/', '$1', $s);
    return $s;
}

function dict_si_cons(string $s): string
{
    return preg_replace('/[aeiou]/', '', dict_si_skel($s));
}

// ---- 2. Missed searches ------------------------------------------------------

/**
 * Remember a search that found nothing. Same search again => hits + 1.
 * Never throws (the dictionary must keep working even if the table is missing).
 */
function dict_missed_log(PDO $pdo, string $q, ?int $level = null): void
{
    try {
        $q = trim(preg_replace('/\s+/u', ' ', $q));
        if ($q === '' || preg_match('~https?:|www\.|[<>\\\\]~i', $q)) {
            return;
        }
        $hasJa = (bool)preg_match('/[\x{3040}-\x{30FF}\x{4E00}-\x{9FFF}]/u', $q);
        $hasSi = (bool)preg_match('/[\x{0D80}-\x{0DFF}]/u', $q);
        $mode  = $hasJa ? 'ja' : ($hasSi ? 'si' : 'lat');
        $len   = (int)preg_match_all('/./us', $q);
        if ($len < ($mode === 'lat' ? 3 : 2)) {
            return;                                   // too short = typing in progress
        }
        $key = strtolower(dict_strip_punct(dict_to_hira($q)));
        $key = preg_replace('/^(.{0,120}).*$/us', '$1', $key);
        if ($key === '') {
            return;
        }
        $lv = ($level !== null && $level >= 1 && $level <= 5) ? $level : null;

        $st = $pdo->prepare(
            'INSERT INTO dict_missed (q_key, q_raw, mode, level_filter) VALUES (:k, :r, :m, :l) '
          . 'ON DUPLICATE KEY UPDATE hits = hits + 1, q_raw = VALUES(q_raw), level_filter = VALUES(level_filter), '
          . "status = IF(status = 'added', 'new', status)"
        );
        $st->execute(['k' => $key, 'r' => $q, 'm' => $mode, 'l' => $lv]);
    } catch (Throwable $e) {
        // table not created yet / DB hiccup -> ignore
    }
}

// ---- 3. Word of the day -------------------------------------------------------

/** today's date in Sri Lanka time (YYYY-MM-DD) */
function dict_today(): string
{
    try {
        return (new DateTime('now', new DateTimeZone('Asia/Colombo')))->format('Y-m-d');
    } catch (Throwable $e) {
        return date('Y-m-d');
    }
}

/**
 * The word of the day (fixed once chosen). $date = 'YYYY-MM-DD' (today if empty, future dates return null).
 * Picks a random N5-N3 word that was not used in the last 365 days, preferring words that have an example sentence.
 * @return array|null  id, level, reading, kanji, romaji, meaning_si, pos, date, sentence{jp,romaji,meaning_si}|null
 */
function dict_wotd(PDO $pdo, ?string $date = null): ?array
{
    $today = dict_today();
    $date  = ($date !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) ? $date : $today;
    if ($date > $today) {
        return null;
    }
    try {
        $sel = $pdo->prepare(
            'SELECT w.id, w.level, w.reading, w.kanji, w.romaji, w.meaning_si '
          . 'FROM dict_wotd d JOIN dict_words w ON w.id = d.word_id WHERE d.wotd_date = :d'
        );
        $sel->execute(['d' => $date]);
        $w = $sel->fetch(PDO::FETCH_ASSOC);

        if (!$w && $date === $today) {
            $pick = null;
            foreach ([true, false] as $needSentence) {
                $sql = 'SELECT w.id, w.reading, w.kanji, w.meaning_si FROM dict_words w '
                     . 'WHERE w.level IN (5,4,3) AND w.meaning_si <> \'\' '
                     . 'AND w.id NOT IN (SELECT word_id FROM dict_wotd WHERE wotd_date >= DATE_SUB(:d, INTERVAL 365 DAY)) '
                     . ($needSentence ? 'AND EXISTS (SELECT 1 FROM dict_sentences s WHERE s.word_id = w.id) ' : '')
                     . 'ORDER BY RAND() LIMIT 60';
                try {
                    $st = $pdo->prepare($sql);
                    $st->execute(['d' => $date]);
                    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $c) {
                        // a single clean word: kana only reading, no phrases / placeholders
                        if (preg_match('/^[\x{3040}-\x{30FF}ー]{2,}$/u', $c['reading'])
                            && !preg_match('/[\/\.…～~、\s]/u', (string)$c['kanji'])
                            && dict_wotd_len($c['meaning_si']) <= 80) {
                            $pick = (int)$c['id'];
                            break;
                        }
                    }
                } catch (Throwable $e) {
                    // dict_sentences missing -> try again without the sentence condition
                }
                if ($pick) {
                    break;
                }
            }
            if ($pick) {
                $pdo->prepare('INSERT IGNORE INTO dict_wotd (wotd_date, word_id) VALUES (:d, :w)')
                    ->execute(['d' => $date, 'w' => $pick]);
                $sel->execute(['d' => $date]);          // re-read (another visitor may have won the race)
                $w = $sel->fetch(PDO::FETCH_ASSOC);
            }
        }
        if (!$w) {
            return null;
        }

        $w['pos'] = null;
        try {
            $ps = $pdo->prepare('SELECT pos FROM dict_words WHERE id = :i');
            $ps->execute(['i' => $w['id']]);
            $w['pos'] = $ps->fetchColumn() ?: null;
        } catch (Throwable $e) { /* pos column not there yet */ }

        $w['date'] = $date;
        $w['sentence'] = null;
        try {
            $ss = $pdo->prepare('SELECT jp, romaji, meaning_si FROM dict_sentences WHERE word_id = :i ORDER BY sort_order, id LIMIT 1');
            $ss->execute(['i' => $w['id']]);
            $row = $ss->fetch(PDO::FETCH_ASSOC);
            $w['sentence'] = $row ?: null;
        } catch (Throwable $e) { /* dict_sentences not there yet */ }
        return $w;
    } catch (Throwable $e) {
        return null;
    }
}

function dict_wotd_len(string $s): int
{
    return (int)preg_match_all('/./us', $s);
}

/** https://host (no trailing slash) */
function dict_site_base(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = preg_replace('/[^A-Za-z0-9\.\-:]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'learnjapanese.jp'));
    return ($https ? 'https://' : 'http://') . $host;
}

/** words for the share text / card (si | en | ja) */
function dict_wotd_labels(string $lang): array
{
    $all = [
        'si' => ['title' => 'අද දවසේ වචනය', 'sent' => 'උදාහරණ වාක්‍යය', 'more' => 'තවත් වචන ඉගෙන ගන්න',
                 'open' => 'කෝෂයේ බලන්න', 'listen' => 'අහන්න', 'wa' => 'WhatsApp', 'fb' => 'Facebook'],
        'en' => ['title' => 'Word of the day', 'sent' => 'Example', 'more' => 'Learn more words',
                 'open' => 'Open in dictionary', 'listen' => 'Listen', 'wa' => 'WhatsApp', 'fb' => 'Facebook'],
        'ja' => ['title' => '今日の単語', 'sent' => '例文', 'more' => 'もっと単語を学ぶ',
                 'open' => '辞典で見る', 'listen' => '聞く', 'wa' => 'WhatsApp', 'fb' => 'Facebook'],
    ];
    return $all[$lang] ?? $all['si'];
}

/** plain text for a WhatsApp / Facebook post */
function dict_wotd_text(array $w, string $lang, string $url): string
{
    $L = dict_wotd_labels($lang);
    $t  = '📖 ' . $L['title'] . ' · N' . $w['level'] . "\n\n";
    $t .= $w['reading'] . ($w['kanji'] !== '' ? '（' . $w['kanji'] . '）' : '') . "\n";
    $t .= $w['romaji'] . "\n";
    $t .= $w['meaning_si'] . "\n";
    if (!empty($w['sentence'])) {
        $t .= "\n💬 " . $w['sentence']['jp'] . "\n";
        if ($w['sentence']['meaning_si'] !== '') {
            $t .= $w['sentence']['meaning_si'] . "\n";
        }
    }
    $t .= "\n👉 " . $L['more'] . ': ' . $url;
    return $t;
}

// ---- 4. Add a word (used by admin-missed.php) -----------------------------------

/**
 * Insert one word into dict_words with all search columns filled.
 * $singlish (optional) = the Sinhala meaning typed in English letters, e.g. "pirimi" - it feeds the Singlish search keys.
 * @return array [bool ok, string message, int id]
 */
function dict_add_word(PDO $pdo, int $level, string $reading, string $kanji, string $meaning, string $romaji, string $singlish = '', string $pos = ''): array
{
    $reading = trim($reading);
    $kanji   = trim($kanji);
    $meaning = trim($meaning);
    $romaji  = trim($romaji);
    $singlish = trim($singlish);

    if ($level < 1 || $level > 5) {
        return [false, 'Level එක 1-5 අතර විය යුතුයි.', 0];
    }
    if ($reading === '' || $meaning === '' || $romaji === '') {
        return [false, 'Reading, සිංහල තේරුම සහ romaji අනිවාර්යයි.', 0];
    }
    if (dict_wotd_len($reading) > 100 || dict_wotd_len($kanji) > 100 || dict_wotd_len($meaning) > 300 || dict_wotd_len($romaji) > 200) {
        return [false, 'අකුරු ගණන වැඩියි.', 0];
    }
    $pos = in_array($pos, ['v1', 'v2', 'v3', 'adj_i', 'adj_na'], true) ? $pos : null;

    $chk = $pdo->prepare('SELECT id FROM dict_words WHERE level = :l AND reading = :r AND kanji = :k LIMIT 1');
    $chk->execute(['l' => $level, 'r' => $reading, 'k' => $kanji]);
    if ($chk->fetchColumn()) {
        return [false, 'මේ වචනය N' . $level . ' එකේ දැනටමත් තියෙනවා.', 0];
    }

    $si = $singlish !== '' ? $singlish : '';
    $row = [
        'level' => $level,
        'reading' => $reading,
        'kanji' => $kanji,
        'meaning_si' => $meaning,
        'reading_norm' => dict_strip_punct(dict_to_hira($reading)),
        'kanji_norm' => dict_strip_punct($kanji),
        'romaji' => $romaji,
        'romaji_key' => dict_ro_skel($romaji),
        'si_key' => dict_si_skel($si),
        'si_cons' => dict_si_cons($si),
        'pos' => $pos,
    ];

    for ($try = 0; $try < 3; $try++) {                 // seq is unique per level -> retry if two admins add at once
        try {
            $sq = $pdo->prepare('SELECT COALESCE(MAX(seq), 0) + 1 FROM dict_words WHERE level = :l');
            $sq->execute(['l' => $level]);
            $row['seq'] = (int)$sq->fetchColumn();

            $cols = array_keys($row);
            $sql = 'INSERT INTO dict_words (`' . implode('`,`', $cols) . '`) VALUES (:' . implode(',:', $cols) . ')';
            $pdo->prepare($sql)->execute($row);
            return [true, '✓ "' . $reading . '" N' . $level . ' ට එකතු කළා (#' . $row['seq'] . ').', (int)$pdo->lastInsertId()];
        } catch (PDOException $e) {
            if ((string)$e->getCode() === '23000') {
                continue;                              // duplicate (level, seq) -> try next seq
            }
            error_log('[dict_add_word] ' . $e->getMessage());
            return [false, 'DB දෝෂයක් (003_dict_features.sql run කළාද?).', 0];
        }
    }
    return [false, 'නැවත උත්සාහ කරන්න.', 0];
}
