<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$no = $_GET['no'] ?? $_POST['no'] ?? '';
$o = row("SELECT * FROM tbl_orders WHERE order_no=? AND user_id=?", 'si', [$no, uid()]);
if (!$o) { flash('error', 'Order not found.'); redirect('orders.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $on = (int)($_POST['alerts'] ?? 0) === 1 ? 1 : 0;
    q("UPDATE tbl_orders SET tracking_alerts=? WHERE order_id=?", 'ii', [$on, $o['order_id']]);
    log_tx('update', 'Tracking alerts ' . ($on ? 'enabled' : 'disabled') . " for {$o['order_no']}");
    flash('success', $on ? 'Live tracking alerts are now ON.' : 'Tracking alerts turned off.');
    redirect('order_success.php?no=' . urlencode($no));
}
$pageTitle = 'Order confirmed';
include __DIR__ . '/includes/header.php';
?>
<div class="card success-box">
  <div class="check-big">✔</div>
  <h1>Thank you! Your order is confirmed.</h1>
  <p>A confirmation has been recorded for your account. Keep your order number to track the installation.</p>
  <p class="orderno"><?= e($o['order_no']) ?></p>
  <ul class="specs">
    <li><b>Setup:</b> <?= e($o['setup_type']) ?> — estimated <?= e(date('M d, Y', strtotime($o['est_date']))) ?></li>
    <li><b>Total:</b> <?= money($o['total']) ?> (<?= e($o['payment_method']) ?> · <?= e($o['payment_status']) ?>)</li>
    <li><b>Installation address:</b> <?= e($o['address']) ?>, <?= e($o['city']) ?></li>
  </ul>
  <form method="post" class="alerts-form">
    <?= csrf_field() ?><input type="hidden" name="no" value="<?= e($no) ?>">
    <p><b>Enable live tracking alerts?</b>
      <span class="pill pill-<?= $o['tracking_alerts'] ? 'ok' : 'warn' ?>"><?= $o['tracking_alerts'] ? 'ON' : 'OFF' ?></span></p>
    <?php if ($o['tracking_alerts']): ?>
      <button class="btn btn-outline" name="alerts" value="0">Turn off alerts</button>
    <?php else: ?>
      <button class="btn" name="alerts" value="1">Turn on tracking alerts</button>
    <?php endif; ?>
  </form>
  <div class="actions">
    <a class="btn" href="<?= url('orders.php?no=' . urlencode($o['order_no'])) ?>">Track this order</a>
    <a class="btn btn-outline" href="<?= url('services.php') ?>">Continue browsing</a>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
