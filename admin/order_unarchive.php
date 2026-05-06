<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/order_repo.php';
require_once __DIR__."/../inc/admin_guard.php";
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /admin/orders.php'); exit; }
csrf_verify();
$id = (int)($_POST['id'] ?? 0);
if ($id > 0) { order_unarchive($id); }
header('Location: /admin/order_view.php?id='.$id); exit;
