<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
if (is_admin()) { flash('error', 'Admins cannot place orders. Use a customer account.'); redirect('admin/index.php'); }
$_SESSION['cart'] = $_SESSION['cart'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $pid = (int)($_POST['plan_id'] ?? 0);
    if ($action === 'add') {
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        $p = row("SELECT * FROM tbl_plans WHERE plan_id=?", 'i', [$pid]);
        if (!$p || !plan_available($p)) {
            flash('error', 'That service is not available.');
            redirect('services.php');
        }
        $new = ($_SESSION['cart'][$pid] ?? 0) + $qty;
        if ($new > min(5, (int)$p['slots'])) {
            flash('error', 'Only ' . min(5, (int)$p['slots']) . ' unit(s) can be ordered for this plan.');
            redirect('plan.php?id=' . $pid);
        }
        $_SESSION['cart'][$pid] = $new;
        flash('success', $p['plan_name'] . ' added to your cart.');
        redirect('cart.php');
    } elseif ($action === 'update') {
        $qty = (int)($_POST['qty'] ?? 1);
        $p = row("SELECT slots FROM tbl_plans WHERE plan_id=?", 'i', [$pid]);
        if ($p && $qty >= 1 && $qty <= min(5, (int)$p['slots'])) { $_SESSION['cart'][$pid] = $qty; flash('success', 'Cart updated.'); }
        else { flash('error', 'Invalid quantity.'); }
    } elseif ($action === 'remove') {
        unset($_SESSION['cart'][$pid]);
        flash('success', 'Item removed.');
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
        flash('success', 'Cart cleared.');
    }
    redirect('cart.php');
}

$items = []; $subtotal = 0;
foreach ($_SESSION['cart'] as $pid => $qty) {
    $p = row("SELECT * FROM tbl_plans WHERE plan_id=?", 'i', [$pid]);
    if (!$p) { unset($_SESSION['cart'][$pid]); continue; }
    $p['qty'] = $qty; $p['line'] = $qty * $p['price']; $p['ok'] = plan_available($p) && $qty <= $p['slots'];
    $subtotal += $p['line'];
    $items[] = $p;
}
$blocked = array_filter($items, fn($i) => !$i['ok']);
$pageTitle = 'My Cart';
include __DIR__ . '/includes/header.php';
?>
<h1>Your cart</h1>
<?php if (!$items): ?>
  <div class="card empty">Your cart is empty. <a href="<?= url('services.php') ?>">Browse services</a></div>
<?php else: ?>
<div class="table-wrap card">
<table>
  <thead><tr><th>Service</th><th>Price</th><th>Qty</th><th>Total</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($items as $i): ?>
    <tr>
      <td><a href="<?= url('plan.php?id=' . $i['plan_id']) ?>"><?= e($i['plan_name']) ?></a>
        <?php if (!$i['ok']): ?><br><span class="pill pill-bad">No longer available — remove to continue</span><?php endif; ?></td>
      <td><?= money($i['price']) ?></td>
      <td>
        <form method="post" class="inline">
          <?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="plan_id" value="<?= $i['plan_id'] ?>">
          <input class="qty" type="number" name="qty" min="1" max="5" value="<?= $i['qty'] ?>"><button class="btn btn-sm btn-outline">Update</button>
        </form>
      </td>
      <td><?= money($i['line']) ?></td>
      <td>
        <form method="post" class="inline" data-confirm="Remove this item from the cart?">
          <?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="plan_id" value="<?= $i['plan_id'] ?>">
          <button class="btn btn-sm btn-danger">Remove</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<div class="summary card">
  <p>Subtotal <strong><?= money($subtotal) ?></strong></p>
  <p class="muted">Choose standard or priority setup at checkout.</p>
  <div class="actions">
    <form method="post" data-confirm="Clear your whole cart?"><?= csrf_field() ?><input type="hidden" name="action" value="clear"><button class="btn btn-outline">Clear cart</button></form>
    <?php if ($blocked): ?><button class="btn" disabled>Proceed to checkout</button>
    <?php else: ?><a class="btn" href="<?= url('checkout.php') ?>">Proceed to checkout</a><?php endif; ?>
  </div>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
