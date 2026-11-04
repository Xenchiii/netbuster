<?php
$pageTitle = $pageTitle ?? 'Net Busters';
$alerts = 0;
if (is_logged_in()) {
    $r = row("SELECT COUNT(*) c FROM tbl_notify WHERE user_id=? AND notified=1 AND seen=0", 'i', [uid()]);
    $alerts = (int)$r['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> — Net Busters</title>
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<header class="site-header">
  <div class="container nav">
    <a class="brand" href="<?= url('index.php') ?>">Net<span>Busters</span></a>
    <button class="nav-toggle" id="navToggle" aria-label="Menu">☰</button>
    <nav id="mainNav">
      <a href="<?= url('index.php') ?>">Home</a>
      <a href="<?= url('services.php') ?>">Services</a>
      <?php if (is_logged_in() && !is_admin()): ?>
        <a href="<?= url('cart.php') ?>">Cart<?php if (cart_count()): ?> <span class="badge"><?= cart_count() ?></span><?php endif; ?></a>
        <a href="<?= url('orders.php') ?>">My Orders</a>
        <a href="<?= url('notifications.php') ?>">Alerts<?php if ($alerts): ?> <span class="badge"><?= $alerts ?></span><?php endif; ?></a>
      <?php endif; ?>
      <?php if (is_admin()): ?>
        <a href="<?= url('admin/index.php') ?>">Dashboard</a>
        <a href="<?= url('admin/plans.php') ?>">Plans</a>
        <a href="<?= url('admin/orders.php') ?>">Orders</a>
        <a href="<?= url('admin/users.php') ?>">Users</a>
        <a href="<?= url('admin/reports.php') ?>">Reports</a>
        <a href="<?= url('admin/transactions.php') ?>">Logs</a>
      <?php endif; ?>
      <?php if (is_logged_in()): ?>
        <span class="who">Hi, <?= e(explode(' ', $_SESSION['user']['full_name'])[0]) ?></span>
        <a class="btn btn-sm btn-outline" href="<?= url('logout.php') ?>">Logout</a>
      <?php else: ?>
        <a href="<?= url('login.php') ?>">Login</a>
        <a class="btn btn-sm" href="<?= url('register.php') ?>">Sign up</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container">
<?php foreach (flash() as $f): ?>
  <div class="alert alert-<?= e($f['t']) ?>"><?= e($f['m']) ?></div>
<?php endforeach; ?>
