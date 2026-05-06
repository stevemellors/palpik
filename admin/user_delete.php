<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/user_repo.php';

require_once __DIR__."/../inc/admin_guard.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /admin/users.php'); exit; }
csrf_verify();
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id > 0) {
  $u = user_get($id);
  if ($u && $u['email'] !== $_SESSION['admin_email']) {
    user_delete($id);
  }
}
header('Location: /admin/users.php'); exit;
