<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$id = (int)($_GET['id'] ?? $_POST['plan_id'] ?? 0);
$cats = ['Fiber', 'DSL', 'Wireless', 'Business', 'Add-on'];
$p = ['plan_name' => '', 'category' => 'Fiber', 'description' => '', 'speed_mbps' => 0, 'price' => '', 'slots' => 10, 'status' => 'Available'];
if ($id) {
    $db = row("SELECT * FROM tbl_plans WHERE plan_id=?", 'i', [$id]);
    if (!$db) { flash('error', 'Plan not found.'); redirect('admin/plans.php'); }
    $p = $db;
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $p['plan_name']   = trim($_POST['plan_name'] ?? '');
    $p['category']    = $_POST['category'] ?? '';
    $p['description'] = trim($_POST['description'] ?? '');
    $p['speed_mbps']  = (int)($_POST['speed_mbps'] ?? 0);
    $p['price']       = $_POST['price'] ?? '';
    $p['slots']       = (int)($_POST['slots'] ?? 0);
    $p['status']      = $_POST['status'] ?? '';
    if (strlen($p['plan_name']) < 3) $errors[] = 'Plan name must be at least 3 characters.';
    if (!in_array($p['category'], $cats, true)) $errors[] = 'Choose a valid category.';
    if (!is_numeric($p['price']) || $p['price'] <= 0) $errors[] = 'Price must be greater than zero.';
    if ($p['speed_mbps'] < 0 || $p['speed_mbps'] > 10000) $errors[] = 'Speed must be between 0 and 10,000 Mbps.';
    if ($p['slots'] < 0) $errors[] = 'Slots cannot be negative.';
    if (!in_array($p['status'], ['Available', 'Unavailable'], true)) $errors[] = 'Choose a status.';
    if (!$errors) {
        if ($id) {
            q("UPDATE tbl_plans SET plan_name=?, category=?, description=?, speed_mbps=?, price=?, slots=?, status=? WHERE plan_id=?",
              'sssidisi', [$p['plan_name'], $p['category'], $p['description'], $p['speed_mbps'], $p['price'], $p['slots'], $p['status'], $id]);
            log_tx('update', 'Updated plan: ' . $p['plan_name'], $id);
            // alert customers waiting for this plan once it is available again
            if ($p['status'] === 'Available' && $p['slots'] > 0) {
                q("UPDATE tbl_notify SET notified=1, seen=0 WHERE plan_id=? AND notified=0", 'i', [$id]);
            }
            flash('success', 'Plan updated.');
        } else {
            q("INSERT INTO tbl_plans (user_id, plan_name, category, description, speed_mbps, price, slots, status) VALUES (?,?,?,?,?,?,?,?)",
              'isssidis', [uid(), $p['plan_name'], $p['category'], $p['description'], $p['speed_mbps'], $p['price'], $p['slots'], $p['status']]);
            log_tx('add', 'Added plan: ' . $p['plan_name'], $conn->insert_id);
            flash('success', 'Plan added.');
        }
        redirect('admin/plans.php');
    }
}
$pageTitle = $id ? 'Edit Plan' : 'Add Plan';
include __DIR__ . '/../includes/header.php';
?>
<p><a href="<?= url('admin/plans.php') ?>">← Back to plans</a></p>
<div class="card form-card">
  <h1><?= $id ? 'Edit plan' : 'Add plan' ?></h1>
  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" data-validate novalidate>
    <?= csrf_field() ?><input type="hidden" name="plan_id" value="<?= $id ?>">
    <label>Plan name<input type="text" name="plan_name" required minlength="3" value="<?= e($p['plan_name']) ?>"></label>
    <div class="row2">
      <label>Category<select name="category"><?php foreach ($cats as $c): ?><option <?= $p['category'] === $c ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?></select></label>
      <label>Status<select name="status"><option <?= $p['status'] === 'Available' ? 'selected' : '' ?>>Available</option><option <?= $p['status'] === 'Unavailable' ? 'selected' : '' ?>>Unavailable</option></select></label>
    </div>
    <label>Description<textarea name="description" rows="4"><?= e($p['description']) ?></textarea></label>
    <div class="row3">
      <label>Speed (Mbps)<input type="number" name="speed_mbps" min="0" max="10000" value="<?= (int)$p['speed_mbps'] ?>"></label>
      <label>Monthly price (₱)<input type="number" name="price" step="0.01" min="1" required value="<?= e($p['price']) ?>"></label>
      <label>Installation slots<input type="number" name="slots" min="0" required value="<?= (int)$p['slots'] ?>"></label>
    </div>
    <button class="btn" type="submit"><?= $id ? 'Save changes' : 'Add plan' ?></button>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
