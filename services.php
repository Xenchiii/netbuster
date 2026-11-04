<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Services';
$q = trim($_GET['q'] ?? '');
$plans = rows("SELECT * FROM tbl_plans ORDER BY (status='Available' AND slots>0) DESC, price ASC");
$cats = ['Fiber', 'DSL', 'Wireless', 'Business', 'Add-on'];
include __DIR__ . '/includes/header.php';
?>
<h1>Services &amp; solutions</h1>
<div class="filters card">
  <input type="search" id="q" placeholder="Search for a service (e.g. fiber, gamer, business)…" value="<?= e($q) ?>">
  <select id="cat">
    <option value="">All categories</option>
    <?php foreach ($cats as $c): ?><option><?= e($c) ?></option><?php endforeach; ?>
  </select>
  <select id="sort">
    <option value="default">Sort: Recommended</option>
    <option value="price-asc">Price: low to high</option>
    <option value="price-desc">Price: high to low</option>
    <option value="speed-desc">Speed: fastest first</option>
    <option value="name">Name A–Z</option>
  </select>
  <label class="check"><input type="checkbox" id="onlyAvail"> Available only</label>
</div>
<p class="muted" id="resultCount"></p>
<div class="grid" id="grid">
<?php foreach ($plans as $i => $p): $av = plan_available($p); ?>
  <article class="card plan" data-name="<?= e(strtolower($p['plan_name'] . ' ' . $p['description'])) ?>" data-title="<?= e($p['plan_name']) ?>"
           data-cat="<?= e($p['category']) ?>" data-price="<?= (float)$p['price'] ?>" data-speed="<?= (int)$p['speed_mbps'] ?>"
           data-avail="<?= $av ? 1 : 0 ?>" data-order="<?= $i ?>">
    <span class="tag"><?= e($p['category']) ?></span>
    <span class="pill pill-<?= $av ? 'ok' : 'bad' ?>"><?= $av ? 'Available' : 'Unavailable' ?></span>
    <h3><?= e($p['plan_name']) ?></h3>
    <p class="speed"><?= $p['speed_mbps'] ? (int)$p['speed_mbps'] . ' Mbps' : 'Add-on' ?></p>
    <p class="desc"><?= e(mb_strimwidth($p['description'], 0, 90, '…')) ?></p>
    <p class="price"><?= money($p['price']) ?><small>/mo</small></p>
    <a class="btn btn-block" href="<?= url('plan.php?id=' . (int)$p['plan_id']) ?>">View details</a>
  </article>
<?php endforeach; ?>
</div>
<div id="emptyMsg" class="card empty" hidden>No services match your search. Try a different keyword or filter.</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
