<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);
set_exception_handler(function ($e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
});
try {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    if (!$data) {
        throw new Exception('Invalid JSON request body.');
    }
    define('SHOP_BOOTSTRAP', true);
    // Root public_html folder එකේ තියෙන config.php සහ includes call කිරීම
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../includes/shop-data.php';
    if (!defined('STRIPE_SECRET_KEY') || empty(STRIPE_SECRET_KEY)) {
        throw new Exception('Stripe Secret Key is not configured in config.php.');
    }

    // --- Shipping config (server is the source of truth — never trust a
    // fee amount sent by the client) --------------------------------------
    // NOTE: const cannot be declared inside a try/function block in PHP —
    // must use define() here instead, or it's a fatal parse error.
    if (!defined('ALLOWED_COUNTRIES_LIST')) {
        define('ALLOWED_COUNTRIES_LIST', ['Japan', 'Sri Lanka']);
    }
    if (!defined('SRI_LANKA_SHIPPING_JPY')) {
        define('SRI_LANKA_SHIPPING_JPY', 1900);
    }

    $bookId   = $data['book_id'] ?? '';
    $quantity = max(1, min(20, (int)($data['quantity'] ?? 1)));

    if (!isset($books[$bookId])) {
        throw new Exception('Invalid book selection.');
    }
    $book = $books[$bookId];

    // --- Effective price (server is the source of truth) -----------------
    // If the book has a discount_price_cents set (and it's actually lower
    // than the normal price), THAT is what gets charged — never the
    // original price_cents, and never anything sent by the client.
    $hasDiscount = !empty($book['discount_price_cents'])
        && (int)$book['discount_price_cents'] < (int)$book['price_cents'];
    $unitAmount = $hasDiscount
        ? (int)$book['discount_price_cents']
        : (int)$book['price_cents'];

    // --- Validate shipping details (mirrors the "required" fields on the
    // frontend form so the check can't be bypassed by calling this
    // endpoint directly) ----------------------------------------------------
    $requiredFields = ['full_name', 'phone', 'email', 'address_line', 'city', 'postal_code'];
    foreach ($requiredFields as $field) {
        if (empty(trim((string)($data[$field] ?? '')))) {
            throw new Exception('Missing required field: ' . $field);
        }
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address.');
    }

    $country = $data['country'] ?? '';
    if (!in_array($country, ALLOWED_COUNTRIES_LIST, true)) {
        throw new Exception('Invalid shipping country.');
    }

    // Vendor Composer Autoload Path එක
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    } else {
        throw new Exception('Stripe SDK vendor directory not found.');
    }
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

    $currency = $shopCurrency ?? 'usd';

    $lineItems = [[
        'price_data' => [
            'currency' => $currency,
            'product_data' => [
                'name' => $book['title']['en'] ?? 'Japanese Learning Book',
            ],
            'unit_amount' => $unitAmount,
        ],
        'quantity' => $quantity,
    ]];

    // Server-computed shipping fee — the amount is never read from $data.
    if ($country === 'Sri Lanka') {
        $lineItems[] = [
            'price_data' => [
                'currency' => $currency,
                'product_data' => [
                    'name' => 'Shipping to Sri Lanka',
                ],
                'unit_amount' => SRI_LANKA_SHIPPING_JPY,
            ],
            'quantity' => 1,
        ];
    }

    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $lineItems,
        'mode' => 'payment',
        'customer_email' => $data['email'] ?? null,
        'metadata' => [
            'book_id' => $bookId,
            'full_name' => $data['full_name'] ?? '',
            'phone' => $data['phone'] ?? '',
            'address_line' => $data['address_line'] ?? '',
            'city' => $data['city'] ?? '',
            'postal_code' => $data['postal_code'] ?? '',
            'country' => $country,
            'shipping_fee_jpy' => $country === 'Sri Lanka' ? SRI_LANKA_SHIPPING_JPY : 0,
            'unit_amount_charged' => $unitAmount,
            'original_price_cents' => (int)$book['price_cents'],
            'discount_applied' => $hasDiscount ? 'yes' : 'no',
        ],
        'success_url' => 'https://learnjapanese.jp/success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'https://learnjapanese.jp/book.php?id=' . urlencode($bookId),
    ]);
    ob_end_clean();
    echo json_encode(['url' => $session->url]);
    exit;
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}