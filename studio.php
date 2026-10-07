<?php
require_once __DIR__ . '/includes/lib.php';
$id = trim((string)($_GET['id'] ?? ''));
$st = getStudio($id);
$navActive = 'studios';
$pageTitle = ($st ? $st['title'] . ' - ' : '') . SITE_NAME;
if ($st) $pageDesc = $st['title'] . ' has ' . number_format((int)$st['followers']) . ' followers on Scratch (studio rank #' . number_format((int)$st['rank']) . ') and ' . number_format((int)$st['projects']) . ' projects.';
if (!$st) http_response_code(404);
require __DIR__ . '/includes/layout-top.php';
if (!$st): ?>
<h1>Studio not found</h1>
<p class="sa-muted">Studio "<?= e($id) ?>" is not tracked yet, or ScratchCensus is busy. <a href="https://scratchnews.net/s/census/crawl">Add it on the Census Crawl page</a>, then come back. Or <a href="<?= e(url('studios')) ?>">browse the top studios</a>.</p>
<?php else:
    $created = !empty($st['created_on']) && strtotime($st['created_on']) ? date('M j, Y', strtotime($st['created_on'])) : null;
?>
<div class="sa-head"><?= picHtml($st['thumbnail'] ?? null) ?><div><h1><?= e($st['title']) ?></h1>
<p class="sa-muted"><?= !empty($st['host']) ? 'Host: <a href="' . e(url('u/' . rawurlencode($st['host']))) . '">' . e($st['host']) . '</a> &middot; ' : '' ?><a href="<?= e($st['url']) ?>" target="_blank" rel="noopener">View on Scratch</a></p></div></div>
<div class="sa-stats">
    <div class="sa-stat"><span>Followers</span><b><?= number_format((int)$st['followers']) ?></b></div>
    <div class="sa-stat"><span>Rank</span><b>#<?= number_format((int)$st['rank']) ?></b></div>
    <div class="sa-stat"><span>Projects</span><b><?= number_format((int)$st['projects']) ?></b></div>
    <div class="sa-stat"><span>Access</span><b><?= !empty($st['open_to_all']) ? 'Open' : 'Closed' ?></b></div>
    <?php if ($st['change'] !== null): ?><div class="sa-stat"><span>Last 2 days</span><b class="<?= (int)$st['change'] > 0 ? 'sa-up' : '' ?>"><?= e(signedNum((int)$st['change'])) ?></b></div><?php endif; ?>
</div>
<section class="sa-card"><h2>About this studio</h2>
<p class="sa-muted"><?= $created ? 'Created ' . e($created) . '. ' : '' ?>Studios do not have a saved growth history yet, so only the change over the last 2 days is shown.</p></section>
<?php endif; ?>
<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
