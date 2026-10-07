<?php
require_once __DIR__ . '/includes/lib.php';
$a = trim((string)($_GET['a'] ?? ''));
$b = trim((string)($_GET['b'] ?? ''));
$navActive = 'compare';
$pageTitle = 'Compare Scratchers - ' . SITE_NAME;
$da = $a !== '' ? getUserBundle($a) : null;
$db = $b !== '' ? getUserBundle($b) : null;
$ua = $da['user'] ?? null; $ub = $db['user'] ?? null;
if ($ua && $ub) {
    $pageTitle = $ua['username'] . ' vs ' . $ub['username'] . ' - ' . SITE_NAME;
    $pageDesc = $ua['username'] . ' (' . number_format((int)$ua['followers']) . ' followers) vs ' . $ub['username'] . ' (' . number_format((int)$ub['followers']) . ' followers). Who catches up, and when?';
}
require __DIR__ . '/includes/layout-top.php';
?>
<h1>Compare two Scratchers</h1>
<form class="sa-search" method="get" action="<?= e(url('compare')) ?>">
    <input type="text" name="a" value="<?= e($a) ?>" placeholder="First username" maxlength="20" autocapitalize="off" required>
    <input type="text" name="b" value="<?= e($b) ?>" placeholder="Second username" maxlength="20" autocapitalize="off" required>
    <button type="submit">Compare</button>
</form>
<?php if ($a !== '' && $b !== ''):
    if (!$ua || !$ub): ?>
<p class="sa-muted"><?= !$ua ? e($a) . ' ' : '' ?><?= !$ua && !$ub ? 'and ' : '' ?><?= !$ub ? e($b) . ' ' : '' ?>not tracked yet, or ScratchCensus is busy.</p>
<?php else:
    $ha = $da['history'] ?? []; $hb = $db['history'] ?? [];
    $pa = pacePerDay($ha); $pb = pacePerDay($hb);
    $fa = (int)$ua['followers']; $fb = (int)$ub['followers'];
    $lead = $fa >= $fb ? $ua : $ub; $trail = $fa >= $fb ? $ub : $ua;
    $gap = abs($fa - $fb);
    $pl = $fa >= $fb ? $pa : $pb; $pt = $fa >= $fb ? $pb : $pa;
?>
<table class="sa-cmp">
    <tr><th></th><th class="c1"><a href="<?= e(url('u/' . rawurlencode($ua['username']))) ?>"><?= e($ua['username']) ?></a></th><th class="c2"><a href="<?= e(url('u/' . rawurlencode($ub['username']))) ?>"><?= e($ub['username']) ?></a></th></tr>
    <tr><td>Followers</td><td><?= number_format($fa) ?></td><td><?= number_format($fb) ?></td></tr>
    <tr><td>Rank</td><td>#<?= number_format((int)$ua['rank']) ?></td><td>#<?= number_format((int)$ub['rank']) ?></td></tr>
    <tr><td>Country</td><td><?= e($ua['country'] ?? '-') ?></td><td><?= e($ub['country'] ?? '-') ?></td></tr>
    <tr><td>Per day</td><td><?= $pa === null ? '-' : ($pa >= 0 ? '+' : '-') . number_format(abs($pa), 1) ?></td><td><?= $pb === null ? '-' : ($pb >= 0 ? '+' : '-') . number_format(abs($pb), 1) ?></td></tr>
</table>
<section class="sa-card"><h2>The gap</h2>
<p><?= e($lead['username']) ?> is ahead by <b><?= number_format($gap) ?></b> follower<?= $gap === 1 ? '' : 's' ?>.
<?php if ($gap === 0): ?>They are level.
<?php elseif ($pl !== null && $pt !== null && $pt > $pl): $days = (int)ceil($gap / ($pt - $pl)); ?>
At the current pace <?= e($trail['username']) ?> catches up in about <?= number_format($days) ?> day<?= $days === 1 ? '' : 's' ?>.
<?php elseif ($pl !== null && $pt !== null): ?>At the current pace the gap stays or grows.
<?php else: ?>Not enough history yet to project this.<?php endif; ?></p>
<?php if (count($ha) >= 2 || count($hb) >= 2): ?>
<?= graphSvg([['name' => $ua['username'], 'hist' => $ha, 'cls' => 'sa-l1'], ['name' => $ub['username'], 'hist' => $hb, 'cls' => 'sa-l2']]) ?>
<p class="sa-muted"><span class="sa-key k1"></span><?= e($ua['username']) ?> <span class="sa-key k2"></span><?= e($ub['username']) ?></p>
<?php endif; ?></section>
<?php endif; endif; ?>
<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
