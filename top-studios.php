<?php
require_once __DIR__ . '/includes/lib.php';
$q = trim((string)($_GET['q'] ?? ''));
if (strlen($q) > 60) $q = substr($q, 0, 60);
if (preg_match('/^\d{1,10}$/', $q)) { header('Location: ' . url('studio/' . $q)); exit; }
$page = min(500, max(1, (int)($_GET['page'] ?? 1)));
$res = getStudiosPage($page, $q);
$navActive = 'studios';
$pageTitle = ($q !== '' ? 'Studios matching "' . $q . '" - ' : 'Top Scratch studios - ') . SITE_NAME;
$pageDesc = 'The Scratch studio leaderboard: followers, rank and projects for every studio ScratchCensus tracks.';
require __DIR__ . '/includes/layout-top.php';
$qp = $q !== '' ? '&q=' . rawurlencode($q) : '';
?>
<h1><?= $q !== '' ? 'Studio search' : 'Top studios' ?></h1>
<form class="sa-search" method="get" action="<?= e(url('studios')) ?>">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Studio title or studio ID" maxlength="60" autocapitalize="off">
    <button type="submit">Search</button>
</form>
<?php if (!$res): ?>
<p class="sa-muted">ScratchCensus is busy. Try again in a minute.</p>
<?php elseif (empty($res['results'])): ?>
<p class="sa-muted">No tracked studio matches "<?= e($q) ?>". <a href="https://scratchnews.net/s/census/crawl">Add one on the Census Crawl page</a>.</p>
<?php else: $pg = $res['paging']; ?>
<p class="sa-muted"><?= number_format((int)$pg['total']) ?> studio<?= (int)$pg['total'] === 1 ? '' : 's' ?><?= $q !== '' ? ' found' : ' tracked' ?>.</p>
<div style="overflow-x:auto"><table class="sa-tbl">
    <tr><th>#</th><th>Studio</th><th>Followers</th><th>Projects</th><th>2 days</th></tr>
<?php foreach ($res['results'] as $r): ?>
    <tr><td><?= number_format((int)$r['rank']) ?></td><td><a href="<?= e(url('studio/' . (int)$r['id'])) ?>"><?= e($r['title']) ?></a></td><td><?= number_format((int)$r['followers']) ?></td><td><?= number_format((int)$r['projects']) ?></td><td><?= e(signedNum($r['change'])) ?></td></tr>
<?php endforeach; ?>
</table></div>
<div class="sa-pager">
    <span><?php if ($page > 1): ?><a href="<?= e(url('studios') . '?page=' . ($page - 1) . $qp) ?>">&larr; Previous</a><?php endif; ?></span>
    <span class="sa-muted">Page <?= number_format($page) ?> of <?= number_format((int)$pg['total_pages']) ?></span>
    <span><?php if ($page < (int)$pg['total_pages']): ?><a href="<?= e(url('studios') . '?page=' . ($page + 1) . $qp) ?>">Next &rarr;</a><?php endif; ?></span>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
