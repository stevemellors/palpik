<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

/** List all categories (id, name, description) */
function categories_all(): array {
    $sql = "SELECT id, name, description FROM categories ORDER BY name ASC";
    return db()->query($sql)->fetchAll();
}

/** Get one category by id */
function category_get(int $id): ?array {
    $st = db()->prepare("SELECT id, name, description FROM categories WHERE id = ?");
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

/** Create a category, returns new id */
function category_create(string $name, ?string $description = null): int {
    $st = db()->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    $st->execute([$name, $description]);
    return (int)db()->lastInsertId();
}

/** Update a category */
function category_update(int $id, string $name, ?string $description = null): void {
    $st = db()->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
    $st->execute([$name, $description, $id]);
}

/** Delete a category */
function category_delete(int $id): void {
    $st = db()->prepare("DELETE FROM categories WHERE id = ?");
    $st->execute([$id]);
}
