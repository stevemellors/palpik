<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/message_repo.php';
require_once __DIR__."/../inc/admin_guard.php";
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /admin/messages.php'); exit; }
csrf_verify();
$id = (int)($_POST['id'] ?? 0);
$body = trim($_POST['body'] ?? '');
if ($id > 0 && $body !== '') {
  $inq = inquiry_get($id);
  if ($inq) {
    inquiry_add_message($id, 'admin', $body, true);
    $subject = 'Reply about: '.$inq['product_name'].' (Inquiry #'.$id.')';
    $html = nl2br(htmlspecialchars($body));
    $headers = [
      'MIME-Version: 1.0',
      'Content-type: text/html; charset=UTF-8',
      'From: support@palletpiks.com'
    ];
    @mail($inq['buyer_email'], $subject, $html, implode("\r\n", $headers));
  }
}
header('Location: /admin/message_view.php?id='.$id); 
exit;
