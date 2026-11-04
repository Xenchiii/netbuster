<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    q("UPDATE tbl_notify SET seen=1 WHERE user_id=? AND notified=1", 'i', [uid()]);
    redirect('notifications.php');
}
$pageTitle = 'Alerts';
$list = rows("SELECT n.*, p.plan_name, p.status, p.slots FROM tbl_notify n JOIN tbl_plans p ON p.plan_id=n.plan_id WHERE n.user_id=? ORDER BY n.created_at DESC", 'i', [uid()]);
$orders = rows("SELECT order_no, status, technician FROM tbl_orders WHERE user_id=? AND tracking_alerts=1 ORDER BY updated_at DESC", 'i', [uid()]);
include __DIR__ . '/includes/header.php';
?>
<h1>Alerts</h1>
<h3>Order tracking alerts</h3>
<?php if (!$orders): ?><div class="card empty">No orders with live tracking alerts enabled.</div><?php else: ?>
  <div class="card"><ul class="lines">
  <?php foreach ($orders as $o): ?>
    <li><span><a href="<?= url('orders.php?no=' . urlencode($o['order_no'])) ?>"><?= e($o['order_no']) ?></a>
      <?= $o['technician'] ? ' · Technician: ' . e($o['technician']) : '' ?></span>
      <span class="pill pill-<?= status_class($o['status']) ?>"><?= e($o['status']) ?></span></li>
  <?php endforeach; ?></ul></div>
<?php endif; ?>
<h3>“Notify me when available”</h3>
<?php if (!$list): ?><div class="card empty">You are not waiting on any services.</div><?php else: ?>
  <div class="card"><ul class="lines">
  <?php foreach ($list as $n): ?>
    <li><span><a href="<?= url('plan.php?id=' . $n['plan_id']) ?>"><?= e($n['plan_name']) ?></a></span>
      <?php if ($n['notified']): ?><span class="pill pill-ok">Now available!</span><?php else: ?><span class="pill pill-warn">Waiting</span><?php endif; ?></li>
  <?php endforeach; ?></ul>
  <form method="post"><?= csrf_field() ?><button class="btn btn-outline btn-sm">Mark alerts as read</button></form></div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
