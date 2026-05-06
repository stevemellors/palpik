<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/product_repo.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['admin_email'])) { header('Location: /admin/login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /admin/products.php'); exit; }
csrf_verify();
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id) {
    $existing = product_get($id);
    product_delete($id);
    if ($existing && !empty($existing['image'])) {
        $imgPath = __DIR__.'/../uploads/'.basename($existing['image']);
        if (file_exists($imgPath)) @unlink($imgPath);
    }
}
header('Location: /admin/products.php');
exit;
