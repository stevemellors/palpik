<?php
declare(strict_types=1);
require_once __DIR__.'/inc/helpers.php';
require_once __DIR__.'/inc/cart.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    /* EARLY SOLD OUT CHECK */
    require_once __DIR__.'/inc/db.php';
    $pid = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 1);
    $st = db()->prepare('SELECT stock FROM products WHERE id=?');
    $st->execute([$pid]);
    $avail = (int)($st->fetchColumn() ?: 0);
    if ($avail <= 0) { header('Location: /product.php?id='.$pid); exit; }
    if ($qty > $avail) { $_POST['qty'] = $avail; }
    /* END CHECK */
    $pid = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 1);
    if ($pid > 0) cart_add($pid, max(1,$qty));
}
header('Location: /cart.php');
exit;
