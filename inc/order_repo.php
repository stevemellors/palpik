<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

/** Create order + items; returns order id */
function order_create(array $data, array $cart): int {
    if (!$cart) throw new RuntimeException('Cart is empty');
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Re-fetch current prices and lock rows to check stock
        $stProd = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id = ? FOR UPDATE");
        $verified = [];
        foreach ($cart as $line) {
            $stProd->execute([(int)$line['id']]);
            $prod = $stProd->fetch();
            if (!$prod) {
                throw new RuntimeException('"'.htmlspecialchars((string)($line['name'] ?? ''))
                    .'" is no longer available.');
            }
            $avail = (int)$prod['stock'];
            $qty   = max(1, (int)$line['qty']);
            if ($avail < $qty) {
                throw new RuntimeException('"'.htmlspecialchars((string)$prod['name'])
                    .'" only has '.$avail.' in stock (you requested '.$qty.').');
            }
            $verified[] = [
                'id'    => (int)$prod['id'],
                'name'  => $prod['name'],
                'price' => (float)$prod['price'],
                'qty'   => $qty,
            ];
        }

        $subtotal = 0.0;
        foreach ($verified as $line) $subtotal += $line['price'] * $line['qty'];
        $tax      = round($subtotal * 0.08, 2);
        $shipping = $subtotal > 100 ? 0.00 : 6.99;
        $total    = round($subtotal + $tax + $shipping, 2);

        $st = $pdo->prepare("INSERT INTO `orders`
            (`email`,`name`,`address`,`city`,`state`,`zip`,`subtotal`,`tax`,`shipping`,`total`,`payment_method`,`status`)
            VALUES (:email,:name,:address,:city,:state,:zip,:subtotal,:tax,:shipping,:total,:payment_method,:status)");
        $st->execute([
            ':email'          => $data['email'] ?? '',
            ':name'           => $data['name'] ?? '',
            ':address'        => $data['address'] ?? '',
            ':city'           => $data['city'] ?? '',
            ':state'          => $data['state'] ?? '',
            ':zip'            => $data['zip'] ?? '',
            ':subtotal'       => $subtotal,
            ':tax'            => $tax,
            ':shipping'       => $shipping,
            ':total'          => $total,
            ':payment_method' => $data['payment_method'] ?? 'Test',
            ':status'         => 'pending',
        ]);
        $orderId = (int)$pdo->lastInsertId();

        $sti  = $pdo->prepare("INSERT INTO `order_items` (`order_id`,`product_id`,`name`,`price`,`qty`) VALUES (:order_id,:product_id,:name,:price,:qty)");
        $stUp = $pdo->prepare("UPDATE `products` SET `stock` = stock - :qty WHERE `id` = :id");
        foreach ($verified as $line) {
            $sti->execute([
                ':order_id'   => $orderId,
                ':product_id' => $line['id'],
                ':name'       => $line['name'],
                ':price'      => $line['price'],
                ':qty'        => $line['qty'],
            ]);
            $stUp->execute([':qty' => $line['qty'], ':id' => $line['id']]);
        }

        $pdo->commit();
        return $orderId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function order_get(int $id): ?array {
    $st = db()->prepare("SELECT * FROM `orders` WHERE id=?");
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}
function order_items(int $orderId): array {
    $st = db()->prepare("SELECT `id`,`product_id`,`name`,`price`,`qty` FROM `order_items` WHERE order_id=? ORDER BY id ASC");
    $st->execute([$orderId]);
    return $st->fetchAll();
}

/** Active (not archived) orders */
function orders_all(int $limit=100): array {
    $st = db()->prepare("SELECT `id`,`created_at`,`name`,`email`,`total`,`status`
                         FROM `orders` WHERE `archived_at` IS NULL
                         ORDER BY id DESC LIMIT ?");
    $st->bindValue(1, $limit, \PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

/** Archive with shipped status + timestamp */
function order_archive_ship(int $id): void {
    $pdo = db();
    $st = $pdo->prepare("UPDATE `orders` SET `status`='shipped', `archived_at`=NOW() WHERE `id`=?");
    $st->execute([$id]);
}

/** Unarchive (bring back to active list) — keeps current status */
function order_unarchive(int $id): void {
    $st = db()->prepare("UPDATE `orders` SET `archived_at`=NULL WHERE `id`=?");
    $st->execute([$id]);
}

/** Search archived orders by id/email/name (case-insensitive) */
function orders_archived_search(string $q='', int $limit=200): array {
    $pdo = db();
    if ($q === '') {
        $st = $pdo->prepare("SELECT `id`,`created_at`,`name`,`email`,`total`,`status`,`archived_at`
                             FROM `orders`
                             WHERE `archived_at` IS NOT NULL
                             ORDER BY `archived_at` DESC, id DESC
                             LIMIT ?");
        $st->bindValue(1, $limit, \PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }
    // try numeric id match or LIKE on email/name
    if (ctype_digit($q)) {
        $st = $pdo->prepare("SELECT `id`,`created_at`,`name`,`email`,`total`,`status`,`archived_at`
                             FROM `orders`
                             WHERE `archived_at` IS NOT NULL AND `id` = ?
                             ORDER BY `archived_at` DESC, id DESC
                             LIMIT ?");
        $st->bindValue(1, (int)$q, \PDO::PARAM_INT);
        $st->bindValue(2, $limit, \PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll();
        if ($rows) return $rows;
    }
    $like = '%'.$q.'%';
    $st = $pdo->prepare("SELECT `id`,`created_at`,`name`,`email`,`total`,`status`,`archived_at`
                         FROM `orders`
                         WHERE `archived_at` IS NOT NULL
                           AND (LOWER(`email`) LIKE LOWER(?) OR LOWER(`name`) LIKE LOWER(?))
                         ORDER BY `archived_at` DESC, id DESC
                         LIMIT ?");
    $st->bindValue(1, $like, \PDO::PARAM_STR);
    $st->bindValue(2, $like, \PDO::PARAM_STR);
    $st->bindValue(3, $limit, \PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

/** Update status (still available if you want non-archiving changes) */
function order_update_status(int $id, string $status): void {
    $allowed = ['pending','paid','shipped','completed','canceled'];
    if (!in_array($status, $allowed, true)) throw new InvalidArgumentException('Bad status');
    $st = db()->prepare("UPDATE `orders` SET `status` = ? WHERE `id` = ?");
    $st->execute([$status, $id]);
}

/** Active orders with optional status + query (by #, email, name) */
function orders_active_search(?string $status=null, string $q='', int $limit=200): array {
    $pdo = db();
    $where = ['archived_at IS NULL'];
    $args  = [];
    if ($status && $status !== 'all') {
        $where[] = '`status` = ?';
        $args[] = $status;
    }
    if ($q !== '') {
        if (ctype_digit($q)) {
            $where[] = 'id = ?';
            $args[] = (int)$q;
        } else {
            $where[] = '(LOWER(email) LIKE LOWER(?) OR LOWER(name) LIKE LOWER(?))';
            $like = '%'.$q.'%';
            $args[] = $like; $args[] = $like;
        }
    }
    $sql = "SELECT `id`,`created_at`,`name`,`email`,`total`,`status`
            FROM `orders`
            WHERE ".implode(' AND ', $where)."
            ORDER BY id DESC
            LIMIT ?";
    $st = $pdo->prepare($sql);
    foreach ($args as $i => $v) $st->bindValue($i+1, $v);
    $st->bindValue(count($args)+1, $limit, \PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

/** Bulk archive + mark shipped */
function orders_archive_ship_bulk(array $ids): int {
    if (!$ids) return 0;
    $pdo = db();
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $st  = $pdo->prepare("UPDATE `orders` SET `status`='shipped', `archived_at`=NOW() WHERE `id` IN ($in)");
    foreach ($ids as $i => $id) $st->bindValue($i+1, (int)$id, \PDO::PARAM_INT);
    $st->execute();
    return $st->rowCount();
}
