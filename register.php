<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Sign up';
if (is_logged_in()) { redirect('index.php'); }
$errors = [];
$old = ['full_name' => '', 'username' => '', 'email' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old['full_name'] = trim($_POST['full_name'] ?? '');
    $old['username']  = trim($_POST['username'] ?? '');
    $old['email']     = trim($_POST['email'] ?? '');
    $pw = $_POST['password'] ?? '';
    $pw2 = $_POST['confirm'] ?? '';
    if (strlen($old['full_name']) < 3) $errors[] = 'Full name must be at least 3 characters.';
    if (!preg_match('/^[A-Za-z0-9_]{4,20}$/', $old['username'])) $errors[] = 'Username must be 4–20 letters, numbers or underscores.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($pw) < 8 || !preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) $errors[] = 'Password must be at least 8 characters with letters and numbers.';
    if ($pw !== $pw2) $errors[] = 'Passwords do not match.';
    if (!$errors) {
        $dup = row("SELECT user_id FROM tbl_users WHERE username=? OR email=?", 'ss', [$old['username'], $old['email']]);
        if ($dup) { $errors[] = 'Username or email is already registered.'; }
    }
    if (!$errors) {
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        q("INSERT INTO tbl_users (username, password, full_name, email, role) VALUES (?,?,?,?, 'user')", 'ssss',
          [$old['username'], $hash, $old['full_name'], $old['email']]);
        flash('success', 'Account created! You can now log in.');
        redirect('login.php');
    }
}
include __DIR__ . '/includes/header.php';
?>
<div class="auth-box card">
  <h2>Create your account</h2>
  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" id="registerForm" data-validate novalidate>
    <?= csrf_field() ?>
    <label>Full name<input type="text" name="full_name" required minlength="3" value="<?= e($old['full_name']) ?>"></label>
    <label>Username<input type="text" name="username" required pattern="[A-Za-z0-9_]{4,20}" title="4–20 letters, numbers or underscores" value="<?= e($old['username']) ?>"></label>
    <label>Email<input type="email" name="email" required value="<?= e($old['email']) ?>"></label>
    <label>Password<input type="password" name="password" id="password" required minlength="8"></label>
    <div class="strength"><span id="strengthBar"></span></div><small id="strengthText" class="muted"></small>
    <label>Confirm password<input type="password" name="confirm" id="confirm" required></label>
    <button class="btn btn-block" type="submit">Sign up</button>
  </form>
  <p class="muted">Already registered? <a href="<?= url('login.php') ?>">Log in</a></p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
