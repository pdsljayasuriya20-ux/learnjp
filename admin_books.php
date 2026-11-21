<?php
// ---------------------------------------------------------------------
// DEBUG: true karala white screen eke error eka balanna.
// Hari giyata passe false karanna.
// ---------------------------------------------------------------------
define('DEBUG_MODE', true);
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

require_once 'config.php';
requireLogin();

$db = getDBConnection();

// Where uploaded book images are stored
define('BOOK_UPLOAD_DIR', __DIR__ . '/img/books/');
define('BOOK_UPLOAD_URL', '/img/books/');
if (!is_dir(BOOK_UPLOAD_DIR)) {
    @mkdir(BOOK_UPLOAD_DIR, 0755, true);
}

$errors  = [];
$success = '';

/**
 * Detect the MIME type of an uploaded file without relying on a
 * single PHP extension (fileinfo may be disabled on shared hosting).
 */
function detect_mime($path)
{
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $m = $finfo->file($path);
        if ($m) return $m;
    }
    if (function_exists('mime_content_type')) {
        $m = @mime_content_type($path);
        if ($m) return $m;
    }
    $info = @getimagesize($path);
    if ($info && !empty($info['mime'])) {
        return $info['mime'];
    }
    return '';
}

/**
 * Handle a single uploaded image field.
 * Returns the public URL path of the saved file, or null if nothing
 * was uploaded. Problems are added to $errors (by reference).
 */
function handle_book_image_upload($fieldName, array &$errors)
{
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Image upload failed ($fieldName). Error code: " . $_FILES[$fieldName]['error']
                  . " (1/2 = file too big for server limits).";
        return null;
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime    = detect_mime($_FILES[$fieldName]['tmp_name']);

    if (!isset($allowed[$mime])) {
        $errors[] = "Invalid image type for $fieldName (only JPG, PNG, WEBP).";
        return null;
    }

    // 5MB limit
    if ($_FILES[$fieldName]['size'] > 5 * 1024 * 1024) {
        $errors[] = "Image too large for $fieldName (max 5MB).";
        return null;
    }

    if (!is_dir(BOOK_UPLOAD_DIR) || !is_writable(BOOK_UPLOAD_DIR)) {
        $errors[] = 'Upload folder (/img/books/) is missing or not writable. Set permission 755 or 775.';
        return null;
    }

    $ext      = $allowed[$mime];
    $filename = 'book_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destPath = BOOK_UPLOAD_DIR . $filename;

    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $destPath)) {
        return BOOK_UPLOAD_URL . $filename;
    }

    $errors[] = "Could not save image $fieldName.";
    return null;
}

function slugify($text)
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '_', $text);
    $text = trim($text, '_');
    return $text !== '' ? $text : ('book_' . time());
}

// ---------------------------------------------------------------------
// Handle form submissions
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // --- DELETE ---
    if ($action === 'delete') {
        try {
            $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
            if ($id > 0) {
                $stmt = $db->prepare("DELETE FROM books WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $success = 'Book deleted.';
            }
        } catch (Throwable $e) {
            error_log('admin_books delete: ' . $e->getMessage());
            $errors[] = 'Delete failed: ' . $e->getMessage();
        }
    }

    // --- ADD / EDIT ---
    if ($action === 'save') {
        $id            = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
        $level         = trim(isset($_POST['level']) ? $_POST['level'] : '');
        $price         = (int)(isset($_POST['price_cents']) ? $_POST['price_cents'] : 0);
        $discountRaw   = trim(isset($_POST['discount_price_cents']) ? $_POST['discount_price_cents'] : '');
        $discountPrice = ($discountRaw === '') ? null : (int)$discountRaw;

        $titleEn = trim(isset($_POST['title_en']) ? $_POST['title_en'] : '');
        $titleJa = trim(isset($_POST['title_ja']) ? $_POST['title_ja'] : '');
        $titleSi = trim(isset($_POST['title_si']) ? $_POST['title_si'] : '');

        $taglineEn = trim(isset($_POST['tagline_en']) ? $_POST['tagline_en'] : '');
        $taglineJa = trim(isset($_POST['tagline_ja']) ? $_POST['tagline_ja'] : '');
        $taglineSi = trim(isset($_POST['tagline_si']) ? $_POST['tagline_si'] : '');

        $descEn = trim(isset($_POST['description_en']) ? $_POST['description_en'] : '');
        $descJa = trim(isset($_POST['description_ja']) ? $_POST['description_ja'] : '');
        $descSi = trim(isset($_POST['description_si']) ? $_POST['description_si'] : '');

        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $isActive   = isset($_POST['is_active']) ? 1 : 0;

        if ($titleEn === '' || $level === '' || $price <= 0) {
            $errors[] = 'Title (English), Level and Price are required.';
        }
        if (mb_strlen($level) > 50) {
            $errors[] = 'Level is too long (max 50 characters). Use something short like N5, N4 or N5-N1.';
        }
        if ($discountPrice !== null && $discountPrice >= $price) {
            $errors[] = 'Discount price must be lower than the normal price.';
        }

        // Uploads (optional — only replace if a new file was chosen)
        $coverUrl = handle_book_image_upload('cover_image', $errors);
        $galleryUrls = [];
        for ($i = 1; $i <= 5; $i++) {
            $galleryUrls[$i] = handle_book_image_upload('gallery_image_' . $i, $errors);
        }

        if (empty($errors)) {
            try {
                if ($id > 0) {
                    // ----- EDIT existing book -----
                    $fields = [
                        'level'                 => $level,
                        'price_cents'           => $price,
                        'discount_price_cents'  => $discountPrice,
                        'title_en'              => $titleEn,
                        'title_ja'              => $titleJa,
                        'title_si'              => $titleSi,
                        'tagline_en'            => $taglineEn,
                        'tagline_ja'            => $taglineJa,
                        'tagline_si'            => $taglineSi,
                        'description_en'        => $descEn,
                        'description_ja'        => $descJa,
                        'description_si'        => $descSi,
                        'is_featured'           => $isFeatured,
                        'is_active'             => $isActive,
                    ];

                    if ($coverUrl) {
                        $fields['cover_image'] = $coverUrl;
                    }
                    for ($i = 1; $i <= 5; $i++) {
                        if ($galleryUrls[$i]) {
                            $fields['gallery_image_' . $i] = $galleryUrls[$i];
                        }
                    }

                    $setParts = [];
                    foreach (array_keys($fields) as $col) {
                        $setParts[] = "$col = :$col";
                    }
                    $setSql = implode(', ', $setParts);
                    $fields['id'] = $id;

                    $stmt = $db->prepare("UPDATE books SET $setSql WHERE id = :id");
                    $stmt->execute($fields);
                    $success = 'Book updated.';
                } else {
                    // ----- ADD new book -----
                    $slugInput = isset($_POST['slug']) ? trim($_POST['slug']) : '';
                    $slugBase  = slugify($slugInput !== '' ? $slugInput : $titleEn);
                    $slug      = $slugBase;
                    $n         = 1;
                    // ensure slug is unique
                    while (true) {
                        $check = $db->prepare("SELECT COUNT(*) FROM books WHERE slug = :slug");
                        $check->execute([':slug' => $slug]);
                        if ($check->fetchColumn() == 0) break;
                        $slug = $slugBase . '_' . (++$n);
                    }

                    $stmt = $db->prepare("
                        INSERT INTO books
                            (slug, level, price_cents, discount_price_cents,
                             title_en, title_ja, title_si,
                             tagline_en, tagline_ja, tagline_si,
                             description_en, description_ja, description_si,
                             cover_image,
                             gallery_image_1, gallery_image_2, gallery_image_3, gallery_image_4, gallery_image_5,
                             is_featured, is_active)
                        VALUES
                            (:slug, :level, :price_cents, :discount_price_cents,
                             :title_en, :title_ja, :title_si,
                             :tagline_en, :tagline_ja, :tagline_si,
                             :description_en, :description_ja, :description_si,
                             :cover_image,
                             :g1, :g2, :g3, :g4, :g5,
                             :is_featured, :is_active)
                    ");
                    $stmt->execute([
                        ':slug'                  => $slug,
                        ':level'                 => $level,
                        ':price_cents'           => $price,
                        ':discount_price_cents'  => $discountPrice,
                        ':title_en'              => $titleEn,
                        ':title_ja'              => $titleJa,
                        ':title_si'              => $titleSi,
                        ':tagline_en'            => $taglineEn,
                        ':tagline_ja'            => $taglineJa,
                        ':tagline_si'            => $taglineSi,
                        ':description_en'        => $descEn,
                        ':description_ja'        => $descJa,
                        ':description_si'        => $descSi,
                        ':cover_image'           => $coverUrl,
                        ':g1'                    => $galleryUrls[1],
                        ':g2'                    => $galleryUrls[2],
                        ':g3'                    => $galleryUrls[3],
                        ':g4'                    => $galleryUrls[4],
                        ':g5'                    => $galleryUrls[5],
                        ':is_featured'           => $isFeatured,
                        ':is_active'             => $isActive,
                    ]);
                    $success = 'Book added.';
                }
            } catch (Throwable $e) {
                error_log('admin_books save: ' . $e->getMessage());
                $errors[] = 'Save failed: ' . $e->getMessage();
            }
        }
    }
}

// ---------------------------------------------------------------------
// Load data for the page
// ---------------------------------------------------------------------
$allBooks = [];
$editBook = null;
try {
    $allBooks = $db->query("SELECT * FROM books ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

    // If editing, load that one book for the form
    $editId = 0;
    if (isset($_GET['edit'])) {
        $editId = (int)$_GET['edit'];
    } elseif (!empty($errors) && !empty($_POST['id'])) {
        // keep the edit form open if saving an edit failed
        $editId = (int)$_POST['id'];
    }
    if ($editId > 0) {
        $stmt = $db->prepare("SELECT * FROM books WHERE id = :id");
        $stmt->execute([':id' => $editId]);
        $editBook = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$editBook) $editBook = null;
    }
} catch (Throwable $e) {
    error_log('admin_books load: ' . $e->getMessage());
    $errors[] = 'Could not load books: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Management - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Poppins',sans-serif; background:#0a0a0a; color:#fff; }
        .container { max-width: 1100px; margin: 0 auto; padding: 30px 20px 60px; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; flex-wrap:wrap; gap:10px; }
        .page-title { font-size:1.8rem; font-weight:800; background:linear-gradient(45deg,#ff006e,#8338ec); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        .back-link { color:#3a86ff; text-decoration:none; font-weight:600; font-size:0.9rem; }
        .card { background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:15px; padding:25px; margin-bottom:25px; }
        .card h3 { color:#ff006e; margin-bottom:18px; font-size:1.15rem; }
        .alert { padding:12px 16px; border-radius:10px; margin-bottom:18px; font-size:0.9rem; }
        .alert-success { background:rgba(6,255,165,0.15); border:1px solid #06ffa5; color:#06ffa5; }
        .alert-error { background:rgba(255,0,110,0.15); border:1px solid #ff006e; color:#ff9db8; }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .form-grid.full { grid-template-columns: 1fr; }
        @media (max-width:700px){ .form-grid{ grid-template-columns:1fr; } }
        label { display:block; font-size:0.8rem; opacity:0.75; margin-bottom:6px; font-weight:600; }
        input[type=text], input[type=number], textarea, select {
            width:100%; padding:10px 12px; background:rgba(255,255,255,0.08);
            border:1px solid rgba(255,255,255,0.15); border-radius:8px; color:#fff; font-family:inherit; font-size:0.9rem;
        }
        textarea { min-height:70px; resize:vertical; }
        input[type=file] { width:100%; color:#ccc; font-size:0.85rem; }
        .field { margin-bottom:14px; }
        .checkbox-row { display:flex; align-items:center; gap:8px; margin-top:6px; }
        .checkbox-row input { width:auto; }
        .thumb-preview { width:70px; height:90px; object-fit:cover; border-radius:6px; margin-top:6px; border:1px solid rgba(255,255,255,0.15); }
        .btn { padding:11px 22px; border:none; border-radius:50px; font-weight:700; cursor:pointer; font-size:0.9rem; }
        .btn-primary { background:linear-gradient(45deg,#ff006e,#8338ec); color:#fff; }
        .btn-danger { background:#ff006e; color:#fff; padding:6px 14px; font-size:0.78rem; border-radius:8px; }
        .btn-secondary { background:rgba(255,255,255,0.1); color:#fff; padding:6px 14px; font-size:0.78rem; border-radius:8px; text-decoration:none; display:inline-block; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:12px; text-align:left; font-size:0.85rem; border-top:1px solid rgba(255,255,255,0.1); }
        th { color:#ff006e; background:rgba(255,0,110,0.08); }
        .book-thumb { width:44px; height:58px; object-fit:cover; border-radius:5px; }
        .price-cell .old { text-decoration:line-through; color:#888; font-size:0.78rem; display:block; }
        .price-cell .new { color:#06ffa5; font-weight:700; }
        .badge { padding:2px 8px; border-radius:20px; font-size:0.72rem; font-weight:700; }
        .badge-active { background:rgba(6,255,165,0.15); color:#06ffa5; }
        .badge-inactive { background:rgba(255,255,255,0.1); color:#999; }
        .actions-cell { display:flex; gap:6px; flex-wrap:wrap; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1 class="page-title">📚 Book Management</h1>
        <a href="admin_dashboard.php" class="back-link">&larr; Back to Dashboard</a>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?php echo htmlspecialchars($err); ?></div><?php endforeach; ?>

    <!-- ADD / EDIT FORM -->
    <div class="card">
        <h3><?php echo $editBook ? '✏️ Edit Book' : '➕ Add New Book'; ?></h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save">
            <?php if ($editBook): ?>
                <input type="hidden" name="id" value="<?php echo (int)$editBook['id']; ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div class="field">
                    <label>Level (e.g. N5, N4, N3, N5-N1)</label>
                    <input type="text" name="level" required maxlength="50" value="<?php echo htmlspecialchars(isset($editBook['level']) ? $editBook['level'] : ''); ?>">
                </div>
                <?php if (!$editBook): ?>
                <div class="field">
                    <label>URL Slug (optional — auto-generated if left blank)</label>
                    <input type="text" name="slug" placeholder="e.g. n5_vocab_book">
                </div>
                <?php endif; ?>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label>Normal Price (¥)</label>
                    <input type="number" name="price_cents" min="1" required value="<?php echo htmlspecialchars(isset($editBook['price_cents']) ? $editBook['price_cents'] : ''); ?>">
                </div>
                <div class="field">
                    <label>Discount Price (¥) — leave blank for no discount</label>
                    <input type="number" name="discount_price_cents" min="1" value="<?php echo htmlspecialchars(isset($editBook['discount_price_cents']) ? $editBook['discount_price_cents'] : ''); ?>">
                </div>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label>Title (English)</label>
                    <input type="text" name="title_en" required value="<?php echo htmlspecialchars(isset($editBook['title_en']) ? $editBook['title_en'] : ''); ?>">
                </div>
                <div class="field">
                    <label>Title (日本語)</label>
                    <input type="text" name="title_ja" value="<?php echo htmlspecialchars(isset($editBook['title_ja']) ? $editBook['title_ja'] : ''); ?>">
                </div>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label>Title (සිංහල)</label>
                    <input type="text" name="title_si" value="<?php echo htmlspecialchars(isset($editBook['title_si']) ? $editBook['title_si'] : ''); ?>">
                </div>
                <div></div>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label>Tagline (English)</label>
                    <textarea name="tagline_en"><?php echo htmlspecialchars(isset($editBook['tagline_en']) ? $editBook['tagline_en'] : ''); ?></textarea>
                </div>
                <div class="field">
                    <label>Tagline (日本語)</label>
                    <textarea name="tagline_ja"><?php echo htmlspecialchars(isset($editBook['tagline_ja']) ? $editBook['tagline_ja'] : ''); ?></textarea>
                </div>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label>Tagline (සිංහල)</label>
                    <textarea name="tagline_si"><?php echo htmlspecialchars(isset($editBook['tagline_si']) ? $editBook['tagline_si'] : ''); ?></textarea>
                </div>
                <div></div>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label>Full Description (English) — shown on book detail page</label>
                    <textarea name="description_en"><?php echo htmlspecialchars(isset($editBook['description_en']) ? $editBook['description_en'] : ''); ?></textarea>
                </div>
                <div class="field">
                    <label>Full Description (日本語)</label>
                    <textarea name="description_ja"><?php echo htmlspecialchars(isset($editBook['description_ja']) ? $editBook['description_ja'] : ''); ?></textarea>
                </div>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label>Full Description (සිංහල)</label>
                    <textarea name="description_si"><?php echo htmlspecialchars(isset($editBook['description_si']) ? $editBook['description_si'] : ''); ?></textarea>
                </div>
                <div></div>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label>Cover / Thumbnail Image <?php echo $editBook ? '(leave blank to keep current)' : ''; ?></label>
                    <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp">
                    <?php if (!empty($editBook['cover_image'])): ?>
                        <img src="<?php echo htmlspecialchars($editBook['cover_image']); ?>" class="thumb-preview">
                    <?php endif; ?>
                </div>
                <div class="field">
                    <label class="checkbox-row" style="margin-top:28px;">
                        <input type="checkbox" name="is_featured" <?php echo !empty($editBook['is_featured']) ? 'checked' : ''; ?>>
                        Show "LATEST" badge on books page
                    </label>
                    <label class="checkbox-row">
                        <input type="checkbox" name="is_active" <?php echo (!$editBook || !empty($editBook['is_active'])) ? 'checked' : ''; ?>>
                        Active (visible on the website)
                    </label>
                </div>
            </div>

            <div class="field">
                <label>Gallery Images (up to 5 — shown on the book's detail page)</label>
                <div class="form-grid">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <div class="field">
                            <label style="font-weight:400;">Image <?php echo $i; ?> <?php echo $editBook ? '(leave blank to keep current)' : ''; ?></label>
                            <input type="file" name="gallery_image_<?php echo $i; ?>" accept="image/jpeg,image/png,image/webp">
                            <?php if (!empty($editBook['gallery_image_' . $i])): ?>
                                <img src="<?php echo htmlspecialchars($editBook['gallery_image_' . $i]); ?>" class="thumb-preview">
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <div style="margin-top:10px; display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary"><?php echo $editBook ? 'Update Book' : 'Add Book'; ?></button>
                <?php if ($editBook): ?>
                    <a href="admin_books.php" class="btn-secondary" style="padding:11px 22px;">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- BOOK LIST -->
    <div class="card">
        <h3>📖 All Books</h3>
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Cover</th>
                    <th>Title</th>
                    <th>Level</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($allBooks)): ?>
                    <tr><td colspan="6" style="text-align:center; opacity:0.6; padding:30px;">No books yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($allBooks as $b): ?>
                    <tr>
                        <td>
                            <img src="<?php echo htmlspecialchars($b['cover_image'] ? $b['cover_image'] : 'https://placehold.co/60x80'); ?>" class="book-thumb">
                        </td>
                        <td><?php echo htmlspecialchars($b['title_en']); ?></td>
                        <td><?php echo htmlspecialchars($b['level']); ?></td>
                        <td class="price-cell">
                            <?php if (!empty($b['discount_price_cents'])): ?>
                                <span class="old">¥<?php echo number_format($b['price_cents']); ?></span>
                                <span class="new">¥<?php echo number_format($b['discount_price_cents']); ?></span>
                            <?php else: ?>
                                ¥<?php echo number_format($b['price_cents']); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?php echo $b['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $b['is_active'] ? 'Active' : 'Hidden'; ?>
                            </span>
                        </td>
                        <td class="actions-cell">
                            <a href="admin_books.php?edit=<?php echo (int)$b['id']; ?>" class="btn-secondary">Edit</a>
                            <form method="POST" onsubmit="return confirm('Delete this book permanently?');" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
                                <button type="submit" class="btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</body>
</html>