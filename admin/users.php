<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)$_POST['user_id'];
    $u = row("SELECT * FROM tbl_users WHERE user_id=?", 'i', [$id]);
    if (!$u) { flash('error', 'User not found.'); redirect('admin/users.php'); }
    if ($id === uid()) { flash('error', 'You cannot change or delete your own account here.'); redirect('admin/users.php'); }
    if (($_POST['action'] ?? '') === 'role') {
        $role = $_POST['role'] === 'admin' ? 'admin' : 'user';
        q("UPDATE tbl_users SET role=? WHERE user_id=?", 'si', [$role, $id]);
        log_tx('update', "Changed role of {$u['username']} to $role");
        flash('success', 'Role updated.');
    } elseif (($_POST['action'] ?? '') === 'delete') {
        try {
            q("DELETE FROM tbl_users WHERE user_id=?", 'i', [$id]);
            log_tx('delete', 'Deleted user ' . $u['username']);
            flash('success', 'User deleted.');
        } catch (mysqli_sql_exception $e) {
            flash('error', 'This user has orders or created plans and cannot be deleted.');
        }
    }
    redirect('admin/users.php');
}
$search = trim($_GET['s'] ?? '');
$users = rows("SELECT u.*, (SELECT COUNT(*) FROM tbl_orders o WHERE o.user_id=u.user_id) orders FROM tbl_users u
               WHERE username LIKE ? OR full_name LIKE ? OR email LIKE ? ORDER BY created_at DESC", 'sss', array_fill(0, 3, '%' . $search . '%'));
$pageTitle = 'Users';
include __DIR__ . '/../includes/header.php';
?>
<h1>Users</h1>
<form class="filters card" method="get"><input type="search" name="s" placeholder="Search users…" value="<?= e($search) ?>"><button class="btn btn-sm">Search</button></form>
<div class="table-wrap card"><table>
  <thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Orders</th><th>Joined</th><th>Role</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><?= e($u['full_name']) ?></td><td><?= e($u['username']) ?></td><td><?= e($u['email']) ?></td>
      <td><?= (int)$u['orders'] ?></td><td><?= e(date('M d, Y', strtotime($u['created_at']))) ?></td>
      <td>
        <?php if ($u['user_id'] == uid()): ?><span class="pill pill-info">admin (you)</span>
        <?php else: ?>
        <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="role"><input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
          <select name="role" onchange="this.form.submit()"><option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>user</option><option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>admin</option></select>
        </form>
        <?php endif; ?>
      </td>
      <td>
        <?php if ($u['user_id'] != uid()): ?>
        <form method="post" class="inline" data-confirm="Delete user <?= e($u['username']) ?>?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
          <button class="btn btn-sm btn-danger">Delete</button></form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
