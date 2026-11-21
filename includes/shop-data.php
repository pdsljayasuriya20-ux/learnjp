<?php
/**
 * shop-data.php
 *
 * Loads all books from the `books` table and builds the exact same
 * $books array shape that books.php / book_detail.php already use,
 * so you don't need to touch the rest of the site.
 *
 * $books[$slug] = [
 *     'id'                   => 'n5_n1_vocab',
 *     'level'                => 'N5 - N1',
 *     'price_cents'          => 2980,
 *     'discount_price_cents' => 1980,   // null if no discount
 *     'title'    => ['en' => ..., 'ja' => ..., 'si' => ...],
 *     'tagline'  => ['en' => ..., 'ja' => ..., 'si' => ...],
 *     'description' => ['en' => ..., 'ja' => ..., 'si' => ...],
 *     'cover'    => '/img/books/xxxx.jpg',
 *     'gallery'  => ['/img/books/a.jpg', '/img/books/b.jpg', ...], // up to 5
 * ]
 */

require_once __DIR__ . '/../config.php'; // config.php is in the site root, shop-data.php is in includes/

$shopCurrency = 'JPY';

/**
 * Format a price for display.
 * NOTE: values are stored as plain whole numbers (e.g. 2980 => ¥2,980),
 * matching how this site already displayed prices like '¥2,980'.
 */
function format_price($amount, $currency = 'JPY')
{
    if ($amount === null) {
        return '';
    }
    $symbols = ['JPY' => '¥', 'USD' => '$', 'LKR' => 'Rs. '];
    $symbol  = $symbols[$currency] ?? '';
    return $symbol . number_format((float)$amount);
}

$books = [];

try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT * FROM books WHERE is_active = 1 ORDER BY is_featured DESC, created_at DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $gallery = [];
        for ($i = 1; $i <= 5; $i++) {
            $col = 'gallery_image_' . $i;
            if (!empty($row[$col])) {
                $gallery[] = $row[$col];
            }
        }

        $books[$row['slug']] = [
            'id'                   => $row['slug'],
            'level'                => $row['level'],
            'price_cents'          => (int)$row['price_cents'],
            'discount_price_cents' => $row['discount_price_cents'] !== null ? (int)$row['discount_price_cents'] : null,
            'title' => [
                'en' => $row['title_en'],
                'ja' => $row['title_ja'],
                'si' => $row['title_si'],
            ],
            'tagline' => [
                'en' => $row['tagline_en'],
                'ja' => $row['tagline_ja'],
                'si' => $row['tagline_si'],
            ],
            'description' => [
                'en' => $row['description_en'],
                'ja' => $row['description_ja'],
                'si' => $row['description_si'],
            ],
            'cover'       => $row['cover_image'] ?: '',
            'gallery'     => $gallery,
            'is_featured' => (bool)$row['is_featured'],
        ];
    }
} catch (PDOException $e) {
    error_log('shop-data.php DB error: ' . $e->getMessage());
    $books = [];
}