<?php
declare(strict_types=1);
require_once __DIR__.'/inc/config.php';
require_once __DIR__.'/inc/helpers.php';
require_once __DIR__.'/inc/cart.php';
require_once __DIR__.'/inc/order_repo.php';
require_once __DIR__.'/inc/mailer.php';
require_once __DIR__.'/inc/nav.php';

$cart = cart_get();
$subtotal = cart_total();
$tax = round($subtotal * 0.08, 2);
$shipping = $subtotal > 100 ? 0.00 : 6.99;
$total = round($subtotal + $tax + $shipping, 2);

$error = '';
$orderId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (!$cart) {
        $error = 'Your cart is empty.';
    } else {
        $required = ['name','email','address','city','state','zip'];
        foreach ($required as $r) {
            if (trim($_POST[$r] ?? '') === '') { $error = 'Please fill all required fields.'; break; }
        }
        if (!$error && !filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        }
    }

    if (!$error) {
        try {
            $orderId = order_create([
                'name'           => trim($_POST['name'] ?? ''),
                'email'          => trim($_POST['email'] ?? ''),
                'address'        => trim($_POST['address'] ?? ''),
                'city'           => trim($_POST['city'] ?? ''),
                'state'          => trim($_POST['state'] ?? ''),
                'zip'            => trim($_POST['zip'] ?? ''),
                'payment_method' => 'Test',
            ], $cart);

            // Load order + items
            $ord   = order_get($orderId);
            $items = order_items($orderId);

            // Email customer
            @send_order_email($ord['email'], $ord, $items);

            // Email owner (HTML slip) to palletpiks@gmail.com, include admin view link
            $adminLink = SITE_URL . '/admin/order_view.php?id=' . $orderId; // Adjust to full URL if you prefer absolute link
            @send_order_admin('palletpiks@gmail.com', $ord, $items, 'noreply@palletpiks.com', $adminLink);

            // Clear cart and show thank-you
            cart_set([]);
            ?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Thank you • <?= h(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/styles.css">
<link rel="stylesheet" href="/assets/theme-override.css">
<script src="/assets/theme.js" defer></script>
</head>
            <body class="container">
            <?= site_nav('Thank you') ?>
            <h1>Thank you!</h1>
            <div class="card">
              <div class="kicker">Order placed</div>
              <p>Your order #<?= (int)$orderId ?> has been placed.</p>
              <p><strong>Total:</strong> <?= money((float)$ord['total']) ?></p>
              <a class="btn" href="/">Back to store</a>
            </div>
            </body></html><?php
            exit;
        } catch (Throwable $e) {
            $error = 'Could not place order. Error: '.$e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Checkout • <?= h(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/styles.css">
<link rel="stylesheet" href="/assets/theme-override.css">
<script src="/assets/theme.js" defer></script>
</head>
<body class="container">
<?= site_nav('Checkout') ?>
<h1>Checkout</h1>

<?php if ($error): ?>
  <p style="color:#ef4444;"><?= h($error) ?></p>
<?php endif; ?>

<?php if (!$cart): ?>
  <p class="kicker">Your cart is empty.</p>
  <p><a class="btn" href="/">← Continue shopping</a></p>
<?php else: ?>
  <div class="grid">
    <div class="card">
      <div class="kicker">Contact & Shipping</div>
      <form method="post" class="form">
        <?= csrf_field() ?>
        <label>Full Name <input name="name" required></label>
        <label>Email <input type="email" name="email" required></label>
        <label>Address <input name="address" required></label>
        <label>City <input name="city" required></label>
        <label>State/Prov <input name="state" required></label>
        <label>ZIP/Postal <input name="zip" required></label>
        <div style="display:flex;gap:8px;margin-top:10px;">
          <a class="btn" href="/cart.php">← Back to Cart</a>
          <button class="btn" type="submit">Place Order</button>
        </div>
      </form>
    </div>

    <div class="card">
      <div class="kicker">Order Summary</div>
      <div class="table-wrap">
      <table class="table stacked" style="width:100%;">
        <thead><tr><th>Item</th><th style="width:80px;">Qty</th><th style="width:120px;">Price</th></tr></thead>
        <tbody>
          <?php foreach ($cart as $line): ?>
            <tr>
              <td data-label="Item"><?= h($line['name']) ?></td>
              <td data-label="Qty"><?= (int)$line['qty'] ?></td>
              <td data-label="Price"><?= money((float)$line['price']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
      <p style="margin-top:8px;">
        <strong>Subtotal:</strong> <?= money((float)$subtotal) ?><br>
        <strong>Shipping:</strong> <?= money((float)$shipping) ?><br>
        <strong>Tax:</strong> <?= money((float)$tax) ?><br>
        <strong>Total:</strong> <?= money((float)$total) ?>
      </p>
    </div>
  </div>
<?php endif; ?>
</body></html>
