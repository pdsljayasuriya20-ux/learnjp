<?php
/**
 * past_import.php - ADMIN tool: paste / upload a paper in the simple text format, preview it, and save it to the database.
 *
 *   1. Change $PP_IMPORT_KEY below to a long random secret (this is the only password of the tool).
 *   2. Open  https://learnjapanese.jp/past_import.php   (never link to it from the site).
 *   3. Paste the text (or choose a .txt file), type the key, press "Preview".  Tick "Save" to write to the DB.
 *
 * It also checks every target word against the dictionary (dict_words) and lists the words that are MISSING,
 * so you can add them to the dictionary (the list is plain text, one word per line).
 *
 * ------------------------------------------------------------------ TEXT FORMAT ------------------------------------------------------------------
 *   @PAPER level=N3 code=n3-sample-1 title="N3 Sample 1 - 文字・語彙" section=vocab minutes=10 free=1
 *   @MONDAI no=1 type=read instr="___のことばの読み方として最もよいものを選びなさい。" instr_si="Sinhala instruction"
 *   Q: 友達{ともだち}との[[約束]]{やくそく}を守{まも}ってください。
 *   QSI: Sinhala meaning of the sentence
 *   1: やくそく | si: Sinhala meaning of choice 1
 *   2: やくぞく | si: ...
 *   3: よくそく
 *   4: よくぞく
 *   ANS: 1
 *   EXP: Sinhala explanation shown after answering
 *   (blank line between questions)
 *
 *   漢字{かんじ}  = furigana (hiragana shown above the kanji when the toggle is ON)
 *   [[word]]      = underlined target word
 *   type          = read | write | fill | meaning | usage | grammar | order
 *                   (read: furigana on the underlined word is always hidden;  write: furigana on the choices is always hidden)
 *   section       = vocab | grammar | reading | listening        free = 1 (everybody) / 0 (needs upgrade)
 *   Several @PAPER blocks can be in one file. Importing a code that already exists REPLACES that paper.
 * ------------------------------------------------------------------------------------------------------------------------------------------------
 */

require_once __DIR__ . '/config.php';

$PP_IMPORT_KEY = 'CHANGE-ME-TO-A-LONG-RANDOM-SECRET';      // <<<<<< CHANGE THIS

header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Cache-Control: no-store');

function ih(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

/** markup -> plain text (no furigana, no [[ ]]) */
function ip_plain(string $s): string
{
    $s = preg_replace('/\{[^}]*\}/u', '', $s);
    return trim(str_replace(['[[', ']]'], '', $s));
}

function ip_hira(string $s): string
{
    return preg_replace_callback('/[\x{30A1}-\x{30F6}]/u', function ($m) {
        $b  = $m[0];
        $cp = ((ord($b[0]) & 0x0F) << 12) | ((ord($b[1]) & 0x3F) << 6) | (ord($b[2]) & 0x3F);
        return html_entity_decode('&#' . ($cp - 0x60) . ';', ENT_QUOTES, 'UTF-8');
    }, $s);
}

function ip_norm(string $s): string
{
    return preg_replace('/[\s～〜~…\.．・、。，,？?！!\/／（）()［］\[\]「」『』―\-－:：]+/u', '', ip_hira($s));
}

/** key=value pairs ( value may be "quoted text" ) */
function ip_attrs(string $s): array
{
    $out = [];
    preg_match_all('/(\w+)=("([^"]*)"|\S+)/u', $s, $m, PREG_SET_ORDER);
    foreach ($m as $x) { $out[$x[1]] = isset($x[3]) && $x[3] !== '' ? $x[3] : trim($x[2], '"'); }
    return $out;
}

/** parse the whole text -> [papers, errors] */
function ip_parse(string $text): array
{
    $papers = []; $errors = [];
    $P = null; $M = ['no' => 1, 'type' => 'fill', 'instr' => '', 'instr_si' => '']; $Q = null; $qno = 0;

    $flush = function () use (&$P, &$Q, &$errors) {
        if (!$Q) { return; }
        $where = 'Q' . $Q['q_no'] . ' (' . mb_substr(ip_plain($Q['text']), 0, 18) . '…)';
        if ($Q['text'] === '')                         { $errors[] = "$where: Q: line is empty"; }
        elseif (count(array_filter($Q['c'], 'strlen')) !== 4) { $errors[] = "$where: needs exactly 4 choices (1: .. 4:)"; }
        elseif ($Q['ans'] < 1 || $Q['ans'] > 4)        { $errors[] = "$where: ANS must be 1-4"; }
        elseif ($P)                                    { $P['qs'][] = $Q; }
        $Q = null;
    };
    $closePaper = function () use (&$P, &$papers, $flush) {
        $flush();
        if ($P) { $papers[] = $P; }
        $P = null;
    };

    foreach (preg_split('/\R/u', $text) as $ln => $line) {
        $line = rtrim($line);
        if ($line === '' || strpos($line, '#') === 0) { continue; }

        if (strpos($line, '@PAPER') === 0) {
            $closePaper();
            $a = ip_attrs(substr($line, 6));
            $lvl = strtoupper($a['level'] ?? '');
            if (!in_array($lvl, ['N5', 'N4', 'N3', 'N2', 'N1'], true) || empty($a['code']) || empty($a['title'])) {
                $errors[] = 'Line ' . ($ln + 1) . ': @PAPER needs level=, code= and title=';
                continue;
            }
            $P = ['level' => $lvl, 'code' => $a['code'], 'title' => $a['title'], 'section' => $a['section'] ?? 'vocab',
                  'minutes' => max(1, (int) ($a['minutes'] ?? 20)), 'free' => (int) ($a['free'] ?? 0) ? 1 : 0, 'qs' => []];
            $qno = 0; $M = ['no' => 1, 'type' => 'fill', 'instr' => '', 'instr_si' => '']; $Q = null;
            continue;
        }
        if (!$P) { $errors[] = 'Line ' . ($ln + 1) . ': text before the first @PAPER line'; continue; }

        if (strpos($line, '@MONDAI') === 0) {
            $flush();
            $a = ip_attrs(substr($line, 7));
            $M = ['no' => max(1, (int) ($a['no'] ?? 1)), 'type' => $a['type'] ?? 'fill', 'instr' => $a['instr'] ?? '', 'instr_si' => $a['instr_si'] ?? ''];
            continue;
        }
        if (strpos($line, 'Q:') === 0) {
            $flush();
            $qno++;
            $Q = ['q_no' => $qno, 'm' => $M, 'text' => trim(substr($line, 2)), 'si' => '', 'c' => ['', '', '', ''], 'csi' => ['', '', '', ''], 'ans' => 0, 'exp' => ''];
            continue;
        }
        if (!$Q) { $errors[] = 'Line ' . ($ln + 1) . ': unexpected line (no Q: before it)'; continue; }

        if (strpos($line, 'QSI:') === 0) { $Q['si'] = trim(substr($line, 4)); continue; }
        if (strpos($line, 'ANS:') === 0) { $Q['ans'] = (int) trim(substr($line, 4)); continue; }
        if (strpos($line, 'EXP:') === 0) { $Q['exp'] = trim(substr($line, 4)); continue; }
        if (preg_match('/^([1-4]):\s*(.*)$/u', $line, $m)) {
            $parts = preg_split('/\s\|\ssi:\s?/u', $m[2], 2);
            $Q['c'][(int) $m[1] - 1]   = trim($parts[0]);
            $Q['csi'][(int) $m[1] - 1] = trim($parts[1] ?? '');
            continue;
        }
        $errors[] = 'Line ' . ($ln + 1) . ': not understood -> ' . mb_substr($line, 0, 40);
    }
    $closePaper();
    return [$papers, $errors];
}

/* ---------------------------------------------------------------- request ---------------------------------------------------------------- */
$msg = ''; $err = ''; $papers = []; $errors = []; $missing = []; $saved = [];
$text = (string) ($_POST['text'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($PP_IMPORT_KEY, (string) ($_POST['key'] ?? '')) || $PP_IMPORT_KEY === 'CHANGE-ME-TO-A-LONG-RANDOM-SECRET') {
        http_response_code(403);
        $err = 'Wrong key (and remember to change $PP_IMPORT_KEY inside this file).';
    } else {
        if (!empty($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
            $text = (string) file_get_contents($_FILES['file']['tmp_name']);
        }
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);             // strip BOM
        [$papers, $errors] = ip_parse($text);
        $pdo = getDBConnection();

        /* ---- dictionary check: target word + the correct answer of each question ---- */
        $seen = [];
        $st = $pdo->prepare('SELECT 1 FROM dict_words WHERE kanji_norm LIKE :a OR reading_norm LIKE :a LIMIT 1');
        foreach ($papers as $P) {
            foreach ($P['qs'] as $Q) {
                if ($Q['m']['type'] === 'grammar' || $Q['m']['type'] === 'order') { continue; }
                $cands = [];
                if (preg_match_all('/\[\[(.+?)\]\]/u', $Q['text'], $mm)) { foreach ($mm[1] as $t) { $cands[] = ip_plain($t); } }
                if ($Q['m']['type'] !== 'read' && $Q['ans'] >= 1) { $cands[] = ip_plain($Q['c'][$Q['ans'] - 1]); }
                foreach ($cands as $w) {
                    $n = ip_norm($w);
                    if ($n === '' || isset($seen[$n]) || mb_strlen($n) > 12) { continue; }
                    $seen[$n] = true;
                    $st->execute(['a' => '%' . addcslashes($n, '\\%_') . '%']);
                    if (!$st->fetchColumn()) { $missing[] = $w . '   (' . $P['level'] . ' · ' . $P['code'] . ' · Q' . $Q['q_no'] . ')'; }
                }
            }
        }

        /* ---- save ---- */
        if (!empty($_POST['save']) && !$errors && $papers) {
            try {
                $pdo->beginTransaction();
                foreach ($papers as $P) {
                    $q = $pdo->prepare('SELECT id FROM past_papers WHERE code = ?'); $q->execute([$P['code']]);
                    $pid = (int) $q->fetchColumn();
                    if ($pid) {
                        $pdo->prepare('UPDATE past_papers SET level=?, title=?, section=?, minutes=?, is_free=? WHERE id=?')
                            ->execute([$P['level'], $P['title'], $P['section'], $P['minutes'], $P['free'], $pid]);
                        $pdo->prepare('DELETE FROM past_questions WHERE paper_id = ?')->execute([$pid]);          // replace
                    } else {
                        $pdo->prepare('INSERT INTO past_papers (level, code, title, section, minutes, is_free) VALUES (?,?,?,?,?,?)')
                            ->execute([$P['level'], $P['code'], $P['title'], $P['section'], $P['minutes'], $P['free']]);
                        $pid = (int) $pdo->lastInsertId();
                    }
                    $ins = $pdo->prepare('INSERT INTO past_questions (paper_id, mondai_no, mondai_type, instr_jp, instr_si, q_no, text_jp, text_si, choices_jp, choices_si, answer, explain_si, sort_order)
                                          VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
                    foreach ($P['qs'] as $i => $Q) {
                        $ins->execute([$pid, $Q['m']['no'], $Q['m']['type'], $Q['m']['instr'], $Q['m']['instr_si'], $Q['q_no'], $Q['text'], $Q['si'],
                                       json_encode($Q['c'], JSON_UNESCAPED_UNICODE), json_encode($Q['csi'], JSON_UNESCAPED_UNICODE), $Q['ans'], $Q['exp'], $i + 1]);
                    }
                    $saved[] = $P['code'] . ' (' . count($P['qs']) . ' questions)';
                }
                $pdo->commit();
                $msg = 'Saved: ' . implode(', ', $saved);
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                error_log('[past_import] ' . $e->getMessage());
                $err = 'Database error: ' . $e->getMessage();
            }
        } elseif (!empty($_POST['save']) && $errors) {
            $err = 'Nothing saved: fix the errors first.';
        }
    }
}
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>Past paper import</title>
<style>
 body{font-family:system-ui,'Noto Sans JP','Noto Sans Sinhala',sans-serif;background:#e6e9f2;color:#2d3250;margin:0;padding:18px}
 .w{max-width:900px;margin:0 auto}.c{background:#e6e9f2;border-radius:20px;box-shadow:8px 8px 18px #c3c9dc,-8px -8px 18px #fff;padding:20px;margin-bottom:18px}
 textarea,input[type=password],input[type=file]{width:100%;box-sizing:border-box;border:0;border-radius:14px;padding:12px;background:#e6e9f2;box-shadow:inset 4px 4px 9px #c3c9dc,inset -4px -4px 9px #fff;font:inherit;color:inherit}
 textarea{min-height:300px;font-family:ui-monospace,Menlo,Consolas,'Noto Sans JP',monospace;font-size:.88rem}
 button{border:0;border-radius:999px;padding:13px 26px;font-weight:800;color:#fff;background:linear-gradient(135deg,#4f46e5,#7c6cf5);cursor:pointer}
 .ok{background:#d1fae5;color:#065f46;padding:12px 16px;border-radius:12px}.er{background:#fee2e2;color:#991b1b;padding:12px 16px;border-radius:12px}
 table{width:100%;border-collapse:collapse;font-size:.88rem}td,th{padding:6px 8px;border-bottom:1px solid #c3c9dc;text-align:left;vertical-align:top}
 label{display:block;margin:12px 0 6px;font-weight:700}
</style></head><body><div class="w">
<div class="c"><h2 style="margin-top:0">📥 Past paper import</h2>
<?php if ($msg): ?><p class="ok"><?php echo ih($msg); ?></p><?php endif; ?>
<?php if ($err): ?><p class="er"><?php echo ih($err); ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
 <label>Paper text (format is documented at the top of this file)</label>
 <textarea name="text" spellcheck="false"><?php echo ih($text); ?></textarea>
 <label>…or choose a .txt file (UTF-8)</label><input type="file" name="file" accept=".txt,text/plain">
 <label>Import key</label><input type="password" name="key" autocomplete="off">
 <label style="font-weight:600"><input type="checkbox" name="save" value="1"> Save to the database (leave unticked = preview only)</label>
 <p><button type="submit">Preview / Import</button></p>
</form></div>

<?php if ($errors): ?><div class="c"><h3 style="margin-top:0">⚠️ Errors</h3><ul><?php foreach ($errors as $e): ?><li><?php echo ih($e); ?></li><?php endforeach; ?></ul></div><?php endif; ?>

<?php foreach ($papers as $P): ?>
<div class="c"><h3 style="margin-top:0"><?php echo ih($P['level'] . ' · ' . $P['code'] . ' · ' . $P['title']); ?></h3>
 <p><?php echo count($P['qs']); ?> questions · <?php echo (int) $P['minutes']; ?> min · <?php echo $P['free'] ? 'FREE' : 'locked (upgrade)'; ?> · section: <?php echo ih($P['section']); ?></p>
 <table><tr><th>#</th><th>M</th><th>type</th><th>question</th><th>ans</th></tr>
 <?php foreach ($P['qs'] as $Q): ?>
  <tr><td><?php echo (int) $Q['q_no']; ?></td><td><?php echo (int) $Q['m']['no']; ?></td><td><?php echo ih($Q['m']['type']); ?></td>
      <td><?php echo ih(ip_plain($Q['text'])); ?><br><small><?php echo ih(implode(' / ', array_map('ip_plain', $Q['c']))); ?></small></td><td><?php echo (int) $Q['ans']; ?></td></tr>
 <?php endforeach; ?></table></div>
<?php endforeach; ?>

<?php if ($papers): ?>
<div class="c"><h3 style="margin-top:0">📖 Words missing from the dictionary (<?php echo count($missing); ?>)</h3>
<?php if ($missing): ?><p>Add these to <code>dict_words</code> (N3 etc.). One word per line:</p>
 <textarea readonly style="min-height:160px"><?php echo ih(implode("\n", $missing)); ?></textarea>
<?php else: ?><p class="ok">All target words already exist in the dictionary ✅</p><?php endif; ?>
<p style="font-size:.8rem;color:#7a8099">The check looks at the underlined [[target]] words and at the correct answers of vocabulary questions (grammar questions are skipped). It is a quick helper, not a full word-splitter.</p></div>
<?php endif; ?>
</div></body></html>
