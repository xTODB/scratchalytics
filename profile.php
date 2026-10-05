<?php
require_once __DIR__ . '/includes/lib.php';
$name = trim((string)($_GET['u'] ?? ''));
$d = getUserBundle($name);
$u = $d['user'] ?? null;
$navActive = 'home';
$pageTitle = ($u ? $u['username'] . ' - ' : '') . SITE_NAME;
if (!$u) http_response_code(404);
require __DIR__ . '/includes/layout-top.php';
if (!$u): ?>
<h1>Scratcher not found</h1>
<p class="sa-muted">"<?= e($name) ?>" is not tracked yet, or ScratchCensus is busy. <a href="https://scratchnews.net/s/census/crawl">Add them on the Census Crawl page</a>, then come back.</p>
<?php else:
    $hist = $d['history'] ?? [];
    $pace = pacePerDay($hist);
    $f = (int)$u['followers'];
?>
<div class="sa-head"><?= picHtml($u['picture'] ?? null) ?><div><h1><?= e($u['username']) ?></h1>
<p class="sa-muted"><?= !empty($u['country']) ? e($u['country']) . ' &middot; ' : '' ?><a href="<?= e($u['profile_url']) ?>" target="_blank" rel="noopener">View on Scratch</a> &middot; <a href="<?= e(url('compare/' . rawurlencode($u['username']))) ?>">Compare</a></p></div></div>
<div class="sa-stats">
    <div class="sa-stat"><span>Followers</span><b><?= number_format($f) ?></b></div>
    <div class="sa-stat"><span>Rank</span><b>#<?= number_format((int)$u['rank']) ?></b></div>
    <?php if (!empty($d['country_rank'])): ?><div class="sa-stat"><span>In <?= e($u['country']) ?></span><b>#<?= number_format((int)$d['country_rank']) ?></b></div><?php endif; ?>
    <?php if ($pace !== null): ?><div class="sa-stat"><span>Per day</span><b><?= ($pace >= 0 ? '+' : '-') . number_format(abs($pace), abs($pace) < 10 ? 1 : 0) ?></b></div><?php endif; ?>
</div>
<?php if (count($hist) >= 2): ?>
<section class="sa-card"><h2>Followers over time</h2><?= graphSvg([['name' => $u['username'], 'hist' => $hist, 'cls' => 'sa-l1']]) ?>
<?php if ($pace !== null): ?><p class="sa-muted">At this pace: about <?= number_format((int)round($f + $pace * 30)) ?> followers in 30 days.</p><?php endif; ?></section>
<?php else: ?>
<section class="sa-card"><h2>Growth history</h2><p class="sa-muted">No history yet. Only the top Scratchers get a saved daily count, and graphs need two days of data.</p></section>
<?php endif; endif; ?>
<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
