<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    $id = (int)$_POST['plan_id'];
    $p = row("SELECT plan_name FROM tbl_plans WHERE plan_id=?", 'i', [$id]);
    if ($p) {
        try {
            q("DELETE FROM tbl_plans WHERE plan_id=?", 'i', [$id]);
            log_tx('delete', 'Deleted plan: ' . $p['plan_name']);
            flash('success', 'Plan deleted.');
        } catch (mysqli_sql_exception $e) {
            flash('error', 'This plan has existing orders and cannot be deleted. Set it to Unavailable instead.');
        }
    }
    redirect('admin/plans.php');
}

$pageTitle = 'Manage Plans';
$search = trim($_GET['s'] ?? '');
$like = '%' . $search . '%';
$plans = rows("SELECT * FROM tbl_plans WHERE plan_name LIKE ? OR category LIKE ? ORDER BY date_added DESC", 'ss', [$like, $like]);
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>Plans &amp; services</h1><a class="btn" href="<?= url('admin/plan_form.php') ?>">+ Add plan</a></div>
<form class="filters card" method="get">
  <input type="search" name="s" placeholder="Search plans or category…" value="<?= e($search) ?>">
  <button class="btn btn-sm">Search</button>
  <?php if ($search): ?><a class="btn btn-sm btn-outline" href="<?= url('admin/plans.php') ?>">Reset</a><?php endif; ?>
</form>
<div class="table-wrap card"><table class="sortable">
  <thead><tr><th data-sort="text">Name</th><th data-sort="text">Category</th><th data-sort="num">Speed</th><th data-sort="num">Price</th><th data-sort="num">Slots</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($plans as $p): ?>
    <tr>
      <td><?= e($p['plan_name']) ?></td><td><?= e($p['category']) ?></td>
      <td data-v="<?= (int)$p['speed_mbps'] ?>"><?= $p['speed_mbps'] ? (int)$p['speed_mbps'] . ' Mbps' : '—' ?></td>
      <td data-v="<?= (float)$p['price'] ?>"><?= money($p['price']) ?></td>
      <td data-v="<?= (int)$p['slots'] ?>"><?= (int)$p['slots'] ?></td>
      <td><span class="pill pill-<?= status_class($p['status']) ?>"><?= e($p['status']) ?></span></td>
      <td class="nowrap">
        <a class="btn btn-sm btn-outline" href="<?= url('admin/plan_form.php?id=' . $p['plan_id']) ?>">Edit</a>
        <form method="post" class="inline" data-confirm="Delete '<?= e($p['plan_name']) ?>'? This cannot be undone.">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="plan_id" value="<?= $p['plan_id'] ?>">
          <button class="btn btn-sm btn-danger">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; if (!$plans): ?><tr><td colspan="7" class="muted">No plans found.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
