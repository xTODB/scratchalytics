<?php
require_once __DIR__ . '/includes/lib.php';
$pageTitle = SITE_NAME . ' - Scratch statistics';
$navActive = 'home';
$home = censusGetMany(['/users?limit=10', '/growth?type=users&dir=up&limit=10', '/stats']); // one parallel round
$top = $home['/users?limit=10'];
$gain = $home['/growth?type=users&dir=up&limit=10'];
$stats = $home['/stats'];
require __DIR__ . '/includes/layout-top.php';
?>
<h1>Look up any Scratcher</h1>
<p class="sa-muted"><?= $stats ? number_format((int)$stats['users']) . ' Scratchers tracked.' : '' ?> Rank, growth graph and projection, or compare two Scratchers.</p>
<form class="sa-search" onsubmit="var n=this.n.value.trim(); if(n) location.href='<?= e(url('u/')) ?>'+encodeURIComponent(n); return false;">
    <input type="text" name="n" placeholder="Scratch username" maxlength="20" autocomplete="off" autocapitalize="off" required>
    <button type="submit">Look up</button>
</form>
<div class="sa-cols">
    <section class="sa-card"><h2>Most followed</h2>
    <?php if ($top): ?><ol><?php foreach ($top['results'] as $r): ?><li><a href="<?= e(url('u/' . rawurlencode($r['username']))) ?>"><?= e($r['username']) ?></a> <span class="sa-muted"><?= number_format((int)$r['followers']) ?></span></li><?php endforeach; ?></ol>
    <?php else: ?><p class="sa-muted">ScratchCensus did not answer. Try again in a moment.</p><?php endif; ?></section>
    <section class="sa-card"><h2>Biggest gains</h2>
    <?php if ($gain && $gain['results']): ?><ol><?php foreach ($gain['results'] as $r): ?><li><a href="<?= e(url('u/' . rawurlencode($r['username']))) ?>"><?= e($r['username']) ?></a> <span class="sa-up">+<?= number_format((int)$r['change']) ?></span></li><?php endforeach; ?></ol>
    <?php else: ?><p class="sa-muted">No recent gains to show.</p><?php endif; ?></section>
</div>
<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
