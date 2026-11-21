<?php
/**
 * includes/w-grammar-render.php
 * Grammar card renderer — NEUMORPHISM theme (matches kanji / dashboard / list pages).
 *
 * TWO LOOKS, ONE MARKUP
 *  - Public pages (normal view):   neumorphism look, applied by CSS only (see $nm below).
 *  - Admin bare view (?bare=1):    the original flat, inline-styled, B5-print / Word-paste safe look
 *                                  + the Colour / Black&White toggle. Neumorphic shadows never reach
 *                                  Word because the neumorphism CSS is simply not output in bare mode.
 *    => call grammar_render_assets($bare) with the same $bare the page uses.
 *
 * LOCKING (unchanged idea): first GC_FREE_LIMIT cards of every level are free; a locked card never
 * echoes its content, so it can't be read from "view source".
 * The upsell banner / locked card now offer the PACKAGE (upgrade.php?level=..) first and the printed
 * book second.
 *
 * Copy protection + watermark live in includes/content_protect.php (cp_*). The old
 * grammar_render_protection() is kept at the bottom only for pages that still call it.
 */
require_once __DIR__ . '/grammar-data.php';

function grammar_h(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** 1 -> ①  ...  20 -> ⑳ ; above 20 -> (21) */
function grammar_circled(int $n): string
{
    static $c = ['①','②','③','④','⑤','⑥','⑦','⑧','⑨','⑩','⑪','⑫','⑬','⑭','⑮','⑯','⑰','⑱','⑲','⑳'];
    return $c[$n - 1] ?? '(' . $n . ')';
}

/** Find header.php / footer.php in includes/ or the site root (null if the site has none). */
function grammar_find_include(string $name): ?string
{
    foreach ([__DIR__ . '/' . $name, __DIR__ . '/../' . $name] as $p) {
        if (is_file($p)) return $p;
    }
    return null;
}

/** Minimal document opening — used when there is no header.php or for bare views. */
function grammar_standalone_open(string $title): string
{
    return '<!DOCTYPE html>' . "\n" . '<html lang="si"><head><meta charset="UTF-8">'
         . '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">'
         . '<title>' . grammar_h($title) . '</title>'
         . '<style>body{margin:0;background:#e6e9f2;font-family:\'Plus Jakarta Sans\',\'Noto Sans Sinhala\',sans-serif}</style>'
         . '</head><body>' . "\n";
}

function grammar_render_toolbar(): string
{
    return '<div class="gc-toolbar"><button id="gc-tbtn" class="gc-btn" type="button" onclick="gcToggleTheme()">◐ Print / B&amp;W මාදිලිය</button></div>';
}

/**
 * Fonts + CSS + theme script.
 * @param bool $bare true = admin bare view → flat print/Word look (no neumorphism CSS).
 */
function grammar_render_assets(bool $bare = false): string
{
    $fonts = 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800'
           . '&family=Shippori+Mincho:wght@500;700&family=Noto+Sans+JP:wght@400;500;700'
           . '&family=Noto+Serif+Sinhala:wght@600;700&family=Noto+Sans+Sinhala:wght@400;500;600;700&display=swap';

    $head = '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>'
          . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
          . '<link href="' . $fonts . '" rel="stylesheet">' . "\n"
          . '<script>window.GC_NM=' . ($bare ? 'false' : 'true') . ';</script>' . "\n";

    $base = <<<'HTML'
<style>
:root {
  --gc-brand: #4f46e5;
  --gc-brand-hover: #4338ca;
  --gc-brand-light: #eef2ff;
  --gc-accent: #ef4444;
  --gc-gold: #C9A227;
  --gc-bg: #f8fafc;
  --gc-card-bg: #ffffff;
  --gc-text: #0f172a;
  --gc-muted: #64748b;
  --gc-border: #e2e8f0;
  --gc-radius-lg: 20px;
  --gc-radius-md: 14px;

  --nm-bg: #e6e9f2; --nm-light: #ffffff; --nm-dark: #c3c9dc;
  --nm-text: #2d3250; --nm-muted: #7a8099;
  --nm-brand: #4f46e5; --nm-brand-2: #7c6cf5; --nm-green: #10b981; --nm-gold: #f59e0b;
  --nm-raised:    8px 8px 18px var(--nm-dark), -8px -8px 18px var(--nm-light);
  --nm-raised-sm: 5px 5px 11px var(--nm-dark), -5px -5px 11px var(--nm-light);
  --nm-inset:     inset 5px 5px 11px var(--nm-dark), inset -5px -5px 11px var(--nm-light);
  --nm-inset-sm:  inset 3px 3px 7px var(--nm-dark), inset -3px -3px 7px var(--nm-light);
}

@page { size: B5 portrait; margin: 10mm; }

html, body {
  margin: 0; padding: 0;
  background: var(--gc-bg);
  color: var(--gc-text);
  font-family: 'Plus Jakarta Sans', 'Noto Sans Sinhala', 'Noto Sans JP', Arial, sans-serif;
  font-size: 10pt; line-height: 1.42;
  -webkit-print-color-adjust: exact; print-color-adjust: exact;
}
body { min-width: 0; }

.gc-wrap { background: var(--gc-bg); padding: 22px 12px 48px; }
.gc-scroll { overflow-x: auto; }

.gc-toolbar { width: 720px; max-width: 100%; margin: 0 auto 16px; text-align: right; }
.gc-btn {
  font-family: 'Plus Jakarta Sans', sans-serif; font-size: 12.5px; font-weight: 700;
  padding: 8px 18px; border: 1px solid var(--gc-brand); border-radius: 30px;
  background: var(--gc-brand); color: #FFFFFF; cursor: pointer; transition: all .2s ease;
}
.gc-btn:hover { background: var(--gc-brand-hover); border-color: var(--gc-brand-hover); }
.gc-btn.bw { background: #FFFFFF; color: var(--gc-brand); border-color: var(--gc-brand); }

.cardwrap {
  width: 100%; max-width: 720px; margin: 0 auto 22px;
  page-break-after: always; break-after: page; page-break-inside: avoid; break-inside: avoid;
}
.cardwrap:last-child { page-break-after: auto; break-after: auto; margin-bottom: 0; }
.gc-card-inner { width: 100%; margin: 0 auto; }

.gc-nav { display: flex; justify-content: space-between; gap: 10px; width: 720px; max-width: 100%; margin: 16px auto 0; font-size: 12px; }
.gc-nav a { color: var(--gc-brand); text-decoration: none; font-weight: 700; }
.gc-nav a:hover { text-decoration: underline; }

.gc-page { max-width: 880px; margin: 0 auto; padding: 18px 14px 60px; }
.gc-page h1 { font-family: 'Noto Serif Sinhala', serif; font-size: 1.5rem; font-weight: 800; color: var(--gc-text); margin: 4px 0 14px; }

.gc-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin: 10px 0 18px; }
.gc-tab { padding: 8px 16px; border: 1px solid var(--gc-border); color: var(--gc-muted); background: #FFFFFF; text-decoration: none; font-weight: 700; border-radius: 30px; font-size: 12.5px; transition: all .2s ease; }
.gc-tab:hover { border-color: var(--gc-brand); color: var(--gc-brand); background: var(--gc-brand-light); }
.gc-tab.active { background: var(--gc-brand); color: #FFFFFF; border-color: var(--gc-brand); }
.gc-tab small { opacity: .8; margin-left: 4px; }

.gc-viewall { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; color: var(--gc-brand); text-decoration: none; margin-bottom: 14px; }
.gc-viewall:hover { color: var(--gc-brand-hover); }

.gc-list { width: 100%; border-collapse: separate; border-spacing: 0; background: var(--gc-card-bg); border: 1px solid var(--gc-border); border-radius: var(--gc-radius-md); overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,.03); }
.gc-list th, .gc-list td { padding: 11px 14px; border-bottom: 1px solid var(--gc-border); text-align: left; vertical-align: top; }
.gc-list tr:last-child td { border-bottom: none; }
.gc-list tr:hover td { background: #fafbff; }
.gc-list th { background: var(--gc-brand-light); color: var(--gc-brand); font-size: 11px; text-transform: uppercase; letter-spacing: .03em; }
.gc-list .jp { font-family: 'Shippori Mincho', 'Noto Sans JP', serif; font-size: 16px; font-weight: 700; color: var(--gc-text); }
.gc-list .sub { font-family: 'Noto Sans JP', sans-serif; font-size: 11.5px; color: var(--gc-muted); }
.gc-list a { color: var(--gc-text); text-decoration: none; }
.gc-list a:hover { color: var(--gc-brand); }

.gc-muted { color: var(--gc-muted); font-size: 12.5px; }
.gc-muted a { color: var(--gc-brand); font-weight: 700; }

.gc-card {
  background: var(--gc-card-bg); border: 1px solid var(--gc-border); border-radius: var(--gc-radius-md);
  overflow: hidden; box-sizing: border-box; box-shadow: 0 8px 20px -8px rgba(15,23,42,.18); table-layout: fixed;
}

@media print {
  html, body { background: #FFFFFF !important; }
  .gc-wrap { background: #FFFFFF !important; padding: 0 !important; }
  .gc-toolbar, .gc-nav, .gc-noprint { display: none !important; }
  .gc-page { max-width: none !important; padding: 0 !important; }
  .cardwrap { margin: 0 !important; max-width: none !important; page-break-inside: avoid !important; break-inside: avoid !important; }
  .gc-card { border: 1px solid #4A4A4A !important; box-shadow: none !important; }
  .gc-card-inner { width: 100% !important; max-width: none !important; }
}

@media (max-width: 600px) {
  .gc-wrap { padding: 14px 8px 36px; }
  .gc-toolbar { text-align: center; margin-bottom: 12px; }
  .gc-btn { width: 100%; max-width: 320px; padding: 10px 14px; }
  .gc-page { padding: 12px 10px 44px; }
  .gc-page h1 { font-size: 1.2rem; }
  .gc-tabs { gap: 6px; margin: 8px 0 14px; }
  .gc-tab { padding: 6px 12px; font-size: 11.5px; }
  .gc-list th, .gc-list td { padding: 8px 9px; }
  .gc-list .jp { font-size: 14px; }
  .gc-list .sub { font-size: 10.5px; }
  .gc-list th:nth-child(3), .gc-list td:nth-child(3) { display: none; }
  .gc-nav { font-size: 11px; flex-wrap: wrap; gap: 6px; }
}

@media (max-width: 480px) {
  .gc-hdr-badge { min-width: 24px !important; padding: 4px !important; }
  .gc-hdr-jp { font-size: 11pt !important; }
  .gc-hdr-formula { font-size: 8pt !important; }
  .gc-hdr-gloss-cell { width: 30% !important; padding-left: 5px !important; }
  .gc-hdr-gloss { font-size: 7.6pt !important; }
  .gc-hdr-gloss-si { font-size: 7.6pt !important; }
  .gc-form-row .gc-form-label,
  .gc-form-row .gc-form-example {
    display: block !important; width: 100% !important; border-left: none !important;
    border-bottom: 1px dashed var(--gc-border) !important;
  }
  .gc-form-row .gc-form-example { border-bottom: none !important; }
}
</style>

<script>
(function(){
  /* Colour / B&W toggle — only used in the admin bare view (the toolbar is hidden otherwise). */
  var themes = {
    color: {
      pageBg:'#f8fafc',
      card:{borderColor:'#e2e8f0'},
      hdr:{backgroundColor:'#4f46e5'},
      badgeA:{backgroundColor:'#FFFFFF', color:'#4f46e5', borderColor:'#ef4444'},
      badgeB:{backgroundColor:'#4f46e5', color:'#FFFFFF', borderColor:'#4f46e5'},
      hdrSub:{color:'#E0E7FF'}, hdrGloss:{color:'#E0E7FF'}, hdrGlossSi:{color:'#FFFFFF'},
      meanCell:{backgroundColor:'#eef2ff', borderBottomColor:'#e2e8f0'},
      meanLabel:{color:'#4f46e5'}, tx1:{color:'#0f172a'}, tx2:{color:'#475569'},
      secT:{color:'#4f46e5', borderLeftColor:'#4f46e5'}, secI:{color:'#4f46e5', borderLeftColor:'#4f46e5'}, secA:{color:'#4f46e5', borderLeftColor:'#4f46e5'},
      fTable:{backgroundColor:'#f8fafc', borderColor:'#e2e8f0'},
      fCell:{color:'#334155', borderBottomColor:'#e2e8f0'},
      fCellR:{color:'#334155', borderBottomColor:'#e2e8f0', borderLeftColor:'#e2e8f0'},
      note:{backgroundColor:'#fffbeb', borderColor:'#fbbf24', borderStyle:'dashed', color:'#92400e'},
      ex:{backgroundColor:'#f8fafc', borderColor:'#e2e8f0', borderLeftColor:'#4f46e5'},
      exRo:{color:'#64748b'},
      tips:{backgroundColor:'#eef2ff', borderColor:'#c7d2fe', color:'#3730a3'},
      rem:{backgroundColor:'#4f46e5'},
      remLabel:{color:'#FFFFFF', borderBottomColor:'#818cf8'}, remText:{color:'#eef2ff'}
    },
    bw: {
      pageBg:'#FFFFFF',
      card:{borderColor:'#000000'},
      hdr:{backgroundColor:'#666666'},
      badgeA:{backgroundColor:'#FFFFFF', color:'#000000', borderColor:'#000000'},
      badgeB:{backgroundColor:'#000000', color:'#FFFFFF', borderColor:'#FFFFFF'},
      hdrSub:{color:'#E2E2E2'}, hdrGloss:{color:'#E2E2E2'}, hdrGlossSi:{color:'#F7F7F7'},
      meanCell:{backgroundColor:'#f2f2f2', borderBottomColor:'#B5B5B5'},
      meanLabel:{color:'#000000'}, tx1:{color:'#000000'}, tx2:{color:'#262626'},
      secT:{color:'#000000', borderLeftColor:'#000000'}, secI:{color:'#000000', borderLeftColor:'#000000'}, secA:{color:'#000000', borderLeftColor:'#000000'},
      fTable:{backgroundColor:'#F8F8F8', borderColor:'#000000'},
      fCell:{color:'#262626', borderBottomColor:'#B5B5B5'},
      fCellR:{color:'#262626', borderBottomColor:'#B5B5B5', borderLeftColor:'#B5B5B5'},
      note:{backgroundColor:'#FFFFFF', borderColor:'#666666', borderStyle:'dashed', color:'#000000'},
      ex:{backgroundColor:'#F8F8F8', borderColor:'#999999', borderLeftColor:'#000000'},
      exRo:{color:'#666666'},
      tips:{backgroundColor:'#FFFFFF', borderColor:'#000000', color:'#262626'},
      rem:{backgroundColor:'#777777'},
      remLabel:{color:'#FFFFFF', borderBottomColor:'#999999'}, remText:{color:'#F2F2F2'}
    }
  };
  var current = 'color';

  function apply(name){
    var wrap = document.getElementById('gc-wrap');
    if(!wrap) return;
    var t = themes[name];
    var els = wrap.querySelectorAll('[data-r]');
    for (var i = 0; i < els.length; i++) {
      var rules = t[els[i].getAttribute('data-r')];
      if(!rules) continue;
      for (var k in rules) { els[i].style[k] = rules[k]; }
    }
    wrap.style.background = t.pageBg;
    var b = document.getElementById('gc-tbtn');
    if(b){
      if(name === 'bw'){ b.className = 'gc-btn bw'; b.innerHTML = '◑ වර්ණ මාදිලියට (Colour)'; }
      else { b.className = 'gc-btn'; b.innerHTML = '◐ Print / B&amp;W මාදිලිය'; }
    }
    current = name;
  }

  window.gcToggleTheme = function(){ apply(current === 'color' ? 'bw' : 'color'); };

  // In the public (neumorphism) view the colours come from CSS, so inline colours must NOT be rewritten.
  document.addEventListener('DOMContentLoaded', function(){ if (!window.GC_NM) apply('color'); });
})();
</script>
HTML;

    // ------------------------------------------------------------------
    // NEUMORPHISM look for the public view. Pure CSS (!important beats the
    // inline styles of the Word-safe markup), screen only — print is untouched.
    // ------------------------------------------------------------------
    $nm = '';
    if (!$bare) {
        $nm = <<<'HTML'
<style>
@media screen {
  .gc-wrap { background: transparent !important; padding-left: 0 !important; padding-right: 0 !important; }

  /* the card */
  .gc-wrap .gc-card {
    background: var(--nm-bg) !important; border: 0 !important;
    border-collapse: separate !important; border-spacing: 0 !important;
    border-radius: 24px !important; box-shadow: var(--nm-raised) !important; overflow: hidden;
  }

  /* header band */
  .gc-wrap [data-r="hdr"] {
    background: linear-gradient(135deg, var(--nm-brand) 0%, var(--nm-brand-2) 100%) !important;
    padding: 16px 18px !important;
  }
  .gc-wrap [data-r="badgeA"] {
    background: var(--nm-bg) !important; color: var(--nm-brand) !important; border: 0 !important;
    box-shadow: var(--nm-raised-sm) !important; min-width: 36px !important; height: 36px !important;
  }

  /* Sinhala meaning */
  .gc-wrap [data-r="meanCell"] { background: var(--nm-bg) !important; border-bottom: 0 !important; padding: 16px 18px 8px !important; }
  .gc-wrap [data-r="meanLabel"] { color: var(--nm-brand) !important; }
  .gc-wrap [data-r="meanCell"] [data-r="tx1"] {
    background: var(--nm-bg); box-shadow: var(--nm-inset-sm); border-radius: 14px;
    padding: 10px 14px; margin-top: 6px; font-weight: 600; color: var(--nm-text) !important;
  }

  /* section titles */
  .gc-wrap [data-r="secT"], .gc-wrap [data-r="secI"], .gc-wrap [data-r="secA"] {
    color: var(--nm-brand) !important; border-left-width: 4px !important; border-left-color: var(--nm-brand) !important;
  }
  .gc-wrap [data-r="tx2"] { color: var(--nm-muted) !important; }
  .gc-wrap [data-r="tx1"] { color: var(--nm-text) !important; }

  /* formation table */
  .gc-wrap [data-r="fTable"] {
    background: var(--nm-bg) !important; border: 0 !important; border-collapse: separate !important; border-spacing: 0 !important;
    border-radius: 16px !important; box-shadow: var(--nm-inset-sm) !important; overflow: hidden;
  }
  .gc-wrap [data-r="fCell"], .gc-wrap [data-r="fCellR"] { color: var(--nm-text) !important; border-bottom-color: var(--nm-dark) !important; }
  .gc-wrap [data-r="fCellR"] { border-left-color: var(--nm-dark) !important; }

  /* note */
  .gc-wrap [data-r="note"] {
    background: var(--nm-bg) !important; border: 0 !important; border-left: 4px solid var(--nm-gold) !important;
    border-radius: 14px !important; box-shadow: var(--nm-inset-sm) !important; color: #92400e !important; padding: 9px 12px !important;
  }

  /* examples */
  .gc-wrap [data-r="ex"] {
    background: var(--nm-bg) !important; border: 0 !important; border-left: 4px solid var(--nm-brand) !important;
    border-radius: 16px !important; box-shadow: var(--nm-raised-sm) !important;
    padding: 10px 14px !important; margin-bottom: 12px !important;
  }
  .gc-wrap [data-r="exRo"] { color: var(--nm-muted) !important; }

  /* exam tips */
  .gc-wrap [data-r="tips"] {
    background: var(--nm-bg) !important; border: 0 !important; border-left: 4px solid var(--nm-gold) !important;
    border-radius: 14px !important; box-shadow: var(--nm-inset-sm) !important; color: #3730a3 !important; padding: 10px 12px !important;
  }

  /* remember point */
  .gc-wrap [data-r="rem"] {
    background: linear-gradient(135deg, var(--nm-brand) 0%, var(--nm-brand-2) 100%) !important;
    border-collapse: separate !important; border-radius: 16px !important; box-shadow: var(--nm-raised-sm) !important; overflow: hidden;
  }

  /* list view (kept for pages that still use the table list) */
  .gc-tab { background: var(--nm-bg); border: 0; box-shadow: var(--nm-raised-sm); }
  .gc-tab.active { box-shadow: var(--nm-inset-sm); color: var(--nm-brand); background: var(--nm-bg); }
  .gc-list { background: var(--nm-bg); border: 0; border-radius: 20px; box-shadow: var(--nm-raised); }
  .gc-list th { background: var(--nm-bg); box-shadow: var(--nm-inset-sm); }
  .gc-list tr:hover td { background: transparent; }
}
</style>
HTML;
    }

    return $head . $base . "\n" . $nm;
}

/** Render One Complete Grammar Card — Word-paste safe markup (inline styles + data-r hooks). */
function grammar_render_card(array $card): string
{
    $no      = (int)$card['card_no'];
    $hasRem  = !empty($card['remember']);
    $hasTips = !empty($card['tips']);

    ob_start(); ?>
<div class="cardwrap" id="card-<?= $no ?>">
  <div class="gc-card-inner" style="width:720px; max-width:100%; margin:0 auto;">
    <table class="gc-card" width="100%" cellpadding="0" cellspacing="0" border="0" data-r="card"
           style="background:#FFFFFF; border:1px solid #e2e8f0; border-collapse:collapse; table-layout:fixed; width:100%; border-radius:14px; overflow:hidden;">

      <!-- Brand header -->
      <tr>
        <td data-r="hdr" style="background:#4f46e5; padding:12px 14px;">
          <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
            <tr>
              <td class="gc-hdr-badge-cell" style="vertical-align:middle; width:36px; padding-right:9px; border-right:1px solid rgba(255,255,255,0.25);">
                <div data-r="badgeA" class="gc-hdr-badge"
                     style="display:inline-flex; align-items:center; justify-content:center; min-width:28px; height:28px; padding:3px; text-align:center; font-family:'Plus Jakarta Sans',sans-serif; font-size:9.5pt; font-weight:800; line-height:1; background:#FFFFFF; color:#4f46e5; border:2px solid #ef4444; border-radius:50%;">
                  <?= grammar_h((string)$no) ?>
                </div>
              </td>

              <td class="gc-hdr-title-cell" style="vertical-align:middle; padding:0 10px;">
                <div class="gc-hdr-jp" style="font-family:'Shippori Mincho','Noto Sans JP',serif; font-size:12pt; font-weight:700; color:#FFFFFF; line-height:1.3;">
                  <?= grammar_h($card['title_jp']) ?>
                </div>
                <div data-r="hdrSub" class="gc-hdr-formula"
                     style="font-family:'Noto Sans JP',sans-serif; font-size:9pt; color:#E0E7FF; padding-top:2px; line-height:1.3;">
                  <?= grammar_h($card['formula']) ?>
                </div>
              </td>

              <td class="gc-hdr-gloss-cell" style="vertical-align:middle; text-align:right; width:26%; padding-left:6px;">
                <div data-r="hdrGloss" class="gc-hdr-gloss"
                     style="font-family:'Noto Sans JP',sans-serif; font-size:9pt; color:#E0E7FF; font-weight:500; line-height:1.25;">
                  <?= grammar_clean_html($card['gloss_en']) ?>
                </div>
                <?php if (!empty($card['gloss_si'])): ?>
                <div data-r="hdrGlossSi" class="gc-hdr-gloss-si"
                     style="font-family:'Noto Sans Sinhala',sans-serif; font-size:9pt; color:#FFFFFF; font-weight:600; padding-top:2px; line-height:1.25;">
                  <?= grammar_clean_html($card['gloss_si']) ?>
                </div>
                <?php endif; ?>
              </td>
            </tr>
          </table>
        </td>
      </tr>

      <!-- Sinhala meaning -->
      <tr>
        <td data-r="meanCell" style="background:#eef2ff; padding:9px 14px; border-bottom:1px solid #e2e8f0;">
          <div data-r="meanLabel"
               style="font-family:'Noto Serif Sinhala',serif; font-size:10pt; font-weight:700; color:#4f46e5; padding-bottom:2px; line-height:1.3;">
            සිංහල අර්ථය
          </div>
          <div data-r="tx1"
               style="font-family:'Noto Sans Sinhala',sans-serif; font-size:10pt; color:#0f172a; line-height:1.48;">
            <?= grammar_clean_html($card['meaning_si']) ?>
          </div>
        </td>
      </tr>

      <!-- Usage -->
      <?php if (!empty($card['usage'])): ?>
      <tr>
        <td style="padding:9px 14px 3px 14px;">
          <div data-r="secT"
               style="font-family:'Noto Serif Sinhala',serif; font-size:10.5pt; font-weight:700; color:#4f46e5; border-left:3px solid #4f46e5; padding-left:8px; margin-bottom:5px; line-height:1.3;">
            භාවිතය (Usage)
          </div>
          <div data-r="tx2"
               style="font-family:'Noto Sans Sinhala',sans-serif; font-size:9.7pt; color:#475569; line-height:1.48;">
          <?php foreach ($card['usage'] as $i => $line): ?>
            <?= grammar_circled($i + 1) ?> <?= grammar_clean_html($line) ?><?= $i < count($card['usage']) - 1 ? '<br>' : '' ?>
          <?php endforeach; ?>
          </div>
        </td>
      </tr>
      <?php endif; ?>

      <!-- Formation & notes -->
      <?php if (!empty($card['formation']) || !empty($card['note_si'])): ?>
      <tr>
        <td style="padding:8px 14px 3px 14px;">
          <div data-r="secT"
               style="font-family:'Noto Serif Sinhala',serif; font-size:10.5pt; font-weight:700; color:#4f46e5; border-left:3px solid #4f46e5; padding-left:8px; margin-bottom:6px; line-height:1.3;">
            සෑදෙන ආකාරය (Formation)
          </div>

          <?php if (!empty($card['formation'])): $n = count($card['formation']); ?>
          <table width="100%" cellpadding="0" cellspacing="0" border="0" data-r="fTable"
                 style="border-collapse:collapse; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; width:100%;">
          <?php foreach ($card['formation'] as $i => $row): $last = ($i === $n - 1); $bb = $last ? 'border-bottom-width:0;' : 'border-bottom:1px solid #e2e8f0;'; ?>
            <tr class="gc-form-row">
              <td data-r="fCell" class="gc-form-label"
                  style="padding:6px 9px; font-family:'Noto Sans JP',sans-serif; font-size:9.5pt; color:#334155; <?= $bb ?><?= $i === 0 ? ' width:50%;' : '' ?>">
                <?= grammar_clean_html($row['label']) ?>
              </td>
              <td data-r="fCellR" class="gc-form-example"
                  style="padding:6px 9px; font-family:'Noto Sans JP',sans-serif; font-size:9.2pt; color:#334155; <?= $bb ?> border-left:1px solid #e2e8f0;">
                <?= grammar_h($row['example_jp']) ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </table>
          <?php endif; ?>

          <?php if (!empty($card['note_si'])): ?>
          <div data-r="note"
               style="font-family:'Noto Sans Sinhala',sans-serif; font-size:9.2pt; color:#92400e; background:#fffbeb; border:1px dashed #fbbf24; border-radius:8px; padding:6px 9px; margin-top:6px; line-height:1.45;">
            <?= grammar_clean_html($card['note_si']) ?>
          </div>
          <?php endif; ?>
        </td>
      </tr>
      <?php endif; ?>

      <!-- Examples -->
      <?php if (!empty($card['examples'])): $en = count($card['examples']); ?>
      <tr>
        <td style="padding:9px 14px 3px 14px;">
          <div data-r="secI"
               style="font-family:'Noto Serif Sinhala',serif; font-size:10.5pt; font-weight:700; color:#4f46e5; border-left:3px solid #4f46e5; padding-left:8px; margin-bottom:6px; line-height:1.3;">
            උදාහරණ වාක්‍ය (Examples)
          </div>

          <?php foreach ($card['examples'] as $i => $ex): ?>
          <div data-r="ex"
               style="background:#f8fafc; border:1px solid #e2e8f0; border-left:3px solid #4f46e5; border-radius:8px; padding:6px 9px;<?= $i < $en - 1 ? ' margin-bottom:5px;' : '' ?>">
            <div data-r="tx1"
                 style="font-family:'Noto Sans JP',sans-serif; font-size:10pt; color:#0f172a; line-height:1.35;">
              <?= grammar_circled($i + 1) ?> <?= grammar_h($ex['jp']) ?>
            </div>

            <?php if ($ex['romaji'] !== ''): ?>
            <div data-r="exRo"
                 style="font-family:'Noto Sans JP',sans-serif; font-size:8.2pt; color:#64748b; padding-top:1px; line-height:1.25;">
              <?= grammar_h($ex['romaji']) ?>
            </div>
            <?php endif; ?>

            <?php if ($ex['meaning_si'] !== ''): ?>
            <div data-r="tx2"
                 style="font-family:'Noto Sans Sinhala',sans-serif; font-size:9.2pt; color:#475569; padding-top:2px; line-height:1.4;">
              <?= grammar_h($ex['meaning_si']) ?>
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </td>
      </tr>
      <?php endif; ?>

      <!-- Exam tips -->
      <?php if ($hasTips): ?>
      <tr>
        <td style="padding:9px 14px <?= $hasRem ? '3px' : '12px' ?> 14px;">
          <div data-r="secA"
               style="font-family:'Noto Serif Sinhala',serif; font-size:10.5pt; font-weight:700; color:#4f46e5; border-left:3px solid #4f46e5; padding-left:8px; margin-bottom:5px; line-height:1.3;">
            විභාග ඉඟි (Exam Tips)
          </div>
          <div data-r="tips"
               style="background:#eef2ff; border:1px solid #c7d2fe; border-radius:8px; padding:7px 9px; font-family:'Noto Sans Sinhala',sans-serif; font-size:9.2pt; color:#3730a3; line-height:1.45;">
          <?php foreach ($card['tips'] as $i => $line): ?>
            ★ <?= grammar_clean_html($line) ?><?= $i < count($card['tips']) - 1 ? '<br>' : '' ?>
          <?php endforeach; ?>
          </div>
        </td>
      </tr>
      <?php endif; ?>

      <!-- Remember points -->
      <?php if ($hasRem): ?>
      <tr>
        <td style="padding:8px 14px 12px 14px;">
          <table width="100%" cellpadding="0" cellspacing="0" border="0" data-r="rem"
                 style="border-collapse:collapse; background:#4f46e5; border-radius:8px; width:100%;">
            <tr>
              <td style="padding:8px 11px;">
                <div data-r="remLabel"
                     style="font-family:'Noto Serif Sinhala',serif; font-size:9.3pt; font-weight:700; color:#FFFFFF; padding-bottom:3px; border-bottom:1px solid #818cf8; margin-bottom:4px; line-height:1.3;">
                  ◆ මතක තබාගන්න (Remember Point)
                </div>
                <div data-r="remText"
                     style="font-family:'Noto Sans Sinhala',sans-serif; font-size:9.2pt; color:#eef2ff; line-height:1.45;">
                <?php foreach ($card['remember'] as $i => $line): ?>
                  <?= str_replace('<b>', '<b style="color:#FFFFFF;">', grammar_clean_html($line)) ?><?= $i < count($card['remember']) - 1 ? '<br>' : '' ?>
                <?php endforeach; ?>
                </div>
              </td>
            </tr>
          </table>
        </td>
      </tr>
      <?php endif; ?>

    </table>
  </div>
</div>
<?php
    return ob_get_clean();
}

/* ==================================================================
 *  PAID-CONTENT GATING
 *  Edit these constants to match your real book / pricing.
 * ================================================================== */
if (!defined('GC_FREE_LIMIT'))    define('GC_FREE_LIMIT', 30);                                   // free cards per level
if (!defined('GC_BOOK_TITLE'))    define('GC_BOOK_TITLE', 'JLPT සම්පූර්ණ ව්‍යාකරණ පොත');            // TODO
if (!defined('GC_BOOK_PRICE'))    define('GC_BOOK_PRICE', 'Rs. 2,500');                          // TODO: real price
if (!defined('GC_BOOK_FORMAT'))   define('GC_BOOK_FORMAT', 'PDF (digital) + Print on request');   // TODO
if (!defined('GC_BOOK_BUY_URL'))  define('GC_BOOK_BUY_URL', 'https://learnjapanese.jp/books.php');        // TODO: real checkout link
if (!defined('GC_UPGRADE_URL'))   define('GC_UPGRADE_URL', 'upgrade.php');                       // package page

/** 0-based position of $slug inside its level's ordered card list, or null if not found. */
function grammar_card_rank(string $level, string $slug): ?int
{
    $list = grammar_list_cards($level);
    foreach ($list as $i => $c) {
        if ($c['slug'] === $slug) return $i;
    }
    return null;
}

function grammar_is_locked_rank(int $rank): bool
{
    return $rank >= GC_FREE_LIMIT;
}

/** upgrade.php link for one level (or the general package page when the level is unknown) */
function grammar_upgrade_url(?string $level = null): string
{
    $level = strtoupper(trim((string)$level));
    return GC_UPGRADE_URL . ($level !== '' ? '?level=' . rawurlencode($level) : '');
}

/** Shared CSS for the lock badge, upsell banner and the book-details modal — neumorphism. */
function grammar_render_lock_assets(): string
{
    return <<<'HTML'
<style>
/* ---------- Locked row (table list view) ---------- */
.gc-locked-row td { color: #94a3b8 !important; background: transparent; }
.gc-lock-ic { display: inline-flex; align-items: center; gap: 6px; font-family: 'Shippori Mincho', 'Noto Sans JP', serif; font-size: 15px; font-weight: 700; color: var(--nm-muted, #7a8099); }
.gc-lock-ic svg { flex: none; }
.gc-locked-row .sub { color: #a3acc0; }

/* ---------- Shared buttons ---------- */
.gc-btn-pkg, .gc-btn-buy, .gc-btn-details {
  font-family: 'Noto Sans Sinhala', 'Plus Jakarta Sans', sans-serif; font-size: 12px; font-weight: 800;
  padding: 11px 20px; border-radius: 999px; text-decoration: none; cursor: pointer; border: 0;
  white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; gap: 7px;
  transition: box-shadow .2s ease, transform .2s ease;
}
.gc-btn-pkg { background: linear-gradient(135deg, var(--nm-brand, #4f46e5), var(--nm-brand-2, #7c6cf5)); color: #fff; box-shadow: var(--nm-raised-sm); }
.gc-btn-pkg:hover { color: #fff; transform: translateY(-2px); }
.gc-btn-buy, .gc-btn-details { background: var(--nm-bg, #e6e9f2); color: var(--nm-brand, #4f46e5); box-shadow: var(--nm-raised-sm); }
.gc-btn-buy:hover, .gc-btn-details:hover { color: var(--nm-brand, #4f46e5); transform: translateY(-2px); }
.gc-btn-pkg:active, .gc-btn-buy:active, .gc-btn-details:active { box-shadow: var(--nm-inset-sm); transform: scale(.97); }

/* ---------- Locked card placeholder ---------- */
.gc-locked-card {
  width: 720px; max-width: 100%; margin: 0 auto 22px;
  background: var(--nm-bg, #e6e9f2); border-radius: 24px; box-shadow: var(--nm-inset-sm);
  padding: 26px 18px; text-align: center;
}
.gc-locked-card .gc-lock-badge {
  display: inline-flex; align-items: center; justify-content: center; width: 46px; height: 46px; border-radius: 50%;
  background: var(--nm-bg, #e6e9f2); box-shadow: var(--nm-raised-sm); margin-bottom: 10px;
}
.gc-locked-card h3 { margin: 4px 0 2px; font-family: 'Shippori Mincho', 'Noto Sans JP', serif; font-size: 14pt; font-weight: 700; color: var(--nm-brand, #4f46e5); }
.gc-locked-card .gc-locked-formula { font-family: 'Noto Sans JP', sans-serif; font-size: 10pt; color: var(--nm-muted, #7a8099); margin-bottom: 12px; }
.gc-locked-card p { font-family: 'Noto Sans Sinhala', sans-serif; font-size: 10pt; color: var(--nm-muted, #7a8099); margin: 0 0 16px; }
.gc-locked-actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }

/* ---------- Upsell banner ---------- */
.gc-upsell {
  width: 720px; max-width: 100%; margin: 6px auto 24px;
  background: var(--nm-bg, #e6e9f2); border-radius: 20px; box-shadow: var(--nm-raised);
  border-left: 6px solid var(--nm-gold, #f59e0b);
  padding: 20px 22px; color: var(--nm-text, #2d3250);
  display: flex; flex-wrap: wrap; align-items: center; gap: 16px;
}
.gc-upsell-icon { font-size: 32px; line-height: 1; flex: none; }
.gc-upsell-body { flex: 1 1 260px; min-width: 220px; }
.gc-upsell-body h4 { margin: 0 0 4px; font-family: 'Noto Serif Sinhala', serif; font-size: 13pt; font-weight: 700; color: var(--nm-text, #2d3250); }
.gc-upsell-body p { margin: 0; font-family: 'Noto Sans Sinhala', sans-serif; font-size: 9.8pt; color: var(--nm-muted, #7a8099); line-height: 1.5; }
.gc-upsell-actions { display: flex; gap: 10px; flex-wrap: wrap; flex: none; }

@media (max-width: 600px) {
  .gc-upsell { padding: 16px; border-radius: 18px; }
  .gc-upsell-actions { width: 100%; }
  .gc-upsell-actions > * { flex: 1 1 100%; }
  .gc-locked-card { padding: 20px 14px; border-radius: 20px; }
  .gc-locked-actions > * { flex: 1 1 100%; }
}

/* ---------- Book details modal ---------- */
.gc-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(45,50,80,.5); -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px); z-index: 99999; align-items: center; justify-content: center; padding: 20px; }
.gc-modal-overlay.open { display: flex; }
.gc-modal { background: var(--nm-bg, #e6e9f2); border-radius: 28px; width: 100%; max-width: 420px; padding: 26px; position: relative; box-shadow: 0 20px 50px rgba(0,0,0,.3); }
.gc-modal-close { position: absolute; top: 12px; right: 14px; width: 36px; height: 36px; border: 0; border-radius: 50%; background: var(--nm-bg, #e6e9f2); box-shadow: var(--nm-raised-sm); font-size: 20px; cursor: pointer; color: var(--nm-muted, #7a8099); line-height: 1; }
.gc-modal-close:active { box-shadow: var(--nm-inset-sm); }
.gc-modal h3 { margin: 0 44px 4px 0; font-family: 'Noto Serif Sinhala', serif; font-size: 14pt; color: var(--nm-text, #2d3250); }
.gc-modal .gc-modal-price { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; color: var(--nm-brand, #4f46e5); font-size: 16pt; margin: 4px 0 14px; }
.gc-modal table { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-family: 'Noto Sans Sinhala', sans-serif; font-size: 9.5pt; }
.gc-modal table td { padding: 7px 0; border-bottom: 1px solid var(--nm-dark, #c3c9dc); color: var(--nm-text, #2d3250); }
.gc-modal table td:first-child { color: var(--nm-muted, #7a8099); width: 44%; }
.gc-modal .gc-modal-actions { display: flex; flex-direction: column; gap: 10px; }
.gc-modal .gc-btn-pkg, .gc-modal .gc-btn-buy { width: 100%; padding: 13px; font-size: 12.5px; }
</style>
HTML;
}

/**
 * The upsell banner shown once a level has locked cards.
 * $extra = how many cards in this level are locked.  $level = 'N4' etc. (for the package link).
 */
function grammar_render_upsell_banner(int $extra, ?string $level = null): string
{
    if ($extra <= 0) return '';
    ob_start(); ?>
<div class="gc-upsell">
  <div class="gc-upsell-icon">👑</div>
  <div class="gc-upsell-body">
    <h4>තව ව්‍යාකරණ පාඩම් <?= (int)$extra ?>ක් Package එකෙන් විවෘත කරගන්න</h4>
    <p>මුල් පාඩම් <?= (int)GC_FREE_LIMIT ?> නොමිලේ. ඉතිරි සියල්ල Package එකක් activate කිරීමෙන් හෝ මුද්‍රිත පොත මිලදී ගැනීමෙන් ලබාගන්න.</p>
  </div>
  <div class="gc-upsell-actions">
    <a class="gc-btn-pkg" href="<?= grammar_h(grammar_upgrade_url($level)) ?>">Package Upgrade</a>
    <a class="gc-btn-buy" href="<?= grammar_h(GC_BOOK_BUY_URL) ?>" target="_blank" rel="noopener">මුද්‍රිත පොත</a>
    <button type="button" class="gc-btn-details" onclick="gcOpenBookModal()">Book Details</button>
  </div>
</div>
<?php
    return ob_get_clean();
}

/**
 * Book-details modal markup + toggle script. Include this ONCE per page.
 * $levels = the array returned by grammar_levels(), used to list contents.
 */
function grammar_render_book_modal(array $levels): string
{
    ob_start(); ?>
<div class="gc-modal-overlay" id="gc-book-modal" onclick="if(event.target===this) gcCloseBookModal()">
  <div class="gc-modal">
    <button type="button" class="gc-modal-close" onclick="gcCloseBookModal()" aria-label="Close">&times;</button>
    <h3><?= grammar_h(GC_BOOK_TITLE) ?></h3>
    <div class="gc-modal-price"><?= grammar_h(GC_BOOK_PRICE) ?></div>
    <table>
      <tr><td>ආකෘතිය (Format)</td><td><?= grammar_h(GC_BOOK_FORMAT) ?></td></tr>
      <?php foreach ($levels as $lv): ?>
      <tr><td>JLPT <?= grammar_h($lv['level']) ?></td><td><?= (int)$lv['total'] ?> ව්‍යාකරණ පාඩම්</td></tr>
      <?php endforeach; ?>
      <tr><td>නොමිලේ ලබාගත හැකි</td><td>එක් මට්ටමකට පාඩම් <?= (int)GC_FREE_LIMIT ?></td></tr>
    </table>
    <div class="gc-modal-actions">
      <a class="gc-btn-pkg" href="<?= grammar_h(grammar_upgrade_url()) ?>">Packages බලන්න</a>
      <a class="gc-btn-buy" href="<?= grammar_h(GC_BOOK_BUY_URL) ?>" target="_blank" rel="noopener">මුද්‍රිත පොත මිලදී ගන්න (Buy Now)</a>
    </div>
  </div>
</div>
<script>
  function gcOpenBookModal(){ document.getElementById('gc-book-modal').classList.add('open'); }
  function gcCloseBookModal(){ document.getElementById('gc-book-modal').classList.remove('open'); }
</script>
<?php
    return ob_get_clean();
}

/* ==================================================================
 *  LEGACY CONTENT PROTECTION
 *  The pages now use includes/content_protect.php (cp_*) which also
 *  covers the whole content area with a traceable watermark. This
 *  function is kept only so older pages that still call it keep working.
 *  Do not call both on the same page (you would get two watermarks).
 * ================================================================== */
function grammar_render_protection(bool $bare): string
{
    if ($bare) return '';

    $wmSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300">'
           . '<defs><pattern id="wm" width="300" height="300" patternUnits="userSpaceOnUse" patternTransform="rotate(-28)">'
           . '<text x="0" y="160" font-family="Arial, sans-serif" font-size="20" font-weight="700" fill="rgba(79,70,229,0.10)">learnjapanese.jp</text>'
           . '</pattern></defs><rect width="100%" height="100%" fill="url(#wm)"/></svg>';
    $wmUri = 'data:image/svg+xml;base64,' . base64_encode($wmSvg);

    ob_start(); ?>
<style>
.gc-wrap, .gc-page { -webkit-user-select: none !important; -moz-user-select: none !important; -ms-user-select: none !important; user-select: none !important; -webkit-touch-callout: none !important; }
.gc-wrap img, .gc-page img { pointer-events: none; -webkit-user-drag: none; }
.gc-watermark { position: fixed; inset: 0; z-index: 100000; pointer-events: none; background-image: url('<?= $wmUri ?>'); background-repeat: repeat; }
.gc-print-block { display: none; }
@media print {
  body > *:not(.gc-print-block) { display: none !important; }
  .gc-print-block { display: block !important; text-align: center; padding: 120px 20px; }
}
</style>
<div class="gc-watermark" aria-hidden="true"></div>
<div class="gc-print-block">
  <h2 style="font-family:'Noto Serif Sinhala',serif;color:#4f46e5;margin-bottom:8px;">learnjapanese.jp</h2>
  <p style="font-family:'Noto Sans Sinhala',sans-serif;color:#64748b;font-size:13px;">මෙම පිටුව මුද්‍රණය කිරීමට අවසර නැත.<br>Printing this page is not permitted.</p>
</div>
<script>
(function(){
  document.addEventListener('copy', function(e){ e.preventDefault(); });
  document.addEventListener('cut', function(e){ e.preventDefault(); });
  document.addEventListener('contextmenu', function(e){ e.preventDefault(); });
  document.addEventListener('dragstart', function(e){ e.preventDefault(); });
  document.addEventListener('keydown', function(e){
    var k = (e.key || '').toLowerCase();
    var blockCombo = (e.ctrlKey || e.metaKey) && ['c','x','u','s','p'].indexOf(k) !== -1;
    var blockDevtools = e.key === 'F12' || ((e.ctrlKey || e.metaKey) && e.shiftKey && ['i','j','c'].indexOf(k) !== -1);
    if (blockCombo || blockDevtools) e.preventDefault();
  });
})();
</script>
<?php
    return ob_get_clean();
}

/** One locked <tr> for the table-style list view. */
function grammar_render_locked_row(array $c): string
{
    ob_start(); ?>
<tr class="gc-locked-row">
  <td><?= sprintf('%02d', (int)$c['card_no']) ?></td>
  <td>
    <span class="gc-lock-ic">
      <svg width="12" height="14" viewBox="0 0 24 28" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="2" y="12" width="20" height="14" rx="2" fill="#c7d2fe"/>
        <path d="M6 12V8a6 6 0 0 1 12 0v4" stroke="#a5b4fc" stroke-width="2.4" fill="none"/>
      </svg>
      <?= grammar_h($c['title_jp']) ?>
    </span>
    <div class="sub"><?= grammar_h($c['formula']) ?></div>
  </td>
  <td class="sub">🔒 Package එකක් අවශ්‍යයි</td>
</tr>
<?php
    return ob_get_clean();
}

/**
 * A locked placeholder that stands in for a full card (all-cards view / single card page).
 * The title and formula are shown as a teaser (same as before); no other content is echoed.
 */
function grammar_render_locked_card(array $c): string
{
    ob_start(); ?>
<div class="gc-locked-card">
  <div class="gc-lock-badge">
    <svg width="17" height="19" viewBox="0 0 24 28" fill="none" xmlns="http://www.w3.org/2000/svg">
      <rect x="2" y="12" width="20" height="14" rx="2" fill="#818cf8"/>
      <path d="M6 12V8a6 6 0 0 1 12 0v4" stroke="#6366f1" stroke-width="2.4" fill="none"/>
    </svg>
  </div>
  <h3><?= grammar_h($c['title_jp']) ?></h3>
  <div class="gc-locked-formula"><?= grammar_h($c['formula']) ?></div>
  <p>මෙම පාඩමේ සම්පූර්ණ අන්තර්ගතය (භාවිතය, උදාහරණ, විභාග ඉඟි) Package එකක් activate කිරීමෙන් විවෘත වේ.</p>
  <div class="gc-locked-actions">
    <a class="gc-btn-pkg" href="<?= grammar_h(grammar_upgrade_url($c['level'] ?? null)) ?>">Package Upgrade</a>
    <a class="gc-btn-buy" href="<?= grammar_h(GC_BOOK_BUY_URL) ?>" target="_blank" rel="noopener">මුද්‍රිත පොත</a>
    <button type="button" class="gc-btn-details" onclick="gcOpenBookModal()">Book Details</button>
  </div>
</div>
<?php
    return ob_get_clean();
}
