<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $no = $_POST['no'] ?? '';
    $o = row("SELECT * FROM tbl_orders WHERE order_no=? AND user_id=?", 'si', [$no, uid()]);
    if ($o && $o['status'] === 'Pending') {
        $conn->begin_transaction();
        try {
            q("UPDATE tbl_orders SET status='Cancelled' WHERE order_id=?", 'i', [$o['order_id']]);
            foreach (rows("SELECT plan_id, qty FROM tbl_order_items WHERE order_id=?", 'i', [$o['order_id']]) as $it) {
                q("UPDATE tbl_plans SET slots = slots + ? WHERE plan_id=?", 'ii', [$it['qty'], $it['plan_id']]);
            }
            log_tx('cancel', "Customer cancelled order {$o['order_no']}");
            $conn->commit();
            flash('success', 'Order ' . $o['order_no'] . ' was cancelled.');
        } catch (Throwable $e) { $conn->rollback(); flash('error', 'Could not cancel the order.'); }
    } else { flash('error', 'Only pending orders can be cancelled.'); }
    redirect('orders.php?no=' . urlencode($no));
}

$no = $_GET['no'] ?? '';
$pageTitle = 'My Orders';
include __DIR__ . '/includes/header.php';

if ($no !== ''):
    $o = row("SELECT * FROM tbl_orders WHERE order_no=? AND user_id=?", 'si', [$no, uid()]);
    if (!$o) { echo '<div class="card empty">Order not found.</div>'; include __DIR__ . '/includes/footer.php'; exit; }
    $items = rows("SELECT i.*, p.plan_name FROM tbl_order_items i JOIN tbl_plans p ON p.plan_id=i.plan_id WHERE i.order_id=?", 'i', [$o['order_id']]);
    $steps = ['Pending' => 'Order placed', 'Technician Assigned' => 'Technician assigned', 'Activated' => 'Service activated'];
    $keys = array_keys($steps); $cur = array_search($o['status'], $keys);
?>
<p><a href="<?= url('orders.php') ?>">← All orders</a></p>
<div class="card">
  <h1>Order <?= e($o['order_no']) ?> <span class="pill pill-<?= status_class($o['status']) ?>"><?= e($o['status']) ?></span></h1>
  <?php if ($o['status'] === 'Cancelled'): ?>
    <div class="alert alert-error">This order was cancelled.</div>
  <?php else: ?>
    <ol class="timeline">
      <?php foreach ($keys as $i => $k): ?>
        <li class="<?= $i <= $cur ? 'done' : '' ?>"><span><?= $i + 1 ?></span><?= e($steps[$k]) ?></li>
      <?php endforeach; ?>
    </ol>
    <?php if ($o['technician']): ?><p><b>Technician:</b> <?= e($o['technician']) ?></p><?php endif; ?>
  <?php endif; ?>
  <ul class="specs">
    <li><b>Placed:</b> <?= e(date('M d, Y h:i A', strtotime($o['created_at']))) ?></li>
    <li><b>Setup:</b> <?= e($o['setup_type']) ?> · est. <?= e(date('M d, Y', strtotime($o['est_date']))) ?></li>
    <li><b>Address:</b> <?= e($o['address']) ?>, <?= e($o['city']) ?> · <?= e($o['contact_no']) ?></li>
    <li><b>Payment:</b> <?= e($o['payment_method']) ?> <?= e($o['payment_ref']) ?> — <?= e($o['payment_status']) ?></li>
    <li><b>Live tracking alerts:</b> <?= $o['tracking_alerts'] ? 'ON' : 'OFF' ?></li>
  </ul>
  <div class="table-wrap"><table>
    <thead><tr><th>Service</th><th>Price</th><th>Qty</th><th>Total</th></tr></thead>
    <tbody>
    <?php foreach ($items as $it): ?>
      <tr><td><?= e($it['plan_name']) ?></td><td><?= money($it['unit_price']) ?></td><td><?= $it['qty'] ?></td><td><?= money($it['unit_price'] * $it['qty']) ?></td></tr>
    <?php endforeach; ?>
      <tr><td colspan="3" class="right">Setup fee</td><td><?= money($o['setup_fee']) ?></td></tr>
      <tr class="total-row"><td colspan="3" class="right">Total</td><td><?= money($o['total']) ?></td></tr>
    </tbody>
  </table></div>
  <?php if ($o['status'] === 'Pending'): ?>
    <form method="post" data-confirm="Cancel this order?">
      <?= csrf_field() ?><input type="hidden" name="no" value="<?= e($o['order_no']) ?>">
      <button class="btn btn-danger">Cancel order</button>
    </form>
  <?php endif; ?>
</div>
<?php else:
    $orders = rows("SELECT * FROM tbl_orders WHERE user_id=? ORDER BY created_at DESC", 'i', [uid()]);
?>
<h1>My orders</h1>
<?php if (!$orders): ?>
  <div class="card empty">No orders yet. <a href="<?= url('services.php') ?>">Browse services</a></div>
<?php else: ?>
  <div class="table-wrap card"><table>
    <thead><tr><th>Order no.</th><th>Date</th><th>Setup</th><th>Total</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td><?= e($o['order_no']) ?></td>
        <td><?= e(date('M d, Y', strtotime($o['created_at']))) ?></td>
        <td><?= e($o['setup_type']) ?></td>
        <td><?= money($o['total']) ?></td>
        <td><span class="pill pill-<?= status_class($o['status']) ?>"><?= e($o['status']) ?></span></td>
        <td><a class="btn btn-sm" href="<?= url('orders.php?no=' . urlencode($o['order_no'])) ?>">Track</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
<?php endif; endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
