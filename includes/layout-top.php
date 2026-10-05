<?php
// Set before including: $pageTitle, $pageDesc, $navActive ('home'|'compare'|'about'|'')
require_once __DIR__ . '/lib.php';
$pageTitle = $pageTitle ?? SITE_NAME;
$pageDesc = $pageDesc ?? 'Scratch statistics: ranks, growth and comparisons.';
$navActive = $navActive ?? '';
$css = (int)@filemtime(__DIR__ . '/../assets/style.css');
$nav = ['home' => ['', 'Home'], 'compare' => ['compare', 'Compare'], 'about' => ['about', 'About']];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDesc) ?>">
<link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>?v=<?= $css ?>">
</head>
<body>
<header class="sa-top">
    <a class="sa-brand" href="<?= e(url()) ?>"><?= e(SITE_NAME) ?></a>
    <nav class="sa-nav">
        <?php foreach ($nav as $k => [$href, $label]): ?><a href="<?= e(url($href)) ?>"<?= $navActive === $k ? ' class="on"' : '' ?>><?= e($label) ?></a><?php endforeach; ?>
    </nav>
</header>
<main class="sa-main">
