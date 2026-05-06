<?php
declare(strict_types=1);
require_once __DIR__.'/db.php';

/**
 * Find an open inquiry for (product_id + buyer_email) or create one,
 * then append the first buyer message. Returns inquiry id.
 */
function inquiry_find_or_create(int $productId, string $buyerEmail, ?string $buyerName, string $firstBody): int {
    $pdo = db();
    // Try existing open thread
    $st = $pdo->prepare("SELECT id FROM product_inquiries WHERE product_id=? AND buyer_email=? AND status='open' ORDER BY id DESC LIMIT 1");
    $st->execute([$productId, $buyerEmail]);
    $row = $st->fetch();
    if ($row) {
        $inqId = (int)$row['id'];
        inquiry_add_message($inqId, 'buyer', $firstBody, true);
        return $inqId;
    }
    // Create new thread
    $st = $pdo->prepare("INSERT INTO product_inquiries (product_id,buyer_email,buyer_name,last_message) VALUES (?,?,?,?)");
    $st->execute([$productId, $buyerEmail, $buyerName, $firstBody]);
    $inqId = (int)$pdo->lastInsertId();
    inquiry_add_message($inqId, 'buyer', $firstBody, false);
    return $inqId;
}

/** Append a message to an inquiry; optionally update last_message */
function inquiry_add_message(int $inquiryId, string $sender, string $body, bool $updateLast=true): void {
    $pdo = db();
    $sender = ($sender === 'admin') ? 'admin' : 'buyer';
    $pdo->beginTransaction();
    try {
        $stm = $pdo->prepare("INSERT INTO inquiry_messages (inquiry_id, sender, body) VALUES (?,?,?)");
        $stm->execute([$inquiryId, $sender, $body]);
        if ($updateLast) {
            $st2 = $pdo->prepare("UPDATE product_inquiries SET last_message=?, updated_at=NOW() WHERE id=?");
            $st2->execute([$body, $inquiryId]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** List inquiries by status ('open' or 'closed'), newest updated first */
function inquiries_list(string $status='open', int $limit=500): array {
    $status = in_array($status, ['open','closed'], true) ? $status : 'open';
    $st = db()->prepare("SELECT pi.*, p.name AS product_name
                         FROM product_inquiries pi
                         JOIN products p ON p.id = pi.product_id
                         WHERE pi.status = ?
                         ORDER BY pi.updated_at DESC
                         LIMIT ?");
    $st->bindValue(1, $status, \PDO::PARAM_STR);
    $st->bindValue(2, $limit, \PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

/** Fetch one inquiry + product name */
function inquiry_get(int $id): ?array {
    $st = db()->prepare("SELECT pi.*, p.name AS product_name
                         FROM product_inquiries pi
                         JOIN products p ON p.id = pi.product_id
                         WHERE pi.id=?");
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

/** Fetch messages for an inquiry (oldest first) */
function inquiry_messages(int $id): array {
    $st = db()->prepare("SELECT * FROM inquiry_messages WHERE inquiry_id=? ORDER BY id ASC");
    $st->execute([$id]);
    return $st->fetchAll();
}

/** Set inquiry status to 'open' or 'closed' */
function inquiry_set_status(int $id, string $status): void {
    $status = in_array($status, ['open','closed'], true) ? $status : 'open';
    $st = db()->prepare("UPDATE product_inquiries SET status=?, updated_at=NOW() WHERE id=?");
    $st->execute([$status, $id]);
}

/** Count inquiries by status (open/closed) */
function inquiries_count(string $status='open'): int {
    $status = in_array($status, ['open','closed'], true) ? $status : 'open';
    $st = db()->prepare("SELECT COUNT(*) AS c FROM product_inquiries WHERE status=?");
    $st->execute([$status]);
    $row = $st->fetch();
    return (int)($row['c'] ?? 0);
}
