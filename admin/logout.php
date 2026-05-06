<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/session_boot.php';
$_SESSION = [];
$p = session_get_cookie_params();
setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
session_destroy();
header('Location: /admin/login.php');
exit;
