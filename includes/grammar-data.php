<?php
/**
 * includes/grammar-data.php
 * -----------------------------------------------------------------
 * Data-access layer used by w-grammar.php / w-grammar_card.php /
 * includes/w-grammar-render.php.
 *
 * Reuses the PDO connection + DB constants already defined in your
 * config.php (getDBConnection(), DB_HOST, DB_NAME, ...). It does NOT
 * store any credentials of its own.
 *
 * ASSUMPTIONS ABOUT grammar_formation_rows / grammar_examples
 * -----------------------------------------------------------------
 * Only the grammar_cards table structure was confirmed from your SQL
 * dump. The two child tables are assumed to look like this (adjust
 * the SELECT column lists below if your real columns differ):
 *
 *   grammar_formation_rows: id, card_id, label, example_jp, sort_order
 *   grammar_examples:       id, card_id, jp, romaji, meaning_si, sort_order
 *
 * If your actual column names differ, tell me the real CREATE TABLE
 * for those two tables and I'll adjust the queries below.
 * -----------------------------------------------------------------
 */

// Reuse the app's existing DB connection helper — don't re-declare
// DB constants or open a second connection.
if (!function_exists('getDBConnection')) {
    $__gc_config_candidates = [
        __DIR__ . '/../config.php',
        __DIR__ . '/../../config.php',
        __DIR__ . '/config.php',
    ];
    foreach ($__gc_config_candidates as $__gc_cfg) {
        if (is_file($__gc_cfg)) { require_once $__gc_cfg; break; }
    }
}

if (!function_exists('getDBConnection')) {
    // Fail loudly and clearly rather than a confusing fatal further down.
    throw new RuntimeException('grammar-data.php: could not find config.php / getDBConnection(). Adjust $__gc_config_candidates above to the real path.');
}

/** Order levels N5 -> N1 (edit if your site uses a different level set/order). */
const GRAMMAR_LEVEL_ORDER = ['N5', 'N4', 'N3', 'N2', 'N1'];

/* ------------------------------------------------------------------
 *  Small text helpers
 * ------------------------------------------------------------------ */

/** Allow a couple of safe inline tags (used for emphasis in remember_si etc.), strip everything else. */
function grammar_clean_html(?string $s): string
{
    return strip_tags((string)$s, '<b><strong><i><em>');
}

/** Fully plain text (for meta descriptions / list "meaning" column). */
function grammar_plain(?string $s): string
{
    return trim(strip_tags((string)$s));
}

/** A TEXT column holding one line per bullet -> array of lines (used for usage_si, tips_si, remember_si). */
function grammar_lines(?string $s): array
{
    $s = trim((string)$s);
    if ($s === '') return [];
    $lines = preg_split('/\r\n|\r|\n/', $s);
    return array_values(array_filter(array_map('trim', $lines), fn($l) => $l !== ''));
}

/* ------------------------------------------------------------------
 *  Levels
 * ------------------------------------------------------------------ */

/** @return array<int, array{level:string, total:int}> */
function grammar_levels(): array
{
    $pdo  = getDBConnection();
    $stmt = $pdo->query("SELECT level, COUNT(*) AS total FROM grammar_cards GROUP BY level");
    $rows = $stmt->fetchAll();

    $byLevel = [];
    foreach ($rows as $r) {
        $byLevel[$r['level']] = (int)$r['total'];
    }

    $ordered = [];
    // Known levels first, in the canonical N5..N1 order.
    foreach (GRAMMAR_LEVEL_ORDER as $lv) {
        if (isset($byLevel[$lv])) {
            $ordered[] = ['level' => $lv, 'total' => $byLevel[$lv]];
            unset($byLevel[$lv]);
        }
    }
    // Anything unexpected (e.g. a custom level code) goes after, alphabetically.
    ksort($byLevel);
    foreach ($byLevel as $lv => $total) {
        $ordered[] = ['level' => $lv, 'total' => $total];
    }

    return $ordered;
}

/* ------------------------------------------------------------------
 *  Card lists
 * ------------------------------------------------------------------ */

/** Lightweight row list for the table/list view. Ordered by sort_order, then card_no. */
function grammar_list_cards(string $level): array
{
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT slug, card_no, title_jp, formula, gloss_en, gloss_si
        FROM grammar_cards
        WHERE level = :level
        ORDER BY sort_order ASC, card_no ASC
    ");
    $stmt->execute([':level' => $level]);
    return $stmt->fetchAll();
}

/** Full card rows (with formation/examples/usage/tips/remember) for a whole level, same order as grammar_list_cards(). */
function grammar_get_cards_full(string $level): array
{
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT *
        FROM grammar_cards
        WHERE level = :level
        ORDER BY sort_order ASC, card_no ASC
    ");
    $stmt->execute([':level' => $level]);
    $rows = $stmt->fetchAll();

    return array_map(fn($row) => grammar_hydrate_card($row, $pdo), $rows);
}

/** One full card by slug, or null. */
function grammar_get_card_by_slug(string $slug): ?array
{
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM grammar_cards WHERE slug = :slug LIMIT 1");
    $stmt->execute([':slug' => $slug]);
    $row = $stmt->fetch();
    return $row ? grammar_hydrate_card($row, $pdo) : null;
}

/** Previous/next card (by slug + title_jp) within the same level, using grammar_list_cards() order. */
function grammar_neighbours(array $card): array
{
    $list = grammar_list_cards($card['level']);
    $pos  = null;
    foreach ($list as $i => $c) {
        if ($c['slug'] === $card['slug']) { $pos = $i; break; }
    }

    return [
        'prev' => ($pos !== null && $pos > 0) ? $list[$pos - 1] : null,
        'next' => ($pos !== null && $pos < count($list) - 1) ? $list[$pos + 1] : null,
    ];
}

/* ------------------------------------------------------------------
 *  Internal: attach formation rows + examples + split text fields
 * ------------------------------------------------------------------ */

function grammar_hydrate_card(array $row, PDO $pdo): array
{
    $cardId = (int)$row['id'];

    $row['formation'] = grammar_safe_query($pdo, "
        SELECT label, example_jp
        FROM grammar_formation_rows
        WHERE card_id = :id
        ORDER BY sort_order ASC, id ASC
    ", $cardId);

    $row['examples'] = grammar_safe_query($pdo, "
        SELECT jp, romaji, meaning_si
        FROM grammar_examples
        WHERE card_id = :id
        ORDER BY sort_order ASC, id ASC
    ", $cardId);

    $row['usage']    = grammar_lines($row['usage_si']    ?? '');
    $row['tips']     = grammar_lines($row['tips_si']     ?? '');
    $row['remember'] = grammar_lines($row['remember_si'] ?? '');

    return $row;
}

/**
 * Run a card_id-scoped query and return its rows, or an empty array if the
 * table doesn't exist yet (SQLSTATE 42S02) — so a card with no formation
 * rows / examples yet still renders instead of crashing the whole page.
 * Any other DB error still throws normally (so real bugs aren't hidden).
 */
function grammar_safe_query(PDO $pdo, string $sql, int $cardId): array
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $cardId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        if ($e->getCode() === '42S02') { // table doesn't exist
            return [];
        }
        throw $e;
    }
}
