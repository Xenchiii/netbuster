<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pageTitle = 'Admin Dashboard';
$s = row("SELECT
  (SELECT COUNT(*) FROM tbl_users WHERE role='user') customers,
  (SELECT COUNT(*) FROM tbl_plans) plans,
  (SELECT COUNT(*) FROM tbl_orders) orders,
  (SELECT COUNT(*) FROM tbl_orders WHERE status='Pending') pending,
  (SELECT COALESCE(SUM(total),0) FROM tbl_orders WHERE status<>'Cancelled') revenue");
$recent = rows("SELECT o.*, u.full_name FROM tbl_orders o JOIN tbl_users u ON u.user_id=o.user_id ORDER BY o.created_at DESC LIMIT 6");
$low = rows("SELECT plan_id, plan_name, slots FROM tbl_plans WHERE slots<=5 ORDER BY slots ASC LIMIT 6");
include __DIR__ . '/../includes/header.php';
?>
<h1>Admin dashboard</h1>
<div class="grid stats">
  <div class="card stat"><strong><?= (int)$s['customers'] ?></strong><span>Customers</span></div>
  <div class="card stat"><strong><?= (int)$s['plans'] ?></strong><span>Plans</span></div>
  <div class="card stat"><strong><?= (int)$s['orders'] ?></strong><span>Orders</span></div>
  <div class="card stat"><strong><?= (int)$s['pending'] ?></strong><span>Pending</span></div>
  <div class="card stat"><strong><?= money($s['revenue']) ?></strong><span>Revenue</span></div>
</div>
<div class="two-col">
  <div class="card">
    <h3>Recent orders</h3>
    <div class="table-wrap"><table>
      <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($recent as $o): ?>
        <tr><td><?= e($o['order_no']) ?></td><td><?= e($o['full_name']) ?></td><td><?= money($o['total']) ?></td>
            <td><span class="pill pill-<?= status_class($o['status']) ?>"><?= e($o['status']) ?></span></td></tr>
      <?php endforeach; if (!$recent): ?><tr><td colspan="4" class="muted">No orders yet.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
    <a href="<?= url('admin/orders.php') ?>">Manage orders →</a>
  </div>
  <div class="card">
    <h3>Low / no slots</h3>
    <ul class="lines">
      <?php foreach ($low as $l): ?><li><span><a href="<?= url('admin/plan_form.php?id=' . $l['plan_id']) ?>"><?= e($l['plan_name']) ?></a></span><b><?= (int)$l['slots'] ?> left</b></li><?php endforeach; ?>
      <?php if (!$low): ?><li class="muted">All plans have healthy slots.</li><?php endif; ?>
    </ul>
    <a href="<?= url('admin/plans.php') ?>">Manage plans →</a>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
