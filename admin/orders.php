<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $oid = (int)$_POST['order_id'];
    $status = $_POST['status'] ?? '';
    $tech = trim($_POST['technician'] ?? '');
    $valid = ['Pending', 'Technician Assigned', 'Activated', 'Cancelled'];
    $o = row("SELECT * FROM tbl_orders WHERE order_id=?", 'i', [$oid]);
    if (!$o || !in_array($status, $valid, true)) { flash('error', 'Invalid request.'); redirect('admin/orders.php'); }
    if (in_array($status, ['Technician Assigned', 'Activated'], true) && $tech === '') {
        flash('error', 'Enter the technician name before assigning or activating.');
        redirect('admin/orders.php');
    }
    $conn->begin_transaction();
    try {
        // restore / re-take slots when cancelling or reopening
        if ($status === 'Cancelled' && $o['status'] !== 'Cancelled') {
            foreach (rows("SELECT plan_id, qty FROM tbl_order_items WHERE order_id=?", 'i', [$oid]) as $it)
                q("UPDATE tbl_plans SET slots = slots + ? WHERE plan_id=?", 'ii', [$it['qty'], $it['plan_id']]);
        } elseif ($o['status'] === 'Cancelled' && $status !== 'Cancelled') {
            foreach (rows("SELECT i.plan_id, i.qty, p.slots, p.plan_name FROM tbl_order_items i JOIN tbl_plans p ON p.plan_id=i.plan_id WHERE i.order_id=?", 'i', [$oid]) as $it) {
                if ($it['slots'] < $it['qty']) throw new RuntimeException('Not enough slots left for ' . $it['plan_name'] . ' to reopen this order.');
                q("UPDATE tbl_plans SET slots = slots - ? WHERE plan_id=?", 'ii', [$it['qty'], $it['plan_id']]);
            }
        }
        $pay = ($status === 'Activated' && $o['payment_method'] === 'Cash') ? 'Paid' : $o['payment_status'];
        q("UPDATE tbl_orders SET status=?, technician=?, payment_status=? WHERE order_id=?", 'sssi', [$status, $tech !== '' ? $tech : null, $pay, $oid]);
        log_tx('update', "Order {$o['order_no']} → $status" . ($tech ? " (tech: $tech)" : ''));
        $conn->commit();
        flash('success', "Order {$o['order_no']} updated to $status.");
    } catch (RuntimeException $e) { $conn->rollback(); flash('error', $e->getMessage()); }
    catch (Throwable $e) { $conn->rollback(); flash('error', 'Could not update the order.'); }
    redirect('admin/orders.php');
}

$filter = $_GET['status'] ?? '';
$search = trim($_GET['s'] ?? '');
$sql = "SELECT o.*, u.full_name FROM tbl_orders o JOIN tbl_users u ON u.user_id=o.user_id WHERE (o.order_no LIKE ? OR u.full_name LIKE ?)";
$types = 'ss'; $par = ['%' . $search . '%', '%' . $search . '%'];
if ($filter !== '') { $sql .= " AND o.status=?"; $types .= 's'; $par[] = $filter; }
$orders = rows($sql . " ORDER BY o.created_at DESC", $types, $par);
$pageTitle = 'Manage Orders';
include __DIR__ . '/../includes/header.php';
?>
<h1>Orders</h1>
<form class="filters card" method="get">
  <input type="search" name="s" placeholder="Search order no. or customer…" value="<?= e($search) ?>">
  <select name="status">
    <option value="">All statuses</option>
    <?php foreach (['Pending', 'Technician Assigned', 'Activated', 'Cancelled'] as $s): ?><option <?= $filter === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
  </select>
  <button class="btn btn-sm">Filter</button>
</form>
<div class="table-wrap card"><table>
  <thead><tr><th>Order</th><th>Customer</th><th>Setup</th><th>Total</th><th>Payment</th><th>Installation</th><th>Update status</th></tr></thead>
  <tbody>
  <?php foreach ($orders as $o): ?>
    <tr>
      <td><?= e($o['order_no']) ?><br><small class="muted"><?= e(date('M d, Y', strtotime($o['created_at']))) ?></small></td>
      <td><?= e($o['full_name']) ?><br><small class="muted"><?= e($o['contact_no']) ?></small></td>
      <td><?= e($o['setup_type']) ?><br><small class="muted">est. <?= e(date('M d', strtotime($o['est_date']))) ?></small></td>
      <td><?= money($o['total']) ?></td>
      <td><?= e($o['payment_method']) ?><br><span class="pill pill-<?= status_class($o['payment_status']) ?>"><?= e($o['payment_status']) ?></span></td>
      <td><small><?= e($o['address']) ?>, <?= e($o['city']) ?></small></td>
      <td>
        <form method="post" class="stack">
          <?= csrf_field() ?><input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
          <select name="status">
            <?php foreach (['Pending', 'Technician Assigned', 'Activated', 'Cancelled'] as $s): ?><option <?= $o['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
          </select>
          <input type="text" name="technician" placeholder="Technician name" value="<?= e($o['technician']) ?>">
          <button class="btn btn-sm">Save</button>
        </form>
      </td>
    </tr>
  <?php endforeach; if (!$orders): ?><tr><td colspan="7" class="muted">No orders found.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
