<?php require_once __DIR__ . '/includes/header.php'; ?>

<style>
        /* =========================================================
           BOOKS LIST — NEUMORPHISM
           ========================================================= */
        .book-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.75rem;
            padding: 6px 4px;           /* room for shadows */
        }
        @media (max-width: 900px) { .book-grid { grid-template-columns: repeat(2, 1fr); gap: 1.4rem; } }
        @media (max-width: 560px) { .book-grid { grid-template-columns: 1fr; gap: 1.5rem; } }

        @keyframes nmCardIn {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .book-card {
            background: var(--nm-bg);
            border-radius: var(--nm-radius);
            box-shadow: var(--nm-raised);
            padding: 14px;
            overflow: hidden;
            display: flex; flex-direction: column;
            text-decoration: none; color: var(--nm-text);
            transition: transform .25s ease, box-shadow .25s ease;
            animation: nmCardIn .5s ease both;
            animation-delay: calc(var(--i, 0) * 70ms);
        }
        .book-card:hover  { transform: translateY(-5px); color: var(--nm-text); }
        .book-card:active { box-shadow: var(--nm-inset); transform: scale(.985); }

        .book-cover-wrap {
            aspect-ratio: 3 / 4;
            background: var(--nm-bg);
            border-radius: var(--nm-radius-sm);
            box-shadow: var(--nm-inset);
            padding: 10px;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; position: relative;
        }
        .book-cover-wrap img {
            width: 100%; height: 100%; object-fit: cover;
            border-radius: 10px; display: block;
            box-shadow: 3px 3px 10px rgba(45, 50, 80, .25);
        }

        .level-badge, .latest-badge, .discount-ribbon {
            position: absolute; z-index: 2; color: #fff;
            font-weight: 800; padding: 4px 11px; border-radius: 999px;
            box-shadow: 2px 2px 7px rgba(0, 0, 0, .22);
        }
        .level-badge {
            top: 14px; left: 14px; font-size: .78rem;
            background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2));
        }
        .latest-badge {
            top: 14px; right: 14px; font-size: .68rem;
            text-transform: uppercase; letter-spacing: .5px;
            background: linear-gradient(135deg, #ef4444, #f97316);
        }
        .discount-ribbon {
            bottom: 14px; left: 14px; font-size: .74rem;
            background: linear-gradient(135deg, #10b981, #34d399);
        }

        .book-card-body { padding: 14px 6px 6px; display: flex; flex-direction: column; gap: .45rem; flex: 1; }
        .book-card-title   { font-weight: 800; font-size: 1rem; line-height: 1.35; }
        .book-card-tagline { color: var(--nm-muted); font-size: .83rem; line-height: 1.55; flex: 1; }
        .book-card-footer  { display: flex; align-items: center; justify-content: space-between; margin-top: .6rem; gap: 10px; flex-wrap: wrap; }

        .book-price-wrap { display: flex; flex-direction: column; line-height: 1.2; }
        .book-price      { font-weight: 800; color: var(--nm-brand); font-size: 1.08rem; }
        .book-price-old  { font-size: .8rem; color: var(--nm-muted); text-decoration: line-through; }

        .btn-view {
            background: var(--nm-bg); color: var(--nm-brand);
            font-weight: 700; font-size: .8rem; padding: 9px 15px;
            border-radius: 999px; text-decoration: none; white-space: nowrap;
            box-shadow: var(--nm-raised-sm);
            transition: box-shadow .2s ease, background .2s ease, color .2s ease;
        }
        .book-card:hover .btn-view {
            background: linear-gradient(135deg, var(--nm-brand), var(--nm-brand-2));
            color: #fff;
        }
        .book-card:active .btn-view { box-shadow: var(--nm-inset-sm); }

        .books-empty {
            grid-column: 1 / -1; text-align: center; color: var(--nm-muted);
            padding: 28px 18px; border-radius: var(--nm-radius);
            box-shadow: var(--nm-inset); margin: 0;
        }

        @media (max-width: 560px) {
            .book-card { padding: 12px; border-radius: 20px; }
            .book-cover-wrap { max-height: 420px; }
        }
</style>

        <!-- Hero Banner -->
        <div class="hero-banner mb-4">
            <h1 class="fw-extrabold fs-2 mb-2"><?php echo htmlspecialchars($st['books_hero_title']); ?></h1>
            <p class="mb-0 opacity-90 fs-6"><?php echo htmlspecialchars($st['books_hero_sub']); ?></p>
        </div>

        <div class="book-grid mb-4">
            <?php $cardIdx = 0; foreach ($books as $id => $book):
                $hasDiscount = !empty($book['discount_price_cents'])
                    && $book['discount_price_cents'] < $book['price_cents'];
                $discountPct = $hasDiscount
                    ? round((1 - $book['discount_price_cents'] / $book['price_cents']) * 100)
                    : 0;
                $coverSrc = $book['cover'] ?: 'https://placehold.co/450x600/eef2ff/4f46e5?text=' . urlencode($book['level']);
            ?>
                <a href="book_detail.php?id=<?php echo urlencode($id); ?>" class="book-card" style="--i:<?php echo $cardIdx++; ?>">
                    <div class="book-cover-wrap">
                        <span class="level-badge"><?php echo htmlspecialchars($book['level']); ?></span>
                        <?php if (!empty($book['is_featured'])): ?>
                            <span class="latest-badge"><i class="fa-solid fa-fire me-1"></i>LATEST</span>
                        <?php endif; ?>
                        <?php if ($hasDiscount): ?>
                            <span class="discount-ribbon">-<?php echo $discountPct; ?>%</span>
                        <?php endif; ?>
                        <img src="<?php echo htmlspecialchars($coverSrc); ?>"
                             alt="<?php echo htmlspecialchars($book['title'][$currentLang]); ?> — <?php echo htmlspecialchars($st['book_cover']); ?>"
                             loading="lazy"
                             onerror="this.onerror=null;this.src='https://placehold.co/450x600/eef2ff/4f46e5?text=<?php echo urlencode($book['level']); ?>';">
                    </div>
                    <div class="book-card-body">
                        <div class="book-card-title"><?php echo htmlspecialchars($book['title'][$currentLang]); ?></div>
                        <div class="book-card-tagline"><?php echo htmlspecialchars($book['tagline'][$currentLang]); ?></div>
                        <div class="book-card-footer">
                            <div class="book-price-wrap">
                                <?php if ($hasDiscount): ?>
                                    <span class="book-price-old"><?php echo format_price($book['price_cents'], $shopCurrency); ?></span>
                                    <span class="book-price"><?php echo format_price($book['discount_price_cents'], $shopCurrency); ?></span>
                                <?php else: ?>
                                    <span class="book-price"><?php echo format_price($book['price_cents'], $shopCurrency); ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="btn-view"><?php echo htmlspecialchars($st['view_details']); ?> <i class="fa-solid fa-arrow-right ms-1"></i></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>

            <?php if (empty($books)): ?>
                <p class="books-empty">
                    No books yet. Add one from the admin panel.
                </p>
            <?php endif; ?>
        </div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>