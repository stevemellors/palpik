<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/order_repo.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['admin_email'])) { header('Location: /admin/login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /admin/orders.php'); exit; }
csrf_verify();
$id = (int)($_POST['id'] ?? 0);
if ($id > 0) { order_archive_ship($id); }
header('Location: /admin/orders.php'); exit;
