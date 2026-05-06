<?php
declare(strict_types=1);
require_once __DIR__.'/inc/config.php';
require_once __DIR__.'/inc/helpers.php';
require_once __DIR__.'/inc/cart.php';
require_once __DIR__.'/inc/nav.php';

$cart = cart_get();
$subtotal = cart_total();
?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cart • <?= h(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/styles.css">
<link rel="stylesheet" href="/assets/theme-override.css">
<script src="/assets/theme.js" defer></script>
</head>
<body class="container">
<?= site_nav('Cart') ?>
<h1>Your Cart</h1>
<?php if (!$cart): ?>
  <p class="kicker">Your cart is empty.</p>
  <p><a class="btn" href="/">← Continue shopping</a></p>
<?php else: ?>
  <form method="post" action="/update_cart.php">
    <?= csrf_field() ?>
    <div class="card">
      <div class="table-wrap">
      <table class="table stacked" style="width:100%;">
        <thead>
          <tr>
            <th>Item</th>
            <th style="width:120px;">Price</th>
            <th style="width:120px;">Qty</th>
            <th style="width:140px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cart as $line): ?>
            <tr>
              <td data-label="Item"><?= h($line['name']) ?></td>
              <td data-label="Price"><?= money((float)$line['price']) ?></td>
              <td data-label="Qty">
                <input type="number" name="qty[<?= (int)$line['id'] ?>]" value="<?= (int)$line['qty'] ?>" min="0" step="1" style="width:90px;">
              </td>
              <td data-label="Actions">
                <!-- Remove = submit qty[ID]=0 to update_cart.php -->
                <button class="btn" type="submit" name="qty[<?= (int)$line['id'] ?>]" value="0"
                        onclick="return confirm('Remove this item?')">Remove</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

      <p style="margin-top:10px;"><strong>Subtotal:</strong> <?= money($subtotal) ?></p>

      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
        <a class="btn" href="/">← Continue shopping</a>
        <button class="btn" type="submit">Update Cart</button>
        <a class="btn" href="/checkout.php">Checkout</a>
      </div>
    </div>
  </form>
<?php endif; ?>

<footer class="site-footer">
  <p>&copy; <?= date('Y') ?> <?= h(SITE_NAME) ?>. All rights reserved.</p>
  <div class="foot-links">
    <a href="/">Home</a><span>·</span><a href="/cart.php">Cart</a><span>·</span><a href="/admin/login.php">Admin</a>
  </div>
</footer>
</body></html>
