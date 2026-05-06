<?php
declare(strict_types=1);
require_once __DIR__.'/inc/helpers.php';
require_once __DIR__.'/inc/cart.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
  require_once __DIR__.'/inc/db.php';
  $updates = $_POST['qty'] ?? [];
  $cart = cart_get();
  foreach ($updates as $pid => $qty) {
    $pid = (int)$pid;
    $qty = max(0, (int)$qty);
    if ($qty === 0) {
      unset($cart[$pid]);
    } elseif (isset($cart[$pid])) {
      $st = db()->prepare('SELECT stock FROM products WHERE id=?');
      $st->execute([$pid]);
      $avail = (int)($st->fetchColumn() ?: 0);
      $cart[$pid]['qty'] = min($qty, $avail);
      if ($cart[$pid]['qty'] <= 0) unset($cart[$pid]);
    }
  }
  cart_set($cart);
}
header('Location: /cart.php');
exit;
