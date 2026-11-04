<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Login';
if (is_logged_in()) { redirect('index.php'); }
$next = $_GET['next'] ?? $_POST['next'] ?? '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $u = row("SELECT * FROM tbl_users WHERE username=?", 's', [$username]);
    if ($u && password_verify($password, $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['user_id' => $u['user_id'], 'username' => $u['username'], 'full_name' => $u['full_name'], 'role' => $u['role']];
        log_tx('login', $u['username'] . ' logged in');
        flash('success', 'Welcome back, ' . $u['full_name'] . '!');
        if ($next && strpos($next, BASE_URL . '/') === 0 && strpos($next, '//') === false) { header('Location: ' . $next); exit; }
        redirect($u['role'] === 'admin' ? 'admin/index.php' : 'services.php');
    }
    $err = 'Invalid username or password. Please try again.';
}
include __DIR__ . '/includes/header.php';
?>
<div class="auth-box card">
  <h2>Log in</h2>
  <p class="muted">Don't have an account? <a href="<?= url('register.php') ?>">Sign up</a> or <a href="<?= url('services.php') ?>">keep browsing as a guest</a>.</p>
  <?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>
  <form method="post" data-validate novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <label>Username<input type="text" name="username" required autofocus value="<?= e($_POST['username'] ?? '') ?>"></label>
    <label>Password<input type="password" name="password" required></label>
    <button class="btn btn-block" type="submit">Log in</button>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
