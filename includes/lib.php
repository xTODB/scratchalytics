<?php
// Scratchalytics: shared helpers. Data comes from the ScratchCensus public API (no database of its own yet).
// config.php (NOT in the repo) can define SITE_BASE, CENSUS_API_BASE, CENSUS_CACHE_SEC and CENSUS_API_KEY before this loads.
// CENSUS_API_KEY must equal API_TRUSTED_KEY in ScratchCensus's config.php, or every page view shares one 60/min rate limit.
@include __DIR__ . '/../config.php';
defined('SITE_NAME')       || define('SITE_NAME', 'Scratchalytics');   // working name, change here only
defined('SITE_BASE')       || define('SITE_BASE', '/s/scratchalytics'); // URL prefix the site lives under, '' for a domain root
defined('CENSUS_API_BASE') || define('CENSUS_API_BASE', 'https://scratchnews.net/s/census/api');
defined('CENSUS_CACHE_SEC')|| define('CENSUS_CACHE_SEC', 300);
defined('CENSUS_API_KEY')  || define('CENSUS_API_KEY', '');

function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function isValidName(string $n): bool { return (bool)preg_match('/^[A-Za-z0-9_-]{3,20}$/', $n); }
function url(string $p = ''): string { return SITE_BASE . '/' . ltrim($p, '/'); }

// Census answers are cached in temp files. Speed rules:
//  - fresh copy (CENSUS_CACHE_SEC, or 15 minutes for the lists/stats that change slowly): used as is
//  - stale copy (up to CENSUS_STALE_SEC): shown instantly, and refreshed AFTER the page is sent
//  - no copy: fetched now, all the page's calls in parallel (not one after another)
//  - a failed call is remembered for 30s so a slow Census does not stall every visit
defined('CENSUS_STALE_SEC') || define('CENSUS_STALE_SEC', 86400);

function censusTtl(string $path): int {
    $slow = strpos($path, '/users?') === 0 || strpos($path, '/growth') === 0 || strpos($path, '/stats') === 0 || strpos($path, '/studios?') === 0;
    return $slow ? max((int)CENSUS_CACHE_SEC, 900) : (int)CENSUS_CACHE_SEC;
}
function censusFile(string $path): string { return sys_get_temp_dir() . '/scratchalytics_' . md5(CENSUS_API_BASE . $path); }

// Parallel GET of several API paths. Returns path => ['code'=>int,'body'=>string|false].
function censusFetchNow(array $paths): array {
    $mh = curl_multi_init();
    $hs = [];
    foreach ($paths as $p) {
        $ch = curl_init(CENSUS_API_BASE . $p);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 6, CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true, CURLOPT_ENCODING => '', CURLOPT_USERAGENT => SITE_NAME . ' (ScratchNews Site)',
            CURLOPT_HTTPHEADER => CENSUS_API_KEY !== '' ? ['X-Census-Key: ' . CENSUS_API_KEY] : []]);
        curl_multi_add_handle($mh, $ch);
        $hs[$p] = $ch;
    }
    do {
        $st = curl_multi_exec($mh, $running);
        if ($running) curl_multi_select($mh, 1.0);
    } while ($running && $st === CURLM_OK);
    $out = [];
    foreach ($hs as $p => $ch) {
        $out[$p] = ['code' => (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE), 'body' => curl_multi_getcontent($ch)];
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
    return $out;
}

// Fetches paths, stores good answers, marks failures. Returns path => decoded array|null.
function censusFetchAndStore(array $paths): array {
    $res = [];
    foreach (censusFetchNow($paths) as $p => $r) {
        $j = ($r['body'] !== false && $r['code'] === 200) ? json_decode((string)$r['body'], true) : null;
        if (is_array($j)) { @file_put_contents(censusFile($p), $r['body'], LOCK_EX); $res[$p] = $j; }
        else { @touch(censusFile($p) . '.neg'); $res[$p] = null; }
    }
    return $res;
}

// Several API paths at once: ['/stats', '/users?limit=10'] => ['/stats' => [...], ...] (null for a failed one).
function censusGetMany(array $paths): array {
    $out = []; $need = []; $later = [];
    foreach ($paths as $p) {
        $f = censusFile($p);
        $age = is_file($f) ? time() - (int)@filemtime($f) : null;
        $cached = $age !== null ? json_decode((string)@file_get_contents($f), true) : null;
        if (is_array($cached) && $age < censusTtl($p)) { $out[$p] = $cached; continue; }
        if (is_array($cached) && $age < CENSUS_STALE_SEC) { $out[$p] = $cached; $later[] = $p; continue; }
        if (is_file($f . '.neg') && time() - (int)@filemtime($f . '.neg') < 30) { $out[$p] = null; continue; }
        $need[] = $p;
    }
    if ($need) foreach (censusFetchAndStore($need) as $p => $j) $out[$p] = $j;
    if ($later) {
        register_shutdown_function(function () use ($later) {
            if (function_exists('fastcgi_finish_request')) @fastcgi_finish_request();
            elseif (function_exists('litespeed_finish_request')) @litespeed_finish_request();
            $todo = [];
            foreach ($later as $p) {
                $lock = @fopen(censusFile($p) . '.lock', 'c');
                if ($lock && flock($lock, LOCK_EX | LOCK_NB)) $todo[$p] = $lock; // one refresher per path
            }
            if ($todo) censusFetchAndStore(array_keys($todo));
        });
    }
    return $out;
}

// GET one Census API path (e.g. "/users/griffpatch/history?days=90"). Returns decoded JSON, or null on any failure.
function censusGet(string $path): ?array {
    return censusGetMany([$path])[$path] ?? null;
}

function getUserBundle(string $name): ?array {
    if (!isValidName($name)) return null;
    return censusGet('/users/' . rawurlencode($name) . '/history?days=90');
}

// Two users at once (compare page): both API calls run in parallel.
function getUserBundles(string $a, string $b): array {
    $pa = isValidName($a) ? '/users/' . rawurlencode($a) . '/history?days=90' : null;
    $pb = isValidName($b) ? '/users/' . rawurlencode($b) . '/history?days=90' : null;
    $r = censusGetMany(array_values(array_filter([$pa, $pb])));
    return [$pa ? ($r[$pa] ?? null) : null, $pb ? ($r[$pb] ?? null) : null];
}

// One studio with its rank (no daily history exists for studios, only the 2-day change). Null if not a valid id or not tracked.
function getStudio(string $id): ?array {
    if (!preg_match('/^\d{1,10}$/', $id)) return null;
    $j = censusGet('/studios/' . $id);
    return $j['studio'] ?? null;
}

// One page of the studio leaderboard, or of a title search when $q is set. Returns ['paging'=>..., 'results'=>[...]] or null.
function getStudiosPage(int $page, string $q = '', int $limit = 25): ?array {
    $qs = 'page=' . $page . '&limit=' . $limit . ($q !== '' ? '&q=' . rawurlencode($q) : '');
    return censusGet('/studios?' . $qs);
}

// "+12" / "-3" / "0" for a change value; "-" when null.
function signedNum(?int $n): string {
    if ($n === null) return '-';
    return ($n > 0 ? '+' : ($n < 0 ? '-' : '')) . number_format(abs($n));
}

// Straight-line pace from the first to the last saved point: followers per day, or null with under 2 days of data.
function pacePerDay(array $hist): ?float {
    if (count($hist) < 2) return null;
    $a = $hist[0]; $b = $hist[count($hist) - 1];
    $span = (strtotime($b['day']) - strtotime($a['day'])) / 86400;
    return $span >= 1 ? ($b['followers'] - $a['followers']) / $span : null;
}

// Line chart for 1 or 2 series, drawn as inline SVG. $series: [['name'=>, 'hist'=>[['day','followers']], 'cls'=>'sa-l1'], ...]
function graphSvg(array $series): string {
    $all = [];
    foreach ($series as $s) foreach ($s['hist'] as $h) $all[] = $h;
    if (count($all) < 2) return '';
    $W = 640; $H = 240; $pl = 60; $pr = 14; $pt = 14; $pb = 30;
    $vals = array_column($all, 'followers');
    $min = min($vals); $max = max($vals);
    if ($max === $min) { $min--; $max++; }
    $days = array_map(fn($h) => strtotime($h['day']), $all);
    $t0 = min($days); $t1 = max($days); $ts = max(1, $t1 - $t0);
    $o = '<svg class="sa-graph" viewBox="0 0 ' . $W . ' ' . $H . '" role="img" aria-label="Followers over time">';
    $o .= '<line class="sa-grid" x1="' . $pl . '" y1="' . $pt . '" x2="' . ($W - $pr) . '" y2="' . $pt . '"/><line class="sa-grid" x1="' . $pl . '" y1="' . ($H - $pb) . '" x2="' . ($W - $pr) . '" y2="' . ($H - $pb) . '"/>';
    $o .= '<text class="sa-axis" x="' . ($pl - 6) . '" y="' . ($pt + 4) . '" text-anchor="end">' . number_format($max) . '</text><text class="sa-axis" x="' . ($pl - 6) . '" y="' . ($H - $pb + 4) . '" text-anchor="end">' . number_format($min) . '</text>';
    $o .= '<text class="sa-axis" x="' . $pl . '" y="' . ($H - 8) . '">' . e(date('Y-m-d', $t0)) . '</text><text class="sa-axis" x="' . ($W - $pr) . '" y="' . ($H - 8) . '" text-anchor="end">' . e(date('Y-m-d', $t1)) . '</text>';
    foreach ($series as $s) {
        if (count($s['hist']) < 2) continue;
        $pts = [];
        foreach ($s['hist'] as $h) {
            $x = $pl + (strtotime($h['day']) - $t0) / $ts * ($W - $pl - $pr);
            $y = $pt + (1 - ($h['followers'] - $min) / ($max - $min)) * ($H - $pt - $pb);
            $pts[] = round($x, 1) . ',' . round($y, 1);
        }
        $o .= '<polyline class="sa-line ' . e($s['cls']) . '" points="' . implode(' ', $pts) . '"><title>' . e($s['name']) . '</title></polyline>';
    }
    return $o . '</svg>';
}

function picHtml(?string $src): string {
    return $src ? '<img class="sa-pic" src="' . e($src) . '" alt="" width="64" height="64" loading="lazy" onerror="this.style.visibility=\'hidden\'">' : '<span class="sa-pic ph"></span>';
}
