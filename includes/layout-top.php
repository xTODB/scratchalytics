<?php
// Set before including: $pageTitle, $pageDesc, $navActive ('home'|'compare'|'studios'|'about'|''), optional $ogImage (path under the site, default share card)
require_once __DIR__ . '/lib.php';
$pageTitle = $pageTitle ?? SITE_NAME;
$pageDesc = $pageDesc ?? 'Scratch statistics: ranks, growth and comparisons.';
$navActive = $navActive ?? '';
$ogImage = $ogImage ?? 'assets/og-default.png';
$origin = defined('SITE_ORIGIN') ? SITE_ORIGIN : 'https://scratchnews.net';
$css = (int)@filemtime(__DIR__ . '/../assets/style.css');
$nav = ['home' => ['', 'Home'], 'compare' => ['compare', 'Compare'], 'studios' => ['studios', 'Studios'], 'about' => ['about', 'About']];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDesc) ?>">
<link rel="icon" href="<?= e(url('assets/favicon.ico')) ?>" sizes="48x48">
<link rel="icon" href="<?= e(url('assets/icon.svg')) ?>" type="image/svg+xml" sizes="any">
<link rel="icon" href="<?= e(url('assets/favicon-192.png')) ?>" type="image/png" sizes="192x192">
<link rel="apple-touch-icon" href="<?= e(url('assets/apple-touch-icon.png')) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($pageDesc) ?>">
<meta property="og:url" content="<?= e($origin . ($_SERVER['REQUEST_URI'] ?? '')) ?>">
<meta property="og:image" content="<?= e($origin . url($ogImage)) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>?v=<?= $css ?>">
</head>
<body>
<header class="sa-top">
    <a class="sa-brand" href="<?= e(url()) ?>"><img class="sa-logo" src="<?= e(url('assets/icon.svg')) ?>" alt="" width="28" height="28"><span><?= e(SITE_NAME) ?></span></a>
    <nav class="sa-nav">
        <?php foreach ($nav as $k => [$href, $label]): ?><a href="<?= e(url($href)) ?>"<?= $navActive === $k ? ' class="on"' : '' ?>><?= e($label) ?></a><?php endforeach; ?>
    </nav>
</header>
<main class="sa-main">
