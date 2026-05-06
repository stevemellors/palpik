<?php
declare(strict_types=1);

require_once __DIR__.'/db.php';

function users_all(): array {
  $st = db()->query("SELECT id,name,email,role,created_at FROM users ORDER BY id DESC");
  return $st->fetchAll();
}

function user_get(int $id): ?array {
  $st = db()->prepare("SELECT id,name,email,password_hash,role,created_at FROM users WHERE id=?");
  $st->execute([$id]);
  $row = $st->fetch();
  return $row ?: null;
}

function user_get_by_email(string $email): ?array {
  $st = db()->prepare("SELECT id,name,email,password_hash,role,created_at FROM users WHERE email=?");
  $st->execute([$email]);
  $row = $st->fetch();
  return $row ?: null;
}

function user_create(string $name, string $email, string $plainPassword, string $role='admin'): int {
  $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
  $st = db()->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)");
  $st->execute([$name, $email, $hash, $role]);
  return (int)db()->lastInsertId();
}

function user_update(int $id, string $name, string $email, string $role): void {
  $st = db()->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
  $st->execute([$name, $email, $role, $id]);
}

function user_set_password(int $id, string $plainPassword): void {
  $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
  $st = db()->prepare("UPDATE users SET password_hash=? WHERE id=?");
  $st->execute([$hash, $id]);
}

function user_delete(int $id): void {
  $st = db()->prepare("DELETE FROM users WHERE id=?");
  $st->execute([$id]);
}

function user_verify_admin_login(string $email, string $plainPassword): bool {
  $u = user_get_by_email($email);
  if (!$u) return false;
  if ($u['role'] !== 'admin') return false;
  return password_verify($plainPassword, (string)$u['password_hash']);
}

function user_create_reset_token(string $email): ?string {
  $u = user_get_by_email($email);
  if (!$u || $u['role'] !== 'admin') return null;
  $token = bin2hex(random_bytes(32));
  $exp   = date('Y-m-d H:i:s', time() + 3600);
  $st = db()->prepare("UPDATE users SET reset_token=?, reset_token_exp=? WHERE id=?");
  $st->execute([$token, $exp, $u['id']]);
  return $token;
}

function user_verify_reset_token(string $token): ?array {
  $st = db()->prepare("SELECT id, email FROM users WHERE reset_token=? AND reset_token_exp > NOW()");
  $st->execute([$token]);
  $row = $st->fetch();
  return $row ?: null;
}

function user_consume_reset_token(int $id, string $newPassword): void {
  $hash = password_hash($newPassword, PASSWORD_BCRYPT);
  $st = db()->prepare("UPDATE users SET password_hash=?, reset_token=NULL, reset_token_exp=NULL WHERE id=?");
  $st->execute([$hash, $id]);
}
