<?php
/**
 * නෙත්සිත ජෝතිශ්‍ය සේවය - Cosmic තේමාව සහිත Footer ගොනුව
 * Requires: $app_settings array (Loaded from settings loader in header.php)
 * Note: The closing </body> and </html> tags should be at the absolute end of the main PHP file (e.g., contact.php), 
 * so they are REMOVED from this included file.
 */

// Settings array එක header.php වෙතින් ලැබෙන බවට සහතික වන්න.
// මෙය නොමැති නම්, default values භාවිතා කරයි.
$app_settings = $app_settings ?? []; 

// Setting values ආරක්ෂිතව ලබා ගැනීම සඳහා htmlspecialchars() භාවිතා කිරීම.

// Settings වෙතින් දත්ත ආරක්ෂිතව ලබා ගැනීම සහ Default values සැපයීම
// safe_output() වෙනුවට htmlspecialchars() භාවිතා කරයි
$site_title = htmlspecialchars($app_settings['site_title'] ?? 'නෙත්සිත ජෝතිශ්‍ය සේවය');
$contact_email = htmlspecialchars($app_settings['contact_email'] ?? 'info@netsita.lk');
$contact_phone = htmlspecialchars($app_settings['contact_phone'] ?? '071 239 1317');
$contact_address = htmlspecialchars($app_settings['contact_address'] ?? 'නො. 123, ප්‍රධාන පාර, කොළඹ'); // නව ලිපිනය key එක එකතු කරන ලදී

$whatsapp_number_clean = preg_replace('/[^0-9]/', '', $app_settings['social_whatsapp'] ?? '94712391317'); // 'contact_phone' වෙනුවට 'social_whatsapp' භාවිතා කරයි.

$current_year = date('Y');

// සමාජ මාධ්‍ය සබැඳි (Settings වලින් පැමිණිය යුතුය)
// safe_output() වෙනුවට htmlspecialchars() භාවිතා කරයි
$social_facebook = htmlspecialchars($app_settings['social_facebook'] ?? 'https://facebook.com');
$social_whatsapp = 'https://wa.me/' . $whatsapp_number_clean; // WhatsApp link එක සකස් කර ඇත
$social_instagram = htmlspecialchars($app_settings['social_instagram'] ?? 'https://instagram.com');
$social_youtube = htmlspecialchars($app_settings['social_youtube'] ?? 'https://youtube.com');

?>

<footer class="footer-section position-relative overflow-hidden">
    <div class="footer-stars">
        <div class="footer-star"></div>
        <div class="footer-star"></div>
        <div class="footer-star"></div>
        <div class="footer-star"></div>
        <div class="footer-star"></div>
        <div class="footer-star"></div>
        <div class="footer-star"></div>
        <div class="footer-star"></div>
    </div>
    
    <div class="footer-zodiac">
        <div class="zodiac-symbol">♈</div>
        <div class="zodiac-symbol">♉</div>
        <div class="zodiac-symbol">♊</div>
    </div>
    
    <div class="container position-relative" style="z-index: 10;">
        <div class="row g-4 py-5">
            <div class="col-lg-4 col-md-6">
                <div class="footer-widget">
                    <h5 class="footer-title mb-4">
                        <i class="fas fa-star text-warning me-2"></i><?php echo $site_title; ?>
                    </h5>
                    <p class="footer-text mb-4">
                        ශ්‍රී ලංකාවේ විශ්වාසදායක හා පළපුරුදු ජෝතිෂ සහ වාස්තු සේවාව. ඔබගේ ජීවිතයට නිවැරදි දිශාව සහ සාර්ථකත්වය ලබා දීම අපගේ අරමුණයි.
                    </p>
                    <div class="footer-social-links">
                        <a href="<?php echo $social_facebook; ?>" target="_blank" class="social-link facebook" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="<?php echo $social_whatsapp; ?>" target="_blank" class="social-link whatsapp" aria-label="WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="<?php echo $social_instagram; ?>" target="_blank" class="social-link instagram" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="<?php echo $social_youtube; ?>" target="_blank" class="social-link youtube" aria-label="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-6">
                <div class="footer-widget">
                    <h5 class="footer-title mb-4">
                        <i class="fas fa-link me-2"></i>ඉක්මන් සබැඳි
                    </h5>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="fas fa-chevron-right me-2"></i>මුල් පිටුව</a></li>
                        <li><a href="services.php"><i class="fas fa-chevron-right me-2"></i>සේවා</a></li>
                        <li><a href="about.php"><i class="fas fa-chevron-right me-2"></i>අප ගැන</a></li>
                        <li><a href="contact.php"><i class="fas fa-chevron-right me-2"></i>සම්බන්ධ වන්න</a></li>
                        <li><a href="privacy-policy.php"><i class="fas fa-chevron-right me-2"></i>රහස්‍යතා ප්‍රතිපත්තිය</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="footer-widget">
                    <h5 class="footer-title mb-4">
                        <i class="fas fa-th-large me-2"></i>අපගේ සේවා
                    </h5>
                    <ul class="footer-links">
                        <li><a href="services.php"><i class="fas fa-star me-2 text-warning"></i>ජෝතිෂ සේවා</a></li>
                        <li><a href="services.php"><i class="fas fa-home me-2 text-success"></i>වාස්තු සේවා</a></li>
                        <li><a href="services.php"><i class="fas fa-calendar me-2 text-info"></i>හොඳ වැඩ දවස්</a></li>
                        <li><a href="services.php"><i class="fas fa-users me-2 text-primary"></i>නම් යෝජනා</a></li>
                        <li><a href="services.php"><i class="fas fa-gem me-2 text-danger"></i>මැණික් උපදෙස්</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="footer-widget">
                    <h5 class="footer-title mb-4">
                        <i class="fas fa-phone-alt me-2"></i>සම්බන්ධ වන්න
                    </h5>
                    <ul class="footer-contact">
                        <li>
                            <i class="fas fa-phone text-success"></i>
                            <div>
                                <strong>දුරකථන:</strong><br>
                                <a href="tel:<?php echo $contact_phone; ?>"><?php echo $contact_phone; ?></a>
                            </div>
                        </li>
                        <li>
                            <i class="fas fa-envelope text-primary"></i>
                            <div>
                                <strong>විද්‍යුත් තැපෑල:</strong><br>
                                <a href="mailto:<?php echo $contact_email; ?>"><?php echo $contact_email; ?></a>
                            </div>
                        </li>
                        <li>
                            <i class="fas fa-map-marker-alt text-danger"></i>
                            <div>
                                <strong>ලිපිනය:</strong><br>
                                <?php echo $contact_address; ?>
                            </div>
                        </li>
                        <li>
                            <i class="fas fa-clock text-warning"></i>
                            <div>
                                <strong>සේවා වේලාව:</strong><br>
                                සඳුදා - ඉරිදා<br>
                                8:00 AM - 8:00 PM
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom py-4">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <p class="mb-0 footer-copyright">
                        <i class="fas fa-copyright me-1"></i>
                        <?php echo $current_year; ?> <?php echo $site_title; ?>. සියලු හිමිකම් ඇවිරිණි.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-0 footer-credit">
                        Designed with <i class="fas fa-heart text-danger"></i> for your future
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>

<button id="backToTopBtn" class="back-to-top" aria-label="මුල් යාම">
    <i class="fas fa-arrow-up"></i>
    <div class="cosmic-ring"></div>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Back to Top Button Functionality
    const backToTopBtn = document.getElementById('backToTopBtn');
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopBtn.classList.add('show');
        } else {
            backToTopBtn.classList.remove('show');
        }
    });
    
    backToTopBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Footer Links Hover Effect
    const footerLinks = document.querySelectorAll('.footer-links a, .footer-contact a');
    footerLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px)';
        });
        link.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
</script>

<link rel="stylesheet" href="assets/css/footer.css">