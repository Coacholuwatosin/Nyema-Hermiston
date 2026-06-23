<?php
define('SHEET_ID',  '1wj6hNhSy0Ip82YZnXBmHJp44xjzCVBQuafaXpPCB0OM');
define('CACHE_TTL', 300);
define('SITE_URL',  'https://nyemahermiston.com.au');

function slugify($s) {
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = preg_replace('/[^a-z0-9\s-]/', '', $s);
    $s = preg_replace('/[\s-]+/', '-', $s);
    return trim($s, '-');
}

function parseGvizDate($raw) {
    if (!$raw) return null;
    if (preg_match('/Date\((\d+),(\d+),(\d+)\)/', $raw, $m))
        return mktime(0, 0, 0, (int)$m[2] + 1, (int)$m[3], (int)$m[1]);
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $raw, $m))
        return mktime(0, 0, 0, (int)$m[2], (int)$m[1], (int)$m[3]);
    $t = strtotime($raw);
    return $t ?: null;
}

function formatDate($ts) {
    return $ts ? date('j F Y', $ts) : '';
}

function fetchPosts() {
    $cacheFile = __DIR__ . '/private/blog_cache.json';
    if (file_exists($cacheFile)) {
        $c = json_decode(file_get_contents($cacheFile), true);
        if ($c && isset($c['ts']) && (time() - $c['ts']) < CACHE_TTL)
            return $c['posts'];
    }

    $url = 'https://docs.google.com/spreadsheets/d/' . SHEET_ID . '/gviz/tq?tqx=out:json';
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_USERAGENT      => 'Mozilla/5.0',
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);
    if (!$raw) return null;

    if (!preg_match('/google\.visualization\.Query\.setResponse\(([\s\S]+?)\);?\s*$/', $raw, $m))
        return null;
    $json = json_decode($m[1], true);
    if (!$json) return null;

    $rows = $json['table']['rows'] ?? [];
    $COL  = ['TITLE'=>0,'DATE'=>1,'CATEGORY'=>2,'EXCERPT'=>3,'CONTENT'=>4,'IMAGE'=>5,'PUBLISHED'=>6];

    $posts = [];
    foreach ($rows as $row) {
        if (empty($row['c'])) continue;
        $cell = function($i) use ($row) {
            return isset($row['c'][$i]['v']) ? trim((string)$row['c'][$i]['v']) : '';
        };
        if (strtolower($cell($COL['PUBLISHED'])) !== 'yes') continue;
        $title = $cell($COL['TITLE']);
        if (!$title) continue;
        $ts = parseGvizDate($cell($COL['DATE']));
        $posts[] = [
            'title'    => $title,
            'slug'     => slugify($title),
            'date'     => $ts,
            'dateStr'  => formatDate($ts),
            'category' => $cell($COL['CATEGORY']),
            'excerpt'  => $cell($COL['EXCERPT']),
            'content'  => $cell($COL['CONTENT']),
            'image'    => $cell($COL['IMAGE']),
        ];
    }

    usort($posts, fn($a, $b) => ($b['date'] ?? 0) - ($a['date'] ?? 0));
    file_put_contents($cacheFile, json_encode(['ts' => time(), 'posts' => $posts]), LOCK_EX);
    return $posts;
}

$requestedSlug = trim($_GET['slug'] ?? '');
if (!$requestedSlug || !preg_match('/^[a-z0-9-]+$/', $requestedSlug)) {
    header('Location: /blog', true, 302);
    exit;
}

$posts = fetchPosts();
$post  = null;
if ($posts) {
    foreach ($posts as $p) {
        if ($p['slug'] === $requestedSlug) { $post = $p; break; }
    }
}

if (!$post) {
    http_response_code(404);
    header('Location: /blog', true, 302);
    exit;
}

function esc($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$ogTitle = $post['title'] . ' — Nyema Hermiston';
$ogDesc  = $post['excerpt'] ?: 'An article by Nyema Hermiston — Registered Nurse, Naturopath, and Homeopath.';
$ogImg   = $post['image'] ?: SITE_URL . '/assets/images/social%20sharing%20banner.png';
$ogUrl   = SITE_URL . '/blog/' . $post['slug'];
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($ogTitle) ?></title>
  <meta name="description" content="<?= esc($ogDesc) ?>">

  <meta property="og:title"       content="<?= esc($ogTitle) ?>">
  <meta property="og:description" content="<?= esc($ogDesc) ?>">
  <meta property="og:image"       content="<?= esc($ogImg) ?>">
  <meta property="og:url"         content="<?= esc($ogUrl) ?>">
  <meta property="og:type"        content="article">
  <?php if ($post['date']): ?>
  <meta property="article:published_time" content="<?= esc(date('c', $post['date'])) ?>">
  <?php endif; ?>

  <meta name="twitter:card"        content="summary_large_image">
  <meta name="twitter:title"       content="<?= esc($ogTitle) ?>">
  <meta name="twitter:description" content="<?= esc($ogDesc) ?>">
  <meta name="twitter:image"       content="<?= esc($ogImg) ?>">

  <link rel="canonical" href="<?= esc($ogUrl) ?>">

  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' fill='%230D0B2B'/><text x='16' y='23' text-anchor='middle' font-family='serif' font-weight='700' font-size='16' fill='%23C9952A'>NH</text></svg>">
  <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/apple-touch-icon.png">
  <meta name="theme-color" content="#5862a3">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400;1,600&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">

  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/nav.css">
  <link rel="stylesheet" href="/assets/css/newsletter.css">
  <link rel="stylesheet" href="/assets/css/footer.css">
  <link rel="stylesheet" href="/assets/css/responsive.css">
  <link rel="stylesheet" href="/assets/css/features.css">

  <style>
    .postHero {
      background: linear-gradient(148deg, #5460ac 0%, #3e4a9c 40%, #2d3888 70%, #1e2870 100%);
      padding: calc(88px + var(--spaceXl)) 0 var(--spaceLg);
      position: relative;
      overflow: hidden;
    }
    .postHero::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: radial-gradient(circle, rgba(255,255,255,0.03) 1px, transparent 1px);
      background-size: 28px 28px;
      pointer-events: none;
    }
    .postHeroContent { position: relative; z-index: 1; }
    .postBackLink {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.82rem;
      font-weight: 500;
      color: rgba(255,255,255,0.65);
      text-decoration: none;
      margin-bottom: var(--spaceMd);
      transition: color 0.2s;
    }
    .postBackLink:hover { color: #fff; }
    .postHeroMeta {
      display: flex;
      align-items: center;
      gap: var(--spaceXs);
      margin-bottom: var(--spaceMd);
      flex-wrap: wrap;
    }
    .postHeroCategory {
      font-family: var(--fontBody);
      font-size: 0.68rem;
      font-weight: 600;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: var(--colorSecondary);
      background: rgba(201,149,42,0.18);
      border-radius: 100px;
      padding: 3px 12px;
    }
    .postHeroDate {
      font-size: 0.82rem;
      color: rgba(255,255,255,0.55);
    }
    .postHeroTitle {
      font-family: var(--fontDisplay);
      font-size: clamp(2rem, 5vw, 3.5rem);
      font-weight: 700;
      color: #fff;
      line-height: 1.1;
      letter-spacing: -0.02em;
      margin-bottom: 0;
      max-width: 820px;
    }
    .postHeroRule {
      display: block;
      width: 44px;
      height: 3px;
      background: var(--colorSecondary);
      border-radius: 2px;
      margin-top: var(--spaceMd);
    }

    .postSection {
      background: var(--colorBackground);
      padding: var(--spaceSection) 0;
    }
    .postArticle {
      max-width: 740px;
      margin: 0 auto;
    }
    .postCoverImg {
      width: 100%;
      max-height: 480px;
      object-fit: contain;
      background: #f1f3f8;
      border-radius: 16px;
      display: block;
      margin-bottom: var(--spaceLg);
    }
    .postCoverPlaceholder {
      width: 100%;
      height: 260px;
      background: linear-gradient(145deg, #5460ac 0%, #3e4a9c 60%, #2d3888 100%);
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: var(--spaceLg);
    }
    .postCoverPlaceholder i { font-size: 3rem; color: rgba(255,255,255,0.2); }

    .postContent {
      font-size: var(--fontSizeBody);
      color: var(--colorTextOnLight);
      line-height: 1.88;
      opacity: 0.88;
    }
    .postContent p { margin-bottom: 1.1em; }
    .postContent h2, .postContent h3 {
      font-family: var(--fontDisplay);
      font-weight: 600;
      color: #5862a3;
      margin-top: 1.6em;
      margin-bottom: 0.5em;
      opacity: 1;
    }
    .postContent img {
      max-width: 100%;
      height: auto;
      display: block;
      border-radius: 10px;
      margin: 1.5em 0;
    }
    .postContent a {
      color: #5862a3;
      text-decoration: none;
      border-bottom: 1px solid rgba(88,98,163,0.30);
      transition: color 0.2s, border-color 0.2s;
    }
    .postContent a:hover { color: #3a4395; border-bottom-color: #5862a3; }

    .postShare {
      margin-top: var(--spaceLg);
      padding-top: var(--spaceMd);
      border-top: 1px solid rgba(88,98,163,0.12);
      display: flex;
      align-items: center;
      gap: var(--spaceSm);
      flex-wrap: wrap;
    }
    .postShareLabel {
      font-size: 0.78rem;
      font-weight: 600;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--colorTextOnLight);
      opacity: 0.45;
      flex-shrink: 0;
    }
    .postShareBtn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.82rem;
      font-weight: 600;
      padding: 0.42rem 1rem;
      border-radius: 100px;
      border: 1px solid rgba(88,98,163,0.22);
      background: transparent;
      color: var(--colorPrimary);
      cursor: pointer;
      text-decoration: none;
      transition: background 0.22s, color 0.22s, border-color 0.22s;
    }
    .postShareBtn:hover {
      background: var(--colorPrimary);
      color: #fff;
      border-color: var(--colorPrimary);
    }
    .postBackFooter {
      margin-top: var(--spaceLg);
      text-align: center;
    }
    .postHeroShare {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
      margin-top: var(--spaceMd);
    }
    .postHeroShareBtn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-family: var(--fontBody);
      font-size: 0.82rem;
      font-weight: 600;
      padding: 0.4rem 1rem;
      border-radius: 100px;
      border: 1px solid rgba(255,255,255,0.28);
      background: rgba(255,255,255,0.10);
      color: rgba(255,255,255,0.85);
      cursor: pointer;
      text-decoration: none;
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      transition: background 0.2s, color 0.2s, border-color 0.2s;
    }
    .postHeroShareBtn:hover {
      background: rgba(255,255,255,0.22);
      color: #fff;
      border-color: rgba(255,255,255,0.5);
    }
  </style>

  <script src="https://cdn.jsdelivr.net/npm/dompurify@3.1.6/dist/purify.min.js"></script>
</head>
<body>

  <a href="#main-content" class="skipToContent">Skip to main content</a>

  <div class="pageLoader" id="pageLoader">
    <div class="pageLoaderInner">
      <span class="pageLoaderText">Nyema Hermiston</span>
      <div class="pageLoaderBar" aria-hidden="true"><div class="pageLoaderBarFill"></div></div>
    </div>
  </div>

  <div class="scrollProgressBar" id="scrollProgressBar"></div>
  <div class="customCursor" id="customCursor"></div>

  <nav class="siteNav scrolled" id="siteNav" role="navigation" aria-label="Main navigation">
    <div class="container">
      <div class="navInner">
        <a href="/" class="navLogo" aria-label="Nyema Hermiston Home">
          <span class="navLogoText">
            <span class="navLogoName">Nyema Hermiston</span>
            <span class="navLogoTagline">RN ND Adv Dip Hom, BScHons</span>
          </span>
        </a>
        <ul class="navLinks" role="list">
          <li><a href="/"           class="navLink">Home</a></li>
          <li><a href="/about"      class="navLink">About</a></li>
          <li><a href="/books"      class="navLink">Books</a></li>
          <li><a href="/mediaPress" class="navLink">Media</a></li>
          <li><a href="/speaking"   class="navLink">Speaking</a></li>
          <li><a href="/blog"       class="navLink active">Blog</a></li>
          <li><a href="/videos"     class="navLink">Videos</a></li>
          <li><a href="/events"     class="navLink">Events</a></li>
          <li><a href="/contact"    class="navLink navCta btnPrimary">Contact</a></li>
        </ul>
        <button class="navHamburger" id="navHamburger" aria-label="Open menu" aria-expanded="false">
          <span class="navHamburgerLine"></span>
          <span class="navHamburgerLine"></span>
          <span class="navHamburgerLine"></span>
        </button>
      </div>
    </div>
  </nav>

  <div class="navOverlay" id="navOverlay" role="navigation" aria-label="Mobile navigation">
    <div class="navOverlayInner">
      <nav class="navOverlayLinks">
        <a href="/"           class="navOverlayLink">Home</a>
        <a href="/about"      class="navOverlayLink">About</a>
        <a href="/books"      class="navOverlayLink">Books</a>
        <a href="/mediaPress" class="navOverlayLink">Media</a>
        <a href="/speaking"   class="navOverlayLink">Speaking</a>
        <a href="/blog"       class="navOverlayLink">Blog</a>
        <a href="/videos"     class="navOverlayLink">Videos</a>
        <a href="/events"     class="navOverlayLink">Events</a>
        <a href="/contact"    class="navOverlayLink navOverlayLinkCta">Contact</a>
      </nav>
    </div>
  </div>

  <main id="main-content">

    <section class="postHero" aria-label="Post header">
      <div class="container postHeroContent" data-aos="fade-up">
        <a href="/blog" class="postBackLink">
          <i class="ri-arrow-left-line" aria-hidden="true"></i> Back to Blog
        </a>
        <div class="postHeroMeta">
          <?php if ($post['category']): ?>
          <span class="postHeroCategory"><?= esc($post['category']) ?></span>
          <?php endif; ?>
          <?php if ($post['dateStr']): ?>
          <span class="postHeroDate"><?= esc($post['dateStr']) ?></span>
          <?php endif; ?>
        </div>
        <h1 class="postHeroTitle"><?= esc($post['title']) ?></h1>
        <span class="postHeroRule" aria-hidden="true"></span>
        <div class="postHeroShare">
          <button class="postHeroShareBtn" id="copyLinkBtnTop" aria-label="Copy link">
            <i class="ri-links-line" aria-hidden="true"></i> Copy Link
          </button>
          <button class="postHeroShareBtn" id="nativeShareBtn" aria-label="Share">
            <i class="ri-share-forward-line" aria-hidden="true"></i> Share
          </button>
        </div>
      </div>
    </section>

    <section class="postSection">
      <div class="container">
        <article class="postArticle" data-aos="fade-up">

          <?php if ($post['image']): ?>
          <img src="<?= esc($post['image']) ?>" alt="" class="postCoverImg" loading="eager">
          <?php else: ?>
          <div class="postCoverPlaceholder" aria-hidden="true">
            <i class="ri-article-line"></i>
          </div>
          <?php endif; ?>

          <div class="postContent" id="postContent"></div>

          <div class="postShare">
            <span class="postShareLabel">Share</span>
            <button class="postShareBtn" id="copyLinkBtn">
              <i class="ri-links-line" aria-hidden="true"></i> Copy Link
            </button>
            <a class="postShareBtn"
               href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($ogUrl) ?>"
               target="_blank" rel="noopener noreferrer">
              <i class="ri-facebook-fill" aria-hidden="true"></i> Facebook
            </a>
            <a class="postShareBtn"
               href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($ogUrl) ?>"
               target="_blank" rel="noopener noreferrer">
              <i class="ri-linkedin-fill" aria-hidden="true"></i> LinkedIn
            </a>
          </div>

          <div class="postBackFooter">
            <a href="/blog" class="btnPrimary">
              <i class="ri-arrow-left-line" aria-hidden="true"></i> Back to All Articles
            </a>
          </div>

        </article>
      </div>
    </section>

  </main>

  <footer class="siteFooter" role="contentinfo">
    <div class="container">
      <div class="footerMission" aria-hidden="true">
        <span class="footerMissionQuote">Empowering parents through evidence, not fear.</span>
      </div>
      <div class="footerGrid">
        <div class="footerBrand">
          <a href="/" aria-label="Nyema Hermiston Home"><span class="footerLogoName">Nyema Hermiston</span></a>
          <span class="footerLogoTagline">RN ND Adv Dip Hom, BScHons</span>
          <p class="footerTagline">Evidence-based natural health writing for parents and practitioners who want the full picture.</p>
        </div>
        <nav aria-label="Footer navigation">
          <span class="footerColHeading">Explore</span>
          <ul class="footerLinks" role="list">
            <li><a href="/"           class="footerLink">Home</a></li>
            <li><a href="/about"      class="footerLink">About</a></li>
            <li><a href="/books"      class="footerLink">Books</a></li>
            <li><a href="/mediaPress" class="footerLink">Media &amp; Press</a></li>
            <li><a href="/speaking"   class="footerLink">Speaking</a></li>
            <li><a href="/blog"       class="footerLink">Blog</a></li>
            <li><a href="/events"     class="footerLink">Events</a></li>
            <li><a href="/contact"    class="footerLink">Contact</a></li>
          </ul>
        </nav>
        <div>
          <span class="footerColHeading">Affiliations</span>
          <ul class="footerLinks" role="list">
            <li><a href="https://karunahealthcare.com.au" class="footerLink" target="_blank" rel="noopener noreferrer">Karuna Health Care</a></li>
          </ul>
        </div>
        <div>
          <span class="footerColHeading">Connect</span>
          <div class="footerSocial">
            <a class="footerSocialLink" href="https://www.instagram.com/autism_research_for_parents/" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="ri-instagram-fill" aria-hidden="true"></i></a>
            <a class="footerSocialLink" href="https://web.facebook.com/people/Nyema-Hermiston-Author/61579045115978/" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="ri-facebook-fill" aria-hidden="true"></i></a>
            <a class="footerSocialLink" href="https://www.goodreads.com/author/show/7400285.Nyema_Hermiston" target="_blank" rel="noopener noreferrer" aria-label="Goodreads"><i class="ri-book-open-fill" aria-hidden="true"></i></a>
            <a class="footerSocialLink" href="https://www.amazon.com/author/nyemahermiston" target="_blank" rel="noopener noreferrer" aria-label="Amazon"><i class="ri-amazon-fill" aria-hidden="true"></i></a>
          </div>
        </div>
      </div>
      <div class="footerBottom">
        <span class="footerCopyright">© 2026 Nyema Hermiston. All rights reserved.</span>
        <div class="footerLegal">
          <a href="/privacy" class="footerLegalLink">Privacy Policy</a>
          <span class="footerLegalSep" aria-hidden="true"></span>
          <a href="https://tubedra.com" class="footerLegalLink" target="_blank" rel="noopener noreferrer">Site by Tubedra</a>
        </div>
      </div>
    </div>
  </footer>

  <button class="backToTop" id="backToTop" aria-label="Back to top">↑</button>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script src="/assets/js/main.js"></script>
  <script src="/assets/js/navScroll.js"></script>
  <script src="/assets/js/features.js"></script>

  <script>
    (function() {
      var raw = <?= json_encode($post['content']) ?>;
      var el  = document.getElementById('postContent');
      if (/<[a-z][\s\S]*>/i.test(raw)) {
        el.innerHTML = DOMPurify.sanitize(raw, {
          ADD_TAGS:  ['iframe'],
          ADD_ATTR:  ['target', 'allowfullscreen', 'frameborder', 'allow'],
          ALLOWED_URI_REGEXP: /^(?:https?|mailto):/i
        });
      } else {
        function esc(s) {
          return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }
        el.innerHTML = '<p>' + esc(raw).replace(/\n\n+/g,'</p><p>').replace(/\n/g,'<br>') + '</p>';
      }
    })();

    var postUrl   = <?= json_encode($ogUrl) ?>;
    var postTitle = <?= json_encode($ogTitle) ?>;

    function copyToClipboard(btn) {
      if (navigator.clipboard) {
        navigator.clipboard.writeText(postUrl).then(function() {
          btn.innerHTML = '<i class="ri-check-line" aria-hidden="true"></i> Copied!';
          setTimeout(function() { btn.innerHTML = '<i class="ri-links-line" aria-hidden="true"></i> Copy Link'; }, 2000);
        });
      } else {
        var inp = document.createElement('input');
        inp.value = postUrl;
        document.body.appendChild(inp);
        inp.select();
        document.execCommand('copy');
        document.body.removeChild(inp);
        btn.innerHTML = '<i class="ri-check-line" aria-hidden="true"></i> Copied!';
        setTimeout(function() { btn.innerHTML = '<i class="ri-links-line" aria-hidden="true"></i> Copy Link'; }, 2000);
      }
    }

    document.getElementById('copyLinkBtn').addEventListener('click', function() { copyToClipboard(this); });
    document.getElementById('copyLinkBtnTop').addEventListener('click', function() { copyToClipboard(this); });

    document.getElementById('nativeShareBtn').addEventListener('click', function() {
      if (navigator.share) {
        navigator.share({ title: postTitle, url: postUrl });
      } else {
        copyToClipboard(document.getElementById('copyLinkBtnTop'));
      }
    });
  </script>

  <div class="cookieBanner" id="cookieBanner" role="region" aria-label="Cookie consent">
    <p class="cookieBannerHeading">We use cookies</p>
    <p class="cookieBannerText">
      This site uses cookies to improve your experience and analyse anonymous traffic.
      <a href="/privacy">Privacy Policy</a>
    </p>
    <div class="cookieBannerActions">
      <button class="cookieBannerAccept">Accept All</button>
      <button class="cookieBannerDecline">Decline</button>
    </div>
  </div>

  <div class="newsletterPopup" id="newsletterPopup" role="dialog" aria-modal="true" aria-labelledby="popupHeadline">
    <div class="newsletterPopupOverlay" id="newsletterPopupOverlay"></div>
    <div class="newsletterPopupCard">
      <button class="newsletterPopupClose" id="newsletterPopupClose" aria-label="Close newsletter popup">
        <i class="ri-close-line" aria-hidden="true"></i>
      </button>
      <div class="newsletterPopupIcon" aria-hidden="true"><i class="ri-mail-open-line"></i></div>
      <span class="newsletterPopupLabel">Stay Connected</span>
      <h2 class="newsletterPopupHeadline" id="popupHeadline">Discover Nyema's Latest Research</h2>
      <p class="newsletterPopupSubtext">Join the community to receive evidence-based natural health insights directly in their inbox.</p>
      <div class="newsletterPopupBenefits" aria-label="What you receive">
        <span><i class="ri-check-line" aria-hidden="true"></i> Research Insights</span>
        <span><i class="ri-check-line" aria-hidden="true"></i> Clinical Guidance</span>
        <span><i class="ri-check-line" aria-hidden="true"></i> Book Updates</span>
      </div>
      <form class="newsletterPopupForm" id="newsletterPopupForm" novalidate>
        <label for="newsletterPopupEmail" class="srOnly">Email Address</label>
        <input type="email" id="newsletterPopupEmail" name="email" class="newsletterPopupInput" placeholder="Your email address" required autocomplete="email">
        <button type="submit" class="newsletterPopupSubmit">Subscribe Now</button>
      </form>
      <div class="newsletterPopupSuccess" id="newsletterPopupSuccess" role="status">
        Thank you for subscribing. You will hear from Nyema soon.
      </div>
      <p class="newsletterPopupPrivacy"><i class="ri-lock-line" aria-hidden="true"></i> No spam. Unsubscribe anytime.</p>
      <span class="newsletterPopupSkip" id="newsletterPopupSkip">No thanks</span>
    </div>
  </div>

</body>
</html>
