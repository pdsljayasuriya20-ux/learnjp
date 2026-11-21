<?php
/**
 * admin-missed.php - "Missed words": searches on the dictionary that found nothing (table dict_missed)
 * Put it in public_html/ next to admin-users.php (uses the same admin login).
 *
 * For every missed search you can: add the word to the dictionary right here, mark it ignored, reopen it, delete it.
 * Needs: migration 003_dict_features.sql, includes/dict_extra.php
 */
require_once __DIR__ . '/includes/progress.php';
require_once __DIR__ . '/includes/dict_extra.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

if (empty($_SESSION['adm_ok'])) {          // not logged in -> admin login page
    header('Location: admin-users.php');
    exit;
}

function ms_h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$db   = la_db();
$csrf = la_csrf();

$qs   = (string)($_SERVER['QUERY_STRING'] ?? '');
$self = 'admin-missed.php' . ($qs !== '' ? '?' . $qs : '');

/* ------------------------------------------------------------ actions (POST) */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $back = (string)($_POST['back'] ?? '');
    if (!preg_match('/^admin-missed\.php(\?[A-Za-z0-9_\-\.=&%+]*)?$/', $back)) $back = 'admin-missed.php';

    if (!la_csrf_ok($_POST['csrf'] ?? null)) {
        $_SESSION['ms_flash'] = ['err', 'Security token වැරදියි. පිටුව refresh කරන්න.'];
        header('Location: ' . $back); exit;
    }

    $action = (string)($_POST['action'] ?? '');
    $id     = (int)($_POST['id'] ?? 0);

    try {
        if ($action === 'status') {
            $to = in_array($_POST['to'] ?? '', ['new', 'added', 'ignored'], true) ? $_POST['to'] : 'new';
            $db->prepare('UPDATE dict_missed SET status = ? WHERE id = ?')->execute([$to, $id]);
            $_SESSION['ms_flash'] = ['ok', 'යාවත්කාලීන කළා.'];

        } elseif ($action === 'delete') {
            $db->prepare('DELETE FROM dict_missed WHERE id = ?')->execute([$id]);
            $_SESSION['ms_flash'] = ['ok', 'මකා දැම්මා.'];

        } elseif ($action === 'note') {
            $db->prepare('UPDATE dict_missed SET note = ? WHERE id = ?')->execute([mb_substr(trim((string)($_POST['note'] ?? '')), 0, 250), $id]);
            $_SESSION['ms_flash'] = ['ok', 'සටහන සුරැකුවා.'];

        } elseif ($action === 'add_word') {
            [$ok, $msg] = dict_add_word(
                $db,
                (int)($_POST['level'] ?? 0),
                (string)($_POST['reading'] ?? ''),
                (string)($_POST['kanji'] ?? ''),
                (string)($_POST['meaning_si'] ?? ''),
                (string)($_POST['romaji'] ?? ''),
                (string)($_POST['singlish'] ?? ''),
                (string)($_POST['pos'] ?? '')
            );
            if ($ok && $id > 0) {
                $db->prepare("UPDATE dict_missed SET status = 'added' WHERE id = ?")->execute([$id]);
            }
            $_SESSION['ms_flash'] = [$ok ? 'ok' : 'err', $msg];
        }
    } catch (Throwable $e) {
        error_log('[admin-missed] ' . $e->getMessage());
        $_SESSION['ms_flash'] = ['err', 'දෝෂයක්: 003_dict_features.sql run කළාද?'];
    }
    header('Location: ' . $back);
    exit;
}

$flash = $_SESSION['ms_flash'] ?? null;
unset($_SESSION['ms_flash']);

/* ------------------------------------------------------------ data */
$flt  = in_array($_GET['f'] ?? '', ['new', 'added', 'ignored', 'all'], true) ? $_GET['f'] : 'new';
$q    = trim((string)($_GET['q'] ?? ''));
$per  = 30;
$page = max(1, (int)($_GET['page'] ?? 1));
$err  = null;
$rows = [];
$cnt  = ['new' => 0, 'added' => 0, 'ignored' => 0];
$count = 0;
$pages = 1;

try {
    foreach ($db->query('SELECT status, COUNT(*) c FROM dict_missed GROUP BY status')->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $cnt[$r['status']] = (int)$r['c'];
    }
    $where = []; $par = [];
    if ($flt !== 'all') { $where[] = 'status = ?'; $par[] = $flt; }
    if ($q !== '')      { $where[] = 'q_raw LIKE ?'; $par[] = '%' . addcslashes($q, '\\%_') . '%'; }
    $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $c = $db->prepare('SELECT COUNT(*) FROM dict_missed ' . $w);
    $c->execute($par);
    $count = (int)$c->fetchColumn();
    $pages = max(1, (int)ceil($count / $per));
    $page  = min($page, $pages);

    $st = $db->prepare('SELECT * FROM dict_missed ' . $w . ' ORDER BY hits DESC, last_seen DESC LIMIT ' . (int)$per . ' OFFSET ' . (int)(($page - 1) * $per));
    $st->execute($par);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $err = 'dict_missed table එක හොයාගන්න බැහැ - 003_dict_features.sql run කළාද බලන්න.';
}

function ms_url(array $over = []): string {
    $p = array_merge($_GET, $over);
    foreach ($p as $k => $v) { if ($v === '' || $v === null) unset($p[$k]); }
    return 'admin-missed.php' . ($p ? '?' . http_build_query($p) : '');
}
function ms_ago(?string $dt): string {
    $t = $dt ? strtotime($dt) : 0;
    if (!$t) return '–';
    $d = time() - $t;
    if ($d < 90) return 'දැන්ම';
    if ($d < 3600) return intdiv($d, 60) . ' මිනි. පෙර';
    if ($d < 86400) return intdiv($d, 3600) . ' පැය පෙර';
    return intdiv($d, 86400) . ' දින පෙර';
}
?>
<!doctype html>
<html lang="si">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Missed words · Admin</title>
<style>
  *{box-sizing:border-box}
  body{font-family:system-ui,-apple-system,"Noto Sans Sinhala","Noto Sans JP",sans-serif;background:#f1f3f9;color:#1f2340;margin:0}
  a{color:#4f46e5;text-decoration:none}
  .top{background:#fff;box-shadow:0 2px 8px rgba(30,35,80,.08);position:sticky;top:0;z-index:5}
  .top-in{max-width:980px;margin:0 auto;padding:12px 18px;display:flex;align-items:center;gap:14px;flex-wrap:wrap}
  .brand{font-weight:800;font-size:1.05rem}.brand span{color:#4f46e5}
  .nav{display:flex;gap:6px;flex-wrap:wrap}
  .nav a{padding:8px 16px;border-radius:999px;font-weight:700;font-size:.88rem;color:#5b6180}
  .nav a.on{background:#4f46e5;color:#fff}
  .spacer{flex:1}
  .wrap{max-width:980px;margin:0 auto;padding:18px}
  input,select,button,textarea{font:inherit}
  input[type=text],input[type=number],select{padding:9px 12px;border:1px solid #c9cde0;border-radius:10px;background:#fff;min-width:0;width:100%}
  button,.btn{background:#4f46e5;color:#fff;border:0;border-radius:10px;padding:9px 16px;font-weight:700;cursor:pointer;display:inline-block}
  button.soft,.btn.soft{background:#eef0ff;color:#4f46e5}
  button.danger{background:#dc2626;padding:6px 11px;font-size:.8rem}
  button.gray{background:#8a8fab;padding:6px 11px;font-size:.8rem}
  .flash{padding:12px 14px;border-radius:12px;margin-bottom:14px;font-weight:600}
  .flash.ok{background:#d1fae5;color:#065f46}.flash.err{background:#fee2e2;color:#991b1b}
  .card{background:#fff;border-radius:16px;padding:16px;margin-bottom:14px;box-shadow:0 2px 8px rgba(30,35,80,.08)}
  .row{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start}
  .q{font-weight:800;font-size:1.25rem;word-break:break-word}
  .hits{background:#eef0ff;color:#4f46e5;border-radius:999px;padding:3px 12px;font-weight:800;font-size:.85rem;white-space:nowrap}
  .meta{font-size:.8rem;color:#5b6180;margin-top:4px}
  .tag{display:inline-block;background:#eef0ff;color:#4f46e5;border-radius:999px;padding:1px 9px;font-size:.72rem;font-weight:800;margin-right:4px}
  .tag.g{background:#d1fae5;color:#065f46}.tag.r{background:#e5e7eb;color:#4b5563}
  .none{color:#8a8fab;font-size:.84rem}
  .filters{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0 14px}
  .filters a{padding:6px 14px;border-radius:999px;background:#fff;font-weight:700;font-size:.82rem;color:#5b6180;box-shadow:0 1px 4px rgba(30,35,80,.1)}
  .filters a.on{background:#4f46e5;color:#fff}
  .bar{display:flex;gap:8px}
  .pager{display:flex;gap:8px;justify-content:center;align-items:center;margin:18px 0;font-size:.86rem}
  details summary{cursor:pointer;font-size:.84rem;font-weight:800;color:#4f46e5;margin-top:12px}
  .g{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px}
  .g .full{grid-column:1/-1}
  label{font-size:.74rem;font-weight:800;color:#5b6180;display:block;margin-bottom:3px}
  .acts{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;align-items:center}
  .acts form{display:inline}
</style>
</head>
<body>
<div class="top"><div class="top-in">
  <div class="brand">Learn<span>Japanese</span> · Admin</div>
  <div class="nav">
    <a href="admin-users.php?tab=overview">📊 Overview</a>
    <a href="admin-users.php?tab=users">👥 Users</a>
    <a href="admin-users.php?tab=plans">🎟 Plans</a>
    <a href="admin-users.php?tab=traffic">📈 Visitors</a>
    <a href="admin-missed.php" class="on">🔎 Missed words</a>
  </div>
  <div class="spacer"></div>
  <a href="dictionary.php" class="none">Dictionary →</a>
</div></div>

<div class="wrap">
<?php if ($flash): ?><div class="flash <?php echo ms_h($flash[0]); ?>"><?php echo ms_h($flash[1]); ?></div><?php endif; ?>
<?php if ($err): ?><div class="flash err"><?php echo ms_h($err); ?></div><?php endif; ?>

<p class="none" style="margin-top:0">Dictionary එකේ සෙවීමක් ප්‍රතිඵල 0ක් ආවම මෙතනට වැටෙනවා. වැඩිපුරම සෙවූ ඒවා උඩින්. වචනය එකතු කළාම <b>Added</b> වෙනවා.
 (Added වුණාට පස්සේත් ඒ සෙවීම ආයෙත් අසාර්ථක වුණොත් නැවත <b>New</b> වෙනවා.)</p>

<form class="bar" method="get">
  <input type="hidden" name="f" value="<?php echo ms_h($flt); ?>">
  <input type="text" name="q" value="<?php echo ms_h($q); ?>" placeholder="සොයන්න (සෙවූ වචනය)">
  <button type="submit">හොයන්න</button>
</form>

<div class="filters">
  <?php foreach (['new' => 'New (' . $cnt['new'] . ')', 'added' => 'Added (' . $cnt['added'] . ')', 'ignored' => 'Ignored (' . $cnt['ignored'] . ')', 'all' => 'සියල්ල'] as $k => $label): ?>
    <a href="<?php echo ms_h(ms_url(['f' => $k, 'page' => null])); ?>" class="<?php echo $flt === $k ? 'on' : ''; ?>"><?php echo ms_h($label); ?></a>
  <?php endforeach; ?>
</div>
<div class="none" style="margin-bottom:10px">මුළු <?php echo $count; ?> ක් · පිටුව <?php echo $page; ?>/<?php echo $pages; ?></div>

<?php if (!$rows && !$err): ?><div class="card none">මෙතන කිසිවක් නැහැ. 🎉</div><?php endif; ?>

<?php foreach ($rows as $r):
    $id   = (int)$r['id'];
    $mode = (string)$r['mode'];
    $raw  = (string)$r['q_raw'];
    $isKana = (bool)preg_match('/^[\x{3040}-\x{30FF}ー]+$/u', $raw);
    $pre = ['reading' => '', 'kanji' => '', 'romaji' => '', 'meaning' => '', 'singlish' => ''];
    if ($mode === 'ja')  { if ($isKana) $pre['reading'] = $raw; else $pre['kanji'] = $raw; }
    elseif ($mode === 'si')  { $pre['meaning'] = $raw; }
    else { $pre['romaji'] = $raw; $pre['singlish'] = $raw; }
    $lv = $r['level_filter'] !== null ? (int)$r['level_filter'] : 5;
?>
  <div class="card">
    <div class="row">
      <div>
        <div class="q"><?php echo ms_h($raw); ?></div>
        <div class="meta">
          <span class="tag"><?php echo ms_h(['ja' => 'ජපන්', 'si' => 'සිංහල', 'lat' => 'romaji/Singlish'][$mode] ?? $mode); ?></span>
          <?php if ($r['level_filter'] !== null): ?><span class="tag">N<?php echo (int)$r['level_filter']; ?> filter</span><?php endif; ?>
          <span class="tag <?php echo $r['status'] === 'added' ? 'g' : ($r['status'] === 'ignored' ? 'r' : ''); ?>"><?php echo ms_h($r['status']); ?></span>
          අන්තිමට <?php echo ms_h(ms_ago((string)$r['last_seen'])); ?> · පළමුව <?php echo ms_h(substr((string)$r['first_seen'], 0, 10)); ?>
          <?php if ($r['note'] !== ''): ?><br>📝 <?php echo ms_h($r['note']); ?><?php endif; ?>
        </div>
      </div>
      <span class="hits"><?php echo (int)$r['hits']; ?> × සෙවුවා</span>
    </div>

    <?php if ($r['status'] !== 'added'): ?>
    <details>
      <summary>➕ වචනය Dictionary එකට එකතු කරන්න</summary>
      <form method="post">
        <input type="hidden" name="csrf" value="<?php echo ms_h($csrf); ?>">
        <input type="hidden" name="back" value="<?php echo ms_h($self); ?>">
        <input type="hidden" name="action" value="add_word">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="g">
          <div><label>Level</label>
            <select name="level"><?php foreach ([5, 4, 3, 2, 1] as $n): ?><option value="<?php echo $n; ?>" <?php echo $n === $lv ? 'selected' : ''; ?>>N<?php echo $n; ?></option><?php endforeach; ?></select></div>
          <div><label>වර්ගය (conjugation table එකට)</label>
            <select name="pos"><option value="">– නැහැ / නොදනිමි –</option>
              <option value="v1">Verb – godan (v1)</option><option value="v2">Verb – ichidan (v2)</option><option value="v3">Verb – irregular (v3)</option>
              <option value="adj_i">い-adjective</option><option value="adj_na">な-adjective</option></select></div>
          <div><label>Reading (hiragana / katakana) *</label><input type="text" name="reading" maxlength="100" value="<?php echo ms_h($pre['reading']); ?>" required></div>
          <div><label>Kanji (හිස් විය හැක)</label><input type="text" name="kanji" maxlength="100" value="<?php echo ms_h($pre['kanji']); ?>"></div>
          <div><label>Romaji *</label><input type="text" name="romaji" maxlength="200" value="<?php echo ms_h($pre['romaji']); ?>" required></div>
          <div><label>Singlish (සිංහල තේරුම English අකුරින් - search එකට)</label><input type="text" name="singlish" maxlength="200" value="<?php echo ms_h($pre['singlish']); ?>" placeholder="උදා: pirimi"></div>
          <div class="full"><label>සිංහල තේරුම *</label><input type="text" name="meaning_si" maxlength="300" value="<?php echo ms_h($pre['meaning']); ?>" required></div>
        </div>
        <div class="acts"><button type="submit">Dictionary එකට එකතු කරන්න</button></div>
      </form>
    </details>
    <?php endif; ?>

    <div class="acts">
      <?php if ($r['status'] !== 'ignored'): ?>
        <form method="post"><input type="hidden" name="csrf" value="<?php echo ms_h($csrf); ?>"><input type="hidden" name="back" value="<?php echo ms_h($self); ?>">
          <input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?php echo $id; ?>"><input type="hidden" name="to" value="ignored">
          <button type="submit" class="gray">Ignore</button></form>
      <?php endif; ?>
      <?php if ($r['status'] !== 'new'): ?>
        <form method="post"><input type="hidden" name="csrf" value="<?php echo ms_h($csrf); ?>"><input type="hidden" name="back" value="<?php echo ms_h($self); ?>">
          <input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?php echo $id; ?>"><input type="hidden" name="to" value="new">
          <button type="submit" class="soft">Reopen</button></form>
      <?php endif; ?>
      <form method="post" onsubmit="return confirm('මකන්නද?');"><input type="hidden" name="csrf" value="<?php echo ms_h($csrf); ?>"><input type="hidden" name="back" value="<?php echo ms_h($self); ?>">
        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $id; ?>">
        <button type="submit" class="danger">මකන්න</button></form>
      <form method="post" style="display:flex;gap:6px;flex:1;min-width:200px"><input type="hidden" name="csrf" value="<?php echo ms_h($csrf); ?>"><input type="hidden" name="back" value="<?php echo ms_h($self); ?>">
        <input type="hidden" name="action" value="note"><input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="text" name="note" maxlength="250" value="<?php echo ms_h($r['note']); ?>" placeholder="සටහනක්">
        <button type="submit" class="soft">සුරකින්න</button></form>
    </div>
  </div>
<?php endforeach; ?>

<?php if ($pages > 1): ?>
  <div class="pager">
    <?php if ($page > 1): ?><a class="btn soft" href="<?php echo ms_h(ms_url(['page' => $page - 1])); ?>">← කලින්</a><?php endif; ?>
    <span>පිටුව <?php echo $page; ?> / <?php echo $pages; ?></span>
    <?php if ($page < $pages): ?><a class="btn soft" href="<?php echo ms_h(ms_url(['page' => $page + 1])); ?>">ඊළඟ →</a><?php endif; ?>
  </div>
<?php endif; ?>
</div>
</body>
</html>
