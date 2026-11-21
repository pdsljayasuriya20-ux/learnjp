<?php
/**
 * includes/wotd_card.php - "Word of the day" card for the home page.
 *
 * In index.php add ONE line where the card should appear (e.g. just before the "Vocabulary Books Module"):
 *     <?php include __DIR__ . '/includes/wotd_card.php'; ?>
 * It prints nothing if the word of the day is not available (so the home page never breaks).
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/dict_extra.php';

$__wd = null;
try {
    $__wd = dict_wotd(getDBConnection());
} catch (Throwable $e) {
    $__wd = null;
}

if ($__wd):
    $__wdLang = (isset($currentLang) && in_array($currentLang, ['si', 'en', 'ja'], true)) ? $currentLang : 'si';
    $__wdL    = dict_wotd_labels($__wdLang);
    $__wdBase = dict_site_base();
    $__wdPage = $__wdBase . '/wotd.php';
    $__wdText = dict_wotd_text($__wd, $__wdLang, $__wdPage);
    $__wdQ    = $__wd['kanji'] !== '' ? trim(explode('/', $__wd['kanji'])[0]) : $__wd['reading'];
    $__wdUrl  = 'dictionary.php?q=' . rawurlencode($__wdQ) . '&level=' . (int)$__wd['level'];
    $__h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
?>
<style>
    .wd-card { background: var(--nm-bg, #e6e9f2); border-radius: var(--nm-radius, 24px); box-shadow: var(--nm-raised, 8px 8px 18px #c3c9dc, -8px -8px 18px #fff);
               padding: 20px 18px; margin-bottom: 1.5rem; animation: nmFadeUp .55s ease both; }
    .wd-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-bottom: 8px; }
    .wd-tag { display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg, var(--nm-brand, #4f46e5), var(--nm-brand-2, #7c6cf5));
              color: #fff; border-radius: 999px; padding: 5px 14px; font-size: .76rem; font-weight: 800; }
    .wd-lv { background: #10b981; color: #fff; border-radius: 999px; padding: 4px 10px; font-size: .7rem; font-weight: 800; }
    .wd-read { font-size: 2rem; font-weight: 800; line-height: 1.2; word-break: break-word; color: var(--nm-text, #2d3250); }
    .wd-kanji { font-size: 1.25rem; font-weight: 700; color: var(--nm-brand, #4f46e5); margin-left: 8px; }
    .wd-rom { color: var(--nm-muted, #7a8099); font-size: .88rem; margin-top: 2px; }
    .wd-mean { margin: 12px 0; padding: 12px 14px; border-radius: var(--nm-radius-sm, 16px); font-size: 1.05rem; line-height: 1.5;
               box-shadow: var(--nm-inset-sm, inset 3px 3px 7px #c3c9dc, inset -3px -3px 7px #fff); color: var(--nm-text, #2d3250); }
    .wd-sent { font-size: .92rem; line-height: 1.5; color: var(--nm-text, #2d3250); margin-bottom: 12px; }
    .wd-sent b { display: block; font-size: 1rem; }
    .wd-sent small { display: block; color: var(--nm-muted, #7a8099); }
    .wd-act { display: flex; gap: 10px; flex-wrap: wrap; }
    .wd-act a, .wd-act button { flex: 1; min-width: 96px; border: 0; text-decoration: none; text-align: center; cursor: pointer;
               background: var(--nm-bg, #e6e9f2); color: var(--nm-brand, #4f46e5); font-weight: 800; font-size: .82rem; padding: 10px 8px;
               border-radius: 999px; box-shadow: var(--nm-raised-sm, 5px 5px 11px #c3c9dc, -5px -5px 11px #fff); }
    .wd-act a:active, .wd-act button:active { box-shadow: var(--nm-inset-sm, inset 3px 3px 7px #c3c9dc, inset -3px -3px 7px #fff); }
</style>

<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="wd-card">
        <div class="wd-head">
            <span class="wd-tag">📅 <?php echo $__h($__wdL['title']); ?></span>
            <span class="wd-lv">N<?php echo (int)$__wd['level']; ?></span>
        </div>
        <div class="wd-read"><?php echo $__h($__wd['reading']); ?><?php if ($__wd['kanji'] !== ''): ?><span class="wd-kanji"><?php echo $__h($__wd['kanji']); ?></span><?php endif; ?></div>
        <div class="wd-rom"><?php echo $__h($__wd['romaji']); ?></div>
        <div class="wd-mean"><?php echo $__h($__wd['meaning_si']); ?></div>
        <?php if (!empty($__wd['sentence'])): ?>
        <div class="wd-sent">💬 <b><?php echo $__h($__wd['sentence']['jp']); ?></b>
            <?php if ($__wd['sentence']['meaning_si'] !== ''): ?><small><?php echo $__h($__wd['sentence']['meaning_si']); ?></small><?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="wd-act">
            <button type="button" id="wdSpeak">🔊 <?php echo $__h($__wdL['listen']); ?></button>
            <a href="<?php echo $__h($__wdUrl); ?>"><?php echo $__h($__wdL['open']); ?> ›</a>
            <a href="https://wa.me/?text=<?php echo rawurlencode($__wdText); ?>" target="_blank" rel="noopener">WhatsApp</a>
        </div>
    </div>
  </div>
</div>
<script>
(function () {
  var b = document.getElementById('wdSpeak');
  if (!b) return;
  b.onclick = function () {
    if (!('speechSynthesis' in window)) return;
    var u = new SpeechSynthesisUtterance(<?php echo json_encode($__wd['reading'], JSON_UNESCAPED_UNICODE); ?>);
    u.lang = 'ja-JP'; u.rate = 0.85; speechSynthesis.cancel(); speechSynthesis.speak(u);
  };
})();
</script>
<?php endif; ?>
