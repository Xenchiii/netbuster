<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$act = $_GET['action'] ?? '';
$search = trim($_GET['s'] ?? '');
$sql = "SELECT t.*, u.username, p.plan_name FROM tbl_transactions t
        LEFT JOIN tbl_users u ON u.user_id=t.user_id LEFT JOIN tbl_plans p ON p.plan_id=t.record_id
        WHERE (t.remarks LIKE ? OR u.username LIKE ?)";
$types = 'ss'; $par = ['%' . $search . '%', '%' . $search . '%'];
if ($act !== '') { $sql .= " AND t.action_type=?"; $types .= 's'; $par[] = $act; }
$logs = rows($sql . " ORDER BY t.transaction_date DESC LIMIT 300", $types, $par);
$actions = rows("SELECT DISTINCT action_type FROM tbl_transactions ORDER BY action_type");
$pageTitle = 'Activity Log';
include __DIR__ . '/../includes/header.php';
?>
<h1>Transaction / activity log</h1>
<form class="filters card" method="get">
  <input type="search" name="s" placeholder="Search remarks or username…" value="<?= e($search) ?>">
  <select name="action"><option value="">All actions</option>
    <?php foreach ($actions as $a): ?><option <?= $act === $a['action_type'] ? 'selected' : '' ?>><?= e($a['action_type']) ?></option><?php endforeach; ?>
  </select>
  <button class="btn btn-sm">Filter</button>
</form>
<div class="table-wrap card"><table>
  <thead><tr><th>#</th><th>Date</th><th>User</th><th>Action</th><th>Remarks</th></tr></thead>
  <tbody>
  <?php foreach ($logs as $l): ?>
    <tr><td><?= $l['transaction_id'] ?></td><td><?= e(date('M d, Y h:i A', strtotime($l['transaction_date']))) ?></td>
        <td><?= e($l['username'] ?? '—') ?></td><td><span class="pill pill-info"><?= e($l['action_type']) ?></span></td><td><?= e($l['remarks']) ?></td></tr>
  <?php endforeach; if (!$logs): ?><tr><td colspan="5" class="muted">No log entries.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
