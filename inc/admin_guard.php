<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__.'/session_boot.php';
}
if (empty($_SESSION['admin_email'])) {
    header('Location: /admin/login.php');
    exit;
}
