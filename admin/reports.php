<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-d');
$a = $from . ' 00:00:00'; $b = $to . ' 23:59:59';

$sum = row("SELECT COUNT(*) orders, COALESCE(SUM(total),0) revenue, COALESCE(SUM(setup_fee),0) fees
            FROM tbl_orders WHERE status<>'Cancelled' AND created_at BETWEEN ? AND ?", 'ss', [$a, $b]);
$byPlan = rows("SELECT p.plan_name, p.category, SUM(i.qty) units, SUM(i.qty*i.unit_price) revenue
                FROM tbl_order_items i JOIN tbl_orders o ON o.order_id=i.order_id JOIN tbl_plans p ON p.plan_id=i.plan_id
                WHERE o.status<>'Cancelled' AND o.created_at BETWEEN ? AND ? GROUP BY p.plan_id ORDER BY revenue DESC", 'ss', [$a, $b]);
$byStatus = rows("SELECT status, COUNT(*) c FROM tbl_orders WHERE created_at BETWEEN ? AND ? GROUP BY status", 'ss', [$a, $b]);
$byPay = rows("SELECT payment_method, COUNT(*) c, SUM(total) t FROM tbl_orders WHERE status<>'Cancelled' AND created_at BETWEEN ? AND ? GROUP BY payment_method", 'ss', [$a, $b]);
$pageTitle = 'Reports';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>Sales report</h1><button class="btn btn-outline no-print" onclick="window.print()">Print / Save PDF</button></div>
<form class="filters card no-print" method="get">
  <label>From <input type="date" name="from" value="<?= e($from) ?>"></label>
  <label>To <input type="date" name="to" value="<?= e($to) ?>"></label>
  <button class="btn btn-sm">Generate</button>
</form>
<p class="muted">Period: <?= e(date('M d, Y', strtotime($from))) ?> – <?= e(date('M d, Y', strtotime($to))) ?></p>
<div class="grid stats">
  <div class="card stat"><strong><?= (int)$sum['orders'] ?></strong><span>Orders (excl. cancelled)</span></div>
  <div class="card stat"><strong><?= money($sum['revenue']) ?></strong><span>Total revenue</span></div>
  <div class="card stat"><strong><?= money($sum['fees']) ?></strong><span>Priority fees</span></div>
</div>
<div class="two-col">
  <div class="card"><h3>Revenue by plan</h3>
    <div class="table-wrap"><table><thead><tr><th>Plan</th><th>Category</th><th>Units</th><th>Revenue</th></tr></thead><tbody>
      <?php foreach ($byPlan as $r): ?><tr><td><?= e($r['plan_name']) ?></td><td><?= e($r['category']) ?></td><td><?= (int)$r['units'] ?></td><td><?= money($r['revenue']) ?></td></tr><?php endforeach; ?>
      <?php if (!$byPlan): ?><tr><td colspan="4" class="muted">No sales in this period.</td></tr><?php endif; ?>
    </tbody></table></div></div>
  <div class="card"><h3>Orders by status</h3>
    <ul class="lines"><?php foreach ($byStatus as $r): ?><li><span><?= e($r['status']) ?></span><b><?= (int)$r['c'] ?></b></li><?php endforeach; ?>
    <?php if (!$byStatus): ?><li class="muted">No orders.</li><?php endif; ?></ul>
    <h3>By payment method</h3>
    <ul class="lines"><?php foreach ($byPay as $r): ?><li><span><?= e($r['payment_method']) ?> (<?= (int)$r['c'] ?>)</span><b><?= money($r['t']) ?></b></li><?php endforeach; ?>
    <?php if (!$byPay): ?><li class="muted">No payments.</li><?php endif; ?></ul></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
