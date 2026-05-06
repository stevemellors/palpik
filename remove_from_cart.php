<?php
declare(strict_types=1);
require_once __DIR__.'/inc/helpers.php';
require_once __DIR__.'/inc/cart.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /cart.php');
  exit;
}
csrf_verify();
$pid = (int)($_POST['product_id'] ?? 0);
if ($pid > 0) {
  cart_remove($pid);
}
header('Location: /cart.php');
exit;
