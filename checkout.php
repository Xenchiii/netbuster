<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
if (is_admin()) { redirect('admin/index.php'); }
if (empty($_SESSION['cart'])) { flash('error', 'Your cart is empty.'); redirect('services.php'); }

function luhn_ok($num) {
    $num = preg_replace('/\D/', '', $num);
    if (strlen($num) < 13 || strlen($num) > 19) return false;
    $sum = 0; $alt = false;
    for ($i = strlen($num) - 1; $i >= 0; $i--) {
        $n = (int)$num[$i];
        if ($alt) { $n *= 2; if ($n > 9) $n -= 9; }
        $sum += $n; $alt = !$alt;
    }
    return $sum % 10 === 0;
}

// Load cart items with fresh prices from the database
$items = []; $subtotal = 0;
foreach ($_SESSION['cart'] as $pid => $qty) {
    $p = row("SELECT * FROM tbl_plans WHERE plan_id=?", 'i', [$pid]);
    if (!$p || !plan_available($p) || $qty > $p['slots']) {
        flash('error', 'An item in your cart is no longer available. Please review your cart.');
        redirect('cart.php');
    }
    $p['qty'] = $qty; $p['line'] = $qty * $p['price'];
    $subtotal += $p['line'];
    $items[] = $p;
}

$errors = [];
$f = ['setup_type' => 'Standard', 'address' => '', 'city' => '', 'contact_no' => '', 'devices' => 3, 'notes' => '',
      'payment_method' => 'Card', 'card_name' => '', 'card_number' => '', 'ewallet_no' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($f as $k => $v) { $f[$k] = trim($_POST[$k] ?? $v); }
    $f['devices'] = (int)$f['devices'];

    // Step 5/6: delivery speed
    if (!in_array($f['setup_type'], ['Standard', 'Priority'], true)) $errors[] = 'Choose a setup type.';
    // Step 7: network details
    if (strlen($f['address']) < 10) $errors[] = 'Enter a complete installation address.';
    if (strlen($f['city']) < 2) $errors[] = 'Enter your city / municipality.';
    if (!preg_match('/^09\d{9}$/', $f['contact_no'])) $errors[] = 'Contact number must be 11 digits starting with 09.';
    if ($f['devices'] < 1 || $f['devices'] > 50) $errors[] = 'Number of devices must be between 1 and 50.';
    // Step 8/9: payment
    $ref = null; $payStatus = 'Pending';
    $pm = $f['payment_method'];
    if (!in_array($pm, ['Card', 'GCash', 'Maya', 'Cash'], true)) { $errors[] = 'Select a payment method.'; }
    elseif ($pm === 'Card') {
        if (strlen($f['card_name']) < 3) $errors[] = 'Payment error: enter the name on the card.';
        if (!luhn_ok($f['card_number'])) $errors[] = 'Payment error: the card number is invalid. Please check and try again.';
        $ref = '****' . substr(preg_replace('/\D/', '', $f['card_number']), -4);  // store last 4 only
        $payStatus = 'Paid';
    } elseif ($pm === 'GCash' || $pm === 'Maya') {
        if (!preg_match('/^09\d{9}$/', $f['ewallet_no'])) $errors[] = "Payment error: enter your {$pm} mobile number (09XXXXXXXXX).";
        $ref = '*******' . substr($f['ewallet_no'], -4);
        $payStatus = 'Paid';
    }

    if (!$errors) {
        $fee = $f['setup_type'] === 'Priority' ? PRIORITY_FEE : 0;
        $total = $subtotal + $fee;
        $est = date('Y-m-d', strtotime($f['setup_type'] === 'Priority' ? '+2 days' : '+5 days'));
        $orderNo = 'NB-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        global $conn;
        $conn->begin_transaction();
        try {
            foreach ($items as $it) {
                $r = row("SELECT slots, status FROM tbl_plans WHERE plan_id=? FOR UPDATE", 'i', [$it['plan_id']]);
                if (!$r || $r['status'] !== 'Available' || $r['slots'] < $it['qty']) {
                    throw new RuntimeException($it['plan_name'] . ' just became unavailable.');
                }
            }
            q("INSERT INTO tbl_orders (order_no,user_id,setup_type,setup_fee,subtotal,total,address,city,contact_no,devices,notes,payment_method,payment_ref,payment_status,est_date)
               VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)", 'sisdddsssisssss',
              [$orderNo, uid(), $f['setup_type'], $fee, $subtotal, $total, $f['address'], $f['city'], $f['contact_no'],
               $f['devices'], $f['notes'], $pm, $ref, $payStatus, $est]);
            $oid = $conn->insert_id;
            foreach ($items as $it) {
                q("INSERT INTO tbl_order_items (order_id, plan_id, qty, unit_price) VALUES (?,?,?,?)", 'iiid', [$oid, $it['plan_id'], $it['qty'], $it['price']]);
                q("UPDATE tbl_plans SET slots = slots - ? WHERE plan_id=?", 'ii', [$it['qty'], $it['plan_id']]);
                log_tx('order', "Order $orderNo: {$it['qty']} x {$it['plan_name']}", $it['plan_id']);
            }
            $conn->commit();
            $_SESSION['cart'] = [];
            redirect('order_success.php?no=' . urlencode($orderNo));
        } catch (RuntimeException $ex) {
            $conn->rollback();
            $errors[] = $ex->getMessage() . ' Please update your cart.';
        } catch (Throwable $ex) {
            $conn->rollback();
            $errors[] = 'Something went wrong while placing your order. Please try again.';
        }
    }
}
$pageTitle = 'Checkout';
include __DIR__ . '/includes/header.php';
?>
<h1>Checkout</h1>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" id="checkoutForm" data-validate novalidate class="checkout" data-subtotal="<?= (float)$subtotal ?>">
  <?= csrf_field() ?>
  <div>
    <section class="card">
      <h3>1. Setup speed</h3>
      <label class="option"><input type="radio" name="setup_type" value="Standard" <?= $f['setup_type'] === 'Standard' ? 'checked' : '' ?>>
        <span><b>Standard setup</b> — Free · estimated <?= date('M d, Y', strtotime('+5 days')) ?></span></label>
      <label class="option"><input type="radio" name="setup_type" value="Priority" <?= $f['setup_type'] === 'Priority' ? 'checked' : '' ?>>
        <span><b>Priority setup</b> — <?= money(PRIORITY_FEE) ?> · estimated <?= date('M d, Y', strtotime('+2 days')) ?></span></label>
    </section>

    <section class="card">
      <h3>2. Network &amp; installation details</h3>
      <label>Installation address<input type="text" name="address" required minlength="10" placeholder="House/Unit no., street, barangay" value="<?= e($f['address']) ?>"></label>
      <div class="row2">
        <label>City / Municipality<input type="text" name="city" required value="<?= e($f['city']) ?>"></label>
        <label>Contact number<input type="tel" name="contact_no" required pattern="09\d{9}" placeholder="09XXXXXXXXX" value="<?= e($f['contact_no']) ?>"></label>
      </div>
      <label>Expected number of connected devices<input type="number" name="devices" min="1" max="50" required value="<?= (int)$f['devices'] ?>"></label>
      <label>Notes for the technician (optional)<input type="text" name="notes" maxlength="250" value="<?= e($f['notes']) ?>"></label>
    </section>

    <section class="card">
      <h3>3. Payment</h3>
      <label>Payment method
        <select name="payment_method" id="payMethod">
          <?php foreach (['Card' => 'Credit / Debit card', 'GCash' => 'GCash', 'Maya' => 'Maya', 'Cash' => 'Cash on installation'] as $k => $v): ?>
            <option value="<?= $k ?>" <?= $f['payment_method'] === $k ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="pay-pane" data-for="Card">
        <label>Name on card<input type="text" name="card_name" value="<?= e($f['card_name']) ?>"></label>
        <label>Card number<input type="text" name="card_number" id="cardNumber" inputmode="numeric" autocomplete="off" placeholder="4242 4242 4242 4242" value="<?= e($f['card_number']) ?>"></label>
        <small class="muted">Demo only — no real payment is processed and only the last 4 digits are stored.</small>
      </div>
      <div class="pay-pane" data-for="GCash Maya" hidden>
        <label>E-wallet mobile number<input type="tel" name="ewallet_no" placeholder="09XXXXXXXXX" value="<?= e($f['ewallet_no']) ?>"></label>
      </div>
      <div class="pay-pane" data-for="Cash" hidden><p class="muted">Pay the technician when your service is installed.</p></div>
    </section>
  </div>

  <aside class="card summary sticky">
    <h3>Review order</h3>
    <ul class="lines">
      <?php foreach ($items as $it): ?><li><span><?= e($it['plan_name']) ?> × <?= $it['qty'] ?></span><b><?= money($it['line']) ?></b></li><?php endforeach; ?>
    </ul>
    <p class="line"><span>Subtotal</span><b><?= money($subtotal) ?></b></p>
    <p class="line"><span>Setup fee</span><b id="feeOut">₱0.00</b></p>
    <p class="line total"><span>Total</span><b id="totalOut"><?= money($subtotal) ?></b></p>
    <button class="btn btn-block" type="submit">Place order &amp; pay</button>
    <a class="btn btn-outline btn-block" href="<?= url('cart.php') ?>">Back to cart</a>
  </aside>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>
