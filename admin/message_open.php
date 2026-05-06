<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/message_repo.php';
require_once __DIR__."/../inc/admin_guard.php";
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /admin/messages.php'); exit; }
csrf_verify();
$id = (int)($_POST['id'] ?? 0);
if ($id > 0) inquiry_set_status($id, 'open');
header('Location: /admin/message_view.php?id='.$id); 
exit;
