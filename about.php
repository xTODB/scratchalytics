<?php
require_once __DIR__ . '/includes/lib.php';
$navActive = 'about';
$pageTitle = 'About - ' . SITE_NAME;
require __DIR__ . '/includes/layout-top.php';
?>
<h1>About <?= e(SITE_NAME) ?></h1>
<p>Scratch statistics: look up a Scratcher, see their rank and growth, and compare two of them. It is built on the <a href="https://scratchnews.net/s/census/">ScratchCensus</a> database, so it only knows Scratchers ScratchCensus tracks. Growth history is saved daily for the top Scratchers only.</p>
<p>Planned next: studio pages, rankings for projects, studios and forums, and a downloadable sprite that links to your own page.</p>
<p class="sa-muted">A ScratchNews Site. Not affiliated with the Scratch Team.</p>
<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
