<?php
/**
 * learnjapanese.jp - JLPT Dictionary (N5-N1)  |  v7  (vocabulary-book protection)
 *
 * NEW in v7:
 *   1. Level chips (සියල්ල / N5..N1) and the "browse words by level" block are REMOVED from the page.
 *   2. A level's full list can never be shown:
 *        - dictionary.php?lvl=N : only page 1 and page 2 (2 x 100 words). Page 2 ends with a
 *          "buy the full book / upgrade" box. Page 3+ shows only that box (noindex), never words.
 *        - search results stop after 60 results per search, then the same box is shown.
 *        - word pages have NO previous / next links any more (no way to walk through the whole list).
 *   3. Copy protection (hard-coded): copy / cut / right-click / drag / select / print / save / view-source
 *      shortcuts blocked, text not selectable (search box excepted), Copy button removed, share = link only.
 *      Server side: rate limit per IP on the JSON API and on word pages, offset cap, noarchive.
 *   4. Links: "full book"  -> books page,  "full word list" -> upgrade.php
 *   5. Add-to-Home-Screen popup (includes/a2hs.php) is included just before the footer.
 *
 * IMPORTANT: browser-side blocking can never be 100% (screenshots, dev-tools...). The REAL protection is
 * server side: nobody can ever download more than page 1-2 / 60 search results / the rate limit.
 *
 * Needs: config.php (getDBConnection()), includes/dict_extra.php, migration 003_dict_features.sql,
 *        tables dict_words, dict_sentences, grammar_examples, grammar_cards, kanji, kanji_examples, books.
 *
 * API:
 *   dictionary.php?json=1&q=...&offset=0       (search, max 60 results per query)
 *   dictionary.php?json=2&id=123[&soff=4]      (word extras)
 *   dictionary.php?json=2&level=5&seq=12[&soff=4]
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/dict_extra.php';
$pdo = getDBConnection();

// ---- 0. SETTINGS (edit these lines only) -----------------------------------
$SITE_URL         = 'https://learnjapanese.jp';        // no trailing slash
$SITE_NAME        = 'learnjapanese.jp';
$OG_IMAGE         = '';                                // full URL of a 1200x630 image, '' = none
$PAGE_FILE        = 'dictionary.php';                  // this file's name (used in canonical URLs / links)
$BOOK_SLUG        = 'jlpt_n5_n1_vocabulary_sinhala';   // books.slug of the vocabulary book
$BOOK_URL_TPL     = 'books.php?slug=%s';                // your real book page URL ( %s = slug )
$BOOKS_FALLBACK   = 'books.php';                       // used on the lock box if the book row is missing
$UPGRADE_URL      = 'upgrade.php';                     // package / upgrade page
$GRAMMAR_URL_TPL  = 'w-grammar_card.php?slug=%s';      // link on the grammar tag ( %s = grammar_cards.slug ); '' = no link
$SENT_PAGE        = 4;                                 // sentences per "show more"
$SSR_SENT         = 8;                                 // sentences printed on a word page
$BROWSE_PER       = 100;                               // words per list page (?lvl=)
$FREE_PAGES       = 2;                                 // list pages anyone can see (page 1 and 2 only)
$SEARCH_MAX       = 60;                                // max search results per query (multiple of 30)

/**
 * Does this visitor own the full vocabulary list?  Returns false for everybody for now.
 * When your package system is ready, put the check here (e.g. a DB lookup on $_SESSION['user_id']).
 */
function dict_full_access(): bool
{
    return false;
}
$FULL = dict_full_access();

// ---- 1. Small helpers (the search normalisers live in includes/dict_extra.php) ----
function dict_h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function dict_like(string $s): string
{
    return '%' . addcslashes($s, '\\%_') . '%';
}

/** number of characters (no mbstring needed) */
function dict_len(string $s): int
{
    return (int)preg_match_all('/./us', $s);
}

/** cut to $n characters (no mbstring needed) */
function dict_trunc(string $s, int $n): string
{
    if (dict_len($s) <= $n) {
        return $s;
    }
    preg_match('/^.{0,' . max(1, $n - 1) . '}/us', $s, $m);
    return rtrim($m[0] ?? '') . '…';
}

/** first meaning of "a / b ; c" */
function dict_first_meaning(string $s): string
{
    $p = preg_split('/\s*[\/;；]\s*/u', $s);
    return trim($p[0] ?? $s);
}

/** "食べる (たべる)" or just the reading */
function dict_disp(array $w): string
{
    $k = trim((string)preg_split('/[\/／]/u', (string)$w['kanji'])[0]);
    return $k !== '' && $k !== $w['reading'] ? $k . ' (' . $w['reading'] . ')' : (string)$w['reading'];
}

/** escape a sentence and wrap the first matching needle in <mark> */
function dict_hl_html(string $text, array $needles): string
{
    foreach ($needles as $n) {
        $i = strpos($text, $n);          // UTF-8 is self-synchronising, so a byte search is safe
        if ($i !== false) {
            return dict_h(substr($text, 0, $i)) . '<mark>' . dict_h($n) . '</mark>' . dict_h(substr($text, $i + strlen($n)));
        }
    }
    return dict_h($text);
}

/** real search engine crawlers are never rate limited (the user-agent can be faked, so this is only a convenience) */
function dict_is_crawler(): bool
{
    return (bool)preg_match('/Googlebot|Google-InspectionTool|bingbot/i', (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
}

/** very small per-IP rate limiter (file in the temp folder). true = allowed */
function dict_rate_ok(string $bucket, int $max, int $window): bool
{
    $ip  = (string)($_SERVER['REMOTE_ADDR'] ?? '0');
    $f   = sys_get_temp_dir() . '/dict_rl_' . preg_replace('/\W/', '', $bucket) . '_' . md5($ip) . '.json';
    $now = time();
    $fh  = @fopen($f, 'c+');
    if (!$fh) {
        return true;                                   // cannot write -> do not block real users
    }
    flock($fh, LOCK_EX);
    $a = json_decode((string)stream_get_contents($fh), true);
    if (!is_array($a)) {
        $a = [];
    }
    $a  = array_values(array_filter($a, function ($t) use ($now, $window) { return (int)$t > $now - $window; }));
    $ok = count($a) < $max;
    if ($ok) {
        $a[] = $now;
    }
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($a));
    flock($fh, LOCK_UN);
    fclose($fh);
    return $ok;
}

/** the "buy the book / upgrade" box (server side) */
function dict_lock_box(array $D, string $bookUrl, string $upUrl): string
{
    return '<div class="d-lock" id="dLock"><div class="l-ico" aria-hidden="true">🔒</div>'
         . '<div class="l-t">' . dict_h($D['lock_t']) . '</div>'
         . '<p class="l-s">' . dict_h($D['lock_s']) . '</p>'
         . '<div class="l-act"><a class="l-b" href="' . dict_h($bookUrl) . '">📘 ' . dict_h($D['lock_book']) . '</a>'
         . '<a class="l-u" href="' . dict_h($upUrl) . '">👑 ' . dict_h($D['lock_up']) . '</a></div></div>';
}

// ---- 2. Search ----
function dict_search(PDO $pdo, string $q, int $limit = 30, ?int $level = null, int $offset = 0): array
{
    $q = trim(preg_replace('/\s+/u', ' ', $q));
    if ($q === '') {
        return [];
    }
    $limit  = max(1, min(100, $limit));
    $offset = max(0, min(2000, $offset));

    $hasJa  = (bool)preg_match('/[\x{3040}-\x{30FF}\x{4E00}-\x{9FFF}]/u', $q);
    $hasSi  = (bool)preg_match('/[\x{0D80}-\x{0DFF}]/u', $q);
    $hasLat = (bool)preg_match('/[A-Za-z]/', $q);

    $where = [];
    $score = [];
    $p = [];

    if ($hasJa) {
        $h = dict_strip_punct(dict_to_hira($q));
        $k = dict_strip_punct($q);
        $p += ['h' => $h, 'hp' => addcslashes($h, '\\%_') . '%', 'hl1' => dict_like($h), 'hl2' => dict_like($h),
               'k' => $k, 'kp' => addcslashes($k, '\\%_') . '%', 'kl1' => dict_like($k), 'kl2' => dict_like($k)];
        $where[] = 'reading_norm LIKE :hl1 OR kanji_norm LIKE :kl1';
        $score[] = '(reading_norm = :h) * 100 + (kanji_norm = :k) * 100 + (reading_norm LIKE :hp) * 60'
                 . ' + (kanji_norm LIKE :kp) * 60 + (reading_norm LIKE :hl2) * 20 + (kanji_norm LIKE :kl2) * 20';
    }

    if ($hasSi) {
        $p += ['q_raw' => $q, 'sp' => addcslashes($q, '\\%_') . '%', 'sl1' => dict_like($q), 'sl2' => dict_like($q)];
        $where[] = 'meaning_si LIKE :sl1';
        $score[] = '(meaning_si = :q_raw) * 100 + (meaning_si LIKE :sp) * 50 + (meaning_si LIKE :sl2) * 20';
    }

    if ($hasLat && !$hasJa && !$hasSi && preg_match_all('/./us', $q) >= 2) {
        // Latin text: could be romaji, Singlish or English - try all three.
        $rk = dict_ro_skel($q);
        $sk = dict_si_skel($q);
        $sc = dict_si_cons($q);

        if ($rk !== '') {
            $esc = addcslashes($rk, '\\%_');
            $p += ['rk' => $rk, 'rkp' => $esc . '%', 'rkl1' => '%' . $esc . '%', 'rkl2' => '%' . $esc . '%'];
            $where[] = 'romaji_key LIKE :rkl1';
            $score[] = '(romaji_key = :rk) * 100 + (romaji_key LIKE :rkp) * 60 + (romaji_key LIKE :rkl2) * 30';
        }
        if ($sk !== '') {
            $esc = addcslashes($sk, '\\%_');
            $p += ['sk' => $sk, 'skp' => $esc . '%', 'skl1' => '%' . $esc . '%', 'skl2' => '%' . $esc . '%'];
            $where[] = 'si_key LIKE :skl1';
            $score[] = '(si_key = :sk) * 90 + (si_key LIKE :skp) * 50 + (si_key LIKE :skl2) * 25';
        }
        if (strlen($sc) >= 4) {
            $esc = addcslashes($sc, '\\%_');
            $p += ['scl1' => '%' . $esc . '%', 'scl2' => '%' . $esc . '%'];
            $where[] = 'si_cons LIKE :scl1';
            $score[] = '(si_cons LIKE :scl2) * 10';
        }
        $esc = addcslashes($q, '\\%_');
        $p += ['el1' => '%' . $esc . '%', 'el2' => '%' . $esc . '%'];
        $where[] = 'meaning_si LIKE :el1';
        $score[] = '(meaning_si LIKE :el2) * 40';
    }

    if (!$where) {
        return [];
    }

    // `id` lets the word sheet ask for its extras; `pos` = word type for the conjugation table
    $sql = 'SELECT id, level, seq, reading, kanji, meaning_si, romaji, pos, (' . implode(' + ', $score) . ') AS score '
         . 'FROM dict_words WHERE (' . implode(' OR ', $where) . ')';
    if ($level !== null && $level >= 1 && $level <= 5) {
        $sql .= ' AND level = :lvl';
        $p['lvl'] = $level;
    }
    $sql .= ' ORDER BY score DESC, level DESC, seq ASC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;

    try {
        $st = $pdo->prepare($sql);
        $st->execute($p);
    } catch (PDOException $e) {
        // `pos` column missing (migration 003 not run yet) -> same query without it
        $st = $pdo->prepare(str_replace(', romaji, pos, (', ', romaji, (', $sql));
        $st->execute($p);
    }
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

// ---- 3. Word extras (matched sentences + related words + kanji) ----

/** masu-form -> dictionary form (dictionary forms are returned unchanged) */
function dict_dictform(string $s, ?string $pos): string
{
    if (!preg_match('/ます$/u', $s)) {
        return $s;
    }
    $stem = preg_replace('/ます$/u', '', $s);
    if ($pos === 'v2') {
        return $stem . 'る';
    }
    if ($pos === 'v3') {
        if (preg_match('/し$/u', $stem)) {
            return preg_replace('/し$/u', 'する', $stem);
        }
        if (preg_match('/来$/u', $stem)) {
            return $stem . 'る';
        }
        if (preg_match('/き$/u', $stem)) {
            return preg_replace('/き$/u', 'くる', $stem);
        }
        return $s;
    }
    if ($pos === 'v1') {
        $hon = ['いらっしゃい' => 'いらっしゃる', 'ください' => 'くださる', 'なさい' => 'なさる', 'おっしゃい' => 'おっしゃる', 'ござい' => 'ござる'];
        foreach ($hon as $k => $v) {
            if (preg_match('/' . preg_quote($k, '/') . '$/u', $stem)) {
                return preg_replace('/' . preg_quote($k, '/') . '$/u', $v, $stem);
            }
        }
        $iToU = ['い' => 'う', 'き' => 'く', 'ぎ' => 'ぐ', 'し' => 'す', 'ち' => 'つ', 'に' => 'ぬ', 'び' => 'ぶ', 'み' => 'む', 'り' => 'る'];
        $c = preg_split('//u', $stem, -1, PREG_SPLIT_NO_EMPTY);
        $n = count($c);
        if ($n && isset($iToU[$c[$n - 1]])) {
            $c[$n - 1] = $iToU[$c[$n - 1]];
            return implode('', $c);
        }
    }
    return $s;
}

/**
 * Strings that show the word inside a sentence (stems of its conjugations).
 *  行く -> 行く 行か 行き 行け 行こ 行っ   |   食べる -> 食べ   |   高い -> 高   |   勉強する -> 勉強
 */
function dict_stems(string $d, ?string $pos): array
{
    $c = preg_split('//u', $d, -1, PREG_SPLIT_NO_EMPTY);
    $n = count($c);
    if ($n < 2) {
        return [$d];
    }
    $last = $c[$n - 1];
    $base = implode('', array_slice($c, 0, $n - 1));

    $A = ['う' => 'わ', 'く' => 'か', 'ぐ' => 'が', 'す' => 'さ', 'つ' => 'た', 'ぬ' => 'な', 'ぶ' => 'ば', 'む' => 'ま', 'る' => 'ら'];
    $I = ['う' => 'い', 'く' => 'き', 'ぐ' => 'ぎ', 'す' => 'し', 'つ' => 'ち', 'ぬ' => 'に', 'ぶ' => 'び', 'む' => 'み', 'る' => 'り'];
    $E = ['う' => 'え', 'く' => 'け', 'ぐ' => 'げ', 'す' => 'せ', 'つ' => 'て', 'ぬ' => 'ね', 'ぶ' => 'べ', 'む' => 'め', 'る' => 'れ'];
    $O = ['う' => 'お', 'く' => 'こ', 'ぐ' => 'ご', 'す' => 'そ', 'つ' => 'と', 'ぬ' => 'の', 'ぶ' => 'ぼ', 'む' => 'も', 'る' => 'ろ'];
    $T = ['う' => 'っ', 'つ' => 'っ', 'る' => 'っ', 'く' => 'い', 'ぐ' => 'い', 'ぬ' => 'ん', 'ぶ' => 'ん', 'む' => 'ん', 'す' => 'し'];

    $hasHan = (bool)preg_match('/\p{Han}/u', $d);
    if ($pos === 'v2' && $last === 'る') {
        if (dict_len($base) >= 2) {
            return [$base];
        }
        return $hasHan ? [$d, $base . 'て', $base . 'た', $base . 'ない', $base . 'ま', $base . 'よ', $base . 'れ'] : [];   // 1 kana = too noisy
    }
    if ($pos === 'v1' && isset($A[$last])) {
        $f = [$d, $base . $A[$last], $base . $I[$last], $base . $E[$last], $base . $O[$last], $base . $T[$last]];
        if (preg_match('/(行く|いく)$/u', $d)) {
            $f[5] = $base . 'っ';                // 行って / 行った (the one irregular godan te-form)
        }
        return array_values(array_unique($f));
    }
    if ($pos === 'v3') {
        if (preg_match('/する$/u', $d)) {
            $b = preg_replace('/する$/u', '', $d);
            return $b === '' ? [] : [$b];
        }
        if (preg_match('/来る$/u', $d)) {
            $b = preg_replace('/来る$/u', '', $d);
            return [$b . '来る', $b . '来て', $b . '来た', $b . '来ない', $b . '来ま', $b . '来よ', $b . '来れ'];
        }
        if (preg_match('/くる$/u', $d)) {
            return [];                          // kana くる forms (きない = できない ...) would match far too much
        }
        return [$d];
    }
    if ($pos === 'adj_i' && $last === 'い') {
        if (dict_len($base) >= 2) {
            return [$base];
        }
        return $hasHan ? [$d, $base . 'く', $base . 'か', $base . 'さ', $base . 'け'] : [];
    }
    return [$d];
}

/**
 * Build the strings to look for inside the grammar example sentences.
 *  - kanji form first (precise), then the kana form
 *  - too short needles are skipped (1 kanji / <3 kana = far too many wrong hits)
 */
function dict_needles(string $kanji, string $reading, ?string $pos = null): array
{
    $out = [];
    foreach ([$kanji, $reading] as $src) {
        if ($src === '') {
            continue;
        }
        foreach (preg_split('/[\/／、,・()（）]/u', $src) as $part) {
            $part = dict_strip_punct($part);
            if ($part === '') {
                continue;
            }
            if ($pos !== null) {
                $forms = dict_stems(dict_dictform($part, $pos), $pos);
            } else {
                // no word type known -> old heuristic
                if (preg_match('/^(.{2,})する$/u', $part, $m)) {
                    $part = $m[1];
                } elseif (preg_match('/\p{Han}/u', $part) && dict_len($part) >= 3 && preg_match('/[るうくぐすつぬぶむい]$/u', $part)) {
                    $part = preg_replace('/.$/us', '', $part);
                }
                $forms = [$part];
            }
            foreach ($forms as $f) {
                $min = preg_match('/\p{Han}/u', $f) ? 2 : 3;
                if (dict_len($f) >= $min) {
                    $out[] = $f;
                }
            }
        }
    }
    return array_slice(array_values(array_unique($out)), 0, 8);
}

/** SELECT that works with and without the `pos` column (migration 003 not run yet) */
function dict_fetch_all(PDO $pdo, string $sql, array $p): array
{
    try {
        $st = $pdo->prepare($sql);
        $st->execute($p);
    } catch (Throwable $e) {
        $st = $pdo->prepare(str_replace(', pos FROM', ' FROM', $sql));
        $st->execute($p);
    }
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

/** the full word row */
function dict_word_full(PDO $pdo, ?int $id, ?int $level = null, ?int $seq = null)
{
    try {
        if ($id) {
            $rows = dict_fetch_all($pdo, 'SELECT id, level, seq, reading, kanji, meaning_si, romaji, pos FROM dict_words WHERE id = :id', ['id' => $id]);
        } else {
            $rows = dict_fetch_all($pdo, 'SELECT id, level, seq, reading, kanji, meaning_si, romaji, pos FROM dict_words WHERE level = :l AND seq = :s',
                ['l' => (int)$level, 's' => (int)$seq]);
        }
        $r = $rows[0] ?? false;
        if ($r && !array_key_exists('pos', $r)) {
            $r['pos'] = null;
        }
        return $r;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Example WORDS: other dictionary words with the same kanji (or the same kana stem), easiest level first.
 * Every item has everything the sheet needs to open it directly.
 */
function dict_related(PDO $pdo, array $w, int $max = 8): array
{
    $queries = [];
    $kanji = trim((string)(preg_split('/[\/／]/u', (string)$w['kanji'])[0] ?? ''));
    if ($kanji !== '' && preg_match_all('/\p{Han}/u', $kanji, $mm)) {
        foreach (array_slice(array_values(array_unique($mm[0])), 0, 3) as $ch) {
            $queries[] = [$ch, 'kanji_norm LIKE :t', dict_like($ch)];
        }
    } else {
        $stem = dict_strip_punct(dict_to_hira((string)$w['reading']));
        $c = preg_split('//u', $stem, -1, PREG_SPLIT_NO_EMPTY);
        if (count($c) >= 4) {
            $c = array_slice($c, 0, count($c) - 1);
        }
        $stem = implode('', $c);
        if (count($c) >= 3) {
            $queries[] = [$stem, 'reading_norm LIKE :t', addcslashes($stem, '\\%_') . '%'];
        }
    }
    if (!$queries) {
        return [];
    }

    $per  = (int)ceil($max / count($queries));
    $seen = [(string)$w['reading'] . '|' . (string)$w['kanji'] => true];
    $out  = [];
    foreach ($queries as [$via, $cond, $param]) {
        try {
            $rows = dict_fetch_all($pdo,
                'SELECT id, level, seq, reading, kanji, meaning_si, romaji, pos FROM dict_words WHERE ' . $cond
                . ' AND id <> :id ORDER BY level DESC, seq ASC LIMIT 14',
                ['t' => $param, 'id' => (int)$w['id']]);
        } catch (Throwable $e) {
            continue;
        }
        $taken = 0;
        foreach ($rows as $r) {
            $key = $r['reading'] . '|' . $r['kanji'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $r['via'] = $via;
            $out[] = $r;
            if (++$taken >= $per) {
                break;
            }
        }
    }
    return array_slice($out, 0, $max);
}

function dict_detail(PDO $pdo, ?int $id, ?int $level, ?int $seq, int $soff, int $page, string $grammarTpl): array
{
    $res = ['needles' => [], 'sentences' => [], 'more' => false, 'kanji' => [], 'related' => [], 'pos' => null];
    try {
        $w = dict_word_full($pdo, $id, $level, $seq);
        if (!$w) {
            return $res;
        }
        $res['pos'] = $w['pos'] ?? null;

        // ---- example sentences: dict_sentences (this exact word) first, then grammar examples (shortest first) ----
        $needles = dict_needles((string)$w['kanji'], (string)$w['reading'], $res['pos']);
        $res['needles'] = $needles;
        $rows = null;
        $limitSql = ' LIMIT ' . ($page + 1) . ' OFFSET ' . max(0, $soff);

        $or = [];
        $p = ['wid' => (int)$w['id']];
        foreach ($needles as $i => $n) {
            $or[]        = 'e.jp LIKE :n' . $i;
            $p['n' . $i] = dict_like($n);
        }

        try {
            $sql = 'SELECT jp, romaji, meaning_si, slug, title_jp, level FROM ('
                 . 'SELECT s.jp, s.romaji, s.meaning_si, NULL AS slug, NULL AS title_jp, NULL AS level, 0 AS src, s.sort_order AS ord, s.id AS oid '
                 . 'FROM dict_sentences s WHERE s.word_id = :wid';
            if ($or) {
                $sql .= ' UNION ALL SELECT e.jp, e.romaji, e.meaning_si, c.slug, c.title_jp, c.level, 1 AS src, CHAR_LENGTH(e.jp) AS ord, e.id AS oid '
                      . 'FROM grammar_examples e JOIN grammar_cards c ON c.id = e.card_id '
                      . 'WHERE c.is_active = 1 AND (' . implode(' OR ', $or) . ') '
                      . 'AND e.jp NOT IN (SELECT s2.jp FROM dict_sentences s2 WHERE s2.word_id = :wid2)';
                $p['wid2'] = (int)$w['id'];
            }
            $sql .= ') u ORDER BY src ASC, ord ASC, oid ASC' . $limitSql;
            $st = $pdo->prepare($sql);
            $st->execute($p);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $rows = null;   // dict_sentences missing -> old behaviour below
        }

        if ($rows === null && $or) {
            $p2 = [];
            foreach ($needles as $i => $n) {
                $p2['n' . $i] = dict_like($n);
            }
            $sql = 'SELECT e.jp, e.romaji, e.meaning_si, c.slug, c.title_jp, c.level '
                 . 'FROM grammar_examples e JOIN grammar_cards c ON c.id = e.card_id '
                 . 'WHERE c.is_active = 1 AND (' . implode(' OR ', $or) . ') '
                 . 'ORDER BY CHAR_LENGTH(e.jp) ASC, e.id ASC' . $limitSql;
            $st = $pdo->prepare($sql);
            $st->execute($p2);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        }

        $rows = $rows ?: [];
        $res['more'] = count($rows) > $page;
        foreach (array_slice($rows, 0, $page) as $r) {
            $r['url'] = ($r['slug'] !== null && $grammarTpl !== '') ? sprintf($grammarTpl, rawurlencode($r['slug'])) : '';
            unset($r['slug']);
            $res['sentences'][] = $r;
        }

        if ($soff === 0) {
            // ---- example words: same kanji / same stem in the dictionary itself ----
            $res['related'] = dict_related($pdo, $w, 8);

            // ---- kanji inside the word + other words that use them ----
            if ((string)$w['kanji'] !== '' && preg_match_all('/\p{Han}/u', (string)$w['kanji'], $mm)) {
                $chars = array_slice(array_values(array_unique($mm[0])), 0, 3);
                $stK = $pdo->prepare('SELECT id, reading_on, reading_kun, meaning_si FROM kanji WHERE `character` = :c ORDER BY id LIMIT 1');
                $stE = $pdo->prepare('SELECT word, reading, meaning_si FROM kanji_examples WHERE kanji_id = :k AND word <> :w ORDER BY sort_order, id LIMIT 6');
                foreach ($chars as $ch) {
                    $stK->execute(['c' => $ch]);
                    $k = $stK->fetch(PDO::FETCH_ASSOC);
                    if (!$k) {
                        continue;
                    }
                    $stE->execute(['k' => $k['id'], 'w' => (string)$w['kanji']]);
                    $res['kanji'][] = [
                        'ch'      => $ch,
                        'meaning' => $k['meaning_si'],
                        'on'      => $k['reading_on'],
                        'kun'     => $k['reading_kun'],
                        'words'   => $stE->fetchAll(PDO::FETCH_ASSOC),
                    ];
                }
            }
        }
    } catch (Throwable $e) {
        // never break the dictionary because an extra table is missing
    }
    return $res;
}

// ---- 4. JSON API (answers BEFORE header.php prints any HTML) ----
$q      = (string)($_GET['q'] ?? '');
$q      = preg_replace('/^(.{0,60}).*$/us', '$1', trim($q));
$offset = (int)($_GET['offset'] ?? 0);
$PAGE   = 30;

if (isset($_GET['json'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Robots-Tag: noindex, noarchive');

    // anti-scraping: max 150 API calls / 10 minutes / IP
    if (!dict_is_crawler() && !dict_rate_ok('api', 150, 600)) {
        http_response_code(429);
        header('Retry-After: 120');
        echo json_encode(['results' => [], 'more' => false, 'locked' => false, 'limited' => true]);
        exit;
    }

    if ($_GET['json'] === '2') {
        // word extras: static data -> let the browser cache it for 1 hour
        header('Cache-Control: public, max-age=3600');
        $d = dict_detail(
            $pdo,
            isset($_GET['id']) ? (int)$_GET['id'] : null,
            isset($_GET['level']) ? (int)$_GET['level'] : null,
            isset($_GET['seq']) ? (int)$_GET['seq'] : null,
            max(0, min(200, (int)($_GET['soff'] ?? 0))),
            $SENT_PAGE,
            $GRAMMAR_URL_TPL
        );
        echo json_encode($d, JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Cache-Control: no-store');

    // search is capped: nobody can page through the whole dictionary with one query
    if (!$FULL && $offset >= $SEARCH_MAX) {
        echo json_encode(['results' => [], 'more' => false, 'locked' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $rows = dict_search($pdo, $q, $PAGE + 1, null, $offset);   // +1 => know if there is more
    $hasMore = count($rows) > $PAGE;
    $locked  = !$FULL && $hasMore && ($offset + $PAGE) >= $SEARCH_MAX;

    if ($offset === 0 && !$rows && $q !== '') {
        // half-typed IME input (kana/kanji mixed with latin letters) or a single character is not worth logging
        $partial = preg_match('/[A-Za-z]/', $q) && preg_match('/[\x{3040}-\x{9FFF}]/u', $q);
        if (!$partial && dict_len($q) >= 2) {
            dict_missed_log($pdo, $q, null);                    // remember it for the admin "Missed words" page
        }
    }
    echo json_encode(['results' => array_slice($rows, 0, $PAGE), 'more' => $hasMore && !$locked, 'locked' => $locked], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---- 5. What kind of page is this? (decided before any HTML so the status code / <head> can be right) ----
$mode    = 'home';     // home | search | word | browse | locked | notfound
$word    = null;       // word page
$det     = null;
$bLevel  = 0;          // browse page
$bPage   = 1;
$bRows   = [];
$bPages  = 1;          // pages that are really shown (max $FREE_PAGES)
$bLocked = false;      // are there more pages than the free ones?

if (isset($_GET['w'])) {
    if (!dict_is_crawler() && !dict_rate_ok('page', 120, 600)) {
        http_response_code(429);
        header('Retry-After: 120');
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Too many requests. Please slow down.';
        exit;
    }
    $word = dict_word_full($pdo, (int)$_GET['w']);
    if ($word) {
        $mode = 'word';
        $det  = dict_detail($pdo, (int)$word['id'], null, null, 0, $SSR_SENT, $GRAMMAR_URL_TPL);
    } else {
        $mode = 'notfound';
    }
} elseif (isset($_GET['lvl'])) {
    $bLevel = (int)$_GET['lvl'];
    $bPage  = max(1, (int)($_GET['p'] ?? 1));
    if ($bLevel >= 1 && $bLevel <= 5) {
        $st = $pdo->prepare('SELECT COUNT(*) FROM dict_words WHERE level = :l');
        $st->execute(['l' => $bLevel]);
        $bTotal  = (int)$st->fetchColumn();
        $bReal   = max(1, (int)ceil($bTotal / $BROWSE_PER));
        $bPages  = $FULL ? $bReal : min($bReal, $FREE_PAGES);
        $bLocked = !$FULL && $bReal > $FREE_PAGES;
        if ($bPage > $bReal) {
            $mode = 'notfound';
        } elseif ($bPage > $bPages) {
            $mode = 'locked';                       // page 3+ : NO words are loaded, only the buy / upgrade box
        } else {
            $st = $pdo->query('SELECT id, level, seq, reading, kanji, meaning_si, romaji FROM dict_words WHERE level = ' . $bLevel
                . ' ORDER BY seq ASC, id ASC LIMIT ' . (int)$BROWSE_PER . ' OFFSET ' . (($bPage - 1) * (int)$BROWSE_PER));
            $bRows = $st->fetchAll(PDO::FETCH_ASSOC);
            $mode  = 'browse';
        }
    } else {
        $mode = 'notfound';
    }
} elseif ($q !== '') {
    $mode = 'search';
}
if ($mode === 'notfound') {
    http_response_code(404);
}
header('X-Robots-Tag: noarchive');

// ---- 6. Page (same header / footer as index.php).  header.php is buffered so the SEO tags can be put into its <head> ----
ob_start();
require_once __DIR__ . '/includes/header.php';
$headerHtml = (string)ob_get_clean();

$DICT_L = [
  'si' => [
    'title' => 'JLPT ශබ්ද කෝෂය', 'sub' => 'N5 – N1 · වචන %s ක් · සිංහල තේරුම් සමඟ',
    'ph' => 'せいしゅん · 青春 · seishun · තරුණ', 'tab_search' => 'සොයන්න', 'tab_saved' => 'සුරැකි',
    'hint' => 'ජපන්, romaji, සිංහල හෝ Singlish වලින් සොයන්න', 'ex' => 'උදාහරණ',
    'm_ja' => 'ජපන් අකුරු', 'm_si' => 'සිංහල', 'm_lat' => 'romaji / Singlish / English',
    'searching' => 'සොයමින්...', 'results' => 'ප්‍රතිඵල', 'more' => 'තවත් පෙන්වන්න',
    'none' => 'ප්‍රතිඵල නැහැ. වෙනත් අකුරු වලින් (කතකනා / හිරගනා / romaji / සිංහල) උත්සාහ කරන්න.',
    'err' => 'සෙවීමේදී දෝෂයක්. නැවත උත්සාහ කරන්න.', 'saved_empty' => 'තවම සුරැකි වචන නැහැ. ☆ එබුවම මෙතන සුරැකේ.',
    'saved_n' => 'සුරැකි වචන', 'copied' => 'Link එක Copy කළා', 'saved_t' => 'සුරැකුවා', 'removed_t' => 'ඉවත් කළා',
    'listen' => 'අහන්න', 'save' => 'සුරකින්න', 'close' => 'වසන්න', 'share' => 'Share', 'page' => 'සම්පූර්ණ පිටුව',
    's_sent' => 'උදාහරණ වාක්‍ය', 's_kanji' => 'මේ වචනයේ කංජි', 's_more' => 'තවත් වාක්‍ය', 's_none' => 'මේ වචනයට උදාහරණ වාක්‍ය තවම නැහැ.',
    's_load' => 'පූරණය වෙමින්...', 's_gram' => 'ව්‍යාකරණ', 's_rel' => 'උදාහරණ වචන (සම්බන්ධ වචන)', 'via' => 'කංජි',
    'words' => 'වචන', 'crumb' => 'ශබ්ද කෝෂය', 'prev' => 'කලින්', 'next' => 'ඊළඟ', 'page_n' => 'පිටුව',
    'list_h' => 'JLPT N%d වචන ලැයිස්තුව', 'nf_t' => 'පිටුව හමු වුණේ නැහැ', 'nf_b' => 'ශබ්ද කෝෂයට ආපසු යන්න',
    'buy_t' => 'සම්පූර්ණ වචන පොත', 'buy_s' => 'N5 – N1 වචන · සිංහල තේරුම් සමඟ', 'buy_b' => 'පොත බලන්න',
    'lock_t' => 'නොමිලේ බලන්න පුළුවන් කොටස මෙතැනින් අවසානයි',
    'lock_s' => 'සම්පූර්ණ වචන ලැයිස්තුව ලබාගන්න සම්පූර්ණ වචන පොත මිලදී ගන්න, නැත්නම් ඔබේ Package එක Upgrade කරගන්න.',
    'lock_book' => 'සම්පූර්ණ පොත මිලදී ගන්න', 'lock_up' => 'Upgrade කරන්න',
    'c_title' => 'රූප වෙනස්වීම් (活用)', 'c_dict' => 'මූලික රූපය (辞書形)', 'c_masu' => 'ආචාරශීලී (ます)',
    'c_te' => 'て රූපය', 'c_nai' => 'නැහැ (ない)', 'c_ta' => 'අතීත (た)', 'c_nak' => 'අතීත නැහැ', 'c_adv' => 'ක්‍රියා විශේෂණ (〜く)',
    'c_note' => 'නීති අනුව ස්වයංක්‍රීයව සාදන ලදී. වැරදි තිබුණොත් දන්වන්න. (එබුවම අහන්න පුළුවන්)',
    'seo_home_t' => 'JLPT ජපන් ශබ්ද කෝෂය (N5–N1) – සිංහල තේරුම් සමඟ',
    'seo_home_d' => 'JLPT N5 සිට N1 දක්වා ජපන් වචන %s ක් සිංහල තේරුම්, romaji, උදාහරණ වාක්‍ය සහ කංජි විස්තර සමඟ සොයන්න.',
    'seo_word_t' => '%1$s – %2$s | JLPT N%3$d වචනය',
    'seo_word_d' => '%1$s (%2$s) – JLPT N%3$d වචනය. තේරුම: %4$s. උදාහරණ වාක්‍ය, සම්බන්ධ වචන සහ කංජි විස්තර.',
    'seo_list_t' => 'JLPT N%1$d වචන ලැයිස්තුව – පිටුව %2$d | සිංහල තේරුම් සමඟ',
    'seo_list_d' => 'JLPT N%1$d ජපන් වචන ලැයිස්තුව (පිටුව %2$d / %3$d) – සිංහල තේරුම් සහ romaji සමඟ.',
  ],
  'en' => [
    'title' => 'JLPT Dictionary', 'sub' => 'N5 – N1 · %s words · with Sinhala meanings',
    'ph' => 'せいしゅん · 青春 · seishun · තරුණ', 'tab_search' => 'Search', 'tab_saved' => 'Saved',
    'hint' => 'Search in Japanese, romaji, Sinhala or Singlish', 'ex' => 'Try',
    'm_ja' => 'Japanese', 'm_si' => 'Sinhala', 'm_lat' => 'romaji / Singlish / English',
    'searching' => 'Searching...', 'results' => 'results', 'more' => 'Show more',
    'none' => 'No results. Try kana, kanji, romaji or Sinhala.',
    'err' => 'Search failed. Please try again.', 'saved_empty' => 'No saved words yet. Tap ☆ on a word to save it.',
    'saved_n' => 'Saved words', 'copied' => 'Link copied', 'saved_t' => 'Saved', 'removed_t' => 'Removed',
    'listen' => 'Listen', 'save' => 'Save', 'close' => 'Close', 'share' => 'Share', 'page' => 'Full page',
    's_sent' => 'Example sentences', 's_kanji' => 'Kanji in this word', 's_more' => 'More sentences', 's_none' => 'No example sentences for this word yet.',
    's_load' => 'Loading...', 's_gram' => 'Grammar', 's_rel' => 'Example words (related words)', 'via' => 'Kanji',
    'words' => 'words', 'crumb' => 'Dictionary', 'prev' => 'Previous', 'next' => 'Next', 'page_n' => 'Page',
    'list_h' => 'JLPT N%d word list', 'nf_t' => 'Page not found', 'nf_b' => 'Back to the dictionary',
    'buy_t' => 'Full Vocabulary Book', 'buy_s' => 'N5 – N1 words · with Sinhala meanings', 'buy_b' => 'View book',
    'lock_t' => 'The free preview ends here',
    'lock_s' => 'To get the complete word list, buy the full vocabulary book or upgrade your package.',
    'lock_book' => 'Buy the full book', 'lock_up' => 'Upgrade',
    'c_title' => 'Conjugation (活用)', 'c_dict' => 'Dictionary form (辞書形)', 'c_masu' => 'Polite (ます)',
    'c_te' => 'Te-form', 'c_nai' => 'Negative (ない)', 'c_ta' => 'Past (た)', 'c_nak' => 'Past negative', 'c_adv' => 'Adverb (〜く)',
    'c_note' => 'Generated automatically from the rules - tell us if something looks wrong. (tap a row to listen)',
    'seo_home_t' => 'JLPT Japanese Dictionary (N5–N1) with Sinhala meanings',
    'seo_home_d' => 'Search %s Japanese words from JLPT N5 to N1 with Sinhala meanings, romaji, example sentences and kanji details.',
    'seo_word_t' => '%1$s – %2$s | JLPT N%3$d word',
    'seo_word_d' => '%1$s (%2$s) – JLPT N%3$d word. Meaning: %4$s. Example sentences, related words and kanji details.',
    'seo_list_t' => 'JLPT N%1$d word list – page %2$d | with Sinhala meanings',
    'seo_list_d' => 'JLPT N%1$d Japanese word list (page %2$d of %3$d) with Sinhala meanings and romaji.',
  ],
  'ja' => [
    'title' => 'JLPT 単語辞典', 'sub' => 'N5 – N1 · %s 語 · シンハラ語の意味つき',
    'ph' => 'せいしゅん · 青春 · seishun · තරුණ', 'tab_search' => '検索', 'tab_saved' => '保存',
    'hint' => '日本語・ローマ字・シンハラ語で検索できます', 'ex' => '例',
    'm_ja' => '日本語', 'm_si' => 'シンハラ語', 'm_lat' => 'ローマ字 / Singlish / English',
    'searching' => '検索中...', 'results' => '件', 'more' => 'もっと見る',
    'none' => '見つかりませんでした。かな・漢字・ローマ字で試してください。',
    'err' => '検索に失敗しました。もう一度お試しください。', 'saved_empty' => '保存した単語はありません。☆を押すと保存できます。',
    'saved_n' => '保存した単語', 'copied' => 'リンクをコピーしました', 'saved_t' => '保存しました', 'removed_t' => '削除しました',
    'listen' => '聞く', 'save' => '保存', 'close' => '閉じる', 'share' => '共有', 'page' => '詳細ページ',
    's_sent' => '例文', 's_kanji' => 'この単語の漢字', 's_more' => 'もっと見る', 's_none' => 'この単語の例文はまだありません。',
    's_load' => '読み込み中...', 's_gram' => '文法', 's_rel' => '関連単語', 'via' => '漢字',
    'words' => '語', 'crumb' => '辞典', 'prev' => '前へ', 'next' => '次へ', 'page_n' => 'ページ',
    'list_h' => 'JLPT N%d 単語一覧', 'nf_t' => 'ページが見つかりません', 'nf_b' => '辞典に戻る',
    'buy_t' => '単語集（完全版）', 'buy_s' => 'N5〜N1 · シンハラ語訳つき', 'buy_b' => '本を見る',
    'lock_t' => '無料で見られるのはここまでです',
    'lock_s' => '単語の完全なリストは、単語集（完全版）をご購入いただくか、パッケージをアップグレードしてご覧ください。',
    'lock_book' => '完全版を購入する', 'lock_up' => 'アップグレード',
    'c_title' => '活用', 'c_dict' => '辞書形', 'c_masu' => 'ます形',
    'c_te' => 'て形', 'c_nai' => 'ない形', 'c_ta' => 'た形', 'c_nak' => '過去否定', 'c_adv' => '副詞形',
    'c_note' => '規則から自動生成しています。間違いがあればお知らせください。（タップで音声）',
    'seo_home_t' => 'JLPT 単語辞典（N5〜N1）シンハラ語訳つき',
    'seo_home_d' => 'JLPT N5〜N1 の単語 %s 語を、シンハラ語の意味・ローマ字・例文・漢字情報つきで検索。',
    'seo_word_t' => '%1$s – %2$s | JLPT N%3$d 単語',
    'seo_word_d' => '%1$s（%2$s）– JLPT N%3$d の単語。意味：%4$s。例文・関連単語・漢字の情報。',
    'seo_list_t' => 'JLPT N%1$d 単語一覧 – %2$d ページ目 | シンハラ語訳つき',
    'seo_list_d' => 'JLPT N%1$d の単語一覧（%2$d / %3$d ページ）。シンハラ語の意味とローマ字つき。',
  ],
];
$dictLang = (isset($currentLang) && isset($DICT_L[$currentLang])) ? $currentLang : 'si';
$D = $DICT_L[$dictLang];

$total = (int)$pdo->query('SELECT COUNT(*) FROM dict_words')->fetchColumn();

// vocabulary book link (banner hidden automatically if the book row is missing / inactive)
$bookUrl = '';
try {
    $stB = $pdo->prepare('SELECT slug FROM books WHERE slug = :s AND is_active = 1');
    $stB->execute(['s' => $BOOK_SLUG]);
    if ($stB->fetchColumn()) {
        $bookUrl = sprintf($BOOK_URL_TPL, rawurlencode($BOOK_SLUG));
    }
} catch (Throwable $e) { /* books table not available -> no banner */ }
$lockBookUrl = $bookUrl !== '' ? $bookUrl : $BOOKS_FALLBACK;   // the lock box always has a book link

// ---- 7. SEO: <title>, description, canonical, robots, Open Graph, JSON-LD -> into the buffered <head> ----
$base      = rtrim($SITE_URL, '/') . '/' . $PAGE_FILE;
$wordUrl   = function (int $id) use ($base) { return $base . '?w=' . $id; };
$listUrl   = function (int $l, int $p) use ($base) { return $base . '?lvl=' . $l . ($p > 1 ? '&p=' . $p : ''); };
$robots    = 'index,follow,noarchive,max-snippet:-1,max-image-preview:large';
$canonical = $base;
$title     = sprintf($D['seo_home_t'], number_format($total));
$desc      = sprintf($D['seo_home_d'], number_format($total));
$headLinks = '';
$ld        = [];
$ldSite    = ['@type' => 'WebSite', 'name' => $SITE_NAME, 'url' => rtrim($SITE_URL, '/') . '/'];
$crumbs    = [['name' => $SITE_NAME, 'url' => rtrim($SITE_URL, '/') . '/'], ['name' => $D['crumb'], 'url' => $base]];

if ($mode === 'home' || $mode === 'search') {
    $ldSite['potentialAction'] = ['@type' => 'SearchAction', 'target' => $base . '?q={search_term_string}', 'query-input' => 'required name=search_term_string'];
    $ld[] = ['@context' => 'https://schema.org'] + $ldSite;
    $ld[] = ['@context' => 'https://schema.org', '@type' => 'DefinedTermSet', 'name' => $D['title'], 'description' => $desc, 'url' => $base, 'inLanguage' => ['si', 'ja', 'en']];
    if ($mode === 'search') {
        $robots = 'noindex,follow';
        $title  = dict_trunc($q, 40) . ' – ' . $D['title'];
    }
} elseif ($mode === 'word') {
    $first   = dict_first_meaning((string)$word['meaning_si']);
    $disp    = dict_disp($word);
    $canonical = $wordUrl((int)$word['id']);
    $title   = sprintf($D['seo_word_t'], $disp, $first, (int)$word['level']);
    $desc    = dict_trunc(sprintf($D['seo_word_d'], trim((string)preg_split('/[\/／]/u', (string)$word['kanji'])[0] ?: $word['reading']), $word['romaji'] ?: $word['reading'], (int)$word['level'], $word['meaning_si']), 158);
    $term = ['@context' => 'https://schema.org', '@type' => 'DefinedTerm', 'name' => $word['kanji'] !== '' ? $word['kanji'] : $word['reading'],
             'alternateName' => array_values(array_filter([$word['reading'], $word['romaji']])), 'description' => $word['meaning_si'],
             'url' => $canonical, 'inLanguage' => 'ja',
             'inDefinedTermSet' => ['@type' => 'DefinedTermSet', 'name' => 'JLPT N' . (int)$word['level'] . ' vocabulary', 'url' => $listUrl((int)$word['level'], 1)]];
    $ld[] = $term;
    $crumbs[] = ['name' => 'N' . (int)$word['level'], 'url' => $listUrl((int)$word['level'], 1)];
    $crumbs[] = ['name' => $disp, 'url' => $canonical];
} elseif ($mode === 'browse') {
    $canonical = $listUrl($bLevel, $bPage);
    $title   = sprintf($D['seo_list_t'], $bLevel, $bPage);
    $desc    = sprintf($D['seo_list_d'], $bLevel, $bPage, $bPages);
    if ($bPage > 1) {
        $headLinks .= '<link rel="prev" href="' . dict_h($listUrl($bLevel, $bPage - 1)) . '">';
    }
    if ($bPage < $bPages) {
        $headLinks .= '<link rel="next" href="' . dict_h($listUrl($bLevel, $bPage + 1)) . '">';
    }
    $items = [];
    foreach ($bRows as $i => $r) {
        $items[] = ['@type' => 'ListItem', 'position' => ($bPage - 1) * $BROWSE_PER + $i + 1, 'url' => $wordUrl((int)$r['id']), 'name' => dict_disp($r)];
    }
    $ld[] = ['@context' => 'https://schema.org', '@type' => 'CollectionPage', 'name' => $title, 'url' => $canonical,
             'mainEntity' => ['@type' => 'ItemList', 'itemListElement' => $items]];
    $crumbs[] = ['name' => 'N' . $bLevel, 'url' => $listUrl($bLevel, 1)];
    if ($bPage > 1) {
        $crumbs[] = ['name' => $D['page_n'] . ' ' . $bPage, 'url' => $canonical];
    }
} elseif ($mode === 'locked') {
    $robots    = 'noindex,follow';
    $canonical = $listUrl($bLevel, 1);
    $title     = sprintf($D['list_h'], $bLevel) . ' | ' . $SITE_NAME;
    $desc      = $D['lock_t'];
} else {
    $robots = 'noindex,follow';
    $title  = $D['nf_t'] . ' | ' . $SITE_NAME;
    $desc   = $D['nf_t'];
}
if ($mode === 'word' || $mode === 'browse') {
    $bl = [];
    foreach ($crumbs as $i => $c) {
        $bl[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['name'], 'item' => $c['url']];
    }
    $ld[] = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $bl];
}

$ogType = $mode === 'word' ? 'article' : 'website';
$seo  = '<title>' . dict_h($title) . '</title>' . "\n";
$seo .= '<meta name="description" content="' . dict_h($desc) . '">' . "\n";
$seo .= '<meta name="robots" content="' . dict_h($robots) . '">' . "\n";
$seo .= '<link rel="canonical" href="' . dict_h($canonical) . '">' . "\n";
$seo .= $headLinks . "\n";
$seo .= '<meta property="og:site_name" content="' . dict_h($SITE_NAME) . '">' . "\n";
$seo .= '<meta property="og:type" content="' . $ogType . '">' . "\n";
$seo .= '<meta property="og:title" content="' . dict_h($title) . '">' . "\n";
$seo .= '<meta property="og:description" content="' . dict_h($desc) . '">' . "\n";
$seo .= '<meta property="og:url" content="' . dict_h($canonical) . '">' . "\n";
$seo .= '<meta name="twitter:card" content="' . ($OG_IMAGE !== '' ? 'summary_large_image' : 'summary') . '">' . "\n";
$seo .= '<meta name="twitter:title" content="' . dict_h($title) . '">' . "\n";
$seo .= '<meta name="twitter:description" content="' . dict_h($desc) . '">' . "\n";
if ($OG_IMAGE !== '') {
    $seo .= '<meta property="og:image" content="' . dict_h($OG_IMAGE) . '">' . "\n";
    $seo .= '<meta name="twitter:image" content="' . dict_h($OG_IMAGE) . '">' . "\n";
}
foreach ($ld as $block) {
    $seo .= '<script type="application/ld+json">' . json_encode($block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>' . "\n";
}

// remove what header.php already printed (so nothing is duplicated), then add ours just before </head>
$headerHtml = preg_replace([
    '#<title\b[^>]*>.*?</title>#is',
    '#<meta\s+[^>]*name=["\'](?:description|robots|twitter:[a-z:]+)["\'][^>]*>#i',
    '#<meta\s+[^>]*property=["\']og:[a-z:_]+["\'][^>]*>#i',
    '#<link\s+[^>]*rel=["\']canonical["\'][^>]*>#i',
], '', $headerHtml) ?? $headerHtml;
if (stripos($headerHtml, '</head>') !== false) {
    $headerHtml = preg_replace('#</head>#i', str_replace(['\\', '$'], ['\\\\', '\\$'], $seo) . '</head>', $headerHtml, 1);
} else {
    $headerHtml = $seo . $headerHtml;     // header.php has no </head> -> best effort
}
echo $headerHtml;

$heroTag = ($mode === 'word' || $mode === 'browse') ? 'div' : 'h1';
$pageApi = $PAGE_FILE;
?>

<style>
    /* theme variables (same values as index.php) */
    :root {
        --nm-bg: #e6e9f2; --nm-light: #ffffff; --nm-dark: #c3c9dc;
        --nm-text: #2d3250; --nm-muted: #6b7290;
        --nm-brand: #4f46e5; --nm-brand-2: #7c6cf5;
        --nm-radius: 24px; --nm-radius-sm: 16px;
        --nm-raised: 8px 8px 18px var(--nm-dark), -8px -8px 18px var(--nm-light);
        --nm-raised-sm: 5px 5px 11px var(--nm-dark), -5px -5px 11px var(--nm-light);
        --nm-inset: inset 5px 5px 11px var(--nm-dark), inset -5px -5px 11px var(--nm-light);
        --nm-inset-sm: inset 3px 3px 7px var(--nm-dark), inset -3px -3px 7px var(--nm-light);
    }
    body { background: var(--nm-bg) !important; color: var(--nm-text); -webkit-tap-highlight-color: transparent; overflow-x: hidden; }
    .text-muted { color: var(--nm-muted) !important; }
    [lang="ja"] { font-family: 'Noto Sans JP', 'Hiragino Sans', 'Yu Gothic', 'Meiryo', sans-serif; }
    [lang="si"] { font-family: 'Noto Sans Sinhala', 'Iskoola Pota', 'Nirmala UI', sans-serif; }
    .d-wrap button, .d-wrap a, .d-sheet button, .d-sheet a { touch-action: manipulation; }
    .d-wrap :focus-visible, .d-sheet :focus-visible { outline: 3px solid var(--nm-brand-2); outline-offset: 2px; }

    /* ---- copy protection: nothing can be selected / long-pressed / dragged (only the search box) ---- */
    body, .d-wrap, .d-ov, .d-toast {
        -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none;
        -webkit-touch-callout: none;
    }
    input, textarea { -webkit-user-select: text !important; -moz-user-select: text !important; user-select: text !important; }
    img, a { -webkit-user-drag: none; user-drag: none; }
    ::selection { background: transparent; }
    input::selection { background: rgba(79,70,229,.25); }
    @media print { html, body { display: none !important; } }

    @keyframes dFadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
    @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }

    .d-hero {
        background: linear-gradient(135deg, var(--nm-brand) 0%, var(--nm-brand-2) 100%);
        color: #fff; border-radius: var(--nm-radius); padding: 24px 20px;
        box-shadow: var(--nm-raised); position: relative; overflow: hidden; animation: dFadeUp .5s ease both;
    }
    .d-hero::before, .d-hero::after { content: ""; position: absolute; border-radius: 50%; background: rgba(255,255,255,.12); }
    .d-hero::before { width: 130px; height: 130px; top: -45px; right: -25px; }
    .d-hero::after  { width: 80px; height: 80px; bottom: -30px; left: 14%; }
    .d-hero > * { position: relative; z-index: 1; }
    .d-hero .d-h1 { font-weight: 800; font-size: clamp(1.25rem, 4.6vw, 1.7rem); margin: 0 0 4px; line-height: 1.25; }
    .d-hero p { margin: 0; opacity: .92; font-size: clamp(.85rem, 3.2vw, .95rem); }

    /* ---- buy banner (page top + inside the sheet) ---- */
    .d-buy { display: flex; align-items: center; gap: 14px; text-decoration: none !important; color: var(--nm-text);
             background: var(--nm-bg); border-radius: var(--nm-radius-sm); box-shadow: var(--nm-raised-sm);
             padding: 12px 14px; margin-top: 16px; transition: box-shadow .2s ease, transform .2s ease; text-align: left; }
    .d-buy:active { box-shadow: var(--nm-inset-sm); transform: scale(.985); }
    .d-buy .b-ico { font-size: 1.9rem; line-height: 1; }
    .d-buy .b-txt { flex: 1; min-width: 0; }
    .d-buy .b-t { font-weight: 800; font-size: .98rem; }
    .d-buy .b-s { font-size: .78rem; color: var(--nm-muted); }
    .d-buy .b-go { flex: 0 0 auto; font-weight: 800; font-size: .8rem; color: #fff; padding: 9px 14px; border-radius: 999px;
                   background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); box-shadow: 2px 2px 6px rgba(0,0,0,.18); }
    .d-buy.sm { margin-top: 14px; padding: 10px 12px; }

    /* ---- lock box (free preview ended: buy the book / upgrade) ---- */
    .d-lock { text-align: center; background: var(--nm-bg); border-radius: var(--nm-radius); box-shadow: var(--nm-raised);
              padding: 26px 18px; margin: 22px auto; max-width: 640px; animation: dFadeUp .4s ease both; }
    .d-lock .l-ico { font-size: 2.2rem; line-height: 1; }
    .d-lock .l-t { font-weight: 800; font-size: 1.1rem; margin: 10px 0 6px; }
    .d-lock .l-s { color: var(--nm-muted); font-size: .92rem; line-height: 1.6; margin: 0 0 16px; }
    .d-lock .l-act { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; }
    .d-lock .l-act a { flex: 1 1 200px; min-height: 50px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
                       text-decoration: none !important; font-weight: 800; border-radius: 999px; padding: 12px 18px; }
    .d-lock .l-b { color: #fff; background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2)); box-shadow: var(--nm-raised-sm); }
    .d-lock .l-u { color: var(--nm-brand); background: var(--nm-bg); box-shadow: var(--nm-raised-sm); }
    .d-lock .l-act a:active { box-shadow: var(--nm-inset-sm); }
    .d-list > .d-lock { grid-column: 1 / -1; width: 100%; }

    /* ---- sticky search area (feels like an app bar) ---- */
    .d-bar { position: sticky; top: var(--d-sticky-top, 0px); z-index: 40; background: var(--nm-bg);
             margin: 0 -12px; padding: 14px 12px 8px; }
    .d-search { position: relative; }
    .d-search .d-ico { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); font-size: 1.1rem; opacity: .6; pointer-events: none; }
    .d-search input {
        width: 100%; border: 0; outline: 0; background: var(--nm-bg); color: var(--nm-text);
        border-radius: 999px; padding: 15px 52px 15px 46px; font-size: 1rem; min-height: 52px;
        box-shadow: var(--nm-inset); -webkit-appearance: none; appearance: none;
    }
    .d-search input::placeholder { color: var(--nm-muted); }
    .d-search input:focus { box-shadow: var(--nm-inset), 0 0 0 2px rgba(79,70,229,.35); }
    .d-clear { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: 40px; height: 40px; border: 0; border-radius: 50%;
               background: var(--nm-bg); color: var(--nm-muted); box-shadow: var(--nm-raised-sm); font-size: 1.3rem; line-height: 1; display: none; }
    .d-clear:active { box-shadow: var(--nm-inset-sm); }

    .d-seg { display: flex; gap: 10px; margin-top: 12px; padding-bottom: 6px; }
    .d-seg button { flex: 1; border: 0; background: var(--nm-bg); color: var(--nm-muted); font-weight: 700; font-size: .92rem; min-height: 44px;
                    padding: 10px 6px; border-radius: 999px; box-shadow: var(--nm-raised-sm); transition: box-shadow .2s ease; }
    .d-seg button.on { color: var(--nm-brand); box-shadow: var(--nm-inset-sm); }

    .d-chip { flex: 0 0 auto; border: 0; background: var(--nm-bg); color: var(--nm-text); font-weight: 700; font-size: .88rem; min-height: 40px;
              padding: 9px 18px; border-radius: 999px; box-shadow: var(--nm-raised-sm); white-space: nowrap; transition: box-shadow .2s ease;
              text-decoration: none !important; display: inline-flex; align-items: center; gap: 6px; }
    .d-chip:active { box-shadow: var(--nm-inset-sm); }
    .d-chip.on { color: var(--nm-brand); box-shadow: var(--nm-inset-sm); }

    .d-meta { display: flex; justify-content: space-between; gap: 10px; font-size: .8rem; color: var(--nm-muted); padding: 4px 6px 8px; }

    /* ---- example chips ---- */
    .d-ex-title { font-size: .82rem; color: var(--nm-muted); margin: 6px 4px 10px; font-weight: 600; }
    .d-ex { display: flex; flex-wrap: wrap; gap: 10px; }

    /* ---- result cards (grid: 1 column on phones, more on wide screens) ---- */
    .d-list { display: grid; grid-template-columns: 1fr; gap: 16px; padding-bottom: 12px; }
    @media (min-width: 720px) { .d-list { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1100px) { .d-list { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .d-list > .d-empty { grid-column: 1 / -1; }
    .d-card { display: block; width: 100%; text-align: left; border: 0; background: var(--nm-bg); color: var(--nm-text);
              border-radius: var(--nm-radius-sm); box-shadow: var(--nm-raised-sm); padding: 14px 14px 14px 16px;
              transition: box-shadow .2s ease, transform .2s ease; animation: dFadeUp .35s ease both; position: relative; cursor: pointer; min-width: 0; }
    .d-card:active { box-shadow: var(--nm-inset-sm); transform: scale(.985); }
    .d-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
    .d-read { font-size: 1.5rem; font-weight: 800; line-height: 1.25; word-break: break-word; }
    .d-kanji { font-size: 1.1rem; color: var(--nm-muted); margin-left: 8px; font-weight: 600; }
    .d-rom { font-size: .85rem; color: var(--nm-muted); margin-top: 2px; word-break: break-word; }
    .d-mean { font-size: 1.02rem; margin-top: 8px; line-height: 1.55; padding-right: 34px; word-break: break-word; }
    .d-lvl { flex: 0 0 auto; font-size: .7rem; font-weight: 800; color: #fff; padding: 4px 10px; border-radius: 999px; font-style: normal;
             box-shadow: 2px 2px 6px rgba(0,0,0,.18); display: inline-block; }
    .l5 { background: linear-gradient(135deg,#0f9d70,#2cc08f); } .l4 { background: linear-gradient(135deg,#3b82f6,#60a5fa); }
    .l3 { background: linear-gradient(135deg,#eab308,#facc15); color:#3b3200; } .l2 { background: linear-gradient(135deg,#ea6a0a,#fb923c); }
    .l1 { background: linear-gradient(135deg,#dc2626,#f97316); }
    .d-star { position: absolute; right: 4px; bottom: 2px; border: 0; background: none; font-size: 1.4rem; color: var(--nm-muted); width: 44px; height: 44px; line-height: 1; }
    .d-star.on { color: #d98900; }
    mark { background: rgba(79,70,229,.18); color: inherit; padding: 0 2px; border-radius: 4px; }

    .d-empty { text-align: center; padding: 34px 18px; margin: 10px 0; border-radius: var(--nm-radius-sm); box-shadow: var(--nm-inset); color: var(--nm-muted); }
    .d-empty .d-chip { margin-top: 14px; color: var(--nm-brand); }
    .d-skel { height: 96px; border-radius: var(--nm-radius-sm); box-shadow: var(--nm-raised-sm);
              background: linear-gradient(90deg, var(--nm-bg) 25%, #eef0f8 50%, var(--nm-bg) 75%); background-size: 200% 100%; animation: dSh 1.2s infinite; }
    @keyframes dSh { to { background-position: -200% 0; } }
    .d-more { display: block; width: 100%; max-width: 420px; margin: 6px auto 22px; border: 0; background: var(--nm-bg); color: var(--nm-brand); font-weight: 800;
              min-height: 50px; padding: 14px; border-radius: 999px; box-shadow: var(--nm-raised); }
    .d-more:active { box-shadow: var(--nm-inset); }

    /* ---- crawlable pages: word page, list page ---- */
    .d-crumb { font-size: .8rem; color: var(--nm-muted); margin: 6px 4px 12px; display: flex; flex-wrap: wrap; gap: 4px 8px; align-items: center; }
    .d-crumb a { color: var(--nm-brand); text-decoration: none; font-weight: 700; }
    .d-crumb a:hover { text-decoration: underline; }
    .d-page { background: var(--nm-bg); border-radius: var(--nm-radius); box-shadow: var(--nm-raised); padding: 20px 16px; margin: 16px 0 20px; }
    @media (min-width: 720px) { .d-page { padding: 28px 30px; } }
    .d-w-head { text-align: center; }
    .d-w-head h1 { font-size: clamp(2rem, 9vw, 3rem); font-weight: 800; line-height: 1.2; margin: 10px 0 2px; word-break: break-word; }
    .d-w-read { font-size: clamp(1.1rem, 4.6vw, 1.5rem); color: var(--nm-brand); font-weight: 700; margin: 0; }
    .d-w-rom { color: var(--nm-muted); margin: 4px 0 0; }
    .d-w-mean { margin: 16px auto 0; max-width: 640px; padding: 16px; border-radius: var(--nm-radius-sm); box-shadow: var(--nm-inset-sm); font-size: clamp(1.05rem, 4vw, 1.2rem); line-height: 1.65; }
    .d-page h2 { font-size: .92rem; font-weight: 800; color: var(--nm-brand); margin: 24px 4px 12px; }
    .d-pn { display: flex; justify-content: space-between; gap: 12px; margin-top: 22px; flex-wrap: wrap; }
    .d-pn a { flex: 1 1 140px; min-height: 48px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none !important;
              color: var(--nm-brand); font-weight: 800; background: var(--nm-bg); border-radius: 999px; box-shadow: var(--nm-raised-sm); padding: 10px 14px; text-align: center; }
    .d-pn a:active { box-shadow: var(--nm-inset-sm); }
    .d-bl { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: 1fr; gap: 12px; }
    @media (min-width: 720px) { .d-bl { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1100px) { .d-bl { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .d-bl a { display: block; text-decoration: none !important; color: var(--nm-text); background: var(--nm-bg); border-radius: 14px; box-shadow: var(--nm-raised-sm); padding: 12px 14px; min-height: 64px; }
    .d-bl a:active { box-shadow: var(--nm-inset-sm); }
    .d-bl b { font-size: 1.15rem; margin-right: 8px; }
    .d-bl i { font-style: normal; color: var(--nm-muted); font-size: .85rem; }
    .d-bl span { display: block; font-size: .92rem; margin-top: 3px; line-height: 1.45; }

    /* ---- bottom sheet (phones) / centred dialog (desktop) ---- */
    .d-ov { position: fixed; inset: 0; z-index: 99998; background: rgba(45,50,80,.45); -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px);
            display: none; align-items: flex-end; justify-content: center; opacity: 0; transition: opacity .25s ease; }
    .d-ov.show { display: flex; } .d-ov.vis { opacity: 1; }
    .d-sheet { width: 100%; max-width: 560px; background: var(--nm-bg); border-radius: 28px 28px 0 0; position: relative;
               padding: 14px 20px calc(24px + env(safe-area-inset-bottom)); box-shadow: 0 -12px 40px rgba(0,0,0,.25);
               transform: translateY(100%); transition: transform .3s ease; text-align: center; max-height: 90vh; max-height: 90dvh; overflow-y: auto;
               overscroll-behavior: contain; -webkit-overflow-scrolling: touch; }
    .d-ov.vis .d-sheet { transform: translateY(0); }
    @media (min-width: 720px) {
        .d-ov { align-items: center; padding: 24px; }
        .d-sheet { border-radius: 28px; max-width: 680px; padding: 22px 28px 24px; transform: translateY(24px) scale(.98); box-shadow: 0 24px 60px rgba(0,0,0,.3); }
        .d-ov.vis .d-sheet { transform: none; }
        .d-grab { display: none; }
    }
    .d-grab { width: 44px; height: 5px; border-radius: 99px; background: var(--nm-dark); margin: 0 auto 14px; }
    .d-s-read { font-size: clamp(1.9rem, 8vw, 2.4rem); font-weight: 800; line-height: 1.2; word-break: break-word; }
    .d-s-kanji { font-size: clamp(1.3rem, 5.6vw, 1.7rem); color: var(--nm-brand); font-weight: 700; margin-top: 4px; word-break: break-word; }
    .d-s-rom { color: var(--nm-muted); margin-top: 4px; }
    .d-s-mean { margin: 16px 0 18px; padding: 16px; border-radius: var(--nm-radius-sm); box-shadow: var(--nm-inset-sm); font-size: 1.12rem; line-height: 1.65; }
    .d-s-act { display: flex; gap: 10px; flex-wrap: wrap; }
    .d-s-act > * { flex: 1 1 calc(50% - 10px); min-width: 110px; border: 0; background: var(--nm-bg); color: var(--nm-brand); font-weight: 800; font-size: .9rem;
                   min-height: 48px; padding: 10px 6px; border-radius: 999px; box-shadow: var(--nm-raised-sm); text-decoration: none !important;
                   display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
    .d-s-act > *:active { box-shadow: var(--nm-inset-sm); }
    .d-s-act > .on { color: #b87500; box-shadow: var(--nm-inset-sm); }
    .d-s-close { width: 100%; border: 0; background: none; color: var(--nm-muted); min-height: 48px; padding: 14px; margin-top: 6px; }

    /* ---- sheet extras: conjugation + sentences + example words + kanji ---- */
    .d-x { text-align: left; margin-top: 20px; }
    .d-xload { text-align: center; color: var(--nm-muted); font-size: .85rem; padding: 12px; }
    .d-xsec { margin-bottom: 18px; }
    .d-xh { font-size: .84rem; font-weight: 800; color: var(--nm-brand); margin: 0 4px 10px; }
    .d-xs { background: var(--nm-bg); border-radius: var(--nm-radius-sm); box-shadow: var(--nm-inset-sm); padding: 12px 14px; margin-bottom: 12px; }
    .d-xs-jp { font-size: 1.12rem; font-weight: 700; line-height: 1.6; word-break: break-word; }
    .d-xs-ro { font-size: .8rem; color: var(--nm-muted); margin-top: 2px; word-break: break-word; }
    .d-xs-si { font-size: .96rem; margin-top: 6px; line-height: 1.55; }
    .d-xs-row { display: flex; align-items: center; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
    .d-xs-spk { border: 0; background: var(--nm-bg); box-shadow: var(--nm-raised-sm); border-radius: 999px; min-width: 44px; min-height: 40px; padding: 5px 14px; font-size: .9rem; }
    .d-xs-spk:active { box-shadow: var(--nm-inset-sm); }
    .d-xs-tag { font-size: .74rem; font-weight: 700; color: var(--nm-brand); background: rgba(79,70,229,.1); padding: 6px 10px;
                border-radius: 999px; text-decoration: none !important; }
    .d-xs-none { font-size: .88rem; color: var(--nm-muted); text-align: center; padding: 8px; }
    .d-xmore { width: 100%; border: 0; background: var(--nm-bg); color: var(--nm-brand); font-weight: 800; font-size: .88rem;
               min-height: 46px; padding: 11px; border-radius: 999px; box-shadow: var(--nm-raised-sm); }
    .d-xmore:active { box-shadow: var(--nm-inset-sm); }
    .d-xk { margin-bottom: 14px; }
    .d-xk-head { display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; margin: 0 4px 8px; }
    .d-xk-ch { font-size: 1.8rem; font-weight: 800; color: var(--nm-brand); line-height: 1; }
    .d-xk-m { font-weight: 700; font-size: .95rem; }
    .d-xk-r { font-size: .78rem; color: var(--nm-muted); }
    .d-xw { display: block; width: 100%; text-align: left; border: 0; background: var(--nm-bg); color: var(--nm-text); text-decoration: none !important;
            box-shadow: var(--nm-raised-sm); border-radius: 12px; padding: 10px 12px; margin-bottom: 10px; min-height: 48px; position: relative; }
    .d-xw:active { box-shadow: var(--nm-inset-sm); }
    .d-xw b { font-size: 1.05rem; margin-right: 6px; }
    .d-xw i { font-style: normal; font-size: .78rem; color: var(--nm-muted); margin-right: 6px; }
    .d-xw span { font-size: .9rem; display: block; margin-top: 2px; line-height: 1.45; }
    .d-xw .d-lvl { position: absolute; right: 10px; top: 10px; padding: 2px 8px; font-size: .66rem; }
    .d-xw.rel { padding-right: 56px; }
    .d-xwg { display: grid; grid-template-columns: 1fr; gap: 0 12px; }
    @media (min-width: 720px) { .d-xwg { grid-template-columns: 1fr 1fr; } }

    /* conjugation table */
    .d-cj { background: var(--nm-bg); border-radius: var(--nm-radius-sm); box-shadow: var(--nm-inset-sm); padding: 6px 12px; }
    .d-cj-r { display: flex; align-items: center; justify-content: space-between; gap: 10px; width: 100%; border: 0; background: none;
              color: var(--nm-text); text-align: left; padding: 10px 0; min-height: 48px; border-bottom: 1px solid rgba(122,128,153,.18); }
    .d-cj-r:last-of-type { border-bottom: 0; }
    .d-cj-r:active { opacity: .6; }
    .d-cj-l { font-size: .76rem; color: var(--nm-muted); font-weight: 700; flex: 0 0 42%; }
    .d-cj-v { flex: 1; text-align: right; font-size: 1.08rem; font-weight: 800; word-break: break-word; }
    .d-cj-v small { display: block; font-size: .8rem; font-weight: 600; color: var(--nm-brand); }
    .d-cj-n { font-size: .72rem; color: var(--nm-muted); margin: 8px 2px 4px; }

    .d-toast { position: fixed; left: 50%; bottom: calc(26px + env(safe-area-inset-bottom)); transform: translate(-50%, 20px); z-index: 100000;
               background: rgba(45,50,80,.94); color: #fff; padding: 11px 20px; border-radius: 999px; font-size: .88rem; font-weight: 600;
               opacity: 0; pointer-events: none; transition: opacity .25s ease, transform .25s ease; max-width: 90vw; text-align: center; }
    .d-toast.show { opacity: 1; transform: translate(-50%, 0); }

    @media (min-width: 768px) { .d-card:hover { transform: translateY(-2px); } }
    @media (max-width: 380px) { .d-bar { margin: 0 -8px; padding-left: 8px; padding-right: 8px; } .d-read { font-size: 1.3rem; } }
</style>

<main class="d-wrap" id="dMain">

<!-- Hero -->
<header class="d-hero mb-3">
    <<?php echo $heroTag; ?> class="d-h1">📖 <?php echo dict_h(($mode === 'browse' || $mode === 'locked') ? sprintf($D['list_h'], $bLevel) : $D['title']); ?></<?php echo $heroTag; ?>>
    <p><?php echo dict_h(sprintf($D['sub'], number_format($total))); ?></p>
</header>

<?php if ($bookUrl !== ''): ?>
<!-- Full vocabulary book banner -->
<a class="d-buy" href="<?php echo dict_h($bookUrl); ?>">
    <span class="b-ico" aria-hidden="true">📘</span>
    <span class="b-txt">
        <div class="b-t"><?php echo dict_h($D['buy_t']); ?></div>
        <div class="b-s"><?php echo dict_h($D['buy_s']); ?></div>
    </span>
    <span class="b-go"><?php echo dict_h($D['buy_b']); ?> ›</span>
</a>
<?php endif; ?>

<!-- Sticky search bar (no level chips any more) -->
<div class="d-bar" id="dBar" role="search">
    <div class="d-search">
        <span class="d-ico" aria-hidden="true">🔍</span>
        <input type="search" id="dq" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" enterkeyhint="search"
               inputmode="search" placeholder="<?php echo dict_h($D['ph']); ?>" value="<?php echo dict_h($q); ?>"
               aria-label="<?php echo dict_h($D['title']); ?>">
        <button type="button" class="d-clear" id="dClear" aria-label="Clear">&times;</button>
    </div>

    <div class="d-seg" role="tablist">
        <button type="button" id="tabSearch" class="on" role="tab">🔎 <?php echo dict_h($D['tab_search']); ?></button>
        <button type="button" id="tabSaved" role="tab">⭐ <?php echo dict_h($D['tab_saved']); ?> <span id="dSavedN">0</span></button>
    </div>
</div>

<div class="d-meta"><span id="dStatus" role="status" aria-live="polite"><?php echo dict_h($D['hint']); ?></span><span id="dMode"></span></div>

<?php if ($mode === 'notfound'): ?>
<!-- 404 -->
<section class="d-page" style="text-align:center">
    <h1 style="font-size:1.3rem;font-weight:800"><?php echo dict_h($D['nf_t']); ?></h1>
    <p><a class="d-chip" href="<?php echo dict_h($PAGE_FILE); ?>"><?php echo dict_h($D['nf_b']); ?></a></p>
</section>
<?php endif; ?>

<?php if ($mode === 'word'):
    $wl      = (int)$word['level'];
    $wKanji  = trim((string)preg_split('/[\/／]/u', (string)$word['kanji'])[0]);
?>
<!-- Word page (server-rendered so Google can read it). No previous / next links on purpose. -->
<article class="d-page" id="dWord">
    <nav class="d-crumb" aria-label="breadcrumb">
        <a href="<?php echo dict_h($PAGE_FILE); ?>"><?php echo dict_h($D['crumb']); ?></a> <span aria-hidden="true">›</span>
        <a href="<?php echo dict_h($PAGE_FILE . '?lvl=' . $wl); ?>">N<?php echo $wl; ?></a> <span aria-hidden="true">›</span>
        <span><?php echo dict_h($wKanji !== '' ? $wKanji : (string)$word['reading']); ?></span>
    </nav>

    <div class="d-w-head">
        <span class="d-lvl l<?php echo $wl; ?>">N<?php echo $wl; ?></span>
        <h1 lang="ja"><?php echo dict_h($wKanji !== '' ? $wKanji : (string)$word['reading']); ?></h1>
        <?php if ($wKanji !== '' && $wKanji !== $word['reading']): ?><p class="d-w-read" lang="ja"><?php echo dict_h((string)$word['reading']); ?></p><?php endif; ?>
        <?php if ($word['romaji'] !== ''): ?><p class="d-w-rom"><?php echo dict_h((string)$word['romaji']); ?></p><?php endif; ?>
        <p class="d-w-mean" lang="si"><?php echo dict_h((string)$word['meaning_si']); ?></p>
    </div>

    <div class="d-s-act" style="margin-top:16px">
        <button type="button" id="wpListen">🔊 <?php echo dict_h($D['listen']); ?></button>
        <button type="button" id="wpShare">🔗 <?php echo dict_h($D['share']); ?></button>
        <button type="button" id="wpSave">☆ <?php echo dict_h($D['save']); ?></button>
    </div>

    <div class="d-x" id="wpConj"></div>

    <section>
        <h2>💬 <?php echo dict_h($D['s_sent']); ?></h2>
        <div id="wpSent">
        <?php foreach ($det['sentences'] as $s): ?>
            <div class="d-xs">
                <div class="d-xs-jp" lang="ja"><?php echo dict_hl_html((string)$s['jp'], $det['needles']); ?></div>
                <?php if ($s['romaji'] !== ''): ?><div class="d-xs-ro"><?php echo dict_h((string)$s['romaji']); ?></div><?php endif; ?>
                <?php if ($s['meaning_si'] !== ''): ?><div class="d-xs-si" lang="si"><?php echo dict_h((string)$s['meaning_si']); ?></div><?php endif; ?>
                <?php if (!empty($s['title_jp'])): ?>
                    <div class="d-xs-row">
                    <?php if ($s['url'] !== ''): ?>
                        <a class="d-xs-tag" href="<?php echo dict_h($s['url']); ?>"><?php echo dict_h($D['s_gram'] . ': ' . $s['title_jp'] . ' · ' . $s['level']); ?></a>
                    <?php else: ?>
                        <span class="d-xs-tag"><?php echo dict_h($D['s_gram'] . ': ' . $s['title_jp'] . ' · ' . $s['level']); ?></span>
                    <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (!$det['sentences']): ?><div class="d-xs-none"><?php echo dict_h($D['s_none']); ?></div><?php endif; ?>
        </div>
        <?php if ($det['more']): ?><button type="button" class="d-xmore" id="wpMore"><?php echo dict_h($D['s_more']); ?></button><?php endif; ?>
    </section>

    <?php if ($det['related']): ?>
    <section>
        <h2>🔗 <?php echo dict_h($D['s_rel']); ?></h2>
        <div class="d-xwg">
        <?php foreach ($det['related'] as $r): $rk = trim((string)preg_split('/[\/／]/u', (string)$r['kanji'])[0]); ?>
            <a class="d-xw rel" href="<?php echo dict_h($PAGE_FILE . '?w=' . (int)$r['id']); ?>">
                <b lang="ja"><?php echo dict_h($rk !== '' ? $rk : (string)$r['reading']); ?></b><?php if ($rk !== '' && $rk !== $r['reading']): ?><i lang="ja"><?php echo dict_h((string)$r['reading']); ?></i><?php endif; ?>
                <span lang="si"><?php echo dict_h((string)$r['meaning_si']); ?></span>
                <em class="d-lvl l<?php echo (int)$r['level']; ?>">N<?php echo (int)$r['level']; ?></em>
            </a>
        <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($det['kanji']): ?>
    <section>
        <h2>漢 <?php echo dict_h($D['s_kanji']); ?></h2>
        <?php foreach ($det['kanji'] as $k): ?>
            <div class="d-xk">
                <div class="d-xk-head">
                    <span class="d-xk-ch" lang="ja"><?php echo dict_h((string)$k['ch']); ?></span>
                    <span class="d-xk-m" lang="si"><?php echo dict_h((string)$k['meaning']); ?></span>
                    <?php $rd = implode(' · ', array_filter([(string)$k['on'], (string)$k['kun']], function ($x) { return $x !== '' && $x !== '—'; })); ?>
                    <?php if ($rd !== ''): ?><span class="d-xk-r" lang="ja"><?php echo dict_h($rd); ?></span><?php endif; ?>
                </div>
                <div class="d-xwg">
                <?php foreach ($k['words'] as $x): ?>
                    <a class="d-xw" href="<?php echo dict_h($PAGE_FILE . '?q=' . rawurlencode((string)$x['word'])); ?>" rel="nofollow">
                        <b lang="ja"><?php echo dict_h((string)$x['word']); ?></b><i lang="ja"><?php echo dict_h((string)$x['reading']); ?></i>
                        <span lang="si"><?php echo dict_h((string)$x['meaning_si']); ?></span>
                    </a>
                <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <nav class="d-pn" aria-label="<?php echo dict_h($D['page_n']); ?>">
        <a href="<?php echo dict_h($PAGE_FILE . '?lvl=' . $wl); ?>">N<?php echo $wl; ?> · <?php echo dict_h($D['words']); ?></a>
    </nav>
</article>
<?php endif; ?>

<?php if ($mode === 'browse'): ?>
<!-- Level list page (server-rendered). Only $FREE_PAGES pages exist; the last one ends with the buy / upgrade box. -->
<section class="d-page" id="dBrowse">
    <nav class="d-crumb" aria-label="breadcrumb">
        <a href="<?php echo dict_h($PAGE_FILE); ?>"><?php echo dict_h($D['crumb']); ?></a> <span aria-hidden="true">›</span>
        <a href="<?php echo dict_h($PAGE_FILE . '?lvl=' . $bLevel); ?>">N<?php echo $bLevel; ?></a>
        <?php if ($bPage > 1): ?><span aria-hidden="true">›</span> <span><?php echo dict_h($D['page_n'] . ' ' . $bPage); ?></span><?php endif; ?>
    </nav>
    <ul class="d-bl">
    <?php foreach ($bRows as $r): $rk = trim((string)preg_split('/[\/／]/u', (string)$r['kanji'])[0]); ?>
        <li><a href="<?php echo dict_h($PAGE_FILE . '?w=' . (int)$r['id']); ?>">
            <b lang="ja"><?php echo dict_h($rk !== '' ? $rk : (string)$r['reading']); ?></b><?php if ($rk !== '' && $rk !== $r['reading']): ?><i lang="ja"><?php echo dict_h((string)$r['reading']); ?></i><?php endif; ?>
            <span lang="si"><?php echo dict_h((string)$r['meaning_si']); ?></span>
        </a></li>
    <?php endforeach; ?>
    </ul>
    <nav class="d-pn" aria-label="<?php echo dict_h($D['page_n']); ?>">
        <?php if ($bPage > 1): ?><a href="<?php echo dict_h($PAGE_FILE . '?lvl=' . $bLevel . ($bPage > 2 ? '&p=' . ($bPage - 1) : '')); ?>" rel="prev">‹ <?php echo dict_h($D['prev']); ?></a><?php endif; ?>
        <a href="<?php echo dict_h($PAGE_FILE . '?lvl=' . $bLevel . ($bPage > 1 ? '&p=' . $bPage : '')); ?>" aria-current="page"><?php echo dict_h($D['page_n'] . ' ' . $bPage . ' / ' . $bPages); ?></a>
        <?php if ($bPage < $bPages): ?><a href="<?php echo dict_h($PAGE_FILE . '?lvl=' . $bLevel . '&p=' . ($bPage + 1)); ?>" rel="next"><?php echo dict_h($D['next']); ?> ›</a><?php endif; ?>
    </nav>
    <?php if ($bLocked && $bPage >= $bPages) { echo dict_lock_box($D, $lockBookUrl, $UPGRADE_URL); } ?>
</section>
<?php endif; ?>

<?php if ($mode === 'locked'): ?>
<!-- Page 3+ of a level list: no words at all, only the buy / upgrade box -->
<section class="d-page" id="dLocked">
    <nav class="d-crumb" aria-label="breadcrumb">
        <a href="<?php echo dict_h($PAGE_FILE); ?>"><?php echo dict_h($D['crumb']); ?></a> <span aria-hidden="true">›</span>
        <a href="<?php echo dict_h($PAGE_FILE . '?lvl=' . $bLevel); ?>">N<?php echo $bLevel; ?></a>
    </nav>
    <?php echo dict_lock_box($D, $lockBookUrl, $UPGRADE_URL); ?>
    <nav class="d-pn"><a href="<?php echo dict_h($PAGE_FILE . '?lvl=' . $bLevel); ?>">‹ N<?php echo $bLevel; ?> · <?php echo dict_h($D['words']); ?></a></nav>
</section>
<?php endif; ?>

<?php if ($mode === 'home' || $mode === 'search'): ?>
<div id="dEx">
    <div class="d-ex-title"><?php echo dict_h($D['ex']); ?>:</div>
    <div class="d-ex">
        <button type="button" class="d-chip dx" lang="ja">学校</button>
        <button type="button" class="d-chip dx">gakkou</button>
        <button type="button" class="d-chip dx" lang="si">තරුණ</button>
        <button type="button" class="d-chip dx">pirimi</button>
        <button type="button" class="d-chip dx" lang="ja">コーヒー</button>
        <button type="button" class="d-chip dx">elevator</button>
    </div>
</div>
<?php endif; ?>

<div class="d-list" id="dList" aria-live="polite"></div>
<button type="button" class="d-more" id="dMore" style="display:none"><?php echo dict_h($D['more']); ?></button>

</main>

<!-- Word detail bottom sheet -->
<div class="d-ov" id="dOv" role="dialog" aria-modal="true" aria-labelledby="sRead">
    <div class="d-sheet" id="dSheet" tabindex="-1">
        <div class="d-grab"></div>
        <span class="d-lvl" id="sLvl"></span>
        <div class="d-s-read" id="sRead" lang="ja"></div>
        <div class="d-s-kanji" id="sKanji" lang="ja"></div>
        <div class="d-s-rom" id="sRom"></div>
        <div class="d-s-mean" id="sMean" lang="si"></div>
        <div class="d-s-act">
            <button type="button" id="sListen">🔊 <?php echo dict_h($D['listen']); ?></button>
            <button type="button" id="sShare">🔗 <?php echo dict_h($D['share']); ?></button>
            <button type="button" id="sSave">☆ <?php echo dict_h($D['save']); ?></button>
            <a id="sPage" href="#">📄 <?php echo dict_h($D['page']); ?></a>
        </div>

        <!-- lazy-loaded: conjugation + example sentences + example words + kanji (filled by JS) -->
        <div class="d-x" id="sExtra"></div>

        <?php if ($bookUrl !== ''): ?>
        <a class="d-buy sm" href="<?php echo dict_h($bookUrl); ?>">
            <span class="b-ico" aria-hidden="true">📘</span>
            <span class="b-txt">
                <div class="b-t"><?php echo dict_h($D['buy_t']); ?></div>
                <div class="b-s"><?php echo dict_h($D['buy_s']); ?></div>
            </span>
            <span class="b-go"><?php echo dict_h($D['buy_b']); ?> ›</span>
        </a>
        <?php endif; ?>

        <button type="button" class="d-s-close" id="sClose"><?php echo dict_h($D['close']); ?></button>
    </div>
</div>
<div class="d-toast" id="dToast" role="status"></div>

<script>
(function () {
  'use strict';
  var L = <?php echo json_encode($D, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); ?>;
  var URLS = <?php echo json_encode(['book' => $lockBookUrl, 'up' => $UPGRADE_URL], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG); ?>;
  var API = location.pathname;
  var WP = <?php echo $mode === 'word'
      ? json_encode(['id' => (int)$word['id'], 'level' => (int)$word['level'], 'seq' => (int)$word['seq'], 'reading' => $word['reading'], 'kanji' => $word['kanji'],
                     'meaning_si' => $word['meaning_si'], 'romaji' => $word['romaji'], 'pos' => $word['pos'] ?? null], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)
      : 'null'; ?>;
  var $ = function (s) { return document.querySelector(s); };
  var el = function (tag, cls, text, lang) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    if (text != null) e.textContent = text;
    if (lang) e.setAttribute('lang', lang);
    return e;
  };
  var E = { q: $('#dq'), clear: $('#dClear'), list: $('#dList'), status: $('#dStatus'), mode: $('#dMode'), more: $('#dMore'),
            ex: $('#dEx'), savedN: $('#dSavedN'), tabSearch: $('#tabSearch'), tabSaved: $('#tabSaved'),
            ov: $('#dOv'), toast: $('#dToast'), extra: $('#sExtra'), sheet: $('#dSheet') };
  var S = { offset: 0, view: 'search', timer: null, ctrl: null, cur: null, open: false, lastFocus: null };
  var X = { cache: {}, tok: 0, soff: 0 };   // word-extras state (cache = in-memory, per page visit)

  /* ---------- COPY PROTECTION (hard-coded) ----------
     Blocks copy / cut / right-click / drag / select / print / save / view-source / dev-tools keys.
     The search box is the only place where selecting and copying still works. */
  (function () {
    var inField = function (t) { return !!(t && t.closest && t.closest('input,textarea')); };
    ['copy', 'cut'].forEach(function (n) {
      document.addEventListener(n, function (e) {
        if (inField(e.target)) return;
        e.preventDefault();
        try { e.clipboardData.setData('text/plain', location.hostname); } catch (x) {}
      }, true);
    });
    ['contextmenu', 'dragstart', 'selectstart'].forEach(function (n) {
      document.addEventListener(n, function (e) { if (!inField(e.target)) e.preventDefault(); }, true);
    });
    document.addEventListener('keydown', function (e) {
      var k = (e.key || '').toLowerCase(), mod = e.ctrlKey || e.metaKey;
      if (e.key === 'F12') { e.preventDefault(); return; }
      if (mod && e.shiftKey && ['i', 'j', 'c', 'k'].indexOf(k) > -1) { e.preventDefault(); return; }
      if (mod && ['s', 'u', 'p'].indexOf(k) > -1) { e.preventDefault(); return; }
      if (mod && ['c', 'x', 'a'].indexOf(k) > -1 && !inField(e.target)) { e.preventDefault(); }
    }, true);
    window.addEventListener('beforeprint', function () { document.documentElement.style.display = 'none'; });
    window.addEventListener('afterprint', function () { document.documentElement.style.display = ''; });
    document.addEventListener('visibilitychange', function () { try { window.getSelection().removeAllRanges(); } catch (x) {} });
  })();

  /* keep the sticky bar below a fixed/sticky site header */
  (function () {
    var h = document.querySelector('header.site-header, .navbar, nav.navbar');
    if (!h) return;
    var cs = getComputedStyle(h);
    if (cs.position === 'fixed' || cs.position === 'sticky') {
      document.documentElement.style.setProperty('--d-sticky-top', h.offsetHeight + 'px');
    }
  })();

  /* ---------- conjugation (godan / ichidan / irregular / adjectives) ---------- */
  var ROW_A = { 'う': 'わ', 'く': 'か', 'ぐ': 'が', 'す': 'さ', 'つ': 'た', 'ぬ': 'な', 'ぶ': 'ば', 'む': 'ま', 'る': 'ら' };
  var ROW_I = { 'う': 'い', 'く': 'き', 'ぐ': 'ぎ', 'す': 'し', 'つ': 'ち', 'ぬ': 'に', 'ぶ': 'び', 'む': 'み', 'る': 'り' };
  var ROW_U = { 'い': 'う', 'き': 'く', 'ぎ': 'ぐ', 'し': 'す', 'ち': 'つ', 'に': 'ぬ', 'び': 'ぶ', 'み': 'む', 'り': 'る' };
  var TE_TA = { 'う': 'っ', 'つ': 'っ', 'る': 'っ', 'む': 'ん', 'ぶ': 'ん', 'ぬ': 'ん', 'く': 'い', 'ぐ': 'い', 'す': 'し' };
  var HONORIFIC = ['いらっしゃる', 'くださる', 'なさる', 'おっしゃる', 'ござる'];

  /* masu-form -> dictionary form (also leaves dictionary forms untouched) */
  function dictForm(s, pos) {
    if (!/ます$/.test(s)) return s;
    var stem = s.slice(0, -2);
    if (pos === 'v3') {
      if (/し$/.test(stem)) return stem.slice(0, -1) + 'する';
      if (/(き|来)$/.test(stem)) return stem.slice(0, -1) + (/来$/.test(stem) ? '来る' : 'くる');
      return null;
    }
    if (pos === 'v2') return stem + 'る';
    if (pos === 'v1') {
      var hon = { 'いらっしゃい': 'いらっしゃる', 'ください': 'くださる', 'なさい': 'なさる', 'おっしゃい': 'おっしゃる', 'ござい': 'ござる' };
      for (var h in hon) { if (stem.slice(-h.length) === h) return stem.slice(0, -h.length) + hon[h]; }
      var last = stem.slice(-1);
      if (ROW_U[last]) return stem.slice(0, -1) + ROW_U[last];
      return null;
    }
    return null;
  }

  /* returns {dict, masu, te, nai, ta} or null */
  function conjVerb(d, pos) {
    if (!d || d.slice(-1) !== 'る' && !/[うくぐすつぬぶむ]$/.test(d)) return null;
    var base, last = d.slice(-1), a;
    if (pos === 'v3') {
      if (/する$/.test(d)) { base = d.slice(0, -2); return { dict: d, masu: base + 'します', te: base + 'して', nai: base + 'しない', ta: base + 'した' }; }
      if (/来る$/.test(d)) { base = d.slice(0, -2); return { dict: d, masu: base + '来ます', te: base + '来て', nai: base + '来ない', ta: base + '来た' }; }
      if (/くる$/.test(d)) { base = d.slice(0, -2); return { dict: d, masu: base + 'きます', te: base + 'きて', nai: base + 'こない', ta: base + 'きた' }; }
      return null;
    }
    if (pos === 'v2') {
      if (last !== 'る') return null;
      base = d.slice(0, -1);
      return { dict: d, masu: base + 'ます', te: base + 'て', nai: base + 'ない', ta: base + 'た' };
    }
    if (pos !== 'v1') return null;
    base = d.slice(0, -1);
    var masu = base + ROW_I[last] + 'ます';
    for (var i = 0; i < HONORIFIC.length; i++) {
      if (d === HONORIFIC[i] || d.slice(-HONORIFIC[i].length) === HONORIFIC[i]) masu = base + 'い' + 'ます';
    }
    var te, ta;
    if (last === 'く' && /(いく|行く)$/.test(d)) { te = base + 'って'; ta = base + 'った'; }
    else if (last === 'ぐ') { te = base + 'いで'; ta = base + 'いだ'; }
    else {
      var c = TE_TA[last];
      var dk = (c === 'ん') ? ['んで', 'んだ'] : (c === 'い') ? ['いて', 'いた'] : (c === 'し') ? ['して', 'した'] : ['って', 'った'];
      te = base + dk[0]; ta = base + dk[1];
    }
    if (d === 'ある') a = 'ない';
    else if (d === '有る' || d === '在る') a = 'ない';
    else a = base + ROW_A[last] + 'ない';
    return { dict: d, masu: masu, te: te, nai: a, ta: ta };
  }

  function conjAdj(d, pos) {
    if (pos === 'adj_i') {
      if (!/い$/.test(d)) return null;
      var stem = d === 'いい' ? 'よ' : d.slice(0, -1);
      return { dict: d, nai: stem + 'くない', ta: stem + 'かった', te: stem + 'くて', nakatta: stem + 'くなかった', adv: stem + 'く' };
    }
    if (pos === 'adj_na') {
      return { dict: d, nai: d + 'じゃない', ta: d + 'だった', te: d + 'で', nakatta: d + 'じゃなかった', adv: d + 'に' };
    }
    return null;
  }

  /* one display form (reading or kanji) -> table object or null */
  function conjOne(form, pos) {
    if (!form) return null;
    if (pos === 'adj_i' || pos === 'adj_na') return conjAdj(form, pos);
    var d = dictForm(form, pos);
    return d ? conjVerb(d, pos) : null;
  }

  /* ---------- saved words (localStorage, guarded) ---------- */
  function loadSaved() { try { return JSON.parse(localStorage.getItem('dict_saved') || '[]'); } catch (e) { return []; } }
  function storeSaved(a) { try { localStorage.setItem('dict_saved', JSON.stringify(a)); } catch (e) {} }
  var saved = loadSaved();
  var kOf = function (w) { return w.level + '-' + w.seq; };
  var isSaved = function (w) { return saved.some(function (s) { return kOf(s) === kOf(w); }); };
  function toggleSaved(w) {
    var was = isSaved(w);
    if (was) saved = saved.filter(function (s) { return kOf(s) !== kOf(w); });
    else saved.unshift({ id: w.id, level: w.level, seq: w.seq, reading: w.reading, kanji: w.kanji, meaning_si: w.meaning_si, romaji: w.romaji, pos: w.pos || null });
    storeSaved(saved); E.savedN.textContent = saved.length;
    if (navigator.vibrate) { try { navigator.vibrate(12); } catch (e) {} }
    toast(was ? L.removed_t : L.saved_t);
    return !was;
  }
  E.savedN.textContent = saved.length;

  /* ---------- helpers ---------- */
  var tt;
  function toast(msg) { E.toast.textContent = msg; E.toast.classList.add('show'); clearTimeout(tt); tt = setTimeout(function () { E.toast.classList.remove('show'); }, 1500); }
  function detectMode(q) {
    if (/[\u3040-\u30FF\u4E00-\u9FFF]/.test(q)) return L.m_ja;
    if (/[\u0D80-\u0DFF]/.test(q)) return L.m_si;
    if (/[A-Za-z]/.test(q)) return L.m_lat;
    return '';
  }
  function hl(text, q) {
    var f = document.createDocumentFragment(), i = q ? text.toLowerCase().indexOf(q.toLowerCase()) : -1;
    if (i < 0) { f.appendChild(document.createTextNode(text)); return f; }
    f.appendChild(document.createTextNode(text.slice(0, i)));
    var m = document.createElement('mark'); m.textContent = text.slice(i, i + q.length); f.appendChild(m);
    f.appendChild(document.createTextNode(text.slice(i + q.length)));
    return f;
  }
  /* highlight the first needle (from the server) that occurs in the sentence */
  function hlAny(text, needles) {
    for (var i = 0; i < (needles || []).length; i++) { if (text.indexOf(needles[i]) >= 0) return hl(text, needles[i]); }
    return hl(text, '');
  }
  function speak(text) {
    if (!('speechSynthesis' in window)) return;
    var u = new SpeechSynthesisUtterance(text.replace(/[～〜~…（）()／\/・]/g, ' ').trim());
    u.lang = 'ja-JP'; u.rate = 0.85; window.speechSynthesis.cancel(); window.speechSynthesis.speak(u);
  }
  function firstKanji(w) { return (w.kanji || '').split(/[\/／]/)[0].trim(); }
  function wordUrl(w) { return w && w.id ? API + '?w=' + encodeURIComponent(w.id) : ''; }
  /* share = LINK ONLY (the word text is never put on the clipboard) */
  function shareWord(w) {
    var url = wordUrl(w) ? new URL(wordUrl(w), location.href).href : location.href;
    if (navigator.share) { navigator.share({ title: document.title, url: url }).catch(function () {}); }
    else if (navigator.clipboard) navigator.clipboard.writeText(url).then(function () { toast(L.copied); }, function () {});
  }

  /* buy the book / upgrade box (after 60 search results) */
  function lockBox() {
    var b = el('div', 'd-lock'); b.id = 'dLock';
    b.appendChild(el('div', 'l-ico', '🔒'));
    b.appendChild(el('div', 'l-t', L.lock_t));
    b.appendChild(el('p', 'l-s', L.lock_s));
    var act = el('div', 'l-act');
    var a1 = el('a', 'l-b', '📘 ' + L.lock_book); a1.href = URLS.book;
    var a2 = el('a', 'l-u', '👑 ' + L.lock_up); a2.href = URLS.up;
    act.appendChild(a1); act.appendChild(a2); b.appendChild(act);
    return b;
  }

  /* ---------- cards ---------- */
  function card(w, q, i) {
    var c = el('div', 'd-card'); c.setAttribute('role', 'button'); c.tabIndex = 0; c.setAttribute('data-k', kOf(w));
    c.style.animationDelay = Math.min(i, 8) * 0.03 + 's';
    var top = el('div', 'd-top'), left = el('div');
    var line = el('div'), r = el('span', 'd-read', null, 'ja'); r.appendChild(hl(w.reading, q)); line.appendChild(r);
    if (w.kanji) { var k = el('span', 'd-kanji', null, 'ja'); k.appendChild(hl(w.kanji, q)); line.appendChild(k); }
    left.appendChild(line); left.appendChild(el('div', 'd-rom', w.romaji));
    top.appendChild(left); top.appendChild(el('span', 'd-lvl l' + w.level, 'N' + w.level));
    var m = el('div', 'd-mean', null, 'si'); m.appendChild(hl(w.meaning_si, q));
    var star = el('button', 'd-star' + (isSaved(w) ? ' on' : ''), isSaved(w) ? '★' : '☆'); star.type = 'button'; star.setAttribute('aria-label', L.save);
    star.onclick = function (ev) {
      ev.stopPropagation(); var on = toggleSaved(w); star.textContent = on ? '★' : '☆'; star.classList.toggle('on', on);
      if (S.view === 'saved') showSaved();
    };
    c.appendChild(top); c.appendChild(m); c.appendChild(star);
    c.onclick = function () { openSheet(w); };
    c.onkeydown = function (ev) { if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); openSheet(w); } };
    return c;
  }
  function skeletons() { E.list.textContent = ''; for (var i = 0; i < 4; i++) E.list.appendChild(el('div', 'd-skel')); }

  /* ---------- word extras: conjugation + sentences + example words + kanji (lazy) ---------- */
  function wkey(w) { return w.id ? 'i' + w.id : 'k' + w.level + '-' + w.seq; }
  function detailUrl(w, soff) {
    return API + '?json=2&v=7&' + (w.id ? 'id=' + encodeURIComponent(w.id) : 'level=' + w.level + '&seq=' + w.seq) + '&soff=' + soff;
  }
  function sentEl(s, needles) {
    var b = el('div', 'd-xs');
    var jp = el('div', 'd-xs-jp', null, 'ja'); jp.appendChild(hlAny(s.jp, needles)); b.appendChild(jp);
    if (s.romaji) b.appendChild(el('div', 'd-xs-ro', s.romaji));
    if (s.meaning_si) b.appendChild(el('div', 'd-xs-si', s.meaning_si, 'si'));
    var row = el('div', 'd-xs-row');
    var spk = el('button', 'd-xs-spk', '🔊'); spk.type = 'button'; spk.setAttribute('aria-label', L.listen);
    spk.onclick = function () { speak(s.jp); };
    row.appendChild(spk);
    if (s.title_jp) {
      var tag = s.url ? el('a', 'd-xs-tag', L.s_gram + ': ' + s.title_jp + ' · ' + s.level) : el('span', 'd-xs-tag', L.s_gram + ': ' + s.title_jp + ' · ' + s.level);
      if (s.url) tag.href = s.url;
      row.appendChild(tag);
    }
    b.appendChild(row);
    return b;
  }

  /* conjugation table: needs the word type (pos) and a hiragana reading */
  function conjSection(w, pos) {
    if (!pos || !/^[\u3040-\u309F\u30FC]+$/.test(w.reading || '')) return null;
    var cr = conjOne(w.reading, pos);
    if (!cr) return null;
    var ck = null, kj = firstKanji(w);
    if (kj && /^[\u4E00-\u9FFF々]+[\u3040-\u309F]*$/.test(kj)) {
      ck = conjOne(kj, pos);
      if (ck && ck.dict.slice(-1) !== cr.dict.slice(-1)) ck = null;   // kanji spelling does not fit the reading -> hide it
    }
    var keys = (pos === 'adj_i' || pos === 'adj_na') ? ['dict', 'nai', 'ta', 'te', 'nak', 'adv'] : ['dict', 'masu', 'te', 'nai', 'ta'];
    var lab = { dict: L.c_dict, masu: L.c_masu, te: L.c_te, nai: L.c_nai, ta: L.c_ta, nak: L.c_nak, adv: L.c_adv };
    var sec = el('div', 'd-xsec'), box = el('div', 'd-cj');
    sec.appendChild(el('div', 'd-xh', '🔀 ' + L.c_title));
    keys.forEach(function (k) {
      var val = k === 'nak' ? cr.nakatta : cr[k];
      if (!val) return;
      var row = el('button', 'd-cj-r'); row.type = 'button';
      row.appendChild(el('span', 'd-cj-l', lab[k]));
      var v = el('span', 'd-cj-v', val, 'ja');
      var kv = ck ? (k === 'nak' ? ck.nakatta : ck[k]) : null;
      if (kv && kv !== val) v.appendChild(el('small', null, kv));
      row.appendChild(v);
      row.onclick = function () { speak(val); };
      box.appendChild(row);
    });
    box.appendChild(el('div', 'd-cj-n', L.c_note));
    sec.appendChild(box);
    return sec;
  }

  /* wire a "show more sentences" button to a list */
  function moreButton(w, list, needles, startOff) {
    var mb = el('button', 'd-xmore', L.s_more); mb.type = 'button';
    var off = startOff;
    mb.onclick = function () {
      mb.disabled = true;
      fetch(detailUrl(w, off)).then(function (r) { return r.json(); }).then(function (n) {
        n.sentences.forEach(function (s) { list.appendChild(sentEl(s, needles)); });
        off += n.sentences.length;
        if (n.more) mb.disabled = false; else mb.style.display = 'none';
      }).catch(function () { mb.disabled = false; });
    };
    return mb;
  }

  function renderExtras(box, w, d) {
    var cj = conjSection(w, d.pos || w.pos);
    if (cj) box.appendChild(cj);

    var sec = el('div', 'd-xsec'), list = el('div');
    sec.appendChild(el('div', 'd-xh', '💬 ' + L.s_sent));
    d.sentences.forEach(function (s) { list.appendChild(sentEl(s, d.needles)); });
    if (!d.sentences.length) list.appendChild(el('div', 'd-xs-none', L.s_none));
    sec.appendChild(list);
    if (d.more) sec.appendChild(moreButton(w, list, d.needles, d.sentences.length));
    box.appendChild(sec);

    if (d.related && d.related.length) {
      var rs = el('div', 'd-xsec'), grid = el('div', 'd-xwg');
      rs.appendChild(el('div', 'd-xh', '🔗 ' + L.s_rel));
      d.related.forEach(function (x) {
        var b = el('button', 'd-xw rel'); b.type = 'button';
        var kj = firstKanji(x);
        b.appendChild(el('b', null, kj || x.reading, 'ja'));
        if (kj && kj !== x.reading) b.appendChild(el('i', null, x.reading, 'ja'));
        b.appendChild(el('span', null, x.meaning_si, 'si'));
        b.appendChild(el('em', 'd-lvl l' + x.level, 'N' + x.level));
        b.onclick = function () { openSheet(x); };      // tap = open that word (its own sentences / words load)
        grid.appendChild(b);
      });
      rs.appendChild(grid);
      box.appendChild(rs);
    }

    if (d.kanji && d.kanji.length) {
      var ks = el('div', 'd-xsec');
      ks.appendChild(el('div', 'd-xh', '漢 ' + L.s_kanji));
      d.kanji.forEach(function (k) {
        var kb = el('div', 'd-xk'), head = el('div', 'd-xk-head');
        head.appendChild(el('span', 'd-xk-ch', k.ch, 'ja'));
        head.appendChild(el('span', 'd-xk-m', k.meaning || '', 'si'));
        var rd = [k.on, k.kun].filter(function (x) { return x && x !== '—'; }).join(' · ');
        if (rd) head.appendChild(el('span', 'd-xk-r', rd, 'ja'));
        kb.appendChild(head);
        var g = el('div', 'd-xwg');
        (k.words || []).forEach(function (x) {
          var b = el('button', 'd-xw'); b.type = 'button';
          b.appendChild(el('b', null, x.word, 'ja')); b.appendChild(el('i', null, x.reading, 'ja')); b.appendChild(el('span', null, x.meaning_si, 'si'));
          b.onclick = function () { closeSheet(true); E.q.value = x.word; search(true); window.scrollTo({ top: 0, behavior: 'smooth' }); };   // tap = search that word
          g.appendChild(b);
        });
        kb.appendChild(g);
        ks.appendChild(kb);
      });
      box.appendChild(ks);
    }
  }
  function loadExtras(w) {
    var box = E.extra, tok = ++X.tok, k = wkey(w);
    box.textContent = '';
    if (X.cache[k]) { renderExtras(box, w, X.cache[k]); return; }
    box.appendChild(el('div', 'd-xload', L.s_load));
    fetch(detailUrl(w, 0))
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
      .then(function (d) {
        X.cache[k] = d;
        if (tok !== X.tok) return;          // user already opened another word
        box.textContent = ''; renderExtras(box, w, d);
      })
      .catch(function () { if (tok === X.tok) box.textContent = ''; });   // fail silently - sheet still works
  }

  /* ---------- bottom sheet (back button closes it) ---------- */
  function openSheet(w) {
    S.cur = w;
    if (!S.open) S.lastFocus = document.activeElement;
    $('#sLvl').className = 'd-lvl l' + w.level; $('#sLvl').textContent = 'N' + w.level;
    $('#sRead').textContent = w.reading; $('#sKanji').textContent = w.kanji || ''; $('#sRom').textContent = w.romaji; $('#sMean').textContent = w.meaning_si;
    var pg = $('#sPage'); pg.href = wordUrl(w) || '#'; pg.style.display = w.id ? '' : 'none';
    refreshSheetSave();
    E.sheet.scrollTop = 0;
    E.ov.classList.add('show'); requestAnimationFrame(function () { E.ov.classList.add('vis'); E.sheet.focus({ preventScroll: true }); });
    document.body.style.overflow = 'hidden';
    var u = wordUrl(w);
    try {
      if (u) { if (S.open) history.replaceState({ sheet: 1 }, '', u); else history.pushState({ sheet: 1 }, '', u); }
      else if (!S.open) history.pushState({ sheet: 1 }, '', location.href);
    } catch (e) {}
    S.open = true;
    loadExtras(w);                           // sheet is already visible; extras arrive a moment later
  }
  function closeSheet(noBack) {
    if (!S.open) return;
    X.tok++;                                 // cancel any pending extras render
    S.open = false;
    E.ov.classList.remove('vis'); setTimeout(function () { if (!S.open) E.ov.classList.remove('show'); }, 300); document.body.style.overflow = '';
    if (S.lastFocus && S.lastFocus.focus) { try { S.lastFocus.focus({ preventScroll: true }); } catch (e) {} }
    if (!noBack && history.state && history.state.sheet) { try { history.back(); } catch (e) {} }
  }
  window.addEventListener('popstate', function () { if (S.open) closeSheet(true); });
  function refreshSheetSave() {
    var b = $('#sSave'), on = S.cur && isSaved(S.cur);
    b.textContent = (on ? '★ ' : '☆ ') + L.save; b.classList.toggle('on', !!on);
  }
  $('#sListen').onclick = function () { if (S.cur) speak(S.cur.reading); };
  $('#sShare').onclick = function () { if (S.cur) shareWord(S.cur); };
  $('#sSave').onclick = function () { if (!S.cur) return; toggleSaved(S.cur); refreshSheetSave(); if (S.view === 'saved') showSaved(); else if (E.q.value.trim()) refreshStars(); };
  $('#sClose').onclick = function () { closeSheet(); };
  E.ov.addEventListener('click', function (ev) { if (ev.target === E.ov) closeSheet(); });
  document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape' && S.open) closeSheet(); });
  function refreshStars() {
    [].forEach.call(E.list.querySelectorAll('.d-card'), function (c) {
      var on = saved.some(function (s) { return kOf(s) === c.getAttribute('data-k'); });
      var st = c.querySelector('.d-star'); if (st) { st.textContent = on ? '★' : '☆'; st.classList.toggle('on', on); }
    });
  }

  /* ---------- search ---------- */
  function setTab(v) {
    S.view = v; E.tabSearch.classList.toggle('on', v === 'search'); E.tabSaved.classList.toggle('on', v === 'saved');
  }
  function updateUrl(q) {
    if (S.open || WP) return;               // keep ?w= while a sheet / word page is showing
    try {
      var u = new URL(location.href);
      u.searchParams.delete('w'); u.searchParams.delete('lvl'); u.searchParams.delete('p'); u.searchParams.delete('level');
      q ? u.searchParams.set('q', q) : u.searchParams.delete('q');
      history.replaceState(null, '', u);
    } catch (e) {}
  }
  function search(reset) {
    setTab('search');
    var q = E.q.value.trim();
    E.clear.style.display = q ? 'flex' : 'none'; E.clear.style.alignItems = 'center'; E.clear.style.justifyContent = 'center';
    E.mode.textContent = detectMode(q);
    if (reset) S.offset = 0;
    updateUrl(q);
    if (!q) { E.list.textContent = ''; E.more.style.display = 'none'; if (E.ex) E.ex.style.display = ''; E.status.textContent = L.hint; return; }
    if (E.ex) E.ex.style.display = 'none';
    if (S.ctrl) S.ctrl.abort();
    S.ctrl = ('AbortController' in window) ? new AbortController() : null;
    if (reset) { skeletons(); E.status.textContent = L.searching; }
    var url = API + '?json=1&q=' + encodeURIComponent(q) + '&offset=' + S.offset;
    fetch(url, S.ctrl ? { signal: S.ctrl.signal } : {})
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
      .then(function (d) {
        if (reset) E.list.textContent = '';
        d.results.forEach(function (w, i) { E.list.appendChild(card(w, q, i)); });
        S.offset += d.results.length;
        E.more.style.display = d.more ? '' : 'none';
        if (!S.offset) { E.list.appendChild(el('div', 'd-empty', L.none)); E.status.textContent = ''; }
        else E.status.textContent = S.offset + (d.more || d.locked ? '+ ' : ' ') + L.results;
        if (d.locked) E.list.appendChild(lockBox());      // free preview ended -> buy the book / upgrade
      })
      .catch(function (e) {
        if (e.name === 'AbortError') return;
        E.list.textContent = ''; E.list.appendChild(el('div', 'd-empty', L.err));
      });
  }
  function showSaved() {
    setTab('saved'); if (E.ex) E.ex.style.display = 'none'; E.more.style.display = 'none'; E.list.textContent = ''; E.mode.textContent = '';
    E.status.textContent = L.saved_n + ': ' + saved.length;
    if (!saved.length) E.list.appendChild(el('div', 'd-empty', L.saved_empty));
    saved.forEach(function (w, i) { E.list.appendChild(card(w, '', i)); });
  }

  /* ---------- server-rendered word page: conjugation, buttons, "more sentences" ---------- */
  function initWordPage(w) {
    var cj = conjSection(w, w.pos), cbox = $('#wpConj');
    if (cj && cbox) cbox.appendChild(cj);
    var b;
    if ((b = $('#wpListen'))) b.onclick = function () { speak(w.reading); };
    if ((b = $('#wpShare')))  b.onclick = function () { shareWord(w); };
    if ((b = $('#wpSave'))) {
      var paint = function () { var on = isSaved(w); b.textContent = (on ? '★ ' : '☆ ') + L.save; b.classList.toggle('on', on); };
      paint(); b.onclick = function () { toggleSaved(w); paint(); };
    }
    var more = $('#wpMore'), list = $('#wpSent');
    if (more && list) {
      var off = list.querySelectorAll('.d-xs').length, needles = [];
      var go = function () {
        more.disabled = true;
        fetch(detailUrl(w, off)).then(function (r) { return r.json(); }).then(function (n) {
          needles = n.needles || needles;
          n.sentences.forEach(function (s) { list.appendChild(sentEl(s, needles)); });
          off += n.sentences.length;
          if (n.more) more.disabled = false; else more.style.display = 'none';
        }).catch(function () { more.disabled = false; });
      };
      more.onclick = go;
    }
  }

  /* ---------- events ---------- */
  E.q.addEventListener('input', function () { clearTimeout(S.timer); S.timer = setTimeout(function () { search(true); }, 250); });
  E.q.addEventListener('keydown', function (ev) { if (ev.key === 'Enter') { E.q.blur(); } });
  E.clear.onclick = function () { E.q.value = ''; search(true); E.q.focus(); };
  E.more.onclick = function () { search(false); };
  E.tabSearch.onclick = function () { search(true); };
  E.tabSaved.onclick = showSaved;
  [].forEach.call(document.querySelectorAll('.dx'), function (c) { c.onclick = function () { E.q.value = c.textContent; search(true); }; });

  /* ---------- init from URL ---------- */
  if (WP) initWordPage(WP);
  if (E.q.value.trim()) search(true);
})();
</script>

<?php
/* ===== Add to Home Screen popup (shared file: includes/a2hs.php) =====
   The dictionary is a public page, so show it to every mobile visitor.
   Set to true if you want logged-in users only.
   Test on phone: open this page with ?a2hs=1                                    */
$a2hsLoginOnly = false;
require __DIR__ . '/includes/a2hs.php';
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>