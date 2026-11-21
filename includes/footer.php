<?php if (!empty($_SESSION['user_id'])) { require_once __DIR__ . '/progress.php'; la_progress_boot(); } ?>
</main>

    <?php
    // ---- WhatsApp contact (shown on phone, tablet AND desktop) ----
    $waNumberLocal = '09050295822';                 // as people write it in Japan
    $waNumberIntl  = '81' . substr($waNumberLocal, 1); // wa.me needs the international form without "+" -> 819050295822
    $waText        = 'Hello! මට learnjapanese.jp ගැන විමසීමක් තියෙනවා.';
    $waUrl         = 'https://wa.me/' . $waNumberIntl . '?text=' . rawurlencode($waText);
    $waShown       = '090-5029-5822';
    ?>

    <!-- WhatsApp question strip: visible on every device (not part of the desktop-only footer) -->
    <section class="wa-strip" aria-label="WhatsApp">
        <div class="container">
            <div class="wa-card">
                <span class="wa-ic" aria-hidden="true"><i class="fab fa-whatsapp"></i></span>
                <div class="wa-tx">
                    <div class="wa-t">ඕනෑම දෙයක් විමසන්න</div>
                    <div class="wa-s">WhatsApp: <strong><?php echo htmlspecialchars($waShown); ?></strong></div>
                </div>
                <a class="wa-btn" href="<?php echo htmlspecialchars($waUrl); ?>" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-whatsapp" aria-hidden="true"></i> WhatsApp කරන්න
                </a>
            </div>
        </div>
    </section>

    <!-- Footer (desktop only — hidden on phone/tablet by header.php CSS) -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-card">
                <div class="row g-4">
                    <div class="col-md-4">
                        <h5 class="footer-title"><i class="fas fa-graduation-cap" style="color:var(--nm-brand)" aria-hidden="true"></i> learnjapanese.jp</h5>
                        <p class="footer-text"><?php echo $t['footer_about']; ?></p>
                        <div class="social-icons">
                            <a href="#" class="social-icon" title="Facebook" aria-label="Facebook" target="_blank" rel="noopener noreferrer"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
                            <a href="#" class="social-icon" title="Twitter" aria-label="Twitter" target="_blank" rel="noopener noreferrer"><i class="fab fa-twitter" aria-hidden="true"></i></a>
                            <a href="#" class="social-icon" title="Instagram" aria-label="Instagram" target="_blank" rel="noopener noreferrer"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                            <a href="#" class="social-icon" title="YouTube" aria-label="YouTube" target="_blank" rel="noopener noreferrer"><i class="fab fa-youtube" aria-hidden="true"></i></a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h5 class="footer-title"><?php echo $t['quick_links']; ?></h5>
                        <ul class="footer-links">
                            <li><a href="index.php"><i class="fas fa-home" aria-hidden="true"></i> <?php echo $t['nav_home']; ?></a></li>
                            <li><a href="n5.php"><i class="fas fa-book" aria-hidden="true"></i> JLPT N5</a></li>
                            <li><a href="n4.php"><i class="fas fa-book" aria-hidden="true"></i> JLPT N4</a></li>
                            <li><a href="comingsoon.php"><i class="fas fa-book" aria-hidden="true"></i> JLPT N3</a></li>
                            <li><a href="vocabulary.php"><i class="fas fa-language" aria-hidden="true"></i> <?php echo $t['nav_vocab']; ?></a></li>
                            <li><a href="books.php"><i class="fas fa-book-open" aria-hidden="true"></i> <?php echo $t['books_title']; ?></a></li>
                            <li><a href="<?php echo htmlspecialchars($drivingExternalUrl); ?>" target="_blank" rel="noopener noreferrer"><i class="fas fa-car" aria-hidden="true"></i> <?php echo $t['nav_driving']; ?></a></li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h5 class="footer-title"><?php echo $t['contact_us']; ?></h5>
                        <ul class="footer-contact">
                            <li><i class="fas fa-envelope" aria-hidden="true"></i> mantech121@gmail.com</li>
                            <li><i class="fab fa-whatsapp" aria-hidden="true"></i> <a href="<?php echo htmlspecialchars($waUrl); ?>" target="_blank" rel="noopener noreferrer">WhatsApp <?php echo htmlspecialchars($waShown); ?></a></li>
                            <li><i class="fas fa-map-marker-alt" aria-hidden="true"></i> Tokyo, Japan</li>
                        </ul>
                        <div class="newsletter">
                            <h6 class="footer-title" style="font-size:0.95rem; margin-bottom:0.25rem;"><?php echo $t['newsletter']; ?></h6>
                            <form class="newsletter-form" onsubmit="handleNewsletter(event)">
                                <label for="newsletterEmail" class="visually-hidden"><?php echo $t['newsletter_ph']; ?></label>
                                <input id="newsletterEmail" type="email" placeholder="<?php echo htmlspecialchars($t['newsletter_ph']); ?>" required>
                                <button type="submit" aria-label="Subscribe"><i class="fas fa-paper-plane" aria-hidden="true"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="footer-bottom">
                    <p class="copyright">&copy; <?php echo date('Y'); ?> learnjapanese.jp — <?php echo $t['footer_about']; ?></p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Floating WhatsApp button: phone, tablet and desktop (sits above the mobile bottom bar) -->
    <a class="wa-float" href="<?php echo htmlspecialchars($waUrl); ?>" target="_blank" rel="noopener noreferrer"
       aria-label="WhatsApp <?php echo htmlspecialchars($waShown); ?>">
        <i class="fab fa-whatsapp" aria-hidden="true"></i>
        <span class="wa-float-txt">විමසන්න</span>
    </a>

    <style>
        /* ---- WhatsApp strip (same neumorphism look as the rest of the site) ---- */
        .wa-strip { padding: 8px 0 22px; }
        .wa-card {
            display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
            background: var(--nm-bg, #e6e9f2); border-radius: var(--nm-radius, 24px);
            box-shadow: var(--nm-raised, 8px 8px 18px #c3c9dc, -8px -8px 18px #fff);
            padding: 16px 18px; color: var(--nm-text, #2d3250);
        }
        .wa-ic {
            flex: 0 0 52px; height: 52px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 1.7rem; color: #fff; background: linear-gradient(135deg, #1fa855, #25d366);
            box-shadow: var(--nm-raised-sm, 5px 5px 11px #c3c9dc, -5px -5px 11px #fff);
        }
        .wa-tx { flex: 1 1 180px; min-width: 0; }
        .wa-t { font-weight: 800; font-size: 1rem; line-height: 1.3; }
        .wa-s { font-size: .86rem; color: var(--nm-muted, #7a8099); font-weight: 600; margin-top: 2px; }
        .wa-s strong { color: var(--nm-text, #2d3250); }
        .wa-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            background: linear-gradient(135deg, #1fa855, #25d366); color: #fff !important; text-decoration: none;
            font-weight: 800; font-size: .92rem; padding: 11px 20px; border-radius: 999px;
            box-shadow: var(--nm-raised-sm, 5px 5px 11px #c3c9dc, -5px -5px 11px #fff);
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .wa-btn:hover { transform: translateY(-2px); color: #fff; }
        .wa-btn:active { transform: scale(.97); }
        @media (max-width: 575.98px) {
            .wa-card { padding: 14px; }
            .wa-btn { width: 100%; }
        }
        /* mobile / tablet: leave room so the strip is not hidden behind the bottom app bar */
        @media (max-width: 991.98px) {
            .wa-strip { padding-bottom: calc(96px + env(safe-area-inset-bottom)); }
        }

        /* ---- floating button ---- */
        .wa-float {
            position: fixed; right: 18px; bottom: 22px; z-index: 1040;
            display: inline-flex; align-items: center; gap: 8px;
            background: linear-gradient(135deg, #1fa855, #25d366); color: #fff !important; text-decoration: none;
            border-radius: 999px; padding: 12px 18px; font-weight: 800; font-size: .9rem;
            box-shadow: 0 8px 20px rgba(31, 168, 85, .45);
            transition: transform .2s ease;
        }
        .wa-float i { font-size: 1.6rem; line-height: 1; }
        .wa-float:hover { transform: translateY(-3px); color: #fff; }
        @media (max-width: 991.98px) {
            .wa-float { bottom: calc(82px + env(safe-area-inset-bottom)); right: 14px; padding: 11px 14px; }
            .wa-float-txt { display: none; }   /* icon only on phones so it stays small */
        }
        @media print { .wa-float, .wa-strip { display: none !important; } }
    </style>

    <!-- Mobile / tablet app bottom bar (Home · Dictionary · Books · JLPT · Contact · Dashboard).
         Included here ONCE, so every page that uses footer.php gets it automatically. -->
    <?php require __DIR__ . '/bottom-nav.php'; ?>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
    <script>
        function handleNewsletter(event) {
            event.preventDefault();
            const email = event.target.querySelector('input[type="email"]').value;
            alert('✅ Thank you for subscribing! We will send updates to ' + email);
            event.target.reset();
        }

        // Book carousel horizontal scroll controls (desktop arrow buttons —
        // mobile users just swipe, scroll-snap handles the rest)
        function scrollBookCarousel(direction) {
            const track = document.getElementById('bookCarousel');
            if (!track) return;
            const cardWidth = track.querySelector('.book-carousel-card')?.offsetWidth || 170;
            track.scrollBy({ left: direction * (cardWidth + 18) * 2, behavior: 'smooth' });
        }
    </script>
</body>
</html>