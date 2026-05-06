<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

/** List all products (with optional category filter) */
function products_all(?int $categoryId=null): array {
    if ($categoryId) {
        $sql = "SELECT p.id, p.name, p.description, p.price, p.image, p.category_id,
                       c.name AS category_name
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.category_id = ?
                ORDER BY p.id DESC";
        $st = db()->prepare($sql);
        $st->execute([$categoryId]);
        return $st->fetchAll();
    }
    $sql = "SELECT p.id, p.name, p.description, p.price, p.image, p.category_id,
                   c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            ORDER BY p.id DESC";
    return db()->query($sql)->fetchAll();
}

/** Get one product */
function product_get(int $id): ?array {
    $sql = "SELECT id, name, description, price, image, category_id, stock
            FROM products WHERE id = ?";
    $st = db()->prepare($sql);
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

/** Create product, returns new id */
function product_create(array $data): int {
    $st = db()->prepare("
        INSERT INTO products (category_id, name, description, price, image, stock)
        VALUES (:category_id, :name, :description, :price, :image, :stock)
    ");
    $st->execute([
        ':category_id' => ($data['category_id'] ?? null) ?: null,
        ':name'        => $data['name'] ?? '',
        ':description' => $data['description'] ?? null,
        ':price'       => $data['price'] ?? 0,
        ':image'       => $data['image'] ?? null,
        ':stock'       => $data['stock'] ?? 0,
    ]);
    return (int)db()->lastInsertId();
}

/** Update product */
function product_update(int $id, array $data): void {
    $st = db()->prepare("
        UPDATE products
        SET category_id = :category_id,
            name        = :name,
            description = :description,
            price       = :price,
            image       = :image,
            stock       = :stock
        WHERE id = :id
    ");
    $st->execute([
        ':id'          => $id,
        ':category_id' => ($data['category_id'] ?? null) ?: null,
        ':name'        => $data['name'] ?? '',
        ':description' => $data['description'] ?? null,
        ':price'       => $data['price'] ?? 0,
        ':image'       => $data['image'] ?? null,
        ':stock'       => $data['stock'] ?? 0,
    ]);
}

/** Delete product */
function product_delete(int $id): void {
    $st = db()->prepare("DELETE FROM products WHERE id = ?");
    $st->execute([$id]);
}

/** Existing helper for storefront category page */
function products_by_category_id(int $categoryId): array {
    $sql = "SELECT id, name, description, price, image
            FROM products
            WHERE category_id = ?
            ORDER BY id DESC";
    $st = db()->prepare($sql);
    $st->execute([$categoryId]);
    return $st->fetchAll();
}

function products_by_category_paged(int $categoryId, int $limit, int $offset): array {
    $st = db()->prepare("SELECT id, name, description, price, image
                         FROM products WHERE category_id = ?
                         ORDER BY id DESC LIMIT ? OFFSET ?");
    $st->bindValue(1, $categoryId, \PDO::PARAM_INT);
    $st->bindValue(2, $limit,      \PDO::PARAM_INT);
    $st->bindValue(3, $offset,     \PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

function products_count_by_category(int $categoryId): int {
    $st = db()->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $st->execute([$categoryId]);
    return (int)$st->fetchColumn();
}

function products_search(string $q, int $limit, int $offset): array {
    $like = '%'.$q.'%';
    $st = db()->prepare(
        "SELECT p.id, p.name, p.description, p.price, p.image, p.stock, c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.name LIKE ? OR p.description LIKE ?
         ORDER BY p.name ASC LIMIT ? OFFSET ?"
    );
    $st->bindValue(1, $like);
    $st->bindValue(2, $like);
    $st->bindValue(3, $limit,  \PDO::PARAM_INT);
    $st->bindValue(4, $offset, \PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

function products_search_count(string $q): int {
    $like = '%'.$q.'%';
    $st = db()->prepare("SELECT COUNT(*) FROM products WHERE name LIKE ? OR description LIKE ?");
    $st->execute([$like, $like]);
    return (int)$st->fetchColumn();
}
