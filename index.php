<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Home';
$featured = rows("SELECT * FROM tbl_plans WHERE status='Available' AND slots>0 ORDER BY speed_mbps DESC LIMIT 4");
$stats = row("SELECT (SELECT COUNT(*) FROM tbl_plans) plans, (SELECT COUNT(*) FROM tbl_orders WHERE status='Activated') active");
include __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div>
    <h1>Bust through slow internet.</h1>
    <p>Browse fiber, DSL, wireless and business plans, check installation availability, and book your setup online — standard or priority.</p>
    <div class="actions">
      <a class="btn" href="<?= url('services.php') ?>">Browse services</a>
      <?php if (!is_logged_in()): ?><a class="btn btn-outline" href="<?= url('register.php') ?>">Create account</a><?php endif; ?>
    </div>
  </div>
  <div class="hero-card">
    <div class="stat"><strong><?= (int)$stats['plans'] ?></strong><span>Services &amp; solutions</span></div>
    <div class="stat"><strong><?= (int)$stats['active'] ?></strong><span>Active installations</span></div>
    <div class="stat"><strong>2 days</strong><span>Priority setup</span></div>
  </div>
</section>

<h2 class="section-title">Featured plans</h2>
<div class="grid">
<?php foreach ($featured as $p): ?>
  <article class="card plan">
    <span class="tag"><?= e($p['category']) ?></span>
    <h3><?= e($p['plan_name']) ?></h3>
    <p class="speed"><?= $p['speed_mbps'] ? (int)$p['speed_mbps'] . ' Mbps' : 'Add-on' ?></p>
    <p class="price"><?= money($p['price']) ?><small>/mo</small></p>
    <a class="btn btn-block" href="<?= url('plan.php?id=' . (int)$p['plan_id']) ?>">View details</a>
  </article>
<?php endforeach; ?>
</div>

<h2 class="section-title">How it works</h2>
<div class="steps">
  <div class="card"><b>1</b><h4>Browse &amp; search</h4><p>Find a plan by speed, category or price.</p></div>
  <div class="card"><b>2</b><h4>Check availability</h4><p>Not available? Ask to be notified when slots open.</p></div>
  <div class="card"><b>3</b><h4>Choose setup</h4><p>Standard (free) or priority installation.</p></div>
  <div class="card"><b>4</b><h4>Pay &amp; track</h4><p>Get an order number and live tracking alerts.</p></div>
  <div class="card"><b>5</b><h4>Get connected</h4><p>A technician is assigned and your service is activated.</p></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
