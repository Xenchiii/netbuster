<?php
require_once __DIR__ . '/includes/functions.php';
$id = (int)($_GET['id'] ?? 0);
$p = row("SELECT * FROM tbl_plans WHERE plan_id=?", 'i', [$id]);
if (!$p) { flash('error', 'Service not found.'); redirect('services.php'); }
$pageTitle = $p['plan_name'];
$av = plan_available($p);
$subscribed = is_logged_in() ? row("SELECT notify_id FROM tbl_notify WHERE user_id=? AND plan_id=?", 'ii', [uid(), $id]) : null;
$max = min(5, (int)$p['slots']);
include __DIR__ . '/includes/header.php';
?>
<p><a href="<?= url('services.php') ?>">← Back to services</a></p>
<div class="detail card">
  <div>
    <span class="tag"><?= e($p['category']) ?></span>
    <h1><?= e($p['plan_name']) ?></h1>
    <p class="speed big"><?= $p['speed_mbps'] ? (int)$p['speed_mbps'] . ' Mbps' : 'Add-on service' ?></p>
    <p><?= nl2br(e($p['description'])) ?></p>
    <ul class="specs">
      <li><b>Category:</b> <?= e($p['category']) ?></li>
      <li><b>Installation slots left:</b> <?= (int)$p['slots'] ?></li>
      <li><b>Last updated:</b> <?= e(date('M d, Y', strtotime($p['date_updated']))) ?></li>
    </ul>
  </div>
  <aside class="buy">
    <p class="price big"><?= money($p['price']) ?><small>/mo</small></p>
    <span class="pill pill-<?= $av ? 'ok' : 'bad' ?>"><?= $av ? 'Available' : 'Currently unavailable' ?></span>
    <?php if ($av): ?>
      <form method="post" action="<?= url('cart.php') ?>" data-validate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="plan_id" value="<?= $id ?>">
        <label>Quantity
          <input type="number" name="qty" value="1" min="1" max="<?= $max ?>" required>
        </label>
        <button class="btn btn-block" type="submit">Add to cart</button>
      </form>
    <?php else: ?>
      <p class="muted">This plan has no open installation slots right now.</p>
      <?php if ($subscribed): ?>
        <button class="btn btn-block" disabled>✔ You'll be notified</button>
      <?php else: ?>
        <form method="post" action="<?= url('notify.php') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="plan_id" value="<?= $id ?>">
          <button class="btn btn-block" type="submit">Notify me when available</button>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </aside>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
