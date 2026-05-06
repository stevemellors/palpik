<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__.'/db.php';

function cart_get(): array {
    return $_SESSION['cart'] ?? [];
}
function cart_set(array $cart): void {
    $_SESSION['cart'] = $cart;
}
function cart_add(int $productId, int $qty=1): void {
    $st = db()->prepare("SELECT id, name, price, image, stock FROM products WHERE id=?");
    $st->execute([$productId]);
    $p = $st->fetch();
    if (!$p) return;
    $available = (int)($p['stock'] ?? 0);
    if ($available <= 0) return;
    $qty = min(max(1, $qty), $available);

    $cart = cart_get();
    if (!isset($cart[$productId])) {
        $cart[$productId] = [
            'id'    => (int)$p['id'],
            'name'  => $p['name'],
            'price' => (float)$p['price'],
            'image' => $p['image'] ?? null,
            'qty'   => 0,
        ];
    }
    $cart[$productId]['qty'] = min($cart[$productId]['qty'] + $qty, $available);
    cart_set($cart);
}
function cart_remove(int $productId): void {
    $cart = cart_get();
    unset($cart[$productId]);
    cart_set($cart);
}
function cart_total(): float {
    $sum = 0;
    foreach (cart_get() as $line) $sum += ((float)$line['price']) * ((int)$line['qty']);
    return $sum;
}
function cart_count(): int {
    $n = 0;
    foreach (cart_get() as $line) $n += (int)$line['qty'];
    return $n;
}
